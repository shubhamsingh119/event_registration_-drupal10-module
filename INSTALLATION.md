# Quick Installation Guide

## Prerequisites
- Drupal 10.x installed
- PHP 8.1+
- MySQL/MariaDB
- Composer

## Installation Steps

### 1. Install Drupal 10 (if needed)
```bash
composer create-project drupal/recommended-project:^10 drupal10
cd drupal10
```

### 2. Copy Module
Copy the entire `event_registration` folder to your Drupal installation:
```bash
cp -r web/modules/custom/event_registration /path/to/drupal/web/modules/custom/
```

### 3. Enable Module
Using Drush:
```bash
drush en event_registration -y
drush cr
```

Or via UI:
- Go to `/admin/modules`
- Find "Event Registration" under Custom
- Check the box and click Install

### 4. Configure Permissions
- Go to `/admin/people/permissions`
- Assign these permissions:
  - **Administrator**: All three permissions
  - **Authenticated users**: "register for events"
  - **Custom role** (optional): "access event registrations"

### 5. Configure Email Settings
- Go to `/admin/config/event-registration/settings`
- Enter admin email address
- Enable email notifications

### 6. Create Your First Event
- Go to `/admin/config/event-registration/events`
- Fill in:
  - Event Name: "Test Workshop"
  - Category: "Online Workshop"
  - Registration Start: Today's date
  - Registration End: Date 7 days from now
  - Event Date: Date 10 days from now
- Click "Create Event"

### 7. Test Registration
- Go to `/event/register`
- Fill in the form
- Watch AJAX dropdowns populate
- Submit registration
- Check for confirmation email

## URLs Reference

| URL | Purpose |
|-----|---------|
| `/admin/config/event-registration/events` | Create events |
| `/admin/config/event-registration/settings` | Configure settings |
| `/admin/event-registration/list` | View registrations |
| `/admin/event-registration/export` | Export CSV |
| `/event/register` | Public registration |

## Troubleshooting

**No events showing in registration form?**
- Check that current date is between registration start and end dates

**Emails not sending?**
- Enable notifications in settings
- Configure Drupal mail system
- Check `/admin/reports/dblog` for errors

**AJAX not working?**
- Clear cache: `drush cr`
- Check browser console for errors

## For Development/Testing

If you don't have Drupal installed yet, you can:
1. Install XAMPP/WAMP (includes PHP, MySQL, Apache)
2. Install Composer
3. Follow step 1 above to create Drupal project
4. Continue with steps 2-7

## Module Files Location
```
web/modules/custom/event_registration/
├── event_registration.info.yml
├── event_registration.install
├── event_registration.module
├── event_registration.permissions.yml
├── event_registration.routing.yml
├── event_registration.services.yml
├── event_registration_schema.sql
├── README.md
└── src/
    ├── Controller/
    ├── Form/
    └── Service/
```

## Next Steps After Installation
1. Create multiple events with different categories
2. Test the registration form
3. View registrations in admin panel
4. Test filters and CSV export
5. Verify email notifications

For detailed documentation, see [README.md](file:///c:/Users/Shubhamm/fossee_project/web/modules/custom/event_registration/README.md)
