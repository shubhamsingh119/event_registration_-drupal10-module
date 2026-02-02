# Event Registration Module

A comprehensive Drupal 10 custom module for managing event registrations with admin configuration, user registration, validation, email notifications, and reporting features.

## Features

- **Admin Event Configuration**: Create and manage events with registration periods
- **Public Registration Form**: User-friendly form with AJAX-dependent dropdowns
- **Validation**: Email format, text field sanitization, and duplicate registration prevention
- **Email Notifications**: Automated emails to users and administrators
- **Admin Dashboard**: View and filter registrations with participant counts
- **CSV Export**: Export registration data for analysis
- **Permissions**: Role-based access control for all features

## Installation

### Prerequisites

- Drupal 10.x
- PHP 8.1 or higher
- MySQL 5.7+ or MariaDB 10.3+
- Composer

### Steps

1. **Copy the module to your Drupal installation:**
   ```bash
   cp -r event_registration /path/to/drupal/web/modules/custom/
   ```

2. **Enable the module using Drush:**
   ```bash
   drush en event_registration -y
   ```

   Or enable via the Drupal admin interface:
   - Navigate to **Extend** (`/admin/modules`)
   - Find "Event Registration" under Custom modules
   - Check the box and click **Install**

3. **Clear cache:**
   ```bash
   drush cr
   ```

4. **Configure permissions:**
   - Navigate to **People > Permissions** (`/admin/people/permissions`)
   - Assign appropriate permissions to roles:
     - `administer event registration` - For administrators
     - `access event registrations` - For viewing registration lists
     - `register for events` - For users who can register

5. **Configure email settings:**
   - Navigate to **Configuration > Event Registration Settings** (`/admin/config/event-registration/settings`)
   - Enter admin email address
   - Enable/disable email notifications

## Database Tables

### event_configuration

Stores admin-created events with registration periods.

| Field           | Type         | Description                                    |
|-----------------|--------------|------------------------------------------------|
| id              | INT (PK)     | Unique event ID                                |
| reg_start_date  | VARCHAR(20)  | Registration start date (YYYY-MM-DD)           |
| reg_end_date    | VARCHAR(20)  | Registration end date (YYYY-MM-DD)             |
| event_date      | VARCHAR(20)  | Actual event date (YYYY-MM-DD)                 |
| event_name      | VARCHAR(255) | Name of the event                              |
| category        | VARCHAR(255) | Event category (e.g., Online Workshop)         |

**Indexes:**
- `idx_category` on `category`
- `idx_event_date` on `event_date`

### event_registration

Stores user registrations for events.

| Field         | Type         | Description                                    |
|---------------|--------------|------------------------------------------------|
| id            | INT (PK)     | Unique registration ID                         |
| full_name     | VARCHAR(255) | Full name of the registrant                    |
| email         | VARCHAR(255) | Email address of the registrant                |
| college_name  | VARCHAR(255) | College name of the registrant                 |
| department    | VARCHAR(255) | Department of the registrant                   |
| category      | VARCHAR(255) | Event category                                 |
| event_date    | VARCHAR(20)  | Event date (YYYY-MM-DD)                        |
| event_id      | INT (FK)     | Foreign key to event_configuration table       |
| created       | INT          | Timestamp when registration was created        |

**Indexes:**
- `idx_email` on `email`
- `idx_event_date` on `event_date`
- `idx_event_id` on `event_id`

**Foreign Key:**
- `event_id` references `event_configuration(id)` with CASCADE on delete

## URLs and Pages

### Admin Pages

| URL                                      | Permission Required              | Description                          |
|------------------------------------------|----------------------------------|--------------------------------------|
| `/admin/config/event-registration/events` | administer event registration    | Create and configure events          |
| `/admin/config/event-registration/settings` | administer event registration  | Configure module settings            |
| `/admin/event-registration/list`         | access event registrations       | View and filter registrations        |
| `/admin/event-registration/export`       | access event registrations       | Export registrations to CSV          |

### Public Pages

| URL                | Permission Required    | Description                          |
|--------------------|------------------------|--------------------------------------|
| `/event/register`  | register for events    | Public event registration form       |

## Validation Logic

The module implements comprehensive validation to ensure data integrity:

### Email Validation
- Uses PHP's `filter_var()` with `FILTER_VALIDATE_EMAIL`
- Ensures proper email format (e.g., user@example.com)
- Applied to both user registration and admin settings

### Text Field Validation
- **Pattern**: Only alphanumeric characters and spaces allowed
- **Regex**: `/^[a-zA-Z0-9\s]+$/`
- **Applied to**:
  - Full Name
  - College Name
  - Department
  - Event Name

### Date Validation
- Registration end date must be after start date
- Event date must be on or after registration end date
- Prevents illogical date configurations

### Duplicate Registration Prevention
- **Check**: Email + Event Date combination
- **Query**: 
  ```sql
  SELECT id FROM event_registration 
  WHERE email = ? AND event_date = ?
  ```
- **Error Message**: "You are already registered for this event."
- Prevents users from registering multiple times for the same event

## Email Functionality

### Configuration
- Admin email address stored in `event_registration.settings` config
- Enable/disable toggle for notifications
- No hardcoded email addresses

