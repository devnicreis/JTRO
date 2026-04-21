<?php

date_default_timezone_set('America/Sao_Paulo');

require_once __DIR__ . '/../src/Core/Auth.php';
require_once __DIR__ . '/../src/Repositories/PresencaRepository.php';
require_once __DIR__ . '/../src/Services/AuditoriaService.php';

Auth::requireLogin();
Auth::requireSenhaAtualizada();

$repo = new PresencaRepository();
$auditoria = new AuditoriaService();

$mensagem = '';
$erro = '';

$reuniaoId = (int) ($_GET['reuniao_id'] ?? $_POST['reuniao_id'] ?? 0);

if ($reuniaoId <= 0) {
    die('Reunião inválida.');
}

$reuniao = $repo->buscarReuniao($reuniaoId);

if (!$reuniao) {
    die('Reunião não encontrada.');
}

if (!Auth::isAdmin() && !$repo->liderPodeAcessarGrupo(Auth::id(), (int) $reuniao['grupo_familiar_id'])) {
    http_response_code(403);
    die('Acesso negado.');
}

$presencasPendentes = $repo->reuniaoTemPresencasPendentes($reuniaoId);

if ($presencasPendentes) {
    header(
        'Location: /presencas.php?grupo_id='
        . (int) $reuniao['grupo_familiar_id']
        . '&data=' . urlencode($reuniao['data'])
        . '&erro_pedidos=1'
    );
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pedidos = $_POST['pedidos'] ?? [];

    try {
        $repo->salvarPedidosOracao($reuniaoId, $pedidos);
        $mensagem = 'Pedidos de oração salvos com sucesso.';

        $auditoria->registrar(
            'atualizar',
            'pedidos_oracao',
            $reuniaoId,
            "Pedidos de oração atualizados para a reunião do GF {$reuniao['grupo_nome']} em " . date('d/m/Y', strtotime($reuniao['data'])) . ".",
            null,
            (int) $reuniao['grupo_familiar_id'],
            $reuniao['data']
        );
    } catch (Exception $e) {
        $erro = $e->getMessage();
    }
}

$camposPedidos = $repo->listarCamposPedidosOracaoDaReuniao($reuniaoId);

$pageTitle = 'Pedidos de Oração - JTRO';

require_once __DIR__ . '/../src/Views/reunioes/pedidos_oracao.php';
