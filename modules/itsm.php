<?php
/**
 * IT Service Management (ITSM) Platform
 * Comprehensive service desk with incident, problem, change, knowledge, asset management and analytics
 */

require_once __DIR__ . '/../classes/Database.php';
$db = Database::getInstance();

// ========== INCIDENT MANAGEMENT DATA ==========
$incidents = [
    [
        'id' => 'INC-2024-001',
        'title' => 'Email server not responding',
        'description' => 'Users unable to send/receive emails since 09:00 AM',
        'category' => 'Email & Messaging',
        'priority' => 'Critical',
        'status' => 'In Progress',
        'assigned_to' => 'John Smith',
        'reported_by' => 'Sarah Johnson',
        'channel' => 'Phone',
        'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours')),
        'sla_breach' => false,
        'time_to_resolve' => 240,
        'sla_remaining' => 58
    ],
    [
        'id' => 'INC-2024-002',
        'title' => 'VPN connection timeout',
        'description' => 'Remote workers experiencing VPN disconnections every 10 minutes',
        'category' => 'Network',
        'priority' => 'High',
        'status' => 'Assigned',
        'assigned_to' => 'Mike Davis',
        'reported_by' => 'IT Helpdesk',
        'channel' => 'Web Portal',
        'created_at' => date('Y-m-d H:i:s', strtotime('-4 hours')),
        'sla_breach' => false,
        'time_to_resolve' => 480,
        'sla_remaining' => 236
    ],
    [
        'id' => 'INC-2024-003',
        'title' => 'Application slow performance',
        'description' => 'CRM application loading times exceed 30 seconds',
        'category' => 'Application',
        'priority' => 'Medium',
        'status' => 'New',
        'assigned_to' => 'Unassigned',
        'reported_by' => 'Sales Team',
        'channel' => 'Email',
        'created_at' => date('Y-m-d H:i:s', strtotime('-6 hours')),
        'sla_breach' => false,
        'time_to_resolve' => 960,
        'sla_remaining' => 594
    ],
    [
        'id' => 'INC-2024-004',
        'title' => 'Printer offline',
        'description' => 'Floor 3 printer not responding to print jobs',
        'category' => 'Hardware',
        'priority' => 'Low',
        'status' => 'On Hold',
        'assigned_to' => 'David Lee',
        'reported_by' => 'Admin Team',
        'channel' => 'Chat',
        'created_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
        'sla_breach' => false,
        'time_to_resolve' => 1440,
        'sla_remaining' => 0
    ],
    [
        'id' => 'INC-2024-005',
        'title' => 'Database connection error',
        'description' => 'Production database throwing connection timeout errors',
        'category' => 'Database',
        'priority' => 'Critical',
        'status' => 'Resolved',
        'assigned_to' => 'Emily Chen',
        'reported_by' => 'Monitoring System',
        'channel' => 'Web Portal',
        'created_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
        'sla_breach' => false,
        'time_to_resolve' => 240,
        'sla_remaining' => 0
    ]
];

// ========== PROBLEM MANAGEMENT DATA ==========
$problems = [
    [
        'id' => 'PRB-2024-001',
        'title' => 'Recurring VPN disconnections',
        'root_cause' => 'Network equipment firmware outdated',
        'related_incidents' => 12,
        'status' => 'Root Cause Analysis',
        'priority' => 'High',
        'assigned_to' => 'Network Team',
        'identified_date' => date('Y-m-d', strtotime('-5 days')),
        'target_resolution' => date('Y-m-d', strtotime('+10 days'))
    ],
    [
        'id' => 'PRB-2024-002',
        'title' => 'Email server intermittent failures',
        'root_cause' => 'Under investigation',
        'related_incidents' => 8,
        'status' => 'Investigation',
        'priority' => 'Critical',
        'assigned_to' => 'Infrastructure Team',
        'identified_date' => date('Y-m-d', strtotime('-2 days')),
        'target_resolution' => date('Y-m-d', strtotime('+5 days'))
    ],
    [
        'id' => 'PRB-2024-003',
        'title' => 'CRM performance degradation',
        'root_cause' => 'Database query optimization needed',
        'related_incidents' => 15,
        'status' => 'Known Error',
        'priority' => 'Medium',
        'assigned_to' => 'Database Team',
        'identified_date' => date('Y-m-d', strtotime('-10 days')),
        'target_resolution' => date('Y-m-d', strtotime('+15 days'))
    ],
    [
        'id' => 'PRB-2024-004',
        'title' => 'Printer queue stuck',
        'root_cause' => 'Print spooler service memory leak',
        'related_incidents' => 6,
        'status' => 'Resolved',
        'priority' => 'Low',
        'assigned_to' => 'Desktop Support',
        'identified_date' => date('Y-m-d', strtotime('-20 days')),
        'target_resolution' => date('Y-m-d', strtotime('-5 days'))
    ]
];

