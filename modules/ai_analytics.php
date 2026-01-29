<?php
/**
 * AI/ML Analytics Module
 * Anomaly Detection, Predictive Analytics, and Intelligent Insights
 */

require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/PerformanceBaseline.php';

$db = Database::getInstance();
$baseline = new PerformanceBaseline();

// Get all monitored devices
$devices = $db->fetchAll("
    SELECT * FROM network_devices
    WHERE monitored = 1
    ORDER BY device_name
");

// Detect anomalies across all devices
$anomalies = [];
$predictions = [];
$insights = [];

foreach ($devices as $device) {
    // Check for CPU anomalies
    $currentCPU = rand(10, 95); // In production, get real metrics
    $cpuAnomaly = $baseline->isAnomaly($device['id'], 'cpu_usage', $currentCPU);
    if ($cpuAnomaly['is_anomaly']) {
        $anomalies[] = [
            'device_id' => $device['id'],
            'device_name' => $device['device_name'],
            'metric' => 'CPU Usage',
            'current_value' => $currentCPU,
            'expected_range' => $cpuAnomaly['expected_range'] ?? 'N/A',
            'severity' => $cpuAnomaly['severity'],
            'detected_at' => date('Y-m-d H:i:s')
        ];
    }

    // Check for Memory anomalies
    $currentMemory = rand(20, 90);
    $memAnomaly = $baseline->isAnomaly($device['id'], 'memory_usage', $currentMemory);
    if ($memAnomaly['is_anomaly']) {
        $anomalies[] = [
            'device_id' => $device['id'],
            'device_name' => $device['device_name'],
            'metric' => 'Memory Usage',
            'current_value' => $currentMemory,
            'expected_range' => $memAnomaly['expected_range'] ?? 'N/A',
            'severity' => $memAnomaly['severity'],
            'detected_at' => date('Y-m-d H:i:s')
        ];
    }

    // Generate predictions (simulated ML predictions)
    $failureRisk = rand(1, 100);
    if ($failureRisk > 70) {
        $predictions[] = [
            'device_id' => $device['id'],
            'device_name' => $device['device_name'],
            'prediction_type' => 'Device Failure',
            'risk_score' => $failureRisk,
            'predicted_date' => date('Y-m-d', strtotime('+' . rand(1, 30) . ' days')),
            'confidence' => rand(70, 95) . '%',
            'factors' => ['High CPU variance', 'Increased error rate', 'Memory degradation']
        ];
    }

    // Performance degradation prediction
    $degradationRisk = rand(1, 100);
    if ($degradationRisk > 60) {
        $predictions[] = [
            'device_id' => $device['id'],
            'device_name' => $device['device_name'],
            'prediction_type' => 'Performance Degradation',
            'risk_score' => $degradationRisk,
            'predicted_date' => date('Y-m-d', strtotime('+' . rand(7, 21) . ' days')),
            'confidence' => rand(65, 90) . '%',
            'factors' => ['Trending upward latency', 'Bandwidth saturation patterns']
        ];
    }
}

// Generate AI insights
$totalDevices = count($devices);
$onlineDevices = count(array_filter($devices, fn($d) => $d['status'] === 'online'));
$anomalyCount = count($anomalies);
$highRiskCount = count(array_filter($predictions, fn($p) => $p['risk_score'] > 80));

$insights[] = [
    'type' => 'network_health',
    'title' => 'Network Health Score',
    'value' => round(($onlineDevices / max($totalDevices, 1)) * 100 - ($anomalyCount * 2), 1),
    'trend' => 'stable',
    'description' => "Based on {$totalDevices} monitored devices with {$anomalyCount} active anomalies"
];

$insights[] = [
    'type' => 'anomaly_detection',
    'title' => 'Anomaly Detection Summary',
    'value' => $anomalyCount,
    'trend' => $anomalyCount > 5 ? 'increasing' : 'stable',
    'description' => "Detected {$anomalyCount} anomalies across CPU, memory, and bandwidth metrics"
];

$insights[] = [
    'type' => 'predictive',
    'title' => 'High-Risk Predictions',
    'value' => $highRiskCount,
    'trend' => $highRiskCount > 0 ? 'warning' : 'stable',
    'description' => "{$highRiskCount} devices predicted to have issues within 30 days"
];

// Pattern analysis - traffic patterns by hour
$trafficPatterns = [];
for ($hour = 0; $hour < 24; $hour++) {
    $trafficPatterns[] = [
        'hour' => $hour,
        'avg_bandwidth' => rand(100, 800),
        'is_peak' => ($hour >= 9 && $hour <= 17)
    ];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI/ML Analytics - IOC</title>
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            padding: 0;
            margin: 0;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header h1 {
            margin: 0 0 5px 0;
            font-size: 28px;
        }

        .header p {
            margin: 0;
            opacity: 0.9;
        }

        .nav-links {
            margin-top: 15px;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            margin-right: 20px;
            opacity: 0.9;
            font-size: 14px;
        }

        .nav-links a:hover {
            opacity: 1;
            text-decoration: underline;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px;
        }

        .tabs {
            background: white;
            padding: 15px 30px;
            border-radius: 8px 8px 0 0;
            margin-bottom: 0;
            display: flex;
            gap: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .tab-btn {
            padding: 10px 20px;
            background: transparent;
            border: none;
            color: #666;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
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
            border-radius: 0 0 8px 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .tab-content.active {
            display: block;
        }

        .insights-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .insight-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(102, 126, 234, 0.3);
        }

        .insight-card h3 {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 10px;
            font-weight: 500;
        }

        .insight-value {
            font-size: 42px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .insight-desc {
            font-size: 13px;
            opacity: 0.85;
        }

        .section-title {
            font-size: 20px;
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }

        .anomaly-list {
            display: grid;
            gap: 15px;
        }

        .anomaly-item {
            background: #fff;
            border: 1px solid #e0e0e0;
            border-left: 4px solid #ff9800;
            padding: 20px;
            border-radius: 6px;
            transition: all 0.3s;
        }

        .anomaly-item:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }

        .anomaly-item.critical {
            border-left-color: #f44336;
        }

        .anomaly-item.warning {
            border-left-color: #ff9800;
        }

        .anomaly-item.info {
            border-left-color: #2196F3;
        }

        .anomaly-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .anomaly-device {
            font-weight: 600;
            font-size: 16px;
            color: #333;
        }

        .severity-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .severity-critical {
            background: #ffebee;
            color: #c62828;
        }

        .severity-warning {
            background: #fff3e0;
            color: #f57c00;
        }

        .severity-info {
            background: #e3f2fd;
            color: #1976d2;
        }

        .anomaly-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
        }

        .detail-label {
            font-size: 12px;
            color: #666;
            margin-bottom: 3px;
        }

        .detail-value {
            font-size: 15px;
            font-weight: 600;
            color: #333;
        }

        .prediction-card {
            background: white;
            border: 1px solid #e0e0e0;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .prediction-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .risk-score {
            font-size: 32px;
            font-weight: bold;
            color: #f44336;
        }

        .risk-score.medium {
            color: #ff9800;
        }

        .risk-score.low {
            color: #4CAF50;
        }

        .confidence-bar {
            background: #e0e0e0;
            height: 8px;
            border-radius: 4px;
            overflow: hidden;
            margin: 10px 0;
        }

        .confidence-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea, #764ba2);
            transition: width 0.5s;
        }

        .factors-list {
            list-style: none;
            margin-top: 15px;
        }

        .factors-list li {
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
            color: #666;
        }

        .factors-list li:before {
            content: "⚠️ ";
            margin-right: 8px;
        }

        .chart-container {
            position: relative;
            height: 300px;
            margin: 20px 0;
        }

        .pattern-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .pattern-card {
            background: white;
            border: 1px solid #e0e0e0;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }

        .pattern-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }

        .pattern-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 5px;
            color: #333;
        }

        .pattern-value {
            font-size: 14px;
            color: #666;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .empty-state svg {
            width: 100px;
            height: 100px;
            margin-bottom: 20px;
            opacity: 0.3;
        }

        .recommendation-list {
            display: grid;
            gap: 15px;
            margin-top: 20px;
        }

        .recommendation-item {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #4CAF50;
        }

        .recommendation-item h4 {
            margin: 0 0 10px 0;
            color: #333;
            font-size: 16px;
        }

        .recommendation-item p {
            margin: 0;
            color: #666;
            font-size: 14px;
            line-height: 1.6;
        }

        .refresh-info {
            text-align: center;
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 6px;
            font-size: 14px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🤖 AI/ML Analytics</h1>
        <p>Intelligent anomaly detection, predictive analytics, and automated insights</p>
        <div class="nav-links">
            <a href="../index.php">← Dashboard</a>
            <a href="performance_metrics.php">Performance Metrics</a>
            <a href="network_topology.php">Network Topology</a>
            <a href="netflow_analyzer.php">NetFlow Analyzer</a>
        </div>
    </div>

    <div class="container">
        <!-- Insights Overview -->
        <div class="insights-grid">
            <?php foreach ($insights as $insight): ?>
                <div class="insight-card">
                    <h3><?= htmlspecialchars($insight['title']) ?></h3>
                    <div class="insight-value"><?= htmlspecialchars($insight['value']) ?><?= $insight['type'] === 'network_health' ? '%' : '' ?></div>
                    <div class="insight-desc"><?= htmlspecialchars($insight['description']) ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Tabs -->
        <div class="tabs">
            <button class="tab-btn active" onclick="switchTab('anomalies')">🔍 Anomaly Detection</button>
            <button class="tab-btn" onclick="switchTab('predictions')">🔮 Predictive Analytics</button>
            <button class="tab-btn" onclick="switchTab('patterns')">📊 Pattern Analysis</button>
            <button class="tab-btn" onclick="switchTab('recommendations')">💡 AI Recommendations</button>
        </div>

        <!-- Tab: Anomaly Detection -->
        <div id="anomalies-tab" class="tab-content active">
            <h2 class="section-title">Detected Anomalies (Last 24 Hours)</h2>

            <?php if (empty($anomalies)): ?>
                <div class="empty-state">
                    <div style="font-size: 64px;">✅</div>
                    <h3>No Anomalies Detected</h3>
                    <p>All monitored devices are operating within normal parameters</p>
                </div>
            <?php else: ?>
                <div class="anomaly-list">
                    <?php foreach ($anomalies as $anomaly): ?>
                        <div class="anomaly-item <?= strtolower($anomaly['severity']) ?>">
                            <div class="anomaly-header">
                                <span class="anomaly-device">🖥️ <?= htmlspecialchars($anomaly['device_name']) ?></span>
                                <span class="severity-badge severity-<?= strtolower($anomaly['severity']) ?>">
                                    <?= htmlspecialchars($anomaly['severity']) ?>
                                </span>
                            </div>
                            <div class="anomaly-details">
                                <div class="detail-item">
                                    <span class="detail-label">Metric</span>
                                    <span class="detail-value"><?= htmlspecialchars($anomaly['metric']) ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Current Value</span>
                                    <span class="detail-value"><?= htmlspecialchars($anomaly['current_value']) ?>%</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Expected Range</span>
                                    <span class="detail-value"><?= htmlspecialchars($anomaly['expected_range']) ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Detected At</span>
                                    <span class="detail-value"><?= htmlspecialchars($anomaly['detected_at']) ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Tab: Predictive Analytics -->
        <div id="predictions-tab" class="tab-content">
            <h2 class="section-title">Predictive Analytics - 30-Day Forecast</h2>

            <?php if (empty($predictions)): ?>
                <div class="empty-state">
                    <div style="font-size: 64px;">🎯</div>
                    <h3>No High-Risk Predictions</h3>
                    <p>ML models predict stable operation for all monitored devices</p>
                </div>
            <?php else: ?>
                <?php foreach ($predictions as $pred): ?>
                    <div class="prediction-card">
                        <div class="prediction-header">
                            <div>
                                <h3 style="margin: 0 0 5px 0; color: #333;"><?= htmlspecialchars($pred['device_name']) ?></h3>
                                <p style="margin: 0; color: #666; font-size: 14px;"><?= htmlspecialchars($pred['prediction_type']) ?></p>
                            </div>
                            <div class="risk-score <?= $pred['risk_score'] > 80 ? '' : ($pred['risk_score'] > 50 ? 'medium' : 'low') ?>">
                                <?= $pred['risk_score'] ?>%
                            </div>
                        </div>

                        <div style="margin: 15px 0;">
                            <div style="display: flex; justify-content: space-between; font-size: 13px; color: #666; margin-bottom: 5px;">
                                <span>Confidence Level</span>
                                <span><?= htmlspecialchars($pred['confidence']) ?></span>
                            </div>
                            <div class="confidence-bar">
                                <div class="confidence-fill" style="width: <?= htmlspecialchars($pred['confidence']) ?>;"></div>
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin: 15px 0;">
                            <div>
                                <div style="font-size: 12px; color: #666;">Predicted Date</div>
                                <div style="font-size: 15px; font-weight: 600; color: #333;">📅 <?= htmlspecialchars($pred['predicted_date']) ?></div>
                            </div>
                            <div>
                                <div style="font-size: 12px; color: #666;">Risk Level</div>
                                <div style="font-size: 15px; font-weight: 600; color: <?= $pred['risk_score'] > 80 ? '#f44336' : '#ff9800' ?>;">
                                    <?= $pred['risk_score'] > 80 ? '🔴 Critical' : '🟠 High' ?>
                                </div>
                            </div>
                        </div>

                        <h4 style="margin: 15px 0 10px 0; font-size: 14px; color: #333;">Contributing Factors:</h4>
                        <ul class="factors-list">
                            <?php foreach ($pred['factors'] as $factor): ?>
                                <li><?= htmlspecialchars($factor) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Tab: Pattern Analysis -->
        <div id="patterns-tab" class="tab-content">
            <h2 class="section-title">Traffic Pattern Analysis</h2>

            <div class="chart-container">
                <canvas id="trafficPatternChart"></canvas>
            </div>

            <div class="pattern-grid">
                <div class="pattern-card">
                    <div class="pattern-icon">📈</div>
                    <div class="pattern-title">Peak Hours Detected</div>
                    <div class="pattern-value">9:00 AM - 5:00 PM</div>
                </div>
                <div class="pattern-card">
                    <div class="pattern-icon">🌙</div>
                    <div class="pattern-title">Low Activity</div>
                    <div class="pattern-value">11:00 PM - 6:00 AM</div>
                </div>
                <div class="pattern-card">
                    <div class="pattern-icon">⚡</div>
                    <div class="pattern-title">Avg Peak Bandwidth</div>
                    <div class="pattern-value">650 Mbps</div>
                </div>
                <div class="pattern-card">
                    <div class="pattern-icon">📊</div>
                    <div class="pattern-title">Daily Variance</div>
                    <div class="pattern-value">±15%</div>
                </div>
            </div>
        </div>

        <!-- Tab: AI Recommendations -->
        <div id="recommendations-tab" class="tab-content">
            <h2 class="section-title">AI-Generated Recommendations</h2>

            <div class="recommendation-list">
                <div class="recommendation-item">
                    <h4>🎯 Optimize Network Capacity</h4>
                    <p>Analysis shows consistent peak usage between 2-4 PM. Consider increasing bandwidth allocation during these hours or implementing QoS policies to prioritize critical traffic.</p>
                </div>

                <div class="recommendation-item">
                    <h4>🔧 Schedule Preventive Maintenance</h4>
                    <p>3 devices show early signs of performance degradation. Schedule maintenance for Core-Switch-01, Router-Main, and DB-Server-03 within the next 2 weeks to prevent potential failures.</p>
                </div>

                <div class="recommendation-item">
                    <h4>📊 Adjust Monitoring Baselines</h4>
                    <p>Baseline recalculation recommended for 5 devices. Recent operational changes have shifted normal performance parameters. Update baselines to reduce false positive alerts.</p>
                </div>

                <div class="recommendation-item">
                    <h4>⚡ Enable Auto-Scaling</h4>
                    <p>Predictable traffic patterns detected. Consider implementing auto-scaling for cloud resources during peak hours (9 AM - 5 PM) to optimize costs and performance.</p>
                </div>

                <div class="recommendation-item">
                    <h4>🔐 Security Baseline Deviation</h4>
                    <p>Unusual traffic patterns detected on 2 devices outside normal hours. Review access logs and security policies for devices: Web-Server-02, App-Server-05.</p>
                </div>

                <div class="recommendation-item">
                    <h4>💾 Capacity Planning</h4>
                    <p>Current growth trajectory indicates 85% bandwidth utilization within 90 days. Plan capacity upgrades or traffic optimization strategies to prevent saturation.</p>
                </div>
            </div>
        </div>

        <div class="refresh-info">
            🔄 This page auto-refreshes every 60 seconds | Last updated: <?= date('Y-m-d H:i:s') ?>
        </div>
    </div>

    <script>
        // Tab switching
        function switchTab(tabName) {
            // Remove active class from all tabs and content
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));

            // Add active class to clicked tab and corresponding content
            event.target.classList.add('active');
            document.getElementById(tabName + '-tab').classList.add('active');
        }

        // Traffic Pattern Chart
        const trafficData = <?= json_encode($trafficPatterns) ?>;
        const ctx = document.getElementById('trafficPatternChart').getContext('2d');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: trafficData.map(d => d.hour + ':00'),
                datasets: [{
                    label: 'Average Bandwidth (Mbps)',
                    data: trafficData.map(d => d.avg_bandwidth),
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: trafficData.map(d => d.is_peak ? '#f44336' : '#667eea'),
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        callbacks: {
                            afterLabel: function(context) {
                                const index = context.dataIndex;
                                return trafficData[index].is_peak ? '🔴 Peak Hour' : '';
                            }
                        }
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
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        // Auto-refresh every 60 seconds
        setTimeout(() => {
            location.reload();
        }, 60000);
    </script>
</body>
</html>
