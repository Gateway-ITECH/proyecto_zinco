<?php

namespace Drupal\zinco_front\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;

/**
 * Administrative configuration form for the secondary logo in the site header.
 */
class SecondaryLogoSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'zinco_front_secondary_logo_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['bootstrap_barrio_subtheme.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('bootstrap_barrio_subtheme.settings');

    $form['intro'] = [
      '#markup' => '<div class="messages messages--status"><p>' . $this->t('Desde esta pantalla puede configurar el logotipo secundario que aparece en la barra de navegación principal al lado del logotipo de Unicórdoba, así como su hipervínculo de destino.') . '</p></div>',
    ];

    $form['secondary_logo_enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Habilitar segundo logotipo'),
      '#description' => $this->t('Active esta casilla para hacer visible el segundo logotipo en el encabezado del sitio web.'),
      '#default_value' => $config->get('secondary_logo_enable') ?? FALSE,
    ];

    $form['settings_container'] = [
      '#type' => 'container',
      '#states' => [
        'visible' => [
          ':input[name="secondary_logo_enable"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['settings_container']['secondary_logo_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Hipervínculo / Enlace del logotipo'),
      '#description' => $this->t('Dirección web (URL) a la que dirigirá al usuario cuando haga clic sobre el logotipo (ejemplo: <code>https://www.unicordoba.edu.co</code>, <code>https://minciencias.gov.co</code> o una ruta interna como <code>/actores</code>).'),
      '#default_value' => $config->get('secondary_logo_url') ?? '',
      '#placeholder' => 'https://...',
    ];

    $form['settings_container']['secondary_logo_target_blank'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Abrir hipervínculo en una pestaña nueva'),
      '#description' => $this->t('Recomendado si el hipervínculo dirige a un sitio web externo.'),
      '#default_value' => $config->get('secondary_logo_target_blank') ?? TRUE,
    ];

    $form['settings_container']['secondary_logo_alt'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Texto alternativo y descripción (Alt / Title)'),
      '#description' => $this->t('Texto para accesibilidad y descripción que aparece al pasar el cursor sobre la imagen.'),
      '#default_value' => $config->get('secondary_logo_alt') ?? '',
      '#placeholder' => $this->t('Ej. Universidad de Córdoba / Institución aliada'),
    ];

    $form['settings_container']['secondary_logo_image'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Subir imagen del logotipo'),
      '#description' => $this->t('Suba un archivo de imagen en formato PNG, SVG, JPG, JPEG o WEBP. Preferiblemente con fondo transparente.'),
      '#upload_location' => 'public://logos/',
      '#upload_validators' => [
        'FileExtension' => ['extensions' => 'png gif jpg jpeg svg webp'],
      ],
      '#default_value' => $config->get('secondary_logo_image') ? (array) $config->get('secondary_logo_image') : [],
    ];

    $form['settings_container']['secondary_logo_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('O especificar ruta/URL externa de la imagen'),
      '#description' => $this->t('Opcional si no desea subir el archivo: escriba la ruta (ej. <code>public://logos/logo.png</code> o una URL web <code>https://ejemplo.com/logo.png</code>). Si sube un archivo arriba, tendrá prioridad.'),
      '#default_value' => $config->get('secondary_logo_path') ?? '',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory()->getEditable('bootstrap_barrio_subtheme.settings');

    $fids = $form_state->getValue('secondary_logo_image');
    if (!empty($fids)) {
      $fid = is_array($fids) ? reset($fids) : $fids;
      if ($fid) {
        $file = File::load($fid);
        if ($file) {
          $file->setPermanent();
          $file->save();
          \Drupal::service('file.usage')->add($file, 'bootstrap_barrio_subtheme', 'theme', 1);
        }
      }
    }

    $config
      ->set('secondary_logo_enable', (bool) $form_state->getValue('secondary_logo_enable'))
      ->set('secondary_logo_url', trim($form_state->getValue('secondary_logo_url') ?? ''))
      ->set('secondary_logo_target_blank', (bool) $form_state->getValue('secondary_logo_target_blank'))
      ->set('secondary_logo_alt', trim($form_state->getValue('secondary_logo_alt') ?? ''))
      ->set('secondary_logo_image', $form_state->getValue('secondary_logo_image'))
      ->set('secondary_logo_path', trim($form_state->getValue('secondary_logo_path') ?? ''))
      ->save();

    // Clear render cache so the updated branding block displays immediately.
    \Drupal::service('cache.render')->invalidateAll();

    $this->messenger()->addStatus($this->t('La configuración del logotipo secundario ha sido guardada exitosamente.'));

    parent::submitForm($form, $form_state);
  }

}
