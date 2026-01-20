<?php
/**
 * CMS API
 * API-endpoints för CMS-funktionalitet
 */

require_once __DIR__ . '/../config.example.php';
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
 * Hämta innehåll
 */
function handleGet() {
    $key = $_GET['key'] ?? '';
    
    if (empty($key)) {
        echo json_encode(['success' => true, 'data' => get_all_content()]);
    } else {
        $value = get_content($key, null);
        echo json_encode(['success' => true, 'data' => $value]);
    }
}

/**
 * Uppdatera innehåll
 */
function handleUpdate() {
    // Validera CSRF
    if (!csrf_verify()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'CSRF validation failed']);
        exit;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    $key = $data['key'] ?? '';
    $value = $data['value'] ?? '';
    
    if (empty($key)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Key is required']);
        exit;
    }
    
    // Sanitera värde
    $value = sanitize_text($value);
    
    // Spara
    if (save_content($key, $value)) {
        echo json_encode(['success' => true, 'message' => 'Content updated']);
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
    
    // Generera säkert filnamn
    $filename = generate_safe_filename($_FILES['image']['name']);
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
