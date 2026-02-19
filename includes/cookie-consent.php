<?php
/**
 * Cookie Consent Banner med Google Consent Mode v2
 * Automatisk GDPR-kompatibel cookie policy
 */

// Hämta företagsinformation från config
$company_name = defined('SITE_NAME') ? SITE_NAME : 'Företaget';
$contact_email = defined('CONTACT_EMAIL') ? CONTACT_EMAIL : 'info@example.com';
$contact_phone = defined('CONTACT_PHONE') ? CONTACT_PHONE : '';
?>

<!-- Cookie Consent Banner -->
<div id="cookie-consent-banner" class="cookie-consent-banner" style="display: none;">
    <div class="cookie-banner-content">
        <h3 class="cookie-banner-title">Vi använder cookies</h3>
        <p class="cookie-banner-text">
            Vi använder cookies för att förbättra din upplevelse på vår webbplats. Vissa cookies är nödvändiga för att webbplatsen ska fungera, medan andra hjälper oss att förstå hur du använder sajten. Du kan läsa mer i vår <a href="/cookies" style="color: white; text-decoration: underline;">cookie policy</a> och <a href="/integritetspolicy" style="color: white; text-decoration: underline;">integritetspolicy</a>.
        </p>
        <div class="cookie-banner-actions">
            <button id="cookie-accept-all" class="cookie-banner-btn cookie-banner-btn-primary">Acceptera alla</button>
            <button id="cookie-accept-necessary" class="cookie-banner-btn cookie-banner-btn-secondary">Endast nödvändiga</button>
            <button id="cookie-settings-link" class="cookie-banner-btn cookie-banner-btn-tertiary">Hantera inställningar</button>
        </div>
    </div>
</div>

