<?php

require_once __DIR__ . '/../src/Core/Auth.php';
require_once __DIR__ . '/../src/Repositories/PessoaRepository.php';
require_once __DIR__ . '/../src/Repositories/GrupoFamiliarRepository.php';
require_once __DIR__ . '/../src/Repositories/PresencaRepository.php';
require_once __DIR__ . '/../src/Repositories/AvisoRepository.php';
require_once __DIR__ . '/../src/Services/CalendarioGfService.php';
require_once __DIR__ . '/../src/Services/DiagnosticoPresencaService.php';
require_once __DIR__ . '/../vendor/autoload.php';

date_default_timezone_set('America/Sao_Paulo');

Auth::requireLogin();
Auth::requireSenhaAtualizada();

$usuario = Auth::usuario();

$pessoaRepo = new PessoaRepository();
$grupoRepo  = new GrupoFamiliarRepository();
$presencaRepo = new PresencaRepository();
$avisoRepo  = new AvisoRepository();
$avisoRepo->sincronizarAvisosAniversarioDoDia();
$avisoRepo->sincronizarAvisosCantina();

$mensagem = '';
if (isset($_GET['senha_alterada']) && $_GET['senha_alterada'] === '1') {
    $mensagem = 'Senha alterada com sucesso.';
} elseif (isset($_GET['privacidade_aceita']) && $_GET['privacidade_aceita'] === '1') {
    $mensagem = 'Aceite de privacidade registrado com sucesso.';
}

$pageTitle = 'Tela Inicial - JTRO';

$chavesLidas    = $avisoRepo->listarChavesLidas(Auth::id());
$chavesLidasMap = array_fill_keys($chavesLidas, true);
$totalAvisos    = 0;
$gruposDoLider = $grupoRepo->listarGruposDoLider(Auth::id());

$calendario  = new CalendarioGfService();
$diagnostico = new DiagnosticoPresencaService();
$agora       = new DateTimeImmutable('now', new DateTimeZone('America/Sao_Paulo'));
$hoje        = $agora->setTime(0, 0, 0);
$saudacao    = $calendario->saudacao($agora) . ', ' . explode(' ', trim((string) $usuario['nome']))[0];

// Dados do cartão "Próxima reunião" (partials/_proxima_reuniao_gf.php) de um GF de listarGruposDoLider().
$montarProximaReuniaoGf = function (array $g) use ($calendario, $presencaRepo, $agora): array {
    $data = $calendario->proximaOcorrencia((string) ($g['dia_semana'] ?? ''), $agora);
    $ultimaReuniao = $presencaRepo->listarUltimasReunioesDoGrupo((int) $g['id'], 1)[0] ?? null;
    return [
        'grupo_id' => (int) $g['id'],
        'nome'     => $g['nome'],
        'proxima'  => $data === null ? null : [
            'data'           => $data,
            'rotulo'         => $calendario->rotuloRelativo($data, $agora),
            'dia_abreviado'  => $calendario->diaSemanaAbreviado($data),
            'mes_abreviado'  => $calendario->mesAbreviado($data),
            'data_extenso'   => $calendario->dataPorExtenso($data, false),
        ],
        'horario'             => (string) ($g['horario'] ?? ''),
        'local_padrao'        => trim((string) ($g['local_padrao'] ?? '')),
        'membros'             => (int) ($g['total_membros_ativos'] ?? 0),
        'ultima_reuniao_id'   => $ultimaReuniao !== null ? (int) $ultimaReuniao['id'] : null,
        'ultima_reuniao_data' => $ultimaReuniao['data'] ?? null,
    ];
};

