<?php

declare(strict_types=1);

namespace Drupal\zinco_actors\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\user\Entity\User;

/**
 * Provides a secure confirmation and Batch form for generating actor users for selected actors.
 */
class GenerarUsuariosMasivoConfirmForm extends ConfirmFormBase {

  /**
   * Helper method to obtain target actor IDs explicitly selected by the user.
   *
   * @return array
   *   Array of selected actor IDs.
   */
  public static function getTargetActorIds(): array {
    $request = \Drupal::request();
    $selected_param = $request->query->get('selected_ids');
    if (!empty($selected_param)) {
      $ids = array_filter(array_map('intval', explode(',', $selected_param)));
      if (!empty($ids)) {
        return array_values($ids);
      }
    }

    try {
      $tempstore_ids = \Drupal::service('tempstore.private')->get('zinco_actors')->get('selected_actor_ids');
      if (!empty($tempstore_ids) && is_array($tempstore_ids)) {
        return array_values(array_map('intval', $tempstore_ids));
      }
    }
    catch (\Throwable $e) {}

    return [];
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
    $target_ids = self::getTargetActorIds();
    return $this->t('¿Deseas procesar y generar usuarios para los @count actores seleccionados?', [
      '@count' => count($target_ids),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('El proceso se ejecutará de forma segura en segundo plano mediante lotes progresivos (Batch API). Todas las operaciones quedarán auditadas en el canal zinco_actors_mail.');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl(): Url {
    return Url::fromRoute('entity.zinco_actors_zincoactors.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    $target_ids = self::getTargetActorIds();
    return $this->t('⚡ Iniciar proceso (@count seleccionados)', ['@count' => count($target_ids)]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $target_ids = self::getTargetActorIds();
    $count = count($target_ids);

    if ($count === 0) {
      $form['empty_message'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['messages', 'messages--warning'],
          'style' => 'padding: 15px; margin-bottom: 20px; background-color: #fff3cd; border: 1px solid #ffeeba; border-radius: 4px; color: #856404;',
        ],
        'text' => [
          '#markup' => $this->t('
            <p><strong>No se seleccionó ningún actor:</strong></p>
            <p>Para enviar o generar usuarios, regresa a la tabla de actores, selecciona los actores deseados utilizando las casillas de verificación (checkboxes) y presiona el botón <em>"⚡ Generar / Enviar usuarios a seleccionados"</em>.</p>
          '),
        ],
      ];

      $form['actions'] = [
        '#type' => 'actions',
        'back' => [
          '#type' => 'link',
          '#title' => $this->t('← Volver a la lista de actores'),
          '#url' => Url::fromRoute('entity.zinco_actors_zincoactors.collection'),
          '#attributes' => ['class' => ['button', 'button--primary']],
        ],
      ];

      return $form;
    }

    $form = parent::buildForm($form, $form_state);

    $actor_storage = $this->entityTypeManager->getStorage('zinco_actors_zincoactors');
    $actors = $actor_storage->loadMultiple($target_ids);

    /** @var \Drupal\Component\Utility\EmailValidatorInterface $email_validator */
    $email_validator = \Drupal::service('email.validator');

    $valid_email_count = 0;
    $missing_email_count = 0;
    $preview_items = [];

    foreach ($actors as $actor) {
      $email = $actor->hasField('email') && !$actor->get('email')->isEmpty() ? trim((string) $actor->get('email')->value) : '';
      $has_valid_email = !empty($email) && $email_validator->isValid($email);

      if ($has_valid_email) {
        $valid_email_count++;
        $status_label = '<span style="color:#28a745;">✓ Email: ' . htmlspecialchars($email) . '</span>';
      }
      else {
        $missing_email_count++;
        $status_label = '<span style="color:#dc3545;">⚠️ Sin email válido</span>';
      }

      if (count($preview_items) < 15) {
        $preview_items[] = '<li><strong>' . htmlspecialchars((string) $actor->label()) . '</strong> (ID: ' . $actor->id() . ') — ' . $status_label . '</li>';
      }
    }

    $remaining = $count - count($preview_items);
    if ($remaining > 0) {
      $preview_items[] = '<li><em>... y ' . $remaining . ' actores más seleccionados.</em></li>';
    }

    $form['summary_box'] = [
      '#type' => 'container',
      '#weight' => -10,
      '#attributes' => [
        'class' => ['messages', 'messages--info'],
        'style' => 'padding: 15px; margin-bottom: 20px; background-color: #e8f4fd; border: 1px solid #b8daff; border-radius: 4px; color: #004085;',
      ],
      'text' => [
        '#markup' => $this->t('
          <p><strong>Resumen de selección para envío de usuarios:</strong></p>
          <ul>
            <li>Total actores seleccionados: <strong>@total</strong></li>
            <li>Con correo electrónico válido: <strong>@valid</strong></li>
            <li>Sin correo válido (serán omitidos): <strong>@missing</strong></li>
          </ul>
          <details style="margin-top: 10px;">
            <summary style="cursor: pointer; font-weight: 600;">Ver detalle de actores seleccionados</summary>
            <ul style="margin-top: 8px;">@items</ul>
          </details>
        ', [
          '@total' => $count,
          '@valid' => $valid_email_count,
          '@missing' => $missing_email_count,
          '@items' => implode('', $preview_items),
        ]),
      ],
    ];

    $form['enviar_correos'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('<strong>Enviar correos electrónicos de notificación a los actores seleccionados</strong>'),
      '#description' => $this->t('⚠️ <em>Desactivado por defecto para evitar envíos involuntarios:</em> Si marcas esta casilla, se enviará un correo a los actores seleccionados con email válido (restablecimiento de contraseña a cuentas existentes o bienvenida/activación a cuentas nuevas). Si la dejas <strong>desmarcada</strong>, las cuentas se generarán o vincularán en el sistema <strong>sin enviar ningún correo</strong>.'),
      '#default_value' => FALSE,
      '#weight' => -5,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $target_ids = self::getTargetActorIds();
    $enviar_correos = (bool) $form_state->getValue('enviar_correos');

    if (empty($target_ids)) {
      $this->messenger()->addWarning($this->t('No se encontraron actores seleccionados para procesar.'));
      $form_state->setRedirectUrl($this->getCancelUrl());
      return;
    }

    $current_user = $this->currentUser();
    $request = $this->getRequest();
    $admin_info = [
      'uid' => (int) $current_user->id(),
      'name' => $current_user->getAccountName() ?: 'Anónimo',
      'email' => $current_user->getEmail() ?: 'Sin email',
      'ip' => $request->getClientIp() ?: 'Desconocida',
      'user_agent' => substr((string) $request->headers->get('User-Agent'), 0, 255),
    ];

    // Registro inicial en el canal de logs dedicado.
    \Drupal::logger('zinco_actors_mail')->notice(
      'INICIO PROCESAMIENTO SELECCIONADOS - Operador: @admin_name (UID: @admin_uid, Email: @admin_email, IP: @admin_ip). Total seleccionados: @total. Enviar correos: @enviar_correos. IDs: @ids',
      [
        '@admin_name' => $admin_info['name'],
        '@admin_uid' => $admin_info['uid'],
        '@admin_email' => $admin_info['email'],
        '@admin_ip' => $admin_info['ip'],
        '@total' => count($target_ids),
        '@enviar_correos' => $enviar_correos ? 'SÍ (Notificaciones por correo activadas)' : 'NO (Solo vincular/crear en sistema, sin emails)',
        '@ids' => implode(',', array_slice($target_ids, 0, 50)) . (count($target_ids) > 50 ? '...' : ''),
      ]
    );

    // Limpiar tempstore una vez iniciado el proceso.
    try {
      \Drupal::service('tempstore.private')->get('zinco_actors')->delete('selected_actor_ids');
    }
    catch (\Throwable $e) {}

    $chunks = array_chunk($target_ids, 20);
    $operations = [];
    foreach ($chunks as $chunk) {
      $operations[] = [
        [static::class, 'processBatchChunk'],
        [$chunk, $enviar_correos, $admin_info],
      ];
    }

    $batch = [
      'title' => $this->t('Procesando usuarios de actores seleccionados...'),
      'operations' => $operations,
      'finished' => [static::class, 'finishBatch'],
      'init_message' => $this->t('Iniciando procesamiento de actores seleccionados...'),
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
   * @param array $admin_info
   *   Information about the admin who initiated the process.
   * @param array $context
   *   Batch context array passed by reference.
   */
  public static function processBatchChunk(array $actor_ids, bool $enviar_correos, array $admin_info, array &$context): void {
    if (!isset($context['results']['created'])) {
      $context['results']['created'] = 0;
      $context['results']['linked'] = 0;
      $context['results']['missing_email'] = 0;
      $context['results']['errors'] = 0;
      $context['results']['notified'] = 0;
      $context['results']['admin_info'] = $admin_info;
    }

    $logger = \Drupal::logger('zinco_actors_mail');
    $entity_type_manager = \Drupal::entityTypeManager();
    $actor_storage = $entity_type_manager->getStorage('zinco_actors_zincoactors');
    /** @var \Drupal\Component\Utility\EmailValidatorInterface $email_validator */
    $email_validator = \Drupal::service('email.validator');

    $actors = $actor_storage->loadMultiple($actor_ids);

    foreach ($actors as $actor) {
      $actor_id = $actor->id();
      $actor_label = $actor->label();
      $email = $actor->hasField('email') && !$actor->get('email')->isEmpty() ? trim((string) $actor->get('email')->value) : '';

      if (empty($email) || !$email_validator->isValid($email)) {
        $context['results']['missing_email']++;
        $logger->warning(
          'ACTOR SELECCIONADO OMITIDO SIN CORREO - Actor: "@label" (ID: @id). Motivo: Correo ausente o no válido ("@email"). Operador: @admin_name (UID: @admin_uid).',
          [
            '@label' => $actor_label,
            '@id' => $actor_id,
            '@email' => $email ?: '(vacío)',
            '@admin_name' => $admin_info['name'],
            '@admin_uid' => $admin_info['uid'],
          ]
        );
        continue;
      }

      try {
        $existing_user = user_load_by_mail($email);
        if ($existing_user) {
          $updated = FALSE;
          if ($existing_user->hasField('field_actor')) {
            $existing_user->set('field_actor', $actor_id);
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

            $logger->info(
              'CORREO ENVIADO A SELECCIONADO (Restablecimiento) - Destinatario: @to_mail | Tipo: password_reset | Descripción: Enlace de un solo uso para restablecer/fijar contraseña de cuenta existente | Actor: "@actor_label" (ID: @actor_id) | Usuario Drupal: "@username" (UID: @uid) | Operador: @admin_name (UID: @admin_uid, IP: @admin_ip)',
              [
                '@to_mail' => $email,
                '@actor_label' => $actor_label,
                '@actor_id' => $actor_id,
                '@username' => $existing_user->getAccountName(),
                '@uid' => $existing_user->id(),
                '@admin_name' => $admin_info['name'],
                '@admin_uid' => $admin_info['uid'],
                '@admin_ip' => $admin_info['ip'],
              ]
            );
          }
          else {
            $logger->notice(
              'CUENTA VINCULADA PARA SELECCIONADO (Sin correo) - Actor: "@actor_label" (ID: @actor_id) vinculado a usuario existente "@username" (UID: @uid, Email: @to_mail). No se envió correo porque la opción fue desmarcada por el operador @admin_name (UID: @admin_uid, IP: @admin_ip)',
              [
                '@actor_label' => $actor_label,
                '@actor_id' => $actor_id,
                '@username' => $existing_user->getAccountName(),
                '@uid' => $existing_user->id(),
                '@to_mail' => $email,
                '@admin_name' => $admin_info['name'],
                '@admin_uid' => $admin_info['uid'],
                '@admin_ip' => $admin_info['ip'],
              ]
            );
          }
        }
        else {
          $prefix = strstr($email, '@', TRUE);
          $base_username = !empty($prefix) ? $prefix : preg_replace('/[^a-zA-Z0-9_]/', '', (string) $actor_label);
          $base_username = substr((string) $base_username, 0, 45);
          if (empty($base_username)) {
            $base_username = 'actor_' . $actor_id;
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
            'field_actor' => $actor_id,
          ]);
          $new_user->save();

          $actor->setOwnerId((int) $new_user->id());
          $actor->save();

          $context['results']['created']++;

          if ($enviar_correos) {
            _user_mail_notify('register_no_approval_required', $new_user);
            $context['results']['notified']++;

            $logger->info(
              'CORREO ENVIADO A SELECCIONADO (Bienvenida y Activación) - Destinatario: @to_mail | Tipo: register_no_approval_required | Descripción: Notificación de bienvenida y enlace único para establecer contraseña de nueva cuenta | Actor: "@actor_label" (ID: @actor_id) | Nuevo Usuario: "@username" (UID: @uid) | Operador: @admin_name (UID: @admin_uid, IP: @admin_ip)',
              [
                '@to_mail' => $email,
                '@actor_label' => $actor_label,
                '@actor_id' => $actor_id,
                '@username' => $new_user->getAccountName(),
                '@uid' => $new_user->id(),
                '@admin_name' => $admin_info['name'],
                '@admin_uid' => $admin_info['uid'],
                '@admin_ip' => $admin_info['ip'],
              ]
            );
          }
          else {
            $logger->notice(
              'CUENTA CREADA PARA SELECCIONADO (Sin correo) - Actor: "@actor_label" (ID: @actor_id). Se creó usuario "@username" (UID: @uid, Email: @to_mail). No se envió correo porque la opción fue desmarcada por el operador @admin_name (UID: @admin_uid, IP: @admin_ip)',
              [
                '@actor_label' => $actor_label,
                '@actor_id' => $actor_id,
                '@username' => $new_user->getAccountName(),
                '@uid' => $new_user->id(),
                '@to_mail' => $email,
                '@admin_name' => $admin_info['name'],
                '@admin_uid' => $admin_info['uid'],
                '@admin_ip' => $admin_info['ip'],
              ]
            );
          }
        }
      }
      catch (\Throwable $e) {
        $context['results']['errors']++;
        $logger->error(
          'ERROR AL PROCESAR ACTOR SELECCIONADO - Actor: "@label" (ID: @id, Email: @mail): @message | Operador: @admin_name (UID: @admin_uid)',
          [
            '@label' => $actor_label,
            '@id' => $actor_id,
            '@mail' => $email,
            '@message' => $e->getMessage(),
            '@admin_name' => $admin_info['name'],
            '@admin_uid' => $admin_info['uid'],
          ]
        );
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
    $logger = \Drupal::logger('zinco_actors_mail');
    $admin_info = $results['admin_info'] ?? ['name' => 'N/A', 'uid' => 'N/A', 'ip' => 'N/A'];

    if ($success) {
      $created = $results['created'] ?? 0;
      $linked = $results['linked'] ?? 0;
      $total = $created + $linked;
      $notified = $results['notified'] ?? 0;
      $missing = $results['missing_email'] ?? 0;
      $errors = $results['errors'] ?? 0;

      $logger->notice(
        'FIN PROCESAMIENTO SELECCIONADOS - Resumen: Total procesados: @total (@created nuevas cuentas creadas, @linked cuentas existentes vinculadas). Correos enviados: @notified. Omitidos por falta de email válido: @missing. Errores: @errors. Operador: @admin_name (UID: @admin_uid, IP: @admin_ip)',
        [
          '@total' => $total,
          '@created' => $created,
          '@linked' => $linked,
          '@notified' => $notified,
          '@missing' => $missing,
          '@errors' => $errors,
          '@admin_name' => $admin_info['name'],
          '@admin_uid' => $admin_info['uid'],
          '@admin_ip' => $admin_info['ip'],
        ]
      );

      if ($total > 0) {
        $messenger->addStatus(\Drupal::translation()->translate(
          'Proceso completado con éxito para los actores seleccionados: @total procesados (@created cuentas nuevas creadas, @linked cuentas existentes vinculadas). Correos de notificación enviados: @notified.',
          [
            '@total' => $total,
            '@created' => $created,
            '@linked' => $linked,
            '@notified' => $notified,
          ]
        ));
      }
      else {
        $messenger->addWarning(\Drupal::translation()->translate('No se procesó ningún actor seleccionado con correo válido.'));
      }

      if ($missing > 0) {
        $messenger->addWarning(\Drupal::translation()->translate(
          '@count actores fueron omitidos porque no tienen un correo electrónico válido configurado.',
          ['@count' => $missing]
        ));
      }

      if ($errors > 0) {
        $messenger->addError(\Drupal::translation()->translate(
          'Ocurrieron @count errores durante el procesamiento. Consulta los registros en el canal zinco_actors_mail para más detalles.',
          ['@count' => $errors]
        ));
      }
    }
    else {
      $logger->error(
        'ERROR CRÍTICO EN PROCESAMIENTO - Ocurrió un error inesperado al procesar los actores seleccionados. Operador: @admin_name (UID: @admin_uid, IP: @admin_ip)',
        [
          '@admin_name' => $admin_info['name'],
          '@admin_uid' => $admin_info['uid'],
          '@admin_ip' => $admin_info['ip'],
        ]
      );
      $messenger->addError(\Drupal::translation()->translate('Ocurrió un error inesperado al procesar el lote.'));
    }
  }

}