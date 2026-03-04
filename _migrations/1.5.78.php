<?php
/**
 * Migration 1.5.78
 * Robust CSS + category handling that survives updates.
 *
 * - Creates cms/extensions/categories.php if missing
 * - If upgrading from 1.5.76/77 where projekt-custom.css has real styles
 *   but projekt-default.css is missing: copies custom → default as safety net
 *
 * Idempotent: safe to run multiple times.
 */

$base = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__);

// --- 1. Create categories.php if missing ---
$categoriesFile = $base . '/cms/extensions/categories.php';
if (!file_exists($categoriesFile)) {
    $categoriesDir = dirname($categoriesFile);
    if (!is_dir($categoriesDir)) {
        mkdir($categoriesDir, 0755, true);
    }
    file_put_contents($categoriesFile, <<<'PHP'
<?php
/**
 * Category configuration (SAFE — survives updates)
 *
 * Each key is a URL prefix. The PHP views use this to determine
 * which category to filter by and what labels to show.
 *
 * To add a custom category, add a new entry here, then add
 * matching routes in cms/extensions/routes.php.
 */
return [
    '/blogg'   => ['category' => 'Blogg',   'title_sv' => 'Blogg',        'title_en' => 'Blog',         'base_url' => '/blogg'],
    '/projekt' => ['category' => 'Projekt',  'title_sv' => 'Våra projekt', 'title_en' => 'Our projects', 'base_url' => '/projekt'],
    '/nyheter' => ['category' => 'Nyhet',    'title_sv' => 'Nyheter',      'title_en' => 'News',         'base_url' => '/nyheter'],
    '/event'   => ['category' => 'Event',    'title_sv' => 'Event',        'title_en' => 'Events',       'base_url' => '/event'],
];
PHP
    );
}

// --- 2. Safety net for v1.5.76/77 upgrades ---
// If custom CSS has real styles but default CSS is missing (shouldn't happen
// since default is in the ZIP, but just in case), copy custom → default.
$listCustom  = $base . '/assets/css/projekt-custom.css';
$listDefault = $base . '/assets/css/projekt-default.css';
if (!file_exists($listDefault) && file_exists($listCustom) && filesize($listCustom) > 200) {
    copy($listCustom, $listDefault);
}

$singleCustom  = $base . '/assets/css/projekt-single-custom.css';
$singleDefault = $base . '/assets/css/projekt-single-default.css';
if (!file_exists($singleDefault) && file_exists($singleCustom) && filesize($singleCustom) > 200) {
    copy($singleCustom, $singleDefault);
}

return 'ok';
