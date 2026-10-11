<?php
/**
 * Authentication Service (Admin & Captain)
 * FOT Knights Arena — Chess Tournament
 */

require_once __DIR__ . '/../Database.php';

class AuthService {
    public static function startSession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function isAdminLoggedIn(): bool {
        self::startSession();
        return !empty($_SESSION['admin_logged_in']);
    }

    public static function getAdminUser(): ?string {
        self::startSession();
        return $_SESSION['admin_user'] ?? null;
    }

    public static function loginAdmin(string $username, string $password): bool {
        self::startSession();
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("SELECT * FROM `admins` WHERE `username` = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password_hash'])) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_user'] = $admin['username'];
            return true;
        }

        return false;
    }

    public static function logoutAdmin(): void {
        self::startSession();
        unset($_SESSION['admin_logged_in'], $_SESSION['admin_user']);
    }

    public static function isCaptainLoggedIn(): bool {
        self::startSession();
        return !empty($_SESSION['captain_team_id']);
    }

    public static function getCaptainTeamId(): ?int {
        self::startSession();
        return isset($_SESSION['captain_team_id']) ? (int)$_SESSION['captain_team_id'] : null;
    }

    public static function getCaptainTeamName(): ?string {
        self::startSession();
        return $_SESSION['captain_team_name'] ?? null;
    }

    public static function loginCaptain(string $teamName, string $password): bool {
        self::startSession();
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("SELECT * FROM `teams` WHERE `team_name` = ?");
        $stmt->execute([$teamName]);
        $team = $stmt->fetch();

        if ($team && !empty($team['password_hash']) && password_verify($password, $team['password_hash'])) {
            $_SESSION['captain_team_id'] = (int)$team['id'];
            $_SESSION['captain_team_name'] = $team['team_name'];
            return true;
        }

        return false;
    }

    public static function logoutCaptain(): void {
        self::startSession();
        unset($_SESSION['captain_team_id'], $_SESSION['captain_team_name']);
    }
}
