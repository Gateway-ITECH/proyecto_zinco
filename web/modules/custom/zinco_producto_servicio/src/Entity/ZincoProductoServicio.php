<?php

declare(strict_types=1);

namespace Drupal\zinco_producto_servicio\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Form\DeleteMultipleForm;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\user\EntityOwnerTrait;
use Drupal\views\EntityViewsData;
use Drupal\zinco_producto_servicio\Form\ZincoProductoServicioForm;
use Drupal\zinco_producto_servicio\ZincoProductoServicioInterface;
use Drupal\zinco_producto_servicio\ZincoProductoServicioListBuilder;

/**
 * Defines the zinco producto servicio entity class.
 */
#[ContentEntityType(
  id: 'zinco_producto_servicio',
  label: new TranslatableMarkup('Zinco producto servicio'),
  label_collection: new TranslatableMarkup('Zinco producto servicios'),
  label_singular: new TranslatableMarkup('zinco producto servicio'),
  label_plural: new TranslatableMarkup('zinco producto servicios'),
  entity_keys: [
    'id' => 'id',
    'bundle' => 'bundle',
    'label' => 'label',
    'owner' => 'uid',
    'published' => 'status',
    'uuid' => 'uuid',
  ],
  handlers: [
    'list_builder' => ZincoProductoServicioListBuilder::class,
    'views_data' => EntityViewsData::class,
    'form' => [
      'add' => ZincoProductoServicioForm::class,
      'edit' => ZincoProductoServicioForm::class,
      'delete' => ContentEntityDeleteForm::class,
      'delete-multiple-confirm' => DeleteMultipleForm::class,
    ],
    'route_provider' => [
      'html' => AdminHtmlRouteProvider::class,
    ],
  ],
  links: [
    'collection' => '/admin/content/zinco-producto-servicio',
    'add-form' => '/zinco-producto-servicio/add/{zinco_producto_servicio_type}',
    'add-page' => '/zinco-producto-servicio/add',
    'canonical' => '/zinco-producto-servicio/{zinco_producto_servicio}',
    'edit-form' => '/zinco-producto-servicio/{zinco_producto_servicio}/edit',
    'delete-form' => '/zinco-producto-servicio/{zinco_producto_servicio}/delete',
    'delete-multiple-form' => '/admin/content/zinco-producto-servicio/delete-multiple',
  ],
  admin_permission: 'administer zinco_producto_servicio types',
  bundle_entity_type: 'zinco_producto_servicio_type',
  bundle_label: new TranslatableMarkup('Zinco producto servicio type'),
  base_table: 'zinco_producto_servicio',
  label_count: [
    'singular' => '@count zinco producto servicios',
    'plural' => '@count zinco producto servicios',
  ],
  field_ui_base_route: 'entity.zinco_producto_servicio_type.edit_form',
)]
class ZincoProductoServicio extends ContentEntityBase implements ZincoProductoServicioInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage): void {
    parent::preSave($storage);
    if (!$this->getOwnerId()) {
      // If no owner has been set explicitly, make the anonymous user the owner.
      $this->setOwnerId(0);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Label'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Status'))
      ->setDefaultValue(TRUE)
      ->setSetting('on_label', 'Enabled')
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => FALSE,
        ],
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'boolean',
        'label' => 'above',
        'weight' => 0,
        'settings' => [
          'format' => 'enabled-disabled',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Description'))
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'text_default',
        'label' => 'above',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Author'))
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback(self::class . '::getDefaultEntityOwner')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
        'weight' => 15,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'author',
        'weight' => 15,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setDescription(t('The time that the zinco producto servicio was created.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'timestamp',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the zinco producto servicio was last edited.'));

    return $fields;
  }

}
