<?php
/**
 * Migration 1.5.76
 * Create default external CSS files for projekt/blogg views.
 * Previously these styles were inline in the PHP files; now they ship as
 * separate CSS files that survive framework updates.
 *
 * Idempotent: skips files that already exist.
 */

$base = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__);

// --- projekt-custom.css (list view) ---
$listCss = $base . '/assets/css/projekt-custom.css';
if (!file_exists($listCss)) {
    file_put_contents($listCss, <<<'CSS'
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

.projekt-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 2rem;
    padding: var(--section-padding, 4rem) 0;
}

.projekt-card {
    background: white;
    border-radius: 5px;
    overflow: hidden;
    border: 1px solid var(--color-gray-200, #e5e5e5);
    transition: all 0.3s;
    text-decoration: none;
    display: block;
}

.projekt-card:hover {
    transform: translateY(-4px);
    box-shadow: none;
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
    border-radius: 5px;
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

@media (max-width: 1024px) {
    .projekt-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .projekt-hero h1 {
        font-size: 2rem;
    }

    .projekt-grid {
        grid-template-columns: 1fr;
    }
}
CSS
    );
}

// --- projekt-single-custom.css (detail view) ---
$singleCss = $base . '/assets/css/projekt-single-custom.css';
if (!file_exists($singleCss)) {
    file_put_contents($singleCss, <<<'CSS'
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
    border-radius: 5px;
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
    border-radius: 5px;
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
    border-radius: 5px;
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
    border-radius: 5px;
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
    border-radius: 5px;
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
    border-radius: 5px;
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
    border-radius: 5px;
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 500;
}

.related-projects {
    max-width: 1200px;
    margin: 0 auto;
    padding: var(--section-padding, 4rem) 1.5rem;
}

.related-projects__title {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--color-foreground, #18181b);
    margin-bottom: 2rem;
    text-align: center;
}

.related-projects__grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.5rem;
}

.related-projects__card {
    background: var(--color-background, #fff);
    border-radius: 5px;
    overflow: hidden;
    border: 1px solid var(--color-gray-200, #e5e5e5);
    text-decoration: none;
    color: inherit;
    transition: transform 0.2s, box-shadow 0.2s;
    display: flex;
    flex-direction: column;
}

.related-projects__card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.related-projects__image {
    width: 100%;
    height: 180px;
    object-fit: cover;
}

.related-projects__body {
    padding: 1.25rem;
    flex: 1;
}

.related-projects__card-title {
    font-size: 1rem;
    font-weight: 600;
    color: var(--color-foreground, #18181b);
    margin-bottom: 0.5rem;
}

.related-projects__summary {
    font-size: 0.875rem;
    color: var(--color-gray-500, #737373);
    line-height: 1.5;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
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

    .related-projects__grid {
        grid-template-columns: 1fr;
    }
}
CSS
    );
}
