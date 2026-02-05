<?php
/**
 * Super Admin API
 * Alla endpoints kraver super admin + CSRF
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../security/session.php';
require_once __DIR__ . '/../security/csrf.php';
require_once __DIR__ . '/../security/updater.php';

// Krav: super admin
require_super_admin();

// CORS/JSON headers
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// CSRF-verifiering för alla requests
$csrfHeader = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (empty($csrfHeader) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfHeader)) {
    http_response_code(403);
    echo json_encode(['error' => 'Ogiltig CSRF-token']);
    exit;
}

switch ($action) {
    case 'check-update':
        handle_check_update();
        break;

    case 'apply-update':
        if ($method !== 'POST') { method_not_allowed(); }
        handle_apply_update();
        break;

    case 'rollback':
        if ($method !== 'POST') { method_not_allowed(); }
        handle_rollback();
        break;

    case 'delete-backup':
        if ($method !== 'POST') { method_not_allowed(); }
        handle_delete_backup();
        break;

    case 'system-info':
        handle_system_info();
        break;

    case 'test-smtp':
        if ($method !== 'POST') { method_not_allowed(); }
        handle_test_smtp();
        break;

    case 'error-log':
        handle_error_log();
        break;

    case 'check-integrity':
        handle_check_integrity();
        break;

    default:
        http_response_code(404);
        echo json_encode(['error' => 'Okänd action']);
        exit;
}

function method_not_allowed(): void {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

function handle_check_update(): void {
    $result = check_for_update();
    echo json_encode($result);
}

function handle_apply_update(): void {
    // Hämta aktuell status
    $state = check_for_update();

    if (isset($state['error'])) {
        echo json_encode(['success' => false, 'errors' => [$state['error']]]);
        return;
    }

    if (empty($state['update_available'])) {
        echo json_encode(['success' => false, 'errors' => ['Ingen uppdatering tillgänglig']]);
        return;
    }

    // PHP-versionskontroll
    if (!empty($state['min_php_version']) && version_compare(PHP_VERSION, $state['min_php_version'], '<')) {
        echo json_encode(['success' => false, 'errors' => ['Kräver PHP ' . $state['min_php_version'] . ', du har ' . PHP_VERSION]]);
        return;
    }

    // Ladda ner
    $zipPath = download_update($state['download_url'], $state['signature'] ?? '');
    if ($zipPath === false) {
        echo json_encode(['success' => false, 'errors' => ['Kunde inte ladda ner uppdateringen']]);
        return;
    }

    // Applicera
    $result = apply_update($zipPath);

    // Om fel - automatisk rollback
    if (!$result['success'] && !empty($result['backup_dir'])) {
        rollback_update($result['backup_dir']);
        $result['rolled_back'] = true;
    }

    // Lägg till nya versionen i responsen
    if ($result['success']) {
        $result['new_version'] = $state['latest_version'] ?? '';
    }

    echo json_encode($result);
}

function handle_rollback(): void {
    $input = json_decode(file_get_contents('php://input'), true);
    $backupName = $input['backup'] ?? '';

    if (empty($backupName) || preg_match('/[^a-zA-Z0-9._-]/', $backupName)) {
        echo json_encode(['success' => false, 'error' => 'Ogiltigt backup-namn']);
        return;
    }

    $backupDir = DATA_PATH . '/backups/' . $backupName;
    if (!is_dir($backupDir)) {
        echo json_encode(['success' => false, 'error' => 'Backup hittades inte']);
        return;
    }

    $success = rollback_update($backupDir);
    echo json_encode(['success' => $success]);
}

function handle_delete_backup(): void {
    $input = json_decode(file_get_contents('php://input'), true);
    $backupName = $input['backup'] ?? '';

    if (empty($backupName) || preg_match('/[^a-zA-Z0-9._-]/', $backupName)) {
        echo json_encode(['success' => false, 'error' => 'Ogiltigt backup-namn']);
        return;
    }

    $backupDir = DATA_PATH . '/backups/' . $backupName;
    $success = delete_backup($backupDir);
    echo json_encode(['success' => $success]);
}

function handle_system_info(): void {
    $info = [
        'php_version' => PHP_VERSION,
        'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Okänd',
        'os' => PHP_OS,
        'memory_limit' => ini_get('memory_limit'),
        'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
        'upload_max' => ini_get('upload_max_filesize'),
        'post_max' => ini_get('post_max_size'),
        'max_execution' => ini_get('max_execution_time') . 's',
        'extensions' => get_loaded_extensions(),
        'writable' => [
            'data/' => is_writable(DATA_PATH),
            'config.php' => is_writable(ROOT_PATH . '/config.php'),
            'assets/css/' => is_writable(ROOT_PATH . '/assets/css'),
        ],
    ];

    if (function_exists('disk_free_space')) {
        $info['disk_free'] = round(@disk_free_space(ROOT_PATH) / 1024 / 1024, 2) . ' MB';
        $info['disk_total'] = round(@disk_total_space(ROOT_PATH) / 1024 / 1024, 2) . ' MB';
    }

    echo json_encode($info);
}

function handle_test_smtp(): void {
    if (!defined('SMTP_HOST') || SMTP_HOST === '') {
        echo json_encode(['success' => false, 'error' => 'SMTP är inte konfigurerat']);
        return;
    }

    require_once __DIR__ . '/../includes/mailer.php';

    $to = defined('CONTACT_EMAIL') ? CONTACT_EMAIL : (defined('SMTP_USERNAME') ? SMTP_USERNAME : '');
    if (empty($to)) {
        echo json_encode(['success' => false, 'error' => 'Ingen mottagaradress hittades']);
        return;
    }

    $subject = 'SMTP-test från ' . (defined('SITE_NAME') ? SITE_NAME : 'Bosse Template');
    $body = "Detta ar ett testmail skickat från Super Admin-panelen.\n\n";
    $body .= "Tidpunkt: " . date('Y-m-d H:i:s') . "\n";
    $body .= "Server: " . ($_SERVER['SERVER_NAME'] ?? 'localhost') . "\n";
    $body .= "PHP: " . PHP_VERSION . "\n";

    $success = send_mail($to, $subject, $body);

    echo json_encode([
        'success' => $success,
        'to' => $to,
        'error' => $success ? null : 'SMTP-anslutningen misslyckades. Kontrollera loggarna.',
    ]);
}

function handle_error_log(): void {
    $log = '';
    $errorLogPath = ini_get('error_log');

    if ($errorLogPath && file_exists($errorLogPath) && is_readable($errorLogPath)) {
        $lines = file($errorLogPath);
        if ($lines !== false) {
            $log = implode('', array_slice($lines, -100));
        }
    }

    echo json_encode(['log' => $log]);
}

function handle_check_integrity(): void {
    $rootPath = defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__);
    $missing = [];

    foreach (UPDATABLE_FILES as $file) {
        $fullPath = $rootPath . '/' . $file;
        if (!file_exists($fullPath)) {
            $missing[] = $file;
        }
    }

    echo json_encode([
        'total' => count(UPDATABLE_FILES),
        'present' => count(UPDATABLE_FILES) - count($missing),
        'missing' => $missing,
    ]);
}
