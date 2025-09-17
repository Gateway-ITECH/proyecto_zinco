<?php

declare(strict_types=1);

namespace Drupal\zinco_actors;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a zincoactors entity type.
 */
interface ZincoActorsInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
