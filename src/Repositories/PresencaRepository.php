<?php

require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Models/Presenca.php';

class PresencaRepository
{
    private PDO $connection;

    public function __construct()
    {
        $this->connection = Database::getConnection();
    }

    public function listarReunioes(): array
    {
        $sql = "
            SELECT
                r.id,
                gf.nome AS grupo_nome,
                r.data,
                r.horario,
                r.local
            FROM reunioes r
            INNER JOIN grupos_familiares gf ON gf.id = r.grupo_familiar_id
            ORDER BY r.data DESC, r.id DESC
        ";

        $stmt = $this->connection->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarPresencasPorReuniao(int $reuniaoId): array
    {
        $sql = "
            SELECT
                p.id,
                p.reuniao_id,
                p.pessoa_id,
                p.status,
                pe.nome,
                pe.cpf,
                pe.cargo
            FROM presencas p
            INNER JOIN pessoas pe ON pe.id = p.pessoa_id
            WHERE p.reuniao_id = :reuniao_id
            ORDER BY pe.nome ASC
        ";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            ':reuniao_id' => $reuniaoId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function atualizarStatus(int $presencaId, string $status): void
    {
        $statusValidos = [
            Presenca::STATUS_PRESENTE,
            Presenca::STATUS_AUSENTE
        ];

        if (!in_array($status, $statusValidos, true)) {
            throw new InvalidArgumentException('Status de presença inválido.');
        }

        $sql = "
            UPDATE presencas
            SET status = :status
            WHERE id = :id
        ";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            ':status' => $status,
            ':id' => $presencaId
        ]);
    }

    public function atualizarEmLote(array $presencas): void
    {
        $this->connection->beginTransaction();

        try {
            foreach ($presencas as $presencaId => $status) {
                $this->atualizarStatus((int) $presencaId, $status);
            }

            $this->connection->commit();
        } catch (Exception $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }
}