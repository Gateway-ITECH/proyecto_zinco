<?php

namespace Drupal\zinco_front\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for registering a reto solution.
 */
class RetoSolutionForm extends FormBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new RetoSolutionForm object.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(AccountProxyInterface $current_user, EntityTypeManagerInterface $entity_type_manager) {
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'reto_solution_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $reto_id = NULL) {
    // Store the reto_id in form state for submission handler.
    $form_state->set('reto_id', $reto_id);

    // Load the reto to display its title.
    $reto = NULL;
    if ($reto_id) {
      $reto = $this->entityTypeManager->getStorage('zinco_retos_innovacion')->load($reto_id);
    }

    if (!$reto) {
      $this->messenger()->addError($this->t('The specified challenge does not exist.'));
      return [];
    }

    $form['reto_title'] = [
      '#type' => 'item',
      '#title' => $this->t('Challenge'),
      '#markup' => $reto->label(),
    ];

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Solution Title'),
      '#required' => TRUE,
      '#maxlength' => 255,
    ];

    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Solution Description'),
      '#required' => TRUE,
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit Solution'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Add any custom validation logic here.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $reto_id = $form_state->get('reto_id');
    $title = $form_state->getValue('title');
    $description = $form_state->getValue('description');
    $user_id = $this->currentUser->id();

    try {
      // Create a new ZincoRetosSoluciones entity.
      $solution = $this->entityTypeManager->getStorage('zinco_retos_soluciones')->create([
        'title' => $title,
        'description' => $description,
        'reto_innovacion' => $reto_id, // Assuming 'reto_innovacion' is the field for the referenced reto.
        'uid' => $user_id,
      ]);
      $solution->save();

      $this->messenger()->addStatus($this->t('Your solution "%title" has been submitted successfully.', ['%title' => $title]));
      $form_state->setRedirect('zinco_front.reto_detail', ['reto_id' => $reto_id]);
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t('Failed to submit solution: @message', ['@message' => $e->getMessage()]));
      $this->getLogger('zinco_front')->error('Failed to submit reto solution: @message', ['@message' => $e->getMessage()]);
    }
  }

}