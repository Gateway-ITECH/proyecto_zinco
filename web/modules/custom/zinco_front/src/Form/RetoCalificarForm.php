<?php

namespace Drupal\zinco_front\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\zinco_front\Service\MailService;

/**
 * Formulario para calificar un reto.
 */
class RetoCalificarForm extends FormBase
{

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The mail service.
   *
   * @var \Drupal\zinco_front\Service\MailService
   */
  protected $mailService;

  /**
   * Constructs a new RetoCalificarForm.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, MailService $mail_service)
  {
    $this->entityTypeManager = $entity_type_manager;
    $this->mailService = $mail_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('zinco_front.mail_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return 'reto_calificar_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $solution_id = NULL)
  {
    $form_state->set('solution_id', $solution_id);
    $current_user_id = \Drupal::currentUser()->id();

    // Load the solution.
    $solution = $this->entityTypeManager->getStorage('zinco_retos_soluciones')->load($solution_id);
    if (!$solution) {
      $this->messenger()->addError($this->t('The specified solution does not exist.'));
      return $form;
    }

    // Load the associated challenge.
    $reto = NULL;
    if ($solution->hasField('field_reto_asociado') && !$solution->get('field_reto_asociado')->isEmpty()) {
      $reto = $solution->get('field_reto_asociado')->entity;
    }

    if (!$reto) {
      $this->messenger()->addError($this->t('The associated challenge could not be found.'));
      return $form;
    }

    // Check if an evaluation already exists for this solution and evaluator.
    $existing_evaluations = $this->entityTypeManager->getStorage('zinco_retos_evaluacion')->loadByProperties([
      'field_solucion_evaluada' => $solution_id,
      'field_evaluador' => $current_user_id,
    ]);

    $evaluation = !empty($existing_evaluations) ? reset($existing_evaluations) : NULL;
    $form_state->set('evaluation_entity', $evaluation);

    // Map existing scores if in edit mode.
    $existing_scores = [];
    if ($evaluation && $evaluation->hasField('field_puntuacion_de_solucion')) {
      foreach ($evaluation->get('field_puntuacion_de_solucion') as $item) {
        $p_score = $item->entity;
        if ($p_score) {
          $name = $p_score->get('field_criterio_evaluado')->value;
          $score_id = $p_score->get('field_calificacion_de_solucion_a')->target_id;
          $existing_scores[$name] = $score_id;
        }
      }
    }

    $form['#attributes']['class'][] = 'zinco-form-premium';

    $form['header'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['evaluation-header', 'mb-5', 'p-4', 'bg-light', 'rounded', 'shadow-sm']],
    ];

    $title_prefix = $evaluation ? $this->t('Editando Evaluación') : $this->t('Evaluación');
    $form['header']['title'] = [
      '#type' => 'markup',
      '#markup' => '<h2 class="h4 text-primary mb-2">' . $this->t('@prefix de la Solución: @title', ['@prefix' => $title_prefix, '@title' => $solution->label()]) . '</h2>',
    ];

    $form['header']['reto_info'] = [
      '#type' => 'markup',
      '#markup' => '<p class="text-muted mb-0"><i class="fas fa-bullseye me-2"></i><strong>' . $this->t('Reto asociado:') . '</strong> ' . $reto->label() . '</p>',
    ];

    // Load taxonomy options for evaluation.
    $options = [];
    try {
      $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadByProperties([
        'vid' => 'opciones_de_evaluacion_de_postul',
      ]);
      foreach ($terms as $term) {
        $options[$term->id()] = $term->label();
      }
    } catch (\Exception $e) {
      $this->messenger()->addWarning($this->t('No se pudieron cargar las opciones de evaluación.'));
    }

    // Load criteria from the challenge.
    $form['criterios_wrapper'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Criterios de Evaluación'),
      '#attributes' => ['class' => ['mb-4']],
    ];

    if ($reto->hasField('field_criterios_reto') && !$reto->get('field_criterios_reto')->isEmpty()) {
      $form['criterios_wrapper']['criterios'] = [
        '#type' => 'container',
        '#tree' => TRUE,
      ];

      $criterios_entities = [];
      $total_peso = 0;

      // First pass: load entities and calculate total weight.
      foreach ($reto->get('field_criterios_reto') as $item) {
        if ($paragraph = $item->entity) {
          $criterios_entities[] = $paragraph;
          if ($paragraph->hasField('field_peso_criterio')) {
            $total_peso += (float) $paragraph->get('field_peso_criterio')->value;
          }
        }
      }

      // Second pass: build form elements with calculated percentage.
      foreach ($criterios_entities as $paragraph) {
        $nombre_criterio = $paragraph->get('field_nombre_criterio')->value;
        $descripcion_criterio = $paragraph->hasField('field_descripcion_criterio') ? $paragraph->get('field_descripcion_criterio')->value : '';
        $peso_valor = $paragraph->hasField('field_peso_criterio') ? (float) $paragraph->get('field_peso_criterio')->value : 0;

        $porcentaje_calculado = ($total_peso > 0) ? ($peso_valor / $total_peso) * 100 : 0;
        // Round to 2 decimal places for better display.
        $porcentaje_calculado = round($porcentaje_calculado, 2);

        // Determine default value.
        $default_val = isset($existing_scores[$nombre_criterio]) ? $existing_scores[$nombre_criterio] : NULL;

        $form['criterios_wrapper']['criterios'][$paragraph->id()] = [
          '#type' => 'select',
          '#title' => new FormattableMarkup('<span style="color: black; font-weight: bold;">@title</span>', ['@title' => $nombre_criterio]),
          '#description' => $descripcion_criterio . ' <br><span class="badge bg-info text-dark">' . $this->t('Peso: @peso%', ['@peso' => $porcentaje_calculado]) . '</span>',
          '#options' => $options,
          '#required' => TRUE,
          '#default_value' => $default_val,
          '#empty_option' => $this->t('- Seleccione una calificación -'),
          '#attributes' => [
            'class' => ['form-select', 'mb-4'],
            'style' => 'max-width: 400px;',
          ],
        ];
      }
    } else {
      $form['criterios_wrapper']['no_criteria'] = [
        '#type' => 'markup',
        '#markup' => '<div class="alert alert-warning">' . $this->t('Este reto no tiene criterios de evaluación configurados.') . '</div>',
      ];
    }

    $form['feedback_wrapper'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Retroalimentación General'),
      '#attributes' => ['class' => ['mb-4']],
    ];

    $form['feedback_wrapper']['field_retroalimentacion'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Comentarios y Sugerencias'),
      '#rows' => 4,
      '#default_value' => $evaluation ? $evaluation->get('field_comentarios_evaluacion')->value : '',
      '#placeholder' => $this->t('Escriba aquí sus comentarios sobre la solución...'),
      '#attributes' => ['class' => ['form-control']],
    ];

    $form['actions'] = [
      '#type' => 'actions',
      '#attributes' => ['class' => ['d-flex', 'justify-content-end', 'gap-2', 'mt-4']],
    ];

    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancelar'),
      '#url' => \Drupal\Core\Url::fromRoute('zinco_front.reto_evaluar_list'),
      '#attributes' => ['class' => ['btn', 'btn-outline-secondary']],
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $evaluation ? $this->t('Actualizar Evaluación') : $this->t('Finalizar Evaluación'),
      '#button_type' => 'primary',
      '#attributes' => ['class' => ['btn', 'btn-primary', 'px-4']],
    ];

    $form['#attached']['library'][] = 'zinco_front/zinco-reto-calificar';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $solution_id = $form_state->get('solution_id');
    $criterios_values = $form_state->getValue('criterios');
    $retroalimentacion = $form_state->getValue('field_retroalimentacion');
    $current_user_id = \Drupal::currentUser()->id();
    $evaluation = $form_state->get('evaluation_entity');

    try {
      // Create or update the evaluation entity.
      if (!$evaluation) {
        $solution = $this->entityTypeManager->getStorage('zinco_retos_soluciones')->load($solution_id);
        $solution_label = $solution ? $solution->label() : $solution_id;

        $evaluation_storage = $this->entityTypeManager->getStorage('zinco_retos_evaluacion');
        $evaluation = $evaluation_storage->create([
          'label' => 'Evaluación: ' . $solution_label,
          'field_solucion_evaluada' => $solution_id,
          'field_evaluador' => $current_user_id,
        ]);
      }

      // Update fields.
      $evaluation->set('field_comentarios_evaluacion', $retroalimentacion);
      $evaluation->set('field_fecha_de_evaluacion', date('Y-m-d\TH:i:s'));

      // Handle scores (paragraphs).
      if ($evaluation->hasField('field_puntuacion_de_solucion')) {
        // If updating, we might want to clear old paragraphs or update them.
        // For simplicity, we clear and recreate.
        $old_puntuaciones = $evaluation->get('field_puntuacion_de_solucion')->referencedEntities();
        foreach ($old_puntuaciones as $old_p) {
          $old_p->delete();
        }

        $puntuaciones = [];
        foreach ($criterios_values as $criterio_id => $rating_id) {
          // Load the criterion paragraph to get its name.
          $criterion_paragraph = $this->entityTypeManager->getStorage('paragraph')->load($criterio_id);
          $criterion_name = $criterion_paragraph ? $criterion_paragraph->get('field_nombre_criterio')->value : $criterio_id;

          $puntuacion_paragraph = $this->entityTypeManager->getStorage('paragraph')->create([
            'type' => 'puntuacion_de_criterios_de_reto',
            'field_criterio_evaluado' => $criterion_name,
            'field_calificacion_de_solucion_a' => $rating_id,
          ]);
          $puntuacion_paragraph->save();
          $puntuaciones[] = [
            'target_id' => $puntuacion_paragraph->id(),
            'target_revision_id' => $puntuacion_paragraph->getRevisionId(),
          ];
        }
        $evaluation->set('field_puntuacion_de_solucion', $puntuaciones);
      }

      $evaluation->save();

      $this->messenger()->addStatus($this->t('La evaluación ha sido guardada exitosamente.'));

      // Send email notifications.
      $this->_notifyEvaluacionCalificada($evaluation, $solution_id);

      $form_state->setRedirect('zinco_front.reto_evaluar_list');
    } catch (\Throwable $e) {
      $this->messenger()->addError($this->t('Error al guardar la calificación: @message in @file:@line', [
        '@message' => $e->getMessage(),
        '@file' => $e->getFile(),
        '@line' => $e->getLine(),
      ]));
      \Drupal::logger('zinco_front')->error('Error saving evaluation: @message in @file:@line', [
        '@message' => $e->getMessage(),
        '@file' => $e->getFile(),
        '@line' => $e->getLine(),
      ]);
    }
  }

  /**
   * Sends email notifications about a new or updated evaluation.
   *
   * Notifies: administrators, administrador_ecosistema users, and reto organizers.
   */
  protected function _notifyEvaluacionCalificada($evaluation, $solution_id) {
    try {
      $base_url = \Drupal::request()->getSchemeAndHttpHost();
      $date_formatter = \Drupal::service('date.formatter');
      $current_user = \Drupal::currentUser();

      // Load the solution to get reto data.
      $solution = $this->entityTypeManager->getStorage('zinco_retos_soluciones')->load($solution_id);
      if (!$solution) {
        return;
      }

      $reto_label = '';
      $reto_entity = NULL;
      if ($solution->hasField('field_reto_asociado') && !$solution->get('field_reto_asociado')->isEmpty()) {
        $reto_entity = $solution->get('field_reto_asociado')->entity;
        if ($reto_entity) {
          $reto_label = $reto_entity->label();
        }
      }

      $variables = [
        'reto_label' => $reto_label,
        'solution_label' => $solution->label(),
        'evaluator_name' => $current_user->getDisplayName(),
        'evaluation_date' => $date_formatter->format(\Drupal::time()->getRequestTime(), 'custom', 'd/m/Y H:i'),
        'base_url' => $base_url,
        'solution_url' => $base_url . '/retos/evaluar/' . $solution_id,
      ];

      // Track who has been notified to avoid duplicate emails.
      $notified_uids = [];

      // 1. Notify administrators and administrador_ecosistema.
      $admin_uids = \Drupal::entityQuery('user')
        ->condition('status', 1)
        ->condition('roles', ['administrator', 'administrador_ecosistema'], 'IN')
        ->accessCheck(FALSE)
        ->execute();

      if (!empty($admin_uids)) {
        foreach (\Drupal\user\Entity\User::loadMultiple($admin_uids) as $user) {
          if (!in_array($user->id(), $notified_uids) && ($email = $user->getEmail())) {
            $notified_uids[] = $user->id();
            $variables['recipient_name'] = $user->getDisplayName();
            $this->mailService->sendTemplatedEmail(
              $email,
              'Propuesta evaluada: ' . $solution->label(),
              'email_evaluacion_calificada',
              $variables
            );
          }
        }
      }

      // 2. Notify reto organizers.
      if ($reto_entity && $reto_entity->hasField('organizador_reto') && !$reto_entity->get('organizador_reto')->isEmpty()) {
        $actor_ids = array_column($reto_entity->get('organizador_reto')->getValue(), 'target_id');

        $organizer_uids = \Drupal::entityQuery('user')
          ->condition('status', 1)
          ->condition('field_actor', $actor_ids, 'IN')
          ->accessCheck(FALSE)
          ->execute();

        if (!empty($organizer_uids)) {
          foreach (\Drupal\user\Entity\User::loadMultiple($organizer_uids) as $user) {
            if (!in_array($user->id(), $notified_uids) && ($email = $user->getEmail())) {
              $notified_uids[] = $user->id();
              $variables['recipient_name'] = $user->getDisplayName();
              $this->mailService->sendTemplatedEmail(
                $email,
                'Propuesta evaluada: ' . $solution->label(),
                'email_evaluacion_calificada',
                $variables
              );
            }
          }
        }
      }
    }
    catch (\Throwable $e) {
      \Drupal::logger('zinco_front')->warning('Could not send evaluation notification: @message', ['@message' => $e->getMessage()]);
    }
  }

}
