<?php
/**
 * Compliance Checker Class
 * Assesses network compliance against security frameworks
 * Supports: NIST CSF, ISO 27001, CIS Controls, PCI DSS, HIPAA, SOC 2
 */

require_once __DIR__ . '/Database.php';

class ComplianceChecker {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Run compliance checks for a scan
     */
    public function runComplianceChecks($scanId, $frameworks = null) {
        if ($frameworks === null) {
            // Get all active frameworks
            $frameworks = $this->db->fetchAll(
                "SELECT id FROM compliance_frameworks WHERE active = 1"
            );
            $frameworks = array_column($frameworks, 'id');
        }

        $results = [];

        foreach ($frameworks as $frameworkId) {
            $results[$frameworkId] = $this->checkFramework($scanId, $frameworkId);
        }

        return $results;
    }

    /**
     * Check compliance against a specific framework
     */
    private function checkFramework($scanId, $frameworkId) {
        $controls = $this->db->fetchAll(
            "SELECT * FROM compliance_controls WHERE framework_id = ?",
            [$frameworkId]
        );

        $results = [
            'passed' => 0,
            'failed' => 0,
            'partial' => 0,
            'not_tested' => 0
        ];

        foreach ($controls as $control) {
            $status = $this->checkControl($scanId, $control);
            $results[$status]++;

            // Store check result
            $this->storeCheckResult($scanId, $control['id'], $status);
        }

        return $results;
    }

    /**
     * Check individual compliance control
     */
    private function checkControl($scanId, $control) {
        // Get scan data
        $scan = $this->db->fetchOne("SELECT * FROM scans WHERE id = ?", [$scanId]);
        $vulnerabilities = $this->db->fetchAll(
            "SELECT v.* FROM vulnerabilities v
             JOIN scan_results sr ON v.id = sr.vulnerability_id
             WHERE sr.scan_id = ?",
            [$scanId]
        );

        // Control-specific checks
        $controlId = $control['control_id'];

        // Map controls to checks
        switch ($controlId) {
            // NIST CSF Controls
            case 'ID.AM-1': // Asset Management
                return $this->checkAssetInventory($scanId);

            case 'PR.AC-1': // Access Control
                return $this->checkAccessControl($scanId, $vulnerabilities);

            case 'PR.DS-1': // Data Security
                return $this->checkDataEncryption($scanId, $vulnerabilities);

            case 'PR.PT-1': // Protective Technology
                return $this->checkProtectiveTechnology($scanId, $vulnerabilities);

            case 'DE.CM-1': // Continuous Monitoring
                return 'pass'; // Tool itself provides this

            // CIS Controls
            case 'CIS-1': // Inventory of Authorized/Unauthorized Devices
                return $this->checkAssetInventory($scanId);

            case 'CIS-3': // Secure Configuration
                return $this->checkSecureConfiguration($scanId, $vulnerabilities);

            case 'CIS-4': // Continuous Vulnerability Assessment
                return 'pass'; // This scan provides this

            case 'CIS-6': // Maintenance/Monitoring of Audit Logs
                return $this->checkAuditLogging($scanId);

            // ISO 27001 Controls
            case 'A.9.1.1': // Access control policy
                return $this->checkAccessControl($scanId, $vulnerabilities);

            case 'A.12.6.1': // Management of technical vulnerabilities
                return $this->checkVulnerabilityManagement($scanId, $vulnerabilities);

            case 'A.14.2.1': // Secure development policy
                return $this->checkSecureConfiguration($scanId, $vulnerabilities);

            // PCI DSS Requirements
            case 'REQ-1': // Install and maintain firewall
                return $this->checkFirewall($scanId, $vulnerabilities);

            case 'REQ-2': // Do not use vendor defaults
                return $this->checkDefaultCredentials($scanId, $vulnerabilities);

            case 'REQ-6': // Develop secure systems
                return $this->checkVulnerabilityManagement($scanId, $vulnerabilities);

            case 'REQ-11': // Test security systems regularly
                return 'pass'; // This scan provides this

            // HIPAA Controls
            case 'HIPAA-164.308': // Administrative Safeguards
                return $this->checkAccessControl($scanId, $vulnerabilities);

            case 'HIPAA-164.312': // Technical Safeguards
                return $this->checkDataEncryption($scanId, $vulnerabilities);

            default:
                return 'not_tested';
        }
    }

    /**
     * Check Asset Inventory
     */
    private function checkAssetInventory($scanId) {
        $hosts = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM hosts WHERE scan_id = ?",
            [$scanId]
        );

