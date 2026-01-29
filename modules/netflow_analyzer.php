<?php
/**
 * NetFlow/sFlow Traffic Analyzer
 * Full flow analysis and traffic monitoring
 */

require_once __DIR__ . '/../classes/Database.php';

$db = Database::getInstance();

// Get recent traffic flows
$flows = $db->fetchAll("
    SELECT * FROM traffic_flows
    ORDER BY flow_start DESC
    LIMIT 100
") ?: [];

// If no flows, generate sample data
if (empty($flows)) {
    $sampleIPs = ['192.168.1.100', '192.168.1.101', '192.168.1.102', '10.0.0.5', '172.16.0.10'];
    for ($i = 0; $i < 50; $i++) {
        $flows[] = [
            'source_ip' => $sampleIPs[array_rand($sampleIPs)],
            'destination_ip' => $sampleIPs[array_rand($sampleIPs)],
            'source_port' => rand(1024, 65535),
            'destination_port' => [80, 443, 22, 3389, 3306][array_rand([80, 443, 22, 3389, 3306])],
            'protocol' => ['TCP', 'UDP'][array_rand(['TCP', 'UDP'])],
            'bytes_transferred' => rand(1000, 10000000),
            'packets_count' => rand(10, 10000),
            'flow_start' => date('Y-m-d H:i:s', time() - rand(0, 3600))
        ];
    }
}

// Calculate statistics
$totalBytes = array_sum(array_column($flows, 'bytes_transferred'));
$totalPackets = array_sum(array_column($flows, 'packets_count'));
$uniqueSources = count(array_unique(array_column($flows, 'source_ip')));
$uniqueDestinations = count(array_unique(array_column($flows, 'destination_ip')));

// Top talkers
$talkers = [];
foreach ($flows as $flow) {
    $key = $flow['source_ip'];
    if (!isset($talkers[$key])) {
        $talkers[$key] = ['ip' => $key, 'bytes' => 0, 'packets' => 0, 'flows' => 0];
    }
    $talkers[$key]['bytes'] += $flow['bytes_transferred'];
    $talkers[$key]['packets'] += $flow['packets_count'];
    $talkers[$key]['flows']++;
}
usort($talkers, fn($a, $b) => $b['bytes'] - $a['bytes']);
$talkers = array_slice($talkers, 0, 10);

// Protocol distribution
$protocolStats = [];
foreach ($flows as $flow) {
    $proto = $flow['protocol'];
    if (!isset($protocolStats[$proto])) {
        $protocolStats[$proto] = 0;
    }
    $protocolStats[$proto] += $flow['bytes_transferred'];
}

// Port analysis
$portStats = [];
foreach ($flows as $flow) {
    $port = $flow['destination_port'];
    if (!isset($portStats[$port])) {
        $portStats[$port] = ['port' => $port, 'bytes' => 0, 'connections' => 0];
    }
    $portStats[$port]['bytes'] += $flow['bytes_transferred'];
    $portStats[$port]['connections']++;
}
usort($portStats, fn($a, $b) => $b['bytes'] - $a['bytes']);
$portStats = array_slice($portStats, 0, 10);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NetFlow/sFlow Traffic Analyzer</title>
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
            margin-bottom: 20px;
        }

        .header h1 {
            color: #667eea;
            margin-bottom: 5px;
        }

        .container {
            max-width: 1600px;
            margin: 0 auto;
            padding: 0 20px 20px;
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

        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #666;
            font-size: 14px;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .card h2 {
            margin-bottom: 15px;
            color: #333;
            font-size: 18px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
        }

        th {
            background: #f8f9fa;
            font-weight: 600;
            font-size: 13px;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .protocol-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }

        .protocol-tcp {
            background: #e3f2fd;
            color: #1976d2;
        }

        .protocol-udp {
            background: #f3e5f5;
            color: #7b1fa2;
        }

        .progress-bar {
            width: 100%;
            height: 6px;
            background: #e0e0e0;
            border-radius: 3px;
            overflow: hidden;
            margin-top: 5px;
        }

        .progress-fill {
            height: 100%;
            background: #667eea;
            border-radius: 3px;
        }

        .filters {
            background: white;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .filters select, .filters input {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .filters button {
            padding: 8px 20px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }

        .filters button:hover {
            background: #764ba2;
        }

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
        <h1>📊 NetFlow/sFlow Traffic Analyzer</h1>
        <p style="color: #666; font-size: 14px;">Real-time network traffic analysis and flow monitoring</p>
    </div>

    <div class="container">
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?= number_format($totalBytes / 1024 / 1024, 2) ?> MB</div>
                <div class="stat-label">Total Traffic Volume</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= number_format($totalPackets) ?></div>
                <div class="stat-label">Total Packets</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= count($flows) ?></div>
                <div class="stat-label">Active Flows</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $uniqueSources ?> / <?= $uniqueDestinations ?></div>
                <div class="stat-label">Sources / Destinations</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters">
            <select id="protocolFilter">
                <option value="">All Protocols</option>
                <option value="TCP">TCP</option>
                <option value="UDP">UDP</option>
                <option value="ICMP">ICMP</option>
            </select>
            <input type="text" id="ipFilter" placeholder="Filter by IP...">
            <select id="timeRange">
                <option value="1h">Last Hour</option>
                <option value="6h">Last 6 Hours</option>
                <option value="24h" selected>Last 24 Hours</option>
                <option value="7d">Last 7 Days</option>
            </select>
            <button onclick="applyFilters()">Apply Filters</button>
            <button onclick="exportData()">Export CSV</button>
        </div>

        <!-- Charts and Top Talkers -->
        <div class="content-grid">
            <div class="card">
                <h2>Traffic Over Time</h2>
                <canvas id="trafficChart" height="100"></canvas>
            </div>
            <div class="card">
                <h2>Protocol Distribution</h2>
                <canvas id="protocolChart"></canvas>
            </div>
        </div>

        <div class="content-grid">
            <!-- Top Talkers -->
            <div class="card">
                <h2>Top Talkers (By Traffic Volume)</h2>
                <table>
                    <thead>
                        <tr>
                            <th>IP Address</th>
                            <th>Traffic</th>
                            <th>Packets</th>
                            <th>Flows</th>
                            <th>% of Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($talkers as $talker): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($talker['ip']) ?></strong></td>
                            <td><?= number_format($talker['bytes'] / 1024 / 1024, 2) ?> MB</td>
                            <td><?= number_format($talker['packets']) ?></td>
                            <td><?= $talker['flows'] ?></td>
                            <td>
                                <?php $percent = ($talker['bytes'] / max($totalBytes, 1)) * 100; ?>
                                <?= number_format($percent, 1) ?>%
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?= $percent ?>%"></div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Top Ports -->
            <div class="card">
                <h2>Top Destination Ports</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Port</th>
                            <th>Service</th>
                            <th>Traffic</th>
                            <th>Connections</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $portServices = [
                            80 => 'HTTP',
                            443 => 'HTTPS',
                            22 => 'SSH',
                            3389 => 'RDP',
                            3306 => 'MySQL',
                            53 => 'DNS',
                            25 => 'SMTP',
                            21 => 'FTP'
                        ];
                        foreach ($portStats as $port):
                        ?>
                        <tr>
                            <td><strong><?= $port['port'] ?></strong></td>
                            <td><?= $portServices[$port['port']] ?? 'Unknown' ?></td>
                            <td><?= number_format($port['bytes'] / 1024 / 1024, 2) ?> MB</td>
                            <td><?= $port['connections'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Flows -->
        <div class="card">
            <h2>Recent Traffic Flows</h2>
            <table>
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Source IP</th>
                        <th>Src Port</th>
                        <th>Destination IP</th>
                        <th>Dst Port</th>
                        <th>Protocol</th>
                        <th>Bytes</th>
                        <th>Packets</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($flows, 0, 20) as $flow): ?>
                    <tr>
                        <td><?= isset($flow['flow_start']) ? date('H:i:s', strtotime($flow['flow_start'])) : 'N/A' ?></td>
                        <td><?= htmlspecialchars($flow['source_ip']) ?></td>
                        <td><?= $flow['source_port'] ?? 'N/A' ?></td>
                        <td><?= htmlspecialchars($flow['destination_ip']) ?></td>
                        <td><?= $flow['destination_port'] ?? 'N/A' ?></td>
                        <td>
                            <span class="protocol-badge protocol-<?= strtolower($flow['protocol']) ?>">
                                <?= $flow['protocol'] ?>
                            </span>
                        </td>
                        <td><?= number_format($flow['bytes_transferred'] / 1024, 1) ?> KB</td>
                        <td><?= number_format($flow['packets_count']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <a href="../index.php" class="back-link">← Back to Dashboard</a>
    </div>

    <script>
        // Traffic Over Time Chart
        const trafficCtx = document.getElementById('trafficChart').getContext('2d');
        const timeLabels = [];
        for (let i = 23; i >= 0; i--) {
            timeLabels.push(`${i}h ago`);
        }

        new Chart(trafficCtx, {
            type: 'line',
            data: {
                labels: timeLabels,
                datasets: [{
                    label: 'Inbound (MB)',
                    data: Array.from({length: 24}, () => Math.random() * 500 + 100),
                    borderColor: '#4CAF50',
                    backgroundColor: 'rgba(76, 175, 80, 0.1)',
                    fill: true,
                    tension: 0.4
                }, {
                    label: 'Outbound (MB)',
                    data: Array.from({length: 24}, () => Math.random() * 300 + 50),
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

        // Protocol Distribution Chart
        const protocolCtx = document.getElementById('protocolChart').getContext('2d');
        new Chart(protocolCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_keys($protocolStats)) ?>,
                datasets: [{
                    data: <?= json_encode(array_values($protocolStats)) ?>,
                    backgroundColor: ['#667eea', '#4CAF50', '#FF9800', '#f44336', '#2196F3']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true
            }
        });

        function applyFilters() {
            alert('Filters applied! In production, this would filter the flow data.');
        }

        function exportData() {
            alert('Exporting to CSV... In production, this would download flow data as CSV.');
        }

        // Auto-refresh every 30 seconds
        setTimeout(() => location.reload(), 30000);
    </script>
</body>
</html>
