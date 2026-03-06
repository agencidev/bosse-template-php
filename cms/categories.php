<?php
/**
 * CMS Category Pages — Manage category listing pages (e.g. /blogg, /nyheter)
 * CORE-fil — skrivs över vid uppdatering
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../security/session.php';
require_once __DIR__ . '/../security/csrf.php';
require_once __DIR__ . '/helpers.php';

if (!is_logged_in()) {
    header('Location: /admin');
    exit;
}

$categoriesFile = __DIR__ . '/extensions/categories.php';
$routesFile = __DIR__ . '/extensions/routes.php';

// Load current categories
$categories = file_exists($categoriesFile) ? (require $categoriesFile) : [];
if (!is_array($categories)) $categories = [];

$error = '';
$success = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $prefix = trim($_POST['prefix'] ?? '');
        $categoryName = trim($_POST['category_name'] ?? '');
        $titleSv = trim($_POST['title_sv'] ?? '');
        $titleEn = trim($_POST['title_en'] ?? '');

        // Validate
        if (empty($prefix) || empty($categoryName) || empty($titleSv)) {
            $error = 'URL-prefix, kategorinamn och svensk titel krävs.';
        } else {
            // Sanitize prefix: lowercase, no slashes, only a-z0-9-
            $prefix = strtolower($prefix);
            $prefix = preg_replace('/[^a-z0-9-]/', '', $prefix);
            if (empty($prefix)) {
                $error = 'Ogiltigt URL-prefix. Använd bara a-z, 0-9 och bindestreck.';
            } elseif (isset($categories['/' . $prefix])) {
                $error = 'URL-prefixet /' . htmlspecialchars($prefix) . ' finns redan.';
            } elseif ($prefix === 'inlagg') {
                $error = '/inlagg är reserverat som standardkategori och kan inte läggas till här.';
            } else {
                $categories['/' . $prefix] = [
                    'category' => $categoryName,
                    'title_sv' => $titleSv,
                    'title_en' => $titleEn ?: $titleSv,
                    'base_url' => '/' . $prefix,
                ];

                if (saveCategories($categories, $categoriesFile, $routesFile)) {
                    $success = 'Kategorisida /' . htmlspecialchars($prefix) . ' skapad.';
                } else {
                    $error = 'Kunde inte spara. Kontrollera filrättigheter.';
                }
            }
        }
    } elseif ($action === 'delete') {
        $deletePrefix = $_POST['prefix'] ?? '';
        if ($deletePrefix === '/inlagg') {
            $error = 'Standardkategorin /inlagg kan inte tas bort.';
        } elseif (isset($categories[$deletePrefix])) {
            unset($categories[$deletePrefix]);
            if (saveCategories($categories, $categoriesFile, $routesFile)) {
                $success = 'Kategorisida ' . htmlspecialchars($deletePrefix) . ' borttagen.';
            } else {
                $error = 'Kunde inte spara. Kontrollera filrättigheter.';
            }
        }
    }
}

// Reload after save
$categories = file_exists($categoriesFile) ? (require $categoriesFile) : [];
if (!is_array($categories)) $categories = [];

/**
 * Save categories.php and update routes.php
 */
