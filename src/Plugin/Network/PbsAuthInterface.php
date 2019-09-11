<?php

namespace Drupal\social_auth_pbs\Plugin\Network;

use Drupal\social_auth\Plugin\Network\NetworkInterface;

/**
 * Interface PbsAuthInterface.
 *
 * @package Drupal\social_auth_pbs\Plugin\Network
 */
interface PbsAuthInterface extends NetworkInterface {

  /**
   * Gets the class name for the specific provider being used.
   *
   * @return string
   *   Class name.
   */
  public static function getClassName(): string;

}
