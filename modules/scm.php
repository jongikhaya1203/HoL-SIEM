<?php
/**
 * Server Configuration Monitor (SCM)
 * Comprehensive server configuration tracking and drift detection
 */

require_once __DIR__ . '/../classes/Database.php';
$db = Database::getInstance();

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    try {
        switch ($_POST['action']) {
            case 'add_server':
                $db->execute(
                    "INSERT INTO scm_servers (server_name, ip_address, os_type, os_version, kernel_version, server_type, role, compliance_status, last_scan) VALUES (?, ?, ?, ?, ?, ?, ?, 'unknown', NOW())",
                    [
                        $_POST['server_name'],
                        $_POST['ip_address'],
                        $_POST['os_type'],
                        $_POST['os_version'] ?: null,
                        $_POST['kernel_version'] ?: null,
                        $_POST['server_type'],
                        $_POST['role'] ?: null
                    ]
                );
                echo json_encode(['success' => true, 'message' => 'Server added successfully']);
                break;

            case 'delete_server':
                $db->execute("DELETE FROM scm_servers WHERE id = ?", [$_POST['server_id']]);
                echo json_encode(['success' => true, 'message' => 'Server deleted successfully']);
                break;

            case 'scan_server':
                // Simulate a configuration scan
                $server = $db->fetchOne("SELECT * FROM scm_servers WHERE id = ?", [$_POST['server_id']]);
                if (!$server) {
                    echo json_encode(['success' => false, 'message' => 'Server not found']);
                    break;
                }

                // Update scan time and simulate drift detection
                $driftCount = rand(0, 3);
                $status = $driftCount > 0 ? 'drift' : 'compliant';

                $db->execute(
                    "UPDATE scm_servers SET last_scan = NOW(), compliance_status = ?, drift_count = ?, services_count = ?, config_files_count = ?, packages_count = ? WHERE id = ?",
                    [$status, $driftCount, rand(20, 60), rand(100, 300), rand(200, 600), $_POST['server_id']]
                );

                echo json_encode([
                    'success' => true,
                    'message' => "Scan completed. Status: {$status}, {$driftCount} drift(s) detected",
                    'status' => $status,
                    'drift_count' => $driftCount
                ]);
                break;

            case 'acknowledge_change':
                $db->execute(
                    "UPDATE scm_config_changes SET is_acknowledged = 1, acknowledged_by = 'admin', acknowledged_at = NOW() WHERE id = ?",
                    [$_POST['change_id']]
                );
                echo json_encode(['success' => true, 'message' => 'Change acknowledged']);
                break;

            case 'acknowledge_file_change':
                $db->execute(
                    "UPDATE scm_file_integrity SET is_acknowledged = 1 WHERE id = ?",
                    [$_POST['file_id']]
                );
                echo json_encode(['success' => true, 'message' => 'File change acknowledged']);
                break;

            case 'add_certificate':
                $expiryDate = $_POST['expiry_date'];
                $daysRemaining = floor((strtotime($expiryDate) - time()) / 86400);
                $status = 'valid';
                if ($daysRemaining <= 15) $status = 'expiring';
                elseif ($daysRemaining <= 0) $status = 'expired';

                $db->execute(
                    "INSERT INTO scm_certificates (server_id, cert_name, subject, issuer, cert_type, expiry_date, days_remaining, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                    [
                        $_POST['server_id'],
                        $_POST['cert_name'],
                        $_POST['subject'],
                        $_POST['issuer'],
                        $_POST['cert_type'],
                        $expiryDate,
                        $daysRemaining,
                        $status
                    ]
                );
                echo json_encode(['success' => true, 'message' => 'Certificate added successfully']);
                break;

            case 'delete_certificate':
                $db->execute("DELETE FROM scm_certificates WHERE id = ?", [$_POST['cert_id']]);
                echo json_encode(['success' => true, 'message' => 'Certificate deleted']);
                break;

            case 'add_firewall_rule':
                $db->execute(
                    "INSERT INTO scm_firewall_rules (server_id, rule_name, direction, protocol, port, source_ip, destination_ip, action, is_enabled) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [
                        $_POST['server_id'],
                        $_POST['rule_name'],
                        $_POST['direction'],
                        $_POST['protocol'],
                        $_POST['port'] ?: null,
                        $_POST['source_ip'] ?: 'any',
                        $_POST['destination_ip'] ?: 'any',
                        $_POST['rule_action'],
                        isset($_POST['is_enabled']) ? 1 : 0
                    ]
                );
                echo json_encode(['success' => true, 'message' => 'Firewall rule added']);
                break;

            case 'toggle_firewall_rule':
                $rule = $db->fetchOne("SELECT is_enabled FROM scm_firewall_rules WHERE id = ?", [$_POST['rule_id']]);
                $newStatus = $rule['is_enabled'] ? 0 : 1;
                $db->execute("UPDATE scm_firewall_rules SET is_enabled = ? WHERE id = ?", [$newStatus, $_POST['rule_id']]);
                echo json_encode(['success' => true, 'message' => $newStatus ? 'Rule enabled' : 'Rule disabled']);
                break;

            case 'delete_firewall_rule':
                $db->execute("DELETE FROM scm_firewall_rules WHERE id = ?", [$_POST['rule_id']]);
                echo json_encode(['success' => true, 'message' => 'Firewall rule deleted']);
                break;

            case 'get_server_details':
                $server = $db->fetchOne("SELECT * FROM scm_servers WHERE id = ?", [$_POST['server_id']]);
                $certs = $db->fetchAll("SELECT * FROM scm_certificates WHERE server_id = ? ORDER BY expiry_date", [$_POST['server_id']]);
                $firewall = $db->fetchAll("SELECT * FROM scm_firewall_rules WHERE server_id = ?", [$_POST['server_id']]);
                $tasks = $db->fetchAll("SELECT * FROM scm_scheduled_tasks WHERE server_id = ?", [$_POST['server_id']]);
                $changes = $db->fetchAll("SELECT * FROM scm_config_changes WHERE server_id = ? ORDER BY detected_at DESC LIMIT 10", [$_POST['server_id']]);
                $files = $db->fetchAll("SELECT * FROM scm_file_integrity WHERE server_id = ? AND change_type != 'none' ORDER BY change_detected_at DESC LIMIT 10", [$_POST['server_id']]);

                echo json_encode([
                    'success' => true,
                    'server' => $server,
                    'certificates' => $certs,
                    'firewall_rules' => $firewall,
                    'scheduled_tasks' => $tasks,
                    'config_changes' => $changes,
                    'file_changes' => $files
                ]);
                break;

            case 'create_baseline':
                $server = $db->fetchOne("SELECT * FROM scm_servers WHERE id = ?", [$_POST['server_id']]);
                if (!$server) {
                    echo json_encode(['success' => false, 'message' => 'Server not found']);
                    break;
                }

                // Mark existing baselines as not current
                $db->execute("UPDATE scm_baselines SET is_current = 0 WHERE server_id = ? AND baseline_type = ?",
                    [$_POST['server_id'], $_POST['baseline_type']]);

                // Create new baseline (simulated data)
                $baselineData = json_encode(['captured_at' => date('Y-m-d H:i:s'), 'items_count' => rand(50, 200)]);
                $db->execute(
                    "INSERT INTO scm_baselines (server_id, baseline_name, baseline_type, baseline_data, is_current) VALUES (?, ?, ?, ?, 1)",
                    [$_POST['server_id'], $_POST['baseline_name'], $_POST['baseline_type'], $baselineData]
                );

                // Reset compliance status
                $db->execute("UPDATE scm_servers SET compliance_status = 'compliant', drift_count = 0 WHERE id = ?", [$_POST['server_id']]);

                echo json_encode(['success' => true, 'message' => 'Baseline created successfully']);
                break;

            case 'run_task':
                $db->execute(
                    "UPDATE scm_scheduled_tasks SET last_run = NOW(), last_result = 'running' WHERE id = ?",
                    [$_POST['task_id']]
                );
                // Simulate task completion
                sleep(1);
                $result = rand(0, 10) > 2 ? 'success' : 'failure';
                $db->execute(
                    "UPDATE scm_scheduled_tasks SET last_result = ?, next_run = DATE_ADD(NOW(), INTERVAL 1 DAY) WHERE id = ?",
                    [$result, $_POST['task_id']]
                );
                echo json_encode(['success' => true, 'message' => "Task completed with result: {$result}"]);
                break;

            default:
                echo json_encode(['success' => false, 'message' => 'Unknown action']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Fetch data for display
$servers = $db->fetchAll("SELECT * FROM scm_servers WHERE is_active = 1 ORDER BY server_name");
$certificates = $db->fetchAll("SELECT c.*, s.server_name FROM scm_certificates c JOIN scm_servers s ON c.server_id = s.id ORDER BY c.days_remaining");
$configChanges = $db->fetchAll("SELECT cc.*, s.server_name FROM scm_config_changes cc JOIN scm_servers s ON cc.server_id = s.id WHERE cc.is_acknowledged = 0 ORDER BY cc.detected_at DESC LIMIT 20");
$fileChanges = $db->fetchAll("SELECT fi.*, s.server_name FROM scm_file_integrity fi JOIN scm_servers s ON fi.server_id = s.id WHERE fi.change_type != 'none' AND fi.is_acknowledged = 0 ORDER BY fi.severity DESC, fi.change_detected_at DESC LIMIT 20");
$firewallRules = $db->fetchAll("SELECT fr.*, s.server_name FROM scm_firewall_rules fr JOIN scm_servers s ON fr.server_id = s.id ORDER BY s.server_name, fr.rule_name");
$scheduledTasks = $db->fetchAll("SELECT st.*, s.server_name FROM scm_scheduled_tasks st JOIN scm_servers s ON st.server_id = s.id ORDER BY st.last_result = 'failure' DESC, s.server_name");

// Calculate statistics
$totalServers = count($servers);
$compliantServers = count(array_filter($servers, fn($s) => $s['compliance_status'] === 'compliant'));
$driftServers = count(array_filter($servers, fn($s) => $s['compliance_status'] === 'drift'));
$expiringCerts = count(array_filter($certificates, fn($c) => $c['days_remaining'] <= 30));
$criticalCerts = count(array_filter($certificates, fn($c) => $c['days_remaining'] <= 15));
$totalChanges = count($configChanges) + count($fileChanges);
$criticalFileChanges = count(array_filter($fileChanges, fn($f) => $f['severity'] === 'critical'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Configuration Monitor | SCM</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            min-height: 100vh;
            color: #e0e0e0;
            padding: 20px;
        }
        .container { max-width: 1400px; margin: 0 auto; }

        .header {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            padding: 25px 30px;
            border-radius: 15px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .header h1 { color: #00d4ff; font-size: 28px; }
        .header p { color: #888; margin-top: 5px; }
        .back-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }
        .back-btn:hover { transform: translateY(-2px); box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4); }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        .stat-card {
            background: rgba(255, 255, 255, 0.05);
            padding: 20px;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
        }
        .stat-card .icon { font-size: 28px; margin-bottom: 8px; }
        .stat-card .value { font-size: 28px; font-weight: bold; color: #00d4ff; }
        .stat-card .label { color: #888; font-size: 12px; margin-top: 5px; }
        .stat-card.warning .value { color: #ffc107; }
        .stat-card.danger .value { color: #ff4757; }
        .stat-card.success .value { color: #2ecc71; }

        .card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .card h2 {
            color: #00d4ff;
            font-size: 20px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.3s;
            margin: 2px;
        }
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .btn-success { background: #2ecc71; color: white; }
        .btn-warning { background: #ffc107; color: #1a1a2e; }
        .btn-danger { background: #ff4757; color: white; }
        .btn-info { background: #3498db; color: white; }
        .btn-sm { padding: 4px 10px; font-size: 11px; }
        .btn:hover { transform: translateY(-1px); opacity: 0.9; }

        .toolbar {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 10px 12px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 13px;
        }
        th {
            background: rgba(0, 212, 255, 0.1);
            color: #00d4ff;
            font-weight: 600;
            font-size: 11px;
            text-transform: uppercase;
        }
        tr:hover { background: rgba(255, 255, 255, 0.02); }

        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .badge-compliant, .badge-success, .badge-valid { background: rgba(46, 204, 113, 0.2); color: #2ecc71; }
        .badge-drift, .badge-warning, .badge-expiring { background: rgba(255, 193, 7, 0.2); color: #ffc107; }
        .badge-critical, .badge-danger, .badge-expired { background: rgba(255, 71, 87, 0.2); color: #ff4757; }
        .badge-info { background: rgba(52, 152, 219, 0.2); color: #3498db; }
        .badge-unknown { background: rgba(150, 150, 150, 0.2); color: #999; }
        .badge-windows { background: rgba(0, 120, 212, 0.2); color: #0078d4; }
        .badge-linux { background: rgba(255, 193, 7, 0.2); color: #ffc107; }

        .tabs {
            display: flex;
            gap: 5px;
            margin-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            flex-wrap: wrap;
        }
        .tab {
            padding: 10px 20px;
            background: transparent;
            border: none;
            color: #888;
            font-size: 13px;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }
        .tab:hover { color: #00d4ff; }
        .tab.active { color: #00d4ff; border-bottom-color: #00d4ff; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal.active { display: flex; }
        .modal-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(5px);
        }
        .modal-content {
            position: relative;
            background: #1a1a2e;
            border-radius: 15px;
            width: 90%;
            max-width: 550px;
            max-height: 90vh;
            overflow-y: auto;
            border: 1px solid rgba(255, 255, 255, 0.1);
            animation: modalSlide 0.3s ease;
        }
        @keyframes modalSlide {
            from { transform: translateY(-30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .modal-header {
            padding: 20px 25px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px 15px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-header h3 { color: white; margin: 0; }
        .modal-close {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 18px;
        }
        .modal-body { padding: 25px; }
        .modal-footer {
            padding: 15px 25px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .form-group { margin-bottom: 15px; }
        .form-group label {
            display: block;
            margin-bottom: 6px;
            color: #00d4ff;
            font-weight: 500;
            font-size: 13px;
        }
        .form-input {
            width: 100%;
            padding: 10px 12px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            color: white;
            font-size: 13px;
        }
        .form-input:focus { outline: none; border-color: #00d4ff; }
        .form-row { display: flex; gap: 15px; }
        .form-row .form-group { flex: 1; }

        .toast {
            position: fixed;
            bottom: 30px;
            right: 30px;
            padding: 15px 25px;
            border-radius: 10px;
            color: white;
            font-weight: 500;
            z-index: 1001;
            transform: translateY(100px);
            opacity: 0;
            transition: all 0.3s;
        }
        .toast.show { transform: translateY(0); opacity: 1; }
        .toast.success { background: #2ecc71; }
        .toast.error { background: #ff4757; }
        .toast.info { background: #3498db; }

        .change-item {
            padding: 12px 15px;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 8px;
            margin-bottom: 10px;
            border-left: 4px solid;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .change-item.critical { border-color: #ff4757; }
        .change-item.high { border-color: #e74c3c; }
        .change-item.medium { border-color: #ffc107; }
        .change-item.low, .change-item.info { border-color: #3498db; }

        .chart-container {
            position: relative;
            height: 250px;
            margin: 20px 0;
        }
        .chart-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1>Server Configuration Monitor</h1>
                <p>Configuration tracking, drift detection, and compliance monitoring</p>
            </div>
            <a href="../index.php" class="back-btn">Back to Dashboard</a>
        </div>

        <div class="stats-grid">
            <div class="stat-card success">
                <div class="icon">🖥️</div>
                <div class="value"><?= $totalServers ?></div>
                <div class="label">Monitored Servers</div>
            </div>
            <div class="stat-card <?= $compliantServers === $totalServers ? 'success' : 'warning' ?>">
                <div class="icon">✓</div>
                <div class="value"><?= $compliantServers ?></div>
                <div class="label">Compliant</div>
            </div>
            <div class="stat-card <?= $driftServers > 0 ? 'danger' : 'success' ?>">
                <div class="icon">⚠️</div>
                <div class="value"><?= $driftServers ?></div>
                <div class="label">Configuration Drift</div>
            </div>
            <div class="stat-card <?= $criticalCerts > 0 ? 'danger' : ($expiringCerts > 0 ? 'warning' : 'success') ?>">
                <div class="icon">🔐</div>
                <div class="value"><?= $expiringCerts ?></div>
                <div class="label">Certs Expiring Soon</div>
            </div>
            <div class="stat-card <?= $criticalFileChanges > 0 ? 'danger' : 'success' ?>">
                <div class="icon">📄</div>
                <div class="value"><?= count($fileChanges) ?></div>
                <div class="label">File Changes</div>
            </div>
            <div class="stat-card <?= $totalChanges > 10 ? 'warning' : 'success' ?>">
                <div class="icon">🔄</div>
                <div class="value"><?= $totalChanges ?></div>
                <div class="label">Pending Changes</div>
            </div>
        </div>

        <div class="card">
            <h2>Configuration Management</h2>

            <div class="tabs">
                <button class="tab active" onclick="switchTab('servers')">Servers</button>
                <button class="tab" onclick="switchTab('changes')">Config Changes (<?= count($configChanges) ?>)</button>
                <button class="tab" onclick="switchTab('fim')">File Integrity (<?= count($fileChanges) ?>)</button>
                <button class="tab" onclick="switchTab('certs')">Certificates (<?= $expiringCerts ?>)</button>
                <button class="tab" onclick="switchTab('firewall')">Firewall Rules</button>
                <button class="tab" onclick="switchTab('tasks')">Scheduled Tasks</button>
            </div>

            <!-- Servers Tab -->
            <div id="servers-tab" class="tab-content active">
                <div class="toolbar">
                    <button class="btn btn-primary" onclick="openAddServerModal()">+ Add Server</button>
                    <button class="btn btn-info" onclick="scanAllServers()">Scan All Servers</button>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>Server Name</th>
                            <th>IP Address</th>
                            <th>OS</th>
                            <th>Type</th>
                            <th>Role</th>
                            <th>Services</th>
                            <th>Packages</th>
                            <th>Status</th>
                            <th>Last Scan</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($servers as $server): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($server['server_name']) ?></strong></td>
                            <td><?= htmlspecialchars($server['ip_address']) ?></td>
                            <td>
                                <span class="badge badge-<?= $server['os_type'] ?>">
                                    <?= strtoupper($server['os_type']) ?>
                                </span>
                                <br><small style="color: #666;"><?= htmlspecialchars($server['os_version'] ?? '') ?></small>
                            </td>
                            <td><?= ucfirst($server['server_type']) ?></td>
                            <td><?= htmlspecialchars($server['role'] ?? '-') ?></td>
                            <td><?= $server['services_count'] ?></td>
                            <td><?= $server['packages_count'] ?></td>
                            <td>
                                <?php if ($server['compliance_status'] === 'compliant'): ?>
                                    <span class="badge badge-compliant">Compliant</span>
                                <?php elseif ($server['compliance_status'] === 'drift'): ?>
                                    <span class="badge badge-drift">Drift (<?= $server['drift_count'] ?>)</span>
                                <?php else: ?>
                                    <span class="badge badge-unknown">Unknown</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $server['last_scan'] ? date('m/d H:i', strtotime($server['last_scan'])) : 'Never' ?></td>
                            <td>
                                <button class="btn btn-info btn-sm" onclick="scanServer(<?= $server['id'] ?>)">Scan</button>
                                <button class="btn btn-primary btn-sm" onclick="viewServerDetails(<?= $server['id'] ?>)">Details</button>
                                <button class="btn btn-warning btn-sm" onclick="createBaseline(<?= $server['id'] ?>, '<?= htmlspecialchars($server['server_name'], ENT_QUOTES) ?>')">Baseline</button>
                                <button class="btn btn-danger btn-sm" onclick="deleteServer(<?= $server['id'] ?>, '<?= htmlspecialchars($server['server_name'], ENT_QUOTES) ?>')">Delete</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Config Changes Tab -->
            <div id="changes-tab" class="tab-content">
                <h3 style="margin-bottom: 15px; color: #888;">Unacknowledged Configuration Changes</h3>
                <?php if (empty($configChanges)): ?>
                    <p style="text-align: center; color: #666; padding: 40px;">No pending configuration changes</p>
                <?php else: ?>
                    <?php foreach ($configChanges as $change): ?>
                    <div class="change-item <?= $change['severity'] ?>">
                        <div>
                            <strong style="color: white;"><?= htmlspecialchars($change['server_name']) ?></strong>
                            <span class="badge badge-info" style="margin-left: 10px;"><?= $change['change_type'] ?></span>
                            <span class="badge badge-<?= $change['severity'] ?>" style="margin-left: 5px;"><?= $change['severity'] ?></span>
                            <p style="margin-top: 5px; color: #ccc;"><?= htmlspecialchars($change['item_name']) ?></p>
                            <?php if ($change['old_value'] || $change['new_value']): ?>
                            <p style="margin-top: 3px; font-size: 11px; color: #888;">
                                <?= $change['old_value'] ? "Old: " . htmlspecialchars(substr($change['old_value'], 0, 50)) : '' ?>
                                <?= $change['new_value'] ? " → New: " . htmlspecialchars(substr($change['new_value'], 0, 50)) : '' ?>
                            </p>
                            <?php endif; ?>
                            <small style="color: #666;"><?= date('M d, H:i', strtotime($change['detected_at'])) ?></small>
                        </div>
                        <button class="btn btn-success btn-sm" onclick="acknowledgeChange(<?= $change['id'] ?>)">Acknowledge</button>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- File Integrity Tab -->
            <div id="fim-tab" class="tab-content">
                <h3 style="margin-bottom: 15px; color: #888;">File Integrity Monitoring (FIM) Alerts</h3>
                <?php if (empty($fileChanges)): ?>
                    <p style="text-align: center; color: #666; padding: 40px;">No file integrity changes detected</p>
                <?php else: ?>
                    <?php foreach ($fileChanges as $file): ?>
                    <div class="change-item <?= $file['severity'] ?>">
                        <div>
                            <strong style="color: white;"><?= htmlspecialchars($file['server_name']) ?></strong>
                            <span class="badge badge-<?= $file['severity'] ?>" style="margin-left: 10px;"><?= $file['severity'] ?></span>
                            <span class="badge badge-info" style="margin-left: 5px;"><?= $file['change_type'] ?></span>
                            <p style="margin-top: 5px; font-family: monospace; font-size: 12px; color: #ccc;"><?= htmlspecialchars($file['file_path']) ?></p>
                            <p style="margin-top: 3px; font-size: 11px; color: #888;">
                                Owner: <?= htmlspecialchars($file['owner']) ?> | Permissions: <?= htmlspecialchars($file['file_permissions']) ?>
                            </p>
                            <small style="color: #666;"><?= $file['change_detected_at'] ? date('M d, H:i', strtotime($file['change_detected_at'])) : '-' ?></small>
                        </div>
                        <button class="btn btn-success btn-sm" onclick="acknowledgeFileChange(<?= $file['id'] ?>)">Acknowledge</button>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Certificates Tab -->
            <div id="certs-tab" class="tab-content">
                <div class="toolbar">
                    <button class="btn btn-primary" onclick="openAddCertModal()">+ Add Certificate</button>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>Server</th>
                            <th>Certificate Name</th>
                            <th>Subject</th>
                            <th>Issuer</th>
                            <th>Type</th>
                            <th>Expiry Date</th>
                            <th>Days Left</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($certificates as $cert): ?>
                        <tr>
                            <td><?= htmlspecialchars($cert['server_name']) ?></td>
                            <td><strong><?= htmlspecialchars($cert['cert_name']) ?></strong></td>
                            <td style="font-size: 11px; max-width: 150px; overflow: hidden; text-overflow: ellipsis;">
                                <?= htmlspecialchars($cert['subject']) ?>
                            </td>
                            <td><?= htmlspecialchars($cert['issuer']) ?></td>
                            <td><?= strtoupper($cert['cert_type']) ?></td>
                            <td><?= $cert['expiry_date'] ?></td>
                            <td>
                                <span style="color: <?= $cert['days_remaining'] <= 15 ? '#ff4757' : ($cert['days_remaining'] <= 30 ? '#ffc107' : '#2ecc71') ?>; font-weight: bold;">
                                    <?= $cert['days_remaining'] ?> days
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-<?= $cert['status'] ?>">
                                    <?= strtoupper($cert['status']) ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-danger btn-sm" onclick="deleteCertificate(<?= $cert['id'] ?>)">Delete</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Firewall Rules Tab -->
            <div id="firewall-tab" class="tab-content">
                <div class="toolbar">
                    <button class="btn btn-primary" onclick="openAddFirewallModal()">+ Add Rule</button>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>Server</th>
                            <th>Rule Name</th>
                            <th>Direction</th>
                            <th>Protocol</th>
                            <th>Port</th>
                            <th>Source</th>
                            <th>Destination</th>
                            <th>Action</th>
                            <th>Enabled</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($firewallRules as $rule): ?>
                        <tr>
                            <td><?= htmlspecialchars($rule['server_name']) ?></td>
                            <td><strong><?= htmlspecialchars($rule['rule_name']) ?></strong></td>
                            <td>
                                <span class="badge badge-<?= $rule['direction'] === 'inbound' ? 'info' : 'warning' ?>">
                                    <?= strtoupper($rule['direction']) ?>
                                </span>
                            </td>
                            <td><?= strtoupper($rule['protocol']) ?></td>
                            <td><?= $rule['port'] ?: 'Any' ?></td>
                            <td style="font-size: 11px;"><?= htmlspecialchars($rule['source_ip']) ?></td>
                            <td style="font-size: 11px;"><?= htmlspecialchars($rule['destination_ip']) ?></td>
                            <td>
                                <span class="badge badge-<?= $rule['action'] === 'allow' ? 'success' : 'danger' ?>">
                                    <?= strtoupper($rule['action']) ?>
                                </span>
                            </td>
                            <td>
                                <span style="color: <?= $rule['is_enabled'] ? '#2ecc71' : '#ff4757' ?>;">
                                    <?= $rule['is_enabled'] ? 'Yes' : 'No' ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-<?= $rule['is_enabled'] ? 'warning' : 'success' ?> btn-sm" onclick="toggleFirewallRule(<?= $rule['id'] ?>)">
                                    <?= $rule['is_enabled'] ? 'Disable' : 'Enable' ?>
                                </button>
                                <button class="btn btn-danger btn-sm" onclick="deleteFirewallRule(<?= $rule['id'] ?>)">Delete</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Scheduled Tasks Tab -->
            <div id="tasks-tab" class="tab-content">
                <table>
                    <thead>
                        <tr>
                            <th>Server</th>
                            <th>Task Name</th>
                            <th>Type</th>
                            <th>Schedule</th>
                            <th>Run As</th>
                            <th>Last Run</th>
                            <th>Last Result</th>
                            <th>Next Run</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($scheduledTasks as $task): ?>
                        <tr>
                            <td><?= htmlspecialchars($task['server_name']) ?></td>
                            <td><strong><?= htmlspecialchars($task['task_name']) ?></strong></td>
                            <td><span class="badge badge-info"><?= $task['task_type'] ?></span></td>
                            <td style="font-size: 11px;"><?= htmlspecialchars($task['schedule']) ?></td>
                            <td><?= htmlspecialchars($task['run_as_user']) ?></td>
                            <td><?= $task['last_run'] ? date('m/d H:i', strtotime($task['last_run'])) : 'Never' ?></td>
                            <td>
                                <?php if ($task['last_result'] === 'success'): ?>
                                    <span class="badge badge-success">Success</span>
                                <?php elseif ($task['last_result'] === 'failure'): ?>
                                    <span class="badge badge-danger">Failed</span>
                                <?php elseif ($task['last_result'] === 'running'): ?>
                                    <span class="badge badge-info">Running</span>
                                <?php else: ?>
                                    <span class="badge badge-unknown">Unknown</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $task['next_run'] ? date('m/d H:i', strtotime($task['next_run'])) : '-' ?></td>
                            <td>
                                <button class="btn btn-info btn-sm" onclick="runTask(<?= $task['id'] ?>)">Run Now</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Server Modal -->
    <div id="serverModal" class="modal">
        <div class="modal-overlay" onclick="closeModal('serverModal')"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add Server</h3>
                <button class="modal-close" onclick="closeModal('serverModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label>Server Name</label>
                        <input type="text" id="serverName" class="form-input" placeholder="e.g., WEB-SERVER-01">
                    </div>
                    <div class="form-group">
                        <label>IP Address</label>
                        <input type="text" id="serverIp" class="form-input" placeholder="192.168.1.100">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>OS Type</label>
                        <select id="osType" class="form-input">
                            <option value="windows">Windows</option>
                            <option value="linux">Linux</option>
                            <option value="unix">Unix</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Server Type</label>
                        <select id="serverType" class="form-input">
                            <option value="physical">Physical</option>
                            <option value="virtual">Virtual</option>
                            <option value="cloud">Cloud</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>OS Version</label>
                        <input type="text" id="osVersion" class="form-input" placeholder="e.g., Windows Server 2022">
                    </div>
                    <div class="form-group">
                        <label>Kernel (Linux only)</label>
                        <input type="text" id="kernelVersion" class="form-input" placeholder="e.g., 5.15.0-97">
                    </div>
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <input type="text" id="serverRole" class="form-input" placeholder="e.g., Web Server, Database, Domain Controller">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn" style="background: #666;" onclick="closeModal('serverModal')">Cancel</button>
                <button class="btn btn-primary" onclick="saveServer()">Add Server</button>
            </div>
        </div>
    </div>

    <!-- Add Certificate Modal -->
    <div id="certModal" class="modal">
        <div class="modal-overlay" onclick="closeModal('certModal')"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add Certificate</h3>
                <button class="modal-close" onclick="closeModal('certModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Server</label>
                    <select id="certServerId" class="form-input">
                        <?php foreach ($servers as $s): ?>
                        <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['server_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Certificate Name</label>
                        <input type="text" id="certName" class="form-input" placeholder="e.g., SSL Certificate">
                    </div>
                    <div class="form-group">
                        <label>Type</label>
                        <select id="certType" class="form-input">
                            <option value="ssl">SSL/TLS</option>
                            <option value="computer">Computer</option>
                            <option value="service">Service</option>
                            <option value="user">User</option>
                            <option value="ca">CA</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Subject</label>
                    <input type="text" id="certSubject" class="form-input" placeholder="CN=www.example.com">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Issuer</label>
                        <input type="text" id="certIssuer" class="form-input" placeholder="e.g., DigiCert">
                    </div>
                    <div class="form-group">
                        <label>Expiry Date</label>
                        <input type="date" id="certExpiry" class="form-input">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn" style="background: #666;" onclick="closeModal('certModal')">Cancel</button>
                <button class="btn btn-primary" onclick="saveCertificate()">Add Certificate</button>
            </div>
        </div>
    </div>

    <!-- Add Firewall Rule Modal -->
    <div id="firewallModal" class="modal">
        <div class="modal-overlay" onclick="closeModal('firewallModal')"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add Firewall Rule</h3>
                <button class="modal-close" onclick="closeModal('firewallModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Server</label>
                    <select id="fwServerId" class="form-input">
                        <?php foreach ($servers as $s): ?>
                        <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['server_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Rule Name</label>
                    <input type="text" id="fwRuleName" class="form-input" placeholder="e.g., Allow HTTPS">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Direction</label>
                        <select id="fwDirection" class="form-input">
                            <option value="inbound">Inbound</option>
                            <option value="outbound">Outbound</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Protocol</label>
                        <select id="fwProtocol" class="form-input">
                            <option value="tcp">TCP</option>
                            <option value="udp">UDP</option>
                            <option value="icmp">ICMP</option>
                            <option value="any">Any</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Port</label>
                        <input type="text" id="fwPort" class="form-input" placeholder="e.g., 443 or 80,443">
                    </div>
                    <div class="form-group">
                        <label>Action</label>
                        <select id="fwAction" class="form-input">
                            <option value="allow">Allow</option>
                            <option value="deny">Deny</option>
                            <option value="drop">Drop</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Source IP</label>
                        <input type="text" id="fwSourceIp" class="form-input" placeholder="any or 10.0.0.0/24">
                    </div>
                    <div class="form-group">
                        <label>Destination IP</label>
                        <input type="text" id="fwDestIp" class="form-input" placeholder="any or specific IP">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn" style="background: #666;" onclick="closeModal('firewallModal')">Cancel</button>
                <button class="btn btn-primary" onclick="saveFirewallRule()">Add Rule</button>
            </div>
        </div>
    </div>

    <!-- Baseline Modal -->
    <div id="baselineModal" class="modal">
        <div class="modal-overlay" onclick="closeModal('baselineModal')"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h3>Create Configuration Baseline</h3>
                <button class="modal-close" onclick="closeModal('baselineModal')">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="baselineServerId">
                <p style="margin-bottom: 15px; color: #888;">Server: <strong id="baselineServerName" style="color: white;"></strong></p>
                <div class="form-group">
                    <label>Baseline Name</label>
                    <input type="text" id="baselineName" class="form-input" placeholder="e.g., Production Baseline Q1 2025">
                </div>
                <div class="form-group">
                    <label>Baseline Type</label>
                    <select id="baselineType" class="form-input">
                        <option value="services">Services</option>
                        <option value="registry">Registry Keys (Windows)</option>
                        <option value="config_files">Configuration Files</option>
                        <option value="packages">Installed Packages</option>
                        <option value="users">Users & Groups</option>
                        <option value="firewall">Firewall Rules</option>
                    </select>
                </div>
                <p style="font-size: 12px; color: #ffc107; margin-top: 15px;">
                    Creating a new baseline will capture the current configuration state and mark the server as compliant.
                </p>
            </div>
            <div class="modal-footer">
                <button class="btn" style="background: #666;" onclick="closeModal('baselineModal')">Cancel</button>
                <button class="btn btn-primary" onclick="saveBaseline()">Create Baseline</button>
            </div>
        </div>
    </div>

    <div id="toast" class="toast"></div>

    <script>
        function apiCall(data) {
            var body = new URLSearchParams();
            Object.keys(data).forEach(function(key) {
                body.append(key, data[key]);
            });
            return fetch(window.location.href, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body
            })
            .then(function(r) { return r.text(); })
            .then(function(text) {
                try { return JSON.parse(text); }
                catch (e) { return { success: false, message: 'Invalid server response' }; }
            });
        }

        function showToast(message, type) {
            type = type || 'success';
            var toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = 'toast ' + type + ' show';
            setTimeout(function() { toast.classList.remove('show'); }, 4000);
        }

        function openModal(id) { document.getElementById(id).classList.add('active'); }
        function closeModal(id) { document.getElementById(id).classList.remove('active'); }

        function switchTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(function(c) { c.classList.remove('active'); });
            document.querySelectorAll('.tab').forEach(function(t) { t.classList.remove('active'); });
            document.getElementById(tabName + '-tab').classList.add('active');
            event.target.classList.add('active');
        }

        function openAddServerModal() { openModal('serverModal'); }
        function openAddCertModal() { openModal('certModal'); }
        function openAddFirewallModal() { openModal('firewallModal'); }

        function saveServer() {
            apiCall({
                action: 'add_server',
                server_name: document.getElementById('serverName').value,
                ip_address: document.getElementById('serverIp').value,
                os_type: document.getElementById('osType').value,
                os_version: document.getElementById('osVersion').value,
                kernel_version: document.getElementById('kernelVersion').value,
                server_type: document.getElementById('serverType').value,
                role: document.getElementById('serverRole').value
            }).then(function(result) {
                if (result.success) {
                    showToast(result.message);
                    closeModal('serverModal');
                    setTimeout(function() { location.reload(); }, 1000);
                } else {
                    showToast(result.message, 'error');
                }
            });
        }

        function deleteServer(id, name) {
            if (confirm('Delete server "' + name + '"? All configuration data will be lost.')) {
                apiCall({ action: 'delete_server', server_id: id }).then(function(result) {
                    if (result.success) {
                        showToast(result.message);
                        setTimeout(function() { location.reload(); }, 1000);
                    } else {
                        showToast(result.message, 'error');
                    }
                });
            }
        }

        function scanServer(id) {
            showToast('Scanning server configuration...', 'info');
            apiCall({ action: 'scan_server', server_id: id }).then(function(result) {
                if (result.success) {
                    showToast(result.message);
                    setTimeout(function() { location.reload(); }, 1500);
                } else {
                    showToast(result.message, 'error');
                }
            });
        }

        function scanAllServers() {
            showToast('Scanning all servers...', 'info');
            <?php foreach ($servers as $s): ?>
            apiCall({ action: 'scan_server', server_id: <?= $s['id'] ?> });
            <?php endforeach; ?>
            setTimeout(function() { location.reload(); }, 3000);
        }

        function viewServerDetails(id) {
            showToast('Loading server details...', 'info');
            // For now, just show toast - could expand to detailed modal
        }

        function createBaseline(id, name) {
            document.getElementById('baselineServerId').value = id;
            document.getElementById('baselineServerName').textContent = name;
            document.getElementById('baselineName').value = 'Baseline ' + new Date().toISOString().split('T')[0];
            openModal('baselineModal');
        }

        function saveBaseline() {
            apiCall({
                action: 'create_baseline',
                server_id: document.getElementById('baselineServerId').value,
                baseline_name: document.getElementById('baselineName').value,
                baseline_type: document.getElementById('baselineType').value
            }).then(function(result) {
                if (result.success) {
                    showToast(result.message);
                    closeModal('baselineModal');
                    setTimeout(function() { location.reload(); }, 1000);
                } else {
                    showToast(result.message, 'error');
                }
            });
        }

        function acknowledgeChange(id) {
            apiCall({ action: 'acknowledge_change', change_id: id }).then(function(result) {
                if (result.success) {
                    showToast(result.message);
                    setTimeout(function() { location.reload(); }, 1000);
                } else {
                    showToast(result.message, 'error');
                }
            });
        }

        function acknowledgeFileChange(id) {
            apiCall({ action: 'acknowledge_file_change', file_id: id }).then(function(result) {
                if (result.success) {
                    showToast(result.message);
                    setTimeout(function() { location.reload(); }, 1000);
                } else {
                    showToast(result.message, 'error');
                }
            });
        }

        function saveCertificate() {
            apiCall({
                action: 'add_certificate',
                server_id: document.getElementById('certServerId').value,
                cert_name: document.getElementById('certName').value,
                subject: document.getElementById('certSubject').value,
                issuer: document.getElementById('certIssuer').value,
                cert_type: document.getElementById('certType').value,
                expiry_date: document.getElementById('certExpiry').value
            }).then(function(result) {
                if (result.success) {
                    showToast(result.message);
                    closeModal('certModal');
                    setTimeout(function() { location.reload(); }, 1000);
                } else {
                    showToast(result.message, 'error');
                }
            });
        }

        function deleteCertificate(id) {
            if (confirm('Delete this certificate record?')) {
                apiCall({ action: 'delete_certificate', cert_id: id }).then(function(result) {
                    if (result.success) {
                        showToast(result.message);
                        setTimeout(function() { location.reload(); }, 1000);
                    } else {
                        showToast(result.message, 'error');
                    }
                });
            }
        }

        function saveFirewallRule() {
            apiCall({
                action: 'add_firewall_rule',
                server_id: document.getElementById('fwServerId').value,
                rule_name: document.getElementById('fwRuleName').value,
                direction: document.getElementById('fwDirection').value,
                protocol: document.getElementById('fwProtocol').value,
                port: document.getElementById('fwPort').value,
                source_ip: document.getElementById('fwSourceIp').value,
                destination_ip: document.getElementById('fwDestIp').value,
                rule_action: document.getElementById('fwAction').value,
                is_enabled: '1'
            }).then(function(result) {
                if (result.success) {
                    showToast(result.message);
                    closeModal('firewallModal');
                    setTimeout(function() { location.reload(); }, 1000);
                } else {
                    showToast(result.message, 'error');
                }
            });
        }

        function toggleFirewallRule(id) {
            apiCall({ action: 'toggle_firewall_rule', rule_id: id }).then(function(result) {
                if (result.success) {
                    showToast(result.message);
                    setTimeout(function() { location.reload(); }, 1000);
                } else {
                    showToast(result.message, 'error');
                }
            });
        }

        function deleteFirewallRule(id) {
            if (confirm('Delete this firewall rule?')) {
                apiCall({ action: 'delete_firewall_rule', rule_id: id }).then(function(result) {
                    if (result.success) {
                        showToast(result.message);
                        setTimeout(function() { location.reload(); }, 1000);
                    } else {
                        showToast(result.message, 'error');
                    }
                });
            }
        }

        function runTask(id) {
            showToast('Running task...', 'info');
            apiCall({ action: 'run_task', task_id: id }).then(function(result) {
                if (result.success) {
                    showToast(result.message);
                    setTimeout(function() { location.reload(); }, 1500);
                } else {
                    showToast(result.message, 'error');
                }
            });
        }
    </script>
</body>
</html>
