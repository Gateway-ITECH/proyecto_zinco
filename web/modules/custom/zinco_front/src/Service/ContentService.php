<?php

namespace Drupal\zinco_front\Service;

use GuzzleHttp\ClientInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\DomCrawler\Crawler;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;

/**
 * Service to handle content scraping and node creation.
 */
class ContentService {

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The file url generator.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Constructs a new ContentService object.
   */
  public function __construct(
    ClientInterface $http_client,
    EntityTypeManagerInterface $entity_type_manager,
    FileSystemInterface $file_system,
    FileUrlGeneratorInterface $file_url_generator,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->httpClient = $http_client;
    $this->entityTypeManager = $entity_type_manager;
    $this->fileSystem = $file_system;
    $this->fileUrlGenerator = $file_url_generator;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * Scrapes news from Unicordoba and creates noticia nodes.
   *
   * @param string $url
   *   The URL to scrape. Defaults to Unicordoba news history.
   * @param int $limit
   *   Maximum number of news to process.
   *
   * @return array
   *   Results of the operation.
   */
  public function scrapeUnicordobaNews($url = 'https://unicordoba.edu.co/noticias-historial/', $limit = 5) {
    $results = [
      'created' => 0,
      'errors' => [],
    ];

    try {
      $response = $this->httpClient->request('GET', $url);
      $html = (string) $response->getBody();
      $crawler = new Crawler($html);

      // Analyze structure based on observed markdown.
      // Usually WordPress Astra/Elementor uses articles.
      $items = $crawler->filter('article')->slice(0, $limit);

      if ($items->count() === 0) {
        // Fallback for some specific Unicordoba structures.
        $items = $crawler->filter('.ast-row .ast-article-post')->slice(0, $limit);
      }

      $items->each(function (Crawler $node) use (&$results) {
        try {
          $title_node = $node->filter('h1.entry-title a, h2.entry-title a, .ast-loop-title a')->first();
          $title = $title_node->count() ? trim($title_node->text()) : '';
          
          $content_node = $node->filter('.ast-excerpt-container, .entry-content, .ast-loop-excerpt')->first();
          $content = $content_node->count() ? trim($content_node->text()) : '';

          $img_node = $node->filter('img.wp-post-image, .post-thumb img, img')->first();
          $img_url = $img_node->count() ? $img_node->attr('src') : '';

          if (empty($title)) {
            return;
          }

          // Check if node already exists by title to avoid duplicates.
          $existing = $this->entityTypeManager->getStorage('node')->loadByProperties([
            'type' => 'noticia',
            'title' => $title,
          ]);

          if (!empty($existing)) {
            return;
          }

          $node_data = [
            'type' => 'noticia',
            'title' => $title,
            'field_contenido_noticia' => [
              'value' => $content,
              'format' => 'basic_html',
            ],
            'status' => 1,
            'uid' => 1,
          ];

          // Handle Image.
          if (!empty($img_url)) {
            $file = $this->downloadAndCreateFile($img_url);
            if ($file) {
              $node_data['field_imagen_destacada'] = [
                'target_id' => $file->id(),
                'alt' => $title,
              ];
            }
          }

          $new_node = Node::create($node_data);
          $new_node->save();
          $results['created']++;

        } catch (\Exception $e) {
          $results['errors'][] = $e->getMessage();
          $this->loggerFactory->get('zinco_front')->error('Error processing scraped item: @msg', ['@msg' => $e->getMessage()]);
        }
      });

    } catch (\Exception $e) {
      $results['errors'][] = $e->getMessage();
      $this->loggerFactory->get('zinco_front')->error('Scraping failed: @msg', ['@msg' => $e->getMessage()]);
    }

    return $results;
  }

  /**
   * Downloads an image and creates a Drupal file entity.
   */
  protected function downloadAndCreateFile($url) {
    try {
      $response = $this->httpClient->request('GET', $url);
      $data = (string) $response->getBody();
      
      $filename = basename(parse_url($url, PHP_URL_PATH));
      if (empty($filename)) {
        $filename = 'news_image_' . time() . '.jpg';
      }

      $directory = 'public://noticias/' . date('Y-m');
      $this->fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);
      
      $destination = $directory . '/' . $filename;
      $file_uri = $this->fileSystem->saveData($data, $destination, FileSystemInterface::EXISTS_REPLACE);

      if ($file_uri) {
        $file = File::create([
          'uri' => $file_uri,
          'status' => 1,
          'uid' => 1,
        ]);
        $file->save();
        return $file;
      }
    } catch (\Exception $e) {
      $this->loggerFactory->get('zinco_front')->error('Failed to download image @url: @msg', ['@url' => $url, '@msg' => $e->getMessage()]);
    }
    return NULL;
  }

  /**
   * Gets a batch definition for scraping.
   */
  public function getScrapeBatch($url = 'https://unicordoba.edu.co/noticias-historial/') {
    $batch = [
      'title' => t('Scraping news from Unicordoba...'),
      'operations' => [
        [[get_class($this), 'processBatchItem'], [$url]],
      ],
      'finished' => [get_class($this), 'finishBatch'],
    ];
    return $batch;
  }

  /**
   * Batch process callback.
   */
  public static function processBatchItem($url, &$context) {
    if (empty($context['sandbox'])) {
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['max'] = 1; // We process in one go for 5 items, or could paginate.
    }

    $service = \Drupal::service('zinco_front.content_service');
    $results = $service->scrapeUnicordobaNews($url, 5);
    
    $context['results'][] = $results;
    $context['message'] = t('Created @count news.', ['@count' => $results['created']]);
    $context['finished'] = 1;
  }

  /**
   * Batch finish callback.
   */
  public static function finishBatch($success, $results, $operations) {
    if ($success) {
      $total = 0;
      foreach ($results as $res) {
        $total += $res['created'];
      }
      \Drupal::messenger()->addMessage(t('Success! Created @total news.', ['@total' => $total]));
    } else {
      \Drupal::messenger()->addError(t('Scraping process failed. Check logs.'));
    }
  }

}
