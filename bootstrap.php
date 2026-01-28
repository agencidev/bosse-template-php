<?php
/**
 * Bootstrap - Laddar config och sätter upp miljön
 */

// Ladda config
$configPath = __DIR__ . '/config.php';
if (!file_exists($configPath)) {
    die('config.php saknas. Kopiera config.example.php till config.php');
}
require_once $configPath;

// Sätt paths
define('ROOT_PATH', __DIR__);
define('DATA_PATH', ROOT_PATH . '/data');
define('UPLOADS_PATH', ROOT_PATH . '/uploads');
define('ASSETS_PATH', ROOT_PATH . '/assets');

// Debug-läge
define('DEBUG', ENVIRONMENT === 'development');

// Error reporting
if (DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Session
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', 1);
session_start();
