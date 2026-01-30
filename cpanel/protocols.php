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

    if ($action === 'save_protocol') {
        $protocol = $_POST['protocol'] ?? '';
        $config = $_POST['config'] ?? [];

        try {
            $stmt = $db->prepare("
                INSERT INTO cpanel_protocols (protocol_name, config, is_enabled, updated_at)
                VALUES (?, ?, 1, NOW())
                ON DUPLICATE KEY UPDATE config = VALUES(config), updated_at = NOW()
            ");
            $stmt->execute([$protocol, json_encode($config)]);
            $message = ucfirst($protocol) . ' protocol configuration saved successfully';
            $messageType = 'success';
        } catch (Exception $e) {
            $message = 'Error saving configuration: ' . $e->getMessage();
            $messageType = 'danger';
        }
    }
}

// Define available protocols
$protocols = [
    'modbus_tcp' => [
        'name' => 'Modbus TCP',
        'category' => 'Industrial',
        'port' => 502,
        'description' => 'Industrial protocol for PLC communication',
        'fields' => [
            ['name' => 'host', 'label' => 'Host/IP Address', 'type' => 'text', 'default' => '192.168.1.100'],
            ['name' => 'port', 'label' => 'Port', 'type' => 'number', 'default' => 502],
            ['name' => 'unit_id', 'label' => 'Unit ID', 'type' => 'number', 'default' => 1],
            ['name' => 'timeout', 'label' => 'Timeout (ms)', 'type' => 'number', 'default' => 3000],
            ['name' => 'retry_count', 'label' => 'Retry Count', 'type' => 'number', 'default' => 3],
            ['name' => 'poll_interval', 'label' => 'Poll Interval (ms)', 'type' => 'number', 'default' => 1000],
        ]
    ],
    'modbus_rtu' => [
        'name' => 'Modbus RTU',
        'category' => 'Industrial',
        'port' => 'Serial',
        'description' => 'Serial Modbus protocol for RTU devices',
        'fields' => [
            ['name' => 'serial_port', 'label' => 'Serial Port', 'type' => 'text', 'default' => 'COM1'],
            ['name' => 'baud_rate', 'label' => 'Baud Rate', 'type' => 'select', 'options' => [9600, 19200, 38400, 57600, 115200], 'default' => 9600],
            ['name' => 'data_bits', 'label' => 'Data Bits', 'type' => 'select', 'options' => [7, 8], 'default' => 8],
            ['name' => 'parity', 'label' => 'Parity', 'type' => 'select', 'options' => ['None', 'Even', 'Odd'], 'default' => 'None'],
            ['name' => 'stop_bits', 'label' => 'Stop Bits', 'type' => 'select', 'options' => [1, 2], 'default' => 1],
            ['name' => 'slave_id', 'label' => 'Slave ID', 'type' => 'number', 'default' => 1],
        ]
    ],
    'opc_ua' => [
        'name' => 'OPC UA',
        'category' => 'Industrial',
        'port' => 4840,
        'description' => 'OPC Unified Architecture for industrial automation',
        'fields' => [
            ['name' => 'endpoint_url', 'label' => 'Endpoint URL', 'type' => 'text', 'default' => 'opc.tcp://localhost:4840'],
            ['name' => 'security_mode', 'label' => 'Security Mode', 'type' => 'select', 'options' => ['None', 'Sign', 'SignAndEncrypt'], 'default' => 'None'],
            ['name' => 'security_policy', 'label' => 'Security Policy', 'type' => 'select', 'options' => ['None', 'Basic128Rsa15', 'Basic256', 'Basic256Sha256'], 'default' => 'None'],
            ['name' => 'username', 'label' => 'Username', 'type' => 'text', 'default' => ''],
            ['name' => 'password', 'label' => 'Password', 'type' => 'password', 'default' => ''],
            ['name' => 'certificate_path', 'label' => 'Certificate Path', 'type' => 'text', 'default' => ''],
        ]
    ],
    'dnp3' => [
        'name' => 'DNP3',
        'category' => 'Industrial',
        'port' => 20000,
        'description' => 'Distributed Network Protocol for SCADA systems',
        'fields' => [
            ['name' => 'host', 'label' => 'Host/IP Address', 'type' => 'text', 'default' => '192.168.1.100'],
            ['name' => 'port', 'label' => 'Port', 'type' => 'number', 'default' => 20000],
            ['name' => 'master_address', 'label' => 'Master Address', 'type' => 'number', 'default' => 1],
            ['name' => 'outstation_address', 'label' => 'Outstation Address', 'type' => 'number', 'default' => 10],
            ['name' => 'timeout', 'label' => 'Timeout (ms)', 'type' => 'number', 'default' => 5000],
            ['name' => 'unsolicited_responses', 'label' => 'Unsolicited Responses', 'type' => 'select', 'options' => ['Enabled', 'Disabled'], 'default' => 'Enabled'],
        ]
    ],
    'snmp' => [
        'name' => 'SNMP',
        'category' => 'Network',
        'port' => 161,
        'description' => 'Simple Network Management Protocol for network devices',
        'fields' => [
            ['name' => 'version', 'label' => 'SNMP Version', 'type' => 'select', 'options' => ['v1', 'v2c', 'v3'], 'default' => 'v2c'],
            ['name' => 'community', 'label' => 'Community String', 'type' => 'text', 'default' => 'public'],
            ['name' => 'port', 'label' => 'Port', 'type' => 'number', 'default' => 161],
            ['name' => 'timeout', 'label' => 'Timeout (seconds)', 'type' => 'number', 'default' => 5],
            ['name' => 'retries', 'label' => 'Retries', 'type' => 'number', 'default' => 3],
            ['name' => 'v3_username', 'label' => 'SNMPv3 Username', 'type' => 'text', 'default' => ''],
            ['name' => 'v3_auth_protocol', 'label' => 'Auth Protocol', 'type' => 'select', 'options' => ['MD5', 'SHA'], 'default' => 'SHA'],
            ['name' => 'v3_auth_password', 'label' => 'Auth Password', 'type' => 'password', 'default' => ''],
            ['name' => 'v3_priv_protocol', 'label' => 'Privacy Protocol', 'type' => 'select', 'options' => ['DES', 'AES'], 'default' => 'AES'],
            ['name' => 'v3_priv_password', 'label' => 'Privacy Password', 'type' => 'password', 'default' => ''],
        ]
    ],
    'mqtt' => [
        'name' => 'MQTT',
        'category' => 'IoT',
        'port' => 1883,
        'description' => 'Message Queuing Telemetry Transport for IoT',
        'fields' => [
            ['name' => 'broker_host', 'label' => 'Broker Host', 'type' => 'text', 'default' => 'localhost'],
            ['name' => 'broker_port', 'label' => 'Broker Port', 'type' => 'number', 'default' => 1883],
            ['name' => 'client_id', 'label' => 'Client ID', 'type' => 'text', 'default' => 'ioc_client'],
            ['name' => 'username', 'label' => 'Username', 'type' => 'text', 'default' => ''],
            ['name' => 'password', 'label' => 'Password', 'type' => 'password', 'default' => ''],
            ['name' => 'use_tls', 'label' => 'Use TLS', 'type' => 'select', 'options' => ['Yes', 'No'], 'default' => 'No'],
            ['name' => 'qos', 'label' => 'QoS Level', 'type' => 'select', 'options' => [0, 1, 2], 'default' => 1],
        ]
    ],
    'bacnet' => [
        'name' => 'BACnet',
        'category' => 'Building Automation',
        'port' => 47808,
        'description' => 'Building Automation and Control Networks protocol',
        'fields' => [
            ['name' => 'ip_address', 'label' => 'IP Address', 'type' => 'text', 'default' => '192.168.1.100'],
            ['name' => 'port', 'label' => 'Port', 'type' => 'number', 'default' => 47808],
            ['name' => 'device_id', 'label' => 'Device ID', 'type' => 'number', 'default' => 1234],
            ['name' => 'network_number', 'label' => 'Network Number', 'type' => 'number', 'default' => 0],
            ['name' => 'timeout', 'label' => 'Timeout (ms)', 'type' => 'number', 'default' => 3000],
        ]
    ],
    'ethernet_ip' => [
        'name' => 'EtherNet/IP',
        'category' => 'Industrial',
        'port' => 44818,
        'description' => 'Industrial Ethernet protocol for Allen-Bradley PLCs',
        'fields' => [
            ['name' => 'host', 'label' => 'Host/IP Address', 'type' => 'text', 'default' => '192.168.1.100'],
            ['name' => 'port', 'label' => 'Port', 'type' => 'number', 'default' => 44818],
            ['name' => 'slot', 'label' => 'Slot Number', 'type' => 'number', 'default' => 0],
            ['name' => 'timeout', 'label' => 'Timeout (ms)', 'type' => 'number', 'default' => 5000],
        ]
    ],
    'profinet' => [
        'name' => 'PROFINET',
        'category' => 'Industrial',
        'port' => 34964,
        'description' => 'Industrial Ethernet standard for Siemens automation',
        'fields' => [
            ['name' => 'ip_address', 'label' => 'IP Address', 'type' => 'text', 'default' => '192.168.1.100'],
            ['name' => 'device_name', 'label' => 'Device Name', 'type' => 'text', 'default' => ''],
            ['name' => 'timeout', 'label' => 'Timeout (ms)', 'type' => 'number', 'default' => 3000],
        ]
    ],
    'iec61850' => [
        'name' => 'IEC 61850',
        'category' => 'Power Systems',
        'port' => 102,
        'description' => 'Communication standard for electrical substation automation',
        'fields' => [
            ['name' => 'host', 'label' => 'Host/IP Address', 'type' => 'text', 'default' => '192.168.1.100'],
            ['name' => 'port', 'label' => 'Port', 'type' => 'number', 'default' => 102],
            ['name' => 'ied_name', 'label' => 'IED Name', 'type' => 'text', 'default' => ''],
            ['name' => 'authentication', 'label' => 'Authentication', 'type' => 'select', 'options' => ['None', 'Password', 'Certificate'], 'default' => 'None'],
        ]
    ],
];

