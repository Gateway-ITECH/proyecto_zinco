<?php

declare(strict_types=1);

namespace Drupal\zinco_actors;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\user\Entity\User;

/**
 * Provides a list controller for the zincoactors entity type.
 */
final class ZincoActorsListBuilder extends EntityListBuilder {

  /**
   * Cache of actor ID to user entity mapping for performance.
   *
   * @var array
   */
  protected array $actorUserMap = [];

  /**
   * Number of actors without an associated user.
   *
   * @var int|null
   */
  protected ?int $pendingUsersCount = null;

  /**
   * {@inheritdoc}
   */
  public function render() {
    $request = \Drupal::request();
    $is_filtered_sin_usuario = $request->query->get('sin_usuario') === '1';
    $search_label = $request->query->get('label') ?? '';

    $pending_count = $this->getPendingUsersCount();

    $action_buttons = [];

    if ($is_filtered_sin_usuario) {
      $action_buttons['ver_todos'] = [
        '#type' => 'link',
        '#title' => $this->t('← Ver todos los actores'),
        '#url' => Url::fromRoute('entity.zinco_actors_zincoactors.collection'),
        '#attributes' => ['class' => ['button', 'button--secondary', 'me-2']],
      ];

      if ($pending_count > 0) {
        $action_buttons['generar_masivo'] = [
          '#type' => 'link',
          '#title' => $this->t('⚡ Generar usuarios para todos (@count)', ['@count' => $pending_count]),
          '#url' => Url::fromRoute('zinco_actors.generar_usuarios_masivo'),
          '#attributes' => [
            'class' => ['button', 'button--primary', 'me-2'],
            'onclick' => 'return confirm("¿Confirmas que deseas generar usuarios y enviar los correos de confirmación a todos los actores pendientes con correo válido?");',
          ],
        ];
      }
    }
    else {
      $btn_title = $pending_count > 0
        ? $this->t('⚠️ Actores sin usuario asociado (@count)', ['@count' => $pending_count])
        : $this->t('Actores sin usuario asociado');

      $action_buttons['ver_sin_usuario'] = [
        '#type' => 'link',
        '#title' => $btn_title,
        '#url' => Url::fromRoute('entity.zinco_actors_zincoactors.collection', [], ['query' => ['sin_usuario' => '1']]),
        '#attributes' => [
          'class' => ['button', $pending_count > 0 ? 'button--danger' : 'button--secondary', 'me-2'],
          'style' => $pending_count > 0 ? 'background-color: #d9534f; color: #fff; border-color: #d43f3a;' : '',
        ],
      ];
    }

    $build['actions_bar'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['zinco-actors-actions-bar', 'mb-3', 'd-flex', 'align-items-center']],
      'buttons' => $action_buttons,
    ];

