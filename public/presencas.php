<?php

require_once __DIR__ . '/../src/Repositories/PresencaRepository.php';

require_once __DIR__ . '/../src/Core/Auth.php';
Auth::requireLogin();

$repo = new PresencaRepository();

$mensagem = '';
$erro = '';

$grupoId = (int) ($_GET['grupo_id'] ?? $_POST['grupo_id'] ?? 0);
$data = trim($_GET['data'] ?? $_POST['data'] ?? '');
$reuniaoId = 0;
$reuniao = null;
$listaPresencas = [];
$resumoGrupo = [];
$ultimasReunioes = [];

if ($grupoId > 0 && !Auth::isAdmin()) {
    if (!$repo->liderPodeAcessarGrupo(Auth::id(), $grupoId)) {
        http_response_code(403);
        die('Acesso negado a este Grupo Familiar.');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_presencas'])) {
    $reuniaoId = (int) ($_POST['reuniao_id'] ?? 0);
    $grupoId = (int) ($_POST['grupo_id'] ?? 0);
    $data = trim($_POST['data'] ?? '');
    $local = trim($_POST['local'] ?? '');
    $observacoes = trim($_POST['observacoes'] ?? '');
    $presencas = $_POST['presencas'] ?? [];

    try {
        $repo->atualizarPresencasEReuniao($reuniaoId, $local, $observacoes, $presencas);
        $mensagem = 'Reunião e presenças atualizadas com sucesso.';
    } catch (Exception $e) {
        $erro = $e->getMessage();
    }
}

if ($grupoId > 0 && $data !== '') {
    try {
        $reuniaoId = $repo->buscarOuCriarReuniaoPorGrupoEData($grupoId, $data);
        $reuniao = $repo->buscarReuniao($reuniaoId);
        $listaPresencas = $repo->listarPresencasPorReuniao($reuniaoId);
        $resumoGrupo = $repo->buscarResumoGrupo($grupoId);
        $ultimasReunioes = $repo->listarUltimasReunioesDoGrupo($grupoId, 5);
    } catch (Exception $e) {
        $erro = $e->getMessage();
    }
}

if (Auth::isAdmin()) {
    $grupos = $repo->listarGruposFamiliares();
} else {
    $grupos = $repo->listarGruposFamiliaresPorLider(Auth::id());
}
$pageTitle = 'Reuniões e Presenças - JTRO';

if ($grupoId > 0 && empty($resumoGrupo)) {
    if (!Auth::isAdmin() && !$repo->liderPodeAcessarGrupo(Auth::id(), $grupoId)) {
        http_response_code(403);
        die('Acesso negado a este Grupo Familiar.');
    }

    $resumoGrupo = $repo->buscarResumoGrupo($grupoId);
    $ultimasReunioes = $repo->listarUltimasReunioesDoGrupo($grupoId, 5);
}

require_once __DIR__ . '/../src/Views/presencas/index.php';