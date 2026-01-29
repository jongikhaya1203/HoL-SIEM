<?php
/**
 * Debug test for optimized scan
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');
set_time_limit(60);

echo "Testing Optimized Scan Components\n";
echo "==================================\n\n";

// Test 1: Check if files exist
echo "1. Checking if required files exist...\n";
$files = [
    'classes/Database.php',
    'classes/ParallelScanner.php',
    'classes/ServiceDetector.php',
    'classes/OptimizedVulnerabilityScanner.php'
];

foreach ($files as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        echo "   ✓ $file exists\n";
    } else {
        echo "   ✗ $file NOT FOUND\n";
    }
}

echo "\n2. Testing class loading...\n";
try {
    require_once __DIR__ . '/classes/Database.php';
    echo "   ✓ Database.php loaded\n";

    require_once __DIR__ . '/classes/ParallelScanner.php';
    echo "   ✓ ParallelScanner.php loaded\n";

    require_once __DIR__ . '/classes/ServiceDetector.php';
    echo "   ✓ ServiceDetector.php loaded\n";

    require_once __DIR__ . '/classes/OptimizedVulnerabilityScanner.php';
    echo "   ✓ OptimizedVulnerabilityScanner.php loaded\n";
} catch (Exception $e) {
    echo "   ✗ Error loading classes: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n3. Testing class instantiation...\n";
try {
    $db = Database::getInstance();
    echo "   ✓ Database instance created\n";

    $parallelScanner = new ParallelScanner();
    echo "   ✓ ParallelScanner instance created\n";

    $serviceDetector = new ServiceDetector();
    echo "   ✓ ServiceDetector instance created\n";

    $vulnScanner = new OptimizedVulnerabilityScanner();
    echo "   ✓ OptimizedVulnerabilityScanner instance created\n";
} catch (Exception $e) {
    echo "   ✗ Error instantiating classes: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n4. Testing parallel host check...\n";
try {
    $hosts = ['127.0.0.1'];
    $alive = $parallelScanner->checkHostsAlive($hosts);
    echo "   ✓ checkHostsAlive() executed\n";
    echo "   Result: " . count($alive) . " hosts alive out of " . count($hosts) . "\n";
    if (count($alive) > 0) {
        echo "   Alive: " . implode(', ', $alive) . "\n";
    }
} catch (Exception $e) {
    echo "   ✗ Error in checkHostsAlive(): " . $e->getMessage() . "\n";
}

echo "\n5. Testing parallel port scan...\n";
try {
    $ports = $parallelScanner->quickScanParallel('127.0.0.1');
    echo "   ✓ quickScanParallel() executed\n";
    $openPorts = array_filter($ports, fn($p) => $p['state'] === 'open');
    echo "   Result: " . count($openPorts) . " open ports found\n";
    foreach ($openPorts as $port) {
        echo "   - Port " . $port['port'] . ": " . $port['state'] . "\n";
    }
} catch (Exception $e) {
    echo "   ✗ Error in quickScanParallel(): " . $e->getMessage() . "\n";
}

echo "\n==================================\n";
echo "Test completed!\n";
