<?php

namespace Drupal\zinco_etl\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Batch\BatchBuilder;
use Drupal\file\Entity\File;
use Drupal\Core\File\FileSystemInterface;

/**
 * Form for merging CSV data with database tables.
 */
class BatchMergeForm extends FormBase
{

    /**
     * The database connection.
     *
     * @var \Drupal\Core\Database\Connection
     */
    protected $database;

    /**
     * The file system service.
     *
     * @var \Drupal\Core\File\FileSystemInterface
     */
    protected $fileSystem;

    /**
     * List of supported tables and their primary/unique keys.
     *
     * @var array
     */
    protected $tables = [
        'investigadores' => [
            'table' => 'data_view_investigadores',
            'title' => 'Investigadores',
            'pk' => 'ID_PERSONA_PR',
        ],
        'grupos_investigacion' => [
            'table' => 'data_grupos_investigacion',
            'title' => 'Grupos de Investigación',
            'pk' => 'cod_grupo',
        ],
        'instituciones_academicas' => [
            'table' => 'data_instituciones_academicas',
            'title' => 'Instituciones Académicas',
            'pk' => 'id',
        ],
        'programas_academicos' => [
            'table' => 'data_programas_academicos',
            'title' => 'Programas Académicos',
            'pk' => 'id',
        ],
        'organizaciones_intermedias' => [
            'table' => 'data_organizaciones_intermedias',
            'title' => 'Organizaciones Intermedias',
            'pk' => 'id',
        ],
        'entidades_gobierno' => [
            'table' => 'data_entidades_gobierno',
            'title' => 'Entidades de Gobierno',
            'pk' => 'id',
        ],
        'instancias_gobierno' => [
            'table' => 'data_instancias_gobierno',
            'title' => 'Instancias de Gobierno',
            'pk' => 'id',
        ],
    ];

    /**
     * Constructs a new BatchMergeForm.
     */
    public function __construct(Connection $database, FileSystemInterface $file_system)
    {
        $this->database = $database;
        $this->fileSystem = $file_system;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('database'),
            $container->get('file_system')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'zinco_etl_batch_merge_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $form['#tree'] = TRUE;
        
        $form['help_text'] = [
            '#markup' => '<div class="messages messages--warning">' . $this->t('Please upload a CSV file for each table you wish to update/merge. The first row must be a header matching the database column names. The merge is performed using the specific primary key for each table.') . '</div>',
        ];

        $form['files'] = [
            '#type' => 'details',
            '#title' => $this->t('Upload CSV Files'),
            '#open' => TRUE,
        ];

        foreach ($this->tables as $key => $info) {
            $form['files'][$key . '_group'] = [
                '#type' => 'container',
                '#attributes' => ['style' => 'margin-bottom: 2em; padding: 1em; border: 1px solid #eee; border-radius: 4px;'],
            ];

            $form['files'][$key . '_group'][$key] = [
                '#type' => 'managed_file',
                '#title' => $this->t('@title (@table)', ['@title' => $info['title'], '@table' => $info['table']]),
                '#description' => $this->t('Upload CSV for @table. Key: @pk', ['@table' => $info['table'], '@pk' => $info['pk']]),
                '#upload_location' => 'public://import/merge',
                '#upload_validators' => [
                    'FileExtension' => ['extensions' => 'csv'],
                ],
            ];

            $form['files'][$key . '_group']['links'] = [
                '#type' => 'markup',
                '#markup' => '<div class="table-actions-links" style="margin-top: 10px;">' . 
                    $this->t('<a href="@url" target="_blank" class="button button--small" style="margin-right: 10px;">🔍 Consultar Datos</a> <a href="@export_url" class="button button--small">📥 Descargar CSV Actual</a>', [
                        '@url' => \Drupal\Core\Url::fromRoute('zinco_etl.table_view', ['table' => $info['table']])->toString(),
                        '@export_url' => \Drupal\Core\Url::fromRoute('zinco_etl.table_export', ['table' => $info['table']])->toString(),
                    ]) . '</div>',
            ];
        }

        $form['options'] = [
            '#type' => 'details',
            '#title' => $this->t('Import Options'),
            '#open' => FALSE,
        ];

        $form['options']['delimiter'] = [
            '#type' => 'select',
            '#title' => $this->t('CSV Delimiter'),
            '#options' => [
                ',' => $this->t('Comma (,)'),
                ';' => $this->t('Semicolon (;)'),
            ],
            '#default_value' => ',',
        ];

        $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Process Batch Merge'),
            '#button_type' => 'primary',
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $files_data = $form_state->getValue('files');
        $delimiter = $form_state->getValue(['options', 'delimiter']);

        $to_process = [];
        foreach ($this->tables as $key => $info) {
            $group_values = $form_state->getValue(['files', $key . '_group']);
            if (!empty($group_values[$key])) {
                $file = File::load(reset($group_values[$key]));
                if ($file) {
                    $to_process[] = [
                        'key' => $key,
                        'file_path' => $this->fileSystem->realpath($file->getFileUri()),
                        'table' => $info['table'],
                        'pk' => $info['pk'],
                    ];
                }
            }
        }

        if (empty($to_process)) {
            $this->messenger()->addWarning($this->t('No files were uploaded.'));
            return;
        }

        $batch_builder = (new BatchBuilder())
            ->setTitle($this->t('Processing Batch Merge'))
            ->setInitMessage($this->t('Starting merge processes...'))
            ->setErrorMessage($this->t('The merge process encountered an error.'));

        foreach ($to_process as $item) {
            $data = $this->parseCsv($item['file_path'], $delimiter);
            if (!empty($data)) {
                foreach ($data as $row) {
                    $batch_builder->addOperation([$this, 'processBatchMergeRow'], [
                        $item['table'],
                        $item['pk'],
                        $row,
                    ]);
                }
            }
        }

        $batch_builder->setFinishCallback([$this, 'batchFinished']);

        batch_set($batch_builder->toArray());
    }

