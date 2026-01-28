<?php
require_once __DIR__ . '/bootstrap.php';

header('Content-Type: text/plain; charset=UTF-8');

echo "User-agent: *\n";
echo "Allow: /\n";
echo "\n";
echo "# Blockera känsliga mappar\n";
echo "Disallow: /cms/\n";
echo "Disallow: /data/\n";
echo "Disallow: /.env\n";
echo "\n";
echo "# Sitemap\n";
echo "Sitemap: " . SITE_URL . "/sitemap.xml\n";
