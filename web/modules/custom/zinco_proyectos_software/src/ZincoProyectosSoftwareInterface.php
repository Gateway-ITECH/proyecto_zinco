<?php

declare(strict_types=1);

namespace Drupal\zinco_proyectos_software;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a zinco proyectos software entity type.
 */
interface ZincoProyectosSoftwareInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
