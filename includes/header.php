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
            
            <nav class="header__nav">
                <a href="/" class="header__nav-link">Hem</a>
                <a href="/om-oss" class="header__nav-link">Om oss</a>
                <a href="/tjanster" class="header__nav-link">Tjänster</a>
                <a href="/kontakt" class="header__nav-link">Kontakt</a>
            </nav>
            
            <button class="header__mobile-toggle" aria-label="Toggle menu">
                <span></span>
                <span></span>
                <span></span>
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
    z-index: 1000;
    transition: padding 0.3s ease, box-shadow 0.3s ease;
}

.header--scrolled {
    padding: 0.5rem 0;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.header--scrolled .header__logo-img {
    height: 2rem;
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
    height: 2.5rem;
    width: auto;
    transition: height 0.3s ease;
}

.header__nav {
    display: flex;
    gap: 2rem;
    align-items: center;
}

.header__nav-link {
    color: var(--color-gray-700);
    text-decoration: none;
    font-weight: var(--font-medium);
    transition: color var(--transition-fast);
}

.header__nav-link:hover {
    color: var(--color-primary);
    text-decoration: none;
}

.header__mobile-toggle {
    display: none;
    flex-direction: column;
    gap: 4px;
    background: none;
    border: none;
    cursor: pointer;
    padding: 0.5rem;
}

.header__mobile-toggle span {
    width: 24px;
    height: 2px;
    background-color: var(--color-gray-700);
    transition: all var(--transition-fast);
}

@media (max-width: 768px) {
    .header__nav {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: white;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 2rem;
        z-index: 999;
    }

    .header__nav.active {
        display: flex;
    }

    .header__nav .header__nav-link {
        font-size: 1.25rem;
    }

    .header__mobile-toggle {
        display: flex;
        z-index: 1001;
        position: relative;
    }

    .header__mobile-toggle.active span:nth-child(1) {
        transform: rotate(45deg) translate(4px, 4px);
    }

    .header__mobile-toggle.active span:nth-child(2) {
        opacity: 0;
    }

    .header__mobile-toggle.active span:nth-child(3) {
        transform: rotate(-45deg) translate(4px, -4px);
    }
}
</style>

<script <?php echo csp_nonce_attr(); ?>>
(function() {
    var toggle = document.querySelector('.header__mobile-toggle');
    var nav = document.querySelector('.header__nav');
    if (!toggle || !nav) return;

    toggle.addEventListener('click', function() {
        toggle.classList.toggle('active');
        nav.classList.toggle('active');
    });

    nav.querySelectorAll('.header__nav-link').forEach(function(link) {
        link.addEventListener('click', function() {
            toggle.classList.remove('active');
            nav.classList.remove('active');
        });
    });

    // Scroll: compact header
    var header = document.querySelector('.header');
    if (header) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 50) {
                header.classList.add('header--scrolled');
            } else {
                header.classList.remove('header--scrolled');
            }
        }, { passive: true });
    }
})();
</script>
