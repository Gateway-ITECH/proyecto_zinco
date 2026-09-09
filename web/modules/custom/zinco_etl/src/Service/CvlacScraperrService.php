<?php

namespace Drupal\zinco_etl\Service;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use DOMDocument;
use DOMXPath;
use Drupal\Core\Database\Connection;

/**
 * Service for scraping data from CvLAC.
 */
class CvlacScraperrService
{

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructor.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The Guzzle HTTP client.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(ClientInterface $http_client, Connection $database)
  {
    $this->httpClient = $http_client;
    $this->database = $database;
  }

  /**
   * Scrapes a CvLAC URL and counts research articles.
   *
   * @param string $url
   *   The CvLAC URL to analyze.
   *
   * @return int
   *   The number of research articles detected, or -1 on error.
   */
  public function countResearchArticles(string $url): int
  {
    \Drupal::logger('cvlac_scraper')->info('🔍 Analizando CvLAC: @url', ['@url' => $url]);

    try {
      $response = $this->httpClient->request('GET', $url, [
        'timeout' => 20,
        'headers' => [
          'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        ],
      ]);

      if ($response->getStatusCode() !== 200) {
        \Drupal::logger('cvlac_scraper')->error('❌ Error HTTP @code al acceder a @url', [
          '@code' => $response->getStatusCode(),
          '@url' => $url,
        ]);
        return -1;
      }

      $html_content = (string) $response->getBody();

      // Parse HTML.
      libxml_use_internal_errors(true);
      $dom = new DOMDocument();
      // Load HTML with UTF-8 support.
      $dom->loadHTML('<?xml encoding="UTF-8">' . $html_content);
      libxml_clear_errors();

      $xpath = new DOMXPath($dom);

      // Find the "Artículos" header.
      // CvLAC usually uses <h3>Artículos</h3>.
      $start_h3 = NULL;
      $headers = $xpath->query("//h3");
      foreach ($headers as $h3) {
        if (stripos(trim($h3->textContent), 'Artículos') !== false) {
          $start_h3 = $h3;
          break;
        }
      }

      if (!$start_h3) {
        \Drupal::logger('cvlac_scraper')->warning('⚠️ No se encontró la sección "Artículos" en @url', ['@url' => $url]);
        return 0;
      }

      // Following the user's specific logic:
      // 1. Start from the h3 "Artículos".
      // 2. Locate the parent (h3 -> tr -> tbody).
      $tbody = $xpath->query("ancestor::tbody[1]", $start_h3)->item(0);

      // Debugging hierarchy.
      $parent = $start_h3->parentNode;
      $grand_parent = $parent ? $parent->parentNode : NULL;
      \Drupal::logger('cvlac_scraper')->debug('Hierarchy Debug: h3 parent is <@p>, grandparent is <@gp>', [
        '@p' => $parent ? $parent->nodeName : 'NONE',
        '@gp' => $grand_parent ? $grand_parent->nodeName : 'NONE',
      ]);

      if (!$tbody) {
        // Fallback: search for the nearest table if tbody is missing (DOMDocument sometimes omits it).
        $tbody = $xpath->query("ancestor::table[1]", $start_h3)->item(0);
        if ($tbody) {
          \Drupal::logger('cvlac_scraper')->info('💡 No se encontró tbody, usando el ancestro table como contenedor.');
        }
      }

      if (!$tbody) {
        \Drupal::logger('cvlac_scraper')->warning('⚠️ No se encontró el elemento contenedor (tbody o table) para la sección de artículos en @url', ['@url' => $url]);
        return 0;
      }

      // 3. Count the number of TRs in that tbody.
      // We use "./tr" to only count direct children TRs of the tbody.
      $trs = $xpath->query("./tr", $tbody);
      $total_trs = $trs->length;

      // 4. Subtract one TR (the one containing the h3) and divide by two.
      $article_count = ($total_trs > 0) ? (int) floor(($total_trs - 1) / 2) : 0;

      \Drupal::logger('cvlac_scraper')->info('✅ Algoritmo aplicado: (@total - 1) / 2 = @count artículos.', [
        '@total' => $total_trs,
        '@count' => $article_count,
      ]);

      \Drupal::logger('cvlac_scraper')->info('✅ Se detectaron @count artículos en @url', [
        '@count' => $article_count,
        '@url' => $url,
      ]);

      return $article_count;

    } catch (RequestException $e) {
      \Drupal::logger('cvlac_scraper')->error('❌ Error de red al scrapear CvLAC: @msg', ['@msg' => $e->getMessage()]);
      return -1;
    } catch (\Exception $e) {
      \Drupal::logger('cvlac_scraper')->error('❌ Error inesperado al procesar CvLAC: @msg', ['@msg' => $e->getMessage()]);
      return -1;
    }
  }

  /**
   * Counts articles in the database for a person ID extracted from a CvLAC URL.
   *
   * @param string $url
   *   The CvLAC URL containing cod_rh.
   *
   * @return int
   *   The number of articles found in the database.
   */
  public function countArticlesFromDatabase(string $url): int
  {
    // Extract cod_rh from URL.
    $parsed_url = parse_url($url);
    parse_str($parsed_url['query'] ?? '', $query_params);
    $cod_rh = $query_params['cod_rh'] ?? NULL;
    //var_dump($cod_rh);

    if (!$cod_rh) {
      \Drupal::logger('cvlac_scraper')->warning('No se pudo extraer cod_rh de la URL: @url', ['@url' => $url]);
      return 0;
    }

    try {
      $query = $this->database->select('data_produccion_cientifica', 'zdpc');
      $query->addExpression('COUNT(DISTINCT ID_PRODUCTO_PD)', 'total_productos');
      $query->condition('ID_PERSONA_PD', $cod_rh)
        ->condition('NME_TIPOLOGIA_PD', 'Artículos de investi');

      $count = (int) $query->execute()->fetchField();

      \Drupal::logger('cvlac_scraper')->info('📦 DB: Se encontraron @count artículos para ID @id en la tabla de producción científica.', [
        '@count' => $count,
        '@id' => $cod_rh,
      ]);

      return $count;
    } catch (\Exception $e) {
      \Drupal::logger('cvlac_scraper')->error('Error consultando base de datos: @msg', ['@msg' => $e->getMessage()]);
      return 0;
    }
  }

}
