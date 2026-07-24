<?php

namespace Drupal\zinco_etl\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Drupal\Core\Url;
use Drupal\zinco_etl\Form\TableFilterForm;
use Drupal\zinco_etl\ZincoEtlTableRegistry;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
     * The form builder.
     *
     * @var \Drupal\Core\Form\FormBuilderInterface
     */
    protected $formBuilder;

    /**
     * Constructs a new TableViewController.
     */
    public function __construct(Connection $database, FormBuilderInterface $form_builder)
    {
        $this->database = $database;
        $this->formBuilder = $form_builder;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('database'),
            $container->get('form_builder')
        );
    }

    /**
     * Renders a table view with pager and filters.
     */
    public function view(string $table, Request $request)
    {
        if (!ZincoEtlTableRegistry::isKnownTable($table)) {
            throw new NotFoundHttpException();
        }

        if (!$this->database->schema()->tableExists($table)) {
            return [
                '#markup' => $this->t('Table "@table" does not exist.', ['@table' => $table]),
            ];
        }

        $pk = ZincoEtlTableRegistry::getPrimaryKey($table);
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

        $header = $columns;
        if ($pk !== NULL) {
            $header[] = $this->t('Acciones');
        }

        $rows = [];
        foreach ($result as $record) {
            $record_array = (array) $record;
            $cells = array_values($record_array);

            if ($pk !== NULL && isset($record_array[$pk]) && $record_array[$pk] !== NULL && $record_array[$pk] !== '') {
                $pk_value = $record_array[$pk];
                $cells[] = [
                    'data' => [
                        '#type' => 'container',
                        '#attributes' => ['style' => 'white-space: nowrap;'],
                        'edit' => [
                            '#type' => 'link',
                            '#title' => $this->t('✏️ Editar'),
                            '#url' => Url::fromRoute('zinco_etl.record_edit', ['table' => $table, 'id' => $pk_value]),
                            '#attributes' => ['class' => ['button', 'button--small'], 'style' => 'margin-right: 6px;'],
                        ],
                        'delete' => [
                            '#type' => 'link',
                            '#title' => $this->t('🗑️ Eliminar'),
                            '#url' => Url::fromRoute('zinco_etl.record_delete', ['table' => $table, 'id' => $pk_value]),
                            '#attributes' => ['class' => ['button', 'button--small', 'button--danger']],
                        ],
                    ],
                ];
            }
            elseif ($pk !== NULL) {
                $cells[] = '';
            }

            $rows[] = $cells;
        }

        $build = [];

        // Render filter form via Form API (GET-based).
        $build['filter_form'] = $this->formBuilder->getForm(TableFilterForm::class, $table, (string) $search);

        // Add / export actions.
        if ($pk !== NULL) {
            $build['add_link'] = [
                '#type' => 'link',
                '#title' => $this->t('➕ Agregar Registro'),
                '#url' => Url::fromRoute('zinco_etl.record_add', ['table' => $table]),
                '#attributes' => ['class' => ['button', 'button--action'], 'style' => 'margin-bottom: 1em; margin-right: 8px; display: inline-block;'],
            ];
        }

        $build['export_link'] = [
            '#type' => 'link',
            '#title' => $this->t('📥 Exportar CSV'),
            '#url' => Url::fromRoute('zinco_etl.table_export', ['table' => $table], ['query' => ['search' => $search]]),
            '#attributes' => ['class' => ['button', 'button--primary'], 'style' => 'margin-bottom: 1em; display: inline-block;'],
        ];

        $build['table'] = [
            '#type' => 'table',
            '#header' => $header,
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
        if (!ZincoEtlTableRegistry::isKnownTable($table)) {
            throw new NotFoundHttpException();
        }

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
