<?php

namespace Drupal\event_registration\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;

/**
 * Controller for displaying event registrations.
 */
class RegistrationListController extends ControllerBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a RegistrationListController object.
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
   * Displays the registration list page.
   */
  public function listRegistrations() {
    $build = [];

    // Filter form.
    $build['filters'] = $this->buildFilterForm();

    // Registration table.
    $build['table'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'registration-table-wrapper'],
    ];

    $build['table']['content'] = $this->buildRegistrationTable();

    // Export button.
    $build['export'] = [
      '#type' => 'link',
      '#title' => $this->t('Export to CSV'),
      '#url' => \Drupal\Core\Url::fromRoute('event_registration.admin.export', [], [
        'query' => \Drupal::request()->query->all(),
      ]),
      '#attributes' => [
        'class' => ['button', 'button--primary'],
      ],
    ];

    $build['#attached']['library'][] = 'core/drupal.ajax';

    return $build;
  }

  /**
   * Build filter form.
   */
  protected function buildFilterForm() {
    $form = \Drupal::formBuilder()->getForm('Drupal\event_registration\Form\RegistrationFilterForm');
    return $form;
  }

  /**
   * Build registration table.
   */
  protected function buildRegistrationTable($event_date = NULL, $event_name = NULL) {
    $query = $this->database->select('event_registration', 'er')
      ->fields('er', [
        'id',
        'full_name',
        'email',
        'college_name',
        'department',
        'category',
        'event_date',
      ])
      ->orderBy('created', 'DESC');

    // Apply filters.
    $request = \Drupal::request();
    $filter_date = $request->query->get('event_date');
    $filter_name = $request->query->get('event_name');

    if ($filter_date) {
      $query->condition('event_date', $filter_date);
    }

    if ($filter_name) {
      $query->condition('event_id', $filter_name);
    }

    $results = $query->execute()->fetchAll();

    // Get event names.
    $event_ids = array_unique(array_column($results, 'event_id'));
    $event_names = [];
    if (!empty($event_ids)) {
      $event_names = $this->database->select('event_configuration', 'ec')
        ->fields('ec', ['id', 'event_name'])
        ->condition('id', $event_ids, 'IN')
        ->execute()
        ->fetchAllKeyed();
    }

    // Build table rows.
    $rows = [];
    foreach ($results as $row) {
      $event_name = $event_names[$row->event_id] ?? 'N/A';
      $rows[] = [
        $row->full_name,
        $row->email,
        $row->college_name,
        $row->department,
        $row->category,
        $row->event_date,
        $event_name,
      ];
    }

    $header = [
      $this->t('Full Name'),
      $this->t('Email'),
      $this->t('College'),
      $this->t('Department'),
      $this->t('Category'),
      $this->t('Event Date'),
      $this->t('Event Name'),
    ];

    $count = count($rows);

    return [
      '#type' => 'container',
      'count' => [
        '#markup' => '<p><strong>' . $this->t('Total Participants: @count', ['@count' => $count]) . '</strong></p>',
      ],
      'table' => [
        '#type' => 'table',
        '#header' => $header,
        '#rows' => $rows,
        '#empty' => $this->t('No registrations found.'),
      ],
    ];
  }

}
