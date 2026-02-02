<?php
/**
 * Uppdateringssystem for Bosse Template
 * Hanterar versionskontroll, nedladdning, backup och applicering
 */

// Filer som far skrivas over vid uppdatering
const UPDATABLE_FILES = [
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

// Wildcard-matchade uppdateringsbara mappar
const UPDATABLE_DIRS = ['bin', 'templates'];

// Filer som ALDRIG rors
const PROTECTED_FILES = [
    'config.php', '.env',
    'data/content.json', 'data/projects.json',
    'assets/css/variables.css', 'assets/css/overrides.css',
    'assets/css/main.css', 'assets/css/components.css',
    'includes/header.php', 'includes/footer.php', 'includes/fonts.php',
    'index.php', 'kontakt.php', 'cookies.php', 'integritetspolicy.php',
];

// Wildcard-skyddade mappar
const PROTECTED_DIRS = ['assets/images', 'uploads'];

/**
 * Kolla om en fil ar uppdateringsbar
 */
function is_updatable_file(string $relativePath): bool {
    // Blocka path traversal
    if (strpos($relativePath, '..') !== false || $relativePath[0] === '/') {
        return false;
    }

    // Kolla exakt match i UPDATABLE_FILES
    if (in_array($relativePath, UPDATABLE_FILES, true)) {
        return true;
    }

    // Kolla wildcard-mappar
    foreach (UPDATABLE_DIRS as $dir) {
        if (strpos($relativePath, $dir . '/') === 0) {
            return true;
        }
    }

    return false;
}

/**
 * Kolla om en fil ar skyddad
 */
function is_protected_file(string $relativePath): bool {
    if (in_array($relativePath, PROTECTED_FILES, true)) {
        return true;
    }

    foreach (PROTECTED_DIRS as $dir) {
        if (strpos($relativePath, $dir . '/') === 0) {
            return true;
        }
    }

    return false;
}

/**
 * Hamta uppdateringsstatus fran cache
 */
function get_update_state(): array {
    $stateFile = DATA_PATH . '/update-state.json';
    if (!file_exists($stateFile)) {
        return [
            'last_check' => 0,
            'latest_version' => null,
            'current_version' => BOSSE_VERSION,
            'update_available' => false,
            'changelog' => '',
            'download_url' => '',
            'signature' => '',
            'critical' => false,
            'min_php_version' => '8.0',
            'release_date' => '',
        ];
    }

    $data = json_decode(file_get_contents($stateFile), true);
    return is_array($data) ? $data : get_update_state_defaults();
}

function get_update_state_defaults(): array {
    return [
        'last_check' => 0,
        'latest_version' => null,
        'current_version' => BOSSE_VERSION,
        'update_available' => false,
        'changelog' => '',
        'download_url' => '',
        'signature' => '',
        'critical' => false,
        'min_php_version' => '8.0',
        'release_date' => '',
    ];
}

/**
 * Spara uppdateringsstatus
 */
function save_update_state(array $state): void {
    $stateFile = DATA_PATH . '/update-state.json';
    if (!is_dir(DATA_PATH)) {
        mkdir(DATA_PATH, 0755, true);
    }
    file_put_contents($stateFile, json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}

/**
 * Kolla om uppdatering finns tillganglig
 * Hamtar manifest.json fran update-servern
 */
function check_for_update(): array {
    $updateUrl = defined('AGENCI_UPDATE_URL') ? AGENCI_UPDATE_URL : '';
    if (empty($updateUrl)) {
        return ['error' => 'AGENCI_UPDATE_URL ar inte konfigurerad'];
    }

    $manifestUrl = rtrim($updateUrl, '/') . '/manifest.json';

    // HTTP-request med 5s timeout
    $context = stream_context_create([
        'http' => [
            'timeout' => 5,
            'method' => 'GET',
            'header' => "User-Agent: BosseTemplate/" . BOSSE_VERSION . "\r\n",
        ],
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
        ],
    ]);

    $response = @file_get_contents($manifestUrl, false, $context);
    if ($response === false) {
        return ['error' => 'Kunde inte nå uppdateringsservern'];
    }

    $manifest = json_decode($response, true);
    if (!is_array($manifest) || !isset($manifest['latest_version'])) {
        return ['error' => 'Ogiltigt manifest-format'];
    }

    $updateAvailable = version_compare($manifest['latest_version'], BOSSE_VERSION, '>');

    // PHP-versionskontroll
    $minPhp = $manifest['min_php_version'] ?? '8.0';
    $phpCompatible = version_compare(PHP_VERSION, $minPhp, '>=');

    $state = [
        'last_check' => time(),
        'latest_version' => $manifest['latest_version'],
        'current_version' => BOSSE_VERSION,
        'update_available' => $updateAvailable,
        'changelog' => $manifest['changelog'] ?? '',
        'download_url' => $manifest['download_url'] ?? '',
        'signature' => $manifest['signature'] ?? '',
        'critical' => $manifest['critical'] ?? false,
        'min_php_version' => $minPhp,
        'php_compatible' => $phpCompatible,
        'release_date' => $manifest['release_date'] ?? '',
        'migrations' => $manifest['migrations'] ?? [],
    ];

    save_update_state($state);

    return $state;
}

/**
 * Verifiera HMAC-signatur pa en fil
 */
function verify_signature(string $filePath, string $expected): bool {
    if (!defined('AGENCI_UPDATE_KEY') || AGENCI_UPDATE_KEY === '') {
        return false;
    }

    // Forväntat format: "sha256:hexhash"
    $parts = explode(':', $expected, 2);
    if (count($parts) !== 2 || $parts[0] !== 'sha256') {
        return false;
    }

    $expectedHash = $parts[1];
    $actualHash = hash_hmac('sha256', file_get_contents($filePath), AGENCI_UPDATE_KEY);

    return hash_equals($expectedHash, $actualHash);
}

/**
 * Ladda ner uppdatering
 */
function download_update(string $url, string $signature): string|false {
    // Skapa tmp-mapp
    $tmpDir = DATA_PATH . '/tmp';
    if (!is_dir($tmpDir)) {
        mkdir($tmpDir, 0755, true);
    }

    // Lock-fil for att forhindra parallella uppdateringar
    $lockFile = $tmpDir . '/.update-lock';
    if (file_exists($lockFile)) {
        $lockAge = time() - filemtime($lockFile);
        if ($lockAge < 600) { // 10 min
            return false;
        }
        // Gammal lock, ta bort
        unlink($lockFile);
    }
    file_put_contents($lockFile, time());

    // Ladda ner
    $context = stream_context_create([
        'http' => [
            'timeout' => 60,
            'method' => 'GET',
            'header' => "User-Agent: BosseTemplate/" . BOSSE_VERSION . "\r\n",
        ],
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
        ],
    ]);

    $zipContent = @file_get_contents($url, false, $context);
    if ($zipContent === false) {
        @unlink($lockFile);
        return false;
    }

    $zipPath = $tmpDir . '/update-' . time() . '.zip';
    file_put_contents($zipPath, $zipContent);

    // Verifiera signatur
    if (!empty($signature) && defined('AGENCI_UPDATE_KEY') && AGENCI_UPDATE_KEY !== '') {
        if (!verify_signature($zipPath, $signature)) {
            @unlink($zipPath);
            @unlink($lockFile);
            return false;
        }
    }

    return $zipPath;
}

