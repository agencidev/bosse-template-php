// Pricing Toggle Functionality
document.addEventListener('DOMContentLoaded', function() {
    const pricingCards = document.querySelectorAll('.pricing-card[data-monthly]');
    
    pricingCards.forEach(card => {
        const toggle = card.querySelector('.pricing-toggle-input');
        const monthlyLabel = card.querySelector('[data-period="monthly"]');
        const yearlyLabel = card.querySelector('[data-period="yearly"]');
        const priceElement = card.querySelector('.pricing-card__amount');
        const periodElement = card.querySelector('.pricing-card__period');
        
        const monthlyPrice = parseInt(card.dataset.monthly);
        const yearlyPrice = parseInt(card.dataset.yearly);
        
        if (!toggle) return;
        
        // Calculate yearly price as monthly cost (rounded up)
        const yearlyAsMonthly = Math.ceil(yearlyPrice / 12);
        
        // Update labels active state
        function updateLabels(isMonthly) {
            if (isMonthly) {
                yearlyLabel.classList.remove('pricing-card__toggle-label--active');
                monthlyLabel.classList.add('pricing-card__toggle-label--active');
            } else {
                monthlyLabel.classList.remove('pricing-card__toggle-label--active');
                yearlyLabel.classList.add('pricing-card__toggle-label--active');
            }
        }
        
        // Update pricing display
        function updatePricing(isMonthly) {
            if (isMonthly) {
                // Show monthly price
                priceElement.textContent = monthlyPrice.toLocaleString('sv-SE') + ' kr';
                periodElement.textContent = '/mån';
            } else {
                // Show yearly price as monthly cost
                priceElement.textContent = yearlyAsMonthly.toLocaleString('sv-SE') + ' kr';
                periodElement.textContent = '/mån';
            }
        }
        
        // Initialize with yearly (unchecked by default = yearly)
        updateLabels(false);
        updatePricing(false);
        
        // Toggle change event
        toggle.addEventListener('change', function() {
            const isMonthly = this.checked;
            updateLabels(isMonthly);
            updatePricing(isMonthly);
        });
        
        // Label click events
        monthlyLabel.addEventListener('click', function() {
            if (!toggle.disabled) {
                toggle.checked = true;
                updateLabels(true);
                updatePricing(true);
            }
        });
        
        yearlyLabel.addEventListener('click', function() {
            if (!toggle.disabled) {
                toggle.checked = false;
                updateLabels(false);
                updatePricing(false);
            }
        });
    });
});
