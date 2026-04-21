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

      // Notificar por correo electrónico a los administradores.
      $query = \Drupal::entityQuery('user')
        ->condition('status', 1)
        ->condition('roles', ['administrator', 'administrador_ecosistema'], 'IN')
        ->accessCheck(FALSE);
      $admin_uids = $query->execute();

      if (!empty($admin_uids)) {
        $admin_users = User::loadMultiple($admin_uids);
        $reto = $this->entity;
        
        $mail_service = \Drupal::service('zinco_front.mail_service');
        $base_url = \Drupal::request()->getSchemeAndHttpHost();
        
        $variables = [
          'reto_label' => $reto->label(),
          'author_name' => $user_entity->getDisplayName(),
          'created_date' => \Drupal::service('date.formatter')->format(\Drupal::time()->getRequestTime(), 'custom', 'd/m/Y'),
          'base_url' => $base_url,
          'reto_url' => $reto->toUrl('canonical')->toString(),
        ];
        
        foreach ($admin_users as $admin_user) {
          if ($email = $admin_user->getEmail()) {
            $mail_service->sendTemplatedEmail(
              $email,
              'Nuevo Reto de Innovación Creado: ' . $reto->label(),
              'email_new_reto_notification',
              $variables
            );
          }
        }
      }

      // Redirect to the actor profile.
      $form_state->setRedirect('zinco_front.actor_profile', [
        'actor_id' => $actor_id,
      ]);
    } else {
      // Fallback to the entity view page if no actor is found.
      $form_state->setRedirectUrl($this->entity->toUrl());
    }

    return $result;
  }

}
