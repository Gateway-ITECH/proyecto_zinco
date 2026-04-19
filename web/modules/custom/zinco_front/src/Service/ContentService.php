<?php

namespace Drupal\zinco_front\Service;

use GuzzleHttp\ClientInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;

/**
 * Service to handle content scraping and node creation.
 */
class ContentService
{

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
  public function scrapeUnicordobaNews($url = 'https://unicordoba.edu.co/noticias-historial/', $limit = 30)
  {
    $results = [
      'created' => 0,
      'errors' => [],
    ];

    try {
      $response = $this->httpClient->request('GET', $url);
      $html = (string) $response->getBody();

      // Use native PHP DOMDocument and XPath to avoid external dependencies like DomCrawler.
      $dom = new \DOMDocument();
      // Suppress errors due to malformed HTML.
      libxml_use_internal_errors(true);
      $dom->loadHTML($html);
      libxml_clear_errors();

      $xpath = new \DOMXPath($dom);

      // Search for articles.
      $articles = $xpath->query("//article");

      for ($i = 0; $i < $articles->length && $results['created'] < $limit; $i++) {
        $article = $articles->item($i);

        try {
          // Extract Title.
          $title_query = $xpath->query(".//h2[contains(@class, 'entry-title')]//a | .//h1[contains(@class, 'entry-title')]//a", $article);
          $title = $title_query->length ? trim($title_query->item(0)->textContent) : '';

          if (empty($title)) {
            continue;
          }

          // Check if already exists.
          $existing = $this->entityTypeManager->getStorage('node')->loadByProperties([
            'type' => 'noticia',
            'title' => $title,
          ]);
          if (!empty($existing)) {
            continue;
          }

          // Extract Content.
          $content_query = $xpath->query(".//div[contains(@class, 'ast-excerpt-container')] | .//div[contains(@class, 'entry-content')]", $article);
          $content = $content_query->length ? trim($content_query->item(0)->textContent) : '';

          // Extract Image.
          $img_query = $xpath->query(".//img[contains(@class, 'wp-post-image')] | .//img", $article);
          $img_url = '';
          if ($img_query->length) {
            $img_url = $img_query->item(0)->getAttribute('src');
          }

          $node_data = [
            'type' => 'noticia',
            'title' => $title,
            'field_contenido_noticia' => [
              'value' => $content,
              'format' => 'basic_html',
            ],
            'status' => 0,
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
      }

    } catch (\Exception $e) {
      $results['errors'][] = $e->getMessage();
      $this->loggerFactory->get('zinco_front')->error('Scraping failed: @msg', ['@msg' => $e->getMessage()]);
    }

    return $results;
  }

  /**
   * Downloads an image and creates a Drupal file entity.
   */
  protected function downloadAndCreateFile($url)
  {
    try {
      // Ensure URL is absolute.
      if (strpos($url, '//') === 0) {
        $url = 'https:' . $url;
      }

      $response = $this->httpClient->request('GET', $url);
      $data = (string) $response->getBody();

      $filename = basename(parse_url($url, PHP_URL_PATH));
      if (empty($filename) || strpos($filename, '.') === false) {
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
  public function getScrapeBatch($url = 'https://unicordoba.edu.co/noticias-historial/')
  {
    $batch = [
      'title' => t('Sincronizando contenidos (Noticias y Convocatorias)...'),
      'operations' => [
        [[get_class($this), 'processBatchItem'], [$url]],
        [[get_class($this), 'processInnovamosBatchItem'], ['https://www.innovamos.gov.co/']],
      ],
      'finished' => [get_class($this), 'finishBatch'],
    ];
    return $batch;
  }

  /**
   * Batch process callback.
   */
  public static function processBatchItem($url, &$context)
  {
    if (empty($context['sandbox'])) {
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['max'] = 1;
    }

    $service = \Drupal::service('zinco_front.content_service');
    $results = $service->scrapeUnicordobaNews($url, 5);

    $context['results'][] = [
      'type' => 'noticias',
      'created' => $results['created'],
    ];
    $context['message'] = t('Creadas @count noticias de Unicordoba.', ['@count' => $results['created']]);
    $context['finished'] = 1;
  }

  /**
   * Batch finish callback.
   */
  public static function finishBatch($success, $results, $operations)
  {
    if ($success) {
      $news_total = 0;
      $conv_total = 0;
      foreach ($results as $res) {
        if (isset($res['type'])) {
          if ($res['type'] == 'noticias') {
            $news_total += $res['created'];
          }
          if ($res['type'] == 'convocatorias') {
            $conv_total += $res['created'];
          }
        }
      }
      \Drupal::messenger()->addMessage(t('Sincronización completada. Noticias: @news, Convocatorias: @conv.', [
        '@news' => $news_total,
        '@conv' => $conv_total,
      ]));
    } else {
      \Drupal::messenger()->addError(t('El proceso de sincronización falló. Revisa los logs para más detalles.'));
    }
  }

  /**
   * Batch process callback for Innovamos.
   */
  public static function processInnovamosBatchItem($url, &$context)
  {
    $service = \Drupal::service('zinco_front.content_service');
    $results = $service->scrapeInnovamosConvocatorias($url, 10);

    $context['results'][] = [
      'type' => 'convocatorias',
      'created' => $results['created'],
    ];
    $context['message'] = t('Procesando convocatorias de Innovamos...');
    $context['finished'] = 1;
  }

  /**
   * Scrapes convocatorias from Innovamos.
   */
  public function scrapeInnovamosConvocatorias($url = 'https://www.innovamos.gov.co/api/v1/contents?benefits=&contentType=12&featured=false&hasNextPage=false&includeTags=true&keyword=&labels=&labelsSecond=&labelsThird=&locations=&orderBy=recent&organizations=&page=0&pageSize=10&publicPolitics=&showOnHome=true&targetUsers=', $limit = 10)
  {
    $results = [
      'created' => 0,
      'errors' => [],
    ];

    try {
      $response = $this->httpClient->request('GET', $url);
      $data = json_decode((string) $response->getBody(), TRUE);

      $this->loggerFactory->get('zinco_front')->info('Iniciando importación API Innovamos. Total elementos recibidos: @count', [
        '@count' => isset($data['items']) ? count($data['items']) : (is_array($data) ? count($data) : 0),
      ]);

      // The API might return items directly or inside an 'items' or 'contents' key.
      $items = $data['items'] ?? $data['contents'] ?? (is_array($data) ? $data : []);

      foreach ($items as $item) {
        if ($results['created'] >= $limit) {
          break;
        }

        try {
          $title = $item['name'] ?? $item['title'] ?? '';
          if (empty($title)) {
            continue;
          }

          // Check if already exists.
          $existing = $this->entityTypeManager->getStorage('node')->loadByProperties([
            'type' => 'convocatoria',
            'title' => $title,
          ]);
          if (!empty($existing)) {
            continue;
          }

          // Dates. API dates are often already formatted or in 'startingDateFormat'.
          $fecha_apertura = NULL;
          if (!empty($item['startingDateFormat'])) {
            $fecha_apertura = $this->parseSpanishDate($item['startingDateFormat']);
          }

          $fecha_cierre = NULL;
          if (!empty($item['closingDateFormat'])) {
            $fecha_cierre = $this->parseSpanishDate($item['closingDateFormat']);
          }

          // Link.
          $link = $item['friendlyUrl'] ?? '';
          if (!empty($link) && strpos($link, 'http') !== 0) {
            $link = 'https://www.innovamos.gov.co' . $link;
          }

          // Image.
          $img_url = $item['defaultImage'] ?? '';
          if (!empty($img_url) && strpos($img_url, 'http') !== 0) {
            $img_url = 'https://www.innovamos.gov.co' . $img_url;
          }

          $node_data = [
            'type' => 'convocatoria',
            'title' => $title,
            'field_publico_objetivo' => [
              'value' => $item['metaDescription'] ?? $item['description'] ?? '',
              'format' => 'basic_html',
            ],
            'field_fecha_de_apertura' => $fecha_apertura,
            'field_fecha_de_cierre' => $fecha_cierre,
            'field_mas_informacion' => $link,
            'status' => 0,
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

          $new_node = \Drupal\node\Entity\Node::create($node_data);
          $new_node->save();
          $results['created']++;

        }
        catch (\Exception $e) {
          $results['errors'][] = $e->getMessage();
          $this->loggerFactory->get('zinco_front')->error('Error procesando el ítem de la API Innovamos: @msg', ['@msg' => $e->getMessage()]);
        }
      }
    }
    catch (\Exception $e) {
      $results['errors'][] = $e->getMessage();
      $this->loggerFactory->get('zinco_front')->error('Fallo la consulta a la API de Innovamos: @msg', ['@msg' => $e->getMessage()]);
    }

    return $results;
  }

  /**
   * Helper to convert Spanish dates like "13 marzo 2026" to "2026-03-13".
   */
  protected function parseSpanishDate($date_string)
  {
    if (empty($date_string)) {
      return NULL;
    }

    $months = [
      'enero' => '01',
      'febrero' => '02',
      'marzo' => '03',
      'abril' => '04',
      'mayo' => '05',
      'junio' => '06',
      'julio' => '07',
      'agosto' => '08',
      'septiembre' => '09',
      'octubre' => '10',
      'noviembre' => '11',
      'diciembre' => '12',
    ];

    $parts = explode(' ', strtolower(trim($date_string)));
    if (count($parts) === 3) {
      $day = str_pad($parts[0], 2, '0', STR_PAD_LEFT);
      $month_name = $parts[1];
      $year = $parts[2];

      if (isset($months[$month_name])) {
        return "$year-" . $months[$month_name] . "-$day";
      }
    }
    return NULL;
  }


  //

}
