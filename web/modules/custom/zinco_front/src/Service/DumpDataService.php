<?php

namespace Drupal\zinco_front\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
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
   * Constructs a new DumpDataService object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(Connection $database, ConfigFactoryInterface $config_factory) {
    $this->database = $database;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('config.factory')
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

    return $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
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
  public function obtenerTablaAgrupada(string $tableName, string $groupColumn, array $filters = [], , array $or_filters = []): array {
    $query = $this->database->select($tableName, 't');
    //$query->fields('t', [$groupColumn], 'group_column');
    $query->addField('t', $groupColumn, 'group_column');
    $query->addExpression('COUNT(' . $groupColumn . ')', 'count');

    // Load configuration for the dashboard tables.
    $config = $this->configFactory->get('zinco_front.dashboard.settings');
    $tables_data = $config->get('tables_data') ?: [];

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

    // Apply OR filters if provided.
    if (!empty($or_filters)) {
      $or = $query->orConditionGroup();
      foreach ($or_filters as $field => $value) {
          $or->condition($field, $value);
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

}