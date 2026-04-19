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
      $data = '';
      $filename = '';

      if (strpos($url, 'data:image/') === 0) {
        // Handle Base64 data URI.
        if (preg_match('/^data:image\/(\w+);base64,(.*)$/', $url, $matches)) {
          $extension = $matches[1];
          $data = base64_decode($matches[2]);
          $filename = 'base64_image_' . time() . '_' . rand(100, 999) . '.' . $extension;
        } else {
          return NULL;
        }
      } else {
        // Handle Normal URL.
        if (strpos($url, '//') === 0) {
          $url = 'https:' . $url;
        }

        $response = $this->httpClient->request('GET', $url);
        $data = (string) $response->getBody();

        $filename = basename(parse_url($url, PHP_URL_PATH));
        if (empty($filename) || strpos($filename, '.') === false) {
          $filename = 'news_image_' . time() . '.jpg';
        }
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
        [[get_class($this), 'processInnovamosBatchItem'], ['https://www.innovamos.gov.co/api/v1/contents?benefits=&contentType=12&featured=false&hasNextPage=false&includeTags=true&keyword=&labels=&labelsSecond=&labelsThird=&locations=&orderBy=recent&organizations=&page=0&pageSize=10&publicPolitics=&showOnHome=true&targetUsers=']],
        [[get_class($this), 'processCCMonteriaBatchItem'], ['https://ccmonteria.org.co/noticias']],
        [[get_class($this), 'processMincienciasBatchItem'], ['https://minciencias.gov.co/plan-convocatorias-actei-2025-2026-0']],
        [[get_class($this), 'processInnpulsaBatchItem'], ['https://source-preserve.emergent.host/api/convocatorias?active_only=true']],
        [[get_class($this), 'processIcetexBatchItem'], ['https://web.icetex.gov.co/becas/becas-para-estudios-en-el-exterior/becas-vigentes']],
        [[get_class($this), 'processColfuturoBatchItem'], ['https://www.colfuturo.org/noticias']],
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
      $cc_news_total = 0;
      $min_conv_total = 0;
      $inn_conv_total = 0;
      $ice_conv_total = 0;
      $col_news_total = 0;
      foreach ($results as $res) {
        if (isset($res['type'])) {
          if ($res['type'] == 'noticias') {
            $news_total += $res['created'];
          }
          if ($res['type'] == 'convocatorias') {
            $conv_total += $res['created'];
          }
          if ($res['type'] == 'noticias_cc') {
            $cc_news_total += $res['created'];
          }
          if ($res['type'] == 'convocatorias_min') {
            $min_conv_total += $res['created'];
          }
          if ($res['type'] == 'convocatorias_innpulsa') {
            $inn_conv_total += $res['created'];
          }
          if ($res['type'] == 'convocatorias_icetex') {
            $ice_conv_total += $res['created'];
          }
          if ($res['type'] == 'noticias_colfuturo') {
            $col_news_total += $res['created'];
          }
        }
      }
      \Drupal::messenger()->addMessage(t('Sincronización completada. Noticias: @news, Noticias CC: @cc, Colfuturo: @col, Convocatorias Innovamos: @conv, Minciencias: @min, Innpulsa: @inn, ICETEX: @ice.', [
        '@news' => $news_total,
        '@cc' => $cc_news_total,
        '@col' => $col_news_total,
        '@conv' => $conv_total,
        '@min' => $min_conv_total,
        '@inn' => $inn_conv_total,
        '@ice' => $ice_conv_total,
      ]));
    } else {
      \Drupal::messenger()->addError(t('El proceso de sincronización falló. Revisa los logs para más detalles.'));
    }
  }

  /**
   * Batch process callback for Minciencias.
   */
  public static function processMincienciasBatchItem($url, &$context)
  {
    $service = \Drupal::service('zinco_front.content_service');
    $results = $service->scrapeMincienciasConvocatorias($url, 20);

    $context['results'][] = [
      'type' => 'convocatorias_min',
      'created' => $results['created'],
    ];
    $context['message'] = t('Procesando plan de convocatorias de Minciencias...');
    $context['finished'] = 1;
  }

  /**
   * Batch process callback for Innpulsa.
   */
  public static function processInnpulsaBatchItem($url, &$context)
  {
    $service = \Drupal::service('zinco_front.content_service');
    $results = $service->scrapeInnpulsaConvocatorias($url, 20);

    $context['results'][] = [
      'type' => 'convocatorias_innpulsa',
      'created' => $results['created'],
    ];
    $context['message'] = t('Procesando convocatorias de Innpulsa...');
    $context['finished'] = 1;
  }

  /**
   * Batch process callback for ICETEX.
   */
  public static function processIcetexBatchItem($url, &$context)
  {
    $logger = \Drupal::logger('zinco_front');
    $logger->info('Iniciando batch de ICETEX para URL: @url', ['@url' => $url]);

    $service = \Drupal::service('zinco_front.content_service');
    $results = $service->scrapeIcetexConvocatorias($url, 10);

    $context['results'][] = [
      'type' => 'convocatorias_icetex',
      'created' => $results['created'],
    ];

    $logger->info('Batch de ICETEX finalizado. Creadas @count convocatorias.', ['@count' => $results['created']]);
    if (!empty($results['errors'])) {
      foreach ($results['errors'] as $error) {
        $logger->error('Error en batch ICETEX: @error', ['@error' => $error]);
      }
    }

    $context['message'] = t('Procesando becas vigentes de ICETEX (@count creadas)...', ['@count' => $results['created']]);
    $context['finished'] = 1;
  }

  /**
   * Batch process callback for CC Montería.
   */
  public static function processCCMonteriaBatchItem($url, &$context)
  {
    $service = \Drupal::service('zinco_front.content_service');
    $results = $service->scrapeCCMonteriaNews($url, 10);

    $context['results'][] = [
      'type' => 'noticias_cc',
      'created' => $results['created'],
    ];
    $context['message'] = t('Procesando noticias de la Cámara de Comercio de Montería...');
    $context['finished'] = 1;
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
   * Batch process callback for Colfuturo.
   */
  public static function processColfuturoBatchItem($url, &$context)
  {
    $service = \Drupal::service('zinco_front.content_service');
    $results = $service->scrapeColfuturoNews($url, 10);

    $context['results'][] = [
      'type' => 'noticias_colfuturo',
      'created' => $results['created'],
    ];
    $context['message'] = t('Procesando noticias de Colfuturo...');
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
      $json_string = (string) $response->getBody();
      $data = json_decode($json_string, TRUE);

      if (json_last_error() !== JSON_ERROR_NONE) {
        $this->loggerFactory->get('zinco_front')->error('Error decodificando JSON de Innovamos: @error. Contenido: @content', [
          '@error' => json_last_error_msg(),
          '@content' => substr($json_string, 0, 500),
        ]);
        return $results;
      }

      $this->loggerFactory->get('zinco_front')->info('Iniciando importación API JSON Innovamos. Datos recibidos: @data', ['@data' => substr($json_string, 0, 1000)]);

      // The items are in the 'results' key.
      $items = $data['results'] ?? [];

      foreach ($items as $item) {
        if ($results['created'] >= $limit) {
          break;
        }

        try {
          $title = $item['name'] ?? '';
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

          // Dates. Format: Y-m-d (Date only field).
          $fecha_apertura = !empty($item['startingDate']) ? date('Y-m-d', strtotime($item['startingDate'])) : NULL;
          $fecha_cierre = !empty($item['closingDate']) ? date('Y-m-d', strtotime($item['closingDate'])) : NULL;

          // Link. Using FriendlyName as requested.
          $link = '';
          if (!empty($item['friendlyName'])) {
            $link = 'https://www.innovamos.gov.co/instrumentos/' . $item['friendlyName'];
          } elseif (!empty($item['friendlyUrl'])) {
            $link = strpos($item['friendlyUrl'], 'http') === 0 ? $item['friendlyUrl'] : 'https://www.innovamos.gov.co' . $item['friendlyUrl'];
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
              'value' => $item['metadescription'] ?? $item['metaDescription'] ?? '',
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

        } catch (\Exception $e) {
          $results['errors'][] = $e->getMessage();
          $this->loggerFactory->get('zinco_front')->error('Error procesando ítem de Innovamos: @msg', ['@msg' => $e->getMessage()]);
        }
      }
    } catch (\Exception $e) {
      $results['errors'][] = $e->getMessage();
      $this->loggerFactory->get('zinco_front')->error('Fallo la consulta API de Innovamos: @msg', ['@msg' => $e->getMessage()]);
    }

    return $results;
  }

  /**
   * Helper to convert Spanish dates like "13 marzo 2026" to "2026-03-13".
   */
  protected function parseSpanishDate($dateString)
  {
    if (empty($dateString)) {
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
      'diciembre' => '12'
    ];

    $dateString = mb_strtolower(trim($dateString));

    // Format: "13 marzo 2026", "13 de marzo de 2026", or "Jueves 13 de marzo de 2026".
    if (preg_match('/(?:[a-z]+,?\s+)?(\d{1,2})\s+(?:de\s+)?([a-z]+)\s+(?:de\s+)?(\d{4})/i', $dateString, $matches)) {
      $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
      $month_name = $matches[2];
      $year = $matches[3];

      if (isset($months[$month_name])) {
        return "$year-" . $months[$month_name] . "-$day";
      }
    }

    // Format: "Marzo 12, 2026" or "Jueves, Marzo 12, 2026".
    if (preg_match('/(?:[a-z]+,?\s+)?([a-z]+)\s+(\d{1,2}),\s+(\d{4})/i', $dateString, $matches)) {
      $month_name = $matches[1];
      $day = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
      $year = $matches[3];

      if (isset($months[$month_name])) {
        return "$year-" . $months[$month_name] . "-$day";
      }
    }

    // Format: "Mar. 24 / 2026" (Colfuturo) or similar.
    if (preg_match('/([a-z]{3})\.?\s+(\d{1,2})\s*[\/\-]\s*(\d{4})/i', $dateString, $matches)) {
      $month_abbr = mb_strtolower(trim($matches[1], '.'));
      $day = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
      $year = $matches[3];

      $abbr_map = [
        'ene' => '01',
        'feb' => '02',
        'mar' => '03',
        'abr' => '04',
        'may' => '05',
        'jun' => '06',
        'jul' => '07',
        'ago' => '08',
        'sep' => '09',
        'oct' => '10',
        'nov' => '11',
        'dic' => '12',
        'jan' => '01',
        'apr' => '04',
        'aug' => '08',
        'dec' => '12'
      ];

      if (isset($abbr_map[$month_abbr])) {
        return "$year-" . $abbr_map[$month_abbr] . "-$day";
      }
    }

    return NULL;
  }


  /**
   * Scrapes news from CC Montería.
   */
  public function scrapeCCMonteriaNews($url = 'https://ccmonteria.org.co/noticias', $limit = 10)
  {
    $results = [
      'created' => 0,
      'errors' => [],
    ];

    try {
      $response = $this->httpClient->request('GET', $url, [
        'headers' => [
          'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        ],
      ]);
      $html = (string) $response->getBody();
      $status_code = $response->getStatusCode();

      $this->loggerFactory->get('zinco_front')->info('Respuesta CC Montería - Status: @status, Longitud: @len', [
        '@status' => $status_code,
        '@len' => strlen($html),
      ]);

      $dom = new \DOMDocument();
      libxml_use_internal_errors(true);
      $dom->loadHTML($html);
      libxml_clear_errors();

      $xpath = new \DOMXPath($dom);

      $this->loggerFactory->get('zinco_front')->info('Iniciando scraping CC Montería. Longitud HTML: @len', ['@len' => strlen($html)]);

      // Selectors based on analysis: a.box-newsreel
      $articles = $xpath->query("//a[contains(@class, 'box-newsreel')]");
      $this->loggerFactory->get('zinco_front')->info('Noticias encontradas en CC Montería: @count', ['@count' => $articles->length]);

      for ($i = 0; $i < $articles->length && $results['created'] < $limit; $i++) {
        $article = $articles->item($i);

        try {
          // Extract Title.
          $title_query = $xpath->query(".//div[contains(@class, 'title-newsreel-index')]", $article);
          $title = $title_query->length ? trim($title_query->item(0)->textContent) : '';

          // Link.
          $link = $article->getAttribute('href');
          if (!empty($link) && strpos($link, 'http') !== 0) {
            $link = 'https://ccmonteria.org.co' . $link;
          }

          // Content from 'title' attribute.
          $content = $article->getAttribute('title');

          if (empty($title)) {
            $this->loggerFactory->get('zinco_front')->warning('CC Montería: Título vacío. Saltando elemento.');
            continue;
          }

          // Check if already exists.
          $existing = $this->entityTypeManager->getStorage('node')->loadByProperties([
            'type' => 'noticia',
            'title' => $title,
          ]);
          if (!empty($existing)) {
            $this->loggerFactory->get('zinco_front')->info('CC Montería: Noticia ya existe (saltando): @title', ['@title' => $title]);
            continue;
          }

          // Extract Image from background-image style.
          $img_url = '';
          $img_div_query = $xpath->query(".//div[contains(@class, 'bg-image-newsreel')]", $article);
          if ($img_div_query->length) {
            $style = $img_div_query->item(0)->getAttribute('style');
            if (preg_match('/url\([\'"]?(.*?)[\'"]?\)/', $style, $matches)) {
              $img_url = $matches[1];
              if (strpos($img_url, 'http') !== 0) {
                $img_url = 'https://ccmonteria.org.co' . $img_url;
              }
            }
          }

          $this->loggerFactory->get('zinco_front')->debug('Datos extraídos CC Montería: Título: @title, Link: @link, Imagen: @img', [
            '@title' => $title,
            '@link' => $link,
            '@img' => $img_url,
          ]);

          $node_data = [
            'type' => 'noticia',
            'title' => $title,
            'field_contenido_noticia' => [
              'value' => $content . '<br><br><a href="' . $link . '" target="_blank">Ver más</a>',
              'format' => 'basic_html',
            ],
            'status' => 0, // MODO BORRADOR
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
          $this->loggerFactory->get('zinco_front')->info('CC Montería: Noticia guardada con éxito: @title (ID: @id)', [
            '@title' => $title,
            '@id' => $new_node->id(),
          ]);
          $results['created']++;

        } catch (\Exception $e) {
          $results['errors'][] = $e->getMessage();
          $this->loggerFactory->get('zinco_front')->error('Error procesando noticia CC Montería: @msg', ['@msg' => $e->getMessage()]);
        }
      }
    } catch (\Exception $e) {
      $results['errors'][] = $e->getMessage();
      $this->loggerFactory->get('zinco_front')->error('Fallo el scraping de CC Montería: @msg', ['@msg' => $e->getMessage()]);
    }

    return $results;
  }

  /**
   * Scrapes convocatorias from Minciencias.
   */
  public function scrapeMincienciasConvocatorias($url = 'https://minciencias.gov.co/plan-convocatorias-actei-2025-2026-0', $limit = 20)
  {
    $results = [
      'created' => 0,
      'errors' => [],
    ];

    try {
      $response = $this->httpClient->request('GET', $url);
      $html = (string) $response->getBody();

      $dom = new \DOMDocument();
      libxml_use_internal_errors(true);
      $dom->loadHTML($html);
      libxml_clear_errors();

      $xpath = new \DOMXPath($dom);

      // Selector identified: table inside the body.
      $rows = $xpath->query("//div[contains(@class, 'field-name-body')]//table//tbody/tr");

      foreach ($rows as $row) {
        if ($results['created'] >= $limit) {
          break;
        }

        try {
          // Date is in column 5 (Opening Date).
          $date_query = $xpath->query(".//td[5]", $row);
          $date_text = $date_query->length ? trim($date_query->item(0)->textContent) : '';

          // Filter: Opening date must be in 2026 or more.
          $year_found = false;
          if (preg_match('/20\d{2}/', $date_text, $yr_matches)) {
            $year = (int) $yr_matches[0];
            if ($year >= 2026) {
              $year_found = true;
            }
          }

          if (!$year_found) {
            continue;
          }

          // Title / Link (Column 2).
          $title_query = $xpath->query(".//td[2]", $row);
          if (!$title_query->length) {
            continue;
          }
          $title = trim($title_query->item(0)->textContent);

          $link_query = $xpath->query(".//a", $title_query->item(0));
          $link = $link_query->length ? $link_query->item(0)->getAttribute('href') : '';
          if (!empty($link) && strpos($link, 'http') !== 0) {
            $link = 'https://minciencias.gov.co' . $link;
          }

          // Description (Column 3).
          $desc_query = $xpath->query(".//td[3]", $row);
          $desc = $desc_query->length ? trim($desc_query->item(0)->textContent) : '';

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

          $node_data = [
            'type' => 'convocatoria',
            'title' => $title,
            'field_publico_objetivo' => [
              'value' => $desc,
              'format' => 'basic_html',
            ],
            'field_mas_informacion' => $link,
            'status' => 0,
            'uid' => 1,
          ];

          // Try to set dates from detail page.
          if (!empty($link)) {
            $detail_dates = $this->scrapeMincienciasDetailDates($link);
            if ($detail_dates['apertura']) {
              $node_data['field_fecha_de_apertura'] = $detail_dates['apertura'];
            }
            if ($detail_dates['cierre']) {
              $node_data['field_fecha_de_cierre'] = $detail_dates['cierre'];
            }
          }

          // Fallback opening date from main table if not found in detail.
          if (empty($node_data['field_fecha_de_apertura'])) {
            $fecha = $this->parseSpanishDate($date_text);
            if ($fecha) {
              $node_data['field_fecha_de_apertura'] = $fecha;
            }
          }

          $new_node = \Drupal\node\Entity\Node::create($node_data);
          $new_node->save();
          $results['created']++;

        } catch (\Exception $e) {
          $results['errors'][] = $e->getMessage();
          $this->loggerFactory->get('zinco_front')->error('Error procesando convocatoria Minciencias: @msg', ['@msg' => $e->getMessage()]);
        }
      }
    } catch (\Exception $e) {
      $results['errors'][] = $e->getMessage();
      $this->loggerFactory->get('zinco_front')->error('Fallo el scraping de Minciencias: @msg', ['@msg' => $e->getMessage()]);
    }

    return $results;
  }

  /**
   * Scrapes dates from the detail page of a Minciencias convocatoria.
   */
  protected function scrapeMincienciasDetailDates($url)
  {
    $dates = [
      'apertura' => NULL,
      'cierre' => NULL,
    ];

    try {
      $response = $this->httpClient->request('GET', $url);
      $html = (string) $response->getBody();

      $dom = new \DOMDocument();
      @$dom->loadHTML($html);
      $xpath = new \DOMXPath($dom);

      // Apertura Date.
      $ap_query = $xpath->query('//table//tr[td[1][contains(normalize-space(), "Apertura")] or th[1][contains(normalize-space(), "Apertura")]]/td[2]');
      if ($ap_query->length) {
        $dates['apertura'] = $this->parseSpanishDate($ap_query->item(0)->textContent);
      }

      // Cierre Date.
      $ci_query = $xpath->query('//table//tr[td[1][contains(normalize-space(), "Cierre")] or th[1][contains(normalize-space(), "Cierre")]]/td[2]');
      if ($ci_query->length) {
        $dates['cierre'] = $this->parseSpanishDate($ci_query->item(0)->textContent);
      }
    } catch (\Exception $e) {
      $this->loggerFactory->get('zinco_front')->error('Error scraping Minciencias detail (@url): @msg', [
        '@url' => $url,
        '@msg' => $e->getMessage(),
      ]);
    }

    return $dates;
  }

  //

  /**
   * Scrapes convocatorias from Innpulsa JSON API.
   */
  public function scrapeInnpulsaConvocatorias($url, $limit = 20)
  {
    $results = [
      'created' => 0,
      'errors' => [],
    ];

    try {
      $response = $this->httpClient->request('GET', $url);
      $json_string = (string) $response->getBody();
      $data = json_decode($json_string, TRUE);

      if (json_last_error() !== JSON_ERROR_NONE) {
        $this->loggerFactory->get('zinco_front')->error('Error decoding Innpulsa JSON: @msg', ['@msg' => json_last_error_msg()]);
        return $results;
      }

      // If the data is nested under a key, adjust here. The screenshot shows a list at root or similar.
      $items = $data;
      if (isset($data['results']))
        $items = $data['results'];
      elseif (isset($data['data']))
        $items = $data['data'];

      foreach ($items as $item) {
        if ($results['created'] >= $limit) {
          break;
        }

        try {
          $title = $item['title'] ?? '';
          if (empty($title))
            continue;

          // Check if already exists.
          $existing = $this->entityTypeManager->getStorage('node')->loadByProperties([
            'type' => 'convocatoria',
            'title' => $title,
          ]);
          if (!empty($existing))
            continue;

          // Process Target Audience + Description + Purpose + Benefits.
          $audience = $item['target_audience'] ?? '';
          $description = $item['description'] ?? '';
          $purpose = $item['purpose'] ?? '';
          $benefits = $item['benefits'] ?? '';

          $full_content = "<strong>Descripción:</strong><br>$description<br><br>";
          $full_content .= "<strong>Público Objetivo:</strong><br>$audience<br><br>";
          $full_content .= "<strong>Propósito:</strong><br>$purpose<br><br>";
          $full_content .= "<strong>Beneficios:</strong><br>$benefits";

          $node_data = [
            'type' => 'convocatoria',
            'title' => $title,
            'field_publico_objetivo' => [
              'value' => $full_content,
              'format' => 'basic_html',
            ],
            'field_fecha_de_apertura' => $item['start_date'] ?? NULL,
            'field_fecha_de_cierre' => $item['end_date'] ?? NULL,
            'field_mas_informacion' => $item['registration_url'] ?? '',
            'status' => 0, // DRAFT
            'uid' => 1,
          ];

          // Handle Image (Base64 or URL).
          $img_url = $item['image_url'] ?? '';
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

        } catch (\Exception $e) {
          $results['errors'][] = $e->getMessage();
          $this->loggerFactory->get('zinco_front')->error('Error processing Innpulsa item: @msg', ['@msg' => $e->getMessage()]);
        }
      }
    } catch (\Exception $e) {
      $results['errors'][] = $e->getMessage();
      $this->loggerFactory->get('zinco_front')->error('Innpulsa API call failed: @msg', ['@msg' => $e->getMessage()]);
    }

    return $results;
  }

  /**
   * Scrapes scholarships (becas) from ICETEX.
   */
  public function scrapeIcetexConvocatorias($url = 'https://web.icetex.gov.co/becas/becas-para-estudios-en-el-exterior/becas-vigentes', $limit = 10)
  {
    $results = [
      'created' => 0,
      'errors' => [],
    ];

    try {
      $response = $this->httpClient->request('GET', $url, [
        'headers' => [
          'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        ],
      ]);
      $html = (string) $response->getBody();

      $dom = new \DOMDocument();
      @$dom->loadHTML($html);
      $xpath = new \DOMXPath($dom);

      // The container is usually div.media
      $items = $xpath->query("//div[contains(@class, 'media')]");
      $this->loggerFactory->get('zinco_front')->info('Becas encontradas en ICETEX: @count', ['@count' => $items->length]);

      for ($i = 0; $i < $items->length && $results['created'] < $limit; $i++) {
        $item_node = $items->item($i);

        // Find the link
        $link_query = $xpath->query(".//a[contains(@class, 'lnk_art_nuevo_cred')]", $item_node);
        if (!$link_query->length)
          continue;

        $link_node = $link_query->item(0);
        $href = $link_node->getAttribute('href');
        if (empty($href))
          continue;

        if (strpos($href, 'http') !== 0) {
          $href = 'https://web.icetex.gov.co' . $href;
        }

        // Title from a tag text (cleaning the span >)
        $title = trim($link_node->textContent);
        $title = preg_replace('/\s*>\s*$/', '', $title);

        // Image from img tag
        $image_url = '';
        $img_query = $xpath->query(".//img[contains(@class, 'image_article_nuevo_cred')]", $item_node);
        if ($img_query->length) {
          $image_url = $img_query->item(0)->getAttribute('src');
          if (!empty($image_url) && strpos($image_url, 'http') !== 0) {
            $image_url = 'https://web.icetex.gov.co' . $image_url;
          }
        }

        try {
          $this->loggerFactory->get('zinco_front')->debug('Procesando beca ICETEX @i: @url', ['@i' => $i, '@url' => $href]);

          $detail_results = $this->scrapeIcetexDetail($href);
          if (!$detail_results) {
            continue;
          }

          $final_title = !empty($title) ? $title : $detail_results['title'];

          // Check if already exists.
          $existing = $this->entityTypeManager->getStorage('node')->loadByProperties([
            'type' => 'convocatoria',
            'title' => $final_title,
          ]);
          if (!empty($existing)) {
            continue;
          }

          $node_data = [
            'type' => 'convocatoria',
            'title' => $final_title,
            'field_publico_objetivo' => [
              'value' => $detail_results['perfil'],
              'format' => 'basic_html',
            ],
            'field_fecha_de_apertura' => $detail_results['apertura'],
            'field_fecha_de_cierre' => $detail_results['cierre'],
            'field_mas_informacion' => $href,
            'status' => 0,
            'uid' => 1,
          ];

          // Handle image if found
          if ($image_url) {
            $file = $this->downloadAndCreateFile($image_url);
            if ($file) {
              $node_data['field_imagen_destacada'] = [
                'target_id' => $file->id(),
                'alt' => $final_title,
              ];
            }
          }

          $new_node = \Drupal\node\Entity\Node::create($node_data);
          $new_node->save();
          $results['created']++;
        } catch (\Exception $e) {
          $this->loggerFactory->get('zinco_front')->error('Error en item ICETEX: @msg', ['@msg' => $e->getMessage()]);
        }
      }

    } catch (\Exception $e) {
      $results['errors'][] = $e->getMessage();
      $this->loggerFactory->get('zinco_front')->error('Fallo el scraping de ICETEX: @msg', ['@msg' => $e->getMessage()]);
    }

    return $results;
  }

  /**
   * Scrapes details from an ICETEX scholarship detail page.
   */
  protected function scrapeIcetexDetail($url)
  {
    try {
      $this->loggerFactory->get('zinco_front')->debug('Llamando a scrapeIcetexDetail para @url', ['@url' => $url]);

      $response = $this->httpClient->request('GET', $url, [
        'headers' => [
          'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        ],
      ]);
      $html = (string) $response->getBody();

      $dom = new \DOMDocument();
      libxml_use_internal_errors(true);
      $dom->loadHTML($html);
      libxml_clear_errors();

      $xpath = new \DOMXPath($dom);

      // Title: h1.titulo-interno
      $title_query = $xpath->query("//h1[contains(@class, 'titulo-interno')]");
      $title = $title_query->length ? trim($title_query->item(0)->textContent) : '';

      // Check if there is a deeper link (a.lnk_art_nuevo_cred) as sometimes the first page is a gateway
      $xpath_target = $xpath;
      $html_target = $html;

      $deep_link_query = $xpath->query("//a[contains(@class, 'lnk_art_nuevo_cred')]");
      if ($deep_link_query->length) {
        $deep_url = $deep_link_query->item(0)->getAttribute('href');
        if (!empty($deep_url)) {
          if (strpos($deep_url, 'http') !== 0) {
            $deep_url = 'https://web.icetex.gov.co' . $deep_url;
          }

          $this->loggerFactory->get('zinco_front')->debug('Siguiendo enlace profundo hacia @url', ['@url' => $deep_url]);

          try {
            $response_deep = $this->httpClient->request('GET', $deep_url, [
              'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
              ],
            ]);
            $html_target = (string) $response_deep->getBody();
            $dom_deep = new \DOMDocument();
            libxml_use_internal_errors(true);
            $dom_deep->loadHTML($html_target);
            libxml_clear_errors();
            $xpath_target = new \DOMXPath($dom_deep);

            // Update title if deeper page has a better one
            $title_query_deep = $xpath_target->query("//h1[contains(@class, 'titulo-interno')]");
            if ($title_query_deep->length) {
              $title = trim($title_query_deep->item(0)->textContent);
            }
          } catch (\Exception $e) {
            $this->loggerFactory->get('zinco_front')->warning('No se pudo cargar el enlace profundo @url: @msg', ['@url' => $deep_url, '@msg' => $e->getMessage()]);
          }
        }
      }

      // Dates: Apertura and Cierre in div.indicadores_becas
      $apertura = NULL;
      $cierre = NULL;

      $date_containers = $xpath_target->query("//div[contains(@class, 'indicadores_becas')]");
      foreach ($date_containers as $container) {
        $text = $container->textContent;
        // Normalize
        $text = str_replace("\xc2\xa0", ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text);

        if (stripos($text, 'Apertura:') !== false) {
          $date_parts = explode('Apertura:', $text, 2);
          if (isset($date_parts[1])) {
            $apertura = $this->parseSpanishDate(trim($date_parts[1]));
          }
        }
        if (stripos($text, 'Cierre:') !== false) {
          $date_parts = explode('Cierre:', $text, 2);
          if (isset($date_parts[1])) {
            $clean_date = preg_replace('/,?\s+hasta.*/i', '', $date_parts[1]);
            $cierre = $this->parseSpanishDate(trim($clean_date));
          }
        }
      }

      // Extract target audience (público objetivo) from the specific path requested.
      $perfil = '';
      $publico_nodes = $xpath_target->query("//div[contains(@class, 'descrp_conv')]/div[contains(@class, 'descrp_conv')]/div[contains(@class, 'descrp_conv')]/div[contains(@class, 'descrp_conv')]/p[3]");
      if ($publico_nodes->length) {
        $perfil = trim($publico_nodes->item(0)->textContent);
      }

      // Fallback to div.info_convo if indicators not found
      if (!$apertura && !$cierre) {
        $info_convo = $xpath_target->query("//div[contains(@class, 'info_convo')]//p");
        foreach ($info_convo as $p) {
          $text = $p->textContent;
          if (stripos($text, 'Apertura:') !== false) {
            $ap_parts = explode(':', $text, 2);
            if (isset($ap_parts[1]))
              $apertura = $this->parseSpanishDate($ap_parts[1]);
          }
          if (stripos($text, 'Cierre:') !== false) {
            $ci_parts = explode(':', $text, 2);
            if (isset($ci_parts[1])) {
              $clean_date = preg_replace('/,?\s+hasta.*/i', '', $ci_parts[1]);
              $cierre = $this->parseSpanishDate($clean_date);
            }
          }
        }
      }

      $this->loggerFactory->get('zinco_front')->debug('Detalle ICETEX extraído: Título: @title, Apertura: @ap, Cierre: @ci', [
        '@title' => $title,
        '@ap' => $apertura ?? 'N/A',
        '@ci' => $cierre ?? 'N/A',
      ]);

      // Fallback: Perfil from Accordion with text "Perfil de las personas aspirantes" if not found above.
      if (empty($perfil)) {
        $perfil_button = $xpath_target->query("//button[contains(normalize-space(), 'Perfil de las personas aspirantes')] | //a[contains(normalize-space(), 'Perfil de las personas aspirantes')]");
        if ($perfil_button->length) {
          $id = $perfil_button->item(0)->getAttribute('aria-controls') ?: $perfil_button->item(0)->getAttribute('href');
          if ($id) {
            $id = ltrim($id, '#');
            $content_by_id = $xpath_target->query("//div[@id='$id']");
            if ($content_by_id->length) {
              $perfil = trim($content_by_id->item(0)->textContent);
            }
          }
        }
      }

      return [
        'title' => $title,
        'apertura' => $apertura,
        'cierre' => $cierre,
        'perfil' => $perfil,
      ];

    } catch (\Exception $e) {
      $this->loggerFactory->get('zinco_front')->error('Error scraping ICETEX detail (@url): @msg', [
        '@url' => $url,
        '@msg' => $e->getMessage(),
      ]);
    }
    return NULL;
  }

  /**
   * Scrapes news from Colfuturo.
   */
  public function scrapeColfuturoNews($url = 'https://www.colfuturo.org/noticias', $limit = 10)
  {
    $results = [
      'created' => 0,
      'errors' => [],
    ];

    try {
      $response = $this->httpClient->request('GET', $url, [
        'headers' => [
          'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        ],
      ]);
      $html = (string) $response->getBody();

      $dom = new \DOMDocument();
      libxml_use_internal_errors(true);
      $dom->loadHTML($html);
      libxml_clear_errors();

      $xpath = new \DOMXPath($dom);

      // Selectors: .views-row
      $articles = $xpath->query("//div[contains(@class, 'views-row')]");
      $this->loggerFactory->get('zinco_front')->info('Noticias encontradas en Colfuturo: @count', ['@count' => $articles->length]);

      for ($i = 0; $i < $articles->length && $results['created'] < $limit; $i++) {
        $article = $articles->item($i);

        try {
          // Extract Title and Link.
          $title_node = $xpath->query(".//h3 | .//div[contains(@class, 'views-field-title')] | .//div[contains(@class, 'new-row__content')]//div[contains(@class, 'title')]", $article)->item(0);

          if (!$title_node) {
            $this->loggerFactory->get('zinco_front')->warning('Colfuturo: No se encontró el nodo de título para el ítem @i.', ['@i' => $i]);
            continue;
          }

          $title = trim($title_node->textContent);
          if (empty($title)) {
            $this->loggerFactory->get('zinco_front')->warning('Colfuturo: Título vacío para el ítem @i.', ['@i' => $i]);
            continue;
          }

          $link = '';
          if ($title_node->nodeName === 'a') {
            $link = $title_node->getAttribute('href');
          } else {
            // Check for link inside title or wrapping it.
            $a_query = $xpath->query(".//a", $title_node);
            if ($a_query->length) {
              $link = $a_query->item(0)->getAttribute('href');
            } else {
              // Try wrapping link or nearby link.
              $a_query = $xpath->query(".//a", $article);
              if ($a_query->length) {
                $link = $a_query->item(0)->getAttribute('href');
              }
            }
          }

          if (empty($link)) {
            $this->loggerFactory->get('zinco_front')->warning('Colfuturo: No se encontró enlace para la noticia: @title', ['@title' => $title]);
          } elseif (strpos($link, 'http') !== 0) {
            $link = 'https://www.colfuturo.org' . $link;
          }

          // Check if already exists.
          $existing = $this->entityTypeManager->getStorage('node')->loadByProperties([
            'type' => 'noticia',
            'title' => $title,
          ]);
          if (!empty($existing)) {
            $this->loggerFactory->get('zinco_front')->debug('Colfuturo: La noticia ya existe (omitida): @title', ['@title' => $title]);
            continue;
          }

          $this->loggerFactory->get('zinco_front')->info('Colfuturo: Procesando noticia nueva: @title', ['@title' => $title]);

          // Extract Image.
          $img_url = '';
          $img_query = $xpath->query(".//div[contains(@class, 'views-field-field-image')]//img | .//img", $article);
          if ($img_query->length) {
            $img_url = $img_query->item(0)->getAttribute('src');
            if (!empty($img_url) && strpos($img_url, 'http') !== 0) {
              $img_url = 'https://www.colfuturo.org' . $img_url;
            }
          }

          // Extract Summary/Date.
          $summary = '';
          $summary_query = $xpath->query(".//div[contains(@class, 'views-field-field-summary')] | .//div[contains(@class, 'node__content')]", $article);
          if ($summary_query->length) {
            $summary = trim($summary_query->item(0)->textContent);
          }

          // Visit Detail Page for full content.
          $content = $summary;
          if (!empty($link)) {
            $detail_content = $this->scrapeColfuturoDetailContent($link);
            if ($detail_content) {
              $content = $detail_content;
            }
          }

          $node_data = [
            'type' => 'noticia',
            'title' => $title,
            'field_contenido_noticia' => [
              'value' => $content,
              'format' => 'basic_html',
            ],
            'status' => 0, // DRAFT
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
          $this->loggerFactory->get('zinco_front')->info('Colfuturo: Noticia creada: @title', ['@title' => $title]);
          $results['created']++;

        } catch (\Exception $e) {
          $results['errors'][] = $e->getMessage();
          $this->loggerFactory->get('zinco_front')->error('Error procesando noticia Colfuturo: @msg', ['@msg' => $e->getMessage()]);
        }
      }
    } catch (\Exception $e) {
      $results['errors'][] = $e->getMessage();
      $this->loggerFactory->get('zinco_front')->error('Fallo el scraping de Colfuturo: @msg', ['@msg' => $e->getMessage()]);
    }

    return $results;
  }

  /**
   * Scrapes the body content from a Colfuturo news detail page.
   */
  protected function scrapeColfuturoDetailContent($url)
  {
    try {
      $response = $this->httpClient->request('GET', $url, [
        'headers' => [
          'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        ],
      ]);
      $html = (string) $response->getBody();
      $dom = new \DOMDocument();
      libxml_use_internal_errors(true);
      $dom->loadHTML($html);
      libxml_clear_errors();
      $xpath = new \DOMXPath($dom);

      $content_query = $xpath->query("//div[contains(@class, 'field--name-field-resumen')] | //div[contains(@class, 'field--name-body')] | //div[contains(@class, 'new-row__content')]//div[contains(@class, 'body')] | //div[@property='schema:text']");
      if ($content_query->length) {
        // Return HTML content.
        return $dom->saveHTML($content_query->item(0));
      }
    } catch (\Exception $e) {
      $this->loggerFactory->get('zinco_front')->error('Error scraping Colfuturo detail (@url): @msg', [
        '@url' => $url,
        '@msg' => $e->getMessage(),
      ]);
    }
    return NULL;
  }

}
