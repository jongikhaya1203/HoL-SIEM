<?php
/**
 * Report Generator Class
 * Generates comprehensive security assessment reports
 * Implements Gartner best practices for vulnerability reporting
 */

require_once __DIR__ . '/Database.php';

class ReportGenerator {
    private $db;
    private $reportId;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Generate comprehensive report
     */
    public function generateReport($scanId, $reportType = 'full', $format = 'html') {
        $scan = $this->getScanData($scanId);

        if (!$scan) {
            throw new Exception("Scan not found: {$scanId}");
        }

        // Create report record
        $this->createReportRecord($scanId, $reportType, $format);

        // Generate report based on type
        switch ($reportType) {
            case 'executive':
                $content = $this->generateExecutiveReport($scan);
                break;

            case 'technical':
                $content = $this->generateTechnicalReport($scan);
                break;

            case 'compliance':
                $content = $this->generateComplianceReport($scan);
                break;

            case 'full':
            default:
                $content = $this->generateFullReport($scan);
                break;
        }

        // Format output
        switch ($format) {
            case 'html':
                $output = $this->formatHTML($content, $scan);
                break;

            case 'json':
                $output = $this->formatJSON($content, $scan);
                break;

            case 'csv':
                $output = $this->formatCSV($content, $scan);
                break;

            default:
                $output = $content;
        }

        // Save report file
        $filePath = $this->saveReport($output, $format);

        // Update report record
        $this->updateReportRecord($filePath, strlen($output));

        return [
            'report_id' => $this->reportId,
            'file_path' => $filePath,
            'format' => $format
        ];
    }

    /**
     * Generate Executive Summary Report
     */
    private function generateExecutiveReport($scan) {
        $report = [
            'title' => 'Executive Security Assessment Report',
            'scan_name' => $scan['scan_name'],
            'scan_date' => $scan['started_at'],
            'target' => $scan['target_range'],
            'executive_summary' => $this->buildExecutiveSummary($scan),
            'risk_overview' => $this->buildRiskOverview($scan),
            'top_vulnerabilities' => $this->getTopVulnerabilities($scan['id'], 10),
            'recommendations' => $this->getExecutiveRecommendations($scan)
        ];

        return $report;
    }

    /**
     * Generate Technical Report
     */
    private function generateTechnicalReport($scan) {
        $hosts = $this->getHostDetails($scan['id']);

        $report = [
            'title' => 'Technical Security Assessment Report',
            'scan_name' => $scan['scan_name'],
            'scan_date' => $scan['started_at'],
            'target' => $scan['target_range'],
            'methodology' => $this->getMethodology(),
            'hosts' => $hosts,
            'vulnerabilities' => $this->getDetailedVulnerabilities($scan['id']),
            'network_topology' => $this->buildNetworkTopology($hosts),
            'technical_recommendations' => $this->getTechnicalRecommendations($scan['id'])
        ];

        return $report;
    }

    /**
     * Generate Compliance Report
     */
    private function generateComplianceReport($scan) {
        $report = [
            'title' => 'Compliance Assessment Report',
            'scan_name' => $scan['scan_name'],
            'scan_date' => $scan['started_at'],
            'frameworks' => $this->getComplianceFrameworks($scan['id']),
            'compliance_status' => $this->getComplianceStatus($scan['id']),
            'failed_controls' => $this->getFailedControls($scan['id']),
            'remediation_roadmap' => $this->getRemediationRoadmap($scan['id'])
        ];

        return $report;
    }

    /**
     * Generate Full Report (combines all report types)
     */
    private function generateFullReport($scan) {
        return [
            'executive' => $this->generateExecutiveReport($scan),
            'technical' => $this->generateTechnicalReport($scan),
            'compliance' => $this->generateComplianceReport($scan),
            'appendix' => $this->generateAppendix($scan)
        ];
    }

