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
    $config = \Drupal::config('system.site');
    $admin_email = $config->get('mail'); // Site mail as fallback or primary admin.
    
    // Get all users with administrator role.
    $ids = \Drupal::entityQuery('user')
      ->condition('status', 1)
      ->condition('roles', 'administrator')
      ->accessCheck(FALSE)
      ->execute();
    $admins = \Drupal::entityTypeManager()->getStorage('user')->loadMultiple($ids);
    
    $actor_name = 'N/A';
    if ($entity->hasField('field_actor_asociado') && !$entity->get('field_actor_asociado')->isEmpty()) {
      $actor_name = $entity->get('field_actor_asociado')->entity->label();
    }

    $variables = [
      'actor_name' => $actor_name,
      'label' => $entity->label(),
      'detail_url' => \Drupal::token()->replace('[site:url]') . 'reconocimientos/' . $entity->id(),
    ];

    foreach ($admins as $admin) {
      $mail_service->sendTemplatedEmail(
        $admin->getEmail(),
        $this->t('Nueva Solicitud de Reconocimiento: @label', ['@label' => $entity->label()]),
        'email_reconocimiento_nueva_solicitud',
        $variables
      );
    }
  }

}
