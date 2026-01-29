<?php
/**
 * Add Performance Indexes
 * Run this once to dramatically improve database query performance
 */

require_once __DIR__ . '/classes/Database.php';

$db = Database::getInstance();

echo "Adding performance indexes to database...\n\n";

$indexes = [
    // Vulnerabilities table
    "CREATE INDEX IF NOT EXISTS idx_vuln_title ON vulnerabilities(title)",
    "CREATE INDEX IF NOT EXISTS idx_vuln_severity ON vulnerabilities(severity)",
    "CREATE INDEX IF NOT EXISTS idx_vuln_cvss ON vulnerabilities(cvss_score)",

    // Scan results table
    "CREATE INDEX IF NOT EXISTS idx_scanres_scan ON scan_results(scan_id)",
    "CREATE INDEX IF NOT EXISTS idx_scanres_host ON scan_results(host_id)",
    "CREATE INDEX IF NOT EXISTS idx_scanres_vuln ON scan_results(vulnerability_id)",
    "CREATE INDEX IF NOT EXISTS idx_scanres_status ON scan_results(status)",

    // Hosts table
    "CREATE INDEX IF NOT EXISTS idx_hosts_scan ON hosts(scan_id)",
    "CREATE INDEX IF NOT EXISTS idx_hosts_ip ON hosts(ip_address)",
    "CREATE INDEX IF NOT EXISTS idx_hosts_risk ON hosts(risk_score)",

    // Ports table
    "CREATE INDEX IF NOT EXISTS idx_ports_host ON ports(host_id)",
    "CREATE INDEX IF NOT EXISTS idx_ports_host_port ON ports(host_id, port_number)",
    "CREATE INDEX IF NOT EXISTS idx_ports_state ON ports(state)",

    // Scans table
    "CREATE INDEX IF NOT EXISTS idx_scans_status ON scans(status)",
    "CREATE INDEX IF NOT EXISTS idx_scans_created ON scans(created_at)",

    // Compliance checks
    "CREATE INDEX IF NOT EXISTS idx_compliance_scan ON compliance_checks(scan_id)",
    "CREATE INDEX IF NOT EXISTS idx_compliance_status ON compliance_checks(status)",

    // Mitigation plans
    "CREATE INDEX IF NOT EXISTS idx_mitigation_vuln ON mitigation_plans(vulnerability_id)"
];

$success = 0;
$failed = 0;

foreach ($indexes as $sql) {
    try {
        $db->query($sql);
        // Extract index name from SQL
        preg_match('/idx_\w+/', $sql, $matches);
        $indexName = $matches[0] ?? 'unknown';
        echo "✓ Created index: $indexName\n";
        $success++;
    } catch (Exception $e) {
        echo "✗ Failed: " . $e->getMessage() . "\n";
        $failed++;
    }
}

echo "\n";
echo "========================================\n";
echo "Indexing complete!\n";
echo "Success: $success\n";
echo "Failed: $failed\n";
echo "========================================\n";
echo "\nDatabase queries should now be significantly faster!\n";
