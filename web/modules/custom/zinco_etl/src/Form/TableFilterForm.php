<?php

namespace Drupal\zinco_etl\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides an inline GET filter form for table views.
 */
class TableFilterForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'zinco_etl_table_filter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, string $table = '', string $search = '') {
    // Use GET method so the search term appears in the URL.
    $form['#method'] = 'get';
    // Disable CSRF token — not needed for read-only GET searches.
    $form['#token'] = FALSE;

    $form['search'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Buscar'),
      '#default_value' => $search,
      '#size' => 40,
      '#placeholder' => $this->t('Ingrese término de búsqueda...'),
    ];

    $form['actions'] = [
      '#type' => 'actions',
      '#attributes' => ['style' => 'display: inline-flex; gap: 8px;'],
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Filtrar'),
    ];
    $form['actions']['reset'] = [
      '#type' => 'link',
      '#title' => $this->t('Limpiar'),
      '#url' => Url::fromRoute('zinco_etl.table_view', ['table' => $table]),
      '#attributes' => ['class' => ['button']],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // GET forms don't need submit logic; browser handles the redirect.
  }

  /**
   * Remove hidden Drupal form fields that pollute the GET query string.
   */
  public function afterBuild(array $form, FormStateInterface $form_state) {
    unset($form['form_build_id']);
    unset($form['form_token']);
    unset($form['form_id']);
    return $form;
  }

}
