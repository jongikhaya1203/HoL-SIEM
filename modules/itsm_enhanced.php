<?php
/**
 * Enhanced IT Service Management (ITSM) Platform
 * Full-featured ITSM with 10 advanced capabilities
 */

require_once __DIR__ . '/../classes/Database.php';
$db = Database::getInstance();

// ========== MULTI-CHANNEL TICKETS DATA ==========
$tickets = [
    ['id' => 'TKT-001', 'title' => 'Email server down', 'channel' => 'Email', 'priority' => 'Critical', 'status' => 'In Progress', 'agent' => 'John Smith', 'sla_remaining' => 45, 'satisfaction' => null, 'created' => '2024-01-15 08:30:00'],
    ['id' => 'TKT-002', 'title' => 'Password reset request', 'channel' => 'Web Portal', 'priority' => 'Low', 'status' => 'Resolved', 'agent' => 'Sarah Lee', 'sla_remaining' => 0, 'satisfaction' => 5, 'created' => '2024-01-15 09:15:00'],
    ['id' => 'TKT-003', 'title' => 'VPN connection issues', 'channel' => 'Phone', 'priority' => 'High', 'status' => 'Assigned', 'agent' => 'Mike Davis', 'sla_remaining' => 120, 'satisfaction' => null, 'created' => '2024-01-15 10:00:00'],
    ['id' => 'TKT-004', 'title' => 'Printer not working', 'channel' => 'Chat', 'priority' => 'Medium', 'status' => 'New', 'agent' => 'Unassigned', 'sla_remaining' => 240, 'satisfaction' => null, 'created' => '2024-01-15 10:30:00'],
    ['id' => 'TKT-005', 'title' => 'Application slow performance', 'channel' => 'Email', 'priority' => 'Medium', 'status' => 'On Hold', 'agent' => 'Emily Chen', 'sla_remaining' => 180, 'satisfaction' => null, 'created' => '2024-01-15 11:00:00'],
    ['id' => 'TKT-006', 'title' => 'New software installation', 'channel' => 'Web Portal', 'priority' => 'Low', 'status' => 'Resolved', 'agent' => 'David Lee', 'sla_remaining' => 0, 'satisfaction' => 4, 'created' => '2024-01-14 14:00:00'],
    ['id' => 'TKT-007', 'title' => 'Network connectivity loss', 'channel' => 'Phone', 'priority' => 'Critical', 'status' => 'Resolved', 'agent' => 'John Smith', 'sla_remaining' => 0, 'satisfaction' => 5, 'created' => '2024-01-14 09:00:00'],
    ['id' => 'TKT-008', 'title' => 'Access permission request', 'channel' => 'Chat', 'priority' => 'Medium', 'status' => 'In Progress', 'agent' => 'Sarah Lee', 'sla_remaining' => 200, 'satisfaction' => null, 'created' => '2024-01-15 08:00:00'],
];

// ========== SLA DEFINITIONS ==========
$sla_definitions = [
    ['priority' => 'Critical', 'response_time' => 15, 'resolution_time' => 240, 'color' => '#f44336'],
    ['priority' => 'High', 'response_time' => 30, 'resolution_time' => 480, 'color' => '#ff9800'],
    ['priority' => 'Medium', 'response_time' => 60, 'resolution_time' => 960, 'color' => '#2196F3'],
    ['priority' => 'Low', 'response_time' => 120, 'resolution_time' => 1440, 'color' => '#4CAF50'],
];

// ========== ROUTING RULES ==========
$routing_rules = [
    ['id' => 1, 'name' => 'Critical Priority Auto-Assign', 'condition' => 'Priority = Critical', 'action' => 'Assign to Senior Team', 'status' => 'Active', 'matched' => 127],
    ['id' => 2, 'name' => 'Email Channel Routing', 'condition' => 'Channel = Email', 'action' => 'Route to Email Support Queue', 'status' => 'Active', 'matched' => 543],
    ['id' => 3, 'name' => 'Network Keywords', 'condition' => 'Keywords: VPN, Network, Connectivity', 'action' => 'Assign to Network Team', 'status' => 'Active', 'matched' => 231],
    ['id' => 4, 'name' => 'After Hours Escalation', 'condition' => 'Time = After 6PM', 'action' => 'Escalate to On-Call Engineer', 'status' => 'Active', 'matched' => 89],
    ['id' => 5, 'name' => 'VIP Customer Priority', 'condition' => 'Customer Type = VIP', 'action' => 'Set Priority High + Notify Manager', 'status' => 'Active', 'matched' => 45],
];

