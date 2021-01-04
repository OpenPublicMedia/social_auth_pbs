<?php

namespace Drupal\social_auth_pbs\Controller;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\social_api\Plugin\NetworkManager;
use Drupal\social_auth\Controller\OAuth2ControllerBase;
use Drupal\social_auth\SocialAuthDataHandler;
use Drupal\social_auth\User\UserAuthenticator;
use Drupal\social_auth_pbs\PbsAuthManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Returns responses for Social Auth PBS module routes.
 *
 * This controller handles callbacks for _all variants_ of the PBS Account OAuth
 * implementation (Apple, Facebook, Google, etc.). Even though the authorization
 * process is shared by all variants, it would probably make more sense to break
 * those variants out in to submodules. The current Social API/Social Auth API
 * treats the provider ID as the "module" so this module will use a different
 * string for each variant (e.g. social_auth_pbs, social_auth_pbs_apple, etc.).
 * This can be confusing e.g. in database logging.
 *
 * @see \Drupal\social_auth_pbs\Controller\PbsAppleAuthController
 * @see \Drupal\social_auth_pbs\Controller\PbsFacebookAuthController
 * @see \Drupal\social_auth_pbs\Controller\PbsGoogleAuthController
 */
abstract class PbsAuthControllerBase extends OAuth2ControllerBase implements PbsAuthControllerInterface {

  /**
   * PbsAuthController constructor.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Used for setting messages.
   * @param \Drupal\social_api\Plugin\NetworkManager $network_manager
   *   Used to get an instance of social_auth_pbs network plugin.
   * @param \Drupal\social_auth\User\UserAuthenticator $user_authenticator
   *   Manages user login/registration.
   * @param \Drupal\social_auth_pbs\PbsAuthManager $pbs_auth_manager
   *   Used to manage authentication methods.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   *   Used to access GET parameters.
   * @param \Drupal\social_auth\SocialAuthDataHandler $data_handler
   *   SocialAuthDataHandler object.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   Used to handle metadata for redirection to authentication URL.
   */
  public function __construct(
    MessengerInterface $messenger,
    NetworkManager $network_manager,
    UserAuthenticator $user_authenticator,
    PbsAuthManager $pbs_auth_manager,
    RequestStack $request,
    SocialAuthDataHandler $data_handler,
    RendererInterface $renderer
  ) {
    // Add the provider ID to the session. This is necessary in order to
    // distinguish between variants (Apple, Facebook, Google, etc.) of the PBS
    // Account system. All variants are authorized the same way, but it may be
    // useful to know _which_ variant a particular user is using.
    $provider_id = $data_handler->get('provider_id');
    if (empty($provider_id)) {
      $provider_id = $this->getProviderId();
      $data_handler->set('provider_id', $provider_id);
    }

    parent::__construct(
      'Social Auth PBS',
      $provider_id,
      $messenger,
      $network_manager,
      $user_authenticator,
      $pbs_auth_manager,
      $request,
      $data_handler,
      $renderer
    );

    // Set the session prefix to to the primary controller no matter the
    // variant. The primary controller is used for the all callbacks, so the
    // session prefix must be shared between all variants.
    $this->dataHandler->setSessionPrefix(PbsAuthController::getProviderId());
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger'),
      $container->get('plugin.network.manager'),
      $container->get('social_auth.user_authenticator'),
      $container->get('social_auth_pbs.manager'),
      $container->get('request_stack'),
      $container->get('social_auth.data_handler'),
      $container->get('renderer')
    );
  }

  /**
   * Response for path 'user/login/pbs/callback'.
   *
   * PBS returns the user here after user has authenticated.
   */
  public function callback() {

    /** @var \OpenPublicMedia\OAuth2\Client\Provider\PbsResourceOwner|null $profile */
    $profile = $this->processCallback();

    // If authentication was successful.
    if ($profile !== NULL) {

      // If user information could be retrieved.
      return $this->userAuthenticator->authenticateUser(
        $profile->getName(),
        $profile->getEmail(),
        $profile->getId(),
        $this->providerManager->getAccessToken(),
        $profile->getThumbnailUrl(),
        $profile->toArray()
      );
    }

    return $this->redirect('user.login');
  }

}
