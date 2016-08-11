<?php

namespace Drupal\office365_calendar\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\office365_calendar\Office365\Oauth2;
use Drupal\office365_calendar\Office365\CalendarAPI;

/**
 * Class TestController.
 *
 * @package Drupal\office365_calendar\Controller
 */
class TestController extends ControllerBase {

  /**
   * Builds the /testoffice365 test page.
   */
  public function calendar() {
    $oauth2 = \Drupal::service('office365_oauth2');
    $uid = \Drupal::currentUser()->id();

    if (!$oauth2->tokenExists($uid)){
      $oauth2->authorize($uid);
    }

    $api = new CalendarAPI($oauth2->getAccessToken($uid));
    $userdata = \Drupal::service('user.data');
    $calendar = (string) $userdata->get('office365_calendar', $uid, 'calendar');
    $query = [
      'startdatetime' => '2016-07-01T00:00:00Z',
      'enddatetime' => '2016-08-31T00:00:00Z',
    ];
    $events = $api->getEvents($calendar, $query);
    $jsglobal = [];
    foreach ($events as $event){
      $jsevent['title'] = $event['Subject'];
      $jsevent['start'] = $event['Start']['DateTime'];
      $jsevent['end'] = $event['End']['DateTime'];
      $jsglobal[] = $jsevent;
    }

    // Array of FullCalendar settings.
    $settings = array(
      'header' => array(
        'left' => 'prev,next today',
        'center' => 'title',
        'right' => 'month,agendaWeek,agendaDay',
      ),
      'defaultDate' => date("Y-m-d"),//'2015-02-12',
      'editable' => FALSE,
      'eventLimit' => TRUE, // allow "more" link when too many events
      'events' => $jsglobal,
    );
    return array(
      '#theme' => 'fullcalendar_calendar',
      '#calendar_id' => 'fullcalendar',
      '#calendar_settings' => $settings,
    );
  }

}
