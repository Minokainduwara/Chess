<?php
/**
 * Database Migrations & Schema Initialization
 * FOT Knights Arena — Chess Tournament
 */

class Migrations {

    /**
     * Initialize database if it does not exist
     */
    public static function initDatabase(): void {
        $config = require __DIR__ . '/../config/database.php';
        $dsnServer = sprintf('mysql:host=%s;port=%d;charset=%s', $config['host'], $config['port'], $config['charset']);
        
        $pdoServer = new PDO($dsnServer, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        $pdoServer->exec("CREATE DATABASE IF NOT EXISTS `{$config['dbname']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        
        self::run();
    }

    /**
     * Run all idempotent schema migrations
     */
    public static function run(): void {
        $pdo = Database::getConnection();

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `teams` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `team_name` VARCHAR(150) NOT NULL UNIQUE,
                `contact_phone` VARCHAR(50) DEFAULT NULL,
                `contact_email` VARCHAR(150) DEFAULT NULL,
                `password_hash` VARCHAR(255) DEFAULT NULL,
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
                `schedule_date` VARCHAR(50) DEFAULT NULL,
                `schedule_time` VARCHAR(50) DEFAULT NULL,
                `schedule_status` VARCHAR(50) DEFAULT NULL,
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

            CREATE TABLE IF NOT EXISTS `matches` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `round_number` INT NOT NULL,
                `team_a_id` INT NOT NULL,
                `team_b_id` INT NOT NULL,
                `status` ENUM('draft', 'scheduled', 'completed') NOT NULL DEFAULT 'draft',
                `team_a_gp` DECIMAL(3,1) DEFAULT 0.0,
                `team_b_gp` DECIMAL(3,1) DEFAULT 0.0,
                `team_a_mp` INT DEFAULT 0,
                `team_b_mp` INT DEFAULT 0,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT `fk_match_team_a` FOREIGN KEY (`team_a_id`) REFERENCES `teams`(`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_match_team_b` FOREIGN KEY (`team_b_id`) REFERENCES `teams`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS `match_boards` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `match_id` INT NOT NULL,
                `board_number` INT NOT NULL,
                `team_a_member_id` INT DEFAULT NULL,
                `team_b_member_id` INT DEFAULT NULL,
                `result` ENUM('1-0', '0.5-0.5', '0-1') DEFAULT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT `fk_board_match` FOREIGN KEY (`match_id`) REFERENCES `matches`(`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_board_member_a` FOREIGN KEY (`team_a_member_id`) REFERENCES `members`(`id`) ON DELETE SET NULL,
                CONSTRAINT `fk_board_member_b` FOREIGN KEY (`team_b_member_id`) REFERENCES `members`(`id`) ON DELETE SET NULL,
                UNIQUE KEY `unique_match_board` (`match_id`, `board_number`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS `arbiters` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(150) NOT NULL,
                `role` VARCHAR(100) NOT NULL DEFAULT 'Arbiter',
                `fide_id` VARCHAR(50) DEFAULT NULL,
                `contact` VARCHAR(150) DEFAULT NULL,
                `display_order` INT NOT NULL DEFAULT 1,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // Migrate arbiters table columns if older schema exists
        try {
            $cols = [];
            $stmtCols = $pdo->query("SHOW COLUMNS FROM `arbiters`");
            while ($c = $stmtCols->fetch(PDO::FETCH_ASSOC)) {
                $cols[strtolower($c['Field'])] = true;
            }
            if (!isset($cols['name'])) {
                $pdo->exec("ALTER TABLE `arbiters` ADD COLUMN `name` VARCHAR(150) NOT NULL AFTER `id`");
            }
            if (!isset($cols['role'])) {
                $pdo->exec("ALTER TABLE `arbiters` ADD COLUMN `role` VARCHAR(100) NOT NULL DEFAULT 'Arbiter' AFTER `name`");
            }
            if (!isset($cols['fide_id'])) {
                $pdo->exec("ALTER TABLE `arbiters` ADD COLUMN `fide_id` VARCHAR(50) DEFAULT NULL AFTER `role`");
            }
            if (!isset($cols['contact'])) {
                $pdo->exec("ALTER TABLE `arbiters` ADD COLUMN `contact` VARCHAR(150) DEFAULT NULL AFTER `fide_id`");
            }
            if (!isset($cols['display_order'])) {
                $pdo->exec("ALTER TABLE `arbiters` ADD COLUMN `display_order` INT NOT NULL DEFAULT 1 AFTER `contact`");
            }
            if (!isset($cols['created_at'])) {
                $pdo->exec("ALTER TABLE `arbiters` ADD COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
            }
        } catch (PDOException $e) {
            // Ignore if already applied
        }

        // Seed default settings if missing
        $pdo->exec("
            INSERT IGNORE INTO `tournament_settings` (`setting_key`, `setting_value`) VALUES
            ('scoreboard_visible', '1'),
            ('scoreboard_status', 'Standings updated live after each round'),
            ('countdown_enabled', '1'),
            ('countdown_target', '2026-10-10 07:00:00'),
            ('registration_open', '1'),
            ('registration_closed_message', 'Official team registration for FOT Knights Arena is now closed.'),
            ('mp_win', '2'),
            ('mp_draw', '1'),
            ('mp_loss', '0'),
            ('mp_loss_zero_gp', '-1');
        ");

        // Seed default admin if missing
        $stmt = $pdo->query("SELECT COUNT(*) AS total FROM `admins`");
        if ((int)$stmt->fetchColumn() === 0) {
            $defaultHash = password_hash('admin123', PASSWORD_DEFAULT);
            $stmtInsert = $pdo->prepare("INSERT INTO `admins` (`username`, `password_hash`) VALUES ('admin', ?)");
            $stmtInsert->execute([$defaultHash]);
        }
    }
}
