<?php

declare(strict_types = 1);

// @TODO Switch when working with vendor library.
// namespace Kalamuna\SmartCDN;
namespace Drupal\smart_content_cdn\Kalamuna\SmartCDN;

/**
 * A class to handle smart content delivery network for users.
 */
class HeaderData {

  /**
   * Header data.
   *
   * @var headers
   */
  private $headers;

  /**
   * Constructor.
   */
  public function __construct() {
    $this->headers = $this->getRequestHeaders();
  }

  /**
   * Retrieve header data and set in $headers array.
   */
  private function getRequestHeaders(): array {
    $headers = [];
    foreach ($_SERVER as $key => $value) {
      if (substr($key, 0, 5) <> 'HTTP_') {
        continue;
      }
      $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
      $headers[$header] = $value;
    }

    return $headers;
  }

  /**
   * Gets a header by key.
   *
   * @param string $key
   *   Key for the header.
   *
   * @return string
   *   Returns header value.
   */
  public function getHeader($key): string {
    return !empty($this->headers[$key]) ? $this->headers[$key] : '';
  }

  /**
   * Parses a header by key using a specified regex.
   *
   * @param string $key
   *   Key for the header.
   *
   * @return array|string
   *   Returns important parts of header string.
   */
  public function parseHeader($key) {
    // Get specified header.
    $header = $this->getHeader($key);

    if (!empty($header)) {
      $parsed_header = [];
      switch ($key) {
        // Parse Audience header.
        case 'Audience':
          // Separate different pairs in header string.
          $header_parts = explode('|', $header);

          foreach ($header_parts as $header_part) {
            // Skip if empty.
            if (empty($header_part)) {
              continue;
            }

            // Separate the pair string into key and value.
            $header_pair = explode(':', $header_part);
            if (count($header_pair) >= 2) {
              $parsed_header[$header_pair[0]] = $header_pair[1];
            }
            // If string isn't formatted as a pair, just set string.
            else {
              $parsed_header[$key][] = $header_part;
            }
          }
          break;

        // Parse Interest header.
        case 'Interest':
          $parsed_header = explode(',', $header);
          break;

        // By default, just return header.
        default:
          $parsed_header = $header;
          break;
      }

      return $parsed_header;
    }

    return [];
  }

  /**
   * Gets personalizaition object.
   *
   * @return array
   *   Returns object with data used for personalization.
   */
  public function returnPersonalizationObject(): array {
    $p_obj = [];

    $header_keys = [
      'Audience',
      'Interest',
      'Role',
    ];

    foreach ($header_keys as $key) {
      // Get parsed header value.
      $header_parsed = $this->parseHeader($key);

      // Add header value to personalization object.
      if (!empty($header_parsed)) {
        $p_obj[$key] = $header_parsed;
      }
    }

    return $p_obj;
  }

  /**
   * Returns vary header array.
   *
   * @param string|array $key
   *   Key for the header, or array of keys.
   *
   * @return array
   *   Vary header array, based on header data.
   */
  public function returnVaryHeader($key): array {
    // Get current vary data if it exists, otherwise start with empty array.
    $vary_header = $this->getHeader('Vary');
    $vary_header_array = !empty($vary_header) ? explode(', ', $vary_header) : [];

    // If array, merge the arrays.
    if (is_array($key)) {
      $vary_header_array += $key;
    }
    // Otherrwise, add header $key to Vary header.
    else {
      $vary_header_array[] = $key;
    }

    // Return vary header array structure.
    return ['vary' => $vary_header_array];
  }

}
