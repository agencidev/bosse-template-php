<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../security/session.php';
require_once __DIR__ . '/../security/csrf.php';

if (!is_logged_in()) {
    header('Location: /cms/admin.php');
    exit;
}

$success = '';
$error = '';

// Läs innehåll
$contentFile = DATA_PATH . '/content.json';
$content = [];
if (file_exists($contentFile)) {
    $content = json_decode(file_get_contents($contentFile), true) ?? [];
}

// Hämta nuvarande meta-värden
$metaTitle = $content['home']['meta_title'] ?? (defined('SITE_NAME') ? SITE_NAME : '');
$metaDescription = $content['home']['meta_description'] ?? (defined('SITE_DESCRIPTION') ? SITE_DESCRIPTION : '');
$siteUrl = defined('SITE_URL') ? SITE_URL : '';

// Hantera formulär
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();

    $newTitle = trim($_POST['meta_title'] ?? '');
    $newDescription = trim($_POST['meta_description'] ?? '');

    if (empty($newTitle)) {
        $error = 'Meta-titel krävs.';
    } else {
        // Uppdatera content
        if (!isset($content['home'])) {
            $content['home'] = [];
        }
        $content['home']['meta_title'] = $newTitle;
        $content['home']['meta_description'] = $newDescription;

        if (file_put_contents($contentFile, json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX)) {
            $success = 'Meta-information uppdaterad!';
            $metaTitle = $newTitle;
            $metaDescription = $newDescription;
        } else {
            $error = 'Kunde inte spara ändringar.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEO - CMS</title>
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
            color: rgba(255,255,255,1.0);
        }
        .page-content {
            padding: 3rem 1.5rem;
        }
        .container {
            max-width: 36rem;
            margin: 0 auto;
        }
        .back-link {
            display: inline-block;
            color: rgba(255,255,255,0.50);
            text-decoration: none;
            font-size: 0.875rem;
            margin-bottom: 1.5rem;
            transition: color 0.2s;
        }
        .back-link:hover {
            color: rgba(255,255,255,1.0);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        .title {
            font-size: 2rem;
            font-weight: bold;
            color: rgba(255,255,255,1.0);
        }
        .status-badge {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 9999px;
            padding: 0.5rem 1rem;
        }
        .status-dot {
            width: 0.5rem;
            height: 0.5rem;
            background: #22c55e;
            border-radius: 50%;
        }
        .status-text {
            color: #166534;
            font-size: 0.875rem;
            font-weight: 500;
        }
        .description {
            color: rgba(255,255,255,0.50);
            font-size: 0.875rem;
            line-height: 1.6;
            margin-bottom: 2rem;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .grid-4 {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .card {
            background: rgba(255,255,255,0.05);
            border-radius: 1.5rem;
            border: 1px solid rgba(255,255,255,0.10);
            padding: 1.5rem;
        }
        .card-title {
            font-size: 0.875rem;
            color: rgba(255,255,255,0.50);
            margin-bottom: 0.5rem;
        }
        .card-value {
            font-size: 2.5rem;
            font-weight: bold;
            color: rgba(255,255,255,1.0);
            margin-bottom: 0.5rem;
        }
        .card-change {
            display: inline-block;
            font-size: 0.875rem;
            font-weight: 500;
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            background: #f0fdf4;
            color: #16a34a;
        }
        .card-subtitle {
            font-size: 0.75rem;
            color: rgba(255,255,255,0.50);
            margin-top: 0.5rem;
        }
        .chart {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 0.5rem;
            height: 6rem;
            margin-top: 1rem;
        }
        .chart-bar {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .chart-bar-fill {
            width: 100%;
            background: linear-gradient(to top, #379b83, rgba(55, 155, 131, 0.3));
            border-radius: 0.25rem 0.25rem 0 0;
            transition: height 0.5s ease;
        }
        .chart-label {
            font-size: 0.75rem;
            color: rgba(255,255,255,0.50);
            margin-top: 0.5rem;
        }
        .status-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .status-card {
            background: rgba(255,255,255,0.05);
            border-radius: 1rem;
            border: 1px solid rgba(255,255,255,0.10);
            padding: 1rem;
        }
        .status-card-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }
        .status-card-icon {
            width: 1.25rem;
            height: 1.25rem;
            color: rgba(255,255,255,1.0);
        }
        .status-card-title {
            font-size: 0.875rem;
            font-weight: 500;
            color: rgba(255,255,255,1.0);
        }
        .status-indicator {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .status-indicator-dot {
            width: 0.5rem;
            height: 0.5rem;
            border-radius: 50%;
            background: #22c55e;
        }
        .status-indicator-text {
            font-size: 0.875rem;
            color: #16a34a;
        }
        .activity-card {
            background: rgba(255,255,255,0.05);
            border-radius: 1.5rem;
            border: 1px solid rgba(255,255,255,0.10);
            padding: 1.5rem;
        }
        .activity-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: rgba(255,255,255,1.0);
            margin-bottom: 1rem;
        }
        .activity-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .activity-item {
            display: flex;
            gap: 1rem;
        }
        .activity-date {
            font-size: 0.75rem;
            color: rgba(255,255,255,0.50);
            width: 3.5rem;
            padding-top: 0.125rem;
        }
        .activity-text {
            font-size: 0.875rem;
            color: rgba(255,255,255,1.0);
            flex: 1;
        }
        @media (max-width: 768px) {
            .grid, .grid-4, .status-grid {
                grid-template-columns: 1fr;
            }
        }
        .meta-card {
            background: rgba(255,255,255,0.05);
            border-radius: 1.5rem;
            border: 1px solid rgba(255,255,255,0.10);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .meta-card-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: rgba(255,255,255,1.0);
            margin-bottom: 1rem;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: rgba(255,255,255,1.0);
            margin-bottom: 0.375rem;
        }
        .form-input, .form-textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 0.5rem;
            font-size: 1rem;
            font-family: inherit;
            outline: none;
            transition: all 0.2s;
            background-color: rgba(255,255,255,0.05);
            color: white;
        }
        .form-input:focus, .form-textarea:focus {
            border-color: #379b83;
            box-shadow: 0 0 0 3px rgba(55, 155, 131, 0.1);
        }
        .form-textarea {
            resize: vertical;
            min-height: 80px;
        }
        .char-count {
            font-size: 0.75rem;
            color: rgba(255,255,255,0.50);
            margin-top: 0.25rem;
            text-align: right;
        }
        .char-count.warning {
            color: #ca8a04;
        }
        .char-count.error {
            color: #dc2626;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1.5rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
        }
        .btn-primary {
            background: #379b83;
            color: white;
        }
        .btn-primary:hover {
            background: #2e8570;
        }
        .alert {
            padding: 0.875rem 1rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            margin-bottom: 1.5rem;
        }
        .alert-success {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #166534;
        }
        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
        }
        .google-preview {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.10);
            border-radius: 1rem;
            padding: 1.25rem;
            margin-top: 1.5rem;
        }
        .google-preview-label {
            font-size: 0.75rem;
            color: rgba(255,255,255,0.50);
            margin-bottom: 0.75rem;
            font-weight: 500;
        }
        .google-preview-title {
            font-size: 1.25rem;
            color: #1a0dab;
            font-weight: 400;
            margin-bottom: 0.25rem;
            line-height: 1.3;
            text-decoration: none;
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .google-preview-url {
            font-size: 0.875rem;
            color: #006621;
            margin-bottom: 0.25rem;
        }
        .google-preview-description {
            font-size: 0.875rem;
            color: #545454;
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/admin-bar.php'; ?>
    <div class="page-content">
    <div class="container">
        <a href="/dashboard" class="back-link">← Tillbaka</a>
        
        <div class="header">
            <h1 class="title">SEO</h1>
            <div class="status-badge">
                <div class="status-dot"></div>
                <span class="status-text">Övervakas</span>
            </div>
        </div>

        <p class="description">
            SEO är ett kompletterande mervärde. Vi arbetar med grundläggande optimering och övervakning
            som stöd för webbens kvalitet – inte som en primär tjänst eller med specifika resultatlöften.
        </p>

        <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Meta-redigerare -->
        <div class="meta-card">
            <h2 class="meta-card-title">Meta-information för startsidan</h2>
            <form method="POST">
                <?php echo csrf_field(); ?>

                <div class="form-group">
                    <label class="form-label" for="meta_title">Meta-titel</label>
                    <input type="text" id="meta_title" name="meta_title" class="form-input"
                           value="<?php echo htmlspecialchars($metaTitle); ?>"
                           placeholder="Företagsnamn - Kort beskrivning"
                           oninput="updatePreview(); updateCharCount('meta_title', 60)">
                    <div class="char-count" id="meta_title_count">
                        <?php echo mb_strlen($metaTitle); ?>/60 tecken
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="meta_description">Meta-beskrivning</label>
                    <textarea id="meta_description" name="meta_description" class="form-textarea"
                              placeholder="En kort beskrivning av er verksamhet..."
                              oninput="updatePreview(); updateCharCount('meta_description', 160)"><?php echo htmlspecialchars($metaDescription); ?></textarea>
                    <div class="char-count" id="meta_description_count">
                        <?php echo mb_strlen($metaDescription); ?>/160 tecken
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Spara</button>

                <!-- Google Preview -->
                <div class="google-preview">
                    <div class="google-preview-label">Så här kan det se ut i Google:</div>
                    <a class="google-preview-title" id="preview-title"><?php echo htmlspecialchars($metaTitle ?: 'Din sidtitel'); ?></a>
                    <div class="google-preview-url"><?php echo htmlspecialchars($siteUrl ?: 'https://dinwebbplats.se'); ?></div>
                    <div class="google-preview-description" id="preview-description"><?php echo htmlspecialchars($metaDescription ?: 'Din meta-beskrivning kommer visas här...'); ?></div>
                </div>
            </form>
        </div>

        <script>
        function updatePreview() {
            const title = document.getElementById('meta_title').value || 'Din sidtitel';
            const desc = document.getElementById('meta_description').value || 'Din meta-beskrivning kommer visas här...';
            document.getElementById('preview-title').textContent = title;
            document.getElementById('preview-description').textContent = desc;
        }

        function updateCharCount(fieldId, limit) {
            const field = document.getElementById(fieldId);
            const countEl = document.getElementById(fieldId + '_count');
            const len = field.value.length;

            countEl.textContent = len + '/' + limit + ' tecken';
            countEl.className = 'char-count';

            if (len > limit) {
                countEl.classList.add('error');
            } else if (len > limit * 0.9) {
                countEl.classList.add('warning');
            }
        }

        // Initial update
        updateCharCount('meta_title', 60);
        updateCharCount('meta_description', 160);
        </script>

        <div class="grid">
            <div class="card">
                <div class="card-title">Besökare senaste 30 dagar</div>
                <div class="card-value">1,247</div>
                <span class="card-change">+14.5%</span>
                <div class="card-subtitle">Jämfört med föregående period</div>
            </div>

            <div class="card">
                <div class="card-title">Synlighet över tid</div>
                <div class="chart">
                    <div class="chart-bar">
                        <div class="chart-bar-fill" style="height: 70%;"></div>
                        <span class="chart-label">Aug</span>
                    </div>
                    <div class="chart-bar">
                        <div class="chart-bar-fill" style="height: 80%;"></div>
                        <span class="chart-label">Sep</span>
                    </div>
                    <div class="chart-bar">
                        <div class="chart-bar-fill" style="height: 75%;"></div>
                        <span class="chart-label">Okt</span>
                    </div>
                    <div class="chart-bar">
                        <div class="chart-bar-fill" style="height: 87%;"></div>
                        <span class="chart-label">Nov</span>
                    </div>
                    <div class="chart-bar">
                        <div class="chart-bar-fill" style="height: 97%;"></div>
                        <span class="chart-label">Dec</span>
                    </div>
                    <div class="chart-bar">
                        <div class="chart-bar-fill" style="height: 100%;"></div>
                        <span class="chart-label">Jan</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="status-grid">
            <div class="status-card">
                <div class="status-card-header">
                    <svg class="status-card-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span class="status-card-title">Teknisk hälsa</span>
                </div>
                <div class="status-indicator">
                    <div class="status-indicator-dot"></div>
                    <span class="status-indicator-text">Godkänd</span>
                </div>
            </div>

            <div class="status-card">
                <div class="status-card-header">
                    <svg class="status-card-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span class="status-card-title">Innehåll</span>
                </div>
                <div class="status-indicator">
                    <div class="status-indicator-dot"></div>
                    <span class="status-indicator-text">Optimerat</span>
                </div>
            </div>

            <div class="status-card">
                <div class="status-card-header">
                    <svg class="status-card-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
                    </svg>
                    <span class="status-card-title">Struktur</span>
                </div>
                <div class="status-indicator">
                    <div class="status-indicator-dot"></div>
                    <span class="status-indicator-text">Godkänd</span>
                </div>
            </div>

            <div class="status-card">
                <div class="status-card-header">
                    <svg class="status-card-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <span class="status-card-title">Indexering</span>
                </div>
                <div class="status-indicator">
                    <div class="status-indicator-dot"></div>
                    <span class="status-indicator-text">Aktiv</span>
                </div>
            </div>
        </div>

        <div class="grid-4">
            <div class="card">
                <div class="card-title">Indexerade sidor</div>
                <div class="card-value" style="font-size: 2rem;">24</div>
                <div class="card-subtitle">Stabil</div>
            </div>

            <div class="card">
                <div class="card-title">Laddningstid</div>
                <div class="card-value" style="font-size: 2rem;">1.2s</div>
                <div class="card-subtitle">Bra</div>
            </div>

            <div class="card">
                <div class="card-title">Mobilanpassning</div>
                <div class="card-value" style="font-size: 2rem;">100%</div>
                <div class="card-subtitle">Bra</div>
            </div>

            <div class="card">
                <div class="card-title">Säkerhetspoäng</div>
                <div class="card-value" style="font-size: 2rem;">A+</div>
                <div class="card-subtitle">Bra</div>
            </div>
        </div>

        <div class="activity-card">
            <h2 class="activity-title">Senaste aktivitet</h2>
            <div class="activity-list">
                <div class="activity-item">
                    <span class="activity-date">13 jan</span>
                    <span class="activity-text">Metadata uppdaterad på startsidan</span>
                </div>
                <div class="activity-item">
                    <span class="activity-date">11 jan</span>
                    <span class="activity-text">Bildoptimering genomförd (12 bilder)</span>
                </div>
                <div class="activity-item">
                    <span class="activity-date">8 jan</span>
                    <span class="activity-text">Strukturerad data verifierad</span>
                </div>
                <div class="activity-item">
                    <span class="activity-date">5 jan</span>
                    <span class="activity-text">Sitemap uppdaterad</span>
                </div>
                <div class="activity-item">
                    <span class="activity-date">2 jan</span>
                    <span class="activity-text">SSL-certifikat kontrollerat</span>
                </div>
            </div>
        </div>
    </div>
    </div>
</body>
</html>
