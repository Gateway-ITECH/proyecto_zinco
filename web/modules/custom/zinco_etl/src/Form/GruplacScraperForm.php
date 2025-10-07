<?php

namespace Drupal\zinco_etl\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\zinco_etl\Service\GruplacScraperService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Batch\BatchBuilder;

/**
 * Provides a Gruplac Scraper form.
 */
class GruplacScraperForm extends FormBase {

  /**
   * The Gruplac Scraper service.
   *
   * @var \Drupal\zinco_etl\Service\GruplacScraperService
   */
  protected $gruplacScraperService;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a new GruplacScraperForm.
   *
   * @param \Drupal\zinco_etl\Service\GruplacScraperService $gruplac_scraper_service
   *   The Gruplac Scraper service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(GruplacScraperService $gruplac_scraper_service, MessengerInterface $messenger, Connection $database) {
    $this->gruplacScraperService = $gruplac_scraper_service;
    $this->messenger = $messenger;
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('zinco_etl.gruplac_scraper'),
      $container->get('messenger'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'gruplac_scraper_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['table_number'] = [
      '#type' => 'number',
      '#title' => $this->t('Número de tabla'),
      '#default_value' => 49,
      '#required' => TRUE,
      '#min' => 1,
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Scrapear Gruplac'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $urls = $this->getGruplacUrlsFromDatabase(0, 10); // Get a batch of URLs and their IDs.
    $table_number = $form_state->getValue('table_number');

    if (empty($urls)) {
      $this->messenger->addWarning($this->t('No se encontraron URLs de Gruplac en la base de datos.'));
      return;
    }

    $batch_builder = (new BatchBuilder())
      ->setTitle($this->t('Scraping Gruplac URLs'))
      ->setInitMessage($this->t('Starting Gruplac URL scraping...'))
      ->setProgressMessage($this->t('Processing @current out of @total URLs.'))
      ->setErrorMessage($this->t('Gruplac URL scraping encountered an error.'));

    foreach ($urls as $item) {
      $batch_builder->addOperation([$this, 'gruplacScraperBatchOperation'], [$item->fuente_informacion, $item->id, $table_number]);
    }

    $batch_builder
      ->setFinishCallback([$this, 'gruplacScraperBatchFinished']);

    batch_set($batch_builder->toArray());
  }

  /**
   * Batch operation for scraping Gruplac URLs.
   *
   * @param string $url
   *   The URL to scrape.
   * @param int $table_number
   *   The table number to extract data from.
   * @param array $context
   *   The batch context.
   */
  public function gruplacScraperBatchOperation(string $url, int $id, int $table_number, array &$context) {
    /** @var \Drupal\zinco_etl\Service\GruplacScraperService $gruplac_scraper_service */
    $gruplac_scraper_service = \Drupal::service('zinco_etl.gruplac_scraper');
    $product_count = $gruplac_scraper_service->extraerYContarTd($url, $table_number);

    if (!isset($context['results']['processed'])) {
      $context['results']['processed'] = 0;
      $context['results']['success'] = 0;
      $context['results']['failed'] = 0;
      $context['results']['messages'] = [];
    }

    $context['results']['processed']++;
    if ($product_count !== -1) {
      $this->updateGruplacTotalSoftwares($id, $product_count);
      $context['results']['success']++;
      $context['results']['messages'][] = $this->t('Se encontraron @count productos en la tabla @table_number de la URL: @url (ID: @id)', [
        '@count' => $product_count,
        '@table_number' => $table_number,
        '@id' => $id,
        '@url' => $url,
      ])->render();
    } else {
      $context['results']['failed']++;
      $context['results']['messages'][] = $this->t('No se pudo extraer la información de Gruplac para la URL: @url. Consulte los logs para más detalles.', [
        '@url' => $url,
      ])->render();
    }
  }

  /**
   * Batch finished callback.
   *
   * @param bool $success
   *   TRUE if the batch completed successfully.
   * @param array $results
   *   The results of the batch operations.
   * @param array $operations
   *   A list of the operations that were performed.
   */
  public function gruplacScraperBatchFinished(bool $success, array $results, array $operations) {
    if ($success) {
      $this->messenger->addStatus($this->t('Gruplac URL scraping completed. Processed @processed URLs, @success succeeded, @failed failed.', [
        '@processed' => $results['processed'] ?? 0,
        '@success' => $results['success'] ?? 0,
        '@failed' => $results['failed'] ?? 0,
      ]));
      foreach ($results['messages'] ?? [] as $message) {
        $this->messenger->addStatus($message);
      }
    } else {
      $this->messenger->addError($this->t('Gruplac URL scraping finished with errors.'));
      foreach ($results['messages'] ?? [] as $message) {
        $this->messenger->addError($message);
      }
    }
  }

  /**
   * Updates the 'gruplac_total_softwares' field for a given ID.
   *
   * @param int $id
   *   The ID of the record to update.
   * @param int $total_softwares
   *   The value to set for 'gruplac_total_softwares'.
   */
  protected function updateGruplacTotalSoftwares(int $id, int $total_softwares) {
    $this->database->update('data_grupos_investigacion')
      ->fields(['gruplac_total_softwares' => $total_softwares])
      ->condition('id', $id)
      ->execute();
  }

  /**
   * Retrieves Gruplac URLs from the database.
   *
   * @param int $offset
   *   The number of records to skip.
   * @param int $limit
   *   The maximum number of records to return.
   *
   * @return array
   *   An array of Gruplac URLs.
   */
  public function getGruplacUrlsFromDatabase(int $offset = 0, int $limit = 100): array {
    $query = $this->database->select('data_grupos_investigacion', 'zdgi')
      ->fields('zdgi', ['fuente_informacion', 'id']);
    $result = $query->execute()->fetchAll();
    return $result;
  }

}