<?php

declare(strict_types=1);

namespace Drupal\zinco_actors;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Drupal\zinco_actors\Form\ZincoActorsListForm;

/**
 * Provides a list controller for the zincoactors entity type.
 */
class ZincoActorsListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function render() {
    return \Drupal::formBuilder()->getForm(ZincoActorsListForm::class);
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
   * Returns the user associated with an actor ID if any.
   *
   * @param int $actor_id
   *   The actor ID.
   *
   * @return \Drupal\user\UserInterface|null
   */
  protected function getAssociatedUser(int $actor_id): ?\Drupal\user\UserInterface {
    $uids = \Drupal::entityQuery('user')
      ->condition('field_actor', $actor_id)
      ->accessCheck(FALSE)
      ->range(0, 1)
      ->execute();

    if (!empty($uids)) {
      $uid = reset($uids);
      return User::load($uid);
    }

    return NULL;
  }

}