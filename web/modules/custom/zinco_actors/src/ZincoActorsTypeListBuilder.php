<?php

declare(strict_types=1);

namespace Drupal\zinco_actors;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of zincoactors type entities.
 *
 * @see \Drupal\zinco_actors\Entity\ZincoActorsType
 */
final class ZincoActorsTypeListBuilder extends ConfigEntityListBuilder
{

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array
  {
    $header['label'] = $this->t('Label');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array
  {
    $row['label'] = $entity->label();
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render(): array
  {
    $build = parent::render();

    $build['table']['#empty'] = $this->t(
      'No hay tipos de actores disponibles. <a href=":link">Agregar tipo de actor</a>.',
      [':link' => Url::fromRoute('entity.zinco_actors_zincoactors_type.add_form')->toString()],
    );

    return $build;
  }

}
