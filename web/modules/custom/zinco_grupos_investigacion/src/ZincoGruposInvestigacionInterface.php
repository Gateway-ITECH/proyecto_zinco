<?php

declare(strict_types=1);

namespace Drupal\zinco_grupos_investigacion;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a zinco grupos investigacion entity type.
 */
interface ZincoGruposInvestigacionInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
