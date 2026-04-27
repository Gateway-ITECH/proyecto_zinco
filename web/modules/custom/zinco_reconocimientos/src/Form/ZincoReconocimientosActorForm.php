<?php

namespace Drupal\zinco_reconocimientos\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the zinco reconocimientos actor forms.
 */
class ZincoReconocimientosActorForm extends ZincoReconocimientosForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    // Ocultar campos según requerimiento del usuario.
    if (isset($form['field_respuesta_solicitud'])) {
      $form['field_respuesta_solicitud']['#access'] = FALSE;
    }
    if (isset($form['field_validado_por'])) {
      $form['field_validado_por']['#access'] = FALSE;
    }
    if (isset($form['field_actor_asociado'])) {
      $form['field_actor_asociado']['#access'] = FALSE;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);

    $current_user = \Drupal::currentUser();
    // Si el usuario es de tipo actor_registrado, redirigir a su perfil.
    if ($current_user->isAuthenticated() && in_array('actor_registrado', $current_user->getRoles())) {
      $form_state->setRedirect('zinco_front.perfil');
    }

    return $result;
  }

}
