/**
 * Hero Parallax Scroll Effect
 * Creates subtle upward floating animation on scroll
 */

document.addEventListener('DOMContentLoaded', function() {
    const heroSection = document.querySelector('.hero-section');
    const heroTitle = document.querySelector('.hero-title');
    const heroDescription = document.querySelector('.hero-description');
    const heroCta = document.querySelector('.hero-cta');
    const heroBlob = document.querySelector('.hero-visual__blob');
    const heroIcon = document.querySelector('.hero-visual__icon');
    
    if (!heroSection) return;
    
    function handleScroll() {
        const scrollY = window.scrollY;
        const heroHeight = heroSection.offsetHeight;
        
        // Only apply effect while hero is visible
        if (scrollY > heroHeight) return;
        
        // Calculate scroll progress (0 to 1)
        const progress = Math.min(scrollY / heroHeight, 1);
        
        // Parallax speeds:
        // Images move fastest (170px)
        // Text and button move at same slower speed
        const imageSpeed = 170; // Images (fastest)
        const contentSpeed = 60; // Text and button (slower)
        
        if (heroBlob) {
            const blobMove = progress * imageSpeed;
            heroBlob.style.transform = `translateY(-${blobMove}px)`;
        }
        
        if (heroIcon) {
            const iconMove = progress * imageSpeed;
            heroIcon.style.transform = `translateY(-${iconMove}px)`;
        }
        
        if (heroTitle) {
            const titleMove = progress * contentSpeed;
            heroTitle.style.transform = `translateY(-${titleMove}px)`;
        }
        
        if (heroDescription) {
            const descMove = progress * contentSpeed;
            heroDescription.style.transform = `translateY(-${descMove}px)`;
        }
        
        if (heroCta) {
            const ctaMove = progress * contentSpeed;
            heroCta.style.transform = `translateY(-${ctaMove}px)`;
        }
    }
    
    // Listen to scroll with passive for better performance
    window.addEventListener('scroll', handleScroll, { passive: true });
    
    // Initial call
    handleScroll();
});