    /**
     * Parses a CSV file and returns data as an array.
     */
    protected function parseCsv(string $file_path, string $delimiter = ','): array
    {
        $rows = [];
        if (($handle = fopen($file_path, 'r')) !== FALSE) {
            $header = fgetcsv($handle, 0, $delimiter);
            if ($header) {
                if (isset($header[0])) {
                    $header[0] = preg_replace('/^[\xEF\xBB\xBF]+/', '', $header[0]);
                }
                $header = array_map('trim', $header);

                while (($data = fgetcsv($handle, 0, $delimiter)) !== FALSE) {
                    if ($data === [NULL] || empty(array_filter($data, 'strlen'))) {
                        continue;
                    }
                    
                    $data_count = count($data);
                    $header_count = count($header);
                    
                    if ($data_count !== $header_count) {
                        if ($data_count < $header_count) {
                            $data = array_pad($data, $header_count, '');
                        } else {
                            $data = array_slice($data, 0, $header_count);
                        }
                    }
                    
                    $rows[] = array_combine($header, $data);
                }
            }
            fclose($handle);
        }
        return $rows;
    }

    /**
     * Batch operation to process a single merge row.
     */
    public static function processBatchMergeRow($table, $pk, $row, &$context)
    {
        if (!isset($context['results']['processed'])) {
            $context['results']['processed'] = 0;
            $context['results']['success'] = 0;
            $context['results']['failed'] = 0;
        }

        /** @var \Drupal\Core\Database\Connection $database */
        $database = \Drupal::database();

        try {
            // Filter row data to remove keys that are not in the table if possible,
            // but db_merge will fail if columns don't exist.
            // For now, we assume CSV headers match columns.
            
            if (!isset($row[$pk])) {
                throw new \Exception("Missing primary key column '$pk' in CSV row.");
            }

            $pk_value = $row[$pk];
            unset($row[$pk]); // Remove PK from fields to update, it's used in the condition.

            $query = $database->merge($table)
                ->key([$pk => $pk_value])
                ->fields($row)
                ->execute();

            $context['results']['success']++;
        } catch (\Exception $e) {
            $context['results']['failed']++;
            \Drupal::logger('zinco_etl_merge')->error('Error merging into @table: @message', [
                '@table' => $table,
                '@message' => $e->getMessage(),
            ]);
        }

        $context['results']['processed']++;
    }

    /**
     * Batch finished callback.
     */
    public static function batchFinished($success, $results, $operations)
    {
        $messenger = \Drupal::messenger();
        if ($success) {
            $messenger->addStatus(t('Batch merge completed. Processed @processed rows (@success success, @failed failed).', [
                '@processed' => $results['processed'] ?? 0,
                '@success' => $results['success'] ?? 0,
                '@failed' => $results['failed'] ?? 0,
            ]));
        } else {
            $messenger->addError(t('Batch merge finished with errors. Check logs for details.'));
        }
    }

}
