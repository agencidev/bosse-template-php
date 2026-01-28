<?php
/**
 * CSRF Protection
 * Skyddar mot Cross-Site Request Forgery attacker
 */

/**
 * Generera CSRF token
 */
function csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Generera CSRF field för formulär
 */
function csrf_field() {
    $token = csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Validera CSRF token (stödjer både POST och header)
 */
function csrf_verify() {
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }

    // Kontrollera POST-parameter först
    if (isset($_POST['csrf_token'])) {
        return hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
    }

    // Kontrollera X-CSRF-Token header (för AJAX/JSON requests)
    $headers = getallheaders();
    $csrf_header = $headers['X-CSRF-Token'] ?? $headers['X-Csrf-Token'] ?? $headers['x-csrf-token'] ?? null;

    if ($csrf_header !== null) {
        return hash_equals($_SESSION['csrf_token'], $csrf_header);
    }

    return false;
}

/**
 * Validera CSRF token för JSON API
 */
function csrf_verify_json() {
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }

    // Kontrollera X-CSRF-Token header
    $headers = getallheaders();
    $csrf_header = $headers['X-CSRF-Token'] ?? $headers['X-Csrf-Token'] ?? $headers['x-csrf-token'] ?? null;

    if ($csrf_header !== null) {
        return hash_equals($_SESSION['csrf_token'], $csrf_header);
    }

    // Kontrollera även i JSON body
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (isset($data['csrf_token'])) {
        return hash_equals($_SESSION['csrf_token'], $data['csrf_token']);
    }

    return false;
}

/**
 * Kräv giltig CSRF token eller avbryt
 */
function csrf_require() {
    if (!csrf_verify()) {
        http_response_code(403);
        die('CSRF token validation failed');
    }
}

/**
 * Regenerera CSRF token (efter lyckad form submission)
 */
function csrf_regenerate() {
    unset($_SESSION['csrf_token']);
    return csrf_token();
}
