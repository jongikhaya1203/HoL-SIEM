<?php
/**
 * Database Connection Test
 * Quick script to verify database connectivity
 */

echo "Testing database connection...\n\n";

// Test 1: Check if config file exists
echo "1. Checking config file... ";
if (file_exists(__DIR__ . '/config/database.php')) {
    echo "✓ OK\n";
} else {
    echo "✗ FAILED - config/database.php not found\n";
    exit(1);
}

// Test 2: Load configuration
echo "2. Loading configuration... ";
try {
    $config = require __DIR__ . '/config/database.php';
    echo "✓ OK\n";
} catch (Exception $e) {
    echo "✗ FAILED - " . $e->getMessage() . "\n";
    exit(1);
}

// Test 3: Connect to MySQL server
echo "3. Connecting to MySQL server... ";
try {
    $dsn = sprintf(
        "mysql:host=%s;port=%s;charset=%s",
        $config['host'],
        $config['port'],
        $config['charset']
    );

    $pdo = new PDO(
        $dsn,
        $config['username'],
        $config['password'],
        $config['options']
    );
    echo "✓ OK\n";
} catch (PDOException $e) {
    echo "✗ FAILED\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "\nTroubleshooting:\n";
    echo "- Is MySQL/MariaDB running?\n";
    echo "- Are the credentials in config/database.php correct?\n";
    echo "- Try: mysql -u {$config['username']} -p\n";
    exit(1);
}

// Test 4: Check if database exists
echo "4. Checking database existence... ";
try {
    $stmt = $pdo->query("SHOW DATABASES LIKE '{$config['database']}'");
    $exists = $stmt->fetch();

    if ($exists) {
        echo "✓ OK (database exists)\n";
    } else {
        echo "⚠ WARNING - database doesn't exist\n";
        echo "   Run: mysql -u root -p < database/schema.sql\n";
        echo "   Or run: php setup.php\n";
    }
} catch (PDOException $e) {
    echo "✗ FAILED - " . $e->getMessage() . "\n";
}

// Test 5: Connect to specific database (if exists)
if ($exists) {
    echo "5. Connecting to database... ";
    try {
        $dsn = sprintf(
            "mysql:host=%s;port=%s;dbname=%s;charset=%s",
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset']
        );

        $pdo = new PDO(
            $dsn,
            $config['username'],
            $config['password'],
            $config['options']
        );
        echo "✓ OK\n";
    } catch (PDOException $e) {
        echo "✗ FAILED - " . $e->getMessage() . "\n";
        exit(1);
    }

    // Test 6: Check tables
    echo "6. Checking tables... ";
    try {
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (count($tables) > 0) {
            echo "✓ OK (" . count($tables) . " tables found)\n";

            $expectedTables = [
                'scans',
                'hosts',
                'ports',
                'vulnerabilities',
                'scan_results',
                'mitigation_plans',
                'reports',
                'compliance_frameworks',
                'compliance_controls',
                'compliance_checks',
                'scheduled_scans',
                'audit_log'
            ];

            $missingTables = array_diff($expectedTables, $tables);
            if (!empty($missingTables)) {
                echo "   ⚠ WARNING - Missing tables: " . implode(', ', $missingTables) . "\n";
                echo "   Re-import: mysql -u root -p {$config['database']} < database/schema.sql\n";
            }
        } else {
            echo "⚠ WARNING - no tables found\n";
            echo "   Import schema: mysql -u root -p {$config['database']} < database/schema.sql\n";
        }
    } catch (PDOException $e) {
        echo "✗ FAILED - " . $e->getMessage() . "\n";
    }

    // Test 7: Test Database class
    echo "7. Testing Database class... ";
    try {
        require_once __DIR__ . '/classes/Database.php';
        $db = Database::getInstance();
        $result = $db->fetchOne("SELECT COUNT(*) as count FROM vulnerabilities");
        echo "✓ OK (found {$result['count']} vulnerabilities)\n";
    } catch (Exception $e) {
        echo "✗ FAILED - " . $e->getMessage() . "\n";
    }
}

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";

if ($exists && count($tables) >= 10) {
    echo "✓ Database is ready! You can now use the scanner.\n";
    echo "\nNext steps:\n";
    echo "  - Web Interface: http://localhost/networkscan/\n";
    echo "  - CLI Scan: php scan_cli.php --target 127.0.0.1 --type quick\n";
} else {
    echo "⚠ Setup required!\n";
    echo "\nRun the setup wizard:\n";
    echo "  php setup.php\n";
    echo "\nOr import manually:\n";
    echo "  mysql -u root -p < database/schema.sql\n";
}

echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";
