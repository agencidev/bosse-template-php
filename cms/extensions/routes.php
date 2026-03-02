<?php
/**
 * Custom routes (SAFE — survives updates)
 * Static routes: '/path' => '/pages/file.php'
 * Dynamic patterns: '__patterns' => [regex, target, params]
 */
return [
    '/blogg' => '/pages/projekt.php',

    '__patterns' => [
        ['#^/blogg/([a-z0-9-]+)/?$#', '/pages/projekt-single.php', ['slug' => 1]],
    ],
];
