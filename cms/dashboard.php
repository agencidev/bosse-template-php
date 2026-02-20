<?php
/**
 * CMS Admin Dashboard - EXAKT som Next.js-versionen
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../security/session.php';
require_once __DIR__ . '/../includes/version.php';

// Kräv inloggning
if (!is_logged_in()) {
    header('Location: /cms/admin.php');
    exit;
}

$username = $_SESSION['username'] ?? 'Admin';

// Asynkron auto-update: om AJAX-request, kör uppdatering och returnera JSON
if (isset($_GET['_auto_update']) && $_GET['_auto_update'] === '1') {
    require_once __DIR__ . '/../security/updater.php';
    header('Content-Type: application/json');
    header('Cache-Control: no-store');
    $result = auto_update();
    echo json_encode(['updated' => $result]);
    exit;
}

// Räkna inlägg
$projectCount = 0;
$publishedCount = 0;
$projectsFile = DATA_PATH . '/projects.json';
if (file_exists($projectsFile)) {
    $projects = json_decode(file_get_contents($projectsFile), true) ?? [];
    $projectCount = count($projects);
    foreach ($projects as $p) {
        if (($p['status'] ?? 'draft') === 'published') {
            $publishedCount++;
        }
    }
}

// PHP-version
$phpVersion = PHP_VERSION;
$phpOk = version_compare($phpVersion, '8.1', '>=');

// Diskutrymme
$diskFree = @disk_free_space(ROOT_PATH);
$diskTotal = @disk_total_space(ROOT_PATH);
$diskUsedMB = ($diskTotal && $diskFree) ? round(($diskTotal - $diskFree) / 1024 / 1024) : 0;

// Senaste uppdateringar
$updateLog = [];
$updateLogFile = DATA_PATH . '/update-log.json';
if (file_exists($updateLogFile)) {
    $logData = json_decode(file_get_contents($updateLogFile), true);
    if (is_array($logData)) {
        $updateLog = array_slice(array_reverse($logData), 0, 5);
    }
}
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - CMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DM Sans', sans-serif;
            background-color: #033234;
            min-height: 100vh;
        }
        .container {
            max-width: 40rem;
            margin: 0 auto;
            padding: 5rem 1.5rem;
        }
        .header {
            text-align: center;
            margin-bottom: 2.5rem;
        }
        .title {
            font-size: 2rem;
            font-weight: bold;
            color: rgba(255,255,255,1.0);
            margin-bottom: 0.75rem;
        }
        .subtitle {
            font-size: 1.125rem;
            color: rgba(255,255,255,0.50);
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }
        .card {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            background-color: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.10);
            border-radius: 1rem;
            text-decoration: none;
            transition: background-color 0.2s;
        }
        .card:hover {
            background-color: rgba(255,255,255,0.08);
        }
        .icon {
            width: 2.75rem;
            height: 2.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0.75rem;
            color: rgba(255,255,255,0.40);
            transition: color 0.2s;
        }
        .card:hover .icon {
            color: #379b83;
        }
        .icon svg {
            width: 1.75rem;
            height: 1.75rem;
        }
        .label {
            font-size: 0.9375rem;
            font-weight: 600;
            color: rgba(255,255,255,0.65);
        }
        .badge {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            min-width: 1.25rem;
            height: 1.25rem;
            padding: 0 0.375rem;
            background: #379b83;
            color: white;
            font-size: 0.625rem;
            font-weight: 700;
            border-radius: 9999px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .logout {
            text-align: center;
            margin-top: 2.5rem;
        }
        .logout button {
            background: none;
            border: none;
            color: rgba(255,255,255,0.40);
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: color 0.2s;
        }
        .logout button:hover {
            color: rgba(255,255,255,0.65);
        }
        /* Traffic overview */
        .traffic-section {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid rgba(255,255,255,0.10);
        }
        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
        }
        .section-title {
            font-size: 1rem;
            font-weight: 600;
            color: rgba(255,255,255,1.0);
        }
        .section-period {
            font-size: 0.75rem;
            color: rgba(255,255,255,0.4);
            background: rgba(255,255,255,0.05);
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            border: 1px solid rgba(255,255,255,0.08);
        }
        .traffic-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.75rem;
            margin-bottom: 1rem;
        }
        .traffic-card {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.10);
            border-radius: 1rem;
            padding: 1rem;
        }
        .traffic-label {
            font-size: 0.6875rem;
            color: rgba(255,255,255,0.45);
            margin-bottom: 0.375rem;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        .traffic-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
            line-height: 1.2;
        }
        .traffic-change {
            display: inline-flex;
            align-items: center;
            gap: 0.2rem;
            font-size: 0.6875rem;
            font-weight: 600;
            margin-top: 0.25rem;
        }
        .traffic-change.up { color: #34d399; }
        .traffic-change.down { color: #f87171; }
        .chart-container {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.10);
            border-radius: 1rem;
            padding: 1.25rem;
            margin-bottom: 1rem;
        }
        .chart-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
        }
        .chart-title {
            font-size: 0.8125rem;
            font-weight: 600;
            color: rgba(255,255,255,0.8);
        }
        .chart-bars {
            display: flex;
            align-items: flex-end;
            gap: 0.375rem;
            height: 5rem;
        }
        .chart-col {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.375rem;
        }
        .chart-bar {
            width: 100%;
            border-radius: 0.25rem 0.25rem 0 0;
            background: linear-gradient(to top, #379b83, rgba(55,155,131,0.3));
            transition: height 0.5s ease;
        }
        .chart-day {
            font-size: 0.625rem;
            color: rgba(255,255,255,0.35);
        }
        .pages-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
        }
        .pages-card {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.10);
            border-radius: 1rem;
            padding: 1rem;
        }
        .pages-title {
            font-size: 0.8125rem;
            font-weight: 600;
            color: rgba(255,255,255,0.8);
            margin-bottom: 0.75rem;
        }
        .pages-list {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .pages-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .pages-name {
            font-size: 0.8125rem;
            color: rgba(255,255,255,0.65);
        }
        .pages-count {
            font-size: 0.8125rem;
            font-weight: 600;
            color: rgba(255,255,255,0.9);
        }
        .pages-bar-wrap {
            flex: 1;
            height: 4px;
            background: rgba(255,255,255,0.06);
            border-radius: 9999px;
            margin: 0 0.75rem;
        }
        .pages-bar-fill {
            height: 100%;
            border-radius: 9999px;
            background: #379b83;
        }
        @media (max-width: 640px) {
            .traffic-grid { grid-template-columns: repeat(2, 1fr); }
            .pages-grid { grid-template-columns: 1fr; }
        }
        .stats-section {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid rgba(255,255,255,0.10);
        }
        .stats-title {
            font-size: 1rem;
            font-weight: 600;
            color: rgba(255,255,255,1.0);
            margin-bottom: 1rem;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .stat-card {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.10);
            border-radius: 1rem;
            padding: 1rem;
        }
        .stat-label {
            font-size: 0.75rem;
            color: rgba(255,255,255,0.50);
            margin-bottom: 0.25rem;
        }
        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: rgba(255,255,255,1.0);
        }
        .stat-value.ok {
            color: #16a34a;
        }
        .stat-value.warning {
            color: #ca8a04;
        }
        .update-log {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.10);
            border-radius: 1rem;
            padding: 1rem;
        }
        .update-log-title {
            font-size: 0.875rem;
            font-weight: 600;
            color: rgba(255,255,255,1.0);
            margin-bottom: 0.75rem;
        }
        .update-log-list {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .update-log-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.8125rem;
            color: rgba(255,255,255,0.65);
        }
        .update-log-date {
            color: rgba(255,255,255,0.40);
            font-size: 0.75rem;
            min-width: 5rem;
        }
        .update-log-version {
            font-weight: 600;
            color: rgba(255,255,255,1.0);
        }
        .update-log-success {
            color: #16a34a;
        }
        .update-log-error {
            color: #dc2626;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/admin-bar.php'; ?>
    <div class="container">
        <div class="header">
            <h1 class="title">Hej! 👋</h1>
            <p class="subtitle">Välkommen till kontrollpanelen. Vad vill du göra?</p>
        </div>

        <div class="grid">
            <a href="/cms/projects/new" class="card">
                <div class="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                </div>
                <span class="label">Skapa inlägg</span>
            </a>

            <a href="/cms/projects/" class="card">
                <?php if ($projectCount > 0): ?>
                <span class="badge"><?php echo $projectCount; ?></span>
                <?php endif; ?>
                <div class="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                    </svg>
                </div>
                <span class="label">Inlägg</span>
            </a>

            <a href="/" class="card">
                <div class="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75" />
                    </svg>
                </div>
                <span class="label">Redigera hemsidan</span>
            </a>

            <a href="/cms/support" class="card">
                <div class="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z" />
                    </svg>
                </div>
                <span class="label">Support</span>
            </a>

            <a href="/cms/seo" class="card">
                <div class="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                </div>
                <span class="label">SEO</span>
            </a>

            <a href="/cms/ai" class="card">
                <span class="badge" style="background: #379b83; font-size: 0.5rem; padding: 0.15rem 0.4rem; letter-spacing: 0.05em;">BETA</span>
                <div class="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456ZM16.894 20.567 16.5 21.75l-.394-1.183a2.25 2.25 0 0 0-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 0 0 1.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 0 0 1.423 1.423l1.183.394-1.183.394a2.25 2.25 0 0 0-1.423 1.423Z" />
                    </svg>
                </div>
                <span class="label">AI</span>
            </a>

            <a href="/cms/settings" class="card">
                <div class="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                </div>
                <span class="label">Inställningar</span>
            </a>

            <?php if (file_exists(__DIR__ . '/extensions/dashboard-cards.php')) include __DIR__ . '/extensions/dashboard-cards.php'; ?>

            <?php if (is_super_admin()): ?>
            <a href="/super-admin" class="card" style="border: 1px solid rgba(245,158,11,0.3); background: rgba(245,158,11,0.10);">
                <div class="icon" style="color: #f59e0b;">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                </div>
                <span class="label" style="color: #f59e0b;">Super Admin</span>
            </a>
            <?php endif; ?>
        </div>

        <!-- Trafik -->
        <div class="traffic-section">
            <div class="section-header">
                <h2 class="section-title">Trafik</h2>
                <span class="section-period">Senaste 30 dagarna</span>
            </div>

            <div class="traffic-grid">
                <div class="traffic-card">
                    <div class="traffic-label">Besökare</div>
                    <div class="traffic-value">1 247</div>
                    <div class="traffic-change up">
                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="18 15 12 9 6 15"/></svg>
                        +14.5%
                    </div>
                </div>
                <div class="traffic-card">
                    <div class="traffic-label">Kontaktförfrågningar</div>
                    <div class="traffic-value">18</div>
                    <div class="traffic-change up">
                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="18 15 12 9 6 15"/></svg>
                        +5 st
                    </div>
                </div>
                <div class="traffic-card">
                    <div class="traffic-label">Google-klick</div>
                    <div class="traffic-value">724</div>
                    <div class="traffic-change up">
                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="18 15 12 9 6 15"/></svg>
                        +22.3%
                    </div>
                </div>
            </div>

            <div class="chart-container">
                <div class="chart-header">
                    <span class="chart-title">Besökare per dag</span>
                </div>
                <div class="chart-bars">
                    <div class="chart-col"><div class="chart-bar" style="height:68%"></div><span class="chart-day">Mån</span></div>
                    <div class="chart-col"><div class="chart-bar" style="height:72%"></div><span class="chart-day">Tis</span></div>
                    <div class="chart-col"><div class="chart-bar" style="height:85%"></div><span class="chart-day">Ons</span></div>
                    <div class="chart-col"><div class="chart-bar" style="height:60%"></div><span class="chart-day">Tor</span></div>
                    <div class="chart-col"><div class="chart-bar" style="height:100%"></div><span class="chart-day">Fre</span></div>
                    <div class="chart-col"><div class="chart-bar" style="height:48%"></div><span class="chart-day">Lör</span></div>
                    <div class="chart-col"><div class="chart-bar" style="height:35%"></div><span class="chart-day">Sön</span></div>
                </div>
            </div>

            <div class="pages-grid">
                <div class="pages-card">
                    <div class="pages-title">Populära sidor</div>
                    <div class="pages-list">
                        <div class="pages-row">
                            <span class="pages-name">/</span>
                            <div class="pages-bar-wrap"><div class="pages-bar-fill" style="width:100%"></div></div>
                            <span class="pages-count">1 204</span>
                        </div>
                        <div class="pages-row">
                            <span class="pages-name">/kontakt</span>
                            <div class="pages-bar-wrap"><div class="pages-bar-fill" style="width:42%"></div></div>
                            <span class="pages-count">508</span>
                        </div>
                        <div class="pages-row">
                            <span class="pages-name">/projekt</span>
                            <div class="pages-bar-wrap"><div class="pages-bar-fill" style="width:28%"></div></div>
                            <span class="pages-count">341</span>
                        </div>
                        <div class="pages-row">
                            <span class="pages-name">/om-oss</span>
                            <div class="pages-bar-wrap"><div class="pages-bar-fill" style="width:15%"></div></div>
                            <span class="pages-count">184</span>
                        </div>
                    </div>
                </div>
                <div class="pages-card">
                    <div class="pages-title">Trafikkällor</div>
                    <div class="pages-list">
                        <div class="pages-row">
                            <span class="pages-name">Google</span>
                            <div class="pages-bar-wrap"><div class="pages-bar-fill" style="width:100%"></div></div>
                            <span class="pages-count">58%</span>
                        </div>
                        <div class="pages-row">
                            <span class="pages-name">Direkt</span>
                            <div class="pages-bar-wrap"><div class="pages-bar-fill" style="width:40%"></div></div>
                            <span class="pages-count">23%</span>
                        </div>
                        <div class="pages-row">
                            <span class="pages-name">Sociala medier</span>
                            <div class="pages-bar-wrap"><div class="pages-bar-fill" style="width:22%"></div></div>
                            <span class="pages-count">13%</span>
                        </div>
                        <div class="pages-row">
                            <span class="pages-name">Övriga</span>
                            <div class="pages-bar-wrap"><div class="pages-bar-fill" style="width:10%"></div></div>
                            <span class="pages-count">6%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Systemstatus -->
        <div class="stats-section">
            <h2 class="stats-title">Systemstatus</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">PHP-version</div>
                    <div class="stat-value <?php echo $phpOk ? 'ok' : 'warning'; ?>"><?php echo htmlspecialchars($phpVersion); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Bosse-version</div>
                    <div class="stat-value"><?php echo htmlspecialchars(BOSSE_VERSION); ?></div>
                </div>
            </div>

            <?php if (!empty($updateLog)): ?>
            <div class="update-log">
                <div class="update-log-title">Senaste ändringar</div>
                <div class="update-log-list">
                    <?php foreach ($updateLog as $entry): ?>
                    <div class="update-log-item">
                        <span class="update-log-date"><?php echo htmlspecialchars(substr($entry['date'] ?? '', 0, 10)); ?></span>
                        <span class="update-log-version <?php echo ($entry['type'] ?? '') === 'success' ? 'update-log-success' : (($entry['type'] ?? '') === 'error' ? 'update-log-error' : ''); ?>">
                            v<?php echo htmlspecialchars($entry['version'] ?? '?'); ?>
                        </span>
                        <span><?php echo htmlspecialchars($entry['message'] ?? ''); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php include __DIR__ . '/../includes/agenci-badge.php'; ?>
    <script>fetch('/dashboard?_auto_update=1').catch(function(){});</script>
</body>
</html>
