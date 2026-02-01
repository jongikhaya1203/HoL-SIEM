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

// Detailed recommendations with fix scripts
$recommendationDetails = [
    'vuln_patching' => [
        'title' => 'Critical Vulnerability Patching',
        'description' => 'Implement immediate patching for all critical and high severity vulnerabilities detected in the network scan.',
        'steps' => [
            'Generate vulnerability report sorted by severity',
            'Identify affected systems and create maintenance window schedule',
            'Download and verify patches from official vendor sources',
            'Test patches in staging environment before production deployment',
            'Deploy patches during maintenance window with rollback plan',
            'Verify patch installation and conduct post-patch vulnerability scan'
        ],
        'auto_fix' => [
            'type' => 'script',
            'command' => 'sudo apt update && sudo apt upgrade -y --security',
            'windows_command' => 'Install-WindowsUpdate -AcceptAll -AutoReboot',
            'description' => 'Automated security patch installation'
        ],
        'effort' => 'high',
        'priority' => 'critical',
        'estimated_time' => '4-8 hours',
        'risk_if_ignored' => 'System compromise, data breach, regulatory penalties'
    ],
    'ssl_update' => [
        'title' => 'SSL/TLS Certificate and Configuration Update',
        'description' => 'Update SSL certificates and enforce minimum TLS 1.2 for all encrypted communications.',
        'steps' => [
            'Audit all SSL certificates for expiration and weak algorithms',
            'Generate new certificates using RSA 2048+ or ECC 256+ keys',
            'Configure servers to disable TLS 1.0, 1.1 and SSL 3.0',
            'Enable TLS 1.2 and TLS 1.3 with strong cipher suites',
            'Implement HSTS headers on web servers',
            'Test configuration using SSL Labs or similar tools'
        ],
        'auto_fix' => [
            'type' => 'config',
            'apache_config' => "SSLProtocol -all +TLSv1.2 +TLSv1.3\nSSLCipherSuite HIGH:!aNULL:!MD5:!3DES",
            'nginx_config' => "ssl_protocols TLSv1.2 TLSv1.3;\nssl_ciphers 'ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256';",
            'description' => 'Server TLS configuration update'
        ],
        'effort' => 'medium',
        'priority' => 'high',
        'estimated_time' => '2-4 hours',
        'risk_if_ignored' => 'Man-in-the-middle attacks, data interception'
    ],
    'firewall_config' => [
        'title' => 'Firewall Configuration Remediation',
        'description' => 'Review and correct firewall misconfigurations to ensure proper network segmentation and access control.',
        'steps' => [
            'Export current firewall rules for audit',
            'Identify overly permissive rules (any-any, 0.0.0.0/0)',
            'Document required network flows and create rule matrix',
            'Replace permissive rules with specific allow rules',
            'Implement deny-by-default policy',
            'Enable firewall logging for all denied connections',
            'Test connectivity after changes'
        ],
        'auto_fix' => [
            'type' => 'audit',
            'command' => 'iptables -L -n --line-numbers > firewall_audit.txt',
            'windows_command' => 'Get-NetFirewallRule | Export-Csv firewall_rules.csv',
            'description' => 'Export firewall rules for review'
        ],
        'effort' => 'medium',
        'priority' => 'high',
        'estimated_time' => '3-6 hours',
        'risk_if_ignored' => 'Unauthorized access, lateral movement by attackers'
    ],
    'access_review' => [
        'title' => 'Access Control Review and Enhancement',
        'description' => 'Conduct comprehensive access review and implement role-based access control improvements.',
        'steps' => [
            'Generate user access report across all systems',
            'Review access levels against job functions',
            'Remove orphaned and inactive accounts',
            'Implement least privilege principle',
            'Enable multi-factor authentication for privileged accounts',
            'Document access control policies and procedures'
        ],
        'auto_fix' => [
            'type' => 'report',
            'command' => 'getent passwd | awk -F: \'$3 >= 1000 {print $1}\'',
            'windows_command' => 'Get-LocalUser | Select Name, Enabled, LastLogon',
            'description' => 'Generate user account audit report'
        ],
        'effort' => 'medium',
        'priority' => 'medium',
        'estimated_time' => '4-8 hours',
        'risk_if_ignored' => 'Privilege escalation, insider threats'
    ],
    'default_creds' => [
        'title' => 'Default Credentials Remediation',
        'description' => 'Change all default credentials on network devices, applications, and systems.',
        'steps' => [
            'Inventory all systems with default credentials',
            'Generate strong unique passwords using password manager',
            'Update credentials on each affected system',
            'Store new credentials securely in password vault',
            'Enable account lockout policies',
            'Schedule regular credential rotation'
        ],
        'auto_fix' => [
            'type' => 'script',
            'command' => 'passwd --expire $(getent passwd | awk -F: \'$3 >= 1000 {print $1}\')',
            'description' => 'Force password change on next login'
        ],
        'effort' => 'low',
        'priority' => 'critical',
        'estimated_time' => '1-2 hours',
        'risk_if_ignored' => 'Immediate system compromise, automated attacks'
    ],
    'encryption_data' => [
        'title' => 'Data Encryption Implementation',
        'description' => 'Implement AES-256 encryption for all sensitive data at rest and in transit.',
        'steps' => [
            'Classify data and identify sensitive information',
            'Enable transparent data encryption on databases',
            'Implement disk encryption on servers and workstations',
            'Configure encrypted backup storage',
            'Implement key management procedures',
            'Test data recovery procedures'
        ],
        'auto_fix' => [
            'type' => 'config',
            'mysql_config' => 'ALTER TABLE sensitive_data ENCRYPTION=\'Y\';',
            'description' => 'Enable database table encryption'
        ],
        'effort' => 'high',
        'priority' => 'high',
        'estimated_time' => '8-16 hours',
        'risk_if_ignored' => 'Data breach, regulatory non-compliance'
    ],
    'software_update' => [
        'title' => 'Outdated Software Remediation',
        'description' => 'Update all outdated software to latest secure versions.',
        'steps' => [
            'Generate software inventory with version information',
            'Identify software with known vulnerabilities',
            'Plan update schedule with minimal business disruption',
            'Create system restore points before updates',
            'Deploy updates in phases starting with critical systems',
            'Verify functionality after updates'
        ],
        'auto_fix' => [
            'type' => 'script',
            'command' => 'apt list --upgradable 2>/dev/null | grep security',
            'windows_command' => 'Get-WmiObject -Class Win32_Product | Select Name, Version',
            'description' => 'List software requiring security updates'
        ],
        'effort' => 'medium',
        'priority' => 'high',
        'estimated_time' => '4-8 hours',
        'risk_if_ignored' => 'Known vulnerability exploitation'
    ],
    'patch_management' => [
        'title' => 'Automated Patch Management',
        'description' => 'Implement automated patch management system for continuous security updates.',
        'steps' => [
            'Deploy patch management solution (WSUS, SCCM, or third-party)',
            'Configure automatic patch download and staging',
            'Create test group for patch validation',
            'Define maintenance windows for production deployment',
            'Configure compliance reporting',
            'Set up alerts for failed patches'
        ],
        'auto_fix' => [
            'type' => 'config',
            'description' => 'Configure Windows Update Group Policy',
            'gpo_settings' => 'Configure Automatic Updates: Auto download and schedule install'
        ],
        'effort' => 'high',
        'priority' => 'high',
        'estimated_time' => '8-24 hours',
        'risk_if_ignored' => 'Accumulating vulnerabilities, compliance failures'
    ],
    'password_policy' => [
        'title' => 'Password Policy Enforcement',
        'description' => 'Implement strong password complexity requirements and rotation policies.',
        'steps' => [
            'Define password complexity requirements (12+ chars, mixed case, numbers, symbols)',
            'Configure password history (prevent last 12 passwords)',
            'Set maximum password age (90 days)',
            'Enable account lockout after 5 failed attempts',
            'Implement password blacklist for common passwords',
            'Deploy password strength meter on login forms'
        ],
        'auto_fix' => [
            'type' => 'config',
            'linux_config' => 'password requisite pam_pwquality.so minlen=12 dcredit=-1 ucredit=-1 lcredit=-1 ocredit=-1',
            'windows_command' => 'net accounts /minpwlen:12 /maxpwage:90 /minpwage:1 /uniquepw:12',
            'description' => 'Configure password policy'
        ],
        'effort' => 'low',
        'priority' => 'high',
        'estimated_time' => '1-2 hours',
        'risk_if_ignored' => 'Brute force attacks, credential stuffing'
    ],
    'unencrypted_protocols' => [
        'title' => 'Disable Unencrypted Protocols',
        'description' => 'Replace unencrypted protocols (HTTP, FTP, Telnet) with encrypted alternatives.',
        'steps' => [
            'Inventory all services using unencrypted protocols',
            'Plan migration to encrypted alternatives (HTTPS, SFTP, SSH)',
            'Generate and deploy SSL certificates',
            'Configure protocol redirects (HTTP to HTTPS)',
            'Update firewall rules to block unencrypted ports',
            'Update documentation and client configurations'
        ],
        'auto_fix' => [
            'type' => 'config',
            'apache_config' => 'Redirect permanent / https://yourdomain.com/',
            'description' => 'Configure HTTP to HTTPS redirect'
        ],
        'effort' => 'medium',
        'priority' => 'critical',
        'estimated_time' => '4-8 hours',
        'risk_if_ignored' => 'Credential theft, data interception'
    ],
    'network_monitoring' => [
        'title' => 'Network Monitoring Enhancement',
        'description' => 'Expand network monitoring coverage and enable anomaly detection.',
        'steps' => [
            'Deploy network monitoring agents on all segments',
            'Configure SNMP monitoring for network devices',
            'Enable NetFlow/sFlow collection',
            'Set up baseline traffic analysis',
            'Configure alerting thresholds',
            'Create monitoring dashboards'
        ],
        'auto_fix' => [
            'type' => 'deploy',
            'description' => 'Deploy HoL SIEM monitoring agent'
        ],
        'effort' => 'medium',
        'priority' => 'medium',
        'estimated_time' => '4-8 hours',
        'risk_if_ignored' => 'Undetected security incidents'
    ],
    'rbac_implementation' => [
        'title' => 'Role-Based Access Control Implementation',
        'description' => 'Implement comprehensive RBAC to enforce least privilege access.',
        'steps' => [
            'Define organizational roles and responsibilities',
            'Map roles to required system permissions',
            'Create role templates in identity management system',
            'Assign users to appropriate roles',
            'Remove direct permissions in favor of role assignments',
            'Implement periodic role certification reviews'
        ],
        'auto_fix' => [
            'type' => 'audit',
            'description' => 'Generate RBAC compliance report'
        ],
        'effort' => 'high',
        'priority' => 'high',
        'estimated_time' => '16-40 hours',
        'risk_if_ignored' => 'Excessive permissions, audit findings'
    ],
    'breach_notification' => [
        'title' => 'Breach Notification Procedures',
        'description' => 'Establish and test data breach notification procedures.',
        'steps' => [
            'Document incident classification criteria',
            'Define notification timelines (72 hours for GDPR)',
            'Create notification templates for authorities and individuals',
            'Establish communication chain of command',
            'Conduct tabletop breach response exercise',
            'Review and update procedures annually'
        ],
        'auto_fix' => [
            'type' => 'documentation',
            'description' => 'Generate breach response procedure template'
        ],
        'effort' => 'medium',
        'priority' => 'high',
        'estimated_time' => '8-16 hours',
        'risk_if_ignored' => 'Regulatory penalties, reputational damage'
    ],
    'asset_inventory' => [
        'title' => 'Asset Inventory Maintenance',
        'description' => 'Maintain accurate and automated asset inventory.',
        'steps' => [
            'Deploy asset discovery tools',
            'Configure automated scanning schedules',
            'Integrate with CMDB for asset tracking',
            'Establish asset classification scheme',
            'Implement change detection alerts',
            'Conduct quarterly inventory reconciliation'
        ],
        'auto_fix' => [
            'type' => 'scan',
            'command' => 'nmap -sn 192.168.0.0/24 -oX asset_discovery.xml',
            'description' => 'Run network asset discovery scan'
        ],
        'effort' => 'low',
        'priority' => 'medium',
        'estimated_time' => '2-4 hours',
        'risk_if_ignored' => 'Shadow IT, unmanaged assets'
    ],
    'logging_config' => [
        'title' => 'Centralized Logging Configuration',
        'description' => 'Ensure all critical systems send logs to centralized SIEM.',
        'steps' => [
            'Identify all log sources requiring collection',
            'Configure syslog forwarding on Linux systems',
            'Configure Windows Event forwarding',
            'Set up application-specific log collection',
            'Configure log retention policies',
            'Create correlation rules for security events'
        ],
        'auto_fix' => [
            'type' => 'config',
            'syslog_config' => '*.* @@siem.company.com:514',
            'description' => 'Configure syslog forwarding to SIEM'
        ],
        'effort' => 'medium',
        'priority' => 'high',
        'estimated_time' => '4-8 hours',
        'risk_if_ignored' => 'Undetected security events, forensic gaps'
    ],
    'pia_assessment' => [
        'title' => 'Privacy Impact Assessment',
        'description' => 'Conduct Privacy Impact Assessment for systems processing personal data.',
        'steps' => [
            'Identify systems processing personal data',
            'Document data flows and storage locations',
            'Assess privacy risks and impacts',
            'Identify mitigation measures',
            'Document residual risks and acceptance',
            'Obtain DPO review and sign-off'
        ],
        'auto_fix' => [
            'type' => 'documentation',
            'description' => 'Generate PIA questionnaire template'
        ],
        'effort' => 'high',
        'priority' => 'medium',
        'estimated_time' => '16-40 hours',
        'risk_if_ignored' => 'GDPR non-compliance, regulatory action'
    ]
];

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
            ['id' => 'iso-001', 'control' => 'A.8.8', 'title' => 'Management of technical vulnerabilities', 'status' => 'fail', 'severity' => 'high', 'finding' => $networkScanData['critical_vulns'] . ' critical vulnerabilities detected', 'recommendation' => 'Implement immediate patching for critical vulnerabilities', 'rec_key' => 'vuln_patching'],
            ['id' => 'iso-002', 'control' => 'A.8.24', 'title' => 'Use of cryptography', 'status' => 'fail', 'severity' => 'medium', 'finding' => $networkScanData['ssl_issues'] . ' SSL/TLS configuration issues', 'recommendation' => 'Update SSL certificates and enforce TLS 1.2+', 'rec_key' => 'ssl_update'],
            ['id' => 'iso-003', 'control' => 'A.8.9', 'title' => 'Configuration management', 'status' => 'fail', 'severity' => 'medium', 'finding' => $networkScanData['firewall_misconfigs'] . ' firewall misconfigurations', 'recommendation' => 'Review and correct firewall rules', 'rec_key' => 'firewall_config'],
            ['id' => 'iso-004', 'control' => 'A.5.15', 'title' => 'Access control', 'status' => 'pass', 'severity' => 'info', 'finding' => 'Access controls properly implemented', 'recommendation' => 'Continue regular access reviews', 'rec_key' => 'access_review'],
            ['id' => 'iso-005', 'control' => 'A.8.15', 'title' => 'Logging', 'status' => 'pass', 'severity' => 'info', 'finding' => 'Centralized logging enabled', 'recommendation' => 'Maintain current logging configuration', 'rec_key' => 'logging_config']
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
            ['id' => 'nist-001', 'control' => 'PR.DS-1', 'title' => 'Data-at-rest protection', 'status' => 'fail', 'severity' => 'high', 'finding' => 'Unencrypted data stores detected', 'recommendation' => 'Implement AES-256 encryption for all data at rest', 'rec_key' => 'encryption_data'],
            ['id' => 'nist-002', 'control' => 'PR.PS-1', 'title' => 'Baseline configurations', 'status' => 'fail', 'severity' => 'medium', 'finding' => $networkScanData['outdated_software'] . ' systems with outdated software', 'recommendation' => 'Update all systems to latest secure versions', 'rec_key' => 'software_update'],
            ['id' => 'nist-003', 'control' => 'DE.CM-1', 'title' => 'Network monitoring', 'status' => 'pass', 'severity' => 'info', 'finding' => 'Network monitoring active on all segments', 'recommendation' => 'Continue current monitoring practices', 'rec_key' => 'network_monitoring'],
            ['id' => 'nist-004', 'control' => 'ID.AM-1', 'title' => 'Asset inventory', 'status' => 'pass', 'severity' => 'info', 'finding' => $networkScanData['total_hosts'] . ' hosts inventoried', 'recommendation' => 'Maintain asset inventory accuracy', 'rec_key' => 'asset_inventory'],
            ['id' => 'nist-005', 'control' => 'PR.AC-1', 'title' => 'Identity management', 'status' => 'fail', 'severity' => 'high', 'finding' => $networkScanData['default_credentials'] . ' systems with default credentials', 'recommendation' => 'Change all default credentials immediately', 'rec_key' => 'default_creds']
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
            ['id' => 'pci-001', 'control' => '6.3.3', 'title' => 'Vulnerability scanning', 'status' => 'fail', 'severity' => 'high', 'finding' => $networkScanData['critical_vulns'] + $networkScanData['high_vulns'] . ' high/critical vulnerabilities unresolved', 'recommendation' => 'Remediate all high and critical vulnerabilities within 30 days', 'rec_key' => 'vuln_patching'],
            ['id' => 'pci-002', 'control' => '2.2.1', 'title' => 'System configurations', 'status' => 'fail', 'severity' => 'medium', 'finding' => $networkScanData['default_credentials'] . ' systems with vendor defaults', 'recommendation' => 'Remove all vendor-supplied default accounts', 'rec_key' => 'default_creds'],
            ['id' => 'pci-003', 'control' => '4.2.1', 'title' => 'Strong cryptography', 'status' => 'fail', 'severity' => 'high', 'finding' => $networkScanData['unencrypted_protocols'] . ' unencrypted transmission channels', 'recommendation' => 'Implement TLS 1.2+ for all transmissions', 'rec_key' => 'unencrypted_protocols'],
            ['id' => 'pci-004', 'control' => '11.3.1', 'title' => 'Penetration testing', 'status' => 'pass', 'severity' => 'info', 'finding' => 'Annual penetration test completed', 'recommendation' => 'Schedule next quarterly internal scan', 'rec_key' => 'vuln_patching'],
            ['id' => 'pci-005', 'control' => '10.2.1', 'title' => 'Audit logs', 'status' => 'pass', 'severity' => 'info', 'finding' => 'Audit logging enabled on all systems', 'recommendation' => 'Continue log retention for 12 months', 'rec_key' => 'logging_config']
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
            ['id' => 'soc2-001', 'control' => 'CC6.1', 'title' => 'Logical access security', 'status' => 'fail', 'severity' => 'high', 'finding' => $networkScanData['weak_passwords'] . ' accounts with weak passwords', 'recommendation' => 'Enforce password complexity policy', 'rec_key' => 'password_policy'],
            ['id' => 'soc2-002', 'control' => 'CC7.2', 'title' => 'Vulnerability management', 'status' => 'fail', 'severity' => 'high', 'finding' => $networkScanData['missing_patches'] . ' missing security patches', 'recommendation' => 'Implement automated patch management', 'rec_key' => 'patch_management'],
            ['id' => 'soc2-003', 'control' => 'CC6.6', 'title' => 'System boundaries', 'status' => 'pass', 'severity' => 'info', 'finding' => 'Network segmentation properly configured', 'recommendation' => 'Continue regular boundary reviews', 'rec_key' => 'firewall_config'],
            ['id' => 'soc2-004', 'control' => 'CC7.1', 'title' => 'Infrastructure monitoring', 'status' => 'pass', 'severity' => 'info', 'finding' => 'Continuous monitoring active', 'recommendation' => 'Maintain current monitoring coverage', 'rec_key' => 'network_monitoring']
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
            ['id' => 'gdpr-001', 'control' => 'Art. 32', 'title' => 'Security of processing', 'status' => 'fail', 'severity' => 'high', 'finding' => 'Encryption gaps detected in ' . $networkScanData['ssl_issues'] . ' systems', 'recommendation' => 'Implement end-to-end encryption for all personal data', 'rec_key' => 'encryption_data'],
            ['id' => 'gdpr-002', 'control' => 'Art. 33', 'title' => 'Breach notification', 'status' => 'pass', 'severity' => 'info', 'finding' => 'Breach notification procedures documented', 'recommendation' => 'Conduct annual breach response drill', 'rec_key' => 'breach_notification'],
            ['id' => 'gdpr-003', 'control' => 'Art. 25', 'title' => 'Data protection by design', 'status' => 'fail', 'severity' => 'medium', 'finding' => 'Privacy impact assessment overdue', 'recommendation' => 'Complete PIA for new systems', 'rec_key' => 'pia_assessment'],
            ['id' => 'gdpr-004', 'control' => 'Art. 17', 'title' => 'Right to erasure', 'status' => 'pass', 'severity' => 'info', 'finding' => 'Data deletion procedures implemented', 'recommendation' => 'Automate data retention enforcement', 'rec_key' => 'encryption_data']
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
            ['id' => 'hipaa-001', 'control' => '164.312(a)(1)', 'title' => 'Access control', 'status' => 'fail', 'severity' => 'high', 'finding' => $networkScanData['access_control_issues'] . ' access control deficiencies', 'recommendation' => 'Implement role-based access control', 'rec_key' => 'rbac_implementation'],
            ['id' => 'hipaa-002', 'control' => '164.312(e)(1)', 'title' => 'Transmission security', 'status' => 'fail', 'severity' => 'critical', 'finding' => $networkScanData['unencrypted_protocols'] . ' unencrypted ePHI transmissions', 'recommendation' => 'Encrypt all ePHI in transit immediately', 'rec_key' => 'unencrypted_protocols'],
            ['id' => 'hipaa-003', 'control' => '164.312(b)', 'title' => 'Audit controls', 'status' => 'pass', 'severity' => 'info', 'finding' => 'Audit logging active for ePHI access', 'recommendation' => 'Continue audit log reviews', 'rec_key' => 'logging_config'],
            ['id' => 'hipaa-004', 'control' => '164.308(a)(1)', 'title' => 'Security management', 'status' => 'pass', 'severity' => 'info', 'finding' => 'Risk analysis completed', 'recommendation' => 'Update risk analysis annually', 'rec_key' => 'pia_assessment']
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
            ['id' => 'cis-001', 'control' => '7.1', 'title' => 'Vulnerability scanning', 'status' => 'fail', 'severity' => 'high', 'finding' => $networkScanData['total_vulnerabilities'] . ' vulnerabilities detected across network', 'recommendation' => 'Establish weekly vulnerability scanning', 'rec_key' => 'vuln_patching'],
            ['id' => 'cis-002', 'control' => '4.1', 'title' => 'Secure configuration', 'status' => 'fail', 'severity' => 'medium', 'finding' => $networkScanData['firewall_misconfigs'] . ' configuration deviations', 'recommendation' => 'Implement configuration management baseline', 'rec_key' => 'firewall_config'],
            ['id' => 'cis-003', 'control' => '1.1', 'title' => 'Asset inventory', 'status' => 'pass', 'severity' => 'info', 'finding' => $networkScanData['total_hosts'] . ' assets tracked', 'recommendation' => 'Continue automated discovery', 'rec_key' => 'asset_inventory'],
            ['id' => 'cis-004', 'control' => '8.2', 'title' => 'Centralized logging', 'status' => 'pass', 'severity' => 'info', 'finding' => 'SIEM collecting logs from all critical systems', 'recommendation' => 'Expand log sources coverage', 'rec_key' => 'logging_config']
        ]
    ]
];

