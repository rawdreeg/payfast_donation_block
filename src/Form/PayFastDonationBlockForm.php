<?php

namespace Drupal\payfast_donation_block\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use PayFast\Auth;
use PayFast\PayFastPayment;

/**
 * Provides a Payfast Donation Block form.
 */
class PayFastDonationBlockForm extends FormBase {
  private $payfast_donation_block_config;
  private $payfast_client;

  /**
   * Constructor
   */
  public function __construct() {
    $this->payfast_donation_block_config = $this->config('payfast_donation_block.settings');
    $this->payfast_client = new PayFastPayment(
      [
        'merchantId' => $this->payfast_donation_block_config->get('merchant_id'),
        'merchantKey' => $this->payfast_donation_block_config->get('merchant_key'),
        'passPhrase' => $this->payfast_donation_block_config->get('pass_phase'),
        'testMode' => (bool) $this->payfast_donation_block_config->get('test_mode'),
      ]
    );
  }


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'payfast_donation_block_pay_fast_donation_block';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['first_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('First name'),
      '#required' => FALSE,
    ];

    $form['last_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Last name'),
      '#required' => FALSE,
    ];


    $form['email_address'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email address'),
      '#required' => TRUE,
    ];

    $form['amount'] = [
      '#type' => 'number',
      '#title' => $this->t('Amount'),
      '#description' => $this->t('Amount in South African Rand'),
      '#required' => TRUE,
    ];

    $form['item_name'] = [
      '#type' => 'hidden',
      '#value' => $this->t('Donation on @sitename', [
        '@username' => $this->config('system.site')->get('name')
      ]),
    ];

    $form['#attached']['library'][] = 'payfast_donation_block/payfast_library';

    if ( $this->payfast_donation_block_config->get('onsite_payment') ) {
      $form['actions'] = [
        '#type' => 'button',
        '#value' => $this->t('Donate!'),
        '#ajax' => [
          'callback' => '::triggerPayment',
        ],
      ];
    } else {
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Donate!'),
        '#ajax' => [
          'callback' => '::initPayfastPaymentForm',
        ]
      ];
      $form['#attached']['library'][] = 'payfast_donation_block/payfast_donation_block';
    }

    $form['message'] = [
      '#type' => 'markup',
      '#markup' => '<div id="payfast_do_onsite_payment"></div>'
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function triggerPayment(array &$form, FormStateInterface $form_state) {

    $response = new AjaxResponse();

    $data = $this->preparePayfastData($form_state);

    $payfast_data = $this->initOnsitePayment($data);

    $response->addCommand(
      new HtmlCommand(
        '#payfast_do_onsite_payment',
        $payfast_data
      ));

    return $response;

  }

  private function initOnsitePayment($data) {
    try {

      // Generate payment identifier
      $identifier = $this->payfast_client->onsite->generatePaymentIdentifier($data);

      if( $identifier !== null ){

        $payfast_modal = '<script type="text/javascript">window.payfast_do_onsite_payment({"uuid":"'.$identifier.'"});</script>';

      }
    }catch (\Exception $e) {

      $payfast_modal = $this->t('Error loading the payment form.');

    }

    return $payfast_modal;

  }

  public function initPayfastPaymentForm ( array &$form, FormStateInterface $form_state ) {
    $data = $this->preparePayfastData($form_state);
    $data['action_url'] = $this->payfast_client::$baseUrl . '/eng/process';

    $response = new AjaxResponse();
    $response->addCommand(new InvokeCommand(NULL, 'processPayfastPaymentForm', [ $data ]));

    return $response;
  }

  private function preparePayfastData ( FormStateInterface $form_state ) {
    if (!$this->payfast_donation_block_config) {
      $this->payfast_donation_block_config =  $this->config('payfast_donation_block.settings');
    }

    if (!$this->payfast_client) {
      $this->payfast_client = new PayFastPayment(
        [
          'merchantId' => $this->payfast_donation_block_config->get('merchant_id'),
          'merchantKey' => $this->payfast_donation_block_config->get('merchant_key'),
          'passPhrase' => $this->payfast_donation_block_config->get('pass_phase'),
          'testMode' => (bool) $this->payfast_donation_block_config->get('test_mode'),
        ]
      );
    }

    $data = [
      'amount' => (float) $form_state->getValue('amount'),
      'email_address' => $form_state->getValue('email_address'),
      'item_name' => $this->t('Donation on @sitename', [
        '@sitename' => $this->config('system.site')->get('name')
      ])->render()
    ];

    if (!empty($form_state->getValue('first_name'))) {
      $data['first_name'] = $form_state->getValue('first_name');
    }

    if (!empty($form_state->getValue('last_name'))) {
      $data['last_name'] = $form_state->getValue('last_name');
    }

    $data['amount'] = number_format( sprintf( '%.2f', $data['amount'] ), 2, '.', '' );
    $data = ['merchant_id' => PayFastPayment::$merchantId, 'merchant_key' => PayFastPayment::$merchantKey] + $data;

    if ( !empty($this->payfast_donation_block_config->get('return_url')) ) {
      $data['return_url'] = $this->payfast_donation_block_config->get('return_url');
    }

    if ( !empty($this->payfast_donation_block_config->get('cancel_url')) ) {
      $data['cancel_url'] = $this->payfast_donation_block_config->get('cancel_url');
    }

    $signature = Auth::generateSignature($data, $this->payfast_client::$passPhrase);
    $data['signature'] = $signature;

    return $data;
  }

  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    // TODO: Implement submitForm() method.
  }

}
