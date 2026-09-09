<?php

namespace Drupal\zinco_etl\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\Core\Url;
use Drupal\zinco_etl\ZincoEtlTableRegistry;

/**
 * Form to create or edit a single row of an ETL-managed table.
 */
class RecordForm extends FormBase
{

    /**
     * The database connection.
     *
     * @var \Drupal\Core\Database\Connection
     */
    protected $database;

    /**
     * Constructs a new RecordForm.
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
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'zinco_etl_record_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, string $table = '', $id = NULL)
    {
        if (!ZincoEtlTableRegistry::isKnownTable($table)) {
            throw new NotFoundHttpException();
        }

        $pk = ZincoEtlTableRegistry::getPrimaryKey($table);

        $record = NULL;
        if ($id !== NULL) {
            $record = $this->database->select($table, 't')
                ->fields('t')
                ->condition($pk, $id)
                ->execute()
                ->fetchAssoc();
            if (!$record) {
                throw new NotFoundHttpException();
            }
        }

        // Determine the table's columns the same way TableViewController does.
        $columns = array_keys($this->database->query("SELECT * FROM {" . $table . "} LIMIT 1")->fetchAssoc() ?: []);
        if (empty($columns)) {
            $columns = $this->database->query("DESCRIBE {" . $table . "}")->fetchAllCol();
        }

        $form['#tree'] = TRUE;
        $form['table'] = ['#type' => 'value', '#value' => $table];
        $form['pk'] = ['#type' => 'value', '#value' => $pk];
        $form['record_id'] = ['#type' => 'value', '#value' => $id];

        foreach ($columns as $col) {
            $form['fields'][$col] = [
                '#type' => 'textfield',
                '#title' => $col,
                '#default_value' => $record[$col] ?? '',
            ];
            if ($col === $pk) {
                $form['fields'][$col]['#required'] = TRUE;
                // The primary key identifies the row; don't allow changing it on edit.
                if ($id !== NULL) {
                    $form['fields'][$col]['#disabled'] = TRUE;
                }
            }
        }

        $form['actions'] = ['#type' => 'actions'];
        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $id !== NULL ? $this->t('Guardar cambios') : $this->t('Crear registro'),
            '#button_type' => 'primary',
        ];
        $form['actions']['cancel'] = [
            '#type' => 'link',
            '#title' => $this->t('Cancelar'),
            '#url' => Url::fromRoute('zinco_etl.table_view', ['table' => $table]),
            '#attributes' => ['class' => ['button']],
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        if ($form_state->getValue('record_id') === NULL) {
            $table = $form_state->getValue('table');
            $pk = $form_state->getValue('pk');
            $values = $form_state->getValue('fields');

            $exists = $this->database->select($table, 't')
                ->fields('t', [$pk])
                ->condition($pk, $values[$pk])
                ->execute()
                ->fetchField();
            if ($exists !== FALSE) {
                $form_state->setErrorByName('fields][' . $pk, $this->t('Ya existe un registro con la clave primaria "@value".', ['@value' => $values[$pk]]));
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $table = $form_state->getValue('table');
        $pk = $form_state->getValue('pk');
        $record_id = $form_state->getValue('record_id');
        $values = $form_state->getValue('fields');

        if ($record_id !== NULL) {
            unset($values[$pk]);
            $this->database->update($table)
                ->fields($values)
                ->condition($pk, $record_id)
                ->execute();
            $this->messenger()->addStatus($this->t('Registro actualizado correctamente.'));
        }
        else {
            $this->database->insert($table)
                ->fields($values)
                ->execute();
            $this->messenger()->addStatus($this->t('Registro creado correctamente.'));
        }

        $form_state->setRedirectUrl(Url::fromRoute('zinco_etl.table_view', ['table' => $table]));
    }

}
