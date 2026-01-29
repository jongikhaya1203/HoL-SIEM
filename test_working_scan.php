<?php
/**
 * Test scan to verify progress messages work
 * Run directly: php test_working_scan.php
 */
require_once __DIR__ . '/classes/Database.php';

// Create a fresh scan
$db = Database::getInstance();
$db->query(
    "INSERT INTO scans (scan_name, target_range, scan_type, status, progress, created_at)
     VALUES (?, ?, ?, 'running', 0, NOW())",
    ['Test Working Scan', '127.0.0.1', 'quick']
);
$scanId = $db->lastInsertId();

echo "Created scan ID: $scanId\n";
echo "Starting scan...\n\n";

// Now run the optimized scan
$phpPath = 'C:\\xampp\\php\\php.exe';
$scriptPath = __DIR__ . '\\run_scan_optimized.php';

$command = "\"$phpPath\" \"$scriptPath\" $scanId 127.0.0.1 quick";
echo "Executing: $command\n\n";

// Run synchronously to see output
passthru($command, $returnCode);

echo "\n\nReturn code: $returnCode\n";
echo "Check scan status at: http://localhost/networkscan/get_scan_status.php?scan_id=$scanId\n";
echo "View on dashboard: http://localhost/networkscan/index.php\n";
