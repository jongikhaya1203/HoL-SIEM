<?php
session_start();
require_once __DIR__ . '/includes/Database.php';

if (!isset($_SESSION['cpanel_logged_in']) || $_SESSION['cpanel_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$db = Database::getInstance()->getConnection();
$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'add_device':
            try {
                $stmt = $db->prepare("INSERT INTO cpanel_scada_devices (device_name, device_type, protocol, connection_config, polling_interval, is_enabled) VALUES (?, ?, ?, ?, ?, ?)");
                $config = json_encode([
                    'host' => $_POST['host'] ?? '',
                    'port' => $_POST['port'] ?? '',
                    'unit_id' => $_POST['unit_id'] ?? 1
                ]);
                $stmt->execute([
                    $_POST['device_name'],
                    $_POST['device_type'],
                    $_POST['protocol'],
                    $config,
                    $_POST['polling_interval'] ?? 1000,
                    isset($_POST['is_enabled']) ? 1 : 0
                ]);
                $message = 'Device added successfully';
                $messageType = 'success';
            } catch (Exception $e) {
                $message = 'Error adding device: ' . $e->getMessage();
                $messageType = 'danger';
            }
            break;

        case 'update_device':
            try {
                $stmt = $db->prepare("UPDATE cpanel_scada_devices SET device_name = ?, device_type = ?, protocol = ?, connection_config = ?, polling_interval = ?, is_enabled = ? WHERE id = ?");
                $config = json_encode([
                    'host' => $_POST['host'] ?? '',
                    'port' => $_POST['port'] ?? '',
                    'unit_id' => $_POST['unit_id'] ?? 1
                ]);
                $stmt->execute([
                    $_POST['device_name'],
                    $_POST['device_type'],
                    $_POST['protocol'],
                    $config,
                    $_POST['polling_interval'] ?? 1000,
                    isset($_POST['is_enabled']) ? 1 : 0,
                    $_POST['device_id']
                ]);
                $message = 'Device updated successfully';
                $messageType = 'success';
            } catch (Exception $e) {
                $message = 'Error updating device: ' . $e->getMessage();
                $messageType = 'danger';
            }
            break;

        case 'delete_device':
            try {
                $stmt = $db->prepare("DELETE FROM cpanel_scada_devices WHERE id = ?");
                $stmt->execute([$_POST['device_id']]);
                $message = 'Device deleted successfully';
                $messageType = 'success';
            } catch (Exception $e) {
                $message = 'Error deleting device: ' . $e->getMessage();
                $messageType = 'danger';
            }
            break;

        case 'add_tag':
            try {
                $stmt = $db->prepare("INSERT INTO cpanel_scada_tags (device_id, tag_name, tag_address, data_type, scaling_factor, engineering_units, alarm_low, alarm_high, is_enabled) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $_POST['device_id'],
                    $_POST['tag_name'],
                    $_POST['tag_address'],
                    $_POST['data_type'],
                    $_POST['scaling_factor'] ?? 1.0,
                    $_POST['engineering_units'] ?? '',
                    $_POST['alarm_low'] ?: null,
                    $_POST['alarm_high'] ?: null,
                    isset($_POST['is_enabled']) ? 1 : 0
                ]);
                $message = 'Tag added successfully';
                $messageType = 'success';
            } catch (Exception $e) {
                $message = 'Error adding tag: ' . $e->getMessage();
                $messageType = 'danger';
            }
            break;

        case 'test_connection':
            // Simulate connection test
            $message = 'Connection test successful (simulated)';
            $messageType = 'success';
            break;
    }
}

// Get devices
$devices = [];
try {
    $stmt = $db->query("SELECT * FROM cpanel_scada_devices ORDER BY device_name");
    $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Table might not exist
}

// Get tags
$tags = [];
try {
    $stmt = $db->query("SELECT t.*, d.device_name FROM cpanel_scada_tags t LEFT JOIN cpanel_scada_devices d ON t.device_id = d.id ORDER BY t.tag_name");
    $tags = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Table might not exist
}

