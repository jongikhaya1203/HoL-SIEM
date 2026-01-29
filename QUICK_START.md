# Network Security Scanner - Quick Start Guide

## 5-Minute Setup

### 1. Install XAMPP
```
1. Download XAMPP from https://www.apachefriends.org/
2. Install to C:\xampp
3. Start Apache and MySQL from XAMPP Control Panel
```

### 2. Deploy Application
```
1. Extract all files to: C:\xampp\htdocs\networkscan\
2. Open browser: http://localhost/phpmyadmin
3. Create database: network_security
4. Import database_schema.sql
```

### 3. Run Setup
```cmd
C:\xampp\php\php.exe C:\xampp\htdocs\networkscan\setup_multitenancy.php
C:\xampp\php\php.exe C:\xampp\htdocs\networkscan\setup_authentication.php
C:\xampp\php\php.exe C:\xampp\htdocs\networkscan\create_agent_tables.php
```

### 4. Login
```
URL: http://localhost/networkscan/login.php
Username: admin
Password: admin123
```

## Quick Tasks

### Start Your First Scan
```
1. Go to Dashboard: http://localhost/networkscan/index.php
2. Fill in:
   - Scan Name: "Test Scan"
   - Target: 127.0.0.1
   - Type: Quick
3. Click "Start Scan"
4. Click "View Report" when completed
```

### Deploy an Agent
```
1. Go to: http://localhost/networkscan/agents.php
2. Click "Download Windows Installer (.bat)"
3. Right-click downloaded file → "Run as Administrator"
4. Wait 5 minutes
5. Agent appears in agents.php
```

### View Results
```
Scans: http://localhost/networkscan/index.php
Agents: http://localhost/networkscan/agents.php
Reports: Click "View Report" on any completed scan
```

## Default Accounts

| Username | Password | Tenant |
|----------|----------|--------|
| admin | admin123 | Default Organization |
| bits0001_admin | admin123 | Bitstech |
| psa001_admin | admin123 | Petro SA |

**⚠️ Change these passwords immediately!**

## Common Commands

### Check Agent Status
```cmd
schtasks /query /TN NetworkAgent
```

### View Agent Logs
```cmd
type C:\ProgramData\NetworkAgent\agent.log
```

### Uninstall Agent
```cmd
powershell -ExecutionPolicy Bypass -File "C:\ProgramData\NetworkAgent\agent.ps1" -Uninstall
```

## Troubleshooting

### Can't Login?
```bash
# Reset admin password
C:\xampp\mysql\bin\mysql.exe -u root network_security -e "UPDATE tenant_users SET password_hash='$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE username='admin';"
# Password is now: admin123
```

### Scan Not Starting?
```bash
# Check if nmap is installed
nmap --version

# View debug log
type C:\xampp\htdocs\networkscan\scan_debug.log
```

### Agent Not Showing?
```cmd
# Run manual check-in
schtasks /run /TN NetworkAgent

# Check logs
type C:\ProgramData\NetworkAgent\agent.log
```

## Next Steps

1. Read full manual: `INSTALLATION_MANUAL.md`
2. Change default passwords
3. Create additional tenants
4. Deploy agents to target machines
5. Schedule regular scans

## Support

For detailed documentation, see:
- **Full Manual:** INSTALLATION_MANUAL.md
- **Online Docs:** http://localhost/networkscan/docs.php (if available)

**Last Updated:** November 9, 2025
