<?php

namespace Drupal\smart_content_cdn\Form;

use Drupal\user\Form\UserLoginForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a user login form.
 *
 * @internal
 */
class SubscriberLoginForm extends UserLoginForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'subscriber_login_form';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if (empty($uid = $form_state->get('uid'))) {
      return;
    }
    $account = $this->userStorage->load($uid);

    // Get list of user roles.
    $roles = $account->getRoles();

    // If a regular role, login like normal.
    if (!in_array('subscriber', $roles)) {
      return parent::submitForm($form, $form_state);
    }
    // If subscriber role.
    else {
      // A destination was set, probably on an exception controller.
      if (!$this->getRequest()->request->has('destination')) {
        $form_state->setRedirect(
          'entity.user.canonical',
          ['user' => $account->id()]
        );
      }
      else {
        $this->getRequest()->query->set('destination', $this->getRequest()->request->get('destination'));
      }

      \Drupal::currentUser()->setAccount($account);
      \Drupal::logger('user')->notice('Session opened for %name.', ['%name' => $account->getAccountName()]);
      // Update the user table timestamp noting user has logged in.
      // This is also used to invalidate one-time login links.
      $account->setLastLoginTime(REQUEST_TIME);
      \Drupal::entityTypeManager()
        ->getStorage('user')
        ->updateLastLoginTimestamp($account);

      \Drupal::moduleHandler()->invokeAll('user_login', [$account]);
    }
  }

}
