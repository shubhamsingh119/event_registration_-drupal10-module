<?php

namespace Drupal\event_registration\Service;

use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Service for sending registration emails.
 */
class MailService {

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a MailService object.
   *
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The mail manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(MailManagerInterface $mail_manager, ConfigFactoryInterface $config_factory) {
    $this->mailManager = $mail_manager;
    $this->configFactory = $config_factory;
  }

  /**
   * Send registration confirmation emails.
   *
   * @param array $registration_data
   *   Array containing registration information.
   */
  public function sendRegistrationEmail(array $registration_data) {
    $config = $this->configFactory->get('event_registration.settings');
    $notifications_enabled = $config->get('enable_notifications');

    if (!$notifications_enabled) {
      return;
    }

    // Send email to user.
    $this->sendUserEmail($registration_data);

    // Send email to admin.
    $admin_email = $config->get('admin_email');
    if ($admin_email) {
      $this->sendAdminEmail($registration_data, $admin_email);
    }
  }

  /**
   * Send confirmation email to user.
   *
   * @param array $data
   *   Registration data.
   */
  protected function sendUserEmail(array $data) {
    $params = [
      'subject' => 'Event Registration Confirmation',
      'body' => $this->buildUserEmailBody($data),
    ];

    $this->mailManager->mail(
      'event_registration',
      'user_confirmation',
      $data['email'],
      'en',
      $params,
      NULL,
      TRUE
    );
  }

  /**
   * Send notification email to admin.
   *
   * @param array $data
   *   Registration data.
   * @param string $admin_email
   *   Admin email address.
   */
  protected function sendAdminEmail(array $data, $admin_email) {
    $params = [
      'subject' => 'New Event Registration',
      'body' => $this->buildAdminEmailBody($data),
    ];

    $this->mailManager->mail(
      'event_registration',
      'admin_notification',
      $admin_email,
      'en',
      $params,
      NULL,
      TRUE
    );
  }

  /**
   * Build email body for user confirmation.
   *
   * @param array $data
   *   Registration data.
   *
   * @return string
   *   Email body.
   */
  protected function buildUserEmailBody(array $data) {
    $body = "Dear {$data['full_name']},\n\n";
    $body .= "Thank you for registering for our event!\n\n";
    $body .= "Registration Details:\n";
    $body .= "-------------------\n";
    $body .= "Event Name: {$data['event_name']}\n";
    $body .= "Category: {$data['category']}\n";
    $body .= "Event Date: {$data['event_date']}\n";
    $body .= "College: {$data['college_name']}\n";
    $body .= "Department: {$data['department']}\n\n";
    $body .= "We look forward to seeing you at the event!\n\n";
    $body .= "Best regards,\n";
    $body .= "Event Management Team";

    return $body;
  }

  /**
   * Build email body for admin notification.
   *
   * @param array $data
   *   Registration data.
   *
   * @return string
   *   Email body.
   */
  protected function buildAdminEmailBody(array $data) {
    $body = "New Event Registration Received\n\n";
    $body .= "Participant Details:\n";
    $body .= "-------------------\n";
    $body .= "Name: {$data['full_name']}\n";
    $body .= "Email: {$data['email']}\n";
    $body .= "College: {$data['college_name']}\n";
    $body .= "Department: {$data['department']}\n\n";
    $body .= "Event Details:\n";
    $body .= "-------------------\n";
    $body .= "Event Name: {$data['event_name']}\n";
    $body .= "Category: {$data['category']}\n";
    $body .= "Event Date: {$data['event_date']}\n";

    return $body;
  }

}
