/**
 * @file
 * JavaScript for the Zinco Landing Page with Typed.js effect.
 */

(function (Drupal, once) {
    'use strict';

    Drupal.behaviors.zincoLandingPageAnimations = {
        attach: function (context, settings) {
            // Scroll-triggered animations for elements with data-animate attribute.
            const animateOnScroll = function () {
                const elements = document.querySelectorAll('[data-animate]');

                const observer = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) {
                            const element = entry.target;
                            const animation = element.getAttribute('data-animate');
                            const delay = element.getAttribute('data-delay') || 0;

                            setTimeout(function () {
                                element.classList.add('animate__animated', 'animate__' + animation);
                            }, delay);

                            // Trigger CountUp if it's a counter element.
                            const counterElement = element.querySelector('.zinco-counter') || (element.classList.contains('zinco-counter') ? element : null);
                            if (counterElement && typeof countUp !== 'undefined') {
                                const endValue = parseInt(counterElement.getAttribute('data-count'), 10);
                                if (!isNaN(endValue)) {
                                    const countUpAnim = new countUp.CountUp(counterElement, endValue, {
                                        duration: 2,
                                        useEasing: true,
                                        useGrouping: true,
                                        separator: '.',
                                        decimal: ','
                                    });
                                    if (!countUpAnim.error) {
                                        countUpAnim.start();
                                    } else {
                                        console.error(countUpAnim.error);
                                    }
                                }
                            }

                            observer.unobserve(element);
                        }
                    });
                }, {
                    threshold: 0.2,
                    rootMargin: '0px 0px -50px 0px'
                });

                elements.forEach(function (element) {
                    observer.observe(element);
                });
            };

            // Initialize scroll animations.
            animateOnScroll();
        }
    };

})(Drupal, once);
