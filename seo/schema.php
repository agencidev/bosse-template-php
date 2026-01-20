<?php
/**
 * Structured Data (Schema.org)
 * Genererar structured data för SEO
 */

/**
 * Organization Schema
 */
function organizationSchema($name = null, $logo = null, $description = null) {
    $name = $name ?? SITE_NAME;
    $logo = $logo ?? SITE_URL . '/assets/images/logo.png';
    $description = $description ?? SITE_DESCRIPTION;
    
    $schema = [
        "@context" => "https://schema.org",
        "@type" => "Organization",
        "name" => $name,
        "url" => SITE_URL,
        "logo" => $logo,
        "description" => $description,
        "contactPoint" => [
            "@type" => "ContactPoint",
            "contactType" => "customer service",
            "email" => $_ENV['CONTACT_EMAIL'] ?? 'info@example.com'
        ]
    ];
    
    echo '<script type="application/ld+json">';
    echo json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    echo '</script>';
}

/**
 * Website Schema
 */
function websiteSchema() {
    $schema = [
        "@context" => "https://schema.org",
        "@type" => "WebSite",
        "name" => SITE_NAME,
        "url" => SITE_URL,
        "potentialAction" => [
            "@type" => "SearchAction",
            "target" => SITE_URL . "/search?q={search_term_string}",
            "query-input" => "required name=search_term_string"
        ]
    ];
    
    echo '<script type="application/ld+json">';
    echo json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    echo '</script>';
}

/**
 * LocalBusiness Schema (för lokala företag)
 */
function localBusinessSchema($businessType = 'LocalBusiness', $address = [], $geo = []) {
    $schema = [
        "@context" => "https://schema.org",
        "@type" => $businessType,
        "name" => SITE_NAME,
        "url" => SITE_URL,
        "telephone" => $address['phone'] ?? '',
        "address" => [
            "@type" => "PostalAddress",
            "streetAddress" => $address['street'] ?? '',
            "addressLocality" => $address['city'] ?? '',
            "postalCode" => $address['zip'] ?? '',
            "addressCountry" => "SE"
        ]
    ];
    
    if (!empty($geo)) {
        $schema["geo"] = [
            "@type" => "GeoCoordinates",
            "latitude" => $geo['lat'] ?? '',
            "longitude" => $geo['lng'] ?? ''
        ];
    }
    
    echo '<script type="application/ld+json">';
    echo json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    echo '</script>';
}

/**
 * Article Schema (för blogginlägg)
 */
function articleSchema($title, $description, $image, $datePublished, $dateModified = null, $author = null) {
    $author = $author ?? SITE_NAME;
    $dateModified = $dateModified ?? $datePublished;
    
    $schema = [
        "@context" => "https://schema.org",
        "@type" => "Article",
        "headline" => $title,
        "description" => $description,
        "image" => $image,
        "datePublished" => $datePublished,
        "dateModified" => $dateModified,
        "author" => [
            "@type" => "Person",
            "name" => $author
        ],
        "publisher" => [
            "@type" => "Organization",
            "name" => SITE_NAME,
            "logo" => [
                "@type" => "ImageObject",
                "url" => SITE_URL . '/assets/images/logo.png'
            ]
        ]
    ];
    
    echo '<script type="application/ld+json">';
    echo json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    echo '</script>';
}
