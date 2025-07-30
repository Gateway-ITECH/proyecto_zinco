<?php

declare(strict_types=1);

namespace Drupal\zinco_ctei_actores\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\Attribute\ConfigEntityType;
use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\zinco_ctei_actores\CteiactorInterface;
use Drupal\zinco_ctei_actores\CteiactorListBuilder;
use Drupal\zinco_ctei_actores\Form\CteiactorForm;

/**
 * Defines the cteiactor entity type.
 */
#[ConfigEntityType(
  id: 'cteiactor',
  label: new TranslatableMarkup('CTEIActor'),
  label_collection: new TranslatableMarkup('CTEIActors'),
  label_singular: new TranslatableMarkup('cteiactor'),
  label_plural: new TranslatableMarkup('cteiactors'),
  config_prefix: 'cteiactor',
  entity_keys: [
    'id' => 'id',
    'label' => 'label',
    'uuid' => 'uuid',
  ],
  handlers: [
    'list_builder' => CteiactorListBuilder::class,
    'form' => [
      'add' => CteiactorForm::class,
      'edit' => CteiactorForm::class,
      'delete' => EntityDeleteForm::class,
    ],
  ],
  links: [
    'collection' => '/admin/structure/cteiactor',
    'add-form' => '/admin/structure/cteiactor/add',
    'edit-form' => '/admin/structure/cteiactor/{cteiactor}',
    'delete-form' => '/admin/structure/cteiactor/{cteiactor}/delete',
  ],
  admin_permission: 'administer cteiactor',
  label_count: [
    'singular' => '@count cteiactor',
    'plural' => '@count cteiactors',
  ],
  config_export: [
    'id',
    'label',
    'description',
  ],
)]
final class Cteiactor extends ConfigEntityBase implements CteiactorInterface {

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
