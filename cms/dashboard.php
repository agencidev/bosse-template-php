<?php
/**
 * CMS Admin Dashboard - EXAKT som Next.js-versionen
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../security/session.php';
require_once __DIR__ . '/../security/csrf.php';

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

// API för att uppdatera kategori
if (isset($_GET['_update_category']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    header('Cache-Control: no-store');

    // Verifiera CSRF
    $csrfHeader = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (empty($csrfHeader) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfHeader)) {
        http_response_code(403);
        echo json_encode(['error' => 'Ogiltig CSRF-token']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $postId = $input['id'] ?? '';
    $newCategory = $input['category'] ?? '';

    $projectsFile = DATA_PATH . '/projects.json';
    if (file_exists($projectsFile)) {
        $projects = json_decode(file_get_contents($projectsFile), true) ?? [];
        foreach ($projects as &$project) {
            if ($project['id'] === $postId) {
                $project['category'] = $newCategory;
                break;
            }
        }
        file_put_contents($projectsFile, json_encode($projects, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Filen hittades inte']);
    }
    exit;
}

// Ladda alla inlägg
$projects = [];
$projectsFile = DATA_PATH . '/projects.json';
if (file_exists($projectsFile)) {
    $projects = json_decode(file_get_contents($projectsFile), true) ?? [];
}

// Räkna kategorier
$categories = [];
foreach ($projects as $p) {
    $cat = $p['category'] ?? 'Okategoriserat';
    $categories[$cat] = ($categories[$cat] ?? 0) + 1;
}
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - CMS</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #fafafa;
            min-height: 100vh;
        }
        .container {
            max-width: 42rem;
            margin: 0 auto;
            padding: 4rem 1.5rem;
        }
        .header {
            text-align: center;
            margin-bottom: 2.5rem;
        }
        .title {
            font-size: 2.25rem;
            font-weight: bold;
            color: #18181b;
            margin-bottom: 0.75rem;
        }
        .subtitle {
            font-size: 1.125rem;
            color: #737373;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }
        .card {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            background-color: white;
            border: 1px solid #e5e5e5;
            border-radius: 1rem;
            text-decoration: none;
            transition: background-color 0.2s;
        }
        .card:hover {
            background-color: #f5f5f5;
        }
        .icon {
            width: 2.5rem;
            height: 2.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0.75rem;
            color: #a3a3a3;
            transition: color 0.2s;
        }
        .card:hover .icon {
            color: #ff5722;
        }
        .icon svg {
            width: 1.75rem;
            height: 1.75rem;
        }
        .label {
            font-size: 0.875rem;
            font-weight: 600;
            color: #18181b;
        }
        .logout {
            text-align: center;
            margin-top: 2.5rem;
        }
        .logout button {
            background: none;
            border: none;
            color: #a3a3a3;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: color 0.2s;
        }
        .logout button:hover {
            color: #737373;
        }
        /* Inläggslista */
        .posts-section {
            margin-top: 2.5rem;
            background: white;
            border: 1px solid #e5e5e5;
            border-radius: 1rem;
            overflow: hidden;
        }
        .posts-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid #e5e5e5;
            background: #fafafa;
        }
        .posts-header h2 {
            font-size: 1rem;
            font-weight: 600;
            color: #18181b;
        }
        .posts-count {
            font-size: 0.75rem;
            color: #737373;
            background: #f5f5f5;
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
        }
        .posts-list {
            list-style: none;
        }
        .post-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.875rem 1.25rem;
            border-bottom: 1px solid #f5f5f5;
            transition: background-color 0.15s;
        }
        .post-item:last-child {
            border-bottom: none;
        }
        .post-item:hover {
            background-color: #fafafa;
        }
        .post-thumb {
            width: 3rem;
            height: 3rem;
            border-radius: 0.5rem;
            object-fit: cover;
            background: #f5f5f5;
            flex-shrink: 0;
        }
        .post-info {
            flex: 1;
            min-width: 0;
        }
        .post-title {
            font-size: 0.875rem;
            font-weight: 600;
            color: #18181b;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-bottom: 0.125rem;
        }
        .post-meta {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.75rem;
            color: #a3a3a3;
        }
        .post-status {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            font-size: 0.625rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 0.125rem 0.375rem;
            border-radius: 9999px;
        }
        .status-published {
            background: #dcfce7;
            color: #166534;
        }
        .status-draft {
            background: #fef3c7;
            color: #92400e;
        }
        .post-category {
            position: relative;
        }
        .category-select {
            appearance: none;
            background: #f5f5f5;
            border: 1px solid #e5e5e5;
            border-radius: 0.375rem;
            padding: 0.25rem 1.5rem 0.25rem 0.5rem;
            font-size: 0.75rem;
            color: #525252;
            cursor: pointer;
            transition: all 0.15s;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%23a3a3a3'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m19 9-7 7-7-7'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.25rem center;
            background-size: 1rem;
        }
        .category-select:hover {
            border-color: #d4d4d4;
            background-color: #fafafa;
        }
        .category-select:focus {
            outline: none;
            border-color: #ff5722;
            box-shadow: 0 0 0 2px rgba(255, 87, 34, 0.1);
        }
        .post-actions {
            display: flex;
            gap: 0.5rem;
        }
        .post-action {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 2rem;
            height: 2rem;
            border-radius: 0.375rem;
            color: #a3a3a3;
            transition: all 0.15s;
            text-decoration: none;
        }
        .post-action:hover {
            background: #f5f5f5;
            color: #525252;
        }
        .post-action svg {
            width: 1rem;
            height: 1rem;
        }
        .posts-empty {
            padding: 2rem;
            text-align: center;
            color: #a3a3a3;
        }
        .posts-empty svg {
            width: 2.5rem;
            height: 2.5rem;
            margin-bottom: 0.75rem;
            color: #d4d4d4;
        }
        .posts-footer {
            padding: 0.75rem 1.25rem;
            border-top: 1px solid #e5e5e5;
            background: #fafafa;
            text-align: center;
        }
        .posts-footer a {
            font-size: 0.75rem;
            color: #ff5722;
            text-decoration: none;
            font-weight: 500;
        }
        .posts-footer a:hover {
            text-decoration: underline;
        }
        .category-saved {
            animation: categorySaved 0.3s ease;
        }
        @keyframes categorySaved {
            0%, 100% { background-color: #f5f5f5; }
            50% { background-color: #dcfce7; }
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

            <a href="/cms/ai" class="card" style="position: relative;">
                <span style="position: absolute; top: 0.5rem; right: 0.5rem; background: #8b5cf6; color: white; font-size: 0.5rem; font-weight: 700; padding: 0.15rem 0.4rem; border-radius: 9999px; letter-spacing: 0.05em; text-transform: uppercase;">Beta</span>
                <div class="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456ZM16.894 20.567 16.5 21.75l-.394-1.183a2.25 2.25 0 0 0-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 0 0 1.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 0 0 1.423 1.423l1.183.394-1.183.394a2.25 2.25 0 0 0-1.423 1.423Z" />
                    </svg>
                </div>
                <span class="label">AI</span>
            </a>

            <a href="https://analytics.google.com" target="_blank" class="card">
                <div class="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                    </svg>
                </div>
                <span class="label">Google</span>
            </a>

            <?php if (is_super_admin()): ?>
            <a href="/super-admin" class="card" style="border: 1px solid #f59e0b; background: #fffbeb;">
                <div class="icon" style="color: #f59e0b;">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                </div>
                <span class="label" style="color: #92400e;">Super Admin</span>
            </a>
            <?php endif; ?>
        </div>

        <!-- Senaste inlägg -->
        <div class="posts-section">
            <div class="posts-header">
                <h2>Alla inlägg</h2>
                <span class="posts-count"><?php echo count($projects); ?> inlägg</span>
            </div>
            <?php if (empty($projects)): ?>
            <div class="posts-empty">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                </svg>
                <p>Inga inlägg ännu. Skapa ditt första inlägg!</p>
            </div>
            <?php else: ?>
            <ul class="posts-list">
                <?php
                // Sortera efter datum (nyast först)
                usort($projects, function($a, $b) {
                    return strtotime($b['createdAt'] ?? '0') - strtotime($a['createdAt'] ?? '0');
                });
                // Visa max 10 senaste
                $displayPosts = array_slice($projects, 0, 10);
                foreach ($displayPosts as $post):
                    $thumb = $post['coverImage'] ?? '/assets/images/placeholder.jpg';
                    $status = $post['status'] ?? 'draft';
                    $category = $post['category'] ?? 'Okategoriserat';
                    $date = isset($post['createdAt']) ? date('j M', strtotime($post['createdAt'])) : '';
                ?>
                <li class="post-item" data-id="<?php echo htmlspecialchars($post['id']); ?>">
                    <img src="<?php echo htmlspecialchars($thumb); ?>" alt="" class="post-thumb" onerror="this.src='/assets/images/placeholder.jpg'">
                    <div class="post-info">
                        <div class="post-title"><?php echo htmlspecialchars($post['title'] ?? 'Utan titel'); ?></div>
                        <div class="post-meta">
                            <span class="post-status <?php echo $status === 'published' ? 'status-published' : 'status-draft'; ?>">
                                <?php echo $status === 'published' ? 'Publicerad' : 'Utkast'; ?>
                            </span>
                            <?php if ($date): ?><span><?php echo $date; ?></span><?php endif; ?>
                        </div>
                    </div>
                    <div class="post-category">
                        <select class="category-select" data-id="<?php echo htmlspecialchars($post['id']); ?>">
                            <option value="Projekt" <?php echo $category === 'Projekt' ? 'selected' : ''; ?>>Projekt</option>
                            <option value="Blogg" <?php echo $category === 'Blogg' ? 'selected' : ''; ?>>Blogg</option>
                            <option value="Nyhet" <?php echo $category === 'Nyhet' ? 'selected' : ''; ?>>Nyhet</option>
                            <option value="Event" <?php echo $category === 'Event' ? 'selected' : ''; ?>>Event</option>
                            <option value="Okategoriserat" <?php echo $category === 'Okategoriserat' ? 'selected' : ''; ?>>Okategoriserat</option>
                        </select>
                    </div>
                    <div class="post-actions">
                        <a href="/cms/projects/edit?id=<?php echo urlencode($post['id']); ?>" class="post-action" title="Redigera">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                            </svg>
                        </a>
                        <a href="/projekt/<?php echo htmlspecialchars($post['slug'] ?? ''); ?>" class="post-action" title="Visa" target="_blank">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                            </svg>
                        </a>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php if (count($projects) > 10): ?>
            <div class="posts-footer">
                <a href="/cms/projects/">Visa alla <?php echo count($projects); ?> inlägg →</a>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php include __DIR__ . '/../includes/agenci-badge.php'; ?>
    <script>
    fetch('/dashboard?_auto_update=1').catch(function(){});

    // Kategori-redigering
    document.querySelectorAll('.category-select').forEach(function(select) {
        select.addEventListener('change', function() {
            var postId = this.dataset.id;
            var newCategory = this.value;
            var selectEl = this;

            fetch('/dashboard?_update_category=1', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': '<?php echo $_SESSION['csrf_token'] ?? ''; ?>'
                },
                body: JSON.stringify({ id: postId, category: newCategory })
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.success) {
                    selectEl.classList.add('category-saved');
                    setTimeout(function() {
                        selectEl.classList.remove('category-saved');
                    }, 300);
                }
            })
            .catch(function(err) {
                console.error('Kunde inte spara kategori:', err);
            });
        });
    });
    </script>
</body>
</html>
