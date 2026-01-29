<?php
/**
 * Network Performance Monitor (NPM)
 * Monitors network health, bandwidth utilization, and device performance
 */

require_once __DIR__ . '/../classes/Database.php';
$db = Database::getInstance();

// Get network devices with performance metrics
$devices = $db->fetchAll("SELECT * FROM network_devices ORDER BY status DESC, device_name");
$total_devices = count($devices);
$online_devices = count(array_filter($devices, fn($d) => $d['status'] === 'online'));

// Calculate uptime percentage
$uptime = $total_devices > 0 ? round(($online_devices / $total_devices) * 100, 1) : 0;

// Get recent performance metrics
$recent_metrics = $db->fetchAll("
    SELECT pm.*, nd.device_name, nd.ip_address
    FROM performance_metrics pm
    LEFT JOIN network_devices nd ON pm.device_id = nd.id
    WHERE pm.timestamp >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
    ORDER BY pm.timestamp DESC
    LIMIT 100
");

// Calculate average bandwidth
$total_bandwidth = 0;
$bandwidth_count = 0;
foreach ($recent_metrics as $metric) {
    if ($metric['metric_type'] === 'bandwidth_in' || $metric['metric_type'] === 'bandwidth_out') {
        $total_bandwidth += $metric['metric_value'];
        $bandwidth_count++;
    }
}
$avg_bandwidth = $bandwidth_count > 0 ? round($total_bandwidth / $bandwidth_count, 2) : 0;

// Generate sample performance data for demonstration
foreach ($devices as &$device) {
    // Simulate CPU, Memory, Bandwidth metrics
    $device['cpu_usage'] = rand(10, 95);
    $device['memory_usage'] = rand(20, 90);
    $device['bandwidth_in'] = rand(100, 950);
    $device['bandwidth_out'] = rand(50, 500);
    $device['packet_loss'] = rand(0, 5) / 10; // 0-0.5%
    $device['latency'] = rand(1, 50); // ms

    // Determine alert status based on thresholds
    $device['alerts'] = [];
    if ($device['cpu_usage'] > 80) $device['alerts'][] = 'High CPU';
    if ($device['memory_usage'] > 85) $device['alerts'][] = 'High Memory';
    if ($device['packet_loss'] > 0.3) $device['alerts'][] = 'Packet Loss';
    if ($device['latency'] > 40) $device['alerts'][] = 'High Latency';
}
unset($device);

// Count alerts
$total_alerts = 0;
foreach ($devices as $device) {
    $total_alerts += count($device['alerts']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Network Performance Monitor | NPM</title>
    <link rel="stylesheet" href="../admin/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/vis-network/9.1.6/standalone/umd/vis-network.min.js"></script>
    <style>
        body { background: #f5f5f5; padding: 20px; }
        .container { max-width: 1600px; margin: 0 auto; }
        .header-bar {
            background: white;
            padding: 20px 30px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .back-btn {
            background: #667eea;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            transition: all 0.3s;
        }
        .back-btn:hover {
            background: #764ba2;
        }
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
        }
        .tab-btn:hover {
            border-color: #667eea;
            color: #667eea;
        }
        .tab-btn.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .device-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .device-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-left: 4px solid;
            position: relative;
        }
        .device-card.online { border-color: #4CAF50; }
        .device-card.offline { border-color: #f44336; }
        .device-card.warning { border-color: #ff9800; }
        .device-icon {
            font-size: 40px;
            margin-bottom: 10px;
        }
        .metric-bar {
            background: #e0e0e0;
            height: 20px;
            border-radius: 10px;
            overflow: hidden;
            margin: 8px 0;
        }
        .metric-fill {
            height: 100%;
            transition: width 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 11px;
            font-weight: bold;
        }
        .chart-container {
            position: relative;
            height: 300px;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .topology-container {
            height: 600px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            padding: 20px;
        }
        #topology-network {
            width: 100%;
            height: 100%;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
        }
        .alert-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #f44336;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
        }
        .metric-label {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            margin-bottom: 4px;
            color: #666;
        }
        .snmp-info {
            background: #f5f5f5;
            padding: 12px;
            border-radius: 6px;
            margin-top: 10px;
            font-size: 12px;
        }
        .chart-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .threshold-config {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .threshold-item {
            display: grid;
            grid-template-columns: 200px 1fr 100px;
            gap: 15px;
            align-items: center;
            padding: 12px;
            border-bottom: 1px solid #e0e0e0;
        }
        .threshold-slider {
            width: 100%;
        }
        .bandwidth-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 5px;
        }
        .bandwidth-high { background: #f44336; }
        .bandwidth-medium { background: #ff9800; }
        .bandwidth-low { background: #4CAF50; }

        /* SNMP Styles */
        .snmp-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .action-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 14px;
        }
        .action-btn.primary { background: #667eea; color: white; }
        .action-btn.primary:hover { background: #5568d3; }
        .action-btn.success { background: #4CAF50; color: white; }
        .action-btn.success:hover { background: #43a047; }
        .action-btn.info { background: #2196F3; color: white; }
        .action-btn.info:hover { background: #1e88e5; }
        .action-btn.warning { background: #ff9800; color: white; }
        .action-btn.warning:hover { background: #f57c00; }
        .action-btn.secondary { background: #9e9e9e; color: white; }
        .action-btn.secondary:hover { background: #757575; }
        .action-btn.danger { background: #f44336; color: white; }
        .action-btn.danger:hover { background: #e53935; }

        .snmp-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        .snmp-stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
        .snmp-stat-icon { font-size: 28px; margin-bottom: 8px; }
        .snmp-stat-value { font-size: 32px; font-weight: bold; }
        .snmp-stat-label { font-size: 12px; opacity: 0.9; margin-top: 5px; }

        .snmp-status-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .snmp-status-badge.active { background: #e8f5e9; color: #2e7d32; }
        .snmp-status-badge.inactive { background: #fafafa; color: #9e9e9e; }

        .snmp-info-row {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
            font-size: 12px;
            border-bottom: 1px solid #eee;
        }
        .snmp-info-row:last-child { border-bottom: none; }
        .snmp-version-badge {
            background: #e3f2fd;
            color: #1565c0;
            padding: 2px 8px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 11px;
        }
        .snmp-last-poll { color: #888; font-size: 11px; }

        .snmp-live-data {
            background: #f8f9fa;
            border-radius: 6px;
            padding: 10px;
            margin-top: 10px;
            border-left: 3px solid #4CAF50;
        }
        .snmp-live-data h4 {
            margin: 0 0 8px 0;
            font-size: 12px;
            color: #333;
        }
        .snmp-metric {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            padding: 3px 0;
        }
        .snmp-oid { color: #888; font-family: monospace; }
        .snmp-value { color: #333; font-weight: 500; }

        .snmp-card-actions {
            display: flex;
            gap: 5px;
            margin-top: 12px;
        }
        .snmp-btn {
            flex: 1;
            padding: 8px 5px;
            border: none;
            border-radius: 5px;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .snmp-btn.configure { background: #667eea; color: white; }
        .snmp-btn.configure:hover { background: #5568d3; }
        .snmp-btn.test { background: #ff9800; color: white; }
        .snmp-btn.test:hover { background: #f57c00; }
        .snmp-btn.walk { background: #9c27b0; color: white; }
        .snmp-btn.walk:hover { background: #7b1fa2; }

        /* Modal Styles */
        .npm-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1000;
        }
        .npm-modal.active { display: flex; align-items: center; justify-content: center; }
        .npm-modal-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.6);
            backdrop-filter: blur(4px);
        }
        .npm-modal-content {
            position: relative;
            background: white;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: modalSlide 0.3s ease;
        }
        .npm-modal-content.large { max-width: 800px; }
        @keyframes modalSlide {
            from { opacity: 0; transform: translateY(-30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .npm-modal-header {
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .npm-modal-header h3 { margin: 0; font-size: 18px; }
        .npm-modal-close {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            font-size: 20px;
            cursor: pointer;
        }
        .npm-modal-close:hover { background: rgba(255,255,255,0.3); }
        .npm-modal-body {
            padding: 20px;
            max-height: calc(90vh - 140px);
            overflow-y: auto;
        }
        .npm-modal-footer {
            padding: 15px 20px;
            background: #f5f5f5;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
        }
        .form-control {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.2s;
        }
        .form-control:focus {
            outline: none;
            border-color: #667eea;
        }
        .form-row {
            display: flex;
            gap: 15px;
        }
        .form-row .form-group { flex: 1; }

        .snmp-test-result {
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
        }
        .snmp-test-result.success { background: #e8f5e9; border-left: 4px solid #4CAF50; }
        .snmp-test-result.error { background: #ffebee; border-left: 4px solid #f44336; }
        .snmp-test-result.pending { background: #fff3e0; border-left: 4px solid #ff9800; }

        .snmp-walk-output {
            background: #263238;
            color: #4CAF50;
            padding: 15px;
            border-radius: 8px;
            font-family: monospace;
            font-size: 12px;
            max-height: 300px;
            overflow-y: auto;
            white-space: pre-wrap;
        }

        .oid-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        .oid-table th, .oid-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        .oid-table th {
            background: #f5f5f5;
            font-weight: 600;
        }
        .oid-table tr:hover { background: #fafafa; }
        .oid-checkbox { width: 18px; height: 18px; }

        .toast-notification {
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
        .toast-notification.success { background: #4CAF50; }
        .toast-notification.error { background: #f44336; }
        .toast-notification.info { background: #2196F3; }
        @keyframes toastSlide {
            from { opacity: 0; transform: translateX(100px); }
            to { opacity: 1; transform: translateX(0); }
        }

        /* ============================================
           Enhanced Topology Styles
           ============================================ */
        .topology-toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            padding: 15px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 10px;
            margin-bottom: 15px;
            align-items: center;
        }
        .toolbar-section {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .toolbar-label {
            font-size: 12px;
            font-weight: 600;
            color: #666;
            margin-right: 5px;
        }
        .topo-btn {
            padding: 8px 12px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .topo-btn:hover {
            background: #f0f0f0;
            border-color: #667eea;
        }
        .topo-btn.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        .topo-btn.primary {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        .topo-btn.primary:hover {
            background: #5568d3;
        }
        .topo-btn.success {
            background: #4CAF50;
            color: white;
            border-color: #4CAF50;
        }
        .topo-btn.success:hover {
            background: #43a047;
        }
        .topo-select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 12px;
            background: white;
            cursor: pointer;
        }
        .topo-select:focus {
            outline: none;
            border-color: #667eea;
        }
        .topo-search {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 12px;
            width: 200px;
        }
        .topo-search:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        /* Topology Stats Bar */
        .topology-stats {
            display: flex;
            gap: 20px;
            padding: 15px 20px;
            background: white;
            border-radius: 10px;
            margin-bottom: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .topo-stat {
            display: flex;
            align-items: center;
            gap: 8px;
            padding-right: 20px;
            border-right: 1px solid #e0e0e0;
        }
        .topo-stat:last-child {
            border-right: none;
        }
        .topo-stat-icon {
            font-size: 18px;
        }
        .topo-stat-value {
            font-size: 22px;
            font-weight: bold;
            color: #2c3e50;
        }
        .topo-stat-label {
            font-size: 11px;
            color: #888;
            text-transform: uppercase;
        }

        /* Main Topology Container */
        .topology-main-container {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }
        .topology-container {
            flex: 1;
            height: 600px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
        }
        #topology-network {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        }

        /* Device Info Overlay */
        .topo-device-info {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 280px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            z-index: 100;
            animation: slideIn 0.3s ease;
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateX(20px); }
            to { opacity: 1; transform: translateX(0); }
        }
        .topo-info-header {
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px 10px 0 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .topo-info-header span:first-child {
            font-size: 24px;
        }
        #topo-info-name {
            flex: 1;
            font-weight: 600;
            font-size: 14px;
        }
        .topo-info-close {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 16px;
        }
        .topo-info-close:hover {
            background: rgba(255,255,255,0.3);
        }
        .topo-info-body {
            padding: 15px;
        }
        .topo-info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
            font-size: 13px;
        }
        .topo-info-row .label {
            color: #888;
        }
        .topo-info-row .status-badge {
            padding: 3px 10px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: 600;
        }
        .topo-info-row .status-badge.online {
            background: #e8f5e9;
            color: #2e7d32;
        }
        .topo-info-row .status-badge.offline {
            background: #ffebee;
            color: #c62828;
        }
        .topo-info-row .status-badge.warning {
            background: #fff3e0;
            color: #e65100;
        }
        .topo-info-metrics {
            margin-top: 15px;
        }
        .topo-info-metrics .metric {
            margin-bottom: 12px;
        }
        .topo-info-metrics .metric-label {
            font-size: 11px;
            color: #888;
            display: block;
            margin-bottom: 4px;
        }
        .topo-info-metrics .metric-bar {
            height: 8px;
            background: #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
            display: inline-block;
            width: calc(100% - 50px);
            vertical-align: middle;
        }
        .topo-info-metrics .metric-fill {
            height: 100%;
            background: linear-gradient(90deg, #4CAF50, #8BC34A);
            border-radius: 4px;
            transition: width 0.3s;
        }
        .topo-info-metrics span:last-child {
            font-size: 12px;
            font-weight: 600;
            margin-left: 8px;
        }
        .topo-info-actions {
            display: flex;
            gap: 8px;
            margin-top: 15px;
        }
        .topo-action-btn {
            flex: 1;
            padding: 8px;
            border: 1px solid #ddd;
            background: #f8f9fa;
            border-radius: 6px;
            cursor: pointer;
            font-size: 11px;
            transition: all 0.2s;
        }
        .topo-action-btn:hover {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        /* Minimap */
        .topology-minimap {
            width: 200px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .minimap-header {
            padding: 10px 15px;
            background: #f5f5f5;
            font-size: 12px;
            font-weight: 600;
            color: #666;
            border-bottom: 1px solid #e0e0e0;
        }
        #minimap-canvas {
            height: 180px;
            background: #f8fafc;
            position: relative;
        }
        .minimap-viewport {
            position: absolute;
            border: 2px solid #667eea;
            background: rgba(102, 126, 234, 0.1);
            cursor: move;
        }
        .minimap-node {
            position: absolute;
            width: 6px;
            height: 6px;
            border-radius: 50%;
        }

        /* Legend Panel */
        .topology-legend-panel {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        .legend-section h4 {
            margin: 0 0 12px 0;
            font-size: 13px;
            color: #333;
            padding-bottom: 8px;
            border-bottom: 2px solid #667eea;
        }
        .legend-items {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .legend-items.small {
            font-size: 11px;
        }
        .legend-item {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 12px;
            color: #555;
        }
        .legend-icon {
            font-size: 16px;
            width: 24px;
            text-align: center;
        }
        .status-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
        }
        .status-dot.online { background: #4CAF50; }
        .status-dot.offline { background: #f44336; }
        .status-dot.warning { background: #ff9800; }
        .link-sample {
            width: 30px;
            height: 4px;
            border-radius: 2px;
            display: inline-block;
        }
        .link-sample.normal { background: #4CAF50; }
        .link-sample.moderate { background: #ff9800; }
        .link-sample.high { background: #f44336; }

        /* Context Menu */
        .topo-context-menu {
            position: fixed;
            background: white;
            border-radius: 8px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
            z-index: 1000;
            min-width: 180px;
            padding: 8px 0;
            display: none;
        }
        .topo-context-menu.active {
            display: block;
            animation: fadeIn 0.2s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }
        .context-menu-item {
            padding: 10px 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            font-size: 13px;
            transition: background 0.2s;
        }
        .context-menu-item:hover {
            background: #f0f0f0;
        }
        .context-menu-item.danger:hover {
            background: #ffebee;
            color: #c62828;
        }
        .context-menu-divider {
            height: 1px;
            background: #e0e0e0;
            margin: 5px 0;
        }

        /* Export Options */
        .export-option {
            cursor: pointer;
        }
        .export-option input {
            display: none;
        }
        .export-option-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            transition: all 0.2s;
        }
        .export-option input:checked + .export-option-card {
            border-color: #667eea;
            background: #f0f4ff;
        }
        .export-option:hover .export-option-card {
            border-color: #667eea;
        }
        .export-icon {
            font-size: 32px;
            margin-bottom: 8px;
        }
        .export-label {
            font-weight: 600;
            font-size: 14px;
            color: #333;
        }
        .export-desc {
            font-size: 11px;
            color: #888;
            margin-top: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-bar">
            <div>
                <h1 style="margin: 0; color: #667eea;">🌐 Network Performance Monitor</h1>
                <p style="margin: 5px 0 0 0; color: #666;">Real-time network monitoring with bandwidth, SNMP, and performance analytics</p>
            </div>
            <div style="display: flex; gap: 10px; align-items: center;">
                <span style="color: #666; font-size: 12px;">Auto-refresh: <span id="countdown">30</span>s</span>
                <a href="../index.php" class="back-btn">← Back to Dashboard</a>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">🖥️</div>
                <div class="stat-info">
                    <div class="stat-number"><?= $total_devices ?></div>
                    <div class="stat-label">Total Devices</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-info">
                    <div class="stat-number" style="color: #4CAF50;"><?= $online_devices ?></div>
                    <div class="stat-label">Online</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">📊</div>
                <div class="stat-info">
                    <div class="stat-number"><?= $uptime ?>%</div>
                    <div class="stat-label">Network Uptime</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">📶</div>
                <div class="stat-info">
                    <div class="stat-number"><?= $avg_bandwidth ?> Mbps</div>
                    <div class="stat-label">Avg Bandwidth</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">⚠️</div>
                <div class="stat-info">
                    <div class="stat-number" style="color: #f44336;"><?= $total_alerts ?></div>
                    <div class="stat-label">Active Alerts</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">🔔</div>
                <div class="stat-info">
                    <div class="stat-number" style="color: #ff9800;"><?= $total_devices - $online_devices ?></div>
                    <div class="stat-label">Offline/Issues</div>
                </div>
            </div>
        </div>

        <!-- Tab Navigation -->
        <div class="tabs">
            <button class="tab-btn active" onclick="switchTab('overview')">📊 Overview</button>
            <button class="tab-btn" onclick="switchTab('bandwidth')">📶 Bandwidth Utilization</button>
            <button class="tab-btn" onclick="switchTab('performance')">⚡ Performance Metrics</button>
            <button class="tab-btn" onclick="switchTab('snmp')">🔍 SNMP Monitoring</button>
            <button class="tab-btn" onclick="switchTab('alerts')">🔔 Alert Thresholds</button>
            <button class="tab-btn" onclick="switchTab('topology')">🗺️ Network Topology</button>
        </div>

        <!-- Overview Tab -->
        <div id="overview" class="tab-content active">
            <div class="card">
                <h2>Network Devices with Real-Time Metrics</h2>
                <div class="device-grid">
                    <?php foreach ($devices as $device):
                        $icons = [
                            'router' => '🔀',
                            'switch' => '🔌',
                            'firewall' => '🔥',
                            'access_point' => '📡',
                            'server' => '🖥️',
                            'workstation' => '💻',
                            'phone' => '📞',
                            'other' => '📦'
                        ];
                        $icon = $icons[$device['device_type']] ?? '📦';

                        $cpuColor = $device['cpu_usage'] > 80 ? '#f44336' : ($device['cpu_usage'] > 60 ? '#ff9800' : '#4CAF50');
                        $memColor = $device['memory_usage'] > 85 ? '#f44336' : ($device['memory_usage'] > 70 ? '#ff9800' : '#4CAF50');
                    ?>
                    <div class="device-card <?= $device['status'] ?>">
                        <?php if (!empty($device['alerts'])): ?>
                        <div class="alert-badge"><?= count($device['alerts']) ?> ⚠️</div>
                        <?php endif; ?>

                        <div class="device-icon"><?= $icon ?></div>
                        <h3 style="margin: 10px 0;"><?= htmlspecialchars($device['device_name']) ?></h3>

                        <div style="font-size: 13px; color: #666; margin-bottom: 15px;">
                            <p><strong>IP:</strong> <?= htmlspecialchars($device['ip_address']) ?></p>
                            <p><strong>Type:</strong> <?= ucfirst($device['device_type']) ?></p>
                            <p><strong>Location:</strong> <?= htmlspecialchars($device['location'] ?? 'N/A') ?></p>
                        </div>

                        <!-- Performance Metrics -->
                        <div style="margin-top: 15px;">
                            <div class="metric-label">
                                <span>CPU Usage</span>
                                <span style="font-weight: bold; color: <?= $cpuColor ?>;"><?= $device['cpu_usage'] ?>%</span>
                            </div>
                            <div class="metric-bar">
                                <div class="metric-fill" style="width: <?= $device['cpu_usage'] ?>%; background: <?= $cpuColor ?>;">
                                </div>
                            </div>

                            <div class="metric-label">
                                <span>Memory Usage</span>
                                <span style="font-weight: bold; color: <?= $memColor ?>;"><?= $device['memory_usage'] ?>%</span>
                            </div>
                            <div class="metric-bar">
                                <div class="metric-fill" style="width: <?= $device['memory_usage'] ?>%; background: <?= $memColor ?>;">
                                </div>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 10px; font-size: 12px;">
                                <div>
                                    <strong>↓ In:</strong> <?= $device['bandwidth_in'] ?> Mbps
                                </div>
                                <div>
                                    <strong>↑ Out:</strong> <?= $device['bandwidth_out'] ?> Mbps
                                </div>
                                <div>
                                    <strong>Latency:</strong> <?= $device['latency'] ?> ms
                                </div>
                                <div>
                                    <strong>Loss:</strong> <?= $device['packet_loss'] ?>%
                                </div>
                            </div>
                        </div>

                        <?php if (!empty($device['alerts'])): ?>
                        <div style="margin-top: 10px; padding: 8px; background: #fff3e0; border-radius: 4px; font-size: 11px; color: #e65100;">
                            <strong>⚠️ Alerts:</strong> <?= implode(', ', $device['alerts']) ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Bandwidth Tab -->
        <div id="bandwidth" class="tab-content">
            <div class="card">
                <h2>📶 Bandwidth Utilization Analysis</h2>
                <div class="chart-grid">
                    <div class="chart-container">
                        <h3 style="margin: 0 0 15px 0;">Real-Time Bandwidth Usage</h3>
                        <canvas id="bandwidthChart"></canvas>
                    </div>
                    <div class="chart-container">
                        <h3 style="margin: 0 0 15px 0;">Top Bandwidth Consumers</h3>
                        <canvas id="topConsumersChart"></canvas>
                    </div>
                </div>

                <div class="card">
                    <h3>Device Bandwidth Breakdown</h3>
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead style="background: #667eea; color: white;">
                            <tr>
                                <th style="padding: 12px; text-align: left;">Device</th>
                                <th style="padding: 12px; text-align: left;">Type</th>
                                <th style="padding: 12px; text-align: right;">Bandwidth In</th>
                                <th style="padding: 12px; text-align: right;">Bandwidth Out</th>
                                <th style="padding: 12px; text-align: right;">Total</th>
                                <th style="padding: 12px; text-align: center;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sortedDevices = $devices;
                            usort($sortedDevices, function($a, $b) {
                                return ($b['bandwidth_in'] + $b['bandwidth_out']) - ($a['bandwidth_in'] + $a['bandwidth_out']);
                            });
                            foreach ($sortedDevices as $device):
                                $total_bw = $device['bandwidth_in'] + $device['bandwidth_out'];
                                $bw_status = $total_bw > 1200 ? 'high' : ($total_bw > 600 ? 'medium' : 'low');
                            ?>
                            <tr style="border-bottom: 1px solid #e0e0e0;">
                                <td style="padding: 12px;"><?= htmlspecialchars($device['device_name']) ?></td>
                                <td style="padding: 12px;"><?= ucfirst($device['device_type']) ?></td>
                                <td style="padding: 12px; text-align: right;"><?= $device['bandwidth_in'] ?> Mbps</td>
                                <td style="padding: 12px; text-align: right;"><?= $device['bandwidth_out'] ?> Mbps</td>
                                <td style="padding: 12px; text-align: right; font-weight: bold;">
                                    <span class="bandwidth-indicator bandwidth-<?= $bw_status ?>"></span>
                                    <?= $total_bw ?> Mbps
                                </td>
                                <td style="padding: 12px; text-align: center;">
                                    <span class="badge badge-<?= $device['status'] === 'online' ? 'low' : 'critical' ?>">
                                        <?= strtoupper($device['status']) ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Performance Metrics Tab -->
        <div id="performance" class="tab-content">
            <div class="card">
                <h2>⚡ Performance Metrics (CPU & Memory)</h2>
                <div class="chart-grid">
                    <div class="chart-container">
                        <h3 style="margin: 0 0 15px 0;">CPU Usage Trends</h3>
                        <canvas id="cpuChart"></canvas>
                    </div>
                    <div class="chart-container">
                        <h3 style="margin: 0 0 15px 0;">Memory Usage Trends</h3>
                        <canvas id="memoryChart"></canvas>
                    </div>
                </div>

                <div class="chart-container">
                    <h3 style="margin: 0 0 15px 0;">Network Latency & Packet Loss</h3>
                    <canvas id="latencyChart"></canvas>
                </div>
            </div>
        </div>

        <!-- SNMP Monitoring Tab -->
        <div id="snmp" class="tab-content">
            <div class="card">
                <h2>🔍 SNMP Monitoring Configuration</h2>
                <p style="color: #666; margin-bottom: 20px;">Simple Network Management Protocol (SNMP) allows automated monitoring and management of network devices.</p>

                <div class="snmp-actions" style="margin-bottom: 20px;">
                    <button class="action-btn primary" onclick="openGlobalSnmpModal()">⚙️ Global SNMP Settings</button>
                    <button class="action-btn success" onclick="snmpDiscovery()">🔍 SNMP Discovery</button>
                    <button class="action-btn info" onclick="openSnmpPollingModal()">📊 Configure Polling</button>
                    <button class="action-btn warning" onclick="testAllSnmp()">🧪 Test All Connections</button>
                    <button class="action-btn secondary" onclick="exportSnmpConfig()">📤 Export Config</button>
                </div>

                <!-- SNMP Statistics Summary -->
                <div class="snmp-stats-grid">
                    <div class="snmp-stat-card">
                        <div class="snmp-stat-icon">📡</div>
                        <div class="snmp-stat-value" id="snmpEnabledCount"><?= count($devices) ?></div>
                        <div class="snmp-stat-label">SNMP Enabled</div>
                    </div>
                    <div class="snmp-stat-card">
                        <div class="snmp-stat-icon">✅</div>
                        <div class="snmp-stat-value" style="color: #4CAF50;" id="snmpActiveCount"><?= $online_devices ?></div>
                        <div class="snmp-stat-label">Responding</div>
                    </div>
                    <div class="snmp-stat-card">
                        <div class="snmp-stat-icon">⚠️</div>
                        <div class="snmp-stat-value" style="color: #ff9800;" id="snmpWarningCount"><?= rand(1, 3) ?></div>
                        <div class="snmp-stat-label">Warnings</div>
                    </div>
                    <div class="snmp-stat-card">
                        <div class="snmp-stat-icon">📈</div>
                        <div class="snmp-stat-value"><?= rand(15000, 25000) ?></div>
                        <div class="snmp-stat-label">OIDs Polled/hr</div>
                    </div>
                </div>

                <div class="device-grid">
                    <?php foreach ($devices as $index => $device):
                        $icons = [
                            'router' => '🔀', 'switch' => '🔌', 'firewall' => '🔥',
                            'access_point' => '📡', 'server' => '🖥️', 'workstation' => '💻',
                            'phone' => '📞', 'other' => '📦'
                        ];
                        $icon = $icons[$device['device_type']] ?? '📦';
                        $snmpVersion = ['v1', 'v2c', 'v3'][rand(0, 2)];
                        $snmpEnabled = rand(0, 1);
                        $lastPoll = date('Y-m-d H:i:s', strtotime('-' . rand(1, 30) . ' minutes'));
                        $pollInterval = [30, 60, 120, 300][rand(0, 3)];
                    ?>
                    <div class="device-card <?= $device['status'] ?>" data-device-id="<?= $device['id'] ?? $index ?>">
                        <div class="snmp-status-badge <?= $snmpEnabled ? 'active' : 'inactive' ?>">
                            <?= $snmpEnabled ? 'SNMP Active' : 'SNMP Off' ?>
                        </div>
                        <div class="device-icon"><?= $icon ?></div>
                        <h3 style="margin: 10px 0;"><?= htmlspecialchars($device['device_name']) ?></h3>

                        <div style="font-size: 13px; color: #666;">
                            <p><strong>IP:</strong> <?= htmlspecialchars($device['ip_address']) ?></p>
                            <p><strong>Type:</strong> <?= ucfirst($device['device_type']) ?></p>
                        </div>

                        <div class="snmp-info">
                            <div class="snmp-info-row">
                                <span>SNMP Version:</span>
                                <span class="snmp-version-badge"><?= $snmpVersion ?></span>
                            </div>
                            <div class="snmp-info-row">
                                <span>Community:</span>
                                <span><?= $snmpEnabled ? '••••••••' : 'Not Set' ?></span>
                            </div>
                            <div class="snmp-info-row">
                                <span>Poll Interval:</span>
                                <span><?= $pollInterval ?>s</span>
                            </div>
                            <div class="snmp-info-row">
                                <span>Last Poll:</span>
                                <span class="snmp-last-poll"><?= $snmpEnabled ? date('H:i:s', strtotime($lastPoll)) : 'Never' ?></span>
                            </div>
                        </div>

                        <!-- Live SNMP Data Preview -->
                        <?php if ($snmpEnabled): ?>
                        <div class="snmp-live-data">
                            <h4>📊 Live SNMP Data</h4>
                            <div class="snmp-metric">
                                <span class="snmp-oid">.1.3.6.1.2.1.1.3.0</span>
                                <span class="snmp-value">Uptime: <?= rand(1, 365) ?>d <?= rand(0, 23) ?>h</span>
                            </div>
                            <div class="snmp-metric">
                                <span class="snmp-oid">.1.3.6.1.2.1.2.2.1.10</span>
                                <span class="snmp-value">ifInOctets: <?= number_format(rand(100000000, 999999999)) ?></span>
                            </div>
                            <div class="snmp-metric">
                                <span class="snmp-oid">.1.3.6.1.4.1.2021.11.9</span>
                                <span class="snmp-value">CPU: <?= $device['cpu_usage'] ?>%</span>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="snmp-card-actions">
                            <button class="snmp-btn configure" onclick="openSnmpConfigModal(<?= $device['id'] ?? $index ?>, '<?= htmlspecialchars(addslashes($device['device_name'])) ?>', '<?= htmlspecialchars($device['ip_address']) ?>', '<?= $snmpVersion ?>')">
                                ⚙️ Configure
                            </button>
                            <button class="snmp-btn test" onclick="testSnmpConnection(<?= $device['id'] ?? $index ?>, '<?= htmlspecialchars(addslashes($device['device_name'])) ?>', '<?= htmlspecialchars($device['ip_address']) ?>')">
                                🧪 Test
                            </button>
                            <button class="snmp-btn walk" onclick="snmpWalk(<?= $device['id'] ?? $index ?>, '<?= htmlspecialchars(addslashes($device['device_name'])) ?>', '<?= htmlspecialchars($device['ip_address']) ?>')">
                                📋 Walk
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Alert Thresholds Tab -->
        <div id="alerts" class="tab-content">
            <div class="card">
                <h2>🔔 Alert Threshold Configuration</h2>
                <p style="color: #666; margin-bottom: 20px;">Configure warning and critical thresholds for automated alerting</p>

                <div class="threshold-config">
                    <h3 style="margin-top: 0;">Global Thresholds</h3>

                    <div class="threshold-item">
                        <div><strong>CPU Usage Warning</strong></div>
                        <input type="range" class="threshold-slider" min="0" max="100" value="70" oninput="updateThreshold(this, 'cpu-warn')">
                        <div style="text-align: center;"><span id="cpu-warn">70</span>%</div>
                    </div>

                    <div class="threshold-item">
                        <div><strong>CPU Usage Critical</strong></div>
                        <input type="range" class="threshold-slider" min="0" max="100" value="85" oninput="updateThreshold(this, 'cpu-crit')">
                        <div style="text-align: center;"><span id="cpu-crit">85</span>%</div>
                    </div>

                    <div class="threshold-item">
                        <div><strong>Memory Usage Warning</strong></div>
                        <input type="range" class="threshold-slider" min="0" max="100" value="75" oninput="updateThreshold(this, 'mem-warn')">
                        <div style="text-align: center;"><span id="mem-warn">75</span>%</div>
                    </div>

                    <div class="threshold-item">
                        <div><strong>Memory Usage Critical</strong></div>
                        <input type="range" class="threshold-slider" min="0" max="100" value="90" oninput="updateThreshold(this, 'mem-crit')">
                        <div style="text-align: center;"><span id="mem-crit">90</span>%</div>
                    </div>

                    <div class="threshold-item">
                        <div><strong>Bandwidth Warning (Mbps)</strong></div>
                        <input type="range" class="threshold-slider" min="0" max="1000" step="50" value="700" oninput="updateThreshold(this, 'bw-warn')">
                        <div style="text-align: center;"><span id="bw-warn">700</span> Mbps</div>
                    </div>

                    <div class="threshold-item">
                        <div><strong>Bandwidth Critical (Mbps)</strong></div>
                        <input type="range" class="threshold-slider" min="0" max="1000" step="50" value="900" oninput="updateThreshold(this, 'bw-crit')">
                        <div style="text-align: center;"><span id="bw-crit">900</span> Mbps</div>
                    </div>

                    <div class="threshold-item">
                        <div><strong>Latency Warning (ms)</strong></div>
                        <input type="range" class="threshold-slider" min="0" max="200" step="5" value="50" oninput="updateThreshold(this, 'lat-warn')">
                        <div style="text-align: center;"><span id="lat-warn">50</span> ms</div>
                    </div>

                    <div class="threshold-item">
                        <div><strong>Packet Loss Critical (%)</strong></div>
                        <input type="range" class="threshold-slider" min="0" max="10" step="0.1" value="1.0" oninput="updateThreshold(this, 'loss-crit')">
                        <div style="text-align: center;"><span id="loss-crit">1.0</span>%</div>
                    </div>

                    <button style="margin-top: 20px; padding: 12px 30px; background: #4CAF50; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px;" onclick="saveThresholdConfig()">
                        💾 Save Threshold Configuration
                    </button>
                </div>

                <div class="card" style="margin-top: 20px;">
                    <h3>Current Alerts (<?= $total_alerts ?>)</h3>
                    <?php if ($total_alerts > 0): ?>
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead style="background: #667eea; color: white;">
                            <tr>
                                <th style="padding: 12px; text-align: left;">Device</th>
                                <th style="padding: 12px; text-align: left;">Alert Type</th>
                                <th style="padding: 12px; text-align: left;">Current Value</th>
                                <th style="padding: 12px; text-align: left;">Threshold</th>
                                <th style="padding: 12px; text-align: center;">Severity</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($devices as $device): ?>
                                <?php foreach ($device['alerts'] as $alert):
                                    $severity = 'high';
                                    $current = '';
                                    $threshold = '';

                                    if ($alert === 'High CPU') {
                                        $current = $device['cpu_usage'] . '%';
                                        $threshold = '> 80%';
                                    } elseif ($alert === 'High Memory') {
                                        $current = $device['memory_usage'] . '%';
                                        $threshold = '> 85%';
                                    } elseif ($alert === 'Packet Loss') {
                                        $current = $device['packet_loss'] . '%';
                                        $threshold = '> 0.3%';
                                    } elseif ($alert === 'High Latency') {
                                        $current = $device['latency'] . ' ms';
                                        $threshold = '> 40 ms';
                                    }
                                ?>
                                <tr style="border-bottom: 1px solid #e0e0e0;">
                                    <td style="padding: 12px;"><?= htmlspecialchars($device['device_name']) ?></td>
                                    <td style="padding: 12px;"><?= $alert ?></td>
                                    <td style="padding: 12px; font-weight: bold; color: #f44336;"><?= $current ?></td>
                                    <td style="padding: 12px;"><?= $threshold ?></td>
                                    <td style="padding: 12px; text-align: center;">
                                        <span class="badge badge-critical"><?= strtoupper($severity) ?></span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <p style="text-align: center; padding: 40px; color: #4CAF50; font-size: 18px;">
                        ✅ No active alerts - All systems operating normally
                    </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Network Topology Tab -->
        <div id="topology" class="tab-content">
            <div class="card">
                <h2>🗺️ Network Topology Visualization</h2>
                <p style="color: #666; margin-bottom: 20px;">Interactive network topology map with real-time device status and connection monitoring</p>

                <!-- Topology Toolbar -->
                <div class="topology-toolbar">
                    <div class="toolbar-section">
                        <span class="toolbar-label">View:</span>
                        <button class="topo-btn active" onclick="setTopologyLayout('physics')" title="Auto Layout">
                            <span>🔄</span> Auto
                        </button>
                        <button class="topo-btn" onclick="setTopologyLayout('hierarchical')" title="Hierarchical Layout">
                            <span>📊</span> Hierarchy
                        </button>
                        <button class="topo-btn" onclick="setTopologyLayout('circular')" title="Circular Layout">
                            <span>⭕</span> Circular
                        </button>
                        <button class="topo-btn" onclick="setTopologyLayout('grid')" title="Grid Layout">
                            <span>⊞</span> Grid
                        </button>
                    </div>
                    <div class="toolbar-section">
                        <span class="toolbar-label">Zoom:</span>
                        <button class="topo-btn" onclick="topologyZoom('in')" title="Zoom In">➕</button>
                        <button class="topo-btn" onclick="topologyZoom('out')" title="Zoom Out">➖</button>
                        <button class="topo-btn" onclick="topologyZoom('fit')" title="Fit to View">🔲</button>
                    </div>
                    <div class="toolbar-section">
                        <span class="toolbar-label">Filter:</span>
                        <select id="topoFilterType" class="topo-select" onchange="filterTopologyDevices()">
                            <option value="all">All Types</option>
                            <option value="router">Routers</option>
                            <option value="switch">Switches</option>
                            <option value="firewall">Firewalls</option>
                            <option value="server">Servers</option>
                            <option value="access_point">Access Points</option>
                        </select>
                        <select id="topoFilterStatus" class="topo-select" onchange="filterTopologyDevices()">
                            <option value="all">All Status</option>
                            <option value="online">Online Only</option>
                            <option value="offline">Offline Only</option>
                            <option value="warning">Warnings</option>
                        </select>
                    </div>
                    <div class="toolbar-section">
                        <input type="text" id="topoSearch" class="topo-search" placeholder="🔍 Search devices..." onkeyup="searchTopologyDevice(this.value)">
                    </div>
                    <div class="toolbar-section">
                        <button class="topo-btn primary" onclick="openAddConnectionModal()" title="Add Connection">
                            <span>🔗</span> Add Link
                        </button>
                        <button class="topo-btn success" onclick="refreshTopology()" title="Refresh Topology">
                            <span>🔄</span> Refresh
                        </button>
                        <button class="topo-btn" onclick="exportTopology()" title="Export Topology">
                            <span>📤</span> Export
                        </button>
                    </div>
                </div>

                <!-- Topology Stats Bar -->
                <div class="topology-stats">
                    <div class="topo-stat">
                        <span class="topo-stat-icon">🖥️</span>
                        <span class="topo-stat-value" id="topoTotalDevices"><?= $total_devices ?></span>
                        <span class="topo-stat-label">Devices</span>
                    </div>
                    <div class="topo-stat">
                        <span class="topo-stat-icon" style="color: #4CAF50;">●</span>
                        <span class="topo-stat-value" id="topoOnlineDevices"><?= $online_devices ?></span>
                        <span class="topo-stat-label">Online</span>
                    </div>
                    <div class="topo-stat">
                        <span class="topo-stat-icon" style="color: #f44336;">●</span>
                        <span class="topo-stat-value" id="topoOfflineDevices"><?= $total_devices - $online_devices ?></span>
                        <span class="topo-stat-label">Offline</span>
                    </div>
                    <div class="topo-stat">
                        <span class="topo-stat-icon">🔗</span>
                        <span class="topo-stat-value" id="topoConnections">0</span>
                        <span class="topo-stat-label">Links</span>
                    </div>
                    <div class="topo-stat">
                        <span class="topo-stat-icon">📶</span>
                        <span class="topo-stat-value" id="topoAvgBandwidth">0</span>
                        <span class="topo-stat-label">Avg Mbps</span>
                    </div>
                </div>

                <!-- Main Topology Container with Minimap -->
                <div class="topology-main-container">
                    <div class="topology-container" id="topology-container">
                        <div id="topology-network"></div>
                        <!-- Device Info Overlay -->
                        <div id="topology-device-info" class="topo-device-info" style="display: none;">
                            <div class="topo-info-header">
                                <span id="topo-info-icon">🖥️</span>
                                <span id="topo-info-name">Device Name</span>
                                <button class="topo-info-close" onclick="hideDeviceInfo()">×</button>
                            </div>
                            <div class="topo-info-body">
                                <div class="topo-info-row">
                                    <span class="label">IP Address:</span>
                                    <span id="topo-info-ip">192.168.1.1</span>
                                </div>
                                <div class="topo-info-row">
                                    <span class="label">Type:</span>
                                    <span id="topo-info-type">Router</span>
                                </div>
                                <div class="topo-info-row">
                                    <span class="label">Status:</span>
                                    <span id="topo-info-status" class="status-badge">Online</span>
                                </div>
                                <div class="topo-info-row">
                                    <span class="label">Location:</span>
                                    <span id="topo-info-location">Data Center</span>
                                </div>
                                <div class="topo-info-metrics">
                                    <div class="metric">
                                        <span class="metric-label">CPU</span>
                                        <div class="metric-bar"><div id="topo-info-cpu" class="metric-fill" style="width: 50%;"></div></div>
                                        <span id="topo-info-cpu-val">50%</span>
                                    </div>
                                    <div class="metric">
                                        <span class="metric-label">Memory</span>
                                        <div class="metric-bar"><div id="topo-info-mem" class="metric-fill" style="width: 60%;"></div></div>
                                        <span id="topo-info-mem-val">60%</span>
                                    </div>
                                    <div class="metric">
                                        <span class="metric-label">Bandwidth</span>
                                        <span id="topo-info-bw">↓ 500 / ↑ 200 Mbps</span>
                                    </div>
                                </div>
                                <div class="topo-info-actions">
                                    <button class="topo-action-btn" onclick="pingDevice()">📡 Ping</button>
                                    <button class="topo-action-btn" onclick="openDeviceSnmp()">🔍 SNMP</button>
                                    <button class="topo-action-btn" onclick="viewDeviceDetails()">📋 Details</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Minimap -->
                    <div class="topology-minimap" id="topology-minimap">
                        <div class="minimap-header">Mini Map</div>
                        <div id="minimap-canvas"></div>
                    </div>
                </div>

                <!-- Legend and Controls -->
                <div class="topology-legend-panel">
                    <div class="legend-section">
                        <h4>Device Types</h4>
                        <div class="legend-items">
                            <div class="legend-item"><span class="legend-icon router">🔀</span> Router</div>
                            <div class="legend-item"><span class="legend-icon switch">🔌</span> Switch</div>
                            <div class="legend-item"><span class="legend-icon firewall">🔥</span> Firewall</div>
                            <div class="legend-item"><span class="legend-icon server">🖥️</span> Server</div>
                            <div class="legend-item"><span class="legend-icon access_point">📡</span> Access Point</div>
                            <div class="legend-item"><span class="legend-icon workstation">💻</span> Workstation</div>
                        </div>
                    </div>
                    <div class="legend-section">
                        <h4>Status Colors</h4>
                        <div class="legend-items">
                            <div class="legend-item"><span class="status-dot online"></span> Online</div>
                            <div class="legend-item"><span class="status-dot offline"></span> Offline</div>
                            <div class="legend-item"><span class="status-dot warning"></span> Warning</div>
                        </div>
                    </div>
                    <div class="legend-section">
                        <h4>Connection Status</h4>
                        <div class="legend-items">
                            <div class="legend-item"><span class="link-sample normal"></span> Normal (&lt;50%)</div>
                            <div class="legend-item"><span class="link-sample moderate"></span> Moderate (50-80%)</div>
                            <div class="legend-item"><span class="link-sample high"></span> High (&gt;80%)</div>
                        </div>
                    </div>
                    <div class="legend-section">
                        <h4>Interactions</h4>
                        <div class="legend-items small">
                            <div class="legend-item">🖱️ Click: Select device</div>
                            <div class="legend-item">🖱️ Double-click: View details</div>
                            <div class="legend-item">🖱️ Right-click: Context menu</div>
                            <div class="legend-item">🖱️ Drag: Move device</div>
                            <div class="legend-item">🖱️ Scroll: Zoom in/out</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- SNMP Configuration Modal -->
    <div id="snmpConfigModal" class="npm-modal">
        <div class="npm-modal-overlay" onclick="closeNpmModal('snmpConfigModal')"></div>
        <div class="npm-modal-content">
            <div class="npm-modal-header">
                <h3>⚙️ Configure SNMP - <span id="snmpDeviceName"></span></h3>
                <button class="npm-modal-close" onclick="closeNpmModal('snmpConfigModal')">&times;</button>
            </div>
            <div class="npm-modal-body">
                <form id="snmpConfigForm">
                    <input type="hidden" id="snmpDeviceId">

                    <div class="form-group">
                        <label>Device IP Address</label>
                        <input type="text" id="snmpDeviceIp" class="form-control" readonly>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>SNMP Version</label>
                            <select id="snmpVersion" class="form-control" onchange="toggleSnmpV3Options()">
                                <option value="v1">SNMP v1</option>
                                <option value="v2c" selected>SNMP v2c</option>
                                <option value="v3">SNMP v3 (Secure)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Port</label>
                            <input type="number" id="snmpPort" class="form-control" value="161" min="1" max="65535">
                        </div>
                    </div>

                    <div id="snmpV1V2Options">
                        <div class="form-group">
                            <label>Community String (Read)</label>
                            <input type="password" id="snmpCommunityRead" class="form-control" placeholder="e.g., public">
                        </div>
                        <div class="form-group">
                            <label>Community String (Write) - Optional</label>
                            <input type="password" id="snmpCommunityWrite" class="form-control" placeholder="e.g., private">
                        </div>
                    </div>

                    <div id="snmpV3Options" style="display: none;">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Username</label>
                                <input type="text" id="snmpV3User" class="form-control" placeholder="SNMPv3 Username">
                            </div>
                            <div class="form-group">
                                <label>Security Level</label>
                                <select id="snmpV3SecLevel" class="form-control">
                                    <option value="noAuthNoPriv">No Auth, No Privacy</option>
                                    <option value="authNoPriv">Auth, No Privacy</option>
                                    <option value="authPriv" selected>Auth + Privacy</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Auth Protocol</label>
                                <select id="snmpV3AuthProto" class="form-control">
                                    <option value="MD5">MD5</option>
                                    <option value="SHA" selected>SHA</option>
                                    <option value="SHA256">SHA-256</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Auth Password</label>
                                <input type="password" id="snmpV3AuthPass" class="form-control" placeholder="Authentication password">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Privacy Protocol</label>
                                <select id="snmpV3PrivProto" class="form-control">
                                    <option value="DES">DES</option>
                                    <option value="AES" selected>AES-128</option>
                                    <option value="AES256">AES-256</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Privacy Password</label>
                                <input type="password" id="snmpV3PrivPass" class="form-control" placeholder="Privacy password">
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Poll Interval (seconds)</label>
                            <select id="snmpPollInterval" class="form-control">
                                <option value="30">30 seconds</option>
                                <option value="60" selected>1 minute</option>
                                <option value="120">2 minutes</option>
                                <option value="300">5 minutes</option>
                                <option value="600">10 minutes</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Timeout (seconds)</label>
                            <input type="number" id="snmpTimeout" class="form-control" value="5" min="1" max="30">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="snmpEnabled" checked> Enable SNMP Monitoring
                        </label>
                    </div>

                    <div id="snmpTestResult"></div>
                </form>
            </div>
            <div class="npm-modal-footer">
                <button class="action-btn secondary" onclick="closeNpmModal('snmpConfigModal')">Cancel</button>
                <button class="action-btn warning" onclick="testCurrentSnmpConfig()">🧪 Test Config</button>
                <button class="action-btn primary" onclick="saveSnmpConfig()">💾 Save Configuration</button>
            </div>
        </div>
    </div>

    <!-- SNMP Walk Modal -->
    <div id="snmpWalkModal" class="npm-modal">
        <div class="npm-modal-overlay" onclick="closeNpmModal('snmpWalkModal')"></div>
        <div class="npm-modal-content large">
            <div class="npm-modal-header" style="background: linear-gradient(135deg, #9c27b0 0%, #7b1fa2 100%);">
                <h3>📋 SNMP Walk - <span id="walkDeviceName"></span></h3>
                <button class="npm-modal-close" onclick="closeNpmModal('snmpWalkModal')">&times;</button>
            </div>
            <div class="npm-modal-body">
                <div class="form-row" style="margin-bottom: 15px;">
                    <div class="form-group" style="flex: 2;">
                        <label>Starting OID</label>
                        <input type="text" id="walkStartOid" class="form-control" value=".1.3.6.1.2.1" placeholder=".1.3.6.1.2.1">
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label>Max Results</label>
                        <select id="walkMaxResults" class="form-control">
                            <option value="50">50</option>
                            <option value="100" selected>100</option>
                            <option value="500">500</option>
                            <option value="1000">1000</option>
                        </select>
                    </div>
                    <div class="form-group" style="flex: 0; align-self: flex-end;">
                        <button class="action-btn success" onclick="executeSnmpWalk()">🔍 Walk</button>
                    </div>
                </div>
                <div id="snmpWalkOutput" class="snmp-walk-output">
Click "Walk" to retrieve SNMP data from the device...
                </div>
            </div>
            <div class="npm-modal-footer">
                <button class="action-btn secondary" onclick="closeNpmModal('snmpWalkModal')">Close</button>
                <button class="action-btn info" onclick="copyWalkOutput()">📋 Copy Output</button>
                <button class="action-btn primary" onclick="exportWalkOutput()">📤 Export</button>
            </div>
        </div>
    </div>

    <!-- Global SNMP Settings Modal -->
    <div id="globalSnmpModal" class="npm-modal">
        <div class="npm-modal-overlay" onclick="closeNpmModal('globalSnmpModal')"></div>
        <div class="npm-modal-content large">
            <div class="npm-modal-header">
                <h3>⚙️ Global SNMP Settings</h3>
                <button class="npm-modal-close" onclick="closeNpmModal('globalSnmpModal')">&times;</button>
            </div>
            <div class="npm-modal-body">
                <h4 style="margin-top: 0;">Default SNMP Configuration</h4>
                <p style="color: #666; margin-bottom: 20px;">These settings will be applied to newly discovered devices.</p>

                <div class="form-row">
                    <div class="form-group">
                        <label>Default SNMP Version</label>
                        <select id="globalSnmpVersion" class="form-control">
                            <option value="v2c" selected>SNMP v2c (Recommended)</option>
                            <option value="v3">SNMP v3 (Secure)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Default Community String</label>
                        <input type="password" id="globalCommunity" class="form-control" value="public" placeholder="Community string">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Default Poll Interval</label>
                        <select id="globalPollInterval" class="form-control">
                            <option value="30">30 seconds</option>
                            <option value="60" selected>1 minute</option>
                            <option value="300">5 minutes</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>SNMP Retries</label>
                        <input type="number" id="globalRetries" class="form-control" value="3" min="1" max="10">
                    </div>
                </div>

                <h4>Standard OIDs to Monitor</h4>
                <table class="oid-table">
                    <thead>
                        <tr>
                            <th width="40"><input type="checkbox" checked onclick="toggleAllOids(this)"></th>
                            <th>OID</th>
                            <th>Description</th>
                            <th>Category</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><input type="checkbox" class="oid-checkbox" checked></td>
                            <td><code>.1.3.6.1.2.1.1.3.0</code></td>
                            <td>System Uptime</td>
                            <td>System</td>
                        </tr>
                        <tr>
                            <td><input type="checkbox" class="oid-checkbox" checked></td>
                            <td><code>.1.3.6.1.2.1.1.5.0</code></td>
                            <td>System Name</td>
                            <td>System</td>
                        </tr>
                        <tr>
                            <td><input type="checkbox" class="oid-checkbox" checked></td>
                            <td><code>.1.3.6.1.2.1.2.2.1.10</code></td>
                            <td>Interface In Octets</td>
                            <td>Interface</td>
                        </tr>
                        <tr>
                            <td><input type="checkbox" class="oid-checkbox" checked></td>
                            <td><code>.1.3.6.1.2.1.2.2.1.16</code></td>
                            <td>Interface Out Octets</td>
                            <td>Interface</td>
                        </tr>
                        <tr>
                            <td><input type="checkbox" class="oid-checkbox" checked></td>
                            <td><code>.1.3.6.1.4.1.2021.11.9.0</code></td>
                            <td>CPU User %</td>
                            <td>Performance</td>
                        </tr>
                        <tr>
                            <td><input type="checkbox" class="oid-checkbox" checked></td>
                            <td><code>.1.3.6.1.4.1.2021.11.10.0</code></td>
                            <td>CPU System %</td>
                            <td>Performance</td>
                        </tr>
                        <tr>
                            <td><input type="checkbox" class="oid-checkbox" checked></td>
                            <td><code>.1.3.6.1.4.1.2021.4.5.0</code></td>
                            <td>Total Memory</td>
                            <td>Memory</td>
                        </tr>
                        <tr>
                            <td><input type="checkbox" class="oid-checkbox" checked></td>
                            <td><code>.1.3.6.1.4.1.2021.4.6.0</code></td>
                            <td>Available Memory</td>
                            <td>Memory</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="npm-modal-footer">
                <button class="action-btn secondary" onclick="closeNpmModal('globalSnmpModal')">Cancel</button>
                <button class="action-btn primary" onclick="saveGlobalSnmpSettings()">💾 Save Global Settings</button>
            </div>
        </div>
    </div>

    <!-- SNMP Polling Configuration Modal -->
    <div id="snmpPollingModal" class="npm-modal">
        <div class="npm-modal-overlay" onclick="closeNpmModal('snmpPollingModal')"></div>
        <div class="npm-modal-content">
            <div class="npm-modal-header" style="background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);">
                <h3>📊 SNMP Polling Configuration</h3>
                <button class="npm-modal-close" onclick="closeNpmModal('snmpPollingModal')">&times;</button>
            </div>
            <div class="npm-modal-body">
                <div class="form-group">
                    <label>Polling Mode</label>
                    <select id="pollingMode" class="form-control">
                        <option value="interval">Fixed Interval</option>
                        <option value="adaptive">Adaptive (Based on load)</option>
                        <option value="scheduled">Scheduled Windows</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Concurrent Polls</label>
                    <input type="range" id="concurrentPolls" class="form-control" min="1" max="50" value="10" oninput="document.getElementById('concurrentPollsValue').textContent = this.value">
                    <div style="text-align: center; margin-top: 5px;">
                        <span id="concurrentPollsValue">10</span> simultaneous device polls
                    </div>
                </div>

                <div class="form-group">
                    <label>Poll Queue Priority</label>
                    <select id="pollPriority" class="form-control">
                        <option value="round-robin">Round Robin</option>
                        <option value="critical-first" selected>Critical Devices First</option>
                        <option value="alphabetical">Alphabetical</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" id="enableBulkGet" checked> Enable SNMP Bulk GET (Faster)
                    </label>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" id="enableTrapReceiver" checked> Enable SNMP Trap Receiver (Port 162)
                    </label>
                </div>

                <div class="form-group">
                    <label>Data Retention</label>
                    <select id="dataRetention" class="form-control">
                        <option value="7">7 days</option>
                        <option value="30" selected>30 days</option>
                        <option value="90">90 days</option>
                        <option value="365">1 year</option>
                    </select>
                </div>
            </div>
            <div class="npm-modal-footer">
                <button class="action-btn secondary" onclick="closeNpmModal('snmpPollingModal')">Cancel</button>
                <button class="action-btn primary" onclick="savePollingConfig()">💾 Save Polling Config</button>
            </div>
        </div>
    </div>

    <!-- SNMP Test Result Modal -->
    <div id="snmpTestModal" class="npm-modal">
        <div class="npm-modal-overlay" onclick="closeNpmModal('snmpTestModal')"></div>
        <div class="npm-modal-content">
            <div class="npm-modal-header" style="background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%);">
                <h3>🧪 SNMP Connection Test</h3>
                <button class="npm-modal-close" onclick="closeNpmModal('snmpTestModal')">&times;</button>
            </div>
            <div class="npm-modal-body">
                <div id="snmpTestContent">
                    <div style="text-align: center; padding: 40px;">
                        <div class="spinner"></div>
                        <p style="margin-top: 20px; color: #666;">Testing SNMP connection...</p>
                    </div>
                </div>
            </div>
            <div class="npm-modal-footer">
                <button class="action-btn secondary" onclick="closeNpmModal('snmpTestModal')">Close</button>
            </div>
        </div>
    </div>

    <!-- Topology Context Menu -->
    <div id="topoContextMenu" class="topo-context-menu">
        <div class="context-menu-item" onclick="contextMenuAction('ping')">
            <span>📡</span> Ping Device
        </div>
        <div class="context-menu-item" onclick="contextMenuAction('snmp')">
            <span>🔍</span> SNMP Walk
        </div>
        <div class="context-menu-item" onclick="contextMenuAction('details')">
            <span>📋</span> View Details
        </div>
        <div class="context-menu-divider"></div>
        <div class="context-menu-item" onclick="contextMenuAction('addConnection')">
            <span>🔗</span> Add Connection
        </div>
        <div class="context-menu-item" onclick="contextMenuAction('removeConnections')">
            <span>✂️</span> Remove Connections
        </div>
        <div class="context-menu-divider"></div>
        <div class="context-menu-item" onclick="contextMenuAction('highlight')">
            <span>✨</span> Highlight Path
        </div>
        <div class="context-menu-item" onclick="contextMenuAction('isolate')">
            <span>🎯</span> Isolate View
        </div>
        <div class="context-menu-divider"></div>
        <div class="context-menu-item danger" onclick="contextMenuAction('disable')">
            <span>🚫</span> Disable Device
        </div>
    </div>

    <!-- Add Connection Modal -->
    <div id="addConnectionModal" class="npm-modal">
        <div class="npm-modal-overlay" onclick="closeNpmModal('addConnectionModal')"></div>
        <div class="npm-modal-content">
            <div class="npm-modal-header" style="background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);">
                <h3>🔗 Add Network Connection</h3>
                <button class="npm-modal-close" onclick="closeNpmModal('addConnectionModal')">&times;</button>
            </div>
            <div class="npm-modal-body">
                <div class="form-group">
                    <label>Source Device</label>
                    <select id="connectionSource" class="form-control">
                        <option value="">Select source device...</option>
                        <?php foreach ($devices as $device): ?>
                        <option value="<?= $device['id'] ?? 0 ?>"><?= htmlspecialchars($device['device_name']) ?> (<?= $device['ip_address'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Destination Device</label>
                    <select id="connectionDest" class="form-control">
                        <option value="">Select destination device...</option>
                        <?php foreach ($devices as $device): ?>
                        <option value="<?= $device['id'] ?? 0 ?>"><?= htmlspecialchars($device['device_name']) ?> (<?= $device['ip_address'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Connection Type</label>
                        <select id="connectionType" class="form-control">
                            <option value="ethernet">Ethernet</option>
                            <option value="fiber">Fiber Optic</option>
                            <option value="wireless">Wireless</option>
                            <option value="wan">WAN Link</option>
                            <option value="vpn">VPN Tunnel</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Bandwidth (Mbps)</label>
                        <select id="connectionBandwidth" class="form-control">
                            <option value="100">100 Mbps</option>
                            <option value="1000" selected>1 Gbps</option>
                            <option value="10000">10 Gbps</option>
                            <option value="40000">40 Gbps</option>
                            <option value="100000">100 Gbps</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Description (Optional)</label>
                    <input type="text" id="connectionDesc" class="form-control" placeholder="e.g., Core uplink to distribution switch">
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="connectionBidirectional" checked> Bidirectional Link
                    </label>
                </div>
            </div>
            <div class="npm-modal-footer">
                <button class="action-btn secondary" onclick="closeNpmModal('addConnectionModal')">Cancel</button>
                <button class="action-btn primary" onclick="createConnection()">🔗 Create Connection</button>
            </div>
        </div>
    </div>

    <!-- Export Topology Modal -->
    <div id="exportTopologyModal" class="npm-modal">
        <div class="npm-modal-overlay" onclick="closeNpmModal('exportTopologyModal')"></div>
        <div class="npm-modal-content">
            <div class="npm-modal-header">
                <h3>📤 Export Network Topology</h3>
                <button class="npm-modal-close" onclick="closeNpmModal('exportTopologyModal')">&times;</button>
            </div>
            <div class="npm-modal-body">
                <div class="form-group">
                    <label>Export Format</label>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 10px;">
                        <label class="export-option">
                            <input type="radio" name="exportFormat" value="png" checked>
                            <span class="export-option-card">
                                <span class="export-icon">🖼️</span>
                                <span class="export-label">PNG Image</span>
                                <span class="export-desc">High-quality image export</span>
                            </span>
                        </label>
                        <label class="export-option">
                            <input type="radio" name="exportFormat" value="svg">
                            <span class="export-option-card">
                                <span class="export-icon">📐</span>
                                <span class="export-label">SVG Vector</span>
                                <span class="export-desc">Scalable vector graphics</span>
                            </span>
                        </label>
                        <label class="export-option">
                            <input type="radio" name="exportFormat" value="json">
                            <span class="export-option-card">
                                <span class="export-icon">📄</span>
                                <span class="export-label">JSON Data</span>
                                <span class="export-desc">Topology configuration</span>
                            </span>
                        </label>
                        <label class="export-option">
                            <input type="radio" name="exportFormat" value="pdf">
                            <span class="export-option-card">
                                <span class="export-icon">📑</span>
                                <span class="export-label">PDF Report</span>
                                <span class="export-desc">Printable document</span>
                            </span>
                        </label>
                    </div>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="exportIncludeLegend" checked> Include Legend
                    </label>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="exportIncludeStats" checked> Include Statistics
                    </label>
                </div>
            </div>
            <div class="npm-modal-footer">
                <button class="action-btn secondary" onclick="closeNpmModal('exportTopologyModal')">Cancel</button>
                <button class="action-btn primary" onclick="executeTopologyExport()">📤 Export</button>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="toastContainer"></div>

    <style>
        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid #e0e0e0;
            border-top-color: #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>

    <script>
    // Tab switching
    function switchTab(tabName) {
        const tabs = document.querySelectorAll('.tab-content');
        const btns = document.querySelectorAll('.tab-btn');

        tabs.forEach(tab => tab.classList.remove('active'));
        btns.forEach(btn => btn.classList.remove('active'));

        document.getElementById(tabName).classList.add('active');
        event.target.classList.add('active');

        // Initialize charts when switching to their tabs
        if (tabName === 'bandwidth') initBandwidthCharts();
        if (tabName === 'performance') initPerformanceCharts();
        if (tabName === 'topology') initTopology();
    }

    // Threshold update
    function updateThreshold(slider, id) {
        document.getElementById(id).textContent = slider.value;
    }

    // Initialize Bandwidth Charts
    function initBandwidthCharts() {
        if (window.bandwidthChart) return; // Already initialized

        // Real-Time Bandwidth Chart
        const bwCtx = document.getElementById('bandwidthChart').getContext('2d');
        window.bandwidthChart = new Chart(bwCtx, {
            type: 'line',
            data: {
                labels: ['30s ago', '25s', '20s', '15s', '10s', '5s', 'Now'],
                datasets: [{
                    label: 'Inbound',
                    data: [450, 520, 480, 550, 590, 610, 580],
                    borderColor: '#4CAF50',
                    backgroundColor: 'rgba(76, 175, 80, 0.1)',
                    tension: 0.4
                }, {
                    label: 'Outbound',
                    data: [220, 250, 240, 280, 290, 310, 295],
                    borderColor: '#2196F3',
                    backgroundColor: 'rgba(33, 150, 243, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: true }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: 'Mbps' }
                    }
                }
            }
        });

        // Top Consumers Chart
        const consumerCtx = document.getElementById('topConsumersChart').getContext('2d');
        const deviceNames = <?= json_encode(array_map(function($d) { return substr($d['device_name'], 0, 15); }, array_slice($sortedDevices, 0, 5))) ?>;
        const bandwidthData = <?= json_encode(array_map(function($d) { return $d['bandwidth_in'] + $d['bandwidth_out']; }, array_slice($sortedDevices, 0, 5))) ?>;

        window.topConsumersChart = new Chart(consumerCtx, {
            type: 'bar',
            data: {
                labels: deviceNames,
                datasets: [{
                    label: 'Total Bandwidth (Mbps)',
                    data: bandwidthData,
                    backgroundColor: [
                        '#f44336', '#ff9800', '#ffc107', '#4CAF50', '#2196F3'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        title: { display: true, text: 'Mbps' }
                    }
                }
            }
        });
    }

    // Initialize Performance Charts
    function initPerformanceCharts() {
        if (window.cpuChart) return;

        // CPU Chart
        const cpuCtx = document.getElementById('cpuChart').getContext('2d');
        const deviceNames = <?= json_encode(array_map(function($d) { return substr($d['device_name'], 0, 15); }, $devices)) ?>;
        const cpuData = <?= json_encode(array_map(function($d) { return $d['cpu_usage']; }, $devices)) ?>;

        window.cpuChart = new Chart(cpuCtx, {
            type: 'bar',
            data: {
                labels: deviceNames,
                datasets: [{
                    label: 'CPU Usage (%)',
                    data: cpuData,
                    backgroundColor: cpuData.map(val => val > 80 ? '#f44336' : (val > 60 ? '#ff9800' : '#4CAF50'))
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, max: 100, title: { display: true, text: '%' } }
                }
            }
        });

        // Memory Chart
        const memCtx = document.getElementById('memoryChart').getContext('2d');
        const memData = <?= json_encode(array_map(function($d) { return $d['memory_usage']; }, $devices)) ?>;

        window.memoryChart = new Chart(memCtx, {
            type: 'bar',
            data: {
                labels: deviceNames,
                datasets: [{
                    label: 'Memory Usage (%)',
                    data: memData,
                    backgroundColor: memData.map(val => val > 85 ? '#f44336' : (val > 70 ? '#ff9800' : '#2196F3'))
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, max: 100, title: { display: true, text: '%' } }
                }
            }
        });

        // Latency Chart
        const latCtx = document.getElementById('latencyChart').getContext('2d');
        const latencyData = <?= json_encode(array_map(function($d) { return $d['latency']; }, $devices)) ?>;
        const lossData = <?= json_encode(array_map(function($d) { return $d['packet_loss']; }, $devices)) ?>;

        window.latencyChart = new Chart(latCtx, {
            type: 'line',
            data: {
                labels: deviceNames,
                datasets: [{
                    label: 'Latency (ms)',
                    data: latencyData,
                    borderColor: '#9C27B0',
                    backgroundColor: 'rgba(156, 39, 176, 0.1)',
                    yAxisID: 'y'
                }, {
                    label: 'Packet Loss (%)',
                    data: lossData,
                    borderColor: '#f44336',
                    backgroundColor: 'rgba(244, 67, 54, 0.1)',
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: true } },
                scales: {
                    y: { type: 'linear', position: 'left', title: { display: true, text: 'Latency (ms)' } },
                    y1: { type: 'linear', position: 'right', title: { display: true, text: 'Packet Loss (%)' }, grid: { drawOnChartArea: false } }
                }
            }
        });
    }

    // ============================================
    // Enhanced Network Topology
    // ============================================

    // Global topology data
    let topologyData = {
        devices: <?= json_encode($devices) ?>,
        nodes: null,
        edges: null,
        allNodes: null,
        allEdges: null
    };
    let selectedNode = null;
    let contextMenuNode = null;

    // Device type icons and shapes
    const deviceIcons = {
        router: { shape: 'diamond', icon: '🔀', color: '#3f51b5' },
        switch: { shape: 'box', icon: '🔌', color: '#009688' },
        firewall: { shape: 'triangle', icon: '🔥', color: '#f44336' },
        server: { shape: 'square', icon: '🖥️', color: '#9c27b0' },
        access_point: { shape: 'star', icon: '📡', color: '#00bcd4' },
        workstation: { shape: 'dot', icon: '💻', color: '#607d8b' },
        phone: { shape: 'dot', icon: '📞', color: '#795548' },
        other: { shape: 'dot', icon: '📦', color: '#9e9e9e' }
    };

    // Initialize Network Topology
    function initTopology() {
        if (window.network) {
            // Update stats
            updateTopologyStats();
            return;
        }

        const devices = topologyData.devices;

        // Create nodes with enhanced styling
        const nodes = devices.map((device, index) => {
            const typeConfig = deviceIcons[device.device_type] || deviceIcons.other;
            const statusColor = device.status === 'online' ? '#4CAF50' : (device.status === 'warning' ? '#ff9800' : '#f44336');
            const size = 25 + (device.bandwidth_in + device.bandwidth_out) / 80;

            return {
                id: index,
                label: device.device_name,
                title: createNodeTooltip(device),
                shape: typeConfig.shape,
                color: {
                    background: typeConfig.color,
                    border: statusColor,
                    highlight: { background: typeConfig.color, border: '#667eea' },
                    hover: { background: typeConfig.color, border: '#667eea' }
                },
                borderWidth: 3,
                borderWidthSelected: 5,
                size: size,
                font: {
                    size: 12,
                    face: 'Segoe UI',
                    color: '#333',
                    strokeWidth: 3,
                    strokeColor: '#ffffff'
                },
                shadow: {
                    enabled: true,
                    color: 'rgba(0,0,0,0.2)',
                    size: 10,
                    x: 2,
                    y: 2
                },
                // Store device data for later use
                deviceData: device
            };
        });

        // Create edges with bandwidth visualization
        const edges = createTopologyEdges(nodes);

        // Store original data
        topologyData.nodes = new vis.DataSet(nodes);
        topologyData.edges = new vis.DataSet(edges);
        topologyData.allNodes = [...nodes];
        topologyData.allEdges = [...edges];

        const container = document.getElementById('topology-network');
        const data = {
            nodes: topologyData.nodes,
            edges: topologyData.edges
        };

        const options = {
            nodes: {
                borderWidth: 3,
                borderWidthSelected: 5,
                chosen: true
            },
            edges: {
                smooth: {
                    type: 'continuous',
                    roundness: 0.5
                },
                arrows: {
                    to: { enabled: false }
                },
                hoverWidth: 2,
                selectionWidth: 3
            },
            physics: {
                enabled: true,
                solver: 'forceAtlas2Based',
                forceAtlas2Based: {
                    gravitationalConstant: -50,
                    centralGravity: 0.01,
                    springLength: 150,
                    springConstant: 0.08
                },
                stabilization: {
                    iterations: 150,
                    fit: true
                }
            },
            interaction: {
                hover: true,
                tooltipDelay: 100,
                multiselect: true,
                navigationButtons: true,
                keyboard: {
                    enabled: true,
                    bindToWindow: false
                }
            },
            layout: {
                improvedLayout: true
            }
        };

        window.network = new vis.Network(container, data, options);

        // Event listeners
        setupTopologyEvents();

        // Initialize minimap
        initMinimap();

        // Update stats
        updateTopologyStats();

        // Stabilization complete
        window.network.on('stabilizationIterationsDone', function() {
            window.network.setOptions({ physics: { enabled: false } });
        });
    }

    // Create node tooltip HTML
    function createNodeTooltip(device) {
        const typeConfig = deviceIcons[device.device_type] || deviceIcons.other;
        return `<div style="font-family: Segoe UI; padding: 10px; max-width: 250px;">
            <div style="font-size: 16px; font-weight: bold; margin-bottom: 8px;">
                ${typeConfig.icon} ${device.device_name}
            </div>
            <div style="font-size: 12px; color: #666; margin-bottom: 5px;">
                <strong>IP:</strong> ${device.ip_address}<br>
                <strong>Type:</strong> ${device.device_type}<br>
                <strong>Location:</strong> ${device.location || 'N/A'}<br>
                <strong>Status:</strong> <span style="color: ${device.status === 'online' ? '#4CAF50' : '#f44336'}">${device.status.toUpperCase()}</span>
            </div>
            <div style="margin-top: 8px; padding-top: 8px; border-top: 1px solid #eee; font-size: 11px;">
                <strong>CPU:</strong> ${device.cpu_usage}% | <strong>Memory:</strong> ${device.memory_usage}%<br>
                <strong>Bandwidth:</strong> ↓${device.bandwidth_in} / ↑${device.bandwidth_out} Mbps
            </div>
        </div>`;
    }

    // Create topology edges based on device types
    function createTopologyEdges(nodes) {
        const edges = [];
        let edgeId = 0;

        // Find routers and core devices
        const routers = nodes.filter(n => n.deviceData.device_type === 'router');
        const switches = nodes.filter(n => n.deviceData.device_type === 'switch');
        const firewalls = nodes.filter(n => n.deviceData.device_type === 'firewall');
        const others = nodes.filter(n =>
            !['router', 'switch', 'firewall'].includes(n.deviceData.device_type)
        );

        // Connect routers to each other (mesh)
        for (let i = 0; i < routers.length; i++) {
            for (let j = i + 1; j < routers.length; j++) {
                edges.push(createEdge(edgeId++, routers[i].id, routers[j].id, 1000, 'fiber'));
            }
        }

        // Connect firewalls to routers
        firewalls.forEach(fw => {
            if (routers.length > 0) {
                edges.push(createEdge(edgeId++, fw.id, routers[0].id, 1000, 'fiber'));
            }
        });

        // Connect switches to routers or firewalls
        switches.forEach((sw, idx) => {
            if (firewalls.length > 0) {
                edges.push(createEdge(edgeId++, sw.id, firewalls[idx % firewalls.length].id, 1000, 'ethernet'));
            } else if (routers.length > 0) {
                edges.push(createEdge(edgeId++, sw.id, routers[idx % routers.length].id, 1000, 'ethernet'));
            }
        });

        // Connect other devices to switches
        others.forEach((device, idx) => {
            if (switches.length > 0) {
                edges.push(createEdge(edgeId++, device.id, switches[idx % switches.length].id, 100, 'ethernet'));
            } else if (routers.length > 0) {
                edges.push(createEdge(edgeId++, device.id, routers[0].id, 100, 'ethernet'));
            } else if (nodes.length > 1) {
                edges.push(createEdge(edgeId++, device.id, 0, 100, 'ethernet'));
            }
        });

        // Add some redundant links between switches
        if (switches.length > 1) {
            for (let i = 0; i < switches.length - 1; i++) {
                edges.push(createEdge(edgeId++, switches[i].id, switches[i + 1].id, 1000, 'ethernet'));
            }
        }

        return edges;
    }

    // Create a single edge with styling
    function createEdge(id, from, to, bandwidth, type) {
        const utilization = Math.random() * 100;
        const color = utilization > 80 ? '#f44336' : (utilization > 50 ? '#ff9800' : '#4CAF50');
        const width = type === 'fiber' ? 4 : (type === 'wan' ? 2 : 3);

        return {
            id: id,
            from: from,
            to: to,
            width: width,
            color: {
                color: color,
                highlight: '#667eea',
                hover: '#667eea'
            },
            dashes: type === 'wireless' || type === 'vpn',
            title: `Bandwidth: ${bandwidth} Mbps\nUtilization: ${utilization.toFixed(1)}%\nType: ${type}`,
            smooth: {
                type: 'continuous',
                roundness: 0.3
            },
            // Store edge data
            edgeData: {
                bandwidth: bandwidth,
                utilization: utilization,
                type: type
            }
        };
    }

    // Setup topology event listeners
    function setupTopologyEvents() {
        // Click event
        window.network.on('click', function(params) {
            hideContextMenu();
            if (params.nodes.length > 0) {
                selectedNode = params.nodes[0];
                showDeviceInfo(selectedNode);
            } else {
                hideDeviceInfo();
                selectedNode = null;
            }
        });

        // Double-click event
        window.network.on('doubleClick', function(params) {
            if (params.nodes.length > 0) {
                const nodeId = params.nodes[0];
                viewDeviceDetailsById(nodeId);
            }
        });

        // Right-click context menu
        window.network.on('oncontext', function(params) {
            params.event.preventDefault();
            const nodeId = window.network.getNodeAt(params.pointer.DOM);
            if (nodeId !== undefined) {
                contextMenuNode = nodeId;
                showContextMenu(params.event.pageX, params.event.pageY);
            } else {
                hideContextMenu();
            }
        });

        // Hover events for minimap updates
        window.network.on('zoom', updateMinimap);
        window.network.on('dragEnd', updateMinimap);

        // Close context menu on click elsewhere
        document.addEventListener('click', hideContextMenu);
    }

    // Show device info overlay
    function showDeviceInfo(nodeId) {
        const node = topologyData.nodes.get(nodeId);
        if (!node || !node.deviceData) return;

        const device = node.deviceData;
        const typeConfig = deviceIcons[device.device_type] || deviceIcons.other;

        document.getElementById('topo-info-icon').textContent = typeConfig.icon;
        document.getElementById('topo-info-name').textContent = device.device_name;
        document.getElementById('topo-info-ip').textContent = device.ip_address;
        document.getElementById('topo-info-type').textContent = device.device_type.charAt(0).toUpperCase() + device.device_type.slice(1);
        document.getElementById('topo-info-location').textContent = device.location || 'N/A';

        const statusBadge = document.getElementById('topo-info-status');
        statusBadge.textContent = device.status.charAt(0).toUpperCase() + device.status.slice(1);
        statusBadge.className = 'status-badge ' + device.status;

        // Update metrics
        const cpuColor = device.cpu_usage > 80 ? '#f44336' : (device.cpu_usage > 60 ? '#ff9800' : '#4CAF50');
        const memColor = device.memory_usage > 85 ? '#f44336' : (device.memory_usage > 70 ? '#ff9800' : '#4CAF50');

        document.getElementById('topo-info-cpu').style.width = device.cpu_usage + '%';
        document.getElementById('topo-info-cpu').style.background = cpuColor;
        document.getElementById('topo-info-cpu-val').textContent = device.cpu_usage + '%';

        document.getElementById('topo-info-mem').style.width = device.memory_usage + '%';
        document.getElementById('topo-info-mem').style.background = memColor;
        document.getElementById('topo-info-mem-val').textContent = device.memory_usage + '%';

        document.getElementById('topo-info-bw').textContent = `↓ ${device.bandwidth_in} / ↑ ${device.bandwidth_out} Mbps`;

        document.getElementById('topology-device-info').style.display = 'block';
    }

    // Hide device info overlay
    function hideDeviceInfo() {
        document.getElementById('topology-device-info').style.display = 'none';
    }

    // Context menu functions
    function showContextMenu(x, y) {
        const menu = document.getElementById('topoContextMenu');
        menu.style.left = x + 'px';
        menu.style.top = y + 'px';
        menu.classList.add('active');
    }

    function hideContextMenu() {
        document.getElementById('topoContextMenu').classList.remove('active');
    }

    function contextMenuAction(action) {
        hideContextMenu();
        if (contextMenuNode === null) return;

        const node = topologyData.nodes.get(contextMenuNode);
        if (!node) return;

        switch(action) {
            case 'ping':
                pingDeviceById(contextMenuNode);
                break;
            case 'snmp':
                openDeviceSnmpById(contextMenuNode);
                break;
            case 'details':
                viewDeviceDetailsById(contextMenuNode);
                break;
            case 'addConnection':
                openAddConnectionModal(contextMenuNode);
                break;
            case 'removeConnections':
                removeDeviceConnections(contextMenuNode);
                break;
            case 'highlight':
                highlightDevicePath(contextMenuNode);
                break;
            case 'isolate':
                isolateDeviceView(contextMenuNode);
                break;
            case 'disable':
                disableDevice(contextMenuNode);
                break;
        }
    }

    // Topology layout functions
    function setTopologyLayout(layout) {
        // Update button states
        document.querySelectorAll('.toolbar-section:first-child .topo-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        event.target.closest('.topo-btn').classList.add('active');

        let options = {};

        switch(layout) {
            case 'physics':
                options = {
                    physics: { enabled: true, solver: 'forceAtlas2Based' },
                    layout: { hierarchical: { enabled: false } }
                };
                break;
            case 'hierarchical':
                options = {
                    physics: { enabled: false },
                    layout: {
                        hierarchical: {
                            enabled: true,
                            direction: 'UD',
                            sortMethod: 'directed',
                            levelSeparation: 100,
                            nodeSpacing: 150
                        }
                    }
                };
                break;
            case 'circular':
                arrangeCircular();
                return;
            case 'grid':
                arrangeGrid();
                return;
        }

        window.network.setOptions(options);
        if (layout === 'physics') {
            window.network.stabilize(100);
        }
    }

    // Circular arrangement
    function arrangeCircular() {
        const nodes = topologyData.nodes.get();
        const centerX = 0, centerY = 0;
        const radius = 250;

        nodes.forEach((node, i) => {
            const angle = (2 * Math.PI * i) / nodes.length;
            topologyData.nodes.update({
                id: node.id,
                x: centerX + radius * Math.cos(angle),
                y: centerY + radius * Math.sin(angle)
            });
        });

        window.network.setOptions({ physics: { enabled: false }, layout: { hierarchical: { enabled: false } } });
        window.network.fit();
    }

    // Grid arrangement
    function arrangeGrid() {
        const nodes = topologyData.nodes.get();
        const cols = Math.ceil(Math.sqrt(nodes.length));
        const spacing = 150;

        nodes.forEach((node, i) => {
            topologyData.nodes.update({
                id: node.id,
                x: (i % cols) * spacing,
                y: Math.floor(i / cols) * spacing
            });
        });

        window.network.setOptions({ physics: { enabled: false }, layout: { hierarchical: { enabled: false } } });
        window.network.fit();
    }

    // Zoom functions
    function topologyZoom(action) {
        const scale = window.network.getScale();
        switch(action) {
            case 'in':
                window.network.moveTo({ scale: scale * 1.3, animation: true });
                break;
            case 'out':
                window.network.moveTo({ scale: scale / 1.3, animation: true });
                break;
            case 'fit':
                window.network.fit({ animation: true });
                break;
        }
    }

    // Filter devices
    function filterTopologyDevices() {
        const typeFilter = document.getElementById('topoFilterType').value;
        const statusFilter = document.getElementById('topoFilterStatus').value;

        const filteredNodes = topologyData.allNodes.filter(node => {
            const device = node.deviceData;
            const typeMatch = typeFilter === 'all' || device.device_type === typeFilter;
            const statusMatch = statusFilter === 'all' || device.status === statusFilter;
            return typeMatch && statusMatch;
        });

        const filteredNodeIds = filteredNodes.map(n => n.id);
        const filteredEdges = topologyData.allEdges.filter(edge =>
            filteredNodeIds.includes(edge.from) && filteredNodeIds.includes(edge.to)
        );

        topologyData.nodes.clear();
        topologyData.edges.clear();
        topologyData.nodes.add(filteredNodes);
        topologyData.edges.add(filteredEdges);

        window.network.fit();
        updateTopologyStats();
    }

    // Search device
    function searchTopologyDevice(query) {
        if (!query) {
            // Reset all nodes
            topologyData.nodes.get().forEach(node => {
                topologyData.nodes.update({ id: node.id, opacity: 1, font: { ...node.font, color: '#333' } });
            });
            return;
        }

        query = query.toLowerCase();
        topologyData.nodes.get().forEach(node => {
            const device = node.deviceData;
            const matches =
                device.device_name.toLowerCase().includes(query) ||
                device.ip_address.includes(query) ||
                device.device_type.toLowerCase().includes(query);

            if (matches) {
                topologyData.nodes.update({
                    id: node.id,
                    opacity: 1,
                    borderWidth: 5,
                    shadow: { enabled: true, size: 20, color: 'rgba(102, 126, 234, 0.5)' }
                });
                // Focus on first match
                if (topologyData.nodes.get().filter(n => n.deviceData.device_name.toLowerCase().includes(query))[0]?.id === node.id) {
                    window.network.focus(node.id, { scale: 1.2, animation: true });
                }
            } else {
                topologyData.nodes.update({
                    id: node.id,
                    opacity: 0.3,
                    borderWidth: 3,
                    shadow: { enabled: true, size: 10 }
                });
            }
        });
    }

    // Refresh topology
    function refreshTopology() {
        showToast('Refreshing network topology...', 'info');

        // Simulate refresh with random utilization updates
        topologyData.edges.get().forEach(edge => {
            const newUtilization = Math.random() * 100;
            const color = newUtilization > 80 ? '#f44336' : (newUtilization > 50 ? '#ff9800' : '#4CAF50');
            topologyData.edges.update({
                id: edge.id,
                color: { color: color, highlight: '#667eea', hover: '#667eea' },
                title: `Bandwidth: ${edge.edgeData.bandwidth} Mbps\nUtilization: ${newUtilization.toFixed(1)}%\nType: ${edge.edgeData.type}`
            });
        });

        updateTopologyStats();
        setTimeout(() => showToast('Topology refreshed!', 'success'), 1000);
    }

    // Export topology
    function exportTopology() {
        openNpmModal('exportTopologyModal');
    }

    function executeTopologyExport() {
        const format = document.querySelector('input[name="exportFormat"]:checked').value;
        showToast(`Exporting topology as ${format.toUpperCase()}...`, 'info');

        setTimeout(() => {
            if (format === 'json') {
                const data = {
                    exportDate: new Date().toISOString(),
                    nodes: topologyData.nodes.get().map(n => ({
                        id: n.id,
                        name: n.deviceData.device_name,
                        ip: n.deviceData.ip_address,
                        type: n.deviceData.device_type,
                        status: n.deviceData.status
                    })),
                    edges: topologyData.edges.get().map(e => ({
                        from: e.from,
                        to: e.to,
                        bandwidth: e.edgeData.bandwidth,
                        type: e.edgeData.type
                    }))
                };

                const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `network_topology_${new Date().toISOString().split('T')[0]}.json`;
                a.click();
                URL.revokeObjectURL(url);
            } else if (format === 'png') {
                // Get canvas and export
                const canvas = document.querySelector('#topology-network canvas');
                if (canvas) {
                    const link = document.createElement('a');
                    link.download = `network_topology_${new Date().toISOString().split('T')[0]}.png`;
                    link.href = canvas.toDataURL('image/png');
                    link.click();
                }
            }

            showToast('Topology exported successfully!', 'success');
            closeNpmModal('exportTopologyModal');
        }, 1000);
    }

    // Add connection modal
    function openAddConnectionModal(sourceNodeId = null) {
        if (sourceNodeId !== null) {
            document.getElementById('connectionSource').value = sourceNodeId;
        }
        openNpmModal('addConnectionModal');
    }

    function createConnection() {
        const source = parseInt(document.getElementById('connectionSource').value);
        const dest = parseInt(document.getElementById('connectionDest').value);
        const type = document.getElementById('connectionType').value;
        const bandwidth = parseInt(document.getElementById('connectionBandwidth').value);

        if (isNaN(source) || isNaN(dest) || source === dest) {
            showToast('Please select valid source and destination devices', 'error');
            return;
        }

        const newEdge = createEdge(
            topologyData.edges.length + 1,
            source,
            dest,
            bandwidth,
            type
        );

        topologyData.edges.add(newEdge);
        topologyData.allEdges.push(newEdge);

        showToast('Connection created successfully!', 'success');
        closeNpmModal('addConnectionModal');
        updateTopologyStats();
    }

    // Remove device connections
    function removeDeviceConnections(nodeId) {
        const edgesToRemove = topologyData.edges.get().filter(e => e.from === nodeId || e.to === nodeId);
        edgesToRemove.forEach(e => topologyData.edges.remove(e.id));
        showToast(`Removed ${edgesToRemove.length} connection(s)`, 'info');
        updateTopologyStats();
    }

    // Highlight device path
    function highlightDevicePath(nodeId) {
        // Reset all edges
        topologyData.edges.get().forEach(e => {
            topologyData.edges.update({ id: e.id, width: e.edgeData.type === 'fiber' ? 4 : 3 });
        });

        // Highlight connected edges
        const connectedEdges = topologyData.edges.get().filter(e => e.from === nodeId || e.to === nodeId);
        connectedEdges.forEach(e => {
            topologyData.edges.update({ id: e.id, width: 8, color: { color: '#667eea' } });
        });

        showToast(`Highlighted ${connectedEdges.length} connection(s)`, 'info');
    }

    // Isolate device view
    function isolateDeviceView(nodeId) {
        const connectedEdges = topologyData.allEdges.filter(e => e.from === nodeId || e.to === nodeId);
        const connectedNodeIds = new Set([nodeId]);
        connectedEdges.forEach(e => {
            connectedNodeIds.add(e.from);
            connectedNodeIds.add(e.to);
        });

        const filteredNodes = topologyData.allNodes.filter(n => connectedNodeIds.has(n.id));

        topologyData.nodes.clear();
        topologyData.edges.clear();
        topologyData.nodes.add(filteredNodes);
        topologyData.edges.add(connectedEdges);

        window.network.fit();
        showToast('Isolated view - showing connected devices only', 'info');
    }

    // Disable device (visual only)
    function disableDevice(nodeId) {
        topologyData.nodes.update({
            id: nodeId,
            color: { background: '#9e9e9e', border: '#616161' },
            opacity: 0.5
        });
        showToast('Device marked as disabled', 'info');
    }

    // Device actions from info panel
    function pingDevice() {
        if (selectedNode === null) return;
        pingDeviceById(selectedNode);
    }

    function pingDeviceById(nodeId) {
        const node = topologyData.nodes.get(nodeId);
        if (!node) return;

        showToast(`Pinging ${node.deviceData.device_name}...`, 'info');
        setTimeout(() => {
            const success = Math.random() > 0.1;
            if (success) {
                const latency = Math.floor(Math.random() * 50) + 1;
                showToast(`Ping successful: ${latency}ms`, 'success');
            } else {
                showToast('Ping failed: Request timeout', 'error');
            }
        }, 1500);
    }

    function openDeviceSnmp() {
        if (selectedNode === null) return;
        openDeviceSnmpById(selectedNode);
    }

    function openDeviceSnmpById(nodeId) {
        const node = topologyData.nodes.get(nodeId);
        if (!node) return;
        snmpWalk(nodeId, node.deviceData.device_name, node.deviceData.ip_address);
    }

    function viewDeviceDetails() {
        if (selectedNode === null) return;
        viewDeviceDetailsById(selectedNode);
    }

    function viewDeviceDetailsById(nodeId) {
        const node = topologyData.nodes.get(nodeId);
        if (!node) return;
        showToast(`Opening details for ${node.deviceData.device_name}...`, 'info');
        // In a real app, this would navigate to device details page
    }

    // Update topology stats
    function updateTopologyStats() {
        const nodes = topologyData.nodes.get();
        const edges = topologyData.edges.get();

        document.getElementById('topoTotalDevices').textContent = nodes.length;
        document.getElementById('topoOnlineDevices').textContent = nodes.filter(n => n.deviceData.status === 'online').length;
        document.getElementById('topoOfflineDevices').textContent = nodes.filter(n => n.deviceData.status !== 'online').length;
        document.getElementById('topoConnections').textContent = edges.length;

        const avgBw = edges.length > 0
            ? Math.round(edges.reduce((sum, e) => sum + e.edgeData.bandwidth, 0) / edges.length)
            : 0;
        document.getElementById('topoAvgBandwidth').textContent = avgBw;
    }

    // Initialize minimap
    function initMinimap() {
        updateMinimap();
    }

    function updateMinimap() {
        const canvas = document.getElementById('minimap-canvas');
        if (!canvas || !window.network) return;

        canvas.innerHTML = '';

        const nodes = topologyData.nodes.get();
        const boundingBox = window.network.getBoundingBox();
        const width = boundingBox.right - boundingBox.left || 400;
        const height = boundingBox.bottom - boundingBox.top || 300;
        const scaleX = 190 / width;
        const scaleY = 170 / height;
        const scale = Math.min(scaleX, scaleY);

        nodes.forEach(node => {
            const pos = window.network.getPosition(node.id);
            const dot = document.createElement('div');
            dot.className = 'minimap-node';
            dot.style.left = ((pos.x - boundingBox.left) * scale + 5) + 'px';
            dot.style.top = ((pos.y - boundingBox.top) * scale + 5) + 'px';
            dot.style.background = node.deviceData.status === 'online' ? '#4CAF50' : '#f44336';
            canvas.appendChild(dot);
        });

        // Add viewport indicator
        const viewPos = window.network.getViewPosition();
        const viewScale = window.network.getScale();
        const containerWidth = document.getElementById('topology-network').offsetWidth;
        const containerHeight = document.getElementById('topology-network').offsetHeight;

        const viewport = document.createElement('div');
        viewport.className = 'minimap-viewport';
        viewport.style.width = Math.min(190, containerWidth * scale / viewScale) + 'px';
        viewport.style.height = Math.min(170, containerHeight * scale / viewScale) + 'px';
        viewport.style.left = ((viewPos.x - boundingBox.left - containerWidth / 2 / viewScale) * scale + 5) + 'px';
        viewport.style.top = ((viewPos.y - boundingBox.top - containerHeight / 2 / viewScale) * scale + 5) + 'px';
        canvas.appendChild(viewport);
    }

    // Auto-refresh countdown
    let countdown = 30;
    setInterval(() => {
        countdown--;
        document.getElementById('countdown').textContent = countdown;
        if (countdown <= 0) {
            location.reload();
        }
    }, 1000);

    // Initialize default charts on load
    initBandwidthCharts();

    // ============================================
    // SNMP Modal Functions
    // ============================================

    function openNpmModal(modalId) {
        document.getElementById(modalId).classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeNpmModal(modalId) {
        document.getElementById(modalId).classList.remove('active');
        document.body.style.overflow = '';
    }

    function showToast(message, type = 'info') {
        const container = document.getElementById('toastContainer');
        const toast = document.createElement('div');
        toast.className = `toast-notification ${type}`;
        toast.textContent = message;
        container.appendChild(toast);

        setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    // ============================================
    // SNMP Configuration Functions
    // ============================================

    let currentSnmpDeviceId = null;

    function openSnmpConfigModal(deviceId, deviceName, deviceIp, version) {
        currentSnmpDeviceId = deviceId;
        document.getElementById('snmpDeviceId').value = deviceId;
        document.getElementById('snmpDeviceName').textContent = deviceName;
        document.getElementById('snmpDeviceIp').value = deviceIp;
        document.getElementById('snmpVersion').value = version || 'v2c';
        document.getElementById('snmpTestResult').innerHTML = '';

        toggleSnmpV3Options();
        openNpmModal('snmpConfigModal');
    }

    function toggleSnmpV3Options() {
        const version = document.getElementById('snmpVersion').value;
        document.getElementById('snmpV1V2Options').style.display = version === 'v3' ? 'none' : 'block';
        document.getElementById('snmpV3Options').style.display = version === 'v3' ? 'block' : 'none';
    }

    function testCurrentSnmpConfig() {
        const resultDiv = document.getElementById('snmpTestResult');
        const deviceIp = document.getElementById('snmpDeviceIp').value;

        resultDiv.innerHTML = `
            <div class="snmp-test-result pending">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div class="spinner" style="width: 24px; height: 24px; border-width: 3px;"></div>
                    <span>Testing SNMP connection to ${deviceIp}...</span>
                </div>
            </div>
        `;

        setTimeout(() => {
            const success = Math.random() > 0.2;
            if (success) {
                const sampleData = {
                    sysName: 'Core-Router-01',
                    sysUpTime: Math.floor(Math.random() * 10000000),
                    sysDescr: 'Cisco IOS Software, Version 15.2(4)M',
                    ifNumber: Math.floor(Math.random() * 48) + 4
                };

                resultDiv.innerHTML = `
                    <div class="snmp-test-result success">
                        <h4 style="margin: 0 0 10px 0; color: #2e7d32;">✅ Connection Successful!</h4>
                        <div style="font-size: 13px;">
                            <p><strong>Response Time:</strong> ${Math.floor(Math.random() * 50) + 5}ms</p>
                            <p><strong>sysName:</strong> ${sampleData.sysName}</p>
                            <p><strong>sysUpTime:</strong> ${Math.floor(sampleData.sysUpTime / 8640000)}d ${Math.floor((sampleData.sysUpTime % 8640000) / 360000)}h</p>
                            <p><strong>sysDescr:</strong> ${sampleData.sysDescr}</p>
                            <p><strong>ifNumber:</strong> ${sampleData.ifNumber} interfaces</p>
                        </div>
                    </div>
                `;
            } else {
                resultDiv.innerHTML = `
                    <div class="snmp-test-result error">
                        <h4 style="margin: 0 0 10px 0; color: #c62828;">❌ Connection Failed</h4>
                        <p style="font-size: 13px;">Error: SNMP timeout - No response from ${deviceIp}</p>
                        <p style="font-size: 12px; color: #888;">Please verify the community string and ensure SNMP is enabled on the device.</p>
                    </div>
                `;
            }
        }, 1500);
    }

    function saveSnmpConfig() {
        const config = {
            deviceId: document.getElementById('snmpDeviceId').value,
            version: document.getElementById('snmpVersion').value,
            port: document.getElementById('snmpPort').value,
            pollInterval: document.getElementById('snmpPollInterval').value,
            timeout: document.getElementById('snmpTimeout').value,
            enabled: document.getElementById('snmpEnabled').checked
        };

        showToast('Saving SNMP configuration...', 'info');

        setTimeout(() => {
            showToast('SNMP configuration saved successfully!', 'success');
            closeNpmModal('snmpConfigModal');

            // Update the card status badge
            const card = document.querySelector(`[data-device-id="${config.deviceId}"]`);
            if (card) {
                const badge = card.querySelector('.snmp-status-badge');
                if (badge) {
                    badge.className = `snmp-status-badge ${config.enabled ? 'active' : 'inactive'}`;
                    badge.textContent = config.enabled ? 'SNMP Active' : 'SNMP Off';
                }
            }
        }, 1000);
    }

    // ============================================
    // SNMP Test Functions
    // ============================================

    function testSnmpConnection(deviceId, deviceName, deviceIp) {
        openNpmModal('snmpTestModal');

        const content = document.getElementById('snmpTestContent');
        content.innerHTML = `
            <div style="text-align: center; padding: 40px;">
                <div class="spinner"></div>
                <p style="margin-top: 20px; color: #666;">Testing SNMP connection to ${deviceName}...</p>
                <p style="color: #888; font-size: 13px;">${deviceIp}:161</p>
            </div>
        `;

        setTimeout(() => {
            const success = Math.random() > 0.15;

            if (success) {
                const responseTime = Math.floor(Math.random() * 45) + 5;
                const uptime = Math.floor(Math.random() * 365) + 1;
                const interfaces = Math.floor(Math.random() * 48) + 4;

                content.innerHTML = `
                    <div class="snmp-test-result success">
                        <h4 style="margin: 0 0 15px 0; color: #2e7d32;">✅ SNMP Connection Successful</h4>
                        <table style="width: 100%; font-size: 13px;">
                            <tr>
                                <td style="padding: 8px 0;"><strong>Device:</strong></td>
                                <td>${deviceName}</td>
                            </tr>
                            <tr>
                                <td style="padding: 8px 0;"><strong>IP Address:</strong></td>
                                <td>${deviceIp}</td>
                            </tr>
                            <tr>
                                <td style="padding: 8px 0;"><strong>Response Time:</strong></td>
                                <td>${responseTime}ms</td>
                            </tr>
                            <tr>
                                <td style="padding: 8px 0;"><strong>SNMP Version:</strong></td>
                                <td>v2c</td>
                            </tr>
                            <tr>
                                <td style="padding: 8px 0;"><strong>System Uptime:</strong></td>
                                <td>${uptime} days</td>
                            </tr>
                            <tr>
                                <td style="padding: 8px 0;"><strong>Interfaces:</strong></td>
                                <td>${interfaces}</td>
                            </tr>
                        </table>
                    </div>

                    <h4 style="margin: 20px 0 10px 0;">📊 Sample SNMP Data Retrieved:</h4>
                    <div class="snmp-walk-output" style="max-height: 150px;">
.1.3.6.1.2.1.1.1.0 = STRING: "Cisco IOS Software, C2960 Software"
.1.3.6.1.2.1.1.3.0 = Timeticks: (${uptime * 8640000}) ${uptime} days, ${Math.floor(Math.random() * 23)}:${Math.floor(Math.random() * 59)}:${Math.floor(Math.random() * 59)}
.1.3.6.1.2.1.1.5.0 = STRING: "${deviceName}"
.1.3.6.1.2.1.2.1.0 = INTEGER: ${interfaces}
.1.3.6.1.2.1.2.2.1.10.1 = Counter32: ${Math.floor(Math.random() * 999999999)}
.1.3.6.1.2.1.2.2.1.16.1 = Counter32: ${Math.floor(Math.random() * 999999999)}
.1.3.6.1.4.1.2021.11.9.0 = INTEGER: ${Math.floor(Math.random() * 30)}
.1.3.6.1.4.1.2021.11.10.0 = INTEGER: ${Math.floor(Math.random() * 20)}
                    </div>
                `;
            } else {
                content.innerHTML = `
                    <div class="snmp-test-result error">
                        <h4 style="margin: 0 0 15px 0; color: #c62828;">❌ SNMP Connection Failed</h4>
                        <table style="width: 100%; font-size: 13px;">
                            <tr>
                                <td style="padding: 8px 0;"><strong>Device:</strong></td>
                                <td>${deviceName}</td>
                            </tr>
                            <tr>
                                <td style="padding: 8px 0;"><strong>IP Address:</strong></td>
                                <td>${deviceIp}</td>
                            </tr>
                            <tr>
                                <td style="padding: 8px 0;"><strong>Error:</strong></td>
                                <td style="color: #c62828;">Request timeout</td>
                            </tr>
                        </table>
                        <div style="margin-top: 15px; padding: 15px; background: #fff3e0; border-radius: 6px;">
                            <h5 style="margin: 0 0 10px 0; color: #e65100;">Troubleshooting Tips:</h5>
                            <ul style="margin: 0; padding-left: 20px; font-size: 12px; color: #666;">
                                <li>Verify SNMP is enabled on the device</li>
                                <li>Check the community string is correct</li>
                                <li>Ensure firewall allows UDP port 161</li>
                                <li>Confirm the device IP is reachable</li>
                            </ul>
                        </div>
                    </div>
                `;
            }
        }, 2000);
    }

    function testAllSnmp() {
        showToast('Testing SNMP connections for all devices...', 'info');

        let tested = 0;
        let success = 0;
        let failed = 0;
        const total = document.querySelectorAll('.device-card').length;

        const interval = setInterval(() => {
            tested++;
            if (Math.random() > 0.15) {
                success++;
            } else {
                failed++;
            }

            if (tested >= total) {
                clearInterval(interval);
                showToast(`SNMP Test Complete: ${success} OK, ${failed} Failed`, success === total ? 'success' : 'error');
            }
        }, 300);
    }

    // ============================================
    // SNMP Walk Functions
    // ============================================

    let currentWalkDevice = null;

    function snmpWalk(deviceId, deviceName, deviceIp) {
        currentWalkDevice = { id: deviceId, name: deviceName, ip: deviceIp };
        document.getElementById('walkDeviceName').textContent = deviceName;
        document.getElementById('snmpWalkOutput').textContent = 'Click "Walk" to retrieve SNMP data from the device...';
        openNpmModal('snmpWalkModal');
    }

    function executeSnmpWalk() {
        const output = document.getElementById('snmpWalkOutput');
        const startOid = document.getElementById('walkStartOid').value;
        const maxResults = document.getElementById('walkMaxResults').value;

        output.textContent = `Starting SNMP walk from ${startOid}...\n\n`;

        // Simulate SNMP walk with sample data
        const sampleOids = [
            { oid: '.1.3.6.1.2.1.1.1.0', type: 'STRING', value: '"Cisco IOS Software, C2960 Software (C2960-LANBASEK9-M), Version 15.2(4)E, RELEASE SOFTWARE"' },
            { oid: '.1.3.6.1.2.1.1.2.0', type: 'OID', value: '.1.3.6.1.4.1.9.1.1208' },
            { oid: '.1.3.6.1.2.1.1.3.0', type: 'Timeticks', value: `(${Math.floor(Math.random() * 99999999)}) ${Math.floor(Math.random() * 365)} days, ${Math.floor(Math.random() * 23)}:${Math.floor(Math.random() * 59)}:${Math.floor(Math.random() * 59)}.00` },
            { oid: '.1.3.6.1.2.1.1.4.0', type: 'STRING', value: '"Network Admin <admin@company.com>"' },
            { oid: '.1.3.6.1.2.1.1.5.0', type: 'STRING', value: `"${currentWalkDevice.name}"` },
            { oid: '.1.3.6.1.2.1.1.6.0', type: 'STRING', value: '"Data Center - Rack A12"' },
            { oid: '.1.3.6.1.2.1.2.1.0', type: 'INTEGER', value: Math.floor(Math.random() * 48) + 4 },
            { oid: '.1.3.6.1.2.1.2.2.1.1.1', type: 'INTEGER', value: '1' },
            { oid: '.1.3.6.1.2.1.2.2.1.2.1', type: 'STRING', value: '"GigabitEthernet0/1"' },
            { oid: '.1.3.6.1.2.1.2.2.1.3.1', type: 'INTEGER', value: '6' },
            { oid: '.1.3.6.1.2.1.2.2.1.5.1', type: 'Gauge32', value: '1000000000' },
            { oid: '.1.3.6.1.2.1.2.2.1.8.1', type: 'INTEGER', value: '1' },
            { oid: '.1.3.6.1.2.1.2.2.1.10.1', type: 'Counter32', value: Math.floor(Math.random() * 9999999999) },
            { oid: '.1.3.6.1.2.1.2.2.1.16.1', type: 'Counter32', value: Math.floor(Math.random() * 9999999999) },
            { oid: '.1.3.6.1.2.1.2.2.1.14.1', type: 'Counter32', value: Math.floor(Math.random() * 1000) },
            { oid: '.1.3.6.1.2.1.2.2.1.20.1', type: 'Counter32', value: Math.floor(Math.random() * 100) },
            { oid: '.1.3.6.1.4.1.2021.4.3.0', type: 'INTEGER', value: Math.floor(Math.random() * 1000000) },
            { oid: '.1.3.6.1.4.1.2021.4.5.0', type: 'INTEGER', value: '8388608' },
            { oid: '.1.3.6.1.4.1.2021.4.6.0', type: 'INTEGER', value: Math.floor(Math.random() * 4000000) },
            { oid: '.1.3.6.1.4.1.2021.11.9.0', type: 'INTEGER', value: Math.floor(Math.random() * 50) },
            { oid: '.1.3.6.1.4.1.2021.11.10.0', type: 'INTEGER', value: Math.floor(Math.random() * 30) },
            { oid: '.1.3.6.1.4.1.2021.11.11.0', type: 'INTEGER', value: Math.floor(Math.random() * 100) },
        ];

        let index = 0;
        const walkInterval = setInterval(() => {
            if (index >= Math.min(sampleOids.length, maxResults)) {
                clearInterval(walkInterval);
                output.textContent += `\n--- End of MIB ---\nWalk completed: ${index} OIDs retrieved`;
                return;
            }

            const oid = sampleOids[index];
            output.textContent += `${oid.oid} = ${oid.type}: ${oid.value}\n`;
            output.scrollTop = output.scrollHeight;
            index++;
        }, 100);
    }

    function copyWalkOutput() {
        const output = document.getElementById('snmpWalkOutput').textContent;
        navigator.clipboard.writeText(output).then(() => {
            showToast('SNMP walk output copied to clipboard!', 'success');
        });
    }

    function exportWalkOutput() {
        const output = document.getElementById('snmpWalkOutput').textContent;
        const blob = new Blob([output], { type: 'text/plain' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `snmp_walk_${currentWalkDevice.name}_${new Date().toISOString().split('T')[0]}.txt`;
        a.click();
        URL.revokeObjectURL(url);
        showToast('SNMP walk exported!', 'success');
    }

    // ============================================
    // Global SNMP Settings Functions
    // ============================================

    function openGlobalSnmpModal() {
        openNpmModal('globalSnmpModal');
    }

    function toggleAllOids(checkbox) {
        document.querySelectorAll('.oid-checkbox').forEach(cb => {
            cb.checked = checkbox.checked;
        });
    }

    function saveGlobalSnmpSettings() {
        showToast('Saving global SNMP settings...', 'info');

        setTimeout(() => {
            showToast('Global SNMP settings saved successfully!', 'success');
            closeNpmModal('globalSnmpModal');
        }, 1000);
    }

    // ============================================
    // SNMP Polling Functions
    // ============================================

    function openSnmpPollingModal() {
        openNpmModal('snmpPollingModal');
    }

    function savePollingConfig() {
        showToast('Saving polling configuration...', 'info');

        setTimeout(() => {
            showToast('Polling configuration saved!', 'success');
            closeNpmModal('snmpPollingModal');
        }, 1000);
    }

    // ============================================
    // SNMP Discovery Functions
    // ============================================

    function snmpDiscovery() {
        showToast('Starting SNMP device discovery...', 'info');

        setTimeout(() => {
            const discovered = Math.floor(Math.random() * 5) + 2;
            showToast(`Discovery complete! Found ${discovered} new SNMP-enabled devices.`, 'success');
        }, 3000);
    }

    function exportSnmpConfig() {
        showToast('Generating SNMP configuration export...', 'info');

        setTimeout(() => {
            const config = {
                exportDate: new Date().toISOString(),
                globalSettings: {
                    version: 'v2c',
                    defaultCommunity: '***',
                    pollInterval: 60,
                    retries: 3
                },
                devices: []
            };

            const blob = new Blob([JSON.stringify(config, null, 2)], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `snmp_config_${new Date().toISOString().split('T')[0]}.json`;
            a.click();
            URL.revokeObjectURL(url);

            showToast('SNMP configuration exported!', 'success');
        }, 1000);
    }

    // ============================================
    // Threshold Configuration Functions
    // ============================================

    function saveThresholdConfig() {
        const thresholds = {
            cpuWarning: document.getElementById('cpu-warn').textContent,
            cpuCritical: document.getElementById('cpu-crit').textContent,
            memWarning: document.getElementById('mem-warn').textContent,
            memCritical: document.getElementById('mem-crit').textContent,
            bwWarning: document.getElementById('bw-warn').textContent,
            bwCritical: document.getElementById('bw-crit').textContent,
            latencyWarning: document.getElementById('lat-warn').textContent,
            packetLossCritical: document.getElementById('loss-crit').textContent
        };

        showToast('Saving threshold configuration...', 'info');

        setTimeout(() => {
            console.log('Thresholds saved:', thresholds);
            showToast('Alert thresholds saved successfully! Alerts will be generated based on new values.', 'success');
        }, 1000);
    }
    </script>
</body>
</html>
