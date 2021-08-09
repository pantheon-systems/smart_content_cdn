<?php

namespace Drupal\smart_content_cdn\Controller;

// @TODO Remove when working with vendor library.
require_once DRUPAL_ROOT . "/modules/custom/smart_content_cdn/libraries/kalamuna/smart-cdn/src/HeaderData.php";

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\smart_content_cdn\Kalamuna\SmartCDN\HeaderData;

// @TODO Switch when working with vendor library.
// use\Kalamuna\SmartCDN\HeaderData;

/**
 * Handles AJAX functionality for Smart Content CDN.
 */
class SmartCDNAjaxController extends ControllerBase {

  /**
   * Index.
   *
   * @return array
   *   Return build array.
   */
  public function interestCount(NodeInterface $node) {
    // When should tag count start affecting interest header.
    $popularity_count = 3;
    // Key name for session variable.
    $session_name = 'interest';
    // Taxonomy field name for interest tags.
    $interest_field_name = 'field_tags';

    // Get session interest tids.
    $session = \Drupal::request()->getSession();
    $session_tids = $session->get($session_name);

    // Get header data.
    $smart_content_cdn = new HeaderData();
    $interest_header = $smart_content_cdn->getHeader('Interest') ?? '';

    // Array to store current node's interest tids.
    $interest_tids = [];

    // Get interest field.
    $interest_field = $node->get($interest_field_name)->getValue();
    if (!empty($interest_field)) {
      // Get array of interest tids.
      $entity_tids = array_map(function ($interest_field) {
        return $interest_field['target_id'] ?? NULL;
      }, $interest_field);

      // Filter out any empty elements.
      $entity_tids = array_filter($entity_tids);

      // If session interest tids are empty, set with current node tids.
      if (empty($session_tids)) {
        // Initialize tids to a count of 1.
        $session_tids = array_fill_keys($entity_tids, 1);
      }
      else {
        // Loops through current node tids.
        foreach ($entity_tids as $tid) {
          // If tid is in session interest tids, increment.
          if (array_key_exists($tid, $session_tids)) {
            $session_tids[$tid]++;
          }
          // Otherwise, set to a count of 1.
          else {
            $session_tids[$tid] = 1;
          }
        }
      }

      // Sort counts in descending order.
      arsort($session_tids, SORT_NUMERIC);

      // Save interest tids.
      $session->set($session_name, $session_tids);

      // Gather popular interest tids.
      foreach ($session_tids as $tid => $count) {
        // If tid has been visited a specified number of times, add it to interest array.
        if ($count >= $popularity_count) {
          $interest_tids[] = $tid;
        }
      }

      if (!empty($interest_tids)) {
        // Since tids are sorted by count, first will be highest count tag.
        $interest_cookie_value = $interest_tids[0];

        // If interest value has changed, set cookie.
        if ($interest_cookie_value != $interest_header) {
          // Save interest tids in a cookie.
          $cookie_service = \Drupal::service('interest_cookie');
          $cookie_service->setCookieValue($interest_cookie_value);
        }
      }
    }

    // Return empty ajax response.
    return new AjaxResponse();
  }

}