    /**
     * Build Executive Summary
     */
    private function buildExecutiveSummary($scan) {
        $critical = $scan['critical_count'];
        $high = $scan['high_count'];
        $totalVulns = $scan['total_vulnerabilities'];

        $riskLevel = 'Low';
        if ($critical > 0) {
            $riskLevel = 'Critical';
        } elseif ($high > 5) {
            $riskLevel = 'High';
        } elseif ($high > 0) {
            $riskLevel = 'Medium';
        }

        $summary = "This security assessment scanned {$scan['total_hosts']} hosts on the network ";
        $summary .= "and identified {$totalVulns} security vulnerabilities.\n\n";

        $summary .= "Overall Risk Level: {$riskLevel}\n\n";

        if ($critical > 0) {
            $summary .= "CRITICAL: {$critical} critical vulnerabilities require immediate attention. ";
            $summary .= "These vulnerabilities pose an immediate threat to the organization and should be remediated within 24-48 hours.\n\n";
        }

        if ($high > 0) {
            $summary .= "HIGH PRIORITY: {$high} high-severity vulnerabilities were identified. ";
            $summary .= "These should be addressed within 1-2 weeks.\n\n";
        }

        $summary .= "Key Findings:\n";
        $summary .= $this->getKeyFindings($scan);

        return $summary;
    }

    /**
     * Build Risk Overview
     */
    private function buildRiskOverview($scan) {
        return [
            'total_assets' => $scan['total_hosts'],
            'total_vulnerabilities' => $scan['total_vulnerabilities'],
            'severity_distribution' => [
                'critical' => $scan['critical_count'],
                'high' => $scan['high_count'],
                'medium' => $scan['medium_count'],
                'low' => $scan['low_count'],
                'info' => $scan['info_count']
            ],
            'risk_score' => $this->calculateOverallRiskScore($scan['id']),
            'compliance_score' => $this->calculateComplianceScore($scan['id'])
        ];
    }

    /**
     * Get Key Findings
     */
    private function getKeyFindings($scan) {
        $findings = "";
        $scanId = $scan['id'];

        // Check for common critical issues
        $criticalIssues = $this->db->fetchAll(
            "SELECT v.title, COUNT(*) as count
             FROM scan_results sr
             JOIN vulnerabilities v ON sr.vulnerability_id = v.id
             WHERE sr.scan_id = ? AND v.severity = 'critical'
             GROUP BY v.title
             ORDER BY count DESC
             LIMIT 5",
            [$scanId]
        );

        foreach ($criticalIssues as $issue) {
            $findings .= "- {$issue['title']} (found on {$issue['count']} host(s))\n";
        }

        return $findings;
    }

    /**
     * Get Top Vulnerabilities
     */
    private function getTopVulnerabilities($scanId, $limit = 10) {
        return $this->db->fetchAll(
            "SELECT v.*, COUNT(sr.id) as affected_hosts
             FROM vulnerabilities v
             JOIN scan_results sr ON v.id = sr.vulnerability_id
             WHERE sr.scan_id = ?
             GROUP BY v.id
             ORDER BY v.cvss_score DESC, affected_hosts DESC
             LIMIT ?",
            [$scanId, $limit]
        );
    }

