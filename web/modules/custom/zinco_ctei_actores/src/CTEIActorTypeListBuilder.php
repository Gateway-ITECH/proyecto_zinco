<?php

declare(strict_types=1);

namespace Drupal\zinco_ctei_actores;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of cteiactor type entities.
 *
 * @see \Drupal\zinco_ctei_actores\Entity\CTEIActorType
 */
final class CTEIActorTypeListBuilder extends ConfigEntityListBuilder {

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
      'No cteiactor types available. <a href=":link">Add cteiactor type</a>.',
      [':link' => Url::fromRoute('entity.cteiactor_type.add_form')->toString()],
    );

    return $build;
  }

}
