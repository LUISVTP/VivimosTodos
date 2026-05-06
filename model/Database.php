<?php

declare(strict_types=1);

class Database
{
    private static ?PDO $connection = null;

    public static function getConnection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $host = 'aws-1-us-west-2.pooler.supabase.com';
        $puerto = '5432';
        $basedatos = 'postgres';
        $usuario = 'postgres.afckeqbqrsccxukgnclr';
        $password = '14deJULIO200';

        $dsn = "pgsql:host={$host};port={$puerto};dbname={$basedatos};sslmode=require";

        self::$connection = new PDO($dsn, $usuario, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        return self::$connection;
    }
}
