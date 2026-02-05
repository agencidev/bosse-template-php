<?php
/**
 * Build Release — Skapar release-ZIP + manifest.json for bosse-updates
 *
 * Anvandning:
 *   php bin/build-release.php [version] [hmac-key]
 *
 * Exempel:
 *   php bin/build-release.php 1.1.0
 *   php bin/build-release.php 1.1.0 my-hmac-secret-key
 *
 * Output hamnar i: dist/
 *   dist/releases/1.1.0.zip
 *   dist/manifest.json
 *
 * Kopiera hela dist/-mappen till agencidev/bosse-updates-repot och pusha.
 */

// --- Konfiguration ---

$templateRoot = dirname(__DIR__); // apps/template/php/

// Core-filer som ingar i uppdateringen (samma lista som UPDATABLE_FILES i updater.php)
$updatableFiles = [
    'bootstrap.php', 'router.php', 'setup.php', 'version.php',
    '.htaccess', '403.php', '404.php', '500.php', 'robots.php',
    'site.webmanifest',
    'cms/admin.php', 'cms/dashboard.php', 'cms/content.php',
    'cms/api.php', 'cms/api-super.php', 'cms/super-admin.php',
    'cms/seo.php', 'cms/support.php', 'cms/ai.php',
    'cms/projects/index.php', 'cms/projects/new.php', 'cms/projects/edit.php',
    'security/csrf.php', 'security/session.php', 'security/validation.php',
    'security/super-admin.php', 'security/updater.php',
    'seo/meta.php', 'seo/schema.php', 'seo/sitemap.php',
    'includes/admin-bar.php', 'includes/cookie-consent.php',
    'includes/mailer.php', 'includes/agenci-badge.php',
    'assets/css/reset.css', 'assets/css/cms.css',
    'assets/js/cms.js',
];

// Hela mappar som ingar
$updatableDirs = ['bin', 'templates', 'assets/images/cms'];

// --- Argument ---

$version = $argv[1] ?? null;
$hmacKey = $argv[2] ?? '';

if (!$version) {
    // Las version fran version.php
    $versionFile = $templateRoot . '/version.php';
    if (file_exists($versionFile)) {
        $content = file_get_contents($versionFile);
        if (preg_match("/BOSSE_VERSION',\s*'([^']+)'/", $content, $m)) {
            $version = $m[1];
        }
    }
}

if (!$version) {
    echo "Anvandning: php bin/build-release.php [version] [hmac-key]\n";
    echo "Exempel:    php bin/build-release.php 1.1.0\n";
    exit(1);
}

echo "Bygger release v{$version}...\n";

// --- Skapa dist-struktur ---

$distDir = $templateRoot . '/dist';
$releasesDir = $distDir . '/releases';

if (!is_dir($releasesDir)) {
    mkdir($releasesDir, 0755, true);
}

$zipPath = $releasesDir . '/' . $version . '.zip';

// --- Skapa ZIP ---

if (!class_exists('ZipArchive')) {
    echo "FEL: ZipArchive-tillaget saknas.\n";
    exit(1);
}

$zip = new ZipArchive();
$res = $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
if ($res !== true) {
    echo "FEL: Kunde inte skapa ZIP (kod: {$res})\n";
    exit(1);
}

$fileCount = 0;

// Lagg till enskilda filer
foreach ($updatableFiles as $file) {
    $fullPath = $templateRoot . '/' . $file;
    if (file_exists($fullPath)) {
        $zip->addFile($fullPath, $file);
        $fileCount++;
    } else {
        echo "  VARNING: {$file} saknas, hoppas over\n";
    }
}

// Lagg till hela mappar
foreach ($updatableDirs as $dir) {
    $dirPath = $templateRoot . '/' . $dir;
    if (is_dir($dirPath)) {
        addDirectoryToZip($zip, $dirPath, $dir, $fileCount);
    }
}

// Lagg till migrationer om de finns
$migrationsDir = $templateRoot . '/_migrations';
if (is_dir($migrationsDir)) {
    addDirectoryToZip($zip, $migrationsDir, '_migrations', $fileCount);
    echo "  Migrationer inkluderade\n";
}

$zip->close();

echo "  ZIP skapad: {$zipPath} ({$fileCount} filer)\n";

// --- Berakna signatur ---

$signature = '';
if (!empty($hmacKey)) {
    $hash = hash_hmac('sha256', file_get_contents($zipPath), $hmacKey);
    $signature = 'sha256:' . $hash;
    echo "  Signatur: {$signature}\n";
} else {
    echo "  Ingen HMAC-nyckel angiven — ZIP ar osignerad\n";
}

// --- Hitta migrationer ---

$migrations = [];
if (is_dir($migrationsDir)) {
    foreach (scandir($migrationsDir) as $file) {
        if (preg_match('/^(\d+\.\d+\.\d+)\.php$/', $file, $m)) {
            $migrations[] = $m[1];
        }
    }
    usort($migrations, 'version_compare');
}

// --- Generera manifest.json ---

$manifest = [
    'latest_version' => $version,
    'release_date' => date('Y-m-d'),
    'min_php_version' => '8.0',
    'changelog' => '',
    'download_url' => 'https://raw.githubusercontent.com/agencidev/bosse-updates/main/releases/' . $version . '.zip',
    'signature' => $signature,
    'migrations' => $migrations,
    'critical' => false,
];

// Forsok lasa changelog fran CHANGELOG.md
$changelogFile = $templateRoot . '/CHANGELOG.md';
if (file_exists($changelogFile)) {
    $changelogContent = file_get_contents($changelogFile);
    // Hamta senaste version-blocket
    if (preg_match('/##\s*' . preg_quote($version) . '.*?\n(.*?)(?=\n##|\z)/s', $changelogContent, $m)) {
        $manifest['changelog'] = trim($m[1]);
    }
}

$manifestPath = $distDir . '/manifest.json';
file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

echo "  Manifest: {$manifestPath}\n";
echo "\nKlar! Kopiera dist/ till agencidev/bosse-updates-repot:\n";
echo "  cp -r dist/* /path/to/bosse-updates/\n";
echo "  cd /path/to/bosse-updates && git add . && git commit -m 'Release v{$version}' && git push\n";

// --- Hjalpfunktion ---

function addDirectoryToZip(ZipArchive $zip, string $srcDir, string $zipDir, int &$count): void {
    $items = scandir($srcDir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..' || $item === '.DS_Store') {
            continue;
        }
        $fullPath = $srcDir . '/' . $item;
        $zipPath = $zipDir . '/' . $item;
        if (is_dir($fullPath)) {
            addDirectoryToZip($zip, $fullPath, $zipPath, $count);
        } else {
            $zip->addFile($fullPath, $zipPath);
            $count++;
        }
    }
}
