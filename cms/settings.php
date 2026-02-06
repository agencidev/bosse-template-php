<?php
/**
 * CMS Settings Page
 * Allows editing site configuration after initial setup
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../security/session.php';
require_once __DIR__ . '/../security/csrf.php';

if (!is_logged_in()) {
    header('Location: /cms/admin.php');
    exit;
}

/**
 * Konvertera PHP-storlek (t.ex. "8M") till bytes
 */
function convertToBytes(string $value): int {
    $value = trim($value);
    $unit = strtolower(substr($value, -1));
    $bytes = (int) $value;
    switch ($unit) {
        case 'g': $bytes *= 1024;
        case 'm': $bytes *= 1024;
        case 'k': $bytes *= 1024;
    }
    return $bytes;
}

$success = '';
$error = '';

// Load current values from config and CSS
$currentConfig = [
    'site_name' => defined('SITE_NAME') ? SITE_NAME : '',
    'site_description' => defined('SITE_DESCRIPTION') ? SITE_DESCRIPTION : '',
    'contact_email' => defined('CONTACT_EMAIL') ? CONTACT_EMAIL : '',
    'contact_phone' => defined('CONTACT_PHONE') ? CONTACT_PHONE : '',
    'site_url' => defined('SITE_URL') ? SITE_URL : '',
    'social_facebook' => defined('SOCIAL_FACEBOOK') ? SOCIAL_FACEBOOK : '',
    'social_instagram' => defined('SOCIAL_INSTAGRAM') ? SOCIAL_INSTAGRAM : '',
    'social_linkedin' => defined('SOCIAL_LINKEDIN') ? SOCIAL_LINKEDIN : '',
    'hours_weekdays' => defined('HOURS_WEEKDAYS') ? HOURS_WEEKDAYS : '',
    'hours_weekends' => defined('HOURS_WEEKENDS') ? HOURS_WEEKENDS : '',
    'ga_id' => defined('GOOGLE_ANALYTICS_ID') ? GOOGLE_ANALYTICS_ID : '',
    'smtp_host' => defined('SMTP_HOST') ? SMTP_HOST : '',
    'smtp_port' => defined('SMTP_PORT') ? SMTP_PORT : 465,
    'smtp_encryption' => defined('SMTP_ENCRYPTION') ? SMTP_ENCRYPTION : 'ssl',
    'smtp_username' => defined('SMTP_USERNAME') ? SMTP_USERNAME : '',
];

// Read colors from variables.css
$cssFile = __DIR__ . '/../assets/css/variables.css';
$primaryColor = '#8b5cf6';
$secondaryColor = '#FF6B35';
$accentColor = '#fe4f2a';

