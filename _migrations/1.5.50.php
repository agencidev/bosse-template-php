<?php
/**
 * Migration v1.5.50
 * - Uppdaterar pages/cookies.php: ersätter inline onclick med addEventListener
 * - Lägger till id="main-content" på <main> i skyddade sidor
 */

$rootPath = defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__);
$results = [];

// --- 1. Fix cookies.php inline onclick ---
$cookiesFile = $rootPath . '/pages/cookies.php';
if (file_exists($cookiesFile)) {
    $content = file_get_contents($cookiesFile);
    $changed = false;

    // Replace onclick button with id-based button
    if (str_contains($content, "onclick=\"document.getElementById('cookie-settings-modal')")) {
        $content = preg_replace(
            '/<button\s+onclick="document\.getElementById\(\'cookie-settings-modal\'\)\.style\.display=\'flex\';"/',
            '<button id="open-cookie-settings"',
            $content
        );
        $changed = true;
    }

    // Add addEventListener script block if not already present
    if ($changed && !str_contains($content, 'open-cookie-settings')) {
        // Fallback: the preg_replace above already added the id, but the script block is missing
    }
    if (!str_contains($content, "getElementById('open-cookie-settings')") && str_contains($content, 'id="open-cookie-settings"')) {
        $scriptBlock = <<<'SCRIPT'

    <script <?php echo csp_nonce_attr(); ?>>
    var openBtn = document.getElementById('open-cookie-settings');
    if (openBtn) {
        openBtn.addEventListener('click', function() {
            document.getElementById('cookie-settings-modal').style.display = 'flex';
        });
    }
    </script>
SCRIPT;

        // Insert before </body>
        $content = str_replace('</body>', $scriptBlock . "\n\n</body>", $content);
        $changed = true;
    }

    // Add id="main-content" to <main> if missing
    if (!str_contains($content, 'id="main-content"') && str_contains($content, '<main>')) {
        $content = str_replace('<main>', '<main id="main-content">', $content);
        $changed = true;
    }

    if ($changed) {
        file_put_contents($cookiesFile, $content, LOCK_EX);
        $results[] = 'cookies.php updated';
    } else {
        $results[] = 'cookies.php already up to date';
    }
} else {
    $results[] = 'cookies.php not found (skipped)';
}

// --- 2. Add id="main-content" to other protected pages ---
$protectedPages = [
    'index.php',
    'pages/kontakt.php',
    'pages/integritetspolicy.php',
];

foreach ($protectedPages as $page) {
    $filePath = $rootPath . '/' . $page;
    if (file_exists($filePath)) {
        $content = file_get_contents($filePath);
        if (!str_contains($content, 'id="main-content"') && str_contains($content, '<main>')) {
            $content = str_replace('<main>', '<main id="main-content">', $content);
            file_put_contents($filePath, $content, LOCK_EX);
            $results[] = $page . ' main-content id added';
        }
    }
}

return $results;
