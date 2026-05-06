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
                if (isset($header[0])) {
                    $header[0] = preg_replace('/^[\xEF\xBB\xBF]+/', '', $header[0]);
                }
                // Trim all header keys to avoid issues with spaces.
                $header = array_map('trim', $header);

                $line_number = 1;

                //

                while (($data = fgetcsv($handle, 0, $delimiter)) !== FALSE) {
                    $line_number++;
                    // Skip empty rows.
                    if ($data === [NULL] || empty(array_filter($data, 'strlen'))) {
                        continue;
                    }

                    $data_count = count($data);
                    $header_count = count($header);

                    if ($data_count !== $header_count) {
                        $this->logger->warning('CSV alignment issue on line @line: Row has @data_cols columns, but header has @header_cols.', [
                            '@line' => $line_number,
                            '@data_cols' => $data_count,
                            '@header_cols' => $header_count,
                        ]);

                        if ($data_count < $header_count) {
                            $data = array_pad($data, $header_count, '');
                        } else {
                            $data = array_slice($data, 0, $header_count);
                        }
                    }

                    // Fill empty fields with '0' as requested.
                    $data = array_map(function ($value) {
                        return ($value === '' || $value === NULL) ? '0' : $value;
                    }, $data);

                    $rows[] = array_combine($header, $data);
                }
            }
            fclose($handle);
        }
        return $rows;
    }

    /**
     * Processes a single row for import, supporting exported CSV structure.
     */
    public function processRow(array $row, string $bundle_machine_name = NULL)
    {
        $entity_type_id = 'zinco_actors_zincoactors';
        $storage = $this->entityTypeManager->getStorage($entity_type_id);
        $field_manager = \Drupal::service('entity_field.manager');
        
        // 1. Identify the bundle.
        $bundle = $bundle_machine_name ?: ($row['bundle'] ?? $row['Bundle'] ?? NULL);
        if (!$bundle) {
            $this->logger->warning('Row skipped: Missing bundle information.');
            return FALSE;
        }

        // 2. Map Labels to Machine Names if necessary.
        $field_definitions = $field_manager->getFieldDefinitions($entity_type_id, $bundle);
        $label_map = [];
        foreach ($field_definitions as $name => $definition) {
            $label_map[(string) $definition->getLabel()] = $name;
            $label_map[$name] = $name; // Also map machine names to themselves.
        }

        $data = [];
        foreach ($row as $key => $value) {
            if (isset($label_map[$key])) {
                $data[$label_map[$key]] = $value;
            }
        }

        // 3. Upsert logic: Try ID first, then UUID, then Label+Bundle.
        $entity = NULL;
        $id = $data['id'] ?? $data['ID'] ?? NULL;
        if ($id && is_numeric($id)) {
            $entity = $storage->load($id);
        }

        if (!$entity && !empty($data['uuid'])) {
            $entities = $storage->loadByProperties(['uuid' => $data['uuid']]);
            $entity = reset($entities);
        }

        if (!$entity && !empty($data['label'])) {
            $query = $storage->getQuery()
                ->condition('bundle', $bundle)
                ->condition('label', $data['label'])
                ->accessCheck(FALSE)
                ->range(0, 1);
            $ids = $query->execute();
            if (!empty($ids)) {
                $entity = $storage->load(reset($ids));
            }
        }

        try {
            if ($entity) {
                $this->logger->info('Updating actor: @label (@bundle)', ['@label' => $data['label'] ?? $entity->label(), '@bundle' => $bundle]);
            } else {
                if (empty($data['label'])) {
                    $this->logger->warning('Row skipped: Missing label for new entity.');
                    return FALSE;
                }
                $entity = $storage->create(['bundle' => $bundle, 'label' => $data['label']]);
                $this->logger->info('Creating actor: @label (@bundle)', ['@label' => $data['label'], '@bundle' => $bundle]);
            }

            // 4. Map and set field values.
            foreach ($data as $field_name => $value) {
                // Skip read-only or internal fields.
                if (in_array($field_name, ['id', 'uuid', 'bundle', 'created', 'changed', 'uid'])) {
                    continue;
                }

                if ($entity->hasField($field_name)) {
                    $value = trim((string)$value);
                    // Skip '0' if it was added as a placeholder for empty, or just handle empty.
                    if ($value !== '' && $value !== '0') {
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

        if ($type === 'entity_reference' || $type === 'entity_reference_revisions' || $type === 'image' || $type === 'file') {
            // Handle complex ID formats:
            // "ID 91|gateway||2000|366|file|..."
            // "8|8|paragraph|5f059527-87e7-4325-a8e0-bef3fd97b675"
            $target_id = NULL;
            $target_revision_id = NULL;

            if (strpos($value, '|') !== FALSE) {
                $parts = explode('|', $value);
                $first_part = trim($parts[0]);
                // Search for "ID 91" or just "91"
                if (preg_match('/(?:ID\s+)?(\d+)/i', $first_part, $matches)) {
                    $target_id = $matches[1];
                }
                // For ERR, the second part might be the revision ID
                if ($type === 'entity_reference_revisions' && isset($parts[1]) && is_numeric($parts[1])) {
                    $target_revision_id = $parts[1];
                }
            } else {
                $target_id = $value;
            }

            if ($type === 'entity_reference' || $type === 'entity_reference_revisions') {
                $target_type = $field_definition->getSetting('target_type');
                if ($target_type === 'taxonomy_term') {
                    $handler_settings = $field_definition->getSetting('handler_settings');
                    $target_bundles = $handler_settings['target_bundles'] ?? [];
                    $bundle = reset($target_bundles);

                    $tid = NULL;
                    if (is_numeric($target_id)) {
                        $tid = $target_id;
                    } else {
                        $tid = $this->lookupTerm($target_id, $bundle);
                    }

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
                    // Generic reference by ID.
                    if ($type === 'entity_reference_revisions') {
                        $entity->set($field_name, [
                            'target_id' => $target_id,
                            'target_revision_id' => $target_revision_id ?: $target_id,
                        ]);
                    } else {
                        $entity->set($field_name, $target_id);
                    }
                }
            } else {
                // Image or File field: directly set the target_id.
                $entity->set($field_name, ['target_id' => $target_id]);
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
        if ($name === '0' || $name === 0) {
            return NULL;
        }
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
