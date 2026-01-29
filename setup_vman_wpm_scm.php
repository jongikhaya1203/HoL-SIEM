<?php
/**
 * Setup Script for VMAN, WPM, and SCM Modules
 * Creates database tables and inserts sample data
 */

require_once __DIR__ . '/classes/Database.php';

echo "<h1>Setting up VMAN, WPM, and SCM Module Tables</h1>";
echo "<pre>";

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();

    // Disable foreign key checks for clean drops
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    // =========================================
    // VIRTUALIZATION MANAGER (VMAN) TABLES
    // =========================================
    echo "\n=== Creating VMAN Tables ===\n";

    // Drop existing tables
    $vmanTables = ['vman_snapshots', 'vman_vms', 'vman_hypervisors', 'vman_cloud_instances', 'vman_cost_recommendations', 'vman_performance_history'];
    foreach ($vmanTables as $table) {
        $pdo->exec("DROP TABLE IF EXISTS $table");
        echo "Dropped table: $table\n";
    }

    // Hypervisors table
    $pdo->exec("CREATE TABLE vman_hypervisors (
        id INT AUTO_INCREMENT PRIMARY KEY,
        hostname VARCHAR(255) NOT NULL,
        platform ENUM('vmware', 'hyperv') NOT NULL,
        vcenter VARCHAR(255) DEFAULT NULL,
        cluster_name VARCHAR(255) DEFAULT NULL,
        os_version VARCHAR(100) DEFAULT NULL,
        cpu_cores INT DEFAULT 0,
        cpu_usage_percent DECIMAL(5,2) DEFAULT 0,
        memory_total_gb INT DEFAULT 0,
        memory_used_gb INT DEFAULT 0,
        storage_total_tb DECIMAL(10,2) DEFAULT 0,
        storage_used_tb DECIMAL(10,2) DEFAULT 0,
        ip_address VARCHAR(45) DEFAULT NULL,
        status ENUM('online', 'offline', 'maintenance', 'warning') DEFAULT 'online',
        uptime_days INT DEFAULT 0,
        last_sync DATETIME DEFAULT CURRENT_TIMESTAMP,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_platform (platform),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "Created table: vman_hypervisors\n";

    // Virtual Machines table
    $pdo->exec("CREATE TABLE vman_vms (
        id INT AUTO_INCREMENT PRIMARY KEY,
        vm_name VARCHAR(255) NOT NULL,
        hypervisor_id INT DEFAULT NULL,
        platform ENUM('vmware', 'hyperv', 'aws', 'azure', 'gcp') NOT NULL,
        vcpus INT DEFAULT 1,
        memory_gb INT DEFAULT 1,
        disk_gb INT DEFAULT 50,
        os_type VARCHAR(100) DEFAULT NULL,
        ip_address VARCHAR(45) DEFAULT NULL,
        network VARCHAR(100) DEFAULT NULL,
        cpu_usage_percent DECIMAL(5,2) DEFAULT 0,
        memory_usage_percent DECIMAL(5,2) DEFAULT 0,
        disk_iops INT DEFAULT 0,
        network_mbps DECIMAL(10,2) DEFAULT 0,
        power_state ENUM('running', 'stopped', 'suspended', 'unknown') DEFAULT 'running',
        health_status ENUM('healthy', 'warning', 'critical') DEFAULT 'healthy',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        last_updated DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (hypervisor_id) REFERENCES vman_hypervisors(id) ON DELETE SET NULL,
        INDEX idx_platform (platform),
        INDEX idx_power_state (power_state),
        INDEX idx_health (health_status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "Created table: vman_vms\n";

    // Snapshots table
    $pdo->exec("CREATE TABLE vman_snapshots (
        id INT AUTO_INCREMENT PRIMARY KEY,
        vm_id INT NOT NULL,
        snapshot_name VARCHAR(255) NOT NULL,
        description TEXT DEFAULT NULL,
        size_gb DECIMAL(10,2) DEFAULT 0,
        include_memory TINYINT(1) DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (vm_id) REFERENCES vman_vms(id) ON DELETE CASCADE,
        INDEX idx_vm (vm_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "Created table: vman_snapshots\n";

    // Cloud Instances table
    $pdo->exec("CREATE TABLE vman_cloud_instances (
        id INT AUTO_INCREMENT PRIMARY KEY,
        provider ENUM('aws', 'azure', 'gcp') NOT NULL,
        region VARCHAR(100) NOT NULL,
        instance_id VARCHAR(255) NOT NULL,
        instance_name VARCHAR(255) DEFAULT NULL,
        instance_type VARCHAR(100) NOT NULL,
        vcpus INT DEFAULT 1,
        memory_gb INT DEFAULT 1,
        storage_gb INT DEFAULT 50,
        state ENUM('running', 'stopped', 'terminated', 'pending') DEFAULT 'running',
        public_ip VARCHAR(45) DEFAULT NULL,
        private_ip VARCHAR(45) DEFAULT NULL,
        monthly_cost DECIMAL(10,2) DEFAULT 0,
        tags JSON DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        last_updated DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_provider (provider),
        INDEX idx_region (region),
        INDEX idx_state (state)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "Created table: vman_cloud_instances\n";

    // Cost Recommendations table
    $pdo->exec("CREATE TABLE vman_cost_recommendations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        resource_type VARCHAR(100) NOT NULL,
        resource_name VARCHAR(255) NOT NULL,
        provider VARCHAR(50) DEFAULT NULL,
        current_cost DECIMAL(10,2) DEFAULT 0,
        recommendation TEXT NOT NULL,
        estimated_savings DECIMAL(10,2) DEFAULT 0,
        savings_percent INT DEFAULT 0,
        reason TEXT DEFAULT NULL,
        priority ENUM('critical', 'high', 'medium', 'low') DEFAULT 'medium',
        action_type VARCHAR(100) DEFAULT NULL,
        status ENUM('pending', 'scheduled', 'applied', 'dismissed') DEFAULT 'pending',
        scheduled_date DATE DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_priority (priority),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "Created table: vman_cost_recommendations\n";

    // Performance History table
    $pdo->exec("CREATE TABLE vman_performance_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        vm_id INT DEFAULT NULL,
        hypervisor_id INT DEFAULT NULL,
        metric_type ENUM('cpu', 'memory', 'disk_iops', 'network') NOT NULL,
        metric_value DECIMAL(10,2) NOT NULL,
        recorded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_vm (vm_id),
        INDEX idx_hypervisor (hypervisor_id),
        INDEX idx_metric (metric_type),
        INDEX idx_time (recorded_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "Created table: vman_performance_history\n";

    // =========================================
    // WEB PERFORMANCE MONITOR (WPM) TABLES
    // =========================================
    echo "\n=== Creating WPM Tables ===\n";

    $wpmTables = ['wpm_transactions', 'wpm_alerts', 'wpm_performance_history', 'wpm_locations', 'wpm_websites'];
    foreach ($wpmTables as $table) {
        $pdo->exec("DROP TABLE IF EXISTS $table");
        echo "Dropped table: $table\n";
    }

    // Websites table
    $pdo->exec("CREATE TABLE wpm_websites (
        id INT AUTO_INCREMENT PRIMARY KEY,
        site_name VARCHAR(255) NOT NULL,
        url VARCHAR(500) NOT NULL,
        check_interval INT DEFAULT 300,
        timeout_seconds INT DEFAULT 30,
        expected_status_code INT DEFAULT 200,
        expected_content VARCHAR(500) DEFAULT NULL,
        ssl_check TINYINT(1) DEFAULT 1,
        ssl_expiry_date DATE DEFAULT NULL,
        ssl_days_remaining INT DEFAULT NULL,
        last_response_time_ms INT DEFAULT NULL,
        last_status_code INT DEFAULT NULL,
        last_check DATETIME DEFAULT NULL,
        status ENUM('up', 'down', 'warning', 'unknown') DEFAULT 'unknown',
        uptime_percent DECIMAL(5,2) DEFAULT 100,
        error_count INT DEFAULT 0,
        consecutive_failures INT DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_status (status),
        INDEX idx_ssl_expiry (ssl_expiry_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "Created table: wpm_websites\n";

    // Monitoring Locations table
    $pdo->exec("CREATE TABLE wpm_locations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        location_name VARCHAR(100) NOT NULL,
        city VARCHAR(100) NOT NULL,
        country VARCHAR(100) NOT NULL,
        region VARCHAR(100) DEFAULT NULL,
        is_active TINYINT(1) DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "Created table: wpm_locations\n";

    // Performance History table
    $pdo->exec("CREATE TABLE wpm_performance_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        website_id INT NOT NULL,
        location_id INT DEFAULT NULL,
        response_time_ms INT DEFAULT NULL,
        dns_time_ms INT DEFAULT NULL,
        connect_time_ms INT DEFAULT NULL,
        ttfb_ms INT DEFAULT NULL,
        download_time_ms INT DEFAULT NULL,
        status_code INT DEFAULT NULL,
        content_size_bytes INT DEFAULT NULL,
        is_successful TINYINT(1) DEFAULT 1,
        error_message VARCHAR(500) DEFAULT NULL,
        recorded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (website_id) REFERENCES wpm_websites(id) ON DELETE CASCADE,
        INDEX idx_website (website_id),
        INDEX idx_time (recorded_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "Created table: wpm_performance_history\n";

    // Alerts table
    $pdo->exec("CREATE TABLE wpm_alerts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        website_id INT NOT NULL,
        alert_type ENUM('down', 'slow', 'ssl_expiring', 'content_mismatch', 'error') NOT NULL,
        severity ENUM('critical', 'warning', 'info') DEFAULT 'warning',
        message TEXT NOT NULL,
        is_acknowledged TINYINT(1) DEFAULT 0,
        acknowledged_by VARCHAR(100) DEFAULT NULL,
        acknowledged_at DATETIME DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        resolved_at DATETIME DEFAULT NULL,
        FOREIGN KEY (website_id) REFERENCES wpm_websites(id) ON DELETE CASCADE,
        INDEX idx_website (website_id),
        INDEX idx_severity (severity),
        INDEX idx_acknowledged (is_acknowledged)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "Created table: wpm_alerts\n";

    // Transactions table (for synthetic monitoring)
    $pdo->exec("CREATE TABLE wpm_transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        website_id INT NOT NULL,
        transaction_name VARCHAR(255) NOT NULL,
        steps JSON NOT NULL,
        is_active TINYINT(1) DEFAULT 1,
        last_run DATETIME DEFAULT NULL,
        last_duration_ms INT DEFAULT NULL,
        last_status ENUM('success', 'failure', 'partial') DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (website_id) REFERENCES wpm_websites(id) ON DELETE CASCADE,
        INDEX idx_website (website_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "Created table: wpm_transactions\n";

    // =========================================
    // SERVER CONFIGURATION MONITOR (SCM) TABLES
    // =========================================
    echo "\n=== Creating SCM Tables ===\n";

    $scmTables = ['scm_config_changes', 'scm_certificates', 'scm_scheduled_tasks', 'scm_firewall_rules', 'scm_file_integrity', 'scm_baselines', 'scm_servers'];
    foreach ($scmTables as $table) {
        $pdo->exec("DROP TABLE IF EXISTS $table");
        echo "Dropped table: $table\n";
    }

    // Servers table
    $pdo->exec("CREATE TABLE scm_servers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        server_name VARCHAR(255) NOT NULL,
        ip_address VARCHAR(45) NOT NULL,
        os_type ENUM('windows', 'linux', 'unix') NOT NULL,
        os_version VARCHAR(100) DEFAULT NULL,
        kernel_version VARCHAR(100) DEFAULT NULL,
        server_type ENUM('physical', 'virtual', 'cloud') DEFAULT 'physical',
        role VARCHAR(100) DEFAULT NULL,
        services_count INT DEFAULT 0,
        config_files_count INT DEFAULT 0,
        packages_count INT DEFAULT 0,
        compliance_status ENUM('compliant', 'drift', 'unknown') DEFAULT 'unknown',
        drift_count INT DEFAULT 0,
        last_scan DATETIME DEFAULT NULL,
        is_active TINYINT(1) DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_os (os_type),
        INDEX idx_compliance (compliance_status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "Created table: scm_servers\n";

    // Configuration Baselines table
    $pdo->exec("CREATE TABLE scm_baselines (
        id INT AUTO_INCREMENT PRIMARY KEY,
        server_id INT NOT NULL,
        baseline_name VARCHAR(255) NOT NULL,
        baseline_type ENUM('services', 'registry', 'config_files', 'packages', 'users', 'firewall') NOT NULL,
        baseline_data JSON NOT NULL,
        is_current TINYINT(1) DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (server_id) REFERENCES scm_servers(id) ON DELETE CASCADE,
        INDEX idx_server (server_id),
        INDEX idx_type (baseline_type)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "Created table: scm_baselines\n";

    // File Integrity Monitoring table
    $pdo->exec("CREATE TABLE scm_file_integrity (
        id INT AUTO_INCREMENT PRIMARY KEY,
        server_id INT NOT NULL,
        file_path VARCHAR(500) NOT NULL,
        file_hash VARCHAR(128) NOT NULL,
        previous_hash VARCHAR(128) DEFAULT NULL,
        file_size BIGINT DEFAULT 0,
        file_permissions VARCHAR(50) DEFAULT NULL,
        owner VARCHAR(100) DEFAULT NULL,
        change_type ENUM('added', 'modified', 'deleted', 'permissions', 'none') DEFAULT 'none',
        severity ENUM('critical', 'high', 'medium', 'low') DEFAULT 'low',
        last_checked DATETIME DEFAULT CURRENT_TIMESTAMP,
        change_detected_at DATETIME DEFAULT NULL,
        is_acknowledged TINYINT(1) DEFAULT 0,
        FOREIGN KEY (server_id) REFERENCES scm_servers(id) ON DELETE CASCADE,
        INDEX idx_server (server_id),
        INDEX idx_change (change_type),
        INDEX idx_severity (severity)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "Created table: scm_file_integrity\n";

    // Firewall Rules table
    $pdo->exec("CREATE TABLE scm_firewall_rules (
        id INT AUTO_INCREMENT PRIMARY KEY,
        server_id INT NOT NULL,
        rule_name VARCHAR(255) NOT NULL,
        direction ENUM('inbound', 'outbound') NOT NULL,
        protocol VARCHAR(20) DEFAULT 'any',
        port VARCHAR(100) DEFAULT NULL,
        source_ip VARCHAR(100) DEFAULT 'any',
        destination_ip VARCHAR(100) DEFAULT 'any',
        action ENUM('allow', 'deny', 'drop') NOT NULL,
        is_enabled TINYINT(1) DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (server_id) REFERENCES scm_servers(id) ON DELETE CASCADE,
        INDEX idx_server (server_id),
        INDEX idx_direction (direction)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "Created table: scm_firewall_rules\n";

    // Scheduled Tasks table
    $pdo->exec("CREATE TABLE scm_scheduled_tasks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        server_id INT NOT NULL,
        task_name VARCHAR(255) NOT NULL,
        task_type ENUM('cron', 'scheduled_task', 'systemd_timer') NOT NULL,
        schedule VARCHAR(255) DEFAULT NULL,
        command TEXT DEFAULT NULL,
        run_as_user VARCHAR(100) DEFAULT NULL,
        is_enabled TINYINT(1) DEFAULT 1,
        last_run DATETIME DEFAULT NULL,
        last_result ENUM('success', 'failure', 'running', 'unknown') DEFAULT 'unknown',
        next_run DATETIME DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (server_id) REFERENCES scm_servers(id) ON DELETE CASCADE,
        INDEX idx_server (server_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "Created table: scm_scheduled_tasks\n";

    // Certificates table
    $pdo->exec("CREATE TABLE scm_certificates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        server_id INT NOT NULL,
        cert_name VARCHAR(255) NOT NULL,
        subject VARCHAR(500) NOT NULL,
        issuer VARCHAR(255) DEFAULT NULL,
        cert_type ENUM('ssl', 'computer', 'service', 'user', 'ca') DEFAULT 'ssl',
        serial_number VARCHAR(100) DEFAULT NULL,
        thumbprint VARCHAR(100) DEFAULT NULL,
        issued_date DATE DEFAULT NULL,
        expiry_date DATE NOT NULL,
        days_remaining INT DEFAULT NULL,
        key_size INT DEFAULT NULL,
        status ENUM('valid', 'expiring', 'expired', 'revoked') DEFAULT 'valid',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (server_id) REFERENCES scm_servers(id) ON DELETE CASCADE,
        INDEX idx_server (server_id),
        INDEX idx_expiry (expiry_date),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "Created table: scm_certificates\n";

    // Configuration Changes table
    $pdo->exec("CREATE TABLE scm_config_changes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        server_id INT NOT NULL,
        change_type ENUM('service', 'registry', 'config_file', 'package', 'user', 'group', 'firewall', 'certificate') NOT NULL,
        item_name VARCHAR(500) NOT NULL,
        old_value TEXT DEFAULT NULL,
        new_value TEXT DEFAULT NULL,
        change_action ENUM('added', 'modified', 'removed') NOT NULL,
        severity ENUM('critical', 'high', 'medium', 'low', 'info') DEFAULT 'info',
        detected_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        is_acknowledged TINYINT(1) DEFAULT 0,
        acknowledged_by VARCHAR(100) DEFAULT NULL,
        acknowledged_at DATETIME DEFAULT NULL,
        FOREIGN KEY (server_id) REFERENCES scm_servers(id) ON DELETE CASCADE,
        INDEX idx_server (server_id),
        INDEX idx_type (change_type),
        INDEX idx_severity (severity),
        INDEX idx_acknowledged (is_acknowledged)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "Created table: scm_config_changes\n";

    // Re-enable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    // =========================================
    // INSERT SAMPLE DATA
    // =========================================
    echo "\n=== Inserting VMAN Sample Data ===\n";

    // Insert hypervisors
    $hypervisors = [
        ['esxi-host-01.lab.local', 'vmware', 'vcenter-prod.lab.local', NULL, 'ESXi 8.0 Update 2', 64, 68, 512, 348, 12.5, 8.3, '192.168.1.100', 'online', 87],
        ['esxi-host-02.lab.local', 'vmware', 'vcenter-prod.lab.local', NULL, 'ESXi 8.0 Update 2', 64, 54, 512, 276, 12.5, 6.9, '192.168.1.101', 'online', 92],
        ['esxi-host-03.lab.local', 'vmware', 'vcenter-prod.lab.local', NULL, 'ESXi 7.0 Update 3', 48, 73, 384, 298, 10.0, 7.5, '192.168.1.102', 'maintenance', 156],
        ['hyperv-node-01', 'hyperv', NULL, 'Production-Cluster', 'Windows Server 2022', 32, 62, 256, 178, 8.0, 5.6, '192.168.1.110', 'online', 45],
        ['hyperv-node-02', 'hyperv', NULL, 'Production-Cluster', 'Windows Server 2022', 32, 58, 256, 165, 8.0, 4.9, '192.168.1.111', 'online', 45],
        ['hyperv-node-03', 'hyperv', NULL, 'Development-Cluster', 'Windows Server 2019', 24, 45, 192, 98, 6.0, 3.2, '192.168.1.112', 'online', 120]
    ];

    $stmt = $pdo->prepare("INSERT INTO vman_hypervisors (hostname, platform, vcenter, cluster_name, os_version, cpu_cores, cpu_usage_percent, memory_total_gb, memory_used_gb, storage_total_tb, storage_used_tb, ip_address, status, uptime_days) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($hypervisors as $h) {
        $stmt->execute($h);
    }
    echo "Inserted " . count($hypervisors) . " hypervisors\n";

    // Insert VMs
    $vms = [
        ['web-server-01', 1, 'vmware', 4, 8, 100, 'Ubuntu 22.04', '10.0.1.10', 'Production', 45, 68, 1250, 125, 'running', 'healthy'],
        ['db-server-01', 1, 'vmware', 8, 32, 500, 'CentOS 8', '10.0.1.20', 'Production', 78, 85, 4500, 340, 'running', 'warning'],
        ['app-server-01', 4, 'hyperv', 4, 16, 200, 'Windows Server 2022', '10.0.2.10', 'Production', 52, 71, 1890, 178, 'running', 'healthy'],
        ['dev-server-01', 6, 'hyperv', 2, 8, 100, 'Ubuntu 20.04', '10.0.3.10', 'Development', 25, 45, 450, 50, 'running', 'healthy'],
        ['test-server-01', 2, 'vmware', 2, 4, 50, 'Windows Server 2019', '10.0.1.30', 'Test', 15, 35, 200, 25, 'stopped', 'healthy'],
        ['backup-server-01', 2, 'vmware', 4, 16, 2000, 'Ubuntu 22.04', '10.0.1.40', 'Management', 30, 60, 800, 100, 'running', 'healthy']
    ];

    $stmt = $pdo->prepare("INSERT INTO vman_vms (vm_name, hypervisor_id, platform, vcpus, memory_gb, disk_gb, os_type, ip_address, network, cpu_usage_percent, memory_usage_percent, disk_iops, network_mbps, power_state, health_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($vms as $vm) {
        $stmt->execute($vm);
    }
    echo "Inserted " . count($vms) . " VMs\n";

    // Insert cloud instances
    $cloudInstances = [
        ['aws', 'us-east-1', 'i-0abc123def456', 'web-prod-01', 't3.large', 2, 8, 100, 'running', '54.123.45.67', '10.0.0.10', 89.50],
        ['aws', 'us-east-1', 'i-0abc123def457', 'api-prod-01', 't3.xlarge', 4, 16, 200, 'running', '54.123.45.68', '10.0.0.11', 156.00],
        ['aws', 'us-west-2', 'i-0def789abc012', 'db-replica-01', 'r5.2xlarge', 8, 64, 500, 'running', '52.88.123.45', '10.1.0.10', 450.00],
        ['azure', 'East US', 'vm-eastus-001', 'app-server-az-01', 'Standard_D4s_v3', 4, 16, 128, 'running', '40.121.123.45', '10.2.0.10', 178.50],
        ['azure', 'West Europe', 'vm-westeu-001', 'web-eu-01', 'Standard_D2s_v3', 2, 8, 64, 'running', '51.124.45.67', '10.3.0.10', 89.25],
        ['gcp', 'us-central1', 'vm-uscentral-001', 'analytics-01', 'n2-standard-8', 8, 32, 256, 'running', '35.192.45.67', '10.4.0.10', 320.00],
        ['gcp', 'europe-west1', 'vm-euwest-001', 'ml-training-01', 'n2-highmem-4', 4, 32, 200, 'stopped', NULL, '10.5.0.10', 0]
    ];

    $stmt = $pdo->prepare("INSERT INTO vman_cloud_instances (provider, region, instance_id, instance_name, instance_type, vcpus, memory_gb, storage_gb, state, public_ip, private_ip, monthly_cost) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($cloudInstances as $ci) {
        $stmt->execute($ci);
    }
    echo "Inserted " . count($cloudInstances) . " cloud instances\n";

    // Insert cost recommendations
    $recommendations = [
        ['EC2', 'i3.4xlarge (us-east-1)', 'aws', 456.78, 'Downsize to i3.2xlarge - CPU averaging 18%', 228.39, 50, 'CPU utilization averaging 18% over 30 days', 'high', 'Resize Instance'],
        ['Azure VM', 'Standard_D16s_v3 (West Europe)', 'azure', 678.90, 'Use Reserved Instance (1 year)', 244.40, 36, 'Running 24/7 with predictable workload', 'high', 'Purchase RI'],
        ['GCP Compute', 'n1-standard-16 (us-central1)', 'gcp', 534.67, 'Switch to e2-standard-16', 160.40, 30, 'E2 instances sufficient for workload', 'medium', 'Change Instance Type'],
        ['EBS Volume', 'Unattached volumes (us-west-2)', 'aws', 245.00, 'Delete 15 unused volumes', 245.00, 100, 'Volumes unattached for >60 days', 'critical', 'Delete Resources'],
        ['RDS', 'db.r5.2xlarge (eu-west-1)', 'aws', 789.12, 'Enable Auto-Scaling', 197.28, 25, 'Load varies significantly throughout day', 'medium', 'Configure Auto-Scaling']
    ];

    $stmt = $pdo->prepare("INSERT INTO vman_cost_recommendations (resource_type, resource_name, provider, current_cost, recommendation, estimated_savings, savings_percent, reason, priority, action_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($recommendations as $r) {
        $stmt->execute($r);
    }
    echo "Inserted " . count($recommendations) . " cost recommendations\n";

    // Insert snapshots
    $snapshots = [
        [1, 'Pre-Update Snapshot', 'Before installing security patches', 2.5, 0],
        [1, 'Clean State', 'Fresh installation backup', 1.8, 1],
        [2, 'Before Schema Change', 'Database schema backup', 5.2, 1],
        [3, 'Weekly Backup', 'Automated weekly snapshot', 3.1, 0]
    ];

    $stmt = $pdo->prepare("INSERT INTO vman_snapshots (vm_id, snapshot_name, description, size_gb, include_memory) VALUES (?, ?, ?, ?, ?)");
    foreach ($snapshots as $s) {
        $stmt->execute($s);
    }
    echo "Inserted " . count($snapshots) . " snapshots\n";

    echo "\n=== Inserting WPM Sample Data ===\n";

    // Insert websites
    $websites = [
        ['Company Website', 'https://www.company.com', 300, 30, 200, NULL, 1, date('Y-m-d', strtotime('+90 days')), 90, 245, 200, date('Y-m-d H:i:s'), 'up', 99.95, 0, 0],
        ['Customer Portal', 'https://portal.company.com', 60, 15, 200, 'Login', 1, date('Y-m-d', strtotime('+45 days')), 45, 567, 200, date('Y-m-d H:i:s'), 'up', 99.87, 2, 0],
        ['API Gateway', 'https://api.company.com/health', 30, 10, 200, '{"status":"ok"}', 1, date('Y-m-d', strtotime('+120 days')), 120, 89, 200, date('Y-m-d H:i:s'), 'up', 99.99, 0, 0],
        ['E-commerce Store', 'https://shop.company.com', 60, 20, 200, NULL, 1, date('Y-m-d', strtotime('+15 days')), 15, 1250, 200, date('Y-m-d H:i:s'), 'warning', 98.50, 15, 0],
        ['Support Desk', 'https://support.company.com', 300, 30, 200, NULL, 1, date('Y-m-d', strtotime('+180 days')), 180, 890, 200, date('Y-m-d H:i:s'), 'up', 99.75, 3, 0],
        ['Legacy App', 'http://legacy.company.com', 600, 60, 200, NULL, 0, NULL, NULL, 2500, 200, date('Y-m-d H:i:s', strtotime('-10 minutes')), 'down', 95.20, 45, 3]
    ];

    $stmt = $pdo->prepare("INSERT INTO wpm_websites (site_name, url, check_interval, timeout_seconds, expected_status_code, expected_content, ssl_check, ssl_expiry_date, ssl_days_remaining, last_response_time_ms, last_status_code, last_check, status, uptime_percent, error_count, consecutive_failures) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($websites as $w) {
        $stmt->execute($w);
    }
    echo "Inserted " . count($websites) . " websites\n";

    // Insert monitoring locations
    $locations = [
        ['US East', 'New York', 'United States', 'North America'],
        ['US West', 'Los Angeles', 'United States', 'North America'],
        ['Europe', 'London', 'United Kingdom', 'Europe'],
        ['Asia Pacific', 'Singapore', 'Singapore', 'Asia'],
        ['Australia', 'Sydney', 'Australia', 'Oceania']
    ];

    $stmt = $pdo->prepare("INSERT INTO wpm_locations (location_name, city, country, region) VALUES (?, ?, ?, ?)");
    foreach ($locations as $l) {
        $stmt->execute($l);
    }
    echo "Inserted " . count($locations) . " monitoring locations\n";

    // Insert alerts
    $alerts = [
        [4, 'ssl_expiring', 'warning', 'SSL certificate expires in 15 days', 0, NULL, NULL],
        [6, 'down', 'critical', 'Website is not responding - 3 consecutive failures', 0, NULL, NULL],
        [4, 'slow', 'warning', 'Response time exceeds 1000ms threshold', 1, 'admin', date('Y-m-d H:i:s', strtotime('-2 hours'))],
        [2, 'error', 'info', 'Intermittent 500 errors detected', 1, 'admin', date('Y-m-d H:i:s', strtotime('-1 day'))]
    ];

    $stmt = $pdo->prepare("INSERT INTO wpm_alerts (website_id, alert_type, severity, message, is_acknowledged, acknowledged_by, acknowledged_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
    foreach ($alerts as $a) {
        $stmt->execute($a);
    }
    echo "Inserted " . count($alerts) . " alerts\n";

    echo "\n=== Inserting SCM Sample Data ===\n";

    // Insert servers
    $servers = [
        ['WIN-SRV-01', '192.168.1.10', 'windows', 'Windows Server 2022', NULL, 'physical', 'File Server', 45, 0, 89, 'compliant', 0],
        ['WIN-SRV-02', '192.168.1.11', 'windows', 'Windows Server 2019', NULL, 'virtual', 'Application Server', 42, 0, 76, 'drift', 3],
        ['WIN-DC-01', '192.168.1.5', 'windows', 'Windows Server 2022', NULL, 'physical', 'Domain Controller', 52, 0, 67, 'compliant', 0],
        ['LINUX-WEB-01', '10.0.0.20', 'linux', 'Ubuntu 22.04', '5.15.0-97', 'virtual', 'Web Server', 28, 234, 512, 'compliant', 0],
        ['LINUX-DB-01', '10.0.0.21', 'linux', 'CentOS 8', '4.18.0-425', 'virtual', 'Database Server', 32, 198, 487, 'drift', 2],
        ['LINUX-APP-01', '10.0.0.22', 'linux', 'Debian 11', '5.10.0-28', 'virtual', 'Application Server', 25, 187, 445, 'compliant', 0]
    ];

    $stmt = $pdo->prepare("INSERT INTO scm_servers (server_name, ip_address, os_type, os_version, kernel_version, server_type, role, services_count, config_files_count, packages_count, compliance_status, drift_count, last_scan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    foreach ($servers as $s) {
        $stmt->execute($s);
    }
    echo "Inserted " . count($servers) . " servers\n";

    // Insert certificates
    $certificates = [
        [1, 'IIS Default Certificate', 'CN=WIN-SRV-01', 'Internal CA', 'ssl', date('Y-m-d', strtotime('+45 days')), 45, 'expiring'],
        [2, 'SQL Server Certificate', 'CN=WIN-SRV-02', 'DigiCert', 'service', date('Y-m-d', strtotime('+5 days')), 5, 'expiring'],
        [3, 'DC Certificate', 'CN=WIN-DC-01.domain.local', 'Internal CA', 'computer', date('Y-m-d', strtotime('+180 days')), 180, 'valid'],
        [4, 'Apache SSL', 'CN=www.company.com', 'Let\'s Encrypt', 'ssl', date('Y-m-d', strtotime('+15 days')), 15, 'expiring'],
        [5, 'MySQL SSL', 'CN=LINUX-DB-01', 'Internal CA', 'ssl', date('Y-m-d', strtotime('+120 days')), 120, 'valid'],
        [6, 'Nginx SSL', 'CN=portal.company.com', 'DigiCert', 'ssl', date('Y-m-d', strtotime('+200 days')), 200, 'valid']
    ];

    $stmt = $pdo->prepare("INSERT INTO scm_certificates (server_id, cert_name, subject, issuer, cert_type, expiry_date, days_remaining, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($certificates as $c) {
        $stmt->execute($c);
    }
    echo "Inserted " . count($certificates) . " certificates\n";

    // Insert file integrity records
    $files = [
        [1, 'C:\\Windows\\System32\\config\\SAM', 'abc123def456', 'abc123def450', 65536, 'rw-------', 'SYSTEM', 'modified', 'high'],
        [1, 'C:\\inetpub\\wwwroot\\web.config', 'def789ghi012', 'def789ghi012', 2048, 'rw-r--r--', 'IIS_IUSRS', 'none', 'low'],
        [4, '/etc/passwd', 'ghi345jkl678', 'ghi345jkl670', 2456, 'rw-r--r--', 'root', 'modified', 'critical'],
        [4, '/etc/shadow', 'jkl901mno234', 'jkl901mno230', 1024, 'rw-------', 'root', 'modified', 'critical'],
        [5, '/etc/mysql/my.cnf', 'mno567pqr890', 'mno567pqr890', 4096, 'rw-r--r--', 'mysql', 'none', 'low'],
        [5, '/var/log/auth.log', 'pqr123stu456', NULL, 102400, 'rw-r-----', 'syslog', 'added', 'medium']
    ];

    $stmt = $pdo->prepare("INSERT INTO scm_file_integrity (server_id, file_path, file_hash, previous_hash, file_size, file_permissions, owner, change_type, severity, change_detected_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    foreach ($files as $f) {
        $stmt->execute($f);
    }
    echo "Inserted " . count($files) . " file integrity records\n";

    // Insert firewall rules
    $firewallRules = [
        [1, 'Allow HTTPS', 'inbound', 'tcp', '443', 'any', '192.168.1.10', 'allow', 1],
        [1, 'Allow HTTP', 'inbound', 'tcp', '80', 'any', '192.168.1.10', 'allow', 1],
        [1, 'Block Telnet', 'inbound', 'tcp', '23', 'any', 'any', 'deny', 1],
        [4, 'Allow SSH', 'inbound', 'tcp', '22', '10.0.0.0/24', '10.0.0.20', 'allow', 1],
        [4, 'Allow HTTP', 'inbound', 'tcp', '80', 'any', '10.0.0.20', 'allow', 1],
        [5, 'Allow MySQL', 'inbound', 'tcp', '3306', '10.0.0.0/24', '10.0.0.21', 'allow', 1]
    ];

    $stmt = $pdo->prepare("INSERT INTO scm_firewall_rules (server_id, rule_name, direction, protocol, port, source_ip, destination_ip, action, is_enabled) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($firewallRules as $fr) {
        $stmt->execute($fr);
    }
    echo "Inserted " . count($firewallRules) . " firewall rules\n";

    // Insert scheduled tasks
    $tasks = [
        [1, 'Windows Update', 'scheduled_task', 'Daily at 3:00 AM', 'wuauclt /detectnow', 'SYSTEM', 1, 'success'],
        [1, 'Backup Job', 'scheduled_task', 'Daily at 2:00 AM', 'C:\\backup\\backup.bat', 'Administrator', 1, 'success'],
        [4, 'Log Rotation', 'cron', '0 0 * * *', '/usr/sbin/logrotate /etc/logrotate.conf', 'root', 1, 'success'],
        [4, 'Security Updates', 'cron', '0 4 * * 0', 'apt-get update && apt-get upgrade -y', 'root', 1, 'success'],
        [5, 'MySQL Backup', 'cron', '0 1 * * *', '/usr/local/bin/mysql_backup.sh', 'mysql', 1, 'failure'],
        [5, 'Optimize Tables', 'cron', '0 5 * * 0', 'mysqlcheck -o --all-databases', 'mysql', 1, 'success']
    ];

    $stmt = $pdo->prepare("INSERT INTO scm_scheduled_tasks (server_id, task_name, task_type, schedule, command, run_as_user, is_enabled, last_result, last_run, next_run) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 1 DAY))");
    foreach ($tasks as $t) {
        $stmt->execute($t);
    }
    echo "Inserted " . count($tasks) . " scheduled tasks\n";

    // Insert config changes
    $changes = [
        [2, 'service', 'Windows Firewall', 'enabled', 'disabled', 'modified', 'high'],
        [2, 'registry', 'HKLM\\SOFTWARE\\Policies\\Microsoft\\Windows\\WindowsUpdate', NULL, 'NoAutoUpdate=1', 'added', 'medium'],
        [2, 'package', 'Adobe Reader', '2023.001.20093', '2024.001.20604', 'modified', 'low'],
        [5, 'config_file', '/etc/mysql/my.cnf', 'max_connections=100', 'max_connections=200', 'modified', 'medium'],
        [5, 'user', 'backup_user', NULL, 'uid=1001', 'added', 'info']
    ];

    $stmt = $pdo->prepare("INSERT INTO scm_config_changes (server_id, change_type, item_name, old_value, new_value, change_action, severity) VALUES (?, ?, ?, ?, ?, ?, ?)");
    foreach ($changes as $c) {
        $stmt->execute($c);
    }
    echo "Inserted " . count($changes) . " configuration changes\n";

    echo "\n=== Setup Complete! ===\n";
    echo "All VMAN, WPM, and SCM tables have been created and populated with sample data.\n";

} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    // Re-enable foreign key checks even on error
    try {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    } catch (Exception $ex) {}
}

echo "</pre>";
echo '<p><a href="index.php">Back to Dashboard</a></p>';
?>
