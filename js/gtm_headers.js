/**
 * @file
 * Set content personalization headers for Google Tag Manager.
 */

 (function ($, Drupal,) {
  Drupal.behaviors.gtmBehavior = {
    attach: function (context, settings){
      if ('gtmHeaders' in settings && 'pObj' in settings.gtmHeaders) {
        // Get geo value.
        let geo = 'Audience' in settings.gtmHeaders.pObj && 'geo' in settings.gtmHeaders.pObj.Audience ?
          settings.gtmHeaders.pObj.Audience.geo : null;

        // Get interest value labels.
        let interest = 'InterestLabels' in settings.gtmHeaders.pObj ? settings.gtmHeaders.pObj.InterestLabels : null;

        // Get role value.
        let role = 'Role' in settings.gtmHeaders.pObj ? settings.gtmHeaders.pObj.Role : 'none';

        // Push header values in dataLayer object.
        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push({
          'event': 'pzn',
          'audience': {
            'geo': geo,
          },
          'interest': interest,
          'role': role,
        });
      }
    }
  };

})(jQuery, Drupal);
