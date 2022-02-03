<?php

namespace Drupal\smart_content_cdn;

use Drupal\smart_content\Decision\DecisionBase;

/**
 * DecisionEvaluator determines which decision condition was satisfied.
 */
class DecisionEvaluator {

  /**
   * Array of conditions found after evalutation.
   *
   * @var array
   */
  protected $foundConditions = [];

  /**
   * With given decision, return the segment that was satisfied.
   *
   * @param \Drupal\smart_content\Decision\DecisionBase $decision
   *   Decision object.
   *
   * @return string
   *   Segment id for the decided segment.
   */
  public function evaluate(DecisionBase $decision) : string {
    if (empty($decision) || $decision->getSegmentSetStorage()->getPluginId() === 'broken') {
      return NULL;
    }

    // Get smart content attached settings.
    $attached_settings = $decision->getAttachedSettings();

    if (empty($attached_settings['segments'])) {
      return NULL;
    }

    $default_segment = NULL;
    // Check for decision settings.
    if (!empty($attached_settings['decisions'])) {
      foreach ($attached_settings['decisions'] as $decision) {
        if (!empty($decision['default'])) {
          // Get default segment.
          $default_segment = $decision['default'];
          break;
        }
      }
    }

    // Check Decision segments.
    $segment_id = NULL;
    foreach ($attached_settings['segments'] as $s_id => $segment) {
      // The root condition in the segment heirarchy.
      $root_condition = $segment['conditions'] ?? NULL;
      if (empty($root_condition)) {
        continue;
      }

      // Check if root condition evaluates as true.
      if ($this->evaluateConditions($root_condition)) {
        $segment_id = $s_id;
      }
    }

    // If segment id doesn't exist, use default segment.
    if (empty($segment_id) && !empty($default_segment)) {
      $segment_id = $default_segment;
    }

    return $segment_id;
  }

  /**
   * Recursive function to evaluate a given condition.
   */
  private function evaluateConditions(array $conditions, $group_op = 'AND') {
    $condition_evaluations = [];

    // Loop through conditions.
    foreach ($conditions as $condition_key => $condition) {
      $condition_evaluation = FALSE;

      // Whether or not to negate the condition.
      $negate = $condition['field']['negate'] ?? FALSE;
      // The operation for the condition.
      $op = $condition['settings']['op'] ?? 'AND';

      // Remove any numbers from condition key to get the condition id.
      $condition_id = preg_replace('/(.*?)(_\d*)?$/mi', '$1', $condition_key);

      // Store conditions.
      $this->foundConditions[] = $condition_id;

      switch ($condition_id) {
        // Group condition, recurse through sub conditions.
        case 'group':
          $sub_conditions = $condition['conditions'] ?? NULL;
          $condition_evaluation = !empty($sub_conditions) ? $this->evaluateConditions($sub_conditions, $op) : FALSE;
          break;

        // Is True condition, just set to true.
        case 'is_true':
          $condition_evaluation = TRUE;
          break;

        // Geo condition.
        case 'smart_cdn:geo':
          // Value from segment settings.
          $value = $condition['settings']['value'] ?? NULL;
          // Value from the headings.
          $header_value = $condition['settings']['smart_cdn']['value'] ?? NULL;

          if (!empty($value) && !empty($header_value) || $op === 'equal') {
            switch ($op) {
              // If the values equal each other.
              case 'equals':
                $condition_evaluation = $value === $header_value;
                break;

              // If the header value contains the value in settings.
              case 'contains':
                $condition_evaluation = strpos($header_value, $value) !== FALSE;
                break;

              // If the header value starts with the value in settings.
              case 'starts_with':
                $condition_evaluation = strpos($header_value, $value) === 0;
                break;

              // If the header value is empty.
              case 'empty':
                $condition_evaluation = empty($header_value);
                break;
            }
          }
          break;

        // Interest condition.
        case 'smart_cdn:interest':
          // Value from segment settings.
          $value = $condition['settings']['value'] ?? NULL;
          // Array from the headings.
          $header_array = $condition['settings']['smart_cdn']['value'] ?? NULL;

          if (!empty($value) && !empty($header_array)) {
            // Check if settings value is in header array.
            $condition_evaluation = in_array($value, $header_array);
          }
          break;

        default:
          // Invoke hook to evaluate condition using smart_cdn values.
          $hook_evaluations = \Drupal::moduleHandler()->invokeAll('ssr_evaluate_condition', [
            $condition_id,
            $condition['settings'],
          ]);
          $hook_evaluations = array_filter($hook_evaluations);
          $condition_evaluation = !empty($hook_evaluations);
          break;
      }

      // If evaluation should be negated.
      if ($negate) {
        $condition_evaluation = !$condition_evaluation;
      }

      // Add this condition's evaluation to array.
      $condition_evaluations[] = $condition_evaluation;
    }

    // Make sure condition values are unique.
    $this->foundConditions = array_unique($this->foundConditions);

    if (!empty($condition_evaluations)) {
      $group_eval = NULL;
      foreach ($condition_evaluations as $eval) {
        // If this is the first condition to evaluate, just set.
        if (empty($group_eval)) {
          $group_eval = $eval;
        }
        else {
          // Use group operation to evaluate conditions together.
          switch ($group_op) {
            // If group op is AND.
            case 'AND':
              $group_eval &= $eval;
              break;

            // If group op is OR.
            case 'OR':
              $group_eval |= $eval;
              break;
          }
        }
      }

      // Return group evaluation.
      return $group_eval;
    }

    return FALSE;
  }

  /**
   * Get array of conditions found after evaluation.
   *
   * @return array
   *   return foundConditions.
   */
  public function foundConditions() : array {
    return $this->foundConditions;
  }

}
