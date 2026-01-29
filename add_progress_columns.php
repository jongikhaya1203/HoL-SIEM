<?php
/**
 * Add progress tracking columns to scans table
 */

require_once __DIR__ . '/classes/Database.php';

$db = Database::getInstance();

echo "Adding progress tracking columns...\n\n";

$alterations = [
    "ALTER TABLE scans ADD COLUMN IF NOT EXISTS progress INT DEFAULT 0",
    "ALTER TABLE scans ADD COLUMN IF NOT EXISTS progress_message TEXT DEFAULT NULL",
    "ALTER TABLE scans ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP"
];

foreach ($alterations as $sql) {
    try {
        $db->query($sql);
        echo "✓ " . substr($sql, 0, 60) . "...\n";
    } catch (Exception $e) {
        // Column might already exist
        if (strpos($e->getMessage(), 'Duplicate') === false) {
            echo "✗ Error: " . $e->getMessage() . "\n";
        } else {
            echo "✓ Column already exists (skipped)\n";
        }
    }
}

echo "\n✓ Progress tracking columns added successfully!\n";
