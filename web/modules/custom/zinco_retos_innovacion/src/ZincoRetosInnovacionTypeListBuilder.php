<?php

declare(strict_types=1);

namespace Drupal\zinco_retos_innovacion;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of zinco retos innovacion type entities.
 *
 * @see \Drupal\zinco_retos_innovacion\Entity\ZincoRetosInnovacionType
 */
final class ZincoRetosInnovacionTypeListBuilder extends ConfigEntityListBuilder {

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
      'No zinco retos innovacion types available. <a href=":link">Add zinco retos innovacion type</a>.',
      [':link' => Url::fromRoute('entity.zinco_retos_innovacion_type.add_form')->toString()],
    );

    return $build;
  }

}