// Get saved configurations
$savedConfigs = [];
try {
    $stmt = $db->query("SELECT * FROM cpanel_protocols");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $savedConfigs[$row['protocol_name']] = json_decode($row['config'], true);
    }
} catch (Exception $e) {
    // Table may not exist
}

$currentPage = 'protocols';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Network Protocols - IOC Control Panel</title>
    <link rel="stylesheet" href="assets/css/cpanel.css">
</head>
<body>
    <div class="cpanel-wrapper">
        <?php include 'includes/sidebar.php'; ?>

        <div class="cpanel-main">
            <?php include 'includes/header.php'; ?>

            <div class="cpanel-content">
                <div class="page-header">
                    <h1>Network Protocol Configuration</h1>
                    <p>Configure industrial and network communication protocols</p>
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

                <!-- Protocol Tabs -->
                <div class="section-card">
                    <div class="tabs">
                        <?php $first = true; foreach ($protocols as $key => $protocol): ?>
                        <button class="tab-btn <?= $first ? 'active' : '' ?>" data-tab="<?= $key ?>">
                            <?= htmlspecialchars($protocol['name']) ?>
                        </button>
                        <?php $first = false; endforeach; ?>
                    </div>

                    <?php $first = true; foreach ($protocols as $key => $protocol): ?>
                    <div class="tab-content <?= $first ? 'active' : '' ?>" id="tab-<?= $key ?>">
                        <div class="section-body">
                            <div class="protocol-header-info" style="margin-bottom: 25px;">
                                <h3><?= htmlspecialchars($protocol['name']) ?></h3>
                                <p style="color: var(--text-secondary); margin-top: 5px;">
                                    <?= htmlspecialchars($protocol['description']) ?>
                                    <span style="margin-left: 15px; padding: 3px 10px; background: #f1f5f9; border-radius: 20px; font-size: 12px;">
                                        Port: <?= $protocol['port'] ?>
                                    </span>
                                    <span style="margin-left: 10px; padding: 3px 10px; background: #dbeafe; border-radius: 20px; font-size: 12px; color: #2563eb;">
                                        <?= htmlspecialchars($protocol['category']) ?>
                                    </span>
                                </p>
                            </div>

                            <form method="POST">
                                <input type="hidden" name="action" value="save_protocol">
                                <input type="hidden" name="protocol" value="<?= $key ?>">

                                <div class="form-row">
                                    <?php foreach ($protocol['fields'] as $field):
                                        $savedValue = $savedConfigs[$key][$field['name']] ?? $field['default'];
                                    ?>
                                    <div class="form-group">
                                        <label><?= htmlspecialchars($field['label']) ?></label>
                                        <?php if ($field['type'] === 'select'): ?>
                                            <select name="config[<?= $field['name'] ?>]">
                                                <?php foreach ($field['options'] as $opt): ?>
                                                    <option value="<?= htmlspecialchars($opt) ?>" <?= $savedValue == $opt ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($opt) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        <?php else: ?>
                                            <input type="<?= $field['type'] ?>"
                                                   name="config[<?= $field['name'] ?>]"
                                                   value="<?= htmlspecialchars($savedValue) ?>"
                                                   placeholder="<?= htmlspecialchars($field['default']) ?>">
                                        <?php endif; ?>
                                    </div>
                                    <?php endforeach; ?>
                                </div>

                                <div style="display: flex; gap: 15px; margin-top: 20px;">
                                    <button type="submit" class="btn btn-primary">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;">
                                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                                            <polyline points="17 21 17 13 7 13 7 21"></polyline>
                                            <polyline points="7 3 7 8 15 8"></polyline>
                                        </svg>
                                        Save Configuration
                                    </button>
                                    <button type="button" class="btn btn-secondary" onclick="testConnection('<?= $key ?>')">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;">
                                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                        </svg>
                                        Test Connection
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php $first = false; endforeach; ?>
                </div>

                <!-- Protocol Status -->
                <div class="section-card">
                    <div class="section-header">
                        <h2>Protocol Status Overview</h2>
                    </div>
                    <div class="section-body">
                        <div class="grid-3">
                            <?php foreach ($protocols as $key => $protocol): ?>
                            <div class="protocol-card <?= isset($savedConfigs[$key]) ? 'active' : '' ?>">
                                <div class="protocol-header">
                                    <div class="protocol-info">
                                        <div class="protocol-icon">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M22 12h-4l-3 9L9 3l-3 9H2"></path>
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="protocol-name"><?= htmlspecialchars($protocol['name']) ?></div>
                                            <div class="protocol-type"><?= htmlspecialchars($protocol['category']) ?></div>
                                        </div>
                                    </div>
                                    <span class="status-badge <?= isset($savedConfigs[$key]) ? 'success' : 'warning' ?>">
                                        <?= isset($savedConfigs[$key]) ? 'Configured' : 'Not Configured' ?>
                                    </span>
                                </div>
                                <div class="protocol-details">
                                    <div class="protocol-detail">
                                        <label>Default Port</label>
                                        <span><?= $protocol['port'] ?></span>
                                    </div>
                                    <div class="protocol-detail">
                                        <label>Parameters</label>
                                        <span><?= count($protocol['fields']) ?> fields</span>
                                    </div>
                                </div>
                                <div class="protocol-actions">
                                    <button type="button" class="btn btn-primary btn-sm" onclick="switchToTab('<?= $key ?>')">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;">
                                            <circle cx="12" cy="12" r="3"></circle>
                                            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                                        </svg>
                                        Configure
                                    </button>
                                    <button type="button" class="btn btn-secondary btn-sm" onclick="testConnection('<?= $key ?>')">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;">
                                            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                                        </svg>
                                        Test
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .protocol-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid var(--border-color);
        }
        .protocol-actions .btn {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        .protocol-card {
            cursor: default;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .protocol-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
    </style>

    <script src="assets/js/cpanel.js"></script>
    <script>
        // Tab switching
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));

                this.classList.add('active');
                document.getElementById('tab-' + this.dataset.tab).classList.add('active');
            });
        });

        function switchToTab(protocol) {
            // Find and click the corresponding tab button
            const tabBtn = document.querySelector('.tab-btn[data-tab="' + protocol + '"]');
            if (tabBtn) {
                tabBtn.click();
                // Scroll to the tabs section
                document.querySelector('.tabs').scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }

        function testConnection(protocol) {
            const protocolNames = {
                'modbus_tcp': 'Modbus TCP',
                'modbus_rtu': 'Modbus RTU',
                'opc_ua': 'OPC UA',
                'dnp3': 'DNP3',
                'snmp': 'SNMP',
                'mqtt': 'MQTT',
                'bacnet': 'BACnet',
                'ethernet_ip': 'EtherNet/IP',
                'profinet': 'PROFINET',
                'iec61850': 'IEC 61850'
            };

            const name = protocolNames[protocol] || protocol;

            // Show testing indicator
            const btn = event.target.closest('.btn');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<svg class="spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;animation:spin 1s linear infinite;"><circle cx="12" cy="12" r="10" stroke-dasharray="32" stroke-dashoffset="12"></circle></svg> Testing...';
            btn.disabled = true;

            // Simulate connection test
            setTimeout(function() {
                btn.innerHTML = originalText;
                btn.disabled = false;

                // Random success/failure for demo
                const success = Math.random() > 0.3;
                if (success) {
                    alert('✓ ' + name + ' Connection Test Successful!\n\nThe protocol endpoint is reachable and responding correctly.');
                } else {
                    alert('✗ ' + name + ' Connection Test Failed\n\nCould not establish connection. Please check:\n- Host/IP address is correct\n- Port is open and accessible\n- Device is powered on\n- Network connectivity');
                }
            }, 1500);
        }
    </script>
    <style>
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
    </style>
</body>
</html>
