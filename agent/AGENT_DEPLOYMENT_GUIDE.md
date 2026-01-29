# SCADA Agent Deployment Guide

## Overview
The SCADA Agent is a lightweight Python application that connects to PLCs/RTUs, reads SCADA tag values, and submits data to the central SCADA server.

## Features
- **Modbus TCP/RTU Support** - Connects to PLCs via Modbus protocol
- **Automatic Tag Discovery** - Fetches tag configuration from server
- **Batch Data Submission** - Efficient data transmission
- **Health Monitoring** - Regular heartbeats to server
- **Error Handling** - Robust error recovery and logging
- **Low Resource Usage** - Minimal CPU and memory footprint

---

## Prerequisites

### Server Side (SCADA Central)
1. MySQL database with SCADA schema installed
2. Web server (Apache/Nginx) with PHP 7.4+
3. SCADA application deployed at: `http://your-server/networkscanscada/`

### Agent Side (PLC/RTU Computer)
1. **Python 3.7 or higher**
   - Windows: Download from https://python.org
   - Linux: `sudo apt install python3 python3-pip`
   - Check version: `python3 --version`

2. **Network connectivity** to PLC and SCADA server

3. **PLC supports Modbus TCP/RTU**

---

## Installation Steps

### Step 1: Prepare Database and Generate API Key

1. **Install Database Tables**
   ```
   Visit: http://your-server/networkscanscada/create_agent_api_tables.php
   ```

2. **Generate API Key**
   ```
   Visit: http://your-server/networkscanscada/generate_agent_api_key.php
   Fill in:
   - Key Name: e.g., "Houston Refinery PLC-01"
   - Site: Select your site
   - Allowed IPs: (optional) Restrict to agent's IP
   - Expiry: (optional) Set expiration days
   ```

3. **Save the API Key**
   - Copy the 64-character API key displayed
   - You'll need this for agent configuration
   - **THIS IS SHOWN ONLY ONCE - SAVE IT!**

### Step 2: Configure Tags in Database

Make sure your SCADA tags are configured in the database:
```sql
SELECT * FROM scada_tags WHERE plc_id = YOUR_PLC_ID;
```

The agent will automatically fetch these tags from the server.

### Step 3: Deploy Agent Software

1. **Copy agent files to PLC computer**
   ```bash
   # Copy these files:
   - scada_agent.py
   - agent_config.ini
   - requirements.txt
   ```

2. **Install Python dependencies**
   ```bash
   # Navigate to agent directory
   cd /path/to/agent

   # Install requirements
   pip install -r requirements.txt
   ```

### Step 4: Configure the Agent

Edit `agent_config.ini`:

```ini
[api]
url = http://your-server/networkscanscada/scada_agent_api.php
api_key = YOUR_64_CHAR_API_KEY_HERE

[plc]
ip_address = 192.168.1.100  # Your PLC IP
port = 502                   # Modbus TCP port
plc_id = 1                   # PLC ID from database

[agent]
poll_interval = 5            # Read tags every 5 seconds
heartbeat_interval = 60      # Send heartbeat every 60 seconds
batch_size = 100             # Send 100 readings at once
```

### Step 5: Test the Agent

1. **Test connection to server**
   ```bash
   curl -X GET "http://your-server/networkscanscada/scada_agent_api.php?action=get_config" \
        -H "X-API-Key: YOUR_API_KEY"
   ```

   Should return:
   ```json
   {
     "success": true,
     "message": "Configuration retrieved"
   }
   ```

2. **Test PLC connectivity**
   ```bash
   # Try pinging PLC
   ping 192.168.1.100

   # Test Modbus connection (if you have modbus client tools)
   modpoll -m tcp -a 1 -r 40001 -c 1 192.168.1.100
   ```

3. **Run agent in test mode**
   ```bash
   python3 scada_agent.py
   ```

   You should see:
   ```
   SCADA Agent v1.0.0
   ==================================================
   2025-01-15 10:00:00 - SCADAAgent - INFO - Loading configuration
   2025-01-15 10:00:01 - SCADAAgent - INFO - Heartbeat sent successfully
   2025-01-15 10:00:02 - SCADAAgent - INFO - Fetched 24 tags from server
   2025-01-15 10:00:03 - SCADAAgent - INFO - Submitted 24 readings successfully
   ```

### Step 6: Run as Service (Production)

#### Windows Service

1. **Install NSSM (Non-Sucking Service Manager)**
   - Download from: https://nssm.cc/download
   - Extract to `C:\nssm`

2. **Create Windows Service**
   ```cmd
   cd C:\nssm\win64
   nssm install SCADAAgent

   # Configure:
   Path: C:\Python39\python.exe
   Startup directory: C:\path\to\agent
   Arguments: C:\path\to\agent\scada_agent.py

   # Start service
   nssm start SCADAAgent
   ```

#### Linux Systemd Service

1. **Create service file**
   ```bash
   sudo nano /etc/systemd/system/scada-agent.service
   ```

2. **Add configuration**
   ```ini
   [Unit]
   Description=SCADA Data Collection Agent
   After=network.target

   [Service]
   Type=simple
   User=scada
   WorkingDirectory=/opt/scada-agent
   ExecStart=/usr/bin/python3 /opt/scada-agent/scada_agent.py
   Restart=always
   RestartSec=10

   [Install]
   WantedBy=multi-user.target
   ```

