<?php

require_once __DIR__ . '/src/Core/Database.php';

$pdo = Database::getConnection();

function tableExists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare("SELECT name FROM sqlite_master WHERE type = 'table' AND name = :table LIMIT 1");
    $stmt->execute([':table' => $table]);
    return (bool) $stmt->fetchColumn();
}

function hasColumn(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->query("PRAGMA table_info($table)");
    $columns = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

    foreach ($columns as $info) {
        if (($info['name'] ?? '') === $column) {
            return true;
        }
    }

    return false;
}

function addColumnIfMissing(PDO $pdo, string $table, string $column, string $definition): void
{
    if (!tableExists($pdo, $table)) {
        echo "Tabela $table nao existe.\n";
        return;
    }

    if (hasColumn($pdo, $table, $column)) {
        echo "[$table] coluna '$column' ja existe.\n";
        return;
    }

    $pdo->exec("ALTER TABLE $table ADD COLUMN $column $definition");
    echo "[$table] coluna '$column' adicionada.\n";
}

try {
    // 1) presencas.pontualidade
    addColumnIfMissing($pdo, 'presencas', 'pontualidade', "TEXT DEFAULT 'no_horario'");

    // 2) data_entrada/data_saida em tabela de membros do GF
    if (tableExists($pdo, 'membros_gf')) {
        addColumnIfMissing($pdo, 'membros_gf', 'data_entrada', 'DATE');
        addColumnIfMissing($pdo, 'membros_gf', 'data_saida', 'DATE');
    }

    if (tableExists($pdo, 'grupo_membros')) {
        addColumnIfMissing($pdo, 'grupo_membros', 'data_entrada', 'DATE');
        addColumnIfMissing($pdo, 'grupo_membros', 'data_saida', 'DATE');
    }

    // 3) colunas padrao em grupos_familiares
    addColumnIfMissing($pdo, 'grupos_familiares', 'local_padrao', 'TEXT');
    addColumnIfMissing($pdo, 'grupos_familiares', 'horario_padrao', 'TEXT');
    addColumnIfMissing($pdo, 'grupos_familiares', 'data_inicio', 'DATE');

    // 4) reunioes.local
    addColumnIfMissing($pdo, 'reunioes', 'local', 'TEXT');

    echo "\nMigracao de relatorios concluida.\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'Erro na migracao: ' . $e->getMessage() . "\n");
    exit(1);
}
