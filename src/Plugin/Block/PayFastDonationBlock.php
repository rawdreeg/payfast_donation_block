<?php

namespace Drupal\payfast_donation_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a Payfast Donation block.
 *
 * @Block(
 *   id = "payfast_donation_block",
 *   admin_label = @Translation("Payfast Donation Block"),
 *   category = @Translation("Payfast Donation Block")
 * )
 */
class PayFastDonationBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $form = \Drupal::formBuilder()->getForm('Drupal\payfast_donation_block\Form\PayFastDonationBlockForm');
    return $form;
  }

}
