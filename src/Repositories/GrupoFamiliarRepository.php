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
        string $perfilGrupo,
        string $localPadrao,
        int $localFixo,
        string $itemCeleiro,
        int $domingoOracaoCulto,
        array $lideresIds,
        array $membrosIds
    ): void {
        if ($nome === '' || $diaSemana === '' || $horario === '' || $perfilGrupo === '') {
            throw new InvalidArgumentException('Preencha nome, dia da semana, horário e perfil do grupo.');
        }
        if ($localFixo === 1 && trim($localPadrao) === '') {
            throw new InvalidArgumentException('Se o GF possui local fixo, o local padrão é obrigatório.');
        }
        if (count($lideresIds) === 0) {
            throw new InvalidArgumentException('Selecione pelo menos um líder.');
        }

        $this->validarParticipantesPorPerfil($perfilGrupo, array_merge($lideresIds, $membrosIds));

        $this->connection->beginTransaction();
        try {
            $stmt = $this->connection->prepare("
                INSERT INTO grupos_familiares (
                    nome, dia_semana, horario, perfil_grupo, local_padrao, local_fixo,
                    item_celeiro, domingo_oracao_culto, ativo
                ) VALUES (
                    :nome, :dia_semana, :horario, :perfil_grupo, :local_padrao, :local_fixo,
                    :item_celeiro, :domingo_oracao_culto, 1
                )
            ");
            $stmt->execute([
                ':nome' => $nome,
                ':dia_semana' => $diaSemana,
                ':horario' => $horario,
                ':perfil_grupo' => $perfilGrupo,
                ':local_padrao' => $localPadrao !== '' ? $localPadrao : null,
                ':local_fixo' => $localFixo,
                ':item_celeiro' => $itemCeleiro !== '' ? $itemCeleiro : null,
                ':domingo_oracao_culto' => $domingoOracaoCulto > 0 ? $domingoOracaoCulto : null,
            ]);

            $grupoId = (int) $this->connection->lastInsertId();
            $this->sincronizarLideres($grupoId, $lideresIds);
            $todosMembros = $this->sincronizarMembros($grupoId, $lideresIds, $membrosIds);
            $this->sincronizarGrupoPrincipalDasPessoas($grupoId, $todosMembros, []);
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
        string $perfilGrupo,
        string $localPadrao,
        int $localFixo,
        string $itemCeleiro,
        int $domingoOracaoCulto,
        array $lideresIds,
        array $membrosIds
    ): void {
        if ($grupoId <= 0) {
            throw new InvalidArgumentException('Grupo Familiar inválido.');
        }
        if ($nome === '' || $diaSemana === '' || $horario === '' || $perfilGrupo === '') {
            throw new InvalidArgumentException('Preencha nome, dia da semana, horário e perfil do grupo.');
        }
        if ($localFixo === 1 && trim($localPadrao) === '') {
            throw new InvalidArgumentException('Se o GF possui local fixo, o local padrão é obrigatório.');
        }
        if (count($lideresIds) === 0) {
            throw new InvalidArgumentException('Selecione pelo menos um líder.');
        }

        $this->validarParticipantesPorPerfil($perfilGrupo, array_merge($lideresIds, $membrosIds));

        $membrosAnteriores = $this->listarMembrosIdsDoGrupo($grupoId);

        $this->connection->beginTransaction();
        try {
            $stmt = $this->connection->prepare("
                UPDATE grupos_familiares
                SET nome = :nome,
                    dia_semana = :dia_semana,
                    horario = :horario,
                    perfil_grupo = :perfil_grupo,
                    local_padrao = :local_padrao,
                    local_fixo = :local_fixo,
                    item_celeiro = :item_celeiro,
                    domingo_oracao_culto = :domingo_oracao_culto
                WHERE id = :id
            ");
            $stmt->execute([
                ':nome' => $nome,
                ':dia_semana' => $diaSemana,
                ':horario' => $horario,
                ':perfil_grupo' => $perfilGrupo,
                ':local_padrao' => $localPadrao !== '' ? $localPadrao : null,
                ':local_fixo' => $localFixo,
                ':item_celeiro' => $itemCeleiro !== '' ? $itemCeleiro : null,
                ':domingo_oracao_culto' => $domingoOracaoCulto > 0 ? $domingoOracaoCulto : null,
                ':id' => $grupoId,
            ]);

            $this->connection->prepare("DELETE FROM grupo_lideres WHERE grupo_familiar_id = :id")
                ->execute([':id' => $grupoId]);
            $this->connection->prepare("DELETE FROM grupo_membros WHERE grupo_familiar_id = :id")
                ->execute([':id' => $grupoId]);

            $this->sincronizarLideres($grupoId, $lideresIds);
            $todosMembros = $this->sincronizarMembros($grupoId, $lideresIds, $membrosIds);
            $this->sincronizarGrupoPrincipalDasPessoas($grupoId, $todosMembros, $membrosAnteriores);
            $this->connection->commit();
        } catch (Exception $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }

    public function desativar(int $grupoId, string $motivo): void
    {
        $this->connection->prepare("
            UPDATE grupos_familiares
            SET ativo = 0,
                motivo_desativacao = :motivo
            WHERE id = :id
        ")->execute([
            ':motivo' => trim($motivo) !== '' ? trim($motivo) : null,
            ':id' => $grupoId
        ]);
    }

    public function reativar(int $grupoId): void
    {
        $this->connection->prepare("
            UPDATE grupos_familiares
            SET ativo = 1,
                motivo_desativacao = NULL
            WHERE id = :id
        ")->execute([':id' => $grupoId]);
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

    private function sincronizarMembros(int $grupoId, array $lideresIds, array $membrosIds): array
    {
        $todos = array_values(array_unique(array_merge($lideresIds, $membrosIds)));
        $stmt = $this->connection->prepare("
            INSERT INTO grupo_membros (grupo_familiar_id, pessoa_id) VALUES (:grupo_id, :pessoa_id)
        ");
        foreach ($todos as $id) {
            $stmt->execute([':grupo_id' => $grupoId, ':pessoa_id' => (int) $id]);
        }

        return array_map('intval', $todos);
    }

    private function sincronizarGrupoPrincipalDasPessoas(int $grupoId, array $membrosAtuais, array $membrosAnteriores): void
    {
        $removidos = array_diff($membrosAnteriores, $membrosAtuais);

        if (count($removidos) > 0) {
            $placeholders = implode(',', array_fill(0, count($removidos), '?'));
            $params = array_merge([$grupoId], array_values($removidos));

            $stmt = $this->connection->prepare("
                UPDATE pessoas
                SET grupo_familiar_id = NULL
                WHERE grupo_familiar_id = ?
                  AND id IN ({$placeholders})
            ");
            $stmt->execute($params);
        }

        if (count($membrosAtuais) > 0) {
            $placeholders = implode(',', array_fill(0, count($membrosAtuais), '?'));
            $params = array_merge([$grupoId], array_values($membrosAtuais));

            $stmt = $this->connection->prepare("
                UPDATE pessoas
                SET grupo_familiar_id = ?
                WHERE id IN ({$placeholders})
                  AND (grupo_familiar_id IS NULL OR grupo_familiar_id = ?)
            ");
            $stmt->execute(array_merge($params, [$grupoId]));
        }
    }

    public function listarTodos(array $filtros = []): array
    {
        $sql = "
            SELECT
                gf.id, gf.nome, gf.dia_semana, gf.horario, gf.perfil_grupo,
                gf.local_padrao, gf.local_fixo, gf.ativo,
                gf.item_celeiro, gf.domingo_oracao_culto, gf.motivo_desativacao,
                (
                    SELECT GROUP_CONCAT(p.nome, ', ')
                    FROM grupo_lideres gl
                    INNER JOIN pessoas p ON p.id = gl.pessoa_id
                    WHERE gl.grupo_familiar_id = gf.id AND p.ativo = 1
                ) AS lideres,
                (
                    SELECT GROUP_CONCAT(p.nome, ', ')
                    FROM grupo_membros gm
                    INNER JOIN pessoas p ON p.id = gm.pessoa_id
                    WHERE gm.grupo_familiar_id = gf.id AND p.ativo = 1
                ) AS membros_nomes,
                (
                    SELECT COUNT(*)
                    FROM grupo_membros gm
                    INNER JOIN pessoas p ON p.id = gm.pessoa_id
                    WHERE gm.grupo_familiar_id = gf.id AND p.ativo = 1
                ) AS total_membros
            FROM grupos_familiares gf
        ";

        $where = [];
        $params = [];

        if (($filtros['id'] ?? '') !== '') {
            $where[] = 'gf.id = :id';
            $params[':id'] = (int) $filtros['id'];
        }

        if (($filtros['nome'] ?? '') !== '') {
            $where[] = 'LOWER(gf.nome) LIKE :nome';
            $params[':nome'] = '%' . mb_strtolower(trim($filtros['nome'])) . '%';
        }

        if (($filtros['dia_semana'] ?? '') !== '') {
            $where[] = 'gf.dia_semana = :dia_semana';
            $params[':dia_semana'] = $filtros['dia_semana'];
        }

        if (($filtros['horario'] ?? '') !== '') {
            $where[] = 'gf.horario = :horario';
            $params[':horario'] = $filtros['horario'];
        }

        if (($filtros['perfil_grupo'] ?? '') !== '') {
            $where[] = 'gf.perfil_grupo = :perfil_grupo';
            $params[':perfil_grupo'] = $filtros['perfil_grupo'];
        }

        if (($filtros['local_padrao'] ?? '') !== '') {
            $where[] = 'LOWER(COALESCE(gf.local_padrao, \'\')) LIKE :local_padrao';
            $params[':local_padrao'] = '%' . mb_strtolower(trim($filtros['local_padrao'])) . '%';
        }

        if (($filtros['local_fixo'] ?? '') !== '') {
            $where[] = 'gf.local_fixo = :local_fixo';
            $params[':local_fixo'] = (int) $filtros['local_fixo'];
        }

        if (($filtros['item_celeiro'] ?? '') !== '') {
            $where[] = 'LOWER(COALESCE(gf.item_celeiro, \'\')) LIKE :item_celeiro';
            $params[':item_celeiro'] = '%' . mb_strtolower(trim($filtros['item_celeiro'])) . '%';
        }

        if (($filtros['domingo_oracao_culto'] ?? '') !== '') {
            $where[] = 'gf.domingo_oracao_culto = :domingo_oracao_culto';
            $params[':domingo_oracao_culto'] = (int) $filtros['domingo_oracao_culto'];
        }

        if (($filtros['status'] ?? '') !== '') {
            $where[] = 'gf.ativo = :ativo';
            $params[':ativo'] = (int) $filtros['status'];
        }

        if (($filtros['lideres'] ?? '') !== '') {
            $where[] = "EXISTS (
                SELECT 1
                FROM grupo_lideres glf
                INNER JOIN pessoas pl ON pl.id = glf.pessoa_id
                WHERE glf.grupo_familiar_id = gf.id
                  AND LOWER(pl.nome) LIKE :lideres
            )";
            $params[':lideres'] = '%' . mb_strtolower(trim($filtros['lideres'])) . '%';
        }

        if (($filtros['membros'] ?? '') !== '') {
            $where[] = "EXISTS (
                SELECT 1
                FROM grupo_membros gmf
                INNER JOIN pessoas pm ON pm.id = gmf.pessoa_id
                WHERE gmf.grupo_familiar_id = gf.id
                  AND LOWER(pm.nome) LIKE :membros
            )";
            $params[':membros'] = '%' . mb_strtolower(trim($filtros['membros'])) . '%';
        }

        if (count($where) > 0) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY gf.id DESC';

        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarAtivos(): array
    {
        return $this->connection->query("
            SELECT id, nome, dia_semana, horario, perfil_grupo, local_padrao, local_fixo,
                   item_celeiro, domingo_oracao_culto
            FROM grupos_familiares WHERE ativo = 1 ORDER BY nome ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarPessoasAtivas(): array
    {
        return $this->connection->query("
            SELECT id, nome, cargo, genero FROM pessoas WHERE ativo = 1 ORDER BY nome ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarPorId(int $grupoId): ?array
    {
        $stmt = $this->connection->prepare("
            SELECT id, nome, dia_semana, horario, perfil_grupo, local_padrao, local_fixo,
                   item_celeiro, domingo_oracao_culto, ativo, motivo_desativacao
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

    private function validarParticipantesPorPerfil(string $perfilGrupo, array $pessoasIds): void
    {
        $pessoasIds = array_values(array_unique(array_map('intval', $pessoasIds)));

        if ($perfilGrupo !== 'mulheres' || count($pessoasIds) === 0) {
            return;
        }

        $placeholders = implode(',', array_fill(0, count($pessoasIds), '?'));
        $stmt = $this->connection->prepare("
            SELECT id, nome, COALESCE(genero, '') AS genero
            FROM pessoas
            WHERE id IN ({$placeholders})
        ");
        $stmt->execute($pessoasIds);
        $pessoas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $invalidas = [];
        foreach ($pessoas as $pessoa) {
            if (($pessoa['genero'] ?? '') !== 'feminino') {
                $invalidas[] = (string) ($pessoa['nome'] ?? 'Pessoa sem nome');
            }
        }

        if ($invalidas !== []) {
            throw new InvalidArgumentException(
                'Grupo Familiar com perfil Mulheres aceita apenas pessoas com genero Feminino. Ajuste: '
                . implode(', ', $invalidas) . '.'
            );
        }
    }

    public function listarGruposDoLider(int $pessoaId): array
    {
        $stmt = $this->connection->prepare("
            SELECT
                gf.id, gf.nome, gf.dia_semana, gf.horario, gf.perfil_grupo,
                gf.local_padrao, gf.local_fixo,
                gf.item_celeiro, gf.domingo_oracao_culto,
                (
                    SELECT MIN(ce.data_escala)
                    FROM cantina_escalas ce
                    WHERE ce.grupo_familiar_id = gf.id
                      AND ce.data_escala >= date('now', 'localtime')
                ) AS proxima_cantina_data,
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
                gf.id, gf.nome, gf.dia_semana, gf.horario, gf.perfil_grupo,
                gf.local_padrao, gf.local_fixo, gf.ativo,
                gf.item_celeiro, gf.domingo_oracao_culto,
                (
                    SELECT MIN(ce.data_escala)
                    FROM cantina_escalas ce
                    WHERE ce.grupo_familiar_id = gf.id
                      AND ce.data_escala >= date('now', 'localtime')
                ) AS proxima_cantina_data,
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
