# Troubleshooting Guide

## Quick Fixes

### Issue: "WARNING - Missing tables" or Database Errors

**Symptoms:**
- Missing tables warning
- "Trying to access array offset on value of type bool"
- Database connection failures

**Solution - Automatic Fix:**

```bash
# Run the repair script
php fix_database.php
```

**Solution - Manual Fix:**

```bash
# Windows (using XAMPP)
cd C:\xampp\htdocs\networkscan

# Option 1: Use the batch file
IMPORT_DATABASE.bat

# Option 2: Import manually
C:\xampp\mysql\bin\mysql.exe -u root -p network_security_scanner < database\schema.sql
C:\xampp\mysql\bin\mysql.exe -u root -p network_security_scanner < database\cms_tables.sql
```

**Or use phpMyAdmin:**
1. Go to http://localhost/phpmyadmin
2. Select `network_security_scanner` database
3. Click "Import" tab
4. Import `database/schema.sql`
5. Import `database/cms_tables.sql`

---

## Common Errors & Solutions

### Error: "Config file not found"

**Cause:** Missing `config/database.php`

**Solution:**
```bash
# Run setup wizard
php setup.php

# Or run fix script
php fix_database.php
```

The script will create a default config automatically.

---

### Error: "Trying to access array offset on value of type bool"

**Cause:** Config file returns `false` instead of array

**Root Causes:**
1. Config file doesn't exist
2. Syntax error in config file
3. Wrong file path (case sensitivity)

**Solution:**
```bash
# Check if config exists
dir config\database.php

# If missing, run:
php fix_database.php
```

**Manual fix - create config/database.php:**
```php
<?php
return [
    'host' => 'localhost',
    'port' => '3306',
    'database' => 'network_security_scanner',
    'username' => 'root',
    'password' => '',  // XAMPP default is blank
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_PERSISTENT => false
    ]
];
```

---

### Error: "Access denied for user 'root'@'localhost'"

**Cause:** Wrong MySQL password

**Solution:**

1. **Find your MySQL password:**
   - Open XAMPP Control Panel
   - Click "Shell" button
   - Type: `mysqladmin -u root password`

2. **If you forgot the password:**
   ```bash
   # Stop MySQL in XAMPP Control Panel

   # Edit my.ini (click Config button next to MySQL)
   # Add under [mysqld]:
   skip-grant-tables

   # Restart MySQL

   # Reset password:
   mysql -u root
   USE mysql;
   UPDATE user SET authentication_string=PASSWORD('newpassword') WHERE User='root';
   FLUSH PRIVILEGES;
   EXIT;

   # Remove skip-grant-tables from my.ini
   # Restart MySQL
   ```

3. **Update config:**
   Edit `config/database.php` and set your password.

---

### Error: "Database network_security_scanner does not exist"

**Solution:**
```bash
# Create database manually
mysql -u root -p
CREATE DATABASE network_security_scanner CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;

# Then import schema
mysql -u root -p network_security_scanner < database\schema.sql
```

**Or run:**
```bash
php fix_database.php
```

---

### Error: "Port 3306 already in use"

**Cause:** Another MySQL instance is running

**Solution:**

1. **Check what's using port 3306:**
   ```bash
   netstat -ano | findstr :3306
   ```

2. **Options:**
   - Stop other MySQL service
   - Change MySQL port in XAMPP (my.ini)
   - Use different port in config/database.php

---

### Error: "XAMPP MySQL won't start"

**Solutions:**

1. **Port conflict:**
   - Check if another MySQL is running
   - Change MySQL port to 3307

2. **Corrupted data:**
   - Backup `C:\xampp\mysql\data`
   - Delete `ibdata1` and `ib_logfile*`
   - Restart MySQL

3. **Check error log:**
   - `C:\xampp\mysql\data\mysql_error.log`

---

### Error: "Fatal error: Uncaught Exception: Failed to connect"

**Cause:** MySQL not running or wrong credentials

**Solution:**
1. Start MySQL in XAMPP Control Panel
2. Verify credentials in `config/database.php`
3. Test connection: `php test_db.php`

---

### Error: "404 Not Found" when accessing pages

**Cause:** Wrong URL or Apache not running

**Solution:**

1. **Check Apache is running** in XAMPP Control Panel

2. **Verify correct URL:**
   - ✅ `http://localhost/networkscan/`
   - ❌ `http://localhost/NetworkScan/` (case matters on Linux)
   - ❌ `http://localhost/` (missing folder)

3. **Check file location:**
   ```bash
   # Files should be in:
   C:\xampp\htdocs\networkscan\

   # Not in:
   C:\xampp\networkscan\  (wrong location)
   ```

---

### Warning: "Folder name case mismatch"

**Issue:** Your path shows `NetworkScan` but code expects `networkscan`

**Windows is case-insensitive but Linux isn't:**

**Solution:**
```bash
# Rename folder to lowercase
cd C:\xampp\htdocs
rename NetworkScan networkscan
```

---

### Error: "Table doesn't exist"

**Cause:** Schema not imported

**Check which tables exist:**
```sql
USE network_security_scanner;
SHOW TABLES;
```

