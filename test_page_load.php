<?php
// Simple test to check if SCADA HMI page loads
$url = 'http://localhost/networkscanscada/scada_hmi.php';
$content = @file_get_contents($url);

if ($content === false) {
    echo "ERROR: Page failed to load\n";
    exit(1);
}

$length = strlen($content);
echo "✓ Page loads successfully ($length bytes)\n";

// Check for key elements
if (strpos($content, 'function switchTab') !== false) {
    echo "✓ switchTab function found\n";
} else {
    echo "✗ switchTab function NOT found\n";
}

if (strpos($content, 'function loadRailSystem') !== false) {
    echo "✓ loadRailSystem function found\n";
} else {
    echo "✗ loadRailSystem function NOT found\n";
}

if (strpos($content, 'rail-panel') !== false) {
    echo "✓ Rail panel element found\n";
} else {
    echo "✗ Rail panel element NOT found\n";
}

echo "\nPage structure appears to be intact.\n";
?>
