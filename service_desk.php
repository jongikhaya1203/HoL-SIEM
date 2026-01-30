<?php
/**
 * IT Service Desk Module - Full ITSM Implementation
 * Includes: Incidents, Problems, Changes, Assets, Knowledge Base, Service Catalog, SLA Management
 */
session_start();

// Sample Data - Incidents
$incidents = [
    ['id' => 'INC-2024-001', 'title' => 'Email Server Down', 'priority' => 'critical', 'status' => 'open', 'category' => 'Infrastructure', 'assignee' => 'John Smith', 'requester' => 'Sarah Johnson', 'created' => '2024-12-15 08:30:00', 'updated' => '2024-12-15 09:15:00', 'sla_due' => '2024-12-15 12:30:00', 'description' => 'Exchange server not responding. Users unable to send/receive emails.', 'impact' => 'high', 'affected_users' => 250],
    ['id' => 'INC-2024-002', 'title' => 'VPN Connection Issues', 'priority' => 'high', 'status' => 'in_progress', 'category' => 'Network', 'assignee' => 'Mike Chen', 'requester' => 'Tom Wilson', 'created' => '2024-12-14 14:20:00', 'updated' => '2024-12-15 10:00:00', 'sla_due' => '2024-12-15 14:20:00', 'description' => 'Remote users experiencing intermittent VPN disconnections.', 'impact' => 'medium', 'affected_users' => 45],
    ['id' => 'INC-2024-003', 'title' => 'Printer Not Working - Floor 3', 'priority' => 'low', 'status' => 'resolved', 'category' => 'Hardware', 'assignee' => 'Lisa Park', 'requester' => 'Amy Brown', 'created' => '2024-12-13 11:00:00', 'updated' => '2024-12-14 09:30:00', 'sla_due' => '2024-12-16 11:00:00', 'description' => 'Network printer HP-3F-01 showing offline status.', 'impact' => 'low', 'affected_users' => 15],
    ['id' => 'INC-2024-004', 'title' => 'Database Performance Degradation', 'priority' => 'critical', 'status' => 'in_progress', 'category' => 'Application', 'assignee' => 'David Lee', 'requester' => 'Finance Dept', 'created' => '2024-12-15 07:45:00', 'updated' => '2024-12-15 11:20:00', 'sla_due' => '2024-12-15 11:45:00', 'description' => 'ERP database queries taking 10x longer than normal.', 'impact' => 'high', 'affected_users' => 120],
    ['id' => 'INC-2024-005', 'title' => 'Software License Expired', 'priority' => 'medium', 'status' => 'pending', 'category' => 'Software', 'assignee' => 'Emma White', 'requester' => 'Design Team', 'created' => '2024-12-14 16:00:00', 'updated' => '2024-12-15 08:00:00', 'sla_due' => '2024-12-17 16:00:00', 'description' => 'Adobe Creative Suite licenses expired for 10 workstations.', 'impact' => 'medium', 'affected_users' => 10],
    ['id' => 'INC-2024-006', 'title' => 'Security Alert - Suspicious Login', 'priority' => 'critical', 'status' => 'open', 'category' => 'Security', 'assignee' => 'Security Team', 'requester' => 'SIEM System', 'created' => '2024-12-15 06:15:00', 'updated' => '2024-12-15 06:20:00', 'sla_due' => '2024-12-15 08:15:00', 'description' => 'Multiple failed login attempts detected from foreign IP.', 'impact' => 'critical', 'affected_users' => 1],
    ['id' => 'INC-2024-007', 'title' => 'WiFi Connectivity Issues - Building B', 'priority' => 'high', 'status' => 'in_progress', 'category' => 'Network', 'assignee' => 'Mike Chen', 'requester' => 'HR Department', 'created' => '2024-12-14 09:30:00', 'updated' => '2024-12-15 14:00:00', 'sla_due' => '2024-12-15 17:30:00', 'description' => 'Wireless access points in Building B dropping connections.', 'impact' => 'medium', 'affected_users' => 80],
    ['id' => 'INC-2024-008', 'title' => 'Backup Job Failed', 'priority' => 'high', 'status' => 'resolved', 'category' => 'Infrastructure', 'assignee' => 'John Smith', 'requester' => 'Monitoring System', 'created' => '2024-12-13 23:00:00', 'updated' => '2024-12-14 08:00:00', 'sla_due' => '2024-12-14 07:00:00', 'description' => 'Nightly backup job for file servers failed.', 'impact' => 'high', 'affected_users' => 500],
];

// Sample Data - Problems
$problems = [
    ['id' => 'PRB-2024-001', 'title' => 'Recurring VPN Disconnections', 'priority' => 'high', 'status' => 'investigating', 'category' => 'Network', 'assignee' => 'Network Team', 'created' => '2024-12-10', 'root_cause' => 'Under investigation - possibly firewall timeout settings', 'related_incidents' => 5, 'workaround' => 'Users can reconnect manually when disconnected'],
    ['id' => 'PRB-2024-002', 'title' => 'Database Connection Pool Exhaustion', 'priority' => 'critical', 'status' => 'root_cause_identified', 'category' => 'Application', 'assignee' => 'DBA Team', 'created' => '2024-12-08', 'root_cause' => 'Connection leak in legacy application module', 'related_incidents' => 12, 'workaround' => 'Restart application server every 4 hours'],
    ['id' => 'PRB-2024-003', 'title' => 'Slow File Server Performance', 'priority' => 'medium', 'status' => 'known_error', 'category' => 'Infrastructure', 'assignee' => 'Infrastructure Team', 'created' => '2024-11-28', 'root_cause' => 'Aging storage array reaching capacity limits', 'related_incidents' => 8, 'workaround' => 'Archive old files to secondary storage'],
    ['id' => 'PRB-2024-004', 'title' => 'Email Attachment Size Limits', 'priority' => 'low', 'status' => 'resolved', 'category' => 'Application', 'assignee' => 'Email Admin', 'created' => '2024-11-15', 'root_cause' => 'Mail gateway configuration limited to 10MB', 'related_incidents' => 25, 'workaround' => 'Use file sharing service for large files'],
];

// Sample Data - Changes
$changes = [
    ['id' => 'CHG-2024-001', 'title' => 'Firewall Rule Update - New VPN Subnet', 'type' => 'standard', 'priority' => 'medium', 'status' => 'approved', 'category' => 'Network', 'requestor' => 'Network Team', 'implementer' => 'Security Team', 'scheduled' => '2024-12-18 22:00:00', 'impact' => 'low', 'risk' => 'low', 'rollback_plan' => 'Revert firewall rules from backup'],
    ['id' => 'CHG-2024-002', 'title' => 'Windows Server 2022 Upgrade', 'type' => 'normal', 'priority' => 'high', 'status' => 'scheduled', 'category' => 'Infrastructure', 'requestor' => 'IT Operations', 'implementer' => 'Server Team', 'scheduled' => '2024-12-21 01:00:00', 'impact' => 'high', 'risk' => 'medium', 'rollback_plan' => 'Restore from VM snapshot'],
    ['id' => 'CHG-2024-003', 'title' => 'ERP System Patch Deployment', 'type' => 'emergency', 'priority' => 'critical', 'status' => 'implementing', 'category' => 'Application', 'requestor' => 'Vendor', 'implementer' => 'App Support', 'scheduled' => '2024-12-15 20:00:00', 'impact' => 'high', 'risk' => 'high', 'rollback_plan' => 'Database restore and application rollback'],
    ['id' => 'CHG-2024-004', 'title' => 'SSL Certificate Renewal', 'type' => 'standard', 'priority' => 'high', 'status' => 'completed', 'category' => 'Security', 'requestor' => 'Security Team', 'implementer' => 'Web Admin', 'scheduled' => '2024-12-12 06:00:00', 'impact' => 'low', 'risk' => 'low', 'rollback_plan' => 'Install previous certificate'],
    ['id' => 'CHG-2024-005', 'title' => 'Network Switch Replacement - Core', 'type' => 'normal', 'priority' => 'critical', 'status' => 'pending_approval', 'category' => 'Network', 'requestor' => 'Network Team', 'implementer' => 'Network Team', 'scheduled' => '2024-12-28 02:00:00', 'impact' => 'critical', 'risk' => 'high', 'rollback_plan' => 'Reconnect old switch if new one fails'],
];

// Sample Data - Assets
$assets = [
    ['id' => 'AST-001', 'name' => 'Dell PowerEdge R750', 'type' => 'Server', 'status' => 'active', 'location' => 'Data Center A - Rack 12', 'owner' => 'IT Operations', 'purchase_date' => '2023-03-15', 'warranty_end' => '2026-03-15', 'cost' => 15000, 'ip' => '10.0.1.50', 'serial' => 'DELL-R750-001'],
    ['id' => 'AST-002', 'name' => 'Cisco Catalyst 9300', 'type' => 'Network', 'status' => 'active', 'location' => 'Data Center A - Rack 01', 'owner' => 'Network Team', 'purchase_date' => '2022-08-20', 'warranty_end' => '2025-08-20', 'cost' => 8500, 'ip' => '10.0.0.1', 'serial' => 'CISCO-9300-001'],
    ['id' => 'AST-003', 'name' => 'HP ProBook 450 G8', 'type' => 'Laptop', 'status' => 'assigned', 'location' => 'Finance Dept', 'owner' => 'Jane Doe', 'purchase_date' => '2023-01-10', 'warranty_end' => '2026-01-10', 'cost' => 1200, 'ip' => 'DHCP', 'serial' => 'HP-PB450-001'],
    ['id' => 'AST-004', 'name' => 'Palo Alto PA-3260', 'type' => 'Firewall', 'status' => 'active', 'location' => 'Data Center A - Rack 02', 'owner' => 'Security Team', 'purchase_date' => '2022-05-01', 'warranty_end' => '2025-05-01', 'cost' => 45000, 'ip' => '10.0.0.254', 'serial' => 'PA-3260-001'],
    ['id' => 'AST-005', 'name' => 'NetApp FAS8200', 'type' => 'Storage', 'status' => 'active', 'location' => 'Data Center A - Rack 15', 'owner' => 'Storage Team', 'purchase_date' => '2021-11-20', 'warranty_end' => '2024-11-20', 'cost' => 85000, 'ip' => '10.0.1.100', 'serial' => 'NTAP-8200-001'],
    ['id' => 'AST-006', 'name' => 'VMware vSphere License', 'type' => 'Software', 'status' => 'active', 'location' => 'Virtual', 'owner' => 'IT Operations', 'purchase_date' => '2024-01-01', 'warranty_end' => '2025-01-01', 'cost' => 25000, 'ip' => 'N/A', 'serial' => 'VMW-VS8-ENT-001'],
    ['id' => 'AST-007', 'name' => 'Dell UltraSharp 27"', 'type' => 'Monitor', 'status' => 'in_stock', 'location' => 'IT Storage Room', 'owner' => 'IT Asset Mgmt', 'purchase_date' => '2024-06-15', 'warranty_end' => '2027-06-15', 'cost' => 450, 'ip' => 'N/A', 'serial' => 'DELL-U2722D-015'],
    ['id' => 'AST-008', 'name' => 'Fortinet FortiGate 100F', 'type' => 'Firewall', 'status' => 'retired', 'location' => 'IT Storage Room', 'owner' => 'Security Team', 'purchase_date' => '2019-03-10', 'warranty_end' => '2022-03-10', 'cost' => 12000, 'ip' => 'N/A', 'serial' => 'FGT-100F-001'],
];

