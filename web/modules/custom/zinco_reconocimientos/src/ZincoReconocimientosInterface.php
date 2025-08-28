<?php

declare(strict_types=1);

namespace Drupal\zinco_reconocimientos;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a zinco reconocimientos entity type.
 */
interface ZincoReconocimientosInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
