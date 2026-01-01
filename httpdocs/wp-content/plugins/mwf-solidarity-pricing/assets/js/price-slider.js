/**
 * MWF Solidarity Price Slider
 * Interactive price slider for WooCommerce variable products
 */

(function($) {
    'use strict';
    
    const MWF_SolidaritySlider = {
        
        /**
         * Initialize slider on product pages
         */
        init: function() {
            // Only run on variable product pages with our slider
            if (!$('.mwf-solidarity-slider').length) {
                console.log('MWF Solidarity Slider: No slider found');
                return;
            }
            
            console.log('MWF Solidarity Slider: Setting up slider');
            this.setupSlider();
        },
        
        /**
         * Setup the solidarity price slider
         */
        setupSlider: function() {
            const self = this;
            
            const $slider = $('#mwf-solidarity-slider');
            const $priceDisplay = $('#mwf-custom-price-display');
            const $priceLabel = $('.mwf-solidarity-price-label');
            const $priceImpact = $('.mwf-solidarity-price-impact');
            const $hiddenInput = $('#mwf-custom-price');
            
            if ($slider.length === 0) return;
            
            // Handle slider changes
            $slider.on('input change', function() {
                const price = parseFloat($(this).val());
                self.updatePriceDisplay(price, $priceDisplay, $priceLabel, $priceImpact, $hiddenInput, $slider);
            });
            
            // Initialize with current value
            const initialPrice = parseFloat($slider.val());
            self.updatePriceDisplay(initialPrice, $priceDisplay, $priceLabel, $priceImpact, $hiddenInput, $slider);
        },
        
        /**
         * Update price display and hidden input
         */
        updatePriceDisplay: function(price, $priceDisplay, $priceLabel, $priceImpact, $hiddenInput, $slider) {
            // Update display
            $priceDisplay.text('£' + price.toFixed(2));
            
            // Update hidden input for form submission
            $hiddenInput.val(price.toFixed(2));
            
            // Get zone thresholds
            const standard = parseFloat($slider.data('standard'));
            const breakEven = parseFloat($slider.data('break-even'));
            
            // Determine zone and update styling
            let zone, icon, label, impact;
            
            if (price < breakEven) {
                // Solidarity zone
                zone = 'solidarity';
                icon = '💚';
                label = 'Solidarity Price';
                impact = "We subsidize you (that's okay if you need help)";
            } else if (price < standard) {
                // Break-even zone
                zone = 'break-even';
                icon = '⚖️';
                label = 'Break-even Price';
                impact = 'Farm covers basic costs';
            } else if (price === standard) {
                // Standard zone
                zone = 'standard';
                icon = '🌱';
                label = 'Standard Price';
                impact = 'Minimum wage for farmers';
            } else {
                // Supporter zone
                zone = 'supporter';
                icon = '🌳';
                label = 'Supporter Price';
                const extra = (price - standard).toFixed(2);
                impact = `You're paying £${extra} extra - helping subsidize others! <strong style="color: #2f855a;">Thank you!</strong>`;
            }
            
            // Update classes
            $priceDisplay.removeClass('solidarity break-even standard supporter').addClass(zone);
            $priceImpact.removeClass('solidarity break-even standard supporter').addClass(zone);
            
            // Update labels
            $priceLabel.html(`<span class="zone-icon">${icon}</span> <span class="zone-text">${label}</span>`);
            $priceImpact.html(`<span class="mwf-price-impact-icon">${icon}</span> <span class="mwf-price-impact-text">${impact}</span>`);
        }
    };
    
    // Initialize when DOM ready
    $(document).ready(function() {
        console.log('MWF Solidarity Slider initializing...');
        MWF_SolidaritySlider.init();
        
        // Re-initialize when WooCommerce variation changes
        $('form.variations_form').on('found_variation', function(event, variation) {
            console.log('MWF Solidarity Slider: Variation changed, reinitializing...');
            setTimeout(function() {
                MWF_SolidaritySlider.init();
            }, 100);
        });
    });
    
})(jQuery);
