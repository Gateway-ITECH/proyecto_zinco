<?php

namespace Drupal\zinco_front\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Provides an 'ActiveCalls' block.
 *
 * @Block(
 *   id = "zinco_front_active_calls",
 *   admin_label = @Translation("Active Calls"),
 *   category = @Translation("Custom")
 * )
 */
class ActiveCalls extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new ActiveCalls object.
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
    $calls = [];

    try {
      // Get current timestamp.
      $current_time = \Drupal::time()->getRequestTime();
      $current_date = date('Y-m-d\TH:i:s', $current_time);
      
      // Query for active convocatoria nodes.
      // Active = fecha_apertura <= now <= fecha_cierre
      $node_storage = $this->entityTypeManager->getStorage('node');
      $query = $node_storage->getQuery()
        ->condition('type', 'convocatoria')
        ->condition('status', 1)
        ->condition('field_fecha_de_apertura', $current_date, '<=')
        ->condition('field_fecha_de_cierre', $current_date, '>=')
        ->sort('field_fecha_de_cierre', 'ASC')
        ->range(0, 6)
        ->accessCheck(TRUE);
      
      $nids = $query->execute();
      
      if (!empty($nids)) {
        $nodes = $node_storage->loadMultiple($nids);
        
        foreach ($nodes as $node) {
          $call = [
            'id' => $node->id(),
            'title' => $node->getTitle(),
            'url' => $node->toUrl()->toString(),
          ];
          
          // Get opening date.
          if ($node->hasField('field_fecha_de_apertura') && !$node->get('field_fecha_de_apertura')->isEmpty()) {
            $opening_date = $node->get('field_fecha_de_apertura')->value;
            $opening_timestamp = strtotime($opening_date);
            $call['opening_date'] = \Drupal::service('date.formatter')->format($opening_timestamp, 'custom', 'd/m/Y');
          }
          
          // Get closing date.
          if ($node->hasField('field_fecha_de_cierre') && !$node->get('field_fecha_de_cierre')->isEmpty()) {
            $closing_date = $node->get('field_fecha_de_cierre')->value;
            $closing_timestamp = strtotime($closing_date);
            
            $call['closing_date'] = \Drupal::service('date.formatter')->format($closing_timestamp, 'custom', 'd/m/Y');
            $call['closing_day'] = \Drupal::service('date.formatter')->format($closing_timestamp, 'custom', 'd');
            $call['closing_month'] = \Drupal::service('date.formatter')->format($closing_timestamp, 'custom', 'M');
            
            // Calculate days remaining.
            $days_remaining = floor(($closing_timestamp - $current_time) / 86400);
            $call['days_remaining'] = $days_remaining;
            
            if ($days_remaining == 0) {
              $call['urgency_label'] = 'Cierra hoy';
              $call['urgency_class'] = 'urgent';
            } elseif ($days_remaining == 1) {
              $call['urgency_label'] = 'Cierra mañana';
              $call['urgency_class'] = 'urgent';
            } elseif ($days_remaining <= 7) {
              $call['urgency_label'] = "Quedan $days_remaining días";
              $call['urgency_class'] = 'warning';
            } else {
              $call['urgency_label'] = "Quedan $days_remaining días";
              $call['urgency_class'] = 'normal';
            }
          }
          
          // Get call type.
          if ($node->hasField('field_tipo_de_convocatoria') && !$node->get('field_tipo_de_convocatoria')->isEmpty()) {
            $type_term = $node->get('field_tipo_de_convocatoria')->entity;
            if ($type_term) {
              $call['type'] = $type_term->label();
            }
          } else {
            $call['type'] = '';
          }
          
          // Get body/description if available.
          if ($node->hasField('body') && !$node->get('body')->isEmpty()) {
            $body = $node->get('body')->value;
            $plain_text = strip_tags($body);
            $call['description'] = mb_strlen($plain_text) > 150 
              ? mb_substr($plain_text, 0, 150) . '...' 
              : $plain_text;
          } else {
            $call['description'] = '';
          }
          
          $calls[] = $call;
        }
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('zinco_front')->error('Error loading active calls: @message', ['@message' => $e->getMessage()]);
    }

    $build['#theme'] = 'zinco_active_calls';
    $build['#calls'] = $calls;
    $build['#cache'] = [
      'tags' => $this->entityTypeManager->getDefinition('node')->getListCacheTags(),
      'contexts' => ['url.path'],
      'max-age' => 3600, // Cache for 1 hour.
    ];
    $build['#attached']['library'][] = 'zinco_front/zinco-active-calls';

    return $build;
  }

}
