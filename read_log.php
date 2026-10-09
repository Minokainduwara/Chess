<?php
$log = '/Applications/XAMPP/xamppfiles/logs/php_error_log';
if (file_exists($log)) {
    echo "Last 20 lines of php_error_log:\n";
    $lines = file($log);
    $last = array_slice($lines, -20);
    echo implode("", $last);
} else {
    echo "Log file not found.";
}
