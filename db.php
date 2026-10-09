<?php
/**
 * Database Connection & Auto-Initialization
 * University of Ruhuna - Faculty of Technology
 * FOT Knights Arena — Chess Tournament
 */

$db_host = '127.0.0.1';
$db_port = 3306;
$db_name = 'chess_tournament';
$db_user = 'root';
$db_pass = '';

try {
    // First, connect to MySQL server to ensure database exists
    $dsn_server = "mysql:host={$db_host};port={$db_port};charset=utf8mb4";
    $pdo_server = new PDO($dsn_server, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    $pdo_server->exec("CREATE DATABASE IF NOT EXISTS `{$db_name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

    // Connect to the specific database
    $dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4";
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);

    // Ensure tables exist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `teams` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `team_name` VARCHAR(150) NOT NULL UNIQUE,
            `contact_phone` VARCHAR(50) DEFAULT NULL,
            `contact_email` VARCHAR(150) DEFAULT NULL,
            `status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
            `played` INT DEFAULT 0,
            `won` INT DEFAULT 0,
            `drawn` INT DEFAULT 0,
            `lost` INT DEFAULT 0,
            `game_points` DECIMAL(5,1) DEFAULT 0.0,
            `match_points` INT DEFAULT 0,
            `tiebreak_sb` DECIMAL(6,2) DEFAULT 0.00,
            `standing_notes` VARCHAR(100) DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS `members` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `team_id` INT NOT NULL,
            `member_order` INT NOT NULL,
            `name` VARCHAR(150) NOT NULL,
            `reg_number` VARCHAR(50) NOT NULL UNIQUE,
            `gender` ENUM('Male', 'Female') NOT NULL,
            `batch_year` VARCHAR(10) NOT NULL,
            `is_captain` TINYINT(1) DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT `fk_team_member` FOREIGN KEY (`team_id`) REFERENCES `teams`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS `admins` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `username` VARCHAR(50) NOT NULL UNIQUE,
            `password_hash` VARCHAR(255) NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS `tournament_settings` (
            `setting_key` VARCHAR(50) PRIMARY KEY,
            `setting_value` TEXT NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS `rounds` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `round_number` INT NOT NULL UNIQUE,
            `label` VARCHAR(100) DEFAULT NULL,
            `status` ENUM('open', 'closed') NOT NULL DEFAULT 'open',
            `deadline` DATETIME DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS `drop_boards` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `team_id` INT NOT NULL,
            `round_number` INT NOT NULL,
            `member_id` INT NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT `fk_drop_team` FOREIGN KEY (`team_id`) REFERENCES `teams`(`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_drop_round` FOREIGN KEY (`round_number`) REFERENCES `rounds`(`round_number`) ON DELETE CASCADE,
            CONSTRAINT `fk_drop_member` FOREIGN KEY (`member_id`) REFERENCES `members`(`id`) ON DELETE CASCADE,
            UNIQUE KEY `unique_drop` (`team_id`, `round_number`, `member_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // Run safe column migrations if table already existed previously
    $pdo->exec("
        ALTER TABLE `teams`
            ADD COLUMN IF NOT EXISTS `password_hash` VARCHAR(255) DEFAULT NULL,
            ADD COLUMN IF NOT EXISTS `played` INT DEFAULT 0,
            ADD COLUMN IF NOT EXISTS `won` INT DEFAULT 0,
            ADD COLUMN IF NOT EXISTS `drawn` INT DEFAULT 0,
            ADD COLUMN IF NOT EXISTS `lost` INT DEFAULT 0,
            ADD COLUMN IF NOT EXISTS `game_points` DECIMAL(5,1) DEFAULT 0.0,
            ADD COLUMN IF NOT EXISTS `match_points` INT DEFAULT 0,
            ADD COLUMN IF NOT EXISTS `tiebreak_sb` DECIMAL(6,2) DEFAULT 0.00,
            ADD COLUMN IF NOT EXISTS `standing_notes` VARCHAR(100) DEFAULT NULL;
    ");

    $pdo->exec("
        ALTER TABLE `rounds`
            ADD COLUMN IF NOT EXISTS `label` VARCHAR(100) DEFAULT NULL;
    ");

    // Approval workflow: newly registered teams get status 'pending' and stay
    // hidden from the public standings until an admin approves them.
    $status_col_exists = false;
    try {
        $stmt_col = $pdo->query("
            SELECT COUNT(*) FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'teams'
              AND COLUMN_NAME = 'status'
        ");
        $status_col_exists = ((int)$stmt_col->fetchColumn()) > 0;
    } catch (PDOException $e) {
        $status_col_exists = false;
    }

    $pdo->exec("
        ALTER TABLE `teams`
            ADD COLUMN IF NOT EXISTS `status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending' AFTER `contact_email`;
    ");

    // One-time upgrade: teams registered before this feature existed are
    // grandfathered in as 'approved' so they stay visible in standings.
    if (!$status_col_exists) {
        $pdo->exec("UPDATE `teams` SET `status` = 'approved'");
    }

    // Seed default settings
    $pdo->exec("
        INSERT IGNORE INTO `tournament_settings` (`setting_key`, `setting_value`) VALUES
        ('scoreboard_visible', '1'),
        ('scoreboard_status', 'Standings updated live after each round'),
        ('countdown_enabled', '1'),
        ('countdown_target', '2026-10-10 07:00:00');
    ");

    // Seed default admin if none exists (Username: admin, Password: admin123)
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM `admins`");
    if ($stmt->fetch()['total'] == 0) {
        $default_hash = password_hash('admin123', PASSWORD_DEFAULT);
        $insert_admin = $pdo->prepare("INSERT INTO `admins` (`username`, `password_hash`) VALUES ('admin', ?)");
        $insert_admin->execute([$default_hash]);
    }

} catch (PDOException $e) {
    // If running in CLI or web, expose a clean message
    $db_error = $e->getMessage();
}

function get_db_connection() {
    global $pdo, $db_error;
    if (isset($pdo)) {
        return $pdo;
    }
    return null;
}
