<?php

namespace Drupal\zinco_front\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Call for Papers form.
 */
class CallForPapersForm extends FormBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a new CallForPapersForm object.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'zinco_front_call_for_papers_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['receptors_selector'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['receptors-selector-wrapper']],
      'view' => views_embed_view('selector_de_receptores', 'embed_receptors_selector'),
      '#weight' => -10,
    ];

    $form['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Mensaje masivo (Call for Papers)'),
      '#description' => $this->t('Este mensaje será enviado como notificación a todos los usuarios con el rol "Actor Registrado".'),
      '#required' => TRUE,
      '#rows' => 5,
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Enviar Mensaje Masivo'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $message = $form_state->getValue('message');

    // Get all users with the role 'actor_registrado'.
    $uids = \Drupal::entityQuery('user')
      ->condition('status', 1)
      ->condition('roles', 'actor_registrado')
      ->accessCheck(FALSE)
      ->execute();

    if (empty($uids)) {
      $this->messenger()->addWarning($this->t('No se encontraron usuarios con el rol "Actor Registrado".'));
      return;
    }

    // Log the event.
    \Drupal::logger('zinco_retos_soluciones')->info('Envío masivo de Call for Papers iniciado. Mensaje: @message. Usuarios objetivo: @count', [
      '@message' => $message,
      '@count' => count($uids),
    ]);

    $batch = [
      'title' => $this->t('Enviando Call for Papers...'),
      'operations' => [],
      'init_message' => $this->t('Iniciando proceso de envío masivo.'),
      'progress_message' => $this->t('Enviando notificación @current de @total.'),
      'error_message' => $this->t('Ocurrió un error durante el proceso.'),
      'finished' => [static::class, 'batchFinished'],
    ];

    // Chunk uids to process in batches of 20 for better performance.
    $chunks = array_chunk($uids, 20);
    foreach ($chunks as $chunk) {
      $batch['operations'][] = [
        [static::class, 'processBatchNotifications'],
        [$chunk, $message, $this->currentUser()->id()],
      ];
    }

    batch_set($batch);
  }

  /**
   * Batch operation callback to process notifications.
   */
  public static function processBatchNotifications($uids, $message, $sender_uid, &$context) {
    $database = \Drupal::database();
    $created = time();

    foreach ($uids as $uid) {
      $database->insert('zinco_notifications')
        ->fields([
          'notification' => $message,
          'created' => $created,
          'uid_sender' => $sender_uid,
          'uid_receiver' => $uid,
        ])
        ->execute();
    }

    if (!isset($context['results']['count'])) {
      $context['results']['count'] = 0;
    }
    $context['results']['count'] += count($uids);
  }

  /**
   * Batch finished callback.
   */
  public static function batchFinished($success, $results, $operations) {
    if ($success) {
      $count = isset($results['count']) ? $results['count'] : 0;
      \Drupal::messenger()->addMessage(t('Se han enviado @count notificaciones exitosamente.', ['@count' => $count]));
    }
    else {
      \Drupal::messenger()->addError(t('Hubo un error al procesar el envío masivo.'));
    }
  }

}
