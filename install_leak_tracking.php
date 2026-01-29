<?php
/**
 * Email Leak Tracking System - Installation Script
 */

echo "=== Email Leak Tracking System - Installation ===\n\n";

// Configuration
$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'mailscan_dlp';

echo "Step 1: Connecting to database...\n";

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "  ✓ Connected to database: $dbName\n";
} catch (PDOException $e) {
    die("  ✗ Connection failed: " . $e->getMessage() . "\n\nPlease ensure:\n- XAMPP MySQL is running\n- Database 'mailscan_dlp' exists (run install_database.php first)\n");
}

echo "\nStep 2: Reading SQL file...\n";

$sqlFile = __DIR__ . '/email_leak_tracking.sql';

if (!file_exists($sqlFile)) {
    die("  ✗ SQL file not found: $sqlFile\n");
}

$sql = file_get_contents($sqlFile);
echo "  ✓ SQL file loaded\n";

echo "\nStep 3: Installing leak tracking tables...\n";

try {
    // Remove USE statement
    $sql = preg_replace('/USE.*?;/i', '', $sql);

    // Split into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));

    $tableCount = 0;
    $insertCount = 0;

    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue;
        }

        $pdo->exec($statement);

        if (stripos($statement, 'CREATE TABLE') !== false) {
            $tableCount++;
            // Extract table name
            preg_match('/CREATE TABLE.*?`?(\w+)`?/i', $statement, $matches);
            $tableName = $matches[1] ?? 'unknown';
            echo "  ✓ Created table: $tableName\n";
        } elseif (stripos($statement, 'INSERT INTO') !== false) {
            $insertCount++;
        }
    }

    echo "\n  ✓ Created $tableCount tables\n";
    echo "  ✓ Inserted sample data ($insertCount statements)\n";

} catch (PDOException $e) {
    die("  ✗ Installation failed: " . $e->getMessage() . "\n");
}

echo "\nStep 4: Verifying installation...\n";

try {
    // Check tables
    $tables = ['email_forwarding_chains', 'email_recipients', 'leak_incidents', 'domain_classifications'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
        $count = $stmt->fetch()['count'];
        echo "  ✓ Table '$table' has $count rows\n";
    }
} catch (PDOException $e) {
    echo "  ⚠ Verification warning: " . $e->getMessage() . "\n";
}

echo "\n=== Installation Complete! ===\n\n";

echo "Next Steps:\n";
echo "1. Generate sample leak chain data:\n";
echo "   php generate_leak_chains.php\n\n";

echo "2. Access the leak tracker:\n";
echo "   http://localhost/networkscan/mailscan_leak_tracker.php\n\n";

echo "✓ Email leak tracking system ready!\n";
?>
