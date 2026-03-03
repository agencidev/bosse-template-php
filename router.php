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

// Serve robots.txt via PHP
if ($uri === '/robots.txt') {
    require __DIR__ . '/seo/robots.php';
    return true;
}

// URL routing
$routes = [
    '/admin' => '/cms/admin.php',
    '/dashboard' => '/cms/dashboard.php',
    '/media' => '/cms/media.php',
    '/seo' => '/cms/seo.php',
    '/support' => '/cms/support.php',
    '/projects' => '/cms/projects/index.php',
    '/projects/new' => '/cms/projects/new.php',
    '/projects/edit' => '/cms/projects/edit.php',
    '/kontakt' => '/pages/kontakt.php',
    '/cookies' => '/pages/cookies.php',
    '/integritetspolicy' => '/pages/integritetspolicy.php',
    '/setup' => '/setup.php',
    '/super-admin' => '/cms/super-admin.php',
    '/api/super' => '/cms/api-super.php',
    '/bosse-health' => '/bosse-health.php',
    '/inlagg' => '/pages/inlagg.php',
    '/categories' => '/cms/categories.php',
    '/tickets' => '/cms/tickets.php',
];

// Load custom routes from extensions (survives updates)
$customPatterns = [];
if (file_exists(__DIR__ . '/cms/extensions/routes.php')) {
    $custom = include __DIR__ . '/cms/extensions/routes.php';
    if (is_array($custom)) {
        $customPatterns = $custom['__patterns'] ?? [];
        unset($custom['__patterns']);
        $routes = array_merge($routes, $custom);
    }
}

// Resolve route file path (handles both relative and absolute paths)
function resolveRoutePath($target) {
    if (file_exists($target)) return $target;
    return __DIR__ . $target;
}

// Check if route exists
if (isset($routes[$uri])) {
    $_SERVER['SCRIPT_NAME'] = $routes[$uri];
    $_SERVER['PHP_SELF'] = $routes[$uri];

    // Preserve query string
    if ($query) {
        $_SERVER['QUERY_STRING'] = $query;
        parse_str($query, $_GET);
    }

    require resolveRoutePath($routes[$uri]);
    return true;
}

// Dynamic patterns from custom routes (e.g. /blogg/{slug}, /nyheter/{slug})
foreach ($customPatterns as $pattern) {
    if (preg_match($pattern[0], $uri, $matches)) {
        if (isset($pattern[2]) && is_array($pattern[2])) {
            foreach ($pattern[2] as $param => $index) {
                $_GET[$param] = $matches[$index];
            }
        }
        $_SERVER['SCRIPT_NAME'] = $pattern[1];
        $_SERVER['PHP_SELF'] = $pattern[1];

        if ($query) {
            $_SERVER['QUERY_STRING'] = $query;
            parse_str($query, $extraParams);
            $_GET = array_merge($_GET, $extraParams);
        }

        require resolveRoutePath($pattern[1]);
        return true;
    }
}

// Dynamic route: /tickets/{id}
if (preg_match('#^/tickets/(\d+)/?$#', $uri, $matches)) {
    $_GET['id'] = $matches[1];
    $_SERVER['SCRIPT_NAME'] = '/cms/ticket-single.php';
    $_SERVER['PHP_SELF'] = '/cms/ticket-single.php';

    if ($query) {
        parse_str($query, $extraParams);
        $_GET = array_merge($_GET, $extraParams);
    }

    require __DIR__ . '/cms/ticket-single.php';
    return true;
}

// Dynamic route: /inlagg/{slug}
if (preg_match('#^/inlagg/([a-z0-9-]+)/?$#', $uri, $matches)) {
    $_GET['slug'] = $matches[1];
    $_SERVER['SCRIPT_NAME'] = '/pages/inlagg-single.php';
    $_SERVER['PHP_SELF'] = '/pages/inlagg-single.php';

    // Preserve additional query string
    if ($query) {
        parse_str($query, $extraParams);
        $_GET = array_merge($_GET, $extraParams);
    }

    require __DIR__ . '/pages/inlagg-single.php';
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

// Try pages/ directory (e.g. /om-oss → pages/om-oss.php)
$pageFile = __DIR__ . '/pages' . $uri . '.php';
if (file_exists($pageFile)) {
    if ($query) {
        $_SERVER['QUERY_STRING'] = $query;
        parse_str($query, $_GET);
    }
    require $pageFile;
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
if (file_exists(__DIR__ . '/pages/errors/404.php')) {
    require __DIR__ . '/pages/errors/404.php';
} else {
    echo '404 - Sidan kunde inte hittas';
}
return true;
