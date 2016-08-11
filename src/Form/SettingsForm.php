<?php

namespace Drupal\office365_calendar\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\encrypt\EncryptionProfileManagerInterface;
use Drupal\office365_calendar\Office365\Oauth2ServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SettingsForm.
 *
 * @package Drupal\office365_calendar\Form
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The encryption profile manager.
   *
   * @var \Drupal\encrypt\EncryptionProfileManagerInterface
   */
  protected $encryptionProfileManager;

  protected $oauth2Service;

  /**
   * {@inheritdoc}
   *
   */
  public function __construct(ConfigFactoryInterface $config_factory, EncryptionProfileManagerInterface $encryption_profile_manager, Oauth2ServiceInterface $oauth2_service) {
    parent::__construct($config_factory);
    $this->encryptionProfileManager = $encryption_profile_manager;
    $this->oauth2Service = $oauth2_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('encrypt.encryption_profile.manager'),
      $container->get('office365_oauth2')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'office365_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'office365_calendar.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('office365_calendar.settings');
    $form['oauth2_settings'] = array(
      '#type' => 'details',
      '#title' => $this->t('Oauth2 settings'),
      '#open' => TRUE,
      '#weight' => 0,
    );
    $form['oauth2_settings']['client_id'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Client ID'),
      '#required' => TRUE,
      '#description' => $this->t('Client ID obtained from registration of this web app.'),
      '#maxlength' => 36,
      '#size' => 64,
      '#default_value' => $config->get('client_id'),
    );
    $form['oauth2_settings']['client_secret'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Client Secret'),
      '#required' => TRUE,
      '#description' => $this->t('Client secret obtained from registration of this web app.'),
      '#maxlength' => 23,
      '#size' => 64,
      '#default_value' => $config->get('client_secret') == NULL ? '' : 'hidden',
    );
    $form['oauth2_settings']['redirect_URI'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Redirect URI'),
      '#required' => TRUE,
      '#description' => $this->t('Redirect URI specified in the request determines how the authorization code is returned to your application.'),
      '#maxlength' => 256,
      '#size' => 64,
      '#default_value' => $config->get('redirect_URI'),
    );

    $form['encryption_settings']['encryption_profile'] = array(
      '#type' => 'select',
      '#title' => t('Encryption profile'),
      '#required' => TRUE,
      '#options' => $this->encryptionProfileManager->getEncryptionProfileNamesAsOptions(),
      '#size' => 1,
      '#default_value' => $config->get('encryption_profile'),
      '#description' => $this->t('Choose an available encryption profile to encrypt user API keys. If the desired profile is not listed, <a href=":link">create a new profile</a>.', [':link' => Url::fromRoute('entity.encryption_profile.add_form')->toString()]),
    );

    $profile_type_ids = \Drupal::service('entity_type.manager')->getStorage('profile_type')->getQuery()->execute();
    foreach ($profile_type_ids as $profile_type_id){
      $profiles[$profile_type_id] = \Drupal::service('entity_type.manager')
        ->getStorage('profile_type')
        ->load($profile_type_id)
        ->label();
    }
    $form['profile_settings']['profile_type'] = array(
      '#type' => 'select',
      '#title' => t('User profile type'),
      '#required' => TRUE,
      '#options' => $profiles,
      '#size' => 1,
      '#default_value' => $config->get('profile_type'),
      '#description' => $this->t('Choose an available user profile type to show calendar on. If the desired profile is not listed, <a href=":link">create a new profile</a>.', [':link' => Url::fromRoute('entity.profile_type.add_form')->toString()]),
    );

    $form['submit'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Submit'),
    );

    return $form;
  }

  /**
    * {@inheritdoc}
    */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('office365_calendar.settings');
    $config_saved = FALSE;
    $config
      ->set('client_id', $form_state->getValue('client_id'))
      ->set('redirect_URI', $form_state->getValue('redirect_URI'))
      ->set('profile_type', $form_state->getValue('profile_type'));

    if ($form_state->getValue('client_secret') != 'hidden'){
      $config->set('client_secret', $form_state->getValue('client_secret'));
    }

    if ($form_state->getValue('encryption_profile') != $this->config('office365_calendar.settings')->get('encryption_profile')){
      $uids = \Drupal::service('entity_type.manager')->getStorage('user')->getQuery()->execute();

      foreach ($uids as $uid){
        $token = $this->oauth2Service->loadToken($uid);
        if ($token) {
          $tokens[$uid] = $token;
        }
      }

      //Save new encryption profile setting before re-encrypting tokens.
      $config
        ->set('encryption_profile', $form_state->getValue('encryption_profile'))
        ->save();
      $config_saved = TRUE;

      foreach ($tokens as $uid => $token){
        $this->oauth2Service->saveToken($uid, $token);
      }
    }
    if (!$config_saved){
      $config->save();
    }


    drupal_set_message($this->t('Settings saved'));
    }

}
