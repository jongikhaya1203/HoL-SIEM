-- =====================================================
-- SCADA Network Monitoring System - Database Schema
-- Industrial Control Systems (ICS/SCADA) Monitoring
-- Supports: Oil & Gas, Rail, Mining, Manufacturing
-- =====================================================

-- Drop existing tables if they exist
DROP TABLE IF EXISTS scada_alarm_history;
DROP TABLE IF EXISTS scada_tag_history;
DROP TABLE IF EXISTS scada_control_actions;
DROP TABLE IF EXISTS scada_calibration_records;
DROP TABLE IF EXISTS scada_valve_status;
DROP TABLE IF EXISTS scada_tank_levels;
DROP TABLE IF EXISTS scada_tags;
DROP TABLE IF EXISTS scada_rtus;
DROP TABLE IF EXISTS scada_plcs;
DROP TABLE IF EXISTS scada_instruments;
DROP TABLE IF EXISTS scada_assets;
DROP TABLE IF EXISTS scada_sites;
DROP TABLE IF EXISTS scada_protocols;
DROP TABLE IF EXISTS scada_industry_configs;

-- =====================================================
-- Core SCADA Tables
-- =====================================================

-- Industry-specific configurations
CREATE TABLE scada_industry_configs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    industry_type ENUM('oil_gas', 'rail', 'mining', 'manufacturing', 'water_treatment', 'power_generation') NOT NULL,
    config_name VARCHAR(100) NOT NULL,
    config_data JSON NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_industry (industry_type),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- SCADA communication protocols
