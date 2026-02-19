<?php
/**
 * Bootstrap - Laddar config och sätter upp miljön
 */

// Ladda config
$configPath = __DIR__ . '/config.php';
if (!file_exists($configPath)) {
    $currentUri = $_SERVER['REQUEST_URI'] ?? '';
    if (!str_contains($currentUri, '/setup')
        && basename($_SERVER['SCRIPT_FILENAME'] ?? '') !== 'setup.php') {
        header('Location: /setup');
        exit;
    }
    exit;
}
require_once $configPath;

// Ladda version
if (file_exists(__DIR__ . '/includes/version.php')) {
    require_once __DIR__ . '/includes/version.php';
} elseif (file_exists(__DIR__ . '/version.php')) {
    require_once __DIR__ . '/version.php';
} else {
    define('BOSSE_VERSION', '0.0.0');
    define('BOSSE_VERSION_DATE', '');
}

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
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
    || (!empty($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', $isHttps ? 1 : 0);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.use_strict_mode', 1);
session_start();

// Vary-header: se till att cacher (SiteGround SuperCacher) skiljer på inloggade/utloggade
header('Vary: Cookie');

// Ladda super admin (efter session start)
require_once __DIR__ . '/security/super-admin.php';
