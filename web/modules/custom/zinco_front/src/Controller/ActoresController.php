<?php

namespace Drupal\zinco_front\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a ZincoFront controller for Actores.
 */
class ActoresController extends ControllerBase {

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
   * Constructs a new ActoresController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, RequestStack $request_stack) {
    $this->entityTypeManager = $entity_type_manager;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('request_stack')
    );
  }

  /**
   * Returns a list of actors.
   *
   * @return array
   *   A renderable array.
   */
  public function listarActores() {
    $actors = [];
    $filters_param = $this->requestStack->getCurrentRequest()->query->get('filters');
    $search_term = $this->requestStack->getCurrentRequest()->query->get('search_term');
    try {
      $actor_storage = $this->entityTypeManager->getStorage('zinco_actors_zincoactors');
      $query = $actor_storage->getQuery();

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
        'empresa_explotadora_de_conocimie' => 'text-bg-primary',
        'empresa_generadora_de_conocimien' => 'text-bg-success',
        'entidad_gobierno' => 'text-bg-info',
        'instancias_de_orientacion_politi' => 'text-bg-warning',
        'institucion_de_educacion_superio' => 'text-bg-danger',
        'investigador' => 'text-bg-secondary',
        'otri' => 'text-bg-secondary',
        'parque_tecnologico' => 'text-bg-secondary',
        'default' => 'text-bg-secondary',
      ];

      // Convert loaded entities to renderable arrays and add ID.
      $actors = array_map(function ($actor) use ($bundle_colors) {
        $actor_data['id'] = $actor->id();
        $actor_data['label'] = $actor->label();

        $bundle_id = $actor->bundle();
        $bundle_entity = $this->entityTypeManager->getStorage('zinco_actors_zincoactors_type')->load($bundle_id);
        $actor_data['field_tipo_actor'] = $bundle_entity ? $bundle_entity->label() : $bundle_id;
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
        $actor_data['description'] = $actor->hasField('field_descripcion') && !$actor->get('field_descripcion')->isEmpty() ? $actor->get('field_descripcion')->value : '';

        // Add 'fomento' field (assuming field_fomento exists).
        $actor_data['field_fomento'] = $actor->hasField('field_fomento') && !$actor->get('field_fomento')->isEmpty() ? $actor->get('field_fomento')->value : '';

        // Add 'website' field (assuming field_website exists).
        $actor_data['field_website'] = $actor->hasField('field_website') && !$actor->get('field_website')->isEmpty() ? $actor->get('field_website')->uri : '';

        // Generate profile link.
        $actor_data['profile_link'] = '/zinco_actors/' . $actor->id();

        return $actor_data;
      }, $actors);
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t('Error loading actors: @message', ['@message' => $e->getMessage()]));
    }

    return [
      '#theme' => 'zinco_actores_list',
      '#actors' => $actors,
      '#cache' => [
        'tags' => $this->entityTypeManager->getDefinition('zinco_actors_zincoactors')->getListCacheTags(),
        'contexts' => ['url.query_args'],
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

}