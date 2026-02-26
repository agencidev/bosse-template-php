<?php
/**
 * Content Security Policy (CSP) - Nonce Generator
 * Generates a unique nonce per request for script-src directives.
 */

/**
 * Get the CSP nonce for the current request
 */
function csp_nonce(): string {
    static $nonce = null;
    if ($nonce === null) {
        $nonce = base64_encode(random_bytes(16));
    }
    return $nonce;
}

/**
 * Output nonce="..." attribute for use in <script> tags
 */
function csp_nonce_attr(): string {
    return 'nonce="' . csp_nonce() . '"';
}

/**
 * Send the Content-Security-Policy header
 */
function send_csp_header(): void {
    if (headers_sent()) {
        return;
    }

    $nonce = csp_nonce();

    $directives = [
        "default-src 'self'",
        "script-src 'self' 'nonce-{$nonce}' https://www.googletagmanager.com https://www.google-analytics.com",
        "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
        "img-src 'self' data: https:",
        "font-src 'self' data: https://fonts.gstatic.com",
        "connect-src 'self' https://www.google-analytics.com https://raw.githubusercontent.com",
    ];

    header('Content-Security-Policy: ' . implode('; ', $directives));
}
