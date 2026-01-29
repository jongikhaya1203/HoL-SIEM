<?php
/**
 * Optimized Background Scan Runner
 * Uses parallel processing and batch operations
 * Usage: php run_scan_optimized.php <scan_id> <target> <type>
 */

// Log to file for debugging
$logFile = __DIR__ . '/scan_debug.log';
function logDebug($message) {
    global $logFile;
    file_put_contents($logFile, date('Y-m-d H:i:s') . ": " . $message . "\n", FILE_APPEND);
}

logDebug("========== Starting Optimized Scan ==========");
logDebug("Script started");

try {
    require_once __DIR__ . '/classes/Database.php';
    logDebug("Database.php loaded");

    require_once __DIR__ . '/classes/ParallelScanner.php';
    logDebug("ParallelScanner.php loaded");

    require_once __DIR__ . '/classes/ServiceDetector.php';
    logDebug("ServiceDetector.php loaded");

    require_once __DIR__ . '/classes/OptimizedVulnerabilityScanner.php';
    logDebug("OptimizedVulnerabilityScanner.php loaded");
} catch (Exception $e) {
    logDebug("ERROR loading classes: " . $e->getMessage());
    exit("Error loading classes\n");
}

// Get arguments
$scanId = $argv[1] ?? null;
$target = $argv[2] ?? null;
$type = $argv[3] ?? 'full';

logDebug("Arguments: scanId=$scanId, target=$target, type=$type");

if (!$scanId || !$target) {
    logDebug("ERROR: Missing required arguments");
    exit("Error: Missing required arguments\n");
}

try {
    $db = Database::getInstance();
    logDebug("Database instance created");
} catch (Exception $e) {
    logDebug("ERROR creating database instance: " . $e->getMessage());
    exit("Error creating database instance\n");
}

// Update progress function
function updateProgress($scanId, $progress, $message = '') {
    global $db;
    try {
        logDebug("updateProgress called: scanId=$scanId, progress=$progress, message=$message");
        $db->query(
            "UPDATE scans SET progress = ?, progress_message = ?, updated_at = NOW() WHERE id = ?",
            [$progress, $message, $scanId]
        );
        logDebug("updateProgress completed successfully");
    } catch (Exception $e) {
        logDebug("ERROR in updateProgress: " . $e->getMessage());
        throw $e;
    }
}

