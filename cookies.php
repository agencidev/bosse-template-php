<?php
/**
 * Cookie Policy
 * Fristående cookie-policy-sida
 */

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/security/session.php';
require_once __DIR__ . '/cms/content.php';
require_once __DIR__ . '/seo/meta.php';

// Prevent caching
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

$company_name = defined('SITE_NAME') ? SITE_NAME : 'Företaget';
$contact_email = defined('CONTACT_EMAIL') ? CONTACT_EMAIL : 'info@example.com';
$contact_phone = defined('CONTACT_PHONE') ? CONTACT_PHONE : '';
$site_url = defined('SITE_URL') ? SITE_URL : 'https://example.com';
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <?php
    generateMeta(
        'Cookie Policy - ' . $company_name,
        'Information om hur ' . $company_name . ' använder cookies på webbplatsen.',
        '/assets/images/og-image.jpg'
    );
    ?>

    <?php if (file_exists(__DIR__ . '/includes/fonts.php')) include __DIR__ . '/includes/fonts.php'; ?>
    <?php if (file_exists(__DIR__ . '/includes/analytics.php')) include __DIR__ . '/includes/analytics.php'; ?>
    <?php if (file_exists(__DIR__ . '/assets/images/favicon.png')): ?>
    <link rel="icon" type="image/png" href="/assets/images/favicon.png">
    <?php endif; ?>
    <?php if (file_exists(__DIR__ . '/assets/images/apple-touch-icon.png')): ?>
    <link rel="apple-touch-icon" href="/assets/images/apple-touch-icon.png">
    <?php endif; ?>
    <link rel="stylesheet" href="/assets/css/main.css?v=<?php echo BOSSE_VERSION; ?>">