CREATE TABLE scada_protocols (
    id INT AUTO_INCREMENT PRIMARY KEY,
    protocol_name ENUM('modbus_tcp', 'modbus_rtu', 'opc_ua', 'dnp3', 'iec61850', 'bacnet', 'profinet', 'ethernet_ip') NOT NULL,
    protocol_version VARCHAR(20),
    default_port INT,
    description TEXT,
    is_secure BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_protocol (protocol_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- SCADA sites/facilities
CREATE TABLE scada_sites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT,
    site_code VARCHAR(50) UNIQUE NOT NULL,
    site_name VARCHAR(200) NOT NULL,
    industry_type ENUM('oil_gas', 'rail', 'mining', 'manufacturing', 'water_treatment', 'power_generation') NOT NULL,
    location VARCHAR(255),
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    timezone VARCHAR(50) DEFAULT 'UTC',
    is_active BOOLEAN DEFAULT TRUE,
    emergency_contact VARCHAR(100),
    site_metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_tenant (tenant_id),
    INDEX idx_industry (industry_type),
    INDEX idx_active (is_active),
    INDEX idx_site_code (site_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- SCADA assets (equipment, systems)
CREATE TABLE scada_assets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    site_id INT NOT NULL,
    asset_code VARCHAR(50) NOT NULL,
    asset_name VARCHAR(200) NOT NULL,
    asset_type ENUM('plc', 'rtu', 'valve', 'pump', 'tank', 'sensor', 'actuator', 'motor', 'compressor', 'turbine', 'transformer', 'breaker', 'other') NOT NULL,
    manufacturer VARCHAR(100),
    model_number VARCHAR(100),
    serial_number VARCHAR(100),
    installation_date DATE,
    last_maintenance_date DATE,
    next_maintenance_date DATE,
    criticality ENUM('critical', 'high', 'medium', 'low') DEFAULT 'medium',
    status ENUM('operational', 'maintenance', 'fault', 'offline') DEFAULT 'operational',
    asset_metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (site_id) REFERENCES scada_sites(id) ON DELETE CASCADE,
    UNIQUE KEY unique_asset (site_id, asset_code),
    INDEX idx_site (site_id),
    INDEX idx_type (asset_type),
    INDEX idx_status (status),
    INDEX idx_criticality (criticality)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Programmable Logic Controllers (PLCs)
CREATE TABLE scada_plcs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    asset_id INT NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    port INT DEFAULT 502,
    protocol_id INT NOT NULL,
    rack_number INT DEFAULT 0,
    slot_number INT DEFAULT 0,
    cpu_type VARCHAR(100),
    firmware_version VARCHAR(50),
    scan_rate_ms INT DEFAULT 1000,
    connection_timeout_ms INT DEFAULT 5000,
    max_retries INT DEFAULT 3,
    is_online BOOLEAN DEFAULT FALSE,
    last_poll_time TIMESTAMP NULL,
    last_error TEXT,
    plc_metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (asset_id) REFERENCES scada_assets(id) ON DELETE CASCADE,
    FOREIGN KEY (protocol_id) REFERENCES scada_protocols(id),
    INDEX idx_asset (asset_id),
    INDEX idx_ip (ip_address),
    INDEX idx_online (is_online)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Remote Terminal Units (RTUs)
CREATE TABLE scada_rtus (
    id INT AUTO_INCREMENT PRIMARY KEY,
    asset_id INT NOT NULL,
    rtu_address VARCHAR(100) NOT NULL,
    communication_type ENUM('serial', 'tcp', 'gsm', 'radio', 'satellite') NOT NULL,
    protocol_id INT NOT NULL,
    gsm_number VARCHAR(20),
    signal_strength INT,
    battery_voltage DECIMAL(5,2),
    scan_rate_ms INT DEFAULT 5000,
    is_online BOOLEAN DEFAULT FALSE,
    last_poll_time TIMESTAMP NULL,
    last_error TEXT,
    rtu_metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (asset_id) REFERENCES scada_assets(id) ON DELETE CASCADE,
    FOREIGN KEY (protocol_id) REFERENCES scada_protocols(id),
    INDEX idx_asset (asset_id),
    INDEX idx_comm_type (communication_type),
    INDEX idx_online (is_online)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Instruments (sensors, transmitters)
CREATE TABLE scada_instruments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    asset_id INT NOT NULL,
    tag_prefix VARCHAR(50),
    instrument_type ENUM('pressure', 'temperature', 'flow', 'level', 'ph', 'conductivity', 'vibration', 'position', 'speed', 'voltage', 'current', 'power', 'gas_detector', 'other') NOT NULL,
    measurement_unit VARCHAR(20) NOT NULL,
    range_min DECIMAL(15,4),
    range_max DECIMAL(15,4),
    accuracy_percent DECIMAL(5,2),
    calibration_interval_days INT DEFAULT 365,
    last_calibration_date DATE,
    next_calibration_date DATE,
    calibration_status ENUM('valid', 'due', 'overdue', 'failed') DEFAULT 'valid',
    instrument_metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (asset_id) REFERENCES scada_assets(id) ON DELETE CASCADE,
    INDEX idx_asset (asset_id),
    INDEX idx_type (instrument_type),
    INDEX idx_calibration_status (calibration_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- SCADA tags (data points)
CREATE TABLE scada_tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    site_id INT NOT NULL,
    plc_id INT,
    rtu_id INT,
    instrument_id INT,
    tag_name VARCHAR(100) NOT NULL,
    tag_description VARCHAR(255),
    tag_type ENUM('analog_input', 'analog_output', 'digital_input', 'digital_output', 'calculated', 'string') NOT NULL,
    data_type ENUM('int16', 'int32', 'uint16', 'uint32', 'float', 'double', 'bool', 'string') NOT NULL,
    memory_address VARCHAR(50),
    register_number INT,
    bit_position INT,
    scaling_factor DECIMAL(10,4) DEFAULT 1.0,
    offset DECIMAL(10,4) DEFAULT 0.0,
    engineering_unit VARCHAR(20),
    min_value DECIMAL(15,4),
    max_value DECIMAL(15,4),
    alarm_low_low DECIMAL(15,4),
    alarm_low DECIMAL(15,4),
    alarm_high DECIMAL(15,4),
    alarm_high_high DECIMAL(15,4),
    deadband DECIMAL(10,4) DEFAULT 0.0,
    scan_rate_ms INT DEFAULT 1000,
    is_archived BOOLEAN DEFAULT TRUE,
    is_alarmed BOOLEAN DEFAULT FALSE,
    current_value VARCHAR(100),
    current_quality ENUM('good', 'bad', 'uncertain') DEFAULT 'uncertain',
    last_update_time TIMESTAMP NULL,
    tag_metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (site_id) REFERENCES scada_sites(id) ON DELETE CASCADE,
    FOREIGN KEY (plc_id) REFERENCES scada_plcs(id) ON DELETE CASCADE,
    FOREIGN KEY (rtu_id) REFERENCES scada_rtus(id) ON DELETE CASCADE,
    FOREIGN KEY (instrument_id) REFERENCES scada_instruments(id) ON DELETE CASCADE,
    UNIQUE KEY unique_tag (site_id, tag_name),
    INDEX idx_site (site_id),
    INDEX idx_plc (plc_id),
    INDEX idx_rtu (rtu_id),
    INDEX idx_instrument (instrument_id),
    INDEX idx_tag_name (tag_name),
    INDEX idx_tag_type (tag_type),
    INDEX idx_alarmed (is_alarmed)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- Process Control Tables
-- =====================================================

-- Tank level monitoring
CREATE TABLE scada_tank_levels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    site_id INT NOT NULL,
    tank_id INT NOT NULL,
    level_tag_id INT NOT NULL,
    tank_name VARCHAR(100) NOT NULL,
    capacity_total DECIMAL(15,2) NOT NULL,
    capacity_unit VARCHAR(20) DEFAULT 'liters',
    current_level DECIMAL(15,2),
    current_volume DECIMAL(15,2),
    current_percentage DECIMAL(5,2),
    tank_status ENUM('normal', 'low', 'high', 'critical_low', 'critical_high', 'overflow', 'empty') DEFAULT 'normal',
    low_level_alarm DECIMAL(15,2),
    high_level_alarm DECIMAL(15,2),
    critical_low_alarm DECIMAL(15,2),
    critical_high_alarm DECIMAL(15,2),
    product_type VARCHAR(100),
    temperature_tag_id INT,
    pressure_tag_id INT,
    last_update_time TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (site_id) REFERENCES scada_sites(id) ON DELETE CASCADE,
    FOREIGN KEY (tank_id) REFERENCES scada_assets(id) ON DELETE CASCADE,
    FOREIGN KEY (level_tag_id) REFERENCES scada_tags(id),
    INDEX idx_site (site_id),
    INDEX idx_tank (tank_id),
    INDEX idx_status (tank_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Valve control and status
CREATE TABLE scada_valve_status (
    id INT AUTO_INCREMENT PRIMARY KEY,
    site_id INT NOT NULL,
    valve_id INT NOT NULL,
    valve_name VARCHAR(100) NOT NULL,
    valve_type ENUM('gate', 'globe', 'ball', 'butterfly', 'check', 'safety', 'control', 'solenoid') NOT NULL,
    control_mode ENUM('manual', 'automatic', 'remote', 'emergency') DEFAULT 'manual',
    position_tag_id INT NOT NULL,
    command_tag_id INT,
    current_position DECIMAL(5,2) DEFAULT 0.0,
    target_position DECIMAL(5,2) DEFAULT 0.0,
    valve_state ENUM('open', 'closed', 'opening', 'closing', 'partial', 'fault', 'locked') DEFAULT 'closed',
    is_interlocked BOOLEAN DEFAULT FALSE,
    interlock_reason TEXT,
    open_limit_switch BOOLEAN DEFAULT FALSE,
    close_limit_switch BOOLEAN DEFAULT TRUE,
    torque_current DECIMAL(10,2),
    cycle_count INT DEFAULT 0,
    last_operated_time TIMESTAMP NULL,
    last_operated_by VARCHAR(100),
    safety_certified BOOLEAN DEFAULT FALSE,
    safety_cert_expiry DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (site_id) REFERENCES scada_sites(id) ON DELETE CASCADE,
    FOREIGN KEY (valve_id) REFERENCES scada_assets(id) ON DELETE CASCADE,
    FOREIGN KEY (position_tag_id) REFERENCES scada_tags(id),
    FOREIGN KEY (command_tag_id) REFERENCES scada_tags(id),
    INDEX idx_site (site_id),
    INDEX idx_valve (valve_id),
    INDEX idx_state (valve_state),
    INDEX idx_interlock (is_interlocked)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Control actions log
CREATE TABLE scada_control_actions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    site_id INT NOT NULL,
    asset_id INT,
    tag_id INT,
    action_type ENUM('valve_open', 'valve_close', 'setpoint_change', 'mode_change', 'start', 'stop', 'reset', 'acknowledge', 'other') NOT NULL,
    action_description TEXT NOT NULL,
    old_value VARCHAR(100),
    new_value VARCHAR(100),
    initiated_by VARCHAR(100) NOT NULL,
    initiated_from VARCHAR(100),
    authorization_level ENUM('operator', 'supervisor', 'engineer', 'administrator') NOT NULL,
    requires_confirmation BOOLEAN DEFAULT FALSE,
    confirmed_by VARCHAR(100),
    confirmed_at TIMESTAMP NULL,
    execution_status ENUM('pending', 'executing', 'completed', 'failed', 'cancelled', 'timeout') DEFAULT 'pending',
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (site_id) REFERENCES scada_sites(id) ON DELETE CASCADE,
    FOREIGN KEY (asset_id) REFERENCES scada_assets(id) ON DELETE SET NULL,
    FOREIGN KEY (tag_id) REFERENCES scada_tags(id) ON DELETE SET NULL,
    INDEX idx_site (site_id),
    INDEX idx_asset (asset_id),
    INDEX idx_type (action_type),
    INDEX idx_status (execution_status),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- Calibration and Maintenance
-- =====================================================

-- Calibration records
CREATE TABLE scada_calibration_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    instrument_id INT NOT NULL,
    calibration_type ENUM('zero', 'span', 'full', 'verification', 'adjustment') NOT NULL,
    calibration_date DATE NOT NULL,
    calibrated_by VARCHAR(100) NOT NULL,
    reference_standard VARCHAR(100),
    test_points JSON,
    as_found_values JSON,
    as_left_values JSON,
    accuracy_achieved DECIMAL(5,2),
    pass_fail ENUM('pass', 'fail', 'conditional') NOT NULL,
    next_calibration_date DATE,
    certificate_number VARCHAR(100),
    comments TEXT,
    calibration_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (instrument_id) REFERENCES scada_instruments(id) ON DELETE CASCADE,
    INDEX idx_instrument (instrument_id),
    INDEX idx_date (calibration_date),
    INDEX idx_pass_fail (pass_fail)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- Historical Data and Alarms
-- =====================================================

-- Tag historical data (time-series)
-- Note: No foreign key due to partitioning limitation in MySQL
CREATE TABLE scada_tag_history (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    tag_id INT NOT NULL,
    timestamp TIMESTAMP(3) NOT NULL,
    value VARCHAR(100) NOT NULL,
    quality ENUM('good', 'bad', 'uncertain') DEFAULT 'good',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tag_time (tag_id, timestamp),
    INDEX idx_timestamp (timestamp),
    INDEX idx_tag (tag_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
PARTITION BY RANGE (UNIX_TIMESTAMP(timestamp)) (
    PARTITION p_old VALUES LESS THAN (UNIX_TIMESTAMP('2025-01-01')),
    PARTITION p_2025_q1 VALUES LESS THAN (UNIX_TIMESTAMP('2025-04-01')),
    PARTITION p_2025_q2 VALUES LESS THAN (UNIX_TIMESTAMP('2025-07-01')),
    PARTITION p_2025_q3 VALUES LESS THAN (UNIX_TIMESTAMP('2025-10-01')),
    PARTITION p_2025_q4 VALUES LESS THAN (UNIX_TIMESTAMP('2026-01-01')),
    PARTITION p_future VALUES LESS THAN MAXVALUE
);

-- Alarm history
CREATE TABLE scada_alarm_history (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    site_id INT NOT NULL,
    tag_id INT NOT NULL,
    asset_id INT,
    alarm_type ENUM('low_low', 'low', 'high', 'high_high', 'deviation', 'rate_of_change', 'system', 'communication', 'other') NOT NULL,
    severity ENUM('critical', 'high', 'medium', 'low', 'info') DEFAULT 'medium',
    alarm_message TEXT NOT NULL,
    alarm_value VARCHAR(100),
    trigger_time TIMESTAMP NOT NULL,
    acknowledge_time TIMESTAMP NULL,
    acknowledged_by VARCHAR(100),
    clear_time TIMESTAMP NULL,
    duration_seconds INT,
    alarm_state ENUM('active', 'acknowledged', 'cleared', 'suppressed') DEFAULT 'active',
    alarm_metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (site_id) REFERENCES scada_sites(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES scada_tags(id) ON DELETE CASCADE,
    FOREIGN KEY (asset_id) REFERENCES scada_assets(id) ON DELETE SET NULL,
    INDEX idx_site (site_id),
    INDEX idx_tag (tag_id),
    INDEX idx_asset (asset_id),
    INDEX idx_severity (severity),
    INDEX idx_state (alarm_state),
    INDEX idx_trigger_time (trigger_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- Insert Default Data
-- =====================================================

-- Insert default protocols
INSERT INTO scada_protocols (protocol_name, protocol_version, default_port, description, is_secure) VALUES
('modbus_tcp', '1.0', 502, 'Modbus TCP/IP protocol for industrial automation', FALSE),
('modbus_rtu', '1.0', NULL, 'Modbus RTU serial protocol', FALSE),
('opc_ua', '1.04', 4840, 'OPC Unified Architecture - Industrial IoT standard', TRUE),
('dnp3', '3.0', 20000, 'Distributed Network Protocol for SCADA/utilities', FALSE),
('iec61850', '2.0', 102, 'Communication protocol for electrical substations', TRUE),
('bacnet', '1.0', 47808, 'Building automation and control networks', FALSE),
('profinet', '2.4', NULL, 'Industrial Ethernet standard by Profibus', FALSE),
('ethernet_ip', '1.0', 44818, 'EtherNet/IP - Common Industrial Protocol', FALSE);

-- Insert industry-specific default configurations
INSERT INTO scada_industry_configs (industry_type, config_name, config_data) VALUES
('oil_gas', 'Default Pipeline Monitoring', '{"scan_rate": 1000, "alarm_deadband": 0.5, "critical_tags": ["pressure", "flow", "temperature"], "safety_systems": ["esdv", "psv", "fire_gas"]}'),
('rail', 'Default Rail Signaling', '{"scan_rate": 500, "critical_tags": ["track_circuit", "signal_aspect", "point_position"], "safety_systems": ["interlocking", "axle_counter", "train_detection"]}'),
('mining', 'Default Mine Monitoring', '{"scan_rate": 2000, "critical_tags": ["gas_concentration", "ventilation", "hoist_position"], "safety_systems": ["gas_detection", "emergency_egress", "roof_support"]}'),
('manufacturing', 'Default Production Line', '{"scan_rate": 1000, "critical_tags": ["production_rate", "quality_metrics", "machine_status"], "safety_systems": ["emergency_stop", "light_curtain", "safety_relay"]}');

-- =====================================================
-- Views for Quick Access
-- =====================================================

CREATE OR REPLACE VIEW v_critical_alarms AS
SELECT
    a.id,
    s.site_name,
    t.tag_name,
    a.alarm_type,
    a.severity,
    a.alarm_message,
    a.alarm_value,
    a.trigger_time,
    a.alarm_state,
    TIMESTAMPDIFF(MINUTE, a.trigger_time, NOW()) as minutes_active
FROM scada_alarm_history a
JOIN scada_sites s ON a.site_id = s.id
JOIN scada_tags t ON a.tag_id = t.id
WHERE a.alarm_state = 'active'
AND a.severity IN ('critical', 'high')
ORDER BY a.trigger_time DESC;

CREATE OR REPLACE VIEW v_valve_overview AS
SELECT
    v.id,
    s.site_name,
    v.valve_name,
    v.valve_type,
    v.control_mode,
    v.current_position,
    v.valve_state,
    v.is_interlocked,
    v.last_operated_time,
    v.last_operated_by
FROM scada_valve_status v
JOIN scada_sites s ON v.site_id = s.id
ORDER BY s.site_name, v.valve_name;

CREATE OR REPLACE VIEW v_tank_status AS
SELECT
    t.id,
    s.site_name,
    t.tank_name,
    t.current_percentage,
    t.current_volume,
    t.capacity_total,
    t.capacity_unit,
    t.tank_status,
    t.product_type,
    t.last_update_time
FROM scada_tank_levels t
JOIN scada_sites s ON t.site_id = s.id
ORDER BY t.current_percentage ASC;

CREATE OR REPLACE VIEW v_instruments_due_calibration AS
SELECT
    i.id,
    s.site_name,
    a.asset_name,
    i.instrument_type,
    i.last_calibration_date,
    i.next_calibration_date,
    i.calibration_status,
    DATEDIFF(i.next_calibration_date, CURDATE()) as days_until_due
FROM scada_instruments i
JOIN scada_assets a ON i.asset_id = a.id
JOIN scada_sites s ON a.site_id = s.id
WHERE i.calibration_status IN ('due', 'overdue')
ORDER BY i.next_calibration_date ASC;

-- =====================================================
-- Indexes for Performance
-- =====================================================

-- Additional indexes for time-series queries
ALTER TABLE scada_tag_history ADD INDEX idx_tag_timestamp_value (tag_id, timestamp, value(50));
ALTER TABLE scada_alarm_history ADD INDEX idx_site_severity_state (site_id, severity, alarm_state);

-- =====================================================
-- Stored Procedures
-- =====================================================

DELIMITER //

-- Procedure to update valve position with safety checks
CREATE PROCEDURE sp_update_valve_position(
    IN p_valve_id INT,
    IN p_target_position DECIMAL(5,2),
    IN p_operated_by VARCHAR(100)
)
BEGIN
    DECLARE v_is_interlocked BOOLEAN;
    DECLARE v_current_position DECIMAL(5,2);

    -- Check if valve is interlocked
    SELECT is_interlocked, current_position
    INTO v_is_interlocked, v_current_position
    FROM scada_valve_status
    WHERE valve_id = p_valve_id;

    IF v_is_interlocked THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Valve is interlocked - cannot operate';
    ELSE
        -- Update valve
        UPDATE scada_valve_status
        SET target_position = p_target_position,
            valve_state = CASE
                WHEN p_target_position > v_current_position THEN 'opening'
                WHEN p_target_position < v_current_position THEN 'closing'
                ELSE valve_state
            END,
            last_operated_time = NOW(),
            last_operated_by = p_operated_by,
            cycle_count = cycle_count + 1
        WHERE valve_id = p_valve_id;

        -- Log the action
        INSERT INTO scada_control_actions (
            site_id, asset_id, action_type, action_description,
            old_value, new_value, initiated_by, authorization_level, execution_status
        )
        SELECT
            site_id, p_valve_id, 'valve_open',
            CONCAT('Valve position changed from ', v_current_position, ' to ', p_target_position),
            v_current_position, p_target_position, p_operated_by, 'operator', 'completed'
        FROM scada_valve_status WHERE valve_id = p_valve_id;
    END IF;
END //

-- Procedure to check and generate calibration due alerts
CREATE PROCEDURE sp_check_calibration_due()
BEGIN
    UPDATE scada_instruments
    SET calibration_status = CASE
        WHEN next_calibration_date < CURDATE() THEN 'overdue'
        WHEN next_calibration_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'due'
        ELSE 'valid'
    END
    WHERE calibration_status != 'failed';
END //

DELIMITER ;

-- =====================================================
-- Events for Automatic Maintenance
-- =====================================================

-- Enable event scheduler
SET GLOBAL event_scheduler = ON;

-- Event to check calibration status daily
CREATE EVENT IF NOT EXISTS evt_check_calibration_daily
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_TIMESTAMP
DO CALL sp_check_calibration_due();

-- Event to clean old historical data (keep 1 year)
CREATE EVENT IF NOT EXISTS evt_cleanup_old_history
ON SCHEDULE EVERY 1 WEEK
STARTS CURRENT_TIMESTAMP
DO DELETE FROM scada_tag_history WHERE timestamp < DATE_SUB(NOW(), INTERVAL 1 YEAR);

-- =====================================================
-- End of Schema
-- =====================================================
