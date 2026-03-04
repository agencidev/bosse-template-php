<?php
/**
 * Inlägg-lista (publik)
 * Visar alla publicerade inlägg från data/projects.json
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../security/session.php';
require_once __DIR__ . '/../cms/content.php';
require_once __DIR__ . '/../seo/meta.php';
require_once __DIR__ . '/../seo/schema.php';

$page_title = 'Alla inlägg - ' . SITE_NAME;
$page_description = 'Läs de senaste nyheterna och insikterna inom avtal, digitalisering och juridik.';

// Hämta projekt från data/projects.json
$projects_file = __DIR__ . '/../data/projects.json';
$all_projects = [];

if (file_exists($projects_file)) {
    $json = file_get_contents($projects_file);
    $all_projects = json_decode($json, true) ?? [];
}

// Filtrera till endast publicerade
$projects = array_filter($all_projects, function($p) {
    return isset($p['status']) && $p['status'] === 'published';
});

// Sortera efter datum (nyast först)
usort($projects, function($a, $b) {
    $dateA = $a['createdAt'] ?? '1970-01-01';
    $dateB = $b['createdAt'] ?? '1970-01-01';
    return strtotime($dateB) - strtotime($dateA);
});

// Hämta unika kategorier för filtrering (från publicerade projekt)
$published_projects = array_filter($all_projects, fn($p) => isset($p['status']) && $p['status'] === 'published');
$categories = array_unique(array_filter(array_map(fn($p) => $p['category'] ?? '', $published_projects)));
sort($categories);

// Category filter
$category_filter = isset($_GET['kategori']) ? trim($_GET['kategori']) : '';
if (!empty($category_filter)) {
    $projects = array_filter($projects, fn($p) => isset($p['category']) && strtolower($p['category']) === strtolower($category_filter));
}

// Helper function för kategori-CSS-klass
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

// Helper function för slumpmässigt skribentnamn
function getRandomAuthor($seed = '') {
    $authors = [
        'Anna Berg',
        'Erik Johansson',
        'Lisa Andersson',
        'Martin Svensson',
        'Karin Delling',
        'Sofia Nilsson',
        'David Larsson',
        'Emma Karlsson',
        'Oscar Lindberg',
        'Maria Olsson'
    ];
    // Använd seed för konsistent slumpmässighet per inlägg
    $index = !empty($seed) ? abs(crc32($seed)) % count($authors) : array_rand($authors);
    return $authors[$index];
}
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <?php
    generateMeta(
        $page_title,
        $page_description,
        '/assets/images/og-image.jpg'
    );
    ?>

    <?php if (file_exists(__DIR__ . '/../assets/images/favicon.png')): ?>
    <link rel="icon" type="image/png" href="/assets/images/favicon.png">
    <?php endif; ?>
    <?php if (file_exists(__DIR__ . '/../assets/images/apple-touch-icon.png')): ?>
    <link rel="apple-touch-icon" href="/assets/images/apple-touch-icon.png">
    <?php endif; ?>
    <link rel="stylesheet" href="/assets/css/main.css?v=<?php echo BOSSE_VERSION; ?>">
    <link rel="stylesheet" href="/assets/css/projekt-custom.css?v=<?php echo BOSSE_VERSION; ?>">
    <?php if (file_exists(__DIR__ . '/../includes/fonts.php')) include __DIR__ . '/../includes/fonts.php'; ?>
    <?php if (file_exists(__DIR__ . '/../includes/analytics.php')) include __DIR__ . '/../includes/analytics.php'; ?>
</head>
<body>
    <?php include __DIR__ . '/../includes/admin-bar.php'; ?>
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <main id="main-content">
        <section class="projekt-page">
            <div class="projekt-container">
                <div class="projekt-header">
                    <h1>Alla inlägg</h1>
                    
                    <!-- Category Filter -->
                    <div class="projekt-filter">
                        <a href="/inlagg" class="<?php echo empty($category_filter) ? 'active' : ''; ?>">ALLA</a>
                        <?php foreach ($categories as $cat): ?>
                            <a href="/inlagg?kategori=<?php echo urlencode($cat); ?>" 
                               class="<?php echo strtolower($category_filter) === strtolower($cat) ? 'active' : ''; ?>">
                                <?php echo strtoupper($cat); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <?php if (empty($projects)): ?>
                    <div class="projekt-empty">
                        <p>Inga inlägg att visa just nu.</p>
                    </div>
                <?php else: ?>
                    <?php
                    // Split projects: first one featured, next 3 in grid, rest in list
                    $featured = array_slice($projects, 0, 1);
                    $grid_items = array_slice($projects, 1, 3);
                    $list_items = array_slice($projects, 4);
                    ?>

                    <!-- Featured Post -->
                    <?php if (!empty($featured)): ?>
                        <?php $project = $featured[0]; ?>
                        <a href="/inlagg/<?php echo htmlspecialchars($project['slug'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="projekt-featured">
                            <div class="projekt-featured__image">
                                <?php if (!empty($project['coverImage'])): ?>
                                    <img src="<?php echo htmlspecialchars($project['coverImage'], ENT_QUOTES, 'UTF-8'); ?>" 
                                         alt="<?php echo htmlspecialchars($project['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                <?php endif; ?>
                            </div>
                            <div class="projekt-featured__content">
                                <span class="projekt-featured__badge">✨ UTVALD</span>
                                <h2 class="projekt-featured__title"><?php echo htmlspecialchars($project['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?></h2>
                                <p class="projekt-featured__summary"><?php echo htmlspecialchars($project['summary'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
                                <div class="projekt-featured__meta">
                                    <span class="projekt-featured__author"><?php echo strtoupper(getRandomAuthor($project['slug'] ?? '')); ?></span>
                                    <span class="projekt-featured__date"><?php echo strtoupper(date('j M Y', strtotime($project['createdAt'] ?? 'now'))); ?></span>
                                    <?php if (!empty($project['category'])): ?>
                                        <span class="projekt-featured__category projekt-featured__category--<?php echo getCategoryClass($project['category']); ?>">
                                            <?php echo strtoupper($project['category']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
                    <?php endif; ?>

                    <!-- Grid Section (3 cards) -->
                    <?php if (!empty($grid_items)): ?>
                        <div class="projekt-grid">
                            <?php foreach ($grid_items as $project): ?>
                                <a href="/inlagg/<?php echo htmlspecialchars($project['slug'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="projekt-grid-card">
                                    <?php if (!empty($project['coverImage'])): ?>
                                        <img src="<?php echo htmlspecialchars($project['coverImage'], ENT_QUOTES, 'UTF-8'); ?>" 
                                             alt="<?php echo htmlspecialchars($project['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                             class="projekt-grid-card__image">
                                    <?php endif; ?>
                                    <div class="projekt-grid-card__content">
                                        <h3 class="projekt-grid-card__title"><?php echo htmlspecialchars($project['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?></h3>
                                        <div class="projekt-grid-card__meta">
                                            <span><?php echo strtoupper(getRandomAuthor($project['slug'] ?? '')); ?></span>
                                            <span><?php echo strtoupper(date('j M Y', strtotime($project['createdAt'] ?? 'now'))); ?></span>
                                            <?php if (!empty($project['category'])): ?>
                                                <span class="projekt-grid-card__category projekt-grid-card__category--<?php echo getCategoryClass($project['category']); ?>">
                                                    <?php echo strtoupper($project['category']); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- List Section -->
                    <?php if (!empty($list_items)): ?>
                        <div class="projekt-list">
                            <?php foreach ($list_items as $project): ?>
                                <a href="/inlagg/<?php echo htmlspecialchars($project['slug'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="projekt-list-item">
                                    <h3 class="projekt-list-item__title"><?php echo htmlspecialchars($project['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?></h3>
                                    <div class="projekt-list-item__meta">
                                        <span class="projekt-list-item__author"><?php echo strtoupper(getRandomAuthor($project['slug'] ?? '')); ?></span>
                                        <span class="projekt-list-item__date"><?php echo strtoupper(date('j M Y', strtotime($project['createdAt'] ?? 'now'))); ?></span>
                                        <?php if (!empty($project['category'])): ?>
                                            <span class="projekt-list-item__category projekt-list-item__category--<?php echo getCategoryClass($project['category']); ?>">
                                                <?php echo strtoupper($project['category']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
