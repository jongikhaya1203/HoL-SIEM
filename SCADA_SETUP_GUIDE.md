# SCADA Network Monitoring System - Complete Setup Guide

## Overview

This comprehensive SCADA (Supervisory Control and Data Acquisition) system provides industrial-grade monitoring and control capabilities for multiple industries including:

- **Oil & Gas**: Pipeline monitoring, wellhead management, LACT units, separators
- **Rail Systems**: Track circuits, signaling, interlocking, points control
- **Mining**: Ventilation systems, gas detection, hoist control, personnel tracking
- **Manufacturing**: Production lines, robotics, quality control, OEE calculation

## Architecture

### Core Components

1. **Protocol Handlers**
   - Modbus TCP/RTU (`ModbusProtocol.php`)
   - OPC UA (`OPCUAProtocol.php`)
   - DNP3 (`DNP3Protocol.php`)

2. **Monitoring Engine**
   - SCADA Monitor (`SCADAMonitor.php`)
   - Real-time data acquisition
   - Alarm management
   - Historical data logging

3. **Control Systems**
   - Valve Controller (`ValveController.php`)
   - Safety interlocks (IEC 61508/61511 compliant)
   - Authorization levels

4. **Specialized Modules**
   - Tank Monitoring (`TankMonitor.php`)
   - Calibration Management (`CalibrationManager.php`)
   - Industry-specific modules

## Installation Instructions

### Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- Network access to PLCs/RTUs/SCADA devices

### Step 1: Database Installation

1. Navigate to the installation directory:
   ```
   http://localhost/networkscanscada/install_scada.php
   ```

2. The installer will:
   - Create the database schema
   - Install all required tables
   - Insert default protocols and sample data
   - Optimize database for performance

### Step 2: Configure Database Connection

Edit `classes/Database.php` if needed to set your database credentials:

```php
private $host = 'localhost';
private $username = 'root';
private $password = '';
private $database = 'network_security';
```

### Step 3: Configure SCADA Sites

Add your industrial sites to the `scada_sites` table:

```sql
INSERT INTO scada_sites (site_code, site_name, industry_type, location, is_active)
VALUES ('SITE001', 'Refinery Plant A', 'oil_gas', 'Houston, TX', 1);
```

### Step 4: Configure PLCs and RTUs

Add your PLC/RTU devices:

```sql
-- Add PLC
INSERT INTO scada_plcs (asset_id, ip_address, port, protocol_id, scan_rate_ms)
VALUES (1, '192.168.1.100', 502, 1, 1000);

-- Add RTU with GSM
INSERT INTO scada_rtus (asset_id, rtu_address, communication_type, protocol_id, gsm_number)
VALUES (2, 'RTU-001', 'gsm', 3, '+1234567890');
```

### Step 5: Configure SCADA Tags

Define your data points (tags):

```sql
INSERT INTO scada_tags (
    site_id, plc_id, tag_name, tag_description,
    tag_type, data_type, memory_address, engineering_unit,
    alarm_low, alarm_high, is_archived
)
VALUES (
    1, 1, 'PT-101', 'Pipeline Pressure',
    'analog_input', 'float', '40001', 'PSI',
    50.0, 150.0, 1
);
```

### Step 6: Configure Valve Control

Set up valves with safety interlocks:

```sql
-- Add valve
INSERT INTO scada_valve_status (
    site_id, valve_id, valve_name, valve_type,
    position_tag_id, command_tag_id, control_mode
)
VALUES (
    1, 1, 'MOV-101', 'gate',
    10, 11, 'automatic'
);

-- Add interlock rule
INSERT INTO scada_interlock_rules (
    valve_id, tag_id, condition_type,
    threshold_value, severity
)
VALUES (
    1, 5, 'greater_than', 200.0, 'critical'
);
```

### Step 7: Configure Tank Monitoring

Set up tank level monitoring:

