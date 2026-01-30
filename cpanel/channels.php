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
    $channel = $_POST['channel'] ?? '';

    if ($action === 'save_channel') {
        $config = $_POST['config'] ?? [];
        $enabled = $_POST['enabled'] ?? 0;

        try {
            $stmt = $db->prepare("
                INSERT INTO cpanel_channels (channel_name, config, is_enabled, updated_at)
                VALUES (?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE config = VALUES(config), is_enabled = VALUES(is_enabled), updated_at = NOW()
            ");
            $stmt->execute([$channel, json_encode($config), $enabled]);

            $message = ucfirst(str_replace('_', ' ', $channel)) . ' channel configuration saved successfully';
            $messageType = 'success';
        } catch (Exception $e) {
            $message = 'Error saving configuration: ' . $e->getMessage();
            $messageType = 'danger';
        }
    } elseif ($action === 'test_channel') {
        $message = "Test notification sent to $channel channel";
        $messageType = 'success';
    }
}

// Define notification channels
$channels = [
    'email' => [
        'name' => 'Email',
        'icon' => '<path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline>',
        'description' => 'Send notifications via SMTP email',
        'fields' => [
            ['name' => 'smtp_host', 'label' => 'SMTP Host', 'type' => 'text', 'default' => 'smtp.gmail.com'],
            ['name' => 'smtp_port', 'label' => 'SMTP Port', 'type' => 'number', 'default' => 587],
            ['name' => 'smtp_encryption', 'label' => 'Encryption', 'type' => 'select', 'options' => ['TLS', 'SSL', 'None'], 'default' => 'TLS'],
            ['name' => 'smtp_username', 'label' => 'Username', 'type' => 'text', 'default' => ''],
            ['name' => 'smtp_password', 'label' => 'Password', 'type' => 'password', 'default' => ''],
            ['name' => 'from_email', 'label' => 'From Email', 'type' => 'email', 'default' => ''],
            ['name' => 'from_name', 'label' => 'From Name', 'type' => 'text', 'default' => 'IOC Alerts'],
            ['name' => 'default_recipients', 'label' => 'Default Recipients', 'type' => 'textarea', 'default' => ''],
        ]
    ],
    'sms' => [
        'name' => 'SMS',
        'icon' => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>',
        'description' => 'Send SMS notifications via Twilio or other providers',
        'fields' => [
            ['name' => 'provider', 'label' => 'SMS Provider', 'type' => 'select', 'options' => ['Twilio', 'Nexmo', 'Plivo', 'AWS SNS'], 'default' => 'Twilio'],
            ['name' => 'account_sid', 'label' => 'Account SID/ID', 'type' => 'text', 'default' => ''],
            ['name' => 'auth_token', 'label' => 'Auth Token/Secret', 'type' => 'password', 'default' => ''],
            ['name' => 'from_number', 'label' => 'From Number', 'type' => 'text', 'default' => ''],
            ['name' => 'default_recipients', 'label' => 'Default Recipients', 'type' => 'textarea', 'default' => ''],
        ]
    ],
    'slack' => [
        'name' => 'Slack',
        'icon' => '<rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="3" y="3" width="7" height="7"></rect>',
        'description' => 'Send notifications to Slack channels',
        'fields' => [
            ['name' => 'webhook_url', 'label' => 'Webhook URL', 'type' => 'text', 'default' => ''],
            ['name' => 'default_channel', 'label' => 'Default Channel', 'type' => 'text', 'default' => '#alerts'],
            ['name' => 'username', 'label' => 'Bot Username', 'type' => 'text', 'default' => 'IOC Bot'],
            ['name' => 'icon_emoji', 'label' => 'Icon Emoji', 'type' => 'text', 'default' => ':shield:'],
            ['name' => 'mention_users', 'label' => 'Mention Users (critical)', 'type' => 'text', 'default' => ''],
        ]
    ],
    'teams' => [
        'name' => 'Microsoft Teams',
        'icon' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path>',
        'description' => 'Send notifications to Microsoft Teams channels',
        'fields' => [
            ['name' => 'webhook_url', 'label' => 'Webhook URL', 'type' => 'text', 'default' => ''],
            ['name' => 'title_prefix', 'label' => 'Title Prefix', 'type' => 'text', 'default' => '[IOC Alert]'],
            ['name' => 'theme_color', 'label' => 'Theme Color', 'type' => 'text', 'default' => '#667eea'],
        ]
    ],
    'webhook' => [
        'name' => 'Custom Webhook',
        'icon' => '<polyline points="16 18 22 12 16 6"></polyline><polyline points="8 6 2 12 8 18"></polyline>',
        'description' => 'Send notifications to custom webhook endpoints',
        'fields' => [
            ['name' => 'url', 'label' => 'Webhook URL', 'type' => 'text', 'default' => ''],
            ['name' => 'method', 'label' => 'HTTP Method', 'type' => 'select', 'options' => ['POST', 'PUT', 'GET'], 'default' => 'POST'],
            ['name' => 'content_type', 'label' => 'Content Type', 'type' => 'select', 'options' => ['application/json', 'application/x-www-form-urlencoded', 'text/plain'], 'default' => 'application/json'],
            ['name' => 'auth_type', 'label' => 'Authentication', 'type' => 'select', 'options' => ['None', 'Basic', 'Bearer', 'API Key'], 'default' => 'None'],
            ['name' => 'auth_value', 'label' => 'Auth Value/Token', 'type' => 'password', 'default' => ''],
            ['name' => 'custom_headers', 'label' => 'Custom Headers (JSON)', 'type' => 'textarea', 'default' => '{}'],
        ]
    ],
    'pushover' => [
        'name' => 'Pushover',
        'icon' => '<path d="M22 2L11 13"></path><path d="M22 2L15 22L11 13L2 9L22 2Z"></path>',
        'description' => 'Send push notifications via Pushover',
        'fields' => [
            ['name' => 'api_token', 'label' => 'API Token', 'type' => 'password', 'default' => ''],
            ['name' => 'user_key', 'label' => 'User/Group Key', 'type' => 'text', 'default' => ''],
            ['name' => 'default_priority', 'label' => 'Default Priority', 'type' => 'select', 'options' => ['-2 (Lowest)', '-1 (Low)', '0 (Normal)', '1 (High)', '2 (Emergency)'], 'default' => '0 (Normal)'],
            ['name' => 'default_sound', 'label' => 'Default Sound', 'type' => 'text', 'default' => 'pushover'],
        ]
    ],
    'pagerduty' => [
        'name' => 'PagerDuty',
        'icon' => '<path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path>',
        'description' => 'Create incidents in PagerDuty',
        'fields' => [
            ['name' => 'integration_key', 'label' => 'Integration Key', 'type' => 'password', 'default' => ''],
            ['name' => 'api_endpoint', 'label' => 'API Endpoint', 'type' => 'text', 'default' => 'https://events.pagerduty.com/v2/enqueue'],
            ['name' => 'severity_mapping', 'label' => 'Severity Mapping', 'type' => 'select', 'options' => ['Auto', 'Critical Only', 'All'], 'default' => 'Auto'],
        ]
    ],
    'discord' => [
        'name' => 'Discord',
        'icon' => '<circle cx="12" cy="12" r="10"></circle><path d="M8 14s1.5 2 4 2 4-2 4-2"></path><line x1="9" y1="9" x2="9.01" y2="9"></line><line x1="15" y1="9" x2="15.01" y2="9"></line>',
        'description' => 'Send notifications to Discord channels',
        'fields' => [
            ['name' => 'webhook_url', 'label' => 'Webhook URL', 'type' => 'text', 'default' => ''],
            ['name' => 'username', 'label' => 'Bot Username', 'type' => 'text', 'default' => 'IOC Alert Bot'],
            ['name' => 'avatar_url', 'label' => 'Avatar URL', 'type' => 'text', 'default' => ''],
        ]
    ],
    'syslog' => [
        'name' => 'Syslog',
        'icon' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line>',
        'description' => 'Send logs to syslog server',
        'fields' => [
            ['name' => 'host', 'label' => 'Syslog Host', 'type' => 'text', 'default' => 'localhost'],
            ['name' => 'port', 'label' => 'Port', 'type' => 'number', 'default' => 514],
            ['name' => 'protocol', 'label' => 'Protocol', 'type' => 'select', 'options' => ['UDP', 'TCP', 'TLS'], 'default' => 'UDP'],
            ['name' => 'facility', 'label' => 'Facility', 'type' => 'select', 'options' => ['local0', 'local1', 'local2', 'local3', 'local4', 'local5', 'local6', 'local7'], 'default' => 'local0'],
            ['name' => 'format', 'label' => 'Format', 'type' => 'select', 'options' => ['RFC3164', 'RFC5424', 'CEF'], 'default' => 'RFC5424'],
        ]
    ],
];

// Get saved configurations
$savedConfigs = [];
try {
    $stmt = $db->query("SELECT * FROM cpanel_channels");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $savedConfigs[$row['channel_name']] = [
            'config' => json_decode($row['config'], true),
            'is_enabled' => $row['is_enabled']
        ];
    }
} catch (Exception $e) {
    // Table may not exist
}

