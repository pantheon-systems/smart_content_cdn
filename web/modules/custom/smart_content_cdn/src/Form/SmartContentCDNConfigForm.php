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

    // Array of node bundle options.
    $cts = $this->getFormOptions('node_type');

    // Get list of fields for each bundle type.
    $field_options = [];
    if (!empty($cts)) {
      foreach ($cts as $bundle => $label) {
        $field_options[$bundle] = $this->getTaxonomyFieldOptions('node', $bundle);
      }
    }

    $default = $config->get('set_vary');
    $form['set_vary'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Set Vary header'),
      '#description' => $this->t('Should the Vary header be set with smart content cdn header data for smart caching?'),
      '#default_value' => isset($default) ? $default : TRUE,
    ];
    $default = $config->get('set_preview');
    $form['set_preview'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable preview mode for role?'),
      '#description' => $this->t('If preview mode is enabled you will be viewing the site as a subscriber, if disabled - as anonymous'),
      '#default_value' => isset($default) ? $default : FALSE,
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

    $default = $config->get('interest_threshold');
    $form['interest_threshold'] = [
      '#type' => 'number',
      '#title' => $this->t('Interest Threshold'),
      '#description' => $this->t('How many times user needs to visit a category to be placed in the personalized segment?'),
      '#default_value' => isset($default) ? $default : '',
      '#size' => 10,
      '#maxlength' => 10,
    ];

    if (!empty($cts) && !empty($field_options)) {
      // Fieldset for interest fields per content type.
      $form['interest_fields'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Interest Fields'),
        '#description' => $this->t("Selected taxonomy fields will be used to determine user's interests."),
      ];

      // For each bundle type.
      foreach ($cts as $bundle => $bundle_label) {
        // If the bundle has valid fields.
        if (!empty($field_options[$bundle])) {
          $interest_fields_key = 'interest_fields_node_' . $bundle;
          $default = $config->get($interest_fields_key);
          $form['interest_fields'][$interest_fields_key] = [
            '#title' => $bundle_label,
            '#type' => 'checkboxes',
            '#default_value' => isset($default) ? $default : [],
            '#options' => $field_options[$bundle],
          ];
        }
      }
    }

    $default = $config->get('subscriber_threshold');
    $form['subscriber_threshold'] = [
      '#type' => 'number',
      '#title' => $this->t('Subscriber Threshold'),
      '#description' => $this->t('How many subscription protected nodes can anonymous users read?'),
      '#default_value' => isset($default) ? $default : '',
      '#size' => 10,
      '#maxlength' => 10,
    ];

    $default = $config->get('subscription_content_types');
    $cts = $this->getFormOptions('node_type');
    $form['subscription_content_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Content types restricted by subscription'),
      '#description' => $this->t('Content types restricted by subscription that will be displayed in Teaser view mode when user is anonymous and in full when user is a subscriber'),
      '#default_value' => isset($default) ? $default : [],
      '#options' => $cts,
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
   * Helper function to get options for the form based on the type of the entity type.
   *
   * @param string $storage_type
   *    Type of the entity to load.
   *
   * @return array $options
   *    Array of options keyed by id and showing entity label.
   */
  protected function getFormOptions(string $storage_type) {
    $entities = \Drupal::entityTypeManager()->getStorage($storage_type)->loadMultiple();
    $options = [];
    foreach ($entities as $key => $entity) {
      $options[$entity->id()] = $entity->label();
    }
    return $options;
  }

  /**
   * Helper function to get taxonomy field options for given entity bundle.
   *
   * @param string $entity_type_id
   *   Type of the entity to load.
   * @param string $bundle
   *   Entity id to load.
   *
   * @return array
   *   Array of options keyed by id and showing field label.
   */
  protected function getTaxonomyFieldOptions(string $entity_type_id, string $bundle) {
    // Get list of field definitions for bundle.
    $entity_fields = \Drupal::service('entity_field.manager')->getFieldDefinitions($entity_type_id, $bundle);

    $options = [];
    foreach ($entity_fields as $field_id => $field) {
      // Check that field is an entity reference field.
      if ($field->getType() != 'entity_reference') {
        continue;
      }

      // Check that field is for taxonomy term entity reference.
      $settings = $field->getSettings();
      if (empty($settings['handler']) || $settings['handler'] != 'default:taxonomy_term') {
        continue;
      }

      // If taxonomy term entity reference field, add to options.
      $options[$field_id] = $field->getLabel() ?? $field_id;
    }

    return $options;
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
