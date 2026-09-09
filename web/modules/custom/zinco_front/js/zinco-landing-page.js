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

            // Initialize Bootstrap tooltips.
            if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
                const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                tooltipTriggerList.map(function (tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });
            }

            // Data Rain Effect Implementation
            const canvas = document.getElementById('data-rain-canvas');
            if (canvas) {
                const ctx = canvas.getContext('2d');
                let width, height, columns, drops;

                const initCanvas = () => {
                    width = canvas.width = window.innerWidth;
                    height = canvas.height = window.innerHeight + 100;
                    columns = Math.floor(width / 25);
                    drops = [];
                    for (let i = 0; i < columns; i++) {
                        drops[i] = Math.random() * -100; // Random start positions
                    }
                };

                const draw = () => {
                    // Create a fading effect (dark color)
                    ctx.fillStyle = 'rgba(0, 0, 0, 0.15)';
                    ctx.fillRect(0, 0, width, height);

                    // Set text style (white color)
                    ctx.fillStyle = '#ffffff';
                    ctx.font = '14px monospace';

                    for (let i = 0; i < drops.length; i++) {
                        // Random binary characters
                        const text = Math.floor(Math.random() * 2);
                        ctx.fillText(text.toString(), i * 25, drops[i] * 25);

                        // Reset drop to top if it reaches the bottom
                        if (drops[i] * 25 > height && Math.random() > 0.975) {
                            drops[i] = 0;
                        }
                        // Slower increment
                        drops[i] += 0.5;
                    }
                    requestAnimationFrame(draw);
                };

                initCanvas();
                draw();

                window.addEventListener('resize', initCanvas);
            }
        }
    };

})(Drupal, once);
