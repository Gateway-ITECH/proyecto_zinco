/**
 * @file
 * Javascript for the Zinco Actor Profile page.
 */

(function (Drupal, once) {

  'use strict';

  Drupal.behaviors.zincoActorProfile = {
    attach: function (context, settings) {
      once('zincoActorProfile', '.actor-profile-wrapper', context).forEach(function (element) {
        // Add any specific JS for the actor profile page here.
        // For example, if you have accordions or tabs that need JS initialization.
        // Bootstrap's JS for accordions/tabs should handle most of it,
        // but if custom logic is needed, it goes here.
        console.log('Zinco Actor Profile JS attached.');
      });
    }
  };

})(Drupal, once);