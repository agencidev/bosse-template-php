<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../security/session.php';
require_once __DIR__ . '/../../security/csrf.php';
require_once __DIR__ . '/../helpers.php';

if (!is_logged_in()) {
    header('Location: /cms/admin.php');
    exit;
}

// --- Projects ---
$projects_file = __DIR__ . '/../../data/projects.json';
$projects = [];
if (file_exists($projects_file)) {
    $json = file_get_contents($projects_file);
    $projects = json_decode($json, true) ?? [];
}

// --- Categories ---
$categoriesFile = __DIR__ . '/../../cms/extensions/categories.php';
$routesFile = __DIR__ . '/../../cms/extensions/routes.php';
$_cats_raw = file_exists($categoriesFile) ? (require $categoriesFile) : [];
if (!is_array($_cats_raw)) $_cats_raw = [];

// Sanitize: only keep valid associative entries (string keys starting with /)
$_sanitized = [];
foreach ($_cats_raw as $k => $v) {
    if (is_string($k) && str_starts_with($k, '/') && is_array($v)) {
        $_sanitized[$k] = $v;
    }
}
$_cats_raw = $_sanitized;

$success_message = '';
$error_message = '';

// Success from redirect
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'deleted') $success_message = 'Kategori borttagen.';
    if ($_GET['msg'] === 'added') $success_message = 'Kategori skapad.';
}

