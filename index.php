<?php
/**
 * Index Page
 * Huvudsida med exempel på CMS-integration
 */

require_once __DIR__ . '/config.example.php';
require_once __DIR__ . '/security/session.php';
require_once __DIR__ . '/cms/content.php';
require_once __DIR__ . '/seo/meta.php';
require_once __DIR__ . '/seo/schema.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Prevent caching to ensure admin bar updates correctly
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <?php 
    generateMeta(
        get_content('home.meta_title', 'Välkommen'),
        get_content('home.meta_description', 'Modern hemsida med CMS, SEO och säkerhet'),
        '/assets/images/og-image.jpg'
    );
    ?>
    
    <link rel="stylesheet" href="/assets/css/main.css">
    
    <?php 
    echo organizationSchema();
    echo websiteSchema();
    ?>
</head>
<body>
    <?php include __DIR__ . '/includes/admin-bar.php'; ?>
    <?php include __DIR__ . '/includes/header.php'; ?>
    
    <main>
        <!-- Hero Section -->
        <section class="section section--white">
            <div class="container text-center">
                <?php editable_text('hero', 'title', 'Välkommen till vår hemsida', 'h1'); ?>
                <?php editable_text('hero', 'description', 'Vi hjälper dig att nå dina mål med moderna lösningar', 'p', 'text-lg'); ?>
                
                <div style="display: flex; gap: 1rem; justify-content: center; margin-top: 2rem;">
                    <a href="/kontakt" class="button button--primary">
                        <?php echo get_content('hero.cta_primary', 'Kontakta oss'); ?>
                    </a>
                    <a href="/om-oss" class="button button--outline">
                        <?php echo get_content('hero.cta_secondary', 'Läs mer'); ?>
                    </a>
                </div>
            </div>
        </section>
        
        <!-- Features Section -->
        <section class="section section--gray">
            <div class="container">
                <?php editable_text('features.title', 'Våra tjänster', 'h2', 'text-center'); ?>
                <?php editable_text('features.description', 'Vi erbjuder kompletta lösningar för ditt företag', 'p', 'text-center mb-4'); ?>
                
                <div class="grid grid--3" style="margin-top: 3rem;">
                    <div class="card">
                        <?php editable_text('features.feature1.title', 'Tjänst 1', 'h3', 'card__title'); ?>
                        <?php editable_text('features.feature1.description', 'Beskrivning av tjänst 1', 'p', 'card__text'); ?>
                    </div>
                    
                    <div class="card">
                        <?php editable_text('features.feature2.title', 'Tjänst 2', 'h3', 'card__title'); ?>
                        <?php editable_text('features.feature2.description', 'Beskrivning av tjänst 2', 'p', 'card__text'); ?>
                    </div>
                    
                    <div class="card">
                        <?php editable_text('features.feature3.title', 'Tjänst 3', 'h3', 'card__title'); ?>
                        <?php editable_text('features.feature3.description', 'Beskrivning av tjänst 3', 'p', 'card__text'); ?>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- CTA Section -->
        <section class="section section--white">
            <div class="container text-center">
                <?php editable_text('cta.title', 'Redo att komma igång?', 'h2'); ?>
                <?php editable_text('cta.description', 'Kontakta oss idag för en kostnadsfri konsultation', 'p', 'text-lg'); ?>
                
                <a href="/kontakt" class="button button--primary" style="margin-top: 2rem;">
                    <?php echo get_content('cta.button_text', 'Kontakta oss'); ?>
                </a>
            </div>
        </section>
    </main>
    
    <?php include __DIR__ . '/includes/footer.php'; ?>
    <?php include __DIR__ . '/includes/cookie-consent.php'; ?>
    
    <script src="/assets/js/cms.js"></script>
    
    <?php if (is_logged_in()): ?>
        <!-- CSRF token för CMS -->
        <form style="display: none;">
            <?php echo csrf_field(); ?>
        </form>
    <?php endif; ?>
</body>
</html>
