<?php

namespace Drupal\smart_content_cdn\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\taxonomy\Entity\Vocabulary;
use Pantheon\EI\HeaderData;

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
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'smart_content_cdn.config';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Form constructor.
    $form = parent::buildForm($form, $form_state);

    // Config object for default values.
    $config = $this->config('smart_content_cdn.config');

    // Array of node bundle options.
    $cts = $this->getFormOptions('node_type');

    // List of taxonomy vocabulary options.
    $vocab_options = $this->getVocabularyOptions();

    $interest_vocab_default = $config->get('interest_vocab') ?? NULL;

    // Get list of fields for each bundle type.
    $field_options = [];
    if (!empty($cts)) {
      // Get interest vocab if set.
      $interest_vocab_value = $form_state->getValue('interest_vocab') ?? $interest_vocab_default;

      foreach ($cts as $bundle => $label) {
        $field_options[$bundle] = $this->getTaxonomyFieldOptions('node', $bundle, $interest_vocab_value);
      }
    }

    $default = $config->get('set_vary');
    $form['set_vary'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Set Vary header'),
      '#description' => $this->t('Should the Vary header be set with smart content cdn header data for smart caching?'),
      '#default_value' => $default ?? TRUE,
    ];

    $default = $config->get('geo_default');
    $form['geo_default'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default Geo value'),
      '#description' => $this->t('Default value for Geo location data'),
      '#default_value' => $default ?? '',
      '#size' => 10,
      '#maxlength' => 10,
    ];

    $default = $config->get('interest_threshold');
    $form['interest_threshold'] = [
      '#type' => 'number',
      '#title' => $this->t('Interest Threshold'),
      '#description' => $this->t('How many times user needs to visit a category to be placed in the personalized segment?'),
      '#default_value' => $default ?? '',
      '#size' => 10,
      '#maxlength' => 10,
    ];


    $form['interest_vocab'] = [
      '#title' => $this->t('Interest Vocabulary'),
      '#type' => 'select',
      '#default_value' => $interest_vocab_default ?? [],
      '#options' => $vocab_options,
      '#ajax' => [
        'callback' => '::updateInterestFields',
        'event' => 'change',
      ],
    ];

    if (!empty($cts) && !empty($field_options)) {
      // Fieldset for interest fields per content type.
      $form['interest_fields'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Interest Fields'),
        '#description' => $this->t("Selected taxonomy fields will be used to determine user's interests."),
        '#prefix' => '<div id="interest-fields-wrapper">',
        '#suffix' => '</div>',
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
            '#default_value' => $default ?? [],
            '#options' => $field_options[$bundle],
          ];
        }
      }
    }

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
   * Ajax callback to update interest fields with options based on vocabulary.
   */
  public function updateInterestFields(array &$form, FormStateInterface $form_state) {
    $interest_vocab = $form_state->getValue('interest_vocab');

    if (!empty($interest_vocab)) {
      // Array of node bundle options.
      $cts = $this->getFormOptions('node_type');

      // Get list of fields for each bundle type.
      if (!empty($cts)) {
        foreach ($cts as $bundle => $bundle_label) {
          $interest_fields_key = 'interest_fields_node_' . $bundle;

          // Get taxonomy field options.
          $bundle_field_options = $this->getTaxonomyFieldOptions('node', $bundle, $interest_vocab);
          if (!empty($bundle_field_options)) {
            // Reset available options.
            $form['interest_fields'][$interest_fields_key]['#options'] = $bundle_field_options;
          }
          else {
            // Unset missing bundles.
            unset($form['interest_fields'][$interest_fields_key]);
          }
        }
      }
    }

    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('#interest-fields-wrapper', $form['interest_fields']));

    return $response;
  }

  /**
   * Get options for the form based on the type of the entity type.
   *
   * @param string $storage_type
   *   Type of the entity to load.
   *
   * @return array
   *   Array of options keyed by id and showing entity label.
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
   * @param string $vocab
   *   Limit fields to specified vocabulary machine name.
   *
   * @return array
   *   Array of options keyed by id and showing field label.
   */
  protected function getTaxonomyFieldOptions(string $entity_type_id, string $bundle, string $vocab = NULL) {
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

      $field_vocabs = $settings['handler_settings']['target_bundles'] ?? [];

      if (empty($vocab) || array_key_exists($vocab, $field_vocabs)) {
        // If taxonomy term entity reference field, add to options.
        $options[$field_id] = $field->getLabel() ?? $field_id;
      }
    }

    return $options;
  }

  /**
   * Helper function to get vocabulary options.
   *
   * @return array
   *   Array of options keyed by id and showing vocab label.
   */
  protected function getVocabularyOptions() {
    // Get list of taxonomy vocabularies.
    $vocabs = Vocabulary::loadMultiple();

    $options = [];
    foreach ($vocabs as $machine_name => $vocab) {
      // Create select option.
      $options[$machine_name] = $vocab->get('name') ?? $machine_name;
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
