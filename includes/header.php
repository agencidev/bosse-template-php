<?php
/**
 * Header Component
 * Global header med navigation
 */
?>
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
                
                <?php if (is_logged_in()): ?>
                    <a href="/admin" class="header__nav-link header__nav-link--admin">
                        CMS Admin
                    </a>
                <?php endif; ?>
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

.header__nav-link--admin {
    padding: 0.5rem 1rem;
    background-color: var(--color-primary);
    color: white;
    border-radius: var(--radius-md);
}

.header__nav-link--admin:hover {
    background-color: var(--color-primary-dark);
    color: white;
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
    
    .header__mobile-toggle {
        display: flex;
    }
}
</style>
