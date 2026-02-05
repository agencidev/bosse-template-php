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
];

// Read colors from variables.css
$cssFile = ROOT_PATH . '/assets/css/variables.css';
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
    csrf_require();

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
        // Handle logo uploads
        $imgDir = ROOT_PATH . '/assets/images';
        $allowedMimes = ['image/png', 'image/jpeg', 'image/svg+xml', 'image/webp'];
        $maxSize = 5 * 1024 * 1024;
        $uploadErrors = [];

        foreach (['logo_dark', 'logo_light'] as $logoField) {
            if (isset($_FILES[$logoField]) && $_FILES[$logoField]['error'] === UPLOAD_ERR_OK) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $_FILES[$logoField]['tmp_name']);
                finfo_close($finfo);

                if (!in_array($mime, $allowedMimes)) {
                    $uploadErrors[] = ucfirst(str_replace('_', ' ', $logoField)) . ': Ogiltigt filformat.';
                } elseif ($_FILES[$logoField]['size'] > $maxSize) {
                    $uploadErrors[] = ucfirst(str_replace('_', ' ', $logoField)) . ': Filen är för stor (max 5MB).';
                } else {
                    $dest = $imgDir . '/' . str_replace('_', '-', $logoField) . '.png';
                    move_uploaded_file($_FILES[$logoField]['tmp_name'], $dest);
                }
            }
        }

        if (!empty($uploadErrors)) {
            $error = implode(' ', $uploadErrors);
        } else {
            $success = 'Logotyper uppdaterade!';
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

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Mörk logotyp</label>
                            <p class="hint">För ljusa bakgrunder</p>
                            <?php if (file_exists(ROOT_PATH . '/assets/images/logo-dark.png')): ?>
                            <div class="current-logo">
                                <img src="/assets/images/logo-dark.png?v=<?php echo time(); ?>" alt="Nuvarande">
                                <span>Nuvarande</span>
                            </div>
                            <?php endif; ?>
                            <div class="file-upload">
                                <input type="file" name="logo_dark" accept=".png,.jpg,.jpeg,.svg,.webp">
                                <div class="file-upload-text">
                                    <strong>Välj fil</strong><br>
                                    <small>PNG, JPG, SVG, WebP</small>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Ljus logotyp</label>
                            <p class="hint">För mörka bakgrunder</p>
                            <?php if (file_exists(ROOT_PATH . '/assets/images/logo-light.png')): ?>
                            <div class="current-logo">
                                <img src="/assets/images/logo-light.png?v=<?php echo time(); ?>" alt="Nuvarande" style="background: #333; padding: 4px; border-radius: 4px;">
                                <span>Nuvarande</span>
                            </div>
                            <?php endif; ?>
                            <div class="file-upload">
                                <input type="file" name="logo_light" accept=".png,.jpg,.jpeg,.svg,.webp">
                                <div class="file-upload-text">
                                    <strong>Välj fil</strong><br>
                                    <small>PNG, JPG, SVG, WebP</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">Ladda upp</button>
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
</body>
</html>
