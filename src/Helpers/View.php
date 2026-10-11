<?php
/**
 * View Rendering Helper
 * FOT Knights Arena — Chess Tournament
 */

class View {
    /**
     * Render a view template with extracted variables
     */
    public static function render(string $template, array $data = []): void {
        extract($data);
        $file = __DIR__ . '/../../views/' . ltrim($template, '/') . '.php';
        if (file_exists($file)) {
            require $file;
        } else {
            throw new RuntimeException("View template not found: {$template} ({$file})");
        }
    }
}
