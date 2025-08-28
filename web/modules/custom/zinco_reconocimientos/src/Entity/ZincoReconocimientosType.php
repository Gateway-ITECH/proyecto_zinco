<?php

declare(strict_types=1);

namespace Drupal\zinco_reconocimientos\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Entity\Attribute\ConfigEntityType;
use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\zinco_reconocimientos\Form\ZincoReconocimientosTypeForm;
use Drupal\zinco_reconocimientos\ZincoReconocimientosTypeListBuilder;

/**
 * Defines the Zinco reconocimientos type configuration entity.
 */
#[ConfigEntityType(
  id: 'zinco_reconocimientos_type',
  label: new TranslatableMarkup('Zinco reconocimientos type'),
  label_collection: new TranslatableMarkup('Zinco reconocimientos types'),
  label_singular: new TranslatableMarkup('zinco reconocimientos type'),
  label_plural: new TranslatableMarkup('zinco reconocimientoss types'),
  config_prefix: 'zinco_reconocimientos_type',
  entity_keys: [
    'id' => 'id',
    'label' => 'label',
    'uuid' => 'uuid',
  ],
  handlers: [
    'list_builder' => ZincoReconocimientosTypeListBuilder::class,
    'route_provider' => [
      'html' => AdminHtmlRouteProvider::class,
    ],
    'form' => [
      'add' => ZincoReconocimientosTypeForm::class,
      'edit' => ZincoReconocimientosTypeForm::class,
      'delete' => EntityDeleteForm::class,
    ],
  ],
  links: [
    'add-form' => '/admin/structure/zinco_reconocimientos_types/add',
    'edit-form' => '/admin/structure/zinco_reconocimientos_types/manage/{zinco_reconocimientos_type}',
    'delete-form' => '/admin/structure/zinco_reconocimientos_types/manage/{zinco_reconocimientos_type}/delete',
    'collection' => '/admin/structure/zinco_reconocimientos_types',
  ],
  admin_permission: 'administer zinco_reconocimientos types',
  bundle_of: 'zinco_reconocimientos',
  label_count: [
    'singular' => '@count zinco reconocimientos type',
    'plural' => '@count zinco reconocimientoss types',
  ],
  config_export: [
    'id',
    'label',
    'uuid',
  ],
)]
final class ZincoReconocimientosType extends ConfigEntityBundleBase {

  /**
   * The machine name of this zinco reconocimientos type.
   */
  protected string $id;

  /**
   * The human-readable name of the zinco reconocimientos type.
   */
  protected string $label;

}
