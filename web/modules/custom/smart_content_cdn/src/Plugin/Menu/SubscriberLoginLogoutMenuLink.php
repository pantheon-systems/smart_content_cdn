<?php

namespace Drupal\smart_content_cdn\Plugin\Menu;

use Drupal\Core\Menu\MenuLinkDefault;
use Drupal\Core\Menu\StaticMenuLinkOverridesInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Kalamuna\SmartCDN\HeaderData;

/**
 * A menu link that shows "Log in" or "Log out" as appropriate.
 */
class SubscriberLoginLogoutMenuLink extends MenuLinkDefault {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a new LoginLogoutMenuLink.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Menu\StaticMenuLinkOverridesInterface $static_override
   *   The static override storage.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, StaticMenuLinkOverridesInterface $static_override, AccountInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $static_override);

    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('menu_link.static.overrides'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    // Get role header.
    $smart_content_cdn = new HeaderData();
    $role = $smart_content_cdn->parseHeader('Role');

    // Show "Log out" if user is subscriber or authenticated.
    if ($role == 'subscriber' || $this->currentUser->isAuthenticated()) {
      return $this->t('Log out');
    }
    // Otherwise show "Log in".
    else {
      return $this->t('Log in');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteName() {
    // Get role header.
    $smart_content_cdn = new HeaderData();
    $role = $smart_content_cdn->parseHeader('Role');

    // Use logout url if user is subscriber or authenticated.
    if ($role == 'subscriber' || $this->currentUser->isAuthenticated()) {
      return 'smart_content_cdn.logout';
    }
    // Otherwise use login url.
    else {
      return 'user.login';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['user.roles:authenticated'];
  }

}
