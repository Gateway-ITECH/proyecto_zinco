<?php

namespace Drupal\zinco_etl\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Controller for viewing and exporting database table data.
 */
class TableViewController extends ControllerBase
{

    /**
     * The database connection.
     *
     * @var \Drupal\Core\Database\Connection
     */
    protected $database;

    /**
     * Constructs a new TableViewController.
     */
    public function __construct(Connection $database)
    {
        $this->database = $database;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('database')
        );
    }

    /**
     * Renders a table view with pager and filters.
     */
    public function view(string $table, Request $request)
    {
        if (!$this->database->schema()->tableExists($table)) {
            return [
                '#markup' => $this->t('Table "@table" does not exist.', ['@table' => $table]),
            ];
        }

        $search = $request->query->get('search');
        $limit = 50;

        // Get column names.
        $columns = [];
        try {
            $columns = array_keys($this->database->query("SELECT * FROM {" . $table . "} LIMIT 1")->fetchAssoc() ?: []);
            if (empty($columns)) {
                // Fallback for empty tables (MySQL specific).
                $columns = $this->database->query("DESCRIBE {" . $table . "}")->fetchAllCol();
            }
        } catch (\Exception $e) {
            // Silently fail or log.
        }

        $query = $this->database->select($table, 't')
            ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
            ->fields('t')
            ->limit($limit);

        if (!empty($search)) {
            $or = $query->orConditionGroup();
            foreach ($columns as $col) {
                $or->condition($col, '%' . $this->database->escapeLike($search) . '%', 'LIKE');
            }
            $query->condition($or);
        }

        $result = $query->execute()->fetchAll();

        $rows = [];
        foreach ($result as $record) {
            $rows[] = (array) $record;
        }

        $build = [];

        $reset_url = Url::fromRoute('zinco_etl.table_view', ['table' => $table])->toString();
        $export_url = Url::fromRoute('zinco_etl.table_export', ['table' => $table], ['query' => ['search' => $search]])->toString();
        $search_escaped = htmlspecialchars((string) $search, ENT_QUOTES, 'UTF-8');

        // Build a raw HTML form so GET parameters work correctly.
        $build['filter_form'] = [
            '#markup' => '
<form method="get" class="form--inline clearfix" style="margin-bottom: 1em; display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
  <div class="form-item" style="margin: 0;">
    <label for="table-search" style="margin-right: 5px;">' . $this->t('Search') . '</label>
    <input type="text" id="table-search" name="search" value="' . $search_escaped . '" size="30" class="form-text" />
  </div>
  <input type="submit" value="' . $this->t('Filter') . '" class="button" />
  <a href="' . $reset_url . '" class="button">' . $this->t('Reset') . '</a>
  <a href="' . $export_url . '" class="button button--primary" style="margin-left: auto;">' . $this->t('Export CSV') . '</a>
</form>',
        ];

        $build['table'] = [
            '#type' => 'table',
            '#header' => $columns,
            '#rows' => $rows,
            '#empty' => $this->t('No data found.'),
        ];

        $build['pager'] = [
            '#type' => 'pager',
        ];

        return $build;
    }

    /**
     * Exports table data to CSV.
     */
    public function export(string $table, Request $request)
    {
        $search = $request->query->get('search');

        $response = new StreamedResponse(function () use ($table, $search) {
            $handle = fopen('php://output', 'w');
            
            // Get columns.
            $first_row = $this->database->query("SELECT * FROM {" . $table . "} LIMIT 1")->fetchAssoc();
            if ($first_row) {
                $columns = array_keys($first_row);
                fputcsv($handle, $columns);

                $query = $this->database->select($table, 't')
                    ->fields('t');

                if (!empty($search)) {
                    $or = $query->orConditionGroup();
                    foreach ($columns as $col) {
                        $or->condition($col, '%' . $this->database->escapeLike($search) . '%', 'LIKE');
                    }
                    $query->condition($or);
                }

                $result = $query->execute();
                while ($row = $result->fetchAssoc()) {
                    fputcsv($handle, $row);
                }
            }
            
            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $table . '_' . date('Ymd_His') . '.csv"');

        return $response;
    }

    /**
     * Exports zinco actors by bundle to CSV using the Entity API.
     */
    public function exportActors(string $bundle)
    {
        $response = new StreamedResponse(function () use ($bundle) {
            $handle = fopen('php://output', 'w');
            $entity_type_id = 'zinco_actors_zincoactors';
            
            /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $storage */
            $storage = \Drupal::entityTypeManager()->getStorage($entity_type_id);
            
            // Query entity IDs for the specific bundle.
            $query = $storage->getQuery()
                ->condition('bundle', $bundle)
                ->accessCheck(FALSE);
            $ids = $query->execute();

            if (!empty($ids)) {
                /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $field_manager */
                $field_manager = \Drupal::service('entity_field.manager');
                $fields = $field_manager->getFieldDefinitions($entity_type_id, $bundle);

                $header = [];
                $field_names = [];
                foreach ($fields as $field_name => $definition) {
                    // Skip some internal fields if necessary, or include all.
                    // For now, include all as requested.
                    $header[] = (string) $definition->getLabel();
                    $field_names[] = $field_name;
                }
                fputcsv($handle, $header);

                // Process in chunks to manage memory.
                foreach (array_chunk($ids, 50) as $chunk) {
                    $entities = $storage->loadMultiple($chunk);
                    foreach ($entities as $entity) {
                        $row = [];
                        foreach ($field_names as $field_name) {
                            try {
                                $row[] = $entity->get($field_name)->getString();
                            } catch (\Exception $e) {
                                $row[] = '';
                            }
                        }
                        fputcsv($handle, $row);
                    }
                    $storage->resetCache($chunk);
                }
            }
            
            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="actors_' . $bundle . '_' . date('Ymd_His') . '.csv"');

        return $response;
    }

}
