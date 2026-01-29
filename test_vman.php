<?php
/**
 * VMAN Diagnostic Test
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>VMAN Diagnostic Test</h1>";
echo "<style>body{font-family:Arial;padding:20px;} .ok{color:green;} .err{color:red;} .warn{color:orange;}</style>";

// Test 1: Database connection
echo "<h2>1. Database Connection</h2>";
try {
    require_once 'classes/Database.php';
    $db = Database::getInstance();
    echo "<p class='ok'>✓ Database connected successfully</p>";
} catch (Exception $e) {
    echo "<p class='err'>✗ Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// Test 2: Check if VMAN tables exist
echo "<h2>2. VMAN Database Tables</h2>";
$tables = [
    'vman_hypervisors' => 'Hypervisors table',
    'vman_vms' => 'Virtual Machines table',
    'vman_snapshots' => 'Snapshots table',
    'vman_cloud_instances' => 'Cloud Instances table',
    'vman_cost_recommendations' => 'Cost Recommendations table',
    'vman_performance_history' => 'Performance History table'
];

$allTablesExist = true;
foreach ($tables as $table => $desc) {
    try {
        $result = $db->fetchOne("SELECT COUNT(*) as cnt FROM $table");
        echo "<p class='ok'>✓ $desc ($table) - {$result['cnt']} rows</p>";
    } catch (Exception $e) {
        echo "<p class='err'>✗ $desc ($table) - TABLE DOES NOT EXIST</p>";
        $allTablesExist = false;
    }
}

if (!$allTablesExist) {
    echo "<div style='background:#fff3cd;padding:15px;border-radius:5px;margin:20px 0;'>";
    echo "<h3>⚠️ Missing Tables!</h3>";
    echo "<p>You need to run the setup script first:</p>";
    echo "<p><a href='setup_vman_wpm_scm.php' style='background:#007bff;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>Run Setup Script</a></p>";
    echo "</div>";
}

// Test 3: Check PHP version
echo "<h2>3. PHP Version</h2>";
echo "<p>PHP Version: <strong>" . phpversion() . "</strong></p>";
if (version_compare(phpversion(), '7.4', '>=')) {
    echo "<p class='ok'>✓ PHP 7.4+ - Arrow functions supported</p>";
} else {
    echo "<p class='warn'>⚠ PHP < 7.4 - Arrow functions may not work (we've fixed this)</p>";
}

// Test 4: Test data retrieval
echo "<h2>4. Data Retrieval Test</h2>";
if ($allTablesExist) {
    try {
        $vmwareHosts = $db->fetchAll("SELECT *, hostname as host FROM vman_hypervisors WHERE platform = 'vmware' ORDER BY hostname");
        echo "<p class='ok'>✓ VMware Hosts: " . count($vmwareHosts) . " found</p>";

        $hypervHosts = $db->fetchAll("SELECT *, hostname as host FROM vman_hypervisors WHERE platform = 'hyperv' ORDER BY hostname");
        echo "<p class='ok'>✓ Hyper-V Hosts: " . count($hypervHosts) . " found</p>";

        $vms = $db->fetchAll("SELECT * FROM vman_vms ORDER BY vm_name");
        echo "<p class='ok'>✓ Virtual Machines: " . count($vms) . " found</p>";

        $cloudInstances = $db->fetchAll("SELECT * FROM vman_cloud_instances ORDER BY provider");
        echo "<p class='ok'>✓ Cloud Instances: " . count($cloudInstances) . " found</p>";

    } catch (Exception $e) {
        echo "<p class='err'>✗ Data retrieval error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

// Test 5: File check
echo "<h2>5. Module File Check</h2>";
$vmanFile = __DIR__ . '/modules/vman.php';
if (file_exists($vmanFile)) {
    echo "<p class='ok'>✓ vman.php exists (" . number_format(filesize($vmanFile)) . " bytes)</p>";
} else {
    echo "<p class='err'>✗ vman.php not found!</p>";
}

// Summary and Links
echo "<h2>6. Quick Links</h2>";
echo "<p><a href='setup_vman_wpm_scm.php' style='background:#28a745;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;margin-right:10px;'>Setup Database Tables</a>";
echo "<a href='modules/vman.php' style='background:#007bff;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;margin-right:10px;'>Open VMAN Module</a>";
echo "<a href='index.php' style='background:#6c757d;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>Go to Dashboard</a></p>";

echo "<h2>7. Recommended Steps</h2>";
echo "<ol>";
echo "<li>If tables are missing, click 'Setup Database Tables' above</li>";
echo "<li>After setup completes, click 'Open VMAN Module'</li>";
echo "<li>The module should now be fully functional with all tabs working</li>";
echo "</ol>";
?>
