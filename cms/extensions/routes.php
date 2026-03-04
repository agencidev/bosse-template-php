<?php
/**
 * Custom Routes
 * 
 * Lägg till dina egna routes här. Dessa överlever systemuppdateringar.
 * Format: '/url-path' => '/path/to/file.php'
 * 
 * För dynamiska routes (med {slug}), använd __patterns:
 * '__patterns' => [
 *     [regex, file, params]
 * ]
 */

return [
    // Inlägg/Blog routes (BOSSE-kompatibel struktur)
    '/inlagg' => '/pages/inlagg.php',
    
    '__patterns' => [
        // /inlagg/{slug} route
        [
            '#^/inlagg/([a-z0-9-]+)/?$#',
            '/pages/inlagg-single.php',
            ['slug' => 1]
        ],
    ],
];