/**
 * Skapa backup av alla UPDATABLE_FILES
 */
function create_backup(): string {
    $backupDir = DATA_PATH . '/backups/backup-' . BOSSE_VERSION . '-' . date('Ymd-His');
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0755, true);
    }

    $rootPath = defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__);
    $backedUp = [];

    // Kopiera exakta filer
    foreach (UPDATABLE_FILES as $file) {
        $src = $rootPath . '/' . $file;
        if (file_exists($src)) {
            $dest = $backupDir . '/' . $file;
            $destDir = dirname($dest);
            if (!is_dir($destDir)) {
                mkdir($destDir, 0755, true);
            }
            copy($src, $dest);
            $backedUp[] = $file;
        }
    }

    // Kopiera wildcard-mappar
    foreach (UPDATABLE_DIRS as $dir) {
        $srcDir = $rootPath . '/' . $dir;
        if (is_dir($srcDir)) {
            backup_directory($srcDir, $backupDir . '/' . $dir);
        }
    }

    // Spara metadata
    $meta = [
        'version' => BOSSE_VERSION,
        'date' => date('Y-m-d H:i:s'),
        'files' => $backedUp,
        'php_version' => PHP_VERSION,
    ];
    file_put_contents($backupDir . '/backup-meta.json', json_encode($meta, JSON_PRETTY_PRINT));

    return $backupDir;
}

