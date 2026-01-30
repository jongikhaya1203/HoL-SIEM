-- IOC Control Panel Database Schema
-- Version 1.0

-- Control Panel Users
CREATE TABLE IF NOT EXISTS cpanel_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    role ENUM('super_admin', 'admin', 'operator', 'viewer') DEFAULT 'viewer',
    is_active TINYINT(1) DEFAULT 1,
    last_login DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Control Panel Settings
CREATE TABLE IF NOT EXISTS cpanel_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT,
    category VARCHAR(50) DEFAULT 'general',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Modules Configuration
CREATE TABLE IF NOT EXISTS cpanel_modules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(50) NOT NULL UNIQUE,
    category VARCHAR(50) DEFAULT 'General',
    description TEXT,
    config JSON,
    is_enabled TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Network Protocols Configuration
CREATE TABLE IF NOT EXISTS cpanel_protocols (
    id INT AUTO_INCREMENT PRIMARY KEY,
    protocol_name VARCHAR(50) NOT NULL UNIQUE,
    display_name VARCHAR(100),
    category VARCHAR(50) DEFAULT 'Industrial',
    config JSON,
    is_enabled TINYINT(1) DEFAULT 0,
    last_connected DATETIME,
    connection_status ENUM('connected', 'disconnected', 'error', 'unknown') DEFAULT 'unknown',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- API Endpoints Configuration
CREATE TABLE IF NOT EXISTS cpanel_api_endpoints (
    id INT AUTO_INCREMENT PRIMARY KEY,
    method VARCHAR(10) NOT NULL,
    path VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(50),
    is_enabled TINYINT(1) DEFAULT 1,
    require_auth TINYINT(1) DEFAULT 1,
    rate_limit INT DEFAULT 100,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_endpoint (method, path)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- API Keys
CREATE TABLE IF NOT EXISTS cpanel_api_keys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    key_name VARCHAR(100) NOT NULL,
    api_key VARCHAR(255) NOT NULL,
    permissions JSON,
    is_active TINYINT(1) DEFAULT 1,
    last_used DATETIME,
    usage_count INT DEFAULT 0,
    expires_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Notification Channels Configuration
CREATE TABLE IF NOT EXISTS cpanel_channels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    channel_name VARCHAR(50) NOT NULL UNIQUE,
    display_name VARCHAR(100),
    config JSON,
    is_enabled TINYINT(1) DEFAULT 0,
    last_test DATETIME,
    test_status ENUM('success', 'failed', 'pending') DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Notification Rules
CREATE TABLE IF NOT EXISTS cpanel_notification_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rule_name VARCHAR(100) NOT NULL,
    event_type VARCHAR(50) NOT NULL,
    severity_filter JSON,
    channels JSON,
    conditions JSON,
    is_enabled TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- SCADA Devices Configuration
CREATE TABLE IF NOT EXISTS cpanel_scada_devices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    device_name VARCHAR(100) NOT NULL,
    device_type VARCHAR(50),
    protocol VARCHAR(50),
    connection_config JSON,
    polling_interval INT DEFAULT 1000,
    is_enabled TINYINT(1) DEFAULT 1,
    status ENUM('online', 'offline', 'error', 'unknown') DEFAULT 'unknown',
    last_poll DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- SCADA Tags Configuration
CREATE TABLE IF NOT EXISTS cpanel_scada_tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    device_id INT,
    tag_name VARCHAR(100) NOT NULL,
    tag_address VARCHAR(100),
    data_type VARCHAR(20),
    scaling_factor DECIMAL(10,4) DEFAULT 1.0,
    engineering_units VARCHAR(20),
    alarm_low DECIMAL(15,4),
    alarm_high DECIMAL(15,4),
    is_enabled TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (device_id) REFERENCES cpanel_scada_devices(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Email DLP Rules Configuration
CREATE TABLE IF NOT EXISTS cpanel_dlp_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rule_name VARCHAR(100) NOT NULL,
    rule_type ENUM('regex', 'keyword', 'pattern') DEFAULT 'regex',
    pattern TEXT,
    severity ENUM('critical', 'high', 'medium', 'low') DEFAULT 'medium',
    action ENUM('flag', 'block', 'quarantine', 'notify') DEFAULT 'flag',
    is_enabled TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Compliance Frameworks Configuration
CREATE TABLE IF NOT EXISTS cpanel_compliance_frameworks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    framework_name VARCHAR(100) NOT NULL,
    framework_code VARCHAR(50) NOT NULL UNIQUE,
    version VARCHAR(20),
    is_enabled TINYINT(1) DEFAULT 1,
    controls_config JSON,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Security Settings
CREATE TABLE IF NOT EXISTS cpanel_security_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Audit Log for CPanel
CREATE TABLE IF NOT EXISTS cpanel_audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    target_type VARCHAR(50),
    target_id INT,
    old_value JSON,
    new_value JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_action (action),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Scheduled Tasks
CREATE TABLE IF NOT EXISTS cpanel_scheduled_tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_name VARCHAR(100) NOT NULL,
    task_type VARCHAR(50) NOT NULL,
    schedule_cron VARCHAR(100),
    task_config JSON,
    is_enabled TINYINT(1) DEFAULT 1,
    last_run DATETIME,
    next_run DATETIME,
    last_status ENUM('success', 'failed', 'running', 'pending') DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Backup History
CREATE TABLE IF NOT EXISTS cpanel_backups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    backup_name VARCHAR(255) NOT NULL,
    backup_type ENUM('full', 'incremental', 'config_only') DEFAULT 'full',
    file_path VARCHAR(500),
    file_size BIGINT,
    status ENUM('completed', 'failed', 'in_progress') DEFAULT 'in_progress',
    created_by INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default modules
INSERT IGNORE INTO cpanel_modules (name, code, category, description, is_enabled) VALUES
('Network Scanner', 'network_scanner', 'Security', 'Network vulnerability scanning and port detection', 1),
('SCADA Monitor', 'scada_monitor', 'Industrial', 'Industrial control system monitoring', 1),
('Email DLP', 'email_dlp', 'Security', 'Email data loss prevention and scanning', 1),
('Compliance Checker', 'compliance', 'Compliance', 'Multi-framework compliance checking', 1),
('ITSM', 'itsm', 'Operations', 'IT Service Management', 1),
('Network Traffic Analysis', 'nta', 'Network', 'NetFlow and traffic analysis', 1),
('IP Address Manager', 'ipam', 'Network', 'IP address management with DHCP/DNS', 1),
('SNMP Monitor', 'snmp', 'Network', 'SNMP device monitoring', 1),
('Database Performance', 'dpa', 'Database', 'Database performance analyzer', 1),
('Server Monitor', 'sam', 'Infrastructure', 'Server and application monitoring', 1),
('Alert Manager', 'alerts', 'Operations', 'Multi-channel alerting system', 1),
('Report Generator', 'reports', 'Reporting', 'Custom report generation', 1),
('AI Analytics', 'ai_analytics', 'Advanced', 'AI-powered security analytics', 0),
('Network Topology', 'topology', 'Network', 'Network topology mapping', 1),
('Remote Support', 'remote_support', 'Operations', 'Remote support and access', 0),
('Observability', 'observability', 'Monitoring', 'Full-stack observability', 1);

-- Insert default protocols
INSERT IGNORE INTO cpanel_protocols (protocol_name, display_name, category, is_enabled) VALUES
('modbus_tcp', 'Modbus TCP', 'Industrial', 0),
('modbus_rtu', 'Modbus RTU', 'Industrial', 0),
('opc_ua', 'OPC UA', 'Industrial', 0),
('dnp3', 'DNP3', 'Industrial', 0),
('snmp', 'SNMP', 'Network', 0),
('mqtt', 'MQTT', 'IoT', 0),
('bacnet', 'BACnet', 'Building Automation', 0),
('ethernet_ip', 'EtherNet/IP', 'Industrial', 0),
('profinet', 'PROFINET', 'Industrial', 0),
('iec61850', 'IEC 61850', 'Power Systems', 0);

-- Insert default notification channels
INSERT IGNORE INTO cpanel_channels (channel_name, display_name, is_enabled) VALUES
('email', 'Email', 0),
('sms', 'SMS', 0),
('slack', 'Slack', 0),
('teams', 'Microsoft Teams', 0),
('webhook', 'Custom Webhook', 0),
('pushover', 'Pushover', 0),
('pagerduty', 'PagerDuty', 0),
('discord', 'Discord', 0),
('syslog', 'Syslog', 0);

-- Insert default compliance frameworks
INSERT IGNORE INTO cpanel_compliance_frameworks (framework_name, framework_code, version, is_enabled) VALUES
('NIST Cybersecurity Framework', 'NIST_CSF', '1.1', 1),
('ISO 27001', 'ISO_27001', '2013', 1),
('CIS Controls', 'CIS', 'v8', 1),
('PCI DSS', 'PCI_DSS', '4.0', 1),
('HIPAA', 'HIPAA', '2013', 1),
('SOC 2', 'SOC2', 'Type II', 1);

-- Insert default security settings
INSERT IGNORE INTO cpanel_security_settings (setting_key, setting_value, description) VALUES
('session_timeout', '3600', 'Session timeout in seconds'),
('max_login_attempts', '5', 'Maximum failed login attempts'),
('lockout_duration', '900', 'Account lockout duration in seconds'),
('password_min_length', '8', 'Minimum password length'),
('require_2fa', '0', 'Require two-factor authentication'),
('ip_whitelist', '', 'Comma-separated list of allowed IPs'),
('audit_retention_days', '90', 'Days to retain audit logs');