// Get protocols
$protocols = [];
try {
    $stmt = $db->query("SELECT * FROM cpanel_protocols WHERE category = 'Industrial' AND is_enabled = 1");
    $protocols = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Default protocols
    $protocols = [
        ['protocol_name' => 'modbus_tcp', 'display_name' => 'Modbus TCP'],
        ['protocol_name' => 'opc_ua', 'display_name' => 'OPC UA'],
        ['protocol_name' => 'dnp3', 'display_name' => 'DNP3']
    ];
}

$currentPage = 'scada';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SCADA Configuration - IOC Control Panel</title>
    <link rel="stylesheet" href="assets/css/cpanel.css">
</head>
<body>
    <div class="cpanel-wrapper">
        <?php include 'includes/sidebar.php'; ?>

        <div class="cpanel-main">
            <?php include 'includes/header.php'; ?>

            <div class="cpanel-content">
                <div class="page-header">
                    <h1>SCADA Configuration</h1>
                    <p>Configure industrial control system devices and data points</p>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?= $messageType ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <?php if ($messageType === 'success'): ?>
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            <?php else: ?>
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                            <?php endif; ?>
                        </svg>
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <!-- Stats -->
                <div class="stats-grid" style="grid-template-columns: repeat(4, 1fr);">
                    <div class="stat-card">
                        <div class="stat-icon modules">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="4" y="4" width="16" height="16" rx="2" ry="2"></rect>
                                <rect x="9" y="9" width="6" height="6"></rect>
                                <line x1="9" y1="1" x2="9" y2="4"></line>
                                <line x1="15" y1="1" x2="15" y2="4"></line>
                                <line x1="9" y1="20" x2="9" y2="23"></line>
                                <line x1="15" y1="20" x2="15" y2="23"></line>
                            </svg>
                        </div>
                        <div class="stat-info">
                            <span class="stat-value"><?= count($devices) ?></span>
                            <span class="stat-label">Devices</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon protocols">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                            </svg>
                        </div>
                        <div class="stat-info">
                            <span class="stat-value"><?= count($tags) ?></span>
                            <span class="stat-label">Tags</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon api">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                        </div>
                        <div class="stat-info">
                            <span class="stat-value"><?= count(array_filter($devices, fn($d) => $d['status'] === 'online')) ?></span>
                            <span class="stat-label">Online</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon channels">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                        </div>
                        <div class="stat-info">
                            <span class="stat-value">1000ms</span>
                            <span class="stat-label">Avg Poll Rate</span>
                        </div>
                    </div>
                </div>

                <!-- Devices Section -->
                <div class="section-card">
                    <div class="section-header">
                        <h2>SCADA Devices</h2>
                        <button class="btn btn-primary btn-sm" onclick="showAddDevice()">+ Add Device</button>
                    </div>
                    <div class="section-body" style="padding: 0;">
                        <table class="config-table">
                            <thead>
                                <tr>
                                    <th>Device Name</th>
                                    <th>Type</th>
                                    <th>Protocol</th>
                                    <th>Connection</th>
                                    <th>Poll Interval</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($devices)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; color: var(--text-secondary);">No devices configured. Add your first SCADA device above.</td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($devices as $device): ?>
                                <?php $config = json_decode($device['connection_config'] ?? '{}', true); ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($device['device_name']) ?></strong></td>
                                    <td><?= htmlspecialchars($device['device_type']) ?></td>
                                    <td><span class="protocol-badge"><?= htmlspecialchars($device['protocol']) ?></span></td>
                                    <td><code><?= htmlspecialchars(($config['host'] ?? '') . ':' . ($config['port'] ?? '')) ?></code></td>
                                    <td><?= $device['polling_interval'] ?>ms</td>
                                    <td>
                                        <span class="status-badge status-<?= $device['status'] ?>">
                                            <?= ucfirst($device['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 5px;">
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="test_connection">
                                                <input type="hidden" name="device_id" value="<?= $device['id'] ?>">
                                                <button type="submit" class="btn btn-secondary btn-sm">Test</button>
                                            </form>
                                            <button class="btn btn-secondary btn-sm" onclick='editDevice(<?= json_encode($device) ?>)'>Edit</button>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="delete_device">
                                                <input type="hidden" name="device_id" value="<?= $device['id'] ?>">
                                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Delete this device?');">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tags Section -->
                <div class="section-card" style="margin-top: 20px;">
                    <div class="section-header">
                        <h2>Data Tags</h2>
                        <button class="btn btn-primary btn-sm" onclick="showAddTag()" <?= empty($devices) ? 'disabled' : '' ?>>+ Add Tag</button>
                    </div>
                    <div class="section-body" style="padding: 0;">
                        <table class="config-table">
                            <thead>
                                <tr>
                                    <th>Tag Name</th>
                                    <th>Device</th>
                                    <th>Address</th>
                                    <th>Data Type</th>
                                    <th>Units</th>
                                    <th>Alarms</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($tags)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; color: var(--text-secondary);">No tags configured</td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($tags as $tag): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($tag['tag_name']) ?></strong></td>
                                    <td><?= htmlspecialchars($tag['device_name'] ?? 'Unknown') ?></td>
                                    <td><code><?= htmlspecialchars($tag['tag_address']) ?></code></td>
                                    <td><?= htmlspecialchars($tag['data_type']) ?></td>
                                    <td><?= htmlspecialchars($tag['engineering_units'] ?: '-') ?></td>
                                    <td>
                                        <?php if ($tag['alarm_low'] || $tag['alarm_high']): ?>
                                            <span class="alarm-badge">L: <?= $tag['alarm_low'] ?: '-' ?> / H: <?= $tag['alarm_high'] ?: '-' ?></span>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?= $tag['is_enabled'] ? 'online' : 'offline' ?>">
                                            <?= $tag['is_enabled'] ? 'Active' : 'Disabled' ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Device Modal -->
    <div id="deviceModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h3 id="deviceModalTitle">Add Device</h3>
                <button class="modal-close" onclick="closeModal('deviceModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" id="deviceForm">
                    <input type="hidden" name="action" value="add_device" id="deviceAction">
                    <input type="hidden" name="device_id" id="deviceId">

                    <div class="form-group">
                        <label>Device Name *</label>
                        <input type="text" name="device_name" id="deviceName" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Device Type</label>
                            <select name="device_type" id="deviceType">
                                <option value="PLC">PLC</option>
                                <option value="RTU">RTU</option>
                                <option value="DCS">DCS</option>
                                <option value="HMI">HMI</option>
                                <option value="Sensor">Sensor</option>
                                <option value="Gateway">Gateway</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Protocol</label>
                            <select name="protocol" id="deviceProtocol">
                                <option value="modbus_tcp">Modbus TCP</option>
                                <option value="modbus_rtu">Modbus RTU</option>
                                <option value="opc_ua">OPC UA</option>
                                <option value="dnp3">DNP3</option>
                                <option value="ethernet_ip">EtherNet/IP</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Host/IP *</label>
                            <input type="text" name="host" id="deviceHost" placeholder="192.168.1.100" required>
                        </div>
                        <div class="form-group">
                            <label>Port</label>
                            <input type="number" name="port" id="devicePort" value="502">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Unit ID</label>
                            <input type="number" name="unit_id" id="deviceUnitId" value="1" min="1" max="255">
                        </div>
                        <div class="form-group">
                            <label>Polling Interval (ms)</label>
                            <input type="number" name="polling_interval" id="devicePolling" value="1000" min="100">
                        </div>
                    </div>

                    <div class="toggle-item">
                        <div class="toggle-info">
                            <span class="toggle-label">Enable Device</span>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="is_enabled" id="deviceEnabled" checked>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>

                    <div style="margin-top: 20px; text-align: right;">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('deviceModal')">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Device</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Tag Modal -->
    <div id="tagModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h3>Add Tag</h3>
                <button class="modal-close" onclick="closeModal('tagModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="action" value="add_tag">

                    <div class="form-group">
                        <label>Device *</label>
                        <select name="device_id" required>
                            <?php foreach ($devices as $device): ?>
                            <option value="<?= $device['id'] ?>"><?= htmlspecialchars($device['device_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Tag Name *</label>
                        <input type="text" name="tag_name" required placeholder="e.g., Tank_Level_1">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Address *</label>
                            <input type="text" name="tag_address" required placeholder="e.g., 40001">
                        </div>
                        <div class="form-group">
                            <label>Data Type</label>
                            <select name="data_type">
                                <option value="INT16">INT16</option>
                                <option value="UINT16">UINT16</option>
                                <option value="INT32">INT32</option>
                                <option value="UINT32">UINT32</option>
                                <option value="FLOAT32">FLOAT32</option>
                                <option value="FLOAT64">FLOAT64</option>
                                <option value="BOOL">BOOL</option>
                                <option value="STRING">STRING</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Scaling Factor</label>
                            <input type="number" name="scaling_factor" value="1.0" step="0.0001">
                        </div>
                        <div class="form-group">
                            <label>Engineering Units</label>
                            <input type="text" name="engineering_units" placeholder="e.g., PSI, GPM, °C">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Low Alarm</label>
                            <input type="number" name="alarm_low" step="0.01">
                        </div>
                        <div class="form-group">
                            <label>High Alarm</label>
                            <input type="number" name="alarm_high" step="0.01">
                        </div>
                    </div>

                    <div class="toggle-item">
                        <div class="toggle-info">
                            <span class="toggle-label">Enable Tag</span>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="is_enabled" checked>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>

                    <div style="margin-top: 20px; text-align: right;">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('tagModal')">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Tag</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
        .protocol-badge {
            background: #e0e7ff;
            color: #3730a3;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        .status-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        .status-online { background: #d1fae5; color: #065f46; }
        .status-offline { background: #fee2e2; color: #991b1b; }
        .status-error { background: #fef3c7; color: #92400e; }
        .status-unknown { background: #e2e8f0; color: #475569; }
        .alarm-badge {
            background: #fef3c7;
            color: #92400e;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
        }
        code {
            background: #f1f5f9;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 12px;
        }
    </style>

    <script src="assets/js/cpanel.js"></script>
    <script>
        function showAddDevice() {
            document.getElementById('deviceModalTitle').textContent = 'Add Device';
            document.getElementById('deviceAction').value = 'add_device';
            document.getElementById('deviceForm').reset();
            document.getElementById('deviceEnabled').checked = true;
            document.getElementById('deviceModal').classList.add('active');
        }

        function editDevice(device) {
            document.getElementById('deviceModalTitle').textContent = 'Edit Device';
            document.getElementById('deviceAction').value = 'update_device';
            document.getElementById('deviceId').value = device.id;
            document.getElementById('deviceName').value = device.device_name;
            document.getElementById('deviceType').value = device.device_type;
            document.getElementById('deviceProtocol').value = device.protocol;

            const config = JSON.parse(device.connection_config || '{}');
            document.getElementById('deviceHost').value = config.host || '';
            document.getElementById('devicePort').value = config.port || 502;
            document.getElementById('deviceUnitId').value = config.unit_id || 1;
            document.getElementById('devicePolling').value = device.polling_interval;
            document.getElementById('deviceEnabled').checked = device.is_enabled == 1;

            document.getElementById('deviceModal').classList.add('active');
        }

        function showAddTag() {
            document.getElementById('tagModal').classList.add('active');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }
    </script>
</body>
</html>
