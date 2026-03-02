<?php
/**
 * [SIDNAMN] Page
 * Kopiera denna fil och döp om till t.ex. om-oss.php, tjanster.php
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../security/session.php';
require_once __DIR__ . '/../cms/content.php';
require_once __DIR__ . '/../seo/meta.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' || is_logged_in()) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
} else {
    header('Cache-Control: public, max-age=300, must-revalidate');
    header_remove('Pragma');
}
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <?php
    generateMeta(
        get_content('sidnamn.meta_title', 'Sidtitel | ' . SITE_NAME),
        get_content('sidnamn.meta_description', 'Beskrivning av sidan')
    );
    ?>

    <?php if (file_exists(__DIR__ . '/../assets/images/favicon.ico')): ?>
    <link rel="icon" href="/assets/images/favicon.ico" sizes="32x32">
    <?php endif; ?>
    <?php if (file_exists(__DIR__ . '/../assets/images/favicon.svg')): ?>
    <link rel="icon" href="/assets/images/favicon.svg" type="image/svg+xml">
    <?php elseif (file_exists(__DIR__ . '/../assets/images/favicon.png')): ?>
    <link rel="icon" type="image/png" href="/assets/images/favicon.png">
    <?php endif; ?>
    <?php if (file_exists(__DIR__ . '/../assets/images/apple-touch-icon.png')): ?>
    <link rel="apple-touch-icon" href="/assets/images/apple-touch-icon.png">
    <?php endif; ?>
    <link rel="stylesheet" href="/assets/css/main.css?v=<?php echo BOSSE_VERSION; ?>">
    <?php if (file_exists(__DIR__ . '/../includes/fonts.php')) include __DIR__ . '/../includes/fonts.php'; ?>
</head>
<body>
    <?php include __DIR__ . '/../includes/admin-bar.php'; ?>
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <main id="main-content">
        <!-- Hero Section -->
        <section class="section section--white">
            <div class="container">
                <?php editable_text('sidnamn', 'title', 'Sidtitel', 'h1'); ?>
                <?php editable_text('sidnamn', 'description', 'Beskrivning av sidan', 'p', 'text-lg'); ?>
            </div>
        </section>

        <!-- Content Section -->
        <section class="section section--gray">
            <div class="container">
                <?php editable_text('sidnamn', 'content_title', 'Innehåll', 'h2'); ?>
                <?php editable_text('sidnamn', 'content_text', 'Lägg till innehåll här...', 'p'); ?>
            </div>
        </section>
    </main>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script src="/assets/js/cms.js?v=<?php echo BOSSE_VERSION; ?>" defer></script>
    <?php include __DIR__ . '/../includes/cookie-consent.php'; ?>
</body>
</html>
