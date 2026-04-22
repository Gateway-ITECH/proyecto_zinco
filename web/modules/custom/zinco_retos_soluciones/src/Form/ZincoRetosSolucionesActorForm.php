<?php

declare(strict_types=1);

namespace Drupal\zinco_retos_soluciones\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;

/**
 * Form controller for the zinco retos soluciones entity edit forms for actors.
 */
class ZincoRetosSolucionesActorForm extends ZincoRetosSolucionesForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Si la entidad es nueva y no tiene autores, asignar el actor del usuario actual.
    if ($this->entity->isNew() && $this->entity->get('field_autores_solucion')->isEmpty()) {
      $current_user = \Drupal::currentUser();
      $user_entity = User::load($current_user->id());
      if ($user_entity && $user_entity->hasField('field_actor') && !$user_entity->get('field_actor')->isEmpty()) {
        $actor_id = $user_entity->get('field_actor')->target_id;
        $this->entity->set('field_autores_solucion', [$actor_id]);
      }
    }

    return parent::buildForm($form, $form_state);
  }
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);

    $current_user = \Drupal::currentUser();
    $user_entity = User::load($current_user->id());

    if ($user_entity && $user_entity->hasField('field_actor') && !$user_entity->get('field_actor')->isEmpty()) {
      $actor_id = $user_entity->get('field_actor')->target_id;
      $form_state->setRedirect('zinco_front.actor_profile', ['actor_id' => $actor_id]);
    }

    return $result;
  }

}
