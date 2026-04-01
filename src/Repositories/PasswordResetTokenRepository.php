<?php

require_once __DIR__ . '/../Core/Database.php';

class PasswordResetTokenRepository
{
    private PDO $connection;
    private static bool $cleanupDone = false;

    public function __construct()
    {
        $this->connection = Database::getConnection();
        $this->purgeOldTokens();
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
            ':token' => $this->hashToken($token),
            ':expira_em' => $expiraEm,
            ':created_at' => date('Y-m-d H:i:s')
        ]);
    }

    public function buscarTokenValido(string $token): ?array
    {
        $tokenHash = $this->hashToken($token);

        $stmt = $this->connection->prepare("
            SELECT *
            FROM password_reset_tokens
            WHERE (token = :token_plain OR token = :token_hash)
              AND usado_em IS NULL
              AND expira_em >= :agora
            LIMIT 1
        ");

        $stmt->execute([
            ':token_plain' => $token,
            ':token_hash' => $tokenHash,
            ':agora' => date('Y-m-d H:i:s')
        ]);

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($resultado && ($resultado['token'] ?? '') !== $tokenHash) {
            $this->atualizarHashDoToken((int) $resultado['id'], $tokenHash);
            $resultado['token'] = $tokenHash;
        }

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

    private function hashToken(string $token): string
    {
        return 'sha256:' . hash('sha256', $token);
    }

    private function atualizarHashDoToken(int $id, string $tokenHash): void
    {
        $stmt = $this->connection->prepare("
            UPDATE password_reset_tokens
            SET token = :token_hash
            WHERE id = :id
        ");

        $stmt->execute([
            ':token_hash' => $tokenHash,
            ':id' => $id
        ]);
    }

    private function purgeOldTokens(int $retentionDays = 30): void
    {
        if (self::$cleanupDone) {
            return;
        }

        self::$cleanupDone = true;

        $limite = date('Y-m-d H:i:s', time() - ($retentionDays * 86400));
        $stmt = $this->connection->prepare("
            DELETE FROM password_reset_tokens
            WHERE (usado_em IS NOT NULL AND usado_em < :limite)
               OR (expira_em < :agora AND created_at < :limite)
        ");

        $stmt->execute([
            ':agora' => date('Y-m-d H:i:s'),
            ':limite' => $limite,
        ]);
    }
}
