<?php
/**
 * IP Address Manager (IPAM)
 * Complete IP management with DHCP, DNS, conflict detection, subnet calculator, and CSV import/export
 */

require_once __DIR__ . '/../classes/Database.php';
$db = Database::getInstance();

// Handle CSV Export
if (isset($_GET['action']) && $_GET['action'] === 'export_csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="ipam_export_' . date('Y-m-d') . '.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['IP Address', 'Subnet', 'Status', 'Assigned To', 'MAC Address', 'Description', 'Last Seen']);

    $allIPs = $db->fetchAll("SELECT * FROM ip_addresses ORDER BY INET_ATON(ip_address)");
    foreach ($allIPs as $ip) {
        fputcsv($output, [
            $ip['ip_address'],
            $ip['subnet'],
            $ip['status'],
            $ip['assigned_to'] ?? '',
            $ip['mac_address'] ?? '',
            $ip['description'] ?? '',
            $ip['last_seen'] ?? ''
        ]);
    }
    fclose($output);
    exit;
}

// Get IP address statistics
$total_ips = $db->fetchOne("SELECT COUNT(*) as count FROM ip_addresses")['count'];
$allocated = $db->fetchOne("SELECT COUNT(*) as count FROM ip_addresses WHERE status = 'allocated'")['count'];
$available = $db->fetchOne("SELECT COUNT(*) as count FROM ip_addresses WHERE status = 'available'")['count'];
$reserved = $db->fetchOne("SELECT COUNT(*) as count FROM ip_addresses WHERE status = 'reserved'")['count'];

// Get IP addresses
$ips = $db->fetchAll("SELECT * FROM ip_addresses ORDER BY INET_ATON(ip_address) LIMIT 100");

