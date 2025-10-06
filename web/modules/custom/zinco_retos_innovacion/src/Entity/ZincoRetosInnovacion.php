<?php

declare(strict_types=1);

namespace Drupal\zinco_retos_innovacion\Entity;

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
use Drupal\zinco_retos_innovacion\Form\ZincoRetosInnovacionForm;
use Drupal\zinco_retos_innovacion\ZincoRetosInnovacionInterface;
use Drupal\zinco_retos_innovacion\ZincoRetosInnovacionListBuilder;

/**
 * Defines the zinco retos innovacion entity class.
 */
#[ContentEntityType(
  id: 'zinco_retos_innovacion',
  label: new TranslatableMarkup('Zinco retos innovacion'),
  label_collection: new TranslatableMarkup('Zinco retos innovacions'),
  label_singular: new TranslatableMarkup('zinco retos innovacion'),
  label_plural: new TranslatableMarkup('zinco retos innovacions'),
  entity_keys: [
    'id' => 'id',
    'bundle' => 'bundle',
    'label' => 'label',
    'owner' => 'uid',
    'published' => 'status',
    'uuid' => 'uuid',
  ],
  handlers: [
    'list_builder' => ZincoRetosInnovacionListBuilder::class,
    'views_data' => EntityViewsData::class,
    'form' => [
      'add' => ZincoRetosInnovacionForm::class,
      'edit' => ZincoRetosInnovacionForm::class,
      'delete' => ContentEntityDeleteForm::class,
      'delete-multiple-confirm' => DeleteMultipleForm::class,
    ],
    'route_provider' => [
      'html' => AdminHtmlRouteProvider::class,
    ],
  ],
  links: [
    'collection' => '/admin/content/zinco-retos-innovacion',
    'add-form' => '/zinco-retos-innovacion/add/{zinco_retos_innovacion_type}',
    'add-page' => '/zinco-retos-innovacion/add',
    'canonical' => '/zinco-retos-innovacion/{zinco_retos_innovacion}',
    'edit-form' => '/zinco-retos-innovacion/{zinco_retos_innovacion}/edit',
    'delete-form' => '/zinco-retos-innovacion/{zinco_retos_innovacion}/delete',
    'delete-multiple-form' => '/admin/content/zinco-retos-innovacion/delete-multiple',
  ],
  admin_permission: 'administer zinco_retos_innovacion types',
  bundle_entity_type: 'zinco_retos_innovacion_type',
  bundle_label: new TranslatableMarkup('Zinco retos innovacion type'),
  base_table: 'zinco_retos_innovacion',
  label_count: [
    'singular' => '@count zinco retos innovacions',
    'plural' => '@count zinco retos innovacions',
  ],
  field_ui_base_route: 'entity.zinco_retos_innovacion_type.edit_form',
)]
class ZincoRetosInnovacion extends ContentEntityBase implements ZincoRetosInnovacionInterface {

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
      ->setLabel(t('Título del reto'))
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
      ->setDescription(t('The time that the zinco retos innovacion was created.'))
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
      ->setDescription(t('The time that the zinco retos innovacion was last edited.'));

