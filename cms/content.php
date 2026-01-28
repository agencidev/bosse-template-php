<?php
/**
 * CMS Content Management
 * Hanterar innehåll från JSON-fil
 */

require_once __DIR__ . '/../bootstrap.php';

// Path till content-fil
define('CONTENT_FILE', DATA_PATH . '/content.json');

/**
 * Hämta innehåll från JSON
 */
function get_content($key, $default = '') {
    if (!file_exists(CONTENT_FILE)) {
        return $default;
    }
    
    $content = json_decode(file_get_contents(CONTENT_FILE), true);
    
    // Stöd för nested keys (t.ex. "hero.title")
    $keys = explode('.', $key);
    $value = $content;
    
    foreach ($keys as $k) {
        if (!isset($value[$k])) {
            return $default;
        }
        $value = $value[$k];
    }
    
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/**
 * Spara innehåll till JSON
 */
function save_content($key, $value) {
    $content = [];
    
    if (file_exists(CONTENT_FILE)) {
        $content = json_decode(file_get_contents(CONTENT_FILE), true) ?? [];
    }
    
    // Stöd för nested keys
    $keys = explode('.', $key);
    $temp = &$content;
    
    foreach ($keys as $i => $k) {
        if ($i === count($keys) - 1) {
            $temp[$k] = $value;
        } else {
            if (!isset($temp[$k]) || !is_array($temp[$k])) {
                $temp[$k] = [];
            }
            $temp = &$temp[$k];
        }
    }
    
    return file_put_contents(CONTENT_FILE, json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}

/**
 * Hämta allt innehåll (med cache för prestanda)
 */
function get_all_content() {
    static $cache = null;

    if ($cache !== null) {
        return $cache;
    }

    if (!file_exists(CONTENT_FILE)) {
        $cache = [];
        return $cache;
    }

    $cache = json_decode(file_get_contents(CONTENT_FILE), true) ?? [];
    return $cache;
}

/**
 * Rensa content-cache (anropas efter uppdatering)
 */
function clear_content_cache() {
    // Statisk variabel kan inte nollställas utifrån,
    // men vid nästa request laddas filen om ändå
    // Denna funktion finns för tydlighet och framtida utbyggnad
}

/**
 * Redigerbar text - EXAKT som EditableText.jsx i Next.js
 * 
 * @param string $contentKey - Content key (e.g., 'hero')
 * @param string $field - Field name (e.g., 'title')
 * @param string $defaultValue - Default value
 * @param string $as - HTML tag (h1, h2, p, span, etc.)
 * @param string $className - CSS classes
 * @param string $placeholder - Placeholder text
 */
function editable_text($contentKey, $field, $defaultValue = '', $as = 'p', $className = '', $placeholder = 'Klicka för att redigera...') {
    $is_admin = isset($_SESSION['logged_in']) && $_SESSION['logged_in'];

    // Get value from content
    $content = get_all_content();
    $value = isset($content[$contentKey][$field]) ? $content[$contentKey][$field] : $defaultValue;
    $displayValue = !empty($value) ? htmlspecialchars($value, ENT_QUOTES, 'UTF-8') : htmlspecialchars($placeholder, ENT_QUOTES, 'UTF-8');

    // Escape alla attribut
    $safeAs = preg_replace('/[^a-z0-9]/i', '', $as); // Endast alfanumeriska tecken för taggen
    $safeClassName = htmlspecialchars($className, ENT_QUOTES, 'UTF-8');

    if (!$is_admin) {
        echo "<{$safeAs} class=\"{$safeClassName}\">{$displayValue}</{$safeAs}>";
        return;
    }

    // Escape attributes för admin-läge
    $safeContentKey = htmlspecialchars($contentKey, ENT_QUOTES, 'UTF-8');
    $safeField = htmlspecialchars($field, ENT_QUOTES, 'UTF-8');
    $safeDefault = htmlspecialchars($defaultValue, ENT_QUOTES, 'UTF-8');

    // Output with data attributes for JavaScript
    echo "<{$safeAs}
        class=\"{$safeClassName} cms-editable-active\"
        data-editable-text=\"true\"
        data-content-key=\"{$safeContentKey}\"
        data-field=\"{$safeField}\"
        data-default-value=\"{$safeDefault}\"
        title=\"Klicka för att redigera\"
    >{$displayValue}</{$safeAs}>";
}

/**
 * Redigerbar bild - EXAKT som EditableImage.jsx i Next.js
 * 
 * @param string $contentKey - Content key (e.g., 'hero')
 * @param string $field - Field name (e.g., 'image')
 * @param string $defaultValue - Default image path
 * @param string $alt - Alt text
 * @param string $className - CSS classes
 */
function editable_image($contentKey, $field, $defaultValue = '/assets/images/placeholder.jpg', $alt = '', $className = '') {
    $is_admin = isset($_SESSION['logged_in']) && $_SESSION['logged_in'];

    // Get value from content
    $content = get_all_content();
    $src = isset($content[$contentKey][$field]) ? $content[$contentKey][$field] : $defaultValue;

    // Escape alla attribut
    $safeSrc = htmlspecialchars($src, ENT_QUOTES, 'UTF-8');
    $safeAlt = htmlspecialchars($alt, ENT_QUOTES, 'UTF-8');
    $safeClassName = htmlspecialchars($className, ENT_QUOTES, 'UTF-8');

    if (!$is_admin) {
        echo '<img src="' . $safeSrc . '" alt="' . $safeAlt . '" class="' . $safeClassName . '">';
        return;
    }

    $safeContentKey = htmlspecialchars($contentKey, ENT_QUOTES, 'UTF-8');
    $safeField = htmlspecialchars($field, ENT_QUOTES, 'UTF-8');

    echo '<img
        src="' . $safeSrc . '"
        alt="' . $safeAlt . '"
        class="' . $safeClassName . '"
        data-editable-image="true"
        data-content-key="' . $safeContentKey . '"
        data-field="' . $safeField . '"
    >';
}
