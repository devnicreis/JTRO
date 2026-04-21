<?php

require_once __DIR__ . '/../Core/Database.php';

class AvisoRepository
{
    private PDO $connection;
    private const MAX_CHAVE_LENGTH = 160;

    public function __construct()
    {
        $this->connection = Database::getConnection();
    }

    public function marcarComoLido(int $usuarioId, string $chaveAviso): void
    {
        $chaveAviso = $this->normalizarChave($chaveAviso);

        $stmt = $this->connection->prepare("
            INSERT INTO avisos_lidos (usuario_id, chave_aviso, lido_em)
            VALUES (:usuario_id, :chave_aviso, :lido_em)
            ON CONFLICT(usuario_id, chave_aviso)
            DO UPDATE SET lido_em = excluded.lido_em
        ");

        $stmt->execute([
            ':usuario_id' => $usuarioId,
            ':chave_aviso' => $chaveAviso,
            ':lido_em' => date('Y-m-d H:i:s')
        ]);
    }

    public function desmarcarComoLido(int $usuarioId, string $chaveAviso): void
    {
        $chaveAviso = $this->normalizarChave($chaveAviso);

        $stmt = $this->connection->prepare("
            DELETE FROM avisos_lidos
            WHERE usuario_id = :usuario_id
            AND chave_aviso = :chave_aviso
        ");

        $stmt->execute([
            ':usuario_id' => $usuarioId,
            ':chave_aviso' => $chaveAviso
        ]);
    }

    public function listarChavesLidas(int $usuarioId): array
    {
        $stmt = $this->connection->prepare("
            SELECT chave_aviso
            FROM avisos_lidos
            WHERE usuario_id = :usuario_id
        ");

        $stmt->execute([':usuario_id' => $usuarioId]);

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function criarAvisoSistema(
        int $usuarioId,
        string $chaveAviso,
        string $tipo,
        string $titulo,
        string $mensagem,
        ?string $link = null
    ): void {
        $chaveAviso = $this->normalizarChave($chaveAviso);

        $stmt = $this->connection->prepare("
            INSERT INTO avisos_sistema (
                usuario_id, chave_aviso, tipo, titulo, mensagem, link, created_at
            ) VALUES (
                :usuario_id, :chave_aviso, :tipo, :titulo, :mensagem, :link, :created_at
            )
            ON CONFLICT(chave_aviso)
            DO UPDATE SET
                usuario_id = excluded.usuario_id,
                tipo = excluded.tipo,
                titulo = excluded.titulo,
                mensagem = excluded.mensagem,
                link = excluded.link,
                created_at = excluded.created_at
        ");

        $stmt->execute([
            ':usuario_id' => $usuarioId,
            ':chave_aviso' => $chaveAviso,
            ':tipo' => $tipo,
            ':titulo' => $titulo,
            ':mensagem' => $mensagem,
            ':link' => $link,
            ':created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function normalizarChave(string $chaveAviso): string
    {
        $chaveAviso = trim($chaveAviso);
        if ($chaveAviso === '') {
            throw new InvalidArgumentException('Chave de aviso invalida.');
        }

        if (mb_strlen($chaveAviso) > self::MAX_CHAVE_LENGTH) {
            $chaveAviso = mb_substr($chaveAviso, 0, self::MAX_CHAVE_LENGTH);
        }

        return $chaveAviso;
    }

    public function listarAvisosSistema(int $usuarioId): array
    {
        $stmt = $this->connection->prepare("
            SELECT id, usuario_id, chave_aviso, tipo, titulo, mensagem, link, created_at
            FROM avisos_sistema
            WHERE usuario_id = :usuario_id
            ORDER BY datetime(created_at) DESC, id DESC
        ");
        $stmt->execute([':usuario_id' => $usuarioId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function contarAvisosNaoLidos(int $usuarioId, array $chavesAviso): int
    {
        if (count($chavesAviso) === 0) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($chavesAviso), '?'));
        $params = array_merge([$usuarioId], $chavesAviso);

        $stmt = $this->connection->prepare("
            SELECT COUNT(*)
            FROM avisos_sistema a
            WHERE a.usuario_id = ?
              AND a.chave_aviso IN ({$placeholders})
              AND NOT EXISTS (
                SELECT 1
                FROM avisos_lidos al
                WHERE al.usuario_id = a.usuario_id
                  AND al.chave_aviso = a.chave_aviso
              )
        ");
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    public function sincronizarAvisosAniversarioDoDia(?string $dataBase = null): void
    {
        $dataBase = $dataBase ?: date('Y-m-d');
        $prefixoHoje = 'aniversario_' . date('Ymd', strtotime($dataBase)) . '_';

        $stmt = $this->connection->prepare("
            SELECT p.id, p.nome, p.grupo_familiar_id, gf.nome AS grupo_familiar_nome
            FROM pessoas p
            LEFT JOIN grupos_familiares gf ON gf.id = p.grupo_familiar_id
            WHERE p.ativo = 1
              AND p.data_nascimento IS NOT NULL
              AND strftime('%m-%d', p.data_nascimento) = strftime('%m-%d', :data_base)
        ");
        $stmt->execute([':data_base' => $dataBase]);
        $aniversariantes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($aniversariantes as $aniversariante) {
            $pessoaId = (int) $aniversariante['id'];
            $nomePessoa = (string) $aniversariante['nome'];
            $grupoId = (int) ($aniversariante['grupo_familiar_id'] ?? 0);
            $grupoNome = trim((string) ($aniversariante['grupo_familiar_nome'] ?? ''));

            $admins = $this->connection->query("
                SELECT id, nome
                FROM pessoas
                WHERE ativo = 1
                  AND cargo = 'admin'
                ORDER BY nome ASC
            ")->fetchAll(PDO::FETCH_ASSOC);

            foreach ($admins as $admin) {
                $usuarioId = (int) $admin['id'];
                $this->criarAvisoSistema(
                    $usuarioId,
                    $prefixoHoje . 'admin_' . $usuarioId . '_' . $pessoaId,
                    'aniversario',
                    'Aniversário de membro',
                    $nomePessoa . ' está fazendo aniversário hoje.' . ($grupoNome !== '' ? ' GF: ' . $grupoNome . '.' : ''),
                    '/pessoas.php'
                );
            }

            if ($grupoId > 0) {
                $stmtLideres = $this->connection->prepare("
                    SELECT p.id, p.nome
                    FROM grupo_lideres gl
                    INNER JOIN pessoas p ON p.id = gl.pessoa_id
                    WHERE gl.grupo_familiar_id = :grupo_id
                      AND p.ativo = 1
                    ORDER BY p.nome ASC
                ");
                $stmtLideres->execute([':grupo_id' => $grupoId]);
                $lideres = $stmtLideres->fetchAll(PDO::FETCH_ASSOC);

                foreach ($lideres as $lider) {
                    $usuarioId = (int) $lider['id'];
                    $this->criarAvisoSistema(
                        $usuarioId,
                        $prefixoHoje . 'lider_' . $usuarioId . '_' . $pessoaId,
                        'aniversario',
                        'Aniversário no seu GF',
                        $nomePessoa . ' está fazendo aniversário hoje.' . ($grupoNome !== '' ? ' GF: ' . $grupoNome . '.' : ''),
                        '/pessoas.php'
                    );
                }
            }
        }
    }

    public function sincronizarAvisosCantina(?string $dataBase = null): void
    {
        $dataBase = $dataBase ?: date('Y-m-d');

        $datasAlvo = [
            7 => date('Y-m-d', strtotime($dataBase . ' +7 days')),
            1 => date('Y-m-d', strtotime($dataBase . ' +1 day')),
            0 => $dataBase,
        ];

        $placeholders = implode(',', array_fill(0, count($datasAlvo), '?'));
        $stmtEscalas = $this->connection->prepare("
            SELECT
                ce.id,
                ce.data_escala,
                ce.grupo_familiar_id,
                gf.nome AS grupo_nome
            FROM cantina_escalas ce
            INNER JOIN grupos_familiares gf ON gf.id = ce.grupo_familiar_id
            WHERE gf.ativo = 1
              AND ce.data_escala IN ({$placeholders})
            ORDER BY ce.data_escala ASC
        ");
        $stmtEscalas->execute(array_values($datasAlvo));
        $escalas = $stmtEscalas->fetchAll(PDO::FETCH_ASSOC);

        $chavesValidas = [];

        foreach ($escalas as $escala) {
            $dataEscala = (string) $escala['data_escala'];
            $escalaId = (int) $escala['id'];
            $grupoId = (int) $escala['grupo_familiar_id'];
            $grupoNome = trim((string) ($escala['grupo_nome'] ?? ''));
            $diasRestantes = (int) floor((strtotime($dataEscala) - strtotime($dataBase)) / 86400);

            if (!in_array($diasRestantes, [7, 1, 0], true)) {
                continue;
            }

            $stmtLideres = $this->connection->prepare("
                SELECT p.id
                FROM grupo_lideres gl
                INNER JOIN pessoas p ON p.id = gl.pessoa_id
                WHERE gl.grupo_familiar_id = :grupo_id
                  AND p.ativo = 1
                ORDER BY p.nome ASC
            ");
            $stmtLideres->execute([':grupo_id' => $grupoId]);
            $lideres = $stmtLideres->fetchAll(PDO::FETCH_COLUMN);

            foreach ($lideres as $liderId) {
                $liderId = (int) $liderId;
                $chave = 'cantina_' . $escalaId . '_' . $liderId . '_' . $diasRestantes;
                $chavesValidas[] = $chave;

                if ($diasRestantes === 7) {
                    $titulo = 'Escala da cantina em 7 dias';
                    $mensagem = 'O GF ' . $grupoNome . ' está escalado para a cantina em ' . $this->formatarDataBr($dataEscala) . '.';
                } elseif ($diasRestantes === 1) {
                    $titulo = 'Escala da cantina amanhã';
                    $mensagem = 'O GF ' . $grupoNome . ' estará na escala da cantina amanhã (' . $this->formatarDataBr($dataEscala) . ').';
                } else {
                    $titulo = 'Escala da cantina hoje';
                    $mensagem = 'O GF ' . $grupoNome . ' está na escala da cantina hoje.';
                }

                $this->criarAvisoSistema(
                    $liderId,
                    $chave,
                    'cantina',
                    $titulo,
                    $mensagem,
                    '/index.php'
                );
            }
        }

        if (count($chavesValidas) > 0) {
            $params = $chavesValidas;
            $params[] = 'cantina';
            $stmtLimpar = $this->connection->prepare("
                DELETE FROM avisos_sistema
                WHERE tipo = ?
                  AND chave_aviso NOT IN (" . implode(',', array_fill(0, count($chavesValidas), '?')) . ")
            ");
            $stmtLimpar->execute(array_merge(['cantina'], $chavesValidas));
        } else {
            $stmtLimpar = $this->connection->prepare("
                DELETE FROM avisos_sistema
                WHERE tipo = :tipo
            ");
            $stmtLimpar->execute([':tipo' => 'cantina']);
        }
    }

    private function formatarDataBr(string $data): string
    {
        $date = DateTime::createFromFormat('Y-m-d', $data);
        return $date ? $date->format('d/m/Y') : $data;
    }
}
