<?php
/**
 * Fix Tenants Table - Add Missing Columns
 */
require_once __DIR__ . '/classes/Database.php';

$db = Database::getInstance();

echo "=== Fixing Tenants Table ===\n\n";

try {
    // Check existing columns
    echo "Step 1: Checking existing columns...\n";
    $columns = $db->fetchAll("SHOW COLUMNS FROM tenants", []);
    $columnNames = array_column($columns, 'Field');

    echo "Current columns: " . implode(', ', $columnNames) . "\n\n";

    // Add max_agents if missing
    if (!in_array('max_agents', $columnNames)) {
        echo "Step 2: Adding max_agents column...\n";
        $db->query("ALTER TABLE tenants ADD COLUMN max_agents INT DEFAULT 100 AFTER updated_at", []);
        echo "✓ Added max_agents column\n";
    } else {
        echo "Step 2: max_agents column already exists\n";
    }

    // Add max_scans_per_day if missing
    if (!in_array('max_scans_per_day', $columnNames)) {
        echo "Step 3: Adding max_scans_per_day column...\n";
        $db->query("ALTER TABLE tenants ADD COLUMN max_scans_per_day INT DEFAULT 1000 AFTER max_agents", []);
        echo "✓ Added max_scans_per_day column\n";
    } else {
        echo "Step 3: max_scans_per_day column already exists\n";
    }

    // Verify final structure
    echo "\nStep 4: Verifying final structure...\n";
    $finalColumns = $db->fetchAll("SHOW COLUMNS FROM tenants", []);
    foreach ($finalColumns as $col) {
        echo "  - {$col['Field']} ({$col['Type']})\n";
    }

    echo "\n✓ Tenants table fixed successfully!\n";

} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
