<?php
declare(strict_types=1);
/**
 * Landing Page Template
 * Generic data-driven template for landing pages rendered from JSON.
 * Expects $pageData to be set before including this file.
 * Included from index.php front controller — ROOT_PATH is already defined.
 */
if (!isset($pageData)) {
    http_response_code(404);
    include ROOT_PATH . '/pages/errors/404.php';
    exit;
}

// Prevent caching
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
        $pageData['meta']['title'],
        $pageData['meta']['description'],
        $pageData['meta']['ogImage'] ?? '/assets/images/og-image.jpg'
    );
    ?>

    <?php if (file_exists(ROOT_PATH . '/includes/fonts.php')) include ROOT_PATH . '/includes/fonts.php'; ?>
    <?php if (file_exists(ROOT_PATH . '/includes/analytics.php')) include ROOT_PATH . '/includes/analytics.php'; ?>
    <?php if (file_exists(ROOT_PATH . '/assets/images/favicon.ico')): ?>
    <link rel="icon" href="/assets/images/favicon.ico" sizes="32x32">
    <?php endif; ?>
    <?php if (file_exists(ROOT_PATH . '/assets/images/favicon.svg')): ?>
    <link rel="icon" href="/assets/images/favicon.svg" type="image/svg+xml">
    <?php elseif (file_exists(ROOT_PATH . '/assets/images/favicon.png')): ?>
    <link rel="icon" type="image/png" href="/assets/images/favicon.png">
    <?php endif; ?>
    <?php if (file_exists(ROOT_PATH . '/assets/images/apple-touch-icon.png')): ?>
    <link rel="apple-touch-icon" href="/assets/images/apple-touch-icon.png">
    <?php endif; ?>
    <link rel="preload" href="/assets/css/main.css?v=<?php echo BOSSE_VERSION; ?>" as="style">
    <link rel="stylesheet" href="/assets/css/main.css?v=<?php echo BOSSE_VERSION; ?>">

    <?php
    if (file_exists(ROOT_PATH . '/seo/schema.php')) {
        require_once ROOT_PATH . '/seo/schema.php';
        if (function_exists('outputSchemas')) {
            outputSchemas('landing');
        }
        if (function_exists('generateBreadcrumb')) {
            $breadcrumbItems = [['name' => 'Hem', 'url' => '/']];
            $breadcrumbItems[] = ['name' => $pageData['meta']['title'], 'url' => '/' . ($pageData['slug'] ?? '')];
            generateBreadcrumb($breadcrumbItems);
        }
    }
    ?>
</head>
<body<?php echo !empty($pageData['bodyClass']) ? ' class="' . htmlspecialchars($pageData['bodyClass']) . '"' : ''; ?>>
    <?php include ROOT_PATH . '/includes/admin-bar.php'; ?>
    <?php include ROOT_PATH . '/includes/header.php'; ?>

    <main id="main-content">
        <!-- Hero Section -->
        <section class="section section--problem">
            <div class="container">
                <div class="problem-content">
                    <h1><?php echo $pageData['hero']['title']; ?></h1>
                    <p><?php echo $pageData['hero']['subtitle']; ?></p>
                    <?php if (!empty($pageData['hero']['buttonText'])): ?>
                    <a href="<?php echo htmlspecialchars($pageData['hero']['buttonLink'] ?? '/kontakt'); ?>" class="button button--primary" style="margin-top: 30px;"><?php echo htmlspecialchars($pageData['hero']['buttonText']); ?></a>
                    <?php endif; ?>
                </div>
            </div>
        </section>

<?php
// Render each section from JSON data
if (!empty($pageData['sections'])):
foreach ($pageData['sections'] as $section):
    // Skip hidden sections
    if (!empty($section['hidden'])) continue;

    $type = $section['type'] ?? '';

    // --- HTML (raw passthrough) ---
    if ($type === 'html'):
?>
        <section class="<?php echo htmlspecialchars($section['sectionClass'] ?? 'section'); ?>">
            <?php echo $section['html']; ?>
        </section>
<?php

    // --- Feature Grid 4 columns ---
    elseif ($type === 'feature-grid-4'):
?>
        <section class="<?php echo htmlspecialchars($section['sectionClass'] ?? 'section section--key-features'); ?>">
            <div class="container">
                <?php if (!empty($section['heading'])): ?>
                <div class="section-header">
                    <h2 class="section-title"><?php echo $section['heading']; ?></h2>
                    <?php if (!empty($section['subheading'])): ?>
                    <p class="section-subtitle"><?php echo $section['subheading']; ?></p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <div class="key-features-grid" style="grid-template-columns: repeat(4, 1fr); gap: 24px;">
                    <?php foreach ($section['items'] as $item): ?>
                    <div class="key-feature-item">
                        <div class="key-feature-icon">
                            <?php echo $item['icon']; ?>
                        </div>
                        <h3><?php echo $item['title']; ?></h3>
                        <p><?php echo $item['text']; ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
