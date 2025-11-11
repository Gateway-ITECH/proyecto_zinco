/**
 * @file
 * zinco-reto-detail.js
 *
 * Provides JavaScript for the Zinco Reto Detail page.
 */

(function (Drupal, once) {

  'use strict';

  Drupal.behaviors.zincoRetoDetail = {
    attach: function (context, settings) {
      once('zincoRetoDetail', '.reto-detail-page', context).forEach(function (element) {
        // Add any specific JavaScript functionality for the reto detail page here.
        console.log('Zinco Reto Detail page loaded.');
      });
    }
  };

})(Drupal, once);