**Should see 16+ tables including:**
- scans
- hosts
- ports
- vulnerabilities
- scan_results
- mitigation_plans
- reports
- compliance_frameworks
- compliance_controls
- compliance_checks
- settings (CMS)
- tasks (CMS)
- admin_users (CMS)

**If tables missing:**
```bash
php fix_database.php
```

---

### Error: "Logo not uploading"

**Cause:** Upload directory not writable

**Solution:**
```bash
# Create directory
mkdir assets\uploads

# Windows: Set permissions (Run as Administrator)
icacls assets\uploads /grant Users:F /T
```

---

### Error: "Admin login not working"

**Cause:** Session or admin_users table missing

**Solutions:**

1. **Check table exists:**
   ```sql
   SELECT * FROM admin_users WHERE username='admin';
   ```

2. **If missing, import CMS tables:**
   ```bash
   mysql -u root -p network_security_scanner < database\cms_tables.sql
   ```

3. **Clear browser cookies/cache**

4. **Check session save path:**
   ```php
   <?php
   echo session_save_path();
   // Make sure this directory is writable
   ?>
   ```

---

## Verification Steps

### 1. Check MySQL Status
```bash
# In XAMPP Control Panel, MySQL should show "Running" (green)
```

### 2. Test Database Connection
```bash
php test_db.php
```

**Expected output:**
```
1. Checking config file... ✓ OK
2. Loading configuration... ✓ OK
3. Connecting to MySQL server... ✓ OK
4. Checking database existence... ✓ OK
5. Connecting to database... ✓ OK
6. Checking tables... ✓ OK (16 tables found)
7. Testing Database class... ✓ OK
```

### 3. Verify Tables
```bash
php -r "require 'classes/Database.php'; $db = Database::getInstance(); echo 'Connected OK!'; "
```

### 4. Test Web Access
```
http://localhost/networkscan/info.html
```

Should show file structure and PHP info.

---

## Database Schema Issues

### Missing Tables After Import

**Check for errors during import:**
```bash
mysql -u root -p network_security_scanner < database\schema.sql 2> errors.txt
type errors.txt
```

**Common issues:**
- Syntax errors (we fixed the `references` → `external_references` issue)
- Permission denied
- Invalid SQL commands

### Fix Specific Tables

**Import only what's missing:**
```sql
USE network_security_scanner;

-- If scans table missing:
SOURCE database/schema.sql;

-- If CMS tables missing:
SOURCE database/cms_tables.sql;
```

---

## Performance Issues

### Slow Queries

**Enable query logging:**
```sql
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 2;
```

**Check slow queries:**
```bash
type C:\xampp\mysql\data\slow_query.log
```

### Database Size

**Check database size:**
```sql
SELECT
    table_schema AS 'Database',
    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'Size (MB)'
FROM information_schema.tables
WHERE table_schema = 'network_security_scanner'
GROUP BY table_schema;
```

---

## Complete Reset

**If all else fails, start fresh:**

```bash
# 1. Drop and recreate database
mysql -u root -p
DROP DATABASE IF EXISTS network_security_scanner;
CREATE DATABASE network_security_scanner CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;

# 2. Import fresh schema
mysql -u root -p network_security_scanner < database\schema.sql
mysql -u root -p network_security_scanner < database\cms_tables.sql

# 3. Verify
php test_db.php

# 4. Access application
# http://localhost/networkscan/
```

---

## Getting Help

### Collect Diagnostic Info

Run this before asking for help:

```bash
php -v  # PHP version
php -m  # PHP modules

mysql --version  # MySQL version

php test_db.php > diagnostic.txt 2>&1
```

### Check Logs

**PHP errors:**
- `C:\xampp\php\logs\php_error_log`

**Apache errors:**
- `C:\xampp\apache\logs\error.log`

**MySQL errors:**
- `C:\xampp\mysql\data\mysql_error.log`

### Common Log Messages

**"Table doesn't exist"** → Import schema
**"Access denied"** → Check credentials
**"Can't connect to MySQL"** → Start MySQL service
**"Unknown column"** → Schema mismatch, re-import

---

## Prevention

### Before Updating

```bash
# Backup database
mysqldump -u root -p network_security_scanner > backup.sql

# Backup files
xcopy /E /I /Y networkscan networkscan_backup
```

### Regular Maintenance

1. **Weekly:** Check scan logs for errors
2. **Monthly:** Backup database
3. **Quarterly:** Review and archive old scans
4. **As needed:** Update vulnerability database

---

## Quick Command Reference

```bash
# Test everything
php fix_database.php

# Test database only
php test_db.php

# Import schema
mysql -u root -p network_security_scanner < database\schema.sql

# Import CMS tables
mysql -u root -p network_security_scanner < database\cms_tables.sql

# Run setup wizard
php setup.php

# Start scan
php scan_cli.php --target 127.0.0.1 --type quick

# Check PHP info
php -i | more

# Check loaded extensions
php -m

# Test config loading
php -r "var_dump(require 'config/database.php');"
```

---

**Still stuck?** Make sure you've:
1. ✅ Installed XAMPP correctly
2. ✅ Started Apache and MySQL
3. ✅ Created the database
4. ✅ Imported both SQL files
5. ✅ Config file exists with correct credentials
6. ✅ Files in `C:\xampp\htdocs\networkscan\`
7. ✅ Cleared browser cache
8. ✅ Checked error logs
