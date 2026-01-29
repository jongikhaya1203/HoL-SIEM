<?php
/**
 * Enhanced Email DLP System
 * Full-featured Data Loss Prevention with Email Monitoring Agent
 */

require_once __DIR__ . '/classes/Database.php';

// Initialize database
try {
    $db = Database::getInstance();
} catch (Exception $e) {
    $db = null;
}

// Sample email data for demonstration
$monitoredEmails = [
    ['id' => 'EM-001', 'from' => 'john.smith@company.com', 'to' => 'external@competitor.com', 'subject' => 'Q4 Financial Report - Confidential', 'status' => 'blocked', 'risk' => 95, 'category' => 'Financial Data', 'time' => '2025-01-26 14:30:00', 'attachments' => 2, 'size' => '2.4 MB'],
    ['id' => 'EM-002', 'from' => 'sarah.jones@company.com', 'to' => 'personal@gmail.com', 'subject' => 'Customer Database Export', 'status' => 'quarantined', 'risk' => 88, 'category' => 'PII Data', 'time' => '2025-01-26 14:25:00', 'attachments' => 1, 'size' => '5.8 MB'],
    ['id' => 'EM-003', 'from' => 'dev.team@company.com', 'to' => 'contractor@external.com', 'subject' => 'Source Code Review', 'status' => 'flagged', 'risk' => 72, 'category' => 'Source Code', 'time' => '2025-01-26 14:20:00', 'attachments' => 5, 'size' => '12.3 MB'],
    ['id' => 'EM-004', 'from' => 'hr@company.com', 'to' => 'recruiter@agency.com', 'subject' => 'Employee Salary Information', 'status' => 'blocked', 'risk' => 91, 'category' => 'HR Data', 'time' => '2025-01-26 14:15:00', 'attachments' => 1, 'size' => '156 KB'],
    ['id' => 'EM-005', 'from' => 'sales@company.com', 'to' => 'partner@vendor.com', 'subject' => 'Price List 2025', 'status' => 'allowed', 'risk' => 35, 'category' => 'Business Data', 'time' => '2025-01-26 14:10:00', 'attachments' => 1, 'size' => '89 KB'],
    ['id' => 'EM-006', 'from' => 'ceo@company.com', 'to' => 'board@external.com', 'subject' => 'M&A Strategy Document', 'status' => 'quarantined', 'risk' => 98, 'category' => 'Strategic Data', 'time' => '2025-01-26 14:05:00', 'attachments' => 3, 'size' => '4.2 MB'],
    ['id' => 'EM-007', 'from' => 'research@company.com', 'to' => 'university@edu.org', 'subject' => 'Patent Application Draft', 'status' => 'flagged', 'risk' => 85, 'category' => 'IP Data', 'time' => '2025-01-26 14:00:00', 'attachments' => 2, 'size' => '8.7 MB'],
    ['id' => 'EM-008', 'from' => 'support@company.com', 'to' => 'customer@client.com', 'subject' => 'Account Details', 'status' => 'allowed', 'risk' => 42, 'category' => 'Customer Data', 'time' => '2025-01-26 13:55:00', 'attachments' => 0, 'size' => '12 KB'],
];

// Agent tracking data
$agentTracking = [
    ['agent_id' => 'AGT-001', 'email_id' => 'EM-001', 'action' => 'Intercepted', 'timestamp' => '2025-01-26 14:30:05', 'details' => 'Blocked outbound transfer to competitor domain'],
    ['agent_id' => 'AGT-001', 'email_id' => 'EM-001', 'action' => 'Pattern Match', 'timestamp' => '2025-01-26 14:30:02', 'details' => 'Detected financial keywords: revenue, profit, forecast'],
    ['agent_id' => 'AGT-002', 'email_id' => 'EM-002', 'action' => 'Quarantined', 'timestamp' => '2025-01-26 14:25:08', 'details' => 'PII detected: SSN patterns, email addresses, phone numbers'],
    ['agent_id' => 'AGT-001', 'email_id' => 'EM-003', 'action' => 'Flagged', 'timestamp' => '2025-01-26 14:20:03', 'details' => 'Source code files detected in attachments'],
    ['agent_id' => 'AGT-003', 'email_id' => 'EM-006', 'action' => 'Executive Alert', 'timestamp' => '2025-01-26 14:05:01', 'details' => 'C-level communication with sensitive content'],
];

// Detection rules
$detectionRules = [
    ['id' => 1, 'name' => 'Credit Card Numbers', 'pattern' => '/\b(?:\d{4}[-\s]?){3}\d{4}\b/', 'category' => 'PII', 'severity' => 'critical', 'status' => 'active', 'matches' => 156],
    ['id' => 2, 'name' => 'Social Security Numbers', 'pattern' => '/\b\d{3}-\d{2}-\d{4}\b/', 'category' => 'PII', 'severity' => 'critical', 'status' => 'active', 'matches' => 89],
    ['id' => 3, 'name' => 'Financial Keywords', 'pattern' => '/\b(revenue|profit|forecast|budget|confidential)\b/i', 'category' => 'Financial', 'severity' => 'high', 'status' => 'active', 'matches' => 342],
    ['id' => 4, 'name' => 'Source Code Files', 'pattern' => '/\.(py|js|java|cpp|cs|php|rb)$/i', 'category' => 'IP', 'severity' => 'high', 'status' => 'active', 'matches' => 67],
    ['id' => 5, 'name' => 'Database Exports', 'pattern' => '/\.(sql|csv|xlsx|mdb)$/i', 'category' => 'Data', 'severity' => 'high', 'status' => 'active', 'matches' => 124],
    ['id' => 6, 'name' => 'API Keys', 'pattern' => '/\b(api[_-]?key|secret[_-]?key|access[_-]?token)\s*[:=]\s*["\']?[\w-]+/i', 'category' => 'Security', 'severity' => 'critical', 'status' => 'active', 'matches' => 23],
    ['id' => 7, 'name' => 'Medical Records (HIPAA)', 'pattern' => '/\b(diagnosis|prescription|patient|medical record)\b/i', 'category' => 'Healthcare', 'severity' => 'critical', 'status' => 'active', 'matches' => 45],
    ['id' => 8, 'name' => 'Personal Email Domains', 'pattern' => '/@(gmail|yahoo|hotmail|outlook|aol)\.(com|net|org)/i', 'category' => 'Policy', 'severity' => 'medium', 'status' => 'active', 'matches' => 567],
];

// Competitor domains watchlist
$competitorDomains = [
    'competitor.com' => ['name' => 'Acme Corp', 'threat_level' => 'critical', 'industry' => 'Direct Competitor'],
    'rival-tech.com' => ['name' => 'Rival Technologies', 'threat_level' => 'critical', 'industry' => 'Direct Competitor'],
    'marketcomp.io' => ['name' => 'Market Comp Inc', 'threat_level' => 'high', 'industry' => 'Market Competitor'],
    'techrivals.net' => ['name' => 'Tech Rivals LLC', 'threat_level' => 'high', 'industry' => 'Technology'],
    'industryspy.com' => ['name' => 'Industry Analytics', 'threat_level' => 'critical', 'industry' => 'Intelligence'],
    'competitorhq.com' => ['name' => 'Competitor HQ', 'threat_level' => 'medium', 'industry' => 'Consulting'],
];

// Competitor leak tracking - emails sent to competitors with full chain
$competitorLeaks = [
    [
        'leak_id' => 'CLK-001',
        'status' => 'confirmed_leak',
        'severity' => 'critical',
        'competitor' => 'Acme Corp',
        'competitor_domain' => 'competitor.com',
        'data_type' => 'Financial Data',
        'original_sender' => 'john.smith@company.com',
        'original_subject' => 'Q4 Financial Report - Confidential',
        'detected_at' => '2025-01-26 14:30:05',
        'chain' => [
            ['hop' => 1, 'from' => 'john.smith@company.com', 'to' => 'finance.team@company.com', 'time' => '2025-01-26 09:15:00', 'action' => 'Sent', 'location' => 'Internal', 'ip' => '192.168.1.45', 'delivered' => true],
            ['hop' => 2, 'from' => 'mary.wilson@company.com', 'to' => 'external.consultant@advisory.com', 'time' => '2025-01-26 11:30:00', 'action' => 'Forwarded', 'location' => 'External', 'ip' => '192.168.1.67', 'delivered' => true],
            ['hop' => 3, 'from' => 'external.consultant@advisory.com', 'to' => 'contact@competitor.com', 'time' => '2025-01-26 14:28:00', 'action' => 'Forwarded', 'location' => 'Competitor', 'ip' => '45.67.89.123', 'delivered' => true],
            ['hop' => 4, 'from' => 'contact@competitor.com', 'to' => 'strategy@competitor.com', 'time' => '2025-01-26 14:45:00', 'action' => 'Internal Forward', 'location' => 'Competitor Internal', 'ip' => '45.67.89.150', 'delivered' => true],
        ],
        'attachments' => ['Q4_Financial_Report.xlsx', 'Revenue_Forecast_2025.pdf'],
        'data_exposed' => ['Revenue figures', 'Profit margins', 'Growth projections', 'Market strategy'],
        'risk_score' => 98
    ],
    [
        'leak_id' => 'CLK-002',
        'status' => 'investigating',
        'severity' => 'high',
        'competitor' => 'Rival Technologies',
        'competitor_domain' => 'rival-tech.com',
        'data_type' => 'Product Roadmap',
        'original_sender' => 'product@company.com',
        'original_subject' => 'Product Roadmap 2025-2026',
        'detected_at' => '2025-01-25 16:45:22',
        'chain' => [
            ['hop' => 1, 'from' => 'product@company.com', 'to' => 'engineering@company.com', 'time' => '2025-01-25 10:00:00', 'action' => 'Sent', 'location' => 'Internal', 'ip' => '192.168.1.89', 'delivered' => true],
            ['hop' => 2, 'from' => 'dev.lead@company.com', 'to' => 'contractor@freelance.io', 'time' => '2025-01-25 14:30:00', 'action' => 'Forwarded', 'location' => 'External', 'ip' => '192.168.1.92', 'delivered' => true],
            ['hop' => 3, 'from' => 'contractor@freelance.io', 'to' => 'jobs@rival-tech.com', 'time' => '2025-01-25 16:42:00', 'action' => 'Forwarded', 'location' => 'Competitor', 'ip' => '78.90.12.34', 'delivered' => true],
        ],
        'attachments' => ['Roadmap_2025.pptx'],
        'data_exposed' => ['Feature plans', 'Release dates', 'Technology stack'],
        'risk_score' => 89
    ],
    [
        'leak_id' => 'CLK-003',
        'status' => 'blocked',
        'severity' => 'critical',
        'competitor' => 'Market Comp Inc',
        'competitor_domain' => 'marketcomp.io',
        'data_type' => 'Customer List',
        'original_sender' => 'sales@company.com',
        'original_subject' => 'Enterprise Customer Database',
        'detected_at' => '2025-01-26 11:20:15',
        'chain' => [
            ['hop' => 1, 'from' => 'sales@company.com', 'to' => 'sales.manager@company.com', 'time' => '2025-01-26 09:00:00', 'action' => 'Sent', 'location' => 'Internal', 'ip' => '192.168.1.55', 'delivered' => true],
            ['hop' => 2, 'from' => 'sales.manager@company.com', 'to' => 'hr@marketcomp.io', 'time' => '2025-01-26 11:18:00', 'action' => 'Attempted', 'location' => 'Competitor', 'ip' => '192.168.1.55', 'delivered' => false],
        ],
        'attachments' => ['Customer_Database.csv'],
        'data_exposed' => ['Blocked before delivery'],
        'risk_score' => 95
    ],
    [
        'leak_id' => 'CLK-004',
        'status' => 'confirmed_leak',
        'severity' => 'high',
        'competitor' => 'Tech Rivals LLC',
        'competitor_domain' => 'techrivals.net',
        'data_type' => 'Source Code',
        'original_sender' => 'dev.team@company.com',
        'original_subject' => 'API Integration Code',
        'detected_at' => '2025-01-24 09:30:00',
        'chain' => [
            ['hop' => 1, 'from' => 'dev.team@company.com', 'to' => 'qa.team@company.com', 'time' => '2025-01-23 15:00:00', 'action' => 'Sent', 'location' => 'Internal', 'ip' => '192.168.1.78', 'delivered' => true],
            ['hop' => 2, 'from' => 'qa.analyst@company.com', 'to' => 'personal@gmail.com', 'time' => '2025-01-23 18:45:00', 'action' => 'Forwarded', 'location' => 'Personal', 'ip' => '192.168.1.78', 'delivered' => true],
            ['hop' => 3, 'from' => 'personal@gmail.com', 'to' => 'hiring@techrivals.net', 'time' => '2025-01-24 09:28:00', 'action' => 'Forwarded', 'location' => 'Competitor', 'ip' => '98.76.54.32', 'delivered' => true],
        ],
        'attachments' => ['api_integration.zip', 'auth_module.py'],
        'data_exposed' => ['Authentication logic', 'API endpoints', 'Database schema'],
        'risk_score' => 92
    ]
];

// Email chain tracking
$emailChains = [
    [
        'chain_id' => 'CHN-001',
        'original_sender' => 'cfo@company.com',
        'subject' => 'Q4 Financial Summary',
        'hops' => [
            ['from' => 'cfo@company.com', 'to' => 'finance.team@company.com', 'time' => '2025-01-25 09:00', 'type' => 'internal', 'risk' => 15],
            ['from' => 'analyst@company.com', 'to' => 'manager@company.com', 'time' => '2025-01-25 10:30', 'type' => 'internal', 'risk' => 15],
            ['from' => 'manager@company.com', 'to' => 'consultant@external.com', 'time' => '2025-01-25 14:00', 'type' => 'external', 'risk' => 65],
            ['from' => 'consultant@external.com', 'to' => 'unknown@competitor.com', 'time' => '2025-01-26 08:00', 'type' => 'unauthorized', 'risk' => 95],
        ],
        'status' => 'leaked',
        'severity' => 'critical'
    ],
    [
        'chain_id' => 'CHN-002',
        'original_sender' => 'hr@company.com',
        'subject' => 'Employee Performance Reviews',
        'hops' => [
            ['from' => 'hr@company.com', 'to' => 'managers@company.com', 'time' => '2025-01-24 11:00', 'type' => 'internal', 'risk' => 20],
            ['from' => 'dept.head@company.com', 'to' => 'personal@gmail.com', 'time' => '2025-01-24 18:00', 'type' => 'external', 'risk' => 75],
        ],
        'status' => 'suspicious',
        'severity' => 'high'
    ],
    [
        'chain_id' => 'CHN-003',
        'original_sender' => 'legal@company.com',
        'subject' => 'Contract Draft - Project Alpha',
        'hops' => [
            ['from' => 'legal@company.com', 'to' => 'executives@company.com', 'time' => '2025-01-26 10:00', 'type' => 'internal', 'risk' => 10],
            ['from' => 'ceo@company.com', 'to' => 'partner@lawfirm.com', 'time' => '2025-01-26 11:30', 'type' => 'approved_external', 'risk' => 25],
        ],
        'status' => 'normal',
        'severity' => 'low'
    ],
];

