# Employee Management System - WordPress Plugin

## Description

A comprehensive Employee Management System plugin for WordPress that provides complete employee lifecycle management including attendance tracking, leave management, task assignment, and payroll management. Includes REST API endpoints for mobile app integration.

## Features

### Core Features
- **Employee Management**: Add, edit, and manage employee records with detailed information
- **Attendance Tracking**: Real-time check-in/check-out system with location tracking
- **Leave Management**: Apply, approve, and track employee leaves
- **Task Management**: Assign and track tasks with priority levels
- **Payroll System**: Manage employee salaries, allowances, and deductions
- **Mobile App Integration**: RESTful API endpoints for Flutter mobile app

### Admin Panel Features
- Modern, responsive dashboard with dark mode support
- Real-time statistics and analytics
- Employee directory with search functionality
- Attendance reports and history
- Leave approval workflow
- Task assignment and tracking
- Payroll management and payslip generation

## Installation

### Requirements
- WordPress 5.0 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher

### Installation Steps

1. **Upload Plugin**
   ```
   - Download the plugin zip file
   - Go to WordPress Admin → Plugins → Add New
   - Click "Upload Plugin" and select the zip file
   - Click "Install Now"
   ```

2. **Activate Plugin**
   ```
   - After installation, click "Activate Plugin"
   - The plugin will automatically create necessary database tables
   ```

3. **Initial Setup**
   ```
   - Go to EMS → Dashboard in your WordPress admin
   - A demo user will be created automatically:
     Username: demo
     Password: demo
   ```

## Configuration

### Creating Demo Data

The plugin automatically creates:
- Demo user account (username: demo, password: demo)
- Sample employee records
- Database tables for all modules

### User Roles

The plugin creates a custom role:
- **EMS Manager**: Can manage all employee data
- **Administrator**: Full access to all features

### API Endpoints

Base URL: `https://yoursite.com/wp-json/ems/v1/mobile/`

#### Authentication
- **POST** `/login` - User login
  ```json
  {
    "username": "demo",
    "password": "demo"
  }
  ```

#### Attendance
- **POST** `/attendance/checkin` - Check in (requires auth)
- **POST** `/attendance/checkout` - Check out (requires auth)

#### Tasks
- **GET** `/tasks` - Get employee tasks (requires auth)
- **POST** `/tasks/update` - Update task status (requires auth)

#### Leaves
- **POST** `/leaves/apply` - Apply for leave (requires auth)
- **GET** `/leaves/history` - Get leave history (requires auth)

#### Profile
- **GET** `/profile` - Get employee profile (requires auth)
- **GET** `/salary` - Get salary information (requires auth)

### Authentication

All protected endpoints require Bearer token authentication:
```
Authorization: Bearer {token}
```

## Database Schema

### Tables Created
1. `wp_ems_employees` - Employee records
2. `wp_ems_attendance` - Attendance logs
3. `wp_ems_leaves` - Leave applications
4. `wp_ems_tasks` - Task assignments
5. `wp_ems_salary` - Payroll records

## Usage

### Admin Panel

1. **Dashboard**
   - View key metrics: Total employees, pending leaves, today's tasks, payroll
   - Quick access to main features
   - Recent activity feed

2. **Employee Management**
   - Add new employees
   - Edit employee details
   - View employee directory
   - Search and filter employees

3. **Attendance**
   - View daily attendance
   - Generate attendance reports
   - Track employee working hours

4. **Leave Management**
   - Review leave applications
   - Approve/reject leaves
   - View leave history
   - Track leave balances

5. **Task Management**
   - Create and assign tasks
   - Set priorities and due dates
   - Track task progress
   - Update task status

6. **Payroll**
   - Manage employee salaries
   - Add allowances and deductions
   - Generate payslips
   - Track payment history

### Mobile App Integration

The plugin provides REST API endpoints for the Flutter mobile app:

1. **Setup Mobile App**
   - Configure the base URL in the mobile app
   - Use demo credentials for testing

2. **Testing API**
   - Use Postman or similar tool
   - Test endpoints with sample data
   - Verify authentication flow

## Troubleshooting

### Common Issues

1. **404 Error on API Endpoints**
   - Go to Settings → Permalinks
   - Click "Save Changes" to flush rewrite rules

2. **Login Failed**
   - Verify demo user exists in Users panel
   - Check WordPress error log for details
   - Deactivate and reactivate plugin

3. **Database Tables Not Created**
   - Deactivate plugin
   - Delete plugin
   - Reinstall and activate

4. **CORS Issues**
   - Plugin includes CORS headers
   - Check server configuration
   - Verify .htaccess rules

### Debug Mode

Enable WordPress debug mode in `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Check logs in: `wp-content/debug.log`

## API Testing

### Using cURL

```bash
# Login
curl -X POST https://yoursite.com/wp-json/ems/v1/mobile/login \
  -H "Content-Type: application/json" \
  -d '{"username":"demo","password":"demo"}'

# Get Tasks (with token)
curl -X GET https://yoursite.com/wp-json/ems/v1/mobile/tasks \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### Using Postman

1. Import collection from documentation
2. Set base URL variable
3. Login to get token
4. Use token for authenticated requests

## Security

- All inputs are sanitized and validated
- SQL queries use prepared statements
- Authentication via secure token system
- CORS headers properly configured
- Nonce verification for AJAX requests

## Customization

### Custom Styling

Add custom CSS in your theme:
```css
.ems-admin {
    /* Your custom styles */
}
```

### Extending API

Add custom endpoints in your theme's `functions.php`:
```php
add_action('rest_api_init', function() {
    register_rest_route('ems/v1', '/custom/endpoint', array(
        'methods' => 'GET',
        'callback' => 'your_callback_function',
    ));
});
```

## Support

For issues and questions:
- Check documentation: [Plugin Documentation]
- WordPress Support Forum: [Support Forum]
- Email: support@yourcompany.com

## Changelog

### Version 1.0.0 (2024-01-01)
- Initial release
- Employee management
- Attendance tracking
- Leave management
- Task management
- Payroll system
- Mobile API integration
- Admin dashboard

## Credits

Developed by: Your Company Name
License: GPL v2 or later
Contributors: Your Team

## License

This plugin is licensed under the GPL v2 or later.

```
Copyright (C) 2024 Your Company Name

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
```