<?php

namespace Drupal\zinco_front\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\zinco_front\Service\DumpDataService;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\zinco_etl\Service\PdfGeneratorService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\views\Views;
use Drupal\Core\Url;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RedirectCommand;

/**
 * Provides a ZincoController.
 */
class ZincoController extends ControllerBase
{

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
   * The PDF generator service.
   *
   * @var \Drupal\zinco_etl\Service\PdfGeneratorService
   */
  protected $pdfGeneratorService;

  /**
   * The file URL generator service.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Constructs a new ZincoController object.
   *
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   * @param \Drupal\zinco_front\Service\DumpDataService $dumpDataService
   *   The dump data service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\zinco_etl\PdfGeneratorService $pdf_generator_service
   *   The PDF generator service.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   */
  public function __construct(FormBuilderInterface $form_builder, DumpDataService $dumpDataService, ConfigFactoryInterface $config_factory, PdfGeneratorService $pdf_generator_service, FileSystemInterface $file_system, FileUrlGeneratorInterface $file_url_generator)
  {
    $this->formBuilder = $form_builder;
    $this->dumpDataService = $dumpDataService;
    $this->configFactory = $config_factory;
    $this->pdfGeneratorService = $pdf_generator_service;
    $this->fileSystem = $file_system;
    $this->fileUrlGenerator = $file_url_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('form_builder'),
      $container->get('zinco_front.dump_data_service'),
      $container->get('config.factory'),
      $container->get('zinco_etl.pdf_generator'),
      $container->get('file_system'),
      $container->get('file_url_generator')
    );
  }

  /**
   * Returns a 'Hello Zinco Front' page.
   *
   * @return array
   *   A simple renderable array.
   */
  public function home()
  {
    return [
      '#type' => 'markup',
      '#markup' => $this->t('Hello Zinco Front'),
    ];


  }

  //

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
  public function dumpData(string $tableName, ?string $formato)
  {
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
   * Dumps the sum of a cumulative field from a specified table.
   *
   * @param string $tableName
   *   The name of the table to query.
   * @param string $cumulativeFieldName
   *   The name of the field to sum.
   * @param string $formato
   *   (optional) The format to return the data in (e.g., 'json').
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JsonResponse containing the sum of the cumulative field.
   */
  public function dumpTablaCampoAcumulativo(string $tableName, string $cumulativeFieldName, ?string $formato)
  {
    $query_params = \Drupal::request()->query->all();
    $sum = $this->dumpDataService->obtenerTablaCampoAcumulativo($tableName, $cumulativeFieldName, $query_params);

    if ($formato === 'json') {
      return new JsonResponse(['sum' => $sum]);
    }

    return new JsonResponse(['error' => 'Invalid format or no format specified.'], 400);
  }


  /**
   * Dumps data from a specified entity.
   *
   * @param string $entity_type_id
   *   The entity type ID to dump.
   * @param string $formato
   *   (optional) The format to return the data in (e.g., 'json').
   *
   * @return array|\Symfony\Component\HttpFoundation\JsonResponse
   *   A renderable array containing the entity data or a JsonResponse.
   */
  public function dumpEntity(string $entity_type_id, ?string $formato)
  {
    $query_params = \Drupal::request()->query->all();
    $data = $this->dumpDataService->obtenerEntidad($entity_type_id, $query_params);

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
      '#table_name' => $entity_type_id,
      '#headers' => $headers,
      '#rows' => $rows,
    ];

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
      '#table_name' => $entity_type_id,
      '#headers' => $headers,
      '#rows' => $rows,
    ];
  }

  /**
   * Dumps data from a specified entity bundle.
   *
   * @param string $entity_type_id
   *   The entity type ID to dump.
   * @param string $bundle
   *   The bundle ID to filter by.
   * @param string $formato
   *   (optional) The format to return the data in (e.g., 'json').
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JsonResponse containing the entity bundle data.
   */
  public function dumpEntityBundle(string $entity_type_id, string $bundle, ?string $formato)
  {
    //obtener query params
    $query_params = \Drupal::request()->query->all();

    $data = $this->dumpDataService->obtenerBundle($entity_type_id, $bundle, $query_params);

    if ($formato === 'json') {
      return new JsonResponse($data);
    }

    return new JsonResponse(['error' => 'Invalid format or no format specified.'], 400);
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
  public function dumpGroupedData(string $tableName, string $groupColumn, ?string $formato)
  {
    $query_params = \Drupal::request()->query->all();
    $data = $this->dumpDataService->obtenerTablaAgrupada($tableName, $groupColumn, $query_params);
    //var_dump($data);

    if ($formato === 'json') {
      return new JsonResponse($data);
    }

    // For 'count' format, return the number of grouped results in JSON.
    if ($formato === 'count') {
      return new JsonResponse(['count' => count($data)]);
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
  public function dumpMultipleData(string $tableNames, ?string $formato)
  {
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
  public function dumpMultipleGroupedData(string $tableNames, string $groupColumn, ?string $formato)
  {
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
  public function dashboard()
  {
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
  public function perfil_investigador()
  {
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
  public function perfil_empresas()
  {
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
  public function perfil_ies()
  {
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
  public function perfil_entidades_gobierno()
  {
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
  public function perfil_parque_tecnologico()
  {
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
  public function perfil_grupos_investigacion()
  {
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
  public function perfil_agente_financiador()
  {
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
  public function deleteDashboardConfigTable(int $id)
  {
    $config = $this->configFactory->getEditable('zinco_front.dashboard.settings');
    $tables_data = $config->get('tables_data') ?: [];

    if (isset($tables_data[$id])) {
      unset($tables_data[$id]);
      // Re-index the array to ensure sequential keys.
      $tables_data = array_values($tables_data);
      $config->set('tables_data', $tables_data)->save();
      $this->messenger()->addStatus($this->t('Table entry has been deleted.'));
    } else {
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
  public function deleteDashboardConfigFilter(int $id)
  {
    $config = $this->configFactory->getEditable('zinco_front.dashboard.settings');
    $filters_data = $config->get('filters_data') ?: [];

    if (isset($filters_data[$id])) {
      unset($filters_data[$id]);
      // Re-index the array to ensure sequential keys.
      $filters_data = array_values($filters_data);
      $config->set('filters_data', $filters_data)->save();
      $this->messenger()->addStatus($this->t('Filter entry has been deleted.'));
    } else {
      $this->messenger()->addError($this->t('Filter entry not found.'));
    }

    return $this->redirect('zinco_front.dashboard_config');
  }

  /**
   * Generates a PDF from JSON data and returns its URL.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing the URL of the generated PDF or an error message.
   */
  public function generatePdfEndpoint(Request $request)
  {
    $content = $request->getContent();
    $data = json_decode($content, TRUE);

    if (json_last_error() !== JSON_ERROR_NONE) {
      return new JsonResponse(['error' => 'Invalid JSON data: ' . json_last_error_msg()], 400);
    }

    // Convert the JSON data back to a string for the service.
    $jsonDataString = json_encode($data);

    $filename = 'zinco_report_' . time() . '.pdf';
    $pdf_content = $this->pdfGeneratorService->generatePdfFromJson($jsonDataString, $filename);

    if ($pdf_content === FALSE) {
      return new JsonResponse(['error' => 'Failed to generate PDF.'], 500);
    }

    // Define the public directory for storing PDFs.
    $directory = 'public://zinco_pdfs';
    $this->fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY);

    $file_uri = $directory . '/' . $filename;
    $file_path = $this->fileSystem->realpath($file_uri);

    if ($this->fileSystem->saveData($pdf_content, $file_uri, FileSystemInterface::EXISTS_REPLACE)) {
      $file_url = $this->fileUrlGenerator->generateAbsoluteString($file_uri);
      return new JsonResponse(['url' => $file_url]);
    } else {
      return new JsonResponse(['error' => 'Failed to save PDF file.'], 500);
    }
  }

  /**
   * Generates a PDF from a specified database table.
   *
   * @param string $tableName
   *   The name of the database table to generate the PDF from.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   A Symfony response object containing the PDF.
   */
  public function generateTablePdf(string $tableName)
  {
    $pdf_content = $this->pdfGeneratorService->generatePdfFromDatabaseTable($tableName, 'Reporte de ' . $tableName, $tableName . '.pdf');

    if ($pdf_content) {
      $response = new Response($pdf_content);
      $response->headers->set('Content-Type', 'application/pdf');
      $response->headers->set('Content-Disposition', 'attachment;filename="' . $tableName . '.pdf"');
      return $response;
    } else {
      $this->messenger()->addError($this->t('Failed to generate PDF for table @table.', ['@table' => $tableName]));
      return $this->redirect('<front>');
    }
  }

  /**
   * Returns a landing page.
   *
   * @return array
   *   A renderable array.
   */
  public function landingPage()
  {
    //obtener entidad tipo nodo con id 3
    $entity = \Drupal::entityTypeManager()->getStorage('node')->load(3);

    // Obtener configuración de tooltips.
    $config = \Drupal::config('zinco_front.dashboard.settings');
    $show_tooltips = $config->get('show_tooltips') ?: FALSE;
    //obtener campo field_actores_registrados
    $field_actores_registrados = $entity->get('field_actores_registrados')->value;
    //obtener campo field_actores_registrados_icon
    $field_actores_registrados_icon = $entity->get('field_actores_registrados_icon')->value;
    //obtener campo field_actores_registrados_label
    $field_actores_registrados_label = $entity->get('field_actores_registrados_label')->value;
    //obtener campo field_beneficios_centro_descripc
    $field_beneficios_centro_descripcion = $entity->get('field_beneficios_centro_descripc')->value;
    //obtener campo field_beneficios_centro_icon
    $field_beneficios_centro_icon = $entity->get('field_beneficios_centro_icon')->value;
    //obtener campo field_beneficios_centro_titulo
    $field_beneficios_centro_titulo = $entity->get('field_beneficios_centro_titulo')->value;
    //obtener campo field_beneficios_derecha_descrip
    $field_beneficios_derecha_descripcion = $entity->get('field_beneficios_derecha_descrip')->value;
    //obtener campo field_beneficios_derecha_icon
    $field_beneficios_derecha_icon = $entity->get('field_beneficios_derecha_icon')->value;
    //obtener campo field_beneficios_derecha_titulo
    $field_beneficios_derecha_titulo = $entity->get('field_beneficios_derecha_titulo')->value;
    //obtener campo field_beneficios_izq_descripcion
    $field_beneficios_izq_descripcion = $entity->get('field_beneficios_izq_descripcion')->value;
    //obtener campo field_beneficios_izq_icono
    $field_beneficios_izq_icono = $entity->get('field_beneficios_izq_icono')->value;
    //obtener campo field_beneficios_izq_titulo
    $field_beneficios_izq_titulo = $entity->get('field_beneficios_izq_titulo')->value;
    //obtener campo field_beneficios_title
    $field_beneficios_title = $entity->get('field_beneficios_title')->value;
    //obtener campo field_beneficios_descripcion
    $field_beneficios_descripcion = $entity->get('field_beneficios_descripcion')->value;
    //obtener campo field_colaboraciones_facilitadas
    $field_colaboraciones_facilitadas = $entity->get('field_colaboraciones_facilitadas')->value;
    //obtener campo field_colaboraciones_icon
    $field_colaboraciones_icon = $entity->get('field_colaboraciones_icon')->value;
    //obtener campo field_colaboraciones_title
    $field_colaboraciones_title = $entity->get('field_colaboraciones_title')->value;
    //obtener campo field_cta_descripcion
    $field_cta_descripcion = $entity->get('field_cta_descripcion')->value;
    //obtener campo field_cta_enlace_button
    $field_cta_enlace_button = $entity->get('field_cta_enlace_button')->value;
    //obtener campo field_cta_titulo
    $field_cta_titulo = $entity->get('field_cta_titulo')->value;
    //obtener campo field_cta_label_button
    $field_cta_label_button = $entity->get('field_cta_label_button')->value;
    //obtener campo field_hero_section_button_label
    $field_hero_section_button_label = $entity->get('field_hero_section_button_label')->value;
    //obtener campo field_hero_section_description
    $field_hero_section_description = $entity->get('field_hero_section_description')->value;
    //obtener campo field_hero_section_title  
    $field_hero_section_title = $entity->get('field_hero_section_title')->value;
    //obtener campo field_hero_section_link
    $field_hero_section_link = $entity->get('field_hero_section_link')->value;
    //obtener campo field_herramientas_centro_descri
    $field_herramientas_centro_descripcion = $entity->get('field_herramientas_centro_descri')->value;
    //obtener campo field_herramientas_centro_icon
    $field_herramientas_centro_icon = $entity->get('field_herramientas_centro_icon')->value;
    //obtener campo field_herramientas_centro_titulo
    $field_herramientas_centro_titulo = $entity->get('field_herramientas_centro_titulo')->value;
    //obtener campo field_herramientas_derecha_descr
    $field_herramientas_derecha_descripcion = $entity->get('field_herramientas_derecha_descr')->value;
    //obtener campo field_herramientas_derecha_icon
    $field_herramientas_derecha_icon = $entity->get('field_herramientas_derecha_icon')->value;
    //obtener campo field_herramientas_derecha_titul
    $field_herramientas_derecha_titulo = $entity->get('field_herramientas_derecha_titul')->value;
    //obtener campo field_herramientas_izq_descripci
    $field_herramientas_izq_descripcion = $entity->get('field_herramientas_izq_descripci')->value;
    //obtener campo field_herramientas_izq_icon
    $field_herramientas_izq_icon = $entity->get('field_herramientas_izq_icon')->value;
    //obtener campo field_herramientas_izq_titulo
    $field_herramientas_izq_titulo = $entity->get('field_herramientas_izq_titulo')->value;
    //obtener campo field_herramientas_titulo
    $field_herramientas_titulo = $entity->get('field_herramientas_titulo')->value;
    //obtener campo field_herramientas_descripcion
    $field_herramientas_descripcion = $entity->get('field_herramientas_descripcion')->value;
    //obtener campo field_proyectos_activos
    $field_proyectos_activos = $entity->get('field_proyectos_activos')->value;
    //obtener campo field_proyectos_icon
    $field_proyectos_activos_icon = $entity->get('field_proyectos_activos_icon')->value;
    //obtener campo field_proyectos_activos_label
    $field_proyectos_activos_label = $entity->get('field_proyectos_activos_label')->value;
    //obtener campo field_data_clave_titulo
    $field_data_clave_titulo = $entity->get('field_data_clave_titulo')->value;
    //obtener campo field_data_clave_descripcion
    $field_data_clave_descripcion = $entity->get('field_data_clave_descripcion')->value;
    //obtener campo field_logos_cooperantes el cual es de tipo imagen y obtener la url de la imagen
    $field_logos_cooperantes = $entity->get('field_logos_cooperantes')->entity->getFileUri();
    //validar si existe alguna imagen
    $field_logos_cooperantes_url = '';
    if ($field_logos_cooperantes) {
      $field_logos_cooperantes_url = \Drupal::service('file_url_generator')->generateAbsoluteString($field_logos_cooperantes);
    }



    // Obtener las definiciones de campos para extraer los labels.
    $field_definitions = \Drupal::service('entity_field.manager')->getFieldDefinitions('node', $entity->bundle());
    $labels = [];
    foreach ($field_definitions as $field_name => $definition) {
      $labels[$field_name] = $definition->getLabel();
    }

    //agrupar los campos en un array para pasarlos a la plantilla 
    $fields = [
      'labels' => $labels,
      'field_actores_registrados' => $field_actores_registrados,
      'field_actores_registrados_icon' => $field_actores_registrados_icon,
      'field_actores_registrados_label' => $field_actores_registrados_label,
      'field_beneficios_centro_descripcion' => $field_beneficios_centro_descripcion,
      'field_beneficios_centro_icon' => $field_beneficios_centro_icon,
      'field_beneficios_centro_titulo' => $field_beneficios_centro_titulo,
      'field_beneficios_derecha_descripcion' => $field_beneficios_derecha_descripcion,
      'field_beneficios_derecha_icon' => $field_beneficios_derecha_icon,
      'field_beneficios_derecha_titulo' => $field_beneficios_derecha_titulo,
      'field_beneficios_izq_descripcion' => $field_beneficios_izq_descripcion,
      'field_beneficios_izq_icono' => $field_beneficios_izq_icono,
      'field_beneficios_izq_titulo' => $field_beneficios_izq_titulo,
      'field_beneficios_title' => $field_beneficios_title,
      'field_beneficios_descripcion' => $field_beneficios_descripcion,
      'field_colaboraciones_facilitadas' => $field_colaboraciones_facilitadas,
      'field_colaboraciones_icon' => $field_colaboraciones_icon,
      'field_colaboraciones_title' => $field_colaboraciones_title,
      'field_cta_descripcion' => $field_cta_descripcion,
      'field_cta_enlace_button' => $field_cta_enlace_button,
      'field_cta_titulo' => $field_cta_titulo,
      'field_cta_label_button' => $field_cta_label_button,
      'field_hero_section_button_label' => $field_hero_section_button_label,
      'field_hero_section_description' => $field_hero_section_description,
      'field_hero_section_title' => $field_hero_section_title,
      'field_hero_section_link' => $field_hero_section_link,
      'field_herramientas_centro_descripcion' => $field_herramientas_centro_descripcion,
      'field_herramientas_centro_icon' => $field_herramientas_centro_icon,
      'field_herramientas_centro_titulo' => $field_herramientas_centro_titulo,
      'field_herramientas_derecha_descripcion' => $field_herramientas_derecha_descripcion,
      'field_herramientas_derecha_icon' => $field_herramientas_derecha_icon,
      'field_herramientas_derecha_titulo' => $field_herramientas_derecha_titulo,
      'field_herramientas_izq_descripcion' => $field_herramientas_izq_descripcion,
      'field_herramientas_izq_icon' => $field_herramientas_izq_icon,
      'field_herramientas_izq_titulo' => $field_herramientas_izq_titulo,
      'field_herramientas_titulo' => $field_herramientas_titulo,
      'field_herramientas_descripcion' => $field_herramientas_descripcion,
      'field_proyectos_activos' => $field_proyectos_activos,
      'field_proyectos_activos_icon' => $field_proyectos_activos_icon,
      'field_proyectos_activos_label' => $field_proyectos_activos_label,
      'field_data_clave_titulo' => $field_data_clave_titulo,
      'field_data_clave_descripcion' => $field_data_clave_descripcion,
      'field_logos_cooperantes_url' => $field_logos_cooperantes_url,
      'show_tooltips' => $show_tooltips,
    ];


    return [
      '#theme' => 'zinco_landing_page',
      '#fields' => $fields,
      '#show_tooltips' => $show_tooltips,
      '#cache' => [
        'tags' => ['node_list', 'config:zinco_front.dashboard.settings'],
      ],
      '#attached' => [
        'library' => [
          'zinco_front/zinco-landing-page',
        ],
      ],
    ];
  }

  /**
   * Returns the oportunidades page.
   *
   * @return array
   *   A renderable array.
   */
  public function oportunidades()
  {
    $noticias = $this->getLatestNodes('noticia', 3);
    $eventos = $this->getLatestNodes('evento', 3);
    $convocatorias = $this->getLatestNodes('convocatoria', 3);
    $cursos = $this->getLatestNodes('cursos', 3);

    return [
      '#theme' => 'zinco_oportunidades',
      '#noticias' => $noticias,
      '#eventos' => $eventos,
      '#convocatorias' => $convocatorias,
      '#cursos' => $cursos,
      '#attached' => [
        'library' => [
          'zinco_front/zinco-landing-page',
          'zinco_front/zinco-news-carousel',
          'zinco_front/zinco-upcoming-events',
          'zinco_front/zinco-active-calls',
          'zinco_front/zinco-oportunidades',
        ],
      ],
      '#cache' => [
        'tags' => ['node_list', 'config:zinco_front.dashboard.settings'],
      ],
    ];
  }

  /**
   * Helper function to get latest nodes of a type.
   */
  private function getLatestNodes($type, $limit = 3)
  {
    $nodes_data = [];
    try {
      $storage = \Drupal::entityTypeManager()->getStorage('node');
      $query = $storage->getQuery()
        ->condition('type', $type)
        ->condition('status', 1)
        ->sort('created', 'DESC')
        ->range(0, $limit)
        ->accessCheck(TRUE);

      $nids = $query->execute();
      if (!empty($nids)) {
        $nodes = $storage->loadMultiple($nids);
        foreach ($nodes as $node) {
          $nodes_data[] = $this->formatNodeForCard($node);
        }
      }
    } catch (\Exception $e) {
      \Drupal::logger('zinco_front')->error('Error loading @type nodes: @message', ['@type' => $type, '@message' => $e->getMessage()]);
    }
    return $nodes_data;
  }

  /**
   * Helper to format node data for cards.
   */
  private function formatNodeForCard($node)
  {
    $type = $node->bundle();
    $data = [
      'id' => $node->id(),
      'title' => $node->getTitle(),
      'url' => $node->toUrl()->toString(),
      'type' => $type,
    ];

    switch ($type) {
      case 'noticia':
        $data['date'] = \Drupal::service('date.formatter')->format($node->getCreatedTime(), 'custom', 'd/m/Y');
        if ($node->hasField('field_imagen_destacada') && !$node->get('field_imagen_destacada')->isEmpty()) {
          $file = $node->get('field_imagen_destacada')->entity;
          if ($file) {
            $data['image_url'] = $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri());
          }
        }
        if ($node->hasField('field_contenido_noticia') && !$node->get('field_contenido_noticia')->isEmpty()) {
          $plain_text = strip_tags($node->get('field_contenido_noticia')->value);
          $data['excerpt'] = mb_strlen($plain_text) > 150 ? mb_substr($plain_text, 0, 150) . '...' : $plain_text;
        }
        break;

      case 'evento':
        if ($node->hasField('field_fecha_del_evento') && !$node->get('field_fecha_del_evento')->isEmpty()) {
          $timestamp = strtotime($node->get('field_fecha_del_evento')->value);
          $data['date_full'] = \Drupal::service('date.formatter')->format($timestamp, 'custom', 'd/m/Y');
          $data['date_day'] = \Drupal::service('date.formatter')->format($timestamp, 'custom', 'd');
          $data['date_month'] = \Drupal::service('date.formatter')->format($timestamp, 'custom', 'M');
          $data['date_year'] = \Drupal::service('date.formatter')->format($timestamp, 'custom', 'Y');
        }
        if ($node->hasField('field_lugar_del_evento') && !$node->get('field_lugar_del_evento')->isEmpty()) {
          $data['location'] = $node->get('field_lugar_del_evento')->value;
        }
        if ($node->hasField('field_agenda_evento') && !$node->get('field_agenda_evento')->isEmpty()) {
          $plain_text = strip_tags($node->get('field_agenda_evento')->value);
          $data['description'] = mb_strlen($plain_text) > 120 ? mb_substr($plain_text, 0, 120) . '...' : $plain_text;
        }
        break;

      case 'convocatoria':
        if ($node->hasField('field_fecha_de_apertura') && !$node->get('field_fecha_de_apertura')->isEmpty()) {
          $data['opening_date'] = \Drupal::service('date.formatter')->format(strtotime($node->get('field_fecha_de_apertura')->value), 'custom', 'd/m/Y');
        }
        if ($node->hasField('field_fecha_de_cierre') && !$node->get('field_fecha_de_cierre')->isEmpty()) {
          $closing_timestamp = strtotime($node->get('field_fecha_de_cierre')->value);
          $data['closing_date'] = \Drupal::service('date.formatter')->format($closing_timestamp, 'custom', 'd/m/Y');
          $data['closing_day'] = \Drupal::service('date.formatter')->format($closing_timestamp, 'custom', 'd');
          $data['closing_month'] = \Drupal::service('date.formatter')->format($closing_timestamp, 'custom', 'M');

          $days_remaining = floor(($closing_timestamp - \Drupal::time()->getRequestTime()) / 86400);
          $data['days_remaining'] = $days_remaining;
          $data['urgency_label'] = $days_remaining <= 0 ? 'Cierra hoy' : ($days_remaining == 1 ? 'Cierra mañana' : "Quedan $days_remaining días");
          $data['urgency_class'] = $days_remaining <= 1 ? 'urgent' : ($days_remaining <= 7 ? 'warning' : 'normal');
        }
        if ($node->hasField('field_tipo_de_convocatoria') && !$node->get('field_tipo_de_convocatoria')->isEmpty()) {
          $term = $node->get('field_tipo_de_convocatoria')->entity;
          if ($term) {
            $data['category'] = $term->label();
          }
        }
        if ($node->hasField('body') && !$node->get('body')->isEmpty()) {
          $plain_text = strip_tags($node->get('body')->value);
          $data['description'] = mb_strlen($plain_text) > 150 ? mb_substr($plain_text, 0, 150) . '...' : $plain_text;
        }
        break;

      case 'cursos':
        $data['url'] = Url::fromRoute('zinco_front.curso_detail', ['curso_id' => $node->id()])->toString();
        if ($node->hasField('field_flyer_publicitario') && !$node->get('field_flyer_publicitario')->isEmpty()) {
          $file = $node->get('field_flyer_publicitario')->entity;
          if ($file) {
            $data['field_flyer_publicitario'] = $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri());
          }
        }
        if ($node->hasField('body') && !$node->get('body')->isEmpty()) {
          $plain_text = strip_tags($node->get('body')->value);
          $data['description'] = mb_strlen($plain_text) > 150 ? mb_substr($plain_text, 0, 150) . '...' : $plain_text;
        }
        break;
    }

    return $data;
  }

  /**
   * AJAX callback to send Call for Papers.
   */
  public function sendCallForPapers(Request $request)
  {
    $logger = \Drupal::logger('zinco_debug');
    $logger->info('ZincoController: sendCallForPapers iniciado (AJAX).');

    $message = $request->request->get('message');
    if (empty($message)) {
      $logger->warning('ZincoController: Intento de envío sin mensaje.');
      return new JsonResponse(['success' => false, 'message' => 'El mensaje es requerido.'], 400);
    }

    // Get all user input to pass to the view if needed.
    $input = $request->request->all();

    // Try to get UIDs from the request first (passed from JS).
    $uids = $request->request->all('uids');
    $logger->info('ZincoController: UIDs recibidos desde el frontend: @count', ['@count' => count($uids)]);

    if (empty($uids)) {
      $logger->info('ZincoController: No se recibieron UIDs del frontend, ejecutando vista de respaldo.');
      $view_id = 'selector_de_receptores';
      $display_id = 'embed_receptors_selector';
      $view = Views::getView($view_id);
      $uids = [];

      if ($view) {
        $view->setDisplay($display_id);
        if (!empty($input)) {
          $view->setExposedInput($input);
        }
        $view->execute();
        $logger->info('ZincoController: Vista ejecutada. Resultados: @count', ['@count' => count($view->result)]);

        foreach ($view->result as $row) {
          if (isset($row->uid)) {
            $uids[] = $row->uid;
          } elseif (isset($row->_entity) && $row->_entity->getEntityTypeId() === 'user') {
            $uids[] = $row->_entity->id();
          }
        }
      } else {
        $logger->error('ZincoController: No se pudo cargar la vista @view.', ['@view' => $view_id]);
      }
    }

    $logger->info('ZincoController: Total de UIDs a procesar: @count', ['@count' => count($uids)]);

    if (empty($uids)) {
      return new JsonResponse(['success' => false, 'message' => 'No se encontraron usuarios para enviar el mensaje con los filtros aplicados.'], 400);
    }

    $batch = [
      'title' => $this->t('Enviando Call for Papers...'),
      'operations' => [],
      'init_message' => $this->t('Iniciando proceso de envío masivo.'),
      'progress_message' => $this->t('Enviando notificación @current de @total.'),
      'error_message' => $this->t('Ocurrió un error durante el proceso.'),
      'finished' => ['\Drupal\zinco_front\Form\CallForPapersForm', 'batchFinished'],
    ];

    // Chunk uids to process in batches of 20.
    $chunks = array_chunk($uids, 20);
    $logger->info('ZincoController: Inicializando batch con @count operaciones.', ['@count' => count($chunks)]);

    $sender_uid = $this->currentUser()->id();
    foreach ($chunks as $chunk) {
      $batch['operations'][] = [
        ['\Drupal\zinco_front\Form\CallForPapersForm', 'processBatchNotifications'],
        [$chunk, $message, $sender_uid],
      ];
    }

    batch_set($batch);

    // batch_process() prepares the batch and returns a redirect response.
    $redirect = batch_process(Url::fromRoute('zinco_front.call_for_papers')->toString());

    if ($redirect instanceof \Symfony\Component\HttpFoundation\RedirectResponse) {
      return new JsonResponse([
        'success' => true,
        'redirect' => $redirect->getTargetUrl(),
      ]);
    }

    return new JsonResponse(['success' => true]);
  }

  /**
   * AJAX callback to get a suggested message based on a challenge.
   */
  public function getRetoSuggestion($reto_id)
  {
    if (!$reto_id) {
      return new JsonResponse(['success' => false, 'message' => 'ID de reto no proporcionado.'], 400);
    }

    try {
      $reto = \Drupal::entityTypeManager()->getStorage('zinco_retos_innovacion')->load($reto_id);
      if (!$reto) {
        return new JsonResponse(['success' => false, 'message' => 'Reto no encontrado.'], 404);
      }

      $title = $reto->label();
      $description = '';
      if ($reto->hasField('description') && !$reto->get('description')->isEmpty()) {
        $description = strip_tags($reto->get('description')->value ?? '');
      }

      $municipio = '';
      if ($reto->hasField('field_municipio') && !$reto->get('field_municipio')->isEmpty()) {
        $municipio_entity = $reto->get('field_municipio')->entity;
        if ($municipio_entity) {
          $municipio = $municipio_entity->label();
        }
      }

      $organizadores = [];
      if ($reto->hasField('organizador_reto') && !$reto->get('organizador_reto')->isEmpty()) {
        foreach ($reto->get('organizador_reto')->referencedEntities() as $org_entity) {
          $organizadores[] = $org_entity->label();
        }
      }
      $organizador_str = implode(', ', $organizadores);

      $recompensas = '';
      if ($reto->hasField('recompensas_reto') && !$reto->get('recompensas_reto')->isEmpty()) {
        $recompensas = strip_tags($reto->get('recompensas_reto')->value ?? '');
      }

      $fecha_inicio = $reto->hasField('fecha_inicio') ? $reto->get('fecha_inicio')->value : '';
      $fecha_fin = $reto->hasField('fecha_fin') ? $reto->get('fecha_fin')->value : '';
      $fecha_eval = $reto->hasField('fecha_evaluacion') ? $reto->get('fecha_evaluacion')->value : '';

      $message = "¡Nuevo Reto de Innovación: $title!\n\n";
      if ($description) {
        $message .= "Descripción: $description\n\n";
      }
      if ($organizador_str) {
        $message .= "Organizado por: $organizador_str\n";
      }
      if ($municipio) {
        $message .= "Ubicación: $municipio\n";
      }
      if ($recompensas) {
        $message .= "Recompensas: $recompensas\n";
      }

      if ($fecha_inicio || $fecha_fin || $fecha_eval) {
        $message .= "\nFechas clave:\n";
        if ($fecha_inicio)
          $message .= "- Inicio: $fecha_inicio\n";
        if ($fecha_fin)
          $message .= "- Fin: $fecha_fin\n";
        if ($fecha_eval)
          $message .= "- Evaluación: $fecha_eval\n";
      }

      $reto_url = $reto->toUrl('canonical', ['absolute' => TRUE])->toString();
      $message .= "\nPuedes ver más detalles del reto en: $reto_url\n";
      $message .= "\nTe invitamos a participar y proponer tu solución.";

      return new JsonResponse([
        'success' => true,
        'suggestion' => $message,
      ]);
    } catch (\Exception $e) {
      return new JsonResponse([
        'success' => false,
        'message' => $e->getMessage(),
      ], 500);
    }
  }

  /**
   * Returns a course detail page.
   *
   * @param int $curso_id
   *   The ID of the course node.
   *
   * @return array
   *   A renderable array.
   */
  public function verCurso($curso_id)
  {
    $node = \Drupal::entityTypeManager()->getStorage('node')->load($curso_id);
    if (!$node || $node->bundle() !== 'cursos') {
      throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
    }

    $data = $this->formatNodeForCard($node);
    if ($node->hasField('body') && !$node->get('body')->isEmpty()) {
      $data['description'] = $node->get('body')->value;
    }

    // New fields for finished courses.
    $data['finalizado'] = $node->hasField('field_finalizado') ? $node->get('field_finalizado')->value : FALSE;
    $data['evidencias'] = [];
    if ($node->hasField('field_evidencias_curso') && !$node->get('field_evidencias_curso')->isEmpty()) {
      foreach ($node->get('field_evidencias_curso') as $item) {
        if ($file = $item->entity) {
          $data['evidencias'][] = $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri());
        }
      }
    }

    // Additional course metadata.
    $data['duracion'] = $node->hasField('field_duracion_horas') ? $node->get('field_duracion_horas')->value : NULL;
    $data['entidad'] = $node->hasField('field_entidad_que_certifica') ? $node->get('field_entidad_que_certifica')->value : NULL;
    $data['modalidad'] = $node->hasField('field_modalidad_del_curso') ? $node->get('field_modalidad_del_curso')->value : NULL;
    
    if ($node->hasField('field_fecha_inicio') && !$node->get('field_fecha_inicio')->isEmpty()) {
      $data['fecha_inicio'] = \Drupal::service('date.formatter')->format(strtotime($node->get('field_fecha_inicio')->value), 'custom', 'd/m/Y');
    }
    if ($node->hasField('field_fecha_fin') && !$node->get('field_fecha_fin')->isEmpty()) {
      $data['fecha_fin'] = \Drupal::service('date.formatter')->format(strtotime($node->get('field_fecha_fin')->value), 'custom', 'd/m/Y');
    }
    if ($node->hasField('field_link_del_curso') && !$node->get('field_link_del_curso')->isEmpty()) {
      $data['link_curso'] = $node->get('field_link_del_curso')->uri;
    }

    return [
      '#theme' => 'zinco_curso_detail',
      '#curso' => $data,
      '#attached' => [
        'library' => [
          'zinco_front/zinco-landing-page',
        ],
      ],
    ];
  }

}
