<?php
/**
 * SEO Meta Tags Generator
 * Genererar meta-tags för SEO
 */

/**
 * Generera meta-tags för en sida
 */
function generateMeta($title, $description, $image = null, $type = 'website') {
    $siteUrl = SITE_URL;
    $siteName = SITE_NAME;
    $image = $image ?? $siteUrl . '/assets/images/og-default.jpg';
    $currentUrl = $siteUrl . $_SERVER['REQUEST_URI'];
    
    // Sanitera input
    $title = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $description = htmlspecialchars($description, ENT_QUOTES, 'UTF-8');
    $siteName = htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8');
    
    echo <<<HTML
    <title>{$title} | {$siteName}</title>
    <meta name="description" content="{$description}">
    <link rel="canonical" href="{$currentUrl}">
    
    <!-- Open Graph -->
    <meta property="og:title" content="{$title}">
    <meta property="og:description" content="{$description}">
    <meta property="og:image" content="{$image}">
    <meta property="og:url" content="{$currentUrl}">
    <meta property="og:type" content="{$type}">
    <meta property="og:site_name" content="{$siteName}">
    <meta property="og:locale" content="sv_SE">
    
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
    
    echo '<script type="application/ld+json">';
    echo json_encode($breadcrumbList, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    echo '</script>';
}
