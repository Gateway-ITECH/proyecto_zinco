<?php

declare(strict_types=1);

namespace Drupal\zinco_proyectos_idi;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a zinco proyectos idi entity type.
 */
interface ZincoProyectosIdiInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
