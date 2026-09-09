<?php

namespace Drupal\zinco_front\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\taxonomy\Entity\Term;

/**
 * Service for dumping data from database tables.
 */
class DumpDataService
{

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new DumpDataService object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(Connection $database, ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager)
  {
    $this->database = $database;
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('database'),
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Retrieves all records from a specified table.
   *
   * @param string $tableName
   *   The name of the table to query.
   * @param array $filters
   *   (optional) An associative array of filters to apply.
   *
   * @return array
   *   An array of associative arrays, where each inner array represents a row.
   */
  public function obtenerTabla(string $tableName, array $filters = []): array
  {
    try {
      $query = $this->database->select($tableName, 't')
        ->fields('t');

      // Load configuration for the dashboard tables.
      $config = $this->configFactory->get('zinco_front.dashboard.settings');
      $tables_data = $config->get('tables_data') ?: [];
      $filters_data = $config->get('filters_data') ?: [];

      // Find the configuration for the current table.
      $table_config = NULL;
      foreach ($tables_data as $data) {
        if ($data['table_name'] === $tableName) {
          $table_config = $data;
          break;
        }
      }

      // Apply filters based on configuration.
      foreach ($filters as $field => $value) {
        if (!empty($value) && $table_config && isset($table_config[$field]) && $table_config[$field]) {
          $query->condition($field, $value);
        }
      }

      $filters_config = [];
      foreach ($filters_data as $data) {
        if ($data['table_name'] === $tableName) {
          $filters_config[] = $data;
        }
      }

      // Apply OR filters if provided.
      if (!empty($filters_config)) {
        $or = $query->orConditionGroup();
        foreach ($filters_config as $value) {
          $or->condition($value['field'], $value['value']);
        }
        $query->condition($or);
      }

      return $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
    } catch (\Exception $e) {
      \Drupal::logger('zinco_front')->error('Error query in table @table: @message', [
        '@table' => $tableName,
        '@message' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Retrieves the sum of values from a specified cumulative field in a table.
   *
   * @param string $tableName
   *   The name of the table to query.
   * @param string $cumulativeFieldName
   *   The name of the field to sum.
   * @param array $filters
   *   (optional) An associative array of filters to apply.
   *
   * @return int
   *   The sum of the values in the cumulative field, or 0 if no records are found.
   */
  public function obtenerTablaCampoAcumulativo(string $tableName, string $cumulativeFieldName, array $filters = []): int
  {
    try {
      $query = $this->database->select($tableName, 't')
        ->fields('t', [$cumulativeFieldName]);

      // Load configuration for the dashboard tables.
      $config = $this->configFactory->get('zinco_front.dashboard.settings');
      $tables_data = $config->get('tables_data') ?: [];
      $filters_data = $config->get('filters_data') ?: [];

      // Find the configuration for the current table.
      $table_config = NULL;
      foreach ($tables_data as $data) {
        if ($data['table_name'] === $tableName) {
          $table_config = $data;
          break;
        }
      }

      // Apply filters based on configuration.
      foreach ($filters as $field => $value) {
        if (!empty($value) && $table_config && isset($table_config[$field]) && $table_config[$field]) {
          $query->condition($field, $value);
        }
      }

      $filters_config = [];
      foreach ($filters_data as $data) {
        if ($data['table_name'] === $tableName) {
          $filters_config[] = $data;
        }
      }

      // Apply OR filters if provided.
      if (!empty($filters_config)) {
        $or = $query->orConditionGroup();
        foreach ($filters_config as $value) {
          $or->condition($value['field'], $value['value']);
        }
        $query->condition($or);
      }

      $result = $query->execute()->fetchCol();
      return array_sum($result);
    } catch (\Exception $e) {
      \Drupal::logger('zinco_front')->error('Error query in table @table: @message', [
        '@table' => $tableName,
        '@message' => $e->getMessage(),
      ]);
      return 0;
    }
  }

  /**
   * Retrieves all records from a specified entity.
   *
   * @param string $entity_type_id
   *   The entity type ID to query.
   * @param array $filters
   *   (optional) An associative array of filters to apply.
   *
   * @return array
   *   An array of associative arrays, where each inner array represents a row.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function obtenerEntidad(string $entity_type_id, array $filters = []): array
  {
    try {
      $storage = $this->entityTypeManager->getStorage($entity_type_id);
      $query = $storage->getQuery();
      $query->accessCheck(FALSE); // Disable access checks
      $ids = $query->execute();
      $entities = $storage->loadMultiple($ids);

      $result = [];
      foreach ($entities as $entity) {
        $row = [];
        switch ($entity_type_id) {
          case 'zinco_retos_innovacion':
            $row['label'] = $entity->get('label')->value;
            $row['estado_reto_innovacion'] = !empty($entity->estado_reto_innovacion->target_id) ? $this->safeGetTermName($entity->estado_reto_innovacion->target_id) : '';
            $row['sector'] = !empty($entity->sector_economico->target_id) ? $this->safeGetTermName($entity->sector_economico->target_id) : '';
            $row['tecnologia40'] = !empty($entity->area_enfoque->target_id) ? $this->safeGetTermName($entity->area_enfoque->target_id) : '';
            $row['municipio'] = !empty($entity->field_municipio->target_id) ? $this->safeGetTermName($entity->field_municipio->target_id) : '';
            break;
          case 'zinco_proyectos_idi':
            $row['label'] = $entity->get('label')->value;
            $row['estado_proyecto'] = !empty($entity->field_estado_proyecto->target_id) ? $this->safeGetTermName($entity->field_estado_proyecto->target_id) : '';
            $row['sector'] = !empty($entity->field_sector_economico_proyecto->target_id) ? $this->safeGetTermName($entity->field_sector_economico_proyecto->target_id) : '';
            $row['tecnologia40'] = !empty($entity->field_tecnologia_principal_proye->target_id) ? $this->safeGetTermName($entity->field_tecnologia_principal_proye->target_id) : '';
            $row['municipio'] = !empty($entity->field_municipio_proyecto->target_id) ? $this->safeGetTermName($entity->field_municipio_proyecto->target_id) : '';
            break;
          case 'zinco_proyectos_software':
            $row['label'] = $entity->get('label')->value;
            $row['estado_proyecto'] = !empty($entity->field_estado_software->target_id) ? $this->safeGetTermName($entity->field_estado_software->target_id) : '';
            $row['sector'] = !empty($entity->field_sector_economico_software->target_id) ? $this->safeGetTermName($entity->field_sector_economico_software->target_id) : '';
            $row['tecnologia40'] = !empty($entity->field_tecnologia_clave_software->target_id) ? $this->safeGetTermName($entity->field_tecnologia_clave_software->target_id) : '';
            $row['municipio'] = !empty($entity->field_municipio_software->target_id) ? $this->safeGetTermName($entity->field_municipio_software->target_id) : '';
            break;
          case 'zinco_software_recursos':
            if (!empty($entity->field_proyecto_de_software->target_id)) {
              $referenced_entity = $this->entityTypeManager->getStorage('zinco_proyectos_software')->load($entity->field_proyecto_de_software->target_id);
              if ($referenced_entity) {
                $row['label'] = $entity->get('label')->value;
                $row['sector'] = !empty($referenced_entity->field_sector_economico_software->target_id) ? $this->safeGetTermName($referenced_entity->field_sector_economico_software->target_id) : '';
                $row['tecnologia40'] = !empty($referenced_entity->field_tecnologia_clave_software->target_id) ? $this->safeGetTermName($referenced_entity->field_tecnologia_clave_software->target_id) : '';
                $row['municipio'] = !empty($referenced_entity->field_municipio_software->target_id) ? $this->safeGetTermName($referenced_entity->field_municipio_software->target_id) : '';
                $row['monto_obtenido'] = !empty($entity->field_monto_obtenido->value) ? $entity->field_monto_obtenido->value : 0;
                $row['fuente_financiacion'] = !empty($entity->field_tipo_de_fuente_de_financia->target_id) ? $this->safeGetTermName($entity->field_tipo_de_fuente_de_financia->target_id) : '';
              }
            }
            break;
          case 'node':
            if ($entity->getType() == 'evento') {
              $row['tipo_evento'] = !empty($entity->field_tipos_de_evento->target_id) ? $this->safeGetTermName($entity->field_tipos_de_evento->target_id) : '';
              $row['sector'] = !empty($entity->field_sector_evento->target_id) ? $this->safeGetTermName($entity->field_sector_evento->target_id) : '';
              $row['tecnologia40'] = !empty($entity->field_tecnologia_evento->target_id) ? $this->safeGetTermName($entity->field_tecnologia_evento->target_id) : '';
              $row['municipio'] = !empty($entity->field_municipio_evento->target_id) ? $this->safeGetTermName($entity->field_municipio_evento->target_id) : '';
            } else {
              $row['tipo_evento'] = 'NA';
            }
            break;
          case 'zinco_reconocimientos':
            $row['label'] = $entity->get('label')->value;
            $row['bundle'] = $entity->bundle();
            $row['sector'] = !empty($entity->field_sector_startup->target_id) ? $this->safeGetTermName($entity->field_sector_startup->target_id) : '';
            $row['tecnologia40'] = !empty($entity->field_tecnologia_clave_startup->target_id) ? $this->safeGetTermName($entity->field_tecnologia_clave_startup->target_id) : '';
            $row['municipio'] = !empty($entity->field_municipio_startup->target_id) ? $this->safeGetTermName($entity->field_municipio_startup->target_id) : '';
            break;
          case 'zinco_actors_zincoactors':
            $row['label'] = $entity->label();
            $row['bundle'] = $entity->bundle();
            $sector_tid = ($entity->hasField('sector_economico_principal') && !$entity->get('sector_economico_principal')->isEmpty())
              ? $entity->get('sector_economico_principal')->target_id : null;
            $row['sector'] = $sector_tid ? $this->safeGetTermName($sector_tid) : '';

            $muni_tid = ($entity->hasField('municipio') && !$entity->get('municipio')->isEmpty())
              ? $entity->get('municipio')->target_id : null;
            $row['municipio'] = $muni_tid ? $this->safeGetTermName($muni_tid) : '';

            $tec_tid = ($entity->hasField('tecnologias_clave') && !$entity->get('tecnologias_clave')->isEmpty())
              ? $entity->get('tecnologias_clave')->target_id : null;
            $row['tecnologia40'] = $tec_tid ? $this->safeGetTermName($tec_tid) : '';
            break;
        }

        $match = TRUE;
        foreach ($filters as $filter_key => $filter_value) {
          if ($filter_value !== '' && $filter_value !== null && isset($row[$filter_key]) && $row[$filter_key] != $filter_value) {
            $match = FALSE;
            break;
          }
        }

        if ($match) {
          $result[] = $row;
        }
      }

      return $result;
    } catch (\Exception $e) {
      \Drupal::logger('zinco_front')->error('Error query in entity @entity: @message', [
        '@entity' => $entity_type_id,
        '@message' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Retrieves all records from a specified entity bundle.
   *
   * @param string $entity_type_id
   *   The entity type ID to query.
   * @param string $bundle
   *   The bundle name.
   * @param array $filters
   *   (optional) An associative array of filters to apply.
   *
   * @return array
   *   An array of associative arrays, where each inner array represents a row.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function obtenerBundle(string $entity_type_id, string $bundle, array $filters = []): array
  {
    try {
      $storage = $this->entityTypeManager->getStorage($entity_type_id);
      $query = $storage->getQuery();
      $query->accessCheck(FALSE); // Disable access checks
      $query->condition('bundle', $bundle);
      $ids = $query->execute();
      $entities = $storage->loadMultiple($ids);

      $result = [];
      foreach ($entities as $entity) {
        $row = [];
        $row['label'] = $entity->get('label')->value;
        switch ($bundle) {
          case 'evento':
            $row['tipo_evento'] = !empty($entity->field_tipos_de_evento->target_id) ? $this->safeGetTermName($entity->field_tipos_de_evento->target_id) : '';
            $row['sector'] = !empty($entity->field_sector_evento->target_id) ? $this->safeGetTermName($entity->field_sector_evento->target_id) : '';
            $row['tecnologia40'] = !empty($entity->field_tecnologia_evento->target_id) ? $this->safeGetTermName($entity->field_tecnologia_evento->target_id) : '';
            $row['municipio'] = !empty($entity->field_municipio_evento->target_id) ? $this->safeGetTermName($entity->field_municipio_evento->target_id) : '';
            break;
          case 'empresa_explotadora_de_conocimie':
          case 'startups_de_base_tecnologica':
          case 'unidades_empresariales_de_i_d_i':
          case 'empresas_spin_off':
          case 'emprendimientos_dinamicos':
          case 'fondos_de_corporate_venture':
          case 'fondos_de_venture_capital':
          case 'instituciones_microfinancieras':
          case 'inversionistas_y_redes_de_invers':
          case 'plataformas_de_crowdfunding':
          case 'empresa_generadora_de_conocimien':
          case 'institucion_de_educacion_superio':
          case 'investigador':
          case 'grupos_de_investigacion':
          case 'centros_e_institutos_de_investig':
          case 'centros_de_desarrollo_tecnologic':
          case 'instancias_de_orientacion_politi':
          case 'otri':
          case 'parque_tecnologico':
          case 'cati':
          case 'centros_de_innovacion_y_producti':
          case 'incubadora':
          case 'centros_de_emprendimiento_ies':
          case 'redes_de_mentores':
          case 'aceleradora':
          case 'entidad_gobierno':
            $sector_tid = ($entity->hasField('sector_economico_principal') && !$entity->get('sector_economico_principal')->isEmpty())
              ? $entity->get('sector_economico_principal')->target_id : null;
            $row['sector'] = $sector_tid ? $this->safeGetTermName($sector_tid) : '';

            $muni_tid = ($entity->hasField('municipio') && !$entity->get('municipio')->isEmpty())
              ? $entity->get('municipio')->target_id : null;
            $row['municipio'] = $muni_tid ? $this->safeGetTermName($muni_tid) : '';

            $tec_tid = ($entity->hasField('tecnologias_clave') && !$entity->get('tecnologias_clave')->isEmpty())
              ? $entity->get('tecnologias_clave')->target_id : null;
            $row['tecnologia40'] = $tec_tid ? $this->safeGetTermName($tec_tid) : '';
            break;
        }

        $match = TRUE;
        foreach ($filters as $filter_key => $filter_value) {
          if ($filter_value !== '' && $filter_value !== null && isset($row[$filter_key]) && $row[$filter_key] != $filter_value) {
            $match = FALSE;
            break;
          }
        }

        if ($match) {
          $result[] = $row;
        }
      }

      return $result;
    } catch (\Exception $e) {
      \Drupal::logger('zinco_front')->error('Error query in bundle @bundle: @message', [
        '@bundle' => $bundle,
        '@message' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Retrieves grouped records from a specified entity.
   *
   * @param string $entity_type_id
   *   The entity type ID to query.
   * @param string $groupColumn
   *   The column to group the data by.
   * @param array $filters
   *   (optional) An associative array of filters to apply.
   *
   * @return array
   *   An array of associative arrays, where each inner array represents a row.
   */
  public function obtenerEntidadAgrupada(string $entity_type_id, string $groupColumn, array $filters = []): array
  {
    try {
      $entity_storage = $this->entityTypeManager->getStorage($entity_type_id);
      $query = $entity_storage->getQuery();
      $query->accessCheck(FALSE);

      foreach ($filters as $field => $value) {
        if (!empty($value)) {
          $query->condition($field, $value);
        }
      }

      $query->groupBy($groupColumn);
      $query->addExpression('COUNT(' . $groupColumn . ')', 'count');
      $entity_ids = $query->execute();

      $results = [];
      foreach ($entity_ids as $group_value => $count) {
        $results[] = [
          'group_column' => $group_value,
          'count' => $count,
        ];
      }

      // Calculate total count for percentage.
      $total_count_query = $entity_storage->getQuery();
      $total_count_query->accessCheck(FALSE);
      foreach ($filters as $field => $value) {
        if (!empty($value)) {
          $total_count_query->condition($field, $value);
        }
      }
      $total_count = count($total_count_query->execute());

      // Add percentage to each result.
      foreach ($results as &$row) {
        $row['percentage'] = ($total_count > 0) ? round(($row['count'] / $total_count) * 100, 2) : 0;
      }

      return $results;
    } catch (\Exception $e) {
      \Drupal::logger('zinco_front')->error('Error query in entity @entity: @message', [
        '@entity' => $entity_type_id,
        '@message' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Retrieves grouped records from a specified table.
   *
   * @param string $tableName
   *   The name of the table to query.
   * @param string $groupColumn
   *   The column to group the data by.
   * @param array $filters
   *   (optional) An associative array of filters to apply.
   *
   * @return array
   *   An array of associative arrays, where each inner array represents a row.
   */
  public function obtenerTablaAgrupada(string $tableName, string $groupColumn, array $filters = []): array
  {
    try {
      $query = $this->database->select($tableName, 't');
      $query->addField('t', $groupColumn, 'group_column');
      $query->addExpression('COUNT(' . $groupColumn . ')', 'count');

      // Load configuration for the dashboard tables.
      $config = $this->configFactory->get('zinco_front.dashboard.settings');
      $tables_data = $config->get('tables_data') ?: [];
      $filters_data = $config->get('filters_data') ?: [];

      // Find the configuration for the current table.
      $table_config = NULL;
      foreach ($tables_data as $data) {
        if ($data['table_name'] === $tableName) {
          $table_config = $data;
          break;
        }
      }

      // Apply filters based on configuration.
      foreach ($filters as $field => $value) {
        if (!empty($value) && $table_config && isset($table_config[$field]) && $table_config[$field]) {
          $query->condition($field, $value);
        }
      }

      $filters_config = [];
      foreach ($filters_data as $data) {
        if ($data['table_name'] === $tableName) {
          $filters_config[] = $data;
        }
      }

      // Apply OR filters if provided.
      if (!empty($filters_config)) {
        $or = $query->orConditionGroup();
        foreach ($filters_config as $value) {
          $or->condition($value['field'], $value['value']);
        }
        $query->condition($or);
      }

      $query->groupBy($groupColumn);
      $results = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);

      // Calculate total count for percentage.
      $total_count_query = $this->database->select($tableName, 't');
      $total_count_query->addExpression('COUNT(*)', 'total');

      foreach ($filters as $field => $value) {
        if (!empty($value) && $table_config && isset($table_config[$field]) && $table_config[$field]) {
          $total_count_query->condition($field, $value);
        }
      }
      $total_count = $total_count_query->execute()->fetchField();

      // Add percentage to each result.
      foreach ($results as &$row) {
        $row['percentage'] = ($total_count > 0) ? round(($row['count'] / $total_count) * 100, 2) : 0;
      }

      return $results;
    } catch (\Exception $e) {
      \Drupal::logger('zinco_front')->error('Error query in table @table: @message', [
        '@table' => $tableName,
        '@message' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Retrieves data from multiple specified tables.
   */
  public function obtenerTablas(array $tableNames, array $filters = []): array
  {
    $results = [];
    foreach ($tableNames as $tableName) {
      $results[$tableName] = $this->obtenerTabla($tableName, $filters);
    }
    return $results;
  }

  /**
   * Retrieves grouped data from multiple specified tables.
   */
  public function obtenerTablasAgrupadas(array $tableNames, string $groupColumn, array $filters = []): array
  {
    $results = [];
    foreach ($tableNames as $tableName) {
      $results[$tableName] = $this->obtenerTablaAgrupada($tableName, $groupColumn, $filters);
    }
    $consolidated_data = [];
    foreach ($results as $table => $data) {
      foreach ($data as $entry) {
        $group_value = $entry['group_column'];
        if (!isset($consolidated_data[$group_value])) {
          $consolidated_data[$group_value] = [
            'group_column' => $group_value,
            'count' => 0,
            'percentage' => 0,
          ];
        }
        $consolidated_data[$group_value]['count'] += $entry['count'];
        $consolidated_data[$group_value]['percentage'] += $entry['percentage'];
      }
    }

    $total_count = array_sum(array_column($consolidated_data, 'count'));

    foreach ($consolidated_data as &$entry) {
      $entry['percentage'] = ($total_count > 0) ? ($entry['count'] / $total_count) * 100 : 0;
    }

    return ['results' => $results, 'consolidated' => $consolidated_data];
  }

  /**
   * Retrieves all records for the 'zinco_retos_innovacion_type' entity with 'reto' bundle.
   */
  public function obtenerRetos(array $filters = []): array
  {
    $filters['bundle'] = 'reto';
    return $this->obtenerEntidad('zinco_retos_innovacion_type', $filters);
  }

  /**
   * Safely gets the name of a taxonomy term.
   *
   * @param int|string|null $tid
   *   The term ID.
   *
   * @return string
   *   The term name or empty string if not found.
   */
  private function safeGetTermName($tid)
  {
    if (empty($tid)) {
      return '';
    }
    try {
      $term = Term::load($tid);
      return $term ? $term->getName() : '';
    } catch (\Exception $e) {
      return '';
    }
  }

}