// ========== CHANGE MANAGEMENT DATA ==========
$changes = [
    [
        'id' => 'CHG-2024-001',
        'title' => 'Upgrade email server to Exchange 2025',
        'change_type' => 'Major',
        'impact' => 'High',
        'risk' => 'Medium',
        'status' => 'Pending Approval',
        'requested_by' => 'IT Manager',
        'assigned_to' => 'Infrastructure Team',
        'scheduled_date' => date('Y-m-d H:i:s', strtotime('+7 days')),
        'approval_status' => 'Awaiting CAB',
        'approvers' => ['IT Director', 'CTO', 'Operations Manager'],
        'approved_count' => 1,
        'backout_plan' => 'Restore from previous night backup',
        'downtime_required' => true,
        'downtime_minutes' => 120
    ],
    [
        'id' => 'CHG-2024-002',
        'title' => 'Deploy new VPN firmware',
        'change_type' => 'Standard',
        'impact' => 'Medium',
        'risk' => 'Low',
        'status' => 'Scheduled',
        'requested_by' => 'Network Admin',
        'assigned_to' => 'Network Team',
        'scheduled_date' => date('Y-m-d H:i:s', strtotime('+2 days')),
        'approval_status' => 'Approved',
        'approvers' => ['Network Manager'],
        'approved_count' => 1,
        'backout_plan' => 'Rollback to firmware v3.2',
        'downtime_required' => true,
        'downtime_minutes' => 30
    ],
    [
        'id' => 'CHG-2024-003',
        'title' => 'Add database read replica',
        'change_type' => 'Normal',
        'impact' => 'Low',
        'risk' => 'Low',
        'status' => 'Implementation',
        'requested_by' => 'Database Admin',
        'assigned_to' => 'Database Team',
        'scheduled_date' => date('Y-m-d H:i:s', strtotime('+1 day')),
        'approval_status' => 'Approved',
        'approvers' => ['IT Manager', 'Database Lead'],
        'approved_count' => 2,
        'backout_plan' => 'Remove replica configuration',
        'downtime_required' => false,
        'downtime_minutes' => 0
    ],
    [
        'id' => 'CHG-2024-004',
        'title' => 'Security patch deployment - Windows Servers',
        'change_type' => 'Emergency',
        'impact' => 'High',
        'risk' => 'High',
        'status' => 'Completed',
        'requested_by' => 'Security Team',
        'assigned_to' => 'Systems Team',
        'scheduled_date' => date('Y-m-d H:i:s', strtotime('-1 day')),
        'approval_status' => 'Emergency Approved',
        'approvers' => ['CTO'],
        'approved_count' => 1,
        'backout_plan' => 'Uninstall patch via WSUS',
        'downtime_required' => true,
        'downtime_minutes' => 15
    ]
];

// ========== KNOWLEDGE BASE DATA ==========
$knowledgeArticles = [
    [
        'id' => 'KB-001',
        'title' => 'How to reset your password',
        'category' => 'Account Access',
        'content_preview' => 'Step-by-step guide for resetting passwords in Active Directory',
        'views' => 1245,
        'helpful_votes' => 156,
        'not_helpful_votes' => 12,
        'last_updated' => date('Y-m-d', strtotime('-5 days')),
        'author' => 'IT Helpdesk',
        'status' => 'Published',
        'tags' => ['password', 'account', 'self-service']
    ],
    [
        'id' => 'KB-002',
        'title' => 'VPN setup guide for remote workers',
        'category' => 'Network & Connectivity',
        'content_preview' => 'Complete VPN configuration for Windows, Mac, and mobile devices',
        'views' => 892,
        'helpful_votes' => 98,
        'not_helpful_votes' => 8,
        'last_updated' => date('Y-m-d', strtotime('-10 days')),
        'author' => 'Network Team',
        'status' => 'Published',
        'tags' => ['vpn', 'remote', 'connectivity']
    ],
    [
        'id' => 'KB-003',
        'title' => 'Troubleshooting email delivery issues',
        'category' => 'Email & Messaging',
        'content_preview' => 'Common email problems and solutions',
        'views' => 567,
        'helpful_votes' => 72,
        'not_helpful_votes' => 5,
        'last_updated' => date('Y-m-d', strtotime('-15 days')),
        'author' => 'Email Team',
        'status' => 'Published',
        'tags' => ['email', 'troubleshooting', 'outlook']
    ],
    [
        'id' => 'KB-004',
        'title' => 'Software installation request process',
        'category' => 'Applications',
        'content_preview' => 'How to request and install approved software',
        'views' => 423,
        'helpful_votes' => 54,
        'not_helpful_votes' => 3,
        'last_updated' => date('Y-m-d', strtotime('-7 days')),
        'author' => 'IT Manager',
        'status' => 'Published',
        'tags' => ['software', 'installation', 'request']
    ],
    [
        'id' => 'KB-005',
        'title' => 'Printer setup and troubleshooting',
        'category' => 'Hardware',
        'content_preview' => 'Network printer installation and common issues',
        'views' => 789,
        'helpful_votes' => 91,
        'not_helpful_votes' => 7,
        'last_updated' => date('Y-m-d', strtotime('-3 days')),
        'author' => 'Desktop Support',
        'status' => 'Published',
        'tags' => ['printer', 'hardware', 'troubleshooting']
    ]
];

