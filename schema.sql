-- Chess Tournament Database Schema
-- University of Ruhuna, Faculty of Technology

CREATE DATABASE IF NOT EXISTS `chess_tournament`
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `chess_tournament`;

-- 1. Teams Table
-- teams.status lifecycle:
--   'pending'  (default) - hidden from public standings until admin approval
--   'approved'           - listed in the public standings
--   'rejected'           - hidden from public standings
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

-- 2. Tournament Settings Table
CREATE TABLE IF NOT EXISTS `tournament_settings` (
  `setting_key` VARCHAR(50) PRIMARY KEY,
  `setting_value` TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Team Members Table
-- Rules enforced:
-- - 4 to 6 members per team
-- - At least 1 female per team
-- - At least 2 distinct batch years per team
--
-- tournament_settings keys:
--   scoreboard_visible  '1' | '0'   show/hide public standings
--   scoreboard_status   free text    note shown beside standings
--   countdown_enabled   '1' | '0'   show/hide public countdown
--   countdown_target    'Y-m-d H:i:s' tournament start (Asia/Colombo)
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

-- 3. Administrators Table
CREATE TABLE IF NOT EXISTS `admins` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default Admin Credential:
-- Username: admin
-- Password: admin123
INSERT IGNORE INTO `admins` (`id`, `username`, `password_hash`)
VALUES (1, 'admin', '$2y$12$VBuPlK3tVI0prP4l//Q0s.R5mSnA5nYob6voVdrrTfh0umJC6ZV66');

-- 4. Rounds Table
CREATE TABLE IF NOT EXISTS `rounds` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `round_number` INT NOT NULL UNIQUE,
  `status` ENUM('open', 'closed') NOT NULL DEFAULT 'open',
  `deadline` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Drop Boards Table
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
