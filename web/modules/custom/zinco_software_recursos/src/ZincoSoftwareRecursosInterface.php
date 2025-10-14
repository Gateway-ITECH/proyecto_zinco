<?php

declare(strict_types=1);

namespace Drupal\zinco_software_recursos;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a zinco software recursos entity type.
 */
interface ZincoSoftwareRecursosInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
