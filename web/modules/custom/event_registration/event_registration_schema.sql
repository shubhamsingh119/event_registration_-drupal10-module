-- Event Registration Module Database Schema
-- This file contains the SQL schema for the event_registration module

-- Table: event_configuration
-- Stores admin-created events with registration periods
CREATE TABLE IF NOT EXISTS event_configuration (
  id INT AUTO_INCREMENT PRIMARY KEY,
  reg_start_date VARCHAR(20) NOT NULL COMMENT 'Registration start date (YYYY-MM-DD)',
  reg_end_date VARCHAR(20) NOT NULL COMMENT 'Registration end date (YYYY-MM-DD)',
  event_date VARCHAR(20) NOT NULL COMMENT 'Actual event date (YYYY-MM-DD)',
  event_name VARCHAR(255) NOT NULL COMMENT 'Name of the event',
  category VARCHAR(255) NOT NULL COMMENT 'Event category (e.g., Online Workshop, Hackathon)',
  INDEX idx_category (category),
  INDEX idx_event_date (event_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Stores event configuration created by administrators';

-- Table: event_registration
-- Stores user registrations for events
CREATE TABLE IF NOT EXISTS event_registration (
  id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(255) NOT NULL COMMENT 'Full name of the registrant',
  email VARCHAR(255) NOT NULL COMMENT 'Email address of the registrant',
  college_name VARCHAR(255) NOT NULL COMMENT 'College name of the registrant',
  department VARCHAR(255) NOT NULL COMMENT 'Department of the registrant',
  category VARCHAR(255) NOT NULL COMMENT 'Event category',
  event_date VARCHAR(20) NOT NULL COMMENT 'Event date (YYYY-MM-DD)',
  event_id INT NOT NULL COMMENT 'Foreign key to event_configuration table',
  created INT NOT NULL COMMENT 'Timestamp when the registration was created',
  INDEX idx_email (email),
  INDEX idx_event_date (event_date),
  INDEX idx_event_id (event_id),
  FOREIGN KEY (event_id) REFERENCES event_configuration(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Stores user registrations for events';
