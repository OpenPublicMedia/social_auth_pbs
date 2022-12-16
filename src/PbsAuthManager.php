<?php

namespace Drupal\social_auth_pbs;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\social_auth\AuthManager\OAuth2Manager;
use Drupal\social_auth\User\SocialAuthUser;
use Drupal\social_auth\User\SocialAuthUserInterface;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Contains all the logic for PBS login integration.
 */
class PbsAuthManager extends OAuth2Manager {

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $configFactory
   *   Used for accessing configuration object factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   Used to get the authorization code from the callback request.
   */
  public function __construct(ConfigFactory $configFactory, LoggerChannelFactoryInterface $logger_factory, RequestStack $request_stack) {
    parent::__construct($configFactory->get('social_auth_pbs.settings'), $logger_factory, $this->request = $request_stack->getCurrentRequest());
  }

  /**
   * {@inheritdoc}
   */
  public function authenticate(): void {
    try {
      $this->setAccessToken($this->client->getAccessToken('authorization_code',
        ['code' => $this->request->query->get('code')]));
    }
    catch (IdentityProviderException $e) {
      $this->loggerFactory->get('social_auth_pbs')->error("There was an error during authentication. Exception: {$e->getMessage()}");
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getUserInfo(): SocialAuthUserInterface {
    if (!$this->user) {
      /** @var \OpenPublicMedia\OAuth2\Client\Provider\PbsResourceOwner $owner */
      $owner = $this->client->getResourceOwner($this->getAccessToken());
      $this->user = new SocialAuthUser(
        $owner->getName() ?? '',
        $owner->getId(),
        $this->getAccessToken(),
        $owner->getEmail(),
        additional_data: $this->getExtraDetails()
      );
    }
    return $this->user;
  }

  /**
   * {@inheritdoc}
   */
  public function getAuthorizationUrl(): string {
    $scopes = $this->getScopes();

    // Returns the URL where user will be redirected.
    return $this->client->getAuthorizationUrl([
      'scope' => $scopes,
      // The "activation" parameter will force a VPPA check during activation
      // and present a dialog to the user if necessary. This is required for PBS
      // Account linked services like Passport video.
      'activation' => 'true',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function requestEndPoint(string $method, string $path, ?string $domain = NULL, array $options = []): mixed {
    if (!$domain) {
      $domain = 'https://account.pbs.org';
    }
    $url = $domain . $path;
    $request = $this->client->getAuthenticatedRequest($method, $url, $this->getAccessToken(), $options);
    try {
      return $this->client->getParsedResponse($request);
    }
    catch (IdentityProviderException $e) {
      $this->loggerFactory->get('social_auth_pbs')->error("There was an error when requesting $url. Exception: {$e->getMessage()}");
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getState(): string {
    return $this->client->getState();
  }

}
