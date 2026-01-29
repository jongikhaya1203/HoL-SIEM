<?php
// Clear opcode cache for api.php
if (function_exists('opcache_invalidate')) {
    $apiFile = __DIR__ . '/api.php';
    $scannerFile = __DIR__ . '/classes/NetworkScanner.php';

    opcache_invalidate($apiFile, true);
    opcache_invalidate($scannerFile, true);

    echo "Cache cleared for:\n";
    echo "- api.php\n";
    echo "- NetworkScanner.php\n";
} else {
    echo "OPcache not available\n";
}

echo "\nTesting output buffering...\n";
ob_start();
echo "This text should be hidden";
ob_end_clean();
echo json_encode(['status' => 'success', 'message' => 'Output buffering works!']);
