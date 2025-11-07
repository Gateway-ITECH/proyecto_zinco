<?php

namespace Drupal\zinco_front\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a ZincoFront controller for Retos.
 */
class RetosController extends ControllerBase {

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
   * Constructs a new RetosController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
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
   * Returns a list of retos.
   *
   * @return array
   *   A renderable array.
   */
  public function listarRetos() {
    $retos = [];
    $filters_param = $this->requestStack->getCurrentRequest()->query->get('filters');
    $search_term = $this->requestStack->getCurrentRequest()->query->get('search_term');

    try {
      $reto_storage = $this->entityTypeManager->getStorage('zinco_retos_innovacion');
      $query = $reto_storage->getQuery();

      if (!empty($filters_param)) {
        $bundle_ids = explode(',', $filters_param);
        // Assuming 'area_enfoque' is the field to filter by.
        $query->condition('area_enfoque', $bundle_ids, 'IN');
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

        // Get the label of the 'estado_reto_innovacion' taxonomy term.
        if ($reto->hasField('estado_reto_innovacion') && !$reto->get('estado_reto_innovacion')->isEmpty()) {
          $estado_tid = $reto->get('estado_reto_innovacion')->target_id;
          $estado_term = $this->entityTypeManager->getStorage('taxonomy_term')->load($estado_tid);
          $reto_data['estado_reto_innovacion'] = $estado_term ? $estado_term->label() : '';
          //$reto_data['estado_color'] = $estado_colors[$estado_term->get('field_machine_name')->value] ?? 'text-bg-secondary';
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
          $organizador_id = $reto->get('organizador_reto')->target_id;
          $organizador_entity = $this->entityTypeManager->getStorage('zinco_actors_zincoactors')->load($organizador_id);
          $reto_data['organizador_reto'] = $organizador_entity ? $organizador_entity->label() : '';
        } else {
          $reto_data['organizador_reto'] = '';
        }

        $reto_data['visibilidad_reto'] = $reto->hasField('visibilidad_reto') && !$reto->get('visibilidad_reto')->isEmpty() ? ($reto->get('visibilidad_reto')->value ? 'Público' : 'Privado') : 'Privado';

        // Generate profile link.
        $reto_data['profile_link'] = '/zinco_retos_innovacion/' . $reto->id();

        return $reto_data;
      }, $retos);
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t('Error loading retos: @message', ['@message' => $e->getMessage()]));
    }

    return [
      '#theme' => 'zinco_retos_list',
      '#retos' => $retos,
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

}