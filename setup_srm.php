<?php
/**
 * SRM (Storage Resource Management) Database Setup Script
 * Creates all required tables for the SRM module
 */

require_once __DIR__ . '/classes/Database.php';

$db = Database::getInstance();
$pdo = $db->getConnection();

echo "<h1>SRM Database Setup</h1>";
echo "<pre>";

try {
    // Disable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    echo "Foreign key checks disabled\n";

    // =====================
    // DROP EXISTING TABLES
    // =====================
    $tables = [
        'srm_alerts',
        'srm_volume_mappings',
        'srm_volumes',
        'srm_disks',
        'srm_thresholds',
        'srm_performance_history',
        'srm_storage_arrays'
    ];

    foreach ($tables as $table) {
        $pdo->exec("DROP TABLE IF EXISTS $table");
        echo "Dropped table: $table\n";
    }

    // Re-enable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "Foreign key checks re-enabled\n\n";

    // =====================
    // CREATE TABLES
    // =====================
    echo "=== Creating SRM Tables ===\n\n";

    // Storage Arrays
    $pdo->exec("CREATE TABLE srm_storage_arrays (
        id INT AUTO_INCREMENT PRIMARY KEY,
        array_code VARCHAR(20) NOT NULL UNIQUE,
        name VARCHAR(255) NOT NULL,
        vendor VARCHAR(100) NOT NULL,
        model VARCHAR(100) NOT NULL,
        location VARCHAR(255) DEFAULT NULL,
        ip_address VARCHAR(45) NOT NULL,
        total_capacity_tb DECIMAL(10,2) DEFAULT 0,
        used_capacity_tb DECIMAL(10,2) DEFAULT 0,
        total_iops INT DEFAULT 0,
        read_iops INT DEFAULT 0,
        write_iops INT DEFAULT 0,
        throughput_mbps INT DEFAULT 0,
        avg_latency_ms DECIMAL(5,2) DEFAULT 0,
        read_latency_ms DECIMAL(5,2) DEFAULT 0,
        write_latency_ms DECIMAL(5,2) DEFAULT 0,
        health_status ENUM('Healthy', 'Warning', 'Critical', 'Offline') DEFAULT 'Healthy',
        disk_count INT DEFAULT 0,
        healthy_disks INT DEFAULT 0,
        failed_disks INT DEFAULT 0,
        raid_type VARCHAR(50) DEFAULT NULL,
        controller_status ENUM('Online', 'Offline', 'Degraded') DEFAULT 'Online',
        cache_hit_ratio DECIMAL(5,2) DEFAULT 0,
        firmware_version VARCHAR(50) DEFAULT NULL,
        last_checked DATETIME DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_health (health_status),
        INDEX idx_ip (ip_address)
    ) ENGINE=InnoDB");
    echo "Created table: srm_storage_arrays\n";

    // Disks
    $pdo->exec("CREATE TABLE srm_disks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        array_id INT NOT NULL,
        disk_id VARCHAR(50) NOT NULL,
        disk_type ENUM('SSD', 'SAS', 'NL-SAS', 'NVMe', 'SATA') NOT NULL,
        capacity_gb INT DEFAULT 0,
        status ENUM('Online', 'Offline', 'Failed', 'Degraded', 'Rebuilding') DEFAULT 'Online',
        temperature_c INT DEFAULT 0,
        wear_level INT DEFAULT 0,
        error_count INT DEFAULT 0,
        serial_number VARCHAR(100) DEFAULT NULL,
        firmware VARCHAR(50) DEFAULT NULL,
        slot_position VARCHAR(20) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (array_id) REFERENCES srm_storage_arrays(id) ON DELETE CASCADE,
        INDEX idx_array (array_id),
        INDEX idx_status (status)
    ) ENGINE=InnoDB");
    echo "Created table: srm_disks\n";

    // Volumes/LUNs
    $pdo->exec("CREATE TABLE srm_volumes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        volume_code VARCHAR(20) NOT NULL UNIQUE,
        name VARCHAR(255) NOT NULL,
        array_id INT NOT NULL,
        capacity_gb INT DEFAULT 0,
        used_gb INT DEFAULT 0,
        lun_id INT DEFAULT NULL,
        raid_type VARCHAR(50) DEFAULT NULL,
        tier ENUM('Tier 1', 'Tier 2', 'Tier 3', 'Archive') DEFAULT 'Tier 2',
        thin_provisioned BOOLEAN DEFAULT TRUE,
        dedup_enabled BOOLEAN DEFAULT FALSE,
        compression_enabled BOOLEAN DEFAULT FALSE,
        snapshot_count INT DEFAULT 0,
        status ENUM('Online', 'Offline', 'Degraded', 'Expanding') DEFAULT 'Online',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (array_id) REFERENCES srm_storage_arrays(id) ON DELETE CASCADE,
        INDEX idx_array (array_id),
        INDEX idx_status (status)
    ) ENGINE=InnoDB");
    echo "Created table: srm_volumes\n";

    // Volume to Host Mappings
    $pdo->exec("CREATE TABLE srm_volume_mappings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        volume_id INT NOT NULL,
        host_name VARCHAR(255) NOT NULL,
        host_type ENUM('Physical', 'Virtual', 'Container', 'Cluster') DEFAULT 'Physical',
        host_os VARCHAR(100) DEFAULT NULL,
        wwpn VARCHAR(50) DEFAULT NULL,
        iqn VARCHAR(255) DEFAULT NULL,
        mapped_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (volume_id) REFERENCES srm_volumes(id) ON DELETE CASCADE,
        INDEX idx_volume (volume_id),
        INDEX idx_host (host_name)
    ) ENGINE=InnoDB");
    echo "Created table: srm_volume_mappings\n";

    // Thresholds
    $pdo->exec("CREATE TABLE srm_thresholds (
        id INT AUTO_INCREMENT PRIMARY KEY,
        metric_type ENUM('capacity', 'iops', 'latency', 'disk_failure', 'temperature', 'wear_level') NOT NULL,
        warning_threshold DECIMAL(10,2) NOT NULL,
        critical_threshold DECIMAL(10,2) NOT NULL,
        notification_email BOOLEAN DEFAULT TRUE,
        notification_sms BOOLEAN DEFAULT FALSE,
        notification_dashboard BOOLEAN DEFAULT TRUE,
        enabled BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_metric (metric_type)
    ) ENGINE=InnoDB");
    echo "Created table: srm_thresholds\n";

    // Alerts
    $pdo->exec("CREATE TABLE srm_alerts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        array_id INT DEFAULT NULL,
        volume_id INT DEFAULT NULL,
        disk_id INT DEFAULT NULL,
        severity ENUM('Info', 'Warning', 'Critical') NOT NULL,
        alert_type ENUM('Capacity', 'Performance', 'Health', 'Temperature', 'Disk') NOT NULL,
        message TEXT NOT NULL,
        threshold_value DECIMAL(10,2) DEFAULT NULL,
        current_value DECIMAL(10,2) DEFAULT NULL,
        acknowledged BOOLEAN DEFAULT FALSE,
        acknowledged_by VARCHAR(100) DEFAULT NULL,
        acknowledged_at DATETIME DEFAULT NULL,
        resolved BOOLEAN DEFAULT FALSE,
        resolved_at DATETIME DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (array_id) REFERENCES srm_storage_arrays(id) ON DELETE SET NULL,
        INDEX idx_severity (severity),
        INDEX idx_acknowledged (acknowledged),
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB");
    echo "Created table: srm_alerts\n";

    // Performance History
    $pdo->exec("CREATE TABLE srm_performance_history (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        array_id INT NOT NULL,
        recorded_at DATETIME NOT NULL,
        total_iops INT DEFAULT 0,
        read_iops INT DEFAULT 0,
        write_iops INT DEFAULT 0,
        throughput_mbps INT DEFAULT 0,
        avg_latency_ms DECIMAL(5,2) DEFAULT 0,
        cache_hit_ratio DECIMAL(5,2) DEFAULT 0,
        FOREIGN KEY (array_id) REFERENCES srm_storage_arrays(id) ON DELETE CASCADE,
        INDEX idx_array_time (array_id, recorded_at)
    ) ENGINE=InnoDB");
    echo "Created table: srm_performance_history\n";

    // =====================
    // INSERT SAMPLE DATA
    // =====================
    echo "\n=== Inserting Sample Data ===\n\n";

    // Storage Arrays
    $pdo->exec("INSERT INTO srm_storage_arrays (array_code, name, vendor, model, location, ip_address, total_capacity_tb, used_capacity_tb, total_iops, read_iops, write_iops, throughput_mbps, avg_latency_ms, read_latency_ms, write_latency_ms, health_status, disk_count, healthy_disks, failed_disks, raid_type, controller_status, cache_hit_ratio, firmware_version, last_checked) VALUES
        ('SA-001', 'Dell EMC Unity XT 680', 'Dell EMC', 'Unity XT 680', 'Data Center 1 - Rack A1', '192.168.10.100', 50.0, 38.5, 45000, 28000, 17000, 2400, 2.3, 1.8, 3.2, 'Warning', 24, 23, 1, 'RAID 6', 'Online', 92.5, '5.2.1.0', NOW()),
        ('SA-002', 'NetApp FAS8300', 'NetApp', 'FAS8300', 'Data Center 1 - Rack A2', '192.168.10.101', 80.0, 52.8, 62000, 38000, 24000, 3200, 1.8, 1.5, 2.4, 'Healthy', 48, 48, 0, 'RAID-DP', 'Online', 94.8, '9.12.1', NOW()),
        ('SA-003', 'HPE 3PAR 8450', 'HPE', '3PAR 8450', 'Data Center 2 - Rack B1', '192.168.10.102', 100.0, 89.5, 38000, 22000, 16000, 2800, 3.5, 2.8, 4.6, 'Critical', 64, 62, 2, 'RAID 5', 'Online', 88.2, '3.3.1.484', NOW()),
        ('SA-004', 'Pure Storage FlashArray', 'Pure Storage', 'FlashArray X70', 'Data Center 2 - Rack B2', '192.168.10.103', 60.0, 28.2, 125000, 75000, 50000, 5600, 0.5, 0.4, 0.7, 'Healthy', 20, 20, 0, 'RAID 3D', 'Online', 98.5, '6.3.4', NOW())");
    echo "Inserted storage arrays\n";

    // Disks
    $pdo->exec("INSERT INTO srm_disks (array_id, disk_id, disk_type, capacity_gb, status, temperature_c, wear_level, error_count, slot_position) VALUES
        (1, 'Disk-0-0', 'SSD', 1920, 'Online', 42, 15, 0, 'Slot 0'),
        (1, 'Disk-0-1', 'SSD', 1920, 'Online', 45, 18, 0, 'Slot 1'),
        (1, 'Disk-0-2', 'SSD', 1920, 'Failed', 65, 92, 156, 'Slot 2'),
        (1, 'Disk-0-3', 'SSD', 1920, 'Online', 43, 16, 0, 'Slot 3'),
        (2, 'Disk-1-0', 'SAS', 1800, 'Online', 38, 12, 0, 'Slot 0'),
        (2, 'Disk-1-1', 'SAS', 1800, 'Online', 40, 14, 0, 'Slot 1'),
        (2, 'Disk-1-2', 'SAS', 1800, 'Online', 39, 13, 0, 'Slot 2'),
        (2, 'Disk-1-3', 'SAS', 1800, 'Online', 41, 15, 0, 'Slot 3'),
        (3, 'Disk-2-0', 'NL-SAS', 4000, 'Online', 44, 25, 2, 'Slot 0'),
        (3, 'Disk-2-1', 'NL-SAS', 4000, 'Failed', 72, 88, 245, 'Slot 1'),
        (3, 'Disk-2-2', 'NL-SAS', 4000, 'Degraded', 58, 76, 45, 'Slot 2'),
        (3, 'Disk-2-3', 'NL-SAS', 4000, 'Online', 46, 28, 1, 'Slot 3'),
        (4, 'Disk-3-0', 'NVMe', 3840, 'Online', 35, 8, 0, 'Slot 0'),
        (4, 'Disk-3-1', 'NVMe', 3840, 'Online', 36, 9, 0, 'Slot 1'),
        (4, 'Disk-3-2', 'NVMe', 3840, 'Online', 34, 7, 0, 'Slot 2'),
        (4, 'Disk-3-3', 'NVMe', 3840, 'Online', 37, 10, 0, 'Slot 3')");
    echo "Inserted disks\n";

    // Volumes
    $pdo->exec("INSERT INTO srm_volumes (volume_code, name, array_id, capacity_gb, used_gb, lun_id, raid_type, tier, thin_provisioned, dedup_enabled, compression_enabled, snapshot_count, status) VALUES
        ('VOL-001', 'PROD-DB-01', 1, 2048, 1638, 10, 'RAID 6', 'Tier 1', TRUE, TRUE, TRUE, 5, 'Online'),
        ('VOL-002', 'PROD-APP-01', 2, 4096, 2458, 20, 'RAID-DP', 'Tier 1', TRUE, TRUE, TRUE, 3, 'Online'),
        ('VOL-003', 'BACKUP-VOLUME', 3, 10240, 9216, 30, 'RAID 5', 'Tier 2', TRUE, FALSE, TRUE, 10, 'Online'),
        ('VOL-004', 'DEV-ENVIRONMENT', 4, 1024, 512, 40, 'RAID 3D', 'Tier 1', TRUE, TRUE, TRUE, 2, 'Online'),
        ('VOL-005', 'VM-DATASTORE-01', 2, 8192, 5734, 50, 'RAID-DP', 'Tier 1', TRUE, TRUE, TRUE, 7, 'Online'),
        ('VOL-006', 'ARCHIVE-DATA', 3, 20480, 18432, 60, 'RAID 5', 'Archive', TRUE, TRUE, TRUE, 1, 'Online'),
        ('VOL-007', 'TEST-VOLUME', 4, 512, 128, 70, 'RAID 3D', 'Tier 2', TRUE, FALSE, FALSE, 0, 'Online')");
    echo "Inserted volumes\n";

    // Volume Mappings
    $pdo->exec("INSERT INTO srm_volume_mappings (volume_id, host_name, host_type, host_os, wwpn) VALUES
        (1, 'db-server-01', 'Physical', 'RHEL 8.5', '50:01:43:80:00:00:00:01'),
        (1, 'db-server-02', 'Physical', 'RHEL 8.5', '50:01:43:80:00:00:00:02'),
        (2, 'app-server-01', 'Physical', 'Windows Server 2022', '50:01:43:80:00:00:01:01'),
        (2, 'app-server-02', 'Physical', 'Windows Server 2022', '50:01:43:80:00:00:01:02'),
        (2, 'app-server-03', 'Physical', 'Windows Server 2022', '50:01:43:80:00:00:01:03'),
        (3, 'backup-server-01', 'Physical', 'Windows Server 2019', '50:01:43:80:00:00:02:01'),
        (4, 'dev-server-01', 'Virtual', 'Ubuntu 22.04', NULL),
        (4, 'dev-server-02', 'Virtual', 'Ubuntu 22.04', NULL),
        (5, 'esxi-host-01', 'Physical', 'VMware ESXi 8.0', '50:01:43:80:00:00:03:01'),
        (5, 'esxi-host-02', 'Physical', 'VMware ESXi 8.0', '50:01:43:80:00:00:03:02'),
        (5, 'esxi-host-03', 'Physical', 'VMware ESXi 8.0', '50:01:43:80:00:00:03:03'),
        (5, 'esxi-host-04', 'Physical', 'VMware ESXi 8.0', '50:01:43:80:00:00:03:04')");
    echo "Inserted volume mappings\n";

    // Thresholds
    $pdo->exec("INSERT INTO srm_thresholds (metric_type, warning_threshold, critical_threshold, notification_email, notification_sms, notification_dashboard) VALUES
        ('capacity', 70, 85, TRUE, FALSE, TRUE),
        ('iops', 80, 95, TRUE, FALSE, TRUE),
        ('latency', 3, 5, TRUE, TRUE, TRUE),
        ('disk_failure', 1, 2, TRUE, TRUE, TRUE),
        ('temperature', 50, 60, TRUE, FALSE, TRUE),
        ('wear_level', 70, 85, TRUE, TRUE, TRUE)");
    echo "Inserted thresholds\n";

    // Alerts
    $pdo->exec("INSERT INTO srm_alerts (array_id, severity, alert_type, message, threshold_value, current_value, acknowledged, created_at) VALUES
        (3, 'Critical', 'Capacity', 'HPE 3PAR 8450 capacity exceeded 85% threshold (currently 89.5%)', 85, 89.5, FALSE, DATE_SUB(NOW(), INTERVAL 2 HOUR)),
        (1, 'Critical', 'Disk', 'Disk failure detected: Disk-0-2 on Dell EMC Unity XT 680', 0, 1, FALSE, DATE_SUB(NOW(), INTERVAL 4 HOUR)),
        (3, 'Critical', 'Disk', 'Multiple disk failures on HPE 3PAR 8450 (2 failed disks)', 0, 2, TRUE, DATE_SUB(NOW(), INTERVAL 6 HOUR)),
        (3, 'Warning', 'Capacity', 'BACKUP-VOLUME capacity exceeded 80% threshold (currently 90%)', 80, 90, TRUE, DATE_SUB(NOW(), INTERVAL 1 DAY)),
        (3, 'Warning', 'Performance', 'HPE 3PAR 8450 average latency exceeded 3ms threshold (currently 3.5ms)', 3.0, 3.5, FALSE, DATE_SUB(NOW(), INTERVAL 12 HOUR)),
        (1, 'Warning', 'Temperature', 'Disk-0-2 temperature exceeded 60C threshold (currently 65C)', 60, 65, FALSE, DATE_SUB(NOW(), INTERVAL 3 HOUR))");
    echo "Inserted alerts\n";

    // Performance History (last 24 hours)
    for ($i = 23; $i >= 0; $i--) {
        $time = date('Y-m-d H:i:s', strtotime("-{$i} hours"));
        $pdo->exec("INSERT INTO srm_performance_history (array_id, recorded_at, total_iops, read_iops, write_iops, throughput_mbps, avg_latency_ms, cache_hit_ratio) VALUES
            (1, '$time', " . rand(35000, 50000) . ", " . rand(20000, 30000) . ", " . rand(15000, 20000) . ", " . rand(2000, 2800) . ", " . (rand(18, 32) / 10) . ", " . (rand(900, 950) / 10) . "),
            (2, '$time', " . rand(55000, 70000) . ", " . rand(33000, 42000) . ", " . rand(22000, 28000) . ", " . rand(2800, 3600) . ", " . (rand(14, 24) / 10) . ", " . (rand(930, 960) / 10) . "),
            (3, '$time', " . rand(30000, 45000) . ", " . rand(18000, 27000) . ", " . rand(12000, 18000) . ", " . rand(2400, 3200) . ", " . (rand(28, 48) / 10) . ", " . (rand(860, 900) / 10) . "),
            (4, '$time', " . rand(110000, 140000) . ", " . rand(66000, 84000) . ", " . rand(44000, 56000) . ", " . rand(5000, 6200) . ", " . (rand(3, 8) / 10) . ", " . (rand(970, 990) / 10) . ")");
    }
    echo "Inserted performance history (24 hours)\n";

    // Update module status
    $pdo->exec("UPDATE modules SET status = 'active', implementation_level = 'full' WHERE module_code = 'SRM'");
    echo "\nUpdated SRM module status to 'active'\n";

    echo "\n</pre>";
    echo "<h2 style='color: green;'>SRM Setup Complete!</h2>";
    echo "<p><a href='modules/srm.php'>Go to Storage Resource Management</a></p>";

} catch (PDOException $e) {
    try {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    } catch (Exception $ignored) {}
    echo "\n<span style='color: red;'>ERROR: " . $e->getMessage() . "</span>\n";
    echo "</pre>";
}
?>
