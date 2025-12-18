/**
 * @file
 * JavaScript for the Upcoming Events block.
 */

(function (Drupal, once) {
    'use strict';

    Drupal.behaviors.zincoUpcomingEvents = {
        attach: function (context, settings) {
            // Initialize all upcoming events cards.
            once('zinco-upcoming-events', '.event-card', context).forEach(function (card) {
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

                // Add ripple effect on click (optional).
                card.addEventListener('click', function (e) {
                    const ripple = document.createElement('span');
                    ripple.classList.add('ripple');

                    const rect = card.getBoundingClientRect();
                    const size = Math.max(rect.width, rect.height);
                    const x = e.clientX - rect.left - size / 2;
                    const y = e.clientY - rect.top - size / 2;

                    ripple.style.width = ripple.style.height = size + 'px';
                    ripple.style.left = x + 'px';
                    ripple.style.top = y + 'px';

                    card.appendChild(ripple);

                    setTimeout(function () {
                        ripple.remove();
                    }, 600);
                });
            });
        }
    };

})(Drupal, once);
