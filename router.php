<?php
/**
 * Router för PHP:s inbyggda server
 * Använd: php -S localhost:8000 router.php
 */

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$query = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);

// Setup wizard redirect
if (!file_exists(__DIR__ . '/config.php')) {
    if ($uri === '/setup' || $uri === '/setup.php') {
        require __DIR__ . '/setup.php';
        return true;
    }
    if (preg_match('/\.(css|js|png|jpg|jpeg|gif|webp|svg|ico|woff|woff2)$/', $uri)) {
        return false;
    }
    header('Location: /setup');
    exit;
}

// URL routing
$routes = [
    '/admin' => '/cms/admin.php',
    '/dashboard' => '/cms/dashboard.php',
    '/ai' => '/cms/ai.php',
    '/seo' => '/cms/seo.php',
    '/support' => '/cms/support.php',
    '/projects' => '/cms/projects/index.php',
    '/projects/new' => '/cms/projects/new.php',
    '/projects/edit' => '/cms/projects/edit.php',
    '/kontakt' => '/kontakt.php',
    '/cookies' => '/cookies.php',
    '/integritetspolicy' => '/integritetspolicy.php',
    '/setup' => '/setup.php',
    '/super-admin' => '/cms/super-admin.php',
    '/api/super' => '/cms/api-super.php',
];

// Check if route exists
if (isset($routes[$uri])) {
    $_SERVER['SCRIPT_NAME'] = $routes[$uri];
    $_SERVER['PHP_SELF'] = $routes[$uri];
    
    // Preserve query string
    if ($query) {
        $_SERVER['QUERY_STRING'] = $query;
        parse_str($query, $_GET);
    }
    
    require __DIR__ . $routes[$uri];
    return true;
}

// Handle .php files directly
if (preg_match('/\.php$/', $uri)) {
    $file = __DIR__ . $uri;
    if (file_exists($file)) {
        return false; // Let PHP serve it
    }
}

// Try adding .php extension
$file = __DIR__ . $uri . '.php';
if (file_exists($file)) {
    // Preserve query string
    if ($query) {
        $_SERVER['QUERY_STRING'] = $query;
        parse_str($query, $_GET);
    }
    require $file;
    return true;
}

// Try with index.php in directory
if (is_dir(__DIR__ . $uri)) {
    $indexFile = __DIR__ . $uri . '/index.php';
    if (file_exists($indexFile)) {
        // Preserve query string
        if ($query) {
            $_SERVER['QUERY_STRING'] = $query;
            parse_str($query, $_GET);
        }
        require $indexFile;
        return true;
    }
}

// Serve static files
$file = __DIR__ . $uri;
if (file_exists($file) && !is_dir($file)) {
    return false; // Let PHP serve it
}

// 404
http_response_code(404);
if (file_exists(__DIR__ . '/404.php')) {
    require __DIR__ . '/404.php';
} else {
    echo '404 - Sidan kunde inte hittas';
}
return true;
