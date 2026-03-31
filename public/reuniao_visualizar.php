<?php

date_default_timezone_set('America/Sao_Paulo');

require_once __DIR__ . '/../src/Core/Auth.php';
require_once __DIR__ . '/../src/Repositories/PresencaRepository.php';

Auth::requireLogin();
Auth::requireSenhaAtualizada();

$repo = new PresencaRepository();

$reuniaoId = (int) ($_GET['id'] ?? 0);

if ($reuniaoId <= 0) {
    die('Reunião inválida.');
}

$reuniao = $repo->buscarReuniao($reuniaoId);

if (!$reuniao) {
    die('Reunião não encontrada.');
}

if (!Auth::isAdmin() && !$repo->liderPodeAcessarGrupo(Auth::id(), (int) $reuniao['grupo_familiar_id'])) {
    http_response_code(403);
    die('Acesso negado a esta reunião.');
}

$listaPresencas = $repo->listarPresencasPorReuniao($reuniaoId);
$resumoReuniao = $repo->buscarResumoDaReuniao($reuniaoId);
$resumoGrupo = $repo->buscarResumoGrupo((int) $reuniao['grupo_familiar_id']);
$ultimasReunioes = $repo->listarUltimasReunioesDoGrupo((int) $reuniao['grupo_familiar_id'], 5);
$lideres = $repo->buscarLideresDoGrupo((int) $reuniao['grupo_familiar_id']);

$pageTitle = 'Visualizar Reunião - JTRO';

require_once __DIR__ . '/../src/Views/reunioes/visualizar_v2.php';
