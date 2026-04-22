<?php

declare(strict_types=1);

namespace Drupal\zinco_retos_soluciones\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;

/**
 * Form controller for the zinco retos soluciones entity edit forms for actors.
 */
class ZincoRetosSolucionesActorForm extends ZincoRetosSolucionesForm
{

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int
  {
    $result = parent::save($form, $form_state);

    $current_user = \Drupal::currentUser();
    $user_entity = User::load($current_user->id());

    // Notificar por correo electrónico a los administradores sobre la nueva postulación.
    $query = \Drupal::entityQuery('user')
      ->condition('status', 1)
      ->condition('roles', ['administrator', 'administrador_ecosistema'], 'IN')
      ->accessCheck(FALSE);
    $admin_uids = $query->execute();

    if (!empty($admin_uids)) {
      $admin_users = User::loadMultiple($admin_uids);
      $solution = $this->entity;

      // Obtener el nombre del reto asociado.
      $reto_label = '';
      if ($solution->hasField('field_reto_asociado') && !$solution->get('field_reto_asociado')->isEmpty()) {
        $reto_entity = $solution->get('field_reto_asociado')->entity;
        if ($reto_entity) {
          $reto_label = $reto_entity->label();
        }
      }

      $mail_service = \Drupal::service('zinco_front.mail_service');
      $base_url = \Drupal::request()->getSchemeAndHttpHost();

      $variables = [
        'solution_label' => $solution->label(),
        'reto_label' => $reto_label,
        'author_name' => $current_user->getDisplayName(),
        'created_date' => \Drupal::service('date.formatter')->format(\Drupal::time()->getRequestTime(), 'custom', 'd/m/Y'),
        'base_url' => $base_url,
        'solution_url' => $solution->toUrl('canonical')->toString(),
      ];

      foreach ($admin_users as $admin_user) {
        if ($email = $admin_user->getEmail()) {
          $mail_service->sendTemplatedEmail(
            $email,
            'Nueva Postulación Recibida: ' . $solution->label(),
            'email_new_solution_notification',
            $variables
          );
        }
      }
    }

    if ($user_entity && $user_entity->hasField('field_actor') && !$user_entity->get('field_actor')->isEmpty()) {
      $actor_id = $user_entity->get('field_actor')->target_id;
      $form_state->setRedirect('zinco_front.actor_profile', ['actor_id' => $actor_id]);
    }

    return $result;
  }

}
