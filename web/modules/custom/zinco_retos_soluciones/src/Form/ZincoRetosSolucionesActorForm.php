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
  public function validateForm(array &$form, FormStateInterface $form_state): void
  {
    parent::validateForm($form, $form_state);

    $reto_entity = NULL;
    if ($this->entity->hasField('field_reto_asociado') && !$this->entity->get('field_reto_asociado')->isEmpty()) {
      $reto_entity = $this->entity->get('field_reto_asociado')->entity;
    }
    if (!$reto_entity) {
      $reto_id = \Drupal::routeMatch()->getParameter('reto_id') ?: $form_state->getValue('field_reto_asociado');
      if (is_array($reto_id)) {
        $reto_id = $reto_id[0]['target_id'] ?? NULL;
      }
      if ($reto_id) {
        $reto_entity = \Drupal::entityTypeManager()->getStorage('zinco_retos_innovacion')->load($reto_id);
      }
    }

    if ($reto_entity) {
      $today = new \DateTime('today');
      $fecha_fin_val = $reto_entity->hasField('fecha_fin') && !$reto_entity->get('fecha_fin')->isEmpty()
        ? $reto_entity->get('fecha_fin')->value
        : NULL;
      $fecha_eval_val = $reto_entity->hasField('fecha_evaluacion') && !$reto_entity->get('fecha_evaluacion')->isEmpty()
        ? $reto_entity->get('fecha_evaluacion')->value
        : NULL;

      $dt_fin = !empty($fecha_fin_val) ? new \DateTime(substr((string) $fecha_fin_val, 0, 10)) : NULL;
      $dt_eval = !empty($fecha_eval_val) ? new \DateTime(substr((string) $fecha_eval_val, 0, 10)) : NULL;

      $es_expirado = FALSE;
      $fecha_limite_dt = NULL;

      if ($dt_fin && $dt_fin < $today) {
        $es_expirado = TRUE;
        $fecha_limite_dt = $dt_fin;
      }
      if ($dt_eval && $dt_eval < $today) {
        $es_expirado = TRUE;
        if (!$fecha_limite_dt || $dt_eval < $fecha_limite_dt) {
          $fecha_limite_dt = $dt_eval;
        }
      }

      if ($es_expirado) {
        $fecha_str = $fecha_limite_dt ? $fecha_limite_dt->format('d/m/Y') : '';
        $msg = $fecha_str
          ? $this->t('La fecha límite para enviar la propuesta de reto era hasta el @fecha.', ['@fecha' => $fecha_str])
          : $this->t('La fecha límite para enviar la propuesta de reto ya ha vencido.');
        $form_state->setErrorByName('', $msg);
      }
    }
  }

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
