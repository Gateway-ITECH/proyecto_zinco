<?php

declare(strict_types=1);

namespace Drupal\zinco_retos_innovacion\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Entity\Attribute\ConfigEntityType;
use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\zinco_retos_innovacion\Form\ZincoRetosInnovacionTypeForm;
use Drupal\zinco_retos_innovacion\ZincoRetosInnovacionTypeListBuilder;

/**
 * Defines the Zinco retos innovacion type configuration entity.
 */
#[ConfigEntityType(
  id: 'zinco_retos_innovacion_type',
  label: new TranslatableMarkup('Tipo de reto de innovación'),
  label_collection: new TranslatableMarkup('Tipos de retos de innovación'),
  label_singular: new TranslatableMarkup('Tipo de reto de innovación'),
  label_plural: new TranslatableMarkup('Tipos de retos de innovación'),
  config_prefix: 'zinco_retos_innovacion_type',
  entity_keys: [
    'id' => 'id',
    'label' => 'label',
    'uuid' => 'uuid',
  ],
  handlers: [
    'list_builder' => ZincoRetosInnovacionTypeListBuilder::class,
    'route_provider' => [
      'html' => AdminHtmlRouteProvider::class,
    ],
    'form' => [
      'add' => ZincoRetosInnovacionTypeForm::class,
      'edit' => ZincoRetosInnovacionTypeForm::class,
      'delete' => EntityDeleteForm::class,
    ],
  ],
  links: [
    'add-form' => '/admin/structure/zinco_retos_innovacion_types/add',
    'edit-form' => '/admin/structure/zinco_retos_innovacion_types/manage/{zinco_retos_innovacion_type}',
    'delete-form' => '/admin/structure/zinco_retos_innovacion_types/manage/{zinco_retos_innovacion_type}/delete',
    'collection' => '/admin/structure/zinco_retos_innovacion_types',
  ],
  admin_permission: 'administer zinco_retos_innovacion types',
  bundle_of: 'zinco_retos_innovacion',
  label_count: [
    'singular' => '@count Tipo de reto de innovación',
    'plural' => '@count Tipos de retos de innovación',
  ],
  config_export: [
    'id',
    'label',
    'uuid',
  ],
)]
final class ZincoRetosInnovacionType extends ConfigEntityBundleBase
{

  /**
   * The machine name of this zinco retos innovacion type.
   */
  protected string $id;

  /**
   * The human-readable name of the zinco retos innovacion type.
   */
  protected string $label;

}
