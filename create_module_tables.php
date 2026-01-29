<?php
/**
 * Create All Module Tables
 * Creates all supporting tables for the module system
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Create Module Tables</title>";
echo "<style>
body { font-family: Arial; padding: 20px; background: #f5f5f5; }
.success { color: green; font-weight: bold; }
.error { color: red; font-weight: bold; }
.info { color: blue; }
h1 { color: #667eea; }
.box { background: white; padding: 20px; margin: 10px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
</style></head><body>";

echo "<h1>🔧 Creating Module Tables</h1>";

require_once 'classes/Database.php';

try {
    $db = Database::getInstance();
    echo "<div class='box'><p class='success'>✓ Database connected</p></div>";
} catch (Exception $e) {
    echo "<div class='box'><p class='error'>✗ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</p></div>";
    exit;
}

// Define all table creation SQL
$tables = [
    'module_metrics' => "CREATE TABLE IF NOT EXISTS module_metrics (
        id INT AUTO_INCREMENT PRIMARY KEY,
        module_id INT NOT NULL,
        metric_name VARCHAR(100) NOT NULL,
        metric_value VARCHAR(255),
        metric_unit VARCHAR(20) DEFAULT NULL,
        metric_status ENUM('healthy', 'warning', 'critical', 'unknown') DEFAULT 'unknown',
        collected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_module (module_id),
        INDEX idx_collected (collected_at)
    ) ENGINE=InnoDB",

    'network_devices' => "CREATE TABLE IF NOT EXISTS network_devices (
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
        status ENUM('online', 'offline', 'warning', 'unknown') DEFAULT 'unknown',
        last_seen DATETIME DEFAULT NULL,
        snmp_community VARCHAR(100) DEFAULT NULL,
        snmp_version ENUM('v1', 'v2c', 'v3') DEFAULT 'v2c',
        monitored BOOLEAN DEFAULT TRUE,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_ip (ip_address),
        INDEX idx_type (device_type),
        INDEX idx_status (status)
    ) ENGINE=InnoDB",

    'performance_metrics' => "CREATE TABLE IF NOT EXISTS performance_metrics (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        device_id INT DEFAULT NULL,
        host_id INT DEFAULT NULL,
        metric_type VARCHAR(50) NOT NULL,
        metric_value DECIMAL(15,4) NOT NULL,
        unit VARCHAR(20) DEFAULT NULL,
        timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_device (device_id),
        INDEX idx_host (host_id),
        INDEX idx_type (metric_type),
        INDEX idx_timestamp (timestamp)
    ) ENGINE=InnoDB",

    'traffic_flows' => "CREATE TABLE IF NOT EXISTS traffic_flows (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        source_ip VARCHAR(45) NOT NULL,
        destination_ip VARCHAR(45) NOT NULL,
        source_port INT DEFAULT NULL,
        destination_port INT DEFAULT NULL,
        protocol VARCHAR(20) NOT NULL,
        bytes_transferred BIGINT DEFAULT 0,
        packets_count INT DEFAULT 0,
        flow_start DATETIME NOT NULL,
        flow_end DATETIME DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_source (source_ip),
        INDEX idx_destination (destination_ip),
        INDEX idx_protocol (protocol),
        INDEX idx_flow_start (flow_start)
    ) ENGINE=InnoDB",

    'ip_addresses' => "CREATE TABLE IF NOT EXISTS ip_addresses (
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
        allocated_at DATETIME DEFAULT NULL,
        last_seen DATETIME DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_ip (ip_address),
        INDEX idx_subnet (subnet),
        INDEX idx_status (status)
    ) ENGINE=InnoDB",

    'voip_calls' => "CREATE TABLE IF NOT EXISTS voip_calls (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        call_id VARCHAR(100) UNIQUE NOT NULL,
        caller_number VARCHAR(50),
        callee_number VARCHAR(50),
        call_start DATETIME NOT NULL,
        call_end DATETIME DEFAULT NULL,
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
    ) ENGINE=InnoDB",

    'monitored_applications' => "CREATE TABLE IF NOT EXISTS monitored_applications (
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
        INDEX idx_host (host_id),
        INDEX idx_status (status)
    ) ENGINE=InnoDB",

    'monitored_databases' => "CREATE TABLE IF NOT EXISTS monitored_databases (
        id INT AUTO_INCREMENT PRIMARY KEY,
        db_name VARCHAR(100) NOT NULL,
        db_type ENUM('mysql', 'postgresql', 'mssql', 'oracle', 'mongodb', 'redis', 'other') NOT NULL,
        host_id INT DEFAULT NULL,
        connection_string TEXT DEFAULT NULL,
        status ENUM('online', 'offline', 'degraded', 'unknown') DEFAULT 'unknown',
        query_count INT DEFAULT 0,
        slow_queries INT DEFAULT 0,
        connections_active INT DEFAULT 0,
        connections_max INT DEFAULT 0,
        db_size_mb BIGINT DEFAULT 0,
        last_backup DATETIME DEFAULT NULL,
        last_check DATETIME DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_type (db_type),
        INDEX idx_status (status)
    ) ENGINE=InnoDB"
];

// Create tables
echo "<div class='box'><h2>Creating Tables...</h2>";

$created = 0;
$errors = 0;

foreach ($tables as $tableName => $sql) {
    try {
        $db->query($sql);
        echo "<p class='success'>✓ Table '{$tableName}' created</p>";
        $created++;
    } catch (Exception $e) {
        echo "<p class='error'>✗ Failed to create '{$tableName}': " . htmlspecialchars($e->getMessage()) . "</p>";
        $errors++;
    }
}

echo "</div>";

// Insert sample data
echo "<div class='box'><h2>Inserting Sample Data...</h2>";

try {
    // Sample network devices
    $db->query("INSERT INTO network_devices (device_name, device_type, ip_address, mac_address, manufacturer, status, location) VALUES
        ('Core-Router-01', 'router', '192.168.1.1', '00:1A:2B:3C:4D:5E', 'Cisco', 'online', 'Server Room'),
        ('Core-Switch-01', 'switch', '192.168.1.2', '00:1A:2B:3C:4D:5F', 'Cisco', 'online', 'Server Room'),
        ('Firewall-01', 'firewall', '192.168.1.254', '00:1A:2B:3C:4D:60', 'Fortinet', 'online', 'DMZ'),
        ('AP-Floor1-01', 'access_point', '192.168.1.10', '00:1A:2B:3C:4D:61', 'Ubiquiti', 'online', 'Floor 1'),
        ('PBX-Server', 'server', '192.168.1.50', '00:1A:2B:3C:4D:62', 'FreePBX', 'online', 'Telecom Rack')
        ON DUPLICATE KEY UPDATE device_name = device_name");
    echo "<p class='success'>✓ Inserted sample network devices</p>";

    // Sample IP addresses
    $db->query("INSERT INTO ip_addresses (ip_address, subnet, status, description) VALUES
        ('192.168.1.1', '192.168.1.0/24', 'allocated', 'Gateway Router'),
        ('192.168.1.2', '192.168.1.0/24', 'allocated', 'Core Switch'),
        ('192.168.1.10', '192.168.1.0/24', 'allocated', 'Access Point'),
        ('192.168.1.254', '192.168.1.0/24', 'allocated', 'Firewall'),
        ('192.168.1.100', '192.168.1.0/24', 'available', 'Available for allocation'),
        ('192.168.1.101', '192.168.1.0/24', 'available', 'Available for allocation'),
        ('192.168.1.102', '192.168.1.0/24', 'available', 'Available for allocation')
        ON DUPLICATE KEY UPDATE ip_address = ip_address");
    echo "<p class='success'>✓ Inserted sample IP addresses</p>";

    // Sample monitored applications
    $db->query("INSERT INTO monitored_applications (app_name, app_type, url, status, response_time_ms) VALUES
        ('Company Website', 'web', 'https://company.com', 'running', 250),
        ('API Server', 'api', 'https://api.company.com', 'running', 120),
        ('Mail Server', 'email', 'mail.company.com', 'running', 50),
        ('CRM Application', 'business', 'https://crm.company.com', 'running', 300)
        ON DUPLICATE KEY UPDATE app_name = app_name");
    echo "<p class='success'>✓ Inserted sample applications</p>";

    // Sample monitored databases
    $db->query("INSERT INTO monitored_databases (db_name, db_type, status, connections_active, connections_max, db_size_mb) VALUES
        ('production_db', 'mysql', 'online', 45, 150, 5120),
        ('analytics_db', 'postgresql', 'online', 12, 50, 2048),
        ('cache_server', 'redis', 'online', 234, 500, 512)
        ON DUPLICATE KEY UPDATE db_name = db_name");
    echo "<p class='success'>✓ Inserted sample databases</p>";

} catch (Exception $e) {
    echo "<p class='error'>✗ Error inserting sample data: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</div>";

// Verification
echo "<div class='box'><h2>Verification</h2>";

$allTables = $db->fetchAll("SHOW TABLES");
$tableNames = array_column($allTables, 'Tables_in_network_security_scanner');

$requiredTables = array_keys($tables);
$requiredTables[] = 'modules'; // Add modules table to check

echo "<h3>Table Status:</h3><ul>";
foreach ($requiredTables as $table) {
    if (in_array($table, $tableNames)) {
        echo "<li class='success'>✓ {$table}</li>";
    } else {
        echo "<li class='error'>✗ {$table} - MISSING</li>";
    }
}
echo "</ul>";

echo "</div>";

// Summary
echo "<div class='box'><h2>📊 Summary</h2>";
echo "<p><strong>Tables Created:</strong> {$created}</p>";
echo "<p><strong>Errors:</strong> {$errors}</p>";

if ($created >= 8 && $errors == 0) {
    echo "<p class='success' style='font-size: 18px;'>✅ All module tables successfully created!</p>";
    echo "<p style='margin-top: 20px;'>";
    echo "<a href='index.php' style='background: #667eea; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-size: 16px;'>🏠 Go to Dashboard →</a>";
    echo "</p>";
} else {
    echo "<p class='error'>Some tables failed to create. Check the errors above.</p>";
}

echo "</div>";

echo "</body></html>";
?>
