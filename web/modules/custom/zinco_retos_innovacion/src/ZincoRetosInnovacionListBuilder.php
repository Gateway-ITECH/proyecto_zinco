<?php

declare(strict_types=1);

namespace Drupal\zinco_retos_innovacion;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Provides a list controller for the zinco retos innovacion entity type.
 */
final class ZincoRetosInnovacionListBuilder extends EntityListBuilder {

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
          'action' => \Drupal\Core\Url::fromRoute('entity.zinco_retos_innovacion.collection')->toString(),
        ],
        'label' => [
          '#type' => 'textfield',
          '#name' => 'label',
          '#title' => $this->t('Buscar por título'),
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
          '#url' => \Drupal\Core\Url::fromRoute('entity.zinco_retos_innovacion.collection'),
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
    $header['uid'] = $this->t('Author');
    $header['created'] = $this->t('Created');
    $header['changed'] = $this->t('Updated');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\zinco_retos_innovacion\ZincoRetosInnovacionInterface $entity */
    $row['id'] = $entity->id();
    $row['label'] = $entity->toLink();
    $row['status'] = $entity->get('status')->value ? $this->t('Enabled') : $this->t('Disabled');
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
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);

    $operations['resultados'] = [
      'title' => $this->t('Ver resultados'),
      'weight' => 20,
      'url' => \Drupal\Core\Url::fromRoute('zinco_front.evaluaciones_reto_summary', ['reto_id' => $entity->id()]),
    ];

    return $operations;
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

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }

    return $query->execute();
  }

}
