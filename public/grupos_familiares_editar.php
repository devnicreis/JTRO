<?php

require_once __DIR__ . '/../src/Core/Auth.php';
require_once __DIR__ . '/../src/Repositories/GrupoFamiliarRepository.php';
require_once __DIR__ . '/../src/Services/AuditoriaService.php';

Auth::requireLogin();
Auth::requireSenhaAtualizada();
Auth::requireAdmin();

$repo = new GrupoFamiliarRepository();
$auditoria = new AuditoriaService();

$mensagem = '';
$erro = '';

$grupoId = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

if ($grupoId <= 0) {
    die('Grupo Familiar inválido.');
}

$grupo = $repo->buscarPorId($grupoId);

if (!$grupo) {
    die('Grupo Familiar não encontrado.');
}

$pessoas = $repo->listarPessoasAtivas();
$lideresSelecionados = $repo->listarLideresIdsDoGrupo($grupoId);
$membrosSelecionados = $repo->listarMembrosIdsDoGrupo($grupoId);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $diaSemana = trim($_POST['dia_semana'] ?? '');
    $horario = trim($_POST['horario'] ?? '');
    $localPadrao = trim($_POST['local_padrao'] ?? '');
    $localFixo = isset($_POST['local_fixo']) ? 1 : 0;
    $lideresIds = array_map('intval', $_POST['lideres'] ?? []);
    $membrosIds = array_map('intval', $_POST['membros'] ?? []);

    if ($nome === '') {
        $erro = 'Informe o nome do Grupo Familiar.';
    } elseif ($repo->buscarPorNomeExcetoId($nome, $grupoId) !== null) {
        $erro = 'Já existe outro Grupo Familiar cadastrado com esse nome.';
    } elseif ($diaSemana === '') {
        $erro = 'Selecione o dia da semana.';
    } elseif ($horario === '') {
        $erro = 'Informe o horário.';
    } elseif ($localFixo === 1 && $localPadrao === '') {
        $erro = 'Para GF com local fixo, informe o local padrão.';
    } elseif (count($lideresIds) === 0) {
        $erro = 'Selecione ao menos um líder.';
    } else {
        try {
            $repo->atualizar($grupoId, $nome, $diaSemana, $horario, $localPadrao, $localFixo, $lideresIds, $membrosIds);
            $mensagem = 'Grupo Familiar atualizado com sucesso.';

            $auditoria->registrar(
                'atualizar',
                'grupo_familiar',
                $grupoId,
                "Grupo Familiar atualizado: {$nome}.",
                null,
                $grupoId,
                null
            );

            $grupo = $repo->buscarPorId($grupoId);
            $lideresSelecionados = $repo->listarLideresIdsDoGrupo($grupoId);
            $membrosSelecionados = $repo->listarMembrosIdsDoGrupo($grupoId);
        } catch (Exception $e) {
            $erro = $e->getMessage();
        }
    }

    if ($erro !== '') {
        $grupo['nome'] = $nome;
        $grupo['dia_semana'] = $diaSemana;
        $grupo['horario'] = $horario;
        $grupo['local_padrao'] = $localPadrao;
        $grupo['local_fixo'] = $localFixo;

        $lideresSelecionados = $lideresIds;
        $membrosSelecionados = $membrosIds;
    }
}

$dias = ['segunda-feira', 'terça-feira', 'quarta-feira', 'quinta-feira', 'sexta-feira', 'sábado', 'domingo'];

$pageTitle = 'Editar Grupo Familiar - JTRO';

require_once __DIR__ . '/../src/Views/grupos_familiares/editar.php';