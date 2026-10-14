<?php

namespace Drupal\zinco_reconocimientos\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Controller for actions on Zinco reconocimientos.
 */
class ZincoReconocimientosActionController extends ControllerBase {

  /**
   * Sends an email to the user with access to register a new actor.
   *
   * @param int $reconocimiento_id
   *   The recognition entity ID.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect response.
   */
  public function enviarAccesoNuevoActor($reconocimiento_id) {
    $current_user = \Drupal::currentUser();
    if (!$current_user->hasPermission('administer zinco_reconocimientos types') && !in_array('administrator', $current_user->getRoles(), TRUE)) {
      throw new AccessDeniedHttpException();
    }

    $reconocimiento = $this->entityTypeManager()->getStorage('zinco_reconocimientos')->load($reconocimiento_id);
    if (!$reconocimiento) {
      throw new NotFoundHttpException();
    }

    $check_uso = self::isReconocimientoEnlaceUsado($reconocimiento);
    if ($check_uso['usado']) {
      $this->messenger()->addWarning($this->t('Esta solicitud de reconocimiento ya fue utilizada para registrar el nuevo actor. No es necesario enviar el enlace de registro nuevamente.'));
      return $this->redirect('zinco_front.reconocimiento_detail', ['reconocimiento_id' => $reconocimiento_id]);
    }

    $owner = $reconocimiento->getOwner();
    $target_email = '';
    $user_name = '';

    if ($owner && $owner->isAuthenticated()) {
      $target_email = $owner->getEmail();
      $user_name = $owner->getDisplayName();
    }

    // Fallback: If owner is missing, check associated actor email.
    if (empty($target_email) && $reconocimiento->hasField('field_actor_asociado') && !$reconocimiento->get('field_actor_asociado')->isEmpty()) {
      $actor = $reconocimiento->get('field_actor_asociado')->entity;
      if ($actor && $actor->hasField('email') && !$actor->get('email')->isEmpty()) {
        $target_email = $actor->get('email')->value;
        $user_name = $actor->label();
      }
    }

    if (empty($target_email)) {
      $this->messenger()->addError($this->t('No se encontró un correo electrónico válido para enviar el acceso (el usuario u actor no tienen correo registrado).'));
      return $this->redirect('zinco_front.reconocimiento_detail', ['reconocimiento_id' => $reconocimiento_id]);
    }

    // Extract requested bundle
    $bundle_info = \Drupal::service('entity_type.bundle.info')->getBundleInfo('zinco_actors_zincoactors');
    $tipo_actor_bundle = '';
    $tipo_actor_label = 'Nuevo Actor';

    if ($reconocimiento->hasField('description') && !$reconocimiento->get('description')->isEmpty()) {
      $meta = json_decode($reconocimiento->get('description')->value, TRUE);
      if (!empty($meta['tipo_actor_bundle'])) {
        $tipo_actor_bundle = $meta['tipo_actor_bundle'];
        $tipo_actor_label = $meta['tipo_actor_label'] ?? ($bundle_info[$tipo_actor_bundle]['label'] ?? $tipo_actor_bundle);
      }
    }

    // Build the registration URL
    if ($tipo_actor_bundle && isset($bundle_info[$tipo_actor_bundle])) {
      $registro_url = Url::fromRoute('zinco_front.actor_add_by_bundle', ['bundle' => $tipo_actor_bundle], ['query' => ['reconocimiento_id' => $reconocimiento_id], 'absolute' => TRUE])->toString();
    }
    else {
      $registro_url = Url::fromRoute('zinco_front.actor_categories_list', [], ['query' => ['reconocimiento_id' => $reconocimiento_id], 'absolute' => TRUE])->toString();
    }

    $actor_name = 'Actor';
    if ($reconocimiento->hasField('field_actor_asociado') && !$reconocimiento->get('field_actor_asociado')->isEmpty()) {
      $actor_entity = $reconocimiento->get('field_actor_asociado')->entity;
      if ($actor_entity) {
        $actor_name = $actor_entity->label();
      }
    }

    $variables = [
      'user_name' => $user_name,
      'user_email' => $target_email,
      'actor_name' => $actor_name,
      'tipo_actor_solicitado' => $tipo_actor_label,
      'registro_url' => $registro_url,
      'detail_url' => Url::fromRoute('zinco_front.reconocimiento_detail', ['reconocimiento_id' => $reconocimiento_id], ['absolute' => TRUE])->toString(),
    ];

    $mail_service = \Drupal::service('zinco_front.mail_service');
    $mail_result = $mail_service->sendTemplatedEmail(
      $target_email,
      $this->t('Acceso para agregar nuevo actor (@tipo) en ZINCO', ['@tipo' => $tipo_actor_label]),
      'email_reconocimiento_acceso_actor',
      $variables
    );

    if ($mail_result['result']) {
      $this->messenger()->addStatus($this->t('Se ha enviado exitosamente el correo de acceso a @email con el enlace para registrar el nuevo actor de tipo "@tipo".', [
        '@email' => $target_email,
        '@tipo' => $tipo_actor_label,
      ]));
      $this->getLogger('zinco_reconocimientos')->notice('Enviado correo de acceso para nuevo actor a @email para la solicitud @id.', [
        '@email' => $target_email,
        '@id' => $reconocimiento_id,
      ]);
    }
    else {
      $this->messenger()->addWarning($this->t('Hubo un problema al enviar el correo a @email. Por favor, verifica la configuración del servidor de correo.', [
        '@email' => $target_email,
      ]));
    }

    $referer = \Drupal::request()->headers->get('referer');
    if ($referer && str_contains($referer, 'admin/content/zinco-reconocimientos')) {
      return $this->redirect('entity.zinco_reconocimientos.collection');
    }

    return $this->redirect('zinco_front.reconocimiento_detail', ['reconocimiento_id' => $reconocimiento_id]);
  }

