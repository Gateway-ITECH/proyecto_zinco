<?php

namespace Drupal\zinco_reconocimientos\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for registering recognition responses.
 */
class ZincoReconocimientosRespuestaForm extends ZincoReconocimientosForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    // Ocultar campos de la solicitud original para enfocarse en la respuesta.
    $hidden_fields = [
      'label',
      'field_solicitud_de_reconocimient',
      'field_soporte_de_reconocimiento',
      'field_actor_asociado',
      'description',
      'uid',
      'created',
      'status',
    ];

    foreach ($hidden_fields as $field) {
      if (isset($form[$field])) {
        $form[$field]['#access'] = FALSE;
      }
    }

    // Extraer tipo de actor solicitado para mostrarlo en el formulario de respuesta.
    $tipo_actor_label = 'No especificado';
    if ($this->entity->hasField('description') && !$this->entity->get('description')->isEmpty()) {
      $meta = json_decode($this->entity->get('description')->value, TRUE);
      if (!empty($meta['tipo_actor_label'])) {
        $tipo_actor_label = $meta['tipo_actor_label'];
      }
    }

    $owner = $this->entity->getOwner();
    $owner_info = ($owner && $owner->isAuthenticated()) ? $owner->getDisplayName() . ' (' . $owner->getEmail() . ')' : $this->t('No identificado');

    $form['solicitud_info_resumen'] = [
      '#type' => 'item',
      '#markup' => '<div class="alert alert-info border-0 shadow-sm mb-4">' .
        '<div class="mb-1"><strong>' . $this->t('Usuario Originador:') . '</strong> ' . htmlspecialchars((string) $owner_info, ENT_QUOTES, 'UTF-8') . '</div>' .
        '<div><strong>' . $this->t('Tipo de Actor Solicitado:') . '</strong> <span class="badge bg-success text-white">' . htmlspecialchars((string) $tipo_actor_label, ENT_QUOTES, 'UTF-8') . '</span></div>' .
        '</div>',
      '#weight' => -10,
    ];

    // Asegurar que los campos de respuesta sean visibles y requeridos si es necesario.
    if (isset($form['field_respuesta_solicitud'])) {
      $form['field_respuesta_solicitud']['#access'] = TRUE;
      $form['field_respuesta_solicitud']['#weight'] = 10;
    }
    if (isset($form['field_validado_por'])) {
      $form['field_validado_por']['#access'] = TRUE;
      $form['field_validado_por']['#weight'] = 20;
    }

    $form['enviar_acceso_nuevo_actor'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enviar también por email el acceso para que el actor agregue su nuevo perfil a su cuenta'),
      '#description' => $this->t('Al marcar esta casilla, se enviará automáticamente un correo al usuario con el enlace directo para registrar el actor de tipo @tipo.', ['@tipo' => $tipo_actor_label]),
      '#default_value' => TRUE,
      '#weight' => 25,
    ];

    $form['actions']['submit']['#value'] = $this->t('Registrar Respuesta');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    // Asignar el validador automáticamente al usuario actual si no se ha seleccionado uno.
    if ($this->entity->hasField('field_validado_por') && $this->entity->get('field_validado_por')->isEmpty()) {
      $this->entity->set('field_validado_por', \Drupal::currentUser()->id());
    }
    
    $result = parent::save($form, $form_state);
    
    // Notificar al usuario que creó la solicitud.
    $this->notifyUser($this->entity);

    // Si se marcó la opción de enviar acceso para agregar nuevo actor.
    if ($form_state->getValue('enviar_acceso_nuevo_actor')) {
      \Drupal::service('class_resolver')
        ->getInstanceFromDefinition('\Drupal\zinco_reconocimientos\Controller\ZincoReconocimientosActionController')
        ->enviarAccesoNuevoActor($this->entity->id());
    }

    $form_state->setRedirect('zinco_front.reconocimiento_detail', [
      'reconocimiento_id' => $this->entity->id(),
    ]);

    return $result;
  }

  /**
   * Notifies the user about the response to their recognition request.
   */
  protected function notifyUser($entity) {
    $mail_service = \Drupal::service('zinco_front.mail_service');
    $owner = $entity->getOwner();
    $target_email = '';
    $user_name = '';
    
    if ($owner && $owner->isAuthenticated()) {
      $target_email = $owner->getEmail();
      $user_name = $owner->getDisplayName();
    }
    elseif ($entity->hasField('field_actor_asociado') && !$entity->get('field_actor_asociado')->isEmpty()) {
      $actor = $entity->get('field_actor_asociado')->entity;
      if ($actor && $actor->hasField('email') && !$actor->get('email')->isEmpty()) {
        $target_email = $actor->get('email')->value;
        $user_name = $actor->label();
      }
    }

    if (empty($target_email)) {
      return;
    }

    $respuesta = '';
    if ($entity->hasField('field_respuesta_solicitud') && !$entity->get('field_respuesta_solicitud')->isEmpty()) {
      $respuesta = $entity->get('field_respuesta_solicitud')->value;
    }

    $variables = [
      'user_name' => $user_name,
      'label' => $entity->label(),
      'respuesta' => $respuesta,
      'detail_url' => \Drupal::token()->replace('[site:url]') . 'reconocimientos/' . $entity->id(),
    ];

    $mail_service->sendTemplatedEmail(
      $target_email,
      $this->t('Respuesta a tu Solicitud de Reconocimiento: @label', ['@label' => $entity->label()]),
      'email_reconocimiento_respuesta',
      $variables
    );
  }

}
