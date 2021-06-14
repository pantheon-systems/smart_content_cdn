<?php

namespace Drupal\smart_content_cdn\Controller;

use Drupal\Core\Controller\ControllerBase;
use Kalamuna\SmartCDN\HeaderData;

/**
 * The CDN controller.
 */
class CDNController extends ControllerBase {

  /**
   * Returns a render-able array for a display page.
   */
  public function content() {
    $renderer = \Drupal::service('renderer');

    // Get personalization object.
    $smart_content_cdn = new HeaderData();
    $p_obj = $smart_content_cdn->returnPersonalizationObject('Interest', 'geo');

    // Get location if it exists.
    $geo_location = !empty($p_obj['geo']) ? $p_obj['geo'] : 'N/A';

    // Choose image file based on location.
    $image_file = NULL;
    switch ($geo_location) {
      case 'US':
        $image_file = 'eagle.jpeg';
        break;

      case 'CA':
        $image_file = 'beaver.jpeg';
        break;
    }

    $build = [
      '#geo' => $geo_location,
      '#image_path' => !empty($image_file) ?
        '/' . drupal_get_path('module', 'smart_content_cdn') . '/assets/' . $image_file :
        NULL,
      '#theme' => 'hello_world_content',
      '#attached' => [
        'library' => 'smart_content_cdn/hello_world',
      ]
    ];

    // Add cacheable dependency for render array based on geo data.
    $renderer->addCacheableDependency($build, $geo_location);

    return $build;
  }

}
