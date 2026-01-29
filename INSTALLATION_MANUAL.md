# Network Security Scanner - Installation & User Manual

## Table of Contents
1. [System Requirements](#system-requirements)
2. [Installation](#installation)
3. [Initial Setup](#initial-setup)
4. [User Authentication](#user-authentication)
5. [Dashboard Overview](#dashboard-overview)
6. [Managing Tenants](#managing-tenants)
7. [Deploying Security Agents](#deploying-security-agents)
8. [Running Network Scans](#running-network-scans)
9. [Viewing Reports](#viewing-reports)
10. [Troubleshooting](#troubleshooting)
11. [API Documentation](#api-documentation)

---

## System Requirements

### Server Requirements
- **Operating System:** Windows Server 2012+ or Linux (Ubuntu 18.04+, CentOS 7+)
- **Web Server:** Apache 2.4+ or Nginx 1.18+
- **PHP:** Version 8.0 or higher
- **Database:** MySQL 5.7+ or MariaDB 10.3+
- **Memory:** Minimum 4GB RAM (8GB recommended)
- **Disk Space:** Minimum 10GB free space

### Client Requirements (For Agents)
- **Windows:** Windows 7+ with PowerShell 3.0+
- **Linux:** Ubuntu, CentOS, Debian (coming soon)
- **Network:** HTTP/HTTPS access to the scanner server

### Browser Requirements
- Google Chrome 90+
- Mozilla Firefox 88+
- Microsoft Edge 90+
- Safari 14+

---

## Installation

### Step 1: Install XAMPP (Windows)

1. **Download XAMPP:**
   - Visit https://www.apachefriends.org/
   - Download XAMPP for Windows (PHP 8.x version)

2. **Install XAMPP:**
   ```
   - Run the installer
   - Choose installation directory: C:\xampp
   - Select components: Apache, MySQL, PHP, phpMyAdmin
   - Complete the installation
   ```

3. **Start Services:**
   ```
   - Open XAMPP Control Panel
   - Start Apache
   - Start MySQL
   ```

### Step 2: Deploy Application Files

1. **Extract Application:**
   ```
   Copy all network scanner files to:
   C:\xampp\htdocs\networkscan\
   ```

2. **Verify Directory Structure:**
   ```
   C:\xampp\htdocs\networkscan\
   ├── classes/
   │   ├── Database.php
   │   ├── NetworkScanner.php
   │   ├── VulnerabilityScanner.php
   │   └── ParallelScanner.php
   ├── index.php
   ├── login.php
   ├── agents.php
   ├── tenants.php
   ├── view_report.php
   ├── agent_api.php
   ├── create_agent_installer.php
   └── setup_authentication.php
   ```

### Step 3: Configure Database

1. **Access phpMyAdmin:**
   ```
   Open browser: http://localhost/phpmyadmin
   Username: root
   Password: (leave blank for default XAMPP)
   ```

2. **Create Database:**
   ```sql
   CREATE DATABASE network_security CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

3. **Import Database Schema:**
   - Click on `network_security` database
   - Go to Import tab
   - Select `database_schema.sql` file
   - Click "Go" to import

### Step 4: Configure Application

1. **Update Database Connection:**

   Edit `C:\xampp\htdocs\networkscan\classes\Database.php`:
   ```php
   private $host = "localhost";
   private $db_name = "network_security";
   private $username = "root";
   private $password = ""; // Change if you set MySQL password
   ```

2. **Set File Permissions:**
   - Ensure Apache has read/write access to the directory
   - On Windows, this is usually automatic

---

## Initial Setup

### Step 1: Run Setup Scripts

1. **Create Multi-Tenancy Structure:**
   ```bash
   C:\xampp\php\php.exe C:\xampp\htdocs\networkscan\setup_multitenancy.php
   ```

2. **Setup Authentication System:**
   ```bash
   C:\xampp\php\php.exe C:\xampp\htdocs\networkscan\setup_authentication.php
   ```

   This creates default admin account:
   - Username: `admin`
   - Password: `admin123`

3. **Create Agent Tables:**
   ```bash
   C:\xampp\php\php.exe C:\xampp\htdocs\networkscan\create_agent_tables.php
   ```

### Step 2: Verify Installation

1. **Access Login Page:**
   ```
   http://localhost/networkscan/login.php
   ```

2. **Test Login:**
   - Username: `admin`
   - Password: `admin123`

3. **You should see the dashboard upon successful login**

---

## User Authentication

### Login Process

1. **Navigate to Login Page:**
   ```
   http://localhost/networkscan/login.php
   ```

2. **Enter Credentials:**
   - Username: Your assigned username
   - Password: Your password

3. **Session Information:**
   - Sessions expire after 8 hours of inactivity
   - You'll be redirected to login if session expires

### Default Accounts

After running `setup_authentication.php`, these accounts are created:

| Tenant | Username | Password | Role |
|--------|----------|----------|------|
| Default Organization | admin | admin123 | admin |
| Bitstech | bits0001_admin | admin123 | admin |
| Petro SA | psa001_admin | admin123 | admin |

**IMPORTANT:** Change default passwords immediately after first login!

### Logout

Click the "Logout" button in the navigation menu to end your session.

---

## Dashboard Overview

### Main Dashboard (index.php)

The dashboard displays:

1. **Statistics Cards:**
   - Total Scans
   - Active Scans
   - Completed Scans
   - Total Vulnerabilities

2. **Recent Scans Table:**
   - Scan Name
   - Target
   - Type (Quick/Full/Comprehensive)
   - Status
   - Started At
   - Actions (View Report)

3. **Quick Actions:**
   - Start New Scan
   - View All Scans
   - Manage Agents
   - View Tenants

### Navigation Menu

- **Dashboard:** Main overview page
- **Agents:** Manage deployed security agents
- **Tenants:** View and manage tenant organizations
- **Logout:** End current session

---

## Managing Tenants

### Viewing Tenants

1. **Access Tenants Page:**
   ```
   http://localhost/networkscan/tenants.php
   ```

2. **Tenant Information Displayed:**
   - Tenant Name
   - Tenant Code
   - Status (Active/Inactive)
   - API Key
   - Created Date
   - Agent Count
   - Scan Count

### Creating New Tenant

1. **Database Method:**
   ```sql
   INSERT INTO tenants (tenant_name, tenant_code, status)
   VALUES ('Company Name', 'COMP001', 'active');
   ```

2. **Generate API Key:**
   ```sql
   INSERT INTO agent_api_keys (tenant_id, api_key, status)
   VALUES (
     [tenant_id],
     SHA2(CONCAT([tenant_id], NOW(), RAND()), 256),
     'active'
   );
   ```

### Creating Tenant User

```bash
# Run this PHP script to create user for new tenant
C:\xampp\php\php.exe -r "
require_once 'C:/xampp/htdocs/networkscan/classes/Database.php';
\$db = Database::getInstance();
\$db->query(
  'INSERT INTO tenant_users (tenant_id, username, password_hash, email, role, status)
   VALUES (?, ?, ?, ?, ?, ?)',
  [
    3,  // tenant_id
    'newcompany_admin',
    password_hash('secure_password', PASSWORD_DEFAULT),
    'admin@newcompany.com',
    'admin',
    'active'
  ]
);
echo 'User created successfully';
"
```

---

## Deploying Security Agents

### Overview

Security agents are deployed on client machines to:
- Collect system information
- Monitor network configurations
- Report security status
- Scan for vulnerabilities
- Send data to central server

### Accessing Agent Management

1. **Navigate to Agents Page:**
   ```
   http://localhost/networkscan/agents.php
   ```

2. **View Agent Dashboard:**
   - Total Agents
   - Active Agents (checked in within 2 hours)
   - Offline Agents
   - Recent check-ins

### Downloading Agent Installer

#### Method 1: Via Web Interface

1. **Login to Dashboard**
2. **Navigate to Agents Page**
3. **Locate "Download Agent Installers" Section**
4. **Click "Download Windows Installer (.bat)"**
5. **Save the `NetworkAgent-Setup.bat` file**

#### Method 2: Direct URL

```
http://localhost/networkscan/create_agent_installer.php?api_key=YOUR_API_KEY&platform=windows&server_url=http://YOUR_SERVER/networkscan/agent_api.php
```

Replace:
- `YOUR_API_KEY`: Your tenant's API key
- `YOUR_SERVER`: Your server's IP address or domain

### Installing Agent on Windows

#### Prerequisites
- Windows 7 or higher
- PowerShell 3.0 or higher
- Administrator privileges

#### Installation Steps

1. **Download Installer:**
   - Get `NetworkAgent-Setup.bat` from agents page

2. **Run as Administrator:**
   ```
   Right-click NetworkAgent-Setup.bat
   Select "Run as Administrator"
   ```

3. **Installation Process:**
   ```
   [1/5] Checking PowerShell version...
         ✓ PowerShell version OK

   [2/5] Creating agent directory...
         ✓ Directory created: C:\ProgramData\NetworkAgent

   [3/5] Installing agent script...
         ✓ Agent script installed

   [4/5] Creating Windows Service...
         ✓ Service created successfully

   [5/5] Starting agent service...
         ✓ Service started successfully

   =========================================
   Installation Complete!
   =========================================
   ```

4. **Verify Installation:**
   - Agent should appear on agents.php page within a few minutes
   - Check status shows "ACTIVE"

### Agent Management Commands

#### Check Agent Status
```cmd
schtasks /query /TN NetworkAgent
```

#### View Agent Logs
```cmd
type C:\ProgramData\NetworkAgent\agent.log
```

#### Start Agent Manually
```cmd
schtasks /run /TN NetworkAgent
```

#### Stop Agent
```cmd
schtasks /end /TN NetworkAgent
```

#### Uninstall Agent
```cmd
powershell -ExecutionPolicy Bypass -File "C:\ProgramData\NetworkAgent\agent.ps1" -Uninstall
```

### What Data Does the Agent Collect?

The agent collects:

1. **System Information:**
   - Hostname
   - Operating System & Version
   - Architecture (32/64-bit)
   - IP Addresses
   - Domain membership
   - Total Memory

2. **Network Information:**
   - Open TCP ports (listening)
   - Associated processes
   - DNS servers
   - Default gateway
   - Routing table

3. **Process Information:**
   - Top 50 processes by CPU usage
   - Process names and PIDs
   - CPU usage percentage
   - Memory usage
   - Executable paths

4. **Security Information:**
   - Firewall status (all profiles)
   - Windows Defender status
   - Antivirus status
   - Last Windows update
   - Local user accounts
   - Account enabled/disabled status
   - Last logon times

### Agent Check-in Schedule

- **Frequency:** Every 1 hour
- **First Check-in:** Immediately after installation
- **Data Retention:** All historical data retained
- **Offline Threshold:** 2 hours (no check-in = marked offline)

---

## Running Network Scans

### Scan Types

1. **Quick Scan:**
   - Ports: Common ports (80, 443, 22, 21, 3389, etc.)
   - Duration: 2-5 minutes
   - Use Case: Quick security assessment

2. **Full Scan:**
   - Ports: 1-1024 (well-known ports)
   - Duration: 10-30 minutes
   - Use Case: Comprehensive port analysis

3. **Comprehensive Scan:**
   - Ports: 1-65535 (all ports)
   - Duration: 1-3 hours
   - Use Case: Complete security audit

### Starting a Scan

#### Via Dashboard

1. **Navigate to Dashboard:**
   ```
   http://localhost/networkscan/index.php
   ```

2. **Locate "Start New Scan" Section**

3. **Fill Scan Details:**
   - **Scan Name:** Descriptive name (e.g., "Office Network Audit")
   - **Target:** IP address or hostname (e.g., 192.168.1.1)
   - **Scan Type:** Select from dropdown (Quick/Full/Comprehensive)

4. **Click "Start Scan" Button**

5. **Monitor Progress:**
   - Scan appears in "Recent Scans" table
   - Status updates automatically
   - Progress percentage shown

#### Via API

```bash
curl -X POST "http://localhost/networkscan/start_scan_async.php" \
  -d "target=192.168.1.100" \
  -d "type=quick" \
  -d "name=API Scan Test"
```

### Monitoring Scan Progress

1. **Dashboard View:**
   - Real-time status updates
   - Progress percentage
   - Elapsed time

2. **Scan Statuses:**
   - `pending`: Queued for execution
   - `running`: Currently scanning
   - `completed`: Finished successfully
   - `failed`: Encountered error

3. **Check Scan Status (API):**
   ```bash
   curl "http://localhost/networkscan/get_scan_status.php?scan_id=1"
   ```

---

## Viewing Reports

### Accessing Scan Reports

1. **From Dashboard:**
   - Locate scan in "Recent Scans" table
   - Click "View Report" button

2. **Direct URL:**
   ```
   http://localhost/networkscan/view_report.php?id=SCAN_ID
   ```

### Report Sections

#### 1. Scan Information
- **Scan Name:** Descriptive name of the scan
- **Target:** IP address or hostname scanned
- **Type:** Scan type (Quick/Full/Comprehensive)
- **Status:** Current status
- **Started At:** Scan start timestamp
- **Completed At:** Scan end timestamp
- **Duration:** Total time taken

#### 2. Vulnerability Summary
Color-coded cards showing:
- **Total Vulnerabilities:** All findings
- **Critical:** Immediate action required (Red)
- **High:** Urgent attention needed (Orange)
- **Medium:** Should be addressed soon (Yellow)
- **Low:** Minor issues (Blue)
- **Info:** Informational findings (Gray)

#### 3. Vulnerability Details

Each vulnerability displays:
- **Title:** Brief description
- **Severity Badge:** Color-coded severity level
- **Description:** Detailed explanation of the issue
- **Recommendation:** How to fix the vulnerability
- **CVE Reference:** Link to NIST database (if applicable)
- **Port:** Affected port number
- **Service:** Service running on the port
- **Detection Time:** When vulnerability was found

#### 4. Export Options

- **Print:** Browser print functionality
- **PDF Export:** Use browser "Print to PDF"

### Sample Report Structure

```
SCAN REPORT: Office Server (192.168.1.100)
==================================================
Scan Type: Full
Started: 2025-11-09 14:30:00
Completed: 2025-11-09 14:45:23
Duration: 00:15:23

VULNERABILITY SUMMARY
==================================================
Total: 12
Critical: 2
High: 3
Medium: 4
Low: 2
Info: 1

VULNERABILITY DETAILS
==================================================

[CRITICAL] Outdated SSL/TLS Version
Description: Server supports TLS 1.0 which has known vulnerabilities...
Port: 443
Service: HTTPS
Recommendation: Update to TLS 1.2 or higher...
CVE: CVE-2011-3389

[HIGH] Anonymous FTP Access
Description: FTP server allows anonymous login...
Port: 21
Service: FTP
Recommendation: Disable anonymous access...
```

---

## Troubleshooting

### Common Issues

#### 1. Cannot Access Login Page

**Problem:** Browser shows "Page not found"

**Solutions:**
```bash
# Check Apache is running
- Open XAMPP Control Panel
- Ensure Apache shows "Running" status
- Click "Admin" button to test

# Verify URL is correct
http://localhost/networkscan/login.php

# Check file exists
dir C:\xampp\htdocs\networkscan\login.php
```

#### 2. Database Connection Error

**Problem:** "Connection failed: Access denied"

**Solutions:**
```php
// 1. Verify MySQL is running in XAMPP Control Panel

// 2. Check credentials in Database.php
private $host = "localhost";
private $db_name = "network_security";
private $username = "root";
private $password = ""; // Usually blank in XAMPP

// 3. Test MySQL connection
mysql -u root -p
USE network_security;
SHOW TABLES;
```

#### 3. Login Fails with Correct Credentials

**Problem:** "Invalid username or password"

**Solutions:**
```bash
# Reset admin password
C:\xampp\mysql\bin\mysql.exe -u root network_security -e "UPDATE tenant_users SET password_hash = '$2y$10$kZXqsxMxJ7.qL8BqH3hKGeYR4ZQJX0qKZJX4YZQJX0' WHERE username = 'admin';"
# New password: admin123

# Verify user exists
C:\xampp\mysql\bin\mysql.exe -u root network_security -e "SELECT username, status FROM tenant_users;"
```

#### 4. Scan Stuck in "Pending" Status

**Problem:** Scan never starts

**Solutions:**
```bash
# Check PHP error log
type C:\xampp\php\logs\php_error_log

# Verify nmap is installed
nmap --version

# Test scan manually
nmap -p 80,443 127.0.0.1

# Check scan_debug.log
type C:\xampp\htdocs\networkscan\scan_debug.log
```

#### 5. Agent Not Appearing After Installation

**Problem:** Agent installed but not showing in dashboard

**Solutions:**
```cmd
# 1. Verify agent is running
schtasks /query /TN NetworkAgent

# 2. Check agent logs
type C:\ProgramData\NetworkAgent\agent.log

# 3. Test manual check-in
schtasks /run /TN NetworkAgent

# 4. Wait 2-3 minutes and refresh agents.php page

# 5. Check API endpoint is accessible
curl http://YOUR_SERVER/networkscan/agent_api.php
```

#### 6. Vulnerabilities Table Error

**Problem:** "Unknown column 'scan_id'"

**Solution:**
```bash
C:\xampp\php\php.exe C:\xampp\htdocs\networkscan\fix_vulnerabilities_table.php
```

### Error Logs Locations

```
PHP Errors:
C:\xampp\php\logs\php_error_log

Apache Errors:
C:\xampp\apache\logs\error.log

MySQL Errors:
C:\xampp\mysql\data\mysql_error.log

Application Logs:
C:\xampp\htdocs\networkscan\scan_debug.log

Agent Logs:
C:\ProgramData\NetworkAgent\agent.log
```

### Getting Help

1. **Check Logs:** Always check error logs first
2. **Verify Requirements:** Ensure all prerequisites are met
3. **Test Components:** Test each component individually
4. **Documentation:** Review this manual thoroughly

---

## API Documentation

### Authentication

All API requests require tenant authentication via API key.

### Endpoints

#### 1. Start Scan

```http
POST /networkscan/start_scan_async.php
Content-Type: application/x-www-form-urlencoded

target=192.168.1.100&type=quick&name=API Scan
```

**Response:**
```json
{
  "success": true,
  "scan_id": 42,
  "message": "Scan started successfully"
}
```

#### 2. Get Scan Status

```http
GET /networkscan/get_scan_status.php?scan_id=42
```

**Response:**
```json
{
  "success": true,
  "scan": {
    "id": 42,
    "scan_name": "API Scan",
    "target": "192.168.1.100",
    "status": "running",
    "progress": 45,
    "started_at": "2025-11-09 14:30:00"
  }
}
```

#### 3. Agent Check-in

```http
POST /networkscan/agent_api.php
Content-Type: application/json

{
  "api_key": "YOUR_API_KEY",
  "action": "checkin",
  "agent_version": "1.0.0",
  "data": {
    "system": {...},
    "network": {...},
    "processes": [...],
    "security": {...}
  }
}
```

**Response:**
```json
{
  "success": true,
  "agent_id": "abc123...",
  "message": "Check-in successful"
}
```

#### 4. Get Statistics

```http
GET /networkscan/api.php?action=stats
```

**Response:**
```json
{
  "success": true,
  "stats": {
    "total_scans": 156,
    "active_scans": 3,
    "completed_scans": 142,
    "failed_scans": 11,
    "total_vulnerabilities": 487,
    "critical_vulnerabilities": 23,
    "active_agents": 45
  }
}
```

### API Error Codes

| Code | Message | Description |
|------|---------|-------------|
| 400 | Bad Request | Missing required parameters |
| 401 | Unauthorized | Invalid API key |
| 404 | Not Found | Resource doesn't exist |
| 500 | Internal Server Error | Server-side error |

---

## Security Best Practices

### 1. Change Default Passwords

```sql
UPDATE tenant_users
SET password_hash = PASSWORD('NEW_SECURE_PASSWORD')
WHERE username = 'admin';
```

### 2. Use HTTPS in Production

```apache
# Enable SSL in Apache
LoadModule ssl_module modules/mod_ssl.so
```

### 3. Restrict Database Access

```sql
CREATE USER 'scanner'@'localhost' IDENTIFIED BY 'strong_password';
GRANT SELECT, INSERT, UPDATE ON network_security.* TO 'scanner'@'localhost';
FLUSH PRIVILEGES;
```

### 4. Firewall Rules

```cmd
# Allow only necessary ports
netsh advfirewall firewall add rule name="Network Scanner" dir=in action=allow protocol=TCP localport=80,443
```

### 5. Regular Backups

```bash
# Backup database daily
C:\xampp\mysql\bin\mysqldump.exe -u root network_security > backup_%date%.sql
```

---

## Maintenance

### Regular Tasks

#### Daily
- Monitor active scans
- Check error logs
- Verify agent check-ins

#### Weekly
- Review vulnerability reports
- Update scan schedules
- Clean old logs

#### Monthly
- Database optimization
- Update application
- Review user accounts
- Backup database

### Database Maintenance

```sql
-- Optimize tables
OPTIMIZE TABLE scans;
OPTIMIZE TABLE vulnerabilities;
OPTIMIZE TABLE agents;
OPTIMIZE TABLE agent_checkins;

-- Clean old data (older than 90 days)
DELETE FROM scans WHERE completed_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
DELETE FROM agent_checkins WHERE checkin_time < DATE_SUB(NOW(), INTERVAL 90 DAY);
```

---

## System Architecture

```
┌─────────────────────────────────────────────────────────┐
│                    Web Dashboard                        │
│  (PHP + MySQL + Apache)                                │
│  - User Authentication                                  │
│  - Scan Management                                      │
│  - Report Generation                                    │
│  - Agent Management                                     │
└─────────────┬───────────────────────────────────────────┘
              │
              │ HTTP/HTTPS
              │
    ┌─────────┴─────────┬─────────────┬─────────────┐
    │                   │             │             │
┌───▼────┐       ┌─────▼──┐    ┌────▼───┐    ┌───▼────┐
│ Agent  │       │ Agent  │    │ Agent  │    │ Agent  │
│ (Win)  │       │ (Win)  │    │ (Win)  │    │ (Lin)  │
└────────┘       └────────┘    └────────┘    └────────┘
```

---

## Glossary

**Agent:** Software deployed on client machines to collect security data

**API Key:** Unique identifier for tenant authentication

**BEC:** Bid Evaluation Committee (not related to this scanner)

**CVE:** Common Vulnerabilities and Exposures database

**Nmap:** Network Mapper - port scanning tool

**Scan:** Process of analyzing network target for vulnerabilities

**Tenant:** Organization or department using the system

**Vulnerability:** Security weakness found during scanning

---

## Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.0.0 | 2025-11-09 | Initial release with multi-tenancy |
| 1.1.0 | 2025-11-09 | Added Windows agent support |
| 1.2.0 | 2025-11-09 | Enhanced reporting and authentication |

---

## Support & Contact

For technical support, please contact your system administrator.

---

**Document Version:** 1.0
**Last Updated:** November 9, 2025
**Author:** Network Security Team
