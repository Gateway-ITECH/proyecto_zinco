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

        $export_url = Url::fromRoute('zinco_etl.table_export', ['table' => $table], ['query' => $request->query->all()])->toString();
        $reset_url = Url::fromRoute('zinco_etl.table_view', ['table' => $table])->toString();
        $current_url = Url::fromRoute('zinco_etl.table_view', ['table' => $table])->toString();
        $search_value = htmlspecialchars($search ?? '', ENT_QUOTES, 'UTF-8');

        $build['filter_form'] = [
            '#markup' => '
            <form method="get" action="' . $current_url . '" style="display:flex; align-items: flex-end; gap: 10px; flex-wrap: wrap; margin-bottom: 16px;">
              <div style="display:flex; flex-direction: column;">
                <label for="table-search" style="font-weight: bold; margin-bottom: 4px;">' . $this->t('Buscar') . '</label>
                <input id="table-search" type="text" name="search" value="' . $search_value . '"
                  style="padding: 6px 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; height: 34px; box-sizing: border-box; min-width: 240px;" />
              </div>
              <button type="submit" class="button" style="height: 34px; padding: 0 16px;">' . $this->t('Filtrar') . '</button>
              <a href="' . $reset_url . '" class="button" style="height: 34px; padding: 0 16px; display:inline-flex; align-items:center;">' . $this->t('Restablecer') . '</a>
              <a href="' . $export_url . '" class="button button--primary" style="height: 34px; padding: 0 16px; display:inline-flex; align-items:center; margin-left: auto;">' . $this->t('Exportar CSV') . '</a>
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
