<?php

require_once __DIR__ . '/../src/Repositories/PessoaRepository.php';

require_once __DIR__ . '/../src/Core/Auth.php';
Auth::requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /pessoas_cadastradas.php');
    exit;
}

$id = $_POST['id'] ?? null;

if (!$id) {
    header('Location: /pessoas_cadastradas.php');
    exit;
}

$repo = new PessoaRepository();
$repo->reativar((int) $id);

header('Location: /pessoas_cadastradas.php');
exit;
