<?php

namespace Drupal\zinco_actors\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;

/**
 * Service for batch processing SQL data to Drupal entities.
 */
class BatchProcessor {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructs a BatchProcessor object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(Connection $database, EntityTypeManagerInterface $entity_type_manager, LoggerChannelFactoryInterface $logger_factory) {
    $this->database = $database;
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger_factory->get('zinco_actors');
  }

  /**
   * Process investigadores from SQL table to entities.
   *
   * @param int $limit
   *   Number of records to process.
   * @param int $offset
   *   Offset for pagination.
   * @param bool $dry_run
   *   If TRUE, simulate without creating entities.
   *
   * @return array
   *   Array with 'success', 'failed', and 'skipped' counts.
   */
  public function processInvestigadores($limit = 100, $offset = 0, $dry_run = FALSE) {
    $results = [
      'success' => 0,
      'failed' => 0,
      'skipped' => 0,
      'errors' => [],
    ];

    try {
      // Query the SQL view.
      $query = $this->database->select('zinco_data_view_investigadores', 'i')
        ->fields('i')
        ->range($offset, $limit);
      
      $records = $query->execute()->fetchAll();

      $this->logger->info('Processing @count investigadores (offset: @offset, limit: @limit, dry_run: @dry_run)', [
        '@count' => count($records),
        '@offset' => $offset,
        '@limit' => $limit,
        '@dry_run' => $dry_run ? 'YES' : 'NO',
      ]);

      foreach ($records as $record) {
        try {
          // Check if entity already exists by external_id.
          if ($this->entityExists($record->ID_PERSONA_PR)) {
            $results['skipped']++;
            $this->logger->info('Skipped investigador @id (already exists)', [
              '@id' => $record->ID_PERSONA_PR,
            ]);
            continue;
          }

          if ($dry_run) {
            $this->logger->info('DRY RUN: Would create investigador @id', [
              '@id' => $record->ID_PERSONA_PR,
            ]);
            $results['success']++;
          }
          else {
            // Create the entity.
            $entity = $this->createInvestigadorEntity($record);
            $entity->save();
            
            $results['success']++;
            $this->logger->info('Created investigador entity @id (@entity_id)', [
              '@id' => $record->ID_PERSONA_PR,
              '@entity_id' => $entity->id(),
            ]);
          }
        }
        catch (\Exception $e) {
          $results['failed']++;
          $error_msg = sprintf('Error processing investigador %s: %s', $record->ID_PERSONA_PR, $e->getMessage());
          $results['errors'][] = $error_msg;
          $this->logger->error($error_msg);
        }
      }

      $this->logger->info('Batch complete. Success: @success, Failed: @failed, Skipped: @skipped', [
        '@success' => $results['success'],
        '@failed' => $results['failed'],
        '@skipped' => $results['skipped'],
      ]);

    }
    catch (\Exception $e) {
      $this->logger->error('Fatal error in batch processing: @message', [
        '@message' => $e->getMessage(),
      ]);
      $results['errors'][] = 'Fatal error: ' . $e->getMessage();
    }

    return $results;
  }

  /**
   * Check if an entity with the given external_id already exists.
   *
   * @param string $external_id
   *   The external ID to check.
   *
   * @return bool
   *   TRUE if entity exists, FALSE otherwise.
   */
  protected function entityExists($external_id) {
    $storage = $this->entityTypeManager->getStorage('zinco_actors_zincoactors');
    $query = $storage->getQuery()
      ->condition('bundle', 'investigador')
      ->condition('field_external_id', $external_id)
      ->accessCheck(FALSE)
      ->range(0, 1);
    
    $ids = $query->execute();
    return !empty($ids);
  }

  /**
   * Create an investigador entity from SQL record.
   *
   * @param object $record
   *   The SQL record.
   *
   * @return \Drupal\zinco_actors\Entity\ZincoActors
   *   The created entity.
   */
  protected function createInvestigadorEntity($record) {
    $storage = $this->entityTypeManager->getStorage('zinco_actors_zincoactors');
    
    $values = [
      'bundle' => 'investigador',
      'label' => $record->ID_PERSONA_PR,
      'field_external_id' => $record->ID_PERSONA_PR,
      'status' => 1,
    ];

    // Map municipio (if exists in divipola taxonomy).
    if (!empty($record->municipio)) {
      $municipio_tid = $this->findTaxonomyTerm('divipola', $record->municipio);
      if ($municipio_tid) {
        $values['municipio'] = $municipio_tid;
      }
    }

    // Map sector (if exists in ciiu_colombia taxonomy).
    if (!empty($record->sector)) {
      $sector_tid = $this->findTaxonomyTerm('ciiu_colombia', $record->sector);
      if ($sector_tid) {
        $values['sector_economico_principal'] = $sector_tid;
      }
    }

    // Map tecnologia40 (if exists in tecnologias_clave taxonomy).
    if (!empty($record->tecnologia40)) {
      $tech_tid = $this->findTaxonomyTerm('tecnologias_clave', $record->tecnologia40);
      if ($tech_tid) {
        $values['tecnologias_clave'] = [$tech_tid];
      }
    }

    // Map afiliacion actual (institution).
    if (!empty($record->INST_FILIA)) {
      $values['field_afiliacion_actual'] = $record->INST_FILIA;
    }

    return $storage->create($values);
  }

  /**
   * Find a taxonomy term by name in a vocabulary.
   *
   * @param string $vocabulary
   *   The vocabulary ID.
   * @param string $term_name
   *   The term name to search for.
   *
   * @return int|null
   *   The term ID if found, NULL otherwise.
   */
  protected function findTaxonomyTerm($vocabulary, $term_name) {
    $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
    
    $query = $term_storage->getQuery()
      ->condition('vid', $vocabulary)
      ->condition('name', $term_name)
      ->accessCheck(FALSE)
      ->range(0, 1);
    
    $tids = $query->execute();
    
    return !empty($tids) ? reset($tids) : NULL;
  }

}
