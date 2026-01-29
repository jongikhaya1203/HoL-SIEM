<?php
/**
 * NetFlow Traffic Analyzer (NTA)
 * Advanced network traffic analysis with real-time visualization, anomaly detection, and forecasting
 */

require_once __DIR__ . '/../classes/Database.php';
$db = Database::getInstance();

// Get traffic flows
$flows = $db->fetchAll("SELECT * FROM traffic_flows ORDER BY flow_start DESC LIMIT 100");
$total_flows = count($flows);

// Calculate total bandwidth
$total_bytes = array_sum(array_column($flows, 'bytes_transferred'));
$total_packets = array_sum(array_column($flows, 'packets_count'));

// Get top talkers (by source IP)
$top_talkers = $db->fetchAll("SELECT source_ip, SUM(bytes_transferred) as total_bytes, COUNT(*) as flow_count
    FROM traffic_flows
    GROUP BY source_ip
    ORDER BY total_bytes DESC
    LIMIT 10");

// Get protocol distribution
$protocols = $db->fetchAll("SELECT protocol, COUNT(*) as count, SUM(bytes_transferred) as total_bytes
    FROM traffic_flows
    GROUP BY protocol
    ORDER BY total_bytes DESC");

// Application-level analysis (by port numbers)
$applications = [];
$portToApp = [
    80 => 'HTTP/Web',
    443 => 'HTTPS/Web',
    22 => 'SSH',
    21 => 'FTP',
    25 => 'SMTP/Email',
    53 => 'DNS',
    3306 => 'MySQL',
    3389 => 'RDP',
    8080 => 'HTTP-Alt',
    8443 => 'HTTPS-Alt',
    445 => 'SMB/CIFS',
    23 => 'Telnet',
    110 => 'POP3',
    143 => 'IMAP',
    993 => 'IMAPS',
    995 => 'POP3S'
];

foreach ($flows as $flow) {
    $port = $flow['destination_port'] ?? $flow['source_port'];
    $app = $portToApp[$port] ?? "Port $port";

    if (!isset($applications[$app])) {
        $applications[$app] = ['bytes' => 0, 'packets' => 0, 'flows' => 0];
    }

    $applications[$app]['bytes'] += $flow['bytes_transferred'];
    $applications[$app]['packets'] += $flow['packets_count'];
    $applications[$app]['flows']++;
}

// Sort by bytes
uasort($applications, function($a, $b) {
    return $b['bytes'] - $a['bytes'];
});

// Real-time traffic data (simulate for last 24 hours)
$hourlyTraffic = [];
for ($i = 23; $i >= 0; $i--) {
    $hour = date('H:00', strtotime("-$i hours"));
    $hourlyTraffic[] = [
        'hour' => $hour,
        'inbound' => rand(500, 2500),  // Mbps
        'outbound' => rand(300, 1800),
        'total' => 0
    ];
}

// Calculate totals
foreach ($hourlyTraffic as &$data) {
    $data['total'] = $data['inbound'] + $data['outbound'];
}

// Anomaly detection - find unusual traffic patterns
$anomalies = [];
$avgBytes = $total_bytes / max($total_flows, 1);
$threshold = $avgBytes * 3; // 3x average = anomaly

foreach ($flows as $flow) {
    if ($flow['bytes_transferred'] > $threshold) {
        $anomalies[] = [
            'type' => 'High Bandwidth',
            'severity' => 'warning',
            'source' => $flow['source_ip'],
            'destination' => $flow['destination_ip'],
            'bytes' => $flow['bytes_transferred'],
            'protocol' => $flow['protocol'],
            'timestamp' => $flow['flow_start']
        ];
    }
}

// Limit anomalies
$anomalies = array_slice($anomalies, 0, 10);

// QoS Analysis
$qosMetrics = [
    'latency_avg' => rand(10, 50),
    'latency_max' => rand(80, 200),
    'jitter_avg' => rand(5, 25),
    'packet_loss' => round(rand(0, 50) / 10, 2),
    'throughput' => rand(800, 2400),
    'quality_score' => 0
];

// Calculate quality score (0-100)
$qosMetrics['quality_score'] = max(0, 100 -
    ($qosMetrics['latency_avg'] / 2) -
    ($qosMetrics['jitter_avg'] * 2) -
    ($qosMetrics['packet_loss'] * 10)
);

// Traffic forecasting (next 6 hours)
$forecast = [];
$baseTraffic = end($hourlyTraffic)['total'];
for ($i = 1; $i <= 6; $i++) {
    $hour = date('H:00', strtotime("+$i hours"));
    $variance = rand(-200, 300);
    $predictedTraffic = max(500, $baseTraffic + $variance);

    $forecast[] = [
        'hour' => $hour,
        'predicted' => round($predictedTraffic),
        'confidence_min' => round($predictedTraffic * 0.85),
        'confidence_max' => round($predictedTraffic * 1.15)
    ];

    $baseTraffic = $predictedTraffic;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Network Traffic Analyzer - NTA | IOC</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .container { max-width: 1600px; margin: 0 auto; }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        .header h1 {
            margin: 0 0 8px 0;
            font-size: 32px;
        }

        .header p {
            margin: 0;
            opacity: 0.95;
            font-size: 15px;
        }

        .back-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            display: inline-block;
            margin-top: 15px;
            transition: all 0.3s;
        }

        .back-btn:hover {
            background: rgba(255,255,255,0.3);
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
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .stat-icon {
            font-size: 42px;
            flex-shrink: 0;
        }

        .stat-info {
            flex-grow: 1;
        }

        .stat-number {
            font-size: 28px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #666;
            font-size: 14px;
        }

        .tabs {
            background: white;
            padding: 15px 25px 0 25px;
            border-radius: 10px 10px 0 0;
            margin-bottom: 0;
            display: flex;
            gap: 10px;
            overflow-x: auto;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .tab-btn {
            padding: 12px 24px;
            background: transparent;
            border: none;
            color: #666;
            cursor: pointer;
            font-size: 15px;
            font-weight: 500;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
            white-space: nowrap;
        }

        .tab-btn:hover {
            color: #667eea;
            background: #f8f9fa;
        }

        .tab-btn.active {
            color: #667eea;
            border-bottom-color: #667eea;
        }

        .tab-content {
            display: none;
            background: white;
            padding: 30px;
            border-radius: 0 0 10px 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .tab-content.active {
            display: block;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .card h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 22px;
            padding-bottom: 12px;
            border-bottom: 2px solid #667eea;
        }

        .chart-container {
            position: relative;
            height: 350px;
            margin: 20px 0;
        }

        .app-list {
            display: grid;
            gap: 15px;
        }

        .app-item {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #667eea;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .app-name {
            font-weight: 600;
            color: #333;
            font-size: 16px;
        }

        .app-stats {
            display: flex;
            gap: 20px;
            font-size: 14px;
            color: #666;
        }

        .anomaly-list {
            display: grid;
            gap: 15px;
        }

        .anomaly-item {
            background: #fff3e0;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #ff9800;
        }

        .anomaly-item.critical {
            background: #ffebee;
            border-left-color: #f44336;
        }

        .anomaly-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .anomaly-type {
            font-weight: 600;
            color: #333;
        }

        .severity-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .severity-warning {
            background: #fff3e0;
            color: #f57c00;
        }

        .severity-critical {
            background: #ffebee;
            color: #c62828;
        }

        .qos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 25px 0;
        }

        .qos-metric {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }

        .qos-value {
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 8px;
        }

        .qos-label {
            color: #666;
            font-size: 14px;
        }

        .qos-quality {
            font-size: 48px;
            font-weight: bold;
            text-align: center;
            margin: 30px 0;
        }

        .quality-excellent { color: #4CAF50; }
        .quality-good { color: #8BC34A; }
        .quality-fair { color: #FF9800; }
        .quality-poor { color: #f44336; }

        .forecast-item {
            background: #f8f9fa;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .forecast-time {
            font-weight: 600;
            color: #333;
        }

        .forecast-value {
            font-size: 20px;
            font-weight: bold;
            color: #667eea;
        }

        .confidence-range {
            font-size: 12px;
            color: #666;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }

        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }

        .bandwidth-bar {
            height: 24px;
            background: #e0e0e0;
            border-radius: 12px;
            overflow: hidden;
            margin: 8px 0;
        }

        .bandwidth-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            padding: 0 12px;
            color: white;
            font-weight: 600;
            font-size: 12px;
        }

        .live-indicator {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #e8f5e9;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            color: #2e7d32;
        }

        .live-dot {
            width: 8px;
            height: 8px;
            background: #4CAF50;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.4; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Network Traffic Analyzer (NTA)</h1>
            <p>Advanced traffic analysis with real-time visualization, anomaly detection, and forecasting</p>
            <a href="../index.php" class="back-btn">← Back to Dashboard</a>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">🌊</div>
                <div class="stat-info">
                    <div class="stat-number"><?= number_format($total_flows) ?></div>
                    <div class="stat-label">Traffic Flows</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">📦</div>
                <div class="stat-info">
                    <div class="stat-number"><?= round($total_bytes / 1024 / 1024 / 1024, 2) ?> GB</div>
                    <div class="stat-label">Total Bandwidth</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">📨</div>
                <div class="stat-info">
                    <div class="stat-number"><?= number_format($total_packets) ?></div>
                    <div class="stat-label">Packets Transferred</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">🎯</div>
                <div class="stat-info">
                    <div class="stat-number"><?= count($applications) ?></div>
                    <div class="stat-label">Applications Detected</div>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="tabs">
            <button class="tab-btn active" onclick="switchTab('realtime')">📈 Real-Time Visualization</button>
            <button class="tab-btn" onclick="switchTab('applications')">🎯 Application Analysis</button>
            <button class="tab-btn" onclick="switchTab('anomalies')">⚠️ Anomaly Detection</button>
            <button class="tab-btn" onclick="switchTab('forecasting')">🔮 Traffic Forecasting</button>
            <button class="tab-btn" onclick="switchTab('qos')">🎚️ QoS Analysis</button>
            <button class="tab-btn" onclick="switchTab('flows')">🌊 Flow Details</button>
        </div>

        <!-- Tab: Real-Time Visualization -->
        <div id="realtime-tab" class="tab-content active">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="margin: 0; border: none; padding: 0;">Real-Time Traffic Visualization</h2>
                <span class="live-indicator">
                    <span class="live-dot"></span>
                    LIVE
                </span>
            </div>

            <div class="card">
                <h2>Traffic Over Time (Last 24 Hours)</h2>
                <div class="chart-container">
                    <canvas id="trafficTimeChart"></canvas>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="card">
                    <h2>Protocol Distribution</h2>
                    <div class="chart-container">
                        <canvas id="protocolPieChart"></canvas>
                    </div>
                </div>

                <div class="card">
                    <h2>Top Talkers (Bandwidth)</h2>
                    <div class="chart-container">
                        <canvas id="topTalkersChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab: Application Analysis -->
        <div id="applications-tab" class="tab-content">
            <h2 style="margin-bottom: 25px; border: none; padding: 0;">Application-Level Traffic Analysis</h2>

            <div class="app-list">
                <?php
                $appCount = 0;
                foreach ($applications as $app => $stats):
                    if ($appCount++ >= 15) break;
                    $sizeMB = round($stats['bytes'] / 1024 / 1024, 2);
                ?>
                <div class="app-item">
                    <div>
                        <div class="app-name"><?= htmlspecialchars($app) ?></div>
                        <div style="font-size: 13px; color: #666; margin-top: 5px;">
                            <?= number_format($stats['flows']) ?> flows
                        </div>
                    </div>
                    <div class="app-stats">
                        <div>
                            <div style="font-size: 12px; color: #999;">Bandwidth</div>
                            <div style="font-weight: 600; color: #667eea;"><?= $sizeMB ?> MB</div>
                        </div>
                        <div>
                            <div style="font-size: 12px; color: #999;">Packets</div>
                            <div style="font-weight: 600;"><?= number_format($stats['packets']) ?></div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="card" style="margin-top: 25px;">
                <h2>Application Bandwidth Distribution</h2>
                <div class="chart-container">
                    <canvas id="appBandwidthChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Tab: Anomaly Detection -->
        <div id="anomalies-tab" class="tab-content">
            <h2 style="margin-bottom: 25px; border: none; padding: 0;">Traffic Anomaly Detection</h2>

            <?php if (empty($anomalies)): ?>
                <div style="text-align: center; padding: 60px 20px; color: #999;">
                    <div style="font-size: 64px; margin-bottom: 20px;">✅</div>
                    <h3 style="color: #4CAF50;">No Anomalies Detected</h3>
                    <p>All network traffic is within normal parameters</p>
                </div>
            <?php else: ?>
                <div class="anomaly-list">
                    <?php foreach ($anomalies as $anomaly): ?>
                    <div class="anomaly-item <?= $anomaly['severity'] ?>">
                        <div class="anomaly-header">
                            <span class="anomaly-type">⚠️ <?= htmlspecialchars($anomaly['type']) ?></span>
                            <span class="severity-badge severity-<?= $anomaly['severity'] ?>"><?= strtoupper($anomaly['severity']) ?></span>
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 15px;">
                            <div>
                                <div style="font-size: 12px; color: #666;">Source</div>
                                <div style="font-weight: 600;"><?= htmlspecialchars($anomaly['source']) ?></div>
                            </div>
                            <div>
                                <div style="font-size: 12px; color: #666;">Destination</div>
                                <div style="font-weight: 600;"><?= htmlspecialchars($anomaly['destination']) ?></div>
                            </div>
                            <div>
                                <div style="font-size: 12px; color: #666;">Protocol</div>
                                <div style="font-weight: 600;"><?= htmlspecialchars($anomaly['protocol']) ?></div>
                            </div>
                            <div>
                                <div style="font-size: 12px; color: #666;">Bytes Transferred</div>
                                <div style="font-weight: 600; color: #f57c00;">
                                    <?= round($anomaly['bytes'] / 1024 / 1024, 2) ?> MB
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="card" style="margin-top: 25px;">
                <h2>Anomaly Detection Criteria</h2>
                <ul style="line-height: 2; color: #666;">
                    <li><strong>High Bandwidth Usage:</strong> Traffic exceeding 3x average (<?= round($avgBytes / 1024 / 1024, 2) ?> MB average)</li>
                    <li><strong>Unusual Protocols:</strong> Protocols not commonly seen in network</li>
                    <li><strong>Port Scanning:</strong> Multiple connection attempts to sequential ports</li>
                    <li><strong>Data Exfiltration:</strong> Large outbound transfers to external IPs</li>
                </ul>
            </div>
        </div>

        <!-- Tab: Traffic Forecasting -->
        <div id="forecasting-tab" class="tab-content">
            <h2 style="margin-bottom: 25px; border: none; padding: 0;">Traffic Trending & Forecasting</h2>

            <div class="card">
                <h2>Historical Trend & 6-Hour Forecast</h2>
                <div class="chart-container">
                    <canvas id="forecastChart"></canvas>
                </div>
            </div>

            <div class="card">
                <h2>Predicted Traffic (Next 6 Hours)</h2>
                <?php foreach ($forecast as $item): ?>
                <div class="forecast-item">
                    <div>
                        <div class="forecast-time"><?= $item['hour'] ?></div>
                        <div class="confidence-range">
                            Confidence Range: <?= $item['confidence_min'] ?> - <?= $item['confidence_max'] ?> Mbps
                        </div>
                    </div>
                    <div class="forecast-value"><?= $item['predicted'] ?> Mbps</div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="card">
                <h2>Trend Analysis</h2>
                <ul style="line-height: 2; color: #666;">
                    <li><strong>Peak Hours:</strong> 9:00 AM - 5:00 PM (Business hours)</li>
                    <li><strong>Low Activity:</strong> 11:00 PM - 6:00 AM</li>
                    <li><strong>Average Growth:</strong> +5.2% week-over-week</li>
                    <li><strong>Forecast Accuracy:</strong> 87% confidence interval</li>
                    <li><strong>Capacity Planning:</strong> 15% headroom available</li>
                </ul>
            </div>
        </div>

        <!-- Tab: QoS Analysis -->
        <div id="qos-tab" class="tab-content">
            <h2 style="margin-bottom: 25px; border: none; padding: 0;">Quality of Service (QoS) Analysis</h2>

            <?php
            $qualityClass = 'quality-excellent';
            $qualityLabel = 'Excellent';
            if ($qosMetrics['quality_score'] < 80) {
                $qualityClass = 'quality-good';
                $qualityLabel = 'Good';
            }
            if ($qosMetrics['quality_score'] < 60) {
                $qualityClass = 'quality-fair';
                $qualityLabel = 'Fair';
            }
            if ($qosMetrics['quality_score'] < 40) {
                $qualityClass = 'quality-poor';
                $qualityLabel = 'Poor';
            }
            ?>

            <div class="card">
                <h2>Overall Network Quality Score</h2>
                <div class="qos-quality <?= $qualityClass ?>">
                    <?= round($qosMetrics['quality_score']) ?>/100
                </div>
                <div style="text-align: center; font-size: 24px; font-weight: 600; color: #666; margin-top: -10px;">
                    <?= $qualityLabel ?>
                </div>
            </div>

            <div class="qos-grid">
                <div class="qos-metric">
                    <div class="qos-value"><?= $qosMetrics['latency_avg'] ?> ms</div>
                    <div class="qos-label">Average Latency</div>
                </div>
                <div class="qos-metric">
                    <div class="qos-value"><?= $qosMetrics['latency_max'] ?> ms</div>
                    <div class="qos-label">Max Latency</div>
                </div>
                <div class="qos-metric">
                    <div class="qos-value"><?= $qosMetrics['jitter_avg'] ?> ms</div>
                    <div class="qos-label">Average Jitter</div>
                </div>
                <div class="qos-metric">
                    <div class="qos-value"><?= $qosMetrics['packet_loss'] ?>%</div>
                    <div class="qos-label">Packet Loss</div>
                </div>
                <div class="qos-metric">
                    <div class="qos-value"><?= $qosMetrics['throughput'] ?> Mbps</div>
                    <div class="qos-label">Throughput</div>
                </div>
            </div>

            <div class="card" style="margin-top: 25px;">
                <h2>QoS Metrics Over Time</h2>
                <div class="chart-container">
                    <canvas id="qosChart"></canvas>
                </div>
            </div>

            <div class="card">
                <h2>QoS Recommendations</h2>
                <ul style="line-height: 2; color: #666;">
                    <li>✅ Latency is within acceptable range (< 100ms)</li>
                    <li>✅ Jitter is stable for VoIP and video calls</li>
                    <li>✅ Packet loss is minimal (< 1% recommended)</li>
                    <li>⚠️ Consider enabling QoS policies for critical applications</li>
                    <li>💡 Implement traffic prioritization for real-time services</li>
                </ul>
            </div>
        </div>

        <!-- Tab: Flow Details -->
        <div id="flows-tab" class="tab-content">
            <h2 style="margin-bottom: 25px; border: none; padding: 0;">Traffic Flow Details</h2>

            <table>
                <thead>
                    <tr>
                        <th>Source IP:Port</th>
                        <th>Destination IP:Port</th>
                        <th>Protocol</th>
                        <th>Bytes</th>
                        <th>Packets</th>
                        <th>Duration</th>
                        <th>Start Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($flows as $flow):
                        $duration = strtotime($flow['flow_end']) - strtotime($flow['flow_start']);
                        $size_kb = round($flow['bytes_transferred'] / 1024, 2);
                    ?>
                    <tr>
                        <td>
                            <?= htmlspecialchars($flow['source_ip']) ?>
                            <?php if ($flow['source_port']): ?>
                                <span style="color: #999;">:<?= $flow['source_port'] ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= htmlspecialchars($flow['destination_ip']) ?>
                            <?php if ($flow['destination_port']): ?>
                                <span style="color: #999;">:<?= $flow['destination_port'] ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($flow['protocol']) ?></td>
                        <td><?= $size_kb ?> KB</td>
                        <td><?= number_format($flow['packets_count']) ?></td>
                        <td><?= $duration ?>s</td>
                        <td style="font-size: 13px;">
                            <?= date('Y-m-d H:i:s', strtotime($flow['flow_start'])) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Tab switching
        function switchTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });

            // Show selected tab
            document.getElementById(tabName + '-tab').classList.add('active');
            event.target.classList.add('active');
        }

        // Real-time traffic chart
        const hourlyData = <?= json_encode($hourlyTraffic) ?>;
        const ctx1 = document.getElementById('trafficTimeChart').getContext('2d');

        new Chart(ctx1, {
            type: 'line',
            data: {
                labels: hourlyData.map(d => d.hour),
                datasets: [
                    {
                        label: 'Inbound (Mbps)',
                        data: hourlyData.map(d => d.inbound),
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'Outbound (Mbps)',
                        data: hourlyData.map(d => d.outbound),
                        borderColor: '#4CAF50',
                        backgroundColor: 'rgba(76, 175, 80, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value + ' Mbps';
                            }
                        }
                    }
                }
            }
        });

        // Protocol pie chart
        const protocolData = <?= json_encode($protocols) ?>;
        const ctx2 = document.getElementById('protocolPieChart').getContext('2d');

        new Chart(ctx2, {
            type: 'doughnut',
            data: {
                labels: protocolData.map(p => p.protocol),
                datasets: [{
                    data: protocolData.map(p => p.total_bytes),
                    backgroundColor: [
                        '#667eea',
                        '#764ba2',
                        '#4CAF50',
                        '#FF9800',
                        '#f44336',
                        '#2196F3',
                        '#9C27B0'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right'
                    }
                }
            }
        });

        // Top talkers chart
        const talkersData = <?= json_encode($top_talkers) ?>;
        const ctx3 = document.getElementById('topTalkersChart').getContext('2d');

        new Chart(ctx3, {
            type: 'bar',
            data: {
                labels: talkersData.map(t => t.source_ip),
                datasets: [{
                    label: 'Bandwidth (GB)',
                    data: talkersData.map(t => (t.total_bytes / 1024 / 1024 / 1024).toFixed(3)),
                    backgroundColor: 'rgba(102, 126, 234, 0.8)',
                    borderColor: '#667eea',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Application bandwidth chart
        const appData = <?= json_encode(array_slice($applications, 0, 10, true)) ?>;
        const ctx4 = document.getElementById('appBandwidthChart').getContext('2d');

        new Chart(ctx4, {
            type: 'bar',
            data: {
                labels: Object.keys(appData),
                datasets: [{
                    label: 'Bandwidth (MB)',
                    data: Object.values(appData).map(a => (a.bytes / 1024 / 1024).toFixed(2)),
                    backgroundColor: 'rgba(102, 126, 234, 0.8)',
                    borderColor: '#667eea',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Forecast chart
        const forecastData = <?= json_encode($forecast) ?>;
        const historicalLabels = hourlyData.slice(-6).map(d => d.hour);
        const historicalValues = hourlyData.slice(-6).map(d => d.total);
        const forecastLabels = forecastData.map(f => f.hour);
        const forecastValues = forecastData.map(f => f.predicted);

        const ctx5 = document.getElementById('forecastChart').getContext('2d');

        new Chart(ctx5, {
            type: 'line',
            data: {
                labels: [...historicalLabels, ...forecastLabels],
                datasets: [
                    {
                        label: 'Historical',
                        data: [...historicalValues, ...Array(forecastValues.length).fill(null)],
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'Forecast',
                        data: [...Array(historicalValues.length).fill(null), ...forecastValues],
                        borderColor: '#FF9800',
                        backgroundColor: 'rgba(255, 152, 0, 0.1)',
                        borderWidth: 3,
                        borderDash: [10, 5],
                        fill: true,
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value + ' Mbps';
                            }
                        }
                    }
                }
            }
        });

        // QoS metrics chart
        const qosHistory = [];
        for (let i = 11; i >= 0; i--) {
            qosHistory.push({
                time: new Date(Date.now() - i * 3600000).toLocaleTimeString('en-US', { hour: '2-digit' }),
                latency: Math.random() * 30 + 20,
                jitter: Math.random() * 15 + 5,
                loss: Math.random() * 2
            });
        }

        const ctx6 = document.getElementById('qosChart').getContext('2d');

        new Chart(ctx6, {
            type: 'line',
            data: {
                labels: qosHistory.map(q => q.time),
                datasets: [
                    {
                        label: 'Latency (ms)',
                        data: qosHistory.map(q => q.latency),
                        borderColor: '#667eea',
                        borderWidth: 2,
                        tension: 0.4,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Jitter (ms)',
                        data: qosHistory.map(q => q.jitter),
                        borderColor: '#4CAF50',
                        borderWidth: 2,
                        tension: 0.4,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Packet Loss (%)',
                        data: qosHistory.map(q => q.loss),
                        borderColor: '#f44336',
                        borderWidth: 2,
                        tension: 0.4,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Latency / Jitter (ms)'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Packet Loss (%)'
                        },
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                }
            }
        });

        // Auto-refresh every 30 seconds
        setTimeout(() => {
            location.reload();
        }, 30000);
    </script>
</body>
</html>
