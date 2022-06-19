<?php

namespace Drupal\payfast_donation_block\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\payfast_donation_block\Entity\PayfastDonations;
use PayFast\Auth;
use PayFast\PayFastPayment;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a Payfast Donation Block form.
 */
class PayFastDonationBlockForm extends FormBase {
  /** @var \Drupal\Core\Config\ImmutableConfig $payfast_donation_block_config */
  private $payfast_donation_block_config;
  /** @var PayFastPayment $payfast_client*/
  private $payfast_client;
  /** @var \Symfony\Component\HttpFoundation\Request|null $request */
  private $request;

  /**
   * Constructor
   */
  public function __construct(RequestStack $request_stack) {
    $this->payfast_donation_block_config = $this->config('payfast_donation_block.settings');
    $this->payfast_client = new PayFastPayment(
      [
        'merchantId' => $this->payfast_donation_block_config->get('merchant_id'),
        'merchantKey' => $this->payfast_donation_block_config->get('merchant_key'),
        'passPhrase' => $this->payfast_donation_block_config->get('pass_phase'),
        'testMode' => (bool) $this->payfast_donation_block_config->get('test_mode'),
      ]
    );
    $this->request = $request_stack->getCurrentRequest();

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack')
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
   * @param array $form
   * @param FormStateInterface $form_state
   * @return AjaxResponse
   * @throws \PayFast\Exceptions\InvalidRequestException
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

  /**
   * @param $data
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string
   */
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

  /**
   * @param array $form
   * @param FormStateInterface $form_state
   * @return AjaxResponse
   * @throws \PayFast\Exceptions\InvalidRequestException
   */
  public function initPayfastPaymentForm ( array &$form, FormStateInterface $form_state ) {
    $data = $this->preparePayfastData($form_state);
    $data['action_url'] = $this->payfast_client::$baseUrl . '/eng/process';

    $response = new AjaxResponse();
    $response->addCommand(new InvokeCommand(NULL, 'processPayfastPaymentForm', [ $data ]));

    return $response;
  }

  /**
   * @param FormStateInterface $form_state
   * @return array
   * @throws \PayFast\Exceptions\InvalidRequestException
   */
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

    $data['return_url'] = !empty($this->payfast_donation_block_config->get('return_url'))
      ? $this->payfast_donation_block_config->get('return_url') . '?payment_status=success' : $this->request->getSchemeAndHttpHost() . '?payment_status=success';

    $data['cancel_url'] = !empty($this->payfast_donation_block_config->get('cancel_url'))
      ? $this->payfast_donation_block_config->get('cancel_url') . '?payment_status=failed' : $this->request->getSchemeAndHttpHost() . '?payment_status=failed';

    if ($this->payfast_donation_block_config->get('save_donation')) {
      // Add the the itn notify route
      $data['notify_url'] = Url::fromRoute('payfast_donation_block.confirm', [] ,[ 'absolute' => TRUE ])->toString();
      // Log the payment.
      /** @var PayfastDonations $saved_donation */
      $saved_donation = PayfastDonations::create(
          [
            'title' => $data['item_name'],
            'first_name' =>  $form_state->getValue('first_name'),
            'last_name' =>  $form_state->getValue('last_name'),
            'email' => $data['email_address'],
            'donation_amount' => $data['amount'],
            'payment_status' => 'pending',
          ]
      );
      $saved_donation->save();
      $data['custom_str1'] = $saved_donation->id();
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
