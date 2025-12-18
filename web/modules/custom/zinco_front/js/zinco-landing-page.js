/**
 * @file
 * JavaScript for the Zinco Landing Page with Typed.js effect.
 */

(function (Drupal, once) {
    'use strict';

    Drupal.behaviors.zincoLandingPageTyped = {
        attach: function (context, settings) {
            // Initialize Typed.js for hero section.
            once('zinco-landing-typed', '.hero-section', context).forEach(function (heroSection) {
                const heroData = document.getElementById('hero-data');

                if (!heroData) {
                    return;
                }

                const titleText = heroData.getAttribute('data-title');
                const subtitleText = heroData.getAttribute('data-subtitle');
                const subtitleElement = document.querySelector('.hero-subtitle');
                const buttonElement = document.getElementById('hero-button');

                // First, type the title.
                const typedTitle = new Typed('#typed-title', {
                    strings: [titleText],
                    typeSpeed: 50,
                    showCursor: true,
                    cursorChar: '|',
                    onComplete: function () {
                        // Hide cursor after title is complete.
                        setTimeout(function () {
                            document.querySelector('#typed-title').parentElement.querySelector('.typed-cursor').style.display = 'none';

                            // Show subtitle with fade in.
                            subtitleElement.style.transition = 'opacity 0.5s';
                            subtitleElement.style.opacity = '1';

                            // Start typing subtitle after a brief delay.
                            setTimeout(function () {
                                const typedSubtitle = new Typed('#typed-subtitle', {
                                    strings: [subtitleText],
                                    typeSpeed: 20,
                                    showCursor: true,
                                    cursorChar: '|',
                                    onComplete: function () {
                                        // Hide cursor after subtitle is complete.
                                        setTimeout(function () {
                                            document.querySelector('#typed-subtitle').parentElement.querySelector('.typed-cursor').style.display = 'none';

                                            // Show button with fade in.
                                            buttonElement.style.transition = 'opacity 0.5s';
                                            buttonElement.style.opacity = '1';
                                        }, 500);
                                    }
                                });
                            }, 300);
                        }, 500);
                    }
                })
                    ;
            });

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
