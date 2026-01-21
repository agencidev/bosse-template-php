<?php
/**
 * Top Banner Component
 * Visuell banner högst upp på sidan
 */
?>
<div class="top-banner">
    <div class="container">
        <div class="top-banner__content">
            <span class="top-banner__text">
                <?php echo get_content('banner.text', '🎉 Välkommen till vår nya hemsida!'); ?>
            </span>
        </div>
    </div>
</div>

<style>
.top-banner {
    background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 50%, #6366f1 100%);
    color: white;
    padding: 0.75rem 0;
    position: relative;
    z-index: 101;
}

.top-banner__content {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
}

.top-banner__text {
    font-size: 0.875rem;
    font-weight: 500;
    text-align: center;
}

@media (max-width: 768px) {
    .top-banner__text {
        font-size: 0.8125rem;
    }
}
</style>
