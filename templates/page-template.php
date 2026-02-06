<?php
/**
 * [SIDNAMN] Page
 * Kopiera denna fil och döp om till t.ex. om-oss.php, tjanster.php
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../security/session.php';
require_once __DIR__ . '/../cms/content.php';
require_once __DIR__ . '/../seo/meta.php';

// Prevent caching to ensure admin bar updates correctly
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
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

    <?php if (file_exists(__DIR__ . '/../includes/fonts.php')) include __DIR__ . '/../includes/fonts.php'; ?>
    <?php if (file_exists(__DIR__ . '/../assets/images/favicon.png')): ?>
    <link rel="icon" type="image/png" href="/assets/images/favicon.png">
    <?php endif; ?>
    <link rel="stylesheet" href="/assets/css/main.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/admin-bar.php'; ?>
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <main>
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

    <script src="/assets/js/cms.js"></script>
    <?php include __DIR__ . '/../includes/cookie-consent.php'; ?>
</body>
</html>
