<?php

namespace Drupal\zinco_front\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Zinco Front dashboard settings.
 */
class ZincoDashboardConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'zinco_front_dashboard_config';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['zinco_front.dashboard.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('zinco_front.dashboard.settings');
    $tables_data = $config->get('tables_data') ?: [];

    $form['tables_data'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Table Name'),
        $this->t('Municipio'),
        $this->t('Sector'),
        $this->t('Tecnología 4.0'),
        $this->t('Operations'),
      ],
      '#empty' => $this->t('No tables configured yet.'),
      '#tablesorter' => TRUE,
      '#attributes' => ['id' => 'zinco-dashboard-tables'],
    ];

    // Add existing rows.
    foreach ($tables_data as $id => $data) {
      $form['tables_data'][$id]['table_name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Table Name'),
        '#title_display' => 'invisible',
        '#default_value' => $data['table_name'],
        '#attributes' => ['readonly' => 'readonly'],
      ];
      $form['tables_data'][$id]['municipio'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Municipio'),
        '#title_display' => 'invisible',
        '#default_value' => $data['municipio'],
      ];
      $form['tables_data'][$id]['sector'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Sector'),
        '#title_display' => 'invisible',
        '#default_value' => $data['sector'],
      ];
      $form['tables_data'][$id]['tecnologia40'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Tecnología 4.0'),
        '#title_display' => 'invisible',
        '#default_value' => $data['tecnologia40'],
      ];
      $form['tables_data'][$id]['operations'] = [
        '#type' => 'operations',
        '#links' => [
          'delete' => [
            'title' => $this->t('Delete'),
            'url' => \Drupal\Core\Url::fromRoute('zinco_front.dashboard_config_delete', ['id' => $id]),
          ],
        ],
      ];
    }

    // Add a new row for adding records.
    $form['tables_data']['new_row'] = [
      'table_name' => [
        '#type' => 'textfield',
        '#title' => $this->t('New Table Name'),
        '#title_display' => 'invisible',
        '#placeholder' => $this->t('Enter new table name'),
      ],
      'municipio' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Municipio'),
        '#title_display' => 'invisible',
      ],
      'sector' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Sector'),
        '#title_display' => 'invisible',
      ],
      'tecnologia40' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Tecnología 4.0'),
        '#title_display' => 'invisible',
      ],
      'operations' => [
        '#type' => 'submit',
        '#value' => $this->t('Add'),
        '#submit' => ['::addTable'],
        '#name' => 'add_table',
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $new_table_name = $form_state->getValue(['tables_data', 'new_row', 'table_name']);
    if (!empty($new_table_name)) {
      $config = $this->config('zinco_front.dashboard.settings');
      $tables_data = $config->get('tables_data') ?: [];
      foreach ($tables_data as $data) {
        if ($data['table_name'] === $new_table_name) {
          $form_state->setErrorByName('tables_data][new_row][table_name', $this->t('This table name already exists.'));
          break;
        }
      }
    }
  }

  /**
   * Submit handler for adding a new table.
   */
  public function addTable(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory()->getEditable('zinco_front.dashboard.settings');
    $tables_data = $config->get('tables_data') ?: [];

    $new_table_name = $form_state->getValue(['tables_data', 'new_row', 'table_name']);
    if (!empty($new_table_name)) {
      $tables_data[] = [
        'table_name' => $new_table_name,
        'municipio' => $form_state->getValue(['tables_data', 'new_row', 'municipio']),
        'sector' => $form_state->getValue(['tables_data', 'new_row', 'sector']),
        'tecnologia40' => $form_state->getValue(['tables_data', 'new_row', 'tecnologia40']),
      ];
      $config->set('tables_data', $tables_data)->save();
      $this->messenger()->addStatus($this->t('Table %name has been added.', ['%name' => $new_table_name]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory()->getEditable('zinco_front.dashboard.settings');
    $tables_data = $form_state->getValue('tables_data');

    // Remove the 'new_row' and update existing rows.
    unset($tables_data['new_row']);
    $config->set('tables_data', $tables_data)->save();

    parent::submitForm($form, $form_state);
  }

}