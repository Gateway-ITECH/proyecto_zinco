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

    // Show results if available in storage.
    $scraped_count = $form_state->get('articles_scraped');
    $db_count = $form_state->get('articles_db');

    if ($scraped_count !== NULL || $db_count !== NULL) {
      $form['result_container'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['messages', 'messages--status']],
        '#weight' => -10,
      ];

      if ($scraped_count !== NULL) {
        $form['result_container']['scraped'] = [
          '#type' => 'item',
          '#markup' => $this->t('Artículos detectados por Scraping (CvLAC en vivo): <strong>@count</strong>', ['@count' => $scraped_count]),
        ];
      }

      if ($db_count !== NULL) {
        $form['result_container']['db'] = [
          '#type' => 'item',
          '#markup' => $this->t('Artículos en Base de Datos (Convocatoria 894 de 2021): <strong>@count</strong>', ['@count' => $db_count]),
        ];
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $url = $form_state->getValue('cvlac_url');
    
    // 1. Scraping en vivo.
    $scraped_count = $this->cvlacScraperService->countResearchArticles($url);
    
    // 2. Consulta en base de datos.
    $db_count = $this->cvlacScraperService->countArticlesFromDatabase($url);

    if ($scraped_count === -1) {
      $this->messenger()->addError($this->t('Hubo un error al realizar el scraping de la URL.'));
    }
    else {
      $this->messenger()->addStatus($this->t('Análisis de CvLAC completado.'));
    }

    $form_state->set('articles_scraped', $scraped_count !== -1 ? $scraped_count : 0);
    $form_state->set('articles_db', $db_count);
    $form_state->setRebuild();
  }

}
