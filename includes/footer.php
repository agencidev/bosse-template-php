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
                    <?php if (defined('HOURS_WEEKDAYS') && HOURS_WEEKDAYS): ?>
                    <p class="footer__text" style="margin-top: 0.5rem;">
                        <strong>Öppettider</strong><br>
                        Mån-Fre: <?php echo htmlspecialchars(HOURS_WEEKDAYS); ?><br>
                        <?php if (defined('HOURS_WEEKENDS') && HOURS_WEEKENDS): ?>
                        Lör-Sön: <?php echo htmlspecialchars(HOURS_WEEKENDS); ?>
                        <?php endif; ?>
                    </p>
                    <?php endif; ?>
                </div>
                <?php
                $socialLinks = [];
                if (defined('SOCIAL_FACEBOOK') && SOCIAL_FACEBOOK) $socialLinks['facebook'] = SOCIAL_FACEBOOK;
                if (defined('SOCIAL_INSTAGRAM') && SOCIAL_INSTAGRAM) $socialLinks['instagram'] = SOCIAL_INSTAGRAM;
                if (defined('SOCIAL_LINKEDIN') && SOCIAL_LINKEDIN) $socialLinks['linkedin'] = SOCIAL_LINKEDIN;
                if (!empty($socialLinks)):
                ?>
                <div class="footer__social">
                    <?php if (isset($socialLinks['facebook'])): ?>
                    <a href="<?php echo htmlspecialchars($socialLinks['facebook']); ?>" target="_blank" rel="noopener noreferrer" class="footer__social-link" aria-label="Facebook">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                    </a>
                    <?php endif; ?>
                    <?php if (isset($socialLinks['instagram'])): ?>
                    <a href="<?php echo htmlspecialchars($socialLinks['instagram']); ?>" target="_blank" rel="noopener noreferrer" class="footer__social-link" aria-label="Instagram">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                    </a>
                    <?php endif; ?>
                    <?php if (isset($socialLinks['linkedin'])): ?>
                    <a href="<?php echo htmlspecialchars($socialLinks['linkedin']); ?>" target="_blank" rel="noopener noreferrer" class="footer__social-link" aria-label="LinkedIn">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
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

.footer__social {
    display: flex;
    gap: 1rem;
    margin-top: 1rem;
}

.footer__social-link {
    color: var(--color-gray-400);
    transition: color var(--transition-fast);
    display: flex;
    align-items: center;
}

.footer__social-link:hover {
    color: white;
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