</head>
<body>
    <?php include __DIR__ . '/includes/admin-bar.php'; ?>
    <?php include __DIR__ . '/includes/header.php'; ?>

    <main>
        <section class="section section--white">
            <div class="container" style="max-width: 780px;">
                <h1 style="margin-bottom: 0.5rem;">Cookie Policy</h1>
                <p style="color: var(--color-gray-500); margin-bottom: 2.5rem;">Senast uppdaterad: <?php echo date('Y-m-d'); ?></p>

                <div class="policy-content">
                    <h2>Vad är cookies?</h2>
                    <p>Cookies är små textfiler som lagras i din webbläsare när du besöker en webbplats. De används för att webbplatsen ska fungera korrekt, för att förbättra din upplevelse och för att samla in anonymiserad statistik.</p>

                    <h2>Hur vi använder cookies</h2>
                    <p><strong><?php echo htmlspecialchars($company_name); ?></strong> använder cookies på <strong><?php echo htmlspecialchars($site_url); ?></strong> för följande ändamål:</p>

                    <h3>Nödvändiga cookies</h3>
                    <p>Dessa cookies krävs för att webbplatsen ska fungera korrekt och kan inte stängas av. De hanterar bland annat:</p>
                    <ul>
                        <li>Sessionshantering (inloggning, formulär)</li>
                        <li>CSRF-skydd (säkerhet mot förfalskade förfrågningar)</li>
                        <li>Cookie-samtycke (sparar dina cookieval)</li>
                    </ul>
                    <table class="cookie-table">
                        <thead>
                            <tr>
                                <th>Cookie</th>
                                <th>Syfte</th>
                                <th>Lagringstid</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>PHPSESSID</code></td>
                                <td>Sessionsidentifierare</td>
                                <td>Sessionen</td>
                            </tr>
                            <tr>
                                <td><code>cookie_consent</code></td>
                                <td>Sparar dina cookieval</td>
                                <td>365 dagar</td>
                            </tr>
                            <tr>
                                <td><code>csrf_token</code></td>
                                <td>Skydd mot CSRF-attacker</td>
                                <td>Sessionen</td>
                            </tr>
                        </tbody>
                    </table>

                    <h3>Analytiska cookies</h3>
                    <p>Hjälper oss att förstå hur besökare interagerar med webbplatsen genom att samla in och rapportera information anonymt. Dessa cookies aktiveras bara om du ger samtycke.</p>
                    <table class="cookie-table">
                        <thead>
                            <tr>
                                <th>Cookie</th>
                                <th>Syfte</th>
                                <th>Lagringstid</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>_ga</code></td>
                                <td>Google Analytics - särskiljer användare</td>
                                <td>2 år</td>
                            </tr>
                            <tr>
                                <td><code>_ga_*</code></td>
                                <td>Google Analytics - sessionstatus</td>
                                <td>2 år</td>
                            </tr>
                        </tbody>
                    </table>

                    <h3>Funktionella cookies</h3>
                    <p>Gör det möjligt för webbplatsen att komma ihåg val du gör (t.ex. språk, region) och ge förbättrade, mer personliga funktioner. Dessa cookies aktiveras bara om du ger samtycke.</p>

                    <h3>Marknadsföringscookies</h3>
                    <p>Används för att spåra besökare över webbplatser för att visa relevanta annonser. Dessa cookies aktiveras bara om du ger samtycke och kan inkludera:</p>
                    <ul>
                        <li>Google Ads-cookies</li>
                        <li>Facebook Pixel-cookies</li>
                        <li>LinkedIn Insight Tag</li>
                    </ul>

                    <h2>Google Consent Mode v2</h2>
                    <p>Vi använder Google Consent Mode v2 för att respektera dina cookieval. Det innebär att Google-tjänster (Analytics, Ads) anpassar sitt beteende baserat på ditt samtycke. Om du inte samtycker till analytiska eller marknadsföringscookies skickas ingen personlig data till Google.</p>

                    <h2>Hantera dina cookies</h2>
                    <p>Du kan när som helst ändra dina cookie-inställningar genom att klicka på knappen nedan:</p>
                    <p>
                        <button onclick="document.getElementById('cookie-settings-modal').style.display='flex';" class="button button--outline" style="cursor: pointer;">
                            Hantera cookie-inställningar
                        </button>
                    </p>
                    <p>Du kan också ta bort cookies via din webbläsares inställningar. Observera att om du blockerar nödvändiga cookies kan webbplatsen sluta fungera korrekt.</p>

                    <h3>Så tar du bort cookies i din webbläsare</h3>
                    <ul>
                        <li><strong>Chrome:</strong> Inställningar &rarr; Sekretess och säkerhet &rarr; Cookies</li>
                        <li><strong>Firefox:</strong> Inställningar &rarr; Sekretess &amp; säkerhet &rarr; Cookies och webbplatsdata</li>
                        <li><strong>Safari:</strong> Inställningar &rarr; Sekretess &rarr; Hantera webbplatsdata</li>
                        <li><strong>Edge:</strong> Inställningar &rarr; Cookies och webbplatsbehörigheter</li>
                    </ul>

                    <h2>Ditt samtycke</h2>
                    <p>Ditt samtycke sparas i 365 dagar. Efter det kommer du att tillfrågas igen. Du kan när som helst ändra eller återkalla ditt samtycke via cookie-inställningarna.</p>

                    <h2>Kontakt</h2>
                    <p>Har du frågor om vår cookie policy? Kontakta oss:</p>
                    <ul>
                        <li>E-post: <a href="mailto:<?php echo htmlspecialchars($contact_email); ?>"><?php echo htmlspecialchars($contact_email); ?></a></li>
                        <?php if (!empty($contact_phone)): ?>
                        <li>Telefon: <?php echo htmlspecialchars($contact_phone); ?></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </section>
    </main>

    <?php include __DIR__ . '/includes/footer.php'; ?>

    <script src="/assets/js/cms.js?v=<?php echo BOSSE_VERSION; ?>"></script>

    <?php if (is_logged_in()): ?>
        <form style="display: none;">
            <?php echo csrf_field(); ?>
        </form>
    <?php endif; ?>

    <?php include __DIR__ . '/includes/cookie-consent.php'; ?>

    <style>
    .policy-content h2 {
        font-size: var(--text-xl);
        font-weight: var(--font-semibold);
        margin-top: 2rem;
        margin-bottom: 0.75rem;
        color: var(--color-foreground);
    }

    .policy-content h3 {
        font-size: var(--text-lg);
        font-weight: var(--font-medium);
        margin-top: 1.5rem;
        margin-bottom: 0.5rem;
        color: var(--color-foreground);
    }

    .policy-content p {
        color: var(--color-gray-600);
        line-height: var(--leading-relaxed);
        margin-bottom: 1rem;
    }

    .policy-content ul {
        color: var(--color-gray-600);
        line-height: var(--leading-relaxed);
        margin-bottom: 1rem;
        padding-left: 1.5rem;
    }

    .policy-content ul li {
        margin-bottom: 0.375rem;
    }

    .policy-content a {
        color: var(--color-primary);
        text-decoration: none;
    }

    .policy-content a:hover {
        text-decoration: underline;
    }

    .cookie-table {
        width: 100%;
        border-collapse: collapse;
        margin: 1rem 0 1.5rem;
        font-size: var(--text-sm);
    }

    .cookie-table th {
        background: var(--color-gray-50);
        padding: 0.75rem 1rem;
        text-align: left;
        font-weight: var(--font-semibold);
        color: var(--color-foreground);
        border-bottom: 2px solid var(--color-gray-200);
    }

    .cookie-table td {
        padding: 0.625rem 1rem;
        border-bottom: 1px solid var(--color-gray-200);
        color: var(--color-gray-600);
    }

    .cookie-table code {
        background: var(--color-gray-100);
        padding: 0.125rem 0.375rem;
        border-radius: 0.25rem;
        font-size: var(--text-xs);
        font-family: var(--font-mono);
    }
    </style>
</body>
</html>