if (file_exists($cssFile)) {
    $css = file_get_contents($cssFile);
    if (preg_match('/--color-primary:\s*(#[0-9a-fA-F]{6})/', $css, $m)) {
        $primaryColor = $m[1];
    }
    if (preg_match('/--color-secondary:\s*(#[0-9a-fA-F]{6})/', $css, $m)) {
        $secondaryColor = $m[1];
    }
    if (preg_match('/--color-accent:\s*(#[0-9a-fA-F]{6})/', $css, $m)) {
        $accentColor = $m[1];
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Detektera om POST-data trunkerades pga PHP-gränser
    $contentLength = $_SERVER['CONTENT_LENGTH'] ?? 0;
    $postMaxSize = ini_get('post_max_size');
    $postMaxBytes = convertToBytes($postMaxSize);

    if ($contentLength > $postMaxBytes || (empty($_POST) && $contentLength > 0)) {
        $error = 'Filen är för stor för servern. Max uppladdningsstorlek: ' . $postMaxSize . '. Kontakta din webbhost för att öka gränsen.';
    } else {
        csrf_require();
    }

    $section = $_POST['section'] ?? '';

    if ($section === 'site_info') {
        // Update site info
        $newName = trim($_POST['site_name'] ?? '');
        $newDesc = trim($_POST['site_description'] ?? '');
        $newEmail = trim($_POST['contact_email'] ?? '');
        $newPhone = trim($_POST['contact_phone'] ?? '');

        if (empty($newName)) {
            $error = 'Företagsnamn krävs.';
        } elseif (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            $error = 'Giltig e-postadress krävs.';
        } else {
            // Update config.php
            $configPath = ROOT_PATH . '/config.php';
            if (file_exists($configPath)) {
                $config = file_get_contents($configPath);
                $config = preg_replace("/define\('SITE_NAME',\s*'[^']*'\);/", "define('SITE_NAME', " . var_export($newName, true) . ");", $config);
                $config = preg_replace("/define\('SITE_DESCRIPTION',\s*'[^']*'\);/", "define('SITE_DESCRIPTION', " . var_export($newDesc, true) . ");", $config);
                $config = preg_replace("/define\('CONTACT_EMAIL',\s*'[^']*'\);/", "define('CONTACT_EMAIL', " . var_export($newEmail, true) . ");", $config);
                $config = preg_replace("/define\('CONTACT_PHONE',\s*'[^']*'\);/", "define('CONTACT_PHONE', " . var_export($newPhone, true) . ");", $config);
                file_put_contents($configPath, $config, LOCK_EX);
                $success = 'Företagsinformation uppdaterad!';
                // Update current values
                $currentConfig['site_name'] = $newName;
                $currentConfig['site_description'] = $newDesc;
                $currentConfig['contact_email'] = $newEmail;
                $currentConfig['contact_phone'] = $newPhone;
            }
        }
    } elseif ($section === 'colors') {
        // Update colors in variables.css
        $newPrimary = $_POST['primary_color'] ?? $primaryColor;
        $newSecondary = $_POST['secondary_color'] ?? $secondaryColor;
        $newAccent = $_POST['accent_color'] ?? $accentColor;

        if (file_exists($cssFile)) {
            $css = file_get_contents($cssFile);
            $css = preg_replace('/(--color-primary:\s*)#[0-9a-fA-F]{6}/', '${1}' . $newPrimary, $css);
            $css = preg_replace('/(--color-secondary:\s*)#[0-9a-fA-F]{6}/', '${1}' . $newSecondary, $css);
            $css = preg_replace('/(--color-accent:\s*)#[0-9a-fA-F]{6}/', '${1}' . $newAccent, $css);

            // Update dark/light variants
            $primaryDark = adjustBrightness($newPrimary, -15);
            $primaryLight = adjustBrightness($newPrimary, 20);
            $secondaryDark = adjustBrightness($newSecondary, -15);

            $css = preg_replace('/(--color-primary-dark:\s*)#[0-9a-fA-F]{6}/', '${1}' . $primaryDark, $css);
            $css = preg_replace('/(--color-primary-light:\s*)#[0-9a-fA-F]{6}/', '${1}' . $primaryLight, $css);
            $css = preg_replace('/(--color-secondary-dark:\s*)#[0-9a-fA-F]{6}/', '${1}' . $secondaryDark, $css);

            file_put_contents($cssFile, $css, LOCK_EX);
            $success = 'Färger uppdaterade!';
            $primaryColor = $newPrimary;
            $secondaryColor = $newSecondary;
            $accentColor = $newAccent;
        }
    } elseif ($section === 'social') {
        // Update social media links
        $newFacebook = trim($_POST['social_facebook'] ?? '');
        $newInstagram = trim($_POST['social_instagram'] ?? '');
        $newLinkedin = trim($_POST['social_linkedin'] ?? '');

        $configPath = ROOT_PATH . '/config.php';
        if (file_exists($configPath)) {
            $config = file_get_contents($configPath);

            // Handle Facebook
            if (preg_match("/define\('SOCIAL_FACEBOOK'/", $config)) {
                $config = preg_replace("/define\('SOCIAL_FACEBOOK',\s*'[^']*'\);/", "define('SOCIAL_FACEBOOK', " . var_export($newFacebook, true) . ");", $config);
            } elseif (!empty($newFacebook)) {
                $config = preg_replace("/(\/\/ Environment)/", "// Sociala medier\ndefine('SOCIAL_FACEBOOK', " . var_export($newFacebook, true) . ");\n\n$1", $config);
            }

            // Handle Instagram
            if (preg_match("/define\('SOCIAL_INSTAGRAM'/", $config)) {
                $config = preg_replace("/define\('SOCIAL_INSTAGRAM',\s*'[^']*'\);/", "define('SOCIAL_INSTAGRAM', " . var_export($newInstagram, true) . ");", $config);
            } elseif (!empty($newInstagram)) {
                $config = preg_replace("/(\/\/ Environment)/", "define('SOCIAL_INSTAGRAM', " . var_export($newInstagram, true) . ");\n\n$1", $config);
            }

            // Handle LinkedIn
            if (preg_match("/define\('SOCIAL_LINKEDIN'/", $config)) {
                $config = preg_replace("/define\('SOCIAL_LINKEDIN',\s*'[^']*'\);/", "define('SOCIAL_LINKEDIN', " . var_export($newLinkedin, true) . ");", $config);
            } elseif (!empty($newLinkedin)) {
                $config = preg_replace("/(\/\/ Environment)/", "define('SOCIAL_LINKEDIN', " . var_export($newLinkedin, true) . ");\n\n$1", $config);
            }

            file_put_contents($configPath, $config, LOCK_EX);
            $success = 'Sociala medier uppdaterade!';
            $currentConfig['social_facebook'] = $newFacebook;
            $currentConfig['social_instagram'] = $newInstagram;
            $currentConfig['social_linkedin'] = $newLinkedin;
        }
    } elseif ($section === 'logos') {
        // Handle logo uploads - använd samma mönster som projects/new.php
        $imgDir = __DIR__ . '/../assets/images';

        if (!is_dir($imgDir)) {
            mkdir($imgDir, 0755, true);
        }

        $allowedMimes = ['image/png', 'image/jpeg', 'image/svg+xml', 'image/webp'];
        $maxSize = 5 * 1024 * 1024;
        $uploadErrors = [];
        $uploadedAny = false;

        foreach (['logo_dark', 'logo_light'] as $logoField) {
            // Kontrollera om fil valts
            if (!isset($_FILES[$logoField]) || $_FILES[$logoField]['error'] === UPLOAD_ERR_NO_FILE) {
                continue; // Ingen fil vald för detta fält, helt ok
            }

            // Hantera upload-fel
            $uploadErr = $_FILES[$logoField]['error'];
            if ($uploadErr !== UPLOAD_ERR_OK) {
                $errMsg = match($uploadErr) {
                    UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Filen är för stor (max 5MB)',
                    UPLOAD_ERR_PARTIAL => 'Uppladdningen avbröts',
                    UPLOAD_ERR_NO_TMP_DIR => 'Serverfel: Temp-mapp saknas',
                    UPLOAD_ERR_CANT_WRITE => 'Serverfel: Kan inte skriva till disk',
                    UPLOAD_ERR_EXTENSION => 'Uppladdning blockerad av server',
                    default => 'Uppladdningsfel (kod ' . $uploadErr . ')',
                };
                $uploadErrors[] = ucfirst(str_replace('_', ' ', $logoField)) . ': ' . $errMsg;
                continue;
            }

            // Validera MIME-typ
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($_FILES[$logoField]['tmp_name']);

            if (in_array($mime, $allowedMimes) && $_FILES[$logoField]['size'] <= $maxSize) {
                $ext = strtolower(pathinfo($_FILES[$logoField]['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, ['png', 'jpg', 'jpeg', 'svg', 'webp'])) {
                    $ext = 'png';
                }
                $destFile = str_replace('_', '-', $logoField) . '.' . $ext;
                $destPath = $imgDir . '/' . $destFile;

                // Ta bort gamla logofiler med andra ändelser
                foreach (['png', 'jpg', 'jpeg', 'svg', 'webp'] as $oldExt) {
                    $oldFile = $imgDir . '/' . str_replace('_', '-', $logoField) . '.' . $oldExt;
                    if (file_exists($oldFile) && $oldFile !== $destPath) {
                        @unlink($oldFile);
                    }
                }

                if (move_uploaded_file($_FILES[$logoField]['tmp_name'], $destPath)) {
                    $uploadedAny = true;
                } else {
                    $uploadErrors[] = ucfirst(str_replace('_', ' ', $logoField)) . ': Kunde inte spara till disk.';
                }
            } else {
                $uploadErrors[] = ucfirst(str_replace('_', ' ', $logoField)) . ': Ogiltigt format eller för stor (max 5MB).';
            }
        }

        if (!empty($uploadErrors)) {
            $error = implode(' ', $uploadErrors);
        } elseif ($uploadedAny) {
            $success = 'Logotyper uppdaterade!';
        } else {
            $error = 'Ingen fil vald. Välj minst en logotyp att ladda upp.';
        }
    } elseif ($section === 'delete_logo') {
        // Ta bort logotyp
        $logoType = $_POST['logo_type'] ?? '';
        if (in_array($logoType, ['logo-dark', 'logo-light'])) {
            $imgDir = __DIR__ . '/../assets/images';
            $deleted = false;
            foreach (['png', 'jpg', 'jpeg', 'svg', 'webp'] as $ext) {
                $file = $imgDir . '/' . $logoType . '.' . $ext;
                if (file_exists($file)) {
                    @unlink($file);
                    $deleted = true;
                }
            }
            $success = $deleted ? 'Logotyp borttagen!' : 'Ingen logotyp att ta bort.';
        }
    } elseif ($section === 'favicon') {
        // Handle favicon upload
        $imgDir = __DIR__ . '/../assets/images';

        if (!is_dir($imgDir)) {
            mkdir($imgDir, 0755, true);
        }

        // Kontrollera om fil valts
        if (!isset($_FILES['favicon']) || $_FILES['favicon']['error'] === UPLOAD_ERR_NO_FILE) {
            $error = 'Ingen fil vald.';
        } else {
            // Hantera upload-fel
            $uploadErr = $_FILES['favicon']['error'];
            if ($uploadErr !== UPLOAD_ERR_OK) {
                $error = match($uploadErr) {
                    UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Filen är för stor (max 1MB)',
                    UPLOAD_ERR_PARTIAL => 'Uppladdningen avbröts',
                    UPLOAD_ERR_NO_TMP_DIR => 'Serverfel: Temp-mapp saknas',
                    UPLOAD_ERR_CANT_WRITE => 'Serverfel: Kan inte skriva till disk',
                    UPLOAD_ERR_EXTENSION => 'Uppladdning blockerad av server',
                    default => 'Uppladdningsfel (kod ' . $uploadErr . ')',
                };
            } else {
                $faviconMimes = ['image/png', 'image/x-icon', 'image/vnd.microsoft.icon', 'image/svg+xml'];
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($_FILES['favicon']['tmp_name']);

                if (in_array($mime, $faviconMimes) && $_FILES['favicon']['size'] <= 1024 * 1024) {
                    $ext = strtolower(pathinfo($_FILES['favicon']['name'], PATHINFO_EXTENSION));

                    // Ta bort gamla favicon-filer
                    @unlink($imgDir . '/favicon.png');
                    @unlink($imgDir . '/favicon.ico');
                    @unlink($imgDir . '/favicon.svg');

                    if ($ext === 'ico') {
                        $destPath = $imgDir . '/favicon.ico';
                    } elseif ($ext === 'svg') {
                        $destPath = $imgDir . '/favicon.svg';
                    } else {
                        $destPath = $imgDir . '/favicon.png';
                    }

                    if (move_uploaded_file($_FILES['favicon']['tmp_name'], $destPath)) {
                        $success = 'Favicon uppdaterad!';
                    } else {
                        $error = 'Kunde inte spara favicon till disk.';
                    }
                } else {
                    $error = 'Favicon: Ogiltigt format eller för stor fil (max 1MB).';
                }
            }
        }
    } elseif ($section === 'delete_favicon') {
        // Ta bort favicon
        $imgDir = __DIR__ . '/../assets/images';
        @unlink($imgDir . '/favicon.png');
        @unlink($imgDir . '/favicon.ico');
        @unlink($imgDir . '/favicon.svg');
        $success = 'Favicon borttagen!';
    } elseif ($section === 'smtp') {
        // Update SMTP settings
        $smtpHost = trim($_POST['smtp_host'] ?? '');
        $smtpPort = (int)($_POST['smtp_port'] ?? 465);
        $smtpEncryption = $_POST['smtp_encryption'] ?? 'ssl';
        $smtpUsername = trim($_POST['smtp_username'] ?? '');
        $smtpPassword = $_POST['smtp_password'] ?? '';

        // Validate
        if (!empty($smtpHost)) {
            if ($smtpPort < 1 || $smtpPort > 65535) {
                $error = 'Ange en giltig SMTP-port (1-65535).';
            } elseif (empty($smtpUsername)) {
                $error = 'SMTP-användarnamn krävs.';
            } elseif (empty($smtpPassword) && !defined('SMTP_PASSWORD')) {
                $error = 'SMTP-lösenord krävs.';
            } elseif (!in_array($smtpEncryption, ['ssl', 'tls'])) {
                $error = 'Ogiltig kryptering.';
            }
        }

        if (empty($error)) {
            $configPath = ROOT_PATH . '/config.php';
            if (file_exists($configPath)) {
                $config = file_get_contents($configPath);

                // Check if SMTP section exists
                if (preg_match("/define\('SMTP_HOST'/", $config)) {
                    // Update existing
                    $config = preg_replace("/define\('SMTP_HOST',\s*'[^']*'\);/", "define('SMTP_HOST', " . var_export($smtpHost, true) . ");", $config);
                    $config = preg_replace("/define\('SMTP_PORT',\s*\d+\);/", "define('SMTP_PORT', " . $smtpPort . ");", $config);
                    $config = preg_replace("/define\('SMTP_ENCRYPTION',\s*'[^']*'\);/", "define('SMTP_ENCRYPTION', " . var_export($smtpEncryption, true) . ");", $config);
                    $config = preg_replace("/define\('SMTP_USERNAME',\s*'[^']*'\);/", "define('SMTP_USERNAME', " . var_export($smtpUsername, true) . ");", $config);
                    if (!empty($smtpPassword)) {
                        $config = preg_replace("/define\('SMTP_PASSWORD',\s*'[^']*'\);/", "define('SMTP_PASSWORD', " . var_export($smtpPassword, true) . ");", $config);
                    }
                } elseif (!empty($smtpHost)) {
                    // Add new SMTP section
                    $smtpBlock = "\n// SMTP\n";
                    $smtpBlock .= "define('SMTP_HOST', " . var_export($smtpHost, true) . ");\n";
                    $smtpBlock .= "define('SMTP_PORT', " . $smtpPort . ");\n";
                    $smtpBlock .= "define('SMTP_ENCRYPTION', " . var_export($smtpEncryption, true) . ");\n";
                    $smtpBlock .= "define('SMTP_USERNAME', " . var_export($smtpUsername, true) . ");\n";
                    $smtpBlock .= "define('SMTP_PASSWORD', " . var_export($smtpPassword, true) . ");\n";
                    $config = preg_replace("/(\/\/ Environment)/", $smtpBlock . "\n$1", $config);
                }

                file_put_contents($configPath, $config, LOCK_EX);
                $success = 'SMTP-inställningar uppdaterade!';
                $currentConfig['smtp_host'] = $smtpHost;
                $currentConfig['smtp_port'] = $smtpPort;
                $currentConfig['smtp_encryption'] = $smtpEncryption;
                $currentConfig['smtp_username'] = $smtpUsername;
            }
        }
    }
}

/**
 * Justera ljusstyrka på en hex-färg
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
        $r = $r + (255 - $r) * $percent / 100;
        $g = $g + (255 - $g) * $percent / 100;
        $b = $b + (255 - $b) * $percent / 100;
    } else {
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
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inställningar - CMS</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #fafafa;
            min-height: 100vh;
        }
        .page-content {
            padding: 3rem 1.5rem;
        }
        .container {
            max-width: 36rem;
            margin: 0 auto;
        }
        .back-link {
            display: inline-block;
            color: #737373;
            text-decoration: none;
            font-size: 0.875rem;
            margin-bottom: 1rem;
            transition: color 0.2s;
        }
        .back-link:hover { color: #18181b; }
        .title {
            font-size: 2rem;
            font-weight: bold;
            color: #18181b;
            margin-bottom: 0.5rem;
        }
        .subtitle {
            font-size: 1rem;
            color: #737373;
            margin-bottom: 2rem;
        }
        .card {
            background: white;
            border-radius: 1rem;
            border: 1px solid #e5e5e5;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .card-title {
            font-size: 1rem;
            font-weight: 700;
            color: #18181b;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid #f5f5f5;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: #18181b;
            margin-bottom: 0.375rem;
        }
        .form-input, .form-textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #d4d4d4;
            border-radius: 0.5rem;
            font-size: 1rem;
            font-family: inherit;
            outline: none;
            transition: all 0.2s;
        }
        .form-input:focus, .form-textarea:focus {
            border-color: #fe4f2a;
            box-shadow: 0 0 0 3px rgba(254, 79, 42, 0.1);
        }
        .form-textarea {
            resize: vertical;
            min-height: 80px;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        .color-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .color-group input[type="color"] {
            width: 48px;
            height: 40px;
            padding: 2px;
            border: 1px solid #d4d4d4;
            border-radius: 0.5rem;
            cursor: pointer;
        }
        .color-group input[type="text"] {
            flex: 1;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1.5rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
        }
        .btn-primary {
            background: #fe4f2a;
            color: white;
        }
        .btn-primary:hover {
            background: #e8461f;
        }
        .btn-danger {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }
        .btn-danger:hover {
            background: #fee2e2;
        }
        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.75rem;
        }
        .alert {
            padding: 0.875rem 1rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            margin-bottom: 1.5rem;
        }
        .alert-success {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #166534;
        }
        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
        }
        .file-upload {
            border: 2px dashed #d4d4d4;
            border-radius: 0.5rem;
            padding: 1rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
        }
        .file-upload:hover {
            border-color: #fe4f2a;
            background: #fff7ed;
        }
        .file-upload input[type="file"] {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
        }
        .file-upload-text {
            font-size: 0.8125rem;
            color: #737373;
        }
        .file-upload-text strong {
            color: #fe4f2a;
        }
        .current-logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.5rem;
            padding: 0.5rem;
            background: #f5f5f5;
            border-radius: 0.5rem;
        }
        .current-logo img {
            height: 32px;
            width: auto;
        }
        .current-logo span {
            font-size: 0.75rem;
            color: #737373;
        }
        .hint {
            font-size: 0.75rem;
            color: #a3a3a3;
            margin-top: 0.25rem;
        }
        .logo-preview {
            display: none;
            max-height: 60px;
            max-width: 100%;
            margin-top: 0.5rem;
            border-radius: 0.25rem;
            border: 1px solid #e5e5e5;
            padding: 0.25rem;
            background: #f5f5f5;
        }
        .logo-preview.dark-bg {
            background: #333;
        }
        .file-selected {
            border-color: #22c55e !important;
            background: #f0fdf4 !important;
        }
        .file-selected .file-upload-text {
            color: #166534;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/admin-bar.php'; ?>
    <div class="page-content">
        <div class="container">
            <a href="/dashboard" class="back-link">&larr; Tillbaka till dashboard</a>

            <h1 class="title">Inställningar</h1>
            <p class="subtitle">Hantera webbplatsens konfiguration</p>

            <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <!-- Site Info -->
            <div class="card">
                <h2 class="card-title">Företagsinformation</h2>
                <form method="POST">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="section" value="site_info">

                    <div class="form-group">
                        <label class="form-label" for="site_name">Företagsnamn</label>
                        <input type="text" id="site_name" name="site_name" class="form-input"
                               value="<?php echo htmlspecialchars($currentConfig['site_name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="site_description">Beskrivning</label>
                        <textarea id="site_description" name="site_description" class="form-textarea"><?php echo htmlspecialchars($currentConfig['site_description']); ?></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="contact_email">E-post</label>
                            <input type="email" id="contact_email" name="contact_email" class="form-input"
                                   value="<?php echo htmlspecialchars($currentConfig['contact_email']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="contact_phone">Telefon</label>
                            <input type="tel" id="contact_phone" name="contact_phone" class="form-input"
                                   value="<?php echo htmlspecialchars($currentConfig['contact_phone']); ?>">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">Spara</button>
                </form>
            </div>

            <!-- Colors -->
            <div class="card">
                <h2 class="card-title">Färger</h2>
                <form method="POST">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="section" value="colors">

                    <div class="form-row" style="grid-template-columns: 1fr 1fr 1fr;">
                        <div class="form-group">
                            <label class="form-label">Primärfärg</label>
                            <div class="color-group">
                                <input type="color" id="primary_color" name="primary_color"
                                       value="<?php echo htmlspecialchars($primaryColor); ?>"
                                       onchange="document.getElementById('primary_text').value = this.value">
                                <input type="text" id="primary_text" class="form-input"
                                       value="<?php echo htmlspecialchars($primaryColor); ?>"
                                       onchange="document.getElementById('primary_color').value = this.value"
                                       pattern="#[0-9a-fA-F]{6}" maxlength="7">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Sekundärfärg</label>
                            <div class="color-group">
                                <input type="color" id="secondary_color" name="secondary_color"
                                       value="<?php echo htmlspecialchars($secondaryColor); ?>"
                                       onchange="document.getElementById('secondary_text').value = this.value">
                                <input type="text" id="secondary_text" class="form-input"
                                       value="<?php echo htmlspecialchars($secondaryColor); ?>"
                                       onchange="document.getElementById('secondary_color').value = this.value"
                                       pattern="#[0-9a-fA-F]{6}" maxlength="7">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Accentfärg</label>
                            <div class="color-group">
                                <input type="color" id="accent_color" name="accent_color"
                                       value="<?php echo htmlspecialchars($accentColor); ?>"
                                       onchange="document.getElementById('accent_text').value = this.value">
                                <input type="text" id="accent_text" class="form-input"
                                       value="<?php echo htmlspecialchars($accentColor); ?>"
                                       onchange="document.getElementById('accent_color').value = this.value"
                                       pattern="#[0-9a-fA-F]{6}" maxlength="7">
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">Spara</button>
                </form>
            </div>

            <!-- Logos -->
            <div class="card">
                <h2 class="card-title">Logotyper</h2>
                <form method="POST" enctype="multipart/form-data">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="section" value="logos">

                    <?php
                    // Hitta logotypfiler oavsett filändelse
                    $imgBase = __DIR__ . '/../assets/images';
                    function findLogoFile($baseName, $imgBase) {
                        $extensions = ['png', 'jpg', 'jpeg', 'svg', 'webp'];
                        foreach ($extensions as $ext) {
                            $path = $imgBase . '/' . $baseName . '.' . $ext;
                            if (file_exists($path)) {
                                return '/assets/images/' . $baseName . '.' . $ext;
                            }
                        }
                        return null;
                    }
                    $logoDarkPath = findLogoFile('logo-dark', $imgBase);
                    $logoLightPath = findLogoFile('logo-light', $imgBase);
                    ?>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Mörk logotyp</label>
                            <p class="hint">För ljusa bakgrunder</p>
                            <?php if ($logoDarkPath): ?>
                            <div class="current-logo">
                                <img src="<?php echo $logoDarkPath; ?>?v=<?php echo time(); ?>" alt="Nuvarande">
                                <span>Nuvarande</span>
                            </div>
                            <?php endif; ?>
                            <div class="file-upload" id="upload-dark">
                                <input type="file" name="logo_dark" accept=".png,.jpg,.jpeg,.svg,.webp" onchange="previewLogo(this, 'preview-dark', 'upload-dark')">
                                <div class="file-upload-text">
                                    <strong>Välj fil</strong> eller dra hit<br>
                                    <small>PNG, JPG, SVG, WebP</small>
                                </div>
                            </div>
                            <img class="logo-preview" id="preview-dark" alt="Förhandsgranskning">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Ljus logotyp</label>
                            <p class="hint">För mörka bakgrunder</p>
                            <?php if ($logoLightPath): ?>
                            <div class="current-logo">
                                <img src="<?php echo $logoLightPath; ?>?v=<?php echo time(); ?>" alt="Nuvarande" style="background: #333; padding: 4px; border-radius: 4px;">
                                <span>Nuvarande</span>
                            </div>
                            <?php endif; ?>
                            <div class="file-upload" id="upload-light">
                                <input type="file" name="logo_light" accept=".png,.jpg,.jpeg,.svg,.webp" onchange="previewLogo(this, 'preview-light', 'upload-light')">
                                <div class="file-upload-text">
                                    <strong>Välj fil</strong> eller dra hit<br>
                                    <small>PNG, JPG, SVG, WebP</small>
                                </div>
                            </div>
                            <img class="logo-preview dark-bg" id="preview-light" alt="Förhandsgranskning">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">Ladda upp</button>
                </form>

                <?php if ($logoDarkPath || $logoLightPath): ?>
                <div style="display: flex; gap: 1rem; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e5e5e5;">
                    <?php if ($logoDarkPath): ?>
                    <form method="POST">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="section" value="delete_logo">
                        <input type="hidden" name="logo_type" value="logo-dark">
                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Ta bort mörk logotyp?')">Ta bort mörk</button>
                    </form>
                    <?php endif; ?>
                    <?php if ($logoLightPath): ?>
                    <form method="POST">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="section" value="delete_logo">
                        <input type="hidden" name="logo_type" value="logo-light">
                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Ta bort ljus logotyp?')">Ta bort ljus</button>
                    </form>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Favicon -->
            <div class="card">
                <h2 class="card-title">Favicon</h2>
                <form method="POST" enctype="multipart/form-data">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="section" value="favicon">

                    <div class="form-group">
                        <label class="form-label">Favicon</label>
                        <p class="hint">Liten ikon som visas i webbläsarfliken</p>
                        <?php
                        $imgDirCheck = __DIR__ . '/../assets/images';
                        $hasFavicon = file_exists($imgDirCheck . '/favicon.png') || file_exists($imgDirCheck . '/favicon.ico') || file_exists($imgDirCheck . '/favicon.svg');
                        $faviconPath = file_exists($imgDirCheck . '/favicon.ico') ? '/assets/images/favicon.ico' : (file_exists($imgDirCheck . '/favicon.svg') ? '/assets/images/favicon.svg' : '/assets/images/favicon.png');
                        ?>
                        <?php if ($hasFavicon): ?>
                        <div class="current-logo">
                            <img src="<?php echo $faviconPath; ?>?v=<?php echo time(); ?>" alt="Nuvarande favicon" style="width: 32px; height: 32px;">
                            <span>Nuvarande favicon</span>
                        </div>
                        <?php endif; ?>
                        <div class="file-upload" id="upload-favicon">
                            <input type="file" name="favicon" accept=".png,.ico,.svg" onchange="previewFavicon(this)">
                            <div class="file-upload-text">
                                <strong>Välj fil</strong> eller dra hit<br>
                                <small>PNG, ICO, SVG (max 1MB, rekommenderat 32x32px)</small>
                            </div>
                        </div>
                        <img class="logo-preview" id="preview-favicon" alt="Förhandsgranskning" style="max-height: 32px;">
                    </div>

                    <button type="submit" class="btn btn-primary">Ladda upp</button>
                </form>

                <?php if ($hasFavicon): ?>
                <form method="POST" style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e5e5e5;">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="section" value="delete_favicon">
                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Ta bort favicon?')">Ta bort favicon</button>
                </form>
                <?php endif; ?>
            </div>

            <!-- SMTP -->
            <div class="card">
                <h2 class="card-title">E-post (SMTP)</h2>
                <form method="POST">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="section" value="smtp">

                    <div class="form-group">
                        <label class="form-label" for="smtp_host">SMTP-server</label>
                        <input type="text" id="smtp_host" name="smtp_host" class="form-input"
                               value="<?php echo htmlspecialchars($currentConfig['smtp_host']); ?>"
                               placeholder="t.ex. smtp.gmail.com">
                        <p class="hint">Lämna tomt för att inaktivera SMTP</p>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="smtp_port">Port</label>
                            <input type="number" id="smtp_port" name="smtp_port" class="form-input"
                                   value="<?php echo (int)$currentConfig['smtp_port']; ?>"
                                   min="1" max="65535">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="smtp_encryption">Kryptering</label>
                            <select id="smtp_encryption" name="smtp_encryption" class="form-input">
                                <option value="ssl" <?php echo $currentConfig['smtp_encryption'] === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                <option value="tls" <?php echo $currentConfig['smtp_encryption'] === 'tls' ? 'selected' : ''; ?>>TLS</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="smtp_username">Användarnamn</label>
                        <input type="text" id="smtp_username" name="smtp_username" class="form-input"
                               value="<?php echo htmlspecialchars($currentConfig['smtp_username']); ?>"
                               placeholder="din@email.com">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="smtp_password">Lösenord</label>
                        <input type="password" id="smtp_password" name="smtp_password" class="form-input"
                               placeholder="<?php echo defined('SMTP_PASSWORD') ? '••••••••' : ''; ?>">
                        <p class="hint"><?php echo defined('SMTP_PASSWORD') ? 'Lämna tomt för att behålla nuvarande lösenord' : 'Ange SMTP-lösenord'; ?></p>
                    </div>

                    <button type="submit" class="btn btn-primary">Spara</button>
                </form>
            </div>

            <!-- Social Media -->
            <div class="card">
                <h2 class="card-title">Sociala medier</h2>
                <form method="POST">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="section" value="social">

                    <div class="form-group">
                        <label class="form-label" for="social_facebook">Facebook</label>
                        <input type="url" id="social_facebook" name="social_facebook" class="form-input"
                               value="<?php echo htmlspecialchars($currentConfig['social_facebook']); ?>"
                               placeholder="https://facebook.com/...">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="social_instagram">Instagram</label>
                        <input type="url" id="social_instagram" name="social_instagram" class="form-input"
                               value="<?php echo htmlspecialchars($currentConfig['social_instagram']); ?>"
                               placeholder="https://instagram.com/...">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="social_linkedin">LinkedIn</label>
                        <input type="url" id="social_linkedin" name="social_linkedin" class="form-input"
                               value="<?php echo htmlspecialchars($currentConfig['social_linkedin']); ?>"
                               placeholder="https://linkedin.com/...">
                    </div>

                    <button type="submit" class="btn btn-primary">Spara</button>
                </form>
            </div>

        </div>
    </div>

    <script>
    // Förhandsvisning av logotyp
    function previewLogo(input, previewId, uploadId) {
        const preview = document.getElementById(previewId);
        const uploadBox = document.getElementById(uploadId);

        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
                uploadBox.classList.add('file-selected');
            };
            reader.readAsDataURL(input.files[0]);
        } else {
            preview.style.display = 'none';
            uploadBox.classList.remove('file-selected');
        }
    }

    // Förhandsvisning av favicon
    function previewFavicon(input) {
        const preview = document.getElementById('preview-favicon');
        const uploadBox = document.getElementById('upload-favicon');

        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
                uploadBox.classList.add('file-selected');
            };
            reader.readAsDataURL(input.files[0]);
        } else {
            preview.style.display = 'none';
            uploadBox.classList.remove('file-selected');
        }
    }
    </script>
</body>
</html>
