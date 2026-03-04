<?php
/**
 * Footer Component
 * Global footer
 */
?>
<footer class="footer">
    <!-- CTA Section -->
    <div class="footer__cta">
        <div class="container">
            <h2 class="footer__cta-title">Skapa uppdragsbrev på minuter och få full kontroll över varje uppdrag.</h2>
            <a href="/boka-demo" class="footer__cta-button">
                Boka demo & kom igång
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M5 12h14M12 5l7 7-7 7"/>
                </svg>
            </a>
        </div>
    </div>

    <!-- Bottom Bar -->
    <div class="footer__bottom">
        <div class="container">
            <div class="footer__bottom-content">
                <p class="footer__copyright">© <?php echo date('Y'); ?></p>
                <nav class="footer__nav">
                    <a href="/kontakt" class="footer__link">Support</a>
                    <a href="/inlagg" class="footer__link">Resurser</a>
                    <a href="/integritetspolicy" class="footer__link">Integritetspolicy</a>
                    <a href="/cookies" class="footer__link">Användarvillkor</a>
                </nav>
            </div>
        </div>
    </div>
</footer>

<style>
.footer {
    margin-top: 0;
}

/* CTA Section */
.footer__cta {
    background: var(--color-gray-100);
    padding: var(--spacing-24) var(--spacing-4);
    text-align: left;
}

.footer__cta-title {
    font-size: 48px;
    font-weight: 500;
    color: var(--color-foreground);
    line-height: 1.2;
    letter-spacing: -0.02em;
    margin: 0 0 var(--spacing-8) 0;
    max-width: 800px;
}

.footer__cta-button {
    display: inline-flex;
    align-items: center;
    gap: var(--spacing-2);
    padding: 16px 32px;
    background: var(--color-primary);
    color: white;
    text-decoration: none;
    border-radius: 50px;
    font-size: 16px;
    font-weight: 500;
    transition: all 0.2s;
}

.footer__cta-button:hover {
    background: var(--color-primary-dark);
    color: white;
    text-decoration: none;
    transform: translateX(4px);
}

/* Bottom Bar */
.footer__bottom {
    background: white;
    padding: var(--spacing-6) var(--spacing-4);
    border-top: 1px solid var(--color-gray-200);
}

.footer__bottom-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: var(--spacing-6);
}

.footer__copyright {
    font-size: 14px;
    color: var(--color-gray-600);
    margin: 0;
}

.footer__nav {
    display: flex;
    align-items: center;
    gap: var(--spacing-8);
}

.footer__link {
    font-size: 14px;
    color: var(--color-gray-600);
    text-decoration: none;
    transition: color 0.2s;
}

.footer__link:hover {
    color: var(--color-foreground);
    text-decoration: none;
}

@media (max-width: 768px) {
    .footer__cta-title {
        font-size: 32px;
    }
    
    .footer__bottom-content {
        flex-direction: column;
        align-items: flex-start;
        gap: var(--spacing-4);
    }
    
    .footer__nav {
        flex-direction: column;
        align-items: flex-start;
        gap: var(--spacing-3);
    }
}
</style>
