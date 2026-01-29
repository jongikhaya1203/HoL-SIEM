-- CMS Portal Additional Tables
-- Add these tables to the existing network_security_scanner database

USE network_security_scanner;

-- Settings Table: Store application configuration
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('string', 'integer', 'boolean', 'json') DEFAULT 'string',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_key (setting_key)
) ENGINE=InnoDB;

-- Tasks Table: To-do list for network administrators
CREATE TABLE IF NOT EXISTS tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    priority ENUM('critical', 'high', 'medium', 'low') DEFAULT 'medium',
    status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    category VARCHAR(50) DEFAULT 'general',
    assigned_to VARCHAR(100) DEFAULT NULL,
    due_date DATE DEFAULT NULL,
    scan_id INT DEFAULT NULL COMMENT 'Related scan if applicable',
    host_id INT DEFAULT NULL COMMENT 'Related host if applicable',
    vulnerability_id INT DEFAULT NULL COMMENT 'Related vulnerability if applicable',
    created_by VARCHAR(100) DEFAULT 'system',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    completed_at DATETIME DEFAULT NULL,
    FOREIGN KEY (scan_id) REFERENCES scans(id) ON DELETE SET NULL,
    FOREIGN KEY (host_id) REFERENCES hosts(id) ON DELETE SET NULL,
    FOREIGN KEY (vulnerability_id) REFERENCES vulnerabilities(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_due_date (due_date),
    INDEX idx_assigned (assigned_to)
) ENGINE=InnoDB;

-- Task Comments: Track task updates and discussions
CREATE TABLE IF NOT EXISTS task_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    comment TEXT NOT NULL,
    created_by VARCHAR(100) DEFAULT 'system',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    INDEX idx_task (task_id)
) ENGINE=InnoDB;

-- Admin Users: CMS user management
CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(255) DEFAULT NULL,
    full_name VARCHAR(100) DEFAULT NULL,
    role ENUM('admin', 'analyst', 'viewer') DEFAULT 'viewer',
    active BOOLEAN DEFAULT TRUE,
    last_login DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_active (active)
) ENGINE=InnoDB;

-- Insert default settings
INSERT INTO settings (setting_key, setting_value, setting_type, description) VALUES
('app_name', 'Network Security Scanner', 'string', 'Application name displayed in header'),
('company_name', '', 'string', 'Company name'),
('support_email', '', 'string', 'Support contact email'),
('theme_color', '#667eea', 'string', 'Primary theme color'),
('logo_url', '', 'string', 'Path to uploaded logo'),
('enable_auto_scan', 'false', 'boolean', 'Enable automatic scheduled scanning'),
('scan_interval', '24', 'integer', 'Hours between automatic scans'),
('retention_days', '90', 'integer', 'Days to retain scan data'),
('alert_critical', 'true', 'boolean', 'Send alerts for critical vulnerabilities'),
('alert_email', '', 'string', 'Email for vulnerability alerts')
ON DUPLICATE KEY UPDATE setting_value = setting_value;

-- Insert default admin user (username: admin, password: admin123)
INSERT INTO admin_users (username, password_hash, email, full_name, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@example.com', 'System Administrator', 'admin')
ON DUPLICATE KEY UPDATE username = username;

-- Insert sample tasks for SolarWinds benchmark
INSERT INTO tasks (title, description, priority, status, category, due_date) VALUES
('Implement Real-Time Network Monitoring', 'Add continuous network monitoring similar to SolarWinds NPM. Monitor bandwidth, latency, and packet loss in real-time.', 'high', 'pending', 'enhancement', DATE_ADD(CURDATE(), INTERVAL 30 DAY)),

('Network Performance Baselines', 'Establish baseline metrics for network performance to detect anomalies, similar to SolarWinds PerfStack.', 'high', 'pending', 'enhancement', DATE_ADD(CURDATE(), INTERVAL 45 DAY)),

('Add Network Device Discovery', 'Implement auto-discovery of routers, switches, firewalls similar to SolarWinds Network Discovery.', 'high', 'pending', 'feature', DATE_ADD(CURDATE(), INTERVAL 60 DAY)),

('SNMP Monitoring Integration', 'Add SNMP v2/v3 support for monitoring network devices like SolarWinds does.', 'critical', 'pending', 'feature', DATE_ADD(CURDATE(), INTERVAL 30 DAY)),

('Network Topology Visualization', 'Create interactive network topology maps like SolarWinds Network Topology Mapper.', 'medium', 'pending', 'feature', DATE_ADD(CURDATE(), INTERVAL 90 DAY)),

('NetFlow/sFlow Analysis', 'Implement flow data collection and analysis for traffic patterns like SolarWinds NetFlow Traffic Analyzer.', 'high', 'pending', 'feature', DATE_ADD(CURDATE(), INTERVAL 60 DAY)),

('Alerting and Notification System', 'Build advanced alerting with multiple channels (email, SMS, webhooks) like SolarWinds Alert Manager.', 'critical', 'pending', 'feature', DATE_ADD(CURDATE(), INTERVAL 30 DAY)),

('Configuration Management', 'Track and backup network device configurations similar to SolarWinds Network Configuration Manager.', 'high', 'pending', 'feature', DATE_ADD(CURDATE(), INTERVAL 90 DAY)),

('VoIP Monitoring', 'Add VoIP quality monitoring (jitter, MOS scores) like SolarWinds VoIP & Network Quality Manager.', 'medium', 'pending', 'enhancement', DATE_ADD(CURDATE(), INTERVAL 120 DAY)),

('Log Management Integration', 'Integrate with syslog and log aggregation similar to SolarWinds Log Analyzer.', 'medium', 'pending', 'integration', DATE_ADD(CURDATE(), INTERVAL 90 DAY)),

('Custom Dashboard Widgets', 'Allow users to create custom dashboard widgets like SolarWinds Orion Platform.', 'medium', 'pending', 'enhancement', DATE_ADD(CURDATE(), INTERVAL 60 DAY)),

('Mobile App Development', 'Develop mobile applications for iOS/Android like SolarWinds Mobile Admin.', 'low', 'pending', 'enhancement', DATE_ADD(CURDATE(), INTERVAL 180 DAY)),

('API Rate Limiting', 'Implement API rate limiting and throttling for better resource management.', 'medium', 'pending', 'security', DATE_ADD(CURDATE(), INTERVAL 45 DAY)),

('Multi-Tenant Support', 'Add support for multiple organizations/customers in single installation.', 'low', 'pending', 'feature', DATE_ADD(CURDATE(), INTERVAL 180 DAY)),

('Automated Remediation', 'Implement automated response actions for common vulnerabilities.', 'high', 'pending', 'security', DATE_ADD(CURDATE(), INTERVAL 90 DAY))
ON DUPLICATE KEY UPDATE title = title;
