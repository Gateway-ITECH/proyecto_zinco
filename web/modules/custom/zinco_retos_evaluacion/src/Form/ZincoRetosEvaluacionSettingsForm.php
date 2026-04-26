<?php

declare(strict_types=1);

namespace Drupal\zinco_retos_evaluacion\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration form for a Evaluación de retos entity type.
 */
final class ZincoRetosEvaluacionSettingsForm extends FormBase
{

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string
  {
    return 'zinco_retos_evaluacion_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array
  {

    $form['settings'] = [
      '#markup' => $this->t('Settings form for a Evaluación de retos entity type.'),
    ];

    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Save'),
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void
  {
    $this->messenger()->addStatus($this->t('The configuration has been updated.'));
  }

}
