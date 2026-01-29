<?php
/**
 * NCM & VNQM Database Setup Script
 * Run this once to create all required tables
 */

require_once __DIR__ . '/classes/Database.php';

$db = Database::getInstance();
$pdo = $db->getConnection();

echo "<h1>NCM & VNQM Database Setup</h1>";
echo "<pre>";

try {
    // Disable foreign key checks for dropping tables
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    echo "Foreign key checks disabled\n";

    // =====================
    // NCM TABLES
    // =====================
    echo "\n=== Creating NCM Tables ===\n\n";

    // Drop NCM tables
    $ncmTables = [
        'ncm_scheduled_jobs',
        'ncm_compliance_results',
        'ncm_compliance_rules',
        'ncm_config_changes',
        'ncm_config_backups',
        'ncm_config_templates',
        'ncm_device_configs',
        'ncm_devices'
    ];

    foreach ($ncmTables as $table) {
        $pdo->exec("DROP TABLE IF EXISTS $table");
        echo "Dropped table: $table\n";
    }

    // NCM Devices
    $pdo->exec("CREATE TABLE ncm_devices (
        id INT AUTO_INCREMENT PRIMARY KEY,
        device_name VARCHAR(255) NOT NULL,
        ip_address VARCHAR(45) NOT NULL,
        device_type ENUM('router', 'switch', 'firewall', 'access_point', 'load_balancer', 'wireless_controller') NOT NULL,
        vendor VARCHAR(100) DEFAULT NULL,
        model VARCHAR(100) DEFAULT NULL,
        firmware_version VARCHAR(50) DEFAULT NULL,
        serial_number VARCHAR(100) DEFAULT NULL,
        location VARCHAR(255) DEFAULT NULL,
        status ENUM('active', 'inactive', 'maintenance') DEFAULT 'active',
        compliance_status ENUM('compliant', 'non_compliant', 'unknown') DEFAULT 'unknown',
        last_backup DATETIME DEFAULT NULL,
        last_compliance_check DATETIME DEFAULT NULL,
        ssh_port INT DEFAULT 22,
        snmp_community VARCHAR(100) DEFAULT NULL,
        credentials_id INT DEFAULT NULL,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_ip (ip_address),
        INDEX idx_type (device_type),
        INDEX idx_status (status),
        INDEX idx_compliance (compliance_status)
    ) ENGINE=InnoDB");
    echo "Created table: ncm_devices\n";

    // Device Configs
    $pdo->exec("CREATE TABLE ncm_device_configs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        device_id INT NOT NULL,
        config_type ENUM('running', 'startup', 'backup') DEFAULT 'running',
        config_content LONGTEXT NOT NULL,
        version INT DEFAULT 1,
        file_size INT DEFAULT 0,
        checksum VARCHAR(64) DEFAULT NULL,
        last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (device_id) REFERENCES ncm_devices(id) ON DELETE CASCADE,
        INDEX idx_device (device_id),
        INDEX idx_type (config_type)
    ) ENGINE=InnoDB");
    echo "Created table: ncm_device_configs\n";

    // Config Templates
    $pdo->exec("CREATE TABLE ncm_config_templates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        template_name VARCHAR(255) NOT NULL,
        device_type VARCHAR(50) NOT NULL,
        vendor VARCHAR(100) DEFAULT NULL,
        template_content LONGTEXT NOT NULL,
        description TEXT,
        variables JSON DEFAULT NULL,
        created_by VARCHAR(100) DEFAULT 'system',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_device_type (device_type)
    ) ENGINE=InnoDB");
    echo "Created table: ncm_config_templates\n";

    // Config Backups
    $pdo->exec("CREATE TABLE ncm_config_backups (
        id INT AUTO_INCREMENT PRIMARY KEY,
        device_id INT NOT NULL,
        config_content LONGTEXT NOT NULL,
        backup_type ENUM('manual', 'scheduled', 'change_triggered', 'pre_change') DEFAULT 'manual',
        file_size INT DEFAULT 0,
        checksum VARCHAR(64) DEFAULT NULL,
        backup_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        retention_days INT DEFAULT 90,
        notes TEXT,
        FOREIGN KEY (device_id) REFERENCES ncm_devices(id) ON DELETE CASCADE,
        INDEX idx_device (device_id),
        INDEX idx_backup_time (backup_time)
    ) ENGINE=InnoDB");
    echo "Created table: ncm_config_backups\n";

    // Config Changes
    $pdo->exec("CREATE TABLE ncm_config_changes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        device_id INT NOT NULL,
        change_type ENUM('manual', 'template_apply', 'bulk_change', 'remediation', 'rollback') NOT NULL,
        change_description TEXT NOT NULL,
        config_before LONGTEXT,
        config_after LONGTEXT,
        lines_added INT DEFAULT 0,
        lines_removed INT DEFAULT 0,
        status ENUM('pending', 'approved', 'rejected', 'applied', 'failed', 'rolled_back') DEFAULT 'pending',
        requested_by VARCHAR(100) NOT NULL,
        approved_by VARCHAR(100) DEFAULT NULL,
        approved_at DATETIME DEFAULT NULL,
        applied_at DATETIME DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (device_id) REFERENCES ncm_devices(id) ON DELETE CASCADE,
        INDEX idx_device (device_id),
        INDEX idx_status (status),
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB");
    echo "Created table: ncm_config_changes\n";

    // Compliance Rules
    $pdo->exec("CREATE TABLE ncm_compliance_rules (
        id INT AUTO_INCREMENT PRIMARY KEY,
        rule_name VARCHAR(255) NOT NULL,
        description TEXT,
        device_type VARCHAR(50) DEFAULT 'all',
        rule_type ENUM('must_contain', 'must_not_contain', 'regex_match', 'regex_not_match') NOT NULL,
        rule_pattern TEXT NOT NULL,
        severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
        remediation_template TEXT,
        enabled BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_device_type (device_type),
        INDEX idx_severity (severity)
    ) ENGINE=InnoDB");
    echo "Created table: ncm_compliance_rules\n";

    // Compliance Results
    $pdo->exec("CREATE TABLE ncm_compliance_results (
        id INT AUTO_INCREMENT PRIMARY KEY,
        device_id INT NOT NULL,
        rule_id INT NOT NULL,
        check_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        result ENUM('pass', 'fail', 'error') NOT NULL,
        details TEXT,
        FOREIGN KEY (device_id) REFERENCES ncm_devices(id) ON DELETE CASCADE,
        FOREIGN KEY (rule_id) REFERENCES ncm_compliance_rules(id) ON DELETE CASCADE,
        INDEX idx_device (device_id),
        INDEX idx_check_time (check_time)
    ) ENGINE=InnoDB");
    echo "Created table: ncm_compliance_results\n";

    // Scheduled Jobs
    $pdo->exec("CREATE TABLE ncm_scheduled_jobs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        job_name VARCHAR(255) NOT NULL,
        job_type ENUM('backup', 'compliance_check', 'config_push', 'report') NOT NULL,
        schedule_cron VARCHAR(100) NOT NULL,
        target_devices JSON DEFAULT NULL,
        job_config JSON DEFAULT NULL,
        status ENUM('active', 'paused', 'disabled') DEFAULT 'active',
        last_run DATETIME DEFAULT NULL,
        next_run DATETIME DEFAULT NULL,
        last_result ENUM('success', 'partial', 'failed') DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_status (status),
        INDEX idx_next_run (next_run)
    ) ENGINE=InnoDB");
    echo "Created table: ncm_scheduled_jobs\n";

    // =====================
    // VNQM TABLES (Enhanced)
    // =====================
    echo "\n=== Creating VNQM Tables ===\n\n";

    // Drop VNQM tables (child tables first due to foreign keys)
    $vnqmTables = [
        'vnqm_alerts',
        'vnqm_codec_stats',
        'vnqm_call_details',  // Must be dropped before vnqm_sip_trunks and vnqm_endpoints (has FK references)
        'vnqm_sip_trunks',
        'vnqm_endpoints'
    ];

    foreach ($vnqmTables as $table) {
        $pdo->exec("DROP TABLE IF EXISTS $table");
        echo "Dropped table: $table\n";
    }

    // Re-enable foreign key checks before creating tables
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "Foreign key checks re-enabled\n\n";

    // VoIP Endpoints
    $pdo->exec("CREATE TABLE vnqm_endpoints (
        id INT AUTO_INCREMENT PRIMARY KEY,
        endpoint_name VARCHAR(255) NOT NULL,
        endpoint_type ENUM('ip_phone', 'softphone', 'conference', 'gateway', 'sbc', 'pbx') NOT NULL,
        ip_address VARCHAR(45) NOT NULL,
        mac_address VARCHAR(17) DEFAULT NULL,
        extension VARCHAR(20) DEFAULT NULL,
        location VARCHAR(255) DEFAULT NULL,
        vendor VARCHAR(100) DEFAULT NULL,
        model VARCHAR(100) DEFAULT NULL,
        firmware VARCHAR(50) DEFAULT NULL,
        status ENUM('registered', 'unregistered', 'offline', 'busy') DEFAULT 'offline',
        last_registration DATETIME DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_ip (ip_address),
        INDEX idx_extension (extension),
        INDEX idx_status (status)
    ) ENGINE=InnoDB");
    echo "Created table: vnqm_endpoints\n";

    // SIP Trunks
    $pdo->exec("CREATE TABLE vnqm_sip_trunks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        trunk_name VARCHAR(255) NOT NULL,
        provider VARCHAR(255) DEFAULT NULL,
        primary_ip VARCHAR(45) NOT NULL,
        secondary_ip VARCHAR(45) DEFAULT NULL,
        channels INT DEFAULT 0,
        channels_in_use INT DEFAULT 0,
        status ENUM('up', 'down', 'degraded') DEFAULT 'down',
        last_status_change DATETIME DEFAULT NULL,
        total_calls_today INT DEFAULT 0,
        failed_calls_today INT DEFAULT 0,
        avg_mos_today DECIMAL(3,2) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_status (status)
    ) ENGINE=InnoDB");
    echo "Created table: vnqm_sip_trunks\n";

    // Call Details (Enhanced)
    $pdo->exec("CREATE TABLE vnqm_call_details (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        call_id VARCHAR(100) NOT NULL,
        trunk_id INT DEFAULT NULL,
        source_endpoint_id INT DEFAULT NULL,
        dest_endpoint_id INT DEFAULT NULL,
        caller_number VARCHAR(50) NOT NULL,
        callee_number VARCHAR(50) NOT NULL,
        call_direction ENUM('inbound', 'outbound', 'internal') DEFAULT 'internal',
        call_start DATETIME NOT NULL,
        call_answer DATETIME DEFAULT NULL,
        call_end DATETIME DEFAULT NULL,
        duration INT DEFAULT 0,
        ring_time INT DEFAULT 0,
        codec VARCHAR(20) DEFAULT NULL,
        mos_score DECIMAL(3,2) DEFAULT NULL,
        r_factor DECIMAL(4,1) DEFAULT NULL,
        jitter_ms INT DEFAULT NULL,
        jitter_max_ms INT DEFAULT NULL,
        packet_loss_percent DECIMAL(5,2) DEFAULT NULL,
        latency_ms INT DEFAULT NULL,
        quality_rating ENUM('excellent', 'good', 'fair', 'poor') DEFAULT NULL,
        hangup_cause VARCHAR(100) DEFAULT NULL,
        recording_path VARCHAR(500) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (trunk_id) REFERENCES vnqm_sip_trunks(id) ON DELETE SET NULL,
        FOREIGN KEY (source_endpoint_id) REFERENCES vnqm_endpoints(id) ON DELETE SET NULL,
        FOREIGN KEY (dest_endpoint_id) REFERENCES vnqm_endpoints(id) ON DELETE SET NULL,
        INDEX idx_call_id (call_id),
        INDEX idx_start (call_start),
        INDEX idx_quality (quality_rating),
        INDEX idx_caller (caller_number),
        INDEX idx_callee (callee_number)
    ) ENGINE=InnoDB");
    echo "Created table: vnqm_call_details\n";

    // Codec Statistics
    $pdo->exec("CREATE TABLE vnqm_codec_stats (
        id INT AUTO_INCREMENT PRIMARY KEY,
        codec_name VARCHAR(50) NOT NULL,
        total_calls INT DEFAULT 0,
        avg_mos DECIMAL(3,2) DEFAULT NULL,
        avg_jitter INT DEFAULT NULL,
        avg_packet_loss DECIMAL(5,2) DEFAULT NULL,
        bandwidth_kbps INT DEFAULT NULL,
        stat_date DATE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_codec_date (codec_name, stat_date),
        INDEX idx_date (stat_date)
    ) ENGINE=InnoDB");
    echo "Created table: vnqm_codec_stats\n";

    // VoIP Alerts
    $pdo->exec("CREATE TABLE vnqm_alerts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        alert_type ENUM('quality_degradation', 'trunk_down', 'high_packet_loss', 'high_jitter', 'endpoint_offline', 'capacity_warning') NOT NULL,
        severity ENUM('info', 'warning', 'critical') NOT NULL,
        source_type ENUM('trunk', 'endpoint', 'call', 'system') NOT NULL,
        source_id INT DEFAULT NULL,
        message TEXT NOT NULL,
        details JSON DEFAULT NULL,
        status ENUM('active', 'acknowledged', 'resolved') DEFAULT 'active',
        acknowledged_by VARCHAR(100) DEFAULT NULL,
        acknowledged_at DATETIME DEFAULT NULL,
        resolved_at DATETIME DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_type (alert_type),
        INDEX idx_severity (severity),
        INDEX idx_status (status),
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB");
    echo "Created table: vnqm_alerts\n";

    // =====================
    // INSERT SAMPLE DATA
    // =====================
    echo "\n=== Inserting Sample Data ===\n\n";

    // NCM Devices
    $pdo->exec("INSERT INTO ncm_devices (device_name, ip_address, device_type, vendor, model, firmware_version, location, status, compliance_status) VALUES
        ('Core-Router-01', '192.168.1.1', 'router', 'Cisco', 'ISR 4451', '16.12.4', 'Data Center Rack A1', 'active', 'compliant'),
        ('Core-Router-02', '192.168.1.2', 'router', 'Cisco', 'ISR 4451', '16.12.4', 'Data Center Rack A2', 'active', 'compliant'),
        ('Core-Switch-01', '192.168.1.10', 'switch', 'Cisco', 'Catalyst 9500', '17.3.2', 'Data Center Rack B1', 'active', 'compliant'),
        ('Core-Switch-02', '192.168.1.11', 'switch', 'Cisco', 'Catalyst 9500', '17.3.2', 'Data Center Rack B2', 'active', 'non_compliant'),
        ('Dist-Switch-01', '192.168.2.1', 'switch', 'Cisco', 'Catalyst 9300', '17.3.2', 'Floor 1 IDF', 'active', 'compliant'),
        ('Dist-Switch-02', '192.168.2.2', 'switch', 'Cisco', 'Catalyst 9300', '17.3.2', 'Floor 2 IDF', 'active', 'compliant'),
        ('Firewall-01', '10.0.0.1', 'firewall', 'Fortinet', 'FortiGate 600E', '7.0.5', 'Data Center Rack C1', 'active', 'compliant'),
        ('Firewall-02', '10.0.0.2', 'firewall', 'Palo Alto', 'PA-3260', '10.2.3', 'Data Center Rack C2', 'active', 'non_compliant'),
        ('WLC-01', '192.168.1.50', 'wireless_controller', 'Cisco', 'C9800-40', '17.6.1', 'Data Center Rack D1', 'active', 'compliant'),
        ('LB-01', '192.168.1.100', 'load_balancer', 'F5', 'BIG-IP i5800', '16.1.2', 'Data Center Rack E1', 'active', 'unknown')");
    echo "Inserted NCM devices\n";

    // NCM Config Templates
    $pdo->exec("INSERT INTO ncm_config_templates (template_name, device_type, vendor, template_content, description) VALUES
        ('Cisco Router Base Config', 'router', 'Cisco', '!\nhostname {{HOSTNAME}}\n!\nenable secret {{ENABLE_SECRET}}\n!\nservice password-encryption\nservice timestamps debug datetime msec\nservice timestamps log datetime msec\n!\nip domain-name {{DOMAIN}}\n!\ninterface GigabitEthernet0/0\n ip address {{MGMT_IP}} {{SUBNET_MASK}}\n no shutdown\n!\nip route 0.0.0.0 0.0.0.0 {{DEFAULT_GW}}\n!\nline con 0\n logging synchronous\nline vty 0 15\n transport input ssh\n!\nend', 'Standard base configuration for Cisco routers'),
        ('Cisco Switch VLAN Config', 'switch', 'Cisco', '!\nvlan {{VLAN_ID}}\n name {{VLAN_NAME}}\n!\ninterface {{INTERFACE}}\n switchport mode access\n switchport access vlan {{VLAN_ID}}\n spanning-tree portfast\n!\nend', 'VLAN configuration template for Cisco switches'),
        ('Fortinet Firewall Policy', 'firewall', 'Fortinet', 'config firewall policy\n edit {{POLICY_ID}}\n  set name \"{{POLICY_NAME}}\"\n  set srcintf \"{{SRC_INTF}}\"\n  set dstintf \"{{DST_INTF}}\"\n  set srcaddr \"{{SRC_ADDR}}\"\n  set dstaddr \"{{DST_ADDR}}\"\n  set action accept\n  set schedule \"always\"\n  set service \"{{SERVICE}}\"\n  set logtraffic all\n next\nend', 'Firewall policy template for FortiGate')");
    echo "Inserted NCM templates\n";

    // NCM Compliance Rules
    $pdo->exec("INSERT INTO ncm_compliance_rules (rule_name, description, device_type, rule_type, rule_pattern, severity) VALUES
        ('Password Encryption', 'Ensure password encryption is enabled', 'all', 'must_contain', 'service password-encryption', 'high'),
        ('SSH Only Access', 'Ensure VTY lines only allow SSH', 'all', 'must_contain', 'transport input ssh', 'critical'),
        ('No Telnet', 'Ensure telnet is disabled', 'all', 'must_not_contain', 'transport input telnet', 'critical'),
        ('Enable Secret Set', 'Ensure enable secret is configured', 'router', 'must_contain', 'enable secret', 'high'),
        ('SNMP v3 Only', 'Ensure only SNMP v3 is used', 'all', 'must_not_contain', 'snmp-server community', 'medium'),
        ('Logging Enabled', 'Ensure logging is configured', 'all', 'must_contain', 'logging', 'medium'),
        ('Banner Configured', 'Ensure login banner is set', 'all', 'must_contain', 'banner', 'low'),
        ('NTP Configured', 'Ensure NTP is configured', 'all', 'must_contain', 'ntp server', 'medium')");
    echo "Inserted NCM compliance rules\n";

    // NCM Scheduled Jobs
    $pdo->exec("INSERT INTO ncm_scheduled_jobs (job_name, job_type, schedule_cron, status, next_run) VALUES
        ('Daily Config Backup', 'backup', '0 2 * * *', 'active', DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL 2 HOUR),
        ('Weekly Compliance Check', 'compliance_check', '0 6 * * 0', 'active', DATE_ADD(CURDATE(), INTERVAL (7 - WEEKDAY(CURDATE())) DAY) + INTERVAL 6 HOUR),
        ('Monthly Config Report', 'report', '0 8 1 * *', 'active', DATE_ADD(DATE_ADD(LAST_DAY(CURDATE()), INTERVAL 1 DAY), INTERVAL 8 HOUR))");
    echo "Inserted NCM scheduled jobs\n";

    // Sample config backups
    $sampleConfig = "!\n! Last configuration change at " . date('H:i:s') . " UTC " . date('D M d Y') . "\n!\nversion 16.12\nservice timestamps debug datetime msec\nservice timestamps log datetime msec\nservice password-encryption\n!\nhostname Core-Router-01\n!\nenable secret 9 \$9\$encrypted\$\n!\nip domain-name company.local\n!\ninterface GigabitEthernet0/0\n description Management Interface\n ip address 192.168.1.1 255.255.255.0\n no shutdown\n!\ninterface GigabitEthernet0/1\n description WAN Link\n ip address 10.0.0.1 255.255.255.252\n no shutdown\n!\nip route 0.0.0.0 0.0.0.0 10.0.0.2\n!\nlogging buffered 16384\nlogging console critical\n!\nntp server 10.10.10.1\n!\nbanner motd ^C\n*** AUTHORIZED ACCESS ONLY ***\n^C\n!\nline con 0\n logging synchronous\nline vty 0 15\n transport input ssh\n!\nend";

    $pdo->exec("INSERT INTO ncm_config_backups (device_id, config_content, backup_type, file_size, backup_time) VALUES
        (1, '" . addslashes($sampleConfig) . "', 'scheduled', " . strlen($sampleConfig) . ", DATE_SUB(NOW(), INTERVAL 2 HOUR)),
        (1, '" . addslashes($sampleConfig) . "', 'scheduled', " . strlen($sampleConfig) . ", DATE_SUB(NOW(), INTERVAL 26 HOUR)),
        (3, '" . addslashes($sampleConfig) . "', 'manual', " . strlen($sampleConfig) . ", DATE_SUB(NOW(), INTERVAL 4 HOUR))");
    echo "Inserted NCM config backups\n";

    // NCM Config Changes
    $pdo->exec("INSERT INTO ncm_config_changes (device_id, change_type, change_description, config_before, config_after, lines_added, lines_removed, status, requested_by, created_at) VALUES
        (1, 'manual', 'Updated management interface IP', 'ip address 192.168.1.5 255.255.255.0', 'ip address 192.168.1.1 255.255.255.0', 1, 1, 'applied', 'admin', DATE_SUB(NOW(), INTERVAL 1 HOUR)),
        (4, 'template_apply', 'Apply VLAN configuration template', '', 'vlan 100\n name USERS\nvlan 200\n name SERVERS', 4, 0, 'pending', 'network_admin', DATE_SUB(NOW(), INTERVAL 30 MINUTE)),
        (7, 'manual', 'Add new firewall policy for web traffic', '', 'policy-id 150 action accept', 1, 0, 'pending', 'security_admin', NOW())");
    echo "Inserted NCM config changes\n";

    // VNQM Endpoints
    $pdo->exec("INSERT INTO vnqm_endpoints (endpoint_name, endpoint_type, ip_address, mac_address, extension, location, vendor, model, status) VALUES
        ('Reception Phone', 'ip_phone', '192.168.10.101', '00:1B:2C:3D:4E:01', '1001', 'Reception', 'Cisco', 'CP-8845', 'registered'),
        ('CEO Office', 'ip_phone', '192.168.10.102', '00:1B:2C:3D:4E:02', '1002', 'Executive Floor', 'Cisco', 'CP-8865', 'registered'),
        ('Conference Room A', 'conference', '192.168.10.110', '00:1B:2C:3D:4E:10', '1100', 'Floor 1', 'Polycom', 'Trio 8800', 'registered'),
        ('Conference Room B', 'conference', '192.168.10.111', '00:1B:2C:3D:4E:11', '1101', 'Floor 2', 'Polycom', 'Trio 8800', 'registered'),
        ('IT Support', 'ip_phone', '192.168.10.120', '00:1B:2C:3D:4E:20', '1200', 'IT Department', 'Cisco', 'CP-8845', 'registered'),
        ('Sales Desk 1', 'ip_phone', '192.168.10.130', '00:1B:2C:3D:4E:30', '1300', 'Sales Floor', 'Yealink', 'T54W', 'registered'),
        ('Sales Desk 2', 'ip_phone', '192.168.10.131', '00:1B:2C:3D:4E:31', '1301', 'Sales Floor', 'Yealink', 'T54W', 'offline'),
        ('Remote Worker 1', 'softphone', '0.0.0.0', NULL, '2001', 'Remote', 'Cisco', 'Jabber', 'registered'),
        ('SIP Gateway', 'gateway', '192.168.10.200', '00:1B:2C:3D:4E:99', NULL, 'Data Center', 'AudioCodes', 'Mediant 500', 'registered'),
        ('PBX Server', 'pbx', '192.168.10.250', '00:1B:2C:3D:4E:AA', NULL, 'Data Center', 'Asterisk', 'FreePBX 15', 'registered')");
    echo "Inserted VNQM endpoints\n";

    // VNQM SIP Trunks
    $pdo->exec("INSERT INTO vnqm_sip_trunks (trunk_name, provider, primary_ip, secondary_ip, channels, channels_in_use, status, total_calls_today, failed_calls_today, avg_mos_today) VALUES
        ('Primary PSTN', 'AT&T SIP', '203.0.113.10', '203.0.113.11', 30, 5, 'up', 245, 3, 4.25),
        ('Secondary PSTN', 'Verizon SIP', '198.51.100.20', '198.51.100.21', 20, 2, 'up', 89, 1, 4.15),
        ('International', 'Twilio', '52.4.128.100', NULL, 10, 1, 'up', 34, 0, 4.05),
        ('Backup Trunk', 'Vonage', '64.233.160.100', NULL, 15, 0, 'down', 0, 0, NULL)");
    echo "Inserted VNQM SIP trunks\n";

    // VNQM Call Details (sample calls)
    for ($i = 0; $i < 50; $i++) {
        $startTime = date('Y-m-d H:i:s', strtotime("-" . rand(0, 72) . " hours -" . rand(0, 59) . " minutes"));
        $duration = rand(30, 600);
        $endTime = date('Y-m-d H:i:s', strtotime($startTime) + $duration);
        $mos = round(rand(250, 450) / 100, 2);
        $jitter = rand(5, 80);
        $packetLoss = round(rand(0, 50) / 10, 2);
        $quality = $mos >= 4.0 ? 'excellent' : ($mos >= 3.5 ? 'good' : ($mos >= 3.0 ? 'fair' : 'poor'));
        $direction = ['inbound', 'outbound', 'internal'][rand(0, 2)];
        $codec = ['G.711', 'G.729', 'G.722', 'Opus'][rand(0, 3)];
        $trunkId = rand(0, 1) ? rand(1, 3) : 'NULL';
        $callId = 'CALL-' . date('Ymd', strtotime($startTime)) . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

        $callers = ['1001', '1002', '1100', '1200', '1300', '+14155551234', '+18005551234'];
        $callees = ['1001', '1002', '1101', '1200', '1301', '+14155554321', '+18005554321'];

        $pdo->exec("INSERT INTO vnqm_call_details (call_id, trunk_id, caller_number, callee_number, call_direction, call_start, call_end, duration, codec, mos_score, jitter_ms, packet_loss_percent, quality_rating) VALUES
            ('$callId', $trunkId, '{$callers[rand(0, count($callers)-1)]}', '{$callees[rand(0, count($callees)-1)]}', '$direction', '$startTime', '$endTime', $duration, '$codec', $mos, $jitter, $packetLoss, '$quality')");
    }
    echo "Inserted VNQM call details (50 sample calls)\n";

    // VNQM Codec Stats
    $pdo->exec("INSERT INTO vnqm_codec_stats (codec_name, total_calls, avg_mos, avg_jitter, avg_packet_loss, bandwidth_kbps, stat_date) VALUES
        ('G.711', 125, 4.15, 18, 0.3, 87, CURDATE()),
        ('G.729', 89, 3.95, 22, 0.5, 31, CURDATE()),
        ('G.722', 45, 4.25, 15, 0.2, 64, CURDATE()),
        ('Opus', 38, 4.35, 12, 0.1, 48, CURDATE())");
    echo "Inserted VNQM codec stats\n";

    // VNQM Alerts
    $pdo->exec("INSERT INTO vnqm_alerts (alert_type, severity, source_type, source_id, message, status, created_at) VALUES
        ('trunk_down', 'critical', 'trunk', 4, 'SIP Trunk \"Backup Trunk\" is DOWN - No response from 64.233.160.100', 'active', DATE_SUB(NOW(), INTERVAL 2 HOUR)),
        ('quality_degradation', 'warning', 'trunk', 2, 'Average MOS score dropped below 4.0 on Secondary PSTN trunk', 'acknowledged', DATE_SUB(NOW(), INTERVAL 6 HOUR)),
        ('endpoint_offline', 'warning', 'endpoint', 7, 'Endpoint \"Sales Desk 2\" (ext 1301) has gone offline', 'active', DATE_SUB(NOW(), INTERVAL 30 MINUTE)),
        ('high_jitter', 'info', 'call', NULL, 'High jitter detected (>50ms) on 3 calls in the last hour', 'resolved', DATE_SUB(NOW(), INTERVAL 12 HOUR))");
    echo "Inserted VNQM alerts\n";

    // Update module status to active
    $pdo->exec("UPDATE modules SET status = 'active', implementation_level = 'full' WHERE module_code IN ('NCM', 'VNQM')");
    echo "\nUpdated NCM and VNQM module status to 'active' with 'full' implementation\n";

    // Ensure foreign key checks are enabled
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "\n</pre>";
    echo "<h2 style='color: green;'>Setup Complete!</h2>";
    echo "<p><a href='modules/ncm.php'>Go to Network Configuration Manager</a></p>";
    echo "<p><a href='modules/vnqm.php'>Go to VoIP Quality Manager</a></p>";

} catch (PDOException $e) {
    // Re-enable foreign key checks even on error
    try {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    } catch (Exception $ignored) {}

    echo "\n<span style='color: red;'>ERROR: " . $e->getMessage() . "</span>\n";
    echo "</pre>";
}
?>
