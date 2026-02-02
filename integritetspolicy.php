<?php
/**
 * Integritetspolicy (Privacy Policy)
 * GDPR-kompatibel integritetspolicy
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
        'Integritetspolicy - ' . $company_name,
        'Läs om hur ' . $company_name . ' hanterar dina personuppgifter i enlighet med GDPR.',
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
    <link rel="stylesheet" href="/assets/css/main.css">
</head>
<body>
    <?php include __DIR__ . '/includes/admin-bar.php'; ?>
    <?php include __DIR__ . '/includes/header.php'; ?>

    <main>
        <section class="section section--white">
            <div class="container" style="max-width: 780px;">
                <h1 style="margin-bottom: 0.5rem;">Integritetspolicy</h1>
                <p style="color: var(--color-gray-500); margin-bottom: 2.5rem;">Senast uppdaterad: <?php echo date('Y-m-d'); ?></p>

                <div class="policy-content">
                    <h2>1. Inledning</h2>
                    <p><strong><?php echo htmlspecialchars($company_name); ?></strong> ("vi", "oss", "vår") respekterar din integritet och är engagerade i att skydda dina personuppgifter. Denna integritetspolicy beskriver hur vi samlar in, använder och skyddar information som vi erhåller via <strong><?php echo htmlspecialchars($site_url); ?></strong>.</p>
                    <p>Vi behandlar personuppgifter i enlighet med EU:s dataskyddsförordning (GDPR) och tillämpliga nationella bestämmelser.</p>

                    <h2>2. Personuppgiftsansvarig</h2>
                    <p>Personuppgiftsansvarig för behandlingen är:</p>
                    <ul>
                        <li><strong>Företag:</strong> <?php echo htmlspecialchars($company_name); ?></li>
                        <li><strong>E-post:</strong> <a href="mailto:<?php echo htmlspecialchars($contact_email); ?>"><?php echo htmlspecialchars($contact_email); ?></a></li>
                        <?php if (!empty($contact_phone)): ?>
                        <li><strong>Telefon:</strong> <?php echo htmlspecialchars($contact_phone); ?></li>
                        <?php endif; ?>
                    </ul>

                    <h2>3. Vilka personuppgifter vi samlar in</h2>
                    <p>Vi kan samla in och behandla följande personuppgifter:</p>

                    <h3>Kontaktformulär</h3>
                    <p>När du kontaktar oss via vårt kontaktformulär samlar vi in:</p>
                    <ul>
                        <li>Namn</li>
                        <li>E-postadress</li>
                        <li>Telefonnummer (frivilligt)</li>
                        <li>Meddelande</li>
                    </ul>

                    <h3>Teknisk data</h3>
                    <p>Vid besök på webbplatsen kan följande teknisk data samlas in automatiskt:</p>
                    <ul>
                        <li>IP-adress (anonymiserad om Analytics används)</li>
                        <li>Webbläsartyp och version</li>
                        <li>Operativsystem</li>
                        <li>Besökta sidor och tidpunkt</li>
                        <li>Referens-URL</li>
                    </ul>

                    <h3>Cookies</h3>
                    <p>Vi använder cookies på vår webbplats. För fullständig information om vilka cookies vi använder, se vår <a href="/cookies">cookie policy</a>.</p>

                    <h2>4. Hur vi använder dina uppgifter</h2>
                    <p>Vi behandlar dina personuppgifter för följande ändamål:</p>
                    <table class="policy-table">
                        <thead>
                            <tr>
                                <th>Ändamål</th>
                                <th>Rättslig grund</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Besvara kontaktförfrågningar</td>
                                <td>Berättigat intresse (Art. 6.1f GDPR)</td>
                            </tr>
                            <tr>
                                <td>Webbplatsens funktionalitet</td>
                                <td>Berättigat intresse (Art. 6.1f GDPR)</td>
                            </tr>
                            <tr>
                                <td>Webbanalys (om samtycke ges)</td>
                                <td>Samtycke (Art. 6.1a GDPR)</td>
                            </tr>
                            <tr>
                                <td>Marknadsföring (om samtycke ges)</td>
                                <td>Samtycke (Art. 6.1a GDPR)</td>
                            </tr>
                        </tbody>
                    </table>

                    <h2>5. Hur länge vi sparar dina uppgifter</h2>
                    <p>Vi sparar personuppgifter bara så länge det behövs för att uppfylla ändamålet:</p>
                    <ul>
                        <li><strong>Kontaktförfrågningar:</strong> Sparas i upp till 12 månader efter att ärendet är avslutat</li>
                        <li><strong>Teknisk data:</strong> Loggar sparas i upp till 90 dagar</li>
                        <li><strong>Cookie-samtycke:</strong> Sparas i 365 dagar</li>
                    </ul>

                    <h2>6. Tredjeparter och delning av uppgifter</h2>
                    <p>Vi delar inte dina personuppgifter med tredje parter, med undantag för:</p>
                    <ul>
                        <li><strong>Webbhotell/hosting:</strong> För att driva webbplatsen</li>
                        <li><strong>Google Analytics:</strong> Om du samtycker till analytiska cookies (data anonymiseras)</li>
                        <li><strong>E-posttjänst:</strong> För att leverera meddelanden från kontaktformuläret</li>
                    </ul>
                    <p>Vi överför inte personuppgifter utanför EU/EES utan lämpliga skyddsåtgärder.</p>

                    <h2>7. Dina rättigheter</h2>
                    <p>Enligt GDPR har du följande rättigheter:</p>
                    <ul>
                        <li><strong>Rätt till tillgång:</strong> Du kan begära en kopia av dina personuppgifter</li>
                        <li><strong>Rätt till rättelse:</strong> Du kan begära att felaktiga uppgifter rättas</li>
                        <li><strong>Rätt till radering:</strong> Du kan begära att dina uppgifter tas bort ("rätten att bli glömd")</li>
                        <li><strong>Rätt till begränsning:</strong> Du kan begära att behandlingen begränsas</li>
                        <li><strong>Rätt till dataportabilitet:</strong> Du kan begära att dina uppgifter överförs till annan part</li>
                        <li><strong>Rätt att invända:</strong> Du kan invända mot behandling baserad på berättigat intresse</li>
                        <li><strong>Rätt att återkalla samtycke:</strong> Du kan när som helst återkalla ett givet samtycke</li>
                    </ul>
                    <p>För att utöva dina rättigheter, kontakta oss på <a href="mailto:<?php echo htmlspecialchars($contact_email); ?>"><?php echo htmlspecialchars($contact_email); ?></a>.</p>

                    <h2>8. Säkerhet</h2>
                    <p>Vi vidtar lämpliga tekniska och organisatoriska åtgärder för att skydda dina personuppgifter, inklusive:</p>
                    <ul>
                        <li>SSL/TLS-kryptering av datatrafik</li>
                        <li>CSRF-skydd på alla formulär</li>
                        <li>Krypterade lösenord (bcrypt)</li>
                        <li>Begränsning av åtkomst till personuppgifter</li>
                        <li>Regelbunden säkerhetsöversyn</li>
                    </ul>

                    <h2>9. Klagomål</h2>
                    <p>Om du anser att vi behandlar dina personuppgifter i strid med GDPR har du rätt att lämna in ett klagomål till tillsynsmyndigheten:</p>
                    <p><strong>Integritetsskyddsmyndigheten (IMY)</strong><br>
                    Box 8114, 104 20 Stockholm<br>
                    <a href="https://www.imy.se" target="_blank" rel="noopener">www.imy.se</a></p>

                    <h2>10. Ändringar i denna policy</h2>
                    <p>Vi kan komma att uppdatera denna integritetspolicy. Vid väsentliga ändringar informerar vi dig via webbplatsen. Vi rekommenderar att du regelbundet granskar denna sida.</p>

                    <h2>Kontakt</h2>
                    <p>Har du frågor om hur vi hanterar dina personuppgifter? Kontakta oss:</p>
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

    <script src="/assets/js/cms.js"></script>

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

    .policy-table {
        width: 100%;
        border-collapse: collapse;
        margin: 1rem 0 1.5rem;
        font-size: var(--text-sm);
    }

    .policy-table th {
        background: var(--color-gray-50);
        padding: 0.75rem 1rem;
        text-align: left;
        font-weight: var(--font-semibold);
        color: var(--color-foreground);
        border-bottom: 2px solid var(--color-gray-200);
    }

    .policy-table td {
        padding: 0.625rem 1rem;
        border-bottom: 1px solid var(--color-gray-200);
        color: var(--color-gray-600);
    }
    </style>
</body>
</html>
