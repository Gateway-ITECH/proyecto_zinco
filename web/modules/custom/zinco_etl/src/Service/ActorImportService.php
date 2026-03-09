<?php

namespace Drupal\zinco_etl\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\zinco_actors\Entity\ZincoActors;

/**
 * Service for importing actors from CSV.
 */
class ActorImportService
{

    /**
     * The entity type manager.
     *
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
    protected $entityTypeManager;

    /**
     * The messenger service.
     *
     * @var \Drupal\Core\Messenger\MessengerInterface
     */
    protected $messenger;

    /**
     * The logger.
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * Constructs a new ActorImportService.
     *
     * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
     *   The entity type manager.
     * @param \Drupal\Core\Messenger\MessengerInterface $messenger
     *   The messenger service.
     * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
     *   The logger factory.
     */
    public function __construct(EntityTypeManagerInterface $entity_type_manager, MessengerInterface $messenger, LoggerChannelFactoryInterface $logger_factory)
    {
        $this->entityTypeManager = $entity_type_manager;
        $this->messenger = $messenger;
        $this->logger = $logger_factory->get('zinco_etl');
    }

    /**
     * Parses a CSV file and returns data as an array.
     */
    public function parseCsv(string $file_path, string $delimiter = ','): array
    {
        $rows = [];
        if (($handle = fopen($file_path, 'r')) !== FALSE) {
            $header = fgetcsv($handle, 0, $delimiter);
            if ($header) {
                // Clean UTF-8 BOM if present in first header column.
                $header[0] = preg_replace('/^[\xEF\xBB\xBF]+/', '', $header[0]);
                while (($data = fgetcsv($handle, 0, $delimiter)) !== FALSE) {
                    if (count($header) == count($data)) {
                        $rows[] = array_combine($header, $data);
                    }
                }
            }
            fclose($handle);
        }
        return $rows;
    }

    /**
     * Processes a single row for import.
     *
     * @param array $row
     *   The data row from CSV.
     *
     * @return bool
     *   TRUE if successful.
     */
    public function processRow(array $row)
    {
        if (empty($row['bundle']) || empty($row['label'])) {
            $this->logger->warning('Row skipped: Missing bundle or label.');
            return FALSE;
        }

        $bundle = $row['bundle'];
        $label = $row['label'];

        try {
            $storage = $this->entityTypeManager->getStorage('zinco_actors_zincoactors');

            // Upsert logic: Search for existing entity by label and bundle.
            $query = $storage->getQuery()
                ->condition('bundle', $bundle)
                ->condition('label', $label)
                ->accessCheck(FALSE);

            $ids = $query->execute();

            if (!empty($ids)) {
                $entity_id = reset($ids);
                $entity = $storage->load($entity_id);
                $this->logger->info('Updating actor: @label (@bundle)', ['@label' => $label, '@bundle' => $bundle]);
            } else {
                $entity = $storage->create(['bundle' => $bundle, 'label' => $label]);
                $this->logger->info('Creating actor: @label (@bundle)', ['@label' => $label, '@bundle' => $bundle]);
            }

            // Map fields.
            foreach ($row as $field_name => $value) {
                // Skip metadata columns.
                if (in_array($field_name, ['bundle'])) {
                    continue;
                }

                if ($entity->hasField($field_name)) {
                    // Requirement: if value is empty, do not alter the field.
                    if ($value !== '' && $value !== NULL) {
                        $this->setFieldValue($entity, $field_name, $value);
                    }
                }
            }

            $entity->save();
            return TRUE;
        } catch (\Exception $e) {
            $this->logger->error('Error processing row: @message', ['@message' => $e->getMessage()]);
            return FALSE;
        }
    }

    /**
     * Sets a field value with basic handling for entity references.
     */
    protected function setFieldValue($entity, $field_name, $value)
    {
        $field_definition = $entity->getFieldDefinition($field_name);
        $type = $field_definition->getType();

        if ($type === 'entity_reference') {
            $target_type = $field_definition->getSetting('target_type');
            if ($target_type === 'taxonomy_term') {
                $handler_settings = $field_definition->getSetting('handler_settings');
                $target_bundles = $handler_settings['target_bundles'] ?? [];
                $bundle = reset($target_bundles);

                $tid = $this->lookupTerm($value, $bundle);
                if ($tid) {
                    $entity->set($field_name, $tid);
                } else {
                    $this->logger->warning('Term "@value" not found for bundle "@bundle" in field "@field"', [
                        '@value' => $value,
                        '@bundle' => $bundle,
                        '@field' => $field_name,
                    ]);
                }
            } else {
                // Generic reference by ID or lookup if needed.
                $entity->set($field_name, $value);
            }
        } else {
            $entity->set($field_name, $value);
        }
    }

    /**
     * Helper to lookup taxonomy term by name.
     */
    protected function lookupTerm($name, $bundle)
    {
        $storage = $this->entityTypeManager->getStorage('taxonomy_term');
        $query = $storage->getQuery()
            ->condition('vid', $bundle)
            ->condition('name', $name)
            ->accessCheck(FALSE)
            ->range(0, 1);

        $ids = $query->execute();
        return !empty($ids) ? reset($ids) : NULL;
    }

}
