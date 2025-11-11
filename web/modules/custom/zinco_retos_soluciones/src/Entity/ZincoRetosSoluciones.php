<?php

declare(strict_types=1);

namespace Drupal\zinco_retos_soluciones\Entity;

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
use Drupal\zinco_retos_soluciones\Form\ZincoRetosSolucionesForm;
use Drupal\zinco_retos_soluciones\ZincoRetosSolucionesInterface;
use Drupal\zinco_retos_soluciones\ZincoRetosSolucionesListBuilder;

/**
 * Defines the zinco retos soluciones entity class.
 */
#[ContentEntityType(
  id: 'zinco_retos_soluciones',
  label: new TranslatableMarkup('Zinco retos soluciones'),
  label_collection: new TranslatableMarkup('Zinco retos solucioness'),
  label_singular: new TranslatableMarkup('zinco retos soluciones'),
  label_plural: new TranslatableMarkup('zinco retos solucioness'),
  entity_keys: [
    'id' => 'id',
    'label' => 'label',
    'owner' => 'uid',
    'published' => 'status',
    'uuid' => 'uuid',
  ],
  handlers: [
    'list_builder' => ZincoRetosSolucionesListBuilder::class,
    'views_data' => EntityViewsData::class,
    'form' => [
      'add' => ZincoRetosSolucionesForm::class,
      'edit' => ZincoRetosSolucionesForm::class,
      'delete' => ContentEntityDeleteForm::class,
      'delete-multiple-confirm' => DeleteMultipleForm::class,
      'frontend_add' => ZincoRetosSolucionesForm::class
    ],
    'route_provider' => [
      'html' => AdminHtmlRouteProvider::class,
    ],
  ],
  links: [
    'collection' => '/admin/content/zinco-retos-soluciones',
    'add-form' => '/zinco-retos-soluciones/add',
    'canonical' => '/zinco-retos-soluciones/{zinco_retos_soluciones}',
    'edit-form' => '/zinco-retos-soluciones/{zinco_retos_soluciones}/edit',
    'delete-form' => '/zinco-retos-soluciones/{zinco_retos_soluciones}/delete',
    'delete-multiple-form' => '/admin/content/zinco-retos-soluciones/delete-multiple',
  ],
  admin_permission: 'administer zinco_retos_soluciones',
  base_table: 'zinco_retos_soluciones',
  label_count: [
    'singular' => '@count zinco retos solucioness',
    'plural' => '@count zinco retos solucioness',
  ],
  field_ui_base_route: 'entity.zinco_retos_soluciones.settings',
)]
class ZincoRetosSoluciones extends ContentEntityBase implements ZincoRetosSolucionesInterface {

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
      ->setLabel(t('Título'))
      ->setRequired(TRUE)
      ->setDefaultValue('Nueva solución')
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
      ->setDescription(t('The time that the zinco retos soluciones was created.'))
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
      ->setDescription(t('The time that the zinco retos soluciones was last edited.'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   *
   * Sobrescribimos este método para establecer una URL de redirección específica
   * para la operación 'frontend_add'.
   */
  protected function getRedirectUrl() {
    
    // 1. Verifica si la operación actual es 'frontend_add'.
    var_dump($this->operation);
    if ($this->operation === 'frontend_add') {
      
      // 2. Devuelve la URL a la que deseas redirigir.
      // Puedes usar fromRoute() para rutas definidas en routing.yml.
      //return Url::fromRoute('mymodule.success_page');
      
      // O a la página principal de la entidad después de la creación:
      // return Url::fromRoute('entity.zinco_retos_soluciones.collection');
      
      // O a una URL absoluta:
      return Url::fromUri('https://tusitio.com/gracias');
    }

    // 3. Para cualquier otra operación (default, edit, delete), 
    // se utiliza la lógica de redirección base de la entidad (generalmente a la vista de la entidad).
    return parent::getRedirectUrl();
  }


  public function customFrontendRedirect(array &$form, FormStateInterface $form_state) {
    // 1. Opcional: Verifica que la entidad se haya guardado correctamente, 
    // aunque este handler generalmente solo se ejecuta en caso de éxito.
    $entity = $this->entity; 
    
    // 2. Verifica la operación para asegurar la especificidad.
    if ($this->operation === 'frontend_add') {
        // 3. Forzar la redirección a una ruta específica.
        // Esto sobrescribe cualquier redirección que el handler principal haya intentado establecer.
        $form_state->setRedirect('mymodule.success_page'); 
        
        // Si necesitas redirigir al ID de la entidad recién creada:
        // $form_state->setRedirect('entity.zinco_retos_soluciones.canonical', ['zinco_retos_soluciones' => $entity->id()]);
    }
}

}


