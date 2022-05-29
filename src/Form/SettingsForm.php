<?php

namespace Drupal\payfast_donation_block\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Payfast Donation Block settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'payfast_donation_block_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['payfast_donation_block.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['payfast_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Payfast Settings'),
      // Show collapse icon.
      '#collapsible' => TRUE,
      // Open by default.
      '#collapsed' => FALSE,
      // Place this group in the vertical tabs bar.
      '#group' => 'verticaltabs',
    ];

    $form['payfast_settings']['merchant_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Merchant ID'),
      '#default_value' => $this->config('payfast_donation_block.settings')->get('merchant_id'),
    ];

    $form['payfast_settings']['merchant_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Merchant Key'),
      '#default_value' => $this->config('payfast_donation_block.settings')->get('merchant_key'),
    ];


    $form['payfast_settings']['pass_phase'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Pass phrase'),
      '#default_value' => $this->config('payfast_donation_block.settings')->get('pass_phase'),
    ];

    $form['payfast_settings']['test_mode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Test Mode?'),
      '#default_value' => $this->config('payfast_donation_block.settings')->get('test_mode'),
    ];

    $form['payfast_settings']['onsite_payment'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Onsite payment modal?'),
      '#default_value' => $this->config('payfast_donation_block.settings')->get('onsite_payment'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('payfast_donation_block.settings')
      ->set('base_url', $form_state->getValue('base_url'))
      ->set('username', $form_state->getValue('username'))
      ->set('password', $form_state->getValue('password'))
      ->set('merchant_id', $form_state->getValue('merchant_id'))
      ->set('merchant_key', $form_state->getValue('merchant_key'))
      ->set('pass_phase', $form_state->getValue('pass_phase'))
      ->set('test_mode', $form_state->getValue('test_mode'))
      ->set('onsite_payment', $form_state->getValue('onsite_payment'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
