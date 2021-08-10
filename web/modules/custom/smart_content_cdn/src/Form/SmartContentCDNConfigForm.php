<?php

namespace Drupal\smart_content_cdn\Form;

// @TODO Remove when working with vendor library.
require_once DRUPAL_ROOT . "/modules/custom/smart_content_cdn/libraries/kalamuna/smart-cdn/src/HeaderData.php";

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\smart_content_cdn\Kalamuna\SmartCDN\HeaderData;

// @TODO Switch when working with vendor library.
// use\Kalamuna\SmartCDN\HeaderData;

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

    // Config object for default values.
    $config = $this->config('smart_content_cdn.config');

    $default = $config->get('set_vary');
    $form['set_vary'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Set Vary header'),
      '#description' => $this->t('Should the Vary header be set with smart content cdn header data for smart caching?'),
      '#default_value' => isset($default) ? $default : TRUE,
    ];

    $default = $config->get('geo_default');
    $form['geo_default'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default Geo value'),
      '#description' => $this->t('Default value for Geo location data'),
      '#default_value' => isset($default) ? $default : '',
      '#size' => 10,
      '#maxlength' => 10,
    ];

    // Get header data.
    $smart_content_cdn = new HeaderData();
    $audience_header = $smart_content_cdn->getHeader('Audience') ?? '';
    $interest_header = $smart_content_cdn->getHeader('Interest') ?? '';

    // Output current header data.
    $form['header_output'] = [
      '#markup' => '<h2>Current Headers</h1>
                              <div>Audience: ' . $audience_header . '</div>
                              <div>Interest: ' . $interest_header . '</div>',
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
  }

}
