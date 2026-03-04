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
                    <?php
                    $logoPath = __DIR__ . '/../assets/images/logo-dark.png';
                    $logoW = 160; $logoH = 40;
                    if (file_exists($logoPath)) {
                        $dims = @getimagesize($logoPath);
                        if ($dims) { $logoW = $dims[0]; $logoH = $dims[1]; }
                    }
                    ?>
                    <img src="/assets/images/logo-dark.png" alt="<?php echo SITE_NAME; ?>" class="header__logo-img" width="<?php echo $logoW; ?>" height="<?php echo $logoH; ?>">
                </a>
            </div>
            
            <nav id="main-nav" class="header__nav" aria-label="Huvudnavigation">
                <a href="/" class="header__nav-link">Hem</a>
                <a href="/om-oss" class="header__nav-link">Om oss</a>
                <a href="/tjanster" class="header__nav-link">Tjänster</a>
                <a href="/kontakt" class="header__nav-link">Kontakt</a>
            </nav>
            
            <button class="header__mobile-toggle" aria-label="Öppna meny" aria-expanded="false" aria-controls="main-nav">
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
    height: 2.5rem;
    width: auto;
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
        flex-direction: column;
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background-color: white;
        padding: 1rem var(--container-padding, 1rem);
        box-shadow: var(--shadow-md, 0 4px 6px rgba(0,0,0,0.1));
        gap: 0;
        z-index: 99;
    }

    .header__nav.is-open {
        display: flex;
    }

    .header__nav-link {
        padding: 0.75rem 0;
        border-bottom: 1px solid var(--color-gray-200, #e5e7eb);
    }

    .header__nav-link:last-child {
        border-bottom: none;
    }

    .header__mobile-toggle {
        display: flex;
    }

    .header__mobile-toggle[aria-expanded="true"] span:nth-child(1) {
        transform: rotate(45deg) translate(4px, 4px);
    }
    .header__mobile-toggle[aria-expanded="true"] span:nth-child(2) {
        opacity: 0;
    }
    .header__mobile-toggle[aria-expanded="true"] span:nth-child(3) {
        transform: rotate(-45deg) translate(4px, -4px);
    }

    .header {
        position: relative;
    }
}
</style>

<script>
(function() {
    var toggle = document.querySelector('.header__mobile-toggle');
    var nav = document.getElementById('main-nav');
    if (!toggle || !nav) return;

    toggle.addEventListener('click', function() {
        var expanded = toggle.getAttribute('aria-expanded') === 'true';
        toggle.setAttribute('aria-expanded', String(!expanded));
        toggle.setAttribute('aria-label', expanded ? 'Öppna meny' : 'Stäng meny');
        nav.classList.toggle('is-open');
    });
})();
</script>
