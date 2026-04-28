<?php

namespace Drupal\zinco_etl\Service;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use DOMDocument;
use DOMXPath;

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
   * Constructor.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The Guzzle HTTP client.
   */
  public function __construct(ClientInterface $http_client)
  {
    $this->httpClient = $http_client;
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

      // Find the TR that contains the "Artículos" header.
      $start_tr = $xpath->query("//tr[descendant::h3[contains(normalize-space(), 'Artículos')]]")->item(0);

      if (!$start_tr) {
        \Drupal::logger('cvlac_scraper')->warning('⚠️ No se encontró la fila con el encabezado "Artículos" en @url', ['@url' => $url]);
        return 0;
      }

      $tr_count = 0;
      $current_tr = $start_tr->nextSibling;

      while ($current_tr) {
        if ($current_tr->nodeType === XML_ELEMENT_NODE) {
          // If the sibling is a TR, count it as part of an article or check if it's the next section.
          if ($current_tr->nodeName === 'tr') {
            // Check if this row contains the "Libros" header to stop.
            if ($xpath->query(".//h3[contains(normalize-space(), 'Libros')]", $current_tr)->length > 0) {
              break;
            }
            $tr_count++;
          }
          // Fallback in case h3 is a direct sibling of the start_tr's parent (less likely).
          elseif ($current_tr->nodeName === 'h3') {
            if (stripos(trim($current_tr->textContent), 'Libros') !== false) {
              break;
            }
          }
        }
        $current_tr = $current_tr->nextSibling;
      }

      // According to the user, each article uses 2 rows (header + detail).
      $article_count = (int) floor($tr_count / 2);

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

}
