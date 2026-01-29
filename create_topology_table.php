<?php
/**
 * Create Network Topology Links Table
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Create Topology Table</title>";
echo "<style>
body { font-family: Arial; padding: 40px; background: #f5f5f5; }
.box { background: white; padding: 30px; border-radius: 8px; max-width: 800px; margin: 0 auto; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
h1 { color: #667eea; }
.success { color: green; font-weight: bold; }
.error { color: red; font-weight: bold; }
.btn { background: #667eea; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 20px; font-weight: 600; }
</style></head><body>";

echo "<div class='box'>";
echo "<h1>🔧 Creating Network Topology Links Table</h1>";

require_once 'classes/Database.php';

try {
    $db = Database::getInstance();

    echo "<p>Creating network_topology_links table...</p>";

    // Create the table
    $db->query("
        CREATE TABLE IF NOT EXISTS network_topology_links (
            id INT AUTO_INCREMENT PRIMARY KEY,
            source_device_id INT NOT NULL,
            target_device_id INT NOT NULL,
            link_type ENUM('physical', 'logical', 'wireless') DEFAULT 'physical',
            bandwidth_mbps INT DEFAULT NULL,
            link_status ENUM('up', 'down', 'degraded') DEFAULT 'up',
            protocol VARCHAR(50) DEFAULT NULL COMMENT 'CDP, LLDP, manual',
            discovered_via VARCHAR(50) DEFAULT 'manual',
            active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_source (source_device_id),
            INDEX idx_target (target_device_id),
            INDEX idx_active (active),
            FOREIGN KEY (source_device_id) REFERENCES network_devices(id) ON DELETE CASCADE,
            FOREIGN KEY (target_device_id) REFERENCES network_devices(id) ON DELETE CASCADE
        ) ENGINE=InnoDB
    ");

    echo "<p class='success'>✅ Table created successfully!</p>";

    // Check if we have devices to create sample links
    $deviceCount = $db->fetchOne("SELECT COUNT(*) as count FROM network_devices")['count'];

    if ($deviceCount > 0) {
        echo "<p>Found {$deviceCount} devices. Creating sample topology links...</p>";

        // Get some devices
        $devices = $db->fetchAll("SELECT id, device_type FROM network_devices LIMIT 10");

        if (count($devices) >= 2) {
            // Create some sample links between devices
            $linksCreated = 0;

            for ($i = 0; $i < count($devices) - 1; $i++) {
                try {
                    $db->query("
                        INSERT INTO network_topology_links
                        (source_device_id, target_device_id, link_type, bandwidth_mbps, link_status, discovered_via)
                        VALUES (?, ?, 'physical', 1000, 'up', 'auto-discovery')
                    ", [$devices[$i]['id'], $devices[$i + 1]['id']]);
                    $linksCreated++;
                } catch (Exception $e) {
                    // Link might already exist, continue
                }
            }

            echo "<p class='success'>✅ Created {$linksCreated} sample topology links</p>";
        }
    }

    // Verify table exists
    $tables = $db->fetchAll("SHOW TABLES LIKE 'network_topology_links'");

    if (count($tables) > 0) {
        echo "<p class='success' style='font-size: 18px; margin-top: 20px;'>✅ Network topology table is ready!</p>";
        echo "<p>You can now use the Network Topology Mapper.</p>";
    }

    echo "<p style='margin-top: 30px;'>";
    echo "<a href='modules/network_topology.php' class='btn'>🗺️ View Network Topology →</a>";
    echo "<a href='index.php' class='btn' style='background: #4CAF50; margin-left: 10px;'>🏠 Back to Dashboard</a>";
    echo "</p>";

} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";

    // Check if it's a foreign key constraint issue
    if (strpos($e->getMessage(), 'foreign key constraint') !== false) {
        echo "<p class='error'>The network_devices table might not exist. Please run create_module_tables.php first.</p>";
        echo "<p><a href='create_module_tables.php' class='btn'>Create Module Tables First</a></p>";
    }
}

echo "</div>";
echo "</body></html>";
?>
