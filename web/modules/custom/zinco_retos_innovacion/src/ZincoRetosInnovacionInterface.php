<?php

declare(strict_types=1);

namespace Drupal\zinco_retos_innovacion;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a zinco retos innovacion entity type.
 */
interface ZincoRetosInnovacionInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
