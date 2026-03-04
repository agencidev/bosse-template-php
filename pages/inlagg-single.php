<?php
/**
 * Enskilt projekt (publik)
 * Visar ett projekt baserat på slug
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
$_uri_prefix = '/inlagg';
$_back = ['url' => '/inlagg', 'sv' => 'Tillbaka till inlägg', 'en' => 'Back to posts'];

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
function projectSchema($project, $_uri_prefix = '/projekt') {
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

    <link rel="stylesheet" href="/assets/css/projekt-single-custom.css?v=<?php echo BOSSE_VERSION; ?>">
</head>
<body>
    <?php include __DIR__ . '/../includes/admin-bar.php'; ?>
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <main id="main-content">
        <article class="projekt-single">
            <div class="projekt-single__container">
                <!-- Breadcrumb -->
                <div class="projekt-single__breadcrumb">
                    <a href="/">HEM</a>
                    <span>›</span>
                    <a href="/inlagg">INLÄGG</a>
                    <span>›</span>
                    <span><?php echo strtoupper($project['category'] ?? ''); ?></span>
                </div>

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

                <!-- Category Badge -->
                <?php if (!empty($project['category'])): ?>
                    <?php
                    function getCategoryClass($category) {
                        $map = [
                            'Projekt' => 'project',
                            'Blogg' => 'blog',
                            'Nyhet' => 'news',
                            'Event' => 'event',
                            'Juridik' => 'project',
                            'Tech' => 'event',
                            'Bransch' => 'blog'
                        ];
                        return $map[$category] ?? 'blog';
                    }
                    ?>
                    <span class="projekt-single__category projekt-single__category--<?php echo getCategoryClass($project['category']); ?>">
                        <?php echo strtoupper($project['category']); ?>
                    </span>
                <?php endif; ?>

                <!-- Title -->
                <h1 class="projekt-single__title"><?php echo htmlspecialchars($project['title'] ?? 'Utan titel', ENT_QUOTES, 'UTF-8'); ?></h1>

                <!-- Meta -->
                <div class="projekt-single__meta">
                    <?php if (!empty($project['createdAt'])): ?>
                        <span><?php echo strtoupper(date('j M Y', strtotime($project['createdAt']))); ?></span>
                    <?php endif; ?>
                    <span>•</span>
                    <span><?php 
                        $authors = ['Anna Berg', 'Erik Johansson', 'Lisa Andersson', 'Martin Svensson', 'Karin Delling', 'Sofia Nilsson', 'David Larsson', 'Emma Karlsson', 'Oscar Lindberg', 'Maria Olsson'];
                        $index = abs(crc32($project['slug'] ?? '')) % count($authors);
                        echo $authors[$index];
                    ?></span>
                </div>

                <!-- Cover Image -->
                <?php if (!empty($project['coverImage'])): ?>
                    <img src="<?php echo htmlspecialchars($project['coverImage'], ENT_QUOTES, 'UTF-8'); ?>"
                         alt="<?php echo htmlspecialchars($project['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                         class="projekt-single__cover" fetchpriority="high">
                <?php endif; ?>

                <!-- Layout: Content + Sidebar -->
                <div class="projekt-single__layout">
                    <!-- Main Content -->
                    <div class="projekt-single__content">
                        <?php if (!empty($project['content'])): ?>
                            <div class="projekt-single__body">
                                <?php echo sanitize_rich_content($project['content']); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Sidebar -->
                    <div class="projekt-single__sidebar">
                        <!-- CTA Box -->
                        <div class="projekt-single__cta-box">
                            <h3 class="projekt-single__cta-title">Redo att komma igång?</h3>
                            <p class="projekt-single__cta-text">Skapa professionella uppdragsbrev på några minuter</p>
                            <a href="/boka-demo" class="projekt-single__cta-button">Kom igång nu</a>
                        </div>
                    </div>
                </div>
            </div>
        </article>

        <?php
        // Related articles - try same category first, then any published articles
        $related = [];
        $currentCategory = $project['category'] ?? '';
        
        // First try to get articles from same category
        if ($currentCategory !== '') {
            foreach ($projects as $rp) {
                if (($rp['id'] ?? '') === ($project['id'] ?? '')) continue;
                if (($rp['status'] ?? '') !== 'published') continue;
                if (($rp['category'] ?? '') !== $currentCategory) continue;
                $related[] = $rp;
                if (count($related) >= 3) break;
            }
        }
        
        // If we don't have 3 articles yet, get any published articles
        if (count($related) < 3) {
            foreach ($projects as $rp) {
                if (($rp['id'] ?? '') === ($project['id'] ?? '')) continue;
                if (($rp['status'] ?? '') !== 'published') continue;
                // Skip if already in related array
                $alreadyAdded = false;
                foreach ($related as $existing) {
                    if (($existing['id'] ?? '') === ($rp['id'] ?? '')) {
                        $alreadyAdded = true;
                        break;
                    }
                }
                if ($alreadyAdded) continue;
                $related[] = $rp;
                if (count($related) >= 3) break;
            }
        }
        ?>
        <?php if (!empty($related)): ?>
        <section class="blog-section">
            <div class="blog-container">
                <h2 class="blog-heading">Relaterade artiklar</h2>
                
                <div class="blog-list">
                    <?php foreach ($related as $post): ?>
                    <article class="blog-card blog-card--list">
                        <a href="/inlagg/<?php echo htmlspecialchars($post['slug'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="blog-card__list-link">
                            <h3 class="blog-card__list-title"><?php echo htmlspecialchars($post['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?></h3>
                            <div class="blog-card__list-meta">
                                <span class="blog-card__author-name"><?php 
                                    $authors = ['Anna Berg', 'Erik Johansson', 'Lisa Andersson', 'Martin Svensson', 'Karin Delling', 'Sofia Nilsson', 'David Larsson', 'Emma Karlsson', 'Oscar Lindberg', 'Maria Olsson'];
                                    $index = abs(crc32($post['slug'] ?? '')) % count($authors);
                                    echo strtoupper($authors[$index]);
                                ?></span>
                                <span class="blog-card__date"><?php echo strtoupper(date('j M Y', strtotime($post['createdAt'] ?? 'now'))); ?></span>
                                <?php if (!empty($post['category'])): ?>
                                    <span class="blog-card__category blog-card__category--<?php echo getCategoryClass($post['category']); ?>">
                                        <?php echo strtoupper($post['category']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </a>
                    </article>
                    <?php endforeach; ?>
                </div>
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
