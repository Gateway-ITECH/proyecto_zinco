<?php

declare(strict_types=1);

namespace Drupal\zinco_actors\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\user\Entity\User;

/**
 * Form for listing, filtering, and selecting actors for bulk actions.
 */
class ZincoActorsListForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'zinco_actors_list_form';
  }

  /**
   * Obtains all actor IDs that already have a user in field_actor.
   *
   * @return array
   */
  public static function getAssignedActorIds(): array {
    $user_query = \Drupal::entityQuery('user')
      ->condition('field_actor', NULL, 'IS NOT NULL')
      ->accessCheck(FALSE);
    $user_ids = $user_query->execute();

    $assigned = [];
    if (!empty($user_ids)) {
      $users = User::loadMultiple($user_ids);
      foreach ($users as $u) {
        if ($u->hasField('field_actor') && !$u->get('field_actor')->isEmpty()) {
          foreach ($u->get('field_actor')->getValue() as $item) {
            if (!empty($item['target_id'])) {
              $assigned[] = (int) $item['target_id'];
            }
          }
        }
      }
    }
    return array_unique($assigned);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $request = $this->getRequest();
    $search_label = trim((string) $request->query->get('label', ''));
    $estado_usuario = trim((string) $request->query->get('estado_usuario', ''));

    // Support backward compatibility with ?sin_usuario=1.
    if (empty($estado_usuario) && $request->query->get('sin_usuario') === '1') {
      $estado_usuario = 'sin_usuario';
    }

    $assigned_actor_ids = self::getAssignedActorIds();

    // Query total count of actors without user for stats.
    $storage = $this->entityTypeManager->getStorage('zinco_actors_zincoactors');
    $pending_total_query = $storage->getQuery()->accessCheck(FALSE);
    if (!empty($assigned_actor_ids)) {
      $pending_total_query->condition('id', $assigned_actor_ids, 'NOT IN');
    }
    $total_sin_usuario = (int) $pending_total_query->count()->execute();

    // 1. Filter section.
    $form['filters'] = [
      '#type' => 'details',
      '#title' => $this->t('🔍 Filtros de búsqueda'),
      '#open' => TRUE,
      '#attributes' => ['class' => ['mb-3']],
    ];

    $form['filters']['container'] = [
      '#type' => 'container',
      '#attributes' => ['style' => 'display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap; margin-bottom: 10px;'],
    ];

    $form['filters']['container']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Nombre o razón social'),
      '#default_value' => $search_label,
      '#size' => 30,
      '#attributes' => ['placeholder' => $this->t('Buscar por nombre...')],
    ];

    $form['filters']['container']['estado_usuario'] = [
      '#type' => 'select',
      '#title' => $this->t('Estado de usuario'),
      '#options' => [
        '' => $this->t('- Todos los actores -'),
        'sin_usuario' => $this->t('⚠️ Sin usuario asociado (@count)', ['@count' => $total_sin_usuario]),
        'con_usuario' => $this->t('👤 Con usuario asociado'),
      ],
      '#default_value' => $estado_usuario,
    ];

    $form['filters']['container']['actions'] = [
      '#type' => 'container',
      '#attributes' => ['style' => 'display: flex; gap: 8px; align-items: center;'],
    ];

    $form['filters']['container']['actions']['filter_submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Filtrar'),
      '#name' => 'op_filter',
      '#submit' => ['::submitFilter'],
      '#attributes' => ['class' => ['button', 'button--primary']],
    ];

    if (!empty($search_label) || !empty($estado_usuario)) {
      $form['filters']['container']['actions']['filter_reset'] = [
        '#type' => 'link',
        '#title' => $this->t('Limpiar filtros'),
        '#url' => Url::fromRoute('entity.zinco_actors_zincoactors.collection'),
        '#attributes' => ['class' => ['button', 'button--secondary']],
      ];
    }

    // 2. Build Query for table.
    $query = $storage->getQuery()
      ->accessCheck(TRUE)
      ->sort('created', 'DESC');

    if (!empty($search_label)) {
      $query->condition('label', '%' . $search_label . '%', 'LIKE');
    }

    if ($estado_usuario === 'sin_usuario') {
      if (!empty($assigned_actor_ids)) {
        $query->condition('id', $assigned_actor_ids, 'NOT IN');
      }
    }
    elseif ($estado_usuario === 'con_usuario') {
      if (!empty($assigned_actor_ids)) {
        $query->condition('id', $assigned_actor_ids, 'IN');
      }
      else {
        $query->condition('id', 0);
      }
    }

    $query->pager(50);
    $actor_ids = $query->execute();

    // 3. Action bar above the table.
    $form['actions_bar'] = [
      '#type' => 'container',
      '#attributes' => [
        'style' => 'display: flex; justify-content: space-between; align-items: center; background: #f8f9fa; padding: 12px 16px; border-radius: 6px; border: 1px solid #e9ecef; margin-bottom: 15px;',
      ],
    ];

    $form['actions_bar']['info'] = [
      '#markup' => $this->t(
        '<span>Selecciona los actores con las casillas de verificación para generar o notificar usuarios.</span> <span style="margin-left: 10px; font-weight: bold; color: #856404;">(Pendientes sin usuario en el sistema: @count)</span>',
        ['@count' => $total_sin_usuario]
      ),
    ];

    $form['actions_bar']['submit_bulk'] = [
      '#type' => 'submit',
      '#value' => $this->t('⚡ Generar / Enviar usuarios a seleccionados'),
      '#name' => 'op_bulk_users',
      '#submit' => ['::submitBulkUsers'],
      '#attributes' => [
        'class' => ['button', 'button--primary'],
        'style' => 'background-color: #007bff; border-color: #007bff; color: #fff; font-weight: 600;',
      ],
    ];

    // 4. Table header.
    $header = [
      'id' => $this->t('ID'),
      'label' => $this->t('Nombre / Razón Social'),
      'bundle' => $this->t('Tipo'),
      'usuario' => $this->t('Usuario asociado'),
      'author' => $this->t('Autor'),
      'created' => $this->t('Creado'),
      'changed' => $this->t('Actualizado'),
      'operations' => $this->t('Operaciones'),
    ];

    $options = [];
    $date_formatter = \Drupal::service('date.formatter');
    $list_builder = $this->entityTypeManager->getListBuilder('zinco_actors_zincoactors');

    // Preload users for performance.
    $actor_user_map = [];
    if (!empty($actor_ids)) {
      $uids_query = \Drupal::entityQuery('user')
        ->condition('field_actor', $actor_ids, 'IN')
        ->accessCheck(FALSE)
        ->execute();
      if (!empty($uids_query)) {
        $users = User::loadMultiple($uids_query);
        foreach ($users as $u) {
          if ($u->hasField('field_actor') && !$u->get('field_actor')->isEmpty()) {
            foreach ($u->get('field_actor')->getValue() as $item) {
              if (!empty($item['target_id'])) {
                $actor_user_map[(int) $item['target_id']] = $u;
              }
            }
          }
        }
      }
    }

    if (!empty($actor_ids)) {
      $actors = $storage->loadMultiple($actor_ids);
      foreach ($actors as $actor) {
        $actor_id = (int) $actor->id();
        $email = $actor->hasField('email') && !$actor->get('email')->isEmpty() ? trim((string) $actor->get('email')->value) : '';
        $associated_user = $actor_user_map[$actor_id] ?? NULL;

        if ($associated_user) {
          $user_col = [
            'data' => [
              '#type' => 'link',
              '#title' => '👤 ' . $associated_user->getAccountName(),
              '#url' => $associated_user->toUrl('canonical'),
              '#attributes' => ['class' => ['fw-bold', 'text-success']],
            ],
          ];
        }
        elseif (!empty($email)) {
          $user_col = [
            'data' => [
              '#markup' => '<span style="display:inline-block; padding:2px 8px; border-radius:4px; font-size:12px; font-weight:600; background-color:#fff3cd; color:#856404; border:1px solid #ffeeba;">' . $this->t('Sin usuario') . '</span> <small style="color:#6c757d; display:block;">' . htmlspecialchars($email) . '</small>',
            ],
          ];
        }
        else {
          $user_col = [
            'data' => [
              '#markup' => '<span style="display:inline-block; padding:2px 8px; border-radius:4px; font-size:12px; font-weight:600; background-color:#f8d7da; color:#721c24; border:1px solid #f5c6cb;">' . $this->t('Sin email') . '</span>',
            ],
          ];
        }

        $owner = $actor->getOwner();
        $author_display = $owner ? $owner->getDisplayName() : 'NA';

        $operations = $list_builder->getOperations($actor);

        $options[$actor_id] = [
          'id' => $actor_id,
          'label' => [
            'data' => [
              '#type' => 'link',
              '#title' => $actor->label() ?: 'NA',
              '#url' => $actor->toUrl('canonical'),
            ],
          ],
          'bundle' => $actor->bundle(),
          'usuario' => $user_col,
          'author' => $author_display,
          'created' => $date_formatter->format((int) $actor->get('created')->value, 'short'),
          'changed' => $date_formatter->format((int) $actor->get('changed')->value, 'short'),
          'operations' => [
            'data' => [
              '#type' => 'operations',
              '#links' => $operations,
            ],
          ],
        ];
      }
    }

    $form['actores_table'] = [
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $options,
      '#empty' => $this->t('No se encontraron actores con los filtros seleccionados.'),
    ];

    $form['pager'] = [
      '#type' => 'pager',
    ];

    return $form;
  }

  /**
   * Submit handler for filtering.
   */
  public function submitFilter(array &$form, FormStateInterface $form_state): void {
    $label = trim((string) $form_state->getValue('label'));
    $estado_usuario = trim((string) $form_state->getValue('estado_usuario'));

    $query = [];
    if (!empty($label)) {
      $query['label'] = $label;
    }
    if (!empty($estado_usuario)) {
      $query['estado_usuario'] = $estado_usuario;
    }

    $form_state->setRedirect('entity.zinco_actors_zincoactors.collection', [], ['query' => $query]);
  }

  /**
   * Submit handler for bulk user generation on selected actors.
   */
  public function submitBulkUsers(array &$form, FormStateInterface $form_state): void {
    $selected = $form_state->getValue('actores_table') ?? [];
    $selected_ids = array_values(array_filter($selected));

    if (empty($selected_ids)) {
      $this->messenger()->addWarning($this->t('Debes marcar las casillas de verificación de al menos un actor en la tabla antes de continuar.'));
      return;
    }

    // Save in private tempstore for reliability.
    try {
      \Drupal::service('tempstore.private')->get('zinco_actors')->set('selected_actor_ids', $selected_ids);
    }
    catch (\Throwable $e) {
      // Tempstore fallback.
    }

    // Redirect to the confirmation form with the selected IDs.
    $form_state->setRedirect('zinco_actors.generar_usuarios_masivo', [], [
      'query' => ['selected_ids' => implode(',', $selected_ids)],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // Default submit calls the bulk action handler.
    $this->submitBulkUsers($form, $form_state);
  }

}