<?php

    // --- Feature Grid 3 columns ---
    elseif ($type === 'feature-grid-3'):
?>
        <section class="<?php echo htmlspecialchars($section['sectionClass'] ?? 'section section--key-features'); ?>">
            <div class="container">
                <?php if (!empty($section['heading'])): ?>
                <div class="section-header">
                    <h2><?php echo $section['heading']; ?></h2>
                    <?php if (!empty($section['subheading'])): ?>
                    <p class="section-subtitle"><?php echo $section['subheading']; ?></p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <div class="key-features-grid" style="grid-template-columns: repeat(3, 1fr); gap: 24px;">
                    <?php foreach ($section['items'] as $item): ?>
                    <div class="key-feature-item"<?php echo isset($item['padding']) ? ' style="padding: ' . $item['padding'] . '"' : ''; ?>>
                        <div class="key-feature-icon">
                            <?php echo $item['icon']; ?>
                        </div>
                        <h3><?php echo $item['title']; ?></h3>
                        <p><?php echo $item['text']; ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
<?php

    // --- Feature Grid 2 columns ---
    elseif ($type === 'feature-grid-2'):
?>
        <section class="<?php echo htmlspecialchars($section['sectionClass'] ?? 'section section--key-features'); ?>">
            <div class="container">
                <?php if (!empty($section['heading'])): ?>
                <div class="section-header">
                    <h2><?php echo $section['heading']; ?></h2>
                    <?php if (!empty($section['subheading'])): ?>
                    <p class="section-subtitle"><?php echo $section['subheading']; ?></p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <div class="key-features-grid" style="grid-template-columns: repeat(2, 1fr); gap: 32px;">
                    <?php foreach ($section['items'] as $item): ?>
                    <div class="key-feature-item" style="padding: 40px;">
                        <div class="key-feature-icon" style="width: 56px; height: 56px;">
                            <?php echo $item['icon']; ?>
                        </div>
                        <h3 style="font-size: 22px;"><?php echo $item['title']; ?></h3>
                        <p><?php echo $item['text']; ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
<?php

    // --- Solution Features (sticky scroll style) ---
    elseif ($type === 'solution-features'):
?>
        <section class="<?php echo htmlspecialchars($section['sectionClass'] ?? 'section section--solution'); ?>">
            <div class="container">
                <div class="solution-header">
                    <h2><?php echo $section['heading']; ?></h2>
                    <?php if (!empty($section['subheading'])): ?>
                    <p><?php echo $section['subheading']; ?></p>
                    <?php endif; ?>
                </div>

                <div class="solution-features">
                    <?php foreach ($section['items'] as $item): ?>
                    <div class="solution-feature">
                        <div class="solution-feature__content">
                            <div class="solution-feature__text">
                                <h3><?php echo $item['title']; ?></h3>
                                <p><?php echo $item['text']; ?></p>
                            </div>
                            <div class="solution-feature__image">
                                <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['imageAlt']); ?>" loading="lazy">
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
<?php

    // --- Problem Cards ---
    elseif ($type === 'problem-cards'):
?>
        <section class="section section--problem">
            <div class="container">
                <div class="problem-cards">
                    <?php foreach ($section['items'] as $item): ?>
                    <div class="problem-card">
                        <div class="problem-number"><?php echo $item['number']; ?></div>
                        <h3><?php echo $item['title']; ?></h3>
                        <p><?php echo $item['text']; ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
<?php

    // --- Numbered Cards 4-grid ---
    elseif ($type === 'numbered-cards-4'):
        $bgClass = ($section['bg'] ?? 'white') === 'gray' ? 'landing-section--gray' : 'landing-section--white';
?>
        <section class="section section--white landing-section <?php echo $bgClass; ?>">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-title"><?php echo $section['heading']; ?></h2>
                    <?php if (!empty($section['subheading'])): ?>
                    <p class="section-subtitle"><?php echo $section['subheading']; ?></p>
                    <?php endif; ?>
                </div>
                <div class="landing-grid-4">
                    <?php foreach ($section['items'] as $item): ?>
                    <div class="landing-card">
                        <?php if (!empty($item['number'])): ?>
                        <div class="landing-number"><?php echo $item['number']; ?></div>
                        <?php endif; ?>
                        <?php if (!empty($item['emoji'])): ?>
                        <div class="landing-emoji"><?php echo $item['emoji']; ?></div>
                        <?php endif; ?>
                        <h4 class="landing-card-subtitle"><?php echo $item['title']; ?></h4>
                        <p class="landing-card-text-sm"><?php echo $item['text']; ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
