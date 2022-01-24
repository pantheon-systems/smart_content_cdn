/**
 * @file
 * Provides condition values for all browser conditions.
 */

 (function (Drupal) {

  Drupal.smartContent = Drupal.smartContent || {};
  Drupal.smartContent.plugin = Drupal.smartContent.plugin || {};
  Drupal.smartContent.plugin.ConditionType = Drupal.smartContent.plugin.ConditionType || {};

  Drupal.smartContent.plugin.ConditionType['type:array_select'] = function (condition, value) {
    // Make sure that value to check is an array.
    if (Array.isArray(value) && value.length > 0) {
      for(var i = 0; i < value.length; i++) {
        // Check array value with value set in Segment condition.
        if (value[i] === condition.settings['value']) {
          return true;
        }
      }
    }

    return false;
  };

})(Drupal);