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
    return [
      '#theme' => 'zinco_admin_dashboard',
      '#title' => $this->t('Zinco Admin Dashboard'),
      '#attached' => [
        'library' => [
          'zinco_admin/zinco_admin.admin_styles',
        ],
      ],
    ];
  }

  /**
   * Displays the Zinco Admin actors export page.
   *
   * @return array
   *   A render array.
   */
  public function actorsExportPage() {
    $tables = [];
    $bundle_info = \Drupal::service('entity_type.bundle.info')->getBundleInfo('zinco_actors_zincoactors');
    foreach ($bundle_info as $bundle_id => $info) {
      $tables[$bundle_id] = $info['label'];
    }

    return [
      '#theme' => 'zinco_admin_actors_export',
      '#title' => $this->t('Exportar Datos de Actores CTI'),
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