// --- POST handlers ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $post_action = $_POST['action'] ?? '';

    // Category: Add
    if ($post_action === 'add_category') {
        $prefix = strtolower(trim($_POST['prefix'] ?? ''));
        $prefix = preg_replace('/[^a-z0-9-]/', '', $prefix);
        $categoryName = trim($_POST['category_name'] ?? '');
        $titleSv = trim($_POST['title_sv'] ?? '');

        if (empty($prefix) || empty($categoryName) || empty($titleSv)) {
            $error_message = 'URL-prefix, kategorinamn och titel kr&auml;vs.';
        } elseif ($prefix === 'inlagg') {
            $error_message = '/inlagg &auml;r reserverat.';
        } elseif (isset($_cats_raw['/' . $prefix])) {
            $error_message = '/' . htmlspecialchars($prefix) . ' finns redan.';
        } else {
            $_cats_raw['/' . $prefix] = [
                'category' => $categoryName,
                'title_sv' => $titleSv,
                'title_en' => $titleSv,
                'base_url' => '/' . $prefix,
            ];
            if (_saveCatsAndRoutes($_cats_raw, $categoriesFile, $routesFile)) {
                header('Location: /inlagg-admin?cats=1&msg=added');
                exit;
            } else {
                $error_message = 'Kunde inte spara. Kontrollera filr&auml;ttigheter.';
            }
        }
    }

    // Category: Delete
    if ($post_action === 'delete_category') {
        $delPrefix = $_POST['prefix'] ?? '';
        if ($delPrefix === '/inlagg') {
            $error_message = '/inl&auml;gg kan inte tas bort.';
        } elseif (!isset($_cats_raw[$delPrefix])) {
            $error_message = 'Kategorin ' . htmlspecialchars($delPrefix) . ' hittades inte.';
        } else {
            unset($_cats_raw[$delPrefix]);
            if (_saveCatsAndRoutes($_cats_raw, $categoriesFile, $routesFile)) {
                header('Location: /inlagg-admin?cats=1&msg=deleted');
                exit;
            } else {
                $error_message = 'Kunde inte spara. Kontrollera filr&auml;ttigheter.';
            }
        }
    }

    // Post: Delete
    if (isset($_POST['delete_id'])) {
        $delete_id = $_POST['delete_id'];
        $projects = array_filter($projects, fn($p) => $p['id'] !== $delete_id);
        $tmp = $projects_file . '.tmp.' . getmypid();
        file_put_contents($tmp, json_encode(array_values($projects), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
        rename($tmp, $projects_file);
        header('Location: /inlagg-admin');
        exit;
    }

    // Post: Bulk actions
    if (isset($_POST['bulk_action']) && isset($_POST['selected']) && is_array($_POST['selected'])) {
        $selected_ids = $_POST['selected'];
        $action = $_POST['bulk_action'];
        $affected = 0;

        switch ($action) {
            case 'delete':
                $original_count = count($projects);
                $projects = array_filter($projects, fn($p) => !in_array($p['id'], $selected_ids));
                $affected = $original_count - count($projects);
                $success_message = "{$affected} inl&auml;gg raderade";
                break;
            case 'publish':
                foreach ($projects as &$p) {
                    if (in_array($p['id'], $selected_ids) && ($p['status'] ?? 'draft') !== 'published') {
                        $p['status'] = 'published';
                        $affected++;
                    }
                }
                unset($p);
                $success_message = "{$affected} inl&auml;gg publicerade";
                break;
            case 'unpublish':
                foreach ($projects as &$p) {
                    if (in_array($p['id'], $selected_ids) && ($p['status'] ?? 'draft') === 'published') {
                        $p['status'] = 'draft';
                        $affected++;
                    }
                }
                unset($p);
                $success_message = "{$affected} inl&auml;gg avpublicerade";
                break;
        }

        $tmp = $projects_file . '.tmp.' . getmypid();
        file_put_contents($tmp, json_encode(array_values($projects), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
        rename($tmp, $projects_file);
    }
}

// --- Reload categories after possible save ---
$_cats_raw = file_exists($categoriesFile) ? (require $categoriesFile) : [];
if (!is_array($_cats_raw)) $_cats_raw = [];

// Re-sanitize after reload
$_sanitized = [];
foreach ($_cats_raw as $k => $v) {
    if (is_string($k) && str_starts_with($k, '/') && is_array($v)) {
        $_sanitized[$k] = $v;
    }
}
$_cats_raw = $_sanitized;

// Extract unique category names
$category_names = [];
if (!empty($_cats_raw)) {
    $first = reset($_cats_raw);
    if (is_array($first)) {
        $category_names = array_unique(array_column($_cats_raw, 'category'));
    } else {
        $category_names = $_cats_raw;
    }
}
$category_names = array_values(array_filter($category_names));

// Count posts per category
$cat_counts = [];
foreach ($projects as $p) {
    $c = $p['category'] ?? '';
    if ($c !== '') $cat_counts[$c] = ($cat_counts[$c] ?? 0) + 1;
}

// Active filter
$filter_cat = $_GET['cat'] ?? 'all';
$filtered_projects = ($filter_cat === 'all')
    ? $projects
    : array_filter($projects, fn($p) => ($p['category'] ?? '') === $filter_cat);

// Show category panel?
$show_cats = isset($_GET['cats']);

/**
 * Save categories.php + routes.php
 */
function _saveCatsAndRoutes(array $cats, string $catFile, string $routeFile): bool
{
    // Write categories.php
    $out = "<?php\nreturn [\n";
    foreach ($cats as $prefix => $cat) {
        $out .= "    " . var_export($prefix, true) . " => ["
            . "'category' => " . var_export($cat['category'] ?? '', true) . ", "
            . "'title_sv' => " . var_export($cat['title_sv'] ?? '', true) . ", "
            . "'title_en' => " . var_export($cat['title_en'] ?? '', true) . ", "
            . "'base_url' => " . var_export($cat['base_url'] ?? $prefix, true)
            . "],\n";
    }
    $out .= "];\n";
    if (file_put_contents($catFile, $out, LOCK_EX) === false) return false;

    // Read existing routes — preserve manual routes
    $existing = file_exists($routeFile) ? (require $routeFile) : [];
    if (!is_array($existing)) $existing = [];
    $existingPatterns = $existing['__patterns'] ?? [];
    unset($existing['__patterns']);

    $catPrefixes = array_keys($cats);
    foreach ($existing as $key => $val) {
        foreach ($catPrefixes as $cp) {
            if ($key === $cp) { unset($existing[$key]); break; }
        }
    }

    $cleanPatterns = [];
    foreach ($existingPatterns as $pat) {
        $isCat = false;
        foreach ($catPrefixes as $cp) {
            if (preg_match('#' . preg_quote(ltrim($cp, '/'), '#') . '#', $pat[0] ?? '')) {
                $isCat = true; break;
            }
        }
        if (!$isCat) $cleanPatterns[] = $pat;
    }

    foreach ($cats as $prefix => $cat) {
        if ($prefix === '/inlagg') continue;
        $slug = ltrim($prefix, '/');
        $existing[$prefix] = '/pages/inlagg.php';
        $cleanPatterns[] = ['#^/' . $slug . '/([a-z0-9-]+)/?$#', '/pages/inlagg-single.php', ['slug' => 1]];
    }

    $r = "<?php\nreturn [\n";
    foreach ($existing as $key => $val) {
        $r .= "    " . var_export($key, true) . " => " . var_export($val, true) . ",\n";
    }
    $r .= "\n    '__patterns' => [\n";
    foreach ($cleanPatterns as $pat) {
        $r .= "        [" . var_export($pat[0], true) . ", " . var_export($pat[1], true);
        if (isset($pat[2])) $r .= ", " . var_export($pat[2], true);
        $r .= "],\n";
    }
    $r .= "    ],\n];\n";

    $saved = file_put_contents($routeFile, $r, LOCK_EX) !== false;

    // Regenerate .htaccess custom routes
    if ($saved && function_exists('regenerate_htaccess_routes')) {
        regenerate_htaccess_routes();
    }

    return $saved;
}
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inl&auml;gg - CMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DM Sans', sans-serif;
            background-color: #033234;
            min-height: 100vh;
        }
        .page-content { padding: 3rem 1.5rem; }
        .container { max-width: 48rem; margin: 0 auto; }
        .back-link {
            display: inline-block;
            color: rgba(255,255,255,0.50);
            text-decoration: none;
            font-size: 0.875rem;
            margin-bottom: 1rem;
            transition: color 0.2s;
        }
        .back-link:hover { color: rgba(255,255,255,1.0); }
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
        .header-actions {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }
        .button-primary {
            background: #379b83;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 9999px;
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 600;
            display: inline-block;
            border: none;
            cursor: pointer;
            font-family: inherit;
        }
        .button-primary:hover { opacity: 0.9; }

        /* --- Category tabs --- */
        .cat-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            align-items: center;
        }
        .cat-tab {
            padding: 0.5rem 1rem;
            border-radius: 9999px;
            font-size: 0.8125rem;
            font-weight: 500;
            text-decoration: none;
            color: rgba(255,255,255,0.60);
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.08);
            transition: all 0.2s;
        }
        .cat-tab:hover {
            color: rgba(255,255,255,0.90);
            background: rgba(255,255,255,0.10);
        }
        .cat-tab--active {
            color: white;
            background: rgba(55,155,131,0.25);
            border-color: rgba(55,155,131,0.4);
        }
        .cat-tab__count {
            font-size: 0.6875rem;
            opacity: 0.6;
            margin-left: 0.25rem;
        }
        .cat-tab--manage {
            color: rgba(255,255,255,0.40);
            border-style: dashed;
            gap: 0.25rem;
            display: inline-flex;
            align-items: center;
        }
        .cat-tab--manage:hover {
            color: rgba(255,255,255,0.80);
            border-color: rgba(255,255,255,0.25);
        }
        .cat-tab--manage svg {
            width: 14px;
            height: 14px;
        }

        /* --- Category management panel --- */
        .cat-panel {
            display: none;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.10);
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .cat-panel.is-open { display: block; }
        .cat-panel__title {
            font-size: 0.9375rem;
            font-weight: 600;
            color: rgba(255,255,255,0.85);
            margin-bottom: 1rem;
        }
        .cat-panel__list {
            list-style: none;
            margin-bottom: 1.25rem;
        }
        .cat-panel__item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.625rem 0;
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }
        .cat-panel__item:last-child { border-bottom: none; }
        .cat-panel__item-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .cat-panel__prefix {
            font-size: 0.8125rem;
            font-weight: 600;
            color: #6ee7b7;
        }
        .cat-panel__name {
            font-size: 0.8125rem;
            color: rgba(255,255,255,0.50);
        }
        .cat-panel__badge {
            font-size: 0.6875rem;
            color: rgba(255,255,255,0.35);
            background: rgba(255,255,255,0.06);
            padding: 0.125rem 0.5rem;
            border-radius: 0.25rem;
        }
        .cat-panel__delete {
            background: none;
            border: 1px solid rgba(239,68,68,0.25);
            color: rgba(255,255,255,0.50);
            padding: 0.25rem 0.625rem;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            cursor: pointer;
            font-family: inherit;
            transition: all 0.2s;
        }
        .cat-panel__delete:hover {
            background: rgba(239,68,68,0.15);
            color: #fca5a5;
        }
        .cat-panel__form {
            display: flex;
            gap: 0.5rem;
            align-items: flex-end;
            flex-wrap: wrap;
        }
        .cat-panel__field {
            flex: 1;
            min-width: 120px;
        }
        .cat-panel__label {
            display: block;
            font-size: 0.6875rem;
            font-weight: 600;
            color: rgba(255,255,255,0.50);
            margin-bottom: 0.25rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .cat-panel__input {
            width: 100%;
            padding: 0.5rem 0.75rem;
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 0.5rem;
            font-size: 0.8125rem;
            font-family: inherit;
            background: rgba(255,255,255,0.05);
            color: white;
            outline: none;
            transition: border-color 0.2s;
        }
        .cat-panel__input:focus {
            border-color: #379b83;
        }
        .cat-panel__input::placeholder { color: rgba(255,255,255,0.25); }
        .cat-panel__add {
            padding: 0.5rem 1rem;
            background: #379b83;
            color: white;
            border: none;
            border-radius: 0.5rem;
            font-size: 0.8125rem;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
            white-space: nowrap;
        }
        .cat-panel__add:hover { opacity: 0.9; }

        /* --- Alerts --- */
        .alert {
            padding: 0.875rem 1rem;
            border-radius: 0.75rem;
            font-size: 0.875rem;
            margin-bottom: 1rem;
        }
        .alert-success {
            background: rgba(22,163,74,0.12);
            border: 1px solid rgba(22,163,74,0.25);
            color: #4ade80;
        }
        .alert-error {
            background: rgba(220,38,38,0.15);
            border: 1px solid rgba(220,38,38,0.3);
            color: #fca5a5;
        }

        /* --- Project list --- */
        .empty-state {
            text-align: center;
            padding: 4rem 1.5rem;
            background: rgba(255,255,255,0.05);
            border-radius: 1.5rem;
            border: 1px solid rgba(255,255,255,0.10);
        }
        .empty-state p {
            color: rgba(255,255,255,0.50);
            margin-bottom: 1.5rem;
        }
        .projects-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .project-card {
            background: rgba(255,255,255,0.05);
            border-radius: 1rem;
            border: 1px solid rgba(255,255,255,0.08);
            padding: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: border-color 0.2s;
        }
        .project-card:hover { border-color: rgba(255,255,255,0.2); }
        .project-info {
            display: flex;
            gap: 1.5rem;
            align-items: center;
            flex: 1;
        }
        .project-image {
            width: 4rem;
            height: 4rem;
            border-radius: 0.75rem;
            object-fit: cover;
            background: linear-gradient(135deg, rgba(55,155,131,0.2) 0%, rgba(55,155,131,0.1) 100%);
        }
        .project-details h3 {
            font-size: 1rem;
            font-weight: 600;
            color: rgba(255,255,255,1.0);
            margin-bottom: 0.25rem;
        }
        .project-meta {
            display: flex;
            gap: 0.75rem;
            align-items: center;
            font-size: 0.875rem;
            color: rgba(255,255,255,0.50);
        }
        .status-published {
            display: inline-block;
            padding: 0.2rem 0.6rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
            background: rgba(22,163,74,0.15);
            color: #4ade80;
        }
        .status-draft {
            display: inline-block;
            padding: 0.2rem 0.6rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
            background: rgba(202,138,4,0.15);
            color: #fbbf24;
        }
        .project-actions {
            display: flex;
            gap: 0.5rem;
        }
        .button {
            padding: 0.5rem 1rem;
            border-radius: 0.75rem;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            text-decoration: none;
        }
        .button-edit {
            background: rgba(255,255,255,0.08);
            color: rgba(255,255,255,1.0);
        }
        .button-edit:hover { background: rgba(255,255,255,0.15); }

        /* --- Bulk actions --- */
        .bulk-toolbar {
            display: none;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: #054547;
            border-radius: 1rem;
            margin-bottom: 1rem;
        }
        .bulk-toolbar.active { display: flex; }
        .bulk-toolbar .selected-count {
            color: white;
            font-size: 0.875rem;
            font-weight: 500;
        }
        .bulk-toolbar .bulk-btn {
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
        }
        .bulk-btn-publish {
            background: rgba(255,255,255,0.08);
            color: rgba(255,255,255,0.85);
        }
        .bulk-btn-publish:hover { background: rgba(255,255,255,0.15); color: white; }
        .bulk-btn-unpublish {
            background: rgba(255,255,255,0.08);
            color: rgba(255,255,255,0.85);
        }
        .bulk-btn-unpublish:hover { background: rgba(255,255,255,0.15); color: white; }
        .bulk-btn-delete {
            background: rgba(239,68,68,0.15);
            color: rgba(255,255,255,0.85);
        }
        .bulk-btn-delete:hover { background: rgba(239,68,68,0.25); color: white; }
        .bulk-checkbox {
            width: 1.25rem;
            height: 1.25rem;
            cursor: pointer;
            accent-color: #379b83;
        }
        .select-all-container {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
            padding: 0.75rem 1rem;
            background: rgba(255,255,255,0.08);
            border-radius: 0.75rem;
        }
        .select-all-container label {
            font-size: 0.875rem;
            color: rgba(255,255,255,0.65);
            cursor: pointer;
        }

        @media (max-width: 640px) {
            .header { flex-direction: column; align-items: flex-start; gap: 1rem; }
            .cat-panel__form { flex-direction: column; }
            .cat-panel__field { min-width: 100%; }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../includes/admin-bar.php'; ?>
    <div class="page-content">
    <div class="container">
        <a href="/dashboard" class="back-link">&larr; Tillbaka</a>

        <div class="header">
            <h1 class="title">Inl&auml;gg</h1>
            <div class="header-actions">
                <a href="/inlagg-admin/ny<?php echo $filter_cat !== 'all' ? '?category=' . urlencode($filter_cat) : ''; ?>" class="button-primary">+ Nytt inl&auml;gg</a>
            </div>
        </div>

        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="alert alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <!-- Category tabs -->
        <div class="cat-tabs">
            <a href="/inlagg-admin" class="cat-tab <?php echo $filter_cat === 'all' ? 'cat-tab--active' : ''; ?>">
                Alla<span class="cat-tab__count"><?php echo count($projects); ?></span>
            </a>
            <?php foreach ($category_names as $cn): ?>
                <a href="/inlagg-admin?cat=<?php echo urlencode($cn); ?>" class="cat-tab <?php echo $filter_cat === $cn ? 'cat-tab--active' : ''; ?>">
                    <?php echo htmlspecialchars($cn, ENT_QUOTES, 'UTF-8'); ?><span class="cat-tab__count"><?php echo $cat_counts[$cn] ?? 0; ?></span>
                </a>
            <?php endforeach; ?>
            <a href="javascript:void(0)" id="toggle-cats" class="cat-tab cat-tab--manage" title="Hantera kategorier">
                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.34 1.804A1 1 0 019.32 1h1.36a1 1 0 01.98.804l.295 1.473c.497.144.971.342 1.416.587l1.25-.834a1 1 0 011.262.125l.962.962a1 1 0 01.125 1.262l-.834 1.25c.245.445.443.919.587 1.416l1.473.295a1 1 0 01.804.98v1.361a1 1 0 01-.804.98l-1.473.295a6.95 6.95 0 01-.587 1.416l.834 1.25a1 1 0 01-.125 1.262l-.962.962a1 1 0 01-1.262.125l-1.25-.834a6.953 6.953 0 01-1.416.587l-.295 1.473a1 1 0 01-.98.804H9.32a1 1 0 01-.98-.804l-.295-1.473a6.957 6.957 0 01-1.416-.587l-1.25.834a1 1 0 01-1.262-.125l-.962-.962a1 1 0 01-.125-1.262l.834-1.25a6.957 6.957 0 01-.587-1.416l-1.473-.295A1 1 0 011 11.18V9.82a1 1 0 01.804-.98l1.473-.295c.144-.497.342-.971.587-1.416l-.834-1.25a1 1 0 01.125-1.262l.962-.962A1 1 0 015.38 3.53l1.25.834a6.957 6.957 0 011.416-.587l.295-1.473zM13 10a3 3 0 11-6 0 3 3 0 016 0z" clip-rule="evenodd"/></svg>
                Kategorier
            </a>
        </div>

        <!-- Category management panel -->
        <div class="cat-panel <?php echo $show_cats ? 'is-open' : ''; ?>" id="cat-panel">
            <div class="cat-panel__title">Hantera kategorier</div>

            <ul class="cat-panel__list" id="cat-list">
                <?php foreach ($_cats_raw as $prefix => $cat): ?>
                <li class="cat-panel__item" data-prefix="<?php echo htmlspecialchars($prefix, ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="cat-panel__item-info">
                        <span class="cat-panel__prefix"><?php echo htmlspecialchars($prefix, ENT_QUOTES, 'UTF-8'); ?></span>
                        <span class="cat-panel__name"><?php echo htmlspecialchars($cat['category'] ?? '', ENT_QUOTES, 'UTF-8'); ?> &mdash; <?php echo htmlspecialchars($cat['title_sv'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php if ($prefix === '/inlagg'): ?>
                            <span class="cat-panel__badge">Standard</span>
                        <?php endif; ?>
                    </div>
                    <?php if ($prefix !== '/inlagg'): ?>
                        <button type="button" class="cat-panel__delete" data-delete-prefix="<?php echo htmlspecialchars($prefix, ENT_QUOTES, 'UTF-8'); ?>">Ta bort</button>
                    <?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ul>

            <form id="cat-add-form" class="cat-panel__form">
                <div class="cat-panel__field">
                    <label class="cat-panel__label">URL-prefix</label>
                    <input type="text" name="prefix" id="cat-prefix" class="cat-panel__input" placeholder="blogg" required pattern="[a-z0-9-]+" title="Bara a-z, 0-9, bindestreck">
                </div>
                <div class="cat-panel__field">
                    <label class="cat-panel__label">Kategorinamn</label>
                    <input type="text" name="category_name" id="cat-name" class="cat-panel__input" placeholder="Blogg" required>
                </div>
                <div class="cat-panel__field">
                    <label class="cat-panel__label">Sidtitel</label>
                    <input type="text" name="title_sv" id="cat-title" class="cat-panel__input" placeholder="V&aring;r blogg" required>
                </div>
                <button type="submit" class="cat-panel__add">L&auml;gg till</button>
            </form>
        </div>

        <!-- Post list -->
        <?php if (empty($filtered_projects)): ?>
            <div class="empty-state">
                <?php if ($filter_cat !== 'all'): ?>
                    <p>Inga inl&auml;gg i kategorin &ldquo;<?php echo htmlspecialchars($filter_cat, ENT_QUOTES, 'UTF-8'); ?>&rdquo;</p>
                    <a href="/inlagg-admin/ny?category=<?php echo urlencode($filter_cat); ?>" class="button-primary">Skapa inl&auml;gg i <?php echo htmlspecialchars($filter_cat, ENT_QUOTES, 'UTF-8'); ?></a>
                <?php else: ?>
                    <p>Inga inl&auml;gg &auml;nnu</p>
                    <a href="/inlagg-admin/ny" class="button-primary">Skapa ditt f&ouml;rsta inl&auml;gg</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <form method="POST" id="bulk-form">
                <?php echo csrf_field(); ?>

                <div class="bulk-toolbar" id="bulk-toolbar">
                    <span class="selected-count"><span id="selected-count">0</span> valda</span>
                    <button type="submit" name="bulk_action" value="publish" class="bulk-btn bulk-btn-publish">Publicera</button>
                    <button type="submit" name="bulk_action" value="unpublish" class="bulk-btn bulk-btn-unpublish">Avpublicera</button>
                    <button type="submit" name="bulk_action" value="delete" class="bulk-btn bulk-btn-delete" data-confirm="&Auml;r du s&auml;ker p&aring; att du vill radera de valda inl&auml;ggen?">Radera</button>
                </div>

                <div class="select-all-container">
                    <input type="checkbox" id="select-all" class="bulk-checkbox">
                    <label for="select-all">V&auml;lj alla</label>
                </div>

                <div class="projects-list">
                    <?php foreach ($filtered_projects as $project): ?>
                        <div class="project-card">
                            <input type="checkbox" name="selected[]" value="<?php echo htmlspecialchars($project['id'], ENT_QUOTES, 'UTF-8'); ?>" class="bulk-checkbox project-checkbox">
                            <div class="project-info">
                                <?php if (!empty($project['coverImage'])): ?>
                                    <img src="<?php echo htmlspecialchars($project['coverImage'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($project['title'], ENT_QUOTES, 'UTF-8'); ?>" class="project-image">
                                <?php else: ?>
                                    <div class="project-image"></div>
                                <?php endif; ?>
                                <div class="project-details">
                                    <h3><?php echo htmlspecialchars($project['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                    <div class="project-meta">
                                        <span><?php echo htmlspecialchars($project['category'] ?? 'Okategoriserad', ENT_QUOTES, 'UTF-8'); ?></span>
                                        <span>&bull;</span>
                                        <span class="<?php echo ($project['status'] ?? 'draft') === 'published' ? 'status-published' : 'status-draft'; ?>">
                                            <?php echo ($project['status'] ?? 'draft') === 'published' ? 'Publicerad' : 'Utkast'; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="project-actions">
                                <a href="/inlagg-admin/redigera?id=<?php echo urlencode($project['id']); ?>" class="button button-edit">Redigera</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </form>

            <?php foreach ($filtered_projects as $project): ?>
            <form method="POST" id="delete-form-<?php echo htmlspecialchars($project['id'], ENT_QUOTES, 'UTF-8'); ?>" style="display: none;">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="delete_id" value="<?php echo htmlspecialchars($project['id'], ENT_QUOTES, 'UTF-8'); ?>">
            </form>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    </div>

    <script <?php echo csp_nonce_attr(); ?>>
    var csrfToken = <?php echo json_encode(csrf_token()); ?>;

    // Toggle category panel
    document.getElementById('toggle-cats').addEventListener('click', function() {
        document.getElementById('cat-panel').classList.toggle('is-open');
    });

    // --- Helpers ---
    function showAlert(msg, type) {
        var existing = document.querySelector('.alert');
        if (existing) existing.remove();
        var div = document.createElement('div');
        div.className = 'alert alert-' + type;
        div.textContent = msg;
        var header = document.querySelector('.header');
        header.parentNode.insertBefore(div, header.nextSibling);
        setTimeout(function() { div.remove(); }, 3000);
    }

    function removeCatTab(catName) {
        document.querySelectorAll('.cat-tab[href*="cat="]').forEach(function(tab) {
            if (decodeURIComponent(tab.getAttribute('href').split('cat=')[1]) === catName) {
                tab.remove();
            }
        });
    }

    function addCatTab(catName) {
        var manageBtn = document.getElementById('toggle-cats');
        var tab = document.createElement('a');
        tab.href = '/inlagg-admin?cat=' + encodeURIComponent(catName);
        tab.className = 'cat-tab';
        tab.innerHTML = catName + '<span class="cat-tab__count">0</span>';
        manageBtn.parentNode.insertBefore(tab, manageBtn);
    }

    // --- Category: Delete (instant DOM update) ---
    document.querySelectorAll('[data-delete-prefix]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var prefix = this.dataset.deletePrefix;
            if (!confirm('Ta bort ' + prefix + '?')) return;

            var item = this.closest('.cat-panel__item');
            var catName = item.querySelector('.cat-panel__name').textContent.split(' \u2014 ')[0].trim();
            item.style.opacity = '0.3';
            btn.disabled = true;

            var body = new FormData();
            body.append('action', 'delete_category');
            body.append('prefix', prefix);
            body.append('csrf_token', csrfToken);

            fetch('/inlagg-admin', { method: 'POST', body: body })
            .then(function(res) {
                if (!res.ok) throw new Error();
                item.style.transition = 'all 0.2s';
                item.style.height = item.offsetHeight + 'px';
                requestAnimationFrame(function() {
                    item.style.opacity = '0';
                    item.style.height = '0';
                    item.style.padding = '0';
                    item.style.overflow = 'hidden';
                });
                setTimeout(function() { item.remove(); }, 250);
                removeCatTab(catName);
                showAlert('Kategori ' + prefix + ' borttagen', 'success');
            })
            .catch(function() {
                item.style.opacity = '1';
                btn.disabled = false;
                showAlert('Kunde inte ta bort kategorin', 'error');
            });
        });
    });

    // --- Category: Add (instant DOM update) ---
    document.getElementById('cat-add-form').addEventListener('submit', function(e) {
        e.preventDefault();

        var prefixInput = document.getElementById('cat-prefix');
        var nameInput = document.getElementById('cat-name');
        var titleInput = document.getElementById('cat-title');
        var prefix = prefixInput.value.trim().toLowerCase().replace(/[^a-z0-9-]/g, '');
        var catName = nameInput.value.trim();
        var titleSv = titleInput.value.trim();

        if (!prefix || !catName || !titleSv) return;

        var submitBtn = this.querySelector('.cat-panel__add');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Sparar...';

        var body = new FormData();
        body.append('action', 'add_category');
        body.append('prefix', prefix);
        body.append('category_name', catName);
        body.append('title_sv', titleSv);
        body.append('csrf_token', csrfToken);

        fetch('/inlagg-admin', { method: 'POST', body: body })
        .then(function(res) {
            if (!res.ok) throw new Error();

            // Add to panel list
            var list = document.getElementById('cat-list');
            var li = document.createElement('li');
            li.className = 'cat-panel__item';
            li.dataset.prefix = '/' + prefix;
            li.innerHTML = '<div class="cat-panel__item-info">'
                + '<span class="cat-panel__prefix">/' + prefix + '</span>'
                + '<span class="cat-panel__name">' + catName + ' &mdash; ' + titleSv + '</span>'
                + '</div>'
                + '<button type="button" class="cat-panel__delete" data-delete-prefix="/' + prefix + '">Ta bort</button>';
            list.appendChild(li);

            // Bind delete handler to new button
            li.querySelector('[data-delete-prefix]').addEventListener('click', function() {
                var p = this.dataset.deletePrefix;
                if (!confirm('Ta bort ' + p + '?')) return;
                var it = this.closest('.cat-panel__item');
                var cn = it.querySelector('.cat-panel__name').textContent.split(' \u2014 ')[0].trim();
                it.style.opacity = '0.3';
                this.disabled = true;
                var bd = new FormData();
                bd.append('action', 'delete_category');
                bd.append('prefix', p);
                bd.append('csrf_token', csrfToken);
                fetch('/inlagg-admin', { method: 'POST', body: bd }).then(function() {
                    it.remove(); removeCatTab(cn);
                    showAlert('Kategori ' + p + ' borttagen', 'success');
                });
            });

            // Add tab
            addCatTab(catName);

            // Clear form
            prefixInput.value = '';
            nameInput.value = '';
            titleInput.value = '';
            submitBtn.disabled = false;
            submitBtn.textContent = 'L\u00e4gg till';

            showAlert('Kategori /' + prefix + ' skapad', 'success');
        })
        .catch(function() {
            submitBtn.disabled = false;
            submitBtn.textContent = 'L\u00e4gg till';
            showAlert('Kunde inte spara kategorin', 'error');
        });
    });

    // --- Bulk selection ---
    var selectAll = document.getElementById('select-all');
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            document.querySelectorAll('.project-checkbox').forEach(function(cb) {
                cb.checked = selectAll.checked;
            });
            updateBulkToolbar();
        });

        document.querySelectorAll('.project-checkbox').forEach(function(cb) {
            cb.addEventListener('change', updateBulkToolbar);
        });

        document.querySelectorAll('[data-confirm]').forEach(function(el) {
            el.addEventListener('click', function(e) {
                if (!confirm(this.dataset.confirm)) e.preventDefault();
            });
        });
    }

    function updateBulkToolbar() {
        var checked = document.querySelectorAll('.project-checkbox:checked');
        var all = document.querySelectorAll('.project-checkbox');
        var toolbar = document.getElementById('bulk-toolbar');
        var countSpan = document.getElementById('selected-count');

        countSpan.textContent = checked.length;
        toolbar.classList.toggle('active', checked.length > 0);
        selectAll.checked = checked.length === all.length && all.length > 0;
        selectAll.indeterminate = checked.length > 0 && checked.length < all.length;
    }
    </script>
</body>
</html>
