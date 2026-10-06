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
      $registro_url = Url::fromRoute('zinco_front.actor_add_by_bundle', ['bundle' => $tipo_actor_bundle], ['absolute' => TRUE])->toString();
    }
    else {
      $registro_url = Url::fromRoute('zinco_front.actor_categories_list', [], ['absolute' => TRUE])->toString();
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

}
