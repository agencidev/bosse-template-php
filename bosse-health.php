<?php
/**
 * Bosse Health Endpoint
 * Returns JSON with site health status for the Bosse Portal.
 * Accessible at /bosse-health (via .htaccess rewrite)
 */

// Prevent caching
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Content-Type: application/json');

// Allow portal to fetch (CORS)
header('Access-Control-Allow-Origin: *');

$health = [
    'status' => 'healthy',
    'timestamp' => time(),
    'php_version' => PHP_VERSION,
];

// Bosse version
if (file_exists(__DIR__ . '/includes/version.php')) {
    require_once __DIR__ . '/includes/version.php';
    $health['bosse_version'] = defined('BOSSE_VERSION') ? BOSSE_VERSION : 'unknown';
} else {
    $health['bosse_version'] = 'unknown';
}

// Config check
$health['config_exists'] = file_exists(__DIR__ . '/config.php');
$health['installed'] = file_exists(__DIR__ . '/.installed');

// Disk space
$freeBytes = @disk_free_space(__DIR__);
if ($freeBytes !== false) {
    $health['disk_free_mb'] = round($freeBytes / 1024 / 1024);
}

// PHP errors in log (check if error_log file exists and has recent entries)
$errorLog = ini_get('error_log');
if ($errorLog && file_exists($errorLog)) {
    $size = filesize($errorLog);
    $health['error_log_size_kb'] = round($size / 1024);
    // Read last few lines to count recent errors
    if ($size > 0 && $size < 5242880) { // Only read if < 5MB
        $lines = file($errorLog, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $today = date('d-M-Y');
        $todayErrors = 0;
        foreach (array_slice($lines, -200) as $line) {
            if (strpos($line, $today) !== false) $todayErrors++;
        }
        $health['errors_today'] = $todayErrors;
    }
}

// Writable checks
$health['writable'] = [
    'data' => is_writable(__DIR__ . '/data'),
    'uploads' => is_writable(__DIR__ . '/uploads'),
];

echo json_encode($health, JSON_PRETTY_PRINT);