// JSON encode recommendations for JavaScript
$recommendationsJson = json_encode($recommendationDetails);

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

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }

        .btn-action {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 11px;
            font-weight: 600;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .btn-action:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .btn-view {
            background: #e0e7ff;
            color: #4338ca;
        }

        .btn-view:hover {
            background: #c7d2fe;
        }

        .btn-accept {
            background: #fef3c7;
            color: #d97706;
        }

        .btn-accept:hover {
            background: #fde68a;
        }

        .btn-accept.accepted {
            background: #d1fae5;
            color: #059669;
        }

        .btn-fix {
            background: #fee2e2;
            color: #dc2626;
        }

        .btn-fix:hover:not(:disabled) {
            background: #fecaca;
        }

        .btn-fix.applied {
            background: #d1fae5;
            color: #059669;
        }

        .compliant-badge {
            color: var(--success);
            font-size: 12px;
            font-weight: 600;
        }

        .btn-sm {
            padding: 8px 16px;
            font-size: 12px;
        }

        /* Modal Styles */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            backdrop-filter: blur(4px);
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal {
            background: white;
            border-radius: 16px;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow: hidden;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
            animation: modalSlide 0.3s ease;
        }

        @keyframes modalSlide {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-header {
            padding: 20px 25px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, var(--primary) 0%, #764ba2 100%);
            color: white;
        }

        .modal-header h2 {
            font-size: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .modal-close {
            background: rgba(255,255,255,0.2);
            border: none;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 20px;
            color: white;
            transition: all 0.2s;
        }

        .modal-close:hover {
            background: rgba(255,255,255,0.3);
        }

        .modal-body {
            padding: 25px;
            overflow-y: auto;
            max-height: calc(90vh - 180px);
        }

        .modal-footer {
            padding: 15px 25px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            background: #f8fafc;
        }

        .rec-section {
            margin-bottom: 25px;
        }

        .rec-section h3 {
            font-size: 14px;
            color: var(--gray);
            text-transform: uppercase;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .rec-description {
            background: var(--light);
            padding: 15px;
            border-radius: 10px;
            font-size: 14px;
            line-height: 1.6;
        }

        .rec-steps {
            list-style: none;
            counter-reset: step-counter;
        }

        .rec-steps li {
            counter-increment: step-counter;
            padding: 12px 15px 12px 50px;
            background: var(--light);
            border-radius: 8px;
            margin-bottom: 8px;
            position: relative;
            font-size: 14px;
        }

        .rec-steps li::before {
            content: counter(step-counter);
            position: absolute;
            left: 15px;
            width: 24px;
            height: 24px;
            background: var(--primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
        }

        .rec-code {
            background: #1e293b;
            color: #e2e8f0;
            padding: 15px;
            border-radius: 10px;
            font-family: 'Monaco', 'Consolas', monospace;
            font-size: 13px;
            overflow-x: auto;
        }

        .rec-code-label {
            color: #94a3b8;
            font-size: 11px;
            margin-bottom: 8px;
            display: block;
        }

        .rec-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
        }

        .rec-meta-item {
            background: var(--light);
            padding: 15px;
            border-radius: 10px;
            text-align: center;
        }

        .rec-meta-label {
            font-size: 11px;
            color: var(--gray);
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .rec-meta-value {
            font-size: 16px;
            font-weight: 600;
            color: var(--dark);
        }

        .rec-meta-value.critical { color: #7f1d1d; }
        .rec-meta-value.high { color: var(--danger); }
        .rec-meta-value.medium { color: var(--warning); }
        .rec-meta-value.low { color: var(--info); }

        .risk-warning {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 10px;
            padding: 15px;
            color: #991b1b;
            font-size: 13px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .risk-warning-icon {
            font-size: 20px;
        }

        /* Toast Notifications */
        .toast-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1100;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .toast {
            background: white;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            display: flex;
            align-items: center;
            gap: 12px;
            animation: toastSlide 0.3s ease;
            min-width: 300px;
        }

        @keyframes toastSlide {
            from {
                opacity: 0;
                transform: translateX(100px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .toast.success {
            border-left: 4px solid var(--success);
        }

        .toast.warning {
            border-left: 4px solid var(--warning);
        }

        .toast.error {
            border-left: 4px solid var(--danger);
        }

        .toast-icon {
            font-size: 24px;
        }

        .toast-content {
            flex: 1;
        }

        .toast-title {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 3px;
        }

        .toast-message {
            font-size: 13px;
            color: var(--gray);
        }

        .progress-indicator {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 30px 50px;
            border-radius: 16px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
            z-index: 1200;
            text-align: center;
        }

        .progress-indicator.active {
            display: block;
        }

        .progress-spinner {
            width: 50px;
            height: 50px;
            border: 4px solid #e2e8f0;
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .fix-applied-row {
            background: #f0fdf4 !important;
        }

        .fix-applied-row .status-badge.fail {
            background: #d1fae5;
            color: #059669;
        }

        /* Print Styles */
        @media print {
            body { background: white; padding: 0; }
            .header-actions, .framework-tabs, .btn, .action-buttons, .modal-overlay, .toast-container { display: none; }
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
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <span style="color: var(--gray); font-size: 13px;"><?= count($activeFramework['findings']) ?> findings</span>
                            <button class="btn btn-success btn-sm" onclick="applyAllRecommendations()" style="padding: 8px 16px; font-size: 12px;">
                                ✨ Apply All Accepted
                            </button>
                        </div>
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
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($activeFramework['findings'] as $finding): ?>
                                <tr id="finding-row-<?= $finding['id'] ?>" data-finding-id="<?= $finding['id'] ?>" data-rec-key="<?= $finding['rec_key'] ?>">
                                    <td><span class="control-id"><?= $finding['control'] ?></span></td>
                                    <td style="font-weight: 500;"><?= $finding['title'] ?></td>
                                    <td>
                                        <span class="status-badge <?= $finding['status'] ?>" id="status-<?= $finding['id'] ?>">
                                            <?= ucfirst($finding['status']) ?>
                                        </span>
                                    </td>
                                    <td><span class="severity-badge <?= $finding['severity'] ?>"><?= ucfirst($finding['severity']) ?></span></td>
                                    <td style="color: var(--gray); font-size: 12px;"><?= $finding['finding'] ?></td>
                                    <td>
                                        <div class="action-buttons" id="actions-<?= $finding['id'] ?>">
                                            <button class="btn-action btn-view" onclick="viewRecommendation('<?= $finding['id'] ?>', '<?= $finding['rec_key'] ?>')" title="View Recommendation">
                                                💡 View
                                            </button>
                                            <?php if ($finding['status'] === 'fail'): ?>
                                            <button class="btn-action btn-accept" onclick="acceptRecommendation('<?= $finding['id'] ?>')" title="Accept Recommendation" id="accept-btn-<?= $finding['id'] ?>">
                                                ✓ Accept
                                            </button>
                                            <button class="btn-action btn-fix" onclick="applyFix('<?= $finding['id'] ?>', '<?= $finding['rec_key'] ?>')" title="Fix & Apply" id="fix-btn-<?= $finding['id'] ?>" disabled>
                                                🔧 Fix & Apply
                                            </button>
                                            <?php else: ?>
                                            <span class="compliant-badge">✅ Compliant</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
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

    <!-- Recommendation Modal -->
    <div class="modal-overlay" id="recModal">
        <div class="modal">
            <div class="modal-header">
                <h2 id="modalTitle">💡 Recommendation Details</h2>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body" id="modalBody">
                <!-- Content will be dynamically populated -->
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" onclick="closeModal()">Close</button>
                <button class="btn btn-primary" id="modalAcceptBtn" onclick="acceptFromModal()">✓ Accept Recommendation</button>
                <button class="btn btn-success" id="modalApplyBtn" onclick="applyFromModal()" disabled>🔧 Fix & Apply</button>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>

    <!-- Progress Indicator -->
    <div class="progress-indicator" id="progressIndicator">
        <div class="progress-spinner"></div>
        <div class="progress-text">Applying fix...</div>
    </div>

    <script>
        // Recommendations data from PHP
        const recommendations = <?= $recommendationsJson ?>;

        // Track accepted and applied recommendations
        let acceptedRecs = JSON.parse(localStorage.getItem('acceptedRecs_<?= $activeTab ?>') || '{}');
        let appliedRecs = JSON.parse(localStorage.getItem('appliedRecs_<?= $activeTab ?>') || '{}');

        // Current modal context
        let currentFindingId = null;
        let currentRecKey = null;

        // Initialize UI state on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Restore accepted/applied states
            Object.keys(acceptedRecs).forEach(findingId => {
                updateAcceptedUI(findingId);
            });
            Object.keys(appliedRecs).forEach(findingId => {
                updateAppliedUI(findingId);
            });
        });

        // View Recommendation
        function viewRecommendation(findingId, recKey) {
            currentFindingId = findingId;
            currentRecKey = recKey;

            const rec = recommendations[recKey];
            if (!rec) {
                showToast('error', 'Error', 'Recommendation details not found');
                return;
            }

            const modalBody = document.getElementById('modalBody');
            const modalTitle = document.getElementById('modalTitle');

            modalTitle.innerHTML = `💡 ${rec.title}`;

            let stepsHtml = rec.steps.map(step => `<li>${step}</li>`).join('');

            let autoFixHtml = '';
            if (rec.auto_fix) {
                autoFixHtml = `
                    <div class="rec-section">
                        <h3>🔧 Auto-Fix Script</h3>
                        <div class="rec-code">
                            <span class="rec-code-label">${rec.auto_fix.description}</span>
                            ${rec.auto_fix.command ? `<div>Linux/macOS: <code>${rec.auto_fix.command}</code></div>` : ''}
                            ${rec.auto_fix.windows_command ? `<div style="margin-top: 8px;">Windows: <code>${rec.auto_fix.windows_command}</code></div>` : ''}
                            ${rec.auto_fix.apache_config ? `<div style="margin-top: 8px;">Apache Config:<br><code>${rec.auto_fix.apache_config.replace(/\n/g, '<br>')}</code></div>` : ''}
                            ${rec.auto_fix.nginx_config ? `<div style="margin-top: 8px;">Nginx Config:<br><code>${rec.auto_fix.nginx_config.replace(/\n/g, '<br>')}</code></div>` : ''}
                            ${rec.auto_fix.mysql_config ? `<div style="margin-top: 8px;">MySQL: <code>${rec.auto_fix.mysql_config}</code></div>` : ''}
                            ${rec.auto_fix.linux_config ? `<div style="margin-top: 8px;">PAM Config: <code>${rec.auto_fix.linux_config}</code></div>` : ''}
                            ${rec.auto_fix.syslog_config ? `<div style="margin-top: 8px;">Syslog: <code>${rec.auto_fix.syslog_config}</code></div>` : ''}
                        </div>
                    </div>
                `;
            }

            modalBody.innerHTML = `
                <div class="rec-section">
                    <h3>📋 Description</h3>
                    <div class="rec-description">${rec.description}</div>
                </div>

                <div class="rec-section">
                    <h3>📝 Remediation Steps</h3>
                    <ol class="rec-steps">${stepsHtml}</ol>
                </div>

                ${autoFixHtml}

                <div class="rec-section">
                    <h3>📊 Metadata</h3>
                    <div class="rec-meta">
                        <div class="rec-meta-item">
                            <div class="rec-meta-label">Priority</div>
                            <div class="rec-meta-value ${rec.priority}">${rec.priority.toUpperCase()}</div>
                        </div>
                        <div class="rec-meta-item">
                            <div class="rec-meta-label">Effort Level</div>
                            <div class="rec-meta-value">${rec.effort.toUpperCase()}</div>
                        </div>
                        <div class="rec-meta-item">
                            <div class="rec-meta-label">Estimated Time</div>
                            <div class="rec-meta-value">${rec.estimated_time}</div>
                        </div>
                    </div>
                </div>

                <div class="rec-section">
                    <h3>⚠️ Risk if Ignored</h3>
                    <div class="risk-warning">
                        <span class="risk-warning-icon">⚠️</span>
                        <div>${rec.risk_if_ignored}</div>
                    </div>
                </div>
            `;

            // Update modal buttons state
            const acceptBtn = document.getElementById('modalAcceptBtn');
            const applyBtn = document.getElementById('modalApplyBtn');

            if (appliedRecs[findingId]) {
                acceptBtn.disabled = true;
                acceptBtn.textContent = '✓ Applied';
                applyBtn.disabled = true;
                applyBtn.textContent = '✅ Fix Applied';
            } else if (acceptedRecs[findingId]) {
                acceptBtn.disabled = true;
                acceptBtn.textContent = '✓ Accepted';
                applyBtn.disabled = false;
            } else {
                acceptBtn.disabled = false;
                acceptBtn.textContent = '✓ Accept Recommendation';
                applyBtn.disabled = true;
            }

            document.getElementById('recModal').classList.add('active');
        }

        // Close Modal
        function closeModal() {
            document.getElementById('recModal').classList.remove('active');
            currentFindingId = null;
            currentRecKey = null;
        }

        // Accept Recommendation
        function acceptRecommendation(findingId) {
            acceptedRecs[findingId] = {
                acceptedAt: new Date().toISOString(),
                acceptedBy: 'Current User'
            };
            localStorage.setItem('acceptedRecs_<?= $activeTab ?>', JSON.stringify(acceptedRecs));

            updateAcceptedUI(findingId);
            showToast('success', 'Recommendation Accepted', 'The recommendation has been accepted and queued for implementation.');
        }

        // Update UI after acceptance
        function updateAcceptedUI(findingId) {
            const acceptBtn = document.getElementById(`accept-btn-${findingId}`);
            const fixBtn = document.getElementById(`fix-btn-${findingId}`);

            if (acceptBtn) {
                acceptBtn.classList.add('accepted');
                acceptBtn.textContent = '✓ Accepted';
                acceptBtn.disabled = true;
            }

            if (fixBtn && !appliedRecs[findingId]) {
                fixBtn.disabled = false;
            }
        }

        // Accept from modal
        function acceptFromModal() {
            if (currentFindingId) {
                acceptRecommendation(currentFindingId);

                // Update modal buttons
                document.getElementById('modalAcceptBtn').disabled = true;
                document.getElementById('modalAcceptBtn').textContent = '✓ Accepted';
                document.getElementById('modalApplyBtn').disabled = false;
            }
        }

        // Apply Fix
        function applyFix(findingId, recKey) {
            if (!acceptedRecs[findingId]) {
                showToast('warning', 'Acceptance Required', 'Please accept the recommendation before applying the fix.');
                return;
            }

            const rec = recommendations[recKey];

            // Show progress indicator
            document.getElementById('progressIndicator').classList.add('active');

            // Simulate fix application (in production, this would make an API call)
            setTimeout(() => {
                document.getElementById('progressIndicator').classList.remove('active');

                appliedRecs[findingId] = {
                    appliedAt: new Date().toISOString(),
                    appliedBy: 'Current User',
                    fixType: rec.auto_fix?.type || 'manual'
                };
                localStorage.setItem('appliedRecs_<?= $activeTab ?>', JSON.stringify(appliedRecs));

                updateAppliedUI(findingId);
                showToast('success', 'Fix Applied Successfully', `${rec.title} has been remediated. Status updated to compliant.`);
            }, 2000);
        }

        // Update UI after fix application
        function updateAppliedUI(findingId) {
            const row = document.getElementById(`finding-row-${findingId}`);
            const fixBtn = document.getElementById(`fix-btn-${findingId}`);
            const statusBadge = document.getElementById(`status-${findingId}`);
            const actionsDiv = document.getElementById(`actions-${findingId}`);

            if (row) {
                row.classList.add('fix-applied-row');
            }

            if (fixBtn) {
                fixBtn.classList.add('applied');
                fixBtn.textContent = '✅ Applied';
                fixBtn.disabled = true;
            }

            if (statusBadge) {
                statusBadge.textContent = 'Fixed';
                statusBadge.classList.remove('fail');
                statusBadge.classList.add('pass');
            }
        }

        // Apply from modal
        function applyFromModal() {
            if (currentFindingId && currentRecKey) {
                applyFix(currentFindingId, currentRecKey);
                closeModal();
            }
        }

        // Apply All Accepted Recommendations
        function applyAllRecommendations() {
            const accepted = Object.keys(acceptedRecs).filter(id => !appliedRecs[id]);

            if (accepted.length === 0) {
                showToast('warning', 'No Pending Fixes', 'No accepted recommendations pending application.');
                return;
            }

            document.getElementById('progressIndicator').classList.add('active');
            document.querySelector('.progress-text').textContent = `Applying ${accepted.length} fixes...`;

            // Simulate batch application
            let completed = 0;
            accepted.forEach((findingId, index) => {
                setTimeout(() => {
                    const row = document.getElementById(`finding-row-${findingId}`);
                    const recKey = row?.dataset.recKey;

                    appliedRecs[findingId] = {
                        appliedAt: new Date().toISOString(),
                        appliedBy: 'Current User',
                        fixType: 'batch'
                    };

                    updateAppliedUI(findingId);
                    completed++;

                    if (completed === accepted.length) {
                        localStorage.setItem('appliedRecs_<?= $activeTab ?>', JSON.stringify(appliedRecs));
                        document.getElementById('progressIndicator').classList.remove('active');
                        showToast('success', 'All Fixes Applied', `Successfully applied ${accepted.length} remediation(s).`);
                    }
                }, (index + 1) * 500);
            });
        }

        // Show Toast Notification
        function showToast(type, title, message) {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;

            const icons = {
                success: '✅',
                warning: '⚠️',
                error: '❌'
            };

            toast.innerHTML = `
                <span class="toast-icon">${icons[type]}</span>
                <div class="toast-content">
                    <div class="toast-title">${title}</div>
                    <div class="toast-message">${message}</div>
                </div>
            `;

            container.appendChild(toast);

            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(100px)';
                setTimeout(() => toast.remove(), 300);
            }, 4000);
        }

        // Close modal on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });

        // Close modal on overlay click
        document.getElementById('recModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

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

        // Reset demo data (for testing)
        function resetDemoData() {
            localStorage.removeItem('acceptedRecs_<?= $activeTab ?>');
            localStorage.removeItem('appliedRecs_<?= $activeTab ?>');
            location.reload();
        }
    </script>
</body>
</html>
