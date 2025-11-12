<?php

namespace Drupal\zinco_admin\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Zinco Admin settings for this site.
 */
class ZincoAdminSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'zinco_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['zinco_admin.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('zinco_admin.settings');

    $form['example'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Example setting'),
      '#description' => $this->t('This is an example setting for Zinco Admin.'),
      '#default_value' => $config->get('example'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('zinco_admin.settings')
      ->set('example', $form_state->getValue('example'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}