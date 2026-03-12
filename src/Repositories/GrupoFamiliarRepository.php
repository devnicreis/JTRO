<?php

require_once __DIR__ . '/../Core/Database.php';

class GrupoFamiliarRepository
{
    private PDO $connection;

    public function __construct()
    {
        $this->connection = Database::getConnection();
    }

    public function salvar(string $nome, string $diaSemana, string $horario, array $lideresIds, array $membrosIds): void
    {
        if ($nome === '' || $diaSemana === '' || $horario === '') {
            throw new InvalidArgumentException('Preencha nome, dia da semana e horário.');
        }

        if (count($lideresIds) === 0) {
            throw new InvalidArgumentException('Selecione pelo menos um líder.');
        }

        $this->connection->beginTransaction();

        try {
            $stmt = $this->connection->prepare("
                INSERT INTO grupos_familiares (nome, dia_semana, horario)
                VALUES (:nome, :dia_semana, :horario)
            ");

            $stmt->execute([
                ':nome' => $nome,
                ':dia_semana' => $diaSemana,
                ':horario' => $horario
            ]);

            $grupoId = (int) $this->connection->lastInsertId();

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

            $this->connection->commit();
        } catch (Exception $e) {
            $this->connection->rollBack();
            throw $e;
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
                (
                    SELECT GROUP_CONCAT(p.nome, ', ')
                    FROM grupo_lideres gl
                    INNER JOIN pessoas p ON p.id = gl.pessoa_id
                    WHERE gl.grupo_familiar_id = gf.id
                ) AS lideres,
                (
                    SELECT COUNT(*)
                    FROM grupo_membros gm
                    WHERE gm.grupo_familiar_id = gf.id
                ) AS total_membros
            FROM grupos_familiares gf
            ORDER BY gf.id DESC
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
}