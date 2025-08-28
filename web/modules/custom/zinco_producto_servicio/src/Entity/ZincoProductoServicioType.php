<?php

declare(strict_types=1);

namespace Drupal\zinco_producto_servicio\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Entity\Attribute\ConfigEntityType;
use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\zinco_producto_servicio\Form\ZincoProductoServicioTypeForm;
use Drupal\zinco_producto_servicio\ZincoProductoServicioTypeListBuilder;

/**
 * Defines the Zinco producto servicio type configuration entity.
 */
#[ConfigEntityType(
  id: 'zinco_producto_servicio_type',
  label: new TranslatableMarkup('Zinco producto servicio type'),
  label_collection: new TranslatableMarkup('Zinco producto servicio types'),
  label_singular: new TranslatableMarkup('zinco producto servicio type'),
  label_plural: new TranslatableMarkup('zinco producto servicios types'),
  config_prefix: 'zinco_producto_servicio_type',
  entity_keys: [
    'id' => 'id',
    'label' => 'label',
    'uuid' => 'uuid',
  ],
  handlers: [
    'list_builder' => ZincoProductoServicioTypeListBuilder::class,
    'route_provider' => [
      'html' => AdminHtmlRouteProvider::class,
    ],
    'form' => [
      'add' => ZincoProductoServicioTypeForm::class,
      'edit' => ZincoProductoServicioTypeForm::class,
      'delete' => EntityDeleteForm::class,
    ],
  ],
  links: [
    'add-form' => '/admin/structure/zinco_producto_servicio_types/add',
    'edit-form' => '/admin/structure/zinco_producto_servicio_types/manage/{zinco_producto_servicio_type}',
    'delete-form' => '/admin/structure/zinco_producto_servicio_types/manage/{zinco_producto_servicio_type}/delete',
    'collection' => '/admin/structure/zinco_producto_servicio_types',
  ],
  admin_permission: 'administer zinco_producto_servicio types',
  bundle_of: 'zinco_producto_servicio',
  label_count: [
    'singular' => '@count zinco producto servicio type',
    'plural' => '@count zinco producto servicios types',
  ],
  config_export: [
    'id',
    'label',
    'uuid',
  ],
)]
final class ZincoProductoServicioType extends ConfigEntityBundleBase {

  /**
   * The machine name of this zinco producto servicio type.
   */
  protected string $id;

  /**
   * The human-readable name of the zinco producto servicio type.
   */
  protected string $label;

}
