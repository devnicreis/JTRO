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
    $perfilGrupo = trim($_POST['perfil_grupo'] ?? '');
    $localPadrao = trim($_POST['local_padrao'] ?? '');
    $localFixo = isset($_POST['local_fixo']) ? 1 : 0;
    $itemCeleiro = trim($_POST['item_celeiro'] ?? '');
    $domingoOracaoCulto = (int) ($_POST['domingo_oracao_culto'] ?? 0);
    $lideresIds = array_map('intval', $_POST['lideres'] ?? []);
    $membrosIds = array_map('intval', $_POST['membros'] ?? []);

    $perfisValidos = ['casais', 'jovens', 'teen', 'mulheres', 'integracao'];

    if ($nome === '') {
        $erro = 'Informe o nome do Grupo Familiar.';
    } elseif ($repo->buscarPorNome($nome) !== null) {
        $erro = 'Já existe um Grupo Familiar cadastrado com esse nome.';
    } elseif ($diaSemana === '') {
        $erro = 'Selecione o dia da semana.';
    } elseif ($horario === '') {
        $erro = 'Informe o horário.';
    } elseif (!in_array($perfilGrupo, $perfisValidos, true)) {
        $erro = 'Selecione um perfil de grupo válido.';
    } elseif ($localFixo === 1 && $localPadrao === '') {
        $erro = 'Para GF com local fixo, informe o local padrão.';
    } elseif (count($lideresIds) === 0) {
        $erro = 'Selecione ao menos um líder.';
    } else {
        try {
            $repo->salvar(
                $nome,
                $diaSemana,
                $horario,
                $perfilGrupo,
                $localPadrao,
                $localFixo,
                $itemCeleiro,
                $domingoOracaoCulto,
                $lideresIds,
                $membrosIds
            );
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

            $_POST = [];
        } catch (Exception $e) {
            $erro = $e->getMessage();
        }
    }
}

$filtros = [
    'id' => trim($_GET['id'] ?? ''),
    'nome' => trim($_GET['nome'] ?? ''),
    'dia_semana' => trim($_GET['dia_semana'] ?? ''),
    'horario' => trim($_GET['horario'] ?? ''),
    'lideres' => trim($_GET['lideres'] ?? ''),
    'membros' => trim($_GET['membros'] ?? ''),
    'perfil_grupo' => trim($_GET['perfil_grupo'] ?? ''),
    'local_padrao' => trim($_GET['local_padrao'] ?? ''),
    'local_fixo' => trim($_GET['local_fixo'] ?? ''),
    'item_celeiro' => trim($_GET['item_celeiro'] ?? ''),
    'domingo_oracao_culto' => trim($_GET['domingo_oracao_culto'] ?? ''),
    'status' => trim($_GET['status'] ?? ''),
];

$pessoas = $repo->listarPessoasAtivas();
$grupos = $repo->listarTodos($filtros);

$pageTitle = 'Cadastro de Grupos Familiares - JTRO';

require_once __DIR__ . '/../src/Views/grupos_familiares/cadastro.php';
