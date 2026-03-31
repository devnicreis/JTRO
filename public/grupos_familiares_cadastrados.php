<?php

require_once __DIR__ . '/../src/Core/Auth.php';
require_once __DIR__ . '/../src/Repositories/GrupoFamiliarRepository.php';

Auth::requireLogin();
Auth::requireSenhaAtualizada();
Auth::requireAdmin();

$repo = new GrupoFamiliarRepository();

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

$grupos = $repo->listarTodos($filtros);
$pageTitle = 'GFs Cadastrados - JTRO';

require_once __DIR__ . '/../src/Views/grupos_familiares/listagem.php';
