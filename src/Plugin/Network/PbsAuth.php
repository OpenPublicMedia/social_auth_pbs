<?php

namespace Drupal\social_auth_pbs\Plugin\Network;

use OpenPublicMedia\OAuth2\Client\Provider\Pbs;

/**
 * Defines a Network Plugin for Social Auth PBS.
 *
 * @package Drupal\social_auth_pbs\Plugin\Network
 *
 * @Network(
 *   id = "social_auth_pbs",
 *   social_network = "PBS",
 *   type = "social_auth",
 *   handlers = {
 *     "settings": {
 *       "class": "\Drupal\social_auth_pbs\Settings\PbsAuthSettings",
 *       "config_id": "social_auth_pbs.settings"
 *     }
 *   }
 * )
 */
class PbsAuth extends PbsAuthBase {

  /**
   * {@inheritdoc}
   */
  public static function getClassName(): string {
    return Pbs::class;
  }

}
