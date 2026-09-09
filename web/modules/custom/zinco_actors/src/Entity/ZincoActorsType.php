<?php

declare(strict_types=1);

namespace Drupal\zinco_actors\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Entity\Attribute\ConfigEntityType;
use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\zinco_actors\Form\ZincoActorsTypeForm;
use Drupal\zinco_actors\ZincoActorsTypeListBuilder;

/**
 * Defines the ZincoActors type configuration entity.
 */
#[ConfigEntityType(
  id: 'zinco_actors_zincoactors_type',
  label: new TranslatableMarkup('ZincoActors type'),
  label_collection: new TranslatableMarkup('ZincoActors types'),
  label_singular: new TranslatableMarkup('zincoactors type'),
  label_plural: new TranslatableMarkup('Actores Zinco types'),
  config_prefix: 'zinco_actors_zincoactors_type',
  entity_keys: [
    'id' => 'id',
    'label' => 'label',
    'uuid' => 'uuid',
  ],
  handlers: [
    'list_builder' => ZincoActorsTypeListBuilder::class,
    'route_provider' => [
      'html' => AdminHtmlRouteProvider::class,
    ],
    'form' => [
      'add' => ZincoActorsTypeForm::class,
      'edit' => ZincoActorsTypeForm::class,
      'delete' => EntityDeleteForm::class,
    ],
  ],
  links: [
    'add-form' => '/admin/structure/zinco_actors_zincoactors_types/add',
    'edit-form' => '/admin/structure/zinco_actors_zincoactors_types/manage/{zinco_actors_zincoactors_type}',
    'delete-form' => '/admin/structure/zinco_actors_zincoactors_types/manage/{zinco_actors_zincoactors_type}/delete',
    'collection' => '/admin/structure/zinco_actors_zincoactors_types',
  ],
  admin_permission: 'administer zinco_actors_zincoactors types',
  bundle_of: 'zinco_actors_zincoactors',
  label_count: [
    'singular' => '@count zincoactors type',
    'plural' => '@count Actores Zinco types',
  ],
  config_export: [
    'id',
    'label',
    'uuid',
  ],
)]
final class ZincoActorsType extends ConfigEntityBundleBase
{

  /**
   * The machine name of this zincoactors type.
   */
  protected string $id;

  /**
   * The human-readable name of the zincoactors type.
   */
  protected string $label;

}
