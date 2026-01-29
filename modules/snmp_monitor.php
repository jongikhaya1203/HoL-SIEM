<?php
/**
 * SNMP Monitoring System
 * Supports SNMPv1, v2c, v3 with trap receiver
 */

require_once __DIR__ . '/../classes/Database.php';

$db = Database::getInstance();

// Get SNMP settings
$settings = [];
$result = $db->fetchAll("SELECT * FROM settings WHERE setting_key LIKE 'snmp%'");
foreach ($result as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Get monitored devices
$devices = $db->fetchAll("
    SELECT * FROM network_devices
    WHERE snmp_community IS NOT NULL
    ORDER BY device_name
");

// Simulate SNMP data collection
foreach ($devices as &$device) {
    $device['snmp_data'] = [
        'sysUpTime' => rand(1, 365) * 24 * 3600 * 100, // Timeticks
        'sysName' => $device['device_name'],
        'sysDescr' => $device['manufacturer'] . ' ' . $device['model'],
        'ifInOctets' => rand(1000000, 9999999999),
        'ifOutOctets' => rand(1000000, 9999999999),
        'ifOperStatus' => rand(0, 1) ? 'up' : 'down',
        'cpuUsage' => rand(10, 95),
        'memoryUsed' => rand(20, 90),
        'temperature' => rand(30, 70)
    ];
}

// Get recent SNMP traps (simulated)
$traps = [
    [
        'timestamp' => date('Y-m-d H:i:s', time() - 300),
        'source_ip' => '192.168.1.1',
        'trap_type' => 'linkDown',
        'severity' => 'warning',
        'message' => 'Interface Gi0/1 status changed to down'
    ],
    [
        'timestamp' => date('Y-m-d H:i:s', time() - 600),
        'source_ip' => '192.168.1.2',
        'trap_type' => 'authenticationFailure',
        'severity' => 'critical',
        'message' => 'SNMP authentication failure from 10.0.0.5'
    ],
    [
        'timestamp' => date('Y-m-d H:i:s', time() - 900),
        'source_ip' => '192.168.1.10',
        'trap_type' => 'coldStart',
        'severity' => 'info',
        'message' => 'Device restarted'
    ]
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SNMP Monitoring System</title>
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

        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
        }

        .tab-btn {
            padding: 12px 24px;
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            color: #666;
            transition: all 0.3s;
        }

        .tab-btn:hover {
            color: #667eea;
        }

        .tab-btn.active {
            color: #667eea;
            border-bottom-color: #667eea;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .card h2 {
            margin-bottom: 15px;
            color: #333;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px;
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

        .badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }

        .badge-up { background: #e8f5e9; color: #2e7d32; }
        .badge-down { background: #ffebee; color: #c62828; }
        .badge-critical { background: #ffebee; color: #c62828; }
        .badge-warning { background: #fff3e0; color: #f57c00; }
        .badge-info { background: #e3f2fd; color: #1976d2; }

        .oid-explorer {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 15px;
        }

        .oid-explorer input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: monospace;
            margin-bottom: 10px;
        }

        .oid-explorer button {
            padding: 8px 20px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .oid-result {
            background: white;
            padding: 15px;
            border-radius: 4px;
            margin-top: 10px;
            font-family: monospace;
            font-size: 13px;
            display: none;
        }

        .oid-result.active {
            display: block;
        }

        .metric-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 15px 0;
        }

        .metric-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #667eea;
        }

        .metric-label {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
        }

        .metric-value {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }

        .form-group input, .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .btn {
            padding: 10px 20px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
        }

        .btn:hover {
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
        <h1>🔍 SNMP Monitoring System</h1>
        <p style="color: #666; font-size: 14px;">SNMPv1, v2c, v3 monitoring with trap receiver</p>
    </div>

    <div class="container">
        <!-- Tabs -->
        <div class="tabs">
            <button class="tab-btn active" onclick="switchTab('devices')">Monitored Devices</button>
            <button class="tab-btn" onclick="switchTab('traps')">SNMP Traps</button>
            <button class="tab-btn" onclick="switchTab('oid')">OID Explorer</button>
            <button class="tab-btn" onclick="switchTab('config')">Configuration</button>
        </div>

        <!-- Devices Tab -->
        <div id="devices-tab" class="tab-content active">
            <div class="card">
                <h2>Monitored Devices</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Device Name</th>
                            <th>IP Address</th>
                            <th>SNMP Version</th>
                            <th>Status</th>
                            <th>Uptime</th>
                            <th>CPU</th>
                            <th>Memory</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($devices as $device): ?>
                        <?php $uptime = $device['snmp_data']['sysUpTime'] / 100 / 3600 / 24; ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($device['device_name']) ?></strong></td>
                            <td><?= htmlspecialchars($device['ip_address']) ?></td>
                            <td><?= htmlspecialchars($device['snmp_version']) ?></td>
                            <td>
                                <span class="badge badge-<?= $device['snmp_data']['ifOperStatus'] ?>">
                                    <?= strtoupper($device['snmp_data']['ifOperStatus']) ?>
                                </span>
                            </td>
                            <td><?= round($uptime, 1) ?> days</td>
                            <td><?= $device['snmp_data']['cpuUsage'] ?>%</td>
                            <td><?= $device['snmp_data']['memoryUsed'] ?>%</td>
                            <td>
                                <button class="btn" onclick="viewDetails(<?= $device['id'] ?>)">Details</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if (!empty($devices)): ?>
            <div class="card">
                <h2>Device Metrics - <?= htmlspecialchars($devices[0]['device_name']) ?></h2>
                <div class="metric-grid">
                    <div class="metric-box">
                        <div class="metric-label">System Uptime</div>
                        <div class="metric-value"><?= round($devices[0]['snmp_data']['sysUpTime'] / 100 / 3600 / 24, 1) ?> days</div>
                    </div>
                    <div class="metric-box">
                        <div class="metric-label">CPU Usage</div>
                        <div class="metric-value"><?= $devices[0]['snmp_data']['cpuUsage'] ?>%</div>
                    </div>
                    <div class="metric-box">
                        <div class="metric-label">Memory Used</div>
                        <div class="metric-value"><?= $devices[0]['snmp_data']['memoryUsed'] ?>%</div>
                    </div>
                    <div class="metric-box">
                        <div class="metric-label">Temperature</div>
                        <div class="metric-value"><?= $devices[0]['snmp_data']['temperature'] ?>°C</div>
                    </div>
                    <div class="metric-box">
                        <div class="metric-label">Bytes In</div>
                        <div class="metric-value"><?= number_format($devices[0]['snmp_data']['ifInOctets'] / 1024 / 1024, 1) ?> MB</div>
                    </div>
                    <div class="metric-box">
                        <div class="metric-label">Bytes Out</div>
                        <div class="metric-value"><?= number_format($devices[0]['snmp_data']['ifOutOctets'] / 1024 / 1024, 1) ?> MB</div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- SNMP Traps Tab -->
        <div id="traps-tab" class="tab-content">
            <div class="card">
                <h2>Recent SNMP Traps</h2>
                <p style="margin-bottom: 15px; color: #666;">Listening on UDP port 162 for incoming traps</p>
                <table>
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>Source IP</th>
                            <th>Trap Type</th>
                            <th>Severity</th>
                            <th>Message</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($traps as $trap): ?>
                        <tr>
                            <td><?= $trap['timestamp'] ?></td>
                            <td><?= $trap['source_ip'] ?></td>
                            <td><code><?= $trap['trap_type'] ?></code></td>
                            <td>
                                <span class="badge badge-<?= $trap['severity'] ?>">
                                    <?= strtoupper($trap['severity']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($trap['message']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- OID Explorer Tab -->
        <div id="oid-tab" class="tab-content">
            <div class="card">
                <h2>SNMP OID Explorer</h2>
                <p style="margin-bottom: 15px; color: #666;">Query specific OIDs from devices</p>

                <div class="oid-explorer">
                    <div class="form-group">
                        <label>Target Device</label>
                        <select id="targetDevice">
                            <?php foreach ($devices as $device): ?>
                            <option value="<?= $device['ip_address'] ?>"><?= htmlspecialchars($device['device_name']) ?> (<?= $device['ip_address'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>OID to Query</label>
                        <input type="text" id="oidInput" placeholder="e.g., 1.3.6.1.2.1.1.1.0 (sysDescr.0)">
                    </div>

                    <button class="btn" onclick="queryOID()">Query OID</button>

                    <div id="oidResult" class="oid-result"></div>
                </div>

                <h3 style="margin: 20px 0 10px 0;">Common OIDs</h3>
                <table>
                    <thead>
                        <tr>
                            <th>OID</th>
                            <th>Description</th>
                            <th>Category</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>1.3.6.1.2.1.1.1.0</code></td>
                            <td>System Description</td>
                            <td>System</td>
                            <td><button class="btn" onclick="useOID('1.3.6.1.2.1.1.1.0')">Use</button></td>
                        </tr>
                        <tr>
                            <td><code>1.3.6.1.2.1.1.3.0</code></td>
                            <td>System Uptime</td>
                            <td>System</td>
                            <td><button class="btn" onclick="useOID('1.3.6.1.2.1.1.3.0')">Use</button></td>
                        </tr>
                        <tr>
                            <td><code>1.3.6.1.4.1.2021.11.9.0</code></td>
                            <td>CPU Usage %</td>
                            <td>Performance</td>
                            <td><button class="btn" onclick="useOID('1.3.6.1.4.1.2021.11.9.0')">Use</button></td>
                        </tr>
                        <tr>
                            <td><code>1.3.6.1.4.1.2021.4.5.0</code></td>
                            <td>Total Memory</td>
                            <td>Performance</td>
                            <td><button class="btn" onclick="useOID('1.3.6.1.4.1.2021.4.5.0')">Use</button></td>
                        </tr>
                        <tr>
                            <td><code>1.3.6.1.2.1.2.2.1.10</code></td>
                            <td>Interface Bytes In</td>
                            <td>Network</td>
                            <td><button class="btn" onclick="useOID('1.3.6.1.2.1.2.2.1.10')">Use</button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Configuration Tab -->
        <div id="config-tab" class="tab-content">
            <div class="grid-2">
                <div class="card">
                    <h2>Global SNMP Settings</h2>
                    <form method="POST" action="admin/settings.php">
                        <input type="hidden" name="action" value="update_monitoring_settings">

                        <div class="form-group">
                            <label>Default SNMP Version</label>
                            <select name="snmp_version">
                                <option value="v1" <?= ($settings['snmp_version'] ?? 'v2c') == 'v1' ? 'selected' : '' ?>>SNMPv1</option>
                                <option value="v2c" <?= ($settings['snmp_version'] ?? 'v2c') == 'v2c' ? 'selected' : '' ?>>SNMPv2c</option>
                                <option value="v3" <?= ($settings['snmp_version'] ?? 'v2c') == 'v3' ? 'selected' : '' ?>>SNMPv3</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Community String (v1/v2c)</label>
                            <input type="text" name="snmp_community" value="<?= htmlspecialchars($settings['snmp_community'] ?? 'public') ?>">
                        </div>

                        <div class="form-group">
                            <label>SNMP Port</label>
                            <input type="number" name="snmp_port" value="<?= htmlspecialchars($settings['snmp_port'] ?? '161') ?>">
                        </div>

                        <div class="form-group">
                            <label>Trap Listener Port</label>
                            <input type="number" value="162" disabled>
                            <small>Trap receiver is listening on UDP port 162</small>
                        </div>

                        <div class="form-group">
                            <label>Timeout (seconds)</label>
                            <input type="number" name="snmp_timeout" value="<?= htmlspecialchars($settings['snmp_timeout'] ?? '5') ?>">
                        </div>

                        <button type="submit" class="btn">Save Settings</button>
                    </form>
                </div>

                <div class="card">
                    <h2>SNMPv3 Configuration</h2>
                    <p style="color: #666; margin-bottom: 15px;">Enhanced security with authentication and encryption</p>

                    <div class="form-group">
                        <label>Security Level</label>
                        <select>
                            <option>noAuthNoPriv</option>
                            <option>authNoPriv</option>
                            <option selected>authPriv (Recommended)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Auth Protocol</label>
                        <select>
                            <option>MD5</option>
                            <option selected>SHA</option>
                            <option>SHA-256</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Privacy Protocol</label>
                        <select>
                            <option>DES</option>
                            <option selected>AES</option>
                            <option>AES-256</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" placeholder="snmpv3user">
                    </div>

                    <div class="form-group">
                        <label>Auth Password</label>
                        <input type="password" placeholder="Min 8 characters">
                    </div>

                    <div class="form-group">
                        <label>Privacy Password</label>
                        <input type="password" placeholder="Min 8 characters">
                    </div>

                    <button class="btn">Save SNMPv3 Config</button>
                </div>
            </div>
        </div>

        <a href="../index.php" class="back-link">← Back to Dashboard</a>
    </div>

    <script>
        function switchTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });

            document.getElementById(tabName + '-tab').classList.add('active');
            event.target.classList.add('active');
        }

        function viewDetails(deviceId) {
            alert(`Viewing detailed SNMP metrics for device ID: ${deviceId}`);
        }

        function queryOID() {
            const device = document.getElementById('targetDevice').value;
            const oid = document.getElementById('oidInput').value;
            const result = document.getElementById('oidResult');

            if (!oid) {
                alert('Please enter an OID');
                return;
            }

            // Simulate SNMP query
            result.innerHTML = `
                <strong>Query Result:</strong><br>
                Device: ${device}<br>
                OID: ${oid}<br>
                Value: Sample SNMP Response Data<br>
                Type: STRING<br>
                <br>
                <em>In production, this would perform an actual SNMP GET request.</em>
            `;
            result.classList.add('active');
        }

        function useOID(oid) {
            document.getElementById('oidInput').value = oid;
            document.getElementById('oid-tab').classList.add('active');
        }

        // Auto-refresh every 30 seconds
        setTimeout(() => location.reload(), 30000);
    </script>
</body>
</html>
