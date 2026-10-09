<?php
// The ONE place where the database connection lives. Every page loads it through bootstrap.php.
class Database
{
    private static $pdo = null;

    public static function connect(): PDO
    {
        if (self::$pdo === null) {
            self::$pdo = new PDO(
                'mysql:host=127.0.0.1;port=3306;dbname=recipe_hub;charset=utf8mb4',
                'root',
                '',
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,       // failed queries throw
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,  // rows come back as arrays
                    PDO::ATTR_EMULATE_PREPARES => false,               // real prepared statements
                ]
            );
        }
        return self::$pdo;
    }

    // Runs $work as one all-or-nothing package: commit if it works, roll back if it throws.
    public static function transaction(callable $work)
    {
        $pdo = self::connect();
        $pdo->beginTransaction();
        try {
            $result = $work();
            $pdo->commit();
            return $result;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}