<?php

namespace Drupal\zinco_admin\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Provides a Zinco Admin dashboard page.
 */
class AdminController extends ControllerBase {

  /**
   * Displays the Zinco Admin dashboard.
   *
   * @return array
   *   A render array.
   */
  public function dashboard() {
    $tables = [
      'data_view_investigadores' => $this->t('Investigadores'),
      'data_grupos_investigacion' => $this->t('Grupos de Investigación'),
      'data_instituciones_academicas' => $this->t('Instituciones Académicas'),
      'data_programas_academicos' => $this->t('Programas Académicos'),
      'data_organizaciones_intermedias' => $this->t('Organizaciones Intermedias'),
      'data_entidades_gobierno' => $this->t('Entidades de Gobierno'),
      'data_instancias_gobierno' => $this->t('Instancias de Gobierno'),
    ];

    return [
      '#theme' => 'zinco_admin_dashboard',
      '#title' => $this->t('Zinco Admin Dashboard'),
      '#tables' => $tables,
      '#attached' => [
        'library' => [
          'zinco_admin/zinco_admin.admin_styles',
        ],
      ],
    ];
  }

  /**
   * Triggers the news scraping process via Batch API.
   */
  public function scrapeNews() {
    $content_service = \Drupal::service('zinco_front.content_service');
    $batch = $content_service->getScrapeBatch();
    batch_set($batch);
    return batch_process('admin/zinco/dashboard');
  }

}