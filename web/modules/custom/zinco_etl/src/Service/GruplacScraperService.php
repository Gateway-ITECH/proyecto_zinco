<?php

namespace Drupal\zinco_etl\Service;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use DOMDocument;
use DOMXPath;

/**
 * Servicio para scraping de datos de Gruplac.
 */
class GruplacScraperService {

  /**
   * Cliente Guzzle HTTP.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Constructor.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   * El cliente Guzzle HTTP inyectado.
   */
  public function __construct(ClientInterface $http_client) {
    $this->httpClient = $http_client;
  }

  /**
   * Se conecta a una URL, encuentra la tabla número N y cuenta el total de celdas <td> dentro de ella.
   *
   * @param string $url
   * La URL del sitio web a scrapear (e.g., URL de Gruplac).
   * @param int $table_number
   * El número ordinal (base 1) de la tabla a extraer.
   *
   * @return int
   * El número total de etiquetas <td> en la tabla especificada, o -1 si falla.
   */
  public function extraerYContarTd(string $url, int $table_number = 49): int {
    \Drupal::logger('gruplac_scraper')->info('🌐 Conectándose a: @url...', ['@url' => $url]);

    try {
      // 1. Realizar la solicitud HTTP con Guzzle (similar a 'requests').
      $response = $this->httpClient->request('GET', $url, [
        'timeout' => 15,
        'headers' => [
          // Recomendado para evitar ser bloqueado.
          'User-Agent' => 'Mozilla/5.0 (Drupal Scraper)',
        ],
      ]);

      if ($response->getStatusCode() !== 200) {
        \Drupal::logger('gruplac_scraper')->error('❌ Error HTTP: Código @code en @url', [
          '@code' => $response->getStatusCode(),
          '@url' => $url,
        ]);
        return -1;
      }

    } catch (RequestException $e) {
      \Drupal::logger('gruplac_scraper')->error('❌ Error al conectar o descargar la página: @e', ['@e' => $e->getMessage()]);
      return -1;
    }

    // Obtener el contenido HTML.
    $html_content = (string) $response->getBody();

    // 2. Parsear el contenido HTML (similar a 'BeautifulSoup').
    // Opciones para evitar errores de parseo con HTML mal formado.
    libxml_use_internal_errors(true); 
    $dom = new DOMDocument();
    $dom->loadHTML($html_content);
    libxml_clear_errors();

    $xpath = new DOMXPath($dom);

    // 3. Seleccionar la tabla N usando XPath (equivalente al selector CSS 'table:nth-of-type(N)').
    // XPath usa índices base 1, por lo que podemos usar directamente el número.
    // El selector es: //table[N]
    $xpath_selector = "//table[{$table_number}]";
    $tablas = $xpath->query($xpath_selector);

    if ($tablas->length === 0) {
      \Drupal::logger('gruplac_scraper')->warning('⚠️ Error: No se encontró la tabla número @num con el selector: @selector', [
        '@num' => $table_number,
        '@selector' => $xpath_selector,
      ]);
      return -1;
    }

    // 4. Contar todas las etiquetas <td> dentro de la tabla encontrada.
    $target_table = $tablas->item(0);

    // El selector es: .//td (busca todos los <td> descendientes del nodo actual)
    $td_count = $xpath->query('.//td', $target_table)->length;

    // Aplicar la fórmula de conteo específica que tenías: (len(td)-1)/2
    // Se asume que esto ajusta el conteo de Gruplac para obtener el número de productos.
    $product_count = ($td_count > 0) ? floor(($td_count - 1) / 2) : 0;
    
    \Drupal::logger('gruplac_scraper')->info('--- Resultados ---');
    \Drupal::logger('gruplac_scraper')->info('✅ Tabla objetivo (N° @num) encontrada.', ['@num' => $table_number]);
    \Drupal::logger('gruplac_scraper')->info('🔢 Total de productos (fórmula aplicada): @count', ['@count' => $product_count]);
    \Drupal::logger('gruplac_scraper')->info('------------------');

    return (int) $product_count;
  }

}