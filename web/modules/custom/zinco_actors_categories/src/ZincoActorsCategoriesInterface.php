<?php

declare(strict_types=1);

namespace Drupal\zinco_actors_categories;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a zinco actors categories entity type.
 */
interface ZincoActorsCategoriesInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
