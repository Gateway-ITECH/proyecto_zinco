<?php

namespace Drupal\zinco_front\Service;

use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Component\Utility\Html;

/**
 * Service to handle email sending with Twig templates.
 */
class MailService {
  use StringTranslationTrait;

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a new MailService.
   */
  public function __construct(MailManagerInterface $mail_manager, RendererInterface $renderer, ConfigFactoryInterface $config_factory, LanguageManagerInterface $language_manager) {
    $this->mailManager = $mail_manager;
    $this->renderer = $renderer;
    $this->configFactory = $config_factory;
    $this->languageManager = $language_manager;
  }

  /**
   * Sends a templated email.
   *
   * @param string $to
   *   Recipient email address.
   * @param string $subject
   *   Email subject.
   * @param string $theme
   *   The theme/template name to use.
   * @param array $variables
   *   Variables to pass to the template.
   *
   * @return array
   *   The result of the mail manager call.
   */
  public function sendTemplatedEmail($to, $subject, $theme, array $variables = []) {
    $langcode = $this->languageManager->getCurrentLanguage()->getId();
    
    // Add default variables like site name.
    $variables['site_name'] = $this->configFactory->get('system.site')->get('name');
    
    $build = [
      '#theme' => $theme,
      '#variables' => $variables,
    ];
    
    $rendered_body = $this->renderer->renderPlain($build);

    $params = [
      'subject' => $subject,
      'body' => $rendered_body,
    ];

    return $this->mailManager->mail('zinco_front', 'templated_email', $to, $langcode, $params, NULL, TRUE);
  }

}
