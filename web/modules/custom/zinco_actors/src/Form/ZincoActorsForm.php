<?php

declare(strict_types=1);

namespace Drupal\zinco_actors\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the zincoactors entity edit forms.
 */
class ZincoActorsForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);

    $current_id = $this->entity->isNew() ? NULL : (int) $this->entity->id();
    $storage = $this->entityTypeManager->getStorage('zinco_actors_zincoactors');

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
      $query->range(0, 1);
      $existing_ids = $query->execute();

      if (!empty($existing_ids)) {
        $existing_id = reset($existing_ids);
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
      $query->range(0, 1);
      $existing_ids = $query->execute();

      if (!empty($existing_ids)) {
        $existing_id = reset($existing_ids);
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
      $query->range(0, 1);
      $existing_ids = $query->execute();

      if (!empty($existing_ids)) {
        $existing_id = reset($existing_ids);
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
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
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