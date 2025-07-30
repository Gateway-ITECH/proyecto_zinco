<?php

declare(strict_types=1);

namespace Drupal\zinco_ctei_actores\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Entity\Attribute\ConfigEntityType;
use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\zinco_ctei_actores\CTEIActorTypeListBuilder;
use Drupal\zinco_ctei_actores\Form\CTEIActorTypeForm;

/**
 * Defines the CTEIActor type configuration entity.
 */
#[ConfigEntityType(
  id: 'cteiactor_type',
  label: new TranslatableMarkup('CTEIActor type'),
  label_collection: new TranslatableMarkup('CTEIActor types'),
  label_singular: new TranslatableMarkup('cteiactor type'),
  label_plural: new TranslatableMarkup('cteiactors types'),
  config_prefix: 'cteiactor_type',
  entity_keys: [
    'id' => 'id',
    'label' => 'label',
    'uuid' => 'uuid',
  ],
  handlers: [
    'list_builder' => CTEIActorTypeListBuilder::class,
    'route_provider' => [
      'html' => AdminHtmlRouteProvider::class,
    ],
    'form' => [
      'add' => CTEIActorTypeForm::class,
      'edit' => CTEIActorTypeForm::class,
      'delete' => EntityDeleteForm::class,
    ],
  ],
  links: [
    'add-form' => '/admin/structure/cteiactor_types/add',
    'edit-form' => '/admin/structure/cteiactor_types/manage/{cteiactor_type}',
    'delete-form' => '/admin/structure/cteiactor_types/manage/{cteiactor_type}/delete',
    'collection' => '/admin/structure/cteiactor_types',
  ],
  admin_permission: 'administer cteiactor types',
  bundle_of: 'cteiactor',
  label_count: [
    'singular' => '@count cteiactor type',
    'plural' => '@count cteiactors types',
  ],
  config_export: [
    'id',
    'label',
    'uuid',
  ],
)]
final class CTEIActorType extends ConfigEntityBundleBase {

  /**
   * The machine name of this cteiactor type.
   */
  protected string $id;

  /**
   * The human-readable name of the cteiactor type.
   */
  protected string $label;

}
