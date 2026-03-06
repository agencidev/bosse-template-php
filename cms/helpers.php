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
 * Regenerate custom RewriteRules in .htaccess from routes.php + categories.php
 *
 * Reads cms/extensions/routes.php and categories.php, generates Apache
 * RewriteRule directives, and injects them between the
 * BEGIN/END BOSSE CUSTOM ROUTES markers in .htaccess.
 *
 * Called by: updater (after apply_update), category admin (after add/delete)
 */
function regenerate_htaccess_routes(): bool
{
    $root = defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__);
    $htaccess = $root . '/.htaccess';
    $routesFile = $root . '/cms/extensions/routes.php';
    $catsFile = $root . '/cms/extensions/categories.php';

    if (!file_exists($htaccess)) return false;

    $content = file_get_contents($htaccess);
    if ($content === false) return false;

    // Collect rules
    $rules = [];

    // 1. Category routes (e.g. /blogg -> pages/inlagg.php)
    if (file_exists($catsFile)) {
        $cats = require $catsFile;
        if (is_array($cats)) {
            foreach ($cats as $prefix => $cat) {
                if (!is_string($prefix) || !str_starts_with($prefix, '/')) continue;
                if ($prefix === '/inlagg') continue; // handled by built-in rule
                $slug = ltrim($prefix, '/');
                $rules[] = "# {$prefix} -> category listing";
                $rules[] = "RewriteRule ^{$slug}/?$ pages/inlagg.php [L]";
                $rules[] = 'RewriteRule ^' . $slug . '/([a-z0-9-]+)/?$ pages/inlagg-single.php?slug=$1 [QSA,L]';
            }
        }
    }

    // 2. Custom static routes from routes.php
    if (file_exists($routesFile)) {
        $custom = require $routesFile;
        if (is_array($custom)) {
            $patterns = $custom['__patterns'] ?? [];
            unset($custom['__patterns']);

            foreach ($custom as $path => $target) {
                if (!is_string($path) || !is_string($target)) continue;
                // Skip category routes (already handled above)
                if (file_exists($catsFile)) {
                    $cats2 = require $catsFile;
                    if (is_array($cats2) && isset($cats2[$path])) continue;
                }
                $slug = ltrim($path, '/');
                $targetClean = ltrim($target, '/');
                $rules[] = "# {$path} -> {$target}";
                $rules[] = "RewriteRule ^{$slug}/?$ {$targetClean} [QSA,L]";
            }

            // Dynamic patterns are handled by router.php front controller
        }
    }

    // Build replacement block
    $block = "# BEGIN BOSSE CUSTOM ROUTES\n";
    $block .= "# Auto-generated from cms/extensions/routes.php and categories.php\n";
    $block .= "# Regenerated on update and when categories change — do not edit manually\n";
    if (!empty($rules)) {
        $block .= implode("\n", $rules) . "\n";
    }
    $block .= "# END BOSSE CUSTOM ROUTES";

    // Replace between markers (use preg_match + substr to avoid $1 backreference issues)
    $beginMarker = '# BEGIN BOSSE CUSTOM ROUTES';
    $endMarker = '# END BOSSE CUSTOM ROUTES';
    $beginPos = strpos($content, $beginMarker);
    $endPos = strpos($content, $endMarker);
    if ($beginPos !== false && $endPos !== false) {
        $newContent = substr($content, 0, $beginPos) . $block . substr($content, $endPos + strlen($endMarker));
    } else {
        // No markers found — inject before front controller
        $fcComment = '# Front controller';
        $pos = strpos($content, $fcComment);
        if ($pos !== false) {
            $newContent = substr($content, 0, $pos) . $block . "\n\n" . substr($content, $pos);
        } else {
            // Append at end
            $newContent = $content . "\n" . $block . "\n";
        }
    }

    if ($newContent === null || $newContent === $content && !empty($rules)) {
        return false;
    }

    return file_put_contents($htaccess, $newContent, LOCK_EX) !== false;
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
