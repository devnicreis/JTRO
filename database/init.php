<?php

require_once __DIR__ . '/../src/Core/Database.php';

$db = Database::getConnection();

$sql = file_get_contents(__DIR__ . '/schema.sql');

$db->exec($sql);

echo "Banco de dados inicializado com sucesso.";

$stmt = $db->query("SELECT name FROM sqlite_master WHERE type='table'");

$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

print_r($tables);