<!-- Cookie Settings Modal -->
<div id="cookie-settings-modal" class="cookie-modal" style="display: none;">
    <div class="cookie-modal-content">
        <div class="cookie-modal-header">
            <h2>Cookie-inställningar</h2>
            <button id="cookie-modal-close" class="cookie-modal-close">✕</button>
        </div>
        
        <div class="cookie-modal-body">
            <p class="cookie-modal-description">
                Vi använder cookies för att säkerställa grundläggande funktioner på webbplatsen och för att förbättra din upplevelse. Du kan välja vilka kategorier av cookies du vill tillåta.
            </p>

            <div class="cookie-category">
                <div class="cookie-category-header" data-toggle="necessary">
                    <button class="cookie-category-toggle">
                        <svg class="cookie-chevron" width="16" height="16" viewBox="0 0 16 16" fill="none">
                            <path d="M4 6L8 10L12 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span>Nödvändiga cookies</span>
                    </button>
                    <label class="cookie-toggle">
                        <input type="checkbox" checked disabled>
                        <span class="cookie-toggle-slider"></span>
                    </label>
                </div>
                <div class="cookie-category-content" id="necessary-content" style="display: none;">
                    <p>Dessa cookies är nödvändiga för att webbplatsen ska fungera korrekt och kan inte stängas av.</p>
                </div>
            </div>

            <div class="cookie-category">
                <div class="cookie-category-header" data-toggle="analytics">
                    <button class="cookie-category-toggle">
                        <svg class="cookie-chevron" width="16" height="16" viewBox="0 0 16 16" fill="none">
                            <path d="M4 6L8 10L12 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span>Analytiska cookies</span>
                    </button>
                    <label class="cookie-toggle">
                        <input type="checkbox" id="cookie-analytics" checked>
                        <span class="cookie-toggle-slider"></span>
                    </label>
                </div>
                <div class="cookie-category-content" id="analytics-content" style="display: none;">
                    <p>Hjälper oss att förstå hur besökare interagerar med webbplatsen genom att samla in och rapportera information anonymt.</p>
                </div>
            </div>

            <div class="cookie-category">
                <div class="cookie-category-header" data-toggle="functional">
                    <button class="cookie-category-toggle">
                        <svg class="cookie-chevron" width="16" height="16" viewBox="0 0 16 16" fill="none">
                            <path d="M4 6L8 10L12 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span>Funktionella cookies</span>
                    </button>
                    <label class="cookie-toggle">
                        <input type="checkbox" id="cookie-functional">
                        <span class="cookie-toggle-slider"></span>
                    </label>
                </div>
                <div class="cookie-category-content" id="functional-content" style="display: none;">
                    <p>Gör det möjligt för webbplatsen att komma ihåg val du gör och ge förbättrade, mer personliga funktioner.</p>
                </div>
            </div>

            <div class="cookie-category">
                <div class="cookie-category-header" data-toggle="marketing">
                    <button class="cookie-category-toggle">
                        <svg class="cookie-chevron" width="16" height="16" viewBox="0 0 16 16" fill="none">
                            <path d="M4 6L8 10L12 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span>Marknadsföringscookies</span>
                    </button>
                    <label class="cookie-toggle">
                        <input type="checkbox" id="cookie-marketing">
                        <span class="cookie-toggle-slider"></span>
                    </label>
                </div>
                <div class="cookie-category-content" id="marketing-content" style="display: none;">
                    <p>Används för att spåra besökare över webbplatser för att visa relevanta annonser.</p>
                </div>
            </div>

            <div class="cookie-info-section">
                <button class="cookie-info-toggle" id="more-info-toggle">
                    <span>Mer information</span>
                    <svg class="cookie-chevron" width="16" height="16" viewBox="0 0 16 16" fill="none">
                        <path d="M4 6L8 10L12 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
                <div class="cookie-info-content" id="more-info-content" style="display: none;">
                    <p><strong>Kontakt:</strong> <?php echo htmlspecialchars($contact_email); ?></p>
                    <p><strong>Företag:</strong> <?php echo htmlspecialchars($company_name); ?></p>
                </div>
            </div>

            <div class="cookie-info-section">
                <button class="cookie-info-toggle" id="cookie-policy-toggle">
                    <span>Cookie policy</span>
                    <svg class="cookie-chevron" width="16" height="16" viewBox="0 0 16 16" fill="none">
                        <path d="M4 6L8 10L12 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
                <div class="cookie-info-content" id="cookie-policy-content" style="display: none;">
                    <p><strong><?php echo htmlspecialchars($company_name); ?></strong> använder cookies på denna webbplats. Nedan beskrivs vilka cookies vi använder och varför.</p>

                    <p style="margin-top: 0.75rem;"><strong style="color: var(--color-primary, #8b5cf6);">Nödvändiga cookies</strong><br>
                    Dessa krävs för att webbplatsen ska fungera korrekt, t.ex. sessionshantering och CSRF-skydd. De kan inte stängas av. Lagringstid: sessionens längd.</p>

                    <p style="margin-top: 0.75rem;"><strong style="color: var(--color-primary, #8b5cf6);">Analytiska cookies</strong><br>
                    Hjälper oss förstå hur besökare använder webbplatsen genom anonymiserad statistik (t.ex. Google Analytics). Lagringstid: upp till 365 dagar.</p>

                    <p style="margin-top: 0.75rem;"><strong style="color: var(--color-primary, #8b5cf6);">Funktionella cookies</strong><br>
                    Gör det möjligt att komma ihåg dina val, t.ex. språk och visningsinställningar. Lagringstid: upp till 365 dagar.</p>

                    <p style="margin-top: 0.75rem;"><strong style="color: var(--color-primary, #8b5cf6);">Marknadsföringscookies</strong><br>
                    Används för att visa relevanta annonser baserat på dina intressen. Lagringstid: upp till 365 dagar.</p>

                    <p style="margin-top: 0.75rem;"><strong>Ditt samtycke</strong><br>
                    Ditt samtycke sparas i 365 dagar. Du kan när som helst ändra dina inställningar via cookie-inställningarna.</p>

                    <p style="margin-top: 0.75rem;"><strong>Kontakt</strong><br>
                    Har du frågor om vår cookie policy? Kontakta oss på <a href="mailto:<?php echo htmlspecialchars($contact_email); ?>" style="color: var(--color-primary, #8b5cf6);"><?php echo htmlspecialchars($contact_email); ?></a><?php if (!empty($contact_phone)): ?> eller ring <strong><?php echo htmlspecialchars($contact_phone); ?></strong><?php endif; ?>.</p>
                </div>
            </div>
        </div>

        <div class="cookie-modal-footer">
            <button id="cookie-accept-all-modal" class="cookie-modal-btn cookie-modal-btn-primary">Acceptera alla</button>
            <button id="cookie-accept-necessary-modal" class="cookie-modal-btn cookie-modal-btn-secondary">Endast nödvändiga</button>
            <button id="cookie-save-settings" class="cookie-modal-btn cookie-modal-btn-tertiary">Spara inställningar</button>
        </div>
    </div>
