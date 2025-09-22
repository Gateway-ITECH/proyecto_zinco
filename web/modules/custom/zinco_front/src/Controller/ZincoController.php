<?php

namespace Drupal\zinco_front\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\zinco_front\Service\DumpDataService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Provides a ZincoController.
 */
class ZincoController extends ControllerBase {

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The DumpDataService.
   *
   * @var \Drupal\zinco_front\Service\DumpDataService
   */
  protected $dumpDataService;

  /**
   * Constructs a new ZincoController object.
   *
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   * @param \Drupal\zinco_front\Service\DumpDataService $dumpDataService
   *   The dump data service.
   */
  public function __construct(FormBuilderInterface $form_builder, DumpDataService $dumpDataService) {
    $this->formBuilder = $form_builder;
    $this->dumpDataService = $dumpDataService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_builder'),
      $container->get('zinco_front.dump_data_service')
    );
  }

  /**
   * Returns a 'Hello Zinco Front' page.
   *
   * @return array
   *   A simple renderable array.
   */
  public function home() {
    return [
      '#type' => 'markup',
      '#markup' => $this->t('Hello Zinco Front'),
    ];
   
  
  }

   /**
     * Dumps data from a specified table.
     *
     * @param string $tableName
     *   The name of the table to dump.
     *
     * @param string $formato
     *   (optional) The format to return the data in (e.g., 'json').
     *
     * @return array|\Symfony\Component\HttpFoundation\JsonResponse
     *   A renderable array containing the table data or a JsonResponse.
     */
    public function dumpData(string $tableName, ?string $formato) {
      $data = $this->dumpDataService->obtenerTabla($tableName);
  
      if ($formato === 'json') {
        return new JsonResponse($data);
      }

      if ($formato === 'count') {
        return count($data);
      }

      $headers = [];
      $rows = [];
  
      if (!empty($data)) {
        $headers = array_keys($data[0]);
        foreach ($data as $row) {
          $rows[] = array_values($row);
        }
      }
  
      return [
        '#theme' => 'dump_data_table',
        '#table_name' => $tableName,
        '#headers' => $headers,
        '#rows' => $rows,
      ];
    }

  /**
   * Returns a dashboard page.
   *
   * @return array
   *   A simple renderable array.
   */
  public function dashboard() {
    $form = $this->formBuilder->getForm('Drupal\zinco_front\Form\ZincoFilterForm');

    $default_data = [];

    $default_data['actores_grupos_de_investigacion'] = $this->dumpData('data_grupos_investigacion ', 'count');


    return [
      '#theme' => 'zinco_dashboard',
      '#test_var' => $this->t('Hello from controller'),
      '#filter_form' => $form,
      '#default_data' => $default_data,
    ];
  }

  /**
   * Returns an investigator profile page.
   *
   * @return array
   *   A simple renderable array.
   */
  public function perfil_investigador() {
    return [
      '#theme' => 'perfil_investigador',
    ];
  }

  /**
   * Returns an empresas profile page.
   *
   * @return array
   *   A simple renderable array.
   */
  public function perfil_empresas() {
    return [
      '#theme' => 'perfil_empresas',
    ];
  }

  /**
   * Returns an IES profile page.
   *
   * @return array
   *   A simple renderable array.
   */
  public function perfil_ies() {
    return [
      '#theme' => 'perfil_ies',
      '#title' => $this->t('Perfil de IES'),
    ];
   
  
  }

  /**
   * Returns an entidades gobierno profile page.
   *
   * @return array
   *   A simple renderable array.
   */
  public function perfil_entidades_gobierno() {
    return [
      '#theme' => 'perfil_entidades_gobierno',
    ];
  }
  
    /**
     * Returns a parque tecnologico profile page.
     *
     * @return array
     *   A simple renderable array.
     */
    public function perfil_parque_tecnologico() {
      return [
        '#theme' => 'perfil_parque_tecnologico',
      ];
    }

   /**
     * Returns a research group profile page.
     *
     * @return array
     *   A simple renderable array.
     */
    public function perfil_grupos_investigacion() {
      return [
        '#theme' => 'perfil_grupos_investigacion',
      ];
    }

  /**
   * Returns a financiador profile page.
   *
   * @return array
   *   A simple renderable array.
   */
  public function perfil_agente_financiador() {
    return [
      '#theme' => 'perfil_agente_financiador',
    ];
  }

}