/**
 * Rekursiv kopieringsfunktion for backup
 */
function backup_directory(string $src, string $dest): void {
    if (!is_dir($dest)) {
        mkdir($dest, 0755, true);
    }

    $items = scandir($src);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $srcPath = $src . '/' . $item;
        $destPath = $dest . '/' . $item;
        if (is_dir($srcPath)) {
            backup_directory($srcPath, $destPath);
        } else {
            copy($srcPath, $destPath);
        }
    }
}

/**
 * Applicera uppdatering fran ZIP
 */
function apply_update(string $zipPath): array {
    $result = [
        'success' => false,
        'updated_files' => [],
        'skipped_files' => [],
        'errors' => [],
        'backup_dir' => '',
    ];

    if (!class_exists('ZipArchive')) {
        $result['errors'][] = 'ZipArchive ar inte tillganglig pa servern';
        return $result;
    }

    $rootPath = defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__);

    // Skapa backup
    $backupDir = create_backup();
    $result['backup_dir'] = $backupDir;

    // Oppna ZIP
    $zip = new ZipArchive();
    $res = $zip->open($zipPath);
    if ($res !== true) {
        $result['errors'][] = 'Kunde inte oppna ZIP-filen (felkod: ' . $res . ')';
        return $result;
    }

    // Extrahera bara godkanda filer
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $filename = $zip->getNameIndex($i);

        // Hoppa over mappar
        if (substr($filename, -1) === '/') {
            continue;
        }

        // Path traversal-skydd
        if (strpos($filename, '..') !== false || $filename[0] === '/') {
            $result['skipped_files'][] = $filename . ' (path traversal)';
            continue;
        }

        // Migreringsfiler (hanteras separat)
        if (strpos($filename, '_migrations/') === 0) {
            continue;
        }

        // Kolla om filen far uppdateras
        if (!is_updatable_file($filename)) {
            $result['skipped_files'][] = $filename . ' (inte i allowlist)';
            continue;
        }

        // Kolla att den inte ar skyddad
        if (is_protected_file($filename)) {
            $result['skipped_files'][] = $filename . ' (skyddad)';
            continue;
        }

        // Extrahera filen
        $content = $zip->getFromIndex($i);
        if ($content === false) {
            $result['errors'][] = 'Kunde inte lasa ' . $filename . ' fran ZIP';
            continue;
        }

        $destPath = $rootPath . '/' . $filename;
        $destDir = dirname($destPath);
        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        if (file_put_contents($destPath, $content) !== false) {
            $result['updated_files'][] = $filename;
        } else {
            $result['errors'][] = 'Kunde inte skriva ' . $filename;
        }
    }

    $zip->close();

    // Kor migrationer
    $state = get_update_state();
    $migrations = $state['migrations'] ?? [];
    if (!empty($migrations)) {
        $migrationResults = run_migrations(BOSSE_VERSION, $state['latest_version'] ?? BOSSE_VERSION, $zipPath);
        $result['migrations'] = $migrationResults;
    }

    // Rensa
    @unlink($zipPath);
    $lockFile = DATA_PATH . '/tmp/.update-lock';
    @unlink($lockFile);

    if (empty($result['errors'])) {
        $result['success'] = true;
    }

    return $result;
}

/**
 * Kor migrationer fran ZIP
 */
function run_migrations(string $fromVersion, string $toVersion, string $zipPath): array {
    $results = [];

    if (!class_exists('ZipArchive')) {
        return ['error' => 'ZipArchive ar inte tillganglig'];
    }

    $zip = new ZipArchive();
    if ($zip->open($zipPath) !== true) {
        return ['error' => 'Kunde inte oppna ZIP for migrationer'];
    }

    // Samla alla migreringsfiler
    $migrations = [];
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = $zip->getNameIndex($i);
        if (preg_match('#^_migrations/(\d+\.\d+\.\d+)\.php$#', $name, $matches)) {
            $version = $matches[1];
            if (version_compare($version, $fromVersion, '>') && version_compare($version, $toVersion, '<=')) {
                $migrations[$version] = $name;
            }
        }
    }

    // Sortera efter version
    uksort($migrations, 'version_compare');

    // Kor i ordning
    foreach ($migrations as $version => $migrationFile) {
        $content = $zip->getFromName($migrationFile);
        if ($content === false) {
            $results[] = ['version' => $version, 'status' => 'error', 'message' => 'Kunde inte lasa migreringsfilen'];
            continue;
        }

        // Spara temporart och kor
        $tmpFile = DATA_PATH . '/tmp/_migration_' . $version . '.php';
        file_put_contents($tmpFile, $content);

        try {
            $migrationResult = require $tmpFile;
            $results[] = ['version' => $version, 'status' => 'ok', 'result' => $migrationResult];
        } catch (\Throwable $e) {
            $results[] = ['version' => $version, 'status' => 'error', 'message' => $e->getMessage()];
        }

        @unlink($tmpFile);
    }

    $zip->close();
    return $results;
}

