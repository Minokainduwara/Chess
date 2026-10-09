<?php
require 'db.php';
try {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=chess_tournament", "root", "");
    $pdo->exec('ALTER TABLE `rounds` ADD COLUMN `schedule_date` VARCHAR(50) DEFAULT NULL, ADD COLUMN `schedule_time` VARCHAR(50) DEFAULT NULL, ADD COLUMN `schedule_status` VARCHAR(50) DEFAULT NULL;');
    echo "Columns added";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
