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
 * Redigerbar text (inline CMS)
 */
function editable_text($key, $default = '', $tag = 'span', $class = '') {
    $value = get_content($key, $default);
    $is_admin = isset($_SESSION['logged_in']) && $_SESSION['logged_in'];
    
    $editable_attr = $is_admin ? 'contenteditable="true" data-key="' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . '"' : '';
    $admin_class = $is_admin ? ' editable' : '';
    
    echo "<{$tag} class=\"{$class}{$admin_class}\" {$editable_attr}>{$value}</{$tag}>";
}

/**
 * Redigerbar bild (inline CMS)
 */
function editable_image($key, $default = '/assets/images/placeholder.jpg', $alt = '', $class = '') {
    $src = get_content($key, $default);
    $is_admin = isset($_SESSION['logged_in']) && $_SESSION['logged_in'];
    
    if ($is_admin) {
        echo '<div class="editable-image-wrapper" data-key="' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . '">';
        echo '<img src="' . htmlspecialchars($src, ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars($alt, ENT_QUOTES, 'UTF-8') . '" class="' . $class . ' editable-image">';
        echo '<button class="image-upload-btn" onclick="uploadImage(\'' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . '\')">Ändra bild</button>';
        echo '</div>';
    } else {
        echo '<img src="' . htmlspecialchars($src, ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars($alt, ENT_QUOTES, 'UTF-8') . '" class="' . $class . '">';
    }
}
