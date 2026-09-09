<?php

declare(strict_types=1);

namespace Drupal\zinco_retos_evaluacion;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a Evaluación de retos entity type.
 */
interface ZincoRetosEvaluacionInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface
{

}
