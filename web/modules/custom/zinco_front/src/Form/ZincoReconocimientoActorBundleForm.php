<?php

namespace Drupal\zinco_front\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for registering a new recognition for an actor type.
 */
class ZincoReconocimientoActorBundleForm extends FormBase
{

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new ZincoReconocimientoActorBundleForm.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager)
  {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return 'zinco_reconocimiento_actor_bundle_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state)
  {
    $bundle_info = \Drupal::service('entity_type.bundle.info')->getBundleInfo('zinco_actors_zincoactors');
    $options = [];
    foreach ($bundle_info as $bundle_id => $info) {
      $options[$info['label']] = $info['label'];
    }

    $form['tipo_actor'] = [
      '#type' => 'select',
      '#title' => $this->t('Tipo de Actor'),
      '#options' => $options,
      '#required' => TRUE,
      '#empty_option' => $this->t('- Seleccione un tipo de actor -'),
    ];

    $form['comentarios'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Comentarios'),
      '#required' => TRUE,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Registrar Reconocimiento'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $tipo_actor = $form_state->getValue('tipo_actor');
    $comentarios = $form_state->getValue('comentarios');

    $bundle = 'reconocimiento_de_actor';
    $current_user = \Drupal::currentUser();

    // Obtener el actor asociado al usuario actual.
    $user_entity = $this->entityTypeManager->getStorage('user')->load($current_user->id());
    $actor_id = NULL;
    if ($user_entity->hasField('field_actor') && !$user_entity->get('field_actor')->isEmpty()) {
      $actor_id = $user_entity->get('field_actor')->target_id;
    }

    $reconocimiento = $this->entityTypeManager->getStorage('zinco_reconocimientos')->create([
      'bundle' => $bundle,
      'label' => $tipo_actor,
      'field_descripcion_reconocimiento' => $comentarios,
      'field_actor_asociado' => $actor_id,
      'uid' => $current_user->id(),
      'status' => TRUE,
    ]);

    $reconocimiento->save();

    $this->messenger()->addStatus($this->t('El reconocimiento de tipo @tipo ha sido registrado correctamente.', ['@tipo' => $tipo_actor]));

    if ($actor_id) {
      $form_state->setRedirect('zinco_front.reconocimientos_list', ['actor_id' => $actor_id]);
    } else {
      $form_state->setRedirect('<front>');
    }
  }

}
