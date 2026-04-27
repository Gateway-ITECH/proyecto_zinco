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
      'uid',
      'created',
      'status',
    ];

    foreach ($hidden_fields as $field) {
      if (isset($form[$field])) {
        $form[$field]['#access'] = FALSE;
      }
    }

    // Asegurar que los campos de respuesta sean visibles y requeridos si es necesario.
    if (isset($form['field_respuesta_solicitud'])) {
      $form['field_respuesta_solicitud']['#access'] = TRUE;
      $form['field_respuesta_solicitud']['#weight'] = 10;
    }
    if (isset($form['field_validado_por'])) {
      $form['field_validado_por']['#access'] = TRUE;
      $form['field_validado_por']['#weight'] = 20;
    }

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
    
    $form_state->setRedirect('zinco_front.reconocimiento_detail', [
      'reconocimiento_id' => $this->entity->id(),
    ]);

    return $result;
  }

}
