<?php

namespace Drupal\zinco_etl\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\zinco_etl\Service\CvlacScraperrService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a CvLAC Scraper form.
 */
class CvlacScraperForm extends FormBase {

  /**
   * The CvLAC Scraper service.
   *
   * @var \Drupal\zinco_etl\Service\CvlacScraperrService
   */
  protected $cvlacScraperService;

  /**
   * Constructs a new CvlacScraperForm.
   *
   * @param \Drupal\zinco_etl\Service\CvlacScraperrService $cvlac_scraper_service
   *   The CvLAC Scraper service.
   */
  public function __construct(CvlacScraperrService $cvlac_scraper_service) {
    $this->cvlacScraperService = $cvlac_scraper_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('zinco_etl.cvlac_scraperr')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cvlac_scraper_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['cvlac_url'] = [
      '#type' => 'url',
      '#title' => $this->t('URL de CvLAC'),
      '#description' => $this->t('Ingrese la URL del visualizador de CvLAC (generarCurriculoCv.do)'),
      '#required' => TRUE,
      '#default_value' => 'https://scienti.minciencias.gov.co/cvlac/visualizador/generarCurriculoCv.do?cod_rh=0000155845',
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Ejecutar Análisis'),
      '#button_type' => 'primary',
    ];

    // Show result if available in storage.
    $result = $form_state->get('articles_found');
    if ($result !== NULL) {
      $form['result_container'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['messages', 'messages--status']],
        '#weight' => -10,
      ];
      $form['result_container']['result'] = [
        '#type' => 'item',
        '#markup' => $this->t('Total de artículos detectados: <strong>@count</strong>', ['@count' => $result]),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $url = $form_state->getValue('cvlac_url');
    $count = $this->cvlacScraperService->countResearchArticles($url);

    if ($count === -1) {
      $this->messenger()->addError($this->t('Hubo un error al procesar la URL. Verifique los logs para más detalles.'));
    }
    else {
      $this->messenger()->addStatus($this->t('Análisis completado con éxito.'));
      $form_state->set('articles_found', $count);
      $form_state->setRebuild();
    }
  }

}
