<?php

namespace Drupal\office365_calendar\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\office365_calendar\Office365\Oauth2Service;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class CallbackController.
 *
 * @package Drupal\office365_calendar\Controller
 */
class CallbackController extends ControllerBase {

  /**
   * Drupal\office365_calendar\Office365\Oauth2Service definition.
   *
   * @var \Drupal\office365_calendar\Office365\Oauth2Service
   */
  protected $office365Oauth2;

  /**
   * {@inheritdoc}
   */
  public function __construct(Oauth2Service $office365_oauth2) {
    $this->office365Oauth2 = $office365_oauth2;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('office365_oauth2')
    );
  }

  /**
   * Callback.
   *
   * @return string
   *   Return Hello string.
   */
  public function callback() {
    $uid = \Drupal::currentUser()->id();
    $this->office365Oauth2->authorize($uid);
    return new RedirectResponse(
      Url::fromRoute(
        'entity.user.edit_form', [
        'user' => $uid,
      ])
        ->toString());
  }

}
