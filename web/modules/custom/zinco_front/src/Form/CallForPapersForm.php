<?php

namespace Drupal\zinco_front\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Url;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Call for Papers form.
 */
class CallForPapersForm extends FormBase
{

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a new CallForPapersForm object.
   */
  public function __construct(Connection $database)
  {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return 'zinco_front_call_for_papers_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state)
  {
    $view_id = 'selector_de_receptores';
    $display_id = 'embed_receptors_selector';

    $view = \Drupal\views\Views::getView($view_id);

    if ($view) {
      $view->setDisplay($display_id);

      // Get user input from form state to pass it to the view for filtering.
      $input = $form_state->getUserInput();
      if (!empty($input)) {
        $view->setExposedInput($input);
      }

      $view->initHandlers();

      // Render the exposed filter form.
      $exposed_form = $view->display_handler->getPlugin('exposed_form');
      //$form['filtros_vista'] = $exposed_form->renderExposedForm();
      $form['previsualizacion_usuarios'] = [
        '#type' => 'details',
        '#title' => $this->t('Usuarios seleccionados para el envío'),
        '#open' => TRUE,
        '#weight' => -10,
        'results' => [
          '#type' => 'container',
          '#attributes' => ['id' => 'view-results-wrapper'],
          'view' => $view->render(),
        ],
      ];
    }

    //

    // Get all Zinco Retos Innovacion for the select list.
    $retos_storage = \Drupal::entityTypeManager()->getStorage('zinco_retos_innovacion');
    $retos = $retos_storage->loadMultiple();
    $options = ['' => $this->t('- Seleccione un reto -')];
    foreach ($retos as $reto) {
      $options[$reto->id()] = $reto->label();
    }

    $form['reto_innovacion'] = [
      '#type' => 'select',
      '#options' => $options,
      '#title' => $this->t('Reto de Innovación Relacionado'),
      '#description' => $this->t('Seleccione el reto de innovación relacionado con este mensaje.'),
      '#required' => FALSE,
      '#weight' => -5,
      '#ajax' => [
        'callback' => '::suggestMessageCallback',
        'wrapper' => 'message-textarea-wrapper',
        'event' => 'change',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Generando sugerencia...'),
        ],
      ],
    ];

    $form['message_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'message-textarea-wrapper'],
      '#weight' => 0,
    ];

    $reto_id = $form_state->getValue('reto_innovacion');
    $suggested_message = '';
    if ($reto_id) {
      try {
        $reto = \Drupal::entityTypeManager()->getStorage('zinco_retos_innovacion')->load($reto_id);
        if ($reto) {
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

          $fecha_inicio = '';
          if ($reto->hasField('fecha_inicio') && !$reto->get('fecha_inicio')->isEmpty()) {
            $fecha_inicio = $reto->get('fecha_inicio')->value;
          }

          $fecha_fin = '';
          if ($reto->hasField('fecha_fin') && !$reto->get('fecha_fin')->isEmpty()) {
            $fecha_fin = $reto->get('fecha_fin')->value;
          }

          $fecha_eval = '';
          if ($reto->hasField('fecha_evaluacion') && !$reto->get('fecha_evaluacion')->isEmpty()) {
            $fecha_eval = $reto->get('fecha_evaluacion')->value;
          }

          $suggested_message = "¡Nuevo Reto de Innovación: $title!\n\n";
          if ($description) {
            $suggested_message .= "Descripción: $description\n\n";
          }
          if ($organizador_str) {
            $suggested_message .= "Organizado por: $organizador_str\n";
          }
          if ($municipio) {
            $suggested_message .= "Ubicación: $municipio\n";
          }
          if ($recompensas) {
            $suggested_message .= "Recompensas: $recompensas\n";
          }
          
          if ($fecha_inicio || $fecha_fin || $fecha_eval) {
            $suggested_message .= "\nFechas clave:\n";
            if ($fecha_inicio) $suggested_message .= "- Inicio: $fecha_inicio\n";
            if ($fecha_fin) $suggested_message .= "- Fin: $fecha_fin\n";
            if ($fecha_eval) $suggested_message .= "- Evaluación: $fecha_eval\n";
          }
          
          $suggested_message .= "\nTe invitamos a participar y proponer tu solución.";
        }
      } catch (\Exception $e) {
        \Drupal::logger('zinco_debug')->error('Error al generar sugerencia de mensaje: @message', ['@message' => $e->getMessage()]);
      }
    }

    $form['message_wrapper']['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Mensaje masivo (Call for Papers)'),
      '#description' => $this->t('Este mensaje será enviado como notificación a todos los usuarios seleccionados.'),
      '#required' => TRUE,
      '#rows' => 10,
      '#value' => $suggested_message ?: $form_state->getValue('message'),
    ];

