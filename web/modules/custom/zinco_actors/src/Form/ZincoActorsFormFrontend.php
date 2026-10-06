<?php

declare(strict_types=1);

namespace Drupal\zinco_actors\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the zincoactors entity frontend forms.
 */
class ZincoActorsFormFrontend extends ZincoActorsForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);

    $current_user = \Drupal::currentUser();
    if ($current_user->isAuthenticated()) {
      $account = \Drupal\user\Entity\User::load($current_user->id());
      if ($account && $account->hasRole('actor_registrado')) {
        $form_state->setRedirect('zinco_front.actor_profile', ['actor_id' => $this->entity->id()]);
      }
      else {
        $form_state->setRedirectUrl($this->entity->toUrl());
      }
    }
    else {
      $form_state->setRedirectUrl($this->entity->toUrl());
    }

    return $result;
  }

}