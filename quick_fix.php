<?php
/**
 * Quick Fix Script
 * Shows errors and displays what's happening
 */

// Enable error display
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Quick Fix</title>";
echo "<style>
body { font-family: Arial; padding: 20px; background: #f5f5f5; }
.box { background: white; padding: 20px; margin: 10px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
.success { color: green; }
.error { color: red; }
.warning { color: orange; }
h1 { color: #667eea; }
button { background: #667eea; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 5px; }
button:hover { background: #764ba2; }
</style></head><body>";

echo "<h1>🔧 Network Scanner - Quick Fix</h1>";

// Step 1: Check Database Connection
echo "<div class='box'>";
echo "<h2>Step 1: Database Connection</h2>";

if (!file_exists('classes/Database.php')) {
    echo "<p class='error'>❌ Database.php not found!</p>";
    exit;
}

require_once 'classes/Database.php';

try {
    $db = Database::getInstance();
    echo "<p class='success'>✅ Database connected successfully</p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Make sure MySQL is running in XAMPP Control Panel</p>";
    exit;
}
echo "</div>";

// Step 2: Check Tables
echo "<div class='box'>";
echo "<h2>Step 2: Check Required Tables</h2>";

$existingTables = $db->fetchAll("SHOW TABLES");
$tableNames = array_column($existingTables, 'Tables_in_network_security_scanner');

$requiredTables = ['scans', 'hosts', 'vulnerabilities', 'settings', 'tasks', 'admin_users', 'modules'];
$missingTables = [];

foreach ($requiredTables as $table) {
    if (in_array($table, $tableNames)) {
        echo "<p class='success'>✅ Table '{$table}' exists</p>";
    } else {
        echo "<p class='error'>❌ Table '{$table}' is MISSING</p>";
        $missingTables[] = $table;
    }
}

if (in_array('modules', $missingTables)) {
    echo "<p class='warning'>⚠️ <strong>modules</strong> table is missing - this is why modules don't show on dashboard!</p>";
    echo "<p><strong>Solution:</strong> Import database/modules_tables.sql</p>";
    echo "<button onclick=\"location.href='IMPORT_MODULES.bat'\">Download IMPORT_MODULES.bat</button>";
}

if (in_array('settings', $missingTables) || in_array('tasks', $missingTables) || in_array('admin_users', $missingTables)) {
    echo "<p class='warning'>⚠️ CMS tables are missing - Admin portal won't work properly!</p>";
    echo "<p><strong>Solution:</strong> Import database/cms_tables.sql</p>";
}

echo "</div>";

// Step 3: Check Modules
if (!in_array('modules', $missingTables)) {
    echo "<div class='box'>";
    echo "<h2>Step 3: Module Status</h2>";

    try {
        $moduleCount = $db->fetchOne("SELECT COUNT(*) as count FROM modules")['count'];

        if ($moduleCount == 0) {
            echo "<p class='error'>❌ Modules table exists but is EMPTY (0 modules)</p>";
            echo "<p class='warning'>The modules_tables.sql file imported the table structure but no data!</p>";
            echo "<p><strong>Solution:</strong> Re-import database/modules_tables.sql</p>";
        } else {
            echo "<p class='success'>✅ Found {$moduleCount} modules in database</p>";

            $enabledCount = $db->fetchOne("SELECT COUNT(*) as count FROM modules WHERE enabled = 1")['count'];
            echo "<p class='success'>✅ {$enabledCount} modules are enabled</p>";

            if ($enabledCount == 0) {
                echo "<p class='warning'>⚠️ No modules are enabled! Enable them:</p>";
                echo "<button onclick=\"if(confirm('Enable all modules?')) location.href='quick_fix.php?action=enable_modules'\">Enable All Modules</button>";
            }
        }

    } catch (Exception $e) {
        echo "<p class='error'>❌ Error checking modules: " . htmlspecialchars($e->getMessage()) . "</p>";
    }

    echo "</div>";
}

// Step 4: Check Settings
if (!in_array('settings', $missingTables)) {
    echo "<div class='box'>";
    echo "<h2>Step 4: CMS Settings</h2>";

    try {
        $settingsCount = $db->fetchOne("SELECT COUNT(*) as count FROM settings")['count'];

        if ($settingsCount == 0) {
            echo "<p class='warning'>⚠️ Settings table exists but is EMPTY</p>";
            echo "<p>CMS won't display properly without settings. Creating default settings...</p>";

            $db->query("INSERT INTO settings (setting_key, setting_value, setting_type) VALUES
                ('app_name', 'Network Security Scanner', 'text'),
                ('logo_url', '', 'text'),
                ('theme_color', '#667eea', 'color')
                ON DUPLICATE KEY UPDATE setting_key = setting_key");

            echo "<p class='success'>✅ Created default settings</p>";
        } else {
            echo "<p class='success'>✅ CMS settings configured ({$settingsCount} settings)</p>";
        }

    } catch (Exception $e) {
        echo "<p class='error'>❌ Error with settings: " . htmlspecialchars($e->getMessage()) . "</p>";
    }

    echo "</div>";
}

// Step 5: Actions
if (isset($_GET['action']) && $_GET['action'] == 'enable_modules') {
    echo "<div class='box'>";
    echo "<h2>Enabling All Modules...</h2>";
    try {
        $db->query("UPDATE modules SET enabled = 1");
        echo "<p class='success'>✅ All modules enabled!</p>";
        echo "<script>setTimeout(() => location.href='quick_fix.php', 2000);</script>";
    } catch (Exception $e) {
        echo "<p class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    echo "</div>";
}

// Final Summary
echo "<div class='box'>";
echo "<h2>📊 Summary & Next Steps</h2>";

if (empty($missingTables)) {
    echo "<p class='success' style='font-size: 18px;'>✅ All required tables exist!</p>";

    if (!in_array('modules', $missingTables)) {
        $moduleCount = $db->fetchOne("SELECT COUNT(*) as count FROM modules")['count'] ?? 0;

        if ($moduleCount >= 18) {
            echo "<p class='success' style='font-size: 18px;'>🎉 Everything looks good!</p>";
            echo "<p><button onclick=\"location.href='index.php'\">Go to Dashboard →</button></p>";
        } else {
            echo "<p class='warning'>⚠️ Expected 18 modules but found {$moduleCount}</p>";
            echo "<p>Import database/modules_tables.sql again</p>";
        }
    }

} else {
    echo "<p class='error' style='font-size: 18px;'>❌ Missing tables: " . implode(', ', $missingTables) . "</p>";

    echo "<h3>Import Required:</h3>";
    echo "<ol>";

    if (in_array('scans', $missingTables) || in_array('hosts', $missingTables)) {
        echo "<li><strong>Main schema:</strong> Import <code>database/schema.sql</code></li>";
    }

    if (in_array('settings', $missingTables) || in_array('tasks', $missingTables)) {
        echo "<li><strong>CMS tables:</strong> Import <code>database/cms_tables.sql</code></li>";
    }

    if (in_array('modules', $missingTables)) {
        echo "<li><strong>Module system:</strong> Import <code>database/modules_tables.sql</code></li>";
    }

    echo "</ol>";

    echo "<p><strong>How to import:</strong></p>";
    echo "<ol>";
    echo "<li>Double-click <code>IMPORT_MODULES.bat</code> in networkscan folder</li>";
    echo "<li>OR use phpMyAdmin to import the SQL files</li>";
    echo "<li>OR run command line: <code>C:\\xampp\\mysql\\bin\\mysql.exe -u root -p network_security_scanner < database\\[filename].sql</code></li>";
    echo "</ol>";
}

echo "<p style='margin-top: 20px;'>";
echo "<button onclick=\"location.reload()\">🔄 Refresh This Page</button>";
echo "<button onclick=\"location.href='test_modules.php'\">🔍 Detailed Diagnostics</button>";
echo "<button onclick=\"location.href='index.php'\">🏠 Go to Dashboard</button>";
echo "</p>";

echo "</div>";

echo "</body></html>";
?>
