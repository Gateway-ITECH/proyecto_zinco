<?php

namespace Drupal\zinco_front\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\zinco_front\Form\ZincoWizardForm;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a ZincoFront controller for Retos.
 */
class RetosController extends ControllerBase
{

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The file URL generator.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * Constructs a new RetosController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator
   *   The file URL generator.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, RequestStack $request_stack, FormBuilderInterface $form_builder, FileUrlGeneratorInterface $file_url_generator)
  {
    $this->entityTypeManager = $entity_type_manager;
    $this->requestStack = $request_stack;
    $this->formBuilder = $form_builder;
    $this->fileUrlGenerator = $file_url_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('request_stack'),
      $container->get('form_builder'),
      $container->get('file_url_generator')
    );
  }

  /**
   * Returns a list of retos.
   *
   * @return array
   *   A renderable array.
   */
  public function listarRetos()
  {
    $retos = [];
    $filters_param = $this->requestStack->getCurrentRequest()->query->get('filters');
    $search_term = $this->requestStack->getCurrentRequest()->query->get('search_term');
    $estado_ids = [];
    if (!empty($filters_param)) {
      $estados_params = explode(',', $filters_param);
      foreach ($estados_params as $filter) {
        if (preg_match('/^estado-(\d+)$/', $filter, $matches)) {
          $estado_ids[] = (int) $matches[1];
        }
      }
    }
    $areas_enfoque_ids = [];
    if (!empty($filters_param)) {
      $areas_enfoque_params = explode(',', $filters_param);
      foreach ($areas_enfoque_params as $filter) {
        if (preg_match('/^categoria-(\d+)$/', $filter, $matches)) {
          $areas_enfoque_ids[] = (int) $matches[1];
        }
      }
    }

    // Get all terms from 'estados_de_retos_de_innovacion' taxonomy.
    $estado_terms = [];
    try {
      $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
      $query = $term_storage->getQuery()
        ->condition('vid', 'estados_de_retos_de_innovacion')
        ->condition('status', 1)
        ->accessCheck(FALSE);
      $tids = $query->execute();
      $terms = $term_storage->loadMultiple($tids);

      foreach ($terms as $term) {
        $estado_terms[] = [
          'id' => $term->id(),
          'label' => $term->label(),
        ];
      }
    } catch (\Exception $e) {
      $this->messenger()->addError($this->t('Error loading estado terms: @message', ['@message' => $e->getMessage()]));
    }

    // Get all terms from 'area_enfoque' taxonomy.
    $area_enfoque_terms = [];
    try {
      $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
      $query = $term_storage->getQuery()
        ->condition('vid', 'tecnologias_clave')
        ->accessCheck(FALSE);
      $tids = $query->execute();
      $terms = $term_storage->loadMultiple($tids);

      foreach ($terms as $term) {
        $area_enfoque_terms[] = [
          'id' => $term->id(),
          'label' => $term->label(),
        ];
      }
    } catch (\Exception $e) {
      $this->messenger()->addError($this->t('Error loading area_enfoque terms: @message', ['@message' => $e->getMessage()]));
    }




    try {
      $reto_storage = $this->entityTypeManager->getStorage('zinco_retos_innovacion');
      $query = $reto_storage->getQuery();
      if (!empty($filters_param)) {
        $bundle_ids = explode(',', $filters_param);
        // Assuming 'area_enfoque' is the field to filter by.

        if (!empty($areas_enfoque_ids)) {
          $query->condition('area_enfoque', $areas_enfoque_ids, 'IN');
        }
        if (!empty($estado_ids)) {
          $query->condition('estado_reto_innovacion', $estado_ids, 'IN');
        }
      }
      if (!empty($search_term)) {
        $query->condition('label', $search_term, 'CONTAINS');
      }

      $query->accessCheck(FALSE);
      $pager = $query->pager(9); // Display 9 retos per page.
      $reto_ids = $pager->execute();
      $retos = $reto_storage->loadMultiple($reto_ids);

      $estado_colors = [
        'abierto' => 'text-bg-success',
        'en_progreso' => 'text-bg-warning',
        'cerrado' => 'text-bg-danger',
        'default' => 'text-bg-secondary',
      ];

      // Convert loaded entities to renderable arrays and add ID.
      $retos = array_map(function ($reto) use ($estado_colors) {
        $reto_data['id'] = $reto->id();
        $reto_data['label'] = $reto->label();
        $reto_data['description'] = $reto->hasField('description') && !$reto->get('description')->isEmpty() ? $reto->get('description')->value : '';
        $reto_data['fecha_inicio'] = $reto->hasField('fecha_inicio') && !$reto->get('fecha_inicio')->isEmpty() ? $reto->get('fecha_inicio')->value : '';
        $reto_data['fecha_fin'] = $reto->hasField('fecha_fin') && !$reto->get('fecha_fin')->isEmpty() ? $reto->get('fecha_fin')->value : '';

        // Calculate days remaining until fecha_fin.
        if (!empty($reto_data['fecha_fin'])) {
          $current_date = new \DateTime();
          $end_date = new \DateTime($reto_data['fecha_fin']);
          $interval = $current_date->diff($end_date);
          $reto_data['days_remaining'] = $interval->days;
        } else {
          $reto_data['days_remaining'] = 0;
        }
        // Get the label of the 'estado_reto_innovacion' taxonomy term.
        if ($reto->hasField('estado_reto_innovacion') && !$reto->get('estado_reto_innovacion')->isEmpty()) {
          $estado_tid = $reto->get('estado_reto_innovacion')->target_id;
          $estado_term = $this->entityTypeManager->getStorage('taxonomy_term')->load($estado_tid);
          $reto_data['estado_reto_innovacion'] = $estado_term ? $estado_term->label() : '';
          $reto_data['estado_color'] = 'text-bg-secondary';
        } else {
          $reto_data['estado_reto_innovacion'] = '';
          $reto_data['estado_color'] = 'text-bg-secondary';
        }

        // Get the labels of the 'area_enfoque' taxonomy terms.
        $reto_data['area_enfoque'] = [];
        if ($reto->hasField('area_enfoque') && !$reto->get('area_enfoque')->isEmpty()) {
          foreach ($reto->get('area_enfoque')->referencedEntities() as $term) {
            $reto_data['area_enfoque'][] = $term->label();
          }
        }

        // Get the label of the 'organizador_reto' entity.
        if ($reto->hasField('organizador_reto') && !$reto->get('organizador_reto')->isEmpty()) {
          $organizador_names = [];
          foreach ($reto->get('organizador_reto')->referencedEntities() as $organizador_entity) {
            if ($organizador_entity) {
              $organizador_names[] = $organizador_entity->label();
            }
          }
          $reto_data['organizador_reto'] = implode(', ', $organizador_names);
        } else {
          $reto_data['organizador_reto'] = '';
        }

        //$reto_data['visibilidad_reto'] = $reto->hasField('visibilidad_reto') && !$reto->get('visibilidad_reto')->isEmpty() ? ($reto->get('visibilidad_reto')->value ? 'Público' : 'Privado') : 'Privado';

        // Generate profile link.
        $reto_data['profile_link'] = '/retos/' . $reto->id();


        return $reto_data;
      }, $retos);
    } catch (\Exception $e) {
      $this->messenger()->addError($this->t('Error loading retos: @message', ['@message' => $e->getMessage()]));
    }

    return [
      '#theme' => 'zinco_retos_list',
      '#retos' => $retos,
      '#estado_terms' => $estado_terms, // Pass the estado terms to the Twig template.
      '#area_enfoque_terms' => $area_enfoque_terms, // Pass the area_enfoque terms to the Twig template.
      '#cache' => [
        'tags' => $this->entityTypeManager->getDefinition('zinco_retos_innovacion')->getListCacheTags(),
        'contexts' => ['url.query_args'],
      ],
      '#pager' => [
        '#type' => 'pager',
        '#element' => 0,
      ],
      '#attached' => [
        'library' => [
          'zinco_front/zinco-retos-list',
        ],
      ],
    ];
  }

  /**
   * Returns a reto detail page.
   *
   * @param int $reto_id
   *   The ID of the reto to display.
   *
   * @return array
   *   A renderable array.
   */
  public function verDetalleReto($reto_id)
  {
    try {
      $reto_storage = $this->entityTypeManager->getStorage('zinco_retos_innovacion');
      $reto = $reto_storage->load($reto_id);

      if (!$reto) {
        throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
      }

      $reto_data = [];
      $reto_data['id'] = $reto->id();
      $reto_data['label'] = $reto->label();
      $reto_data['description'] = $reto->hasField('description') && !$reto->get('description')->isEmpty() ? $reto->get('description')->value : '';
      $reto_data['fecha_inicio'] = $reto->hasField('fecha_inicio') && !$reto->get('fecha_inicio')->isEmpty() ? $reto->get('fecha_inicio')->value : '';
      $reto_data['fecha_fin'] = $reto->hasField('fecha_fin') && !$reto->get('fecha_fin')->isEmpty() ? $reto->get('fecha_fin')->value : '';

      // Calculate days remaining until fecha_fin.
      if (!empty($reto_data['fecha_fin'])) {
        $current_date = new \DateTime();
        $end_date = new \DateTime($reto_data['fecha_fin']);
        $interval = $current_date->diff($end_date);
        $reto_data['days_remaining'] = $interval->days;
      } else {
        $reto_data['days_remaining'] = 0;
      }

      // Get the label of the 'estado_reto_innovacion' taxonomy term.
      if ($reto->hasField('estado_reto_innovacion') && !$reto->get('estado_reto_innovacion')->isEmpty()) {
        $estado_tid = $reto->get('estado_reto_innovacion')->target_id;
        $estado_term = $this->entityTypeManager->getStorage('taxonomy_term')->load($estado_tid);
        $reto_data['estado_reto_innovacion'] = $estado_term ? $estado_term->label() : '';
        $reto_data['estado_icono'] = $estado_term && $estado_term->hasField('field_icono') && !$estado_term->get('field_icono')->isEmpty() ? $estado_term->get('field_icono')->value : '';
      } else {
        $reto_data['estado_reto_innovacion'] = '';
        $reto_data['estado_icono'] = '';
      }

      // Get the labels of the 'area_enfoque' taxonomy terms.
      $reto_data['area_enfoque'] = [];
      if ($reto->hasField('area_enfoque') && !$reto->get('area_enfoque')->isEmpty()) {
        foreach ($reto->get('area_enfoque')->referencedEntities() as $term) {
          $reto_data['area_enfoque'][] = $term->label();
        }
      }

      // Get the label of the 'organizador_reto' entity.
      if ($reto->hasField('organizador_reto') && !$reto->get('organizador_reto')->isEmpty()) {
        $organizador_names = [];
        foreach ($reto->get('organizador_reto')->referencedEntities() as $organizador_entity) {
          if ($organizador_entity) {
            $organizador_names[] = $organizador_entity->label();
          }
        }
        $reto_data['organizador_reto'] = implode(', ', $organizador_names);
      } else {
        $reto_data['organizador_reto'] = '';
      }

      //agregar campo descripcion_corta
      $reto_data['descripcion_corta'] = $reto->hasField('field_descripcion_corta') && !$reto->get('field_descripcion_corta')->isEmpty() ? $reto->get('field_descripcion_corta')->value : '';

      //agregar campo requisitos_restricciones
      $reto_data['requisitos_restricciones'] = $reto->hasField('field_requisitos_restricciones') && !$reto->get('field_requisitos_restricciones')->isEmpty() ? $reto->get('field_requisitos_restricciones')->value : '';

      //agregar campo criterios_reto tipo paragraph con varios campos de texto
      $reto_data['criterios_reto'] = [];
      if ($reto->hasField('field_criterios_reto') && !$reto->get('field_criterios_reto')->isEmpty()) {
        foreach ($reto->get('field_criterios_reto') as $item) {
          $paragraph = $item->entity;
          if ($paragraph) {
            $reto_data['criterios_reto'][] = [
              'field_descripcion_criterio' => $paragraph->get('field_descripcion_criterio')->value,
              'field_nombre_criterio' => $paragraph->get('field_nombre_criterio')->value,
              'field_peso_criterio' => $paragraph->get('field_peso_criterio')->value
            ];
          }
        }
      }

      //agregar campo recompensas_reto
      $reto_data['recompensas_reto'] = $reto->hasField('recompensas_reto') && !$reto->get('recompensas_reto')->isEmpty() ? $reto->get('recompensas_reto')->value : '';


      // Obtener las entidades de tipo zinco_retos_soluciones relacionadas con el reto actual.
      $reto_data['soluciones'] = [];
      $soluciones_query = $this->entityTypeManager->getStorage('zinco_retos_soluciones')->getQuery()
        ->condition('field_reto_asociado', $reto->id())
        ->condition('status', 1)
        ->sort('created', 'DESC')
        ->range(0, 9) // Limitar a las 10 soluciones más recientes.
        ->accessCheck(FALSE)
        ->execute();

      if (!empty($soluciones_query)) {
        $soluciones = $this->entityTypeManager->getStorage('zinco_retos_soluciones')->loadMultiple($soluciones_query);
        foreach ($soluciones as $solucion) {
          $reto_data['soluciones'][] = [
            'id' => $solucion->id(),
            'label' => $solucion->label(),
            'description' => $solucion->get('description')->value,
            'author' => $solucion->getOwner()->getDisplayName(),
            // Add other fields as needed.
          ];
        }
      }


      return [
        '#theme' => 'zinco_reto_detail',
        '#reto' => $reto_data,
        '#cache' => [
          'tags' => $this->entityTypeManager->getDefinition('zinco_retos_innovacion')->getListCacheTags(),
          'contexts' => ['url'],
        ],
        '#attached' => [
          'library' => [
            'zinco_front/zinco-reto-detail',
          ],
        ],
      ];
    } catch (\Exception $e) {
      $this->messenger()->addError($this->t('Error loading reto: @message', ['@message' => $e->getMessage()]));
      return [];
    }
  }

  /**
   * Returns a solution detail page.
   *
   * @param int $solution_id
   *   The ID of the solution to display.
   *
   * @return array
   *   A renderable array.
   */
  public function solutionDetail($solution_id)
  {
    try {
      $solution_storage = $this->entityTypeManager->getStorage('zinco_retos_soluciones');
      $solution = $solution_storage->load($solution_id);

      if (!$solution) {
        throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
      }

      $solution_data = [];
      $solution_data['id'] = $solution->id();
      $solution_data['label'] = $solution->label();
      $solution_data['description'] = $solution->hasField('description') && !$solution->get('description')->isEmpty() ? $solution->get('description')->value : '';
      $solution_data['authors'] = [];
      if ($solution->hasField('field_autores_solucion') && !$solution->get('field_autores_solucion')->isEmpty()) {
        foreach ($solution->get('field_autores_solucion')->referencedEntities() as $actor_entity) {
          if ($actor_entity) {
            $solution_data['authors'][] = [
              'name' => $actor_entity->label(),
              'profile_link' => '/actores/' . $actor_entity->id(),
            ];
          }
        }
      }
      $solution_data['created'] = $solution->hasField('created') && !$solution->get('created')->isEmpty() ? $solution->get('created')->value : '';

      // Get attached files.
      $solution_data['files'] = [];
      if ($solution->hasField('field_archvos_solucion') && !$solution->get('field_archvos_solucion')->isEmpty()) {
        foreach ($solution->get('field_archvos_solucion') as $file_item) {
          $file_entity = $file_item->entity;
          if ($file_entity) {
            $solution_data['files'][] = [
              'url' => $this->fileUrlGenerator->generate($file_entity->getFileUri())->toString(),
              'filename' => $file_entity->getFilename(),
            ];
          }
        }
      }

      // Load associated reto (challenge).
      $reto_data = [];
      if ($solution->hasField('field_reto_asociado') && !$solution->get('field_reto_asociado')->isEmpty()) {
        $reto_id = $solution->get('field_reto_asociado')->target_id;
        $reto_storage = $this->entityTypeManager->getStorage('zinco_retos_innovacion');
        $reto = $reto_storage->load($reto_id);

        if ($reto) {
          $reto_data['id'] = $reto->id();
          $reto_data['label'] = $reto->label();
          $reto_data['description'] = $reto->hasField('description') && !$reto->get('description')->isEmpty() ? $reto->get('description')->value : '';
          $reto_data['fecha_inicio'] = $reto->hasField('fecha_inicio') && !$reto->get('fecha_inicio')->isEmpty() ? $reto->get('fecha_inicio')->value : '';
          $reto_data['fecha_fin'] = $reto->hasField('fecha_fin') && !$reto->get('fecha_fin')->isEmpty() ? $reto->get('fecha_fin')->value : '';

          // Calculate days remaining until fecha_fin.
          if (!empty($reto_data['fecha_fin'])) {
            $current_date = new \DateTime();
            $end_date = new \DateTime($reto_data['fecha_fin']);
            $interval = $current_date->diff($end_date);
            $reto_data['days_remaining'] = $interval->days;
          } else {
            $reto_data['days_remaining'] = 0;
          }

          // Get the label of the 'estado_reto_innovacion' taxonomy term.
          if ($reto->hasField('estado_reto_innovacion') && !$reto->get('estado_reto_innovacion')->isEmpty()) {
            $estado_tid = $reto->get('estado_reto_innovacion')->target_id;
            $estado_term = $this->entityTypeManager->getStorage('taxonomy_term')->load($estado_tid);
            $reto_data['estado_reto_innovacion'] = $estado_term ? $estado_term->label() : '';
            $reto_data['estado_icono'] = $estado_term && $estado_term->hasField('field_icono') && !$estado_term->get('field_icono')->isEmpty() ? $estado_term->get('field_icono')->value : '';
          } else {
            $reto_data['estado_reto_innovacion'] = '';
            $reto_data['estado_icono'] = '';
          }

          // Get the labels of the 'area_enfoque' taxonomy terms.
          $reto_data['area_enfoque'] = [];
          if ($reto->hasField('area_enfoque') && !$reto->get('area_enfoque')->isEmpty()) {
            foreach ($reto->get('area_enfoque')->referencedEntities() as $term) {
              $reto_data['area_enfoque'][] = $term->label();
            }
          }

          // Get the label of the 'organizador_reto' entity.
          if ($reto->hasField('organizador_reto') && !$reto->get('organizador_reto')->isEmpty()) {
            $organizador_names = [];
            foreach ($reto->get('organizador_reto')->referencedEntities() as $organizador_entity) {
              if ($organizador_entity) {
                $organizador_names[] = $organizador_entity->label();
              }
            }
            $reto_data['organizador_reto'] = implode(', ', $organizador_names);
          } else {
            $reto_data['organizador_reto'] = '';
          }

          $reto_data['descripcion_corta'] = $reto->hasField('field_descripcion_corta') && !$reto->get('field_descripcion_corta')->isEmpty() ? $reto->get('field_descripcion_corta')->value : '';
          $reto_data['requisitos_restricciones'] = $reto->hasField('field_requisitos_restricciones') && !$reto->get('field_requisitos_restricciones')->isEmpty() ? $reto->get('field_requisitos_restricciones')->value : '';
          $reto_data['criterios_reto'] = [];
          if ($reto->hasField('field_criterios_reto') && !$reto->get('field_criterios_reto')->isEmpty()) {
            foreach ($reto->get('field_criterios_reto') as $item) {
              $paragraph = $item->entity;
              if ($paragraph) {
                $reto_data['criterios_reto'][] = [
                  'field_descripcion_criterio' => $paragraph->get('field_descripcion_criterio')->value,
                  'field_nombre_criterio' => $paragraph->get('field_nombre_criterio')->value,
                  'field_peso_criterio' => $paragraph->get('field_peso_criterio')->value
                ];
              }
            }
          }
          $reto_data['recompensas_reto'] = $reto->hasField('recompensas_reto') && !$reto->get('recompensas_reto')->isEmpty() ? $reto->get('recompensas_reto')->value : '';
        }
      }

      return [
        '#theme' => 'zinco_reto_solution_detail',
        '#solution' => $solution_data,
        '#reto' => $reto_data,
        '#cache' => [
          'tags' => $this->entityTypeManager->getDefinition('zinco_retos_soluciones')->getListCacheTags(),
          'contexts' => ['url'],
        ],
        '#attached' => [
          'library' => [
            'zinco_front/zinco-reto-detail', // Reusing reto detail library for now.
          ],
        ],
      ];
    } catch (\Exception $e) {
      $this->messenger()->addError($this->t('Error loading solution: @message', ['@message' => $e->getMessage()]));
      return [];
    }
  }

  /**
   * Displays the reto solution submission form.
   *
   * @param int $reto_id
   *   The ID of the reto to submit a solution for.
   *
   * @return array
   *   A renderable array containing the form.
   */
  public function submitRetoSolutionForm($reto_id)
  {
    $entity = $this->entityTypeManager()->getStorage('zinco_retos_soluciones')->create([
      'reto_id' => $reto_id,
    ]);
    //asignar id del reto al campo field_reto_asociado
    $entity->set('field_reto_asociado', $reto_id);

    // Load the 'No revisado' term from 'estados_de_postulacion_a_retos' vocabulary.
    $term_storage = $this->entityTypeManager()->getStorage('taxonomy_term');
    $terms = $term_storage->loadByProperties([
      'vid' => 'estados_de_postulacion_a_retos',
      'name' => 'No revisado',
    ]);
    $no_revisado_term = reset($terms);

    if ($no_revisado_term) {
      $entity->set('field_estado_postulacion_idea', $no_revisado_term->id());
    }

    $form = $this->entityFormBuilder()->getForm($entity, 'frontend_add');
    $form['field_reto_asociado']['#access'] = FALSE;
    $form['field_retroalimentacion']['#access'] = FALSE;
    $form['field_revisores_postulacion']['#access'] = FALSE;
    $form['field_estado_postulacion_idea']['#access'] = FALSE;
    //
    $form['contextual_alert'] = [
      '#type' => 'markup',
      '#markup' => $this->t('<div class="alert alert-info">En caso que no encuentre el nombre del autor puede registrarlo <a href="/actores/categorias">aquí</a></div>'),
      // Asignar un peso negativo lo coloca al principio del formulario.
      // Los elementos del formulario principal suelen tener pesos cercanos a 0 o positivos.
      '#weight' => 2,
    ];
    //$form['#submit'][] = [$this, 'retosPropuestasSubmitHandler'];

    return [
      '#theme' => 'zinco_reto_solution_form',
      '#form' => $form,
      '#reto_id' => $reto_id,
      '#cache' => [
        'contexts' => ['url.query_args'],
      ],
    ];
  }

  /**
   * Returns a list of retos to evaluate.
   *
   * @return array
   *   A renderable array.
   */
  public function listadoRetosEvaluar()
  {
    $soluciones_data = [];
    //obtener id del usuario actual
    $current_user = \Drupal::currentUser();
    $user_id = $current_user->id();
    try {
      $reto_solucion_storage = $this->entityTypeManager->getStorage('zinco_retos_soluciones');
      $reto_innovacion_storage = $this->entityTypeManager->getStorage('zinco_retos_innovacion');
      $actor_storage = $this->entityTypeManager->getStorage('zinco_actors_zincoactors');
      $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');

      // Get the 'En evaluación' term ID from 'estados_de_postulacion_a_retos' vocabulary.
      $terms = $term_storage->loadByProperties([
        'vid' => 'estados_de_postulacion_a_retos',
        'name' => 'En revisión',
      ]);
      $en_evaluacion_term = reset($terms);

      if (!$en_evaluacion_term) {
        $this->messenger()->addWarning($this->t('The "En evaluación" term was not found in the "estados_de_postulacion_a_retos" vocabulary.'));
        return [];
      }

      // Query zinco_retos_soluciones where the current user is a reviewer and the solution is 'En evaluación'.
      $solucion_query = $reto_solucion_storage->getQuery()
        ->condition('field_revisores_postulacion', $user_id, 'IN')
        ->condition('field_estado_postulacion_idea', $en_evaluacion_term->id())
        ->accessCheck(FALSE);
      $solucion_ids = $solucion_query->execute();

      if (!empty($solucion_ids)) {
        $soluciones = $reto_solucion_storage->loadMultiple($solucion_ids);
        foreach ($soluciones as $solucion) {
          $solucion_item = [];
          $solucion_item['id'] = $solucion->id();
          $solucion_item['label'] = $solucion->label();
          $solucion_item['description'] = $solucion->hasField('description') && !$solucion->get('description')->isEmpty() ? $solucion->get('description')->value : '';

          // Get authors.
          $authors = [];
          if ($solucion->hasField('field_autores_solucion') && !$solucion->get('field_autores_solucion')->isEmpty()) {
            foreach ($solucion->get('field_autores_solucion')->referencedEntities() as $actor_entity) {
              if ($actor_entity) {
                $authors[] = $actor_entity->label();
              }
            }
          }
          $solucion_item['authors'] = implode(', ', $authors);

          // Get associated reto name.
          $reto_name = '';
          if ($solucion->hasField('field_reto_asociado') && !$solucion->get('field_reto_asociado')->isEmpty()) {
            $reto_id = $solucion->get('field_reto_asociado')->target_id;
            $reto_entity = $reto_innovacion_storage->load($reto_id);
            if ($reto_entity) {
              $reto_name = $reto_entity->label();
            }
          }
          $solucion_item['reto_asociado'] = $reto_name;
          $solucion_item['reto_asociado_id'] = $reto_id; // Add reto ID to solution data.
          $solucion_item['evaluate_link'] = '/retos/evaluar/' . $solucion->id(); // Link to evaluate the specific solution.

          $soluciones_data[] = $solucion_item;
        }
      }
    } catch (\Exception $e) {
      $this->messenger()->addError($this->t('Error loading solutions for evaluation: @message', ['@message' => $e->getMessage()]));
    }

    return [
      '#theme' => 'zinco_retos_evaluar_list',
      '#soluciones' => $soluciones_data, // Pass solutions data to the Twig template.
      '#cache' => [
        'tags' => $this->entityTypeManager->getDefinition('zinco_retos_soluciones')->getListCacheTags(),
        'contexts' => ['url.query_args', 'user'],
      ],
      '#attached' => [
        'library' => [
          'zinco_front/zinco-retos-evaluar-list',
        ],
      ],
    ];
  }

  /**
   * Displays the reto solution evaluation form.
   *
   * @param int $solution_id
   *   The ID of the solution to evaluate.
   *
   * @return array
   *   A renderable array containing the form.
   */
  public function calificarReto($solution_id)
  {
    try {
      //obtener usuario actual
      $current_user = \Drupal::currentUser();
      $user_id = $current_user->id();
      //obtener fecha actual y hora actual
      $current_datetime = new \DateTime();
      $formatted_datetime = $current_datetime->format('Y-m-d\TH:i:s');
      //crear entidad vacia de tipo zinco_retos_evaluaciones
      $entity = $this->entityTypeManager()->getStorage('zinco_retos_evaluacion')->create([
        'field_solucion_evaluada' => $solution_id,
        'field_evaluador' => $user_id
      ]);

      //cargar form de entity
      $form = $this->entityFormBuilder()->getForm($entity, 'frontend_add');
      //ocultar campos no necesarios
      $form['field_solucion_evaluada']['#access'] = FALSE;
      $form['field_evaluador']['#access'] = FALSE;
      $form['field_fecha_de_evaluacion']['#access'] = FALSE;


      return [
        '#theme' => 'zinco_reto_calificar',
        '#form' => $form,
        '#solution_id' => $solution_id,
        '#cache' => [
          'contexts' => ['url.query_args'],
        ],
        '#attached' => [
          'library' => [
            'zinco_front/zinco-reto-calificar',
          ],
        ],
      ];
    } catch (\Exception $e) {
      $this->messenger()->addError($this->t('Error loading solution for evaluation: @message', ['@message' => $e->getMessage()]));
      return [];
    }
  }

  /**
   * Displays the zinco_retos_innovacion entity add form for the 'reto' bundle.
   *
   * @return array
   *   A renderable array containing the form.
   */
  public function addRetoForm()
  {
    $entity = $this->entityTypeManager->getStorage('zinco_retos_innovacion')->create([
      'bundle' => 'reto',
      'status' => FALSE,
    ]);

    // Set the current user's actor as the default organizer.
    $current_user = \Drupal::currentUser();
    $user_entity = \Drupal\user\Entity\User::load($current_user->id());
    if ($user_entity && $user_entity->hasField('field_actor') && !$user_entity->get('field_actor')->isEmpty()) {
      $actor_id = $user_entity->get('field_actor')->target_id;
      $entity->set('organizador_reto', [$actor_id]);
    }

    $form = $this->entityFormBuilder()->getForm($entity, 'actor_add');

    // Hide fields for actors.
    $hidden_fields = ['estado_reto_innovacion', 'aprobado_por', 'organizador_reto'];
    foreach ($hidden_fields as $field_name) {
      if (isset($form[$field_name])) {
        $form[$field_name]['#access'] = FALSE;
      }
    }

    return [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['container', 'mt-5', 'mb-5', 'p-4', 'bg-white', 'shadow-sm', 'rounded'],
      ],
      'title' => [
        '#type' => 'html_tag',
        '#tag' => 'h2',
        '#value' => $this->t('Publicar Nuevo Reto de Innovación'),
        '#attributes' => ['class' => ['mb-4', 'text-primary', 'border-bottom', 'pb-3']],
      ],
      'form' => $form,
    ];
  }

}