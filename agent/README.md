# SCADA Agent

Lightweight Python agent for collecting data from PLCs/RTUs and submitting to SCADA central server.

## Quick Start

1. **Install Dependencies**
   ```bash
   pip install -r requirements.txt
   ```

2. **Configure Agent**
   Edit `agent_config.ini`:
   - Set your API key
   - Set PLC IP address
   - Set PLC ID from database

3. **Run Agent**
   ```bash
   python3 scada_agent.py
   ```

## Files

- `scada_agent.py` - Main agent application
- `agent_config.ini` - Configuration file
- `requirements.txt` - Python dependencies
- `AGENT_DEPLOYMENT_GUIDE.md` - Complete deployment guide

## Configuration

```ini
[api]
url = http://your-server/networkscanscada/scada_agent_api.php
api_key = YOUR_API_KEY_HERE

[plc]
ip_address = 192.168.1.100
port = 502
plc_id = 1

[agent]
poll_interval = 5
heartbeat_interval = 60
batch_size = 100
```

## Features

✅ Modbus TCP/RTU support
✅ Automatic tag discovery from server
✅ Batch data submission
✅ Health monitoring with heartbeats
✅ Comprehensive logging
✅ Error recovery
✅ Low resource usage

## Requirements

- Python 3.7+
- pymodbus >= 3.0.0
- requests >= 2.28.0

## Documentation

See **AGENT_DEPLOYMENT_GUIDE.md** for complete installation and deployment instructions.

## License

Part of SCADA Network Monitoring System
