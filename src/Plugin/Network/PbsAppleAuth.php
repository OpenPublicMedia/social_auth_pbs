<?php

namespace Drupal\social_auth_pbs\Plugin\Network;

use Drupal\social_auth\Plugin\Network\NetworkBase;
use Drupal\social_auth\Plugin\Network\NetworkInterface;

/**
 * Defines a Network Plugin for Social Auth PBS Apple variant.
 *
 * @package Drupal\social_auth_pbs\Plugin\Network
 *
 * @Network(
 *   id = "social_auth_pbs_apple",
 *   short_name = "pbs_apple",
 *   social_network = "PBS - Apple",
 *   img_path = "img/pbs_logo.svg",
 *   type = "social_auth",
 *   class_name = "\OpenPublicMedia\OAuth2\Client\Provider\Apple",
 *   auth_manager = "\Drupal\social_auth_pbs\PbsAuthManager",
 *   routes = {
 *     "redirect": "social_auth_pbs.network.pbs.redirect",
 *     "callback": "social_auth_pbs.network.callback",
 *     "settings_form": "social_auth.network.settings_form",
 *   },
 *   handlers = {
 *     "settings": {
 *       "class": "\Drupal\social_auth\Settings\SettingsBase",
 *       "config_id": "social_auth_pbs.settings"
 *     }
 *   }
 * )
 */
class PbsAppleAuth extends NetworkBase implements NetworkInterface {}