  /**
   * Checks if a recognition registration link has already been used.
   *
   * @param \Drupal\Core\Entity\EntityInterface|int $reconocimiento
   *   The recognition entity or ID.
   *
   * @return array
   *   Array with 'usado' (bool) and 'actor_id' (int|null).
   */
  public static function isReconocimientoEnlaceUsado($reconocimiento): array {
    if (is_numeric($reconocimiento)) {
      $reconocimiento = \Drupal::entityTypeManager()->getStorage('zinco_reconocimientos')->load($reconocimiento);
    }
    if (!$reconocimiento) {
      return ['usado' => FALSE, 'actor_id' => NULL];
    }

    $rec_id = (int) $reconocimiento->id();
    $meta = [];
    if ($reconocimiento->hasField('description') && !$reconocimiento->get('description')->isEmpty()) {
      $meta = json_decode($reconocimiento->get('description')->value, TRUE) ?: [];
    }

    // 1. Explicitly recorded in description meta.
    if (!empty($meta['actor_creado_id']) || !empty($meta['enlace_usado'])) {
      return [
        'usado' => TRUE,
        'actor_id' => !empty($meta['actor_creado_id']) ? (int) $meta['actor_creado_id'] : NULL,
      ];
    }

    // 2. Explicitly recorded in Drupal State.
    $state_actor_id = \Drupal::state()->get("zinco_reconocimiento_used_{$rec_id}");
    if (!empty($state_actor_id)) {
      return [
        'usado' => TRUE,
        'actor_id' => (int) $state_actor_id,
      ];
    }

    // 3. Fallback: check if the owner has already registered an actor of the requested bundle after the recognition was created.
    $owner_uid = $reconocimiento->getOwnerId();
    $tipo_bundle = $meta['tipo_actor_bundle'] ?? '';
    if (!empty($owner_uid) && !empty($tipo_bundle)) {
      $created_time = (int) $reconocimiento->getCreatedTime();
      $storage = \Drupal::entityTypeManager()->getStorage('zinco_actors_zincoactors');
      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('uid', $owner_uid)
        ->condition('bundle', $tipo_bundle)
        ->condition('created', $created_time, '>=')
        ->sort('created', 'DESC')
        ->range(0, 1);
      $found_ids = $query->execute();
      if (!empty($found_ids)) {
        $found_id = (int) reset($found_ids);
        \Drupal::state()->set("zinco_reconocimiento_used_{$rec_id}", $found_id);
        return [
          'usado' => TRUE,
          'actor_id' => $found_id,
        ];
      }
    }

    return ['usado' => FALSE, 'actor_id' => NULL];
  }

  /**
   * Marks a recognition link as used by the given actor entity.
   *
   * @param int $rec_id
   *   The recognition ID.
   * @param \Drupal\Core\Entity\EntityInterface $actor
   *   The created actor entity.
   */
  public static function markReconocimientoEnlaceUsado(int $rec_id, $actor): void {
    if (!$rec_id || !$actor) {
      return;
    }

    \Drupal::state()->set("zinco_reconocimiento_used_{$rec_id}", (int) $actor->id());

    $rec = \Drupal::entityTypeManager()->getStorage('zinco_reconocimientos')->load($rec_id);
    if ($rec) {
      $meta = [];
      if ($rec->hasField('description') && !$rec->get('description')->isEmpty()) {
        $meta = json_decode($rec->get('description')->value, TRUE) ?: [];
      }
      $meta['actor_creado_id'] = (int) $actor->id();
      $meta['actor_creado_bundle'] = $actor->bundle();
      $meta['actor_creado_label'] = $actor->label();
      $meta['actor_creado_timestamp'] = time();
      $meta['enlace_usado'] = TRUE;
      if ($rec->hasField('description')) {
        $rec->set('description', json_encode($meta, JSON_UNESCAPED_UNICODE));
      }
      $rec->save();
    }
  }

}
