<?php

namespace Drupal\social_auth_pbs\Plugin\Network;

use OpenPublicMedia\OAuth2\Client\Provider\Apple;

/**
 * Defines a Network Plugin for Social Auth PBS Apple variant.
 *
 * @package Drupal\social_auth_pbs\Plugin\Network
 *
 * @Network(
 *   id = "social_auth_pbs_apple",
 *   social_network = "PBS - Apple",
 *   type = "social_auth",
 *   handlers = {
 *     "settings": {
 *       "class": "\Drupal\social_auth_pbs\Settings\PbsAuthSettings",
 *       "config_id": "social_auth_pbs.settings"
 *     }
 *   }
 * )
 */
class PbsAppleAuth extends PbsAuthBase {

  /**
   * {@inheritdoc}
   */
  public static function getClassName(): string {
    return Apple::class;
  }

}
