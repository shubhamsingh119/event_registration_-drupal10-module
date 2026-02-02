<?php

namespace Drupal\event_registration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Filter form for registration list.
 */
class RegistrationFilterForm extends FormBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a RegistrationFilterForm object.
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
    return 'registration_filter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $request = \Drupal::request();
    
    // Get all unique event dates.
    $event_dates = $this->database->select('event_registration', 'er')
      ->fields('er', ['event_date'])
      ->distinct()
      ->orderBy('event_date', 'DESC')
      ->execute()
      ->fetchCol();

    $date_options = ['' => $this->t('- All Dates -')];
    foreach ($event_dates as $date) {
      $date_options[$date] = $date;
    }

    $form['event_date'] = [
      '#type' => 'select',
      '#title' => $this->t('Event Date'),
      '#options' => $date_options,
      '#default_value' => $request->query->get('event_date', ''),
      '#ajax' => [
        'callback' => '::updateEventNames',
        'wrapper' => 'event-name-filter-wrapper',
        'event' => 'change',
      ],
    ];

    $form['event_name_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'event-name-filter-wrapper'],
    ];

    $selected_date = $form_state->getValue('event_date') ?? $request->query->get('event_date');
    
    if ($selected_date) {
      $event_names = $this->getEventNamesByDate($selected_date);
      $form['event_name_wrapper']['event_name'] = [
        '#type' => 'select',
        '#title' => $this->t('Event Name'),
        '#options' => ['' => $this->t('- All Events -')] + $event_names,
        '#default_value' => $request->query->get('event_name', ''),
      ];
    }
    else {
      $all_events = $this->getAllEventNames();
      $form['event_name_wrapper']['event_name'] = [
        '#type' => 'select',
        '#title' => $this->t('Event Name'),
        '#options' => ['' => $this->t('- All Events -')] + $all_events,
        '#default_value' => $request->query->get('event_name', ''),
      ];
    }

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Filter'),
      '#button_type' => 'primary',
    ];

    $form['actions']['reset'] = [
      '#type' => 'submit',
      '#value' => $this->t('Reset'),
      '#submit' => ['::resetForm'],
    ];

    return $form;
  }

  /**
   * AJAX callback for event name dropdown.
   */
  public function updateEventNames(array &$form, FormStateInterface $form_state) {
    return $form['event_name_wrapper'];
  }

  /**
   * Get event names by date.
   */
  protected function getEventNamesByDate($event_date) {
    $query = $this->database->select('event_registration', 'er')
      ->fields('er', ['event_id'])
      ->condition('event_date', $event_date)
      ->distinct();
    
    $event_ids = $query->execute()->fetchCol();
    
    if (empty($event_ids)) {
      return [];
    }

    $event_names = $this->database->select('event_configuration', 'ec')
      ->fields('ec', ['id', 'event_name'])
      ->condition('id', $event_ids, 'IN')
      ->execute()
      ->fetchAllKeyed();

    return $event_names;
  }

  /**
   * Get all event names.
   */
  protected function getAllEventNames() {
    $query = $this->database->select('event_registration', 'er')
      ->fields('er', ['event_id'])
      ->distinct();
    
    $event_ids = $query->execute()->fetchCol();
    
    if (empty($event_ids)) {
      return [];
    }

    $event_names = $this->database->select('event_configuration', 'ec')
      ->fields('ec', ['id', 'event_name'])
      ->condition('id', $event_ids, 'IN')
      ->execute()
      ->fetchAllKeyed();

    return $event_names;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $query_params = [];
    
    $event_date = $form_state->getValue('event_date');
    $event_name = $form_state->getValue('event_name');

    if ($event_date) {
      $query_params['event_date'] = $event_date;
    }

    if ($event_name) {
      $query_params['event_name'] = $event_name;
    }

    $form_state->setRedirect('event_registration.admin.list', [], ['query' => $query_params]);
  }

  /**
   * Reset form handler.
   */
  public function resetForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('event_registration.admin.list');
  }

}
