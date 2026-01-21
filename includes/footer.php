<?php
/**
 * Footer Component
 * Global footer
 */
?>
<footer class="footer">
    <div class="container">
        <div class="footer__content">
            <div class="footer__section">
                <div class="footer__logo">
                    <img src="/assets/images/logo-light.png" alt="<?php echo SITE_NAME; ?>" class="footer__logo-img">
                </div>
                <?php editable_text('footer.description', 'Din partner för digitala lösningar', 'p', 'footer__text'); ?>
            </div>
            
            <div class="footer__section">
                <h4 class="footer__title">Snabblänkar</h4>
                <nav class="footer__nav">
                    <a href="/" class="footer__link">Hem</a>
                    <a href="/om-oss" class="footer__link">Om oss</a>
                    <a href="/tjanster" class="footer__link">Tjänster</a>
                    <a href="/kontakt" class="footer__link">Kontakt</a>
                </nav>
            </div>
            
            <div class="footer__section">
                <h4 class="footer__title">Kontakt</h4>
                <div class="footer__contact">
                    <?php editable_text('footer.email', 'info@example.com', 'p', 'footer__text'); ?>
                    <?php editable_text('footer.phone', '+46 70 123 45 67', 'p', 'footer__text'); ?>
                </div>
            </div>
        </div>
        
        <div class="footer__bottom">
            <p>&copy; <?php echo date('Y'); ?> <?php echo get_content('footer.company_name', SITE_NAME); ?>. Alla rättigheter förbehållna.</p>
            <div class="footer__links">
                <a href="/integritetspolicy" class="footer__link">Integritetspolicy</a>
                <a href="/cookies" class="footer__link">Cookies</a>
            </div>
        </div>
    </div>
</footer>

<style>
.footer {
    background-color: var(--color-gray-900);
    color: var(--color-gray-100);
    padding: 4rem 0 2rem;
    margin-top: 4rem;
}

.footer__content {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 3rem;
    margin-bottom: 3rem;
}

.footer__section {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.footer__logo {
    margin-bottom: 0.5rem;
}

.footer__logo-img {
    height: 2.5rem;
    width: auto;
}

.footer__title {
    font-size: var(--text-lg);
    font-weight: var(--font-semibold);
    color: white;
    margin-bottom: 0.5rem;
}

.footer__text {
    color: var(--color-gray-300);
    line-height: var(--leading-relaxed);
    margin-bottom: 0.5rem;
}

.footer__nav {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.footer__link {
    color: var(--color-gray-300);
    text-decoration: none;
    transition: color var(--transition-fast);
}

.footer__link:hover {
    color: white;
    text-decoration: none;
}

.footer__contact {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.footer__bottom {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 2rem;
    border-top: 1px solid var(--color-gray-700);
    font-size: var(--text-sm);
    color: var(--color-gray-400);
}

.footer__links {
    display: flex;
    gap: 1.5rem;
}

@media (max-width: 768px) {
    .footer__content {
        grid-template-columns: 1fr;
        gap: 2rem;
    }
    
    .footer__bottom {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
}
</style>
