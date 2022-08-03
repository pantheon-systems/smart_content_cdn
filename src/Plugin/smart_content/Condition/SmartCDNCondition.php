<?php

namespace Drupal\smart_content_cdn\Plugin\smart_content\Condition;

use Drupal\smart_content\Condition\ConditionTypeConfigurableBase;
use Pantheon\EI\HeaderData;

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
    $config = \Drupal::configFactory()->get('smart_content_cdn.config');

    // Get personalization object.
    $smart_content_cdn = new HeaderData();
    $p_obj = $smart_content_cdn->returnPersonalizationObject();

    // Determine CDN value based on derivative id.
    $cdn_value = NULL;
    switch ($derivative_id) {
      case 'geo_continent_code':
        // Set value to Geo header if available.
        $cdn_value = !empty($p_obj['P13n-Geo-Continent-Code']) ? $p_obj['P13n-Geo-Continent-Code'] : NULL;
        break;

      case 'geo_country_code':
        // Get default Geo value from config.
        $geo_default = $config->get('geo_default') ?? NULL;

        // Set value to Geo header if available, set to default config value
        // otherwise.
        $cdn_value = !empty($p_obj['P13n-Geo-Country-Code']) ? $p_obj['P13n-Geo-Country-Code'] : $geo_default;
        break;

      case 'geo_country_name':
        // Set value to Geo header if available.
        $cdn_value = !empty($p_obj['P13n-Geo-Country-Name']) ? $p_obj['P13n-Geo-Country-Name'] : NULL;
        break;

      case 'geo_region':
        // Set value to Geo header if available.
        $cdn_value = !empty($p_obj['P13n-Geo-Region']) ? $p_obj['P13n-Geo-Region'] : NULL;
        break;

      case 'geo_city':
        // Set value to Geo header if available.
        $cdn_value = !empty($p_obj['P13n-Geo-City']) ? $p_obj['P13n-Geo-City'] : NULL;
        break;

      case 'geo_connection_type':
        // Set value to Geo header if available.
        $cdn_value = !empty($p_obj['P13n-Geo-Conn-Type']) ? $p_obj['P13n-Geo-Conn-Type'] : NULL;
        break;

      case 'geo_conneciton_speed':
        // Set value to Geo header if available.
        $cdn_value = !empty($p_obj['P13n-Geo-Conn-Speed']) ? $p_obj['P13n-Geo-Conn-Speed'] : NULL;
        break;

      case 'interest':
        $cdn_value = !empty($p_obj['Interest']) ? $p_obj['Interest'] : [];
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
