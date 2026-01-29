<?php
/**
 * Server & Application Monitor (SAM)
 * Advanced server health, application performance, and resource utilization tracking
 */

require_once __DIR__ . '/../classes/Database.php';
$db = Database::getInstance();

// Get monitored applications from database
$applications = $db->fetchAll("SELECT * FROM monitored_applications ORDER BY status, app_name");
$total_apps = count($applications);
$running_apps = count(array_filter($applications, fn($a) => $a['status'] === 'running'));
$error_apps = count(array_filter($applications, fn($a) => $a['status'] === 'error'));

// Get servers from database
$servers = $db->fetchAll("SELECT * FROM hosts ORDER BY hostname LIMIT 50");
$total_servers = count($servers);

// ========== CPU & MEMORY MONITORING DATA ==========
$cpuMemoryData = [
    [
        'server' => 'web-server-01',
        'ip' => '192.168.1.10',
        'cpu_usage' => 68.5,
        'cpu_cores' => 8,
        'memory_total_gb' => 32,
        'memory_used_gb' => 24.8,
        'memory_percent' => 77.5,
        'swap_total_gb' => 8,
        'swap_used_gb' => 2.1,
        'swap_percent' => 26.3,
        'load_average_1m' => 2.45,
        'load_average_5m' => 2.12,
        'load_average_15m' => 1.98,
        'uptime_days' => 45,
        'status' => 'warning'
    ],
    [
        'server' => 'db-server-01',
        'ip' => '192.168.1.20',
        'cpu_usage' => 85.2,
        'cpu_cores' => 16,
        'memory_total_gb' => 64,
        'memory_used_gb' => 58.3,
        'memory_percent' => 91.1,
        'swap_total_gb' => 16,
        'swap_used_gb' => 8.5,
        'swap_percent' => 53.1,
        'load_average_1m' => 12.34,
        'load_average_5m' => 11.87,
        'load_average_15m' => 10.45,
        'uptime_days' => 87,
        'status' => 'critical'
    ],
    [
        'server' => 'app-server-01',
        'ip' => '192.168.1.30',
        'cpu_usage' => 42.3,
        'cpu_cores' => 8,
        'memory_total_gb' => 32,
        'memory_used_gb' => 18.4,
        'memory_percent' => 57.5,
        'swap_total_gb' => 8,
        'swap_used_gb' => 0.5,
        'swap_percent' => 6.3,
        'load_average_1m' => 1.23,
        'load_average_5m' => 1.45,
        'load_average_15m' => 1.67,
        'uptime_days' => 122,
        'status' => 'healthy'
    ],
    [
        'server' => 'cache-server-01',
        'ip' => '192.168.1.40',
        'cpu_usage' => 35.8,
        'cpu_cores' => 4,
        'memory_total_gb' => 16,
        'memory_used_gb' => 12.2,
        'memory_percent' => 76.3,
        'swap_total_gb' => 4,
        'swap_used_gb' => 0.2,
        'swap_percent' => 5.0,
        'load_average_1m' => 0.89,
        'load_average_5m' => 0.92,
        'load_average_15m' => 0.95,
        'uptime_days' => 34,
        'status' => 'healthy'
    ],
    [
        'server' => 'mail-server-01',
        'ip' => '192.168.1.50',
        'cpu_usage' => 25.4,
        'cpu_cores' => 4,
        'memory_total_gb' => 16,
        'memory_used_gb' => 8.9,
        'memory_percent' => 55.6,
        'swap_total_gb' => 4,
        'swap_used_gb' => 0.1,
        'swap_percent' => 2.5,
        'load_average_1m' => 0.45,
        'load_average_5m' => 0.52,
        'load_average_15m' => 0.58,
        'uptime_days' => 156,
        'status' => 'healthy'
    ]
];

// Historical CPU/Memory data for charts (last 24 hours)
$historicalData = [];
for ($i = 23; $i >= 0; $i--) {
    $time = date('H:i', strtotime("-{$i} hours"));
    $historicalData[] = [
        'time' => $time,
        'cpu_web' => rand(50, 80),
        'cpu_db' => rand(70, 95),
        'cpu_app' => rand(30, 60),
        'mem_web' => rand(60, 85),
        'mem_db' => rand(80, 95),
        'mem_app' => rand(40, 70)
    ];
}