function saveCategories(array $cats, string $catFile, string $routeFile): bool
{
    // 1. Write categories.php
    $catContent = "<?php\n";
    $catContent .= "/**\n";
    $catContent .= " * Category configuration (SAFE — survives updates)\n";
    $catContent .= " *\n";
    $catContent .= " * Each key is a URL prefix. The PHP views use this to determine\n";
    $catContent .= " * which category to filter by and what labels to show.\n";
    $catContent .= " *\n";
    $catContent .= " * Managed via CMS admin (/categories). Manual edits are also OK.\n";
    $catContent .= " */\n";
    $catContent .= "return [\n";
    foreach ($cats as $prefix => $cat) {
        $catContent .= "    " . var_export($prefix, true) . " => ["
            . "'category' => " . var_export($cat['category'] ?? '', true) . ", "
            . "'title_sv' => " . var_export($cat['title_sv'] ?? '', true) . ", "
            . "'title_en' => " . var_export($cat['title_en'] ?? '', true) . ", "
            . "'base_url' => " . var_export($cat['base_url'] ?? $prefix, true)
            . "],\n";
    }
    $catContent .= "];\n";

    if (file_put_contents($catFile, $catContent, LOCK_EX) === false) {
        return false;
    }

    // 2. Read existing routes.php — preserve manual/custom routes
    $existingRoutes = file_exists($routeFile) ? (require $routeFile) : [];
    if (!is_array($existingRoutes)) $existingRoutes = [];
    $existingPatterns = $existingRoutes['__patterns'] ?? [];
    unset($existingRoutes['__patterns']);

    // Remove old category-based routes (keys matching any category prefix except /inlagg)
    $catPrefixes = array_keys($cats);
    foreach ($existingRoutes as $key => $val) {
        foreach ($catPrefixes as $cp) {
            if ($key === $cp) {
                unset($existingRoutes[$key]);
                break;
            }
        }
    }

    // Remove old category-based patterns
    $cleanPatterns = [];
    foreach ($existingPatterns as $pat) {
        $isCategoryPattern = false;
        foreach ($catPrefixes as $cp) {
            $escaped = preg_quote(ltrim($cp, '/'), '#');
            if (preg_match('#' . $escaped . '#', $pat[0] ?? '')) {
                $isCategoryPattern = true;
                break;
            }
        }
        if (!$isCategoryPattern) {
            $cleanPatterns[] = $pat;
        }
    }

    // Add new category routes (everything except /inlagg, which is in .htaccess)
    foreach ($cats as $prefix => $cat) {
        if ($prefix === '/inlagg') continue;
        $slug = ltrim($prefix, '/');
        $existingRoutes[$prefix] = '/pages/inlagg.php';
        $cleanPatterns[] = ['#^/' . $slug . '/([a-z0-9-]+)/?$#', '/pages/inlagg-single.php', ['slug' => 1]];
    }

    // 3. Write routes.php
    $routeContent = "<?php\n";
    $routeContent .= "/**\n";
    $routeContent .= " * Custom routes (SAFE — survives updates)\n";
    $routeContent .= " * Static routes: '/path' => '/pages/file.php'\n";
    $routeContent .= " * Dynamic patterns: '__patterns' => [regex, target, params]\n";
    $routeContent .= " *\n";
    $routeContent .= " * Category-routes are auto-generated by CMS admin (/categories).\n";
    $routeContent .= " */\n";
    $routeContent .= "return [\n";

    foreach ($existingRoutes as $key => $val) {
        $routeContent .= "    " . var_export($key, true) . " => " . var_export($val, true) . ",\n";
    }

    $routeContent .= "\n    '__patterns' => [\n";
    foreach ($cleanPatterns as $pat) {
        $routeContent .= "        [" . var_export($pat[0], true) . ", " . var_export($pat[1], true);
        if (isset($pat[2])) {
            $routeContent .= ", " . var_export($pat[2], true);
        }
        $routeContent .= "],\n";
    }
    $routeContent .= "    ],\n";
    $routeContent .= "];\n";

    $saved = file_put_contents($routeFile, $routeContent, LOCK_EX) !== false;

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
    <title>Kategorisidor - CMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DM Sans', sans-serif;
            background-color: #033234;
            min-height: 100vh;
            color: white;
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
        .title {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        .subtitle {
            font-size: 0.9375rem;
            color: rgba(255,255,255,0.50);
            margin-bottom: 2rem;
        }
        .card {
            background: rgba(255,255,255,0.05);
            border-radius: 1.5rem;
            border: 1px solid rgba(255,255,255,0.10);
            padding: 2rem;
            margin-bottom: 1.5rem;
        }
        .card-title {
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
        }
        .alert {
            padding: 0.875rem 1rem;
            border-radius: 0.75rem;
            font-size: 0.875rem;
            margin-bottom: 1.5rem;
        }
        .alert-error {
            background: rgba(220,38,38,0.15);
            border: 1px solid rgba(220,38,38,0.3);
            color: #fca5a5;
        }
        .alert-success {
            background: rgba(55,155,131,0.15);
            border: 1px solid rgba(55,155,131,0.3);
            color: #6ee7b7;
        }
        .cat-list { list-style: none; }
        .cat-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 0;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }
        .cat-item:last-child { border-bottom: none; }
        .cat-info { flex: 1; }
        .cat-prefix {
            font-weight: 600;
            font-size: 1rem;
            color: #6ee7b7;
        }
        .cat-meta {
            font-size: 0.8125rem;
            color: rgba(255,255,255,0.50);
            margin-top: 0.25rem;
        }
        .cat-badge {
            display: inline-block;
            padding: 0.2rem 0.6rem;
            background: rgba(255,255,255,0.08);
            border-radius: 0.375rem;
            font-size: 0.75rem;
            color: rgba(255,255,255,0.50);
            margin-left: 0.5rem;
        }
        .btn-delete {
            background: none;
            border: 1px solid rgba(220,38,38,0.3);
            color: #fca5a5;
            padding: 0.4rem 0.75rem;
            border-radius: 0.5rem;
            font-size: 0.8125rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-delete:hover {
            background: rgba(220,38,38,0.15);
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        .form-group { margin-bottom: 1rem; }
        .form-label {
            display: block;
            font-size: 0.8125rem;
            font-weight: 600;
            color: rgba(255,255,255,0.85);
            margin-bottom: 0.375rem;
        }
        .form-hint {
            font-size: 0.75rem;
            color: rgba(255,255,255,0.40);
            margin-top: 0.25rem;
        }
        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 0.75rem;
            font-size: 0.9375rem;
            font-family: inherit;
            background: rgba(255,255,255,0.05);
            color: white;
            outline: none;
            transition: all 0.2s;
        }
        .form-input:focus {
            border-color: #379b83;
            box-shadow: 0 0 0 3px rgba(55, 155, 131, 0.2);
        }
        .form-input::placeholder { color: rgba(255,255,255,0.30); }
        .btn-submit {
            padding: 0.75rem 1.5rem;
            background: #379b83;
            color: white;
            border: none;
            border-radius: 9999px;
            font-size: 0.9375rem;
            font-weight: 600;
            cursor: pointer;
            transition: opacity 0.2s;
        }
        .btn-submit:hover { opacity: 0.9; }
        @media (max-width: 640px) {
            .form-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/admin-bar.php'; ?>
    <div class="page-content">
    <div class="container">
        <a href="/dashboard" class="back-link">&larr; Tillbaka till dashboard</a>

        <h1 class="title">Kategorisidor</h1>
        <p class="subtitle">Hantera listvyer som /blogg, /nyheter, /event. Varje kategorisida filtrerar inlägg efter kategori.</p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <!-- Existing categories -->
        <div class="card">
            <h2 class="card-title">Aktiva kategorisidor</h2>
            <?php if (empty($categories)): ?>
                <p style="color: rgba(255,255,255,0.50); font-size: 0.875rem;">Inga kategorisidor konfigurerade.</p>
            <?php else: ?>
                <ul class="cat-list">
                    <?php foreach ($categories as $prefix => $cat): ?>
                    <li class="cat-item">
                        <div class="cat-info">
                            <span class="cat-prefix"><?php echo htmlspecialchars($prefix, ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php if ($prefix === '/inlagg'): ?>
                                <span class="cat-badge">Standard</span>
                            <?php endif; ?>
                            <div class="cat-meta">
                                Kategori: <?php echo htmlspecialchars($cat['category'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                &middot; Titel: <?php echo htmlspecialchars($cat['title_sv'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                <?php if (!empty($cat['title_en'])): ?>
                                    / <?php echo htmlspecialchars($cat['title_en'], ENT_QUOTES, 'UTF-8'); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if ($prefix !== '/inlagg'): ?>
                        <form method="POST" style="margin: 0;" onsubmit="return confirm('Ta bort kategorisida <?php echo htmlspecialchars($prefix, ENT_QUOTES, 'UTF-8'); ?>?')">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="prefix" value="<?php echo htmlspecialchars($prefix, ENT_QUOTES, 'UTF-8'); ?>">
                            <button type="submit" class="btn-delete">Ta bort</button>
                        </form>
                        <?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <!-- Add new category -->
        <div class="card">
            <h2 class="card-title">Lägg till kategorisida</h2>
            <form method="POST">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="add">

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="prefix">URL-prefix *</label>
                        <input type="text" id="prefix" name="prefix" class="form-input" placeholder="blogg" required
                               pattern="[a-z0-9-]+" title="Bara små bokstäver, siffror och bindestreck">
                        <p class="form-hint">Blir URL: /blogg, /nyheter, etc.</p>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="category_name">Kategorinamn *</label>
                        <input type="text" id="category_name" name="category_name" class="form-input" placeholder="Blogg" required>
                        <p class="form-hint">Visas i CMS-dropdown vid skapande av inlägg</p>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="title_sv">Titel (svenska) *</label>
                        <input type="text" id="title_sv" name="title_sv" class="form-input" placeholder="Blogg" required>
                        <p class="form-hint">Sidrubrik på listvy</p>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="title_en">Titel (engelska)</label>
                        <input type="text" id="title_en" name="title_en" class="form-input" placeholder="Blog">
                        <p class="form-hint">Valfritt — för engelska sajter</p>
                    </div>
                </div>

                <button type="submit" class="btn-submit">Lägg till kategorisida</button>
            </form>
        </div>
    </div>
    </div>
</body>
</html>
