<?php

require_once __DIR__ . '/../src/Repositories/PessoaRepository.php';
require_once __DIR__ . '/../src/Repositories/GrupoFamiliarRepository.php';
require_once __DIR__ . '/../src/Core/Auth.php';

Auth::requireAdmin();
Auth::requireSenhaAtualizada();

$repo = new PessoaRepository();
$grupoRepo = new GrupoFamiliarRepository();

$filtros = [
    'id' => trim($_GET['id'] ?? ''),
    'nome' => trim($_GET['nome'] ?? ''),
    'cpf' => trim($_GET['cpf'] ?? ''),
    'email' => trim($_GET['email'] ?? ''),
    'cargo' => trim($_GET['cargo'] ?? ''),
    'genero' => trim($_GET['genero'] ?? ''),
    'data_nascimento' => trim($_GET['data_nascimento'] ?? ''),
    'estado_civil' => trim($_GET['estado_civil'] ?? ''),
    'nome_conjuge' => trim($_GET['nome_conjuge'] ?? ''),
    'eh_lider' => trim($_GET['eh_lider'] ?? ''),
    'lider_grupo_familiar' => trim($_GET['lider_grupo_familiar'] ?? ''),
    'lider_departamento' => trim($_GET['lider_departamento'] ?? ''),
    'grupo_familiar_id' => trim($_GET['grupo_familiar_id'] ?? ''),
    'telefone' => trim($_GET['telefone'] ?? ''),
    'endereco' => trim($_GET['endereco'] ?? ''),
    'concluiu_integracao' => trim($_GET['concluiu_integracao'] ?? ''),
    'participou_retiro_integracao' => trim($_GET['participou_retiro_integracao'] ?? ''),
    'status' => trim($_GET['status'] ?? ''),
];

$pessoas = $repo->listarTodos($filtros);
$gruposFamiliares = $grupoRepo->listarAtivos();
$pageTitle = 'Pessoas Cadastradas - JTRO';

require_once __DIR__ . '/../src/Views/pessoas/listagem_v2.php';
