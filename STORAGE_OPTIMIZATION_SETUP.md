# Storage Optimization Module - Setup Guide

## Installation Status

✅ **Installation completed successfully via CLI**
- Database tables created
- Sample data generated (35 files, 4 duplicate groups, 26GB wasted space)

## Issue: Web Interface Can't Find Tables

The error indicates the web server can't see the tables that were created. This is a common issue with different PHP configurations between CLI and web server.

## Quick Fix

### Option 1: Import SQL Directly via phpMyAdmin (Recommended)

1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Select database: `network_security_scanner`
3. Click "Import" tab
4. Choose file: `C:\xampp\htdocs\networkscan\srm_storage_optimization.sql`
5. Click "Go" to import

### Option 2: Verify Database Connection

Check that your `config/database.php` file has the correct database name:

```php
return [
    'host' => 'localhost',
    'port' => '3306',
    'database' => 'network_security_scanner',  // <- Make sure this is correct
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4',
    'options' => [...]
];
```

### Option 3: Run Installation Through Web

1. Create a web-accessible installation page
2. Navigate to: `http://localhost/networkscan/web_install_storage.php`
3. Click the install button

## Verification

After installation, you should be able to access:
- **Dashboard**: `http://localhost/networkscan/modules/srm_optimization.php`

## Expected Results

When properly installed, you'll see:
- **Total Storage**: 45.4 GB
- **Wasted Space**: 26.01 GB
- **Duplicate Groups**: 4 (including 20GB critical and 6GB high priority)
- **Files Scanned**: 35
- **Classifications**: 10 different types
- **Recommendations**: 1 pending

## Sample Data Overview

The system includes:
1. **3x 10GB database backups** (DUP001BACKUP) - 20GB wasted - CRITICAL
2. **4x 2GB marketing videos** (DUP002VIDEO) - 6GB wasted - HIGH
3. **2x document copies** (DUP003DOC) - LOW
4. **5x logo images** (DUP004IMAGE) - LOW

Files span multiple departments:
- Finance (Financial Data - Confidential)
- HR (Human Resources - Restricted)
- IT (Database Backups - Confidential)
- Marketing (Videos, Images - Internal)
- Development (Source Code - Internal)
- Legal (Documents - Restricted)

## Troubleshooting

### Error: "Table doesn't exist"
- Run the SQL import through phpMyAdmin (Option 1 above)
- Verify database name in config file

### Error: "Connection failed"
- Check that XAMPP MySQL is running
- Verify credentials in `config/database.php`

### Sample data missing
- Run: `php generate_storage_sample_data.php`
- Check output for success message

## Manual SQL Import (Alternative)

If automated installation fails, manually create tables by running this SQL in phpMyAdmin:

```sql
USE network_security_scanner;

-- Then paste contents of srm_storage_optimization.sql
```

## Support

If you continue to have issues:
1. Check XAMPP control panel - MySQL must be running
2. Verify database exists: `network_security_scanner`
3. Check PHP error logs in: `C:\xampp\php\logs\php_error_log`
4. Try accessing phpMyAdmin to verify database connectivity

## Next Steps After Successful Installation

1. Access the dashboard: `http://localhost/networkscan/modules/srm_optimization.php`
2. Explore the 5 tabs:
   - **Overview** - See total storage and waste distribution
   - **Deduplication** - View 4 duplicate groups
   - **Classification** - Browse 10 file type categories
   - **Recommendations** - Review optimization suggestions
   - **Storage Tiers** - Understand tiering strategy

3. Generate fresh data anytime: `php generate_storage_sample_data.php`
