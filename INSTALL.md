# Installation Guide

## Quick Start

### Automated Setup (Recommended)

1. **Run the setup wizard:**
```bash
php setup.php
```

The wizard will:
- Check PHP requirements
- Configure database connection
- Create database and tables
- Initialize compliance controls
- Set up report directories

2. **Access the application:**
```
http://localhost/networkscan/
```

---

## Manual Installation

### Step 1: Requirements Check

Verify your system meets these requirements:

- **PHP**: 7.4 or higher
- **MySQL**: 5.7 or higher
- **Web Server**: Apache/Nginx (XAMPP, WAMP, or similar)

Required PHP extensions:
- pdo
- pdo_mysql
- json
- mbstring
- openssl

Check your PHP version:
```bash
php -v
```

Check installed extensions:
```bash
php -m
```

### Step 2: Download Files

Place all files in your web server directory:
```
C:\xampp\htdocs\networkscan\
```

Or on Linux/Mac:
```
/var/www/html/networkscan/
```

### Step 3: Database Setup

#### Option A: Using MySQL Command Line

```bash
# Login to MySQL
mysql -u root -p

# Create database
CREATE DATABASE network_security_scanner CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Import schema
USE network_security_scanner;
SOURCE /path/to/networkscan/database/schema.sql;

# Verify tables
SHOW TABLES;
```

#### Option B: Using phpMyAdmin

1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Create new database: `network_security_scanner`
3. Select the database
4. Click "Import" tab
5. Choose `database/schema.sql`
6. Click "Go"

### Step 4: Configure Database Connection

Edit `config/database.php`:

```php
return [
    'host' => 'localhost',        // Your MySQL host
    'port' => '3306',             // MySQL port
    'database' => 'network_security_scanner',
    'username' => 'root',         // Your MySQL username
    'password' => '',             // Your MySQL password
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    // ... rest of config
];
```

### Step 5: Set Permissions

Create and set permissions for reports directory:

**Windows:**
```cmd
mkdir reports
```

**Linux/Mac:**
```bash
mkdir reports
chmod 755 reports
chown www-data:www-data reports
```

### Step 6: Initialize Compliance Controls

Run from command line:
```bash
php -r "
require 'classes/Database.php';
require 'classes/ComplianceChecker.php';
\$c = new ComplianceChecker();
\$c->initializeDefaultControls();
echo 'Compliance controls initialized\n';
"
```

### Step 7: Test Installation

1. **Access web interface:**
```
http://localhost/networkscan/
```

2. **Test CLI:**
```bash
php scan_cli.php --help
```

3. **Test API:**
```bash
curl http://localhost/networkscan/api.php?action=stats
```

---

## Troubleshooting

### Database Connection Failed

**Error:** `Failed to connect to database`

**Solution:**
1. Verify MySQL is running
2. Check credentials in `config/database.php`
3. Ensure database exists
4. Check user permissions:
```sql
GRANT ALL PRIVILEGES ON network_security_scanner.* TO 'root'@'localhost';
FLUSH PRIVILEGES;
```

### PHP Version Error

**Error:** `PHP 7.4 or higher required`

**Solution:**
- Update PHP version
- On XAMPP, download latest version
- On Linux: `sudo apt-get install php8.1` (or appropriate version)

### Missing PHP Extensions

**Error:** `Extension not loaded`

**Solution:**

**Windows (XAMPP):**
1. Edit `php.ini`
2. Uncomment extension lines:
```ini
extension=pdo_mysql
extension=mbstring
extension=openssl
```
3. Restart Apache

**Linux:**
```bash
sudo apt-get install php-pdo php-mysql php-mbstring php-json
sudo systemctl restart apache2
```

### Permission Denied (Reports)

**Error:** `Failed to write report`

**Solution:**
```bash
# Linux/Mac
sudo chown -R www-data:www-data reports/
sudo chmod -R 755 reports/

# Windows - Run as Administrator
icacls reports /grant Users:F /T
```

