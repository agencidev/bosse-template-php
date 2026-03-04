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
            "email" => defined('CONTACT_EMAIL') ? CONTACT_EMAIL : 'info@example.com'
        ]
    ];

    // Sociala medier (sameAs)
    $sameAs = [];
    if (defined('SOCIAL_FACEBOOK') && SOCIAL_FACEBOOK) $sameAs[] = SOCIAL_FACEBOOK;
    if (defined('SOCIAL_INSTAGRAM') && SOCIAL_INSTAGRAM) $sameAs[] = SOCIAL_INSTAGRAM;
    if (defined('SOCIAL_LINKEDIN') && SOCIAL_LINKEDIN) $sameAs[] = SOCIAL_LINKEDIN;
    if (!empty($sameAs)) {
        $schema['sameAs'] = $sameAs;
    }

    echo '<script type="application/ld+json" ' . csp_nonce_attr() . '>';
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
        "description" => defined('SITE_DESCRIPTION') ? SITE_DESCRIPTION : ''
    ];

    echo '<script type="application/ld+json" ' . csp_nonce_attr() . '>';
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

    // Opening hours from config — parse "HH:MM-HH:MM" format
    if (defined('HOURS_WEEKDAYS') && HOURS_WEEKDAYS) {
        $parseHours = function($str) {
            if (preg_match('/(\d{1,2}[:.]\d{2})\s*[-–]\s*(\d{1,2}[:.]\d{2})/', $str, $m)) {
                return ['opens' => str_replace('.', ':', $m[1]), 'closes' => str_replace('.', ':', $m[2])];
            }
            return null;
        };
        $weekdayHours = $parseHours(HOURS_WEEKDAYS);
        if ($weekdayHours) {
            $schema['openingHoursSpecification'] = [
                [
                    "@type" => "OpeningHoursSpecification",
                    "dayOfWeek" => ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday"],
                    "opens" => $weekdayHours['opens'],
                    "closes" => $weekdayHours['closes']
                ]
            ];
            if (defined('HOURS_WEEKENDS') && HOURS_WEEKENDS && strtolower(HOURS_WEEKENDS) !== 'stängt') {
                $weekendHours = $parseHours(HOURS_WEEKENDS);
                if ($weekendHours) {
                    $schema['openingHoursSpecification'][] = [
                        "@type" => "OpeningHoursSpecification",
                        "dayOfWeek" => ["Saturday", "Sunday"],
                        "opens" => $weekendHours['opens'],
                        "closes" => $weekendHours['closes']
                    ];
                }
            }
        }
    }

    // Sociala medier
    $sameAs = [];
    if (defined('SOCIAL_FACEBOOK') && SOCIAL_FACEBOOK) $sameAs[] = SOCIAL_FACEBOOK;
    if (defined('SOCIAL_INSTAGRAM') && SOCIAL_INSTAGRAM) $sameAs[] = SOCIAL_INSTAGRAM;
    if (defined('SOCIAL_LINKEDIN') && SOCIAL_LINKEDIN) $sameAs[] = SOCIAL_LINKEDIN;
    if (!empty($sameAs)) {
        $schema['sameAs'] = $sameAs;
    }

    echo '<script type="application/ld+json" ' . csp_nonce_attr() . '>';
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

    echo '<script type="application/ld+json" ' . csp_nonce_attr() . '>';
    echo json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    echo '</script>';
}

/**
 * Output Default Schemas
 * Outputs both organization and website schemas
 */
function outputDefaultSchemas() {
    organizationSchema();
    websiteSchema();
}
