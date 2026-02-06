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
 * MIME-typ till extension mapping
 */
function get_extension_from_mime($mime_type) {
    $mime_map = [
        'image/jpeg' => 'jpg',
        'image/jpg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
        'image/svg+xml' => 'svg',
        'image/bmp' => 'bmp',
        'image/tiff' => 'tiff',
        'application/pdf' => 'pdf',
        'text/plain' => 'txt',
        'text/html' => 'html',
        'text/css' => 'css',
        'application/javascript' => 'js',
        'application/json' => 'json',
    ];

    return $mime_map[$mime_type] ?? null;
}

/**
 * Optimera bild vid uppladdning
 * - Auto-resize om bredd > max_width
 * - Komprimera JPEG till angiven kvalitet
 * - Returnerar path till optimerad fil (eller original om optimering ej möjlig)
 *
 * @param string $path Sökväg till bildfilen
 * @param int $max_width Max bredd i pixlar (default 2000)
 * @param int $quality JPEG-kvalitet 0-100 (default 85)
 * @return array ['success' => bool, 'path' => string, 'optimized' => bool, 'message' => string]
 */
function optimize_image(string $path, int $max_width = 2000, int $quality = 85): array {
    // Kontrollera att GD finns
    if (!extension_loaded('gd')) {
        return [
            'success' => true,
            'path' => $path,
            'optimized' => false,
            'message' => 'GD-biblioteket saknas - ingen optimering'
        ];
    }

    // Läs bildinfo
    $imageInfo = @getimagesize($path);
    if ($imageInfo === false) {
        return [
            'success' => true,
            'path' => $path,
            'optimized' => false,
            'message' => 'Kunde inte läsa bildinfo'
        ];
    }

    $width = $imageInfo[0];
    $height = $imageInfo[1];
    $mime = $imageInfo['mime'];

    // Hoppa över om redan tillräckligt liten
    if ($width <= $max_width) {
        // Fortfarande komprimera JPEG om det är en
        if ($mime === 'image/jpeg') {
            $image = @imagecreatefromjpeg($path);
            if ($image !== false) {
                imagejpeg($image, $path, $quality);
                imagedestroy($image);
                return [
                    'success' => true,
                    'path' => $path,
                    'optimized' => true,
                    'message' => 'JPEG komprimerad'
                ];
            }
        }
        return [
            'success' => true,
            'path' => $path,
            'optimized' => false,
            'message' => 'Bilden behöver inte resizas'
        ];
    }

    // Skapa bild från fil baserat på typ
    switch ($mime) {
        case 'image/jpeg':
            $source = @imagecreatefromjpeg($path);
            break;
        case 'image/png':
            $source = @imagecreatefrompng($path);
            break;
        case 'image/webp':
            if (function_exists('imagecreatefromwebp')) {
                $source = @imagecreatefromwebp($path);
            } else {
                return [
                    'success' => true,
                    'path' => $path,
                    'optimized' => false,
                    'message' => 'WebP-stöd saknas i GD'
                ];
            }
            break;
        case 'image/gif':
            $source = @imagecreatefromgif($path);
            break;
        default:
            return [
                'success' => true,
                'path' => $path,
                'optimized' => false,
                'message' => 'Bildtypen stöds inte för optimering'
            ];
    }

    if ($source === false) {
        return [
            'success' => true,
            'path' => $path,
            'optimized' => false,
            'message' => 'Kunde inte läsa bilden'
        ];
    }

    // Beräkna nya dimensioner
    $ratio = $max_width / $width;
    $newWidth = $max_width;
    $newHeight = (int) round($height * $ratio);

    // Skapa ny bild
    $resized = imagecreatetruecolor($newWidth, $newHeight);

    // Bevara transparens för PNG
    if ($mime === 'image/png') {
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
        imagefilledrectangle($resized, 0, 0, $newWidth, $newHeight, $transparent);
    }

    // Resize
    imagecopyresampled($resized, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

    // Spara
    $saved = false;
    switch ($mime) {
        case 'image/jpeg':
            $saved = imagejpeg($resized, $path, $quality);
            break;
        case 'image/png':
            // PNG-kvalitet är 0-9 (0 = ingen komprimering)
            $pngQuality = (int) round((100 - $quality) / 11.11);
            $saved = imagepng($resized, $path, $pngQuality);
            break;
        case 'image/webp':
            if (function_exists('imagewebp')) {
                $saved = imagewebp($resized, $path, $quality);
            }
            break;
        case 'image/gif':
            $saved = imagegif($resized, $path);
            break;
    }

    // Frigör minne
    imagedestroy($source);
    imagedestroy($resized);

    if ($saved) {
        return [
            'success' => true,
            'path' => $path,
            'optimized' => true,
            'message' => "Resizad från {$width}x{$height} till {$newWidth}x{$newHeight}"
        ];
    }

    return [
        'success' => true,
        'path' => $path,
        'optimized' => false,
        'message' => 'Kunde inte spara optimerad bild'
    ];
}

/**
 * Generera säkert filnamn baserat på MIME-typ (inte originalnamn)
 */
function generate_safe_filename($original_filename, $tmp_file = null) {
    // Om tmp_file finns, bestäm extension från faktisk MIME-typ
    if ($tmp_file !== null && file_exists($tmp_file)) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $tmp_file);
        finfo_close($finfo);

        $extension = get_extension_from_mime($mime_type);

        if ($extension === null) {
            // Fallback: använd original extension om MIME inte känns igen
            $extension = strtolower(pathinfo($original_filename, PATHINFO_EXTENSION));
            // Validera att extension är säker
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'pdf'];
            if (!in_array($extension, $allowed_extensions)) {
                $extension = 'bin'; // Generisk extension för okända typer
            }
        }
    } else {
        // Fallback för bakåtkompatibilitet
        $extension = strtolower(pathinfo($original_filename, PATHINFO_EXTENSION));
    }

    $safe_name = bin2hex(random_bytes(16));
    return $safe_name . '.' . $extension;
}