```sql
INSERT INTO scada_tank_levels (
    site_id, tank_id, level_tag_id, tank_name,
    capacity_total, capacity_unit,
    low_level_alarm, high_level_alarm,
    critical_low_alarm, critical_high_alarm
)
VALUES (
    1, 3, 20, 'Storage Tank 1',
    10000, 'barrels',
    1000, 8000, 500, 9000
);
```

### Step 8: Configure Calibration Management

Set up instruments for calibration tracking:

```sql
INSERT INTO scada_instruments (
    asset_id, instrument_type, measurement_unit,
    range_min, range_max, accuracy_percent,
    calibration_interval_days
)
VALUES (
    5, 'pressure', 'PSI',
    0, 500, 0.5, 365
);
```

## Usage Examples

### Starting SCADA Monitoring

```php
require_once 'classes/SCADAMonitor.php';

$monitor = new SCADAMonitor();

// Start monitoring a site
$result = $monitor->startMonitoring($siteId = 1);

// Get statistics
$stats = $monitor->getStatistics($siteId);
```

### Controlling Valves

```php
require_once 'classes/ValveController.php';

$controller = new ValveController();

// Open a valve
$result = $controller->controlValve(
    $valveId = 1,
    $command = 'open',
    $value = null,
    $operatorInfo = [
        'operator_name' => 'John Doe',
        'authorization_level' => 'supervisor'
    ]
);
```

### Monitoring Tank Levels

```php
require_once 'classes/TankMonitor.php';

$tankMonitor = new TankMonitor();

// Update tank level
$result = $tankMonitor->updateTankLevel(
    $tankId = 1,
    $levelValue = 5.5, // meters
    $temperatureValue = 25.0,
    $pressureValue = 1.2
);

// Get tank fill rate
$rate = $tankMonitor->getTankRate($tankId, $timeWindowMinutes = 10);
```

### Performing Calibration

```php
require_once 'classes/CalibrationManager.php';

$calibration = new CalibrationManager();

// Perform full calibration
$result = $calibration->performCalibration($instrumentId, [
    'type' => 'full',
    'calibrated_by' => 'Tech-001',
    'reference_standard' => 'NIST-12345',
    'as_found_readings' => [0.1, 25.2, 50.1, 75.3, 100.2],
    'as_left_readings' => [0.0, 25.0, 50.0, 75.0, 100.0],
    'num_points' => 5,
    'comments' => 'Annual calibration'
]);
```

### Industry-Specific Monitoring

#### Oil & Gas

```php
require_once 'classes/Industry/OilGasModule.php';

$oilGas = new \SCADA\Industry\OilGasModule();

// Monitor pipeline
$pipeline = $oilGas->monitorPipeline($pipelineId = 1);

// Monitor wellhead
$well = $oilGas->monitorWellhead($wellId = 1);

// Monitor LACT unit
$lact = $oilGas->monitorLACT($lactId = 1);
```

#### Rail Systems

```php
require_once 'classes/Industry/RailModule.php';

$rail = new \SCADA\Industry\RailModule();

// Monitor track circuit
$circuit = $rail->monitorTrackCircuit($circuitId = 1);

// Monitor signal
$signal = $rail->monitorSignal($signalId = 1);

// Monitor point (switch)
$point = $rail->monitorPoint($pointId = 1);
```

#### Mining

```php
require_once 'classes/Industry/MiningModule.php';

$mining = new \SCADA\Industry\MiningModule();

// Monitor ventilation
$fan = $mining->monitorVentilation($fanId = 1);

// Monitor gas detection
$gas = $mining->monitorGasDetection($sensorId = 1);

// Monitor hoist
$hoist = $mining->monitorHoist($hoistId = 1);
```

#### Manufacturing

```php
require_once 'classes/Industry/ManufacturingModule.php';

$manufacturing = new \SCADA\Industry\ManufacturingModule();

// Monitor production line
$line = $manufacturing->monitorProductionLine($lineId = 1);

// Monitor robot
$robot = $manufacturing->monitorRobot($robotId = 1);

// Get production stats
$stats = $manufacturing->getProductionStats($lineId, $shiftHours = 8);
```

