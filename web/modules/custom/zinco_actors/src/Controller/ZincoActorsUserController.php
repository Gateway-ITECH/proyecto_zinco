<?php

declare(strict_types=1);

namespace Drupal\zinco_actors\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for generating and associating Drupal users with ZincoActors.
 */
class ZincoActorsUserController extends ControllerBase {

  /**
   * Generates or associates a user for a specific actor and sends the password confirmation email.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param int|string $actor_id
   *   The ID of the actor.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirects back to the actors collection page.
   */
  public function generarUsuario(Request $request, $actor_id): RedirectResponse {
    $destination = $request->query->get('destination') ?: Url::fromRoute('entity.zinco_actors_zincoactors.collection')->toString();

    $actor_storage = $this->entityTypeManager()->getStorage('zinco_actors_zincoactors');
    /** @var \Drupal\zinco_actors\ZincoActorsInterface|null $actor */
    $actor = $actor_storage->load($actor_id);

    if (!$actor) {
      $this->messenger()->addError($this->t('No se encontró el actor especificado (ID: @id).', ['@id' => $actor_id]));
      return new RedirectResponse($destination);
    }

    $email = $actor->hasField('email') && !$actor->get('email')->isEmpty() ? trim((string) $actor->get('email')->value) : '';

    if (empty($email)) {
      $this->messenger()->addError($this->t('El actor "%label" (ID: @id) no tiene un correo electrónico configurado. Por favor, edita el actor y define un correo de contacto válido.', [
        '%label' => $actor->label(),
        '@id' => $actor->id(),
      ]));
      return new RedirectResponse($destination);
    }

    /** @var \Drupal\Component\Utility\EmailValidatorInterface $email_validator */
    $email_validator = \Drupal::service('email.validator');
    if (!$email_validator->isValid($email)) {
      $this->messenger()->addError($this->t('El correo "%email" configurado en el actor "%label" no tiene un formato válido.', [
        '%email' => $email,
        '%label' => $actor->label(),
      ]));
      return new RedirectResponse($destination);
    }

    try {
      // Check if user already exists with this email.
      $existing_user = user_load_by_mail($email);

      if ($existing_user) {
        $updated = FALSE;
        if ($existing_user->hasField('field_actor')) {
          $existing_user->set('field_actor', $actor->id());
          $updated = TRUE;
        }
        if (!$existing_user->hasRole('actor_registrado')) {
          $existing_user->addRole('actor_registrado');
          $updated = TRUE;
        }
        if ($existing_user->isBlocked()) {
          $existing_user->activate();
          $updated = TRUE;
        }
        if ($updated) {
          $existing_user->save();
        }

        // Link actor owner.
        $actor->setOwnerId((int) $existing_user->id());
        $actor->save();

        // Enviar correo de restablecimiento/confirmacion de contraseña
        _user_mail_notify('password_reset', $existing_user);

        $this->messenger()->addStatus($this->t('El actor "%label" fue vinculado con la cuenta existente "%username" (@email). Se ha enviado un correo para confirmar y establecer la contraseña.', [
          '%label' => $actor->label(),
          '%username' => $existing_user->getAccountName(),
          '@email' => $email,
        ]));
      }
      else {
        // Generate a clean, unique username.
        $prefix = strstr($email, '@', TRUE);
        $base_username = !empty($prefix) ? $prefix : preg_replace('/[^a-zA-Z0-9_]/', '', (string) $actor->label());
        $base_username = substr((string) $base_username, 0, 45);
        if (empty($base_username)) {
          $base_username = 'actor_' . $actor->id();
        }

        $username = $base_username;
        $counter = 1;
        while (user_load_by_name($username)) {
          $username = substr($base_username, 0, 40) . '_' . $counter;
          $counter++;
        }

        /** @var \Drupal\user\Entity\User $new_user */
        $new_user = User::create([
          'name' => $username,
          'mail' => $email,
          'status' => 1,
          'roles' => ['actor_registrado'],
          'field_actor' => $actor->id(),
        ]);
        $new_user->save();

        // Associate user as owner of the actor.
        $actor->setOwnerId((int) $new_user->id());
        $actor->save();

        // Send official Drupal welcome notification with one-time login link to set password.
        _user_mail_notify('register_no_approval_required', $new_user);

        $this->messenger()->addStatus($this->t('Se ha creado con éxito el usuario "%username" para el actor "%label" y se ha enviado el correo a @email con el enlace para confirmar su contraseña.', [
          '%username' => $username,
          '%label' => $actor->label(),
          '@email' => $email,
        ]));
      }
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t('Ocurrió un error al procesar el usuario para el actor "%label": @message', [
        '%label' => $actor->label(),
        '@message' => $e->getMessage(),
      ]));
    }

