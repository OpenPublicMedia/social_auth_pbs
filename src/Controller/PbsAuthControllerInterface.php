<?php

namespace Drupal\social_auth_pbs\Controller;

/**
 * Interface for the base PBS Social Auth controller.
 *
 * @package Drupal\social_auth_pbs\Controller
 */
interface PbsAuthControllerInterface {

  /**
   * Gets the ID for the specific provider being used.
   *
   * @return string
   *   Provider ID.
   */
  public static function getProviderId(): string;

}