<?php

    // --- Stats 3-grid ---
    elseif ($type === 'stats-3'):
        $bgClass = ($section['bg'] ?? 'white') === 'gray' ? 'landing-section--gray' : 'landing-section--white';
?>
        <section class="section section--white landing-section <?php echo $bgClass; ?>">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-title"<?php echo !empty($section['headingSize']) ? ' style="font-size: ' . $section['headingSize'] . '"' : ''; ?>><?php echo $section['heading']; ?></h2>
                    <?php if (!empty($section['subheading'])): ?>
                    <p class="section-subtitle"><?php echo $section['subheading']; ?></p>
                    <?php endif; ?>
                </div>
                <div class="landing-grid-3 landing-stats-grid">
                    <?php foreach ($section['items'] as $item): ?>
                    <div class="landing-card landing-stat-card">
                        <div class="landing-number"><?php echo $item['value']; ?></div>
                        <p class="landing-stat-main"><?php echo $item['label']; ?></p>
                        <?php if (!empty($item['sublabel'])): ?>
                        <p class="landing-stat-sub"><?php echo $item['sublabel']; ?></p>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
<?php

    // --- Steps 3 (numbered circles) ---
    elseif ($type === 'steps-3'):
?>
        <section class="<?php echo htmlspecialchars($section['sectionClass'] ?? 'section section--key-features'); ?>">
            <div class="container">
                <div class="section-header">
                    <h2><?php echo $section['heading']; ?></h2>
                    <?php if (!empty($section['subheading'])): ?>
                    <p class="section-subtitle"><?php echo $section['subheading']; ?></p>
                    <?php endif; ?>
                </div>
                <div class="key-features-grid" style="grid-template-columns: repeat(3, 1fr); gap: 32px;">
                    <?php foreach ($section['items'] as $item): ?>
                    <div class="key-feature-item" style="text-align: center; padding: 48px 32px;">
                        <div class="landing-step-circle">
                            <span><?php echo $item['number']; ?></span>
                        </div>
                        <h3 style="font-size: 22px; margin-bottom: 12px;"><?php echo $item['title']; ?></h3>
                        <p class="landing-card-text"><?php echo $item['text']; ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
<?php

    // --- Related Pages ---
    elseif ($type === 'related-pages'):
        $bgClass = ($section['bg'] ?? 'gray') === 'gray' ? 'landing-section--gray' : 'landing-section--white';
        $linkClass = ($section['linkStyle'] ?? 'gray') === 'white' ? 'related-link--white' : 'related-link--gray';
?>
        <section class="section section--white landing-section <?php echo $bgClass; ?>">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-title"><?php echo $section['heading']; ?></h2>
                    <?php if (!empty($section['subheading'])): ?>
                    <p class="section-subtitle"><?php echo $section['subheading']; ?></p>
                    <?php endif; ?>
                </div>
                <div class="landing-grid-3">
                    <?php foreach ($section['items'] as $item): ?>
                    <a href="<?php echo htmlspecialchars($item['href']); ?>" class="related-link <?php echo $linkClass; ?>">
                        <h3 class="related-link-title"><?php echo htmlspecialchars($item['title']); ?></h3>
                        <p class="related-link-text"><?php echo htmlspecialchars($item['text']); ?></p>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
<?php
    endif;
endforeach;
endif;
?>

        <!-- CTA Section -->
        <?php if (!empty($pageData['cta'])): ?>
        <section class="section--cta-dark">
            <div class="container">
                <h2><?php echo $pageData['cta']['heading']; ?></h2>
                <p><?php echo $pageData['cta']['text']; ?></p>
                <a href="<?php echo htmlspecialchars($pageData['cta']['buttonLink'] ?? '/kontakt'); ?>" class="button--primary"><?php echo htmlspecialchars($pageData['cta']['buttonText']); ?></a>
            </div>
        </section>
        <?php endif; ?>
    </main>

    <?php include ROOT_PATH . '/includes/footer.php'; ?>

    <script src="/assets/js/cms.js?v=<?php echo BOSSE_VERSION; ?>" defer></script>

    <?php if (function_exists('is_logged_in') && is_logged_in()): ?>
        <form style="display: none;">
            <?php echo csrf_field(); ?>
        </form>
    <?php endif; ?>

    <?php include ROOT_PATH . '/includes/cookie-consent.php'; ?>
</body>
</html>
