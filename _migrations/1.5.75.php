<?php
/**
 * Migration v1.5.75
 * Fix incorrect routes.php at customer sites:
 * - Replace non-existent page files (blogg.php, nyheter.php, event.php) with projekt.php
 * - Remove __DIR__-based absolute paths, use relative /pages/... paths
 */

$rootPath = defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__);
$results  = [];

$routesFile = $rootPath . '/cms/extensions/routes.php';

if (!file_exists($routesFile)) {
    return ['routes.php not found (skipped)'];
}

$content  = file_get_contents($routesFile);
$original = $content;

// --- 1. Replace __DIR__-based paths with relative /pages/ paths ---
// Matches patterns like: __DIR__ . '/../pages/something.php'  or  __DIR__ . "/../pages/something.php"
// Also handles variations with/without spaces around the dot
$content = preg_replace(
    '/\b__DIR__\s*\.\s*[\'"][^"\']*?pages\/([^\'"]+)[\'"]/m',
    "'/pages/$1'",
    $content
);

if ($content !== $original) {
    $results[] = 'Removed __DIR__ absolute paths';
}

// --- 2. Replace wrong page filenames ---
$replacements = [
    'blogg.php'          => 'projekt.php',
    'blogg-single.php'   => 'projekt-single.php',
    'nyheter.php'        => 'projekt.php',
    'nyheter-single.php' => 'projekt-single.php',
    'event.php'          => 'projekt.php',
    'event-single.php'   => 'projekt-single.php',
];

foreach ($replacements as $wrong => $correct) {
    if (str_contains($content, '/pages/' . $wrong)) {
        $content = str_replace('/pages/' . $wrong, '/pages/' . $correct, $content);
        $results[] = "Replaced $wrong → $correct";
    }
}

// --- 3. Write back only if changed ---
if ($content !== $original) {
    file_put_contents($routesFile, $content, LOCK_EX);
    $results[] = 'routes.php updated';
} else {
    $results[] = 'routes.php already correct (no changes)';
}

return $results;