try {
    logDebug("Starting optimized scan ID: $scanId for target: $target");
    echo "Starting optimized scan ID: $scanId for target: $target\n";

    logDebug("Calling updateProgress with scanId=$scanId, progress=5");
    updateProgress($scanId, 5, 'Initializing scan');
    logDebug("Progress updated to 5%");

    // Parse target range
    $targets = [];
    if (filter_var($target, FILTER_VALIDATE_IP)) {
        $targets = [$target];
    } elseif (strpos($target, '/') !== false) {
        // Parse CIDR
        list($base, $mask) = explode('/', $target);
        $parts = explode('.', $base);
        $hostCount = pow(2, (32 - (int)$mask)) - 2;
        $limit = min($hostCount, 254);  // Safety limit

        for ($i = 1; $i <= $limit; $i++) {
            $targets[] = $parts[0] . '.' . $parts[1] . '.' . $parts[2] . '.' . $i;
        }
    } elseif (strpos($target, '-') !== false) {
        // Parse range like 192.168.1.1-10
        if (preg_match('/^(\d+\.\d+\.\d+\.)(\d+)-(\d+)$/', $target, $matches)) {
            for ($i = $matches[2]; $i <= $matches[3]; $i++) {
                $targets[] = $matches[1] . $i;
            }
        }
    } else {
        $targets = [$target];
    }

    $totalTargets = count($targets);
    echo "Total targets: $totalTargets\n";
    updateProgress($scanId, 10, "Checking $totalTargets hosts for availability");

    // STEP 1: Parallel host discovery
    $parallelScanner = new ParallelScanner();
    $parallelScanner->setTimeout(0.5);

    echo "Performing parallel host discovery...\n";
    $aliveHosts = $parallelScanner->checkHostsAlive($targets);
    $totalHosts = count($aliveHosts);

    echo "Found $totalHosts alive hosts\n";
    updateProgress($scanId, 20, "Found $totalHosts alive hosts, scanning ports");

    if ($totalHosts === 0) {
        updateProgress($scanId, 100, 'No alive hosts found');
        $db->query("UPDATE scans SET status = 'completed', completed_at = NOW(),
            total_hosts = 0, total_vulnerabilities = 0 WHERE id = ?", [$scanId]);
        exit("No alive hosts found\n");
    }

    // STEP 2: Port scanning with progress tracking
    $serviceDetector = new ServiceDetector();
    $vulnScanner = new OptimizedVulnerabilityScanner();

    $totalVulns = 0;
    $severityCounts = ['critical' => 0, 'high' => 0, 'medium' => 0, 'low' => 0, 'info' => 0];
    $processedHosts = 0;

    foreach ($aliveHosts as $ip) {
        $processedHosts++;
        $progress = 20 + (($processedHosts / $totalHosts) * 60);  // 20-80% for port scanning
        updateProgress($scanId, (int)$progress, "Scanning $ip ($processedHosts/$totalHosts)");

        echo "Scanning $ip...\n";

        // Parallel port scan
        $ports = $parallelScanner->quickScanParallel($ip);
        $openPorts = array_filter($ports, fn($p) => $p['state'] === 'open');

        echo "  Found " . count($openPorts) . " open ports\n";

        // Store host
        $db->query("INSERT INTO hosts (scan_id, ip_address, open_ports, status, last_seen)
            VALUES (?, ?, ?, 'online', NOW())", [$scanId, $ip, count($openPorts)]);
        $hostId = $db->lastInsertId();

        // Service detection and vulnerability scanning for open ports
        $services = [];
        foreach ($openPorts as &$port) {
            // Quick service detection
            if ($port['state'] === 'open') {
                $serviceInfo = $serviceDetector->detectService($ip, $port['port']);
                $port = array_merge($port, $serviceInfo);

                // Store port
                $db->query("INSERT INTO ports (host_id, port_number, protocol, state, service_name, service_version, service_banner)
                    VALUES (?, ?, ?, ?, ?, ?, ?)", [
                    $hostId, $port['port'], $port['protocol'] ?? 'tcp', $port['state'],
                    $port['service_name'] ?? null, $port['version'] ?? null, $port['banner'] ?? null
                ]);

                $services[] = $port;
            }
        }

        // Vulnerability scanning (batched)
        $vulns = $vulnScanner->scanHost($hostId, ['ip' => $ip], $services);
        $totalVulns += count($vulns);

        foreach ($vulns as $vuln) {
            $severityCounts[$vuln['severity']]++;
        }

        // Calculate risk score
        $riskScore = $vulnScanner->calculateRiskScore($vulns);
        $db->query("UPDATE hosts SET risk_score = ? WHERE id = ?", [$riskScore, $hostId]);

        echo "  Vulnerabilities: " . count($vulns) . ", Risk: $riskScore/10\n";
    }

    // Flush any remaining batched vulnerabilities
    $vulnScanner->flushBatch();

    updateProgress($scanId, 90, 'Finalizing scan results');

    // STEP 3: Complete scan
    $db->query("UPDATE scans SET status = 'completed', completed_at = NOW(), progress = 100,
        total_hosts = ?, total_vulnerabilities = ?,
        critical_count = ?, high_count = ?, medium_count = ?, low_count = ?, info_count = ?
        WHERE id = ?", [
        $totalHosts, $totalVulns,
        $severityCounts['critical'], $severityCounts['high'], $severityCounts['medium'],
        $severityCounts['low'], $severityCounts['info'], $scanId
    ]);

    echo "\n========================================\n";
    echo "Scan completed successfully!\n";
    echo "Hosts scanned: $totalHosts\n";
    echo "Vulnerabilities: $totalVulns\n";
    echo "  Critical: {$severityCounts['critical']}\n";
    echo "  High: {$severityCounts['high']}\n";
    echo "  Medium: {$severityCounts['medium']}\n";
    echo "  Low: {$severityCounts['low']}\n";
    echo "  Info: {$severityCounts['info']}\n";
    echo "========================================\n";

} catch (Exception $e) {
    logDebug("EXCEPTION CAUGHT: " . $e->getMessage());
    logDebug("Stack trace: " . $e->getTraceAsString());
    echo "Error: " . $e->getMessage() . "\n";
    updateProgress($scanId, 0, 'Scan failed: ' . $e->getMessage());
    $db->query("UPDATE scans SET status = 'failed', completed_at = NOW() WHERE id = ?", [$scanId]);
}
