<?php
/**
 * Arbiter Service
 * FOT Knights Arena — Chess Tournament
 */

require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../Migrations.php';

class ArbiterService {

    /**
     * Get all arbiters ordered by display_order ASC, id ASC
     * Self-heals by running table/column schema migration if outdated.
     */
    public static function getAllArbiters(): array {
        self::ensureTable();
        $pdo = Database::getConnection();
        try {
            $stmt = $pdo->query("SELECT * FROM `arbiters` ORDER BY `display_order` ASC, `id` ASC");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            // Normalize columns to prevent undefined index notices
            return array_map(function($r) {
                return [
                    'id'            => (int)($r['id'] ?? 0),
                    'name'          => (string)($r['name'] ?? ''),
                    'role'          => (string)($r['role'] ?? 'Arbiter'),
                    'fide_id'       => $r['fide_id'] ?? null,
                    'contact'       => $r['contact'] ?? null,
                    'display_order' => (int)($r['display_order'] ?? 1),
                    'created_at'    => $r['created_at'] ?? null,
                ];
            }, $rows);
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Get single arbiter by ID
     */
    public static function getArbiterById(int $id): ?array {
        self::ensureTable();
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM `arbiters` WHERE `id` = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;
        return [
            'id'            => (int)($row['id'] ?? 0),
            'name'          => (string)($row['name'] ?? ''),
            'role'          => (string)($row['role'] ?? 'Arbiter'),
            'fide_id'       => $row['fide_id'] ?? null,
            'contact'       => $row['contact'] ?? null,
            'display_order' => (int)($row['display_order'] ?? 1),
            'created_at'    => $row['created_at'] ?? null,
        ];
    }

    /**
     * Add a new arbiter
     */
    public static function createArbiter(string $name, string $role, ?string $fideId = null, ?string $contact = null, int $displayOrder = 1): bool {
        self::ensureTable();
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            INSERT INTO `arbiters` (`name`, `role`, `fide_id`, `contact`, `display_order`)
            VALUES (?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            trim($name),
            trim($role) ?: 'Arbiter',
            trim($fideId) ?: null,
            trim($contact) ?: null,
            $displayOrder
        ]);
    }

    /**
     * Update an arbiter
     */
    public static function updateArbiter(int $id, string $name, string $role, ?string $fideId = null, ?string $contact = null, int $displayOrder = 1): bool {
        self::ensureTable();
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            UPDATE `arbiters`
            SET `name` = ?, `role` = ?, `fide_id` = ?, `contact` = ?, `display_order` = ?
            WHERE `id` = ?
        ");
        return $stmt->execute([
            trim($name),
            trim($role) ?: 'Arbiter',
            trim($fideId) ?: null,
            trim($contact) ?: null,
            $displayOrder,
            $id
        ]);
    }

    /**
     * Delete an arbiter
     */
    public static function deleteArbiter(int $id): bool {
        self::ensureTable();
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("DELETE FROM `arbiters` WHERE `id` = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Self-healing: Ensure table and all required columns exist in the database
     */
    public static function ensureTable(): void {
        try {
            $pdo = Database::getConnection();
            $pdo->exec("
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

            // Introspect columns to automatically migrate older schemas
            $columns = [];
            $stmt = $pdo->query("SHOW COLUMNS FROM `arbiters`");
            while ($col = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $columns[strtolower($col['Field'])] = true;
            }

            if (!isset($columns['name'])) {
                $pdo->exec("ALTER TABLE `arbiters` ADD COLUMN `name` VARCHAR(150) NOT NULL AFTER `id`");
            }
            if (!isset($columns['role'])) {
                $pdo->exec("ALTER TABLE `arbiters` ADD COLUMN `role` VARCHAR(100) NOT NULL DEFAULT 'Arbiter' AFTER `name`");
            }
            if (!isset($columns['fide_id'])) {
                $pdo->exec("ALTER TABLE `arbiters` ADD COLUMN `fide_id` VARCHAR(50) DEFAULT NULL AFTER `role`");
            }
            if (!isset($columns['contact'])) {
                $pdo->exec("ALTER TABLE `arbiters` ADD COLUMN `contact` VARCHAR(150) DEFAULT NULL AFTER `fide_id`");
            }
            if (!isset($columns['display_order'])) {
                $pdo->exec("ALTER TABLE `arbiters` ADD COLUMN `display_order` INT NOT NULL DEFAULT 1 AFTER `contact`");
            }
            if (!isset($columns['created_at'])) {
                $pdo->exec("ALTER TABLE `arbiters` ADD COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
            }
        } catch (PDOException $e) {
            // Silently fall back if migration fails
        }
    }
}
