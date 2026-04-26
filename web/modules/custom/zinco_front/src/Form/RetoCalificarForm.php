<?php

namespace Drupal\zinco_front\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\Component\Render\FormattableMarkup;

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
   * Constructs a new RetoCalificarForm.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager)
  {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('entity_type.manager')
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

    $form['#attributes']['class'][] = 'zinco-form-premium';

    $form['header'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['evaluation-header', 'mb-5', 'p-4', 'bg-light', 'rounded', 'shadow-sm']],
    ];

    $form['header']['title'] = [
      '#type' => 'markup',
      '#markup' => '<h2 class="h4 text-primary mb-2">' . $this->t('Evaluación de la Solución: @title', ['@title' => $solution->label()]) . '</h2>',
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

        $form['criterios_wrapper']['criterios'][$paragraph->id()] = [
          '#type' => 'select',
          '#title' => new FormattableMarkup('<span style="color: black; font-weight: bold;">@title</span>', ['@title' => $nombre_criterio]),
          '#description' => $descripcion_criterio . ' <br><span class="badge bg-info text-dark">' . $this->t('Peso: @peso%', ['@peso' => $porcentaje_calculado]) . '</span>',
          '#options' => $options,
          '#required' => TRUE,
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
      '#value' => $this->t('Finalizar Evaluación'),
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

    try {
      // Create the evaluation entity.
      $solution = $this->entityTypeManager->getStorage('zinco_retos_soluciones')->load($solution_id);
      $solution_label = $solution ? $solution->label() : $solution_id;

      $evaluation_storage = $this->entityTypeManager->getStorage('zinco_retos_evaluacion');
      $evaluation = $evaluation_storage->create([
        'label' => 'Evaluación: ' . $solution_label,
        'field_solucion_evaluada' => $solution_id,
        'field_evaluador' => $current_user_id,
        'field_retroalimentacion' => $retroalimentacion,
        'field_fecha_de_evaluacion' => date('Y-m-d\TH:i:s'),
      ]);

      // Handle scores if the field exists.
      if ($evaluation->hasField('field_puntuacion_de_solucion')) {
        $puntuaciones = [];
        $paragraph_storage = $this->entityTypeManager->getStorage('paragraph');

        foreach ($criterios_values as $paragraph_id => $rating_id) {
          // Load the criteria paragraph to find the associated taxonomy term.
          $criteria_paragraph = $paragraph_storage->load($paragraph_id);
          if ($criteria_paragraph) {
            $term_id = NULL;
            // Check for potential field names referencing the taxonomy term.
            if ($criteria_paragraph->hasField('field_criterio') && !$criteria_paragraph->get('field_criterio')->isEmpty()) {
              $term_id = $criteria_paragraph->get('field_criterio')->target_id;
            }
            elseif ($criteria_paragraph->hasField('field_criterio_evaluacion') && !$criteria_paragraph->get('field_criterio_evaluacion')->isEmpty()) {
              $term_id = $criteria_paragraph->get('field_criterio_evaluacion')->target_id;
            }

            // Only create the score paragraph if we have a valid term ID.
            if ($term_id) {
              $puntuacion_paragraph = $paragraph_storage->create([
                'type' => 'puntuacion_de_criterios_de_reto',
                'field_criterio_evaluacion_reto' => $term_id,
                'field_calificacion_de_solucion_a' => $rating_id,
              ]);
              $puntuacion_paragraph->save();
              $puntuaciones[] = [
                'target_id' => $puntuacion_paragraph->id(),
                'target_revision_id' => $puntuacion_paragraph->getRevisionId(),
              ];
            }
          }
        }
        $evaluation->set('field_puntuacion_de_solucion', $puntuaciones);
      }

      $evaluation->save();

      $this->messenger()->addStatus($this->t('La evaluación ha sido guardada exitosamente.'));
      $form_state->setRedirect('zinco_front.reto_evaluar_list');
    } catch (\Exception $e) {
      $this->messenger()->addError($this->t('Error al guardar la calificación: @message', ['@message' => $e->getMessage()]));
      \Drupal::logger('zinco_front')->error('Error saving evaluation: @message', ['@message' => $e->getMessage()]);
    }
  }

}