    /**
     * Get Host Details
     */
    private function getHostDetails($scanId) {
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
                "SELECT v.*, sr.evidence
                 FROM scan_results sr
                 JOIN vulnerabilities v ON sr.vulnerability_id = v.id
                 WHERE sr.host_id = ?
                 ORDER BY v.cvss_score DESC",
                [$host['id']]
            );
        }

        return $hosts;
    }

    /**
     * Get Detailed Vulnerabilities
     */
    private function getDetailedVulnerabilities($scanId) {
        return $this->db->fetchAll(
            "SELECT v.*,
                    GROUP_CONCAT(DISTINCT h.ip_address) as affected_hosts,
                    COUNT(DISTINCT h.id) as host_count
             FROM vulnerabilities v
             JOIN scan_results sr ON v.id = sr.vulnerability_id
             JOIN hosts h ON sr.host_id = h.id
             WHERE sr.scan_id = ?
             GROUP BY v.id
             ORDER BY v.cvss_score DESC",
            [$scanId]
        );
    }

    /**
     * Get Executive Recommendations
     */
    private function getExecutiveRecommendations($scan) {
        $recommendations = [];

        if ($scan['critical_count'] > 0) {
            $recommendations[] = [
                'priority' => 'IMMEDIATE',
                'title' => 'Address Critical Vulnerabilities',
                'description' => 'Deploy emergency patches and mitigations for all critical-severity vulnerabilities within 24-48 hours.',
                'effort' => 'High',
                'impact' => 'Critical'
            ];
        }

        if ($scan['high_count'] > 0) {
            $recommendations[] = [
                'priority' => 'HIGH',
                'title' => 'Remediate High-Severity Issues',
                'description' => 'Schedule patching and remediation for high-severity vulnerabilities within 1-2 weeks.',
                'effort' => 'Medium',
                'impact' => 'High'
            ];
        }

        // Check for common patterns
        $openDatabases = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM ports p
             JOIN hosts h ON p.host_id = h.id
             WHERE h.scan_id = ? AND p.port_number IN (3306, 5432, 1433, 27017)",
            [$scan['id']]
        );

        if ($openDatabases['count'] > 0) {
            $recommendations[] = [
                'priority' => 'HIGH',
                'title' => 'Secure Database Services',
                'description' => 'Restrict network access to database services. Databases should not be directly accessible from the internet.',
                'effort' => 'Low',
                'impact' => 'High'
            ];
        }

        return $recommendations;
    }

    /**
     * Get Technical Recommendations
     */
    private function getTechnicalRecommendations($scanId) {
        $recommendations = $this->db->fetchAll(
            "SELECT mp.*, v.title as vulnerability, v.severity
             FROM mitigation_plans mp
             JOIN vulnerabilities v ON mp.vulnerability_id = v.id
             JOIN scan_results sr ON v.id = sr.vulnerability_id
             WHERE sr.scan_id = ?
             GROUP BY mp.id
             ORDER BY mp.priority ASC, v.cvss_score DESC",
            [$scanId]
        );

        return $recommendations;
    }

    /**
     * Build Network Topology
     */
    private function buildNetworkTopology($hosts) {
        $topology = [
            'total_hosts' => count($hosts),
            'services_detected' => [],
            'os_distribution' => []
        ];

        foreach ($hosts as $host) {
            // Count services
            foreach ($host['ports'] as $port) {
                $service = $port['service_name'] ?? 'unknown';
                if (!isset($topology['services_detected'][$service])) {
                    $topology['services_detected'][$service] = 0;
                }
                $topology['services_detected'][$service]++;
            }

            // Count OS types
            if ($host['os_type']) {
                if (!isset($topology['os_distribution'][$host['os_type']])) {
                    $topology['os_distribution'][$host['os_type']] = 0;
                }
                $topology['os_distribution'][$host['os_type']]++;
            }
        }

        return $topology;
    }

    /**
     * Get Compliance Frameworks
     */
    private function getComplianceFrameworks($scanId) {
        return $this->db->fetchAll(
            "SELECT cf.*,
                    COUNT(cc.id) as total_checks,
                    SUM(CASE WHEN cc.status = 'pass' THEN 1 ELSE 0 END) as passed,
                    SUM(CASE WHEN cc.status = 'fail' THEN 1 ELSE 0 END) as failed
             FROM compliance_frameworks cf
             LEFT JOIN compliance_controls ctrl ON cf.id = ctrl.framework_id
             LEFT JOIN compliance_checks cc ON ctrl.id = cc.control_id AND cc.scan_id = ?
             WHERE cf.active = 1
             GROUP BY cf.id",
            [$scanId]
        );
    }

    /**
     * Get Compliance Status
     */
    private function getComplianceStatus($scanId) {
        $checks = $this->db->fetchAll(
            "SELECT status, COUNT(*) as count FROM compliance_checks
             WHERE scan_id = ? GROUP BY status",
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

    /**
     * Get Failed Controls
     */
    private function getFailedControls($scanId) {
        return $this->db->fetchAll(
            "SELECT cc.*, ctrl.control_id, ctrl.control_name, cf.name as framework_name
             FROM compliance_checks cc
             JOIN compliance_controls ctrl ON cc.control_id = ctrl.id
             JOIN compliance_frameworks cf ON ctrl.framework_id = cf.id
             WHERE cc.scan_id = ? AND cc.status = 'fail'
             ORDER BY cf.name, ctrl.control_id",
            [$scanId]
        );
    }

    /**
     * Get Remediation Roadmap
     */
    private function getRemediationRoadmap($scanId) {
        // Group vulnerabilities by priority and effort
        $roadmap = [
            'immediate' => [],
            'short_term' => [],
            'medium_term' => [],
            'long_term' => []
        ];

        $vulnerabilities = $this->getDetailedVulnerabilities($scanId);

        foreach ($vulnerabilities as $vuln) {
            $mitigation = $this->db->fetchOne(
                "SELECT * FROM mitigation_plans WHERE vulnerability_id = ? LIMIT 1",
                [$vuln['id']]
            );

            $item = [
                'vulnerability' => $vuln['title'],
                'severity' => $vuln['severity'],
                'affected_hosts' => $vuln['host_count'],
                'mitigation' => $mitigation
            ];

            if ($vuln['severity'] === 'critical') {
                $roadmap['immediate'][] = $item;
            } elseif ($vuln['severity'] === 'high') {
                $roadmap['short_term'][] = $item;
            } elseif ($vuln['severity'] === 'medium') {
                $roadmap['medium_term'][] = $item;
            } else {
                $roadmap['long_term'][] = $item;
            }
        }

        return $roadmap;
    }

    /**
     * Generate Appendix
     */
    private function generateAppendix($scan) {
        return [
            'glossary' => $this->getGlossary(),
            'references' => $this->getReferences(),
            'methodology' => $this->getMethodology(),
            'tools_used' => $this->getToolsUsed()
        ];
    }

    /**
     * Get Scan Data
     */
    private function getScanData($scanId) {
        return $this->db->fetchOne("SELECT * FROM scans WHERE id = ?", [$scanId]);
    }

    /**
     * Calculate Overall Risk Score
     */
    private function calculateOverallRiskScore($scanId) {
        $result = $this->db->fetchOne(
            "SELECT AVG(risk_score) as avg_score FROM hosts WHERE scan_id = ?",
            [$scanId]
        );

        return round($result['avg_score'] ?? 0, 2);
    }

    /**
     * Calculate Compliance Score
     */
    private function calculateComplianceScore($scanId) {
        $result = $this->db->fetchOne(
            "SELECT
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pass' THEN 1 ELSE 0 END) as passed
             FROM compliance_checks WHERE scan_id = ?",
            [$scanId]
        );

        if ($result['total'] == 0) {
            return 0;
        }

        return round(($result['passed'] / $result['total']) * 100, 2);
    }

    /**
     * Create Report Record
     */
    private function createReportRecord($scanId, $reportType, $format) {
        $scan = $this->getScanData($scanId);

        $sql = "INSERT INTO reports (scan_id, report_type, report_format, title,
                total_vulnerabilities, total_hosts, overall_risk_score, generated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";

        $title = ucfirst($reportType) . " Security Assessment Report - " . $scan['scan_name'];

        $this->db->query($sql, [
            $scanId,
            $reportType,
            $format,
            $title,
            $scan['total_vulnerabilities'],
            $scan['total_hosts'],
            $this->calculateOverallRiskScore($scanId)
        ]);

        $this->reportId = $this->db->lastInsertId();
    }

    /**
     * Update Report Record
     */
    private function updateReportRecord($filePath, $fileSize) {
        $sql = "UPDATE reports SET file_path = ?, file_size = ? WHERE id = ?";
        $this->db->query($sql, [$filePath, $fileSize, $this->reportId]);
    }

    /**
     * Save Report to File
     */
    private function saveReport($content, $format) {
        $reportsDir = __DIR__ . '/../reports';
        if (!is_dir($reportsDir)) {
            mkdir($reportsDir, 0755, true);
        }

        $fileName = 'report_' . $this->reportId . '_' . date('Ymd_His') . '.' . $format;
        $filePath = $reportsDir . '/' . $fileName;

        file_put_contents($filePath, $content);

        return $filePath;
    }

    /**
     * Format as HTML
     */
    private function formatHTML($content, $scan) {
        ob_start();
        include __DIR__ . '/../templates/report_template.php';
        $html = ob_get_clean();

        return $html;
    }

    /**
     * Format as JSON
     */
    private function formatJSON($content, $scan) {
        return json_encode($content, JSON_PRETTY_PRINT);
    }

    /**
     * Format as CSV
     */
    private function formatCSV($content, $scan) {
        $csv = "Vulnerability,Severity,CVSS Score,Affected Hosts,Description\n";

        if (isset($content['technical']['vulnerabilities'])) {
            foreach ($content['technical']['vulnerabilities'] as $vuln) {
                $csv .= sprintf(
                    '"%s","%s",%.1f,"%s","%s"' . "\n",
                    $vuln['title'],
                    $vuln['severity'],
                    $vuln['cvss_score'],
                    $vuln['affected_hosts'],
                    str_replace('"', '""', $vuln['description'])
                );
            }
        }

        return $csv;
    }

    /**
     * Get Glossary
     */
    private function getGlossary() {
        return [
            'CVE' => 'Common Vulnerabilities and Exposures',
            'CVSS' => 'Common Vulnerability Scoring System',
            'NDR' => 'Network Detection and Response',
            'NIST' => 'National Institute of Standards and Technology',
            'ISO 27001' => 'Information Security Management Standard',
            'CIS' => 'Center for Internet Security'
        ];
    }

    /**
     * Get References
     */
    private function getReferences() {
        return [
            'NIST Cybersecurity Framework' => 'https://www.nist.gov/cyberframework',
            'CWE/SANS Top 25' => 'https://cwe.mitre.org/top25/',
            'OWASP Top 10' => 'https://owasp.org/www-project-top-ten/',
            'CVE Database' => 'https://cve.mitre.org/',
            'NVD' => 'https://nvd.nist.gov/'
        ];
    }

    /**
     * Get Methodology
     */
    private function getMethodology() {
        return "This assessment follows Gartner best practices for network security assessment " .
               "and monitoring. The methodology includes:\n\n" .
               "1. Network Discovery: Identify active hosts and services\n" .
               "2. Port Scanning: Enumerate open ports and protocols\n" .
               "3. Service Detection: Identify service versions\n" .
               "4. Vulnerability Assessment: Match services against CVE database\n" .
               "5. Risk Scoring: Calculate CVSS scores and overall risk\n" .
               "6. Compliance Checking: Assess against security frameworks\n" .
               "7. Reporting: Generate actionable recommendations";
    }

    /**
     * Get Tools Used
     */
    private function getToolsUsed() {
        return [
            'Port Scanner' => 'Custom TCP/UDP port scanning engine',
            'Service Detector' => 'Banner grabbing and fingerprinting',
            'Vulnerability Scanner' => 'CVE database matching and assessment',
            'Compliance Engine' => 'Multi-framework compliance checking'
        ];
    }
}
