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

    $current_user = $this->currentUser();
    $admin_info = [
      'uid' => (int) $current_user->id(),
      'name' => $current_user->getAccountName() ?: 'Anónimo',
      'email' => $current_user->getEmail() ?: 'Sin email',
      'ip' => $request->getClientIp() ?: 'Desconocida',
    ];
    $logger = \Drupal::logger('zinco_actors_mail');

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
      $logger->warning('GENERACIÓN INDIVIDUAL OMITIDA - Actor "@label" (ID: @id) no tiene correo electrónico configurado. Operador: @admin_name (UID: @admin_uid, IP: @admin_ip)', [
        '@label' => $actor->label(),
        '@id' => $actor->id(),
        '@admin_name' => $admin_info['name'],
        '@admin_uid' => $admin_info['uid'],
        '@admin_ip' => $admin_info['ip'],
      ]);
      return new RedirectResponse($destination);
    }

    /** @var \Drupal\Component\Utility\EmailValidatorInterface $email_validator */
    $email_validator = \Drupal::service('email.validator');
    if (!$email_validator->isValid($email)) {
      $this->messenger()->addError($this->t('El correo "%email" configurado en el actor "%label" no tiene un formato válido.', [
        '%email' => $email,
        '%label' => $actor->label(),
      ]));
      $logger->warning('GENERACIÓN INDIVIDUAL OMITIDA - El correo "@email" del actor "@label" (ID: @id) no tiene formato válido. Operador: @admin_name (UID: @admin_uid, IP: @admin_ip)', [
        '@email' => $email,
        '@label' => $actor->label(),
        '@id' => $actor->id(),
        '@admin_name' => $admin_info['name'],
        '@admin_uid' => $admin_info['uid'],
        '@admin_ip' => $admin_info['ip'],
      ]);
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

        $logger->info(
          'CORREO ENVIADO INDIVIDUAL (Restablecimiento) - Destinatario: @to_mail | Tipo: password_reset | Descripción: Enlace de un solo uso para restablecer/fijar contraseña de cuenta existente vinculada a actor | Actor: "@actor_label" (ID: @actor_id) | Usuario Drupal: "@username" (UID: @uid) | Operador: @admin_name (UID: @admin_uid, IP: @admin_ip)',
          [
            '@to_mail' => $email,
            '@actor_label' => $actor->label(),
            '@actor_id' => $actor->id(),
            '@username' => $existing_user->getAccountName(),
            '@uid' => $existing_user->id(),
            '@admin_name' => $admin_info['name'],
            '@admin_uid' => $admin_info['uid'],
            '@admin_ip' => $admin_info['ip'],
          ]
        );

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

        $logger->info(
          'CORREO ENVIADO INDIVIDUAL (Bienvenida y Activación) - Destinatario: @to_mail | Tipo: register_no_approval_required | Descripción: Notificación de bienvenida y enlace único para establecer contraseña de la nueva cuenta de actor | Actor: "@actor_label" (ID: @actor_id) | Nuevo Usuario: "@username" (UID: @uid) | Operador: @admin_name (UID: @admin_uid, IP: @admin_ip)',
          [
            '@to_mail' => $email,
            '@actor_label' => $actor->label(),
            '@actor_id' => $actor->id(),
            '@username' => $new_user->getAccountName(),
            '@uid' => $new_user->id(),
            '@admin_name' => $admin_info['name'],
            '@admin_uid' => $admin_info['uid'],
            '@admin_ip' => $admin_info['ip'],
          ]
        );

        $this->messenger()->addStatus($this->t('Se ha creado con éxito el usuario "%username" para el actor "%label" y se ha enviado el correo a @email con el enlace para confirmar su contraseña.', [
          '%username' => $username,
          '%label' => $actor->label(),
          '@email' => $email,
        ]));
      }
    }
    catch (\Throwable $e) {
      $logger->error(
        'ERROR EN GENERACIÓN INDIVIDUAL - Actor "@label" (ID: @id, Email: @email): @message | Operador: @admin_name (UID: @admin_uid, IP: @admin_ip)',
        [
          '@label' => $actor->label(),
          '@id' => $actor->id(),
          '@email' => $email,
          '@message' => $e->getMessage(),
          '@admin_name' => $admin_info['name'],
          '@admin_uid' => $admin_info['uid'],
          '@admin_ip' => $admin_info['ip'],
        ]
      );
      $this->messenger()->addError($this->t('Ocurrió un error al procesar el usuario para el actor "%label": @message', [
        '%label' => $actor->label(),
        '@message' => $e->getMessage(),
      ]));
    }

    return new RedirectResponse($destination);
  }

  /**
   * Redirects legacy requests to the secure confirmation form.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirects to the confirmation form.
   */
  public function generarUsuariosMasivo(Request $request): RedirectResponse {
    return new RedirectResponse(Url::fromRoute('zinco_actors.generar_usuarios_masivo')->toString());
  }

}