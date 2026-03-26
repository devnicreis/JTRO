<?php

require_once __DIR__ . '/../src/Core/Auth.php';
require_once __DIR__ . '/../src/Repositories/GrupoFamiliarRepository.php';
require_once __DIR__ . '/../src/Repositories/PresencaRepository.php';

date_default_timezone_set('America/Sao_Paulo');

Auth::requireLogin();
Auth::requireSenhaAtualizada();

$grupoRepo    = new GrupoFamiliarRepository();
$presencaRepo = new PresencaRepository();

$pageTitle = 'Diagnóstico de GFs - JTRO';

$usuario = Auth::usuario();
$isAdmin = Auth::isAdmin();

if ($isAdmin) {
    $gruposAtivosLista = $grupoRepo->listarTodos();
} else {
    $gruposAtivosLista = $grupoRepo->listarPorLider($usuario['id']);
}
$grupoSelecionadoId = (int) ($_GET['grupo_id'] ?? 0);
$membroFiltroId     = (int) ($_GET['membro_id'] ?? 0);

$diagnostico       = null;
$diagnosticoClasse = null;
$resumoPresenca    = null;
$resumoMembros     = null;
$faltosos          = [];
$ultimasReunioes   = [];
$grupoSelecionado  = null;
$membrosFiltrados  = [];
$totalLideresNoGrupo = 0;

if (!$isAdmin && count($gruposAtivosLista) === 1) {
    $grupoSelecionadoId = $gruposAtivosLista[0]['id'];
}

if ($grupoSelecionadoId > 0) {
    foreach ($gruposAtivosLista as $g) {
        if ((int) ($g['ativo'] ?? 1) !== 1) continue;

        if ((int) $g['id'] === $grupoSelecionadoId) {
            $grupoSelecionado = $g;
            break;
        }
    }

    if ($grupoSelecionadoId > 0 && !$grupoSelecionado) {
        http_response_code(403);
        die('Acesso negado.');
    }

    if ($grupoSelecionado) {
        // Busca dados calculados (total_membros_ativos, total_reunioes, ultima_data_reuniao)
        $resumoGrupo     = $presencaRepo->buscarResumoGrupo($grupoSelecionadoId);
        // Mescla com os dados base do grupo (dia_semana, horario, lideres etc)
        $grupoSelecionado = array_merge($grupoSelecionado, [
            'total_membros'   => $resumoGrupo['total_membros_ativos'] ?? '—',
            'total_reunioes'  => $resumoGrupo['total_reunioes'] ?? '—',
            'ultima_reuniao'  => $resumoGrupo['ultima_data_reuniao'] ?? null,
        ]);
        $resumoPresenca  = $presencaRepo->buscarResumoPresencaPorGrupo($grupoSelecionadoId);
        $resumoMembros   = $presencaRepo->buscarResumoPorMembroDoGrupo($grupoSelecionadoId);
        $faltosos        = $presencaRepo->buscarMembrosComFaltasConsecutivas($grupoSelecionadoId, 2);
        $ultimasReunioes = $presencaRepo->listarUltimasReunioesDoGrupo($grupoSelecionadoId, 10);

        // Filtro por membro
        if ($membroFiltroId > 0) {
            $membrosFiltrados = array_filter($resumoMembros, fn($m) => (int)$m['pessoa_id'] === $membroFiltroId);
        } else {
            $membrosFiltrados = $resumoMembros;
        }

        // Diagnóstico
        foreach ($resumoMembros as $membroResumo) {
            if (
                (int) ($membroResumo['lider_grupo_familiar'] ?? 0) === 1 ||
                (int) ($membroResumo['lider_departamento'] ?? 0) === 1
            ) {
                $totalLideresNoGrupo++;
            }
        }

        $percentual      = (float) $resumoPresenca['percentual_presencas'];
        $totalRegistros  = (int) $resumoPresenca['total_presencas'] + (int) $resumoPresenca['total_ausencias'];

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
    }
}

require_once __DIR__ . '/../src/Views/diagnostico_gf/index.php';
