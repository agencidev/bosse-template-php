<?php
/**
 * Input Validation
 * Validerar user input för säkerhet
 */

/**
 * Validera email
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validera text (alfanumeriskt + vissa tecken)
 */
function validate_text($text, $min_length = 1, $max_length = 255) {
    $text = trim($text);
    $length = mb_strlen($text);
    
    if ($length < $min_length || $length > $max_length) {
        return false;
    }
    
    return true;
}

/**
 * Validera URL
 */
function validate_url($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

/**
 * Validera telefonnummer (svenskt format)
 */
function validate_phone($phone) {
    $phone = preg_replace('/[^0-9+]/', '', $phone);
    return preg_match('/^(\+46|0)[0-9]{7,13}$/', $phone);
}

/**
 * Sanitera text input
 */
function sanitize_text($text) {
    return htmlspecialchars(trim($text), ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitera HTML (tillåt vissa taggar)
 */
function sanitize_html($html, $allowed_tags = '<p><br><strong><em><a>') {
    return strip_tags($html, $allowed_tags);
}

/**
 * Validera filuppladdning
 */
function validate_file_upload($file, $allowed_types = ['image/jpeg', 'image/png', 'image/webp'], $max_size = 5242880) {
    // Kontrollera att fil existerar
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return ['valid' => false, 'error' => 'Ingen fil uppladdad'];
    }
    
    // Kontrollera filstorlek (5MB default)
    if ($file['size'] > $max_size) {
        return ['valid' => false, 'error' => 'Filen är för stor (max ' . ($max_size / 1024 / 1024) . 'MB)'];
    }
    
    // Kontrollera filtyp
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime_type, $allowed_types)) {
        return ['valid' => false, 'error' => 'Ogiltig filtyp'];
    }
    
    return ['valid' => true];
}

/**
 * Generera säkert filnamn
 */
function generate_safe_filename($original_filename) {
    $extension = pathinfo($original_filename, PATHINFO_EXTENSION);
    $safe_name = bin2hex(random_bytes(16));
    return $safe_name . '.' . $extension;
}