</div>

<style>
/* Cookie Consent Banner - Mörk design som bilderna */
.cookie-consent-banner {
    position: fixed;
    bottom: 1.5rem;
    left: 1.5rem;
    background: #054547;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    z-index: 9999;
    border-radius: 0.75rem;
    max-width: 400px;
    animation: slideInLeft 0.4s ease-out;
}

@keyframes slideInLeft {
    from {
        transform: translateX(-100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

.cookie-banner-content {
    padding: 1.75rem;
}

.cookie-banner-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: white;
    margin: 0 0 1rem 0;
}

.cookie-banner-text {
    font-size: 0.875rem;
    color: #b3b3b3;
    line-height: 1.6;
    margin: 0 0 1.5rem 0;
}

.cookie-banner-text strong {
    color: white;
    font-weight: 600;
}

.cookie-banner-actions {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.cookie-banner-btn {
    width: 100%;
    padding: 0.875rem 1.25rem;
    border-radius: 9999px;
    font-size: 0.9375rem;
    font-weight: 600;
    cursor: pointer;
    border: none;
    transition: all 0.2s;
    text-align: center;
}

.cookie-banner-btn-primary {
    background: white;
    color: #033234;
}

.cookie-banner-btn-primary:hover {
    background: #f0f0f0;
}

.cookie-banner-btn-secondary {
    background: white;
    color: #033234;
}

.cookie-banner-btn-secondary:hover {
    background: #f0f0f0;
}

.cookie-banner-btn-tertiary {
    background: transparent;
    color: white;
    border: 1px solid rgba(255, 255, 255, 0.3);
}

.cookie-banner-btn-tertiary:hover {
    background: rgba(255, 255, 255, 0.1);
    border-color: rgba(255, 255, 255, 0.5);
}

/* Cookie Modal */
.cookie-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    animation: fadeIn 0.2s ease-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

.cookie-modal-content {
    background: #033234;
    border-radius: 0.75rem;
    max-width: 680px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    animation: scaleIn 0.2s ease-out;
}

@keyframes scaleIn {
    from {
        transform: scale(0.9);
    }
    to {
        transform: scale(1);
    }
}

.cookie-modal-header {
    padding: 1.5rem 1.75rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.cookie-modal-header h2 {
    font-size: 1.25rem;
    font-weight: 600;
    color: white;
}

.cookie-modal-close {
    background: rgba(255, 255, 255, 0.1);
    border: none;
    font-size: 1.25rem;
    color: white;
    cursor: pointer;
    line-height: 1;
    width: 32px;
    height: 32px;
    border-radius: 0.375rem;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.2s;
}

.cookie-modal-close:hover {
    background: rgba(255, 255, 255, 0.2);
}

.cookie-modal-body {
    padding: 1.75rem;
}

.cookie-modal-description {
    font-size: 0.875rem;
    color: #b3b3b3;
    line-height: 1.6;
    margin: 0 0 1.5rem 0;
}

.cookie-category {
    background: #054547;
    border-radius: 0.5rem;
    margin-bottom: 0.75rem;
    overflow: hidden;
}

.cookie-category-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.25rem;
    gap: 1rem;
}

.cookie-category-toggle {
    background: none;
    border: none;
    color: white;
    font-size: 0.9375rem;
    font-weight: 500;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex: 1;
    text-align: left;
    padding: 0;
}

.cookie-chevron {
    transition: transform 0.2s;
    color: #b3b3b3;
}

.cookie-category-toggle.active .cookie-chevron {
    transform: rotate(180deg);
}

.cookie-category-content {
    padding: 0 1.25rem 1rem 1.25rem;
    font-size: 0.875rem;
    color: #b3b3b3;
    line-height: 1.6;
}

.cookie-category-content p {
    margin: 0;
}

.cookie-toggle {
    position: relative;
    display: inline-block;
    width: 48px;
    height: 24px;
    flex-shrink: 0;
}

.cookie-toggle input {
    opacity: 0;
    width: 0;
    height: 0;
}

.cookie-toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(255,255,255,0.20);
    transition: 0.3s;
    border-radius: 24px;
}

.cookie-toggle-slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: 0.3s;
    border-radius: 50%;
}

.cookie-toggle input:checked + .cookie-toggle-slider {
    background-color: var(--color-primary, #379b83);
}

.cookie-toggle input:checked + .cookie-toggle-slider:before {
    transform: translateX(24px);
}

.cookie-toggle input:disabled + .cookie-toggle-slider {
    opacity: 0.5;
    cursor: not-allowed;
}

.cookie-info-section {
    background: #054547;
    border-radius: 0.5rem;
    margin-top: 0.75rem;
    overflow: hidden;
}

.cookie-info-toggle {
    width: 100%;
    background: none;
    border: none;
    color: white;
    font-size: 0.9375rem;
    font-weight: 500;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.25rem;
    text-align: left;
}

.cookie-info-content {
    padding: 0 1.25rem 1rem 1.25rem;
    font-size: 0.875rem;
    color: #b3b3b3;
    line-height: 1.6;
}

.cookie-info-content p {
    margin: 0 0 0.5rem 0;
}

.cookie-modal-footer {
    padding: 1.75rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    display: flex;
    gap: 0.75rem;
}

.cookie-modal-btn {
    flex: 1;
    padding: 0.875rem 1.25rem;
    border-radius: 9999px;
    font-size: 0.9375rem;
    font-weight: 600;
    cursor: pointer;
    border: none;
    transition: all 0.2s;
    text-align: center;
}

.cookie-modal-btn-primary {
    background: white;
    color: #033234;
}

.cookie-modal-btn-primary:hover {
    background: #f0f0f0;
}

.cookie-modal-btn-secondary {
    background: white;
    color: #033234;
}

.cookie-modal-btn-secondary:hover {
    background: #f0f0f0;
}

.cookie-modal-btn-tertiary {
    background: transparent;
    color: white;
    border: 1px solid rgba(255, 255, 255, 0.3);
}

.cookie-modal-btn-tertiary:hover {
    background: rgba(255, 255, 255, 0.1);
    border-color: rgba(255, 255, 255, 0.5);
}

@media (max-width: 768px) {
    .cookie-consent-content {
        flex-direction: column;
        align-items: stretch;
    }
    
    .cookie-consent-actions {
        flex-direction: column;
    }
    
    .cookie-btn {
        width: 100%;
    }
    
    .cookie-modal-footer {
        flex-direction: column;
    }
}
</style>

<script>
// Google Consent Mode v2
window.dataLayer = window.dataLayer || [];
function gtag() { dataLayer.push(arguments); }

// Default consent state (denied)
gtag('consent', 'default', {
    'ad_storage': 'denied',
    'ad_user_data': 'denied',
    'ad_personalization': 'denied',
    'analytics_storage': 'denied',
    'functionality_storage': 'granted',
    'personalization_storage': 'denied',
    'security_storage': 'granted',
    'wait_for_update': 500
});

// Cookie Consent Logic
document.addEventListener('DOMContentLoaded', function() {
    const COOKIE_NAME = 'cookie_consent';
    const COOKIE_EXPIRY_DAYS = 365;
    
    // Get cookie
    function getCookie(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
    }
    
    // Set cookie
    function setCookie(name, value, days) {
        const date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        const expires = `expires=${date.toUTCString()}`;
        document.cookie = `${name}=${value};${expires};path=/;SameSite=Lax`;
    }
    
    // Update consent
    function updateConsent(analytics, marketing) {
        gtag('consent', 'update', {
            'ad_storage': marketing ? 'granted' : 'denied',
            'ad_user_data': marketing ? 'granted' : 'denied',
            'ad_personalization': marketing ? 'granted' : 'denied',
            'analytics_storage': analytics ? 'granted' : 'denied',
            'personalization_storage': analytics ? 'granted' : 'denied'
        });
    }
    
    // Save consent
    function saveConsent(analytics, marketing) {
        const consent = {
            analytics: analytics,
            marketing: marketing,
            timestamp: new Date().toISOString()
        };
        setCookie(COOKIE_NAME, JSON.stringify(consent), COOKIE_EXPIRY_DAYS);
        updateConsent(analytics, marketing);
    }
    
    // Check existing consent
    const existingConsent = getCookie(COOKIE_NAME);
    if (existingConsent) {
        try {
            const consent = JSON.parse(existingConsent);
            updateConsent(consent.analytics, consent.marketing);
        } catch (e) {
            console.error('Failed to parse consent cookie', e);
        }
    } else {
        // Show banner if no consent
        document.getElementById('cookie-consent-banner').style.display = 'block';
    }
    
    // Event listeners - Banner
    document.getElementById('cookie-accept-all').addEventListener('click', function() {
        saveConsent(true, true);
        document.getElementById('cookie-consent-banner').style.display = 'none';
    });
    
    document.getElementById('cookie-accept-necessary').addEventListener('click', function() {
        saveConsent(false, false);
        document.getElementById('cookie-consent-banner').style.display = 'none';
    });
    
    document.getElementById('cookie-settings-link').addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('cookie-consent-banner').style.display = 'none';
        document.getElementById('cookie-settings-modal').style.display = 'flex';
    });
    
    // Toggle functionality for cookie categories
    document.querySelectorAll('.cookie-category-toggle').forEach(function(toggle) {
        toggle.addEventListener('click', function() {
            const header = this.closest('.cookie-category-header');
            const toggleAttr = header.getAttribute('data-toggle');
            const content = document.getElementById(toggleAttr + '-content');
            
            if (content.style.display === 'none' || !content.style.display) {
                content.style.display = 'block';
                this.classList.add('active');
            } else {
                content.style.display = 'none';
                this.classList.remove('active');
            }
        });
    });
    
    // Toggle for more info
    document.getElementById('more-info-toggle').addEventListener('click', function() {
        const content = document.getElementById('more-info-content');
        if (content.style.display === 'none' || !content.style.display) {
            content.style.display = 'block';
            this.classList.add('active');
        } else {
            content.style.display = 'none';
            this.classList.remove('active');
        }
    });

    // Toggle for cookie policy
    document.getElementById('cookie-policy-toggle').addEventListener('click', function() {
        const content = document.getElementById('cookie-policy-content');
        if (content.style.display === 'none' || !content.style.display) {
            content.style.display = 'block';
            this.classList.add('active');
        } else {
            content.style.display = 'none';
            this.classList.remove('active');
        }
    });
    
    document.getElementById('cookie-modal-close').addEventListener('click', function() {
        document.getElementById('cookie-settings-modal').style.display = 'none';
        if (!existingConsent) {
            document.getElementById('cookie-consent-banner').style.display = 'block';
        }
    });
    
    document.getElementById('cookie-save-settings').addEventListener('click', function() {
        const analytics = document.getElementById('cookie-analytics').checked;
        const marketing = document.getElementById('cookie-marketing').checked;
        saveConsent(analytics, marketing);
        document.getElementById('cookie-settings-modal').style.display = 'none';
    });
    
    document.getElementById('cookie-accept-all-modal').addEventListener('click', function() {
        document.getElementById('cookie-analytics').checked = true;
        document.getElementById('cookie-marketing').checked = true;
        document.getElementById('cookie-functional').checked = true;
        saveConsent(true, true);
        document.getElementById('cookie-settings-modal').style.display = 'none';
    });
    
    document.getElementById('cookie-accept-necessary-modal').addEventListener('click', function() {
        document.getElementById('cookie-analytics').checked = false;
        document.getElementById('cookie-marketing').checked = false;
        document.getElementById('cookie-functional').checked = false;
        saveConsent(false, false);
        document.getElementById('cookie-settings-modal').style.display = 'none';
    });
    
    // Close modal on outside click
    document.getElementById('cookie-settings-modal').addEventListener('click', function(e) {
        if (e.target === this) {
            this.style.display = 'none';
            if (!existingConsent) {
                document.getElementById('cookie-consent-banner').style.display = 'block';
            }
        }
    });
});
</script>