    if ($is_filtered_sin_usuario) {
      $build['filter_notice'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['messages', 'messages--warning', 'mb-3'],
          'style' => 'padding: 12px 16px; background-color: #fff3cd; border: 1px solid #ffeeba; border-radius: 4px; color: #856404;',
        ],
        'text' => [
          '#markup' => $this->t('<strong>Mostrando únicamente actores sin usuario asociado.</strong> Puedes generar la cuenta individualmente desde la columna "Operaciones" o de forma masiva con el botón superior.'),
        ],
      ];
    }

    $build['filter_form'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['form-inline', 'mb-3']],
      'search_form' => [
        '#type' => 'html_tag',
        '#tag' => 'form',
        '#attributes' => [
          'method' => 'get',
          'action' => Url::fromRoute('entity.zinco_actors_zincoactors.collection')->toString(),
        ],
        'hidden_filter' => $is_filtered_sin_usuario ? [
          '#type' => 'html_tag',
          '#tag' => 'input',
          '#attributes' => [
            'type' => 'hidden',
            'name' => 'sin_usuario',
            'value' => '1',
          ],
        ] : [],
        'label' => [
          '#type' => 'textfield',
          '#name' => 'label',
          '#title' => $this->t('Buscar por nombre'),
          '#title_display' => 'invisible',
          '#default_value' => $search_label,
          '#size' => 40,
          '#attributes' => ['placeholder' => $this->t('Ingresa una palabra clave...')],
        ],
        'submit' => [
          '#type' => 'html_tag',
          '#tag' => 'input',
          '#attributes' => [
            'type' => 'submit',
            'value' => $this->t('Buscar'),
            'class' => ['button', 'button--primary'],
          ],
        ],
        'reset' => [
          '#type' => 'link',
          '#title' => $this->t('Limpiar'),
          '#url' => Url::fromRoute('entity.zinco_actors_zincoactors.collection', [], $is_filtered_sin_usuario ? ['query' => ['sin_usuario' => '1']] : []),
          '#attributes' => ['class' => ['button']],
        ],
      ],
    ];

    $build['table'] = parent::render();
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['id'] = $this->t('ID');
    $header['label'] = $this->t('Label');    
    $header['status'] = $this->t('Status');
    $header['bundle'] = $this->t('Tipo');
    $header['usuario_asociado'] = $this->t('Usuario asociado');
    $header['author'] = $this->t('Autor');
    $header['created'] = $this->t('Created');
    $header['changed'] = $this->t('Updated');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\zinco_actors\ZincoActorsInterface $entity */
    $row['id'] = $entity->id();
    $row['label'] = $entity->label() ? $entity->toLink() : 'NA';
    $row['status'] = $entity->get('status')->value ? $this->t('Enabled') : $this->t('Disabled');
    $row['bundle'] = $entity->bundle();

    // Check associated user via field_actor.
    $associated_user = $this->getAssociatedUser((int) $entity->id());
    if ($associated_user) {
      $row['usuario_asociado'] = [
        'data' => [
          '#type' => 'link',
          '#title' => '👤 ' . $associated_user->getAccountName(),
          '#url' => $associated_user->toUrl('canonical'),
          '#attributes' => ['class' => ['fw-bold', 'text-success']],
        ],
      ];
    }
    else {
      $email = $entity->hasField('email') && !$entity->get('email')->isEmpty() ? trim((string) $entity->get('email')->value) : '';
      if (!empty($email)) {
        $row['usuario_asociado'] = [
          'data' => [
            '#markup' => '<span style="display:inline-block; padding:3px 8px; border-radius:4px; font-size:12px; font-weight:600; background-color:#fff3cd; color:#856404; border:1px solid #ffeeba;">' . $this->t('Sin usuario') . '</span> <small style="color:#6c757d; display:block;">' . htmlspecialchars($email) . '</small>',
          ],
        ];
      }
      else {
        $row['usuario_asociado'] = [
          'data' => [
            '#markup' => '<span style="display:inline-block; padding:3px 8px; border-radius:4px; font-size:12px; font-weight:600; background-color:#f8d7da; color:#721c24; border:1px solid #f5c6cb;">' . $this->t('Sin email') . '</span>',
          ],
        ];
      }
    }

    $username_options = [
      'label' => 'hidden',
      'settings' => ['link' => $entity->get('uid')->entity && $entity->get('uid')->entity->isAuthenticated()],
    ];
    $row['author']['data'] = $entity->get('uid')->view($username_options);
    $row['created']['data'] = $entity->get('created')->view(['label' => 'hidden']);
    $row['changed']['data'] = $entity->get('changed')->view(['label' => 'hidden']);

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity): array {
    $operations = parent::getDefaultOperations($entity);

    $associated_user = $this->getAssociatedUser((int) $entity->id());
    if (!$associated_user) {
      $email = $entity->hasField('email') && !$entity->get('email')->isEmpty() ? trim((string) $entity->get('email')->value) : '';
      if (!empty($email)) {
        $operations['generar_usuario'] = [
          'title' => $this->t('✉️ Generar usuario y enviar correo'),
          'weight' => 5,
          'url' => Url::fromRoute('zinco_actors.generar_usuario', ['actor_id' => $entity->id()], [
            'query' => \Drupal::destination()->getAsArray(),
          ]),
        ];
      }
    }

    $operations['reconocimientos'] = [
      'title' => $this->t('Reconocimientos'),
      'weight' => 20,
      'url' => Url::fromRoute('zinco_front.reconocimientos_list', ['actor_id' => $entity->id()]),
    ];

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds(): array {
    $query = $this->getStorage()->getQuery()
      ->accessCheck(TRUE)
      ->sort('created', 'DESC');

    $search = \Drupal::request()->query->get('label');
    if (!empty($search)) {
      $query->condition('label', '%' . $search . '%', 'LIKE');
    }

    $sin_usuario = \Drupal::request()->query->get('sin_usuario');
    if ($sin_usuario === '1') {
      $assigned_actor_ids = $this->getAssignedActorIds();
      if (!empty($assigned_actor_ids)) {
        $query->condition('id', $assigned_actor_ids, 'NOT IN');
      }
    }

    if ($this->limit) {
      $query->pager($this->limit);
    }

    return $query->execute();
  }

  /**
   * Returns an array of actor IDs that are already associated with a user in field_actor.
   *
   * @return array
   */
  protected function getAssignedActorIds(): array {
    $user_query = \Drupal::entityQuery('user')
      ->condition('field_actor', NULL, 'IS NOT NULL')
      ->accessCheck(FALSE);
    $user_ids = $user_query->execute();

    $assigned = [];
    if (!empty($user_ids)) {
      $users = User::loadMultiple($user_ids);
      foreach ($users as $u) {
        if ($u->hasField('field_actor') && !$u->get('field_actor')->isEmpty()) {
          $actor_id = (int) $u->get('field_actor')->target_id;
          $assigned[] = $actor_id;
          $this->actorUserMap[$actor_id] = $u;
        }
      }
    }
    return array_unique($assigned);
  }

  /**
   * Returns the user associated with an actor ID if any.
   *
   * @param int $actor_id
   *   The actor ID.
   *
   * @return \Drupal\user\UserInterface|null
   */
  protected function getAssociatedUser(int $actor_id): ?\Drupal\user\UserInterface {
    if (isset($this->actorUserMap[$actor_id])) {
      return $this->actorUserMap[$actor_id];
    }

    $uids = \Drupal::entityQuery('user')
      ->condition('field_actor', $actor_id)
      ->accessCheck(FALSE)
      ->range(0, 1)
      ->execute();

    if (!empty($uids)) {
      $uid = reset($uids);
      $user = User::load($uid);
      if ($user) {
        $this->actorUserMap[$actor_id] = $user;
        return $user;
      }
    }

    return null;
  }

  /**
   * Counts actors without an associated user account.
   *
   * @return int
   */
  protected function getPendingUsersCount(): int {
    if ($this->pendingUsersCount !== null) {
      return $this->pendingUsersCount;
    }

    $assigned = $this->getAssignedActorIds();

    $query = $this->getStorage()->getQuery()
      ->accessCheck(FALSE);

    if (!empty($assigned)) {
      $query->condition('id', $assigned, 'NOT IN');
    }

    $this->pendingUsersCount = (int) $query->count()->execute();
    return $this->pendingUsersCount;
  }

}
