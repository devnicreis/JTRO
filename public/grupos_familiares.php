<?php

require_once __DIR__ . '/../src/Repositories/GrupoFamiliarRepository.php';

require_once __DIR__ . '/../src/Core/Auth.php';
Auth::requireAdmin();

$repo = new GrupoFamiliarRepository();

$mensagem = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $diaSemana = trim($_POST['dia_semana'] ?? '');
    $horario = trim($_POST['horario'] ?? '');
    $localPadrao = trim($_POST['local_padrao'] ?? '');
    $localFixo = isset($_POST['local_fixo']) ? 1 : 0;
    $lideresIds = $_POST['lideres'] ?? [];
    $membrosIds = $_POST['membros'] ?? [];

    try {
        $repo->salvar($nome, $diaSemana, $horario, $localPadrao, $localFixo, $lideresIds, $membrosIds);
        $mensagem = 'Grupo Familiar cadastrado com sucesso.';
    } catch (Exception $e) {
        $erro = $e->getMessage();
    }
}

$pessoas = $repo->listarPessoasAtivas();
$grupos = $repo->listarTodos();
$pageTitle = 'Cadastro de Grupos Familiares - JTRO';

require_once __DIR__ . '/../src/Views/grupos_familiares/index.php';