// ========== ASSET MANAGEMENT DATA ==========
$assets = [
    [
        'id' => 'AST-2024-001',
        'asset_name' => 'Dell Latitude 5520',
        'asset_type' => 'Laptop',
        'serial_number' => 'DL5520-ABC123',
        'assigned_to' => 'John Smith',
        'department' => 'Sales',
        'location' => 'New York Office',
        'purchase_date' => date('Y-m-d', strtotime('-18 months')),
        'warranty_expiry' => date('Y-m-d', strtotime('+6 months')),
        'status' => 'Active',
        'cost' => 1299.99,
        'vendor' => 'Dell',
        'lifecycle_stage' => 'In Use'
    ],
    [
        'id' => 'AST-2024-002',
        'asset_name' => 'iPhone 14 Pro',
        'asset_type' => 'Mobile Device',
        'serial_number' => 'IP14P-XYZ789',
        'assigned_to' => 'Sarah Johnson',
        'department' => 'Marketing',
        'location' => 'San Francisco Office',
        'purchase_date' => date('Y-m-d', strtotime('-12 months')),
        'warranty_expiry' => date('Y-m-d', strtotime('+12 months')),
        'status' => 'Active',
        'cost' => 1099.00,
        'vendor' => 'Apple',
        'lifecycle_stage' => 'In Use'
    ],
    [
        'id' => 'AST-2024-003',
        'asset_name' => 'Microsoft Office 365 E3',
        'asset_type' => 'Software License',
        'serial_number' => 'O365-LIC-456',
        'assigned_to' => 'IT Department',
        'department' => 'IT',
        'location' => 'Cloud',
        'purchase_date' => date('Y-m-d', strtotime('-6 months')),
        'warranty_expiry' => date('Y-m-d', strtotime('+6 months')),
        'status' => 'Active',
        'cost' => 24.00,
        'vendor' => 'Microsoft',
        'lifecycle_stage' => 'Subscription'
    ],
    [
        'id' => 'AST-2024-004',
        'asset_name' => 'Cisco Catalyst 9300',
        'asset_type' => 'Network Switch',
        'serial_number' => 'CS9300-NET001',
        'assigned_to' => 'Network Infrastructure',
        'department' => 'IT',
        'location' => 'Data Center',
        'purchase_date' => date('Y-m-d', strtotime('-24 months')),
        'warranty_expiry' => date('Y-m-d', strtotime('+12 months')),
        'status' => 'Active',
        'cost' => 8500.00,
        'vendor' => 'Cisco',
        'lifecycle_stage' => 'In Use'
    ],
    [
        'id' => 'AST-2024-005',
        'asset_name' => 'HP LaserJet Pro M404dn',
        'asset_type' => 'Printer',
        'serial_number' => 'HP-M404-PRN01',
        'assigned_to' => 'Floor 3 Common Area',
        'department' => 'Shared',
        'location' => 'New York Office',
        'purchase_date' => date('Y-m-d', strtotime('-30 months')),
        'warranty_expiry' => date('Y-m-d', strtotime('-6 months')),
        'status' => 'Retired',
        'cost' => 399.99,
        'vendor' => 'HP',
        'lifecycle_stage' => 'End of Life'
    ]
];

// Calculate statistics
$total_incidents = count($incidents);
$critical_incidents = count(array_filter($incidents, fn($i) => $i['priority'] === 'Critical'));
$open_incidents = count(array_filter($incidents, fn($i) => in_array($i['status'], ['New', 'Assigned', 'In Progress'])));
$sla_breaches = count(array_filter($incidents, fn($i) => $i['sla_breach']));

$total_problems = count($problems);
$open_problems = count(array_filter($problems, fn($p) => $p['status'] !== 'Resolved'));

$total_changes = count($changes);
$pending_changes = count(array_filter($changes, fn($c) => in_array($c['status'], ['Pending Approval', 'Scheduled'])));

$total_kb_articles = count($knowledgeArticles);
$kb_total_views = array_sum(array_column($knowledgeArticles, 'views'));

$total_assets = count($assets);
$active_assets = count(array_filter($assets, fn($a) => $a['status'] === 'Active'));

