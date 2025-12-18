/**
 * @file
 * JavaScript for the News Carousel block.
 */

(function (Drupal, once) {
    'use strict';

    Drupal.behaviors.zincoNewsCarousel = {
        attach: function (context, settings) {
            // Initialize all news carousels on the page.
            once('zinco-news-carousel', '.zinco-news-carousel', context).forEach(function (carousel) {
                // Get the Bootstrap carousel instance.
                const bsCarousel = new bootstrap.Carousel(carousel, {
                    interval: 5000, // Auto-advance every 5 seconds
                    wrap: true,     // Loop continuously
                    pause: 'hover'  // Pause on hover
                });

                // Add keyboard navigation.
                carousel.addEventListener('keydown', function (e) {
                    if (e.key === 'ArrowLeft') {
                        bsCarousel.prev();
                    } else if (e.key === 'ArrowRight') {
                        bsCarousel.next();
                    }
                });

                // Make carousel focusable for keyboard navigation.
                carousel.setAttribute('tabindex', '0');

                // Add smooth scroll behavior for better UX.
                carousel.addEventListener('slide.bs.carousel', function (e) {
                    // Optional: Add custom animations or tracking here.
                });
            });
        }
    };

})(Drupal, once);
