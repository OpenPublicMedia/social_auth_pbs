<?php

namespace Drupal\social_auth_pbs\Controller;

/**
 * Returns responses for Social Auth PBS module routes.
 *
 * Note: This controller is also used for _all_ Social Auth PBS callbacks. The
 * base controller is configured to share the session prefix among all variants
 * (PBS Account, Facebook, and Google) in order to support the single callback.
 *
 * @see \Drupal\social_auth_pbs\Controller\PbsAuthControllerBase
 */
class PbsAuthController extends PbsAuthControllerBase {

  /**
   * {@inheritdoc}
   */
  public static function getProviderId(): string {
    return 'social_auth_pbs';
  }

}
