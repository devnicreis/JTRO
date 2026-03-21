<?php
require_once __DIR__ . '/../src/Core/Auth.php';
require_once __DIR__ . '/../src/Repositories/PresencaRepository.php';
require_once __DIR__ . '/../src/Repositories/AvisoRepository.php';
require_once __DIR__ . '/../src/Repositories/CartaRepository.php';

Auth::start();
if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['erro' => 'Não autorizado']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

$presencaRepo = new PresencaRepository();
$avisoRepo    = new AvisoRepository();
$cartaRepo    = new CartaRepository();
$usuarioId    = Auth::id();
$isAdmin      = Auth::isAdmin();

// ── POST: marcar lido/não lido ─────────────────────────────
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

// ── GET: listar avisos ─────────────────────────────────────
$chavesLidas    = $avisoRepo->listarChavesLidas($usuarioId);
$chavesLidasMap = array_fill_keys($chavesLidas, true);

if ($isAdmin) {
    $gruposAlarmantes   = $presencaRepo->buscarGruposAlarmantes();
    $membrosFaltosos    = $presencaRepo->buscarMembrosComFaltasConsecutivasGerais(2);
    $reunioesForaPadrao = $presencaRepo->buscarReunioesForaDoPadrao(20);
} else {
    $gruposAlarmantes   = $presencaRepo->buscarGruposAlarmantesDoLider($usuarioId);
    $membrosFaltosos    = $presencaRepo->buscarMembrosComFaltasConsecutivasDoLider($usuarioId, 2);
    $reunioesForaPadrao = $presencaRepo->buscarReunioesForaDoPadraoDoLider($usuarioId, 20);
}

$avisos = [];

// Cartas novas não lidas (apenas para líderes — admin já gerencia as cartas)
if (!$isAdmin) {
    $cartasPublicadas = $cartaRepo->listarPublicadas();
    foreach ($cartasPublicadas as $carta) {
        $chave = 'carta_nova_' . $carta['id'];
        $ts    = strtotime($carta['data_carta'] ?? $carta['created_at']);
        $avisos[] = [
            'chave'     => $chave,
            'tipo'      => 'info',
            'texto'     => 'Nova Carta Semanal disponível',
            'detalhe'   => date('d/m/Y', $ts),
            'link'      => '/carta_visualizar.php?id=' . (int)$carta['id'],
            'lido'      => isset($chavesLidasMap[$chave]),
            'timestamp' => $ts,
        ];
    }
}

foreach ($gruposAlarmantes as $g) {
    $chave = 'gf_alarmante_' . $g['id'];
    $avisos[] = [
        'chave'     => $chave,
        'tipo'      => 'danger',
        'texto'     => 'GF com presença alarmante',
        'detalhe'   => $g['nome'],
        'lido'      => isset($chavesLidasMap[$chave]),
        'timestamp' => time(),
    ];
}

foreach ($membrosFaltosos as $m) {
    $chave = 'faltas_' . $m['grupo_id'] . '_' . $m['pessoa_id'];
    $avisos[] = [
        'chave'     => $chave,
        'tipo'      => 'warn',
        'texto'     => 'Faltas consecutivas',
        'detalhe'   => $m['nome'] . ' · GF ' . $m['grupo_nome'],
        'lido'      => isset($chavesLidasMap[$chave]),
        'timestamp' => strtotime('yesterday'),
    ];
}

foreach ($reunioesForaPadrao as $r) {
    $chave = 'reuniao_fora_padrao_' . $r['id'];
    $dataFormatada = !empty($r['data']) ? date('d/m/Y', strtotime($r['data'])) : '';
    $avisos[] = [
        'chave'     => $chave,
        'tipo'      => 'info',
        'texto'     => 'Reunião fora do padrão',
        'detalhe'   => trim(($r['grupo_nome'] ?? 'GF') . ($dataFormatada !== '' ? ' · ' . $dataFormatada : '')),
        'motivo'    => !empty($r['motivo_alteracao']) ? 'Motivo: ' . $r['motivo_alteracao'] : '',
        'lido'      => isset($chavesLidasMap[$chave]),
        'timestamp' => isset($r['data']) ? strtotime($r['data']) : strtotime('-3 days'),
    ];
}

// Ordena: não lidos primeiro, depois por timestamp desc
usort($avisos, function($a, $b) {
    if ($a['lido'] !== $b['lido']) return $a['lido'] <=> $b['lido'];
    return $b['timestamp'] <=> $a['timestamp'];
});

echo json_encode(['avisos' => $avisos]);