<?php

namespace Drupal\zinco_front\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
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
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new ZincoController object.
   *
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   * @param \Drupal\zinco_front\Service\DumpDataService $dumpDataService
   *   The dump data service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(FormBuilderInterface $form_builder, DumpDataService $dumpDataService, ConfigFactoryInterface $config_factory) {
    $this->formBuilder = $form_builder;
    $this->dumpDataService = $dumpDataService;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_builder'),
      $container->get('zinco_front.dump_data_service'),
      $container->get('config.factory')
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
      $query_params = \Drupal::request()->query->all();
      $data = $this->dumpDataService->obtenerTabla($tableName, $query_params);
      
  
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
     * Dumps grouped data from a specified table.
     *
     * @param string $tableName
     *   The name of the table to dump.
     * @param string $groupColumn
     *   The column to group the data by.
     * @param string $formato
     *   (optional) The format to return the data in (e.g., 'json').
     *
     * @return array|\Symfony\Component\HttpFoundation\JsonResponse
     *   A renderable array containing the table data or a JsonResponse.
     */
    public function dumpGroupedData(string $tableName, string $groupColumn, ?string $formato) {
      $query_params = \Drupal::request()->query->all();
      $data = $this->dumpDataService->obtenerTablaAgrupada($tableName, $groupColumn, $query_params);
      //var_dump($data);

      if ($formato === 'json') {
        return new JsonResponse($data);
      }

      // For 'count' format, return the number of grouped results.
      if ($formato === 'count') {
        return count($data);
      }

      // Default to table rendering if no specific format is requested.
      $headers = [$this->t('Group'), $this->t('Count'), $this->t('Percentage')];
      $rows = [];

      if (!empty($data)) {
        foreach ($data as $row) {
          $rows[] = [
            $row['group_column'],
            $row['count'],
            $row['percentage'] . '%',
          ];
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
     * Dumps data from multiple specified tables.
     *
     * @param string $tableNames
     *   The names of the tables to dump, separated by '+'.
     * @param string $formato
     *   (optional) The format to return the data in (e.g., 'json').
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   A JsonResponse containing the table data.
     */
    public function dumpMultipleData(string $tableNames, ?string $formato) {
      $query_params = \Drupal::request()->query->all();
      $tables_array = explode('+', $tableNames);
      $data = $this->dumpDataService->obtenerTablas($tables_array, $query_params);

      if ($formato === 'json') {
        return new JsonResponse($data);
      }

      // If no specific format is requested or format is not 'json',
      // return an error or default response.
      return new JsonResponse(['error' => 'Invalid format or no format specified.'], 400);
    }

    /**
     * Dumps grouped data from multiple specified tables.
     *
     * @param string $tableNames
     *   The names of the tables to dump, separated by '+'.
     * @param string $groupColumn
     *   The column to group the data by.
     * @param string $formato
     *   (optional) The format to return the data in (e.g., 'json').
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   A JsonResponse containing the grouped table data.
     */
    public function dumpMultipleGroupedData(string $tableNames, string $groupColumn, ?string $formato) {
      $query_params = \Drupal::request()->query->all();
      $tables_array = explode('-', $tableNames);
      $data = $this->dumpDataService->obtenerTablasAgrupadas($tables_array, $groupColumn, $query_params);

      if ($formato === 'json') {
        return new JsonResponse($data);
      }

      // If no specific format is requested or format is not 'json',
      // return an error or default response.
      return new JsonResponse(['error' => 'Invalid format or no format specified.'], 400);
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

    //$default_data['actores_grupos_de_investigacion'] = $this->dumpData('data_grupos_investigacion ', 'count');


    return [
      '#theme' => 'zinco_dashboard',
      '#test_var' => $this->t('Hello from controller'),
      '#filter_form' => $form,
      '#default_data' => $default_data,
      '#attached' => [
        'library' => [
          'zinco_front/zinco-dashboard-front',
        ],
      ],
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



  /**
   * Deletes a table entry from the Zinco Dashboard configuration.
   *
   * @param int $id
   *   The ID of the table entry to delete.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response to the configuration form.
   */
  public function deleteDashboardConfigTable(int $id) {
    $config = $this->configFactory->getEditable('zinco_front.dashboard.settings');
    $tables_data = $config->get('tables_data') ?: [];

    if (isset($tables_data[$id])) {
      unset($tables_data[$id]);
      // Re-index the array to ensure sequential keys.
      $tables_data = array_values($tables_data);
      $config->set('tables_data', $tables_data)->save();
      $this->messenger()->addStatus($this->t('Table entry has been deleted.'));
    }
    else {
      $this->messenger()->addError($this->t('Table entry not found.'));
    }

    return $this->redirect('zinco_front.dashboard_config');
  }

   /**
   * Deletes a filter entry from the Zinco Dashboard configuration.
   *
   * @param int $id
   *   The ID of the filter entry to delete.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response to the configuration form.
   */
  public function deleteDashboardConfigFilter(int $id) {
    $config = $this->configFactory->getEditable('zinco_front.dashboard.settings');
    $filters_data = $config->get('filters_data') ?: [];

    if (isset($filters_data[$id])) {
      unset($filters_data[$id]);
      // Re-index the array to ensure sequential keys.
      $filters_data = array_values($filters_data);
      $config->set('filters_data', $filters_data)->save();
      $this->messenger()->addStatus($this->t('Filter entry has been deleted.'));
    }
    else {
      $this->messenger()->addError($this->t('Filter entry not found.'));
    }

    return $this->redirect('zinco_front.dashboard_config');
  }

}

 