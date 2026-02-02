<?php

namespace Drupal\event_registration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\event_registration\Service\MailService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Public form for users to register for events.
 */
class EventRegistrationForm extends FormBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The mail service.
   *
   * @var \Drupal\event_registration\Service\MailService
   */
  protected $mailService;

  /**
   * Constructs an EventRegistrationForm object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\event_registration\Service\MailService $mail_service
   *   The mail service.
   */
  public function __construct(Connection $database, MailService $mail_service) {
    $this->database = $database;
    $this->mailService = $mail_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('event_registration.mail_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'event_registration_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['full_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Full Name'),
      '#required' => TRUE,
      '#maxlength' => 255,
    ];

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email Address'),
      '#required' => TRUE,
      '#maxlength' => 255,
    ];

    $form['college_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('College Name'),
      '#required' => TRUE,
      '#maxlength' => 255,
    ];

    $form['department'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Department'),
      '#required' => TRUE,
      '#maxlength' => 255,
    ];

    // Get categories from active events.
    $categories = $this->getActiveCategories();
    $form['category'] = [
      '#type' => 'select',
      '#title' => $this->t('Event Category'),
      '#required' => TRUE,
      '#options' => $categories,
      '#empty_option' => $this->t('- Select Category -'),
      '#ajax' => [
        'callback' => '::updateEventDates',
        'wrapper' => 'event-date-wrapper',
        'event' => 'change',
      ],
    ];

    $form['event_date_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'event-date-wrapper'],
    ];

    $selected_category = $form_state->getValue('category');
    if ($selected_category) {
      $event_dates = $this->getEventDatesByCategory($selected_category);
      $form['event_date_wrapper']['event_date'] = [
        '#type' => 'select',
        '#title' => $this->t('Event Date'),
        '#required' => TRUE,
        '#options' => $event_dates,
        '#empty_option' => $this->t('- Select Event Date -'),
        '#ajax' => [
          'callback' => '::updateEventNames',
          'wrapper' => 'event-name-wrapper',
          'event' => 'change',
        ],
      ];
    }
    else {
      $form['event_date_wrapper']['event_date'] = [
        '#type' => 'select',
        '#title' => $this->t('Event Date'),
        '#required' => TRUE,
        '#options' => [],
        '#empty_option' => $this->t('- Select Category First -'),
        '#disabled' => TRUE,
      ];
    }

    $form['event_name_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'event-name-wrapper'],
    ];

    $selected_date = $form_state->getValue('event_date');
    if ($selected_category && $selected_date) {
      $event_names = $this->getEventNamesByCategoryAndDate($selected_category, $selected_date);
      $form['event_name_wrapper']['event_name'] = [
        '#type' => 'select',
        '#title' => $this->t('Event Name'),
        '#required' => TRUE,
        '#options' => $event_names,
        '#empty_option' => $this->t('- Select Event -'),
      ];
    }
    else {
      $form['event_name_wrapper']['event_name'] = [
        '#type' => 'select',
        '#title' => $this->t('Event Name'),
        '#required' => TRUE,
        '#options' => [],
        '#empty_option' => $this->t('- Select Date First -'),
        '#disabled' => TRUE,
      ];
    }

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Register'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * AJAX callback to update event dates based on category.
   */
  public function updateEventDates(array &$form, FormStateInterface $form_state) {
    return $form['event_date_wrapper'];
  }

  /**
   * AJAX callback to update event names based on category and date.
   */
  public function updateEventNames(array &$form, FormStateInterface $form_state) {
    return $form['event_name_wrapper'];
  }

  /**
   * Get active event categories.
   */
  protected function getActiveCategories() {
    $current_date = date('Y-m-d');
    
    $query = $this->database->select('event_configuration', 'ec')
      ->fields('ec', ['category'])
      ->condition('reg_start_date', $current_date, '<=')
      ->condition('reg_end_date', $current_date, '>=')
      ->distinct();
    
    $results = $query->execute()->fetchCol();
    
    $categories = [];
    foreach ($results as $category) {
      $categories[$category] = $category;
    }
    
    return $categories;
  }

  /**
   * Get event dates by category.
   */
  protected function getEventDatesByCategory($category) {
    $current_date = date('Y-m-d');
    
    $query = $this->database->select('event_configuration', 'ec')
      ->fields('ec', ['event_date'])
      ->condition('category', $category)
      ->condition('reg_start_date', $current_date, '<=')
      ->condition('reg_end_date', $current_date, '>=')
      ->distinct()
      ->orderBy('event_date', 'ASC');
    
    $results = $query->execute()->fetchCol();
    
    $dates = [];
    foreach ($results as $date) {
      $dates[$date] = $date;
    }
    
    return $dates;
  }

  /**
   * Get event names by category and date.
   */
  protected function getEventNamesByCategoryAndDate($category, $event_date) {
    $current_date = date('Y-m-d');
    
    $query = $this->database->select('event_configuration', 'ec')
      ->fields('ec', ['id', 'event_name'])
      ->condition('category', $category)
      ->condition('event_date', $event_date)
      ->condition('reg_start_date', $current_date, '<=')
      ->condition('reg_end_date', $current_date, '>=')
      ->orderBy('event_name', 'ASC');
    
    $results = $query->execute()->fetchAllKeyed();
    
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Validate email format.
    $email = $form_state->getValue('email');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $form_state->setErrorByName('email', $this->t('Please enter a valid email address.'));
    }

    // Validate text fields - no special characters.
    $text_fields = ['full_name', 'college_name', 'department'];
    foreach ($text_fields as $field) {
      $value = $form_state->getValue($field);
      if (!preg_match('/^[a-zA-Z0-9\s]+$/', $value)) {
        $form_state->setErrorByName($field, $this->t('@field should only contain letters, numbers, and spaces.', [
          '@field' => ucfirst(str_replace('_', ' ', $field)),
        ]));
      }
    }

    // Check for duplicate registration.
    $email = $form_state->getValue('email');
    $event_date = $form_state->getValue('event_date');
    
    if ($email && $event_date) {
      $existing = $this->database->select('event_registration', 'er')
        ->fields('er', ['id'])
        ->condition('email', $email)
        ->condition('event_date', $event_date)
        ->execute()
        ->fetchField();
      
      if ($existing) {
        $form_state->setErrorByName('email', $this->t('You are already registered for this event.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    try {
      $category = $form_state->getValue('category');
      $event_date = $form_state->getValue('event_date');
      $event_name_id = $form_state->getValue('event_name');

      // Get event name from ID.
      $event_name = $this->database->select('event_configuration', 'ec')
        ->fields('ec', ['event_name'])
        ->condition('id', $event_name_id)
        ->execute()
        ->fetchField();

      // Insert registration.
      $this->database->insert('event_registration')
        ->fields([
          'full_name' => $form_state->getValue('full_name'),
          'email' => $form_state->getValue('email'),
          'college_name' => $form_state->getValue('college_name'),
          'department' => $form_state->getValue('department'),
          'category' => $category,
          'event_date' => $event_date,
          'event_id' => $event_name_id,
          'created' => \Drupal::time()->getRequestTime(),
        ])
        ->execute();

      // Prepare registration data for email.
      $registration_data = [
        'full_name' => $form_state->getValue('full_name'),
        'email' => $form_state->getValue('email'),
        'college_name' => $form_state->getValue('college_name'),
        'department' => $form_state->getValue('department'),
        'category' => $category,
        'event_date' => $event_date,
        'event_name' => $event_name,
      ];

      // Send email notifications.
      $this->mailService->sendRegistrationEmail($registration_data);

      $this->messenger()->addMessage($this->t('Thank you for registering! You will receive a confirmation email shortly.'));
      
      // Redirect to prevent resubmission.
      $form_state->setRedirect('event_registration.register');
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t('An error occurred during registration. Please try again.'));
      \Drupal::logger('event_registration')->error('Registration error: @message', ['@message' => $e->getMessage()]);
    }
  }

}
