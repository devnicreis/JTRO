<?php

require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Models/Pessoa.php';

class PessoaRepository
{
    private PDO $connection;

    public function __construct()
    {
        $this->connection = Database::getConnection();
    }

    public function salvar(Pessoa $pessoa, ?string $email = null): void
    {
        $sql = "INSERT INTO pessoas (nome, cpf, cargo, ativo, email)
                VALUES (:nome, :cpf, :cargo, :ativo, :email)";

        $stmt = $this->connection->prepare($sql);

        $stmt->execute([
            ':nome' => $pessoa->nome,
            ':cpf' => $pessoa->getCpf(),
            ':cargo' => $pessoa->getCargo(),
            ':ativo' => $pessoa->ativo ? 1 : 0,
            ':email' => $email !== '' ? $email : null
        ]);
    }

    public function listarTodos(): array
    {
        $sql = "SELECT * FROM pessoas ORDER BY id DESC";

        $stmt = $this->connection->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarAtivas(): array
    {
        $sql = "SELECT * FROM pessoas WHERE ativo = 1 ORDER BY id DESC";

        $stmt = $this->connection->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarPorCpf(string $cpf): ?array
    {
        $sql = "SELECT * FROM pessoas WHERE cpf = :cpf LIMIT 1";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute([':cpf' => $cpf]);

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return $resultado ?: null;
    }

    public function buscarPorId(int $id): ?array
    {
        $sql = "SELECT * FROM pessoas WHERE id = :id LIMIT 1";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute([':id' => $id]);

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return $resultado ?: null;
    }

    public function buscarPorCpfExcetoId(string $cpf, int $id): ?array
    {
        $sql = "SELECT * FROM pessoas WHERE cpf = :cpf AND id != :id LIMIT 1";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            ':cpf' => $cpf,
            ':id' => $id
        ]);

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return $resultado ?: null;
    }

    public function atualizar(int $id, string $nome, string $cpf, ?string $email, string $cargo): void
    {
        $sql = "
            UPDATE pessoas
            SET nome = :nome,
                cpf = :cpf,
                cargo = :cargo,
                email = :email
            WHERE id = :id
        ";

        $stmt = $this->connection->prepare($sql);

        $stmt->execute([
            ':nome' => $nome,
            ':cpf' => $cpf,
            ':cargo' => $cargo,
            ':email' => $email !== '' ? $email : null,
            ':id' => $id
        ]);
    }

