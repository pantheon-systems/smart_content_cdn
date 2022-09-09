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
      'geo_continent_code' => [
        'label' => $this->t('Geo: Continent Code'),
        'type' => 'textfield',
      ] + $base_plugin_definition,
      'geo_country_code' => [
        'label' => $this->t('Geo: Country Code'),
        'type' => 'textfield',
      ] + $base_plugin_definition,
      'geo_country_name' => [
        'label' => $this->t('Geo: Country Name'),
        'type' => 'textfield',
      ] + $base_plugin_definition,
      'geo_region' => [
        'label' => $this->t('Geo: Region'),
        'type' => 'textfield',
      ] + $base_plugin_definition,
      'geo_city' => [
        'label' => $this->t('Geo: City'),
        'type' => 'textfield',
      ] + $base_plugin_definition,
      'geo_connection_type' => [
        'label' => $this->t('Geo: Connection Type'),
        'type' => 'textfield',
      ] + $base_plugin_definition,
      'geo_connection_speed' => [
        'label' => $this->t('Geo: Connection Speed'),
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
   * Returns list of 'Interest' options for select element.
   *
   * @return array
   *   Array of Interest tids.
   */
  public static function getInterestOptions() {
    // Get interest vocabulary.
    $config = \Drupal::configFactory()->get('smart_content_cdn.config');
    $interest_vocab = $config->get('interest_vocab');

    if (!empty($interest_vocab)) {
      // Return list of taxonomy term options.
      return SmartCDNDerivative::getTaxonomyOptions($interest_vocab);
    }

    return [];
  }

  /**
   * Returns list of taxonomy term options for select element.
   *
   * @param string $vocab_name
   *   Machine name for vocab to get term options from.
   *
   * @return array
   *   Array of term names keyed by tid.
   */
  public static function getTaxonomyOptions($vocab_name) {
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
