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

    private static function contarLideresNoGrupo(int $grupoId): int
    {
        $stmt = self::connection()->prepare("
            SELECT COUNT(DISTINCT pe.id)
            FROM grupo_membros gm
            INNER JOIN pessoas pe ON pe.id = gm.pessoa_id
            WHERE gm.grupo_familiar_id = :grupo_id
              AND pe.ativo = 1
              AND (COALESCE(pe.lider_grupo_familiar, 0) = 1 OR COALESCE(pe.lider_departamento, 0) = 1)
        ");
        $stmt->execute([':grupo_id' => $grupoId]);

        return (int) ($stmt->fetchColumn() ?: 0);
    }

    private static function contarFilhosDoGrupo(int $grupoId, int $idadeMaxima = 9): int
    {
        $stmt = self::connection()->prepare("
            WITH integrantes AS (
                SELECT gm.pessoa_id
                FROM grupo_membros gm
                WHERE gm.grupo_familiar_id = :grupo_id
                UNION
                SELECT gl.pessoa_id
                FROM grupo_lideres gl
                WHERE gl.grupo_familiar_id = :grupo_id
            )
            SELECT COUNT(DISTINCT filho.id)
            FROM pessoas filho
            WHERE filho.ativo = 1
              AND filho.data_nascimento IS NOT NULL
              AND CAST((julianday('now', 'localtime') - julianday(filho.data_nascimento)) / 365.2425 AS INTEGER)
                    BETWEEN 0 AND :idade_maxima
              AND (
                    filho.responsavel_1_pessoa_id IN (SELECT pessoa_id FROM integrantes)
                 OR filho.responsavel_2_pessoa_id IN (SELECT pessoa_id FROM integrantes)
                 OR EXISTS (
                        SELECT 1
                        FROM integrantes i
                        INNER JOIN pessoas responsavel ON responsavel.id = i.pessoa_id
                        WHERE responsavel.cpf = filho.responsavel_1_cpf
                    )
                 OR EXISTS (
                        SELECT 1
                        FROM integrantes i
                        INNER JOIN pessoas responsavel ON responsavel.id = i.pessoa_id
                        WHERE responsavel.cpf = filho.responsavel_2_cpf
                    )
              )
        ");
        $stmt->execute([
            ':grupo_id' => $grupoId,
            ':idade_maxima' => max(0, $idadeMaxima),
        ]);

        return (int) ($stmt->fetchColumn() ?: 0);
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

    private static function nomeLideresSql(string $aliasGf = 'gf'): string
    {
        return "COALESCE((
            SELECT GROUP_CONCAT(p.nome, ', ')
            FROM grupo_lideres gl
            INNER JOIN pessoas p ON p.id = gl.pessoa_id
            WHERE gl.grupo_familiar_id = {$aliasGf}.id
              AND p.ativo = 1
        ), 'Sem lider')";
    }

    public static function mapeamentoAssiduidadeGlobal(string $dataInicial, string $dataFinal): array
    {
        $conn = self::connection();

        $stmtMembros = $conn->query("SELECT COUNT(*) FROM pessoas WHERE ativo = 1");
        $totalMembros = (int) ($stmtMembros->fetchColumn() ?: 0);

        $stmtKpi = $conn->prepare("
            SELECT
                SUM(CASE WHEN pr.status = 'presente' THEN 1 ELSE 0 END) AS total_presencas,
                SUM(CASE WHEN pr.status = 'ausente' AND pr.ausencia_tipo = 'justificada' THEN 1 ELSE 0 END) AS faltas_just,
                SUM(CASE WHEN pr.status = 'ausente' AND COALESCE(pr.ausencia_tipo, 'injustificada') <> 'justificada' THEN 1 ELSE 0 END) AS faltas_injust,
                COUNT(DISTINCT r.id) AS total_reunioes,
                COUNT(pr.id) AS total_registros
            FROM reunioes r
            LEFT JOIN presencas pr ON pr.reuniao_id = r.id
            WHERE r.data BETWEEN :di AND :df
        ");
        $stmtKpi->execute([':di' => $dataInicial, ':df' => $dataFinal]);
        $kpi = $stmtKpi->fetch(PDO::FETCH_ASSOC) ?: [];

        $totalRegistros = (int) ($kpi['total_registros'] ?? 0);
        $totalPresencas = (int) ($kpi['total_presencas'] ?? 0);
        $faltasJust = (int) ($kpi['faltas_just'] ?? 0);
        $faltasInjust = (int) ($kpi['faltas_injust'] ?? 0);

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
        $stmtMensal->execute([':di' => $dataInicial, ':df' => $dataFinal]);
        $mensal = $stmtMensal->fetchAll(PDO::FETCH_ASSOC);

        $taxaAnterior = null;
        foreach ($mensal as &$mes) {
            $total = max(1, (int) ($mes['total'] ?? 0));
            $taxa = round(((int) ($mes['presencas'] ?? 0) / $total) * 100, 1);
            $mes['taxa_presenca'] = $taxa;
            $mes['tendencia'] = $taxaAnterior !== null ? round($taxa - $taxaAnterior, 1) : null;
            $taxaAnterior = $taxa;
        }
        unset($mes);

        $stmtPorGf = $conn->prepare("
            WITH membros AS (
                SELECT
                    gm.grupo_familiar_id AS gf_id,
                    COUNT(DISTINCT gm.pessoa_id) AS qtd_membros
                FROM grupo_membros gm
                INNER JOIN pessoas p ON p.id = gm.pessoa_id
                WHERE p.ativo = 1
                GROUP BY gm.grupo_familiar_id
            ),
            stats AS (
                SELECT
                    r.grupo_familiar_id AS gf_id,
                    COUNT(DISTINCT r.id) AS qtd_reunioes,
                    SUM(CASE WHEN pr.status = 'presente' THEN 1 ELSE 0 END) AS presencas,
                    SUM(CASE WHEN pr.status = 'ausente' AND pr.ausencia_tipo = 'justificada' THEN 1 ELSE 0 END) AS just,
                    SUM(CASE WHEN pr.status = 'ausente' AND COALESCE(pr.ausencia_tipo, 'injustificada') <> 'justificada' THEN 1 ELSE 0 END) AS injust,
                    COUNT(pr.id) AS total_reg
                FROM reunioes r
                LEFT JOIN presencas pr ON pr.reuniao_id = r.id
                WHERE r.data BETWEEN :di AND :df
                GROUP BY r.grupo_familiar_id
            )
            SELECT
                gf.id AS gf_id,
                gf.nome AS gf_nome,
                COALESCE(m.qtd_membros, 0) AS qtd_membros,
                COALESCE(s.qtd_reunioes, 0) AS qtd_reunioes,
                COALESCE(s.presencas, 0) AS presencas,
                COALESCE(s.just, 0) AS just,
                COALESCE(s.injust, 0) AS injust,
                COALESCE(s.total_reg, 0) AS total_reg
            FROM grupos_familiares gf
            LEFT JOIN membros m ON m.gf_id = gf.id
            LEFT JOIN stats s ON s.gf_id = gf.id
            WHERE gf.ativo = 1
            ORDER BY gf.nome ASC
        ");
        $stmtPorGf->execute([':di' => $dataInicial, ':df' => $dataFinal]);
        $porGf = $stmtPorGf->fetchAll(PDO::FETCH_ASSOC);

        foreach ($porGf as &$grupo) {
            $grupo['taxa_presenca'] = self::percentual((int) ($grupo['presencas'] ?? 0), (int) ($grupo['total_reg'] ?? 0));
        }
        unset($grupo);

        $rank = $porGf;
        usort($rank, static fn(array $a, array $b): int => (($b['taxa_presenca'] ?? 0) <=> ($a['taxa_presenca'] ?? 0)));

        return [
            'tipo' => 'mapeamento_assiduidade',
            'titulo' => 'Mapeamento de Assiduidade Global',
            'periodo' => [$dataInicial, $dataFinal],
            'kpis' => [
                'total_membros' => $totalMembros,
                'total_presencas' => $totalPresencas,
                'faltas_just' => $faltasJust,
                'faltas_injust' => $faltasInjust,
                'total_reunioes' => (int) ($kpi['total_reunioes'] ?? 0),
                'total_registros' => $totalRegistros,
                'taxa_presenca' => self::percentual($totalPresencas, $totalRegistros),
            ],
            'mensal' => $mensal,
            'por_gf' => $porGf,
            'top_melhores' => array_slice($rank, 0, 3),
            'top_piores' => array_slice(array_reverse($rank), 0, 3),
        ];
    }

    public static function termometroSobrecarga(string $dataInicial, string $dataFinal): array
    {
        $conn = self::connection();
        $stmt = $conn->prepare("
            WITH membros AS (
                SELECT
                    gm.grupo_familiar_id AS gf_id,
                    COUNT(DISTINCT gm.pessoa_id) AS qtd_membros
                FROM grupo_membros gm
                INNER JOIN pessoas p ON p.id = gm.pessoa_id
                WHERE p.ativo = 1
                GROUP BY gm.grupo_familiar_id
            ),
            stats AS (
                SELECT
                    r.grupo_familiar_id AS gf_id,
                    COUNT(DISTINCT r.id) AS qtd_reunioes,
                    SUM(CASE WHEN pr.status = 'presente' THEN 1 ELSE 0 END) AS presencas,
                    SUM(CASE WHEN pr.status = 'ausente' AND pr.ausencia_tipo = 'justificada' THEN 1 ELSE 0 END) AS faltas_just,
                    SUM(CASE WHEN pr.status = 'ausente' AND COALESCE(pr.ausencia_tipo, 'injustificada') <> 'justificada' THEN 1 ELSE 0 END) AS faltas_injust,
                    COUNT(pr.id) AS total_reg
                FROM reunioes r
                LEFT JOIN presencas pr ON pr.reuniao_id = r.id
                WHERE r.data BETWEEN :di AND :df
                GROUP BY r.grupo_familiar_id
            )
            SELECT
                gf.id AS gf_id,
                gf.nome AS gf_nome,
                COALESCE(m.qtd_membros, 0) AS qtd_membros,
                COALESCE(s.qtd_reunioes, 0) AS qtd_reunioes,
                COALESCE(s.presencas, 0) AS presencas,
                COALESCE(s.faltas_just, 0) AS faltas_just,
                COALESCE(s.faltas_injust, 0) AS faltas_injust,
                COALESCE(s.total_reg, 0) AS total_reg
            FROM grupos_familiares gf
            LEFT JOIN membros m ON m.gf_id = gf.id
            LEFT JOIN stats s ON s.gf_id = gf.id
            WHERE gf.ativo = 1
            ORDER BY gf.nome ASC
        ");
        $stmt->execute([':di' => $dataInicial, ':df' => $dataFinal]);
        $lideres = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($lideres as &$lider) {
            $qtdMembros = (int) ($lider['qtd_membros'] ?? 0);
            $faltasInjust = (int) ($lider['faltas_injust'] ?? 0);
            $qtdReunioes = (int) ($lider['qtd_reunioes'] ?? 0);
            $presencas = (int) ($lider['presencas'] ?? 0);

            $score = ($qtdMembros * 0.4) + ($faltasInjust * 0.6);
            $scoreArredondado = round($score, 1);
            $lider['score_sobrecarga'] = $scoreArredondado;
            $lider['nivel'] = $scoreArredondado > 12 ? 'alto' : ($scoreArredondado > 6 ? 'medio' : 'baixo');
            $lider['media_presentes_reuniao'] = $qtdReunioes > 0 ? round($presencas / $qtdReunioes, 1) : 0;
        }
        unset($lider);

        usort($lideres, static fn(array $a, array $b): int => (($b['score_sobrecarga'] ?? 0) <=> ($a['score_sobrecarga'] ?? 0)));

        return [
            'tipo' => 'termometro_sobrecarga',
            'titulo' => 'Termômetro de Sobrecarga',
            'periodo' => [$dataInicial, $dataFinal],
            'lideres' => $lideres,
        ];
    }

    public static function diagnosticoGF(string $dataInicial, string $dataFinal, int $gfId): array
    {
        $conn = self::connection();

        $temHorarioPadrao = self::tabelaTemColuna('grupos_familiares', 'horario_padrao');
        $temDataInicio = self::tabelaTemColuna('grupos_familiares', 'data_inicio');
        $temDataEntrada = self::tabelaTemColuna('grupo_membros', 'data_entrada');
        $temDataSaida = self::tabelaTemColuna('grupo_membros', 'data_saida');

        $selectHorarioPadrao = $temHorarioPadrao ? 'gf.horario_padrao' : 'gf.horario';
        $selectDataInicio = $temDataInicio ? 'gf.data_inicio' : 'NULL';

        $stmtGf = $conn->prepare("
            SELECT
                gf.id,
                gf.nome AS gf_nome,
                gf.local_padrao,
                {$selectHorarioPadrao} AS horario_padrao,
                gf.item_celeiro,
                gf.domingo_oracao_culto,
                {$selectDataInicio} AS data_inicio,
                " . self::nomeLideresSql('gf') . " AS lider_nome,
                '-' AS lider_contato
            FROM grupos_familiares gf
            WHERE gf.id = :gf_id
            LIMIT 1
        ");
        $stmtGf->execute([':gf_id' => $gfId]);
        $gf = $stmtGf->fetch(PDO::FETCH_ASSOC) ?: [];

        $stmtKpi = $conn->prepare("
            SELECT
                COUNT(DISTINCT gm.pessoa_id) AS qtd_membros,
                COUNT(DISTINCT r.id) AS qtd_reunioes,
                SUM(CASE WHEN pr.status = 'presente' THEN 1 ELSE 0 END) AS presencas,
                SUM(CASE WHEN pr.status = 'ausente' AND pr.ausencia_tipo = 'justificada' THEN 1 ELSE 0 END) AS faltas_just,
                SUM(CASE WHEN pr.status = 'ausente' AND COALESCE(pr.ausencia_tipo, 'injustificada') <> 'justificada' THEN 1 ELSE 0 END) AS faltas_injust,
                COUNT(pr.id) AS total_reg
            FROM grupo_membros gm
            LEFT JOIN reunioes r ON r.grupo_familiar_id = :gf_id
                AND r.data BETWEEN :di AND :df
            LEFT JOIN presencas pr ON pr.reuniao_id = r.id
                AND pr.pessoa_id = gm.pessoa_id
            WHERE gm.grupo_familiar_id = :gf_id2
        ");
        $stmtKpi->execute([
            ':gf_id' => $gfId,
            ':gf_id2' => $gfId,
            ':di' => $dataInicial,
            ':df' => $dataFinal,
        ]);
        $kpis = $stmtKpi->fetch(PDO::FETCH_ASSOC) ?: [];
        $kpis['taxa_presenca'] = self::percentual((int) ($kpis['presencas'] ?? 0), (int) ($kpis['total_reg'] ?? 0));

        $novos = 0;
        $saidas = 0;
        if ($temDataEntrada || $temDataSaida) {
            $sqlCrescimento = "SELECT ";
            $sqlCrescimento .= $temDataEntrada
                ? "SUM(CASE WHEN gm.data_entrada BETWEEN :di AND :df THEN 1 ELSE 0 END) AS novos_membros,"
                : "0 AS novos_membros,";
            $sqlCrescimento .= $temDataSaida
                ? " SUM(CASE WHEN gm.data_saida BETWEEN :di AND :df THEN 1 ELSE 0 END) AS saidas"
                : " 0 AS saidas";
            $sqlCrescimento .= " FROM grupo_membros gm WHERE gm.grupo_familiar_id = :gf_id";

            $stmtCrescimento = $conn->prepare($sqlCrescimento);
            $stmtCrescimento->execute([':di' => $dataInicial, ':df' => $dataFinal, ':gf_id' => $gfId]);
            $crescimento = $stmtCrescimento->fetch(PDO::FETCH_ASSOC) ?: [];
            $novos = (int) ($crescimento['novos_membros'] ?? 0);
            $saidas = (int) ($crescimento['saidas'] ?? 0);
        }

        $stmtReunioes = $conn->prepare("
            SELECT
                r.id,
                r.data AS data_reuniao,
                r.horario AS hora_inicio_reuniao,
                r.local AS local_reuniao,
                SUM(CASE WHEN pr.status = 'presente' THEN 1 ELSE 0 END) AS qtd_presentes,
                SUM(CASE WHEN pr.status = 'ausente' AND pr.ausencia_tipo = 'justificada' THEN 1 ELSE 0 END) AS qtd_just,
                SUM(CASE WHEN pr.status = 'ausente' AND COALESCE(pr.ausencia_tipo, 'injustificada') <> 'justificada' THEN 1 ELSE 0 END) AS qtd_ausentes,
                COUNT(pr.id) AS total_convocados,
                CASE
                    WHEN gf.local_padrao IS NOT NULL
                     AND TRIM(gf.local_padrao) <> ''
                     AND r.local IS NOT NULL
                     AND TRIM(r.local) <> ''
                     AND TRIM(r.local) <> TRIM(gf.local_padrao)
                    THEN 1 ELSE 0
                END AS local_anomalo,
                CASE
                    WHEN {$selectHorarioPadrao} IS NOT NULL
                     AND TRIM({$selectHorarioPadrao}) <> ''
                     AND r.horario IS NOT NULL
                     AND TRIM(r.horario) <> ''
                     AND TRIM(r.horario) <> TRIM({$selectHorarioPadrao})
                    THEN 1 ELSE 0
                END AS horario_anomalo,
                gf.local_padrao,
                {$selectHorarioPadrao} AS horario_padrao
            FROM reunioes r
            INNER JOIN grupos_familiares gf ON gf.id = r.grupo_familiar_id
            LEFT JOIN presencas pr ON pr.reuniao_id = r.id
            WHERE r.grupo_familiar_id = :gf_id
              AND r.data BETWEEN :di AND :df
            GROUP BY r.id
            ORDER BY r.data ASC
        ");
        $stmtReunioes->execute([':gf_id' => $gfId, ':di' => $dataInicial, ':df' => $dataFinal]);
        $reunioes = $stmtReunioes->fetchAll(PDO::FETCH_ASSOC);

        foreach ($reunioes as &$reuniao) {
            $reuniao['taxa_presenca'] = self::percentual((int) ($reuniao['qtd_presentes'] ?? 0), (int) ($reuniao['total_convocados'] ?? 0));
        }
        unset($reuniao);

        $stmtMembros = $conn->prepare("
            SELECT
                p.id AS pessoa_id,
                p.nome AS membro_nome,
                COALESCE(NULLIF(TRIM(p.telefone_movel), ''), NULLIF(TRIM(p.telefone_fixo), ''), '-') AS contato,
                COALESCE(p.lider_grupo_familiar, 0) AS lider_grupo_familiar,
                COALESCE(p.lider_departamento, 0) AS lider_departamento
            FROM grupo_membros gm
            INNER JOIN pessoas p ON p.id = gm.pessoa_id
            WHERE gm.grupo_familiar_id = :gf_id
              AND p.ativo = 1
            ORDER BY p.nome ASC
        ");
        $stmtMembros->execute([':gf_id' => $gfId]);
        $membros = $stmtMembros->fetchAll(PDO::FETCH_ASSOC);

        $lideresNoGrupo = 0;
        foreach ($membros as $membroResumo) {
            if (
                (int) ($membroResumo['lider_grupo_familiar'] ?? 0) === 1 ||
                (int) ($membroResumo['lider_departamento'] ?? 0) === 1
            ) {
                $lideresNoGrupo++;
            }
        }

        $filhosNoGrupo = self::contarFilhosDoGrupo($gfId, 9);

        $stmtPresencas = $conn->prepare("
            SELECT
                pr.pessoa_id,
                pr.reuniao_id,
                CASE
                    WHEN pr.status = 'ausente' AND pr.ausencia_tipo = 'justificada' THEN 'falta_justificada'
                    ELSE pr.status
                END AS status_rel,
                COALESCE(NULLIF(pr.pontualidade, ''), NULLIF(pr.presente_tempo, ''), 'no_horario') AS pontualidade,
                COALESCE(pr.justificativa_ausencia, '') AS observacao
            FROM presencas pr
            INNER JOIN reunioes r ON r.id = pr.reuniao_id
            WHERE r.grupo_familiar_id = :gf_id
              AND r.data BETWEEN :di AND :df
        ");
        $stmtPresencas->execute([':gf_id' => $gfId, ':di' => $dataInicial, ':df' => $dataFinal]);
        $presencas = $stmtPresencas->fetchAll(PDO::FETCH_ASSOC);

        $mapaPresenca = [];
        foreach ($presencas as $presenca) {
            $mapaPresenca[(int) $presenca['pessoa_id']][(int) $presenca['reuniao_id']] = $presenca;
        }

        $reuniaoIds = array_map(static fn(array $r): int => (int) ($r['id'] ?? 0), $reunioes);
        foreach ($membros as &$membro) {
            $pid = (int) ($membro['pessoa_id'] ?? 0);
            $membro['mapa'] = [];

            $pres = 0;
            $noHorario = 0;
            $atrasado = 0;
            $ausencias = 0;
            $maxConsec = 0;
            $consec = 0;
            $obs = [];

            foreach ($reuniaoIds as $rid) {
                $registro = $mapaPresenca[$pid][$rid] ?? null;

                if ($registro === null) {
                    $membro['mapa'][$rid] = null;
                    continue;
                }

                $status = (string) ($registro['status_rel'] ?? '');
                $membro['mapa'][$rid] = $status;

                if ($status === 'presente') {
                    $pres++;
                    $consec = 0;
                    $pontualidade = (string) ($registro['pontualidade'] ?? '');
                    if ($pontualidade === 'no_horario') {
                        $noHorario++;
                    } elseif ($pontualidade === 'atrasado') {
                        $atrasado++;
                    }
                } else {
                    $ausencias++;
                    $consec++;
                    if ($consec > $maxConsec) {
                        $maxConsec = $consec;
                    }
                }

                $observacao = trim((string) ($registro['observacao'] ?? ''));
                if ($observacao !== '') {
                    $obs[] = $observacao;
                }
            }

            $totalReunioes = count($reuniaoIds);
            $membro['total_reunioes'] = $totalReunioes;
            $membro['presencas'] = $pres;
            $membro['no_horario'] = $noHorario;
            $membro['atrasado'] = $atrasado;
            $membro['ausencias'] = $ausencias;
            $membro['max_consec'] = $maxConsec;
            $membro['taxa_presenca'] = self::percentual($pres, $totalReunioes);
            $membro['taxa_pontualidade'] = self::percentual($noHorario, $pres);
            $membro['observacoes'] = implode(' | ', array_values(array_unique($obs)));

            $taxaAusencia = $totalReunioes > 0 ? ($ausencias / $totalReunioes) : 0;
            $membro['nivel_alerta'] = $taxaAusencia >= 0.5 ? 'urgente' : ($taxaAusencia >= 0.3 ? 'atencao' : 'normal');
        }
        unset($membro);

        $vulneraveis = array_values(array_filter(
            $membros,
            static fn(array $m): bool => (($m['nivel_alerta'] ?? 'normal') !== 'normal') && ((int) ($m['ausencias'] ?? 0) >= 2)
        ));
        usort($vulneraveis, static fn(array $a, array $b): int => (($b['ausencias'] ?? 0) <=> ($a['ausencias'] ?? 0)));

        return [
            'tipo' => 'diagnostico_gf',
            'titulo' => 'Diagnóstico Completo do GF: ' . (string) ($gf['gf_nome'] ?? ''),
            'periodo' => [$dataInicial, $dataFinal],
            'gf' => $gf,
            'kpis' => $kpis,
            'indicadores' => [
                'total_reunioes' => (int) ($kpis['qtd_reunioes'] ?? 0),
                'lideres_no_gf' => $lideresNoGrupo,
                'filhos_no_gf' => $filhosNoGrupo,
            ],
            'crescimento' => [
                'novos_membros' => $novos,
                'saidas' => $saidas,
                'saldo' => $novos - $saidas,
            ],
            'reunioes' => $reunioes,
            'membros' => $membros,
            'vulneraveis' => $vulneraveis,
        ];
    }

    public static function listarGruposFamiliares(): array
    {
        $stmt = self::connection()->query("
            SELECT
                gf.id,
                gf.nome,
                " . self::nomeLideresSql('gf') . " AS lider_nome
            FROM grupos_familiares gf
            WHERE gf.ativo = 1
            ORDER BY gf.nome ASC
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
