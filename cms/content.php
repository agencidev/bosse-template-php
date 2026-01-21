<?php
/**
 * CMS Content Management
 * Hanterar innehåll från JSON-fil
 */

require_once __DIR__ . '/../config.example.php';

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
    
    return file_put_contents(CONTENT_FILE, json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

/**
 * Hämta allt innehåll
 */
function get_all_content() {
    if (!file_exists(CONTENT_FILE)) {
        return [];
    }
    
    return json_decode(file_get_contents(CONTENT_FILE), true) ?? [];
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
    $displayValue = !empty($value) ? $value : $placeholder;
    
    if (!$is_admin) {
        echo "<{$as} class=\"{$className}\">{$displayValue}</{$as}>";
        return;
    }
    
    // Escape attributes
    $safeContentKey = htmlspecialchars($contentKey, ENT_QUOTES, 'UTF-8');
    $safeField = htmlspecialchars($field, ENT_QUOTES, 'UTF-8');
    $safeDefault = htmlspecialchars($defaultValue, ENT_QUOTES, 'UTF-8');
    
    // Output with data attributes for JavaScript
    echo "<{$as} 
        class=\"{$className} cms-editable-active\" 
        data-editable-text=\"true\"
        data-content-key=\"{$safeContentKey}\" 
        data-field=\"{$safeField}\"
        data-default-value=\"{$safeDefault}\"
        title=\"✏️ Klicka för att redigera\"
    >{$displayValue}</{$as}>";
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
    
    if (!$is_admin) {
        echo '<img src="' . htmlspecialchars($src, ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars($alt, ENT_QUOTES, 'UTF-8') . '" class="' . $className . '">';
        return;
    }
    
    $safeContentKey = htmlspecialchars($contentKey, ENT_QUOTES, 'UTF-8');
    $safeField = htmlspecialchars($field, ENT_QUOTES, 'UTF-8');
    $safeSrc = htmlspecialchars($src, ENT_QUOTES, 'UTF-8');
    $safeAlt = htmlspecialchars($alt, ENT_QUOTES, 'UTF-8');
    
    echo '<img 
        src="' . $safeSrc . '" 
        alt="' . $safeAlt . '" 
        class="' . $className . '" 
        data-editable-image="true"
        data-content-key="' . $safeContentKey . '"
        data-field="' . $safeField . '"
    >';
}
