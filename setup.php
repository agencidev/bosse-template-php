<?php
/**
 * Setup Wizard - 3-stegs konfigurationsguide
 * Genererar config.php, variabler, innehåll och AI-filer
 */

// Guard: blockera om redan konfigurerad
if (file_exists(__DIR__ . '/config.php') || file_exists(__DIR__ . '/data/.setup-complete')) {
    header('Location: /');
    exit;
}

// Session (duplicerar bootstrap.php-inställningar)
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0); // 0 för localhost under setup
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', 1);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// CSRF-token
if (empty($_SESSION['setup_csrf'])) {
    $_SESSION['setup_csrf'] = bin2hex(random_bytes(32));
}

// Felmeddelanden
$errors = [];
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$step = max(1, min(3, $step));

// Pre-flight: kontrollera skrivbehörigheter
$writableCheck = [
    __DIR__ . '/config.php' => __DIR__,
    __DIR__ . '/assets/css/variables.css' => __DIR__ . '/assets/css',
    __DIR__ . '/assets/images/' => __DIR__ . '/assets/images',
    __DIR__ . '/data/' => __DIR__ . '/data',
    __DIR__ . '/.windsurf/' => __DIR__ . '/.windsurf',
    __DIR__ . '/includes/' => __DIR__ . '/includes',
];
$writableErrors = [];
foreach ($writableCheck as $label => $path) {
    if (is_dir($path) && !is_writable($path)) {
        $writableErrors[] = $path;
    } elseif (is_file($path) && !is_writable($path)) {
        $writableErrors[] = $path;
    }
}