        return $hosts['count'] > 0 ? 'pass' : 'fail';
    }

    /**
     * Check Access Control
     */
    private function checkAccessControl($scanId, $vulnerabilities) {
        // Check for open authentication services
        $openAuth = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM ports p
             JOIN hosts h ON p.host_id = h.id
             WHERE h.scan_id = ? AND p.port_number IN (22, 23, 3389, 5900)
             AND p.state = 'open'",
            [$scanId]
        );

        // Check for default credential vulnerabilities
        $defaultCreds = array_filter($vulnerabilities, function($v) {
            return stripos($v['title'], 'default') !== false ||
                   stripos($v['title'], 'credential') !== false;
        });

        if ($openAuth['count'] > 0 && count($defaultCreds) > 0) {
            return 'fail';
        } elseif ($openAuth['count'] > 0 || count($defaultCreds) > 0) {
            return 'partial';
        }

        return 'pass';
    }

    /**
     * Check Data Encryption
     */
    private function checkDataEncryption($scanId, $vulnerabilities) {
        // Check for unencrypted protocols
        $unencrypted = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM ports p
             JOIN hosts h ON p.host_id = h.id
             WHERE h.scan_id = ? AND p.port_number IN (21, 23, 80, 143, 110)
             AND p.state = 'open'",
            [$scanId]
        );

        // Check for SSL/TLS issues
        $sslIssues = array_filter($vulnerabilities, function($v) {
            return stripos($v['title'], 'SSL') !== false ||
                   stripos($v['title'], 'TLS') !== false ||
                   stripos($v['title'], 'certificate') !== false;
        });

        if ($unencrypted['count'] > 0 || count($sslIssues) > 0) {
            return 'fail';
        }

        return 'pass';
    }

    /**
     * Check Protective Technology
     */
    private function checkProtectiveTechnology($scanId, $vulnerabilities) {
        $criticalVulns = array_filter($vulnerabilities, function($v) {
            return $v['severity'] === 'critical' || $v['severity'] === 'high';
        });

        if (count($criticalVulns) > 5) {
            return 'fail';
        } elseif (count($criticalVulns) > 0) {
            return 'partial';
        }

        return 'pass';
    }

    /**
     * Check Secure Configuration
     */
    private function checkSecureConfiguration($scanId, $vulnerabilities) {
        $configIssues = array_filter($vulnerabilities, function($v) {
            return stripos($v['title'], 'misconfiguration') !== false ||
                   stripos($v['title'], 'default') !== false ||
                   stripos($v['title'], 'banner') !== false ||
                   stripos($v['title'], 'disclosure') !== false;
        });

        if (count($configIssues) > 3) {
            return 'fail';
        } elseif (count($configIssues) > 0) {
            return 'partial';
        }

        return 'pass';
    }

    /**
     * Check Audit Logging
     */
    private function checkAuditLogging($scanId) {
        // Check if audit logging is enabled (from audit_log table)
        $logs = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM audit_log WHERE entity_id = ?",
            [$scanId]
        );

        return $logs['count'] > 0 ? 'pass' : 'fail';
    }

    /**
     * Check Vulnerability Management
     */
    private function checkVulnerabilityManagement($scanId, $vulnerabilities) {
        // Count unresolved vulnerabilities
        $unresolved = array_filter($vulnerabilities, function($v) {
            return $v['severity'] === 'critical' || $v['severity'] === 'high';
        });

        if (count($unresolved) > 10) {
            return 'fail';
        } elseif (count($unresolved) > 0) {
            return 'partial';
        }

        return 'pass';
    }

    /**
     * Check Firewall
     */
    private function checkFirewall($scanId, $vulnerabilities) {
        // Check for excessive open ports
        $openPorts = $this->db->fetchOne(
            "SELECT SUM(open_ports) as total FROM hosts WHERE scan_id = ?",
            [$scanId]
        );

        $avgPorts = $openPorts['total'] / max(1, $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM hosts WHERE scan_id = ?",
            [$scanId]
        )['count']);

        if ($avgPorts > 20) {
            return 'fail';
        } elseif ($avgPorts > 10) {
            return 'partial';
        }

        return 'pass';
    }

    /**
     * Check Default Credentials
     */
    private function checkDefaultCredentials($scanId, $vulnerabilities) {
        $defaultCreds = array_filter($vulnerabilities, function($v) {
            return stripos($v['title'], 'default') !== false &&
                   (stripos($v['title'], 'credential') !== false ||
                    stripos($v['title'], 'password') !== false);
        });

        return count($defaultCreds) === 0 ? 'pass' : 'fail';
    }

    /**
     * Store check result
     */
    private function storeCheckResult($scanId, $controlId, $status) {
        // Check if result already exists
        $existing = $this->db->fetchOne(
            "SELECT id FROM compliance_checks WHERE scan_id = ? AND control_id = ?",
            [$scanId, $controlId]
        );

        if ($existing) {
            // Update existing
            $sql = "UPDATE compliance_checks SET status = ?, tested_at = NOW()
                    WHERE scan_id = ? AND control_id = ?";
            $this->db->query($sql, [$status, $scanId, $controlId]);
        } else {
            // Insert new
            $sql = "INSERT INTO compliance_checks (scan_id, control_id, status, tested_at)
                    VALUES (?, ?, ?, NOW())";
            $this->db->query($sql, [$scanId, $controlId, $status]);
        }
    }

    /**
     * Get compliance summary
     */
    public function getComplianceSummary($scanId) {
        $frameworks = $this->db->fetchAll(
            "SELECT cf.*, COUNT(cc.id) as total_checks,
                    SUM(CASE WHEN cc.status = 'pass' THEN 1 ELSE 0 END) as passed,
                    SUM(CASE WHEN cc.status = 'fail' THEN 1 ELSE 0 END) as failed,
                    SUM(CASE WHEN cc.status = 'partial' THEN 1 ELSE 0 END) as partial
             FROM compliance_frameworks cf
             LEFT JOIN compliance_controls ctrl ON cf.id = ctrl.framework_id
             LEFT JOIN compliance_checks cc ON ctrl.id = cc.control_id AND cc.scan_id = ?
             WHERE cf.active = 1
             GROUP BY cf.id",
            [$scanId]
        );

        foreach ($frameworks as &$framework) {
            if ($framework['total_checks'] > 0) {
                $framework['compliance_percentage'] = round(
                    ($framework['passed'] / $framework['total_checks']) * 100,
                    2
                );
            } else {
                $framework['compliance_percentage'] = 0;
            }
        }

        return $frameworks;
    }

    /**
     * Initialize default compliance controls
     */
    public function initializeDefaultControls() {
        $controls = [
            // NIST CSF
            ['framework' => 'NIST CSF', 'control_id' => 'ID.AM-1', 'name' => 'Asset Management'],
            ['framework' => 'NIST CSF', 'control_id' => 'PR.AC-1', 'name' => 'Access Control'],
            ['framework' => 'NIST CSF', 'control_id' => 'PR.DS-1', 'name' => 'Data Security'],
            ['framework' => 'NIST CSF', 'control_id' => 'PR.PT-1', 'name' => 'Protective Technology'],
            ['framework' => 'NIST CSF', 'control_id' => 'DE.CM-1', 'name' => 'Continuous Monitoring'],

            // CIS Controls
            ['framework' => 'CIS Controls', 'control_id' => 'CIS-1', 'name' => 'Inventory and Control of Hardware Assets'],
            ['framework' => 'CIS Controls', 'control_id' => 'CIS-3', 'name' => 'Continuous Vulnerability Management'],
            ['framework' => 'CIS Controls', 'control_id' => 'CIS-4', 'name' => 'Controlled Use of Administrative Privileges'],
            ['framework' => 'CIS Controls', 'control_id' => 'CIS-6', 'name' => 'Maintenance, Monitoring and Analysis of Audit Logs'],

            // ISO 27001
            ['framework' => 'ISO 27001', 'control_id' => 'A.9.1.1', 'name' => 'Access control policy'],
            ['framework' => 'ISO 27001', 'control_id' => 'A.12.6.1', 'name' => 'Management of technical vulnerabilities'],
            ['framework' => 'ISO 27001', 'control_id' => 'A.14.2.1', 'name' => 'Secure development policy'],

            // PCI DSS
            ['framework' => 'PCI DSS', 'control_id' => 'REQ-1', 'name' => 'Install and maintain a firewall configuration'],
            ['framework' => 'PCI DSS', 'control_id' => 'REQ-2', 'name' => 'Do not use vendor-supplied defaults'],
            ['framework' => 'PCI DSS', 'control_id' => 'REQ-6', 'name' => 'Develop and maintain secure systems'],
            ['framework' => 'PCI DSS', 'control_id' => 'REQ-11', 'name' => 'Regularly test security systems'],

            // HIPAA
            ['framework' => 'HIPAA', 'control_id' => 'HIPAA-164.308', 'name' => 'Administrative Safeguards'],
            ['framework' => 'HIPAA', 'control_id' => 'HIPAA-164.312', 'name' => 'Technical Safeguards']
        ];

        foreach ($controls as $control) {
            // Get framework ID
            $framework = $this->db->fetchOne(
                "SELECT id FROM compliance_frameworks WHERE name = ?",
                [$control['framework']]
            );

            if ($framework) {
                // Check if control exists
                $existing = $this->db->fetchOne(
                    "SELECT id FROM compliance_controls WHERE framework_id = ? AND control_id = ?",
                    [$framework['id'], $control['control_id']]
                );

                if (!$existing) {
                    $sql = "INSERT INTO compliance_controls (framework_id, control_id, control_name, description)
                            VALUES (?, ?, ?, ?)";
                    $this->db->query($sql, [
                        $framework['id'],
                        $control['control_id'],
                        $control['name'],
                        'Automated compliance check for ' . $control['name']
                    ]);
                }
            }
        }
    }
}
