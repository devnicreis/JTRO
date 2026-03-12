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

    public function listarGruposFamiliares(): array
    {
        $sql = "
            SELECT id, nome, dia_semana, horario, local_padrao, local_fixo
            FROM grupos_familiares
            WHERE ativo = 1
            ORDER BY nome ASC
        ";

        $stmt = $this->connection->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarOuCriarReuniaoPorGrupoEData(int $grupoId, string $data): int
    {
        $stmt = $this->connection->prepare("
            SELECT id
            FROM reunioes
            WHERE grupo_familiar_id = :grupo_id
            AND data = :data
            LIMIT 1
        ");

        $stmt->execute([
            ':grupo_id' => $grupoId,
            ':data' => $data
        ]);

        $reuniaoId = $stmt->fetchColumn();

        if ($reuniaoId) {
            return (int) $reuniaoId;
        }

        $stmt = $this->connection->prepare("
            SELECT horario, local_padrao, local_fixo
            FROM grupos_familiares
            WHERE id = :id
            LIMIT 1
        ");

        $stmt->execute([':id' => $grupoId]);
        $grupo = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$grupo) {
            throw new InvalidArgumentException('Grupo Familiar não encontrado.');
        }

        $this->connection->beginTransaction();

        try {
            $local = ((int)$grupo['local_fixo'] === 1 && !empty($grupo['local_padrao']))
                ? $grupo['local_padrao']
                : '';

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
                    NULL,
                    NULL
                )
            ");

            $stmt->execute([
                ':grupo_familiar_id' => $grupoId,
                ':data' => $data,
                ':horario' => $grupo['horario'],
                ':local' => $local
            ]);

            $novoReuniaoId = (int) $this->connection->lastInsertId();

            $stmtMembros = $this->connection->prepare("
                SELECT gm.pessoa_id
                FROM grupo_membros gm
                INNER JOIN pessoas p ON p.id = gm.pessoa_id
                WHERE gm.grupo_familiar_id = :grupo_id
                AND p.ativo = 1
            ");

            $stmtMembros->execute([':grupo_id' => $grupoId]);
            $membros = $stmtMembros->fetchAll(PDO::FETCH_COLUMN);

            $stmtPresenca = $this->connection->prepare("
                INSERT INTO presencas (reuniao_id, pessoa_id, status)
                VALUES (:reuniao_id, :pessoa_id, :status)
            ");

            foreach ($membros as $pessoaId) {
                $stmtPresenca->execute([
                    ':reuniao_id' => $novoReuniaoId,
                    ':pessoa_id' => (int) $pessoaId,
                    ':status' => Presenca::STATUS_PRESENTE
                ]);
            }

            $this->connection->commit();

            return $novoReuniaoId;
        } catch (Exception $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }

    public function buscarReuniao(int $reuniaoId): ?array
    {
        $stmt = $this->connection->prepare("
            SELECT
                r.id,
                r.grupo_familiar_id,
                r.data,
                r.horario,
                r.local,
                r.motivo_alteracao,
                r.observacoes,
                gf.nome AS grupo_nome,
                gf.local_fixo,
                gf.local_padrao
            FROM reunioes r
            INNER JOIN grupos_familiares gf ON gf.id = r.grupo_familiar_id
            WHERE r.id = :id
            LIMIT 1
        ");

        $stmt->execute([':id' => $reuniaoId]);

        $reuniao = $stmt->fetch(PDO::FETCH_ASSOC);

        return $reuniao ?: null;
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

    public function atualizarReuniao(int $reuniaoId, string $local, string $observacoes): void
    {
        $stmtReuniao = $this->connection->prepare("
            SELECT r.id, r.local, gf.local_fixo
            FROM reunioes r
            INNER JOIN grupos_familiares gf ON gf.id = r.grupo_familiar_id
            WHERE r.id = :id
            LIMIT 1
        ");

        $stmtReuniao->execute([':id' => $reuniaoId]);
        $reuniao = $stmtReuniao->fetch(PDO::FETCH_ASSOC);

        if (!$reuniao) {
            throw new InvalidArgumentException('Reunião não encontrada.');
        }

        if ((int)$reuniao['local_fixo'] === 0 && trim($local) === '') {
            throw new InvalidArgumentException('Para GF sem local fixo, o local da reunião é obrigatório.');
        }

        $stmt = $this->connection->prepare("
            UPDATE reunioes
            SET local = :local,
                observacoes = :observacoes
            WHERE id = :id
        ");

        $stmt->execute([
            ':local' => $local,
            ':observacoes' => $observacoes !== '' ? $observacoes : null,
            ':id' => $reuniaoId
        ]);
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

    public function atualizarPresencasEReuniao(int $reuniaoId, string $local, string $observacoes, array $presencas): void
    {
        $this->connection->beginTransaction();

        try {
            $this->atualizarReuniao($reuniaoId, $local, $observacoes);

            foreach ($presencas as $presencaId => $status) {
                $this->atualizarStatus((int) $presencaId, $status);
            }

            $this->connection->commit();
        } catch (Exception $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }
    public function listarGruposFamiliaresPorLider(int $pessoaId): array
    {
        $sql = "
        SELECT gf.id, gf.nome, gf.dia_semana, gf.horario, gf.local_padrao, gf.local_fixo
        FROM grupos_familiares gf
        INNER JOIN grupo_lideres gl ON gl.grupo_familiar_id = gf.id
        WHERE gf.ativo = 1
        AND gl.pessoa_id = :pessoa_id
        ORDER BY gf.nome ASC
    ";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute([':pessoa_id' => $pessoaId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function liderPodeAcessarGrupo(int $pessoaId, int $grupoId): bool
    {
        $stmt = $this->connection->prepare("
        SELECT COUNT(*)
        FROM grupo_lideres gl
        INNER JOIN grupos_familiares gf ON gf.id = gl.grupo_familiar_id
        WHERE gl.pessoa_id = :pessoa_id
        AND gl.grupo_familiar_id = :grupo_id
        AND gf.ativo = 1
    ");

        $stmt->execute([
            ':pessoa_id' => $pessoaId,
            ':grupo_id' => $grupoId
        ]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function buscarResumoGrupo(int $grupoId): array
    {
        $stmt = $this->connection->prepare("
        SELECT
            gf.id,
            gf.nome,
            gf.dia_semana,
            gf.horario,
            gf.local_padrao,
            gf.local_fixo,
            (
                SELECT COUNT(*)
                FROM grupo_membros gm
                INNER JOIN pessoas p ON p.id = gm.pessoa_id
                WHERE gm.grupo_familiar_id = gf.id
                AND p.ativo = 1
            ) AS total_membros_ativos,
            (
                SELECT COUNT(*)
                FROM reunioes r
                WHERE r.grupo_familiar_id = gf.id
            ) AS total_reunioes,
            (
                SELECT MAX(r.data)
                FROM reunioes r
                WHERE r.grupo_familiar_id = gf.id
            ) AS ultima_data_reuniao
        FROM grupos_familiares gf
        WHERE gf.id = :grupo_id
        LIMIT 1
    ");

        $stmt->execute([':grupo_id' => $grupoId]);

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return $resultado ?: [];
    }

    public function listarUltimasReunioesDoGrupo(int $grupoId, int $limite = 5): array
    {
        $limite = max(1, $limite);

        $sql = "
        SELECT
            r.id,
            r.data,
            r.horario,
            r.local,
            r.observacoes,
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
        WHERE r.grupo_familiar_id = :grupo_id
        ORDER BY r.data DESC, r.id DESC
        LIMIT {$limite}
    ";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute([':grupo_id' => $grupoId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}