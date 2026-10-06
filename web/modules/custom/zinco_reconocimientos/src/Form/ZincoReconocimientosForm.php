<?php

declare(strict_types=1);

namespace Drupal\zinco_reconocimientos\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the zinco reconocimientos entity edit forms.
 */
class ZincoReconocimientosForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);

    $message_args = ['%label' => $this->entity->toLink()->toString()];
    $logger_args = [
      '%label' => $this->entity->label(),
      'link' => $this->entity->toLink($this->t('View'))->toString(),
    ];

    switch ($result) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('New zinco reconocimientos %label has been created.', $message_args));
        $this->logger('zinco_reconocimientos')->notice('New zinco reconocimientos %label has been created.', $logger_args);
        break;

      case SAVED_UPDATED:
        $this->messenger()->addStatus($this->t('The zinco reconocimientos %label has been updated.', $message_args));
        $this->logger('zinco_reconocimientos')->notice('The zinco reconocimientos %label has been updated.', $logger_args);
        break;

      default:
        throw new \LogicException('Could not save the entity.');
    }

    if ($result === SAVED_NEW) {
      $this->notifyAdmins($this->entity);
    }

    $form_state->setRedirectUrl($this->entity->toUrl());

    return $result;
  }

  /**
   * Notifies administrators about a new recognition request.
   */
  protected function notifyAdmins($entity) {
    $mail_service = \Drupal::service('zinco_front.mail_service');
    
    // Get all users with administrator role.
    $ids = \Drupal::entityQuery('user')
      ->condition('status', 1)
      ->condition('roles', 'administrator')
      ->accessCheck(FALSE)
      ->execute();
    $admins = \Drupal::entityTypeManager()->getStorage('user')->loadMultiple($ids);
    
    $actor_name = 'N/A';
    $actor_bundle_label = '';
    if ($entity->hasField('field_actor_asociado') && !$entity->get('field_actor_asociado')->isEmpty()) {
      $actor_entity = $entity->get('field_actor_asociado')->entity;
      if ($actor_entity) {
        $actor_name = $actor_entity->label();
        $bundle_info = \Drupal::service('entity_type.bundle.info')->getBundleInfo('zinco_actors_zincoactors');
        $actor_bundle_label = $bundle_info[$actor_entity->bundle()]['label'] ?? $actor_entity->bundle();
      }
    }

    $owner = $entity->getOwner();
    $user_name = ($owner && $owner->isAuthenticated()) ? $owner->getDisplayName() : 'No identificado';
    $user_email = ($owner && $owner->isAuthenticated()) ? $owner->getEmail() : 'No disponible';

    $tipo_actor_solicitado = 'No especificado';
    if ($entity->hasField('description') && !$entity->get('description')->isEmpty()) {
      $meta = json_decode($entity->get('description')->value, TRUE);
      if (!empty($meta['tipo_actor_label'])) {
        $tipo_actor_solicitado = $meta['tipo_actor_label'];
      }
    }

    $variables = [
      'actor_name' => $actor_name,
      'actor_bundle_label' => $actor_bundle_label,
      'user_name' => $user_name,
      'user_email' => $user_email,
      'tipo_actor_solicitado' => $tipo_actor_solicitado,
      'label' => $entity->label(),
      'detail_url' => \Drupal::token()->replace('[site:url]') . 'reconocimientos/' . $entity->id(),
      'admin_url' => \Drupal::token()->replace('[site:url]') . 'admin/content/zinco-reconocimientos',
    ];

    foreach ($admins as $admin) {
      $mail_service->sendTemplatedEmail(
        $admin->getEmail(),
        $this->t('Nueva Solicitud de Reconocimiento (@tipo): @label', [
          '@tipo' => $tipo_actor_solicitado,
          '@label' => $entity->label(),
        ]),
        'email_reconocimiento_nueva_solicitud',
        $variables
      );
    }
  }

}