// ========== PROCESS MONITORING DATA ==========
$processes = [
    [
        'server' => 'web-server-01',
        'pid' => 1234,
        'process' => 'nginx',
        'user' => 'www-data',
        'cpu_percent' => 15.2,
        'memory_mb' => 450,
        'threads' => 24,
        'status' => 'running',
        'uptime' => '15d 8h',
        'command' => '/usr/sbin/nginx -g daemon off;'
    ],
    [
        'server' => 'web-server-01',
        'pid' => 2345,
        'process' => 'php-fpm',
        'user' => 'www-data',
        'cpu_percent' => 32.8,
        'memory_mb' => 1250,
        'threads' => 48,
        'status' => 'running',
        'uptime' => '15d 8h',
        'command' => 'php-fpm: pool www'
    ],
    [
        'server' => 'db-server-01',
        'pid' => 3456,
        'process' => 'mysqld',
        'user' => 'mysql',
        'cpu_percent' => 68.5,
        'memory_mb' => 8500,
        'threads' => 156,
        'status' => 'running',
        'uptime' => '87d 12h',
        'command' => '/usr/sbin/mysqld'
    ],
    [
        'server' => 'db-server-01',
        'pid' => 4567,
        'process' => 'redis-server',
        'user' => 'redis',
        'cpu_percent' => 8.3,
        'memory_mb' => 2400,
        'threads' => 8,
        'status' => 'running',
        'uptime' => '87d 12h',
        'command' => '/usr/bin/redis-server 127.0.0.1:6379'
    ],
    [
        'server' => 'app-server-01',
        'pid' => 5678,
        'process' => 'java',
        'user' => 'tomcat',
        'cpu_percent' => 28.7,
        'memory_mb' => 3200,
        'threads' => 64,
        'status' => 'running',
        'uptime' => '122d 4h',
        'command' => 'java -jar /opt/tomcat/app.jar'
    ],
    [
        'server' => 'app-server-01',
        'pid' => 6789,
        'process' => 'node',
        'user' => 'nodejs',
        'cpu_percent' => 12.4,
        'memory_mb' => 890,
        'threads' => 12,
        'status' => 'running',
        'uptime' => '45d 18h',
        'command' => 'node /app/server.js'
    ],
    [
        'server' => 'cache-server-01',
        'pid' => 7890,
        'process' => 'memcached',
        'user' => 'memcache',
        'cpu_percent' => 5.2,
        'memory_mb' => 4096,
        'threads' => 4,
        'status' => 'running',
        'uptime' => '34d 6h',
        'command' => '/usr/bin/memcached -m 4096'
    ],
    [
        'server' => 'mail-server-01',
        'pid' => 8901,
        'process' => 'postfix',
        'user' => 'postfix',
        'cpu_percent' => 3.8,
        'memory_mb' => 145,
        'threads' => 8,
        'status' => 'running',
        'uptime' => '156d 2h',
        'command' => '/usr/lib/postfix/sbin/master'
    ]
];

// ========== SERVICE DEPENDENCY MAPPING DATA ==========
$serviceDependencies = [
    [
        'service' => 'Web Application',
        'server' => 'web-server-01',
        'status' => 'running',
        'depends_on' => ['Load Balancer', 'Application Server', 'Cache Server'],
        'dependency_health' => 100,
        'critical_path' => true
    ],
    [
        'service' => 'Load Balancer',
        'server' => 'lb-server-01',
        'status' => 'running',
        'depends_on' => [],
        'dependency_health' => 100,
        'critical_path' => true
    ],
    [
        'service' => 'Application Server',
        'server' => 'app-server-01',
        'status' => 'running',
        'depends_on' => ['Database Server', 'Cache Server', 'Message Queue'],
        'dependency_health' => 100,
        'critical_path' => true
    ],
    [
        'service' => 'Database Server',
        'server' => 'db-server-01',
        'status' => 'running',
        'depends_on' => ['Storage Array'],
        'dependency_health' => 100,
        'critical_path' => true
    ],
    [
        'service' => 'Cache Server',
        'server' => 'cache-server-01',
        'status' => 'running',
        'depends_on' => [],
        'dependency_health' => 100,
        'critical_path' => false
    ],
    [
        'service' => 'Message Queue',
        'server' => 'mq-server-01',
        'status' => 'running',
        'depends_on' => [],
        'dependency_health' => 100,
        'critical_path' => false
    ],
    [
        'service' => 'Storage Array',
        'server' => 'storage-01',
        'status' => 'running',
        'depends_on' => [],
        'dependency_health' => 100,
        'critical_path' => true
    ],
    [
        'service' => 'Email Service',
        'server' => 'mail-server-01',
        'status' => 'running',
        'depends_on' => ['SMTP Relay'],
        'dependency_health' => 100,
        'critical_path' => false
    ]
];

