#!/usr/bin/env php
<?php
/**
 * Database Repair and Setup Script
 * Fixes common database issues
 */

echo "\n";
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║                                                              ║\n";
echo "║     🔧 Database Repair & Setup Tool 🔧                       ║\n";
echo "║                                                              ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo "\n";

$errors = [];
$warnings = [];
$success = [];

// Step 1: Check PHP version
echo "1. Checking PHP version... ";
if (version_compare(PHP_VERSION, '7.4.0', '>=')) {
    echo "✓ OK (" . PHP_VERSION . ")\n";
    $success[] = "PHP version compatible";
} else {
    echo "✗ FAILED\n";
    $errors[] = "PHP 7.4+ required. Current: " . PHP_VERSION;
}

// Step 2: Check required extensions
echo "2. Checking PHP extensions... ";
$required = ['pdo', 'pdo_mysql'];
$missing = [];
foreach ($required as $ext) {
    if (!extension_loaded($ext)) {
        $missing[] = $ext;
    }
}
if (empty($missing)) {
    echo "✓ OK\n";
    $success[] = "All required extensions present";
} else {
    echo "✗ MISSING: " . implode(', ', $missing) . "\n";
    $errors[] = "Missing extensions: " . implode(', ', $missing);
}

// Step 3: Check config file
echo "3. Checking config file... ";
$configFile = __DIR__ . '/config/database.php';
if (file_exists($configFile)) {
    echo "✓ FOUND\n";
    $success[] = "Config file exists";

    // Try to load it
    echo "   Loading config... ";
    try {
        $config = require $configFile;
        if (is_array($config)) {
            echo "✓ OK\n";
            $success[] = "Config file valid";
        } else {
            echo "✗ INVALID FORMAT\n";
            $errors[] = "Config file doesn't return array";
        }
    } catch (Exception $e) {
        echo "✗ ERROR: " . $e->getMessage() . "\n";
        $errors[] = "Config file error: " . $e->getMessage();
    }
} else {
    echo "✗ NOT FOUND\n";
    $errors[] = "Config file missing: {$configFile}";

    echo "   Creating default config... ";
    if (!is_dir(__DIR__ . '/config')) {
        mkdir(__DIR__ . '/config', 0755, true);
    }

    $defaultConfig = <<<'PHP'
<?php
/**
 * Database Configuration
 * Network Security Assessment Tool
 */

return [
    'host' => 'localhost',
    'port' => '3306',
    'database' => 'network_security_scanner',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_PERSISTENT => false
    ]
];
PHP;

    file_put_contents($configFile, $defaultConfig);
    echo "✓ CREATED\n";
    $success[] = "Default config created";

    // Reload config
    $config = require $configFile;
}

