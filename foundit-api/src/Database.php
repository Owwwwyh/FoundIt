<?php
// ----------------------------------------------------------------------
// PDO database connection (single shared instance).
// Owner: M1.  Use App\Database::pdo() anywhere you need the database.
// ----------------------------------------------------------------------

namespace App;

use PDO;

class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $_ENV['DB_HOST'], $_ENV['DB_PORT'], $_ENV['DB_NAME']
            );
            self::$pdo = new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASS'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // throw on error
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,         // rows as arrays
                PDO::ATTR_EMULATE_PREPARES   => false,                   // real prepared statements
            ]);
        }
        return self::$pdo;
    }
}
