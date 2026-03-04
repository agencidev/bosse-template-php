<?php
/**
 * Kontaktsida
 * Publik kontaktsida med formulär — ingen inloggning krävs
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../security/session.php';
require_once __DIR__ . '/../security/csrf.php';
require_once __DIR__ . '/../security/validation.php';
require_once __DIR__ . '/../cms/content.php';
require_once __DIR__ . '/../seo/meta.php';
require_once __DIR__ . '/../includes/mailer.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' || is_logged_in()) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
} else {
    header('Cache-Control: public, max-age=300, must-revalidate');
    header_remove('Pragma');
}

$success = false;
$error = '';
$form_data = [
    'name' => '',
    'email' => '',
    'phone' => '',
    'subject' => '',
    'message' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();

    // Honeypot check — hidden field should be empty
    if (!empty($_POST['website'] ?? '')) {
        // Bot detected — silently accept
        $success = true;
    }

    if (!$success) {
    // Rate limiting: IP-based, max 5 meddelanden per 15 min
    $rateLimitFile = __DIR__ . '/../data/contact_rate.json';
    $rateLimits = [];
    if (file_exists($rateLimitFile)) {
        $rateLimits = json_decode(file_get_contents($rateLimitFile), true) ?? [];
    }

    $clientIp = hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''));
    $cutoff = time() - (15 * 60);

    // Clean old entries
    foreach ($rateLimits as $ip => $timestamps) {
        $rateLimits[$ip] = array_values(array_filter($timestamps, fn($ts) => $ts > $cutoff));
        if (empty($rateLimits[$ip])) {
            unset($rateLimits[$ip]);
        }
    }

    $ipTimestamps = $rateLimits[$clientIp] ?? [];

    if (count($ipTimestamps) >= 5) {
        $error = 'Du har skickat för många meddelanden. Vänta en stund innan du försöker igen.';
    } else {
        // Hämta och sanitera data
        $form_data['name'] = trim($_POST['name'] ?? '');
        $form_data['email'] = trim($_POST['email'] ?? '');
        $form_data['phone'] = trim($_POST['phone'] ?? '');
        $form_data['subject'] = trim($_POST['subject'] ?? '');
        $form_data['message'] = trim($_POST['message'] ?? '');

        // Validera
        if (!validate_text($form_data['name'], 2, 100)) {
            $error = 'Ange ditt namn (minst 2 tecken).';
        } elseif (!validate_email($form_data['email'])) {
            $error = 'Ange en giltig e-postadress.';
        } elseif (!validate_text($form_data['subject'], 2, 200)) {
            $error = 'Ange ett ämne (minst 2 tecken).';
        } elseif (!validate_text($form_data['message'], 10, 5000)) {
            $error = 'Meddelandet måste vara minst 10 tecken.';
        } elseif (!empty($form_data['phone']) && !validate_phone($form_data['phone'])) {
            $error = 'Ange ett giltigt telefonnummer.';
        }

        if (empty($error)) {
            // Bygg HTML-mail
            $safe_name = htmlspecialchars($form_data['name'], ENT_QUOTES, 'UTF-8');
            $safe_email = htmlspecialchars($form_data['email'], ENT_QUOTES, 'UTF-8');
            $safe_phone = htmlspecialchars($form_data['phone'], ENT_QUOTES, 'UTF-8');
            $safe_subject = htmlspecialchars($form_data['subject'], ENT_QUOTES, 'UTF-8');
            $safe_message = nl2br(htmlspecialchars($form_data['message'], ENT_QUOTES, 'UTF-8'));
            $site_name = defined('SITE_NAME') ? SITE_NAME : 'Webbplats';

            $htmlBody = <<<HTML
<!DOCTYPE html>
<html lang="sv">
<head><meta charset="UTF-8"></head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #18181b; max-width: 600px; margin: 0 auto; padding: 20px;">
    <h2 style="color: #8b5cf6; border-bottom: 2px solid #8b5cf6; padding-bottom: 10px;">Nytt kontaktmeddelande</h2>
    <table style="width: 100%; border-collapse: collapse;">
        <tr><td style="padding: 8px 0; font-weight: 600; width: 120px;">Namn:</td><td style="padding: 8px 0;">{$safe_name}</td></tr>
        <tr><td style="padding: 8px 0; font-weight: 600;">E-post:</td><td style="padding: 8px 0;"><a href="mailto:{$safe_email}">{$safe_email}</a></td></tr>
        <tr><td style="padding: 8px 0; font-weight: 600;">Telefon:</td><td style="padding: 8px 0;">{$safe_phone}</td></tr>
        <tr><td style="padding: 8px 0; font-weight: 600;">Ämne:</td><td style="padding: 8px 0;">{$safe_subject}</td></tr>
    </table>
    <h3 style="margin-top: 20px;">Meddelande</h3>
    <div style="background: #f4f4f5; padding: 15px; border-radius: 8px; line-height: 1.6;">{$safe_message}</div>
    <p style="color: #71717a; font-size: 12px; margin-top: 30px;">Skickat via kontaktformuläret på {$site_name}</p>
</body>
</html>
HTML;

            $mail_subject = '[Kontaktformulär] ' . $form_data['subject'];
            $contact_email = defined('CONTACT_EMAIL') ? CONTACT_EMAIL : '';

            if (empty($contact_email)) {
                $error = 'Kontaktadressen är inte konfigurerad. Försök igen senare.';
            } else {
                $sent = send_mail($contact_email, $mail_subject, $htmlBody, [
                    'html' => true,
                    'reply_to' => $form_data['email'],
                ]);

                // Save as ticket FIRST (before mail, so messages never disappear)
                try {
                    require_once __DIR__ . '/../cms/tickets-db.php';
                    ticket_create([
                        'source' => 'contact',
                        'name' => $form_data['name'],
                        'email' => $form_data['email'],
                        'phone' => $form_data['phone'],
                        'subject' => $form_data['subject'],
                        'message' => $form_data['message'],
                    ]);
                } catch (\Throwable $e) {
                    error_log('Ticket creation failed: ' . $e->getMessage());
                }

                if ($sent) {
                    $success = true;

                    // Update IP rate limit
                    $ipTimestamps[] = time();
                    $rateLimits[$clientIp] = $ipTimestamps;
                    $tmpFile = $rateLimitFile . '.tmp.' . getmypid();
                    file_put_contents($tmpFile, json_encode($rateLimits), LOCK_EX);
                    rename($tmpFile, $rateLimitFile);

                    // Rensa formulärdata vid lyckad sändning
                    $form_data = ['name' => '', 'email' => '', 'phone' => '', 'subject' => '', 'message' => ''];
                } else {
                    $success = true; // Still show success — ticket was created
                    $form_data = ['name' => '', 'email' => '', 'phone' => '', 'subject' => '', 'message' => ''];
                }
            }
        }
    }
    } // end if (!$success) honeypot
}
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <?php
    generateMeta(
        'Kontakt - ' . (defined('SITE_NAME') ? SITE_NAME : 'Webbplats'),
        'Kontakta oss för frågor, offertförfrågan eller support.',
        '/assets/images/og-image.jpg'
    );
    ?>

    <?php if (file_exists(__DIR__ . '/../assets/images/favicon.png')): ?>
    <link rel="icon" type="image/png" href="/assets/images/favicon.png">
    <?php endif; ?>
    <?php if (file_exists(__DIR__ . '/../assets/images/apple-touch-icon.png')): ?>
    <link rel="apple-touch-icon" href="/assets/images/apple-touch-icon.png">
    <?php endif; ?>
    <link rel="stylesheet" href="/assets/css/main.css?v=<?php echo BOSSE_VERSION; ?>">
    <?php if (file_exists(__DIR__ . '/../includes/fonts.php')) include __DIR__ . '/../includes/fonts.php'; ?>
    <?php if (file_exists(__DIR__ . '/../includes/analytics.php')) include __DIR__ . '/../includes/analytics.php'; ?>
</head>
<body>
    <?php include __DIR__ . '/../includes/admin-bar.php'; ?>
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <main id="main-content">
        <section class="section section--white">
            <div class="container" style="max-width: 640px;">
                <h1 class="text-center" style="margin-bottom: 0.5rem;">Kontakta oss</h1>
                <p class="text-center text-lg" style="color: var(--color-gray-500); margin-bottom: 2rem;">
                    Har du frågor eller vill veta mer? Fyll i formuläret så återkommer vi.
                </p>

                <?php if ($success): ?>
                <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 0.75rem; padding: 1.5rem; text-align: center; margin-bottom: 2rem;">
                    <p style="font-size: 2rem; margin-bottom: 0.5rem;">&#10003;</p>
                    <h2 style="font-size: 1.25rem; margin-bottom: 0.5rem;">Tack för ditt meddelande!</h2>
                    <p style="color: #15803d;">Vi har tagit emot ditt meddelande och återkommer så snart som möjligt.</p>
                </div>
                <?php endif; ?>

                <?php if (!empty($error)): ?>
                <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 0.75rem; padding: 1rem 1.25rem; margin-bottom: 1.5rem;">
                    <p style="color: #dc2626; font-size: 0.875rem; margin: 0;"><?php echo htmlspecialchars($error); ?></p>
                </div>
                <?php endif; ?>

                <?php if (!$success): ?>
                <form method="POST" action="/kontakt" style="background: white; border: 1px solid var(--color-gray-200); border-radius: 1rem; padding: 2rem;">
                    <?php echo csrf_field(); ?>
                    <div style="position: absolute; left: -9999px;" aria-hidden="true">
                        <input type="text" name="website" tabindex="-1" autocomplete="off">
                    </div>

                    <div style="margin-bottom: 1.25rem;">
                        <label for="name" style="display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.375rem;">Namn *</label>
                        <input type="text" id="name" name="name" required minlength="2" maxlength="100"
                               value="<?php echo htmlspecialchars($form_data['name']); ?>"
                               placeholder="Ditt namn"
                               style="width: 100%; padding: 0.625rem 0.875rem; border: 1px solid var(--color-gray-300); border-radius: 0.5rem; font-size: 0.9375rem; font-family: inherit;">
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;" class="kontakt-grid">
                        <div>
                            <label for="email" style="display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.375rem;">E-post *</label>
                            <input type="email" id="email" name="email" required
                                   value="<?php echo htmlspecialchars($form_data['email']); ?>"
                                   placeholder="din@email.com"
                                   style="width: 100%; padding: 0.625rem 0.875rem; border: 1px solid var(--color-gray-300); border-radius: 0.5rem; font-size: 0.9375rem; font-family: inherit;">
                        </div>
                        <div>
                            <label for="phone" style="display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.375rem;">Telefon</label>
                            <input type="tel" id="phone" name="phone"
                                   value="<?php echo htmlspecialchars($form_data['phone']); ?>"
                                   placeholder="+46 70 000 00 00"
                                   style="width: 100%; padding: 0.625rem 0.875rem; border: 1px solid var(--color-gray-300); border-radius: 0.5rem; font-size: 0.9375rem; font-family: inherit;">
                        </div>
                    </div>

                    <div style="margin-bottom: 1.25rem;">
                        <label for="subject" style="display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.375rem;">Ämne *</label>
                        <input type="text" id="subject" name="subject" required minlength="2" maxlength="200"
                               value="<?php echo htmlspecialchars($form_data['subject']); ?>"
                               placeholder="Vad gäller det?"
                               style="width: 100%; padding: 0.625rem 0.875rem; border: 1px solid var(--color-gray-300); border-radius: 0.5rem; font-size: 0.9375rem; font-family: inherit;">
                    </div>

                    <div style="margin-bottom: 1.5rem;">
                        <label for="message" style="display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.375rem;">Meddelande *</label>
                        <textarea id="message" name="message" required minlength="10" maxlength="5000" rows="6"
                                  placeholder="Beskriv ditt ärende..."
                                  style="width: 100%; padding: 0.625rem 0.875rem; border: 1px solid var(--color-gray-300); border-radius: 0.5rem; font-size: 0.9375rem; font-family: inherit; resize: vertical; min-height: 120px;"><?php echo htmlspecialchars($form_data['message']); ?></textarea>
                    </div>

                    <button type="submit" class="button button--primary" style="width: 100%; padding: 0.875rem; font-size: 1rem; cursor: pointer;">
                        Skicka meddelande
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script src="/assets/js/cms.js?v=<?php echo BOSSE_VERSION; ?>" defer></script>

    <?php if (is_logged_in()): ?>
        <form style="display: none;">
            <?php echo csrf_field(); ?>
        </form>
    <?php endif; ?>

    <?php include __DIR__ . '/../includes/cookie-consent.php'; ?>
</body>
</html>