// Agent productivity metrics
$agentMetrics = [
    ['agent' => 'John Smith', 'tickets_resolved' => 42, 'avg_resolution_time' => 145, 'satisfaction' => 4.7],
    ['agent' => 'Mike Davis', 'tickets_resolved' => 38, 'avg_resolution_time' => 162, 'satisfaction' => 4.5],
    ['agent' => 'Emily Chen', 'tickets_resolved' => 51, 'avg_resolution_time' => 128, 'satisfaction' => 4.9],
    ['agent' => 'David Lee', 'tickets_resolved' => 29, 'avg_resolution_time' => 198, 'satisfaction' => 4.2]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT Service Management | ITSM Platform</title>
    <link rel="stylesheet" href="../admin/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        .header-bar {
            background: white;
            padding: 25px 35px;
            border-radius: 15px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        .header-bar h1 {
            margin: 0;
            color: #667eea;
            font-size: 32px;
        }
        .header-bar p {
            margin: 8px 0 0 0;
            color: #666;
            font-size: 16px;
        }
        .back-btn {
            background: #667eea;
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }
        .back-btn:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .action-btn:hover {
            transform: translateY(-3px);
            opacity: 0.9;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            display: flex;
            cursor: pointer;
            transition: all 0.3s ease;
            align-items: center;
            gap: 20px;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 50px rgba(0,0,0,0.25);
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }
        .stat-icon {
            font-size: 48px;
            width: 70px;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
        }
        .stat-info {
            flex: 1;
        }
        .stat-number {
            font-size: 36px;
            font-weight: bold;
            color: #333;
            line-height: 1;
            margin-bottom: 5px;
        }
        .stat-label {
            color: #666;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 25px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
        }
        .card h2 {
            color: #667eea;
            font-size: 24px;
            margin: 0 0 25px 0;
            padding-bottom: 15px;
            border-bottom: 3px solid #667eea;
        }
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            border-bottom: 2px solid #e0e0e0;
            flex-wrap: wrap;
        }
        .tab {
            padding: 12px 24px;
            background: #f5f5f5;
            border: none;
            border-radius: 8px 8px 0 0;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            color: #666;
            transition: all 0.3s;
        }
        .tab:hover {
            background: #e8e8e8;
            color: #333;
        }
        .tab.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
            animation: fadeIn 0.3s;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        th {
            padding: 14px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        td {
            padding: 12px 14px;
            border-bottom: 1px solid #e0e0e0;
            font-size: 14px;
        }
        tbody tr:hover {
            background: #f5f5f5;
        }
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .badge-critical { background: #ffebee; color: #c62828; }
        .badge-high { background: #fff3e0; color: #e65100; }
        .badge-medium { background: #fff9c4; color: #f57f17; }
        .badge-low { background: #e8f5e9; color: #2e7d32; }
        .badge-new { background: #e3f2fd; color: #1565c0; }
        .badge-assigned { background: #f3e5f5; color: #6a1b9a; }
        .badge-in-progress { background: #fff3e0; color: #e65100; }
        .badge-resolved { background: #e8f5e9; color: #2e7d32; }
        .badge-on-hold { background: #fce4ec; color: #880e4f; }
        .chart-container {
            position: relative;
            height: 350px;
            margin: 25px 0;
        }
        .kb-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 15px;
            border-left: 5px solid #667eea;
            cursor: pointer;
            transition: all 0.3s;
        }
        .kb-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transform: translateX(5px);
        }
        .kb-card h4 {
            margin: 0 0 10px 0;
            color: #333;
            font-size: 18px;
        }
        .kb-meta {
            display: flex;
            gap: 20px;
            margin-top: 15px;
            font-size: 13px;
            color: #666;
        }
        .tag-list {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin: 10px 0;
        }
        .tag {
            background: white;
            padding: 5px 12px;
            border-radius: 12px;
            font-size: 12px;
            border: 1px solid #ddd;
        }
        .asset-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .asset-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            border-left: 5px solid #667eea;
        }
        .asset-card h4 {
            margin: 0 0 15px 0;
            color: #333;
            font-size: 18px;
        }
        .metric-row {
            display: flex;
            justify-content: space-between;
            margin: 10px 0;
            font-size: 14px;
        }
        .metric-label {
            color: #666;
            font-weight: 500;
        }
        .metric-value {
            font-weight: bold;
            color: #333;
        }
        .btn {
            background: #667eea;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s;
        }
        .btn:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }
        .change-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 15px;
            border-left: 5px solid;
        }
        .change-card.major { border-color: #f44336; }
        .change-card.normal { border-color: #FF9800; }
        .change-card.standard { border-color: #4CAF50; }
        .change-card.emergency { border-color: #9C27B0; }
        .approval-bar {
            width: 100%;
            height: 8px;
            background: #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
            margin: 10px 0;
        }
        .approval-fill {
            height: 100%;
            background: #4CAF50;
            border-radius: 4px;
            transition: width 0.3s;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-bar">
            <div>
                <h1>🎫 IT Service Management Platform</h1>
                <p>Comprehensive ITSM with incident, problem, change, knowledge, and asset management</p>
            </div>
            <a href="../index.php" class="back-btn">← Back to Dashboard</a>
        </div>

        <!-- Quick Actions -->
        <div style="display: flex; gap: 15px; justify-content: center; margin-bottom: 30px; flex-wrap: wrap;">
            <a href="log_incident.php" class="action-btn" style="text-decoration: none; background: #f44336; color: white; padding: 14px 28px; border-radius: 8px; font-weight: 600; box-shadow: 0 4px 12px rgba(244,67,54,0.3); transition: all 0.3s; display: inline-block;">
                🎫 Log New Incident
            </a>
            <a href="log_problem.php" class="action-btn" style="text-decoration: none; background: #ff9800; color: white; padding: 14px 28px; border-radius: 8px; font-weight: 600; box-shadow: 0 4px 12px rgba(255,152,0,0.3); transition: all 0.3s; display: inline-block;">
                🔧 Log New Problem
            </a>
            <a href="log_change.php" class="action-btn" style="text-decoration: none; background: #4CAF50; color: white; padding: 14px 28px; border-radius: 8px; font-weight: 600; box-shadow: 0 4px 12px rgba(76,175,80,0.3); transition: all 0.3s; display: inline-block;">
                📋 Log Change Request
            </a>
        </div>

        <!-- Summary Statistics -->
        <div class="stats-grid">
            <div class="stat-card" onclick="switchTab('incidents')" title="Click to view all incidents">
                <div class="stat-icon">🎫</div>
                <div class="stat-info">
                    <div class="stat-number"><?= $total_incidents ?></div>
                    <div class="stat-label">Total Incidents</div>
                </div>
            </div>
            <div class="stat-card" onclick="switchTab('incidents')" title="Click to view critical incidents">
                <div class="stat-icon">🚨</div>
                <div class="stat-info">
                    <div class="stat-number" style="color: #f44336;"><?= $critical_incidents ?></div>
                    <div class="stat-label">Critical</div>
                </div>
            </div>
            <div class="stat-card" onclick="switchTab('problems')" title="Click to view active problems">
                <div class="stat-icon">🔧</div>
                <div class="stat-info">
                    <div class="stat-number"><?= $total_problems ?></div>
                    <div class="stat-label">Active Problems</div>
                </div>
            </div>
            <div class="stat-card" onclick="switchTab('changes')" title="Click to view pending changes">
                <div class="stat-icon">🔄</div>
                <div class="stat-info">
                    <div class="stat-number"><?= $pending_changes ?></div>
                    <div class="stat-label">Pending Changes</div>
                </div>
            </div>
            <div class="stat-card" onclick="switchTab('knowledge')" title="Click to view knowledge base">
                <div class="stat-icon">📚</div>
                <div class="stat-info">
                    <div class="stat-number"><?= $total_kb_articles ?></div>
                    <div class="stat-label">KB Articles</div>
                </div>
            </div>
            <div class="stat-card" onclick="switchTab('assets')" title="Click to view asset inventory">
                <div class="stat-icon">📦</div>
                <div class="stat-info">
                    <div class="stat-number"><?= $active_assets ?></div>
                    <div class="stat-label">Active Assets</div>
                </div>
            </div>
        </div>

        <!-- Main Content with Tabs -->
        <div class="card">
            <div class="tabs">
                <button class="tab active" onclick="switchTab('dashboard')">📊 Dashboard</button>
                <button class="tab" onclick="switchTab('incidents')">🎫 Incidents</button>
                <button class="tab" onclick="switchTab('problems')">🔧 Problems</button>
                <button class="tab" onclick="switchTab('changes')">🔄 Changes</button>
                <button class="tab" onclick="switchTab('knowledge')">📚 Knowledge Base</button>
                <button class="tab" onclick="switchTab('assets')">📦 Assets</button>
                <button class="tab" onclick="switchTab('analytics')">📊 Analytics</button>
            </div>

            <!-- Dashboard Tab -->
            <div id="dashboard" class="tab-content active">
                <h2>Service Desk Dashboard</h2>

                <div class="chart-container">
                    <canvas id="dashboardChart"></canvas>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-top: 30px;">
                    <div>
                        <h3>Multi-Channel Ticket Sources</h3>
                        <div class="chart-container" style="height: 250px;">
                            <canvas id="channelChart"></canvas>
                        </div>
                    </div>
                    <div>
                        <h3>SLA Compliance</h3>
                        <div class="chart-container" style="height: 250px;">
                            <canvas id="slaChart"></canvas>
                        </div>
                    </div>
                </div>

                <h3 style="margin-top: 30px;">Key Performance Indicators</h3>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-info">
                            <div class="stat-number" style="font-size: 28px; color: #4CAF50;">92.5%</div>
                            <div class="stat-label">SLA Compliance Rate</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-info">
                            <div class="stat-number" style="font-size: 28px; color: #667eea;">156</div>
                            <div class="stat-label">Avg Resolution Time (min)</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-info">
                            <div class="stat-number" style="font-size: 28px; color: #FF9800;">4.6</div>
                            <div class="stat-label">Customer Satisfaction</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-info">
                            <div class="stat-number" style="font-size: 28px; color: #9C27B0;">78%</div>
                            <div class="stat-label">First Contact Resolution</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Incidents Tab -->
            <div id="incidents" class="tab-content">
                <h2>Incident Management</h2>

                <div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <button class="btn">+ Create New Incident</button>
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <input type="text" placeholder="Search incidents..." style="padding: 10px; border: 1px solid #ddd; border-radius: 6px; width: 300px;">
                        <select style="padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                            <option>All Priorities</option>
                            <option>Critical</option>
                            <option>High</option>
                            <option>Medium</option>
                            <option>Low</option>
                        </select>
                    </div>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Assigned To</th>
                            <th>Channel</th>
                            <th>SLA Remaining</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($incidents as $incident): ?>
                        <tr>
                            <td><strong><?= $incident['id'] ?></strong></td>
                            <td><?= htmlspecialchars($incident['title']) ?></td>
                            <td><?= $incident['category'] ?></td>
                            <td>
                                <span class="badge badge-<?= strtolower($incident['priority']) ?>">
                                    <?= $incident['priority'] ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-<?= strtolower(str_replace(' ', '-', $incident['status'])) ?>">
                                    <?= $incident['status'] ?>
                                </span>
                            </td>
                            <td><?= $incident['assigned_to'] ?></td>
                            <td><?= $incident['channel'] ?></td>
                            <td style="color: <?= $incident['sla_remaining'] < 60 ? '#f44336' : '#4CAF50' ?>;">
                                <strong><?= $incident['sla_remaining'] ?> min</strong>
                            </td>
                            <td><?= date('M d, H:i', strtotime($incident['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div style="margin-top: 30px; padding: 20px; background: #e3f2fd; border-left: 5px solid #2196F3; border-radius: 8px;">
                    <h4 style="margin: 0 0 10px 0; color: #1565c0;">Multi-Channel Support</h4>
                    <ul style="margin: 0; color: #666; line-height: 1.8;">
                        <li>📧 Email integration with automatic ticket creation</li>
                        <li>🌐 Self-service web portal for ticket submission</li>
                        <li>📱 Mobile app for technicians (iOS & Android)</li>
                        <li>💬 Live chat integration with instant ticket routing</li>
                        <li>📞 Phone system integration with IVR</li>
                        <li>🤖 Automated ticket routing based on keywords and categories</li>
                        <li>⚡ SLA-based escalation and notifications</li>
                    </ul>
                </div>
            </div>

            <!-- Problems Tab -->
            <div id="problems" class="tab-content">
                <h2>Problem Management</h2>

                <div style="margin-bottom: 20px;">
                    <button class="btn">+ Create Problem Record</button>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Root Cause</th>
                            <th>Related Incidents</th>
                            <th>Status</th>
                            <th>Priority</th>
                            <th>Assigned To</th>
                            <th>Target Resolution</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($problems as $problem): ?>
                        <tr>
                            <td><strong><?= $problem['id'] ?></strong></td>
                            <td><?= htmlspecialchars($problem['title']) ?></td>
                            <td><?= htmlspecialchars($problem['root_cause']) ?></td>
                            <td><span class="badge badge-high"><?= $problem['related_incidents'] ?> incidents</span></td>
                            <td>
                                <span class="badge badge-<?= strtolower(str_replace(' ', '-', $problem['status'])) ?>">
                                    <?= $problem['status'] ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-<?= strtolower($problem['priority']) ?>">
                                    <?= $problem['priority'] ?>
                                </span>
                            </td>
                            <td><?= $problem['assigned_to'] ?></td>
                            <td><?= date('M d, Y', strtotime($problem['target_resolution'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div style="margin-top: 30px;">
                    <h3>Problem Lifecycle</h3>
                    <div style="background: #f8f9fa; padding: 20px; border-radius: 10px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <div style="text-align: center; flex: 1;">
                                <div style="width: 60px; height: 60px; background: #2196F3; border-radius: 50%; margin: 0 auto 10px; display: flex; align-items: center; justify-content: center; color: white; font-size: 24px;">1</div>
                                <div style="font-weight: 600; color: #333;">Detection</div>
                                <div style="font-size: 12px; color: #666;">Identify recurring issues</div>
                            </div>
                            <div style="color: #ddd; font-size: 24px;">→</div>
                            <div style="text-align: center; flex: 1;">
                                <div style="width: 60px; height: 60px; background: #FF9800; border-radius: 50%; margin: 0 auto 10px; display: flex; align-items: center; justify-content: center; color: white; font-size: 24px;">2</div>
                                <div style="font-weight: 600; color: #333;">Investigation</div>
                                <div style="font-size: 12px; color: #666;">Root cause analysis</div>
                            </div>
                            <div style="color: #ddd; font-size: 24px;">→</div>
                            <div style="text-align: center; flex: 1;">
                                <div style="width: 60px; height: 60px; background: #9C27B0; border-radius: 50%; margin: 0 auto 10px; display: flex; align-items: center; justify-content: center; color: white; font-size: 24px;">3</div>
                                <div style="font-weight: 600; color: #333;">Known Error</div>
                                <div style="font-size: 12px; color: #666;">Document workaround</div>
                            </div>
                            <div style="color: #ddd; font-size: 24px;">→</div>
                            <div style="text-align: center; flex: 1;">
                                <div style="width: 60px; height: 60px; background: #4CAF50; border-radius: 50%; margin: 0 auto 10px; display: flex; align-items: center; justify-content: center; color: white; font-size: 24px;">4</div>
                                <div style="font-weight: 600; color: #333;">Resolution</div>
                                <div style="font-size: 12px; color: #666;">Permanent fix deployed</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Changes Tab -->
            <div id="changes" class="tab-content">
                <h2>Change Management</h2>

                <div style="margin-bottom: 20px;">
                    <button class="btn">+ Request Change</button>
                </div>

                <?php foreach ($changes as $change): ?>
                <div class="change-card <?= strtolower($change['change_type']) ?>">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px;">
                        <div style="flex: 1;">
                            <h4 style="margin: 0 0 5px 0;"><?= htmlspecialchars($change['title']) ?></h4>
                            <div style="font-size: 13px; color: #666;"><?= $change['id'] ?></div>
                        </div>
                        <div style="text-align: right;">
                            <span class="badge badge-<?= strtolower($change['change_type']) ?>">
                                <?= $change['change_type'] ?>
                            </span>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; margin: 15px 0;">
                        <div class="metric-row" style="display: block;">
                            <span class="metric-label">Impact:</span>
                            <span class="badge badge-<?= strtolower($change['impact']) ?>" style="margin-left: 5px;">
                                <?= $change['impact'] ?>
                            </span>
                        </div>
                        <div class="metric-row" style="display: block;">
                            <span class="metric-label">Risk:</span>
                            <span class="badge badge-<?= strtolower($change['risk']) ?>" style="margin-left: 5px;">
                                <?= $change['risk'] ?>
                            </span>
                        </div>
                        <div class="metric-row" style="display: block;">
                            <span class="metric-label">Status:</span>
                            <span class="badge badge-<?= strtolower(str_replace(' ', '-', $change['status'])) ?>" style="margin-left: 5px;">
                                <?= $change['status'] ?>
                            </span>
                        </div>
                    </div>

                    <div class="metric-row">
                        <span class="metric-label">Scheduled Date:</span>
                        <span class="metric-value"><?= date('M d, Y H:i', strtotime($change['scheduled_date'])) ?></span>
                    </div>

                    <div class="metric-row">
                        <span class="metric-label">Assigned To:</span>
                        <span class="metric-value"><?= $change['assigned_to'] ?></span>
                    </div>

                    <?php if ($change['downtime_required']): ?>
                    <div class="metric-row">
                        <span class="metric-label">Downtime Required:</span>
                        <span class="metric-value" style="color: #f44336;"><?= $change['downtime_minutes'] ?> minutes</span>
                    </div>
                    <?php endif; ?>

                    <div style="margin: 15px 0;">
                        <strong style="font-size: 13px; color: #666;">Approval Progress: <?= $change['approved_count'] ?> / <?= count($change['approvers']) ?></strong>
                        <div class="approval-bar">
                            <div class="approval-fill" style="width: <?= ($change['approved_count'] / count($change['approvers'])) * 100 ?>%;"></div>
                        </div>
                        <div style="font-size: 12px; color: #666; margin-top: 5px;">
                            Approvers: <?= implode(', ', $change['approvers']) ?>
                        </div>
                    </div>

                    <div style="margin-top: 15px; padding: 12px; background: white; border-radius: 6px;">
                        <strong style="font-size: 13px;">Backout Plan:</strong>
                        <div style="font-size: 13px; color: #666; margin-top: 5px;">
                            <?= htmlspecialchars($change['backout_plan']) ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Knowledge Base Tab -->
            <div id="knowledge" class="tab-content">
                <h2>Knowledge Base</h2>

                <div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <button class="btn">+ Create Article</button>
                    </div>
                    <div>
                        <input type="text" placeholder="Search knowledge base..." style="padding: 10px; border: 1px solid #ddd; border-radius: 6px; width: 400px;">
                    </div>
                </div>

                <div class="stats-grid" style="grid-template-columns: repeat(4, 1fr); margin-bottom: 25px;">
                    <div class="stat-card">
                        <div class="stat-info">
                            <div class="stat-number" style="font-size: 28px;"><?= $total_kb_articles ?></div>
                            <div class="stat-label">Published Articles</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-info">
                            <div class="stat-number" style="font-size: 28px;"><?= number_format($kb_total_views) ?></div>
                            <div class="stat-label">Total Views</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-info">
                            <div class="stat-number" style="font-size: 28px;">87%</div>
                            <div class="stat-label">Helpfulness Rating</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-info">
                            <div class="stat-number" style="font-size: 28px;">34%</div>
                            <div class="stat-label">Self-Service Resolution</div>
                        </div>
                    </div>
                </div>

                <?php foreach ($knowledgeArticles as $article): ?>
                <div class="kb-card">
                    <div style="display: flex; justify-content: space-between; align-items: start;">
                        <div style="flex: 1;">
                            <h4><?= htmlspecialchars($article['title']) ?></h4>
                            <p style="color: #666; margin: 5px 0 10px 0; font-size: 14px;">
                                <?= htmlspecialchars($article['content_preview']) ?>
                            </p>
                            <div class="tag-list">
                                <?php foreach ($article['tags'] as $tag): ?>
                                <span class="tag"><?= htmlspecialchars($tag) ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <span class="badge badge-resolved"><?= $article['status'] ?></span>
                        </div>
                    </div>

                    <div class="kb-meta">
                        <div>📁 <?= $article['category'] ?></div>
                        <div>👁️ <?= number_format($article['views']) ?> views</div>
                        <div>👍 <?= $article['helpful_votes'] ?> helpful</div>
                        <div>👎 <?= $article['not_helpful_votes'] ?> not helpful</div>
                        <div>✍️ <?= $article['author'] ?></div>
                        <div>📅 <?= date('M d, Y', strtotime($article['last_updated'])) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Assets Tab -->
            <div id="assets" class="tab-content">
                <h2>Asset Management</h2>

                <div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <button class="btn">+ Add Asset</button>
                        <button class="btn" style="background: white; color: #667eea; border: 2px solid #667eea;">Import from CSV</button>
                    </div>
                    <div>
                        <select style="padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                            <option>All Asset Types</option>
                            <option>Laptop</option>
                            <option>Mobile Device</option>
                            <option>Software License</option>
                            <option>Network Equipment</option>
                            <option>Printer</option>
                        </select>
                    </div>
                </div>

                <div class="chart-container">
                    <canvas id="assetChart"></canvas>
                </div>

                <h3 style="margin-top: 30px;">Asset Inventory</h3>
                <div class="asset-grid">
                    <?php foreach ($assets as $asset): ?>
                    <div class="asset-card">
                        <h4><?= htmlspecialchars($asset['asset_name']) ?></h4>
                        <div style="font-size: 12px; color: #666; margin-bottom: 15px;">
                            <?= $asset['id'] ?> • <?= $asset['asset_type'] ?>
                        </div>

                        <div class="metric-row">
                            <span class="metric-label">Serial Number:</span>
                            <span class="metric-value" style="font-size: 12px;"><?= $asset['serial_number'] ?></span>
                        </div>

                        <div class="metric-row">
                            <span class="metric-label">Assigned To:</span>
                            <span class="metric-value"><?= $asset['assigned_to'] ?></span>
                        </div>

                        <div class="metric-row">
                            <span class="metric-label">Department:</span>
                            <span class="metric-value"><?= $asset['department'] ?></span>
                        </div>

                        <div class="metric-row">
                            <span class="metric-label">Location:</span>
                            <span class="metric-value"><?= $asset['location'] ?></span>
                        </div>

                        <div class="metric-row">
                            <span class="metric-label">Purchase Date:</span>
                            <span class="metric-value"><?= date('M d, Y', strtotime($asset['purchase_date'])) ?></span>
                        </div>

                        <div class="metric-row">
                            <span class="metric-label">Warranty Expiry:</span>
                            <span class="metric-value" style="color: <?= strtotime($asset['warranty_expiry']) < time() ? '#f44336' : '#4CAF50' ?>;">
                                <?= date('M d, Y', strtotime($asset['warranty_expiry'])) ?>
                            </span>
                        </div>

                        <div class="metric-row">
                            <span class="metric-label">Cost:</span>
                            <span class="metric-value">$<?= number_format($asset['cost'], 2) ?></span>
                        </div>

                        <div class="metric-row">
                            <span class="metric-label">Status:</span>
                            <span class="badge badge-<?= $asset['status'] === 'Active' ? 'resolved' : 'critical' ?>">
                                <?= $asset['status'] ?>
                            </span>
                        </div>

                        <div class="metric-row">
                            <span class="metric-label">Lifecycle:</span>
                            <span class="badge badge-medium"><?= $asset['lifecycle_stage'] ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Analytics Tab -->
            <div id="analytics" class="tab-content">
                <h2>Reporting & Analytics</h2>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-bottom: 30px;">
                    <div>
                        <h3>Incident Trends (Last 30 Days)</h3>
                        <div class="chart-container" style="height: 300px;">
                            <canvas id="trendChart"></canvas>
                        </div>
                    </div>
                    <div>
                        <h3>Incidents by Category</h3>
                        <div class="chart-container" style="height: 300px;">
                            <canvas id="categoryChart"></canvas>
                        </div>
                    </div>
                </div>

                <h3>Agent Productivity</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Agent Name</th>
                            <th>Tickets Resolved</th>
                            <th>Avg Resolution Time (min)</th>
                            <th>Customer Satisfaction</th>
                            <th>Performance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($agentMetrics as $agent): ?>
                        <tr>
                            <td><strong><?= $agent['agent'] ?></strong></td>
                            <td><?= $agent['tickets_resolved'] ?></td>
                            <td><?= $agent['avg_resolution_time'] ?></td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 5px;">
                                    <span style="font-weight: bold;"><?= $agent['satisfaction'] ?></span>
                                    <span style="color: #FF9800;">★★★★★</span>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-<?= $agent['satisfaction'] >= 4.5 ? 'resolved' : 'medium' ?>">
                                    <?= $agent['satisfaction'] >= 4.5 ? 'Excellent' : 'Good' ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div style="margin-top: 30px;">
                    <h3>Workflow Automation</h3>
                    <div style="background: #f8f9fa; padding: 20px; border-radius: 10px;">
                        <ul style="line-height: 2; color: #666;">
                            <li>✅ <strong>Auto-routing:</strong> Tickets automatically assigned based on keywords, category, and agent availability</li>
                            <li>✅ <strong>Escalation:</strong> Automatic escalation when SLA breach is imminent</li>
                            <li>✅ <strong>Notifications:</strong> Real-time alerts via email, SMS, and push notifications</li>
                            <li>✅ <strong>Surveys:</strong> Automated customer satisfaction surveys after ticket closure</li>
                            <li>✅ <strong>Integration:</strong> Seamless integration with monitoring tools for auto-incident creation</li>
                            <li>✅ <strong>Custom Forms:</strong> Configurable ticket fields based on category</li>
                            <li>✅ <strong>Service Catalog:</strong> Standardized service request fulfillment workflows</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Tab switching
        function switchTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            document.getElementById(tabName).classList.add('active');
            event.target.classList.add('active');
        }

        // Dashboard Chart - Incident Volume Over Time
        const dashboardCtx = document.getElementById('dashboardChart').getContext('2d');
        new Chart(dashboardCtx, {
            type: 'line',
            data: {
                labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
                datasets: [{
                    label: 'Incidents Created',
                    data: [45, 52, 38, 48],
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Incidents Resolved',
                    data: [42, 48, 41, 45],
                    borderColor: '#4CAF50',
                    backgroundColor: 'rgba(76, 175, 80, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' },
                    title: { display: true, text: 'Incident Volume - Last 30 Days' }
                }
            }
        });

        // Channel Chart
        const channelCtx = document.getElementById('channelChart').getContext('2d');
        new Chart(channelCtx, {
            type: 'doughnut',
            data: {
                labels: ['Web Portal', 'Email', 'Phone', 'Chat', 'Mobile App'],
                datasets: [{
                    data: [35, 28, 18, 12, 7],
                    backgroundColor: ['#667eea', '#4CAF50', '#FF9800', '#2196F3', '#9C27B0']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'right' }
                }
            }
        });

        // SLA Chart
        const slaCtx = document.getElementById('slaChart').getContext('2d');
        new Chart(slaCtx, {
            type: 'bar',
            data: {
                labels: ['Critical', 'High', 'Medium', 'Low'],
                datasets: [{
                    label: 'Met SLA',
                    data: [88, 92, 95, 98],
                    backgroundColor: '#4CAF50'
                }, {
                    label: 'Breached SLA',
                    data: [12, 8, 5, 2],
                    backgroundColor: '#f44336'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { stacked: true },
                    y: { stacked: true, max: 100 }
                },
                plugins: {
                    legend: { position: 'top' }
                }
            }
        });

        // Asset Chart
        const assetCtx = document.getElementById('assetChart').getContext('2d');
        new Chart(assetCtx, {
            type: 'bar',
            data: {
                labels: ['Laptops', 'Mobile Devices', 'Software Licenses', 'Network Equipment', 'Printers'],
                datasets: [{
                    label: 'Asset Count',
                    data: [245, 189, 456, 78, 34],
                    backgroundColor: '#667eea'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    title: { display: true, text: 'Assets by Type' }
                }
            }
        });

        // Trend Chart
        const trendCtx = document.getElementById('trendChart').getContext('2d');
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: Array.from({length: 30}, (_, i) => `Day ${i + 1}`),
                datasets: [{
                    label: 'Daily Incidents',
                    data: Array.from({length: 30}, () => Math.floor(Math.random() * 20) + 10),
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                }
            }
        });

        // Category Chart
        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        new Chart(categoryCtx, {
            type: 'pie',
            data: {
                labels: ['Email & Messaging', 'Network', 'Application', 'Hardware', 'Database'],
                datasets: [{
                    data: [28, 22, 18, 15, 17],
                    backgroundColor: ['#667eea', '#4CAF50', '#FF9800', '#2196F3', '#9C27B0']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'right' }
                }
            }
        });
    </script>
</body>
</html>
