<?php

date_default_timezone_set('America/Sao_Paulo');

class Database
{
    private static ?PDO $connection = null;

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
        }

        return self::$connection;
    }
}