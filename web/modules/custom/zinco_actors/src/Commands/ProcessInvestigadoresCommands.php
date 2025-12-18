<?php

namespace Drupal\zinco_actors\Commands;

use Drupal\zinco_actors\Service\BatchProcessor;
use Drush\Commands\DrushCommands;

/**
 * Drush commands for processing investigadores.
 */
class ProcessInvestigadoresCommands extends DrushCommands {

  /**
   * The batch processor service.
   *
   * @var \Drupal\zinco_actors\Service\BatchProcessor
   */
  protected $batchProcessor;

  /**
   * Constructs a ProcessInvestigadoresCommands object.
   *
   * @param \Drupal\zinco_actors\Service\BatchProcessor $batch_processor
   *   The batch processor service.
   */
  public function __construct(BatchProcessor $batch_processor) {
    parent::__construct();
    $this->batchProcessor = $batch_processor;
  }

  /**
   * Process investigadores from SQL table to entities.
   *
   * @param array $options
   *   An associative array of options.
   *
   * @command zinco:process-investigadores
   * @option limit Number of records to process (default: 100)
   * @option offset Offset for pagination (default: 0)
   * @option dry-run Simulate without creating entities
   * @usage zinco:process-investigadores --limit=10 --dry-run
   *   Process 10 investigadores in dry-run mode
   * @usage zinco:process-investigadores --limit=100 --offset=100
   *   Process 100 investigadores starting from offset 100
   * @aliases zinco:pi
   */
  public function processInvestigadores(array $options = ['limit' => 100, 'offset' => 0, 'dry-run' => FALSE]) {
    $limit = $options['limit'];
    $offset = $options['offset'];
    $dry_run = $options['dry-run'];

    $this->output()->writeln(sprintf(
      'Processing investigadores (limit: %d, offset: %d, dry-run: %s)...',
      $limit,
      $offset,
      $dry_run ? 'YES' : 'NO'
    ));

    $results = $this->batchProcessor->processInvestigadores($limit, $offset, $dry_run);

    // Display results.
    $this->output()->writeln('');
    $this->output()->writeln('<info>Processing complete!</info>');
    $this->output()->writeln(sprintf('  Success: %d', $results['success']));
    $this->output()->writeln(sprintf('  Failed: %d', $results['failed']));
    $this->output()->writeln(sprintf('  Skipped: %d', $results['skipped']));

    if (!empty($results['errors'])) {
      $this->output()->writeln('');
      $this->output()->writeln('<error>Errors:</error>');
      foreach ($results['errors'] as $error) {
        $this->output()->writeln('  - ' . $error);
      }
    }

    // Return appropriate exit code.
    return $results['failed'] > 0 ? DrushCommands::EXIT_FAILURE : DrushCommands::EXIT_SUCCESS;
  }

}
