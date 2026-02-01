<?php
/**
 * HoL SIEM Observability - Full Feature Implementation
 * Unified monitoring for applications, infrastructure, logs, and traces
 */

require_once __DIR__ . '/../classes/Database.php';
$db = Database::getInstance();

// Initialize variables with defaults
$total_hosts = 0;
$total_devices = 0;
$total_apps = 0;
$total_databases = 0;
$recent_scans = [];
$recent_vulns = [];
$critical_issues = 0;
$high_issues = 0;

// Aggregate data from multiple sources with error handling
try { $result = $db->fetchOne("SELECT COUNT(*) as count FROM hosts"); $total_hosts = $result ? $result['count'] : 0; } catch (Exception $e) { $total_hosts = 0; }
try { $result = $db->fetchOne("SELECT COUNT(*) as count FROM network_devices"); $total_devices = $result ? $result['count'] : 0; } catch (Exception $e) { $total_devices = 0; }
try { $result = $db->fetchOne("SELECT COUNT(*) as count FROM monitored_applications"); $total_apps = $result ? $result['count'] : 0; } catch (Exception $e) { $total_apps = 0; }
try { $result = $db->fetchOne("SELECT COUNT(*) as count FROM monitored_databases"); $total_databases = $result ? $result['count'] : 0; } catch (Exception $e) { $total_databases = 0; }
try { $recent_scans = $db->fetchAll("SELECT * FROM scans ORDER BY started_at DESC LIMIT 5"); } catch (Exception $e) { $recent_scans = []; }
try { $result = $db->fetchOne("SELECT COUNT(*) as count FROM vulnerabilities WHERE severity = 'critical'"); $critical_issues = $result ? $result['count'] : 0; } catch (Exception $e) { $critical_issues = 0; }
try { $result = $db->fetchOne("SELECT COUNT(*) as count FROM vulnerabilities WHERE severity = 'high'"); $high_issues = $result ? $result['count'] : 0; } catch (Exception $e) { $high_issues = 0; }

$health_score = max(0, 100 - ($critical_issues * 10) - ($high_issues * 5));
$health_color = $health_score < 50 ? '#f44336' : ($health_score < 75 ? '#ff9800' : '#4CAF50');

// Sample Log Data
$logs = [
    ['timestamp' => '2025-01-26 11:45:32', 'level' => 'ERROR', 'source' => 'web-server-01', 'service' => 'nginx', 'message' => 'Connection refused to upstream server 10.0.1.50:8080', 'trace_id' => 'abc123'],
    ['timestamp' => '2025-01-26 11:45:30', 'level' => 'WARN', 'source' => 'api-gateway', 'service' => 'kong', 'message' => 'Rate limit exceeded for client IP 192.168.1.105', 'trace_id' => 'def456'],
    ['timestamp' => '2025-01-26 11:45:28', 'level' => 'INFO', 'source' => 'auth-service', 'service' => 'keycloak', 'message' => 'User john.doe@company.com logged in successfully', 'trace_id' => 'ghi789'],
    ['timestamp' => '2025-01-26 11:45:25', 'level' => 'ERROR', 'source' => 'db-primary', 'service' => 'mysql', 'message' => 'Deadlock detected in transaction, retrying...', 'trace_id' => 'jkl012'],
    ['timestamp' => '2025-01-26 11:45:22', 'level' => 'DEBUG', 'source' => 'payment-service', 'service' => 'stripe-api', 'message' => 'Processing payment for order #12345', 'trace_id' => 'mno345'],
    ['timestamp' => '2025-01-26 11:45:20', 'level' => 'INFO', 'source' => 'cache-server', 'service' => 'redis', 'message' => 'Cache hit ratio: 94.5%, Keys: 15,234', 'trace_id' => 'pqr678'],
    ['timestamp' => '2025-01-26 11:45:18', 'level' => 'WARN', 'source' => 'storage-node-02', 'service' => 'minio', 'message' => 'Disk usage at 85% threshold', 'trace_id' => 'stu901'],
    ['timestamp' => '2025-01-26 11:45:15', 'level' => 'ERROR', 'source' => 'email-service', 'service' => 'postfix', 'message' => 'SMTP connection timeout to relay.mail.com', 'trace_id' => 'vwx234'],
    ['timestamp' => '2025-01-26 11:45:12', 'level' => 'INFO', 'source' => 'scheduler', 'service' => 'cron', 'message' => 'Job backup_daily completed in 45.2s', 'trace_id' => 'yza567'],
    ['timestamp' => '2025-01-26 11:45:10', 'level' => 'CRITICAL', 'source' => 'firewall-01', 'service' => 'iptables', 'message' => 'Blocked 1,247 intrusion attempts from 45.33.32.156', 'trace_id' => 'bcd890'],
];

// Sample Traces
$traces = [
    ['trace_id' => 'abc123', 'name' => 'POST /api/orders', 'duration' => 245, 'status' => 'error', 'spans' => 8, 'services' => ['api-gateway', 'order-service', 'inventory-service', 'payment-service']],
    ['trace_id' => 'def456', 'name' => 'GET /api/products', 'duration' => 45, 'status' => 'success', 'spans' => 4, 'services' => ['api-gateway', 'product-service', 'cache']],
    ['trace_id' => 'ghi789', 'name' => 'POST /auth/login', 'duration' => 180, 'status' => 'success', 'spans' => 5, 'services' => ['api-gateway', 'auth-service', 'user-db']],
    ['trace_id' => 'jkl012', 'name' => 'PUT /api/inventory', 'duration' => 520, 'status' => 'error', 'spans' => 6, 'services' => ['api-gateway', 'inventory-service', 'db-primary']],
    ['trace_id' => 'mno345', 'name' => 'POST /api/payments', 'duration' => 890, 'status' => 'success', 'spans' => 7, 'services' => ['api-gateway', 'payment-service', 'stripe-api', 'notification-service']],
];

// Sample Alerts for Correlation
$alerts = [
    ['id' => 'ALT-001', 'timestamp' => '2025-01-26 11:40:00', 'severity' => 'critical', 'source' => 'db-primary', 'title' => 'Database Connection Pool Exhausted', 'status' => 'active', 'correlated' => true, 'root_cause' => true],
    ['id' => 'ALT-002', 'timestamp' => '2025-01-26 11:41:00', 'severity' => 'high', 'source' => 'api-gateway', 'title' => 'High API Latency Detected', 'status' => 'active', 'correlated' => true, 'root_cause' => false],
    ['id' => 'ALT-003', 'timestamp' => '2025-01-26 11:41:30', 'severity' => 'high', 'source' => 'order-service', 'title' => 'Order Processing Failures Spike', 'status' => 'active', 'correlated' => true, 'root_cause' => false],
    ['id' => 'ALT-004', 'timestamp' => '2025-01-26 11:42:00', 'severity' => 'medium', 'source' => 'web-server-01', 'title' => '5xx Error Rate Increased', 'status' => 'active', 'correlated' => true, 'root_cause' => false],
    ['id' => 'ALT-005', 'timestamp' => '2025-01-26 11:35:00', 'severity' => 'low', 'source' => 'monitoring', 'title' => 'Disk Space Warning on backup-server', 'status' => 'acknowledged', 'correlated' => false, 'root_cause' => false],
    ['id' => 'ALT-006', 'timestamp' => '2025-01-26 11:30:00', 'severity' => 'medium', 'source' => 'ssl-monitor', 'title' => 'SSL Certificate Expiring in 14 Days', 'status' => 'acknowledged', 'correlated' => false, 'root_cause' => false],
];

// Sample Anomalies
$anomalies = [
    ['id' => 1, 'metric' => 'CPU Usage', 'source' => 'web-server-01', 'expected' => '25-35%', 'actual' => '89%', 'deviation' => '+154%', 'severity' => 'high', 'detected' => '2025-01-26 11:30:00', 'status' => 'investigating'],
    ['id' => 2, 'metric' => 'Memory Usage', 'source' => 'db-primary', 'expected' => '60-70%', 'actual' => '95%', 'deviation' => '+36%', 'severity' => 'critical', 'detected' => '2025-01-26 11:35:00', 'status' => 'active'],
    ['id' => 3, 'metric' => 'Network Latency', 'source' => 'api-gateway', 'expected' => '10-20ms', 'actual' => '156ms', 'deviation' => '+680%', 'severity' => 'high', 'detected' => '2025-01-26 11:40:00', 'status' => 'active'],
    ['id' => 4, 'metric' => 'Error Rate', 'source' => 'payment-service', 'expected' => '0.1-0.5%', 'actual' => '4.2%', 'deviation' => '+740%', 'severity' => 'critical', 'detected' => '2025-01-26 11:42:00', 'status' => 'active'],
    ['id' => 5, 'metric' => 'Request Rate', 'source' => 'auth-service', 'expected' => '100-150/s', 'actual' => '45/s', 'deviation' => '-70%', 'severity' => 'medium', 'detected' => '2025-01-26 11:25:00', 'status' => 'resolved'],
];

// Sample Services for Dependency Map
$services = [
    ['id' => 'api-gateway', 'name' => 'API Gateway', 'type' => 'gateway', 'status' => 'healthy', 'requests' => 15420, 'errors' => 23, 'latency' => 45],
    ['id' => 'web-frontend', 'name' => 'Web Frontend', 'type' => 'frontend', 'status' => 'healthy', 'requests' => 8500, 'errors' => 5, 'latency' => 120],
    ['id' => 'auth-service', 'name' => 'Auth Service', 'type' => 'service', 'status' => 'healthy', 'requests' => 3200, 'errors' => 2, 'latency' => 35],
    ['id' => 'order-service', 'name' => 'Order Service', 'type' => 'service', 'status' => 'degraded', 'requests' => 2100, 'errors' => 89, 'latency' => 450],
    ['id' => 'payment-service', 'name' => 'Payment Service', 'type' => 'service', 'status' => 'critical', 'requests' => 1800, 'errors' => 156, 'latency' => 890],
    ['id' => 'inventory-service', 'name' => 'Inventory Service', 'type' => 'service', 'status' => 'healthy', 'requests' => 4500, 'errors' => 12, 'latency' => 65],
    ['id' => 'notification-service', 'name' => 'Notification Service', 'type' => 'service', 'status' => 'healthy', 'requests' => 2800, 'errors' => 8, 'latency' => 25],
    ['id' => 'db-primary', 'name' => 'Primary Database', 'type' => 'database', 'status' => 'critical', 'requests' => 25000, 'errors' => 234, 'latency' => 520],
    ['id' => 'db-replica', 'name' => 'Replica Database', 'type' => 'database', 'status' => 'healthy', 'requests' => 18000, 'errors' => 5, 'latency' => 15],
    ['id' => 'cache', 'name' => 'Redis Cache', 'type' => 'cache', 'status' => 'healthy', 'requests' => 45000, 'errors' => 0, 'latency' => 2],
];

