#!/usr/bin/env php
<?php
/**
 * Automatiska tester för projektstart
 * Kör detta script efter att ha klonat bosse-template-php
 * 
 * Användning: php bin/test-setup.php
 */

// ANSI färgkoder
define('GREEN', "\033[0;32m");
define('RED', "\033[0;31m");
define('YELLOW', "\033[1;33m");
define('BLUE', "\033[0;34m");
define('RESET', "\033[0m");

$tests_passed = 0;
$tests_failed = 0;
$warnings = 0;

echo BLUE . "\n🧪 Kör automatiska tester för bosse-template-php\n" . RESET;
echo str_repeat("=", 60) . "\n\n";

/**
 * Test helper functions
 */
function test($name, $callback) {
    global $tests_passed, $tests_failed;
    
    echo "Testing: $name ... ";
    
    try {
        $result = $callback();
        if ($result === true) {
            echo GREEN . "✓ PASS" . RESET . "\n";
            $tests_passed++;
            return true;
        } else {
            echo RED . "✗ FAIL" . RESET;
            if (is_string($result)) {
                echo " - " . $result;
            }
            echo "\n";
            $tests_failed++;
            return false;
        }
    } catch (Exception $e) {
        echo RED . "✗ ERROR: " . $e->getMessage() . RESET . "\n";
        $tests_failed++;
        return false;
    }
}

function warn($message) {
    global $warnings;
    echo YELLOW . "⚠ WARNING: $message" . RESET . "\n";
    $warnings++;
}

/**
 * Tests
 */

// 1. Filstruktur
echo BLUE . "\n📁 Filstruktur\n" . RESET;
echo str_repeat("-", 60) . "\n";

test("config.example.php finns", function() {
    return file_exists(__DIR__ . '/../config.example.php');
});

test("setup.php finns", function() {
    return file_exists(__DIR__ . '/../setup.php');
});

test("templates/brand-guide-template.md finns", function() {
    return file_exists(__DIR__ . '/../templates/brand-guide-template.md');
});

test("templates/ai-rules-template.md finns", function() {
    return file_exists(__DIR__ . '/../templates/ai-rules-template.md');
});

test("router.php finns", function() {
    return file_exists(__DIR__ . '/../router.php');
});

test(".htaccess finns", function() {
    return file_exists(__DIR__ . '/../.htaccess');
});

test("assets/ mapp finns", function() {
    return is_dir(__DIR__ . '/../assets');
});

test("includes/ mapp finns", function() {
    return is_dir(__DIR__ . '/../includes');
});

test("cms/ mapp finns", function() {
    return is_dir(__DIR__ . '/../cms');
});

test("includes/mailer.php finns", function() {
    return file_exists(__DIR__ . '/../includes/mailer.php');
});

test("pages/ mapp finns", function() {
    return is_dir(__DIR__ . '/../pages');
});

test("pages/kontakt.php finns", function() {
    return file_exists(__DIR__ . '/../pages/kontakt.php');
});

test("data/ mapp finns", function() {
    return is_dir(__DIR__ . '/../data');
});

test("public/uploads/ mapp finns", function() {
    $dir = __DIR__ . '/../public/uploads';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
        return "Skapade uploads-mapp";
    }
    return true;
});

// 2. Konfiguration
echo BLUE . "\n⚙️  Konfiguration\n" . RESET;
echo str_repeat("-", 60) . "\n";

test("bootstrap.php finns", function() {
    return file_exists(__DIR__ . '/../bootstrap.php');
});

test("config.example.php är giltig PHP", function() {
    // Kolla att config.example.php är giltig syntax (utan att köra den)
    $output = [];
    $return = 0;
    exec('php -l ' . escapeshellarg(__DIR__ . '/../config.example.php') . ' 2>&1', $output, $return);
    return $return === 0;
});

// Ladda config.php om den finns (för att kunna testa konfiguration)
$configFile = __DIR__ . '/../config.php';
$hasConfig = file_exists($configFile);
if ($hasConfig) {
    require_once $configFile;
}