// Step 4: Test database connection
if (isset($config) && is_array($config)) {
    echo "4. Testing MySQL connection... ";
    try {
        $dsn = "mysql:host={$config['host']};port={$config['port']};charset={$config['charset']}";
        $pdo = new PDO($dsn, $config['username'], $config['password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "✓ OK\n";
        $success[] = "MySQL connection successful";
    } catch (PDOException $e) {
        echo "✗ FAILED\n";
        $errors[] = "Cannot connect to MySQL: " . $e->getMessage();
        echo "\n";
        echo "ERROR: " . $e->getMessage() . "\n";
        echo "\nTroubleshooting:\n";
        echo "1. Make sure MySQL is running (check XAMPP Control Panel)\n";
        echo "2. Verify username/password in config/database.php\n";
        echo "3. Check MySQL port (default: 3306)\n";
        echo "\n";
        exit(1);
    }

    // Step 5: Check if database exists
    echo "5. Checking database... ";
    try {
        $stmt = $pdo->query("SHOW DATABASES LIKE '{$config['database']}'");
        $dbExists = $stmt->fetch();

        if ($dbExists) {
            echo "✓ EXISTS\n";
            $success[] = "Database exists";
        } else {
            echo "✗ NOT FOUND\n";
            echo "   Creating database... ";

            $pdo->exec("CREATE DATABASE {$config['database']} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            echo "✓ CREATED\n";
            $success[] = "Database created";
        }
    } catch (PDOException $e) {
        echo "✗ ERROR: " . $e->getMessage() . "\n";
        $errors[] = "Database check failed";
    }

    // Step 6: Connect to database
    echo "6. Connecting to database... ";
    try {
        $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}";
        $pdo = new PDO($dsn, $config['username'], $config['password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "✓ OK\n";
        $success[] = "Database connection successful";
    } catch (PDOException $e) {
        echo "✗ FAILED: " . $e->getMessage() . "\n";
        $errors[] = "Cannot connect to database";
        exit(1);
    }

    // Step 7: Check tables
    echo "7. Checking tables... ";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $requiredTables = [
        'scans', 'hosts', 'ports', 'vulnerabilities', 'scan_results',
        'mitigation_plans', 'reports', 'compliance_frameworks',
        'compliance_controls', 'compliance_checks', 'scheduled_scans',
        'audit_log', 'network_segments'
    ];

    $missingTables = array_diff($requiredTables, $tables);

    if (empty($missingTables)) {
        echo "✓ OK (" . count($tables) . " tables)\n";
        $success[] = "All required tables present";
    } else {
        echo "✗ MISSING " . count($missingTables) . " tables\n";
        echo "   Missing: " . implode(', ', $missingTables) . "\n";
        $warnings[] = "Missing tables need to be imported";

        echo "\n   Do you want to import the schema now? (y/N): ";
        $handle = fopen("php://stdin", "r");
        $answer = trim(fgets($handle));
        fclose($handle);

        if (strtolower($answer) === 'y') {
            echo "   Importing schema.sql... ";

            $schemaFile = __DIR__ . '/database/schema.sql';
            if (!file_exists($schemaFile)) {
                echo "✗ FILE NOT FOUND\n";
                $errors[] = "schema.sql not found at: {$schemaFile}";
            } else {
                try {
                    $sql = file_get_contents($schemaFile);

                    // Remove USE statement
                    $sql = preg_replace('/USE\s+[^;]+;/', '', $sql);

                    // Execute
                    $pdo->exec($sql);

                    echo "✓ IMPORTED\n";
                    $success[] = "Schema imported successfully";
                } catch (PDOException $e) {
                    echo "✗ ERROR\n";
                    echo "   " . $e->getMessage() . "\n";
                    $errors[] = "Schema import failed: " . $e->getMessage();
                }
            }
        }
    }

    // Step 8: Check CMS tables
    echo "8. Checking CMS tables... ";
    $cmsTablesRequired = ['settings', 'tasks', 'task_comments', 'admin_users'];
    $missingCMS = array_diff($cmsTablesRequired, $tables);

    if (empty($missingCMS)) {
        echo "✓ OK\n";
        $success[] = "CMS tables present";
    } else {
        echo "⚠ MISSING: " . implode(', ', $missingCMS) . "\n";
        echo "   These are optional but needed for Admin Portal\n";
        $warnings[] = "CMS tables missing - import database/cms_tables.sql";
    }

    // Step 9: Verify critical data
    echo "9. Checking data... ";
    try {
        $vulnCount = $pdo->query("SELECT COUNT(*) FROM vulnerabilities")->fetchColumn();
        $frameworkCount = $pdo->query("SELECT COUNT(*) FROM compliance_frameworks")->fetchColumn();

        echo "✓ OK\n";
        echo "   Vulnerabilities: {$vulnCount}\n";
        echo "   Compliance Frameworks: {$frameworkCount}\n";
        $success[] = "Database contains data";
    } catch (PDOException $e) {
        echo "⚠ TABLES EMPTY OR MISSING\n";
        $warnings[] = "Database tables may be empty";
    }
}

// Summary
echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "SUMMARY\n";
echo "═══════════════════════════════════════════════════════════════\n";

if (!empty($success)) {
    echo "\n✓ SUCCESS (" . count($success) . "):\n";
    foreach ($success as $msg) {
        echo "  - {$msg}\n";
    }
}

if (!empty($warnings)) {
    echo "\n⚠ WARNINGS (" . count($warnings) . "):\n";
    foreach ($warnings as $msg) {
        echo "  - {$msg}\n";
    }
}

if (!empty($errors)) {
    echo "\n✗ ERRORS (" . count($errors) . "):\n";
    foreach ($errors as $msg) {
        echo "  - {$msg}\n";
    }
    echo "\n";
    echo "Please fix these errors before continuing.\n";
    exit(1);
}

echo "\n";

if (!empty($missingTables) || !empty($missingCMS)) {
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "NEXT STEPS\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "\n";

    if (!empty($missingTables)) {
        echo "1. Import main schema:\n";
        echo "   mysql -u root -p network_security_scanner < database/schema.sql\n\n";
    }

    if (!empty($missingCMS)) {
        echo "2. Import CMS tables (for Admin Portal):\n";
        echo "   mysql -u root -p network_security_scanner < database/cms_tables.sql\n\n";
    }

    echo "3. Run this script again to verify:\n";
    echo "   php fix_database.php\n\n";
} else {
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "✓ DATABASE IS READY!\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "\n";
    echo "You can now:\n";
    echo "1. Access dashboard: http://localhost/networkscan/\n";
    echo "2. Run a scan: php scan_cli.php --target 127.0.0.1 --type quick\n";
    echo "3. Access admin portal: http://localhost/networkscan/admin/\n";
    echo "\n";
}
