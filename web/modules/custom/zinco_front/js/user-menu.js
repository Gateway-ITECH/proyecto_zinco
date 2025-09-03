/**
 * @file
 * User menu behaviors using vanilla JavaScript.
 */

(function (Drupal) {

  'use strict';

  Drupal.behaviors.zincoFrontUserMenu = {
    attach: function (context, settings) {
      // Ensure dropdowns are initialized using vanilla JS.
      // This targets elements with the 'dropdown-toggle' class within the context.
      context.querySelectorAll('.dropdown-toggle').forEach(function (toggle) {
        // Use Drupal.once to ensure the event listener is attached only once.
        if (!toggle.dataset.userMenuDropdownProcessed) {
          toggle.dataset.userMenuDropdownProcessed = true;

          toggle.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation(); // Prevent closing other dropdowns immediately.

            const dropdownMenu = this.nextElementSibling;
            if (dropdownMenu && dropdownMenu.classList.contains('dropdown-menu')) {
              dropdownMenu.classList.toggle('show');
            }

            // Close other dropdowns when one is opened.
            document.querySelectorAll('.dropdown-menu.show').forEach(function (openMenu) {
              if (openMenu !== dropdownMenu) {
                openMenu.classList.remove('show');
              }
            });
          });

          // Close dropdowns when clicking outside.
          document.addEventListener('click', function (event) {
            const isClickInside = toggle.contains(event.target) || (toggle.nextElementSibling && toggle.nextElementSibling.contains(event.target));
            if (!isClickInside && toggle.nextElementSibling && toggle.nextElementSibling.classList.contains('show')) {
              toggle.nextElementSibling.classList.remove('show');
            }
          });
        }
      });
    }
  };

})(Drupal);