<?php

namespace Drupal\zinco_front\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\File\FileSystemInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Provides a ZincoFront controller for Actores.
 */
class ActoresController extends ControllerBase
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
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   *   The file URL generator.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * The entity form builder.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilderInterface
   */
  protected $entityFormBuilder;

  /**
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * Constructs a new ActoresController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator
   *   The file URL generator service.
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $entity_form_builder
   *   The entity form builder.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, RequestStack $request_stack, FileSystemInterface $file_system, FileUrlGeneratorInterface $file_url_generator, EntityFormBuilderInterface $entity_form_builder, EntityTypeBundleInfoInterface $entity_type_bundle_info)
  {
    $this->entityTypeManager = $entity_type_manager;
    $this->requestStack = $request_stack;
    $this->fileSystem = $file_system;
    $this->fileUrlGenerator = $file_url_generator;
    $this->entityFormBuilder = $entity_form_builder;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('request_stack'),
      $container->get('file_system'),
      $container->get('file_url_generator'),
      $container->get('entity.form_builder'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * Returns a list of actors.
   *
   * @return array
   *   A renderable array.
   */
  public function listarActores($category_tid = NULL)
  {
    $actors = [];
    $filters_param = $this->requestStack->getCurrentRequest()->query->get('filters');
    $search_term = $this->requestStack->getCurrentRequest()->query->get('search_term');
    $cache_tags = $this->entityTypeManager->getDefinition('zinco_actors_zincoactors')->getListCacheTags();
    $category_name = '';

    try {
      $actor_storage = $this->entityTypeManager->getStorage('zinco_actors_zincoactors');
      $query = $actor_storage->getQuery();

      $bundle_ids_to_filter = [];
      $bundles = [];


      // If a category TID is provided, filter bundles by it.
      if ($category_tid) {
        $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
        $term = $term_storage->load($category_tid);
        $category_name = $term ? $term->label() : '';

        if (!$term || $term->bundle() !== 'categorias_de_actores') {
          $this->messenger()->addError($this->t('Invalid actor category provided.'));
          return $this->redirect('zinco_front.actor_categories_list');
        }
        $cache_tags = array_merge($cache_tags, $term->getCacheTags());

        $zinco_actors_categories_storage = $this->entityTypeManager->getStorage('zinco_actors_categories');
        $category_query = $zinco_actors_categories_storage->getQuery()
          ->condition('field_actor_category', $category_tid)
          ->accessCheck(FALSE);
        $category_entity_ids = $category_query->execute();
        $category_entities = $zinco_actors_categories_storage->loadMultiple($category_entity_ids);

        foreach ($category_entities as $category_entity) {
          //obtener info del bundle
          $bundle_entity = $this->entityTypeManager->getStorage('zinco_actors_zincoactors_type')->load($category_entity->get('field_actor_bundle')->value);

          $bundle = [
            'id' => $category_entity->get('field_actor_bundle')->value,
            'label' => $bundle_entity->label(),
          ];
          $bundles[] = $bundle;
          $bundle_ids_to_filter[] = $category_entity->get('field_actor_bundle')->value;
        }
        $cache_tags = array_merge($cache_tags, $this->entityTypeManager->getDefinition('zinco_actors_categories')->getListCacheTags());

        if (empty($bundle_ids_to_filter)) {
          $this->messenger()->addWarning($this->t('No actor bundles found for the selected category.'));
          return $this->redirect('zinco_front.actor_categories_list');
        }
        $query->condition('bundle', $bundle_ids_to_filter, 'IN');
      }

      if (!empty($filters_param)) {
        $bundle_ids = explode(',', $filters_param);
        $query->condition('bundle', $bundle_ids, 'IN');
      }
      if (!empty($search_term)) {
        $query->condition('label', $search_term, 'CONTAINS');
      }
      // If no filters are applied, you might want to show all actors or a default set.
      // For now, if no filters are present, no bundle condition is added, effectively showing all.
      $query->accessCheck(FALSE);
      $pager = $query->pager(9);
      $actor_ids = $pager->execute();
      $actors = $actor_storage->loadMultiple($actor_ids);

      $bundle_colors = [

      ];

      // Convert loaded entities to renderable arrays and add ID.
      $actors = array_map(function ($actor) use ($bundle_colors) {
        $actor_data['id'] = $actor->id();
        $actor_data['label'] = $actor->label();

        $bundle_id = $actor->bundle();
        $bundle_entity = $this->entityTypeManager->getStorage('zinco_actors_zincoactors_type')->load($bundle_id);
        $bundle_label = $bundle_entity ? $bundle_entity->label() : $bundle_id;
        $actor_data['field_tipo_actor'] = $bundle_label;
        $actor_data['bundle_color'] = $bundle_colors[$bundle_id] ?? 'text-bg-secondary';

        // Get the label of the 'municipio' taxonomy term.
        if ($actor->hasField('municipio') && !$actor->get('municipio')->isEmpty()) {
          $municipio_tid = $actor->get('municipio')->target_id;
          $municipio_term = $this->entityTypeManager->getStorage('taxonomy_term')->load($municipio_tid);
          $actor_data['field_municipio'] = $municipio_term ? $municipio_term->label() : '';
        } else {
          $actor_data['field_municipio'] = '';
        }

        // Add description field.
        $actor_data['description'] = $actor->hasField('description') && !$actor->get('description')->isEmpty() ? $actor->get('description')->value : '';

        // Add 'fomento' field (assuming field_fomento exists).
        $actor_data['field_fomento'] = $actor->hasField('field_fomento') && !$actor->get('field_fomento')->isEmpty() ? $actor->get('field_fomento')->value : '';

        // Add 'website' field (assuming field_website exists).
        $actor_data['field_website'] = $actor->hasField('field_website') && !$actor->get('field_website')->isEmpty() ? $actor->get('field_website')->uri : '';

        // Generate profile link.
        $actor_data['profile_link'] = '/actores/' . $actor->id();

        return $actor_data;
      }, $actors);
    } catch (\Exception $e) {
      $this->messenger()->addError($this->t('Error loading actors: @message', ['@message' => $e->getMessage()]));
    }

    return [
      '#theme' => 'zinco_actores_list',
      '#actors' => $actors,
      '#category_tid' => $category_tid,
      '#category_name' => $category_name,
      '#bundles' => $bundles,
      '#cache' => [
        'tags' => $cache_tags,
        'contexts' => ['url.query_args', 'url.path'],
      ],
      '#pager' => [
        '#type' => 'pager',
        '#element' => 0,
      ],
      '#attached' => [
        'library' => [
          'zinco_front/zinco-actores-list',
        ],
      ],
    ];
  }


  /**
   * Redirects the user to their associated actor profile.
   */
  public function perfil()
  {
    $current_user = $this->entityTypeManager->getStorage('user')->load(\Drupal::currentUser()->id());

    if ($current_user->hasField('field_actor') && !$current_user->get('field_actor')->isEmpty()) {
      $actor_id = $current_user->get('field_actor')->target_id;
      return $this->redirect('zinco_front.actor_profile', ['actor_id' => $actor_id]);
    }

    return $this->redirect('zinco_front.actor_categories_list');
  }

  /**
   * Returns a single actor profile.
   *
   * @param int $actor_id
   *   The ID of the actor to display.
   *
   * @return array
   *   A renderable array.
   */
  public function verPerfilActor(int $actor_id)
  {
    try {
      $actor_storage = $this->entityTypeManager->getStorage('zinco_actors_zincoactors');
      $actor = $actor_storage->load($actor_id);
      $current_user = $this->entityTypeManager->getStorage('user')->load(\Drupal::currentUser()->id());

      if (!$actor) {
        $this->messenger()->addError($this->t('Actor with ID @id not found.', ['@id' => $actor_id]));
        return $this->redirect('zinco_front.actores_list');
      }

      $actor_data = [
        'id' => $actor->id(),
        'label' => $actor->label(),
      ];

      // Load bundle information.
      $bundle_id = $actor->bundle();
      $bundle_entity = $this->entityTypeManager->getStorage('zinco_actors_zincoactors_type')->load($bundle_id);
      $actor_data['bundle_id'] = $bundle_id; // Add bundle_id to actor_data
      $actor_data['field_tipo_actor'] = $bundle_entity ? $bundle_entity->label() : $bundle_id;

      // Get the label of the 'municipio' taxonomy term.
      if ($actor->hasField('municipio') && !$actor->get('municipio')->isEmpty()) {
        $municipio_tid = $actor->get('municipio')->target_id;
        $municipio_term = $this->entityTypeManager->getStorage('taxonomy_term')->load($municipio_tid);
        $actor_data['field_municipio'] = $municipio_term ? $municipio_term->label() : '';
      } else {
        $actor_data['field_municipio'] = '';
      }

      // Add description field.
      $actor_data['description'] = $actor->hasField('description') && !$actor->get('description')->isEmpty() ? $actor->get('description')->value : '';

      //agregar campo imagen de perfil
      $actor_data['imagen_perfil'] = '';
      if ($actor->hasField('imagen_perfil') && !$actor->get('imagen_perfil')->isEmpty()) {
        $image_file = $actor->get('imagen_perfil')->entity;
        if ($image_file) {
          $actor_data['imagen_perfil'] = $this->fileUrlGenerator->generateAbsoluteString($image_file->getFileUri());
        }
      }

      //agregar campo field_nit_empresa_explotadora
      $actor_data['nit_empresa'] = '';
      if ($actor->hasField('field_nit_empresa_explotadora') && !$actor->get('field_nit_empresa_explotadora')->isEmpty()) {
        $actor_data['nit_empresa'] = $actor->get('field_nit_empresa_explotadora')->value;
      }

      //agregar campo field_lineas_negocio_innovacion campo multiple
      $actor_data['lineas_negocio'] = [];
      if ($actor->hasField('field_lineas_negocio_innovacion') && !$actor->get('field_lineas_negocio_innovacion')->isEmpty()) {
        foreach ($actor->get('field_lineas_negocio_innovacion') as $item) {
          $actor_data['lineas_negocio'][] = $item->value;
        }
      }

      //agregar campo field_direccion_empresa 
      $actor_data['datos_contacto']['direccion_empresa'] = '';
      if ($actor->hasField('field_direccion_empresa') && !$actor->get('field_direccion_empresa')->isEmpty()) {
        $actor_data['datos_contacto']['direccion_empresa'] = $actor->get('field_direccion_empresa')->value;
      }

      //agregar campo telefono
      $actor_data['datos_contacto']['telefono'] = '';
      if ($actor->hasField('telefono') && !$actor->get('telefono')->isEmpty()) {
        $actor_data['datos_contacto']['telefono'] = $actor->get('telefono')->value;
      }

      //agregar campo email
      $actor_data['datos_contacto']['email'] = '';
      if ($actor->hasField('email') && !$actor->get('email')->isEmpty()) {
        $actor_data['datos_contacto']['email'] = $actor->get('email')->value;
      }

      // Obtener entidades zinco_proyectos_idi en las que el actor actual se encuentra referenciado.
      $proyectos_idi = [];
      $query = $this->entityTypeManager->getStorage('zinco_proyectos_idi')->getQuery()
        ->condition('field_actores_proyectos.target_id', $actor_id, 'IN')
        ->range(0, 3)
        ->accessCheck(FALSE)
        ->execute();

      if (!empty($query)) {
        $proyectos_idi_entities = $this->entityTypeManager->getStorage('zinco_proyectos_idi')->loadMultiple($query);
        foreach ($proyectos_idi_entities as $proyecto) {
          $proyectos_idi[] = [
            'id' => $proyecto->id(),
            'label' => $proyecto->label(),
            'tipo' => $proyecto->hasField('field_tipo_de_proyecto_idi') && !$proyecto->get('field_tipo_de_proyecto_idi')->isEmpty() ? $proyecto->get('field_tipo_de_proyecto_idi')->entity->label() : '',
            'estado' => $proyecto->hasField('field_estado_proyecto') && !$proyecto->get('field_estado_proyecto')->isEmpty() ? $proyecto->get('field_estado_proyecto')->entity->label() : '',
          ];
        }
      }
      $actor_data['proyectos_idi'] = $proyectos_idi;

      //obtener entidades zinco_retos_innovacion_types en los que el actor actual se encuentra referenciado.
      $retos_innovacion = [];
      $query = $this->entityTypeManager->getStorage('zinco_retos_innovacion')->getQuery()
        ->condition('organizador_reto.target_id', $actor_id, 'IN')
        ->range(0, 3)
        ->accessCheck(FALSE)
        ->execute();
      if (!empty($query)) {
        $retos_innovacion_entities = $this->entityTypeManager->getStorage('zinco_retos_innovacion')->loadMultiple($query);
        foreach ($retos_innovacion_entities as $reto) {
          $retos_innovacion[] = [
            'id' => $reto->id(),
            'label' => $reto->label(),
            'tipo' => $reto->hasField('area_enfoque') && !$reto->get('area_enfoque')->isEmpty() ? $reto->get('area_enfoque')->entity->label() : '',
            'estado' => $reto->hasField('estado_reto_innovacion') && !$reto->get('estado_reto_innovacion')->isEmpty() ? $reto->get('estado_reto_innovacion')->entity->label() : '',
          ];
        }
      }
      $actor_data['retos_innovacion'] = $retos_innovacion;

      //agregar campo sitio_web
      $actor_data['datos_contacto']['sitio_web'] = '';
      if ($actor->hasField('sitio_web') && !$actor->get('sitio_web')->isEmpty()) {
        $actor_data['datos_contacto']['sitio_web'] = $actor->get('sitio_web')->value;
      }

      //agregar campo field_incentivos_recibidos campo multiple
      $actor_data['incentivos_recibidos'] = [];
      if ($actor->hasField('field_incentivos_recibidos') && !$actor->get('field_incentivos_recibidos')->isEmpty()) {
        foreach ($actor->get('field_incentivos_recibidos') as $item) {
          $actor_data['incentivos_recibidos'][] = $item->value;
        }
      }


      //si el bundle es entidad gobierno agregar campo nivel_gobierno
      $actor_data['field_nivel_gobierno'] = '';
      if ($bundle_id == 'entidad_gobierno' && $actor->hasField('field_nivel_gobierno') && !$actor->get('field_nivel_gobierno')->isEmpty()) {
        $actor_data['field_nivel_gobierno'] = 'Nivel ' . $actor->get('field_nivel_gobierno')->entity->label();
      }

      //si el bundle es entidad gobierno agregar campo mision
      $actor_data['field_mision'] = '';
      if (($bundle_id == 'entidad_gobierno' || $bundle_id == 'institucion_de_educacion_superio') && $actor->hasField('field_mision') && !$actor->get('field_mision')->isEmpty()) {
        $actor_data['field_mision'] = $actor->get('field_mision')->value;
      }

      //si el bundle es entidad gobierno agregar campo representante legal
      $actor_data['field_representante_legal'] = '';
      if ($bundle_id == 'entidad_gobierno' && $actor->hasField('field_nombre_representante_legal') && !$actor->get('field_nombre_representante_legal')->isEmpty()) {
        $actor_data['field_representante_legal'] = $actor->get('field_nombre_representante_legal')->value;
      }

      //sie el bundle es entidad gobierno agregar campo cargo representante legal
      $actor_data['field_cargo_representante_legal'] = '';
      if ($bundle_id == 'entidad_gobierno' && $actor->hasField('field_cargo_representante_legal') && !$actor->get('field_cargo_representante_legal')->isEmpty()) {
        $actor_data['field_cargo_representante_legal'] = $actor->get('field_cargo_representante_legal')->value;
      }

      //si el bundle es entidad gobierno agregar campo sede principal
      $actor_data['field_sede_principal'] = '';
      if ($bundle_id == 'entidad_gobierno' && $actor->hasField('field_sede_principal') && !$actor->get('field_sede_principal')->isEmpty()) {
        $actor_data['field_sede_principal'] = $actor->get('field_sede_principal')->value;
      }

      //si el bundle es entidad gobierno agregar campo funcion ecosistema cti
      $actor_data['field_funcion_ecosistema_cti'] = '';
      if ($bundle_id == 'entidad_gobierno' && $actor->hasField('field_funcion_ecosistema_cti') && !$actor->get('field_funcion_ecosistema_cti')->isEmpty()) {
        $actor_data['field_funcion_ecosistema_cti'] = $actor->get('field_funcion_ecosistema_cti')->value;
      }

      //si el bundle es entidad gobierno agregar campo politicas y estrategias multiple
      $actor_data['field_politicas_estrategias'] = [];
      if ($bundle_id == 'entidad_gobierno' && $actor->hasField('field_politicas_y_estrategias') && !$actor->get('field_politicas_y_estrategias')->isEmpty()) {
        foreach ($actor->get('field_politicas_y_estrategias') as $item) {
          $actor_data['field_politicas_y_estrategias'][] = $item->value;
        }
      }

      //si el bundle es entidad gobierno agregar campo marcos regulatorios multiple
      $actor_data['field_marcos_regulatorios'] = [];
      if ($bundle_id == 'entidad_gobierno' && $actor->hasField('field_marcos_regulatorios') && !$actor->get('field_marcos_regulatorios')->isEmpty()) {
        foreach ($actor->get('field_marcos_regulatorios') as $item) {
          $actor_data['field_marcos_regulatorios'][] = $item->value;
        }
      }

      //si el bundle es entidad gobierno agregar campo normativas multiple
      $actor_data['field_normativas'] = [];
      if ($bundle_id == 'entidad_gobierno' && $actor->hasField('field_normativas') && !$actor->get('field_normativas')->isEmpty()) {
        foreach ($actor->get('field_normativas') as $item) {
          $actor_data['field_normativas'][] = $item->value;
        }
      }

      //si el bundle es entidad gobierno agregar campo programas principales multiple
      $actor_data['programas_principales'] = [];
      if ($bundle_id == 'entidad_gobierno' && $actor->hasField('field_programas_principales') && !$actor->get('field_programas_principales')->isEmpty()) {
        foreach ($actor->get('field_programas_principales') as $item) {
          $actor_data['programas_principales'][] = $item->value;
        }
      }

      //si el bundle es entidad gobierno agregar campo convocatorias abiertas multiple
      $actor_data['convocatorias_abiertas'] = [];
      if ($bundle_id == 'entidad_gobierno' && $actor->hasField('field_convocatorias') && !$actor->get('field_convocatorias')->isEmpty()) {
        foreach ($actor->get('field_convocatorias') as $item) {
          $actor_data['convocatorias_abiertas'][] = $item->value;
        }
      }


      //si el bundle es entidad gobierno agregar campo mecanismos financiamiento multiple
      $actor_data['field_mecanismos_financiamiento'] = [];
      if ($bundle_id == 'entidad_gobierno' && $actor->hasField('field_mecanismos_de_financiamien') && !$actor->get('field_mecanismos_de_financiamien')->isEmpty()) {
        foreach ($actor->get('field_mecanismos_de_financiamien') as $item) {
          $actor_data['field_mecanismos_financiamiento'][] = $item->value;
        }
      }

      //si el bundle es instancia de orientacion politica agregar campo miembros multiple
      $actor_data['field_miembros_iep'] = [];
      if ($bundle_id == 'instancias_de_orientacion_politi' && $actor->hasField('field_miembros_iep') && !$actor->get('field_miembros_iep')->isEmpty()) {
        foreach ($actor->get('field_miembros_iep') as $item) {
          $member_entity = $item->entity;
          if ($member_entity) {
            $actor_data['field_miembros_iep'][] = [
              'label' => $member_entity->label(),
              'url' => Url::fromRoute('zinco_front.actor_profile', ['actor_id' => $member_entity->id()])->toString(),
            ];
          }
        }
      }

      //si el bundle es instancia de orientacion politica agregar campo entidad lider
      $actor_data['field_entidad_lider'] = '';
      if ($bundle_id == 'instancias_de_orientacion_politi' && $actor->hasField('field_entidad_lider') && !$actor->get('field_entidad_lider')->isEmpty()) {
        $actor_data['field_entidad_lider'] = $actor->get('field_entidad_lider')->entity->label();
      }

      //si el bundle es institucion de educacion superior agregar campo field_caracter_academico
      $actor_data['field_caracter_academico'] = '';
      if ($bundle_id == 'institucion_de_educacion_superio' && $actor->hasField('field_caracter_academico') && !$actor->get('field_caracter_academico')->isEmpty()) {
        $actor_data['field_caracter_academico'] = $actor->get('field_caracter_academico')->entity->label();
      }

      //si el bundle es institucion de educacion superior agregar campo field_estado_acreditacion
      $actor_data['field_estado_acreditacion'] = [];
      if ($bundle_id == 'institucion_de_educacion_superio' && $actor->hasField('field_estado_acreditacion') && !$actor->get('field_estado_acreditacion')->isEmpty()) {
        $actor_data['field_estado_acreditacion'] = $actor->get('field_estado_acreditacion')->entity->label();
      }

      //si el bundle es institucion de educacion superior agregar campo field_siglas_ies
      $actor_data['field_siglas_ies'] = '';
      if ($bundle_id == 'institucion_de_educacion_superio' && $actor->hasField('field_siglas_ies') && !$actor->get('field_siglas_ies')->isEmpty()) {
        $actor_data['field_siglas_ies'] = $actor->get('field_siglas_ies')->value;
      }

      //si el bundle es institucion de educacion superior agregar campo field_tipo_de_ies
      $actor_data['field_tipo_de_ies'] = '';
      if ($bundle_id == 'institucion_de_educacion_superio' && $actor->hasField('field_tipo_de_ies') && !$actor->get('field_tipo_de_ies')->isEmpty()) {
        $actor_data['field_tipo_de_ies'] = $actor->get('field_tipo_de_ies')->entity->label();
      }

      //si el bundle es institucion de educacion superior agregar campo field_programas_academicos multiple
      $actor_data['field_programas_academicos'] = [];
      if ($bundle_id == 'institucion_de_educacion_superio' && $actor->hasField('field_programas_academicos') && !$actor->get('field_programas_academicos')->isEmpty()) {
        foreach ($actor->get('field_programas_academicos') as $item) {
          $actor_data['field_programas_academicos'][] = $item->value;
        }
      }

      //si el bundle es investigador agregar campo field_afiliacion_actual  
      $actor_data['field_afiliacion_actual'] = '';
      if ($bundle_id == 'investigador' && $actor->hasField('field_afiliacion_actual') && !$actor->get('field_afiliacion_actual')->isEmpty()) {
        $actor_data['field_afiliacion_actual'] = $actor->get('field_afiliacion_actual')->value;
      }

      //si el bundle es investigador agregar campo field_nacionalidad  
      $actor_data['field_nacionalidad'] = '';
      if ($bundle_id == 'investigador' && $actor->hasField('field_nacionalidad') && !$actor->get('field_nacionalidad')->isEmpty()) {
        $actor_data['field_nacionalidad'] = $actor->get('field_nacionalidad')->value;
      }

      //si el bundle es investigador agregar campo field_fecha_de_nacimiento
      $actor_data['field_fecha_de_nacimiento'] = '';
      if ($bundle_id == 'investigador' && $actor->hasField('field_fecha_de_nacimiento') && !$actor->get('field_fecha_de_nacimiento')->isEmpty()) {
        $actor_data['field_fecha_de_nacimiento'] = $actor->get('field_fecha_de_nacimiento')->value;
      }

      //si el bundle es investigador agregar un query para obtener los grupos de investigación en los cuales participa el investigador
      $actor_data['grupos_investigacion'] = [];
      if ($bundle_id == 'investigador') {
        $query = $this->entityTypeManager->getStorage('zinco_grupos_investigacion')->getQuery()
          ->condition('miembros.target_id', $actor_id, 'IN')
          ->accessCheck(FALSE)
          ->execute();
        if (!empty($query)) {
          $grupos_investigacion_entities = $this->entityTypeManager->getStorage('zinco_grupos_investigacion')->loadMultiple($query);
          foreach ($grupos_investigacion_entities as $grupo) {
            $actor_data['grupos_investigacion'][] = [
              'id' => $grupo->id(),
              'label' => $grupo->label(),
            ];
          }
        }
      }

      //si el bundle es investigador agregar campo field_formacion_academica multiple
      $actor_data['field_formacion_academica'] = [];
      if ($bundle_id == 'investigador' && $actor->hasField('field_formacion_academica') && !$actor->get('field_formacion_academica')->isEmpty()) {
        foreach ($actor->get('field_formacion_academica') as $item) {
          $paragraph = $item->entity;
          if ($paragraph) {
            $actor_data['field_formacion_academica'][] = [
              'titulo' => $paragraph->get('field_titulo_obtenido')->value,
              'institucion' => $paragraph->get('field_nombre_institucion')->value,
              'tipo_formacion' => $paragraph->get('field_tipo_de_formacion')->entity ? $paragraph->get('field_tipo_de_formacion')->entity->label() : NULL,
              'fecha_fin' => $paragraph->get('field_fecha_fin_formacion')->value,
              'fecha_inicio' => $paragraph->get('field_fecha_inicio_formacion')->value,
            ];
          }
        }
      }

      //si el bundle es investigador agregar campo field_trayectoria_profesional multiple y paragraph trayectoria_profesional
      $actor_data['field_trayectoria_profesional'] = [];
      if ($bundle_id == 'investigador' && $actor->hasField('field_trayectoria_profesional') && !$actor->get('field_trayectoria_profesional')->isEmpty()) {
        foreach ($actor->get('field_trayectoria_profesional') as $item) {
          $paragraph = $item->entity;
          if ($paragraph) {
            $actor_data['field_trayectoria_profesional'][] = [
              'cargo' => $paragraph->get('field_cargo')->value,
              'empresa' => $paragraph->get('field_nombre_empresa')->value,
              'fecha_inicio' => $paragraph->get('field_fecha_inicio_trayectoria')->value,
              'fecha_fin' => $paragraph->get('field_fecha_fin_trayetoria')->value,
            ];
          }
        }
      }

      //si el id de actor del usuario logueado es igual al id del actor que se esta viendo, agregar una bandera acciones_rapidas en true
      $actor_data['acciones_rapidas'] = false;
      if ($current_user->hasField('field_actor') && !$current_user->get('field_actor')->isEmpty()) {
        if ($actor->id() == $current_user->get('field_actor')->target_id) {
          $actor_data['acciones_rapidas'] = true;
        }
      }




      return [
        '#theme' => 'zinco_actor_profile',
        '#actor' => $actor_data,
        '#cache' => [
          'tags' => $actor->getCacheTags(),
        ],
        '#attached' => [
          'library' => [
            'zinco_front/zinco-actor-profile',
          ],
        ],
      ];
    } catch (\Exception $e) {
      $this->messenger()->addError($this->t('Error loading actor profile: @message', ['@message' => $e->getMessage()]));
      return $this->redirect('zinco_front.actores_list');
    }
  }

  /**
   * Returns a list of actor bundles.
   *
   * @return array
   *   A renderable array.
   */
  public function listActorBundles($category_tid = NULL)
  {
    $bundle_data = [];
    $cache_tags = [];
    $nombre_categoria = '';

    // If a category TID is provided, filter bundles by it.
    if ($category_tid) {
      $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
      $term = $term_storage->load($category_tid);

      //obtener label del termino
      if ($term) {
        $nombre_categoria = $term->label();
      }

      if (!$term || $term->bundle() !== 'categorias_de_actores') {
        $this->messenger()->addError($this->t('Invalid actor category provided.'));
        return $this->redirect('zinco_front.actor_categories_list');
      }

      $zinco_actors_categories_storage = $this->entityTypeManager->getStorage('zinco_actors_categories');
      $query = $zinco_actors_categories_storage->getQuery()
        ->condition('field_actor_category', $category_tid)
        ->accessCheck(FALSE);
      $category_entity_ids = $query->execute();
      $category_entities = $zinco_actors_categories_storage->loadMultiple($category_entity_ids);

      $allowed_bundle_ids = [];
      $bundle_icons = [];
      $bundle_descriptions = [];
      foreach ($category_entities as $category_entity) {
        $bundle_id = $category_entity->get('field_actor_bundle')->value;
        $allowed_bundle_ids[] = $bundle_id;
        // Store icon for this bundle
        if ($category_entity->hasField('icon') && !$category_entity->get('icon')->isEmpty()) {
          $bundle_icons[$bundle_id] = strip_tags($category_entity->get('icon')->value);
        }
        // Store description for this bundle
        if ($category_entity->hasField('description') && !$category_entity->get('description')->isEmpty()) {
          $bundle_descriptions[$bundle_id] = strip_tags($category_entity->get('description')->value);
        }
      }

      if (empty($allowed_bundle_ids)) {
        $this->messenger()->addWarning($this->t('No actor bundles found for the selected category.'));
        return $this->redirect('zinco_front.actor_categories_list');
      }

      $bundle_storage = $this->entityTypeManager->getStorage('zinco_actors_zincoactors_type');
      $bundles = $bundle_storage->loadMultiple($allowed_bundle_ids);

      foreach ($bundles as $bundle_id => $bundle_entity) {
        $bundle_data[] = [
          'id' => $bundle_id,
          'label' => $bundle_entity->label(),
          'icon' => $bundle_icons[$bundle_id] ?? '',
          'description' => $bundle_descriptions[$bundle_id] ?? '',
        ];
      }
      $cache_tags = $this->entityTypeManager->getDefinition('zinco_actors_categories')->getListCacheTags();
      $cache_tags = array_merge($cache_tags, $this->entityTypeManager->getDefinition('zinco_actors_zincoactors_type')->getListCacheTags());
      $cache_tags = array_merge($cache_tags, $term->getCacheTags());
    } else {
      // Original logic to load all bundles if no category is specified.
      $bundle_storage = $this->entityTypeManager->getStorage('zinco_actors_zincoactors_type');
      $bundles = $bundle_storage->loadMultiple();

      foreach ($bundles as $bundle_id => $bundle_entity) {
        $bundle_data[] = [
          'id' => $bundle_id,
          'label' => $bundle_entity->label(),
          'icon' => '',
        ];
      }
      $cache_tags = $this->entityTypeManager->getDefinition('zinco_actors_zincoactors_type')->getListCacheTags();
    }




    return [
      '#theme' => 'zinco_actor_bundles_list',
      '#bundles' => $bundle_data,
      '#category_name' => $nombre_categoria,
      '#cache' => [
        'tags' => $cache_tags,
        'contexts' => ['url.path'],
      ],
    ];
  }


  /**
   * Returns a list of actor categories.
   *
   * @return array
   *   A renderable array.
   */
  public function listarCategoriasActores()
  {
    $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
    $query = $term_storage->getQuery()
      ->condition('vid', 'categorias_de_actores')
      ->accessCheck(FALSE);
    $tids = $query->execute();
    $terms = $term_storage->loadMultiple($tids);

    $term_data = [];
    foreach ($terms as $term_id => $term_entity) {
      $logo_url = '';
      if ($term_entity->hasField('field_logotipo') && !$term_entity->get('field_logotipo')->isEmpty()) {
        $file = $term_entity->get('field_logotipo')->entity;
        if ($file) {
          $logo_url = $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri());
        }
      }
      $term_data[] = [
        'id' => $term_id,
        'label' => $term_entity->label(),
        'description' => $term_entity->hasField('description') && !$term_entity->get('description')->isEmpty() ? $term_entity->get('description')->value : '',
        'logotipo' => $logo_url,
      ];
    }

    return [
      '#theme' => 'zinco_categorias_actores_list',
      '#terms' => $term_data,
      '#cache' => [
        'tags' => $this->entityTypeManager->getDefinition('taxonomy_term')->getListCacheTags(),
      ],
      '#attached' => [
        'library' => [
          'zinco_front/zinco-categorias-actores-list',
        ],
      ],
    ];
  }

  /**
   * Returns a list of actor categories.
   *
   * @return array
   *   A renderable array.
   */
  public function listarCategoriasBusquedaActores()
  {
    $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
    $query = $term_storage->getQuery()
      ->condition('vid', 'categorias_de_actores')
      ->accessCheck(FALSE);
    $tids = $query->execute();
    $terms = $term_storage->loadMultiple($tids);

    $term_data = [];
    foreach ($terms as $term_id => $term_entity) {
      $logo_url = '';
      if ($term_entity->hasField('field_logotipo') && !$term_entity->get('field_logotipo')->isEmpty()) {
        $file = $term_entity->get('field_logotipo')->entity;
        if ($file) {
          $logo_url = $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri());
        }
      }
      $term_data[] = [
        'id' => $term_id,
        'label' => $term_entity->label(),
        'description' => $term_entity->hasField('description') && !$term_entity->get('description')->isEmpty() ? $term_entity->get('description')->value : '',
        'logotipo' => $logo_url,
      ];
    }

    return [
      '#theme' => 'zinco_categorias_busquedas_actores_list',
      '#terms' => $term_data,
      '#cache' => [
        'tags' => $this->entityTypeManager->getDefinition('taxonomy_term')->getListCacheTags(),
      ],
      '#attached' => [
        'library' => [
          'zinco_front/zinco-categorias-busquedas-actores-list',
        ],
      ],
    ];
  }

  /**
   * Generates an actor creation form for a specific bundle.
   *
   * @param string $bundle
   *   The machine name of the actor bundle.
   *
   * @return array
   *   A renderable array containing the actor creation form.
   */
  public function addActorFormByBundle(string $bundle)
  {
    $bundle_info = $this->entityTypeBundleInfo->getBundleInfo('zinco_actors_zincoactors');
    if (!isset($bundle_info[$bundle])) {
      $this->messenger()->addError($this->t('Invalid actor bundle: @bundle', ['@bundle' => $bundle]));
      return $this->redirect('zinco_front.actor_bundles_list');
    }

    $actor = $this->entityTypeManager->getStorage('zinco_actors_zincoactors')->create(['bundle' => $bundle]);
    $form = $this->entityFormBuilder->getForm($actor, 'frontend');

    return [
      '#theme' => 'zinco_actor_form_by_bundle',
      '#actor_form' => $form,
      '#bundle_label' => $bundle_info[$bundle]['label'],
      '#cache' => [
        'tags' => $this->entityTypeManager->getDefinition('zinco_actors_zincoactors')->getListCacheTags(),
      ],
    ];
  }

  /**
   * Returns the count of actors for AJAX request.
   */
  public function getActorsCount() {
    $query = $this->entityTypeManager->getStorage('zinco_actors_zincoactors')->getQuery()
      ->accessCheck(FALSE)
      ->count();
    $count = $query->execute();

    return new JsonResponse(['count' => $count]);
  }

  /**
   * Returns the count of projects for AJAX request.
   */
  public function getProjectsCount() {
    $query = $this->entityTypeManager->getStorage('zinco_proyectos_idi')->getQuery()
      ->accessCheck(FALSE)
      ->count();
    $count = $query->execute();

    return new JsonResponse(['count' => $count]);
  }

  /**
   * Generates an actor edit form for the current user's actor.
   *
   * @return array
   *   A renderable array containing the actor edit form.
   */
  public function editActorForm()
  {
    $current_user = $this->entityTypeManager->getStorage('user')->load(\Drupal::currentUser()->id());

    if (!$current_user->hasField('field_actor') || $current_user->get('field_actor')->isEmpty()) {
      $this->messenger()->addWarning($this->t('No tienes un perfil de actor asociado. Por favor, crea uno.'));
      return $this->redirect('zinco_front.actor_categories_list');
    }

    $actor_id = $current_user->get('field_actor')->target_id;
    $actor = $this->entityTypeManager->getStorage('zinco_actors_zincoactors')->load($actor_id);

    if (!$actor) {
      $this->messenger()->addError($this->t('No se pudo cargar tu perfil de actor.'));
      return $this->redirect('zinco_front.actor_categories_list');
    }

    $bundle_info = $this->entityTypeBundleInfo->getBundleInfo('zinco_actors_zincoactors');
    $bundle = $actor->bundle();
    
    $form = $this->entityFormBuilder->getForm($actor, 'frontend');

    return [
      '#theme' => 'zinco_actor_edit_form',
      '#actor_form' => $form,
      '#bundle_label' => $bundle_info[$bundle]['label'] ?? $bundle,
      '#cache' => [
        'tags' => $actor->getCacheTags(),
      ],
    ];
  }

}



