<?php

namespace Drupal\zinco_front\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Provides a ZincoController.
 */
class ZincoController extends ControllerBase {

  /**
   * Returns a 'Hello Zinco Front' page.
   *
   * @return array
   *   A simple renderable array.
   */
  public function home() {
    return [
      '#type' => 'markup',
      '#markup' => $this->t('Hello Zinco Front'),
    ];
  }

}