## Protocol Configuration

### Modbus TCP

- Default port: 502
- Supports function codes: 01, 02, 03, 04, 05, 06, 0F, 10
- Addressing: 40001-49999 (Holding Registers), 30001-39999 (Input Registers)

### OPC UA

- Default port: 4840
- Security modes: None, Sign, SignAndEncrypt
- Node addressing: ns=X;s=NodeName or ns=X;i=NodeID

### DNP3

- Default port: 20000
- Supports: Binary I/O, Analog I/O, Counters
- Addressing: AI10 (Analog Input 10), BI5 (Binary Input 5)

## Security Best Practices

### IEC 62443 Compliance

1. **Network Segmentation**
   - Separate SCADA network from corporate network
   - Use industrial firewalls
   - Implement VLANs

2. **Access Control**
   - Role-based authorization (Operator, Supervisor, Engineer, Administrator)
   - Multi-factor authentication recommended
   - Audit logging of all control actions

3. **Data Protection**
   - TLS encryption for OPC UA
   - VPN for remote access
   - Regular security audits

### Authorization Levels

- **Operator**: Basic monitoring and simple controls
- **Supervisor**: Valve control, alarm acknowledgment
- **Engineer**: Configuration changes, interlocks
- **Administrator**: Full system access

## Alarming System

### Alarm Priorities

- **Critical**: Immediate action required (safety-related)
- **High**: Urgent attention needed
- **Medium**: Monitor and plan action
- **Low**: Informational, review when possible

### Alarm Configuration

```sql
UPDATE scada_tags
SET alarm_low_low = 10.0,
    alarm_low = 20.0,
    alarm_high = 80.0,
    alarm_high_high = 90.0,
    is_alarmed = 1
WHERE id = 1;
```

## Performance Optimization

### Database Partitioning

Historical data is partitioned by time range for optimal performance.

### Indexing

All critical tables have proper indexes for fast queries.

### Scan Rate Optimization

- Critical tags: 500-1000ms
- Normal tags: 1000-2000ms
- Slow-changing tags: 5000-10000ms

## Backup and Recovery

### Database Backup

```bash
mysqldump -u root network_security > scada_backup.sql
```

### Configuration Backup

Backup these critical files:
- Database schema
- Tag configurations
- Interlock rules
- User permissions

## Troubleshooting

### PLC Connection Issues

1. Verify IP address and port
2. Check firewall rules
3. Test with Modbus/OPC UA client tool
4. Verify protocol ID in database

### Data Not Updating

1. Check `is_online` status in `scada_plcs` or `scada_rtus`
2. Review `last_error` field
3. Verify tag `memory_address` is correct
4. Check scan rate settings

### Alarm Not Triggering

1. Verify `is_alarmed = 1` for the tag
2. Check alarm threshold values
3. Ensure tag value is being updated
4. Review `scada_alarm_history` table

## API Reference

### REST API Endpoints

```
GET  /scada_api.php?action=sites - List all sites
GET  /scada_api.php?action=site_status&id=1 - Site status
POST /scada_api.php?action=control_valve - Control valve
GET  /scada_api.php?action=alarms - Active alarms
GET  /scada_api.php?action=tank_levels - All tank levels
```

## Support and Documentation

- Technical Support: support@ioc-platform.com
- Documentation: https://docs.ioc-platform.com
- GitHub: https://github.com/ioc-platform/scada

## Compliance Standards

- **IEC 61508**: Functional Safety
- **IEC 61511**: Process Industry Functional Safety
- **IEC 62443**: Industrial Cybersecurity
- **ISA-95**: Enterprise-Control Integration
- **ISO 17025**: Calibration Laboratory Standards

## License

Enterprise license. Unauthorized use prohibited.

---

**Version**: 2.0
**Last Updated**: 2025-01-11
**Platform**: IOC (Intelligent Operating Centre)
