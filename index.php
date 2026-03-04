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

// Prevent caching to ensure admin bar updates correctly
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');

// Hämta senaste 4 publicerade inlägg från projects.json
$projects_file = __DIR__ . '/data/projects.json';
$latest_posts = [];

if (file_exists($projects_file)) {
    $json = file_get_contents($projects_file);
    $all_projects = json_decode($json, true) ?? [];
    
    // Filtrera endast publicerade
    $published = array_filter($all_projects, fn($p) => isset($p['status']) && $p['status'] === 'published');
    
    // Sortera efter datum (nyast först)
    usort($published, function($a, $b) {
        $dateA = $a['createdAt'] ?? '1970-01-01';
        $dateB = $b['createdAt'] ?? '1970-01-01';
        return strtotime($dateB) - strtotime($dateA);
    });
    
    // Ta de 4 senaste
    $latest_posts = array_slice($published, 0, 4);
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
        get_content('home.meta_title', 'Välkommen'),
        get_content('home.meta_description', 'Modern hemsida med CMS, SEO och säkerhet'),
        '/assets/images/og-image.jpg'
    );
    ?>
    
    <?php if (file_exists(__DIR__ . '/includes/fonts.php')) include __DIR__ . '/includes/fonts.php'; ?>
    <?php if (file_exists(__DIR__ . '/includes/analytics.php')) include __DIR__ . '/includes/analytics.php'; ?>
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
    <link rel="preload" href="/assets/css/main.css?v=<?php echo BOSSE_VERSION; ?>" as="style">
    <link rel="stylesheet" href="/assets/css/main.css?v=<?php echo BOSSE_VERSION; ?>">
    <link rel="dns-prefetch" href="https://fonts.googleapis.com">
    <link rel="dns-prefetch" href="https://www.googletagmanager.com">
    
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
        <section class="hero-section">
            <div class="hero-container">
                <div class="hero-content">
                    <?php editable_text('hero', 'title', 'Track every transaction instantly in one place', 'h1', 'hero-title'); ?>
                    <?php editable_text('hero', 'description', 'Nova helps you budget smarter, track spending, and build wealth — all in one place.', 'p', 'hero-description'); ?>
                    
                    <a href="/boka-demo" class="hero-cta">
                        <?php echo get_content('hero.cta', 'Boka demo'); ?>
                    </a>
                </div>
                
                <div class="hero-visual">
                    <img src="/uploads/blob.avif" alt="" class="hero-visual__blob" aria-hidden="true">
                    <img src="/uploads/icon.avif" alt="" class="hero-visual__icon" aria-hidden="true">
                    <img src="/uploads/Hero-phone.png" alt="Nova app interface showing transaction tracking" class="hero-visual__screen">
                </div>
            </div>
        </section>
        
        <!-- Section 2: Logo Slider & Text -->
        <section class="section-2">
            <div class="section-2__container">
                <!-- Logo Slider -->
                <div class="logo-slider">
                    <p class="logo-slider__label">Används av moderna byråer</p>
                    <div class="logo-slider__track">
                        <div class="logo-slider__content">
                            <img src="/uploads/logo.png" alt="Partner logo" class="logo-slider__logo">
                            <img src="/uploads/Jubileumslogotyp_mork.svg" alt="Rdek Redovisning och Revision AB" class="logo-slider__logo">
                            <img src="/uploads/edlings_logo.png" alt="Edlings" class="logo-slider__logo">
                            <img src="/uploads/btilly.webp" alt="Btilly" class="logo-slider__logo">
                            <img src="/uploads/logo.png" alt="Partner logo" class="logo-slider__logo">
                            <img src="/uploads/Jubileumslogotyp_mork.svg" alt="Rdek Redovisning och Revision AB" class="logo-slider__logo">
                            <img src="/uploads/edlings_logo.png" alt="Edlings" class="logo-slider__logo">
                            <img src="/uploads/btilly.webp" alt="Btilly" class="logo-slider__logo">
                            <img src="/uploads/logo.png" alt="Partner logo" class="logo-slider__logo">
                            <img src="/uploads/Jubileumslogotyp_mork.svg" alt="Rdek Redovisning och Revision AB" class="logo-slider__logo">
                            <img src="/uploads/edlings_logo.png" alt="Edlings" class="logo-slider__logo">
                            <img src="/uploads/btilly.webp" alt="Btilly" class="logo-slider__logo">
                            <img src="/uploads/logo.png" alt="Partner logo" class="logo-slider__logo">
                            <img src="/uploads/Jubileumslogotyp_mork.svg" alt="Rdek Redovisning och Revision AB" class="logo-slider__logo">
                            <img src="/uploads/edlings_logo.png" alt="Edlings" class="logo-slider__logo">
                            <img src="/uploads/btilly.webp" alt="Btilly" class="logo-slider__logo">
                        </div>
                    </div>
                </div>
                
                <!-- Text Section -->
                <div class="text-section">
                    <div class="text-section__content">
                        <?php editable_text('section2', 'heading1', 'Uppdragsbrev gör det enkelt att skapa juridiskt säkra avtal på minuter. Du samlar allt digitalt, slipper manuell hantering och säkerställer att varje uppdrag uppfyller branschens krav.', 'h2', 'text-section__heading'); ?>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Section 3: Benefits Grid -->
        <section class="section-3">
            <div class="section-3__container">
                <img src="/uploads/sektion3.png" alt="" class="section-3__image">
                <h3 class="section-3__heading">Uppdragsbrev hjälper dig att…</h3>
                
                <div class="benefits-grid">
                    <div class="benefit-card">
                        <div class="benefit-card__icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 11l3 3L22 4"></path>
                                <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                            </svg>
                        </div>
                        <?php editable_text('section3', 'card1_title', 'Uppfylla branschkraven', 'h3', 'benefit-card__title'); ?>
                        <?php editable_text('section3', 'card1_desc', 'Säkerställ att varje uppdrag har ett korrekt och juridiskt säkrat uppdragsbrev från start.', 'p', 'benefit-card__description'); ?>
                    </div>
                    
                    <div class="benefit-card">
                        <div class="benefit-card__icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="9" y1="3" x2="9" y2="21"></line>
                            </svg>
                        </div>
                        <?php editable_text('section3', 'card2_title', 'Skapa tydliga ramar', 'h3', 'benefit-card__title'); ?>
                        <?php editable_text('section3', 'card2_desc', 'Definiera ansvar, omfattning och villkor innan arbetet börjar och minska risken för missförstånd.', 'p', 'benefit-card__description'); ?>
                    </div>
                    
                    <div class="benefit-card">
                        <div class="benefit-card__icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                <line x1="16" y1="17" x2="8" y2="17"></line>
                                <polyline points="10 9 9 9 8 9"></polyline>
                            </svg>
                        </div>
                        <?php editable_text('section3', 'card3_title', 'Skapa och signera digitalt', 'h3', 'benefit-card__title'); ?>
                        <?php editable_text('section3', 'card3_desc', 'Använd mallar och skicka för juridiskt säker e-underskrift direkt i plattformen.', 'p', 'benefit-card__description'); ?>
                    </div>
                    
                    <div class="benefit-card">
                        <div class="benefit-card__icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                            </svg>
                        </div>
                        <?php editable_text('section3', 'card4_title', 'Ha full kontroll', 'h3', 'benefit-card__title'); ?>
                        <?php editable_text('section3', 'card4_desc', 'Alla avtal sparas automatiskt och hanteras tryggt enligt GDPR.', 'p', 'benefit-card__description'); ?>
                    </div>
                </div>
                
                <a href="/boka-demo" class="section-3__cta">
                    Boka kostnadsfri demo
                </a>
            </div>
        </section>
        
        <!-- Testimonial Section -->
        <section class="testimonial-section">
            <div class="testimonial-container">
                <blockquote class="testimonial-quote">
                    <?php editable_text('testimonial', 'quote', 'Nu har vi jobbat med Uppdragsbrev i fyra månader och det är fascinerande hur omoderna våra tidigare arbetssätt var. Man sitter i sina rutiner och inser inte behovet av förändring förrän den är genomförd.', 'p', 'testimonial-quote__text'); ?>
                </blockquote>
                
                <div class="testimonial-author">
                    <img src="/uploads/tobias.webp" alt="Tobias Svensson" class="testimonial-author__image">
                    <div class="testimonial-author__info">
                        <?php editable_text('testimonial', 'name', 'Tobias Svensson', 'p', 'testimonial-author__name'); ?>
                        <?php editable_text('testimonial', 'title', 'Rådek – IT och Digitalisering', 'p', 'testimonial-author__title'); ?>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Pricing Section -->
        <section class="pricing-section">
            <div class="pricing-container">
                <h2 class="pricing-heading">Välj rätt plan för ditt företag</h2>
                
                <div class="pricing-grid">
                    <!-- Bas Plan -->
                    <div class="pricing-card" data-monthly="749" data-yearly="7440">
                        <div class="pricing-card__header">
                            <h3 class="pricing-card__name">Bas</h3>
                            <div class="pricing-card__toggle">
                                <span class="pricing-card__toggle-label pricing-card__toggle-label--active" data-period="yearly">År (-17%)</span>
                                <label class="pricing-card__toggle-switch">
                                    <input type="checkbox" class="pricing-toggle-input">
                                    <span class="pricing-card__toggle-slider"></span>
                                </label>
                                <span class="pricing-card__toggle-label" data-period="monthly">Månad</span>
                            </div>
                        </div>
                        <div class="pricing-card__price">
                            <span class="pricing-card__amount" data-price="620">620 kr</span>
                            <span class="pricing-card__period">/mån</span>
                        </div>
                        <p class="pricing-card__target">Byråstorlek: 1-4 anställda</p>
                        
                        <ul class="pricing-card__features">
                            <li class="pricing-card__feature">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                    <circle cx="10" cy="10" r="10" fill="currentColor" opacity="0.15"/>
                                    <path d="M6 10l2.5 2.5L14 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                Full åtkomst till portalen
                            </li>
                            <li class="pricing-card__feature">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                    <circle cx="10" cy="10" r="10" fill="currentColor" opacity="0.15"/>
                                    <path d="M6 10l2.5 2.5L14 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                Koppling mot SCB företagsregister
                            </li>
                            <li class="pricing-card__feature">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                    <circle cx="10" cy="10" r="10" fill="currentColor" opacity="0.15"/>
                                    <path d="M6 10l2.5 2.5L14 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                Er branding (white label)
                            </li>
                        </ul>
                        
                        <a href="/boka-demo" class="pricing-card__cta">Kom igång</a>
                    </div>
                    
                    <!-- Standard Plan -->
                    <div class="pricing-card" data-monthly="1699" data-yearly="17040">
                        <div class="pricing-card__header">
                            <h3 class="pricing-card__name">Standard</h3>
                            <div class="pricing-card__toggle">
                                <span class="pricing-card__toggle-label pricing-card__toggle-label--active" data-period="yearly">År (-17%)</span>
                                <label class="pricing-card__toggle-switch">
                                    <input type="checkbox" class="pricing-toggle-input">
                                    <span class="pricing-card__toggle-slider"></span>
                                </label>
                                <span class="pricing-card__toggle-label" data-period="monthly">Månad</span>
                            </div>
                        </div>
                        <div class="pricing-card__price">
                            <span class="pricing-card__amount" data-price="1420">1 420 kr</span>
                            <span class="pricing-card__period">/mån</span>
                        </div>
                        <p class="pricing-card__target">Byråstorlek: 5-15 anställda</p>
                        
                        <ul class="pricing-card__features">
                            <li class="pricing-card__feature">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                    <circle cx="10" cy="10" r="10" fill="currentColor" opacity="0.15"/>
                                    <path d="M6 10l2.5 2.5L14 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                Full åtkomst till portalen
                            </li>
                            <li class="pricing-card__feature">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                    <circle cx="10" cy="10" r="10" fill="currentColor" opacity="0.15"/>
                                    <path d="M6 10l2.5 2.5L14 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                Koppling mot SCB företagsregister
                            </li>
                            <li class="pricing-card__feature">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                    <circle cx="10" cy="10" r="10" fill="currentColor" opacity="0.15"/>
                                    <path d="M6 10l2.5 2.5L14 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                Er branding (white label)
                            </li>
                        </ul>
                        
                        <a href="/boka-demo" class="pricing-card__cta">Kom igång</a>
                    </div>
                    
                    <!-- Premium Plan -->
                    <div class="pricing-card" data-monthly="3499" data-yearly="35040">
                        <div class="pricing-card__header">
                            <h3 class="pricing-card__name">Premium</h3>
                            <div class="pricing-card__toggle">
                                <span class="pricing-card__toggle-label pricing-card__toggle-label--active" data-period="yearly">År (-17%)</span>
                                <label class="pricing-card__toggle-switch">
                                    <input type="checkbox" class="pricing-toggle-input">
                                    <span class="pricing-card__toggle-slider"></span>
                                </label>
                                <span class="pricing-card__toggle-label" data-period="monthly">Månad</span>
                            </div>
                        </div>
                        <div class="pricing-card__price">
                            <span class="pricing-card__amount" data-price="2920">2 920 kr</span>
                            <span class="pricing-card__period">/mån</span>
                        </div>
                        <p class="pricing-card__target">Byråstorlek: 16-30 anställda</p>
                        
                        <ul class="pricing-card__features">
                            <li class="pricing-card__feature">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                    <circle cx="10" cy="10" r="10" fill="currentColor" opacity="0.15"/>
                                    <path d="M6 10l2.5 2.5L14 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                Full åtkomst till portalen
                            </li>
                            <li class="pricing-card__feature">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                    <circle cx="10" cy="10" r="10" fill="currentColor" opacity="0.15"/>
                                    <path d="M6 10l2.5 2.5L14 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                Koppling mot SCB företagsregister
                            </li>
                            <li class="pricing-card__feature">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                    <circle cx="10" cy="10" r="10" fill="currentColor" opacity="0.15"/>
                                    <path d="M6 10l2.5 2.5L14 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                Er branding (white label)
                            </li>
                        </ul>
                        
                        <a href="/boka-demo" class="pricing-card__cta">Kom igång</a>
                    </div>
                    
                    <!-- Enterprise Plan -->
                    <div class="pricing-card pricing-card--enterprise">
                        <div class="pricing-card__header">
                            <h3 class="pricing-card__name">Enterprise</h3>
                        </div>
                        <div class="pricing-card__price">
                            <span class="pricing-card__amount">Kontakta oss</span>
                        </div>
                        <p class="pricing-card__target">Byråstorlek: 30+ anställda</p>
                        
                        <ul class="pricing-card__features">
                            <li class="pricing-card__feature">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                    <circle cx="10" cy="10" r="10" fill="currentColor" opacity="0.15"/>
                                    <path d="M6 10l2.5 2.5L14 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                Full åtkomst till portalen
                            </li>
                            <li class="pricing-card__feature">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                    <circle cx="10" cy="10" r="10" fill="currentColor" opacity="0.15"/>
                                    <path d="M6 10l2.5 2.5L14 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                Koppling mot SCB företagsregister
                            </li>
                            <li class="pricing-card__feature">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                    <circle cx="10" cy="10" r="10" fill="currentColor" opacity="0.15"/>
                                    <path d="M6 10l2.5 2.5L14 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                Er branding (white label)
                            </li>
                        </ul>
                        
                        <a href="#" class="pricing-card__cta">Kontakta oss</a>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Blog Section -->
        <section class="blog-section">
            <div class="blog-container">
                <h2 class="blog-heading">Håll dig uppdaterad</h2>
                
                <?php if (!empty($latest_posts)): ?>
                <div class="blog-grid">
                    <?php 
                    // Första inlägget blir featured
                    $featured = $latest_posts[0];
                    $list_posts = array_slice($latest_posts, 1);
                    ?>
                    
                    <!-- Featured Article -->
                    <article class="blog-card blog-card--featured">
                        <a href="/inlagg/<?php echo htmlspecialchars($featured['slug'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="blog-card__link">
                            <div class="blog-card__image">
                                <?php if (!empty($featured['coverImage'])): ?>
                                    <img src="<?php echo htmlspecialchars($featured['coverImage'], ENT_QUOTES, 'UTF-8'); ?>" 
                                         alt="<?php echo htmlspecialchars($featured['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                <?php endif; ?>
                            </div>
                            <div class="blog-card__content">
                                <span class="blog-card__badge">✨ UTVALD</span>
                                <h3 class="blog-card__title"><?php echo htmlspecialchars($featured['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?></h3>
                                <p class="blog-card__excerpt"><?php echo htmlspecialchars($featured['summary'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
                                <div class="blog-card__meta">
                                    <div class="blog-card__author">
                                        <div class="blog-card__author-info">
                                            <span class="blog-card__author-name"><?php echo strtoupper(getRandomAuthor($featured['slug'] ?? '')); ?></span>
                                            <span class="blog-card__date"><?php echo strtoupper(date('j M Y', strtotime($featured['createdAt'] ?? 'now'))); ?></span>
                                        </div>
                                    </div>
                                    <?php if (!empty($featured['category'])): ?>
                                        <span class="blog-card__category blog-card__category--<?php echo getCategoryClass($featured['category']); ?>">
                                            <?php echo strtoupper($featured['category']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
                    </article>
                    
                    <div class="blog-list">
                        <?php foreach ($list_posts as $post): ?>
                        <article class="blog-card blog-card--list">
                            <a href="/inlagg/<?php echo htmlspecialchars($post['slug'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="blog-card__list-link">
                                <h3 class="blog-card__list-title"><?php echo htmlspecialchars($post['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?></h3>
                                <div class="blog-card__list-meta">
                                    <span class="blog-card__author-name"><?php echo strtoupper(getRandomAuthor($post['slug'] ?? '')); ?></span>
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
                <?php else: ?>
                <p style="text-align: center; color: var(--color-gray-600);">Inga inlägg att visa ännu.</p>
                <?php endif; ?>
            </div>
        </section>
    </main>
    
    <?php include __DIR__ . '/includes/footer.php'; ?>
    
    <script src="/assets/js/cms.js?v=<?php echo BOSSE_VERSION; ?>" defer></script>
    <script src="/assets/js/pricing-toggle.js?v=<?php echo BOSSE_VERSION; ?>" defer></script>
    <script src="/assets/js/hero-parallax.js?v=<?php echo BOSSE_VERSION; ?>" defer></script>
    
    <?php if (is_logged_in()): ?>
        <!-- CSRF token för CMS -->
        <form style="display: none;">
            <?php echo csrf_field(); ?>
        </form>
    <?php endif; ?>
    
    <?php include __DIR__ . '/includes/cookie-consent.php'; ?>
</body>
</html>
