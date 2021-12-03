<?php

namespace Drupal\smart_content_cdn\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller routines for user routes.
 */
class SubscriberUserController extends ControllerBase {

  /**
   * Logs the current user out.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirection to home page.
   */
  public function logout() {
    if ($this->currentUser()->isAuthenticated()) {
      user_logout();
    }

    // Delete subscriber cookie on logout.
    $cookie_service = \Drupal::service('subscriber_cookie');
    $cookie_service->setShouldDeleteCookie(TRUE);

    return $this->redirect('<front>');
  }

}
