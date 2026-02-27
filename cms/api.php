<?php
/**
 * CMS API
 * API-endpoints för CMS-funktionalitet
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../security/csrf.php';
require_once __DIR__ . '/../security/session.php';
require_once __DIR__ . '/../security/validation.php';
require_once __DIR__ . '/content.php';

header('Content-Type: application/json');

// Kräv inloggning
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get':
        handleGet();
        break;

    case 'update':
        handleUpdate();
        break;

    case 'upload':
        handleUpload();
        break;

    case 'backup':
        handleBackup();
        break;

    case 'media-list':
        handleMediaList();
        break;

    case 'media-delete':
        handleMediaDelete();
        break;

    case 'media-select':
        handleMediaSelect();
        break;

    default:
        $customHandled = false;
        if (file_exists(__DIR__ . '/extensions/api-handlers.php')) {
            $customHandled = include __DIR__ . '/extensions/api-handlers.php';
        }
        if (!$customHandled) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
        }
}

/**
 * Hämta innehåll - EXAKT som Next.js /api/content
 */
function handleGet() {
    // Returnera hela content-objektet med CSRF token för efterföljande requests
    $response = get_all_content();
    $response['_csrf'] = csrf_token();
    echo json_encode($response);
}

/**
 * Uppdatera innehåll - EXAKT som Next.js /api/admin/content
 */
function handleUpdate() {
    // Validera Content-Type
    $content_type = $_SERVER['CONTENT_TYPE'] ?? '';
    if (!str_contains($content_type, 'application/json')) {
        http_response_code(415);
        echo json_encode(['success' => false, 'error' => 'Content-Type must be application/json']);
        exit;
    }

    // Validera CSRF token (via header eller body)
    if (!csrf_verify_json()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'CSRF validation failed']);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $key = $data['key'] ?? '';
    $value = $data['value'] ?? null;

    if (empty($key)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Key is required']);
        exit;
    }

    // Läs nuvarande content
    $content = get_all_content();

    // Uppdatera section (value är ett objekt med fields)
    $content[$key] = $value;

    // Spara hela content-filen med fillåsning
    if (file_put_contents(CONTENT_FILE, json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX)) {
        // Invalidera content-cache
        clear_content_cache();
        echo json_encode(['success' => true, '_version' => time()]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to save content']);
    }
}

/**
 * Ladda upp bild
 */
function handleUpload() {
    // Validera CSRF
    if (!csrf_verify()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'CSRF validation failed']);
        exit;
    }

    if (!isset($_FILES['image'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'No file uploaded']);
        exit;
    }

    $key = $_POST['key'] ?? '';
    $field = $_POST['field'] ?? '';
    if (empty($key)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Key is required']);
        exit;
    }

    // Validera fil
    $validation = validate_file_upload($_FILES['image']);
    if (!$validation['valid']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $validation['error']]);
        exit;
    }

    // Generera säkert filnamn baserat på faktisk MIME-typ
    $filename = generate_safe_filename($_FILES['image']['name'], $_FILES['image']['tmp_name']);
    $upload_path = UPLOADS_PATH . '/' . $filename;

    // Flytta fil
    if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
        // Optimera bild (resize + komprimering + WebP)
        $optResult = optimize_image($upload_path);
        $url = '/uploads/' . $filename;

        // Build full content key (e.g. "hero.image" for nested storage)
        $fullKey = !empty($field) ? $key . '.' . $field : $key;

        // Spara URL + bilddimensioner i en atomisk skrivning
        $updates = [$fullKey => $url];
        $imgSize = @getimagesize($upload_path);
        if ($imgSize !== false) {
            $updates[$fullKey . '_width'] = $imgSize[0];
            $updates[$fullKey . '_height'] = $imgSize[1];
        }
        save_content_bulk($updates);

        echo json_encode(['success' => true, 'url' => $url, 'optimized' => $optResult['optimized'] ?? false]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to upload file']);
    }
}

/**
 * Skapa och ladda ner backup
 */
