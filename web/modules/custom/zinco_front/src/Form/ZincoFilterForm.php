<?php

namespace Drupal\zinco_front\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Zinco Front filter form.
 */
class ZincoFilterForm extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new ZincoFilterForm.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'zinco_front_filter_form';
  }

  /**
   * Helper method to get taxonomy terms as options for a select list.
   *
   * @param string $vocabulary_id
   *   The vocabulary ID.
   *
   * @param int|null $parent_id
   *   (optional) The parent term ID to filter by.
   *
   * @return array
   *   An array of options suitable for a select list.
   */
  protected function getTaxonomyTermsAsOptions(string $vocabulary_id, ?int $parent_id = NULL): array {
    $options = [];
    $query = $this->entityTypeManager->getStorage('taxonomy_term')->getQuery()
      ->condition('vid', $vocabulary_id)
      ->sort('name')
      ->accessCheck(FALSE);

    if ($parent_id !== NULL) {
      $query->condition('parent', $parent_id);
    }

    $tids = $query->execute();
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadMultiple($tids);

    foreach ($terms as $term) {
      $options[$term->getName()] = $term->getName();
    }
    return $options;
  }

  /**
   * Helper method to get a taxonomy term ID by its name and vocabulary.
   *
   * @param string $name
   *   The name of the term.
   * @param string $vocabulary_id
   *   The vocabulary ID.
   *
   * @return int|null
   *   The term ID if found, NULL otherwise.
   */
  protected function getTermIdByName(string $name, string $vocabulary_id): ?int {
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadByProperties([
      'name' => $name,
      'vid' => $vocabulary_id,
    ]);
    $term = reset($terms);
    return $term ? $term->id() : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attributes']['class'][] = 'form-inline';
    $form['#attributes']['class'][] = 'row';

    $cordoba_tid = $this->getTermIdByName('Córdoba', 'divipola');

    $form['municipio'] = [
      '#type' => 'select',
      '#title' => $this->t('Municipio'),
      '#options' => $this->getTaxonomyTermsAsOptions('divipola', $cordoba_tid),
      '#empty_option' => $this->t('- Select -'),
      '#attributes' => ['class' => ['form-control', 'mb-2', 'mr-sm-2']],
      '#prefix' => '<div class="form-group col-md-4">',
      '#suffix' => '</div>',
    ];

    $form['sector'] = [
      '#type' => 'select',
      '#title' => $this->t('Sector'),
      '#options' => $this->getTaxonomyTermsAsOptions('sectores_clave'),
      '#empty_option' => $this->t('- Select -'),
      '#attributes' => ['class' => ['form-control', 'mb-2', 'mr-sm-2']],
      '#prefix' => '<div class="form-group col-md-4">',
      '#suffix' => '</div>',
    ];

    $form['tecnologias_40'] = [
      '#type' => 'select',
      '#title' => $this->t('Tecnologías 4.0'),
      '#options' => $this->getTaxonomyTermsAsOptions('tecnologias_clave'),
      '#empty_option' => $this->t('- Select -'),
      '#attributes' => ['class' => ['form-control', 'mb-2', 'mr-sm-2']],
      '#prefix' => '<div class="form-group col-md-4">',
      '#suffix' => '</div>',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->messenger()->addStatus($this->t('Your form is being submitted...'));
    // Here you would typically process the form values, e.g.,
    // filter a list of entities based on the selected criteria.
    // For now, we'll just display a message.
    $this->messenger()->addStatus($this->t('Municipio: @municipio, Sector: @sector, Tecnologías 4.0: @tecnologias', [
      '@municipio' => $form_state->getValue('municipio'),
      '@sector' => $form_state->getValue('sector'),
      '@tecnologias' => $form_state->getValue('tecnologias_40'),
    ]));
  }

}