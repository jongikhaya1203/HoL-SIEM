<?php
/**
 * Module Diagnostic Test
 * Check if modules are properly loaded
 */

echo "<!DOCTYPE html><html><head><title>Module Diagnostics</title>";
echo "<style>body { font-family: Arial; padding: 20px; background: #f5f5f5; }";
echo ".success { color: green; font-weight: bold; }";
echo ".error { color: red; font-weight: bold; }";
echo ".info { color: blue; }";
echo "pre { background: white; padding: 15px; border-radius: 5px; overflow-x: auto; }";
echo "</style></head><body>";

echo "<h1>🔍 Module System Diagnostics</h1>";

// Test 1: Check if Database class exists
echo "<h2>Test 1: Database Class</h2>";
if (file_exists(__DIR__ . '/classes/Database.php')) {
    echo "<p class='success'>✓ Database.php found</p>";
    require_once __DIR__ . '/classes/Database.php';
} else {
    echo "<p class='error'>✗ Database.php NOT found</p>";
    exit;
}

// Test 2: Database connection
echo "<h2>Test 2: Database Connection</h2>";
try {
    $db = Database::getInstance();
    echo "<p class='success'>✓ Database connection successful</p>";
} catch (Exception $e) {
    echo "<p class='error'>✗ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// Test 3: Check if modules table exists
echo "<h2>Test 3: Modules Table</h2>";
try {
    $tableCheck = $db->query("SHOW TABLES LIKE 'modules'");
    if ($tableCheck->rowCount() > 0) {
        echo "<p class='success'>✓ 'modules' table exists</p>";
    } else {
        echo "<p class='error'>✗ 'modules' table does NOT exist</p>";
        echo "<p class='info'>You need to import database/modules_tables.sql</p>";
        echo "<p>Run this command:<br><code>C:\\xampp\\mysql\\bin\\mysql.exe -u root -p network_security_scanner < database\\modules_tables.sql</code></p>";
        exit;
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Error checking table: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// Test 4: Count modules
echo "<h2>Test 4: Module Count</h2>";
try {
    $count = $db->fetchOne("SELECT COUNT(*) as count FROM modules");
    $moduleCount = $count['count'];

    if ($moduleCount > 0) {
        echo "<p class='success'>✓ Found {$moduleCount} modules in database</p>";
    } else {
        echo "<p class='error'>✗ No modules found in database (count = 0)</p>";
        echo "<p class='info'>The modules table exists but is empty. Import database/modules_tables.sql</p>";
        exit;
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Error counting modules: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// Test 5: List all modules
echo "<h2>Test 5: Module List</h2>";
try {
    $modules = $db->fetchAll("SELECT module_code, module_name, category, status, enabled FROM modules ORDER BY category, display_order");

    echo "<table border='1' cellpadding='8' style='background: white; border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #667eea; color: white;'>";
    echo "<th>Code</th><th>Name</th><th>Category</th><th>Status</th><th>Enabled</th>";
    echo "</tr>";

    foreach ($modules as $module) {
        $enabledIcon = $module['enabled'] ? '✓' : '✗';
        $enabledColor = $module['enabled'] ? 'green' : 'red';
        echo "<tr>";
        echo "<td>{$module['module_code']}</td>";
        echo "<td>{$module['module_name']}</td>";
        echo "<td>{$module['category']}</td>";
        echo "<td>{$module['status']}</td>";
        echo "<td style='color: {$enabledColor};'>{$enabledIcon}</td>";
        echo "</tr>";
    }
    echo "</table>";

} catch (Exception $e) {
    echo "<p class='error'>✗ Error listing modules: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// Test 6: Group by category
echo "<h2>Test 6: Modules by Category</h2>";
try {
    $modules = $db->fetchAll("SELECT * FROM modules WHERE enabled = 1 ORDER BY category, display_order");
    $modulesByCategory = [];
    foreach ($modules as $module) {
        $modulesByCategory[$module['category']][] = $module;
    }

    if (empty($modulesByCategory)) {
        echo "<p class='error'>✗ No enabled modules found</p>";
        echo "<p class='info'>All modules might be disabled. Check the 'enabled' column.</p>";
    } else {
        echo "<p class='success'>✓ Found " . count($modulesByCategory) . " categories with enabled modules</p>";
        echo "<ul>";
        foreach ($modulesByCategory as $category => $categoryModules) {
            echo "<li><strong>{$category}</strong>: " . count($categoryModules) . " modules</li>";
        }
        echo "</ul>";
    }

} catch (Exception $e) {
    echo "<p class='error'>✗ Error grouping modules: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// Test 7: Check all required tables
echo "<h2>Test 7: All Module Tables</h2>";
$requiredTables = [
    'modules',
    'module_metrics',
    'network_devices',
    'performance_metrics',
    'traffic_flows',
    'ip_addresses',
    'voip_calls',
    'monitored_applications',
    'monitored_databases'
];

$missingTables = [];
foreach ($requiredTables as $table) {
    try {
        $tableCheck = $db->query("SHOW TABLES LIKE '{$table}'");
        if ($tableCheck->rowCount() > 0) {
            echo "<p class='success'>✓ Table '{$table}' exists</p>";
        } else {
            echo "<p class='error'>✗ Table '{$table}' is missing</p>";
            $missingTables[] = $table;
        }
    } catch (Exception $e) {
        echo "<p class='error'>✗ Error checking table '{$table}': " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

if (!empty($missingTables)) {
    echo "<p class='error'><strong>Missing tables:</strong> " . implode(', ', $missingTables) . "</p>";
    echo "<p class='info'>Import database/modules_tables.sql to create these tables</p>";
}

// Summary
echo "<h2>📊 Summary</h2>";
echo "<ul>";
echo "<li><strong>Database Connection:</strong> <span class='success'>OK</span></li>";
echo "<li><strong>Modules Table:</strong> <span class='success'>Exists</span></li>";
echo "<li><strong>Total Modules:</strong> {$moduleCount}</li>";
echo "<li><strong>Enabled Modules:</strong> " . count($modules) . "</li>";
echo "<li><strong>Missing Tables:</strong> " . (empty($missingTables) ? '<span class="success">None</span>' : '<span class="error">' . count($missingTables) . '</span>') . "</li>";
echo "</ul>";

if ($moduleCount >= 18 && empty($missingTables)) {
    echo "<p class='success' style='font-size: 18px;'>🎉 ALL CHECKS PASSED! Modules should appear on the dashboard.</p>";
    echo "<p><a href='index.php' style='background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Dashboard →</a></p>";
} else {
    echo "<p class='error' style='font-size: 18px;'>⚠️ Issues detected. Please import database/modules_tables.sql</p>";
    echo "<p>Double-click: <strong>IMPORT_MODULES.bat</strong></p>";
}

echo "</body></html>";
?>
