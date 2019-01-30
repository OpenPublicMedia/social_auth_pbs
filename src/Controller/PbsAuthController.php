<?php

namespace Drupal\social_auth_pbs\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\social_api\Plugin\NetworkManager;
use Drupal\social_auth\SocialAuthDataHandler;
use Drupal\social_auth\SocialAuthUserManager;
use Drupal\social_auth_pbs\PbsAuthManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Returns responses for Simple Auth PBS module routes.
 */
class PbsAuthController extends ControllerBase {

  /**
   * The network plugin manager.
   *
   * @var \Drupal\social_api\Plugin\NetworkManager
   */
  private $networkManager;

  /**
   * The user manager.
   *
   * @var \Drupal\social_auth\SocialAuthUserManager
   */
  private $userManager;

  /**
   * The PBS authentication manager.
   *
   * @var \Drupal\social_auth_pbs\PbsAuthManager
   */
  private $pbsAuthManager;

  /**
   * Used to access GET parameters.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  private $request;

  /**
   * The Social Auth Data Handler.
   *
   * @var \Drupal\social_auth\SocialAuthDataHandler
   */
  private $dataHandler;


  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * PbsAuthController constructor.
   *
   * @param \Drupal\social_api\Plugin\NetworkManager $network_manager
   *   Used to get an instance of social_auth_pbs network plugin.
   * @param \Drupal\social_auth\SocialAuthUserManager $user_manager
   *   Manages user login/registration.
   * @param \Drupal\social_auth_pbs\PbsAuthManager $pbs_auth_manager
   *   Used to manage authentication methods.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   *   Used to access GET parameters.
   * @param \Drupal\social_auth\SocialAuthDataHandler $social_auth_data_handler
   *   SocialAuthDataHandler object.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   Used for logging errors.
   */
  public function __construct(NetworkManager $network_manager,
                              SocialAuthUserManager $user_manager,
                              PbsAuthManager $pbs_auth_manager,
                              RequestStack $request,
                              SocialAuthDataHandler $social_auth_data_handler,
                              LoggerChannelFactoryInterface $logger_factory) {

    $this->networkManager = $network_manager;
    $this->userManager = $user_manager;
    $this->pbsAuthManager = $pbs_auth_manager;
    $this->request = $request;
    $this->dataHandler = $social_auth_data_handler;
    $this->loggerFactory = $logger_factory;

    // Sets the plugin id.
    $this->userManager->setPluginId('social_auth_pbs');

    // Sets the session keys to nullify if user could not logged in.
    $this->userManager->setSessionKeysToNullify(['access_token', 'oauth2state']);
    $this->setting = $this->config('social_auth_pbs.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.network.manager'),
      $container->get('social_auth.user_manager'),
      $container->get('social_auth_pbs.manager'),
      $container->get('request_stack'),
      $container->get('social_auth.data_handler'),
      $container->get('logger.factory')
    );
  }

  /**
   * Response for path 'user/login/pbs'.
   *
   * Redirects the user to PBS for authentication.
   */
  public function redirectToPbs() {
    /* @var \CascadePublicMedia\OAuth2\Client\Provider\Pbs|false $pbs */
    $pbs = $this->networkManager->createInstance('social_auth_pbs')->getSdk();

    // If PBS client could not be obtained.
    if (!$pbs) {
      drupal_set_message($this->t('Social Auth PBS not configured properly. 
        Contact site administrator.'), 'error');
      return $this->redirect('user.login');
    }

    // PBS service was returned, inject it to $pbsAuthManager.
    $this->pbsAuthManager->setClient($pbs);

    // Generates the URL where the user will be redirected for authentication.
    $auth_url = $this->pbsAuthManager->getAuthorizationUrl();

    $state = $this->pbsAuthManager->getState();

    $this->dataHandler->set('oauth2state', $state);

    return new TrustedRedirectResponse($auth_url);
  }

  /**
   * Response for path 'user/login/pbs/callback'.
   *
   * PBS returns the user here after user has authenticated.
   */
  public function callback() {
    // Checks if user cancel authentication via PBS.
    $error = $this->request->getCurrentRequest()->get('error');
    if ($error == 'access_denied') {
      drupal_set_message($this->t('You could not be authenticated.'), 'error');
      return $this->redirect('user.login');
    }

    /* @var \CascadePublicMedia\OAuth2\Client\Provider\Pbs|false $pbs */
    $pbs = $this->networkManager->createInstance('social_auth_pbs')->getSdk();

    // If PBS client could not be obtained.
    if (!$pbs) {
      drupal_set_message($this->t('Social Auth PBS not configured properly. 
        Contact site administrator.'), 'error');
      return $this->redirect('user.login');
    }

    $state = $this->dataHandler->get('oauth2state');

    // Retrieves $_GET['state'].
    $retrievedState = $this->request->getCurrentRequest()->query->get('state');
    if (empty($retrievedState) || ($retrievedState !== $state)) {
      $this->userManager->nullifySessionKeys();
      drupal_set_message($this->t('PBS login failed. Invalid OAuth2 State.'), 'error');
      return $this->redirect('user.login');
    }

    $this->pbsAuthManager->setClient($pbs)->authenticate();

    // Saves access token to session.
    $this->dataHandler->set('access_token', $this->pbsAuthManager->getAccessToken());

    // Gets user's info from PBS API.
    /* @var \CascadePublicMedia\OAuth2\Client\Provider\Pbs $profile */
    if (!$profile = $this->pbsAuthManager->getUserInfo()) {
      drupal_set_message($this->t('PBS login failed, could not load PBS 
        profile. Contact site administrator.'), 'error');
      return $this->redirect('user.login');
    }

    // Gets (or not) extra initial data.
    $data = $this->userManager->checkIfUserExists($profile->getId()) ? NULL : $this->pbsAuthManager->getExtraDetails();

    // If user information could be retrieved.
    return $this->userManager->authenticateUser(
      $profile->getName(),
      $profile->getEmail(),
      $profile->getId(),
      $this->pbsAuthManager->getAccessToken(),
      $profile->getThumbnailUrl(),
      $data
    );
  }

}
