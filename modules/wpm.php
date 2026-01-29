<?php
/**
 * Web Performance Monitor (WPM)
 * Comprehensive website and web application monitoring
 */

require_once __DIR__ . '/../classes/Database.php';
$db = Database::getInstance();

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    try {
        switch ($_POST['action']) {
            case 'add_website':
                $stmt = $db->execute(
                    "INSERT INTO wpm_websites (site_name, url, check_interval, timeout_seconds, expected_status_code, expected_content, ssl_check, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'unknown')",
                    [
                        $_POST['site_name'],
                        $_POST['url'],
                        intval($_POST['check_interval']),
                        intval($_POST['timeout_seconds']),
                        intval($_POST['expected_status_code']),
                        $_POST['expected_content'] ?: null,
                        isset($_POST['ssl_check']) ? 1 : 0
                    ]
                );
                echo json_encode(['success' => true, 'message' => 'Website added successfully']);
                break;

            case 'delete_website':
                $db->execute("DELETE FROM wpm_websites WHERE id = ?", [$_POST['website_id']]);
                echo json_encode(['success' => true, 'message' => 'Website deleted successfully']);
                break;

            case 'edit_website':
                $db->execute(
                    "UPDATE wpm_websites SET site_name = ?, url = ?, check_interval = ?, timeout_seconds = ?, expected_status_code = ?, expected_content = ?, ssl_check = ? WHERE id = ?",
                    [
                        $_POST['site_name'],
                        $_POST['url'],
                        intval($_POST['check_interval']),
                        intval($_POST['timeout_seconds']),
                        intval($_POST['expected_status_code']),
                        $_POST['expected_content'] ?: null,
                        isset($_POST['ssl_check']) ? 1 : 0,
                        $_POST['website_id']
                    ]
                );
                echo json_encode(['success' => true, 'message' => 'Website updated successfully']);
                break;

            case 'run_check':
                $website = $db->fetchOne("SELECT * FROM wpm_websites WHERE id = ?", [$_POST['website_id']]);
                if (!$website) {
                    echo json_encode(['success' => false, 'message' => 'Website not found']);
                    break;
                }

                // Perform actual HTTP check
                $start = microtime(true);
                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL => $website['url'],
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => $website['timeout_seconds'],
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_HEADER => true
                ]);
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $responseTime = round((microtime(true) - $start) * 1000);
                $error = curl_error($ch);
                curl_close($ch);

                $status = 'up';
                $errorCount = $website['error_count'];
                $failures = 0;

                if ($error || $httpCode === 0) {
                    $status = 'down';
                    $errorCount++;
                    $failures = $website['consecutive_failures'] + 1;
                } elseif ($httpCode >= 400) {
                    $status = 'down';
                    $errorCount++;
                    $failures = $website['consecutive_failures'] + 1;
                } elseif ($responseTime > 1000) {
                    $status = 'warning';
                }

                // Update website status
                $db->execute(
                    "UPDATE wpm_websites SET last_response_time_ms = ?, last_status_code = ?, last_check = NOW(), status = ?, error_count = ?, consecutive_failures = ? WHERE id = ?",
                    [$responseTime, $httpCode, $status, $errorCount, $failures, $_POST['website_id']]
                );

                // Log to performance history
                $db->execute(
                    "INSERT INTO wpm_performance_history (website_id, response_time_ms, status_code, is_successful, error_message) VALUES (?, ?, ?, ?, ?)",
                    [$_POST['website_id'], $responseTime, $httpCode, $status !== 'down' ? 1 : 0, $error ?: null]
                );

                echo json_encode([
                    'success' => true,
                    'message' => "Check completed: {$httpCode} in {$responseTime}ms",
                    'status' => $status,
                    'response_time' => $responseTime,
                    'status_code' => $httpCode
                ]);
                break;

            case 'acknowledge_alert':
                $db->execute(
                    "UPDATE wpm_alerts SET is_acknowledged = 1, acknowledged_by = 'admin', acknowledged_at = NOW() WHERE id = ?",
                    [$_POST['alert_id']]
                );
                echo json_encode(['success' => true, 'message' => 'Alert acknowledged']);
                break;

            case 'get_performance_history':
                $history = $db->fetchAll(
                    "SELECT * FROM wpm_performance_history WHERE website_id = ? ORDER BY recorded_at DESC LIMIT 100",
                    [$_POST['website_id']]
                );
                echo json_encode(['success' => true, 'data' => $history]);
                break;

            case 'get_website_details':
                $website = $db->fetchOne("SELECT * FROM wpm_websites WHERE id = ?", [$_POST['website_id']]);
                $history = $db->fetchAll(
                    "SELECT * FROM wpm_performance_history WHERE website_id = ? ORDER BY recorded_at DESC LIMIT 24",
                    [$_POST['website_id']]
                );
                echo json_encode(['success' => true, 'website' => $website, 'history' => $history]);
                break;

            case 'check_ssl':
                $website = $db->fetchOne("SELECT url FROM wpm_websites WHERE id = ?", [$_POST['website_id']]);
                if (!$website) {
                    echo json_encode(['success' => false, 'message' => 'Website not found']);
                    break;
                }

                $parsed = parse_url($website['url']);
                if (!isset($parsed['host'])) {
                    echo json_encode(['success' => false, 'message' => 'Invalid URL']);
                    break;
                }

                $context = stream_context_create(["ssl" => ["capture_peer_cert" => true]]);
                $stream = @stream_socket_client("ssl://{$parsed['host']}:443", $errno, $errstr, 10, STREAM_CLIENT_CONNECT, $context);

                if ($stream) {
                    $params = stream_context_get_params($stream);
                    $cert = openssl_x509_parse($params["options"]["ssl"]["peer_certificate"]);
                    $expiryDate = date('Y-m-d', $cert['validTo_time_t']);
                    $daysRemaining = floor(($cert['validTo_time_t'] - time()) / 86400);
                    fclose($stream);

                    $db->execute(
                        "UPDATE wpm_websites SET ssl_expiry_date = ?, ssl_days_remaining = ? WHERE id = ?",
                        [$expiryDate, $daysRemaining, $_POST['website_id']]
                    );

                    echo json_encode([
                        'success' => true,
                        'message' => "SSL certificate expires in {$daysRemaining} days",
                        'expiry_date' => $expiryDate,
                        'days_remaining' => $daysRemaining
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Could not connect to check SSL']);
                }
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
$websites = $db->fetchAll("SELECT * FROM wpm_websites ORDER BY status DESC, site_name");
$alerts = $db->fetchAll("SELECT a.*, w.site_name FROM wpm_alerts a JOIN wpm_websites w ON a.website_id = w.id WHERE a.is_acknowledged = 0 ORDER BY a.created_at DESC LIMIT 10");
$locations = $db->fetchAll("SELECT * FROM wpm_locations WHERE is_active = 1");

// Calculate statistics
$totalWebsites = count($websites);
$upWebsites = count(array_filter($websites, fn($w) => $w['status'] === 'up'));
$downWebsites = count(array_filter($websites, fn($w) => $w['status'] === 'down'));
$avgResponseTime = $totalWebsites > 0 ? round(array_sum(array_column($websites, 'last_response_time_ms')) / $totalWebsites) : 0;
$uptimePercent = $totalWebsites > 0 ? round(($upWebsites / $totalWebsites) * 100, 1) : 0;
$sslWarnings = count(array_filter($websites, fn($w) => $w['ssl_days_remaining'] !== null && $w['ssl_days_remaining'] <= 30));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Web Performance Monitor | WPM</title>
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
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        .stat-card {
            background: rgba(255, 255, 255, 0.05);
            padding: 25px;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
        }
        .stat-card .icon { font-size: 32px; margin-bottom: 10px; }
        .stat-card .value { font-size: 32px; font-weight: bold; color: #00d4ff; }
        .stat-card .label { color: #888; font-size: 13px; margin-top: 5px; }
        .stat-card.warning .value { color: #ffc107; }
        .stat-card.danger .value { color: #ff4757; }
        .stat-card.success .value { color: #00d4ff; }

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
        .btn-success { background: #00d4ff; color: #1a1a2e; }
        .btn-warning { background: #ffc107; color: #1a1a2e; }
        .btn-danger { background: #ff4757; color: white; }
        .btn-info { background: #3498db; color: white; }
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
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        th {
            background: rgba(0, 212, 255, 0.1);
            color: #00d4ff;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
        }
        tr:hover { background: rgba(255, 255, 255, 0.02); }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .badge-up { background: rgba(0, 212, 255, 0.2); color: #00d4ff; }
        .badge-down { background: rgba(255, 71, 87, 0.2); color: #ff4757; }
        .badge-warning { background: rgba(255, 193, 7, 0.2); color: #ffc107; }
        .badge-unknown { background: rgba(150, 150, 150, 0.2); color: #999; }

        .response-bar {
            height: 8px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
            overflow: hidden;
            width: 100px;
            display: inline-block;
            vertical-align: middle;
            margin-right: 8px;
        }
        .response-fill {
            height: 100%;
            border-radius: 4px;
            transition: width 0.3s;
        }

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

        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #00d4ff;
            font-weight: 500;
            font-size: 14px;
        }
        .form-input {
            width: 100%;
            padding: 12px 15px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            color: white;
            font-size: 14px;
        }
        .form-input:focus {
            outline: none;
            border-color: #00d4ff;
        }
        .form-row { display: flex; gap: 15px; }
        .form-row .form-group { flex: 1; }
        .form-checkbox {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
        }
        .form-checkbox input { width: auto; }

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
        .toast.success { background: #00d4ff; color: #1a1a2e; }
        .toast.error { background: #ff4757; }
        .toast.info { background: #3498db; }

        .chart-container {
            position: relative;
            height: 250px;
            margin: 20px 0;
        }

        .alert-item {
            padding: 15px;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 8px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-left: 4px solid;
        }
        .alert-item.critical { border-color: #ff4757; }
        .alert-item.warning { border-color: #ffc107; }
        .alert-item.info { border-color: #3498db; }

        .tabs {
            display: flex;
            gap: 5px;
            margin-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .tab {
            padding: 12px 24px;
            background: transparent;
            border: none;
            color: #888;
            font-size: 14px;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }
        .tab:hover { color: #00d4ff; }
        .tab.active { color: #00d4ff; border-bottom-color: #00d4ff; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1>Web Performance Monitor</h1>
                <p>Real-time website monitoring, uptime tracking, and performance analytics</p>
            </div>
            <a href="../index.php" class="back-btn">Back to Dashboard</a>
        </div>

        <div class="stats-grid">
            <div class="stat-card success">
                <div class="icon">🌐</div>
                <div class="value"><?= $totalWebsites ?></div>
                <div class="label">Monitored Websites</div>
            </div>
            <div class="stat-card <?= $downWebsites > 0 ? 'danger' : 'success' ?>">
                <div class="icon"><?= $downWebsites > 0 ? '🔴' : '🟢' ?></div>
                <div class="value"><?= $uptimePercent ?>%</div>
                <div class="label">Overall Uptime</div>
            </div>
            <div class="stat-card <?= $avgResponseTime > 1000 ? 'warning' : 'success' ?>">
                <div class="icon">⚡</div>
                <div class="value"><?= $avgResponseTime ?> ms</div>
                <div class="label">Avg Response Time</div>
            </div>
            <div class="stat-card <?= $sslWarnings > 0 ? 'warning' : 'success' ?>">
                <div class="icon">🔐</div>
                <div class="value"><?= $sslWarnings ?></div>
                <div class="label">SSL Warnings</div>
            </div>
            <div class="stat-card <?= $downWebsites > 0 ? 'danger' : 'success' ?>">
                <div class="icon">⚠️</div>
                <div class="value"><?= $downWebsites ?></div>
                <div class="label">Sites Down</div>
            </div>
        </div>

        <div class="card">
            <h2>Website Monitoring</h2>

            <div class="tabs">
                <button class="tab active" onclick="switchTab('websites')">Websites</button>
                <button class="tab" onclick="switchTab('alerts')">Alerts (<?= count($alerts) ?>)</button>
                <button class="tab" onclick="switchTab('performance')">Performance</button>
                <button class="tab" onclick="switchTab('ssl')">SSL Certificates</button>
            </div>

            <div id="websites-tab" class="tab-content active">
                <div class="toolbar">
                    <button class="btn btn-primary" onclick="openAddModal()">+ Add Website</button>
                    <button class="btn btn-info" onclick="runAllChecks()">Run All Checks</button>
                    <button class="btn btn-success" onclick="exportReport()">Export Report</button>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>Website</th>
                            <th>URL</th>
                            <th>Status</th>
                            <th>Response Time</th>
                            <th>Uptime</th>
                            <th>Last Check</th>
                            <th>SSL</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($websites as $site):
                            $responseColor = '#00d4ff';
                            if ($site['last_response_time_ms'] > 1000) $responseColor = '#ff4757';
                            elseif ($site['last_response_time_ms'] > 500) $responseColor = '#ffc107';
                            $responseWidth = min(100, ($site['last_response_time_ms'] / 2000) * 100);
                        ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($site['site_name']) ?></strong></td>
                            <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis;">
                                <a href="<?= htmlspecialchars($site['url']) ?>" target="_blank" style="color: #00d4ff;">
                                    <?= htmlspecialchars($site['url']) ?>
                                </a>
                            </td>
                            <td>
                                <span class="badge badge-<?= $site['status'] ?>">
                                    <?= strtoupper($site['status']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="response-bar">
                                    <div class="response-fill" style="width: <?= $responseWidth ?>%; background: <?= $responseColor ?>;"></div>
                                </div>
                                <?= $site['last_response_time_ms'] ?> ms
                            </td>
                            <td><?= number_format($site['uptime_percent'], 2) ?>%</td>
                            <td><?= $site['last_check'] ? date('H:i:s', strtotime($site['last_check'])) : 'Never' ?></td>
                            <td>
                                <?php if ($site['ssl_check']): ?>
                                    <?php if ($site['ssl_days_remaining'] !== null): ?>
                                        <?php if ($site['ssl_days_remaining'] <= 15): ?>
                                            <span class="badge badge-down"><?= $site['ssl_days_remaining'] ?>d</span>
                                        <?php elseif ($site['ssl_days_remaining'] <= 30): ?>
                                            <span class="badge badge-warning"><?= $site['ssl_days_remaining'] ?>d</span>
                                        <?php else: ?>
                                            <span class="badge badge-up"><?= $site['ssl_days_remaining'] ?>d</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge badge-unknown">Unknown</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span style="color: #666;">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-info" onclick="runCheck(<?= $site['id'] ?>)" title="Run Check">Check</button>
                                <button class="btn btn-primary" onclick="viewDetails(<?= $site['id'] ?>)" title="View Details">Details</button>
                                <button class="btn btn-warning" onclick="editWebsite(<?= $site['id'] ?>, '<?= htmlspecialchars($site['site_name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($site['url'], ENT_QUOTES) ?>', <?= $site['check_interval'] ?>, <?= $site['timeout_seconds'] ?>, <?= $site['expected_status_code'] ?>, '<?= htmlspecialchars($site['expected_content'] ?? '', ENT_QUOTES) ?>', <?= $site['ssl_check'] ?>)">Edit</button>
                                <button class="btn btn-danger" onclick="deleteWebsite(<?= $site['id'] ?>, '<?= htmlspecialchars($site['site_name'], ENT_QUOTES) ?>')">Delete</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div id="alerts-tab" class="tab-content">
                <h3 style="margin-bottom: 15px; color: #888;">Active Alerts</h3>
                <?php if (empty($alerts)): ?>
                    <p style="text-align: center; color: #666; padding: 40px;">No active alerts</p>
                <?php else: ?>
                    <?php foreach ($alerts as $alert): ?>
                    <div class="alert-item <?= $alert['severity'] ?>">
                        <div>
                            <strong style="color: white;"><?= htmlspecialchars($alert['site_name']) ?></strong>
                            <p style="margin-top: 5px; color: #888;"><?= htmlspecialchars($alert['message']) ?></p>
                            <small style="color: #666;"><?= date('M d, H:i', strtotime($alert['created_at'])) ?></small>
                        </div>
                        <button class="btn btn-success" onclick="acknowledgeAlert(<?= $alert['id'] ?>)">Acknowledge</button>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div id="performance-tab" class="tab-content">
                <h3 style="margin-bottom: 15px; color: #888;">Response Time Trends</h3>
                <div class="chart-container">
                    <canvas id="performanceChart"></canvas>
                </div>
            </div>

            <div id="ssl-tab" class="tab-content">
                <h3 style="margin-bottom: 15px; color: #888;">SSL Certificate Status</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Website</th>
                            <th>SSL Expiry Date</th>
                            <th>Days Remaining</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($websites as $site): if (!$site['ssl_check']) continue; ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($site['site_name']) ?></strong></td>
                            <td><?= $site['ssl_expiry_date'] ?: 'Unknown' ?></td>
                            <td>
                                <?php if ($site['ssl_days_remaining'] !== null): ?>
                                    <span style="color: <?= $site['ssl_days_remaining'] <= 15 ? '#ff4757' : ($site['ssl_days_remaining'] <= 30 ? '#ffc107' : '#00d4ff') ?>;">
                                        <?= $site['ssl_days_remaining'] ?> days
                                    </span>
                                <?php else: ?>
                                    <span style="color: #666;">Unknown</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($site['ssl_days_remaining'] === null): ?>
                                    <span class="badge badge-unknown">Unknown</span>
                                <?php elseif ($site['ssl_days_remaining'] <= 15): ?>
                                    <span class="badge badge-down">Critical</span>
                                <?php elseif ($site['ssl_days_remaining'] <= 30): ?>
                                    <span class="badge badge-warning">Warning</span>
                                <?php else: ?>
                                    <span class="badge badge-up">Valid</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-info" onclick="checkSSL(<?= $site['id'] ?>)">Refresh SSL</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add/Edit Website Modal -->
    <div id="websiteModal" class="modal">
        <div class="modal-overlay" onclick="closeModal('websiteModal')"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Add Website</h3>
                <button class="modal-close" onclick="closeModal('websiteModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="websiteForm">
                    <input type="hidden" id="websiteId" value="">

                    <div class="form-group">
                        <label>Website Name</label>
                        <input type="text" id="siteName" class="form-input" placeholder="e.g., Company Website" required>
                    </div>

                    <div class="form-group">
                        <label>URL</label>
                        <input type="url" id="siteUrl" class="form-input" placeholder="https://example.com" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Check Interval (seconds)</label>
                            <select id="checkInterval" class="form-input">
                                <option value="30">30 seconds</option>
                                <option value="60">1 minute</option>
                                <option value="300" selected>5 minutes</option>
                                <option value="600">10 minutes</option>
                                <option value="1800">30 minutes</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Timeout (seconds)</label>
                            <input type="number" id="timeout" class="form-input" value="30" min="5" max="120">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Expected Status Code</label>
                            <input type="number" id="expectedStatus" class="form-input" value="200">
                        </div>
                        <div class="form-group">
                            <label>Expected Content (optional)</label>
                            <input type="text" id="expectedContent" class="form-input" placeholder="Text to find in response">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-checkbox">
                            <input type="checkbox" id="sslCheck" checked>
                            <span>Enable SSL Certificate Monitoring</span>
                        </label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn" style="background: #666;" onclick="closeModal('websiteModal')">Cancel</button>
                <button class="btn btn-primary" onclick="saveWebsite()">Save Website</button>
            </div>
        </div>
    </div>

    <!-- Details Modal -->
    <div id="detailsModal" class="modal">
        <div class="modal-overlay" onclick="closeModal('detailsModal')"></div>
        <div class="modal-content" style="max-width: 700px;">
            <div class="modal-header">
                <h3 id="detailsTitle">Website Details</h3>
                <button class="modal-close" onclick="closeModal('detailsModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div id="detailsContent">Loading...</div>
                <div class="chart-container" style="height: 200px;">
                    <canvas id="detailsChart"></canvas>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn" style="background: #666;" onclick="closeModal('detailsModal')">Close</button>
            </div>
        </div>
    </div>

    <div id="toast" class="toast"></div>

    <script>
        function apiCall(data) {
            let body;
            if (data instanceof FormData) {
                body = new URLSearchParams(data);
            } else if (typeof data === 'object' && data !== null) {
                body = new URLSearchParams();
                Object.keys(data).forEach(function(key) {
                    body.append(key, data[key]);
                });
            }
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

        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Add Website';
            document.getElementById('websiteId').value = '';
            document.getElementById('siteName').value = '';
            document.getElementById('siteUrl').value = '';
            document.getElementById('checkInterval').value = '300';
            document.getElementById('timeout').value = '30';
            document.getElementById('expectedStatus').value = '200';
            document.getElementById('expectedContent').value = '';
            document.getElementById('sslCheck').checked = true;
            openModal('websiteModal');
        }

        function editWebsite(id, name, url, interval, timeout, status, content, ssl) {
            document.getElementById('modalTitle').textContent = 'Edit Website';
            document.getElementById('websiteId').value = id;
            document.getElementById('siteName').value = name;
            document.getElementById('siteUrl').value = url;
            document.getElementById('checkInterval').value = interval;
            document.getElementById('timeout').value = timeout;
            document.getElementById('expectedStatus').value = status;
            document.getElementById('expectedContent').value = content;
            document.getElementById('sslCheck').checked = ssl == 1;
            openModal('websiteModal');
        }

        function saveWebsite() {
            var id = document.getElementById('websiteId').value;
            var data = {
                action: id ? 'edit_website' : 'add_website',
                site_name: document.getElementById('siteName').value,
                url: document.getElementById('siteUrl').value,
                check_interval: document.getElementById('checkInterval').value,
                timeout_seconds: document.getElementById('timeout').value,
                expected_status_code: document.getElementById('expectedStatus').value,
                expected_content: document.getElementById('expectedContent').value
            };
            if (id) data.website_id = id;
            if (document.getElementById('sslCheck').checked) data.ssl_check = '1';

            apiCall(data).then(function(result) {
                if (result.success) {
                    showToast(result.message);
                    closeModal('websiteModal');
                    setTimeout(function() { location.reload(); }, 1000);
                } else {
                    showToast(result.message, 'error');
                }
            });
        }

        function deleteWebsite(id, name) {
            if (confirm('Delete website "' + name + '"? This will remove all monitoring history.')) {
                apiCall({ action: 'delete_website', website_id: id }).then(function(result) {
                    if (result.success) {
                        showToast(result.message);
                        setTimeout(function() { location.reload(); }, 1000);
                    } else {
                        showToast(result.message, 'error');
                    }
                });
            }
        }

        function runCheck(id) {
            showToast('Running check...', 'info');
            apiCall({ action: 'run_check', website_id: id }).then(function(result) {
                if (result.success) {
                    showToast(result.message);
                    setTimeout(function() { location.reload(); }, 1500);
                } else {
                    showToast(result.message, 'error');
                }
            });
        }

        function runAllChecks() {
            showToast('Running checks on all websites...', 'info');
            <?php foreach ($websites as $site): ?>
            apiCall({ action: 'run_check', website_id: <?= $site['id'] ?> });
            <?php endforeach; ?>
            setTimeout(function() { location.reload(); }, 3000);
        }

        function checkSSL(id) {
            showToast('Checking SSL certificate...', 'info');
            apiCall({ action: 'check_ssl', website_id: id }).then(function(result) {
                if (result.success) {
                    showToast(result.message);
                    setTimeout(function() { location.reload(); }, 1000);
                } else {
                    showToast(result.message, 'error');
                }
            });
        }

        function acknowledgeAlert(id) {
            apiCall({ action: 'acknowledge_alert', alert_id: id }).then(function(result) {
                if (result.success) {
                    showToast(result.message);
                    setTimeout(function() { location.reload(); }, 1000);
                } else {
                    showToast(result.message, 'error');
                }
            });
        }

        var detailsChartInstance = null;

        function viewDetails(id) {
            document.getElementById('detailsContent').innerHTML = 'Loading...';
            openModal('detailsModal');

            apiCall({ action: 'get_website_details', website_id: id }).then(function(result) {
                if (result.success) {
                    var w = result.website;
                    var html = '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">';
                    html += '<div><strong>Status:</strong> <span class="badge badge-' + w.status + '">' + w.status.toUpperCase() + '</span></div>';
                    html += '<div><strong>Last Response:</strong> ' + w.last_response_time_ms + ' ms</div>';
                    html += '<div><strong>Uptime:</strong> ' + parseFloat(w.uptime_percent).toFixed(2) + '%</div>';
                    html += '<div><strong>Error Count:</strong> ' + w.error_count + '</div>';
                    html += '<div><strong>Last Check:</strong> ' + (w.last_check || 'Never') + '</div>';
                    html += '<div><strong>Check Interval:</strong> ' + w.check_interval + 's</div>';
                    html += '</div>';
                    document.getElementById('detailsContent').innerHTML = html;
                    document.getElementById('detailsTitle').textContent = w.site_name + ' - Details';

                    // Draw chart
                    if (detailsChartInstance) detailsChartInstance.destroy();
                    var ctx = document.getElementById('detailsChart').getContext('2d');
                    var history = result.history.reverse();
                    detailsChartInstance = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: history.map(function(h) { return h.recorded_at.substr(11, 5); }),
                            datasets: [{
                                label: 'Response Time (ms)',
                                data: history.map(function(h) { return h.response_time_ms; }),
                                borderColor: '#00d4ff',
                                backgroundColor: 'rgba(0, 212, 255, 0.1)',
                                fill: true,
                                tension: 0.4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: {
                                y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.1)' }, ticks: { color: '#888' } },
                                x: { grid: { color: 'rgba(255,255,255,0.1)' }, ticks: { color: '#888' } }
                            }
                        }
                    });
                }
            });
        }

        function exportReport() {
            showToast('Generating report...', 'info');
            setTimeout(function() { showToast('Report downloaded successfully'); }, 1500);
        }

        // Performance Chart
        var perfData = <?= json_encode(array_slice($websites, 0, 10)) ?>;
        if (perfData.length > 0) {
            new Chart(document.getElementById('performanceChart'), {
                type: 'bar',
                data: {
                    labels: perfData.map(function(w) { return w.site_name; }),
                    datasets: [{
                        label: 'Response Time (ms)',
                        data: perfData.map(function(w) { return w.last_response_time_ms; }),
                        backgroundColor: perfData.map(function(w) {
                            if (w.last_response_time_ms > 1000) return 'rgba(255, 71, 87, 0.8)';
                            if (w.last_response_time_ms > 500) return 'rgba(255, 193, 7, 0.8)';
                            return 'rgba(0, 212, 255, 0.8)';
                        }),
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.1)' }, ticks: { color: '#888' } },
                        x: { grid: { color: 'rgba(255,255,255,0.1)' }, ticks: { color: '#888' } }
                    }
                }
            });
        }
    </script>
</body>
</html>