    return new RedirectResponse($destination);
  }

  /**
   * Generates users in batch for all actors without an associated user account.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirects back to the actors collection page.
   */
  public function generarUsuariosMasivo(Request $request): RedirectResponse {
    $destination = Url::fromRoute('entity.zinco_actors_zincoactors.collection', [], ['query' => ['sin_usuario' => '1']])->toString();

    // Query actors that already have a user in user__field_actor.
    $user_query = \Drupal::entityQuery('user')
      ->condition('field_actor', NULL, 'IS NOT NULL')
      ->accessCheck(FALSE);
    $user_ids = $user_query->execute();

    $assigned_actor_ids = [];
    if (!empty($user_ids)) {
      $users = User::loadMultiple($user_ids);
      foreach ($users as $u) {
        if ($u->hasField('field_actor') && !$u->get('field_actor')->isEmpty()) {
          $assigned_actor_ids[] = (int) $u->get('field_actor')->target_id;
        }
      }
    }

    // Query all actors not in assigned_actor_ids.
    $actor_storage = $this->entityTypeManager()->getStorage('zinco_actors_zincoactors');
    $actor_query = $actor_storage->getQuery()
      ->accessCheck(FALSE);

    if (!empty($assigned_actor_ids)) {
      $actor_query->condition('id', array_unique($assigned_actor_ids), 'NOT IN');
    }

    $pending_actor_ids = $actor_query->execute();

    if (empty($pending_actor_ids)) {
      $this->messenger()->addStatus($this->t('Todos los actores registrados ya cuentan con un usuario asociado en el sistema.'));
      return new RedirectResponse(Url::fromRoute('entity.zinco_actors_zincoactors.collection')->toString());
    }

    $actors = $actor_storage->loadMultiple($pending_actor_ids);
    $success_count = 0;
    $missing_email_count = 0;

    /** @var \Drupal\Component\Utility\EmailValidatorInterface $email_validator */
    $email_validator = \Drupal::service('email.validator');

    foreach ($actors as $actor) {
      $email = $actor->hasField('email') && !$actor->get('email')->isEmpty() ? trim((string) $actor->get('email')->value) : '';

      if (empty($email) || !$email_validator->isValid($email)) {
        $missing_email_count++;
        continue;
      }

      try {
        $existing_user = user_load_by_mail($email);
        if ($existing_user) {
          if ($existing_user->hasField('field_actor')) {
            $existing_user->set('field_actor', $actor->id());
          }
          if (!$existing_user->hasRole('actor_registrado')) {
            $existing_user->addRole('actor_registrado');
          }
          if ($existing_user->isBlocked()) {
            $existing_user->activate();
          }
          $existing_user->save();
          $actor->setOwnerId((int) $existing_user->id());
          $actor->save();

          _user_mail_notify('password_reset', $existing_user);
          $success_count++;
        }
        else {
          $prefix = strstr($email, '@', TRUE);
          $base_username = !empty($prefix) ? $prefix : preg_replace('/[^a-zA-Z0-9_]/', '', (string) $actor->label());
          $base_username = substr((string) $base_username, 0, 45);
          if (empty($base_username)) {
            $base_username = 'actor_' . $actor->id();
          }

          $username = $base_username;
          $counter = 1;
          while (user_load_by_name($username)) {
            $username = substr($base_username, 0, 40) . '_' . $counter;
            $counter++;
          }

          $new_user = User::create([
            'name' => $username,
            'mail' => $email,
            'status' => 1,
            'roles' => ['actor_registrado'],
            'field_actor' => $actor->id(),
          ]);
          $new_user->save();

          $actor->setOwnerId((int) $new_user->id());
          $actor->save();

          _user_mail_notify('register_no_approval_required', $new_user);
          $success_count++;
        }
      }
      catch (\Exception $e) {
        $this->logger('zinco_actors')->error('Error en generación masiva para actor @id: @msg', [
          '@id' => $actor->id(),
          '@msg' => $e->getMessage(),
        ]);
      }
    }

    if ($success_count > 0) {
      $this->messenger()->addStatus($this->t('Proceso masivo completado: se generaron y notificaron con éxito @count usuarios para sus actores correspondientes.', ['@count' => $success_count]));
    }
    if ($missing_email_count > 0) {
      $this->messenger()->addWarning($this->t('@count actores fueron omitidos porque no tienen un correo electrónico válido configurado.', ['@count' => $missing_email_count]));
    }

    return new RedirectResponse($destination);
  }

}
