<?php
/**
 * Header Component
 * Global header med navigation
 */
?>
<a href="#main-content" class="skip-to-content">Hoppa till innehåll</a>
<header class="header">
    <div class="container">
        <div class="header__content">
            <div class="header__logo">
                <a href="/">
                    <img src="/assets/images/logo-dark.png" alt="<?php echo SITE_NAME; ?>" class="header__logo-img">
                </a>
            </div>

            <nav id="main-nav" class="header__nav">
                <a href="/inlagg" class="header__nav-link">Resurser</a>
                <a href="/kontakt" class="header__nav-link">Support</a>
            </nav>

            <a href="/boka-demo" class="header__cta">
                Kom igång gratis
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M5 12h14M12 5l7 7-7 7"/>
                </svg>
            </a>

            <button class="header__mobile-toggle" aria-label="Öppna meny" aria-expanded="false" aria-controls="main-nav">
                <svg class="header__menu-icon" width="28" height="20" viewBox="0 0 28 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect y="0" width="28" height="2.5" rx="1.25" fill="currentColor"/>
                    <rect x="6" y="8.5" width="22" height="2.5" rx="1.25" fill="currentColor"/>
                    <rect y="17" width="28" height="2.5" rx="1.25" fill="currentColor"/>
                </svg>
                <svg class="header__close-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <line x1="4" y1="4" x2="20" y2="20" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
                    <line x1="20" y1="4" x2="4" y2="20" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
                </svg>
            </button>
        </div>
    </div>
</header>

<style>
.skip-to-content {
    position: absolute;
    left: -9999px;
    top: auto;
    width: 1px;
    height: 1px;
    overflow: hidden;
    z-index: 10001;
    background: var(--color-primary, #379b83);
    color: white;
    padding: 0.75rem 1.5rem;
    text-decoration: none;
    font-weight: 600;
    border-radius: 0 0 0.5rem 0;
}

.skip-to-content:focus {
    position: fixed;
    left: 0;
    top: 0;
    width: auto;
    height: auto;
    overflow: visible;
}

.header {
    background-color: white;
    border-bottom: 1px solid var(--color-gray-200);
    padding: 1rem 0;
    position: sticky;
    top: 0;
    z-index: 100;
}

.header__content {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.header__logo a {
    display: flex;
    align-items: center;
    text-decoration: none;
}

.header__logo-img {
    height: 2rem;
    width: auto;
}

.header__nav {
    display: flex;
    gap: var(--spacing-8);
    align-items: center;
    position: absolute;
    left: 50%;
    transform: translateX(-50%);
}

.header__nav-link {
    color: var(--color-gray-600);
    text-decoration: none;
    font-size: 15px;
    font-weight: 400;
    transition: color 0.2s;
}

.header__nav-link:hover {
    color: var(--color-foreground);
    text-decoration: none;
}

.header__cta {
    display: inline-flex;
    align-items: center;
    gap: var(--spacing-2);
    padding: 10px 20px;
    background: var(--color-primary);
    color: white;
    text-decoration: none;
    border-radius: 50px;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.2s;
    margin-left: auto;
}

.header__cta:hover {
    background: var(--color-primary-dark);
    color: white;
    transform: translateX(2px);
    text-decoration: none;
}

.header__mobile-toggle {
    display: none;
    align-items: center;
    justify-content: center;
    background: none;
    border: none;
    cursor: pointer;
    padding: 0.5rem;
    color: var(--color-gray-700);
    z-index: 102;
    position: relative;
}

.header__close-icon {
    display: none;
}

.header__mobile-toggle[aria-expanded="true"] .header__menu-icon {
    display: none;
}

.header__mobile-toggle[aria-expanded="true"] .header__close-icon {
    display: block;
}

@media (max-width: 768px) {
    .header__nav {
        position: fixed;
        top: 0;
        right: 0;
        width: 100%;
        height: 100dvh;
        background-color: white;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        gap: 0;
        z-index: 101;
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.3s ease, visibility 0.3s ease;
    }

    .header__nav.is-open {
        display: flex;
        opacity: 1;
        visibility: visible;
    }

    .header__nav-link {
        font-size: 1.5rem;
        font-weight: 600;
        padding: 1rem 0;
        color: var(--color-gray-800, #1f2937);
        text-decoration: none;
        opacity: 0;
        transform: translateY(10px);
        transition: opacity 0.3s ease, transform 0.3s ease, color 0.2s ease;
    }

    .header__nav.is-open .header__nav-link {
        opacity: 1;
        transform: translateY(0);
    }

    .header__nav.is-open .header__nav-link:nth-child(1) { transition-delay: 0.1s; }
    .header__nav.is-open .header__nav-link:nth-child(2) { transition-delay: 0.15s; }
    .header__nav.is-open .header__nav-link:nth-child(3) { transition-delay: 0.2s; }
    .header__nav.is-open .header__nav-link:nth-child(4) { transition-delay: 0.25s; }
    .header__nav.is-open .header__nav-link:nth-child(5) { transition-delay: 0.3s; }

    .header__nav-link:hover {
        color: var(--color-primary);
    }

    .header__cta {
        display: none;
    }

    .header__mobile-toggle {
        display: flex;
    }

    .header__mobile-toggle[aria-expanded="true"] {
        color: var(--color-gray-800, #1f2937);
    }
}
</style>

<script>
(function() {
    var toggle = document.querySelector('.header__mobile-toggle');
    var nav = document.getElementById('main-nav');
    if (!toggle || !nav) return;

    function closeMenu() {
        toggle.setAttribute('aria-expanded', 'false');
        toggle.setAttribute('aria-label', 'Öppna meny');
        nav.classList.remove('is-open');
        document.body.style.overflow = '';
    }

    function openMenu() {
        toggle.setAttribute('aria-expanded', 'true');
        toggle.setAttribute('aria-label', 'Stäng meny');
        nav.classList.add('is-open');
        document.body.style.overflow = 'hidden';
    }

    toggle.addEventListener('click', function() {
        var expanded = toggle.getAttribute('aria-expanded') === 'true';
        expanded ? closeMenu() : openMenu();
    });

    nav.querySelectorAll('.header__nav-link').forEach(function(link) {
        link.addEventListener('click', closeMenu);
    });
})();
</script>
