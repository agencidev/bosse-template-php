<?php
/**
 * Sitemap Generator
 * Genererar XML sitemap för SEO
 */

require_once __DIR__ . '/../bootstrap.php';

header('Content-Type: application/xml; charset=utf-8');

// Hämta alla sidor (detta kan anpassas baserat på din site-struktur)
$pages = [
    ['url' => '/', 'priority' => '1.0', 'changefreq' => 'daily'],
    ['url' => '/om-oss', 'priority' => '0.8', 'changefreq' => 'monthly'],
    ['url' => '/kontakt', 'priority' => '0.8', 'changefreq' => 'monthly'],
];

// Generera XML
echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

foreach ($pages as $page) {
    $url = SITE_URL . $page['url'];
    $lastmod = date('Y-m-d');
    
    echo '<url>';
    echo '<loc>' . htmlspecialchars($url, ENT_XML1, 'UTF-8') . '</loc>';
    echo '<lastmod>' . $lastmod . '</lastmod>';
    echo '<changefreq>' . $page['changefreq'] . '</changefreq>';
    echo '<priority>' . $page['priority'] . '</priority>';
    echo '</url>';
}

echo '</urlset>';
