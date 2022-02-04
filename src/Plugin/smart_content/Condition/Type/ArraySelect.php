<?php

namespace Drupal\smart_content_cdn\Plugin\smart_content\Condition\Type;

use Drupal\Core\Form\FormStateInterface;
use Drupal\smart_content\Condition\Type\ConditionTypeBase;

/**
 * Provides a 'array_select' ConditionType.
 *
 * @SmartConditionType(
 *  id = "array_select",
 *  label = @Translation("Array Select"),
 * )
 */
class ArraySelect extends ConditionTypeBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $options = $this->getFormOptions();
    $form['value'] = [
      '#type' => 'select',
      '#required' => TRUE,
      '#options' => $options,
      '#default_value' => $this->configuration['value'] ?? $this->defaultFieldConfiguration()['value'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultFieldConfiguration() {
    return [
      'value' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries() {
    return ['smart_content_cdn/condition_type.cdn'];
  }

  /**
   * {@inheritdoc}
   */
  public function getAttachedSettings() {
    return $this->getConfiguration() + $this->defaultFieldConfiguration();
  }

  /**
   * Get the options for this select element.
   *
   * @return array
   *   The array of options.
   */
  public function getFormOptions() {
    $condition_definition = $this->conditionInstance->getPluginDefinition();
    $options = [];
    // If 'options' are defined in definition, populate options.
    if (isset($condition_definition['options'])) {
      $options = $condition_definition['options'];
    }
    // If 'options_callback' is defined in definition, validate and populate
    // options.
    elseif (isset($condition_definition['options_callback'])) {
      // Confirm 'options_callback' is callable function/method.
      if (is_callable($condition_definition['options_callback'], FALSE, $callable_name)) {
        $options = call_user_func($condition_definition['options_callback'], $this->conditionInstance);
      }
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getHtmlSummary() {
    $configuration = $this->getConfiguration();
    $options = $this->getFormOptions();
    $value = $configuration['value'];
    if (isset($options[$configuration['value']])) {
      $value = $options[$configuration['value']];
    }
    return [
      '#type' => 'markup',
      'op' => [
        '#markup' => $this->t('equals'),
        '#prefix' => '<span class="condition-type-op">',
        '#suffix' => '</span> ',
      ],
      'value' => [
        '#markup' => "$value",
        '#prefix' => '<span class="condition-type-value">"',
        '#suffix' => '"</span>',
      ],
    ];
  }

}