3. **Enable and start**
   ```bash
   sudo systemctl daemon-reload
   sudo systemctl enable scada-agent
   sudo systemctl start scada-agent
   sudo systemctl status scada-agent
   ```

---

## Monitoring and Troubleshooting

### Check Agent Status

1. **View agent logs**
   ```bash
   tail -f scada_agent.log
   ```

2. **Check heartbeats in database**
   ```sql
   SELECT * FROM agent_heartbeats
   ORDER BY heartbeat_time DESC
   LIMIT 10;
   ```

3. **Check data submissions**
   ```sql
   SELECT * FROM agent_data_submissions
   ORDER BY submitted_at DESC
   LIMIT 10;
   ```

### Common Issues

#### Agent can't connect to server
```
ERROR: Invalid or inactive API key
```
**Solution:** Check API key in config file, verify it's active in database

#### Can't connect to PLC
```
ERROR: Failed to connect to PLC 192.168.1.100:502
```
**Solutions:**
- Verify PLC IP address
- Check network connectivity: `ping 192.168.1.100`
- Verify Modbus is enabled on PLC
- Check firewall rules
- Verify port 502 is open

#### Tags not found
```
ERROR: Tag not found: Tank-101.Level
```
**Solution:** Verify tags are configured in database for this PLC

#### Bad quality readings
```
quality: 'bad'
```
**Solutions:**
- Check Modbus address in tag configuration
- Verify data type matches PLC register type
- Check PLC is responding to Modbus requests

---

## API Endpoints

The agent uses these API endpoints:

### GET /scada_agent_api.php?action=get_config
Returns agent configuration

### GET /scada_agent_api.php?action=get_tags&plc_id=1
Returns list of tags to monitor

### POST /scada_agent_api.php?action=heartbeat
Submit agent heartbeat
```json
{
  "agent_version": "1.0.0",
  "hostname": "PLC-Computer-01",
  "system_info": {...}
}
```

### POST /scada_agent_api.php?action=submit_data
Submit tag readings
```json
{
  "type": "tag_data",
  "plc_id": 1,
  "readings": [
    {
      "tag_name": "Tank-101.Level",
      "value": 12.5,
      "quality": "good",
      "timestamp": "2025-01-15 10:00:00"
    }
  ]
}
```

---

## Security Best Practices

1. **Use HTTPS** in production
   - Configure SSL certificate
   - Update agent config: `https://your-server/...`

2. **Restrict API key by IP**
   - Set allowed IPs when generating API key
   - Only agent's IP can use the key

3. **Set API key expiration**
   - Rotate keys regularly (e.g., every 365 days)
   - Generate new key before old one expires

4. **Network segmentation**
   - Keep SCADA network separate from corporate network
   - Use VPN for remote access

5. **Monitor agent activity**
   - Check heartbeats regularly
   - Set up alerts for missing heartbeats
   - Review submission logs for anomalies

---

## Performance Tuning

### High-Frequency Data Collection
For fast-changing tags:
```ini
[agent]
poll_interval = 1  # Read every 1 second
batch_size = 50    # Smaller batches, more frequent
```

### Low-Bandwidth Networks
For GSM/cellular connections:
```ini
[agent]
poll_interval = 30   # Read every 30 seconds
batch_size = 200     # Larger batches, less frequent
heartbeat_interval = 300  # 5-minute heartbeats
```

### Multiple PLCs
Run multiple agent instances with different config files:
```bash
python3 scada_agent.py agent_config_plc1.ini &
python3 scada_agent.py agent_config_plc2.ini &
```

---

## Support and Maintenance

### Updating the Agent

1. Stop the agent service
2. Backup current files
3. Replace `scada_agent.py` with new version
4. Update dependencies: `pip install -r requirements.txt --upgrade`
5. Restart the agent service

### Backup Configuration

Always backup:
- `agent_config.ini` - Your configuration
- `scada_agent.log` - Recent logs (for troubleshooting)
- API key (stored securely)

---

## Quick Start Checklist

- [ ] Install agent database tables
- [ ] Generate API key
- [ ] Configure SCADA tags in database
- [ ] Copy agent files to PLC computer
- [ ] Install Python and dependencies
- [ ] Edit agent_config.ini with API key and PLC IP
- [ ] Test agent manually
- [ ] Verify data appears in database
- [ ] Set up as service for automatic startup
- [ ] Configure monitoring/alerts

---

## Example Deployment Scenarios

### Scenario 1: Oil & Gas Pipeline Monitoring
- **PLC:** Siemens S7-1500 at remote pump station
- **Connection:** GSM modem with cellular
- **Configuration:**
  ```ini
  poll_interval = 10
  batch_size = 500
  heartbeat_interval = 300
  ```

### Scenario 2: Manufacturing Production Line
- **PLC:** Allen-Bradley ControlLogix
- **Connection:** Ethernet LAN
- **Configuration:**
  ```ini
  poll_interval = 2
  batch_size = 50
  heartbeat_interval = 60
  ```

### Scenario 3: Rail Signaling System
- **RTU:** ABB RTU560
- **Connection:** Dedicated fiber network
- **Configuration:**
  ```ini
  poll_interval = 1
  batch_size = 100
  heartbeat_interval = 30
  ```

---

For questions or issues, check the logs first:
- Agent log: `scada_agent.log`
- Server API log: Check `agent_data_submissions` table

**Agent Version:** 1.0.0
**Last Updated:** 2025-01-15