// Monitoring agents
$agents = [
    ['id' => 'AGT-001', 'name' => 'Financial Data Monitor', 'status' => 'active', 'emails_scanned' => 12456, 'threats_detected' => 234, 'last_activity' => '2025-01-26 14:30:05', 'cpu' => 12, 'memory' => 256],
    ['id' => 'AGT-002', 'name' => 'PII Protection Agent', 'status' => 'active', 'emails_scanned' => 15678, 'threats_detected' => 456, 'last_activity' => '2025-01-26 14:29:58', 'cpu' => 18, 'memory' => 312],
    ['id' => 'AGT-003', 'name' => 'Executive Communications Monitor', 'status' => 'active', 'emails_scanned' => 3456, 'threats_detected' => 89, 'last_activity' => '2025-01-26 14:28:45', 'cpu' => 8, 'memory' => 128],
    ['id' => 'AGT-004', 'name' => 'Attachment Scanner', 'status' => 'active', 'emails_scanned' => 8901, 'threats_detected' => 178, 'last_activity' => '2025-01-26 14:30:01', 'cpu' => 25, 'memory' => 512],
    ['id' => 'AGT-005', 'name' => 'External Domain Watchdog', 'status' => 'paused', 'emails_scanned' => 6234, 'threats_detected' => 123, 'last_activity' => '2025-01-26 12:00:00', 'cpu' => 0, 'memory' => 64],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email DLP System - Advanced Monitoring</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --primary: #e91e63;
            --primary-dark: #c2185b;
            --secondary: #9c27b0;
            --bg-dark: #1a1a2e;
            --bg-card: #16213e;
            --bg-lighter: #1f2b47;
            --text: #e2e8f0;
            --text-muted: #94a3b8;
            --border: #2d3748;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg-dark);
            color: var(--text);
            min-height: 100vh;
        }

        /* Layout */
        .layout { display: flex; min-height: 100vh; }

        .sidebar {
            width: 260px;
            background: var(--bg-card);
            border-right: 1px solid var(--border);
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            overflow-y: auto;
            z-index: 100;
        }

        .sidebar-header {
            padding: 20px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            text-align: center;
        }

        .sidebar-header h1 { font-size: 18px; margin-bottom: 5px; }
        .sidebar-header p { font-size: 11px; opacity: 0.9; }

        .sidebar-nav { padding: 15px 0; }

        .nav-section {
            padding: 10px 20px;
            font-size: 10px;
            text-transform: uppercase;
            color: var(--text-muted);
            font-weight: 600;
            letter-spacing: 1px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 20px;
            color: var(--text);
            text-decoration: none;
            transition: all 0.3s;
            cursor: pointer;
            border-left: 3px solid transparent;
        }

        .nav-item:hover { background: var(--bg-lighter); }
        .nav-item.active { background: var(--bg-lighter); border-left-color: var(--primary); color: var(--primary); }
        .nav-item .icon { font-size: 18px; width: 24px; text-align: center; }
        .nav-item .badge { margin-left: auto; background: var(--danger); color: white; padding: 2px 8px; border-radius: 10px; font-size: 11px; }

        .sidebar-footer {
            padding: 15px 20px;
            border-top: 1px solid var(--border);
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: var(--bg-card);
        }

        .back-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 15px;
            background: var(--bg-lighter);
            color: var(--text);
            text-decoration: none;
            border-radius: 8px;
            font-size: 13px;
            transition: all 0.3s;
        }

        .back-btn:hover { background: var(--primary); }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 260px;
            padding: 20px;
        }

        .module { display: none; }
        .module.active { display: block; animation: fadeIn 0.3s; }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: var(--bg-card);
            border-radius: 10px;
            padding: 20px;
            border: 1px solid var(--border);
        }

        .stat-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .stat-icon { font-size: 24px; }
        .stat-value { font-size: 28px; font-weight: 700; }
        .stat-label { font-size: 12px; color: var(--text-muted); margin-top: 5px; }
        .stat-change { font-size: 11px; margin-top: 5px; }
        .stat-change.up { color: var(--danger); }
        .stat-change.down { color: var(--success); }

        /* Cards */
        .card {
            background: var(--bg-card);
            border-radius: 12px;
            border: 1px solid var(--border);
            margin-bottom: 20px;
            overflow: hidden;
        }

        .card-header {
            padding: 15px 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header h3 { font-size: 16px; display: flex; align-items: center; gap: 10px; }
        .card-body { padding: 20px; }

        /* Tables */
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th,
        .data-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }

        .data-table th {
            background: var(--bg-lighter);
            font-size: 11px;
            text-transform: uppercase;
            color: var(--text-muted);
            font-weight: 600;
        }

        .data-table tr:hover { background: var(--bg-lighter); }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }

        .badge-critical { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
        .badge-high { background: rgba(245, 158, 11, 0.2); color: #f59e0b; }
        .badge-medium { background: rgba(59, 130, 246, 0.2); color: #3b82f6; }
        .badge-low { background: rgba(16, 185, 129, 0.2); color: #10b981; }

        .badge-blocked { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
        .badge-quarantined { background: rgba(245, 158, 11, 0.2); color: #f59e0b; }
        .badge-flagged { background: rgba(59, 130, 246, 0.2); color: #3b82f6; }
        .badge-allowed { background: rgba(16, 185, 129, 0.2); color: #10b981; }

        .badge-active { background: rgba(16, 185, 129, 0.2); color: #10b981; }
        .badge-paused { background: rgba(156, 163, 175, 0.2); color: #9ca3af; }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            text-decoration: none;
        }

        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-dark); }
        .btn-success { background: var(--success); color: white; }
        .btn-success:hover { background: #059669; }
        .btn-warning { background: var(--warning); color: #1a1a1a; }
        .btn-warning:hover { background: #d97706; }
        .btn-danger { background: var(--danger); color: white; }
        .btn-danger:hover { background: #dc2626; }
        .btn-secondary { background: var(--bg-lighter); color: var(--text); border: 1px solid var(--border); }
        .btn-secondary:hover { background: var(--border); }
        .btn-sm { padding: 5px 10px; font-size: 11px; }

        /* Risk Meter */
        .risk-meter {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .risk-bar {
            flex: 1;
            height: 8px;
            background: var(--bg-lighter);
            border-radius: 4px;
            overflow: hidden;
        }

        .risk-fill {
            height: 100%;
            border-radius: 4px;
            transition: width 0.5s;
        }

        .risk-value { font-weight: 600; min-width: 35px; text-align: right; }

        /* Email Chain Visualization */
        .chain-container {
            padding: 20px;
        }

        .chain-hop {
            position: relative;
            padding: 15px 20px;
            background: var(--bg-lighter);
            border-radius: 8px;
            margin-bottom: 30px;
            border-left: 4px solid var(--success);
        }

        .chain-hop.external { border-left-color: var(--warning); }
        .chain-hop.unauthorized { border-left-color: var(--danger); }

        .chain-hop:not(:last-child)::after {
            content: '';
            position: absolute;
            left: 30px;
            bottom: -25px;
            width: 2px;
            height: 20px;
            background: var(--border);
        }

        .chain-hop:not(:last-child)::before {
            content: '↓';
            position: absolute;
            left: 24px;
            bottom: -28px;
            color: var(--primary);
            font-size: 16px;
        }

        .hop-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .hop-number {
            width: 28px;
            height: 28px;
            background: var(--primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 12px;
        }

        .hop-details { flex: 1; margin-left: 15px; }
        .hop-email { font-weight: 500; }
        .hop-meta { font-size: 12px; color: var(--text-muted); margin-top: 5px; }

        /* Agent Status Cards */
        .agent-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 15px;
        }

        .agent-card {
            background: var(--bg-card);
            border-radius: 10px;
            padding: 20px;
            border: 1px solid var(--border);
            position: relative;
        }

        .agent-status-indicator {
            position: absolute;
            top: 15px;
            right: 15px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        .agent-status-indicator.active { background: var(--success); }
        .agent-status-indicator.paused { background: var(--text-muted); animation: none; }

        @keyframes pulse {
            0%, 100% { opacity: 1; box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4); }
            50% { opacity: 0.8; box-shadow: 0 0 0 8px rgba(16, 185, 129, 0); }
        }

        .agent-name { font-weight: 600; margin-bottom: 5px; }
        .agent-id { font-size: 11px; color: var(--text-muted); margin-bottom: 15px; }

        .agent-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-bottom: 15px;
        }

        .agent-stat { font-size: 12px; }
        .agent-stat-value { font-weight: 600; color: var(--primary); }

        .agent-actions {
            display: flex;
            gap: 8px;
        }

        /* Chart Container */
        .chart-container {
            position: relative;
            height: 250px;
            margin: 15px 0;
        }

        /* Modal */
        .dlp-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .dlp-modal.active { display: flex; }

        .dlp-modal-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(4px);
        }

        .dlp-modal-content {
            position: relative;
            background: var(--bg-card);
            border-radius: 12px;
            border: 1px solid var(--border);
            width: 90%;
            max-width: 700px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5);
        }

        .dlp-modal-header {
            padding: 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            border-radius: 12px 12px 0 0;
        }

        .dlp-modal-header h3 { margin: 0; font-size: 18px; }

        .dlp-modal-close {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            font-size: 20px;
            cursor: pointer;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .dlp-modal-close:hover { background: rgba(255,255,255,0.3); }
        .dlp-modal-body { padding: 20px; }
        .dlp-modal-footer {
            padding: 15px 20px;
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        /* Form Elements */
        .form-group { margin-bottom: 15px; }
        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-size: 13px;
            font-weight: 500;
        }

        .form-input {
            width: 100%;
            padding: 10px 12px;
            background: var(--bg-lighter);
            border: 1px solid var(--border);
            border-radius: 6px;
            color: var(--text);
            font-size: 14px;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary);
        }

        /* Toggle Switch */
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 26px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #475569;
            border-radius: 26px;
            transition: 0.3s;
        }

        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 20px;
            width: 20px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            border-radius: 50%;
            transition: 0.3s;
        }

        .toggle-switch input:checked + .toggle-slider {
            background-color: var(--success);
        }

        .toggle-switch input:checked + .toggle-slider:before {
            transform: translateX(24px);
        }

        /* Info Box */
        .info-box {
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }

        .info-box.warning { background: rgba(245, 158, 11, 0.1); border-left: 4px solid var(--warning); }
        .info-box.danger { background: rgba(239, 68, 68, 0.1); border-left: 4px solid var(--danger); }
        .info-box.success { background: rgba(16, 185, 129, 0.1); border-left: 4px solid var(--success); }
        .info-box.info { background: rgba(59, 130, 246, 0.1); border-left: 4px solid var(--info); }

        /* Toast */
        .dlp-toast {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: var(--success);
            color: white;
            padding: 15px 25px;
            border-radius: 8px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.3);
            transform: translateY(100px);
            opacity: 0;
            transition: all 0.3s;
            z-index: 1001;
        }

        .dlp-toast.show { transform: translateY(0); opacity: 1; }
        .dlp-toast.error { background: var(--danger); }
        .dlp-toast.warning { background: var(--warning); color: #1a1a1a; }
        .dlp-toast.info { background: var(--info); }

        /* Activity Log */
        .activity-log {
            max-height: 400px;
            overflow-y: auto;
        }

        .activity-item {
            display: flex;
            gap: 15px;
            padding: 12px 0;
            border-bottom: 1px solid var(--border);
        }

        .activity-item:last-child { border-bottom: none; }

        .activity-icon {
            width: 36px;
            height: 36px;
            background: var(--bg-lighter);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            flex-shrink: 0;
        }

        .activity-content { flex: 1; }
        .activity-title { font-weight: 500; margin-bottom: 3px; }
        .activity-meta { font-size: 12px; color: var(--text-muted); }

        /* Quick Actions */
        .quick-actions {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        /* Filters */
        .filters {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .filter-group label {
            font-size: 11px;
            color: var(--text-muted);
            text-transform: uppercase;
        }

        /* Live Indicator */
        .live-indicator {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 5px 12px;
            background: rgba(16, 185, 129, 0.2);
            border-radius: 20px;
            font-size: 12px;
            color: var(--success);
        }

        .live-dot {
            width: 8px;
            height: 8px;
            background: var(--success);
            border-radius: 50%;
            animation: blink 1s infinite;
        }

        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; }
        }

        /* Spinner Animation */
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid var(--bg-lighter);
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-success:hover {
            background: #0d9668;
        }
    </style>
</head>
<body>
    <div class="layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h1>Email DLP System</h1>
                <p>Data Loss Prevention</p>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-section">Monitoring</div>
                <div class="nav-item active" onclick="showModule('dashboard')">
                    <span class="icon">📊</span>
                    <span>Dashboard</span>
                </div>
                <div class="nav-item" onclick="showModule('emails')">
                    <span class="icon">📧</span>
                    <span>Email Monitor</span>
                    <span class="badge">8</span>
                </div>
                <div class="nav-item" onclick="showModule('agents')">
                    <span class="icon">🤖</span>
                    <span>Monitoring Agents</span>
                </div>

                <div class="nav-section">Tracking</div>
                <div class="nav-item" onclick="showModule('chains')">
                    <span class="icon">🔗</span>
                    <span>Email Chains</span>
                </div>
                <div class="nav-item" onclick="showModule('leaks')">
                    <span class="icon">🚨</span>
                    <span>Leak Incidents</span>
                    <span class="badge">3</span>
                </div>
                <div class="nav-item" onclick="showModule('competitor-leaks')" style="background: linear-gradient(90deg, rgba(239,68,68,0.15), transparent);">
                    <span class="icon">🎯</span>
                    <span>Competitor Leaks</span>
                    <span class="badge" style="background: var(--danger); animation: pulse 2s infinite;">4</span>
                </div>

                <div class="nav-section">Configuration</div>
                <div class="nav-item" onclick="showModule('rules')">
                    <span class="icon">📋</span>
                    <span>Detection Rules</span>
                </div>
                <div class="nav-item" onclick="showModule('policies')">
                    <span class="icon">📜</span>
                    <span>Policies</span>
                </div>
                <div class="nav-item" onclick="showModule('quarantine')">
                    <span class="icon">🔒</span>
                    <span>Quarantine</span>
                    <span class="badge">5</span>
                </div>

                <div class="nav-section">Reports</div>
                <div class="nav-item" onclick="showModule('reports')">
                    <span class="icon">📈</span>
                    <span>Analytics</span>
                </div>
                <div class="nav-item" onclick="showModule('leak-reports')" style="background: linear-gradient(90deg, rgba(139,92,246,0.15), transparent);">
                    <span class="icon">📋</span>
                    <span>Leak Reports</span>
                    <span class="badge" style="background: #8b5cf6;">NEW</span>
                </div>
            </nav>

            <div class="sidebar-footer">
                <a href="index.php" class="back-btn">
                    <span>←</span>
                    <span>Back to Dashboard</span>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Dashboard Module -->
            <div id="dashboard" class="module active">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <div>
                        <h2 style="font-size: 24px; margin-bottom: 5px;">Email DLP Dashboard</h2>
                        <p style="color: var(--text-muted);">Real-time email monitoring and data protection</p>
                    </div>
                    <div class="live-indicator">
                        <span class="live-dot"></span>
                        Live Monitoring
                    </div>
                </div>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <span class="stat-icon">📧</span>
                        </div>
                        <div class="stat-value">12,456</div>
                        <div class="stat-label">Emails Scanned Today</div>
                        <div class="stat-change up">+15% from yesterday</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <span class="stat-icon">🚫</span>
                        </div>
                        <div class="stat-value" style="color: var(--danger);">234</div>
                        <div class="stat-label">Blocked/Quarantined</div>
                        <div class="stat-change up">+8% from yesterday</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <span class="stat-icon">⚠️</span>
                        </div>
                        <div class="stat-value" style="color: var(--warning);">567</div>
                        <div class="stat-label">Flagged for Review</div>
                        <div class="stat-change down">-3% from yesterday</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <span class="stat-icon">🔗</span>
                        </div>
                        <div class="stat-value" style="color: var(--danger);">3</div>
                        <div class="stat-label">Active Leak Incidents</div>
                        <div class="stat-change up">+2 new</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <span class="stat-icon">🤖</span>
                        </div>
                        <div class="stat-value" style="color: var(--success);">4/5</div>
                        <div class="stat-label">Active Agents</div>
                        <div class="stat-change">1 paused</div>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
                    <div class="card">
                        <div class="card-header">
                            <h3>Email Activity (Last 24 Hours)</h3>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="activityChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3>Threat Categories</h3>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="categoryChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Recent Activity</h3>
                        <button class="btn btn-sm btn-secondary" onclick="showModule('emails')">View All</button>
                    </div>
                    <div class="card-body">
                        <div class="activity-log">
                            <?php foreach (array_slice($agentTracking, 0, 5) as $activity): ?>
                            <div class="activity-item">
                                <div class="activity-icon">
                                    <?= $activity['action'] === 'Intercepted' ? '🚫' : ($activity['action'] === 'Quarantined' ? '🔒' : '⚠️') ?>
                                </div>
                                <div class="activity-content">
                                    <div class="activity-title"><?= htmlspecialchars($activity['action']) ?>: <?= htmlspecialchars($activity['email_id']) ?></div>
                                    <div class="activity-meta"><?= htmlspecialchars($activity['details']) ?></div>
                                    <div class="activity-meta"><?= htmlspecialchars($activity['timestamp']) ?> - Agent: <?= htmlspecialchars($activity['agent_id']) ?></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Email Monitor Module -->
            <div id="emails" class="module">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <div>
                        <h2 style="font-size: 24px; margin-bottom: 5px;">Email Monitor</h2>
                        <p style="color: var(--text-muted);">Track and manage monitored emails</p>
                    </div>
                    <div class="live-indicator">
                        <span class="live-dot"></span>
                        Auto-refresh: 30s
                    </div>
                </div>

                <div class="quick-actions">
                    <button class="btn btn-primary" onclick="openScanEmailModal()">Scan New Email</button>
                    <button class="btn btn-secondary" onclick="refreshEmails()">Refresh</button>
                    <button class="btn btn-secondary" onclick="exportEmails()">Export Report</button>
                    <button class="btn btn-success" onclick="createPDFReport()">Create PDF Report</button>
                </div>

                <div class="filters">
                    <div class="filter-group">
                        <label>Status</label>
                        <select class="form-input" style="width: 150px;" onchange="filterEmails()">
                            <option value="all">All Status</option>
                            <option value="blocked">Blocked</option>
                            <option value="quarantined">Quarantined</option>
                            <option value="flagged">Flagged</option>
                            <option value="allowed">Allowed</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Risk Level</label>
                        <select class="form-input" style="width: 150px;">
                            <option value="all">All Levels</option>
                            <option value="critical">Critical (90+)</option>
                            <option value="high">High (70-89)</option>
                            <option value="medium">Medium (40-69)</option>
                            <option value="low">Low (&lt;40)</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Category</label>
                        <select class="form-input" style="width: 150px;">
                            <option value="all">All Categories</option>
                            <option value="pii">PII Data</option>
                            <option value="financial">Financial Data</option>
                            <option value="source">Source Code</option>
                            <option value="hr">HR Data</option>
                        </select>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body" style="padding: 0;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Email ID</th>
                                    <th>From</th>
                                    <th>To</th>
                                    <th>Subject</th>
                                    <th>Category</th>
                                    <th>Risk</th>
                                    <th>Status</th>
                                    <th>Time</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($monitoredEmails as $email): ?>
                                <tr>
                                    <td><code><?= htmlspecialchars($email['id']) ?></code></td>
                                    <td><?= htmlspecialchars($email['from']) ?></td>
                                    <td><?= htmlspecialchars($email['to']) ?></td>
                                    <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                        <?= htmlspecialchars($email['subject']) ?>
                                    </td>
                                    <td><?= htmlspecialchars($email['category']) ?></td>
                                    <td>
                                        <div class="risk-meter">
                                            <div class="risk-bar">
                                                <div class="risk-fill" style="width: <?= $email['risk'] ?>%; background: <?= $email['risk'] >= 80 ? 'var(--danger)' : ($email['risk'] >= 50 ? 'var(--warning)' : 'var(--success)') ?>;"></div>
                                            </div>
                                            <span class="risk-value"><?= $email['risk'] ?></span>
                                        </div>
                                    </td>
                                    <td><span class="badge badge-<?= $email['status'] ?>"><?= ucfirst($email['status']) ?></span></td>
                                    <td style="font-size: 12px; color: var(--text-muted);"><?= date('H:i', strtotime($email['time'])) ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-secondary" onclick="viewEmailDetails('<?= $email['id'] ?>')">View</button>
                                        <?php if ($email['status'] === 'quarantined'): ?>
                                        <button class="btn btn-sm btn-success" onclick="releaseEmail('<?= $email['id'] ?>')">Release</button>
                                        <?php endif; ?>
                                        <?php if ($email['status'] === 'flagged'): ?>
                                        <button class="btn btn-sm btn-danger" onclick="blockEmail('<?= $email['id'] ?>')">Block</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Monitoring Agents Module -->
            <div id="agents" class="module">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <div>
                        <h2 style="font-size: 24px; margin-bottom: 5px;">Monitoring Agents</h2>
                        <p style="color: var(--text-muted);">Manage email tracking and monitoring agents</p>
                    </div>
                    <button class="btn btn-primary" onclick="openCreateAgentModal()">+ Create New Agent</button>
                </div>

                <div class="stats-grid" style="grid-template-columns: repeat(4, 1fr);">
                    <div class="stat-card">
                        <div class="stat-value" style="color: var(--success);">4</div>
                        <div class="stat-label">Active Agents</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">46,725</div>
                        <div class="stat-label">Total Emails Scanned</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value" style="color: var(--danger);">1,080</div>
                        <div class="stat-label">Threats Detected</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">1.27 GB</div>
                        <div class="stat-label">Total Memory Used</div>
                    </div>
                </div>

                <div class="agent-grid">
                    <?php foreach ($agents as $agent): ?>
                    <div class="agent-card">
                        <div class="agent-status-indicator <?= $agent['status'] ?>"></div>
                        <div class="agent-name"><?= htmlspecialchars($agent['name']) ?></div>
                        <div class="agent-id"><?= htmlspecialchars($agent['id']) ?></div>

                        <div class="agent-stats">
                            <div class="agent-stat">
                                <div>Emails Scanned</div>
                                <div class="agent-stat-value"><?= number_format($agent['emails_scanned']) ?></div>
                            </div>
                            <div class="agent-stat">
                                <div>Threats Found</div>
                                <div class="agent-stat-value"><?= number_format($agent['threats_detected']) ?></div>
                            </div>
                            <div class="agent-stat">
                                <div>CPU Usage</div>
                                <div class="agent-stat-value"><?= $agent['cpu'] ?>%</div>
                            </div>
                            <div class="agent-stat">
                                <div>Memory</div>
                                <div class="agent-stat-value"><?= $agent['memory'] ?> MB</div>
                            </div>
                        </div>

                        <div style="font-size: 11px; color: var(--text-muted); margin-bottom: 15px;">
                            Last activity: <?= date('M j, H:i', strtotime($agent['last_activity'])) ?>
                        </div>

                        <div class="agent-actions">
                            <?php if ($agent['status'] === 'active'): ?>
                            <button class="btn btn-sm btn-warning" onclick="pauseAgent('<?= $agent['id'] ?>')">Pause</button>
                            <?php else: ?>
                            <button class="btn btn-sm btn-success" onclick="resumeAgent('<?= $agent['id'] ?>')">Resume</button>
                            <?php endif; ?>
                            <button class="btn btn-sm btn-secondary" onclick="configureAgent('<?= $agent['id'] ?>')">Configure</button>
                            <button class="btn btn-sm btn-secondary" onclick="viewAgentLogs('<?= $agent['id'] ?>')">Logs</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Email Chains Module -->
            <div id="chains" class="module">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <div>
                        <h2 style="font-size: 24px; margin-bottom: 5px;">Email Chain Tracking</h2>
                        <p style="color: var(--text-muted);">Track email forwarding paths and identify data leaks</p>
                    </div>
                    <button class="btn btn-primary" onclick="openTrackEmailModal()">Track New Email</button>
                </div>

                <?php foreach ($emailChains as $chain): ?>
                <div class="card">
                    <div class="card-header">
                        <div>
                            <h3>
                                <?= $chain['status'] === 'leaked' ? '🚨' : ($chain['status'] === 'suspicious' ? '⚠️' : '✅') ?>
                                <?= htmlspecialchars($chain['subject']) ?>
                            </h3>
                            <div style="font-size: 12px; color: var(--text-muted); margin-top: 5px;">
                                Chain ID: <?= htmlspecialchars($chain['chain_id']) ?> | Original Sender: <?= htmlspecialchars($chain['original_sender']) ?>
                            </div>
                        </div>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <span class="badge badge-<?= $chain['severity'] ?>"><?= strtoupper($chain['severity']) ?></span>
                            <button class="btn btn-sm btn-secondary" onclick="investigateChain('<?= $chain['chain_id'] ?>')">Investigate</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="chain-container">
                            <?php foreach ($chain['hops'] as $index => $hop): ?>
                            <div class="chain-hop <?= $hop['type'] ?>">
                                <div class="hop-header">
                                    <div style="display: flex; align-items: center; flex: 1;">
                                        <div class="hop-number"><?= $index + 1 ?></div>
                                        <div class="hop-details">
                                            <div class="hop-email">
                                                <strong>From:</strong> <?= htmlspecialchars($hop['from']) ?>
                                                <span style="margin: 0 10px;">→</span>
                                                <strong>To:</strong> <?= htmlspecialchars($hop['to']) ?>
                                            </div>
                                            <div class="hop-meta">
                                                <?= htmlspecialchars($hop['time']) ?> |
                                                Type: <span class="badge badge-<?= $hop['type'] === 'unauthorized' ? 'critical' : ($hop['type'] === 'external' ? 'high' : 'low') ?>"><?= ucfirst(str_replace('_', ' ', $hop['type'])) ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="risk-meter" style="width: 120px;">
                                        <div class="risk-bar">
                                            <div class="risk-fill" style="width: <?= $hop['risk'] ?>%; background: <?= $hop['risk'] >= 80 ? 'var(--danger)' : ($hop['risk'] >= 50 ? 'var(--warning)' : 'var(--success)') ?>;"></div>
                                        </div>
                                        <span class="risk-value"><?= $hop['risk'] ?></span>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if ($chain['status'] === 'leaked'): ?>
                        <div class="info-box danger">
                            <strong>LEAK DETECTED:</strong> This email chain has reached an unauthorized external recipient. Immediate investigation recommended.
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Leak Incidents Module -->
            <div id="leaks" class="module">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <div>
                        <h2 style="font-size: 24px; margin-bottom: 5px;">Leak Incidents</h2>
                        <p style="color: var(--text-muted);">Active data leak investigations</p>
                    </div>
                </div>

                <div class="stats-grid" style="grid-template-columns: repeat(4, 1fr);">
                    <div class="stat-card">
                        <div class="stat-value" style="color: var(--danger);">3</div>
                        <div class="stat-label">Open Incidents</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value" style="color: var(--warning);">7</div>
                        <div class="stat-label">Under Investigation</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value" style="color: var(--success);">23</div>
                        <div class="stat-label">Resolved This Month</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">4.2 hrs</div>
                        <div class="stat-label">Avg Resolution Time</div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body" style="padding: 0;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Incident ID</th>
                                    <th>Type</th>
                                    <th>Source</th>
                                    <th>Destination</th>
                                    <th>Data Category</th>
                                    <th>Severity</th>
                                    <th>Status</th>
                                    <th>Detected</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code>INC-001</code></td>
                                    <td>External Forward</td>
                                    <td>manager@company.com</td>
                                    <td>unknown@competitor.com</td>
                                    <td>Financial Data</td>
                                    <td><span class="badge badge-critical">CRITICAL</span></td>
                                    <td><span class="badge badge-high">Investigating</span></td>
                                    <td>2025-01-26</td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" onclick="viewIncident('INC-001')">Investigate</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td><code>INC-002</code></td>
                                    <td>Personal Email</td>
                                    <td>dept.head@company.com</td>
                                    <td>personal@gmail.com</td>
                                    <td>HR Data</td>
                                    <td><span class="badge badge-high">HIGH</span></td>
                                    <td><span class="badge badge-high">Investigating</span></td>
                                    <td>2025-01-25</td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" onclick="viewIncident('INC-002')">Investigate</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td><code>INC-003</code></td>
                                    <td>Attachment Leak</td>
                                    <td>dev@company.com</td>
                                    <td>contractor@external.com</td>
                                    <td>Source Code</td>
                                    <td><span class="badge badge-high">HIGH</span></td>
                                    <td><span class="badge badge-medium">Pending Review</span></td>
                                    <td>2025-01-25</td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" onclick="viewIncident('INC-003')">Investigate</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Detection Rules Module -->
            <div id="rules" class="module">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <div>
                        <h2 style="font-size: 24px; margin-bottom: 5px;">Detection Rules</h2>
                        <p style="color: var(--text-muted);">Configure data detection patterns and policies</p>
                    </div>
                    <button class="btn btn-primary" onclick="openCreateRuleModal()">+ Create New Rule</button>
                </div>

                <div class="card">
                    <div class="card-body" style="padding: 0;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Rule Name</th>
                                    <th>Category</th>
                                    <th>Pattern</th>
                                    <th>Severity</th>
                                    <th>Matches</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($detectionRules as $rule): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($rule['name']) ?></strong></td>
                                    <td><?= htmlspecialchars($rule['category']) ?></td>
                                    <td><code style="font-size: 11px; max-width: 200px; display: inline-block; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars($rule['pattern']) ?></code></td>
                                    <td><span class="badge badge-<?= $rule['severity'] ?>"><?= strtoupper($rule['severity']) ?></span></td>
                                    <td><?= number_format($rule['matches']) ?></td>
                                    <td><span class="badge badge-<?= $rule['status'] ?>"><?= ucfirst($rule['status']) ?></span></td>
                                    <td>
                                        <button class="btn btn-sm btn-secondary" onclick="editRule(<?= $rule['id'] ?>)">Edit</button>
                                        <button class="btn btn-sm btn-secondary" onclick="testRule(<?= $rule['id'] ?>)">Test</button>
                                        <button class="btn btn-sm btn-danger" onclick="disableRule(<?= $rule['id'] ?>)">Disable</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Policies Module -->
            <div id="policies" class="module">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <div>
                        <h2 style="font-size: 24px; margin-bottom: 5px;">DLP Policies</h2>
                        <p style="color: var(--text-muted);">Configure data loss prevention policies</p>
                    </div>
                    <button class="btn btn-primary" onclick="openCreatePolicyModal()">+ Create Policy</button>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Active Policies</h3>
                    </div>
                    <div class="card-body">
                        <div style="display: grid; gap: 15px;">
                            <div id="policy-1" style="padding: 15px; background: var(--bg-lighter); border-radius: 8px; border-left: 4px solid var(--danger);">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <strong>Block External Financial Data</strong>
                                        <p style="font-size: 12px; color: var(--text-muted); margin-top: 5px;">Block all emails containing financial data sent to non-company domains</p>
                                    </div>
                                    <div style="display: flex; gap: 10px; align-items: center;">
                                        <span class="badge badge-critical">BLOCK</span>
                                        <button class="btn btn-sm btn-secondary" onclick="editPolicy(1)">Edit</button>
                                    </div>
                                </div>
                            </div>

                            <div id="policy-2" style="padding: 15px; background: var(--bg-lighter); border-radius: 8px; border-left: 4px solid var(--warning);">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <strong>Quarantine PII Data</strong>
                                        <p style="font-size: 12px; color: var(--text-muted); margin-top: 5px;">Quarantine emails containing SSN, credit cards, or personal information for review</p>
                                    </div>
                                    <div style="display: flex; gap: 10px; align-items: center;">
                                        <span class="badge badge-high">QUARANTINE</span>
                                        <button class="btn btn-sm btn-secondary" onclick="editPolicy(2)">Edit</button>
                                    </div>
                                </div>
                            </div>

                            <div id="policy-3" style="padding: 15px; background: var(--bg-lighter); border-radius: 8px; border-left: 4px solid var(--info);">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <strong>Flag Source Code Transfers</strong>
                                        <p style="font-size: 12px; color: var(--text-muted); margin-top: 5px;">Flag emails with source code attachments for security review</p>
                                    </div>
                                    <div style="display: flex; gap: 10px; align-items: center;">
                                        <span class="badge badge-medium">FLAG</span>
                                        <button class="btn btn-sm btn-secondary" onclick="editPolicy(3)">Edit</button>
                                    </div>
                                </div>
                            </div>

                            <div id="policy-4" style="padding: 15px; background: var(--bg-lighter); border-radius: 8px; border-left: 4px solid var(--success);">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <strong>Executive Communications Monitor</strong>
                                        <p style="font-size: 12px; color: var(--text-muted); margin-top: 5px;">Log and track all C-level executive external communications</p>
                                    </div>
                                    <div style="display: flex; gap: 10px; align-items: center;">
                                        <span class="badge badge-low">LOG</span>
                                        <button class="btn btn-sm btn-secondary" onclick="editPolicy(4)">Edit</button>
                                    </div>
                                </div>
                            </div>

                            <div id="policy-5" style="padding: 15px; background: linear-gradient(135deg, rgba(239,68,68,0.15), rgba(245,158,11,0.15)); border-radius: 8px; border-left: 4px solid #ff0000; border: 1px solid rgba(239,68,68,0.3);">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <strong style="color: #ff6b6b;">🎯 Competitor Leak Tracking Policy</strong>
                                        <p style="font-size: 12px; color: var(--text-muted); margin-top: 5px;">Track and block all emails sent to competitor domains. Full chain tracking to last recipient delivery.</p>
                                        <div style="display: flex; gap: 8px; margin-top: 8px;">
                                            <span style="font-size: 10px; padding: 2px 6px; background: rgba(239,68,68,0.2); border-radius: 4px; color: #ff6b6b;">6 Competitor Domains</span>
                                            <span style="font-size: 10px; padding: 2px 6px; background: rgba(245,158,11,0.2); border-radius: 4px; color: #f59e0b;">4 Active Leaks</span>
                                            <span style="font-size: 10px; padding: 2px 6px; background: rgba(16,185,129,0.2); border-radius: 4px; color: #10b981;">1 Blocked</span>
                                        </div>
                                    </div>
                                    <div style="display: flex; gap: 10px; align-items: center;">
                                        <span class="badge badge-critical" style="animation: pulse 2s infinite;">TRACK & BLOCK</span>
                                        <button class="btn btn-sm btn-secondary" onclick="editPolicy(5)">Edit</button>
                                        <button class="btn btn-sm btn-danger" onclick="showModule('competitor-leaks')">View Leaks</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Competitor Leaks Module -->
            <div id="competitor-leaks" class="module">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <div>
                        <h2 style="font-size: 24px; margin-bottom: 5px;">🎯 Competitor Leak Tracking</h2>
                        <p style="color: var(--text-muted);">Track emails sent to competitors with full delivery chain to last recipient</p>
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <button class="btn btn-secondary" onclick="openCompetitorDomainsModal()">Manage Competitors</button>
                        <button class="btn btn-primary" onclick="exportCompetitorReport()">Export Report</button>
                    </div>
                </div>

                <!-- Stats -->
                <div class="stats-grid" style="grid-template-columns: repeat(5, 1fr);">
                    <div class="stat-card" style="border-left: 4px solid var(--danger);">
                        <div class="stat-value" style="color: var(--danger);">4</div>
                        <div class="stat-label">Total Leaks Detected</div>
                    </div>
                    <div class="stat-card" style="border-left: 4px solid #ff6b6b;">
                        <div class="stat-value" style="color: #ff6b6b;">2</div>
                        <div class="stat-label">Confirmed Leaks</div>
                    </div>
                    <div class="stat-card" style="border-left: 4px solid var(--warning);">
                        <div class="stat-value" style="color: var(--warning);">1</div>
                        <div class="stat-label">Under Investigation</div>
                    </div>
                    <div class="stat-card" style="border-left: 4px solid var(--success);">
                        <div class="stat-value" style="color: var(--success);">1</div>
                        <div class="stat-label">Blocked</div>
                    </div>
                    <div class="stat-card" style="border-left: 4px solid var(--primary);">
                        <div class="stat-value">6</div>
                        <div class="stat-label">Monitored Competitors</div>
                    </div>
                </div>

                <!-- Competitor Domains -->
                <div class="card" style="margin-bottom: 20px;">
                    <div class="card-header">
                        <h3>🏢 Monitored Competitor Domains</h3>
                    </div>
                    <div class="card-body">
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px;">
                            <?php foreach ($competitorDomains as $domain => $info): ?>
                            <div style="padding: 15px; background: var(--bg-lighter); border-radius: 8px; border-left: 4px solid <?= $info['threat_level'] === 'critical' ? 'var(--danger)' : ($info['threat_level'] === 'high' ? 'var(--warning)' : 'var(--info)') ?>;">
                                <div style="display: flex; justify-content: space-between; align-items: start;">
                                    <div>
                                        <strong><?= htmlspecialchars($info['name']) ?></strong>
                                        <div style="font-size: 12px; color: var(--primary); margin-top: 3px;"><?= htmlspecialchars($domain) ?></div>
                                        <div style="font-size: 11px; color: var(--text-muted); margin-top: 5px;"><?= htmlspecialchars($info['industry']) ?></div>
                                    </div>
                                    <span class="badge badge-<?= $info['threat_level'] === 'critical' ? 'critical' : ($info['threat_level'] === 'high' ? 'high' : 'medium') ?>"><?= strtoupper($info['threat_level']) ?></span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Leak Incidents -->
                <div class="card">
                    <div class="card-header">
                        <h3>📧 Detected Competitor Leaks - Full Chain Tracking</h3>
                    </div>
                    <div class="card-body" style="padding: 0;">
                        <?php foreach ($competitorLeaks as $leak): ?>
                        <div class="leak-incident" style="padding: 20px; border-bottom: 1px solid var(--border);">
                            <!-- Leak Header -->
                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px;">
                                <div>
                                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 5px;">
                                        <span style="font-size: 18px;">
                                            <?= $leak['status'] === 'confirmed_leak' ? '🚨' : ($leak['status'] === 'investigating' ? '🔍' : '🛡️') ?>
                                        </span>
                                        <strong style="font-size: 16px;"><?= htmlspecialchars($leak['original_subject']) ?></strong>
                                        <span class="badge badge-<?= $leak['severity'] === 'critical' ? 'critical' : 'high' ?>"><?= strtoupper($leak['severity']) ?></span>
                                        <span class="badge" style="background: <?= $leak['status'] === 'confirmed_leak' ? 'var(--danger)' : ($leak['status'] === 'investigating' ? 'var(--warning)' : 'var(--success)') ?>;">
                                            <?= strtoupper(str_replace('_', ' ', $leak['status'])) ?>
                                        </span>
                                    </div>
                                    <div style="font-size: 13px; color: var(--text-muted);">
                                        Leak ID: <span style="color: var(--primary);"><?= $leak['leak_id'] ?></span> |
                                        Competitor: <span style="color: var(--danger);"><?= htmlspecialchars($leak['competitor']) ?></span> |
                                        Data Type: <span style="color: var(--warning);"><?= htmlspecialchars($leak['data_type']) ?></span> |
                                        Detected: <?= $leak['detected_at'] ?>
                                    </div>
                                </div>
                                <div style="display: flex; gap: 10px;">
                                    <button class="btn btn-sm btn-secondary" onclick="investigateCompetitorLeak('<?= $leak['leak_id'] ?>')">Investigate</button>
                                    <button class="btn btn-sm btn-danger" onclick="escalateCompetitorLeak('<?= $leak['leak_id'] ?>')">Escalate</button>
                                </div>
                            </div>

                            <!-- Email Chain Visualization -->
                            <div style="background: var(--bg-dark); border-radius: 8px; padding: 15px; margin-bottom: 15px;">
                                <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 10px; display: flex; justify-content: space-between;">
                                    <span>📬 Email Delivery Chain (<?= count($leak['chain']) ?> hops)</span>
                                    <span>Risk Score: <span style="color: <?= $leak['risk_score'] > 90 ? 'var(--danger)' : 'var(--warning)' ?>; font-weight: bold;"><?= $leak['risk_score'] ?>/100</span></span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 5px; overflow-x: auto; padding: 10px 0;">
                                    <?php foreach ($leak['chain'] as $index => $hop): ?>
                                    <div style="flex-shrink: 0; text-align: center;">
                                        <div style="padding: 12px 15px; background: <?= $hop['location'] === 'Internal' ? 'rgba(16,185,129,0.2)' : ($hop['location'] === 'Competitor' || $hop['location'] === 'Competitor Internal' ? 'rgba(239,68,68,0.3)' : 'rgba(245,158,11,0.2)') ?>; border-radius: 8px; border: 2px solid <?= $hop['location'] === 'Internal' ? 'var(--success)' : ($hop['location'] === 'Competitor' || $hop['location'] === 'Competitor Internal' ? 'var(--danger)' : 'var(--warning)') ?>; min-width: 180px;">
                                            <div style="font-size: 10px; color: var(--text-muted); margin-bottom: 3px;">Hop <?= $hop['hop'] ?> - <?= $hop['action'] ?></div>
                                            <div style="font-size: 11px; margin-bottom: 3px;"><strong>From:</strong> <?= htmlspecialchars($hop['from']) ?></div>
                                            <div style="font-size: 11px; margin-bottom: 3px;"><strong>To:</strong> <?= htmlspecialchars($hop['to']) ?></div>
                                            <div style="font-size: 10px; color: var(--text-muted);"><?= $hop['time'] ?></div>
                                            <div style="font-size: 10px; margin-top: 5px;">
                                                <span style="padding: 2px 6px; border-radius: 4px; background: <?= $hop['location'] === 'Internal' ? 'var(--success)' : ($hop['location'] === 'Competitor' || $hop['location'] === 'Competitor Internal' ? 'var(--danger)' : 'var(--warning)') ?>; color: white;"><?= $hop['location'] ?></span>
                                                <?php if ($hop['delivered']): ?>
                                                <span style="color: var(--success);">✓ Delivered</span>
                                                <?php else: ?>
                                                <span style="color: var(--danger);">✗ Blocked</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php if ($index < count($leak['chain']) - 1): ?>
                                    <div style="flex-shrink: 0; color: var(--text-muted); font-size: 20px;">→</div>
                                    <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Exposed Data & Attachments -->
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                <div style="background: var(--bg-lighter); padding: 12px; border-radius: 8px;">
                                    <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 8px;">📎 Attachments</div>
                                    <div style="display: flex; flex-wrap: wrap; gap: 5px;">
                                        <?php foreach ($leak['attachments'] as $attachment): ?>
                                        <span style="padding: 4px 8px; background: var(--bg-dark); border-radius: 4px; font-size: 11px;"><?= htmlspecialchars($attachment) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <div style="background: var(--bg-lighter); padding: 12px; border-radius: 8px;">
                                    <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 8px;">⚠️ Data Exposed</div>
                                    <div style="display: flex; flex-wrap: wrap; gap: 5px;">
                                        <?php foreach ($leak['data_exposed'] as $data): ?>
                                        <span style="padding: 4px 8px; background: rgba(239,68,68,0.2); border-radius: 4px; font-size: 11px; color: #ff6b6b;"><?= htmlspecialchars($data) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Quarantine Module -->
            <div id="quarantine" class="module">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <div>
                        <h2 style="font-size: 24px; margin-bottom: 5px;">Email Quarantine</h2>
                        <p style="color: var(--text-muted);">Review and manage quarantined emails</p>
                    </div>
                </div>

                <div class="stats-grid" style="grid-template-columns: repeat(4, 1fr);">
                    <div class="stat-card">
                        <div class="stat-value" style="color: var(--warning);">5</div>
                        <div class="stat-label">Pending Review</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">23</div>
                        <div class="stat-label">Released Today</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value" style="color: var(--danger);">12</div>
                        <div class="stat-label">Permanently Blocked</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">48 hrs</div>
                        <div class="stat-label">Auto-Delete After</div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body" style="padding: 0;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Email ID</th>
                                    <th>From</th>
                                    <th>To</th>
                                    <th>Subject</th>
                                    <th>Reason</th>
                                    <th>Quarantined</th>
                                    <th>Expires</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_filter($monitoredEmails, fn($e) => $e['status'] === 'quarantined') as $email): ?>
                                <tr>
                                    <td><code><?= htmlspecialchars($email['id']) ?></code></td>
                                    <td><?= htmlspecialchars($email['from']) ?></td>
                                    <td><?= htmlspecialchars($email['to']) ?></td>
                                    <td><?= htmlspecialchars($email['subject']) ?></td>
                                    <td><?= htmlspecialchars($email['category']) ?></td>
                                    <td><?= date('M j, H:i', strtotime($email['time'])) ?></td>
                                    <td>48 hrs</td>
                                    <td>
                                        <button class="btn btn-sm btn-secondary" onclick="reviewEmail('<?= $email['id'] ?>')">Review</button>
                                        <button class="btn btn-sm btn-success" onclick="releaseEmail('<?= $email['id'] ?>')">Release</button>
                                        <button class="btn btn-sm btn-danger" onclick="deleteEmail('<?= $email['id'] ?>')">Delete</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Reports Module -->
            <div id="reports" class="module">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <div>
                        <h2 style="font-size: 24px; margin-bottom: 5px;">Analytics & Reports</h2>
                        <p style="color: var(--text-muted);">DLP performance and trend analysis</p>
                    </div>
                    <button class="btn btn-primary" onclick="generateReport()">Generate Report</button>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="card">
                        <div class="card-header">
                            <h3>Detection Trends (30 Days)</h3>
                        </div>
                        <div class="card-body">
                            <div class="chart-container" style="height: 300px;">
                                <canvas id="trendsChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3>Top Data Categories</h3>
                        </div>
                        <div class="card-body">
                            <div class="chart-container" style="height: 300px;">
                                <canvas id="categoriesChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Top Offenders</h3>
                    </div>
                    <div class="card-body" style="padding: 0;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Email Address</th>
                                    <th>Department</th>
                                    <th>Violations</th>
                                    <th>Most Common Category</th>
                                    <th>Risk Score</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>john.smith@company.com</td>
                                    <td>Finance</td>
                                    <td>12</td>
                                    <td>Financial Data</td>
                                    <td><span class="badge badge-critical">High Risk</span></td>
                                    <td><button class="btn btn-sm btn-secondary" onclick="viewUserProfile('john.smith')">View Profile</button></td>
                                </tr>
                                <tr>
                                    <td>sarah.jones@company.com</td>
                                    <td>HR</td>
                                    <td>8</td>
                                    <td>PII Data</td>
                                    <td><span class="badge badge-high">Medium Risk</span></td>
                                    <td><button class="btn btn-sm btn-secondary" onclick="viewUserProfile('sarah.jones')">View Profile</button></td>
                                </tr>
                                <tr>
                                    <td>dev.team@company.com</td>
                                    <td>Engineering</td>
                                    <td>5</td>
                                    <td>Source Code</td>
                                    <td><span class="badge badge-medium">Low Risk</span></td>
                                    <td><button class="btn btn-sm btn-secondary" onclick="viewUserProfile('dev.team')">View Profile</button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Leak Reports Module -->
            <div id="leak-reports" class="module">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <div>
                        <h2 style="font-size: 24px; margin-bottom: 5px;">📋 Leak Detection Reports</h2>
                        <p style="color: var(--text-muted);">Comprehensive reports showing leaked emails and their complete delivery trail</p>
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <button class="btn btn-secondary" onclick="printLeakReport()">🖨️ Print Report</button>
                        <button class="btn btn-secondary" onclick="exportLeakReportPDF()">📄 Export PDF</button>
                        <button class="btn btn-primary" onclick="exportLeakReportCSV()">📥 Export CSV</button>
                    </div>
                </div>

                <!-- Report Header -->
                <div class="card" style="margin-bottom: 20px; background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); border: 1px solid var(--primary);">
                    <div class="card-body">
                        <div style="text-align: center; padding: 20px;">
                            <h1 style="font-size: 28px; margin-bottom: 10px; color: var(--primary);">DATA LEAK DETECTION REPORT</h1>
                            <p style="font-size: 14px; color: var(--text-muted);">Email Trail Analysis & Forensic Investigation Summary</p>
                            <div style="display: flex; justify-content: center; gap: 30px; margin-top: 20px;">
                                <div>
                                    <span style="font-size: 12px; color: var(--text-muted);">Report Generated:</span>
                                    <div style="font-weight: bold;"><?= date('F j, Y - H:i:s') ?></div>
                                </div>
                                <div>
                                    <span style="font-size: 12px; color: var(--text-muted);">Report Period:</span>
                                    <div style="font-weight: bold;">January 1-26, 2025</div>
                                </div>
                                <div>
                                    <span style="font-size: 12px; color: var(--text-muted);">Report ID:</span>
                                    <div style="font-weight: bold;">RPT-<?= date('Ymd') ?>-001</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Executive Summary -->
                <div class="card" style="margin-bottom: 20px;">
                    <div class="card-header" style="background: linear-gradient(90deg, var(--danger), var(--primary));">
                        <h3 style="color: white;">📊 Executive Summary</h3>
                    </div>
                    <div class="card-body">
                        <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 20px; margin-bottom: 20px;">
                            <div style="text-align: center; padding: 20px; background: var(--bg-lighter); border-radius: 12px; border-bottom: 4px solid var(--danger);">
                                <div style="font-size: 36px; font-weight: bold; color: var(--danger);">7</div>
                                <div style="font-size: 12px; color: var(--text-muted);">Total Leaks Detected</div>
                            </div>
                            <div style="text-align: center; padding: 20px; background: var(--bg-lighter); border-radius: 12px; border-bottom: 4px solid #ff6b6b;">
                                <div style="font-size: 36px; font-weight: bold; color: #ff6b6b;">4</div>
                                <div style="font-size: 12px; color: var(--text-muted);">Confirmed Deliveries</div>
                            </div>
                            <div style="text-align: center; padding: 20px; background: var(--bg-lighter); border-radius: 12px; border-bottom: 4px solid var(--success);">
                                <div style="font-size: 36px; font-weight: bold; color: var(--success);">2</div>
                                <div style="font-size: 12px; color: var(--text-muted);">Blocked in Transit</div>
                            </div>
                            <div style="text-align: center; padding: 20px; background: var(--bg-lighter); border-radius: 12px; border-bottom: 4px solid var(--warning);">
                                <div style="font-size: 36px; font-weight: bold; color: var(--warning);">1</div>
                                <div style="font-size: 12px; color: var(--text-muted);">Under Investigation</div>
                            </div>
                            <div style="text-align: center; padding: 20px; background: var(--bg-lighter); border-radius: 12px; border-bottom: 4px solid var(--primary);">
                                <div style="font-size: 36px; font-weight: bold; color: var(--primary);">23</div>
                                <div style="font-size: 12px; color: var(--text-muted);">Total Email Hops Traced</div>
                            </div>
                        </div>
                        <div style="background: rgba(239,68,68,0.1); padding: 15px; border-radius: 8px; border-left: 4px solid var(--danger);">
                            <strong style="color: var(--danger);">⚠️ Critical Finding:</strong>
                            <span>4 sensitive documents successfully reached competitor destinations. Immediate action required for CLK-001 and CLK-004.</span>
                        </div>
                    </div>
                </div>

                <!-- Detailed Leak Reports -->
                <div class="card" style="margin-bottom: 20px;">
                    <div class="card-header">
                        <h3>📧 Detailed Leak Reports with Email Trail</h3>
                    </div>
                    <div class="card-body" style="padding: 0;">

                        <!-- Leak Report 1 -->
                        <div class="leak-report-item" style="padding: 25px; border-bottom: 2px solid var(--border);">
                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 20px;">
                                <div>
                                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                                        <span style="font-size: 24px;">🚨</span>
                                        <h4 style="font-size: 18px; margin: 0;">LEAK #CLK-001: Q4 Financial Report - Confidential</h4>
                                        <span class="badge badge-critical">CRITICAL</span>
                                        <span class="badge" style="background: var(--danger);">CONFIRMED LEAK</span>
                                    </div>
                                    <div style="font-size: 13px; color: var(--text-muted);">
                                        Original Sender: <strong>john.smith@company.com</strong> (Finance Department) |
                                        Detected: <strong>January 26, 2025 14:30:05</strong> |
                                        Risk Score: <strong style="color: var(--danger);">98/100</strong>
                                    </div>
                                </div>
                                <div style="text-align: right;">
                                    <div style="font-size: 11px; color: var(--text-muted);">Final Destination</div>
                                    <div style="font-size: 14px; color: var(--danger); font-weight: bold;">strategy@competitor.com</div>
                                    <div style="font-size: 11px; color: var(--text-muted);">Acme Corp (Competitor)</div>
                                </div>
                            </div>

                            <!-- Email Trail Visualization -->
                            <div style="background: var(--bg-dark); border-radius: 12px; padding: 20px; margin-bottom: 20px;">
                                <div style="font-size: 13px; font-weight: bold; margin-bottom: 15px; color: var(--primary);">📬 COMPLETE EMAIL TRAIL (4 Hops)</div>

                                <div class="email-trail" style="position: relative;">
                                    <!-- Hop 1 -->
                                    <div style="display: flex; align-items: start; margin-bottom: 20px;">
                                        <div style="width: 50px; text-align: center;">
                                            <div style="width: 40px; height: 40px; background: var(--success); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; margin: 0 auto;">1</div>
                                            <div style="width: 2px; height: 40px; background: var(--success); margin: 5px auto;"></div>
                                        </div>
                                        <div style="flex: 1; background: rgba(16,185,129,0.1); border: 1px solid var(--success); border-radius: 8px; padding: 15px; margin-left: 15px;">
                                            <div style="display: flex; justify-content: space-between; align-items: start;">
                                                <div>
                                                    <span style="padding: 3px 8px; background: var(--success); color: white; border-radius: 4px; font-size: 11px;">INTERNAL - ORIGIN</span>
                                                    <div style="margin-top: 10px;">
                                                        <div style="font-size: 13px;"><strong>From:</strong> john.smith@company.com</div>
                                                        <div style="font-size: 13px;"><strong>To:</strong> finance.team@company.com</div>
                                                        <div style="font-size: 12px; color: var(--text-muted); margin-top: 5px;">Action: Original Send | IP: 192.168.1.45</div>
                                                    </div>
                                                </div>
                                                <div style="text-align: right;">
                                                    <div style="font-size: 12px; color: var(--text-muted);">Jan 26, 2025</div>
                                                    <div style="font-size: 14px; font-weight: bold;">09:15:00</div>
                                                    <div style="color: var(--success); font-size: 12px; margin-top: 5px;">✓ Delivered</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Hop 2 -->
                                    <div style="display: flex; align-items: start; margin-bottom: 20px;">
                                        <div style="width: 50px; text-align: center;">
                                            <div style="width: 40px; height: 40px; background: var(--warning); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; margin: 0 auto;">2</div>
                                            <div style="width: 2px; height: 40px; background: var(--warning); margin: 5px auto;"></div>
                                        </div>
                                        <div style="flex: 1; background: rgba(245,158,11,0.1); border: 1px solid var(--warning); border-radius: 8px; padding: 15px; margin-left: 15px;">
                                            <div style="display: flex; justify-content: space-between; align-items: start;">
                                                <div>
                                                    <span style="padding: 3px 8px; background: var(--warning); color: #1a1a2e; border-radius: 4px; font-size: 11px;">EXTERNAL - FORWARDED</span>
                                                    <div style="margin-top: 10px;">
                                                        <div style="font-size: 13px;"><strong>From:</strong> mary.wilson@company.com</div>
                                                        <div style="font-size: 13px;"><strong>To:</strong> external.consultant@advisory.com</div>
                                                        <div style="font-size: 12px; color: var(--text-muted); margin-top: 5px;">Action: Forwarded | IP: 192.168.1.67</div>
                                                    </div>
                                                </div>
                                                <div style="text-align: right;">
                                                    <div style="font-size: 12px; color: var(--text-muted);">Jan 26, 2025</div>
                                                    <div style="font-size: 14px; font-weight: bold;">11:30:00</div>
                                                    <div style="color: var(--success); font-size: 12px; margin-top: 5px;">✓ Delivered</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Hop 3 -->
                                    <div style="display: flex; align-items: start; margin-bottom: 20px;">
                                        <div style="width: 50px; text-align: center;">
                                            <div style="width: 40px; height: 40px; background: var(--danger); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; margin: 0 auto;">3</div>
                                            <div style="width: 2px; height: 40px; background: var(--danger); margin: 5px auto;"></div>
                                        </div>
                                        <div style="flex: 1; background: rgba(239,68,68,0.1); border: 1px solid var(--danger); border-radius: 8px; padding: 15px; margin-left: 15px;">
                                            <div style="display: flex; justify-content: space-between; align-items: start;">
                                                <div>
                                                    <span style="padding: 3px 8px; background: var(--danger); color: white; border-radius: 4px; font-size: 11px;">⚠️ COMPETITOR - BREACH POINT</span>
                                                    <div style="margin-top: 10px;">
                                                        <div style="font-size: 13px;"><strong>From:</strong> external.consultant@advisory.com</div>
                                                        <div style="font-size: 13px;"><strong>To:</strong> <span style="color: var(--danger); font-weight: bold;">contact@competitor.com</span></div>
                                                        <div style="font-size: 12px; color: var(--text-muted); margin-top: 5px;">Action: Forwarded to Competitor | IP: 45.67.89.123</div>
                                                    </div>
                                                </div>
                                                <div style="text-align: right;">
                                                    <div style="font-size: 12px; color: var(--text-muted);">Jan 26, 2025</div>
                                                    <div style="font-size: 14px; font-weight: bold;">14:28:00</div>
                                                    <div style="color: var(--danger); font-size: 12px; margin-top: 5px;">⚠️ Delivered to Competitor</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Hop 4 - Final -->
                                    <div style="display: flex; align-items: start;">
                                        <div style="width: 50px; text-align: center;">
                                            <div style="width: 40px; height: 40px; background: #ff0000; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; margin: 0 auto; animation: pulse 2s infinite;">4</div>
                                        </div>
                                        <div style="flex: 1; background: rgba(255,0,0,0.15); border: 2px solid #ff0000; border-radius: 8px; padding: 15px; margin-left: 15px;">
                                            <div style="display: flex; justify-content: space-between; align-items: start;">
                                                <div>
                                                    <span style="padding: 3px 8px; background: #ff0000; color: white; border-radius: 4px; font-size: 11px;">🎯 FINAL DESTINATION - COMPETITOR INTERNAL</span>
                                                    <div style="margin-top: 10px;">
                                                        <div style="font-size: 13px;"><strong>From:</strong> contact@competitor.com</div>
                                                        <div style="font-size: 13px;"><strong>To:</strong> <span style="color: #ff0000; font-weight: bold;">strategy@competitor.com</span></div>
                                                        <div style="font-size: 12px; color: var(--text-muted); margin-top: 5px;">Action: Internal Forward at Competitor | IP: 45.67.89.150</div>
                                                    </div>
                                                </div>
                                                <div style="text-align: right;">
                                                    <div style="font-size: 12px; color: var(--text-muted);">Jan 26, 2025</div>
                                                    <div style="font-size: 14px; font-weight: bold;">14:45:00</div>
                                                    <div style="color: #ff0000; font-size: 12px; margin-top: 5px;">🎯 FINAL RECIPIENT</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Data & Attachments -->
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                <div style="background: var(--bg-lighter); padding: 15px; border-radius: 8px;">
                                    <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 10px;">📎 Attachments Leaked</div>
                                    <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                                        <span style="padding: 6px 12px; background: var(--bg-dark); border-radius: 6px; font-size: 12px;">📊 Q4_Financial_Report.xlsx</span>
                                        <span style="padding: 6px 12px; background: var(--bg-dark); border-radius: 6px; font-size: 12px;">📄 Revenue_Forecast_2025.pdf</span>
                                    </div>
                                </div>
                                <div style="background: rgba(239,68,68,0.1); padding: 15px; border-radius: 8px; border: 1px solid var(--danger);">
                                    <div style="font-size: 12px; color: var(--danger); margin-bottom: 10px;">⚠️ Sensitive Data Exposed</div>
                                    <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                                        <span style="padding: 6px 12px; background: rgba(239,68,68,0.2); border-radius: 6px; font-size: 12px; color: #ff6b6b;">Revenue figures</span>
                                        <span style="padding: 6px 12px; background: rgba(239,68,68,0.2); border-radius: 6px; font-size: 12px; color: #ff6b6b;">Profit margins</span>
                                        <span style="padding: 6px 12px; background: rgba(239,68,68,0.2); border-radius: 6px; font-size: 12px; color: #ff6b6b;">Growth projections</span>
                                        <span style="padding: 6px 12px; background: rgba(239,68,68,0.2); border-radius: 6px; font-size: 12px; color: #ff6b6b;">Market strategy</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Leak Report 2 -->
                        <div class="leak-report-item" style="padding: 25px; border-bottom: 2px solid var(--border);">
                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 20px;">
                                <div>
                                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                                        <span style="font-size: 24px;">🔍</span>
                                        <h4 style="font-size: 18px; margin: 0;">LEAK #CLK-002: Product Roadmap 2025-2026</h4>
                                        <span class="badge badge-high">HIGH</span>
                                        <span class="badge" style="background: var(--warning);">INVESTIGATING</span>
                                    </div>
                                    <div style="font-size: 13px; color: var(--text-muted);">
                                        Original Sender: <strong>product@company.com</strong> (Product Team) |
                                        Detected: <strong>January 25, 2025 16:45:22</strong> |
                                        Risk Score: <strong style="color: var(--warning);">89/100</strong>
                                    </div>
                                </div>
                                <div style="text-align: right;">
                                    <div style="font-size: 11px; color: var(--text-muted);">Final Destination</div>
                                    <div style="font-size: 14px; color: var(--danger); font-weight: bold;">jobs@rival-tech.com</div>
                                    <div style="font-size: 11px; color: var(--text-muted);">Rival Technologies (Competitor)</div>
                                </div>
                            </div>

                            <!-- Email Trail -->
                            <div style="background: var(--bg-dark); border-radius: 12px; padding: 20px; margin-bottom: 20px;">
                                <div style="font-size: 13px; font-weight: bold; margin-bottom: 15px; color: var(--primary);">📬 COMPLETE EMAIL TRAIL (3 Hops)</div>

                                <div style="display: flex; align-items: center; gap: 15px; overflow-x: auto; padding: 10px 0;">
                                    <!-- Hop 1 -->
                                    <div style="flex-shrink: 0; min-width: 220px; padding: 15px; background: rgba(16,185,129,0.1); border: 2px solid var(--success); border-radius: 8px;">
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                            <span style="width: 28px; height: 28px; background: var(--success); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 12px;">1</span>
                                            <span style="font-size: 10px; padding: 2px 6px; background: var(--success); color: white; border-radius: 4px;">INTERNAL</span>
                                        </div>
                                        <div style="font-size: 11px;"><strong>From:</strong> product@company.com</div>
                                        <div style="font-size: 11px;"><strong>To:</strong> engineering@company.com</div>
                                        <div style="font-size: 10px; color: var(--text-muted); margin-top: 8px;">Jan 25, 10:00 AM</div>
                                        <div style="font-size: 10px; color: var(--success);">✓ Delivered</div>
                                    </div>

                                    <div style="font-size: 24px; color: var(--text-muted);">→</div>

                                    <!-- Hop 2 -->
                                    <div style="flex-shrink: 0; min-width: 220px; padding: 15px; background: rgba(245,158,11,0.1); border: 2px solid var(--warning); border-radius: 8px;">
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                            <span style="width: 28px; height: 28px; background: var(--warning); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 12px;">2</span>
                                            <span style="font-size: 10px; padding: 2px 6px; background: var(--warning); color: #1a1a2e; border-radius: 4px;">EXTERNAL</span>
                                        </div>
                                        <div style="font-size: 11px;"><strong>From:</strong> dev.lead@company.com</div>
                                        <div style="font-size: 11px;"><strong>To:</strong> contractor@freelance.io</div>
                                        <div style="font-size: 10px; color: var(--text-muted); margin-top: 8px;">Jan 25, 2:30 PM</div>
                                        <div style="font-size: 10px; color: var(--success);">✓ Delivered</div>
                                    </div>

                                    <div style="font-size: 24px; color: var(--text-muted);">→</div>

                                    <!-- Hop 3 - Final -->
                                    <div style="flex-shrink: 0; min-width: 220px; padding: 15px; background: rgba(239,68,68,0.15); border: 2px solid var(--danger); border-radius: 8px;">
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                            <span style="width: 28px; height: 28px; background: var(--danger); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 12px;">3</span>
                                            <span style="font-size: 10px; padding: 2px 6px; background: var(--danger); color: white; border-radius: 4px;">🎯 COMPETITOR</span>
                                        </div>
                                        <div style="font-size: 11px;"><strong>From:</strong> contractor@freelance.io</div>
                                        <div style="font-size: 11px; color: var(--danger);"><strong>To:</strong> jobs@rival-tech.com</div>
                                        <div style="font-size: 10px; color: var(--text-muted); margin-top: 8px;">Jan 25, 4:42 PM</div>
                                        <div style="font-size: 10px; color: var(--danger);">🎯 Final Recipient</div>
                                    </div>
                                </div>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                <div style="background: var(--bg-lighter); padding: 15px; border-radius: 8px;">
                                    <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 10px;">📎 Attachments Leaked</div>
                                    <span style="padding: 6px 12px; background: var(--bg-dark); border-radius: 6px; font-size: 12px;">📊 Roadmap_2025.pptx</span>
                                </div>
                                <div style="background: rgba(239,68,68,0.1); padding: 15px; border-radius: 8px; border: 1px solid var(--danger);">
                                    <div style="font-size: 12px; color: var(--danger); margin-bottom: 10px;">⚠️ Sensitive Data Exposed</div>
                                    <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                                        <span style="padding: 6px 12px; background: rgba(239,68,68,0.2); border-radius: 6px; font-size: 12px; color: #ff6b6b;">Feature plans</span>
                                        <span style="padding: 6px 12px; background: rgba(239,68,68,0.2); border-radius: 6px; font-size: 12px; color: #ff6b6b;">Release dates</span>
                                        <span style="padding: 6px 12px; background: rgba(239,68,68,0.2); border-radius: 6px; font-size: 12px; color: #ff6b6b;">Technology stack</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Leak Report 3 - BLOCKED -->
                        <div class="leak-report-item" style="padding: 25px; border-bottom: 2px solid var(--border);">
                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 20px;">
                                <div>
                                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                                        <span style="font-size: 24px;">🛡️</span>
                                        <h4 style="font-size: 18px; margin: 0;">LEAK #CLK-003: Enterprise Customer Database</h4>
                                        <span class="badge badge-critical">CRITICAL</span>
                                        <span class="badge" style="background: var(--success);">BLOCKED</span>
                                    </div>
                                    <div style="font-size: 13px; color: var(--text-muted);">
                                        Original Sender: <strong>sales@company.com</strong> (Sales Team) |
                                        Detected: <strong>January 26, 2025 11:20:15</strong> |
                                        Risk Score: <strong style="color: var(--danger);">95/100</strong>
                                    </div>
                                </div>
                                <div style="text-align: right;">
                                    <div style="font-size: 11px; color: var(--text-muted);">Attempted Destination</div>
                                    <div style="font-size: 14px; color: var(--success); font-weight: bold; text-decoration: line-through;">hr@marketcomp.io</div>
                                    <div style="font-size: 11px; color: var(--success);">✓ Successfully Blocked</div>
                                </div>
                            </div>

                            <!-- Email Trail -->
                            <div style="background: var(--bg-dark); border-radius: 12px; padding: 20px; margin-bottom: 20px;">
                                <div style="font-size: 13px; font-weight: bold; margin-bottom: 15px; color: var(--success);">🛡️ EMAIL TRAIL - BLOCKED BEFORE DELIVERY (2 Hops)</div>

                                <div style="display: flex; align-items: center; gap: 15px; overflow-x: auto; padding: 10px 0;">
                                    <!-- Hop 1 -->
                                    <div style="flex-shrink: 0; min-width: 220px; padding: 15px; background: rgba(16,185,129,0.1); border: 2px solid var(--success); border-radius: 8px;">
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                            <span style="width: 28px; height: 28px; background: var(--success); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 12px;">1</span>
                                            <span style="font-size: 10px; padding: 2px 6px; background: var(--success); color: white; border-radius: 4px;">INTERNAL</span>
                                        </div>
                                        <div style="font-size: 11px;"><strong>From:</strong> sales@company.com</div>
                                        <div style="font-size: 11px;"><strong>To:</strong> sales.manager@company.com</div>
                                        <div style="font-size: 10px; color: var(--text-muted); margin-top: 8px;">Jan 26, 9:00 AM</div>
                                        <div style="font-size: 10px; color: var(--success);">✓ Delivered</div>
                                    </div>

                                    <div style="font-size: 24px; color: var(--text-muted);">→</div>

                                    <!-- Hop 2 - BLOCKED -->
                                    <div style="flex-shrink: 0; min-width: 220px; padding: 15px; background: rgba(16,185,129,0.15); border: 3px solid var(--success); border-radius: 8px; position: relative;">
                                        <div style="position: absolute; top: -10px; right: -10px; background: var(--success); color: white; padding: 4px 8px; border-radius: 4px; font-size: 10px; font-weight: bold;">🛡️ BLOCKED</div>
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                            <span style="width: 28px; height: 28px; background: var(--success); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 12px;">2</span>
                                            <span style="font-size: 10px; padding: 2px 6px; background: var(--success); color: white; border-radius: 4px;">COMPETITOR - BLOCKED</span>
                                        </div>
                                        <div style="font-size: 11px;"><strong>From:</strong> sales.manager@company.com</div>
                                        <div style="font-size: 11px; text-decoration: line-through; color: var(--text-muted);"><strong>To:</strong> hr@marketcomp.io</div>
                                        <div style="font-size: 10px; color: var(--text-muted); margin-top: 8px;">Jan 26, 11:18 AM</div>
                                        <div style="font-size: 10px; color: var(--success); font-weight: bold;">✓ Blocked by DLP Policy</div>
                                    </div>
                                </div>
                            </div>

                            <div style="background: rgba(16,185,129,0.1); padding: 15px; border-radius: 8px; border: 1px solid var(--success);">
                                <strong style="color: var(--success);">✓ Prevention Success:</strong>
                                <span>Email containing Customer_Database.csv was blocked before reaching competitor. User has been warned and manager notified.</span>
                            </div>
                        </div>

                        <!-- Leak Report 4 -->
                        <div class="leak-report-item" style="padding: 25px;">
                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 20px;">
                                <div>
                                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                                        <span style="font-size: 24px;">🚨</span>
                                        <h4 style="font-size: 18px; margin: 0;">LEAK #CLK-004: API Integration Code</h4>
                                        <span class="badge badge-high">HIGH</span>
                                        <span class="badge" style="background: var(--danger);">CONFIRMED LEAK</span>
                                    </div>
                                    <div style="font-size: 13px; color: var(--text-muted);">
                                        Original Sender: <strong>dev.team@company.com</strong> (Engineering) |
                                        Detected: <strong>January 24, 2025 09:30:00</strong> |
                                        Risk Score: <strong style="color: var(--danger);">92/100</strong>
                                    </div>
                                </div>
                                <div style="text-align: right;">
                                    <div style="font-size: 11px; color: var(--text-muted);">Final Destination</div>
                                    <div style="font-size: 14px; color: var(--danger); font-weight: bold;">hiring@techrivals.net</div>
                                    <div style="font-size: 11px; color: var(--text-muted);">Tech Rivals LLC (Competitor)</div>
                                </div>
                            </div>

                            <!-- Email Trail -->
                            <div style="background: var(--bg-dark); border-radius: 12px; padding: 20px; margin-bottom: 20px;">
                                <div style="font-size: 13px; font-weight: bold; margin-bottom: 15px; color: var(--primary);">📬 COMPLETE EMAIL TRAIL (3 Hops) - Via Personal Email</div>

                                <div style="display: flex; align-items: center; gap: 15px; overflow-x: auto; padding: 10px 0;">
                                    <!-- Hop 1 -->
                                    <div style="flex-shrink: 0; min-width: 220px; padding: 15px; background: rgba(16,185,129,0.1); border: 2px solid var(--success); border-radius: 8px;">
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                            <span style="width: 28px; height: 28px; background: var(--success); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 12px;">1</span>
                                            <span style="font-size: 10px; padding: 2px 6px; background: var(--success); color: white; border-radius: 4px;">INTERNAL</span>
                                        </div>
                                        <div style="font-size: 11px;"><strong>From:</strong> dev.team@company.com</div>
                                        <div style="font-size: 11px;"><strong>To:</strong> qa.team@company.com</div>
                                        <div style="font-size: 10px; color: var(--text-muted); margin-top: 8px;">Jan 23, 3:00 PM</div>
                                        <div style="font-size: 10px; color: var(--success);">✓ Delivered</div>
                                    </div>

                                    <div style="font-size: 24px; color: var(--text-muted);">→</div>

                                    <!-- Hop 2 - Personal -->
                                    <div style="flex-shrink: 0; min-width: 220px; padding: 15px; background: rgba(139,92,246,0.1); border: 2px solid #8b5cf6; border-radius: 8px;">
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                            <span style="width: 28px; height: 28px; background: #8b5cf6; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 12px;">2</span>
                                            <span style="font-size: 10px; padding: 2px 6px; background: #8b5cf6; color: white; border-radius: 4px;">PERSONAL EMAIL</span>
                                        </div>
                                        <div style="font-size: 11px;"><strong>From:</strong> qa.analyst@company.com</div>
                                        <div style="font-size: 11px;"><strong>To:</strong> personal@gmail.com</div>
                                        <div style="font-size: 10px; color: var(--text-muted); margin-top: 8px;">Jan 23, 6:45 PM</div>
                                        <div style="font-size: 10px; color: #8b5cf6;">⚠️ Policy Violation</div>
                                    </div>

                                    <div style="font-size: 24px; color: var(--text-muted);">→</div>

                                    <!-- Hop 3 - Final -->
                                    <div style="flex-shrink: 0; min-width: 220px; padding: 15px; background: rgba(239,68,68,0.15); border: 2px solid var(--danger); border-radius: 8px;">
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                            <span style="width: 28px; height: 28px; background: var(--danger); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 12px;">3</span>
                                            <span style="font-size: 10px; padding: 2px 6px; background: var(--danger); color: white; border-radius: 4px;">🎯 COMPETITOR</span>
                                        </div>
                                        <div style="font-size: 11px;"><strong>From:</strong> personal@gmail.com</div>
                                        <div style="font-size: 11px; color: var(--danger);"><strong>To:</strong> hiring@techrivals.net</div>
                                        <div style="font-size: 10px; color: var(--text-muted); margin-top: 8px;">Jan 24, 9:28 AM</div>
                                        <div style="font-size: 10px; color: var(--danger);">🎯 Final Recipient</div>
                                    </div>
                                </div>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                <div style="background: var(--bg-lighter); padding: 15px; border-radius: 8px;">
                                    <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 10px;">📎 Attachments Leaked</div>
                                    <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                                        <span style="padding: 6px 12px; background: var(--bg-dark); border-radius: 6px; font-size: 12px;">📦 api_integration.zip</span>
                                        <span style="padding: 6px 12px; background: var(--bg-dark); border-radius: 6px; font-size: 12px;">🐍 auth_module.py</span>
                                    </div>
                                </div>
                                <div style="background: rgba(239,68,68,0.1); padding: 15px; border-radius: 8px; border: 1px solid var(--danger);">
                                    <div style="font-size: 12px; color: var(--danger); margin-bottom: 10px;">⚠️ Sensitive Data Exposed</div>
                                    <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                                        <span style="padding: 6px 12px; background: rgba(239,68,68,0.2); border-radius: 6px; font-size: 12px; color: #ff6b6b;">Authentication logic</span>
                                        <span style="padding: 6px 12px; background: rgba(239,68,68,0.2); border-radius: 6px; font-size: 12px; color: #ff6b6b;">API endpoints</span>
                                        <span style="padding: 6px 12px; background: rgba(239,68,68,0.2); border-radius: 6px; font-size: 12px; color: #ff6b6b;">Database schema</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Summary Table -->
                <div class="card" style="margin-top: 20px;">
                    <div class="card-header">
                        <h3>📊 Leak Summary Table</h3>
                    </div>
                    <div class="card-body" style="padding: 0;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Leak ID</th>
                                    <th>Subject</th>
                                    <th>Original Sender</th>
                                    <th>Final Recipient</th>
                                    <th>Hops</th>
                                    <th>Data Type</th>
                                    <th>Risk</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>CLK-001</strong></td>
                                    <td>Q4 Financial Report</td>
                                    <td>john.smith@company.com</td>
                                    <td style="color: var(--danger);">strategy@competitor.com</td>
                                    <td><span class="badge" style="background: var(--bg-lighter);">4</span></td>
                                    <td>Financial</td>
                                    <td><span style="color: var(--danger); font-weight: bold;">98</span></td>
                                    <td><span class="badge badge-critical">LEAKED</span></td>
                                </tr>
                                <tr>
                                    <td><strong>CLK-002</strong></td>
                                    <td>Product Roadmap 2025</td>
                                    <td>product@company.com</td>
                                    <td style="color: var(--danger);">jobs@rival-tech.com</td>
                                    <td><span class="badge" style="background: var(--bg-lighter);">3</span></td>
                                    <td>Strategic</td>
                                    <td><span style="color: var(--warning); font-weight: bold;">89</span></td>
                                    <td><span class="badge badge-high">INVESTIGATING</span></td>
                                </tr>
                                <tr>
                                    <td><strong>CLK-003</strong></td>
                                    <td>Customer Database</td>
                                    <td>sales@company.com</td>
                                    <td style="color: var(--success); text-decoration: line-through;">hr@marketcomp.io</td>
                                    <td><span class="badge" style="background: var(--bg-lighter);">2</span></td>
                                    <td>Customer PII</td>
                                    <td><span style="color: var(--danger); font-weight: bold;">95</span></td>
                                    <td><span class="badge" style="background: var(--success);">BLOCKED</span></td>
                                </tr>
                                <tr>
                                    <td><strong>CLK-004</strong></td>
                                    <td>API Integration Code</td>
                                    <td>dev.team@company.com</td>
                                    <td style="color: var(--danger);">hiring@techrivals.net</td>
                                    <td><span class="badge" style="background: var(--bg-lighter);">3</span></td>
                                    <td>Source Code</td>
                                    <td><span style="color: var(--danger); font-weight: bold;">92</span></td>
                                    <td><span class="badge badge-critical">LEAKED</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Report Footer -->
                <div style="margin-top: 20px; padding: 20px; background: var(--bg-card); border-radius: 8px; border: 1px solid var(--border);">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div style="font-size: 12px; color: var(--text-muted);">Report Classification: <strong style="color: var(--danger);">CONFIDENTIAL</strong></div>
                            <div style="font-size: 12px; color: var(--text-muted);">For authorized security personnel only</div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-size: 12px; color: var(--text-muted);">Generated by DLP System v2.5</div>
                            <div style="font-size: 12px; color: var(--text-muted);">NetworkScan SCADA Security Suite</div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Email Details Modal -->
    <div id="emailDetailsModal" class="dlp-modal">
        <div class="dlp-modal-overlay" onclick="closeModal('emailDetailsModal')"></div>
        <div class="dlp-modal-content">
            <div class="dlp-modal-header">
                <h3>Email Details</h3>
                <button class="dlp-modal-close" onclick="closeModal('emailDetailsModal')">&times;</button>
            </div>
            <div class="dlp-modal-body">
                <div style="display: grid; gap: 15px;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div>
                            <label style="font-size: 12px; color: var(--text-muted);">From</label>
                            <div id="emailDetailFrom" style="font-weight: 500;">-</div>
                        </div>
                        <div>
                            <label style="font-size: 12px; color: var(--text-muted);">To</label>
                            <div id="emailDetailTo" style="font-weight: 500;">-</div>
                        </div>
                    </div>
                    <div>
                        <label style="font-size: 12px; color: var(--text-muted);">Subject</label>
                        <div id="emailDetailSubject" style="font-weight: 500;">-</div>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px;">
                        <div>
                            <label style="font-size: 12px; color: var(--text-muted);">Risk Score</label>
                            <div id="emailDetailRisk" style="font-weight: 600; font-size: 24px; color: var(--danger);">-</div>
                        </div>
                        <div>
                            <label style="font-size: 12px; color: var(--text-muted);">Category</label>
                            <div id="emailDetailCategory">-</div>
                        </div>
                        <div>
                            <label style="font-size: 12px; color: var(--text-muted);">Attachments</label>
                            <div id="emailDetailAttachments">-</div>
                        </div>
                        <div>
                            <label style="font-size: 12px; color: var(--text-muted);">Size</label>
                            <div id="emailDetailSize">-</div>
                        </div>
                    </div>

                    <div>
                        <label style="font-size: 12px; color: var(--text-muted);">Detected Patterns</label>
                        <div id="emailDetailPatterns" style="padding: 15px; background: var(--bg-lighter); border-radius: 8px; margin-top: 5px;">
                            Loading...
                        </div>
                    </div>

                    <div>
                        <label style="font-size: 12px; color: var(--text-muted);">Agent Tracking Log</label>
                        <div id="emailDetailTracking" style="padding: 15px; background: var(--bg-lighter); border-radius: 8px; margin-top: 5px; max-height: 200px; overflow-y: auto;">
                            Loading...
                        </div>
                    </div>
                </div>
            </div>
            <div class="dlp-modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('emailDetailsModal')">Close</button>
                <button class="btn btn-success" onclick="releaseEmailFromModal()">Release</button>
                <button class="btn btn-danger" onclick="blockEmailFromModal()">Block</button>
            </div>
        </div>
    </div>

    <!-- Scan Email Modal -->
    <div id="scanEmailModal" class="dlp-modal">
        <div class="dlp-modal-overlay" onclick="closeModal('scanEmailModal')"></div>
        <div class="dlp-modal-content" style="max-width: 600px;">
            <div class="dlp-modal-header">
                <h3>Scan New Email</h3>
                <button class="dlp-modal-close" onclick="closeModal('scanEmailModal')">&times;</button>
            </div>
            <div class="dlp-modal-body">
                <form id="scanEmailForm">
                    <div style="display: grid; gap: 15px;">
                        <div>
                            <label style="font-size: 13px; font-weight: 500; margin-bottom: 5px; display: block;">Scan Type</label>
                            <select class="form-input" id="scanType" style="width: 100%;">
                                <option value="manual">Manual Email Entry</option>
                                <option value="mailbox">Scan Mailbox</option>
                                <option value="folder">Scan Mail Folder</option>
                                <option value="batch">Batch Scan (Multiple)</option>
                            </select>
                        </div>
                        <div id="manualEmailSection">
                            <label style="font-size: 13px; font-weight: 500; margin-bottom: 5px; display: block;">From Address</label>
                            <input type="email" class="form-input" id="scanFromEmail" placeholder="sender@example.com" style="width: 100%;">
                        </div>
                        <div>
                            <label style="font-size: 13px; font-weight: 500; margin-bottom: 5px; display: block;">To Address</label>
                            <input type="email" class="form-input" id="scanToEmail" placeholder="recipient@company.com" style="width: 100%;">
                        </div>
                        <div>
                            <label style="font-size: 13px; font-weight: 500; margin-bottom: 5px; display: block;">Subject</label>
                            <input type="text" class="form-input" id="scanSubject" placeholder="Email subject line" style="width: 100%;">
                        </div>
                        <div>
                            <label style="font-size: 13px; font-weight: 500; margin-bottom: 5px; display: block;">Email Body Content</label>
                            <textarea class="form-input" id="scanEmailBody" rows="5" placeholder="Paste email content here for DLP scanning..." style="width: 100%; resize: vertical;"></textarea>
                        </div>
                        <div>
                            <label style="font-size: 13px; font-weight: 500; margin-bottom: 5px; display: block;">Attachments</label>
                            <input type="file" class="form-input" id="scanAttachments" multiple style="width: 100%;">
                            <small style="color: var(--text-muted);">Select files to scan for sensitive data</small>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div>
                                <label style="font-size: 13px; font-weight: 500; margin-bottom: 5px; display: block;">Scan Priority</label>
                                <select class="form-input" id="scanPriority" style="width: 100%;">
                                    <option value="normal">Normal</option>
                                    <option value="high">High</option>
                                    <option value="critical">Critical</option>
                                </select>
                            </div>
                            <div>
                                <label style="font-size: 13px; font-weight: 500; margin-bottom: 5px; display: block;">Agent</label>
                                <select class="form-input" id="scanAgent" style="width: 100%;">
                                    <option value="auto">Auto-Select Best Agent</option>
                                    <option value="AGT-001">Financial Data Monitor</option>
                                    <option value="AGT-002">PII Protection Agent</option>
                                    <option value="AGT-003">Executive Communications</option>
                                    <option value="AGT-004">Attachment Scanner</option>
                                </select>
                            </div>
                        </div>
                        <div style="background: var(--bg-lighter); padding: 15px; border-radius: 8px;">
                            <label style="font-size: 13px; font-weight: 500; margin-bottom: 10px; display: block;">Scan Options</label>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                    <input type="checkbox" id="scanPII" checked> PII Detection
                                </label>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                    <input type="checkbox" id="scanFinancial" checked> Financial Data
                                </label>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                    <input type="checkbox" id="scanHealthcare"> Healthcare (HIPAA)
                                </label>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                    <input type="checkbox" id="scanSourceCode"> Source Code
                                </label>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                    <input type="checkbox" id="scanConfidential" checked> Confidential Markers
                                </label>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                    <input type="checkbox" id="scanMalware" checked> Malware Check
                                </label>
                            </div>
                        </div>
                    </div>
                </form>
                <div id="scanResultsSection" style="display: none; margin-top: 20px;">
                    <h4 style="margin-bottom: 15px; color: var(--primary);">Scan Results</h4>
                    <div id="scanResultsContent" style="background: var(--bg-lighter); padding: 15px; border-radius: 8px;"></div>
                </div>
            </div>
            <div class="dlp-modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('scanEmailModal')">Cancel</button>
                <button class="btn btn-primary" onclick="executeScanEmail()" id="scanEmailBtn">Start Scan</button>
            </div>
        </div>
    </div>

    <!-- PDF Report Modal -->
    <div id="pdfReportModal" class="dlp-modal">
        <div class="dlp-modal-overlay" onclick="closeModal('pdfReportModal')"></div>
        <div class="dlp-modal-content" style="max-width: 550px;">
            <div class="dlp-modal-header">
                <h3>Create PDF Report</h3>
                <button class="dlp-modal-close" onclick="closeModal('pdfReportModal')">&times;</button>
            </div>
            <div class="dlp-modal-body">
                <form id="pdfReportForm">
                    <div style="display: grid; gap: 15px;">
                        <div>
                            <label style="font-size: 13px; font-weight: 500; margin-bottom: 5px; display: block;">Report Title</label>
                            <input type="text" class="form-input" id="reportTitle" value="Email DLP Security Report" style="width: 100%;">
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div>
                                <label style="font-size: 13px; font-weight: 500; margin-bottom: 5px; display: block;">Date Range</label>
                                <select class="form-input" id="reportDateRange" style="width: 100%;">
                                    <option value="today">Today</option>
                                    <option value="week" selected>Last 7 Days</option>
                                    <option value="month">Last 30 Days</option>
                                    <option value="quarter">Last 90 Days</option>
                                    <option value="custom">Custom Range</option>
                                </select>
                            </div>
                            <div>
                                <label style="font-size: 13px; font-weight: 500; margin-bottom: 5px; display: block;">Report Format</label>
                                <select class="form-input" id="reportFormat" style="width: 100%;">
                                    <option value="detailed">Detailed Report</option>
                                    <option value="summary">Executive Summary</option>
                                    <option value="compliance">Compliance Report</option>
                                </select>
                            </div>
                        </div>
                        <div style="background: var(--bg-lighter); padding: 15px; border-radius: 8px;">
                            <label style="font-size: 13px; font-weight: 500; margin-bottom: 10px; display: block;">Include Sections</label>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                    <input type="checkbox" id="incOverview" checked> Overview Statistics
                                </label>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                    <input type="checkbox" id="incThreats" checked> Threat Analysis
                                </label>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                    <input type="checkbox" id="incIncidents" checked> Incident Details
                                </label>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                    <input type="checkbox" id="incAgents" checked> Agent Performance
                                </label>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                    <input type="checkbox" id="incPolicies"> Policy Violations
                                </label>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                    <input type="checkbox" id="incUsers"> User Activity
                                </label>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                    <input type="checkbox" id="incCharts" checked> Charts & Graphs
                                </label>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                    <input type="checkbox" id="incRecommendations" checked> Recommendations
                                </label>
                            </div>
                        </div>
                        <div>
                            <label style="font-size: 13px; font-weight: 500; margin-bottom: 5px; display: block;">Additional Notes</label>
                            <textarea class="form-input" id="reportNotes" rows="3" placeholder="Add any notes to include in the report..." style="width: 100%; resize: vertical;"></textarea>
                        </div>
                    </div>
                </form>
                <div id="pdfGenerationProgress" style="display: none; margin-top: 20px;">
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <div class="spinner" style="width: 24px; height: 24px; border: 3px solid var(--bg-lighter); border-top-color: var(--primary); border-radius: 50%; animation: spin 1s linear infinite;"></div>
                        <span id="pdfProgressText">Generating report...</span>
                    </div>
                    <div style="margin-top: 10px; background: var(--bg-lighter); border-radius: 8px; height: 8px; overflow: hidden;">
                        <div id="pdfProgressBar" style="height: 100%; background: var(--primary); width: 0%; transition: width 0.3s;"></div>
                    </div>
                </div>
            </div>
            <div class="dlp-modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('pdfReportModal')">Cancel</button>
                <button class="btn btn-primary" onclick="generatePDFReport()" id="generatePdfBtn">Generate PDF</button>
            </div>
        </div>
    </div>

    <!-- Create Agent Modal -->
    <div id="createAgentModal" class="dlp-modal">
        <div class="dlp-modal-overlay" onclick="closeModal('createAgentModal')"></div>
        <div class="dlp-modal-content">
            <div class="dlp-modal-header">
                <h3>Create New Monitoring Agent</h3>
                <button class="dlp-modal-close" onclick="closeModal('createAgentModal')">&times;</button>
            </div>
            <div class="dlp-modal-body">
                <form id="createAgentForm">
                    <div class="form-group">
                        <label>Agent Name</label>
                        <input type="text" id="newAgentName" class="form-input" placeholder="e.g., Healthcare Data Monitor" required>
                    </div>
                    <div class="form-group">
                        <label>Agent Type</label>
                        <select class="form-input">
                            <option value="pattern">Pattern Matching</option>
                            <option value="ml">Machine Learning</option>
                            <option value="keyword">Keyword Detection</option>
                            <option value="attachment">Attachment Scanner</option>
                            <option value="chain">Chain Tracker</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Target Categories</label>
                        <select class="form-input" multiple style="height: 100px;">
                            <option value="pii">PII Data</option>
                            <option value="financial">Financial Data</option>
                            <option value="healthcare">Healthcare (HIPAA)</option>
                            <option value="source">Source Code</option>
                            <option value="legal">Legal Documents</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Priority Level</label>
                        <select class="form-input">
                            <option value="high">High - Real-time processing</option>
                            <option value="medium" selected>Medium - Near real-time</option>
                            <option value="low">Low - Batch processing</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                            <input type="checkbox" checked style="width: auto;">
                            <span>Start agent immediately after creation</span>
                        </label>
                    </div>
                </form>
            </div>
            <div class="dlp-modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('createAgentModal')">Cancel</button>
                <button class="btn btn-primary" onclick="createAgent()">Create Agent</button>
            </div>
        </div>
    </div>

    <!-- Incident Investigation Modal -->
    <div id="incidentModal" class="dlp-modal">
        <div class="dlp-modal-overlay" onclick="closeModal('incidentModal')"></div>
        <div class="dlp-modal-content" style="max-width: 900px;">
            <div class="dlp-modal-header">
                <h3>🔍 Incident Investigation - <span id="incidentIdHeader">INC-001</span></h3>
                <button class="dlp-modal-close" onclick="closeModal('incidentModal')">&times;</button>
            </div>
            <div class="dlp-modal-body">
                <div id="incidentAlertBox" class="info-box danger">
                    <strong id="incidentAlertTitle">CRITICAL DATA LEAK DETECTED</strong>
                    <p style="margin-top: 5px;" id="incidentAlertDesc">Confidential financial data has been forwarded to an unauthorized external recipient.</p>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0;">
                    <div>
                        <h4 style="margin-bottom: 15px;">Incident Details</h4>
                        <div style="background: var(--bg-lighter); padding: 15px; border-radius: 8px;">
                            <div style="margin-bottom: 10px;"><strong>Incident ID:</strong> <span id="incidentId">INC-001</span></div>
                            <div style="margin-bottom: 10px;"><strong>Type:</strong> <span id="incidentType">External Forward</span></div>
                            <div style="margin-bottom: 10px;"><strong>Severity:</strong> <span id="incidentSeverityBadge" class="badge badge-critical">CRITICAL</span></div>
                            <div style="margin-bottom: 10px;"><strong>Detected:</strong> <span id="incidentDetected">2025-01-26 14:30:05</span></div>
                            <div style="margin-bottom: 10px;"><strong>User:</strong> <span id="incidentUser">john.smith@company.com</span></div>
                            <div><strong>Status:</strong> <span id="incidentStatusBadge" class="badge badge-high">Investigating</span></div>
                        </div>
                    </div>
                    <div>
                        <h4 style="margin-bottom: 15px;">Actions Taken</h4>
                        <div id="incidentActionsList" style="background: var(--bg-lighter); padding: 15px; border-radius: 8px;">
                            <div style="display: flex; gap: 10px; align-items: center; margin-bottom: 10px;">
                                <span style="color: var(--success);">✓</span> Email blocked
                            </div>
                            <div style="display: flex; gap: 10px; align-items: center; margin-bottom: 10px;">
                                <span style="color: var(--success);">✓</span> Sender notified
                            </div>
                            <div style="display: flex; gap: 10px; align-items: center; margin-bottom: 10px;">
                                <span style="color: var(--success);">✓</span> Security team alerted
                            </div>
                            <div id="managerNotifyStatus" style="display: flex; gap: 10px; align-items: center;">
                                <span style="color: var(--warning);">○</span> Manager notification pending
                            </div>
                        </div>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div>
                        <h4 style="margin-bottom: 15px;">Email Information</h4>
                        <div style="background: var(--bg-lighter); padding: 15px; border-radius: 8px;">
                            <div style="margin-bottom: 10px;"><strong>Subject:</strong> <span id="incidentSubject">Q4 Financial Report</span></div>
                            <div style="margin-bottom: 10px;"><strong>From:</strong> <span id="incidentFrom">john.smith@company.com</span></div>
                            <div style="margin-bottom: 10px;"><strong>To:</strong> <span id="incidentTo" style="color: var(--danger);">external@competitor.com</span></div>
                            <div><strong>Attachments:</strong> <span id="incidentAttachments">2 files</span></div>
                        </div>
                    </div>
                    <div>
                        <h4 style="margin-bottom: 15px;">User Information</h4>
                        <div style="background: var(--bg-lighter); padding: 15px; border-radius: 8px;">
                            <div style="margin-bottom: 10px;"><strong>Name:</strong> <span id="incidentUserName">John Smith</span></div>
                            <div style="margin-bottom: 10px;"><strong>Department:</strong> <span id="incidentDepartment">Finance</span></div>
                            <div style="margin-bottom: 10px;"><strong>Manager:</strong> <span id="incidentManager">Michael Johnson</span></div>
                            <div><strong>Previous Incidents:</strong> <span id="incidentPrevious" style="color: var(--warning);">3</span></div>
                        </div>
                    </div>
                </div>

                <h4 style="margin-bottom: 15px;">Investigation Notes</h4>
                <textarea id="incidentNotes" class="form-input" rows="4" placeholder="Add investigation notes..."></textarea>

                <h4 style="margin: 20px 0 15px;">Resolution Actions</h4>
                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px;">
                    <button id="btnNotifyManager" class="btn btn-secondary" onclick="notifyManager()" style="display: flex; flex-direction: column; align-items: center; padding: 15px;">
                        <span style="font-size: 24px; margin-bottom: 5px;">📧</span>
                        <span>Notify Manager</span>
                    </button>
                    <button id="btnSuspendUser" class="btn btn-secondary" onclick="suspendUser()" style="display: flex; flex-direction: column; align-items: center; padding: 15px;">
                        <span style="font-size: 24px; margin-bottom: 5px;">🚫</span>
                        <span>Suspend User</span>
                    </button>
                    <button id="btnRevokeAccess" class="btn btn-secondary" onclick="revokeAccess()" style="display: flex; flex-direction: column; align-items: center; padding: 15px;">
                        <span style="font-size: 24px; margin-bottom: 5px;">🔒</span>
                        <span>Revoke Access</span>
                    </button>
                    <button id="btnGenerateReport" class="btn btn-secondary" onclick="generateIncidentReport()" style="display: flex; flex-direction: column; align-items: center; padding: 15px;">
                        <span style="font-size: 24px; margin-bottom: 5px;">📋</span>
                        <span>Generate Report</span>
                    </button>
                </div>
            </div>
            <div class="dlp-modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('incidentModal')">Close</button>
                <button class="btn btn-warning" onclick="escalateIncident()">⬆️ Escalate</button>
                <button class="btn btn-success" onclick="resolveIncident()">✓ Mark Resolved</button>
            </div>
        </div>
    </div>

    <!-- Notify Manager Modal -->
    <div id="notifyManagerModal" class="dlp-modal">
        <div class="dlp-modal-overlay" onclick="closeModal('notifyManagerModal')"></div>
        <div class="dlp-modal-content" style="max-width: 600px;">
            <div class="dlp-modal-header">
                <h3>📧 Notify Manager</h3>
                <button class="dlp-modal-close" onclick="closeModal('notifyManagerModal')">&times;</button>
            </div>
            <div class="dlp-modal-body">
                <div class="form-group">
                    <label>Manager to Notify</label>
                    <select id="managerSelect" class="form-input">
                        <option value="michael.johnson@company.com">Michael Johnson (Direct Manager)</option>
                        <option value="sarah.williams@company.com">Sarah Williams (Department Head)</option>
                        <option value="david.chen@company.com">David Chen (VP Finance)</option>
                        <option value="ciso@company.com">CISO Office</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Notification Priority</label>
                    <select id="notifyPriority" class="form-input">
                        <option value="urgent">🔴 Urgent - Immediate attention required</option>
                        <option value="high">🟠 High - Same day response</option>
                        <option value="normal">🟡 Normal - Within 24 hours</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Additional Recipients (CC)</label>
                    <input type="text" id="notifyCc" class="form-input" placeholder="email1@company.com, email2@company.com">
                </div>

                <div class="form-group">
                    <label>Message</label>
                    <textarea id="notifyMessage" class="form-input" rows="5">Dear Manager,

A data leak incident has been detected involving an employee in your department.

Incident ID: INC-001
Employee: john.smith@company.com
Severity: CRITICAL
Type: External Forward to Competitor

Please review this incident and take appropriate action.

Regards,
DLP Security Team</textarea>
                </div>

                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" id="notifyIncludeDetails" checked> Include full incident details in notification
                    </label>
                </div>
            </div>
            <div class="dlp-modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('notifyManagerModal')">Cancel</button>
                <button class="btn btn-primary" onclick="sendManagerNotification()">📤 Send Notification</button>
            </div>
        </div>
    </div>

    <!-- Suspend User Modal -->
    <div id="suspendUserModal" class="dlp-modal">
        <div class="dlp-modal-overlay" onclick="closeModal('suspendUserModal')"></div>
        <div class="dlp-modal-content" style="max-width: 600px;">
            <div class="dlp-modal-header">
                <h3>🚫 Suspend User Account</h3>
                <button class="dlp-modal-close" onclick="closeModal('suspendUserModal')">&times;</button>
            </div>
            <div class="dlp-modal-body">
                <div class="info-box warning">
                    <strong>⚠️ Warning:</strong> This action will immediately suspend the user's account and prevent access to all company systems.
                </div>

                <div style="background: var(--bg-lighter); padding: 20px; border-radius: 8px; margin: 20px 0;">
                    <h4 style="margin-bottom: 15px;">User Account Details</h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div>
                            <div style="font-size: 12px; color: var(--text-muted);">Username</div>
                            <div id="suspendUsername" style="font-weight: bold;">john.smith</div>
                        </div>
                        <div>
                            <div style="font-size: 12px; color: var(--text-muted);">Email</div>
                            <div id="suspendEmail">john.smith@company.com</div>
                        </div>
                        <div>
                            <div style="font-size: 12px; color: var(--text-muted);">Department</div>
                            <div id="suspendDepartment">Finance</div>
                        </div>
                        <div>
                            <div style="font-size: 12px; color: var(--text-muted);">Employee ID</div>
                            <div id="suspendEmployeeId">EMP-1234</div>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Suspension Duration</label>
                    <select id="suspendDuration" class="form-input">
                        <option value="24h">24 Hours - Temporary suspension</option>
                        <option value="72h">72 Hours - Short-term suspension</option>
                        <option value="1week">1 Week - Extended suspension</option>
                        <option value="indefinite" selected>Indefinite - Until further review</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Suspension Reason</label>
                    <select id="suspendReason" class="form-input">
                        <option value="data_leak">Data Leak Violation</option>
                        <option value="policy_violation">Policy Violation</option>
                        <option value="security_threat">Security Threat</option>
                        <option value="investigation">Pending Investigation</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Additional Notes</label>
                    <textarea id="suspendNotes" class="form-input" rows="3" placeholder="Document the reason for suspension..."></textarea>
                </div>

                <div class="form-group">
                    <label>Systems to Suspend Access:</label>
                    <div style="display: flex; flex-wrap: wrap; gap: 15px; margin-top: 10px;">
                        <label style="display: flex; align-items: center; gap: 5px; cursor: pointer;">
                            <input type="checkbox" checked> Email
                        </label>
                        <label style="display: flex; align-items: center; gap: 5px; cursor: pointer;">
                            <input type="checkbox" checked> VPN
                        </label>
                        <label style="display: flex; align-items: center; gap: 5px; cursor: pointer;">
                            <input type="checkbox" checked> Active Directory
                        </label>
                        <label style="display: flex; align-items: center; gap: 5px; cursor: pointer;">
                            <input type="checkbox" checked> Cloud Services
                        </label>
                        <label style="display: flex; align-items: center; gap: 5px; cursor: pointer;">
                            <input type="checkbox" checked> Building Access
                        </label>
                    </div>
                </div>
            </div>
            <div class="dlp-modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('suspendUserModal')">Cancel</button>
                <button class="btn btn-danger" onclick="confirmSuspendUser()">🚫 Suspend User Account</button>
            </div>
        </div>
    </div>

    <!-- Revoke Access Modal -->
    <div id="revokeAccessModal" class="dlp-modal">
        <div class="dlp-modal-overlay" onclick="closeModal('revokeAccessModal')"></div>
        <div class="dlp-modal-content" style="max-width: 600px;">
            <div class="dlp-modal-header">
                <h3>🔒 Revoke User Access</h3>
                <button class="dlp-modal-close" onclick="closeModal('revokeAccessModal')">&times;</button>
            </div>
            <div class="dlp-modal-body">
                <div class="info-box info">
                    <strong>ℹ️ Selective Access Revocation:</strong> Choose which specific access rights to revoke for this user.
                </div>

                <div style="background: var(--bg-lighter); padding: 15px; border-radius: 8px; margin: 20px 0;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <strong id="revokeUserName">John Smith</strong>
                            <div style="font-size: 12px; color: var(--text-muted);" id="revokeUserEmail">john.smith@company.com</div>
                        </div>
                        <span class="badge badge-high">Active User</span>
                    </div>
                </div>

                <h4 style="margin-bottom: 15px;">Select Access to Revoke:</h4>

                <div style="display: grid; gap: 12px;">
                    <label style="display: flex; align-items: center; gap: 12px; padding: 15px; background: var(--bg-lighter); border-radius: 8px; cursor: pointer; border: 2px solid transparent;" class="revoke-option">
                        <input type="checkbox" id="revokeEmail" checked>
                        <div style="flex: 1;">
                            <strong>📧 External Email</strong>
                            <div style="font-size: 12px; color: var(--text-muted);">Prevent sending emails to external addresses</div>
                        </div>
                    </label>

                    <label style="display: flex; align-items: center; gap: 12px; padding: 15px; background: var(--bg-lighter); border-radius: 8px; cursor: pointer; border: 2px solid transparent;" class="revoke-option">
                        <input type="checkbox" id="revokeAttachments" checked>
                        <div style="flex: 1;">
                            <strong>📎 Attachment Sending</strong>
                            <div style="font-size: 12px; color: var(--text-muted);">Block ability to send email attachments</div>
                        </div>
                    </label>

                    <label style="display: flex; align-items: center; gap: 12px; padding: 15px; background: var(--bg-lighter); border-radius: 8px; cursor: pointer; border: 2px solid transparent;" class="revoke-option">
                        <input type="checkbox" id="revokeCloudStorage">
                        <div style="flex: 1;">
                            <strong>☁️ Cloud Storage Sharing</strong>
                            <div style="font-size: 12px; color: var(--text-muted);">Disable external sharing on OneDrive, SharePoint, etc.</div>
                        </div>
                    </label>

                    <label style="display: flex; align-items: center; gap: 12px; padding: 15px; background: var(--bg-lighter); border-radius: 8px; cursor: pointer; border: 2px solid transparent;" class="revoke-option">
                        <input type="checkbox" id="revokeUsb">
                        <div style="flex: 1;">
                            <strong>💾 USB/Removable Media</strong>
                            <div style="font-size: 12px; color: var(--text-muted);">Block access to USB drives and removable storage</div>
                        </div>
                    </label>

                    <label style="display: flex; align-items: center; gap: 12px; padding: 15px; background: var(--bg-lighter); border-radius: 8px; cursor: pointer; border: 2px solid transparent;" class="revoke-option">
                        <input type="checkbox" id="revokePrinting">
                        <div style="flex: 1;">
                            <strong>🖨️ Printing</strong>
                            <div style="font-size: 12px; color: var(--text-muted);">Disable printing capabilities</div>
                        </div>
                    </label>

                    <label style="display: flex; align-items: center; gap: 12px; padding: 15px; background: var(--bg-lighter); border-radius: 8px; cursor: pointer; border: 2px solid transparent;" class="revoke-option">
                        <input type="checkbox" id="revokeDownload">
                        <div style="flex: 1;">
                            <strong>⬇️ File Downloads</strong>
                            <div style="font-size: 12px; color: var(--text-muted);">Prevent downloading sensitive files</div>
                        </div>
                    </label>
                </div>

                <div class="form-group" style="margin-top: 20px;">
                    <label>Revocation Duration</label>
                    <select id="revokeDuration" class="form-input">
                        <option value="24h">24 Hours</option>
                        <option value="1week">1 Week</option>
                        <option value="1month">1 Month</option>
                        <option value="permanent" selected>Permanent (until manually restored)</option>
                    </select>
                </div>
            </div>
            <div class="dlp-modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('revokeAccessModal')">Cancel</button>
                <button class="btn btn-warning" onclick="confirmRevokeAccess()">🔒 Revoke Selected Access</button>
            </div>
        </div>
    </div>

    <!-- Create Policy Modal -->
    <div id="createPolicyModal" class="dlp-modal">
        <div class="dlp-modal-overlay" onclick="closeModal('createPolicyModal')"></div>
        <div class="dlp-modal-content" style="max-width: 700px;">
            <div class="dlp-modal-header">
                <h3>Create New DLP Policy</h3>
                <button class="dlp-modal-close" onclick="closeModal('createPolicyModal')">&times;</button>
            </div>
            <div class="dlp-modal-body">
                <form id="createPolicyForm">
                    <div class="form-group">
                        <label>Policy Name *</label>
                        <input type="text" id="newPolicyName" class="form-input" placeholder="Enter policy name" required>
                    </div>

                    <div class="form-group">
                        <label>Description *</label>
                        <textarea id="newPolicyDescription" class="form-input" rows="3" placeholder="Describe what this policy does..." required></textarea>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label>Action *</label>
                            <select id="newPolicyAction" class="form-input" required>
                                <option value="">Select action...</option>
                                <option value="block">Block - Prevent email delivery</option>
                                <option value="quarantine">Quarantine - Hold for review</option>
                                <option value="flag">Flag - Mark for attention</option>
                                <option value="log">Log - Record activity only</option>
                                <option value="encrypt">Encrypt - Force encryption</option>
                                <option value="notify">Notify - Alert admin only</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Severity *</label>
                            <select id="newPolicySeverity" class="form-input" required>
                                <option value="">Select severity...</option>
                                <option value="critical">Critical</option>
                                <option value="high">High</option>
                                <option value="medium">Medium</option>
                                <option value="low">Low</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Apply To</label>
                        <div style="display: flex; gap: 15px; flex-wrap: wrap; margin-top: 10px;">
                            <label style="display: flex; align-items: center; gap: 5px; cursor: pointer;">
                                <input type="checkbox" id="applyOutbound" checked> Outbound Emails
                            </label>
                            <label style="display: flex; align-items: center; gap: 5px; cursor: pointer;">
                                <input type="checkbox" id="applyInbound"> Inbound Emails
                            </label>
                            <label style="display: flex; align-items: center; gap: 5px; cursor: pointer;">
                                <input type="checkbox" id="applyInternal"> Internal Emails
                            </label>
                            <label style="display: flex; align-items: center; gap: 5px; cursor: pointer;">
                                <input type="checkbox" id="applyAttachments" checked> Attachments
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Detection Rules</label>
                        <div id="policyRulesSelection" style="background: var(--bg-lighter); padding: 15px; border-radius: 8px; max-height: 150px; overflow-y: auto;">
                            <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px; cursor: pointer;">
                                <input type="checkbox" name="policyRule" value="ssn"> SSN Pattern Detection
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px; cursor: pointer;">
                                <input type="checkbox" name="policyRule" value="creditcard"> Credit Card Numbers
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px; cursor: pointer;">
                                <input type="checkbox" name="policyRule" value="financial"> Financial Keywords
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px; cursor: pointer;">
                                <input type="checkbox" name="policyRule" value="sourcecode"> Source Code Patterns
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px; cursor: pointer;">
                                <input type="checkbox" name="policyRule" value="confidential"> Confidential Markers
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="checkbox" name="policyRule" value="pii"> PII Data Patterns
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Exceptions (Optional)</label>
                        <textarea id="newPolicyExceptions" class="form-input" rows="2" placeholder="e.g., Exclude legal@company.com, finance-reports@company.com"></textarea>
                    </div>

                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="checkbox" id="newPolicyActive" checked> Enable policy immediately
                        </label>
                    </div>
                </form>
            </div>
            <div class="dlp-modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('createPolicyModal')">Cancel</button>
                <button class="btn btn-primary" onclick="saveNewPolicy()">Create Policy</button>
            </div>
        </div>
    </div>

    <!-- Edit Policy Modal -->
    <div id="editPolicyModal" class="dlp-modal">
        <div class="dlp-modal-overlay" onclick="closeModal('editPolicyModal')"></div>
        <div class="dlp-modal-content" style="max-width: 700px;">
            <div class="dlp-modal-header">
                <h3>Edit Policy: <span id="editPolicyTitle"></span></h3>
                <button class="dlp-modal-close" onclick="closeModal('editPolicyModal')">&times;</button>
            </div>
            <div class="dlp-modal-body">
                <input type="hidden" id="editPolicyId">

                <div class="form-group">
                    <label>Policy Name *</label>
                    <input type="text" id="editPolicyName" class="form-input" required>
                </div>

                <div class="form-group">
                    <label>Description *</label>
                    <textarea id="editPolicyDescription" class="form-input" rows="3" required></textarea>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>Action *</label>
                        <select id="editPolicyAction" class="form-input" required>
                            <option value="block">Block - Prevent email delivery</option>
                            <option value="quarantine">Quarantine - Hold for review</option>
                            <option value="flag">Flag - Mark for attention</option>
                            <option value="log">Log - Record activity only</option>
                            <option value="encrypt">Encrypt - Force encryption</option>
                            <option value="notify">Notify - Alert admin only</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Severity *</label>
                        <select id="editPolicySeverity" class="form-input" required>
                            <option value="critical">Critical</option>
                            <option value="high">High</option>
                            <option value="medium">Medium</option>
                            <option value="low">Low</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Apply To</label>
                    <div style="display: flex; gap: 15px; flex-wrap: wrap; margin-top: 10px;">
                        <label style="display: flex; align-items: center; gap: 5px; cursor: pointer;">
                            <input type="checkbox" id="editApplyOutbound"> Outbound Emails
                        </label>
                        <label style="display: flex; align-items: center; gap: 5px; cursor: pointer;">
                            <input type="checkbox" id="editApplyInbound"> Inbound Emails
                        </label>
                        <label style="display: flex; align-items: center; gap: 5px; cursor: pointer;">
                            <input type="checkbox" id="editApplyInternal"> Internal Emails
                        </label>
                        <label style="display: flex; align-items: center; gap: 5px; cursor: pointer;">
                            <input type="checkbox" id="editApplyAttachments"> Attachments
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label>Detection Rules</label>
                    <div id="editPolicyRulesSelection" style="background: var(--bg-lighter); padding: 15px; border-radius: 8px; max-height: 150px; overflow-y: auto;">
                        <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px; cursor: pointer;">
                            <input type="checkbox" name="editPolicyRule" value="ssn"> SSN Pattern Detection
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px; cursor: pointer;">
                            <input type="checkbox" name="editPolicyRule" value="creditcard"> Credit Card Numbers
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px; cursor: pointer;">
                            <input type="checkbox" name="editPolicyRule" value="financial"> Financial Keywords
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px; cursor: pointer;">
                            <input type="checkbox" name="editPolicyRule" value="sourcecode"> Source Code Patterns
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px; cursor: pointer;">
                            <input type="checkbox" name="editPolicyRule" value="confidential"> Confidential Markers
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="checkbox" name="editPolicyRule" value="pii"> PII Data Patterns
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label>Exceptions</label>
                    <textarea id="editPolicyExceptions" class="form-input" rows="2" placeholder="e.g., Exclude legal@company.com"></textarea>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; padding: 15px; background: var(--bg-lighter); border-radius: 8px; margin-top: 15px;">
                    <div>
                        <strong>Policy Status</strong>
                        <p style="font-size: 12px; color: var(--text-muted);">Enable or disable this policy</p>
                    </div>
                    <label class="toggle-switch" style="position: relative; display: inline-block; width: 50px; height: 26px;">
                        <input type="checkbox" id="editPolicyStatus" style="opacity: 0; width: 0; height: 0;">
                        <span class="toggle-slider" style="position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #475569; border-radius: 26px; transition: 0.3s;"></span>
                    </label>
                </div>

                <div style="margin-top: 20px; padding: 15px; background: var(--bg-lighter); border-radius: 8px;">
                    <h4 style="margin-bottom: 10px;">Policy Statistics</h4>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; text-align: center;">
                        <div>
                            <div style="font-size: 24px; font-weight: bold; color: var(--primary);" id="editPolicyMatches">0</div>
                            <div style="font-size: 12px; color: var(--text-muted);">Total Matches</div>
                        </div>
                        <div>
                            <div style="font-size: 24px; font-weight: bold; color: var(--danger);" id="editPolicyBlocked">0</div>
                            <div style="font-size: 12px; color: var(--text-muted);">Emails Blocked</div>
                        </div>
                        <div>
                            <div style="font-size: 24px; font-weight: bold; color: var(--warning);" id="editPolicyFalsePositives">0</div>
                            <div style="font-size: 12px; color: var(--text-muted);">False Positives</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="dlp-modal-footer">
                <button class="btn btn-danger" onclick="deletePolicy()" style="margin-right: auto;">Delete Policy</button>
                <button class="btn btn-secondary" onclick="closeModal('editPolicyModal')">Cancel</button>
                <button class="btn btn-primary" onclick="saveEditedPolicy()">Save Changes</button>
            </div>
        </div>
    </div>

    <!-- Configure Agent Modal -->
    <div id="configureAgentModal" class="dlp-modal">
        <div class="dlp-modal-overlay" onclick="closeModal('configureAgentModal')"></div>
        <div class="dlp-modal-content" style="max-width: 700px;">
            <div class="dlp-modal-header">
                <h3>Configure Agent: <span id="configAgentName"></span></h3>
                <button class="dlp-modal-close" onclick="closeModal('configureAgentModal')">&times;</button>
            </div>
            <div class="dlp-modal-body">
                <input type="hidden" id="configAgentId">

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div style="background: var(--bg-lighter); padding: 15px; border-radius: 8px; text-align: center;">
                        <div style="font-size: 24px; font-weight: bold; color: var(--primary);" id="configEmailsScanned">0</div>
                        <div style="font-size: 12px; color: var(--text-muted);">Emails Scanned</div>
                    </div>
                    <div style="background: var(--bg-lighter); padding: 15px; border-radius: 8px; text-align: center;">
                        <div style="font-size: 24px; font-weight: bold; color: var(--danger);" id="configThreatsDetected">0</div>
                        <div style="font-size: 12px; color: var(--text-muted);">Threats Detected</div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Agent Name</label>
                    <input type="text" id="configAgentNameInput" class="form-input">
                </div>

                <div class="form-group">
                    <label>Scan Priority</label>
                    <select id="configScanPriority" class="form-input">
                        <option value="low">Low - Background scanning</option>
                        <option value="normal">Normal - Standard priority</option>
                        <option value="high">High - Priority scanning</option>
                        <option value="critical">Critical - Real-time scanning</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Resource Limits</label>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div>
                            <label style="font-size: 12px; color: var(--text-muted);">Max CPU Usage (%)</label>
                            <input type="number" id="configMaxCpu" class="form-input" min="5" max="100" value="25">
                        </div>
                        <div>
                            <label style="font-size: 12px; color: var(--text-muted);">Max Memory (MB)</label>
                            <input type="number" id="configMaxMemory" class="form-input" min="64" max="2048" value="512">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Scan Targets</label>
                    <div style="display: flex; gap: 15px; flex-wrap: wrap; margin-top: 10px;">
                        <label style="display: flex; align-items: center; gap: 5px; cursor: pointer;">
                            <input type="checkbox" id="configScanInbound" checked> Inbound Emails
                        </label>
                        <label style="display: flex; align-items: center; gap: 5px; cursor: pointer;">
                            <input type="checkbox" id="configScanOutbound" checked> Outbound Emails
                        </label>
                        <label style="display: flex; align-items: center; gap: 5px; cursor: pointer;">
                            <input type="checkbox" id="configScanInternal"> Internal Emails
                        </label>
                        <label style="display: flex; align-items: center; gap: 5px; cursor: pointer;">
                            <input type="checkbox" id="configScanAttachments" checked> Attachments
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label>Detection Rules</label>
                    <div style="background: var(--bg-lighter); padding: 15px; border-radius: 8px; max-height: 150px; overflow-y: auto;">
                        <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px; cursor: pointer;">
                            <input type="checkbox" name="configRule" value="pii" checked> PII Detection (SSN, DOB, etc.)
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px; cursor: pointer;">
                            <input type="checkbox" name="configRule" value="financial" checked> Financial Data
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px; cursor: pointer;">
                            <input type="checkbox" name="configRule" value="creditcard"> Credit Card Numbers
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px; cursor: pointer;">
                            <input type="checkbox" name="configRule" value="sourcecode"> Source Code Patterns
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px; cursor: pointer;">
                            <input type="checkbox" name="configRule" value="healthcare"> Healthcare Data (HIPAA)
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="checkbox" name="configRule" value="confidential"> Confidential Markers
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label>Notification Settings</label>
                    <div style="display: flex; gap: 15px; flex-wrap: wrap; margin-top: 10px;">
                        <label style="display: flex; align-items: center; gap: 5px; cursor: pointer;">
                            <input type="checkbox" id="configNotifyEmail" checked> Email Alerts
                        </label>
                        <label style="display: flex; align-items: center; gap: 5px; cursor: pointer;">
                            <input type="checkbox" id="configNotifySlack"> Slack Notifications
                        </label>
                        <label style="display: flex; align-items: center; gap: 5px; cursor: pointer;">
                            <input type="checkbox" id="configNotifySms"> SMS Alerts
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label>Alert Threshold</label>
                    <select id="configAlertThreshold" class="form-input">
                        <option value="all">All detections</option>
                        <option value="medium">Medium severity and above</option>
                        <option value="high">High severity and above</option>
                        <option value="critical">Critical only</option>
                    </select>
                </div>
            </div>
            <div class="dlp-modal-footer">
                <button class="btn btn-danger" onclick="deleteAgent()" style="margin-right: auto;">Delete Agent</button>
                <button class="btn btn-secondary" onclick="closeModal('configureAgentModal')">Cancel</button>
                <button class="btn btn-primary" onclick="saveAgentConfig()">Save Configuration</button>
            </div>
        </div>
    </div>

    <!-- Agent Logs Modal -->
    <div id="agentLogsModal" class="dlp-modal">
        <div class="dlp-modal-overlay" onclick="closeModal('agentLogsModal')"></div>
        <div class="dlp-modal-content" style="max-width: 900px;">
            <div class="dlp-modal-header">
                <h3>Agent Logs: <span id="logsAgentName"></span></h3>
                <button class="dlp-modal-close" onclick="closeModal('agentLogsModal')">&times;</button>
            </div>
            <div class="dlp-modal-body">
                <div style="display: flex; gap: 10px; margin-bottom: 20px;">
                    <select id="logsFilter" class="form-input" style="width: 200px;" onchange="filterAgentLogs()">
                        <option value="all">All Events</option>
                        <option value="scan">Scans</option>
                        <option value="threat">Threats</option>
                        <option value="block">Blocks</option>
                        <option value="error">Errors</option>
                        <option value="system">System</option>
                    </select>
                    <select id="logsTimeRange" class="form-input" style="width: 200px;" onchange="filterAgentLogs()">
                        <option value="1h">Last Hour</option>
                        <option value="24h" selected>Last 24 Hours</option>
                        <option value="7d">Last 7 Days</option>
                        <option value="30d">Last 30 Days</option>
                    </select>
                    <button class="btn btn-secondary" onclick="refreshAgentLogs()">🔄 Refresh</button>
                    <button class="btn btn-secondary" onclick="exportAgentLogs()">📥 Export</button>
                </div>

                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 20px;">
                    <div style="background: var(--bg-lighter); padding: 12px; border-radius: 8px; text-align: center;">
                        <div style="font-size: 20px; font-weight: bold;" id="logsTotalEvents">0</div>
                        <div style="font-size: 11px; color: var(--text-muted);">Total Events</div>
                    </div>
                    <div style="background: var(--bg-lighter); padding: 12px; border-radius: 8px; text-align: center;">
                        <div style="font-size: 20px; font-weight: bold; color: var(--danger);" id="logsThreatEvents">0</div>
                        <div style="font-size: 11px; color: var(--text-muted);">Threats</div>
                    </div>
                    <div style="background: var(--bg-lighter); padding: 12px; border-radius: 8px; text-align: center;">
                        <div style="font-size: 20px; font-weight: bold; color: var(--warning);" id="logsBlockEvents">0</div>
                        <div style="font-size: 11px; color: var(--text-muted);">Blocks</div>
                    </div>
                    <div style="background: var(--bg-lighter); padding: 12px; border-radius: 8px; text-align: center;">
                        <div style="font-size: 20px; font-weight: bold; color: var(--danger);" id="logsErrorEvents">0</div>
                        <div style="font-size: 11px; color: var(--text-muted);">Errors</div>
                    </div>
                </div>

                <div id="agentLogsContainer" style="background: var(--bg-lighter); border-radius: 8px; max-height: 400px; overflow-y: auto;">
                    <table class="data-table" style="margin: 0;">
                        <thead>
                            <tr>
                                <th style="width: 160px;">Timestamp</th>
                                <th style="width: 100px;">Type</th>
                                <th>Message</th>
                                <th style="width: 120px;">Details</th>
                            </tr>
                        </thead>
                        <tbody id="agentLogsBody">
                            <!-- Logs will be populated dynamically -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="dlp-modal-footer">
                <button class="btn btn-secondary" onclick="clearAgentLogs()">Clear Logs</button>
                <button class="btn btn-secondary" onclick="closeModal('agentLogsModal')">Close</button>
            </div>
        </div>
    </div>

    <!-- User Profile Modal -->
    <div id="userProfileModal" class="dlp-modal">
        <div class="dlp-modal-overlay" onclick="closeModal('userProfileModal')"></div>
        <div class="dlp-modal-content" style="max-width: 900px;">
            <div class="dlp-modal-header">
                <h3>User Profile: <span id="profileUserEmail"></span></h3>
                <button class="dlp-modal-close" onclick="closeModal('userProfileModal')">&times;</button>
            </div>
            <div class="dlp-modal-body">
                <div style="display: grid; grid-template-columns: 250px 1fr; gap: 25px;">
                    <!-- User Info Sidebar -->
                    <div>
                        <div style="text-align: center; padding: 20px; background: var(--bg-lighter); border-radius: 12px; margin-bottom: 20px;">
                            <div id="profileAvatar" style="width: 80px; height: 80px; background: linear-gradient(135deg, var(--primary), var(--secondary)); border-radius: 50%; margin: 0 auto 15px; display: flex; align-items: center; justify-content: center; font-size: 32px; color: white;"></div>
                            <h4 id="profileFullName" style="margin-bottom: 5px;"></h4>
                            <div id="profileDepartment" style="color: var(--text-muted); font-size: 13px;"></div>
                            <div id="profileRiskBadge" style="margin-top: 10px;"></div>
                        </div>

                        <div style="background: var(--bg-lighter); border-radius: 8px; padding: 15px;">
                            <h5 style="margin-bottom: 15px; font-size: 13px; color: var(--text-muted); text-transform: uppercase;">Contact Info</h5>
                            <div style="margin-bottom: 12px;">
                                <div style="font-size: 11px; color: var(--text-muted);">Email</div>
                                <div id="profileEmailFull" style="font-size: 13px;"></div>
                            </div>
                            <div style="margin-bottom: 12px;">
                                <div style="font-size: 11px; color: var(--text-muted);">Manager</div>
                                <div id="profileManager" style="font-size: 13px;"></div>
                            </div>
                            <div>
                                <div style="font-size: 11px; color: var(--text-muted);">Employee Since</div>
                                <div id="profileHireDate" style="font-size: 13px;"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Main Content -->
                    <div>
                        <!-- Risk Stats -->
                        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 20px;">
                            <div style="background: var(--bg-lighter); padding: 15px; border-radius: 8px; text-align: center;">
                                <div id="profileTotalViolations" style="font-size: 28px; font-weight: bold; color: var(--danger);"></div>
                                <div style="font-size: 11px; color: var(--text-muted);">Total Violations</div>
                            </div>
                            <div style="background: var(--bg-lighter); padding: 15px; border-radius: 8px; text-align: center;">
                                <div id="profileBlockedEmails" style="font-size: 28px; font-weight: bold; color: var(--warning);"></div>
                                <div style="font-size: 11px; color: var(--text-muted);">Emails Blocked</div>
                            </div>
                            <div style="background: var(--bg-lighter); padding: 15px; border-radius: 8px; text-align: center;">
                                <div id="profileRiskScore" style="font-size: 28px; font-weight: bold;"></div>
                                <div style="font-size: 11px; color: var(--text-muted);">Risk Score</div>
                            </div>
                            <div style="background: var(--bg-lighter); padding: 15px; border-radius: 8px; text-align: center;">
                                <div id="profileLastIncident" style="font-size: 14px; font-weight: bold; color: var(--text);"></div>
                                <div style="font-size: 11px; color: var(--text-muted);">Last Incident</div>
                            </div>
                        </div>

                        <!-- Violation Breakdown -->
                        <div style="background: var(--bg-lighter); border-radius: 8px; padding: 20px; margin-bottom: 20px;">
                            <h4 style="margin-bottom: 15px;">Violation Breakdown</h4>
                            <div id="profileViolationBreakdown" style="display: grid; gap: 12px;"></div>
                        </div>

                        <!-- Recent Incidents -->
                        <div style="background: var(--bg-lighter); border-radius: 8px; padding: 20px;">
                            <h4 style="margin-bottom: 15px;">Recent Incidents</h4>
                            <div id="profileRecentIncidents" style="max-height: 200px; overflow-y: auto;">
                                <!-- Incidents will be populated dynamically -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="dlp-modal-footer">
                <button class="btn btn-warning" onclick="sendWarningToUser()" style="margin-right: auto;">Send Warning</button>
                <button class="btn btn-secondary" onclick="exportUserReport()">Export Report</button>
                <button class="btn btn-secondary" onclick="scheduleTraining()">Schedule Training</button>
                <button class="btn btn-danger" onclick="restrictUserAccess()">Restrict Access</button>
                <button class="btn btn-secondary" onclick="closeModal('userProfileModal')">Close</button>
            </div>
        </div>
    </div>

    <!-- Toast -->
    <div id="dlpToast" class="dlp-toast"></div>

    <script>
    // Module Navigation
    function showModule(moduleId) {
        document.querySelectorAll('.module').forEach(m => m.classList.remove('active'));
        document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));

        document.getElementById(moduleId).classList.add('active');
        event.target.closest('.nav-item')?.classList.add('active');
    }

    // Modal Functions
    function openModal(modalId) {
        document.getElementById(modalId).classList.add('active');
    }

    function closeModal(modalId) {
        document.getElementById(modalId).classList.remove('active');
    }

    function showToast(message, type = 'success') {
        const toast = document.getElementById('dlpToast');
        toast.textContent = message;
        toast.className = 'dlp-toast ' + type;
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 4000);
    }

    // Email Functions
    let currentEmailId = null;

    function viewEmailDetails(emailId) {
        currentEmailId = emailId;
        const emails = <?= json_encode($monitoredEmails) ?>;
        const email = emails.find(e => e.id === emailId);

        if (email) {
            document.getElementById('emailDetailFrom').textContent = email.from;
            document.getElementById('emailDetailTo').textContent = email.to;
            document.getElementById('emailDetailSubject').textContent = email.subject;
            document.getElementById('emailDetailRisk').textContent = email.risk;
            document.getElementById('emailDetailCategory').textContent = email.category;
            document.getElementById('emailDetailAttachments').textContent = email.attachments + ' files';
            document.getElementById('emailDetailSize').textContent = email.size;

            document.getElementById('emailDetailPatterns').innerHTML = `
                <div style="margin-bottom: 8px;"><span class="badge badge-critical">Financial Keywords</span> Found: revenue, profit, forecast</div>
                <div style="margin-bottom: 8px;"><span class="badge badge-high">Confidential Tag</span> Document marked as confidential</div>
                <div><span class="badge badge-medium">External Recipient</span> Sending to non-company domain</div>
            `;

            document.getElementById('emailDetailTracking').innerHTML = `
                <div style="margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px solid var(--border);">
                    <strong>14:30:05</strong> - Agent AGT-001: Email intercepted and blocked
                </div>
                <div style="margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px solid var(--border);">
                    <strong>14:30:03</strong> - Agent AGT-001: Pattern match detected (Financial keywords)
                </div>
                <div style="margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px solid var(--border);">
                    <strong>14:30:02</strong> - Agent AGT-004: Attachment scan started
                </div>
                <div>
                    <strong>14:30:01</strong> - Email received and queued for scanning
                </div>
            `;
        }

        openModal('emailDetailsModal');
    }

    function releaseEmail(emailId) {
        if (confirm('Are you sure you want to release this email?')) {
            showToast('Email ' + emailId + ' released successfully');
        }
    }

    function blockEmail(emailId) {
        if (confirm('Are you sure you want to permanently block this email?')) {
            showToast('Email ' + emailId + ' blocked', 'warning');
        }
    }

    function releaseEmailFromModal() {
        releaseEmail(currentEmailId);
        closeModal('emailDetailsModal');
    }

    function blockEmailFromModal() {
        blockEmail(currentEmailId);
        closeModal('emailDetailsModal');
    }

    function openScanEmailModal() {
        document.getElementById('scanEmailForm').reset();
        document.getElementById('scanResultsSection').style.display = 'none';
        document.getElementById('scanEmailBtn').disabled = false;
        document.getElementById('scanEmailBtn').textContent = 'Start Scan';
        openModal('scanEmailModal');
    }

    function executeScanEmail() {
        const btn = document.getElementById('scanEmailBtn');
        const resultsSection = document.getElementById('scanResultsSection');
        const resultsContent = document.getElementById('scanResultsContent');

        const fromEmail = document.getElementById('scanFromEmail').value || 'unknown@sender.com';
        const toEmail = document.getElementById('scanToEmail').value || 'recipient@company.com';
        const subject = document.getElementById('scanSubject').value || 'No Subject';
        const body = document.getElementById('scanEmailBody').value || '';

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner" style="width:14px;height:14px;border:2px solid rgba(255,255,255,0.3);border-top-color:white;border-radius:50%;display:inline-block;animation:spin 1s linear infinite;margin-right:8px;"></span> Scanning...';

        showToast('Scanning email for sensitive data...', 'info');

        // Simulate scan progress
        setTimeout(() => {
            // Generate scan results
            const riskScore = Math.floor(Math.random() * 100);
            const threats = [];

            if (document.getElementById('scanPII').checked && body.toLowerCase().includes('ssn')) {
                threats.push({ type: 'PII', pattern: 'Social Security Number detected', severity: 'critical' });
            }
            if (document.getElementById('scanFinancial').checked && (body.toLowerCase().includes('credit card') || body.toLowerCase().includes('account'))) {
                threats.push({ type: 'Financial', pattern: 'Financial data pattern detected', severity: 'high' });
            }
            if (document.getElementById('scanConfidential').checked && body.toLowerCase().includes('confidential')) {
                threats.push({ type: 'Confidential', pattern: 'Confidential marker found', severity: 'medium' });
            }

            // Add random detections for demo
            if (riskScore > 70) {
                threats.push({ type: 'PII', pattern: 'Email address pattern (external)', severity: 'medium' });
            }
            if (riskScore > 50) {
                threats.push({ type: 'Data Leak', pattern: 'Potential data exfiltration', severity: 'high' });
            }

            let resultHtml = '<div style="margin-bottom: 15px;">';
            resultHtml += '<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; text-align: center; margin-bottom: 20px;">';
            resultHtml += '<div style="background: var(--bg-card); padding: 15px; border-radius: 8px;"><div style="font-size: 28px; font-weight: bold; color: ' + (riskScore > 70 ? 'var(--danger)' : riskScore > 40 ? 'var(--warning)' : 'var(--success)') + ';">' + riskScore + '</div><div style="font-size: 12px; color: var(--text-muted);">Risk Score</div></div>';
            resultHtml += '<div style="background: var(--bg-card); padding: 15px; border-radius: 8px;"><div style="font-size: 28px; font-weight: bold; color: var(--primary);">' + threats.length + '</div><div style="font-size: 12px; color: var(--text-muted);">Threats Found</div></div>';
            resultHtml += '<div style="background: var(--bg-card); padding: 15px; border-radius: 8px;"><div style="font-size: 28px; font-weight: bold; color: var(--success);">0.8s</div><div style="font-size: 12px; color: var(--text-muted);">Scan Time</div></div>';
            resultHtml += '</div>';

            if (threats.length > 0) {
                resultHtml += '<h5 style="margin-bottom: 10px; color: var(--danger);">Detected Threats:</h5>';
                threats.forEach(threat => {
                    const color = threat.severity === 'critical' ? 'var(--danger)' : threat.severity === 'high' ? 'var(--warning)' : 'var(--info)';
                    resultHtml += '<div style="display: flex; align-items: center; gap: 10px; padding: 10px; background: var(--bg-card); border-radius: 6px; margin-bottom: 8px; border-left: 3px solid ' + color + ';">';
                    resultHtml += '<span style="background: ' + color + '; color: white; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 600;">' + threat.severity.toUpperCase() + '</span>';
                    resultHtml += '<span style="font-weight: 500;">' + threat.type + ':</span>';
                    resultHtml += '<span style="color: var(--text-muted);">' + threat.pattern + '</span>';
                    resultHtml += '</div>';
                });
            } else {
                resultHtml += '<div style="text-align: center; padding: 20px; color: var(--success);"><span style="font-size: 48px;">✓</span><div style="margin-top: 10px; font-weight: 500;">No threats detected</div></div>';
            }

            resultHtml += '</div>';
            resultHtml += '<div style="display: flex; gap: 10px; margin-top: 15px;">';
            resultHtml += '<button class="btn btn-secondary" onclick="closeModal(\'scanEmailModal\')">Close</button>';
            if (threats.length > 0) {
                resultHtml += '<button class="btn btn-warning" onclick="quarantineScannedEmail()">Quarantine</button>';
                resultHtml += '<button class="btn btn-danger" onclick="blockScannedEmail()">Block</button>';
            }
            resultHtml += '<button class="btn btn-primary" onclick="openScanEmailModal()">Scan Another</button>';
            resultHtml += '</div>';

            resultsContent.innerHTML = resultHtml;
            resultsSection.style.display = 'block';
            btn.style.display = 'none';

            showToast('Scan complete! ' + threats.length + ' threat(s) detected', threats.length > 0 ? 'warning' : 'success');
        }, 2000);
    }

    function quarantineScannedEmail() {
        showToast('Email quarantined successfully', 'success');
        closeModal('scanEmailModal');
    }

    function blockScannedEmail() {
        showToast('Email blocked and sender added to blacklist', 'success');
        closeModal('scanEmailModal');
    }

    function refreshEmails() {
        showToast('Refreshing email list...', 'info');

        // Add visual feedback
        const emailTable = document.querySelector('.data-table tbody');
        if (emailTable) {
            emailTable.style.opacity = '0.5';
        }

        setTimeout(() => {
            if (emailTable) {
                emailTable.style.opacity = '1';
            }
            showToast('Email list updated - ' + Math.floor(Math.random() * 10 + 5) + ' new emails found', 'success');
        }, 1500);
    }

    function exportEmails() {
        showToast('Generating CSV export...', 'info');

        setTimeout(() => {
            // Create CSV content
            const csvContent = 'ID,From,To,Subject,Risk Score,Status,Category,Timestamp\n' +
                'E001,john.doe@external.com,finance@company.com,Q4 Financial Report,85,Blocked,Financial Data,' + new Date().toISOString() + '\n' +
                'E002,hr@company.com,employees@company.com,Salary Information,78,Quarantined,PII,' + new Date().toISOString() + '\n' +
                'E003,support@vendor.com,it@company.com,System Access Request,45,Flagged,Access Request,' + new Date().toISOString();

            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'dlp_email_report_' + new Date().toISOString().split('T')[0] + '.csv';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);

            showToast('Report exported successfully!', 'success');
        }, 1500);
    }

    function createPDFReport() {
        openModal('pdfReportModal');
        document.getElementById('pdfGenerationProgress').style.display = 'none';
        document.getElementById('generatePdfBtn').disabled = false;
    }

    function generatePDFReport() {
        const btn = document.getElementById('generatePdfBtn');
        const progress = document.getElementById('pdfGenerationProgress');
        const progressBar = document.getElementById('pdfProgressBar');
        const progressText = document.getElementById('pdfProgressText');

        btn.disabled = true;
        progress.style.display = 'block';

        const steps = [
            { percent: 10, text: 'Gathering email statistics...' },
            { percent: 25, text: 'Analyzing threat data...' },
            { percent: 40, text: 'Processing incident reports...' },
            { percent: 55, text: 'Generating charts...' },
            { percent: 70, text: 'Compiling agent performance...' },
            { percent: 85, text: 'Formatting PDF document...' },
            { percent: 100, text: 'Finalizing report...' }
        ];

        let stepIndex = 0;
        const interval = setInterval(() => {
            if (stepIndex < steps.length) {
                progressBar.style.width = steps[stepIndex].percent + '%';
                progressText.textContent = steps[stepIndex].text;
                stepIndex++;
            } else {
                clearInterval(interval);

                // Generate PDF content
                const title = document.getElementById('reportTitle').value || 'Email DLP Security Report';
                const dateRange = document.getElementById('reportDateRange').value;
                const format = document.getElementById('reportFormat').value;

                // Create a simulated PDF download
                const pdfContent = generatePDFContent(title, dateRange, format);
                const blob = new Blob([pdfContent], { type: 'application/pdf' });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'DLP_Report_' + new Date().toISOString().split('T')[0] + '.pdf';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);

                showToast('PDF Report generated successfully!', 'success');
                closeModal('pdfReportModal');
            }
        }, 400);
    }

    function generatePDFContent(title, dateRange, format) {
        // Generate a text-based report (in production, use a PDF library)
        const now = new Date();
        const dateStr = now.toLocaleDateString();
        const timeStr = now.toLocaleTimeString();

        let content = '%PDF-1.4\n';
        content += '1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj\n';
        content += '2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj\n';
        content += '3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R >> endobj\n';
        content += '4 0 obj << /Length 500 >> stream\n';
        content += 'BT /F1 24 Tf 50 750 Td (' + title + ') Tj ET\n';
        content += 'BT /F1 12 Tf 50 720 Td (Generated: ' + dateStr + ' ' + timeStr + ') Tj ET\n';
        content += 'BT /F1 12 Tf 50 690 Td (Date Range: ' + dateRange + ') Tj ET\n';
        content += 'BT /F1 14 Tf 50 650 Td (Email DLP Summary) Tj ET\n';
        content += 'BT /F1 12 Tf 50 620 Td (Total Emails Scanned: 45,234) Tj ET\n';
        content += 'BT /F1 12 Tf 50 600 Td (Threats Detected: 1,247) Tj ET\n';
        content += 'BT /F1 12 Tf 50 580 Td (Emails Blocked: 523) Tj ET\n';
        content += 'BT /F1 12 Tf 50 560 Td (Emails Quarantined: 312) Tj ET\n';
        content += 'BT /F1 12 Tf 50 540 Td (Active Agents: 4) Tj ET\n';
        content += 'endstream endobj\n';
        content += 'xref\n0 5\n';
        content += 'trailer << /Size 5 /Root 1 0 R >>\n';
        content += '%%EOF';

        return content;
    }

    // Agent Functions
    function openCreateAgentModal() {
        document.getElementById('createAgentForm').reset();
        openModal('createAgentModal');
    }

    // Agent Data
    const agentsData = {
        'AGT-001': {
            id: 'AGT-001',
            name: 'Financial Data Monitor',
            status: 'active',
            emails_scanned: 12456,
            threats_detected: 234,
            cpu: 12,
            memory: 256,
            priority: 'high',
            maxCpu: 25,
            maxMemory: 512,
            scanTargets: { inbound: true, outbound: true, internal: false, attachments: true },
            rules: ['pii', 'financial', 'creditcard'],
            notifications: { email: true, slack: false, sms: false },
            alertThreshold: 'medium'
        },
        'AGT-002': {
            id: 'AGT-002',
            name: 'PII Protection Agent',
            status: 'active',
            emails_scanned: 15678,
            threats_detected: 456,
            cpu: 18,
            memory: 312,
            priority: 'critical',
            maxCpu: 30,
            maxMemory: 512,
            scanTargets: { inbound: true, outbound: true, internal: true, attachments: true },
            rules: ['pii', 'creditcard', 'healthcare'],
            notifications: { email: true, slack: true, sms: false },
            alertThreshold: 'all'
        },
        'AGT-003': {
            id: 'AGT-003',
            name: 'Executive Communications Monitor',
            status: 'active',
            emails_scanned: 3456,
            threats_detected: 89,
            cpu: 8,
            memory: 128,
            priority: 'normal',
            maxCpu: 15,
            maxMemory: 256,
            scanTargets: { inbound: true, outbound: true, internal: false, attachments: false },
            rules: ['financial', 'confidential'],
            notifications: { email: true, slack: false, sms: true },
            alertThreshold: 'high'
        },
        'AGT-004': {
            id: 'AGT-004',
            name: 'Attachment Scanner',
            status: 'active',
            emails_scanned: 8901,
            threats_detected: 178,
            cpu: 25,
            memory: 512,
            priority: 'high',
            maxCpu: 40,
            maxMemory: 1024,
            scanTargets: { inbound: true, outbound: true, internal: true, attachments: true },
            rules: ['sourcecode', 'confidential', 'pii'],
            notifications: { email: true, slack: true, sms: false },
            alertThreshold: 'medium'
        },
        'AGT-005': {
            id: 'AGT-005',
            name: 'External Domain Watchdog',
            status: 'paused',
            emails_scanned: 6234,
            threats_detected: 123,
            cpu: 0,
            memory: 64,
            priority: 'normal',
            maxCpu: 20,
            maxMemory: 256,
            scanTargets: { inbound: false, outbound: true, internal: false, attachments: true },
            rules: ['financial', 'pii'],
            notifications: { email: true, slack: false, sms: false },
            alertThreshold: 'high'
        }
    };

    // Agent Logs Data
    const agentLogsData = {
        'AGT-001': [
            { timestamp: '2025-01-26 14:30:05', type: 'scan', message: 'Scanned email from john@company.com to external@partner.com', details: 'Clean' },
            { timestamp: '2025-01-26 14:29:45', type: 'threat', message: 'Financial data detected in attachment', details: 'Risk: 85' },
            { timestamp: '2025-01-26 14:28:30', type: 'block', message: 'Blocked email containing revenue forecast', details: 'Policy: FIN-001' },
            { timestamp: '2025-01-26 14:25:12', type: 'scan', message: 'Scanned 15 emails in batch process', details: '15 clean' },
            { timestamp: '2025-01-26 14:20:00', type: 'system', message: 'Agent heartbeat - all systems operational', details: 'CPU: 12%' },
            { timestamp: '2025-01-26 14:15:33', type: 'threat', message: 'Confidential marker detected in email body', details: 'Risk: 72' },
            { timestamp: '2025-01-26 14:10:15', type: 'scan', message: 'Deep scan completed on 3 attachments', details: 'Clean' },
            { timestamp: '2025-01-26 14:05:00', type: 'system', message: 'Memory optimization completed', details: '256MB' },
            { timestamp: '2025-01-26 14:00:22', type: 'block', message: 'Blocked sensitive data transfer attempt', details: 'Policy: SEC-003' },
            { timestamp: '2025-01-26 13:55:18', type: 'error', message: 'Timeout scanning large attachment', details: 'Retry scheduled' }
        ],
        'AGT-002': [
            { timestamp: '2025-01-26 14:29:58', type: 'threat', message: 'SSN pattern detected in email body', details: 'Risk: 95' },
            { timestamp: '2025-01-26 14:28:45', type: 'block', message: 'Blocked email with credit card data', details: 'Policy: PII-001' },
            { timestamp: '2025-01-26 14:25:30', type: 'scan', message: 'Scanned HR department outbound emails', details: '23 emails' },
            { timestamp: '2025-01-26 14:20:15', type: 'system', message: 'Rule update applied: PII patterns v2.3', details: 'Success' },
            { timestamp: '2025-01-26 14:15:00', type: 'threat', message: 'Multiple PII fields in single email', details: 'Risk: 88' }
        ],
        'AGT-003': [
            { timestamp: '2025-01-26 14:28:45', type: 'scan', message: 'Monitoring CEO external communications', details: '5 emails' },
            { timestamp: '2025-01-26 14:20:30', type: 'system', message: 'Executive watchlist updated', details: '+2 contacts' },
            { timestamp: '2025-01-26 14:15:22', type: 'threat', message: 'Unusual recipient domain detected', details: 'Risk: 45' }
        ],
        'AGT-004': [
            { timestamp: '2025-01-26 14:30:01', type: 'scan', message: 'Scanned ZIP attachment - 12 files', details: 'Clean' },
            { timestamp: '2025-01-26 14:28:15', type: 'threat', message: 'Source code detected in attachment', details: 'Risk: 78' },
            { timestamp: '2025-01-26 14:25:00', type: 'block', message: 'Blocked executable attachment', details: 'Policy: ATT-002' },
            { timestamp: '2025-01-26 14:20:45', type: 'error', message: 'Unable to scan encrypted PDF', details: 'Quarantined' }
        ],
        'AGT-005': [
            { timestamp: '2025-01-26 12:00:00', type: 'system', message: 'Agent paused by administrator', details: 'Manual' },
            { timestamp: '2025-01-26 11:55:30', type: 'scan', message: 'Final scan before pause', details: '8 emails' },
            { timestamp: '2025-01-26 11:50:15', type: 'threat', message: 'External domain flagged', details: 'Risk: 62' }
        ]
    };

    let currentAgentId = null;

    function createAgent() {
        const agentName = document.getElementById('newAgentName')?.value || 'New Agent';
        const agentId = 'AGT-' + String(Object.keys(agentsData).length + 1).padStart(3, '0');

        agentsData[agentId] = {
            id: agentId,
            name: agentName,
            status: 'active',
            emails_scanned: 0,
            threats_detected: 0,
            cpu: 0,
            memory: 64,
            priority: 'normal',
            maxCpu: 25,
            maxMemory: 512,
            scanTargets: { inbound: true, outbound: true, internal: false, attachments: true },
            rules: ['pii', 'financial'],
            notifications: { email: true, slack: false, sms: false },
            alertThreshold: 'medium'
        };

        agentLogsData[agentId] = [
            { timestamp: new Date().toISOString().replace('T', ' ').substr(0, 19), type: 'system', message: 'Agent created and initialized', details: 'Ready' }
        ];

        closeModal('createAgentModal');
        showToast('Agent "' + agentName + '" created and started successfully');

        // Refresh page to show new agent (in real app, would dynamically add)
        setTimeout(() => location.reload(), 1500);
    }

    function pauseAgent(agentId) {
        const agent = agentsData[agentId];
        if (!agent) {
            showToast('Agent not found', 'error');
            return;
        }

        if (!confirm('Pause agent "' + agent.name + '"? It will stop scanning emails until resumed.')) {
            return;
        }

        agent.status = 'paused';
        agent.cpu = 0;

        // Update UI
        const agentCard = findAgentCard(agentId);
        if (agentCard) {
            const statusIndicator = agentCard.querySelector('.agent-status-indicator');
            if (statusIndicator) {
                statusIndicator.classList.remove('active');
                statusIndicator.classList.add('paused');
            }

            const actionsDiv = agentCard.querySelector('.agent-actions');
            if (actionsDiv) {
                actionsDiv.innerHTML = `
                    <button class="btn btn-sm btn-success" onclick="resumeAgent('${agentId}')">Resume</button>
                    <button class="btn btn-sm btn-secondary" onclick="configureAgent('${agentId}')">Configure</button>
                    <button class="btn btn-sm btn-secondary" onclick="viewAgentLogs('${agentId}')">Logs</button>
                `;
            }

            // Update CPU display
            const cpuStat = agentCard.querySelectorAll('.agent-stat-value')[2];
            if (cpuStat) cpuStat.textContent = '0%';
        }

        // Add log entry
        if (!agentLogsData[agentId]) agentLogsData[agentId] = [];
        agentLogsData[agentId].unshift({
            timestamp: new Date().toISOString().replace('T', ' ').substr(0, 19),
            type: 'system',
            message: 'Agent paused by administrator',
            details: 'Manual'
        });

        showToast('Agent "' + agent.name + '" paused', 'warning');
    }

    function resumeAgent(agentId) {
        const agent = agentsData[agentId];
        if (!agent) {
            showToast('Agent not found', 'error');
            return;
        }

        agent.status = 'active';
        agent.cpu = Math.floor(Math.random() * 20) + 5; // Simulate CPU usage

        // Update UI
        const agentCard = findAgentCard(agentId);
        if (agentCard) {
            const statusIndicator = agentCard.querySelector('.agent-status-indicator');
            if (statusIndicator) {
                statusIndicator.classList.remove('paused');
                statusIndicator.classList.add('active');
            }

            const actionsDiv = agentCard.querySelector('.agent-actions');
            if (actionsDiv) {
                actionsDiv.innerHTML = `
                    <button class="btn btn-sm btn-warning" onclick="pauseAgent('${agentId}')">Pause</button>
                    <button class="btn btn-sm btn-secondary" onclick="configureAgent('${agentId}')">Configure</button>
                    <button class="btn btn-sm btn-secondary" onclick="viewAgentLogs('${agentId}')">Logs</button>
                `;
            }

            // Update CPU display
            const cpuStat = agentCard.querySelectorAll('.agent-stat-value')[2];
            if (cpuStat) cpuStat.textContent = agent.cpu + '%';
        }

        // Add log entry
        if (!agentLogsData[agentId]) agentLogsData[agentId] = [];
        agentLogsData[agentId].unshift({
            timestamp: new Date().toISOString().replace('T', ' ').substr(0, 19),
            type: 'system',
            message: 'Agent resumed by administrator',
            details: 'Active'
        });

        showToast('Agent "' + agent.name + '" resumed successfully');
    }

    function findAgentCard(agentId) {
        const cards = document.querySelectorAll('.agent-card');
        for (let card of cards) {
            if (card.querySelector('.agent-id')?.textContent === agentId) {
                return card;
            }
        }
        return null;
    }

    function configureAgent(agentId) {
        const agent = agentsData[agentId];
        if (!agent) {
            showToast('Agent not found', 'error');
            return;
        }

        currentAgentId = agentId;

        // Populate modal
        document.getElementById('configAgentId').value = agentId;
        document.getElementById('configAgentName').textContent = agent.name;
        document.getElementById('configAgentNameInput').value = agent.name;
        document.getElementById('configEmailsScanned').textContent = agent.emails_scanned.toLocaleString();
        document.getElementById('configThreatsDetected').textContent = agent.threats_detected.toLocaleString();
        document.getElementById('configScanPriority').value = agent.priority || 'normal';
        document.getElementById('configMaxCpu').value = agent.maxCpu || 25;
        document.getElementById('configMaxMemory').value = agent.maxMemory || 512;

        // Scan targets
        document.getElementById('configScanInbound').checked = agent.scanTargets?.inbound ?? true;
        document.getElementById('configScanOutbound').checked = agent.scanTargets?.outbound ?? true;
        document.getElementById('configScanInternal').checked = agent.scanTargets?.internal ?? false;
        document.getElementById('configScanAttachments').checked = agent.scanTargets?.attachments ?? true;

        // Rules
        document.querySelectorAll('input[name="configRule"]').forEach(cb => {
            cb.checked = agent.rules?.includes(cb.value) ?? false;
        });

        // Notifications
        document.getElementById('configNotifyEmail').checked = agent.notifications?.email ?? true;
        document.getElementById('configNotifySlack').checked = agent.notifications?.slack ?? false;
        document.getElementById('configNotifySms').checked = agent.notifications?.sms ?? false;

        // Alert threshold
        document.getElementById('configAlertThreshold').value = agent.alertThreshold || 'medium';

        openModal('configureAgentModal');
    }

    function saveAgentConfig() {
        const agentId = document.getElementById('configAgentId').value;
        const agent = agentsData[agentId];

        if (!agent) {
            showToast('Agent not found', 'error');
            return;
        }

        // Update agent data
        agent.name = document.getElementById('configAgentNameInput').value;
        agent.priority = document.getElementById('configScanPriority').value;
        agent.maxCpu = parseInt(document.getElementById('configMaxCpu').value);
        agent.maxMemory = parseInt(document.getElementById('configMaxMemory').value);

        agent.scanTargets = {
            inbound: document.getElementById('configScanInbound').checked,
            outbound: document.getElementById('configScanOutbound').checked,
            internal: document.getElementById('configScanInternal').checked,
            attachments: document.getElementById('configScanAttachments').checked
        };

        agent.rules = [];
        document.querySelectorAll('input[name="configRule"]:checked').forEach(cb => {
            agent.rules.push(cb.value);
        });

        agent.notifications = {
            email: document.getElementById('configNotifyEmail').checked,
            slack: document.getElementById('configNotifySlack').checked,
            sms: document.getElementById('configNotifySms').checked
        };

        agent.alertThreshold = document.getElementById('configAlertThreshold').value;

        // Update UI
        const agentCard = findAgentCard(agentId);
        if (agentCard) {
            const nameEl = agentCard.querySelector('.agent-name');
            if (nameEl) nameEl.textContent = agent.name;
        }

        // Add log entry
        if (!agentLogsData[agentId]) agentLogsData[agentId] = [];
        agentLogsData[agentId].unshift({
            timestamp: new Date().toISOString().replace('T', ' ').substr(0, 19),
            type: 'system',
            message: 'Agent configuration updated',
            details: 'By admin'
        });

        closeModal('configureAgentModal');
        showToast('Agent "' + agent.name + '" configuration saved successfully');
    }

    function deleteAgent() {
        const agentId = document.getElementById('configAgentId').value;
        const agent = agentsData[agentId];

        if (!agent) {
            showToast('Agent not found', 'error');
            return;
        }

        if (!confirm('Are you sure you want to delete agent "' + agent.name + '"? This action cannot be undone.')) {
            return;
        }

        // Remove agent card from UI
        const agentCard = findAgentCard(agentId);
        if (agentCard) {
            agentCard.remove();
        }

        // Remove from data
        delete agentsData[agentId];
        delete agentLogsData[agentId];

        closeModal('configureAgentModal');
        showToast('Agent deleted successfully', 'warning');
    }

    function viewAgentLogs(agentId) {
        const agent = agentsData[agentId];
        if (!agent) {
            showToast('Agent not found', 'error');
            return;
        }

        currentAgentId = agentId;
        document.getElementById('logsAgentName').textContent = agent.name;

        // Reset filters
        document.getElementById('logsFilter').value = 'all';
        document.getElementById('logsTimeRange').value = '24h';

        // Populate logs
        populateAgentLogs(agentId);

        openModal('agentLogsModal');
    }

    function populateAgentLogs(agentId, filterType = 'all') {
        const logs = agentLogsData[agentId] || [];
        const tbody = document.getElementById('agentLogsBody');

        // Calculate stats
        let totalEvents = logs.length;
        let threatEvents = logs.filter(l => l.type === 'threat').length;
        let blockEvents = logs.filter(l => l.type === 'block').length;
        let errorEvents = logs.filter(l => l.type === 'error').length;

        document.getElementById('logsTotalEvents').textContent = totalEvents;
        document.getElementById('logsThreatEvents').textContent = threatEvents;
        document.getElementById('logsBlockEvents').textContent = blockEvents;
        document.getElementById('logsErrorEvents').textContent = errorEvents;

        // Filter logs
        let filteredLogs = filterType === 'all' ? logs : logs.filter(l => l.type === filterType);

        // Build table HTML
        const typeStyles = {
            'scan': 'background: rgba(59, 130, 246, 0.2); color: #3b82f6;',
            'threat': 'background: rgba(239, 68, 68, 0.2); color: #ef4444;',
            'block': 'background: rgba(245, 158, 11, 0.2); color: #f59e0b;',
            'error': 'background: rgba(239, 68, 68, 0.2); color: #ef4444;',
            'system': 'background: rgba(16, 185, 129, 0.2); color: #10b981;'
        };

        tbody.innerHTML = filteredLogs.map(log => `
            <tr>
                <td style="font-family: monospace; font-size: 12px;">${log.timestamp}</td>
                <td><span style="padding: 3px 8px; border-radius: 4px; font-size: 11px; ${typeStyles[log.type] || ''}">${log.type.toUpperCase()}</span></td>
                <td>${log.message}</td>
                <td style="font-size: 12px; color: var(--text-muted);">${log.details}</td>
            </tr>
        `).join('');

        if (filteredLogs.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" style="text-align: center; padding: 30px; color: var(--text-muted);">No logs found for selected filter</td></tr>';
        }
    }

    function filterAgentLogs() {
        const filterType = document.getElementById('logsFilter').value;
        populateAgentLogs(currentAgentId, filterType);
    }

    function refreshAgentLogs() {
        // Simulate adding a new log entry
        if (currentAgentId && agentLogsData[currentAgentId]) {
            agentLogsData[currentAgentId].unshift({
                timestamp: new Date().toISOString().replace('T', ' ').substr(0, 19),
                type: 'system',
                message: 'Log refresh requested',
                details: 'Manual'
            });
        }
        filterAgentLogs();
        showToast('Logs refreshed', 'info');
    }

    function exportAgentLogs() {
        const agent = agentsData[currentAgentId];
        const logs = agentLogsData[currentAgentId] || [];

        // Create CSV content
        let csv = 'Timestamp,Type,Message,Details\n';
        logs.forEach(log => {
            csv += `"${log.timestamp}","${log.type}","${log.message}","${log.details}"\n`;
        });

        // Download
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `agent_logs_${currentAgentId}_${new Date().toISOString().split('T')[0]}.csv`;
        a.click();
        window.URL.revokeObjectURL(url);

        showToast('Logs exported successfully');
    }

    function clearAgentLogs() {
        if (!confirm('Clear all logs for this agent? This cannot be undone.')) {
            return;
        }

        agentLogsData[currentAgentId] = [{
            timestamp: new Date().toISOString().replace('T', ' ').substr(0, 19),
            type: 'system',
            message: 'Logs cleared by administrator',
            details: 'Manual'
        }];

        filterAgentLogs();
        showToast('Logs cleared', 'warning');
    }

    // Chain Functions
    function investigateChain(chainId) {
        showToast('Opening chain investigation for ' + chainId, 'info');
    }

    function openTrackEmailModal() {
        showToast('Opening email tracker...', 'info');
    }

    // Incident Functions
    // Incident Data
    const incidentsData = {
        'INC-001': {
            id: 'INC-001',
            type: 'External Forward',
            severity: 'critical',
            detected: '2025-01-26 14:30:05',
            status: 'investigating',
            user: {
                email: 'john.smith@company.com',
                name: 'John Smith',
                username: 'john.smith',
                department: 'Finance',
                employeeId: 'EMP-1234',
                manager: 'Michael Johnson',
                managerEmail: 'michael.johnson@company.com',
                previousIncidents: 3
            },
            email: {
                subject: 'Q4 Financial Report - Confidential',
                from: 'john.smith@company.com',
                to: 'external@competitor.com',
                attachments: '2 files (Q4_Report.xlsx, Forecast.pdf)'
            },
            description: 'Confidential financial data has been forwarded to an unauthorized external recipient.',
            actionsTaken: ['Email blocked', 'Sender notified', 'Security team alerted'],
            managerNotified: false,
            notes: ''
        },
        'INC-002': {
            id: 'INC-002',
            type: 'PII Data Exposure',
            severity: 'high',
            detected: '2025-01-26 11:15:22',
            status: 'investigating',
            user: {
                email: 'sarah.jones@company.com',
                name: 'Sarah Jones',
                username: 'sarah.jones',
                department: 'Human Resources',
                employeeId: 'EMP-2345',
                manager: 'Emily Davis',
                managerEmail: 'emily.davis@company.com',
                previousIncidents: 1
            },
            email: {
                subject: 'Employee Records Export',
                from: 'sarah.jones@company.com',
                to: 'personal@gmail.com',
                attachments: '1 file (HR_Records.csv)'
            },
            description: 'Employee personal information (SSN, addresses) sent to personal email account.',
            actionsTaken: ['Email quarantined', 'User notified'],
            managerNotified: false,
            notes: ''
        },
        'INC-003': {
            id: 'INC-003',
            type: 'Source Code Leak',
            severity: 'high',
            detected: '2025-01-25 16:45:00',
            status: 'investigating',
            user: {
                email: 'dev.team@company.com',
                name: 'Development Team',
                username: 'dev.team',
                department: 'Engineering',
                employeeId: 'EMP-3456',
                manager: 'Alex Chen',
                managerEmail: 'alex.chen@company.com',
                previousIncidents: 0
            },
            email: {
                subject: 'Code Review Files',
                from: 'dev.team@company.com',
                to: 'contractor@external.com',
                attachments: '5 files (Various .py and .js files)'
            },
            description: 'Proprietary source code shared with unauthorized external contractor.',
            actionsTaken: ['Email flagged', 'Security review initiated'],
            managerNotified: true,
            notes: ''
        }
    };

    let currentIncidentId = null;

    function viewIncident(incidentId) {
        const incident = incidentsData[incidentId];
        if (!incident) {
            // Use default for demo
            currentIncidentId = 'INC-001';
            document.getElementById('incidentId').textContent = incidentId;
            document.getElementById('incidentIdHeader').textContent = incidentId;
            openModal('incidentModal');
            return;
        }

        currentIncidentId = incidentId;

        // Populate incident details
        document.getElementById('incidentId').textContent = incident.id;
        document.getElementById('incidentIdHeader').textContent = incident.id;
        document.getElementById('incidentType').textContent = incident.type;
        document.getElementById('incidentDetected').textContent = incident.detected;
        document.getElementById('incidentUser').textContent = incident.user.email;

        // Severity badge
        const severityBadge = document.getElementById('incidentSeverityBadge');
        severityBadge.textContent = incident.severity.toUpperCase();
        severityBadge.className = 'badge badge-' + (incident.severity === 'critical' ? 'critical' : 'high');

        // Status badge
        const statusBadge = document.getElementById('incidentStatusBadge');
        statusBadge.textContent = incident.status.charAt(0).toUpperCase() + incident.status.slice(1);

        // Alert box
        document.getElementById('incidentAlertTitle').textContent =
            incident.severity === 'critical' ? 'CRITICAL DATA LEAK DETECTED' : 'HIGH SEVERITY INCIDENT DETECTED';
        document.getElementById('incidentAlertDesc').textContent = incident.description;

        // Email info
        document.getElementById('incidentSubject').textContent = incident.email.subject;
        document.getElementById('incidentFrom').textContent = incident.email.from;
        document.getElementById('incidentTo').textContent = incident.email.to;
        document.getElementById('incidentAttachments').textContent = incident.email.attachments;

        // User info
        document.getElementById('incidentUserName').textContent = incident.user.name;
        document.getElementById('incidentDepartment').textContent = incident.user.department;
        document.getElementById('incidentManager').textContent = incident.user.manager;
        document.getElementById('incidentPrevious').textContent = incident.user.previousIncidents;

        // Actions taken
        let actionsHtml = '';
        incident.actionsTaken.forEach(action => {
            actionsHtml += `<div style="display: flex; gap: 10px; align-items: center; margin-bottom: 10px;">
                <span style="color: var(--success);">✓</span> ${action}
            </div>`;
        });
        actionsHtml += `<div id="managerNotifyStatus" style="display: flex; gap: 10px; align-items: center;">
            <span style="color: ${incident.managerNotified ? 'var(--success)' : 'var(--warning)'};">
                ${incident.managerNotified ? '✓' : '○'}
            </span> Manager notification ${incident.managerNotified ? 'sent' : 'pending'}
        </div>`;
        document.getElementById('incidentActionsList').innerHTML = actionsHtml;

        // Notes
        document.getElementById('incidentNotes').value = incident.notes || '';

        openModal('incidentModal');
    }

    function notifyManager() {
        const incident = incidentsData[currentIncidentId] || incidentsData['INC-001'];

        // Populate notify manager modal
        document.getElementById('notifyMessage').value = `Dear ${incident.user.manager},

A data leak incident has been detected involving an employee in your department.

Incident ID: ${incident.id}
Employee: ${incident.user.email}
Severity: ${incident.severity.toUpperCase()}
Type: ${incident.type}
Detected: ${incident.detected}

Description: ${incident.description}

Please review this incident and take appropriate action.

Regards,
DLP Security Team`;

        openModal('notifyManagerModal');
    }

    function sendManagerNotification() {
        const manager = document.getElementById('managerSelect').value;
        const priority = document.getElementById('notifyPriority').value;
        const cc = document.getElementById('notifyCc').value;
        const message = document.getElementById('notifyMessage').value;
        const includeDetails = document.getElementById('notifyIncludeDetails').checked;

        if (!message.trim()) {
            showToast('Please enter a message', 'error');
            return;
        }

        // Simulate sending
        showToast('Sending notification...', 'info');

        setTimeout(() => {
            // Update incident data
            if (incidentsData[currentIncidentId]) {
                incidentsData[currentIncidentId].managerNotified = true;
                incidentsData[currentIncidentId].actionsTaken.push('Manager notified: ' + manager);
            }

            // Update the actions list in incident modal
            const statusEl = document.getElementById('managerNotifyStatus');
            if (statusEl) {
                statusEl.innerHTML = '<span style="color: var(--success);">✓</span> Manager notified';
            }

            closeModal('notifyManagerModal');
            showToast('Manager notification sent to ' + manager, 'success');

            // Log the action
            console.log('Notification sent:', { manager, priority, cc, includeDetails, message });
        }, 1500);
    }

    function suspendUser() {
        const incident = incidentsData[currentIncidentId] || incidentsData['INC-001'];

        // Populate suspend modal
        document.getElementById('suspendUsername').textContent = incident.user.username;
        document.getElementById('suspendEmail').textContent = incident.user.email;
        document.getElementById('suspendDepartment').textContent = incident.user.department;
        document.getElementById('suspendEmployeeId').textContent = incident.user.employeeId;

        openModal('suspendUserModal');
    }

    function confirmSuspendUser() {
        const duration = document.getElementById('suspendDuration').value;
        const reason = document.getElementById('suspendReason').value;
        const notes = document.getElementById('suspendNotes').value;
        const incident = incidentsData[currentIncidentId] || incidentsData['INC-001'];

        const durationText = {
            '24h': '24 hours',
            '72h': '72 hours',
            '1week': '1 week',
            'indefinite': 'indefinitely'
        };

        if (!confirm(`Are you sure you want to suspend ${incident.user.name}'s account ${durationText[duration]}?\n\nThis will immediately revoke access to all company systems.`)) {
            return;
        }

        showToast('Suspending user account...', 'info');

        setTimeout(() => {
            // Update incident data
            if (incidentsData[currentIncidentId]) {
                incidentsData[currentIncidentId].actionsTaken.push('User account suspended (' + durationText[duration] + ')');
            }

            // Update button to show completed
            const btn = document.getElementById('btnSuspendUser');
            btn.innerHTML = '<span style="font-size: 24px; margin-bottom: 5px;">✓</span><span>User Suspended</span>';
            btn.style.background = 'var(--danger)';
            btn.style.color = 'white';
            btn.disabled = true;

            closeModal('suspendUserModal');
            showToast(`User ${incident.user.email} has been suspended ${durationText[duration]}`, 'warning');

            // Log the action
            console.log('User suspended:', { user: incident.user.email, duration, reason, notes });
        }, 2000);
    }

    function revokeAccess() {
        const incident = incidentsData[currentIncidentId] || incidentsData['INC-001'];

        // Populate revoke modal
        document.getElementById('revokeUserName').textContent = incident.user.name;
        document.getElementById('revokeUserEmail').textContent = incident.user.email;

        openModal('revokeAccessModal');
    }

    function confirmRevokeAccess() {
        const incident = incidentsData[currentIncidentId] || incidentsData['INC-001'];

        const revokedItems = [];
        if (document.getElementById('revokeEmail').checked) revokedItems.push('External Email');
        if (document.getElementById('revokeAttachments').checked) revokedItems.push('Attachment Sending');
        if (document.getElementById('revokeCloudStorage').checked) revokedItems.push('Cloud Storage Sharing');
        if (document.getElementById('revokeUsb').checked) revokedItems.push('USB/Removable Media');
        if (document.getElementById('revokePrinting').checked) revokedItems.push('Printing');
        if (document.getElementById('revokeDownload').checked) revokedItems.push('File Downloads');

        if (revokedItems.length === 0) {
            showToast('Please select at least one access type to revoke', 'error');
            return;
        }

        const duration = document.getElementById('revokeDuration').value;

        if (!confirm(`Revoke the following access for ${incident.user.name}?\n\n- ${revokedItems.join('\n- ')}\n\nDuration: ${duration}`)) {
            return;
        }

        showToast('Revoking access...', 'info');

        setTimeout(() => {
            // Update incident data
            if (incidentsData[currentIncidentId]) {
                incidentsData[currentIncidentId].actionsTaken.push('Access revoked: ' + revokedItems.join(', '));
            }

            // Update button to show completed
            const btn = document.getElementById('btnRevokeAccess');
            btn.innerHTML = '<span style="font-size: 24px; margin-bottom: 5px;">✓</span><span>Access Revoked</span>';
            btn.style.background = 'var(--warning)';
            btn.style.color = '#1a1a2e';
            btn.disabled = true;

            closeModal('revokeAccessModal');
            showToast(`Access revoked for ${incident.user.email}: ${revokedItems.join(', ')}`, 'warning');

            // Log the action
            console.log('Access revoked:', { user: incident.user.email, items: revokedItems, duration });
        }, 1500);
    }

    function generateIncidentReport() {
        const incident = incidentsData[currentIncidentId] || incidentsData['INC-001'];
        const notes = document.getElementById('incidentNotes').value;

        showToast('Generating incident report...', 'info');

        setTimeout(() => {
            let report = `
╔══════════════════════════════════════════════════════════════════════════════╗
║                      INCIDENT INVESTIGATION REPORT                            ║
╚══════════════════════════════════════════════════════════════════════════════╝

Report Generated: ${new Date().toISOString()}
Classification: CONFIDENTIAL - FOR AUTHORIZED PERSONNEL ONLY

═══════════════════════════════════════════════════════════════════════════════
                           INCIDENT SUMMARY
═══════════════════════════════════════════════════════════════════════════════

Incident ID:        ${incident.id}
Type:               ${incident.type}
Severity:           ${incident.severity.toUpperCase()}
Status:             ${incident.status.toUpperCase()}
Detected:           ${incident.detected}

Description:
${incident.description}

═══════════════════════════════════════════════════════════════════════════════
                           USER INFORMATION
═══════════════════════════════════════════════════════════════════════════════

Name:               ${incident.user.name}
Email:              ${incident.user.email}
Department:         ${incident.user.department}
Employee ID:        ${incident.user.employeeId}
Manager:            ${incident.user.manager}
Previous Incidents: ${incident.user.previousIncidents}

═══════════════════════════════════════════════════════════════════════════════
                           EMAIL DETAILS
═══════════════════════════════════════════════════════════════════════════════

Subject:            ${incident.email.subject}
From:               ${incident.email.from}
To:                 ${incident.email.to}
Attachments:        ${incident.email.attachments}

═══════════════════════════════════════════════════════════════════════════════
                           ACTIONS TAKEN
═══════════════════════════════════════════════════════════════════════════════

${incident.actionsTaken.map((a, i) => `${i + 1}. ${a}`).join('\n')}

═══════════════════════════════════════════════════════════════════════════════
                        INVESTIGATION NOTES
═══════════════════════════════════════════════════════════════════════════════

${notes || 'No additional notes recorded.'}

═══════════════════════════════════════════════════════════════════════════════
                           RECOMMENDATIONS
═══════════════════════════════════════════════════════════════════════════════

Based on the incident analysis, the following actions are recommended:

1. ${incident.severity === 'critical' ? 'Immediate escalation to CISO and Legal team' : 'Review with department manager'}
2. ${incident.user.previousIncidents > 0 ? 'Consider progressive disciplinary action due to repeated incidents' : 'First-time incident - issue formal warning'}
3. Mandatory DLP awareness training for the user
4. Review and strengthen data handling policies for the department
5. ${incident.type.includes('Forward') ? 'Implement additional controls on external email forwarding' : 'Enhance monitoring for this data category'}

═══════════════════════════════════════════════════════════════════════════════
                              END OF REPORT
═══════════════════════════════════════════════════════════════════════════════

Generated by: DLP Security System v2.5
Approved by: ___________________________  Date: _______________
`;

            // Update incident data with notes
            if (incidentsData[currentIncidentId]) {
                incidentsData[currentIncidentId].notes = notes;
                incidentsData[currentIncidentId].actionsTaken.push('Incident report generated');
            }

            // Download report
            const blob = new Blob([report], { type: 'text/plain' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `Incident_Report_${incident.id}_${new Date().toISOString().split('T')[0]}.txt`;
            a.click();
            window.URL.revokeObjectURL(url);

            // Update button
            const btn = document.getElementById('btnGenerateReport');
            btn.innerHTML = '<span style="font-size: 24px; margin-bottom: 5px;">✓</span><span>Report Generated</span>';
            btn.style.background = 'var(--success)';
            btn.style.color = 'white';

            showToast('Incident report generated and downloaded', 'success');
        }, 2000);
    }

    function escalateIncident() {
        const incident = incidentsData[currentIncidentId] || incidentsData['INC-001'];

        if (!confirm(`Escalate incident ${incident.id} to Security Leadership and CISO?\n\nThis will:\n- Alert the CISO office\n- Notify VP of Security\n- Flag for legal review\n- Create high-priority case`)) {
            return;
        }

        showToast('Escalating incident...', 'info');

        setTimeout(() => {
            if (incidentsData[currentIncidentId]) {
                incidentsData[currentIncidentId].status = 'escalated';
                incidentsData[currentIncidentId].actionsTaken.push('Escalated to Security Leadership');
            }

            showToast('Incident escalated to Security Leadership and CISO', 'warning');
            closeModal('incidentModal');
        }, 1500);
    }

    function resolveIncident() {
        const incident = incidentsData[currentIncidentId] || incidentsData['INC-001'];
        const notes = document.getElementById('incidentNotes').value;

        if (!confirm(`Mark incident ${incident.id} as resolved?\n\nMake sure all necessary actions have been taken.`)) {
            return;
        }

        showToast('Resolving incident...', 'info');

        setTimeout(() => {
            if (incidentsData[currentIncidentId]) {
                incidentsData[currentIncidentId].status = 'resolved';
                incidentsData[currentIncidentId].notes = notes;
                incidentsData[currentIncidentId].actionsTaken.push('Incident resolved');
            }

            showToast('Incident ' + incident.id + ' marked as resolved', 'success');
            closeModal('incidentModal');
        }, 1000);
    }

    // Rule Functions
    function editRule(ruleId) {
        showToast('Opening rule editor for rule ' + ruleId, 'info');
    }

    function testRule(ruleId) {
        showToast('Testing rule ' + ruleId + '...', 'info');
        setTimeout(() => showToast('Rule test completed: 15 matches found'), 2000);
    }

    function disableRule(ruleId) {
        if (confirm('Disable this detection rule?')) {
            showToast('Rule ' + ruleId + ' disabled', 'warning');
        }
    }

    function openCreateRuleModal() {
        showToast('Opening rule creator...', 'info');
    }

    // Policy Data
    const policiesData = {
        1: {
            id: 1,
            name: 'Block External Financial Data',
            description: 'Block all emails containing financial data sent to non-company domains',
            action: 'block',
            severity: 'critical',
            applyTo: { outbound: true, inbound: false, internal: false, attachments: true },
            rules: ['financial', 'confidential'],
            exceptions: 'finance-auditors@company.com, legal@company.com',
            status: true,
            stats: { matches: 1247, blocked: 1198, falsePositives: 12 }
        },
        2: {
            id: 2,
            name: 'Quarantine PII Data',
            description: 'Quarantine emails containing SSN, credit cards, or personal information for review',
            action: 'quarantine',
            severity: 'high',
            applyTo: { outbound: true, inbound: true, internal: false, attachments: true },
            rules: ['ssn', 'creditcard', 'pii'],
            exceptions: 'hr@company.com',
            status: true,
            stats: { matches: 856, blocked: 743, falsePositives: 45 }
        },
        3: {
            id: 3,
            name: 'Flag Source Code Transfers',
            description: 'Flag emails with source code attachments for security review',
            action: 'flag',
            severity: 'medium',
            applyTo: { outbound: true, inbound: false, internal: true, attachments: true },
            rules: ['sourcecode'],
            exceptions: 'github-notifications@company.com',
            status: true,
            stats: { matches: 324, blocked: 0, falsePositives: 28 }
        },
        4: {
            id: 4,
            name: 'Executive Communications Monitor',
            description: 'Log and track all C-level executive external communications',
            action: 'log',
            severity: 'low',
            applyTo: { outbound: true, inbound: true, internal: false, attachments: false },
            rules: [],
            exceptions: '',
            status: true,
            stats: { matches: 2156, blocked: 0, falsePositives: 0 }
        },
        5: {
            id: 5,
            name: 'Competitor Leak Tracking Policy',
            description: 'Track and block all emails sent to competitor domains. Full chain tracking to last recipient delivery.',
            action: 'block',
            severity: 'critical',
            applyTo: { outbound: true, inbound: false, internal: false, attachments: true },
            rules: ['financial', 'sourcecode', 'confidential', 'pii'],
            exceptions: '',
            status: true,
            competitorDomains: ['competitor.com', 'rival-tech.com', 'marketcomp.io', 'techrivals.net', 'industryspy.com', 'competitorhq.com'],
            trackChain: true,
            alertOnDetection: true,
            stats: { matches: 47, blocked: 12, leaksDetected: 4, chainsTracked: 15 }
        }
    };

    let nextPolicyId = 6;

    // Policy Functions
    function openCreatePolicyModal() {
        // Reset form
        document.getElementById('createPolicyForm').reset();
        document.getElementById('newPolicyActive').checked = true;
        document.getElementById('applyOutbound').checked = true;
        document.getElementById('applyAttachments').checked = true;

        // Uncheck all rules
        document.querySelectorAll('input[name="policyRule"]').forEach(cb => cb.checked = false);

        openModal('createPolicyModal');
    }

    function saveNewPolicy() {
        const name = document.getElementById('newPolicyName').value.trim();
        const description = document.getElementById('newPolicyDescription').value.trim();
        const action = document.getElementById('newPolicyAction').value;
        const severity = document.getElementById('newPolicySeverity').value;

        if (!name || !description || !action || !severity) {
            showToast('Please fill in all required fields', 'error');
            return;
        }

        // Gather selected rules
        const selectedRules = [];
        document.querySelectorAll('input[name="policyRule"]:checked').forEach(cb => {
            selectedRules.push(cb.value);
        });

        // Create new policy object
        const newPolicy = {
            id: nextPolicyId,
            name: name,
            description: description,
            action: action,
            severity: severity,
            applyTo: {
                outbound: document.getElementById('applyOutbound').checked,
                inbound: document.getElementById('applyInbound').checked,
                internal: document.getElementById('applyInternal').checked,
                attachments: document.getElementById('applyAttachments').checked
            },
            rules: selectedRules,
            exceptions: document.getElementById('newPolicyExceptions').value.trim(),
            status: document.getElementById('newPolicyActive').checked,
            stats: { matches: 0, blocked: 0, falsePositives: 0 }
        };

        policiesData[nextPolicyId] = newPolicy;
        nextPolicyId++;

        // Add to UI
        addPolicyToUI(newPolicy);

        closeModal('createPolicyModal');
        showToast('Policy "' + name + '" created successfully', 'success');
    }

    function addPolicyToUI(policy) {
        const actionColors = {
            'block': 'var(--danger)',
            'quarantine': 'var(--warning)',
            'flag': 'var(--info)',
            'log': 'var(--success)',
            'encrypt': 'var(--primary)',
            'notify': '#8b5cf6'
        };

        const actionBadges = {
            'block': 'badge-critical',
            'quarantine': 'badge-high',
            'flag': 'badge-medium',
            'log': 'badge-low',
            'encrypt': 'badge-medium',
            'notify': 'badge-low'
        };

        const policyHTML = `
            <div id="policy-${policy.id}" style="padding: 15px; background: var(--bg-lighter); border-radius: 8px; border-left: 4px solid ${actionColors[policy.action]};">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <strong>${policy.name}</strong>
                        <p style="font-size: 12px; color: var(--text-muted); margin-top: 5px;">${policy.description}</p>
                    </div>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <span class="badge ${actionBadges[policy.action]}">${policy.action.toUpperCase()}</span>
                        <button class="btn btn-sm btn-secondary" onclick="editPolicy(${policy.id})">Edit</button>
                    </div>
                </div>
            </div>
        `;

        const container = document.querySelector('#policies .card-body > div');
        container.insertAdjacentHTML('beforeend', policyHTML);
    }

    function editPolicy(policyId) {
        const policy = policiesData[policyId];
        if (!policy) {
            showToast('Policy not found', 'error');
            return;
        }

        // Populate form
        document.getElementById('editPolicyId').value = policy.id;
        document.getElementById('editPolicyTitle').textContent = policy.name;
        document.getElementById('editPolicyName').value = policy.name;
        document.getElementById('editPolicyDescription').value = policy.description;
        document.getElementById('editPolicyAction').value = policy.action;
        document.getElementById('editPolicySeverity').value = policy.severity;
        document.getElementById('editPolicyExceptions').value = policy.exceptions || '';

        // Apply To checkboxes
        document.getElementById('editApplyOutbound').checked = policy.applyTo.outbound;
        document.getElementById('editApplyInbound').checked = policy.applyTo.inbound;
        document.getElementById('editApplyInternal').checked = policy.applyTo.internal;
        document.getElementById('editApplyAttachments').checked = policy.applyTo.attachments;

        // Detection Rules
        document.querySelectorAll('input[name="editPolicyRule"]').forEach(cb => {
            cb.checked = policy.rules.includes(cb.value);
        });

        // Status toggle
        document.getElementById('editPolicyStatus').checked = policy.status;
        updateToggleSlider('editPolicyStatus');

        // Statistics
        document.getElementById('editPolicyMatches').textContent = policy.stats.matches.toLocaleString();
        document.getElementById('editPolicyBlocked').textContent = policy.stats.blocked.toLocaleString();
        document.getElementById('editPolicyFalsePositives').textContent = policy.stats.falsePositives.toLocaleString();

        openModal('editPolicyModal');
    }

    function saveEditedPolicy() {
        const policyId = parseInt(document.getElementById('editPolicyId').value);
        const policy = policiesData[policyId];

        if (!policy) {
            showToast('Policy not found', 'error');
            return;
        }

        const name = document.getElementById('editPolicyName').value.trim();
        const description = document.getElementById('editPolicyDescription').value.trim();

        if (!name || !description) {
            showToast('Please fill in all required fields', 'error');
            return;
        }

        // Update policy data
        policy.name = name;
        policy.description = description;
        policy.action = document.getElementById('editPolicyAction').value;
        policy.severity = document.getElementById('editPolicySeverity').value;
        policy.applyTo = {
            outbound: document.getElementById('editApplyOutbound').checked,
            inbound: document.getElementById('editApplyInbound').checked,
            internal: document.getElementById('editApplyInternal').checked,
            attachments: document.getElementById('editApplyAttachments').checked
        };
        policy.exceptions = document.getElementById('editPolicyExceptions').value.trim();
        policy.status = document.getElementById('editPolicyStatus').checked;

        // Gather selected rules
        policy.rules = [];
        document.querySelectorAll('input[name="editPolicyRule"]:checked').forEach(cb => {
            policy.rules.push(cb.value);
        });

        // Update UI
        updatePolicyInUI(policy);

        closeModal('editPolicyModal');
        showToast('Policy "' + policy.name + '" updated successfully', 'success');
    }

    function updatePolicyInUI(policy) {
        const actionColors = {
            'block': 'var(--danger)',
            'quarantine': 'var(--warning)',
            'flag': 'var(--info)',
            'log': 'var(--success)',
            'encrypt': 'var(--primary)',
            'notify': '#8b5cf6'
        };

        const actionBadges = {
            'block': 'badge-critical',
            'quarantine': 'badge-high',
            'flag': 'badge-medium',
            'log': 'badge-low',
            'encrypt': 'badge-medium',
            'notify': 'badge-low'
        };

        const policyEl = document.getElementById('policy-' + policy.id);
        if (policyEl) {
            policyEl.style.borderLeftColor = actionColors[policy.action];
            policyEl.style.opacity = policy.status ? '1' : '0.5';
            policyEl.querySelector('strong').textContent = policy.name;
            policyEl.querySelector('p').textContent = policy.description;
            policyEl.querySelector('.badge').className = 'badge ' + actionBadges[policy.action];
            policyEl.querySelector('.badge').textContent = policy.action.toUpperCase();
        }
    }

    function deletePolicy() {
        const policyId = parseInt(document.getElementById('editPolicyId').value);
        const policy = policiesData[policyId];

        if (!policy) {
            showToast('Policy not found', 'error');
            return;
        }

        if (!confirm('Are you sure you want to delete the policy "' + policy.name + '"? This action cannot be undone.')) {
            return;
        }

        // Remove from data
        delete policiesData[policyId];

        // Remove from UI
        const policyEl = document.getElementById('policy-' + policyId);
        if (policyEl) {
            policyEl.remove();
        }

        closeModal('editPolicyModal');
        showToast('Policy deleted successfully', 'warning');
    }

    function updateToggleSlider(checkboxId) {
        const checkbox = document.getElementById(checkboxId);
        const slider = checkbox.nextElementSibling;
        if (checkbox.checked) {
            slider.style.backgroundColor = 'var(--success)';
        } else {
            slider.style.backgroundColor = '#475569';
        }
    }

    // Toggle switch event listener
    document.addEventListener('DOMContentLoaded', function() {
        const toggleCheckbox = document.getElementById('editPolicyStatus');
        if (toggleCheckbox) {
            toggleCheckbox.addEventListener('change', function() {
                updateToggleSlider('editPolicyStatus');
            });
        }
    });

    // Competitor Leak Tracking Data
    const competitorLeaksData = {
        'CLK-001': {
            leak_id: 'CLK-001',
            status: 'confirmed_leak',
            severity: 'critical',
            competitor: 'Acme Corp',
            competitor_domain: 'competitor.com',
            data_type: 'Financial Data',
            original_sender: 'john.smith@company.com',
            original_subject: 'Q4 Financial Report - Confidential',
            detected_at: '2025-01-26 14:30:05',
            risk_score: 98,
            chain: [
                { hop: 1, from: 'john.smith@company.com', to: 'finance.team@company.com', time: '2025-01-26 09:15:00', action: 'Sent', location: 'Internal', ip: '192.168.1.45', delivered: true },
                { hop: 2, from: 'mary.wilson@company.com', to: 'external.consultant@advisory.com', time: '2025-01-26 11:30:00', action: 'Forwarded', location: 'External', ip: '192.168.1.67', delivered: true },
                { hop: 3, from: 'external.consultant@advisory.com', to: 'contact@competitor.com', time: '2025-01-26 14:28:00', action: 'Forwarded', location: 'Competitor', ip: '45.67.89.123', delivered: true },
                { hop: 4, from: 'contact@competitor.com', to: 'strategy@competitor.com', time: '2025-01-26 14:45:00', action: 'Internal Forward', location: 'Competitor Internal', ip: '45.67.89.150', delivered: true }
            ],
            attachments: ['Q4_Financial_Report.xlsx', 'Revenue_Forecast_2025.pdf'],
            data_exposed: ['Revenue figures', 'Profit margins', 'Growth projections', 'Market strategy'],
            investigation_notes: '',
            actions_taken: []
        },
        'CLK-002': {
            leak_id: 'CLK-002',
            status: 'investigating',
            severity: 'high',
            competitor: 'Rival Technologies',
            competitor_domain: 'rival-tech.com',
            data_type: 'Product Roadmap',
            original_sender: 'product@company.com',
            original_subject: 'Product Roadmap 2025-2026',
            detected_at: '2025-01-25 16:45:22',
            risk_score: 89,
            chain: [
                { hop: 1, from: 'product@company.com', to: 'engineering@company.com', time: '2025-01-25 10:00:00', action: 'Sent', location: 'Internal', ip: '192.168.1.89', delivered: true },
                { hop: 2, from: 'dev.lead@company.com', to: 'contractor@freelance.io', time: '2025-01-25 14:30:00', action: 'Forwarded', location: 'External', ip: '192.168.1.92', delivered: true },
                { hop: 3, from: 'contractor@freelance.io', to: 'jobs@rival-tech.com', time: '2025-01-25 16:42:00', action: 'Forwarded', location: 'Competitor', ip: '78.90.12.34', delivered: true }
            ],
            attachments: ['Roadmap_2025.pptx'],
            data_exposed: ['Feature plans', 'Release dates', 'Technology stack'],
            investigation_notes: '',
            actions_taken: []
        },
        'CLK-003': {
            leak_id: 'CLK-003',
            status: 'blocked',
            severity: 'critical',
            competitor: 'Market Comp Inc',
            competitor_domain: 'marketcomp.io',
            data_type: 'Customer List',
            original_sender: 'sales@company.com',
            original_subject: 'Enterprise Customer Database',
            detected_at: '2025-01-26 11:20:15',
            risk_score: 95,
            chain: [
                { hop: 1, from: 'sales@company.com', to: 'sales.manager@company.com', time: '2025-01-26 09:00:00', action: 'Sent', location: 'Internal', ip: '192.168.1.55', delivered: true },
                { hop: 2, from: 'sales.manager@company.com', to: 'hr@marketcomp.io', time: '2025-01-26 11:18:00', action: 'Attempted', location: 'Competitor', ip: '192.168.1.55', delivered: false }
            ],
            attachments: ['Customer_Database.csv'],
            data_exposed: ['Blocked before delivery'],
            investigation_notes: 'Attempt blocked by DLP policy. User notified.',
            actions_taken: ['Email blocked', 'User warned', 'Manager notified']
        },
        'CLK-004': {
            leak_id: 'CLK-004',
            status: 'confirmed_leak',
            severity: 'high',
            competitor: 'Tech Rivals LLC',
            competitor_domain: 'techrivals.net',
            data_type: 'Source Code',
            original_sender: 'dev.team@company.com',
            original_subject: 'API Integration Code',
            detected_at: '2025-01-24 09:30:00',
            risk_score: 92,
            chain: [
                { hop: 1, from: 'dev.team@company.com', to: 'qa.team@company.com', time: '2025-01-23 15:00:00', action: 'Sent', location: 'Internal', ip: '192.168.1.78', delivered: true },
                { hop: 2, from: 'qa.analyst@company.com', to: 'personal@gmail.com', time: '2025-01-23 18:45:00', action: 'Forwarded', location: 'Personal', ip: '192.168.1.78', delivered: true },
                { hop: 3, from: 'personal@gmail.com', to: 'hiring@techrivals.net', time: '2025-01-24 09:28:00', action: 'Forwarded', location: 'Competitor', ip: '98.76.54.32', delivered: true }
            ],
            attachments: ['api_integration.zip', 'auth_module.py'],
            data_exposed: ['Authentication logic', 'API endpoints', 'Database schema'],
            investigation_notes: '',
            actions_taken: []
        }
    };

    const competitorDomainsData = {
        'competitor.com': { name: 'Acme Corp', threat_level: 'critical', industry: 'Direct Competitor', added: '2024-06-15', incidents: 5 },
        'rival-tech.com': { name: 'Rival Technologies', threat_level: 'critical', industry: 'Direct Competitor', added: '2024-07-20', incidents: 3 },
        'marketcomp.io': { name: 'Market Comp Inc', threat_level: 'high', industry: 'Market Competitor', added: '2024-08-10', incidents: 2 },
        'techrivals.net': { name: 'Tech Rivals LLC', threat_level: 'high', industry: 'Technology', added: '2024-09-05', incidents: 1 },
        'industryspy.com': { name: 'Industry Analytics', threat_level: 'critical', industry: 'Intelligence', added: '2024-10-12', incidents: 0 },
        'competitorhq.com': { name: 'Competitor HQ', threat_level: 'medium', industry: 'Consulting', added: '2024-11-28', incidents: 0 }
    };

    // Competitor Leak Functions
    function investigateCompetitorLeak(leakId) {
        const leak = competitorLeaksData[leakId];
        if (!leak) {
            showToast('Leak record not found', 'error');
            return;
        }

        // Build investigation modal content
        let chainHTML = leak.chain.map((hop, index) => {
            const locationColor = hop.location === 'Internal' ? 'var(--success)' :
                                  (hop.location.includes('Competitor') ? 'var(--danger)' : 'var(--warning)');
            return `
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                    <div style="width: 30px; height: 30px; background: ${locationColor}; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 12px;">${hop.hop}</div>
                    <div style="flex: 1; padding: 10px; background: var(--bg-dark); border-radius: 8px; border-left: 3px solid ${locationColor};">
                        <div style="font-size: 12px; color: var(--text-muted);">${hop.time} - ${hop.action}</div>
                        <div style="font-size: 13px;"><strong>From:</strong> ${hop.from}</div>
                        <div style="font-size: 13px;"><strong>To:</strong> ${hop.to}</div>
                        <div style="font-size: 11px; margin-top: 5px;">
                            <span style="padding: 2px 6px; background: ${locationColor}; color: white; border-radius: 4px;">${hop.location}</span>
                            <span style="margin-left: 10px;">IP: ${hop.ip}</span>
                            ${hop.delivered ? '<span style="color: var(--success); margin-left: 10px;">✓ Delivered</span>' : '<span style="color: var(--danger); margin-left: 10px;">✗ Blocked</span>'}
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        const modalContent = `
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div style="background: var(--bg-lighter); padding: 15px; border-radius: 8px;">
                    <h4 style="margin-bottom: 15px; color: var(--danger);">🎯 Leak Details</h4>
                    <div style="margin-bottom: 8px;"><strong>Leak ID:</strong> ${leak.leak_id}</div>
                    <div style="margin-bottom: 8px;"><strong>Status:</strong> <span class="badge badge-${leak.status === 'confirmed_leak' ? 'critical' : (leak.status === 'investigating' ? 'high' : 'medium')}">${leak.status.replace('_', ' ').toUpperCase()}</span></div>
                    <div style="margin-bottom: 8px;"><strong>Competitor:</strong> <span style="color: var(--danger);">${leak.competitor}</span></div>
                    <div style="margin-bottom: 8px;"><strong>Domain:</strong> ${leak.competitor_domain}</div>
                    <div style="margin-bottom: 8px;"><strong>Data Type:</strong> ${leak.data_type}</div>
                    <div style="margin-bottom: 8px;"><strong>Risk Score:</strong> <span style="color: ${leak.risk_score > 90 ? 'var(--danger)' : 'var(--warning)'}; font-weight: bold;">${leak.risk_score}/100</span></div>
                    <div><strong>Detected:</strong> ${leak.detected_at}</div>
                </div>
                <div style="background: var(--bg-lighter); padding: 15px; border-radius: 8px;">
                    <h4 style="margin-bottom: 15px;">📧 Original Email</h4>
                    <div style="margin-bottom: 8px;"><strong>Subject:</strong> ${leak.original_subject}</div>
                    <div style="margin-bottom: 8px;"><strong>Original Sender:</strong> ${leak.original_sender}</div>
                    <div style="margin-bottom: 15px;"><strong>Attachments:</strong></div>
                    <div style="display: flex; flex-wrap: wrap; gap: 5px;">
                        ${leak.attachments.map(a => `<span style="padding: 4px 8px; background: var(--bg-dark); border-radius: 4px; font-size: 11px;">📎 ${a}</span>`).join('')}
                    </div>
                </div>
            </div>

            <div style="background: var(--bg-lighter); padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <h4 style="margin-bottom: 15px;">🔗 Complete Delivery Chain (${leak.chain.length} hops to final recipient)</h4>
                ${chainHTML}
            </div>

            <div style="background: rgba(239,68,68,0.1); padding: 15px; border-radius: 8px; border: 1px solid var(--danger); margin-bottom: 20px;">
                <h4 style="margin-bottom: 10px; color: var(--danger);">⚠️ Data Exposed to Competitor</h4>
                <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                    ${leak.data_exposed.map(d => `<span style="padding: 5px 10px; background: rgba(239,68,68,0.2); border-radius: 4px; color: #ff6b6b;">${d}</span>`).join('')}
                </div>
            </div>

            <div style="background: var(--bg-lighter); padding: 15px; border-radius: 8px;">
                <h4 style="margin-bottom: 10px;">📝 Investigation Notes</h4>
                <textarea id="investigationNotes" class="form-input" rows="3" placeholder="Add investigation notes...">${leak.investigation_notes || ''}</textarea>
            </div>
        `;

        // Create and show modal
        showDynamicModal('Investigate Competitor Leak: ' + leak.leak_id, modalContent, [
            { label: 'Mark as Resolved', class: 'btn-success', onclick: () => resolveCompetitorLeak(leakId) },
            { label: 'Escalate to Legal', class: 'btn-warning', onclick: () => escalateCompetitorLeak(leakId) },
            { label: 'Generate Report', class: 'btn-secondary', onclick: () => generateLeakReport(leakId) },
            { label: 'Close', class: 'btn-secondary', onclick: () => closeDynamicModal() }
        ]);
    }

    function showDynamicModal(title, content, buttons) {
        // Remove existing dynamic modal if any
        const existing = document.getElementById('dynamicModal');
        if (existing) existing.remove();

        const buttonsHTML = buttons.map(b =>
            `<button class="btn ${b.class}" onclick="${b.onclick.toString().includes('=>') ? b.onclick.toString().split('=>')[1].trim() : b.onclick.name + '()'}">${b.label}</button>`
        ).join('');

        const modalHTML = `
            <div id="dynamicModal" class="dlp-modal active">
                <div class="dlp-modal-overlay" onclick="closeDynamicModal()"></div>
                <div class="dlp-modal-content" style="max-width: 900px; max-height: 90vh; overflow-y: auto;">
                    <div class="dlp-modal-header">
                        <h3>${title}</h3>
                        <button class="dlp-modal-close" onclick="closeDynamicModal()">&times;</button>
                    </div>
                    <div class="dlp-modal-body">
                        ${content}
                    </div>
                    <div class="dlp-modal-footer">
                        ${buttonsHTML}
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHTML);
    }

    function closeDynamicModal() {
        const modal = document.getElementById('dynamicModal');
        if (modal) modal.remove();
    }

    function escalateCompetitorLeak(leakId) {
        const leak = competitorLeaksData[leakId];
        if (!leak) return;

        if (confirm(`Escalate leak ${leakId} to Legal and Executive team?\n\nThis will:\n- Notify Legal Department\n- Alert C-level executives\n- Initiate formal investigation\n- Preserve all evidence`)) {
            leak.actions_taken.push('Escalated to Legal - ' + new Date().toISOString());
            showToast(`Leak ${leakId} escalated to Legal and Executive team`, 'warning');
            closeDynamicModal();
        }
    }

    function resolveCompetitorLeak(leakId) {
        const leak = competitorLeaksData[leakId];
        if (!leak) return;

        const notes = document.getElementById('investigationNotes')?.value || '';

        if (confirm(`Mark leak ${leakId} as resolved?\n\nThis will close the investigation.`)) {
            leak.status = 'resolved';
            leak.investigation_notes = notes;
            leak.actions_taken.push('Resolved - ' + new Date().toISOString());
            showToast(`Leak ${leakId} marked as resolved`, 'success');
            closeDynamicModal();
        }
    }

    function generateLeakReport(leakId) {
        const leak = competitorLeaksData[leakId];
        if (!leak) return;

        showToast('Generating comprehensive leak report...', 'info');

        setTimeout(() => {
            let report = `COMPETITOR LEAK INVESTIGATION REPORT\n`;
            report += `====================================\n\n`;
            report += `Leak ID: ${leak.leak_id}\n`;
            report += `Status: ${leak.status}\n`;
            report += `Severity: ${leak.severity.toUpperCase()}\n`;
            report += `Risk Score: ${leak.risk_score}/100\n\n`;
            report += `COMPETITOR DETAILS\n`;
            report += `-----------------\n`;
            report += `Name: ${leak.competitor}\n`;
            report += `Domain: ${leak.competitor_domain}\n\n`;
            report += `ORIGINAL EMAIL\n`;
            report += `--------------\n`;
            report += `Subject: ${leak.original_subject}\n`;
            report += `Sender: ${leak.original_sender}\n`;
            report += `Detected: ${leak.detected_at}\n\n`;
            report += `DELIVERY CHAIN (${leak.chain.length} hops)\n`;
            report += `-----------------------------\n`;
            leak.chain.forEach(hop => {
                report += `Hop ${hop.hop}: ${hop.from} → ${hop.to}\n`;
                report += `  Time: ${hop.time}\n`;
                report += `  Location: ${hop.location}\n`;
                report += `  IP: ${hop.ip}\n`;
                report += `  Delivered: ${hop.delivered ? 'Yes' : 'No (Blocked)'}\n\n`;
            });
            report += `DATA EXPOSED\n`;
            report += `------------\n`;
            leak.data_exposed.forEach(d => report += `- ${d}\n`);
            report += `\nATTACHMENTS\n`;
            report += `-----------\n`;
            leak.attachments.forEach(a => report += `- ${a}\n`);

            const blob = new Blob([report], { type: 'text/plain' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `competitor_leak_report_${leakId}_${new Date().toISOString().split('T')[0]}.txt`;
            a.click();
            window.URL.revokeObjectURL(url);

            showToast('Report downloaded successfully');
        }, 1500);
    }

    function openCompetitorDomainsModal() {
        let domainsHTML = '';
        for (const [domain, info] of Object.entries(competitorDomainsData)) {
            const threatColor = info.threat_level === 'critical' ? 'var(--danger)' :
                               (info.threat_level === 'high' ? 'var(--warning)' : 'var(--info)');
            domainsHTML += `
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background: var(--bg-dark); border-radius: 8px; margin-bottom: 10px; border-left: 4px solid ${threatColor};">
                    <div>
                        <strong>${info.name}</strong>
                        <div style="font-size: 12px; color: var(--primary);">${domain}</div>
                        <div style="font-size: 11px; color: var(--text-muted);">Industry: ${info.industry} | Added: ${info.added}</div>
                    </div>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <span class="badge badge-${info.threat_level === 'critical' ? 'critical' : (info.threat_level === 'high' ? 'high' : 'medium')}">${info.threat_level.toUpperCase()}</span>
                        <span style="font-size: 12px; color: var(--text-muted);">${info.incidents} incidents</span>
                        <button class="btn btn-sm btn-danger" onclick="removeCompetitorDomain('${domain}')">Remove</button>
                    </div>
                </div>
            `;
        }

        const modalContent = `
            <div style="margin-bottom: 20px;">
                <h4 style="margin-bottom: 15px;">Add New Competitor Domain</h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 10px; align-items: end;">
                    <div class="form-group" style="margin: 0;">
                        <label style="font-size: 12px;">Domain</label>
                        <input type="text" id="newCompetitorDomain" class="form-input" placeholder="e.g., competitor.com">
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <label style="font-size: 12px;">Company Name</label>
                        <input type="text" id="newCompetitorName" class="form-input" placeholder="e.g., Acme Corp">
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <label style="font-size: 12px;">Threat Level</label>
                        <select id="newCompetitorThreat" class="form-input">
                            <option value="critical">Critical</option>
                            <option value="high">High</option>
                            <option value="medium">Medium</option>
                        </select>
                    </div>
                    <button class="btn btn-primary" onclick="addCompetitorDomain()">Add</button>
                </div>
            </div>
            <div>
                <h4 style="margin-bottom: 15px;">Monitored Competitor Domains (${Object.keys(competitorDomainsData).length})</h4>
                <div id="competitorDomainsList" style="max-height: 400px; overflow-y: auto;">
                    ${domainsHTML}
                </div>
            </div>
        `;

        showDynamicModal('Manage Competitor Domains', modalContent, [
            { label: 'Export List', class: 'btn-secondary', onclick: () => exportCompetitorDomains() },
            { label: 'Close', class: 'btn-secondary', onclick: () => closeDynamicModal() }
        ]);
    }

    function addCompetitorDomain() {
        const domain = document.getElementById('newCompetitorDomain').value.trim().toLowerCase();
        const name = document.getElementById('newCompetitorName').value.trim();
        const threat = document.getElementById('newCompetitorThreat').value;

        if (!domain || !name) {
            showToast('Please fill in domain and company name', 'error');
            return;
        }

        if (competitorDomainsData[domain]) {
            showToast('Domain already exists', 'error');
            return;
        }

        competitorDomainsData[domain] = {
            name: name,
            threat_level: threat,
            industry: 'New',
            added: new Date().toISOString().split('T')[0],
            incidents: 0
        };

        showToast(`Competitor domain ${domain} added successfully`);
        closeDynamicModal();
        openCompetitorDomainsModal(); // Refresh the modal
    }

    function removeCompetitorDomain(domain) {
        if (confirm(`Remove ${domain} from competitor watchlist?`)) {
            delete competitorDomainsData[domain];
            showToast(`Domain ${domain} removed`, 'warning');
            closeDynamicModal();
            openCompetitorDomainsModal(); // Refresh the modal
        }
    }

    function exportCompetitorDomains() {
        let csv = 'Domain,Company Name,Threat Level,Industry,Added,Incidents\n';
        for (const [domain, info] of Object.entries(competitorDomainsData)) {
            csv += `"${domain}","${info.name}","${info.threat_level}","${info.industry}","${info.added}",${info.incidents}\n`;
        }

        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `competitor_domains_${new Date().toISOString().split('T')[0]}.csv`;
        a.click();
        window.URL.revokeObjectURL(url);

        showToast('Competitor domains exported');
    }

    function exportCompetitorReport() {
        showToast('Generating comprehensive competitor leak report...', 'info');

        setTimeout(() => {
            let report = `COMPETITOR LEAK TRACKING - EXECUTIVE SUMMARY\n`;
            report += `=============================================\n`;
            report += `Generated: ${new Date().toISOString()}\n\n`;

            report += `OVERVIEW\n`;
            report += `--------\n`;
            report += `Total Leaks Detected: ${Object.keys(competitorLeaksData).length}\n`;
            report += `Confirmed Leaks: ${Object.values(competitorLeaksData).filter(l => l.status === 'confirmed_leak').length}\n`;
            report += `Under Investigation: ${Object.values(competitorLeaksData).filter(l => l.status === 'investigating').length}\n`;
            report += `Blocked: ${Object.values(competitorLeaksData).filter(l => l.status === 'blocked').length}\n`;
            report += `Monitored Competitors: ${Object.keys(competitorDomainsData).length}\n\n`;

            report += `COMPETITOR DOMAINS\n`;
            report += `------------------\n`;
            for (const [domain, info] of Object.entries(competitorDomainsData)) {
                report += `${info.name} (${domain}) - ${info.threat_level.toUpperCase()} - ${info.incidents} incidents\n`;
            }

            report += `\nLEAK INCIDENTS\n`;
            report += `--------------\n`;
            for (const leak of Object.values(competitorLeaksData)) {
                report += `\n[${leak.leak_id}] ${leak.original_subject}\n`;
                report += `  Status: ${leak.status} | Competitor: ${leak.competitor}\n`;
                report += `  Data Type: ${leak.data_type} | Risk: ${leak.risk_score}/100\n`;
                report += `  Chain Length: ${leak.chain.length} hops | Final: ${leak.chain[leak.chain.length-1].to}\n`;
            }

            const blob = new Blob([report], { type: 'text/plain' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `competitor_leak_summary_${new Date().toISOString().split('T')[0]}.txt`;
            a.click();
            window.URL.revokeObjectURL(url);

            showToast('Executive summary exported successfully');
        }, 2000);
    }

    // Quarantine Functions
    function reviewEmail(emailId) {
        viewEmailDetails(emailId);
    }

    function deleteEmail(emailId) {
        if (confirm('Permanently delete email ' + emailId + '?')) {
            showToast('Email deleted', 'warning');
        }
    }

    // Report Functions
    function generateReport() {
        showToast('Generating DLP report...', 'info');
        setTimeout(() => showToast('Report generated successfully'), 2500);
    }

    // Leak Report Data
    const leakReportData = [
        {
            leak_id: 'CLK-001',
            subject: 'Q4 Financial Report - Confidential',
            original_sender: 'john.smith@company.com',
            department: 'Finance',
            detected: '2025-01-26 14:30:05',
            risk_score: 98,
            status: 'confirmed_leak',
            competitor: 'Acme Corp',
            final_recipient: 'strategy@competitor.com',
            data_type: 'Financial Data',
            attachments: ['Q4_Financial_Report.xlsx', 'Revenue_Forecast_2025.pdf'],
            data_exposed: ['Revenue figures', 'Profit margins', 'Growth projections', 'Market strategy'],
            chain: [
                { hop: 1, from: 'john.smith@company.com', to: 'finance.team@company.com', time: '2025-01-26 09:15:00', location: 'Internal', delivered: true },
                { hop: 2, from: 'mary.wilson@company.com', to: 'external.consultant@advisory.com', time: '2025-01-26 11:30:00', location: 'External', delivered: true },
                { hop: 3, from: 'external.consultant@advisory.com', to: 'contact@competitor.com', time: '2025-01-26 14:28:00', location: 'Competitor', delivered: true },
                { hop: 4, from: 'contact@competitor.com', to: 'strategy@competitor.com', time: '2025-01-26 14:45:00', location: 'Competitor Internal', delivered: true }
            ]
        },
        {
            leak_id: 'CLK-002',
            subject: 'Product Roadmap 2025-2026',
            original_sender: 'product@company.com',
            department: 'Product',
            detected: '2025-01-25 16:45:22',
            risk_score: 89,
            status: 'investigating',
            competitor: 'Rival Technologies',
            final_recipient: 'jobs@rival-tech.com',
            data_type: 'Product Roadmap',
            attachments: ['Roadmap_2025.pptx'],
            data_exposed: ['Feature plans', 'Release dates', 'Technology stack'],
            chain: [
                { hop: 1, from: 'product@company.com', to: 'engineering@company.com', time: '2025-01-25 10:00:00', location: 'Internal', delivered: true },
                { hop: 2, from: 'dev.lead@company.com', to: 'contractor@freelance.io', time: '2025-01-25 14:30:00', location: 'External', delivered: true },
                { hop: 3, from: 'contractor@freelance.io', to: 'jobs@rival-tech.com', time: '2025-01-25 16:42:00', location: 'Competitor', delivered: true }
            ]
        },
        {
            leak_id: 'CLK-003',
            subject: 'Enterprise Customer Database',
            original_sender: 'sales@company.com',
            department: 'Sales',
            detected: '2025-01-26 11:20:15',
            risk_score: 95,
            status: 'blocked',
            competitor: 'Market Comp Inc',
            final_recipient: 'hr@marketcomp.io (BLOCKED)',
            data_type: 'Customer List',
            attachments: ['Customer_Database.csv'],
            data_exposed: ['Blocked before delivery'],
            chain: [
                { hop: 1, from: 'sales@company.com', to: 'sales.manager@company.com', time: '2025-01-26 09:00:00', location: 'Internal', delivered: true },
                { hop: 2, from: 'sales.manager@company.com', to: 'hr@marketcomp.io', time: '2025-01-26 11:18:00', location: 'Competitor', delivered: false }
            ]
        },
        {
            leak_id: 'CLK-004',
            subject: 'API Integration Code',
            original_sender: 'dev.team@company.com',
            department: 'Engineering',
            detected: '2025-01-24 09:30:00',
            risk_score: 92,
            status: 'confirmed_leak',
            competitor: 'Tech Rivals LLC',
            final_recipient: 'hiring@techrivals.net',
            data_type: 'Source Code',
            attachments: ['api_integration.zip', 'auth_module.py'],
            data_exposed: ['Authentication logic', 'API endpoints', 'Database schema'],
            chain: [
                { hop: 1, from: 'dev.team@company.com', to: 'qa.team@company.com', time: '2025-01-23 15:00:00', location: 'Internal', delivered: true },
                { hop: 2, from: 'qa.analyst@company.com', to: 'personal@gmail.com', time: '2025-01-23 18:45:00', location: 'Personal Email', delivered: true },
                { hop: 3, from: 'personal@gmail.com', to: 'hiring@techrivals.net', time: '2025-01-24 09:28:00', location: 'Competitor', delivered: true }
            ]
        }
    ];

    function printLeakReport() {
        showToast('Preparing report for printing...', 'info');
        setTimeout(() => {
            window.print();
        }, 500);
    }

    function exportLeakReportPDF() {
        showToast('Generating PDF report...', 'info');

        setTimeout(() => {
            // Create text-based report (in real app, would use jsPDF or server-side PDF generation)
            let report = `
╔══════════════════════════════════════════════════════════════════════════════╗
║                     DATA LEAK DETECTION REPORT                                ║
║              Email Trail Analysis & Forensic Investigation                     ║
╚══════════════════════════════════════════════════════════════════════════════╝

Report Generated: ${new Date().toISOString()}
Report Period: January 1-26, 2025
Report ID: RPT-${new Date().toISOString().split('T')[0].replace(/-/g, '')}-001
Classification: CONFIDENTIAL

═══════════════════════════════════════════════════════════════════════════════
                            EXECUTIVE SUMMARY
═══════════════════════════════════════════════════════════════════════════════

Total Leaks Detected:     7
Confirmed Deliveries:     4
Blocked in Transit:       2
Under Investigation:      1
Total Email Hops Traced:  23

CRITICAL FINDING: 4 sensitive documents successfully reached competitor
destinations. Immediate action required for CLK-001 and CLK-004.

`;

            leakReportData.forEach(leak => {
                report += `
═══════════════════════════════════════════════════════════════════════════════
LEAK #${leak.leak_id}: ${leak.subject}
═══════════════════════════════════════════════════════════════════════════════
Status:           ${leak.status.toUpperCase().replace('_', ' ')}
Risk Score:       ${leak.risk_score}/100
Original Sender:  ${leak.original_sender} (${leak.department})
Detected:         ${leak.detected}
Competitor:       ${leak.competitor}
Final Recipient:  ${leak.final_recipient}
Data Type:        ${leak.data_type}

ATTACHMENTS LEAKED:
${leak.attachments.map(a => '  • ' + a).join('\n')}

DATA EXPOSED:
${leak.data_exposed.map(d => '  • ' + d).join('\n')}

EMAIL TRAIL (${leak.chain.length} Hops):
${'─'.repeat(77)}
`;
                leak.chain.forEach(hop => {
                    report += `
Hop ${hop.hop} [${hop.location}]
  From: ${hop.from}
  To:   ${hop.to}
  Time: ${hop.time}
  Status: ${hop.delivered ? 'DELIVERED' : 'BLOCKED'}
`;
                });
            });

            report += `
═══════════════════════════════════════════════════════════════════════════════
                              END OF REPORT
═══════════════════════════════════════════════════════════════════════════════

Generated by DLP System v2.5
NetworkScan SCADA Security Suite
For authorized security personnel only
`;

            const blob = new Blob([report], { type: 'text/plain' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `Leak_Detection_Report_${new Date().toISOString().split('T')[0]}.txt`;
            a.click();
            window.URL.revokeObjectURL(url);

            showToast('PDF report exported successfully (as TXT)');
        }, 1500);
    }

    function exportLeakReportCSV() {
        showToast('Generating CSV export...', 'info');

        setTimeout(() => {
            // Main summary CSV
            let csv = 'LEAK DETECTION REPORT - EMAIL TRAIL ANALYSIS\n';
            csv += 'Generated:,' + new Date().toISOString() + '\n';
            csv += 'Report Period:,January 1-26 2025\n\n';

            csv += 'SUMMARY\n';
            csv += 'Leak ID,Subject,Original Sender,Department,Final Recipient,Competitor,Hops,Risk Score,Status,Data Type,Detected\n';

            leakReportData.forEach(leak => {
                csv += `"${leak.leak_id}","${leak.subject}","${leak.original_sender}","${leak.department}","${leak.final_recipient}","${leak.competitor}",${leak.chain.length},${leak.risk_score},"${leak.status}","${leak.data_type}","${leak.detected}"\n`;
            });

            csv += '\n\nDETAILED EMAIL TRAILS\n';
            csv += 'Leak ID,Hop,From,To,Time,Location,Delivered\n';

            leakReportData.forEach(leak => {
                leak.chain.forEach(hop => {
                    csv += `"${leak.leak_id}",${hop.hop},"${hop.from}","${hop.to}","${hop.time}","${hop.location}",${hop.delivered ? 'Yes' : 'No'}\n`;
                });
            });

            csv += '\n\nATTACHMENTS LEAKED\n';
            csv += 'Leak ID,Attachment\n';
            leakReportData.forEach(leak => {
                leak.attachments.forEach(att => {
                    csv += `"${leak.leak_id}","${att}"\n`;
                });
            });

            csv += '\n\nDATA EXPOSED\n';
            csv += 'Leak ID,Data Category\n';
            leakReportData.forEach(leak => {
                leak.data_exposed.forEach(data => {
                    csv += `"${leak.leak_id}","${data}"\n`;
                });
            });

            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `Leak_Detection_Report_${new Date().toISOString().split('T')[0]}.csv`;
            a.click();
            window.URL.revokeObjectURL(url);

            showToast('CSV report exported successfully');
        }, 1000);
    }

    // User Profile Data
    const userProfilesData = {
        'john.smith': {
            id: 'john.smith',
            email: 'john.smith@company.com',
            fullName: 'John Smith',
            initials: 'JS',
            department: 'Finance',
            manager: 'Michael Johnson',
            hireDate: 'March 15, 2019',
            riskLevel: 'high',
            riskScore: 78,
            totalViolations: 12,
            blockedEmails: 8,
            lastIncident: '2 hours ago',
            violations: {
                'Financial Data': 7,
                'Confidential': 3,
                'External Transfer': 2
            },
            incidents: [
                { date: '2025-01-26 12:30', type: 'Financial Data', action: 'Blocked', details: 'Attempted to send Q4 revenue forecast to external email' },
                { date: '2025-01-26 09:15', type: 'Confidential', action: 'Quarantined', details: 'Email with confidential merger documents' },
                { date: '2025-01-25 16:45', type: 'Financial Data', action: 'Blocked', details: 'Budget spreadsheet sent to personal Gmail' },
                { date: '2025-01-25 11:20', type: 'External Transfer', action: 'Flagged', details: 'Large attachment sent to competitor domain' },
                { date: '2025-01-24 14:30', type: 'Financial Data', action: 'Blocked', details: 'Payroll data in email attachment' }
            ]
        },
        'sarah.jones': {
            id: 'sarah.jones',
            email: 'sarah.jones@company.com',
            fullName: 'Sarah Jones',
            initials: 'SJ',
            department: 'Human Resources',
            manager: 'Emily Davis',
            hireDate: 'June 22, 2020',
            riskLevel: 'medium',
            riskScore: 52,
            totalViolations: 8,
            blockedEmails: 4,
            lastIncident: '1 day ago',
            violations: {
                'PII Data': 5,
                'Employee Records': 2,
                'External Transfer': 1
            },
            incidents: [
                { date: '2025-01-25 10:45', type: 'PII Data', action: 'Blocked', details: 'SSN detected in email to external recruiter' },
                { date: '2025-01-24 15:30', type: 'Employee Records', action: 'Quarantined', details: 'Performance reviews sent to wrong recipient' },
                { date: '2025-01-23 09:00', type: 'PII Data', action: 'Flagged', details: 'New hire personal info shared externally' },
                { date: '2025-01-22 14:15', type: 'PII Data', action: 'Blocked', details: 'Benefits enrollment forms with SSN' }
            ]
        },
        'dev.team': {
            id: 'dev.team',
            email: 'dev.team@company.com',
            fullName: 'Development Team',
            initials: 'DT',
            department: 'Engineering',
            manager: 'Alex Chen',
            hireDate: 'N/A (Group)',
            riskLevel: 'low',
            riskScore: 28,
            totalViolations: 5,
            blockedEmails: 2,
            lastIncident: '3 days ago',
            violations: {
                'Source Code': 4,
                'API Keys': 1
            },
            incidents: [
                { date: '2025-01-23 16:20', type: 'Source Code', action: 'Flagged', details: 'Code snippet shared on external forum' },
                { date: '2025-01-22 11:45', type: 'API Keys', action: 'Blocked', details: 'AWS credentials in email to contractor' },
                { date: '2025-01-20 14:30', type: 'Source Code', action: 'Flagged', details: 'Repository link shared externally' }
            ]
        }
    };

    function viewUserProfile(userId) {
        const user = userProfilesData[userId];
        if (!user) {
            showToast('User profile not found', 'error');
            return;
        }

        // Populate basic info
        document.getElementById('profileUserEmail').textContent = user.email;
        document.getElementById('profileAvatar').textContent = user.initials;
        document.getElementById('profileFullName').textContent = user.fullName;
        document.getElementById('profileDepartment').textContent = user.department;
        document.getElementById('profileEmailFull').textContent = user.email;
        document.getElementById('profileManager').textContent = user.manager;
        document.getElementById('profileHireDate').textContent = user.hireDate;

        // Risk badge
        const riskBadges = {
            'high': '<span class="badge badge-critical">High Risk</span>',
            'medium': '<span class="badge badge-high">Medium Risk</span>',
            'low': '<span class="badge badge-medium">Low Risk</span>'
        };
        document.getElementById('profileRiskBadge').innerHTML = riskBadges[user.riskLevel] || '';

        // Stats
        document.getElementById('profileTotalViolations').textContent = user.totalViolations;
        document.getElementById('profileBlockedEmails').textContent = user.blockedEmails;
        document.getElementById('profileRiskScore').textContent = user.riskScore;
        document.getElementById('profileRiskScore').style.color = user.riskScore > 70 ? 'var(--danger)' : (user.riskScore > 40 ? 'var(--warning)' : 'var(--success)');
        document.getElementById('profileLastIncident').textContent = user.lastIncident;

        // Violation breakdown
        const breakdownContainer = document.getElementById('profileViolationBreakdown');
        let breakdownHTML = '';
        const totalViolations = Object.values(user.violations).reduce((a, b) => a + b, 0);

        for (const [category, count] of Object.entries(user.violations)) {
            const percentage = Math.round((count / totalViolations) * 100);
            breakdownHTML += `
                <div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                        <span>${category}</span>
                        <span>${count} violations (${percentage}%)</span>
                    </div>
                    <div style="height: 8px; background: var(--bg-dark); border-radius: 4px; overflow: hidden;">
                        <div style="height: 100%; width: ${percentage}%; background: linear-gradient(90deg, var(--primary), var(--secondary)); border-radius: 4px;"></div>
                    </div>
                </div>
            `;
        }
        breakdownContainer.innerHTML = breakdownHTML;

        // Recent incidents
        const incidentsContainer = document.getElementById('profileRecentIncidents');
        let incidentsHTML = '';

        const actionColors = {
            'Blocked': 'var(--danger)',
            'Quarantined': 'var(--warning)',
            'Flagged': 'var(--info)'
        };

        user.incidents.forEach(incident => {
            incidentsHTML += `
                <div style="display: flex; gap: 15px; padding: 12px; background: var(--bg-dark); border-radius: 8px; margin-bottom: 10px;">
                    <div style="min-width: 100px;">
                        <div style="font-size: 11px; color: var(--text-muted);">${incident.date}</div>
                        <div style="font-size: 12px; color: ${actionColors[incident.action]}; font-weight: 500;">${incident.action}</div>
                    </div>
                    <div style="flex: 1;">
                        <div style="font-size: 12px; color: var(--primary); margin-bottom: 3px;">${incident.type}</div>
                        <div style="font-size: 13px;">${incident.details}</div>
                    </div>
                </div>
            `;
        });

        incidentsContainer.innerHTML = incidentsHTML || '<div style="text-align: center; color: var(--text-muted); padding: 20px;">No recent incidents</div>';

        // Store current user for actions
        window.currentProfileUser = user;

        openModal('userProfileModal');
    }

    function sendWarningToUser() {
        const user = window.currentProfileUser;
        if (!user) return;

        if (confirm(`Send a formal warning email to ${user.fullName} (${user.email}) about their DLP violations?`)) {
            showToast(`Warning email sent to ${user.email}`, 'warning');
        }
    }

    function exportUserReport() {
        const user = window.currentProfileUser;
        if (!user) return;

        showToast('Generating user compliance report...', 'info');
        setTimeout(() => {
            // Create report content
            let report = `DLP Compliance Report\n`;
            report += `=====================\n\n`;
            report += `User: ${user.fullName}\n`;
            report += `Email: ${user.email}\n`;
            report += `Department: ${user.department}\n`;
            report += `Risk Score: ${user.riskScore}/100\n`;
            report += `Total Violations: ${user.totalViolations}\n\n`;
            report += `Violation Breakdown:\n`;
            for (const [category, count] of Object.entries(user.violations)) {
                report += `  - ${category}: ${count}\n`;
            }
            report += `\nRecent Incidents:\n`;
            user.incidents.forEach(inc => {
                report += `  [${inc.date}] ${inc.action}: ${inc.details}\n`;
            });

            // Download
            const blob = new Blob([report], { type: 'text/plain' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `dlp_report_${user.id}_${new Date().toISOString().split('T')[0]}.txt`;
            a.click();
            window.URL.revokeObjectURL(url);

            showToast('Report exported successfully');
        }, 1500);
    }

    function scheduleTraining() {
        const user = window.currentProfileUser;
        if (!user) return;

        if (confirm(`Schedule mandatory DLP compliance training for ${user.fullName}?`)) {
            showToast(`Training scheduled for ${user.fullName}. Calendar invite sent.`, 'success');
        }
    }

    function restrictUserAccess() {
        const user = window.currentProfileUser;
        if (!user) return;

        if (confirm(`WARNING: This will restrict ${user.fullName}'s ability to send external emails. Continue?`)) {
            if (confirm(`Please confirm: Restrict external email access for ${user.email}?`)) {
                showToast(`External email access restricted for ${user.email}`, 'warning');
                closeModal('userProfileModal');
            }
        }
    }

    // Initialize Charts
    document.addEventListener('DOMContentLoaded', function() {
        // Activity Chart
        new Chart(document.getElementById('activityChart'), {
            type: 'line',
            data: {
                labels: ['00:00', '04:00', '08:00', '12:00', '16:00', '20:00', 'Now'],
                datasets: [
                    {
                        label: 'Scanned',
                        data: [120, 80, 450, 890, 1200, 780, 456],
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'Blocked',
                        data: [5, 3, 25, 45, 67, 34, 23],
                        borderColor: '#ef4444',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        fill: true,
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { color: '#e2e8f0' } } },
                scales: {
                    y: { grid: { color: 'rgba(255,255,255,0.1)' }, ticks: { color: '#94a3b8' } },
                    x: { grid: { color: 'rgba(255,255,255,0.1)' }, ticks: { color: '#94a3b8' } }
                }
            }
        });

        // Category Chart
        new Chart(document.getElementById('categoryChart'), {
            type: 'doughnut',
            data: {
                labels: ['PII', 'Financial', 'Source Code', 'HR Data', 'Other'],
                datasets: [{
                    data: [35, 28, 18, 12, 7],
                    backgroundColor: ['#ef4444', '#f59e0b', '#3b82f6', '#10b981', '#6b7280']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'right', labels: { color: '#e2e8f0' } } }
            }
        });

        // Trends Chart (Reports)
        if (document.getElementById('trendsChart')) {
            new Chart(document.getElementById('trendsChart'), {
                type: 'bar',
                data: {
                    labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
                    datasets: [
                        { label: 'Blocked', data: [145, 167, 189, 234], backgroundColor: '#ef4444' },
                        { label: 'Quarantined', data: [89, 102, 95, 112], backgroundColor: '#f59e0b' },
                        { label: 'Flagged', data: [234, 256, 278, 312], backgroundColor: '#3b82f6' }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom', labels: { color: '#e2e8f0' } } },
                    scales: {
                        y: { grid: { color: 'rgba(255,255,255,0.1)' }, ticks: { color: '#94a3b8' } },
                        x: { grid: { color: 'rgba(255,255,255,0.1)' }, ticks: { color: '#94a3b8' } }
                    }
                }
            });
        }

        if (document.getElementById('categoriesChart')) {
            new Chart(document.getElementById('categoriesChart'), {
                type: 'polarArea',
                data: {
                    labels: ['PII', 'Financial', 'Source Code', 'HR', 'Legal', 'Healthcare'],
                    datasets: [{
                        data: [456, 342, 178, 234, 89, 67],
                        backgroundColor: [
                            'rgba(239, 68, 68, 0.7)',
                            'rgba(245, 158, 11, 0.7)',
                            'rgba(59, 130, 246, 0.7)',
                            'rgba(16, 185, 129, 0.7)',
                            'rgba(139, 92, 246, 0.7)',
                            'rgba(236, 72, 153, 0.7)'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'right', labels: { color: '#e2e8f0' } } }
                }
            });
        }
    });

    // Auto-refresh simulation
    setInterval(() => {
        const liveIndicators = document.querySelectorAll('.live-indicator');
        // Simulate live updates
    }, 30000);

    // Escape key to close modals
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.dlp-modal.active').forEach(modal => {
                closeModal(modal.id);
            });
        }
    });
    </script>
</body>
</html>
