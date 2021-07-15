/**
 * @file
 * Provides condition values for all browser conditions.
 */

 (function (Drupal) {

  Drupal.smartContent = Drupal.smartContent || {};
  Drupal.smartContent.plugin = Drupal.smartContent.plugin || {};
  Drupal.smartContent.plugin.ConditionType = Drupal.smartContent.plugin.ConditionType || {};

  Drupal.smartContent.plugin.ConditionType['type:array_select'] = function (condition, value) {
    let context = value || '';

    if (Array.isArray(context) && context.length > 0) {
      return in_array(condition.settings['value'] , value);
    }

    return false;
  };

})(Drupal);