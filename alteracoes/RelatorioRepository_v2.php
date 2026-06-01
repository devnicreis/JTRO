<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;

class RelatorioRepository
{
    // ══════════════════════════════════════════════════════════════
    // RELATÓRIOS GERAIS
    // ══════════════════════════════════════════════════════════════

    /**
     * Mapeamento de Assiduidade Global
     * Retorna KPIs, dados mensais com tendência, destaques e ranking por GF.
     */
    public static function mapeamentoAssiduidadeGlobal(string $dataInicial, string $dataFinal): array
    {
        $pdo = Database::getInstance();

        // ── KPIs globais ──
        $kpis = $pdo->prepare("
            SELECT
                (SELECT COUNT(*) FROM pessoas WHERE ativo = 1)                AS total_membros,
                SUM(CASE WHEN pr.status = 'presente' THEN 1 ELSE 0 END)      AS total_presencas,
                SUM(CASE WHEN pr.status = 'falta_justificada' THEN 1 ELSE 0 END) AS faltas_just,
                SUM(CASE WHEN pr.status = 'ausente' THEN 1 ELSE 0 END)       AS faltas_injust,
                COUNT(DISTINCT r.id)                                          AS total_reunioes,
                COUNT(pr.id)                                                  AS total_registros
            FROM reunioes r
            LEFT JOIN presencas pr ON pr.reuniao_id = r.id
            WHERE r.data_reuniao BETWEEN :di AND :df
        ");
        $kpis->execute([':di' => $dataInicial, ':df' => $dataFinal]);
        $kpisData = $kpis->fetch(PDO::FETCH_ASSOC);

        $totalReg = (int)($kpisData['total_registros'] ?? 0);
        $kpisData['taxa_presenca'] = $totalReg > 0
            ? round(($kpisData['total_presencas'] / $totalReg) * 100, 1)
            : 0;

        // ── Dados mensais (com tendência) ──
        $mensal = $pdo->prepare("
            SELECT
                strftime('%Y-%m', r.data_reuniao)                                 AS mes,
                SUM(CASE WHEN pr.status = 'presente' THEN 1 ELSE 0 END)           AS presencas,
                SUM(CASE WHEN pr.status = 'falta_justificada' THEN 1 ELSE 0 END)  AS just,
                SUM(CASE WHEN pr.status = 'ausente' THEN 1 ELSE 0 END)            AS injust,
                COUNT(pr.id)                                                       AS total
            FROM reunioes r
            JOIN presencas pr ON pr.reuniao_id = r.id
            WHERE r.data_reuniao BETWEEN :di AND :df
            GROUP BY mes
            ORDER BY mes ASC
        ");
        $mensal->execute([':di' => $dataInicial, ':df' => $dataFinal]);
        $mensalData = $mensal->fetchAll(PDO::FETCH_ASSOC);

        // Calcular taxa e tendência
        $taxaAnterior = null;
        foreach ($mensalData as &$m) {
            $total = max(1, (int)$m['total']);
            $taxa = round($m['presencas'] / $total * 100, 1);
            $m['taxa_presenca'] = $taxa;
            $m['tendencia'] = $taxaAnterior !== null ? round($taxa - $taxaAnterior, 1) : null;
            $taxaAnterior = $taxa;
        }
        unset($m);

        // ── Assiduidade por GF ──
        $porGF = $pdo->prepare("
            SELECT
                gf.id                                                              AS gf_id,
                gf.nome                                                            AS gf_nome,
                lider.nome                                                         AS lider_nome,
                COUNT(DISTINCT m.pessoa_id)                                        AS qtd_membros,
                COUNT(DISTINCT r.id)                                               AS qtd_reunioes,
                SUM(CASE WHEN pr.status = 'presente' THEN 1 ELSE 0 END)           AS presencas,
                SUM(CASE WHEN pr.status = 'falta_justificada' THEN 1 ELSE 0 END)  AS just,
                SUM(CASE WHEN pr.status = 'ausente' THEN 1 ELSE 0 END)            AS injust,
                COUNT(pr.id)                                                       AS total_reg
            FROM grupos_familiares gf
            LEFT JOIN pessoas lider ON lider.id = gf.lider_id
            LEFT JOIN membros_gf m ON m.grupo_familiar_id = gf.id
            LEFT JOIN reunioes r ON r.grupo_familiar_id = gf.id
                AND r.data_reuniao BETWEEN :di AND :df
            LEFT JOIN presencas pr ON pr.reuniao_id = r.id
            GROUP BY gf.id
            ORDER BY presencas DESC
        ");
        $porGF->execute([':di' => $dataInicial, ':df' => $dataFinal]);
        $porGFData = $porGF->fetchAll(PDO::FETCH_ASSOC);

        foreach ($porGFData as &$row) {
            $row['taxa_presenca'] = $row['total_reg'] > 0
                ? round(($row['presencas'] / $row['total_reg']) * 100, 1)
                : 0;
        }
        unset($row);

        // Top 3 melhores e piores
        $rankOrdenado = $porGFData;
        usort($rankOrdenado, fn($a, $b) => $b['taxa_presenca'] <=> $a['taxa_presenca']);
        $top3Melhores = array_slice($rankOrdenado, 0, 3);
        $top3Piores   = array_slice(array_reverse($rankOrdenado), 0, 3);

        return [
            'tipo'        => 'mapeamento_assiduidade',
            'titulo'      => 'Mapeamento de Assiduidade Global',
            'periodo'     => [$dataInicial, $dataFinal],
            'kpis'        => $kpisData,
            'mensal'      => $mensalData,
            'por_gf'      => $porGFData,
            'top_melhores' => $top3Melhores,
            'top_piores'   => $top3Piores,
        ];
    }

    /**
     * Termômetro de Sobrecarga (Risco de Burnout)
     * Score = (membros × 0,4) + (faltas_injust × 0,6)
     * Inclui média de membros por reunião e tempo de liderança.
     */
    public static function termometroSobrecarga(string $dataInicial, string $dataFinal): array
    {
        $pdo = Database::getInstance();

        $stmt = $pdo->prepare("
            SELECT
                lider.nome                                                          AS lider_nome,
                lider.contato                                                       AS lider_contato,
                gf.nome                                                             AS gf_nome,
                gf.data_inicio                                                      AS gf_data_inicio,
                COUNT(DISTINCT m.pessoa_id)                                         AS qtd_membros,
                COUNT(DISTINCT r.id)                                                AS qtd_reunioes,
                SUM(CASE WHEN pr.status = 'presente' THEN 1 ELSE 0 END)            AS presencas,
                SUM(CASE WHEN pr.status = 'ausente' THEN 1 ELSE 0 END)             AS faltas_injust,
                SUM(CASE WHEN pr.status = 'falta_justificada' THEN 1 ELSE 0 END)   AS faltas_just,
                COUNT(pr.id)                                                        AS total_reg
            FROM grupos_familiares gf
            JOIN pessoas lider ON lider.id = gf.lider_id
            LEFT JOIN membros_gf m ON m.grupo_familiar_id = gf.id
            LEFT JOIN reunioes r ON r.grupo_familiar_id = gf.id
                AND r.data_reuniao BETWEEN :di AND :df
            LEFT JOIN presencas pr ON pr.reuniao_id = r.id
            WHERE lider.ativo = 1
            GROUP BY gf.id
            ORDER BY qtd_membros DESC
        ");
        $stmt->execute([':di' => $dataInicial, ':df' => $dataFinal]);
        $lideres = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($lideres as &$row) {
            // Score
            $score = ($row['qtd_membros'] * 0.4) + ($row['faltas_injust'] * 0.6);
            $row['score_sobrecarga'] = round($score, 1);
            $row['nivel'] = $score > 12 ? 'alto' : ($score > 6 ? 'medio' : 'baixo');

            // Média presentes por reunião
            $row['media_presentes_reuniao'] = $row['qtd_reunioes'] > 0
                ? round($row['presencas'] / $row['qtd_reunioes'], 1)
                : 0;

            // Tempo de liderança (em meses)
            if (!empty($row['gf_data_inicio'])) {
                $inicio = new \DateTime($row['gf_data_inicio']);
                $hoje   = new \DateTime();
                $diff   = $inicio->diff($hoje);
                $meses  = $diff->y * 12 + $diff->m;
                $row['tempo_lideranca'] = $meses >= 12
                    ? floor($meses / 12) . ' ano' . (floor($meses / 12) > 1 ? 's' : '')
                    : $meses . ' mês' . ($meses > 1 ? 'es' : '');
            } else {
                $row['tempo_lideranca'] = '–';
            }
        }
        unset($row);

        usort($lideres, fn($a, $b) => $b['score_sobrecarga'] <=> $a['score_sobrecarga']);

        return [
            'tipo'    => 'termometro_sobrecarga',
            'titulo'  => 'Termômetro de Sobrecarga (Risco de Burnout)',
            'periodo' => [$dataInicial, $dataFinal],
            'lideres' => $lideres,
        ];
    }

    // ══════════════════════════════════════════════════════════════
    // RELATÓRIO UNIFICADO DE GF
    // ══════════════════════════════════════════════════════════════

    /**
     * Diagnóstico Completo do Grupo Familiar
     * Combina: saúde geral do GF, reuniões do período com alertas de anomalia,
     * mapa de frequência individual, vulnerabilidade pastoral e pontualidade.
     */
    public static function diagnosticoGF(string $dataInicial, string $dataFinal, int $gfId): array
    {
        $pdo = Database::getInstance();

        // ── Informações do GF ──
        $gfStmt = $pdo->prepare("
            SELECT
                gf.id,
                gf.nome                  AS gf_nome,
                gf.local_padrao,
                gf.horario_padrao,
                gf.item_celeiro,
                gf.domingo_oracao_culto,
                gf.data_inicio,
                lider.nome               AS lider_nome,
                lider.contato            AS lider_contato
            FROM grupos_familiares gf
            JOIN pessoas lider ON lider.id = gf.lider_id
            WHERE gf.id = :gf_id
        ");
        $gfStmt->execute([':gf_id' => $gfId]);
        $gfInfo = $gfStmt->fetch(PDO::FETCH_ASSOC);

        // ── KPIs de saúde do GF ──
        $kpiStmt = $pdo->prepare("
            SELECT
                COUNT(DISTINCT m.pessoa_id)                                         AS qtd_membros,
                COUNT(DISTINCT r.id)                                                AS qtd_reunioes,
                SUM(CASE WHEN pr.status = 'presente' THEN 1 ELSE 0 END)            AS presencas,
                SUM(CASE WHEN pr.status = 'falta_justificada' THEN 1 ELSE 0 END)   AS faltas_just,
                SUM(CASE WHEN pr.status = 'ausente' THEN 1 ELSE 0 END)             AS faltas_injust,
                COUNT(pr.id)                                                        AS total_reg
            FROM membros_gf m
            LEFT JOIN reunioes r ON r.grupo_familiar_id = :gf_id
                AND r.data_reuniao BETWEEN :di AND :df
            LEFT JOIN presencas pr ON pr.reuniao_id = r.id AND pr.pessoa_id = m.pessoa_id
            WHERE m.grupo_familiar_id = :gf_id2
        ");
        $kpiStmt->execute([':gf_id' => $gfId, ':di' => $dataInicial, ':df' => $dataFinal, ':gf_id2' => $gfId]);
        $kpis = $kpiStmt->fetch(PDO::FETCH_ASSOC);
        $kpis['taxa_presenca'] = $kpis['total_reg'] > 0
            ? round($kpis['presencas'] / $kpis['total_reg'] * 100, 1)
            : 0;

        // ── Membros novos vs. saídas no período (crescimento) ──
        $crescStmt = $pdo->prepare("
            SELECT
                SUM(CASE WHEN m.data_entrada BETWEEN :di AND :df THEN 1 ELSE 0 END) AS novos_membros,
                SUM(CASE WHEN m.data_saida BETWEEN :di AND :df THEN 1 ELSE 0 END)   AS saidas
            FROM membros_gf m
            WHERE m.grupo_familiar_id = :gf_id
        ");
        $crescStmt->execute([':di' => $dataInicial, ':df' => $dataFinal, ':gf_id' => $gfId]);
        $crescimento = $crescStmt->fetch(PDO::FETCH_ASSOC);
        $crescimento['saldo'] = ($crescimento['novos_membros'] ?? 0) - ($crescimento['saidas'] ?? 0);

        // ── Reuniões do período com alertas de anomalia ──
        $reuniaoStmt = $pdo->prepare("
            SELECT
                r.id,
                r.data_reuniao,
                r.horario     AS hora_inicio_reuniao,
                r.local       AS local_reuniao,
                COUNT(CASE WHEN pr.status = 'presente' THEN 1 END)             AS qtd_presentes,
                COUNT(CASE WHEN pr.status = 'falta_justificada' THEN 1 END)    AS qtd_just,
                COUNT(CASE WHEN pr.status = 'ausente' THEN 1 END)              AS qtd_ausentes,
                COUNT(pr.id)                                                    AS total_convocados,
                -- Anomalias
                CASE WHEN gf.local_padrao IS NOT NULL
                     AND r.local IS NOT NULL
                     AND r.local != gf.local_padrao
                     THEN 1 ELSE 0 END                                          AS local_anomalo,
                CASE WHEN gf.horario_padrao IS NOT NULL
                     AND r.horario IS NOT NULL
                     AND r.horario != gf.horario_padrao
                     THEN 1 ELSE 0 END                                          AS horario_anomalo,
                gf.local_padrao,
                gf.horario_padrao
            FROM reunioes r
            JOIN grupos_familiares gf ON gf.id = r.grupo_familiar_id
            LEFT JOIN presencas pr ON pr.reuniao_id = r.id
            WHERE r.grupo_familiar_id = :gf_id
              AND r.data_reuniao BETWEEN :di AND :df
            GROUP BY r.id
            ORDER BY r.data_reuniao ASC
        ");
        $reuniaoStmt->execute([':gf_id' => $gfId, ':di' => $dataInicial, ':df' => $dataFinal]);
        $reunioes = $reuniaoStmt->fetchAll(PDO::FETCH_ASSOC);

        // Taxa de presença por reunião
        foreach ($reunioes as &$r) {
            $r['taxa_presenca'] = $r['total_convocados'] > 0
                ? round($r['qtd_presentes'] / $r['total_convocados'] * 100, 1)
                : 0;
        }
        unset($r);

        // ── Mapa de frequência individual (membro × reunião) ──
        $membrosStmt = $pdo->prepare("
            SELECT
                p.id    AS pessoa_id,
                p.nome  AS membro_nome,
                p.contato
            FROM membros_gf m
            JOIN pessoas p ON p.id = m.pessoa_id
            WHERE m.grupo_familiar_id = :gf_id
              AND p.ativo = 1
            ORDER BY p.nome ASC
        ");
        $membrosStmt->execute([':gf_id' => $gfId]);
        $membros = $membrosStmt->fetchAll(PDO::FETCH_ASSOC);

        // Presença de cada membro em cada reunião
        $presStmt = $pdo->prepare("
            SELECT pr.pessoa_id, pr.reuniao_id, pr.status, pr.pontualidade, pr.observacao
            FROM presencas pr
            JOIN reunioes r ON r.id = pr.reuniao_id
            WHERE r.grupo_familiar_id = :gf_id
              AND r.data_reuniao BETWEEN :di AND :df
        ");
        $presStmt->execute([':gf_id' => $gfId, ':di' => $dataInicial, ':df' => $dataFinal]);
        $presencas = $presStmt->fetchAll(PDO::FETCH_ASSOC);

        // Indexar por pessoa_id → reuniao_id
        $mapaPresenca = [];
        foreach ($presencas as $pr) {
            $mapaPresenca[$pr['pessoa_id']][$pr['reuniao_id']] = $pr;
        }

        // Montar dados por membro (totais + mapa)
        $reuniaoIds = array_column($reunioes, 'id');
        foreach ($membros as &$mb) {
            $pid = $mb['pessoa_id'];
            $mb['mapa'] = [];
            $totPresenca = 0;
            $totNoHorario = 0;
            $totAtrasado = 0;
            $totalConvoc = count($reuniaoIds);
            $ausenciasConsec = 0;
            $maxConsec = 0;
            $obsNotaveis = [];

            foreach ($reuniaoIds as $rid) {
                $pr = $mapaPresenca[$pid][$rid] ?? null;
                if ($pr) {
                    $mb['mapa'][$rid] = $pr['status'];
                    if ($pr['status'] === 'presente') {
                        $totPresenca++;
                        $ausenciasConsec = 0;
                        if (($pr['pontualidade'] ?? '') === 'no_horario') $totNoHorario++;
                        if (($pr['pontualidade'] ?? '') === 'atrasado')   $totAtrasado++;
                    } else {
                        $ausenciasConsec++;
                        if ($ausenciasConsec > $maxConsec) $maxConsec = $ausenciasConsec;
                    }
                    if (!empty($pr['observacao'])) {
                        $obsNotaveis[] = $pr['observacao'];
                    }
                } else {
                    $mb['mapa'][$rid] = null;
                }
            }

            $mb['total_reunioes']  = $totalConvoc;
            $mb['presencas']       = $totPresenca;
            $mb['no_horario']      = $totNoHorario;
            $mb['atrasado']        = $totAtrasado;
            $mb['ausencias']       = $totalConvoc - $totPresenca;
            $mb['max_consec']      = $maxConsec;
            $mb['taxa_presenca']   = $totalConvoc > 0 ? round($totPresenca / $totalConvoc * 100, 1) : 0;
            $mb['taxa_pontualidade'] = $totPresenca > 0 ? round($totNoHorario / $totPresenca * 100, 1) : 0;
            $mb['observacoes']     = implode(' | ', array_unique($obsNotaveis));

            // Nível de alerta de vulnerabilidade
            $taxaAus = $totalConvoc > 0 ? ($mb['ausencias'] / $totalConvoc) : 0;
            $mb['nivel_alerta'] = $taxaAus >= 0.5 ? 'urgente' : ($taxaAus >= 0.3 ? 'atencao' : 'normal');
        }
        unset($mb);

        // Membros vulneráveis (ausência ≥ 2 e nível != normal)
        $vulneraveis = array_filter($membros, fn($m) => $m['nivel_alerta'] !== 'normal' && $m['ausencias'] >= 2);
        usort($vulneraveis, fn($a, $b) => $b['ausencias'] <=> $a['ausencias']);
        $vulneraveis = array_values($vulneraveis);

        return [
            'tipo'        => 'diagnostico_gf',
            'titulo'      => 'Diagnóstico Completo do GF: ' . ($gfInfo['gf_nome'] ?? ''),
            'periodo'     => [$dataInicial, $dataFinal],
            'gf'          => $gfInfo,
            'kpis'        => $kpis,
            'crescimento' => $crescimento,
            'reunioes'    => $reunioes,
            'membros'     => $membros,
            'vulneraveis' => $vulneraveis,
        ];
    }

    // ══════════════════════════════════════════════════════════════
    // HELPERS
    // ══════════════════════════════════════════════════════════════

    /** Lista todos os GFs ativos para o <select> */
    public static function listarGruposFamiliares(): array
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->query("
            SELECT gf.id, gf.nome, p.nome AS lider_nome
            FROM grupos_familiares gf
            JOIN pessoas p ON p.id = gf.lider_id
            ORDER BY gf.nome ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
