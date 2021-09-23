<?php

namespace Drupal\smart_content_cdn\Plugin\Block;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\smart_content\Decision\DecisionManager;
use Drupal\smart_content\Decision\Storage\DecisionStorageBase;
use Drupal\smart_content\Decision\Storage\DecisionStorageManager;
use Drupal\smart_content\Form\SegmentSetConfigEntityForm;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\smart_content_block\BlockPluginCollection;

/**
 * Provides a 'SmartBlock' block.
 *
 * @todo: omit this block from layout_builder and potentially block_field.
 *
 * @Block(
 *  id = "smart_content_cdn_ssr_decision_block",
 *  admin_label = @Translation("SSR Decision Block"),
 * )
 */
class SSRDecisionBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The decision storage.
   *
   * @var mixed
   */
  protected $decisionStorage;

  /**
   * The decision storage plugin manager.
   *
   * @var \Drupal\smart_content\Decision\Storage\DecisionStorageInterface
   */
  protected $decisionStorageManager;

  /**
   * The decision plugin manager.
   *
   * @var \Drupal\smart_content\Decision\DecisionManager
   */
  protected $decisionManager;

  /**
   * Constructs a SmartCDNDecisionBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\smart_content\Decision\DecisionManager $decisionManager
   *   The decision plugin manager.
   * @param \Drupal\smart_content\Decision\Storage\DecisionStorageManager $decisionStorageManager
   *   The decision storage manager.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, DecisionManager $decisionManager, DecisionStorageManager $decisionStorageManager) {
    $this->decisionManager = $decisionManager;
    $this->decisionStorageManager = $decisionStorageManager;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    if (isset($configuration['decision_storage_serialized'])) {
      $this->decisionStorage = unserialize($configuration['decision_storage_serialized']);
      unset($configuration['decision_storage_serialized']);
    }
    if (!$this->getDecisionStorage()->hasDecision()) {
      $decision_stub = $this->decisionManager->createInstance('multiple_block_decision');
      $this->getDecisionStorage()->setDecision($decision_stub);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.smart_content.decision'),
      $container->get('plugin.manager.smart_content.decision_storage')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'label_display' => FALSE,
      'decision_storage' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = ['#markup' => ''];

    // Renderer for adding cacheable dependencies.
    $renderer = \Drupal::service('renderer');

    $storage = $this->getDecisionStorage();
    if ($storage->hasDecision()) {
      // Get decision from storage.
      $decision = $this->getDecisionStorage()->getDecision();

      // Get DecisionEvaluator service.
      $evaluator = \Drupal::service('smart_content_cdn.decision_evaluator');
      if ($segment_id = $evaluator->evaluate($decision)) {
        // Get reaction config for block data.
        $reaction_config = $decision->getReactions()->getConfiguration();

        if (array_key_exists($segment_id, $reaction_config)) {
          // Get list of blocks for decided segment.
          $segment_blocks = $reaction_config[$segment_id]['blocks'] ?? NULL;

          if (!empty($segment_blocks)) {
            // Get Block Plugin Collection for block instance data.
            $block_manager = \Drupal::service('plugin.manager.block');
            $blocks_collection = new BlockPluginCollection($block_manager, (array) $segment_blocks);

            // Get block instance ids.
            $instance_ids = $blocks_collection->getInstanceIds();
            if (!empty($instance_ids)) {
              $block_builds = [];

              foreach ($instance_ids as $instance_id) {
                // Get block instance.
                $block_instance = $blocks_collection->get($instance_id);

                // Determine if user has access to block.
                $user = \Drupal::currentUser();
                $access = $block_instance->access($user, TRUE);

                if ($access) {
                  // Get block render array.
                  $block_build = [
                    '#theme' => 'block',
                    '#attributes' => [],
                    '#configuration' => $block_instance->getConfiguration(),
                    '#plugin_id' => $block_instance->getPluginId(),
                    '#base_plugin_id' => $block_instance->getBaseId(),
                    '#derivative_plugin_id' => $block_instance->getDerivativeId(),
                    '#id' => $this->getPluginId(),
                    'content' => $block_instance->build(),
                  ];
                  // Set up cacheable dependency with decided segment id.
                  $renderer->addCacheableDependency($block_build, $segment_id);

                  // Add block build to build array.
                  $block_builds[] = $block_build;
                }
              }

              if (!empty($block_builds)) {
                $build = $block_builds;
              }
            }
          }
        }
      }
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $form['#process'][] = [$this, 'buildWidget'];
    $form['#attached']['library'][] = 'smart_content/form';
    return $form;
  }

  /**
   * Render API callback: builds the formatter settings elements.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state.
   * @param array $complete_form
   *   The complete form array.
   *
   * @return array
   *   The processed form element.
   */
  public function buildWidget(array &$element, FormStateInterface $form_state, array &$complete_form) {
    if ($decision_storage = DecisionStorageBase::getWidgetState($element['#array_parents'], $form_state)) {
      $this->decisionStorage = $decision_storage;
    }
    DecisionStorageBase::setWidgetState($element['#array_parents'], $form_state, $this->getDecisionStorage());
    SegmentSetConfigEntityForm::pluginForm($this->getDecisionStorage()
      ->getDecision(), $element, $form_state, ['decision']);
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $parents = $form['#array_parents'];
    if (end($parents) != 'settings') {
      $parents[] = 'settings';
    }

    if ($decision_storage = DecisionStorageBase::getWidgetState($parents, $form_state)) {
      $this->decisionStorage = $decision_storage;
    }
    if (isset($form['decision'])) {
      $element = $form;
    }
    else {
      $element = NestedArray::getValue($form, $parents);
    }

    if ($element) {
      // Get the decision from storage.
      $decision = $this->getDecisionStorage()->getDecision();
      if ($decision->getSegmentSetStorage()) {
        // Submit the form with the decision.
        SegmentSetConfigEntityForm::pluginFormSubmit($decision, $element, $form_state, ['decision']);
        // Set the decision to storage.
        $this->getDecisionStorage()->setDecision($decision);
      }
    }
    // Confirm validation is complete before serializing for save.
    if ($this->getDecisionStorage() && $form_state->isValidationComplete()) {
      $this->setConfigurationValue('decision_storage_serialized', serialize($this->getDecisionStorage()));
    }
  }

  /**
   * Get the decision storage plugin.
   *
   * @return \Drupal\smart_content\Decision\Storage\DecisionStorageInterface
   *   The decision storage plugin.
   */
  public function getDecisionStorage() {
    if (!isset($this->decisionStorage)) {
      $decision_storage_configuration = $this->getConfiguration()['decision_storage'];
      $storage_plugin_id = isset($decision_storage_configuration['plugin_id']) ? $decision_storage_configuration['plugin_id'] : 'config_entity';
      $this->decisionStorage = $this->decisionStorageManager
        ->createInstance($storage_plugin_id, (array) $decision_storage_configuration);
    }
    return $this->decisionStorage;
  }

  /**
   * Saves the block_content entity for this plugin.
   */
  public function saveBlockContent() {
    $this->getDecisionStorage()->save();
  }

}
