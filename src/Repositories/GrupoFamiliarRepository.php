<?php

require_once __DIR__ . '/../Core/Database.php';

class GrupoFamiliarRepository
{
    private PDO $connection;

    public function __construct()
    {
        $this->connection = Database::getConnection();
    }

    public function salvar(
        string $nome,
        string $diaSemana,
        string $horario,
        string $localPadrao,
        int $localFixo,
        array $lideresIds,
        array $membrosIds
    ): void {
        if ($nome === '' || $diaSemana === '' || $horario === '') {
            throw new InvalidArgumentException('Preencha nome, dia da semana e horário.');
        }

        if ($localFixo === 1 && trim($localPadrao) === '') {
            throw new InvalidArgumentException('Se o GF possui local fixo, o local padrão é obrigatório.');
        }

        if (count($lideresIds) === 0) {
            throw new InvalidArgumentException('Selecione pelo menos um líder.');
        }

        $this->connection->beginTransaction();

        try {
            $stmt = $this->connection->prepare("
                INSERT INTO grupos_familiares (nome, dia_semana, horario, local_padrao, local_fixo, ativo)
                VALUES (:nome, :dia_semana, :horario, :local_padrao, :local_fixo, 1)
            ");

            $stmt->execute([
                ':nome' => $nome,
                ':dia_semana' => $diaSemana,
                ':horario' => $horario,
                ':local_padrao' => $localPadrao !== '' ? $localPadrao : null,
                ':local_fixo' => $localFixo
            ]);

            $grupoId = (int) $this->connection->lastInsertId();

            $this->sincronizarLideres($grupoId, $lideresIds);
            $this->sincronizarMembros($grupoId, $lideresIds, $membrosIds);

            $this->connection->commit();
        } catch (Exception $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }

    public function atualizar(
        int $grupoId,
        string $nome,
        string $diaSemana,
        string $horario,
        string $localPadrao,
        int $localFixo,
        array $lideresIds,
        array $membrosIds
    ): void {
        if ($grupoId <= 0) {
            throw new InvalidArgumentException('Grupo Familiar inválido.');
        }

        if ($nome === '' || $diaSemana === '' || $horario === '') {
            throw new InvalidArgumentException('Preencha nome, dia da semana e horário.');
        }

        if (count($lideresIds) === 0) {
            throw new InvalidArgumentException('Selecione pelo menos um líder.');
        }

        $this->connection->beginTransaction();

        try {
            $stmt = $this->connection->prepare("
                UPDATE grupos_familiares
                SET nome = :nome,
                    dia_semana = :dia_semana,
                    horario = :horario,
                    local_padrao = :local_padrao,
                    local_fixo = :local_fixo
                WHERE id = :id
            ");

            $stmt->execute([
                ':nome' => $nome,
                ':dia_semana' => $diaSemana,
                ':horario' => $horario,
                ':local_padrao' => $localPadrao !== '' ? $localPadrao : null,
                ':local_fixo' => $localFixo,
                ':id' => $grupoId
            ]);

            $stmt = $this->connection->prepare("DELETE FROM grupo_lideres WHERE grupo_familiar_id = :grupo_id");
            $stmt->execute([':grupo_id' => $grupoId]);

            $stmt = $this->connection->prepare("DELETE FROM grupo_membros WHERE grupo_familiar_id = :grupo_id");
            $stmt->execute([':grupo_id' => $grupoId]);

            $this->sincronizarLideres($grupoId, $lideresIds);
            $this->sincronizarMembros($grupoId, $lideresIds, $membrosIds);

            $this->connection->commit();
        } catch (Exception $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }

    public function desativar(int $grupoId): void
    {
        $stmt = $this->connection->prepare("
            UPDATE grupos_familiares
            SET ativo = 0
            WHERE id = :id
        ");

        $stmt->execute([':id' => $grupoId]);
    }

    public function reativar(int $grupoId): void
    {
        $stmt = $this->connection->prepare("
            UPDATE grupos_familiares
            SET ativo = 1
            WHERE id = :id
        ");

        $stmt->execute([':id' => $grupoId]);
    }

    private function sincronizarLideres(int $grupoId, array $lideresIds): void
    {
        $stmtLider = $this->connection->prepare("
            INSERT INTO grupo_lideres (grupo_familiar_id, pessoa_id)
            VALUES (:grupo_id, :pessoa_id)
        ");

        foreach ($lideresIds as $pessoaId) {
            $stmtLider->execute([
                ':grupo_id' => $grupoId,
                ':pessoa_id' => (int) $pessoaId
            ]);
        }
    }

    private function sincronizarMembros(int $grupoId, array $lideresIds, array $membrosIds): void
    {
        $todosMembros = array_unique(array_merge($lideresIds, $membrosIds));

        $stmtMembro = $this->connection->prepare("
            INSERT INTO grupo_membros (grupo_familiar_id, pessoa_id)
            VALUES (:grupo_id, :pessoa_id)
        ");

        foreach ($todosMembros as $pessoaId) {
            $stmtMembro->execute([
                ':grupo_id' => $grupoId,
                ':pessoa_id' => (int) $pessoaId
            ]);
        }
    }

    public function listarTodos(): array
    {
        $sql = "
            SELECT
                gf.id,
                gf.nome,
                gf.dia_semana,
                gf.horario,
                gf.local_padrao,
                gf.local_fixo,
                gf.ativo,
                (
                    SELECT GROUP_CONCAT(p.nome, ', ')
                    FROM grupo_lideres gl
                    INNER JOIN pessoas p ON p.id = gl.pessoa_id
                    WHERE gl.grupo_familiar_id = gf.id
                    AND p.ativo = 1
                ) AS lideres,
                (
                    SELECT COUNT(*)
                    FROM grupo_membros gm
                    INNER JOIN pessoas p ON p.id = gm.pessoa_id
                    WHERE gm.grupo_familiar_id = gf.id
                    AND p.ativo = 1
                ) AS total_membros
            FROM grupos_familiares gf
            ORDER BY gf.id DESC
        ";

        $stmt = $this->connection->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarAtivos(): array
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

    public function listarPessoasAtivas(): array
    {
        $sql = "SELECT id, nome, cargo FROM pessoas WHERE ativo = 1 ORDER BY nome ASC";

        $stmt = $this->connection->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarPorId(int $grupoId): ?array
    {
        $stmt = $this->connection->prepare("
            SELECT id, nome, dia_semana, horario, local_padrao, local_fixo, ativo
            FROM grupos_familiares
            WHERE id = :id
            LIMIT 1
        ");

        $stmt->execute([':id' => $grupoId]);

        $grupo = $stmt->fetch(PDO::FETCH_ASSOC);

        return $grupo ?: null;
    }

    public function listarLideresIdsDoGrupo(int $grupoId): array
    {
        $stmt = $this->connection->prepare("
            SELECT pessoa_id
            FROM grupo_lideres
            WHERE grupo_familiar_id = :grupo_id
        ");

        $stmt->execute([':grupo_id' => $grupoId]);

        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public function listarMembrosIdsDoGrupo(int $grupoId): array
    {
        $stmt = $this->connection->prepare("
            SELECT pessoa_id
            FROM grupo_membros
            WHERE grupo_familiar_id = :grupo_id
        ");

        $stmt->execute([':grupo_id' => $grupoId]);

        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }
}
