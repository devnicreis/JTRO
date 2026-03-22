<?php
$db = new PDO('sqlite:storage/database.sqlite');

try {
    $db->exec("
        CREATE TABLE IF NOT EXISTS eventos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            titulo TEXT NOT NULL,
            data TEXT NOT NULL,
            hora_inicio TEXT NOT NULL,
            hora_fim TEXT,
            departamento TEXT NOT NULL,
            descricao TEXT,
            recorrente INTEGER NOT NULL DEFAULT 0,
            regra_recorrencia TEXT,
            criado_por INTEGER NOT NULL,
            created_at TEXT NOT NULL,
            updated_at TEXT,
            FOREIGN KEY (criado_por) REFERENCES pessoas(id)
        )
    ");
    echo "Tabela eventos criada.\n";
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
echo "Concluido.\n";