    public function desativar(int $id): void
    {
        $sql = "UPDATE pessoas SET ativo = 0 WHERE id = :id";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            ':id' => $id
        ]);
    }

    public function reativar(int $id): void
    {
        $this->connection->beginTransaction();

        try {
            $stmt = $this->connection->prepare("
                UPDATE pessoas
                SET ativo = 1
                WHERE id = :id
            ");

            $stmt->execute([
                ':id' => $id
            ]);

            $stmt = $this->connection->prepare("
                DELETE FROM grupo_membros
                WHERE pessoa_id = :id
            ");

            $stmt->execute([
                ':id' => $id
            ]);

            $stmt = $this->connection->prepare("
                DELETE FROM grupo_lideres
                WHERE pessoa_id = :id
            ");

            $stmt->execute([
                ':id' => $id
            ]);

            $this->connection->commit();
        } catch (Exception $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }

    public function buscarPorCpfAtivo(string $cpf): ?array
    {
        $sql = "SELECT * FROM pessoas WHERE cpf = :cpf AND ativo = 1 LIMIT 1";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute([':cpf' => $cpf]);

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return $resultado ?: null;
    }

    public function atualizarSenha(int $id, string $senha): void
    {
        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

        $sql = "UPDATE pessoas SET senha_hash = :senha_hash WHERE id = :id";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            ':senha_hash' => $senhaHash,
            ':id' => $id
        ]);
    }

    public function definirSenhaNoCadastro(Pessoa $pessoa, string $senha): void
    {
        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

        $sql = "INSERT INTO pessoas (nome, cpf, cargo, ativo, senha_hash)
            VALUES (:nome, :cpf, :cargo, :ativo, :senha_hash)";

        $stmt = $this->connection->prepare($sql);

        $stmt->execute([
            ':nome' => $pessoa->nome,
            ':cpf' => $pessoa->getCpf(),
            ':cargo' => $pessoa->getCargo(),
            ':ativo' => $pessoa->ativo ? 1 : 0,
            ':senha_hash' => $senhaHash
        ]);
    }

    public function marcarPrecisaTrocarSenha(int $id, bool $valor): void
    {
        $sql = "UPDATE pessoas SET precisa_trocar_senha = :valor WHERE id = :id";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            ':valor' => $valor ? 1 : 0,
            ':id' => $id
        ]);
    }

    public function atualizarSenhaEObrigacao(int $id, string $senha, bool $precisaTrocarSenha): void
    {
        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

        $sql = "
        UPDATE pessoas
        SET senha_hash = :senha_hash,
            precisa_trocar_senha = :precisa_trocar_senha
        WHERE id = :id
    ";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            ':senha_hash' => $senhaHash,
            ':precisa_trocar_senha' => $precisaTrocarSenha ? 1 : 0,
            ':id' => $id
        ]);
    }

    public function buscarPorEmail(string $email): ?array
    {
        $sql = "SELECT * FROM pessoas WHERE email = :email LIMIT 1";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute([':email' => $email]);

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return $resultado ?: null;
    }

    public function buscarPorEmailAtivo(string $email): ?array
    {
        $sql = "SELECT * FROM pessoas WHERE email = :email AND ativo = 1 LIMIT 1";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute([':email' => $email]);

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return $resultado ?: null;
    }

    public function buscarPorEmailExcetoId(string $email, int $id): ?array
    {
        $sql = "SELECT * FROM pessoas WHERE email = :email AND id != :id LIMIT 1";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            ':email' => $email,
            ':id' => $id
        ]);

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return $resultado ?: null;
    }

    public function contarPessoasAtivas(): int
    {
        $stmt = $this->connection->query("
        SELECT COUNT(*)
        FROM pessoas
        WHERE ativo = 1
    ");

        return (int) $stmt->fetchColumn();
    }

    public function contarLideresAtivos(): int
    {
        $stmt = $this->connection->query("
        SELECT COUNT(DISTINCT gl.pessoa_id)
        FROM grupo_lideres gl
        INNER JOIN pessoas p ON p.id = gl.pessoa_id
        WHERE p.ativo = 1
    ");

        return (int) $stmt->fetchColumn();
    }

    public function buscarResumoPresencaPorGrupo(int $grupoId): array
    {
        $stmt = $this->connection->prepare("
        SELECT
            SUM(CASE WHEN p.status = 'presente' THEN 1 ELSE 0 END) AS total_presencas,
            SUM(CASE WHEN p.status = 'ausente' THEN 1 ELSE 0 END) AS total_ausencias,
            COUNT(*) AS total_registros
        FROM presencas p
        INNER JOIN reunioes r ON r.id = p.reuniao_id
        WHERE r.grupo_familiar_id = :grupo_id
    ");

        $stmt->execute([':grupo_id' => $grupoId]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        $presencas = (int) ($resultado['total_presencas'] ?? 0);
        $ausencias = (int) ($resultado['total_ausencias'] ?? 0);
        $total = (int) ($resultado['total_registros'] ?? 0);

        return [
            'total_presencas' => $presencas,
            'total_ausencias' => $ausencias,
            'percentual_presencas' => $total > 0 ? round(($presencas / $total) * 100, 1) : 0,
            'percentual_ausencias' => $total > 0 ? round(($ausencias / $total) * 100, 1) : 0,
        ];
    }

    public function buscarResumoPorMembroDoGrupo(int $grupoId): array
    {
        $stmt = $this->connection->prepare("
        SELECT
            pe.id AS pessoa_id,
            pe.nome,
            SUM(CASE WHEN p.status = 'presente' THEN 1 ELSE 0 END) AS total_presencas,
            SUM(CASE WHEN p.status = 'ausente' THEN 1 ELSE 0 END) AS total_ausencias,
            MAX(CASE WHEN p.status = 'presente' THEN r.data ELSE NULL END) AS ultima_presenca
        FROM grupo_membros gm
        INNER JOIN pessoas pe ON pe.id = gm.pessoa_id
        LEFT JOIN reunioes r ON r.grupo_familiar_id = gm.grupo_familiar_id
        LEFT JOIN presencas p ON p.reuniao_id = r.id AND p.pessoa_id = pe.id
        WHERE gm.grupo_familiar_id = :grupo_id
        AND pe.ativo = 1
        GROUP BY pe.id, pe.nome
        ORDER BY pe.nome ASC
    ");

        $stmt->execute([':grupo_id' => $grupoId]);
        $linhas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($linhas as &$linha) {
            $presencas = (int) ($linha['total_presencas'] ?? 0);
            $ausencias = (int) ($linha['total_ausencias'] ?? 0);
            $total = $presencas + $ausencias;

            $linha['percentual_presenca'] = $total > 0 ? round(($presencas / $total) * 100, 1) : 0;
        }

        return $linhas;
    }

    public function buscarMembrosComFaltasConsecutivas(int $grupoId, int $minimo = 2): array
    {
        $stmt = $this->connection->prepare("
        SELECT
            pe.id AS pessoa_id,
            pe.nome,
            r.data,
            p.status
        FROM grupo_membros gm
        INNER JOIN pessoas pe ON pe.id = gm.pessoa_id
        INNER JOIN reunioes r ON r.grupo_familiar_id = gm.grupo_familiar_id
        INNER JOIN presencas p ON p.reuniao_id = r.id AND p.pessoa_id = pe.id
        WHERE gm.grupo_familiar_id = :grupo_id
        AND pe.ativo = 1
        ORDER BY pe.id ASC, r.data DESC, r.id DESC
    ");

        $stmt->execute([':grupo_id' => $grupoId]);
        $linhas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $resultado = [];
        $controle = [];

        foreach ($linhas as $linha) {
            $pessoaId = (int) $linha['pessoa_id'];

            if (!isset($controle[$pessoaId])) {
                $controle[$pessoaId] = [
                    'nome' => $linha['nome'],
                    'faltas' => 0,
                    'encerrado' => false
                ];
            }

            if ($controle[$pessoaId]['encerrado']) {
                continue;
            }

            if ($linha['status'] === 'ausente') {
                $controle[$pessoaId]['faltas']++;
            } else {
                $controle[$pessoaId]['encerrado'] = true;
            }
        }

        foreach ($controle as $pessoaId => $info) {
            if ($info['faltas'] >= $minimo) {
                $resultado[] = [
                    'pessoa_id' => $pessoaId,
                    'nome' => $info['nome'],
                    'faltas_consecutivas' => $info['faltas']
                ];
            }
        }

        usort($resultado, function ($a, $b) {
            return $b['faltas_consecutivas'] <=> $a['faltas_consecutivas'];
        });

        return $resultado;
    }
}
