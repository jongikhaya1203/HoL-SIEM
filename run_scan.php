<?php
/**
 * Background scan runner
 * Usage: php run_scan.php <scan_id> <target> <type>
 */

require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/classes/PortScanner.php';
require_once __DIR__ . '/classes/ServiceDetector.php';
require_once __DIR__ . '/classes/VulnerabilityScanner.php';

// Get arguments
$scanId = $argv[1] ?? null;
$target = $argv[2] ?? null;
$type = $argv[3] ?? 'full';

if (!$scanId || !$target) {
    exit("Error: Missing required arguments\n");
}

$db = Database::getInstance();

try {
    echo "Starting scan ID: $scanId for target: $target\n";

    // Parse target (simple implementation)
    $targets = [];
    if (filter_var($target, FILTER_VALIDATE_IP)) {
        $targets = [$target];
    } elseif (strpos($target, '/') !== false) {
        // Simple CIDR - just scan first 10 hosts for testing
        list($base) = explode('/', $target);
        $parts = explode('.', $base);
        for ($i = 1; $i <= 10; $i++) {
            $targets[] = $parts[0] . '.' . $parts[1] . '.' . $parts[2] . '.' . $i;
        }
    }

    $portScanner = new PortScanner();
    $serviceDetector = new ServiceDetector();
    $vulnScanner = new VulnerabilityScanner();

    $totalHosts = 0;
    $totalVulns = 0;
    $severityCounts = ['critical' => 0, 'high' => 0, 'medium' => 0, 'low' => 0, 'info' => 0];

    foreach ($targets as $ip) {
        if ($portScanner->isHostAlive($ip)) {
            $totalHosts++;

            // Quick scan
            $ports = $portScanner->quickScan($ip);
            $openPorts = array_filter($ports, fn($p) => $p['state'] === 'open');

            // Store host
            $db->query("INSERT INTO hosts (scan_id, ip_address, open_ports, status, last_seen)
                VALUES (?, ?, ?, 'online', NOW())", [$scanId, $ip, count($openPorts)]);
            $hostId = $db->lastInsertId();

            // Scan vulnerabilities
            $vulns = $vulnScanner->scanHost($hostId, ['ip' => $ip], $openPorts);
            $totalVulns += count($vulns);

            foreach ($vulns as $vuln) {
                $severityCounts[$vuln['severity']]++;
            }
        }
    }

    // Complete scan
    $db->query("UPDATE scans SET status = 'completed', completed_at = NOW(),
        total_hosts = ?, total_vulnerabilities = ?,
        critical_count = ?, high_count = ?, medium_count = ?, low_count = ?, info_count = ?
        WHERE id = ?", [
        $totalHosts, $totalVulns,
        $severityCounts['critical'], $severityCounts['high'], $severityCounts['medium'],
        $severityCounts['low'], $severityCounts['info'], $scanId
    ]);

    echo "Scan completed: $scanId\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    $db->query("UPDATE scans SET status = 'failed', completed_at = NOW() WHERE id = ?", [$scanId]);
}
