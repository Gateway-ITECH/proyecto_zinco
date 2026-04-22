<?php

declare(strict_types=1);

namespace Drupal\zinco_retos_soluciones\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;

/**
 * Form controller for the zinco retos soluciones entity edit forms for actors.
 */
class ZincoRetosSolucionesActorForm extends ZincoRetosSolucionesForm
{

  public function save(array $form, FormStateInterface $form_state): int
  {
    $result = parent::save($form, $form_state);

    $message_args = ['%label' => $this->entity->toLink()->toString()];
    $logger_args = [
      '%label' => $this->entity->label(),
      'link' => $this->entity->toLink($this->t('View'))->toString(),
    ];

    switch ($result) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('New zinco retos soluciones %label has been created.', $message_args));
        $this->logger('zinco_retos_soluciones')->notice('New zinco retos soluciones %label has been created.', $logger_args);
        break;

      case SAVED_UPDATED:
        $this->messenger()->addStatus($this->t('The zinco retos soluciones %label has been updated.', $message_args));
        $this->logger('zinco_retos_soluciones')->notice('The zinco retos soluciones %label has been updated.', $logger_args);
        break;

      default:
        throw new \LogicException('Could not save the entity.');
    }

    $form_state->setRedirectUrl($this->entity->toUrl());

    return $result;
  }


}
