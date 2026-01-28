<?php
/**
 * Configuration
 * Kopiera till config.php och fyll i värden
 */

// Site
define('SITE_URL', 'https://example.com');
define('SITE_NAME', 'Site Name');
define('SITE_DESCRIPTION', 'Beskrivning');
define('CONTACT_EMAIL', 'info@example.com');

// Admin (generera hash: php bin/generate-password-hash.php)
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD_HASH', '$2y$10$...');

// Security (generera: php bin/generate-secret.php)
define('SESSION_SECRET', '...');
define('CSRF_TOKEN_SALT', '...');

// Environment
define('ENVIRONMENT', 'production');
