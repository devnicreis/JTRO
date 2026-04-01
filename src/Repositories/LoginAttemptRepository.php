<?php

require_once __DIR__ . '/../Core/Database.php';

class LoginAttemptRepository
{
    private PDO $connection;
    private static bool $cleanupDone = false;

    public function __construct()
    {
        $this->connection = Database::getConnection();
        $this->purgeOldRecords();
    }

    public function registrar(string $cpf, ?string $ipAddress, string $status): void
    {
        $stmt = $this->connection->prepare("
            INSERT INTO login_attempts (cpf, ip_address, status, attempted_at)
            VALUES (:cpf, :ip_address, :status, :attempted_at)
        ");

        $stmt->bindValue(':cpf', $cpf, PDO::PARAM_STR);
        $stmt->bindValue(':ip_address', $ipAddress, $ipAddress === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':status', $status, PDO::PARAM_STR);
        $stmt->bindValue(':attempted_at', date('Y-m-d H:i:s'), PDO::PARAM_STR);
        $stmt->execute();
    }

    public function contarFalhasPorCpfDesde(string $cpf, string $desde): int
    {
        $stmt = $this->connection->prepare("
            SELECT COUNT(1)
            FROM login_attempts
            WHERE cpf = :cpf
              AND status = 'failed'
              AND attempted_at >= :desde
        ");

        $stmt->execute([
            ':cpf' => $cpf,
            ':desde' => $desde,
        ]);

        return (int) $stmt->fetchColumn();
    }

    public function contarFalhasPorIpDesde(string $ipAddress, string $desde): int
    {
        $stmt = $this->connection->prepare("
            SELECT COUNT(1)
            FROM login_attempts
            WHERE ip_address = :ip_address
              AND status = 'failed'
              AND attempted_at >= :desde
        ");

        $stmt->execute([
            ':ip_address' => $ipAddress,
            ':desde' => $desde,
        ]);

        return (int) $stmt->fetchColumn();
    }

    public function limparFalhasPorCpf(string $cpf): void
    {
        $stmt = $this->connection->prepare("
            DELETE FROM login_attempts
            WHERE cpf = :cpf
              AND status = 'failed'
        ");

        $stmt->execute([
            ':cpf' => $cpf,
        ]);
    }

    public function houveAlertaPorCpfDesde(string $cpf, string $desde): bool
    {
        $stmt = $this->connection->prepare("
            SELECT COUNT(1)
            FROM login_attempts
            WHERE cpf = :cpf
              AND status = 'alert_sent'
              AND attempted_at >= :desde
        ");

        $stmt->execute([
            ':cpf' => $cpf,
            ':desde' => $desde,
        ]);

        return (int) $stmt->fetchColumn() > 0;
    }

    private function purgeOldRecords(int $days = 30): void
    {
        if (self::$cleanupDone) {
            return;
        }

        self::$cleanupDone = true;

        $limite = date('Y-m-d H:i:s', time() - ($days * 86400));
        $stmt = $this->connection->prepare("
            DELETE FROM login_attempts
            WHERE attempted_at < :limite
        ");

        $stmt->execute([':limite' => $limite]);
    }
}
