<?php

declare(strict_types=1);

namespace Drupal\zinco_producto_servicio;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a zinco producto servicio entity type.
 */
interface ZincoProductoServicioInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
