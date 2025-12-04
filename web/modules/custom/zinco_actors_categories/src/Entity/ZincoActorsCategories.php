<?php

declare(strict_types=1);

namespace Drupal\zinco_actors_categories\Entity;

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
use Drupal\zinco_actors_categories\Form\ZincoActorsCategoriesForm;
use Drupal\zinco_actors_categories\ZincoActorsCategoriesInterface;
use Drupal\zinco_actors_categories\ZincoActorsCategoriesListBuilder;

/**
 * Defines the zinco actors categories entity class.
 */
#[ContentEntityType(
  id: 'zinco_actors_categories',
  label: new TranslatableMarkup('Zinco actors categories'),
  label_collection: new TranslatableMarkup('Zinco actors categoriess'),
  label_singular: new TranslatableMarkup('zinco actors categories'),
  label_plural: new TranslatableMarkup('zinco actors categoriess'),
  entity_keys: [
    'id' => 'id',
    'label' => 'label',
    'owner' => 'uid',
    'published' => 'status',
    'uuid' => 'uuid',
  ],
  handlers: [
    'list_builder' => ZincoActorsCategoriesListBuilder::class,
    'views_data' => EntityViewsData::class,
    'form' => [
      'add' => ZincoActorsCategoriesForm::class,
      'edit' => ZincoActorsCategoriesForm::class,
      'delete' => ContentEntityDeleteForm::class,
      'delete-multiple-confirm' => DeleteMultipleForm::class,
    ],
    'route_provider' => [
      'html' => AdminHtmlRouteProvider::class,
    ],
  ],
  links: [
    'collection' => '/admin/content/zinco-actors-categories',
    'add-form' => '/zinco-actors-categories/add',
    'canonical' => '/zinco-actors-categories/{zinco_actors_categories}',
    'edit-form' => '/zinco-actors-categories/{zinco_actors_categories}/edit',
    'delete-form' => '/zinco-actors-categories/{zinco_actors_categories}/delete',
    'delete-multiple-form' => '/admin/content/zinco-actors-categories/delete-multiple',
  ],
  admin_permission: 'administer zinco_actors_categories',
  base_table: 'zinco_actors_categories',
  label_count: [
    'singular' => '@count zinco actors categoriess',
    'plural' => '@count zinco actors categoriess',
  ],
)]
class ZincoActorsCategories extends ContentEntityBase implements ZincoActorsCategoriesInterface {

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
      ->setDescription(t('The time that the zinco actors categories was created.'))
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
      ->setDescription(t('The time that the zinco actors categories was last edited.'));

    
    $fields['field_actor_bundle'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Associated Actor Bundle'))
      ->setDescription(t('The actor bundle associated with this category.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values_function', 'Drupal\zinco_actors_categories\Entity\ZincoActorsCategories::getActorBundleOptions')
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 25,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 25,
      ])
      ->setDisplayConfigurable('view', TRUE);

      // agregar un campo para seleccionar un elemento de una taxonomía llamada "Categorías de Actores"
      $fields['field_actor_category'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Actor Category'))
      ->setDescription(t('The category of the actor.'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('handler', 'default')
      ->setSetting('handler_settings', [
        'target_bundles' => [
          'categorias_de_actores' => 'categorias_de_actores',
        ],
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 30,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'entity_reference_label',
        'weight' => 30,
      ])
      ->setDisplayConfigurable('view', TRUE); 

    return $fields;
  }

  /**
   * Returns a list of actor bundle options for the field.
   *
   * @return array
   *   An array of allowed values for the actor bundle field.
   */
  public static function getActorBundleOptions() {
    $entity_type_manager = \Drupal::service('entity_type.manager');
    $bundle_storage = $entity_type_manager->getStorage('zinco_actors_zincoactors_type');
    $bundles = $bundle_storage->loadMultiple();
    $options = [];
    foreach ($bundles as $bundle_id => $bundle_entity) {
      $options[$bundle_id] = $bundle_entity->label();
    }
    return $options;
  }

}

