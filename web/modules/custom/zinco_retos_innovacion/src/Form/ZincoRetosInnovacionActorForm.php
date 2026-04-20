<?php

declare(strict_types=1);

namespace Drupal\zinco_retos_innovacion\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\user\Entity\User;

/**
 * Form controller for the zinco retos innovacion entity forms for actors.
 */
final class ZincoRetosInnovacionActorForm extends ZincoRetosInnovacionForm
{

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int
  {
    $result = parent::save($form, $form_state);

    $current_user = \Drupal::currentUser();
    $user_entity = User::load($current_user->id());

    if ($user_entity && $user_entity->hasField('field_actor') && !$user_entity->get('field_actor')->isEmpty()) {
      $actor_id = $user_entity->get('field_actor')->target_id;
      // Redirect to the actor profile. 
      // Assuming the route name based on standard entity patterns.
      // $form_state->setRedirect('entity.zinco_actors_zincoactors.canonical', [
      //   'zinco_actors_zincoactors' => $actor_id,
      // ]);
    } else {
      // Fallback to the entity view page if no actor is found.
      $form_state->setRedirectUrl($this->entity->toUrl());
    }

    return $result;
  }

}
