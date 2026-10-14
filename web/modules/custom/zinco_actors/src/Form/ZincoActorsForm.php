<?php

declare(strict_types=1);

namespace Drupal\zinco_actors\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the zincoactors entity edit forms.
 */
class ZincoActorsForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    if (isset($form['municipio'])) {
      $current_tid = '';
      if ($this->entity->hasField('municipio') && !$this->entity->get('municipio')->isEmpty()) {
        $current_tid = (int) $this->entity->get('municipio')->target_id;
      }

      $municipios_options = $this->getCordobaMunicipiosOptions();

      $weight = -3;
      if (isset($form['municipio']['#weight'])) {
        $weight = $form['municipio']['#weight'];
      }
      elseif (isset($form['municipio']['widget']['#weight'])) {
        $weight = $form['municipio']['widget']['#weight'];
      }

      // Ocultar el widget original para que no interfiera.
      $form['municipio']['#access'] = FALSE;

      // Crear el campo selector con el nombre, ayuda y opciones de Córdoba.
      $form['municipio_actividad'] = [
        '#type' => 'select',
        '#title' => $this->t('Municipio actividad'),
        '#description' => $this->t('Municipio de Córdoba en el cual opera un proyecto, trabaja o reside.'),
        '#options' => ['' => $this->t('- Seleccione un municipio -')] + $municipios_options,
        '#default_value' => $current_tid,
        '#weight' => $weight,
        '#required' => $form['municipio']['widget']['#required'] ?? FALSE,
      ];

