<?php

namespace Drupal\zinco_front\Service;

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
   * Constructs a new DumpDataService object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * Retrieves all records from a specified table.
   *
   * @param string $tableName
   *   The name of the table to query.
   *
   * @return array
   *   An array of associative arrays, where each inner array represents a row.
   */
  public function obtenerTabla(string $tableName): array {
    $query = $this->database->select($tableName, 't')
      ->fields('t')
      ->execute();

    return $query->fetchAll(\PDO::FETCH_ASSOC);
  }

}