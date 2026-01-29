-- SolarWinds-Style Modules System
-- Add these tables to enable modular features

USE network_security_scanner;

-- Modules Table: Track available modules and their status
CREATE TABLE IF NOT EXISTS modules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    module_code VARCHAR(50) UNIQUE NOT NULL,
    module_name VARCHAR(100) NOT NULL,
    category ENUM('network_infrastructure', 'systems_applications', 'database', 'security', 'service_management', 'observability') NOT NULL,
    description TEXT,
    icon VARCHAR(50) DEFAULT '📊',
    status ENUM('active', 'inactive', 'beta', 'coming_soon') DEFAULT 'active',
    implementation_level ENUM('full', 'partial', 'placeholder', 'planned') DEFAULT 'placeholder',
    url VARCHAR(255) DEFAULT NULL,
    display_order INT DEFAULT 0,
    enabled BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_status (status),
    INDEX idx_enabled (enabled)
) ENGINE=InnoDB;

-- Module Metrics: Store real-time metrics for each module
CREATE TABLE IF NOT EXISTS module_metrics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    module_id INT NOT NULL,
    metric_name VARCHAR(100) NOT NULL,
    metric_value VARCHAR(255),
    metric_unit VARCHAR(20) DEFAULT NULL,
    metric_status ENUM('healthy', 'warning', 'critical', 'unknown') DEFAULT 'unknown',
    collected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE,
    INDEX idx_module (module_id),
    INDEX idx_collected (collected_at)
) ENGINE=InnoDB;

-- Network Devices: For device tracking and management
CREATE TABLE IF NOT EXISTS network_devices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    device_name VARCHAR(255) NOT NULL,
    device_type ENUM('router', 'switch', 'firewall', 'access_point', 'server', 'workstation', 'phone', 'other') NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    mac_address VARCHAR(17) DEFAULT NULL,
    manufacturer VARCHAR(100) DEFAULT NULL,
    model VARCHAR(100) DEFAULT NULL,
    firmware_version VARCHAR(50) DEFAULT NULL,
    serial_number VARCHAR(100) DEFAULT NULL,
    location VARCHAR(255) DEFAULT NULL,
    status ENUM('online', 'offline', 'warning') DEFAULT 'unknown',
    last_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    snmp_community VARCHAR(100) DEFAULT NULL,
    snmp_version ENUM('v1', 'v2c', 'v3') DEFAULT 'v2c',
    monitored BOOLEAN DEFAULT TRUE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_ip (ip_address),
    INDEX idx_type (device_type),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- Performance Metrics: Store historical performance data
CREATE TABLE IF NOT EXISTS performance_metrics (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    device_id INT DEFAULT NULL,
    host_id INT DEFAULT NULL,
    metric_type VARCHAR(50) NOT NULL,
    metric_value DECIMAL(15,4) NOT NULL,
    unit VARCHAR(20) DEFAULT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (device_id) REFERENCES network_devices(id) ON DELETE CASCADE,
    FOREIGN KEY (host_id) REFERENCES hosts(id) ON DELETE CASCADE,
    INDEX idx_device (device_id),
    INDEX idx_host (host_id),
    INDEX idx_type (metric_type),
    INDEX idx_timestamp (timestamp)
) ENGINE=InnoDB;

-- Traffic Flows: NetFlow/sFlow data
CREATE TABLE IF NOT EXISTS traffic_flows (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    source_ip VARCHAR(45) NOT NULL,
    destination_ip VARCHAR(45) NOT NULL,
    source_port INT DEFAULT NULL,
    destination_port INT DEFAULT NULL,
    protocol VARCHAR(20) NOT NULL,
    bytes_transferred BIGINT DEFAULT 0,
    packets_count INT DEFAULT 0,
    flow_start TIMESTAMP NOT NULL,
    flow_end TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_source (source_ip),
    INDEX idx_destination (destination_ip),
    INDEX idx_protocol (protocol),
    INDEX idx_flow_start (flow_start)
) ENGINE=InnoDB;

-- IP Address Management
CREATE TABLE IF NOT EXISTS ip_addresses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) UNIQUE NOT NULL,
    subnet VARCHAR(50) NOT NULL,
    status ENUM('available', 'allocated', 'reserved', 'quarantine') DEFAULT 'available',
    assigned_to VARCHAR(255) DEFAULT NULL,
    device_id INT DEFAULT NULL,
    mac_address VARCHAR(17) DEFAULT NULL,
    hostname VARCHAR(255) DEFAULT NULL,
    dns_name VARCHAR(255) DEFAULT NULL,
    description TEXT,
    allocated_at TIMESTAMP DEFAULT NULL,
    last_seen TIMESTAMP DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (device_id) REFERENCES network_devices(id) ON DELETE SET NULL,
    INDEX idx_ip (ip_address),
    INDEX idx_subnet (subnet),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- VoIP Call Records