/**
 * Aterstall fran backup
 */
function rollback_update(string $backupDir): bool {
    if (!is_dir($backupDir)) {
        return false;
    }

    $rootPath = defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__);
    $metaFile = $backupDir . '/backup-meta.json';

    if (!file_exists($metaFile)) {
        return false;
    }

    $meta = json_decode(file_get_contents($metaFile), true);
    if (!is_array($meta)) {
        return false;
    }

    // Kopiera tillbaka alla filer
    $files = $meta['files'] ?? [];
    foreach ($files as $file) {
        $src = $backupDir . '/' . $file;
        $dest = $rootPath . '/' . $file;
        if (file_exists($src)) {
            $destDir = dirname($dest);
            if (!is_dir($destDir)) {
                mkdir($destDir, 0755, true);
            }
            copy($src, $dest);
        }
    }

    // Kopiera tillbaka wildcard-mappar
    foreach (UPDATABLE_DIRS as $dir) {
        $srcDir = $backupDir . '/' . $dir;
        if (is_dir($srcDir)) {
            restore_directory($srcDir, $rootPath . '/' . $dir);
        }
    }

    return true;
}

/**
 * Rekursiv aterstallning fran backup
 */
function restore_directory(string $src, string $dest): void {
    if (!is_dir($dest)) {
        mkdir($dest, 0755, true);
    }

    $items = scandir($src);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..' || $item === 'backup-meta.json') {
            continue;
        }
        $srcPath = $src . '/' . $item;
        $destPath = $dest . '/' . $item;
        if (is_dir($srcPath)) {
            restore_directory($srcPath, $destPath);
        } else {
            copy($srcPath, $destPath);
        }
    }
}

/**
 * Lista befintliga backups
 */
function list_backups(): array {
    $backupsDir = DATA_PATH . '/backups';
    if (!is_dir($backupsDir)) {
        return [];
    }

    $backups = [];
    $items = scandir($backupsDir, SCANDIR_SORT_DESCENDING);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $dir = $backupsDir . '/' . $item;
        $metaFile = $dir . '/backup-meta.json';
        if (is_dir($dir) && file_exists($metaFile)) {
            $meta = json_decode(file_get_contents($metaFile), true);
            if (is_array($meta)) {
                $meta['path'] = $dir;
                $meta['dir_name'] = $item;
                $backups[] = $meta;
            }
        }
    }

    return $backups;
}

/**
 * Ta bort en backup
 */
function delete_backup(string $backupDir): bool {
    // Sakerhetscheck - maste vara under backups-mappen
    $backupsDir = DATA_PATH . '/backups';
    $realBackup = realpath($backupDir);
    $realBackupsDir = realpath($backupsDir);

    if ($realBackup === false || $realBackupsDir === false) {
        return false;
    }

    if (strpos($realBackup, $realBackupsDir) !== 0) {
        return false;
    }

    // Rekursiv borttagning
    return delete_directory($backupDir);
}

/**
 * Rekursiv borttagning av mapp
 */
function delete_directory(string $dir): bool {
    if (!is_dir($dir)) {
        return false;
    }

    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $path = $dir . '/' . $item;
        if (is_dir($path)) {
            delete_directory($path);
        } else {
            unlink($path);
        }
    }

    return rmdir($dir);
}

/**
 * Kontrollera om uppdateringskoll behovs (var 12:e timme)
 */
function should_check_for_update(): bool {
    $state = get_update_state();
    $lastCheck = $state['last_check'] ?? 0;
    return (time() - $lastCheck) > 43200; // 12 timmar
}