// ========== PERFORMANCE BASELINES & ANOMALY DETECTION ==========
$performanceBaselines = [
    [
        'metric' => 'CPU Usage',
        'server' => 'web-server-01',
        'baseline_avg' => 45.2,
        'current_value' => 68.5,
        'variance_percent' => 51.5,
        'threshold' => 80.0,
        'status' => 'warning',
        'anomaly_detected' => true,
        'detection_time' => date('Y-m-d H:i:s', strtotime('-15 minutes'))
    ],
    [
        'metric' => 'Memory Usage',
        'server' => 'db-server-01',
        'baseline_avg' => 75.3,
        'current_value' => 91.1,
        'variance_percent' => 21.0,
        'threshold' => 85.0,
        'status' => 'critical',
        'anomaly_detected' => true,
        'detection_time' => date('Y-m-d H:i:s', strtotime('-5 minutes'))
    ],
    [
        'metric' => 'Disk I/O',
        'server' => 'db-server-01',
        'baseline_avg' => 1250.5,
        'current_value' => 3450.8,
        'variance_percent' => 176.0,
        'threshold' => 2500.0,
        'status' => 'critical',
        'anomaly_detected' => true,
        'detection_time' => date('Y-m-d H:i:s', strtotime('-2 minutes'))
    ],
    [
        'metric' => 'Network Traffic',
        'server' => 'web-server-01',
        'baseline_avg' => 125.8,
        'current_value' => 142.3,
        'variance_percent' => 13.1,
        'threshold' => 200.0,
        'status' => 'normal',
        'anomaly_detected' => false,
        'detection_time' => null
    ],
    [
        'metric' => 'Response Time',
        'server' => 'app-server-01',
        'baseline_avg' => 85.4,
        'current_value' => 245.7,
        'variance_percent' => 187.7,
        'threshold' => 150.0,
        'status' => 'critical',
        'anomaly_detected' => true,
        'detection_time' => date('Y-m-d H:i:s', strtotime('-8 minutes'))
    ]
];

// ========== CUSTOM APPLICATION TEMPLATES ==========
$applicationTemplates = [
    [
        'template_name' => 'Web Server (LAMP Stack)',
        'description' => 'Linux + Apache + MySQL + PHP stack monitoring',
        'services' => ['apache2', 'mysql', 'php-fpm'],
        'metrics' => ['CPU', 'Memory', 'Disk I/O', 'Network', 'HTTP Requests'],
        'ports' => [80, 443, 3306],
        'health_checks' => ['HTTP 200 Response', 'MySQL Connection', 'PHP-FPM Status'],
        'alert_thresholds' => ['CPU > 80%', 'Memory > 90%', 'Response Time > 500ms'],
        'created_by' => 'Admin',
        'usage_count' => 12
    ],
    [
        'template_name' => 'Database Server (MySQL)',
        'description' => 'MySQL database server monitoring template',
        'services' => ['mysqld'],
        'metrics' => ['CPU', 'Memory', 'Disk I/O', 'Queries/sec', 'Connections', 'Replication Lag'],
        'ports' => [3306],
        'health_checks' => ['MySQL Connection', 'Replication Status', 'Table Locks'],
        'alert_thresholds' => ['CPU > 85%', 'Memory > 95%', 'Slow Queries > 100/min'],
        'created_by' => 'Admin',
        'usage_count' => 8
    ],
    [
        'template_name' => 'Application Server (Java)',
        'description' => 'Java application server (Tomcat/Spring Boot) monitoring',
        'services' => ['java', 'tomcat'],
        'metrics' => ['CPU', 'Memory', 'Heap Usage', 'Thread Count', 'GC Time', 'Request Rate'],
        'ports' => [8080, 8443],
        'health_checks' => ['HTTP Health Endpoint', 'JMX Connection', 'Heap Memory'],
        'alert_thresholds' => ['Heap > 85%', 'GC Time > 10%', 'Thread Count > 500'],
        'created_by' => 'Admin',
        'usage_count' => 15
    ],
    [
        'template_name' => 'Cache Server (Redis)',
        'description' => 'Redis cache server monitoring',
        'services' => ['redis-server'],
        'metrics' => ['CPU', 'Memory', 'Hit Rate', 'Evictions', 'Connected Clients', 'Commands/sec'],
        'ports' => [6379],
        'health_checks' => ['Redis PING', 'Memory Usage', 'Replication Status'],
        'alert_thresholds' => ['Memory > 90%', 'Hit Rate < 80%', 'Evictions > 1000/min'],
        'created_by' => 'Admin',
        'usage_count' => 6
    ],
    [
        'template_name' => 'Message Queue (RabbitMQ)',
        'description' => 'RabbitMQ message broker monitoring',
        'services' => ['rabbitmq-server'],
        'metrics' => ['CPU', 'Memory', 'Queue Length', 'Message Rate', 'Connections', 'Channels'],
        'ports' => [5672, 15672],
        'health_checks' => ['AMQP Connection', 'Management API', 'Cluster Status'],
        'alert_thresholds' => ['Queue Length > 10000', 'Memory > 80%', 'Message Rate < 10/sec'],
        'created_by' => 'Admin',
        'usage_count' => 4
    ],
    [
        'template_name' => 'Container Host (Docker)',
        'description' => 'Docker container host monitoring',
        'services' => ['docker'],
        'metrics' => ['CPU', 'Memory', 'Container Count', 'Image Count', 'Network I/O', 'Disk Usage'],
        'ports' => [2375, 2376],
        'health_checks' => ['Docker Daemon', 'Container Health', 'Volume Space'],
        'alert_thresholds' => ['CPU > 75%', 'Memory > 85%', 'Disk > 80%'],
        'created_by' => 'Admin',
        'usage_count' => 18
    ]
];

