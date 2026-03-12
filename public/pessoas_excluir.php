<?php

require_once __DIR__ . '/../src/Core/Database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /pessoas.php');
    exit;
}

$id = $_POST['id'] ?? null;

if (!$id) {
    header('Location: /pessoas.php');
    exit;
}

$db = Database::getConnection();

$stmt = $db->prepare("DELETE FROM pessoas WHERE id = :id");
$stmt->execute([
    ':id' => $id
]);

header('Location: /pessoas.php');
exit;