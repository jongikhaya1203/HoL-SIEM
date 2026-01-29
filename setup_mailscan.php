<?php
/**
 * Email DLP System - Quick Setup Script
 * This script automates the initial setup process
 */

echo "=== Email DLP System - Quick Setup ===\n\n";

// Configuration
$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'mailscan_dlp';

echo "Step 1: Checking PHP extensions...\n";

// Check required PHP extensions
$required_extensions = ['pdo', 'pdo_mysql', 'mbstring'];
$missing_extensions = [];

foreach ($required_extensions as $ext) {
    if (!extension_loaded($ext)) {
        $missing_extensions[] = $ext;
        echo "  ✗ Missing: $ext\n";
    } else {
        echo "  ✓ Found: $ext\n";
    }
}

if (!empty($missing_extensions)) {
    die("\nError: Missing required PHP extensions. Please install: " . implode(', ', $missing_extensions) . "\n");
}

echo "\nStep 2: Connecting to MySQL...\n";

try {
    $pdo = new PDO("mysql:host=$dbHost", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "  ✓ Connected to MySQL server\n";
} catch (PDOException $e) {
    die("  ✗ Connection failed: " . $e->getMessage() . "\n\nPlease ensure:\n- XAMPP MySQL is running\n- Credentials are correct\n");
}

echo "\nStep 3: Creating database...\n";

try {
    // Check if database exists
    $stmt = $pdo->query("SHOW DATABASES LIKE '$dbName'");
    if ($stmt->rowCount() > 0) {
        echo "  ⚠ Database '$dbName' already exists\n";
        echo "  Do you want to drop and recreate it? (y/n): ";
        $handle = fopen("php://stdin", "r");
        $line = trim(fgets($handle));
        fclose($handle);

        if (strtolower($line) === 'y') {
            $pdo->exec("DROP DATABASE $dbName");
            echo "  ✓ Dropped existing database\n";
            $pdo->exec("CREATE DATABASE $dbName CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            echo "  ✓ Created new database '$dbName'\n";
        } else {
            echo "  → Using existing database\n";
        }
    } else {
        $pdo->exec("CREATE DATABASE $dbName CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "  ✓ Created database '$dbName'\n";
    }
} catch (PDOException $e) {
    die("  ✗ Database creation failed: " . $e->getMessage() . "\n");
}

echo "\nStep 4: Creating tables...\n";

try {
    // Connect to the new database
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Read and execute SQL file
    $sqlFile = __DIR__ . '/mailscan_db.sql';

    if (!file_exists($sqlFile)) {
        die("  ✗ SQL file not found: $sqlFile\n");
    }

    $sql = file_get_contents($sqlFile);

    // Remove CREATE DATABASE and USE statements (already done)
    $sql = preg_replace('/CREATE DATABASE.*?;/i', '', $sql);
    $sql = preg_replace('/USE.*?;/i', '', $sql);

    // Split into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));

    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $pdo->exec($statement);
        }
    }

    echo "  ✓ Tables created successfully\n";

    // Verify tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "  ✓ Created " . count($tables) . " tables: " . implode(', ', $tables) . "\n";

} catch (PDOException $e) {
    die("  ✗ Table creation failed: " . $e->getMessage() . "\n");
}

echo "\nStep 5: Verifying detection rules...\n";

try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM detection_rules");
    $ruleCount = $stmt->fetch()['count'];

    if ($ruleCount > 0) {
        echo "  ✓ Found $ruleCount detection rules\n";

        // Show rules by category
        $stmt = $pdo->query("SELECT category, COUNT(*) as count FROM detection_rules GROUP BY category");
        while ($row = $stmt->fetch()) {
            echo "    - {$row['category']}: {$row['count']} rule(s)\n";
        }
    } else {
        echo "  ⚠ No detection rules found (they will be created when you run the SQL file)\n";
    }
} catch (PDOException $e) {
    echo "  ⚠ Could not verify rules: " . $e->getMessage() . "\n";
}

echo "\nStep 6: System ready!\n\n";

echo "=== Setup Complete! ===\n\n";

echo "Next Steps:\n";
echo "1. Generate sample data:\n";
echo "   php generate_sample_data.php\n\n";

echo "2. Access the system:\n";
echo "   Dashboard:  http://localhost/networkscan/mailscan_dashboard.php\n";
echo "   Rules:      http://localhost/networkscan/mailscan_rules.php\n";
echo "   Scan Email: http://localhost/networkscan/mailscan_scan.php\n\n";

echo "3. Read the documentation:\n";
echo "   See MAILSCAN_README.md for complete usage guide\n\n";

echo "✓ Your Email DLP system is ready to use!\n";
?>
