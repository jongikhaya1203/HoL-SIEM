<?php
require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/classes/NetworkScanner.php';

echo "Testing silent mode...\n\n";

echo "=== Test 1: Normal mode (with output) ===\n";
$scanner1 = new NetworkScanner(false);
echo "Scanner created in normal mode\n\n";

echo "=== Test 2: Silent mode (no output) ===\n";
$scanner2 = new NetworkScanner(true);
echo "Scanner created in silent mode\n";
echo "If you see this, silent mode works!\n\n";

echo "=== Test 3: JSON output ===\n";
ob_start();
echo "This should be hidden";
ob_clean();
echo json_encode(['success' => true, 'message' => 'Clean JSON output!']);
echo "\n";
