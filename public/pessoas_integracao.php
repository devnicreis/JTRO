<?php

require_once __DIR__ . '/../src/Core/Auth.php';
require_once __DIR__ . '/../src/Repositories/PessoaRepository.php';
require_once __DIR__ . '/../src/Repositories/PresencaRepository.php';

Auth::requireLogin();
Auth::requireSenhaAtualizada();

$pessoaId = (int) ($_GET['id'] ?? 0);

if ($pessoaId <= 0) {
    http_response_code(400);
    die('Pessoa inválida.');
}

$pessoaRepo = new PessoaRepository();
$presencaRepo = new PresencaRepository();

$pessoa = $pessoaRepo->buscarPorId($pessoaId);
if (!$pessoa) {
    http_response_code(404);
    die('Pessoa não encontrada.');
}

$grupoId = (int) ($pessoa['grupo_familiar_id'] ?? 0);
$usuarioId = Auth::id();

if (!Auth::isAdmin()) {
    if ($grupoId <= 0 || !$presencaRepo->liderPodeAcessarGrupo($usuarioId, $grupoId)) {
        http_response_code(403);
        die('Você não tem permissão para visualizar a integração desta pessoa.');
    }
}

$progresso = $presencaRepo->listarProgressoIntegracaoPessoa($pessoaId);
$totalConcluidas = 0;

foreach ($progresso as $aula) {
    if (!empty($aula['concluida'])) {
        $totalConcluidas++;
    }
}

$pageTitle = 'Aulas de Integração - JTRO';

require_once __DIR__ . '/../src/Views/pessoas/integracao.php';
