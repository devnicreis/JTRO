<?php

require_once __DIR__ . '/../Core/Database.php';

class RelatorioRepository
{
    private static ?PDO $connection = null;

    private static function connection(): PDO
    {
        if (self::$connection === null) {
            self::$connection = Database::getConnection();
        }

        return self::$connection;
    }

    private static function percentual(int $parte, int $todo): float
    {
        if ($todo <= 0) {
            return 0.0;
        }

        return round(($parte / $todo) * 100, 1);
    }

    private static function tabelaTemColuna(string $tabela, string $coluna): bool
    {
        $stmt = self::connection()->query(sprintf('PRAGMA table_info(%s)', $tabela));
        $colunas = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        foreach ($colunas as $dadosColuna) {
            if (($dadosColuna['name'] ?? '') === $coluna) {
                return true;
            }
        }

        return false;
    }

    private static function buscarInfoGrupoFamiliar(int $gfId): ?array
    {
        $stmt = self::connection()->prepare("
            SELECT
                gf.id,
                gf.nome AS gf_nome,
                COALESCE(
                    (
                        SELECT GROUP_CONCAT(p.nome, ', ')
                        FROM grupo_lideres gl
                        INNER JOIN pessoas p ON p.id = gl.pessoa_id
                        WHERE gl.grupo_familiar_id = gf.id
                          AND p.ativo = 1
                    ),
                    'Sem lider cadastrado'
                ) AS lider_nome
            FROM grupos_familiares gf
            WHERE gf.id = :gf_id
            LIMIT 1
        ");
        $stmt->execute([':gf_id' => $gfId]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function mapeamentoAssiduidadeGlobal(string $dataInicial, string $dataFinal): array
    {
        $conn = self::connection();

        $stmtMembros = $conn->query("
            SELECT COUNT(*) AS total_membros
            FROM pessoas
            WHERE ativo = 1
        ");
        $totalMembros = (int) ($stmtMembros->fetchColumn() ?: 0);

        $stmtKpi = $conn->prepare("
            SELECT
                SUM(CASE WHEN pr.status = 'presente' THEN 1 ELSE 0 END) AS total_presencas,
                SUM(CASE WHEN pr.status = 'ausente' AND pr.ausencia_tipo = 'justificada' THEN 1 ELSE 0 END) AS faltas_justificadas,
                SUM(CASE WHEN pr.status = 'ausente' AND COALESCE(pr.ausencia_tipo, 'injustificada') <> 'justificada' THEN 1 ELSE 0 END) AS faltas_injustificadas,
                COUNT(pr.id) AS total_registros
            FROM presencas pr
            INNER JOIN reunioes r ON r.id = pr.reuniao_id
            WHERE r.data BETWEEN :di AND :df
        ");
        $stmtKpi->execute([
            ':di' => $dataInicial,
            ':df' => $dataFinal,
        ]);
        $kpis = $stmtKpi->fetch(PDO::FETCH_ASSOC) ?: [];

        $totalRegistros = (int) ($kpis['total_registros'] ?? 0);
        $totalPresencas = (int) ($kpis['total_presencas'] ?? 0);
        $faltasJustificadas = (int) ($kpis['faltas_justificadas'] ?? 0);
        $faltasInjustificadas = (int) ($kpis['faltas_injustificadas'] ?? 0);

        $stmtMensal = $conn->prepare("
            SELECT
                strftime('%Y-%m', r.data) AS mes,
                SUM(CASE WHEN pr.status = 'presente' THEN 1 ELSE 0 END) AS presencas,
                SUM(CASE WHEN pr.status = 'ausente' AND pr.ausencia_tipo = 'justificada' THEN 1 ELSE 0 END) AS just,
                SUM(CASE WHEN pr.status = 'ausente' AND COALESCE(pr.ausencia_tipo, 'injustificada') <> 'justificada' THEN 1 ELSE 0 END) AS injust,
                COUNT(pr.id) AS total
            FROM reunioes r
            INNER JOIN presencas pr ON pr.reuniao_id = r.id
            WHERE r.data BETWEEN :di AND :df
            GROUP BY strftime('%Y-%m', r.data)
            ORDER BY mes ASC
        ");
        $stmtMensal->execute([
            ':di' => $dataInicial,
            ':df' => $dataFinal,
        ]);
        $mensal = $stmtMensal->fetchAll(PDO::FETCH_ASSOC);

        foreach ($mensal as &$mes) {
            $mes['taxa_presenca'] = self::percentual((int) ($mes['presencas'] ?? 0), (int) ($mes['total'] ?? 0));
        }
        unset($mes);

        $stmtPorGf = $conn->prepare("
            SELECT
                gf.id,
                gf.nome AS gf_nome,
                COALESCE(
                    (
                        SELECT GROUP_CONCAT(p.nome, ', ')
                        FROM grupo_lideres gl
                        INNER JOIN pessoas p ON p.id = gl.pessoa_id
                        WHERE gl.grupo_familiar_id = gf.id
                          AND p.ativo = 1
                    ),
                    'Sem lider'
                ) AS lider_nome,
                COUNT(DISTINCT gm.pessoa_id) AS qtd_membros,
                COUNT(DISTINCT r.id) AS qtd_reunioes,
                SUM(CASE WHEN pr.status = 'presente' THEN 1 ELSE 0 END) AS presencas,
                SUM(CASE WHEN pr.status = 'ausente' AND pr.ausencia_tipo = 'justificada' THEN 1 ELSE 0 END) AS just,
                SUM(CASE WHEN pr.status = 'ausente' AND COALESCE(pr.ausencia_tipo, 'injustificada') <> 'justificada' THEN 1 ELSE 0 END) AS injust,
                COUNT(pr.id) AS total_reg
            FROM grupos_familiares gf
            LEFT JOIN grupo_membros gm ON gm.grupo_familiar_id = gf.id
            LEFT JOIN reunioes r ON r.grupo_familiar_id = gf.id
                AND r.data BETWEEN :di AND :df
            LEFT JOIN presencas pr ON pr.reuniao_id = r.id
            WHERE gf.ativo = 1
            GROUP BY gf.id, gf.nome
            ORDER BY gf.nome ASC
        ");
        $stmtPorGf->execute([
            ':di' => $dataInicial,
            ':df' => $dataFinal,
        ]);
        $porGf = $stmtPorGf->fetchAll(PDO::FETCH_ASSOC);

        foreach ($porGf as &$grupo) {
            $grupo['taxa_presenca'] = self::percentual((int) ($grupo['presencas'] ?? 0), (int) ($grupo['total_reg'] ?? 0));
        }
        unset($grupo);

        return [
            'tipo' => 'mapeamento_assiduidade',
            'titulo' => 'Mapeamento de Assiduidade Global',
            'periodo' => [$dataInicial, $dataFinal],
            'kpis' => [
                'total_membros' => $totalMembros,
                'total_presencas' => $totalPresencas,
                'faltas_justificadas' => $faltasJustificadas,
                'faltas_injustificadas' => $faltasInjustificadas,
                'total_registros' => $totalRegistros,
                'taxa_presenca' => self::percentual($totalPresencas, $totalRegistros),
            ],
            'mensal' => $mensal,
            'por_gf' => $porGf,
        ];
    }

    public static function termometroSobrecarga(string $dataInicial, string $dataFinal): array
    {
        $stmt = self::connection()->prepare("
            SELECT
                gf.id AS gf_id,
                gf.nome AS gf_nome,
                COALESCE(
                    (
                        SELECT GROUP_CONCAT(p.nome, ', ')
                        FROM grupo_lideres gl
                        INNER JOIN pessoas p ON p.id = gl.pessoa_id
                        WHERE gl.grupo_familiar_id = gf.id
                          AND p.ativo = 1
                    ),
                    'Sem lider'
                ) AS lider_nome,
                COUNT(DISTINCT gm.pessoa_id) AS qtd_membros,
                COUNT(DISTINCT r.id) AS qtd_reunioes,
                SUM(CASE WHEN pr.status = 'ausente' AND COALESCE(pr.ausencia_tipo, 'injustificada') <> 'justificada' THEN 1 ELSE 0 END) AS faltas_injust,
                SUM(CASE WHEN pr.status = 'ausente' AND pr.ausencia_tipo = 'justificada' THEN 1 ELSE 0 END) AS faltas_just,
                COUNT(pr.id) AS total_reg
            FROM grupos_familiares gf
            LEFT JOIN grupo_membros gm ON gm.grupo_familiar_id = gf.id
            LEFT JOIN reunioes r ON r.grupo_familiar_id = gf.id
                AND r.data BETWEEN :di AND :df
            LEFT JOIN presencas pr ON pr.reuniao_id = r.id
            WHERE gf.ativo = 1
            GROUP BY gf.id, gf.nome
            ORDER BY gf.nome ASC
        ");
        $stmt->execute([
            ':di' => $dataInicial,
            ':df' => $dataFinal,
        ]);
        $lideres = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($lideres as &$lider) {
            $qtdMembros = (int) ($lider['qtd_membros'] ?? 0);
            $faltasInjust = (int) ($lider['faltas_injust'] ?? 0);
            $score = ($qtdMembros * 0.4) + ($faltasInjust * 0.6);

            $lider['score_sobrecarga'] = round($score, 1);
            $lider['nivel'] = $score > 12 ? 'alto' : ($score > 6 ? 'medio' : 'baixo');
        }
        unset($lider);

        usort($lideres, static function (array $a, array $b): int {
            return (float) ($b['score_sobrecarga'] ?? 0) <=> (float) ($a['score_sobrecarga'] ?? 0);
        });

        return [
            'tipo' => 'termometro_sobrecarga',
            'titulo' => 'Termometro de Sobrecarga',
            'periodo' => [$dataInicial, $dataFinal],
            'lideres' => $lideres,
        ];
    }

    public static function engajamentoVisitantes(string $dataInicial, string $dataFinal): array
    {
        $stmt = self::connection()->prepare("
            SELECT
                p.id AS pessoa_id,
                p.nome AS membro_nome,
                gf.nome AS gf_nome,
                COALESCE(
                    (
                        SELECT GROUP_CONCAT(pl.nome, ', ')
                        FROM grupo_lideres gl
                        INNER JOIN pessoas pl ON pl.id = gl.pessoa_id
                        WHERE gl.grupo_familiar_id = gf.id
                          AND pl.ativo = 1
                    ),
                    'Sem lider'
                ) AS lider_nome,
                MIN(r.data) AS primeira_visita,
                MAX(r.data) AS ultima_visita,
                COUNT(pr.id) AS qtd_visitas,
                p.ativo AS membro_ativo
            FROM presencas pr
            INNER JOIN pessoas p ON p.id = pr.pessoa_id
            INNER JOIN reunioes r ON r.id = pr.reuniao_id
            INNER JOIN grupos_familiares gf ON gf.id = r.grupo_familiar_id
            LEFT JOIN pedidos_oracao po ON po.reuniao_id = r.id
                AND po.pessoa_id = p.id
            WHERE r.data BETWEEN :di AND :df
              AND (
                    LOWER(COALESCE(po.pedido, '')) LIKE '%visitante%'
                 OR LOWER(COALESCE(pr.justificativa_ausencia, '')) LIKE '%visitante%'
                 OR LOWER(COALESCE(r.observacoes, '')) LIKE '%visitante%'
              )
            GROUP BY p.id, gf.id
            ORDER BY qtd_visitas DESC, membro_nome ASC
        ");
        $stmt->execute([
            ':di' => $dataInicial,
            ':df' => $dataFinal,
        ]);
        $visitantes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $total = count($visitantes);
        $retornaram = count(array_filter($visitantes, static fn(array $visitante): bool => (int) ($visitante['qtd_visitas'] ?? 0) > 1));
        $convertidos = count(array_filter($visitantes, static fn(array $visitante): bool => (int) ($visitante['membro_ativo'] ?? 0) === 1));

        return [
            'tipo' => 'engajamento_visitantes',
            'titulo' => 'Engajamento de Visitantes',
            'periodo' => [$dataInicial, $dataFinal],
            'visitantes' => $visitantes,
            'total' => $total,
            'convertidos' => $convertidos,
            'taxa_retorno' => $total > 0 ? round(($retornaram / $total) * 100, 1) : 0.0,
            'taxa_conversao' => $total > 0 ? round(($convertidos / $total) * 100, 1) : 0.0,
        ];
    }

    public static function raioXVulnerabilidade(string $dataInicial, string $dataFinal, int $gfId): array
    {
        $gf = self::buscarInfoGrupoFamiliar($gfId);

        $stmt = self::connection()->prepare("
            SELECT
                p.id AS pessoa_id,
                p.nome AS membro_nome,
                COALESCE(NULLIF(TRIM(p.telefone_movel), ''), NULLIF(TRIM(p.telefone_fixo), ''), '-') AS contato,
                SUM(CASE WHEN pr.status = 'ausente' THEN 1 ELSE 0 END) AS total_ausencias,
                SUM(CASE WHEN pr.status = 'ausente' AND pr.ausencia_tipo = 'justificada' THEN 1 ELSE 0 END) AS total_just,
                COUNT(pr.id) AS total_reunioes,
                GROUP_CONCAT(
                    CASE
                        WHEN pr.status = 'ausente' AND COALESCE(pr.justificativa_ausencia, '') <> '' THEN pr.justificativa_ausencia
                        ELSE NULL
                    END,
                    ' | '
                ) AS justificativas
            FROM presencas pr
            INNER JOIN reunioes r ON r.id = pr.reuniao_id
            INNER JOIN pessoas p ON p.id = pr.pessoa_id
            WHERE r.grupo_familiar_id = :gf_id
              AND r.data BETWEEN :di AND :df
            GROUP BY p.id, p.nome
            HAVING total_ausencias >= 2
            ORDER BY total_ausencias DESC, membro_nome ASC
        ");
        $stmt->execute([
            ':gf_id' => $gfId,
            ':di' => $dataInicial,
            ':df' => $dataFinal,
        ]);
        $membros = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($membros as &$membro) {
            $taxa = self::percentual((int) ($membro['total_ausencias'] ?? 0), (int) ($membro['total_reunioes'] ?? 0));
            $membro['taxa_ausencia'] = $taxa;
            $membro['nivel_alerta'] = $taxa >= 50 ? 'urgente' : ($taxa >= 30 ? 'atencao' : 'observacao');
        }
        unset($membro);

        return [
            'tipo' => 'raio_x_vulnerabilidade',
            'titulo' => 'Raio-X de Vulnerabilidade Pastoral',
            'periodo' => [$dataInicial, $dataFinal],
            'gf' => $gf,
            'membros' => $membros,
        ];
    }

    public static function historicoPedidosOracao(string $dataInicial, string $dataFinal, int $gfId): array
    {
        $gf = self::buscarInfoGrupoFamiliar($gfId);

        $stmt = self::connection()->prepare("
            SELECT
                r.data AS data_reuniao,
                p.nome AS membro_nome,
                po.pedido AS observacao,
                COALESCE(pr.status, 'nao_informado') AS status
            FROM pedidos_oracao po
            INNER JOIN reunioes r ON r.id = po.reuniao_id
            INNER JOIN pessoas p ON p.id = po.pessoa_id
            LEFT JOIN presencas pr ON pr.reuniao_id = po.reuniao_id
                AND pr.pessoa_id = po.pessoa_id
            WHERE r.grupo_familiar_id = :gf_id
              AND r.data BETWEEN :di AND :df
              AND TRIM(COALESCE(po.pedido, '')) <> ''
            ORDER BY r.data ASC, p.nome ASC
        ");
        $stmt->execute([
            ':gf_id' => $gfId,
            ':di' => $dataInicial,
            ':df' => $dataFinal,
        ]);
        $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'tipo' => 'historico_oracao',
            'titulo' => 'Historico de Pedidos de Oracao',
            'periodo' => [$dataInicial, $dataFinal],
            'gf' => $gf,
            'pedidos' => $pedidos,
        ];
    }

    public static function desempenhoPontualidade(string $dataInicial, string $dataFinal, int $gfId): array
    {
        $gf = self::buscarInfoGrupoFamiliar($gfId);
        $temPontualidade = self::tabelaTemColuna('presencas', 'pontualidade');
        $campoPontualidade = $temPontualidade
            ? "COALESCE(NULLIF(pr.pontualidade, ''), NULLIF(pr.presente_tempo, ''), 'no_horario')"
            : "COALESCE(NULLIF(pr.presente_tempo, ''), 'no_horario')";

        $sql = "
            SELECT
                p.id AS pessoa_id,
                p.nome AS membro_nome,
                COUNT(pr.id) AS total_reunioes,
                SUM(CASE WHEN pr.status = 'presente' THEN 1 ELSE 0 END) AS presencas,
                SUM(CASE WHEN pr.status = 'presente' AND {$campoPontualidade} = 'no_horario' THEN 1 ELSE 0 END) AS no_horario,
                SUM(CASE WHEN pr.status = 'presente' AND {$campoPontualidade} = 'atrasado' THEN 1 ELSE 0 END) AS atrasado,
                SUM(CASE WHEN pr.status = 'ausente' THEN 1 ELSE 0 END) AS ausencias,
                SUM(CASE WHEN pr.status = 'ausente' AND pr.ausencia_tipo = 'justificada' THEN 1 ELSE 0 END) AS just
            FROM presencas pr
            INNER JOIN pessoas p ON p.id = pr.pessoa_id
            INNER JOIN reunioes r ON r.id = pr.reuniao_id
            WHERE r.grupo_familiar_id = :gf_id
              AND r.data BETWEEN :di AND :df
            GROUP BY p.id, p.nome
            ORDER BY presencas DESC, membro_nome ASC
        ";
        $stmt = self::connection()->prepare($sql);
        $stmt->execute([
            ':gf_id' => $gfId,
            ':di' => $dataInicial,
            ':df' => $dataFinal,
        ]);
        $membros = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($membros as &$membro) {
            $membro['taxa_presenca'] = self::percentual((int) ($membro['presencas'] ?? 0), (int) ($membro['total_reunioes'] ?? 0));
            $membro['taxa_pontualidade'] = self::percentual((int) ($membro['no_horario'] ?? 0), (int) ($membro['presencas'] ?? 0));
        }
        unset($membro);

        return [
            'tipo' => 'pontualidade_frequencia',
            'titulo' => 'Pontualidade e Frequencia',
            'periodo' => [$dataInicial, $dataFinal],
            'gf' => $gf,
            'membros' => $membros,
        ];
    }

    public static function listarGruposFamiliares(): array
    {
        $stmt = self::connection()->query("
            SELECT
                gf.id,
                gf.nome,
                COALESCE(
                    (
                        SELECT GROUP_CONCAT(p.nome, ', ')
                        FROM grupo_lideres gl
                        INNER JOIN pessoas p ON p.id = gl.pessoa_id
                        WHERE gl.grupo_familiar_id = gf.id
                          AND p.ativo = 1
                    ),
                    'Sem lider'
                ) AS lider_nome
            FROM grupos_familiares gf
            WHERE gf.ativo = 1
            ORDER BY gf.nome ASC
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