    $fields['fecha_inicio'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha de inicio'))
      ->setDescription(t('The start date of the Zinco Reto Innovacion.'))
      ->setSetting('datetime_type', 'date')
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => 30,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'datetime_default',
        'label' => 'above',
        'settings' => [
          'format_type' => 'html_date',
        ],
        'weight' => 30,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['fecha_fin'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha de fin'))
      ->setDescription(t('The end date of the Zinco Reto Innovacion.'))
      ->setSetting('datetime_type', 'date')
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => 31,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'datetime_default',
        'label' => 'above',
        'settings' => [
          'format_type' => 'html_date',
        ],
        'weight' => 31,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['estado_reto_innovacion'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Estado del reto de innovación'))
      ->setDescription(t('The status of the Zinco Reto Innovacion.'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('handler_settings', ['target_bundles' => ['estados_de_retos_de_innovacion' => 'estados_de_retos_de_innovacion']])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 32,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'entity_reference_label',
        'label' => 'above',
        'weight' => 32,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['area_enfoque'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Área de enfoque'))
      ->setDescription(t('The area of focus for the Zinco Reto Innovacion.'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('handler_settings', ['target_bundles' => ['tecnologias_clave' => 'tecnologias_clave']])
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 33,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'entity_reference_label',
        'label' => 'above',
        'weight' => 33,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['sector_economico'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Sector económico'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('handler_settings', [
        'target_bundles' => ['ciiu_colombia' => 'ciiu_colombia'],
        'auto_create' => FALSE,
        'selection_handler' => 'default:taxonomy_term',
        'selection_settings' => [
          'target_bundles' => ['ciiu_colombia' => 'ciiu_colombia'],
          'sort' => [
            'field' => 'weight',
            'direction' => 'asc',
          ],
          'auto_create' => FALSE,
          'tree' => TRUE, // This setting is crucial for displaying hierarchy.
        ],
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select', // Use options_select for a hierarchical dropdown.
        'weight' => 33, // Adjust weight as needed
        'settings' => [],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'entity_reference_label',
        'weight' => 33, // Adjust weight as needed
      ])
      ->setDisplayConfigurable('view', TRUE);

  

    $fields['organizador_reto'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Organizador del reto'))
      ->setDescription(t('The organization or individual organizing the Zinco Reto Innovacion.'))
      ->setSetting('target_type', 'zinco_actors_zincoactors')
      ->setSetting('handler_settings', ['target_bundles' => NULL]) // Allow all bundles of zinco_actors
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 34,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'entity_reference_label',
        'label' => 'above',
        'weight' => 34,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['visibilidad_reto'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Visibilidad del reto'))
      ->setDescription(t('Determines if the Zinco Reto Innovacion is public or private.'))
      ->setDefaultValue(FALSE) // Default to private
      ->setSetting('on_label', t('Público'))
      ->setSetting('off_label', t('Privado'))
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => TRUE,
        ],
        'weight' => 35,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'boolean',
        'label' => 'above',
        'weight' => 35,
        'settings' => [
          'format' => 'enabled-disabled',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['criterios_evaluacion_reto'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Criterios de evaluación del reto'))
      ->setDescription(t('The evaluation criteria for the Zinco Reto Innovacion.'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('handler_settings', ['target_bundles' => ['criterios_evaluacion_retos' => 'criterios_evaluacion_retos']])
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 36,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'entity_reference_label',
        'label' => 'above',
        'weight' => 36,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['aprobado_por'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Aprobado por'))
      ->setDescription(t('The user who approved the Zinco Reto Innovacion.'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler_settings', ['target_bundles' => NULL]) // Allow all bundles of user
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 37,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'entity_reference_label',
        'label' => 'above',
        'weight' => 37,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['fecha_evaluacion'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha de evaluación'))
      ->setDescription(t('The evaluation date of the Zinco Reto Innovacion.'))
      ->setSetting('datetime_type', 'date')
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => 38,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'datetime_default',
        'label' => 'above',
        'settings' => [
          'format_type' => 'html_date',
        ],
        'weight' => 38,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['recompensas_reto'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Recompensas del reto'))
      ->setDescription(t('Description of the rewards for completing the Zinco Reto Innovacion.'))
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 39,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'text_default',
        'label' => 'above',
        'weight' => 39,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['documentos_reto'] = BaseFieldDefinition::create('file')
      ->setLabel(t('Documentos del reto'))
      ->setDescription(t('Documents related to the Zinco Reto Innovacion.'))
      ->setSetting('file_extensions', 'jpg png pdf docx xlsx csv')
      ->setSetting('max_filesize', '10MB')
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setDisplayOptions('form', [
        'type' => 'file_generic',
        'weight' => 40,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'file_default',
        'label' => 'above',
        'weight' => 40,
      ])
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
