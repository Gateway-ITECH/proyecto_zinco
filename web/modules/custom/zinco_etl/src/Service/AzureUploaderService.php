<?php

namespace Drupal\zinco_etl\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Service for uploading files to Azure Blob Storage.
 */
class AzureUploaderService {
  use StringTranslationTrait;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The logger channel factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * The Azure Blob Storage connection string.
   *
   * @var string
   */
  protected $connectionString;

  /**
   * Constructs a new AzureUploaderService object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger channel factory.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The Guzzle HTTP client.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, LoggerChannelFactoryInterface $logger_factory, TranslationInterface $string_translation) {
    $this->configFactory = $config_factory;
    $this->loggerFactory = $logger_factory;
    $this->httpClient = $http_client;
    $this->stringTranslation = $string_translation;
    $this->initializeAzureCredentials();
  }

  /**
   * Gets the Azure Blob Storage connection string.
   *
   * @return string
   *   The Azure Blob Storage connection string.
   */
  public function getConnectionString(): string {
    return $this->connectionString;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('logger.factory'),
      $container->get('string_translation')
    );
  }

  /**
   * Initializes the Azure Blob Storage credentials from the connection string.
   */
  protected function initializeAzureCredentials() {
    $config = $this->configFactory->get('zinco_etl.settings');
    $this->connectionString = $config->get('azure_blob_connection_string');

    if (empty($this->connectionString)) {
      $this->loggerFactory->get('zinco_etl')->error('Azure Blob Storage connection string is not configured.');
    }
  }


  /**
   * Uploads a file to a subfolder in an Azure Blob Storage container using REST API.
   *
   * @param string $file_path
   *   The full path to the file on the local filesystem.
   * @param string $subfolder
   *   The name of the subfolder within the container (e.g., 'imports/daily').
   * @param string $blob_name
   *   (Optional) The name of the blob in Azure. If not provided, the basename
   *   of the local file path will be used.
   *
   * @return bool
   *   TRUE if the upload was successful, FALSE otherwise.
   */
  public function uploadFile(string $file_path, string $subfolder, string $blob_name = ''): bool {
    if (empty($this->connectionString)) {
      $this->loggerFactory->get('zinco_etl')->error('Azure Blob Storage connection string is not configured. Cannot upload file.');
      return FALSE;
    }

    $config = $this->configFactory->get('zinco_etl.settings');
    $container_name = $config->get('azure_blob_container_name');

    if (empty($container_name)) {
      $this->loggerFactory->get('zinco_etl')->error('Azure Blob Storage container name is not configured. Cannot upload file.');
      return FALSE;
    }

    if (!file_exists($file_path)) {
      $this->loggerFactory->get('zinco_etl')->error('Local file not found: {file_path}', ['file_path' => $file_path]);
      return FALSE;
    }

    if (empty($blob_name)) {
      $blob_name = basename($file_path);
    }

    $full_blob_path = trim($subfolder, '/') . '/' . $blob_name;
    $script_path = DRUPAL_ROOT . '/../scripts/subir_archivo.py';

    // Ensure the script exists.
    if (!file_exists($script_path)) {
      $this->loggerFactory->get('zinco_etl')->error('Python upload script not found: {script_path}', ['script_path' => $script_path]);
      return FALSE;
    }

    // Build the command to execute the Python script.
    // Arguments: container_name, connection_string, file_path, full_blob_path
    $command = [
      'python',
      $script_path,
      $file_path,
      $container_name,
      $this->connectionString,      
      $full_blob_path,
    ];

    $process = new Process($command);
    $process->setTimeout(3600); // Set a timeout for the process (e.g., 1 hour).

    $this->loggerFactory->get('zinco_etl')->info('Executing Azure Blob Storage upload script: {command}', ['command' => $process->getCommandLine()]);

    try {
      $process->mustRun();

      $this->loggerFactory->get('zinco_etl')->info('Successfully uploaded file {file_path} to Azure Blob Storage container {container_name}/{full_blob_path} using Python script. Output: {output}', [
        'file_path' => $file_path,
        'container_name' => $container_name,
        'full_blob_path' => $full_blob_path,
        'output' => $process->getOutput(),
      ]);
      return TRUE;
    }
    catch (ProcessFailedException $exception) {
      $this->loggerFactory->get('zinco_etl')->error('Failed to upload file {file_path} to Azure Blob Storage using Python script. Error: {error}, Output: {output}', [
        'file_path' => $file_path,
        'error' => $exception->getMessage(),
        'output' => $process->getErrorOutput(),
      ]);
      return FALSE;
    }
  }

  /**
   * Tests the connection to the Azure Blob Storage container.
   *
   * @return array
   *   An associative array with 'status' (bool) and 'message' (string).
   */
  public function testConnection(): array {
    if (empty($this->connectionString)) {
      return [
        'status' => FALSE,
        'message' => $this->t('Azure Blob Storage connection string is not configured. Check connection string configuration.'),
      ];
    }

    $config = $this->configFactory->get('zinco_etl.settings');
    $container_name = $config->get('azure_blob_container_name');

    if (empty($container_name)) {
      return [
        'status' => FALSE,
        'message' => $this->t('Azure Blob Storage container name is not configured.'),
      ];
    }

    $script_path = DRUPAL_ROOT . '/../scripts/test_azure_connection.py';

    // Ensure the script exists.
    if (!file_exists($script_path)) {
      $this->loggerFactory->get('zinco_etl')->error('Python connection test script not found: {script_path}', ['script_path' => $script_path]);
      return [
        'status' => FALSE,
        'message' => $this->t('Python connection test script not found.'),
      ];
    }

    $command = [
      'python',
      $script_path,
      $container_name,
      $this->connectionString,
    ];

    $process = new Process($command);
    $process->setTimeout(60); // Set a timeout for the process (e.g., 60 seconds).

    $this->loggerFactory->get('zinco_etl')->info('Executing Azure Blob Storage connection test script: {command}', ['command' => $process->getCommandLine()]);

    try {
      $process->mustRun();

      $this->loggerFactory->get('zinco_etl')->info('Azure Blob Storage connection test successful. Output: {output}', [
        'output' => $process->getOutput(),
      ]);
      return [
        'status' => TRUE,
        'message' => $this->t('Successfully connected to Azure Blob Storage. Container "{container}" exists.', ['{container}' => $container_name]),
      ];
    }
    catch (ProcessFailedException $exception) {
      $this->loggerFactory->get('zinco_etl')->error('Azure Blob Storage connection test failed. Error: {error}, Output: {output}', [
        'error' => $exception->getMessage(),
        'output' => $process->getErrorOutput(),
      ]);
      return [
        'status' => FALSE,
        'message' => $this->t('Azure Blob Storage connection failed: @message', ['@message' => $process->getErrorOutput()]),
      ];
    }
  }

}