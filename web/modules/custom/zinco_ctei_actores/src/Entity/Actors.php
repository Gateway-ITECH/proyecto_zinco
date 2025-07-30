<?php

declare(strict_types=1);

namespace Drupal\zinco_ctei_actores\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\Attribute\ConfigEntityType;
use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\zinco_ctei_actores\ActorsInterface;
use Drupal\zinco_ctei_actores\ActorsListBuilder;
use Drupal\zinco_ctei_actores\Form\ActorsForm;

/**
 * Defines the actors entity type.
 */
#[ConfigEntityType(
  id: 'actors',
  label: new TranslatableMarkup('Actors'),
  label_collection: new TranslatableMarkup('Actorss'),
  label_singular: new TranslatableMarkup('actors'),
  label_plural: new TranslatableMarkup('actorss'),
  config_prefix: 'actors',
  entity_keys: [
    'id' => 'id',
    'label' => 'label',
    'uuid' => 'uuid',
  ],
  handlers: [
    'list_builder' => ActorsListBuilder::class,
    'form' => [
      'add' => ActorsForm::class,
      'edit' => ActorsForm::class,
      'delete' => EntityDeleteForm::class,
    ],
  ],
  links: [
    'collection' => '/admin/structure/actors',
    'add-form' => '/admin/structure/actors/add',
    'edit-form' => '/admin/structure/actors/{actors}',
    'delete-form' => '/admin/structure/actors/{actors}/delete',
  ],
  admin_permission: 'administer actors',
  label_count: [
    'singular' => '@count actors',
    'plural' => '@count actorss',
  ],
  config_export: [
    'id',
    'label',
    'description',
  ],
)]
final class Actors extends ConfigEntityBase implements ActorsInterface {

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
