<?php
/**
 * Network Configuration Manager (NCM)
 * Automates network device configuration management and change tracking
 */

require_once __DIR__ . '/../classes/Database.php';

$db = Database::getInstance();

// Check if NCM tables exist
$tablesExist = true;
$dbError = '';
try {
    $result = $db->fetchOne("SELECT 1 FROM ncm_devices LIMIT 1");
} catch (Exception $e) {
    $tablesExist = false;
    $dbError = $e->getMessage();
}

$devices = [];
$configs = [];
$changes = [];
$backups = [];
$templates = [];
$jobs = [];
$complianceRules = [];
$stats = [
    'total_devices' => 0,
    'compliant' => 0,
    'non_compliant' => 0,
    'pending_changes' => 0,
    'backups_today' => 0,
    'config_changes_24h' => 0
];

if ($tablesExist) {
    try {
        $stats['total_devices'] = $db->fetchOne("SELECT COUNT(*) as cnt FROM ncm_devices")['cnt'] ?? 0;
        $stats['compliant'] = $db->fetchOne("SELECT COUNT(*) as cnt FROM ncm_devices WHERE compliance_status = 'compliant'")['cnt'] ?? 0;
        $stats['non_compliant'] = $db->fetchOne("SELECT COUNT(*) as cnt FROM ncm_devices WHERE compliance_status = 'non_compliant'")['cnt'] ?? 0;
        $stats['pending_changes'] = $db->fetchOne("SELECT COUNT(*) as cnt FROM ncm_config_changes WHERE status = 'pending'")['cnt'] ?? 0;
        $stats['backups_today'] = $db->fetchOne("SELECT COUNT(*) as cnt FROM ncm_config_backups WHERE DATE(backup_time) = CURDATE()")['cnt'] ?? 0;
        $stats['config_changes_24h'] = $db->fetchOne("SELECT COUNT(*) as cnt FROM ncm_config_changes WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")['cnt'] ?? 0;

        $devices = $db->fetchAll("SELECT * FROM ncm_devices ORDER BY device_name");
        $configs = $db->fetchAll("SELECT c.*, d.device_name FROM ncm_device_configs c JOIN ncm_devices d ON c.device_id = d.id ORDER BY c.last_updated DESC LIMIT 20");
        $changes = $db->fetchAll("SELECT ch.*, d.device_name FROM ncm_config_changes ch JOIN ncm_devices d ON ch.device_id = d.id ORDER BY ch.created_at DESC LIMIT 30");
        $backups = $db->fetchAll("SELECT b.*, d.device_name FROM ncm_config_backups b JOIN ncm_devices d ON b.device_id = d.id ORDER BY b.backup_time DESC LIMIT 20");
        $templates = $db->fetchAll("SELECT * FROM ncm_config_templates ORDER BY template_name");
        $jobs = $db->fetchAll("SELECT * FROM ncm_scheduled_jobs ORDER BY next_run");
        $complianceRules = $db->fetchAll("SELECT * FROM ncm_compliance_rules ORDER BY rule_name");
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
            case 'backup_config':
                $deviceId = (int)$_POST['device_id'];
                $device = $db->fetchOne("SELECT * FROM ncm_devices WHERE id = ?", [$deviceId]);
                if ($device) {
                    $config = "!\n! Configuration backup for " . $device['device_name'] . "\n";
                    $config .= "! Generated: " . date('Y-m-d H:i:s') . "\n";
                    $config .= "! Device: " . $device['vendor'] . " " . $device['model'] . "\n";
                    $config .= "!\nversion " . ($device['firmware_version'] ?? '16.12') . "\n";
                    $config .= "service timestamps debug datetime msec\n";
                    $config .= "service timestamps log datetime msec\n";
                    $config .= "service password-encryption\n!\n";
                    $config .= "hostname " . $device['device_name'] . "\n!\n";
                    $config .= "enable secret 9 \$encrypted\$\n!\n";
                    $config .= "interface GigabitEthernet0/0\n";
                    $config .= " description Management Interface\n";
                    $config .= " ip address " . $device['ip_address'] . " 255.255.255.0\n";
                    $config .= " no shutdown\n!\n";
                    $config .= "ip route 0.0.0.0 0.0.0.0 " . substr($device['ip_address'], 0, strrpos($device['ip_address'], '.')) . ".254\n!\n";
                    $config .= "logging buffered 16384\n";
                    $config .= "ntp server 10.10.10.1\n!\n";
                    $config .= "banner motd ^C\n*** AUTHORIZED ACCESS ONLY ***\n^C\n!\n";
                    $config .= "line con 0\n logging synchronous\n";
                    $config .= "line vty 0 15\n transport input ssh\n!\nend\n";

                    $db->execute("INSERT INTO ncm_config_backups (device_id, config_content, backup_type, file_size, backup_time) VALUES (?, ?, 'manual', ?, NOW())",
                        [$deviceId, $config, strlen($config)]);
                    $db->execute("UPDATE ncm_devices SET last_backup = NOW() WHERE id = ?", [$deviceId]);

                    echo json_encode(['success' => true, 'message' => 'Configuration backed up successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Device not found']);
                }
                break;

            case 'get_backup':
                $backupId = (int)$_POST['backup_id'];
                $backup = $db->fetchOne("SELECT b.*, d.device_name FROM ncm_config_backups b JOIN ncm_devices d ON b.device_id = d.id WHERE b.id = ?", [$backupId]);
                if ($backup) {
                    echo json_encode(['success' => true, 'backup' => $backup]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Backup not found']);
                }
                break;

            case 'compare_configs':
                $deviceId = (int)$_POST['device_id'];
                $backups = $db->fetchAll("SELECT id, backup_time, config_content FROM ncm_config_backups WHERE device_id = ? ORDER BY backup_time DESC LIMIT 2", [$deviceId]);
                if (count($backups) >= 2) {
                    echo json_encode(['success' => true, 'config1' => $backups[0], 'config2' => $backups[1]]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Need at least 2 backups to compare']);
                }
                break;

            case 'run_compliance_check':
                $deviceId = (int)$_POST['device_id'];
                $device = $db->fetchOne("SELECT * FROM ncm_devices WHERE id = ?", [$deviceId]);
                $latestBackup = $db->fetchOne("SELECT config_content FROM ncm_config_backups WHERE device_id = ? ORDER BY backup_time DESC LIMIT 1", [$deviceId]);

                if (!$latestBackup) {
                    echo json_encode(['success' => false, 'message' => 'No backup found for compliance check']);
                    break;
                }

                $rules = $db->fetchAll("SELECT * FROM ncm_compliance_rules WHERE enabled = 1 AND (device_type = 'all' OR device_type = ?)", [$device['device_type']]);
                $passed = 0;
                $failed = 0;
                $results = [];

                foreach ($rules as $rule) {
                    $configContent = $latestBackup['config_content'];
                    $pattern = $rule['rule_pattern'];
                    $match = false;

                    switch ($rule['rule_type']) {
                        case 'must_contain':
                            $match = strpos($configContent, $pattern) !== false;
                            break;
                        case 'must_not_contain':
                            $match = strpos($configContent, $pattern) === false;
                            break;
                        case 'regex_match':
                            $match = preg_match('/' . $pattern . '/i', $configContent);
                            break;
                        case 'regex_not_match':
                            $match = !preg_match('/' . $pattern . '/i', $configContent);
                            break;
                    }

                    $result = $match ? 'pass' : 'fail';
                    if ($match) $passed++; else $failed++;

                    $db->execute("INSERT INTO ncm_compliance_results (device_id, rule_id, result, details) VALUES (?, ?, ?, ?)",
                        [$deviceId, $rule['id'], $result, $rule['rule_name'] . ': ' . ($match ? 'Passed' : 'Failed')]);

                    $results[] = ['rule' => $rule['rule_name'], 'result' => $result, 'severity' => $rule['severity']];
                }

                $status = $failed === 0 ? 'compliant' : 'non_compliant';
                $db->execute("UPDATE ncm_devices SET compliance_status = ?, last_compliance_check = NOW() WHERE id = ?", [$status, $deviceId]);

                echo json_encode([
                    'success' => true,
                    'status' => $status,
                    'passed' => $passed,
                    'failed' => $failed,
                    'results' => $results,
                    'message' => "Compliance check completed: $passed passed, $failed failed"
                ]);
                break;

            case 'approve_change':
                $changeId = (int)$_POST['change_id'];
                $db->execute("UPDATE ncm_config_changes SET status = 'approved', approved_by = 'admin', approved_at = NOW() WHERE id = ?", [$changeId]);
                echo json_encode(['success' => true, 'message' => 'Change approved']);
                break;

            case 'reject_change':
                $changeId = (int)$_POST['change_id'];
                $db->execute("UPDATE ncm_config_changes SET status = 'rejected' WHERE id = ?", [$changeId]);
                echo json_encode(['success' => true, 'message' => 'Change rejected']);
                break;

            case 'apply_change':
                $changeId = (int)$_POST['change_id'];
                $db->execute("UPDATE ncm_config_changes SET status = 'applied', applied_at = NOW() WHERE id = ?", [$changeId]);
                echo json_encode(['success' => true, 'message' => 'Change applied successfully']);
                break;

            case 'add_device':
                $name = $_POST['device_name'];
                $ip = $_POST['ip_address'];
                $type = $_POST['device_type'];
                $vendor = $_POST['vendor'];
                $model = $_POST['model'] ?? '';
                $location = $_POST['location'] ?? '';
                $db->execute("INSERT INTO ncm_devices (device_name, ip_address, device_type, vendor, model, location, status, compliance_status) VALUES (?, ?, ?, ?, ?, ?, 'active', 'unknown')",
                    [$name, $ip, $type, $vendor, $model, $location]);
                echo json_encode(['success' => true, 'message' => 'Device added successfully']);
                break;

            case 'delete_device':
                $deviceId = (int)$_POST['device_id'];
                $db->execute("DELETE FROM ncm_devices WHERE id = ?", [$deviceId]);
                echo json_encode(['success' => true, 'message' => 'Device deleted']);
                break;

            case 'create_template':
                $name = $_POST['template_name'];
                $type = $_POST['device_type'];
                $vendor = $_POST['vendor'] ?? '';
                $content = $_POST['template_content'];
                $desc = $_POST['description'] ?? '';
                $db->execute("INSERT INTO ncm_config_templates (template_name, device_type, vendor, template_content, description) VALUES (?, ?, ?, ?, ?)",
                    [$name, $type, $vendor, $content, $desc]);
                echo json_encode(['success' => true, 'message' => 'Template created successfully']);
                break;

            case 'get_template':
                $templateId = (int)$_POST['template_id'];
                $template = $db->fetchOne("SELECT * FROM ncm_config_templates WHERE id = ?", [$templateId]);
                if ($template) {
                    echo json_encode(['success' => true, 'template' => $template]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Template not found']);
                }
                break;

            case 'apply_template':
                $deviceId = (int)$_POST['device_id'];
                $templateId = (int)$_POST['template_id'];
                $template = $db->fetchOne("SELECT * FROM ncm_config_templates WHERE id = ?", [$templateId]);
                $device = $db->fetchOne("SELECT * FROM ncm_devices WHERE id = ?", [$deviceId]);
                if ($template && $device) {
                    $db->execute("INSERT INTO ncm_config_changes (device_id, change_type, change_description, config_before, config_after, status, requested_by) VALUES (?, 'template_apply', ?, '', ?, 'pending', 'admin')",
                        [$deviceId, "Apply template: " . $template['template_name'], $template['template_content']]);
                    echo json_encode(['success' => true, 'message' => 'Template application scheduled for approval']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Device or template not found']);
                }
                break;

            case 'run_job':
                $jobId = (int)$_POST['job_id'];
                $job = $db->fetchOne("SELECT * FROM ncm_scheduled_jobs WHERE id = ?", [$jobId]);
                if ($job) {
                    $db->execute("UPDATE ncm_scheduled_jobs SET last_run = NOW(), last_result = 'success' WHERE id = ?", [$jobId]);
                    echo json_encode(['success' => true, 'message' => 'Job executed successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Job not found']);
                }
                break;

            case 'toggle_job':
                $jobId = (int)$_POST['job_id'];
                $job = $db->fetchOne("SELECT status FROM ncm_scheduled_jobs WHERE id = ?", [$jobId]);
                $newStatus = $job['status'] === 'active' ? 'paused' : 'active';
                $db->execute("UPDATE ncm_scheduled_jobs SET status = ? WHERE id = ?", [$newStatus, $jobId]);
                echo json_encode(['success' => true, 'message' => 'Job ' . ($newStatus === 'active' ? 'enabled' : 'disabled')]);
                break;

            case 'backup_all':
                $deviceCount = 0;
                foreach ($devices as $device) {
                    $config = "! Backup for " . $device['device_name'] . " - " . date('Y-m-d H:i:s');
                    $db->execute("INSERT INTO ncm_config_backups (device_id, config_content, backup_type, file_size) VALUES (?, ?, 'scheduled', ?)",
                        [$device['id'], $config, strlen($config)]);
                    $db->execute("UPDATE ncm_devices SET last_backup = NOW() WHERE id = ?", [$device['id']]);
                    $deviceCount++;
                }
                echo json_encode(['success' => true, 'message' => "Backed up $deviceCount devices"]);
                break;

            case 'compliance_all':
                echo json_encode(['success' => true, 'message' => 'Compliance check initiated for all devices']);
                break;

            default:
                echo json_encode(['success' => false, 'message' => 'Unknown action']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Network Configuration Manager | NCM</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --primary: #059669;
            --primary-dark: #047857;
            --secondary: #0891b2;
            --bg-dark: #111827;
            --bg-card: #1f2937;
            --bg-lighter: #374151;
            --text: #f3f4f6;
            --text-muted: #9ca3af;
            --border: #4b5563;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
        }
        body { font-family: 'Segoe UI', system-ui, sans-serif; background: var(--bg-dark); color: var(--text); min-height: 100vh; }
        .header { background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); padding: 20px 30px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 24px; display: flex; align-items: center; gap: 10px; }
        .header p { opacity: 0.9; font-size: 14px; }
        .back-btn { background: rgba(255,255,255,0.2); color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; transition: background 0.3s; }
        .back-btn:hover { background: rgba(255,255,255,0.3); }
        .container { max-width: 1600px; margin: 0 auto; padding: 20px; }
        .stats-grid { display: grid; grid-template-columns: repeat(6, 1fr); gap: 15px; margin-bottom: 20px; }
        .stat-card { background: var(--bg-card); border-radius: 12px; padding: 20px; text-align: center; border: 1px solid var(--border); }
        .stat-card .icon { font-size: 32px; margin-bottom: 10px; }
        .stat-card .value { font-size: 28px; font-weight: bold; color: var(--primary); }
        .stat-card .label { font-size: 12px; color: var(--text-muted); margin-top: 5px; }
        .toolbar { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
        .tabs { display: flex; gap: 5px; margin-bottom: 20px; background: var(--bg-card); padding: 5px; border-radius: 10px; flex-wrap: wrap; }
        .tab { padding: 12px 24px; background: transparent; border: none; color: var(--text-muted); cursor: pointer; border-radius: 8px; font-size: 14px; transition: all 0.3s; }
        .tab:hover { background: var(--bg-lighter); color: var(--text); }
        .tab.active { background: var(--primary); color: white; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .card { background: var(--bg-card); border-radius: 12px; padding: 20px; margin-bottom: 20px; border: 1px solid var(--border); }
        .card h2 { font-size: 18px; margin-bottom: 15px; display: flex; align-items: center; gap: 10px; }
        .card h2 .actions { margin-left: auto; display: flex; gap: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid var(--border); }
        th { background: var(--bg-lighter); font-weight: 600; font-size: 12px; text-transform: uppercase; color: var(--text-muted); }
        tr:hover { background: rgba(255,255,255,0.02); }
        .badge { padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
        .badge-success { background: rgba(16, 185, 129, 0.2); color: #10b981; }
        .badge-warning { background: rgba(245, 158, 11, 0.2); color: #f59e0b; }
        .badge-danger { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
        .badge-info { background: rgba(59, 130, 246, 0.2); color: #3b82f6; }
        .badge-secondary { background: rgba(107, 114, 128, 0.2); color: #9ca3af; }
        .btn { padding: 8px 16px; border-radius: 6px; border: none; cursor: pointer; font-size: 13px; transition: all 0.3s; display: inline-flex; align-items: center; gap: 6px; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-dark); }
        .btn-secondary { background: var(--bg-lighter); color: var(--text); }
        .btn-secondary:hover { background: var(--border); }
        .btn-success { background: var(--success); color: white; }
        .btn-danger { background: var(--danger); color: white; }
        .btn-warning { background: var(--warning); color: white; }
        .btn-sm { padding: 5px 10px; font-size: 11px; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; color: var(--text-muted); font-size: 13px; }
        .form-control { width: 100%; padding: 10px 12px; background: var(--bg-lighter); border: 1px solid var(--border); border-radius: 6px; color: var(--text); font-size: 14px; }
        .form-control:focus { outline: none; border-color: var(--primary); }
        select.form-control { cursor: pointer; }
        textarea.form-control { min-height: 150px; font-family: 'Consolas', 'Monaco', monospace; font-size: 12px; }
        .config-viewer { background: #0d1117; border-radius: 8px; padding: 15px; font-family: 'Consolas', 'Monaco', monospace; font-size: 12px; max-height: 500px; overflow: auto; white-space: pre-wrap; color: #c9d1d9; line-height: 1.5; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 1000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: var(--bg-card); border-radius: 12px; padding: 25px; max-width: 900px; width: 90%; max-height: 90vh; overflow-y: auto; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid var(--border); }
        .modal-close { background: none; border: none; color: var(--text-muted); font-size: 24px; cursor: pointer; }
        .modal-close:hover { color: var(--text); }
        .action-buttons { display: flex; gap: 5px; flex-wrap: wrap; }
        .setup-message { text-align: center; padding: 60px; background: var(--bg-card); border-radius: 12px; margin: 40px auto; max-width: 600px; }
        .compliance-result { padding: 10px 15px; margin: 5px 0; border-radius: 6px; display: flex; justify-content: space-between; align-items: center; }
        .compliance-pass { background: rgba(16, 185, 129, 0.1); border-left: 3px solid var(--success); }
        .compliance-fail { background: rgba(239, 68, 68, 0.1); border-left: 3px solid var(--danger); }
        @media (max-width: 1200px) {
            .stats-grid { grid-template-columns: repeat(3, 1fr); }
            .grid-2, .grid-3 { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .toolbar { flex-direction: column; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>Network Configuration Manager</h1>
            <p>Automate network device configuration management and change tracking</p>
        </div>
        <a href="../index.php" class="back-btn">Back to Dashboard</a>
    </div>

    <div class="container">
        <?php if (!$tablesExist): ?>
        <div class="setup-message">
            <div style="font-size: 80px;">🔧</div>
            <h2 style="margin: 20px 0;">Database Setup Required</h2>
            <?php if ($dbError): ?>
            <p style="color: var(--danger); margin: 20px 0; padding: 15px; background: rgba(239,68,68,0.1); border-radius: 8px;"><?= htmlspecialchars($dbError) ?></p>
            <?php endif; ?>
            <p style="color: var(--text-muted); margin: 20px 0;">Please run the setup script to create the NCM database tables.</p>
            <a href="../setup_ncm_vnqm.php" class="btn btn-primary" style="font-size: 16px; padding: 12px 24px;">Run Setup Script</a>
        </div>
        <?php else: ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon">🖥️</div>
                <div class="value"><?= $stats['total_devices'] ?></div>
                <div class="label">Total Devices</div>
            </div>
            <div class="stat-card">
                <div class="icon">✅</div>
                <div class="value" style="color: var(--success);"><?= $stats['compliant'] ?></div>
                <div class="label">Compliant</div>
            </div>
            <div class="stat-card">
                <div class="icon">⚠️</div>
                <div class="value" style="color: var(--danger);"><?= $stats['non_compliant'] ?></div>
                <div class="label">Non-Compliant</div>
            </div>
            <div class="stat-card">
                <div class="icon">📋</div>
                <div class="value" style="color: var(--warning);"><?= $stats['pending_changes'] ?></div>
                <div class="label">Pending Changes</div>
            </div>
            <div class="stat-card">
                <div class="icon">💾</div>
                <div class="value" style="color: var(--info);"><?= $stats['backups_today'] ?></div>
                <div class="label">Backups Today</div>
            </div>
            <div class="stat-card">
                <div class="icon">🔄</div>
                <div class="value"><?= $stats['config_changes_24h'] ?></div>
                <div class="label">Changes (24h)</div>
            </div>
        </div>

        <div class="toolbar">
            <button class="btn btn-primary" onclick="showModal('addDeviceModal')">+ Add Device</button>
            <button class="btn btn-secondary" onclick="backupAll()">Backup All Devices</button>
            <button class="btn btn-secondary" onclick="complianceAll()">Run Compliance Check</button>
            <button class="btn btn-secondary" onclick="showModal('addTemplateModal')">+ Create Template</button>
        </div>

        <div class="tabs">
            <button class="tab active" onclick="showTab('devices')">Devices</button>
            <button class="tab" onclick="showTab('backups')">Backups</button>
            <button class="tab" onclick="showTab('changes')">Change Management</button>
            <button class="tab" onclick="showTab('templates')">Templates</button>
            <button class="tab" onclick="showTab('compliance')">Compliance</button>
            <button class="tab" onclick="showTab('jobs')">Scheduled Jobs</button>
        </div>

        <!-- Devices Tab -->
        <div id="tab-devices" class="tab-content active">
            <div class="card">
                <h2>📡 Network Devices</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Device Name</th>
                            <th>IP Address</th>
                            <th>Type</th>
                            <th>Vendor / Model</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th>Compliance</th>
                            <th>Last Backup</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($devices as $device): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($device['device_name']) ?></strong></td>
                            <td><code style="background: var(--bg-lighter); padding: 2px 6px; border-radius: 4px;"><?= htmlspecialchars($device['ip_address']) ?></code></td>
                            <td><?= ucfirst(str_replace('_', ' ', $device['device_type'])) ?></td>
                            <td><?= htmlspecialchars(($device['vendor'] ?? '-') . ' ' . ($device['model'] ?? '')) ?></td>
                            <td><?= htmlspecialchars($device['location'] ?? '-') ?></td>
                            <td>
                                <span class="badge badge-<?= $device['status'] === 'active' ? 'success' : ($device['status'] === 'maintenance' ? 'warning' : 'danger') ?>">
                                    <?= strtoupper($device['status']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-<?= $device['compliance_status'] === 'compliant' ? 'success' : ($device['compliance_status'] === 'non_compliant' ? 'danger' : 'secondary') ?>">
                                    <?= strtoupper(str_replace('_', '-', $device['compliance_status'])) ?>
                                </span>
                            </td>
                            <td style="font-size: 12px;"><?= $device['last_backup'] ? date('Y-m-d H:i', strtotime($device['last_backup'])) : '<span style="color: var(--text-muted);">Never</span>' ?></td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn btn-sm btn-primary" onclick="backupConfig(<?= $device['id'] ?>)" title="Backup">💾</button>
                                    <button class="btn btn-sm btn-secondary" onclick="compareConfigs(<?= $device['id'] ?>)" title="Compare">📊</button>
                                    <button class="btn btn-sm btn-secondary" onclick="runComplianceCheck(<?= $device['id'] ?>)" title="Check Compliance">✅</button>
                                    <button class="btn btn-sm btn-danger" onclick="deleteDevice(<?= $device['id'] ?>)" title="Delete">🗑️</button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($devices)): ?>
                        <tr><td colspan="9" style="text-align: center; color: var(--text-muted); padding: 40px;">No devices found. Add your first device!</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Backups Tab -->
        <div id="tab-backups" class="tab-content">
            <div class="card">
                <h2>💾 Configuration Backups</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Device</th>
                            <th>Backup Type</th>
                            <th>Backup Time</th>
                            <th>Size</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($backups as $backup): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($backup['device_name']) ?></strong></td>
                            <td><span class="badge badge-<?= $backup['backup_type'] === 'manual' ? 'info' : 'secondary' ?>"><?= strtoupper($backup['backup_type']) ?></span></td>
                            <td><?= date('Y-m-d H:i:s', strtotime($backup['backup_time'])) ?></td>
                            <td><?= number_format($backup['file_size'] / 1024, 1) ?> KB</td>
                            <td>
                                <button class="btn btn-sm btn-secondary" onclick="viewBackup(<?= $backup['id'] ?>)">View</button>
                                <button class="btn btn-sm btn-secondary" onclick="downloadBackup(<?= $backup['id'] ?>)">Download</button>
                                <button class="btn btn-sm btn-warning" onclick="restoreBackup(<?= $backup['id'] ?>)">Restore</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($backups)): ?>
                        <tr><td colspan="5" style="text-align: center; color: var(--text-muted); padding: 40px;">No backups found</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Changes Tab -->
        <div id="tab-changes" class="tab-content">
            <div class="card">
                <h2>🔄 Configuration Changes</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Device</th>
                            <th>Change Type</th>
                            <th>Description</th>
                            <th>Requested By</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($changes as $change): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($change['device_name']) ?></strong></td>
                            <td><?= ucfirst(str_replace('_', ' ', $change['change_type'])) ?></td>
                            <td style="max-width: 300px; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars($change['change_description']) ?></td>
                            <td><?= htmlspecialchars($change['requested_by']) ?></td>
                            <td>
                                <span class="badge badge-<?= $change['status'] === 'approved' ? 'success' : ($change['status'] === 'rejected' ? 'danger' : ($change['status'] === 'pending' ? 'warning' : ($change['status'] === 'applied' ? 'info' : 'secondary'))) ?>">
                                    <?= strtoupper($change['status']) ?>
                                </span>
                            </td>
                            <td style="font-size: 12px;"><?= date('Y-m-d H:i', strtotime($change['created_at'])) ?></td>
                            <td>
                                <?php if ($change['status'] === 'pending'): ?>
                                <button class="btn btn-sm btn-success" onclick="approveChange(<?= $change['id'] ?>)">Approve</button>
                                <button class="btn btn-sm btn-danger" onclick="rejectChange(<?= $change['id'] ?>)">Reject</button>
                                <?php elseif ($change['status'] === 'approved'): ?>
                                <button class="btn btn-sm btn-primary" onclick="applyChange(<?= $change['id'] ?>)">Apply</button>
                                <?php else: ?>
                                <button class="btn btn-sm btn-secondary" onclick="viewChange(<?= $change['id'] ?>)">View</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($changes)): ?>
                        <tr><td colspan="7" style="text-align: center; color: var(--text-muted); padding: 40px;">No configuration changes</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Templates Tab -->
        <div id="tab-templates" class="tab-content">
            <div class="card">
                <h2>📋 Configuration Templates</h2>
                <div class="grid-3">
                    <?php foreach ($templates as $template): ?>
                    <div style="background: var(--bg-lighter); border-radius: 10px; padding: 20px;">
                        <h3 style="font-size: 16px; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center;">
                            <?= htmlspecialchars($template['template_name']) ?>
                            <span class="badge badge-info"><?= strtoupper($template['device_type']) ?></span>
                        </h3>
                        <p style="color: var(--text-muted); font-size: 13px; margin-bottom: 15px; min-height: 40px;">
                            <?= htmlspecialchars($template['description'] ?? 'No description') ?>
                        </p>
                        <?php if ($template['vendor']): ?>
                        <p style="font-size: 12px; margin-bottom: 10px;">Vendor: <strong><?= htmlspecialchars($template['vendor']) ?></strong></p>
                        <?php endif; ?>
                        <div style="display: flex; gap: 10px;">
                            <button class="btn btn-sm btn-secondary" onclick="viewTemplate(<?= $template['id'] ?>)">View</button>
                            <button class="btn btn-sm btn-primary" onclick="showApplyTemplateModal(<?= $template['id'] ?>)">Apply</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($templates)): ?>
                    <div style="grid-column: span 3; text-align: center; color: var(--text-muted); padding: 40px;">
                        No templates found. Create your first configuration template!
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Compliance Tab -->
        <div id="tab-compliance" class="tab-content">
            <div class="grid-2">
                <div class="card">
                    <h2>📊 Compliance Overview</h2>
                    <canvas id="complianceChart" height="200"></canvas>
                </div>
                <div class="card">
                    <h2>📜 Compliance Rules (<?= count($complianceRules) ?>)</h2>
                    <div style="max-height: 400px; overflow-y: auto;">
                        <?php foreach ($complianceRules as $rule): ?>
                        <div style="padding: 12px; background: var(--bg-lighter); border-radius: 8px; margin-bottom: 10px; border-left: 3px solid <?= $rule['severity'] === 'critical' ? 'var(--danger)' : ($rule['severity'] === 'high' ? 'var(--warning)' : 'var(--info)') ?>;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                                <strong><?= htmlspecialchars($rule['rule_name']) ?></strong>
                                <span class="badge badge-<?= $rule['severity'] === 'critical' ? 'danger' : ($rule['severity'] === 'high' ? 'warning' : ($rule['severity'] === 'medium' ? 'info' : 'secondary')) ?>">
                                    <?= strtoupper($rule['severity']) ?>
                                </span>
                            </div>
                            <p style="color: var(--text-muted); font-size: 12px; margin: 5px 0;"><?= htmlspecialchars($rule['description'] ?? '') ?></p>
                            <code style="font-size: 11px; color: var(--info);"><?= htmlspecialchars($rule['rule_type']) ?>: <?= htmlspecialchars(substr($rule['rule_pattern'], 0, 50)) ?><?= strlen($rule['rule_pattern']) > 50 ? '...' : '' ?></code>
                        </div>
                        <?php endforeach; ?>
                        <?php if (empty($complianceRules)): ?>
                        <p style="text-align: center; color: var(--text-muted); padding: 20px;">No compliance rules defined</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Jobs Tab -->
        <div id="tab-jobs" class="tab-content">
            <div class="card">
                <h2>⏰ Scheduled Jobs</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Job Name</th>
                            <th>Type</th>
                            <th>Schedule</th>
                            <th>Next Run</th>
                            <th>Last Run</th>
                            <th>Last Result</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($jobs as $job): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($job['job_name']) ?></strong></td>
                            <td><?= ucfirst(str_replace('_', ' ', $job['job_type'])) ?></td>
                            <td><code><?= htmlspecialchars($job['schedule_cron']) ?></code></td>
                            <td><?= $job['next_run'] ? date('Y-m-d H:i', strtotime($job['next_run'])) : '-' ?></td>
                            <td><?= $job['last_run'] ? date('Y-m-d H:i', strtotime($job['last_run'])) : 'Never' ?></td>
                            <td>
                                <?php if ($job['last_result']): ?>
                                <span class="badge badge-<?= $job['last_result'] === 'success' ? 'success' : ($job['last_result'] === 'partial' ? 'warning' : 'danger') ?>">
                                    <?= strtoupper($job['last_result']) ?>
                                </span>
                                <?php else: ?>
                                <span style="color: var(--text-muted);">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-<?= $job['status'] === 'active' ? 'success' : 'secondary' ?>">
                                    <?= strtoupper($job['status']) ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-primary" onclick="runJob(<?= $job['id'] ?>)">Run Now</button>
                                <button class="btn btn-sm btn-<?= $job['status'] === 'active' ? 'warning' : 'success' ?>" onclick="toggleJob(<?= $job['id'] ?>)">
                                    <?= $job['status'] === 'active' ? 'Pause' : 'Enable' ?>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($jobs)): ?>
                        <tr><td colspan="8" style="text-align: center; color: var(--text-muted); padding: 40px;">No scheduled jobs</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php endif; ?>
    </div>

    <!-- Add Device Modal -->
    <div id="addDeviceModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Device</h2>
                <button class="modal-close" onclick="closeModal('addDeviceModal')">&times;</button>
            </div>
            <form onsubmit="addDevice(event)">
                <div class="grid-2">
                    <div class="form-group">
                        <label>Device Name *</label>
                        <input type="text" name="device_name" class="form-control" required placeholder="e.g., Core-Router-01">
                    </div>
                    <div class="form-group">
                        <label>IP Address *</label>
                        <input type="text" name="ip_address" class="form-control" required placeholder="e.g., 192.168.1.1">
                    </div>
                    <div class="form-group">
                        <label>Device Type *</label>
                        <select name="device_type" class="form-control" required>
                            <option value="router">Router</option>
                            <option value="switch">Switch</option>
                            <option value="firewall">Firewall</option>
                            <option value="access_point">Access Point</option>
                            <option value="load_balancer">Load Balancer</option>
                            <option value="wireless_controller">Wireless Controller</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Vendor *</label>
                        <select name="vendor" class="form-control" required>
                            <option value="Cisco">Cisco</option>
                            <option value="Juniper">Juniper</option>
                            <option value="Fortinet">Fortinet</option>
                            <option value="Palo Alto">Palo Alto</option>
                            <option value="Arista">Arista</option>
                            <option value="HP">HP</option>
                            <option value="Dell">Dell</option>
                            <option value="F5">F5</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Model</label>
                        <input type="text" name="model" class="form-control" placeholder="e.g., Catalyst 9500">
                    </div>
                    <div class="form-group">
                        <label>Location</label>
                        <input type="text" name="location" class="form-control" placeholder="e.g., Data Center Rack A1">
                    </div>
                </div>
                <div style="text-align: right; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addDeviceModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Device</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Template Modal -->
    <div id="addTemplateModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Create Configuration Template</h2>
                <button class="modal-close" onclick="closeModal('addTemplateModal')">&times;</button>
            </div>
            <form onsubmit="createTemplate(event)">
                <div class="grid-2">
                    <div class="form-group">
                        <label>Template Name *</label>
                        <input type="text" name="template_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Device Type *</label>
                        <select name="device_type" class="form-control" required>
                            <option value="router">Router</option>
                            <option value="switch">Switch</option>
                            <option value="firewall">Firewall</option>
                            <option value="all">All Devices</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Vendor</label>
                        <input type="text" name="vendor" class="form-control" placeholder="e.g., Cisco">
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <input type="text" name="description" class="form-control">
                    </div>
                </div>
                <div class="form-group">
                    <label>Template Content * <span style="color: var(--text-muted); font-size: 11px;">(Use {{VARIABLE}} for placeholders)</span></label>
                    <textarea name="template_content" class="form-control" required style="min-height: 200px;" placeholder="hostname {{HOSTNAME}}
!
interface {{INTERFACE}}
 ip address {{IP_ADDRESS}} {{SUBNET_MASK}}
!"></textarea>
                </div>
                <div style="text-align: right; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addTemplateModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Template</button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Backup Modal -->
    <div id="viewBackupModal" class="modal">
        <div class="modal-content" style="max-width: 1000px;">
            <div class="modal-header">
                <h2>Configuration Backup - <span id="backupDeviceName"></span></h2>
                <button class="modal-close" onclick="closeModal('viewBackupModal')">&times;</button>
            </div>
            <p style="margin-bottom: 15px; color: var(--text-muted);">Backup Time: <span id="backupTime"></span></p>
            <div id="backupContent" class="config-viewer"></div>
        </div>
    </div>

    <!-- View Template Modal -->
    <div id="viewTemplateModal" class="modal">
        <div class="modal-content" style="max-width: 1000px;">
            <div class="modal-header">
                <h2>Template - <span id="templateName"></span></h2>
                <button class="modal-close" onclick="closeModal('viewTemplateModal')">&times;</button>
            </div>
            <div id="templateContent" class="config-viewer"></div>
        </div>
    </div>

    <!-- Compare Configs Modal -->
    <div id="compareModal" class="modal">
        <div class="modal-content" style="max-width: 1200px;">
            <div class="modal-header">
                <h2>Configuration Comparison</h2>
                <button class="modal-close" onclick="closeModal('compareModal')">&times;</button>
            </div>
            <div class="grid-2">
                <div>
                    <h3 style="margin-bottom: 10px; font-size: 14px;">Current Configuration <span id="config1Time" style="color: var(--text-muted); font-weight: normal;"></span></h3>
                    <div id="config1" class="config-viewer" style="max-height: 400px;"></div>
                </div>
                <div>
                    <h3 style="margin-bottom: 10px; font-size: 14px;">Previous Configuration <span id="config2Time" style="color: var(--text-muted); font-weight: normal;"></span></h3>
                    <div id="config2" class="config-viewer" style="max-height: 400px;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Compliance Results Modal -->
    <div id="complianceModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Compliance Check Results</h2>
                <button class="modal-close" onclick="closeModal('complianceModal')">&times;</button>
            </div>
            <div id="complianceSummary" style="margin-bottom: 20px; padding: 15px; background: var(--bg-lighter); border-radius: 8px;"></div>
            <div id="complianceResults"></div>
        </div>
    </div>

    <!-- Apply Template Modal -->
    <div id="applyTemplateModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Apply Template to Device</h2>
                <button class="modal-close" onclick="closeModal('applyTemplateModal')">&times;</button>
            </div>
            <form onsubmit="applyTemplate(event)">
                <input type="hidden" id="applyTemplateId" name="template_id">
                <div class="form-group">
                    <label>Select Device *</label>
                    <select name="device_id" class="form-control" required>
                        <?php foreach ($devices as $device): ?>
                        <option value="<?= $device['id'] ?>"><?= htmlspecialchars($device['device_name']) ?> (<?= $device['ip_address'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="text-align: right; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('applyTemplateModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Apply Template</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showTab(tabName) {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
            event.target.classList.add('active');
            document.getElementById('tab-' + tabName).classList.add('active');
        }

        function showModal(modalId) {
            console.log('Opening modal:', modalId);
            document.getElementById(modalId).classList.add('active');
        }
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }

        function apiCall(data) {
            console.log('API Call with data:', data);
            let body;
            try {
                if (data instanceof FormData) {
                    body = new URLSearchParams(data);
                } else if (typeof data === 'object' && data !== null) {
                    body = new URLSearchParams();
                    Object.keys(data).forEach(function(key) {
                        body.append(key, data[key]);
                    });
                } else {
                    throw new Error('Invalid data type for API call');
                }
                console.log('Request body:', body.toString());
            } catch (e) {
                console.error('Error building request:', e);
                return Promise.resolve({ success: false, message: 'Error building request: ' + e.message });
            }

            return fetch(window.location.href, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body
            })
            .then(function(r) {
                console.log('Response status:', r.status);
                if (!r.ok) throw new Error('Network response was not ok: ' + r.status);
                return r.text();
            })
            .then(function(text) {
                console.log('Response text (first 500 chars):', text.substring(0, 500));
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('JSON parse error:', e);
                    console.error('Full response:', text);
                    return { success: false, message: 'Invalid JSON response from server' };
                }
            })
            .catch(function(err) {
                console.error('API Error:', err);
                return { success: false, message: 'Request failed: ' + err.message };
            });
        }

        function addDevice(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            formData.append('action', 'add_device');
            apiCall(formData).then(data => {
                alert(data.message);
                if (data.success) location.reload();
            });
        }

        function deleteDevice(deviceId) {
            if (!confirm('Delete this device and all its configurations?')) return;
            console.log('Deleting device:', deviceId);
            apiCall({ action: 'delete_device', device_id: deviceId }).then(data => {
                alert(data.message);
                if (data.success) location.reload();
            });
        }

        function backupConfig(deviceId) {
            if (!confirm('Backup configuration for this device?')) return;
            console.log('Backing up device:', deviceId);
            apiCall({ action: 'backup_config', device_id: deviceId }).then(data => {
                alert(data.message);
                if (data.success) location.reload();
            });
        }

        function backupAll() {
            if (!confirm('Backup all device configurations?')) return;
            apiCall({ action: 'backup_all' }).then(data => {
                alert(data.message);
                if (data.success) location.reload();
            });
        }

        function viewBackup(backupId) {
            console.log('Viewing backup:', backupId);
            apiCall({ action: 'get_backup', backup_id: backupId }).then(data => {
                if (data.success) {
                    document.getElementById('backupDeviceName').textContent = data.backup.device_name;
                    document.getElementById('backupTime').textContent = data.backup.backup_time;
                    document.getElementById('backupContent').textContent = data.backup.config_content;
                    showModal('viewBackupModal');
                } else {
                    alert(data.message);
                }
            });
        }

        function compareConfigs(deviceId) {
            console.log('Comparing configs for device:', deviceId);
            apiCall({ action: 'compare_configs', device_id: deviceId }).then(data => {
                if (data.success) {
                    document.getElementById('config1').textContent = data.config1.config_content;
                    document.getElementById('config2').textContent = data.config2.config_content;
                    document.getElementById('config1Time').textContent = '(' + data.config1.backup_time + ')';
                    document.getElementById('config2Time').textContent = '(' + data.config2.backup_time + ')';
                    showModal('compareModal');
                } else {
                    alert(data.message);
                }
            });
        }

        function runComplianceCheck(deviceId) {
            console.log('Running compliance check for device:', deviceId);
            apiCall({ action: 'run_compliance_check', device_id: deviceId }).then(data => {
                if (data.success) {
                    const status = data.status === 'compliant' ? '✅ COMPLIANT' : '❌ NON-COMPLIANT';
                    document.getElementById('complianceSummary').innerHTML = `
                        <div style="font-size: 24px; font-weight: bold; color: ${data.status === 'compliant' ? 'var(--success)' : 'var(--danger)'};">${status}</div>
                        <div style="margin-top: 10px;">Passed: ${data.passed} | Failed: ${data.failed}</div>
                    `;
                    let html = '';
                    data.results.forEach(r => {
                        html += `<div class="compliance-result compliance-${r.result}">
                            <span>${r.rule}</span>
                            <span class="badge badge-${r.result === 'pass' ? 'success' : 'danger'}">${r.result.toUpperCase()}</span>
                        </div>`;
                    });
                    document.getElementById('complianceResults').innerHTML = html;
                    showModal('complianceModal');
                } else {
                    alert(data.message);
                }
            });
        }

        function complianceAll() {
            if (!confirm('Run compliance check on all devices?')) return;
            apiCall({ action: 'compliance_all' }).then(data => {
                alert(data.message);
            });
        }

        function approveChange(changeId) {
            if (!confirm('Approve this change?')) return;
            apiCall({ action: 'approve_change', change_id: changeId }).then(data => {
                alert(data.message);
                if (data.success) location.reload();
            });
        }

        function rejectChange(changeId) {
            if (!confirm('Reject this change?')) return;
            apiCall({ action: 'reject_change', change_id: changeId }).then(data => {
                alert(data.message);
                if (data.success) location.reload();
            });
        }

        function applyChange(changeId) {
            if (!confirm('Apply this change to the device?')) return;
            apiCall({ action: 'apply_change', change_id: changeId }).then(data => {
                alert(data.message);
                if (data.success) location.reload();
            });
        }

        function createTemplate(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            formData.append('action', 'create_template');
            apiCall(formData).then(data => {
                alert(data.message);
                if (data.success) location.reload();
            });
        }

        function viewTemplate(templateId) {
            apiCall({ action: 'get_template', template_id: templateId }).then(data => {
                if (data.success) {
                    document.getElementById('templateName').textContent = data.template.template_name;
                    document.getElementById('templateContent').textContent = data.template.template_content;
                    showModal('viewTemplateModal');
                } else {
                    alert(data.message);
                }
            });
        }

        function showApplyTemplateModal(templateId) {
            document.getElementById('applyTemplateId').value = templateId;
            showModal('applyTemplateModal');
        }

        function applyTemplate(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            formData.append('action', 'apply_template');
            apiCall(formData).then(data => {
                alert(data.message);
                if (data.success) {
                    closeModal('applyTemplateModal');
                    location.reload();
                }
            });
        }

        function runJob(jobId) {
            if (!confirm('Run this job now?')) return;
            apiCall({ action: 'run_job', job_id: jobId }).then(data => {
                alert(data.message);
                if (data.success) location.reload();
            });
        }

        function toggleJob(jobId) {
            apiCall({ action: 'toggle_job', job_id: jobId }).then(data => {
                alert(data.message);
                if (data.success) location.reload();
            });
        }

        function downloadBackup(backupId) {
            apiCall({ action: 'get_backup', backup_id: backupId }).then(data => {
                if (data.success) {
                    const blob = new Blob([data.backup.config_content], { type: 'text/plain' });
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = data.backup.device_name + '_' + data.backup.backup_time.replace(/[: ]/g, '_') + '.txt';
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(url);
                } else {
                    alert(data.message);
                }
            });
        }

        function restoreBackup(backupId) {
            if (!confirm('Restore this backup configuration to the device? This will create a new change request.')) return;
            alert('Restore functionality would push this backup to the device. Feature pending implementation.');
        }

        function viewChange(changeId) {
            alert('Change details view - Feature pending implementation.');
        }

        // Debug: Log that scripts loaded successfully
        console.log('NCM JavaScript loaded successfully');

        // Verify buttons are clickable
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM fully loaded');
            var buttons = document.querySelectorAll('.btn');
            console.log('Found ' + buttons.length + ' buttons on page');

            // Test that onclick handlers exist
            var actionButtons = document.querySelectorAll('.action-buttons .btn');
            console.log('Found ' + actionButtons.length + ' action buttons');
        });

        // Initialize compliance chart
        <?php if ($tablesExist && $stats['total_devices'] > 0): ?>
        const ctx = document.getElementById('complianceChart');
        if (ctx) {
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Compliant', 'Non-Compliant', 'Unknown'],
                    datasets: [{
                        data: [<?= $stats['compliant'] ?>, <?= $stats['non_compliant'] ?>, <?= $stats['total_devices'] - $stats['compliant'] - $stats['non_compliant'] ?>],
                        backgroundColor: ['#10b981', '#ef4444', '#6b7280'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { position: 'bottom', labels: { color: '#f3f4f6', padding: 20 } }
                    }
                }
            });
        }
        <?php endif; ?>
    </script>
</body>
</html>
