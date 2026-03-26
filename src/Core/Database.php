<?php

date_default_timezone_set('America/Sao_Paulo');

class Database
{
    private static ?PDO $connection = null;
    private static bool $schemaInitialized = false;

    public static function getConnection(): PDO
    {
        if (self::$connection === null) {

            $storagePath = __DIR__ . '/../../storage';
            $databasePath = $storagePath . '/database.sqlite';

            // cria pasta storage se não existir
            if (!is_dir($storagePath)) {
                mkdir($storagePath, 0777, true);
            }

            // cria arquivo sqlite se não existir
            if (!file_exists($databasePath)) {
                touch($databasePath);
            }

            $dsn = 'sqlite:' . $databasePath;

            self::$connection = new PDO($dsn);
            self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            self::$connection->exec('PRAGMA foreign_keys = ON');

            self::initializeSchema(self::$connection);
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
}
