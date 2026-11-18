<?php
namespace App\Core;

use PDO;

/**
 * One shared database connection (PDO).
 * Every query in the app uses prepared statements, so user input is never
 * pasted into SQL. That is what blocks SQL injection (NFR03 in Chapter 4).
 */
class Database
{
    private static ?PDO $pdo = null;

    public static function connect(): PDO
    {
        if (self::$pdo === null) {
            $c = Config::get('db');
            self::$pdo = new PDO($c['dsn'], $c['user'], $c['password'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // show errors instead of failing silently
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,                    // real prepared statements
            ]);
        }
        return self::$pdo;
    }

    /** Run a query with values bound safely. Returns the statement. */
    public static function run(string $sql, array $params = [])
    {
        $stmt = self::connect()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function one(string $sql, array $params = []): ?array
    {
        $row = self::run($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    public static function all(string $sql, array $params = []): array
    {
        return self::run($sql, $params)->fetchAll();
    }

    public static function lastId(): int
    {
        return (int) self::connect()->lastInsertId();
    }
}
