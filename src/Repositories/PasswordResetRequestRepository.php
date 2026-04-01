<?php

require_once __DIR__ . '/../Core/Database.php';

class PasswordResetRequestRepository
{
    private PDO $connection;
    private static bool $cleanupDone = false;

    public function __construct()
    {
        $this->connection = Database::getConnection();
        $this->purgeOldRecords();
    }

    public function registrar(?int $pessoaId, string $email, ?string $ipAddress, string $status): void
    {
        $stmt = $this->connection->prepare("
            INSERT INTO password_reset_requests (pessoa_id, email, ip_address, status, requested_at)
            VALUES (:pessoa_id, :email, :ip_address, :status, :requested_at)
        ");

        $stmt->bindValue(':pessoa_id', $pessoaId, $pessoaId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->bindValue(':ip_address', $ipAddress, $ipAddress === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':status', $status, PDO::PARAM_STR);
        $stmt->bindValue(':requested_at', date('Y-m-d H:i:s'), PDO::PARAM_STR);
        $stmt->execute();
    }

    public function houveEnvioPorPessoaDesde(int $pessoaId, string $desde): bool
    {
        $stmt = $this->connection->prepare("
            SELECT 1
            FROM password_reset_requests
            WHERE pessoa_id = :pessoa_id
              AND status = 'sent'
              AND requested_at >= :desde
            LIMIT 1
        ");

        $stmt->execute([
            ':pessoa_id' => $pessoaId,
            ':desde' => $desde,
        ]);

        return $stmt->fetchColumn() !== false;
    }

    public function contarEnviosPorPessoaDesde(int $pessoaId, string $desde): int
    {
        $stmt = $this->connection->prepare("
            SELECT COUNT(*)
            FROM password_reset_requests
            WHERE pessoa_id = :pessoa_id
              AND status = 'sent'
              AND requested_at >= :desde
        ");

        $stmt->execute([
            ':pessoa_id' => $pessoaId,
            ':desde' => $desde,
        ]);

        return (int) $stmt->fetchColumn();
    }

    public function contarSolicitacoesPorIpDesde(string $ipAddress, string $desde): int
    {
        $stmt = $this->connection->prepare("
            SELECT COUNT(*)
            FROM password_reset_requests
            WHERE ip_address = :ip_address
              AND requested_at >= :desde
        ");

        $stmt->execute([
            ':ip_address' => $ipAddress,
            ':desde' => $desde,
        ]);

        return (int) $stmt->fetchColumn();
    }

    private function purgeOldRecords(int $days = 90): void
    {
        if (self::$cleanupDone) {
            return;
        }

        self::$cleanupDone = true;

        $limite = date('Y-m-d H:i:s', time() - ($days * 86400));
        $stmt = $this->connection->prepare("
            DELETE FROM password_reset_requests
            WHERE requested_at < :limite
        ");

        $stmt->execute([':limite' => $limite]);
    }
}
