<?php

namespace Drupal\social_auth_pbs\Plugin\Network;

use Drupal\Core\Url;
use Drupal\social_auth\Plugin\Network\NetworkBase;
use Drupal\social_auth\Plugin\Network\NetworkInterface;

/**
 * Defines a base Network Plugin for Social Auth PBS networks.
 *
 * @package Drupal\social_auth_pbs\Plugin\Network
 */
abstract class PbsNetworkBase extends NetworkBase implements NetworkInterface {

  /**
   * {@inheritdoc}
   */
  public function getRedirectUrl(array $route_options = []): Url {
    // Add the network short name as a query parameter that can be used by the
    // controller to determine the specific network during callback.
    $route_options['query'] = array_merge($route_options['query'] ?? [], ['network' => $this->getShortName()]);
    return parent::getRedirectUrl($route_options);
  }

}
