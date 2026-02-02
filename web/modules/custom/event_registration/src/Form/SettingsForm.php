<?php

namespace Drupal\event_registration\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration form for event registration settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['event_registration.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'event_registration_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('event_registration.settings');

    $form['admin_email'] = [
      '#type' => 'email',
      '#title' => $this->t('Admin Email Address'),
      '#description' => $this->t('Email address where registration notifications will be sent.'),
      '#default_value' => $config->get('admin_email') ?? '',
      '#required' => TRUE,
    ];

    $form['enable_notifications'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Email Notifications'),
      '#description' => $this->t('Check this box to enable email notifications for new registrations.'),
      '#default_value' => $config->get('enable_notifications') ?? TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $email = $form_state->getValue('admin_email');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $form_state->setErrorByName('admin_email', $this->t('Please enter a valid email address.'));
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('event_registration.settings')
      ->set('admin_email', $form_state->getValue('admin_email'))
      ->set('enable_notifications', $form_state->getValue('enable_notifications'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
