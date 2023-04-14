<?php

namespace Drupal\social_auth_pbs\Controller;

use Drupal\social_auth\Controller\OAuth2ControllerBase;
use Drupal\social_auth\Plugin\Network\NetworkInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Returns responses for Social Auth PBS module routes.
 *
 * This controller handles callbacks for all variants of the PBS Account OAuth
 * implementation (Apple, Facebook, Google, etc.). Redirects and callbacks are
 * provided with the specific network based on a query parameter containing the
 * network short name.
 *
 * @see \Drupal\social_auth_pbs\Plugin\Network\PbsNetworkBase::getRedirectUrl()
 */
class PbsAuthController extends OAuth2ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function callback(NetworkInterface $network): RedirectResponse {
    $this->setSessionPrefix();
    $plugin_id = $this->dataHandler->get('plugin_id');
    /** @var \Drupal\social_auth\Plugin\Network\NetworkInterface $network */
    $network = $this->networkManager->createInstance($plugin_id);
    return parent::callback($network);
  }

  /**
   * {@inheritdoc}
   */
  public function redirectToProvider(NetworkInterface $network): Response {
    $request = $this->request->getCurrentRequest();
    if (!$request->query->has('network')) {
      throw new \RuntimeException("Network ID missing.");
    }
    $network_short_name = $request->query->get('network');
    /** @var \Drupal\social_auth\Plugin\Network\NetworkInterface $network */
    $network = $this->networkManager->createInstance("social_auth_$network_short_name");
    $this->setSessionPrefix();
    $this->dataHandler->set('plugin_id', $network->getId());
    return parent::redirectToProvider($network);
  }

  /**
   * Sets the session prefix to the base provider ID.
   *
   * This controller is used for the all redirects and callbacks so the session
   * prefix must be shared between all variants to allow passing of the actual
   * network via the data handler.
   */
  private function setSessionPrefix(): void {
    $this->dataHandler->setSessionPrefix('social_auth_pbs');
  }

}
