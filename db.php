<?php
/**
 * Database Connection Bridge (Backward Compatibility)
 * FOT Knights Arena — Chess Tournament
 */

require_once __DIR__ . '/src/Database.php';

function get_db_connection(): ?PDO {
    try {
        return Database::getConnection();
    } catch (PDOException $e) {
        error_log("Database connection error: " . $e->getMessage());
        return null;
    }
}

// Global variable support for legacy includes
$pdo = get_db_connection();
