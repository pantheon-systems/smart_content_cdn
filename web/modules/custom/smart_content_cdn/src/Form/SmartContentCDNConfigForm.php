<?php

namespace Drupal\smart_content_cdn\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Contains Smart Content CDN configuration form.
 */
class SmartContentCDNConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['smart_content_cdn.config'];
  }

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'smart_content_cdn.config';
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Form constructor.
    $form = parent::buildForm($form, $form_state);

    $config = $this->config('smart_content_cdn.config');

    $form['set_vary'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Set Vary header'),
      '#default_value' => ($config->get('set_vary')) ? $config->get('set_vary') : TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get an array of form values.
    $values = $form_state->getValues();
    $config = \Drupal::configFactory()->getEditable('smart_content_cdn.config');

    $ignore_keys = [
      '_core',
      'submit',
      'form_build_id',
      'form_token',
      'form_id',
      'op',
    ];

    // Loop through the values and save them to configuration.
    foreach ($values as $value_key => $value) {
      if (in_array($value_key, $ignore_keys)) {
        continue;
      }
      $config->set($value_key, $value)->save();
    }

    parent::submitForm($form, $form_state);
  }

}
