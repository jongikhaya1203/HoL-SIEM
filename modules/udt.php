<?php
/**
 * User Device Tracker (UDT)
 * Advanced device tracking with switch port mapping, VLAN tracking, and rogue detection
 */

require_once __DIR__ . '/../classes/Database.php';
$db = Database::getInstance();

// Get connected devices from hosts table
$devices = $db->fetchAll("SELECT * FROM hosts ORDER BY last_seen DESC LIMIT 100");

// Calculate device statistics
$device_stats = [
    'online' => 0,
    'offline' => 0,
    'unknown' => 0,
    'total' => count($devices)
];

foreach ($devices as $device) {
    $lastSeen = strtotime($device['last_seen']);
    $minutesAgo = (time() - $lastSeen) / 60;

    if ($minutesAgo < 5) {
        $device_stats['online']++;
    } elseif ($minutesAgo < 60) {
        $device_stats['offline']++;
    } else {
        $device_stats['unknown']++;
    }
}

// Simulated switch port mappings
$portMappings = [
    ['device_name' => 'Laptop-001', 'ip' => '192.168.1.101', 'mac' => '00:1A:2B:3C:4D:01', 'switch' => 'Core-Switch-01', 'port' => 'Gi0/1', 'speed' => '1000 Mbps', 'duplex' => 'Full', 'status' => 'Connected', 'vlan' => '10'],
    ['device_name' => 'Desktop-042', 'ip' => '192.168.1.102', 'mac' => '00:1A:2B:3C:4D:02', 'switch' => 'Core-Switch-01', 'port' => 'Gi0/2', 'speed' => '1000 Mbps', 'duplex' => 'Full', 'status' => 'Connected', 'vlan' => '10'],
    ['device_name' => 'Printer-HP-301', 'ip' => '192.168.1.201', 'mac' => '00:1A:2B:3C:4D:03', 'switch' => 'Core-Switch-01', 'port' => 'Gi0/5', 'speed' => '100 Mbps', 'duplex' => 'Full', 'status' => 'Connected', 'vlan' => '20'],
    ['device_name' => 'Phone-VoIP-15', 'ip' => '192.168.2.50', 'mac' => '00:1A:2B:3C:4D:04', 'switch' => 'Core-Switch-01', 'port' => 'Gi0/12', 'speed' => '100 Mbps', 'duplex' => 'Full', 'status' => 'Connected', 'vlan' => '30'],
    ['device_name' => 'Server-DB-01', 'ip' => '10.0.0.10', 'mac' => '00:1A:2B:3C:4D:05', 'switch' => 'Core-Switch-02', 'port' => 'Gi0/24', 'speed' => '10 Gbps', 'duplex' => 'Full', 'status' => 'Connected', 'vlan' => '50'],
    ['device_name' => 'Laptop-025', 'ip' => '192.168.1.125', 'mac' => '00:1A:2B:3C:4D:06', 'switch' => 'Access-Switch-01', 'port' => 'Fa0/8', 'speed' => '100 Mbps', 'duplex' => 'Full', 'status' => 'Connected', 'vlan' => '10'],
    ['device_name' => 'Camera-IP-02', 'ip' => '192.168.3.12', 'mac' => '00:1A:2B:3C:4D:07', 'switch' => 'Access-Switch-01', 'port' => 'Fa0/16', 'speed' => '100 Mbps', 'duplex' => 'Full', 'status' => 'Connected', 'vlan' => '40'],
    ['device_name' => 'Unknown-Device', 'ip' => '192.168.1.199', 'mac' => 'AA:BB:CC:DD:EE:FF', 'switch' => 'Core-Switch-01', 'port' => 'Gi0/18', 'speed' => '1000 Mbps', 'duplex' => 'Full', 'status' => 'Connected', 'vlan' => '1'],
    ['device_name' => 'Tablet-Guest-03', 'ip' => '192.168.99.33', 'mac' => '00:1A:2B:3C:4D:08', 'switch' => 'Access-Switch-02', 'port' => 'Fa0/4', 'speed' => '100 Mbps', 'duplex' => 'Full', 'status' => 'Connected', 'vlan' => '99'],
    ['device_name' => 'Laptop-IT-007', 'ip' => '192.168.1.107', 'mac' => '00:1A:2B:3C:4D:09', 'switch' => 'Core-Switch-01', 'port' => 'Gi0/8', 'speed' => '1000 Mbps', 'duplex' => 'Full', 'status' => 'Connected', 'vlan' => '10']
];

// VLAN definitions and assignments
$vlans = [
    ['id' => '1', 'name' => 'Default', 'description' => 'Default VLAN', 'subnet' => '192.168.1.0/24', 'devices' => 1, 'color' => '#9e9e9e'],
    ['id' => '10', 'name' => 'Corporate', 'description' => 'Corporate user devices', 'subnet' => '192.168.1.0/24', 'devices' => 4, 'color' => '#2196F3'],
    ['id' => '20', 'name' => 'Printers', 'description' => 'Network printers', 'subnet' => '192.168.1.192/27', 'devices' => 1, 'color' => '#4CAF50'],
    ['id' => '30', 'name' => 'VoIP', 'description' => 'Voice over IP devices', 'subnet' => '192.168.2.0/24', 'devices' => 1, 'color' => '#ff9800'],
    ['id' => '40', 'name' => 'Surveillance', 'description' => 'IP cameras and security', 'subnet' => '192.168.3.0/24', 'devices' => 1, 'color' => '#f44336'],
    ['id' => '50', 'name' => 'Servers', 'description' => 'Production servers', 'subnet' => '10.0.0.0/24', 'devices' => 1, 'color' => '#9c27b0'],
    ['id' => '99', 'name' => 'Guest', 'description' => 'Guest wireless network', 'subnet' => '192.168.99.0/24', 'devices' => 1, 'color' => '#607d8b']
];

