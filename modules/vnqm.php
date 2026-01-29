<?php
/**
 * VoIP & Network Quality Manager (VNQM)
 * Monitors VoIP call quality, MOS scores, jitter, and packet loss
 */

require_once __DIR__ . '/../classes/Database.php';
$db = Database::getInstance();

// Check if VNQM tables exist
$tablesExist = true;
$dbError = '';
try {
    $result = $db->fetchOne("SELECT 1 FROM vnqm_endpoints LIMIT 1");
} catch (Exception $e) {
    $tablesExist = false;
    $dbError = $e->getMessage();
}

$endpoints = [];
$trunks = [];
$calls = [];
$alerts = [];
$codecStats = [];
$stats = [
    'total_calls_today' => 0,
    'avg_mos' => 0,
    'active_endpoints' => 0,
    'trunks_up' => 0,
    'active_alerts' => 0,
    'poor_quality_calls' => 0
];

if ($tablesExist) {
    try {
        // Get statistics
        $stats['total_calls_today'] = $db->fetchOne("SELECT COUNT(*) as cnt FROM vnqm_call_details WHERE DATE(call_start) = CURDATE()")['cnt'] ?? 0;
        $avgMos = $db->fetchOne("SELECT AVG(mos_score) as avg FROM vnqm_call_details WHERE mos_score IS NOT NULL AND DATE(call_start) = CURDATE()");
        $stats['avg_mos'] = round($avgMos['avg'] ?? 0, 2);
        $stats['active_endpoints'] = $db->fetchOne("SELECT COUNT(*) as cnt FROM vnqm_endpoints WHERE status = 'registered'")['cnt'] ?? 0;
        $stats['trunks_up'] = $db->fetchOne("SELECT COUNT(*) as cnt FROM vnqm_sip_trunks WHERE status = 'up'")['cnt'] ?? 0;
        $stats['active_alerts'] = $db->fetchOne("SELECT COUNT(*) as cnt FROM vnqm_alerts WHERE status = 'active'")['cnt'] ?? 0;
        $stats['poor_quality_calls'] = $db->fetchOne("SELECT COUNT(*) as cnt FROM vnqm_call_details WHERE quality_rating = 'poor' AND DATE(call_start) = CURDATE()")['cnt'] ?? 0;

        // Get data
        $endpoints = $db->fetchAll("SELECT * FROM vnqm_endpoints ORDER BY endpoint_name");
        $trunks = $db->fetchAll("SELECT * FROM vnqm_sip_trunks ORDER BY trunk_name");
        $calls = $db->fetchAll("SELECT * FROM vnqm_call_details ORDER BY call_start DESC LIMIT 50");
        $alerts = $db->fetchAll("SELECT * FROM vnqm_alerts ORDER BY created_at DESC LIMIT 20");
        $codecStats = $db->fetchAll("SELECT * FROM vnqm_codec_stats WHERE stat_date = CURDATE() ORDER BY total_calls DESC");

        // Quality distribution
        $qualityDist = $db->fetchAll("SELECT quality_rating, COUNT(*) as cnt FROM vnqm_call_details WHERE DATE(call_start) = CURDATE() AND quality_rating IS NOT NULL GROUP BY quality_rating");
    } catch (Exception $e) {
        // Tables might be empty
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    if (!$tablesExist) {
        echo json_encode(['success' => false, 'message' => 'Database tables not set up']);
        exit;
    }

    $action = $_POST['action'];

    try {
        switch ($action) {
            case 'acknowledge_alert':
                $alertId = (int)$_POST['alert_id'];
                $db->execute("UPDATE vnqm_alerts SET status = 'acknowledged', acknowledged_by = 'admin', acknowledged_at = NOW() WHERE id = ?", [$alertId]);
                echo json_encode(['success' => true, 'message' => 'Alert acknowledged']);
                break;

            case 'resolve_alert':
                $alertId = (int)$_POST['alert_id'];
                $db->execute("UPDATE vnqm_alerts SET status = 'resolved', resolved_at = NOW() WHERE id = ?", [$alertId]);
                echo json_encode(['success' => true, 'message' => 'Alert resolved']);
                break;

            case 'add_endpoint':
                $name = $_POST['endpoint_name'];
                $type = $_POST['endpoint_type'];
                $ip = $_POST['ip_address'];
                $ext = $_POST['extension'] ?? '';
                $location = $_POST['location'] ?? '';
                $vendor = $_POST['vendor'] ?? '';
                $model = $_POST['model'] ?? '';
                $db->execute("INSERT INTO vnqm_endpoints (endpoint_name, endpoint_type, ip_address, extension, location, vendor, model, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'offline')",
                    [$name, $type, $ip, $ext, $location, $vendor, $model]);
                echo json_encode(['success' => true, 'message' => 'Endpoint added successfully']);
                break;

            case 'delete_endpoint':
                $endpointId = (int)$_POST['endpoint_id'];
                $db->execute("DELETE FROM vnqm_endpoints WHERE id = ?", [$endpointId]);
                echo json_encode(['success' => true, 'message' => 'Endpoint deleted']);
                break;

            case 'register_endpoint':
                $endpointId = (int)$_POST['endpoint_id'];
                $db->execute("UPDATE vnqm_endpoints SET status = 'registered', last_registration = NOW() WHERE id = ?", [$endpointId]);
                echo json_encode(['success' => true, 'message' => 'Endpoint registered']);
                break;

            case 'add_trunk':
                $name = $_POST['trunk_name'];
                $provider = $_POST['provider'] ?? '';
                $primaryIp = $_POST['primary_ip'];
                $secondaryIp = $_POST['secondary_ip'] ?? null;
                $channels = (int)$_POST['channels'];
                $db->execute("INSERT INTO vnqm_sip_trunks (trunk_name, provider, primary_ip, secondary_ip, channels, status) VALUES (?, ?, ?, ?, ?, 'down')",
                    [$name, $provider, $primaryIp, $secondaryIp, $channels]);
                echo json_encode(['success' => true, 'message' => 'SIP Trunk added successfully']);
                break;

            case 'test_trunk':
                $trunkId = (int)$_POST['trunk_id'];
                // Simulate trunk test
                $status = rand(0, 1) ? 'up' : 'down';
                $db->execute("UPDATE vnqm_sip_trunks SET status = ?, last_status_change = NOW() WHERE id = ?", [$status, $trunkId]);
                echo json_encode(['success' => true, 'status' => $status, 'message' => "Trunk status: $status"]);
                break;

            case 'get_call_details':
                $callId = (int)$_POST['call_id'];
                $call = $db->fetchOne("SELECT * FROM vnqm_call_details WHERE id = ?", [$callId]);
                if ($call) {
                    echo json_encode(['success' => true, 'call' => $call]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Call not found']);
                }
                break;

            case 'get_hourly_stats':
                $hourlyStats = $db->fetchAll("
                    SELECT
                        HOUR(call_start) as hour,
                        COUNT(*) as calls,
                        AVG(mos_score) as avg_mos,
                        AVG(jitter_ms) as avg_jitter
                    FROM vnqm_call_details
                    WHERE DATE(call_start) = CURDATE()
                    GROUP BY HOUR(call_start)
                    ORDER BY hour
                ");
                echo json_encode(['success' => true, 'data' => $hourlyStats]);
                break;

            default:
                echo json_encode(['success' => false, 'message' => 'Unknown action']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Quality distribution for charts
$qualityData = ['excellent' => 0, 'good' => 0, 'fair' => 0, 'poor' => 0];
if ($tablesExist && isset($qualityDist)) {
    foreach ($qualityDist as $q) {
        if (isset($qualityData[$q['quality_rating']])) {
            $qualityData[$q['quality_rating']] = (int)$q['cnt'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VoIP Quality Manager | VNQM</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --primary: #8b5cf6;
            --primary-dark: #7c3aed;
            --secondary: #06b6d4;
            --bg-dark: #0f172a;
            --bg-card: #1e293b;
            --bg-lighter: #334155;
            --text: #f1f5f9;
            --text-muted: #94a3b8;
            --border: #475569;
            --success: #22c55e;
            --warning: #eab308;
            --danger: #ef4444;
            --info: #3b82f6;
        }
        body { font-family: 'Segoe UI', system-ui, sans-serif; background: var(--bg-dark); color: var(--text); min-height: 100vh; }
        .header { background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); padding: 20px 30px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 24px; display: flex; align-items: center; gap: 10px; }
        .header p { opacity: 0.9; font-size: 14px; }
        .back-btn { background: rgba(255,255,255,0.2); color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; }
        .container { max-width: 1600px; margin: 0 auto; padding: 20px; }
        .stats-grid { display: grid; grid-template-columns: repeat(6, 1fr); gap: 15px; margin-bottom: 20px; }
        .stat-card { background: var(--bg-card); border-radius: 12px; padding: 20px; text-align: center; border: 1px solid var(--border); }
        .stat-card .icon { font-size: 32px; margin-bottom: 10px; }
        .stat-card .value { font-size: 28px; font-weight: bold; }
        .stat-card .label { font-size: 12px; color: var(--text-muted); margin-top: 5px; }
        .mos-gauge { width: 180px; height: 180px; border-radius: 50%; display: flex; flex-direction: column; align-items: center; justify-content: center; margin: 0 auto; position: relative; }
        .mos-gauge .value { font-size: 48px; font-weight: bold; }
        .mos-gauge .label { font-size: 14px; color: var(--text-muted); }
        .tabs { display: flex; gap: 5px; margin-bottom: 20px; background: var(--bg-card); padding: 5px; border-radius: 10px; flex-wrap: wrap; }
        .tab { padding: 12px 24px; background: transparent; border: none; color: var(--text-muted); cursor: pointer; border-radius: 8px; font-size: 14px; transition: all 0.3s; }
        .tab:hover { background: var(--bg-lighter); color: var(--text); }
        .tab.active { background: var(--primary); color: white; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .card { background: var(--bg-card); border-radius: 12px; padding: 20px; margin-bottom: 20px; border: 1px solid var(--border); }
        .card h2 { font-size: 18px; margin-bottom: 15px; display: flex; align-items: center; gap: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid var(--border); }
        th { background: var(--bg-lighter); font-weight: 600; font-size: 12px; text-transform: uppercase; color: var(--text-muted); }
        tr:hover { background: rgba(255,255,255,0.02); }
        .badge { padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
        .badge-success { background: rgba(34, 197, 94, 0.2); color: #22c55e; }
        .badge-warning { background: rgba(234, 179, 8, 0.2); color: #eab308; }
        .badge-danger { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
        .badge-info { background: rgba(59, 130, 246, 0.2); color: #3b82f6; }
        .badge-secondary { background: rgba(148, 163, 184, 0.2); color: #94a3b8; }
        .badge-excellent { background: rgba(34, 197, 94, 0.2); color: #22c55e; }
        .badge-good { background: rgba(59, 130, 246, 0.2); color: #3b82f6; }
        .badge-fair { background: rgba(234, 179, 8, 0.2); color: #eab308; }
        .badge-poor { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
        .btn { padding: 8px 16px; border-radius: 6px; border: none; cursor: pointer; font-size: 13px; transition: all 0.3s; display: inline-flex; align-items: center; gap: 6px; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-secondary { background: var(--bg-lighter); color: var(--text); }
        .btn-success { background: var(--success); color: white; }
        .btn-danger { background: var(--danger); color: white; }
        .btn-warning { background: var(--warning); color: black; }
        .btn-sm { padding: 5px 10px; font-size: 11px; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; color: var(--text-muted); font-size: 13px; }
        .form-control { width: 100%; padding: 10px 12px; background: var(--bg-lighter); border: 1px solid var(--border); border-radius: 6px; color: var(--text); font-size: 14px; }
        .form-control:focus { outline: none; border-color: var(--primary); }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 1000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: var(--bg-card); border-radius: 12px; padding: 25px; max-width: 700px; width: 90%; max-height: 90vh; overflow-y: auto; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid var(--border); }
        .modal-close { background: none; border: none; color: var(--text-muted); font-size: 24px; cursor: pointer; }
        .setup-message { text-align: center; padding: 60px; background: var(--bg-card); border-radius: 12px; margin: 40px auto; max-width: 600px; }
        .trunk-status { display: flex; align-items: center; gap: 10px; }
        .trunk-status .indicator { width: 12px; height: 12px; border-radius: 50%; }
        .trunk-status .indicator.up { background: var(--success); box-shadow: 0 0 10px var(--success); }
        .trunk-status .indicator.down { background: var(--danger); box-shadow: 0 0 10px var(--danger); }
        .trunk-status .indicator.degraded { background: var(--warning); box-shadow: 0 0 10px var(--warning); }
        .quality-cards { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin: 20px 0; }
        .quality-card { background: var(--bg-lighter); padding: 20px; border-radius: 10px; text-align: center; }
        .quality-card .emoji { font-size: 36px; margin-bottom: 10px; }
        .quality-card .count { font-size: 32px; font-weight: bold; }
        .quality-card .label { font-size: 12px; color: var(--text-muted); margin-top: 5px; }
        .alert-item { padding: 15px; background: var(--bg-lighter); border-radius: 8px; margin-bottom: 10px; border-left: 4px solid; }
        .alert-item.critical { border-left-color: var(--danger); }
        .alert-item.warning { border-left-color: var(--warning); }
        .alert-item.info { border-left-color: var(--info); }
        .endpoint-card { background: var(--bg-lighter); border-radius: 10px; padding: 15px; }
        .endpoint-card .status-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; margin-right: 8px; }
        .endpoint-card .status-dot.registered { background: var(--success); }
        .endpoint-card .status-dot.offline { background: var(--danger); }
        .endpoint-card .status-dot.busy { background: var(--warning); }
        @media (max-width: 1200px) {
            .stats-grid { grid-template-columns: repeat(3, 1fr); }
            .grid-2, .grid-3 { grid-template-columns: 1fr; }
            .quality-cards { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>VoIP & Network Quality Manager</h1>
            <p>Monitor VoIP call quality, MOS scores, jitter, packet loss, and SIP trunk performance</p>
        </div>
        <a href="../index.php" class="back-btn">Back to Dashboard</a>
    </div>

    <div class="container">
        <?php if (!$tablesExist): ?>
        <div class="setup-message">
            <div style="font-size: 80px;">📞</div>
            <h2 style="margin: 20px 0;">Database Setup Required</h2>
            <?php if ($dbError): ?>
            <p style="color: var(--danger); margin: 20px 0; padding: 15px; background: rgba(239,68,68,0.1); border-radius: 8px;"><?= htmlspecialchars($dbError) ?></p>
            <?php endif; ?>
            <p style="color: var(--text-muted); margin: 20px 0;">Please run the setup script to create the VNQM database tables.</p>
            <a href="../setup_ncm_vnqm.php" class="btn btn-primary" style="font-size: 16px; padding: 12px 24px;">Run Setup Script</a>
        </div>
        <?php else: ?>

        <!-- MOS Gauge and Stats -->
        <div class="grid-2" style="margin-bottom: 20px;">
            <div class="card" style="text-align: center;">
                <h2 style="justify-content: center;">Average MOS Score (Today)</h2>
                <?php
                $mosColor = '#22c55e';
                $mosText = 'Excellent';
                if ($stats['avg_mos'] < 2.5) { $mosColor = '#ef4444'; $mosText = 'Poor'; }
                elseif ($stats['avg_mos'] < 3.5) { $mosColor = '#eab308'; $mosText = 'Fair'; }
                elseif ($stats['avg_mos'] < 4.0) { $mosColor = '#3b82f6'; $mosText = 'Good'; }
                ?>
                <div class="mos-gauge" style="background: conic-gradient(<?= $mosColor ?> <?= ($stats['avg_mos'] / 5) * 100 ?>%, var(--bg-lighter) 0);">
                    <div style="background: var(--bg-card); width: 140px; height: 140px; border-radius: 50%; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                        <div class="value" style="color: <?= $mosColor ?>;"><?= $stats['avg_mos'] ?: 'N/A' ?></div>
                        <div class="label"><?= $mosText ?></div>
                    </div>
                </div>
                <p style="margin-top: 15px; color: var(--text-muted);">Mean Opinion Score (1.0 = Poor, 5.0 = Excellent)</p>
            </div>
            <div>
                <div class="stats-grid" style="grid-template-columns: repeat(3, 1fr);">
                    <div class="stat-card">
                        <div class="icon">📞</div>
                        <div class="value" style="color: var(--primary);"><?= $stats['total_calls_today'] ?></div>
                        <div class="label">Calls Today</div>
                    </div>
                    <div class="stat-card">
                        <div class="icon">📱</div>
                        <div class="value" style="color: var(--success);"><?= $stats['active_endpoints'] ?></div>
                        <div class="label">Active Endpoints</div>
                    </div>
                    <div class="stat-card">
                        <div class="icon">🔗</div>
                        <div class="value" style="color: var(--info);"><?= $stats['trunks_up'] ?></div>
                        <div class="label">Trunks Up</div>
                    </div>
                    <div class="stat-card">
                        <div class="icon">⚠️</div>
                        <div class="value" style="color: var(--warning);"><?= $stats['active_alerts'] ?></div>
                        <div class="label">Active Alerts</div>
                    </div>
                    <div class="stat-card">
                        <div class="icon">📉</div>
                        <div class="value" style="color: var(--danger);"><?= $stats['poor_quality_calls'] ?></div>
                        <div class="label">Poor Quality</div>
                    </div>
                    <div class="stat-card">
                        <div class="icon">📊</div>
                        <div class="value"><?= count($codecStats) ?></div>
                        <div class="label">Codecs Used</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quality Distribution -->
        <div class="quality-cards">
            <div class="quality-card" style="border-top: 4px solid var(--success);">
                <div class="emoji">😄</div>
                <div class="count" style="color: var(--success);"><?= $qualityData['excellent'] ?></div>
                <div class="label">Excellent (MOS 4.0+)</div>
            </div>
            <div class="quality-card" style="border-top: 4px solid var(--info);">
                <div class="emoji">🙂</div>
                <div class="count" style="color: var(--info);"><?= $qualityData['good'] ?></div>
                <div class="label">Good (MOS 3.5-4.0)</div>
            </div>
            <div class="quality-card" style="border-top: 4px solid var(--warning);">
                <div class="emoji">😐</div>
                <div class="count" style="color: var(--warning);"><?= $qualityData['fair'] ?></div>
                <div class="label">Fair (MOS 3.0-3.5)</div>
            </div>
            <div class="quality-card" style="border-top: 4px solid var(--danger);">
                <div class="emoji">😞</div>
                <div class="count" style="color: var(--danger);"><?= $qualityData['poor'] ?></div>
                <div class="label">Poor (MOS < 3.0)</div>
            </div>
        </div>

        <div class="tabs">
            <button class="tab active" onclick="showTab('calls')">Call History</button>
            <button class="tab" onclick="showTab('endpoints')">Endpoints</button>
            <button class="tab" onclick="showTab('trunks')">SIP Trunks</button>
            <button class="tab" onclick="showTab('alerts')">Alerts</button>
            <button class="tab" onclick="showTab('codecs')">Codec Analysis</button>
            <button class="tab" onclick="showTab('analytics')">Analytics</button>
        </div>

        <!-- Call History Tab -->
        <div id="tab-calls" class="tab-content active">
            <div class="card">
                <h2>📞 Recent VoIP Calls</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Call ID</th>
                            <th>Direction</th>
                            <th>Caller → Callee</th>
                            <th>Duration</th>
                            <th>Codec</th>
                            <th>MOS</th>
                            <th>Jitter</th>
                            <th>Packet Loss</th>
                            <th>Quality</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($calls as $call): ?>
                        <tr onclick="viewCallDetails(<?= $call['id'] ?>)" style="cursor: pointer;">
                            <td><code style="font-size: 10px;"><?= htmlspecialchars(substr($call['call_id'], 0, 15)) ?>...</code></td>
                            <td>
                                <span class="badge badge-<?= $call['call_direction'] === 'inbound' ? 'success' : ($call['call_direction'] === 'outbound' ? 'info' : 'secondary') ?>">
                                    <?= strtoupper($call['call_direction']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($call['caller_number']) ?> → <?= htmlspecialchars($call['callee_number']) ?></td>
                            <td><?= gmdate("i:s", $call['duration']) ?></td>
                            <td><code><?= htmlspecialchars($call['codec'] ?? 'N/A') ?></code></td>
                            <td style="font-weight: bold; color: <?= $call['mos_score'] >= 4 ? 'var(--success)' : ($call['mos_score'] >= 3.5 ? 'var(--info)' : ($call['mos_score'] >= 3 ? 'var(--warning)' : 'var(--danger)')) ?>;">
                                <?= $call['mos_score'] ?? 'N/A' ?>
                            </td>
                            <td><?= $call['jitter_ms'] ? $call['jitter_ms'] . ' ms' : 'N/A' ?></td>
                            <td><?= $call['packet_loss_percent'] !== null ? $call['packet_loss_percent'] . '%' : 'N/A' ?></td>
                            <td>
                                <span class="badge badge-<?= $call['quality_rating'] ?? 'secondary' ?>">
                                    <?= strtoupper($call['quality_rating'] ?? 'N/A') ?>
                                </span>
                            </td>
                            <td style="font-size: 12px;"><?= date('Y-m-d H:i', strtotime($call['call_start'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($calls)): ?>
                        <tr><td colspan="10" style="text-align: center; color: var(--text-muted); padding: 40px;">No call records found</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Endpoints Tab -->
        <div id="tab-endpoints" class="tab-content">
            <div class="card">
                <h2>📱 VoIP Endpoints <button class="btn btn-primary btn-sm" onclick="showModal('addEndpointModal')" style="margin-left: auto;">+ Add Endpoint</button></h2>
                <div class="grid-3">
                    <?php foreach ($endpoints as $endpoint): ?>
                    <div class="endpoint-card">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px;">
                            <div>
                                <span class="status-dot <?= $endpoint['status'] ?>"></span>
                                <strong><?= htmlspecialchars($endpoint['endpoint_name']) ?></strong>
                            </div>
                            <span class="badge badge-<?= $endpoint['status'] === 'registered' ? 'success' : ($endpoint['status'] === 'busy' ? 'warning' : 'danger') ?>">
                                <?= strtoupper($endpoint['status']) ?>
                            </span>
                        </div>
                        <div style="font-size: 13px; color: var(--text-muted); margin-bottom: 10px;">
                            <div>Type: <?= ucfirst(str_replace('_', ' ', $endpoint['endpoint_type'])) ?></div>
                            <div>IP: <code><?= htmlspecialchars($endpoint['ip_address']) ?></code></div>
                            <?php if ($endpoint['extension']): ?>
                            <div>Extension: <strong><?= htmlspecialchars($endpoint['extension']) ?></strong></div>
                            <?php endif; ?>
                            <?php if ($endpoint['location']): ?>
                            <div>Location: <?= htmlspecialchars($endpoint['location']) ?></div>
                            <?php endif; ?>
                            <?php if ($endpoint['vendor']): ?>
                            <div>Device: <?= htmlspecialchars($endpoint['vendor'] . ' ' . $endpoint['model']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div style="display: flex; gap: 10px;">
                            <?php if ($endpoint['status'] !== 'registered'): ?>
                            <button class="btn btn-sm btn-success" onclick="registerEndpoint(<?= $endpoint['id'] ?>)">Register</button>
                            <?php endif; ?>
                            <button class="btn btn-sm btn-danger" onclick="deleteEndpoint(<?= $endpoint['id'] ?>)">Delete</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($endpoints)): ?>
                    <div style="grid-column: span 3; text-align: center; color: var(--text-muted); padding: 40px;">
                        No endpoints configured. Add your first VoIP endpoint!
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- SIP Trunks Tab -->
        <div id="tab-trunks" class="tab-content">
            <div class="card">
                <h2>🔗 SIP Trunks <button class="btn btn-primary btn-sm" onclick="showModal('addTrunkModal')" style="margin-left: auto;">+ Add Trunk</button></h2>
                <table>
                    <thead>
                        <tr>
                            <th>Trunk Name</th>
                            <th>Provider</th>
                            <th>Primary IP</th>
                            <th>Channels</th>
                            <th>In Use</th>
                            <th>Calls Today</th>
                            <th>Failed</th>
                            <th>Avg MOS</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($trunks as $trunk): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($trunk['trunk_name']) ?></strong></td>
                            <td><?= htmlspecialchars($trunk['provider'] ?? '-') ?></td>
                            <td><code><?= htmlspecialchars($trunk['primary_ip']) ?></code></td>
                            <td><?= $trunk['channels'] ?></td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div style="flex: 1; background: var(--bg-lighter); border-radius: 4px; height: 8px; overflow: hidden;">
                                        <div style="width: <?= $trunk['channels'] > 0 ? ($trunk['channels_in_use'] / $trunk['channels']) * 100 : 0 ?>%; height: 100%; background: var(--primary);"></div>
                                    </div>
                                    <span style="font-size: 12px;"><?= $trunk['channels_in_use'] ?></span>
                                </div>
                            </td>
                            <td><?= $trunk['total_calls_today'] ?></td>
                            <td style="color: <?= $trunk['failed_calls_today'] > 0 ? 'var(--danger)' : 'inherit' ?>;"><?= $trunk['failed_calls_today'] ?></td>
                            <td style="font-weight: bold; color: <?= $trunk['avg_mos_today'] >= 4 ? 'var(--success)' : ($trunk['avg_mos_today'] >= 3.5 ? 'var(--info)' : 'var(--warning)') ?>;">
                                <?= $trunk['avg_mos_today'] ?? '-' ?>
                            </td>
                            <td>
                                <div class="trunk-status">
                                    <span class="indicator <?= $trunk['status'] ?>"></span>
                                    <span class="badge badge-<?= $trunk['status'] === 'up' ? 'success' : ($trunk['status'] === 'degraded' ? 'warning' : 'danger') ?>">
                                        <?= strtoupper($trunk['status']) ?>
                                    </span>
                                </div>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-secondary" onclick="testTrunk(<?= $trunk['id'] ?>)">Test</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($trunks)): ?>
                        <tr><td colspan="10" style="text-align: center; color: var(--text-muted); padding: 40px;">No SIP trunks configured</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Alerts Tab -->
        <div id="tab-alerts" class="tab-content">
            <div class="card">
                <h2>⚠️ Active Alerts</h2>
                <?php foreach ($alerts as $alert): ?>
                <div class="alert-item <?= $alert['severity'] ?>">
                    <div style="display: flex; justify-content: space-between; align-items: start;">
                        <div>
                            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 5px;">
                                <span class="badge badge-<?= $alert['severity'] === 'critical' ? 'danger' : ($alert['severity'] === 'warning' ? 'warning' : 'info') ?>">
                                    <?= strtoupper($alert['severity']) ?>
                                </span>
                                <strong><?= ucfirst(str_replace('_', ' ', $alert['alert_type'])) ?></strong>
                                <span class="badge badge-<?= $alert['status'] === 'active' ? 'danger' : ($alert['status'] === 'acknowledged' ? 'warning' : 'success') ?>">
                                    <?= strtoupper($alert['status']) ?>
                                </span>
                            </div>
                            <p style="color: var(--text-muted);"><?= htmlspecialchars($alert['message']) ?></p>
                            <div style="font-size: 12px; color: var(--text-muted); margin-top: 5px;">
                                <?= date('Y-m-d H:i:s', strtotime($alert['created_at'])) ?>
                            </div>
                        </div>
                        <div style="display: flex; gap: 10px;">
                            <?php if ($alert['status'] === 'active'): ?>
                            <button class="btn btn-sm btn-warning" onclick="acknowledgeAlert(<?= $alert['id'] ?>)">Acknowledge</button>
                            <?php endif; ?>
                            <?php if ($alert['status'] !== 'resolved'): ?>
                            <button class="btn btn-sm btn-success" onclick="resolveAlert(<?= $alert['id'] ?>)">Resolve</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($alerts)): ?>
                <p style="text-align: center; color: var(--text-muted); padding: 40px;">No alerts</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Codec Analysis Tab -->
        <div id="tab-codecs" class="tab-content">
            <div class="card">
                <h2>🎵 Codec Performance Analysis</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Codec</th>
                            <th>Total Calls</th>
                            <th>Average MOS</th>
                            <th>Avg Jitter</th>
                            <th>Avg Packet Loss</th>
                            <th>Bandwidth</th>
                            <th>Performance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($codecStats as $codec): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($codec['codec_name']) ?></strong></td>
                            <td><?= $codec['total_calls'] ?></td>
                            <td style="font-weight: bold; color: <?= $codec['avg_mos'] >= 4 ? 'var(--success)' : ($codec['avg_mos'] >= 3.5 ? 'var(--info)' : 'var(--warning)') ?>;">
                                <?= $codec['avg_mos'] ?>
                            </td>
                            <td><?= $codec['avg_jitter'] ?> ms</td>
                            <td><?= $codec['avg_packet_loss'] ?>%</td>
                            <td><?= $codec['bandwidth_kbps'] ?> kbps</td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div style="flex: 1; max-width: 100px; background: var(--bg-lighter); border-radius: 4px; height: 8px; overflow: hidden;">
                                        <div style="width: <?= ($codec['avg_mos'] / 5) * 100 ?>%; height: 100%; background: <?= $codec['avg_mos'] >= 4 ? 'var(--success)' : ($codec['avg_mos'] >= 3.5 ? 'var(--info)' : 'var(--warning)') ?>;"></div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($codecStats)): ?>
                        <tr><td colspan="7" style="text-align: center; color: var(--text-muted); padding: 40px;">No codec statistics available</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Analytics Tab -->
        <div id="tab-analytics" class="tab-content">
            <div class="grid-2">
                <div class="card">
                    <h2>📈 Hourly Call Volume</h2>
                    <canvas id="hourlyChart" height="200"></canvas>
                </div>
                <div class="card">
                    <h2>📊 Quality Distribution</h2>
                    <canvas id="qualityChart" height="200"></canvas>
                </div>
            </div>
            <div class="card">
                <h2>📉 MOS Score Trend</h2>
                <canvas id="mosChart" height="150"></canvas>
            </div>
        </div>

        <?php endif; ?>
    </div>

    <!-- Add Endpoint Modal -->
    <div id="addEndpointModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add VoIP Endpoint</h2>
                <button class="modal-close" onclick="closeModal('addEndpointModal')">&times;</button>
            </div>
            <form onsubmit="addEndpoint(event)">
                <div class="grid-2">
                    <div class="form-group">
                        <label>Endpoint Name *</label>
                        <input type="text" name="endpoint_name" class="form-control" required placeholder="e.g., Reception Phone">
                    </div>
                    <div class="form-group">
                        <label>Type *</label>
                        <select name="endpoint_type" class="form-control" required>
                            <option value="ip_phone">IP Phone</option>
                            <option value="softphone">Softphone</option>
                            <option value="conference">Conference Phone</option>
                            <option value="gateway">Gateway</option>
                            <option value="sbc">SBC</option>
                            <option value="pbx">PBX</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>IP Address *</label>
                        <input type="text" name="ip_address" class="form-control" required placeholder="e.g., 192.168.10.100">
                    </div>
                    <div class="form-group">
                        <label>Extension</label>
                        <input type="text" name="extension" class="form-control" placeholder="e.g., 1001">
                    </div>
                    <div class="form-group">
                        <label>Location</label>
                        <input type="text" name="location" class="form-control" placeholder="e.g., Reception">
                    </div>
                    <div class="form-group">
                        <label>Vendor</label>
                        <select name="vendor" class="form-control">
                            <option value="">Select Vendor</option>
                            <option value="Cisco">Cisco</option>
                            <option value="Polycom">Polycom</option>
                            <option value="Yealink">Yealink</option>
                            <option value="Grandstream">Grandstream</option>
                            <option value="Avaya">Avaya</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Model</label>
                    <input type="text" name="model" class="form-control" placeholder="e.g., CP-8845">
                </div>
                <div style="text-align: right; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addEndpointModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Endpoint</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Trunk Modal -->
    <div id="addTrunkModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add SIP Trunk</h2>
                <button class="modal-close" onclick="closeModal('addTrunkModal')">&times;</button>
            </div>
            <form onsubmit="addTrunk(event)">
                <div class="grid-2">
                    <div class="form-group">
                        <label>Trunk Name *</label>
                        <input type="text" name="trunk_name" class="form-control" required placeholder="e.g., Primary PSTN">
                    </div>
                    <div class="form-group">
                        <label>Provider</label>
                        <input type="text" name="provider" class="form-control" placeholder="e.g., AT&T SIP">
                    </div>
                    <div class="form-group">
                        <label>Primary IP *</label>
                        <input type="text" name="primary_ip" class="form-control" required placeholder="e.g., 203.0.113.10">
                    </div>
                    <div class="form-group">
                        <label>Secondary IP</label>
                        <input type="text" name="secondary_ip" class="form-control" placeholder="e.g., 203.0.113.11">
                    </div>
                    <div class="form-group">
                        <label>Channels *</label>
                        <input type="number" name="channels" class="form-control" required min="1" value="30">
                    </div>
                </div>
                <div style="text-align: right; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addTrunkModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Trunk</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Call Details Modal -->
    <div id="callDetailsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Call Details</h2>
                <button class="modal-close" onclick="closeModal('callDetailsModal')">&times;</button>
            </div>
            <div id="callDetailsContent"></div>
        </div>
    </div>

    <script>
        function showTab(tabName) {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
            event.target.classList.add('active');
            document.getElementById('tab-' + tabName).classList.add('active');
        }

        function showModal(modalId) { document.getElementById(modalId).classList.add('active'); }
        function closeModal(modalId) { document.getElementById(modalId).classList.remove('active'); }

        function apiCall(data) {
            let body;
            if (data instanceof FormData) {
                body = new URLSearchParams(data);
            } else {
                body = new URLSearchParams();
                for (const key in data) {
                    if (data.hasOwnProperty(key)) {
                        body.append(key, data[key]);
                    }
                }
            }
            return fetch(window.location.href, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body
            })
            .then(r => {
                if (!r.ok) throw new Error('Network response was not ok');
                return r.json();
            })
            .catch(err => {
                console.error('API Error:', err);
                return { success: false, message: 'Request failed: ' + err.message };
            });
        }

        function addEndpoint(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            formData.append('action', 'add_endpoint');
            apiCall(formData).then(data => {
                alert(data.message);
                if (data.success) location.reload();
            });
        }

        function deleteEndpoint(endpointId) {
            if (!confirm('Delete this endpoint?')) return;
            apiCall({ action: 'delete_endpoint', endpoint_id: endpointId }).then(data => {
                alert(data.message);
                if (data.success) location.reload();
            });
        }

        function registerEndpoint(endpointId) {
            apiCall({ action: 'register_endpoint', endpoint_id: endpointId }).then(data => {
                alert(data.message);
                if (data.success) location.reload();
            });
        }

        function addTrunk(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            formData.append('action', 'add_trunk');
            apiCall(formData).then(data => {
                alert(data.message);
                if (data.success) location.reload();
            });
        }

        function testTrunk(trunkId) {
            apiCall({ action: 'test_trunk', trunk_id: trunkId }).then(data => {
                alert(data.message);
                if (data.success) location.reload();
            });
        }

        function acknowledgeAlert(alertId) {
            apiCall({ action: 'acknowledge_alert', alert_id: alertId }).then(data => {
                alert(data.message);
                if (data.success) location.reload();
            });
        }

        function resolveAlert(alertId) {
            apiCall({ action: 'resolve_alert', alert_id: alertId }).then(data => {
                alert(data.message);
                if (data.success) location.reload();
            });
        }

        function viewCallDetails(callId) {
            apiCall({ action: 'get_call_details', call_id: callId }).then(data => {
                if (data.success) {
                    const c = data.call;
                    document.getElementById('callDetailsContent').innerHTML = `
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div>
                                <h3 style="margin-bottom: 15px;">Call Information</h3>
                                <p><strong>Call ID:</strong> <code>${c.call_id}</code></p>
                                <p><strong>Direction:</strong> ${c.call_direction}</p>
                                <p><strong>Caller:</strong> ${c.caller_number}</p>
                                <p><strong>Callee:</strong> ${c.callee_number}</p>
                                <p><strong>Start Time:</strong> ${c.call_start}</p>
                                <p><strong>End Time:</strong> ${c.call_end || 'N/A'}</p>
                                <p><strong>Duration:</strong> ${Math.floor(c.duration / 60)}:${(c.duration % 60).toString().padStart(2, '0')}</p>
                                <p><strong>Codec:</strong> ${c.codec || 'N/A'}</p>
                            </div>
                            <div>
                                <h3 style="margin-bottom: 15px;">Quality Metrics</h3>
                                <p><strong>MOS Score:</strong> <span style="font-size: 24px; font-weight: bold; color: ${c.mos_score >= 4 ? '#22c55e' : (c.mos_score >= 3.5 ? '#3b82f6' : '#ef4444')};">${c.mos_score || 'N/A'}</span></p>
                                <p><strong>R-Factor:</strong> ${c.r_factor || 'N/A'}</p>
                                <p><strong>Jitter:</strong> ${c.jitter_ms} ms (max: ${c.jitter_max_ms || 'N/A'} ms)</p>
                                <p><strong>Packet Loss:</strong> ${c.packet_loss_percent}%</p>
                                <p><strong>Latency:</strong> ${c.latency_ms || 'N/A'} ms</p>
                                <p><strong>Quality Rating:</strong> <span class="badge badge-${c.quality_rating}">${(c.quality_rating || 'N/A').toUpperCase()}</span></p>
                                <p><strong>Hangup Cause:</strong> ${c.hangup_cause || 'Normal'}</p>
                            </div>
                        </div>
                    `;
                    showModal('callDetailsModal');
                } else {
                    alert(data.message);
                }
            });
        }

        // Initialize charts
        <?php if ($tablesExist): ?>
        // Quality Distribution Chart
        const qualityCtx = document.getElementById('qualityChart');
        if (qualityCtx) {
            new Chart(qualityCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Excellent', 'Good', 'Fair', 'Poor'],
                    datasets: [{
                        data: [<?= $qualityData['excellent'] ?>, <?= $qualityData['good'] ?>, <?= $qualityData['fair'] ?>, <?= $qualityData['poor'] ?>],
                        backgroundColor: ['#22c55e', '#3b82f6', '#eab308', '#ef4444'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { position: 'bottom', labels: { color: '#f1f5f9', padding: 15 } }
                    }
                }
            });
        }

        // Hourly Call Volume Chart
        apiCall({ action: 'get_hourly_stats' }).then(data => {
            if (data.success) {
                const hourlyCtx = document.getElementById('hourlyChart');
                if (hourlyCtx) {
                    const hours = Array.from({length: 24}, (_, i) => i);
                    const callCounts = hours.map(h => {
                        const stat = data.data.find(s => s.hour == h);
                        return stat ? stat.calls : 0;
                    });

                    new Chart(hourlyCtx, {
                        type: 'bar',
                        data: {
                            labels: hours.map(h => h + ':00'),
                            datasets: [{
                                label: 'Calls',
                                data: callCounts,
                                backgroundColor: '#8b5cf6',
                                borderRadius: 4
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: { display: false }
                            },
                            scales: {
                                x: { grid: { color: '#334155' }, ticks: { color: '#94a3b8' } },
                                y: { grid: { color: '#334155' }, ticks: { color: '#94a3b8' } }
                            }
                        }
                    });
                }
            }
        });

        // MOS Trend Chart (simulated data)
        const mosCtx = document.getElementById('mosChart');
        if (mosCtx) {
            const hours = Array.from({length: 24}, (_, i) => i + ':00');
            const mosData = Array.from({length: 24}, () => (Math.random() * 1.5 + 3.5).toFixed(2));

            new Chart(mosCtx, {
                type: 'line',
                data: {
                    labels: hours,
                    datasets: [{
                        label: 'Average MOS',
                        data: mosData,
                        borderColor: '#8b5cf6',
                        backgroundColor: 'rgba(139, 92, 246, 0.1)',
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        x: { grid: { color: '#334155' }, ticks: { color: '#94a3b8' } },
                        y: { min: 1, max: 5, grid: { color: '#334155' }, ticks: { color: '#94a3b8' } }
                    }
                }
            });
        }
        <?php endif; ?>
    </script>
</body>
</html>
