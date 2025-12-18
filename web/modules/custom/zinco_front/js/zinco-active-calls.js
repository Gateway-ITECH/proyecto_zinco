/**
 * @file
 * JavaScript for the Active Calls block.
 */

(function (Drupal, once) {
    'use strict';

    Drupal.behaviors.zincoActiveCalls = {
        attach: function (context, settings) {
            // Initialize all active call cards.
            once('zinco-active-calls', '.call-card', context).forEach(function (card) {
                // Add entrance animation on scroll.
                const observer = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('animate-in');
                            observer.unobserve(entry.target);
                        }
                    });
                }, {
                    threshold: 0.1
                });

                observer.observe(card);

                // Add pulse animation to urgent calls.
                if (card.classList.contains('call-urgent')) {
                    const urgencyLabel = card.querySelector('.urgency-urgent');
                    if (urgencyLabel) {
                        setInterval(function () {
                            urgencyLabel.style.transform = 'scale(1.05)';
                            setTimeout(function () {
                                urgencyLabel.style.transform = 'scale(1)';
                            }, 200);
                        }, 2000);
                    }
                }
            });
        }
    };

})(Drupal, once);
