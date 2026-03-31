<?php

require_once __DIR__ . '/../Core/Database.php';

class CantinaRepository
{
    private PDO $connection;

    public function __construct()
    {
        $this->connection = Database::getConnection();
    }

    public function salvarEscala(?int $id, int $grupoFamiliarId, string $dataEscala, ?string $observacoes = null): int
    {
        if ($grupoFamiliarId <= 0) {
            throw new InvalidArgumentException('Selecione um Grupo Familiar para a escala da cantina.');
        }

        $data = DateTime::createFromFormat('Y-m-d', $dataEscala);
        if (!$data || $data->format('Y-m-d') !== $dataEscala) {
            throw new InvalidArgumentException('Informe uma data válida para a escala da cantina.');
        }

        $stmtGrupo = $this->connection->prepare("
            SELECT id
            FROM grupos_familiares
            WHERE id = :id
              AND ativo = 1
            LIMIT 1
        ");
        $stmtGrupo->execute([':id' => $grupoFamiliarId]);
        if (!$stmtGrupo->fetchColumn()) {
            throw new InvalidArgumentException('Grupo Familiar inválido para a escala da cantina.');
        }

        $stmtConflito = $this->connection->prepare("
            SELECT id
            FROM cantina_escalas
            WHERE data_escala = :data_escala
              AND (:id IS NULL OR id != :id)
            LIMIT 1
        ");
        $stmtConflito->bindValue(':data_escala', $dataEscala);
        $stmtConflito->bindValue(':id', $id, $id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmtConflito->execute();
        if ($stmtConflito->fetchColumn()) {
            throw new InvalidArgumentException('Já existe uma escala da cantina cadastrada para essa data.');
        }

        $dados = [
            ':data_escala' => $dataEscala,
            ':grupo_familiar_id' => $grupoFamiliarId,
            ':observacoes' => $this->normalizarTextoOpcional($observacoes),
            ':updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($id !== null && $id > 0) {
            $stmt = $this->connection->prepare("
                UPDATE cantina_escalas
                SET data_escala = :data_escala,
                    grupo_familiar_id = :grupo_familiar_id,
                    observacoes = :observacoes,
                    updated_at = :updated_at
                WHERE id = :id
            ");
            $dados[':id'] = $id;
            $stmt->execute($dados);
            return $id;
        }

        $stmt = $this->connection->prepare("
            INSERT INTO cantina_escalas (
                data_escala, grupo_familiar_id, observacoes, created_at, updated_at
            ) VALUES (
                :data_escala, :grupo_familiar_id, :observacoes, :created_at, :updated_at
            )
        ");
        $dados[':created_at'] = date('Y-m-d H:i:s');
        $stmt->execute($dados);

        return (int) $this->connection->lastInsertId();
    }

    public function excluirEscala(int $id): void
    {
        $stmt = $this->connection->prepare('DELETE FROM cantina_escalas WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->connection->prepare("
            SELECT ce.*, gf.nome AS grupo_nome
            FROM cantina_escalas ce
            INNER JOIN grupos_familiares gf ON gf.id = ce.grupo_familiar_id
            WHERE ce.id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function listarEscalasDoAno(int $ano, array $filtros = []): array
    {
        if ($ano < 2000 || $ano > 2100) {
            throw new InvalidArgumentException('Ano inválido para consulta da cantina.');
        }

        $inicio = sprintf('%04d-01-01', $ano);
        $fim = sprintf('%04d-12-31', $ano);

        $stmt = $this->connection->prepare("
            SELECT
                ce.id,
                ce.data_escala,
                ce.grupo_familiar_id,
                ce.observacoes,
                gf.nome AS grupo_nome
            FROM cantina_escalas ce
            INNER JOIN grupos_familiares gf ON gf.id = ce.grupo_familiar_id
            WHERE ce.data_escala BETWEEN :inicio AND :fim
            ORDER BY ce.data_escala ASC, ce.id ASC
        ");
        $stmt->execute([
            ':inicio' => $inicio,
            ':fim' => $fim,
        ]);
        $salvas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $salvasPorData = [];
        foreach ($salvas as $escala) {
            $salvasPorData[$escala['data_escala']] = $escala;
        }

        $linhas = [];
        $dataCursor = new DateTime($inicio);
        $dataFim = new DateTime($fim);

        while ($dataCursor <= $dataFim) {
            if ((int) $dataCursor->format('w') === 0) {
                $dataAtual = $dataCursor->format('Y-m-d');
                $escala = $salvasPorData[$dataAtual] ?? null;

                $linhas[] = [
                    'id' => (int) ($escala['id'] ?? 0),
                    'data_escala' => $dataAtual,
                    'grupo_familiar_id' => (int) ($escala['grupo_familiar_id'] ?? 0),
                    'grupo_nome' => $escala['grupo_nome'] ?? '',
                    'observacoes' => $escala['observacoes'] ?? '',
                    'origem' => $escala ? 'salva' : 'domingo_padrao',
                    'eh_domingo' => true,
                ];
            }

            $dataCursor->modify('+1 day');
        }

        foreach ($salvas as $escala) {
            $dataEscala = (string) $escala['data_escala'];
            $diaSemana = (int) (new DateTime($dataEscala))->format('w');
            if ($diaSemana !== 0) {
                $linhas[] = [
                    'id' => (int) $escala['id'],
                    'data_escala' => $dataEscala,
                    'grupo_familiar_id' => (int) $escala['grupo_familiar_id'],
                    'grupo_nome' => $escala['grupo_nome'] ?? '',
                    'observacoes' => $escala['observacoes'] ?? '',
                    'origem' => 'data_alterada',
                    'eh_domingo' => false,
                ];
            }
        }

        usort($linhas, function (array $a, array $b) {
            return strcmp((string) $a['data_escala'], (string) $b['data_escala']);
        });

        $dataFiltro = trim((string) ($filtros['data_escala'] ?? ''));
        if ($dataFiltro !== '') {
            $linhas = array_values(array_filter($linhas, function (array $linha) use ($dataFiltro) {
                return (string) ($linha['data_escala'] ?? '') === $dataFiltro;
            }));
        }

        $grupoFiltro = (int) ($filtros['grupo_familiar_id'] ?? 0);
        if ($grupoFiltro > 0) {
            $linhas = array_values(array_filter($linhas, function (array $linha) use ($grupoFiltro) {
                return (int) ($linha['grupo_familiar_id'] ?? 0) === $grupoFiltro;
            }));
        }

        return $linhas;
    }

    public function buscarProximaEscalaDoGrupo(int $grupoId, ?string $dataBase = null): ?array
    {
        $dataBase = $dataBase ?: date('Y-m-d');

        $stmt = $this->connection->prepare("
            SELECT
                ce.id,
                ce.data_escala,
                ce.observacoes,
                gf.nome AS grupo_nome
            FROM cantina_escalas ce
            INNER JOIN grupos_familiares gf ON gf.id = ce.grupo_familiar_id
            WHERE ce.grupo_familiar_id = :grupo_id
              AND ce.data_escala >= :data_base
            ORDER BY ce.data_escala ASC
            LIMIT 1
        ");
        $stmt->execute([
            ':grupo_id' => $grupoId,
            ':data_base' => $dataBase,
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function normalizarTextoOpcional(?string $valor): ?string
    {
        $valor = trim((string) $valor);
        return $valor !== '' ? $valor : null;
    }
}
