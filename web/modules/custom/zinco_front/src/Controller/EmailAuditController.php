<?php

namespace Drupal\zinco_front\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatterInterface;

/**
 * Controller for auditing emails/notifications sent to actors.
 */
class EmailAuditController extends ControllerBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs an EmailAuditController object.
   */
  public function __construct(Connection $database, DateFormatterInterface $dateFormatter) {
    $this->database = $database;
    $this->dateFormatter = $dateFormatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('date.formatter')
    );
  }

  /**
   * Displays the email audit log list.
   *
   * @return array
   *   Render array for the audit page.
   */
  public function list() {
    $header = [
      ['data' => $this->t('ID'), 'field' => 'id'],
      ['data' => $this->t('Fecha y Hora'), 'field' => 'created', 'sort' => 'desc'],
      ['data' => $this->t('Usuario Remitente (Inició envío)')],
      ['data' => $this->t('Destinatario (Actor/Usuario)')],
      ['data' => $this->t('Mensaje Enviado')],
    ];

    $query = $this->database->select('zinco_notifications', 'zn')
      ->fields('zn', ['id', 'notification', 'created', 'uid_sender', 'uid_receiver'])
      ->extend('\Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('\Drupal\Core\Database\Query\TableSortExtender')
      ->orderByHeader($header)
      ->limit(30);

    $results = $query->execute();

    $rows = [];
    foreach ($results as $row) {
      // Sender info.
      $sender_markup = $this->t('Sistema');
      if ($row->uid_sender > 0) {
        $sender = User::load($row->uid_sender);
        if ($sender) {
          $sender_name = $sender->getDisplayName();
          $sender_mail = $sender->getEmail();
          $sender_markup = '<strong>' . htmlspecialchars($sender_name) . '</strong><br><small class="text-muted">' . htmlspecialchars($sender_mail) . ' (UID: ' . $row->uid_sender . ')</small>';
        }
        else {
          $sender_markup = $this->t('Usuario eliminado (UID: @uid)', ['@uid' => $row->uid_sender]);
        }
      }

      // Receiver info.
      $receiver_markup = $this->t('Desconocido');
      if ($row->uid_receiver > 0) {
        $receiver = User::load($row->uid_receiver);
        if ($receiver) {
          $receiver_name = $receiver->getDisplayName();
          $receiver_mail = $receiver->getEmail();
          $receiver_markup = '<strong>' . htmlspecialchars($receiver_name) . '</strong><br><small class="text-muted">' . htmlspecialchars($receiver_mail) . '</small>';
        }
        else {
          $receiver_markup = $this->t('Usuario #@uid', ['@uid' => $row->uid_receiver]);
        }
      }

      // Message snippet.
      $full_message = nl2br(htmlspecialchars($row->notification));
      $snippet = mb_substr(strip_tags($row->notification), 0, 100);
      if (mb_strlen($row->notification) > 100) {
        $snippet .= '...';
      }

      $message_cell = [
        'data' => [
          '#type' => 'details',
          '#title' => htmlspecialchars($snippet),
          '#open' => FALSE,
          'content' => [
            '#markup' => '<div style="max-height: 200px; overflow-y: auto; background: #f8f9fa; padding: 10px; border-radius: 4px; font-size: 0.9em;">' . $full_message . '</div>',
          ],
        ],
      ];

      $date_formatted = $this->dateFormatter->format($row->created, 'custom', 'd/m/Y - h:i:s A');

      $rows[] = [
        ['data' => $row->id],
        ['data' => $date_formatted],
        ['data' => ['#markup' => $sender_markup]],
        ['data' => ['#markup' => $receiver_markup]],
        $message_cell,
      ];
    }

    $build['description'] = [
      '#markup' => '<p>' . $this->t('Registro histórico y auditoría de todos los correos y notificaciones emitidos desde la lista de actores del ecosistema ZINCO. Muestra la fecha exacta, la hora y el usuario que originó el envío.') . '</p>',
    ];

    $build['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No se han registrado envíos de correos a actores aún.'),
      '#attributes' => ['class' => ['table', 'table-striped', 'table-hover', 'zinco-email-audit-table']],
    ];

    $build['pager'] = [
      '#type' => 'pager',
    ];

    return $build;
  }

}
