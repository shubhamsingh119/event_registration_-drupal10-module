<?php

namespace Drupal\event_registration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for administrators to create and configure events.
 */
class EventConfigForm extends FormBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs an EventConfigForm object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'event_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['event_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Event Name'),
      '#required' => TRUE,
      '#maxlength' => 255,
      '#description' => $this->t('Enter the name of the event.'),
    ];

    $form['category'] = [
      '#type' => 'select',
      '#title' => $this->t('Category'),
      '#required' => TRUE,
      '#options' => [
        '' => $this->t('- Select Category -'),
        'Online Workshop' => $this->t('Online Workshop'),
        'Hackathon' => $this->t('Hackathon'),
        'Seminar' => $this->t('Seminar'),
        'Conference' => $this->t('Conference'),
        'Training' => $this->t('Training'),
      ],
      '#description' => $this->t('Select the event category.'),
    ];

    $form['reg_start_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Registration Start Date'),
      '#required' => TRUE,
      '#description' => $this->t('Date when registration opens.'),
    ];

    $form['reg_end_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Registration End Date'),
      '#required' => TRUE,
      '#description' => $this->t('Date when registration closes.'),
    ];

    $form['event_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Event Date'),
      '#required' => TRUE,
      '#description' => $this->t('Date when the event will take place.'),
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Create Event'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $reg_start = $form_state->getValue('reg_start_date');
    $reg_end = $form_state->getValue('reg_end_date');
    $event_date = $form_state->getValue('event_date');

    // Validate that registration end date is after start date.
    if (strtotime($reg_end) < strtotime($reg_start)) {
      $form_state->setErrorByName('reg_end_date', $this->t('Registration end date must be after the start date.'));
    }

    // Validate that event date is on or after registration end date.
    if (strtotime($event_date) < strtotime($reg_end)) {
      $form_state->setErrorByName('event_date', $this->t('Event date must be on or after the registration end date.'));
    }

    // Validate event name contains no special characters.
    $event_name = $form_state->getValue('event_name');
    if (!preg_match('/^[a-zA-Z0-9\s]+$/', $event_name)) {
      $form_state->setErrorByName('event_name', $this->t('Event name should only contain letters, numbers, and spaces.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    try {
      // Insert event into database.
      $this->database->insert('event_configuration')
        ->fields([
          'event_name' => $form_state->getValue('event_name'),
          'category' => $form_state->getValue('category'),
          'reg_start_date' => $form_state->getValue('reg_start_date'),
          'reg_end_date' => $form_state->getValue('reg_end_date'),
          'event_date' => $form_state->getValue('event_date'),
        ])
        ->execute();

      $this->messenger()->addMessage($this->t('Event "@name" has been created successfully.', [
        '@name' => $form_state->getValue('event_name'),
      ]));

      // Reset form.
      $form_state->setRebuild(FALSE);
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t('An error occurred while creating the event. Please try again.'));
      \Drupal::logger('event_registration')->error('Error creating event: @message', ['@message' => $e->getMessage()]);
    }
  }

}
