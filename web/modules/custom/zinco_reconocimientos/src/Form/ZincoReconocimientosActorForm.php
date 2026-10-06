<?php

namespace Drupal\zinco_reconocimientos\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the zinco reconocimientos actor forms.
 */
class ZincoReconocimientosActorForm extends ZincoReconocimientosForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    // Ocultar campos internos o de respuesta.
    if (isset($form['field_respuesta_solicitud'])) {
      $form['field_respuesta_solicitud']['#access'] = FALSE;
    }
    if (isset($form['field_validado_por'])) {
      $form['field_validado_por']['#access'] = FALSE;
    }
    if (isset($form['field_actor_asociado'])) {
      $form['field_actor_asociado']['#access'] = FALSE;
    }
    if (isset($form['description'])) {
      $form['description']['#access'] = FALSE;
    }
    if (isset($form['uid'])) {
      $form['uid']['#access'] = FALSE;
    }
    if (isset($form['status'])) {
      $form['status']['#access'] = FALSE;
    }

    if (isset($form['label'])) {
      $form['label']['widget'][0]['value']['#title'] = $this->t('Asunto de la solicitud');
      $form['label']['widget'][0]['value']['#placeholder'] = $this->t('Ej: Solicitud de reconocimiento e inclusión en el ecosistema');
    }

    // 1. Selector de tipo de actor al cual desea ser reconocido.
    $bundle_info = \Drupal::service('entity_type.bundle.info')->getBundleInfo('zinco_actors_zincoactors');
    $actor_types = [];
    foreach ($bundle_info as $bundle_id => $info) {
      $actor_types[$bundle_id] = $info['label'];
    }

    // Extraer valor por defecto si ya existía.
    $default_tipo = '';
    if ($this->entity->hasField('description') && !$this->entity->get('description')->isEmpty()) {
      $meta = json_decode($this->entity->get('description')->value, TRUE);
      if (!empty($meta['tipo_actor_bundle'])) {
        $default_tipo = $meta['tipo_actor_bundle'];
      }
    }

    $form['tipo_actor_solicitado'] = [
      '#type' => 'select',
      '#title' => $this->t('Tipo de Actor al cual desea ser reconocido'),
      '#options' => $actor_types,
      '#default_value' => $default_tipo,
      '#required' => TRUE,
      '#empty_option' => $this->t('- Seleccione el tipo de actor deseado -'),
      '#weight' => -4,
      '#description' => $this->t('Indica la categoría de actor para la cual solicitas la validación y reconocimiento.'),
    ];

    // 2. Selector o información del actor solicitante.
    $current_user_id = \Drupal::currentUser()->id();
    $current_user_entity = \Drupal::entityTypeManager()->getStorage('user')->load($current_user_id);
    $user_actors_options = [];

    if ($current_user_entity && $current_user_entity->hasField('field_actor') && !$current_user_entity->get('field_actor')->isEmpty()) {
      $actor_ids = [];
      foreach ($current_user_entity->get('field_actor')->getValue() as $item) {
        if (!empty($item['target_id'])) {
          $actor_ids[] = (int) $item['target_id'];
        }
      }
      if (!empty($actor_ids)) {
        $actors = \Drupal::entityTypeManager()->getStorage('zinco_actors_zincoactors')->loadMultiple($actor_ids);
        foreach ($actors as $act) {
          $b_lbl = $bundle_info[$act->bundle()]['label'] ?? $act->bundle();
          $user_actors_options[$act->id()] = $act->label() . ' (' . $b_lbl . ')';
        }
      }
    }

    $default_actor_id = $this->entity->hasField('field_actor_asociado') && !$this->entity->get('field_actor_asociado')->isEmpty()
      ? $this->entity->get('field_actor_asociado')->target_id
      : (\Drupal::request()->query->get('actor_id') ?: (count($user_actors_options) ? key($user_actors_options) : NULL));

    if (count($user_actors_options) > 1) {
      $form['actor_origen_select'] = [
        '#type' => 'select',
        '#title' => $this->t('Actor que realiza la solicitud'),
        '#options' => $user_actors_options,
        '#default_value' => $default_actor_id,
        '#required' => TRUE,
        '#weight' => -6,
        '#description' => $this->t('Selecciona cuál de tus perfiles asociados origina esta solicitud.'),
      ];
    }
    elseif (count($user_actors_options) === 1) {
      $actor_label_current = reset($user_actors_options);
      $form['actor_origen_info'] = [
        '#type' => 'item',
        '#title' => $this->t('Actor solicitante'),
        '#markup' => '<div class="alert alert-light border py-2 px-3 mb-3"><i class="fas fa-building text-success me-2"></i><strong>' . htmlspecialchars($actor_label_current, ENT_QUOTES, 'UTF-8') . '</strong></div>',
        '#weight' => -6,
      ];
      if ($default_actor_id) {
        $form['actor_origen_select'] = [
          '#type' => 'value',
          '#value' => $default_actor_id,
        ];
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $current_user = \Drupal::currentUser();
    if ($current_user->isAuthenticated()) {
      $this->entity->setOwnerId($current_user->id());
    }

    // Guardar actor solicitante si se seleccionó.
    if ($form_state->hasValue('actor_origen_select')) {
      $this->entity->set('field_actor_asociado', $form_state->getValue('actor_origen_select'));
    }

    // Procesar tipo de actor solicitado.
    $tipo_actor_bundle = $form_state->getValue('tipo_actor_solicitado');
    if ($tipo_actor_bundle) {
      $bundle_info = \Drupal::service('entity_type.bundle.info')->getBundleInfo('zinco_actors_zincoactors');
      $tipo_actor_label = $bundle_info[$tipo_actor_bundle]['label'] ?? $tipo_actor_bundle;

      $meta = [
        'tipo_actor_bundle' => $tipo_actor_bundle,
        'tipo_actor_label' => $tipo_actor_label,
      ];
      $this->entity->set('description', json_encode($meta, JSON_UNESCAPED_UNICODE));

      // Agregar prefijo al label para visibilidad inmediata si no lo tiene.
      $asunto = $this->entity->label();
      $prefix = "[{$tipo_actor_label}] ";
      if (!str_starts_with($asunto, '[')) {
        $this->entity->set('label', $prefix . $asunto);
      }
    }

    $result = parent::save($form, $form_state);

    if ($current_user->isAuthenticated() && in_array('actor_registrado', $current_user->getRoles())) {
      $form_state->setRedirect('zinco_front.perfil');
    }

    return $result;
  }

}
