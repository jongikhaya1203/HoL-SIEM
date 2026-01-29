-- IOC Intelligent Operating Centre Database Schema
-- AI-Powered Network Operations & Performance Management Platform

-- Database Creation
CREATE DATABASE IF NOT EXISTS network_security_scanner CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE network_security_scanner;

-- Scans Table: Track all scanning activities
CREATE TABLE IF NOT EXISTS scans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    scan_name VARCHAR(255) NOT NULL,
    scan_type ENUM('full', 'quick', 'custom', 'compliance', 'vulnerability') DEFAULT 'full',
    target_range VARCHAR(255) NOT NULL COMMENT 'IP range or single IP',
    status ENUM('pending', 'running', 'completed', 'failed', 'paused') DEFAULT 'pending',
    started_at DATETIME DEFAULT NULL,
    completed_at DATETIME DEFAULT NULL,
    total_hosts INT DEFAULT 0,
    total_vulnerabilities INT DEFAULT 0,
    critical_count INT DEFAULT 0,
    high_count INT DEFAULT 0,
    medium_count INT DEFAULT 0,
    low_count INT DEFAULT 0,
    info_count INT DEFAULT 0,
    scan_options JSON COMMENT 'Scan configuration options',
    created_by VARCHAR(100) DEFAULT 'system',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB;

-- Hosts Table: Discovered network devices
CREATE TABLE IF NOT EXISTS hosts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    scan_id INT NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    hostname VARCHAR(255) DEFAULT NULL,
    mac_address VARCHAR(17) DEFAULT NULL,
    os_type VARCHAR(100) DEFAULT NULL,
    os_version VARCHAR(100) DEFAULT NULL,
    device_type VARCHAR(50) DEFAULT 'unknown' COMMENT 'server, workstation, router, switch, etc.',
    status ENUM('online', 'offline', 'filtered') DEFAULT 'online',
    open_ports INT DEFAULT 0,
    risk_score DECIMAL(5,2) DEFAULT 0.00 COMMENT 'Overall risk score 0-10',
    last_seen DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (scan_id) REFERENCES scans(id) ON DELETE CASCADE,
    INDEX idx_ip (ip_address),
    INDEX idx_scan_id (scan_id),
    INDEX idx_risk_score (risk_score)
) ENGINE=InnoDB;

-- Ports Table: Discovered open ports
CREATE TABLE IF NOT EXISTS ports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    host_id INT NOT NULL,
    port_number INT NOT NULL,
    protocol ENUM('tcp', 'udp') DEFAULT 'tcp',
    state ENUM('open', 'closed', 'filtered') DEFAULT 'open',
    service_name VARCHAR(100) DEFAULT NULL,
    service_version VARCHAR(255) DEFAULT NULL,
    service_banner TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (host_id) REFERENCES hosts(id) ON DELETE CASCADE,
    INDEX idx_host_port (host_id, port_number),
    INDEX idx_service (service_name)
) ENGINE=InnoDB;

-- Vulnerabilities Database: Known CVEs and vulnerabilities
CREATE TABLE IF NOT EXISTS vulnerabilities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cve_id VARCHAR(20) UNIQUE DEFAULT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    severity ENUM('critical', 'high', 'medium', 'low', 'info') NOT NULL,
    cvss_score DECIMAL(3,1) DEFAULT NULL COMMENT 'CVSS v3 Score 0.0-10.0',
    cvss_vector VARCHAR(100) DEFAULT NULL,
    affected_services TEXT COMMENT 'Comma-separated list of affected services',
    affected_versions TEXT COMMENT 'Affected version patterns',
    published_date DATE DEFAULT NULL,
    external_references TEXT COMMENT 'External references and links',
    cwe_id VARCHAR(20) DEFAULT NULL COMMENT 'Common Weakness Enumeration',
    exploit_available BOOLEAN DEFAULT FALSE,
    patch_available BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_cve (cve_id),
    INDEX idx_severity (severity),
    INDEX idx_cvss (cvss_score)
) ENGINE=InnoDB;

