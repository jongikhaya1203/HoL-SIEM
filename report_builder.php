<?php
/**
 * Advanced Report Builder with AI Analysis
 * Comprehensive security assessment reports with threat analysis,
 * recommendations, and 2-year quarterly tracking
 */

require_once __DIR__ . '/classes/Database.php';

// Initialize database connection
try {
    $db = Database::getInstance();
    $settings_result = $db->fetchAll("SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ('logo_url', 'app_name', 'theme_color')");
    $settings = [
        'app_name' => 'IOC Intelligent Operating Centre',
        'logo_url' => '',
        'theme_color' => '#667eea'
    ];
    foreach ($settings_result as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (Exception $e) {
    $settings = [
        'app_name' => 'IOC Intelligent Operating Centre',
        'logo_url' => '',
        'theme_color' => '#667eea'
    ];
}

$app_name = $settings['app_name'] ?? 'IOC Intelligent Operating Centre';
$logo_url = $settings['logo_url'] ?? '';
$theme_color = $settings['theme_color'] ?? '#667eea';

// ============================================
// COMPREHENSIVE SAMPLE DATA - 2 YEARS QUARTERLY
// ============================================
$quarterlyData = [
    '2023-Q1' => ['quarter' => 'Q1 2023', 'scans' => 12, 'hosts' => 245, 'critical' => 8, 'high' => 22, 'medium' => 45, 'low' => 67, 'resolved' => 89, 'pending' => 53, 'compliance' => 68],
    '2023-Q2' => ['quarter' => 'Q2 2023', 'scans' => 15, 'hosts' => 267, 'critical' => 12, 'high' => 28, 'medium' => 52, 'low' => 71, 'resolved' => 102, 'pending' => 61, 'compliance' => 71],
    '2023-Q3' => ['quarter' => 'Q3 2023', 'scans' => 18, 'hosts' => 289, 'critical' => 15, 'high' => 35, 'medium' => 48, 'low' => 63, 'resolved' => 118, 'pending' => 43, 'compliance' => 74],
    '2023-Q4' => ['quarter' => 'Q4 2023', 'scans' => 21, 'hosts' => 312, 'critical' => 11, 'high' => 31, 'medium' => 56, 'low' => 72, 'resolved' => 134, 'pending' => 36, 'compliance' => 76],
    '2024-Q1' => ['quarter' => 'Q1 2024', 'scans' => 24, 'hosts' => 328, 'critical' => 14, 'high' => 38, 'medium' => 61, 'low' => 78, 'resolved' => 142, 'pending' => 49, 'compliance' => 73],
    '2024-Q2' => ['quarter' => 'Q2 2024', 'scans' => 28, 'hosts' => 345, 'critical' => 19, 'high' => 42, 'medium' => 55, 'low' => 69, 'resolved' => 156, 'pending' => 29, 'compliance' => 78],
    '2024-Q3' => ['quarter' => 'Q3 2024', 'scans' => 32, 'hosts' => 358, 'critical' => 16, 'high' => 36, 'medium' => 49, 'low' => 61, 'resolved' => 168, 'pending' => 24, 'compliance' => 81],
    '2024-Q4' => ['quarter' => 'Q4 2024', 'scans' => 35, 'hosts' => 372, 'critical' => 17, 'high' => 38, 'medium' => 52, 'low' => 58, 'resolved' => 178, 'pending' => 17, 'compliance' => 84],
];

// Issue Tracking Data with Status Flags
$issueTracking = [
    [
        'id' => 'ISS-2023-001', 'title' => 'Unpatched Apache Servers', 'severity' => 'critical',
        'raised' => '2023-01-15', 'due' => '2023-01-22', 'resolved' => '2023-01-20',
        'status' => 'resolved', 'owner' => 'Infrastructure Team', 'affected' => 12,
        'category' => 'Patch Management', 'sla_met' => true
    ],
    [
        'id' => 'ISS-2023-002', 'title' => 'SQL Injection in Customer Portal', 'severity' => 'critical',
        'raised' => '2023-02-08', 'due' => '2023-02-10', 'resolved' => '2023-02-09',
        'status' => 'resolved', 'owner' => 'Development Team', 'affected' => 1,
        'category' => 'Application Security', 'sla_met' => true
    ],
    [
        'id' => 'ISS-2023-003', 'title' => 'Weak SSH Configuration', 'severity' => 'high',
        'raised' => '2023-03-12', 'due' => '2023-03-26', 'resolved' => '2023-04-02',
        'status' => 'resolved', 'owner' => 'Security Team', 'affected' => 45,
        'category' => 'Configuration', 'sla_met' => false
    ],
    [
        'id' => 'ISS-2023-004', 'title' => 'Exposed Database Ports', 'severity' => 'critical',
        'raised' => '2023-04-20', 'due' => '2023-04-22', 'resolved' => null,
        'status' => 'overdue', 'owner' => 'Database Team', 'affected' => 8,
        'category' => 'Network Security', 'sla_met' => false
    ],
    [
        'id' => 'ISS-2023-005', 'title' => 'Missing MFA on Admin Accounts', 'severity' => 'high',
        'raised' => '2023-05-15', 'due' => '2023-05-29', 'resolved' => '2023-05-28',
        'status' => 'resolved', 'owner' => 'IAM Team', 'affected' => 23,
        'category' => 'Access Control', 'sla_met' => true
    ],
    [
        'id' => 'ISS-2024-001', 'title' => 'Log4j Vulnerability Detected', 'severity' => 'critical',
        'raised' => '2024-01-10', 'due' => '2024-01-12', 'resolved' => '2024-01-11',
        'status' => 'resolved', 'owner' => 'Development Team', 'affected' => 34,
        'category' => 'Application Security', 'sla_met' => true
    ],
    [
        'id' => 'ISS-2024-002', 'title' => 'Outdated SSL Certificates', 'severity' => 'medium',
        'raised' => '2024-02-18', 'due' => '2024-03-04', 'resolved' => null,
        'status' => 'in_progress', 'owner' => 'Infrastructure Team', 'affected' => 15,
        'category' => 'Certificate Management', 'sla_met' => true
    ],
    [
        'id' => 'ISS-2024-003', 'title' => 'XSS in Internal Dashboard', 'severity' => 'high',
        'raised' => '2024-03-22', 'due' => '2024-04-05', 'resolved' => '2024-04-01',
        'status' => 'resolved', 'owner' => 'Development Team', 'affected' => 1,
        'category' => 'Application Security', 'sla_met' => true
    ],
    [
        'id' => 'ISS-2024-004', 'title' => 'Privilege Escalation Risk', 'severity' => 'critical',
        'raised' => '2024-04-15', 'due' => '2024-04-17', 'resolved' => null,
        'status' => 'open', 'owner' => 'Security Team', 'affected' => 67,
        'category' => 'Access Control', 'sla_met' => false
    ],
    [
        'id' => 'ISS-2024-005', 'title' => 'Unencrypted Data Transfer', 'severity' => 'high',
        'raised' => '2024-05-08', 'due' => '2024-05-22', 'resolved' => null,
        'status' => 'in_progress', 'owner' => 'Network Team', 'affected' => 12,
        'category' => 'Data Protection', 'sla_met' => true
    ],
];

// Detailed Vulnerabilities with AI Analysis
$vulnerabilities = [
    [
        'id' => 1, 'cve' => 'CVE-2024-1086', 'title' => 'Linux Kernel Use-After-Free Vulnerability',
        'severity' => 'critical', 'cvss' => 9.8, 'hosts' => 12, 'status' => 'open',
        'discovered' => '2024-01-15', 'exploit_available' => true,
        'description' => 'A use-after-free vulnerability in the Linux kernel netfilter subsystem allows local privilege escalation.',
        'affected_systems' => 'Linux servers running kernel versions 5.14 to 6.6',
        'threat_analysis' => 'CRITICAL: Active exploitation in the wild. Attackers can gain root access to affected systems. This vulnerability is being actively used in targeted attacks against enterprise environments.',
        'business_impact' => 'Complete system compromise possible. Attackers could access sensitive data, install backdoors, or use compromised systems as pivot points for lateral movement.',
        'recommendation' => 'Immediately apply kernel patches from your Linux distribution. If patching is not immediately possible, implement network segmentation to limit exposure and monitor for suspicious privilege escalation attempts.',
        'remediation_steps' => ['Update Linux kernel to version 6.7 or later', 'Apply vendor-specific patches', 'Enable kernel live patching if available', 'Monitor system logs for exploitation attempts'],
        'references' => ['https://nvd.nist.gov/vuln/detail/CVE-2024-1086', 'https://kernel.org/security']
    ],
    [
        'id' => 2, 'cve' => 'CVE-2024-3094', 'title' => 'XZ Utils Backdoor (Supply Chain Attack)',
        'severity' => 'critical', 'cvss' => 10.0, 'hosts' => 3, 'status' => 'open',
        'discovered' => '2024-03-29', 'exploit_available' => true,
        'description' => 'Malicious code was discovered in XZ Utils versions 5.6.0 and 5.6.1, creating a backdoor in SSH authentication.',
        'affected_systems' => 'Systems with XZ Utils 5.6.0 or 5.6.1 installed, particularly those running sshd',
        'threat_analysis' => 'CRITICAL: State-sponsored supply chain attack. The backdoor allows unauthorized remote access via SSH. This is one of the most sophisticated supply chain attacks ever discovered.',
        'business_impact' => 'Complete remote system compromise without authentication. Attackers can bypass SSH security entirely, leading to data theft, system manipulation, or ransomware deployment.',
        'recommendation' => 'Immediately downgrade XZ Utils to version 5.4.x or earlier. Audit systems for signs of compromise. Review SSH logs for anomalous authentication patterns.',
        'remediation_steps' => ['Downgrade XZ Utils to 5.4.6 or earlier', 'Restart SSH daemon', 'Audit SSH authentication logs', 'Check for unauthorized access', 'Implement network monitoring for SSH anomalies'],
        'references' => ['https://nvd.nist.gov/vuln/detail/CVE-2024-3094', 'https://www.openwall.com/lists/oss-security/2024/03/29/4']
    ],
    [
        'id' => 3, 'cve' => 'CVE-2024-21762', 'title' => 'Fortinet FortiOS SSL VPN Remote Code Execution',
        'severity' => 'critical', 'cvss' => 9.6, 'hosts' => 2, 'status' => 'mitigated',
        'discovered' => '2024-02-08', 'exploit_available' => true,
        'description' => 'An out-of-bounds write vulnerability in FortiOS SSL VPN allows unauthenticated remote code execution.',
        'affected_systems' => 'FortiOS versions 7.4.0-7.4.2, 7.2.0-7.2.6, 7.0.0-7.0.13, 6.4.0-6.4.14, 6.2.0-6.2.15',
        'threat_analysis' => 'HIGH PRIORITY: Actively exploited by APT groups. VPN infrastructure is a prime target as it provides direct network access. Chinese state-sponsored actors have been observed using this vulnerability.',
        'business_impact' => 'Network perimeter breach possible. Attackers can gain initial access to internal networks, potentially leading to full domain compromise.',
        'recommendation' => 'Apply FortiOS patches immediately. If patching is delayed, disable SSL VPN or implement strict IP allowlisting. Enable enhanced logging for VPN connections.',
        'remediation_steps' => ['Update to FortiOS 7.4.3, 7.2.7, 7.0.14, 6.4.15, or 6.2.16', 'Review VPN access logs', 'Implement MFA for VPN access', 'Consider network segmentation'],
        'references' => ['https://www.fortiguard.com/psirt/FG-IR-24-015']
    ],
    [
        'id' => 4, 'cve' => 'CVE-2024-0012', 'title' => 'Palo Alto PAN-OS Authentication Bypass',
        'severity' => 'high', 'cvss' => 8.8, 'hosts' => 5, 'status' => 'open',
        'discovered' => '2024-01-20', 'exploit_available' => true,
        'description' => 'Authentication bypass in PAN-OS management interface allows unauthorized administrative access.',
        'affected_systems' => 'PAN-OS 10.0, 10.1, 10.2, 11.0 with management interface exposed',
        'threat_analysis' => 'HIGH: Firewall management access could allow attackers to disable security controls, create backdoor access rules, or intercept network traffic.',
        'business_impact' => 'Complete firewall compromise allows attackers to modify security policies, potentially exposing entire network segments.',
        'recommendation' => 'Restrict management interface access to trusted networks only. Apply vendor patches. Implement out-of-band management network.',
        'remediation_steps' => ['Apply PAN-OS security patches', 'Restrict management interface to internal IPs only', 'Enable MFA for management access', 'Review firewall rule changes'],
        'references' => ['https://security.paloaltonetworks.com/']
    ],
    [
        'id' => 5, 'cve' => 'CVE-2024-20353', 'title' => 'Cisco ASA/FTD Denial of Service',
        'severity' => 'high', 'cvss' => 8.6, 'hosts' => 8, 'status' => 'open',
        'discovered' => '2024-04-24', 'exploit_available' => false,
        'description' => 'A vulnerability in Cisco ASA and FTD allows remote attackers to cause a denial of service condition.',
        'affected_systems' => 'Cisco ASA and Firepower Threat Defense devices',
        'threat_analysis' => 'MEDIUM-HIGH: While not allowing code execution, DoS attacks on security infrastructure can disable protections during coordinated attacks.',
        'business_impact' => 'Network security devices becoming unavailable could leave the network unprotected during an attack.',
        'recommendation' => 'Apply Cisco security updates. Implement redundant security infrastructure. Configure rate limiting on affected services.',
        'remediation_steps' => ['Apply Cisco security patches', 'Configure connection limits', 'Implement HA pairs for critical devices', 'Enable DoS protection features'],
        'references' => ['https://sec.cloudapps.cisco.com/security/center/']
    ],
    [
        'id' => 6, 'cve' => 'CVE-2024-27198', 'title' => 'JetBrains TeamCity Authentication Bypass',
        'severity' => 'high', 'cvss' => 8.1, 'hosts' => 4, 'status' => 'mitigated',
        'discovered' => '2024-03-04', 'exploit_available' => true,
        'description' => 'Critical authentication bypass allowing unauthenticated attackers to take control of TeamCity servers.',
        'affected_systems' => 'JetBrains TeamCity before 2023.11.4',
        'threat_analysis' => 'HIGH: CI/CD pipeline compromise can lead to supply chain attacks. Attackers can inject malicious code into software builds.',
        'business_impact' => 'Software supply chain compromise. Malicious code could be distributed to customers through compromised builds.',
        'recommendation' => 'Update TeamCity immediately. Audit build configurations and artifacts. Implement build signing and verification.',
        'remediation_steps' => ['Update to TeamCity 2023.11.4 or later', 'Review user accounts for unauthorized additions', 'Audit recent build configurations', 'Implement artifact signing'],
        'references' => ['https://www.jetbrains.com/privacy-security/']
    ],
    [
        'id' => 7, 'cve' => 'CVE-2024-1709', 'title' => 'ConnectWise ScreenConnect Authentication Bypass',
        'severity' => 'medium', 'cvss' => 6.8, 'hosts' => 6, 'status' => 'open',
        'discovered' => '2024-02-19', 'exploit_available' => true,
        'description' => 'Authentication bypass vulnerability allowing unauthorized access to ScreenConnect servers.',
        'affected_systems' => 'ConnectWise ScreenConnect versions before 23.9.8',
        'threat_analysis' => 'MEDIUM: Remote access tools are high-value targets. Compromise allows persistent access to managed endpoints.',
        'business_impact' => 'Attackers could access all endpoints managed through compromised ScreenConnect instance.',
        'recommendation' => 'Update ScreenConnect immediately. Review connected endpoints for signs of compromise.',
        'remediation_steps' => ['Update to ScreenConnect 23.9.8 or later', 'Review access logs', 'Audit connected endpoints', 'Implement network segmentation'],
        'references' => ['https://www.connectwise.com/company/trust/security-bulletins']
    ],
    [
        'id' => 8, 'cve' => 'CVE-2024-22024', 'title' => 'Ivanti Connect Secure XXE Injection',
        'severity' => 'medium', 'cvss' => 6.5, 'hosts' => 7, 'status' => 'open',
        'discovered' => '2024-01-31', 'exploit_available' => true,
        'description' => 'XML External Entity injection vulnerability in Ivanti Connect Secure VPN appliances.',
        'affected_systems' => 'Ivanti Connect Secure 9.x and 22.x',
        'threat_analysis' => 'MEDIUM: XXE can lead to sensitive data disclosure and SSRF attacks. Active exploitation observed.',
        'business_impact' => 'Internal configuration and credential exposure possible. Could be chained with other vulnerabilities.',
        'recommendation' => 'Apply Ivanti patches. Consider temporary mitigation through configuration changes.',
        'remediation_steps' => ['Apply vendor patches', 'Import mitigation XML file', 'Monitor for exploitation attempts', 'Review VPN access logs'],
        'references' => ['https://forums.ivanti.com/s/article/CVE-2024-22024']
    ],
    [
        'id' => 9, 'cve' => 'CVE-2024-23897', 'title' => 'Jenkins CLI Arbitrary File Read',
        'severity' => 'medium', 'cvss' => 5.9, 'hosts' => 3, 'status' => 'mitigated',
        'discovered' => '2024-01-24', 'exploit_available' => true,
        'description' => 'Arbitrary file read vulnerability in Jenkins CLI allows attackers to read sensitive files.',
        'affected_systems' => 'Jenkins versions before 2.442 and LTS before 2.426.3',
        'threat_analysis' => 'MEDIUM: Can expose credentials, configuration files, and secrets stored on Jenkins server.',
        'business_impact' => 'Credential theft could lead to further compromise of connected systems and repositories.',
        'recommendation' => 'Update Jenkins and disable CLI if not needed. Rotate any exposed credentials.',
        'remediation_steps' => ['Update Jenkins to latest version', 'Disable Jenkins CLI if unused', 'Rotate credentials stored in Jenkins', 'Review secrets management'],
        'references' => ['https://www.jenkins.io/security/advisory/2024-01-24/']
    ],
    [
        'id' => 10, 'cve' => 'CVE-2024-0402', 'title' => 'GitLab Arbitrary File Write',
        'severity' => 'low', 'cvss' => 4.3, 'hosts' => 2, 'status' => 'open',
        'discovered' => '2024-01-11', 'exploit_available' => false,
        'description' => 'Authenticated users can write files to arbitrary locations on the GitLab server.',
        'affected_systems' => 'GitLab CE/EE versions 16.0 to 16.7',
        'threat_analysis' => 'LOW-MEDIUM: Requires authentication but could lead to code execution through file write.',
        'business_impact' => 'Potential for code execution on GitLab server if exploited by malicious insider.',
        'recommendation' => 'Update GitLab to patched version. Review user permissions and audit logs.',
        'remediation_steps' => ['Update GitLab to 16.7.2 or later', 'Review user permissions', 'Enable audit logging', 'Implement least privilege access'],
        'references' => ['https://about.gitlab.com/releases/']
    ]
];

// AI Threat Intelligence Summary
$threatIntelligence = [
    'overall_risk_level' => 'HIGH',
    'risk_score' => 7.8,
    'trend' => 'increasing',
    'active_threats' => 4,
    'key_findings' => [
        'Multiple critical vulnerabilities with active exploitation detected',
        'Supply chain attack vector identified (XZ Utils backdoor)',
        'VPN and remote access infrastructure at high risk',
        'CI/CD pipeline security concerns require immediate attention'
    ],
    'threat_actors' => [
        ['name' => 'APT Groups', 'activity' => 'High', 'targets' => 'VPN infrastructure, network devices'],
        ['name' => 'Ransomware Operators', 'activity' => 'Medium', 'targets' => 'Unpatched systems, exposed services'],
        ['name' => 'Supply Chain Attackers', 'activity' => 'High', 'targets' => 'Build systems, dependencies']
    ],
    'attack_vectors' => [
        ['vector' => 'Remote Code Execution', 'likelihood' => 'High', 'impact' => 'Critical'],
        ['vector' => 'Authentication Bypass', 'likelihood' => 'High', 'impact' => 'High'],
        ['vector' => 'Supply Chain Compromise', 'likelihood' => 'Medium', 'impact' => 'Critical'],
        ['vector' => 'Privilege Escalation', 'likelihood' => 'High', 'impact' => 'High']
    ]
];

// Hosts data
$hosts = [
    ['id' => 1, 'ip' => '192.168.1.10', 'hostname' => 'web-server-01', 'os' => 'Ubuntu 22.04', 'type' => 'Server', 'risk' => 8.5, 'vulns' => 5, 'status' => 'online'],
    ['id' => 2, 'ip' => '192.168.1.20', 'hostname' => 'db-server-01', 'os' => 'CentOS 8', 'type' => 'Server', 'risk' => 7.2, 'vulns' => 3, 'status' => 'online'],
    ['id' => 3, 'ip' => '192.168.1.30', 'hostname' => 'app-server-01', 'os' => 'Windows Server 2022', 'type' => 'Server', 'risk' => 6.8, 'vulns' => 4, 'status' => 'online'],
    ['id' => 4, 'ip' => '192.168.1.40', 'hostname' => 'firewall-01', 'os' => 'PAN-OS 11.0', 'type' => 'Firewall', 'risk' => 5.5, 'vulns' => 2, 'status' => 'online'],
    ['id' => 5, 'ip' => '192.168.1.50', 'hostname' => 'switch-core-01', 'os' => 'Cisco IOS XE', 'type' => 'Switch', 'risk' => 4.2, 'vulns' => 1, 'status' => 'online'],
    ['id' => 6, 'ip' => '192.168.1.60', 'hostname' => 'mail-server-01', 'os' => 'Debian 12', 'type' => 'Server', 'risk' => 7.8, 'vulns' => 6, 'status' => 'online'],
    ['id' => 7, 'ip' => '192.168.1.70', 'hostname' => 'vpn-gateway-01', 'os' => 'FortiOS 7.2', 'type' => 'VPN', 'risk' => 9.2, 'vulns' => 3, 'status' => 'online'],
    ['id' => 8, 'ip' => '192.168.1.80', 'hostname' => 'jenkins-ci-01', 'os' => 'Ubuntu 24.04', 'type' => 'Server', 'risk' => 6.5, 'vulns' => 2, 'status' => 'online']
];

// Compliance frameworks
$compliance = [
    ['framework' => 'NIST CSF', 'score' => 78, 'passed' => 156, 'failed' => 44, 'total' => 200, 'trend' => '+3%'],
    ['framework' => 'ISO 27001', 'score' => 82, 'passed' => 98, 'failed' => 22, 'total' => 120, 'trend' => '+5%'],
    ['framework' => 'PCI DSS', 'score' => 71, 'passed' => 85, 'failed' => 35, 'total' => 120, 'trend' => '-2%'],
    ['framework' => 'CIS Controls', 'score' => 85, 'passed' => 153, 'failed' => 27, 'total' => 180, 'trend' => '+4%'],
    ['framework' => 'HIPAA', 'score' => 68, 'passed' => 68, 'failed' => 32, 'total' => 100, 'trend' => '+1%'],
    ['framework' => 'SOC 2', 'score' => 75, 'passed' => 90, 'failed' => 30, 'total' => 120, 'trend' => '+6%']
];

// Current statistics
$statistics = [
    'total_scans' => 185,
    'total_hosts' => 372,
    'total_vulnerabilities' => 165,
    'critical_count' => 17,
    'high_count' => 38,
    'medium_count' => 52,
    'low_count' => 58,
    'avg_risk_score' => 7.8,
    'compliance_score' => 76,
    'issues_open' => 5,
    'issues_in_progress' => 2,
    'issues_resolved' => 178,
    'issues_overdue' => 1,
    'sla_compliance' => 87
];

// Combine all data
$sampleData = [
    'quarterlyData' => $quarterlyData,
    'issueTracking' => $issueTracking,
    'vulnerabilities' => $vulnerabilities,
    'threatIntelligence' => $threatIntelligence,
    'hosts' => $hosts,
    'compliance' => $compliance,
    'statistics' => $statistics
];

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    switch ($_POST['action']) {
        case 'get_data':
            echo json_encode(['success' => true, 'data' => $sampleData]);
            exit;
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
            exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Report Builder | <?= htmlspecialchars($app_name) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Roboto+Mono:wght@400;500&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        :root {
            --primary: #667eea;
            --primary-dark: #5a67d8;
            --secondary: #764ba2;
            --success: #48bb78;
            --warning: #ed8936;
            --danger: #e53e3e;
            --info: #4299e1;
            --dark: #2d3748;
            --gray: #718096;
            --light: #f7fafc;
            --border: #e2e8f0;
            --font: 'Inter', -apple-system, sans-serif;
            --mono: 'Roboto Mono', monospace;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: var(--font);
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            min-height: 100vh;
            font-size: 14px;
            line-height: 1.6;
            color: var(--dark);
        }

        .container { max-width: 1700px; margin: 0 auto; padding: 20px; }

        /* Header */
        .header {
            background: white;
            padding: 20px 28px;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header-subtitle { color: var(--gray); font-size: 0.875rem; margin-top: 4px; }

        /* Navigation */
        .nav {
            background: white;
            padding: 12px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .nav-btn {
            padding: 10px 18px;
            background: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.8125rem;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
        }

        .nav-btn:hover { background: var(--primary-dark); transform: translateY(-1px); }
        .nav-btn.secondary { background: var(--light); color: var(--primary); }
        .nav-btn.secondary:hover { background: var(--primary); color: white; }

        /* Layout */
        .builder-layout {
            display: grid;
            grid-template-columns: 260px 1fr 280px;
            gap: 20px;
            align-items: start;
        }

        @media (max-width: 1400px) {
            .builder-layout { grid-template-columns: 240px 1fr; }
            .properties-panel { display: none; }
        }

        @media (max-width: 1000px) {
            .builder-layout { grid-template-columns: 1fr; }
            .components-panel { display: none; }
        }

        /* Panels */
        .panel {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .panel-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 16px 20px;
            font-weight: 700;
            font-size: 0.875rem;
        }

        .panel-body {
            padding: 16px;
            max-height: calc(100vh - 260px);
            overflow-y: auto;
        }

        .panel-body::-webkit-scrollbar { width: 5px; }
        .panel-body::-webkit-scrollbar-thumb { background: var(--border); border-radius: 3px; }

        /* Components */
        .component-category { margin-bottom: 20px; }

        .category-title {
            font-size: 0.6875rem;
            font-weight: 700;
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 10px;
            padding-bottom: 6px;
            border-bottom: 2px solid var(--light);
        }

        .component-item {
            background: var(--light);
            border: 2px solid var(--border);
            border-radius: 10px;
            padding: 10px 12px;
            margin-bottom: 6px;
            cursor: grab;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .component-item:hover {
            border-color: var(--primary);
            transform: translateX(3px);
        }

        .component-icon {
            font-size: 1.125rem;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: white;
            border-radius: 8px;
        }

        .component-info h4 { font-size: 0.8125rem; font-weight: 600; color: var(--dark); }
        .component-info p { font-size: 0.6875rem; color: var(--gray); }

        /* Canvas */
        .canvas-panel { display: flex; flex-direction: column; min-height: calc(100vh - 260px); }

        .canvas-toolbar {
            background: var(--light);
            padding: 12px 16px;
            border-bottom: 2px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .toolbar-group { display: flex; gap: 8px; align-items: center; }

        .toolbar-btn {
            padding: 9px 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.8125rem;
            font-weight: 600;
            transition: all 0.2s;
            font-family: var(--font);
        }

        .toolbar-btn.primary { background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white; }
        .toolbar-btn.primary:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(102,126,234,0.4); }
        .toolbar-btn.secondary { background: white; color: var(--primary); border: 2px solid var(--primary); }
        .toolbar-btn.secondary:hover { background: var(--primary); color: white; }
        .toolbar-btn.success { background: var(--success); color: white; }
        .toolbar-btn.danger { background: rgba(229,62,62,0.1); color: var(--danger); border: 2px solid rgba(229,62,62,0.3); }
        .toolbar-btn.danger:hover { background: var(--danger); color: white; }

        .toolbar-select {
            padding: 9px 14px;
            border: 2px solid var(--border);
            border-radius: 8px;
            font-size: 0.8125rem;
            font-family: var(--font);
            background: white;
            min-width: 180px;
        }

        /* Report Canvas */
        .report-canvas {
            flex: 1;
            padding: 20px;
            background: #e8ecf4;
            overflow-y: auto;
        }

        .report-preview {
            background: white;
            max-width: 900px;
            margin: 0 auto;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.12);
            overflow: hidden;
        }

        /* Report Header */
        .report-header-section {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 32px 36px;
        }

        .report-title { font-size: 1.5rem; font-weight: 800; margin-bottom: 6px; }
        .report-subtitle { font-size: 0.875rem; opacity: 0.9; }

        .report-meta {
            display: flex;
            gap: 24px;
            margin-top: 16px;
            font-size: 0.8125rem;
            flex-wrap: wrap;
        }

        .report-meta-item { display: flex; align-items: center; gap: 6px; opacity: 0.95; }
        .report-content { padding: 28px 36px; }

        /* Sections */
        .report-section {
            background: var(--light);
            border: 2px solid var(--border);
            border-radius: 12px;
            padding: 20px 24px;
            margin-bottom: 16px;
        }

        .report-section:hover { border-color: var(--primary); }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 2px solid var(--border);
        }

        .section-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-actions { display: flex; gap: 4px; }

        .section-btn {
            width: 28px;
            height: 28px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .section-btn.delete { background: rgba(229,62,62,0.1); color: var(--danger); }
        .section-btn:hover { transform: scale(1.1); }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
            gap: 12px;
        }

        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 16px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            border-left: 4px solid var(--primary);
        }

        .stat-card.critical { border-left-color: var(--danger); }
        .stat-card.high { border-left-color: var(--warning); }
        .stat-card.medium { border-left-color: #ecc94b; }
        .stat-card.low { border-left-color: var(--success); }

        .stat-value { font-size: 1.75rem; font-weight: 800; color: var(--dark); }
        .stat-label { font-size: 0.6875rem; font-weight: 600; color: var(--gray); margin-top: 4px; text-transform: uppercase; }

        /* Tables */
        .table-container { overflow-x: auto; border-radius: 8px; border: 1px solid var(--border); }

        .data-table { width: 100%; border-collapse: collapse; font-size: 0.8125rem; }

        .data-table th {
            background: var(--primary);
            color: white;
            padding: 10px 12px;
            text-align: left;
            font-weight: 600;
            font-size: 0.6875rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .data-table td { padding: 10px 12px; border-bottom: 1px solid var(--border); }
        .data-table tr:hover { background: var(--light); }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.625rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .badge-critical { background: rgba(229,62,62,0.15); color: #c53030; }
        .badge-high { background: rgba(237,137,54,0.15); color: #c05621; }
        .badge-medium { background: rgba(236,201,75,0.2); color: #b7791f; }
        .badge-low { background: rgba(72,187,120,0.15); color: #276749; }
        .badge-open { background: rgba(229,62,62,0.15); color: #c53030; }
        .badge-in_progress { background: rgba(66,153,225,0.15); color: #2b6cb0; }
        .badge-resolved { background: rgba(72,187,120,0.15); color: #276749; }
        .badge-overdue { background: rgba(128,90,213,0.15); color: #6b46c1; }
        .badge-info { background: rgba(66,153,225,0.15); color: #2b6cb0; }

        /* AI Analysis Box */
        .ai-analysis-box {
            background: linear-gradient(135deg, #1a1c2e 0%, #2d2f45 100%);
            border-radius: 12px;
            padding: 20px 24px;
            color: white;
            margin-bottom: 16px;
        }

        .ai-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
        }

        .ai-badge {
            background: linear-gradient(135deg, #667eea, #764ba2);
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.6875rem;
            font-weight: 700;
        }

        .ai-title { font-size: 1rem; font-weight: 700; }
        .ai-content { font-size: 0.875rem; line-height: 1.7; opacity: 0.95; }
        .ai-content ul { margin: 10px 0 10px 20px; }
        .ai-content li { margin-bottom: 6px; }

        /* Threat Level Indicator */
        .threat-level {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 0.75rem;
        }

        .threat-level.critical { background: var(--danger); color: white; }
        .threat-level.high { background: var(--warning); color: white; }
        .threat-level.medium { background: #ecc94b; color: #744210; }
        .threat-level.low { background: var(--success); color: white; }

        /* Recommendation Card */
        .recommendation-card {
            border-radius: 10px;
            padding: 16px 20px;
            margin-bottom: 12px;
        }

        .recommendation-card.critical {
            background: rgba(229,62,62,0.08);
            border-left: 4px solid var(--danger);
        }

        .recommendation-card.high {
            background: rgba(237,137,54,0.08);
            border-left: 4px solid var(--warning);
        }

        .recommendation-card.medium {
            background: rgba(236,201,75,0.1);
            border-left: 4px solid #ecc94b;
        }

        .recommendation-card h4 { font-size: 0.875rem; margin-bottom: 8px; }
        .recommendation-card ul { margin: 8px 0 0 20px; font-size: 0.8125rem; line-height: 1.7; }

        /* Chart Container */
        .chart-container {
            background: white;
            border-radius: 10px;
            padding: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }

        .chart-wrapper { position: relative; height: 240px; }
        .charts-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px; }

        /* Issue Flag */
        .issue-flag {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.625rem;
            font-weight: 700;
        }

        .issue-flag.sla-breach { background: var(--danger); color: white; }
        .issue-flag.sla-warning { background: var(--warning); color: white; }
        .issue-flag.sla-ok { background: var(--success); color: white; }

        /* Progress Bar */
        .progress-bar {
            height: 8px;
            background: var(--border);
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            border-radius: 4px;
            transition: width 0.5s ease;
        }

        /* Drop Zone */
        .drop-zone {
            border: 3px dashed var(--border);
            border-radius: 12px;
            padding: 40px 20px;
            text-align: center;
            color: var(--gray);
            margin-bottom: 16px;
        }

        .drop-zone:hover { border-color: var(--primary); background: rgba(102,126,234,0.05); }
        .drop-zone-icon { font-size: 2.5rem; margin-bottom: 12px; }
        .drop-zone-text { font-size: 0.9375rem; font-weight: 600; }

        /* Properties */
        .property-group { margin-bottom: 16px; }
        .property-label { font-size: 0.6875rem; font-weight: 700; color: var(--primary); text-transform: uppercase; margin-bottom: 6px; display: block; }

        .property-input {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid var(--border);
            border-radius: 8px;
            font-size: 0.8125rem;
            font-family: var(--font);
        }

        .property-input:focus { outline: none; border-color: var(--primary); }

        .property-checkbox {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 0;
            cursor: pointer;
            font-size: 0.8125rem;
        }

        .property-checkbox input { width: 16px; height: 16px; accent-color: var(--primary); }

        /* Modal */
        .modal-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.6);
            backdrop-filter: blur(4px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            padding: 20px;
        }

        .modal-overlay.active { display: flex; }

        .modal {
            background: white;
            border-radius: 16px;
            max-width: 500px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 18px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-title { font-size: 1.0625rem; font-weight: 700; }

        .modal-close {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 1.125rem;
        }

        .modal-body { padding: 20px 24px; }
        .modal-footer { padding: 14px 24px; border-top: 1px solid var(--border); display: flex; justify-content: flex-end; gap: 8px; }

        /* Loading */
        .loading-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(255,255,255,0.95);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 3000;
            flex-direction: column;
            gap: 12px;
        }

        .loading-overlay.active { display: flex; }

        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid var(--border);
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin { to { transform: rotate(360deg); } }

        /* Toast */
        .toast-container { position: fixed; top: 20px; right: 20px; z-index: 2000; }

        .toast {
            background: white;
            border-radius: 10px;
            padding: 12px 16px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.3s ease;
            min-width: 260px;
        }

        @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }

        .toast.success { border-left: 4px solid var(--success); }
        .toast.error { border-left: 4px solid var(--danger); }
        .toast.info { border-left: 4px solid var(--info); }

        /* Footer */
        .footer { text-align: center; color: white; padding: 24px; margin-top: 20px; font-size: 0.8125rem; }

        /* Print */
        @media print {
            body { background: white; }
            .header, .nav, .components-panel, .properties-panel, .canvas-toolbar, .footer, .section-actions { display: none !important; }
            .builder-layout { display: block; }
            .report-canvas { padding: 0; background: white; }
            .report-preview { box-shadow: none; max-width: none; }
        }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
        .fade-in { animation: fadeIn 0.3s ease; }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div>
                <h1>AI Report Builder</h1>
                <p class="header-subtitle">Advanced security analysis with AI-powered insights and 2-year quarterly tracking</p>
            </div>
            <a href="admin/login.php" class="nav-btn secondary">Settings</a>
        </div>

        <!-- Navigation -->
        <div class="nav">
            <a href="index.php" class="nav-btn secondary">Dashboard</a>
            <a href="scan.php" class="nav-btn secondary">New Scan</a>
            <a href="reports.php" class="nav-btn secondary">View Reports</a>
            <a href="report_builder.php" class="nav-btn">AI Report Builder</a>
            <a href="compliance.php" class="nav-btn secondary">Compliance</a>
        </div>

        <!-- Builder Layout -->
        <div class="builder-layout">
            <!-- Components Panel -->
            <div class="panel components-panel">
                <div class="panel-header">+ Report Components</div>
                <div class="panel-body">
                    <div class="component-category">
                        <div class="category-title">AI Analysis</div>
                        <div class="component-item" draggable="true" data-component="ai-threat-analysis">
                            <div class="component-icon">🤖</div>
                            <div class="component-info">
                                <h4>AI Threat Analysis</h4>
                                <p>Intelligent threat assessment</p>
                            </div>
                        </div>
                        <div class="component-item" draggable="true" data-component="ai-recommendations">
                            <div class="component-icon">💡</div>
                            <div class="component-info">
                                <h4>AI Recommendations</h4>
                                <p>Prioritized remediation</p>
                            </div>
                        </div>
                        <div class="component-item" draggable="true" data-component="vulnerability-deep-dive">
                            <div class="component-icon">🔍</div>
                            <div class="component-info">
                                <h4>Vulnerability Deep Dive</h4>
                                <p>Detailed CVE analysis</p>
                            </div>
                        </div>
                    </div>

                    <div class="component-category">
                        <div class="category-title">Quarterly Reports</div>
                        <div class="component-item" draggable="true" data-component="quarterly-trend">
                            <div class="component-icon">📈</div>
                            <div class="component-info">
                                <h4>2-Year Trend Analysis</h4>
                                <p>Quarterly comparison</p>
                            </div>
                        </div>
                        <div class="component-item" draggable="true" data-component="quarterly-comparison">
                            <div class="component-icon">📊</div>
                            <div class="component-info">
                                <h4>Quarter Comparison</h4>
                                <p>YoY metrics</p>
                            </div>
                        </div>
                    </div>

                    <div class="component-category">
                        <div class="category-title">Issue Tracking</div>
                        <div class="component-item" draggable="true" data-component="issue-tracker">
                            <div class="component-icon">🚩</div>
                            <div class="component-info">
                                <h4>Issue Tracker</h4>
                                <p>Status & SLA tracking</p>
                            </div>
                        </div>
                        <div class="component-item" draggable="true" data-component="overdue-issues">
                            <div class="component-icon">⚠️</div>
                            <div class="component-info">
                                <h4>Overdue Issues</h4>
                                <p>Unattended problems</p>
                            </div>
                        </div>
                        <div class="component-item" draggable="true" data-component="sla-performance">
                            <div class="component-icon">⏱️</div>
                            <div class="component-info">
                                <h4>SLA Performance</h4>
                                <p>Resolution metrics</p>
                            </div>
                        </div>
                    </div>

                    <div class="component-category">
                        <div class="category-title">Summary</div>
                        <div class="component-item" draggable="true" data-component="executive-summary">
                            <div class="component-icon">📝</div>
                            <div class="component-info">
                                <h4>Executive Summary</h4>
                                <p>High-level overview</p>
                            </div>
                        </div>
                        <div class="component-item" draggable="true" data-component="statistics">
                            <div class="component-icon">📊</div>
                            <div class="component-info">
                                <h4>Key Statistics</h4>
                                <p>Metrics dashboard</p>
                            </div>
                        </div>
                    </div>

                    <div class="component-category">
                        <div class="category-title">Data Tables</div>
                        <div class="component-item" draggable="true" data-component="vulnerability-table">
                            <div class="component-icon">🔓</div>
                            <div class="component-info">
                                <h4>Vulnerabilities</h4>
                                <p>Full findings list</p>
                            </div>
                        </div>
                        <div class="component-item" draggable="true" data-component="host-table">
                            <div class="component-icon">💻</div>
                            <div class="component-info">
                                <h4>Host Inventory</h4>
                                <p>Asset list</p>
                            </div>
                        </div>
                        <div class="component-item" draggable="true" data-component="compliance-table">
                            <div class="component-icon">✓</div>
                            <div class="component-info">
                                <h4>Compliance Status</h4>
                                <p>Framework scores</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Canvas Panel -->
            <div class="panel canvas-panel">
                <div class="canvas-toolbar">
                    <div class="toolbar-group">
                        <select class="toolbar-select" id="reportTemplate" onchange="loadTemplate(this.value)">
                            <option value="ai-comprehensive">AI Comprehensive Report</option>
                            <option value="quarterly">Quarterly Report</option>
                            <option value="issue-tracking">Issue Tracking Report</option>
                            <option value="executive">Executive Summary</option>
                            <option value="custom">Custom Report</option>
                        </select>
                        <select class="toolbar-select" id="reportFormat">
                            <option value="pdf">PDF Report</option>
                            <option value="html">HTML Report</option>
                            <option value="csv">CSV Export</option>
                        </select>
                    </div>
                    <div class="toolbar-group">
                        <button class="toolbar-btn secondary" onclick="previewReport()">Preview</button>
                        <button class="toolbar-btn danger" onclick="clearCanvas()">Clear</button>
                        <button class="toolbar-btn success" onclick="generateReport()">Download Report</button>
                    </div>
                </div>

                <div class="report-canvas" id="reportCanvas">
                    <div class="report-preview" id="reportPreview">
                        <div class="report-header-section" id="reportHeaderSection">
                            <div class="report-title" id="reportTitle">AI Security Assessment Report</div>
                            <div class="report-subtitle" id="reportSubtitle">Comprehensive threat analysis with AI-powered insights</div>
                            <div class="report-meta">
                                <div class="report-meta-item">📅 <span id="reportDate"></span></div>
                                <div class="report-meta-item">🎯 Network Assessment</div>
                                <div class="report-meta-item">📊 Q4 2024</div>
                            </div>
                        </div>

                        <div class="report-content" id="reportContent">
                            <div class="drop-zone" id="dropZone">
                                <div class="drop-zone-icon">📄</div>
                                <div class="drop-zone-text">Drag components here or select a template</div>
                            </div>
                            <div id="sectionContainer"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Properties Panel -->
            <div class="panel properties-panel">
                <div class="panel-header">⚙ Properties</div>
                <div class="panel-body">
                    <div class="property-group">
                        <label class="property-label">Report Title</label>
                        <input type="text" class="property-input" id="propTitle" value="AI Security Assessment Report" onchange="updateTitle()">
                    </div>
                    <div class="property-group">
                        <label class="property-label">Report Period</label>
                        <select class="property-input" id="propPeriod">
                            <option value="Q4 2024">Q4 2024</option>
                            <option value="Q3 2024">Q3 2024</option>
                            <option value="Q2 2024">Q2 2024</option>
                            <option value="Q1 2024">Q1 2024</option>
                            <option value="2024 Full Year">2024 Full Year</option>
                            <option value="2023-2024">2023-2024 (2 Years)</option>
                        </select>
                    </div>
                    <div class="property-group">
                        <label class="property-label">Include Sections</label>
                        <label class="property-checkbox"><input type="checkbox" checked> AI Analysis</label>
                        <label class="property-checkbox"><input type="checkbox" checked> Threat Intelligence</label>
                        <label class="property-checkbox"><input type="checkbox" checked> Recommendations</label>
                        <label class="property-checkbox"><input type="checkbox" checked> Issue Tracking</label>
                        <label class="property-checkbox"><input type="checkbox" checked> Quarterly Data</label>
                    </div>
                    <div class="property-group">
                        <label class="property-label">Quick Actions</label>
                        <button class="toolbar-btn secondary" style="width:100%;margin-bottom:8px;" onclick="loadTemplate('ai-comprehensive')">Load AI Report</button>
                        <button class="toolbar-btn secondary" style="width:100%;" onclick="addAllSections()">Add All Sections</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="footer">
            <p><strong><?= htmlspecialchars($app_name) ?></strong> | AI Report Builder</p>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal-overlay" id="exportModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Download Report</h3>
                <button class="modal-close" onclick="closeModal('exportModal')">×</button>
            </div>
            <div class="modal-body">
                <div class="property-group">
                    <label class="property-label">Format</label>
                    <select class="property-input" id="exportFormat">
                        <option value="pdf">PDF Document</option>
                        <option value="html">HTML Report</option>
                        <option value="csv">CSV Data</option>
                    </select>
                </div>
                <div class="property-group">
                    <label class="property-label">Filename</label>
                    <input type="text" class="property-input" id="exportFilename" value="ai_security_report">
                </div>
            </div>
            <div class="modal-footer">
                <button class="toolbar-btn secondary" onclick="closeModal('exportModal')">Cancel</button>
                <button class="toolbar-btn success" onclick="downloadReport()">Download</button>
            </div>
        </div>
    </div>

    <!-- Loading -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner"></div>
        <div id="loadingText">Generating report...</div>
    </div>

    <!-- Toast -->
    <div class="toast-container" id="toastContainer"></div>

    <script>
        // Data
        const sampleData = <?= json_encode($sampleData) ?>;
        let reportSections = [];
        let sectionId = 0;
        let charts = {};

        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('reportDate').textContent = new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
            initDragDrop();
            loadTemplate('ai-comprehensive');
        });

        // Drag & Drop
        function initDragDrop() {
            document.querySelectorAll('.component-item').forEach(c => {
                c.addEventListener('dragstart', e => e.dataTransfer.setData('component', c.dataset.component));
            });

            ['dropZone', 'sectionContainer'].forEach(id => {
                const el = document.getElementById(id);
                el.addEventListener('dragover', e => { e.preventDefault(); el.classList.add('active'); });
                el.addEventListener('dragleave', () => el.classList.remove('active'));
                el.addEventListener('drop', e => {
                    e.preventDefault();
                    el.classList.remove('active');
                    const type = e.dataTransfer.getData('component');
                    if (type) addSection(type);
                });
            });
        }

        // Add Section
        function addSection(type) {
            document.getElementById('dropZone').style.display = 'none';
            const id = 'section-' + (++sectionId);
            const html = getSectionHtml(type, id);
            document.getElementById('sectionContainer').insertAdjacentHTML('beforeend', html);
            reportSections.push({ id, type });
            setTimeout(() => initCharts(id, type), 100);
            showToast('Section added', 'success');
        }

        // Delete Section
        function deleteSection(id) {
            if (charts[id]) { charts[id].destroy(); delete charts[id]; }
            document.getElementById(id)?.remove();
            reportSections = reportSections.filter(s => s.id !== id);
            if (!reportSections.length) document.getElementById('dropZone').style.display = 'block';
            showToast('Section removed', 'info');
        }

        // Section HTML Generator
        function getSectionHtml(type, id) {
            const d = sampleData;
            const s = d.statistics;
            const q = d.quarterlyData;
            const v = d.vulnerabilities;
            const i = d.issueTracking;
            const t = d.threatIntelligence;

            const sections = {
                'ai-threat-analysis': `
                    <div class="report-section fade-in" id="${id}">
                        <div class="section-header">
                            <div class="section-title">🤖 AI Threat Analysis</div>
                            <div class="section-actions"><button class="section-btn delete" onclick="deleteSection('${id}')">🗑</button></div>
                        </div>
                        <div class="ai-analysis-box">
                            <div class="ai-header">
                                <span class="ai-badge">AI POWERED</span>
                                <span class="ai-title">Intelligent Threat Assessment</span>
                                <span class="threat-level critical" style="margin-left:auto;">RISK: ${t.overall_risk_level}</span>
                            </div>
                            <div class="ai-content">
                                <p><strong>Overall Risk Score: ${t.risk_score}/10</strong> (Trend: ${t.trend})</p>
                                <p style="margin-top:12px;"><strong>Key Findings:</strong></p>
                                <ul>${t.key_findings.map(f => `<li>${f}</li>`).join('')}</ul>
                                <p style="margin-top:12px;"><strong>Active Threat Actors:</strong></p>
                                <ul>${t.threat_actors.map(a => `<li><strong>${a.name}</strong> - Activity: ${a.activity}, Targets: ${a.targets}</li>`).join('')}</ul>
                                <p style="margin-top:12px;"><strong>Primary Attack Vectors:</strong></p>
                                <ul>${t.attack_vectors.map(a => `<li><strong>${a.vector}</strong> - Likelihood: ${a.likelihood}, Impact: ${a.impact}</li>`).join('')}</ul>
                            </div>
                        </div>
                    </div>
                `,
                'ai-recommendations': `
                    <div class="report-section fade-in" id="${id}">
                        <div class="section-header">
                            <div class="section-title">💡 AI-Powered Recommendations</div>
                            <div class="section-actions"><button class="section-btn delete" onclick="deleteSection('${id}')">🗑</button></div>
                        </div>
                        <div class="recommendation-card critical">
                            <h4 style="color:#c53030;">🚨 CRITICAL - Immediate Action Required (0-48 hours)</h4>
                            <ul>
                                <li><strong>CVE-2024-3094 (XZ Utils Backdoor):</strong> Downgrade XZ Utils to 5.4.x immediately on all Linux systems. This is a supply chain attack with maximum severity.</li>
                                <li><strong>CVE-2024-1086 (Kernel Exploit):</strong> Apply kernel patches urgently. Active exploitation observed - attackers gaining root access.</li>
                                <li><strong>CVE-2024-21762 (FortiOS RCE):</strong> Patch VPN appliances or disable SSL VPN temporarily. APT groups actively exploiting.</li>
                            </ul>
                        </div>
                        <div class="recommendation-card high">
                            <h4 style="color:#c05621;">⚠️ HIGH PRIORITY - Action Within 1-2 Weeks</h4>
                            <ul>
                                <li><strong>Authentication Bypass Vulnerabilities:</strong> Update PAN-OS and TeamCity. Implement network segmentation for management interfaces.</li>
                                <li><strong>Enable MFA:</strong> Enforce multi-factor authentication on all administrative and VPN access points.</li>
                                <li><strong>Network Segmentation:</strong> Isolate critical assets. Database servers should not be directly accessible from DMZ.</li>
                            </ul>
                        </div>
                        <div class="recommendation-card medium">
                            <h4 style="color:#b7791f;">📋 MEDIUM PRIORITY - Plan for 1-3 Months</h4>
                            <ul>
                                <li><strong>Vulnerability Management:</strong> Implement automated scanning and patch management program.</li>
                                <li><strong>Supply Chain Security:</strong> Audit third-party dependencies. Implement software composition analysis.</li>
                                <li><strong>Security Monitoring:</strong> Deploy EDR solution and enhance SIEM rules for detecting exploitation attempts.</li>
                            </ul>
                        </div>
                    </div>
                `,
                'vulnerability-deep-dive': `
                    <div class="report-section fade-in" id="${id}">
                        <div class="section-header">
                            <div class="section-title">🔍 Vulnerability Deep Dive Analysis</div>
                            <div class="section-actions"><button class="section-btn delete" onclick="deleteSection('${id}')">🗑</button></div>
                        </div>
                        ${v.slice(0, 5).map(vuln => `
                            <div style="background:white;border-radius:10px;padding:16px;margin-bottom:12px;border-left:4px solid ${vuln.severity === 'critical' ? '#e53e3e' : vuln.severity === 'high' ? '#ed8936' : '#ecc94b'};">
                                <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:10px;">
                                    <div>
                                        <strong style="font-size:0.9375rem;">${vuln.cve}</strong>
                                        <span class="badge badge-${vuln.severity}" style="margin-left:8px;">${vuln.severity}</span>
                                        ${vuln.exploit_available ? '<span class="badge badge-critical" style="margin-left:4px;">EXPLOIT AVAILABLE</span>' : ''}
                                    </div>
                                    <div style="text-align:right;">
                                        <div style="font-size:1.25rem;font-weight:800;color:${vuln.cvss >= 9 ? '#c53030' : vuln.cvss >= 7 ? '#c05621' : '#b7791f'};">${vuln.cvss}</div>
                                        <div style="font-size:0.6875rem;color:#718096;">CVSS Score</div>
                                    </div>
                                </div>
                                <p style="font-weight:600;margin-bottom:8px;">${vuln.title}</p>
                                <p style="font-size:0.8125rem;color:#4a5568;margin-bottom:10px;">${vuln.description}</p>
                                <div style="background:#f7fafc;padding:12px;border-radius:8px;margin-bottom:10px;">
                                    <p style="font-size:0.75rem;font-weight:700;color:#e53e3e;margin-bottom:6px;">🎯 THREAT ANALYSIS:</p>
                                    <p style="font-size:0.8125rem;">${vuln.threat_analysis}</p>
                                </div>
                                <div style="background:#fffaf0;padding:12px;border-radius:8px;margin-bottom:10px;">
                                    <p style="font-size:0.75rem;font-weight:700;color:#c05621;margin-bottom:6px;">💼 BUSINESS IMPACT:</p>
                                    <p style="font-size:0.8125rem;">${vuln.business_impact}</p>
                                </div>
                                <div style="background:#f0fff4;padding:12px;border-radius:8px;">
                                    <p style="font-size:0.75rem;font-weight:700;color:#276749;margin-bottom:6px;">✅ RECOMMENDATION:</p>
                                    <p style="font-size:0.8125rem;">${vuln.recommendation}</p>
                                    <ul style="margin:8px 0 0 16px;font-size:0.8125rem;">
                                        ${vuln.remediation_steps.map(step => `<li>${step}</li>`).join('')}
                                    </ul>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                `,
                'quarterly-trend': `
                    <div class="report-section fade-in" id="${id}">
                        <div class="section-header">
                            <div class="section-title">📈 2-Year Quarterly Trend Analysis (2023-2024)</div>
                            <div class="section-actions"><button class="section-btn delete" onclick="deleteSection('${id}')">🗑</button></div>
                        </div>
                        <div class="charts-row">
                            <div class="chart-container">
                                <h4 style="font-size:0.8125rem;margin-bottom:12px;text-align:center;">Vulnerability Trend</h4>
                                <div class="chart-wrapper"><canvas id="chart-trend-${id}"></canvas></div>
                            </div>
                            <div class="chart-container">
                                <h4 style="font-size:0.8125rem;margin-bottom:12px;text-align:center;">Resolution Rate</h4>
                                <div class="chart-wrapper"><canvas id="chart-resolution-${id}"></canvas></div>
                            </div>
                        </div>
                        <div class="table-container" style="margin-top:16px;">
                            <table class="data-table">
                                <thead>
                                    <tr><th>Quarter</th><th>Scans</th><th>Hosts</th><th>Critical</th><th>High</th><th>Medium</th><th>Resolved</th><th>Pending</th><th>Compliance</th></tr>
                                </thead>
                                <tbody>
                                    ${Object.values(q).map(qd => `
                                        <tr>
                                            <td style="font-weight:600;">${qd.quarter}</td>
                                            <td>${qd.scans}</td>
                                            <td>${qd.hosts}</td>
                                            <td style="color:#c53030;font-weight:600;">${qd.critical}</td>
                                            <td style="color:#c05621;font-weight:600;">${qd.high}</td>
                                            <td style="color:#b7791f;">${qd.medium}</td>
                                            <td style="color:#276749;">${qd.resolved}</td>
                                            <td style="color:#c53030;">${qd.pending}</td>
                                            <td><strong>${qd.compliance}%</strong></td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                `,
                'issue-tracker': `
                    <div class="report-section fade-in" id="${id}">
                        <div class="section-header">
                            <div class="section-title">🚩 Issue Tracker - Status & SLA Compliance</div>
                            <div class="section-actions"><button class="section-btn delete" onclick="deleteSection('${id}')">🗑</button></div>
                        </div>
                        <div class="stats-grid" style="margin-bottom:16px;">
                            <div class="stat-card"><div class="stat-value" style="color:#e53e3e;">${s.issues_open}</div><div class="stat-label">Open</div></div>
                            <div class="stat-card"><div class="stat-value" style="color:#4299e1;">${s.issues_in_progress}</div><div class="stat-label">In Progress</div></div>
                            <div class="stat-card"><div class="stat-value" style="color:#48bb78;">${s.issues_resolved}</div><div class="stat-label">Resolved</div></div>
                            <div class="stat-card"><div class="stat-value" style="color:#6b46c1;">${s.issues_overdue}</div><div class="stat-label">Overdue</div></div>
                            <div class="stat-card"><div class="stat-value">${s.sla_compliance}%</div><div class="stat-label">SLA Met</div></div>
                        </div>
                        <div class="table-container">
                            <table class="data-table">
                                <thead><tr><th>Issue ID</th><th>Title</th><th>Severity</th><th>Status</th><th>Raised</th><th>Due</th><th>Owner</th><th>SLA</th></tr></thead>
                                <tbody>
                                    ${i.map(issue => `
                                        <tr>
                                            <td style="font-family:var(--mono);font-size:0.75rem;">${issue.id}</td>
                                            <td style="font-weight:500;">${issue.title}</td>
                                            <td><span class="badge badge-${issue.severity}">${issue.severity}</span></td>
                                            <td><span class="badge badge-${issue.status}">${issue.status.replace('_', ' ')}</span></td>
                                            <td>${issue.raised}</td>
                                            <td>${issue.due}</td>
                                            <td>${issue.owner}</td>
                                            <td><span class="issue-flag ${issue.sla_met ? 'sla-ok' : 'sla-breach'}">${issue.sla_met ? '✓ MET' : '✗ BREACH'}</span></td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                `,
                'overdue-issues': `
                    <div class="report-section fade-in" id="${id}">
                        <div class="section-header">
                            <div class="section-title">⚠️ Overdue & Unattended Issues - Requires Immediate Attention</div>
                            <div class="section-actions"><button class="section-btn delete" onclick="deleteSection('${id}')">🗑</button></div>
                        </div>
                        <div class="ai-analysis-box" style="background:linear-gradient(135deg, #742a2a, #9b2c2c);">
                            <div class="ai-content">
                                <p style="font-weight:700;font-size:1rem;margin-bottom:10px;">⚠️ ${i.filter(x => x.status === 'overdue' || x.status === 'open').length} Issues Require Immediate Action</p>
                                <p>The following issues have exceeded their SLA deadlines or remain unaddressed. These represent significant security risks and must be prioritized.</p>
                            </div>
                        </div>
                        ${i.filter(x => x.status === 'overdue' || x.status === 'open').map(issue => `
                            <div style="background:rgba(229,62,62,0.08);border-left:4px solid #e53e3e;border-radius:8px;padding:16px;margin-bottom:12px;">
                                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                                    <strong style="font-size:0.9375rem;">${issue.id}: ${issue.title}</strong>
                                    <span class="badge badge-${issue.status}">${issue.status.replace('_', ' ')}</span>
                                </div>
                                <p style="font-size:0.8125rem;color:#4a5568;margin-bottom:8px;"><strong>Severity:</strong> ${issue.severity.toUpperCase()} | <strong>Affected Systems:</strong> ${issue.affected} | <strong>Owner:</strong> ${issue.owner}</p>
                                <p style="font-size:0.8125rem;color:#c53030;"><strong>Raised:</strong> ${issue.raised} | <strong>Due:</strong> ${issue.due} | <strong>Days Overdue:</strong> ${Math.floor((new Date() - new Date(issue.due)) / (1000 * 60 * 60 * 24))}</p>
                            </div>
                        `).join('')}
                    </div>
                `,
                'executive-summary': `
                    <div class="report-section fade-in" id="${id}">
                        <div class="section-header">
                            <div class="section-title">📝 Executive Summary</div>
                            <div class="section-actions"><button class="section-btn delete" onclick="deleteSection('${id}')">🗑</button></div>
                        </div>
                        <div style="line-height:1.8;">
                            <p style="margin-bottom:16px;">This comprehensive security assessment analyzed <strong>${s.total_hosts}</strong> hosts across the enterprise network through <strong>${s.total_scans}</strong> security scans. The assessment identified <strong>${s.total_vulnerabilities}</strong> vulnerabilities, including <strong style="color:#c53030;">${s.critical_count} critical</strong> and <strong style="color:#c05621;">${s.high_count} high-severity</strong> issues requiring immediate attention.</p>
                            <p style="margin-bottom:16px;">The overall security posture is rated <strong style="color:#ed8936;">MODERATE-HIGH RISK</strong> with a risk score of <strong>${s.avg_risk_score}/10</strong>. The organization's compliance score stands at <strong>${s.compliance_score}%</strong>, with improvements needed in PCI DSS and HIPAA frameworks.</p>
                            <p><strong>Key Concerns:</strong></p>
                            <ul style="margin:8px 0 16px 20px;">
                                <li>Active exploitation of VPN infrastructure vulnerabilities (FortiOS, Ivanti)</li>
                                <li>Supply chain attack vector identified requiring immediate remediation</li>
                                <li>CI/CD pipeline security gaps exposing software delivery</li>
                                <li>${s.issues_overdue} overdue issues representing SLA breaches</li>
                            </ul>
                        </div>
                    </div>
                `,
                'statistics': `
                    <div class="report-section fade-in" id="${id}">
                        <div class="section-header">
                            <div class="section-title">📊 Key Statistics</div>
                            <div class="section-actions"><button class="section-btn delete" onclick="deleteSection('${id}')">🗑</button></div>
                        </div>
                        <div class="stats-grid">
                            <div class="stat-card"><div class="stat-value">${s.total_scans}</div><div class="stat-label">Total Scans</div></div>
                            <div class="stat-card"><div class="stat-value">${s.total_hosts}</div><div class="stat-label">Hosts</div></div>
                            <div class="stat-card critical"><div class="stat-value" style="color:#c53030;">${s.critical_count}</div><div class="stat-label">Critical</div></div>
                            <div class="stat-card high"><div class="stat-value" style="color:#c05621;">${s.high_count}</div><div class="stat-label">High</div></div>
                            <div class="stat-card medium"><div class="stat-value" style="color:#b7791f;">${s.medium_count}</div><div class="stat-label">Medium</div></div>
                            <div class="stat-card low"><div class="stat-value" style="color:#276749;">${s.low_count}</div><div class="stat-label">Low</div></div>
                        </div>
                    </div>
                `,
                'vulnerability-table': `
                    <div class="report-section fade-in" id="${id}">
                        <div class="section-header">
                            <div class="section-title">🔓 Vulnerability Findings</div>
                            <div class="section-actions"><button class="section-btn delete" onclick="deleteSection('${id}')">🗑</button></div>
                        </div>
                        <div class="table-container">
                            <table class="data-table">
                                <thead><tr><th>CVE</th><th>Title</th><th>Severity</th><th>CVSS</th><th>Hosts</th><th>Status</th><th>Exploit</th></tr></thead>
                                <tbody>
                                    ${v.map(vuln => `
                                        <tr>
                                            <td style="font-family:var(--mono);font-size:0.75rem;">${vuln.cve}</td>
                                            <td style="font-weight:500;">${vuln.title}</td>
                                            <td><span class="badge badge-${vuln.severity}">${vuln.severity}</span></td>
                                            <td style="font-weight:700;color:${vuln.cvss >= 9 ? '#c53030' : vuln.cvss >= 7 ? '#c05621' : '#b7791f'};">${vuln.cvss}</td>
                                            <td>${vuln.hosts}</td>
                                            <td><span class="badge badge-${vuln.status === 'open' ? 'open' : 'resolved'}">${vuln.status}</span></td>
                                            <td>${vuln.exploit_available ? '<span class="badge badge-critical">YES</span>' : '<span class="badge badge-low">NO</span>'}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                `,
                'host-table': `
                    <div class="report-section fade-in" id="${id}">
                        <div class="section-header">
                            <div class="section-title">💻 Host Inventory</div>
                            <div class="section-actions"><button class="section-btn delete" onclick="deleteSection('${id}')">🗑</button></div>
                        </div>
                        <div class="table-container">
                            <table class="data-table">
                                <thead><tr><th>IP</th><th>Hostname</th><th>OS</th><th>Type</th><th>Risk</th><th>Vulns</th></tr></thead>
                                <tbody>
                                    ${d.hosts.map(h => `
                                        <tr>
                                            <td style="font-family:var(--mono);font-size:0.75rem;">${h.ip}</td>
                                            <td style="font-weight:500;">${h.hostname}</td>
                                            <td>${h.os}</td>
                                            <td><span class="badge badge-info">${h.type}</span></td>
                                            <td style="font-weight:700;color:${h.risk >= 7 ? '#c53030' : h.risk >= 5 ? '#c05621' : '#276749'};">${h.risk}</td>
                                            <td>${h.vulns}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                `,
                'compliance-table': `
                    <div class="report-section fade-in" id="${id}">
                        <div class="section-header">
                            <div class="section-title">✓ Compliance Status</div>
                            <div class="section-actions"><button class="section-btn delete" onclick="deleteSection('${id}')">🗑</button></div>
                        </div>
                        <div class="table-container">
                            <table class="data-table">
                                <thead><tr><th>Framework</th><th>Score</th><th>Passed</th><th>Failed</th><th>Trend</th><th>Status</th></tr></thead>
                                <tbody>
                                    ${d.compliance.map(c => `
                                        <tr>
                                            <td style="font-weight:600;">${c.framework}</td>
                                            <td style="font-weight:700;color:${c.score >= 80 ? '#276749' : c.score >= 60 ? '#c05621' : '#c53030'};">${c.score}%</td>
                                            <td style="color:#276749;">${c.passed}</td>
                                            <td style="color:#c53030;">${c.failed}</td>
                                            <td style="color:${c.trend.startsWith('+') ? '#276749' : '#c53030'};">${c.trend}</td>
                                            <td><span class="badge badge-${c.score >= 80 ? 'low' : c.score >= 60 ? 'medium' : 'critical'}">${c.score >= 80 ? 'COMPLIANT' : c.score >= 60 ? 'PARTIAL' : 'NON-COMPLIANT'}</span></td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                `,
                'sla-performance': `
                    <div class="report-section fade-in" id="${id}">
                        <div class="section-header">
                            <div class="section-title">⏱️ SLA Performance Metrics</div>
                            <div class="section-actions"><button class="section-btn delete" onclick="deleteSection('${id}')">🗑</button></div>
                        </div>
                        <div class="stats-grid" style="margin-bottom:16px;">
                            <div class="stat-card"><div class="stat-value">${s.sla_compliance}%</div><div class="stat-label">SLA Compliance</div></div>
                            <div class="stat-card"><div class="stat-value">${i.filter(x => x.sla_met).length}</div><div class="stat-label">SLA Met</div></div>
                            <div class="stat-card critical"><div class="stat-value">${i.filter(x => !x.sla_met).length}</div><div class="stat-label">SLA Breached</div></div>
                        </div>
                        <div class="chart-container">
                            <h4 style="font-size:0.8125rem;margin-bottom:12px;text-align:center;">SLA Performance by Category</h4>
                            <div class="chart-wrapper"><canvas id="chart-sla-${id}"></canvas></div>
                        </div>
                    </div>
                `,
                'quarterly-comparison': `
                    <div class="report-section fade-in" id="${id}">
                        <div class="section-header">
                            <div class="section-title">📊 Year-over-Year Comparison</div>
                            <div class="section-actions"><button class="section-btn delete" onclick="deleteSection('${id}')">🗑</button></div>
                        </div>
                        <div class="charts-row">
                            <div class="chart-container">
                                <h4 style="font-size:0.8125rem;margin-bottom:12px;text-align:center;">2023 vs 2024 - Vulnerabilities</h4>
                                <div class="chart-wrapper"><canvas id="chart-yoy-${id}"></canvas></div>
                            </div>
                            <div class="chart-container">
                                <h4 style="font-size:0.8125rem;margin-bottom:12px;text-align:center;">Compliance Trend</h4>
                                <div class="chart-wrapper"><canvas id="chart-compliance-${id}"></canvas></div>
                            </div>
                        </div>
                    </div>
                `
            };
            return sections[type] || `<div class="report-section" id="${id}">Unknown: ${type}</div>`;
        }

        // Initialize Charts
        function initCharts(id, type) {
            const q = sampleData.quarterlyData;
            const quarters = Object.values(q).map(x => x.quarter);

            if (type === 'quarterly-trend') {
                charts[`trend-${id}`] = new Chart(document.getElementById(`chart-trend-${id}`), {
                    type: 'line',
                    data: {
                        labels: quarters,
                        datasets: [
                            { label: 'Critical', data: Object.values(q).map(x => x.critical), borderColor: '#e53e3e', backgroundColor: 'rgba(229,62,62,0.1)', fill: true, tension: 0.4 },
                            { label: 'High', data: Object.values(q).map(x => x.high), borderColor: '#ed8936', backgroundColor: 'rgba(237,137,54,0.1)', fill: true, tension: 0.4 }
                        ]
                    },
                    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { font: { size: 10 } } } } }
                });

                charts[`resolution-${id}`] = new Chart(document.getElementById(`chart-resolution-${id}`), {
                    type: 'bar',
                    data: {
                        labels: quarters,
                        datasets: [
                            { label: 'Resolved', data: Object.values(q).map(x => x.resolved), backgroundColor: '#48bb78' },
                            { label: 'Pending', data: Object.values(q).map(x => x.pending), backgroundColor: '#e53e3e' }
                        ]
                    },
                    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { font: { size: 10 } } } } }
                });
            }

            if (type === 'sla-performance') {
                const categories = ['Patch Management', 'Application Security', 'Configuration', 'Network Security', 'Access Control'];
                charts[`sla-${id}`] = new Chart(document.getElementById(`chart-sla-${id}`), {
                    type: 'bar',
                    data: {
                        labels: categories,
                        datasets: [{ label: 'SLA Compliance %', data: [92, 88, 75, 60, 85], backgroundColor: ['#48bb78', '#48bb78', '#ed8936', '#e53e3e', '#48bb78'] }]
                    },
                    options: { responsive: true, maintainAspectRatio: false, indexAxis: 'y', plugins: { legend: { display: false } } }
                });
            }

            if (type === 'quarterly-comparison') {
                charts[`yoy-${id}`] = new Chart(document.getElementById(`chart-yoy-${id}`), {
                    type: 'bar',
                    data: {
                        labels: ['Q1', 'Q2', 'Q3', 'Q4'],
                        datasets: [
                            { label: '2023', data: [q['2023-Q1'].critical + q['2023-Q1'].high, q['2023-Q2'].critical + q['2023-Q2'].high, q['2023-Q3'].critical + q['2023-Q3'].high, q['2023-Q4'].critical + q['2023-Q4'].high], backgroundColor: '#667eea' },
                            { label: '2024', data: [q['2024-Q1'].critical + q['2024-Q1'].high, q['2024-Q2'].critical + q['2024-Q2'].high, q['2024-Q3'].critical + q['2024-Q3'].high, q['2024-Q4'].critical + q['2024-Q4'].high], backgroundColor: '#764ba2' }
                        ]
                    },
                    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { font: { size: 10 } } } } }
                });

                charts[`compliance-${id}`] = new Chart(document.getElementById(`chart-compliance-${id}`), {
                    type: 'line',
                    data: {
                        labels: quarters,
                        datasets: [{ label: 'Compliance %', data: Object.values(q).map(x => x.compliance), borderColor: '#48bb78', backgroundColor: 'rgba(72,187,120,0.2)', fill: true, tension: 0.4 }]
                    },
                    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { min: 60, max: 100 } } }
                });
            }
        }

        // Templates
        function loadTemplate(type) {
            clearCanvas(false);
            const templates = {
                'ai-comprehensive': ['executive-summary', 'ai-threat-analysis', 'statistics', 'vulnerability-deep-dive', 'ai-recommendations', 'issue-tracker', 'quarterly-trend'],
                'quarterly': ['executive-summary', 'statistics', 'quarterly-trend', 'quarterly-comparison', 'compliance-table'],
                'issue-tracking': ['executive-summary', 'issue-tracker', 'overdue-issues', 'sla-performance'],
                'executive': ['executive-summary', 'statistics', 'ai-threat-analysis', 'ai-recommendations'],
                'custom': []
            };
            (templates[type] || []).forEach((s, i) => setTimeout(() => addSection(s), i * 80));
            if (templates[type]?.length) showToast('Template loaded', 'success');
        }

        function clearCanvas(msg = true) {
            Object.values(charts).forEach(c => c.destroy());
            charts = {};
            document.getElementById('sectionContainer').innerHTML = '';
            document.getElementById('dropZone').style.display = 'block';
            reportSections = [];
            sectionId = 0;
            if (msg) showToast('Canvas cleared', 'info');
        }

        function addAllSections() {
            clearCanvas(false);
            ['executive-summary', 'ai-threat-analysis', 'ai-recommendations', 'vulnerability-deep-dive', 'statistics', 'vulnerability-table', 'host-table', 'compliance-table', 'quarterly-trend', 'quarterly-comparison', 'issue-tracker', 'overdue-issues', 'sla-performance'].forEach((s, i) => setTimeout(() => addSection(s), i * 60));
            showToast('All sections added', 'success');
        }

        // Export
        function updateTitle() { document.getElementById('reportTitle').textContent = document.getElementById('propTitle').value; }
        function generateReport() { document.getElementById('exportModal').classList.add('active'); }
        function closeModal(id) { document.getElementById(id).classList.remove('active'); }

        function previewReport() {
            const preview = document.getElementById('reportPreview').cloneNode(true);
            preview.querySelectorAll('.section-actions').forEach(e => e.remove());
            const win = window.open('', '_blank');
            win.document.write(`<!DOCTYPE html><html><head><title>Report Preview</title><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"><style>*{margin:0;padding:0;box-sizing:border-box}body{font-family:'Inter',sans-serif;background:#f5f5f5;padding:40px}.report{max-width:900px;margin:0 auto;background:white;box-shadow:0 10px 40px rgba(0,0,0,0.1);border-radius:12px;overflow:hidden}</style></head><body><div class="report">${preview.innerHTML}</div></body></html>`);
            win.document.close();
        }

        async function downloadReport() {
            const format = document.getElementById('exportFormat').value;
            const filename = document.getElementById('exportFilename').value || 'ai_security_report';
            closeModal('exportModal');
            document.getElementById('loadingText').textContent = 'Generating ' + format.toUpperCase() + '...';
            document.getElementById('loadingOverlay').classList.add('active');

            try {
                if (format === 'pdf') {
                    const el = document.getElementById('reportPreview').cloneNode(true);
                    el.querySelectorAll('.section-actions, .drop-zone').forEach(e => e.remove());
                    await html2pdf().set({ margin: 10, filename: filename + '.pdf', image: { type: 'jpeg', quality: 0.98 }, html2canvas: { scale: 2 }, jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' } }).from(el).save();
                } else if (format === 'html') {
                    const el = document.getElementById('reportPreview').cloneNode(true);
                    el.querySelectorAll('.section-actions, .drop-zone').forEach(e => e.remove());
                    const blob = new Blob([`<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Security Report</title><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"><style>*{margin:0;padding:0;box-sizing:border-box}body{font-family:'Inter',sans-serif;background:#f5f5f5;padding:40px}.report{max-width:900px;margin:0 auto;background:white;box-shadow:0 10px 40px rgba(0,0,0,0.1);border-radius:12px;overflow:hidden}</style></head><body><div class="report">${el.innerHTML}</div></body></html>`], { type: 'text/html' });
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url; a.download = filename + '.html'; a.click();
                    URL.revokeObjectURL(url);
                } else if (format === 'csv') {
                    let csv = 'CVE,Title,Severity,CVSS,Hosts,Status,Exploit Available\n';
                    sampleData.vulnerabilities.forEach(v => csv += `"${v.cve}","${v.title}","${v.severity}",${v.cvss},${v.hosts},"${v.status}",${v.exploit_available}\n`);
                    const blob = new Blob([csv], { type: 'text/csv' });
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url; a.download = filename + '.csv'; a.click();
                    URL.revokeObjectURL(url);
                }
                showToast('Report downloaded!', 'success');
            } catch (e) {
                console.error(e);
                showToast('Error generating report', 'error');
            }
            document.getElementById('loadingOverlay').classList.remove('active');
        }

        function showToast(msg, type = 'info') {
            const icons = { success: '✓', error: '✕', info: 'ℹ' };
            const toast = document.createElement('div');
            toast.className = 'toast ' + type;
            toast.innerHTML = `<span style="font-size:1.125rem;">${icons[type]}</span><span>${msg}</span>`;
            document.getElementById('toastContainer').appendChild(toast);
            setTimeout(() => { toast.style.opacity = '0'; setTimeout(() => toast.remove(), 300); }, 3000);
        }

        document.querySelectorAll('.modal-overlay').forEach(m => m.addEventListener('click', e => { if (e.target === m) m.classList.remove('active'); }));
    </script>
</body>
</html>
