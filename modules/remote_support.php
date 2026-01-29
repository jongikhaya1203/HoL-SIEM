<?php
/**
 * Remote Support Module
 * Comprehensive remote assistance and device management
 */
require_once __DIR__ . '/../classes/Database.php';
$db = Database::getInstance();

// Sample data for remote sessions
$activeSessions = [
    ['id' => 1, 'device' => 'WKS-ADMIN-01', 'ip' => '192.168.1.101', 'user' => 'John Smith', 'technician' => 'Tech Support', 'type' => 'remote_desktop', 'status' => 'active', 'started' => '2025-01-26 09:15:00', 'duration' => 45],
    ['id' => 2, 'device' => 'SRV-WEB-02', 'ip' => '192.168.1.50', 'user' => 'System', 'technician' => 'Admin Team', 'type' => 'command_line', 'status' => 'active', 'started' => '2025-01-26 10:30:00', 'duration' => 12],
    ['id' => 3, 'device' => 'WKS-SALES-05', 'ip' => '192.168.1.115', 'user' => 'Jane Doe', 'technician' => 'Help Desk', 'type' => 'file_transfer', 'status' => 'active', 'started' => '2025-01-26 10:45:00', 'duration' => 5],
];

$pendingRequests = [
    ['id' => 101, 'device' => 'WKS-HR-03', 'ip' => '192.168.1.130', 'user' => 'Mike Johnson', 'issue' => 'Cannot access email', 'priority' => 'high', 'requested' => '2025-01-26 10:50:00'],
    ['id' => 102, 'device' => 'WKS-FIN-02', 'ip' => '192.168.1.142', 'user' => 'Sarah Wilson', 'issue' => 'Printer not working', 'priority' => 'medium', 'requested' => '2025-01-26 10:48:00'],
    ['id' => 103, 'device' => 'LAPTOP-EXEC-01', 'ip' => '192.168.1.200', 'user' => 'CEO Office', 'issue' => 'Software installation needed', 'priority' => 'critical', 'requested' => '2025-01-26 10:52:00'],
];

$recentHistory = [
    ['id' => 1001, 'device' => 'WKS-DEV-04', 'ip' => '192.168.1.88', 'technician' => 'Tech Support', 'type' => 'remote_desktop', 'started' => '2025-01-26 08:00:00', 'ended' => '2025-01-26 08:45:00', 'status' => 'completed', 'notes' => 'Resolved driver issue'],
    ['id' => 1002, 'device' => 'SRV-DB-01', 'ip' => '192.168.1.10', 'technician' => 'Admin Team', 'type' => 'command_line', 'started' => '2025-01-26 07:30:00', 'ended' => '2025-01-26 07:35:00', 'status' => 'completed', 'notes' => 'Database backup verified'],
    ['id' => 1003, 'device' => 'WKS-MKT-01', 'ip' => '192.168.1.155', 'technician' => 'Help Desk', 'type' => 'file_transfer', 'started' => '2025-01-25 16:00:00', 'ended' => '2025-01-25 16:10:00', 'status' => 'completed', 'notes' => 'Deployed marketing assets'],
    ['id' => 1004, 'device' => 'WKS-SALES-02', 'ip' => '192.168.1.112', 'technician' => 'Tech Support', 'type' => 'remote_desktop', 'started' => '2025-01-25 14:20:00', 'ended' => '2025-01-25 14:55:00', 'status' => 'completed', 'notes' => 'CRM software troubleshooting'],
    ['id' => 1005, 'device' => 'WKS-ACC-03', 'ip' => '192.168.1.178', 'technician' => 'Help Desk', 'type' => 'chat_support', 'started' => '2025-01-25 11:00:00', 'ended' => '2025-01-25 11:15:00', 'status' => 'completed', 'notes' => 'Password reset assistance'],
];

$managedDevices = [
    ['id' => 1, 'name' => 'WKS-ADMIN-01', 'ip' => '192.168.1.101', 'type' => 'workstation', 'os' => 'Windows 11 Pro', 'user' => 'John Smith', 'department' => 'IT', 'status' => 'online', 'agent' => 'active', 'last_seen' => '2025-01-26 10:55:00'],
    ['id' => 2, 'name' => 'WKS-SALES-05', 'ip' => '192.168.1.115', 'type' => 'workstation', 'os' => 'Windows 10 Pro', 'user' => 'Jane Doe', 'department' => 'Sales', 'status' => 'online', 'agent' => 'active', 'last_seen' => '2025-01-26 10:54:00'],
    ['id' => 3, 'name' => 'SRV-WEB-02', 'ip' => '192.168.1.50', 'type' => 'server', 'os' => 'Windows Server 2022', 'user' => 'System', 'department' => 'IT', 'status' => 'online', 'agent' => 'active', 'last_seen' => '2025-01-26 10:55:00'],
    ['id' => 4, 'name' => 'WKS-HR-03', 'ip' => '192.168.1.130', 'type' => 'workstation', 'os' => 'Windows 11 Pro', 'user' => 'Mike Johnson', 'department' => 'HR', 'status' => 'online', 'agent' => 'active', 'last_seen' => '2025-01-26 10:53:00'],
    ['id' => 5, 'name' => 'LAPTOP-EXEC-01', 'ip' => '192.168.1.200', 'type' => 'laptop', 'os' => 'Windows 11 Enterprise', 'user' => 'CEO', 'department' => 'Executive', 'status' => 'online', 'agent' => 'active', 'last_seen' => '2025-01-26 10:52:00'],
    ['id' => 6, 'name' => 'SRV-DB-01', 'ip' => '192.168.1.10', 'type' => 'server', 'os' => 'Ubuntu 22.04 LTS', 'user' => 'System', 'department' => 'IT', 'status' => 'online', 'agent' => 'active', 'last_seen' => '2025-01-26 10:55:00'],
    ['id' => 7, 'name' => 'WKS-FIN-02', 'ip' => '192.168.1.142', 'type' => 'workstation', 'os' => 'Windows 10 Pro', 'user' => 'Sarah Wilson', 'department' => 'Finance', 'status' => 'online', 'agent' => 'active', 'last_seen' => '2025-01-26 10:50:00'],
    ['id' => 8, 'name' => 'WKS-DEV-04', 'ip' => '192.168.1.88', 'type' => 'workstation', 'os' => 'Windows 11 Pro', 'user' => 'Dev Team', 'department' => 'Development', 'status' => 'offline', 'agent' => 'inactive', 'last_seen' => '2025-01-26 08:45:00'],
];

$scheduledTasks = [
    ['id' => 1, 'name' => 'Weekly System Update', 'devices' => 'All Workstations', 'type' => 'script', 'schedule' => 'Every Sunday 2:00 AM', 'next_run' => '2025-02-02 02:00:00', 'status' => 'active'],
    ['id' => 2, 'name' => 'Daily Backup Check', 'devices' => 'All Servers', 'type' => 'command', 'schedule' => 'Daily 6:00 AM', 'next_run' => '2025-01-27 06:00:00', 'status' => 'active'],
    ['id' => 3, 'name' => 'Security Scan', 'devices' => 'Finance Department', 'type' => 'script', 'schedule' => 'Monthly 1st', 'next_run' => '2025-02-01 00:00:00', 'status' => 'active'],
];

