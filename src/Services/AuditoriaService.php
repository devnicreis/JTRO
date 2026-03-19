<?php

require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Core/Auth.php';

class AuditoriaService
{
    private PDO $connection;

    public function __construct()
    {
        $this->connection = Database::getConnection();
    }

    public function registrar(
        string $acao,
        string $entidade,
        ?int $entidadeId = null,
        ?string $detalhes = null,
        ?int $usuarioId = null,
        ?int $grupoFamiliarId = null,
        ?string $reuniaoData = null
    ): void {
        if ($usuarioId === null && class_exists('Auth')) {
            $usuarioId = Auth::id();
        }

        $stmt = $this->connection->prepare("
            INSERT INTO logs (
                usuario_id,
                acao,
                entidade,
                entidade_id,
                grupo_familiar_id,
                reuniao_data,
                detalhes,
                created_at
            )
            VALUES (
                :usuario_id,
                :acao,
                :entidade,
                :entidade_id,
                :grupo_familiar_id,
                :reuniao_data,
                :detalhes,
                :created_at
            )
        ");

        $stmt->execute([
            ':usuario_id' => $usuarioId,
            ':acao' => $acao,
            ':entidade' => $entidade,
            ':entidade_id' => $entidadeId,
            ':grupo_familiar_id' => $grupoFamiliarId,
            ':reuniao_data' => $reuniaoData,
            ':detalhes' => $detalhes,
            ':created_at' => date('Y-m-d H:i:s')
        ]);
    }

    public function listarUltimosLogs(int $limite = 10): array
    {
        $limite = max(1, $limite);

        $sql = "
            SELECT
                l.id,
                l.usuario_id,
                l.acao,
                l.entidade,
                l.entidade_id,
                l.grupo_familiar_id,
                l.reuniao_data,
                l.detalhes,
                l.created_at,
                p.nome AS usuario_nome,
                gf.nome AS grupo_nome
            FROM logs l
            LEFT JOIN pessoas p ON p.id = l.usuario_id
            LEFT JOIN grupos_familiares gf ON gf.id = l.grupo_familiar_id
            ORDER BY l.created_at DESC, l.id DESC
            LIMIT {$limite}
        ";

        $stmt = $this->connection->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function contarLogsPorEntidadeEAcao(string $entidade, string $acao): int
    {
        $stmt = $this->connection->prepare("
            SELECT COUNT(*)
            FROM logs
            WHERE entidade = :entidade
            AND acao = :acao
        ");

        $stmt->execute([
            ':entidade' => $entidade,
            ':acao' => $acao
        ]);

        return (int) $stmt->fetchColumn();
    }

    public function listarLogsFiltrados(
        ?int $usuarioId = null,
        ?int $grupoFamiliarId = null,
        ?string $dataAlteracaoInicio = null,
        ?string $dataAlteracaoFim = null,
        ?string $dataReuniao = null,
        int $limite = 100
    ): array {
        $limite = max(1, $limite);

        $where = [];
        $params = [];

        if ($usuarioId !== null && $usuarioId > 0) {
            $where[] = "l.usuario_id = :usuario_id";
            $params[':usuario_id'] = $usuarioId;
        }

        if ($grupoFamiliarId !== null && $grupoFamiliarId > 0) {
            $where[] = "l.grupo_familiar_id = :grupo_familiar_id";
            $params[':grupo_familiar_id'] = $grupoFamiliarId;
        }

        if ($dataAlteracaoInicio !== null && $dataAlteracaoInicio !== '') {
            $where[] = "date(l.created_at) >= :data_alteracao_inicio";
            $params[':data_alteracao_inicio'] = $dataAlteracaoInicio;
        }

        if ($dataAlteracaoFim !== null && $dataAlteracaoFim !== '') {
            $where[] = "date(l.created_at) <= :data_alteracao_fim";
            $params[':data_alteracao_fim'] = $dataAlteracaoFim;
        }

        if ($dataReuniao !== null && $dataReuniao !== '') {
            $where[] = "l.reuniao_data = :data_reuniao";
            $params[':data_reuniao'] = $dataReuniao;
        }

        $whereSql = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "
            SELECT
                l.id,
                l.usuario_id,
                l.acao,
                l.entidade,
                l.entidade_id,
                l.grupo_familiar_id,
                l.reuniao_data,
                l.detalhes,
                l.created_at,
                p.nome AS usuario_nome,
                gf.nome AS grupo_nome
            FROM logs l
            LEFT JOIN pessoas p ON p.id = l.usuario_id
            LEFT JOIN grupos_familiares gf ON gf.id = l.grupo_familiar_id
            {$whereSql}
            ORDER BY l.created_at DESC, l.id DESC
            LIMIT {$limite}
        ";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}