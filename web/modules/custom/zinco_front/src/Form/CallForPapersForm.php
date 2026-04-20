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
    ];

    $form['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Mensaje masivo (Call for Papers)'),
      '#description' => $this->t('Este mensaje será enviado como notificación a todos los usuarios seleccionados.'),
      '#required' => TRUE,
      '#rows' => 5,
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
   * Batch operation callback to process notifications.
   */
  public static function processBatchNotifications($uids, $message, $sender_uid, &$context)
  {
    $database = \Drupal::database();
    $mailManager = \Drupal::service('plugin.manager.mail');
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

      // Load user and send email if possible.
      $user = \Drupal\user\Entity\User::load($uid);
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
