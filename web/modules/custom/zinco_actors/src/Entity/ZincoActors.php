<?php

declare(strict_types=1);

namespace Drupal\zinco_actors\Entity;

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
use Drupal\zinco_actors\Form\ZincoActorsForm;
use Drupal\zinco_actors\Form\ZincoActorsFormFrontend;
use Drupal\zinco_actors\ZincoActorsInterface;
use Drupal\zinco_actors\ZincoActorsListBuilder;

/**
 * Defines the zincoactors entity class.
 */
#[ContentEntityType(
  id: 'zinco_actors_zincoactors',
  label: new TranslatableMarkup('ZincoActors'),
  label_collection: new TranslatableMarkup('Actores Zinco'),
  label_singular: new TranslatableMarkup('zincoactors'),
  label_plural: new TranslatableMarkup('Actores Zinco'),
  entity_keys: [
    'id' => 'id',
    'bundle' => 'bundle',
    'label' => 'label',
    'owner' => 'uid',
    'published' => 'status',
    'uuid' => 'uuid',
  ],
  handlers: [
    'list_builder' => ZincoActorsListBuilder::class,
    'views_data' => EntityViewsData::class,
    'form' => [
      'add' => ZincoActorsForm::class,
      'edit' => ZincoActorsForm::class,
      'delete' => ContentEntityDeleteForm::class,
      'delete-multiple-confirm' => DeleteMultipleForm::class,
      'default' => ZincoActorsForm::class,
      'frontend' => ZincoActorsFormFrontend::class,
    ],
    'route_provider' => [
      'html' => AdminHtmlRouteProvider::class,
    ],
  ],
  links: [
    'collection' => '/admin/content/zincoactors',
    'add-form' => '/zincoactors/add/{zinco_actors_zincoactors_type}',
    'add-page' => '/zincoactors/add',
    'canonical' => '/zincoactors/{zinco_actors_zincoactors}',
    'edit-form' => '/zincoactors/{zinco_actors_zincoactors}/edit',
    'delete-form' => '/zincoactors/{zinco_actors_zincoactors}/delete',
    'delete-multiple-form' => '/admin/content/zincoactors/delete-multiple',
  ],
  admin_permission: 'administer zinco_actors_zincoactors types',
  bundle_entity_type: 'zinco_actors_zincoactors_type',
  bundle_label: new TranslatableMarkup('ZincoActors type'),
  base_table: 'zinco_actors_zincoactors',
  label_count: [
    'singular' => '@count Actores Zinco',
    'plural' => '@count Actores Zinco',
  ],
  field_ui_base_route: 'entity.zinco_actors_zincoactors_type.edit_form',
)]
class ZincoActors extends ContentEntityBase implements ZincoActorsInterface
{

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage): void
  {
    parent::preSave($storage);
    if (!$this->getOwnerId()) {
      // If no owner has been set explicitly, make the anonymous user the owner.
      $this->setOwnerId(0);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
  {

    $fields = parent::baseFieldDefinitions($entity_type);



    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre o Razón social'))
      ->setRequired(FALSE)
      ->setDefaultValue('Sin nombre')
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


    $fields['municipio'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Municipio'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('handler_settings', ['target_bundles' => ['divipola' => 'divipola']])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -3,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'entity_reference_label',
        'weight' => -3,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['sector_economico_principal'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Sector economico principal'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('handler_settings', ['target_bundles' => ['ciiu_colombia' => 'ciiu_colombia']])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -2,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'entity_reference_label',
        'weight' => -2,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['tecnologias_clave'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tecnologias clave'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('handler_settings', ['target_bundles' => ['tecnologias_clave' => 'tecnologias_clave']])
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -1,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'entity_reference_label',
        'weight' => -1,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['sitio_web'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Sitio web'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['email'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Correo electronico de contacto'))
      ->setSetting('max_length', 255)
      ->setSetting('validate', ['email'])
      ->setDisplayOptions('form', [
        'type' => 'email_default',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['telefono'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Teléfono/Celular'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('view', TRUE);


    $fields['imagen_perfil'] = BaseFieldDefinition::create('image')
      ->setLabel(t('Imagen de perfil'))
      ->setSetting('file_extensions', 'png jpg jpeg gif')
      ->setSetting('max_filesize', '2MB')
      ->setDisplayOptions('form', [
        'type' => 'image_image',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'image',
        'weight' => 3,
        'settings' => [
          'image_style' => 'thumbnail',
          'image_link' => 'content',
        ],
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
      ->setDescription(t('The time that the zincoactors was created.'))
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
      ->setDescription(t('The time that the zincoactors was last edited.'));


    // $fields['color_de_etiqueta'] = BaseFieldDefinition::create('list_string')
    //   ->setLabel(t('Color de etiqueta'))
    //   ->setRequired(FALSE)
    //   ->setDefaultValue('text-bg-secondary')
    //   ->setSettings([
    //     'allowed_values' => [
    //       'text-bg-primary' => 'Primary',
    //       'text-bg-secondary' => 'Secondary',
    //       'text-bg-success' => 'Success',
    //       'text-bg-danger' => 'Danger',
    //       'text-bg-warning' => 'Warning',
    //       'text-bg-info' => 'Info',
    //       'text-bg-light' => 'Light',
    //       'text-bg-dark' => 'Dark',
    //     ],
    //   ])
    //   ->setDisplayOptions('form', [
    //     'type' => 'options_select',
    //     'weight' => 25,
    //   ])
    //   ->setDisplayConfigurable('form', TRUE)
    //   ->setDisplayOptions('view', [
    //     'label' => 'above',
    //     'type' => 'list_default',
    //     'weight' => 25,
    //   ])
    //   ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
