<?php

namespace Drupal\smart_content_cdn\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class SubscriberCookie.
 */
class SubscriberCookie implements EventSubscriberInterface {

  /**
   * Current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request|null
   */
  protected $request;

  /**
   * Name of the cookie this service will manage.
   *
   * @var string
   */
  protected $cookieName = 'subscriberToken';

  /**
   * The cookie value that will be set during the respond event.
   *
   * @var mixed
   */
  protected $newCookieValue;

  /**
   * Whether or not the cookie should be updated during the response.
   *
   * @var bool
   */
  protected $shouldUpdateCookie = FALSE;

  /**
   * Whether or not the cookie should be deleted during the response.
   *
   * @var bool
   */
  protected $shouldDeleteCookie = FALSE;

  /**
   * MySimpleCookie constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   Request stack service.
   */
  public function __construct(RequestStack $request_stack) {
    $this->request = $request_stack->getCurrentRequest();
  }

  /**
   * Get this cookie's name.
   *
   * @return string
   */
  public function getCookieName() {
    return $this->cookieName;
  }

  /**
   * Get the cookie's value.
   *
   * @return mixed
   *   Cookie value.
   */
  public function getCookieValue() {
    // If we're mid-request and setting a new cookie value, return that new
    // value. This allows other parts of the system access to the most recent
    // cookie value.
    if (!empty($this->newCookieValue)) {
      return $this->newCookieValue;
    }

    return $this->request->cookies->get($this->getCookieName());
  }

  /**
   * Set the cookie's new value.
   *
   * @param mixed $value
   */
  public function setCookieValue($value) {
    $this->shouldUpdateCookie = TRUE;
    $this->newCookieValue = $value;
  }

  /**
   * Whether or not the cookie should be updated during the response.
   *
   * @return bool
   */
  public function getShouldUpdateCookie() {
    return $this->shouldUpdateCookie;
  }

  /**
   * Whether or not the cookie should be deleted during the response.
   *
   * @return bool
   */
  public function getShouldDeleteCookie() {
    return $this->shouldDeleteCookie;
  }

  /**
   * Set whether or not the cookie should be deleted during the response.
   *
   * @param bool $delete_cookie
   *   Whether or not to delete the cookie during the response.
   */
  public function setShouldDeleteCookie($delete_cookie = TRUE) {
    $this->shouldDeleteCookie = (bool) $delete_cookie;
  }

  /**
   * {@inheritDoc}
   */
  public static function getSubscribedEvents() {
    return [
      KernelEvents::RESPONSE => 'onResponse',
    ];
  }

  /**
   * React to the symfony kernel response event by managing visitor cookies.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The event to process.
   */
  public function onResponse(FilterResponseEvent $event) {
    $response = $event->getResponse();

    if ($this->getShouldUpdateCookie()) {
      $new_cookie = new Cookie($this->getCookieName(), $this->getCookieValue());
      $response->headers->setCookie($new_cookie);
    }

    // The "should delete" needs to happen after "should update", or we could
    // find ourselves in a situation where we are unable to delete the cookie
    // because another part of the system is trying to update its value.
    if ($this->getShouldDeleteCookie()) {
      $response->headers->clearCookie($this->getCookieName());
    }
  }

}
