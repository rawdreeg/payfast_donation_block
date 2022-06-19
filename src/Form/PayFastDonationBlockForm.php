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
  /**
   * Donation config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig*/
  private $payfastDonationBlockConfig;
  /**
   * Payfast client.
   *
   * @var \PayFast\PayFastPayment*/
  private $payfastClient;
  /**
   * Request service.
   *
   * @var \Symfony\Component\HttpFoundation\Request|null*/
  private $request;

  /**
   * Constructor.
   */
  public function __construct(RequestStack $request_stack) {
    $this->payfastDonationBlockConfig = $this->config('payfast_donation_block.settings');
    $this->payfastClient = new PayFastPayment(
      [
        'merchantId' => $this->payfastDonationBlockConfig->get('merchant_id'),
        'merchantKey' => $this->payfastDonationBlockConfig->get('merchant_key'),
        'passPhrase' => $this->payfastDonationBlockConfig->get('pass_phase'),
        'testMode' => (bool) $this->payfastDonationBlockConfig->get('test_mode'),
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
        '@username' => $this->config('system.site')->get('name'),
      ]),
    ];

    $form['#attached']['library'][] = 'payfast_donation_block/payfast_library';

    if ($this->payfastDonationBlockConfig->get('onsite_payment')) {
      $form['actions'] = [
        '#type' => 'button',
        '#value' => $this->t('Donate!'),
        '#ajax' => [
          'callback' => '::triggerPayment',
        ],
      ];
    }
    else {
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Donate!'),
        '#ajax' => [
          'callback' => '::initPayfastPaymentForm',
        ],
      ];
      $form['#attached']['library'][] = 'payfast_donation_block/payfast_donation_block';
    }

    $form['message'] = [
      '#type' => 'markup',
      '#markup' => '<div id="payfast_do_onsite_payment"></div>',
    ];

    return $form;
  }

  /**
   * Triggers the payment modal.
   *
   * @param array $form
   *   - Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   - Form state object.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   - Ajax response with onsite trigger script.
   *
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
   * Builds and return js init for modal.
   *
   * @param array $data
   *   - Payfast data array.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string
   *   - The JS script or an error message.
   */
  private function initOnsitePayment(array $data) {
    try {

      // Generate payment identifier.
      $identifier = $this->payfastClient->onsite->generatePaymentIdentifier($data);

      if ($identifier !== NULL) {

        $payfast_modal = '<script type="text/javascript">window.payfast_do_onsite_payment({"uuid":"' . $identifier . '"});</script>';

      }
    }
    catch (\Exception $e) {

      $payfast_modal = $this->t('Error loading the payment form.');

    }

    return $payfast_modal;

  }

  /**
   * Generate and return payfast external form.
   *
   * @param array $form
   *   - Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   - Form state object.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   - Returns the external payment HTML.
   *
   * @throws \PayFast\Exceptions\InvalidRequestException
   */
  public function initPayfastPaymentForm(array &$form, FormStateInterface $form_state) {
    $data = $this->preparePayfastData($form_state);
    $data['action_url'] = $this->payfastClient::$baseUrl . '/eng/process';

    $response = new AjaxResponse();
    $response->addCommand(new InvokeCommand(NULL, 'processPayfastPaymentForm', [$data]));

    return $response;
  }

  /**
   * Prepared the payfast data.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   - Form state object.
   *
   * @return array
   *   - Returns the normalized data to submit to Payfast.
   *
   * @throws \PayFast\Exceptions\InvalidRequestException
   */
  private function preparePayfastData(FormStateInterface $form_state) {
    if (!$this->payfastDonationBlockConfig) {
      $this->payfastDonationBlockConfig = $this->config('payfast_donation_block.settings');
    }

    if (!$this->payfastClient) {
      $this->payfastClient = new PayFastPayment(
        [
          'merchantId' => $this->payfastDonationBlockConfig->get('merchant_id'),
          'merchantKey' => $this->payfastDonationBlockConfig->get('merchant_key'),
          'passPhrase' => $this->payfastDonationBlockConfig->get('pass_phase'),
          'testMode' => (bool) $this->payfastDonationBlockConfig->get('test_mode'),
        ]
      );
    }

    $data = [
      'amount' => (float) $form_state->getValue('amount'),
      'email_address' => $form_state->getValue('email_address'),
      'item_name' => $this->t('Donation on @sitename', [
        '@sitename' => $this->config('system.site')->get('name'),
      ])->render(),
    ];

    if (!empty($form_state->getValue('first_name'))) {
      $data['first_name'] = $form_state->getValue('first_name');
    }

    if (!empty($form_state->getValue('last_name'))) {
      $data['last_name'] = $form_state->getValue('last_name');
    }

    $data['amount'] = number_format(sprintf('%.2f', $data['amount']), 2, '.', '');
    $data = [
      'merchant_id' => PayFastPayment::$merchantId,
      'merchant_key' => PayFastPayment::$merchantKey,
    ] + $data;

    $data['return_url'] = !empty($this->payfastDonationBlockConfig->get('return_url'))
      ? $this->payfastDonationBlockConfig->get('return_url') . '?payment_status=success'
      : $this->request->getSchemeAndHttpHost() . '?payment_status=success';

    $data['cancel_url'] = !empty($this->payfastDonationBlockConfig->get('cancel_url'))
      ? $this->payfastDonationBlockConfig->get('cancel_url') . '?payment_status=failed'
      : $this->request->getSchemeAndHttpHost() . '?payment_status=failed';

    if ($this->payfastDonationBlockConfig->get('save_donation')) {
      // Add the the itn notify route.
      $data['notify_url'] = Url::fromRoute('payfast_donation_block.confirm', [], ['absolute' => TRUE])->toString();
      // Log the payment.
      /** @var \Drupal\payfast_donation_block\Entity\PayfastDonations $saved_donation */
      $saved_donation = PayfastDonations::create(
          [
            'title' => $data['item_name'],
            'first_name' => $form_state->getValue('first_name'),
            'last_name' => $form_state->getValue('last_name'),
            'email' => $data['email_address'],
            'donation_amount' => $data['amount'],
            'payment_status' => 'pending',
          ]
      );
      $saved_donation->save();
      $data['custom_str1'] = $saved_donation->id();
    }

    $signature = Auth::generateSignature($data, $this->payfastClient::$passPhrase);
    $data['signature'] = $signature;

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // @todo Implement submitForm() method.
  }

}
