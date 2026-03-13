<?php

require_once __DIR__ . '/../src/Core/Database.php';

$db = Database::getConnection();

$schemaPath = __DIR__ . '/schema.sql';

if (!file_exists($schemaPath)) {
    die('Arquivo schema.sql não encontrado.');
}

$sql = file_get_contents($schemaPath);

if ($sql === false) {
    die('Não foi possível ler o schema.sql.');
}

$db->exec($sql);

echo "Banco de dados inicializado com sucesso.";