/**
 * Automatisk uppdatering — kor hela flodet tyst
 * Kollar → laddar ner → backup → applicerar → loggar
 * Returnerar true om uppdatering applicerades
 */
function auto_update(): bool {
    // Kolla om update URL ar konfigurerad
    if (!defined('AGENCI_UPDATE_URL') || AGENCI_UPDATE_URL === '') {
        return false;
    }

    // Kolla om det ar dags
    if (!should_check_for_update()) {
        // Kolla cachad state — kanske redan vetat om update men inte applicerat
        $state = get_update_state();
        if (empty($state['update_available'])) {
            return false;
        }
        // Finns cachad update — forsok applicera
        return auto_apply_from_state($state);
    }

    // Gor ny koll mot servern
    $state = check_for_update();

    if (isset($state['error']) || empty($state['update_available'])) {
        return false;
    }

    return auto_apply_from_state($state);
}

/**
 * Applicera uppdatering fran cachad state (intern funktion)
 */
function auto_apply_from_state(array $state): bool {
    // PHP-versionskontroll
    if (!empty($state['min_php_version']) && version_compare(PHP_VERSION, $state['min_php_version'], '<')) {
        log_update_event('skip', $state['latest_version'] ?? '?', 'Kraver PHP ' . $state['min_php_version']);
        return false;
    }

    // Ladda ner
    $zipPath = download_update($state['download_url'] ?? '', $state['signature'] ?? '');
    if ($zipPath === false) {
        log_update_event('error', $state['latest_version'] ?? '?', 'Nedladdning misslyckades');
        return false;
    }

    // Applicera (skapar backup automatiskt)
    $result = apply_update($zipPath);

    if ($result['success']) {
        // Rensa update-available-flaggan
        $state['update_available'] = false;
        $state['current_version'] = $state['latest_version'];
        save_update_state($state);

        log_update_event('success', $state['latest_version'] ?? '?',
            count($result['updated_files']) . ' filer uppdaterade');

        // Stada gamla backups och tmp-filer
        cleanup_old_data();

        return true;
    }

    // Misslyckades — rollback skedde automatiskt i apply_update
    log_update_event('error', $state['latest_version'] ?? '?',
        implode('; ', $result['errors'] ?? ['Okant fel']));
    return false;
}

/**
 * Logga uppdateringshandelse till data/update-log.json
 */
function log_update_event(string $type, string $version, string $message): void {
    $logFile = DATA_PATH . '/update-log.json';
    $log = [];

    if (file_exists($logFile)) {
        $existing = json_decode(file_get_contents($logFile), true);
        if (is_array($existing)) {
            $log = $existing;
        }
    }

    $log[] = [
        'type' => $type,
        'version' => $version,
        'message' => $message,
        'date' => date('Y-m-d H:i:s'),
        'from_version' => BOSSE_VERSION,
        'php_version' => PHP_VERSION,
    ];

    // Behall max 50 poster
    if (count($log) > 50) {
        $log = array_slice($log, -50);
    }

    if (!is_dir(DATA_PATH)) {
        mkdir(DATA_PATH, 0755, true);
    }
    file_put_contents($logFile, json_encode($log, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}

/**
 * Rensa gamla backups — behall max 3 stycken
 * + ta bort allt i data/tmp/
 */
function cleanup_old_data(): void {
    // --- Backups: behall max 3 ---
    $backups = list_backups(); // sorterade nyast forst
    if (count($backups) > 3) {
        $toDelete = array_slice($backups, 3);
        foreach ($toDelete as $old) {
            if (!empty($old['path'])) {
                delete_backup($old['path']);
            }
        }
    }

    // --- Tmp: rensa allt utom lock-filen ---
    $tmpDir = DATA_PATH . '/tmp';
    if (is_dir($tmpDir)) {
        $items = scandir($tmpDir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..' || $item === '.update-lock') {
                continue;
            }
            $path = $tmpDir . '/' . $item;
            if (is_file($path)) {
                @unlink($path);
            } elseif (is_dir($path)) {
                delete_directory($path);
            }
        }
    }
}

/**
 * Hamta uppdateringslogg
 */
function get_update_log(): array {
    $logFile = DATA_PATH . '/update-log.json';
    if (!file_exists($logFile)) {
        return [];
    }
    $data = json_decode(file_get_contents($logFile), true);
    return is_array($data) ? array_reverse($data) : [];
}