// ========== SERVICE CATALOG ==========
$service_catalog = [
    ['id' => 'SVC-001', 'category' => 'Access Management', 'service' => 'New User Account', 'sla' => '24 hours', 'approver' => 'IT Manager', 'requests' => 45, 'avg_time' => '4 hours'],
    ['id' => 'SVC-002', 'category' => 'Access Management', 'service' => 'Password Reset', 'sla' => '2 hours', 'approver' => 'Auto-Approved', 'requests' => 234, 'avg_time' => '15 min'],
    ['id' => 'SVC-003', 'category' => 'Hardware', 'service' => 'New Laptop Request', 'sla' => '5 days', 'approver' => 'Department Head', 'requests' => 12, 'avg_time' => '3 days'],
    ['id' => 'SVC-004', 'category' => 'Software', 'service' => 'Software Installation', 'sla' => '48 hours', 'approver' => 'IT Security', 'requests' => 67, 'avg_time' => '1 day'],
    ['id' => 'SVC-005', 'category' => 'Access Management', 'service' => 'VPN Access', 'sla' => '4 hours', 'approver' => 'Security Team', 'requests' => 89, 'avg_time' => '2 hours'],
    ['id' => 'SVC-006', 'category' => 'Email', 'service' => 'Distribution List Creation', 'sla' => '8 hours', 'approver' => 'IT Manager', 'requests' => 23, 'avg_time' => '6 hours'],
    ['id' => 'SVC-007', 'category' => 'Hardware', 'service' => 'Monitor/Peripherals', 'sla' => '48 hours', 'approver' => 'IT Support', 'requests' => 34, 'avg_time' => '1 day'],
    ['id' => 'SVC-008', 'category' => 'Network', 'service' => 'Network Port Activation', 'sla' => '24 hours', 'approver' => 'Network Team', 'requests' => 18, 'avg_time' => '8 hours'],
];

// ========== WORKFLOW AUTOMATIONS ==========
$workflows = [
    ['id' => 1, 'name' => 'Critical Incident Escalation', 'trigger' => 'Priority = Critical AND Status = New', 'steps' => '1. Notify Manager 2. Assign Senior Agent 3. Create War Room', 'executions' => 45, 'success_rate' => 98],
    ['id' => 2, 'name' => 'SLA Breach Warning', 'trigger' => 'SLA Remaining < 30 min', 'steps' => '1. Send Alert to Agent 2. Notify Supervisor 3. Add to Dashboard', 'executions' => 123, 'success_rate' => 100],
    ['id' => 3, 'name' => 'Auto-Close Resolved Tickets', 'trigger' => 'Status = Resolved AND No Activity > 48h', 'steps' => '1. Send Satisfaction Survey 2. Auto-Close Ticket 3. Archive', 'executions' => 567, 'success_rate' => 95],
    ['id' => 4, 'name' => 'Password Reset Auto-Fulfill', 'trigger' => 'Service = Password Reset', 'steps' => '1. Verify Identity 2. Reset Password 3. Send Email 4. Close Ticket', 'executions' => 892, 'success_rate' => 99],
    ['id' => 5, 'name' => 'VIP Customer Notification', 'trigger' => 'Customer Type = VIP', 'steps' => '1. Notify Account Manager 2. Set High Priority 3. Assign Best Agent', 'executions' => 78, 'success_rate' => 100],
];

// ========== CUSTOM FIELDS ==========
$custom_fields = [
    ['id' => 1, 'field_name' => 'Affected Department', 'field_type' => 'Dropdown', 'options' => 'IT, HR, Finance, Sales, Operations', 'required' => true, 'usage' => 1245],
    ['id' => 2, 'field_name' => 'Business Impact', 'field_type' => 'Radio', 'options' => 'Critical, High, Medium, Low', 'required' => true, 'usage' => 1245],
    ['id' => 3, 'field_name' => 'Number of Users Affected', 'field_type' => 'Number', 'options' => 'Min: 1, Max: 10000', 'required' => false, 'usage' => 892],
    ['id' => 4, 'field_name' => 'Error Message', 'field_type' => 'Text Area', 'options' => 'Max Length: 1000', 'required' => false, 'usage' => 678],
    ['id' => 5, 'field_name' => 'Preferred Contact Method', 'field_type' => 'Checkbox', 'options' => 'Email, Phone, Chat, SMS', 'required' => false, 'usage' => 1123],
    ['id' => 6, 'field_name' => 'Asset Tag Number', 'field_type' => 'Text', 'options' => 'Pattern: AST-[0-9]{5}', 'required' => false, 'usage' => 456],
];

// ========== MONITORING INTEGRATION ==========
$monitoring_alerts = [
    ['id' => 'MON-001', 'source' => 'Nagios', 'alert_type' => 'CPU High', 'host' => 'web-server-01', 'severity' => 'Warning', 'auto_ticket' => 'TKT-009', 'timestamp' => '2024-01-15 11:45:00', 'status' => 'Active'],
    ['id' => 'MON-002', 'source' => 'Zabbix', 'alert_type' => 'Disk Space Low', 'host' => 'db-server-02', 'severity' => 'Critical', 'auto_ticket' => 'TKT-010', 'timestamp' => '2024-01-15 11:50:00', 'status' => 'Active'],
    ['id' => 'MON-003', 'source' => 'PRTG', 'alert_type' => 'Network Bandwidth High', 'host' => 'router-01', 'severity' => 'Warning', 'auto_ticket' => 'TKT-011', 'timestamp' => '2024-01-15 12:00:00', 'status' => 'Active'],
    ['id' => 'MON-004', 'source' => 'SolarWinds', 'alert_type' => 'Service Down', 'host' => 'mail-server-01', 'severity' => 'Critical', 'auto_ticket' => 'TKT-012', 'timestamp' => '2024-01-15 12:10:00', 'status' => 'Resolved'],
    ['id' => 'MON-005', 'source' => 'Prometheus', 'alert_type' => 'Memory Usage High', 'host' => 'app-server-03', 'severity' => 'Warning', 'auto_ticket' => null, 'timestamp' => '2024-01-15 12:15:00', 'status' => 'Active'],
];

