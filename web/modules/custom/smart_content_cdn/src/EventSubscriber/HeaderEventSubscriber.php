<?php

namespace Drupal\smart_content_cdn\EventSubscriber;

// @TODO Remove when working with vendor library.
require_once DRUPAL_ROOT . "/modules/custom/smart_content_cdn/libraries/kalamuna/smart-cdn/src/HeaderData.php";

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\smart_content_cdn\Kalamuna\SmartCDN\HeaderData;

// @TODO Switch when working with vendor library.
// use\Kalamuna\SmartCDN\HeaderData;

/**
 * Class HeaderEventSubscriber.
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
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The dispatched event.
   */
  public function onRespond(FilterResponseEvent $event) {
    $config = \Drupal::configFactory()->get('smart_content_cdn.config');

    // Check if Vary Header should be set.
    if ($config->get('set_vary') ?? TRUE) {
      $response = $event->getResponse();

      // Retrieve and set vary header.
      $smart_content_cdn = new HeaderData();
      $response_vary_header = $smart_content_cdn->returnVaryHeader('Interest');
      $response->headers->add($response_vary_header);
    }
  }

}
