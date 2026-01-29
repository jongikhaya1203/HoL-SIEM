# SCADA Agent System - Complete Setup Guide

## Overview

This document provides a complete overview of the SCADA Agent System, including sample data, API keys, and agent deployment.

---

## What Has Been Created

### 1. Sample Data System
**File:** `create_sample_data.php`

Creates comprehensive sample data for all SCADA modules:
- ✅ 4 Industrial sites (Oil & Gas, Rail, Mining, Manufacturing)
- ✅ 4 PLCs + 2 RTUs
- ✅ 9 Assets (Tanks, Valves, Pumps, Signals, Fans, etc.)
- ✅ 24 SCADA Tags with real-time values
- ✅ Tank levels and valve positions
- ✅ 3 Calibrated instruments
- ✅ Industry-specific equipment for all modules
- ✅ 24 hours of historical data
- ✅ Active alarms and control action logs

**To Run:**
```
http://localhost/networkscanscada/create_sample_data.php
```

### 2. API Key Management System

**Database Tables:**
- `agent_api_keys` - Stores API keys for agent authentication
- `agent_heartbeats` - Tracks agent health and status
- `agent_data_submissions` - Logs all data submissions

**Files:**
- `create_agent_api_tables.php` - Creates database tables
- `generate_agent_api_key.php` - Web interface to generate API keys

**Features:**
- Secure 64-character API keys
- Site-specific access control
- IP address restrictions
- Expiration dates
- Permission management
- Usage tracking

**To Use:**
1. Install tables: `http://localhost/networkscanscada/create_agent_api_tables.php`
2. Generate key: `http://localhost/networkscanscada/generate_agent_api_key.php`

### 3. Agent API Endpoint

**File:** `scada_agent_api.php`

RESTful API for agent communication:

**Endpoints:**
- `POST ?action=submit_data` - Submit tag readings
- `POST ?action=heartbeat` - Agent health check
- `GET ?action=get_config` - Get agent configuration
- `GET ?action=get_tags` - Get tags to monitor

**Features:**
- API key authentication
- IP address validation
- Automatic alarm detection
- Historical data logging
- Error handling and logging
- Batch data processing

### 4. SCADA Agent Software

**Directory:** `agent/`

**Files:**
- `scada_agent.py` - Main agent application (Python)
- `agent_config.ini` - Configuration template
- `requirements.txt` - Python dependencies
- `README.md` - Quick reference
- `AGENT_DEPLOYMENT_GUIDE.md` - Complete deployment guide

**Agent Features:**
- ✅ Modbus TCP/RTU protocol support
- ✅ Automatic tag discovery from server
- ✅ Batch data submission for efficiency
- ✅ Regular heartbeat monitoring
- ✅ Comprehensive error handling
- ✅ Detailed logging
- ✅ Low resource usage
- ✅ Production-ready

---

## Complete Setup Workflow

### Phase 1: Database Setup

```bash
# 1. Create main application tables
http://localhost/networkscanscada/create_main_app_tables.php

# 2. Install SCADA tables
http://localhost/networkscanscada/install_scada_simple.php

# 3. Add SCADA module to homepage
http://localhost/networkscanscada/add_scada_module.php

# 4. Create sample data
http://localhost/networkscanscada/create_sample_data.php

# 5. Create agent API tables
http://localhost/networkscanscada/create_agent_api_tables.php
```

### Phase 2: API Key Generation

```bash
# 6. Generate API key for your agent
http://localhost/networkscanscada/generate_agent_api_key.php

# Fill in:
- Key Name: "Houston Refinery PLC-01"
- Site: Oil & Gas Refinery - Houston
- Allowed IPs: (optional)
- Expiry: 365 days

# SAVE THE API KEY - IT'S SHOWN ONLY ONCE!
```

### Phase 3: Agent Deployment

```bash
# 7. Copy agent files to PLC computer
cp -r agent/ /path/to/deployment/

# 8. Install Python dependencies
cd /path/to/deployment/agent
pip install -r requirements.txt

# 9. Configure agent
nano agent_config.ini
# Edit:
# - api_key = YOUR_64_CHAR_KEY
# - ip_address = 192.168.1.100
# - plc_id = 1

# 10. Test agent
python3 scada_agent.py

# 11. Setup as service (production)
# See AGENT_DEPLOYMENT_GUIDE.md for details
```

