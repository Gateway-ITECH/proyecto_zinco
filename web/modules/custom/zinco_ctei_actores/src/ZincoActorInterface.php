<?php

declare(strict_types=1);

namespace Drupal\zinco_ctei_actores;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a zincoactor entity type.
 */
interface ZincoActorInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