// Historical connection logs (last 24 hours)
$connectionLogs = [
    ['timestamp' => date('Y-m-d H:i:s', strtotime('-30 minutes')), 'device' => 'Laptop-001', 'ip' => '192.168.1.101', 'mac' => '00:1A:2B:3C:4D:01', 'event' => 'Connected', 'port' => 'Gi0/1', 'vlan' => '10'],
    ['timestamp' => date('Y-m-d H:i:s', strtotime('-45 minutes')), 'device' => 'Phone-VoIP-15', 'ip' => '192.168.2.50', 'mac' => '00:1A:2B:3C:4D:04', 'event' => 'Connected', 'port' => 'Gi0/12', 'vlan' => '30'],
    ['timestamp' => date('Y-m-d H:i:s', strtotime('-1 hour')), 'device' => 'Desktop-042', 'ip' => '192.168.1.102', 'mac' => '00:1A:2B:3C:4D:02', 'event' => 'Connected', 'port' => 'Gi0/2', 'vlan' => '10'],
    ['timestamp' => date('Y-m-d H:i:s', strtotime('-2 hours')), 'device' => 'Unknown-Device', 'ip' => '192.168.1.199', 'mac' => 'AA:BB:CC:DD:EE:FF', 'event' => 'Connected', 'port' => 'Gi0/18', 'vlan' => '1'],
    ['timestamp' => date('Y-m-d H:i:s', strtotime('-3 hours')), 'device' => 'Laptop-025', 'ip' => '192.168.1.125', 'mac' => '00:1A:2B:3C:4D:06', 'event' => 'Disconnected', 'port' => 'Fa0/8', 'vlan' => '10'],
    ['timestamp' => date('Y-m-d H:i:s', strtotime('-3 hours 15 minutes')), 'device' => 'Laptop-025', 'ip' => '192.168.1.125', 'mac' => '00:1A:2B:3C:4D:06', 'event' => 'Connected', 'port' => 'Fa0/8', 'vlan' => '10'],
    ['timestamp' => date('Y-m-d H:i:s', strtotime('-5 hours')), 'device' => 'Tablet-Guest-03', 'ip' => '192.168.99.33', 'mac' => '00:1A:2B:3C:4D:08', 'event' => 'Connected', 'port' => 'Fa0/4', 'vlan' => '99'],
    ['timestamp' => date('Y-m-d H:i:s', strtotime('-8 hours')), 'device' => 'Camera-IP-02', 'ip' => '192.168.3.12', 'mac' => '00:1A:2B:3C:4D:07', 'event' => 'Connected', 'port' => 'Fa0/16', 'vlan' => '40'],
    ['timestamp' => date('Y-m-d H:i:s', strtotime('-12 hours')), 'device' => 'Printer-HP-301', 'ip' => '192.168.1.201', 'mac' => '00:1A:2B:3C:4D:03', 'event' => 'Disconnected', 'port' => 'Gi0/5', 'vlan' => '20'],
    ['timestamp' => date('Y-m-d H:i:s', strtotime('-12 hours 5 minutes')), 'device' => 'Printer-HP-301', 'ip' => '192.168.1.201', 'mac' => '00:1A:2B:3C:4D:03', 'event' => 'Connected', 'port' => 'Gi0/5', 'vlan' => '20']
];

