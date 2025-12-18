<?php

namespace Drupal\zinco_front\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;

/**
 * Provides a 'NewsCarousel' block.
 *
 * @Block(
 *   id = "zinco_front_news_carousel",
 *   admin_label = @Translation("News Carousel"),
 *   category = @Translation("Custom")
 * )
 */
class NewsCarousel extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The file URL generator.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * Constructs a new NewsCarousel object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator
   *   The file URL generator.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, FileUrlGeneratorInterface $file_url_generator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->fileUrlGenerator = $file_url_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('file_url_generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $news_items = [];

    try {
      // Query for recent noticia nodes.
      $node_storage = $this->entityTypeManager->getStorage('node');
      $query = $node_storage->getQuery()
        ->condition('type', 'noticia')
        ->condition('status', 1)
        ->sort('created', 'DESC')
        ->range(0, 10)
        ->accessCheck(TRUE);
      
      $nids = $query->execute();
      
      if (!empty($nids)) {
        $nodes = $node_storage->loadMultiple($nids);
        
        foreach ($nodes as $node) {
          $news_item = [
            'id' => $node->id(),
            'title' => $node->getTitle(),
            'url' => $node->toUrl()->toString(),
            'date' => \Drupal::service('date.formatter')->format($node->getCreatedTime(), 'custom', 'd/m/Y'),
            'timestamp' => $node->getCreatedTime(),
          ];
          
          // Get featured image.
          if ($node->hasField('field_imagen_destacada') && !$node->get('field_imagen_destacada')->isEmpty()) {
            $image_field = $node->get('field_imagen_destacada')->first();
            $file = $image_field->entity;
            if ($file) {
              $news_item['image_url'] = $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri());
              $news_item['image_alt'] = $image_field->alt ?? $node->getTitle();
            }
          }
          
          // Get content excerpt.
          if ($node->hasField('field_contenido_noticia') && !$node->get('field_contenido_noticia')->isEmpty()) {
            $content = $node->get('field_contenido_noticia')->value;
            // Strip HTML tags and limit to 150 characters.
            $plain_text = strip_tags($content);
            $news_item['excerpt'] = mb_strlen($plain_text) > 150 
              ? mb_substr($plain_text, 0, 150) . '...' 
              : $plain_text;
          } else {
            $news_item['excerpt'] = '';
          }
          
          $news_items[] = $news_item;
        }
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('zinco_front')->error('Error loading news for carousel: @message', ['@message' => $e->getMessage()]);
    }

    $build['#theme'] = 'zinco_news_carousel';
    $build['#news_items'] = $news_items;
    $build['#carousel_id'] = 'news-carousel-' . uniqid();
    $build['#cache'] = [
      'tags' => $this->entityTypeManager->getDefinition('node')->getListCacheTags(),
      'contexts' => ['url.path'],
      'max-age' => 3600, // Cache for 1 hour.
    ];
    $build['#attached']['library'][] = 'zinco_front/zinco-news-carousel';

    return $build;
  }

}
