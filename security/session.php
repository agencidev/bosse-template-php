<?php
/**
 * Session Management
 * Säker session-hantering
 */

// Rate limiting constants
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 300); // 5 minuter
define('LOGIN_ATTEMPTS_FILE', DATA_PATH . '/login_attempts.json');

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

/**
 * Rate Limiting - Hämta IP-adress (rå, för loggning)
 */
function get_client_ip() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    return preg_replace('/[^0-9a-fA-F:.]/', '', $ip);
}

/**
 * Rate Limiting - Hämta hashad IP (GDPR-säker, för lagring)
 */
function get_client_ip_hash(): string {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $salt = defined('SESSION_SECRET') ? SESSION_SECRET : 'bosse-default-salt';
    return hash_hmac('sha256', $ip, $salt);
}

/**
 * Rate Limiting - Hämta inloggningsförsök
 */
function get_login_attempts() {
    if (!file_exists(LOGIN_ATTEMPTS_FILE)) {
        return [];
    }
    $data = json_decode(file_get_contents(LOGIN_ATTEMPTS_FILE), true);
    return is_array($data) ? $data : [];
}

/**
 * Rate Limiting - Spara inloggningsförsök
 */
function save_login_attempts($attempts) {
    $dir = dirname(LOGIN_ATTEMPTS_FILE);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    file_put_contents(LOGIN_ATTEMPTS_FILE, json_encode($attempts, JSON_PRETTY_PRINT), LOCK_EX);
}

/**
 * Rate Limiting - Registrera misslyckat försök
 */
function record_login_attempt() {
    $ip = get_client_ip_hash();
    $attempts = get_login_attempts();

    if (!isset($attempts[$ip])) {
        $attempts[$ip] = ['count' => 0, 'first_attempt' => time(), 'last_attempt' => time()];
    }

    $attempts[$ip]['count']++;
    $attempts[$ip]['last_attempt'] = time();

    save_login_attempts($attempts);
}

/**
 * Rate Limiting - Kontrollera om inloggning är tillåten
 */
function check_login_rate_limit() {
    $ip = get_client_ip_hash();
    $attempts = get_login_attempts();

    // Rensa gamla poster (äldre än lockout-tid)
    $cleaned = false;
    foreach ($attempts as $stored_ip => $data) {
        if (time() - $data['last_attempt'] > LOGIN_LOCKOUT_TIME) {
            unset($attempts[$stored_ip]);
            $cleaned = true;
        }
    }
    if ($cleaned) {
        save_login_attempts($attempts);
    }

    // Kontrollera denna IP
    if (!isset($attempts[$ip])) {
        return ['allowed' => true, 'attempts_remaining' => MAX_LOGIN_ATTEMPTS];
    }

    $data = $attempts[$ip];

    if ($data['count'] >= MAX_LOGIN_ATTEMPTS) {
        $time_passed = time() - $data['last_attempt'];
        $wait_seconds = LOGIN_LOCKOUT_TIME - $time_passed;

        if ($wait_seconds > 0) {
            return [
                'allowed' => false,
                'wait_seconds' => $wait_seconds,
                'attempts_remaining' => 0
            ];
        }

        // Lockout har gått ut, återställ
        unset($attempts[$ip]);
        save_login_attempts($attempts);
        return ['allowed' => true, 'attempts_remaining' => MAX_LOGIN_ATTEMPTS];
    }

    return [
        'allowed' => true,
        'attempts_remaining' => MAX_LOGIN_ATTEMPTS - $data['count']
    ];
}

/**
 * Rate Limiting - Rensa försök efter lyckad inloggning
 */
function clear_login_attempts() {
    $ip = get_client_ip_hash();
    $attempts = get_login_attempts();

    if (isset($attempts[$ip])) {
        unset($attempts[$ip]);
        save_login_attempts($attempts);
    }
}
