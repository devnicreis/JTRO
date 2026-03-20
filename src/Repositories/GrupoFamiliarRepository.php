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
        string $itemCeleiro,
        int $domingoOracaoCulto,
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
                INSERT INTO grupos_familiares (
                    nome, dia_semana, horario, local_padrao, local_fixo,
                    item_celeiro, domingo_oracao_culto, ativo
                ) VALUES (
                    :nome, :dia_semana, :horario, :local_padrao, :local_fixo,
                    :item_celeiro, :domingo_oracao_culto, 1
                )
            ");
            $stmt->execute([
                ':nome'                 => $nome,
                ':dia_semana'           => $diaSemana,
                ':horario'              => $horario,
                ':local_padrao'         => $localPadrao !== '' ? $localPadrao : null,
                ':local_fixo'           => $localFixo,
                ':item_celeiro'         => $itemCeleiro !== '' ? $itemCeleiro : null,
                ':domingo_oracao_culto' => $domingoOracaoCulto > 0 ? $domingoOracaoCulto : null,
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
        string $itemCeleiro,
        int $domingoOracaoCulto,
        array $lideresIds,
        array $membrosIds
    ): void {
        if ($grupoId <= 0) throw new InvalidArgumentException('Grupo Familiar inválido.');
        if ($nome === '' || $diaSemana === '' || $horario === '') {
            throw new InvalidArgumentException('Preencha nome, dia da semana e horário.');
        }
        if ($localFixo === 1 && trim($localPadrao) === '') {
            throw new InvalidArgumentException('Se o GF possui local fixo, o local padrão é obrigatório.');
        }
        if (count($lideresIds) === 0) throw new InvalidArgumentException('Selecione pelo menos um líder.');

        $this->connection->beginTransaction();
        try {
            $stmt = $this->connection->prepare("
                UPDATE grupos_familiares
                SET nome                 = :nome,
                    dia_semana           = :dia_semana,
                    horario              = :horario,
                    local_padrao         = :local_padrao,
                    local_fixo           = :local_fixo,
                    item_celeiro         = :item_celeiro,
                    domingo_oracao_culto = :domingo_oracao_culto
                WHERE id = :id
            ");
            $stmt->execute([
                ':nome'                 => $nome,
                ':dia_semana'           => $diaSemana,
                ':horario'              => $horario,
                ':local_padrao'         => $localPadrao !== '' ? $localPadrao : null,
                ':local_fixo'           => $localFixo,
                ':item_celeiro'         => $itemCeleiro !== '' ? $itemCeleiro : null,
                ':domingo_oracao_culto' => $domingoOracaoCulto > 0 ? $domingoOracaoCulto : null,
                ':id'                   => $grupoId,
            ]);

            $this->connection->prepare("DELETE FROM grupo_lideres WHERE grupo_familiar_id = :id")
                ->execute([':id' => $grupoId]);
            $this->connection->prepare("DELETE FROM grupo_membros WHERE grupo_familiar_id = :id")
                ->execute([':id' => $grupoId]);

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
        $this->connection->prepare("UPDATE grupos_familiares SET ativo = 0 WHERE id = :id")
            ->execute([':id' => $grupoId]);
    }

    public function reativar(int $grupoId): void
    {
        $this->connection->prepare("UPDATE grupos_familiares SET ativo = 1 WHERE id = :id")
            ->execute([':id' => $grupoId]);
    }

    private function sincronizarLideres(int $grupoId, array $lideresIds): void
    {
        $stmt = $this->connection->prepare("
            INSERT INTO grupo_lideres (grupo_familiar_id, pessoa_id) VALUES (:grupo_id, :pessoa_id)
        ");
        foreach ($lideresIds as $id) {
            $stmt->execute([':grupo_id' => $grupoId, ':pessoa_id' => (int) $id]);
        }
    }

    private function sincronizarMembros(int $grupoId, array $lideresIds, array $membrosIds): void
    {
        $todos = array_unique(array_merge($lideresIds, $membrosIds));
        $stmt  = $this->connection->prepare("
            INSERT INTO grupo_membros (grupo_familiar_id, pessoa_id) VALUES (:grupo_id, :pessoa_id)
        ");
        foreach ($todos as $id) {
            $stmt->execute([':grupo_id' => $grupoId, ':pessoa_id' => (int) $id]);
        }
    }

    public function listarTodos(): array
    {
        $sql = "
            SELECT
                gf.id, gf.nome, gf.dia_semana, gf.horario,
                gf.local_padrao, gf.local_fixo, gf.ativo,
                gf.item_celeiro, gf.domingo_oracao_culto,
                (
                    SELECT GROUP_CONCAT(p.nome, ', ')
                    FROM grupo_lideres gl
                    INNER JOIN pessoas p ON p.id = gl.pessoa_id
                    WHERE gl.grupo_familiar_id = gf.id AND p.ativo = 1
                ) AS lideres,
                (
                    SELECT COUNT(*)
                    FROM grupo_membros gm
                    INNER JOIN pessoas p ON p.id = gm.pessoa_id
                    WHERE gm.grupo_familiar_id = gf.id AND p.ativo = 1
                ) AS total_membros
            FROM grupos_familiares gf
            ORDER BY gf.id DESC
        ";
        return $this->connection->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarAtivos(): array
    {
        return $this->connection->query("
            SELECT id, nome, dia_semana, horario, local_padrao, local_fixo,
                   item_celeiro, domingo_oracao_culto
            FROM grupos_familiares WHERE ativo = 1 ORDER BY nome ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarPessoasAtivas(): array
    {
        return $this->connection->query("
            SELECT id, nome, cargo FROM pessoas WHERE ativo = 1 ORDER BY nome ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarPorId(int $grupoId): ?array
    {
        $stmt = $this->connection->prepare("
            SELECT id, nome, dia_semana, horario, local_padrao, local_fixo,
                   item_celeiro, domingo_oracao_culto, ativo
            FROM grupos_familiares WHERE id = :id LIMIT 1
        ");
        $stmt->execute([':id' => $grupoId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function listarLideresIdsDoGrupo(int $grupoId): array
    {
        $stmt = $this->connection->prepare("SELECT pessoa_id FROM grupo_lideres WHERE grupo_familiar_id = :id");
        $stmt->execute([':id' => $grupoId]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public function listarMembrosIdsDoGrupo(int $grupoId): array
    {
        $stmt = $this->connection->prepare("SELECT pessoa_id FROM grupo_membros WHERE grupo_familiar_id = :id");
        $stmt->execute([':id' => $grupoId]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public function buscarPorNome(string $nome): ?array
    {
        $stmt = $this->connection->prepare("
            SELECT id, nome FROM grupos_familiares
            WHERE LOWER(TRIM(nome)) = LOWER(TRIM(:nome)) LIMIT 1
        ");
        $stmt->execute([':nome' => $nome]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function buscarPorNomeExcetoId(string $nome, int $id): ?array
    {
        $stmt = $this->connection->prepare("
            SELECT id, nome FROM grupos_familiares
            WHERE LOWER(TRIM(nome)) = LOWER(TRIM(:nome)) AND id != :id LIMIT 1
        ");
        $stmt->execute([':nome' => $nome, ':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function contarGruposAtivos(): int
    {
        return (int) $this->connection->query(
            "SELECT COUNT(*) FROM grupos_familiares WHERE ativo = 1"
        )->fetchColumn();
    }

    public function listarGruposDoLider(int $pessoaId): array
    {
        $stmt = $this->connection->prepare("
            SELECT
                gf.id, gf.nome, gf.dia_semana, gf.horario,
                gf.local_padrao, gf.local_fixo,
                gf.item_celeiro, gf.domingo_oracao_culto,
                (
                    SELECT COUNT(*) FROM grupo_membros gm
                    INNER JOIN pessoas p ON p.id = gm.pessoa_id
                    WHERE gm.grupo_familiar_id = gf.id AND p.ativo = 1
                ) AS total_membros_ativos,
                (
                    SELECT COUNT(*) FROM reunioes r WHERE r.grupo_familiar_id = gf.id
                ) AS total_reunioes,
                (
                    SELECT MAX(r.data) FROM reunioes r WHERE r.grupo_familiar_id = gf.id
                ) AS ultima_reuniao
            FROM grupos_familiares gf
            INNER JOIN grupo_lideres gl ON gl.grupo_familiar_id = gf.id
            WHERE gf.ativo = 1 AND gl.pessoa_id = :pessoa_id
            ORDER BY gf.nome ASC
        ");
        $stmt->execute([':pessoa_id' => $pessoaId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarPorLider(int $liderId): array
    {
        $stmt = $this->connection->prepare("
            SELECT
                gf.id, gf.nome, gf.dia_semana, gf.horario,
                gf.local_padrao, gf.local_fixo, gf.ativo,
                gf.item_celeiro, gf.domingo_oracao_culto,
                (
                    SELECT GROUP_CONCAT(p.nome, ', ')
                    FROM grupo_lideres gl2
                    INNER JOIN pessoas p ON p.id = gl2.pessoa_id
                    WHERE gl2.grupo_familiar_id = gf.id AND p.ativo = 1
                ) AS lideres,
                (
                    SELECT COUNT(*) FROM grupo_membros gm
                    INNER JOIN pessoas p ON p.id = gm.pessoa_id
                    WHERE gm.grupo_familiar_id = gf.id AND p.ativo = 1
                ) AS total_membros
            FROM grupos_familiares gf
            INNER JOIN grupo_lideres gl ON gl.grupo_familiar_id = gf.id
            WHERE gf.ativo = 1 AND gl.pessoa_id = :lider_id
            ORDER BY gf.nome ASC
        ");
        $stmt->execute([':lider_id' => $liderId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