### Phase 4: Verification

```bash
# 12. Verify data in dashboard
http://localhost/networkscanscada/scada_hmi.php

# 13. Check agent heartbeats
SELECT * FROM agent_heartbeats ORDER BY heartbeat_time DESC;

# 14. Check data submissions
SELECT * FROM agent_data_submissions ORDER BY submitted_at DESC;

# 15. Check tag history
SELECT * FROM scada_tag_history ORDER BY timestamp DESC LIMIT 100;
```

---

## System Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                        SCADA Server                          │
│  ┌───────────────────────────────────────────────────────┐  │
│  │  Database (MySQL)                                     │  │
│  │  - scada_sites, scada_plcs, scada_rtus               │  │
│  │  - scada_tags, scada_tag_history                     │  │
│  │  - agent_api_keys, agent_heartbeats                  │  │
│  └───────────────────────────────────────────────────────┘  │
│  ┌───────────────────────────────────────────────────────┐  │
│  │  Web Application (PHP)                               │  │
│  │  - scada_hmi.php (HMI Dashboard)                     │  │
│  │  - scada_agent_api.php (Agent API)                   │  │
│  │  - generate_agent_api_key.php (Key Management)       │  │
│  └───────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
                            ▲
                            │ HTTPS/API
                            │ (API Key Auth)
                            │
┌─────────────────────────────────────────────────────────────┐
│                      SCADA Agent                             │
│  ┌───────────────────────────────────────────────────────┐  │
│  │  scada_agent.py (Python)                              │  │
│  │  - Modbus Client                                      │  │
│  │  - Tag Reading Loop                                   │  │
│  │  - API Client                                         │  │
│  │  - Heartbeat Service                                  │  │
│  └───────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
                            ▲
                            │ Modbus TCP/RTU
                            │
┌─────────────────────────────────────────────────────────────┐
│                      PLC/RTU                                 │
│  - Siemens S7-1500                                          │
│  - Allen-Bradley ControlLogix                               │
│  - Schneider M580                                           │
│  - ABB RTU560                                               │
└─────────────────────────────────────────────────────────────┘
```

---

## Data Flow

1. **Agent → PLC**
   - Agent reads Modbus registers
   - Retrieves tag values (temperature, pressure, flow, etc.)

2. **Agent → Server**
   - Submits batch of readings via API
   - Includes timestamp, value, quality
   - Sends heartbeat every 60 seconds

3. **Server Processing**
   - Validates API key
   - Stores data in `scada_tag_history`
   - Checks alarm conditions
   - Creates alarms if limits exceeded

4. **User → Dashboard**
   - Views real-time data in HMI
   - Sees trends and history
   - Acknowledges alarms
   - Controls valves/equipment

---

## Security Features

### API Authentication
- 64-character cryptographically secure API keys
- API key rotation with expiration dates
- IP address whitelisting
- Per-site access control

### Data Protection
- Prepared statements (SQL injection prevention)
- Input validation
- Error logging without exposing sensitive data

### Network Security
- HTTPS support for production
- VPN recommended for remote access
- Network segmentation for SCADA

---

## Example Data Created

### Sites
1. **Oil & Gas Refinery** - Houston, Texas
2. **Metro Rail Operations** - Sydney, Australia
3. **Deep Mine Complex** - Johannesburg, South Africa
4. **Smart Factory** - Shanghai, China

### Equipment Examples

**Oil & Gas:**
- Tank-101 (Crude Oil Storage) - 12.5m level, 4166 m³
- Valve-201 (Main Pipeline Inlet) - 75% open
- Pump-301 (Transfer Pump) - 1800 RPM, 245 m³/h

**Rail:**
- Signal-A12 (Platform Entry) - Proceed aspect
- Point-SW05 (Junction) - Normal position, locked
- Track Circuit TC-A12 - Clear, 450m

**Mining:**
- Fan-VF01 (Ventilation) - 980 RPM, 145 m³/s airflow
- Hoist-H1 (Main Shaft) - 450m depth, 8.5 m/s, 3500kg load
- Gas Sensor GS-Level5-01 - 0.35% methane (normal)

**Manufacturing:**
- Production Line-A - 35.5 units/min, 79.46% OEE
- Robot-R01 - Running, 12,547 cycles
- Conveyor C01 - 35.5 m/min, 8,765 parts

---

## API Examples

### Submit Tag Data
```bash
curl -X POST "http://localhost/networkscanscada/scada_agent_api.php?action=submit_data" \
  -H "X-API-Key: YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "plc_id": 1,
    "readings": [
      {
        "tag_name": "Tank-101.Level",
        "value": 12.5,
        "quality": "good",
        "timestamp": "2025-01-15 10:00:00"
      }
    ]
  }'
