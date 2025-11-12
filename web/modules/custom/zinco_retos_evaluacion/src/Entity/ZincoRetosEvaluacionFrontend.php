<?php

namespace Drupal\zinco_retos_evaluacion\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the Zinco Retos Evaluacion Frontend entity.
 *
 * @ingroup zinco_retos_evaluacion
 */
class ZincoRetosEvaluacionFrontend extends ZincoRetosEvaluacion {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a new ZincoRetosEvaluacionFrontend object.
   *
   * @param array $values
   *   An array of entity values.
   * @param string $entity_type
   *   The entity type ID.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   */
  public function __construct(array $values, $entity_type, AccountProxyInterface $current_user) {
    parent::__construct($values, $entity_type);
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, array $values, $entity_type) {
    return new static(
      $values,
      $entity_type,
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function save() {
    // Add custom logic before saving the entity.
    // For example, set the author to the current user if not already set.
    if (!$this->get('uid')->target_id) {
      $this->set('uid', $this->currentUser->id());
    }
    // Set the creation date if not already set.
    if (!$this->get('created')->value) {
      $this->set('created', \Drupal::time()->getRequestTime());
    }
    // Set the changed date to the current time.
    $this->set('changed', \Drupal::time()->getRequestTime());

    // Call the parent save method.
    return parent::save();
  }

}