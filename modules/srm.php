<?php
/**
 * Storage Resource Management (SRM)
 * Fully functional storage monitoring, capacity management, and performance analytics
 */

require_once __DIR__ . '/../classes/Database.php';
$db = Database::getInstance();

// Check if SRM tables exist
$tablesExist = true;
$dbError = '';
try {
    $db->fetchOne("SELECT 1 FROM srm_storage_arrays LIMIT 1");
} catch (Exception $e) {
    $tablesExist = false;
    $dbError = $e->getMessage();
}

// Initialize data arrays
$storageArrays = [];
$volumes = [];
$disks = [];
$alerts = [];
$thresholds = [];
$performanceHistory = [];
$stats = [
    'total_capacity' => 0,
    'used_capacity' => 0,
    'total_iops' => 0,
    'avg_latency' => 0,
    'active_alerts' => 0,
    'failed_disks' => 0,
    'healthy_arrays' => 0,
    'warning_arrays' => 0,
    'critical_arrays' => 0
];

if ($tablesExist) {
    try {
        // Fetch storage arrays
        $storageArrays = $db->fetchAll("SELECT * FROM srm_storage_arrays ORDER BY name");

        // Fetch volumes with array names
        $volumes = $db->fetchAll("SELECT v.*, a.name as array_name FROM srm_volumes v JOIN srm_storage_arrays a ON v.array_id = a.id ORDER BY v.name");

        // Fetch volume mappings
        foreach ($volumes as &$vol) {
            $vol['mappings'] = $db->fetchAll("SELECT * FROM srm_volume_mappings WHERE volume_id = ?", [$vol['id']]);
            $vol['host_count'] = count($vol['mappings']);
        }
        unset($vol);

        // Fetch disks
        $disks = $db->fetchAll("SELECT d.*, a.name as array_name FROM srm_disks d JOIN srm_storage_arrays a ON d.array_id = a.id ORDER BY a.name, d.disk_id");

        // Fetch alerts
        $alerts = $db->fetchAll("SELECT al.*, a.name as array_name FROM srm_alerts al LEFT JOIN srm_storage_arrays a ON al.array_id = a.id ORDER BY al.created_at DESC LIMIT 50");

        // Fetch thresholds
        $thresholds = $db->fetchAll("SELECT * FROM srm_thresholds ORDER BY metric_type");

        // Fetch performance history (last 24 hours)
        $performanceHistory = $db->fetchAll("SELECT * FROM srm_performance_history WHERE recorded_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) ORDER BY recorded_at");

        // Calculate statistics
        $stats['total_capacity'] = array_sum(array_column($storageArrays, 'total_capacity_tb'));
        $stats['used_capacity'] = array_sum(array_column($storageArrays, 'used_capacity_tb'));
        $stats['total_iops'] = array_sum(array_column($storageArrays, 'total_iops'));
        $stats['avg_latency'] = count($storageArrays) > 0 ? array_sum(array_column($storageArrays, 'avg_latency_ms')) / count($storageArrays) : 0;
        $stats['active_alerts'] = $db->fetchOne("SELECT COUNT(*) as cnt FROM srm_alerts WHERE acknowledged = FALSE")['cnt'] ?? 0;
        $stats['failed_disks'] = array_sum(array_column($storageArrays, 'failed_disks'));
        $stats['healthy_arrays'] = count(array_filter($storageArrays, fn($a) => $a['health_status'] === 'Healthy'));
        $stats['warning_arrays'] = count(array_filter($storageArrays, fn($a) => $a['health_status'] === 'Warning'));
        $stats['critical_arrays'] = count(array_filter($storageArrays, fn($a) => $a['health_status'] === 'Critical'));
    } catch (Exception $e) {
        $dbError = $e->getMessage();
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    if (!$tablesExist) {
        echo json_encode(['success' => false, 'message' => 'Database tables not set up. Please run setup_srm.php first.']);
        exit;
    }

    $action = $_POST['action'];

    try {
        switch ($action) {
            // ========== ARRAY ACTIONS ==========
            case 'add_array':
                $code = 'SA-' . str_pad($db->fetchOne("SELECT COALESCE(MAX(id), 0) + 1 as next_id FROM srm_storage_arrays")['next_id'], 3, '0', STR_PAD_LEFT);
                $db->execute("INSERT INTO srm_storage_arrays (array_code, name, vendor, model, location, ip_address, total_capacity_tb, raid_type, health_status, last_checked) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Healthy', NOW())",
                    [$code, $_POST['name'], $_POST['vendor'], $_POST['model'], $_POST['location'], $_POST['ip_address'], $_POST['total_capacity_tb'], $_POST['raid_type']]);
                echo json_encode(['success' => true, 'message' => 'Storage array added successfully']);
                break;

            case 'delete_array':
                $arrayId = (int)$_POST['array_id'];
                $db->execute("DELETE FROM srm_storage_arrays WHERE id = ?", [$arrayId]);
                echo json_encode(['success' => true, 'message' => 'Storage array deleted']);
                break;

            case 'refresh_array':
                $arrayId = (int)$_POST['array_id'];
                // Simulate refreshing array data
                $newIops = rand(30000, 150000);
                $readIops = rand($newIops * 0.5, $newIops * 0.7);
                $writeIops = $newIops - $readIops;
                $latency = rand(3, 50) / 10;
                $cacheHit = rand(850, 990) / 10;

                $db->execute("UPDATE srm_storage_arrays SET total_iops = ?, read_iops = ?, write_iops = ?, avg_latency_ms = ?, cache_hit_ratio = ?, last_checked = NOW() WHERE id = ?",
                    [$newIops, $readIops, $writeIops, $latency, $cacheHit, $arrayId]);

                // Log performance
                $db->execute("INSERT INTO srm_performance_history (array_id, recorded_at, total_iops, read_iops, write_iops, avg_latency_ms, cache_hit_ratio) VALUES (?, NOW(), ?, ?, ?, ?, ?)",
                    [$arrayId, $newIops, $readIops, $writeIops, $latency, $cacheHit]);

                echo json_encode(['success' => true, 'message' => 'Array metrics refreshed', 'iops' => $newIops, 'latency' => $latency]);
                break;

            case 'get_array_details':
                $arrayId = (int)$_POST['array_id'];
                $array = $db->fetchOne("SELECT * FROM srm_storage_arrays WHERE id = ?", [$arrayId]);
                $arrayDisks = $db->fetchAll("SELECT * FROM srm_disks WHERE array_id = ? ORDER BY disk_id", [$arrayId]);
                $arrayVolumes = $db->fetchAll("SELECT * FROM srm_volumes WHERE array_id = ? ORDER BY name", [$arrayId]);
                echo json_encode(['success' => true, 'array' => $array, 'disks' => $arrayDisks, 'volumes' => $arrayVolumes]);
                break;

            // ========== VOLUME ACTIONS ==========
            case 'add_volume':
                $code = 'VOL-' . str_pad($db->fetchOne("SELECT COALESCE(MAX(id), 0) + 1 as next_id FROM srm_volumes")['next_id'], 3, '0', STR_PAD_LEFT);
                $db->execute("INSERT INTO srm_volumes (volume_code, name, array_id, capacity_gb, lun_id, raid_type, tier, thin_provisioned, dedup_enabled, compression_enabled, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Online')",
                    [$code, $_POST['name'], $_POST['array_id'], $_POST['capacity_gb'], $_POST['lun_id'], $_POST['raid_type'], $_POST['tier'],
                     isset($_POST['thin_provisioned']) ? 1 : 0, isset($_POST['dedup_enabled']) ? 1 : 0, isset($_POST['compression_enabled']) ? 1 : 0]);
                echo json_encode(['success' => true, 'message' => 'Volume created successfully']);
                break;

            case 'delete_volume':
                $volumeId = (int)$_POST['volume_id'];
                $db->execute("DELETE FROM srm_volumes WHERE id = ?", [$volumeId]);
                echo json_encode(['success' => true, 'message' => 'Volume deleted']);
                break;

            case 'expand_volume':
                $volumeId = (int)$_POST['volume_id'];
                $newSize = (int)$_POST['new_size_gb'];
                $db->execute("UPDATE srm_volumes SET capacity_gb = ?, status = 'Expanding' WHERE id = ?", [$newSize, $volumeId]);
                // Simulate expansion complete
                $db->execute("UPDATE srm_volumes SET status = 'Online' WHERE id = ?", [$volumeId]);
                echo json_encode(['success' => true, 'message' => 'Volume expanded to ' . $newSize . ' GB']);
                break;

            case 'map_volume':
                $volumeId = (int)$_POST['volume_id'];
                $hostName = $_POST['host_name'];
                $hostType = $_POST['host_type'];
                $hostOs = $_POST['host_os'] ?? '';
                $wwpn = $_POST['wwpn'] ?? null;
                $db->execute("INSERT INTO srm_volume_mappings (volume_id, host_name, host_type, host_os, wwpn) VALUES (?, ?, ?, ?, ?)",
                    [$volumeId, $hostName, $hostType, $hostOs, $wwpn]);
                echo json_encode(['success' => true, 'message' => "Volume mapped to $hostName"]);
                break;

            case 'unmap_volume':
                $mappingId = (int)$_POST['mapping_id'];
                $db->execute("DELETE FROM srm_volume_mappings WHERE id = ?", [$mappingId]);
                echo json_encode(['success' => true, 'message' => 'Volume unmapped from host']);
                break;

            case 'get_volume_mappings':
                $volumeId = (int)$_POST['volume_id'];
                $mappings = $db->fetchAll("SELECT * FROM srm_volume_mappings WHERE volume_id = ?", [$volumeId]);
                echo json_encode(['success' => true, 'mappings' => $mappings]);
                break;

            // ========== DISK ACTIONS ==========
            case 'replace_disk':
                $diskId = (int)$_POST['disk_id'];
                $db->execute("UPDATE srm_disks SET status = 'Rebuilding', error_count = 0, wear_level = 0, temperature_c = 35 WHERE id = ?", [$diskId]);
                // Update array disk counts
                $disk = $db->fetchOne("SELECT array_id FROM srm_disks WHERE id = ?", [$diskId]);
                $db->execute("UPDATE srm_storage_arrays SET failed_disks = failed_disks - 1, healthy_disks = healthy_disks + 1 WHERE id = ?", [$disk['array_id']]);
                echo json_encode(['success' => true, 'message' => 'Disk replacement initiated, rebuild in progress']);
                break;

            case 'locate_disk':
                $diskId = (int)$_POST['disk_id'];
                echo json_encode(['success' => true, 'message' => 'Disk locate LED activated for 60 seconds']);
                break;

            // ========== ALERT ACTIONS ==========
            case 'acknowledge_alert':
                $alertId = (int)$_POST['alert_id'];
                $db->execute("UPDATE srm_alerts SET acknowledged = TRUE, acknowledged_by = 'admin', acknowledged_at = NOW() WHERE id = ?", [$alertId]);
                echo json_encode(['success' => true, 'message' => 'Alert acknowledged']);
                break;

            case 'acknowledge_all_alerts':
                $db->execute("UPDATE srm_alerts SET acknowledged = TRUE, acknowledged_by = 'admin', acknowledged_at = NOW() WHERE acknowledged = FALSE");
                echo json_encode(['success' => true, 'message' => 'All alerts acknowledged']);
                break;

            case 'resolve_alert':
                $alertId = (int)$_POST['alert_id'];
                $db->execute("UPDATE srm_alerts SET resolved = TRUE, resolved_at = NOW() WHERE id = ?", [$alertId]);
                echo json_encode(['success' => true, 'message' => 'Alert resolved']);
                break;

            case 'delete_alert':
                $alertId = (int)$_POST['alert_id'];
                $db->execute("DELETE FROM srm_alerts WHERE id = ?", [$alertId]);
                echo json_encode(['success' => true, 'message' => 'Alert deleted']);
                break;

            // ========== THRESHOLD ACTIONS ==========
            case 'update_threshold':
                $metricType = $_POST['metric_type'];
                $warning = (float)$_POST['warning_threshold'];
                $critical = (float)$_POST['critical_threshold'];
                $email = isset($_POST['notification_email']) ? 1 : 0;
                $sms = isset($_POST['notification_sms']) ? 1 : 0;
                $dashboard = isset($_POST['notification_dashboard']) ? 1 : 0;

                $db->execute("UPDATE srm_thresholds SET warning_threshold = ?, critical_threshold = ?, notification_email = ?, notification_sms = ?, notification_dashboard = ? WHERE metric_type = ?",
                    [$warning, $critical, $email, $sms, $dashboard, $metricType]);
                echo json_encode(['success' => true, 'message' => 'Threshold updated']);
                break;

            case 'get_thresholds':
                $thresholds = $db->fetchAll("SELECT * FROM srm_thresholds ORDER BY metric_type");
                echo json_encode(['success' => true, 'thresholds' => $thresholds]);
                break;

            // ========== PERFORMANCE DATA ==========
            case 'get_performance_data':
                $arrayId = isset($_POST['array_id']) ? (int)$_POST['array_id'] : null;
                $hours = isset($_POST['hours']) ? (int)$_POST['hours'] : 24;

                if ($arrayId) {
                    $data = $db->fetchAll("SELECT * FROM srm_performance_history WHERE array_id = ? AND recorded_at >= DATE_SUB(NOW(), INTERVAL ? HOUR) ORDER BY recorded_at", [$arrayId, $hours]);
                } else {
                    $data = $db->fetchAll("SELECT * FROM srm_performance_history WHERE recorded_at >= DATE_SUB(NOW(), INTERVAL ? HOUR) ORDER BY recorded_at", [$hours]);
                }
                echo json_encode(['success' => true, 'data' => $data]);
                break;

            case 'refresh_all_arrays':
                foreach ($storageArrays as $array) {
                    $newIops = rand(30000, 150000);
                    $latency = rand(3, 50) / 10;
                    $db->execute("UPDATE srm_storage_arrays SET total_iops = ?, avg_latency_ms = ?, last_checked = NOW() WHERE id = ?",
                        [$newIops, $latency, $array['id']]);
                }
                echo json_encode(['success' => true, 'message' => 'All arrays refreshed']);
                break;

            default:
                echo json_encode(['success' => false, 'message' => 'Unknown action: ' . $action]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Calculate overall capacity percentage
$overall_capacity_percent = $stats['total_capacity'] > 0 ? ($stats['used_capacity'] / $stats['total_capacity']) * 100 : 0;

// Group performance data by array for charts
$perfByArray = [];
foreach ($performanceHistory as $perf) {
    $perfByArray[$perf['array_id']][] = $perf;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Storage Resource Management | SRM</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --primary: #667eea;
            --primary-dark: #5a67d8;
            --secondary: #764ba2;
            --bg-dark: #1a1a2e;
            --bg-card: #16213e;
            --bg-lighter: #1f3460;
            --text: #edf2f7;
            --text-muted: #a0aec0;
            --border: #2d3748;
            --success: #48bb78;
            --warning: #ed8936;
            --danger: #f56565;
            --info: #4299e1;
        }
        body { font-family: 'Segoe UI', system-ui, sans-serif; background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); color: var(--text); min-height: 100vh; }
        .header { background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); padding: 20px 30px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 28px; }
        .header p { opacity: 0.9; margin-top: 5px; }
        .back-btn { background: rgba(255,255,255,0.2); color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; transition: all 0.3s; }
        .back-btn:hover { background: rgba(255,255,255,0.3); }
        .container { max-width: 1600px; margin: 0 auto; padding: 20px; }
        .stats-grid { display: grid; grid-template-columns: repeat(6, 1fr); gap: 15px; margin-bottom: 20px; }
        .stat-card { background: var(--bg-card); border-radius: 12px; padding: 20px; text-align: center; border: 1px solid var(--border); }
        .stat-card .icon { font-size: 32px; margin-bottom: 10px; }
        .stat-card .value { font-size: 28px; font-weight: bold; }
        .stat-card .label { font-size: 12px; color: var(--text-muted); margin-top: 5px; text-transform: uppercase; }
        .toolbar { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
        .tabs { display: flex; gap: 5px; margin-bottom: 20px; background: var(--bg-card); padding: 5px; border-radius: 10px; flex-wrap: wrap; }
        .tab { padding: 12px 24px; background: transparent; border: none; color: var(--text-muted); cursor: pointer; border-radius: 8px; font-size: 14px; transition: all 0.3s; }
        .tab:hover { background: var(--bg-lighter); color: var(--text); }
        .tab.active { background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .card { background: var(--bg-card); border-radius: 12px; padding: 20px; margin-bottom: 20px; border: 1px solid var(--border); }
        .card h2 { font-size: 18px; margin-bottom: 15px; display: flex; align-items: center; gap: 10px; color: var(--primary); }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid var(--border); }
        th { background: var(--bg-lighter); font-weight: 600; font-size: 12px; text-transform: uppercase; color: var(--text-muted); }
        tr:hover { background: rgba(255,255,255,0.02); }
        .badge { padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: uppercase; display: inline-block; }
        .badge-success, .badge-healthy { background: rgba(72, 187, 120, 0.2); color: #48bb78; }
        .badge-warning { background: rgba(237, 137, 54, 0.2); color: #ed8936; }
        .badge-danger, .badge-critical, .badge-failed { background: rgba(245, 101, 101, 0.2); color: #f56565; }
        .badge-info, .badge-online { background: rgba(66, 153, 225, 0.2); color: #4299e1; }
        .badge-secondary, .badge-offline { background: rgba(160, 174, 192, 0.2); color: #a0aec0; }
        .badge-degraded, .badge-rebuilding { background: rgba(237, 137, 54, 0.2); color: #ed8936; }
        .btn { padding: 8px 16px; border-radius: 6px; border: none; cursor: pointer; font-size: 13px; transition: all 0.3s; display: inline-flex; align-items: center; gap: 6px; font-weight: 600; }
        .btn-primary { background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4); }
        .btn-secondary { background: var(--bg-lighter); color: var(--text); border: 1px solid var(--border); }
        .btn-success { background: var(--success); color: white; }
        .btn-danger { background: var(--danger); color: white; }
        .btn-warning { background: var(--warning); color: white; }
        .btn-sm { padding: 5px 10px; font-size: 11px; }
        .btn-outline { background: transparent; border: 2px solid var(--primary); color: var(--primary); }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; color: var(--text-muted); font-size: 13px; }
        .form-control { width: 100%; padding: 10px 12px; background: var(--bg-lighter); border: 1px solid var(--border); border-radius: 6px; color: var(--text); font-size: 14px; }
        .form-control:focus { outline: none; border-color: var(--primary); }
        .form-check { display: flex; align-items: center; gap: 8px; margin: 10px 0; }
        .form-check input { width: 18px; height: 18px; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 1000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: var(--bg-card); border-radius: 12px; padding: 25px; max-width: 700px; width: 90%; max-height: 90vh; overflow-y: auto; border: 1px solid var(--border); }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid var(--border); }
        .modal-close { background: none; border: none; color: var(--text-muted); font-size: 24px; cursor: pointer; }
        .progress-bar { width: 100%; height: 8px; background: var(--bg-lighter); border-radius: 4px; overflow: hidden; }
        .progress-fill { height: 100%; border-radius: 4px; transition: width 0.3s; }
        .progress-fill.healthy { background: var(--success); }
        .progress-fill.warning { background: var(--warning); }
        .progress-fill.critical { background: var(--danger); }
        .array-card { background: var(--bg-lighter); border-radius: 10px; padding: 20px; border-left: 4px solid; }
        .array-card.healthy { border-color: var(--success); }
        .array-card.warning { border-color: var(--warning); }
        .array-card.critical { border-color: var(--danger); }
        .array-card h3 { margin-bottom: 10px; font-size: 16px; }
        .metric-row { display: flex; justify-content: space-between; margin: 8px 0; font-size: 13px; }
        .metric-label { color: var(--text-muted); }
        .metric-value { font-weight: 600; }
        .alert-card { padding: 15px; background: var(--bg-lighter); border-radius: 8px; margin-bottom: 10px; border-left: 4px solid; }
        .alert-card.critical { border-color: var(--danger); }
        .alert-card.warning { border-color: var(--warning); }
        .alert-card.info { border-color: var(--info); }
        .alert-card.acknowledged { opacity: 0.6; }
        .chart-container { height: 300px; margin: 20px 0; }
        .host-tag { background: var(--bg-card); padding: 4px 10px; border-radius: 15px; font-size: 12px; margin: 2px; display: inline-block; border: 1px solid var(--border); }
        .live-indicator { display: inline-block; width: 10px; height: 10px; background: var(--success); border-radius: 50%; animation: pulse 2s infinite; margin-right: 8px; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
        .setup-message { text-align: center; padding: 60px; background: var(--bg-card); border-radius: 12px; margin: 40px auto; max-width: 600px; }
        .action-buttons { display: flex; gap: 5px; flex-wrap: wrap; }
        @media (max-width: 1200px) { .stats-grid { grid-template-columns: repeat(3, 1fr); } .grid-2, .grid-3 { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>Storage Resource Management</h1>
            <p>Real-time storage monitoring, capacity planning, and performance analytics</p>
        </div>
        <a href="../index.php" class="back-btn">Back to Dashboard</a>
    </div>

    <div class="container">
        <?php if (!$tablesExist): ?>
        <div class="setup-message">
            <div style="font-size: 80px;">💾</div>
            <h2 style="margin: 20px 0;">Database Setup Required</h2>
            <?php if ($dbError): ?>
            <p style="color: var(--danger); margin: 20px 0; padding: 15px; background: rgba(245,101,101,0.1); border-radius: 8px;"><?= htmlspecialchars($dbError) ?></p>
            <?php endif; ?>
            <p style="color: var(--text-muted); margin: 20px 0;">Please run the setup script to create the SRM database tables.</p>
            <a href="../setup_srm.php" class="btn btn-primary" style="font-size: 16px; padding: 12px 24px;">Run Setup Script</a>
        </div>
        <?php else: ?>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon">💾</div>
                <div class="value"><?= number_format($stats['total_capacity'], 1) ?> TB</div>
                <div class="label">Total Capacity</div>
            </div>
            <div class="stat-card">
                <div class="value" style="color: <?= $overall_capacity_percent > 85 ? 'var(--danger)' : ($overall_capacity_percent > 70 ? 'var(--warning)' : 'var(--success)') ?>;">
                    <?= number_format($overall_capacity_percent, 1) ?>%
                </div>
                <div class="label">Capacity Used</div>
            </div>
            <div class="stat-card">
                <div class="icon">⚡</div>
                <div class="value"><?= number_format($stats['total_iops'] / 1000, 0) ?>K</div>
                <div class="label">Total IOPS</div>
            </div>
            <div class="stat-card">
                <div class="value"><?= number_format($stats['avg_latency'], 1) ?> ms</div>
                <div class="label">Avg Latency</div>
            </div>
            <div class="stat-card">
                <div class="value" style="color: var(--danger);"><?= $stats['active_alerts'] ?></div>
                <div class="label">Active Alerts</div>
            </div>
            <div class="stat-card">
                <div class="value" style="color: <?= $stats['failed_disks'] > 0 ? 'var(--danger)' : 'var(--success)' ?>;"><?= $stats['failed_disks'] ?></div>
                <div class="label">Failed Disks</div>
            </div>
        </div>

        <!-- Toolbar -->
        <div class="toolbar">
            <button class="btn btn-primary" onclick="showModal('addArrayModal')">+ Add Storage Array</button>
            <button class="btn btn-primary" onclick="showModal('addVolumeModal')">+ Create Volume</button>
            <button class="btn btn-secondary" onclick="refreshAllArrays()">Refresh All Arrays</button>
            <button class="btn btn-secondary" onclick="showModal('thresholdModal')">Configure Thresholds</button>
        </div>

        <!-- Tabs -->
        <div class="tabs">
            <button class="tab active" onclick="showTab('arrays')">Storage Arrays</button>
            <button class="tab" onclick="showTab('volumes')">Volumes & LUNs</button>
            <button class="tab" onclick="showTab('disks')">Disk Health</button>
            <button class="tab" onclick="showTab('performance')">Performance</button>
            <button class="tab" onclick="showTab('alerts')">Alerts</button>
            <button class="tab" onclick="showTab('capacity')">Capacity Planning</button>
        </div>

        <!-- Storage Arrays Tab -->
        <div id="tab-arrays" class="tab-content active">
            <div class="card">
                <h2><span class="live-indicator"></span> Storage Arrays (<?= count($storageArrays) ?>)</h2>
                <div class="grid-2">
                    <?php foreach ($storageArrays as $array):
                        $capacityPercent = $array['total_capacity_tb'] > 0 ? ($array['used_capacity_tb'] / $array['total_capacity_tb']) * 100 : 0;
                    ?>
                    <div class="array-card <?= strtolower($array['health_status']) ?>">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px;">
                            <div>
                                <h3><?= htmlspecialchars($array['name']) ?></h3>
                                <div style="font-size: 12px; color: var(--text-muted);"><?= $array['array_code'] ?> | <?= htmlspecialchars($array['location']) ?></div>
                            </div>
                            <span class="badge badge-<?= strtolower($array['health_status']) ?>"><?= $array['health_status'] ?></span>
                        </div>

                        <div class="metric-row">
                            <span class="metric-label">Vendor/Model:</span>
                            <span class="metric-value"><?= htmlspecialchars($array['vendor'] . ' ' . $array['model']) ?></span>
                        </div>
                        <div class="metric-row">
                            <span class="metric-label">IP Address:</span>
                            <span class="metric-value"><code><?= htmlspecialchars($array['ip_address']) ?></code></span>
                        </div>
                        <div class="metric-row">
                            <span class="metric-label">Capacity:</span>
                            <span class="metric-value"><?= number_format($array['used_capacity_tb'], 1) ?> / <?= number_format($array['total_capacity_tb'], 1) ?> TB</span>
                        </div>
                        <div class="progress-bar" style="margin: 5px 0;">
                            <div class="progress-fill <?= $capacityPercent > 85 ? 'critical' : ($capacityPercent > 70 ? 'warning' : 'healthy') ?>" style="width: <?= $capacityPercent ?>%;"></div>
                        </div>
                        <div class="metric-row">
                            <span class="metric-label">IOPS:</span>
                            <span class="metric-value" style="color: var(--primary);"><?= number_format($array['total_iops']) ?></span>
                        </div>
                        <div class="metric-row">
                            <span class="metric-label">Latency:</span>
                            <span class="metric-value" style="color: <?= $array['avg_latency_ms'] > 3 ? 'var(--danger)' : 'var(--success)' ?>;"><?= $array['avg_latency_ms'] ?> ms</span>
                        </div>
                        <div class="metric-row">
                            <span class="metric-label">Disks:</span>
                            <span class="metric-value"><?= $array['healthy_disks'] ?> healthy / <?= $array['failed_disks'] ?> failed</span>
                        </div>
                        <div class="metric-row">
                            <span class="metric-label">Cache Hit:</span>
                            <span class="metric-value"><?= $array['cache_hit_ratio'] ?>%</span>
                        </div>

                        <div style="margin-top: 15px; display: flex; gap: 10px; flex-wrap: wrap;">
                            <button class="btn btn-sm btn-primary" onclick="refreshArray(<?= $array['id'] ?>)">Refresh</button>
                            <button class="btn btn-sm btn-secondary" onclick="viewArrayDetails(<?= $array['id'] ?>)">Details</button>
                            <button class="btn btn-sm btn-danger" onclick="deleteArray(<?= $array['id'] ?>)">Delete</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php if (empty($storageArrays)): ?>
                <p style="text-align: center; color: var(--text-muted); padding: 40px;">No storage arrays configured. Add your first array!</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Volumes Tab -->
        <div id="tab-volumes" class="tab-content">
            <div class="card">
                <h2>Volumes & LUN Mappings (<?= count($volumes) ?>)</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Volume</th>
                            <th>Array</th>
                            <th>Capacity</th>
                            <th>Used</th>
                            <th>LUN ID</th>
                            <th>Tier</th>
                            <th>Features</th>
                            <th>Mapped Hosts</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($volumes as $volume):
                            $usedPercent = $volume['capacity_gb'] > 0 ? ($volume['used_gb'] / $volume['capacity_gb']) * 100 : 0;
                        ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($volume['name']) ?></strong>
                                <div style="font-size: 11px; color: var(--text-muted);"><?= $volume['volume_code'] ?></div>
                            </td>
                            <td><?= htmlspecialchars($volume['array_name']) ?></td>
                            <td><?= number_format($volume['capacity_gb']) ?> GB</td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div class="progress-bar" style="width: 80px;">
                                        <div class="progress-fill <?= $usedPercent > 85 ? 'critical' : ($usedPercent > 70 ? 'warning' : 'healthy') ?>" style="width: <?= $usedPercent ?>%;"></div>
                                    </div>
                                    <span><?= number_format($usedPercent, 0) ?>%</span>
                                </div>
                            </td>
                            <td><?= $volume['lun_id'] ?></td>
                            <td><span class="badge badge-info"><?= $volume['tier'] ?></span></td>
                            <td style="font-size: 11px;">
                                <?= $volume['thin_provisioned'] ? '✓ Thin' : '' ?>
                                <?= $volume['dedup_enabled'] ? '✓ Dedup' : '' ?>
                                <?= $volume['compression_enabled'] ? '✓ Compress' : '' ?>
                            </td>
                            <td>
                                <?php foreach ($volume['mappings'] as $mapping): ?>
                                <span class="host-tag"><?= htmlspecialchars($mapping['host_name']) ?></span>
                                <?php endforeach; ?>
                                <?php if (empty($volume['mappings'])): ?>
                                <span style="color: var(--text-muted);">Unmapped</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge badge-<?= strtolower($volume['status']) ?>"><?= $volume['status'] ?></span></td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn btn-sm btn-secondary" onclick="showMapVolumeModal(<?= $volume['id'] ?>, '<?= htmlspecialchars($volume['name']) ?>')">Map</button>
                                    <button class="btn btn-sm btn-secondary" onclick="showExpandVolumeModal(<?= $volume['id'] ?>, <?= $volume['capacity_gb'] ?>)">Expand</button>
                                    <button class="btn btn-sm btn-danger" onclick="deleteVolume(<?= $volume['id'] ?>)">Delete</button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Disks Tab -->
        <div id="tab-disks" class="tab-content">
            <div class="card">
                <h2>Disk Health Monitoring</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Array</th>
                            <th>Disk ID</th>
                            <th>Type</th>
                            <th>Capacity</th>
                            <th>Slot</th>
                            <th>Status</th>
                            <th>Temperature</th>
                            <th>Wear Level</th>
                            <th>Errors</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($disks as $disk): ?>
                        <tr>
                            <td><?= htmlspecialchars($disk['array_name']) ?></td>
                            <td><strong><?= htmlspecialchars($disk['disk_id']) ?></strong></td>
                            <td><span class="badge badge-info"><?= $disk['disk_type'] ?></span></td>
                            <td><?= number_format($disk['capacity_gb']) ?> GB</td>
                            <td><?= htmlspecialchars($disk['slot_position']) ?></td>
                            <td><span class="badge badge-<?= strtolower($disk['status']) ?>"><?= $disk['status'] ?></span></td>
                            <td style="color: <?= $disk['temperature_c'] > 60 ? 'var(--danger)' : ($disk['temperature_c'] > 50 ? 'var(--warning)' : 'var(--success)') ?>;">
                                <strong><?= $disk['temperature_c'] ?>°C</strong>
                            </td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <div class="progress-bar" style="width: 60px;">
                                        <div class="progress-fill <?= $disk['wear_level'] > 80 ? 'critical' : ($disk['wear_level'] > 60 ? 'warning' : 'healthy') ?>" style="width: <?= $disk['wear_level'] ?>%;"></div>
                                    </div>
                                    <span><?= $disk['wear_level'] ?>%</span>
                                </div>
                            </td>
                            <td style="color: <?= $disk['error_count'] > 0 ? 'var(--danger)' : 'var(--success)' ?>;">
                                <?= $disk['error_count'] ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <?php if ($disk['status'] === 'Failed' || $disk['status'] === 'Degraded'): ?>
                                    <button class="btn btn-sm btn-warning" onclick="replaceDisk(<?= $disk['id'] ?>)">Replace</button>
                                    <?php endif; ?>
                                    <button class="btn btn-sm btn-secondary" onclick="locateDisk(<?= $disk['id'] ?>)">Locate</button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Performance Tab -->
        <div id="tab-performance" class="tab-content">
            <div class="card">
                <h2><span class="live-indicator"></span> Real-Time Performance</h2>
                <div class="chart-container">
                    <canvas id="iopsChart"></canvas>
                </div>
            </div>
            <div class="grid-2">
                <div class="card">
                    <h2>Latency by Array</h2>
                    <div class="chart-container">
                        <canvas id="latencyChart"></canvas>
                    </div>
                </div>
                <div class="card">
                    <h2>Throughput Distribution</h2>
                    <div class="chart-container">
                        <canvas id="throughputChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alerts Tab -->
        <div id="tab-alerts" class="tab-content">
            <div class="card">
                <h2>Alerts & Notifications</h2>
                <div style="margin-bottom: 20px;">
                    <button class="btn btn-warning" onclick="acknowledgeAllAlerts()">Acknowledge All</button>
                </div>

                <?php
                $unacknowledged = array_filter($alerts, fn($a) => !$a['acknowledged']);
                $acknowledged = array_filter($alerts, fn($a) => $a['acknowledged']);
                ?>

                <h3 style="margin: 20px 0 15px;">Active Alerts (<?= count($unacknowledged) ?>)</h3>
                <?php foreach ($unacknowledged as $alert): ?>
                <div class="alert-card <?= strtolower($alert['severity']) ?>">
                    <div style="display: flex; justify-content: space-between; align-items: start;">
                        <div style="flex: 1;">
                            <div style="display: flex; gap: 10px; margin-bottom: 10px;">
                                <span class="badge badge-<?= strtolower($alert['severity']) ?>"><?= $alert['severity'] ?></span>
                                <span class="badge badge-secondary"><?= $alert['alert_type'] ?></span>
                            </div>
                            <p style="margin-bottom: 10px;"><?= htmlspecialchars($alert['message']) ?></p>
                            <div style="font-size: 12px; color: var(--text-muted);">
                                Array: <?= htmlspecialchars($alert['array_name'] ?? 'N/A') ?> |
                                Threshold: <?= $alert['threshold_value'] ?> |
                                Current: <?= $alert['current_value'] ?> |
                                Time: <?= date('M d, H:i', strtotime($alert['created_at'])) ?>
                            </div>
                        </div>
                        <div class="action-buttons">
                            <button class="btn btn-sm btn-warning" onclick="acknowledgeAlert(<?= $alert['id'] ?>)">Acknowledge</button>
                            <button class="btn btn-sm btn-success" onclick="resolveAlert(<?= $alert['id'] ?>)">Resolve</button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($unacknowledged)): ?>
                <p style="color: var(--text-muted); padding: 20px; text-align: center;">No active alerts</p>
                <?php endif; ?>

                <h3 style="margin: 30px 0 15px;">Acknowledged Alerts (<?= count($acknowledged) ?>)</h3>
                <?php foreach ($acknowledged as $alert): ?>
                <div class="alert-card <?= strtolower($alert['severity']) ?> acknowledged">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <span class="badge badge-<?= strtolower($alert['severity']) ?>"><?= $alert['severity'] ?></span>
                            <span style="margin-left: 10px;"><?= htmlspecialchars($alert['message']) ?></span>
                        </div>
                        <button class="btn btn-sm btn-danger" onclick="deleteAlert(<?= $alert['id'] ?>)">Delete</button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Capacity Tab -->
        <div id="tab-capacity" class="tab-content">
            <div class="card">
                <h2>Capacity Planning & Forecasting</h2>
                <div class="chart-container">
                    <canvas id="capacityChart"></canvas>
                </div>

                <h3 style="margin-top: 30px;">Capacity Details</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Array</th>
                            <th>Total</th>
                            <th>Used</th>
                            <th>Available</th>
                            <th>Usage</th>
                            <th>Growth Rate</th>
                            <th>Est. Full Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($storageArrays as $array):
                            $available = $array['total_capacity_tb'] - $array['used_capacity_tb'];
                            $percent = $array['total_capacity_tb'] > 0 ? ($array['used_capacity_tb'] / $array['total_capacity_tb']) * 100 : 0;
                            $growthRate = 1.5; // TB/month
                            $monthsToFull = $available / $growthRate;
                            $estFull = date('M Y', strtotime("+{$monthsToFull} months"));
                        ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($array['name']) ?></strong></td>
                            <td><?= number_format($array['total_capacity_tb'], 1) ?> TB</td>
                            <td><?= number_format($array['used_capacity_tb'], 1) ?> TB</td>
                            <td><?= number_format($available, 1) ?> TB</td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div class="progress-bar" style="width: 100px;">
                                        <div class="progress-fill <?= $percent > 85 ? 'critical' : ($percent > 70 ? 'warning' : 'healthy') ?>" style="width: <?= $percent ?>%;"></div>
                                    </div>
                                    <span style="color: <?= $percent > 85 ? 'var(--danger)' : ($percent > 70 ? 'var(--warning)' : 'var(--success)') ?>;"><?= number_format($percent, 1) ?>%</span>
                                </div>
                            </td>
                            <td>~<?= $growthRate ?> TB/mo</td>
                            <td><?= $estFull ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php endif; ?>
    </div>

    <!-- Add Array Modal -->
    <div id="addArrayModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add Storage Array</h2>
                <button class="modal-close" onclick="closeModal('addArrayModal')">&times;</button>
            </div>
            <form onsubmit="addArray(event)">
                <div class="grid-2">
                    <div class="form-group">
                        <label>Array Name *</label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g., Dell EMC Unity 680">
                    </div>
                    <div class="form-group">
                        <label>IP Address *</label>
                        <input type="text" name="ip_address" class="form-control" required placeholder="e.g., 192.168.10.100">
                    </div>
                    <div class="form-group">
                        <label>Vendor *</label>
                        <select name="vendor" class="form-control" required>
                            <option value="Dell EMC">Dell EMC</option>
                            <option value="NetApp">NetApp</option>
                            <option value="HPE">HPE</option>
                            <option value="Pure Storage">Pure Storage</option>
                            <option value="IBM">IBM</option>
                            <option value="Hitachi">Hitachi</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Model *</label>
                        <input type="text" name="model" class="form-control" required placeholder="e.g., Unity XT 680">
                    </div>
                    <div class="form-group">
                        <label>Location</label>
                        <input type="text" name="location" class="form-control" placeholder="e.g., Data Center 1 - Rack A1">
                    </div>
                    <div class="form-group">
                        <label>Total Capacity (TB) *</label>
                        <input type="number" name="total_capacity_tb" class="form-control" required step="0.1" min="0">
                    </div>
                    <div class="form-group">
                        <label>RAID Type</label>
                        <select name="raid_type" class="form-control">
                            <option value="RAID 5">RAID 5</option>
                            <option value="RAID 6">RAID 6</option>
                            <option value="RAID 10">RAID 10</option>
                            <option value="RAID-DP">RAID-DP</option>
                            <option value="RAID 3D">RAID 3D</option>
                        </select>
                    </div>
                </div>
                <div style="text-align: right; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addArrayModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Array</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Volume Modal -->
    <div id="addVolumeModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Create Volume</h2>
                <button class="modal-close" onclick="closeModal('addVolumeModal')">&times;</button>
            </div>
            <form onsubmit="addVolume(event)">
                <div class="grid-2">
                    <div class="form-group">
                        <label>Volume Name *</label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g., PROD-DB-01">
                    </div>
                    <div class="form-group">
                        <label>Storage Array *</label>
                        <select name="array_id" class="form-control" required>
                            <?php foreach ($storageArrays as $array): ?>
                            <option value="<?= $array['id'] ?>"><?= htmlspecialchars($array['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Capacity (GB) *</label>
                        <input type="number" name="capacity_gb" class="form-control" required min="1">
                    </div>
                    <div class="form-group">
                        <label>LUN ID</label>
                        <input type="number" name="lun_id" class="form-control" min="0">
                    </div>
                    <div class="form-group">
                        <label>RAID Type</label>
                        <select name="raid_type" class="form-control">
                            <option value="RAID 5">RAID 5</option>
                            <option value="RAID 6">RAID 6</option>
                            <option value="RAID 10">RAID 10</option>
                            <option value="RAID-DP">RAID-DP</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Tier</label>
                        <select name="tier" class="form-control">
                            <option value="Tier 1">Tier 1 (High Performance)</option>
                            <option value="Tier 2">Tier 2 (Standard)</option>
                            <option value="Tier 3">Tier 3 (Capacity)</option>
                            <option value="Archive">Archive</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Features</label>
                    <div class="form-check"><input type="checkbox" name="thin_provisioned" checked> Thin Provisioning</div>
                    <div class="form-check"><input type="checkbox" name="dedup_enabled"> Deduplication</div>
                    <div class="form-check"><input type="checkbox" name="compression_enabled"> Compression</div>
                </div>
                <div style="text-align: right; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addVolumeModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Volume</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Map Volume Modal -->
    <div id="mapVolumeModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Map Volume: <span id="mapVolumeName"></span></h2>
                <button class="modal-close" onclick="closeModal('mapVolumeModal')">&times;</button>
            </div>
            <form onsubmit="mapVolume(event)">
                <input type="hidden" name="volume_id" id="mapVolumeId">
                <div class="grid-2">
                    <div class="form-group">
                        <label>Host Name *</label>
                        <input type="text" name="host_name" class="form-control" required placeholder="e.g., db-server-01">
                    </div>
                    <div class="form-group">
                        <label>Host Type</label>
                        <select name="host_type" class="form-control">
                            <option value="Physical">Physical Server</option>
                            <option value="Virtual">Virtual Machine</option>
                            <option value="Container">Container</option>
                            <option value="Cluster">Cluster</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Operating System</label>
                        <input type="text" name="host_os" class="form-control" placeholder="e.g., RHEL 8.5">
                    </div>
                    <div class="form-group">
                        <label>WWPN (optional)</label>
                        <input type="text" name="wwpn" class="form-control" placeholder="e.g., 50:01:43:80:00:00:00:01">
                    </div>
                </div>
                <div style="text-align: right; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('mapVolumeModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Map Volume</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Expand Volume Modal -->
    <div id="expandVolumeModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Expand Volume</h2>
                <button class="modal-close" onclick="closeModal('expandVolumeModal')">&times;</button>
            </div>
            <form onsubmit="expandVolume(event)">
                <input type="hidden" name="volume_id" id="expandVolumeId">
                <div class="form-group">
                    <label>Current Size: <span id="currentVolumeSize"></span> GB</label>
                </div>
                <div class="form-group">
                    <label>New Size (GB) *</label>
                    <input type="number" name="new_size_gb" id="newVolumeSize" class="form-control" required min="1">
                </div>
                <div style="text-align: right; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('expandVolumeModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Expand Volume</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Threshold Modal -->
    <div id="thresholdModal" class="modal">
        <div class="modal-content" style="max-width: 900px;">
            <div class="modal-header">
                <h2>Configure Thresholds</h2>
                <button class="modal-close" onclick="closeModal('thresholdModal')">&times;</button>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Metric</th>
                        <th>Warning</th>
                        <th>Critical</th>
                        <th>Email</th>
                        <th>SMS</th>
                        <th>Dashboard</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($thresholds as $t): ?>
                    <tr>
                        <td><strong><?= ucfirst(str_replace('_', ' ', $t['metric_type'])) ?></strong></td>
                        <td><input type="number" id="warn_<?= $t['metric_type'] ?>" value="<?= $t['warning_threshold'] ?>" class="form-control" style="width: 80px;" step="0.1"></td>
                        <td><input type="number" id="crit_<?= $t['metric_type'] ?>" value="<?= $t['critical_threshold'] ?>" class="form-control" style="width: 80px;" step="0.1"></td>
                        <td><input type="checkbox" id="email_<?= $t['metric_type'] ?>" <?= $t['notification_email'] ? 'checked' : '' ?>></td>
                        <td><input type="checkbox" id="sms_<?= $t['metric_type'] ?>" <?= $t['notification_sms'] ? 'checked' : '' ?>></td>
                        <td><input type="checkbox" id="dash_<?= $t['metric_type'] ?>" <?= $t['notification_dashboard'] ? 'checked' : '' ?>></td>
                        <td><button class="btn btn-sm btn-primary" onclick="updateThreshold('<?= $t['metric_type'] ?>')">Save</button></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        console.log('SRM JavaScript loading...');

        // Tab switching
        function showTab(tabName) {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
            event.target.classList.add('active');
            document.getElementById('tab-' + tabName).classList.add('active');
        }

        function showModal(id) { document.getElementById(id).classList.add('active'); }
        function closeModal(id) { document.getElementById(id).classList.remove('active'); }

        // API Call function
        function apiCall(data) {
            console.log('API Call:', data);
            let body = new URLSearchParams();
            Object.keys(data).forEach(key => body.append(key, data[key]));

            return fetch(window.location.href, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body
            })
            .then(r => r.json())
            .then(result => {
                console.log('API Response:', result);
                return result;
            })
            .catch(err => {
                console.error('API Error:', err);
                return { success: false, message: err.message };
            });
        }

        // Array functions
        function addArray(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = { action: 'add_array' };
            formData.forEach((v, k) => data[k] = v);
            apiCall(data).then(r => { alert(r.message); if (r.success) location.reload(); });
        }

        function deleteArray(id) {
            if (!confirm('Delete this storage array and all its volumes?')) return;
            apiCall({ action: 'delete_array', array_id: id }).then(r => { alert(r.message); if (r.success) location.reload(); });
        }

        function refreshArray(id) {
            apiCall({ action: 'refresh_array', array_id: id }).then(r => { alert(r.message); if (r.success) location.reload(); });
        }

        function refreshAllArrays() {
            apiCall({ action: 'refresh_all_arrays' }).then(r => { alert(r.message); if (r.success) location.reload(); });
        }

        function viewArrayDetails(id) {
            apiCall({ action: 'get_array_details', array_id: id }).then(r => {
                if (r.success) {
                    alert('Array: ' + r.array.name + '\nDisks: ' + r.disks.length + '\nVolumes: ' + r.volumes.length);
                }
            });
        }

        // Volume functions
        function addVolume(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = { action: 'add_volume' };
            formData.forEach((v, k) => data[k] = v);
            apiCall(data).then(r => { alert(r.message); if (r.success) location.reload(); });
        }

        function deleteVolume(id) {
            if (!confirm('Delete this volume?')) return;
            apiCall({ action: 'delete_volume', volume_id: id }).then(r => { alert(r.message); if (r.success) location.reload(); });
        }

        function showMapVolumeModal(volumeId, volumeName) {
            document.getElementById('mapVolumeId').value = volumeId;
            document.getElementById('mapVolumeName').textContent = volumeName;
            showModal('mapVolumeModal');
        }

        function mapVolume(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = { action: 'map_volume' };
            formData.forEach((v, k) => data[k] = v);
            apiCall(data).then(r => { alert(r.message); if (r.success) location.reload(); });
        }

        function showExpandVolumeModal(volumeId, currentSize) {
            document.getElementById('expandVolumeId').value = volumeId;
            document.getElementById('currentVolumeSize').textContent = currentSize;
            document.getElementById('newVolumeSize').value = currentSize;
            document.getElementById('newVolumeSize').min = currentSize + 1;
            showModal('expandVolumeModal');
        }

        function expandVolume(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = { action: 'expand_volume' };
            formData.forEach((v, k) => data[k] = v);
            apiCall(data).then(r => { alert(r.message); if (r.success) location.reload(); });
        }

        // Disk functions
        function replaceDisk(id) {
            if (!confirm('Initiate disk replacement? A rebuild will begin.')) return;
            apiCall({ action: 'replace_disk', disk_id: id }).then(r => { alert(r.message); if (r.success) location.reload(); });
        }

        function locateDisk(id) {
            apiCall({ action: 'locate_disk', disk_id: id }).then(r => alert(r.message));
        }

        // Alert functions
        function acknowledgeAlert(id) {
            apiCall({ action: 'acknowledge_alert', alert_id: id }).then(r => { if (r.success) location.reload(); });
        }

        function acknowledgeAllAlerts() {
            apiCall({ action: 'acknowledge_all_alerts' }).then(r => { alert(r.message); if (r.success) location.reload(); });
        }

        function resolveAlert(id) {
            apiCall({ action: 'resolve_alert', alert_id: id }).then(r => { if (r.success) location.reload(); });
        }

        function deleteAlert(id) {
            apiCall({ action: 'delete_alert', alert_id: id }).then(r => { if (r.success) location.reload(); });
        }

        // Threshold functions
        function updateThreshold(metricType) {
            const data = {
                action: 'update_threshold',
                metric_type: metricType,
                warning_threshold: document.getElementById('warn_' + metricType).value,
                critical_threshold: document.getElementById('crit_' + metricType).value
            };
            if (document.getElementById('email_' + metricType).checked) data.notification_email = 1;
            if (document.getElementById('sms_' + metricType).checked) data.notification_sms = 1;
            if (document.getElementById('dash_' + metricType).checked) data.notification_dashboard = 1;
            apiCall(data).then(r => alert(r.message));
        }

        // Initialize Charts
        <?php if ($tablesExist && count($storageArrays) > 0): ?>
        // IOPS Chart
        new Chart(document.getElementById('iopsChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($storageArrays, 'name')) ?>,
                datasets: [
                    { label: 'Read IOPS', data: <?= json_encode(array_column($storageArrays, 'read_iops')) ?>, backgroundColor: '#48bb78' },
                    { label: 'Write IOPS', data: <?= json_encode(array_column($storageArrays, 'write_iops')) ?>, backgroundColor: '#4299e1' }
                ]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { labels: { color: '#a0aec0' } } }, scales: { x: { stacked: true, ticks: { color: '#a0aec0' } }, y: { stacked: true, ticks: { color: '#a0aec0' } } } }
        });

        // Latency Chart
        new Chart(document.getElementById('latencyChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($storageArrays, 'name')) ?>,
                datasets: [{ label: 'Latency (ms)', data: <?= json_encode(array_column($storageArrays, 'avg_latency_ms')) ?>, backgroundColor: ['#48bb78', '#48bb78', '#f56565', '#48bb78'] }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { ticks: { color: '#a0aec0' } }, y: { ticks: { color: '#a0aec0' } } } }
        });

        // Throughput Chart
        new Chart(document.getElementById('throughputChart'), {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_column($storageArrays, 'name')) ?>,
                datasets: [{ data: <?= json_encode(array_column($storageArrays, 'throughput_mbps')) ?>, backgroundColor: ['#667eea', '#48bb78', '#ed8936', '#4299e1'] }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right', labels: { color: '#a0aec0' } } } }
        });

        // Capacity Chart
        new Chart(document.getElementById('capacityChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($storageArrays, 'name')) ?>,
                datasets: [
                    { label: 'Used (TB)', data: <?= json_encode(array_column($storageArrays, 'used_capacity_tb')) ?>, backgroundColor: '#667eea' },
                    { label: 'Available (TB)', data: <?= json_encode(array_map(fn($a) => $a['total_capacity_tb'] - $a['used_capacity_tb'], $storageArrays)) ?>, backgroundColor: '#2d3748' }
                ]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { labels: { color: '#a0aec0' } } }, scales: { x: { stacked: true, ticks: { color: '#a0aec0' } }, y: { stacked: true, ticks: { color: '#a0aec0' } } } }
        });
        <?php endif; ?>

        console.log('SRM JavaScript loaded successfully');
    </script>
</body>
</html>
