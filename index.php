<?php
/**
 * Index Page
 * Huvudsida med exempel på CMS-integration
 */

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/security/session.php';
require_once __DIR__ . '/cms/content.php';
require_once __DIR__ . '/seo/meta.php';
require_once __DIR__ . '/seo/schema.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' || is_logged_in()) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
} else {
    header('Cache-Control: public, max-age=300, must-revalidate');
    header_remove('Pragma');
}
// Front controller for custom routes (fallback from .htaccess)
$_fc_uri = '/' . trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
if ($_fc_uri !== '' && $_fc_uri !== '/') {
    $_fc_routes = [];
    if (file_exists(__DIR__ . '/cms/extensions/routes.php')) {
        $_fc_routes = include __DIR__ . '/cms/extensions/routes.php';
    }
    if (is_array($_fc_routes)) {
        // Resolve route path (handles both relative and absolute paths)
        $_fc_resolve = function($path) {
            return file_exists($path) ? $path : __DIR__ . $path;
        };
        // Static route match
        if (isset($_fc_routes[$_fc_uri])) {
            require $_fc_resolve($_fc_routes[$_fc_uri]);
            exit;
        }
        // Dynamic pattern match (e.g. /blogg/{slug})
        if (isset($_fc_routes['__patterns'])) {
            foreach ($_fc_routes['__patterns'] as $_fc_pattern) {
                if (preg_match($_fc_pattern[0], $_fc_uri, $_fc_matches)) {
                    if (isset($_fc_pattern[2]) && is_array($_fc_pattern[2])) {
                        foreach ($_fc_pattern[2] as $_fc_param => $_fc_index) {
                            $_GET[$_fc_param] = $_fc_matches[$_fc_index];
                        }
                    }
                    $_fc_target = $_fc_resolve($_fc_pattern[1]);
                    if (file_exists($_fc_target)) {
                        require $_fc_target;
                        exit;
                    }
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
        get_content('home.meta_title', 'Välkommen'),
        get_content('home.meta_description', 'Modern hemsida med CMS, SEO och säkerhet'),
        '/assets/images/og-image.jpg'
    );
    ?>
    
    <?php if (file_exists(__DIR__ . '/assets/images/favicon.ico')): ?>
    <link rel="icon" href="/assets/images/favicon.ico" sizes="32x32">
    <?php endif; ?>
    <?php if (file_exists(__DIR__ . '/assets/images/favicon.svg')): ?>
    <link rel="icon" href="/assets/images/favicon.svg" type="image/svg+xml">
    <?php elseif (file_exists(__DIR__ . '/assets/images/favicon.png')): ?>
    <link rel="icon" type="image/png" href="/assets/images/favicon.png">
    <?php endif; ?>
    <?php if (file_exists(__DIR__ . '/assets/images/apple-touch-icon.png')): ?>
    <link rel="apple-touch-icon" href="/assets/images/apple-touch-icon.png">
    <?php endif; ?>
    <link rel="stylesheet" href="/assets/css/main.css?v=<?php echo BOSSE_VERSION; ?>">
    <?php if (file_exists(__DIR__ . '/includes/fonts.php')) include __DIR__ . '/includes/fonts.php'; ?>
    <?php if (file_exists(__DIR__ . '/includes/analytics.php')) include __DIR__ . '/includes/analytics.php'; ?>
    
    <?php 
    echo organizationSchema();
    echo websiteSchema();
    ?>
</head>
<body>
    <?php include __DIR__ . '/includes/admin-bar.php'; ?>
    <?php include __DIR__ . '/includes/header.php'; ?>
    
    <main id="main-content">
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
                <?php editable_text('features', 'title', 'Våra tjänster', 'h2', 'text-center'); ?>
                <?php editable_text('features', 'description', 'Vi erbjuder kompletta lösningar för ditt företag', 'p', 'text-center mb-4'); ?>
                
                <div class="grid grid--3" style="margin-top: 3rem;">
                    <div class="card">
                        <?php editable_text('features_feature1', 'title', 'Tjänst 1', 'h3', 'card__title'); ?>
                        <?php editable_text('features_feature1', 'description', 'Beskrivning av tjänst 1', 'p', 'card__text'); ?>
                    </div>

                    <div class="card">
                        <?php editable_text('features_feature2', 'title', 'Tjänst 2', 'h3', 'card__title'); ?>
                        <?php editable_text('features_feature2', 'description', 'Beskrivning av tjänst 2', 'p', 'card__text'); ?>
                    </div>

                    <div class="card">
                        <?php editable_text('features_feature3', 'title', 'Tjänst 3', 'h3', 'card__title'); ?>
                        <?php editable_text('features_feature3', 'description', 'Beskrivning av tjänst 3', 'p', 'card__text'); ?>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- CTA Section -->
        <section class="section section--white">
            <div class="container text-center">
                <?php editable_text('cta', 'title', 'Redo att komma igång?', 'h2'); ?>
                <?php editable_text('cta', 'description', 'Kontakta oss idag för en kostnadsfri konsultation', 'p', 'text-lg'); ?>
                
                <a href="/kontakt" class="button button--primary" style="margin-top: 2rem;">
                    <?php echo get_content('cta.button_text', 'Kontakta oss'); ?>
                </a>
            </div>
        </section>
    </main>
    
    <?php include __DIR__ . '/includes/footer.php'; ?>
    
    <script src="/assets/js/cms.js?v=<?php echo BOSSE_VERSION; ?>" defer></script>
    
    <?php if (is_logged_in()): ?>
        <!-- CSRF token för CMS -->
        <form style="display: none;">
            <?php echo csrf_field(); ?>
        </form>
    <?php endif; ?>
    
    <?php include __DIR__ . '/includes/cookie-consent.php'; ?>
</body>
</html>
