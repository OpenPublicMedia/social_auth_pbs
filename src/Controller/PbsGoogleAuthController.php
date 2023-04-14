<?php

namespace Drupal\social_auth_pbs\Controller;

/**
 * Returns responses for Social Auth PBS Google variant routes.
 */
final class PbsGoogleAuthController extends PbsAuthControllerBase {

  /**
   * {@inheritdoc}
   */
  public static function getProviderId(): string {
    return 'social_auth_pbs_google';
  }

}