CREATE TABLE IF NOT EXISTS voip_calls (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    call_id VARCHAR(100) UNIQUE NOT NULL,
    caller_number VARCHAR(50),
    callee_number VARCHAR(50),
    call_start TIMESTAMP NOT NULL,
    call_end TIMESTAMP DEFAULT NULL,
    duration INT DEFAULT 0 COMMENT 'Duration in seconds',
    codec VARCHAR(20) DEFAULT NULL,
    mos_score DECIMAL(3,2) DEFAULT NULL COMMENT 'Mean Opinion Score 1-5',
    jitter_ms INT DEFAULT NULL,
    packet_loss_percent DECIMAL(5,2) DEFAULT NULL,
    quality_rating ENUM('excellent', 'good', 'fair', 'poor') DEFAULT NULL,
    server_ip VARCHAR(45) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_caller (caller_number),
    INDEX idx_callee (callee_number),
    INDEX idx_start (call_start),
    INDEX idx_quality (quality_rating)
) ENGINE=InnoDB;

-- Application Monitoring
CREATE TABLE IF NOT EXISTS monitored_applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    app_name VARCHAR(100) NOT NULL,
    app_type VARCHAR(50) DEFAULT NULL,
    host_id INT DEFAULT NULL,
    url VARCHAR(500) DEFAULT NULL,
    status ENUM('running', 'stopped', 'error', 'unknown') DEFAULT 'unknown',
    response_time_ms INT DEFAULT NULL,
    cpu_usage_percent DECIMAL(5,2) DEFAULT NULL,
    memory_usage_mb INT DEFAULT NULL,
    error_count INT DEFAULT 0,
    last_check TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (host_id) REFERENCES hosts(id) ON DELETE CASCADE,
    INDEX idx_host (host_id),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- Database Monitoring
CREATE TABLE IF NOT EXISTS monitored_databases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    db_name VARCHAR(100) NOT NULL,
    db_type ENUM('mysql', 'postgresql', 'mssql', 'oracle', 'mongodb', 'redis', 'other') NOT NULL,
    host_id INT DEFAULT NULL,
    connection_string TEXT DEFAULT NULL,
    status ENUM('online', 'offline', 'degraded') DEFAULT 'unknown',
    query_count INT DEFAULT 0,
    slow_queries INT DEFAULT 0,
    connections_active INT DEFAULT 0,
    connections_max INT DEFAULT 0,
    db_size_mb BIGINT DEFAULT 0,
    last_backup TIMESTAMP DEFAULT NULL,
    last_check TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (host_id) REFERENCES hosts(id) ON DELETE CASCADE,
    INDEX idx_type (db_type),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- Insert SolarWinds-style modules
INSERT INTO modules (module_code, module_name, category, description, icon, status, implementation_level, url, display_order) VALUES
-- Network Infrastructure
('NPM', 'Network Performance Monitor', 'network_infrastructure', 'Monitors network health, bandwidth utilization, and device performance in real-time', '🌐', 'active', 'partial', 'modules/npm.php', 1),
('NTA', 'NetFlow Traffic Analyzer', 'network_infrastructure', 'Analyzes network traffic patterns, bandwidth consumption, and top talkers', '📊', 'beta', 'partial', 'modules/nta.php', 2),
('IPAM', 'IP Address Manager', 'network_infrastructure', 'Manages IP addresses, DHCP, DNS configurations and subnet allocation', '🔢', 'active', 'partial', 'modules/ipam.php', 3),
('NCM', 'Network Configuration Manager', 'network_infrastructure', 'Automates network device configuration management and change tracking', '⚙️', 'coming_soon', 'placeholder', 'modules/ncm.php', 4),
('UDT', 'User Device Tracker', 'network_infrastructure', 'Tracks all devices connected to the network with switch port mapping', '📱', 'active', 'partial', 'modules/udt.php', 5),
('VNQM', 'VoIP Quality Manager', 'network_infrastructure', 'Monitors VoIP call quality, MOS scores, jitter, and packet loss', '📞', 'beta', 'partial', 'modules/vnqm.php', 6),

