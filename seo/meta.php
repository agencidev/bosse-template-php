<?php
/**
 * SEO Meta Tags Generator
 * Genererar meta-tags för SEO
 */

/**
 * Generera meta-tags för en sida
 */
function generateMeta($title, $description, $image = null, $type = 'website', $extra = []) {
    $siteUrl = SITE_URL;
    $siteName = SITE_NAME;

    // Build absolute image URL
    if ($image && !str_starts_with($image, 'http')) {
        $image = $siteUrl . $image;
    }
    $image = $image ?? $siteUrl . '/assets/images/og-default.jpg';

    // Canonical: strip query params for clean URL
    $path = strtok($_SERVER['REQUEST_URI'], '?');
    $canonicalUrl = $siteUrl . rtrim($path, '/');
    if ($path === '/') $canonicalUrl = $siteUrl . '/';

    // Sanitera input
    $title = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $description = htmlspecialchars($description, ENT_QUOTES, 'UTF-8');
    $siteName = htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8');

    // Try to get OG image dimensions
    $ogImageMeta = '';
    $localImage = str_replace($siteUrl, '', $image);
    $localPath = (defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__)) . $localImage;
    if (file_exists($localPath)) {
        $imgSize = @getimagesize($localPath);
        if ($imgSize !== false) {
            $ogImageMeta = "\n    <meta property=\"og:image:width\" content=\"{$imgSize[0]}\">";
            $ogImageMeta .= "\n    <meta property=\"og:image:height\" content=\"{$imgSize[1]}\">";
        }
    }

    // Article dates (for blog posts / projects)
    $articleMeta = '';
    if ($type === 'article') {
        if (!empty($extra['published'])) {
            $articleMeta .= "\n    <meta property=\"article:published_time\" content=\"" . htmlspecialchars($extra['published']) . "\">";
        }
        if (!empty($extra['modified'])) {
            $articleMeta .= "\n    <meta property=\"article:modified_time\" content=\"" . htmlspecialchars($extra['modified']) . "\">";
        }
    }

    echo <<<HTML
    <title>{$title} | {$siteName}</title>
    <meta name="description" content="{$description}">
    <link rel="canonical" href="{$canonicalUrl}">

    <!-- Open Graph -->
    <meta property="og:title" content="{$title}">
    <meta property="og:description" content="{$description}">
    <meta property="og:image" content="{$image}">
    <meta property="og:image:alt" content="{$title}">{$ogImageMeta}
    <meta property="og:url" content="{$canonicalUrl}">
    <meta property="og:type" content="{$type}">
    <meta property="og:site_name" content="{$siteName}">
    <meta property="og:locale" content="sv_SE">{$articleMeta}

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{$title}">
    <meta name="twitter:description" content="{$description}">
    <meta name="twitter:image" content="{$image}">

    <!-- Additional SEO -->
    <meta name="robots" content="index, follow">
    <meta name="language" content="Swedish">
    <meta name="author" content="{$siteName}">
HTML;
}

/**
 * Generera breadcrumb meta-tags
 */
function generateBreadcrumb($items) {
    $breadcrumbList = [
        "@context" => "https://schema.org",
        "@type" => "BreadcrumbList",
        "itemListElement" => []
    ];
    
    foreach ($items as $index => $item) {
        $breadcrumbList["itemListElement"][] = [
            "@type" => "ListItem",
            "position" => $index + 1,
            "name" => $item['name'],
            "item" => SITE_URL . $item['url']
        ];
    }
    
    echo '<script type="application/ld+json" ' . csp_nonce_attr() . '>';
    echo json_encode($breadcrumbList, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    echo '</script>';
}
