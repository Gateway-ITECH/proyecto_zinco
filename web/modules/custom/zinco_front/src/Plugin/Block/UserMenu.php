<?php

namespace Drupal\zinco_front\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;

/**
 * Provides a 'UserMenu' block.
 *
 * @Block(
 *   id = "zinco_front_user_menu",
 *   admin_label = @Translation("User Menu"),
 *   category = @Translation("Custom")
 * )
 */
class UserMenu extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a new UserMenu object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountProxyInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
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
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $build['#theme'] = 'zinco_front_user_menu';
    $build['#logged_in'] = $this->currentUser->isAuthenticated();
    $build['#user_name'] = $this->currentUser->getDisplayName();
    $build['#cache']['tags'][] = 'user:' . $this->currentUser->id();
    $user_picture = NULL;
    if ($this->currentUser->isAuthenticated()) {
        $account = \Drupal\user\Entity\User::load($this->currentUser->id());
        if ($account && $account->user_picture && !$account->user_picture->isEmpty()) {
            $file = $account->user_picture->entity;
            if ($file) {
                $image_style = \Drupal::entityTypeManager()
                    ->getStorage('image_style')
                    ->load('thumbnail');
                if ($image_style) {
                    $user_picture = $image_style->buildUrl($file->getFileUri());
                }
                else {
                    $user_picture = file_create_url($file->getFileUri());
                }
            }
        }

    }
    $build['#user_picture'] = $user_picture;
    $build['#unread_notifications'] = 5; // Placeholder for unread notifications count.
    $build['#logout_url'] = Url::fromRoute('user.logout')->toString();

    // Attach a library for styling and basic JS functionality.
    $build['#attached']['library'][] = 'zinco_front/user-menu';

    return $build;
  }

}