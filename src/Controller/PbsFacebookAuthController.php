<?php

namespace Drupal\social_auth_pbs\Controller;

/**
 * Returns responses for Social Auth PBS Facebook variant routes.
 */
class PbsFacebookAuthController extends PbsAuthControllerBase {

  /**
   * {@inheritdoc}
   */
  public static function getProviderId(): string {
    return 'social_auth_pbs_facebook';
  }

}
