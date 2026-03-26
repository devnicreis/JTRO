<?php

require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/AvisoRepository.php';

class ChamadoRepository
{
    private PDO $connection;
    private AvisoRepository $avisoRepository;

    public function __construct()
    {
        $this->connection = Database::getConnection();
        $this->avisoRepository = new AvisoRepository();
    }

    public function listarChamadosDoSolicitante(int $solicitanteId): array
    {
        $stmt = $this->connection->prepare("
            SELECT
                c.*,
                p.nome AS solicitante_nome,
                p.telefone_movel AS solicitante_telefone,
                p.telefone_fixo AS solicitante_telefone_fixo,
                pe.nome AS pessoa_nome,
                gf.nome AS grupo_nome,
                a.nome AS admin_nome
            FROM chamados c
            INNER JOIN pessoas p ON p.id = c.solicitante_id
            LEFT JOIN pessoas pe ON pe.id = c.pessoa_id
            LEFT JOIN grupos_familiares gf ON gf.id = c.grupo_familiar_id
            LEFT JOIN pessoas a ON a.id = c.admin_responsavel_id
            WHERE c.solicitante_id = :solicitante_id
            ORDER BY datetime(c.solicitado_em) DESC, c.id DESC
        ");
        $stmt->execute([':solicitante_id' => $solicitanteId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarChamadosParaAdmin(): array
    {
        $stmt = $this->connection->query("
            SELECT
                c.*,
                p.nome AS solicitante_nome,
                p.telefone_movel AS solicitante_telefone,
                p.telefone_fixo AS solicitante_telefone_fixo,
                pe.nome AS pessoa_nome,
                gf.nome AS grupo_nome,
                a.nome AS admin_nome
            FROM chamados c
            INNER JOIN pessoas p ON p.id = c.solicitante_id
            LEFT JOIN pessoas pe ON pe.id = c.pessoa_id
            LEFT JOIN grupos_familiares gf ON gf.id = c.grupo_familiar_id
            LEFT JOIN pessoas a ON a.id = c.admin_responsavel_id
            ORDER BY
                CASE c.status
                    WHEN 'em_analise' THEN 0
                    WHEN 'concluido' THEN 1
                    WHEN 'cancelado' THEN 2
                    ELSE 3
                END,
                datetime(c.solicitado_em) DESC,
                c.id DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarPessoasDosGruposDoLider(int $liderId): array
    {
        $stmt = $this->connection->prepare("
            SELECT DISTINCT
                p.id,
                p.nome,
                gf.nome AS grupo_nome
            FROM grupo_lideres gl
            INNER JOIN grupo_membros gm ON gm.grupo_familiar_id = gl.grupo_familiar_id
            INNER JOIN pessoas p ON p.id = gm.pessoa_id
            INNER JOIN grupos_familiares gf ON gf.id = gm.grupo_familiar_id
            WHERE gl.pessoa_id = :lider_id
              AND p.ativo = 1
              AND gf.ativo = 1
            ORDER BY p.nome ASC
        ");
        $stmt->execute([':lider_id' => $liderId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarGruposDoLider(int $liderId): array
    {
        $stmt = $this->connection->prepare("
            SELECT DISTINCT gf.id, gf.nome
            FROM grupo_lideres gl
            INNER JOIN grupos_familiares gf ON gf.id = gl.grupo_familiar_id
            WHERE gl.pessoa_id = :lider_id
              AND gf.ativo = 1
            ORDER BY gf.nome ASC
        ");
        $stmt->execute([':lider_id' => $liderId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function criarChamado(array $dados): string
    {
        $this->connection->beginTransaction();

        try {
            $ano = (int) date('Y');
            $stmtSeq = $this->connection->prepare("
                SELECT COALESCE(MAX(numero_sequencial), 0) + 1
                FROM chamados
                WHERE numero_ano = :numero_ano
            ");
            $stmtSeq->execute([':numero_ano' => $ano]);
            $sequencial = (int) $stmtSeq->fetchColumn();
            $numeroFormatado = sprintf('#%04d/%d', $sequencial, $ano);

            $stmt = $this->connection->prepare("
                INSERT INTO chamados (
                    numero_sequencial, numero_ano, numero_formatado, solicitante_id, destino,
                    assunto_tipo, assunto_label, tela_problema, pessoa_id, grupo_familiar_id,
                    campo_alteracao, motivo_desativacao_tipo, motivo_desativacao_detalhe,
                    motivo_desativacao_texto, resumo_solicitacao, status, solicitado_em
                ) VALUES (
                    :numero_sequencial, :numero_ano, :numero_formatado, :solicitante_id, :destino,
                    :assunto_tipo, :assunto_label, :tela_problema, :pessoa_id, :grupo_familiar_id,
                    :campo_alteracao, :motivo_desativacao_tipo, :motivo_desativacao_detalhe,
                    :motivo_desativacao_texto, :resumo_solicitacao, 'em_analise', :solicitado_em
                )
            ");

            $stmt->execute([
                ':numero_sequencial' => $sequencial,
                ':numero_ano' => $ano,
                ':numero_formatado' => $numeroFormatado,
                ':solicitante_id' => (int) $dados['solicitante_id'],
                ':destino' => $dados['destino'],
                ':assunto_tipo' => $dados['assunto_tipo'],
                ':assunto_label' => $dados['assunto_label'],
                ':tela_problema' => $this->textoOuNulo($dados['tela_problema'] ?? null),
                ':pessoa_id' => $this->inteiroOuNulo($dados['pessoa_id'] ?? null),
                ':grupo_familiar_id' => $this->inteiroOuNulo($dados['grupo_familiar_id'] ?? null),
                ':campo_alteracao' => $this->textoOuNulo($dados['campo_alteracao'] ?? null),
                ':motivo_desativacao_tipo' => $this->textoOuNulo($dados['motivo_desativacao_tipo'] ?? null),
                ':motivo_desativacao_detalhe' => $this->textoOuNulo($dados['motivo_desativacao_detalhe'] ?? null),
                ':motivo_desativacao_texto' => $this->textoOuNulo($dados['motivo_desativacao_texto'] ?? null),
                ':resumo_solicitacao' => trim((string) $dados['resumo_solicitacao']),
                ':solicitado_em' => date('Y-m-d H:i:s'),
            ]);

            $solicitante = $this->buscarPessoaBasica((int) $dados['solicitante_id']);
            $stmtAdmins = $this->connection->query("
                SELECT id
                FROM pessoas
                WHERE ativo = 1
                  AND cargo = 'admin'
            ");
            foreach (array_map('intval', $stmtAdmins->fetchAll(PDO::FETCH_COLUMN)) as $adminId) {
                $this->avisoRepository->criarAvisoSistema(
                    $adminId,
                    'chamado_novo_' . $numeroFormatado . '_usuario_' . $adminId,
                    'chamado_novo',
                    'Novo chamado: ' . $numeroFormatado,
                    $numeroFormatado . ' aberto por ' . ($solicitante['nome'] ?? 'Líder') . '. Consulte a Tela de Chamados.',
                    '/chamados.php'
                );
            }

            $this->connection->commit();
            return $numeroFormatado;
        } catch (Exception $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }

    public function atualizarStatus(int $chamadoId, string $status, string $observacaoAdmin, int $adminId): void
    {
        if (!in_array($status, ['concluido', 'cancelado'], true)) {
            throw new InvalidArgumentException('Status do chamado inválido.');
        }
        if (mb_strlen($observacaoAdmin) > 500) {
            throw new InvalidArgumentException('A observação do admin deve ter no máximo 500 caracteres.');
        }

        $stmtAtual = $this->connection->prepare("
            SELECT id, numero_formatado, solicitante_id, solicitado_em
            FROM chamados
            WHERE id = :id
            LIMIT 1
        ");
        $stmtAtual->execute([':id' => $chamadoId]);
        $chamado = $stmtAtual->fetch(PDO::FETCH_ASSOC);

        if (!$chamado) {
            throw new InvalidArgumentException('Chamado não encontrado.');
        }

        $stmt = $this->connection->prepare("
            UPDATE chamados
            SET status = :status,
                observacao_admin = :observacao_admin,
                admin_responsavel_id = :admin_responsavel_id,
                resolvido_em = :resolvido_em
            WHERE id = :id
        ");
        $stmt->execute([
            ':status' => $status,
            ':observacao_admin' => $this->textoOuNulo($observacaoAdmin),
            ':admin_responsavel_id' => $adminId,
            ':resolvido_em' => date('Y-m-d H:i:s'),
            ':id' => $chamadoId,
        ]);

        $statusLabel = $status === 'concluido' ? 'concluído' : 'cancelado';
        $dataAbertura = !empty($chamado['solicitado_em']) ? date('d/m/Y', strtotime($chamado['solicitado_em'])) : '';
        $this->avisoRepository->criarAvisoSistema(
            (int) $chamado['solicitante_id'],
            'chamado_atualizado_' . $chamado['numero_formatado'],
            'chamado_atualizado',
            'Chamado atualizado: ' . $chamado['numero_formatado'],
            'Seu chamado ' . $chamado['numero_formatado'] . ', aberto em ' . $dataAbertura . ', foi ' . $statusLabel . '. Consulte a tela de Chamados.',
            '/chamados.php'
        );
    }

    private function buscarPessoaBasica(int $pessoaId): ?array
    {
        $stmt = $this->connection->prepare("
            SELECT id, nome
            FROM pessoas
            WHERE id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $pessoaId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function textoOuNulo(?string $valor): ?string
    {
        $valor = trim((string) $valor);
        return $valor === '' ? null : $valor;
    }

    private function inteiroOuNulo($valor): ?int
    {
        $numero = (int) $valor;
        return $numero > 0 ? $numero : null;
    }
}
