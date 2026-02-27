/**
 * @file
 * Global utilities.
 *
 */
(function (Drupal, once) {

  'use strict';

  Drupal.behaviors.bootstrap_barrio_subtheme = {
    attach: function (context, settings) {

    }
  };

  /**
   * Navbar scroll behavior: adds/removes 'scrolled' class on the navbar
   * depending on the vertical scroll position.
   */
  Drupal.behaviors.navbarScroll = {
    attach: function (context, settings) {
      once('navbar-scroll', 'body', context).forEach(function () {
        var navbar = document.querySelector('nav.navbar');
        if (!navbar) return;

        function onScroll() {
          if (window.scrollY > 50) {
            navbar.classList.add('scrolled');
          } else {
            navbar.classList.remove('scrolled');
          }
        }

        window.addEventListener('scroll', onScroll, { passive: true });
        // Run once on load in case page is already scrolled.
        onScroll();
      });
    }
  };

})(Drupal, once);