      // Registrar entity builder para transferir el valor al campo municipio de la entidad.
      $form['#entity_builders'][] = '::buildMunicipioActividad';
    }

    // Si es un nuevo actor y el usuario ya está autenticado (proceso de reconocimiento o nuevo perfil),
    // precargar datos de contacto y ubicación de su actor existente para facilitar el registro.
    $this->prefillFromExistingActor($form, $form_state);

    // Campo de aceptación de términos y condiciones y tratamiento de datos personales.
    $this->buildAceptarTerminosElement($form, $form_state);

    return $form;
  }

  /**
   * Pre-fills contact and location fields from an existing actor if adding a new actor.
   */
  protected function prefillFromExistingActor(array &$form, FormStateInterface $form_state): void {
    if (!$this->entity->isNew()) {
      return;
    }

    $current_user = \Drupal::currentUser();
    if (!$current_user->isAuthenticated()) {
      return;
    }

    $storage = $this->entityTypeManager->getStorage('zinco_actors_zincoactors');
    $user_actor_ids = [];
    $account = \Drupal\user\Entity\User::load($current_user->id());
    if ($account && $account->hasField('field_actor') && !$account->get('field_actor')->isEmpty()) {
      foreach ($account->get('field_actor')->getValue() as $item) {
        if (!empty($item['target_id'])) {
          $user_actor_ids[] = (int) $item['target_id'];
        }
      }
    }

    $primary_actor = NULL;
    $rec_id = \Drupal::request()->query->get('reconocimiento_id');
    if ($rec_id) {
      $rec = $this->entityTypeManager->getStorage('zinco_reconocimientos')->load($rec_id);
      if ($rec && $rec->hasField('field_actor_asociado') && !$rec->get('field_actor_asociado')->isEmpty()) {
        $primary_actor = $rec->get('field_actor_asociado')->entity;
      }
    }

    if (!$primary_actor && !empty($user_actor_ids)) {
      $primary_actor = $storage->load(reset($user_actor_ids));
    }

    if (!$primary_actor) {
      return;
    }

    // 1. Email
    if ($this->entity->hasField('email') && $this->entity->get('email')->isEmpty() && $primary_actor->hasField('email') && !$primary_actor->get('email')->isEmpty()) {
      $val = (string) $primary_actor->get('email')->value;
      $this->entity->set('email', $val);
      if (isset($form['email']['widget'][0]['value'])) {
        $form['email']['widget'][0]['value']['#default_value'] = $val;
      }
    }

    // 2. Telefono
    if ($this->entity->hasField('telefono') && $this->entity->get('telefono')->isEmpty() && $primary_actor->hasField('telefono') && !$primary_actor->get('telefono')->isEmpty()) {
      $val = (string) $primary_actor->get('telefono')->value;
      $this->entity->set('telefono', $val);
      if (isset($form['telefono']['widget'][0]['value'])) {
        $form['telefono']['widget'][0]['value']['#default_value'] = $val;
      }
    }

    // 3. Sitio web
    if ($this->entity->hasField('sitio_web') && $this->entity->get('sitio_web')->isEmpty() && $primary_actor->hasField('sitio_web') && !$primary_actor->get('sitio_web')->isEmpty()) {
      $val = (string) $primary_actor->get('sitio_web')->value;
      $this->entity->set('sitio_web', $val);
      if (isset($form['sitio_web']['widget'][0]['value'])) {
        $form['sitio_web']['widget'][0]['value']['#default_value'] = $val;
      }
    }

    // 4. Municipio
    if ($this->entity->hasField('municipio') && $this->entity->get('municipio')->isEmpty() && $primary_actor->hasField('municipio') && !$primary_actor->get('municipio')->isEmpty()) {
      $mun_id = (int) $primary_actor->get('municipio')->target_id;
      $this->entity->set('municipio', $mun_id);
      if (isset($form['municipio_actividad'])) {
        $form['municipio_actividad']['#default_value'] = $mun_id;
      }
    }

    // 5. NIT / Cédula / Documento
    $nit_candidates = [
      'field_nit', 'nit', 'field_documento', 'documento',
      'field_numero_documento', 'numero_documento', 'field_identificacion',
      'identificacion', 'field_numero_identificacion', 'numero_identificacion',
      'field_cedula', 'cedula', 'field_numero_identidad', 'numero_identidad',
    ];
    $primary_nit = '';
    foreach ($nit_candidates as $cand) {
      if ($primary_actor->hasField($cand) && !$primary_actor->get($cand)->isEmpty()) {
        $primary_nit = (string) $primary_actor->get($cand)->value;
        break;
      }
    }
    if ($primary_nit !== '') {
      foreach ($nit_candidates as $cand) {
        if ($this->entity->hasField($cand) && $this->entity->get($cand)->isEmpty()) {
          $this->entity->set($cand, $primary_nit);
          if (isset($form[$cand]['widget'][0]['value'])) {
            $form[$cand]['widget'][0]['value']['#default_value'] = $primary_nit;
          }
          break;
        }
      }
    }

    // Alerta informativa visible
    $form['info_prellenado_reconocimiento'] = [
      '#type' => 'markup',
      '#markup' => '<div class="alert alert-info border-0 shadow-sm mb-4 d-flex align-items-center" style="border-radius: 10px;">' .
        '<i class="fas fa-info-circle fa-lg text-info me-3"></i>' .
        '<div>' .
        '<strong>' . $this->t('Registro de nuevo perfil de actor:') . '</strong> ' .
        $this->t('Se han precargado tus datos de contacto habituales de <strong>@actor</strong>. Puedes conservarlos o modificarlos; en este proceso se permite compartir los mismos datos con tu cuenta sin conflicto.', [
          '@actor' => htmlspecialchars((string) $primary_actor->label(), ENT_QUOTES, 'UTF-8'),
        ]) .
        '</div></div>',
      '#weight' => -100,
    ];
  }

  /**
   * Builds the 'aceptar_terminos' checkbox element and ensures help text is visible.
   */
  protected function buildAceptarTerminosElement(array &$form, FormStateInterface $form_state): void {
    $is_new = $this->entity->isNew();
    $help_text = $this->t('Al registrarte, declaras conocer y aceptar los términos de uso y la política de tratamiento y protección de datos personales de la plataforma ZINCO, autorizando el uso de la información suministrada para la articulación en el ecosistema CTI.');

    // Verificar si la entidad ya cuenta con un campo configurable de términos.
    $terms_candidates = [
      'field_terminos',
      'field_aceptar_terminos',
      'field_terminos_y_condiciones',
      'field_terminos_condiciones',
      'field_tratamiento_datos',
      'terminos',
      'aceptar_terminos',
    ];

    $configured_field = NULL;
    foreach ($terms_candidates as $candidate) {
      if ($this->entity->hasField($candidate)) {
        $configured_field = $candidate;
        break;
      }
    }

    if ($configured_field && isset($form[$configured_field])) {
      $form[$configured_field]['#description_display'] = 'after';
      if (isset($form[$configured_field]['widget'])) {
        $form[$configured_field]['widget']['#description_display'] = 'after';
        if (isset($form[$configured_field]['widget']['value'])) {
          $form[$configured_field]['widget']['value']['#description_display'] = 'after';
        }
        if (empty($form[$configured_field]['widget']['#description']) && empty($form[$configured_field]['widget']['value']['#description'])) {
          $form[$configured_field]['widget']['#description'] = $help_text;
          if (isset($form[$configured_field]['widget']['value'])) {
            $form[$configured_field]['widget']['value']['#description'] = $help_text;
          }
        }
        if ($is_new) {
          $form[$configured_field]['widget']['#required'] = TRUE;
          if (isset($form[$configured_field]['widget']['value'])) {
            $form[$configured_field]['widget']['value']['#required'] = TRUE;
          }
        }
      }
    }
    else {
      // Elemento de formulario estándar para aceptar términos.
      $form['aceptar_terminos'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Acepto los términos, condiciones y la política de tratamiento de datos personales'),
        '#description' => $help_text,
        '#description_display' => 'after',
        '#required' => $is_new,
        '#default_value' => $is_new ? 0 : 1,
        '#weight' => 95,
        '#attributes' => [
          'class' => ['zinco-terminos-checkbox'],
        ],
      ];
    }
  }

  /**
   * Entity builder callback for 'municipio_actividad'.
   */
  public function buildMunicipioActividad(string $entity_type_id, EntityInterface $entity, array &$form, FormStateInterface $form_state): void {
    if ($form_state->hasValue('municipio_actividad')) {
      $selected_tid = $form_state->getValue('municipio_actividad');
      if (!empty($selected_tid)) {
        $entity->set('municipio', (int) $selected_tid);
      }
      else {
        $entity->set('municipio', NULL);
      }
    }
  }

  /**
   * Helper method to get the child municipality terms of Córdoba from divipola.
   *
   * @return array
   *   Array of municipality names keyed by taxonomy term ID.
   */
  protected function getCordobaMunicipiosOptions(): array {
    $options = [];
    $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');

    // Buscar el término padre 'Córdoba' en el vocabulario 'divipola'.
    $query = $term_storage->getQuery()
      ->condition('vid', 'divipola')
      ->condition('name', ['Córdoba', 'Cordoba', 'CORDOBA', 'CÓRDOBA'], 'IN')
      ->accessCheck(FALSE);
    $parent_tids = $query->execute();

    if (empty($parent_tids)) {
      $query = $term_storage->getQuery()
        ->condition('vid', 'divipola')
        ->condition('name', 'Cordoba', 'CONTAINS')
        ->accessCheck(FALSE);
      $parent_tids = $query->execute();
    }

    if (!empty($parent_tids)) {
      $parent_tid = (int) reset($parent_tids);

      // Cargar los términos hijos del departamento de Córdoba.
      $children = $term_storage->loadChildren($parent_tid);
      if (!empty($children)) {
        foreach ($children as $child) {
          $options[$child->id()] = $child->label();
        }
      }
      else {
        $tree = $term_storage->loadTree('divipola', $parent_tid, 1, TRUE);
        foreach ($tree as $term_child) {
          $options[$term_child->id()] = $term_child->label();
        }
      }
    }

    // Fallback: si no se encuentran hijos de Córdoba, cargar términos de divipola.
    if (empty($options)) {
      $terms = $term_storage->loadByProperties(['vid' => 'divipola']);
      foreach ($terms as $term) {
        $options[$term->id()] = $term->label();
      }
    }

    natcasesort($options);

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    if ($form_state->hasValue('municipio_actividad')) {
      $selected_tid = $form_state->getValue('municipio_actividad');
      if (!empty($selected_tid)) {
        $this->entity->set('municipio', (int) $selected_tid);
        $form_state->setValue('municipio', [['target_id' => (int) $selected_tid]]);
      }
      else {
        $this->entity->set('municipio', NULL);
        $form_state->setValue('municipio', []);
      }
    }

    parent::validateForm($form, $form_state);

    $current_id = $this->entity->isNew() ? NULL : (int) $this->entity->id();
    $storage = $this->entityTypeManager->getStorage('zinco_actors_zincoactors');

    // Determinar actores asociados al usuario autenticado (para permitir compartir datos propios sin bloqueo).
    $current_user = \Drupal::currentUser();
    $user_owned_actor_ids = [];
    if ($current_user->isAuthenticated()) {
      $account = \Drupal\user\Entity\User::load($current_user->id());
      if ($account && $account->hasField('field_actor') && !$account->get('field_actor')->isEmpty()) {
        foreach ($account->get('field_actor')->getValue() as $item) {
          if (!empty($item['target_id'])) {
            $user_owned_actor_ids[] = (int) $item['target_id'];
          }
        }
      }
      $owned_query = $storage->getQuery()
        ->condition('uid', $current_user->id())
        ->accessCheck(FALSE);
      $owned_ids = $owned_query->execute();
      if (!empty($owned_ids)) {
        $user_owned_actor_ids = array_unique(array_merge($user_owned_actor_ids, array_map('intval', $owned_ids)));
      }
    }

    // Si viene de un reconocimiento específico en la URL:
    $rec_id_param = \Drupal::request()->query->get('reconocimiento_id');
    if ($rec_id_param) {
      $rec_entity = $this->entityTypeManager->getStorage('zinco_reconocimientos')->load($rec_id_param);
      if ($rec_entity && $rec_entity->hasField('field_actor_asociado') && !$rec_entity->get('field_actor_asociado')->isEmpty()) {
        $user_owned_actor_ids[] = (int) $rec_entity->get('field_actor_asociado')->target_id;
      }
    }
    $user_owned_actor_ids = array_unique($user_owned_actor_ids);

    // 1. Extraer y validar correo electrónico.
    $email = '';
    if ($this->entity->hasField('email') && !$this->entity->get('email')->isEmpty()) {
      $email = trim((string) $this->entity->get('email')->value);
    }
    if (empty($email)) {
      $raw_email = $form_state->getValue('email');
      if (is_array($raw_email)) {
        $email = trim((string) ($raw_email[0]['value'] ?? $raw_email['value'] ?? ''));
      }
      elseif (is_string($raw_email)) {
        $email = trim($raw_email);
      }
    }

    if (!empty($email)) {
      $query = $storage->getQuery()
        ->condition('email', $email)
        ->accessCheck(FALSE);
      if ($current_id) {
        $query->condition('id', $current_id, '<>');
      }
      $existing_ids = $query->execute();

      // Permitir coincidencia si el actor existente pertenece a los perfiles del usuario actual.
      $foreign_existing_ids = array_diff(array_map('intval', $existing_ids), $user_owned_actor_ids);

      if (!empty($foreign_existing_ids)) {
        $existing_id = reset($foreign_existing_ids);
        $existing_actor = $storage->load($existing_id);
        $label = $existing_actor ? $existing_actor->label() : "ID $existing_id";

        $form_state->setErrorByName('email', $this->t(
          'El actor ya se encuentra registrado con ese correo electrónico (@email). Pertenece a "@label" (ID: @id).',
          [
            '@email' => $email,
            '@label' => $label,
            '@id' => $existing_id,
          ]
        ));
      }
    }

    // 2. Extraer y validar teléfono.
    $telefono = '';
    if ($this->entity->hasField('telefono') && !$this->entity->get('telefono')->isEmpty()) {
      $telefono = trim((string) $this->entity->get('telefono')->value);
    }
    if (empty($telefono)) {
      $raw_tel = $form_state->getValue('telefono');
      if (is_array($raw_tel)) {
        $telefono = trim((string) ($raw_tel[0]['value'] ?? $raw_tel['value'] ?? ''));
      }
      elseif (is_string($raw_tel)) {
        $telefono = trim($raw_tel);
      }
    }

    if (!empty($telefono)) {
      $query = $storage->getQuery()
        ->condition('telefono', $telefono)
        ->accessCheck(FALSE);
      if ($current_id) {
        $query->condition('id', $current_id, '<>');
      }
      $existing_ids = $query->execute();

      // Permitir coincidencia si el actor existente pertenece a los perfiles del usuario actual.
      $foreign_existing_ids = array_diff(array_map('intval', $existing_ids), $user_owned_actor_ids);

      if (!empty($foreign_existing_ids)) {
        $existing_id = reset($foreign_existing_ids);
        $existing_actor = $storage->load($existing_id);
        $label = $existing_actor ? $existing_actor->label() : "ID $existing_id";

        $form_state->setErrorByName('telefono', $this->t(
          'El actor ya se encuentra registrado con ese teléfono (@telefono). Pertenece a "@label" (ID: @id).',
          [
            '@telefono' => $telefono,
            '@label' => $label,
            '@id' => $existing_id,
          ]
        ));
      }
    }

    // 3. Extraer y validar número de identidad / NIT (si existe campo configurado).
    $nit_field_name = NULL;
    $nit_value = '';
    $nit_candidates = [
      'field_nit',
      'nit',
      'field_documento',
      'documento',
      'field_numero_documento',
      'numero_documento',
      'field_identificacion',
      'identificacion',
      'field_numero_identificacion',
      'numero_identificacion',
      'field_cedula',
      'cedula',
      'field_numero_identidad',
      'numero_identidad',
    ];

    foreach ($nit_candidates as $candidate) {
      if ($this->entity->hasField($candidate) && !$this->entity->get($candidate)->isEmpty()) {
        $nit_field_name = $candidate;
        $nit_value = trim((string) $this->entity->get($candidate)->value);
        break;
      }
    }

    if (!$nit_field_name) {
      foreach ($this->entity->getFields() as $f_name => $field_list) {
        $label_lower = mb_strtolower((string) $field_list->getFieldDefinition()->getLabel());
        if (str_contains($label_lower, 'nit') || str_contains($label_lower, 'identidad') || str_contains($label_lower, 'identificación') || str_contains($label_lower, 'documento de')) {
          if (!$field_list->isEmpty()) {
            $nit_field_name = $f_name;
            $nit_value = trim((string) $field_list->value);
            break;
          }
        }
      }
    }

    if ($nit_field_name && !empty($nit_value)) {
      $query = $storage->getQuery()
        ->condition($nit_field_name, $nit_value)
        ->accessCheck(FALSE);
      if ($current_id) {
        $query->condition('id', $current_id, '<>');
      }
      $existing_ids = $query->execute();

      // Permitir coincidencia si el actor existente pertenece a los perfiles del usuario actual.
      $foreign_existing_ids = array_diff(array_map('intval', $existing_ids), $user_owned_actor_ids);

      if (!empty($foreign_existing_ids)) {
        $existing_id = reset($foreign_existing_ids);
        $existing_actor = $storage->load($existing_id);
        $label = $existing_actor ? $existing_actor->label() : "ID $existing_id";

        $form_state->setErrorByName($nit_field_name, $this->t(
          'El actor ya se encuentra registrado con ese número de identidad o NIT (@nit). Pertenece a "@label" (ID: @id).',
          [
            '@nit' => $nit_value,
            '@label' => $label,
            '@id' => $existing_id,
          ]
        ));
      }
    }

    // 4. Validar aceptación obligatoria de términos al registrar un nuevo actor.
    if ($this->entity->isNew()) {
      $terminos_aceptados = FALSE;

      $terms_candidates = [
        'field_terminos',
        'field_aceptar_terminos',
        'field_terminos_y_condiciones',
        'field_terminos_condiciones',
        'field_tratamiento_datos',
        'terminos',
        'aceptar_terminos',
      ];

      $matched_key = NULL;
      foreach ($terms_candidates as $candidate) {
        if ($form_state->hasValue($candidate)) {
          $matched_key = $candidate;
          $val = $form_state->getValue($candidate);
          if (is_array($val)) {
            $terminos_aceptados = !empty($val[0]['value']) || !empty($val['value']);
          }
          else {
            $terminos_aceptados = !empty($val);
          }
          if ($terminos_aceptados) {
            break;
          }
        }
      }

      if (!$terminos_aceptados) {
        $error_field = $matched_key ?: 'aceptar_terminos';
        $form_state->setErrorByName(
          $error_field,
          $this->t('Debes aceptar los términos y condiciones y el tratamiento de datos personales para completar tu registro.')
        );
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $current_user = \Drupal::currentUser();
    if ($this->entity->isNew() && $current_user->isAuthenticated() && $this->entity->hasField('uid') && $this->entity->get('uid')->isEmpty()) {
      $this->entity->set('uid', $current_user->id());
    }

    $result = parent::save($form, $form_state);

    $message_args = ['%label' => $this->entity->toLink()->toString()];
    $logger_args = [
      '%label' => $this->entity->label(),
      'link' => $this->entity->toLink($this->t('View'))->toString(),
    ];

    switch ($result) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('New zincoactors %label has been created.', $message_args));
        $this->logger('zinco_actors')->notice('New zincoactors %label has been created.', $logger_args);
        break;

      case SAVED_UPDATED:
        $this->messenger()->addStatus($this->t('The zincoactors %label has been updated.', $message_args));
        $this->logger('zinco_actors')->notice('The zincoactors %label has been updated.', $logger_args);
        break;

      default:
        throw new \LogicException('Could not save the entity.');
    }

    $form_state->setRedirectUrl($this->entity->toUrl());

    return $result;
  }

}
