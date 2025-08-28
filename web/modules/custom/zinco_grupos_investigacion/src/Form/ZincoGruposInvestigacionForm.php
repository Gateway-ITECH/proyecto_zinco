<?php

declare(strict_types=1);

namespace Drupal\zinco_grupos_investigacion\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the zinco grupos investigacion entity edit forms.
 */
final class ZincoGruposInvestigacionForm extends ContentEntityForm {

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
        $this->messenger()->addStatus($this->t('New zinco grupos investigacion %label has been created.', $message_args));
        $this->logger('zinco_grupos_investigacion')->notice('New zinco grupos investigacion %label has been created.', $logger_args);
        break;

      case SAVED_UPDATED:
        $this->messenger()->addStatus($this->t('The zinco grupos investigacion %label has been updated.', $message_args));
        $this->logger('zinco_grupos_investigacion')->notice('The zinco grupos investigacion %label has been updated.', $logger_args);
        break;

      default:
        throw new \LogicException('Could not save the entity.');
    }

    $form_state->setRedirectUrl($this->entity->toUrl());

    return $result;
  }

}