-- Scan Results: Vulnerabilities found during scans
CREATE TABLE IF NOT EXISTS scan_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    scan_id INT NOT NULL,
    host_id INT NOT NULL,
    port_id INT DEFAULT NULL,
    vulnerability_id INT NOT NULL,
    status ENUM('open', 'confirmed', 'false_positive', 'mitigated', 'accepted') DEFAULT 'open',
    evidence TEXT COMMENT 'Proof of vulnerability',
    exploit_complexity ENUM('low', 'medium', 'high') DEFAULT 'medium',
    business_impact TEXT,
    detected_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    resolved_at DATETIME DEFAULT NULL,
    FOREIGN KEY (scan_id) REFERENCES scans(id) ON DELETE CASCADE,
    FOREIGN KEY (host_id) REFERENCES hosts(id) ON DELETE CASCADE,
    FOREIGN KEY (port_id) REFERENCES ports(id) ON DELETE SET NULL,
    FOREIGN KEY (vulnerability_id) REFERENCES vulnerabilities(id) ON DELETE CASCADE,
    INDEX idx_scan (scan_id),
    INDEX idx_host (host_id),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- Mitigation Plans: Remediation recommendations
CREATE TABLE IF NOT EXISTS mitigation_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vulnerability_id INT NOT NULL,
    priority INT DEFAULT 1 COMMENT '1=highest, 5=lowest',
    mitigation_title VARCHAR(255) NOT NULL,
    mitigation_steps TEXT NOT NULL,
    estimated_effort VARCHAR(50) DEFAULT NULL COMMENT 'hours, days, weeks',
    required_skills VARCHAR(255) DEFAULT NULL,
    patch_url VARCHAR(500) DEFAULT NULL,
    workaround TEXT DEFAULT NULL,
    verification_steps TEXT DEFAULT NULL,
    compliance_frameworks VARCHAR(255) DEFAULT NULL COMMENT 'Related compliance frameworks',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vulnerability_id) REFERENCES vulnerabilities(id) ON DELETE CASCADE,
    INDEX idx_vulnerability (vulnerability_id),
    INDEX idx_priority (priority)
) ENGINE=InnoDB;

-- Reports: Generated assessment reports
CREATE TABLE IF NOT EXISTS reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    scan_id INT NOT NULL,
    report_type ENUM('executive', 'technical', 'compliance', 'full') DEFAULT 'full',
    report_format ENUM('html', 'pdf', 'json', 'csv') DEFAULT 'pdf',
    title VARCHAR(255) NOT NULL,
    summary TEXT,
    total_vulnerabilities INT DEFAULT 0,
    total_hosts INT DEFAULT 0,
    overall_risk_score DECIMAL(5,2) DEFAULT 0.00,
    compliance_status ENUM('compliant', 'non_compliant', 'partial', 'not_assessed') DEFAULT 'not_assessed',
    file_path VARCHAR(500) DEFAULT NULL,
    file_size INT DEFAULT NULL COMMENT 'File size in bytes',
    generated_by VARCHAR(100) DEFAULT 'system',
    generated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_scan (scan_id),
    FOREIGN KEY (scan_id) REFERENCES scans(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Compliance Frameworks: Security standards and frameworks
CREATE TABLE IF NOT EXISTS compliance_frameworks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    version VARCHAR(20) DEFAULT NULL,
    description TEXT,
    category VARCHAR(50) DEFAULT NULL COMMENT 'regulatory, industry, best-practice',
    total_controls INT DEFAULT 0,
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Compliance Controls: Individual controls within frameworks
CREATE TABLE IF NOT EXISTS compliance_controls (
    id INT AUTO_INCREMENT PRIMARY KEY,
    framework_id INT NOT NULL,
    control_id VARCHAR(50) NOT NULL,
    control_name VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(100) DEFAULT NULL,
    test_procedure TEXT,
    FOREIGN KEY (framework_id) REFERENCES compliance_frameworks(id) ON DELETE CASCADE,
    INDEX idx_framework (framework_id),
    UNIQUE KEY unique_control (framework_id, control_id)
) ENGINE=InnoDB;

-- Compliance Checks: Compliance assessment results
CREATE TABLE IF NOT EXISTS compliance_checks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    scan_id INT NOT NULL,
    control_id INT NOT NULL,
    status ENUM('pass', 'fail', 'partial', 'not_applicable', 'not_tested') DEFAULT 'not_tested',
    evidence TEXT,
    findings TEXT,
    recommendations TEXT,
    tested_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (scan_id) REFERENCES scans(id) ON DELETE CASCADE,
    FOREIGN KEY (control_id) REFERENCES compliance_controls(id) ON DELETE CASCADE,
    INDEX idx_scan (scan_id),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- Scheduled Scans: Automated recurring scans
CREATE TABLE IF NOT EXISTS scheduled_scans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    target_range VARCHAR(255) NOT NULL,
    scan_type ENUM('full', 'quick', 'custom', 'compliance', 'vulnerability') DEFAULT 'full',
    schedule_pattern VARCHAR(100) NOT NULL COMMENT 'Cron-like pattern',
    scan_options JSON,
    active BOOLEAN DEFAULT TRUE,
    last_run_at DATETIME DEFAULT NULL,
    next_run_at DATETIME DEFAULT NULL,
    created_by VARCHAR(100) DEFAULT 'system',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_next_run (next_run_at, active)
) ENGINE=InnoDB;