if (Auth::isAdmin()) {
    // Busca base — usada para múltiplas derivações abaixo
    $todosGrupos  = $grupoRepo->listarTodos();
    $gruposAtivos = array_values(array_filter($todosGrupos, fn($g) => (int)($g['ativo'] ?? 0) === 1));

    // Líderes únicos de GFs ativos (fonte de verdade: campo lideres do grupo)
    $lideresAtivos = [];
    foreach ($gruposAtivos as $g) {
        if (!empty($g['lideres'])) {
            foreach (explode(', ', $g['lideres']) as $nomeLider) {
                $nomeLider = trim($nomeLider);
                if ($nomeLider !== '') $lideresAtivos[$nomeLider] = true;
            }
        }
    }

    // ── Situação da última reunião prevista (regras 5 e 6) ──────
    $situacao   = $presencaRepo->listarSituacaoUltimaReuniaoPrevista(null, $agora);
    $pendentes  = array_values(array_filter($situacao, fn($s) => !$s['registrada']));
    // Mais recente primeiro; GF sem dia da semana válido (sem data prevista) no fim.
    usort($pendentes, function (array $a, array $b): int {
        return [(string) $b['data_prevista'], (string) $b['horario']] <=> [(string) $a['data_prevista'], (string) $a['horario']];
    });
    foreach ($pendentes as &$pendente) {
        $dataPrevista = $pendente['data_prevista'] !== null ? new DateTimeImmutable($pendente['data_prevista']) : null;
        $pendente['dia_curto'] = $dataPrevista !== null ? $calendario->diaCurto($dataPrevista) : null;
    }
    unset($pendente);

    // ── Alertas não lidos (regra 9: mesmas chaves de avisos_lidos) ──
    $alertasGfs = [];
    foreach ($presencaRepo->buscarGruposAlarmantes() as $g) {
        if (!isset($chavesLidasMap['gf_alarmante_' . $g['id']])) {
            $alertasGfs[] = [
                'chave'      => 'gf_alarmante_' . (int) $g['id'],
                'grupo_id'   => (int) $g['id'],
                'nome'       => $g['nome'],
                'percentual' => (float) $g['resumo_presenca']['percentual_presencas'],
            ];
        }
    }
    usort($alertasGfs, fn($a, $b) => $a['percentual'] <=> $b['percentual']);

    $alertasFaltas = [];
    foreach ($presencaRepo->buscarMembrosComFaltasConsecutivasGerais(2) as $m) {
        $chave = 'faltas_' . $m['grupo_id'] . '_' . $m['pessoa_id'];
        if (!isset($chavesLidasMap[$chave])) {
            $alertasFaltas[] = [
                'chave'      => $chave,
                'grupo_id'   => (int) $m['grupo_id'],
                'grupo_nome' => $m['grupo_nome'],
                'pessoa_id'  => (int) $m['pessoa_id'],
                'nome'       => $m['nome'],
                'faltas'     => (int) $m['faltas_consecutivas'],
            ];
        }
    }

    $alertasPadrao = [];
    foreach ($presencaRepo->buscarReunioesForaDoPadrao(20) as $r) {
        if (!isset($chavesLidasMap['reuniao_fora_padrao_' . $r['id']])) {
            $alertasPadrao[] = [
                'chave'      => 'reuniao_fora_padrao_' . (int) $r['id'],
                'reuniao_id' => (int) $r['id'],
                'grupo_nome' => $r['grupo_nome'],
                'data'       => $r['data'],
                'motivo'     => trim((string) $r['motivo_alteracao']),
            ];
        }
    }

    $totalAvisos = count($alertasGfs) + count($alertasFaltas) + count($alertasPadrao);
    foreach ($avisoRepo->listarAvisosSistema(Auth::id()) as $avisoSistema) {
        $chaveAviso = $avisoSistema['chave_aviso'] ?? '';
        if ($chaveAviso !== '' && !isset($chavesLidasMap[$chaveAviso])) $totalAvisos++;
    }

    // ── Próximas reuniões (regra 7: hoje até hoje + 6) ───────────
    $limiteProximas = $hoje->modify('+6 days');
    $proximas = [];
    foreach ($gruposAtivos as $g) {
        $data = $calendario->proximaOcorrencia((string) ($g['dia_semana'] ?? ''), $agora);
        if ($data === null || $data > $limiteProximas) {
            continue;
        }
        $proximas[] = [
            'grupo_id' => (int) $g['id'],
            'nome'     => $g['nome'],
            'horario'  => (string) ($g['horario'] ?? ''),
            'membros'  => (int) ($g['total_membros'] ?? 0),
            'data'     => $data,
        ];
    }
    usort($proximas, fn($a, $b) => [$a['data'], $a['horario'], $a['nome']] <=> [$b['data'], $b['horario'], $b['nome']]);

    $maxProximas = 8;
    $diasProximas = [];
    foreach (array_slice($proximas, 0, $maxProximas) as $p) {
        $chaveDia = $p['data']->format('Y-m-d');
        if (!isset($diasProximas[$chaveDia])) {
            $rotulo = $calendario->rotuloRelativo($p['data'], $agora);
            $diaCurto = $calendario->diaCurto($p['data']);
            $diasProximas[$chaveDia] = [
                'titulo'   => in_array($rotulo, ['Hoje', 'Amanhã'], true) ? $rotulo . ' · ' . $diaCurto : ucfirst($diaCurto),
                'reunioes' => [],
            ];
        }
        $diasProximas[$chaveDia]['reunioes'][] = $p;
    }
    $excedentes = array_slice($proximas, $maxProximas);
    $rotuloExcedente = '';
    if (count($excedentes) > 0) {
        $qtd = count($excedentes);
        $rotuloExcedente = '+ ' . $qtd . ($qtd === 1 ? ' reunião' : ' reuniões');
        $datasExcedentes = array_unique(array_map(fn($p) => $p['data']->format('Y-m-d'), $excedentes));
        if (count($datasExcedentes) === 1) {
            $dataExcedente = $excedentes[0]['data'];
            if ($calendario->rotuloRelativo($dataExcedente, $agora) === 'Hoje') {
                $rotuloExcedente .= ' hoje';
            } else {
                $nomeDia = explode(',', $calendario->diaCurto($dataExcedente))[0];
                $rotuloExcedente .= ((int) $dataExcedente->format('N') >= 6 ? ' no ' : ' na ') . $nomeDia;
            }
        } else {
            $rotuloExcedente .= ' nos próximos dias';
        }
    }

    // ── Saúde dos GFs (regras 1 e 2) ────────────────────────────
    $saude = [];
    foreach ($gruposAtivos as $g) {
        $resumo = $presencaRepo->buscarResumoPresencaPorGrupo((int) $g['id']);
        $totalRegistros = (int) $resumo['total_presencas'] + (int) $resumo['total_ausencias'];
        $saude[] = [
            'grupo_id'      => (int) $g['id'],
            'nome'          => $g['nome'],
            'membros'       => (int) ($g['total_membros'] ?? 0),
            'percentual'    => (float) $resumo['percentual_presencas'],
            'classificacao' => $diagnostico->classificar((float) $resumo['percentual_presencas'], $totalRegistros),
        ];
    }
    // Menor frequência primeiro; NEUTRO (sem registros) no fim.
    usort($saude, function (array $a, array $b): int {
        $aNeutro = $a['classificacao']['codigo'] === 'neutro';
        $bNeutro = $b['classificacao']['codigo'] === 'neutro';
        return [$aNeutro, $a['percentual'], $a['nome']] <=> [$bNeutro, $b['percentual'], $b['nome']];
    });
    $saudeVisiveis = array_slice($saude, 0, 8);
    foreach ($saudeVisiveis as &$item) {
        $resumoGrupo = $presencaRepo->buscarResumoGrupo($item['grupo_id']);
        $item['ultima_reuniao'] = $resumoGrupo['ultima_data_reuniao'] ?? null;
    }
    unset($item);

    // ── Meus GFs (admin que também é líder) ─────────────────────
    $meusGfs = array_map($montarProximaReuniaoGf, $gruposDoLider);

    $painel = [
        'saudacao'     => $saudacao,
        'data_extenso' => $calendario->dataPorExtenso($agora),
        'contadores'   => [
            'pessoas'  => $pessoaRepo->contarPessoasAtivas(),
            'gfs'      => count($gruposAtivos),
            'lideres'  => count($lideresAtivos),
            'reunioes' => $presencaRepo->contarReunioes(),
        ],
        'situacao' => [
            'total'       => count($situacao),
            'registrados' => count($situacao) - count($pendentes),
            'pendentes'   => $pendentes,
        ],
        'alertas' => [
            'gfs'    => ['total' => count($alertasGfs),    'itens' => array_slice($alertasGfs, 0, 5)],
            'faltas' => ['total' => count($alertasFaltas), 'itens' => array_slice($alertasFaltas, 0, 5)],
            'padrao' => ['total' => count($alertasPadrao), 'itens' => array_slice($alertasPadrao, 0, 5)],
        ],
        'proximas' => [
            'dias'             => $diasProximas,
            'total_excedente'  => count($excedentes),
            'rotulo_excedente' => $rotuloExcedente,
        ],
        'saude' => [
            'total' => count($saude),
            'itens' => $saudeVisiveis,
        ],
        'meus_gfs' => $meusGfs,
    ];

    require_once __DIR__ . '/../src/Views/dashboard/admin.php';

} else {
    // ── Presença pendente (regras 5 e 6, só os GFs do líder) ────
    $pendencias = [];
    foreach ($presencaRepo->listarSituacaoUltimaReuniaoPrevista(Auth::id(), $agora) as $situacaoGf) {
        if ($situacaoGf['registrada']) {
            continue;
        }
        $dataPrevista = $situacaoGf['data_prevista'] !== null ? new DateTimeImmutable($situacaoGf['data_prevista']) : null;
        $pendencias[] = [
            'grupo_id'      => (int) $situacaoGf['grupo_id'],
            'nome'          => $situacaoGf['nome'],
            'data_prevista' => $situacaoGf['data_prevista'],
            'dia_curto'     => $dataPrevista !== null ? $calendario->diaCurto($dataPrevista) : null,
        ];
    }

    // ── Um bloco por GF do líder ────────────────────────────────
    $gfsPainel = [];
    foreach ($gruposDoLider as $g) {
        $grupoId = (int) $g['id'];

        // Frequência (regras 1 e 2) e presentes das últimas 8 reuniões, da mais antiga para a mais recente.
        $resumo          = $presencaRepo->buscarResumoPresencaPorGrupo($grupoId);
        $totalRegistros  = (int) $resumo['total_presencas'] + (int) $resumo['total_ausencias'];
        $resumoGrupo     = $presencaRepo->buscarResumoGrupo($grupoId);
        $colunas = [];
        foreach (array_reverse($presencaRepo->listarUltimasReunioesDoGrupo($grupoId, 8)) as $reuniao) {
            $presentes  = (int) $reuniao['total_presentes'];
            $registros  = $presentes + (int) $reuniao['total_ausentes'];
            $percentual = $registros > 0 ? $presentes / $registros * 100 : 0.0;
            $colunas[] = [
                'data'       => $reuniao['data'],
                'presentes'  => $presentes,
                'registros'  => $registros,
                'percentual' => (int) round($percentual),
                'baixa'      => $diagnostico->ehAlarmante($percentual, $registros),
            ];
        }

        // Escalas: só as que existirem.
        $escalas = [];
        if (trim((string) ($g['item_celeiro'] ?? '')) !== '') {
            $escalas['celeiro'] = trim((string) $g['item_celeiro']);
        }
        $domingoOracao = (int) ($g['domingo_oracao_culto'] ?? 0);
        $dataOracao = $domingoOracao > 0 ? $calendario->proximoDomingoDeOracao($domingoOracao, $agora) : null;
        if ($dataOracao !== null) {
            $escalas['oracao'] = ['domingo' => $domingoOracao, 'data' => $dataOracao];
        }
        if (!empty($g['proxima_cantina_data'])) {
            $escalas['cantina'] = (string) $g['proxima_cantina_data'];
        }

        // Precisam de cuidado: faltas seguidas + última presença do resumo por membro.
        $resumoMembros = $presencaRepo->buscarResumoPorMembroDoGrupo($grupoId);
        $ultimaPresencaPorPessoa = array_column($resumoMembros, 'ultima_presenca', 'pessoa_id');
        $cuidado = [];
        foreach ($presencaRepo->buscarMembrosComFaltasConsecutivas($grupoId, 2) as $faltoso) {
            $cuidado[] = [
                'grupo_id'        => $grupoId,
                'grupo_nome'      => $g['nome'],
                'pessoa_id'       => (int) $faltoso['pessoa_id'],
                'nome'            => $faltoso['nome'],
                'faltas'          => (int) $faltoso['faltas_consecutivas'],
                'ultima_presenca' => $ultimaPresencaPorPessoa[$faltoso['pessoa_id']] ?? null,
            ];
        }

        // Integração: mesma filtragem da tela antiga (index_v2.php).
        $integracao = [];
        if (($g['perfil_grupo'] ?? '') === 'integracao') {
            $integracao = array_values(array_filter(
                $resumoMembros,
                static function (array $membro): bool {
                    if ((int) ($membro['pessoa_id'] ?? 0) <= 0) {
                        return false;
                    }
                    return (int) ($membro['lider_grupo_familiar'] ?? 0) !== 1
                        && (int) ($membro['lider_departamento'] ?? 0) !== 1;
                }
            ));
        }

        $gfsPainel[] = [
            'proxima'    => $montarProximaReuniaoGf($g),
            'frequencia' => [
                'percentual'     => (float) $resumo['percentual_presencas'],
                'classificacao'  => $diagnostico->classificar((float) $resumo['percentual_presencas'], $totalRegistros),
                'total_reunioes' => (int) ($resumoGrupo['total_reunioes'] ?? 0),
            ],
            'colunas'    => $colunas,
            'escalas'    => $escalas,
            'cuidado'    => $cuidado,
            'integracao' => $integracao,
        ];
    }

    // Uma lista só, com quem tem mais faltas seguidas primeiro.
    $cuidadoPainel = array_merge([], ...array_column($gfsPainel, 'cuidado'));
    usort($cuidadoPainel, fn($a, $b) => [$b['faltas'], $a['nome']] <=> [$a['faltas'], $b['nome']]);

    // ── Últimas reuniões dos GFs do líder ────────────────────────
    $ultimas = [];
    foreach ($presencaRepo->listarUltimasReunioesDoLider(Auth::id(), 4) as $reuniao) {
        $dataReuniao = new DateTimeImmutable($reuniao['data']);
        $presentes   = (int) $reuniao['total_presentes'];
        $registros   = $presentes + (int) $reuniao['total_ausentes'];
        $percentual  = $registros > 0 ? $presentes / $registros * 100 : 0.0;
        $ultimas[] = [
            'reuniao_id'    => (int) $reuniao['id'],
            'grupo_nome'    => $reuniao['grupo_nome'],
            'data'          => $dataReuniao,
            'mes_abreviado' => $calendario->mesAbreviado($dataReuniao),
            'presentes'     => $presentes,
            'registros'     => $registros,
            'percentual'    => (int) round($percentual),
            'classificacao' => $diagnostico->classificar($percentual, $registros),
        ];
    }

    $painel = [
        'saudacao'     => $saudacao,
        'data_extenso' => $calendario->dataPorExtenso($agora),
        'pendencias'   => $pendencias,
        'gfs'          => $gfsPainel,
        'cuidado'      => $cuidadoPainel,
        'ultimas'      => $ultimas,
    ];

    // ── Contagem de avisos não lidos (o header depende de $totalAvisos) ──
    $gruposAlarmantesAvisos     = $presencaRepo->buscarGruposAlarmantesDoLider(Auth::id());
    $membrosFaltososAvisos      = $presencaRepo->buscarMembrosComFaltasConsecutivasDoLider(Auth::id(), 2);
    $reunioesForaDoPadraoAvisos = $presencaRepo->buscarReunioesForaDoPadraoDoLider(Auth::id(), 20);

    foreach ($gruposAlarmantesAvisos as $i) {
        if (!isset($chavesLidasMap['gf_alarmante_' . $i['id']])) $totalAvisos++;
    }
    foreach ($membrosFaltososAvisos as $i) {
        if (!isset($chavesLidasMap['faltas_' . $i['grupo_id'] . '_' . $i['pessoa_id']])) $totalAvisos++;
    }
    foreach ($reunioesForaDoPadraoAvisos as $i) {
        if (!isset($chavesLidasMap['reuniao_fora_padrao_' . $i['id']])) $totalAvisos++;
    }
    foreach ($avisoRepo->listarAvisosSistema(Auth::id()) as $avisoSistema) {
        $chaveAviso = $avisoSistema['chave_aviso'] ?? '';
        if ($chaveAviso !== '' && !isset($chavesLidasMap[$chaveAviso])) $totalAvisos++;
    }

    require_once __DIR__ . '/../src/Views/dashboard/lider.php';
}
