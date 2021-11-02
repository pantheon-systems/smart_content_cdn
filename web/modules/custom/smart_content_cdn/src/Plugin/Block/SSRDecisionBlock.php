<?php

namespace Drupal\smart_content_cdn\Plugin\Block;

use Drupal\smart_content_block\Plugin\Block\DecisionBlock;
use Drupal\smart_content_block\BlockPluginCollection;

/**
 * Provides a 'SmartBlock' block.
 *
 * @Block(
 *  id = "smart_content_cdn_ssr_decision_block",
 *  admin_label = @Translation("SSR Decision Block"),
 * )
 */
class SSRDecisionBlock extends DecisionBlock {

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

}
