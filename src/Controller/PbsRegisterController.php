<?php

namespace Drupal\social_auth_pbs\Controller;

use Drupal\Core\Routing\TrustedRedirectResponse;

/**
 * Returns responses for special Social Auth PBS Account register routes.
 *
 * This controller generates a URL that sends the user first through an account
 * creation flow and then through the regular OAuth2 authorization flow.
 */
class PbsRegisterController extends PbsAuthController {

  /**
   * {@inheritdoc}
   */
  public function redirectToProvider() {
    $response = parent::redirectToProvider();
    if ($response instanceof TrustedRedirectResponse) {
      $url_parts = parse_url($response->getTargetUrl());
      $next = urlencode("{$url_parts['path']}?{$url_parts['query']}");
      $register_url = "{$url_parts['scheme']}://{$url_parts['host']}/oauth2/register/?next=$next";
      $response->setTrustedTargetUrl($register_url);
    }
    return $response;
  }

}
