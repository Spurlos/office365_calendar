<?php

namespace Drupal\office365_calendar\Office365;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;

class CalendarAPI {

//  /**
//   * @var ClientInterface
//   */
//  private $client;

  private $clientConfig;

  private $events = [];

  private $windowszonesurl = "http://unicode.org/repos/cldr/trunk/common/supplemental/windowsZones.xml";

  /**
   * Constructor
   *
   * @param $client_id
   * @param $access_token
   */
  public function __construct($accesstoken) {
    $this->windowszones = simplexml_load_file($this->windowszonesurl);
    $this->clientConfig = (
      [
        'base_uri' => 'https://outlook.office.com/api/v2.0/',
        'headers' => [
          'Accept' => 'application/json; odata.metadata=none',
          'Authorization' => 'Bearer ' . $accesstoken,
          'client-request-id' => $this->makeGuid(),
          'return-client-request-id' => 'true'
        ]
      ]
    );
  }

  public function getCalendars(){
    $client = new Client($this->clientConfig);
    $response = $client->get('me/calendars');
    $this->checkResponseStatusCode($response, 200);
    return json_decode($response->getBody(), TRUE);
  }

  /**
   * Returns events based on query parameters
   *
   * @return array
   */
  public function getEvents($calendar, $query) {
    $timezone = $this->windowszones->xpath('//mapZone[@type="'.drupal_get_user_timezone().'"]');
    $timezone = $timezone[0]->xpath('@other');
    $timezone = (string) $timezone[0]->other;

    $this->clientConfig['headers']['Prefer'] = 'outlook.timezone = "' . $timezone . '"';
    $client = new Client($this->clientConfig);
    $response = $client->get('me/calendars/'.$calendar.'/calendarview', [
        'query' => $query,
      ]
    );
    $this->checkResponseStatusCode($response, 200);
    $jsonarray = json_decode($response->getBody(), TRUE);
    $this->events = array_merge($this->events, $jsonarray['value']);
    if (isset($jsonarray['@odata.nextLink'])) {
      $nextpagearguments = parse_url(urldecode($jsonarray['@odata.nextLink']), PHP_URL_QUERY);
      parse_str($nextpagearguments, $query);
      $this->getEvents($calendar, $query);
    }
    return $this->events;
  }

  public function getMe() {
    $client = new Client($this->clientConfig);
    $response = $client->get('me');
    $this->checkResponseStatusCode($response, 200);
    return json_decode($response->getBody(), TRUE);
  }

  /**
   * Check the response status code.
   *
   * @param ResponseInterface $response
   * @param int $expectedStatusCode
   *
   * @throws \RuntimeException on unexpected status code
   */
  private function checkResponseStatusCode(ResponseInterface $response, $expectedStatusCode) {
    $statusCode = $response->getStatusCode();

    if ($statusCode !== $expectedStatusCode) {
      throw new \RuntimeException('Office365 API returned status code ' . $statusCode . ' expected ' . $expectedStatusCode);
    }
  }

  // This function generates a random GUID.
  public static function makeGuid() {
    if (function_exists('com_create_guid')) {
      error_log("Using 'com_create_guid'.");
      return strtolower(trim(com_create_guid(), '{}'));
    }
    else {
      error_log("Using custom GUID code.");
      $charid = strtolower(md5(uniqid(rand(), TRUE)));
      $hyphen = chr(45);
      $uuid = substr($charid, 0, 8) . $hyphen
        . substr($charid, 8, 4) . $hyphen
        . substr($charid, 12, 4) . $hyphen
        . substr($charid, 16, 4) . $hyphen
        . substr($charid, 20, 12);

      return $uuid;
    }
  }

}