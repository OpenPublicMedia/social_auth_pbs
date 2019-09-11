<?php

namespace Drupal\social_auth_pbs\Plugin\Network;

use OpenPublicMedia\OAuth2\Client\Provider\Facebook;

/**
 * Defines a Network Plugin for Social Auth PBS Facebook variant.
 *
 * @package Drupal\social_auth_pbs\Plugin\Network
 *
 * @Network(
 *   id = "social_auth_pbs_facebook",
 *   social_network = "PBS - Facebook",
 *   type = "social_auth",
 *   handlers = {
 *     "settings": {
 *       "class": "\Drupal\social_auth_pbs\Settings\PbsAuthSettings",
 *       "config_id": "social_auth_pbs.settings"
 *     }
 *   }
 * )
 */
class PbsFacebookAuth extends PbsAuthBase {

  /**
   * {@inheritdoc}
   */
  public static function getClassName(): string {
    return Facebook::class;
  }

}
