/**
 * @file
 * Provides condition values for all browser conditions.
 */

 (function (Drupal) {

  Drupal.smartContent = Drupal.smartContent || {};
  Drupal.smartContent.plugin = Drupal.smartContent.plugin || {};
  Drupal.smartContent.plugin.Field = Drupal.smartContent.plugin.Field || {};

  Drupal.smartContent.plugin.Field['smart_cdn'] = function (condition) {
    console.log(condition);
    return condition.settings.smart_cdn.value ? condition.settings.smart_cdn.value : null;
  }

})(Drupal);
