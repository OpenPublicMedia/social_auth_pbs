<?php

namespace Drupal\social_auth_pbs\Plugin\Network;

use OpenPublicMedia\OAuth2\Client\Provider\Google;

/**
 * Defines a Network Plugin for Social Auth PBS Google variant.
 *
 * @package Drupal\social_auth_pbs\Plugin\Network
 *
 * @Network(
 *   id = "social_auth_pbs_google",
 *   social_network = "PBS - Google",
 *   type = "social_auth",
 *   handlers = {
 *     "settings": {
 *       "class": "\Drupal\social_auth_pbs\Settings\PbsAuthSettings",
 *       "config_id": "social_auth_pbs.settings"
 *     }
 *   }
 * )
 */
class PbsGoogleAuth extends PbsAuthBase {

  /**
   * {@inheritdoc}
   */
  public static function getClassName(): string {
    return Google::class;
  }

}
