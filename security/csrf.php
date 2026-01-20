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
 * Validera CSRF token
 */
function csrf_verify() {
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token'])) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
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
