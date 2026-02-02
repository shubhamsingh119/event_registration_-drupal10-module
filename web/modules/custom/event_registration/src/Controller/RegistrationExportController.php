<?php

namespace Drupal\event_registration\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller for exporting registrations to CSV.
 */
class RegistrationExportController extends ControllerBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a RegistrationExportController object.
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
   * Export registrations to CSV.
   */
  public function exportCsv() {
    $query = $this->database->select('event_registration', 'er')
      ->fields('er', [
        'full_name',
        'email',
        'college_name',
        'department',
        'category',
        'event_date',
        'event_id',
      ])
      ->orderBy('created', 'DESC');

    // Apply filters from query parameters.
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

    // Build CSV content.
    $csv_data = [];
    
    // Header row.
    $csv_data[] = [
      'Full Name',
      'Email',
      'College',
      'Department',
      'Category',
      'Event Date',
      'Event Name',
    ];

    // Data rows.
    foreach ($results as $row) {
      $event_name = $event_names[$row->event_id] ?? 'N/A';
      $csv_data[] = [
        $row->full_name,
        $row->email,
        $row->college_name,
        $row->department,
        $row->category,
        $row->event_date,
        $event_name,
      ];
    }

    // Convert to CSV format.
    $output = fopen('php://temp', 'r+');
    foreach ($csv_data as $row) {
      fputcsv($output, $row);
    }
    rewind($output);
    $csv_content = stream_get_contents($output);
    fclose($output);

    // Create response.
    $response = new Response($csv_content);
    $response->headers->set('Content-Type', 'text/csv');
    $response->headers->set('Content-Disposition', 'attachment; filename="event_registrations_' . date('Y-m-d_H-i-s') . '.csv"');

    return $response;
  }

}
