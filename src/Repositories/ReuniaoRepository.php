<?php

require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Models/Presenca.php';

class ReuniaoRepository
{
    private PDO $connection;

    public function __construct()
    {
        $this->connection = Database::getConnection();
    }

    public function listarGruposFamiliares(): array
    {
        $sql = "
            SELECT id, nome, dia_semana, horario
            FROM grupos_familiares
            ORDER BY nome ASC
        ";

        $stmt = $this->connection->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function salvar(
        int $grupoFamiliarId,
        string $data,
        string $horario,
        string $local,
        ?string $motivoAlteracao,
        ?string $observacoes
    ): void {
        if ($grupoFamiliarId <= 0) {
            throw new InvalidArgumentException('Selecione um Grupo Familiar.');
        }

        if ($data === '' || $horario === '' || $local === '') {
            throw new InvalidArgumentException('Preencha grupo, data, horário e local.');
        }

        $this->connection->beginTransaction();

        try {
            $stmt = $this->connection->prepare("
                INSERT INTO reunioes (
                    grupo_familiar_id,
                    data,
                    horario,
                    local,
                    motivo_alteracao,
                    observacoes
                ) VALUES (
                    :grupo_familiar_id,
                    :data,
                    :horario,
                    :local,
                    :motivo_alteracao,
                    :observacoes
                )
            ");

            $stmt->execute([
                ':grupo_familiar_id' => $grupoFamiliarId,
                ':data' => $data,
                ':horario' => $horario,
                ':local' => $local,
                ':motivo_alteracao' => $motivoAlteracao !== '' ? $motivoAlteracao : null,
                ':observacoes' => $observacoes !== '' ? $observacoes : null
            ]);

            $reuniaoId = (int) $this->connection->lastInsertId();

            $stmtMembros = $this->connection->prepare("
                SELECT gm.pessoa_id
                FROM grupo_membros gm
                INNER JOIN pessoas p ON p.id = gm.pessoa_id
                WHERE gm.grupo_familiar_id = :grupo_familiar_id
                AND p.ativo = 1
            ");

            $stmtMembros->execute([
                ':grupo_familiar_id' => $grupoFamiliarId
            ]);

            $membros = $stmtMembros->fetchAll(PDO::FETCH_COLUMN);

            $stmtPresenca = $this->connection->prepare("
                INSERT INTO presencas (reuniao_id, pessoa_id, status)
                VALUES (:reuniao_id, :pessoa_id, :status)
            ");

            foreach ($membros as $pessoaId) {
                $stmtPresenca->execute([
                    ':reuniao_id' => $reuniaoId,
                    ':pessoa_id' => (int) $pessoaId,
                    ':status' => Presenca::STATUS_PRESENTE
                ]);
            }

            $this->connection->commit();
        } catch (Exception $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }

    public function listarTodas(): array
    {
        $sql = "
            SELECT
                r.id,
                gf.nome AS grupo_nome,
                r.data,
                r.horario,
                r.local,
                r.motivo_alteracao,
                r.observacoes,
                (
                    SELECT COUNT(*)
                    FROM presencas p
                    WHERE p.reuniao_id = r.id
                ) AS total_presencas,
                (
                    SELECT COUNT(*)
                    FROM presencas p
                    WHERE p.reuniao_id = r.id
                    AND p.status = 'presente'
                ) AS total_presentes,
                (
                    SELECT COUNT(*)
                    FROM presencas p
                    WHERE p.reuniao_id = r.id
                    AND p.status = 'ausente'
                ) AS total_ausentes
            FROM reunioes r
            INNER JOIN grupos_familiares gf ON gf.id = r.grupo_familiar_id
            ORDER BY r.id DESC
        ";

        $stmt = $this->connection->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}