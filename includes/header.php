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
                <a href="/inlagg" class="header__nav-link">Resurser</a>
                <a href="/kontakt" class="header__nav-link">Support</a>
            </nav>
            
            <a href="/boka-demo" class="header__cta">
                Kom igång gratis
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M5 12h14M12 5l7 7-7 7"/>
                </svg>
            </a>
            
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
    }
    
    .header__cta {
        display: none;
    }
    
    .header__mobile-toggle {
        display: flex;
    }
}
</style>
