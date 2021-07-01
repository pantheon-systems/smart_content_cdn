<?php

namespace Drupal\smart_content_cdn\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Contains Subscriber Login form.
 */
class SubscriberLoginForm extends FormBase {

  /**
   * Test login username/password.
   *
   * @var array
   */
  private $loginInfo = [
    'TestUser' => '1234',
  ];

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'subscriber_login';
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#default_value' => 'TestUser',
      '#required' => TRUE,
    ];

    $form['password'] = [
      '#type' => 'password',
      '#title' => $this->t('Password'),
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    if (!empty($values['username']) && !empty($values['password']) &&
    array_key_exists($values['username'], $this->loginInfo) &&
    $this->loginInfo[$values['username']] == $values['password']) {
      $auth = \Drupal::service('jwt.authentication.jwt');
      $token = $auth->generateToken();

      $cookie_service = \Drupal::service('subscriber_cookie');
      $cookie_service->setCookieValue($token);
    }
    else {
      \Drupal::messenger()->addStatus('Invalid username or password.');
    }
  }

}
