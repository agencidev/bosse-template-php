<?php
/**
 * Gemensamma hjälpfunktioner för CMS
 * Centraliserad plats för återanvändbara funktioner
 */

/**
 * Konvertera PHP-storlek (t.ex. "8M") till bytes
 */
function convertToBytes(string $value): int {
    $value = trim($value);
    $unit = strtolower(substr($value, -1));
    $bytes = (int) $value;
    $bytes *= match ($unit) {
        'g' => 1024 * 1024 * 1024,
        'm' => 1024 * 1024,
        'k' => 1024,
        default => 1,
    };
    return $bytes;
}

/**
 * Justera ljusstyrka på en hex-färg
 * @param string $hex Hex-färgkod (med eller utan #)
 * @param int $percent Positiv = ljusare, negativ = mörkare
 */
function adjustBrightness(string $hex, int $percent): string {
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }

    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));

    if ($percent > 0) {
        // Lighten
        $r = $r + (255 - $r) * $percent / 100;
        $g = $g + (255 - $g) * $percent / 100;
        $b = $b + (255 - $b) * $percent / 100;
    } else {
        // Darken
        $factor = (100 + $percent) / 100;
        $r = $r * $factor;
        $g = $g * $factor;
        $b = $b * $factor;
    }

    $r = max(0, min(255, round($r)));
    $g = max(0, min(255, round($g)));
    $b = max(0, min(255, round($b)));

    return '#' . str_pad(dechex($r), 2, '0', STR_PAD_LEFT)
               . str_pad(dechex($g), 2, '0', STR_PAD_LEFT)
               . str_pad(dechex($b), 2, '0', STR_PAD_LEFT);
}

/**
 * Generera URL-slug från titel
 * Hanterar svenska tecken och ersätter specialtecken
 */
function generateSlug(string $title): string {
    $slug = strtolower($title);
    $slug = str_replace(['å', 'ä', 'ö'], ['a', 'a', 'o'], $slug);
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug;
}

/**
 * Skapa backup av config.php innan ändringar
 */
function backup_config(): bool {
    $config = ROOT_PATH . '/config.php';
    if (!file_exists($config)) return false;
    $backup = ROOT_PATH . '/config.php.bak';
    return copy($config, $backup);
}

/**
 * Safe preg_replace for config.php — verifies the replacement succeeded
 * Returns the modified string, or the original + logs a warning on failure.
 *
 * @param string $pattern  Regex pattern
 * @param string $replacement  Replacement string
 * @param string $subject  The config file contents
 * @param string $constantName  Name of the constant (for logging)
 * @return array ['config' => string, 'success' => bool]
 */
function safe_config_replace(string $pattern, string $replacement, string $subject, string $constantName = ''): array {
    $result = preg_replace($pattern, $replacement, $subject, -1, $count);
    if ($result === null) {
        error_log("Config update FAILED (regex error) for {$constantName}: " . preg_last_error_msg());
        return ['config' => $subject, 'success' => false];
    }
    if ($count === 0) {
        // Pattern didn't match — constant missing or format changed
        error_log("Config update WARNING: pattern did not match for {$constantName}");
        return ['config' => $subject, 'success' => false];
    }
    return ['config' => $result, 'success' => true];
}

/**
 * Kontrollera om POST-data överstiger PHP-gränser
 * @return string|null Felmeddelande om gränsen överskrids, annars null
 */
function checkPostSizeLimit(): ?string {
    $contentLength = $_SERVER['CONTENT_LENGTH'] ?? 0;
    $postMaxSize = ini_get('post_max_size');
    $postMaxBytes = convertToBytes($postMaxSize);

    if ($contentLength > $postMaxBytes || (empty($_POST) && $contentLength > 0)) {
        return 'Filen är för stor för servern. Max: ' . $postMaxSize . '. Kontakta din webbhost för att öka gränsen, eller använd en mindre bild.';
    }
    return null;
}