// ========== CUSTOMER SATISFACTION ==========
$csat_surveys = [
    ['ticket_id' => 'TKT-002', 'rating' => 5, 'feedback' => 'Quick resolution, excellent service!', 'agent' => 'Sarah Lee', 'submitted' => '2024-01-15 10:30:00'],
    ['ticket_id' => 'TKT-006', 'rating' => 4, 'feedback' => 'Good service but took longer than expected', 'agent' => 'David Lee', 'submitted' => '2024-01-14 16:00:00'],
    ['ticket_id' => 'TKT-007', 'rating' => 5, 'feedback' => 'Very professional and fast!', 'agent' => 'John Smith', 'submitted' => '2024-01-14 11:30:00'],
    ['ticket_id' => 'TKT-015', 'rating' => 3, 'feedback' => 'Issue resolved but communication could be better', 'agent' => 'Mike Davis', 'submitted' => '2024-01-13 15:00:00'],
    ['ticket_id' => 'TKT-018', 'rating' => 5, 'feedback' => 'Outstanding support!', 'agent' => 'Sarah Lee', 'submitted' => '2024-01-13 09:00:00'],
    ['ticket_id' => 'TKT-022', 'rating' => 2, 'feedback' => 'Resolution took too long, multiple follow-ups needed', 'agent' => 'Emily Chen', 'submitted' => '2024-01-12 14:00:00'],
];

// ========== AGENT PRODUCTIVITY ==========
$agent_stats = [
    ['agent' => 'John Smith', 'tickets_resolved' => 45, 'avg_resolution_time' => 3.2, 'csat_score' => 4.8, 'sla_compliance' => 98, 'active_tickets' => 5],
    ['agent' => 'Sarah Lee', 'tickets_resolved' => 52, 'avg_resolution_time' => 2.8, 'csat_score' => 4.9, 'sla_compliance' => 99, 'active_tickets' => 3],
    ['agent' => 'Mike Davis', 'tickets_resolved' => 38, 'avg_resolution_time' => 4.1, 'csat_score' => 4.2, 'sla_compliance' => 95, 'active_tickets' => 7],
    ['agent' => 'Emily Chen', 'tickets_resolved' => 41, 'avg_resolution_time' => 3.5, 'csat_score' => 4.5, 'sla_compliance' => 97, 'active_tickets' => 4],
    ['agent' => 'David Lee', 'tickets_resolved' => 36, 'avg_resolution_time' => 3.9, 'csat_score' => 4.6, 'sla_compliance' => 96, 'active_tickets' => 6],
];

