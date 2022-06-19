<?php

namespace Drupal\payfast_donation_block\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Installer\InstallerKernel;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\State\State;
use PayFast\Auth;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Payfast Donation Block event subscriber.
 */
class PayfastDonationBlockSubscriber implements EventSubscriberInterface {

  /**
   * The logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\State
   */
  private $state;

  /**
   * Current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  private $routeMatch;

  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $configFactory;

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  private $messenger;

  /**
   * Constructs event subscriber.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger service.
   * @param \Drupal\Core\State\State $state
   *   The state service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match interface.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(LoggerChannelFactoryInterface $logger, State $state, RouteMatchInterface $route_match, ConfigFactoryInterface $configFactory, MessengerInterface $messenger) {
    $this->logger = $logger;
    $this->state = $state;
    $this->routeMatch = $route_match;
    $this->configFactory = $configFactory;
    $this->messenger = $messenger;
  }

  /**
   * Kernel request event handler.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   Response event.
   */
  public function onKernelRequest(RequestEvent $event) {

    // Make sure front page module is not run when using cli (drush).
    // Make sure front page module does not run when installing Drupal either.
    if (PHP_SAPI === 'cli' || InstallerKernel::installationAttempted()) {
      return;
    }

    // Don't run when site is in maintenance mode.
    if ($this->state->get('system.maintenance_mode')) {
      return;
    }

    // Ignore non index.php requests (like cron).
    if (!empty($_SERVER['SCRIPT_FILENAME'])
      && realpath(DRUPAL_ROOT . '/index.php') != realpath($_SERVER['SCRIPT_FILENAME'])) {
      return;
    }

    if ($this->routeMatch->getRouteName() === 'payfast_donation_block.confirm') {
      // Validate request.
      $request_data = $event->getRequest()->request->all();
      $payfast_donation_block_config = $this->configFactory
        ->get('payfast_donation_block.settings');

      if (empty($request_data)) {
        throw new AccessDeniedHttpException();
      }
      // Validate signature.
      if (!$request_data['signature'] === Auth::generateApiSignature($request_data, $payfast_donation_block_config->get('pass_phase'))) {
        $this->logger->get('payfast_donation_block')->debug('Could not verify signature', $request_data);
        throw new AccessDeniedHttpException();
      }

    }

  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      KernelEvents::REQUEST => ['onKernelRequest'],
    ];
  }

}
