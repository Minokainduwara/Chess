<?php
/**
 * Flash Message Helper
 * FOT Knights Arena — Chess Tournament
 */

class Flash {
    public static function init(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function setSuccess(string $message): void {
        self::init();
        $_SESSION['flash_success'] = $message;
    }

    public static function setError(string $message): void {
        self::init();
        $_SESSION['flash_error'] = $message;
    }

    public static function getSuccess(): ?string {
        self::init();
        $msg = $_SESSION['flash_success'] ?? null;
        unset($_SESSION['flash_success']);
        return $msg;
    }

    public static function getError(): ?string {
        self::init();
        $msg = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_error']);
        return $msg;
    }
}
