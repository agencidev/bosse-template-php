<?php
/**
 * Boka Demo Page
 * Conversion-optimized demo booking page
 */

require_once __DIR__ . '/../bootstrap.php';

$page_title = 'Boka Demo - ' . SITE_NAME;
$page_description = 'Boka en kostnadsfri demo och se hur Uppdragsbrev kan förenkla din avtalshantering.';
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <?php include __DIR__ . '/../seo/meta.php'; ?>
    <link rel="stylesheet" href="/assets/css/main.css?v=<?php echo BOSSE_VERSION; ?>">
    <style>
    .demo-page {
        background: var(--color-gray-100);
        padding: var(--spacing-16) var(--spacing-4);
    }

    .demo-container {
        max-width: 600px;
        margin: 0 auto;
    }

    .demo-header {
        text-align: center;
        margin-bottom: var(--spacing-8);
    }

    .demo-header__title {
        font-size: 36px;
        font-weight: 600;
        color: var(--color-foreground);
        line-height: 1.2;
        margin: 0 0 var(--spacing-3) 0;
    }

    .demo-header__subtitle {
        font-size: 18px;
        color: var(--color-gray-600);
        margin: 0;
        line-height: 1.5;
    }

    /* Contact Person */
    .demo-contact {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        margin-bottom: var(--spacing-8);
    }

    .demo-contact__avatar {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        object-fit: cover;
        margin-bottom: var(--spacing-4);
    }

    .demo-contact__info {
        max-width: 400px;
    }

    .demo-contact__name {
        font-size: 20px;
        font-weight: 600;
        color: var(--color-foreground);
        margin: 0 0 var(--spacing-1) 0;
    }

    .demo-contact__title {
        font-size: 14px;
        color: var(--color-gray-600);
        margin: 0;
    }

    .demo-form-wrapper {
        background: white;
        border-radius: var(--radius-xl);
        padding: var(--spacing-8);
        margin-bottom: var(--spacing-6);
    }

    .demo-form__group {
        margin-bottom: var(--spacing-4);
    }

    .demo-form__label {
        display: block;
        font-size: var(--text-sm);
        font-weight: var(--font-semibold);
        color: var(--color-foreground);
        margin-bottom: var(--spacing-2);
    }

    .demo-form__input,
    .demo-form__select {
        width: 100%;
        padding: 0.75rem 1rem;
        border: 1px solid var(--color-gray-300);
        background: white;
        border-radius: var(--radius-md);
        font-size: var(--text-base);
        font-family: inherit;
        transition: border-color 0.2s;
    }

    .demo-form__input:focus,
    .demo-form__select:focus {
        outline: none;
        border-color: var(--color-primary);
    }

    .demo-form__submit {
        width: 100%;
        padding: 0.875rem;
        background: var(--color-primary);
        color: white;
        border: none;
        border-radius: var(--radius-md);
        font-size: var(--text-base);
        font-weight: var(--font-semibold);
        cursor: pointer;
        transition: all 0.2s;
        margin-top: var(--spacing-2);
    }

    .demo-form__submit:hover {
        background: var(--color-primary-dark);
        color: white;
        text-decoration: none;
        transform: translateY(-2px);
    }

    /* Contact Information */
    .demo-contact-info {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: var(--spacing-4);
    }

    .demo-contact-info__item {
        display: flex;
        align-items: center;
        gap: var(--spacing-3);
        background: white;
        padding: var(--spacing-5);
        border-radius: var(--radius-lg);
    }

    .demo-contact-info__icon {
        width: 40px;
        height: 40px;
        background: rgba(79, 70, 229, 0.1);
        border-radius: var(--radius-md);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--color-primary);
        flex-shrink: 0;
    }

    .demo-contact-info__info {
        flex: 1;
    }

    .demo-contact-info__label {
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--color-gray-500);
        margin: 0 0 4px 0;
    }

    .demo-contact-info__value {
        font-size: 15px;
        font-weight: 600;
        color: var(--color-foreground);
        margin: 0;
    }

    .demo-contact-info__value a {
        color: var(--color-foreground);
        text-decoration: none;
    }

    .demo-contact-info__value a:hover {
        color: var(--color-primary);
    }

    @media (max-width: 768px) {
        .demo-contact-info {
            grid-template-columns: 1fr;
        }
        .demo-header__title {
            font-size: 28px;
        }

        .demo-form-wrapper {
            padding: var(--spacing-6);
        }
    }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/admin-bar.php'; ?>
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <main id="main-content">
        <section class="demo-page">
            <div class="demo-container">
                <div class="demo-header">
                    <h1 class="demo-header__title">Boka en kostnadsfri demo</h1>
                    <p class="demo-header__subtitle">Se hur vi kan spara er 10+ timmar i veckan. Fyll i formuläret så kontaktar jag dig inom 24 timmar.</p>
                </div>

                <!-- Contact Person -->
                <div class="demo-contact">
                    <img src="/uploads/tobias.webp" alt="Tobias Svensson" class="demo-contact__avatar">
                    <div class="demo-contact__info">
                        <h3 class="demo-contact__name">Tobias Svensson</h3>
                        <p class="demo-contact__title">IT och Digitalisering</p>
                    </div>
                </div>

                <div class="demo-form-wrapper">
                    <form class="demo-form" method="POST" action="/api/demo-request">
                        <div class="demo-form__group">
                            <label for="name" class="demo-form__label">Namn *</label>
                            <input type="text" id="name" name="name" class="demo-form__input" required placeholder="Ditt namn">
                        </div>

                        <div class="demo-form__group">
                            <label for="email" class="demo-form__label">E-post *</label>
                            <input type="email" id="email" name="email" class="demo-form__input" required placeholder="din@email.com">
                        </div>

                        <div class="demo-form__group">
                            <label for="phone" class="demo-form__label">Telefon</label>
                            <input type="tel" id="phone" name="phone" class="demo-form__input" placeholder="+46 70 000 00 00">
                        </div>

                        <div class="demo-form__group">
                            <label for="company" class="demo-form__label">Företag *</label>
                            <input type="text" id="company" name="company" class="demo-form__input" required placeholder="Ditt företag">
                        </div>

                        <div class="demo-form__group">
                            <label for="company_size" class="demo-form__label">Företagsstorlek</label>
                            <select id="company_size" name="company_size" class="demo-form__select">
                                <option value="">Välj...</option>
                                <option value="1-4">1-4 anställda</option>
                                <option value="5-15">5-15 anställda</option>
                                <option value="16-30">16-30 anställda</option>
                                <option value="31+">31+ anställda</option>
                            </select>
                        </div>

                        <button type="submit" class="demo-form__submit">Boka demo nu</button>
                    </form>
                </div>

                <!-- Contact Information -->
                <div class="demo-contact-info">
                    <div class="demo-contact-info__item">
                        <div class="demo-contact-info__icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                <polyline points="22,6 12,13 2,6"/>
                            </svg>
                        </div>
                        <div class="demo-contact-info__info">
                            <p class="demo-contact-info__label">E-POST</p>
                            <p class="demo-contact-info__value">
                                <a href="mailto:support@uppdragsbrev.se">support@uppdragsbrev.se</a>
                            </p>
                        </div>
                    </div>

                    <div class="demo-contact-info__item">
                        <div class="demo-contact-info__icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                            </svg>
                        </div>
                        <div class="demo-contact-info__info">
                            <p class="demo-contact-info__label">TELEFON</p>
                            <p class="demo-contact-info__value">
                                <a href="tel:+46701234567">+46 70 123 45 67</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

</body>
</html>
