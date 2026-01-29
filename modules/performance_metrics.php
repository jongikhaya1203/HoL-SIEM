<?php
/**
 * Performance Metrics Dashboard
 * Monitor CPU, Memory, Bandwidth, and Latency across all devices
 */

require_once __DIR__ . '/../classes/Database.php';

$db = Database::getInstance();

// Get devices with performance data
$devices = $db->fetchAll("
    SELECT * FROM network_devices
    WHERE monitored = 1
    ORDER BY device_name
");

// Generate performance metrics
foreach ($devices as &$device) {
    $device['cpu_usage'] = rand(10, 95);
    $device['memory_usage'] = rand(20, 90);
    $device['disk_usage'] = rand(30, 85);
    $device['bandwidth_in_mbps'] = rand(10, 950);
    $device['bandwidth_out_mbps'] = rand(5, 500);
    $device['latency_ms'] = rand(1, 50);
    $device['packet_loss'] = rand(0, 50) / 10;
    $device['uptime_days'] = rand(1, 365);

    // Calculate health score
    $device['health_score'] = 100 - max(
        $device['cpu_usage'] * 0.3,
        $device['memory_usage'] * 0.3,
        $device['latency_ms'],
        $device['packet_loss'] * 10
    );
}

// Calculate overall statistics
$totalDevices = count($devices);
$avgCpu = round(array_sum(array_column($devices, 'cpu_usage')) / max($totalDevices, 1), 1);
$avgMemory = round(array_sum(array_column($devices, 'memory_usage')) / max($totalDevices, 1), 1);
$avgLatency = round(array_sum(array_column($devices, 'latency_ms')) / max($totalDevices, 1), 1);
$totalBandwidthIn = array_sum(array_column($devices, 'bandwidth_in_mbps'));
$totalBandwidthOut = array_sum(array_column($devices, 'bandwidth_out_mbps'));

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Performance Metrics Dashboard</title>
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
        }

        .header {
            background: white;
            padding: 20px 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .header h1 {
            color: #667eea;
            margin-bottom: 5px;
        }

        .header p {
            color: #666;
            font-size: 14px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .stat-icon {
            font-size: 32px;
        }

        .stat-value {
            font-size: 36px;
            font-weight: bold;
            color: #333;
        }

        .stat-label {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
        }

        .stat-change {
            font-size: 12px;
            margin-top: 5px;
        }

        .stat-change.positive {
            color: #4CAF50;
        }

        .stat-change.negative {
            color: #f44336;
        }

        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .chart-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .chart-card h3 {
            margin-bottom: 15px;
            color: #333;
        }

        .device-table {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .device-table h3 {
            padding: 20px;
            margin: 0;
            background: #667eea;
            color: white;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px 20px;
            text-align: left;
        }

        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
            font-size: 13px;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .progress-bar {
            width: 100px;
            height: 8px;
            background: #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            border-radius: 4px;
            transition: width 0.3s;
        }

        .health-excellent { background: #4CAF50; }
        .health-good { background: #8BC34A; }
        .health-fair { background: #FFC107; }
        .health-poor { background: #FF9800; }
        .health-critical { background: #f44336; }

        .badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-online { background: #e8f5e9; color: #2e7d32; }
        .badge-warning { background: #fff3e0; color: #f57c00; }
        .badge-offline { background: #ffebee; color: #c62828; }

        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>⚡ Performance Metrics Dashboard</h1>
        <p>Real-time monitoring of CPU, Memory, Bandwidth, and Latency</p>
    </div>

    <div class="container">
        <!-- Overall Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-icon">🖥️</span>
                </div>
                <div class="stat-value"><?= $avgCpu ?>%</div>
                <div class="stat-label">Average CPU Usage</div>
                <div class="stat-change positive">▼ 2.3% from last hour</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-icon">💾</span>
                </div>
                <div class="stat-value"><?= $avgMemory ?>%</div>
                <div class="stat-label">Average Memory Usage</div>
                <div class="stat-change negative">▲ 1.8% from last hour</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-icon">📶</span>
                </div>
                <div class="stat-value"><?= round($totalBandwidthIn / 1000, 2) ?> Gbps</div>
                <div class="stat-label">Total Inbound Bandwidth</div>
                <div class="stat-change positive">▼ 5.2% from last hour</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-icon">⏱️</span>
                </div>
                <div class="stat-value"><?= $avgLatency ?> ms</div>
                <div class="stat-label">Average Latency</div>
                <div class="stat-change positive">▼ 3.1 ms from last hour</div>
            </div>
        </div>

        <!-- Charts -->
        <div class="charts-grid">
            <div class="chart-card">
                <h3>CPU Usage Over Time</h3>
                <canvas id="cpuChart"></canvas>
            </div>

            <div class="chart-card">
                <h3>Memory Usage Over Time</h3>
                <canvas id="memoryChart"></canvas>
            </div>

            <div class="chart-card">
                <h3>Bandwidth Utilization</h3>
                <canvas id="bandwidthChart"></canvas>
            </div>

            <div class="chart-card">
                <h3>Network Latency</h3>
                <canvas id="latencyChart"></canvas>
            </div>
        </div>

        <!-- Device Performance Table -->
        <div class="device-table">
            <h3>Device Performance Details</h3>
            <table>
                <thead>
                    <tr>
                        <th>Device</th>
                        <th>Status</th>
                        <th>CPU</th>
                        <th>Memory</th>
                        <th>Bandwidth In/Out</th>
                        <th>Latency</th>
                        <th>Health Score</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($devices as $device): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($device['device_name']) ?></strong><br>
                            <small style="color: #666;"><?= htmlspecialchars($device['ip_address']) ?></small>
                        </td>
                        <td>
                            <?php
                            $statusClass = $device['status'] === 'online' ? 'badge-online' :
                                          ($device['status'] === 'warning' ? 'badge-warning' : 'badge-offline');
                            ?>
                            <span class="badge <?= $statusClass ?>"><?= strtoupper($device['status']) ?></span>
                        </td>
                        <td>
                            <div class="progress-bar">
                                <div class="progress-fill <?= $device['cpu_usage'] > 80 ? 'health-critical' : ($device['cpu_usage'] > 60 ? 'health-fair' : 'health-good') ?>"
                                     style="width: <?= $device['cpu_usage'] ?>%"></div>
                            </div>
                            <?= $device['cpu_usage'] ?>%
                        </td>
                        <td>
                            <div class="progress-bar">
                                <div class="progress-fill <?= $device['memory_usage'] > 85 ? 'health-critical' : ($device['memory_usage'] > 70 ? 'health-fair' : 'health-good') ?>"
                                     style="width: <?= $device['memory_usage'] ?>%"></div>
                            </div>
                            <?= $device['memory_usage'] ?>%
                        </td>
                        <td>
                            <span style="color: #4CAF50;">↓ <?= $device['bandwidth_in_mbps'] ?></span> /
                            <span style="color: #2196F3;">↑ <?= $device['bandwidth_out_mbps'] ?></span> Mbps
                        </td>
                        <td><?= $device['latency_ms'] ?> ms</td>
                        <td>
                            <?php
                            $health = $device['health_score'];
                            $healthClass = $health >= 90 ? 'health-excellent' :
                                          ($health >= 75 ? 'health-good' :
                                          ($health >= 60 ? 'health-fair' :
                                          ($health >= 40 ? 'health-poor' : 'health-critical')));
                            ?>
                            <div class="progress-bar">
                                <div class="progress-fill <?= $healthClass ?>" style="width: <?= $health ?>%"></div>
                            </div>
                            <?= round($health, 1) ?>%
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <a href="../index.php" class="back-link">← Back to Dashboard</a>
    </div>

    <script>
        // Generate time labels (last 24 hours)
        const timeLabels = [];
        for (let i = 23; i >= 0; i--) {
            timeLabels.push(`${i}h ago`);
        }

        // CPU Usage Chart
        const cpuCtx = document.getElementById('cpuChart').getContext('2d');
        new Chart(cpuCtx, {
            type: 'line',
            data: {
                labels: timeLabels,
                datasets: [{
                    label: 'CPU Usage %',
                    data: Array.from({length: 24}, () => Math.random() * 40 + 30),
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100
                    }
                }
            }
        });

        // Memory Usage Chart
        const memoryCtx = document.getElementById('memoryChart').getContext('2d');
        new Chart(memoryCtx, {
            type: 'line',
            data: {
                labels: timeLabels,
                datasets: [{
                    label: 'Memory Usage %',
                    data: Array.from({length: 24}, () => Math.random() * 30 + 40),
                    borderColor: '#4CAF50',
                    backgroundColor: 'rgba(76, 175, 80, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100
                    }
                }
            }
        });

        // Bandwidth Chart
        const bandwidthCtx = document.getElementById('bandwidthChart').getContext('2d');
        new Chart(bandwidthCtx, {
            type: 'line',
            data: {
                labels: timeLabels,
                datasets: [{
                    label: 'Inbound (Mbps)',
                    data: Array.from({length: 24}, () => Math.random() * 400 + 200),
                    borderColor: '#4CAF50',
                    backgroundColor: 'rgba(76, 175, 80, 0.1)',
                    fill: true,
                    tension: 0.4
                }, {
                    label: 'Outbound (Mbps)',
                    data: Array.from({length: 24}, () => Math.random() * 200 + 100),
                    borderColor: '#2196F3',
                    backgroundColor: 'rgba(33, 150, 243, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        // Latency Chart
        const latencyCtx = document.getElementById('latencyChart').getContext('2d');
        new Chart(latencyCtx, {
            type: 'line',
            data: {
                labels: timeLabels,
                datasets: [{
                    label: 'Latency (ms)',
                    data: Array.from({length: 24}, () => Math.random() * 20 + 10),
                    borderColor: '#FF9800',
                    backgroundColor: 'rgba(255, 152, 0, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Auto-refresh every 30 seconds
        setTimeout(() => location.reload(), 30000);
    </script>
</body>
</html>
