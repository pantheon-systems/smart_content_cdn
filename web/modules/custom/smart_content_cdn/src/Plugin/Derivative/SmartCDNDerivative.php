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
    ];
    return $this->derivatives;
  }

}
