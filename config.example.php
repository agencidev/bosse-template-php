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
define('CONTACT_PHONE', '+46 70 000 00 00');

// SMTP
define('SMTP_HOST', 'smtp.example.com');
define('SMTP_PORT', 465);
define('SMTP_ENCRYPTION', 'ssl');
define('SMTP_USERNAME', 'user@example.com');
define('SMTP_PASSWORD', '...');

// Admin (generera hash: php bin/generate-password-hash.php)
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD_HASH', '$2y$10$...');

// Security (generera: php bin/generate-secret.php)
define('SESSION_SECRET', '...');
define('CSRF_TOKEN_SALT', '...');

// Google Analytics
define('GOOGLE_ANALYTICS_ID', 'G-XXXXXXXXXX');

// Sociala medier
define('SOCIAL_FACEBOOK', 'https://facebook.com/foretagsnamn');
define('SOCIAL_INSTAGRAM', 'https://instagram.com/foretagsnamn');
define('SOCIAL_LINKEDIN', 'https://linkedin.com/company/foretagsnamn');

// Öppettider
define('HOURS_WEEKDAYS', '08:00 - 17:00');
define('HOURS_WEEKENDS', 'Stängt');

// Environment
define('ENVIRONMENT', 'production');

// Agenci (satts vid installation, synlig bara i config.php)
define('AGENCI_SUPER_ADMIN_TOKEN', ''); // Tom = inaktiverad
define('AGENCI_UPDATE_URL', 'https://raw.githubusercontent.com/agenci/bosse-updates/main');
define('AGENCI_UPDATE_KEY', ''); // HMAC-nyckel for verifiering
