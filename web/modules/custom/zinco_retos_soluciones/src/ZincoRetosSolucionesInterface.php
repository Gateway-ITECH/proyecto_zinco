<?php

declare(strict_types=1);

namespace Drupal\zinco_retos_soluciones;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a zinco retos soluciones entity type.
 */
interface ZincoRetosSolucionesInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
