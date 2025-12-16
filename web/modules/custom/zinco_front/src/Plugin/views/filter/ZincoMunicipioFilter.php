<?php

namespace Drupal\zinco_front\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\InOperator;

/**
 * Filter by municipality.
 *
 * @ViewsFilter("zinco_municipio_filter")
 */
class ZincoMunicipioFilter extends InOperator {

  /**
   * {@inheritdoc}
   */
  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {
    if (!isset($this->valueOptions)) {
      $this->valueOptions = [];
      $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('divipola');
      $cordoba_tid = NULL;

      foreach ($terms as $term) {
        if ($term->name == 'Córdoba') {
          $cordoba_tid = $term->tid;
          break;
        }
      }

      if ($cordoba_tid) {
        $children = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadChildren($cordoba_tid);
        foreach ($children as $child) {
          $this->valueOptions[$child->id()] = $child->getName();
        }
      }
    }
    return $this->valueOptions;
  }

  

}
