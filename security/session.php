<?php
/**
 * Session Management
 * Säker session-hantering
 */

/**
 * Initiera säker session
 */
function init_secure_session() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Kontrollera session timeout (30 minuter)
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
        session_unset();
        session_destroy();
        session_start();
    }
    
    $_SESSION['last_activity'] = time();
    
    // Regenerera session ID regelbundet
    if (!isset($_SESSION['created'])) {
        $_SESSION['created'] = time();
    } else if (time() - $_SESSION['created'] > 1800) {
        session_regenerate_id(true);
        $_SESSION['created'] = time();
    }
}

/**
 * Logga in användare
 */
function login_user($username) {
    session_regenerate_id(true);
    $_SESSION['logged_in'] = true;
    $_SESSION['username'] = $username;
    $_SESSION['login_time'] = time();
}

/**
 * Logga ut användare
 */
function logout_user() {
    $_SESSION = array();
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
}

/**
 * Kontrollera om användare är inloggad
 */
function is_logged_in() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * Kräv inloggning
 */
function require_login() {
    if (!is_logged_in()) {
        header('Location: /cms/admin.php?action=login');
        exit;
    }
}
