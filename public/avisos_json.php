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

$avisoRepo->sincronizarAvisosAniversarioDoDia();
$avisoRepo->sincronizarAvisosCantina();

// POST: marcar lido/não lido
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::requireCsrf($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);

    $rawBody = file_get_contents('php://input');
    $body  = json_decode($rawBody !== false ? $rawBody : '', true);
    if (!is_array($body)) {
        http_response_code(400);
        echo json_encode(['erro' => 'Corpo da requisicao invalido.']);
        exit;
    }

    $acao  = $body['acao']  ?? '';
    $chave = $body['chave'] ?? '';

    if ($acao === 'marcar_lido' && $chave !== '') {
        $avisoRepo->marcarComoLido($usuarioId, $chave);
    } elseif ($acao === 'marcar_nao_lido' && $chave !== '') {
        $avisoRepo->desmarcarComoLido($usuarioId, $chave);
    } elseif ($acao === 'marcar_todos_lidos') {
        $chaves = is_array($body['chaves'] ?? null) ? array_slice($body['chaves'], 0, 200) : [];
        foreach ($chaves as $c) {
            if (is_string($c) && trim($c) !== '') {
                $avisoRepo->marcarComoLido($usuarioId, $c);
            }
        }
    } else {
        http_response_code(400);
        echo json_encode(['erro' => 'Acao invalida.']);
        exit;
    }

    echo json_encode(['ok' => true]);
    exit;
}

// GET: listar avisos
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

foreach ($avisoRepo->listarAvisosSistema($usuarioId) as $avisoSistema) {
    $timestamp = strtotime($avisoSistema['created_at'] ?? 'now');
    $avisos[] = [
        'chave'     => $avisoSistema['chave_aviso'],
        'tipo'      => ($avisoSistema['tipo'] ?? '') === 'integracao_concluida' ? 'success' : 'info',
        'texto'     => $avisoSistema['titulo'],
        'detalhe'   => $avisoSistema['mensagem'],
        'link'      => $avisoSistema['link'] ?? null,
        'cta_label' => !empty($avisoSistema['link']) ? 'ABRIR DETALHES' : null,
        'lido'      => isset($chavesLidasMap[$avisoSistema['chave_aviso']]),
        'timestamp' => $timestamp,
    ];
}

// Cartas novas não lidas (todos os perfis)
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
        'cta_label' => 'ACESSAR CARTA COMPLETA',
        'lido'      => isset($chavesLidasMap[$chave]),
        'timestamp' => $ts,
    ];
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