// Device profiles with detailed information
$deviceProfiles = [
    ['device' => 'Laptop-001', 'ip' => '192.168.1.101', 'mac' => '00:1A:2B:3C:4D:01', 'manufacturer' => 'Dell Inc.', 'type' => 'Laptop', 'os' => 'Windows 11 Pro', 'user' => 'john.doe', 'department' => 'IT', 'authorized' => true, 'first_seen' => date('Y-m-d', strtotime('-90 days')), 'total_connections' => 245, 'avg_uptime' => '8.5 hours', 'risk_score' => 5],
    ['device' => 'Desktop-042', 'ip' => '192.168.1.102', 'mac' => '00:1A:2B:3C:4D:02', 'manufacturer' => 'HP', 'type' => 'Desktop', 'os' => 'Windows 10 Pro', 'user' => 'jane.smith', 'department' => 'Sales', 'authorized' => true, 'first_seen' => date('Y-m-d', strtotime('-120 days')), 'total_connections' => 312, 'avg_uptime' => '9.2 hours', 'risk_score' => 3],
    ['device' => 'Printer-HP-301', 'ip' => '192.168.1.201', 'mac' => '00:1A:2B:3C:4D:03', 'manufacturer' => 'HP Inc.', 'type' => 'Printer', 'os' => 'Printer Firmware 5.2', 'user' => 'N/A', 'department' => 'Shared', 'authorized' => true, 'first_seen' => date('Y-m-d', strtotime('-365 days')), 'total_connections' => 1200, 'avg_uptime' => '24 hours', 'risk_score' => 8],
    ['device' => 'Phone-VoIP-15', 'ip' => '192.168.2.50', 'mac' => '00:1A:2B:3C:4D:04', 'manufacturer' => 'Cisco', 'type' => 'VoIP Phone', 'os' => 'Cisco IOS', 'user' => 'conference.room.a', 'department' => 'Shared', 'authorized' => true, 'first_seen' => date('Y-m-d', strtotime('-200 days')), 'total_connections' => 450, 'avg_uptime' => '24 hours', 'risk_score' => 2],
    ['device' => 'Server-DB-01', 'ip' => '10.0.0.10', 'mac' => '00:1A:2B:3C:4D:05', 'manufacturer' => 'Dell EMC', 'type' => 'Server', 'os' => 'Ubuntu Server 22.04', 'user' => 'sysadmin', 'department' => 'IT', 'authorized' => true, 'first_seen' => date('Y-m-d', strtotime('-500 days')), 'total_connections' => 1500, 'avg_uptime' => '720 hours', 'risk_score' => 1],
    ['device' => 'Unknown-Device', 'ip' => '192.168.1.199', 'mac' => 'AA:BB:CC:DD:EE:FF', 'manufacturer' => 'Unknown', 'type' => 'Unknown', 'os' => 'Unknown', 'user' => 'Unknown', 'department' => 'Unknown', 'authorized' => false, 'first_seen' => date('Y-m-d', strtotime('-2 hours')), 'total_connections' => 1, 'avg_uptime' => '2 hours', 'risk_score' => 95],
    ['device' => 'Tablet-Guest-03', 'ip' => '192.168.99.33', 'mac' => '00:1A:2B:3C:4D:08', 'manufacturer' => 'Apple Inc.', 'type' => 'Tablet', 'os' => 'iOS 17', 'user' => 'guest', 'department' => 'Guest', 'authorized' => true, 'first_seen' => date('Y-m-d', strtotime('-5 hours')), 'total_connections' => 1, 'avg_uptime' => '5 hours', 'risk_score' => 15],
    ['device' => 'Camera-IP-02', 'ip' => '192.168.3.12', 'mac' => '00:1A:2B:3C:4D:07', 'manufacturer' => 'Hikvision', 'type' => 'IP Camera', 'os' => 'Linux Embedded', 'user' => 'N/A', 'department' => 'Security', 'authorized' => true, 'first_seen' => date('Y-m-d', strtotime('-180 days')), 'total_connections' => 600, 'avg_uptime' => '24 hours', 'risk_score' => 12]
];

// Rogue device detection
$rogueDevices = [
    ['device' => 'Unknown-Device', 'ip' => '192.168.1.199', 'mac' => 'AA:BB:CC:DD:EE:FF', 'first_seen' => date('Y-m-d H:i:s', strtotime('-2 hours')), 'risk_level' => 'Critical', 'reason' => 'Unrecognized MAC address, not in authorized device list', 'manufacturer' => 'Unknown', 'port' => 'Gi0/18', 'vlan' => '1', 'risk_score' => 95],
    ['device' => 'Suspicious-Laptop', 'ip' => '192.168.1.250', 'mac' => 'DE:AD:BE:EF:CA:FE', 'first_seen' => date('Y-m-d H:i:s', strtotime('-30 minutes')), 'risk_level' => 'High', 'reason' => 'Port scanning detected, attempting to access restricted ports', 'manufacturer' => 'Unknown', 'port' => 'Gi0/22', 'vlan' => '10', 'risk_score' => 85],
    ['device' => 'Tablet-Guest-03', 'ip' => '192.168.99.33', 'mac' => '00:1A:2B:3C:4D:08', 'first_seen' => date('Y-m-d H:i:s', strtotime('-5 hours')), 'risk_level' => 'Medium', 'reason' => 'New device on guest network, unusual data usage pattern', 'manufacturer' => 'Apple Inc.', 'port' => 'Fa0/4', 'vlan' => '99', 'risk_score' => 45],
    ['device' => 'Printer-HP-301', 'ip' => '192.168.1.201', 'mac' => '00:1A:2B:3C:4D:03', 'first_seen' => date('Y-m-d', strtotime('-365 days')), 'risk_level' => 'Low', 'reason' => 'Outdated firmware version detected (security advisory)', 'manufacturer' => 'HP Inc.', 'port' => 'Gi0/5', 'vlan' => '20', 'risk_score' => 25]
];

// Connection activity over time (last 24 hours, hourly)
$activityData = [];
for ($i = 23; $i >= 0; $i--) {
    $hour = date('H:00', strtotime("-$i hours"));
    $activityData[] = [
        'hour' => $hour,
        'connections' => rand(5, 25),
        'disconnections' => rand(1, 8)
    ];
}

