<?php
/**
 * Projekt-lista (publik)
 * Visar alla publicerade projekt från data/projects.json
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../security/session.php';
require_once __DIR__ . '/../cms/content.php';
require_once __DIR__ . '/../seo/meta.php';
require_once __DIR__ . '/../seo/schema.php';

// Hämta projekt
$projects_file = __DIR__ . '/../data/projects.json';
$all_projects = [];

if (file_exists($projects_file)) {
    $json = file_get_contents($projects_file);
    $all_projects = json_decode($json, true) ?? [];
}

// Filtrera till endast publicerade
$projects = array_filter($all_projects, fn($p) => isset($p['status']) && $p['status'] === 'published');

// Sortera efter datum (nyast först)
usort($projects, function($a, $b) {
    $dateA = $a['createdAt'] ?? '1970-01-01';
    $dateB = $b['createdAt'] ?? '1970-01-01';
    return strtotime($dateB) - strtotime($dateA);
});

// Kategorifil­trering (valfri GET-parameter)
$category_filter = isset($_GET['kategori']) ? trim($_GET['kategori']) : '';
if (!empty($category_filter)) {
    $projects = array_filter($projects, fn($p) => isset($p['category']) && strtolower($p['category']) === strtolower($category_filter));
}

// Hämta unika kategorier för filtrering
$categories = array_unique(array_filter(array_map(fn($p) => $p['category'] ?? '', $all_projects)));
sort($categories);
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <?php
    generateMeta(
        get_content('projekt.meta_title', 'Projekt - ' . SITE_NAME),
        get_content('projekt.meta_description', 'Se våra senaste projekt och case studies'),
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
    .projekt-hero {
        padding: var(--section-padding, 4rem) 0;
        background: var(--color-gray-50, #fafafa);
        text-align: center;
    }

    .projekt-hero h1 {
        font-size: 2.5rem;
        font-weight: 700;
        color: var(--color-foreground, #18181b);
        margin-bottom: 1rem;
    }

    .projekt-hero p {
        font-size: 1.125rem;
        color: var(--color-gray-600, #525252);
        max-width: 600px;
        margin: 0 auto;
    }

    .projekt-filter {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
        justify-content: center;
        margin-top: 2rem;
    }

    .projekt-filter a {
        padding: 0.5rem 1rem;
        border-radius: var(--radius-md, 0.5rem);
        background: white;
        color: var(--color-gray-600, #525252);
        text-decoration: none;
        font-size: 0.875rem;
        font-weight: 500;
        border: 1px solid var(--color-gray-200, #e5e5e5);
        transition: all 0.2s;
    }

    .projekt-filter a:hover,
    .projekt-filter a.active {
        background: var(--color-primary, #8b5cf6);
        color: white;
        border-color: var(--color-primary, #8b5cf6);
    }

    .projekt-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 2rem;
        padding: var(--section-padding, 4rem) 0;
    }

    .projekt-card {
        background: white;
        border-radius: var(--radius-lg, 1rem);
        overflow: hidden;
        border: 1px solid var(--color-gray-200, #e5e5e5);
        transition: all 0.3s;
        text-decoration: none;
        display: block;
    }

    .projekt-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 24px -8px rgba(0, 0, 0, 0.15);
    }

    .projekt-card__image {
        width: 100%;
        height: 200px;
        object-fit: cover;
        background: linear-gradient(135deg, var(--color-primary-light, #a78bfa) 0%, var(--color-primary, #8b5cf6) 100%);
    }

    .projekt-card__content {
        padding: 1.5rem;
    }

    .projekt-card__category {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        background: var(--color-gray-100, #f5f5f5);
        color: var(--color-gray-600, #525252);
        font-size: 0.75rem;
        font-weight: 600;
        border-radius: var(--radius-md, 0.5rem);
        margin-bottom: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.025em;
    }

    .projekt-card__title {
        font-size: 1.25rem;
        font-weight: 600;
        color: var(--color-foreground, #18181b);
        margin-bottom: 0.5rem;
        line-height: 1.3;
    }

    .projekt-card__summary {
        font-size: 0.9375rem;
        color: var(--color-gray-600, #525252);
        line-height: 1.6;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .projekt-card__date {
        font-size: 0.8125rem;
        color: var(--color-gray-400, #a3a3a3);
        margin-top: 1rem;
    }

    .projekt-empty {
        text-align: center;
        padding: 4rem 1.5rem;
    }

    .projekt-empty p {
        color: var(--color-gray-500, #737373);
        margin-bottom: 1.5rem;
    }

    @media (max-width: 768px) {
        .projekt-hero h1 {
            font-size: 2rem;
        }

        .projekt-grid {
            grid-template-columns: 1fr;
        }
    }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/admin-bar.php'; ?>
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <main>
        <!-- Hero Section -->
        <section class="projekt-hero">
            <div class="container">
                <?php editable_text('projekt', 'title', 'Våra projekt', 'h1'); ?>
                <?php editable_text('projekt', 'description', 'Utforska våra senaste projekt och se vad vi kan hjälpa dig med', 'p'); ?>

                <?php if (!empty($categories)): ?>
                <div class="projekt-filter">
                    <a href="/projekt" class="<?php echo empty($category_filter) ? 'active' : ''; ?>">Alla</a>
                    <?php foreach ($categories as $cat): ?>
                        <a href="/projekt?kategori=<?php echo urlencode($cat); ?>"
                           class="<?php echo strtolower($cat) === strtolower($category_filter) ? 'active' : ''; ?>">
                            <?php echo htmlspecialchars($cat, ENT_QUOTES, 'UTF-8'); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Projekt Grid -->
        <section class="section section--white">
            <div class="container">
                <?php if (empty($projects)): ?>
                    <div class="projekt-empty">
                        <p>Inga projekt att visa just nu.</p>
                        <?php if (is_logged_in()): ?>
                            <a href="/cms/projects/new" class="button button--primary">Skapa ditt första projekt</a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="projekt-grid">
                        <?php foreach ($projects as $project): ?>
                            <a href="/projekt/<?php echo htmlspecialchars($project['slug'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="projekt-card">
                                <?php if (!empty($project['coverImage'])): ?>
                                    <img src="<?php echo htmlspecialchars($project['coverImage'], ENT_QUOTES, 'UTF-8'); ?>"
                                         alt="<?php echo htmlspecialchars($project['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                         class="projekt-card__image"
                                         loading="lazy">
                                <?php else: ?>
                                    <div class="projekt-card__image"></div>
                                <?php endif; ?>

                                <div class="projekt-card__content">
                                    <?php if (!empty($project['category'])): ?>
                                        <span class="projekt-card__category"><?php echo htmlspecialchars($project['category'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php endif; ?>

                                    <h2 class="projekt-card__title"><?php echo htmlspecialchars($project['title'] ?? 'Utan titel', ENT_QUOTES, 'UTF-8'); ?></h2>

                                    <?php if (!empty($project['summary'])): ?>
                                        <p class="projekt-card__summary"><?php echo htmlspecialchars($project['summary'], ENT_QUOTES, 'UTF-8'); ?></p>
                                    <?php endif; ?>

                                    <?php if (!empty($project['createdAt'])): ?>
                                        <p class="projekt-card__date">
                                            <?php
                                            $date = strtotime($project['createdAt']);
                                            echo date('j', $date) . ' ' . getSwedishMonth(date('n', $date)) . ' ' . date('Y', $date);
                                            ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
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
<?php
/**
 * Hjälpfunktion för svenska månadsnamn
 */
function getSwedishMonth($month) {
    $months = [
        1 => 'januari', 2 => 'februari', 3 => 'mars', 4 => 'april',
        5 => 'maj', 6 => 'juni', 7 => 'juli', 8 => 'augusti',
        9 => 'september', 10 => 'oktober', 11 => 'november', 12 => 'december'
    ];
    return $months[(int)$month] ?? '';
}
?>
