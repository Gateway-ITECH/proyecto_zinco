<?php

namespace Drupal\zinco_etl\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\zinco_etl\Service\ActorImportService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Batch\BatchBuilder;
use Drupal\file\Entity\File;
use Drupal\Core\File\FileSystemInterface;

/**
 * Form for importing actors from CSV.
 */
class ActorImportForm extends FormBase
{

    /**
     * The actor import service.
     *
     * @var \Drupal\zinco_etl\Service\ActorImportService
     */
    protected $actorImportService;

    /**
     * Constructs a new ActorImportForm.
     */
    public function __construct(ActorImportService $actor_import_service)
    {
        $this->actorImportService = $actor_import_service;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('zinco_etl.actor_import')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'zinco_etl_actor_import_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $form['csv_file'] = [
            '#type' => 'managed_file',
            '#title' => $this->t('CSV File'),
            '#description' => $this->t('Upload the CSV or Excel source file. Currently only CSV is supported.'),
            '#upload_location' => 'public://import',
            '#upload_validators' => [
                'FileExtension' => ['extensions' => 'csv'],
            ],
            '#required' => TRUE,
        ];

        $form['delimiter'] = [
            '#type' => 'select',
            '#title' => $this->t('Delimiter'),
            '#options' => [
                ',' => $this->t('Comma (,)'),
                ';' => $this->t('Semicolon (;)'),
            ],
            '#default_value' => ',',
        ];

        $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Start Import'),
            '#button_type' => 'primary',
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $file_id = $form_state->getValue('csv_file');
        $delimiter = $form_state->getValue('delimiter');

        if (!$file_id) {
            return;
        }

        $file = File::load(reset($file_id));
        if (!$file) {
            return;
        }

        $file_path = \Drupal::service('file_system')->realpath($file->getFileUri());
        $data = $this->actorImportService->parseCsv($file_path, $delimiter);

        if (empty($data)) {
            $this->messenger()->addError($this->t('The CSV file is empty or could not be parsed.'));
            return;
        }

        $batch_builder = (new BatchBuilder())
            ->setTitle($this->t('Importing Actors'))
            ->setInitMessage($this->t('Starting import...'))
            ->setProgressMessage($this->t('Processed @current out of @total rows.'))
            ->setErrorMessage($this->t('Import encountered an error.'));

        foreach ($data as $row) {
            $batch_builder->addOperation([$this, 'processBatchRow'], [$row]);
        }

        $batch_builder->setFinishCallback([$this, 'batchFinished']);

        batch_set($batch_builder->toArray());
    }

    /**
     * Batch operation to process a single row.
     */
    public static function processBatchRow($row, &$context)
    {
        /** @var \Drupal\zinco_etl\Service\ActorImportService $service */
        $service = \Drupal::service('zinco_etl.actor_import');
        $success = $service->processRow($row);

        if (!isset($context['results']['processed'])) {
            $context['results']['processed'] = 0;
            $context['results']['success'] = 0;
            $context['results']['failed'] = 0;
        }

        $context['results']['processed']++;
        if ($success) {
            $context['results']['success']++;
        } else {
            $context['results']['failed']++;
        }
    }

    /**
     * Batch finished callback.
     */
    public static function batchFinished($success, $results, $operations)
    {
        $messenger = \Drupal::messenger();
        if ($success) {
            $messenger->addStatus(t('Import completed. Processed @processed rows (@success success, @failed failed).', [
                '@processed' => $results['processed'] ?? 0,
                '@success' => $results['success'] ?? 0,
                '@failed' => $results['failed'] ?? 0,
            ]));
        } else {
            $messenger->addError(t('Import finished with errors.'));
        }
    }

}
