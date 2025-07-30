<?php

declare(strict_types=1);

namespace Drupal\zinco_ctei_actores\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\Attribute\ConfigEntityType;
use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\zinco_ctei_actores\ActoresInterface;
use Drupal\zinco_ctei_actores\ActoresListBuilder;
use Drupal\zinco_ctei_actores\Form\ActoresForm;

/**
 * Defines the actores entity type.
 */
#[ConfigEntityType(
  id: 'actores',
  label: new TranslatableMarkup('Actores'),
  label_collection: new TranslatableMarkup('Actoress'),
  label_singular: new TranslatableMarkup('actores'),
  label_plural: new TranslatableMarkup('actoress'),
  config_prefix: 'actores',
  entity_keys: [
    'id' => 'id',
    'label' => 'label',
    'uuid' => 'uuid',
  ],
  handlers: [
    'list_builder' => ActoresListBuilder::class,
    'form' => [
      'add' => ActoresForm::class,
      'edit' => ActoresForm::class,
      'delete' => EntityDeleteForm::class,
    ],
  ],
  links: [
    'collection' => '/admin/structure/actores',
    'add-form' => '/admin/structure/actores/add',
    'edit-form' => '/admin/structure/actores/{actores}',
    'delete-form' => '/admin/structure/actores/{actores}/delete',
  ],
  admin_permission: 'administer actores',
  label_count: [
    'singular' => '@count actores',
    'plural' => '@count actoress',
  ],
  config_export: [
    'id',
    'label',
    'description',
  ],
)]
final class Actores extends ConfigEntityBase implements ActoresInterface {

  /**
   * The example ID.
   */
  protected string $id;

  /**
   * The example label.
   */
  protected string $label;

  /**
   * The example description.
   */
  protected string $description;

}