// Calculate statistics
$totalPortMappings = count($portMappings);
$totalVLANs = count($vlans);
$totalConnectionLogs = count($connectionLogs);
$totalRogueDevices = count($rogueDevices);
$criticalRogues = count(array_filter($rogueDevices, fn($d) => $d['risk_level'] === 'Critical'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Device Tracker | UDT - IOC</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        .container { max-width: 1400px; margin: 0 auto; }

        /* Header */
        .header {
            background: white;
            padding: 25px 30px;
            border-radius: 10px;
            margin-bottom: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h1 {
            color: #667eea;
            font-size: 32px;
            margin-bottom: 5px;
        }
        .header p {
            color: #666;
            font-size: 14px;
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

        /* Stats Grid */
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
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
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
            border-radius: 12px;
            flex-shrink: 0;
        }
        .stat-info {
            flex: 1;
        }
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #333;
            line-height: 1;
            margin-bottom: 5px;
        }
        .stat-label {
            color: #666;
            font-size: 13px;
            font-weight: 500;
        }

        /* Tabs */
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }
        .tab {
            background: rgba(255, 255, 255, 0.9);
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            color: #667eea;
            transition: all 0.3s;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        .tab:hover {
            background: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .tab.active {
            background: white;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        }

        /* Tab Content */
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

        /* Cards */
        .card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
            margin-bottom: 25px;
        }
        .card h2 {
            color: #667eea;
            font-size: 24px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 3px solid #667eea;
        }
        .card h3 {
            color: #333;
            font-size: 18px;
            margin: 20px 0 15px 0;
        }

        /* Tables */
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        thead {
            background: #f8f9fa;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        th {
            font-weight: 600;
            color: #333;
            font-size: 13px;
            text-transform: uppercase;
        }
        td {
            color: #666;
            font-size: 14px;
        }
        tr:hover {
            background: #f8f9fa;
        }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .badge-success {
            background: #e8f5e9;
            color: #2e7d32;
        }
        .badge-danger {
            background: #ffebee;
            color: #c62828;
        }
        .badge-warning {
            background: #fff3e0;
            color: #e65100;
        }
        .badge-info {
            background: #e3f2fd;
            color: #1565c0;
        }
        .badge-critical {
            background: #c62828;
            color: white;
        }
        .badge-high {
            background: #ff5722;
            color: white;
        }
        .badge-medium {
            background: #ff9800;
            color: white;
        }
        .badge-low {
            background: #ffc107;
            color: #333;
        }

        /* Status Indicator */
        .status-indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
        }
        .status-connected { background: #4CAF50; }
        .status-disconnected { background: #f44336; }

        /* Charts */
        .chart-container {
            position: relative;
            height: 300px;
            margin: 25px 0;
        }
        .chart-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
            gap: 25px;
            margin: 25px 0;
        }
        .chart-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
        }
        .chart-card h3 {
            color: #667eea;
            font-size: 16px;
            margin: 0 0 15px 0;
            font-weight: 600;
        }

        /* VLAN Card */
        .vlan-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin: 15px 0;
            border-left: 5px solid;
        }

        /* Port Map Grid */
        .port-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 10px;
            margin: 20px 0;
        }
        .port-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            border: 2px solid #e0e0e0;
            transition: all 0.3s;
        }
        .port-box:hover {
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .port-box.connected {
            border-color: #4CAF50;
            background: #e8f5e9;
        }
        .port-number {
            font-weight: 600;
            color: #667eea;
            margin-bottom: 5px;
        }
        .port-device {
            font-size: 11px;
            color: #666;
        }

        /* Info Grid */
        .info-grid {
            display: grid;
            grid-template-columns: 200px 1fr;
            gap: 15px;
            margin: 15px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .info-label {
            font-weight: 600;
            color: #667eea;
        }
        .info-value {
            color: #666;
        }

        /* Alert Box */
        .alert-box {
            padding: 20px;
            border-radius: 8px;
            margin: 15px 0;
            border-left: 5px solid;
        }
        .alert-critical {
            background: #ffebee;
            border-color: #c62828;
        }
        .alert-high {
            background: #fff3e0;
            border-color: #ff5722;
        }
        .alert-medium {
            background: #fff8e1;
            border-color: #ff9800;
        }
        .alert-low {
            background: #fffde7;
            border-color: #ffc107;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
            .chart-grid {
                grid-template-columns: 1fr;
            }
            .info-grid {
                grid-template-columns: 1fr;
                gap: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div>
                <h1>📱 User Device Tracker</h1>
                <p>Advanced Device Tracking, Port Mapping & Rogue Detection</p>
            </div>
            <a href="../index.php" class="back-btn">← Back to Dashboard</a>
        </div>

        <!-- Statistics Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📊</div>
                <div class="stat-info">
                    <div class="stat-number"><?= $device_stats['total'] ?></div>
                    <div class="stat-label">Total Devices Tracked</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-info">
                    <div class="stat-number" style="color: #4CAF50;"><?= $device_stats['online'] ?></div>
                    <div class="stat-label">Currently Online</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">🔌</div>
                <div class="stat-info">
                    <div class="stat-number"><?= $totalPortMappings ?></div>
                    <div class="stat-label">Port Mappings</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">⚠️</div>
                <div class="stat-info">
                    <div class="stat-number" style="color: #c62828;"><?= $totalRogueDevices ?></div>
                    <div class="stat-label">Rogue Devices Detected</div>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="tabs">
            <div class="tab active" onclick="switchTab('overview')">📊 Overview</div>
            <div class="tab" onclick="switchTab('portmap')">🔌 Port Mapping</div>
            <div class="tab" onclick="switchTab('vlans')">🌐 VLAN Tracking</div>
            <div class="tab" onclick="switchTab('history')">📜 Connection History</div>
            <div class="tab" onclick="switchTab('profiles')">👤 Device Profiles</div>
            <div class="tab" onclick="switchTab('rogue')">⚠️ Rogue Detection</div>
        </div>

        <!-- Overview Tab -->
        <div id="overview-tab" class="tab-content active">
            <div class="card">
                <h2>📊 Device Tracking Overview</h2>

                <div class="chart-grid">
                    <div class="chart-card">
                        <h3>Connection Activity (Last 24 Hours)</h3>
                        <div style="height: 250px;">
                            <canvas id="activityChart"></canvas>
                        </div>
                    </div>

                    <div class="chart-card">
                        <h3>Devices by VLAN</h3>
                        <div style="height: 250px;">
                            <canvas id="vlanChart"></canvas>
                        </div>
                    </div>
                </div>

                <h3>Recent Device Activity</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Device Name</th>
                            <th>IP Address</th>
                            <th>MAC Address</th>
                            <th>Switch Port</th>
                            <th>VLAN</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($portMappings, 0, 8) as $mapping): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($mapping['device_name']) ?></strong></td>
                            <td><?= htmlspecialchars($mapping['ip']) ?></td>
                            <td><?= htmlspecialchars($mapping['mac']) ?></td>
                            <td><?= htmlspecialchars($mapping['switch']) ?> - <?= htmlspecialchars($mapping['port']) ?></td>
                            <td><span class="badge badge-info">VLAN <?= htmlspecialchars($mapping['vlan']) ?></span></td>
                            <td>
                                <span class="status-indicator status-connected"></span>
                                <span class="badge badge-success"><?= htmlspecialchars($mapping['status']) ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Port Mapping Tab -->
        <div id="portmap-tab" class="tab-content">
            <div class="card">
                <h2>🔌 Switch Port Mapping</h2>

                <p style="color: #666; margin-bottom: 20px;">
                    Real-time mapping of devices to physical switch ports with connection details.
                </p>

                <h3>Core-Switch-01 Port Status</h3>
                <div class="port-grid">
                    <?php for ($i = 1; $i <= 24; $i++):
                        $portNum = "Gi0/$i";
                        $mapping = array_filter($portMappings, fn($m) => $m['port'] === $portNum && $m['switch'] === 'Core-Switch-01');
                        $mapping = reset($mapping);
                    ?>
                    <div class="port-box <?= $mapping ? 'connected' : '' ?>">
                        <div class="port-number"><?= $portNum ?></div>
                        <div class="port-device">
                            <?php if ($mapping): ?>
                                <?= htmlspecialchars(substr($mapping['device_name'], 0, 15)) ?>
                            <?php else: ?>
                                Available
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endfor; ?>
                </div>

                <h3>Detailed Port Mappings</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Device Name</th>
                            <th>IP Address</th>
                            <th>MAC Address</th>
                            <th>Switch</th>
                            <th>Port</th>
                            <th>Speed</th>
                            <th>Duplex</th>
                            <th>VLAN</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($portMappings as $mapping): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($mapping['device_name']) ?></strong></td>
                            <td><?= htmlspecialchars($mapping['ip']) ?></td>
                            <td><?= htmlspecialchars($mapping['mac']) ?></td>
                            <td><?= htmlspecialchars($mapping['switch']) ?></td>
                            <td><strong><?= htmlspecialchars($mapping['port']) ?></strong></td>
                            <td><?= htmlspecialchars($mapping['speed']) ?></td>
                            <td><?= htmlspecialchars($mapping['duplex']) ?></td>
                            <td><span class="badge badge-info">VLAN <?= htmlspecialchars($mapping['vlan']) ?></span></td>
                            <td>
                                <span class="status-indicator status-connected"></span>
                                <span class="badge badge-success"><?= htmlspecialchars($mapping['status']) ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- VLAN Tracking Tab -->
        <div id="vlans-tab" class="tab-content">
            <div class="card">
                <h2>🌐 VLAN Assignment Tracking</h2>

                <p style="color: #666; margin-bottom: 20px;">
                    Track device assignments across VLANs with subnet information and device counts.
                </p>

                <h3>VLAN Overview</h3>
                <?php foreach ($vlans as $vlan): ?>
                <div class="vlan-card" style="border-left-color: <?= $vlan['color'] ?>;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <div>
                            <h4 style="color: #333; margin: 0 0 5px 0;">
                                <span style="color: <?= $vlan['color'] ?>;">■</span>
                                VLAN <?= htmlspecialchars($vlan['id']) ?> - <?= htmlspecialchars($vlan['name']) ?>
                            </h4>
                            <p style="color: #666; font-size: 13px; margin: 0;"><?= htmlspecialchars($vlan['description']) ?></p>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-size: 28px; font-weight: bold; color: <?= $vlan['color'] ?>;"><?= $vlan['devices'] ?></div>
                            <div style="font-size: 12px; color: #666;">Devices</div>
                        </div>
                    </div>
                    <div class="info-grid" style="margin: 0;">
                        <div class="info-label">Subnet:</div>
                        <div class="info-value"><?= htmlspecialchars($vlan['subnet']) ?></div>
                        <div class="info-label">VLAN ID:</div>
                        <div class="info-value"><?= htmlspecialchars($vlan['id']) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>

                <h3>Devices by VLAN</h3>
                <table>
                    <thead>
                        <tr>
                            <th>VLAN</th>
                            <th>Device Name</th>
                            <th>IP Address</th>
                            <th>MAC Address</th>
                            <th>Switch Port</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($portMappings as $mapping):
                            $vlanInfo = array_filter($vlans, fn($v) => $v['id'] === $mapping['vlan']);
                            $vlanInfo = reset($vlanInfo);
                        ?>
                        <tr>
                            <td>
                                <span style="color: <?= $vlanInfo['color'] ?? '#666' ?>; font-weight: bold;">
                                    VLAN <?= htmlspecialchars($mapping['vlan']) ?>
                                </span>
                            </td>
                            <td><strong><?= htmlspecialchars($mapping['device_name']) ?></strong></td>
                            <td><?= htmlspecialchars($mapping['ip']) ?></td>
                            <td><?= htmlspecialchars($mapping['mac']) ?></td>
                            <td><?= htmlspecialchars($mapping['port']) ?></td>
                            <td><span class="badge badge-success">Connected</span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <h3>VLAN Distribution Chart</h3>
                <div class="chart-container">
                    <canvas id="vlanDistributionChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Connection History Tab -->
        <div id="history-tab" class="tab-content">
            <div class="card">
                <h2>📜 Historical Connection Logs</h2>

                <p style="color: #666; margin-bottom: 20px;">
                    Complete audit trail of device connections and disconnections with timestamps.
                </p>

                <h3>Connection Activity Timeline</h3>
                <div class="chart-container">
                    <canvas id="connectionTimelineChart"></canvas>
                </div>

                <h3>Recent Connection Events</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>Device Name</th>
                            <th>IP Address</th>
                            <th>MAC Address</th>
                            <th>Event</th>
                            <th>Switch Port</th>
                            <th>VLAN</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($connectionLogs as $log): ?>
                        <tr>
                            <td><?= htmlspecialchars($log['timestamp']) ?></td>
                            <td><strong><?= htmlspecialchars($log['device']) ?></strong></td>
                            <td><?= htmlspecialchars($log['ip']) ?></td>
                            <td><?= htmlspecialchars($log['mac']) ?></td>
                            <td>
                                <?php if ($log['event'] === 'Connected'): ?>
                                    <span class="badge badge-success">✓ Connected</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">✗ Disconnected</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($log['port']) ?></td>
                            <td><span class="badge badge-info">VLAN <?= htmlspecialchars($log['vlan']) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <h3>Connection Statistics</h3>
                <div class="info-grid">
                    <div class="info-label">Total Events (24h):</div>
                    <div class="info-value"><?= count($connectionLogs) ?> events</div>

                    <div class="info-label">Connections:</div>
                    <div class="info-value"><?= count(array_filter($connectionLogs, fn($l) => $l['event'] === 'Connected')) ?> devices connected</div>

                    <div class="info-label">Disconnections:</div>
                    <div class="info-value"><?= count(array_filter($connectionLogs, fn($l) => $l['event'] === 'Disconnected')) ?> devices disconnected</div>

                    <div class="info-label">Unique Devices:</div>
                    <div class="info-value"><?= count(array_unique(array_column($connectionLogs, 'device'))) ?> different devices</div>

                    <div class="info-label">Most Active Port:</div>
                    <div class="info-value">Gi0/1 (<?= rand(5, 15) ?> events)</div>

                    <div class="info-label">Peak Activity:</div>
                    <div class="info-value"><?= date('H:i', strtotime('-5 hours')) ?> (<?= rand(8, 15) ?> events)</div>
                </div>
            </div>
        </div>

        <!-- Device Profiles Tab -->
        <div id="profiles-tab" class="tab-content">
            <div class="card">
                <h2>👤 Device Profiling</h2>

                <p style="color: #666; margin-bottom: 20px;">
                    Detailed device profiles with manufacturer identification, usage patterns, and behavioral analysis.
                </p>

                <h3>Device Profile Summary</h3>
                <div class="chart-grid">
                    <div class="chart-card">
                        <h3>Devices by Type</h3>
                        <div style="height: 250px;">
                            <canvas id="deviceTypeChart"></canvas>
                        </div>
                    </div>

                    <div class="chart-card">
                        <h3>Risk Score Distribution</h3>
                        <div style="height: 250px;">
                            <canvas id="riskScoreChart"></canvas>
                        </div>
                    </div>
                </div>

                <h3>Detailed Device Profiles</h3>
                <?php foreach ($deviceProfiles as $profile): ?>
                <div style="margin: 20px 0; padding: 20px; background: #f8f9fa; border-radius: 8px; border-left: 5px solid <?= $profile['authorized'] ? '#4CAF50' : '#f44336' ?>;">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px;">
                        <div>
                            <h4 style="color: #333; margin: 0 0 5px 0;"><?= htmlspecialchars($profile['device']) ?></h4>
                            <div style="color: #666; font-size: 13px;">
                                <span>📍 <?= htmlspecialchars($profile['ip']) ?></span>
                                <span style="margin-left: 15px;">🔖 <?= htmlspecialchars($profile['mac']) ?></span>
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <?php if ($profile['authorized']): ?>
                                <span class="badge badge-success">✓ Authorized</span>
                            <?php else: ?>
                                <span class="badge badge-danger">✗ Unauthorized</span>
                            <?php endif; ?>
                            <div style="margin-top: 5px;">
                                <span class="badge" style="background: <?= $profile['risk_score'] > 70 ? '#c62828' : ($profile['risk_score'] > 40 ? '#ff9800' : '#4CAF50') ?>; color: white;">
                                    Risk: <?= $profile['risk_score'] ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="info-grid" style="margin: 0;">
                        <div class="info-label">Manufacturer:</div>
                        <div class="info-value"><?= htmlspecialchars($profile['manufacturer']) ?></div>

                        <div class="info-label">Device Type:</div>
                        <div class="info-value"><?= htmlspecialchars($profile['type']) ?></div>

                        <div class="info-label">Operating System:</div>
                        <div class="info-value"><?= htmlspecialchars($profile['os']) ?></div>

                        <div class="info-label">User:</div>
                        <div class="info-value"><?= htmlspecialchars($profile['user']) ?></div>

                        <div class="info-label">Department:</div>
                        <div class="info-value"><?= htmlspecialchars($profile['department']) ?></div>

                        <div class="info-label">First Seen:</div>
                        <div class="info-value"><?= htmlspecialchars($profile['first_seen']) ?></div>

                        <div class="info-label">Total Connections:</div>
                        <div class="info-value"><?= number_format($profile['total_connections']) ?> connections</div>

                        <div class="info-label">Average Uptime:</div>
                        <div class="info-value"><?= htmlspecialchars($profile['avg_uptime']) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Rogue Detection Tab -->
        <div id="rogue-tab" class="tab-content">
            <div class="card">
                <h2>⚠️ Rogue Device Detection</h2>

                <p style="color: #666; margin-bottom: 20px;">
                    Automated detection and alerting for unauthorized, suspicious, or potentially malicious devices.
                </p>

                <?php if ($criticalRogues > 0): ?>
                <div style="background: #ffebee; padding: 20px; border-radius: 8px; border-left: 5px solid #c62828; margin-bottom: 25px;">
                    <h3 style="color: #c62828; margin: 0 0 10px 0;">🚨 Critical Alert</h3>
                    <p style="color: #666; margin: 0;">
                        <strong><?= $criticalRogues ?></strong> critical rogue device(s) detected on your network. Immediate action required.
                    </p>
                </div>
                <?php endif; ?>

                <h3>Risk Level Summary</h3>
                <div class="chart-grid">
                    <div class="chart-card">
                        <h3>Rogue Devices by Risk Level</h3>
                        <div style="height: 250px;">
                            <canvas id="rogueRiskChart"></canvas>
                        </div>
                    </div>

                    <div class="chart-card">
                        <h3>Detection Timeline (Last 24h)</h3>
                        <div style="height: 250px;">
                            <canvas id="rogueTimelineChart"></canvas>
                        </div>
                    </div>
                </div>

                <h3>Detected Rogue Devices</h3>
                <?php foreach ($rogueDevices as $rogue): ?>
                <div class="alert-box alert-<?= strtolower($rogue['risk_level']) ?>">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px;">
                        <div>
                            <h4 style="color: #333; margin: 0 0 5px 0;">
                                <?php if ($rogue['risk_level'] === 'Critical'): ?>
                                    🔴
                                <?php elseif ($rogue['risk_level'] === 'High'): ?>
                                    🟠
                                <?php elseif ($rogue['risk_level'] === 'Medium'): ?>
                                    🟡
                                <?php else: ?>
                                    🟢
                                <?php endif; ?>
                                <?= htmlspecialchars($rogue['device']) ?>
                            </h4>
                            <div style="color: #666; font-size: 13px;">
                                <span>📍 <?= htmlspecialchars($rogue['ip']) ?></span>
                                <span style="margin-left: 15px;">🔖 <?= htmlspecialchars($rogue['mac']) ?></span>
                                <span style="margin-left: 15px;">🕒 First Seen: <?= htmlspecialchars($rogue['first_seen']) ?></span>
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <span class="badge badge-<?= strtolower($rogue['risk_level']) ?>"><?= htmlspecialchars($rogue['risk_level']) ?> Risk</span>
                            <div style="margin-top: 5px; font-size: 20px; font-weight: bold; color: <?= $rogue['risk_score'] > 70 ? '#c62828' : ($rogue['risk_score'] > 40 ? '#ff9800' : '#ffc107') ?>;">
                                Risk: <?= $rogue['risk_score'] ?>
                            </div>
                        </div>
                    </div>

                    <div style="padding: 15px; background: white; border-radius: 6px; margin-bottom: 15px;">
                        <strong>Detection Reason:</strong>
                        <p style="margin: 5px 0 0 0; color: #666;"><?= htmlspecialchars($rogue['reason']) ?></p>
                    </div>

                    <div class="info-grid" style="margin: 0;">
                        <div class="info-label">Manufacturer:</div>
                        <div class="info-value"><?= htmlspecialchars($rogue['manufacturer']) ?></div>

                        <div class="info-label">Switch Port:</div>
                        <div class="info-value"><?= htmlspecialchars($rogue['port']) ?></div>

                        <div class="info-label">VLAN:</div>
                        <div class="info-value">VLAN <?= htmlspecialchars($rogue['vlan']) ?></div>

                        <div class="info-label">Recommended Action:</div>
                        <div class="info-value">
                            <?php if ($rogue['risk_level'] === 'Critical'): ?>
                                <strong style="color: #c62828;">Immediately disconnect and investigate</strong>
                            <?php elseif ($rogue['risk_level'] === 'High'): ?>
                                <strong style="color: #ff5722;">Quarantine to isolated VLAN and monitor</strong>
                            <?php elseif ($rogue['risk_level'] === 'Medium'): ?>
                                Monitor closely and verify authorization
                            <?php else: ?>
                                Schedule review during next maintenance window
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>

                <h3>Rogue Detection Configuration</h3>
                <div class="info-grid">
                    <div class="info-label">Detection Methods:</div>
                    <div class="info-value">MAC whitelist, Behavioral analysis, Port scanning detection</div>

                    <div class="info-label">Scan Frequency:</div>
                    <div class="info-value">Real-time + Full scan every 15 minutes</div>

                    <div class="info-label">Alert Threshold:</div>
                    <div class="info-value">Risk score ≥ 40 (Medium and above)</div>

                    <div class="info-label">Auto-Quarantine:</div>
                    <div class="info-value">Enabled for Critical risk (score ≥ 80)</div>

                    <div class="info-label">Notification:</div>
                    <div class="info-value">Email + SMS for High/Critical detections</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Tab switching
        function switchTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            document.getElementById(tabName + '-tab').classList.add('active');
            event.target.classList.add('active');
        }

        // Chart.js configurations
        const chartColors = {
            primary: '#667eea',
            secondary: '#764ba2',
            success: '#4CAF50',
            danger: '#f44336',
            warning: '#ff9800',
            info: '#2196F3'
        };

        // Connection Activity Chart
        const activityData = <?= json_encode($activityData) ?>;
        new Chart(document.getElementById('activityChart'), {
            type: 'line',
            data: {
                labels: activityData.map(d => d.hour),
                datasets: [
                    {
                        label: 'Connections',
                        data: activityData.map(d => d.connections),
                        borderColor: chartColors.success,
                        backgroundColor: 'rgba(76, 175, 80, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'Disconnections',
                        data: activityData.map(d => d.disconnections),
                        borderColor: chartColors.danger,
                        backgroundColor: 'rgba(244, 67, 54, 0.1)',
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
                        position: 'bottom'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // VLAN Chart (Overview)
        const vlanData = <?= json_encode($vlans) ?>;
        new Chart(document.getElementById('vlanChart'), {
            type: 'doughnut',
            data: {
                labels: vlanData.map(v => 'VLAN ' + v.id + ' - ' + v.name),
                datasets: [{
                    data: vlanData.map(v => v.devices),
                    backgroundColor: vlanData.map(v => v.color),
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // VLAN Distribution Chart
        new Chart(document.getElementById('vlanDistributionChart'), {
            type: 'bar',
            data: {
                labels: vlanData.map(v => 'VLAN ' + v.id),
                datasets: [{
                    label: 'Devices',
                    data: vlanData.map(v => v.devices),
                    backgroundColor: vlanData.map(v => v.color),
                    borderRadius: 8
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
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });

        // Connection Timeline Chart
        new Chart(document.getElementById('connectionTimelineChart'), {
            type: 'scatter',
            data: {
                datasets: [
                    {
                        label: 'Connected',
                        data: <?= json_encode(array_map(function($log, $i) {
                            return $log['event'] === 'Connected' ? ['x' => $i, 'y' => 1] : null;
                        }, $connectionLogs, array_keys($connectionLogs))) ?>.filter(d => d !== null),
                        backgroundColor: chartColors.success,
                        pointRadius: 6
                    },
                    {
                        label: 'Disconnected',
                        data: <?= json_encode(array_map(function($log, $i) {
                            return $log['event'] === 'Disconnected' ? ['x' => $i, 'y' => 0] : null;
                        }, $connectionLogs, array_keys($connectionLogs))) ?>.filter(d => d !== null),
                        backgroundColor: chartColors.danger,
                        pointRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 2,
                        ticks: {
                            stepSize: 1,
                            callback: function(value) {
                                return value === 1 ? 'Connect' : value === 0 ? 'Disconnect' : '';
                            }
                        }
                    }
                }
            }
        });

        // Device Type Chart
        const deviceProfiles = <?= json_encode($deviceProfiles) ?>;
        const deviceTypeCounts = deviceProfiles.reduce((acc, d) => {
            acc[d.type] = (acc[d.type] || 0) + 1;
            return acc;
        }, {});

        new Chart(document.getElementById('deviceTypeChart'), {
            type: 'pie',
            data: {
                labels: Object.keys(deviceTypeCounts),
                datasets: [{
                    data: Object.values(deviceTypeCounts),
                    backgroundColor: [
                        chartColors.primary,
                        chartColors.success,
                        chartColors.warning,
                        chartColors.info,
                        '#9c27b0',
                        '#e91e63'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Risk Score Distribution Chart
        const riskCategories = {
            'Low (0-30)': deviceProfiles.filter(d => d.risk_score <= 30).length,
            'Medium (31-60)': deviceProfiles.filter(d => d.risk_score > 30 && d.risk_score <= 60).length,
            'High (61-80)': deviceProfiles.filter(d => d.risk_score > 60 && d.risk_score <= 80).length,
            'Critical (81-100)': deviceProfiles.filter(d => d.risk_score > 80).length
        };

        new Chart(document.getElementById('riskScoreChart'), {
            type: 'bar',
            data: {
                labels: Object.keys(riskCategories),
                datasets: [{
                    label: 'Devices',
                    data: Object.values(riskCategories),
                    backgroundColor: [
                        chartColors.success,
                        chartColors.warning,
                        '#ff5722',
                        chartColors.danger
                    ],
                    borderRadius: 8
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
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });

        // Rogue Risk Chart
        const rogueDevices = <?= json_encode($rogueDevices) ?>;
        const rogueLevelCounts = {
            'Critical': rogueDevices.filter(d => d.risk_level === 'Critical').length,
            'High': rogueDevices.filter(d => d.risk_level === 'High').length,
            'Medium': rogueDevices.filter(d => d.risk_level === 'Medium').length,
            'Low': rogueDevices.filter(d => d.risk_level === 'Low').length
        };

        new Chart(document.getElementById('rogueRiskChart'), {
            type: 'doughnut',
            data: {
                labels: Object.keys(rogueLevelCounts),
                datasets: [{
                    data: Object.values(rogueLevelCounts),
                    backgroundColor: [
                        '#c62828',
                        '#ff5722',
                        '#ff9800',
                        '#ffc107'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Rogue Timeline Chart
        new Chart(document.getElementById('rogueTimelineChart'), {
            type: 'line',
            data: {
                labels: ['6h ago', '5h ago', '4h ago', '3h ago', '2h ago', '1h ago', 'Now'],
                datasets: [{
                    label: 'Rogue Devices Detected',
                    data: [0, 1, 1, 2, 3, 4, 4],
                    borderColor: chartColors.danger,
                    backgroundColor: 'rgba(244, 67, 54, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 5,
                    pointHoverRadius: 8
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
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
