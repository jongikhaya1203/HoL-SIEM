<?php
/**
 * Populate Modules Database
 * Directly inserts all 18 modules into the database
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Populate Modules</title>";
echo "<style>
body { font-family: Arial; padding: 20px; background: #f5f5f5; }
.success { color: green; font-weight: bold; }
.error { color: red; font-weight: bold; }
.info { color: blue; }
h1 { color: #667eea; }
.box { background: white; padding: 20px; margin: 10px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
</style></head><body>";

echo "<h1>📦 Populating Modules Database</h1>";

// Load Database
require_once 'classes/Database.php';

try {
    $db = Database::getInstance();
    echo "<div class='box'><p class='success'>✓ Database connected</p></div>";
} catch (Exception $e) {
    echo "<div class='box'><p class='error'>✗ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</p></div>";
    exit;
}

// Check if modules table exists
echo "<div class='box'><h2>Checking Tables...</h2>";
try {
    $db->query("SELECT 1 FROM modules LIMIT 1");
    echo "<p class='success'>✓ Modules table exists</p>";
} catch (Exception $e) {
    echo "<p class='error'>✗ Modules table does not exist!</p>";
    echo "<p>Creating modules table...</p>";

    // Create modules table
    $createTable = "CREATE TABLE IF NOT EXISTS modules (
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
    ) ENGINE=InnoDB";

    $db->query($createTable);
    echo "<p class='success'>✓ Modules table created</p>";
}
echo "</div>";

// Define all 18 modules
$modules = [
    // Network Infrastructure
    ['NPM', 'Network Performance Monitor', 'network_infrastructure', 'Monitors network health, bandwidth utilization, and device performance in real-time', '🌐', 'active', 'partial', 'modules/npm.php', 1],
    ['NTA', 'NetFlow Traffic Analyzer', 'network_infrastructure', 'Analyzes network traffic patterns, bandwidth consumption, and top talkers', '📊', 'beta', 'partial', 'modules/nta.php', 2],
    ['IPAM', 'IP Address Manager', 'network_infrastructure', 'Manages IP addresses, DHCP, DNS configurations and subnet allocation', '🔢', 'active', 'partial', 'modules/ipam.php', 3],
    ['NCM', 'Network Configuration Manager', 'network_infrastructure', 'Automates network device configuration management and change tracking', '⚙️', 'coming_soon', 'placeholder', 'modules/ncm.php', 4],
    ['UDT', 'User Device Tracker', 'network_infrastructure', 'Tracks all devices connected to the network with switch port mapping', '📱', 'active', 'partial', 'modules/udt.php', 5],
    ['VNQM', 'VoIP Quality Manager', 'network_infrastructure', 'Monitors VoIP call quality, MOS scores, jitter, and packet loss', '📞', 'beta', 'partial', 'modules/vnqm.php', 6],

    // Systems & Application Management
    ['SAM', 'Server & Application Monitor', 'systems_applications', 'Tracks server health, application performance, and resource utilization', '💻', 'active', 'partial', 'modules/sam.php', 7],
    ['VMAN', 'Virtualization Manager', 'systems_applications', 'Manages and monitors virtual machines, hypervisors, and cloud resources', '☁️', 'active', 'full', 'modules/vman.php', 8],
    ['SRM', 'Storage Resource Monitor', 'systems_applications', 'Monitors storage performance, capacity, and IOPS across SAN/NAS', '💾', 'coming_soon', 'placeholder', 'modules/srm.php', 9],
    ['WPM', 'Web Performance Monitor', 'systems_applications', 'Tracks website and web application performance and availability', '🌍', 'active', 'full', 'modules/wpm.php', 10],
    ['SCM', 'Server Configuration Monitor', 'systems_applications', 'Monitors server configuration changes and drift detection', '🔧', 'active', 'full', 'modules/scm.php', 11],

    // Database Management
    ['DPA', 'Database Performance Analyzer', 'database', 'Provides deep insights into database performance bottlenecks and query optimization', '🗄️', 'active', 'partial', 'modules/dpa.php', 12],
    ['SQL_SENTRY', 'SQL Sentry', 'database', 'Advanced SQL Server 2016-2022 monitoring with wait-time analysis, Always On AGs, TempDB, and performance insights', '📈', 'active', 'full', 'modules/sql_sentry.php', 13],

    // IT Security
    ['SEM', 'Security Event Manager', 'security', 'Real-time threat detection, SIEM capabilities, and incident response', '🔒', 'active', 'full', 'modules/sem.php', 14],
    ['ARM', 'Access Rights Manager', 'security', 'Manages user access, permissions, and compliance reporting', '👥', 'coming_soon', 'placeholder', 'modules/arm.php', 15],

    // IT Service Management
    ['DRE', 'Remote Support', 'service_management', 'Remote IT support and system administration capabilities', '🖥️', 'coming_soon', 'placeholder', 'modules/dre.php', 16],
    ['SERVICE_DESK', 'IT Service Desk', 'service_management', 'IT service management, ticketing, and helpdesk solutions', '🎫', 'coming_soon', 'placeholder', 'modules/service_desk.php', 17],

    // Observability
    ['OBSERVABILITY', 'HoL SIEM Observability', 'observability', 'Unified monitoring for applications, infrastructure, logs, and traces', '👁️', 'beta', 'partial', 'modules/observability.php', 18]
];

// Insert modules
echo "<div class='box'><h2>Inserting Modules...</h2>";

$inserted = 0;
$errors = 0;

foreach ($modules as $module) {
    try {
        $sql = "INSERT INTO modules (module_code, module_name, category, description, icon, status, implementation_level, url, display_order, enabled)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
                ON DUPLICATE KEY UPDATE
                    module_name = VALUES(module_name),
                    description = VALUES(description),
                    icon = VALUES(icon),
                    status = VALUES(status),
                    implementation_level = VALUES(implementation_level),
                    url = VALUES(url),
                    display_order = VALUES(display_order)";

        $db->query($sql, [
            $module[0], // module_code
            $module[1], // module_name
            $module[2], // category
            $module[3], // description
            $module[4], // icon
            $module[5], // status
            $module[6], // implementation_level
            $module[7], // url
            $module[8]  // display_order
        ]);

        echo "<p class='success'>✓ Inserted: {$module[1]} ({$module[0]})</p>";
        $inserted++;

    } catch (Exception $e) {
        echo "<p class='error'>✗ Failed to insert {$module[1]}: " . htmlspecialchars($e->getMessage()) . "</p>";
        $errors++;
    }
}

echo "</div>";

// Verify
echo "<div class='box'><h2>Verification</h2>";

try {
    $count = $db->fetchOne("SELECT COUNT(*) as count FROM modules")['count'];
    echo "<p class='success'>✓ Total modules in database: <strong>{$count}</strong></p>";

    if ($count >= 18) {
        echo "<p class='success' style='font-size: 20px;'>🎉 SUCCESS! All {$count} modules are now in the database!</p>";
    } else {
        echo "<p class='error'>⚠️ Expected 18 modules but found {$count}</p>";
    }

    // Show by category
    $byCat = $db->fetchAll("SELECT category, COUNT(*) as count FROM modules GROUP BY category ORDER BY category");
    echo "<h3>Modules by Category:</h3><ul>";
    foreach ($byCat as $cat) {
        echo "<li><strong>{$cat['category']}</strong>: {$cat['count']} modules</li>";
    }
    echo "</ul>";

} catch (Exception $e) {
    echo "<p class='error'>✗ Error verifying: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</div>";

// Summary
echo "<div class='box'><h2>📊 Summary</h2>";
echo "<p><strong>Inserted:</strong> {$inserted} modules</p>";
echo "<p><strong>Errors:</strong> {$errors}</p>";

if ($inserted >= 18 && $errors == 0) {
    echo "<p class='success' style='font-size: 18px;'>✅ All modules successfully populated!</p>";
    echo "<p style='margin-top: 20px;'>";
    echo "<a href='index.php' style='background: #667eea; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-size: 16px;'>🏠 Go to Dashboard →</a>";
    echo "</p>";
} else {
    echo "<p class='error'>Some modules failed to insert. Check the errors above.</p>";
}

echo "</div>";

echo "</body></html>";
?>
