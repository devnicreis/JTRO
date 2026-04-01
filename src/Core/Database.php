<?php

date_default_timezone_set('America/Sao_Paulo');

class Database
{
    private static ?PDO $connection = null;
    private static bool $schemaInitialized = false;
    private static bool $legacyMigrationsApplied = false;

    public static function getConnection(): PDO
    {
        if (self::$connection === null) {

            $storagePath = __DIR__ . '/../../storage';
            $databasePath = $storagePath . '/database.sqlite';

            // cria pasta storage se não existir
            if (!is_dir($storagePath)) {
                mkdir($storagePath, 0750, true);
            }

            if (function_exists('chmod')) {
                @chmod($storagePath, 0750);
            }

            // cria arquivo sqlite se não existir
            if (!file_exists($databasePath)) {
                touch($databasePath);
            }

            if (function_exists('chmod')) {
                @chmod($databasePath, 0640);
            }

            $dsn = 'sqlite:' . $databasePath;

            self::$connection = new PDO($dsn);
            self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            self::$connection->exec('PRAGMA foreign_keys = ON');

            self::initializeSchema(self::$connection);
            self::applyLegacyMigrations(self::$connection);
        }

        return self::$connection;
    }

    private static function initializeSchema(PDO $connection): void
    {
        if (self::$schemaInitialized) {
            return;
        }

        $schemaPath = __DIR__ . '/../../database/schema.sql';
        if (!file_exists($schemaPath)) {
            throw new RuntimeException('Arquivo de schema do banco não encontrado.');
        }

        $sql = file_get_contents($schemaPath);
        if ($sql === false) {
            throw new RuntimeException('Não foi possível ler o schema do banco.');
        }

        $connection->exec($sql);
        self::$schemaInitialized = true;
    }

    private static function applyLegacyMigrations(PDO $connection): void
    {
        if (self::$legacyMigrationsApplied) {
            return;
        }

        self::ensurePessoaAddressColumns($connection);
        self::ensurePessoaPrivacyColumns($connection);
        self::ensurePessoaResponsibleColumns($connection);
        self::ensurePessoaGenderAndSpouseColumns($connection);
        self::$legacyMigrationsApplied = true;
    }

    private static function ensurePessoaAddressColumns(PDO $connection): void
    {
        $addressColumns = [
            'endereco_cep' => 'TEXT',
            'endereco_logradouro' => 'TEXT',
            'endereco_numero' => 'TEXT',
            'endereco_complemento' => 'TEXT',
            'endereco_bairro' => 'TEXT',
            'endereco_cidade' => 'TEXT',
            'endereco_uf' => 'TEXT',
        ];

        foreach ($addressColumns as $column => $type) {
            if (self::tableHasColumn($connection, 'pessoas', $column)) {
                continue;
            }

            $connection->exec(sprintf('ALTER TABLE pessoas ADD COLUMN %s %s', $column, $type));
        }
    }

    private static function ensurePessoaPrivacyColumns(PDO $connection): void
    {
        $privacyColumns = [
            'privacidade_aceita_em' => 'TEXT',
            'termos_versao_aceita' => 'TEXT',
            'politica_versao_aceita' => 'TEXT',
        ];

        foreach ($privacyColumns as $column => $type) {
            if (self::tableHasColumn($connection, 'pessoas', $column)) {
                continue;
            }

            $connection->exec(sprintf('ALTER TABLE pessoas ADD COLUMN %s %s', $column, $type));
        }
    }

    private static function ensurePessoaResponsibleColumns(PDO $connection): void
    {
        $responsibleColumns = [
            'responsavel_1_cpf' => 'TEXT',
            'responsavel_1_nome' => 'TEXT',
            'responsavel_1_pessoa_id' => 'INTEGER',
            'responsavel_2_cpf' => 'TEXT',
            'responsavel_2_nome' => 'TEXT',
            'responsavel_2_pessoa_id' => 'INTEGER',
        ];

        foreach ($responsibleColumns as $column => $type) {
            if (self::tableHasColumn($connection, 'pessoas', $column)) {
                continue;
            }

            $connection->exec(sprintf('ALTER TABLE pessoas ADD COLUMN %s %s', $column, $type));
        }

        $connection->exec('CREATE INDEX IF NOT EXISTS idx_pessoas_responsavel_1_pessoa_id ON pessoas(responsavel_1_pessoa_id)');
        $connection->exec('CREATE INDEX IF NOT EXISTS idx_pessoas_responsavel_2_pessoa_id ON pessoas(responsavel_2_pessoa_id)');
    }

    private static function ensurePessoaGenderAndSpouseColumns(PDO $connection): void
    {
        $columns = [
            'genero' => 'TEXT',
            'conjuge_cpf' => 'TEXT',
            'conjuge_pessoa_id' => 'INTEGER',
        ];

        foreach ($columns as $column => $type) {
            if (self::tableHasColumn($connection, 'pessoas', $column)) {
                continue;
            }

            $connection->exec(sprintf('ALTER TABLE pessoas ADD COLUMN %s %s', $column, $type));
        }

        $connection->exec('CREATE INDEX IF NOT EXISTS idx_pessoas_conjuge_pessoa_id ON pessoas(conjuge_pessoa_id)');
    }

    private static function tableHasColumn(PDO $connection, string $table, string $column): bool
    {
        $stmt = $connection->query(sprintf('PRAGMA table_info(%s)', $table));
        $columns = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        foreach ($columns as $info) {
            if (($info['name'] ?? null) === $column) {
                return true;
            }
        }

        return false;
    }
}