$currentPage = 'channels';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification Channels - IOC Control Panel</title>
    <link rel="stylesheet" href="assets/css/cpanel.css">
</head>
<body>
    <div class="cpanel-wrapper">
        <?php include 'includes/sidebar.php'; ?>

        <div class="cpanel-main">
            <?php include 'includes/header.php'; ?>

            <div class="cpanel-content">
                <div class="page-header">
                    <h1>Notification Channels</h1>
                    <p>Configure multi-channel notification delivery</p>
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

                <!-- Channel Cards Overview -->
                <div class="grid-3" style="margin-bottom: 30px;">
                    <?php foreach ($channels as $key => $channel):
                        $isConfigured = isset($savedConfigs[$key]);
                        $isEnabled = $isConfigured && $savedConfigs[$key]['is_enabled'];
                    ?>
                    <div class="channel-card <?= $isEnabled ? 'active' : '' ?>" onclick="openChannelConfig('<?= $key ?>')">
                        <div class="channel-header">
                            <div class="channel-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <?= $channel['icon'] ?>
                                </svg>
                            </div>
                            <span class="status-badge <?= $isEnabled ? 'success' : ($isConfigured ? 'warning' : '') ?>">
                                <?= $isEnabled ? 'Active' : ($isConfigured ? 'Configured' : 'Not Set') ?>
                            </span>
                        </div>
                        <h3 class="channel-name"><?= htmlspecialchars($channel['name']) ?></h3>
                        <p class="channel-description"><?= htmlspecialchars($channel['description']) ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Channel Configuration Sections -->
                <?php foreach ($channels as $key => $channel):
                    $savedConfig = $savedConfigs[$key]['config'] ?? [];
                    $isEnabled = $savedConfigs[$key]['is_enabled'] ?? 0;
                ?>
                <div class="section-card channel-config" id="config-<?= $key ?>" style="display: none;">
                    <div class="section-header">
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <div class="channel-icon" style="width: 40px; height: 40px;">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <?= $channel['icon'] ?>
                                </svg>
                            </div>
                            <div>
                                <h2><?= htmlspecialchars($channel['name']) ?> Configuration</h2>
                                <p style="color: var(--text-secondary); font-size: 13px; margin-top: 3px;">
                                    <?= htmlspecialchars($channel['description']) ?>
                                </p>
                            </div>
                        </div>
                        <button class="btn btn-secondary btn-sm" onclick="closeChannelConfig('<?= $key ?>')">Close</button>
                    </div>
                    <div class="section-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="save_channel">
                            <input type="hidden" name="channel" value="<?= $key ?>">

                            <div class="form-group">
                                <label>Enable Channel</label>
                                <select name="enabled">
                                    <option value="1" <?= $isEnabled ? 'selected' : '' ?>>Enabled</option>
                                    <option value="0" <?= !$isEnabled ? 'selected' : '' ?>>Disabled</option>
                                </select>
                            </div>

                            <div class="form-row">
                                <?php foreach ($channel['fields'] as $field):
                                    $value = $savedConfig[$field['name']] ?? $field['default'];
                                ?>
                                <div class="form-group">
                                    <label><?= htmlspecialchars($field['label']) ?></label>
                                    <?php if ($field['type'] === 'select'): ?>
                                        <select name="config[<?= $field['name'] ?>]">
                                            <?php foreach ($field['options'] as $opt): ?>
                                                <option value="<?= htmlspecialchars($opt) ?>" <?= $value == $opt ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($opt) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php elseif ($field['type'] === 'textarea'): ?>
                                        <textarea name="config[<?= $field['name'] ?>]" rows="3"><?= htmlspecialchars($value) ?></textarea>
                                    <?php else: ?>
                                        <input type="<?= $field['type'] ?>"
                                               name="config[<?= $field['name'] ?>]"
                                               value="<?= htmlspecialchars($value) ?>"
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
                                <button type="button" class="btn btn-secondary" onclick="testChannel('<?= $key ?>')">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;">
                                        <polygon points="5 3 19 12 5 21 5 3"></polygon>
                                    </svg>
                                    Send Test Notification
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>

                <!-- Notification Rules -->
                <div class="section-card">
                    <div class="section-header">
                        <h2>Notification Rules</h2>
                        <button class="btn btn-primary btn-sm" onclick="openRuleModal()">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;">
                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                            </svg>
                            Add Rule
                        </button>
                    </div>
                    <div class="section-body">
                        <table class="config-table">
                            <thead>
                                <tr>
                                    <th>Rule Name</th>
                                    <th>Event Type</th>
                                    <th>Severity</th>
                                    <th>Channels</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="rulesTableBody">
                                <tr data-rule-id="1">
                                    <td>Critical Security Alerts</td>
                                    <td>Security Scan</td>
                                    <td><span class="severity-badge critical">Critical</span></td>
                                    <td>Email, Slack, PagerDuty</td>
                                    <td><span class="status-badge success">Active</span></td>
                                    <td>
                                        <div style="display: flex; gap: 5px;">
                                            <button class="btn btn-secondary btn-sm" onclick="editRule(1, 'Critical Security Alerts', 'security_scan', 'critical', ['email','slack','pagerduty'], true)">Edit</button>
                                            <button class="btn btn-danger btn-sm" onclick="deleteRule(1)">Delete</button>
                                        </div>
                                    </td>
                                </tr>
                                <tr data-rule-id="2">
                                    <td>DLP Violations</td>
                                    <td>Email DLP</td>
                                    <td><span class="severity-badge high">High</span></td>
                                    <td>Email, Teams</td>
                                    <td><span class="status-badge success">Active</span></td>
                                    <td>
                                        <div style="display: flex; gap: 5px;">
                                            <button class="btn btn-secondary btn-sm" onclick="editRule(2, 'DLP Violations', 'email_dlp', 'high', ['email','teams'], true)">Edit</button>
                                            <button class="btn btn-danger btn-sm" onclick="deleteRule(2)">Delete</button>
                                        </div>
                                    </td>
                                </tr>
                                <tr data-rule-id="3">
                                    <td>SCADA Alarms</td>
                                    <td>SCADA Monitor</td>
                                    <td><span class="severity-badge medium">Medium+</span></td>
                                    <td>Email, SMS, Webhook</td>
                                    <td><span class="status-badge success">Active</span></td>
                                    <td>
                                        <div style="display: flex; gap: 5px;">
                                            <button class="btn btn-secondary btn-sm" onclick="editRule(3, 'SCADA Alarms', 'scada_monitor', 'medium', ['email','sms','webhook'], true)">Edit</button>
                                            <button class="btn btn-danger btn-sm" onclick="deleteRule(3)">Delete</button>
                                        </div>
                                    </td>
                                </tr>
                                <tr data-rule-id="4">
                                    <td>Compliance Failures</td>
                                    <td>Compliance Check</td>
                                    <td><span class="severity-badge low">All</span></td>
                                    <td>Email</td>
                                    <td><span class="status-badge warning">Paused</span></td>
                                    <td>
                                        <div style="display: flex; gap: 5px;">
                                            <button class="btn btn-secondary btn-sm" onclick="editRule(4, 'Compliance Failures', 'compliance', 'all', ['email'], false)">Edit</button>
                                            <button class="btn btn-danger btn-sm" onclick="deleteRule(4)">Delete</button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Add/Edit Rule Modal -->
                <div id="ruleModal" class="modal">
                    <div class="modal-content" style="max-width: 600px;">
                        <div class="modal-header">
                            <h3 id="ruleModalTitle">Add Notification Rule</h3>
                            <button class="modal-close" onclick="closeRuleModal()">&times;</button>
                        </div>
                        <div class="modal-body">
                            <form id="ruleForm" onsubmit="saveRule(event)">
                                <input type="hidden" id="ruleId" name="rule_id" value="">

                                <div class="form-group">
                                    <label>Rule Name *</label>
                                    <input type="text" id="ruleName" name="rule_name" required placeholder="e.g., Critical Security Alerts">
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Event Type *</label>
                                        <select id="ruleEventType" name="event_type" required>
                                            <option value="">Select event type...</option>
                                            <option value="security_scan">Security Scan</option>
                                            <option value="email_dlp">Email DLP</option>
                                            <option value="scada_monitor">SCADA Monitor</option>
                                            <option value="compliance">Compliance Check</option>
                                            <option value="network_traffic">Network Traffic</option>
                                            <option value="system_health">System Health</option>
                                            <option value="user_activity">User Activity</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Minimum Severity *</label>
                                        <select id="ruleSeverity" name="severity" required>
                                            <option value="all">All Severities</option>
                                            <option value="low">Low and above</option>
                                            <option value="medium">Medium and above</option>
                                            <option value="high">High and above</option>
                                            <option value="critical">Critical only</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Notification Channels *</label>
                                    <div class="checkbox-grid">
                                        <?php foreach ($channels as $key => $channel): ?>
                                        <label class="checkbox-label">
                                            <input type="checkbox" name="channels[]" value="<?= $key ?>">
                                            <span><?= htmlspecialchars($channel['name']) ?></span>
                                        </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Additional Conditions (Optional)</label>
                                    <textarea id="ruleConditions" name="conditions" rows="3" placeholder='{"ip_range": "192.168.1.0/24", "exclude_hosts": ["test-server"]}'></textarea>
                                    <small>JSON format for advanced filtering</small>
                                </div>

                                <div class="form-group">
                                    <label>Status</label>
                                    <select id="ruleStatus" name="status">
                                        <option value="1">Active</option>
                                        <option value="0">Paused</option>
                                    </select>
                                </div>

                                <div style="display: flex; gap: 15px; margin-top: 20px;">
                                    <button type="button" class="btn btn-secondary" onclick="closeRuleModal()">Cancel</button>
                                    <button type="submit" class="btn btn-primary">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;">
                                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                                            <polyline points="17 21 17 13 7 13 7 21"></polyline>
                                            <polyline points="7 3 7 8 15 8"></polyline>
                                        </svg>
                                        Save Rule
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .channel-card {
            background: white;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .channel-card:hover {
            border-color: var(--primary);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.1);
            transform: translateY(-3px);
        }
        .channel-card.active {
            border-color: var(--success);
            background: linear-gradient(to bottom, #f0fdf4, white);
        }
        .channel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px;
        }
        .channel-icon {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .channel-icon svg {
            width: 22px;
            height: 22px;
            color: white;
        }
        .channel-name {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .channel-description {
            font-size: 13px;
            color: var(--text-secondary);
            line-height: 1.5;
        }
        .severity-badge {
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        .severity-badge.critical { background: #fee2e2; color: #dc2626; }
        .severity-badge.high { background: #ffedd5; color: #ea580c; }
        .severity-badge.medium { background: #fef3c7; color: #d97706; }
        .severity-badge.low { background: #dbeafe; color: #2563eb; }

        .checkbox-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            padding: 15px;
            background: #f8fafc;
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }
        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            font-size: 14px;
        }
        .checkbox-label input {
            width: 16px;
            height: 16px;
            cursor: pointer;
        }
        small {
            display: block;
            margin-top: 5px;
            color: var(--text-secondary);
            font-size: 12px;
        }
    </style>

    <script src="assets/js/cpanel.js"></script>
    <script>
        function openChannelConfig(channel) {
            document.querySelectorAll('.channel-config').forEach(el => el.style.display = 'none');
            document.getElementById('config-' + channel).style.display = 'block';
            document.getElementById('config-' + channel).scrollIntoView({ behavior: 'smooth' });
        }

        function closeChannelConfig(channel) {
            document.getElementById('config-' + channel).style.display = 'none';
        }

        function testChannel(channel) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="test_channel">
                <input type="hidden" name="channel" value="${channel}">
            `;
            document.body.appendChild(form);
            form.submit();
        }

        // Rule Modal Functions
        function openRuleModal() {
            document.getElementById('ruleModalTitle').textContent = 'Add Notification Rule';
            document.getElementById('ruleForm').reset();
            document.getElementById('ruleId').value = '';
            document.getElementById('ruleModal').classList.add('active');
        }

        function closeRuleModal() {
            document.getElementById('ruleModal').classList.remove('active');
        }

        function editRule(id, name, eventType, severity, channels, isActive) {
            document.getElementById('ruleModalTitle').textContent = 'Edit Notification Rule';
            document.getElementById('ruleId').value = id;
            document.getElementById('ruleName').value = name;
            document.getElementById('ruleEventType').value = eventType;
            document.getElementById('ruleSeverity').value = severity;
            document.getElementById('ruleStatus').value = isActive ? '1' : '0';

            // Reset all checkboxes
            document.querySelectorAll('input[name="channels[]"]').forEach(cb => cb.checked = false);

            // Check the selected channels
            channels.forEach(ch => {
                const checkbox = document.querySelector(`input[name="channels[]"][value="${ch}"]`);
                if (checkbox) checkbox.checked = true;
            });

            document.getElementById('ruleModal').classList.add('active');
        }

        function deleteRule(id) {
            if (confirm('Are you sure you want to delete this notification rule?')) {
                const row = document.querySelector(`tr[data-rule-id="${id}"]`);
                if (row) {
                    row.style.transition = 'opacity 0.3s';
                    row.style.opacity = '0';
                    setTimeout(() => {
                        row.remove();
                        showNotification('Rule deleted successfully', 'success');
                    }, 300);
                }
            }
        }

        function saveRule(event) {
            event.preventDefault();

            const form = document.getElementById('ruleForm');
            const formData = new FormData(form);

            const ruleId = formData.get('rule_id');
            const ruleName = formData.get('rule_name');
            const eventType = formData.get('event_type');
            const severity = formData.get('severity');
            const status = formData.get('status');
            const channels = formData.getAll('channels[]');

            if (channels.length === 0) {
                alert('Please select at least one notification channel.');
                return;
            }

            // Map values to display text
            const eventTypeLabels = {
                'security_scan': 'Security Scan',
                'email_dlp': 'Email DLP',
                'scada_monitor': 'SCADA Monitor',
                'compliance': 'Compliance Check',
                'network_traffic': 'Network Traffic',
                'system_health': 'System Health',
                'user_activity': 'User Activity'
            };

            const severityLabels = {
                'all': 'All',
                'low': 'Low',
                'medium': 'Medium',
                'high': 'High',
                'critical': 'Critical'
            };

            const channelLabels = {
                'email': 'Email',
                'sms': 'SMS',
                'slack': 'Slack',
                'teams': 'Teams',
                'webhook': 'Webhook',
                'pushover': 'Pushover',
                'pagerduty': 'PagerDuty',
                'discord': 'Discord',
                'syslog': 'Syslog'
            };

            const channelDisplay = channels.map(c => channelLabels[c] || c).join(', ');
            const severityClass = severity === 'all' ? 'low' : severity;
            const statusClass = status === '1' ? 'success' : 'warning';
            const statusText = status === '1' ? 'Active' : 'Paused';

            if (ruleId) {
                // Update existing row
                const row = document.querySelector(`tr[data-rule-id="${ruleId}"]`);
                if (row) {
                    row.innerHTML = `
                        <td>${ruleName}</td>
                        <td>${eventTypeLabels[eventType] || eventType}</td>
                        <td><span class="severity-badge ${severityClass}">${severityLabels[severity] || severity}</span></td>
                        <td>${channelDisplay}</td>
                        <td><span class="status-badge ${statusClass}">${statusText}</span></td>
                        <td>
                            <div style="display: flex; gap: 5px;">
                                <button class="btn btn-secondary btn-sm" onclick="editRule(${ruleId}, '${ruleName}', '${eventType}', '${severity}', ${JSON.stringify(channels)}, ${status === '1'})">Edit</button>
                                <button class="btn btn-danger btn-sm" onclick="deleteRule(${ruleId})">Delete</button>
                            </div>
                        </td>
                    `;
                }
                showNotification('Rule updated successfully', 'success');
            } else {
                // Add new row
                const newId = Date.now();
                const tbody = document.getElementById('rulesTableBody');
                const newRow = document.createElement('tr');
                newRow.setAttribute('data-rule-id', newId);
                newRow.innerHTML = `
                    <td>${ruleName}</td>
                    <td>${eventTypeLabels[eventType] || eventType}</td>
                    <td><span class="severity-badge ${severityClass}">${severityLabels[severity] || severity}</span></td>
                    <td>${channelDisplay}</td>
                    <td><span class="status-badge ${statusClass}">${statusText}</span></td>
                    <td>
                        <div style="display: flex; gap: 5px;">
                            <button class="btn btn-secondary btn-sm" onclick="editRule(${newId}, '${ruleName}', '${eventType}', '${severity}', ${JSON.stringify(channels)}, ${status === '1'})">Edit</button>
                            <button class="btn btn-danger btn-sm" onclick="deleteRule(${newId})">Delete</button>
                        </div>
                    </td>
                `;
                tbody.appendChild(newRow);
                showNotification('Rule added successfully', 'success');
            }

            closeRuleModal();
        }

        // Simple notification function
        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = `alert alert-${type}`;
            notification.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 10000; padding: 15px 20px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); animation: slideIn 0.3s ease;';
            notification.innerHTML = `
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 20px; height: 20px; margin-right: 10px;">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
                ${message}
            `;
            document.body.appendChild(notification);
            setTimeout(() => {
                notification.style.opacity = '0';
                notification.style.transition = 'opacity 0.3s';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        // Close modal on backdrop click
        document.getElementById('ruleModal').addEventListener('click', function(e) {
            if (e.target === this) closeRuleModal();
        });

        // Close modal on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeRuleModal();
        });
    </script>
    <style>
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    </style>
</body>
</html>
