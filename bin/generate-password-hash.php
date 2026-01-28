#!/usr/bin/env php
<?php
/**
 * Generera säker lösenords-hash
 * Användning: php bin/generate-password-hash.php [lösenord]
 */

echo "Lösenords-hash Generator\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// Hämta lösenord från argument eller fråga
if (isset($argv[1])) {
    $password = $argv[1];
} else {
    echo "Ange lösenord: ";
    $password = trim(fgets(STDIN));
}

if (empty($password)) {
    echo "Fel: Lösenord kan inte vara tomt.\n";
    exit(1);
}

// Generera hash med bcrypt (PASSWORD_DEFAULT)
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "Din lösenords-hash:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo $hash . "\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "Lägg till i din .env fil:\n";
echo "ADMIN_PASSWORD_HASH=" . $hash . "\n\n";

echo "VIKTIGT:\n";
echo "- Spara aldrig lösenordet i klartext\n";
echo "- Använd ett starkt lösenord (minst 12 tecken)\n";
echo "- Spara aldrig hash i version control\n";
