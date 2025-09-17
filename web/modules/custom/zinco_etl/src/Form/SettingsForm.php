<?php

namespace Drupal\zinco_etl\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\zinco_etl\Service\AzureUploaderService;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
/**
 * Configure Zinco ETL settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The Azure Uploader service.
   *
   * @var \Drupal\zinco_etl\Service\AzureUploaderService
   */
  protected $azureUploaderService;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a new SettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\zinco_etl\Service\AzureUploaderService $azure_uploader_service
   *   The Azure Uploader service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AzureUploaderService $azure_uploader_service, MessengerInterface $messenger) {
    //parent::__construct($config_factory, 'zinco_etl.settings');
    $this->azureUploaderService = $azure_uploader_service;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('zinco_etl.azure_uploader'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'zinco_etl_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['zinco_etl.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('zinco_etl.settings');

    $form['azure_blob_connection_string'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Azure Blob Storage Connection String'),
      '#default_value' => $config->get('azure_blob_connection_string'),
      '#description' => $this->t('The connection string for your Azure Blob Storage account.'),
      '#required' => TRUE,
    ];

    $form['azure_blob_container_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Azure Blob Storage Container Name'),
      '#default_value' => $config->get('azure_blob_container_name'),
      '#description' => $this->t('The name of the container in Azure Blob Storage where files will be uploaded.'),
      '#required' => TRUE,
    ];

    $form['test_connection'] = [
      '#type' => 'button',
      '#value' => $this->t('Test Azure Blob Connection'),
      '#name' => 'test_connection',
      '#ajax' => [
        'callback' => '::testConnectionAjaxCallback',
        'event' => 'click',
        'wrapper' => 'azure-connection-test-message',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Testing connection...'),
        ],
      ],
    ];

    $form['azure_connection_test_message'] = [
      '#type' => 'markup',
      '#markup' => '<div id="azure-connection-test-message"></div>',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * AJAX callback for testing Azure Blob Storage connection.
   */
  public function testConnectionAjaxCallback(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $test_result = $this->azureUploaderService->testConnection();

    if ($test_result['status']) {
      $this->messenger->addStatus($test_result['message']);
      $response->addCommand(new HtmlCommand('#azure-connection-test-message', '<div class="messages messages--status">' . $test_result['message'] . '</div>'));
    }
    else {
      $this->messenger->addError($test_result['message']);
      $response->addCommand(new HtmlCommand('#azure-connection-test-message', '<div class="messages messages--error">' . $test_result['message'] . '</div>'));
    }
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('zinco_etl.settings')
      ->set('azure_blob_connection_string', $form_state->getValue('azure_blob_connection_string'))
      ->set('azure_blob_container_name', $form_state->getValue('azure_blob_container_name'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}