// Sample Data - Knowledge Base
$knowledgeBase = [
    ['id' => 'KB-001', 'title' => 'How to Reset VPN Password', 'category' => 'Network', 'views' => 1250, 'helpful' => 95, 'created' => '2024-01-15', 'updated' => '2024-11-20', 'author' => 'Mike Chen', 'tags' => ['vpn', 'password', 'remote-access']],
    ['id' => 'KB-002', 'title' => 'Setting Up Email on Mobile Devices', 'category' => 'Email', 'views' => 2100, 'helpful' => 88, 'created' => '2023-06-10', 'updated' => '2024-10-15', 'author' => 'Lisa Park', 'tags' => ['email', 'mobile', 'outlook']],
    ['id' => 'KB-003', 'title' => 'Troubleshooting Printer Connectivity', 'category' => 'Hardware', 'views' => 890, 'helpful' => 76, 'created' => '2023-09-22', 'updated' => '2024-08-30', 'author' => 'John Smith', 'tags' => ['printer', 'network', 'troubleshooting']],
    ['id' => 'KB-004', 'title' => 'Security Best Practices for Remote Work', 'category' => 'Security', 'views' => 3200, 'helpful' => 92, 'created' => '2024-03-01', 'updated' => '2024-12-01', 'author' => 'Security Team', 'tags' => ['security', 'remote-work', 'best-practices']],
    ['id' => 'KB-005', 'title' => 'How to Request New Software', 'category' => 'Process', 'views' => 1500, 'helpful' => 82, 'created' => '2023-04-18', 'updated' => '2024-07-22', 'author' => 'IT Service Desk', 'tags' => ['software', 'request', 'procurement']],
    ['id' => 'KB-006', 'title' => 'VPN Client Installation Guide', 'category' => 'Network', 'views' => 1800, 'helpful' => 91, 'created' => '2023-02-28', 'updated' => '2024-09-10', 'author' => 'Network Team', 'tags' => ['vpn', 'installation', 'client']],
];

// Sample Data - Service Catalog
$serviceCatalog = [
    ['id' => 'SVC-001', 'name' => 'New Employee Onboarding', 'category' => 'HR Services', 'sla' => '3 business days', 'cost' => 0, 'description' => 'Complete IT setup for new employees including laptop, accounts, and access.', 'popularity' => 95],
    ['id' => 'SVC-002', 'name' => 'Software Installation Request', 'category' => 'Software', 'sla' => '2 business days', 'cost' => 0, 'description' => 'Request installation of approved software on your workstation.', 'popularity' => 88],
    ['id' => 'SVC-003', 'name' => 'Hardware Upgrade Request', 'category' => 'Hardware', 'sla' => '5 business days', 'cost' => 'Varies', 'description' => 'Request hardware upgrades such as RAM, storage, or peripherals.', 'popularity' => 65],
    ['id' => 'SVC-004', 'name' => 'VPN Access Request', 'category' => 'Network', 'sla' => '1 business day', 'cost' => 0, 'description' => 'Request VPN access for remote work capabilities.', 'popularity' => 92],
    ['id' => 'SVC-005', 'name' => 'Password Reset', 'category' => 'Account Management', 'sla' => '15 minutes', 'cost' => 0, 'description' => 'Reset your domain password or unlock your account.', 'popularity' => 100],
    ['id' => 'SVC-006', 'name' => 'Security Exception Request', 'category' => 'Security', 'sla' => '5 business days', 'cost' => 0, 'description' => 'Request exception to security policies with proper justification.', 'popularity' => 25],
    ['id' => 'SVC-007', 'name' => 'Conference Room AV Support', 'category' => 'Support', 'sla' => '30 minutes', 'cost' => 0, 'description' => 'On-site support for conference room audio/visual equipment.', 'popularity' => 78],
    ['id' => 'SVC-008', 'name' => 'Data Recovery Request', 'category' => 'Data Services', 'sla' => '2 business days', 'cost' => 0, 'description' => 'Request recovery of accidentally deleted files from backup.', 'popularity' => 45],
];

// SLA Metrics
$slaMetrics = [
    'incidents' => ['target' => 95, 'current' => 92.5, 'trend' => 'up'],
    'requests' => ['target' => 98, 'current' => 96.8, 'trend' => 'stable'],
    'changes' => ['target' => 99, 'current' => 98.5, 'trend' => 'up'],
    'problems' => ['target' => 90, 'current' => 87.2, 'trend' => 'down'],
];

// Dashboard Stats
$dashboardStats = [
    'open_incidents' => count(array_filter($incidents, fn($i) => in_array($i['status'], ['open', 'in_progress', 'pending']))),
    'critical_incidents' => count(array_filter($incidents, fn($i) => $i['priority'] === 'critical' && $i['status'] !== 'resolved')),
    'pending_changes' => count(array_filter($changes, fn($c) => in_array($c['status'], ['pending_approval', 'approved', 'scheduled']))),
    'open_problems' => count(array_filter($problems, fn($p) => $p['status'] !== 'resolved')),
    'total_assets' => count($assets),
    'expiring_warranties' => count(array_filter($assets, fn($a) => strtotime($a['warranty_end']) < strtotime('+90 days'))),
];

