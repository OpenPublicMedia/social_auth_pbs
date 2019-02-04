<?php

namespace Drupal\social_auth_pbs\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\social_api\Plugin\NetworkManager;
use Drupal\social_auth\SocialAuthDataHandler;
use Drupal\social_auth\SocialAuthUserManager;
use Drupal\social_auth_pbs\PbsAuthManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

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
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Used for settings messages.
   */
  public function __construct(NetworkManager $network_manager,
                              SocialAuthUserManager $user_manager,
                              PbsAuthManager $pbs_auth_manager,
                              RequestStack $request,
                              SocialAuthDataHandler $social_auth_data_handler,
                              LoggerChannelFactoryInterface $logger_factory,
                              MessengerInterface $messenger) {

    $this->networkManager = $network_manager;
    $this->userManager = $user_manager;
    $this->pbsAuthManager = $pbs_auth_manager;
    $this->request = $request;
    $this->dataHandler = $social_auth_data_handler;
    $this->loggerFactory = $logger_factory;
    $this->messenger = $messenger;

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
      $container->get('logger.factory'),
      $container->get('messenger')
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
      $this->messenger->addError($this->t('You could not be authenticated.'));
    }

    /* @var \CascadePublicMedia\OAuth2\Client\Provider\Pbs|bool $pbs */
    $pbs = $this->networkManager->createInstance('social_auth_pbs')->getSdk();

    // If PBS client could not be obtained.
    if (!$pbs) {
      $this->messenger->addError($this->t('Social Auth PBS not configured
        properly. Contact site administrator.'));
      return $this->redirect('user.login');
    }

    $state = $this->dataHandler->get('oauth2state');

    // Retrieves $_GET['state'].
    $retrievedState = $this->request->getCurrentRequest()->query->get('state');
    if (empty($retrievedState) || ($retrievedState !== $state)) {
      $this->userManager->nullifySessionKeys();
      $this->messenger->addError($this->t('PBS login failed. Invalid OAuth2 
        state.'));
      return $this->redirect('user.login');
    }

    $this->pbsAuthManager->setClient($pbs)->authenticate();

    // Saves access token to session.
    $this->dataHandler->set('access_token', $this->pbsAuthManager->getAccessToken());

    // Gets user's info from PBS API.
    /* @var \CascadePublicMedia\OAuth2\Client\Provider\Pbs $profile */
    if (!$profile = $this->pbsAuthManager->getUserInfo()) {
      $this->messenger->addError($this->t('PBS login failed, could not load PBS 
        profile. Contact site administrator.'));
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
