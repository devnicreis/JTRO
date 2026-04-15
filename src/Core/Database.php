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
        self::ensurePessoaEmailIsNotUnique($connection);
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

    private static function ensurePessoaEmailIsNotUnique(PDO $connection): void
    {
        if (!self::tableHasUniqueConstraintOnColumn($connection, 'pessoas', 'email')) {
            return;
        }

        $connection->exec('PRAGMA foreign_keys = OFF');
        $connection->beginTransaction();

        try {
            $connection->exec('
                CREATE TABLE pessoas_sem_email_unico (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    nome TEXT NOT NULL,
                    cpf TEXT NOT NULL UNIQUE,
                    email TEXT,
                    cargo TEXT NOT NULL,
                    genero TEXT,
                    data_nascimento TEXT,
                    estado_civil TEXT NOT NULL DEFAULT \'solteiro\',
                    nome_conjuge TEXT,
                    conjuge_cpf TEXT,
                    conjuge_pessoa_id INTEGER,
                    eh_lider INTEGER NOT NULL DEFAULT 0,
                    lider_grupo_familiar INTEGER NOT NULL DEFAULT 0,
                    lider_departamento INTEGER NOT NULL DEFAULT 0,
                    grupo_familiar_id INTEGER,
                    telefone_fixo TEXT,
                    telefone_movel TEXT,
                    endereco_cep TEXT,
                    endereco_logradouro TEXT,
                    endereco_numero TEXT,
                    endereco_complemento TEXT,
                    endereco_bairro TEXT,
                    endereco_cidade TEXT,
                    endereco_uf TEXT,
                    concluiu_integracao INTEGER NOT NULL DEFAULT 0,
                    integracao_conclusao_manual INTEGER NOT NULL DEFAULT 0,
                    participou_retiro_integracao INTEGER NOT NULL DEFAULT 0,
                    motivo_desativacao_tipo TEXT,
                    motivo_desativacao_detalhe TEXT,
                    motivo_desativacao_texto TEXT,
                    ativo INTEGER NOT NULL DEFAULT 1,
                    senha_hash TEXT,
                    precisa_trocar_senha INTEGER NOT NULL DEFAULT 1,
                    privacidade_aceita_em TEXT,
                    termos_versao_aceita TEXT,
                    politica_versao_aceita TEXT,
                    responsavel_1_cpf TEXT,
                    responsavel_1_nome TEXT,
                    responsavel_1_pessoa_id INTEGER,
                    responsavel_2_cpf TEXT,
                    responsavel_2_nome TEXT,
                    responsavel_2_pessoa_id INTEGER
                )
            ');

            $connection->exec('
                INSERT INTO pessoas_sem_email_unico (
                    id,
                    nome,
                    cpf,
                    email,
                    cargo,
                    genero,
                    data_nascimento,
                    estado_civil,
                    nome_conjuge,
                    conjuge_cpf,
                    conjuge_pessoa_id,
                    eh_lider,
                    lider_grupo_familiar,
                    lider_departamento,
                    grupo_familiar_id,
                    telefone_fixo,
                    telefone_movel,
                    endereco_cep,
                    endereco_logradouro,
                    endereco_numero,
                    endereco_complemento,
                    endereco_bairro,
                    endereco_cidade,
                    endereco_uf,
                    concluiu_integracao,
                    integracao_conclusao_manual,
                    participou_retiro_integracao,
                    motivo_desativacao_tipo,
                    motivo_desativacao_detalhe,
                    motivo_desativacao_texto,
                    ativo,
                    senha_hash,
                    precisa_trocar_senha,
                    privacidade_aceita_em,
                    termos_versao_aceita,
                    politica_versao_aceita,
                    responsavel_1_cpf,
                    responsavel_1_nome,
                    responsavel_1_pessoa_id,
                    responsavel_2_cpf,
                    responsavel_2_nome,
                    responsavel_2_pessoa_id
                )
                SELECT
                    id,
                    nome,
                    cpf,
                    email,
                    cargo,
                    genero,
                    data_nascimento,
                    estado_civil,
                    nome_conjuge,
                    conjuge_cpf,
                    conjuge_pessoa_id,
                    eh_lider,
                    lider_grupo_familiar,
                    lider_departamento,
                    grupo_familiar_id,
                    telefone_fixo,
                    telefone_movel,
                    endereco_cep,
                    endereco_logradouro,
                    endereco_numero,
                    endereco_complemento,
                    endereco_bairro,
                    endereco_cidade,
                    endereco_uf,
                    concluiu_integracao,
                    integracao_conclusao_manual,
                    participou_retiro_integracao,
                    motivo_desativacao_tipo,
                    motivo_desativacao_detalhe,
                    motivo_desativacao_texto,
                    ativo,
                    senha_hash,
                    precisa_trocar_senha,
                    privacidade_aceita_em,
                    termos_versao_aceita,
                    politica_versao_aceita,
                    responsavel_1_cpf,
                    responsavel_1_nome,
                    responsavel_1_pessoa_id,
                    responsavel_2_cpf,
                    responsavel_2_nome,
                    responsavel_2_pessoa_id
                FROM pessoas
            ');

            $connection->exec('DROP TABLE pessoas');
            $connection->exec('ALTER TABLE pessoas_sem_email_unico RENAME TO pessoas');
            $connection->exec('CREATE INDEX IF NOT EXISTS idx_pessoas_responsavel_1_pessoa_id ON pessoas(responsavel_1_pessoa_id)');
            $connection->exec('CREATE INDEX IF NOT EXISTS idx_pessoas_responsavel_2_pessoa_id ON pessoas(responsavel_2_pessoa_id)');
            $connection->exec('CREATE INDEX IF NOT EXISTS idx_pessoas_conjuge_pessoa_id ON pessoas(conjuge_pessoa_id)');

            $connection->commit();
        } catch (Throwable $e) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }

            throw $e;
        } finally {
            $connection->exec('PRAGMA foreign_keys = ON');
        }
    }

    private static function tableHasUniqueConstraintOnColumn(PDO $connection, string $table, string $column): bool
    {
        $stmt = $connection->query(sprintf('PRAGMA index_list(%s)', $table));
        $indexes = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        foreach ($indexes as $indexInfo) {
            if ((int) ($indexInfo['unique'] ?? 0) !== 1) {
                continue;
            }

            $indexName = $indexInfo['name'] ?? '';
            if ($indexName === '') {
                continue;
            }

            $stmtCols = $connection->query(sprintf('PRAGMA index_info(%s)', $indexName));
            $indexColumns = $stmtCols ? $stmtCols->fetchAll(PDO::FETCH_ASSOC) : [];

            if (count($indexColumns) !== 1) {
                continue;
            }

            if (($indexColumns[0]['name'] ?? '') === $column) {
                return true;
            }
        }

        return false;
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
