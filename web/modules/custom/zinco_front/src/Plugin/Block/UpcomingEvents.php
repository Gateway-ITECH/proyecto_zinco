<?php

namespace Drupal\zinco_front\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Provides an 'UpcomingEvents' block.
 *
 * @Block(
 *   id = "zinco_front_upcoming_events",
 *   admin_label = @Translation("Upcoming Events"),
 *   category = @Translation("Custom")
 * )
 */
class UpcomingEvents extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new UpcomingEvents object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $events = [];

    try {
      // Get current timestamp.
      $current_time = \Drupal::time()->getRequestTime();
      
      // Query for upcoming evento nodes.
      $node_storage = $this->entityTypeManager->getStorage('node');
      $query = $node_storage->getQuery()
        ->condition('type', 'evento')
        ->condition('status', 1)
        ->condition('field_fecha_del_evento', date('Y-m-d\TH:i:s', $current_time), '>=')
        ->sort('field_fecha_del_evento', 'ASC')
        ->range(0, 6)
        ->accessCheck(TRUE);
      
      $nids = $query->execute();
      
      if (!empty($nids)) {
        $nodes = $node_storage->loadMultiple($nids);
        
        foreach ($nodes as $node) {
          $event = [
            'id' => $node->id(),
            'title' => $node->getTitle(),
            'url' => $node->toUrl()->toString(),
          ];
          
          // Get event date.
          if ($node->hasField('field_fecha_del_evento') && !$node->get('field_fecha_del_evento')->isEmpty()) {
            $date_value = $node->get('field_fecha_del_evento')->value;
            $timestamp = strtotime($date_value);
            
            $event['date_full'] = \Drupal::service('date.formatter')->format($timestamp, 'custom', 'd/m/Y');
            $event['date_day'] = \Drupal::service('date.formatter')->format($timestamp, 'custom', 'd');
            $event['date_month'] = \Drupal::service('date.formatter')->format($timestamp, 'custom', 'M');
            $event['date_year'] = \Drupal::service('date.formatter')->format($timestamp, 'custom', 'Y');
            $event['timestamp'] = $timestamp;
            
            // Calculate days until event.
            $days_until = floor(($timestamp - $current_time) / 86400);
            $event['days_until'] = $days_until;
            
            if ($days_until == 0) {
              $event['time_label'] = 'Hoy';
            } elseif ($days_until == 1) {
              $event['time_label'] = 'Mañana';
            } elseif ($days_until <= 7) {
              $event['time_label'] = "En $days_until días";
            } else {
              $event['time_label'] = $event['date_full'];
            }
          }
          
          // Get event location.
          if ($node->hasField('field_lugar_del_evento') && !$node->get('field_lugar_del_evento')->isEmpty()) {
            $event['location'] = $node->get('field_lugar_del_evento')->value;
          } else {
            $event['location'] = '';
          }
          
          // Get event agenda/description.
          if ($node->hasField('field_agenda_evento') && !$node->get('field_agenda_evento')->isEmpty()) {
            $agenda = $node->get('field_agenda_evento')->value;
            // Strip HTML tags and limit to 120 characters.
            $plain_text = strip_tags($agenda);
            $event['description'] = mb_strlen($plain_text) > 120 
              ? mb_substr($plain_text, 0, 120) . '...' 
              : $plain_text;
          } else {
            $event['description'] = '';
          }
          
          $events[] = $event;
        }
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('zinco_front')->error('Error loading upcoming events: @message', ['@message' => $e->getMessage()]);
    }

    $build['#theme'] = 'zinco_upcoming_events';
    $build['#events'] = $events;
    $build['#cache'] = [
      'tags' => $this->entityTypeManager->getDefinition('node')->getListCacheTags(),
      'contexts' => ['url.path'],
      'max-age' => 3600, // Cache for 1 hour.
    ];
    $build['#attached']['library'][] = 'zinco_front/zinco-upcoming-events';

    return $build;
  }

}