test("config.php finns", function() use ($hasConfig) {
    if (!$hasConfig) {
        warn("config.php saknas - kopiera config.example.php till config.php");
        return false;
    }
    return true;
});

if ($hasConfig) {
    test("SITE_NAME är definierad", function() {
        return defined('SITE_NAME') && !empty(SITE_NAME);
    });

    test("CONTACT_EMAIL är definierad", function() {
        return defined('CONTACT_EMAIL') && !empty(CONTACT_EMAIL);
    });

    test("ADMIN_USERNAME är definierad", function() {
        if (!defined('ADMIN_USERNAME') || ADMIN_USERNAME === 'admin') {
            warn("Använd inte standardanvändarnamnet 'admin'");
            return false;
        }
        return true;
    });

    test("SESSION_SECRET är definierad", function() {
        if (!defined('SESSION_SECRET') || strlen(SESSION_SECRET) < 32) {
            return "SESSION_SECRET måste vara minst 32 tecken";
        }
        return true;
    });

    test("CSRF_TOKEN_SALT är definierad", function() {
        if (!defined('CSRF_TOKEN_SALT') || strlen(CSRF_TOKEN_SALT) < 32) {
            return "CSRF_TOKEN_SALT måste vara minst 32 tecken";
        }
        return true;
    });

    test("SMTP_HOST är definierad", function() {
        if (!defined('SMTP_HOST')) {
            warn("SMTP_HOST är inte definierad — e-post fungerar inte");
            return false;
        }
        return !empty(SMTP_HOST);
    });

    test("SMTP_PORT är definierad", function() {
        if (!defined('SMTP_PORT')) {
            warn("SMTP_PORT är inte definierad — e-post fungerar inte");
            return false;
        }
        return SMTP_PORT > 0 && SMTP_PORT <= 65535;
    });
} else {
    echo YELLOW . "⚠ Hoppar över config-tester (config.php saknas)\n" . RESET;
}

// 2b. Setup-genererade filer
$setupComplete = file_exists(__DIR__ . '/../data/.setup-complete');
if ($setupComplete) {
    echo BLUE . "\n🔧 Setup-genererade filer\n" . RESET;
    echo str_repeat("-", 60) . "\n";

    test(".rules/brand-guide.md finns", function() {
        return file_exists(__DIR__ . '/../.rules/brand-guide.md');
    });

    test(".rules/ai-rules.md finns", function() {
        return file_exists(__DIR__ . '/../.rules/ai-rules.md');
    });

    test("includes/fonts.php finns", function() {
        return file_exists(__DIR__ . '/../includes/fonts.php');
    });

    test("data/.setup-complete finns", function() {
        return file_exists(__DIR__ . '/../data/.setup-complete');
    });
}

// 3. Säkerhet
echo BLUE . "\n🔒 Säkerhet\n" . RESET;
echo str_repeat("-", 60) . "\n";

test(".gitignore finns", function() {
    return file_exists(__DIR__ . '/../.gitignore');
});

test(".env är i .gitignore", function() {
    $gitignore = file_get_contents(__DIR__ . '/../.gitignore');
    return str_contains($gitignore, '.env');
});

test("data/content.json är skrivbar", function() {
    $file = __DIR__ . '/../data/content.json';
    if (!file_exists($file)) {
        file_put_contents($file, '{}');
    }
    return is_writable($file);
});

test("public/uploads/ är skrivbar", function() {
    return is_writable(__DIR__ . '/../public/uploads');
});

test("PHP-exekvering är blockerad i uploads", function() {
    $htaccess = __DIR__ . '/../uploads/.htaccess';
    if (!file_exists($htaccess)) {
        return "uploads/.htaccess saknas";
    }
    $content = file_get_contents($htaccess);
    return str_contains($content, 'Require all denied');
});

// 4. Assets
echo BLUE . "\n🎨 Assets\n" . RESET;
echo str_repeat("-", 60) . "\n";

test("CSS-filer finns", function() {
    return file_exists(__DIR__ . '/../assets/css/variables.css') &&
           file_exists(__DIR__ . '/../assets/css/components.css');
});

test("JavaScript-filer finns", function() {
    return file_exists(__DIR__ . '/../assets/js/cms.js');
});

