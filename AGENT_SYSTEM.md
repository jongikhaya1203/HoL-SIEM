# Network Security Scanner - Agent System

## Overview

The agent system enables automated, continuous monitoring of hosts in your network environment. Deploy agents on target hosts to automatically collect and report system information, network configuration, security posture, and running processes.

## Features

- Automatic agent registration and tracking
- Cross-platform support (Windows/Linux)
- Centralized agent management dashboard
- Secure API-key based authentication
- Configurable check-in intervals
- Data retention management (30-day history)
- Real-time status monitoring

## Installation

### 1. Database Setup

Run the database creation script once:

```bash
php C:\xampp\htdocs\networkscan\create_agent_tables.php
```

This will create:
- Agent tracking tables
- Network/process/security data tables
- API key management
- Check-in logging

Save the generated API key - you'll need it for agent configuration.

**Current API Key:** `9f62dc83775058a79d5a31d61dc488a739b87d00225b89834cae52d50a96a5c4`

### 2. Deploy Agent

Copy the agent file to target hosts:

```bash
# Copy agent to target host
scp C:\xampp\htdocs\networkscan\agent\agent.php user@target-host:/path/to/agent.php
```

### 3. Configure Agent

On the target host, run the agent with server URL and API key:

```bash
# One-time check-in
php agent.php --server=http://scanner-server/networkscan --key=YOUR_API_KEY

# Daemon mode with 1-hour interval
php agent.php --server=http://scanner-server/networkscan --key=YOUR_API_KEY --daemon --interval=3600
```

## Usage

### View Agent Dashboard

Access the agent management dashboard:

```
http://localhost/networkscan/agents.php
```

The dashboard displays:
- Total registered agents
- Active agents (checked in within 2 hours)
- Recent check-ins (last 24 hours)
- Error count
- Agent details (OS, IP addresses, status, last check-in)

### Agent Data Collected

Each agent collects and reports:

**System Information:**
- Hostname
- Operating system and version
- Architecture (x86, x64, etc.)
- IP addresses
- Agent version

**Network Configuration:**
- Network interfaces
- Open ports
- Listening services
- Routing table
- DNS servers

**Running Processes:**
- Top 50 running processes
- Process names and PIDs
- User information (Linux)

**Security Status:**
- Firewall status
- Antivirus status
- Windows Defender status (Windows)
- Last system update
- User accounts (up to 20)

## API Endpoints

### Agent Check-in

```
POST /networkscan/agent_api.php
Content-Type: application/json

{
  "api_key": "YOUR_API_KEY",
  "action": "checkin",
  "data": {
    "agent_version": "1.0.0",
    "system": { ... },
    "network": { ... },
    "processes": [ ... ],
    "security": { ... }
  }
}
```

### Get Configuration

```
POST /networkscan/agent_api.php
Content-Type: application/json

{
  "api_key": "YOUR_API_KEY",
  "action": "get_config"
}
```

### Report Error

```
POST /networkscan/agent_api.php
Content-Type: application/json

{
  "api_key": "YOUR_API_KEY",
  "action": "report_error",
  "agent_id": "AGENT_ID",
  "error": "Error message"
}
```

## Database Schema

### Tables Created

- `agents` - Agent registration and tracking
- `agent_ips` - IP addresses assigned to each agent
- `agent_network` - Network configuration snapshots
- `agent_processes` - Process snapshots
- `agent_security` - Security status snapshots
- `agent_api_keys` - API key management
- `agent_checkins` - Check-in audit log

### Data Retention

Historical data is automatically cleaned up after 30 days to manage database size:
- Network snapshots
- Process snapshots
- Security snapshots
- Check-in logs

Agent registration records are retained indefinitely.

## Security Considerations

1. **API Key Protection**
   - Store API keys securely
   - Rotate keys periodically
   - Use environment variables or secure configuration files

2. **Network Security**
   - Use HTTPS in production
   - Implement firewall rules
   - Restrict API access to known networks

3. **Agent Permissions**
   - Run agents with minimal required permissions
   - Avoid running as root/Administrator unless necessary
   - Review collected data for sensitive information

## Testing

Test the agent system:

```bash
php C:\xampp\htdocs\networkscan\test_agent_checkin.php
```

This will:
- Simulate an agent check-in
- Verify API authentication
- Test data collection and storage
- Validate database operations

## Troubleshooting

### Agent Not Appearing in Dashboard

1. Check API key is correct
2. Verify network connectivity to server
3. Review agent.log for errors
4. Check agent_api.log on server

### Check-in Failures

1. Verify API endpoint is accessible
2. Check firewall rules
3. Review server logs: `agent_api.log`
4. Test with: `php test_agent_checkin.php`

### Agent Shows as Inactive

Agents are marked inactive if no check-in within 2 hours:
- Check agent daemon is running
- Verify check-in interval setting
- Review agent logs for errors

## Advanced Configuration

### Custom Check-in Intervals

Modify the default 1-hour interval:

```bash
# Check in every 30 minutes
php agent.php --server=SERVER_URL --key=API_KEY --daemon --interval=1800

# Check in every 4 hours
php agent.php --server=SERVER_URL --key=API_KEY --daemon --interval=14400
```

### Running as System Service

**Windows (Task Scheduler):**
```powershell
schtasks /create /tn "Security Agent" /tr "php C:\path\to\agent.php --server=URL --key=KEY --daemon" /sc onstart /ru SYSTEM
```

**Linux (systemd):**
```ini
[Unit]
Description=Network Security Agent
After=network.target

[Service]
Type=simple
ExecStart=/usr/bin/php /path/to/agent.php --server=URL --key=KEY --daemon
Restart=always

[Install]
WantedBy=multi-user.target
```

## Files Created

- `C:\XAMPP\HTDOCS\networkscan\agent\agent.php` - Deployable agent
- `C:\XAMPP\HTDOCS\networkscan\agent_api.php` - Server-side API endpoint
- `C:\XAMPP\HTDOCS\networkscan\agents.php` - Management dashboard
- `C:\XAMPP\HTDOCS\networkscan\get_agent_details.php` - Agent details API
- `C:\XAMPP\HTDOCS\networkscan\create_agent_tables.php` - Database setup
- `C:\XAMPP\HTDOCS\networkscan\test_agent_checkin.php` - Testing tool

## Next Steps

1. Deploy agents to target hosts
2. Configure daemon mode for continuous monitoring
3. Set up system services for automatic startup
4. Review collected data in dashboard
5. Configure alerts for agent failures
6. Implement custom data retention policies

## Support

For issues or questions:
- Check agent.log on client hosts
- Check agent_api.log on server
- Review database for check-in records
- Test with test_agent_checkin.php
