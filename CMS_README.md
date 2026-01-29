# CMS Admin Portal Documentation

## Overview

The CMS (Content Management System) Admin Portal provides centralized management for your Network Security Scanner installation. Administrators can upload logos, configure settings, manage tasks, and review SolarWinds benchmark recommendations.

## Features

### 1. **Dashboard Management**
- Upload and manage company logo
- Configure application name and branding
- Set theme colors
- Manage support contact information

### 2. **Task Manager**
- Create and track security assessment tasks
- Priority-based task organization (Critical, High, Medium, Low)
- Task categorization (Feature, Enhancement, Security, Bug Fix, etc.)
- Due date tracking
- Status management (Pending, In Progress, Completed)
- Pre-loaded SolarWinds benchmark tasks

### 3. **SolarWinds Benchmark**
- Detailed feature comparison matrix
- Gap analysis and recommendations
- Implementation roadmap (12-month plan)
- Cost-benefit analysis
- Gartner Magic Quadrant positioning

### 4. **Settings Management**
- Application configuration
- Email settings
- Retention policies
- Alert preferences

## Installation

### Step 1: Import CMS Tables

The CMS requires additional database tables. Import them after the main schema:

```bash
# Import CMS tables
mysql -u root -p network_security_scanner < database/cms_tables.sql
```

Or using phpMyAdmin:
1. Select the `network_security_scanner` database
2. Go to "Import" tab
3. Choose `database/cms_tables.sql`
4. Click "Go"

### Step 2: Verify Tables Created

Check that these tables exist:
- `settings` - Application configuration
- `tasks` - Task management
- `task_comments` - Task discussions
- `admin_users` - CMS user accounts

### Step 3: Access Admin Portal

Navigate to:
```
http://localhost/networkscan/admin/login.php
```

**Default Credentials:**
- Username: `admin`
- Password: `admin123`

⚠️ **IMPORTANT**: Change these credentials immediately in production!

## User Guide

### Logging In

1. Navigate to `/admin/login.php`
2. Enter username and password
3. Click "Login"
4. You'll be redirected to the admin dashboard

### Uploading a Logo

1. Go to **Dashboard** (default landing page)
2. Scroll to "Company Logo" section
3. Click "Choose Logo"
4. Select an image file (JPG, PNG, GIF, or SVG)
5. Click "Upload Logo"
6. Logo will appear on the main dashboard immediately

**Recommended Logo Specs:**
- Dimensions: 200x60 pixels
- Format: PNG (transparent background preferred)
- Max file size: 2MB

### Configuring Application Settings

1. Go to **Dashboard**
2. Scroll to "Application Settings"
3. Update fields:
   - **Application Name**: Shown in header and browser title
   - **Company Name**: Your organization name
   - **Support Email**: Contact email for support
   - **Theme Color**: Primary color for branding
4. Click "Save Settings"

### Managing Tasks

#### Creating a Task

1. Go to **Task Manager** from sidebar
2. Fill in the form:
   - **Task Title**: Short description (required)
   - **Description**: Detailed information
   - **Priority**: Critical, High, Medium, or Low
   - **Category**: Feature, Enhancement, Security, etc.
   - **Due Date**: Optional deadline
3. Click "Create Task"

#### Filtering Tasks

Use the filter section to view specific tasks:
- **Status**: All, Pending, In Progress, Completed
- **Priority**: All, Critical, High, Medium, Low
- **Category**: All categories or specific type
- Click "Apply Filters"

#### Updating Task Status

1. Find the task in the list
2. Click:
   - **Start**: Move from Pending to In Progress
   - **Complete**: Mark task as completed
   - **Delete**: Remove task permanently

#### Task Priority Colors

Tasks are color-coded by priority:
- 🔴 **Red border**: Critical
- 🟠 **Orange border**: High
- 🟡 **Yellow border**: Medium
- 🟢 **Green border**: Low

### Reviewing SolarWinds Benchmark

1. Go to **SolarWinds Benchmark** from sidebar
2. Review sections:
   - **Feature Comparison Matrix**: Side-by-side comparison
   - **Strengths & Weaknesses**: What we do well and gaps
   - **Implementation Roadmap**: 12-month development plan
   - **Cost-Benefit Analysis**: Savings vs. SolarWinds
   - **Strategic Recommendations**: Action items

3. **Pre-loaded Tasks**: The benchmark analysis automatically created 15 priority tasks based on SolarWinds feature gaps

### Logging Out

Click the "Logout" button in the top-right corner of any admin page.

## Security Recommendations

### Change Default Password

**CRITICAL**: Change the default admin password immediately!

1. Access your database
2. Generate new password hash:
```php
<?php
echo password_hash('your_new_password', PASSWORD_DEFAULT);
?>
```

3. Update in database:
```sql
UPDATE admin_users
SET password_hash = 'your_generated_hash'
WHERE username = 'admin';
```

### Add Additional Admin Users

```sql
INSERT INTO admin_users (username, password_hash, email, full_name, role)
VALUES ('newadmin', 'password_hash_here', 'admin@company.com', 'John Doe', 'admin');
```

Roles available:
- `admin` - Full access
- `analyst` - View and edit (planned)
- `viewer` - Read-only access (planned)

### Restrict Admin Access

Add authentication to your web server configuration:

**Apache (.htaccess in /admin/ folder):**
```apache
<Files "*.php">
    AuthType Basic
    AuthName "Admin Area"
    AuthUserFile /path/to/.htpasswd
    Require valid-user
</Files>
```

