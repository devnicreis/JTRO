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

    public function buscarPorCpf(string $cpf): ?array
    {
        $sql = "SELECT * FROM pessoas WHERE cpf = :cpf LIMIT 1";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute([':cpf' => $cpf]);

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return $resultado ?: null;
    }
}