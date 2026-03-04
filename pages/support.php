<?php
/**
 * Support Page
 * Support page with contact information and support-focused form
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../security/session.php';
require_once __DIR__ . '/../security/csrf.php';
require_once __DIR__ . '/../security/validation.php';
require_once __DIR__ . '/../cms/content.php';
require_once __DIR__ . '/../seo/meta.php';
require_once __DIR__ . '/../includes/mailer.php';

// Prevent caching
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

$success = false;
$error = '';
$form_data = [
    'name' => '',
    'email' => '',
    'issue_type' => '',
    'priority' => 'normal',
    'message' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();

    // Rate limiting
    if (!isset($_SESSION['support_timestamps'])) {
        $_SESSION['support_timestamps'] = [];
    }

    $cutoff = time() - (15 * 60);
    $_SESSION['support_timestamps'] = array_filter(
        $_SESSION['support_timestamps'],
        fn($ts) => $ts > $cutoff
    );

    if (count($_SESSION['support_timestamps']) >= 5) {
        $error = 'Du har skickat för många meddelanden. Vänta en stund innan du försöker igen.';
    } else {
        $form_data['name'] = trim($_POST['name'] ?? '');
        $form_data['email'] = trim($_POST['email'] ?? '');
        $form_data['issue_type'] = trim($_POST['issue_type'] ?? '');
        $form_data['priority'] = trim($_POST['priority'] ?? 'normal');
        $form_data['message'] = trim($_POST['message'] ?? '');

        // Validera
        if (!validate_text($form_data['name'], 2, 100)) {
            $error = 'Ange ditt namn (minst 2 tecken).';
        } elseif (!validate_email($form_data['email'])) {
            $error = 'Ange en giltig e-postadress.';
        } elseif (empty($form_data['issue_type'])) {
            $error = 'Välj typ av ärende.';
        } elseif (!validate_text($form_data['message'], 10, 5000)) {
            $error = 'Meddelandet måste vara minst 10 tecken.';
        }

        if (empty($error)) {
            $safe_name = htmlspecialchars($form_data['name'], ENT_QUOTES, 'UTF-8');
            $safe_email = htmlspecialchars($form_data['email'], ENT_QUOTES, 'UTF-8');
            $safe_issue_type = htmlspecialchars($form_data['issue_type'], ENT_QUOTES, 'UTF-8');
            $safe_priority = htmlspecialchars($form_data['priority'], ENT_QUOTES, 'UTF-8');
            $safe_message = nl2br(htmlspecialchars($form_data['message'], ENT_QUOTES, 'UTF-8'));
            $site_name = defined('SITE_NAME') ? SITE_NAME : 'Webbplats';

            $htmlBody = <<<HTML
<!DOCTYPE html>
<html lang="sv">
<head><meta charset="UTF-8"></head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #18181b; max-width: 600px; margin: 0 auto; padding: 20px;">
    <h2 style="color: #379b83; border-bottom: 2px solid #379b83; padding-bottom: 10px;">Nytt supportärende</h2>
    <table style="width: 100%; border-collapse: collapse;">
        <tr><td style="padding: 8px 0; font-weight: 600; width: 120px;">Namn:</td><td style="padding: 8px 0;">{$safe_name}</td></tr>
        <tr><td style="padding: 8px 0; font-weight: 600;">E-post:</td><td style="padding: 8px 0;"><a href="mailto:{$safe_email}">{$safe_email}</a></td></tr>
        <tr><td style="padding: 8px 0; font-weight: 600;">Ärendetyp:</td><td style="padding: 8px 0;">{$safe_issue_type}</td></tr>
        <tr><td style="padding: 8px 0; font-weight: 600;">Prioritet:</td><td style="padding: 8px 0;">{$safe_priority}</td></tr>
    </table>
    <h3 style="margin-top: 20px;">Meddelande</h3>
    <div style="background: #f4f4f5; padding: 15px; border-radius: 8px; line-height: 1.6;">{$safe_message}</div>
    <p style="color: #71717a; font-size: 12px; margin-top: 30px;">Skickat via supportformuläret på {$site_name}</p>
</body>
</html>
HTML;

            $mail_subject = '[Support] ' . $form_data['issue_type'];
            $contact_email = defined('CONTACT_EMAIL') ? CONTACT_EMAIL : '';

            if (empty($contact_email)) {
                $error = 'Kontaktadressen är inte konfigurerad. Försök igen senare.';
            } else {
                $sent = send_mail($contact_email, $mail_subject, $htmlBody, [
                    'html' => true,
                    'reply_to' => $form_data['email'],
                ]);

                if ($sent) {
                    $success = true;
                    $_SESSION['support_timestamps'][] = time();

                    try {
                        require_once __DIR__ . '/../cms/tickets-db.php';
                        ticket_create([
                            'source' => 'support',
                            'name' => $form_data['name'],
                            'email' => $form_data['email'],
                            'subject' => $form_data['issue_type'],
                            'message' => $form_data['message'],
                            'priority' => $form_data['priority'],
                        ]);
                    } catch (\Throwable $e) {
                        error_log('Ticket creation failed: ' . $e->getMessage());
                    }

                    $form_data = ['name' => '', 'email' => '', 'issue_type' => '', 'priority' => 'normal', 'message' => ''];
                } else {
                    $error = 'Meddelandet kunde inte skickas just nu. Försök igen senare.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <?php
    generateMeta(
        'Support - ' . (defined('SITE_NAME') ? SITE_NAME : 'Webbplats'),
        'Behöver du hjälp? Kontakta vår support så hjälper vi dig.',
        '/assets/images/og-image.jpg'
    );
    ?>

    <?php if (file_exists(__DIR__ . '/../includes/fonts.php')) include __DIR__ . '/../includes/fonts.php'; ?>
    <?php if (file_exists(__DIR__ . '/../includes/analytics.php')) include __DIR__ . '/../includes/analytics.php'; ?>
    <?php if (file_exists(__DIR__ . '/../assets/images/favicon.png')): ?>
    <link rel="icon" type="image/png" href="/assets/images/favicon.png">
    <?php endif; ?>
    <?php if (file_exists(__DIR__ . '/../assets/images/apple-touch-icon.png')): ?>
    <link rel="apple-touch-icon" href="/assets/images/apple-touch-icon.png">
    <?php endif; ?>
    <link rel="stylesheet" href="/assets/css/main.css?v=<?php echo BOSSE_VERSION; ?>">
    
    <style>
    .support-header {
        text-align: center;
        margin-bottom: var(--spacing-12);
    }

    .support-header__title {
        font-size: 36px;
        font-weight: 600;
        color: var(--color-foreground);
        margin: 0 0 var(--spacing-4) 0;
    }

    .support-header__subtitle {
        font-size: 18px;
        color: var(--color-gray-600);
        margin: 0;
    }

    .support-contact {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: var(--spacing-6);
        margin-bottom: var(--spacing-12);
        padding: var(--spacing-8);
        background: var(--color-gray-50);
        border-radius: var(--radius-xl);
    }

    .support-contact__item {
        display: flex;
        align-items: center;
        gap: var(--spacing-4);
    }

    .support-contact__icon {
        width: 48px;
        height: 48px;
        background: var(--color-primary);
        color: white;
        border-radius: var(--radius-lg);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .support-contact__info {
        flex: 1;
    }

    .support-contact__label {
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        color: var(--color-gray-500);
        letter-spacing: 0.05em;
        margin: 0 0 4px 0;
    }

    .support-contact__value {
        font-size: 16px;
        font-weight: 600;
        color: var(--color-foreground);
        margin: 0;
    }

    .support-contact__value a {
        color: var(--color-foreground);
        text-decoration: none;
    }

    .support-contact__value a:hover {
        color: var(--color-primary);
    }

    @media (max-width: 768px) {
        .support-contact {
            grid-template-columns: 1fr;
        }

        .support-header__title {
            font-size: 28px;
        }
    }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/admin-bar.php'; ?>
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <main id="main-content">
        <section class="section section--white">
            <div class="container" style="max-width: 640px;">
                <div class="support-header">
                    <h1 class="support-header__title">Hur kan vi hjälpa dig?</h1>
                    <p class="support-header__subtitle">Vi svarar vanligtvis inom 24 timmar</p>
                </div>

                <!-- Contact Information -->
                <div class="support-contact">
                    <div class="support-contact__item">
                        <div class="support-contact__icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                <polyline points="22,6 12,13 2,6"/>
                            </svg>
                        </div>
                        <div class="support-contact__info">
                            <p class="support-contact__label">E-post</p>
                            <p class="support-contact__value">
                                <a href="mailto:support@uppdragsbrev.se">support@uppdragsbrev.se</a>
                            </p>
                        </div>
                    </div>

                    <div class="support-contact__item">
                        <div class="support-contact__icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                            </svg>
                        </div>
                        <div class="support-contact__info">
                            <p class="support-contact__label">Telefon</p>
                            <p class="support-contact__value">
                                <a href="tel:+46701234567">+46 70 123 45 67</a>
                            </p>
                        </div>
                    </div>
                </div>

                <?php if ($success): ?>
                <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 0.75rem; padding: 1.5rem; text-align: center; margin-bottom: 2rem;">
                    <p style="font-size: 2rem; margin-bottom: 0.5rem;">✓</p>
                    <h2 style="font-size: 1.25rem; margin-bottom: 0.5rem;">Tack för ditt meddelande!</h2>
                    <p style="color: #15803d;">Vi har tagit emot ditt ärende och återkommer så snart som möjligt.</p>
                </div>
                <?php endif; ?>

                <?php if (!empty($error)): ?>
                <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 0.75rem; padding: 1rem 1.25rem; margin-bottom: 1.5rem;">
                    <p style="color: #dc2626; font-size: 0.875rem; margin: 0;"><?php echo htmlspecialchars($error); ?></p>
                </div>
                <?php endif; ?>

                <?php if (!$success): ?>
                <form method="POST" action="/support" style="background: white; border: 1px solid var(--color-gray-200); border-radius: 1rem; padding: 2rem;">
                    <?php echo csrf_field(); ?>

                    <div style="margin-bottom: 1.25rem;">
                        <label for="name" style="display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.375rem;">Namn *</label>
                        <input type="text" id="name" name="name" required minlength="2" maxlength="100"
                               value="<?php echo htmlspecialchars($form_data['name']); ?>"
                               placeholder="Ditt namn"
                               style="width: 100%; padding: 0.625rem 0.875rem; border: 1px solid var(--color-gray-300); border-radius: 0.5rem; font-size: 0.9375rem; font-family: inherit;">
                    </div>

                    <div style="margin-bottom: 1.25rem;">
                        <label for="email" style="display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.375rem;">E-post *</label>
                        <input type="email" id="email" name="email" required
                               value="<?php echo htmlspecialchars($form_data['email']); ?>"
                               placeholder="din@email.com"
                               style="width: 100%; padding: 0.625rem 0.875rem; border: 1px solid var(--color-gray-300); border-radius: 0.5rem; font-size: 0.9375rem; font-family: inherit;">
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                        <div>
                            <label for="issue_type" style="display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.375rem;">Typ av ärende *</label>
                            <select id="issue_type" name="issue_type" required
                                    style="width: 100%; padding: 0.625rem 0.875rem; border: 1px solid var(--color-gray-300); border-radius: 0.5rem; font-size: 0.9375rem; font-family: inherit;">
                                <option value="">Välj...</option>
                                <option value="Teknisk support" <?php echo $form_data['issue_type'] === 'Teknisk support' ? 'selected' : ''; ?>>Teknisk support</option>
                                <option value="Fråga om funktioner" <?php echo $form_data['issue_type'] === 'Fråga om funktioner' ? 'selected' : ''; ?>>Fråga om funktioner</option>
                                <option value="Fakturering" <?php echo $form_data['issue_type'] === 'Fakturering' ? 'selected' : ''; ?>>Fakturering</option>
                                <option value="Felanmälan" <?php echo $form_data['issue_type'] === 'Felanmälan' ? 'selected' : ''; ?>>Felanmälan</option>
                                <option value="Övrigt" <?php echo $form_data['issue_type'] === 'Övrigt' ? 'selected' : ''; ?>>Övrigt</option>
                            </select>
                        </div>
                        <div>
                            <label for="priority" style="display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.375rem;">Prioritet</label>
                            <select id="priority" name="priority"
                                    style="width: 100%; padding: 0.625rem 0.875rem; border: 1px solid var(--color-gray-300); border-radius: 0.5rem; font-size: 0.9375rem; font-family: inherit;">
                                <option value="low" <?php echo $form_data['priority'] === 'low' ? 'selected' : ''; ?>>Låg</option>
                                <option value="normal" <?php echo $form_data['priority'] === 'normal' ? 'selected' : ''; ?>>Normal</option>
                                <option value="high" <?php echo $form_data['priority'] === 'high' ? 'selected' : ''; ?>>Hög</option>
                                <option value="urgent" <?php echo $form_data['priority'] === 'urgent' ? 'selected' : ''; ?>>Brådskande</option>
                            </select>
                        </div>
                    </div>

                    <div style="margin-bottom: 1.5rem;">
                        <label for="message" style="display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.375rem;">Beskriv ditt ärende *</label>
                        <textarea id="message" name="message" required minlength="10" maxlength="5000" rows="6"
                                  placeholder="Beskriv ditt problem eller din fråga så detaljerat som möjligt..."
                                  style="width: 100%; padding: 0.625rem 0.875rem; border: 1px solid var(--color-gray-300); border-radius: 0.5rem; font-size: 0.9375rem; font-family: inherit; resize: vertical; min-height: 120px;"><?php echo htmlspecialchars($form_data['message']); ?></textarea>
                    </div>

                    <button type="submit" class="button button--primary" style="width: 100%; padding: 0.875rem; font-size: 1rem; cursor: pointer;">
                        Skicka supportärende
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script src="/assets/js/cms.js?v=<?php echo BOSSE_VERSION; ?>"></script>

    <?php if (is_logged_in()): ?>
        <form style="display: none;">
            <?php echo csrf_field(); ?>
        </form>
    <?php endif; ?>

    <?php include __DIR__ . '/../includes/cookie-consent.php'; ?>
</body>
</html>