### Port Scanning Not Working

**Error:** `Connection timeout` or `Operation not permitted`

**Possible Causes:**
1. Firewall blocking outbound connections
2. Network restrictions
3. PHP timeout too short

**Solutions:**
1. Check firewall rules
2. Increase timeout in `classes/PortScanner.php`:
```php
private $timeout = 5; // Increase from 2 to 5
```
3. Adjust PHP max execution time:
```php
set_time_limit(300); // 5 minutes
```

### Import Schema Failed

**Error:** During database import

**Solution:**
1. Check MySQL user has CREATE privileges
2. Import manually in sections:
```bash
# Split large schema into parts
mysql -u root -p network_security_scanner < schema_part1.sql
```

---

## Post-Installation Configuration

### 1. Security Hardening

**Change default credentials:**
- Update MySQL root password
- Create dedicated database user:
```sql
CREATE USER 'scanner'@'localhost' IDENTIFIED BY 'strong_password';
GRANT ALL PRIVILEGES ON network_security_scanner.* TO 'scanner'@'localhost';
```

**Restrict web access:**
- Add `.htaccess` for authentication
- Use HTTPS in production
- Implement IP whitelisting

### 2. Performance Optimization

**MySQL optimization:**
```sql
# Edit my.cnf or my.ini
[mysqld]
innodb_buffer_pool_size = 256M
max_connections = 100
query_cache_size = 32M
```

**PHP optimization:**
```ini
# php.ini
memory_limit = 256M
max_execution_time = 300
max_input_time = 300
```

### 3. Scheduled Scans (Optional)

**Linux Cron:**
```bash
crontab -e

# Add daily scan at 2 AM
0 2 * * * cd /var/www/html/networkscan && php scan_cli.php --target 192.168.1.0/24 --type full --report html
```

**Windows Task Scheduler:**
1. Open Task Scheduler
2. Create Basic Task
3. Trigger: Daily
4. Action: Start Program
   - Program: `C:\xampp\php\php.exe`
   - Arguments: `scan_cli.php --target 192.168.1.0/24 --type full`
   - Start in: `C:\xampp\htdocs\networkscan`

---

## Verification

After installation, verify everything works:

### 1. Database Check
```bash
php -r "
require 'classes/Database.php';
try {
    \$db = Database::getInstance();
    echo 'Database connection: OK\n';
} catch (Exception \$e) {
    echo 'Database connection: FAILED - ' . \$e->getMessage() . '\n';
}
"
```

### 2. Test Scan
```bash
php scan_cli.php --target 127.0.0.1 --type quick
```

### 3. API Test
```bash
curl -s http://localhost/networkscan/api.php?action=stats | php -r 'echo json_encode(json_decode(file_get_contents("php://stdin")), JSON_PRETTY_PRINT);'
```

---

## Upgrade from Previous Version

If upgrading:

1. **Backup database:**
```bash
mysqldump -u root -p network_security_scanner > backup.sql
```

2. **Backup files:**
```bash
cp -r networkscan networkscan_backup
```

3. **Update files:**
- Replace all files except `config/database.php`
- Merge any custom changes

4. **Update database:**
```bash
mysql -u root -p network_security_scanner < database/migrations/upgrade.sql
```

---

## Getting Help

If you encounter issues:

1. Check `error_log` in web server directory
2. Enable debug mode in `config/database.php`
3. Check MySQL error logs
4. Review README.md for usage examples
5. Verify all requirements are met

---

## Next Steps

After successful installation:

1. Read [README.md](README.md) for usage guide
2. Review security best practices
3. Configure compliance frameworks
4. Set up scheduled scans
5. Customize vulnerability database

**Important:** Only scan networks you have permission to test!

---

**Installation Support:**
- Check all requirements are met
- Follow steps in exact order
- Review error messages carefully
- Verify file permissions
- Test with simple scans first
