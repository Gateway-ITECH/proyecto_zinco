<?php

declare(strict_types=1);

namespace Drupal\zinco_reconocimientos;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Url;

/**
 * Provides a list controller for the zinco reconocimientos entity type.
 */
final class ZincoReconocimientosListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['id'] = $this->t('ID');
    $header['label'] = $this->t('Asunto / Solicitud');
    $header['actor'] = $this->t('Actor Solicitante');
    $header['tipo_solicitado'] = $this->t('Tipo de Actor Solicitado');
    $header['usuario'] = $this->t('Usuario Originador');
    $header['estado_proceso'] = $this->t('Estado');
    $header['created'] = $this->t('Fecha');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\zinco_reconocimientos\ZincoReconocimientosInterface $entity */
    $row['id'] = $entity->id();
    $row['label'] = $entity->toLink();

    // Actor solicitante
    $actor_name = '-';
    if ($entity->hasField('field_actor_asociado') && !$entity->get('field_actor_asociado')->isEmpty()) {
      $actor = $entity->get('field_actor_asociado')->entity;
      if ($actor) {
        $actor_name = $actor->label();
      }
    }
    $row['actor'] = $actor_name;

    // Tipo de actor solicitado
    $tipo_solicitado = '-';
    if ($entity->hasField('description') && !$entity->get('description')->isEmpty()) {
      $meta = json_decode($entity->get('description')->value, TRUE);
      if (!empty($meta['tipo_actor_label'])) {
        $tipo_solicitado = $meta['tipo_actor_label'];
      }
    }
    $row['tipo_solicitado'] = $tipo_solicitado;

    // Usuario originador
    $owner = $entity->getOwner();
    if ($owner && $owner->isAuthenticated()) {
      $row['usuario'] = [
        'data' => [
          '#markup' => '<strong>' . htmlspecialchars($owner->getDisplayName(), ENT_QUOTES, 'UTF-8') . '</strong><br><small style="color:#6c757d;">' . htmlspecialchars($owner->getEmail(), ENT_QUOTES, 'UTF-8') . '</small>',
        ],
      ];
    }
    else {
      $row['usuario'] = $this->t('Anónimo / No identificado');
    }

    // Estado del proceso
    $has_response = $entity->hasField('field_respuesta_solicitud') && !$entity->get('field_respuesta_solicitud')->isEmpty();
    $status_badge = $has_response 
      ? '<span style="background-color:#28a745;color:#fff;padding:3px 8px;border-radius:4px;font-size:12px;font-weight:600;">' . $this->t('Respondida') . '</span>'
      : '<span style="background-color:#ffc107;color:#212529;padding:3px 8px;border-radius:4px;font-size:12px;font-weight:600;">' . $this->t('Pendiente') . '</span>';
    $row['estado_proceso'] = ['data' => ['#markup' => $status_badge]];

    // Fecha
    $row['created']['data'] = $entity->get('created')->view(['label' => 'hidden']);

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultOperations(EntityInterface $entity): array {
    $operations = parent::getDefaultOperations($entity);

    $operations['view_front'] = [
      'title' => $this->t('Ver detalle'),
      'weight' => -10,
      'url' => Url::fromRoute('zinco_front.reconocimiento_detail', ['reconocimiento_id' => $entity->id()]),
    ];

    $operations['respond'] = [
      'title' => $this->t('Responder solicitud'),
      'weight' => -5,
      'url' => Url::fromRoute('zinco_front.reconocimiento_respond', ['reconocimiento_id' => $entity->id()]),
    ];

    $operations['enviar_acceso'] = [
      'title' => $this->t('Enviar acceso para nuevo actor'),
      'weight' => 0,
      'url' => Url::fromRoute('zinco_reconocimientos.enviar_acceso', ['reconocimiento_id' => $entity->id()]),
    ];

    return $operations;
  }

}
