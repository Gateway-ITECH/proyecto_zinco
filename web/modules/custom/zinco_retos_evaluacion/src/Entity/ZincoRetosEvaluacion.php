<?php

declare(strict_types=1);

namespace Drupal\zinco_retos_evaluacion\Entity;

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
use Drupal\zinco_retos_evaluacion\Form\ZincoRetosEvaluacionForm;
use Drupal\zinco_retos_evaluacion\Form\ZincoRetosEvaluacionFormFrontend;
use Drupal\zinco_retos_evaluacion\ZincoRetosEvaluacionInterface;
use Drupal\zinco_retos_evaluacion\ZincoRetosEvaluacionListBuilder;

/**
 * Defines the Evaluación de retos entity class.
 */
#[ContentEntityType(
  id: 'zinco_retos_evaluacion',
  label: new TranslatableMarkup('Evaluación de retos'),
  label_collection: new TranslatableMarkup('Evaluaciones de retos'),
  label_singular: new TranslatableMarkup('Evaluación de retos'),
  label_plural: new TranslatableMarkup('Evaluaciones de retos'),
  entity_keys: [
    'id' => 'id',
    'label' => 'label',
    'owner' => 'uid',
    'published' => 'status',
    'uuid' => 'uuid',
  ],
  handlers: [
    'list_builder' => ZincoRetosEvaluacionListBuilder::class,
    'views_data' => EntityViewsData::class,
    'form' => [
      'add' => ZincoRetosEvaluacionForm::class,
      'edit' => ZincoRetosEvaluacionForm::class,
      'delete' => ContentEntityDeleteForm::class,
      'delete-multiple-confirm' => DeleteMultipleForm::class,
      'frontend_add' => ZincoRetosEvaluacionFormFrontend::class
    ],
    'route_provider' => [
      'html' => AdminHtmlRouteProvider::class,
    ],
  ],
  links: [
    'collection' => '/admin/content/zinco-retos-evaluacion',
    'add-form' => '/zinco-retos-evaluacion/add',
    'canonical' => '/zinco-retos-evaluacion/{zinco_retos_evaluacion}',
    'edit-form' => '/zinco-retos-evaluacion/{zinco_retos_evaluacion}/edit',
    'delete-form' => '/zinco-retos-evaluacion/{zinco_retos_evaluacion}/delete',
    'delete-multiple-form' => '/admin/content/zinco-retos-evaluacion/delete-multiple',
  ],
  admin_permission: 'administer zinco_retos_evaluacion',
  base_table: 'zinco_retos_evaluacion',
  label_count: [
    'singular' => '@count Evaluaciones de retos',
    'plural' => '@count Evaluaciones de retos',
  ],
  field_ui_base_route: 'entity.zinco_retos_evaluacion.settings',
)]
class ZincoRetosEvaluacion extends ContentEntityBase implements ZincoRetosEvaluacionInterface
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
      ->setLabel(t('Label'))
      ->setRequired(TRUE)
      ->setDefaultValue('Nueva evaluación')
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
      ->setDescription(t('The time that the Evaluación de retos was created.'))
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
      ->setDescription(t('The time that the Evaluación de retos was last edited.'));

    return $fields;
  }

}
