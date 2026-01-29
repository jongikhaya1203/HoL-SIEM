<?php
/**
 * SCADA Installation Quick Fix
 * Run this if you encounter installation errors
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'localhost';
$username = 'root';
$password = '';
$database = 'network_security';

echo "<!DOCTYPE html><html><head><title>SCADA Install Fix</title>";
echo "<style>body{font-family:monospace;background:#1a1a2e;color:#0f0;padding:20px;}";
echo ".err{color:#f00;}.warn{color:#fa0;}.ok{color:#0f0;}</style></head><body>";
echo "<h2>SCADA Installation Fix</h2><pre>";

try {
    $conn = new mysqli($host, $username, $password, $database);

    if ($conn->connect_error) {
        die("<span class='err'>Connection failed: " . $conn->connect_error . "</span>");
    }

    echo "<span class='ok'>✓ Connected to database</span>\n\n";

    // Drop problematic tables
    echo "Dropping tables that may have issues...\n";
    $tables = [
        'scada_emergency_shutdown',
        'scada_permissives',
        'scada_interlock_rules',
        'scada_alarm_history',
        'scada_tag_history',
        'scada_control_actions',
        'scada_calibration_records',
        'scada_valve_status',
        'scada_tank_levels',
        'scada_tags',
        'scada_rtus',
        'scada_plcs',
        'scada_instruments',
        'scada_assets',
        'scada_sites',
        'scada_protocols',
        'scada_industry_configs'
    ];

    foreach ($tables as $table) {
        $conn->query("DROP TABLE IF EXISTS $table");
        echo "  - Dropped $table\n";
    }

    echo "<span class='ok'>✓ Tables dropped</span>\n\n";

    echo "Now run the installer:\n";
    echo "<a href='install_scada_simple.php' style='color:#0ff;font-size:18px;'>→ Click Here to Run Installer</a>\n";

    $conn->close();

} catch (Exception $e) {
    echo "<span class='err'>ERROR: " . $e->getMessage() . "</span>\n";
}

echo "</pre></body></html>";
?>
