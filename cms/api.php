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
    
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
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
    if (strpos($content_type, 'application/json') === false) {
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
        $url = '/uploads/' . $filename;
        
        // Spara URL i content
        save_content($key, $url);
        
        echo json_encode(['success' => true, 'url' => $url]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to upload file']);
    }
}
