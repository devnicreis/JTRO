<?php

require_once __DIR__ . '/../src/Repositories/GrupoFamiliarRepository.php';

require_once __DIR__ . '/../src/Core/Auth.php';
Auth::requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /grupos_familiares.php');
    exit;
}

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

if ($id <= 0) {
    header('Location: /grupos_familiares.php');
    exit;
}

$repo = new GrupoFamiliarRepository();
$repo->desativar($id);

header('Location: /grupos_familiares.php');
exit;