// Calculate statistics
$total_tickets = count($tickets);
$open_tickets = count(array_filter($tickets, fn($t) => $t['status'] != 'Resolved'));
$avg_csat = array_sum(array_column($csat_surveys, 'rating')) / count($csat_surveys);
$sla_breaches = count(array_filter($tickets, fn($t) => $t['sla_remaining'] < 0));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enhanced ITSM Platform - IOC</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        .header {
            text-align: center;
            color: white;
            margin-bottom: 30px;
        }
        .header h1 {
            font-size: 42px;
            margin-bottom: 10px;
            text-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        .header p { font-size: 18px; opacity: 0.95; }

        .stats-bar {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-box {
            background: white;
            padding: 25px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        .stat-number {
            font-size: 36px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }
        .stat-label {
            font-size: 14px;
            color: #666;
            text-transform: uppercase;
        }

        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .tab {
            background: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            color: #667eea;
            transition: all 0.3s;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .tab:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        .tab.active {
            background: #667eea;
            color: white;
        }

        .tab-content {
            display: none;
            animation: fadeIn 0.3s;
        }
        .tab-content.active {
            display: block;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        .card h2 {
            color: #667eea;
            font-size: 24px;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid #e0e0e0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th {
            background: #f5f5f5;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #667eea;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #e0e0e0;
        }
        tr:hover {
            background: #f9f9f9;
        }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-critical { background: #ffebee; color: #c62828; }
        .badge-high { background: #fff3e0; color: #e65100; }
        .badge-medium { background: #e3f2fd; color: #1565c0; }
        .badge-low { background: #e8f5e9; color: #2e7d32; }
        .badge-new { background: #f3e5f5; color: #6a1b9a; }
        .badge-assigned { background: #e1f5fe; color: #01579b; }
        .badge-progress { background: #fff9c4; color: #f57f17; }
        .badge-resolved { background: #e8f5e9; color: #2e7d32; }
        .badge-hold { background: #fce4ec; color: #880e4f; }
        .badge-active { background: #e8f5e9; color: #2e7d32; }
        .badge-warning { background: #fff3e0; color: #e65100; }

        .channel-icon {
            display: inline-block;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            text-align: center;
            line-height: 30px;
            margin-right: 8px;
        }
        .channel-email { background: #e3f2fd; }
        .channel-web { background: #e8f5e9; }
        .channel-phone { background: #fff3e0; }
        .channel-chat { background: #f3e5f5; }

        .sla-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
        }
        .sla-ok { background: #4CAF50; }
        .sla-warning { background: #ff9800; }
        .sla-breach { background: #f44336; }

        .progress-bar {
            background: #e0e0e0;
            height: 20px;
            border-radius: 10px;
            overflow: hidden;
            position: relative;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea, #764ba2);
            transition: width 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 12px;
            font-weight: 600;
        }

        .rating-stars {
            color: #ffd700;
            font-size: 18px;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .grid-3 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        .chart-container {
            position: relative;
            height: 300px;
            margin-top: 20px;
        }

        .service-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 10px;
            border-left: 4px solid #667eea;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .service-item:hover {
            transform: translateX(5px);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .service-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        .service-meta {
            font-size: 13px;
            color: #666;
        }

        .btn {
            display: inline-block;
            padding: 14px 28px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .btn-primary {
            background: white;
            color: #667eea;
        }
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.25);
        }

        .workflow-step {
            background: #f5f5f5;
            padding: 10px 15px;
            border-radius: 5px;
            margin: 5px 0;
            font-size: 13px;
        }

        .mobile-preview {
            max-width: 375px;
            margin: 0 auto;
            background: white;
            border-radius: 30px;
            padding: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        }

        .btn {
            padding: 10px 20px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #5568d3;
        }

        @media (max-width: 768px) {
            .grid-2, .grid-3 { grid-template-columns: 1fr; }
            .tabs { flex-direction: column; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🎫 Enhanced ITSM Platform</h1>
        <p>Comprehensive IT Service Management with 10 Advanced Features</p>
    </div>

    <!-- Quick Actions -->
    <div style="display: flex; gap: 15px; justify-content: center; margin-bottom: 30px; flex-wrap: wrap;">
        <a href="log_incident.php" class="btn btn-primary" style="text-decoration: none;">🎫 Log Incident</a>
        <a href="log_problem.php" class="btn btn-primary" style="text-decoration: none;">🔧 Log Problem</a>
        <a href="log_change.php" class="btn btn-primary" style="text-decoration: none;">📋 Log Change Request</a>
    </div>

    <!-- Statistics Dashboard -->
    <div class="stats-bar">
        <div class="stat-box">
            <div class="stat-number"><?= $total_tickets ?></div>
            <div class="stat-label">Total Tickets</div>
        </div>
        <div class="stat-box">
            <div class="stat-number"><?= $open_tickets ?></div>
            <div class="stat-label">Open Tickets</div>
        </div>
        <div class="stat-box">
            <div class="stat-number"><?= number_format($avg_csat, 1) ?>/5</div>
            <div class="stat-label">Avg CSAT Score</div>
        </div>
        <div class="stat-box">
            <div class="stat-number"><?= count($agent_stats) ?></div>
            <div class="stat-label">Active Agents</div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="tabs">
        <button class="tab active" onclick="switchTab('multichannel')">📬 Multi-Channel</button>
        <button class="tab" onclick="switchTab('sla')">⏱️ SLA Management</button>
        <button class="tab" onclick="switchTab('routing')">🔄 Auto Routing</button>
        <button class="tab" onclick="switchTab('catalog')">📋 Service Catalog</button>
        <button class="tab" onclick="switchTab('workflow')">⚙️ Workflows</button>
        <button class="tab" onclick="switchTab('fields')">📝 Custom Fields</button>
        <button class="tab" onclick="switchTab('monitoring')">📡 Monitoring</button>
        <button class="tab" onclick="switchTab('satisfaction')">⭐ CSAT Surveys</button>
        <button class="tab" onclick="switchTab('productivity')">📊 Agent Productivity</button>
        <button class="tab" onclick="switchTab('mobile')">📱 Mobile App</button>
    </div>

    <!-- Tab 1: Multi-Channel Support -->
    <div id="multichannel" class="tab-content active">
        <div class="card">
            <h2>📬 Multi-Channel Ticket Management</h2>
            <p style="color: #666; margin-bottom: 20px;">Unified inbox for Email, Web Portal, Phone, and Chat channels</p>

            <div class="grid-2">
                <div class="chart-container">
                    <canvas id="channelChart"></canvas>
                </div>
                <div class="chart-container">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Ticket ID</th>
                        <th>Channel</th>
                        <th>Title</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Agent</th>
                        <th>SLA</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tickets as $ticket): ?>
                    <tr>
                        <td><strong><?= $ticket['id'] ?></strong></td>
                        <td>
                            <span class="channel-icon channel-<?= strtolower($ticket['channel']) ?>">
                                <?php
                                    $icons = ['Email' => '📧', 'Web Portal' => '🌐', 'Phone' => '📞', 'Chat' => '💬'];
                                    echo $icons[$ticket['channel']];
                                ?>
                            </span>
                            <?= $ticket['channel'] ?>
                        </td>
                        <td><?= $ticket['title'] ?></td>
                        <td><span class="badge badge-<?= strtolower($ticket['priority']) ?>"><?= $ticket['priority'] ?></span></td>
                        <td><span class="badge badge-<?= str_replace(' ', '', strtolower($ticket['status'])) ?>"><?= $ticket['status'] ?></span></td>
                        <td><?= $ticket['agent'] ?></td>
                        <td>
                            <span class="sla-indicator <?= $ticket['sla_remaining'] > 60 ? 'sla-ok' : ($ticket['sla_remaining'] > 0 ? 'sla-warning' : 'sla-breach') ?>"></span>
                            <?= $ticket['sla_remaining'] > 0 ? $ticket['sla_remaining'] . ' min' : 'Breached' ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Tab 2: SLA Management -->
    <div id="sla" class="tab-content">
        <div class="card">
            <h2>⏱️ SLA Management & Tracking</h2>
            <p style="color: #666; margin-bottom: 20px;">Service Level Agreement definitions and compliance tracking</p>

            <div class="grid-2">
                <div>
                    <h3 style="color: #333; margin-bottom: 15px;">SLA Definitions</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Priority</th>
                                <th>Response Time</th>
                                <th>Resolution Time</th>
                                <th>Compliance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sla_definitions as $sla): ?>
                            <tr>
                                <td><span class="badge badge-<?= strtolower($sla['priority']) ?>"><?= $sla['priority'] ?></span></td>
                                <td><?= $sla['response_time'] ?> minutes</td>
                                <td><?= $sla['resolution_time'] ?> minutes</td>
                                <td>
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?= rand(90, 99) ?>%"><?= rand(90, 99) ?>%</div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="chart-container">
                    <canvas id="slaComplianceChart"></canvas>
                </div>
            </div>
        </div>

        <div class="card">
            <h2>🚨 SLA Breach Alerts</h2>
            <div class="grid-3">
                <div style="background: #ffebee; padding: 20px; border-radius: 8px; border-left: 4px solid #f44336;">
                    <div style="font-size: 32px; font-weight: bold; color: #c62828;">3</div>
                    <div style="color: #666; margin-top: 5px;">Critical - At Risk</div>
                </div>
                <div style="background: #fff3e0; padding: 20px; border-radius: 8px; border-left: 4px solid #ff9800;">
                    <div style="font-size: 32px; font-weight: bold; color: #e65100;">7</div>
                    <div style="color: #666; margin-top: 5px;">Warning - < 30 min</div>
                </div>
                <div style="background: #e8f5e9; padding: 20px; border-radius: 8px; border-left: 4px solid #4CAF50;">
                    <div style="font-size: 32px; font-weight: bold; color: #2e7d32;">98%</div>
                    <div style="color: #666; margin-top: 5px;">Overall Compliance</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab 3: Automated Routing -->
    <div id="routing" class="tab-content">
        <div class="card">
            <h2>🔄 Automated Ticket Routing & Assignment</h2>
            <p style="color: #666; margin-bottom: 20px;">Intelligent routing rules based on conditions and keywords</p>

            <div style="text-align: right; margin-bottom: 15px;">
                <button class="btn">➕ Create New Rule</button>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Rule Name</th>
                        <th>Condition</th>
                        <th>Action</th>
                        <th>Status</th>
                        <th>Tickets Matched</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($routing_rules as $rule): ?>
                    <tr>
                        <td><strong><?= $rule['name'] ?></strong></td>
                        <td><code style="background: #f5f5f5; padding: 4px 8px; border-radius: 4px;"><?= $rule['condition'] ?></code></td>
                        <td><?= $rule['action'] ?></td>
                        <td><span class="badge badge-active"><?= $rule['status'] ?></span></td>
                        <td><strong><?= $rule['matched'] ?></strong> tickets</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="card">
            <h2>📈 Routing Performance</h2>
            <div class="chart-container">
                <canvas id="routingChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Tab 4: Service Catalog -->
    <div id="catalog" class="tab-content">
        <div class="card">
            <h2>📋 Service Catalog & Request Fulfillment</h2>
            <p style="color: #666; margin-bottom: 20px;">Self-service portal for common IT requests</p>

            <div class="grid-2">
                <div>
                    <?php
                    $categories = array_unique(array_column($service_catalog, 'category'));
                    foreach ($categories as $category):
                        $services = array_filter($service_catalog, fn($s) => $s['category'] === $category);
                    ?>
                    <h3 style="color: #667eea; margin: 20px 0 10px 0;"><?= $category ?></h3>
                    <?php foreach ($services as $service): ?>
                    <div class="service-item">
                        <div class="service-title"><?= $service['service'] ?></div>
                        <div class="service-meta">
                            SLA: <?= $service['sla'] ?> | Approver: <?= $service['approver'] ?> |
                            <?= $service['requests'] ?> requests | Avg: <?= $service['avg_time'] ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endforeach; ?>
                </div>

                <div class="chart-container">
                    <canvas id="catalogChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab 5: Workflow Automation -->
    <div id="workflow" class="tab-content">
        <div class="card">
            <h2>⚙️ Workflow Automation & Escalation</h2>
            <p style="color: #666; margin-bottom: 20px;">Automated workflows for common scenarios</p>

            <div style="text-align: right; margin-bottom: 15px;">
                <button class="btn">➕ Create New Workflow</button>
            </div>

            <?php foreach ($workflows as $workflow): ?>
            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 15px; border-left: 4px solid #667eea;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                    <h3 style="color: #333; margin: 0;"><?= $workflow['name'] ?></h3>
                    <span class="badge badge-active">Active</span>
                </div>
                <div style="color: #666; margin-bottom: 10px;">
                    <strong>Trigger:</strong> <code style="background: white; padding: 4px 8px; border-radius: 4px;"><?= $workflow['trigger'] ?></code>
                </div>
                <div style="color: #666; margin-bottom: 10px;">
                    <strong>Automation Steps:</strong>
                </div>
                <?php
                $steps = explode(' ', $workflow['steps']);
                foreach ($steps as $step):
                    if (trim($step)):
                ?>
                <div class="workflow-step"><?= trim($step) ?></div>
                <?php
                    endif;
                endforeach;
                ?>
                <div style="margin-top: 15px; display: flex; gap: 20px;">
                    <div><strong><?= $workflow['executions'] ?></strong> executions</div>
                    <div><strong><?= $workflow['success_rate'] ?>%</strong> success rate</div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Tab 6: Custom Fields -->
    <div id="fields" class="tab-content">
        <div class="card">
            <h2>📝 Custom Forms & Ticket Fields</h2>
            <p style="color: #666; margin-bottom: 20px;">Configurable fields for capturing specific information</p>

            <div style="text-align: right; margin-bottom: 15px;">
                <button class="btn">➕ Add Custom Field</button>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Field Name</th>
                        <th>Field Type</th>
                        <th>Options/Validation</th>
                        <th>Required</th>
                        <th>Usage Count</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($custom_fields as $field): ?>
                    <tr>
                        <td><strong><?= $field['field_name'] ?></strong></td>
                        <td><span class="badge badge-medium"><?= $field['field_type'] ?></span></td>
                        <td><small><?= $field['options'] ?></small></td>
                        <td><?= $field['required'] ? '✅ Yes' : '⭕ No' ?></td>
                        <td><?= $field['usage'] ?> tickets</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="card">
            <h2>📊 Field Usage Analytics</h2>
            <div class="chart-container">
                <canvas id="fieldsChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Tab 7: Monitoring Integration -->
    <div id="monitoring" class="tab-content">
        <div class="card">
            <h2>📡 Monitoring Tools Integration</h2>
            <p style="color: #666; margin-bottom: 20px;">Auto-create tickets from monitoring alerts (Nagios, Zabbix, PRTG, SolarWinds, Prometheus)</p>

            <div class="grid-3" style="margin-bottom: 20px;">
                <div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                    <div style="font-size: 28px; margin-bottom: 10px;">🔴</div>
                    <div style="font-size: 24px; font-weight: bold; color: #f44336;">2</div>
                    <div style="color: #666;">Critical Alerts</div>
                </div>
                <div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                    <div style="font-size: 28px; margin-bottom: 10px;">🟡</div>
                    <div style="font-size: 24px; font-weight: bold; color: #ff9800;">3</div>
                    <div style="color: #666;">Warning Alerts</div>
                </div>
                <div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                    <div style="font-size: 28px; margin-bottom: 10px;">🎫</div>
                    <div style="font-size: 24px; font-weight: bold; color: #667eea;">4</div>
                    <div style="color: #666;">Auto-Created Tickets</div>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Alert ID</th>
                        <th>Source System</th>
                        <th>Alert Type</th>
                        <th>Host/Device</th>
                        <th>Severity</th>
                        <th>Auto Ticket</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($monitoring_alerts as $alert): ?>
                    <tr>
                        <td><strong><?= $alert['id'] ?></strong></td>
                        <td><?= $alert['source'] ?></td>
                        <td><?= $alert['alert_type'] ?></td>
                        <td><code><?= $alert['host'] ?></code></td>
                        <td><span class="badge badge-<?= strtolower($alert['severity']) ?>"><?= $alert['severity'] ?></span></td>
                        <td><?= $alert['auto_ticket'] ? '<a href="#" style="color: #667eea; font-weight: 600;">' . $alert['auto_ticket'] . '</a>' : 'N/A' ?></td>
                        <td><span class="badge badge-<?= strtolower($alert['status']) ?>"><?= $alert['status'] ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="card">
            <h2>🔗 Connected Monitoring Systems</h2>
            <div class="grid-3">
                <?php
                $systems = ['Nagios', 'Zabbix', 'PRTG', 'SolarWinds', 'Prometheus', 'Datadog'];
                foreach ($systems as $system):
                ?>
                <div style="background: #e8f5e9; padding: 15px; border-radius: 8px; text-align: center;">
                    <div style="font-size: 20px; font-weight: 600; color: #2e7d32;"><?= $system ?></div>
                    <div style="color: #666; font-size: 12px; margin-top: 5px;">✅ Connected</div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Tab 8: Customer Satisfaction -->
    <div id="satisfaction" class="tab-content">
        <div class="card">
            <h2>⭐ Customer Satisfaction Surveys</h2>
            <p style="color: #666; margin-bottom: 20px;">Automated CSAT surveys sent after ticket resolution</p>

            <div class="grid-3" style="margin-bottom: 20px;">
                <div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                    <div style="font-size: 36px; font-weight: bold; color: #667eea;"><?= number_format($avg_csat, 1) ?>/5</div>
                    <div style="color: #666; margin-top: 5px;">Average CSAT Score</div>
                </div>
                <div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                    <div style="font-size: 36px; font-weight: bold; color: #4CAF50;"><?= count($csat_surveys) ?></div>
                    <div style="color: #666; margin-top: 5px;">Responses Received</div>
                </div>
                <div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                    <div style="font-size: 36px; font-weight: bold; color: #ff9800;">68%</div>
                    <div style="color: #666; margin-top: 5px;">Response Rate</div>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Ticket ID</th>
                        <th>Rating</th>
                        <th>Feedback</th>
                        <th>Agent</th>
                        <th>Submitted</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($csat_surveys as $survey): ?>
                    <tr>
                        <td><strong><?= $survey['ticket_id'] ?></strong></td>
                        <td>
                            <span class="rating-stars">
                                <?= str_repeat('⭐', $survey['rating']) ?>
                                <?= str_repeat('☆', 5 - $survey['rating']) ?>
                            </span>
                        </td>
                        <td><?= $survey['feedback'] ?></td>
                        <td><?= $survey['agent'] ?></td>
                        <td><?= date('M d, H:i', strtotime($survey['submitted'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="card">
            <h2>📊 CSAT Trend Analysis</h2>
            <div class="chart-container">
                <canvas id="csatChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Tab 9: Agent Productivity -->
    <div id="productivity" class="tab-content">
        <div class="card">
            <h2>📊 Agent Productivity Tracking</h2>
            <p style="color: #666; margin-bottom: 20px;">Comprehensive metrics for agent performance and efficiency</p>

            <table>
                <thead>
                    <tr>
                        <th>Agent Name</th>
                        <th>Tickets Resolved</th>
                        <th>Avg Resolution Time</th>
                        <th>CSAT Score</th>
                        <th>SLA Compliance</th>
                        <th>Active Tickets</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($agent_stats as $agent): ?>
                    <tr>
                        <td><strong><?= $agent['agent'] ?></strong></td>
                        <td><?= $agent['tickets_resolved'] ?></td>
                        <td><?= $agent['avg_resolution_time'] ?> hours</td>
                        <td>
                            <span class="rating-stars">
                                <?= str_repeat('⭐', floor($agent['csat_score'])) ?>
                            </span>
                            <?= number_format($agent['csat_score'], 1) ?>
                        </td>
                        <td>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?= $agent['sla_compliance'] ?>%"><?= $agent['sla_compliance'] ?>%</div>
                            </div>
                        </td>
                        <td><?= $agent['active_tickets'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="grid-2">
            <div class="card">
                <h2>🏆 Top Performers</h2>
                <div class="chart-container">
                    <canvas id="topAgentsChart"></canvas>
                </div>
            </div>

            <div class="card">
                <h2>⏱️ Resolution Time Comparison</h2>
                <div class="chart-container">
                    <canvas id="resolutionChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab 10: Mobile App -->
    <div id="mobile" class="tab-content">
        <div class="card">
            <h2>📱 Mobile App for Technicians</h2>
            <p style="color: #666; margin-bottom: 20px;">On-the-go ticket management for field technicians</p>

            <div class="grid-2">
                <div>
                    <h3 style="color: #667eea; margin-bottom: 15px;">📱 Mobile Features</h3>
                    <ul style="list-style: none; padding: 0;">
                        <li style="padding: 12px; background: #f8f9fa; margin-bottom: 10px; border-radius: 6px;">
                            ✅ <strong>Real-time Ticket Updates</strong> - Push notifications for new assignments
                        </li>
                        <li style="padding: 12px; background: #f8f9fa; margin-bottom: 10px; border-radius: 6px;">
                            ✅ <strong>Offline Mode</strong> - Work without internet, sync when connected
                        </li>
                        <li style="padding: 12px; background: #f8f9fa; margin-bottom: 10px; border-radius: 6px;">
                            ✅ <strong>GPS Check-in</strong> - Automatic location tracking for on-site visits
                        </li>
                        <li style="padding: 12px; background: #f8f9fa; margin-bottom: 10px; border-radius: 6px;">
                            ✅ <strong>Photo Attachments</strong> - Capture and upload photos directly
                        </li>
                        <li style="padding: 12px; background: #f8f9fa; margin-bottom: 10px; border-radius: 6px;">
                            ✅ <strong>Voice Notes</strong> - Add voice memos to tickets
                        </li>
                        <li style="padding: 12px; background: #f8f9fa; margin-bottom: 10px; border-radius: 6px;">
                            ✅ <strong>Barcode Scanner</strong> - Scan asset tags for quick lookup
                        </li>
                        <li style="padding: 12px; background: #f8f9fa; margin-bottom: 10px; border-radius: 6px;">
                            ✅ <strong>Time Tracking</strong> - Log hours spent on tickets
                        </li>
                        <li style="padding: 12px; background: #f8f9fa; margin-bottom: 10px; border-radius: 6px;">
                            ✅ <strong>Knowledge Base Access</strong> - Search solutions on the go
                        </li>
                    </ul>
                </div>

                <div>
                    <h3 style="color: #667eea; margin-bottom: 15px; text-align: center;">📱 Mobile App Preview</h3>
                    <div class="mobile-preview">
                        <div style="text-align: center; color: #667eea; font-weight: 600; padding-bottom: 15px; border-bottom: 1px solid #e0e0e0; margin-bottom: 15px;">
                            🎫 ITSM Mobile
                        </div>

                        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 10px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                <strong>TKT-001</strong>
                                <span class="badge badge-critical">Critical</span>
                            </div>
                            <div style="color: #333; margin-bottom: 5px;">Email server down</div>
                            <div style="font-size: 12px; color: #666;">⏱️ 45 min remaining</div>
                        </div>

                        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 10px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                <strong>TKT-003</strong>
                                <span class="badge badge-high">High</span>
                            </div>
                            <div style="color: #333; margin-bottom: 5px;">VPN connection issues</div>
                            <div style="font-size: 12px; color: #666;">⏱️ 120 min remaining</div>
                        </div>

                        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                <strong>TKT-008</strong>
                                <span class="badge badge-medium">Medium</span>
                            </div>
                            <div style="color: #333; margin-bottom: 5px;">Access permission request</div>
                            <div style="font-size: 12px; color: #666;">⏱️ 200 min remaining</div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <button style="padding: 12px; background: #667eea; color: white; border: none; border-radius: 6px; font-weight: 600;">📸 Scan Asset</button>
                            <button style="padding: 12px; background: #4CAF50; color: white; border: none; border-radius: 6px; font-weight: 600;">📍 Check In</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <h2>📊 Mobile App Usage Statistics</h2>
            <div class="grid-3">
                <div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                    <div style="font-size: 32px; font-weight: bold; color: #667eea;">127</div>
                    <div style="color: #666; margin-top: 5px;">Active Mobile Users</div>
                </div>
                <div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                    <div style="font-size: 32px; font-weight: bold; color: #4CAF50;">89%</div>
                    <div style="color: #666; margin-top: 5px;">Adoption Rate</div>
                </div>
                <div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                    <div style="font-size: 32px; font-weight: bold; color: #ff9800;">342</div>
                    <div style="color: #666; margin-top: 5px;">Tickets via Mobile (Today)</div>
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

        // Chart.js configurations
        const chartColors = ['#667eea', '#764ba2', '#f093fb', '#4facfe', '#43e97b', '#fa709a', '#fee140'];

        // Channel Distribution Chart
        const channelData = <?= json_encode(array_count_values(array_column($tickets, 'channel'))) ?>;
        new Chart(document.getElementById('channelChart'), {
            type: 'doughnut',
            data: {
                labels: Object.keys(channelData),
                datasets: [{
                    data: Object.values(channelData),
                    backgroundColor: chartColors
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' },
                    title: { display: true, text: 'Tickets by Channel' }
                }
            }
        });

        // Status Distribution Chart
        const statusData = <?= json_encode(array_count_values(array_column($tickets, 'status'))) ?>;
        new Chart(document.getElementById('statusChart'), {
            type: 'pie',
            data: {
                labels: Object.keys(statusData),
                datasets: [{
                    data: Object.values(statusData),
                    backgroundColor: chartColors
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' },
                    title: { display: true, text: 'Tickets by Status' }
                }
            }
        });

        // SLA Compliance Chart
        new Chart(document.getElementById('slaComplianceChart'), {
            type: 'bar',
            data: {
                labels: ['Critical', 'High', 'Medium', 'Low'],
                datasets: [{
                    label: 'SLA Compliance %',
                    data: [96, 98, 99, 100],
                    backgroundColor: '#667eea'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, max: 100 }
                },
                plugins: {
                    title: { display: true, text: 'SLA Compliance by Priority' }
                }
            }
        });

        // Routing Performance Chart
        new Chart(document.getElementById('routingChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($routing_rules, 'name')) ?>,
                datasets: [{
                    label: 'Tickets Matched',
                    data: <?= json_encode(array_column($routing_rules, 'matched')) ?>,
                    backgroundColor: '#667eea'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    title: { display: true, text: 'Routing Rules Performance' }
                }
            }
        });

        // Service Catalog Chart
        new Chart(document.getElementById('catalogChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column(array_slice($service_catalog, 0, 5), 'service')) ?>,
                datasets: [{
                    label: 'Requests',
                    data: <?= json_encode(array_column(array_slice($service_catalog, 0, 5), 'requests')) ?>,
                    backgroundColor: '#667eea'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: { display: true, text: 'Top 5 Requested Services' }
                }
            }
        });

        // Custom Fields Usage Chart
        new Chart(document.getElementById('fieldsChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($custom_fields, 'field_name')) ?>,
                datasets: [{
                    label: 'Usage Count',
                    data: <?= json_encode(array_column($custom_fields, 'usage')) ?>,
                    backgroundColor: '#667eea'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    title: { display: true, text: 'Custom Field Usage' }
                }
            }
        });

        // CSAT Trend Chart
        new Chart(document.getElementById('csatChart'), {
            type: 'line',
            data: {
                labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
                datasets: [{
                    label: 'Average CSAT Score',
                    data: [4.2, 4.5, 4.6, 4.4],
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, max: 5 }
                },
                plugins: {
                    title: { display: true, text: 'CSAT Score Trend (Last 4 Weeks)' }
                }
            }
        });

        // Top Agents Chart
        new Chart(document.getElementById('topAgentsChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($agent_stats, 'agent')) ?>,
                datasets: [{
                    label: 'Tickets Resolved',
                    data: <?= json_encode(array_column($agent_stats, 'tickets_resolved')) ?>,
                    backgroundColor: '#667eea'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: { display: true, text: 'Tickets Resolved by Agent' }
                }
            }
        });

        // Resolution Time Chart
        new Chart(document.getElementById('resolutionChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($agent_stats, 'agent')) ?>,
                datasets: [{
                    label: 'Avg Resolution Time (hours)',
                    data: <?= json_encode(array_column($agent_stats, 'avg_resolution_time')) ?>,
                    backgroundColor: '#764ba2'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: { display: true, text: 'Average Resolution Time by Agent' }
                }
            }
        });
    </script>
</body>
</html>
