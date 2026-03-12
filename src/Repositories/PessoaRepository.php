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

    public function salvar(Pessoa $pessoa): void
    {
        $sql = "INSERT INTO pessoas (nome, cpf, cargo, ativo)
                VALUES (:nome, :cpf, :cargo, :ativo)";

        $stmt = $this->connection->prepare($sql);

        $stmt->execute([
            ':nome' => $pessoa->nome,
            ':cpf' => $pessoa->getCpf(),
            ':cargo' => $pessoa->getCargo(),
            ':ativo' => $pessoa->ativo ? 1 : 0
        ]);
    }

    public function listarTodas(): array
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

    public function atualizar(int $id, string $nome, string $cpf, string $cargo): void
    {
        $sql = "
            UPDATE pessoas
            SET nome = :nome,
                cpf = :cpf,
                cargo = :cargo
            WHERE id = :id
        ";

        $stmt = $this->connection->prepare($sql);

        $stmt->execute([
            ':nome' => $nome,
            ':cpf' => $cpf,
            ':cargo' => $cargo,
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
}