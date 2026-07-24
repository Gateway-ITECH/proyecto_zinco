<?php

namespace Drupal\zinco_etl\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\Core\Url;
use Drupal\zinco_etl\ZincoEtlTableRegistry;

/**
 * Confirmation form to delete a single row of an ETL-managed table.
 */
class RecordDeleteForm extends ConfirmFormBase
{

    /**
     * The database connection.
     *
     * @var \Drupal\Core\Database\Connection
     */
    protected $database;

    /**
     * The table being edited.
     *
     * @var string
     */
    protected $table;

    /**
     * The primary key column for the table.
     *
     * @var string
     */
    protected $pk;

    /**
     * The primary key value of the row to delete.
     *
     * @var mixed
     */
    protected $recordId;

    /**
     * Constructs a new RecordDeleteForm.
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
        return 'zinco_etl_record_delete_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, string $table = '', $id = NULL)
    {
        if (!ZincoEtlTableRegistry::isKnownTable($table)) {
            throw new NotFoundHttpException();
        }

        $this->table = $table;
        $this->pk = ZincoEtlTableRegistry::getPrimaryKey($table);
        $this->recordId = $id;

        $exists = $this->database->select($table, 't')
            ->fields('t', [$this->pk])
            ->condition($this->pk, $id)
            ->execute()
            ->fetchField();
        if ($exists === FALSE) {
            throw new NotFoundHttpException();
        }

        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function getQuestion()
    {
        return $this->t('¿Eliminar el registro "@id" de @table?', [
            '@id' => $this->recordId,
            '@table' => $this->table,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return $this->t('Esta acción no se puede deshacer.');
    }

    /**
     * {@inheritdoc}
     */
    public function getCancelUrl()
    {
        return Url::fromRoute('zinco_etl.table_view', ['table' => $this->table]);
    }

    /**
     * {@inheritdoc}
     */
    public function getConfirmText()
    {
        return $this->t('Eliminar');
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $this->database->delete($this->table)
            ->condition($this->pk, $this->recordId)
            ->execute();

        $this->messenger()->addStatus($this->t('Registro eliminado correctamente.'));
        $form_state->setRedirectUrl($this->getCancelUrl());
    }

}