```

### Get Tags to Monitor
```bash
curl -X GET "http://localhost/networkscanscada/scada_agent_api.php?action=get_tags&plc_id=1" \
  -H "X-API-Key: YOUR_API_KEY"
```

### Send Heartbeat
```bash
curl -X POST "http://localhost/networkscanscada/scada_agent_api.php?action=heartbeat" \
  -H "X-API-Key: YOUR_API_KEY" \
  -d '{
    "agent_version": "1.0.0",
    "hostname": "PLC-Computer-01",
    "cpu_usage": 15.5,
    "memory_usage": 45.2
  }'
```

---

## Monitoring and Maintenance

### Check Agent Status
```sql
-- Latest heartbeats
SELECT ak.key_name, ah.hostname, ah.heartbeat_time
FROM agent_heartbeats ah
JOIN agent_api_keys ak ON ah.api_key_id = ak.id
ORDER BY ah.heartbeat_time DESC
LIMIT 10;

-- Data submission stats
SELECT
    DATE(submitted_at) as date,
    COUNT(*) as submissions,
    SUM(records_count) as total_records,
    SUM(CASE WHEN processing_status = 'processed' THEN 1 ELSE 0 END) as successful
FROM agent_data_submissions
GROUP BY DATE(submitted_at)
ORDER BY date DESC
LIMIT 7;
```

### View Recent Alarms
```sql
SELECT t.tag_name, a.alarm_type, a.alarm_message, a.value, a.alarm_time
FROM scada_alarm_history a
JOIN scada_tags t ON a.tag_id = t.id
WHERE a.is_acknowledged = 0
ORDER BY a.alarm_time DESC;
```

---

## Troubleshooting

### Agent Not Connecting
1. Check API key is correct
2. Verify network connectivity to server
3. Check firewall rules
4. Review agent logs: `scada_agent.log`

### No Data Appearing
1. Verify PLC is accessible
2. Check Modbus addresses in tags table
3. Review data submissions table for errors
4. Check agent is reading tags correctly

### Alarms Not Triggering
1. Verify alarm limits are set on tags
2. Check tag quality is 'good'
3. Review alarm history table

---

## Next Steps

1. ✅ **Complete initial setup** (all phases above)
2. ✅ **Verify sample data** appears in HMI
3. ✅ **Generate API key** for your first agent
4. ✅ **Deploy agent** to test PLC
5. ✅ **Monitor data flow** for 24 hours
6. 🔲 **Add more PLCs/RTUs** as needed
7. 🔲 **Configure alarm notifications** (email/SMS)
8. 🔲 **Setup data retention policies**
9. 🔲 **Implement backup procedures**
10. 🔲 **Train operators** on HMI usage

---

## Files Summary

### Server Files (PHP)
- `create_sample_data.php` - Populate sample data
- `create_agent_api_tables.php` - Create API tables
- `generate_agent_api_key.php` - Generate API keys
- `scada_agent_api.php` - Agent API endpoint
- `scada_hmi.php` - HMI Dashboard

### Agent Files (Python)
- `agent/scada_agent.py` - Agent application
- `agent/agent_config.ini` - Configuration
- `agent/requirements.txt` - Dependencies
- `agent/README.md` - Quick start
- `agent/AGENT_DEPLOYMENT_GUIDE.md` - Full guide

### Documentation
- `AGENT_SYSTEM_README.md` (this file)
- `SCADA_README.md` - System overview
- `SCADA_SETUP_GUIDE.md` - Installation guide

---

**System Version:** 1.0.0
**Last Updated:** 2025-01-15
**Status:** Production Ready ✅
