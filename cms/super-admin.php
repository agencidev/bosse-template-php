<?php
/**
 * Super Admin Dashboard
 * Endast tillgänglig för Agenci via super admin-token
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../security/session.php';
require_once __DIR__ . '/../security/csrf.php';
require_once __DIR__ . '/../security/updater.php';

// Krav: super admin
require_super_admin();

// Hämta data
$updateState = get_update_state();
$backups = list_backups();
$updateLog = get_update_log();
$csrfToken = csrf_token();

// System info
$systemInfo = [
    'php_version' => PHP_VERSION,
    'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Okänd',
    'os' => PHP_OS,
    'memory_limit' => ini_get('memory_limit'),
    'upload_max' => ini_get('upload_max_filesize'),
    'post_max' => ini_get('post_max_size'),
    'max_execution' => ini_get('max_execution_time') . 's',
    'disk_free' => function_exists('disk_free_space') ? format_bytes(@disk_free_space(ROOT_PATH)) : 'N/A',
    'disk_total' => function_exists('disk_total_space') ? format_bytes(@disk_total_space(ROOT_PATH)) : 'N/A',
    'bosse_version' => BOSSE_VERSION,
    'bosse_version_date' => BOSSE_VERSION_DATE,
    'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? ROOT_PATH,
    'extensions' => implode(', ', ['curl' => extension_loaded('curl') ? 'curl' : '', 'zip' => extension_loaded('zip') ? 'zip' : '', 'mbstring' => extension_loaded('mbstring') ? 'mbstring' : '', 'gd' => extension_loaded('gd') ? 'gd' : '', 'openssl' => extension_loaded('openssl') ? 'openssl' : '']),
];

// Filtrera tomma extensions
$systemInfo['extensions'] = implode(', ', array_filter(explode(', ', $systemInfo['extensions'])));

// SMTP-status
$smtpConfigured = defined('SMTP_HOST') && SMTP_HOST !== '';

// Error log
$errorLog = '';
$errorLogPath = ini_get('error_log');
if ($errorLogPath && file_exists($errorLogPath) && is_readable($errorLogPath)) {
    $lines = file($errorLogPath);
    if ($lines !== false) {
        $errorLog = implode('', array_slice($lines, -50));
    }
}

// Config-konstanter (maskera kansliga)
$configConstants = [];
$sensitiveKeys = ['ADMIN_PASSWORD_HASH', 'SESSION_SECRET', 'CSRF_TOKEN_SALT', 'SMTP_PASSWORD', 'AGENCI_SUPER_ADMIN_TOKEN', 'AGENCI_UPDATE_KEY', 'GITHUB_TOKEN'];
$relevantConstants = [
    'SITE_URL', 'SITE_NAME', 'SITE_DESCRIPTION', 'CONTACT_EMAIL', 'CONTACT_PHONE',
    'ADMIN_USERNAME', 'ADMIN_PASSWORD_HASH', 'ADMIN_EMAIL',
    'SESSION_SECRET', 'CSRF_TOKEN_SALT',
    'SMTP_HOST', 'SMTP_PORT', 'SMTP_ENCRYPTION', 'SMTP_USERNAME', 'SMTP_PASSWORD',
    'GITHUB_REPO', 'GITHUB_TOKEN',
    'GOOGLE_ANALYTICS_ID',
    'ENVIRONMENT',
    'AGENCI_SUPER_ADMIN_TOKEN', 'AGENCI_UPDATE_URL', 'AGENCI_UPDATE_KEY',
];

foreach ($relevantConstants as $key) {
    if (defined($key)) {
        $value = constant($key);
        if (in_array($key, $sensitiveKeys)) {
            $value = substr((string)$value, 0, 6) . '***';
        }
        $configConstants[$key] = $value;
    }
}

function format_bytes(float $bytes): string {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin - <?php echo htmlspecialchars(SITE_NAME); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #fafafa;
            min-height: 100vh;
            color: #18181b;
        }
        .sa-container {
            max-width: 64rem;
            margin: 0 auto;
            padding: 4rem 1.5rem 3rem;
        }
        .sa-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 2rem;
        }
        .sa-header h1 {
            font-size: 1.75rem;
            font-weight: bold;
        }
        .sa-header .sa-version {
            font-size: 0.875rem;
            color: #a3a3a3;
            background: #f5f5f5;
            padding: 0.375rem 0.75rem;
            border-radius: 9999px;
        }
        .sa-back {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            color: #a3a3a3;
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 1rem;
        }
        .sa-back:hover { color: #737373; }

        /* Panels */
        .sa-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }
        @media (min-width: 768px) {
            .sa-grid { grid-template-columns: 1fr 1fr; }
            .sa-grid .sa-panel--full { grid-column: 1 / -1; }
        }
        .sa-panel {
            background: white;
            border: 1px solid #e5e5e5;
            border-radius: 1rem;
            overflow: hidden;
        }
        .sa-panel__header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .sa-panel__header h2 {
            font-size: 1rem;
            font-weight: 700;
        }
        .sa-panel__body { padding: 1.25rem 1.5rem; }

        /* Info rows */
        .sa-info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid #f5f5f5;
            font-size: 0.875rem;
        }
        .sa-info-row:last-child { border-bottom: none; }
        .sa-info-row .sa-label { color: #737373; }
        .sa-info-row .sa-value { font-weight: 500; text-align: right; max-width: 60%; word-break: break-all; }

        /* Buttons */
        .sa-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-size: 0.8125rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
            text-decoration: none;
        }
        .sa-btn--primary { background: #fe4f2a; color: white; }
        .sa-btn--primary:hover { background: #e8461f; }
        .sa-btn--secondary { background: #f5f5f5; color: #18181b; }
        .sa-btn--secondary:hover { background: #e5e5e5; }
        .sa-btn--danger { background: #fef2f2; color: #b91c1c; }
        .sa-btn--danger:hover { background: #fee2e2; }
        .sa-btn--success { background: #f0fdf4; color: #15803d; }
        .sa-btn--success:hover { background: #dcfce7; }
        .sa-btn:disabled { opacity: 0.5; cursor: not-allowed; }

        /* Update banner */
        .sa-update-banner {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            border: 1px solid #10b981;
            border-radius: 1rem;
            padding: 1.25rem 1.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }
        .sa-update-banner--critical {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            border-color: #ef4444;
        }
        .sa-update-banner p { font-size: 0.9375rem; font-weight: 500; color: #065f46; }
        .sa-update-banner small { font-size: 0.8125rem; color: #047857; }

        /* Status badges */
        .sa-badge {
            display: inline-block;
            padding: 0.125rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .sa-badge--ok { background: #f0fdf4; color: #15803d; }
        .sa-badge--warn { background: #fffbeb; color: #92400e; }
        .sa-badge--error { background: #fef2f2; color: #b91c1c; }

        /* Backup list */
        .sa-backup-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f5f5f5;
            font-size: 0.875rem;
        }
        .sa-backup-item:last-child { border-bottom: none; }
        .sa-backup-item .sa-backup-info { flex: 1; }
        .sa-backup-item .sa-backup-version { font-weight: 600; }
        .sa-backup-item .sa-backup-date { color: #a3a3a3; font-size: 0.8125rem; }
        .sa-backup-actions { display: flex; gap: 0.5rem; }

        /* Error log */
        .sa-log {
            background: #18181b;
            color: #a3e635;
            font-family: 'SFMono-Regular', Consolas, monospace;
            font-size: 0.75rem;
            padding: 1rem;
            border-radius: 0.5rem;
            max-height: 300px;
            overflow-y: auto;
            white-space: pre-wrap;
            word-break: break-all;
        }

        /* Toast */
        .sa-toast {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            background: #18181b;
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 1rem;
            font-size: 0.9375rem;
            font-weight: 500;
            z-index: 10001;
            transform: translateY(100px);
            opacity: 0;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        .sa-toast.show { transform: translateY(0); opacity: 1; }
        .sa-toast--success {
            background: linear-gradient(135deg, #10b981, #059669);
        }
        .sa-toast--error {
            background: linear-gradient(135deg, #ef4444, #dc2626);
        }
        .sa-toast__icon {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        /* Loading */
        .sa-spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: sa-spin 0.6s linear infinite;
        }
        @keyframes sa-spin { to { transform: rotate(360deg); } }

        /* Deactivate link */
        .sa-deactivate {
            display: inline-block;
            margin-top: 2rem;
            padding: 0.5rem 1rem;
            font-size: 0.8125rem;
            color: #a3a3a3;
            text-decoration: none;
            border: 1px solid #e5e5e5;
            border-radius: 0.5rem;
        }
        .sa-deactivate:hover { color: #737373; background: #f5f5f5; }

        @media (max-width: 640px) {
            .sa-header { flex-direction: column; align-items: flex-start; gap: 0.5rem; }
            .sa-update-banner { flex-direction: column; align-items: flex-start; }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/admin-bar.php'; ?>

    <div class="sa-container">
        <a href="/dashboard" class="sa-back">&larr; Tillbaka till Dashboard</a>

        <div class="sa-header">
            <h1>Super Admin</h1>
            <span class="sa-version">v<?php echo htmlspecialchars(BOSSE_VERSION); ?> (<?php echo htmlspecialchars(BOSSE_VERSION_DATE); ?>)</span>
        </div>

        <?php if (!empty($updateState['update_available']) && $updateState['update_available']): ?>
        <div class="sa-update-banner <?php echo !empty($updateState['critical']) ? 'sa-update-banner--critical' : ''; ?>">
            <div>
                <p>Version <?php echo htmlspecialchars($updateState['latest_version'] ?? ''); ?> tillgänglig<?php echo !empty($updateState['critical']) ? ' (kritisk)' : ''; ?></p>
                <small><?php echo htmlspecialchars($updateState['changelog'] ?? ''); ?></small>
            </div>
            <button class="sa-btn sa-btn--primary" onclick="applyUpdate()" id="btn-apply-update">Uppdatera nu</button>
        </div>
        <?php endif; ?>

        <div class="sa-grid">
            <!-- System Info -->
            <div class="sa-panel">
                <div class="sa-panel__header">
                    <h2>System</h2>
                    <span class="sa-badge sa-badge--ok">OK</span>
                </div>
                <div class="sa-panel__body">
                    <div class="sa-info-row">
                        <span class="sa-label">PHP</span>
                        <span class="sa-value"><?php echo htmlspecialchars($systemInfo['php_version']); ?></span>
                    </div>
                    <div class="sa-info-row">
                        <span class="sa-label">Server</span>
                        <span class="sa-value"><?php echo htmlspecialchars($systemInfo['server']); ?></span>
                    </div>
                    <div class="sa-info-row">
                        <span class="sa-label">OS</span>
                        <span class="sa-value"><?php echo htmlspecialchars($systemInfo['os']); ?></span>
                    </div>
                    <div class="sa-info-row">
                        <span class="sa-label">Memory Limit</span>
                        <span class="sa-value"><?php echo htmlspecialchars($systemInfo['memory_limit']); ?></span>
                    </div>
                    <div class="sa-info-row">
                        <span class="sa-label">Upload Max</span>
                        <span class="sa-value"><?php echo htmlspecialchars($systemInfo['upload_max']); ?></span>
                    </div>
                    <div class="sa-info-row">
                        <span class="sa-label">Max Execution</span>
                        <span class="sa-value"><?php echo htmlspecialchars($systemInfo['max_execution']); ?></span>
                    </div>
                    <div class="sa-info-row">
                        <span class="sa-label">Disk</span>
                        <span class="sa-value"><?php echo htmlspecialchars($systemInfo['disk_free']); ?> / <?php echo htmlspecialchars($systemInfo['disk_total']); ?></span>
                    </div>
                    <div class="sa-info-row">
                        <span class="sa-label">Tillägg</span>
                        <span class="sa-value"><?php echo htmlspecialchars($systemInfo['extensions']); ?></span>
                    </div>
                </div>
            </div>

            <!-- Update Panel -->
            <div class="sa-panel">
                <div class="sa-panel__header">
                    <h2>Uppdatering</h2>
                    <?php if (!empty($updateState['update_available'])): ?>
                        <span class="sa-badge sa-badge--warn">Ny version</span>
                    <?php else: ?>
                        <span class="sa-badge sa-badge--ok">Senaste</span>
                    <?php endif; ?>
                </div>
                <div class="sa-panel__body">
                    <div class="sa-info-row">
                        <span class="sa-label">Installerad</span>
                        <span class="sa-value"><?php echo htmlspecialchars(BOSSE_VERSION); ?></span>
                    </div>
                    <div class="sa-info-row">
                        <span class="sa-label">Senaste</span>
                        <span class="sa-value" id="latest-version"><?php echo htmlspecialchars($updateState['latest_version'] ?? 'Okänd'); ?></span>
                    </div>
                    <div class="sa-info-row">
                        <span class="sa-label">Senaste koll</span>
                        <span class="sa-value"><?php echo $updateState['last_check'] ? date('Y-m-d H:i', $updateState['last_check']) : 'Aldrig'; ?></span>
                    </div>
                    <?php if (!empty($updateState['changelog'])): ?>
                    <div class="sa-info-row">
                        <span class="sa-label">Changelog</span>
                        <span class="sa-value"><?php echo htmlspecialchars($updateState['changelog']); ?></span>
                    </div>
                    <?php endif; ?>
                    <div style="margin-top: 1rem; display: flex; gap: 0.5rem;">
                        <button class="sa-btn sa-btn--secondary" onclick="checkUpdate()" id="btn-check-update">Sök uppdatering</button>
                        <?php if (!empty($updateState['update_available'])): ?>
                        <button class="sa-btn sa-btn--primary" onclick="applyUpdate()" id="btn-apply-update-2">Uppdatera</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Backups -->
            <div class="sa-panel">
                <div class="sa-panel__header">
                    <h2>Backup-historik</h2>
                    <span class="sa-badge sa-badge--ok"><?php echo count($backups); ?> st</span>
                </div>
                <div class="sa-panel__body">
                    <?php if (empty($backups)): ?>
                        <p style="font-size: 0.875rem; color: #a3a3a3;">Inga backups ännu. En skapas automatiskt före varje uppdatering.</p>
                    <?php else: ?>
                        <?php foreach ($backups as $backup): ?>
                        <div class="sa-backup-item">
                            <div class="sa-backup-info">
                                <div class="sa-backup-version">v<?php echo htmlspecialchars($backup['version'] ?? '?'); ?></div>
                                <div class="sa-backup-date"><?php echo htmlspecialchars($backup['date'] ?? ''); ?></div>
                            </div>
                            <div class="sa-backup-actions">
                                <button class="sa-btn sa-btn--secondary" onclick="rollbackBackup('<?php echo htmlspecialchars($backup['dir_name'] ?? ''); ?>')">Återställ</button>
                                <button class="sa-btn sa-btn--danger" onclick="deleteBackup('<?php echo htmlspecialchars($backup['dir_name'] ?? ''); ?>')">Ta bort</button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Update Log -->
            <div class="sa-panel sa-panel--full">
                <div class="sa-panel__header">
                    <h2>Uppdateringshistorik</h2>
                    <span class="sa-badge sa-badge--ok"><?php echo count($updateLog); ?> händelser</span>
                </div>
                <div class="sa-panel__body">
                    <?php if (empty($updateLog)): ?>
                        <p style="font-size: 0.875rem; color: #a3a3a3;">Inga uppdateringar har körts ännu. Uppdateringar sker automatiskt.</p>
                    <?php else: ?>
                        <?php foreach (array_slice($updateLog, 0, 15) as $entry): ?>
                        <div class="sa-info-row">
                            <span class="sa-label" style="display: flex; align-items: center; gap: 0.5rem;">
                                <?php if ($entry['type'] === 'success'): ?>
                                    <span class="sa-badge sa-badge--ok">OK</span>
                                <?php elseif ($entry['type'] === 'error'): ?>
                                    <span class="sa-badge sa-badge--error">Fel</span>
                                <?php else: ?>
                                    <span class="sa-badge sa-badge--warn">Hoppades</span>
                                <?php endif; ?>
                                v<?php echo htmlspecialchars($entry['version'] ?? '?'); ?>
                            </span>
                            <span class="sa-value" style="font-weight: 400; font-size: 0.8125rem;">
                                <?php echo htmlspecialchars($entry['message'] ?? ''); ?>
                                <br><span style="color: #a3a3a3;"><?php echo htmlspecialchars($entry['date'] ?? ''); ?></span>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Ändra kundlösenord -->
            <div class="sa-panel">
                <div class="sa-panel__header">
                    <h2>Kundkonto</h2>
                </div>
                <div class="sa-panel__body">
                    <div class="sa-info-row">
                        <span class="sa-label">Användarnamn</span>
                        <span class="sa-value"><?php echo htmlspecialchars(defined('ADMIN_USERNAME') ? ADMIN_USERNAME : 'N/A'); ?></span>
                    </div>
                    <div class="sa-info-row">
                        <span class="sa-label">E-post</span>
                        <span class="sa-value"><?php echo htmlspecialchars(defined('ADMIN_EMAIL') ? ADMIN_EMAIL : 'Ej angiven'); ?></span>
                    </div>
                    <div style="margin-top: 1rem;">
                        <label style="display:block;font-size:0.8125rem;font-weight:600;margin-bottom:0.375rem;color:#a3a3a3;">Nytt lösenord</label>
                        <div style="display:flex;gap:0.5rem;">
                            <input type="password" id="new-customer-password" placeholder="Minst 8 tecken" style="flex:1;padding:0.5rem 0.75rem;background:#27272a;border:1px solid #3f3f46;border-radius:0.375rem;color:white;font-size:0.875rem;">
                            <button class="sa-btn sa-btn--success" onclick="changeCustomerPassword()">Ändra</button>
                        </div>
                    </div>
                    <div id="password-result" style="margin-top: 0.75rem; font-size: 0.8125rem;"></div>
                </div>
            </div>

            <!-- SMTP Test -->
            <div class="sa-panel">
                <div class="sa-panel__header">
                    <h2>SMTP / E-post</h2>
                    <span class="sa-badge <?php echo $smtpConfigured ? 'sa-badge--ok' : 'sa-badge--warn'; ?>">
                        <?php echo $smtpConfigured ? 'Konfigurerad' : 'Ej konfigurerad'; ?>
                    </span>
                </div>
                <div class="sa-panel__body">
                    <?php if ($smtpConfigured): ?>
                    <div class="sa-info-row">
                        <span class="sa-label">Server</span>
                        <span class="sa-value"><?php echo htmlspecialchars(SMTP_HOST); ?>:<?php echo defined('SMTP_PORT') ? SMTP_PORT : '465'; ?></span>
                    </div>
                    <div class="sa-info-row">
                        <span class="sa-label">Kryptering</span>
                        <span class="sa-value"><?php echo htmlspecialchars(defined('SMTP_ENCRYPTION') ? SMTP_ENCRYPTION : 'ssl'); ?></span>
                    </div>
                    <div class="sa-info-row">
                        <span class="sa-label">Användare</span>
                        <span class="sa-value"><?php echo htmlspecialchars(defined('SMTP_USERNAME') ? SMTP_USERNAME : ''); ?></span>
                    </div>
                    <div style="margin-top: 1rem;">
                        <button class="sa-btn sa-btn--success" onclick="testSmtp()" id="btn-smtp-test">Skicka testmail</button>
                    </div>
                    <?php else: ?>
                    <p style="font-size: 0.875rem; color: #a3a3a3;">SMTP är inte konfigurerat. Lägg till SMTP-inställningar i config.php.</p>
                    <?php endif; ?>
                    <div id="smtp-result" style="margin-top: 0.75rem; font-size: 0.8125rem;"></div>
                </div>
            </div>

            <!-- Config (read-only overview) -->
            <div class="sa-panel sa-panel--full">
                <div class="sa-panel__header">
                    <h2>Konfiguration</h2>
                </div>
                <div class="sa-panel__body">
                    <?php foreach ($configConstants as $key => $value): ?>
                    <div class="sa-info-row">
                        <span class="sa-label"><?php echo htmlspecialchars($key); ?></span>
                        <span class="sa-value"><?php echo htmlspecialchars((string)$value); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Redigera konfiguration -->
            <div class="sa-panel sa-panel--full">
                <div class="sa-panel__header">
                    <h2>Redigera konfiguration</h2>
                </div>
                <div class="sa-panel__body">
                    <p style="font-size: 0.8125rem; color: #a3a3a3; margin-bottom: 1.5rem;">Ändra inställningar som angavs vid setup. Sparar direkt till config.php.</p>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div>
                            <label style="display:block;font-size:0.8125rem;font-weight:600;margin-bottom:0.375rem;color:#737373;">Sajt-URL</label>
                            <input type="text" id="cfg-site-url" value="<?php echo htmlspecialchars(defined('SITE_URL') ? SITE_URL : ''); ?>" style="width:100%;padding:0.5rem 0.75rem;border:1px solid #e5e5e5;border-radius:0.375rem;font-size:0.875rem;">
                        </div>
                        <div>
                            <label style="display:block;font-size:0.8125rem;font-weight:600;margin-bottom:0.375rem;color:#737373;">Företagsnamn</label>
                            <input type="text" id="cfg-site-name" value="<?php echo htmlspecialchars(defined('SITE_NAME') ? SITE_NAME : ''); ?>" style="width:100%;padding:0.5rem 0.75rem;border:1px solid #e5e5e5;border-radius:0.375rem;font-size:0.875rem;">
                        </div>
                        <div>
                            <label style="display:block;font-size:0.8125rem;font-weight:600;margin-bottom:0.375rem;color:#737373;">Beskrivning</label>
                            <input type="text" id="cfg-site-desc" value="<?php echo htmlspecialchars(defined('SITE_DESCRIPTION') ? SITE_DESCRIPTION : ''); ?>" style="width:100%;padding:0.5rem 0.75rem;border:1px solid #e5e5e5;border-radius:0.375rem;font-size:0.875rem;">
                        </div>
                        <div>
                            <label style="display:block;font-size:0.8125rem;font-weight:600;margin-bottom:0.375rem;color:#737373;">Kontakt-email</label>
                            <input type="email" id="cfg-contact-email" value="<?php echo htmlspecialchars(defined('CONTACT_EMAIL') ? CONTACT_EMAIL : ''); ?>" style="width:100%;padding:0.5rem 0.75rem;border:1px solid #e5e5e5;border-radius:0.375rem;font-size:0.875rem;">
                        </div>
                        <div>
                            <label style="display:block;font-size:0.8125rem;font-weight:600;margin-bottom:0.375rem;color:#737373;">Kontakt-telefon</label>
                            <input type="text" id="cfg-contact-phone" value="<?php echo htmlspecialchars(defined('CONTACT_PHONE') ? CONTACT_PHONE : ''); ?>" style="width:100%;padding:0.5rem 0.75rem;border:1px solid #e5e5e5;border-radius:0.375rem;font-size:0.875rem;">
                        </div>
                        <div>
                            <label style="display:block;font-size:0.8125rem;font-weight:600;margin-bottom:0.375rem;color:#737373;">Admin-användarnamn</label>
                            <input type="text" id="cfg-admin-username" value="<?php echo htmlspecialchars(defined('ADMIN_USERNAME') ? ADMIN_USERNAME : ''); ?>" style="width:100%;padding:0.5rem 0.75rem;border:1px solid #e5e5e5;border-radius:0.375rem;font-size:0.875rem;">
                        </div>
                    </div>

                    <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #f5f5f5;">
                        <h3 style="font-size: 0.875rem; font-weight: 600; margin-bottom: 1rem; color: #737373;">SMTP</h3>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div>
                                <label style="display:block;font-size:0.8125rem;font-weight:600;margin-bottom:0.375rem;color:#737373;">SMTP-server</label>
                                <input type="text" id="cfg-smtp-host" value="<?php echo htmlspecialchars(defined('SMTP_HOST') ? SMTP_HOST : ''); ?>" style="width:100%;padding:0.5rem 0.75rem;border:1px solid #e5e5e5;border-radius:0.375rem;font-size:0.875rem;">
                            </div>
                            <div>
                                <label style="display:block;font-size:0.8125rem;font-weight:600;margin-bottom:0.375rem;color:#737373;">Port</label>
                                <input type="number" id="cfg-smtp-port" value="<?php echo htmlspecialchars(defined('SMTP_PORT') ? SMTP_PORT : '465'); ?>" style="width:100%;padding:0.5rem 0.75rem;border:1px solid #e5e5e5;border-radius:0.375rem;font-size:0.875rem;">
                            </div>
                            <div>
                                <label style="display:block;font-size:0.8125rem;font-weight:600;margin-bottom:0.375rem;color:#737373;">Kryptering</label>
                                <select id="cfg-smtp-encryption" style="width:100%;padding:0.5rem 0.75rem;border:1px solid #e5e5e5;border-radius:0.375rem;font-size:0.875rem;">
                                    <option value="ssl" <?php echo (defined('SMTP_ENCRYPTION') && SMTP_ENCRYPTION === 'ssl') ? 'selected' : ''; ?>>SSL</option>
                                    <option value="tls" <?php echo (defined('SMTP_ENCRYPTION') && SMTP_ENCRYPTION === 'tls') ? 'selected' : ''; ?>>TLS</option>
                                    <option value="" <?php echo (defined('SMTP_ENCRYPTION') && SMTP_ENCRYPTION === '') ? 'selected' : ''; ?>>Ingen</option>
                                </select>
                            </div>
                            <div>
                                <label style="display:block;font-size:0.8125rem;font-weight:600;margin-bottom:0.375rem;color:#737373;">SMTP-användare</label>
                                <input type="text" id="cfg-smtp-username" value="<?php echo htmlspecialchars(defined('SMTP_USERNAME') ? SMTP_USERNAME : ''); ?>" style="width:100%;padding:0.5rem 0.75rem;border:1px solid #e5e5e5;border-radius:0.375rem;font-size:0.875rem;">
                            </div>
                            <div>
                                <label style="display:block;font-size:0.8125rem;font-weight:600;margin-bottom:0.375rem;color:#737373;">SMTP-lösenord</label>
                                <input type="password" id="cfg-smtp-password" placeholder="Lämna tomt för att behålla" style="width:100%;padding:0.5rem 0.75rem;border:1px solid #e5e5e5;border-radius:0.375rem;font-size:0.875rem;">
                            </div>
                        </div>
                    </div>

                    <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #f5f5f5;">
                        <h3 style="font-size: 0.875rem; font-weight: 600; margin-bottom: 1rem; color: #737373;">GitHub</h3>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div>
                                <label style="display:block;font-size:0.8125rem;font-weight:600;margin-bottom:0.375rem;color:#737373;">Repo (org/namn)</label>
                                <input type="text" id="cfg-github-repo" value="<?php echo htmlspecialchars(defined('GITHUB_REPO') ? GITHUB_REPO : ''); ?>" placeholder="peysdev/kundnamn" style="width:100%;padding:0.5rem 0.75rem;border:1px solid #e5e5e5;border-radius:0.375rem;font-size:0.875rem;">
                            </div>
                            <div>
                                <label style="display:block;font-size:0.8125rem;font-weight:600;margin-bottom:0.375rem;color:#737373;">Token</label>
                                <input type="password" id="cfg-github-token" placeholder="Lämna tomt för att behålla" style="width:100%;padding:0.5rem 0.75rem;border:1px solid #e5e5e5;border-radius:0.375rem;font-size:0.875rem;">
                            </div>
                        </div>
                    </div>

                    <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #f5f5f5;">
                        <h3 style="font-size: 0.875rem; font-weight: 600; margin-bottom: 1rem; color: #737373;">Google Analytics</h3>
                        <div>
                            <label style="display:block;font-size:0.8125rem;font-weight:600;margin-bottom:0.375rem;color:#737373;">Mät-ID</label>
                            <input type="text" id="cfg-ga-id" value="<?php echo htmlspecialchars(defined('GOOGLE_ANALYTICS_ID') ? GOOGLE_ANALYTICS_ID : ''); ?>" placeholder="G-XXXXXXXXXX" style="width:100%;max-width:20rem;padding:0.5rem 0.75rem;border:1px solid #e5e5e5;border-radius:0.375rem;font-size:0.875rem;">
                        </div>
                    </div>

                    <div style="margin-top: 1.5rem;">
                        <button class="sa-btn sa-btn--primary" onclick="saveConfig()" id="btn-save-config">Spara konfiguration</button>
                        <span id="config-result" style="margin-left: 1rem; font-size: 0.8125rem;"></span>
                    </div>
                </div>
            </div>

            <!-- Error Log -->
            <div class="sa-panel sa-panel--full">
                <div class="sa-panel__header">
                    <h2>Felloggar</h2>
                    <button class="sa-btn sa-btn--secondary" onclick="refreshErrorLog()">Uppdatera</button>
                </div>
                <div class="sa-panel__body">
                    <?php if (!empty($errorLog)): ?>
                    <div class="sa-log" id="error-log-content"><?php echo htmlspecialchars($errorLog); ?></div>
                    <?php else: ?>
                    <p style="font-size: 0.875rem; color: #a3a3a3;" id="error-log-content">Inga felloggar hittades eller error_log är inte lasbar.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- File Integrity -->
            <div class="sa-panel sa-panel--full">
                <div class="sa-panel__header">
                    <h2>Filintegritet</h2>
                    <span class="sa-badge sa-badge--ok" id="integrity-badge">Ej kontrollerad</span>
                </div>
                <div class="sa-panel__body">
                    <p style="font-size: 0.875rem; color: #a3a3a3; margin-bottom: 1rem;">
                        Jämför core-filers status. Upptäcker om filer har ändrats manuellt eller saknas.
                    </p>
                    <button class="sa-btn sa-btn--secondary" onclick="checkIntegrity()">Kontrollera filer</button>
                    <div id="integrity-result" style="margin-top: 1rem;"></div>
                </div>
            </div>
        </div>

        <a href="/admin?action=deactivate-sa" class="sa-deactivate">Avaktivera Super Admin</a>
    </div>

    <div class="sa-toast" id="sa-toast"></div>

    <script>
    const CSRF_TOKEN = '<?php echo htmlspecialchars($csrfToken, ENT_QUOTES); ?>';
    const API_URL = '/api/super';

    function showToast(msg, duration, type) {
        duration = duration || 3000;
        type = type || 'default';
        const t = document.getElementById('sa-toast');

        // Reset classes
        t.className = 'sa-toast';

        // Add type class
        if (type === 'success') {
            t.className += ' sa-toast--success';
            t.innerHTML = '<span class="sa-toast__icon"><svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M2 7L5.5 10.5L12 3.5" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>' + msg;
        } else if (type === 'error') {
            t.className += ' sa-toast--error';
            t.innerHTML = '<span class="sa-toast__icon"><svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M3.5 3.5L10.5 10.5M10.5 3.5L3.5 10.5" stroke="white" stroke-width="2" stroke-linecap="round"/></svg></span>' + msg;
        } else {
            t.textContent = msg;
        }

        t.classList.add('show');
        setTimeout(function() { t.classList.remove('show'); }, duration);
    }

    function setLoading(btnId, loading) {
        const btn = document.getElementById(btnId);
        if (!btn) return;
        if (loading) {
            btn.disabled = true;
            btn.dataset.origText = btn.textContent;
            btn.innerHTML = '<span class="sa-spinner"></span> Laddar...';
        } else {
            btn.disabled = false;
            btn.textContent = btn.dataset.origText || 'Klar';
        }
    }

    function apiRequest(action, method, body) {
        method = method || 'GET';
        var opts = {
            method: method,
            headers: { 'X-CSRF-Token': CSRF_TOKEN }
        };
        if (body) {
            opts.headers['Content-Type'] = 'application/json';
            opts.body = JSON.stringify(body);
        }
        return fetch(API_URL + '?action=' + action, opts).then(function(r) { return r.json(); });
    }

    function checkUpdate() {
        setLoading('btn-check-update', true);
        apiRequest('check-update').then(function(data) {
            setLoading('btn-check-update', false);
            if (data.error) {
                showToast('Fel: ' + data.error, 4000, 'error');
            } else if (data.update_available) {
                document.getElementById('latest-version').textContent = data.latest_version;
                showToast('Ny version tillgänglig: ' + data.latest_version, 4000, 'success');
                setTimeout(function() { location.reload(); }, 1500);
            } else {
                showToast('Du kör redan senaste versionen!', 3000, 'success');
            }
        }).catch(function(e) {
            setLoading('btn-check-update', false);
            showToast('Nätverksfel', 4000, 'error');
        });
    }

    function applyUpdate() {
        if (!confirm('Vill du uppdatera? En backup skapas automatiskt.')) return;
        setLoading('btn-apply-update', true);
        setLoading('btn-apply-update-2', true);
        apiRequest('apply-update', 'POST').then(function(data) {
            setLoading('btn-apply-update', false);
            setLoading('btn-apply-update-2', false);
            if (data.success) {
                // Ta bort update-bannern
                var banner = document.querySelector('.sa-update-banner');
                if (banner) {
                    banner.style.transition = 'all 0.3s';
                    banner.style.opacity = '0';
                    banner.style.transform = 'translateY(-20px)';
                    setTimeout(function() { banner.remove(); }, 300);
                }

                // Uppdatera version i UI
                var versionBadge = document.querySelector('.sa-version');
                if (versionBadge && data.new_version) {
                    versionBadge.textContent = 'v' + data.new_version;
                }

                // Uppdatera panelens badge
                var updateBadge = document.querySelector('.sa-panel:nth-child(2) .sa-badge');
                if (updateBadge) {
                    updateBadge.className = 'sa-badge sa-badge--ok';
                    updateBadge.textContent = 'Senaste';
                }

                // Visa grön success-toast
                showToast('Uppdatering klar! Du kör nu senaste versionen.', 6000, 'success');

                // Ladda om efter en stund
                setTimeout(function() { location.reload(); }, 3000);
            } else {
                showToast('Fel: ' + (data.errors ? data.errors.join(', ') : 'Okänt fel'), 5000, 'error');
            }
        }).catch(function(e) {
            setLoading('btn-apply-update', false);
            setLoading('btn-apply-update-2', false);
            showToast('Nätverksfel vid uppdatering', 5000, 'error');
        });
    }

    function rollbackBackup(dirName) {
        if (!confirm('Återställ från backup ' + dirName + '? Nuvarande filer skrivs över.')) return;
        apiRequest('rollback', 'POST', { backup: dirName }).then(function(data) {
            if (data.success) {
                showToast('Återställning klar!');
                setTimeout(function() { location.reload(); }, 1500);
            } else {
                showToast('Fel: ' + (data.error || 'Okänt fel'));
            }
        }).catch(function() { showToast('Nätverksfel'); });
    }

    function deleteBackup(dirName) {
        if (!confirm('Ta bort backup ' + dirName + '?')) return;
        apiRequest('delete-backup', 'POST', { backup: dirName }).then(function(data) {
            if (data.success) {
                showToast('Backup borttagen');
                setTimeout(function() { location.reload(); }, 1000);
            } else {
                showToast('Fel: ' + (data.error || 'Okänt fel'));
            }
        }).catch(function() { showToast('Nätverksfel'); });
    }

    function changeCustomerPassword() {
        var input = document.getElementById('new-customer-password');
        var password = input.value.trim();
        if (password.length < 8) {
            document.getElementById('password-result').innerHTML = '<span style="color:#b91c1c;">Lösenordet måste vara minst 8 tecken</span>';
            return;
        }
        apiRequest('change-password', 'POST', { password: password }).then(function(data) {
            var el = document.getElementById('password-result');
            if (data.success) {
                el.innerHTML = '<span style="color:#15803d;">Lösenordet har ändrats!</span>';
                input.value = '';
                showToast('Lösenord ändrat!');
            } else {
                el.innerHTML = '<span style="color:#b91c1c;">Fel: ' + (data.error || 'Okänt') + '</span>';
            }
        }).catch(function() { showToast('Nätverksfel'); });
    }

    function testSmtp() {
        setLoading('btn-smtp-test', true);
        apiRequest('test-smtp', 'POST').then(function(data) {
            setLoading('btn-smtp-test', false);
            var el = document.getElementById('smtp-result');
            if (data.success) {
                el.innerHTML = '<span style="color: #15803d;">Testmail skickat till ' + (data.to || '') + '</span>';
                showToast('Testmail skickat!');
            } else {
                el.innerHTML = '<span style="color: #b91c1c;">Fel: ' + (data.error || 'Okant') + '</span>';
            }
        }).catch(function() {
            setLoading('btn-smtp-test', false);
            showToast('Nätverksfel');
        });
    }

    function refreshErrorLog() {
        apiRequest('error-log').then(function(data) {
            var el = document.getElementById('error-log-content');
            if (data.log) {
                el.className = 'sa-log';
                el.textContent = data.log;
            } else {
                el.textContent = 'Inga felloggar hittades.';
            }
        }).catch(function() { showToast('Nätverksfel'); });
    }

    function saveConfig() {
        var data = {
            site_url: document.getElementById('cfg-site-url').value.trim(),
            site_name: document.getElementById('cfg-site-name').value.trim(),
            site_description: document.getElementById('cfg-site-desc').value.trim(),
            contact_email: document.getElementById('cfg-contact-email').value.trim(),
            contact_phone: document.getElementById('cfg-contact-phone').value.trim(),
            admin_username: document.getElementById('cfg-admin-username').value.trim(),
            smtp_host: document.getElementById('cfg-smtp-host').value.trim(),
            smtp_port: document.getElementById('cfg-smtp-port').value.trim(),
            smtp_encryption: document.getElementById('cfg-smtp-encryption').value,
            smtp_username: document.getElementById('cfg-smtp-username').value.trim(),
            smtp_password: document.getElementById('cfg-smtp-password').value,
            github_repo: document.getElementById('cfg-github-repo').value.trim(),
            github_token: document.getElementById('cfg-github-token').value,
            ga_id: document.getElementById('cfg-ga-id').value.trim()
        };
        setLoading('btn-save-config', true);
        apiRequest('save-config', 'POST', data).then(function(res) {
            setLoading('btn-save-config', false);
            var el = document.getElementById('config-result');
            if (res.success) {
                showToast('Konfiguration sparad!', 3000, 'success');
                el.innerHTML = '<span style="color:#15803d;">Sparad!</span>';
                setTimeout(function() { location.reload(); }, 1500);
            } else {
                el.innerHTML = '<span style="color:#b91c1c;">Fel: ' + (res.error || 'Okänt') + '</span>';
                showToast('Fel: ' + (res.error || 'Okänt'), 4000, 'error');
            }
        }).catch(function() {
            setLoading('btn-save-config', false);
            showToast('Nätverksfel', 4000, 'error');
        });
    }

    function checkIntegrity() {
        apiRequest('check-integrity').then(function(data) {
            var el = document.getElementById('integrity-result');
            var badge = document.getElementById('integrity-badge');
            if (data.missing && data.missing.length > 0) {
                badge.className = 'sa-badge sa-badge--warn';
                badge.textContent = data.missing.length + ' saknas';
                el.innerHTML = '<div style="font-size: 0.8125rem; color: #92400e;"><strong>Saknade filer:</strong><br>' + data.missing.join('<br>') + '</div>';
            } else {
                badge.className = 'sa-badge sa-badge--ok';
                badge.textContent = 'Alla filer OK';
                el.innerHTML = '<div style="font-size: 0.8125rem; color: #15803d;">Alla core-filer är på plats.</div>';
            }
        }).catch(function() { showToast('Nätverksfel'); });
    }
    </script>
</body>
</html>
