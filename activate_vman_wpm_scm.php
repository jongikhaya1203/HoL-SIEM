<?php
/**
 * Activate VMAN, WPM, and SCM Modules
 * Updates the modules table to set these modules as active
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Activate Modules</title>";
echo "<style>
body { font-family: Arial; padding: 20px; background: #1a1a2e; color: #eee; }
.success { color: #4CAF50; font-weight: bold; }
.error { color: #f44336; font-weight: bold; }
.info { color: #2196F3; }
h1 { color: #667eea; }
.box { background: #16213e; padding: 20px; margin: 10px 0; border-radius: 8px; border: 1px solid #0f3460; }
a.btn { display: inline-block; background: #667eea; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; margin-top: 20px; }
a.btn:hover { background: #5a6fd6; }
</style></head><body>";

echo "<h1>🚀 Activating VMAN, WPM & SCM Modules</h1>";

require_once 'classes/Database.php';

try {
    $db = Database::getInstance();
    echo "<div class='box'><p class='success'>✓ Database connected</p></div>";
} catch (Exception $e) {
    echo "<div class='box'><p class='error'>✗ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</p></div>";
    exit;
}

echo "<div class='box'><h2>Updating Module Status...</h2>";

$updates = [
    ['VMAN', 'Virtualization Manager', 'active', 'full'],
    ['WPM', 'Web Performance Monitor', 'active', 'full'],
    ['SCM', 'Server Configuration Monitor', 'active', 'full'],
    ['SQL_SENTRY', 'SQL Sentry', 'active', 'full']
];

$success = 0;
$errors = 0;

foreach ($updates as $update) {
    try {
        $sql = "UPDATE modules SET status = ?, implementation_level = ? WHERE module_code = ?";
        $db->query($sql, [$update[2], $update[3], $update[0]]);

        // Check if update was successful
        $module = $db->fetchOne("SELECT * FROM modules WHERE module_code = ?", [$update[0]]);

        if ($module && $module['status'] === 'active') {
            echo "<p class='success'>✓ {$update[1]} ({$update[0]}) - Status: <strong>ACTIVE</strong>, Implementation: <strong>FULL</strong></p>";
            $success++;
        } else {
            echo "<p class='info'>⚠ {$update[1]} ({$update[0]}) - Module may not exist in database</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>✗ Failed to update {$update[1]}: " . htmlspecialchars($e->getMessage()) . "</p>";
        $errors++;
    }
}

echo "</div>";

// Show current status of all modules
echo "<div class='box'><h2>Current Module Status</h2>";
echo "<table style='width: 100%; border-collapse: collapse;'>";
echo "<tr style='background: #0f3460;'><th style='padding: 10px; text-align: left;'>Module</th><th style='padding: 10px;'>Status</th><th style='padding: 10px;'>Implementation</th></tr>";

try {
    $modules = $db->fetchAll("SELECT module_code, module_name, status, implementation_level FROM modules ORDER BY display_order");
    foreach ($modules as $mod) {
        $statusColor = match($mod['status']) {
            'active' => '#4CAF50',
            'beta' => '#2196F3',
            'coming_soon' => '#FF9800',
            default => '#9E9E9E'
        };
        $implColor = match($mod['implementation_level']) {
            'full' => '#4CAF50',
            'partial' => '#2196F3',
            'placeholder' => '#FF9800',
            default => '#9E9E9E'
        };
        echo "<tr style='border-bottom: 1px solid #0f3460;'>";
        echo "<td style='padding: 10px;'><strong>{$mod['module_code']}</strong> - {$mod['module_name']}</td>";
        echo "<td style='padding: 10px; text-align: center;'><span style='color: {$statusColor}; font-weight: bold;'>" . strtoupper($mod['status']) . "</span></td>";
        echo "<td style='padding: 10px; text-align: center;'><span style='color: {$implColor};'>" . ucfirst($mod['implementation_level']) . "</span></td>";
        echo "</tr>";
    }
} catch (Exception $e) {
    echo "<tr><td colspan='3' class='error'>Error fetching modules: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
}

echo "</table></div>";

// Summary
echo "<div class='box'><h2>📊 Summary</h2>";
echo "<p><strong>Updated:</strong> {$success} modules</p>";
echo "<p><strong>Errors:</strong> {$errors}</p>";

if ($success > 0 && $errors == 0) {
    echo "<p class='success' style='font-size: 18px;'>✅ Modules successfully activated!</p>";
    echo "<p>The following modules are now fully functional:</p>";
    echo "<ul>";
    echo "<li><strong>VMAN</strong> - Virtualization Manager (VMs, Hypervisors, Cloud Resources)</li>";
    echo "<li><strong>WPM</strong> - Web Performance Monitor (Website Monitoring, SSL Checks)</li>";
    echo "<li><strong>SCM</strong> - Server Configuration Monitor (Config Tracking, Drift Detection)</li>";
    echo "</ul>";
}

echo "<a class='btn' href='index.php'>🏠 Go to Dashboard</a>";
echo " <a class='btn' href='index.php?module=vman' style='background: #4CAF50;'>☁️ Open VMAN</a>";
echo " <a class='btn' href='index.php?module=wpm' style='background: #2196F3;'>🌍 Open WPM</a>";
echo " <a class='btn' href='index.php?module=scm' style='background: #FF9800;'>🔧 Open SCM</a>";

echo "</div>";

echo "</body></html>";
?>
