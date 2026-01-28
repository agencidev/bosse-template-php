#!/usr/bin/env php
<?php
/**
 * Generera säker CSRF secret
 * Användning: php bin/generate-secret.php
 */

echo "🔐 Genererar ny CSRF secret...\n\n";

$secret = bin2hex(random_bytes(32));

echo "Din nya CSRF secret:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo $secret . "\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "Uppdatera SESSION_SECRET och CSRF_TOKEN_SALT i config.php med detta värde.\n";
echo "✓ Spara aldrig detta värde i version control!\n";
