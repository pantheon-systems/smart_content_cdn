<?php

namespace Drupal\smart_content_cdn\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Pantheon\EI\HeaderData;

/**
 * Main HeaderEventSubscriber class.
 */
class HeaderEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = ['onRespond', -200];
    return $events;
  }

  /**
   * This method is called when the kernel.response is dispatched.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   The dispatched event.
   */
  public function onRespond(ResponseEvent $event) {
    $config = \Drupal::configFactory()->get('smart_content_cdn.config');

    $response = $event->getResponse();

    // Check if Vary Header should be set.
    if (($config->get('set_vary') ?? TRUE) && method_exists($response, 'getCacheableMetadata')) {
      // Get cache tags.
      $tags = $response->getCacheableMetadata()->getCacheTags();

      // Get existing vary headers if they've been set in other
      // EventSubscribers.
      $headers = $response->headers->all();
      $vary_headers = array_key_exists('vary', $headers) ? $headers['vary'] : [];

      // Add Geo to Vary header if there is a Geo decision on the page.
      if (in_array('smart_content_cdn.geo', $tags)) {
        $vary_headers[] = 'Audience';
      }
      // Add Interest to Vary header if there is a Interest decision on the
      // page.
      if (in_array('smart_content_cdn.interest', $tags)) {
        $vary_headers[] = 'Interest';
      }

      // Retrieve and set vary header.
      $smart_content_cdn = new HeaderData();
      $response_vary_header = $smart_content_cdn->returnVaryHeader($vary_headers);
      $response->headers->add($response_vary_header);
    }
  }

}
