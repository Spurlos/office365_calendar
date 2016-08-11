<?php

namespace Drupal\office365_calendar\Office365;

use Drupal\Core\Config\ConfigFactory;
use Drupal\encrypt\EncryptService;
use Drupal\user\UserData;
use League\OAuth2\Client\Token\AccessToken;


/**
 * Class Office365Oauth2Service.
 *
 * @package Drupal\office365_calendar
 */
interface Oauth2ServiceInterface {
  /**
   * Constructor.
   */
  public function __construct(ConfigFactory $config_factory, UserData $user_data, EncryptService $encrypt_service);

  public function authorize($uid);

  public function getAccessToken($uid);

  public function refreshToken(AccessToken $token);

  public function tokenExists($uid);

  public function saveToken($uid, AccessToken $token);

  public function loadToken($uid);

  public function deleteToken($uid);
}