// Spara stegdata i session
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verifiera CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['setup_csrf'], $_POST['csrf_token'])) {
        $errors[] = 'Ogiltig CSRF-token. Ladda om sidan.';
    } else {
        $postStep = (int)($_POST['step'] ?? 1);

        if ($postStep === 1) {
            // Validera steg 1
            $site_name = trim($_POST['site_name'] ?? '');
            $contact_email = trim($_POST['contact_email'] ?? '');
            $contact_phone = trim($_POST['contact_phone'] ?? '');
            $site_url = trim($_POST['site_url'] ?? '');
            $site_description = trim($_POST['site_description'] ?? '');

            // SMTP-fält
            $smtp_host = trim($_POST['smtp_host'] ?? '');
            $smtp_port = (int)($_POST['smtp_port'] ?? 465);
            $smtp_encryption = $_POST['smtp_encryption'] ?? 'ssl';
            $smtp_username = trim($_POST['smtp_username'] ?? '');
            $smtp_password = $_POST['smtp_password'] ?? '';

            // Google Analytics
            $ga_id = trim($_POST['ga_id'] ?? '');

            // Sociala medier
            $social_facebook = trim($_POST['social_facebook'] ?? '');
            $social_instagram = trim($_POST['social_instagram'] ?? '');
            $social_linkedin = trim($_POST['social_linkedin'] ?? '');

            // Öppettider
            $hours_weekdays = trim($_POST['hours_weekdays'] ?? '');
            $hours_weekends = trim($_POST['hours_weekends'] ?? '');

            if (empty($site_name)) $errors[] = 'Företagsnamn krävs.';
            if (empty($contact_email) || !filter_var($contact_email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Giltig e-postadress krävs.';
            if (empty($site_url) || !filter_var($site_url, FILTER_VALIDATE_URL)) $errors[] = 'Giltig webbadress krävs.';
            if (empty($site_description)) $errors[] = 'Beskrivning krävs.';

            // SMTP-validering: om host anges måste port+user+pass också fyllas i
            if (!empty($smtp_host)) {
                if ($smtp_port < 1 || $smtp_port > 65535) $errors[] = 'Ange en giltig SMTP-port (1-65535).';
                if (empty($smtp_username)) $errors[] = 'SMTP-användarnamn krävs om SMTP-server anges.';
                if (empty($smtp_password)) $errors[] = 'SMTP-lösenord krävs om SMTP-server anges.';
                if (!in_array($smtp_encryption, ['ssl', 'tls'])) $smtp_encryption = 'ssl';
            }

            // GA-validering (valfritt, men om angivet måste det börja med G-)
            if (!empty($ga_id) && !preg_match('/^G-[A-Z0-9]+$/', $ga_id)) {
                $errors[] = 'Google Analytics ID måste ha formatet G-XXXXXXXXX.';
            }

            // URL-validering sociala medier (valfria)
            foreach (['social_facebook' => $social_facebook, 'social_instagram' => $social_instagram, 'social_linkedin' => $social_linkedin] as $name => $url) {
                if (!empty($url) && !filter_var($url, FILTER_VALIDATE_URL)) {
                    $labels = ['social_facebook' => 'Facebook', 'social_instagram' => 'Instagram', 'social_linkedin' => 'LinkedIn'];
                    $errors[] = $labels[$name] . '-URL måste vara en giltig webbadress.';
                }
            }

            if (empty($errors)) {
                $_SESSION['setup_data'] = array_merge($_SESSION['setup_data'] ?? [], [
                    'site_name' => $site_name,
                    'contact_email' => $contact_email,
                    'contact_phone' => $contact_phone,
                    'site_url' => rtrim($site_url, '/'),
                    'site_description' => $site_description,
                    'smtp_host' => $smtp_host,
                    'smtp_port' => $smtp_port,
                    'smtp_encryption' => $smtp_encryption,
                    'smtp_username' => $smtp_username,
                    'smtp_password' => $smtp_password,
                    'ga_id' => $ga_id,
                    'social_facebook' => $social_facebook,
                    'social_instagram' => $social_instagram,
                    'social_linkedin' => $social_linkedin,
                    'hours_weekdays' => $hours_weekdays,
                    'hours_weekends' => $hours_weekends,
                ]);
                header('Location: /setup?step=2');
                exit;
            }
            $step = 1;
        } elseif ($postStep === 2) {
            // Validera steg 2
            $primary_color = $_POST['primary_color'] ?? '#8b5cf6';
            $secondary_color = $_POST['secondary_color'] ?? '#FF6B35';
            $accent_color = $_POST['accent_color'] ?? '#fe4f2a';
            $font_heading = $_POST['font_heading'] ?? 'System UI';
            $font_body = $_POST['font_body'] ?? 'System UI';

            $allowedFonts = ['System UI', 'Inter', 'Poppins', 'Playfair Display', 'Roboto', 'DM Sans', 'Montserrat', 'Lora'];
            if (!in_array($font_heading, $allowedFonts)) $font_heading = 'System UI';
            if (!in_array($font_body, $allowedFonts)) $font_body = 'System UI';

            // Nya brand guide-fält
            $border_radius = $_POST['border_radius'] ?? 'rounded';
            $button_style = $_POST['button_style'] ?? 'filled';
            $tonality = $_POST['tonality'] ?? 'professional';
            $layout_width = $_POST['layout_width'] ?? 'normal';
            $bg_pattern = $_POST['bg_pattern'] ?? 'solid';
            $spacing_feel = $_POST['spacing_feel'] ?? 'normal';

            $allowedRadius = ['sharp', 'rounded', 'soft', 'pill'];
            $allowedButtonStyle = ['filled', 'outline', 'ghost'];
            $allowedTonality = ['professional', 'playful', 'minimal'];
            $allowedLayoutWidth = ['narrow', 'normal', 'wide'];
            $allowedBgPattern = ['solid', 'gradient', 'subtle'];
            $allowedSpacingFeel = ['compact', 'normal', 'airy'];

            if (!in_array($border_radius, $allowedRadius)) $border_radius = 'rounded';
            if (!in_array($button_style, $allowedButtonStyle)) $button_style = 'filled';
            if (!in_array($tonality, $allowedTonality)) $tonality = 'professional';
            if (!in_array($layout_width, $allowedLayoutWidth)) $layout_width = 'normal';
            if (!in_array($bg_pattern, $allowedBgPattern)) $bg_pattern = 'solid';
            if (!in_array($spacing_feel, $allowedSpacingFeel)) $spacing_feel = 'normal';

            // Logo uploads
            $logoErrors = [];
            $allowedMimes = ['image/png', 'image/jpeg', 'image/svg+xml', 'image/webp'];
            $maxSize = 5 * 1024 * 1024; // 5MB

            foreach (['logo_dark', 'logo_light'] as $logoField) {
                if (isset($_FILES[$logoField]) && $_FILES[$logoField]['error'] === UPLOAD_ERR_OK) {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime = finfo_file($finfo, $_FILES[$logoField]['tmp_name']);
                    finfo_close($finfo);

                    if (!in_array($mime, $allowedMimes)) {
                        $logoErrors[] = ucfirst(str_replace('_', ' ', $logoField)) . ': Ogiltigt filformat. Tillåtna: PNG, JPG, SVG, WebP.';
                    } elseif ($_FILES[$logoField]['size'] > $maxSize) {
                        $logoErrors[] = ucfirst(str_replace('_', ' ', $logoField)) . ': Filen är för stor (max 5MB).';
                    }
                }
            }

            if (!empty($logoErrors)) {
                $errors = array_merge($errors, $logoErrors);
            }

            if (empty($errors)) {
                // Spara logotyper
                $imgDir = __DIR__ . '/assets/images';
                foreach (['logo_dark', 'logo_light'] as $logoField) {
                    if (isset($_FILES[$logoField]) && $_FILES[$logoField]['error'] === UPLOAD_ERR_OK) {
                        $dest = $imgDir . '/' . str_replace('_', '-', $logoField) . '.png';
                        move_uploaded_file($_FILES[$logoField]['tmp_name'], $dest);
                    }
                }

                $_SESSION['setup_data'] = array_merge($_SESSION['setup_data'] ?? [], [
                    'primary_color' => $primary_color,
                    'secondary_color' => $secondary_color,
                    'accent_color' => $accent_color,
                    'font_heading' => $font_heading,
                    'font_body' => $font_body,
                    'border_radius' => $border_radius,
                    'button_style' => $button_style,
                    'tonality' => $tonality,
                    'layout_width' => $layout_width,
                    'bg_pattern' => $bg_pattern,
                    'spacing_feel' => $spacing_feel,
                ]);
                header('Location: /setup?step=3');
                exit;
            }
            $step = 2;
        } elseif ($postStep === 3) {
            // Validera steg 3
            $admin_username = trim($_POST['admin_username'] ?? '');
            $admin_password = $_POST['admin_password'] ?? '';
            $admin_password_confirm = $_POST['admin_password_confirm'] ?? '';

            if (strlen($admin_username) < 3) $errors[] = 'Användarnamn måste vara minst 3 tecken.';
            if (strlen($admin_password) < 8) $errors[] = 'Lösenord måste vara minst 8 tecken.';
            if ($admin_password !== $admin_password_confirm) $errors[] = 'Lösenorden matchar inte.';

            if (empty($errors)) {
                $data = $_SESSION['setup_data'] ?? [];
                $data['admin_username'] = $admin_username;
                $data['admin_password'] = $admin_password;

                // === GENERERA ALLA FILER ===
                $genErrors = generateAllFiles($data);
                if (!empty($genErrors)) {
                    $errors = $genErrors;
                    $step = 3;
                } else {
                    // Rensa session-data
                    unset($_SESSION['setup_data']);
                    $step = 4; // Färdig-sida
                }
            } else {
                $step = 3;
            }
        }
    }
}

/**
 * Generera alla konfigurationsfiler
 */
function generateAllFiles(array $data): array {
    $errors = [];

    // 1. config.php
    $sessionSecret = bin2hex(random_bytes(32));
    $csrfSalt = bin2hex(random_bytes(32));
    $passwordHash = password_hash($data['admin_password'], PASSWORD_BCRYPT);

    $configContent = "<?php\n";
    $configContent .= "/**\n * Konfiguration - Genererad av Setup Wizard\n * " . date('Y-m-d H:i:s') . "\n */\n\n";
    $configContent .= "// Site\n";
    $configContent .= "define('SITE_URL', " . var_export($data['site_url'], true) . ");\n";
    $configContent .= "define('SITE_NAME', " . var_export($data['site_name'], true) . ");\n";
    $configContent .= "define('SITE_DESCRIPTION', " . var_export($data['site_description'], true) . ");\n";
    $configContent .= "define('CONTACT_EMAIL', " . var_export($data['contact_email'], true) . ");\n";
    $configContent .= "define('CONTACT_PHONE', " . var_export($data['contact_phone'], true) . ");\n\n";
    $configContent .= "// Admin\n";
    $configContent .= "define('ADMIN_USERNAME', " . var_export($data['admin_username'], true) . ");\n";
    $configContent .= "define('ADMIN_PASSWORD_HASH', " . var_export($passwordHash, true) . ");\n\n";
    $configContent .= "// Security\n";
    $configContent .= "define('SESSION_SECRET', " . var_export($sessionSecret, true) . ");\n";
    $configContent .= "define('CSRF_TOKEN_SALT', " . var_export($csrfSalt, true) . ");\n\n";
    // SMTP (om konfigurerat)
    if (!empty($data['smtp_host'])) {
        $configContent .= "// SMTP\n";
        $configContent .= "define('SMTP_HOST', " . var_export($data['smtp_host'], true) . ");\n";
        $configContent .= "define('SMTP_PORT', " . (int)$data['smtp_port'] . ");\n";
        $configContent .= "define('SMTP_ENCRYPTION', " . var_export($data['smtp_encryption'], true) . ");\n";
        $configContent .= "define('SMTP_USERNAME', " . var_export($data['smtp_username'], true) . ");\n";
        $configContent .= "define('SMTP_PASSWORD', " . var_export($data['smtp_password'], true) . ");\n\n";
    }

    // Google Analytics (om konfigurerat)
    if (!empty($data['ga_id'])) {
        $configContent .= "// Google Analytics\n";
        $configContent .= "define('GOOGLE_ANALYTICS_ID', " . var_export($data['ga_id'], true) . ");\n\n";
    }

    // Sociala medier (om konfigurerat)
    $hasSocial = !empty($data['social_facebook']) || !empty($data['social_instagram']) || !empty($data['social_linkedin']);
    if ($hasSocial) {
        $configContent .= "// Sociala medier\n";
        if (!empty($data['social_facebook'])) $configContent .= "define('SOCIAL_FACEBOOK', " . var_export($data['social_facebook'], true) . ");\n";
        if (!empty($data['social_instagram'])) $configContent .= "define('SOCIAL_INSTAGRAM', " . var_export($data['social_instagram'], true) . ");\n";
        if (!empty($data['social_linkedin'])) $configContent .= "define('SOCIAL_LINKEDIN', " . var_export($data['social_linkedin'], true) . ");\n";
        $configContent .= "\n";
    }

    // Öppettider (om konfigurerat)
    if (!empty($data['hours_weekdays'])) {
        $configContent .= "// Öppettider\n";
        $configContent .= "define('HOURS_WEEKDAYS', " . var_export($data['hours_weekdays'], true) . ");\n";
        $configContent .= "define('HOURS_WEEKENDS', " . var_export($data['hours_weekends'] ?? 'Stängt', true) . ");\n\n";
    }

    $configContent .= "// Environment\n";
    $configContent .= "define('ENVIRONMENT', 'development');\n\n";

    // Agenci Super Admin
    $saToken = bin2hex(random_bytes(32)); // 64 tecken
    $configContent .= "// Agenci\n";
    $configContent .= "define('AGENCI_SUPER_ADMIN_TOKEN', " . var_export($saToken, true) . ");\n";
    $configContent .= "define('AGENCI_UPDATE_URL', 'https://raw.githubusercontent.com/agenci/bosse-updates/main');\n";
    $configContent .= "define('AGENCI_UPDATE_KEY', '');\n";

    if (file_put_contents(__DIR__ . '/config.php', $configContent) === false) {
        $errors[] = 'Kunde inte skriva config.php';
        return $errors;
    }

    // 2. variables.css
    $cssFile = __DIR__ . '/assets/css/variables.css';
    if (file_exists($cssFile)) {
        $css = file_get_contents($cssFile);

        $primaryDark = adjustBrightness($data['primary_color'], -15);
        $primaryLight = adjustBrightness($data['primary_color'], 20);
        $secondaryDark = adjustBrightness($data['secondary_color'], -15);

        $css = preg_replace('/(--color-primary:\s*)#[0-9a-fA-F]{3,8}/', '${1}' . $data['primary_color'], $css);
        $css = preg_replace('/(--color-primary-dark:\s*)#[0-9a-fA-F]{3,8}/', '${1}' . $primaryDark, $css);
        $css = preg_replace('/(--color-primary-light:\s*)#[0-9a-fA-F]{3,8}/', '${1}' . $primaryLight, $css);
        $css = preg_replace('/(--color-secondary:\s*)#[0-9a-fA-F]{3,8}/', '${1}' . $data['secondary_color'], $css);
        $css = preg_replace('/(--color-secondary-dark:\s*)#[0-9a-fA-F]{3,8}/', '${1}' . $secondaryDark, $css);

        // Font stacks
        $fontPrimary = buildFontStack($data['font_body']);
        $fontHeading = buildFontStack($data['font_heading']);

        $css = preg_replace('/(--font-primary:\s*).+?;/', '${1}' . $fontPrimary . ';', $css);
        $css = preg_replace('/(--font-heading:\s*).+?;/', '${1}' . $fontHeading . ';', $css);

        // Hörnradie
        $radiusMap = ['sharp' => '0.125rem', 'rounded' => '0.5rem', 'soft' => '1rem', 'pill' => '9999px'];
        $radiusLgMap = ['sharp' => '0.25rem', 'rounded' => '1rem', 'soft' => '1.5rem', 'pill' => '9999px'];
        $radiusVal = $radiusMap[$data['border_radius'] ?? 'rounded'] ?? '0.5rem';
        $radiusLgVal = $radiusLgMap[$data['border_radius'] ?? 'rounded'] ?? '1rem';
        $css = preg_replace('/(--radius-md:\s*)[^;]+;/', '${1}' . $radiusVal . ';', $css);
        $css = preg_replace('/(--radius-lg:\s*)[^;]+;/', '${1}' . $radiusLgVal . ';', $css);

        // Knappstil (button-radius)
        $btnRadiusMap = ['sharp' => '0.25rem', 'rounded' => '0.5rem', 'soft' => '1rem', 'pill' => '9999px'];
        $btnRadius = $btnRadiusMap[$data['border_radius'] ?? 'rounded'] ?? '0.5rem';
        $buttonStyle = $data['button_style'] ?? 'filled';
        if (strpos($css, '--button-radius') === false) {
            $css = str_replace('--radius-full: 9999px;', "--radius-full: 9999px;\n  --button-radius: {$btnRadius};\n  --button-style: {$buttonStyle};", $css);
        } else {
            $css = preg_replace('/(--button-radius:\s*)[^;]+;/', '${1}' . $btnRadius . ';', $css);
            $css = preg_replace('/(--button-style:\s*)[^;]+;/', '${1}' . $buttonStyle . ';', $css);
        }

        // Layout-bredd
        $widthMap = ['narrow' => '960px', 'normal' => '1200px', 'wide' => '1440px'];
        $containerWidth = $widthMap[$data['layout_width'] ?? 'normal'] ?? '1200px';
        $css = preg_replace('/(--container-max-width:\s*)[^;]+;/', '${1}' . $containerWidth . ';', $css);

        // Accent color
        if (!empty($data['accent_color'])) {
            if (strpos($css, '--color-accent') === false) {
                $css = str_replace('--color-secondary-dark:', "--color-accent: " . $data['accent_color'] . ";\n\n  /* Secondary Colors */\n  --color-secondary-dark:", $css);
                // Remove the duplicate "Secondary Colors" comment if it exists
                $css = str_replace("/* Secondary Colors */\n  /* Secondary Colors */", "/* Secondary Colors */", $css);
            } else {
                $css = preg_replace('/(--color-accent:\s*)#[0-9a-fA-F]{3,8}/', '${1}' . $data['accent_color'], $css);
            }
        }

        // Section padding (spacing feel)
        $sectionPaddingMap = ['compact' => '3rem', 'normal' => '4rem', 'airy' => '6rem'];
        $sectionPadding = $sectionPaddingMap[$data['spacing_feel'] ?? 'normal'] ?? '4rem';
        if (strpos($css, '--section-padding') === false) {
            $css = str_replace('--container-padding:', "--section-padding: {$sectionPadding};\n  --container-padding:", $css);
        } else {
            $css = preg_replace('/(--section-padding:\s*)[^;]+;/', '${1}' . $sectionPadding . ';', $css);
        }

        file_put_contents($cssFile, $css);
    }

    // 3. data/content.json
    $contentJson = [
        'hero' => [
            'title' => 'Välkommen till ' . $data['site_name'],
            'description' => $data['site_description'],
            'cta_primary' => 'Kontakta oss',
            'cta_secondary' => 'Läs mer',
        ],
        'home' => [
            'meta_title' => $data['site_name'] . ' - ' . $data['site_description'],
            'meta_description' => $data['site_description'],
        ],
        'footer' => [
            'company_name' => $data['site_name'],
            'email' => $data['contact_email'],
            'phone' => $data['contact_phone'],
        ],
    ];

    $dataDir = __DIR__ . '/data';
    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0755, true);
    }
    file_put_contents($dataDir . '/content.json', json_encode($contentJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    // 4. .windsurf/brand-guide.md
    $windsurfDir = __DIR__ . '/.windsurf';
    if (!is_dir($windsurfDir)) {
        mkdir($windsurfDir, 0755, true);
    }

    $brandTemplate = file_get_contents(__DIR__ . '/templates/brand-guide-template.md');
    if ($brandTemplate) {
        // Hörnradie-labels
        $radiusLabels = ['sharp' => 'Skarp (2px)', 'rounded' => 'Rundad (8px)', 'soft' => 'Mjuk (16px)', 'pill' => 'Pill (helrund)'];
        $radiusValues = ['sharp' => '0.125rem', 'rounded' => '0.5rem', 'soft' => '1rem', 'pill' => '9999px'];
        $btnRadiusValues = ['sharp' => '0.25rem', 'rounded' => '0.5rem', 'soft' => '1rem', 'pill' => '9999px'];

        // Knappstil-labels
        $btnLabels = ['filled' => 'Fylld', 'outline' => 'Outline', 'ghost' => 'Ghost'];
        $btnDescs = [
            'filled' => 'Bakgrundsfärg med vit text',
            'outline' => 'Transparent med färgad ram',
            'ghost' => 'Transparent utan ram, enbart text',
        ];

        // Tonalitet-labels
        $toneLabels = ['professional' => 'Professionell', 'playful' => 'Lekfull', 'minimal' => 'Minimalistisk'];
        $toneDescs = [
            'professional' => "- **Stil:** Tydlig, koncis, handlingsorienterad\n- Undvik: Jargong, överdriven formalitet, passiv form",
            'playful' => "- **Stil:** Varm, personlig, energisk\n- Undvik: Stelhet, formellt språk, passiv form",
            'minimal' => "- **Stil:** Kort, ren, avskalad\n- Undvik: Överflödiga ord, utsmyckningar, långa meningar",
        ];

        // Layout-labels
        $widthLabels = ['narrow' => 'Smal', 'normal' => 'Normal', 'wide' => 'Bred'];
        $widthValues = ['narrow' => '960px', 'normal' => '1200px', 'wide' => '1440px'];

        $br = $data['border_radius'] ?? 'rounded';
        $bs = $data['button_style'] ?? 'filled';
        $tn = $data['tonality'] ?? 'professional';
        $lw = $data['layout_width'] ?? 'normal';
        $bg = $data['bg_pattern'] ?? 'solid';
        $sp = $data['spacing_feel'] ?? 'normal';

        // Background pattern labels
        $bgLabels = ['solid' => 'Solid (enfärgad)', 'gradient' => 'Gradient (tonad)', 'subtle' => 'Subtil (mönster)'];
        $bgDescs = [
            'solid' => 'Rena, enfärgade bakgrunder. Sektioner alternerar mellan vit och grå.',
            'gradient' => 'Subtila gradienter med primärfärg. Hero och CTA-sektioner kan ha tonade bakgrunder.',
            'subtle' => 'Diskreta mönster eller texturer. Använd CSS-patterns eller SVG-bakgrunder sparsamt.',
        ];

        // Spacing feel labels
        $spacingLabels = ['compact' => 'Kompakt', 'normal' => 'Normal', 'airy' => 'Luftig'];
        $spacingValues = ['compact' => '3rem', 'normal' => '4rem', 'airy' => '6rem'];
        $spacingDescs = [
            'compact' => 'Tätare sektions-padding (3rem). Passar informationstäta sidor.',
            'normal' => 'Standard sektions-padding (4rem). Balanserad känsla.',
            'airy' => 'Generös sektions-padding (6rem). Lyxig, andningsbar känsla.',
        ];

        $brandGuide = str_replace(
            [
                '{{COMPANY_NAME}}', '{{PRIMARY_COLOR}}', '{{SECONDARY_COLOR}}', '{{ACCENT_COLOR}}',
                '{{FONT_HEADING}}', '{{FONT_BODY}}', '{{DATE}}',
                '{{BORDER_RADIUS_LABEL}}', '{{BORDER_RADIUS_VALUE}}',
                '{{BUTTON_STYLE_LABEL}}', '{{BUTTON_RADIUS_VALUE}}', '{{BUTTON_STYLE_DESC}}',
                '{{TONALITY_LABEL}}', '{{TONALITY_DESC}}',
                '{{LAYOUT_WIDTH_LABEL}}', '{{LAYOUT_WIDTH_VALUE}}',
                '{{BG_PATTERN_LABEL}}', '{{BG_PATTERN_DESC}}',
                '{{SPACING_FEEL_LABEL}}', '{{SPACING_FEEL_VALUE}}', '{{SPACING_FEEL_DESC}}',
            ],
            [
                $data['site_name'], $data['primary_color'], $data['secondary_color'], $data['accent_color'],
                $data['font_heading'], $data['font_body'], date('Y-m-d'),
                $radiusLabels[$br] ?? 'Rundad (8px)', $radiusValues[$br] ?? '0.5rem',
                $btnLabels[$bs] ?? 'Fylld', $btnRadiusValues[$br] ?? '0.5rem', $btnDescs[$bs] ?? 'Bakgrundsfärg med vit text',
                $toneLabels[$tn] ?? 'Professionell', $toneDescs[$tn] ?? '',
                $widthLabels[$lw] ?? 'Normal', $widthValues[$lw] ?? '1200px',
                $bgLabels[$bg] ?? 'Solid (enfärgad)', $bgDescs[$bg] ?? '',
                $spacingLabels[$sp] ?? 'Normal', $spacingValues[$sp] ?? '4rem', $spacingDescs[$sp] ?? '',
            ],
            $brandTemplate
        );
        file_put_contents($windsurfDir . '/brand-guide.md', $brandGuide);
    }

    // 5. .windsurf/ai-rules.md
    $aiTemplate = file_get_contents(__DIR__ . '/templates/ai-rules-template.md');
    if ($aiTemplate) {
        $aiRules = str_replace('{{COMPANY_NAME}}', $data['site_name'], $aiTemplate);
        file_put_contents($windsurfDir . '/ai-rules.md', $aiRules);
    }

    // 6. data/.setup-complete
    file_put_contents($dataDir . '/.setup-complete', 'Setup completed: ' . date('Y-m-d H:i:s') . "\n");

    // 7. includes/fonts.php
    $fontsContent = "<?php\n// Genererad av Setup Wizard\n";
    $googleFonts = [];
    foreach ([$data['font_heading'], $data['font_body']] as $font) {
        if ($font !== 'System UI' && !in_array($font, $googleFonts)) {
            $googleFonts[] = $font;
        }
    }

    if (!empty($googleFonts)) {
        $families = [];
        foreach ($googleFonts as $font) {
            $families[] = 'family=' . urlencode($font) . ':wght@400;500;600;700';
        }
        $url = 'https://fonts.googleapis.com/css2?' . implode('&', $families) . '&display=swap';
        $fontsContent .= "?>\n<link rel=\"preconnect\" href=\"https://fonts.googleapis.com\">\n";
        $fontsContent .= "<link rel=\"preconnect\" href=\"https://fonts.gstatic.com\" crossorigin>\n";
        $fontsContent .= "<link href=\"" . htmlspecialchars($url) . "\" rel=\"stylesheet\">\n";
    } else {
        $fontsContent .= "// System UI - ingen extern font behövs\n";
    }

    file_put_contents(__DIR__ . '/includes/fonts.php', $fontsContent);

    // 8. Favicon (kopiera logo-dark som favicon om den finns)
    $logoDark = __DIR__ . '/assets/images/logo-dark.png';
    $faviconDest = __DIR__ . '/assets/images/favicon.png';
    if (file_exists($logoDark) && !file_exists($faviconDest)) {
        // Försök skapa en 32x32 favicon med GD
        if (function_exists('imagecreatefrompng')) {
            $src = @imagecreatefrompng($logoDark);
            if ($src) {
                $w = imagesx($src);
                $h = imagesy($src);
                $size = 32;
                $favicon = imagecreatetruecolor($size, $size);
                imagesavealpha($favicon, true);
                $transparent = imagecolorallocatealpha($favicon, 0, 0, 0, 127);
                imagefill($favicon, 0, 0, $transparent);
                // Fit within 32x32 keeping aspect ratio
                $ratio = min($size / $w, $size / $h);
                $newW = (int)round($w * $ratio);
                $newH = (int)round($h * $ratio);
                $x = (int)round(($size - $newW) / 2);
                $y = (int)round(($size - $newH) / 2);
                imagecopyresampled($favicon, $src, $x, $y, 0, 0, $newW, $newH, $w, $h);
                imagepng($favicon, $faviconDest);

                // Also create apple-touch-icon (180x180)
                $src2 = @imagecreatefrompng($logoDark);
                if ($src2) {
                    $appleSize = 180;
                    $apple = imagecreatetruecolor($appleSize, $appleSize);
                    imagesavealpha($apple, true);
                    $transparent2 = imagecolorallocatealpha($apple, 0, 0, 0, 127);
                    imagefill($apple, 0, 0, $transparent2);
                    $ratio2 = min($appleSize / $w, $appleSize / $h);
                    $newW2 = (int)round($w * $ratio2);
                    $newH2 = (int)round($h * $ratio2);
                    $x2 = (int)round(($appleSize - $newW2) / 2);
                    $y2 = (int)round(($appleSize - $newH2) / 2);
                    imagecopyresampled($apple, $src2, $x2, $y2, 0, 0, $newW2, $newH2, $w, $h);
                    imagepng($apple, __DIR__ . '/assets/images/apple-touch-icon.png');
                }
            }
        } else {
            // Fallback: kopiera logotypen direkt
            copy($logoDark, $faviconDest);
        }
    }

    // 9. includes/analytics.php (GA4 script)
    if (!empty($data['ga_id'])) {
        $gaContent = "<?php\n// Google Analytics 4 - Genererad av Setup Wizard\n";
        $gaContent .= "// Använder Google Consent Mode v2 (konfigureras i cookie-consent.php)\n?>\n";
        $gaContent .= "<script async src=\"https://www.googletagmanager.com/gtag/js?id=" . htmlspecialchars($data['ga_id']) . "\"></script>\n";
        $gaContent .= "<script>\n";
        $gaContent .= "window.dataLayer = window.dataLayer || [];\n";
        $gaContent .= "function gtag(){dataLayer.push(arguments);}\n";
        $gaContent .= "gtag('js', new Date());\n";
        $gaContent .= "gtag('config', '" . htmlspecialchars($data['ga_id']) . "');\n";
        $gaContent .= "</script>\n";
        file_put_contents(__DIR__ . '/includes/analytics.php', $gaContent);
    }

    // 10. overrides.css (tom fil med header — för framtida AI-overrides)
    $overridesFile = __DIR__ . '/assets/css/overrides.css';
    if (!file_exists($overridesFile)) {
        $overridesContent = "/**\n";
        $overridesContent .= " * Design Overrides\n";
        $overridesContent .= " * Denna fil laddas SIST och vinner över allt annat.\n";
        $overridesContent .= " *\n";
        $overridesContent .= " * REGLER:\n";
        $overridesContent .= " * - Alla visuella ändringar som avviker från brand guiden skrivs HÄR\n";
        $overridesContent .= " * - Ändra ALDRIG variables.css eller components.css direkt\n";
        $overridesContent .= " * - Denna fil överlever omgenerering av setup-wizarden\n";
        $overridesContent .= " *\n";
        $overridesContent .= " * EXEMPEL:\n";
        $overridesContent .= " * .button--primary { border-radius: 0; background: linear-gradient(...); }\n";
        $overridesContent .= " * .card { box-shadow: none; border: 2px solid #000; }\n";
        $overridesContent .= " */\n";
        file_put_contents($overridesFile, $overridesContent);
    }

    return $errors;
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
 * Bygg font-stack för CSS
 */
function buildFontStack(string $font): string {
    if ($font === 'System UI') {
        return "system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif";
    }
    return "'" . $font . "', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif";
}

// Ladda sparad data för formulär
$saved = $_SESSION['setup_data'] ?? [];
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Wizard - Konfigurera din webbplats</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #fafafa;
            color: #18181b;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 3rem 1.5rem;
        }

        .setup-container {
            width: 100%;
            max-width: 36rem;
        }

        .setup-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .setup-header h1 {
            font-size: 2.25rem;
            font-weight: bold;
            color: #18181b;
            margin-bottom: 0.75rem;
        }

        .setup-header p {
            font-size: 1.125rem;
            color: #737373;
        }

        /* Progress Steps */
        .progress-steps {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0;
            margin-bottom: 2.5rem;
        }

        .progress-step {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .step-circle {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.875rem;
            border: 2px solid #d4d4d4;
            color: #a3a3a3;
            background: white;
            transition: all 0.2s;
        }

        .step-circle.active {
            border-color: #fe4f2a;
            background: #fe4f2a;
            color: white;
        }

        .step-circle.completed {
            border-color: #10b981;
            background: #10b981;
            color: white;
        }

        .step-label {
            font-size: 0.8125rem;
            font-weight: 600;
            color: #737373;
            display: none;
        }

        @media (min-width: 640px) {
            .step-label { display: block; }
        }

        .step-line {
            width: 60px;
            height: 2px;
            background: #d4d4d4;
            margin: 0 0.75rem;
            transition: background 0.2s;
        }

        .step-line.completed {
            background: #10b981;
        }

        /* Card — matches CMS dashboard/support style */
        .setup-card {
            background: white;
            border-radius: 1.5rem;
            border: 1px solid #e5e5e5;
            padding: 2rem;
        }

        .setup-card h2 {
            font-size: 1.25rem;
            font-weight: bold;
            color: #18181b;
            margin-bottom: 1.5rem;
        }

        /* Form — matches CMS admin.php / support.php */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: #18181b;
            margin-bottom: 0.5rem;
        }

        .form-group .hint {
            font-size: 0.75rem;
            color: #a3a3a3;
            margin-top: 0.375rem;
        }

        input[type="text"],
        input[type="email"],
        input[type="tel"],
        input[type="url"],
        input[type="password"],
        input[type="number"],
        textarea,
        select {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 1px solid #d4d4d4;
            border-radius: 0.75rem;
            font-size: 1rem;
            font-family: inherit;
            background: white;
            color: #18181b;
            outline: none;
            transition: all 0.2s;
        }

        input:focus,
        textarea:focus,
        select:focus {
            border-color: transparent;
            box-shadow: 0 0 0 2px #ff5722;
        }

        textarea {
            min-height: 80px;
            resize: vertical;
        }

        input[type="color"] {
            width: 48px;
            height: 44px;
            padding: 2px;
            border: 1px solid #d4d4d4;
            border-radius: 0.75rem;
            cursor: pointer;
            background: white;
        }

        .color-group {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .color-group input[type="text"] {
            flex: 1;
        }

        /* Section divider */
        .section-divider {
            margin-top: 2rem;
            margin-bottom: 1rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e5e5e5;
        }

        .section-divider h3 {
            font-size: 1rem;
            font-weight: bold;
            color: #18181b;
            margin-bottom: 0.25rem;
        }

        .section-divider p {
            font-size: 0.8125rem;
            color: #a3a3a3;
            margin-bottom: 1.25rem;
        }

        /* File upload */
        .file-upload {
            border: 2px dashed #d4d4d4;
            border-radius: 0.75rem;
            padding: 1.25rem;
            text-align: center;
            cursor: pointer;
            transition: border-color 0.2s, background 0.2s;
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
            font-size: 0.875rem;
            color: #737373;
        }

        .file-upload-text strong {
            color: #fe4f2a;
        }

        .logo-preview {
            max-height: 60px;
            max-width: 200px;
            margin-top: 0.75rem;
            display: none;
            border-radius: 0.25rem;
        }

        /* Preview card */
        .design-preview {
            border: 1px solid #e5e5e5;
            border-radius: 1rem;
            padding: 1.5rem;
            margin-top: 1.5rem;
        }

        .design-preview h3 {
            font-size: 0.875rem;
            font-weight: 600;
            color: #a3a3a3;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 1rem;
        }

        .preview-colors {
            display: flex;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .preview-swatch {
            width: 48px;
            height: 48px;
            border-radius: 0.75rem;
            border: 1px solid rgba(0,0,0,0.1);
        }

        .preview-text h4 {
            font-size: 1.125rem;
            margin-bottom: 0.25rem;
        }

        .preview-text p {
            font-size: 0.875rem;
            color: #737373;
        }

        .preview-button {
            display: inline-block;
            padding: 0.5rem 1.25rem;
            border-radius: 9999px;
            color: white;
            font-size: 0.875rem;
            font-weight: 600;
            margin-top: 0.75rem;
            border: none;
        }

        /* Password strength */
        .password-strength {
            height: 4px;
            border-radius: 2px;
            background: #e5e5e5;
            margin-top: 0.5rem;
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            border-radius: 2px;
            transition: width 0.3s, background 0.3s;
            width: 0;
        }

        .password-strength-text {
            font-size: 0.75rem;
            margin-top: 0.25rem;
            color: #a3a3a3;
        }

        /* Buttons — matches CMS button style */
        .form-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 2rem;
            gap: 1rem;
        }

        .btn {
            padding: 0.875rem 1.75rem;
            border-radius: 9999px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .btn-primary {
            background: #fe4f2a;
            color: white;
        }

        .btn-primary:hover {
            background: #e8461f;
        }

        .btn-secondary {
            background: none;
            color: #a3a3a3;
            border: 1px solid #d4d4d4;
        }

        .btn-secondary:hover {
            background: #f5f5f5;
            color: #737373;
        }

        .btn-success {
            background: #10b981;
            color: white;
        }

        .btn-success:hover {
            background: #059669;
        }

        /* Errors */
        .error-list {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 0.75rem;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
            list-style: none;
        }

        .error-list li {
            font-size: 0.875rem;
            color: #b91c1c;
            padding: 0.125rem 0;
        }

        /* Writable errors */
        .writable-error {
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 0.75rem;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
            color: #92400e;
        }

        .writable-error code {
            background: rgba(0,0,0,0.05);
            padding: 0.125rem 0.375rem;
            border-radius: 0.25rem;
            font-size: 0.8125rem;
        }

        /* Complete page */
        .complete-list {
            list-style: none;
            padding: 0;
        }

        .complete-list li {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 0;
            font-size: 0.9375rem;
            border-bottom: 1px solid #f5f5f5;
        }

        .complete-list li:last-child {
            border-bottom: none;
        }

        .check-icon {
            width: 24px;
            height: 24px;
            background: #10b981;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            color: white;
            font-size: 0.75rem;
        }

        .complete-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .complete-actions .btn {
            flex: 1;
            justify-content: center;
        }

        /* Row layout */
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        /* Agenci badge */
        .agenci-badge {
            position: fixed;
            bottom: 1.5rem;
            left: 1.5rem;
            z-index: 9999;
        }
        .agenci-badge a {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: white;
            padding: 0.5rem 1rem;
            border-radius: 9999px;
            text-decoration: none;
            transition: all 0.2s;
            font-size: 0.875rem;
            color: #18181b;
            font-weight: 600;
        }
        .agenci-badge a:hover {
            transform: translateY(-2px);
        }

        @media (max-width: 480px) {
            .form-row { grid-template-columns: 1fr; }
            .form-actions { flex-direction: column-reverse; }
            .form-actions .btn { width: 100%; justify-content: center; }
            .complete-actions { flex-direction: column; }
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <div class="setup-header">
            <h1>Välkommen! 👋</h1>
            <p>Konfigurera din webbplats i tre enkla steg</p>
        </div>

        <?php if ($step <= 3): ?>
        <!-- Progress Steps -->
        <div class="progress-steps">
            <div class="progress-step">
                <div class="step-circle <?php echo $step === 1 ? 'active' : ($step > 1 ? 'completed' : ''); ?>">
                    <?php echo $step > 1 ? '&#10003;' : '1'; ?>
                </div>
                <span class="step-label">Företag</span>
            </div>
            <div class="step-line <?php echo $step > 1 ? 'completed' : ''; ?>"></div>
            <div class="progress-step">
                <div class="step-circle <?php echo $step === 2 ? 'active' : ($step > 2 ? 'completed' : ''); ?>">
                    <?php echo $step > 2 ? '&#10003;' : '2'; ?>
                </div>
                <span class="step-label">Design</span>
            </div>
            <div class="step-line <?php echo $step > 2 ? 'completed' : ''; ?>"></div>
            <div class="progress-step">
                <div class="step-circle <?php echo $step === 3 ? 'active' : ''; ?>">3</div>
                <span class="step-label">Admin</span>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($writableErrors) && $step <= 3): ?>
        <div class="writable-error">
            <strong>Skrivbehörigheter saknas!</strong> Följande mappar/filer är inte skrivbara:
            <?php foreach ($writableErrors as $path): ?>
                <br><code><?php echo htmlspecialchars($path); ?></code>
            <?php endforeach; ?>
            <br><br>Kör: <code>chmod -R 755 <?php echo htmlspecialchars(__DIR__); ?></code>
        </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
        <ul class="error-list">
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>

        <?php if ($step === 1): ?>
        <!-- STEG 1: Företagsinformation -->
        <div class="setup-card">
            <h2>Företagsinformation</h2>
            <form method="post" action="/setup?step=1" id="step1-form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['setup_csrf']); ?>">
                <input type="hidden" name="step" value="1">

                <div class="form-group">
                    <label for="site_name">Företagsnamn *</label>
                    <input type="text" id="site_name" name="site_name" required
                           value="<?php echo htmlspecialchars($saved['site_name'] ?? ''); ?>"
                           placeholder="Mitt Företag AB">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="contact_email">E-postadress *</label>
                        <input type="email" id="contact_email" name="contact_email" required
                               value="<?php echo htmlspecialchars($saved['contact_email'] ?? ''); ?>"
                               placeholder="info@example.com">
                    </div>
                    <div class="form-group">
                        <label for="contact_phone">Telefon</label>
                        <input type="tel" id="contact_phone" name="contact_phone"
                               value="<?php echo htmlspecialchars($saved['contact_phone'] ?? ''); ?>"
                               placeholder="+46 70 000 00 00">
                    </div>
                </div>

                <div class="form-group">
                    <label for="site_url">Webbadress *</label>
                    <input type="url" id="site_url" name="site_url" required
                           value="<?php echo htmlspecialchars($saved['site_url'] ?? 'http://localhost:8000'); ?>"
                           placeholder="https://example.com">
                    <div class="hint">Ändra till din riktiga domän vid produktion</div>
                </div>

                <div class="form-group">
                    <label for="site_description">Beskrivning *</label>
                    <textarea id="site_description" name="site_description" required
                              placeholder="Kort beskrivning av företaget och vad ni gör"><?php echo htmlspecialchars($saved['site_description'] ?? ''); ?></textarea>
                </div>

                <div class="section-divider">
                    <h3>E-post &amp; SMTP</h3>
                    <p>Valfritt — behövs för kontaktformulär och support-mail.</p>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="smtp_host">SMTP-server</label>
                        <input type="text" id="smtp_host" name="smtp_host"
                               value="<?php echo htmlspecialchars($saved['smtp_host'] ?? 'server16.serverdrift.com'); ?>"
                               placeholder="smtp.example.com">
                    </div>
                    <div class="form-group">
                        <label for="smtp_port">Port</label>
                        <input type="number" id="smtp_port" name="smtp_port" min="1" max="65535"
                               value="<?php echo htmlspecialchars($saved['smtp_port'] ?? '465'); ?>"
                               placeholder="465">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="smtp_encryption">Kryptering</label>
                        <select id="smtp_encryption" name="smtp_encryption">
                            <option value="ssl" <?php echo ($saved['smtp_encryption'] ?? 'ssl') === 'ssl' ? 'selected' : ''; ?>>SSL (port 465)</option>
                            <option value="tls" <?php echo ($saved['smtp_encryption'] ?? 'ssl') === 'tls' ? 'selected' : ''; ?>>STARTTLS (port 587)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="smtp_username">SMTP-användarnamn</label>
                        <input type="email" id="smtp_username" name="smtp_username"
                               value="<?php echo htmlspecialchars($saved['smtp_username'] ?? 'webb@peys.se'); ?>"
                               placeholder="user@example.com">
                    </div>
                </div>

                <div class="form-group">
                    <label for="smtp_password">SMTP-lösenord</label>
                    <input type="password" id="smtp_password" name="smtp_password"
                           value="<?php echo htmlspecialchars($saved['smtp_password'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                           placeholder="SMTP-lösenord" autocomplete="new-password">
                    <div class="hint">Ange lösenord för SMTP-servern. Lämna tomt om du inte vill använda SMTP-mail.</div>
                </div>

                <div class="section-divider">
                    <h3>Google Analytics</h3>
                    <p>Valfritt — kräver samtycke via cookie-bannern (Consent Mode v2).</p>
                </div>

                <div class="form-group">
                    <label for="ga_id">Measurement ID</label>
                    <input type="text" id="ga_id" name="ga_id"
                           value="<?php echo htmlspecialchars($saved['ga_id'] ?? ''); ?>"
                           placeholder="G-XXXXXXXXXX">
                    <div class="hint">Hittas i Google Analytics &rarr; Admin &rarr; Data Streams</div>
                </div>

                <div class="section-divider">
                    <h3>Sociala medier</h3>
                    <p>Valfritt — visas i footer och används i schema.org SEO.</p>
                </div>

                <div class="form-group">
                    <label for="social_facebook">Facebook</label>
                    <input type="url" id="social_facebook" name="social_facebook"
                           value="<?php echo htmlspecialchars($saved['social_facebook'] ?? ''); ?>"
                           placeholder="https://facebook.com/foretagsnamn">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="social_instagram">Instagram</label>
                        <input type="url" id="social_instagram" name="social_instagram"
                               value="<?php echo htmlspecialchars($saved['social_instagram'] ?? ''); ?>"
                               placeholder="https://instagram.com/foretagsnamn">
                    </div>
                    <div class="form-group">
                        <label for="social_linkedin">LinkedIn</label>
                        <input type="url" id="social_linkedin" name="social_linkedin"
                               value="<?php echo htmlspecialchars($saved['social_linkedin'] ?? ''); ?>"
                               placeholder="https://linkedin.com/company/foretagsnamn">
                    </div>
                </div>

                <div class="section-divider">
                    <h3>Öppettider</h3>
                    <p>Valfritt — visas i footer och schema.org.</p>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="hours_weekdays">Vardagar (mån-fre)</label>
                        <input type="text" id="hours_weekdays" name="hours_weekdays"
                               value="<?php echo htmlspecialchars($saved['hours_weekdays'] ?? ''); ?>"
                               placeholder="08:00 - 17:00">
                    </div>
                    <div class="form-group">
                        <label for="hours_weekends">Helger (lör-sön)</label>
                        <input type="text" id="hours_weekends" name="hours_weekends"
                               value="<?php echo htmlspecialchars($saved['hours_weekends'] ?? ''); ?>"
                               placeholder="Stängt">
                    </div>
                </div>

                <div class="form-actions">
                    <span></span>
                    <button type="submit" class="btn btn-primary">Nästa &rarr;</button>
                </div>
            </form>
        </div>

        <?php elseif ($step === 2): ?>
        <!-- STEG 2: Design & Varumärke -->
        <div class="setup-card">
            <h2>Design &amp; Varumärke</h2>
            <form method="post" action="/setup?step=2" enctype="multipart/form-data" id="step2-form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['setup_csrf']); ?>">
                <input type="hidden" name="step" value="2">

                <div class="form-row">
                    <div class="form-group">
                        <label>Logo (mörk bakgrund)</label>
                        <div class="file-upload" id="upload-dark">
                            <input type="file" name="logo_dark" accept=".png,.jpg,.jpeg,.svg,.webp"
                                   onchange="previewLogo(this, 'preview-dark')">
                            <div class="file-upload-text">
                                <strong>Välj fil</strong> eller dra hit
                                <br><small>PNG, JPG, SVG, WebP (max 5MB)</small>
                            </div>
                            <img class="logo-preview" id="preview-dark" alt="Logo förhandsgranskning">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Logo (ljus bakgrund)</label>
                        <div class="file-upload" id="upload-light">
                            <input type="file" name="logo_light" accept=".png,.jpg,.jpeg,.svg,.webp"
                                   onchange="previewLogo(this, 'preview-light')">
                            <div class="file-upload-text">
                                <strong>Välj fil</strong> eller dra hit
                                <br><small>PNG, JPG, SVG, WebP (max 5MB)</small>
                            </div>
                            <img class="logo-preview" id="preview-light" alt="Logo förhandsgranskning">
                        </div>
                    </div>
                </div>

                <div class="form-row" style="grid-template-columns: 1fr 1fr 1fr;">
                    <div class="form-group">
                        <label for="primary_color">Primärfärg</label>
                        <div class="color-group">
                            <input type="color" id="primary_color" name="primary_color"
                                   value="<?php echo htmlspecialchars($saved['primary_color'] ?? '#8b5cf6'); ?>"
                                   onchange="updateColorText(this, 'primary_text'); updatePreview();">
                            <input type="text" id="primary_text"
                                   value="<?php echo htmlspecialchars($saved['primary_color'] ?? '#8b5cf6'); ?>"
                                   onchange="document.getElementById('primary_color').value = this.value; updatePreview();"
                                   pattern="#[0-9a-fA-F]{6}" maxlength="7">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="secondary_color">Sekundärfärg</label>
                        <div class="color-group">
                            <input type="color" id="secondary_color" name="secondary_color"
                                   value="<?php echo htmlspecialchars($saved['secondary_color'] ?? '#FF6B35'); ?>"
                                   onchange="updateColorText(this, 'secondary_text'); updatePreview();">
                            <input type="text" id="secondary_text"
                                   value="<?php echo htmlspecialchars($saved['secondary_color'] ?? '#FF6B35'); ?>"
                                   onchange="document.getElementById('secondary_color').value = this.value; updatePreview();"
                                   pattern="#[0-9a-fA-F]{6}" maxlength="7">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="accent_color">Accentfärg</label>
                        <div class="color-group">
                            <input type="color" id="accent_color" name="accent_color"
                                   value="<?php echo htmlspecialchars($saved['accent_color'] ?? '#fe4f2a'); ?>"
                                   onchange="updateColorText(this, 'accent_text'); updatePreview();">
                            <input type="text" id="accent_text"
                                   value="<?php echo htmlspecialchars($saved['accent_color'] ?? '#fe4f2a'); ?>"
                                   onchange="document.getElementById('accent_color').value = this.value; updatePreview();"
                                   pattern="#[0-9a-fA-F]{6}" maxlength="7">
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="font_heading">Rubrik-typsnitt</label>
                        <select id="font_heading" name="font_heading" onchange="updateFontPreview()">
                            <?php
                            $fonts = ['System UI', 'Inter', 'Poppins', 'Playfair Display', 'Roboto', 'DM Sans', 'Montserrat', 'Lora'];
                            $selectedHeading = $saved['font_heading'] ?? 'System UI';
                            foreach ($fonts as $f):
                            ?>
                            <option value="<?php echo $f; ?>" <?php echo $f === $selectedHeading ? 'selected' : ''; ?>><?php echo $f; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="font_body">Brödtext-typsnitt</label>
                        <select id="font_body" name="font_body" onchange="updateFontPreview()">
                            <?php
                            $selectedBody = $saved['font_body'] ?? 'System UI';
                            foreach ($fonts as $f):
                            ?>
                            <option value="<?php echo $f; ?>" <?php echo $f === $selectedBody ? 'selected' : ''; ?>><?php echo $f; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="section-divider">
                    <h3>Form &amp; Stil</h3>
                    <p>Styr grundkänslan — kan alltid justeras av AI senare.</p>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="border_radius">Hörnradie</label>
                        <select id="border_radius" name="border_radius" onchange="updatePreview()">
                            <?php
                            $radiusOptions = ['sharp' => 'Skarp (2px)', 'rounded' => 'Rundad (8px)', 'soft' => 'Mjuk (16px)', 'pill' => 'Pill (helrund)'];
                            $selectedRadius = $saved['border_radius'] ?? 'rounded';
                            foreach ($radiusOptions as $val => $label):
                            ?>
                            <option value="<?php echo $val; ?>" <?php echo $val === $selectedRadius ? 'selected' : ''; ?>><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="button_style">Knappstil</label>
                        <select id="button_style" name="button_style" onchange="updatePreview()">
                            <?php
                            $btnOptions = ['filled' => 'Fylld', 'outline' => 'Outline', 'ghost' => 'Ghost'];
                            $selectedBtn = $saved['button_style'] ?? 'filled';
                            foreach ($btnOptions as $val => $label):
                            ?>
                            <option value="<?php echo $val; ?>" <?php echo $val === $selectedBtn ? 'selected' : ''; ?>><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="tonality">Tonalitet</label>
                        <select id="tonality" name="tonality">
                            <?php
                            $toneOptions = ['professional' => 'Professionell', 'playful' => 'Lekfull', 'minimal' => 'Minimalistisk'];
                            $selectedTone = $saved['tonality'] ?? 'professional';
                            foreach ($toneOptions as $val => $label):
                            ?>
                            <option value="<?php echo $val; ?>" <?php echo $val === $selectedTone ? 'selected' : ''; ?>><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="layout_width">Layout-bredd</label>
                        <select id="layout_width" name="layout_width">
                            <?php
                            $widthOptions = ['narrow' => 'Smal (960px)', 'normal' => 'Normal (1200px)', 'wide' => 'Bred (1440px)'];
                            $selectedWidth = $saved['layout_width'] ?? 'normal';
                            foreach ($widthOptions as $val => $label):
                            ?>
                            <option value="<?php echo $val; ?>" <?php echo $val === $selectedWidth ? 'selected' : ''; ?>><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="bg_pattern">Bakgrundsmönster</label>
                        <select id="bg_pattern" name="bg_pattern">
                            <?php
                            $bgOptions = ['solid' => 'Solid (enfärgad)', 'gradient' => 'Gradient (tonad)', 'subtle' => 'Subtil (mönster)'];
                            $selectedBg = $saved['bg_pattern'] ?? 'solid';
                            foreach ($bgOptions as $val => $label):
                            ?>
                            <option value="<?php echo $val; ?>" <?php echo $val === $selectedBg ? 'selected' : ''; ?>><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="spacing_feel">Spacing-känsla</label>
                        <select id="spacing_feel" name="spacing_feel">
                            <?php
                            $spacingOptions = ['compact' => 'Kompakt (tätare)', 'normal' => 'Normal (standard)', 'airy' => 'Luftig (rymligare)'];
                            $selectedSpacing = $saved['spacing_feel'] ?? 'normal';
                            foreach ($spacingOptions as $val => $label):
                            ?>
                            <option value="<?php echo $val; ?>" <?php echo $val === $selectedSpacing ? 'selected' : ''; ?>><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Design preview -->
                <div class="design-preview" id="design-preview">
                    <h3>Förhandsgranskning</h3>
                    <div class="preview-colors">
                        <div class="preview-swatch" id="swatch-primary" style="background: #8b5cf6;"></div>
                        <div class="preview-swatch" id="swatch-secondary" style="background: #FF6B35;"></div>
                        <div class="preview-swatch" id="swatch-accent" style="background: #fe4f2a;"></div>
                    </div>
                    <div class="preview-text">
                        <h4 id="preview-heading" style="font-family: system-ui, sans-serif;">Rubrik-exempel</h4>
                        <p id="preview-body" style="font-family: system-ui, sans-serif;">Brödtext-exempel med valt typsnitt.</p>
                    </div>
                    <button type="button" class="preview-button" id="preview-btn" style="background: #8b5cf6;">Exempelknapp</button>
                </div>

                <div class="form-actions">
                    <a href="/setup?step=1" class="btn btn-secondary">&larr; Tillbaka</a>
                    <button type="submit" class="btn btn-primary">Nästa &rarr;</button>
                </div>
            </form>
        </div>

        <?php elseif ($step === 3): ?>
        <!-- STEG 3: Admin-konto -->
        <div class="setup-card">
            <h2>Admin-konto</h2>
            <form method="post" action="/setup?step=3" id="step3-form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['setup_csrf']); ?>">
                <input type="hidden" name="step" value="3">

                <div class="form-group">
                    <label for="admin_username">Användarnamn *</label>
                    <input type="text" id="admin_username" name="admin_username" required minlength="3"
                           placeholder="admin" autocomplete="username">
                    <div class="hint">Minst 3 tecken. Undvik "admin" för bättre säkerhet.</div>
                </div>

                <div class="form-group">
                    <label for="admin_password">Lösenord *</label>
                    <input type="password" id="admin_password" name="admin_password" required minlength="8"
                           placeholder="Minst 8 tecken" autocomplete="new-password">
                    <div class="password-strength">
                        <div class="password-strength-bar" id="strength-bar"></div>
                    </div>
                    <div class="password-strength-text" id="strength-text"></div>
                </div>

                <div class="form-group">
                    <label for="admin_password_confirm">Bekräfta lösenord *</label>
                    <input type="password" id="admin_password_confirm" name="admin_password_confirm" required minlength="8"
                           placeholder="Upprepa lösenord" autocomplete="new-password">
                </div>

                <div class="form-actions">
                    <a href="/setup?step=2" class="btn btn-secondary">&larr; Tillbaka</a>
                    <button type="submit" class="btn btn-primary" id="submit-btn">Slutför installation</button>
                </div>
            </form>
        </div>

        <?php elseif ($step === 4):
            // Backend-verifiering
            $checks = [
                ['label' => 'config.php', 'desc' => 'Konfiguration', 'ok' => file_exists(__DIR__ . '/config.php')],
                ['label' => 'variables.css', 'desc' => 'Färger och typsnitt', 'ok' => file_exists(__DIR__ . '/assets/css/variables.css') && strpos(file_get_contents(__DIR__ . '/assets/css/variables.css'), '--color-primary') !== false],
                ['label' => 'overrides.css', 'desc' => 'Override-system', 'ok' => file_exists(__DIR__ . '/assets/css/overrides.css')],
                ['label' => 'content.json', 'desc' => 'Innehållsdata', 'ok' => file_exists(__DIR__ . '/data/content.json')],
                ['label' => 'brand-guide.md', 'desc' => 'Varumärkesguide', 'ok' => file_exists(__DIR__ . '/.windsurf/brand-guide.md')],
                ['label' => 'ai-rules.md', 'desc' => 'AI-regler', 'ok' => file_exists(__DIR__ . '/.windsurf/ai-rules.md')],
                ['label' => 'fonts.php', 'desc' => 'Typsnittslänkar', 'ok' => file_exists(__DIR__ . '/includes/fonts.php')],
                ['label' => 'SMTP', 'desc' => 'E-postkonfiguration', 'ok' => defined('SMTP_HOST') || (file_exists(__DIR__ . '/config.php') && strpos(file_get_contents(__DIR__ . '/config.php'), 'SMTP_HOST') !== false)],
                ['label' => 'Säkerhet', 'desc' => 'Session & CSRF', 'ok' => file_exists(__DIR__ . '/config.php') && strpos(file_get_contents(__DIR__ . '/config.php'), 'SESSION_SECRET') !== false],
                ['label' => 'Skrivbar', 'desc' => 'data/-mappen', 'ok' => is_writable(__DIR__ . '/data')],
            ];
            $allOk = !in_array(false, array_column($checks, 'ok'));
        ?>
        <!-- STEG 4: Verifiering + Färdig -->
        <div class="setup-card" id="verify-card">
            <h2 style="text-align: center;" id="verify-title">Verifierar installation...</h2>
            <p style="text-align: center; color: #737373; margin-bottom: 1.5rem;" id="verify-subtitle">Kontrollerar att allt genererats korrekt</p>

            <ul class="complete-list" id="verify-list">
                <?php foreach ($checks as $i => $check): ?>
                <li data-index="<?php echo $i; ?>" data-ok="<?php echo $check['ok'] ? '1' : '0'; ?>" style="opacity: 0.3;">
                    <span class="check-icon" style="background: #d4d4d4;" id="icon-<?php echo $i; ?>">
                        <svg width="12" height="12" viewBox="0 0 12 12" fill="none" style="display:none;" id="svg-<?php echo $i; ?>">
                            <path d="M2 6L5 9L10 3" stroke="white" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        <svg width="12" height="12" viewBox="0 0 12 12" fill="none" style="display:none;" id="fail-<?php echo $i; ?>">
                            <path d="M3 3L9 9M9 3L3 9" stroke="white" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </span>
                    <span><?php echo htmlspecialchars($check['label']); ?> — <?php echo htmlspecialchars($check['desc']); ?></span>
                </li>
                <?php endforeach; ?>
            </ul>

            <div class="complete-actions" id="verify-actions" style="display: none;">
                <a href="/" class="btn btn-primary">Gå till hemsidan</a>
                <a href="/admin" class="btn btn-success">Öppna admin</a>
            </div>
        </div>

        <script>
        (function() {
            const items = document.querySelectorAll('#verify-list li');
            const total = items.length;
            let current = 0;

            function revealNext() {
                if (current >= total) {
                    // Done — show result
                    const allOk = <?php echo $allOk ? 'true' : 'false'; ?>;
                    document.getElementById('verify-title').textContent = allOk ? 'Installation klar!' : 'Nästan klart';
                    document.getElementById('verify-subtitle').textContent = allOk
                        ? 'Alla kontroller godkända — din webbplats är redo.'
                        : 'Vissa kontroller misslyckades. Se markerade rader.';
                    document.getElementById('verify-actions').style.display = 'flex';
                    return;
                }

                const li = items[current];
                const ok = li.dataset.ok === '1';
                const icon = document.getElementById('icon-' + current);
                const svg = document.getElementById('svg-' + current);
                const fail = document.getElementById('fail-' + current);

                li.style.transition = 'opacity 0.3s';
                li.style.opacity = '1';

                setTimeout(function() {
                    icon.style.transition = 'background 0.2s';
                    icon.style.background = ok ? '#10b981' : '#ef4444';
                    if (ok) {
                        svg.style.display = 'block';
                    } else {
                        fail.style.display = 'block';
                    }
                    current++;
                    setTimeout(revealNext, 200);
                }, 150);
            }

            setTimeout(revealNext, 500);
        })();
        </script>
        <?php endif; ?>
    </div>

    <div class="agenci-badge">
        <a href="https://agenci.se" target="_blank" rel="noopener noreferrer">
            Skapad av oss 🥳
        </a>
    </div>

    <script>
    // Logo preview
    function previewLogo(input, previewId) {
        const preview = document.getElementById(previewId);
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    // Color picker sync
    function updateColorText(colorInput, textId) {
        document.getElementById(textId).value = colorInput.value;
    }

    // Update preview
    function updatePreview() {
        const primary = document.getElementById('primary_color')?.value || '#8b5cf6';
        const secondary = document.getElementById('secondary_color')?.value || '#FF6B35';
        const accent = document.getElementById('accent_color')?.value || '#fe4f2a';

        const sp = document.getElementById('swatch-primary');
        const ss = document.getElementById('swatch-secondary');
        const sa = document.getElementById('swatch-accent');
        const btn = document.getElementById('preview-btn');

        if (sp) sp.style.background = primary;
        if (ss) ss.style.background = secondary;
        if (sa) sa.style.background = accent;

        // Border radius
        const radiusMap = { sharp: '2px', rounded: '8px', soft: '16px', pill: '9999px' };
        const radius = radiusMap[document.getElementById('border_radius')?.value] || '8px';

        // Button style
        const btnStyle = document.getElementById('button_style')?.value || 'filled';

        if (btn) {
            btn.style.borderRadius = radius;
            if (btnStyle === 'filled') {
                btn.style.background = primary;
                btn.style.color = 'white';
                btn.style.border = 'none';
            } else if (btnStyle === 'outline') {
                btn.style.background = 'transparent';
                btn.style.color = primary;
                btn.style.border = '2px solid ' + primary;
            } else {
                btn.style.background = 'transparent';
                btn.style.color = primary;
                btn.style.border = 'none';
            }
        }

        // Update swatches radius
        [sp, ss, sa].forEach(function(s) { if (s) s.style.borderRadius = radius; });
    }

    // Font preview with dynamic loading
    const loadedFonts = {};
    function loadGoogleFont(fontName) {
        if (fontName === 'System UI' || loadedFonts[fontName]) return;
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = 'https://fonts.googleapis.com/css2?family=' + encodeURIComponent(fontName) + ':wght@400;500;600;700&display=swap';
        document.head.appendChild(link);
        loadedFonts[fontName] = true;
    }

    function getFontFamily(fontName) {
        if (fontName === 'System UI') return 'system-ui, sans-serif';
        return "'" + fontName + "', system-ui, sans-serif";
    }

    function updateFontPreview() {
        const heading = document.getElementById('font_heading')?.value || 'System UI';
        const body = document.getElementById('font_body')?.value || 'System UI';

        loadGoogleFont(heading);
        loadGoogleFont(body);

        const previewH = document.getElementById('preview-heading');
        const previewB = document.getElementById('preview-body');

        if (previewH) previewH.style.fontFamily = getFontFamily(heading);
        if (previewB) previewB.style.fontFamily = getFontFamily(body);
    }

    // Password strength indicator
    const passwordInput = document.getElementById('admin_password');
    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            const val = this.value;
            let score = 0;
            if (val.length >= 8) score++;
            if (val.length >= 12) score++;
            if (/[a-z]/.test(val) && /[A-Z]/.test(val)) score++;
            if (/\d/.test(val)) score++;
            if (/[^a-zA-Z0-9]/.test(val)) score++;

            const bar = document.getElementById('strength-bar');
            const text = document.getElementById('strength-text');
            const levels = [
                { width: '0%', color: '#e4e4e7', label: '' },
                { width: '20%', color: '#ef4444', label: 'Mycket svagt' },
                { width: '40%', color: '#f59e0b', label: 'Svagt' },
                { width: '60%', color: '#f59e0b', label: 'Medel' },
                { width: '80%', color: '#10b981', label: 'Starkt' },
                { width: '100%', color: '#10b981', label: 'Mycket starkt' },
            ];

            if (bar) {
                bar.style.width = levels[score].width;
                bar.style.background = levels[score].color;
            }
            if (text) {
                text.textContent = levels[score].label;
                text.style.color = levels[score].color;
            }
        });
    }

    // Client-side validation
    document.querySelectorAll('form').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            const step3 = document.getElementById('step3-form');
            if (form === step3) {
                const pw = document.getElementById('admin_password').value;
                const pwc = document.getElementById('admin_password_confirm').value;
                if (pw !== pwc) {
                    e.preventDefault();
                    alert('Lösenorden matchar inte.');
                    return false;
                }
            }
        });
    });

    // Init preview on load
    if (document.getElementById('design-preview')) {
        updatePreview();
        updateFontPreview();
    }
    </script>
</body>
</html>
