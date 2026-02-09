<?php
/**
 * Enskilt projekt (publik)
 * Visar ett projekt baserat på slug
 */

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/security/session.php';
require_once __DIR__ . '/cms/content.php';
require_once __DIR__ . '/seo/meta.php';
require_once __DIR__ . '/seo/schema.php';

// Hämta slug från URL
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

// Hämta projekt
$projects_file = __DIR__ . '/data/projects.json';
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
    if (file_exists(__DIR__ . '/404.php')) {
        include __DIR__ . '/404.php';
        exit;
    }
    echo '<h1>404 - Projektet kunde inte hittas</h1>';
    exit;
}

// Schema.org för projektet
function projectSchema($project) {
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => $project['title'] ?? '',
        'description' => $project['summary'] ?? '',
        'datePublished' => $project['createdAt'] ?? '',
        'author' => [
            '@type' => 'Organization',
            'name' => SITE_NAME
        ]
    ];

    if (!empty($project['coverImage'])) {
        $schema['image'] = SITE_URL . $project['coverImage'];
    }

    return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';
}
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <?php
    generateMeta(
        htmlspecialchars($project['title'] ?? 'Projekt', ENT_QUOTES, 'UTF-8') . ' - ' . SITE_NAME,
        htmlspecialchars($project['summary'] ?? '', ENT_QUOTES, 'UTF-8'),
        $project['coverImage'] ?? '/assets/images/og-image.jpg'
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

    <?php echo projectSchema($project); ?>

    <style>
    .projekt-single {
        padding: var(--section-padding, 4rem) 0;
    }

    .projekt-single__back {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        color: var(--color-gray-500, #737373);
        text-decoration: none;
        font-size: 0.875rem;
        margin-bottom: 2rem;
        transition: color 0.2s;
    }

    .projekt-single__back:hover {
        color: var(--color-primary, #8b5cf6);
    }

    .projekt-single__header {
        max-width: 800px;
        margin: 0 auto 3rem;
        text-align: center;
    }

    .projekt-single__category {
        display: inline-block;
        padding: 0.375rem 1rem;
        background: var(--color-primary-light, #a78bfa);
        color: white;
        font-size: 0.75rem;
        font-weight: 600;
        border-radius: var(--radius-md, 0.5rem);
        margin-bottom: 1rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .projekt-single__title {
        font-size: 2.5rem;
        font-weight: 700;
        color: var(--color-foreground, #18181b);
        margin-bottom: 1rem;
        line-height: 1.2;
    }

    .projekt-single__meta {
        display: flex;
        gap: 1.5rem;
        justify-content: center;
        font-size: 0.875rem;
        color: var(--color-gray-500, #737373);
    }

    .projekt-single__status {
        padding: 0.25rem 0.75rem;
        border-radius: var(--radius-md, 0.5rem);
        font-size: 0.75rem;
        font-weight: 600;
    }

    .projekt-single__status--draft {
        background: #fef3c7;
        color: #92400e;
    }

    .projekt-single__status--published {
        background: #d1fae5;
        color: #065f46;
    }

    .projekt-single__cover {
        width: 100%;
        max-height: 500px;
        object-fit: cover;
        border-radius: var(--radius-lg, 1rem);
        margin-bottom: 3rem;
    }

    .projekt-single__content {
        max-width: 800px;
        margin: 0 auto;
    }

    .projekt-single__summary {
        font-size: 1.25rem;
        color: var(--color-gray-600, #525252);
        line-height: 1.7;
        margin-bottom: 2rem;
        padding-bottom: 2rem;
        border-bottom: 1px solid var(--color-gray-200, #e5e5e5);
    }

    .projekt-single__body {
        font-size: 1.0625rem;
        color: var(--color-foreground, #18181b);
        line-height: 1.8;
    }

    .projekt-single__body p {
        margin-bottom: 1.5rem;
    }

    .projekt-single__body h2 {
        font-size: 1.5rem;
        font-weight: 600;
        margin-top: 2.5rem;
        margin-bottom: 1rem;
    }

    .projekt-single__body h3 {
        font-size: 1.25rem;
        font-weight: 600;
        margin-top: 2rem;
        margin-bottom: 0.75rem;
    }

    .projekt-single__body ul,
    .projekt-single__body ol {
        margin-bottom: 1.5rem;
        padding-left: 1.5rem;
    }

    .projekt-single__body li {
        margin-bottom: 0.5rem;
    }

    .projekt-single__body img {
        max-width: 100%;
        height: auto;
        border-radius: var(--radius-md, 0.5rem);
        margin: 2rem 0;
    }

    .projekt-single__gallery {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 1rem;
        margin-top: 3rem;
    }

    .projekt-single__gallery img {
        width: 100%;
        height: 200px;
        object-fit: cover;
        border-radius: var(--radius-md, 0.5rem);
        cursor: pointer;
        transition: transform 0.3s;
    }

    .projekt-single__gallery img:hover {
        transform: scale(1.02);
    }

    .projekt-single__cta {
        margin-top: 4rem;
        padding-top: 3rem;
        border-top: 1px solid var(--color-gray-200, #e5e5e5);
        text-align: center;
    }

    .projekt-single__cta h3 {
        font-size: 1.5rem;
        font-weight: 600;
        margin-bottom: 1rem;
    }

    .projekt-single__cta p {
        color: var(--color-gray-600, #525252);
        margin-bottom: 1.5rem;
    }

    .projekt-single__admin-bar {
        background: #fef3c7;
        border: 1px solid #fde68a;
        border-radius: var(--radius-md, 0.5rem);
        padding: 1rem 1.5rem;
        margin-bottom: 2rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .projekt-single__admin-bar span {
        font-size: 0.875rem;
        color: #92400e;
    }

    .projekt-single__admin-bar a {
        padding: 0.5rem 1rem;
        background: #18181b;
        color: white;
        border-radius: var(--radius-md, 0.5rem);
        text-decoration: none;
        font-size: 0.875rem;
        font-weight: 500;
    }

    @media (max-width: 768px) {
        .projekt-single__title {
            font-size: 1.875rem;
        }

        .projekt-single__meta {
            flex-direction: column;
            gap: 0.5rem;
        }

        .projekt-single__summary {
            font-size: 1.125rem;
        }
    }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/admin-bar.php'; ?>
    <?php include __DIR__ . '/includes/header.php'; ?>

    <main>
        <article class="projekt-single">
            <div class="container">
                <a href="/projekt" class="projekt-single__back">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M10 12L6 8L10 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Tillbaka till projekt
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
                         class="projekt-single__cover">
                <?php endif; ?>

                <div class="projekt-single__content">
                    <?php if (!empty($project['summary'])): ?>
                        <p class="projekt-single__summary"><?php echo htmlspecialchars($project['summary'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php endif; ?>

                    <?php if (!empty($project['content'])): ?>
                        <div class="projekt-single__body">
                            <?php echo nl2br(htmlspecialchars($project['content'], ENT_QUOTES, 'UTF-8')); ?>
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
    </main>

    <?php include __DIR__ . '/includes/footer.php'; ?>

    <script src="/assets/js/cms.js?v=<?php echo BOSSE_VERSION; ?>"></script>

    <?php if (is_logged_in()): ?>
        <form style="display: none;">
            <?php echo csrf_field(); ?>
        </form>
    <?php endif; ?>

    <?php include __DIR__ . '/includes/cookie-consent.php'; ?>
</body>
</html>