-- Systems & Application Management
('SAM', 'Server & Application Monitor', 'systems_applications', 'Tracks server health, application performance, and resource utilization', '💻', 'active', 'partial', 'modules/sam.php', 7),
('VMAN', 'Virtualization Manager', 'systems_applications', 'Manages and monitors virtual machines, hypervisors, and cloud resources', '☁️', 'coming_soon', 'placeholder', 'modules/vman.php', 8),
('SRM', 'Storage Resource Monitor', 'systems_applications', 'Monitors storage performance, capacity, and IOPS across SAN/NAS', '💾', 'coming_soon', 'placeholder', 'modules/srm.php', 9),
('WPM', 'Web Performance Monitor', 'systems_applications', 'Tracks website and web application performance and availability', '🌍', 'beta', 'partial', 'modules/wpm.php', 10),
('SCM', 'Server Configuration Monitor', 'systems_applications', 'Monitors server configuration changes and drift detection', '🔧', 'coming_soon', 'placeholder', 'modules/scm.php', 11),

-- Database Management
('DPA', 'Database Performance Analyzer', 'database', 'Provides deep insights into database performance bottlenecks and query optimization', '🗄️', 'active', 'partial', 'modules/dpa.php', 12),
('SQL_SENTRY', 'SQL Sentry', 'database', 'Advanced monitoring for SQL Server environments with wait-time analysis', '📈', 'coming_soon', 'placeholder', 'modules/sql_sentry.php', 13),

-- IT Security
('SEM', 'Security Event Manager', 'security', 'Real-time threat detection, SIEM capabilities, and incident response', '🔒', 'active', 'full', 'modules/sem.php', 14),
('ARM', 'Access Rights Manager', 'security', 'Manages user access, permissions, and compliance reporting', '👥', 'active', 'full', 'modules/arm.php', 15),

-- IT Service Management
('DRE', 'Remote Support', 'service_management', 'Comprehensive remote IT support with desktop sharing, file transfer, and system management', '🖥️', 'active', 'full', 'modules/remote_support.php', 16),
('SERVICE_DESK', 'IT Service Desk', 'service_management', 'Full ITSM solution with incidents, problems, changes, assets, knowledge base, and SLA management', '🎫', 'active', 'full', 'service_desk.php', 17),

-- Observability
('OBSERVABILITY', 'SolarWinds Observability', 'observability', 'Unified monitoring for applications, infrastructure, logs, and traces', '👁️', 'beta', 'partial', 'modules/observability.php', 18)
ON DUPLICATE KEY UPDATE module_name = module_name;

-- Sample network devices
INSERT INTO network_devices (device_name, device_type, ip_address, mac_address, manufacturer, status, location) VALUES
('Core-Router-01', 'router', '192.168.1.1', '00:1A:2B:3C:4D:5E', 'Cisco', 'online', 'Server Room'),
('Core-Switch-01', 'switch', '192.168.1.2', '00:1A:2B:3C:4D:5F', 'Cisco', 'online', 'Server Room'),
('Firewall-01', 'firewall', '192.168.1.254', '00:1A:2B:3C:4D:60', 'Fortinet', 'online', 'DMZ'),
('AP-Floor1-01', 'access_point', '192.168.1.10', '00:1A:2B:3C:4D:61', 'Ubiquiti', 'online', 'Floor 1'),
('PBX-Server', 'server', '192.168.1.50', '00:1A:2B:3C:4D:62', 'FreePBX', 'online', 'Telecom Rack')
ON DUPLICATE KEY UPDATE device_name = device_name;

-- Sample IP Address ranges
INSERT INTO ip_addresses (ip_address, subnet, status, description) VALUES
('192.168.1.1', '192.168.1.0/24', 'allocated', 'Gateway Router'),
('192.168.1.2', '192.168.1.0/24', 'allocated', 'Core Switch'),
('192.168.1.10', '192.168.1.0/24', 'allocated', 'Access Point'),
('192.168.1.254', '192.168.1.0/24', 'allocated', 'Firewall'),
('192.168.1.100', '192.168.1.0/24', 'available', 'Available for allocation'),
('192.168.1.101', '192.168.1.0/24', 'available', 'Available for allocation'),
('192.168.1.102', '192.168.1.0/24', 'available', 'Available for allocation')
ON DUPLICATE KEY UPDATE ip_address = ip_address;

-- Sample monitored applications
INSERT INTO monitored_applications (app_name, app_type, url, status, response_time_ms) VALUES
('Company Website', 'web', 'https://company.com', 'running', 250),
('API Server', 'api', 'https://api.company.com', 'running', 120),
('Mail Server', 'email', 'mail.company.com', 'running', 50),
('CRM Application', 'business', 'https://crm.company.com', 'running', 300)
ON DUPLICATE KEY UPDATE app_name = app_name;

-- Sample monitored databases
INSERT INTO monitored_databases (db_name, db_type, status, connections_active, connections_max, db_size_mb) VALUES
('production_db', 'mysql', 'online', 45, 150, 5120),
('analytics_db', 'postgresql', 'online', 12, 50, 2048),
('cache_server', 'redis', 'online', 234, 500, 512)
ON DUPLICATE KEY UPDATE db_name = db_name;