$stats = [
    'active_sessions' => count($activeSessions),
    'pending_requests' => count($pendingRequests),
    'online_devices' => count(array_filter($managedDevices, fn($d) => $d['status'] === 'online')),
    'total_devices' => count($managedDevices),
    'today_sessions' => 47,
    'avg_resolution' => '12 min'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Remote Support | IOC</title>
    <link rel="stylesheet" href="../admin/style.css">
    <style>
        body { background: #f5f5f5; padding: 20px; }
        .container { max-width: 1600px; margin: 0 auto; }

        .header-bar {
            background: linear-gradient(135deg, #00bcd4 0%, #0097a7 100%);
            padding: 20px 30px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
            box-shadow: 0 4px 15px rgba(0,188,212,0.3);
        }
        .header-bar h1 { margin: 0; font-size: 24px; }
        .header-bar p { margin: 5px 0 0; opacity: 0.9; }
        .back-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            transition: all 0.3s;
        }
        .back-btn:hover { background: rgba(255,255,255,0.3); }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-icon { font-size: 32px; margin-bottom: 10px; }
        .stat-value { font-size: 28px; font-weight: bold; color: #00bcd4; }
        .stat-label { font-size: 12px; color: #666; margin-top: 5px; }

        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .tab-btn {
            background: white;
            padding: 12px 24px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 600;
            font-size: 14px;
        }
        .tab-btn:hover { border-color: #00bcd4; color: #00bcd4; }
        .tab-btn.active { background: #00bcd4; color: white; border-color: #00bcd4; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }

        .card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .card h2 { margin: 0 0 20px; color: #333; font-size: 20px; }
        .card h3 { margin: 0 0 15px; color: #333; font-size: 16px; }

        .action-bar {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
            align-items: center;
        }
        .action-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s;
        }
        .action-btn.primary { background: #00bcd4; color: white; }
        .action-btn.primary:hover { background: #0097a7; }
        .action-btn.success { background: #4CAF50; color: white; }
        .action-btn.success:hover { background: #43a047; }
        .action-btn.warning { background: #ff9800; color: white; }
        .action-btn.warning:hover { background: #f57c00; }
        .action-btn.danger { background: #f44336; color: white; }
        .action-btn.danger:hover { background: #e53935; }
        .action-btn.secondary { background: #9e9e9e; color: white; }
        .action-btn.secondary:hover { background: #757575; }

        .search-box {
            padding: 10px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            width: 250px;
            font-size: 14px;
        }
        .search-box:focus { outline: none; border-color: #00bcd4; }

        /* Session Cards */
        .sessions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }
        .session-card {
            background: white;
            border-radius: 10px;
            border: 2px solid #e0e0e0;
            overflow: hidden;
            transition: all 0.3s;
        }
        .session-card:hover { border-color: #00bcd4; box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
        .session-card.active { border-color: #4CAF50; }
        .session-card.pending { border-color: #ff9800; }
        .session-header {
            padding: 15px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .session-device { font-weight: 600; font-size: 16px; }
        .session-status {
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .session-status.active { background: #e8f5e9; color: #2e7d32; }
        .session-status.pending { background: #fff3e0; color: #e65100; }
        .session-status.ended { background: #f5f5f5; color: #666; }
        .session-body { padding: 15px; }
        .session-info { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 13px; }
        .session-info-item { display: flex; flex-direction: column; }
        .session-info-label { color: #888; font-size: 11px; }
        .session-info-value { font-weight: 500; color: #333; }
        .session-actions { padding: 15px; background: #f8f9fa; display: flex; gap: 8px; }
        .session-btn {
            flex: 1;
            padding: 8px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.2s;
        }
        .session-btn.connect { background: #4CAF50; color: white; }
        .session-btn.connect:hover { background: #43a047; }
        .session-btn.view { background: #2196F3; color: white; }
        .session-btn.view:hover { background: #1e88e5; }
        .session-btn.end { background: #f44336; color: white; }
        .session-btn.end:hover { background: #e53935; }
        .session-btn.chat { background: #9c27b0; color: white; }
        .session-btn.chat:hover { background: #7b1fa2; }

        /* Devices Table */
        .devices-table {
            width: 100%;
            border-collapse: collapse;
        }
        .devices-table th {
            background: #00bcd4;
            color: white;
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
        }
        .devices-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #e0e0e0;
        }
        .devices-table tr:hover { background: #f8f9fa; }
        .device-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .device-status.online { background: #e8f5e9; color: #2e7d32; }
        .device-status.offline { background: #ffebee; color: #c62828; }
        .device-status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: currentColor;
        }
        .device-actions { display: flex; gap: 5px; }
        .device-action-btn {
            padding: 6px 10px;
            border: 1px solid #e0e0e0;
            background: white;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.2s;
        }
        .device-action-btn:hover { background: #f0f0f0; border-color: #00bcd4; }

        /* Request Cards */
        .request-card {
            background: white;
            border-radius: 10px;
            border-left: 4px solid #ff9800;
            padding: 20px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .request-card.critical { border-left-color: #f44336; }
        .request-card.high { border-left-color: #ff9800; }
        .request-card.medium { border-left-color: #ffc107; }
        .request-card.low { border-left-color: #4CAF50; }
        .request-info h4 { margin: 0 0 5px; font-size: 16px; }
        .request-info p { margin: 0; color: #666; font-size: 13px; }
        .request-meta { display: flex; gap: 15px; margin-top: 10px; font-size: 12px; color: #888; }
        .priority-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .priority-badge.critical { background: #ffebee; color: #c62828; }
        .priority-badge.high { background: #fff3e0; color: #e65100; }
        .priority-badge.medium { background: #fffde7; color: #f57f17; }
        .priority-badge.low { background: #e8f5e9; color: #2e7d32; }

        /* Remote Desktop Viewer */
        .remote-viewer {
            background: #1a1a2e;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 20px;
        }
        .viewer-toolbar {
            background: #16213e;
            padding: 10px 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .viewer-title { color: white; font-weight: 600; }
        .viewer-controls { display: flex; gap: 10px; }
        .viewer-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            background: rgba(255,255,255,0.1);
            color: white;
            transition: all 0.2s;
        }
        .viewer-btn:hover { background: rgba(255,255,255,0.2); }
        .viewer-btn.danger { background: #f44336; }
        .viewer-screen {
            height: 400px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            position: relative;
        }
        .screen-placeholder {
            text-align: center;
            color: #888;
        }
        .screen-placeholder-icon { font-size: 64px; margin-bottom: 15px; }

        /* File Transfer */
        .file-transfer-panel {
            display: grid;
            grid-template-columns: 1fr 80px 1fr;
            gap: 20px;
            align-items: start;
        }
        .file-panel {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
        }
        .file-panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e0e0e0;
        }
        .file-panel-title { font-weight: 600; font-size: 14px; }
        .file-path {
            font-family: monospace;
            font-size: 12px;
            color: #666;
            background: white;
            padding: 8px 12px;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        .file-list {
            max-height: 300px;
            overflow-y: auto;
        }
        .file-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 10px;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.2s;
        }
        .file-item:hover { background: #e0e0e0; }
        .file-item.selected { background: #e3f2fd; }
        .file-icon { font-size: 18px; }
        .file-name { flex: 1; font-size: 13px; }
        .file-size { font-size: 11px; color: #888; }
        .transfer-controls {
            display: flex;
            flex-direction: column;
            gap: 10px;
            align-items: center;
            padding-top: 100px;
        }
        .transfer-btn {
            width: 50px;
            height: 40px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 18px;
            background: #00bcd4;
            color: white;
            transition: all 0.2s;
        }
        .transfer-btn:hover { background: #0097a7; }

        /* Command Terminal */
        .terminal {
            background: #1e1e1e;
            border-radius: 8px;
            overflow: hidden;
            font-family: 'Consolas', 'Monaco', monospace;
        }
        .terminal-header {
            background: #323232;
            padding: 10px 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .terminal-dots {
            display: flex;
            gap: 6px;
        }
        .terminal-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }
        .terminal-dot.red { background: #ff5f56; }
        .terminal-dot.yellow { background: #ffbd2e; }
        .terminal-dot.green { background: #27ca3f; }
        .terminal-title { color: #888; font-size: 12px; flex: 1; text-align: center; }
        .terminal-body {
            padding: 15px;
            height: 300px;
            overflow-y: auto;
            color: #00ff00;
            font-size: 13px;
            line-height: 1.6;
        }
        .terminal-output { white-space: pre-wrap; }
        .terminal-input-line {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 15px;
            background: #252526;
            border-top: 1px solid #323232;
        }
        .terminal-prompt { color: #00bcd4; }
        .terminal-input {
            flex: 1;
            background: transparent;
            border: none;
            color: #00ff00;
            font-family: inherit;
            font-size: 13px;
            outline: none;
        }

        /* Chat Panel */
        .chat-panel {
            display: flex;
            flex-direction: column;
            height: 400px;
            background: #f8f9fa;
            border-radius: 8px;
            overflow: hidden;
        }
        .chat-messages {
            flex: 1;
            padding: 15px;
            overflow-y: auto;
        }
        .chat-message {
            margin-bottom: 15px;
            max-width: 80%;
        }
        .chat-message.sent { margin-left: auto; }
        .chat-message.received { margin-right: auto; }
        .chat-bubble {
            padding: 10px 15px;
            border-radius: 15px;
            font-size: 14px;
        }
        .chat-message.sent .chat-bubble {
            background: #00bcd4;
            color: white;
            border-bottom-right-radius: 5px;
        }
        .chat-message.received .chat-bubble {
            background: white;
            border-bottom-left-radius: 5px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .chat-time {
            font-size: 10px;
            color: #888;
            margin-top: 4px;
        }
        .chat-message.sent .chat-time { text-align: right; }
        .chat-input-area {
            display: flex;
            gap: 10px;
            padding: 15px;
            background: white;
            border-top: 1px solid #e0e0e0;
        }
        .chat-input {
            flex: 1;
            padding: 10px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 20px;
            font-size: 14px;
            outline: none;
        }
        .chat-input:focus { border-color: #00bcd4; }
        .chat-send-btn {
            padding: 10px 20px;
            background: #00bcd4;
            color: white;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            font-weight: 600;
        }
        .chat-send-btn:hover { background: #0097a7; }

        /* Modal Styles */
        .rs-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1000;
        }
        .rs-modal.active { display: flex; align-items: center; justify-content: center; }
        .rs-modal-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.6);
            backdrop-filter: blur(4px);
        }
        .rs-modal-content {
            position: relative;
            background: white;
            border-radius: 12px;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .rs-modal-content.large { max-width: 1000px; }
        .rs-modal-content.small { max-width: 500px; }
        .rs-modal-header {
            padding: 20px;
            background: linear-gradient(135deg, #00bcd4 0%, #0097a7 100%);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .rs-modal-header h3 { margin: 0; font-size: 18px; }
        .rs-modal-close {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            font-size: 20px;
            cursor: pointer;
        }
        .rs-modal-close:hover { background: rgba(255,255,255,0.3); }
        .rs-modal-body {
            padding: 20px;
            max-height: calc(90vh - 140px);
            overflow-y: auto;
        }
        .rs-modal-footer {
            padding: 15px 20px;
            background: #f5f5f5;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 8px; color: #333; }
        .form-control {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
        }
        .form-control:focus { outline: none; border-color: #00bcd4; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }

        /* System Info Panel */
        .sysinfo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }
        .sysinfo-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
        }
        .sysinfo-card h4 {
            margin: 0 0 15px;
            font-size: 14px;
            color: #00bcd4;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .sysinfo-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e0e0e0;
            font-size: 13px;
        }
        .sysinfo-item:last-child { border-bottom: none; }
        .sysinfo-label { color: #666; }
        .sysinfo-value { font-weight: 500; }

        /* Toast */
        .rs-toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 15px 25px;
            border-radius: 8px;
            color: white;
            font-weight: 500;
            box-shadow: 0 5px 20px rgba(0,0,0,0.3);
            z-index: 2000;
            animation: toastSlide 0.3s ease;
        }
        .rs-toast.success { background: #4CAF50; }
        .rs-toast.error { background: #f44336; }
        .rs-toast.info { background: #00bcd4; }
        @keyframes toastSlide {
            from { transform: translateX(100px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-bar">
            <div>
                <h1>🖥️ Remote Support Center</h1>
                <p>Secure remote assistance and device management</p>
            </div>
            <a href="../index.php" class="back-btn">← Back to Dashboard</a>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">🔗</div>
                <div class="stat-value"><?= $stats['active_sessions'] ?></div>
                <div class="stat-label">Active Sessions</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📋</div>
                <div class="stat-value"><?= $stats['pending_requests'] ?></div>
                <div class="stat-label">Pending Requests</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🖥️</div>
                <div class="stat-value"><?= $stats['online_devices'] ?>/<?= $stats['total_devices'] ?></div>
                <div class="stat-label">Online Devices</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📊</div>
                <div class="stat-value"><?= $stats['today_sessions'] ?></div>
                <div class="stat-label">Sessions Today</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">⏱️</div>
                <div class="stat-value"><?= $stats['avg_resolution'] ?></div>
                <div class="stat-label">Avg Resolution</div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="tabs">
            <button class="tab-btn active" onclick="switchTab('sessions')">🔗 Active Sessions</button>
            <button class="tab-btn" onclick="switchTab('requests')">📋 Support Requests</button>
            <button class="tab-btn" onclick="switchTab('devices')">🖥️ Managed Devices</button>
            <button class="tab-btn" onclick="switchTab('remote')">🖱️ Remote Desktop</button>
            <button class="tab-btn" onclick="switchTab('terminal')">⌨️ Remote Terminal</button>
            <button class="tab-btn" onclick="switchTab('filetransfer')">📁 File Transfer</button>
            <button class="tab-btn" onclick="switchTab('scheduled')">⏰ Scheduled Tasks</button>
            <button class="tab-btn" onclick="switchTab('history')">📜 History</button>
        </div>

        <!-- Active Sessions Tab -->
        <div id="sessions" class="tab-content active">
            <div class="card">
                <div class="action-bar">
                    <button class="action-btn primary" onclick="openNewSessionModal()">➕ New Session</button>
                    <button class="action-btn success" onclick="refreshSessions()">🔄 Refresh</button>
                    <input type="text" class="search-box" placeholder="🔍 Search sessions..." onkeyup="searchSessions(this.value)">
                </div>

                <h2>Active Remote Sessions (<?= count($activeSessions) ?>)</h2>
                <div class="sessions-grid">
                    <?php foreach ($activeSessions as $session): ?>
                    <div class="session-card active">
                        <div class="session-header">
                            <span class="session-device"><?= htmlspecialchars($session['device']) ?></span>
                            <span class="session-status active">● Live</span>
                        </div>
                        <div class="session-body">
                            <div class="session-info">
                                <div class="session-info-item">
                                    <span class="session-info-label">IP Address</span>
                                    <span class="session-info-value"><?= $session['ip'] ?></span>
                                </div>
                                <div class="session-info-item">
                                    <span class="session-info-label">Remote User</span>
                                    <span class="session-info-value"><?= htmlspecialchars($session['user']) ?></span>
                                </div>
                                <div class="session-info-item">
                                    <span class="session-info-label">Technician</span>
                                    <span class="session-info-value"><?= htmlspecialchars($session['technician']) ?></span>
                                </div>
                                <div class="session-info-item">
                                    <span class="session-info-label">Duration</span>
                                    <span class="session-info-value"><?= $session['duration'] ?> min</span>
                                </div>
                            </div>
                        </div>
                        <div class="session-actions">
                            <button class="session-btn view" onclick="viewSession(<?= $session['id'] ?>)">👁️ View</button>
                            <button class="session-btn connect" onclick="joinSession(<?= $session['id'] ?>)">🔗 Join</button>
                            <button class="session-btn chat" onclick="openChat(<?= $session['id'] ?>)">💬 Chat</button>
                            <button class="session-btn end" onclick="endSession(<?= $session['id'] ?>)">⏹️ End</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Support Requests Tab -->
        <div id="requests" class="tab-content">
            <div class="card">
                <div class="action-bar">
                    <button class="action-btn primary" onclick="refreshRequests()">🔄 Refresh</button>
                    <select class="search-box" onchange="filterRequests(this.value)">
                        <option value="all">All Priorities</option>
                        <option value="critical">Critical</option>
                        <option value="high">High</option>
                        <option value="medium">Medium</option>
                        <option value="low">Low</option>
                    </select>
                </div>

                <h2>Pending Support Requests (<?= count($pendingRequests) ?>)</h2>
                <?php foreach ($pendingRequests as $request): ?>
                <div class="request-card <?= $request['priority'] ?>">
                    <div class="request-info">
                        <h4><?= htmlspecialchars($request['issue']) ?></h4>
                        <p><?= htmlspecialchars($request['device']) ?> (<?= $request['ip'] ?>) - <?= htmlspecialchars($request['user']) ?></p>
                        <div class="request-meta">
                            <span>📅 <?= date('H:i', strtotime($request['requested'])) ?></span>
                            <span class="priority-badge <?= $request['priority'] ?>"><?= strtoupper($request['priority']) ?></span>
                        </div>
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <button class="action-btn success" onclick="acceptRequest(<?= $request['id'] ?>)">✓ Accept</button>
                        <button class="action-btn secondary" onclick="viewRequestDetails(<?= $request['id'] ?>)">👁️ Details</button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Managed Devices Tab -->
        <div id="devices" class="tab-content">
            <div class="card">
                <div class="action-bar">
                    <button class="action-btn primary" onclick="openAddDeviceModal()">➕ Add Device</button>
                    <button class="action-btn success" onclick="deployAgent()">📦 Deploy Agent</button>
                    <button class="action-btn warning" onclick="scanNetwork()">🔍 Scan Network</button>
                    <input type="text" class="search-box" placeholder="🔍 Search devices..." onkeyup="searchDevices(this.value)">
                </div>

                <h2>Managed Devices (<?= count($managedDevices) ?>)</h2>
                <table class="devices-table">
                    <thead>
                        <tr>
                            <th>Device Name</th>
                            <th>IP Address</th>
                            <th>Type</th>
                            <th>OS</th>
                            <th>User</th>
                            <th>Department</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($managedDevices as $device): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($device['name']) ?></strong></td>
                            <td><?= $device['ip'] ?></td>
                            <td><?= ucfirst($device['type']) ?></td>
                            <td><?= htmlspecialchars($device['os']) ?></td>
                            <td><?= htmlspecialchars($device['user']) ?></td>
                            <td><?= htmlspecialchars($device['department']) ?></td>
                            <td>
                                <span class="device-status <?= $device['status'] ?>">
                                    <span class="device-status-dot"></span>
                                    <?= ucfirst($device['status']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="device-actions">
                                    <button class="device-action-btn" onclick="connectToDevice('<?= $device['name'] ?>', '<?= $device['ip'] ?>')" title="Connect">🔗</button>
                                    <button class="device-action-btn" onclick="openTerminal('<?= $device['name'] ?>', '<?= $device['ip'] ?>')" title="Terminal">⌨️</button>
                                    <button class="device-action-btn" onclick="viewSystemInfo('<?= $device['name'] ?>')" title="System Info">ℹ️</button>
                                    <button class="device-action-btn" onclick="wakeOnLan('<?= $device['name'] ?>')" title="Wake on LAN">⚡</button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Remote Desktop Tab -->
        <div id="remote" class="tab-content">
            <div class="card">
                <h2>🖱️ Remote Desktop Connection</h2>
                <div class="action-bar">
                    <select id="remoteDeviceSelect" class="search-box" style="width: 300px;">
                        <option value="">Select a device to connect...</option>
                        <?php foreach ($managedDevices as $device): ?>
                            <?php if ($device['status'] === 'online'): ?>
                            <option value="<?= $device['ip'] ?>"><?= htmlspecialchars($device['name']) ?> (<?= $device['ip'] ?>)</option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                    <button class="action-btn primary" onclick="startRemoteDesktop()">🔗 Connect</button>
                    <button class="action-btn secondary" onclick="toggleFullscreen()">⛶ Fullscreen</button>
                </div>

                <div class="remote-viewer">
                    <div class="viewer-toolbar">
                        <span class="viewer-title" id="viewerTitle">Not Connected</span>
                        <div class="viewer-controls">
                            <button class="viewer-btn" onclick="sendCtrlAltDel()">Ctrl+Alt+Del</button>
                            <button class="viewer-btn" onclick="toggleKeyboard()">⌨️ Keyboard</button>
                            <button class="viewer-btn" onclick="takeScreenshot()">📷 Screenshot</button>
                            <button class="viewer-btn" onclick="toggleQuality()">🎨 Quality</button>
                            <button class="viewer-btn danger" onclick="disconnectRemote()">🔌 Disconnect</button>
                        </div>
                    </div>
                    <div class="viewer-screen" id="remoteScreen">
                        <div class="screen-placeholder">
                            <div class="screen-placeholder-icon">🖥️</div>
                            <p>Select a device and click Connect to start remote session</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Remote Terminal Tab -->
        <div id="terminal" class="tab-content">
            <div class="card">
                <h2>⌨️ Remote Command Execution</h2>
                <div class="action-bar">
                    <select id="terminalDeviceSelect" class="search-box" style="width: 300px;">
                        <option value="">Select a device...</option>
                        <?php foreach ($managedDevices as $device): ?>
                            <?php if ($device['status'] === 'online'): ?>
                            <option value="<?= $device['ip'] ?>"><?= htmlspecialchars($device['name']) ?> (<?= $device['ip'] ?>)</option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                    <button class="action-btn primary" onclick="connectTerminal()">🔗 Connect</button>
                    <button class="action-btn secondary" onclick="clearTerminal()">🗑️ Clear</button>
                    <button class="action-btn warning" onclick="openScriptLibrary()">📚 Script Library</button>
                </div>

                <div class="terminal">
                    <div class="terminal-header">
                        <div class="terminal-dots">
                            <span class="terminal-dot red"></span>
                            <span class="terminal-dot yellow"></span>
                            <span class="terminal-dot green"></span>
                        </div>
                        <span class="terminal-title" id="terminalTitle">Not Connected</span>
                    </div>
                    <div class="terminal-body" id="terminalOutput">
<span style="color: #888;">Welcome to IOC Remote Terminal</span>
<span style="color: #888;">Select a device and click Connect to start...</span>
                    </div>
                    <div class="terminal-input-line">
                        <span class="terminal-prompt">$</span>
                        <input type="text" class="terminal-input" id="terminalInput" placeholder="Enter command..." onkeypress="handleTerminalInput(event)" disabled>
                    </div>
                </div>

                <div style="margin-top: 20px;">
                    <h3>Quick Commands</h3>
                    <div style="display: flex; flex-wrap: wrap; gap: 10px; margin-top: 10px;">
                        <button class="action-btn secondary" onclick="runQuickCommand('systeminfo')">System Info</button>
                        <button class="action-btn secondary" onclick="runQuickCommand('ipconfig /all')">IP Config</button>
                        <button class="action-btn secondary" onclick="runQuickCommand('tasklist')">Process List</button>
                        <button class="action-btn secondary" onclick="runQuickCommand('netstat -an')">Network Stats</button>
                        <button class="action-btn secondary" onclick="runQuickCommand('wmic cpu get name')">CPU Info</button>
                        <button class="action-btn secondary" onclick="runQuickCommand('wmic memorychip get capacity')">Memory Info</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- File Transfer Tab -->
        <div id="filetransfer" class="tab-content">
            <div class="card">
                <h2>📁 File Transfer</h2>
                <div class="action-bar">
                    <select id="fileTransferDevice" class="search-box" style="width: 300px;">
                        <option value="">Select a device...</option>
                        <?php foreach ($managedDevices as $device): ?>
                            <?php if ($device['status'] === 'online'): ?>
                            <option value="<?= $device['ip'] ?>"><?= htmlspecialchars($device['name']) ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                    <button class="action-btn primary" onclick="connectFileTransfer()">🔗 Connect</button>
                </div>

                <div class="file-transfer-panel">
                    <!-- Local Files -->
                    <div class="file-panel">
                        <div class="file-panel-header">
                            <span class="file-panel-title">📂 Local System</span>
                            <button class="device-action-btn" onclick="uploadFiles()">📤 Upload</button>
                        </div>
                        <div class="file-path">C:\Users\Technician\Documents</div>
                        <div class="file-list" id="localFileList">
                            <div class="file-item" onclick="selectFile(this)">
                                <span class="file-icon">📁</span>
                                <span class="file-name">..</span>
                                <span class="file-size"></span>
                            </div>
                            <div class="file-item" onclick="selectFile(this)">
                                <span class="file-icon">📁</span>
                                <span class="file-name">Tools</span>
                                <span class="file-size"></span>
                            </div>
                            <div class="file-item" onclick="selectFile(this)">
                                <span class="file-icon">📁</span>
                                <span class="file-name">Scripts</span>
                                <span class="file-size"></span>
                            </div>
                            <div class="file-item" onclick="selectFile(this)">
                                <span class="file-icon">📄</span>
                                <span class="file-name">config.xml</span>
                                <span class="file-size">2.4 KB</span>
                            </div>
                            <div class="file-item" onclick="selectFile(this)">
                                <span class="file-icon">📦</span>
                                <span class="file-name">update_v2.5.exe</span>
                                <span class="file-size">15.8 MB</span>
                            </div>
                            <div class="file-item" onclick="selectFile(this)">
                                <span class="file-icon">📝</span>
                                <span class="file-name">readme.txt</span>
                                <span class="file-size">1.2 KB</span>
                            </div>
                        </div>
                    </div>

                    <!-- Transfer Controls -->
                    <div class="transfer-controls">
                        <button class="transfer-btn" onclick="transferToRemote()" title="Send to Remote">→</button>
                        <button class="transfer-btn" onclick="transferToLocal()" title="Get from Remote">←</button>
                    </div>

                    <!-- Remote Files -->
                    <div class="file-panel">
                        <div class="file-panel-header">
                            <span class="file-panel-title">🖥️ Remote System</span>
                            <button class="device-action-btn" onclick="downloadFiles()">📥 Download</button>
                        </div>
                        <div class="file-path" id="remotePath">Not Connected</div>
                        <div class="file-list" id="remoteFileList">
                            <div style="text-align: center; padding: 40px; color: #888;">
                                Select a device and connect to browse files
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Transfer Progress -->
                <div id="transferProgress" style="display: none; margin-top: 20px;">
                    <h3>Transfer Progress</h3>
                    <div style="background: #e0e0e0; height: 20px; border-radius: 10px; overflow: hidden; margin-top: 10px;">
                        <div id="transferBar" style="width: 0%; height: 100%; background: linear-gradient(90deg, #00bcd4, #0097a7); transition: width 0.3s;"></div>
                    </div>
                    <p id="transferStatus" style="margin-top: 10px; font-size: 13px; color: #666;">Preparing transfer...</p>
                </div>
            </div>
        </div>

        <!-- Scheduled Tasks Tab -->
        <div id="scheduled" class="tab-content">
            <div class="card">
                <div class="action-bar">
                    <button class="action-btn primary" onclick="openNewTaskModal()">➕ New Task</button>
                    <button class="action-btn success" onclick="refreshTasks()">🔄 Refresh</button>
                </div>

                <h2>Scheduled Remote Tasks</h2>
                <table class="devices-table">
                    <thead>
                        <tr>
                            <th>Task Name</th>
                            <th>Target Devices</th>
                            <th>Type</th>
                            <th>Schedule</th>
                            <th>Next Run</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($scheduledTasks as $task): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($task['name']) ?></strong></td>
                            <td><?= htmlspecialchars($task['devices']) ?></td>
                            <td><?= ucfirst($task['type']) ?></td>
                            <td><?= htmlspecialchars($task['schedule']) ?></td>
                            <td><?= date('M j, Y H:i', strtotime($task['next_run'])) ?></td>
                            <td>
                                <span class="device-status online"><?= ucfirst($task['status']) ?></span>
                            </td>
                            <td>
                                <div class="device-actions">
                                    <button class="device-action-btn" onclick="runTaskNow(<?= $task['id'] ?>)" title="Run Now">▶️</button>
                                    <button class="device-action-btn" onclick="editTask(<?= $task['id'] ?>)" title="Edit">✏️</button>
                                    <button class="device-action-btn" onclick="deleteTask(<?= $task['id'] ?>)" title="Delete">🗑️</button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- History Tab -->
        <div id="history" class="tab-content">
            <div class="card">
                <div class="action-bar">
                    <input type="date" class="search-box" onchange="filterHistory(this.value)">
                    <select class="search-box" onchange="filterHistoryType(this.value)">
                        <option value="all">All Types</option>
                        <option value="remote_desktop">Remote Desktop</option>
                        <option value="command_line">Command Line</option>
                        <option value="file_transfer">File Transfer</option>
                        <option value="chat_support">Chat Support</option>
                    </select>
                    <button class="action-btn secondary" onclick="exportHistory()">📤 Export</button>
                </div>

                <h2>Session History</h2>
                <table class="devices-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Device</th>
                            <th>Technician</th>
                            <th>Type</th>
                            <th>Started</th>
                            <th>Ended</th>
                            <th>Duration</th>
                            <th>Notes</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentHistory as $history):
                            $start = strtotime($history['started']);
                            $end = strtotime($history['ended']);
                            $duration = round(($end - $start) / 60);
                        ?>
                        <tr>
                            <td>#<?= $history['id'] ?></td>
                            <td><strong><?= htmlspecialchars($history['device']) ?></strong><br><small style="color: #888;"><?= $history['ip'] ?></small></td>
                            <td><?= htmlspecialchars($history['technician']) ?></td>
                            <td><?= ucwords(str_replace('_', ' ', $history['type'])) ?></td>
                            <td><?= date('M j, H:i', $start) ?></td>
                            <td><?= date('M j, H:i', $end) ?></td>
                            <td><?= $duration ?> min</td>
                            <td><?= htmlspecialchars($history['notes']) ?></td>
                            <td>
                                <div class="device-actions">
                                    <button class="device-action-btn" onclick="viewSessionDetails(<?= $history['id'] ?>)" title="View Details">👁️</button>
                                    <button class="device-action-btn" onclick="playRecording(<?= $history['id'] ?>)" title="Play Recording">▶️</button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- New Session Modal -->
    <div id="newSessionModal" class="rs-modal">
        <div class="rs-modal-overlay" onclick="closeModal('newSessionModal')"></div>
        <div class="rs-modal-content small">
            <div class="rs-modal-header">
                <h3>➕ Start New Remote Session</h3>
                <button class="rs-modal-close" onclick="closeModal('newSessionModal')">&times;</button>
            </div>
            <div class="rs-modal-body">
                <div class="form-group">
                    <label>Target Device</label>
                    <select id="newSessionDevice" class="form-control">
                        <option value="">Select a device...</option>
                        <?php foreach ($managedDevices as $device): ?>
                            <?php if ($device['status'] === 'online'): ?>
                            <option value="<?= $device['id'] ?>"><?= htmlspecialchars($device['name']) ?> (<?= $device['ip'] ?>)</option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Session Type</label>
                    <select id="newSessionType" class="form-control">
                        <option value="remote_desktop">Remote Desktop</option>
                        <option value="command_line">Command Line Only</option>
                        <option value="file_transfer">File Transfer</option>
                        <option value="view_only">View Only (No Control)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Reason/Ticket #</label>
                    <input type="text" class="form-control" id="newSessionReason" placeholder="e.g., Ticket #12345 - Software installation">
                </div>
                <div class="form-group">
                    <label><input type="checkbox" id="newSessionRecord"> Record this session</label>
                </div>
            </div>
            <div class="rs-modal-footer">
                <button class="action-btn secondary" onclick="closeModal('newSessionModal')">Cancel</button>
                <button class="action-btn primary" onclick="startNewSession()">🔗 Start Session</button>
            </div>
        </div>
    </div>

    <!-- System Info Modal -->
    <div id="systemInfoModal" class="rs-modal">
        <div class="rs-modal-overlay" onclick="closeModal('systemInfoModal')"></div>
        <div class="rs-modal-content large">
            <div class="rs-modal-header">
                <h3>ℹ️ System Information - <span id="sysInfoDeviceName">Device</span></h3>
                <button class="rs-modal-close" onclick="closeModal('systemInfoModal')">&times;</button>
            </div>
            <div class="rs-modal-body">
                <div class="sysinfo-grid">
                    <div class="sysinfo-card">
                        <h4>🖥️ System</h4>
                        <div class="sysinfo-item"><span class="sysinfo-label">Hostname</span><span class="sysinfo-value" id="sysHostname">-</span></div>
                        <div class="sysinfo-item"><span class="sysinfo-label">OS</span><span class="sysinfo-value" id="sysOS">-</span></div>
                        <div class="sysinfo-item"><span class="sysinfo-label">Architecture</span><span class="sysinfo-value" id="sysArch">-</span></div>
                        <div class="sysinfo-item"><span class="sysinfo-label">Uptime</span><span class="sysinfo-value" id="sysUptime">-</span></div>
                    </div>
                    <div class="sysinfo-card">
                        <h4>💻 Hardware</h4>
                        <div class="sysinfo-item"><span class="sysinfo-label">CPU</span><span class="sysinfo-value" id="sysCPU">-</span></div>
                        <div class="sysinfo-item"><span class="sysinfo-label">RAM</span><span class="sysinfo-value" id="sysRAM">-</span></div>
                        <div class="sysinfo-item"><span class="sysinfo-label">Disk</span><span class="sysinfo-value" id="sysDisk">-</span></div>
                        <div class="sysinfo-item"><span class="sysinfo-label">GPU</span><span class="sysinfo-value" id="sysGPU">-</span></div>
                    </div>
                    <div class="sysinfo-card">
                        <h4>🌐 Network</h4>
                        <div class="sysinfo-item"><span class="sysinfo-label">IP Address</span><span class="sysinfo-value" id="sysIP">-</span></div>
                        <div class="sysinfo-item"><span class="sysinfo-label">MAC Address</span><span class="sysinfo-value" id="sysMAC">-</span></div>
                        <div class="sysinfo-item"><span class="sysinfo-label">Gateway</span><span class="sysinfo-value" id="sysGateway">-</span></div>
                        <div class="sysinfo-item"><span class="sysinfo-label">DNS</span><span class="sysinfo-value" id="sysDNS">-</span></div>
                    </div>
                    <div class="sysinfo-card">
                        <h4>📊 Performance</h4>
                        <div class="sysinfo-item"><span class="sysinfo-label">CPU Usage</span><span class="sysinfo-value" id="sysCPUUsage">-</span></div>
                        <div class="sysinfo-item"><span class="sysinfo-label">RAM Usage</span><span class="sysinfo-value" id="sysRAMUsage">-</span></div>
                        <div class="sysinfo-item"><span class="sysinfo-label">Disk Usage</span><span class="sysinfo-value" id="sysDiskUsage">-</span></div>
                        <div class="sysinfo-item"><span class="sysinfo-label">Processes</span><span class="sysinfo-value" id="sysProcesses">-</span></div>
                    </div>
                </div>
            </div>
            <div class="rs-modal-footer">
                <button class="action-btn secondary" onclick="closeModal('systemInfoModal')">Close</button>
                <button class="action-btn primary" onclick="refreshSystemInfo()">🔄 Refresh</button>
                <button class="action-btn success" onclick="exportSystemInfo()">📤 Export</button>
            </div>
        </div>
    </div>

    <!-- Request Details Modal -->
    <div id="requestDetailsModal" class="rs-modal">
        <div class="rs-modal-overlay" onclick="closeModal('requestDetailsModal')"></div>
        <div class="rs-modal-content">
            <div class="rs-modal-header" style="background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%);">
                <h3>📋 Support Request Details</h3>
                <button class="rs-modal-close" onclick="closeModal('requestDetailsModal')">&times;</button>
            </div>
            <div class="rs-modal-body">
                <div class="sysinfo-grid" style="grid-template-columns: 1fr 1fr;">
                    <div class="sysinfo-card">
                        <h4>📝 Request Information</h4>
                        <div class="sysinfo-item"><span class="sysinfo-label">Request ID</span><span class="sysinfo-value" id="reqDetailId">-</span></div>
                        <div class="sysinfo-item"><span class="sysinfo-label">Issue</span><span class="sysinfo-value" id="reqDetailIssue">-</span></div>
                        <div class="sysinfo-item"><span class="sysinfo-label">Priority</span><span class="sysinfo-value" id="reqDetailPriority">-</span></div>
                        <div class="sysinfo-item"><span class="sysinfo-label">Requested At</span><span class="sysinfo-value" id="reqDetailTime">-</span></div>
                    </div>
                    <div class="sysinfo-card">
                        <h4>🖥️ Device & User</h4>
                        <div class="sysinfo-item"><span class="sysinfo-label">Device</span><span class="sysinfo-value" id="reqDetailDevice">-</span></div>
                        <div class="sysinfo-item"><span class="sysinfo-label">IP Address</span><span class="sysinfo-value" id="reqDetailIP">-</span></div>
                        <div class="sysinfo-item"><span class="sysinfo-label">User</span><span class="sysinfo-value" id="reqDetailUser">-</span></div>
                        <div class="sysinfo-item"><span class="sysinfo-label">Department</span><span class="sysinfo-value" id="reqDetailDept">-</span></div>
                    </div>
                </div>
                <div class="sysinfo-card" style="margin-top: 15px;">
                    <h4>📄 Description</h4>
                    <p id="reqDetailDescription" style="padding: 15px; background: #f5f5f5; border-radius: 6px; line-height: 1.6;">-</p>
                </div>
                <div class="sysinfo-card" style="margin-top: 15px;">
                    <h4>💬 Add Notes</h4>
                    <textarea id="reqDetailNotes" class="form-control" placeholder="Add notes about this request..." style="min-height: 80px;"></textarea>
                </div>
            </div>
            <div class="rs-modal-footer">
                <button class="action-btn secondary" onclick="closeModal('requestDetailsModal')">Close</button>
                <button class="action-btn warning" onclick="escalateRequest()">⬆️ Escalate</button>
                <button class="action-btn success" onclick="acceptRequestFromModal()">✓ Accept & Connect</button>
            </div>
        </div>
    </div>

    <!-- Accept Request Modal -->
    <div id="acceptRequestModal" class="rs-modal">
        <div class="rs-modal-overlay" onclick="closeModal('acceptRequestModal')"></div>
        <div class="rs-modal-content small">
            <div class="rs-modal-header" style="background: linear-gradient(135deg, #4CAF50 0%, #43a047 100%);">
                <h3>✓ Accept Support Request</h3>
                <button class="rs-modal-close" onclick="closeModal('acceptRequestModal')">&times;</button>
            </div>
            <div class="rs-modal-body">
                <div style="text-align: center; padding: 20px 0;">
                    <div style="font-size: 48px; margin-bottom: 15px;">🎫</div>
                    <h3 id="acceptReqTitle" style="margin-bottom: 10px;">-</h3>
                    <p id="acceptReqInfo" style="color: #666;">-</p>
                </div>
                <div class="form-group">
                    <label>Session Type</label>
                    <select id="acceptSessionType" class="form-control">
                        <option value="remote_desktop">Remote Desktop (Full Control)</option>
                        <option value="view_only">View Only (No Control)</option>
                        <option value="command_line">Command Line Only</option>
                        <option value="chat_only">Chat Support Only</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Estimated Time</label>
                    <select id="acceptEstTime" class="form-control">
                        <option value="15">~15 minutes</option>
                        <option value="30">~30 minutes</option>
                        <option value="60">~1 hour</option>
                        <option value="120">~2 hours</option>
                    </select>
                </div>
                <div class="form-group">
                    <label><input type="checkbox" id="acceptNotifyUser" checked> Notify user before connecting</label>
                </div>
                <div class="form-group">
                    <label><input type="checkbox" id="acceptRecordSession"> Record this session</label>
                </div>
            </div>
            <div class="rs-modal-footer">
                <button class="action-btn secondary" onclick="closeModal('acceptRequestModal')">Cancel</button>
                <button class="action-btn success" onclick="confirmAcceptRequest()">🔗 Accept & Start Session</button>
            </div>
        </div>
    </div>

    <!-- Session Started Modal -->
    <div id="sessionStartedModal" class="rs-modal">
        <div class="rs-modal-overlay"></div>
        <div class="rs-modal-content small" style="text-align: center;">
            <div class="rs-modal-header" style="background: linear-gradient(135deg, #4CAF50 0%, #43a047 100%);">
                <h3>🎉 Session Started</h3>
                <button class="rs-modal-close" onclick="closeModal('sessionStartedModal')">&times;</button>
            </div>
            <div class="rs-modal-body">
                <div style="font-size: 64px; margin: 20px 0;">✅</div>
                <h3>Successfully Connected!</h3>
                <p style="color: #666; margin: 15px 0;" id="sessionStartedInfo">-</p>
                <div style="background: #e8f5e9; padding: 15px; border-radius: 8px; margin-top: 20px;">
                    <strong style="color: #2e7d32;">Session ID: </strong>
                    <code id="newSessionId" style="background: #fff; padding: 4px 8px; border-radius: 4px;">-</code>
                </div>
            </div>
            <div class="rs-modal-footer" style="justify-content: center;">
                <button class="action-btn primary" onclick="goToActiveSession()">🖥️ Open Remote Desktop</button>
                <button class="action-btn secondary" onclick="closeModal('sessionStartedModal')">Stay Here</button>
            </div>
        </div>
    </div>

    <!-- Chat Modal -->
    <div id="chatModal" class="rs-modal">
        <div class="rs-modal-overlay" onclick="closeModal('chatModal')"></div>
        <div class="rs-modal-content">
            <div class="rs-modal-header" style="background: linear-gradient(135deg, #9c27b0 0%, #7b1fa2 100%);">
                <h3>💬 Chat with User - <span id="chatUserName">User</span></h3>
                <button class="rs-modal-close" onclick="closeModal('chatModal')">&times;</button>
            </div>
            <div class="rs-modal-body" style="padding: 0;">
                <div class="chat-panel">
                    <div class="chat-messages" id="chatMessages">
                        <div class="chat-message received">
                            <div class="chat-bubble">Hi, I'm having trouble with my email. It keeps showing an error.</div>
                            <div class="chat-time">10:45 AM</div>
                        </div>
                        <div class="chat-message sent">
                            <div class="chat-bubble">Hello! I'll be happy to help. Can you tell me what error message you're seeing?</div>
                            <div class="chat-time">10:46 AM</div>
                        </div>
                        <div class="chat-message received">
                            <div class="chat-bubble">It says "Cannot connect to server" when I try to send emails</div>
                            <div class="chat-time">10:47 AM</div>
                        </div>
                        <div class="chat-message sent">
                            <div class="chat-bubble">I see. Let me connect to your computer remotely to check the email settings.</div>
                            <div class="chat-time">10:48 AM</div>
                        </div>
                    </div>
                    <div class="chat-input-area">
                        <input type="text" class="chat-input" id="chatInput" placeholder="Type a message..." onkeypress="handleChatInput(event)">
                        <button class="chat-send-btn" onclick="sendChatMessage()">Send</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- New/Edit Task Modal -->
    <div id="taskModal" class="rs-modal">
        <div class="rs-modal-overlay" onclick="closeModal('taskModal')"></div>
        <div class="rs-modal-content">
            <div class="rs-modal-header" style="background: linear-gradient(135deg, #9c27b0 0%, #7b1fa2 100%);">
                <h3 id="taskModalTitle">➕ Create New Scheduled Task</h3>
                <button class="rs-modal-close" onclick="closeModal('taskModal')">&times;</button>
            </div>
            <div class="rs-modal-body">
                <div class="form-group">
                    <label>Task Name *</label>
                    <input type="text" class="form-control" id="taskName" placeholder="e.g., Weekly System Update">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Task Type *</label>
                        <select id="taskType" class="form-control">
                            <option value="script">Script Execution</option>
                            <option value="command">Single Command</option>
                            <option value="patch">Patch Deployment</option>
                            <option value="backup">Backup Task</option>
                            <option value="maintenance">Maintenance</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Priority</label>
                        <select id="taskPriority" class="form-control">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                            <option value="critical">Critical</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Target Devices *</label>
                    <select id="taskDevices" class="form-control" multiple style="height: 100px;">
                        <option value="all_workstations">All Workstations</option>
                        <option value="all_servers">All Servers</option>
                        <option value="finance">Finance Department</option>
                        <option value="hr">HR Department</option>
                        <option value="it">IT Department</option>
                        <option value="executive">Executive Devices</option>
                    </select>
                    <small style="color: #666;">Hold Ctrl/Cmd to select multiple</small>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Schedule Type *</label>
                        <select id="taskScheduleType" class="form-control" onchange="updateScheduleOptions()">
                            <option value="once">Run Once</option>
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                            <option value="monthly">Monthly</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Time *</label>
                        <input type="time" class="form-control" id="taskTime" value="02:00">
                    </div>
                </div>
                <div class="form-group" id="scheduleOptionsContainer">
                    <label>Day</label>
                    <select id="taskDay" class="form-control">
                        <option value="sunday">Sunday</option>
                        <option value="monday">Monday</option>
                        <option value="tuesday">Tuesday</option>
                        <option value="wednesday">Wednesday</option>
                        <option value="thursday">Thursday</option>
                        <option value="friday">Friday</option>
                        <option value="saturday">Saturday</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Command / Script *</label>
                    <textarea id="taskScript" class="form-control" placeholder="Enter command or script to execute..." style="min-height: 100px; font-family: monospace;"></textarea>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea id="taskDescription" class="form-control" placeholder="Optional description of what this task does..."></textarea>
                </div>
                <div class="form-group">
                    <label><input type="checkbox" id="taskEnabled" checked> Enable this task</label>
                </div>
                <div class="form-group">
                    <label><input type="checkbox" id="taskNotify"> Send email notification on completion</label>
                </div>
            </div>
            <div class="rs-modal-footer">
                <button class="action-btn secondary" onclick="closeModal('taskModal')">Cancel</button>
                <button class="action-btn primary" onclick="saveTask()" id="saveTaskBtn">💾 Save Task</button>
            </div>
        </div>
    </div>

    <!-- Run Task Modal -->
    <div id="runTaskModal" class="rs-modal">
        <div class="rs-modal-overlay" onclick="closeModal('runTaskModal')"></div>
        <div class="rs-modal-content small">
            <div class="rs-modal-header" style="background: linear-gradient(135deg, #4CAF50 0%, #43a047 100%);">
                <h3>▶️ Run Task Now</h3>
                <button class="rs-modal-close" onclick="closeModal('runTaskModal')">&times;</button>
            </div>
            <div class="rs-modal-body">
                <div style="text-align: center; padding: 20px 0;">
                    <div style="font-size: 48px; margin-bottom: 15px;" id="runTaskIcon">⏳</div>
                    <h3 id="runTaskName">Task Name</h3>
                    <p id="runTaskStatus" style="color: #666; margin: 15px 0;">Preparing to run task...</p>
                </div>
                <div id="runTaskProgress" style="display: none;">
                    <div style="background: #e0e0e0; height: 20px; border-radius: 10px; overflow: hidden; margin: 20px 0;">
                        <div id="runTaskBar" style="width: 0%; height: 100%; background: linear-gradient(90deg, #4CAF50, #8BC34A); transition: width 0.3s;"></div>
                    </div>
                    <div id="runTaskLog" style="background: #1e1e1e; color: #00ff00; padding: 15px; border-radius: 8px; font-family: monospace; font-size: 12px; max-height: 150px; overflow-y: auto;">
                        <div>Initializing task...</div>
                    </div>
                </div>
                <div id="runTaskDevices" style="margin-top: 15px;">
                    <strong>Target Devices:</strong>
                    <p id="runTaskDeviceList" style="color: #666;">-</p>
                </div>
            </div>
            <div class="rs-modal-footer" id="runTaskFooter">
                <button class="action-btn secondary" onclick="closeModal('runTaskModal')">Cancel</button>
                <button class="action-btn success" onclick="confirmRunTask()" id="confirmRunBtn">▶️ Run Now</button>
            </div>
        </div>
    </div>

    <!-- Delete Task Confirmation Modal -->
    <div id="deleteTaskModal" class="rs-modal">
        <div class="rs-modal-overlay" onclick="closeModal('deleteTaskModal')"></div>
        <div class="rs-modal-content small">
            <div class="rs-modal-header" style="background: linear-gradient(135deg, #f44336 0%, #d32f2f 100%);">
                <h3>🗑️ Delete Task</h3>
                <button class="rs-modal-close" onclick="closeModal('deleteTaskModal')">&times;</button>
            </div>
            <div class="rs-modal-body">
                <div style="text-align: center; padding: 20px 0;">
                    <div style="font-size: 48px; margin-bottom: 15px;">⚠️</div>
                    <h3>Are you sure?</h3>
                    <p style="color: #666; margin: 15px 0;">You are about to delete the following scheduled task:</p>
                    <div style="background: #ffebee; padding: 15px; border-radius: 8px; margin: 15px 0;">
                        <strong id="deleteTaskName" style="color: #c62828;">-</strong>
                    </div>
                    <p style="color: #888; font-size: 13px;">This action cannot be undone.</p>
                </div>
            </div>
            <div class="rs-modal-footer">
                <button class="action-btn secondary" onclick="closeModal('deleteTaskModal')">Cancel</button>
                <button class="action-btn danger" onclick="confirmDeleteTask()">🗑️ Delete Task</button>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="toastContainer"></div>

    <script>
    // Tab switching
    function switchTab(tabName) {
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.getElementById(tabName).classList.add('active');
        event.target.classList.add('active');
    }

    // Toast notification
    function showToast(message, type = 'info') {
        const container = document.getElementById('toastContainer');
        const toast = document.createElement('div');
        toast.className = `rs-toast ${type}`;
        toast.textContent = message;
        container.appendChild(toast);
        setTimeout(() => { toast.style.opacity = '0'; setTimeout(() => toast.remove(), 300); }, 3000);
    }

    // Modal functions
    function openModal(modalId) {
        document.getElementById(modalId).classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    function closeModal(modalId) {
        document.getElementById(modalId).classList.remove('active');
        document.body.style.overflow = '';
    }

    // Session functions
    function openNewSessionModal() { openModal('newSessionModal'); }
    function startNewSession() {
        const device = document.getElementById('newSessionDevice').value;
        if (!device) { showToast('Please select a device', 'error'); return; }
        showToast('Initiating remote session...', 'info');
        closeModal('newSessionModal');
        setTimeout(() => { showToast('Remote session started!', 'success'); }, 1500);
    }
    function viewSession(id) { showToast('Opening session viewer...', 'info'); }
    function joinSession(id) { showToast('Joining session...', 'info'); }
    function endSession(id) {
        if (confirm('Are you sure you want to end this session?')) {
            showToast('Session ended', 'success');
        }
    }
    function refreshSessions() { showToast('Refreshing sessions...', 'info'); }
    function searchSessions(query) { /* Filter logic */ }

    // Chat functions
    function openChat(sessionId) {
        document.getElementById('chatUserName').textContent = 'John Smith';
        openModal('chatModal');
    }
    function sendChatMessage() {
        const input = document.getElementById('chatInput');
        const message = input.value.trim();
        if (!message) return;

        const messagesDiv = document.getElementById('chatMessages');
        const time = new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        messagesDiv.innerHTML += `<div class="chat-message sent"><div class="chat-bubble">${message}</div><div class="chat-time">${time}</div></div>`;
        input.value = '';
        messagesDiv.scrollTop = messagesDiv.scrollHeight;

        setTimeout(() => {
            messagesDiv.innerHTML += `<div class="chat-message received"><div class="chat-bubble">Thanks, I can see you're working on it now!</div><div class="chat-time">${time}</div></div>`;
            messagesDiv.scrollTop = messagesDiv.scrollHeight;
        }, 2000);
    }
    function handleChatInput(e) { if (e.key === 'Enter') sendChatMessage(); }

    // Request data store
    const pendingRequestsData = <?= json_encode($pendingRequests) ?>;
    let currentRequestId = null;

    // Request functions
    function acceptRequest(id) {
        currentRequestId = id;
        const request = pendingRequestsData.find(r => r.id === id);
        if (request) {
            document.getElementById('acceptReqTitle').textContent = request.issue;
            document.getElementById('acceptReqInfo').innerHTML = `
                <strong>${request.device}</strong> (${request.ip})<br>
                User: ${request.user} | Priority: <span class="priority-badge ${request.priority}">${request.priority.toUpperCase()}</span>
            `;
            openModal('acceptRequestModal');
        }
    }

    function viewRequestDetails(id) {
        currentRequestId = id;
        const request = pendingRequestsData.find(r => r.id === id);
        if (request) {
            document.getElementById('reqDetailId').textContent = '#' + request.id;
            document.getElementById('reqDetailIssue').textContent = request.issue;
            document.getElementById('reqDetailPriority').innerHTML = `<span class="priority-badge ${request.priority}">${request.priority.toUpperCase()}</span>`;
            document.getElementById('reqDetailTime').textContent = request.requested;
            document.getElementById('reqDetailDevice').textContent = request.device;
            document.getElementById('reqDetailIP').textContent = request.ip;
            document.getElementById('reqDetailUser').textContent = request.user;

            // Additional details based on request
            const deptMap = {
                'Mike Johnson': 'Human Resources',
                'Sarah Wilson': 'Finance',
                'CEO Office': 'Executive'
            };
            document.getElementById('reqDetailDept').textContent = deptMap[request.user] || 'General';

            const descMap = {
                'Cannot access email': 'User reports being unable to access their email account. Error message displays "Cannot connect to server". Issue started approximately 30 minutes ago. User has tried restarting Outlook but the problem persists. This is affecting their ability to communicate with clients and complete urgent tasks.',
                'Printer not working': 'Network printer on Floor 2 (HP LaserJet Pro) is not responding to print jobs. Print queue shows jobs as pending but nothing is printing. Other users on the same floor are experiencing the same issue. Printer display shows "Ready" status.',
                'Software installation needed': 'Executive requires urgent installation of Adobe Acrobat Pro DC for reviewing and signing contracts. Meeting scheduled in 2 hours requires this software. Standard software request process was bypassed due to urgency. License has been pre-approved by IT Manager.'
            };
            document.getElementById('reqDetailDescription').textContent = descMap[request.issue] || 'No additional details provided.';

            openModal('requestDetailsModal');
        }
    }

    function acceptRequestFromModal() {
        closeModal('requestDetailsModal');
        acceptRequest(currentRequestId);
    }

    function confirmAcceptRequest() {
        const request = pendingRequestsData.find(r => r.id === currentRequestId);
        const sessionType = document.getElementById('acceptSessionType').value;
        const notifyUser = document.getElementById('acceptNotifyUser').checked;

        closeModal('acceptRequestModal');

        // Show connecting toast
        showToast('Connecting to ' + request.device + '...', 'info');

        // Simulate connection process
        setTimeout(() => {
            const sessionId = 'SES-' + Date.now().toString().slice(-6);
            document.getElementById('newSessionId').textContent = sessionId;
            document.getElementById('sessionStartedInfo').innerHTML = `
                Connected to <strong>${request.device}</strong><br>
                User: ${request.user} | Type: ${sessionType.replace('_', ' ')}
            `;

            // Remove the request card from the list
            const requestCards = document.querySelectorAll('.request-card');
            requestCards.forEach(card => {
                if (card.innerHTML.includes(request.issue)) {
                    card.style.transition = 'all 0.3s';
                    card.style.opacity = '0';
                    card.style.transform = 'translateX(100px)';
                    setTimeout(() => card.remove(), 300);
                }
            });

            // Update pending count
            const pendingTitle = document.querySelector('#requests h2');
            if (pendingTitle) {
                const currentCount = parseInt(pendingTitle.textContent.match(/\d+/)[0]);
                pendingTitle.textContent = `Pending Support Requests (${currentCount - 1})`;
            }

            openModal('sessionStartedModal');
            showToast('Session started successfully!', 'success');
        }, 2000);
    }

    function goToActiveSession() {
        closeModal('sessionStartedModal');
        // Switch to Remote Desktop tab
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.getElementById('remote').classList.add('active');
        document.querySelectorAll('.tab-btn')[3].classList.add('active');

        // Simulate connection
        const request = pendingRequestsData.find(r => r.id === currentRequestId);
        if (request) {
            document.getElementById('remoteDeviceSelect').value = request.ip;
            startRemoteDesktop();
        }
    }

    function escalateRequest() {
        showToast('Request escalated to senior technician', 'warning');
        closeModal('requestDetailsModal');
    }

    function refreshRequests() {
        showToast('Refreshing requests...', 'info');
        setTimeout(() => showToast('Requests updated', 'success'), 1000);
    }

    function filterRequests(priority) {
        const cards = document.querySelectorAll('.request-card');
        cards.forEach(card => {
            if (priority === 'all' || card.classList.contains(priority)) {
                card.style.display = 'flex';
            } else {
                card.style.display = 'none';
            }
        });
    }

    // Device functions
    function openAddDeviceModal() { showToast('Opening add device dialog...', 'info'); }
    function deployAgent() { showToast('Preparing agent deployment package...', 'info'); }
    function scanNetwork() { showToast('Scanning network for devices...', 'info'); }
    function searchDevices(query) { /* Search logic */ }

    function connectToDevice(name, ip) {
        document.getElementById('remoteDeviceSelect').value = ip;
        switchTab('remote');
        document.querySelectorAll('.tab-btn')[3].classList.add('active');
        document.querySelectorAll('.tab-btn').forEach((b, i) => { if (i !== 3) b.classList.remove('active'); });
        startRemoteDesktop();
    }

    function openTerminal(name, ip) {
        document.getElementById('terminalDeviceSelect').value = ip;
        switchTab('terminal');
        document.querySelectorAll('.tab-btn')[4].classList.add('active');
        document.querySelectorAll('.tab-btn').forEach((b, i) => { if (i !== 4) b.classList.remove('active'); });
        connectTerminal();
    }

    function viewSystemInfo(deviceName) {
        document.getElementById('sysInfoDeviceName').textContent = deviceName;
        // Populate with sample data
        document.getElementById('sysHostname').textContent = deviceName;
        document.getElementById('sysOS').textContent = 'Windows 11 Pro';
        document.getElementById('sysArch').textContent = '64-bit';
        document.getElementById('sysUptime').textContent = '5 days, 12 hours';
        document.getElementById('sysCPU').textContent = 'Intel Core i7-12700K';
        document.getElementById('sysRAM').textContent = '32 GB DDR4';
        document.getElementById('sysDisk').textContent = '512 GB SSD';
        document.getElementById('sysGPU').textContent = 'NVIDIA RTX 3060';
        document.getElementById('sysIP').textContent = '192.168.1.101';
        document.getElementById('sysMAC').textContent = 'AA:BB:CC:DD:EE:FF';
        document.getElementById('sysGateway').textContent = '192.168.1.1';
        document.getElementById('sysDNS').textContent = '8.8.8.8, 8.8.4.4';
        document.getElementById('sysCPUUsage').textContent = '23%';
        document.getElementById('sysRAMUsage').textContent = '8.5 GB / 32 GB (27%)';
        document.getElementById('sysDiskUsage').textContent = '245 GB / 512 GB (48%)';
        document.getElementById('sysProcesses').textContent = '156 running';
        openModal('systemInfoModal');
    }

    function wakeOnLan(name) { showToast(`Sending Wake-on-LAN to ${name}...`, 'info'); }
    function refreshSystemInfo() { showToast('Refreshing system info...', 'info'); }
    function exportSystemInfo() { showToast('Exporting system info...', 'success'); }

    // Remote Desktop functions
    function startRemoteDesktop() {
        const device = document.getElementById('remoteDeviceSelect').value;
        if (!device) { showToast('Please select a device', 'error'); return; }

        showToast('Connecting to remote desktop...', 'info');
        document.getElementById('viewerTitle').textContent = 'Connecting...';

        setTimeout(() => {
            document.getElementById('viewerTitle').textContent = document.getElementById('remoteDeviceSelect').selectedOptions[0].text;
            document.getElementById('remoteScreen').innerHTML = `
                <div style="width: 100%; height: 100%; background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1920 1080"><rect fill="%23008080"/><text x="960" y="540" text-anchor="middle" fill="white" font-size="48">Remote Desktop Connected</text><text x="960" y="600" text-anchor="middle" fill="%23ccc" font-size="24">Simulated Remote Session</text></svg>') center/cover; display: flex; align-items: center; justify-content: center;">
                    <div style="background: rgba(0,0,0,0.8); padding: 30px 50px; border-radius: 10px; color: white; text-align: center;">
                        <div style="font-size: 48px; margin-bottom: 15px;">🖥️</div>
                        <div style="font-size: 18px; font-weight: bold;">Remote Desktop Active</div>
                        <div style="font-size: 14px; color: #aaa; margin-top: 10px;">Session: ${document.getElementById('remoteDeviceSelect').selectedOptions[0].text}</div>
                    </div>
                </div>`;
            showToast('Connected successfully!', 'success');
        }, 2000);
    }

    function disconnectRemote() {
        document.getElementById('viewerTitle').textContent = 'Not Connected';
        document.getElementById('remoteScreen').innerHTML = `<div class="screen-placeholder"><div class="screen-placeholder-icon">🖥️</div><p>Select a device and click Connect to start remote session</p></div>`;
        showToast('Disconnected from remote desktop', 'info');
    }

    function sendCtrlAltDel() { showToast('Sending Ctrl+Alt+Del...', 'info'); }
    function toggleKeyboard() { showToast('Virtual keyboard toggled', 'info'); }
    function takeScreenshot() { showToast('Screenshot captured!', 'success'); }
    function toggleQuality() { showToast('Quality settings changed', 'info'); }
    function toggleFullscreen() { document.documentElement.requestFullscreen?.(); }

    // Terminal functions
    let terminalConnected = false;
    function connectTerminal() {
        const device = document.getElementById('terminalDeviceSelect').value;
        if (!device) { showToast('Please select a device', 'error'); return; }

        showToast('Connecting to terminal...', 'info');
        const output = document.getElementById('terminalOutput');
        const deviceName = document.getElementById('terminalDeviceSelect').selectedOptions[0].text;

        output.innerHTML = `<span style="color: #888;">Connecting to ${deviceName}...</span>\n`;

        setTimeout(() => {
            document.getElementById('terminalTitle').textContent = deviceName;
            document.getElementById('terminalInput').disabled = false;
            output.innerHTML += `<span style="color: #4CAF50;">Connected to ${deviceName}</span>\n`;
            output.innerHTML += `<span style="color: #888;">Microsoft Windows [Version 10.0.22621.2428]</span>\n`;
            output.innerHTML += `<span style="color: #888;">(c) Microsoft Corporation. All rights reserved.</span>\n\n`;
            terminalConnected = true;
            showToast('Terminal connected!', 'success');
        }, 1500);
    }

    function clearTerminal() {
        document.getElementById('terminalOutput').innerHTML = '';
    }

    function handleTerminalInput(e) {
        if (e.key === 'Enter') {
            const input = document.getElementById('terminalInput');
            const cmd = input.value.trim();
            if (!cmd) return;

            const output = document.getElementById('terminalOutput');
            output.innerHTML += `<span style="color: #00bcd4;">$ ${cmd}</span>\n`;
            input.value = '';

            // Simulate command output
            setTimeout(() => {
                output.innerHTML += getCommandOutput(cmd) + '\n';
                output.scrollTop = output.scrollHeight;
            }, 500);
        }
    }

    function getCommandOutput(cmd) {
        const outputs = {
            'systeminfo': `<span>Host Name: WKS-ADMIN-01
OS Name: Microsoft Windows 11 Pro
OS Version: 10.0.22621 Build 22621
System Manufacturer: Dell Inc.
System Model: OptiPlex 7090
Processor: Intel(R) Core(TM) i7-12700K
Total Physical Memory: 32,768 MB
Available Physical Memory: 24,156 MB</span>`,
            'ipconfig /all': `<span>Ethernet adapter Ethernet:
   Connection-specific DNS Suffix: corp.local
   IPv4 Address: 192.168.1.101
   Subnet Mask: 255.255.255.0
   Default Gateway: 192.168.1.1
   DNS Servers: 192.168.1.10, 8.8.8.8</span>`,
            'tasklist': `<span>Image Name         PID   Mem Usage
========================= ====== ============
System                  4    140 K
Registry              112   54,280 K
smss.exe              456    1,024 K
csrss.exe             624   5,124 K
explorer.exe         3456  125,456 K
chrome.exe           7890  456,789 K</span>`,
            'netstat -an': `<span>Active Connections
  Proto  Local Address          Foreign Address        State
  TCP    0.0.0.0:80             0.0.0.0:0              LISTENING
  TCP    0.0.0.0:443            0.0.0.0:0              LISTENING
  TCP    192.168.1.101:51234    142.250.80.46:443      ESTABLISHED
  TCP    192.168.1.101:51567    20.190.163.21:443      ESTABLISHED</span>`,
            'dir': `<span> Volume in drive C is OS
 Directory of C:\\Users\\Admin

01/26/2025  10:30 AM    &lt;DIR&gt;          Desktop
01/26/2025  09:15 AM    &lt;DIR&gt;          Documents
01/25/2025  04:20 PM    &lt;DIR&gt;          Downloads
               0 File(s)              0 bytes
               3 Dir(s)  267,456,512,000 bytes free</span>`
        };
        return outputs[cmd] || `<span style="color: #ff9800;">'${cmd}' executed successfully</span>`;
    }

    function runQuickCommand(cmd) {
        if (!terminalConnected) { showToast('Please connect to a device first', 'error'); return; }
        document.getElementById('terminalInput').value = cmd;
        handleTerminalInput({key: 'Enter'});
    }

    function openScriptLibrary() { showToast('Opening script library...', 'info'); }

    // File Transfer functions
    function connectFileTransfer() {
        const device = document.getElementById('fileTransferDevice').value;
        if (!device) { showToast('Please select a device', 'error'); return; }

        showToast('Connecting...', 'info');
        setTimeout(() => {
            document.getElementById('remotePath').textContent = 'C:\\Users\\Admin\\Desktop';
            document.getElementById('remoteFileList').innerHTML = `
                <div class="file-item" onclick="selectFile(this)"><span class="file-icon">📁</span><span class="file-name">..</span><span class="file-size"></span></div>
                <div class="file-item" onclick="selectFile(this)"><span class="file-icon">📁</span><span class="file-name">Documents</span><span class="file-size"></span></div>
                <div class="file-item" onclick="selectFile(this)"><span class="file-icon">📁</span><span class="file-name">Downloads</span><span class="file-size"></span></div>
                <div class="file-item" onclick="selectFile(this)"><span class="file-icon">📄</span><span class="file-name">report.docx</span><span class="file-size">156 KB</span></div>
                <div class="file-item" onclick="selectFile(this)"><span class="file-icon">📊</span><span class="file-name">data.xlsx</span><span class="file-size">2.3 MB</span></div>
                <div class="file-item" onclick="selectFile(this)"><span class="file-icon">🖼️</span><span class="file-name">screenshot.png</span><span class="file-size">845 KB</span></div>
            `;
            showToast('Connected to remote file system', 'success');
        }, 1000);
    }

    function selectFile(element) {
        element.parentElement.querySelectorAll('.file-item').forEach(f => f.classList.remove('selected'));
        element.classList.add('selected');
    }

    function transferToRemote() {
        const selected = document.querySelector('#localFileList .file-item.selected');
        if (!selected) { showToast('Please select a file to transfer', 'error'); return; }
        simulateTransfer('Uploading');
    }

    function transferToLocal() {
        const selected = document.querySelector('#remoteFileList .file-item.selected');
        if (!selected) { showToast('Please select a file to download', 'error'); return; }
        simulateTransfer('Downloading');
    }

    function simulateTransfer(action) {
        document.getElementById('transferProgress').style.display = 'block';
        const bar = document.getElementById('transferBar');
        const status = document.getElementById('transferStatus');
        let progress = 0;

        const interval = setInterval(() => {
            progress += Math.random() * 15;
            if (progress >= 100) {
                progress = 100;
                clearInterval(interval);
                status.textContent = 'Transfer complete!';
                showToast('File transfer completed!', 'success');
                setTimeout(() => { document.getElementById('transferProgress').style.display = 'none'; }, 2000);
            } else {
                status.textContent = `${action}... ${Math.round(progress)}%`;
            }
            bar.style.width = progress + '%';
        }, 200);
    }

    function uploadFiles() { showToast('Select files to upload...', 'info'); }
    function downloadFiles() { showToast('Preparing download...', 'info'); }

    // Scheduled Tasks Data
    const scheduledTasksData = <?= json_encode($scheduledTasks) ?>;
    let currentTaskId = null;
    let isEditMode = false;

    // Scheduled Tasks Functions
    function openNewTaskModal() {
        isEditMode = false;
        currentTaskId = null;
        document.getElementById('taskModalTitle').textContent = '➕ Create New Scheduled Task';
        document.getElementById('saveTaskBtn').textContent = '💾 Create Task';

        // Clear form
        document.getElementById('taskName').value = '';
        document.getElementById('taskType').value = 'script';
        document.getElementById('taskPriority').value = 'medium';
        document.getElementById('taskScheduleType').value = 'weekly';
        document.getElementById('taskTime').value = '02:00';
        document.getElementById('taskScript').value = '';
        document.getElementById('taskDescription').value = '';
        document.getElementById('taskEnabled').checked = true;
        document.getElementById('taskNotify').checked = false;

        updateScheduleOptions();
        openModal('taskModal');
    }

    function editTask(id) {
        isEditMode = true;
        currentTaskId = id;
        const task = scheduledTasksData.find(t => t.id === id);

        if (task) {
            document.getElementById('taskModalTitle').textContent = '✏️ Edit Scheduled Task';
            document.getElementById('saveTaskBtn').textContent = '💾 Update Task';

            // Populate form with task data
            document.getElementById('taskName').value = task.name;
            document.getElementById('taskType').value = task.type;
            document.getElementById('taskEnabled').checked = task.status === 'active';

            // Parse schedule
            if (task.schedule.includes('Sunday')) {
                document.getElementById('taskScheduleType').value = 'weekly';
                document.getElementById('taskDay').value = 'sunday';
            } else if (task.schedule.includes('Daily')) {
                document.getElementById('taskScheduleType').value = 'daily';
            } else if (task.schedule.includes('Monthly')) {
                document.getElementById('taskScheduleType').value = 'monthly';
            }

            // Sample script based on task
            const scripts = {
                1: 'wuauclt /detectnow /updatenow\necho "Windows Update initiated"',
                2: 'wbadmin get status\necho "Backup status check completed"',
                3: 'Start-MpScan -ScanType QuickScan\necho "Security scan completed"'
            };
            document.getElementById('taskScript').value = scripts[id] || '# Task script';
            document.getElementById('taskDescription').value = task.name + ' - Automated task';

            updateScheduleOptions();
            openModal('taskModal');
        }
    }

    function updateScheduleOptions() {
        const type = document.getElementById('taskScheduleType').value;
        const container = document.getElementById('scheduleOptionsContainer');

        if (type === 'weekly') {
            container.style.display = 'block';
            container.innerHTML = `
                <label>Day of Week</label>
                <select id="taskDay" class="form-control">
                    <option value="sunday">Sunday</option>
                    <option value="monday">Monday</option>
                    <option value="tuesday">Tuesday</option>
                    <option value="wednesday">Wednesday</option>
                    <option value="thursday">Thursday</option>
                    <option value="friday">Friday</option>
                    <option value="saturday">Saturday</option>
                </select>
            `;
        } else if (type === 'monthly') {
            container.style.display = 'block';
            container.innerHTML = `
                <label>Day of Month</label>
                <select id="taskDay" class="form-control">
                    ${[...Array(31)].map((_, i) => `<option value="${i+1}">${i+1}</option>`).join('')}
                </select>
            `;
        } else {
            container.style.display = 'none';
        }
    }

    function saveTask() {
        const name = document.getElementById('taskName').value.trim();
        const script = document.getElementById('taskScript').value.trim();

        if (!name) {
            showToast('Please enter a task name', 'error');
            return;
        }
        if (!script) {
            showToast('Please enter a command or script', 'error');
            return;
        }

        closeModal('taskModal');

        if (isEditMode) {
            showToast('Task updated successfully!', 'success');
        } else {
            showToast('Task created successfully!', 'success');
            // Add new row to table (simulated)
            const tbody = document.querySelector('#scheduled .devices-table tbody');
            const newRow = document.createElement('tr');
            newRow.innerHTML = `
                <td><strong>${name}</strong></td>
                <td>Selected Devices</td>
                <td>${document.getElementById('taskType').value}</td>
                <td>${document.getElementById('taskScheduleType').value}</td>
                <td>${new Date().toLocaleDateString()}</td>
                <td><span class="device-status online">Active</span></td>
                <td>
                    <div class="device-actions">
                        <button class="device-action-btn" onclick="runTaskNow(99)" title="Run Now">▶️</button>
                        <button class="device-action-btn" onclick="editTask(99)" title="Edit">✏️</button>
                        <button class="device-action-btn" onclick="deleteTask(99)" title="Delete">🗑️</button>
                    </div>
                </td>
            `;
            tbody.appendChild(newRow);
        }
    }

    function runTaskNow(id) {
        currentTaskId = id;
        const task = scheduledTasksData.find(t => t.id === id);

        document.getElementById('runTaskIcon').textContent = '⏳';
        document.getElementById('runTaskName').textContent = task ? task.name : 'Custom Task';
        document.getElementById('runTaskStatus').textContent = 'Ready to execute task';
        document.getElementById('runTaskDeviceList').textContent = task ? task.devices : 'Selected devices';
        document.getElementById('runTaskProgress').style.display = 'none';
        document.getElementById('runTaskBar').style.width = '0%';
        document.getElementById('runTaskLog').innerHTML = '<div>Ready to start...</div>';
        document.getElementById('confirmRunBtn').style.display = 'inline-block';
        document.getElementById('runTaskFooter').innerHTML = `
            <button class="action-btn secondary" onclick="closeModal('runTaskModal')">Cancel</button>
            <button class="action-btn success" onclick="confirmRunTask()" id="confirmRunBtn">▶️ Run Now</button>
        `;

        openModal('runTaskModal');
    }

    function confirmRunTask() {
        const task = scheduledTasksData.find(t => t.id === currentTaskId);
        const taskName = task ? task.name : 'Custom Task';

        document.getElementById('runTaskStatus').textContent = 'Executing task...';
        document.getElementById('runTaskProgress').style.display = 'block';
        document.getElementById('confirmRunBtn').style.display = 'none';
        document.getElementById('runTaskFooter').innerHTML = `
            <button class="action-btn secondary" onclick="closeModal('runTaskModal')" disabled>Please wait...</button>
        `;

        const log = document.getElementById('runTaskLog');
        const bar = document.getElementById('runTaskBar');
        let progress = 0;

        const logMessages = [
            'Connecting to target devices...',
            'Authentication successful',
            'Deploying task to devices...',
            'Device 1: Task started',
            'Device 2: Task started',
            'Device 3: Task started',
            'Waiting for completion...',
            'Device 1: Completed successfully',
            'Device 2: Completed successfully',
            'Device 3: Completed successfully',
            'All devices completed',
            'Task execution finished'
        ];

        let msgIndex = 0;
        const interval = setInterval(() => {
            progress += 8;
            bar.style.width = Math.min(progress, 100) + '%';

            if (msgIndex < logMessages.length) {
                log.innerHTML += `<div style="color: ${msgIndex >= 10 ? '#4CAF50' : '#00ff00'};">[${new Date().toLocaleTimeString()}] ${logMessages[msgIndex]}</div>`;
                log.scrollTop = log.scrollHeight;
                msgIndex++;
            }

            if (progress >= 100) {
                clearInterval(interval);
                document.getElementById('runTaskIcon').textContent = '✅';
                document.getElementById('runTaskStatus').innerHTML = '<span style="color: #4CAF50; font-weight: bold;">Task completed successfully!</span>';
                document.getElementById('runTaskFooter').innerHTML = `
                    <button class="action-btn secondary" onclick="closeModal('runTaskModal')">Close</button>
                    <button class="action-btn primary" onclick="viewTaskResults()">📋 View Results</button>
                `;
                showToast('Task "' + taskName + '" completed successfully!', 'success');
            }
        }, 400);
    }

    function viewTaskResults() {
        closeModal('runTaskModal');
        showToast('Opening task results...', 'info');
    }

    function deleteTask(id) {
        currentTaskId = id;
        const task = scheduledTasksData.find(t => t.id === id);
        document.getElementById('deleteTaskName').textContent = task ? task.name : 'Task #' + id;
        openModal('deleteTaskModal');
    }

    function confirmDeleteTask() {
        closeModal('deleteTaskModal');

        // Find and remove the table row
        const rows = document.querySelectorAll('#scheduled .devices-table tbody tr');
        rows.forEach(row => {
            const taskIdMatch = row.innerHTML.match(/deleteTask\((\d+)\)/);
            if (taskIdMatch && parseInt(taskIdMatch[1]) === currentTaskId) {
                row.style.transition = 'all 0.3s';
                row.style.opacity = '0';
                row.style.transform = 'translateX(50px)';
                setTimeout(() => row.remove(), 300);
            }
        });

        showToast('Task deleted successfully', 'success');
    }

    function refreshTasks() {
        showToast('Refreshing tasks...', 'info');
        setTimeout(() => {
            showToast('Tasks refreshed', 'success');
        }, 1000);
    }

    // History
    function filterHistory(date) { showToast('Filtering by date...', 'info'); }
    function filterHistoryType(type) { showToast('Filtering by type...', 'info'); }
    function exportHistory() { showToast('Exporting history...', 'success'); }
    function viewSessionDetails(id) { showToast('Loading session details...', 'info'); }
    function playRecording(id) { showToast('Loading session recording...', 'info'); }
    </script>
</body>
</html>
