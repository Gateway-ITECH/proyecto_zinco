<?php

namespace Drupal\zinco_front\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\user\Entity\User;

/**
 * Provides a 'UserNotificationsBlock' block.
 *
 * @Block(
 *  id = "zinco_user_notifications_block",
 *  admin_label = @Translation("Zinco: User Notifications"),
 * )
 */
class UserNotificationsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs a new UserNotificationsBlock object.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    Connection $database,
    AccountInterface $current_user,
    DateFormatterInterface $date_formatter
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->database = $database;
    $this->currentUser = $current_user;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database'),
      $container->get('current_user'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    if ($this->currentUser->isAnonymous()) {
      return [];
    }

    $notifications = [];
    try {
      $query = $this->database->select('zinco_notifications', 'zn');
      $query->fields('zn');
      $query->condition('uid_receiver', $this->currentUser->id());
      $query->orderBy('created', 'DESC');
      $query->range(0, 3);
      $results = $query->execute()->fetchAll();

      foreach ($results as $row) {
        $sender = \Drupal\user\Entity\User::load($row->uid_sender);
        $notifications[] = [
          'message' => $row->notification,
          'created' => $this->dateFormatter->format($row->created, 'short'),
          'sender_name' => $sender ? $sender->getDisplayName() : $this->t('System'),
          'sender_picture' => $sender && !$sender->get('user_picture')->isEmpty() ? \Drupal::service('file_url_generator')->generateAbsoluteString($sender->get('user_picture')->entity->getFileUri()) : NULL,
        ];
      }
    } catch (\Exception $e) {
      \Drupal::logger('zinco_front')->error('Error loading notifications: @msg', ['@msg' => $e->getMessage()]);
    }

    return [
      '#theme' => 'zinco_user_notifications_block',
      '#notifications' => $notifications,
      '#cache' => [
        'contexts' => ['user'],
        'tags' => ['zinco_notifications_list'],
      ],
      '#attached' => [
        'library' => [
          'zinco_front/zinco-user-notifications',
        ],
      ],
    ];
  }

}
