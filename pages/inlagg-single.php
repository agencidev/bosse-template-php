<?php
/**
 * Enskilt inlägg (publik)
 * Visar ett inlägg baserat på slug
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../security/session.php';
require_once __DIR__ . '/../security/validation.php';
require_once __DIR__ . '/../cms/content.php';
require_once __DIR__ . '/../seo/meta.php';
require_once __DIR__ . '/../seo/schema.php';

// Hämta slug från URL
$slug = trim($_GET['slug'] ?? '');

// Context detection based on URL prefix
$_uri_prefix = '/' . explode('/', trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'))[0];

// Load categories from SAFE config (survives updates), with hardcoded fallback
$_categories_file = __DIR__ . '/../cms/extensions/categories.php';
$_ctx_map = file_exists($_categories_file) ? (require $_categories_file) : [];
if (empty($_ctx_map) || !is_array($_ctx_map)) {
    $_ctx_map = [
        '/inlagg' => ['category' => 'Inlägg', 'title_sv' => 'Inlägg', 'title_en' => 'Posts', 'base_url' => '/inlagg'],
    ];
}

$_back_map = [];
foreach ($_ctx_map as $prefix => $ctx) {
    $_back_map[$prefix] = [
        'url' => $ctx['base_url'] ?? $prefix,
        'sv'  => 'Tillbaka till ' . mb_strtolower($ctx['title_sv'] ?? $prefix),
        'en'  => 'Back to ' . mb_strtolower($ctx['title_en'] ?? $prefix),
    ];
}
if (empty($_back_map)) {
    $_back_map = [
        '/inlagg' => ['url' => '/inlagg', 'sv' => 'Tillbaka till inlägg', 'en' => 'Back to posts'],
    ];
}
$_back = $_back_map[$_uri_prefix] ?? $_back_map['/inlagg'];

// Hämta projekt
$projects_file = __DIR__ . '/../data/projects.json';
$projects = [];
$project = null;

if (file_exists($projects_file)) {
    $json = file_get_contents($projects_file);
    $projects = json_decode($json, true) ?? [];
}

// Hitta projektet
foreach ($projects as $p) {
    if (isset($p['slug']) && $p['slug'] === $slug) {
        // Endast publicerade för icke-inloggade
        if (isset($p['status']) && $p['status'] === 'published') {
            $project = $p;
            break;
        }
        // Admin kan se utkast också
        if (is_logged_in()) {
            $project = $p;
            break;
        }
    }
}

// 404 om projektet inte finns
if (!$project) {
    http_response_code(404);
    if (file_exists(__DIR__ . '/errors/404.php')) {
        include __DIR__ . '/errors/404.php';
        exit;
    }
    echo '<h1>404 - Projektet kunde inte hittas</h1>';
    exit;
}

// Schema.org för projektet
function projectSchema($project, $_uri_prefix = '/inlagg') {
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => $project['title'] ?? '',
        'description' => $project['summary'] ?? '',
        'datePublished' => $project['createdAt'] ?? '',
        'dateModified' => $project['updatedAt'] ?? $project['createdAt'] ?? '',
        'author' => [
            '@type' => 'Organization',
            'name' => SITE_NAME
        ],
        'publisher' => [
            '@type' => 'Organization',
            'name' => SITE_NAME,
            'logo' => [
                '@type' => 'ImageObject',
                'url' => SITE_URL . '/assets/images/logo-dark.png'
            ]
        ],
        'mainEntityOfPage' => [
            '@type' => 'WebPage',
            '@id' => SITE_URL . $_uri_prefix . '/' . ($project['slug'] ?? '')
        ]
    ];

    if (!empty($project['coverImage'])) {
        $schema['image'] = SITE_URL . $project['coverImage'];
    }

    return '<script type="application/ld+json" ' . csp_nonce_attr() . '>' . json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';
}
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <?php
    generateMeta(
        $project['title'] ?? 'Projekt',
        $project['summary'] ?? '',
        $project['coverImage'] ?? '/assets/images/og-image.jpg',
        'article',
        [
            'published' => $project['createdAt'] ?? '',
            'modified' => $project['updatedAt'] ?? $project['createdAt'] ?? '',
        ]
    );
    ?>

    <?php if (file_exists(__DIR__ . '/../assets/images/favicon.png')): ?>
    <link rel="icon" type="image/png" href="/assets/images/favicon.png">
    <?php endif; ?>
    <?php if (file_exists(__DIR__ . '/../assets/images/apple-touch-icon.png')): ?>
    <link rel="apple-touch-icon" href="/assets/images/apple-touch-icon.png">
    <?php endif; ?>
    <link rel="stylesheet" href="/assets/css/main.css?v=<?php echo BOSSE_VERSION; ?>">
    <?php if (!empty($project['coverImage'])): ?>
    <link rel="preload" as="image" href="<?php echo htmlspecialchars($project['coverImage'], ENT_QUOTES, 'UTF-8'); ?>">
    <?php endif; ?>
    <?php if (file_exists(__DIR__ . '/../includes/fonts.php')) include __DIR__ . '/../includes/fonts.php'; ?>
    <?php if (file_exists(__DIR__ . '/../includes/analytics.php')) include __DIR__ . '/../includes/analytics.php'; ?>

    <?php echo projectSchema($project, $_uri_prefix); ?>

    <link rel="stylesheet" href="/assets/css/inlagg-single-default.css?v=<?php echo BOSSE_VERSION; ?>">
    <?php if (file_exists(__DIR__ . '/../assets/css/inlagg-single-custom.css') && filesize(__DIR__ . '/../assets/css/inlagg-single-custom.css') > 50): ?>
    <link rel="stylesheet" href="/assets/css/inlagg-single-custom.css?v=<?php echo BOSSE_VERSION; ?>">
    <?php endif; ?>
</head>
<body>
    <?php include __DIR__ . '/../includes/admin-bar.php'; ?>
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <main id="main-content">
        <article class="projekt-single">
            <div class="container">
                <a href="<?php echo $_back['url']; ?>" class="projekt-single__back">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M10 12L6 8L10 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <?php echo $_back['sv']; ?>
                </a>

                <?php if (is_logged_in()): ?>
                <div class="projekt-single__admin-bar">
                    <span>
                        <?php if (($project['status'] ?? 'draft') === 'draft'): ?>
                            Detta är ett utkast och visas endast för inloggade
                        <?php else: ?>
                            Du kan redigera detta projekt
                        <?php endif; ?>
                    </span>
                    <a href="/projects/edit?id=<?php echo urlencode($project['id'] ?? ''); ?>">Redigera</a>
                </div>
                <?php endif; ?>

                <header class="projekt-single__header">
                    <?php if (!empty($project['category'])): ?>
                        <span class="projekt-single__category"><?php echo htmlspecialchars($project['category'], ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php endif; ?>

                    <h1 class="projekt-single__title"><?php echo htmlspecialchars($project['title'] ?? 'Utan titel', ENT_QUOTES, 'UTF-8'); ?></h1>

                    <div class="projekt-single__meta">
                        <?php if (!empty($project['createdAt'])): ?>
                            <span>
                                <?php
                                $date = strtotime($project['createdAt']);
                                $months = [1 => 'januari', 2 => 'februari', 3 => 'mars', 4 => 'april', 5 => 'maj', 6 => 'juni', 7 => 'juli', 8 => 'augusti', 9 => 'september', 10 => 'oktober', 11 => 'november', 12 => 'december'];
                                echo date('j', $date) . ' ' . $months[(int)date('n', $date)] . ' ' . date('Y', $date);
                                ?>
                            </span>
                        <?php endif; ?>

                        <?php if (is_logged_in()): ?>
                            <span class="projekt-single__status projekt-single__status--<?php echo htmlspecialchars($project['status'] ?? 'draft', ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo ($project['status'] ?? 'draft') === 'published' ? 'Publicerad' : 'Utkast'; ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </header>

                <?php if (!empty($project['coverImage'])): ?>
                    <img src="<?php echo htmlspecialchars($project['coverImage'], ENT_QUOTES, 'UTF-8'); ?>"
                         alt="<?php echo htmlspecialchars($project['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                         class="projekt-single__cover" fetchpriority="high">
                <?php endif; ?>

                <div class="projekt-single__content">
                    <?php if (!empty($project['summary'])): ?>
                        <p class="projekt-single__summary"><?php echo htmlspecialchars($project['summary'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php endif; ?>

                    <?php if (!empty($project['content'])): ?>
                        <div class="projekt-single__body">
                            <?php echo sanitize_rich_content($project['content']); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($project['gallery']) && is_array($project['gallery'])): ?>
                        <div class="projekt-single__gallery">
                            <?php foreach ($project['gallery'] as $image): ?>
                                <img src="<?php echo htmlspecialchars($image, ENT_QUOTES, 'UTF-8'); ?>"
                                     alt="Projektbild"
                                     loading="lazy">
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- CTA Section -->
                    <div class="projekt-single__cta">
                        <?php editable_text('projekt_cta', 'title', 'Intresserad av något liknande?', 'h3'); ?>
                        <?php editable_text('projekt_cta', 'description', 'Kontakta oss så berättar vi mer om hur vi kan hjälpa dig', 'p'); ?>
                        <a href="/kontakt" class="button button--primary">Kontakta oss</a>
                    </div>
                </div>
            </div>
        </article>

        <?php
        // Related projects (same category, published, exclude current)
        $related = [];
        $currentCategory = $project['category'] ?? '';
        if ($currentCategory !== '') {
            foreach ($projects as $rp) {
                if (($rp['id'] ?? '') === ($project['id'] ?? '')) continue;
                if (($rp['status'] ?? '') !== 'published') continue;
                if (($rp['category'] ?? '') !== $currentCategory) continue;
                $related[] = $rp;
                if (count($related) >= 3) break;
            }
        }
        ?>
        <?php if (!empty($related)): ?>
        <section class="related-projects">
            <h2 class="related-projects__title">Fler inom <?php echo htmlspecialchars($currentCategory, ENT_QUOTES, 'UTF-8'); ?></h2>
            <div class="related-projects__grid">
                <?php foreach ($related as $rp): ?>
                <a href="<?php echo $_uri_prefix; ?>/<?php echo htmlspecialchars($rp['slug'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="related-projects__card">
                    <?php if (!empty($rp['coverImage'])): ?>
                    <img src="<?php echo htmlspecialchars($rp['coverImage'], ENT_QUOTES, 'UTF-8'); ?>"
                         alt="<?php echo htmlspecialchars($rp['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                         class="related-projects__image" loading="lazy">
                    <?php endif; ?>
                    <div class="related-projects__body">
                        <h3 class="related-projects__card-title"><?php echo htmlspecialchars($rp['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?></h3>
                        <?php if (!empty($rp['summary'])): ?>
                        <p class="related-projects__summary"><?php echo htmlspecialchars($rp['summary'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php endif; ?>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
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