function handleBackup() {
    // Kontrollera att ZipArchive finns
    if (!class_exists('ZipArchive')) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'ZipArchive är inte tillgänglig på servern']);
        exit;
    }

    $siteName = defined('SITE_NAME') ? preg_replace('/[^a-z0-9]/i', '-', SITE_NAME) : 'bosse';
    $filename = 'backup-' . strtolower($siteName) . '-' . date('Y-m-d') . '.zip';

    // Skapa temporär ZIP-fil
    $tmpFile = sys_get_temp_dir() . '/' . $filename;

    $zip = new ZipArchive();
    if ($zip->open($tmpFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Kunde inte skapa ZIP-fil']);
        exit;
    }

    // Lägg till data-filer
    $dataPath = DATA_PATH;
    $filesToBackup = ['content.json', 'projects.json'];
    foreach ($filesToBackup as $file) {
        $fullPath = $dataPath . '/' . $file;
        if (file_exists($fullPath)) {
            $zip->addFile($fullPath, 'data/' . $file);
        }
    }

    // Lägg till uploads-mapp (med storleksbegränsning)
    $uploadsPath = UPLOADS_PATH;
    if (is_dir($uploadsPath)) {
        $totalSize = 0;
        $maxSize = 50 * 1024 * 1024; // 50 MB max för uploads

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($uploadsPath, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $fileSize = $file->getSize();
                if ($totalSize + $fileSize > $maxSize) {
                    // Stoppa om vi når gränsen
                    break;
                }
                $relativePath = 'uploads/' . substr($file->getPathname(), strlen($uploadsPath) + 1);
                $zip->addFile($file->getPathname(), $relativePath);
                $totalSize += $fileSize;
            }
        }
    }

    $zip->close();

    // Skicka filen
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($tmpFile));
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');

    readfile($tmpFile);

    // Rensa upp
    @unlink($tmpFile);
    exit;
}

/**
 * Lista bilder i mediabiblioteket
 */
function handleMediaList() {
    $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
    $images = [];

    if (is_dir(UPLOADS_PATH)) {
        $files = scandir(UPLOADS_PATH);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExt, true)) continue;

            $fullPath = UPLOADS_PATH . '/' . $file;
            if (!is_file($fullPath)) continue;

            $images[] = [
                'filename' => $file,
                'url' => '/uploads/' . $file,
                'size' => filesize($fullPath),
                'modified' => filemtime($fullPath),
            ];
        }
    }

    // Sort newest first
    usort($images, fn($a, $b) => $b['modified'] - $a['modified']);

    echo json_encode([
        'success' => true,
        'images' => $images,
        '_csrf' => csrf_token(),
    ]);
}

/**
 * Radera bild från mediabiblioteket
 */
function handleMediaDelete() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        exit;
    }

    if (!csrf_verify_json()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'CSRF validation failed']);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $filename = $data['filename'] ?? '';

    // Validate filename — no path traversal
    if (empty($filename) || str_contains($filename, '/') || str_contains($filename, '\\') || str_contains($filename, '..')) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid filename']);
        exit;
    }

    $filePath = UPLOADS_PATH . '/' . $filename;
    $realPath = realpath($filePath);
    $realUploads = realpath(UPLOADS_PATH);

    // Ensure file is inside uploads directory
    if ($realPath === false || $realUploads === false || !str_starts_with($realPath, $realUploads . '/')) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'File not found']);
        exit;
    }

    // Check if referenced
    $referenced = false;
    $contentFile = DATA_PATH . '/content.json';
    $projectsFile = DATA_PATH . '/projects.json';
    $rawJson = '';
    if (file_exists($contentFile)) $rawJson .= file_get_contents($contentFile);
    if (file_exists($projectsFile)) $rawJson .= file_get_contents($projectsFile);
    if (str_contains($rawJson, $filename)) {
        $referenced = true;
    }

    // Delete file
    if (!@unlink($realPath)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Could not delete file']);
        exit;
    }

    // Delete WebP companion if it exists
    $pathInfo = pathinfo($realPath);
    $webpPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '.webp';
    if (file_exists($webpPath)) {
        @unlink($webpPath);
    }

    echo json_encode([
        'success' => true,
        'referenced' => $referenced,
        '_csrf' => csrf_token(),
    ]);
}

/**
 * Välj befintlig bild från mediabiblioteket
 */
function handleMediaSelect() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        exit;
    }

    if (!csrf_verify_json()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'CSRF validation failed']);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $key = $data['key'] ?? '';
    $field = $data['field'] ?? '';
    $url = $data['url'] ?? '';

    if (empty($key) || empty($field) || empty($url)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'key, field and url are required']);
        exit;
    }

    // Use save_content_bulk with dot-notation to update only the specific field
    $fullKey = $key . '.' . $field;
    $result = save_content_bulk([$fullKey => $url]);

    if ($result) {
        echo json_encode(['success' => true, '_csrf' => csrf_token()]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to save content']);
    }
}
