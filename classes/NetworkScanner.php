<?php
/**
 * Network Scanner - Main Orchestrator
 * Coordinates port scanning, service detection, and vulnerability assessment
 * Implements Gartner NDR best practices for network discovery and monitoring
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/PortScanner.php';
require_once __DIR__ . '/ServiceDetector.php';
require_once __DIR__ . '/VulnerabilityScanner.php';

class NetworkScanner {
    private $db;
    private $portScanner;
    private $serviceDetector;
    private $vulnerabilityScanner;
    private $scanId;
    private $silent = false;

    public function __construct($silent = false) {
        $this->db = Database::getInstance();
        $this->portScanner = new PortScanner();
        $this->serviceDetector = new ServiceDetector();
        $this->vulnerabilityScanner = new VulnerabilityScanner();
        $this->silent = $silent;
    }

    /**
     * Output message (only if not in silent mode)
     */
    private function output($message) {
        if (!$this->silent) {
            echo $message;
        }
    }

    /**
     * Start a new scan
     */
    public function startScan($targetRange, $scanType = 'full', $options = []) {
        // Create scan record
        $sql = "INSERT INTO scans (scan_name, scan_type, target_range, status, scan_options, started_at)
                VALUES (?, ?, ?, 'running', ?, NOW())";

        $scanName = $options['scan_name'] ?? 'Scan ' . date('Y-m-d H:i:s');

        $this->db->query($sql, [
            $scanName,
            $scanType,
            $targetRange,
            json_encode($options)
        ]);

        $this->scanId = $this->db->lastInsertId();

        // Log audit
        $this->logAudit('scan_started', 'scans', $this->scanId, "Started scan: {$targetRange}");

        return $this->scanId;
    }

    /**
     * Perform network scan
     */
    public function scan($targetRange, $scanType = 'full', $options = []) {
        $this->startScan($targetRange, $scanType, $options);

        $this->output("Starting network scan on: {$targetRange}\n");
        $this->output("Scan ID: {$this->scanId}\n");
        $this->output("Scan Type: {$scanType}\n\n");

        // Parse target range
        $targets = $this->parseTargetRange($targetRange);
        $totalHosts = 0;
        $totalVulnerabilities = 0;

        $severityCounts = [
            'critical' => 0,
            'high' => 0,
            'medium' => 0,
            'low' => 0,
            'info' => 0
        ];

        foreach ($targets as $target) {
            $this->output("Scanning host: {$target}\n");

            // Check if host is alive
            if (!$this->portScanner->isHostAlive($target)) {
                $this->output("  Host appears to be offline\n\n");
                continue;
            }

            $this->output("  Host is online\n");
            $totalHosts++;

            // Perform port scan
            $this->output("  Performing port scan...\n");
            $ports = $this->performPortScan($target, $scanType);
            $openPorts = array_filter($ports, fn($p) => $p['state'] === 'open');
            $this->output("  Found " . count($openPorts) . " open ports\n");

            // Create host record
            $hostId = $this->createHostRecord($target, count($openPorts));

            // Service detection
            $this->output("  Detecting services...\n");
            $services = [];
            foreach ($openPorts as &$port) {
                $serviceInfo = $this->serviceDetector->detectService($target, $port['port']);
                $port = array_merge($port, $serviceInfo);

                if ($serviceInfo['service_name']) {
                    $this->output("    Port {$port['port']}: {$serviceInfo['service_name']}");
                    if ($serviceInfo['version']) {
                        $this->output(" v{$serviceInfo['version']}");
                    }
                    $this->output("\n");
                }

                $services[] = $port;
                $this->storePortInfo($hostId, $port);
            }

            // Vulnerability scanning
            $this->output("  Scanning for vulnerabilities...\n");
            $vulnerabilities = $this->vulnerabilityScanner->scanHost(
                $hostId,
                ['ip' => $target],
                $services
            );

            $this->output("  Found " . count($vulnerabilities) . " potential vulnerabilities\n");
            $totalVulnerabilities += count($vulnerabilities);

            // Count severities
            foreach ($vulnerabilities as $vuln) {
                $severityCounts[$vuln['severity']]++;
            }

            // Calculate and update risk score
            $riskScore = $this->vulnerabilityScanner->calculateRiskScore($vulnerabilities);
            $this->updateHostRiskScore($hostId, $riskScore);

            $this->output("  Risk Score: {$riskScore}/10\n\n");
        }

        // Update scan completion
        $this->completeScan($totalHosts, $totalVulnerabilities, $severityCounts);

        $this->output("Scan completed!\n");
        $this->output("Total hosts scanned: {$totalHosts}\n");
        $this->output("Total vulnerabilities found: {$totalVulnerabilities}\n");
        $this->output("  Critical: {$severityCounts['critical']}\n");
        $this->output("  High: {$severityCounts['high']}\n");
        $this->output("  Medium: {$severityCounts['medium']}\n");
        $this->output("  Low: {$severityCounts['low']}\n");
        $this->output("  Info: {$severityCounts['info']}\n");

        return [
            'scan_id' => $this->scanId,
            'total_hosts' => $totalHosts,
            'total_vulnerabilities' => $totalVulnerabilities,
            'severity_counts' => $severityCounts
        ];
    }

    /**
     * Parse target range (supports single IP, CIDR, range)
     */
    private function parseTargetRange($targetRange) {
        $targets = [];

        // Single IP
        if (filter_var($targetRange, FILTER_VALIDATE_IP)) {
            return [$targetRange];
        }

        // CIDR notation (e.g., 192.168.1.0/24)
        if (strpos($targetRange, '/') !== false) {
            list($subnet, $mask) = explode('/', $targetRange);
            $targets = $this->getCIDRRange($subnet, $mask);
        }
        // Range notation (e.g., 192.168.1.1-10)
        elseif (preg_match('/^(\d+\.\d+\.\d+\.)(\d+)-(\d+)$/', $targetRange, $matches)) {
            $base = $matches[1];
            $start = (int)$matches[2];
            $end = (int)$matches[3];

            for ($i = $start; $i <= $end; $i++) {
                $targets[] = $base . $i;
            }
        }
        // Comma-separated IPs
        elseif (strpos($targetRange, ',') !== false) {
            $targets = array_map('trim', explode(',', $targetRange));
        }
        else {
            throw new Exception("Invalid target range format: {$targetRange}");
        }

        return $targets;
    }

    /**
     * Get all IPs in CIDR range
     */
    private function getCIDRRange($subnet, $mask) {
        $mask = (int)$mask;
        if ($mask < 8 || $mask > 32) {
            throw new Exception("CIDR mask must be between 8 and 32");
        }

        $range = [];
        $subnet = ip2long($subnet);
        $start = $subnet & ((-1 << (32 - $mask)));
        $end = $start + pow(2, (32 - $mask)) - 1;

        // Limit to 65535 hosts for safety (allows /16 networks)
        $hostCount = $end - $start;
        if ($hostCount > 65535) {
            throw new Exception("Target range too large. Maximum 65,535 hosts. Your range has approximately " . number_format($hostCount) . " hosts.");
        }

        // Skip network address (first) and broadcast address (last)
        for ($ip = $start + 1; $ip < $end; $ip++) {
            $range[] = long2ip($ip);
        }

        return $range;
    }

    /**
     * Perform port scan based on scan type
     */
    private function performPortScan($target, $scanType) {
        switch ($scanType) {
            case 'quick':
                return $this->portScanner->quickScan($target);

            case 'full':
                // Scan common ports + extended range
                $commonPorts = $this->portScanner->quickScan($target);
                $extendedPorts = $this->portScanner->scanPortRange($target, 1024, 10000);
                return array_merge($commonPorts, $extendedPorts);

            case 'custom':
                if (isset($options['ports'])) {
                    return $this->portScanner->scanPorts($target, $options['ports']);
                }
                return $this->portScanner->quickScan($target);

            default:
                return $this->portScanner->quickScan($target);
        }
    }

    /**
     * Create host record in database
     */
    private function createHostRecord($ipAddress, $openPorts) {
        $sql = "INSERT INTO hosts (scan_id, ip_address, open_ports, status, last_seen)
                VALUES (?, ?, ?, 'online', NOW())";

        $this->db->query($sql, [$this->scanId, $ipAddress, $openPorts]);

        return $this->db->lastInsertId();
    }

    /**
     * Store port information
     */
    private function storePortInfo($hostId, $portData) {
        $sql = "INSERT INTO ports (host_id, port_number, protocol, state, service_name, service_version, service_banner)
                VALUES (?, ?, ?, ?, ?, ?, ?)";

        $this->db->query($sql, [
            $hostId,
            $portData['port'],
            $portData['protocol'] ?? 'tcp',
            $portData['state'],
            $portData['service_name'] ?? null,
            $portData['version'] ?? null,
            $portData['banner'] ?? null
        ]);
    }

    /**
     * Update host risk score
     */
    private function updateHostRiskScore($hostId, $riskScore) {
        $sql = "UPDATE hosts SET risk_score = ? WHERE id = ?";
        $this->db->query($sql, [$riskScore, $hostId]);
    }

    /**
     * Complete scan
     */
    private function completeScan($totalHosts, $totalVulnerabilities, $severityCounts) {
        $sql = "UPDATE scans SET
                status = 'completed',
                completed_at = NOW(),
                total_hosts = ?,
                total_vulnerabilities = ?,
                critical_count = ?,
                high_count = ?,
                medium_count = ?,
                low_count = ?,
                info_count = ?
                WHERE id = ?";

        $this->db->query($sql, [
            $totalHosts,
            $totalVulnerabilities,
            $severityCounts['critical'],
            $severityCounts['high'],
            $severityCounts['medium'],
            $severityCounts['low'],
            $severityCounts['info'],
            $this->scanId
        ]);

        $this->logAudit('scan_completed', 'scans', $this->scanId,
            "Completed scan. Hosts: {$totalHosts}, Vulnerabilities: {$totalVulnerabilities}");
    }

    /**
     * Get scan results
     */
    public function getScanResults($scanId) {
        $scan = $this->db->fetchOne("SELECT * FROM scans WHERE id = ?", [$scanId]);

        if (!$scan) {
            throw new Exception("Scan not found: {$scanId}");
        }

        $hosts = $this->db->fetchAll(
            "SELECT * FROM hosts WHERE scan_id = ? ORDER BY risk_score DESC",
            [$scanId]
        );

        foreach ($hosts as &$host) {
            $host['ports'] = $this->db->fetchAll(
                "SELECT * FROM ports WHERE host_id = ? AND state = 'open'",
                [$host['id']]
            );

            $host['vulnerabilities'] = $this->db->fetchAll(
                "SELECT sr.*, v.title, v.severity, v.cvss_score, v.description
                 FROM scan_results sr
                 JOIN vulnerabilities v ON sr.vulnerability_id = v.id
                 WHERE sr.host_id = ?
                 ORDER BY v.cvss_score DESC",
                [$host['id']]
            );
        }

        $scan['hosts'] = $hosts;

        return $scan;
    }

    /**
     * Log audit entry
     */
    private function logAudit($action, $entityType, $entityId, $details) {
        $sql = "INSERT INTO audit_log (action, entity_type, entity_id, details, ip_address)
                VALUES (?, ?, ?, ?, ?)";

        $this->db->query($sql, [
            $action,
            $entityType,
            $entityId,
            $details,
            $_SERVER['REMOTE_ADDR'] ?? 'CLI'
        ]);
    }

    /**
     * Get scan statistics
     */
    public function getScanStatistics($scanId) {
        $scan = $this->getScanResults($scanId);

        return [
            'scan_id' => $scanId,
            'scan_name' => $scan['scan_name'],
            'total_hosts' => $scan['total_hosts'],
            'total_vulnerabilities' => $scan['total_vulnerabilities'],
            'severity_breakdown' => [
                'critical' => $scan['critical_count'],
                'high' => $scan['high_count'],
                'medium' => $scan['medium_count'],
                'low' => $scan['low_count'],
                'info' => $scan['info_count']
            ],
            'average_risk_score' => $this->calculateAverageRiskScore($scanId),
            'compliance_status' => $this->getComplianceStatus($scanId)
        ];
    }

    /**
     * Calculate average risk score
     */
    private function calculateAverageRiskScore($scanId) {
        $result = $this->db->fetchOne(
            "SELECT AVG(risk_score) as avg_score FROM hosts WHERE scan_id = ?",
            [$scanId]
        );

        return round($result['avg_score'] ?? 0, 2);
    }

    /**
     * Get compliance status
     */
    private function getComplianceStatus($scanId) {
        $checks = $this->db->fetchAll(
            "SELECT status, COUNT(*) as count FROM compliance_checks WHERE scan_id = ? GROUP BY status",
            [$scanId]
        );

        $status = [
            'pass' => 0,
            'fail' => 0,
            'partial' => 0,
            'not_tested' => 0
        ];

        foreach ($checks as $check) {
            $status[$check['status']] = $check['count'];
        }

        return $status;
    }
}