$dependencies = [
    ['from' => 'web-frontend', 'to' => 'api-gateway'],
    ['from' => 'api-gateway', 'to' => 'auth-service'],
    ['from' => 'api-gateway', 'to' => 'order-service'],
    ['from' => 'api-gateway', 'to' => 'inventory-service'],
    ['from' => 'order-service', 'to' => 'payment-service'],
    ['from' => 'order-service', 'to' => 'inventory-service'],
    ['from' => 'order-service', 'to' => 'db-primary'],
    ['from' => 'payment-service', 'to' => 'notification-service'],
    ['from' => 'payment-service', 'to' => 'db-primary'],
    ['from' => 'inventory-service', 'to' => 'db-primary'],
    ['from' => 'inventory-service', 'to' => 'cache'],
    ['from' => 'auth-service', 'to' => 'db-replica'],
    ['from' => 'auth-service', 'to' => 'cache'],
    ['from' => 'db-primary', 'to' => 'db-replica'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HoL SIEM Observability | HoL Platform</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
            --dark: #1e1e2f;
            --darker: #151521;
            --card-bg: #252538;
            --card-border: #3a3a50;
            --text: #e2e8f0;
            --text-muted: #94a3b8;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--darker); color: var(--text); min-height: 100vh; }

        .header {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }
        .header h1 { font-size: 24px; display: flex; align-items: center; gap: 10px; }
        .back-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.3s;
        }
        .back-btn:hover { background: rgba(255,255,255,0.3); }

        .main-container { display: flex; min-height: calc(100vh - 80px); }

        .sidebar {
            width: 240px;
            background: var(--dark);
            border-right: 1px solid var(--card-border);
            padding: 20px 0;
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
        .nav-item:hover { background: rgba(99, 102, 241, 0.1); color: var(--text); }
        .nav-item.active { background: rgba(99, 102, 241, 0.15); color: var(--primary); border-left-color: var(--primary); }
        .nav-item .icon { font-size: 18px; }
        .nav-item .badge { margin-left: auto; background: var(--danger); color: white; font-size: 10px; padding: 2px 6px; border-radius: 10px; }

        .content { flex: 1; padding: 25px; overflow-y: auto; }

        .module { display: none; }
        .module.active { display: block; animation: fadeIn 0.3s ease; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 25px; }
        .stat-card {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 20px;
            border: 1px solid var(--card-border);
        }
        .stat-card.critical { border-left: 4px solid var(--danger); }
        .stat-card.warning { border-left: 4px solid var(--warning); }
        .stat-card.success { border-left: 4px solid var(--success); }
        .stat-card.info { border-left: 4px solid var(--info); }
        .stat-value { font-size: 32px; font-weight: 700; }
        .stat-label { color: var(--text-muted); font-size: 13px; margin-top: 5px; }

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
        .card-title { font-size: 16px; font-weight: 600; display: flex; align-items: center; gap: 10px; }
        .card-body { padding: 20px; }

        .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
        .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.3s;
        }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-dark); }
        .btn-sm { padding: 5px 10px; font-size: 12px; }

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
        .badge-error { background: rgba(239, 68, 68, 0.2); color: #f87171; }
        .badge-warn { background: rgba(245, 158, 11, 0.2); color: #fbbf24; }
        .badge-info { background: rgba(59, 130, 246, 0.2); color: #60a5fa; }
        .badge-debug { background: rgba(107, 114, 128, 0.2); color: #9ca3af; }
        .badge-success { background: rgba(16, 185, 129, 0.2); color: #34d399; }

        .search-box {
            background: var(--darker);
            border: 1px solid var(--card-border);
            border-radius: 6px;
            padding: 8px 12px;
            color: var(--text);
            width: 250px;
        }
        .search-box:focus { outline: none; border-color: var(--primary); }

        .filters { display: flex; gap: 10px; margin-bottom: 15px; flex-wrap: wrap; }
        .filter-btn {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            color: var(--text-muted);
            padding: 6px 14px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.3s;
        }
        .filter-btn:hover, .filter-btn.active { background: var(--primary); border-color: var(--primary); color: white; }

        /* Log Viewer */
        .log-viewer {
            background: #0d1117;
            border-radius: 8px;
            font-family: 'Consolas', 'Monaco', monospace;
            font-size: 12px;
            max-height: 500px;
            overflow-y: auto;
        }
        .log-entry {
            padding: 8px 15px;
            border-bottom: 1px solid #21262d;
            display: flex;
            gap: 15px;
            align-items: flex-start;
        }
        .log-entry:hover { background: #161b22; }
        .log-time { color: #8b949e; min-width: 150px; }
        .log-level { min-width: 70px; font-weight: 600; }
        .log-level.ERROR, .log-level.CRITICAL { color: #f85149; }
        .log-level.WARN { color: #d29922; }
        .log-level.INFO { color: #58a6ff; }
        .log-level.DEBUG { color: #8b949e; }
        .log-source { color: #a5d6ff; min-width: 120px; }
        .log-message { color: #c9d1d9; flex: 1; }
        .log-trace { color: #7ee787; font-size: 11px; }

        /* Trace Viewer */
        .trace-item {
            background: var(--darker);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            border-left: 4px solid var(--primary);
        }
        .trace-item.error { border-left-color: var(--danger); }
        .trace-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        .trace-name { font-weight: 600; }
        .trace-services { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 10px; }
        .trace-service {
            background: rgba(99, 102, 241, 0.2);
            color: var(--primary);
            padding: 3px 10px;
            border-radius: 4px;
            font-size: 11px;
        }
        .span-bar {
            height: 24px;
            background: var(--card-bg);
            border-radius: 4px;
            position: relative;
            margin: 15px 0;
        }
        .span-segment {
            position: absolute;
            height: 100%;
            border-radius: 4px;
            display: flex;
            align-items: center;
            padding: 0 8px;
            font-size: 10px;
            color: white;
            overflow: hidden;
        }

        /* Service Map */
        .service-map {
            min-height: 500px;
            background: var(--darker);
            border-radius: 8px;
            position: relative;
            overflow: hidden;
        }
        .service-node {
            position: absolute;
            background: var(--card-bg);
            border: 2px solid var(--card-border);
            border-radius: 10px;
            padding: 15px;
            min-width: 140px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        .service-node:hover { transform: scale(1.05); box-shadow: 0 5px 20px rgba(0,0,0,0.3); }
        .service-node.healthy { border-color: var(--success); }
        .service-node.degraded { border-color: var(--warning); }
        .service-node.critical { border-color: var(--danger); }
        .service-node-icon { font-size: 24px; margin-bottom: 8px; }
        .service-node-name { font-weight: 600; font-size: 12px; }
        .service-node-stats { font-size: 10px; color: var(--text-muted); margin-top: 5px; }

        /* Alert Correlation */
        .alert-group {
            background: var(--darker);
            border-radius: 8px;
            border: 1px solid var(--card-border);
            margin-bottom: 15px;
            overflow: hidden;
        }
        .alert-group-header {
            background: rgba(239, 68, 68, 0.1);
            padding: 15px;
            border-bottom: 1px solid var(--card-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .alert-group-header.root-cause { background: rgba(239, 68, 68, 0.2); }
        .alert-item {
            padding: 12px 15px;
            border-bottom: 1px solid var(--card-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .alert-item:last-child { border-bottom: none; }
        .correlation-line {
            width: 3px;
            background: var(--danger);
            position: absolute;
            left: 30px;
        }

        /* Anomaly Card */
        .anomaly-card {
            background: var(--darker);
            border-radius: 8px;
            padding: 15px;
            border-left: 4px solid var(--warning);
            margin-bottom: 10px;
        }
        .anomaly-card.critical { border-left-color: var(--danger); }
        .anomaly-card.high { border-left-color: var(--warning); }
        .anomaly-card.medium { border-left-color: var(--info); }
        .anomaly-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        .anomaly-metric { font-weight: 600; }
        .anomaly-values { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-top: 10px; }
        .anomaly-value { text-align: center; }
        .anomaly-value-label { font-size: 11px; color: var(--text-muted); }
        .anomaly-value-number { font-size: 18px; font-weight: 600; }
        .anomaly-value-number.expected { color: var(--success); }
        .anomaly-value-number.actual { color: var(--danger); }
        .anomaly-value-number.deviation { color: var(--warning); }

        /* Health Circle */
        .health-circle {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            font-size: 48px;
            font-weight: bold;
            color: white;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }

        .chart-container { position: relative; height: 250px; }

        /* Custom Dashboard */
        .widget {
            background: var(--card-bg);
            border-radius: 8px;
            border: 1px solid var(--card-border);
            padding: 15px;
        }
        .widget-header { font-weight: 600; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; }

        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px 12px; text-align: left; border-bottom: 1px solid var(--card-border); }
        th { font-size: 11px; text-transform: uppercase; color: var(--text-muted); font-weight: 600; }
        tr:hover { background: rgba(99, 102, 241, 0.05); }

        @media (max-width: 1200px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .grid-2, .grid-3 { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <header class="header">
        <div>
            <h1><span>👁️</span> HoL SIEM Observability</h1>
            <p style="font-size: 13px; opacity: 0.9; margin-top: 5px;">Unified monitoring for applications, infrastructure, logs, and traces</p>
        </div>
        <a href="../index.php" class="back-btn">← Back to Dashboard</a>
    </header>

    <div class="main-container">
        <nav class="sidebar">
            <div class="nav-item active" onclick="showModule('overview')">
                <span class="icon">📊</span>
                <span>Overview</span>
            </div>
            <div class="nav-item" onclick="showModule('logs')">
                <span class="icon">📋</span>
                <span>Log Analysis</span>
                <span class="badge"><?= count($logs) ?></span>
            </div>
            <div class="nav-item" onclick="showModule('traces')">
                <span class="icon">🔍</span>
                <span>Distributed Tracing</span>
            </div>
            <div class="nav-item" onclick="showModule('dashboards')">
                <span class="icon">📈</span>
                <span>Custom Dashboards</span>
            </div>
            <div class="nav-item" onclick="showModule('alerts')">
                <span class="icon">🔔</span>
                <span>Alert Correlation</span>
                <span class="badge"><?= count(array_filter($alerts, fn($a) => $a['status'] === 'active')) ?></span>
            </div>
            <div class="nav-item" onclick="showModule('anomalies')">
                <span class="icon">🤖</span>
                <span>AI Anomaly Detection</span>
                <span class="badge"><?= count(array_filter($anomalies, fn($a) => $a['status'] === 'active')) ?></span>
            </div>
            <div class="nav-item" onclick="showModule('dependencies')">
                <span class="icon">🗺️</span>
                <span>Service Map</span>
            </div>
        </nav>

        <main class="content">
            <!-- Overview Module -->
            <div id="overview" class="module active">
                <h2 style="margin-bottom: 20px;">Observability Overview</h2>

                <div class="stats-grid">
                    <div class="stat-card success">
                        <div class="stat-value"><?= $total_hosts + $total_devices ?></div>
                        <div class="stat-label">Monitored Endpoints</div>
                    </div>
                    <div class="stat-card info">
                        <div class="stat-value"><?= $total_apps ?></div>
                        <div class="stat-label">Applications</div>
                    </div>
                    <div class="stat-card warning">
                        <div class="stat-value"><?= count(array_filter($anomalies, fn($a) => $a['status'] === 'active')) ?></div>
                        <div class="stat-label">Active Anomalies</div>
                    </div>
                    <div class="stat-card critical">
                        <div class="stat-value"><?= count(array_filter($alerts, fn($a) => $a['status'] === 'active')) ?></div>
                        <div class="stat-label">Active Alerts</div>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">🏥 Infrastructure Health</div>
                        </div>
                        <div class="card-body" style="text-align: center;">
                            <div class="health-circle" style="background: <?= $health_color ?>;">
                                <div><?= $health_score ?></div>
                                <div style="font-size: 14px;">/100</div>
                            </div>
                            <p style="margin-top: 15px; color: var(--text-muted);">
                                <?php
                                if ($health_score >= 90) echo "Excellent - Systems operating normally";
                                elseif ($health_score >= 75) echo "Good - Minor issues detected";
                                elseif ($health_score >= 50) echo "Fair - Several issues need attention";
                                else echo "Critical - Immediate action required";
                                ?>
                            </p>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">📈 Request Volume (24h)</div>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="requestChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div class="card-title">✅ Observability Features Status</div>
                    </div>
                    <div class="card-body">
                        <div class="grid-3">
                            <div style="display: flex; align-items: center; gap: 10px; padding: 10px; background: var(--darker); border-radius: 8px;">
                                <span style="color: var(--success);">✅</span>
                                <span>Log Aggregation & Analysis</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 10px; padding: 10px; background: var(--darker); border-radius: 8px;">
                                <span style="color: var(--success);">✅</span>
                                <span>Distributed Tracing</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 10px; padding: 10px; background: var(--darker); border-radius: 8px;">
                                <span style="color: var(--success);">✅</span>
                                <span>Custom Dashboards</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 10px; padding: 10px; background: var(--darker); border-radius: 8px;">
                                <span style="color: var(--success);">✅</span>
                                <span>Alert Correlation & RCA</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 10px; padding: 10px; background: var(--darker); border-radius: 8px;">
                                <span style="color: var(--success);">✅</span>
                                <span>AI Anomaly Detection</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 10px; padding: 10px; background: var(--darker); border-radius: 8px;">
                                <span style="color: var(--success);">✅</span>
                                <span>Service Dependency Map</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Log Analysis Module -->
            <div id="logs" class="module">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2>📋 Log Aggregation & Analysis</h2>
                    <div style="display: flex; gap: 10px;">
                        <input type="text" class="search-box" placeholder="Search logs..." id="logSearch" onkeyup="filterLogs()">
                        <button class="btn btn-primary" onclick="refreshLogs()">🔄 Refresh</button>
                        <button class="btn btn-primary" onclick="exportLogs()">📤 Export</button>
                    </div>
                </div>

                <div class="stats-grid" style="grid-template-columns: repeat(5, 1fr);">
                    <div class="stat-card critical">
                        <div class="stat-value"><?= count(array_filter($logs, fn($l) => in_array($l['level'], ['ERROR', 'CRITICAL']))) ?></div>
                        <div class="stat-label">Errors</div>
                    </div>
                    <div class="stat-card warning">
                        <div class="stat-value"><?= count(array_filter($logs, fn($l) => $l['level'] === 'WARN')) ?></div>
                        <div class="stat-label">Warnings</div>
                    </div>
                    <div class="stat-card info">
                        <div class="stat-value"><?= count(array_filter($logs, fn($l) => $l['level'] === 'INFO')) ?></div>
                        <div class="stat-label">Info</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?= count(array_filter($logs, fn($l) => $l['level'] === 'DEBUG')) ?></div>
                        <div class="stat-label">Debug</div>
                    </div>
                    <div class="stat-card success">
                        <div class="stat-value"><?= count($logs) ?></div>
                        <div class="stat-label">Total Logs</div>
                    </div>
                </div>

                <div class="filters">
                    <button class="filter-btn active" onclick="filterLogLevel('all', this)">All</button>
                    <button class="filter-btn" onclick="filterLogLevel('CRITICAL', this)">Critical</button>
                    <button class="filter-btn" onclick="filterLogLevel('ERROR', this)">Error</button>
                    <button class="filter-btn" onclick="filterLogLevel('WARN', this)">Warning</button>
                    <button class="filter-btn" onclick="filterLogLevel('INFO', this)">Info</button>
                    <button class="filter-btn" onclick="filterLogLevel('DEBUG', this)">Debug</button>
                </div>

                <div class="card">
                    <div class="card-body" style="padding: 0;">
                        <div class="log-viewer" id="logViewer">
                            <?php foreach ($logs as $log): ?>
                            <div class="log-entry" data-level="<?= $log['level'] ?>">
                                <span class="log-time"><?= $log['timestamp'] ?></span>
                                <span class="log-level <?= $log['level'] ?>"><?= $log['level'] ?></span>
                                <span class="log-source"><?= $log['source'] ?></span>
                                <span class="log-message"><?= htmlspecialchars($log['message']) ?></span>
                                <span class="log-trace" title="Trace ID"><?= $log['trace_id'] ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">📊 Log Volume by Level</div>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="logLevelChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">🖥️ Top Log Sources</div>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="logSourceChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Distributed Tracing Module -->
            <div id="traces" class="module">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2>🔍 Distributed Tracing</h2>
                    <div style="display: flex; gap: 10px;">
                        <input type="text" class="search-box" placeholder="Search traces..." id="traceSearch">
                        <select class="search-box" id="traceFilter" onchange="filterTraces()">
                            <option value="all">All Status</option>
                            <option value="success">Success</option>
                            <option value="error">Error</option>
                        </select>
                    </div>
                </div>

                <div class="stats-grid" style="grid-template-columns: repeat(4, 1fr);">
                    <div class="stat-card info">
                        <div class="stat-value"><?= count($traces) ?></div>
                        <div class="stat-label">Total Traces</div>
                    </div>
                    <div class="stat-card success">
                        <div class="stat-value"><?= count(array_filter($traces, fn($t) => $t['status'] === 'success')) ?></div>
                        <div class="stat-label">Successful</div>
                    </div>
                    <div class="stat-card critical">
                        <div class="stat-value"><?= count(array_filter($traces, fn($t) => $t['status'] === 'error')) ?></div>
                        <div class="stat-label">Errors</div>
                    </div>
                    <div class="stat-card warning">
                        <div class="stat-value"><?= round(array_sum(array_column($traces, 'duration')) / count($traces)) ?>ms</div>
                        <div class="stat-label">Avg Duration</div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Recent Traces</div>
                    </div>
                    <div class="card-body">
                        <?php foreach ($traces as $trace):
                            $colors = ['#6366f1', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#14b8a6'];
                        ?>
                        <div class="trace-item <?= $trace['status'] ?>" onclick="viewTraceDetail('<?= $trace['trace_id'] ?>')">
                            <div class="trace-header">
                                <div>
                                    <span class="trace-name"><?= $trace['name'] ?></span>
                                    <span style="color: var(--text-muted); font-size: 12px; margin-left: 10px;">ID: <?= $trace['trace_id'] ?></span>
                                </div>
                                <div style="display: flex; gap: 15px; align-items: center;">
                                    <span><?= $trace['spans'] ?> spans</span>
                                    <span style="font-weight: 600;"><?= $trace['duration'] ?>ms</span>
                                    <span class="badge badge-<?= $trace['status'] === 'success' ? 'success' : 'error' ?>"><?= strtoupper($trace['status']) ?></span>
                                </div>
                            </div>
                            <div class="span-bar">
                                <?php
                                $offset = 0;
                                $maxDuration = $trace['duration'];
                                foreach ($trace['services'] as $idx => $service):
                                    $segmentDuration = rand(10, 30);
                                    $width = ($segmentDuration / $maxDuration) * 100;
                                    $left = $offset;
                                    $offset += $width;
                                ?>
                                <div class="span-segment" style="left: <?= $left ?>%; width: <?= min($width, 100 - $left) ?>%; background: <?= $colors[$idx % count($colors)] ?>;">
                                    <?= $service ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="trace-services">
                                <?php foreach ($trace['services'] as $service): ?>
                                <span class="trace-service"><?= $service ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Custom Dashboards Module -->
            <div id="dashboards" class="module">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2>📈 Custom Dashboards</h2>
                    <div style="display: flex; gap: 10px;">
                        <button class="btn btn-primary" onclick="addWidget()">➕ Add Widget</button>
                        <button class="btn btn-primary" onclick="saveDashboard()">💾 Save Dashboard</button>
                    </div>
                </div>

                <div class="grid-3">
                    <div class="widget">
                        <div class="widget-header">
                            <span>🖥️ CPU Usage</span>
                            <button class="btn btn-sm" onclick="editWidget(1)">⚙️</button>
                        </div>
                        <div class="chart-container" style="height: 150px;">
                            <canvas id="cpuChart"></canvas>
                        </div>
                    </div>
                    <div class="widget">
                        <div class="widget-header">
                            <span>💾 Memory Usage</span>
                            <button class="btn btn-sm" onclick="editWidget(2)">⚙️</button>
                        </div>
                        <div class="chart-container" style="height: 150px;">
                            <canvas id="memoryChart"></canvas>
                        </div>
                    </div>
                    <div class="widget">
                        <div class="widget-header">
                            <span>🌐 Network Traffic</span>
                            <button class="btn btn-sm" onclick="editWidget(3)">⚙️</button>
                        </div>
                        <div class="chart-container" style="height: 150px;">
                            <canvas id="networkChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="grid-2" style="margin-top: 20px;">
                    <div class="widget">
                        <div class="widget-header">
                            <span>📊 Service Response Times</span>
                            <button class="btn btn-sm" onclick="editWidget(4)">⚙️</button>
                        </div>
                        <div class="chart-container">
                            <canvas id="responseTimeChart"></canvas>
                        </div>
                    </div>
                    <div class="widget">
                        <div class="widget-header">
                            <span>❌ Error Rate by Service</span>
                            <button class="btn btn-sm" onclick="editWidget(5)">⚙️</button>
                        </div>
                        <div class="chart-container">
                            <canvas id="errorRateChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="widget" style="margin-top: 20px;">
                    <div class="widget-header">
                        <span>🔥 Top Services by Request Volume</span>
                        <button class="btn btn-sm" onclick="editWidget(6)">⚙️</button>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Service</th>
                                <th>Requests</th>
                                <th>Errors</th>
                                <th>Error Rate</th>
                                <th>Avg Latency</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($services as $service):
                                $errorRate = $service['requests'] > 0 ? round(($service['errors'] / $service['requests']) * 100, 2) : 0;
                            ?>
                            <tr>
                                <td><strong><?= $service['name'] ?></strong></td>
                                <td><?= number_format($service['requests']) ?></td>
                                <td><?= number_format($service['errors']) ?></td>
                                <td><?= $errorRate ?>%</td>
                                <td><?= $service['latency'] ?>ms</td>
                                <td><span class="badge badge-<?= $service['status'] === 'healthy' ? 'success' : ($service['status'] === 'degraded' ? 'warning' : 'critical') ?>"><?= strtoupper($service['status']) ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Alert Correlation Module -->
            <div id="alerts" class="module">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2>🔔 Alert Correlation & Root Cause Analysis</h2>
                    <button class="btn btn-primary" onclick="runRCA()">🔍 Run Root Cause Analysis</button>
                </div>

                <div class="stats-grid" style="grid-template-columns: repeat(4, 1fr);">
                    <div class="stat-card critical">
                        <div class="stat-value"><?= count(array_filter($alerts, fn($a) => $a['severity'] === 'critical')) ?></div>
                        <div class="stat-label">Critical Alerts</div>
                    </div>
                    <div class="stat-card warning">
                        <div class="stat-value"><?= count(array_filter($alerts, fn($a) => $a['severity'] === 'high')) ?></div>
                        <div class="stat-label">High Alerts</div>
                    </div>
                    <div class="stat-card info">
                        <div class="stat-value"><?= count(array_filter($alerts, fn($a) => $a['correlated'])) ?></div>
                        <div class="stat-label">Correlated</div>
                    </div>
                    <div class="stat-card success">
                        <div class="stat-value">1</div>
                        <div class="stat-label">Root Causes Found</div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div class="card-title">🎯 Correlated Alert Group - Database Connection Issue</div>
                        <span class="badge badge-critical">ROOT CAUSE IDENTIFIED</span>
                    </div>
                    <div class="card-body">
                        <div style="background: rgba(239, 68, 68, 0.1); padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid var(--danger);">
                            <h4 style="color: var(--danger); margin-bottom: 10px;">🔴 Root Cause: Database Connection Pool Exhausted</h4>
                            <p style="color: var(--text-muted); font-size: 13px;">The primary database (db-primary) has exhausted its connection pool, causing cascading failures across dependent services.</p>
                            <div style="margin-top: 15px;">
                                <strong>Recommended Actions:</strong>
                                <ul style="margin-top: 10px; padding-left: 20px; color: var(--text-muted); font-size: 13px;">
                                    <li>Increase database connection pool size</li>
                                    <li>Identify and close idle connections</li>
                                    <li>Review connection leak in order-service</li>
                                    <li>Scale database horizontally if needed</li>
                                </ul>
                            </div>
                        </div>

                        <?php
                        $correlatedAlerts = array_filter($alerts, fn($a) => $a['correlated']);
                        foreach ($correlatedAlerts as $alert):
                        ?>
                        <div class="alert-item" style="<?= $alert['root_cause'] ? 'background: rgba(239, 68, 68, 0.1);' : '' ?>">
                            <div style="display: flex; align-items: center; gap: 15px;">
                                <?php if ($alert['root_cause']): ?>
                                <span style="color: var(--danger); font-size: 20px;">🎯</span>
                                <?php else: ?>
                                <span style="color: var(--text-muted); font-size: 20px;">↳</span>
                                <?php endif; ?>
                                <div>
                                    <div style="font-weight: 600;"><?= $alert['title'] ?></div>
                                    <div style="font-size: 12px; color: var(--text-muted);"><?= $alert['source'] ?> • <?= $alert['timestamp'] ?></div>
                                </div>
                            </div>
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <span class="badge badge-<?= $alert['severity'] ?>"><?= strtoupper($alert['severity']) ?></span>
                                <?php if ($alert['root_cause']): ?>
                                <span class="badge" style="background: rgba(239, 68, 68, 0.3); color: #f87171;">ROOT CAUSE</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div class="card-title">📋 Other Active Alerts</div>
                    </div>
                    <div class="card-body">
                        <?php
                        $uncorrelatedAlerts = array_filter($alerts, fn($a) => !$a['correlated']);
                        foreach ($uncorrelatedAlerts as $alert):
                        ?>
                        <div class="alert-item">
                            <div>
                                <div style="font-weight: 600;"><?= $alert['title'] ?></div>
                                <div style="font-size: 12px; color: var(--text-muted);"><?= $alert['source'] ?> • <?= $alert['timestamp'] ?></div>
                            </div>
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <span class="badge badge-<?= $alert['severity'] ?>"><?= strtoupper($alert['severity']) ?></span>
                                <span class="badge badge-<?= $alert['status'] === 'active' ? 'error' : 'info' ?>"><?= strtoupper($alert['status']) ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- AI Anomaly Detection Module -->
            <div id="anomalies" class="module">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2>🤖 AI-Powered Anomaly Detection</h2>
                    <div style="display: flex; gap: 10px;">
                        <button class="btn btn-primary" onclick="trainModel()">🧠 Retrain Model</button>
                        <button class="btn btn-primary" onclick="configureThresholds()">⚙️ Configure</button>
                    </div>
                </div>

                <div class="stats-grid" style="grid-template-columns: repeat(4, 1fr);">
                    <div class="stat-card critical">
                        <div class="stat-value"><?= count(array_filter($anomalies, fn($a) => $a['severity'] === 'critical')) ?></div>
                        <div class="stat-label">Critical Anomalies</div>
                    </div>
                    <div class="stat-card warning">
                        <div class="stat-value"><?= count(array_filter($anomalies, fn($a) => $a['severity'] === 'high')) ?></div>
                        <div class="stat-label">High Anomalies</div>
                    </div>
                    <div class="stat-card info">
                        <div class="stat-value"><?= count(array_filter($anomalies, fn($a) => $a['status'] === 'investigating')) ?></div>
                        <div class="stat-label">Investigating</div>
                    </div>
                    <div class="stat-card success">
                        <div class="stat-value">98.5%</div>
                        <div class="stat-label">Model Accuracy</div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div class="card-title">🚨 Detected Anomalies</div>
                    </div>
                    <div class="card-body">
                        <?php foreach ($anomalies as $anomaly): ?>
                        <div class="anomaly-card <?= $anomaly['severity'] ?>">
                            <div class="anomaly-header">
                                <div>
                                    <span class="anomaly-metric"><?= $anomaly['metric'] ?></span>
                                    <span style="color: var(--text-muted); font-size: 12px; margin-left: 10px;"><?= $anomaly['source'] ?></span>
                                </div>
                                <div style="display: flex; gap: 10px; align-items: center;">
                                    <span class="badge badge-<?= $anomaly['severity'] ?>"><?= strtoupper($anomaly['severity']) ?></span>
                                    <span class="badge badge-<?= $anomaly['status'] === 'active' ? 'error' : ($anomaly['status'] === 'resolved' ? 'success' : 'info') ?>"><?= strtoupper($anomaly['status']) ?></span>
                                </div>
                            </div>
                            <div class="anomaly-values">
                                <div class="anomaly-value">
                                    <div class="anomaly-value-label">Expected Range</div>
                                    <div class="anomaly-value-number expected"><?= $anomaly['expected'] ?></div>
                                </div>
                                <div class="anomaly-value">
                                    <div class="anomaly-value-label">Actual Value</div>
                                    <div class="anomaly-value-number actual"><?= $anomaly['actual'] ?></div>
                                </div>
                                <div class="anomaly-value">
                                    <div class="anomaly-value-label">Deviation</div>
                                    <div class="anomaly-value-number deviation"><?= $anomaly['deviation'] ?></div>
                                </div>
                            </div>
                            <div style="margin-top: 15px; display: flex; justify-content: space-between; align-items: center;">
                                <span style="font-size: 12px; color: var(--text-muted);">Detected: <?= $anomaly['detected'] ?></span>
                                <div style="display: flex; gap: 10px;">
                                    <button class="btn btn-sm" onclick="investigateAnomaly(<?= $anomaly['id'] ?>)">🔍 Investigate</button>
                                    <button class="btn btn-sm" onclick="dismissAnomaly(<?= $anomaly['id'] ?>)">✓ Dismiss</button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">📊 Anomaly Detection Over Time</div>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="anomalyTrendChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">🎯 Anomalies by Category</div>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="anomalyCategoryChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Service Dependency Map Module -->
            <div id="dependencies" class="module">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2>🗺️ Service Dependency Map</h2>
                    <div style="display: flex; gap: 10px;">
                        <button class="btn btn-primary" onclick="refreshMap()">🔄 Refresh</button>
                        <button class="btn btn-primary" onclick="exportMap()">📤 Export</button>
                    </div>
                </div>

                <div class="stats-grid" style="grid-template-columns: repeat(4, 1fr);">
                    <div class="stat-card success">
                        <div class="stat-value"><?= count(array_filter($services, fn($s) => $s['status'] === 'healthy')) ?></div>
                        <div class="stat-label">Healthy Services</div>
                    </div>
                    <div class="stat-card warning">
                        <div class="stat-value"><?= count(array_filter($services, fn($s) => $s['status'] === 'degraded')) ?></div>
                        <div class="stat-label">Degraded</div>
                    </div>
                    <div class="stat-card critical">
                        <div class="stat-value"><?= count(array_filter($services, fn($s) => $s['status'] === 'critical')) ?></div>
                        <div class="stat-label">Critical</div>
                    </div>
                    <div class="stat-card info">
                        <div class="stat-value"><?= count($dependencies) ?></div>
                        <div class="stat-label">Dependencies</div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Interactive Service Map</div>
                        <div style="display: flex; gap: 15px; font-size: 12px;">
                            <span><span style="display: inline-block; width: 12px; height: 12px; background: var(--success); border-radius: 50%; margin-right: 5px;"></span>Healthy</span>
                            <span><span style="display: inline-block; width: 12px; height: 12px; background: var(--warning); border-radius: 50%; margin-right: 5px;"></span>Degraded</span>
                            <span><span style="display: inline-block; width: 12px; height: 12px; background: var(--danger); border-radius: 50%; margin-right: 5px;"></span>Critical</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="service-map" id="serviceMap">
                            <svg width="100%" height="500" id="connectionsSvg" style="position: absolute; top: 0; left: 0; pointer-events: none;"></svg>

                            <!-- Service Nodes positioned manually for demo -->
                            <div class="service-node healthy" style="left: 50%; top: 20px; transform: translateX(-50%);" onclick="showServiceDetails('web-frontend')">
                                <div class="service-node-icon">🌐</div>
                                <div class="service-node-name">Web Frontend</div>
                                <div class="service-node-stats">8,500 req/s</div>
                            </div>

                            <div class="service-node healthy" style="left: 50%; top: 120px; transform: translateX(-50%);" onclick="showServiceDetails('api-gateway')">
                                <div class="service-node-icon">🚪</div>
                                <div class="service-node-name">API Gateway</div>
                                <div class="service-node-stats">15,420 req/s</div>
                            </div>

                            <div class="service-node healthy" style="left: 15%; top: 240px;" onclick="showServiceDetails('auth-service')">
                                <div class="service-node-icon">🔐</div>
                                <div class="service-node-name">Auth Service</div>
                                <div class="service-node-stats">3,200 req/s</div>
                            </div>

                            <div class="service-node degraded" style="left: 38%; top: 240px;" onclick="showServiceDetails('order-service')">
                                <div class="service-node-icon">📦</div>
                                <div class="service-node-name">Order Service</div>
                                <div class="service-node-stats">2,100 req/s • 89 errors</div>
                            </div>

                            <div class="service-node healthy" style="left: 62%; top: 240px;" onclick="showServiceDetails('inventory-service')">
                                <div class="service-node-icon">📋</div>
                                <div class="service-node-name">Inventory Service</div>
                                <div class="service-node-stats">4,500 req/s</div>
                            </div>

                            <div class="service-node critical" style="left: 25%; top: 360px;" onclick="showServiceDetails('payment-service')">
                                <div class="service-node-icon">💳</div>
                                <div class="service-node-name">Payment Service</div>
                                <div class="service-node-stats">1,800 req/s • 156 errors</div>
                            </div>

                            <div class="service-node healthy" style="left: 55%; top: 360px;" onclick="showServiceDetails('notification-service')">
                                <div class="service-node-icon">🔔</div>
                                <div class="service-node-name">Notification Service</div>
                                <div class="service-node-stats">2,800 req/s</div>
                            </div>

                            <div class="service-node critical" style="left: 30%; top: 460px;" onclick="showServiceDetails('db-primary')">
                                <div class="service-node-icon">🗄️</div>
                                <div class="service-node-name">Primary Database</div>
                                <div class="service-node-stats">25,000 req/s • 234 errors</div>
                            </div>

                            <div class="service-node healthy" style="left: 55%; top: 460px;" onclick="showServiceDetails('db-replica')">
                                <div class="service-node-icon">🗄️</div>
                                <div class="service-node-name">Replica Database</div>
                                <div class="service-node-stats">18,000 req/s</div>
                            </div>

                            <div class="service-node healthy" style="left: 80%; top: 360px;" onclick="showServiceDetails('cache')">
                                <div class="service-node-icon">⚡</div>
                                <div class="service-node-name">Redis Cache</div>
                                <div class="service-node-stats">45,000 req/s</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div class="card-title">📊 Service Health Details</div>
                    </div>
                    <div class="card-body">
                        <table>
                            <thead>
                                <tr>
                                    <th>Service</th>
                                    <th>Type</th>
                                    <th>Requests/s</th>
                                    <th>Errors</th>
                                    <th>Latency</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($services as $service): ?>
                                <tr>
                                    <td><strong><?= $service['name'] ?></strong></td>
                                    <td><?= ucfirst($service['type']) ?></td>
                                    <td><?= number_format($service['requests']) ?></td>
                                    <td style="color: <?= $service['errors'] > 50 ? 'var(--danger)' : 'inherit' ?>;"><?= $service['errors'] ?></td>
                                    <td style="color: <?= $service['latency'] > 200 ? 'var(--warning)' : 'inherit' ?>;"><?= $service['latency'] ?>ms</td>
                                    <td><span class="badge badge-<?= $service['status'] === 'healthy' ? 'success' : ($service['status'] === 'degraded' ? 'warning' : 'critical') ?>"><?= strtoupper($service['status']) ?></span></td>
                                    <td>
                                        <button class="btn btn-sm" onclick="showServiceDetails('<?= $service['id'] ?>')">View</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Widget Gallery Modal -->
    <div id="widgetGalleryModal" class="obs-modal">
        <div class="obs-modal-overlay" onclick="closeModal('widgetGalleryModal')"></div>
        <div class="obs-modal-content" style="max-width: 800px;">
            <div class="obs-modal-header">
                <h3>Add Widget</h3>
                <button class="obs-modal-close" onclick="closeModal('widgetGalleryModal')">&times;</button>
            </div>
            <div class="obs-modal-body">
                <p style="color: var(--text-muted); margin-bottom: 20px;">Select a widget type to add to your dashboard</p>

                <div class="widget-gallery-grid">
                    <div class="widget-gallery-item" onclick="selectWidgetType('line-chart')">
                        <div class="widget-gallery-icon">📈</div>
                        <div class="widget-gallery-name">Line Chart</div>
                        <div class="widget-gallery-desc">Time series data visualization</div>
                    </div>
                    <div class="widget-gallery-item" onclick="selectWidgetType('bar-chart')">
                        <div class="widget-gallery-icon">📊</div>
                        <div class="widget-gallery-name">Bar Chart</div>
                        <div class="widget-gallery-desc">Compare values across categories</div>
                    </div>
                    <div class="widget-gallery-item" onclick="selectWidgetType('gauge')">
                        <div class="widget-gallery-icon">🎯</div>
                        <div class="widget-gallery-name">Gauge</div>
                        <div class="widget-gallery-desc">Display single metric with threshold</div>
                    </div>
                    <div class="widget-gallery-item" onclick="selectWidgetType('stat')">
                        <div class="widget-gallery-icon">🔢</div>
                        <div class="widget-gallery-name">Stat Counter</div>
                        <div class="widget-gallery-desc">Big number with trend indicator</div>
                    </div>
                    <div class="widget-gallery-item" onclick="selectWidgetType('pie-chart')">
                        <div class="widget-gallery-icon">🥧</div>
                        <div class="widget-gallery-name">Pie Chart</div>
                        <div class="widget-gallery-desc">Show distribution of values</div>
                    </div>
                    <div class="widget-gallery-item" onclick="selectWidgetType('table')">
                        <div class="widget-gallery-icon">📋</div>
                        <div class="widget-gallery-name">Data Table</div>
                        <div class="widget-gallery-desc">Tabular data display</div>
                    </div>
                    <div class="widget-gallery-item" onclick="selectWidgetType('heatmap')">
                        <div class="widget-gallery-icon">🔥</div>
                        <div class="widget-gallery-name">Heatmap</div>
                        <div class="widget-gallery-desc">Visualize data intensity</div>
                    </div>
                    <div class="widget-gallery-item" onclick="selectWidgetType('alert-list')">
                        <div class="widget-gallery-icon">🚨</div>
                        <div class="widget-gallery-name">Alert List</div>
                        <div class="widget-gallery-desc">Recent alerts and notifications</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Widget Configuration Modal -->
    <div id="widgetConfigModal" class="obs-modal">
        <div class="obs-modal-overlay" onclick="closeModal('widgetConfigModal')"></div>
        <div class="obs-modal-content" style="max-width: 600px;">
            <div class="obs-modal-header">
                <h3 id="widgetConfigTitle">Configure Widget</h3>
                <button class="obs-modal-close" onclick="closeModal('widgetConfigModal')">&times;</button>
            </div>
            <div class="obs-modal-body">
                <form id="widgetConfigForm" onsubmit="saveWidgetConfig(event)">
                    <input type="hidden" id="widgetId" value="">
                    <input type="hidden" id="widgetType" value="">

                    <div class="form-group">
                        <label>Widget Title</label>
                        <input type="text" id="widgetTitle" class="form-input" placeholder="Enter widget title" required>
                    </div>

                    <div class="form-group">
                        <label>Data Source</label>
                        <select id="widgetDataSource" class="form-input">
                            <option value="cpu">CPU Metrics</option>
                            <option value="memory">Memory Metrics</option>
                            <option value="network">Network Traffic</option>
                            <option value="disk">Disk Usage</option>
                            <option value="requests">Request Rate</option>
                            <option value="errors">Error Rate</option>
                            <option value="latency">Response Latency</option>
                            <option value="services">Service Health</option>
                            <option value="alerts">Active Alerts</option>
                            <option value="logs">Log Events</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Time Range</label>
                        <select id="widgetTimeRange" class="form-input">
                            <option value="5m">Last 5 minutes</option>
                            <option value="15m">Last 15 minutes</option>
                            <option value="1h" selected>Last 1 hour</option>
                            <option value="6h">Last 6 hours</option>
                            <option value="24h">Last 24 hours</option>
                            <option value="7d">Last 7 days</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Refresh Interval</label>
                        <select id="widgetRefresh" class="form-input">
                            <option value="10">10 seconds</option>
                            <option value="30" selected>30 seconds</option>
                            <option value="60">1 minute</option>
                            <option value="300">5 minutes</option>
                            <option value="0">Manual only</option>
                        </select>
                    </div>

                    <div class="form-row">
                        <div class="form-group" style="flex: 1;">
                            <label>Width</label>
                            <select id="widgetWidth" class="form-input">
                                <option value="1">1 Column</option>
                                <option value="2" selected>2 Columns</option>
                                <option value="3">3 Columns</option>
                            </select>
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label>Height</label>
                            <select id="widgetHeight" class="form-input">
                                <option value="small">Small</option>
                                <option value="medium" selected>Medium</option>
                                <option value="large">Large</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group" id="thresholdConfig" style="display: none;">
                        <label>Thresholds</label>
                        <div class="threshold-row">
                            <input type="number" id="warnThreshold" class="form-input" placeholder="Warning" style="width: 45%;">
                            <input type="number" id="critThreshold" class="form-input" placeholder="Critical" style="width: 45%;">
                        </div>
                    </div>

                    <div class="obs-modal-footer">
                        <button type="button" class="btn" onclick="closeModal('widgetConfigModal')">Cancel</button>
                        <button type="button" class="btn btn-danger" onclick="deleteWidget()" id="deleteWidgetBtn" style="display: none;">Delete</button>
                        <button type="submit" class="btn btn-primary">Save Widget</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Save Dashboard Modal -->
    <div id="saveDashboardModal" class="obs-modal">
        <div class="obs-modal-overlay" onclick="closeModal('saveDashboardModal')"></div>
        <div class="obs-modal-content" style="max-width: 500px;">
            <div class="obs-modal-header">
                <h3>Save Dashboard</h3>
                <button class="obs-modal-close" onclick="closeModal('saveDashboardModal')">&times;</button>
            </div>
            <div class="obs-modal-body">
                <form id="saveDashboardForm" onsubmit="confirmSaveDashboard(event)">
                    <div class="form-group">
                        <label>Dashboard Name</label>
                        <input type="text" id="dashboardName" class="form-input" placeholder="My Custom Dashboard" value="Infrastructure Overview" required>
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea id="dashboardDescription" class="form-input" rows="3" placeholder="Dashboard description...">Custom observability dashboard for monitoring infrastructure and services</textarea>
                    </div>

                    <div class="form-group">
                        <label>Visibility</label>
                        <select id="dashboardVisibility" class="form-input">
                            <option value="private">Private - Only you</option>
                            <option value="team" selected>Team - Your team members</option>
                            <option value="public">Public - All users</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                            <input type="checkbox" id="setAsDefault" style="width: auto;">
                            <span>Set as default dashboard</span>
                        </label>
                    </div>

                    <div class="save-preview">
                        <h4>Dashboard Preview</h4>
                        <div class="save-preview-content">
                            <div class="save-preview-item"><span>Widgets:</span> <strong id="previewWidgetCount">6</strong></div>
                            <div class="save-preview-item"><span>Layout:</span> <strong>3 columns</strong></div>
                            <div class="save-preview-item"><span>Last Modified:</span> <strong id="previewLastModified">Just now</strong></div>
                        </div>
                    </div>

                    <div class="obs-modal-footer">
                        <button type="button" class="btn" onclick="closeModal('saveDashboardModal')">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Dashboard</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Anomaly Investigation Modal -->
    <div id="investigateModal" class="obs-modal">
        <div class="obs-modal-overlay" onclick="closeModal('investigateModal')"></div>
        <div class="obs-modal-content" style="max-width: 800px;">
            <div class="obs-modal-header">
                <h3>Anomaly Investigation</h3>
                <button class="obs-modal-close" onclick="closeModal('investigateModal')">&times;</button>
            </div>
            <div class="obs-modal-body">
                <div class="investigation-header">
                    <div class="investigation-status">
                        <span class="badge badge-high" id="investigateStatus">INVESTIGATING</span>
                    </div>
                    <div class="investigation-id">ID: <span id="investigateId">ANM-001</span></div>
                </div>

                <div class="investigation-summary">
                    <div class="investigation-metric">
                        <div class="investigation-metric-icon" id="investigateIcon">🖥️</div>
                        <div class="investigation-metric-details">
                            <h4 id="investigateMetric">CPU Usage</h4>
                            <p id="investigateSource">Source: web-server-01</p>
                        </div>
                    </div>
                    <div class="investigation-values">
                        <div class="investigation-value">
                            <span class="label">Expected</span>
                            <span class="value success" id="investigateExpected">25-35%</span>
                        </div>
                        <div class="investigation-value">
                            <span class="label">Actual</span>
                            <span class="value danger" id="investigateActual">89%</span>
                        </div>
                        <div class="investigation-value">
                            <span class="label">Deviation</span>
                            <span class="value warning" id="investigateDeviation">+154%</span>
                        </div>
                    </div>
                </div>

                <div class="investigation-timeline">
                    <h4>Timeline</h4>
                    <div class="timeline-items">
                        <div class="timeline-item">
                            <div class="timeline-dot active"></div>
                            <div class="timeline-content">
                                <div class="timeline-time" id="investigateDetectedTime">2025-01-26 11:30:00</div>
                                <div class="timeline-text">Anomaly detected by AI model</div>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-dot"></div>
                            <div class="timeline-content">
                                <div class="timeline-time" id="investigateStartTime">Now</div>
                                <div class="timeline-text">Investigation started</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="investigation-analysis">
                    <h4>AI Analysis</h4>
                    <div class="analysis-content" id="investigateAnalysis">
                        <p><strong>Probable Cause:</strong> Sudden spike in incoming requests causing resource contention.</p>
                        <p><strong>Related Events:</strong></p>
                        <ul>
                            <li>API Gateway saw 300% increase in traffic at 11:28:00</li>
                            <li>Memory usage also elevated on same host</li>
                            <li>No recent deployments detected</li>
                        </ul>
                        <p><strong>Recommendation:</strong> Check for potential DDoS attack or viral content causing traffic spike. Consider scaling horizontally.</p>
                    </div>
                </div>

                <div class="investigation-actions-section">
                    <h4>Actions</h4>
                    <div class="action-buttons-grid">
                        <button class="action-btn" onclick="runDiagnostics()">
                            <span class="action-icon">🔍</span>
                            <span class="action-label">Run Diagnostics</span>
                        </button>
                        <button class="action-btn" onclick="viewRelatedLogs()">
                            <span class="action-icon">📋</span>
                            <span class="action-label">View Related Logs</span>
                        </button>
                        <button class="action-btn" onclick="viewMetricHistory()">
                            <span class="action-icon">📈</span>
                            <span class="action-label">Metric History</span>
                        </button>
                        <button class="action-btn" onclick="createIncident()">
                            <span class="action-icon">🎫</span>
                            <span class="action-label">Create Incident</span>
                        </button>
                        <button class="action-btn" onclick="notifyTeam()">
                            <span class="action-icon">📧</span>
                            <span class="action-label">Notify Team</span>
                        </button>
                        <button class="action-btn" onclick="addToWatchlist()">
                            <span class="action-icon">👁️</span>
                            <span class="action-label">Add to Watchlist</span>
                        </button>
                    </div>
                </div>

                <div class="form-group" style="margin-top: 20px;">
                    <label>Investigation Notes</label>
                    <textarea id="investigationNotes" class="form-input" rows="3" placeholder="Add notes about this investigation..."></textarea>
                </div>

                <div class="obs-modal-footer">
                    <button type="button" class="btn" onclick="closeModal('investigateModal')">Close</button>
                    <button type="button" class="btn btn-warning" onclick="escalateAnomaly()">Escalate</button>
                    <button type="button" class="btn btn-success" onclick="resolveFromInvestigation()">Mark Resolved</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Anomaly Dismiss Modal -->
    <div id="dismissModal" class="obs-modal">
        <div class="obs-modal-overlay" onclick="closeModal('dismissModal')"></div>
        <div class="obs-modal-content" style="max-width: 500px;">
            <div class="obs-modal-header">
                <h3>Dismiss Anomaly</h3>
                <button class="obs-modal-close" onclick="closeModal('dismissModal')">&times;</button>
            </div>
            <div class="obs-modal-body">
                <div class="dismiss-warning">
                    <span class="dismiss-warning-icon">⚠️</span>
                    <div>
                        <strong>Are you sure you want to dismiss this anomaly?</strong>
                        <p>Dismissing will remove it from the active anomalies list.</p>
                    </div>
                </div>

                <div class="dismiss-summary">
                    <div class="dismiss-detail">
                        <span class="label">Metric:</span>
                        <span class="value" id="dismissMetric">CPU Usage</span>
                    </div>
                    <div class="dismiss-detail">
                        <span class="label">Source:</span>
                        <span class="value" id="dismissSource">web-server-01</span>
                    </div>
                    <div class="dismiss-detail">
                        <span class="label">Severity:</span>
                        <span class="badge badge-high" id="dismissSeverity">HIGH</span>
                    </div>
                </div>

                <input type="hidden" id="dismissAnomalyId" value="">

                <div class="form-group">
                    <label>Reason for Dismissal <span style="color: var(--danger);">*</span></label>
                    <select id="dismissReason" class="form-input" required>
                        <option value="">Select a reason...</option>
                        <option value="false_positive">False Positive - Normal behavior</option>
                        <option value="expected">Expected - Planned maintenance/deployment</option>
                        <option value="known_issue">Known Issue - Already being addressed</option>
                        <option value="resolved">Self-Resolved - Issue no longer present</option>
                        <option value="duplicate">Duplicate - Already reported elsewhere</option>
                        <option value="not_actionable">Not Actionable - Outside our control</option>
                        <option value="other">Other</option>
                    </select>
                </div>

                <div class="form-group" id="otherReasonGroup" style="display: none;">
                    <label>Please specify</label>
                    <input type="text" id="otherReason" class="form-input" placeholder="Enter reason...">
                </div>

                <div class="form-group">
                    <label>Additional Comments</label>
                    <textarea id="dismissComments" class="form-input" rows="2" placeholder="Optional comments..."></textarea>
                </div>

                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                        <input type="checkbox" id="suppressSimilar" style="width: auto;">
                        <span>Suppress similar anomalies for 24 hours</span>
                    </label>
                </div>

                <div class="obs-modal-footer">
                    <button type="button" class="btn" onclick="closeModal('dismissModal')">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="confirmDismiss()">Dismiss Anomaly</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Toast -->
    <div id="toast" class="obs-toast"></div>

    <style>
    /* Modal Styles */
    .obs-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 1000;
    }
    .obs-modal.active { display: flex; align-items: center; justify-content: center; }
    .obs-modal-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        backdrop-filter: blur(4px);
    }
    .obs-modal-content {
        position: relative;
        background: var(--card-bg);
        border-radius: 12px;
        border: 1px solid var(--card-border);
        width: 90%;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5);
    }
    .obs-modal-header {
        padding: 20px;
        border-bottom: 1px solid var(--card-border);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .obs-modal-header h3 { margin: 0; font-size: 18px; }
    .obs-modal-close {
        background: none;
        border: none;
        color: var(--text-muted);
        font-size: 24px;
        cursor: pointer;
        padding: 0 5px;
    }
    .obs-modal-close:hover { color: var(--text); }
    .obs-modal-body { padding: 20px; }
    .obs-modal-footer {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid var(--card-border);
    }

    /* Widget Gallery Styles */
    .widget-gallery-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 15px;
    }
    .widget-gallery-item {
        background: var(--darker);
        border: 2px solid var(--card-border);
        border-radius: 10px;
        padding: 20px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s;
    }
    .widget-gallery-item:hover {
        border-color: var(--primary);
        transform: translateY(-3px);
        box-shadow: 0 5px 20px rgba(99, 102, 241, 0.2);
    }
    .widget-gallery-item.selected {
        border-color: var(--primary);
        background: rgba(99, 102, 241, 0.1);
    }
    .widget-gallery-icon { font-size: 32px; margin-bottom: 10px; }
    .widget-gallery-name { font-weight: 600; margin-bottom: 5px; }
    .widget-gallery-desc { font-size: 11px; color: var(--text-muted); }

    /* Form Styles */
    .form-group { margin-bottom: 15px; }
    .form-group label {
        display: block;
        margin-bottom: 6px;
        font-size: 13px;
        font-weight: 500;
        color: var(--text);
    }
    .form-input {
        width: 100%;
        padding: 10px 12px;
        background: var(--darker);
        border: 1px solid var(--card-border);
        border-radius: 6px;
        color: var(--text);
        font-size: 14px;
    }
    .form-input:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    }
    .form-row { display: flex; gap: 15px; }
    .threshold-row { display: flex; gap: 10px; }

    /* Button Styles */
    .btn-danger { background: var(--danger); color: white; }
    .btn-danger:hover { background: #dc2626; }

    /* Save Preview */
    .save-preview {
        background: var(--darker);
        border-radius: 8px;
        padding: 15px;
        margin-top: 20px;
    }
    .save-preview h4 {
        margin: 0 0 12px 0;
        font-size: 13px;
        color: var(--text-muted);
        text-transform: uppercase;
    }
    .save-preview-content { display: flex; gap: 20px; }
    .save-preview-item {
        font-size: 13px;
        color: var(--text-muted);
    }
    .save-preview-item strong { color: var(--text); }

    /* Toast */
    .obs-toast {
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
    .obs-toast.show {
        transform: translateY(0);
        opacity: 1;
    }
    .obs-toast.error { background: var(--danger); }

    /* Dynamic Widget Container */
    .dynamic-widgets {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
        margin-top: 20px;
    }
    .dynamic-widget {
        background: var(--card-bg);
        border-radius: 8px;
        border: 1px solid var(--card-border);
        padding: 15px;
        position: relative;
    }
    .dynamic-widget.width-1 { grid-column: span 1; }
    .dynamic-widget.width-2 { grid-column: span 2; }
    .dynamic-widget.width-3 { grid-column: span 3; }
    .widget-drag-handle {
        position: absolute;
        top: 10px;
        left: 10px;
        cursor: move;
        color: var(--text-muted);
        opacity: 0;
        transition: opacity 0.2s;
    }
    .dynamic-widget:hover .widget-drag-handle { opacity: 1; }

    /* Investigation Modal Styles */
    .investigation-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 1px solid var(--card-border);
    }
    .investigation-id {
        color: var(--text-muted);
        font-family: monospace;
    }
    .investigation-summary {
        background: var(--darker);
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
    }
    .investigation-metric {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 20px;
    }
    .investigation-metric-icon {
        font-size: 40px;
        background: rgba(99, 102, 241, 0.2);
        padding: 15px;
        border-radius: 12px;
    }
    .investigation-metric-details h4 {
        margin: 0 0 5px 0;
        font-size: 18px;
    }
    .investigation-metric-details p {
        margin: 0;
        color: var(--text-muted);
        font-size: 13px;
    }
    .investigation-values {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 15px;
    }
    .investigation-value {
        text-align: center;
        padding: 15px;
        background: var(--card-bg);
        border-radius: 8px;
    }
    .investigation-value .label {
        display: block;
        font-size: 11px;
        color: var(--text-muted);
        margin-bottom: 5px;
        text-transform: uppercase;
    }
    .investigation-value .value {
        font-size: 20px;
        font-weight: 700;
    }
    .investigation-value .value.success { color: var(--success); }
    .investigation-value .value.danger { color: var(--danger); }
    .investigation-value .value.warning { color: var(--warning); }

    .investigation-timeline {
        margin-bottom: 20px;
    }
    .investigation-timeline h4 {
        margin: 0 0 15px 0;
        font-size: 14px;
        color: var(--text-muted);
        text-transform: uppercase;
    }
    .timeline-items {
        padding-left: 20px;
        border-left: 2px solid var(--card-border);
    }
    .timeline-item {
        position: relative;
        padding-bottom: 15px;
        padding-left: 20px;
    }
    .timeline-dot {
        position: absolute;
        left: -27px;
        top: 2px;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: var(--card-border);
        border: 2px solid var(--darker);
    }
    .timeline-dot.active {
        background: var(--primary);
    }
    .timeline-time {
        font-size: 12px;
        color: var(--text-muted);
        margin-bottom: 3px;
    }
    .timeline-text {
        font-size: 13px;
    }

    .investigation-analysis {
        margin-bottom: 20px;
    }
    .investigation-analysis h4 {
        margin: 0 0 15px 0;
        font-size: 14px;
        color: var(--text-muted);
        text-transform: uppercase;
    }
    .analysis-content {
        background: var(--darker);
        border-radius: 8px;
        padding: 15px;
        font-size: 13px;
        line-height: 1.6;
    }
    .analysis-content p {
        margin: 0 0 10px 0;
    }
    .analysis-content ul {
        margin: 10px 0;
        padding-left: 20px;
    }
    .analysis-content li {
        margin-bottom: 5px;
        color: var(--text-muted);
    }

    .investigation-actions-section h4 {
        margin: 0 0 15px 0;
        font-size: 14px;
        color: var(--text-muted);
        text-transform: uppercase;
    }
    .action-buttons-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 10px;
    }
    .action-btn {
        background: var(--darker);
        border: 1px solid var(--card-border);
        border-radius: 8px;
        padding: 15px;
        color: var(--text);
        cursor: pointer;
        text-align: center;
        transition: all 0.3s;
    }
    .action-btn:hover {
        border-color: var(--primary);
        background: rgba(99, 102, 241, 0.1);
    }
    .action-icon {
        display: block;
        font-size: 24px;
        margin-bottom: 8px;
    }
    .action-label {
        font-size: 12px;
    }

    /* Dismiss Modal Styles */
    .dismiss-warning {
        display: flex;
        gap: 15px;
        padding: 15px;
        background: rgba(245, 158, 11, 0.1);
        border: 1px solid rgba(245, 158, 11, 0.3);
        border-radius: 8px;
        margin-bottom: 20px;
    }
    .dismiss-warning-icon {
        font-size: 28px;
    }
    .dismiss-warning strong {
        display: block;
        margin-bottom: 5px;
    }
    .dismiss-warning p {
        margin: 0;
        font-size: 13px;
        color: var(--text-muted);
    }
    .dismiss-summary {
        background: var(--darker);
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 20px;
    }
    .dismiss-detail {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 0;
        border-bottom: 1px solid var(--card-border);
    }
    .dismiss-detail:last-child {
        border-bottom: none;
    }
    .dismiss-detail .label {
        color: var(--text-muted);
        font-size: 13px;
    }
    .dismiss-detail .value {
        font-weight: 500;
    }

    .btn-warning {
        background: var(--warning);
        color: #1a1a1a;
    }
    .btn-warning:hover {
        background: #d97706;
    }
    .btn-success {
        background: var(--success);
        color: white;
    }
    .btn-success:hover {
        background: #059669;
    }

    @media (max-width: 768px) {
        .widget-gallery-grid { grid-template-columns: repeat(2, 1fr); }
        .form-row { flex-direction: column; }
        .save-preview-content { flex-direction: column; gap: 10px; }
        .action-buttons-grid { grid-template-columns: repeat(2, 1fr); }
        .investigation-values { grid-template-columns: 1fr; }
    }
    </style>

    <script>
    // Navigation
    function showModule(moduleId) {
        document.querySelectorAll('.module').forEach(m => m.classList.remove('active'));
        document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
        document.getElementById(moduleId).classList.add('active');
        event.target.closest('.nav-item').classList.add('active');

        // Initialize charts when module is shown
        if (moduleId === 'dashboards') initDashboardCharts();
        if (moduleId === 'logs') initLogCharts();
        if (moduleId === 'anomalies') initAnomalyCharts();
    }

    // Log Functions
    function filterLogLevel(level, btn) {
        document.querySelectorAll('.filters .filter-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

        document.querySelectorAll('.log-entry').forEach(entry => {
            if (level === 'all' || entry.dataset.level === level) {
                entry.style.display = 'flex';
            } else {
                entry.style.display = 'none';
            }
        });
    }

    function filterLogs() {
        const search = document.getElementById('logSearch').value.toLowerCase();
        document.querySelectorAll('.log-entry').forEach(entry => {
            entry.style.display = entry.textContent.toLowerCase().includes(search) ? 'flex' : 'none';
        });
    }

    function refreshLogs() { alert('Refreshing logs...'); }
    function exportLogs() { alert('Exporting logs to CSV...'); }

    // Trace Functions
    function viewTraceDetail(traceId) { alert('Opening trace details for: ' + traceId); }
    function filterTraces() { alert('Filtering traces...'); }

    // Dashboard Functions - Widget Management
    let dashboardWidgets = [
        { id: 1, type: 'line-chart', title: 'CPU Usage', dataSource: 'cpu', timeRange: '1h', refresh: 30, width: 1, height: 'medium' },
        { id: 2, type: 'line-chart', title: 'Memory Usage', dataSource: 'memory', timeRange: '1h', refresh: 30, width: 1, height: 'medium' },
        { id: 3, type: 'line-chart', title: 'Network Traffic', dataSource: 'network', timeRange: '1h', refresh: 30, width: 1, height: 'medium' },
        { id: 4, type: 'bar-chart', title: 'Service Response Times', dataSource: 'latency', timeRange: '1h', refresh: 30, width: 2, height: 'medium' },
        { id: 5, type: 'bar-chart', title: 'Error Rate by Service', dataSource: 'errors', timeRange: '1h', refresh: 30, width: 2, height: 'medium' },
        { id: 6, type: 'table', title: 'Top Services by Request Volume', dataSource: 'services', timeRange: '1h', refresh: 60, width: 3, height: 'medium' }
    ];
    let selectedWidgetType = null;
    let editingWidgetId = null;
    let nextWidgetId = 7;

    // Modal Functions
    function openModal(modalId) {
        document.getElementById(modalId).classList.add('active');
    }

    function closeModal(modalId) {
        document.getElementById(modalId).classList.remove('active');
        // Reset form states
        if (modalId === 'widgetGalleryModal') {
            selectedWidgetType = null;
            document.querySelectorAll('.widget-gallery-item').forEach(i => i.classList.remove('selected'));
        }
        if (modalId === 'widgetConfigModal') {
            editingWidgetId = null;
            document.getElementById('widgetConfigForm').reset();
        }
    }

    function showToast(message, isError = false) {
        const toast = document.getElementById('toast');
        toast.textContent = message;
        toast.className = 'obs-toast' + (isError ? ' error' : '');
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 3000);
    }

    // Widget Gallery Functions
    function addWidget() {
        openModal('widgetGalleryModal');
    }

    function selectWidgetType(type) {
        selectedWidgetType = type;
        document.querySelectorAll('.widget-gallery-item').forEach(item => {
            item.classList.remove('selected');
            if (item.onclick.toString().includes("'" + type + "'")) {
                item.classList.add('selected');
            }
        });

        // Close gallery and open configuration
        closeModal('widgetGalleryModal');

        // Setup for new widget
        editingWidgetId = null;
        document.getElementById('widgetId').value = '';
        document.getElementById('widgetType').value = type;
        document.getElementById('widgetTitle').value = getDefaultTitle(type);
        document.getElementById('widgetDataSource').value = getDefaultDataSource(type);
        document.getElementById('widgetTimeRange').value = '1h';
        document.getElementById('widgetRefresh').value = '30';
        document.getElementById('widgetWidth').value = '1';
        document.getElementById('widgetHeight').value = 'medium';
        document.getElementById('widgetConfigTitle').textContent = 'Add New Widget';
        document.getElementById('deleteWidgetBtn').style.display = 'none';

        // Show threshold config for certain types
        document.getElementById('thresholdConfig').style.display =
            ['gauge', 'stat'].includes(type) ? 'block' : 'none';

        openModal('widgetConfigModal');
    }

    function getDefaultTitle(type) {
        const titles = {
            'line-chart': 'New Line Chart',
            'bar-chart': 'New Bar Chart',
            'gauge': 'New Gauge',
            'stat': 'New Counter',
            'pie-chart': 'New Pie Chart',
            'table': 'New Data Table',
            'heatmap': 'New Heatmap',
            'alert-list': 'Recent Alerts'
        };
        return titles[type] || 'New Widget';
    }

    function getDefaultDataSource(type) {
        const sources = {
            'line-chart': 'cpu',
            'bar-chart': 'latency',
            'gauge': 'cpu',
            'stat': 'requests',
            'pie-chart': 'errors',
            'table': 'services',
            'heatmap': 'requests',
            'alert-list': 'alerts'
        };
        return sources[type] || 'cpu';
    }

    function editWidget(id) {
        const widget = dashboardWidgets.find(w => w.id === id);
        if (!widget) {
            showToast('Widget not found', true);
            return;
        }

        editingWidgetId = id;
        document.getElementById('widgetId').value = id;
        document.getElementById('widgetType').value = widget.type;
        document.getElementById('widgetTitle').value = widget.title;
        document.getElementById('widgetDataSource').value = widget.dataSource;
        document.getElementById('widgetTimeRange').value = widget.timeRange;
        document.getElementById('widgetRefresh').value = widget.refresh;
        document.getElementById('widgetWidth').value = widget.width;
        document.getElementById('widgetHeight').value = widget.height;
        document.getElementById('widgetConfigTitle').textContent = 'Edit Widget';
        document.getElementById('deleteWidgetBtn').style.display = 'inline-block';

        // Show threshold config for certain types
        document.getElementById('thresholdConfig').style.display =
            ['gauge', 'stat'].includes(widget.type) ? 'block' : 'none';

        if (widget.warnThreshold) document.getElementById('warnThreshold').value = widget.warnThreshold;
        if (widget.critThreshold) document.getElementById('critThreshold').value = widget.critThreshold;

        openModal('widgetConfigModal');
    }

    function saveWidgetConfig(event) {
        event.preventDefault();

        const widgetData = {
            id: editingWidgetId || nextWidgetId++,
            type: document.getElementById('widgetType').value || selectedWidgetType,
            title: document.getElementById('widgetTitle').value,
            dataSource: document.getElementById('widgetDataSource').value,
            timeRange: document.getElementById('widgetTimeRange').value,
            refresh: parseInt(document.getElementById('widgetRefresh').value),
            width: parseInt(document.getElementById('widgetWidth').value),
            height: document.getElementById('widgetHeight').value
        };

        // Add thresholds if applicable
        const warnThreshold = document.getElementById('warnThreshold').value;
        const critThreshold = document.getElementById('critThreshold').value;
        if (warnThreshold) widgetData.warnThreshold = parseFloat(warnThreshold);
        if (critThreshold) widgetData.critThreshold = parseFloat(critThreshold);

        if (editingWidgetId) {
            // Update existing widget
            const index = dashboardWidgets.findIndex(w => w.id === editingWidgetId);
            if (index !== -1) {
                dashboardWidgets[index] = widgetData;
                showToast('Widget updated successfully');
            }
        } else {
            // Add new widget
            dashboardWidgets.push(widgetData);
            showToast('Widget added successfully');
        }

        closeModal('widgetConfigModal');
        renderDynamicWidgets();
        updateWidgetCount();
    }

    function deleteWidget() {
        if (!editingWidgetId) return;

        if (confirm('Are you sure you want to delete this widget?')) {
            dashboardWidgets = dashboardWidgets.filter(w => w.id !== editingWidgetId);
            closeModal('widgetConfigModal');
            showToast('Widget deleted');
            renderDynamicWidgets();
            updateWidgetCount();
        }
    }

    function updateWidgetCount() {
        document.getElementById('previewWidgetCount').textContent = dashboardWidgets.length;
    }

    // Save Dashboard Functions
    function saveDashboard() {
        updateWidgetCount();
        document.getElementById('previewLastModified').textContent = 'Just now';
        openModal('saveDashboardModal');
    }

    function confirmSaveDashboard(event) {
        event.preventDefault();

        const dashboardData = {
            name: document.getElementById('dashboardName').value,
            description: document.getElementById('dashboardDescription').value,
            visibility: document.getElementById('dashboardVisibility').value,
            isDefault: document.getElementById('setAsDefault').checked,
            widgets: dashboardWidgets,
            savedAt: new Date().toISOString()
        };

        // Simulate saving to localStorage
        localStorage.setItem('obs_dashboard_' + Date.now(), JSON.stringify(dashboardData));

        closeModal('saveDashboardModal');
        showToast('Dashboard "' + dashboardData.name + '" saved successfully!');
    }

    // Render dynamic widgets container
    function renderDynamicWidgets() {
        // Find or create dynamic widgets container
        let container = document.getElementById('dynamicWidgetsContainer');
        if (!container) {
            // Create container after existing widgets
            const dashboardModule = document.getElementById('dashboards');
            const existingContent = dashboardModule.querySelector('.widget:last-of-type');
            container = document.createElement('div');
            container.id = 'dynamicWidgetsContainer';
            container.className = 'dynamic-widgets';
            container.innerHTML = '<h3 style="grid-column: span 3; margin-bottom: 0;">Custom Widgets</h3>';
            if (existingContent && existingContent.parentNode) {
                existingContent.parentNode.insertBefore(container, existingContent.nextSibling);
            }
        }

        // Clear and re-render widgets that were dynamically added (id > 6)
        const dynamicWidgets = dashboardWidgets.filter(w => w.id > 6);
        if (dynamicWidgets.length === 0) {
            container.style.display = 'none';
            return;
        }

        container.style.display = 'grid';
        container.innerHTML = '<h3 style="grid-column: span 3; margin: 20px 0 10px;">Custom Widgets</h3>';

        dynamicWidgets.forEach(widget => {
            const widgetEl = document.createElement('div');
            widgetEl.className = 'dynamic-widget width-' + widget.width;
            widgetEl.innerHTML = `
                <div class="widget-header">
                    <span>${getWidgetIcon(widget.type)} ${widget.title}</span>
                    <button class="btn btn-sm" onclick="editWidget(${widget.id})">⚙️</button>
                </div>
                <div class="chart-container" style="height: ${widget.height === 'small' ? '100px' : widget.height === 'large' ? '300px' : '150px'};">
                    <canvas id="dynamicChart${widget.id}"></canvas>
                </div>
                <div style="margin-top: 10px; font-size: 11px; color: var(--text-muted);">
                    Source: ${widget.dataSource} | Refresh: ${widget.refresh}s
                </div>
            `;
            container.appendChild(widgetEl);

            // Initialize chart for this widget
            setTimeout(() => initDynamicChart(widget), 100);
        });
    }

    function getWidgetIcon(type) {
        const icons = {
            'line-chart': '📈',
            'bar-chart': '📊',
            'gauge': '🎯',
            'stat': '🔢',
            'pie-chart': '🥧',
            'table': '📋',
            'heatmap': '🔥',
            'alert-list': '🚨'
        };
        return icons[type] || '📊';
    }

    function initDynamicChart(widget) {
        const canvas = document.getElementById('dynamicChart' + widget.id);
        if (!canvas) return;

        const ctx = canvas.getContext('2d');

        // Generate sample data
        const labels = ['1m', '2m', '3m', '4m', '5m'];
        const data = Array.from({length: 5}, () => Math.floor(Math.random() * 100));

        if (widget.type === 'pie-chart') {
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Success', 'Warning', 'Error'],
                    datasets: [{ data: [65, 25, 10], backgroundColor: ['#10b981', '#f59e0b', '#ef4444'] }]
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right', labels: { color: '#e2e8f0' } } } }
            });
        } else if (widget.type === 'bar-chart') {
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{ label: widget.title, data: data, backgroundColor: '#6366f1' }]
                },
                options: { ...chartOptions, plugins: { legend: { display: false } } }
            });
        } else {
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{ label: widget.title, data: data, borderColor: '#6366f1', tension: 0.4 }]
                },
                options: { ...chartOptions, plugins: { legend: { display: false } } }
            });
        }
    }

    // Alert Functions
    function runRCA() { alert('Running Root Cause Analysis...'); }

    // Anomaly Functions
    const anomalyData = {
        1: { id: 1, metric: 'CPU Usage', source: 'web-server-01', expected: '25-35%', actual: '89%', deviation: '+154%', severity: 'high', detected: '2025-01-26 11:30:00', status: 'investigating', icon: '🖥️' },
        2: { id: 2, metric: 'Memory Usage', source: 'db-primary', expected: '60-70%', actual: '95%', deviation: '+36%', severity: 'critical', detected: '2025-01-26 11:35:00', status: 'active', icon: '💾' },
        3: { id: 3, metric: 'Network Latency', source: 'api-gateway', expected: '10-20ms', actual: '156ms', deviation: '+680%', severity: 'high', detected: '2025-01-26 11:40:00', status: 'active', icon: '🌐' },
        4: { id: 4, metric: 'Error Rate', source: 'payment-service', expected: '0.1-0.5%', actual: '4.2%', deviation: '+740%', severity: 'critical', detected: '2025-01-26 11:42:00', status: 'active', icon: '❌' },
        5: { id: 5, metric: 'Request Rate', source: 'auth-service', expected: '100-150/s', actual: '45/s', deviation: '-70%', severity: 'medium', detected: '2025-01-26 11:25:00', status: 'resolved', icon: '📊' }
    };

    let currentInvestigatingId = null;

    function trainModel() {
        showToast('AI model retraining initiated. This may take a few minutes...');
        // Simulate training progress
        setTimeout(() => showToast('AI model retrained successfully! Accuracy: 98.7%'), 3000);
    }

    function configureThresholds() {
        alert('Opening threshold configuration panel...');
    }

    function investigateAnomaly(id) {
        const anomaly = anomalyData[id];
        if (!anomaly) {
            showToast('Anomaly not found', true);
            return;
        }

        currentInvestigatingId = id;

        // Populate investigation modal
        document.getElementById('investigateId').textContent = 'ANM-' + String(id).padStart(3, '0');
        document.getElementById('investigateMetric').textContent = anomaly.metric;
        document.getElementById('investigateSource').textContent = 'Source: ' + anomaly.source;
        document.getElementById('investigateIcon').textContent = anomaly.icon;
        document.getElementById('investigateExpected').textContent = anomaly.expected;
        document.getElementById('investigateActual').textContent = anomaly.actual;
        document.getElementById('investigateDeviation').textContent = anomaly.deviation;
        document.getElementById('investigateDetectedTime').textContent = anomaly.detected;
        document.getElementById('investigateStartTime').textContent = new Date().toLocaleTimeString();

        // Update status badge
        const statusBadge = document.getElementById('investigateStatus');
        statusBadge.textContent = anomaly.status.toUpperCase();
        statusBadge.className = 'badge badge-' + (anomaly.status === 'active' ? 'error' : anomaly.status === 'resolved' ? 'success' : 'info');

        // Generate AI analysis based on anomaly type
        const analysisContent = generateAIAnalysis(anomaly);
        document.getElementById('investigateAnalysis').innerHTML = analysisContent;

        // Clear previous notes
        document.getElementById('investigationNotes').value = '';

        openModal('investigateModal');
    }

    function generateAIAnalysis(anomaly) {
        const analyses = {
            'CPU Usage': `
                <p><strong>Probable Cause:</strong> Sudden spike in incoming requests causing CPU resource contention.</p>
                <p><strong>Related Events:</strong></p>
                <ul>
                    <li>API Gateway saw 300% increase in traffic at 11:28:00</li>
                    <li>Memory usage also elevated on same host</li>
                    <li>No recent deployments detected</li>
                </ul>
                <p><strong>Recommendation:</strong> Check for potential DDoS attack or viral content causing traffic spike. Consider scaling horizontally.</p>
            `,
            'Memory Usage': `
                <p><strong>Probable Cause:</strong> Memory leak detected in database connection pooling.</p>
                <p><strong>Related Events:</strong></p>
                <ul>
                    <li>Connection pool size grew from 50 to 247 connections</li>
                    <li>Query execution times increased by 340%</li>
                    <li>Last restart: 14 days ago</li>
                </ul>
                <p><strong>Recommendation:</strong> Schedule immediate restart during low-traffic window. Review connection pool settings and implement connection timeout.</p>
            `,
            'Network Latency': `
                <p><strong>Probable Cause:</strong> Network congestion detected on primary route.</p>
                <p><strong>Related Events:</strong></p>
                <ul>
                    <li>Packet loss increased to 2.3% on primary interface</li>
                    <li>DNS resolution times elevated</li>
                    <li>Upstream provider experiencing issues</li>
                </ul>
                <p><strong>Recommendation:</strong> Enable failover to secondary network path. Contact network provider for status update.</p>
            `,
            'Error Rate': `
                <p><strong>Probable Cause:</strong> Third-party payment gateway experiencing intermittent failures.</p>
                <p><strong>Related Events:</strong></p>
                <ul>
                    <li>Stripe API returning 503 errors at 8% rate</li>
                    <li>Retry queue depth: 1,247 requests</li>
                    <li>Customer complaints increased in last 15 minutes</li>
                </ul>
                <p><strong>Recommendation:</strong> Enable fallback payment processor. Implement circuit breaker pattern. Notify customers of potential delays.</p>
            `,
            'Request Rate': `
                <p><strong>Probable Cause:</strong> Upstream service dependency failure causing reduced traffic.</p>
                <p><strong>Related Events:</strong></p>
                <ul>
                    <li>Load balancer health checks failing for 2 of 5 instances</li>
                    <li>Authentication cache miss rate: 45%</li>
                    <li>Mobile app users reporting login failures</li>
                </ul>
                <p><strong>Recommendation:</strong> Investigate unhealthy instances. Check authentication service connectivity. Review recent deployment changes.</p>
            `
        };
        return analyses[anomaly.metric] || '<p>Analysis pending. AI model is evaluating patterns...</p>';
    }

    function dismissAnomaly(id) {
        const anomaly = anomalyData[id];
        if (!anomaly) {
            showToast('Anomaly not found', true);
            return;
        }

        // Populate dismiss modal
        document.getElementById('dismissAnomalyId').value = id;
        document.getElementById('dismissMetric').textContent = anomaly.metric;
        document.getElementById('dismissSource').textContent = anomaly.source;

        const severityBadge = document.getElementById('dismissSeverity');
        severityBadge.textContent = anomaly.severity.toUpperCase();
        severityBadge.className = 'badge badge-' + anomaly.severity;

        // Reset form
        document.getElementById('dismissReason').value = '';
        document.getElementById('otherReason').value = '';
        document.getElementById('dismissComments').value = '';
        document.getElementById('suppressSimilar').checked = false;
        document.getElementById('otherReasonGroup').style.display = 'none';

        openModal('dismissModal');
    }

    // Show/hide other reason input
    document.addEventListener('DOMContentLoaded', function() {
        const dismissReasonSelect = document.getElementById('dismissReason');
        if (dismissReasonSelect) {
            dismissReasonSelect.addEventListener('change', function() {
                document.getElementById('otherReasonGroup').style.display =
                    this.value === 'other' ? 'block' : 'none';
            });
        }
    });

    function confirmDismiss() {
        const id = document.getElementById('dismissAnomalyId').value;
        const reason = document.getElementById('dismissReason').value;

        if (!reason) {
            showToast('Please select a reason for dismissal', true);
            return;
        }

        if (reason === 'other' && !document.getElementById('otherReason').value.trim()) {
            showToast('Please specify the reason', true);
            return;
        }

        // Update anomaly status
        if (anomalyData[id]) {
            anomalyData[id].status = 'dismissed';
        }

        // Remove from UI
        const anomalyCard = document.querySelector(`.anomaly-card:has(button[onclick="investigateAnomaly(${id})"])`);
        if (anomalyCard) {
            anomalyCard.style.transition = 'all 0.3s';
            anomalyCard.style.opacity = '0';
            anomalyCard.style.transform = 'translateX(100px)';
            setTimeout(() => anomalyCard.remove(), 300);
        }

        closeModal('dismissModal');
        showToast('Anomaly dismissed successfully');

        // Log suppression if selected
        if (document.getElementById('suppressSimilar').checked) {
            showToast('Similar anomalies will be suppressed for 24 hours');
        }
    }

    // Investigation action functions
    function runDiagnostics() {
        showToast('Running diagnostics on ' + anomalyData[currentInvestigatingId]?.source + '...');
        setTimeout(() => showToast('Diagnostics complete. No hardware issues detected.'), 2000);
    }

    function viewRelatedLogs() {
        closeModal('investigateModal');
        showModule('logs');
        showToast('Showing logs filtered by anomaly source');
    }

    function viewMetricHistory() {
        showToast('Loading metric history chart...');
        setTimeout(() => {
            alert('Metric History for ' + anomalyData[currentInvestigatingId]?.metric + '\n\nLast 24 hours:\n- Average: 32%\n- Peak: 89%\n- Minimum: 18%\n- Anomalies detected: 3');
        }, 500);
    }

    function createIncident() {
        const anomaly = anomalyData[currentInvestigatingId];
        if (anomaly) {
            closeModal('investigateModal');
            showToast('Incident INC-' + Date.now().toString().slice(-6) + ' created for ' + anomaly.metric);
        }
    }

    function notifyTeam() {
        showToast('Notification sent to on-call team');
    }

    function addToWatchlist() {
        showToast('Added to watchlist. You will receive updates on this metric.');
    }

    function escalateAnomaly() {
        const anomaly = anomalyData[currentInvestigatingId];
        if (anomaly) {
            anomaly.severity = 'critical';
            closeModal('investigateModal');
            showToast('Anomaly escalated to CRITICAL severity. Incident team notified.');
        }
    }

    function resolveFromInvestigation() {
        const anomaly = anomalyData[currentInvestigatingId];
        if (anomaly) {
            anomaly.status = 'resolved';

            // Update UI
            const anomalyCard = document.querySelector(`.anomaly-card:has(button[onclick="investigateAnomaly(${currentInvestigatingId})"])`);
            if (anomalyCard) {
                const statusBadge = anomalyCard.querySelector('.badge:last-of-type');
                if (statusBadge) {
                    statusBadge.textContent = 'RESOLVED';
                    statusBadge.className = 'badge badge-success';
                }
            }

            closeModal('investigateModal');
            showToast('Anomaly marked as resolved');
        }
    }

    // Service Map Functions
    function refreshMap() { alert('Refreshing service map...'); }
    function exportMap() { alert('Exporting service map...'); }
    function showServiceDetails(serviceId) { alert('Showing details for: ' + serviceId); }

    // Chart Initialization
    const chartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { labels: { color: '#e2e8f0' } } },
        scales: {
            y: { grid: { color: 'rgba(255,255,255,0.1)' }, ticks: { color: '#94a3b8' } },
            x: { grid: { color: 'rgba(255,255,255,0.1)' }, ticks: { color: '#94a3b8' } }
        }
    };

    // Request Chart (Overview)
    new Chart(document.getElementById('requestChart'), {
        type: 'line',
        data: {
            labels: ['00:00', '04:00', '08:00', '12:00', '16:00', '20:00', '24:00'],
            datasets: [{
                label: 'Requests',
                data: [1200, 800, 2500, 4500, 5200, 4800, 3200],
                borderColor: '#6366f1',
                backgroundColor: 'rgba(99, 102, 241, 0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: chartOptions
    });

    function initLogCharts() {
        // Log Level Chart
        new Chart(document.getElementById('logLevelChart'), {
            type: 'doughnut',
            data: {
                labels: ['Error', 'Warning', 'Info', 'Debug'],
                datasets: [{
                    data: [3, 2, 3, 2],
                    backgroundColor: ['#ef4444', '#f59e0b', '#3b82f6', '#6b7280']
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right', labels: { color: '#e2e8f0' } } } }
        });

        // Log Source Chart
        new Chart(document.getElementById('logSourceChart'), {
            type: 'bar',
            data: {
                labels: ['web-server', 'api-gateway', 'db-primary', 'auth-service', 'cache'],
                datasets: [{
                    label: 'Log Count',
                    data: [25, 18, 15, 12, 8],
                    backgroundColor: '#6366f1'
                }]
            },
            options: chartOptions
        });
    }

    function initDashboardCharts() {
        // CPU Chart
        new Chart(document.getElementById('cpuChart'), {
            type: 'line',
            data: {
                labels: ['1m', '2m', '3m', '4m', '5m'],
                datasets: [{ label: 'CPU %', data: [45, 52, 48, 89, 72], borderColor: '#6366f1', tension: 0.4 }]
            },
            options: { ...chartOptions, plugins: { legend: { display: false } } }
        });

        // Memory Chart
        new Chart(document.getElementById('memoryChart'), {
            type: 'line',
            data: {
                labels: ['1m', '2m', '3m', '4m', '5m'],
                datasets: [{ label: 'Memory %', data: [68, 70, 72, 95, 88], borderColor: '#10b981', tension: 0.4 }]
            },
            options: { ...chartOptions, plugins: { legend: { display: false } } }
        });

        // Network Chart
        new Chart(document.getElementById('networkChart'), {
            type: 'line',
            data: {
                labels: ['1m', '2m', '3m', '4m', '5m'],
                datasets: [
                    { label: 'In', data: [120, 150, 180, 200, 165], borderColor: '#3b82f6', tension: 0.4 },
                    { label: 'Out', data: [80, 95, 110, 140, 120], borderColor: '#f59e0b', tension: 0.4 }
                ]
            },
            options: { ...chartOptions, plugins: { legend: { display: false } } }
        });

        // Response Time Chart
        new Chart(document.getElementById('responseTimeChart'), {
            type: 'bar',
            data: {
                labels: ['API Gateway', 'Auth', 'Orders', 'Payment', 'Inventory'],
                datasets: [{ label: 'Latency (ms)', data: [45, 35, 450, 890, 65], backgroundColor: ['#10b981', '#10b981', '#f59e0b', '#ef4444', '#10b981'] }]
            },
            options: chartOptions
        });

        // Error Rate Chart
        new Chart(document.getElementById('errorRateChart'), {
            type: 'bar',
            data: {
                labels: ['API Gateway', 'Auth', 'Orders', 'Payment', 'Inventory'],
                datasets: [{ label: 'Error Rate %', data: [0.15, 0.06, 4.2, 8.7, 0.27], backgroundColor: ['#10b981', '#10b981', '#f59e0b', '#ef4444', '#10b981'] }]
            },
            options: chartOptions
        });
    }

    function initAnomalyCharts() {
        // Anomaly Trend
        new Chart(document.getElementById('anomalyTrendChart'), {
            type: 'line',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{ label: 'Anomalies Detected', data: [3, 5, 2, 8, 12, 4, 5], borderColor: '#f59e0b', backgroundColor: 'rgba(245, 158, 11, 0.1)', fill: true, tension: 0.4 }]
            },
            options: chartOptions
        });

        // Anomaly Category
        new Chart(document.getElementById('anomalyCategoryChart'), {
            type: 'doughnut',
            data: {
                labels: ['CPU', 'Memory', 'Network', 'Error Rate', 'Latency'],
                datasets: [{ data: [15, 22, 18, 30, 15], backgroundColor: ['#6366f1', '#10b981', '#3b82f6', '#ef4444', '#f59e0b'] }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right', labels: { color: '#e2e8f0' } } } }
        });
    }

    // Initialize charts on load
    document.addEventListener('DOMContentLoaded', function() {
        initLogCharts();
        initDashboardCharts();
        initAnomalyCharts();

        // Initialize dynamic widgets container
        const dashboardModule = document.getElementById('dashboards');
        const lastWidget = dashboardModule.querySelector('.widget:last-of-type');
        if (lastWidget) {
            const container = document.createElement('div');
            container.id = 'dynamicWidgetsContainer';
            container.className = 'dynamic-widgets';
            container.style.display = 'none';
            lastWidget.parentNode.insertBefore(container, lastWidget.nextSibling);
        }

        // Update widget count in preview
        updateWidgetCount();
    });

    // Close modals with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.obs-modal.active').forEach(modal => {
                closeModal(modal.id);
            });
        }
    });
    </script>
</body>
</html>