### Email Types

#### 1. User Confirmation Email
**Sent to**: User's email address  
**Trigger**: Successful registration  
**Contains**:
- Full name
- Event name
- Event category
- Event date
- College name
- Department

#### 2. Admin Notification Email
**Sent to**: Admin email (from settings)  
**Trigger**: Successful registration  
**Contains**:
- All participant details
- All event details

### Implementation
- Uses Drupal Mail API via `MailManagerInterface`
- Dependency injection for testability
- Implements `hook_mail()` for message formatting
- Only sends if notifications are enabled in settings

## AJAX Functionality

The registration form uses nested AJAX callbacks for dynamic field updates:

### Category → Event Date
1. User selects a category
2. AJAX callback `updateEventDates()` triggers
3. Event date dropdown populates with dates for selected category
4. Only shows events where current date is within registration period

### Event Date → Event Name
1. User selects an event date
2. AJAX callback `updateEventNames()` triggers
3. Event name dropdown populates with events matching category + date
4. Only shows active events

### Active Event Filtering
Events are considered "active" when:
```
current_date >= reg_start_date AND current_date <= reg_end_date
```

## Admin Features

### Registration List
- **Filtering**: By event date and event name (AJAX-dependent)
- **Display**: Sortable table with all registration details
- **Count**: Shows total number of participants
- **Pagination**: Supports large datasets

### CSV Export
- **Format**: Standard CSV with headers
- **Filename**: `event_registrations_YYYY-MM-DD_HH-MM-SS.csv`
- **Columns**:
  - Full Name
  - Email
  - College
  - Department
  - Category
  - Event Date
  - Event Name
- **Filtering**: Respects current filter selections

## Architecture

### Dependency Injection
All classes use proper dependency injection:
- `Connection` for database operations
- `MailManagerInterface` for email
- `ConfigFactoryInterface` for configuration

### Best Practices
- ✅ No use of `\Drupal::service()` in classes
- ✅ Proper use of Config API
- ✅ Database API for all queries
- ✅ Form API for all forms
- ✅ Permissions on all routes
- ✅ Error handling and logging
- ✅ AJAX for better UX

## File Structure

```
event_registration/
├── event_registration.info.yml          # Module metadata
├── event_registration.install           # Database schema and hooks
├── event_registration.module            # Hook implementations
├── event_registration.permissions.yml   # Permission definitions
├── event_registration.routing.yml       # Route definitions
├── event_registration.services.yml      # Service definitions
├── event_registration_schema.sql        # SQL export for submission
├── README.md                            # This file
└── src/
    ├── Controller/
    │   ├── RegistrationExportController.php    # CSV export
    │   └── RegistrationListController.php      # Admin listing
    ├── Form/
    │   ├── EventConfigForm.php                 # Admin event config
    │   ├── EventRegistrationForm.php           # Public registration
    │   ├── RegistrationFilterForm.php          # Admin filter form
    │   └── SettingsForm.php                    # Module settings
    └── Service/
        └── MailService.php                     # Email service
```

## Usage Guide

### For Administrators

1. **Create an Event**:
   - Go to `/admin/config/event-registration/events`
   - Fill in event details (name, category, dates)
   - Click "Create Event"

2. **Configure Email Settings**:
   - Go to `/admin/config/event-registration/settings`
   - Enter your admin email
   - Enable notifications if desired

3. **View Registrations**:
   - Go to `/admin/event-registration/list`
   - Use filters to narrow down results
   - View participant count

4. **Export Data**:
   - Apply desired filters
   - Click "Export to CSV"
   - Download the generated file

### For Users

1. **Register for an Event**:
   - Go to `/event/register`
   - Fill in personal details
   - Select event category (dropdown populates from active events)
   - Select event date (based on category)
   - Select event name (based on category + date)
   - Click "Register"
   - Receive confirmation email

## Troubleshooting

### Emails Not Sending
- Check that notifications are enabled in settings
- Verify admin email is configured
- Check Drupal's mail configuration
- Review logs at `/admin/reports/dblog`
- **Note for Localhost/XAMPP Users**: If you are running this locally without a configured mail server (e.g., Sendmail/Postfix), you will see an "Unable to send email" error. This is expected behavior for the environment; the module logic is correct and will function properly on a live server.

### AJAX Not Working
- Clear Drupal cache: `drush cr`
- Check browser console for JavaScript errors
- Ensure jQuery is loaded

### No Events Showing in Registration Form
- Verify events are created in admin
- Check that current date is within registration period
- Ensure events have valid dates

## Development Notes

### Adding New Event Categories
Edit `EventConfigForm.php` line 68 to add options to the category dropdown.

### Customizing Email Templates
Modify `MailService.php` methods:
- `buildUserEmailBody()` for user emails
- `buildAdminEmailBody()` for admin emails

### Extending Validation
Add custom validation in form classes:
- `EventConfigForm::validateForm()`
- `EventRegistrationForm::validateForm()`

## License

This module is provided as-is for educational purposes.

## Support

For issues or questions, please contact the module maintainer or refer to Drupal.org documentation.

## Version

**Version**: 1.0.0  
**Drupal**: 10.x  
**Last Updated**: February 2026
