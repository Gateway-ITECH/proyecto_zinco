<?php

namespace Drupal\zinco_front\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a Zinco Front wizard form.
 */
class ZincoWizardForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'zinco_front_wizard_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $step = $form_state->get('step') ?: 1;
    $form_state->set('step', $step);

    $form['#prefix'] = '<div id="zinco-wizard-form-wrapper">';
    $form['#suffix'] = '</div>';

    $form['message'] = [
      '#markup' => $this->t('Step @step of 2', ['@step' => $step]),
    ];

    if ($step == 1) {
      $form['field_step1'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Field for Step 1'),
        '#required' => TRUE,
        '#default_value' => $form_state->getValue('field_step1', ''),
      ];
      $form['actions']['next'] = [
        '#type' => 'submit',
        '#value' => $this->t('Next'),
        '#button_type' => 'primary',
        '#submit' => ['::nextStepSubmit'],
        '#ajax' => [
          'callback' => '::ajaxFormCallback',
          'wrapper' => 'zinco-wizard-form-wrapper',
        ],
      ];
    }
    elseif ($step == 2) {
      $form['field_step2'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Field for Step 2'),
        '#required' => TRUE,
        '#default_value' => $form_state->getValue('field_step2', ''),
      ];
      $form['actions']['previous'] = [
        '#type' => 'submit',
        '#value' => $this->t('Previous'),
        '#submit' => ['::previousStepSubmit'],
        '#ajax' => [
          'callback' => '::ajaxFormCallback',
          'wrapper' => 'zinco-wizard-form-wrapper',
        ],
      ];
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Submit'),
        '#button_type' => 'primary',
        '#ajax' => [
          'callback' => '::ajaxFormCallback',
          'wrapper' => 'zinco-wizard-form-wrapper',
        ],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $step = $form_state->get('step');

    if ($step == 1 && $form_state->getTriggeringElement()['#id'] == 'edit-next') {
      if (empty($form_state->getValue('field_step1'))) {
        $form_state->setErrorByName('field_step1', $this->t('Field for Step 1 is required.'));
      }
    }
    elseif ($step == 2 && $form_state->getTriggeringElement()['#id'] == 'edit-submit') {
      if (empty($form_state->getValue('field_step2'))) {
        $form_state->setErrorByName('field_step2', $this->t('Field for Step 2 is required.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->messenger()->addStatus($this->t('Wizard form submitted successfully!'));
    $this->messenger()->addStatus($this->t('Step 1 value: @value1', ['@value1' => $form_state->getValue('field_step1')]));
    $this->messenger()->addStatus($this->t('Step 2 value: @value2', ['@value2' => $form_state->getValue('field_step2')]));
    $form_state->setRedirect('<front>');
  }

  /**
   * Submit handler for the "Next" button.
   */
  public function nextStepSubmit(array &$form, FormStateInterface $form_state) {
    $form_state->set('step', 2);
    $form_state->setRebuild(TRUE);
  }

  /**
   * Submit handler for the "Previous" button.
   */
  public function previousStepSubmit(array &$form, FormStateInterface $form_state) {
    $form_state->set('step', 1);
    $form_state->setRebuild(TRUE);
  }

  /**
   * AJAX callback for the form.
   */
  public function ajaxFormCallback(array &$form, FormStateInterface $form_state) {
    return $form;
  }

}