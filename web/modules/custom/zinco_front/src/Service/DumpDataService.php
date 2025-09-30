<?php

namespace Drupal\zinco_front\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Service for dumping data from database tables.
 */
class DumpDataService {

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
  public function __construct(Connection $database, ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    $this->database = $database;
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
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
  public function obtenerTabla(string $tableName, array $filters = []): array {
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
      //var_dump($filters_config);
      $or = $query->orConditionGroup();
      foreach ($filters_config as $value) {          
          $or->condition($value['field'], $value['value']);
      }
      $query->condition($or);
    }

    return $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
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
  public function obtenerEntidad(string $entity_type_id, array $filters = []): array {
    $storage = $this->entityTypeManager->getStorage('zinco_retos_innovacion');
    $query = $storage->getQuery(); 
    $query->accessCheck(FALSE); // Disable access checks
    $ids = $query->execute();
    $entities = $storage->loadMultiple($ids);

    $result = [];
    foreach ($entities as $entity) {
        $row = [];      
        $row['label'] = $entity->get('label')->value;   
        $row['estado_reto_innovacion'] = !empty($entity->estado_reto_innovacion->target_id) ? \Drupal\taxonomy\Entity\Term::load($entity->estado_reto_innovacion->target_id)->getName() : '';
        $row['sector_economico'] = $entity->get('sector_economico')->value;  
        $row['area_enfoque'] = $entity->get('area_enfoque')->value;  
        $result[] = $row;       
      }
    return $result;
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
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function obtenerEntidadAgrupada(string $entity_type_id, string $groupColumn, array $filters = []): array {
    $entity_storage = $this->entityTypeManager->getStorage($entity_type_id);
    $query = $entity_storage->getQuery();

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
  public function obtenerTablaAgrupada(string $tableName, string $groupColumn, array $filters = []): array {
    $query = $this->database->select($tableName, 't');
    //$query->fields('t', [$groupColumn], 'group_column');
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
      //var_dump($filters_config);
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
  }


    /**
     * Retrieves data from multiple specified tables.
     *
     * @param array $tableNames
     *   An array of table names to query.
     * @param array $filters
     *   (optional) An associative array of filters to apply.
     *
     * @return array
     *   An associative array where keys are table names and values are the results
     *   from obtenerTabla for each table.
     */
    public function obtenerTablas(array $tableNames, array $filters = []): array {
      $results = [];
      foreach ($tableNames as $tableName) {
        $results[$tableName] = $this->obtenerTabla($tableName, $filters);
      }
      return $results;
    }

  /**
   * Retrieves grouped data from multiple specified tables.
   *
   * @param array $tableNames
   *   An array of table names to query.
   * @param string $groupColumn
   *   The column to group the data by.
   * @param array $filters
   *   (optional) An associative array of filters to apply.
   *
   * @return array
   *   An associative array where keys are table names and values are the results
   *   from obtenerTablaAgrupada for each table.
   */
  public function obtenerTablasAgrupadas(array $tableNames, string $groupColumn, array $filters = []): array {
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
   *
   * @param array $filters
   *   (optional) An associative array of additional filters to apply.
   *
   * @return array
   *   An array of associative arrays, where each inner array represents a row.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function obtenerRetos(array $filters = []): array {
    $filters['bundle'] = 'reto';
    return $this->obtenerEntidad('zinco_retos_innovacion_type', $filters);
  }

}