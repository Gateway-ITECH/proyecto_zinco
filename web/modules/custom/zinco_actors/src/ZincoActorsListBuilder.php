<?php

declare(strict_types=1);

namespace Drupal\zinco_actors;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Provides a list controller for the zincoactors entity type.
 */
final class ZincoActorsListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build['filter_form'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['form-inline', 'mb-3']],
      'search_form' => [
        '#type' => 'html_tag',
        '#tag' => 'form',
        '#attributes' => [
          'method' => 'get',
          'action' => \Drupal\Core\Url::fromRoute('entity.zinco_actors_zincoactors.collection')->toString(),
        ],
        'label' => [
          '#type' => 'textfield',
          '#name' => 'label',
          '#title' => $this->t('Buscar por nombre'),
          '#title_display' => 'invisible',
          '#default_value' => \Drupal::request()->query->get('label') ?? '',
          '#size' => 40,
          '#attributes' => ['placeholder' => $this->t('Ingresa una palabra clave...')],
        ],
        'submit' => [
          '#type' => 'html_tag',
          '#tag' => 'input',
          '#attributes' => [
            'type' => 'submit',
            'value' => $this->t('Buscar'),
            'class' => ['button', 'button--primary'],
          ],
        ],
        'reset' => [
          '#type' => 'link',
          '#title' => $this->t('Limpiar'),
          '#url' => \Drupal\Core\Url::fromRoute('entity.zinco_actors_zincoactors.collection'),
          '#attributes' => ['class' => ['button']],
        ],
      ],
    ];
    $build['table'] = parent::render();
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['id'] = $this->t('ID');
    $header['label'] = $this->t('Label');    
    $header['status'] = $this->t('Status');
    $header['bundle'] = $this->t('Tipo');
    $header['uid'] = $this->t('Author');
    $header['created'] = $this->t('Created');
    $header['changed'] = $this->t('Updated');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\zinco_actors\ZincoActorsInterface $entity */
    $row['id'] = $entity->id();
    $row['label'] = $entity->label() ? $entity->toLink() : 'NA';
    $row['status'] = $entity->get('status')->value ? $this->t('Enabled') : $this->t('Disabled');
    $row['bundle'] = $entity->bundle();
    $username_options = [
      'label' => 'hidden',
      'settings' => ['link' => $entity->get('uid')->entity->isAuthenticated()],
    ];
    $row['uid']['data'] = $entity->get('uid')->view($username_options);
    $row['created']['data'] = $entity->get('created')->view(['label' => 'hidden']);
    $row['changed']['data'] = $entity->get('changed')->view(['label' => 'hidden']);
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   *
   * Orders the list by creation date descending (newest first).
   */
  protected function getEntityIds(): array {
    $query = $this->getStorage()->getQuery()
      ->accessCheck(TRUE)
      ->sort('created', 'DESC');

    $search = \Drupal::request()->query->get('label');
    if (!empty($search)) {
      $query->condition('label', '%' . $search . '%', 'LIKE');
    }

    if ($this->limit) {
      $query->pager($this->limit);
    }

    return $query->execute();
  }

}