// Team Members
$teamMembers = [
    ['name' => 'John Smith', 'role' => 'Infrastructure Lead', 'tickets' => 12, 'resolved' => 45, 'status' => 'available'],
    ['name' => 'Mike Chen', 'role' => 'Network Engineer', 'tickets' => 8, 'resolved' => 38, 'status' => 'busy'],
    ['name' => 'Lisa Park', 'role' => 'Support Analyst', 'tickets' => 15, 'resolved' => 52, 'status' => 'available'],
    ['name' => 'David Lee', 'role' => 'DBA', 'tickets' => 5, 'resolved' => 28, 'status' => 'away'],
    ['name' => 'Emma White', 'role' => 'Software Specialist', 'tickets' => 10, 'resolved' => 41, 'status' => 'available'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT Service Desk - IOC Platform</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Roboto+Mono:wght@400;500&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        :root {
            --primary: #667eea;
            --primary-dark: #5a67d8;
            --secondary: #764ba2;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
            --dark: #1e1e2f;
            --darker: #15151f;
            --card-bg: #252538;
            --card-border: #3a3a50;
            --text: #e2e8f0;
            --text-muted: #94a3b8;
            --gradient-primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-success: linear-gradient(135deg, #10b981 0%, #059669 100%);
            --gradient-warning: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            --gradient-danger: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--darker);
            color: var(--text);
            min-height: 100vh;
        }

        /* Header */
        .header {
            background: var(--gradient-primary);
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .logo {
            font-size: 24px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .back-btn {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
            text-decoration: none;
        }

        .back-btn:hover {
            background: rgba(255,255,255,0.3);
        }

        .header-actions {
            display: flex;
            gap: 15px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: white;
            color: var(--primary);
        }

        .btn-primary:hover {
            background: #f0f0f0;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: rgba(255,255,255,0.2);
            color: white;
        }

        .btn-secondary:hover {
            background: rgba(255,255,255,0.3);
        }

        /* Main Layout */
        .main-container {
            display: flex;
            min-height: calc(100vh - 80px);
        }

        /* Sidebar */
        .sidebar {
            width: 260px;
            background: var(--dark);
            border-right: 1px solid var(--card-border);
            padding: 20px 0;
        }

        .sidebar-section {
            margin-bottom: 25px;
        }

        .sidebar-title {
            font-size: 11px;
            text-transform: uppercase;
            color: var(--text-muted);
            padding: 0 20px;
            margin-bottom: 10px;
            letter-spacing: 1px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 20px;
            color: var(--text-muted);
            cursor: pointer;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }

        .nav-item:hover {
            background: rgba(102, 126, 234, 0.1);
            color: var(--text);
        }

        .nav-item.active {
            background: rgba(102, 126, 234, 0.15);
            color: var(--primary);
            border-left-color: var(--primary);
        }

        .nav-item .icon {
            font-size: 18px;
            width: 24px;
            text-align: center;
        }

        .nav-item .badge {
            margin-left: auto;
            background: var(--danger);
            color: white;
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 10px;
        }

        /* Content Area */
        .content {
            flex: 1;
            padding: 25px;
            overflow-y: auto;
        }

        /* Module Containers */
        .module {
            display: none;
        }

        .module.active {
            display: block;
        }

        /* Dashboard Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 20px;
            border: 1px solid var(--card-border);
        }

        .stat-card.critical {
            border-left: 4px solid var(--danger);
        }

        .stat-card.warning {
            border-left: 4px solid var(--warning);
        }

        .stat-card.success {
            border-left: 4px solid var(--success);
        }

        .stat-card.info {
            border-left: 4px solid var(--info);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .stat-icon {
            width: 45px;
            height: 45px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .stat-icon.critical { background: rgba(239, 68, 68, 0.2); }
        .stat-icon.warning { background: rgba(245, 158, 11, 0.2); }
        .stat-icon.success { background: rgba(16, 185, 129, 0.2); }
        .stat-icon.info { background: rgba(59, 130, 246, 0.2); }

        .stat-value {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .stat-label {
            color: var(--text-muted);
            font-size: 13px;
        }

        .stat-trend {
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 5px;
            margin-top: 10px;
        }

        .stat-trend.up { color: var(--success); }
        .stat-trend.down { color: var(--danger); }

        /* Cards */
        .card {
            background: var(--card-bg);
            border-radius: 12px;
            border: 1px solid var(--card-border);
            margin-bottom: 20px;
        }

        .card-header {
            padding: 18px 20px;
            border-bottom: 1px solid var(--card-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-title {
            font-size: 16px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-body {
            padding: 20px;
        }

        /* Tables */
        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--card-border);
        }

        th {
            font-size: 12px;
            text-transform: uppercase;
            color: var(--text-muted);
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        tr:hover {
            background: rgba(102, 126, 234, 0.05);
        }

        /* Priority/Status Badges */
        .badge {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-critical { background: rgba(239, 68, 68, 0.2); color: #f87171; }
        .badge-high { background: rgba(245, 158, 11, 0.2); color: #fbbf24; }
        .badge-medium { background: rgba(59, 130, 246, 0.2); color: #60a5fa; }
        .badge-low { background: rgba(16, 185, 129, 0.2); color: #34d399; }

        .badge-open { background: rgba(239, 68, 68, 0.2); color: #f87171; }
        .badge-in_progress, .badge-investigating { background: rgba(59, 130, 246, 0.2); color: #60a5fa; }
        .badge-pending, .badge-pending_approval { background: rgba(245, 158, 11, 0.2); color: #fbbf24; }
        .badge-resolved, .badge-completed { background: rgba(16, 185, 129, 0.2); color: #34d399; }
        .badge-scheduled, .badge-approved { background: rgba(139, 92, 246, 0.2); color: #a78bfa; }
        .badge-implementing { background: rgba(236, 72, 153, 0.2); color: #f472b6; }
        .badge-root_cause_identified { background: rgba(20, 184, 166, 0.2); color: #2dd4bf; }
        .badge-known_error { background: rgba(251, 146, 60, 0.2); color: #fb923c; }

        /* Asset Status */
        .badge-active { background: rgba(16, 185, 129, 0.2); color: #34d399; }
        .badge-assigned { background: rgba(59, 130, 246, 0.2); color: #60a5fa; }
        .badge-in_stock { background: rgba(139, 92, 246, 0.2); color: #a78bfa; }
        .badge-retired { background: rgba(107, 114, 128, 0.2); color: #9ca3af; }

        /* Team Status */
        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
        }

        .status-dot.available { background: var(--success); }
        .status-dot.busy { background: var(--warning); }
        .status-dot.away { background: var(--text-muted); }

        /* Grid Layouts */
        .grid-2 {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .grid-3 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        /* Charts Container */
        .chart-container {
            position: relative;
            height: 300px;
        }

        /* Quick Actions */
        .quick-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .quick-action-btn {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            color: var(--text);
            padding: 12px 20px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .quick-action-btn:hover {
            background: var(--primary);
            border-color: var(--primary);
        }

        /* Search */
        .search-box {
            background: var(--darker);
            border: 1px solid var(--card-border);
            border-radius: 8px;
            padding: 10px 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            width: 300px;
        }

        .search-box input {
            background: none;
            border: none;
            color: var(--text);
            flex: 1;
            outline: none;
            font-size: 14px;
        }

        .search-box input::placeholder {
            color: var(--text-muted);
        }

        /* Filters */
        .filters {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .filter-btn {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            color: var(--text-muted);
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 13px;
        }

        .filter-btn:hover, .filter-btn.active {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
        }

        /* SLA Progress */
        .sla-bar {
            background: var(--darker);
            height: 8px;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 10px;
        }

        .sla-progress {
            height: 100%;
            border-radius: 4px;
            transition: width 0.5s ease;
        }

        .sla-progress.good { background: var(--gradient-success); }
        .sla-progress.warning { background: var(--gradient-warning); }
        .sla-progress.danger { background: var(--gradient-danger); }

        /* KB Article Card */
        .kb-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 10px;
            padding: 20px;
            transition: all 0.3s;
            cursor: pointer;
        }

        .kb-card:hover {
            border-color: var(--primary);
            transform: translateY(-2px);
        }

        .kb-card-title {
            font-weight: 600;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .kb-card-meta {
            display: flex;
            gap: 15px;
            font-size: 12px;
            color: var(--text-muted);
        }

        .kb-tags {
            display: flex;
            gap: 8px;
            margin-top: 12px;
        }

        .kb-tag {
            background: rgba(102, 126, 234, 0.2);
            color: var(--primary);
            padding: 3px 10px;
            border-radius: 4px;
            font-size: 11px;
        }

        /* Service Card */
        .service-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 10px;
            padding: 20px;
            transition: all 0.3s;
            cursor: pointer;
        }

        .service-card:hover {
            border-color: var(--primary);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.2);
        }

        .service-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            background: var(--gradient-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 15px;
        }

        .service-title {
            font-weight: 600;
            margin-bottom: 8px;
        }

        .service-desc {
            font-size: 13px;
            color: var(--text-muted);
            margin-bottom: 15px;
            line-height: 1.5;
        }

        .service-meta {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: var(--text-muted);
        }

        /* Modal */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal-overlay.show {
            display: flex;
        }

        .modal {
            background: var(--card-bg);
            border-radius: 12px;
            width: 90%;
            max-width: 700px;
            max-height: 90vh;
            overflow-y: auto;
            border: 1px solid var(--card-border);
        }

        .modal-header {
            padding: 20px;
            border-bottom: 1px solid var(--card-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-title {
            font-size: 18px;
            font-weight: 600;
        }

        .modal-close {
            background: none;
            border: none;
            color: var(--text-muted);
            font-size: 24px;
            cursor: pointer;
        }

        .modal-body {
            padding: 20px;
        }

        .modal-footer {
            padding: 15px 20px;
            border-top: 1px solid var(--card-border);
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        /* Form Elements */
        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            font-size: 14px;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            background: var(--darker);
            border: 1px solid var(--card-border);
            border-radius: 8px;
            color: var(--text);
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
        }

        select.form-control {
            cursor: pointer;
        }

        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        /* Timeline */
        .timeline {
            position: relative;
            padding-left: 30px;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 8px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: var(--card-border);
        }

        .timeline-item {
            position: relative;
            padding-bottom: 20px;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -26px;
            top: 4px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--primary);
            border: 2px solid var(--card-bg);
        }

        .timeline-time {
            font-size: 12px;
            color: var(--text-muted);
            margin-bottom: 5px;
        }

        .timeline-content {
            font-size: 14px;
        }

        /* Responsive */
        @media (max-width: 1400px) {
            .dashboard-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 1024px) {
            .sidebar {
                width: 200px;
            }
            .grid-2, .grid-3 {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                display: none;
            }
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateX(100px); }
            to { opacity: 1; transform: translateX(0); }
        }

        @keyframes fadeOut {
            from { opacity: 1; transform: translateX(0); }
            to { opacity: 0; transform: translateX(100px); }
        }

        .module.active {
            animation: fadeIn 0.3s ease;
        }

        /* File Upload Area */
        .file-upload-area {
            border: 2px dashed var(--card-border);
            border-radius: 10px;
            padding: 25px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: var(--bg-dark);
        }

        .file-upload-area:hover {
            border-color: var(--primary);
            background: rgba(102, 126, 234, 0.05);
        }

        .file-upload-area.dragover {
            border-color: var(--primary);
            background: rgba(102, 126, 234, 0.1);
        }

        .file-upload-icon {
            font-size: 32px;
            margin-bottom: 10px;
        }

        .file-upload-text {
            font-weight: 500;
            color: var(--text);
            margin-bottom: 5px;
        }

        .file-upload-hint {
            font-size: 12px;
            color: var(--text-muted);
        }

        .attachment-list {
            margin-top: 10px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .attachment-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 15px;
            background: var(--bg-dark);
            border-radius: 8px;
            border: 1px solid var(--card-border);
        }

        .attachment-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .attachment-icon {
            font-size: 20px;
        }

        .attachment-name {
            font-weight: 500;
            color: var(--text);
        }

        .attachment-size {
            font-size: 12px;
            color: var(--text-muted);
        }

        .attachment-remove {
            background: none;
            border: none;
            color: var(--danger);
            cursor: pointer;
            font-size: 18px;
            padding: 5px;
            border-radius: 4px;
            transition: all 0.2s;
        }

        .attachment-remove:hover {
            background: rgba(239, 68, 68, 0.1);
        }

        /* Ticket Action Buttons */
        .action-btn {
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            padding: 5px;
            border-radius: 4px;
            transition: all 0.2s;
        }

        .action-btn:hover {
            background: rgba(102, 126, 234, 0.2);
            color: var(--primary);
        }

        /* Tooltip */
        [data-tooltip] {
            position: relative;
        }

        [data-tooltip]:hover::after {
            content: attr(data-tooltip);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: var(--dark);
            color: var(--text);
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            white-space: nowrap;
            z-index: 100;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-left">
            <a href="index.php" class="back-btn">< Back to Dashboard</a>
            <div class="logo">
                <span>🎫</span>
                <span>IT Service Desk</span>
            </div>
        </div>
        <div class="header-actions">
            <button class="btn btn-secondary" onclick="showModal('searchModal')">
                <span>🔍</span> Search
            </button>
            <button class="btn btn-primary" onclick="showModal('newTicketModal')">
                <span>+</span> New Ticket
            </button>
        </div>
    </header>

    <div class="main-container">
        <!-- Sidebar Navigation -->
        <nav class="sidebar">
            <div class="sidebar-section">
                <div class="sidebar-title">Main</div>
                <div class="nav-item active" onclick="showModule('dashboard')">
                    <span class="icon">📊</span>
                    <span>Dashboard</span>
                </div>
            </div>

            <div class="sidebar-section">
                <div class="sidebar-title">Service Management</div>
                <div class="nav-item" onclick="showModule('incidents')">
                    <span class="icon">🔥</span>
                    <span>Incidents</span>
                    <span class="badge"><?= $dashboardStats['open_incidents'] ?></span>
                </div>
                <div class="nav-item" onclick="showModule('problems')">
                    <span class="icon">🔧</span>
                    <span>Problems</span>
                    <span class="badge"><?= $dashboardStats['open_problems'] ?></span>
                </div>
                <div class="nav-item" onclick="showModule('changes')">
                    <span class="icon">📋</span>
                    <span>Changes</span>
                    <span class="badge"><?= $dashboardStats['pending_changes'] ?></span>
                </div>
            </div>

            <div class="sidebar-section">
                <div class="sidebar-title">Resources</div>
                <div class="nav-item" onclick="showModule('assets')">
                    <span class="icon">💻</span>
                    <span>Assets</span>
                </div>
                <div class="nav-item" onclick="showModule('knowledge')">
                    <span class="icon">📚</span>
                    <span>Knowledge Base</span>
                </div>
                <div class="nav-item" onclick="showModule('catalog')">
                    <span class="icon">🛒</span>
                    <span>Service Catalog</span>
                </div>
            </div>

            <div class="sidebar-section">
                <div class="sidebar-title">Analytics</div>
                <div class="nav-item" onclick="showModule('sla')">
                    <span class="icon">⏱️</span>
                    <span>SLA Management</span>
                </div>
                <div class="nav-item" onclick="showModule('reports')">
                    <span class="icon">📈</span>
                    <span>Reports</span>
                </div>
            </div>

            <div class="sidebar-section">
                <div class="sidebar-title">Team</div>
                <div class="nav-item" onclick="showModule('team')">
                    <span class="icon">👥</span>
                    <span>Team</span>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="content">
            <!-- Dashboard Module -->
            <div id="dashboard" class="module active">
                <h2 style="margin-bottom: 20px;">Service Desk Dashboard</h2>

                <!-- Stats Grid -->
                <div class="dashboard-grid">
                    <div class="stat-card critical">
                        <div class="stat-header">
                            <div>
                                <div class="stat-value"><?= $dashboardStats['open_incidents'] ?></div>
                                <div class="stat-label">Open Incidents</div>
                            </div>
                            <div class="stat-icon critical">🔥</div>
                        </div>
                        <div class="stat-trend up">↑ 12% from last week</div>
                    </div>
                    <div class="stat-card warning">
                        <div class="stat-header">
                            <div>
                                <div class="stat-value"><?= $dashboardStats['critical_incidents'] ?></div>
                                <div class="stat-label">Critical Incidents</div>
                            </div>
                            <div class="stat-icon warning">⚠️</div>
                        </div>
                        <div class="stat-trend down">↓ 5% from last week</div>
                    </div>
                    <div class="stat-card info">
                        <div class="stat-header">
                            <div>
                                <div class="stat-value"><?= $dashboardStats['pending_changes'] ?></div>
                                <div class="stat-label">Pending Changes</div>
                            </div>
                            <div class="stat-icon info">📋</div>
                        </div>
                        <div class="stat-trend">3 scheduled this week</div>
                    </div>
                    <div class="stat-card success">
                        <div class="stat-header">
                            <div>
                                <div class="stat-value">92.5%</div>
                                <div class="stat-label">SLA Compliance</div>
                            </div>
                            <div class="stat-icon success">✓</div>
                        </div>
                        <div class="stat-trend up">↑ 2.3% from last month</div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="grid-2">
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">📈 Incident Trend (Last 7 Days)</div>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="incidentTrendChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">📊 Incidents by Category</div>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="categoryChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Incidents & Team -->
                <div class="grid-2">
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">🔥 Recent Incidents</div>
                            <button class="filter-btn" onclick="showModule('incidents')">View All</button>
                        </div>
                        <div class="card-body">
                            <div class="table-container">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Title</th>
                                            <th>Priority</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach(array_slice($incidents, 0, 5) as $incident): ?>
                                        <tr onclick="viewIncident('<?= $incident['id'] ?>')">
                                            <td><code><?= $incident['id'] ?></code></td>
                                            <td><?= htmlspecialchars($incident['title']) ?></td>
                                            <td><span class="badge badge-<?= $incident['priority'] ?>"><?= $incident['priority'] ?></span></td>
                                            <td><span class="badge badge-<?= $incident['status'] ?>"><?= str_replace('_', ' ', $incident['status']) ?></span></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">👥 Team Availability</div>
                        </div>
                        <div class="card-body">
                            <div class="table-container">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Role</th>
                                            <th>Tickets</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($teamMembers as $member): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($member['name']) ?></td>
                                            <td><?= htmlspecialchars($member['role']) ?></td>
                                            <td><?= $member['tickets'] ?></td>
                                            <td><span class="status-dot <?= $member['status'] ?>"></span><?= ucfirst($member['status']) ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">⚡ Quick Actions</div>
                    </div>
                    <div class="card-body">
                        <div class="quick-actions">
                            <button class="quick-action-btn" onclick="showModal('newTicketModal')">➕ New Incident</button>
                            <button class="quick-action-btn" onclick="showModule('changes')">📋 Submit Change</button>
                            <button class="quick-action-btn" onclick="showModule('knowledge')">📚 Search KB</button>
                            <button class="quick-action-btn" onclick="showModule('assets')">💻 Find Asset</button>
                            <button class="quick-action-btn" onclick="showModule('catalog')">🛒 Request Service</button>
                            <button class="quick-action-btn" onclick="showModule('reports')">📈 View Reports</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Incidents Module -->
            <div id="incidents" class="module">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2>Incident Management</h2>
                    <button class="btn btn-primary" onclick="showModal('newTicketModal')" style="background: var(--gradient-primary); color: white;">
                        + New Incident
                    </button>
                </div>

                <div class="filters">
                    <button class="filter-btn active" onclick="filterIncidents('all')">All</button>
                    <button class="filter-btn" onclick="filterIncidents('open')">Open</button>
                    <button class="filter-btn" onclick="filterIncidents('in_progress')">In Progress</button>
                    <button class="filter-btn" onclick="filterIncidents('pending')">Pending</button>
                    <button class="filter-btn" onclick="filterIncidents('resolved')">Resolved</button>
                    <div class="search-box" style="margin-left: auto;">
                        <span>🔍</span>
                        <input type="text" placeholder="Search incidents..." id="incidentSearch" onkeyup="searchIncidents()">
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="table-container">
                            <table id="incidentsTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Title</th>
                                        <th>Priority</th>
                                        <th>Status</th>
                                        <th>Category</th>
                                        <th>Assignee</th>
                                        <th>Created</th>
                                        <th>SLA Due</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($incidents as $incident):
                                        $slaDue = strtotime($incident['sla_due']);
                                        $now = time();
                                        $slaClass = ($slaDue < $now && $incident['status'] !== 'resolved') ? 'color: var(--danger);' : '';
                                    ?>
                                    <tr class="incident-row" data-status="<?= $incident['status'] ?>">
                                        <td><code><?= $incident['id'] ?></code></td>
                                        <td>
                                            <strong style="cursor: pointer;" onclick="viewIncident('<?= $incident['id'] ?>')"><?= htmlspecialchars($incident['title']) ?></strong>
                                            <div style="font-size: 12px; color: var(--text-muted);">Requester: <?= $incident['requester'] ?></div>
                                        </td>
                                        <td><span class="badge badge-<?= $incident['priority'] ?>"><?= $incident['priority'] ?></span></td>
                                        <td><span class="badge badge-<?= $incident['status'] ?>"><?= str_replace('_', ' ', $incident['status']) ?></span></td>
                                        <td><?= $incident['category'] ?></td>
                                        <td><?= $incident['assignee'] ?></td>
                                        <td><?= date('M d, H:i', strtotime($incident['created'])) ?></td>
                                        <td style="<?= $slaClass ?>"><?= date('M d, H:i', $slaDue) ?></td>
                                        <td>
                                            <button class="action-btn" data-tooltip="View" onclick="viewIncident('<?= $incident['id'] ?>')">👁️</button>
                                            <button class="action-btn" data-tooltip="Edit" onclick="editIncident('<?= $incident['id'] ?>')">✏️</button>
                                            <button class="action-btn" data-tooltip="Assign" onclick="assignIncident('<?= $incident['id'] ?>')">👤</button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Problems Module -->
            <div id="problems" class="module">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2>Problem Management</h2>
                    <button class="btn btn-primary" onclick="showModal('newProblemModal')" style="background: var(--gradient-primary); color: white;">
                        + New Problem
                    </button>
                </div>

                <div class="dashboard-grid" style="grid-template-columns: repeat(4, 1fr);">
                    <div class="stat-card info">
                        <div class="stat-value"><?= count($problems) ?></div>
                        <div class="stat-label">Total Problems</div>
                    </div>
                    <div class="stat-card warning">
                        <div class="stat-value"><?= count(array_filter($problems, fn($p) => $p['status'] === 'investigating')) ?></div>
                        <div class="stat-label">Investigating</div>
                    </div>
                    <div class="stat-card critical">
                        <div class="stat-value"><?= count(array_filter($problems, fn($p) => $p['status'] === 'known_error')) ?></div>
                        <div class="stat-label">Known Errors</div>
                    </div>
                    <div class="stat-card success">
                        <div class="stat-value"><?= count(array_filter($problems, fn($p) => $p['status'] === 'resolved')) ?></div>
                        <div class="stat-label">Resolved</div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Title</th>
                                        <th>Priority</th>
                                        <th>Status</th>
                                        <th>Category</th>
                                        <th>Related Incidents</th>
                                        <th>Root Cause</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($problems as $problem): ?>
                                    <tr>
                                        <td><code><?= $problem['id'] ?></code></td>
                                        <td>
                                            <strong><?= htmlspecialchars($problem['title']) ?></strong>
                                            <div style="font-size: 12px; color: var(--text-muted);">Assigned: <?= $problem['assignee'] ?></div>
                                        </td>
                                        <td><span class="badge badge-<?= $problem['priority'] ?>"><?= $problem['priority'] ?></span></td>
                                        <td><span class="badge badge-<?= $problem['status'] ?>"><?= str_replace('_', ' ', $problem['status']) ?></span></td>
                                        <td><?= $problem['category'] ?></td>
                                        <td><span class="badge badge-info"><?= $problem['related_incidents'] ?> incidents</span></td>
                                        <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?= htmlspecialchars($problem['root_cause']) ?></td>
                                        <td>
                                            <button class="action-btn" data-tooltip="View" onclick="viewProblem('<?= $problem['id'] ?>')">👁️</button>
                                            <button class="action-btn" data-tooltip="Link Incident" onclick="linkIncident('<?= $problem['id'] ?>')">🔗</button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Changes Module -->
            <div id="changes" class="module">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2>Change Management</h2>
                    <button class="btn btn-primary" onclick="showModal('newChangeModal')" style="background: var(--gradient-primary); color: white;">
                        + Submit Change Request
                    </button>
                </div>

                <div class="dashboard-grid" style="grid-template-columns: repeat(5, 1fr);">
                    <div class="stat-card warning">
                        <div class="stat-value"><?= count(array_filter($changes, fn($c) => $c['status'] === 'pending_approval')) ?></div>
                        <div class="stat-label">Pending Approval</div>
                    </div>
                    <div class="stat-card info">
                        <div class="stat-value"><?= count(array_filter($changes, fn($c) => $c['status'] === 'approved')) ?></div>
                        <div class="stat-label">Approved</div>
                    </div>
                    <div class="stat-card critical">
                        <div class="stat-value"><?= count(array_filter($changes, fn($c) => $c['status'] === 'scheduled')) ?></div>
                        <div class="stat-label">Scheduled</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?= count(array_filter($changes, fn($c) => $c['status'] === 'implementing')) ?></div>
                        <div class="stat-label">Implementing</div>
                    </div>
                    <div class="stat-card success">
                        <div class="stat-value"><?= count(array_filter($changes, fn($c) => $c['status'] === 'completed')) ?></div>
                        <div class="stat-label">Completed</div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Title</th>
                                        <th>Type</th>
                                        <th>Priority</th>
                                        <th>Status</th>
                                        <th>Scheduled</th>
                                        <th>Impact</th>
                                        <th>Risk</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="changesTableBody">
                                    <?php foreach($changes as $change): ?>
                                    <tr>
                                        <td><code><?= $change['id'] ?></code></td>
                                        <td>
                                            <strong><?= htmlspecialchars($change['title']) ?></strong>
                                            <div style="font-size: 12px; color: var(--text-muted);">By: <?= $change['requestor'] ?></div>
                                        </td>
                                        <td><span class="badge badge-<?= $change['type'] === 'emergency' ? 'critical' : ($change['type'] === 'normal' ? 'medium' : 'low') ?>"><?= $change['type'] ?></span></td>
                                        <td><span class="badge badge-<?= $change['priority'] ?>"><?= $change['priority'] ?></span></td>
                                        <td><span class="badge badge-<?= $change['status'] ?>"><?= str_replace('_', ' ', $change['status']) ?></span></td>
                                        <td><?= date('M d, H:i', strtotime($change['scheduled'])) ?></td>
                                        <td><span class="badge badge-<?= $change['impact'] ?>"><?= $change['impact'] ?></span></td>
                                        <td><span class="badge badge-<?= $change['risk'] ?>"><?= $change['risk'] ?></span></td>
                                        <td>
                                            <button class="action-btn" data-tooltip="View" onclick="viewChange('<?= $change['id'] ?>')">👁️</button>
                                            <?php if($change['status'] === 'pending_approval'): ?>
                                            <button class="action-btn" data-tooltip="Approve" onclick="approveChange('<?= $change['id'] ?>')">✅</button>
                                            <button class="action-btn" data-tooltip="Reject" onclick="rejectChange('<?= $change['id'] ?>')">❌</button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Assets Module -->
            <div id="assets" class="module">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2>Asset Management</h2>
                    <div style="display: flex; gap: 10px;">
                        <div class="search-box">
                            <span>🔍</span>
                            <input type="text" placeholder="Search assets..." id="assetSearch" onkeyup="searchAssets()">
                        </div>
                        <button class="btn btn-primary" onclick="showModal('newAssetModal')" style="background: var(--gradient-primary); color: white;">
                            + Add Asset
                        </button>
                    </div>
                </div>

                <div class="dashboard-grid" style="grid-template-columns: repeat(4, 1fr);">
                    <div class="stat-card success">
                        <div class="stat-value"><?= count(array_filter($assets, fn($a) => $a['status'] === 'active')) ?></div>
                        <div class="stat-label">Active Assets</div>
                    </div>
                    <div class="stat-card info">
                        <div class="stat-value"><?= count(array_filter($assets, fn($a) => $a['type'] === 'Server')) ?></div>
                        <div class="stat-label">Servers</div>
                    </div>
                    <div class="stat-card warning">
                        <div class="stat-value"><?= $dashboardStats['expiring_warranties'] ?></div>
                        <div class="stat-label">Warranties Expiring Soon</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">$<?= number_format(array_sum(array_column($assets, 'cost'))) ?></div>
                        <div class="stat-label">Total Asset Value</div>
                    </div>
                </div>

                <div class="filters">
                    <button class="filter-btn active" onclick="filterAssets('all')">All</button>
                    <button class="filter-btn" onclick="filterAssets('Server')">Servers</button>
                    <button class="filter-btn" onclick="filterAssets('Network')">Network</button>
                    <button class="filter-btn" onclick="filterAssets('Laptop')">Laptops</button>
                    <button class="filter-btn" onclick="filterAssets('Firewall')">Firewalls</button>
                    <button class="filter-btn" onclick="filterAssets('Software')">Software</button>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="table-container">
                            <table id="assetsTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Location</th>
                                        <th>Owner</th>
                                        <th>IP Address</th>
                                        <th>Warranty</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($assets as $asset):
                                        $warrantyEnd = strtotime($asset['warranty_end']);
                                        $warrantyClass = $warrantyEnd < time() ? 'color: var(--danger);' : ($warrantyEnd < strtotime('+90 days') ? 'color: var(--warning);' : '');
                                    ?>
                                    <tr class="asset-row" data-type="<?= $asset['type'] ?>">
                                        <td><code><?= $asset['id'] ?></code></td>
                                        <td>
                                            <strong><?= htmlspecialchars($asset['name']) ?></strong>
                                            <div style="font-size: 12px; color: var(--text-muted);">S/N: <?= $asset['serial'] ?></div>
                                        </td>
                                        <td><?= $asset['type'] ?></td>
                                        <td><span class="badge badge-<?= $asset['status'] ?>"><?= str_replace('_', ' ', $asset['status']) ?></span></td>
                                        <td><?= $asset['location'] ?></td>
                                        <td><?= $asset['owner'] ?></td>
                                        <td><code><?= $asset['ip'] ?></code></td>
                                        <td style="<?= $warrantyClass ?>"><?= date('M d, Y', $warrantyEnd) ?></td>
                                        <td>
                                            <button class="action-btn" data-tooltip="View" onclick="viewAsset('<?= $asset['id'] ?>')">👁️</button>
                                            <button class="action-btn" data-tooltip="Edit" onclick="editAsset('<?= $asset['id'] ?>')">✏️</button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Knowledge Base Module -->
            <div id="knowledge" class="module">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2>Knowledge Base</h2>
                    <div style="display: flex; gap: 10px;">
                        <div class="search-box" style="width: 400px;">
                            <span>🔍</span>
                            <input type="text" placeholder="Search articles..." id="kbSearch" onkeyup="searchKB()">
                        </div>
                        <button class="btn btn-primary" onclick="showModal('newArticleModal')" style="background: var(--gradient-primary); color: white;">
                            + New Article
                        </button>
                    </div>
                </div>

                <div class="filters">
                    <button class="filter-btn active" onclick="filterKB('all')">All</button>
                    <button class="filter-btn" onclick="filterKB('Network')">Network</button>
                    <button class="filter-btn" onclick="filterKB('Email')">Email</button>
                    <button class="filter-btn" onclick="filterKB('Hardware')">Hardware</button>
                    <button class="filter-btn" onclick="filterKB('Security')">Security</button>
                    <button class="filter-btn" onclick="filterKB('Process')">Process</button>
                </div>

                <div class="grid-3" id="kbGrid">
                    <?php foreach($knowledgeBase as $article): ?>
                    <div class="kb-card" data-category="<?= $article['category'] ?>" onclick="viewArticle('<?= $article['id'] ?>')">
                        <div class="kb-card-title">
                            <span>📄</span>
                            <?= htmlspecialchars($article['title']) ?>
                        </div>
                        <div class="kb-card-meta">
                            <span>👁️ <?= number_format($article['views']) ?> views</span>
                            <span>👍 <?= $article['helpful'] ?>% helpful</span>
                            <span>✏️ <?= $article['author'] ?></span>
                        </div>
                        <div class="kb-tags">
                            <?php foreach($article['tags'] as $tag): ?>
                            <span class="kb-tag"><?= $tag ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Service Catalog Module -->
            <div id="catalog" class="module">
                <div style="margin-bottom: 20px;">
                    <h2>Service Catalog</h2>
                    <p style="color: var(--text-muted); margin-top: 5px;">Browse and request IT services</p>
                </div>

                <div class="search-box" style="width: 100%; max-width: 500px; margin-bottom: 20px;">
                    <span>🔍</span>
                    <input type="text" placeholder="What service do you need?" id="catalogSearch" onkeyup="searchCatalog()">
                </div>

                <div class="grid-3" id="catalogGrid">
                    <?php
                    $icons = ['👤', '💿', '🖥️', '🌐', '🔑', '🛡️', '📺', '💾'];
                    foreach($serviceCatalog as $idx => $service):
                    ?>
                    <div class="service-card" onclick="requestService('<?= $service['id'] ?>')">
                        <div class="service-icon"><?= $icons[$idx % count($icons)] ?></div>
                        <div class="service-title"><?= htmlspecialchars($service['name']) ?></div>
                        <div class="service-desc"><?= htmlspecialchars($service['description']) ?></div>
                        <div class="service-meta">
                            <span>⏱️ <?= $service['sla'] ?></span>
                            <span>📊 <?= $service['popularity'] ?>% popular</span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- SLA Management Module -->
            <div id="sla" class="module">
                <h2 style="margin-bottom: 20px;">SLA Management</h2>

                <div class="grid-2">
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">📊 SLA Performance Overview</div>
                        </div>
                        <div class="card-body">
                            <?php foreach($slaMetrics as $metric => $data):
                                $progressClass = $data['current'] >= $data['target'] ? 'good' : ($data['current'] >= $data['target'] - 5 ? 'warning' : 'danger');
                            ?>
                            <div style="margin-bottom: 25px;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                    <span style="text-transform: capitalize;"><?= $metric ?></span>
                                    <span>
                                        <strong><?= $data['current'] ?>%</strong>
                                        <span style="color: var(--text-muted);">/ <?= $data['target'] ?>% target</span>
                                        <?php if($data['trend'] === 'up'): ?>
                                            <span style="color: var(--success);">↑</span>
                                        <?php elseif($data['trend'] === 'down'): ?>
                                            <span style="color: var(--danger);">↓</span>
                                        <?php else: ?>
                                            <span style="color: var(--text-muted);">→</span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <div class="sla-bar">
                                    <div class="sla-progress <?= $progressClass ?>" style="width: <?= $data['current'] ?>%;"></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">📈 SLA Trend (Last 6 Months)</div>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="slaTrendChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div class="card-title">⏱️ SLA Definitions</div>
                    </div>
                    <div class="card-body">
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Priority</th>
                                        <th>Response Time</th>
                                        <th>Resolution Time</th>
                                        <th>Escalation</th>
                                        <th>Current Performance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><span class="badge badge-critical">Critical</span></td>
                                        <td>15 minutes</td>
                                        <td>4 hours</td>
                                        <td>After 1 hour</td>
                                        <td><span class="badge badge-success">94.5%</span></td>
                                    </tr>
                                    <tr>
                                        <td><span class="badge badge-high">High</span></td>
                                        <td>30 minutes</td>
                                        <td>8 hours</td>
                                        <td>After 2 hours</td>
                                        <td><span class="badge badge-success">91.2%</span></td>
                                    </tr>
                                    <tr>
                                        <td><span class="badge badge-medium">Medium</span></td>
                                        <td>2 hours</td>
                                        <td>24 hours</td>
                                        <td>After 4 hours</td>
                                        <td><span class="badge badge-success">96.8%</span></td>
                                    </tr>
                                    <tr>
                                        <td><span class="badge badge-low">Low</span></td>
                                        <td>4 hours</td>
                                        <td>72 hours</td>
                                        <td>After 24 hours</td>
                                        <td><span class="badge badge-success">98.3%</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Reports Module -->
            <div id="reports" class="module">
                <h2 style="margin-bottom: 20px;">Reports & Analytics</h2>

                <div class="grid-2">
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">📊 Monthly Ticket Volume</div>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="monthlyVolumeChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">📈 Resolution Time by Priority</div>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="resolutionTimeChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid-3">
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">🏆 Top Categories</div>
                        </div>
                        <div class="card-body">
                            <div style="margin-bottom: 15px;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                    <span>Network</span><span>32%</span>
                                </div>
                                <div class="sla-bar"><div class="sla-progress good" style="width: 32%;"></div></div>
                            </div>
                            <div style="margin-bottom: 15px;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                    <span>Application</span><span>28%</span>
                                </div>
                                <div class="sla-bar"><div class="sla-progress good" style="width: 28%;"></div></div>
                            </div>
                            <div style="margin-bottom: 15px;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                    <span>Hardware</span><span>22%</span>
                                </div>
                                <div class="sla-bar"><div class="sla-progress good" style="width: 22%;"></div></div>
                            </div>
                            <div style="margin-bottom: 15px;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                    <span>Security</span><span>18%</span>
                                </div>
                                <div class="sla-bar"><div class="sla-progress warning" style="width: 18%;"></div></div>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">👥 Top Performers</div>
                        </div>
                        <div class="card-body">
                            <?php
                            $sortedMembers = $teamMembers;
                            usort($sortedMembers, fn($a, $b) => $b['resolved'] - $a['resolved']);
                            foreach(array_slice($sortedMembers, 0, 4) as $idx => $member):
                            ?>
                            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 15px;">
                                <div style="width: 30px; height: 30px; border-radius: 50%; background: var(--gradient-primary); display: flex; align-items: center; justify-content: center; font-weight: bold;"><?= $idx + 1 ?></div>
                                <div style="flex: 1;">
                                    <div style="font-weight: 500;"><?= $member['name'] ?></div>
                                    <div style="font-size: 12px; color: var(--text-muted);"><?= $member['role'] ?></div>
                                </div>
                                <div style="text-align: right;">
                                    <div style="font-weight: 600; color: var(--success);"><?= $member['resolved'] ?></div>
                                    <div style="font-size: 11px; color: var(--text-muted);">resolved</div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">📋 Quick Reports</div>
                        </div>
                        <div class="card-body">
                            <button class="quick-action-btn" style="width: 100%; margin-bottom: 10px;" onclick="generateReport('weekly')">📊 Weekly Summary</button>
                            <button class="quick-action-btn" style="width: 100%; margin-bottom: 10px;" onclick="generateReport('monthly')">📈 Monthly Analysis</button>
                            <button class="quick-action-btn" style="width: 100%; margin-bottom: 10px;" onclick="generateReport('sla')">⏱️ SLA Report</button>
                            <button class="quick-action-btn" style="width: 100%;" onclick="generateReport('assets')">💻 Asset Inventory</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Team Module -->
            <div id="team" class="module">
                <h2 style="margin-bottom: 20px;">Team Management</h2>

                <div class="grid-3">
                    <?php foreach($teamMembers as $member): ?>
                    <div class="card">
                        <div class="card-body" style="text-align: center;">
                            <div style="width: 80px; height: 80px; border-radius: 50%; background: var(--gradient-primary); margin: 0 auto 15px; display: flex; align-items: center; justify-content: center; font-size: 32px;">
                                <?= strtoupper(substr($member['name'], 0, 1)) ?>
                            </div>
                            <h3 style="margin-bottom: 5px;"><?= $member['name'] ?></h3>
                            <div style="color: var(--text-muted); margin-bottom: 15px;"><?= $member['role'] ?></div>
                            <div style="display: flex; justify-content: center; gap: 10px; margin-bottom: 15px;">
                                <span class="status-dot <?= $member['status'] ?>"></span>
                                <span style="text-transform: capitalize;"><?= $member['status'] ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-around; padding-top: 15px; border-top: 1px solid var(--card-border);">
                                <div>
                                    <div style="font-size: 24px; font-weight: 600; color: var(--warning);"><?= $member['tickets'] ?></div>
                                    <div style="font-size: 12px; color: var(--text-muted);">Active</div>
                                </div>
                                <div>
                                    <div style="font-size: 24px; font-weight: 600; color: var(--success);"><?= $member['resolved'] ?></div>
                                    <div style="font-size: 12px; color: var(--text-muted);">Resolved</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- New Ticket Modal -->
    <div class="modal-overlay" id="newTicketModal">
        <div class="modal">
            <div class="modal-header">
                <div class="modal-title">➕ Create New Incident</div>
                <button class="modal-close" onclick="hideModal('newTicketModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="newTicketForm" onsubmit="submitTicket(event)">
                    <div class="form-group">
                        <label class="form-label">Title *</label>
                        <input type="text" class="form-control" name="title" required placeholder="Brief description of the issue">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Priority *</label>
                            <select class="form-control" name="priority" required>
                                <option value="">Select priority</option>
                                <option value="critical">Critical - Business down</option>
                                <option value="high">High - Major impact</option>
                                <option value="medium">Medium - Limited impact</option>
                                <option value="low">Low - Minor issue</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Category *</label>
                            <select class="form-control" name="category" required>
                                <option value="">Select category</option>
                                <option value="Infrastructure">Infrastructure</option>
                                <option value="Network">Network</option>
                                <option value="Application">Application</option>
                                <option value="Hardware">Hardware</option>
                                <option value="Software">Software</option>
                                <option value="Security">Security</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Requester *</label>
                            <input type="text" class="form-control" name="requester" required placeholder="Your name or department">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Affected Users</label>
                            <input type="number" class="form-control" name="affected_users" placeholder="Number of affected users">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Description *</label>
                        <textarea class="form-control" name="description" required placeholder="Detailed description of the incident..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="hideModal('newTicketModal')">Cancel</button>
                <button class="btn btn-primary" onclick="document.getElementById('newTicketForm').requestSubmit()" style="background: var(--gradient-primary); color: white;">Create Incident</button>
            </div>
        </div>
    </div>

    <!-- Search Modal -->
    <div class="modal-overlay" id="searchModal">
        <div class="modal" style="max-width: 600px;">
            <div class="modal-header">
                <div class="modal-title">🔍 Global Search</div>
                <button class="modal-close" onclick="hideModal('searchModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="search-box" style="width: 100%; margin-bottom: 20px;">
                    <span>🔍</span>
                    <input type="text" placeholder="Search incidents, problems, assets, KB articles..." id="globalSearch" onkeyup="performGlobalSearch()">
                </div>
                <div id="searchResults">
                    <div style="color: var(--text-muted); text-align: center; padding: 40px;">
                        Start typing to search across all modules...
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Incident Detail Modal -->
    <div class="modal-overlay" id="incidentDetailModal">
        <div class="modal" style="max-width: 800px;">
            <div class="modal-header">
                <div class="modal-title" id="incidentDetailTitle">Incident Details</div>
                <button class="modal-close" onclick="hideModal('incidentDetailModal')">&times;</button>
            </div>
            <div class="modal-body" id="incidentDetailContent">
                <!-- Content populated by JavaScript -->
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="hideModal('incidentDetailModal')">Close</button>
                <button class="btn btn-primary" style="background: var(--gradient-success); color: white;">Update Status</button>
            </div>
        </div>
    </div>

    <!-- New Change Request Modal -->
    <div class="modal-overlay" id="newChangeModal">
        <div class="modal" style="max-width: 700px;">
            <div class="modal-header">
                <div class="modal-title">📋 Submit Change Request</div>
                <button class="modal-close" onclick="hideModal('newChangeModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="newChangeForm" onsubmit="submitChangeRequest(event)">
                    <div class="form-group">
                        <label class="form-label">Change Title *</label>
                        <input type="text" class="form-control" name="title" required placeholder="Brief description of the change">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Change Type *</label>
                            <select class="form-control" name="type" required>
                                <option value="">Select type</option>
                                <option value="standard">Standard - Pre-approved</option>
                                <option value="normal">Normal - Requires CAB approval</option>
                                <option value="emergency">Emergency - Urgent fix</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Priority *</label>
                            <select class="form-control" name="priority" required>
                                <option value="">Select priority</option>
                                <option value="critical">Critical</option>
                                <option value="high">High</option>
                                <option value="medium">Medium</option>
                                <option value="low">Low</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Category *</label>
                            <select class="form-control" name="category" required>
                                <option value="">Select category</option>
                                <option value="Infrastructure">Infrastructure</option>
                                <option value="Network">Network</option>
                                <option value="Application">Application</option>
                                <option value="Security">Security</option>
                                <option value="Database">Database</option>
                                <option value="Hardware">Hardware</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Scheduled Date/Time *</label>
                            <input type="datetime-local" class="form-control" name="scheduled" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Impact Level *</label>
                            <select class="form-control" name="impact" required>
                                <option value="">Select impact</option>
                                <option value="low">Low - Single user/system</option>
                                <option value="medium">Medium - Department/group</option>
                                <option value="high">High - Organization-wide</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Risk Level *</label>
                            <select class="form-control" name="risk" required>
                                <option value="">Select risk</option>
                                <option value="low">Low - Minimal risk</option>
                                <option value="medium">Medium - Some risk</option>
                                <option value="high">High - Significant risk</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Requestor *</label>
                            <input type="text" class="form-control" name="requestor" required placeholder="Your name or team">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Implementer *</label>
                            <input type="text" class="form-control" name="implementer" required placeholder="Team responsible for implementation">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Change Description *</label>
                        <textarea class="form-control" name="description" required placeholder="Detailed description of the change, including scope and objectives..."></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Rollback Plan *</label>
                        <textarea class="form-control" name="rollback_plan" required placeholder="Steps to revert the change if issues occur..." style="min-height: 80px;"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Testing Plan</label>
                        <textarea class="form-control" name="testing_plan" placeholder="How will you verify the change was successful?"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Attachments</label>
                        <div class="file-upload-area" id="changeFileUpload" onclick="document.getElementById('changeAttachments').click()">
                            <input type="file" id="changeAttachments" name="attachments" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.txt,.png,.jpg,.jpeg,.zip" style="display: none;" onchange="handleChangeAttachments(this)">
                            <div class="file-upload-icon">📎</div>
                            <div class="file-upload-text">Click to upload or drag files here</div>
                            <div class="file-upload-hint">PDF, DOC, XLS, TXT, Images, ZIP (Max 10MB each)</div>
                        </div>
                        <div id="changeAttachmentList" class="attachment-list"></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="hideModal('newChangeModal')">Cancel</button>
                <button class="btn btn-primary" onclick="document.getElementById('newChangeForm').requestSubmit()" style="background: var(--gradient-primary); color: white;">Submit Change Request</button>
            </div>
        </div>
    </div>

    <!-- New Problem Modal -->
    <div class="modal-overlay" id="newProblemModal">
        <div class="modal" style="max-width: 650px;">
            <div class="modal-header">
                <div class="modal-title">🔴 Create New Problem</div>
                <button class="modal-close" onclick="hideModal('newProblemModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="newProblemForm" onsubmit="submitProblem(event)">
                    <div class="form-group">
                        <label class="form-label">Problem Title *</label>
                        <input type="text" class="form-control" name="title" required placeholder="Brief description of the problem">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Priority *</label>
                            <select class="form-control" name="priority" required>
                                <option value="">Select priority</option>
                                <option value="critical">Critical</option>
                                <option value="high">High</option>
                                <option value="medium">Medium</option>
                                <option value="low">Low</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Category *</label>
                            <select class="form-control" name="category" required>
                                <option value="">Select category</option>
                                <option value="Infrastructure">Infrastructure</option>
                                <option value="Network">Network</option>
                                <option value="Application">Application</option>
                                <option value="Security">Security</option>
                                <option value="Hardware">Hardware</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Affected Services</label>
                            <input type="text" class="form-control" name="affected_services" placeholder="e.g., Email, VPN, ERP">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Related Incidents</label>
                            <input type="text" class="form-control" name="related_incidents" placeholder="e.g., INC-001, INC-002">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Problem Description *</label>
                        <textarea class="form-control" name="description" required placeholder="Detailed description of the problem and its symptoms..."></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Root Cause Analysis</label>
                        <textarea class="form-control" name="root_cause" placeholder="Initial analysis of the root cause (if known)..."></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Workaround</label>
                        <textarea class="form-control" name="workaround" placeholder="Temporary workaround if available..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="hideModal('newProblemModal')">Cancel</button>
                <button class="btn btn-primary" onclick="document.getElementById('newProblemForm').requestSubmit()" style="background: var(--gradient-primary); color: white;">Create Problem</button>
            </div>
        </div>
    </div>

    <!-- New Asset Modal -->
    <div class="modal-overlay" id="newAssetModal">
        <div class="modal" style="max-width: 650px;">
            <div class="modal-header">
                <div class="modal-title">💻 Add New Asset</div>
                <button class="modal-close" onclick="hideModal('newAssetModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="newAssetForm" onsubmit="submitAsset(event)">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Asset Name *</label>
                            <input type="text" class="form-control" name="name" required placeholder="e.g., PROD-WEB-01">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Asset Type *</label>
                            <select class="form-control" name="type" required>
                                <option value="">Select type</option>
                                <option value="Server">Server</option>
                                <option value="Laptop">Laptop</option>
                                <option value="Desktop">Desktop</option>
                                <option value="Network">Network Device</option>
                                <option value="Firewall">Firewall</option>
                                <option value="Storage">Storage</option>
                                <option value="Software">Software License</option>
                                <option value="Printer">Printer</option>
                                <option value="Mobile">Mobile Device</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Status *</label>
                            <select class="form-control" name="status" required>
                                <option value="active">Active</option>
                                <option value="maintenance">Maintenance</option>
                                <option value="retired">Retired</option>
                                <option value="storage">In Storage</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Location *</label>
                            <input type="text" class="form-control" name="location" required placeholder="e.g., Data Center A, Floor 3">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Assigned To</label>
                            <input type="text" class="form-control" name="assigned_to" placeholder="User or department">
                        </div>
                        <div class="form-group">
                            <label class="form-label">IP Address</label>
                            <input type="text" class="form-control" name="ip_address" placeholder="e.g., 192.168.1.100">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Serial Number</label>
                            <input type="text" class="form-control" name="serial" placeholder="Manufacturer serial number">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Purchase Cost ($)</label>
                            <input type="number" class="form-control" name="cost" placeholder="e.g., 1500">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Purchase Date</label>
                            <input type="date" class="form-control" name="purchase_date">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Warranty Expiry</label>
                            <input type="date" class="form-control" name="warranty_expiry">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" placeholder="Additional notes about the asset..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="hideModal('newAssetModal')">Cancel</button>
                <button class="btn btn-primary" onclick="document.getElementById('newAssetForm').requestSubmit()" style="background: var(--gradient-primary); color: white;">Add Asset</button>
            </div>
        </div>
    </div>

    <!-- New KB Article Modal -->
    <div class="modal-overlay" id="newArticleModal">
        <div class="modal" style="max-width: 700px;">
            <div class="modal-header">
                <div class="modal-title">📚 Create Knowledge Article</div>
                <button class="modal-close" onclick="hideModal('newArticleModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="newArticleForm" onsubmit="submitArticle(event)">
                    <div class="form-group">
                        <label class="form-label">Article Title *</label>
                        <input type="text" class="form-control" name="title" required placeholder="Clear, descriptive title">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Category *</label>
                            <select class="form-control" name="category" required>
                                <option value="">Select category</option>
                                <option value="Network">Network</option>
                                <option value="Email">Email</option>
                                <option value="Hardware">Hardware</option>
                                <option value="Software">Software</option>
                                <option value="Security">Security</option>
                                <option value="Process">Process</option>
                                <option value="How-To">How-To Guide</option>
                                <option value="FAQ">FAQ</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Visibility *</label>
                            <select class="form-control" name="visibility" required>
                                <option value="public">Public - All Users</option>
                                <option value="internal">Internal - Staff Only</option>
                                <option value="restricted">Restricted - IT Only</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Summary *</label>
                        <input type="text" class="form-control" name="summary" required placeholder="Brief summary of the article content">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Article Content *</label>
                        <textarea class="form-control" name="content" required placeholder="Full article content. You can use markdown formatting..." style="min-height: 200px;"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tags</label>
                        <input type="text" class="form-control" name="tags" placeholder="Comma-separated tags, e.g., vpn, remote, access">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Related Articles</label>
                        <input type="text" class="form-control" name="related" placeholder="IDs of related articles, e.g., KB001, KB002">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="hideModal('newArticleModal')">Cancel</button>
                <button class="btn btn-primary" onclick="document.getElementById('newArticleForm').requestSubmit()" style="background: var(--gradient-primary); color: white;">Publish Article</button>
            </div>
        </div>
    </div>

    <script>
        // Navigation
        function showModule(moduleId) {
            document.querySelectorAll('.module').forEach(m => m.classList.remove('active'));
            document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));

            document.getElementById(moduleId).classList.add('active');
            event.target.closest('.nav-item')?.classList.add('active');
        }

        // Modals
        function showModal(modalId) {
            document.getElementById(modalId).classList.add('show');
        }

        function hideModal(modalId) {
            document.getElementById(modalId).classList.remove('show');
        }

        // Close modal on outside click
        document.querySelectorAll('.modal-overlay').forEach(overlay => {
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) {
                    overlay.classList.remove('show');
                }
            });
        });

        // Incident Functions
        function filterIncidents(status) {
            document.querySelectorAll('.filters .filter-btn').forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');

            document.querySelectorAll('.incident-row').forEach(row => {
                if (status === 'all' || row.dataset.status === status) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function searchIncidents() {
            const search = document.getElementById('incidentSearch').value.toLowerCase();
            document.querySelectorAll('.incident-row').forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(search) ? '' : 'none';
            });
        }

        function viewIncident(id) {
            const incidents = <?= json_encode($incidents) ?>;
            const incident = incidents.find(i => i.id === id);

            if (incident) {
                document.getElementById('incidentDetailTitle').textContent = incident.id + ' - ' + incident.title;
                document.getElementById('incidentDetailContent').innerHTML = `
                    <div class="grid-2" style="margin-bottom: 20px;">
                        <div>
                            <p><strong>Status:</strong> <span class="badge badge-${incident.status}">${incident.status.replace('_', ' ')}</span></p>
                            <p><strong>Priority:</strong> <span class="badge badge-${incident.priority}">${incident.priority}</span></p>
                            <p><strong>Category:</strong> ${incident.category}</p>
                            <p><strong>Assignee:</strong> ${incident.assignee}</p>
                        </div>
                        <div>
                            <p><strong>Requester:</strong> ${incident.requester}</p>
                            <p><strong>Created:</strong> ${incident.created}</p>
                            <p><strong>SLA Due:</strong> ${incident.sla_due}</p>
                            <p><strong>Affected Users:</strong> ${incident.affected_users}</p>
                        </div>
                    </div>
                    <div style="margin-bottom: 20px;">
                        <strong>Description:</strong>
                        <p style="margin-top: 10px; padding: 15px; background: var(--darker); border-radius: 8px;">${incident.description}</p>
                    </div>
                    <div>
                        <strong>Activity Timeline:</strong>
                        <div class="timeline" style="margin-top: 15px;">
                            <div class="timeline-item">
                                <div class="timeline-time">${incident.created}</div>
                                <div class="timeline-content">Incident created by ${incident.requester}</div>
                            </div>
                            <div class="timeline-item">
                                <div class="timeline-time">${incident.updated}</div>
                                <div class="timeline-content">Assigned to ${incident.assignee}</div>
                            </div>
                        </div>
                    </div>
                `;
                showModal('incidentDetailModal');
            }
        }

        function editIncident(id) {
            alert('Edit incident: ' + id);
        }

        function assignIncident(id) {
            alert('Assign incident: ' + id);
        }

        // Asset Functions
        function filterAssets(type) {
            document.querySelectorAll('.filters .filter-btn').forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');

            document.querySelectorAll('.asset-row').forEach(row => {
                if (type === 'all' || row.dataset.type === type) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function searchAssets() {
            const search = document.getElementById('assetSearch').value.toLowerCase();
            document.querySelectorAll('.asset-row').forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(search) ? '' : 'none';
            });
        }

        function viewAsset(id) {
            alert('View asset: ' + id);
        }

        function editAsset(id) {
            alert('Edit asset: ' + id);
        }

        // Knowledge Base Functions
        function filterKB(category) {
            document.querySelectorAll('.filters .filter-btn').forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');

            document.querySelectorAll('.kb-card').forEach(card => {
                if (category === 'all' || card.dataset.category === category) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        function searchKB() {
            const search = document.getElementById('kbSearch').value.toLowerCase();
            document.querySelectorAll('.kb-card').forEach(card => {
                const text = card.textContent.toLowerCase();
                card.style.display = text.includes(search) ? '' : 'none';
            });
        }

        function viewArticle(id) {
            alert('View article: ' + id);
        }

        // Service Catalog Functions
        function searchCatalog() {
            const search = document.getElementById('catalogSearch').value.toLowerCase();
            document.querySelectorAll('.service-card').forEach(card => {
                const text = card.textContent.toLowerCase();
                card.style.display = text.includes(search) ? '' : 'none';
            });
        }

        function requestService(id) {
            alert('Request service: ' + id);
        }

        // Problem Functions
        function viewProblem(id) {
            alert('View problem: ' + id);
        }

        function linkIncident(id) {
            alert('Link incident to problem: ' + id);
        }

        // Change Functions
        function viewChange(id) {
            alert('View change: ' + id);
        }

        function approveChange(id) {
            if (confirm('Approve change ' + id + '?')) {
                alert('Change approved!');
            }
        }

        function rejectChange(id) {
            if (confirm('Reject change ' + id + '?')) {
                alert('Change rejected!');
            }
        }

        // Report Functions
        function generateReport(type) {
            alert('Generating ' + type + ' report...');
        }

        // Form Submission
        function submitTicket(e) {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);

            alert('Incident created successfully!\n\nTitle: ' + formData.get('title') + '\nPriority: ' + formData.get('priority'));
            hideModal('newTicketModal');
            form.reset();
        }

        // Submit Change Request
        function submitChangeRequest(e) {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);

            // Generate change ID
            const changeId = 'CHG-' + new Date().getFullYear() + '-' + String(Math.floor(Math.random() * 900) + 100);

            // Get form values
            const title = formData.get('title');
            const type = formData.get('type');
            const priority = formData.get('priority');
            const category = formData.get('category');
            const scheduled = formData.get('scheduled');
            const impact = formData.get('impact');
            const risk = formData.get('risk');
            const requestor = formData.get('requestor');

            // Format scheduled date for display
            const scheduledDate = new Date(scheduled);
            const formattedDate = scheduledDate.toLocaleDateString('en-US', { month: 'short', day: '2-digit' }) + ', ' +
                                  scheduledDate.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: false });

            // Get type badge class
            const typeBadgeClass = type === 'emergency' ? 'critical' : (type === 'normal' ? 'medium' : 'low');

            // Create new table row
            const newRow = document.createElement('tr');
            newRow.style.animation = 'fadeIn 0.5s ease';
            // Get attachment info
            const attachmentCount = changeAttachmentsFiles.length;
            const attachmentBadge = attachmentCount > 0
                ? `<span style="margin-left: 5px; font-size: 11px; color: var(--primary);">📎 ${attachmentCount} file${attachmentCount > 1 ? 's' : ''}</span>`
                : '';

            newRow.innerHTML = `
                <td><code>${changeId}</code></td>
                <td>
                    <strong>${title}</strong>${attachmentBadge}
                    <div style="font-size: 12px; color: var(--text-muted);">By: ${requestor}</div>
                </td>
                <td><span class="badge badge-${typeBadgeClass}">${type}</span></td>
                <td><span class="badge badge-${priority}">${priority}</span></td>
                <td><span class="badge badge-pending_approval">pending approval</span></td>
                <td>${formattedDate}</td>
                <td><span class="badge badge-${impact}">${impact}</span></td>
                <td><span class="badge badge-${risk}">${risk}</span></td>
                <td>
                    <button class="action-btn" data-tooltip="View" onclick="viewChange('${changeId}')">👁️</button>
                    <button class="action-btn" data-tooltip="Approve" onclick="approveChange('${changeId}')">✅</button>
                    <button class="action-btn" data-tooltip="Reject" onclick="rejectChange('${changeId}')">❌</button>
                </td>
            `;

            // Add row to table at the beginning
            const tableBody = document.getElementById('changesTableBody');
            tableBody.insertBefore(newRow, tableBody.firstChild);

            // Update the Pending Approval stat card
            const pendingCard = document.querySelector('#changes .stat-card.warning .stat-value');
            if (pendingCard) {
                pendingCard.textContent = parseInt(pendingCard.textContent) + 1;
            }

            // Show success notification with attachment info
            const attachmentMsg = attachmentCount > 0 ? ` with ${attachmentCount} attachment${attachmentCount > 1 ? 's' : ''}` : '';
            showNotification('✅ Change Request ' + changeId + ' submitted successfully' + attachmentMsg + '!', 'success');

            hideModal('newChangeModal');
            form.reset();

            // Clear attachments
            changeAttachmentsFiles = [];
            document.getElementById('changeAttachmentList').innerHTML = '';

            // Switch to changes tab to show the new entry
            showModule('changes');
        }

        // Show notification toast
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 25px;
                background: ${type === 'success' ? 'linear-gradient(135deg, #10b981, #059669)' : 'linear-gradient(135deg, #3b82f6, #2563eb)'};
                color: white;
                border-radius: 10px;
                box-shadow: 0 10px 40px rgba(0,0,0,0.3);
                z-index: 10000;
                animation: slideIn 0.3s ease;
                font-weight: 500;
            `;
            notification.textContent = message;
            document.body.appendChild(notification);

            setTimeout(() => {
                notification.style.animation = 'fadeOut 0.3s ease';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        // Change attachments storage
        let changeAttachmentsFiles = [];

        // Handle change attachments
        function handleChangeAttachments(input) {
            const files = Array.from(input.files);
            const maxSize = 10 * 1024 * 1024; // 10MB

            files.forEach(file => {
                if (file.size > maxSize) {
                    showNotification(`File "${file.name}" exceeds 10MB limit`, 'error');
                    return;
                }
                if (!changeAttachmentsFiles.find(f => f.name === file.name)) {
                    changeAttachmentsFiles.push(file);
                }
            });

            renderChangeAttachments();
        }

        // Render attachment list
        function renderChangeAttachments() {
            const listContainer = document.getElementById('changeAttachmentList');
            listContainer.innerHTML = '';

            changeAttachmentsFiles.forEach((file, index) => {
                const icon = getFileIcon(file.name);
                const size = formatFileSize(file.size);

                const item = document.createElement('div');
                item.className = 'attachment-item';
                item.innerHTML = `
                    <div class="attachment-info">
                        <span class="attachment-icon">${icon}</span>
                        <div>
                            <div class="attachment-name">${file.name}</div>
                            <div class="attachment-size">${size}</div>
                        </div>
                    </div>
                    <button type="button" class="attachment-remove" onclick="removeChangeAttachment(${index})">×</button>
                `;
                listContainer.appendChild(item);
            });
        }

        // Remove attachment
        function removeChangeAttachment(index) {
            changeAttachmentsFiles.splice(index, 1);
            renderChangeAttachments();
        }

        // Get file icon based on extension
        function getFileIcon(filename) {
            const ext = filename.split('.').pop().toLowerCase();
            const icons = {
                'pdf': '📄',
                'doc': '📝', 'docx': '📝',
                'xls': '📊', 'xlsx': '📊',
                'txt': '📃',
                'png': '🖼️', 'jpg': '🖼️', 'jpeg': '🖼️', 'gif': '🖼️',
                'zip': '📦', 'rar': '📦', '7z': '📦'
            };
            return icons[ext] || '📎';
        }

        // Format file size
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        // Setup drag and drop for file upload
        document.addEventListener('DOMContentLoaded', function() {
            const uploadArea = document.getElementById('changeFileUpload');
            if (uploadArea) {
                uploadArea.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    uploadArea.classList.add('dragover');
                });

                uploadArea.addEventListener('dragleave', () => {
                    uploadArea.classList.remove('dragover');
                });

                uploadArea.addEventListener('drop', (e) => {
                    e.preventDefault();
                    uploadArea.classList.remove('dragover');
                    const files = e.dataTransfer.files;
                    document.getElementById('changeAttachments').files = files;
                    handleChangeAttachments(document.getElementById('changeAttachments'));
                });
            }
        });

        // Submit Problem
        function submitProblem(e) {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);

            // Generate problem ID
            const problemId = 'PRB-' + new Date().getFullYear() + '-' + String(Math.floor(Math.random() * 900) + 100);

            alert('✅ Problem Record Created!\n\n' +
                  'Problem ID: ' + problemId + '\n' +
                  'Title: ' + formData.get('title') + '\n' +
                  'Priority: ' + formData.get('priority').charAt(0).toUpperCase() + formData.get('priority').slice(1) + '\n' +
                  'Category: ' + formData.get('category') + '\n\n' +
                  'Status: Under Investigation\n' +
                  'The problem has been logged and assigned for root cause analysis.');

            hideModal('newProblemModal');
            form.reset();
        }

        // Submit Asset
        function submitAsset(e) {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);

            // Generate asset ID
            const assetId = 'AST-' + String(Math.floor(Math.random() * 9000) + 1000);

            alert('✅ Asset Added Successfully!\n\n' +
                  'Asset ID: ' + assetId + '\n' +
                  'Name: ' + formData.get('name') + '\n' +
                  'Type: ' + formData.get('type') + '\n' +
                  'Status: ' + formData.get('status').charAt(0).toUpperCase() + formData.get('status').slice(1) + '\n' +
                  'Location: ' + formData.get('location') + '\n\n' +
                  'The asset has been added to the inventory.');

            hideModal('newAssetModal');
            form.reset();
        }

        // Submit KB Article
        function submitArticle(e) {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);

            // Generate article ID
            const articleId = 'KB' + String(Math.floor(Math.random() * 900) + 100);

            alert('✅ Knowledge Article Published!\n\n' +
                  'Article ID: ' + articleId + '\n' +
                  'Title: ' + formData.get('title') + '\n' +
                  'Category: ' + formData.get('category') + '\n' +
                  'Visibility: ' + formData.get('visibility') + '\n\n' +
                  'The article is now available in the Knowledge Base.');

            hideModal('newArticleModal');
            form.reset();
        }

        // Global Search
        function performGlobalSearch() {
            const search = document.getElementById('globalSearch').value.toLowerCase();
            const resultsContainer = document.getElementById('searchResults');

            if (search.length < 2) {
                resultsContainer.innerHTML = '<div style="color: var(--text-muted); text-align: center; padding: 40px;">Start typing to search across all modules...</div>';
                return;
            }

            const incidents = <?= json_encode($incidents) ?>;
            const assets = <?= json_encode($assets) ?>;
            const kb = <?= json_encode($knowledgeBase) ?>;

            let results = [];

            incidents.forEach(i => {
                if (i.title.toLowerCase().includes(search) || i.id.toLowerCase().includes(search)) {
                    results.push({ type: 'Incident', icon: '🔥', id: i.id, title: i.title, status: i.status });
                }
            });

            assets.forEach(a => {
                if (a.name.toLowerCase().includes(search) || a.id.toLowerCase().includes(search)) {
                    results.push({ type: 'Asset', icon: '💻', id: a.id, title: a.name, status: a.status });
                }
            });

            kb.forEach(k => {
                if (k.title.toLowerCase().includes(search)) {
                    results.push({ type: 'KB Article', icon: '📄', id: k.id, title: k.title, status: null });
                }
            });

            if (results.length === 0) {
                resultsContainer.innerHTML = '<div style="color: var(--text-muted); text-align: center; padding: 40px;">No results found</div>';
            } else {
                resultsContainer.innerHTML = results.map(r => `
                    <div style="padding: 15px; border-bottom: 1px solid var(--card-border); cursor: pointer;" onclick="hideModal('searchModal')">
                        <span style="margin-right: 10px;">${r.icon}</span>
                        <strong>${r.type}</strong> - ${r.id}: ${r.title}
                        ${r.status ? `<span class="badge badge-${r.status}" style="margin-left: 10px;">${r.status}</span>` : ''}
                    </div>
                `).join('');
            }
        }

        // Charts
        document.addEventListener('DOMContentLoaded', function() {
            // Incident Trend Chart
            new Chart(document.getElementById('incidentTrendChart'), {
                type: 'line',
                data: {
                    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                    datasets: [{
                        label: 'New Incidents',
                        data: [12, 19, 15, 8, 22, 5, 3],
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        tension: 0.4,
                        fill: true
                    }, {
                        label: 'Resolved',
                        data: [10, 15, 18, 12, 20, 8, 5],
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { labels: { color: '#e2e8f0' } } },
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
                    labels: ['Network', 'Application', 'Hardware', 'Security', 'Infrastructure'],
                    datasets: [{
                        data: [32, 28, 22, 10, 8],
                        backgroundColor: ['#667eea', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'right', labels: { color: '#e2e8f0' } } }
                }
            });

            // SLA Trend Chart
            new Chart(document.getElementById('slaTrendChart'), {
                type: 'line',
                data: {
                    labels: ['Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                    datasets: [{
                        label: 'SLA Compliance %',
                        data: [89, 91, 90, 93, 91, 92.5],
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        tension: 0.4,
                        fill: true
                    }, {
                        label: 'Target',
                        data: [95, 95, 95, 95, 95, 95],
                        borderColor: '#ef4444',
                        borderDash: [5, 5],
                        fill: false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { labels: { color: '#e2e8f0' } } },
                    scales: {
                        y: { min: 80, max: 100, grid: { color: 'rgba(255,255,255,0.1)' }, ticks: { color: '#94a3b8' } },
                        x: { grid: { color: 'rgba(255,255,255,0.1)' }, ticks: { color: '#94a3b8' } }
                    }
                }
            });

            // Monthly Volume Chart
            new Chart(document.getElementById('monthlyVolumeChart'), {
                type: 'bar',
                data: {
                    labels: ['Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                    datasets: [{
                        label: 'Incidents',
                        data: [145, 132, 158, 141, 167, 152],
                        backgroundColor: '#667eea'
                    }, {
                        label: 'Service Requests',
                        data: [89, 95, 102, 88, 110, 98],
                        backgroundColor: '#10b981'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { labels: { color: '#e2e8f0' } } },
                    scales: {
                        y: { grid: { color: 'rgba(255,255,255,0.1)' }, ticks: { color: '#94a3b8' } },
                        x: { grid: { color: 'rgba(255,255,255,0.1)' }, ticks: { color: '#94a3b8' } }
                    }
                }
            });

            // Resolution Time Chart
            new Chart(document.getElementById('resolutionTimeChart'), {
                type: 'bar',
                data: {
                    labels: ['Critical', 'High', 'Medium', 'Low'],
                    datasets: [{
                        label: 'Avg Resolution (hours)',
                        data: [3.2, 6.8, 18.5, 45.2],
                        backgroundColor: ['#ef4444', '#f59e0b', '#3b82f6', '#10b981']
                    }, {
                        label: 'SLA Target (hours)',
                        data: [4, 8, 24, 72],
                        backgroundColor: 'rgba(255,255,255,0.2)'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { labels: { color: '#e2e8f0' } } },
                    scales: {
                        y: { grid: { color: 'rgba(255,255,255,0.1)' }, ticks: { color: '#94a3b8' } },
                        x: { grid: { color: 'rgba(255,255,255,0.1)' }, ticks: { color: '#94a3b8' } }
                    }
                }
            });
        });

        // Keyboard shortcut for search
        document.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                showModal('searchModal');
                document.getElementById('globalSearch').focus();
            }
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal-overlay.show').forEach(m => m.classList.remove('show'));
            }
        });
    </script>
</body>
</html>
