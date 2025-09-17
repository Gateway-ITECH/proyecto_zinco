<?php

declare(strict_types=1);

namespace Drupal\zinco_producto_servicio;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of zinco producto servicio type entities.
 *
 * @see \Drupal\zinco_producto_servicio\Entity\ZincoProductoServicioType
 */
final class ZincoProductoServicioTypeListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['label'] = $this->t('Label');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $row['label'] = $entity->label();
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render(): array {
    $build = parent::render();

    $build['table']['#empty'] = $this->t(
      'No zinco producto servicio types available. <a href=":link">Add zinco producto servicio type</a>.',
      [':link' => Url::fromRoute('entity.zinco_producto_servicio_type.add_form')->toString()],
    );

    return $build;
  }

}
