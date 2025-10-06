<?php

declare(strict_types=1);

namespace Drupal\zinco_proyectos_software\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the zinco proyectos software entity edit forms.
 */
final class ZincoProyectosSoftwareForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);

    $message_args = ['%label' => $this->entity->toLink()->toString()];
    $logger_args = [
      '%label' => $this->entity->label(),
      'link' => $this->entity->toLink($this->t('View'))->toString(),
    ];

    switch ($result) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('New zinco proyectos software %label has been created.', $message_args));
        $this->logger('zinco_proyectos_software')->notice('New zinco proyectos software %label has been created.', $logger_args);
        break;

      case SAVED_UPDATED:
        $this->messenger()->addStatus($this->t('The zinco proyectos software %label has been updated.', $message_args));
        $this->logger('zinco_proyectos_software')->notice('The zinco proyectos software %label has been updated.', $logger_args);
        break;

      default:
        throw new \LogicException('Could not save the entity.');
    }

    $form_state->setRedirectUrl($this->entity->toUrl());

    return $result;
  }

}
