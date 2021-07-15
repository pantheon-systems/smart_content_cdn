<?php

namespace Drupal\smart_content_cdn\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Deriver for SmartCDNCondition.
 *
 * Provides a deriver for
 * Drupal\smart_content_cdn\Plugin\smart_content\Condition\SmartCDNCondition.
 * Create derivatives based on smart_cdn php library.
 */
class SmartCDNDerivative extends DeriverBase {
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = [
      'geo' => [
        'label' => $this->t('Geo'),
        'type' => 'textfield',
      ] + $base_plugin_definition,
      'interest' => [
        'label' => $this->t('Interest'),
        'type' => 'array_select',
        'options_callback' => [get_class($this), 'getInterestOptions'],
      ] + $base_plugin_definition,
    ];
    return $this->derivatives;
  }

  /**
   * Returns list of 'Interest' for select element.
   *
   * @return array
   *   Array of Interest tids.
   */
  public static function getInterestOptions() {
    // Machine name for taxonomy vocab.
    $vocab_name = 'tags';

    // Load all terms in taxonomy.
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vocab_name);

    // List of select options.
    $term_options = [];

    if (!empty($terms)) {
      // Generate options from taxonomy terms.
      foreach ($terms as $term) {
        $term_options[$term->tid] = $term->name;
      }
    }

    return $term_options;
  }

}
