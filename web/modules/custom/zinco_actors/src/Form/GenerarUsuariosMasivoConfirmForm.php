<?php

declare(strict_types=1);

namespace Drupal\zinco_actors\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\user\Entity\User;

/**
 * Provides a secure confirmation and Batch form for generating actor users.
 */
class GenerarUsuariosMasivoConfirmForm extends ConfirmFormBase {

  /**
   * Helper method to obtain all actor IDs that do not have an associated user.
   *
   * @return array
   *   Array of pending actor IDs.
   */
  public static function getPendingActorIds(): array {
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

    $actor_storage = \Drupal::entityTypeManager()->getStorage('zinco_actors_zincoactors');
    $actor_query = $actor_storage->getQuery()
      ->accessCheck(FALSE);

    if (!empty($assigned_actor_ids)) {
      $actor_query->condition('id', array_unique($assigned_actor_ids), 'NOT IN');
    }

    return array_values($actor_query->execute());
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'zinco_actors_generar_usuarios_masivo_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $pending_ids = self::getPendingActorIds();
    return $this->t('¿Deseas generar y vincular usuarios para los @count actores pendientes?', [
      '@count' => count($pending_ids),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('El proceso se ejecutará de forma segura mediante lotes progresivos (Batch API). Puedes cancelar en cualquier momento.');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl(): Url {
    return Url::fromRoute('entity.zinco_actors_zincoactors.collection', [], [
      'query' => ['sin_usuario' => '1'],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('⚡ Iniciar proceso por lotes');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $pending_ids = self::getPendingActorIds();
    $count = count($pending_ids);

    if ($count === 0) {
      $form['empty_message'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['messages', 'messages--status'],
          'style' => 'padding: 15px; margin-bottom: 20px; background-color: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px; color: #155724;',
        ],
        'text' => [
          '#markup' => $this->t('<strong>No hay actores pendientes:</strong> Todos los actores registrados en el sistema ya cuentan con un usuario asociado.'),
        ],
      ];

      $form['actions'] = [
        '#type' => 'actions',
        'cancel' => [
          '#type' => 'link',
          '#title' => $this->t('← Volver al listado de actores'),
          '#url' => Url::fromRoute('entity.zinco_actors_zincoactors.collection'),
          '#attributes' => ['class' => ['button', 'button--secondary']],
        ],
      ];

      return $form;
    }

    $form = parent::buildForm($form, $form_state);

    $form['warning_box'] = [
      '#type' => 'container',
      '#weight' => -10,
      '#attributes' => [
        'class' => ['messages', 'messages--warning'],
        'style' => 'padding: 15px; margin-bottom: 20px; background-color: #fff3cd; border: 1px solid #ffeeba; border-radius: 4px; color: #856404;',
      ],
      'text' => [
        '#markup' => $this->t('
          <p><strong>Resumen de la operación:</strong></p>
          <ul>
            <li>Se detectaron <strong>@count actores</strong> sin usuario asociado.</li>
            <li>Para cada actor con correo válido se creará una cuenta de usuario o se vinculará retroactivamente si el usuario ya existía con ese correo.</li>
            <li>Se asignará automáticamente el rol <em>Actor Registrado</em> a la cuenta.</li>
          </ul>', ['@count' => $count]),
      ],
    ];

    $form['enviar_correos'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('<strong>Enviar correos electrónicos de notificación a los actores</strong>'),
      '#description' => $this->t('⚠️ <em>Desactivado por defecto para evitar envíos masivos involuntarios:</em> Si marcas esta casilla, se enviará un correo automático a los actores procesados (restablecimiento de contraseña a usuarios existentes, o bienvenida a cuentas nuevas). Si la dejas <strong>desmarcada</strong>, las cuentas se generarán y vincularán en el sistema <strong>sin enviar ningún correo masivo</strong>.'),
      '#default_value' => FALSE,
      '#weight' => -5,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $pending_ids = self::getPendingActorIds();
    $enviar_correos = (bool) $form_state->getValue('enviar_correos');

    if (empty($pending_ids)) {
      $this->messenger()->addStatus($this->t('No hay actores pendientes para procesar.'));
      $form_state->setRedirectUrl($this->getCancelUrl());
      return;
    }

    $chunks = array_chunk($pending_ids, 20);
    $operations = [];
    foreach ($chunks as $chunk) {
      $operations[] = [
        [static::class, 'processBatchChunk'],
        [$chunk, $enviar_correos],
      ];
    }

    $batch = [
      'title' => $this->t('Procesando usuarios de actores...'),
      'operations' => $operations,
      'finished' => [static::class, 'finishBatch'],
      'init_message' => $this->t('Iniciando procesamiento de actores...'),
      'progress_message' => $this->t('Procesando lote @current de @total...'),
      'error_message' => $this->t('Ocurrió un error inesperado al procesar el lote.'),
    ];

    batch_set($batch);
  }

  /**
   * Processes a chunk of actors in Batch API.
   *
   * @param array $actor_ids
   *   IDs of actors to process in this chunk.
   * @param bool $enviar_correos
   *   Whether to send email notifications.
   * @param array $context
   *   Batch context array passed by reference.
   */
  public static function processBatchChunk(array $actor_ids, bool $enviar_correos, array &$context): void {
    if (!isset($context['results']['created'])) {
      $context['results']['created'] = 0;
      $context['results']['linked'] = 0;
      $context['results']['missing_email'] = 0;
      $context['results']['errors'] = 0;
      $context['results']['notified'] = 0;
    }

    $entity_type_manager = \Drupal::entityTypeManager();
    $actor_storage = $entity_type_manager->getStorage('zinco_actors_zincoactors');
    /** @var \Drupal\Component\Utility\EmailValidatorInterface $email_validator */
    $email_validator = \Drupal::service('email.validator');

    $actors = $actor_storage->loadMultiple($actor_ids);

    foreach ($actors as $actor) {
      $email = $actor->hasField('email') && !$actor->get('email')->isEmpty() ? trim((string) $actor->get('email')->value) : '';

      if (empty($email) || !$email_validator->isValid($email)) {
        $context['results']['missing_email']++;
        continue;
      }

      try {
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

          $actor->setOwnerId((int) $existing_user->id());
          $actor->save();

          $context['results']['linked']++;

          if ($enviar_correos) {
            _user_mail_notify('password_reset', $existing_user);
            $context['results']['notified']++;
          }
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

          /** @var \Drupal\user\Entity\User $new_user */
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

          $context['results']['created']++;

          if ($enviar_correos) {
            _user_mail_notify('register_no_approval_required', $new_user);
            $context['results']['notified']++;
          }
        }
      }
      catch (\Throwable $e) {
        $context['results']['errors']++;
        \Drupal::logger('zinco_actors')->error('Error al generar usuario en lote para actor @id: @msg', [
          '@id' => $actor->id(),
          '@msg' => $e->getMessage(),
        ]);
      }
    }
  }

  /**
   * Finished callback for the batch.
   *
   * @param bool $success
   *   Whether the batch completed successfully.
   * @param array $results
   *   Results accumulated by the batch operations.
   * @param array $operations
   *   Remaining operations if any failed.
   */
  public static function finishBatch(bool $success, array $results, array $operations): void {
    $messenger = \Drupal::messenger();
    if ($success) {
      $created = $results['created'] ?? 0;
      $linked = $results['linked'] ?? 0;
      $total = $created + $linked;
      $notified = $results['notified'] ?? 0;
      $missing = $results['missing_email'] ?? 0;
      $errors = $results['errors'] ?? 0;

      if ($total > 0) {
        $messenger->addStatus(\Drupal::translation()->translate(
          'Proceso completado con éxito: @total actores procesados (@created cuentas nuevas creadas, @linked cuentas existentes vinculadas). Correos de notificación enviados: @notified.',
          [
            '@total' => $total,
            '@created' => $created,
            '@linked' => $linked,
            '@notified' => $notified,
          ]
        ));
      }
      else {
        $messenger->addWarning(\Drupal::translation()->translate('No se procesó ningún actor con correo válido.'));
      }

      if ($missing > 0) {
        $messenger->addWarning(\Drupal::translation()->translate(
          '@count actores fueron omitidos porque no tienen un correo electrónico válido configurado.',
          ['@count' => $missing]
        ));
      }

      if ($errors > 0) {
        $messenger->addError(\Drupal::translation()->translate(
          'Ocurrieron @count errores durante el procesamiento. Consulta los registros (watchdog) para más detalles.',
          ['@count' => $errors]
        ));
      }
    }
    else {
      $messenger->addError(\Drupal::translation()->translate('Ocurrió un error inesperado al procesar el lote masivo.'));
    }
  }

}