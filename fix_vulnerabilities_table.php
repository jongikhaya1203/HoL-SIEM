<?php
/**
 * Fix Vulnerabilities Table - Add scan_id Column
 */
require_once __DIR__ . '/classes/Database.php';

$db = Database::getInstance();

echo "=== Fixing Vulnerabilities Table ===\n\n";

try {
    // Check existing columns
    echo "Step 1: Checking existing columns in vulnerabilities table...\n";
    $columns = $db->fetchAll("SHOW COLUMNS FROM vulnerabilities", []);
    $columnNames = array_column($columns, 'Field');

    echo "Current columns: " . implode(', ', $columnNames) . "\n\n";

    // Add scan_id column if missing
    if (!in_array('scan_id', $columnNames)) {
        echo "Step 2: Adding scan_id column...\n";

        // Add the column
        $db->query("ALTER TABLE vulnerabilities ADD COLUMN scan_id INT NOT NULL AFTER id", []);
        echo "✓ Added scan_id column\n";

        // Add index for better performance
        $db->query("ALTER TABLE vulnerabilities ADD INDEX idx_scan_id (scan_id)", []);
        echo "✓ Added index on scan_id\n";

        // Add foreign key constraint
        $db->query("ALTER TABLE vulnerabilities ADD FOREIGN KEY (scan_id) REFERENCES scans(id) ON DELETE CASCADE", []);
        echo "✓ Added foreign key constraint\n";
    } else {
        echo "Step 2: scan_id column already exists\n";
    }

    // Verify final structure
    echo "\nStep 3: Verifying final structure...\n";
    $finalColumns = $db->fetchAll("SHOW COLUMNS FROM vulnerabilities", []);
    foreach ($finalColumns as $col) {
        echo "  - {$col['Field']} ({$col['Type']})\n";
    }

    echo "\n✓ Vulnerabilities table fixed successfully!\n";

} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