test("Logotyper finns", function() {
    $light = file_exists(__DIR__ . '/../assets/images/logo-light.png');
    $dark = file_exists(__DIR__ . '/../assets/images/logo-dark.png');
    
    if (!$light || !$dark) {
        warn("Glöm inte att ersätta logotyperna med kundens logotyper");
    }
    
    return $light && $dark;
});

// 5. CMS-komponenter
echo BLUE . "\n📝 CMS-komponenter\n" . RESET;
echo str_repeat("-", 60) . "\n";

test("admin-bar.php finns", function() {
    return file_exists(__DIR__ . '/../includes/admin-bar.php');
});

test("cookie-consent.php finns", function() {
    return file_exists(__DIR__ . '/../includes/cookie-consent.php');
});

test("agenci-badge.php finns", function() {
    return file_exists(__DIR__ . '/../includes/agenci-badge.php');
});

test("session.php finns", function() {
    return file_exists(__DIR__ . '/../security/session.php');
});

test("csrf.php finns", function() {
    return file_exists(__DIR__ . '/../security/csrf.php');
});

// 6. CMS-sidor
echo BLUE . "\n📄 CMS-sidor\n" . RESET;
echo str_repeat("-", 60) . "\n";

test("admin.php (login) finns", function() {
    return file_exists(__DIR__ . '/../cms/admin.php');
});

test("dashboard.php finns", function() {
    return file_exists(__DIR__ . '/../cms/dashboard.php');
});

test("projects/index.php finns", function() {
    return file_exists(__DIR__ . '/../cms/projects/index.php');
});

test("projects/new.php finns", function() {
    return file_exists(__DIR__ . '/../cms/projects/new.php');
});

test("support.php finns", function() {
    return file_exists(__DIR__ . '/../cms/support.php');
});

test("seo.php finns", function() {
    return file_exists(__DIR__ . '/../cms/seo.php');
});

test("ai.php finns", function() {
    return file_exists(__DIR__ . '/../cms/ai.php');
});

// 7. URL-routing
echo BLUE . "\n🔗 URL-routing\n" . RESET;
echo str_repeat("-", 60) . "\n";

test("router.php är giltig", function() {
    $router = file_get_contents(__DIR__ . '/../router.php');
    return str_contains($router, '/admin') &&
           str_contains($router, '/dashboard');
});

test("router.php har /kontakt-route", function() {
    $router = file_get_contents(__DIR__ . '/../router.php');
    return str_contains($router, "'/kontakt'");
});

test(".htaccess har rewrite-regler", function() {
    $htaccess = file_get_contents(__DIR__ . '/../.htaccess');
    return str_contains($htaccess, 'RewriteEngine On');
});

// Sammanfattning
echo BLUE . "\n" . str_repeat("=", 60) . RESET . "\n";
echo BLUE . "📊 Testresultat\n" . RESET;
echo str_repeat("=", 60) . "\n\n";

$total = $tests_passed + $tests_failed;
$percentage = $total > 0 ? round(($tests_passed / $total) * 100) : 0;

echo "Totalt antal tester: " . BLUE . $total . RESET . "\n";
echo GREEN . "✓ Godkända: $tests_passed" . RESET . "\n";
echo RED . "✗ Misslyckade: $tests_failed" . RESET . "\n";
echo YELLOW . "⚠ Varningar: $warnings" . RESET . "\n";
echo "\nFramgångsgrad: " . ($percentage >= 90 ? GREEN : ($percentage >= 70 ? YELLOW : RED)) . "$percentage%" . RESET . "\n\n";

if ($tests_failed === 0 && $warnings === 0) {
    echo GREEN . "🎉 Alla tester godkända! Projektet är redo att användas.\n" . RESET;
    exit(0);
} elseif ($tests_failed === 0) {
    echo YELLOW . "⚠️  Alla tester godkända men det finns varningar. Kontrollera dessa innan produktion.\n" . RESET;
    exit(0);
} else {
    echo RED . "❌ Några tester misslyckades. Åtgärda problemen innan du fortsätter.\n" . RESET;
    exit(1);
}
