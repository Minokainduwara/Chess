<?php
require 'db.php';
$pdo = get_db_connection();

$pdo->exec("
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
    FOREIGN KEY (`round_number`) REFERENCES `rounds`(`round_number`) ON DELETE CASCADE,
    FOREIGN KEY (`team_a_id`) REFERENCES `teams`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`team_b_id`) REFERENCES `teams`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `match_boards` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `match_id` INT NOT NULL,
    `board_number` INT NOT NULL,
    `team_a_member_id` INT,
    `team_b_member_id` INT,
    `result` VARCHAR(10) DEFAULT NULL,
    FOREIGN KEY (`match_id`) REFERENCES `matches`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
echo "DB Updated\n";
