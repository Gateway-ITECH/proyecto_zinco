<?php

declare(strict_types=1);

namespace Drupal\zinco_reconocimientos\Entity;

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
use Drupal\zinco_reconocimientos\Form\ZincoReconocimientosForm;
use Drupal\zinco_reconocimientos\Form\ZincoReconocimientosActorForm;
use Drupal\zinco_reconocimientos\ZincoReconocimientosInterface;
use Drupal\zinco_reconocimientos\ZincoReconocimientosListBuilder;

/**
 * Defines the zinco reconocimientos entity class.
 */
#[ContentEntityType(
  id: 'zinco_reconocimientos',
  label: new TranslatableMarkup('Zinco reconocimientos'),
  label_collection: new TranslatableMarkup('Zinco reconocimientoss'),
  label_singular: new TranslatableMarkup('zinco reconocimientos'),
  label_plural: new TranslatableMarkup('zinco reconocimientoss'),
  entity_keys: [
    'id' => 'id',
    'bundle' => 'bundle',
    'label' => 'label',
    'owner' => 'uid',
    'published' => 'status',
    'uuid' => 'uuid',
  ],
  handlers: [
    'list_builder' => ZincoReconocimientosListBuilder::class,
    'views_data' => EntityViewsData::class,
    'form' => [
      'add' => ZincoReconocimientosForm::class,
      'edit' => ZincoReconocimientosForm::class,
      'delete' => ContentEntityDeleteForm::class,
      'delete-multiple-confirm' => DeleteMultipleForm::class,
      'default' => ZincoReconocimientosForm::class,
      'actor' => ZincoReconocimientosActorForm::class,
    ],
    'route_provider' => [
      'html' => AdminHtmlRouteProvider::class,
    ],
  ],
  links: [
    'collection' => '/admin/content/zinco-reconocimientos',
    'add-form' => '/zinco-reconocimientos/add/{zinco_reconocimientos_type}',
    'add-page' => '/zinco-reconocimientos/add',
    'canonical' => '/zinco-reconocimientos/{zinco_reconocimientos}',
    'edit-form' => '/zinco-reconocimientos/{zinco_reconocimientos}/edit',
    'delete-form' => '/zinco-reconocimientos/{zinco_reconocimientos}/delete',
    'delete-multiple-form' => '/admin/content/zinco-reconocimientos/delete-multiple',
  ],
  admin_permission: 'administer zinco_reconocimientos types',
  bundle_entity_type: 'zinco_reconocimientos_type',
  bundle_label: new TranslatableMarkup('Zinco reconocimientos type'),
  base_table: 'zinco_reconocimientos',
  label_count: [
    'singular' => '@count zinco reconocimientoss',
    'plural' => '@count zinco reconocimientoss',
  ],
  field_ui_base_route: 'entity.zinco_reconocimientos_type.edit_form',
)]
class ZincoReconocimientos extends ContentEntityBase implements ZincoReconocimientosInterface {

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
      ->setLabel(t('Nombre'))
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
      ->setDescription(t('The time that the zinco reconocimientos was created.'))
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
      ->setDescription(t('The time that the zinco reconocimientos was last edited.'));

    return $fields;
  }

}
