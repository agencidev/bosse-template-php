<?php
/**
 * Sitemap Generator
 * Genererar XML sitemap för SEO
 */

require_once __DIR__ . '/../bootstrap.php';

header('Content-Type: application/xml; charset=utf-8');

// Statiska sidor
$pages = [
    ['url' => '/', 'priority' => '1.0', 'changefreq' => 'daily'],
    ['url' => '/om-oss', 'priority' => '0.8', 'changefreq' => 'monthly'],
    ['url' => '/kontakt', 'priority' => '0.8', 'changefreq' => 'monthly'],
    ['url' => '/cookies', 'priority' => '0.3', 'changefreq' => 'yearly'],
    ['url' => '/integritetspolicy', 'priority' => '0.3', 'changefreq' => 'yearly'],
];

// Dynamiska sidor från projects.json
$projectsFile = __DIR__ . '/../data/projects.json';
if (file_exists($projectsFile)) {
    $projects = json_decode(file_get_contents($projectsFile), true) ?? [];
    foreach ($projects as $project) {
        if (($project['status'] ?? 'draft') === 'published' && !empty($project['slug'])) {
            $pages[] = [
                'url' => '/projekt/' . $project['slug'],
                'priority' => '0.7',
                'changefreq' => 'weekly',
                'lastmod' => $project['updatedAt'] ?? $project['createdAt'] ?? null,
            ];
        }
    }
}

// Generera XML
echo '<?xml version="1.0" encoding="UTF-8"?>';
echo "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
echo "\n";

foreach ($pages as $page) {
    $url = SITE_URL . $page['url'];
    $lastmod = isset($page['lastmod']) ? substr($page['lastmod'], 0, 10) : date('Y-m-d');

    echo '  <url>';
    echo "\n";
    echo '    <loc>' . htmlspecialchars($url, ENT_XML1, 'UTF-8') . '</loc>';
    echo "\n";
    echo '    <lastmod>' . $lastmod . '</lastmod>';
    echo "\n";
    echo '    <changefreq>' . $page['changefreq'] . '</changefreq>';
    echo "\n";
    echo '    <priority>' . $page['priority'] . '</priority>';
    echo "\n";
    echo '  </url>';
    echo "\n";
}

echo '</urlset>';
