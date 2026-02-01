<?php
/**
 * International Standards Compliance Reports
 * Generate compliance reports based on network scan data
 * Supports: ISO 27001, NIST CSF, PCI-DSS, SOC 2, GDPR, HIPAA, CIS Controls
 */

require_once __DIR__ . '/classes/Database.php';

// Initialize database
try {
    $db = Database::getInstance();
    $settings_result = $db->fetchAll("SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ('logo_url', 'app_name', 'theme_color')");
    $settings = [
        'app_name' => 'HoL Intelligent Operating Centre',
        'logo_url' => '',
        'theme_color' => '#667eea'
    ];
    foreach ($settings_result as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (Exception $e) {
    $settings = [
        'app_name' => 'HoL Intelligent Operating Centre',
        'logo_url' => '',
        'theme_color' => '#667eea'
    ];
}

$app_name = $settings['app_name'] ?? 'HoL Intelligent Operating Centre';
$logo_url = $settings['logo_url'] ?? '';

// Current tab
$activeTab = $_GET['framework'] ?? 'overview';

// Sample Network Scan Data (simulating real scan results)
$networkScanData = [
    'scan_date' => date('Y-m-d H:i:s'),
    'total_hosts' => 847,
    'total_vulnerabilities' => 156,
    'critical_vulns' => 12,
    'high_vulns' => 34,
    'medium_vulns' => 67,
    'low_vulns' => 43,
    'open_ports' => 2456,
    'services_detected' => 189,
    'ssl_issues' => 23,
    'outdated_software' => 45,
    'missing_patches' => 78,
    'weak_passwords' => 15,
    'unencrypted_protocols' => 8,
    'default_credentials' => 6,
    'firewall_misconfigs' => 11,
    'access_control_issues' => 19
];

// Calculate overall security score based on scan data
$maxPenalty = ($networkScanData['critical_vulns'] * 10) + ($networkScanData['high_vulns'] * 5) +
              ($networkScanData['medium_vulns'] * 2) + ($networkScanData['low_vulns'] * 1);
$securityScore = max(0, 100 - min(100, $maxPenalty / 10));

// Compliance Frameworks with detailed control mappings
$frameworks = [
    'iso27001' => [
        'name' => 'ISO 27001:2022',
        'full_name' => 'Information Security Management System',
        'icon' => '🔒',
        'color' => '#2196F3',
        'description' => 'International standard for information security management systems (ISMS)',
        'total_controls' => 93,
        'domains' => [
            ['name' => 'A.5 Organizational Controls', 'controls' => 37, 'passed' => 32, 'failed' => 3, 'na' => 2],
            ['name' => 'A.6 People Controls', 'controls' => 8, 'passed' => 7, 'failed' => 1, 'na' => 0],
            ['name' => 'A.7 Physical Controls', 'controls' => 14, 'passed' => 12, 'failed' => 1, 'na' => 1],
            ['name' => 'A.8 Technological Controls', 'controls' => 34, 'passed' => 28, 'failed' => 4, 'na' => 2]
        ],
        'findings' => [
            ['control' => 'A.8.8', 'title' => 'Management of technical vulnerabilities', 'status' => 'fail', 'severity' => 'high', 'finding' => $networkScanData['critical_vulns'] . ' critical vulnerabilities detected', 'recommendation' => 'Implement immediate patching for critical vulnerabilities'],
            ['control' => 'A.8.24', 'title' => 'Use of cryptography', 'status' => 'fail', 'severity' => 'medium', 'finding' => $networkScanData['ssl_issues'] . ' SSL/TLS configuration issues', 'recommendation' => 'Update SSL certificates and enforce TLS 1.2+'],
            ['control' => 'A.8.9', 'title' => 'Configuration management', 'status' => 'fail', 'severity' => 'medium', 'finding' => $networkScanData['firewall_misconfigs'] . ' firewall misconfigurations', 'recommendation' => 'Review and correct firewall rules'],
            ['control' => 'A.5.15', 'title' => 'Access control', 'status' => 'pass', 'severity' => 'info', 'finding' => 'Access controls properly implemented', 'recommendation' => 'Continue regular access reviews'],
            ['control' => 'A.8.15', 'title' => 'Logging', 'status' => 'pass', 'severity' => 'info', 'finding' => 'Centralized logging enabled', 'recommendation' => 'Maintain current logging configuration']
        ]
    ],
    'nist' => [
        'name' => 'NIST CSF 2.0',
        'full_name' => 'Cybersecurity Framework',
        'icon' => '🛡️',
        'color' => '#4CAF50',
        'description' => 'Framework for improving critical infrastructure cybersecurity',
        'total_controls' => 108,
        'domains' => [
            ['name' => 'GOVERN (GV)', 'controls' => 18, 'passed' => 15, 'failed' => 2, 'na' => 1],
            ['name' => 'IDENTIFY (ID)', 'controls' => 20, 'passed' => 17, 'failed' => 2, 'na' => 1],
            ['name' => 'PROTECT (PR)', 'controls' => 25, 'passed' => 20, 'failed' => 4, 'na' => 1],
            ['name' => 'DETECT (DE)', 'controls' => 18, 'passed' => 16, 'failed' => 1, 'na' => 1],
            ['name' => 'RESPOND (RS)', 'controls' => 15, 'passed' => 13, 'failed' => 1, 'na' => 1],
            ['name' => 'RECOVER (RC)', 'controls' => 12, 'passed' => 10, 'failed' => 1, 'na' => 1]
        ],
        'findings' => [
            ['control' => 'PR.DS-1', 'title' => 'Data-at-rest protection', 'status' => 'fail', 'severity' => 'high', 'finding' => 'Unencrypted data stores detected', 'recommendation' => 'Implement AES-256 encryption for all data at rest'],
            ['control' => 'PR.PS-1', 'title' => 'Baseline configurations', 'status' => 'fail', 'severity' => 'medium', 'finding' => $networkScanData['outdated_software'] . ' systems with outdated software', 'recommendation' => 'Update all systems to latest secure versions'],
            ['control' => 'DE.CM-1', 'title' => 'Network monitoring', 'status' => 'pass', 'severity' => 'info', 'finding' => 'Network monitoring active on all segments', 'recommendation' => 'Continue current monitoring practices'],
            ['control' => 'ID.AM-1', 'title' => 'Asset inventory', 'status' => 'pass', 'severity' => 'info', 'finding' => $networkScanData['total_hosts'] . ' hosts inventoried', 'recommendation' => 'Maintain asset inventory accuracy'],
            ['control' => 'PR.AC-1', 'title' => 'Identity management', 'status' => 'fail', 'severity' => 'high', 'finding' => $networkScanData['default_credentials'] . ' systems with default credentials', 'recommendation' => 'Change all default credentials immediately']
        ]
    ],
    'pci' => [
        'name' => 'PCI-DSS 4.0',
        'full_name' => 'Payment Card Industry Data Security Standard',
        'icon' => '💳',
        'color' => '#FF9800',
        'description' => 'Security standard for organizations handling credit card data',
        'total_controls' => 324,
        'domains' => [
            ['name' => 'Req 1: Network Security Controls', 'controls' => 36, 'passed' => 30, 'failed' => 4, 'na' => 2],
            ['name' => 'Req 2: Secure Configurations', 'controls' => 28, 'passed' => 22, 'failed' => 5, 'na' => 1],
            ['name' => 'Req 3: Protect Account Data', 'controls' => 42, 'passed' => 38, 'failed' => 3, 'na' => 1],
            ['name' => 'Req 4: Protect CHD Transmission', 'controls' => 18, 'passed' => 15, 'failed' => 2, 'na' => 1],
            ['name' => 'Req 5: Malware Protection', 'controls' => 24, 'passed' => 22, 'failed' => 1, 'na' => 1],
            ['name' => 'Req 6: Secure Development', 'controls' => 38, 'passed' => 33, 'failed' => 4, 'na' => 1],
            ['name' => 'Req 7: Restrict Access', 'controls' => 22, 'passed' => 19, 'failed' => 2, 'na' => 1],
            ['name' => 'Req 8: Identify Users', 'controls' => 32, 'passed' => 27, 'failed' => 4, 'na' => 1],
            ['name' => 'Req 9: Physical Access', 'controls' => 28, 'passed' => 26, 'failed' => 1, 'na' => 1],
            ['name' => 'Req 10: Logging & Monitoring', 'controls' => 26, 'passed' => 23, 'failed' => 2, 'na' => 1],
            ['name' => 'Req 11: Security Testing', 'controls' => 18, 'passed' => 14, 'failed' => 3, 'na' => 1],
            ['name' => 'Req 12: Security Policies', 'controls' => 12, 'passed' => 11, 'failed' => 0, 'na' => 1]
        ],
        'findings' => [
            ['control' => '6.3.3', 'title' => 'Vulnerability scanning', 'status' => 'fail', 'severity' => 'high', 'finding' => $networkScanData['critical_vulns'] + $networkScanData['high_vulns'] . ' high/critical vulnerabilities unresolved', 'recommendation' => 'Remediate all high and critical vulnerabilities within 30 days'],
            ['control' => '2.2.1', 'title' => 'System configurations', 'status' => 'fail', 'severity' => 'medium', 'finding' => $networkScanData['default_credentials'] . ' systems with vendor defaults', 'recommendation' => 'Remove all vendor-supplied default accounts'],
            ['control' => '4.2.1', 'title' => 'Strong cryptography', 'status' => 'fail', 'severity' => 'high', 'finding' => $networkScanData['unencrypted_protocols'] . ' unencrypted transmission channels', 'recommendation' => 'Implement TLS 1.2+ for all transmissions'],
            ['control' => '11.3.1', 'title' => 'Penetration testing', 'status' => 'pass', 'severity' => 'info', 'finding' => 'Annual penetration test completed', 'recommendation' => 'Schedule next quarterly internal scan'],
            ['control' => '10.2.1', 'title' => 'Audit logs', 'status' => 'pass', 'severity' => 'info', 'finding' => 'Audit logging enabled on all systems', 'recommendation' => 'Continue log retention for 12 months']
        ]
    ],
    'soc2' => [
        'name' => 'SOC 2 Type II',
        'full_name' => 'Service Organization Control 2',
        'icon' => '✅',
        'color' => '#9C27B0',
        'description' => 'Trust service criteria for service organizations',
        'total_controls' => 64,
        'domains' => [
            ['name' => 'CC1: Control Environment', 'controls' => 8, 'passed' => 7, 'failed' => 1, 'na' => 0],
            ['name' => 'CC2: Communication & Information', 'controls' => 6, 'passed' => 5, 'failed' => 1, 'na' => 0],
            ['name' => 'CC3: Risk Assessment', 'controls' => 6, 'passed' => 5, 'failed' => 1, 'na' => 0],
            ['name' => 'CC4: Monitoring Activities', 'controls' => 4, 'passed' => 4, 'failed' => 0, 'na' => 0],
            ['name' => 'CC5: Control Activities', 'controls' => 8, 'passed' => 6, 'failed' => 2, 'na' => 0],
            ['name' => 'CC6: Logical & Physical Access', 'controls' => 12, 'passed' => 9, 'failed' => 2, 'na' => 1],
            ['name' => 'CC7: System Operations', 'controls' => 10, 'passed' => 8, 'failed' => 1, 'na' => 1],
            ['name' => 'CC8: Change Management', 'controls' => 6, 'passed' => 5, 'failed' => 1, 'na' => 0],
            ['name' => 'CC9: Risk Mitigation', 'controls' => 4, 'passed' => 3, 'failed' => 1, 'na' => 0]
        ],
        'findings' => [
            ['control' => 'CC6.1', 'title' => 'Logical access security', 'status' => 'fail', 'severity' => 'high', 'finding' => $networkScanData['weak_passwords'] . ' accounts with weak passwords', 'recommendation' => 'Enforce password complexity policy'],
            ['control' => 'CC7.2', 'title' => 'Vulnerability management', 'status' => 'fail', 'severity' => 'high', 'finding' => $networkScanData['missing_patches'] . ' missing security patches', 'recommendation' => 'Implement automated patch management'],
            ['control' => 'CC6.6', 'title' => 'System boundaries', 'status' => 'pass', 'severity' => 'info', 'finding' => 'Network segmentation properly configured', 'recommendation' => 'Continue regular boundary reviews'],
            ['control' => 'CC7.1', 'title' => 'Infrastructure monitoring', 'status' => 'pass', 'severity' => 'info', 'finding' => 'Continuous monitoring active', 'recommendation' => 'Maintain current monitoring coverage']
        ]
    ],
    'gdpr' => [
        'name' => 'GDPR',
        'full_name' => 'General Data Protection Regulation',
        'icon' => '🇪🇺',
        'color' => '#3F51B5',
        'description' => 'EU regulation on data protection and privacy',
        'total_controls' => 99,
        'domains' => [
            ['name' => 'Art. 5: Data Processing Principles', 'controls' => 12, 'passed' => 10, 'failed' => 1, 'na' => 1],
            ['name' => 'Art. 6: Lawfulness of Processing', 'controls' => 8, 'passed' => 7, 'failed' => 1, 'na' => 0],
            ['name' => 'Art. 12-23: Data Subject Rights', 'controls' => 18, 'passed' => 15, 'failed' => 2, 'na' => 1],
            ['name' => 'Art. 24-31: Controller/Processor', 'controls' => 16, 'passed' => 13, 'failed' => 2, 'na' => 1],
            ['name' => 'Art. 32-34: Security & Breach', 'controls' => 15, 'passed' => 11, 'failed' => 3, 'na' => 1],
            ['name' => 'Art. 35-36: DPIA', 'controls' => 10, 'passed' => 8, 'failed' => 1, 'na' => 1],
            ['name' => 'Art. 37-39: DPO', 'controls' => 8, 'passed' => 7, 'failed' => 0, 'na' => 1],
            ['name' => 'Art. 44-49: International Transfers', 'controls' => 12, 'passed' => 10, 'failed' => 1, 'na' => 1]
        ],
        'findings' => [
            ['control' => 'Art. 32', 'title' => 'Security of processing', 'status' => 'fail', 'severity' => 'high', 'finding' => 'Encryption gaps detected in ' . $networkScanData['ssl_issues'] . ' systems', 'recommendation' => 'Implement end-to-end encryption for all personal data'],
            ['control' => 'Art. 33', 'title' => 'Breach notification', 'status' => 'pass', 'severity' => 'info', 'finding' => 'Breach notification procedures documented', 'recommendation' => 'Conduct annual breach response drill'],
            ['control' => 'Art. 25', 'title' => 'Data protection by design', 'status' => 'fail', 'severity' => 'medium', 'finding' => 'Privacy impact assessment overdue', 'recommendation' => 'Complete PIA for new systems'],
            ['control' => 'Art. 17', 'title' => 'Right to erasure', 'status' => 'pass', 'severity' => 'info', 'finding' => 'Data deletion procedures implemented', 'recommendation' => 'Automate data retention enforcement']
        ]
    ],
    'hipaa' => [
        'name' => 'HIPAA',
        'full_name' => 'Health Insurance Portability and Accountability Act',
        'icon' => '🏥',
        'color' => '#E91E63',
        'description' => 'US regulation for protecting health information',
        'total_controls' => 75,
        'domains' => [
            ['name' => 'Administrative Safeguards', 'controls' => 25, 'passed' => 21, 'failed' => 3, 'na' => 1],
            ['name' => 'Physical Safeguards', 'controls' => 15, 'passed' => 13, 'failed' => 1, 'na' => 1],
            ['name' => 'Technical Safeguards', 'controls' => 20, 'passed' => 15, 'failed' => 4, 'na' => 1],
            ['name' => 'Organizational Requirements', 'controls' => 10, 'passed' => 9, 'failed' => 0, 'na' => 1],
            ['name' => 'Policies & Documentation', 'controls' => 5, 'passed' => 4, 'failed' => 1, 'na' => 0]
        ],
        'findings' => [
            ['control' => '164.312(a)(1)', 'title' => 'Access control', 'status' => 'fail', 'severity' => 'high', 'finding' => $networkScanData['access_control_issues'] . ' access control deficiencies', 'recommendation' => 'Implement role-based access control'],
            ['control' => '164.312(e)(1)', 'title' => 'Transmission security', 'status' => 'fail', 'severity' => 'critical', 'finding' => $networkScanData['unencrypted_protocols'] . ' unencrypted ePHI transmissions', 'recommendation' => 'Encrypt all ePHI in transit immediately'],
            ['control' => '164.312(b)', 'title' => 'Audit controls', 'status' => 'pass', 'severity' => 'info', 'finding' => 'Audit logging active for ePHI access', 'recommendation' => 'Continue audit log reviews'],
            ['control' => '164.308(a)(1)', 'title' => 'Security management', 'status' => 'pass', 'severity' => 'info', 'finding' => 'Risk analysis completed', 'recommendation' => 'Update risk analysis annually']
        ]
    ],
    'cis' => [
        'name' => 'CIS Controls v8',
        'full_name' => 'Center for Internet Security Controls',
        'icon' => '🔐',
        'color' => '#00BCD4',
        'description' => 'Prioritized security actions to protect against cyber attacks',
        'total_controls' => 153,
        'domains' => [
            ['name' => 'CIS 1: Inventory of Assets', 'controls' => 8, 'passed' => 7, 'failed' => 1, 'na' => 0],
            ['name' => 'CIS 2: Inventory of Software', 'controls' => 7, 'passed' => 5, 'failed' => 2, 'na' => 0],
            ['name' => 'CIS 3: Data Protection', 'controls' => 12, 'passed' => 9, 'failed' => 2, 'na' => 1],
            ['name' => 'CIS 4: Secure Configuration', 'controls' => 12, 'passed' => 8, 'failed' => 3, 'na' => 1],
            ['name' => 'CIS 5: Account Management', 'controls' => 10, 'passed' => 7, 'failed' => 2, 'na' => 1],
            ['name' => 'CIS 6: Access Control', 'controls' => 8, 'passed' => 6, 'failed' => 1, 'na' => 1],
            ['name' => 'CIS 7: Vulnerability Management', 'controls' => 7, 'passed' => 4, 'failed' => 3, 'na' => 0],
            ['name' => 'CIS 8: Audit Log Management', 'controls' => 12, 'passed' => 10, 'failed' => 1, 'na' => 1],
            ['name' => 'CIS 9: Email & Browser', 'controls' => 7, 'passed' => 5, 'failed' => 2, 'na' => 0],
            ['name' => 'CIS 10: Malware Defenses', 'controls' => 7, 'passed' => 6, 'failed' => 1, 'na' => 0],
            ['name' => 'CIS 11: Data Recovery', 'controls' => 5, 'passed' => 4, 'failed' => 1, 'na' => 0],
            ['name' => 'CIS 12: Network Infrastructure', 'controls' => 8, 'passed' => 6, 'failed' => 2, 'na' => 0],
            ['name' => 'CIS 13: Network Monitoring', 'controls' => 11, 'passed' => 9, 'failed' => 1, 'na' => 1],
            ['name' => 'CIS 14: Security Training', 'controls' => 9, 'passed' => 7, 'failed' => 1, 'na' => 1],
            ['name' => 'CIS 15: Service Provider', 'controls' => 7, 'passed' => 6, 'failed' => 0, 'na' => 1],
            ['name' => 'CIS 16: Application Security', 'controls' => 14, 'passed' => 10, 'failed' => 3, 'na' => 1],
            ['name' => 'CIS 17: Incident Response', 'controls' => 9, 'passed' => 8, 'failed' => 0, 'na' => 1],
            ['name' => 'CIS 18: Penetration Testing', 'controls' => 5, 'passed' => 4, 'failed' => 1, 'na' => 0]
        ],
        'findings' => [
            ['control' => '7.1', 'title' => 'Vulnerability scanning', 'status' => 'fail', 'severity' => 'high', 'finding' => $networkScanData['total_vulnerabilities'] . ' vulnerabilities detected across network', 'recommendation' => 'Establish weekly vulnerability scanning'],
            ['control' => '4.1', 'title' => 'Secure configuration', 'status' => 'fail', 'severity' => 'medium', 'finding' => $networkScanData['firewall_misconfigs'] . ' configuration deviations', 'recommendation' => 'Implement configuration management baseline'],
            ['control' => '1.1', 'title' => 'Asset inventory', 'status' => 'pass', 'severity' => 'info', 'finding' => $networkScanData['total_hosts'] . ' assets tracked', 'recommendation' => 'Continue automated discovery'],
            ['control' => '8.2', 'title' => 'Centralized logging', 'status' => 'pass', 'severity' => 'info', 'finding' => 'SIEM collecting logs from all critical systems', 'recommendation' => 'Expand log sources coverage']
        ]
    ]
];

// Calculate compliance scores for each framework
foreach ($frameworks as $key => &$framework) {
    $totalPassed = 0;
    $totalControls = 0;
    foreach ($framework['domains'] as $domain) {
        $totalPassed += $domain['passed'];
        $totalControls += $domain['controls'];
    }
    $framework['passed'] = $totalPassed;
    $framework['failed'] = $totalControls - $totalPassed;
    $framework['score'] = round(($totalPassed / $totalControls) * 100);
    $framework['status'] = $framework['score'] >= 90 ? 'Compliant' : ($framework['score'] >= 70 ? 'Partial' : 'Non-Compliant');
}
unset($framework);

// Get active framework details
$activeFramework = $frameworks[$activeTab] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compliance Reports | <?= htmlspecialchars($app_name) ?></title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --primary: #667eea;
            --primary-dark: #5a67d8;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
            --dark: #1e293b;
            --light: #f8fafc;
            --gray: #64748b;
        }

        body {
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container { max-width: 1600px; margin: 0 auto; }

        /* Header */
        .header {
            background: white;
            border-radius: 16px;
            padding: 25px 30px;
            margin-bottom: 25px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .header-left h1 {
            color: var(--primary);
            font-size: 28px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header-left p { color: var(--gray); margin-top: 5px; }

        .header-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }

        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-dark); transform: translateY(-2px); }
        .btn-secondary { background: var(--dark); color: white; }
        .btn-outline { background: white; color: var(--primary); border: 2px solid var(--primary); }
        .btn-success { background: var(--success); color: white; }

        /* Framework Tabs */
        .framework-tabs {
            background: white;
            border-radius: 16px;
            padding: 15px;
            margin-bottom: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            overflow-x: auto;
        }

        .framework-tab {
            padding: 12px 20px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            font-size: 13px;
            color: var(--dark);
            background: var(--light);
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
        }

        .framework-tab:hover { background: #e2e8f0; }
        .framework-tab.active { background: var(--primary); color: white; }

        .tab-score {
            background: rgba(255,255,255,0.2);
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 11px;
        }

        .framework-tab.active .tab-score { background: rgba(255,255,255,0.3); }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            text-align: center;
        }

        .stat-icon { font-size: 40px; margin-bottom: 10px; }
        .stat-value { font-size: 32px; font-weight: bold; color: var(--dark); }
        .stat-label { color: var(--gray); font-size: 13px; margin-top: 5px; }

        .stat-card.success { border-left: 4px solid var(--success); }
        .stat-card.warning { border-left: 4px solid var(--warning); }
        .stat-card.danger { border-left: 4px solid var(--danger); }
        .stat-card.info { border-left: 4px solid var(--info); }

        /* Main Content Grid */
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 25px;
        }

        @media (max-width: 1200px) {
            .content-grid { grid-template-columns: 1fr; }
        }

        /* Cards */
        .card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .card-header {
            padding: 20px 25px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header h2 {
            font-size: 18px;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-body { padding: 25px; }

        /* Domain List */
        .domain-list { display: flex; flex-direction: column; gap: 15px; }

        .domain-item {
            background: var(--light);
            border-radius: 12px;
            padding: 15px 20px;
        }

        .domain-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .domain-name { font-weight: 600; color: var(--dark); font-size: 14px; }

        .domain-stats {
            display: flex;
            gap: 15px;
            font-size: 12px;
        }

        .domain-stats span { display: flex; align-items: center; gap: 4px; }
        .domain-stats .passed { color: var(--success); }
        .domain-stats .failed { color: var(--danger); }
        .domain-stats .na { color: var(--gray); }

        .progress-bar {
            height: 8px;
            background: #e2e8f0;
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--success), #34d399);
            border-radius: 4px;
            transition: width 0.5s ease;
        }

        /* Findings Table */
        .findings-table {
            width: 100%;
            border-collapse: collapse;
        }

        .findings-table th {
            text-align: left;
            padding: 12px 15px;
            background: var(--light);
            font-size: 12px;
            font-weight: 600;
            color: var(--gray);
            text-transform: uppercase;
        }

        .findings-table td {
            padding: 15px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 13px;
        }

        .findings-table tr:hover { background: #f8fafc; }

        .control-id {
            font-family: 'Monaco', 'Consolas', monospace;
            background: var(--light);
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-badge.pass { background: #d1fae5; color: #059669; }
        .status-badge.fail { background: #fee2e2; color: #dc2626; }

        .severity-badge {
            padding: 3px 10px;
            border-radius: 6px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .severity-badge.critical { background: #7f1d1d; color: white; }
        .severity-badge.high { background: #ef4444; color: white; }
        .severity-badge.medium { background: #f59e0b; color: white; }
        .severity-badge.low { background: #3b82f6; color: white; }
        .severity-badge.info { background: #6b7280; color: white; }

        /* Sidebar */
        .sidebar-card { margin-bottom: 20px; }

        .compliance-score {
            text-align: center;
            padding: 30px;
        }

        .score-circle {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: conic-gradient(var(--success) calc(var(--score) * 1%), #e2e8f0 0);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            position: relative;
        }

        .score-circle::before {
            content: '';
            position: absolute;
            width: 120px;
            height: 120px;
            background: white;
            border-radius: 50%;
        }

        .score-value {
            position: relative;
            z-index: 1;
            font-size: 36px;
            font-weight: bold;
            color: var(--dark);
        }

        .score-label { color: var(--gray); font-size: 14px; }

        .compliance-status {
            display: inline-block;
            padding: 8px 20px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
            margin-top: 15px;
        }

        .compliance-status.compliant { background: #d1fae5; color: #059669; }
        .compliance-status.partial { background: #fef3c7; color: #d97706; }
        .compliance-status.non-compliant { background: #fee2e2; color: #dc2626; }

        /* Scan Info */
        .scan-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .scan-info-item {
            background: var(--light);
            padding: 12px;
            border-radius: 8px;
        }

        .scan-info-label { font-size: 11px; color: var(--gray); }
        .scan-info-value { font-size: 16px; font-weight: 600; color: var(--dark); margin-top: 2px; }

        /* Chart Container */
        .chart-container {
            position: relative;
            height: 250px;
            padding: 15px;
        }

        /* Framework Overview Grid */
        .framework-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .framework-card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            border-left: 5px solid var(--primary);
            transition: all 0.3s;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
        }

        .framework-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }

        .framework-card-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }

        .framework-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .framework-card h3 { font-size: 18px; color: var(--dark); }
        .framework-card p { font-size: 12px; color: var(--gray); margin-top: 3px; }

        .framework-score-large {
            font-size: 42px;
            font-weight: bold;
            margin: 15px 0;
        }

        .framework-metrics {
            display: flex;
            gap: 20px;
            font-size: 13px;
            color: var(--gray);
        }

        .framework-metrics span { display: flex; align-items: center; gap: 5px; }

        /* Print Styles */
        @media print {
            body { background: white; padding: 0; }
            .header-actions, .framework-tabs, .btn { display: none; }
            .card { box-shadow: none; border: 1px solid #e2e8f0; }
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .card { animation: fadeIn 0.5s ease; }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                <h1>📋 Compliance Reports</h1>
                <p>International Standards Compliance Assessment Based on Network Scan Data</p>
            </div>
            <div class="header-actions">
                <a href="index.php" class="btn btn-outline">← Dashboard</a>
                <button onclick="window.print()" class="btn btn-secondary">🖨️ Print Report</button>
                <button onclick="generatePDF()" class="btn btn-primary">📄 Export PDF</button>
            </div>
        </div>

        <!-- Framework Tabs -->
        <div class="framework-tabs">
            <a href="?framework=overview" class="framework-tab <?= $activeTab === 'overview' ? 'active' : '' ?>">
                📊 Overview
            </a>
            <?php foreach ($frameworks as $key => $fw): ?>
            <a href="?framework=<?= $key ?>" class="framework-tab <?= $activeTab === $key ? 'active' : '' ?>" style="<?= $activeTab === $key ? 'background: ' . $fw['color'] : '' ?>">
                <?= $fw['icon'] ?> <?= $fw['name'] ?>
                <span class="tab-score"><?= $fw['score'] ?>%</span>
            </a>
            <?php endforeach; ?>
        </div>

        <?php if ($activeTab === 'overview'): ?>
        <!-- Overview Section -->
        <div class="stats-grid">
            <div class="stat-card info">
                <div class="stat-icon">🔍</div>
                <div class="stat-value"><?= $networkScanData['total_hosts'] ?></div>
                <div class="stat-label">Hosts Scanned</div>
            </div>
            <div class="stat-card danger">
                <div class="stat-icon">⚠️</div>
                <div class="stat-value"><?= $networkScanData['total_vulnerabilities'] ?></div>
                <div class="stat-label">Vulnerabilities Found</div>
            </div>
            <div class="stat-card success">
                <div class="stat-icon">✅</div>
                <div class="stat-value"><?= count(array_filter($frameworks, fn($f) => $f['status'] === 'Compliant')) ?>/<?= count($frameworks) ?></div>
                <div class="stat-label">Frameworks Compliant</div>
            </div>
            <div class="stat-card warning">
                <div class="stat-icon">📊</div>
                <div class="stat-value"><?= round(array_sum(array_column($frameworks, 'score')) / count($frameworks)) ?>%</div>
                <div class="stat-label">Average Compliance</div>
            </div>
        </div>

        <div class="framework-overview">
            <?php foreach ($frameworks as $key => $fw): ?>
            <a href="?framework=<?= $key ?>" class="framework-card" style="border-left-color: <?= $fw['color'] ?>">
                <div class="framework-card-header">
                    <div class="framework-icon" style="background: <?= $fw['color'] ?>20; color: <?= $fw['color'] ?>"><?= $fw['icon'] ?></div>
                    <div>
                        <h3><?= $fw['name'] ?></h3>
                        <p><?= $fw['full_name'] ?></p>
                    </div>
                </div>
                <div class="framework-score-large" style="color: <?= $fw['score'] >= 90 ? 'var(--success)' : ($fw['score'] >= 70 ? 'var(--warning)' : 'var(--danger)') ?>">
                    <?= $fw['score'] ?>%
                </div>
                <div class="progress-bar" style="margin-bottom: 15px;">
                    <div class="progress-fill" style="width: <?= $fw['score'] ?>%; background: <?= $fw['color'] ?>"></div>
                </div>
                <div class="framework-metrics">
                    <span style="color: var(--success)">✓ <?= $fw['passed'] ?> Passed</span>
                    <span style="color: var(--danger)">✗ <?= $fw['failed'] ?> Failed</span>
                    <span><?= $fw['total_controls'] ?> Controls</span>
                </div>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- Network Scan Summary Card -->
        <div class="card">
            <div class="card-header">
                <h2>🔍 Network Scan Summary (Basis for Compliance Assessment)</h2>
                <span style="color: var(--gray); font-size: 13px;">Last Scan: <?= $networkScanData['scan_date'] ?></span>
            </div>
            <div class="card-body">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px;">
                    <div class="scan-info-item">
                        <div class="scan-info-label">Critical Vulnerabilities</div>
                        <div class="scan-info-value" style="color: var(--danger)"><?= $networkScanData['critical_vulns'] ?></div>
                    </div>
                    <div class="scan-info-item">
                        <div class="scan-info-label">High Vulnerabilities</div>
                        <div class="scan-info-value" style="color: #f97316"><?= $networkScanData['high_vulns'] ?></div>
                    </div>
                    <div class="scan-info-item">
                        <div class="scan-info-label">SSL/TLS Issues</div>
                        <div class="scan-info-value" style="color: var(--warning)"><?= $networkScanData['ssl_issues'] ?></div>
                    </div>
                    <div class="scan-info-item">
                        <div class="scan-info-label">Missing Patches</div>
                        <div class="scan-info-value" style="color: var(--warning)"><?= $networkScanData['missing_patches'] ?></div>
                    </div>
                    <div class="scan-info-item">
                        <div class="scan-info-label">Outdated Software</div>
                        <div class="scan-info-value"><?= $networkScanData['outdated_software'] ?></div>
                    </div>
                    <div class="scan-info-item">
                        <div class="scan-info-label">Default Credentials</div>
                        <div class="scan-info-value" style="color: var(--danger)"><?= $networkScanData['default_credentials'] ?></div>
                    </div>
                    <div class="scan-info-item">
                        <div class="scan-info-label">Firewall Misconfigs</div>
                        <div class="scan-info-value"><?= $networkScanData['firewall_misconfigs'] ?></div>
                    </div>
                    <div class="scan-info-item">
                        <div class="scan-info-label">Access Control Issues</div>
                        <div class="scan-info-value"><?= $networkScanData['access_control_issues'] ?></div>
                    </div>
                </div>
            </div>
        </div>

        <?php else: ?>
        <!-- Framework Detail View -->
        <?php if ($activeFramework): ?>
        <div class="stats-grid">
            <div class="stat-card success">
                <div class="stat-icon">✅</div>
                <div class="stat-value"><?= $activeFramework['passed'] ?></div>
                <div class="stat-label">Controls Passed</div>
            </div>
            <div class="stat-card danger">
                <div class="stat-icon">❌</div>
                <div class="stat-value"><?= $activeFramework['failed'] ?></div>
                <div class="stat-label">Controls Failed</div>
            </div>
            <div class="stat-card info">
                <div class="stat-icon">📋</div>
                <div class="stat-value"><?= $activeFramework['total_controls'] ?></div>
                <div class="stat-label">Total Controls</div>
            </div>
            <div class="stat-card warning">
                <div class="stat-icon">📊</div>
                <div class="stat-value"><?= $activeFramework['score'] ?>%</div>
                <div class="stat-label">Compliance Score</div>
            </div>
        </div>

        <div class="content-grid">
            <div class="main-content">
                <!-- Control Domains -->
                <div class="card" style="margin-bottom: 25px;">
                    <div class="card-header">
                        <h2><?= $activeFramework['icon'] ?> <?= $activeFramework['name'] ?> Control Domains</h2>
                    </div>
                    <div class="card-body">
                        <div class="domain-list">
                            <?php foreach ($activeFramework['domains'] as $domain):
                                $domainScore = round(($domain['passed'] / $domain['controls']) * 100);
                            ?>
                            <div class="domain-item">
                                <div class="domain-header">
                                    <span class="domain-name"><?= $domain['name'] ?></span>
                                    <div class="domain-stats">
                                        <span class="passed">✓ <?= $domain['passed'] ?></span>
                                        <span class="failed">✗ <?= $domain['failed'] ?></span>
                                        <span class="na">N/A <?= $domain['na'] ?></span>
                                    </div>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?= $domainScore ?>%"></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Findings -->
                <div class="card">
                    <div class="card-header">
                        <h2>🔍 Scan-Based Compliance Findings</h2>
                        <span style="color: var(--gray); font-size: 13px;"><?= count($activeFramework['findings']) ?> findings</span>
                    </div>
                    <div class="card-body" style="padding: 0;">
                        <table class="findings-table">
                            <thead>
                                <tr>
                                    <th>Control</th>
                                    <th>Title</th>
                                    <th>Status</th>
                                    <th>Severity</th>
                                    <th>Finding</th>
                                    <th>Recommendation</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($activeFramework['findings'] as $finding): ?>
                                <tr>
                                    <td><span class="control-id"><?= $finding['control'] ?></span></td>
                                    <td style="font-weight: 500;"><?= $finding['title'] ?></td>
                                    <td><span class="status-badge <?= $finding['status'] ?>"><?= ucfirst($finding['status']) ?></span></td>
                                    <td><span class="severity-badge <?= $finding['severity'] ?>"><?= ucfirst($finding['severity']) ?></span></td>
                                    <td style="color: var(--gray);"><?= $finding['finding'] ?></td>
                                    <td style="font-size: 12px;"><?= $finding['recommendation'] ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="sidebar">
                <div class="card sidebar-card">
                    <div class="card-body">
                        <div class="compliance-score">
                            <div class="score-circle" style="--score: <?= $activeFramework['score'] ?>">
                                <span class="score-value"><?= $activeFramework['score'] ?>%</span>
                            </div>
                            <div class="score-label"><?= $activeFramework['full_name'] ?></div>
                            <div class="compliance-status <?= strtolower(str_replace('-', '', $activeFramework['status'])) ?>">
                                <?= $activeFramework['status'] ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card sidebar-card">
                    <div class="card-header">
                        <h2>📊 Compliance Breakdown</h2>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="complianceChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="card sidebar-card">
                    <div class="card-header">
                        <h2>🔍 Scan Data Impact</h2>
                    </div>
                    <div class="card-body">
                        <div class="scan-info">
                            <div class="scan-info-item">
                                <div class="scan-info-label">Hosts Scanned</div>
                                <div class="scan-info-value"><?= $networkScanData['total_hosts'] ?></div>
                            </div>
                            <div class="scan-info-item">
                                <div class="scan-info-label">Vulnerabilities</div>
                                <div class="scan-info-value" style="color: var(--danger)"><?= $networkScanData['total_vulnerabilities'] ?></div>
                            </div>
                            <div class="scan-info-item">
                                <div class="scan-info-label">Critical Issues</div>
                                <div class="scan-info-value" style="color: var(--danger)"><?= $networkScanData['critical_vulns'] ?></div>
                            </div>
                            <div class="scan-info-item">
                                <div class="scan-info-label">Security Score</div>
                                <div class="scan-info-value" style="color: var(--success)"><?= round($securityScore) ?>%</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card sidebar-card">
                    <div class="card-header">
                        <h2>📅 Audit Schedule</h2>
                    </div>
                    <div class="card-body">
                        <div style="display: flex; flex-direction: column; gap: 10px;">
                            <div style="padding: 12px; background: var(--light); border-radius: 8px;">
                                <div style="font-size: 11px; color: var(--gray);">Last Assessment</div>
                                <div style="font-weight: 600; margin-top: 3px;"><?= date('M d, Y', strtotime('-15 days')) ?></div>
                            </div>
                            <div style="padding: 12px; background: var(--light); border-radius: 8px;">
                                <div style="font-size: 11px; color: var(--gray);">Next Scheduled Audit</div>
                                <div style="font-weight: 600; margin-top: 3px;"><?= date('M d, Y', strtotime('+90 days')) ?></div>
                            </div>
                            <div style="padding: 12px; background: var(--light); border-radius: 8px;">
                                <div style="font-size: 11px; color: var(--gray);">Certification Expires</div>
                                <div style="font-weight: 600; margin-top: 3px;"><?= date('M d, Y', strtotime('+365 days')) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>

    <script>
        <?php if ($activeTab !== 'overview' && $activeFramework): ?>
        // Compliance Chart
        const ctx = document.getElementById('complianceChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Passed', 'Failed'],
                datasets: [{
                    data: [<?= $activeFramework['passed'] ?>, <?= $activeFramework['failed'] ?>],
                    backgroundColor: ['#10b981', '#ef4444'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                },
                cutout: '60%'
            }
        });
        <?php endif; ?>

        function generatePDF() {
            alert('Generating PDF report... In production, this would use a library like jsPDF or server-side PDF generation.');
            window.print();
        }
    </script>
</body>
</html>
