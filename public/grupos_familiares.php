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
    } elseif ($repo->buscarPorNome($nome) !== null) {
        $erro = 'Já existe um Grupo Familiar cadastrado com esse nome.';
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
            $repo->salvar($nome, $diaSemana, $horario, $localPadrao, $localFixo, $lideresIds, $membrosIds);
            $mensagem = 'Grupo Familiar cadastrado com sucesso.';

            $grupoCriado = $repo->buscarPorNome($nome);

            if ($grupoCriado) {
                $auditoria->registrar(
                    'criar',
                    'grupo_familiar',
                    $grupoCriado['id'],
                    "Grupo Familiar criado: {$nome}.",
                    null,
                    $grupoCriado['id'],
                    null
                );
            }
        } catch (Exception $e) {
            $erro = $e->getMessage();
        }
    }
}

$pessoas = $repo->listarPessoasAtivas();
$grupos = $repo->listarTodos();

$pageTitle = 'Cadastro de Grupos Familiares - JTRO';

require_once __DIR__ . '/../src/Views/grupos_familiares/index.php';