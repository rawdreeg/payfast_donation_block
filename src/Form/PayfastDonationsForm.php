<?php

namespace Drupal\payfast_donation_block\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the payfast donations entity edit forms.
 */
class PayfastDonationsForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {

    $entity = $this->getEntity();
    $result = $entity->save();
    $link = $entity->toLink($this->t('View'))->toRenderable();

    $message_arguments = ['%label' => $this->entity->label()];
    $logger_arguments = $message_arguments + ['link' => render($link)];

    if ($result == SAVED_NEW) {
      $this->messenger()->addStatus($this->t('New payfast donations %label has been created.', $message_arguments));
      $this->logger('payfast_donation_block')->notice('Created new payfast donations %label', $logger_arguments);
    }
    else {
      $this->messenger()->addStatus($this->t('The payfast donations %label has been updated.', $message_arguments));
      $this->logger('payfast_donation_block')->notice('Updated new payfast donations %label.', $logger_arguments);
    }

    $form_state->setRedirect('entity.payfast_donations.canonical', ['payfast_donations' => $entity->id()]);
  }

}
