<?php
/**
 * Setup All Required Tables
 * Creates all missing database tables for IOC
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Database Setup</title>";
echo "<style>
body { font-family: Arial; padding: 20px; background: #f5f5f5; }
.success { color: green; font-weight: bold; }
.error { color: red; font-weight: bold; }
.warning { color: orange; }
h1 { color: #667eea; }
.box { background: white; padding: 20px; margin: 10px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
.btn { background: #667eea; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 5px; font-weight: 600; }
table { width: 100%; border-collapse: collapse; margin: 10px 0; }
th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
th { background: #f8f9fa; }
</style></head><body>";

echo "<h1>🔧 IOC Database Setup</h1>";

require_once 'classes/Database.php';

try {
    $db = Database::getInstance();
    echo "<div class='box'><p class='success'>✓ Database connected</p></div>";
} catch (Exception $e) {
    echo "<div class='box'><p class='error'>✗ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</p></div>";
    exit;
}

$tables = [
    'network_topology_links' => "CREATE TABLE IF NOT EXISTS network_topology_links (
        id INT AUTO_INCREMENT PRIMARY KEY,
        source_device_id INT NOT NULL,
        target_device_id INT NOT NULL,
        link_type ENUM('physical', 'logical', 'wireless') DEFAULT 'physical',
        bandwidth_mbps INT DEFAULT NULL,
        link_status ENUM('up', 'down', 'degraded') DEFAULT 'up',
        protocol VARCHAR(50) DEFAULT NULL,
        discovered_via VARCHAR(50) DEFAULT 'manual',
        active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_source (source_device_id),
        INDEX idx_target (target_device_id),
        INDEX idx_active (active)
    ) ENGINE=InnoDB",

    'performance_baselines' => "CREATE TABLE IF NOT EXISTS performance_baselines (
        id INT AUTO_INCREMENT PRIMARY KEY,
        device_id INT NOT NULL,
        metric_type VARCHAR(50) NOT NULL,
        mean DECIMAL(10,2) NOT NULL,
        median DECIMAL(10,2) NOT NULL,
        std_dev DECIMAL(10,2) NOT NULL,
        min_value DECIMAL(10,2) NOT NULL,
        max_value DECIMAL(10,2) NOT NULL,
        percentile_95 DECIMAL(10,2) NOT NULL,
        percentile_99 DECIMAL(10,2) NOT NULL,
        sample_size INT NOT NULL,
        calculated_at DATETIME NOT NULL,
        valid_until DATETIME NOT NULL,
        UNIQUE KEY unique_device_metric (device_id, metric_type),
        INDEX idx_device (device_id),
        INDEX idx_valid (valid_until)
    ) ENGINE=InnoDB",

    'alert_log' => "CREATE TABLE IF NOT EXISTS alert_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        severity ENUM('critical', 'warning', 'info') NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        alert_data JSON,
        created_at DATETIME NOT NULL,
        INDEX idx_severity (severity),
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB",

    'api_rate_limits' => "CREATE TABLE IF NOT EXISTS api_rate_limits (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id VARCHAR(255) NOT NULL,
        endpoint VARCHAR(255) NOT NULL,
        request_count INT DEFAULT 1,
        window_start DATETIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_client_endpoint (client_id, endpoint, window_start)
    ) ENGINE=InnoDB",

    'discovery_history' => "CREATE TABLE IF NOT EXISTS discovery_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        subnet VARCHAR(50) NOT NULL,
        devices_found INT DEFAULT 0,
        discovery_type VARCHAR(50) DEFAULT 'manual',
        status ENUM('running', 'completed', 'failed') DEFAULT 'running',
        started_at DATETIME NOT NULL,
        completed_at DATETIME DEFAULT NULL,
        INDEX idx_status (status),
        INDEX idx_started (started_at)
    ) ENGINE=InnoDB"
];

echo "<div class='box'><h2>Creating Tables...</h2>";
echo "<table><thead><tr><th>Table Name</th><th>Status</th></tr></thead><tbody>";

$created = 0;
$errors = 0;

foreach ($tables as $tableName => $sql) {
    try {
        $db->query($sql);
        echo "<tr><td>{$tableName}</td><td class='success'>✓ Created</td></tr>";
        $created++;
    } catch (Exception $e) {
        echo "<tr><td>{$tableName}</td><td class='error'>✗ " . htmlspecialchars($e->getMessage()) . "</td></tr>";
        $errors++;
    }
}

echo "</tbody></table></div>";

// Verify all tables exist
echo "<div class='box'><h2>Verification</h2>";

$allTables = $db->fetchAll("SHOW TABLES");
$tableNames = array_column($allTables, 'Tables_in_network_security_scanner');

echo "<p>Found " . count($tableNames) . " tables in database:</p>";
echo "<table><thead><tr><th>Table Name</th><th>Status</th></tr></thead><tbody>";

$requiredTables = array_merge(
    array_keys($tables),
    ['scans', 'hosts', 'vulnerabilities', 'settings', 'tasks', 'admin_users', 'modules', 'network_devices', 'performance_metrics']
);

foreach ($requiredTables as $table) {
    if (in_array($table, $tableNames)) {
        echo "<tr><td>{$table}</td><td class='success'>✓ Exists</td></tr>";
    } else {
        echo "<tr><td>{$table}</td><td class='warning'>⚠ Missing</td></tr>";
    }
}

echo "</tbody></table></div>";

// Summary
echo "<div class='box'><h2>📊 Summary</h2>";
echo "<p><strong>Tables Created:</strong> {$created}</p>";
echo "<p><strong>Errors:</strong> {$errors}</p>";

if ($errors == 0) {
    echo "<p class='success' style='font-size: 18px;'>✅ All tables created successfully!</p>";
} else {
    echo "<p class='warning'>Some tables had errors. Check above for details.</p>";
}

echo "<p style='margin-top: 20px;'>";
echo "<a href='modules/network_topology.php' class='btn'>🗺️ Network Topology</a>";
echo "<a href='index.php' class='btn' style='background: #4CAF50;'>🏠 Dashboard</a>";
echo "<a href='feature_roadmap.php' class='btn' style='background: #FF9800;'>🚀 Feature Roadmap</a>";
echo "</p>";

echo "</div>";

echo "</body></html>";
?>
