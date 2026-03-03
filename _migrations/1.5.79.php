<?php
/**
 * Migration 1.5.79
 * Rename projekt → inlägg as default category.
 *
 * 1. Rename custom CSS files (if they exist)
 * 2. Update categories.php: /projekt → /inlagg, category "Projekt" → "Inlägg"
 * 3. Update routes.php: pages/projekt*.php → pages/inlagg*.php
 * 4. Update projects.json: category "Projekt" → "Inlägg"
 * 5. Delete old files no longer shipped
 *
 * Idempotent: safe to run multiple times.
 */

$base = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__);

// --- 1. Rename custom CSS files ---
$cssRenames = [
    'assets/css/projekt-custom.css'        => 'assets/css/inlagg-custom.css',
    'assets/css/projekt-single-custom.css'  => 'assets/css/inlagg-single-custom.css',
];
foreach ($cssRenames as $old => $new) {
    $oldPath = $base . '/' . $old;
    $newPath = $base . '/' . $new;
    if (file_exists($oldPath) && !file_exists($newPath)) {
        rename($oldPath, $newPath);
    }
}

// --- 2. Update categories.php ---
$categoriesFile = $base . '/cms/extensions/categories.php';
if (file_exists($categoriesFile)) {
    $cats = require $categoriesFile;
    if (is_array($cats)) {
        $updated = false;
        $newCats = [];

        // Ensure /inlagg exists
        $hasInlagg = isset($cats['/inlagg']);

        foreach ($cats as $prefix => $cat) {
            if ($prefix === '/projekt') {
                // Migrate /projekt → /inlagg
                if (!$hasInlagg) {
                    $newCats['/inlagg'] = [
                        'category' => 'Inlägg',
                        'title_sv' => 'Inlägg',
                        'title_en' => 'Posts',
                        'base_url' => '/inlagg',
                    ];
                    $hasInlagg = true;
                }
                $updated = true;
                // Skip /projekt entry
            } else {
                $newCats[$prefix] = $cat;
            }
        }

        // If no /inlagg was added yet, add it
        if (!$hasInlagg) {
            $newCats = ['/inlagg' => [
                'category' => 'Inlägg',
                'title_sv' => 'Inlägg',
                'title_en' => 'Posts',
                'base_url' => '/inlagg',
            ]] + $newCats;
            $updated = true;
        }

        if ($updated) {
            $content = "<?php\n";
            $content .= "/**\n * Category configuration (SAFE — survives updates)\n *\n";
            $content .= " * Managed via CMS admin (/categories). Manual edits are also OK.\n */\n";
            $content .= "return [\n";
            foreach ($newCats as $prefix => $cat) {
                $content .= "    " . var_export($prefix, true) . " => ["
                    . "'category' => " . var_export($cat['category'] ?? '', true) . ", "
                    . "'title_sv' => " . var_export($cat['title_sv'] ?? '', true) . ", "
                    . "'title_en' => " . var_export($cat['title_en'] ?? '', true) . ", "
                    . "'base_url' => " . var_export($cat['base_url'] ?? $prefix, true)
                    . "],\n";
            }
            $content .= "];\n";
            file_put_contents($categoriesFile, $content, LOCK_EX);
        }
    }
}

// --- 3. Update routes.php ---
$routesFile = $base . '/cms/extensions/routes.php';
if (file_exists($routesFile)) {
    $routeContent = file_get_contents($routesFile);
    $changed = false;

    if (str_contains($routeContent, 'pages/projekt.php')) {
        $routeContent = str_replace('pages/projekt.php', 'pages/inlagg.php', $routeContent);
        $changed = true;
    }
    if (str_contains($routeContent, 'pages/projekt-single.php')) {
        $routeContent = str_replace('pages/projekt-single.php', 'pages/inlagg-single.php', $routeContent);
        $changed = true;
    }

    if ($changed) {
        file_put_contents($routesFile, $routeContent, LOCK_EX);
    }
}

// --- 4. Update projects.json ---
$projectsFile = $base . '/data/projects.json';
if (file_exists($projectsFile)) {
    $projects = json_decode(file_get_contents($projectsFile), true);
    if (is_array($projects)) {
        $changed = false;
        foreach ($projects as &$p) {
            if (isset($p['category']) && $p['category'] === 'Projekt') {
                $p['category'] = 'Inlägg';
                $changed = true;
            }
        }
        unset($p);
        if ($changed) {
            file_put_contents($projectsFile, json_encode($projects, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
        }
    }
}

// --- 5. Delete old files ---
$oldFiles = [
    'pages/projekt.php',
    'pages/projekt-single.php',
    'assets/css/projekt-default.css',
    'assets/css/projekt-single-default.css',
];
foreach ($oldFiles as $f) {
    $path = $base . '/' . $f;
    if (file_exists($path)) {
        @unlink($path);
    }
}

return 'ok';
