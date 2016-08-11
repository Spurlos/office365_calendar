<?php

namespace Drupal\office365_calendar\Office365;


use Stevenmaguire\OAuth2\Client\Provider\Microsoft;

class MicrosoftOath2Provider extends Microsoft {
  /**
   * Default scopes
   *
   * @var array
   */
  public $defaultScopes = [
    'https://outlook.office.com/calendars.readwrite',
    'offline_access'
  ];

  /**
   * Get authorization url to begin OAuth flow
   *
   * @return string
   */
  public function getBaseAuthorizationUrl()
  {
    return 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize';
  }

  /**
   * Get access token url to retrieve token
   *
   * @return string
   */
  public function getBaseAccessTokenUrl(array $params)
  {
    return 'https://login.microsoftonline.com/common/oauth2/v2.0/token';
  }

  /**
   * {@inheritdoc}
   */
  protected function getScopeSeparator()
  {
    return ' ';
  }
}