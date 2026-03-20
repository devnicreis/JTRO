<?php
require_once __DIR__ . '/../src/Core/Auth.php';
require_once __DIR__ . '/../src/Repositories/PresencaRepository.php';
require_once __DIR__ . '/../src/Repositories/AvisoRepository.php';

Auth::start();
if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['erro' => 'Não autorizado']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

$presencaRepo = new PresencaRepository();
$avisoRepo    = new AvisoRepository();
$usuarioId    = Auth::id();
$isAdmin      = Auth::isAdmin();

// Marcar como lido via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body  = json_decode(file_get_contents('php://input'), true);
    $acao  = $body['acao']  ?? '';
    $chave = $body['chave'] ?? '';

    if ($acao === 'marcar_lido' && $chave !== '') {
        $avisoRepo->marcarComoLido($usuarioId, $chave);
    } elseif ($acao === 'marcar_nao_lido' && $chave !== '') {
        $avisoRepo->desmarcarComoLido($usuarioId, $chave);
    } elseif ($acao === 'marcar_todos_lidos') {
        $chaves = $body['chaves'] ?? [];
        foreach ($chaves as $c) {
            if ($c !== '') $avisoRepo->marcarComoLido($usuarioId, $c);
        }
    }

    echo json_encode(['ok' => true]);
    exit;
}

// Buscar avisos
$chavesLidas    = $avisoRepo->listarChavesLidas($usuarioId);
$chavesLidasMap = array_fill_keys($chavesLidas, true);

if ($isAdmin) {
    $gruposAlarmantes     = $presencaRepo->buscarGruposAlarmantes();
    $membrosFaltosos      = $presencaRepo->buscarMembrosComFaltasConsecutivasGerais(2);
    $reunioesForaPadrao   = $presencaRepo->buscarReunioesForaDoPadrao(20);
} else {
    $gruposAlarmantes     = $presencaRepo->buscarGruposAlarmantesDoLider($usuarioId);
    $membrosFaltosos      = $presencaRepo->buscarMembrosComFaltasConsecutivasDoLider($usuarioId, 2);
    $reunioesForaPadrao   = $presencaRepo->buscarReunioesForaDoPadraoDoLider($usuarioId, 20);
}

$avisos = [];

foreach ($gruposAlarmantes as $g) {
    $chave = 'gf_alarmante_' . $g['id'];
    $avisos[] = [
        'chave'     => $chave,
        'tipo'      => 'danger',
        'texto'     => 'GF ' . $g['nome'] . ' com presença alarmante',
        'lido'      => isset($chavesLidasMap[$chave]),
        'timestamp' => time(),
    ];
}

foreach ($membrosFaltosos as $m) {
    $chave = 'faltas_' . $m['grupo_id'] . '_' . $m['pessoa_id'];
    $avisos[] = [
        'chave'     => $chave,
        'tipo'      => 'warn',
        'texto'     => $m['nome'] . ' — ' . (int)$m['faltas_consecutivas'] . ' faltas consecutivas no GF ' . $m['grupo_nome'],
        'lido'      => isset($chavesLidasMap[$chave]),
        'timestamp' => strtotime('yesterday'),
    ];
}

foreach ($reunioesForaPadrao as $r) {
    $chave = 'reuniao_fora_padrao_' . $r['id'];
    $avisos[] = [
        'chave'     => $chave,
        'tipo'      => 'info',
        'texto'     => 'Reunião do GF ' . $r['grupo_nome'] . ' com presença abaixo do padrão',
        'lido'      => isset($chavesLidasMap[$chave]),
        'timestamp' => isset($r['data']) ? strtotime($r['data']) : strtotime('-3 days'),
    ];
}

echo json_encode(['avisos' => $avisos]);