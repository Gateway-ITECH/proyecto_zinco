<?php

declare(strict_types=1);

namespace Drupal\zinco_ctei_actores;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a cteiactor entity type.
 */
interface CTEIActorInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
