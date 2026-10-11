<?php
/**
 * Database Singleton Connection
 * FOT Knights Arena — Chess Tournament
 */

class Database {
    private static ?PDO $instance = null;

    /**
     * Get the active PDO database connection
     */
    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $config = require __DIR__ . '/../config/database.php';
            
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $config['host'],
                $config['port'],
                $config['dbname'],
                $config['charset']
            );

            try {
                self::$instance = new PDO($dsn, $config['username'], $config['password'], [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false
                ]);
            } catch (PDOException $e) {
                // If database does not exist yet, attempt initialization
                if ($e->getCode() == 1049) {
                    require_once __DIR__ . '/Migrations.php';
                    Migrations::initDatabase();
                    return self::getConnection();
                }
                throw $e;
            }
        }

        return self::$instance;
    }

    /**
     * Prevent instantiation
     */
    private function __construct() {}
    private function __clone() {}
}
