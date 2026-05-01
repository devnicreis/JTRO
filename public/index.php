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
$grupoRepo  = new GrupoFamiliarRepository();
$presencaRepo = new PresencaRepository();
$auditoria  = new AuditoriaService();
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
$ultimasReunioesDosMeusGfs = $presencaRepo->listarUltimasReunioesDoLider(Auth::id(), 5);

// ── Helper para contar avisos não lidos ──────────────────────
function contarNaoLidos(array $itens, string $prefixo, array $map, string $campoId = 'id'): int {
    $count = 0;
    foreach ($itens as $item) {
        if (!isset($map[$prefixo . $item[$campoId]])) $count++;
    }
    return $count;
}

if (Auth::isAdmin()) {
    $dashboardTipo = 'admin';

    // Busca base — usada para múltiplas derivações abaixo
    $todosGrupos    = $grupoRepo->listarTodos();
    $gruposAtivos   = array_values(array_filter($todosGrupos, fn($g) => (int)($g['ativo'] ?? 0) === 1));

    $totalPessoasAtivas        = $pessoaRepo->contarPessoasAtivas();
    $totalGruposAtivos         = count($gruposAtivos);
    $totalReunioes             = $presencaRepo->contarReunioes();
    $ultimasReunioes           = $presencaRepo->listarUltimasReunioesGerais(10);
    $totalPresencasAtualizadas = $auditoria->contarLogsPorEntidadeEAcao('presencas', 'atualizar');
    $proximasReunioes          = $gruposAtivos;

    // Líderes únicos de GFs ativos (fonte de verdade: campo lideres do grupo)
    $listaLideresAtivos = [];
    foreach ($gruposAtivos as $g) {
        if (!empty($g['lideres'])) {
            foreach (explode(', ', $g['lideres']) as $nomeLider) {
                $nomeLider = trim($nomeLider);
                if ($nomeLider !== '') $listaLideresAtivos[$nomeLider] = true;
            }
        }
    }
    $listaLideresAtivos = array_keys($listaLideresAtivos);
    $totalLideresAtivos = count($listaLideresAtivos);

    // Listas para tooltips
    $listaPessoasAtivas = array_column($pessoaRepo->listarAtivas(), 'nome');
    $listaGruposAtivos  = array_column($gruposAtivos, 'nome');

    $gruposAlarmantesAvisos      = $presencaRepo->buscarGruposAlarmantes();
    $membrosFaltososAvisos       = $presencaRepo->buscarMembrosComFaltasConsecutivasGerais(2);
    $reunioesForaDoPadraoAvisos  = $presencaRepo->buscarReunioesForaDoPadrao(20);

    foreach ($gruposDoLider as &$grupo) {
        $grupo['resumo_presenca'] = $presencaRepo->buscarResumoPresencaPorGrupo((int) $grupo['id']);
        $grupo['resumo_membros'] = $presencaRepo->buscarResumoPorMembroDoGrupo((int) $grupo['id']);
        $grupo['faltosos'] = $presencaRepo->buscarMembrosComFaltasConsecutivas((int) $grupo['id'], 2);
    }
    unset($grupo);

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

} else {
    $dashboardTipo = 'lider';

    $ultimasReunioes = $ultimasReunioesDosMeusGfs;

    foreach ($gruposDoLider as &$grupo) {
        $grupo['resumo_presenca'] = $presencaRepo->buscarResumoPresencaPorGrupo((int) $grupo['id']);
        $grupo['resumo_membros']  = $presencaRepo->buscarResumoPorMembroDoGrupo((int) $grupo['id']);
        $grupo['faltosos']        = $presencaRepo->buscarMembrosComFaltasConsecutivas((int) $grupo['id'], 2);
    }
    unset($grupo);

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
}

require_once __DIR__ . '/../src/Views/dashboard/index_v2.php';
