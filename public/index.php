<?php

require_once __DIR__ . '/../src/Core/Database.php';

try {
    $connection = Database::getConnection();

    echo "<h1>Conexão com o banco realizada com sucesso!</h1>";
    echo "<p>O JTRO já consegue acessar o SQLite.</p>";
} catch (Exception $e) {
    echo "<h1>Erro ao conectar com o banco</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
}