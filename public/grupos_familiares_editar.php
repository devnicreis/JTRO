<?php

require_once __DIR__ . '/../src/Repositories/GrupoFamiliarRepository.php';
require_once __DIR__ . '/../src/Core/Auth.php';

Auth::requireAdmin();
Auth::requireSenhaAtualizada();

$repo = new GrupoFamiliarRepository();

$mensagem = '';
$erro = '';

$grupoId = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

if ($grupoId <= 0) {
    die('Grupo Familiar inválido.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $diaSemana = trim($_POST['dia_semana'] ?? '');
    $horario = trim($_POST['horario'] ?? '');
    $localPadrao = trim($_POST['local_padrao'] ?? '');
    $localFixo = isset($_POST['local_fixo']) ? 1 : 0;
    $lideresIds = $_POST['lideres'] ?? [];
    $membrosIds = $_POST['membros'] ?? [];

    try {
        $repo->atualizar($grupoId, $nome, $diaSemana, $horario, $localPadrao, $localFixo, $lideresIds, $membrosIds);
        $mensagem = 'Grupo Familiar atualizado com sucesso.';
    } catch (Exception $e) {
        $erro = $e->getMessage();
    }
}

$grupo = $repo->buscarPorId($grupoId);

if (!$grupo) {
    die('Grupo Familiar não encontrado.');
}

$pessoas = $repo->listarPessoasAtivas();
$lideresSelecionados = $repo->listarLideresIdsDoGrupo($grupoId);
$membrosSelecionados = $repo->listarMembrosIdsDoGrupo($grupoId);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $grupo['nome'] = $_POST['nome'] ?? $grupo['nome'];
    $grupo['dia_semana'] = $_POST['dia_semana'] ?? $grupo['dia_semana'];
    $grupo['horario'] = $_POST['horario'] ?? $grupo['horario'];
    $grupo['local_padrao'] = $_POST['local_padrao'] ?? ($grupo['local_padrao'] ?? '');
    $grupo['local_fixo'] = isset($_POST['local_fixo']) ? 1 : 0;

    $lideresSelecionados = array_map('intval', $_POST['lideres'] ?? []);
    $membrosSelecionados = array_map('intval', $_POST['membros'] ?? []);
}

$dias = ['segunda-feira', 'terça-feira', 'quarta-feira', 'quinta-feira', 'sexta-feira', 'sábado', 'domingo'];
$pageTitle = 'Editar Grupo Familiar - JTRO';

require_once __DIR__ . '/../src/Views/grupos_familiares/editar.php';