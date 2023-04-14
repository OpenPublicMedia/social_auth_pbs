<?php

namespace Drupal\social_auth_pbs\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\social_api\Plugin\NetworkManager;
use Drupal\social_auth\Controller\OAuth2ControllerBase;
use Drupal\social_auth\Plugin\Network\NetworkInterface;
use Drupal\social_auth\SocialAuthDataHandler;
use Drupal\social_auth\User\UserAuthenticator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Returns responses for Social Auth PBS module routes.
 *
 * This controller handles callbacks for _all variants_ of the PBS Account OAuth
 * implementation (Apple, Facebook, Google, etc.). Social Auth API treats the
 * provider ID as the "module" so this module will use a different string for
 * each variant (e.g. social_auth_pbs, social_auth_pbs_apple, etc.).
 *
 * For this to work each provider must use a distinct controller class for the
 * redirect so the provider ID can be stored in the session before redirecting
 * the user.
 *
 * @see \Drupal\social_auth_pbs\Controller\PbsAppleAuthController
 * @see \Drupal\social_auth_pbs\Controller\PbsFacebookAuthController
 * @see \Drupal\social_auth_pbs\Controller\PbsGoogleAuthController
 * @see \Drupal\social_auth_pbs\Controller\PbsRegisterController
 */
abstract class PbsAuthControllerBase extends OAuth2ControllerBase implements PbsAuthControllerInterface {

  /**
   * PbsAuthControllerBase constructor.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    LoggerChannelFactoryInterface $logger,
    MessengerInterface $messenger,
    NetworkManager $network_manager,
    UserAuthenticator $user_authenticator,
    RequestStack $request,
    SocialAuthDataHandler $data_handler,
    RendererInterface $renderer,
    EventDispatcherInterface $dispatcher,
    CurrentRouteMatch $current_route_match
  ) {
    // Set the session prefix to the primary controller no matter the variant.
    // The primary controller is used for the all callbacks, so the session
    // prefix must be shared between all variants.
    $data_handler->setSessionPrefix(PbsAuthController::getProviderId());

    // Add the provider ID to the session and use it on callback. This is
    // necessary in order to distinguish between variants (Apple, Facebook,
    // Google, etc.) of the PBS Account system. All variants are authorized the
    // same way, but it may be useful to know _which_ variant a particular user
    // has used.
    if ($current_route_match->getRouteName() !== 'social_auth_pbs.network.callback') {
      $data_handler->set('provider_id', $this->getProviderId());
    }
    parent::__construct(
      $config_factory,
      $logger,
      $messenger,
      $network_manager,
      $user_authenticator,
      $request,
      $data_handler,
      $renderer,
      $dispatcher
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('config.factory'),
      $container->get('logger.factory'),
      $container->get('messenger'),
      $container->get('plugin.network.manager'),
      $container->get('social_auth.user_authenticator'),
      $container->get('request_stack'),
      $container->get('social_auth.data_handler'),
      $container->get('renderer'),
      $container->get('event_dispatcher'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function callback(NetworkInterface $network): RedirectResponse {
    $provider_id = $this->dataHandler->get('provider_id') ?? $this->getProviderId();
    if ($this->networkManager->hasDefinition($provider_id)) {
      /** @var \Drupal\social_auth\Plugin\Network\NetworkInterface $network */
      $network = $this->networkManager->createInstance($provider_id);
    }
    else {
      throw new \RuntimeException("Invalid provider ID: $provider_id");
    }
    return parent::callback($network);
  }

}
