<?php

require_once __DIR__ . '/../Core/Database.php';

class PasswordResetTokenRepository
{
    private PDO $connection;

    public function __construct()
    {
        $this->connection = Database::getConnection();
    }

    public function invalidarTokensAtivosDaPessoa(int $pessoaId): void
    {
        $stmt = $this->connection->prepare("
            UPDATE password_reset_tokens
            SET usado_em = :usado_em
            WHERE pessoa_id = :pessoa_id
            AND usado_em IS NULL
        ");

        $stmt->execute([
            ':usado_em' => date('Y-m-d H:i:s'),
            ':pessoa_id' => $pessoaId
        ]);
    }

    public function criarToken(int $pessoaId, string $token, string $expiraEm): void
    {
        $stmt = $this->connection->prepare("
            INSERT INTO password_reset_tokens (pessoa_id, token, expira_em, usado_em, created_at)
            VALUES (:pessoa_id, :token, :expira_em, NULL, :created_at)
        ");

        $stmt->execute([
            ':pessoa_id' => $pessoaId,
            ':token' => $token,
            ':expira_em' => $expiraEm,
            ':created_at' => date('Y-m-d H:i:s')
        ]);
    }

    public function buscarTokenValido(string $token): ?array
    {
        $stmt = $this->connection->prepare("
            SELECT *
            FROM password_reset_tokens
            WHERE token = :token
            AND usado_em IS NULL
            AND expira_em >= :agora
            LIMIT 1
        ");

        $stmt->execute([
            ':token' => $token,
            ':agora' => date('Y-m-d H:i:s')
        ]);

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return $resultado ?: null;
    }

    public function buscarTokenValidoDaPessoa(int $pessoaId): ?array
    {
        $stmt = $this->connection->prepare("
            SELECT *
            FROM password_reset_tokens
            WHERE pessoa_id = :pessoa_id
              AND usado_em IS NULL
              AND expira_em >= :agora
            ORDER BY created_at DESC, id DESC
            LIMIT 1
        ");

        $stmt->execute([
            ':pessoa_id' => $pessoaId,
            ':agora' => date('Y-m-d H:i:s')
        ]);

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return $resultado ?: null;
    }

    public function marcarComoUsado(int $id): void
    {
        $stmt = $this->connection->prepare("
            UPDATE password_reset_tokens
            SET usado_em = :usado_em
            WHERE id = :id
        ");

        $stmt->execute([
            ':usado_em' => date('Y-m-d H:i:s'),
            ':id' => $id
        ]);
    }
}