-- Audit Log: Track all system activities
CREATE TABLE IF NOT EXISTS audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user VARCHAR(100) DEFAULT 'system',
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50) DEFAULT NULL,
    entity_id INT DEFAULT NULL,
    details TEXT,
    ip_address VARCHAR(45) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_created_at (created_at),
    INDEX idx_user (user)
) ENGINE=InnoDB;

-- Network Segments: Define network zones for better organization
CREATE TABLE IF NOT EXISTS network_segments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    cidr_range VARCHAR(50) NOT NULL,
    description TEXT,
    criticality ENUM('critical', 'high', 'medium', 'low') DEFAULT 'medium',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Insert default compliance frameworks
INSERT INTO compliance_frameworks (name, version, description, category, total_controls) VALUES
('NIST CSF', '1.1', 'NIST Cybersecurity Framework', 'best-practice', 23),
('ISO 27001', '2013', 'Information Security Management', 'industry', 114),
('CIS Controls', 'v8', 'Center for Internet Security Controls', 'best-practice', 18),
('PCI DSS', 'v4.0', 'Payment Card Industry Data Security Standard', 'regulatory', 12),
('HIPAA', '2013', 'Health Insurance Portability and Accountability Act', 'regulatory', 45),
('SOC 2', 'Type II', 'Service Organization Control 2', 'industry', 64);

-- Insert sample vulnerability data
INSERT INTO vulnerabilities (cve_id, title, description, severity, cvss_score, cvss_vector, affected_services, published_date, cwe_id) VALUES
('CVE-2024-1086', 'Linux Kernel Use-After-Free Vulnerability', 'A use-after-free vulnerability in the Linux kernel netfilter subsystem allows local privilege escalation', 'critical', 9.8, 'CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H', 'linux-kernel', '2024-01-31', 'CWE-416'),
('CVE-2024-3094', 'XZ Utils Backdoor', 'Malicious backdoor in XZ Utils liblzma library allowing remote code execution', 'critical', 10.0, 'CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:C/C:H/I:H/A:H', 'xz-utils,liblzma', '2024-03-29', 'CWE-506'),
('CVE-2023-44487', 'HTTP/2 Rapid Reset Attack', 'HTTP/2 protocol vulnerability allowing DDoS attacks', 'high', 7.5, 'CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:N/I:N/A:H', 'http2,nginx,apache', '2023-10-10', 'CWE-400'),
('CVE-2023-23397', 'Microsoft Outlook Elevation of Privilege', 'Outlook vulnerability allowing NTLM hash theft', 'critical', 9.8, 'CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H', 'outlook,exchange', '2023-03-14', 'CWE-294');

-- Insert mitigation plans for sample vulnerabilities
INSERT INTO mitigation_plans (vulnerability_id, priority, mitigation_title, mitigation_steps, estimated_effort, patch_url) VALUES
(1, 1, 'Update Linux Kernel', '1. Backup system\n2. Update to kernel version 6.7.4 or later\n3. Reboot system\n4. Verify kernel version\n5. Test critical services', '2-4 hours', 'https://kernel.org'),
(2, 1, 'Update XZ Utils', '1. Identify affected systems\n2. Update xz-utils to version 5.6.2 or later\n3. Verify integrity of liblzma\n4. Audit for compromise indicators\n5. Monitor for suspicious activity', '1-2 hours', 'https://github.com/tukaani-project/xz'),
(3, 1, 'Apply HTTP/2 Security Patches', '1. Update web server to latest version\n2. Configure HTTP/2 rate limiting\n3. Implement connection limits\n4. Enable logging for anomaly detection', '2-3 hours', NULL),
(4, 1, 'Patch Microsoft Outlook', '1. Deploy Microsoft security update KB5023403\n2. Restart Outlook clients\n3. Add firewall rules to block SMB\n4. Monitor for NTLM authentication attempts', '1-2 hours', 'https://msrc.microsoft.com/update-guide/');
