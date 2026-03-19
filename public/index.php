<?php

require_once __DIR__ . '/../src/Core/Auth.php';
require_once __DIR__ . '/../src/Repositories/PessoaRepository.php';
require_once __DIR__ . '/../src/Repositories/GrupoFamiliarRepository.php';
require_once __DIR__ . '/../src/Repositories/PresencaRepository.php';
require_once __DIR__ . '/../src/Services/AuditoriaService.php';
require_once __DIR__ . '/../src/Repositories/AvisoRepository.php';

date_default_timezone_set('America/Sao_Paulo');

Auth::requireLogin();
Auth::requireSenhaAtualizada();

$usuario = Auth::usuario();

$pessoaRepo = new PessoaRepository();
$grupoRepo = new GrupoFamiliarRepository();
$presencaRepo = new PresencaRepository();
$auditoria = new AuditoriaService();
$avisoRepo = new AvisoRepository();

$mensagem = '';

if (isset($_GET['senha_alterada']) && $_GET['senha_alterada'] === '1') {
    $mensagem = 'Senha alterada com sucesso.';
}

$pageTitle = 'Dashboard - JTRO';

$chavesLidas = $avisoRepo->listarChavesLidas(Auth::id());
$chavesLidasMap = array_fill_keys($chavesLidas, true);

if (Auth::isAdmin()) {
    $dashboardTipo = 'admin';

    $totalPessoasAtivas = $pessoaRepo->contarPessoasAtivas();
    $totalLideresAtivos = $pessoaRepo->contarLideresAtivos();
    $totalGruposAtivos = $grupoRepo->contarGruposAtivos();
    $totalReunioes = $presencaRepo->contarReunioes();
    $ultimasReunioes = $presencaRepo->listarUltimasReunioesGerais(5);
    $totalPresencasAtualizadas = $auditoria->contarLogsPorEntidadeEAcao('presencas', 'atualizar');

    $grupoSelecionadoId = (int) ($_GET['grupo_id'] ?? 0);

    $gruposResumoAdmin = [];
    $gruposAtivosLista = $grupoRepo->listarTodos();

    if ($grupoSelecionadoId > 0) {
        foreach ($gruposAtivosLista as $grupo) {
            if ((int) ($grupo['ativo'] ?? 1) !== 1) {
                continue;
            }

            if ((int) $grupo['id'] !== $grupoSelecionadoId) {
                continue;
            }

            $resumoPresenca = $presencaRepo->buscarResumoPresencaPorGrupo((int) $grupo['id']);
            $resumoMembros = $presencaRepo->buscarResumoPorMembroDoGrupo((int) $grupo['id']);
            $faltosos = $presencaRepo->buscarMembrosComFaltasConsecutivas((int) $grupo['id'], 2);

            $percentual = (float) $resumoPresenca['percentual_presencas'];
            $totalRegistros = (int) $resumoPresenca['total_presencas'] + (int) $resumoPresenca['total_ausencias'];

            if ($totalRegistros === 0) {
                $diagnostico = 'NEUTRO';
                $diagnosticoClasse = 'diagnostico-neutro';
            } elseif ($percentual >= 90) {
                $diagnostico = 'ÓTIMO';
                $diagnosticoClasse = 'diagnostico-otimo';
            } elseif ($percentual >= 70) {
                $diagnostico = 'BOM';
                $diagnosticoClasse = 'diagnostico-bom';
            } elseif ($percentual >= 51) {
                $diagnostico = 'ATENÇÃO';
                $diagnosticoClasse = 'diagnostico-atencao';
            } else {
                $diagnostico = 'ALARMANTE';
                $diagnosticoClasse = 'diagnostico-alarmante';
            }

            $gruposResumoAdmin[] = [
                'grupo' => $grupo,
                'resumo_presenca' => $resumoPresenca,
                'resumo_membros' => $resumoMembros,
                'faltosos' => $faltosos,
                'diagnostico' => $diagnostico,
                'diagnostico_classe' => $diagnosticoClasse
            ];

            break;
        }
    }

    $gruposAlarmantesAvisos = $presencaRepo->buscarGruposAlarmantes();
    $membrosFaltososAvisos = $presencaRepo->buscarMembrosComFaltasConsecutivasGerais(2);
    $reunioesForaDoPadraoAvisos = $presencaRepo->buscarReunioesForaDoPadrao(20);

    $totalAvisos = 0;

    foreach ($gruposAlarmantesAvisos as $grupoAviso) {
        $chave = 'gf_alarmante_' . $grupoAviso['id'];

        if (!isset($chavesLidasMap[$chave])) {
            $totalAvisos++;
        }
    }

    foreach ($membrosFaltososAvisos as $membroAviso) {
        $chave = 'faltas_' . $membroAviso['grupo_id'] . '_' . $membroAviso['pessoa_id'];

        if (!isset($chavesLidasMap[$chave])) {
            $totalAvisos++;
        }
    }

    foreach ($reunioesForaDoPadraoAvisos as $reuniaoAviso) {
        $chave = 'reuniao_fora_padrao_' . $reuniaoAviso['id'];

        if (!isset($chavesLidasMap[$chave])) {
            $totalAvisos++;
        }
    }
} else {
    $dashboardTipo = 'lider';

    $gruposDoLider = $grupoRepo->listarGruposDoLider(Auth::id());
    $ultimasReunioes = $presencaRepo->listarUltimasReunioesDoLider(Auth::id(), 5);

    foreach ($gruposDoLider as &$grupo) {
        $grupo['resumo_presenca'] = $presencaRepo->buscarResumoPresencaPorGrupo((int) $grupo['id']);
        $grupo['resumo_membros'] = $presencaRepo->buscarResumoPorMembroDoGrupo((int) $grupo['id']);
        $grupo['faltosos'] = $presencaRepo->buscarMembrosComFaltasConsecutivas((int) $grupo['id'], 2);
    }
    unset($grupo);

    $gruposAlarmantesAvisos = $presencaRepo->buscarGruposAlarmantesDoLider(Auth::id());
    $membrosFaltososAvisos = $presencaRepo->buscarMembrosComFaltasConsecutivasDoLider(Auth::id(), 2);
    $reunioesForaDoPadraoAvisos = $presencaRepo->buscarReunioesForaDoPadraoDoLider(Auth::id(), 20);

    $totalAvisos = 0;

    foreach ($gruposAlarmantesAvisos as $grupoAviso) {
        $chave = 'gf_alarmante_' . $grupoAviso['id'];

        if (!isset($chavesLidasMap[$chave])) {
            $totalAvisos++;
        }
    }

    foreach ($membrosFaltososAvisos as $membroAviso) {
        $chave = 'faltas_' . $membroAviso['grupo_id'] . '_' . $membroAviso['pessoa_id'];

        if (!isset($chavesLidasMap[$chave])) {
            $totalAvisos++;
        }
    }

    foreach ($reunioesForaDoPadraoAvisos as $reuniaoAviso) {
        $chave = 'reuniao_fora_padrao_' . $reuniaoAviso['id'];

        if (!isset($chavesLidasMap[$chave])) {
            $totalAvisos++;
        }
    }
}

require_once __DIR__ . '/../src/Views/dashboard/index.php';
