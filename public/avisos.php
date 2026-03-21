<?php

require_once __DIR__ . '/../src/Core/Auth.php';
require_once __DIR__ . '/../src/Repositories/PresencaRepository.php';
require_once __DIR__ . '/../src/Repositories/AvisoRepository.php';

date_default_timezone_set('America/Sao_Paulo');

Auth::requireLogin();
Auth::requireSenhaAtualizada();

$presencaRepo = new PresencaRepository();
$avisoRepo = new AvisoRepository();

$usuarioId = Auth::id();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
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

if (Auth::isAdmin()) {
    $gruposAlarmantes = $presencaRepo->buscarGruposAlarmantes();
    $membrosFaltosos = $presencaRepo->buscarMembrosComFaltasConsecutivasGerais(2);
    $reunioesForaDoPadrao = $presencaRepo->buscarReunioesForaDoPadrao(20);
} else {
    $gruposAlarmantes = $presencaRepo->buscarGruposAlarmantesDoLider(Auth::id());
    $membrosFaltosos = $presencaRepo->buscarMembrosComFaltasConsecutivasDoLider(Auth::id(), 2);
    $reunioesForaDoPadrao = $presencaRepo->buscarReunioesForaDoPadraoDoLider(Auth::id(), 20);
}

$avisos = [];

foreach ($gruposAlarmantes as $grupo) {
    $avisos[] = [
        'chave' => 'gf_alarmante_' . $grupo['id'],
        'tipo' => 'grupo_alarmante',
        'titulo' => 'Grupo Familiar em nível alarmante',
        'grupo' => $grupo
    ];
}

foreach ($membrosFaltosos as $membro) {
    $avisos[] = [
        'chave' => 'faltas_' . $membro['grupo_id'] . '_' . $membro['pessoa_id'],
        'tipo' => 'faltas_consecutivas',
        'membro' => $membro
    ];
}

foreach ($reunioesForaDoPadrao as $reuniao) {
    $avisos[] = [
        'chave' => 'reuniao_fora_padrao_' . $reuniao['id'],
        'tipo' => 'reuniao_fora_padrao',
        'reuniao' => $reuniao
    ];
}

$chavesLidas = $avisoRepo->listarChavesLidas($usuarioId);
$chavesLidasMap = array_fill_keys($chavesLidas, true);

$avisosNaoLidos = [];
$avisosLidos = [];

foreach ($avisos as $aviso) {
    if (isset($chavesLidasMap[$aviso['chave']])) {
        $avisosLidos[] = $aviso;
    } else {
        $avisosNaoLidos[] = $aviso;
    }
}

$pageTitle = 'Notificações';

require_once __DIR__ . '/../src/Views/avisos/index.php';