// Calculate summary statistics
$critical_servers = count(array_filter($cpuMemoryData, fn($s) => $s['status'] === 'critical'));
$warning_servers = count(array_filter($cpuMemoryData, fn($s) => $s['status'] === 'warning'));
$healthy_servers = count(array_filter($cpuMemoryData, fn($s) => $s['status'] === 'healthy'));
$total_anomalies = count(array_filter($performanceBaselines, fn($b) => $b['anomaly_detected']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server & Application Monitor | SAM</title>
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
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            gap: 20px;
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
            font-size: 14px;
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
        .server-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .server-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            border-left: 5px solid;
        }
        .server-card.healthy { border-color: #4CAF50; }
        .server-card.warning { border-color: #FF9800; }
        .server-card.critical { border-color: #f44336; }
        .server-card h3 {
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
        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
            margin: 5px 0;
        }
        .progress-fill {
            height: 100%;
            border-radius: 4px;
            transition: width 0.3s;
        }
        .progress-fill.healthy { background: #4CAF50; }
        .progress-fill.warning { background: #FF9800; }
        .progress-fill.critical { background: #f44336; }
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .badge-healthy {
            background: #e8f5e9;
            color: #2e7d32;
        }
        .badge-warning {
            background: #fff3e0;
            color: #e65100;
        }
        .badge-critical {
            background: #ffebee;
            color: #c62828;
        }
        .badge-running {
            background: #e3f2fd;
            color: #1565c0;
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
        .chart-container {
            position: relative;
            height: 350px;
            margin: 25px 0;
        }
        .dependency-map {
            display: grid;
            gap: 15px;
            margin-top: 20px;
        }
        .dependency-item {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            border-left: 5px solid #667eea;
        }
        .dependency-item.critical-path {
            border-left-color: #f44336;
        }
        .dependency-item h4 {
            margin: 0 0 10px 0;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .dependency-list {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 10px;
        }
        .dependency-tag {
            background: white;
            padding: 6px 14px;
            border-radius: 15px;
            font-size: 12px;
            border: 1px solid #ddd;
        }
        .template-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 15px;
            border-left: 5px solid #667eea;
        }
        .template-card h4 {
            margin: 0 0 10px 0;
            color: #333;
            font-size: 18px;
        }
        .template-meta {
            display: flex;
            gap: 20px;
            margin: 15px 0;
            flex-wrap: wrap;
        }
        .template-meta-item {
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header-bar">
            <div>
                <h1>💻 Server & Application Monitor</h1>
                <p>Advanced monitoring, performance baselines, and anomaly detection</p>
            </div>
            <a href="../index.php" class="back-btn">← Back to Dashboard</a>
        </div>

        <!-- Summary Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">🖥️</div>
                <div class="stat-info">
                    <div class="stat-number"><?= count($cpuMemoryData) ?></div>
                    <div class="stat-label">Monitored Servers</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-info">
                    <div class="stat-number" style="color: #4CAF50;"><?= $healthy_servers ?></div>
                    <div class="stat-label">Healthy</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">⚠️</div>
                <div class="stat-info">
                    <div class="stat-number" style="color: #FF9800;"><?= $warning_servers ?></div>
                    <div class="stat-label">Warnings</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🚨</div>
                <div class="stat-info">
                    <div class="stat-number" style="color: #f44336;"><?= $critical_servers ?></div>
                    <div class="stat-label">Critical</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🔍</div>
                <div class="stat-info">
                    <div class="stat-number" style="color: #f44336;"><?= $total_anomalies ?></div>
                    <div class="stat-label">Anomalies Detected</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📋</div>
                <div class="stat-info">
                    <div class="stat-number"><?= count($applicationTemplates) ?></div>
                    <div class="stat-label">App Templates</div>
                </div>
            </div>
        </div>

        <!-- Main Content with Tabs -->
        <div class="card">
            <div class="tabs">
                <button class="tab active" onclick="switchTab('overview')">📊 Overview</button>
                <button class="tab" onclick="switchTab('cpu-memory')">💾 CPU & Memory</button>
                <button class="tab" onclick="switchTab('processes')">⚙️ Processes</button>
                <button class="tab" onclick="switchTab('dependencies')">🔗 Dependencies</button>
                <button class="tab" onclick="switchTab('baselines')">📈 Baselines & Anomalies</button>
                <button class="tab" onclick="switchTab('templates')">📝 Templates</button>
            </div>

            <!-- Overview Tab -->
            <div id="overview" class="tab-content active">
                <h2>System Overview</h2>

                <div class="chart-container">
                    <canvas id="overviewChart"></canvas>
                </div>

                <h3 style="margin-top: 30px;">Recent Alerts</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Server</th>
                            <th>Metric</th>
                            <th>Status</th>
                            <th>Message</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_filter($performanceBaselines, fn($b) => $b['anomaly_detected']) as $anomaly): ?>
                        <tr>
                            <td><?= date('H:i:s', strtotime($anomaly['detection_time'])) ?></td>
                            <td><?= htmlspecialchars($anomaly['server']) ?></td>
                            <td><?= htmlspecialchars($anomaly['metric']) ?></td>
                            <td>
                                <span class="badge badge-<?= $anomaly['status'] ?>">
                                    <?= strtoupper($anomaly['status']) ?>
                                </span>
                            </td>
                            <td>
                                <?= $anomaly['metric'] ?> exceeded baseline by <?= number_format($anomaly['variance_percent'], 1) ?>%
                                (<?= number_format($anomaly['baseline_avg'], 1) ?> → <?= number_format($anomaly['current_value'], 1) ?>)
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- CPU & Memory Tab -->
            <div id="cpu-memory" class="tab-content">
                <h2>CPU & Memory Monitoring</h2>

                <div class="chart-container">
                    <canvas id="cpuChart"></canvas>
                </div>

                <div class="chart-container">
                    <canvas id="memoryChart"></canvas>
                </div>

                <h3 style="margin-top: 30px;">Server Resource Details</h3>
                <div class="server-grid">
                    <?php foreach ($cpuMemoryData as $server): ?>
                    <div class="server-card <?= $server['status'] ?>">
                        <h3><?= htmlspecialchars($server['server']) ?></h3>
                        <div style="font-size: 12px; color: #666; margin-bottom: 15px;">
                            <?= htmlspecialchars($server['ip']) ?>
                        </div>

                        <div class="metric-row">
                            <span class="metric-label">CPU Usage:</span>
                            <span class="metric-value" style="color: <?= $server['cpu_usage'] > 80 ? '#f44336' : ($server['cpu_usage'] > 60 ? '#FF9800' : '#4CAF50') ?>;">
                                <?= $server['cpu_usage'] ?>%
                            </span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill <?= $server['cpu_usage'] > 80 ? 'critical' : ($server['cpu_usage'] > 60 ? 'warning' : 'healthy') ?>"
                                 style="width: <?= $server['cpu_usage'] ?>%;"></div>
                        </div>

                        <div class="metric-row" style="margin-top: 15px;">
                            <span class="metric-label">Memory Usage:</span>
                            <span class="metric-value" style="color: <?= $server['memory_percent'] > 85 ? '#f44336' : ($server['memory_percent'] > 70 ? '#FF9800' : '#4CAF50') ?>;">
                                <?= $server['memory_percent'] ?>%
                            </span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill <?= $server['memory_percent'] > 85 ? 'critical' : ($server['memory_percent'] > 70 ? 'warning' : 'healthy') ?>"
                                 style="width: <?= $server['memory_percent'] ?>%;"></div>
                        </div>
                        <div style="font-size: 12px; color: #666; margin-top: 5px;">
                            <?= number_format($server['memory_used_gb'], 1) ?> GB / <?= $server['memory_total_gb'] ?> GB
                        </div>

                        <div class="metric-row" style="margin-top: 15px;">
                            <span class="metric-label">CPU Cores:</span>
                            <span class="metric-value"><?= $server['cpu_cores'] ?></span>
                        </div>

                        <div class="metric-row">
                            <span class="metric-label">Load Average:</span>
                            <span class="metric-value">
                                <?= $server['load_average_1m'] ?> / <?= $server['load_average_5m'] ?> / <?= $server['load_average_15m'] ?>
                            </span>
                        </div>

                        <div class="metric-row">
                            <span class="metric-label">Swap Usage:</span>
                            <span class="metric-value"><?= $server['swap_percent'] ?>%</span>
                        </div>

                        <div class="metric-row">
                            <span class="metric-label">Uptime:</span>
                            <span class="metric-value"><?= $server['uptime_days'] ?> days</span>
                        </div>

                        <div class="metric-row">
                            <span class="metric-label">Status:</span>
                            <span class="badge badge-<?= $server['status'] ?>">
                                <?= strtoupper($server['status']) ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Processes Tab -->
            <div id="processes" class="tab-content">
                <h2>Process Monitoring</h2>

                <div class="chart-container">
                    <canvas id="processChart"></canvas>
                </div>

                <h3 style="margin-top: 30px;">Active Processes</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Server</th>
                            <th>PID</th>
                            <th>Process</th>
                            <th>User</th>
                            <th>CPU %</th>
                            <th>Memory (MB)</th>
                            <th>Threads</th>
                            <th>Uptime</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($processes as $proc): ?>
                        <tr>
                            <td><?= htmlspecialchars($proc['server']) ?></td>
                            <td><?= $proc['pid'] ?></td>
                            <td><strong><?= htmlspecialchars($proc['process']) ?></strong></td>
                            <td><?= htmlspecialchars($proc['user']) ?></td>
                            <td style="color: <?= $proc['cpu_percent'] > 50 ? '#f44336' : '#4CAF50' ?>;">
                                <strong><?= $proc['cpu_percent'] ?>%</strong>
                            </td>
                            <td><?= number_format($proc['memory_mb']) ?></td>
                            <td><?= $proc['threads'] ?></td>
                            <td><?= $proc['uptime'] ?></td>
                            <td>
                                <span class="badge badge-running">
                                    <?= strtoupper($proc['status']) ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="9" style="padding: 5px 14px; font-size: 12px; color: #666; background: #f9f9f9;">
                                <strong>Command:</strong> <?= htmlspecialchars($proc['command']) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Dependencies Tab -->
            <div id="dependencies" class="tab-content">
                <h2>Service Dependency Mapping</h2>

                <p style="color: #666; margin-bottom: 20px;">
                    Visual representation of service dependencies and health status.
                    Critical path services are highlighted in red.
                </p>

                <div class="dependency-map">
                    <?php foreach ($serviceDependencies as $service): ?>
                    <div class="dependency-item <?= $service['critical_path'] ? 'critical-path' : '' ?>">
                        <h4>
                            <span><?= htmlspecialchars($service['service']) ?></span>
                            <span class="badge badge-<?= $service['status'] === 'running' ? 'running' : 'critical' ?>">
                                <?= strtoupper($service['status']) ?>
                            </span>
                            <?php if ($service['critical_path']): ?>
                            <span class="badge" style="background: #ffebee; color: #c62828;">
                                CRITICAL PATH
                            </span>
                            <?php endif; ?>
                        </h4>

                        <div class="metric-row">
                            <span class="metric-label">Server:</span>
                            <span class="metric-value"><?= htmlspecialchars($service['server']) ?></span>
                        </div>

                        <div class="metric-row">
                            <span class="metric-label">Dependency Health:</span>
                            <span class="metric-value" style="color: #4CAF50;">
                                <?= $service['dependency_health'] ?>%
                            </span>
                        </div>

                        <?php if (count($service['depends_on']) > 0): ?>
                        <div style="margin-top: 15px;">
                            <strong style="color: #666; font-size: 13px;">Depends On:</strong>
                            <div class="dependency-list">
                                <?php foreach ($service['depends_on'] as $dep): ?>
                                <div class="dependency-tag">
                                    <?= htmlspecialchars($dep) ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php else: ?>
                        <div style="margin-top: 15px; color: #666; font-size: 13px;">
                            <em>No dependencies (root service)</em>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Baselines & Anomalies Tab -->
            <div id="baselines" class="tab-content">
                <h2>Performance Baselines & Anomaly Detection</h2>

                <p style="color: #666; margin-bottom: 20px;">
                    Automated detection of unusual patterns based on historical performance baselines.
                    Anomalies are detected when current values deviate significantly from established baselines.
                </p>

                <div class="chart-container">
                    <canvas id="baselineChart"></canvas>
                </div>

                <h3 style="margin-top: 30px;">Performance Metrics vs Baselines</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Metric</th>
                            <th>Server</th>
                            <th>Baseline Avg</th>
                            <th>Current Value</th>
                            <th>Variance</th>
                            <th>Threshold</th>
                            <th>Status</th>
                            <th>Anomaly</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($performanceBaselines as $baseline): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($baseline['metric']) ?></strong></td>
                            <td><?= htmlspecialchars($baseline['server']) ?></td>
                            <td><?= number_format($baseline['baseline_avg'], 2) ?></td>
                            <td style="color: <?= $baseline['anomaly_detected'] ? '#f44336' : '#4CAF50' ?>;">
                                <strong><?= number_format($baseline['current_value'], 2) ?></strong>
                            </td>
                            <td style="color: <?= $baseline['variance_percent'] > 50 ? '#f44336' : ($baseline['variance_percent'] > 20 ? '#FF9800' : '#4CAF50') ?>;">
                                <?= number_format($baseline['variance_percent'], 1) ?>%
                            </td>
                            <td><?= number_format($baseline['threshold'], 2) ?></td>
                            <td>
                                <span class="badge badge-<?= $baseline['status'] ?>">
                                    <?= strtoupper($baseline['status']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($baseline['anomaly_detected']): ?>
                                <span class="badge badge-critical">
                                    ⚠️ DETECTED
                                </span>
                                <div style="font-size: 11px; color: #666; margin-top: 5px;">
                                    <?= date('H:i:s', strtotime($baseline['detection_time'])) ?>
                                </div>
                                <?php else: ?>
                                <span class="badge badge-healthy">
                                    ✓ NORMAL
                                </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div style="margin-top: 30px; padding: 20px; background: #fff3e0; border-left: 5px solid #FF9800; border-radius: 8px;">
                    <h4 style="margin: 0 0 10px 0; color: #e65100;">Detection Methodology</h4>
                    <ul style="margin: 0; color: #666; line-height: 1.8;">
                        <li>Baselines calculated from 30-day historical averages</li>
                        <li>Anomalies detected when variance exceeds 50% or value exceeds threshold</li>
                        <li>Critical status when current value exceeds defined threshold</li>
                        <li>Warning status when variance is between 20-50%</li>
                        <li>Real-time monitoring with 30-second check intervals</li>
                    </ul>
                </div>
            </div>

            <!-- Templates Tab -->
            <div id="templates" class="tab-content">
                <h2>Custom Application Templates</h2>

                <p style="color: #666; margin-bottom: 20px;">
                    Pre-configured monitoring templates for common application stacks.
                    Templates include recommended metrics, health checks, and alert thresholds.
                </p>

                <div style="margin-bottom: 20px;">
                    <button style="background: #667eea; color: white; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">
                        + Create New Template
                    </button>
                </div>

                <?php foreach ($applicationTemplates as $template): ?>
                <div class="template-card">
                    <div style="display: flex; justify-content: space-between; align-items: start;">
                        <div style="flex: 1;">
                            <h4><?= htmlspecialchars($template['template_name']) ?></h4>
                            <p style="color: #666; margin: 5px 0 15px 0; font-size: 14px;">
                                <?= htmlspecialchars($template['description']) ?>
                            </p>
                        </div>
                        <div style="text-align: right;">
                            <div class="badge badge-running">
                                <?= $template['usage_count'] ?> Deployments
                            </div>
                        </div>
                    </div>

                    <div class="template-meta">
                        <div class="template-meta-item">
                            <strong>Created by:</strong> <?= htmlspecialchars($template['created_by']) ?>
                        </div>
                        <div class="template-meta-item">
                            <strong>Services:</strong> <?= count($template['services']) ?>
                        </div>
                        <div class="template-meta-item">
                            <strong>Metrics:</strong> <?= count($template['metrics']) ?>
                        </div>
                        <div class="template-meta-item">
                            <strong>Ports:</strong> <?= implode(', ', $template['ports']) ?>
                        </div>
                    </div>

                    <div style="margin: 15px 0;">
                        <strong style="font-size: 13px; color: #666;">Services:</strong>
                        <div class="tag-list">
                            <?php foreach ($template['services'] as $service): ?>
                            <span class="tag"><?= htmlspecialchars($service) ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div style="margin: 15px 0;">
                        <strong style="font-size: 13px; color: #666;">Monitored Metrics:</strong>
                        <div class="tag-list">
                            <?php foreach ($template['metrics'] as $metric): ?>
                            <span class="tag"><?= htmlspecialchars($metric) ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div style="margin: 15px 0;">
                        <strong style="font-size: 13px; color: #666;">Health Checks:</strong>
                        <div class="tag-list">
                            <?php foreach ($template['health_checks'] as $check): ?>
                            <span class="tag" style="background: #e8f5e9; border-color: #4CAF50;">
                                ✓ <?= htmlspecialchars($check) ?>
                            </span>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div style="margin: 15px 0;">
                        <strong style="font-size: 13px; color: #666;">Alert Thresholds:</strong>
                        <div class="tag-list">
                            <?php foreach ($template['alert_thresholds'] as $threshold): ?>
                            <span class="tag" style="background: #fff3e0; border-color: #FF9800;">
                                ⚠️ <?= htmlspecialchars($threshold) ?>
                            </span>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div style="margin-top: 15px; display: flex; gap: 10px;">
                        <button style="background: #667eea; color: white; padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer; font-size: 13px;">
                            Deploy Template
                        </button>
                        <button style="background: white; color: #667eea; padding: 8px 16px; border: 2px solid #667eea; border-radius: 6px; cursor: pointer; font-size: 13px;">
                            Edit Template
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script>
        // Tab switching
        function switchTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });

            // Show selected tab
            document.getElementById(tabName).classList.add('active');
            event.target.classList.add('active');
        }

        // Overview Chart - System Health Over Time
        const overviewCtx = document.getElementById('overviewChart').getContext('2d');
        new Chart(overviewCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($historicalData, 'time')) ?>,
                datasets: [
                    {
                        label: 'CPU - Web Server',
                        data: <?= json_encode(array_column($historicalData, 'cpu_web')) ?>,
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'CPU - DB Server',
                        data: <?= json_encode(array_column($historicalData, 'cpu_db')) ?>,
                        borderColor: '#f44336',
                        backgroundColor: 'rgba(244, 67, 54, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Memory - Web Server',
                        data: <?= json_encode(array_column($historicalData, 'mem_web')) ?>,
                        borderColor: '#4CAF50',
                        backgroundColor: 'rgba(76, 175, 80, 0.1)',
                        tension: 0.4,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'System Resource Usage - Last 24 Hours'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                }
            }
        });

        // CPU Chart
        const cpuCtx = document.getElementById('cpuChart').getContext('2d');
        new Chart(cpuCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($cpuMemoryData, 'server')) ?>,
                datasets: [{
                    label: 'CPU Usage (%)',
                    data: <?= json_encode(array_column($cpuMemoryData, 'cpu_usage')) ?>,
                    backgroundColor: function(context) {
                        const value = context.parsed.y;
                        return value > 80 ? '#f44336' : (value > 60 ? '#FF9800' : '#4CAF50');
                    },
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: 'CPU Usage by Server'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                }
            }
        });

        // Memory Chart
        const memoryCtx = document.getElementById('memoryChart').getContext('2d');
        new Chart(memoryCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($cpuMemoryData, 'server')) ?>,
                datasets: [{
                    label: 'Memory Usage (%)',
                    data: <?= json_encode(array_column($cpuMemoryData, 'memory_percent')) ?>,
                    backgroundColor: function(context) {
                        const value = context.parsed.y;
                        return value > 85 ? '#f44336' : (value > 70 ? '#FF9800' : '#4CAF50');
                    },
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: 'Memory Usage by Server'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                }
            }
        });

        // Process Chart
        const processCtx = document.getElementById('processChart').getContext('2d');
        new Chart(processCtx, {
            type: 'horizontalBar',
            data: {
                labels: <?= json_encode(array_column($processes, 'process')) ?>,
                datasets: [{
                    label: 'CPU Usage (%)',
                    data: <?= json_encode(array_column($processes, 'cpu_percent')) ?>,
                    backgroundColor: '#667eea',
                    borderWidth: 0
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: 'Top Processes by CPU Usage'
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                }
            }
        });

        // Baseline Chart
        const baselineCtx = document.getElementById('baselineChart').getContext('2d');
        new Chart(baselineCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_map(fn($b) => $b['metric'] . ' - ' . $b['server'], $performanceBaselines)) ?>,
                datasets: [
                    {
                        label: 'Baseline',
                        data: <?= json_encode(array_column($performanceBaselines, 'baseline_avg')) ?>,
                        backgroundColor: '#4CAF50',
                        borderWidth: 0
                    },
                    {
                        label: 'Current',
                        data: <?= json_encode(array_column($performanceBaselines, 'current_value')) ?>,
                        backgroundColor: function(context) {
                            const index = context.dataIndex;
                            const baselines = <?= json_encode($performanceBaselines) ?>;
                            return baselines[index].anomaly_detected ? '#f44336' : '#667eea';
                        },
                        borderWidth: 0
                    },
                    {
                        label: 'Threshold',
                        data: <?= json_encode(array_column($performanceBaselines, 'threshold')) ?>,
                        type: 'line',
                        borderColor: '#FF9800',
                        borderWidth: 2,
                        borderDash: [5, 5],
                        fill: false,
                        pointRadius: 0
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top'
                    },
                    title: {
                        display: true,
                        text: 'Performance Baselines vs Current Values'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>