**Nginx:**
```nginx
location /networkscan/admin/ {
    auth_basic "Admin Area";
    auth_basic_user_file /etc/nginx/.htpasswd;
}
```

### Enable HTTPS

In production, always use HTTPS:
1. Obtain SSL certificate (Let's Encrypt is free)
2. Configure your web server for SSL
3. Redirect HTTP to HTTPS

## Customization

### Changing Admin Portal Colors

Edit `/admin/style.css`:

```css
/* Change primary gradient */
.btn-primary,
.header {
    background: linear-gradient(135deg, #YOUR_COLOR_1, #YOUR_COLOR_2);
}
```

### Adding Custom Pages

1. Create new PHP file in `/admin/` folder
2. Copy structure from existing pages (include header.php, sidebar.php)
3. Add menu item to `sidebar.php`:
```php
<li>
    <a href="yourpage.php">
        <span class="icon">🆕</span>
        <span>Your Page</span>
    </a>
</li>
```

### Custom Task Categories

Add new categories in the task form (`tasks.php`):
```html
<option value="yourcategory">Your Category</option>
```

## API Integration

The CMS settings are accessible via the main API:

```bash
# Get settings
curl http://localhost/networkscan/api.php?action=settings

# Update setting (requires authentication)
curl -X POST http://localhost/networkscan/api.php \
  -d "action=update_setting" \
  -d "key=app_name" \
  -d "value=My Custom Name"
```

## Troubleshooting

### Issue: Logo Not Displaying

**Solution:**
1. Check file upload succeeded (should see success message)
2. Verify file in `/assets/uploads/` folder
3. Check file permissions (755 for folder, 644 for files)
4. Clear browser cache
5. Inspect database:
```sql
SELECT setting_value FROM settings WHERE setting_key = 'logo_url';
```

### Issue: Tasks Not Saving

**Solution:**
1. Verify `tasks` table exists
2. Check database connection
3. Look for PHP errors in error log
4. Ensure form fields are filled correctly

### Issue: Can't Login

**Solution:**
1. Verify `admin_users` table has default admin user
2. Check password hash is correct
3. Clear browser cookies/cache
4. Try in incognito/private browsing mode
5. Check PHP session.save_path is writable

### Issue: Settings Not Applying to Dashboard

**Solution:**
1. Clear browser cache
2. Verify settings saved in database:
```sql
SELECT * FROM settings;
```
3. Check dashboard PHP code includes settings loading
4. Restart web server

## Database Schema

### Settings Table
```sql
CREATE TABLE settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE,
    setting_value TEXT,
    setting_type ENUM('string', 'integer', 'boolean', 'json'),
    description TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### Tasks Table
```sql
CREATE TABLE tasks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    priority ENUM('critical', 'high', 'medium', 'low'),
    status ENUM('pending', 'in_progress', 'completed', 'cancelled'),
    category VARCHAR(50),
    assigned_to VARCHAR(100),
    due_date DATE,
    scan_id INT,
    host_id INT,
    vulnerability_id INT,
    created_by VARCHAR(100),
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    completed_at DATETIME
);
```

## Backup & Restore

### Backup Settings and Tasks

```bash
# Backup CMS data
mysqldump -u root -p network_security_scanner settings tasks task_comments admin_users > cms_backup.sql

# Backup uploaded files
tar -czf uploads_backup.tar.gz assets/uploads/
```

### Restore

```bash
# Restore database
mysql -u root -p network_security_scanner < cms_backup.sql

# Restore files
tar -xzf uploads_backup.tar.gz
```

## Best Practices

1. **Regular Backups**: Backup settings and tasks weekly
2. **Password Rotation**: Change admin passwords every 90 days
3. **Task Management**: Review and update tasks weekly
4. **Logo Guidelines**: Use professional, high-resolution logos
5. **Monitoring**: Check admin access logs regularly
6. **Updates**: Keep system updated with latest security patches

## Support

For issues with the CMS portal:

1. Check this documentation
2. Review error logs (PHP error log, MySQL error log)
3. Verify database tables exist and have correct schema
4. Check file permissions
5. Test with default configuration

## Future Enhancements

Planned features for future versions:

- [ ] User management interface (add/edit/delete users)
- [ ] Backup/restore functionality in UI
- [ ] Activity log viewer
- [ ] Email testing tool
- [ ] Theme customization wizard
- [ ] Multi-language support
- [ ] Advanced user roles and permissions
- [ ] Task comments and collaboration
- [ ] File attachment to tasks
- [ ] Gantt chart for task timeline
- [ ] Dashboard widget customization

---

## Quick Reference

### Admin Portal URLs
- Login: `/admin/login.php`
- Dashboard: `/admin/index.php`
- Tasks: `/admin/tasks.php`
- Benchmark: `/admin/recommendations.php`
- Settings: `/admin/settings.php` (coming soon)

### Default Credentials
- Username: `admin`
- Password: `admin123`

### File Locations
- Admin files: `/admin/`
- Uploaded logos: `/assets/uploads/`
- CMS styles: `/admin/style.css`
- CMS JavaScript: `/admin/script.js`
- Database schema: `/database/cms_tables.sql`

### Database Tables
- `settings` - Application configuration
- `tasks` - Task management
- `task_comments` - Task discussions
- `admin_users` - CMS users

---

**Version**: 1.0
**Last Updated**: January 2025
**Maintained by**: Network Security Scanner Team
