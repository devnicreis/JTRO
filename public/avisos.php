<?php
require_once __DIR__ . '/../src/Core/Auth.php';
require_once __DIR__ . '/../src/Repositories/PresencaRepository.php';
require_once __DIR__ . '/../src/Repositories/AvisoRepository.php';
require_once __DIR__ . '/../src/Repositories/CartaRepository.php';

date_default_timezone_set('America/Sao_Paulo');
Auth::requireLogin();
Auth::requireSenhaAtualizada();

$presencaRepo = new PresencaRepository();
$avisoRepo    = new AvisoRepository();
$cartaRepo    = new CartaRepository();
$usuarioId    = Auth::id();
$isAdmin      = Auth::isAdmin();

$avisoRepo->sincronizarAvisosAniversarioDoDia();
$avisoRepo->sincronizarAvisosCantina();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao       = $_POST['acao'] ?? '';
    $chaveAviso = trim($_POST['chave_aviso'] ?? '');
    if ($chaveAviso !== '') {
        if ($acao === 'marcar_lido') {
            $avisoRepo->marcarComoLido($usuarioId, $chaveAviso);
        } elseif ($acao === 'marcar_nao_lido') {
            $avisoRepo->desmarcarComoLido($usuarioId, $chaveAviso);
        }
    }
    header('Location: /avisos.php');
    exit;
}

if ($isAdmin) {
    $gruposAlarmantes    = $presencaRepo->buscarGruposAlarmantes();
    $membrosFaltosos     = $presencaRepo->buscarMembrosComFaltasConsecutivasGerais(2);
    $reunioesForaPadrao  = $presencaRepo->buscarReunioesForaDoPadrao(20);
} else {
    $gruposAlarmantes    = $presencaRepo->buscarGruposAlarmantesDoLider(Auth::id());
    $membrosFaltosos     = $presencaRepo->buscarMembrosComFaltasConsecutivasDoLider(Auth::id(), 2);
    $reunioesForaPadrao  = $presencaRepo->buscarReunioesForaDoPadraoDoLider(Auth::id(), 20);
}

$avisos = [];

foreach ($avisoRepo->listarAvisosSistema($usuarioId) as $avisoSistema) {
    $avisos[] = [
        'chave' => $avisoSistema['chave_aviso'],
        'tipo' => 'aviso_sistema',
        'subtipo' => $avisoSistema['tipo'],
        'titulo' => $avisoSistema['titulo'],
        'mensagem' => $avisoSistema['mensagem'],
        'link' => $avisoSistema['link'],
        'created_at' => $avisoSistema['created_at'],
        'ts' => strtotime($avisoSistema['created_at'] ?? 'now'),
    ];
}

// Cartas publicadas para todos os perfis
foreach ($cartaRepo->listarPublicadas() as $carta) {
    $ts = strtotime($carta['data_carta'] ?? $carta['created_at']);
    $avisos[] = [
        'chave'  => 'carta_nova_' . $carta['id'],
        'tipo'   => 'carta_nova',
        'titulo' => 'Carta Semanal disponível',
        'carta'  => $carta,
        'ts'     => $ts,
    ];
}

foreach ($gruposAlarmantes as $grupo) {
    $avisos[] = [
        'chave' => 'gf_alarmante_' . $grupo['id'],
        'tipo'  => 'grupo_alarmante',
        'titulo'=> 'Grupo Familiar em nível alarmante',
        'grupo' => $grupo,
        'ts' => time(),
    ];
}

foreach ($membrosFaltosos as $membro) {
    $avisos[] = [
        'chave'  => 'faltas_' . $membro['grupo_id'] . '_' . $membro['pessoa_id'],
        'tipo'   => 'faltas_consecutivas',
        'membro' => $membro,
        'ts' => strtotime('yesterday'),
    ];
}

foreach ($reunioesForaPadrao as $reuniao) {
    $avisos[] = [
        'chave'   => 'reuniao_fora_padrao_' . $reuniao['id'],
        'tipo'    => 'reuniao_fora_padrao',
        'reuniao' => $reuniao,
        'ts' => isset($reuniao['data']) ? strtotime($reuniao['data']) : strtotime('-3 days'),
    ];
}

$chavesLidas    = $avisoRepo->listarChavesLidas($usuarioId);
$chavesLidasMap = array_fill_keys($chavesLidas, true);

$avisosNaoLidos = [];
$avisosLidos    = [];

foreach ($avisos as $aviso) {
    if (isset($chavesLidasMap[$aviso['chave']])) {
        $avisosLidos[]    = $aviso;
    } else {
        $avisosNaoLidos[] = $aviso;
    }
}

usort($avisosNaoLidos, function ($a, $b) {
    return ($b['ts'] ?? 0) <=> ($a['ts'] ?? 0);
});

usort($avisosLidos, function ($a, $b) {
    return ($b['ts'] ?? 0) <=> ($a['ts'] ?? 0);
});

$pageTitle = 'Notificações';
require_once __DIR__ . '/../src/Views/avisos/index.php';