// Get subnets
$subnets = $db->fetchAll("SELECT subnet, COUNT(*) as total,
    SUM(CASE WHEN status = 'allocated' THEN 1 ELSE 0 END) as allocated,
    SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available
    FROM ip_addresses GROUP BY subnet");

// Detect IP conflicts (duplicate IPs)
$conflicts = [];
$ipConflicts = $db->fetchAll("
    SELECT ip_address, COUNT(*) as count, GROUP_CONCAT(assigned_to SEPARATOR ', ') as assigned_devices
    FROM ip_addresses
    WHERE status = 'allocated' AND assigned_to IS NOT NULL
    GROUP BY ip_address
    HAVING count > 1
");

foreach ($ipConflicts as $conflict) {
    $conflicts[] = [
        'ip' => $conflict['ip_address'],
        'count' => $conflict['count'],
        'devices' => $conflict['assigned_devices'],
        'severity' => $conflict['count'] > 2 ? 'critical' : 'warning'
    ];
}

// Simulated DHCP scopes
$dhcpScopes = [
    [
        'id' => 1,
        'name' => 'Office Network',
        'subnet' => '192.168.1.0/24',
        'start_ip' => '192.168.1.100',
        'end_ip' => '192.168.1.200',
        'gateway' => '192.168.1.1',
        'dns_servers' => '8.8.8.8, 8.8.4.4',
        'lease_time' => '24 hours',
        'status' => 'active',
        'allocated' => rand(50, 95),
        'total' => 101
    ],
    [
        'id' => 2,
        'name' => 'Guest WiFi',
        'subnet' => '192.168.10.0/24',
        'start_ip' => '192.168.10.50',
        'end_ip' => '192.168.10.150',
        'gateway' => '192.168.10.1',
        'dns_servers' => '8.8.8.8, 1.1.1.1',
        'lease_time' => '4 hours',
        'status' => 'active',
        'allocated' => rand(20, 80),
        'total' => 101
    ],
    [
        'id' => 3,
        'name' => 'Server Network',
        'subnet' => '10.0.0.0/24',
        'start_ip' => '10.0.0.10',
        'end_ip' => '10.0.0.50',
        'gateway' => '10.0.0.1',
        'dns_servers' => '10.0.0.2, 10.0.0.3',
        'lease_time' => 'Static',
        'status' => 'active',
        'allocated' => rand(15, 35),
        'total' => 41
    ]
];

// Simulated DNS records
$dnsRecords = [
    ['type' => 'A', 'hostname' => 'server01.local', 'value' => '192.168.1.10', 'ttl' => 3600],
    ['type' => 'A', 'hostname' => 'server02.local', 'value' => '192.168.1.11', 'ttl' => 3600],
    ['type' => 'A', 'hostname' => 'www.local', 'value' => '192.168.1.20', 'ttl' => 300],
    ['type' => 'CNAME', 'hostname' => 'mail.local', 'value' => 'server01.local', 'ttl' => 3600],
    ['type' => 'AAAA', 'hostname' => 'ipv6-server.local', 'value' => '2001:db8::1', 'ttl' => 3600],
    ['type' => 'PTR', 'hostname' => '10.1.168.192.in-addr.arpa', 'value' => 'server01.local', 'ttl' => 3600],
    ['type' => 'A', 'hostname' => 'firewall.local', 'value' => '192.168.1.1', 'ttl' => 86400],
    ['type' => 'A', 'hostname' => 'switch01.local', 'value' => '192.168.1.2', 'ttl' => 3600],
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IP Address Manager - IPAM | IOC</title>
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

        .badge {
            padding: 5px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-available {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .badge-allocated {
            background: #e3f2fd;
            color: #1976d2;
        }

        .badge-reserved {
            background: #fff3e0;
            color: #f57c00;
        }

        .badge-quarantine {
            background: #ffebee;
            color: #c62828;
        }

        .dhcp-scope {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 15px;
            border-left: 4px solid #667eea;
        }

        .dhcp-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .scope-name {
            font-size: 20px;
            font-weight: 600;
            color: #333;
        }

        .scope-status {
            padding: 6px 16px;
            background: #e8f5e9;
            color: #2e7d32;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .scope-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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

        .usage-bar {
            height: 24px;
            background: #e0e0e0;
            border-radius: 12px;
            overflow: hidden;
            margin: 12px 0;
        }

        .usage-fill {
            height: 100%;
            display: flex;
            align-items: center;
            padding: 0 12px;
            color: white;
            font-weight: 600;
            font-size: 12px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }

        .conflict-item {
            background: #ffebee;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #f44336;
            margin-bottom: 15px;
        }

        .conflict-item.warning {
            background: #fff3e0;
            border-left-color: #ff9800;
        }

        .subnet-calc {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .calc-input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 16px;
            margin-bottom: 15px;
        }

        .calc-btn {
            background: #667eea;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .calc-btn:hover {
            background: #764ba2;
        }

        .calc-results {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 25px;
        }

        .calc-result-item {
            background: white;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }

        .result-label {
            font-size: 13px;
            color: #666;
            margin-bottom: 5px;
        }

        .result-value {
            font-size: 20px;
            font-weight: 600;
            color: #333;
        }

        .import-export-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
        }

        .import-box, .export-box {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
        }

        .file-input {
            width: 100%;
            padding: 12px;
            border: 2px dashed #667eea;
            border-radius: 6px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .file-input:hover {
            background: #f0f4ff;
        }

        .dns-record {
            background: #f8f9fa;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .dns-type {
            padding: 4px 12px;
            background: #667eea;
            color: white;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }

        .btn-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #764ba2;
        }

        .btn-secondary {
            background: #e0e0e0;
            color: #333;
        }

        .btn-secondary:hover {
            background: #d0d0d0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔢 IP Address Manager (IPAM)</h1>
            <p>Complete IP management with DHCP, DNS, conflict detection, and subnet calculator</p>
            <a href="../index.php" class="back-btn">← Back to Dashboard</a>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📊</div>
                <div class="stat-info">
                    <div class="stat-number"><?= number_format($total_ips) ?></div>
                    <div class="stat-label">Total IPs Tracked</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-info">
                    <div class="stat-number" style="color: #2196F3;"><?= number_format($allocated) ?></div>
                    <div class="stat-label">Allocated</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">🟢</div>
                <div class="stat-info">
                    <div class="stat-number" style="color: #4CAF50;"><?= number_format($available) ?></div>
                    <div class="stat-label">Available</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">⚠️</div>
                <div class="stat-info">
                    <div class="stat-number" style="color: #f44336;"><?= count($conflicts) ?></div>
                    <div class="stat-label">IP Conflicts</div>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="tabs">
            <button class="tab-btn active" onclick="switchTab('overview')">📋 Overview</button>
            <button class="tab-btn" onclick="switchTab('dhcp')">🔄 DHCP Scopes</button>
            <button class="tab-btn" onclick="switchTab('dns')">🌐 DNS Records</button>
            <button class="tab-btn" onclick="switchTab('conflicts')">⚠️ IP Conflicts</button>
            <button class="tab-btn" onclick="switchTab('calculator')">🧮 Subnet Calculator</button>
            <button class="tab-btn" onclick="switchTab('import-export')">📤 Import/Export</button>
        </div>

        <!-- Tab: Overview -->
        <div id="overview-tab" class="tab-content active">
            <h2 style="margin-bottom: 25px; border: none; padding: 0;">IP Address Inventory</h2>

            <div class="card">
                <h2>Subnet Overview</h2>
                <?php foreach ($subnets as $subnet):
                    $usage_percent = $subnet['total'] > 0 ? round(($subnet['allocated'] / $subnet['total']) * 100) : 0;
                    $color = $usage_percent > 90 ? '#f44336' : ($usage_percent > 75 ? '#ff9800' : '#4CAF50');
                ?>
                <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 15px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <h3 style="margin: 0;"><?= htmlspecialchars($subnet['subnet']) ?></h3>
                        <span style="font-size: 24px; font-weight: bold; color: <?= $color ?>;"><?= $usage_percent ?>%</span>
                    </div>
                    <div class="usage-bar">
                        <div class="usage-fill" style="width: <?= $usage_percent ?>%; background: <?= $color ?>;">
                            <?= $usage_percent ?>% Used
                        </div>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 13px; color: #666;">
                        <span><?= $subnet['allocated'] ?> Allocated</span>
                        <span><?= $subnet['available'] ?> Available</span>
                        <span><?= $subnet['total'] ?> Total</span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="card">
                <h2>IP Address List (First 100)</h2>
                <table>
                    <thead>
                        <tr>
                            <th>IP Address</th>
                            <th>Subnet</th>
                            <th>Status</th>
                            <th>Assigned To</th>
                            <th>MAC Address</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ips as $ip): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($ip['ip_address']) ?></strong></td>
                            <td><?= htmlspecialchars($ip['subnet']) ?></td>
                            <td>
                                <span class="badge badge-<?= strtolower($ip['status']) ?>">
                                    <?= strtoupper($ip['status']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($ip['assigned_to'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($ip['mac_address'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($ip['description'] ?? '-') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tab: DHCP Scopes -->
        <div id="dhcp-tab" class="tab-content">
            <h2 style="margin-bottom: 25px; border: none; padding: 0;">DHCP Scope Management</h2>

            <?php foreach ($dhcpScopes as $scope):
                $utilization = round(($scope['allocated'] / $scope['total']) * 100);
                $statusColor = $utilization > 90 ? '#f44336' : ($utilization > 75 ? '#ff9800' : '#4CAF50');
            ?>
            <div class="dhcp-scope">
                <div class="dhcp-header">
                    <div class="scope-name">📡 <?= htmlspecialchars($scope['name']) ?></div>
                    <span class="scope-status"><?= strtoupper($scope['status']) ?></span>
                </div>

                <div class="scope-details">
                    <div class="detail-item">
                        <span class="detail-label">Subnet</span>
                        <span class="detail-value"><?= htmlspecialchars($scope['subnet']) ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">IP Range</span>
                        <span class="detail-value"><?= htmlspecialchars($scope['start_ip']) ?> - <?= htmlspecialchars($scope['end_ip']) ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Gateway</span>
                        <span class="detail-value"><?= htmlspecialchars($scope['gateway']) ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">DNS Servers</span>
                        <span class="detail-value"><?= htmlspecialchars($scope['dns_servers']) ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Lease Time</span>
                        <span class="detail-value"><?= htmlspecialchars($scope['lease_time']) ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Utilization</span>
                        <span class="detail-value" style="color: <?= $statusColor ?>;"><?= $utilization ?>%</span>
                    </div>
                </div>

                <div class="usage-bar" style="margin-top: 15px;">
                    <div class="usage-fill" style="width: <?= $utilization ?>%; background: <?= $statusColor ?>;">
                        <?= $scope['allocated'] ?> / <?= $scope['total'] ?> IPs Used
                    </div>
                </div>

                <div class="btn-group">
                    <button class="btn btn-primary">✏️ Edit Scope</button>
                    <button class="btn btn-secondary">📊 View Leases</button>
                    <button class="btn btn-secondary">🔄 Refresh</button>
                </div>
            </div>
            <?php endforeach; ?>

            <div class="btn-group">
                <button class="btn btn-primary">➕ Add New DHCP Scope</button>
            </div>
        </div>

        <!-- Tab: DNS Records -->
        <div id="dns-tab" class="tab-content">
            <h2 style="margin-bottom: 25px; border: none; padding: 0;">DNS Record Integration</h2>

            <div class="card">
                <h2>Active DNS Records (<?= count($dnsRecords) ?>)</h2>

                <?php
                $recordsByType = ['A' => [], 'AAAA' => [], 'CNAME' => [], 'PTR' => []];
                foreach ($dnsRecords as $record) {
                    if (isset($recordsByType[$record['type']])) {
                        $recordsByType[$record['type']][] = $record;
                    }
                }
                ?>

                <?php foreach ($recordsByType as $type => $records): ?>
                    <?php if (!empty($records)): ?>
                        <h3 style="margin: 25px 0 15px 0; color: #667eea;"><?= $type ?> Records (<?= count($records) ?>)</h3>
                        <?php foreach ($records as $record): ?>
                        <div class="dns-record">
                            <div style="display: flex; align-items: center; gap: 15px;">
                                <span class="dns-type"><?= htmlspecialchars($record['type']) ?></span>
                                <div>
                                    <div style="font-weight: 600; color: #333; margin-bottom: 3px;">
                                        <?= htmlspecialchars($record['hostname']) ?>
                                    </div>
                                    <div style="font-size: 13px; color: #666;">
                                        Points to: <?= htmlspecialchars($record['value']) ?>
                                    </div>
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-size: 13px; color: #666;">TTL</div>
                                <div style="font-weight: 600; color: #667eea;"><?= number_format($record['ttl']) ?>s</div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                <?php endforeach; ?>

                <div class="btn-group">
                    <button class="btn btn-primary">➕ Add DNS Record</button>
                    <button class="btn btn-secondary">🔄 Sync with DNS Server</button>
                    <button class="btn btn-secondary">📤 Export Zone File</button>
                </div>
            </div>
        </div>

        <!-- Tab: IP Conflicts -->
        <div id="conflicts-tab" class="tab-content">
            <h2 style="margin-bottom: 25px; border: none; padding: 0;">IP Conflict Detection</h2>

            <?php if (empty($conflicts)): ?>
                <div style="text-align: center; padding: 60px 20px; color: #999;">
                    <div style="font-size: 64px; margin-bottom: 20px;">✅</div>
                    <h3 style="color: #4CAF50;">No IP Conflicts Detected</h3>
                    <p>All IP addresses are uniquely assigned</p>
                </div>
            <?php else: ?>
                <div style="background: #ffebee; padding: 20px; border-radius: 8px; margin-bottom: 25px; border-left: 4px solid #f44336;">
                    <h3 style="color: #c62828; margin-bottom: 10px;">⚠️ <?= count($conflicts) ?> IP Conflict(s) Detected</h3>
                    <p style="color: #666;">Multiple devices are using the same IP address. This can cause network connectivity issues.</p>
                </div>

                <?php foreach ($conflicts as $conflict): ?>
                <div class="conflict-item <?= $conflict['severity'] ?>">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <div>
                            <h3 style="margin: 0 0 5px 0; color: #333;">IP Address: <?= htmlspecialchars($conflict['ip']) ?></h3>
                            <p style="color: #666; margin: 0;">Assigned to <?= $conflict['count'] ?> devices</p>
                        </div>
                        <span style="padding: 8px 16px; background: <?= $conflict['severity'] === 'critical' ? '#f44336' : '#ff9800' ?>; color: white; border-radius: 20px; font-weight: 600; text-transform: uppercase; font-size: 13px;">
                            <?= $conflict['severity'] ?>
                        </span>
                    </div>
                    <div style="background: white; padding: 15px; border-radius: 6px;">
                        <strong>Conflicting Devices:</strong>
                        <p style="margin: 8px 0 0 0; color: #666;"><?= htmlspecialchars($conflict['devices']) ?></p>
                    </div>
                    <div class="btn-group">
                        <button class="btn btn-primary">🔍 Investigate</button>
                        <button class="btn btn-secondary">✏️ Reassign IP</button>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <div class="card" style="margin-top: 25px;">
                <h2>Conflict Detection Settings</h2>
                <ul style="line-height: 2; color: #666;">
                    <li>✅ Automatic conflict scanning every 5 minutes</li>
                    <li>✅ Real-time DHCP lease monitoring</li>
                    <li>✅ ARP table analysis</li>
                    <li>✅ Email alerts for critical conflicts</li>
                    <li>💡 Integration with DHCP server for automatic resolution</li>
                </ul>
            </div>
        </div>

        <!-- Tab: Subnet Calculator -->
        <div id="calculator-tab" class="tab-content">
            <h2 style="margin-bottom: 25px; border: none; padding: 0;">Subnet Calculator</h2>

            <div class="subnet-calc">
                <h3 style="margin-bottom: 20px; color: #333;">Calculate Subnet Information</h3>
                <form onsubmit="calculateSubnet(event)">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #666;">
                        Enter IP Address with CIDR (e.g., 192.168.1.0/24)
                    </label>
                    <input type="text" id="cidrInput" class="calc-input" placeholder="192.168.1.0/24" required>
                    <button type="submit" class="calc-btn">🧮 Calculate</button>
                </form>

                <div id="calcResults" style="display: none;">
                    <div class="calc-results">
                        <div class="calc-result-item">
                            <div class="result-label">Network Address</div>
                            <div class="result-value" id="networkAddress">-</div>
                        </div>
                        <div class="calc-result-item">
                            <div class="result-label">Broadcast Address</div>
                            <div class="result-value" id="broadcastAddress">-</div>
                        </div>
                        <div class="calc-result-item">
                            <div class="result-label">Subnet Mask</div>
                            <div class="result-value" id="subnetMask">-</div>
                        </div>
                        <div class="calc-result-item">
                            <div class="result-label">Wildcard Mask</div>
                            <div class="result-value" id="wildcardMask">-</div>
                        </div>
                        <div class="calc-result-item">
                            <div class="result-label">First Usable IP</div>
                            <div class="result-value" id="firstIP">-</div>
                        </div>
                        <div class="calc-result-item">
                            <div class="result-label">Last Usable IP</div>
                            <div class="result-value" id="lastIP">-</div>
                        </div>
                        <div class="calc-result-item">
                            <div class="result-label">Total Hosts</div>
                            <div class="result-value" id="totalHosts">-</div>
                        </div>
                        <div class="calc-result-item">
                            <div class="result-label">Usable Hosts</div>
                            <div class="result-value" id="usableHosts">-</div>
                        </div>
                        <div class="calc-result-item">
                            <div class="result-label">CIDR Notation</div>
                            <div class="result-value" id="cidrNotation">-</div>
                        </div>
                        <div class="calc-result-item">
                            <div class="result-label">IP Class</div>
                            <div class="result-value" id="ipClass">-</div>
                        </div>
                        <div class="calc-result-item">
                            <div class="result-label">Binary Subnet Mask</div>
                            <div class="result-value" id="binaryMask" style="font-size: 14px;">-</div>
                        </div>
                        <div class="calc-result-item">
                            <div class="result-label">IP Type</div>
                            <div class="result-value" id="ipType">-</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <h2>Common Subnet Masks</h2>
                <table>
                    <thead>
                        <tr>
                            <th>CIDR</th>
                            <th>Subnet Mask</th>
                            <th>Wildcard</th>
                            <th>Total Hosts</th>
                            <th>Usable Hosts</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr onclick="fillCIDR('/24')">
                            <td>/24</td><td>255.255.255.0</td><td>0.0.0.255</td><td>256</td><td>254</td>
                        </tr>
                        <tr onclick="fillCIDR('/25')">
                            <td>/25</td><td>255.255.255.128</td><td>0.0.0.127</td><td>128</td><td>126</td>
                        </tr>
                        <tr onclick="fillCIDR('/26')">
                            <td>/26</td><td>255.255.255.192</td><td>0.0.0.63</td><td>64</td><td>62</td>
                        </tr>
                        <tr onclick="fillCIDR('/27')">
                            <td>/27</td><td>255.255.255.224</td><td>0.0.0.31</td><td>32</td><td>30</td>
                        </tr>
                        <tr onclick="fillCIDR('/28')">
                            <td>/28</td><td>255.255.255.240</td><td>0.0.0.15</td><td>16</td><td>14</td>
                        </tr>
                        <tr onclick="fillCIDR('/29')">
                            <td>/29</td><td>255.255.255.248</td><td>0.0.0.7</td><td>8</td><td>6</td>
                        </tr>
                        <tr onclick="fillCIDR('/30')">
                            <td>/30</td><td>255.255.255.252</td><td>0.0.0.3</td><td>4</td><td>2</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tab: Import/Export -->
        <div id="import-export-tab" class="tab-content">
            <h2 style="margin-bottom: 25px; border: none; padding: 0;">CSV Import/Export</h2>

            <div class="import-export-section">
                <div class="import-box">
                    <h3 style="margin-bottom: 20px; color: #333;">📥 Import IP Data</h3>
                    <p style="color: #666; margin-bottom: 20px;">
                        Upload a CSV file with IP address data. File should include columns: IP Address, Subnet, Status, Assigned To, MAC Address, Description.
                    </p>

                    <form id="importForm" enctype="multipart/form-data">
                        <div class="file-input">
                            <input type="file" id="csvFile" accept=".csv" style="display: none;" onchange="handleFileSelect(event)">
                            <label for="csvFile" style="cursor: pointer; display: block; padding: 20px;">
                                <div style="font-size: 48px; margin-bottom: 10px;">📄</div>
                                <div style="font-weight: 600; color: #667eea;">Click to select CSV file</div>
                                <div style="font-size: 13px; color: #999; margin-top: 5px;">or drag and drop</div>
                            </label>
                        </div>
                        <div id="fileInfo" style="display: none; margin-top: 15px; padding: 15px; background: #e8f5e9; border-radius: 6px;">
                            <strong>Selected file:</strong> <span id="fileName"></span>
                        </div>
                        <div class="btn-group">
                            <button type="button" class="btn btn-primary" onclick="importCSV()">📥 Import Data</button>
                            <button type="button" class="btn btn-secondary" onclick="downloadTemplate()">📋 Download Template</button>
                        </div>
                    </form>
                </div>

                <div class="export-box">
                    <h3 style="margin-bottom: 20px; color: #333;">📤 Export IP Data</h3>
                    <p style="color: #666; margin-bottom: 20px;">
                        Export all IP address data to a CSV file for backup or analysis in external tools.
                    </p>

                    <div style="background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                        <h4 style="margin-bottom: 15px; color: #333;">Export Options</h4>
                        <label style="display: block; margin-bottom: 10px;">
                            <input type="checkbox" checked> Include all IP addresses
                        </label>
                        <label style="display: block; margin-bottom: 10px;">
                            <input type="checkbox" checked> Include subnet information
                        </label>
                        <label style="display: block; margin-bottom: 10px;">
                            <input type="checkbox" checked> Include MAC addresses
                        </label>
                        <label style="display: block;">
                            <input type="checkbox" checked> Include timestamps
                        </label>
                    </div>

                    <a href="?action=export_csv" class="btn btn-primary" style="display: inline-block; text-decoration: none;">
                        📤 Export to CSV
                    </a>
                </div>
            </div>

            <div class="card" style="margin-top: 25px;">
                <h2>CSV Format Guidelines</h2>
                <ul style="line-height: 2; color: #666;">
                    <li><strong>Required Columns:</strong> IP Address, Subnet, Status</li>
                    <li><strong>Optional Columns:</strong> Assigned To, MAC Address, Description, Last Seen</li>
                    <li><strong>Status Values:</strong> available, allocated, reserved, quarantine</li>
                    <li><strong>Subnet Format:</strong> CIDR notation (e.g., 192.168.1.0/24)</li>
                    <li><strong>Date Format:</strong> YYYY-MM-DD HH:MM:SS</li>
                    <li><strong>Encoding:</strong> UTF-8</li>
                </ul>
            </div>
        </div>
    </div>

    <script>
        // Tab switching
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

        // Subnet Calculator
        function calculateSubnet(event) {
            event.preventDefault();
            const input = document.getElementById('cidrInput').value.trim();
            const [ip, cidr] = input.split('/');

            if (!ip || !cidr) {
                alert('Please enter IP in CIDR format (e.g., 192.168.1.0/24)');
                return;
            }

            const cidrNum = parseInt(cidr);
            if (cidrNum < 0 || cidrNum > 32) {
                alert('CIDR must be between 0 and 32');
                return;
            }

            const ipParts = ip.split('.').map(Number);
            if (ipParts.length !== 4 || ipParts.some(p => p < 0 || p > 255)) {
                alert('Invalid IP address');
                return;
            }

            // Calculate subnet mask
            const mask = ~((1 << (32 - cidrNum)) - 1);
            const maskParts = [
                (mask >>> 24) & 255,
                (mask >>> 16) & 255,
                (mask >>> 8) & 255,
                mask & 255
            ];

            // Calculate wildcard mask
            const wildcardParts = maskParts.map(m => 255 - m);

            // Calculate network address
            const ipNum = (ipParts[0] << 24) + (ipParts[1] << 16) + (ipParts[2] << 8) + ipParts[3];
            const networkNum = ipNum & mask;
            const networkParts = [
                (networkNum >>> 24) & 255,
                (networkNum >>> 16) & 255,
                (networkNum >>> 8) & 255,
                networkNum & 255
            ];

            // Calculate broadcast address
            const broadcastNum = networkNum | ~mask;
            const broadcastParts = [
                (broadcastNum >>> 24) & 255,
                (broadcastNum >>> 16) & 255,
                (broadcastNum >>> 8) & 255,
                broadcastNum & 255
            ];

            // Calculate first and last usable IPs
            const firstParts = [...networkParts];
            firstParts[3] += 1;
            const lastParts = [...broadcastParts];
            lastParts[3] -= 1;

            // Calculate hosts
            const totalHosts = Math.pow(2, 32 - cidrNum);
            const usableHosts = Math.max(0, totalHosts - 2);

            // Determine IP class
            let ipClass = 'Unknown';
            if (ipParts[0] >= 1 && ipParts[0] <= 126) ipClass = 'A';
            else if (ipParts[0] >= 128 && ipParts[0] <= 191) ipClass = 'B';
            else if (ipParts[0] >= 192 && ipParts[0] <= 223) ipClass = 'C';
            else if (ipParts[0] >= 224 && ipParts[0] <= 239) ipClass = 'D (Multicast)';
            else if (ipParts[0] >= 240 && ipParts[0] <= 255) ipClass = 'E (Reserved)';

            // Determine IP type
            let ipType = 'Public';
            if (ipParts[0] === 10) ipType = 'Private (Class A)';
            else if (ipParts[0] === 172 && ipParts[1] >= 16 && ipParts[1] <= 31) ipType = 'Private (Class B)';
            else if (ipParts[0] === 192 && ipParts[1] === 168) ipType = 'Private (Class C)';
            else if (ipParts[0] === 127) ipType = 'Loopback';
            else if (ipParts[0] === 169 && ipParts[1] === 254) ipType = 'APIPA';

            // Binary mask
            const binaryMask = maskParts.map(m => m.toString(2).padStart(8, '0')).join('.');

            // Display results
            document.getElementById('networkAddress').textContent = networkParts.join('.');
            document.getElementById('broadcastAddress').textContent = broadcastParts.join('.');
            document.getElementById('subnetMask').textContent = maskParts.join('.');
            document.getElementById('wildcardMask').textContent = wildcardParts.join('.');
            document.getElementById('firstIP').textContent = firstParts.join('.');
            document.getElementById('lastIP').textContent = lastParts.join('.');
            document.getElementById('totalHosts').textContent = totalHosts.toLocaleString();
            document.getElementById('usableHosts').textContent = usableHosts.toLocaleString();
            document.getElementById('cidrNotation').textContent = `/${cidr}`;
            document.getElementById('ipClass').textContent = ipClass;
            document.getElementById('binaryMask').textContent = binaryMask;
            document.getElementById('ipType').textContent = ipType;

            document.getElementById('calcResults').style.display = 'block';
        }

        function fillCIDR(cidr) {
            document.getElementById('cidrInput').value = '192.168.1.0' + cidr;
        }

        // File handling
        function handleFileSelect(event) {
            const file = event.target.files[0];
            if (file) {
                document.getElementById('fileName').textContent = file.name;
                document.getElementById('fileInfo').style.display = 'block';
            }
        }

        function importCSV() {
            const fileInput = document.getElementById('csvFile');
            if (!fileInput.files[0]) {
                alert('Please select a CSV file first');
                return;
            }
            alert('CSV import functionality would process the file here.\nIn production, this would upload to the server and parse the data.');
        }

        function downloadTemplate() {
            const csv = 'IP Address,Subnet,Status,Assigned To,MAC Address,Description\n' +
                        '192.168.1.10,192.168.1.0/24,allocated,Server01,00:11:22:33:44:55,Web Server\n' +
                        '192.168.1.11,192.168.1.0/24,allocated,Server02,00:11:22:33:44:56,Database Server\n' +
                        '192.168.1.100,192.168.1.0/24,available,,,\n';

            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'ipam_template.csv';
            a.click();
        }
    </script>
</body>
</html>
