<?php

namespace Drupal\smart_content_cdn\Plugin\smart_content\Condition;

// @TODO Remove when working with vendor library.
require_once DRUPAL_ROOT . "/modules/custom/smart_content_cdn/libraries/kalamuna/smart-cdn/src/HeaderData.php";

use Drupal\smart_content\Condition\ConditionTypeConfigurableBase;
use Drupal\smart_content_cdn\Kalamuna\SmartCDN\HeaderData;

// @TODO Switch when working with vendor library.
// use Kalamuna\SmartCDN\HeaderData;

/**
 * Provides a default Smart Condition.
 *
 * @SmartCondition(
 *   id = "smart_cdn",
 *   label = @Translation("Smart CDN"),
 *   group = "smart_cdn",
 *   weight = 0,
 *   deriver = "Drupal\smart_content_cdn\Plugin\Derivative\SmartCDNDerivative"
 * )
 */
class SmartCDNCondition extends ConditionTypeConfigurableBase {

  /**
   * {@inheritdoc}
   */
  public function getLibraries() {
    $libraries = array_unique(array_merge(parent::getLibraries(), ['smart_content_cdn/condition.cdn']));
    return $libraries;
  }

  /**
   * {@inheritdoc}
   */
  public function getAttachedSettings() {
    $settings = parent::getAttachedSettings();
    $field_settings = $settings['settings'] + $this->defaultFieldConfiguration();
    $derivative_id = $this->getDerivativeId();

    // Get personalization object.
    $smart_content_cdn = new HeaderData();
    $p_obj = $smart_content_cdn->returnPersonalizationObject('Audience', 'geo');

    // Determine CDN value based on derivative id.
    $cdn_value = NULL;
    switch ($derivative_id) {
      case 'geo':
        $cdn_value = !empty($p_obj['geo']) ? $p_obj['geo'] : NULL;
        break;
    }

    // Set smart_cdn settings to be used on JS.
    $field_settings['smart_cdn'] = [
      'value' => $cdn_value,
    ];

    // Set field condition settings.
    $settings['settings'] = $field_settings;
    $settings['field']['settings'] = $field_settings;

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultFieldConfiguration() {
    return [
      'smart_cdn' => [
        'derivative' => NULL,
        'value' => NULL,
      ],
    ];
  }

}