    $form['actions']['submit'] = [
      '#type' => 'button',
      '#value' => $this->t('Enviar Mensaje Masivo'),
      '#attributes' => [
        'id' => 'btn-enviar-masivo',
        'class' => ['button--primary'],
        'data-endpoint' => Url::fromRoute('zinco_front.send_call_for_papers')->toString(),
      ],
      '#attached' => [
        'library' => ['zinco_front/zinco-send-call-for-papers'],
      ],
    ];

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state)
  {
    // Validation logic if needed.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    // Validation logic if needed.
  }

  /**
   * AJAX callback to suggest a message based on the selected challenge.
   */
  public function suggestMessageCallback(array &$form, FormStateInterface $form_state)
  {
    return $form['message_wrapper'];
  }

  /**
   * Batch operation callback to process notifications.
   */
  public static function processBatchNotifications($uids, $message, $sender_uid, &$context)
  {
    $database = \Drupal::database();
    $mailManager = \Drupal::service('plugin.manager.mail');
    $created = time();

    $logger = \Drupal::logger('zinco_retos_soluciones');

    foreach ($uids as $actor_id) {
      // Find the user associated with this actor via field_actor.
      $user_ids = \Drupal::entityTypeManager()->getStorage('user')->getQuery()
        ->condition('field_actor', $actor_id)
        ->accessCheck(FALSE)
        ->execute();

      if (!empty($user_ids)) {
        $user_id = reset($user_ids);
        $user = \Drupal\user\Entity\User::load($user_id);

        $database->insert('zinco_notifications')
          ->fields([
            'notification' => $message,
            'created' => $created,
            'uid_sender' => $sender_uid,
            'uid_receiver' => $user_id,
          ])
          ->execute();

        // Log the notification.
        $logger->info('Notificación masiva enviada: Usuario @uid (Actor @actor). Mensaje: @msg', [
          '@uid' => $user_id,
          '@actor' => $actor_id,
          '@msg' => mb_substr($message, 0, 100) . (mb_strlen($message) > 100 ? '...' : ''),
        ]);

        // Send email if the user has one.
        if ($user && !empty($user->getEmail())) {
          $module = 'zinco_front';
          $key = 'call_for_papers';
          $to = $user->getEmail();
          $params = [
            'message' => $message,
            'subject' => t('Nueva Notificación: Call for Papers'),
          ];
          $langcode = $user->getPreferredLangcode();
          $mailManager->mail($module, $key, $to, $langcode, $params, NULL, TRUE);
        }
      }
    }

    if (!isset($context['results']['count'])) {
      $context['results']['count'] = 0;
    }
    $context['results']['count'] += count($uids);
  }

  /**
   * Batch finished callback.
   */
  public static function batchFinished($success, $results, $operations)
  {
    if ($success) {
      $count = isset($results['count']) ? $results['count'] : 0;
      \Drupal::messenger()->addMessage(t('Se han enviado @count notificaciones exitosamente.', ['@count' => $count]));
    } else {
      \Drupal::messenger()->addError(t('Hubo un error al procesar el envío masivo.'));
    }
  }

}
