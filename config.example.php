<?php
/**
 * Configuration File
 * Kopiera till config.php och uppdatera värden
 */

// Load environment variables from .env
function loadEnv($path) {
    if (!file_exists($path)) {
        die('.env file not found. Copy .env.example to .env');
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        if (!array_key_exists($name, $_ENV)) {
            $_ENV[$name] = $value;
        }
    }
}

// Load .env file
loadEnv(__DIR__ . '/.env');

// Site Configuration
define('SITE_URL', $_ENV['SITE_URL'] ?? 'https://example.com');
define('SITE_NAME', $_ENV['SITE_NAME'] ?? 'Site Name');
define('SITE_DESCRIPTION', $_ENV['SITE_DESCRIPTION'] ?? 'Site description');

// Admin Configuration
define('ADMIN_USERNAME', $_ENV['ADMIN_USERNAME'] ?? 'admin');
// VIKTIGT: Lösenord måste vara hashat med password_hash()
// Generera hash: php -r "echo password_hash('ditt-lösenord', PASSWORD_DEFAULT);"
define('ADMIN_PASSWORD_HASH', $_ENV['ADMIN_PASSWORD_HASH'] ?? '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'); // default: password

// Security
define('SESSION_SECRET', $_ENV['SESSION_SECRET'] ?? 'change-this-secret');
define('CSRF_TOKEN_SALT', $_ENV['CSRF_TOKEN_SALT'] ?? 'change-this-salt');

// Environment
define('ENVIRONMENT', $_ENV['ENVIRONMENT'] ?? 'development');
define('DEBUG', ENVIRONMENT === 'development');

// Paths
define('ROOT_PATH', __DIR__);
define('ASSETS_PATH', ROOT_PATH . '/assets');
define('UPLOADS_PATH', ROOT_PATH . '/uploads');
define('DATA_PATH', ROOT_PATH . '/data');

// Error Reporting
if (DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Session Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', 1);

// Start session
session_start();
