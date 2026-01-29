<?php
/**
 * CMS Admin Portal - Settings Page
 */
session_start();

// Check authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../classes/Database.php';

$db = Database::getInstance();

// Get current settings
$settings = [];
$result = $db->fetchAll("SELECT * FROM settings");
foreach ($result as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'upload_logo') {
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'svg'];
            $filename = $_FILES['logo']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            if (in_array($ext, $allowed)) {
                $newname = 'logo.' . $ext;
                $upload_path = __DIR__ . '/../assets/uploads/' . $newname;

                // Create upload directory if not exists
                if (!is_dir(__DIR__ . '/../assets/uploads')) {
                    mkdir(__DIR__ . '/../assets/uploads', 0755, true);
                }

                if (move_uploaded_file($_FILES['logo']['tmp_name'], $upload_path)) {
                    // Save to database
                    $logo_url = '/networkscan/assets/uploads/' . $newname;
                    $db->query(
                        "INSERT INTO settings (setting_key, setting_value) VALUES ('logo_url', ?)
                         ON DUPLICATE KEY UPDATE setting_value = ?",
                        [$logo_url, $logo_url]
                    );

                    $message = 'Logo uploaded successfully!';
                    $messageType = 'success';
                    $settings['logo_url'] = $logo_url;
                } else {
                    $message = 'Failed to upload logo';
                    $messageType = 'error';
                }
            } else {
                $message = 'Invalid file type. Allowed: JPG, PNG, GIF, SVG';
                $messageType = 'error';
            }
        }
    } elseif ($action === 'update_settings') {
        $app_name = $_POST['app_name'] ?? 'Network Security Scanner';
        $company_name = $_POST['company_name'] ?? '';
        $support_email = $_POST['support_email'] ?? '';
        $theme_color = $_POST['theme_color'] ?? '#667eea';

        $settingsToUpdate = [
            'app_name' => $app_name,
            'company_name' => $company_name,
            'support_email' => $support_email,
            'theme_color' => $theme_color
        ];

        foreach ($settingsToUpdate as $key => $value) {
            $db->query(
                "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE setting_value = ?",
                [$key, $value, $value]
            );
            $settings[$key] = $value;
        }

        $message = 'Settings updated successfully!';
        $messageType = 'success';
    } elseif ($action === 'update_alert_settings') {
        $alertSettings = [
            'alert_email' => $_POST['alert_email'] ?? '',
            'alert_sms_number' => $_POST['alert_sms_number'] ?? '',
            'alert_webhook_url' => $_POST['alert_webhook_url'] ?? '',
            'alert_critical' => isset($_POST['alert_critical']) ? '1' : '0',
            'alert_high' => isset($_POST['alert_high']) ? '1' : '0',
            'alert_enable_email' => isset($_POST['alert_enable_email']) ? '1' : '0',
            'alert_enable_sms' => isset($_POST['alert_enable_sms']) ? '1' : '0',
            'alert_enable_webhook' => isset($_POST['alert_enable_webhook']) ? '1' : '0',
        ];

        foreach ($alertSettings as $key => $value) {
            $db->query(
                "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE setting_value = ?",
                [$key, $value, $value]
            );
            $settings[$key] = $value;
        }

        $message = 'Alert settings updated successfully!';
        $messageType = 'success';
    } elseif ($action === 'update_monitoring_settings') {
        $monitoringSettings = [
            'snmp_community' => $_POST['snmp_community'] ?? 'public',
            'snmp_version' => $_POST['snmp_version'] ?? 'v2c',
            'snmp_port' => $_POST['snmp_port'] ?? '161',
            'snmp_timeout' => $_POST['snmp_timeout'] ?? '5',
            'netflow_enabled' => isset($_POST['netflow_enabled']) ? '1' : '0',
            'netflow_port' => $_POST['netflow_port'] ?? '2055',
            'sflow_enabled' => isset($_POST['sflow_enabled']) ? '1' : '0',
            'sflow_port' => $_POST['sflow_port'] ?? '6343',
            'performance_polling_interval' => $_POST['performance_polling_interval'] ?? '60',
        ];

        foreach ($monitoringSettings as $key => $value) {
            $db->query(
                "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE setting_value = ?",
                [$key, $value, $value]
            );
            $settings[$key] = $value;
        }

        $message = 'Monitoring settings updated successfully!';
        $messageType = 'success';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Admin Portal</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
        }

        .tab-btn {
            padding: 12px 24px;
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            color: #666;
            transition: all 0.3s;
        }

        .tab-btn:hover {
            color: #667eea;
        }

        .tab-btn.active {
            color: #667eea;
            border-bottom-color: #667eea;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .setting-group {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .setting-group h3 {
            margin-bottom: 15px;
            color: #333;
        }

        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: 0.4s;
            border-radius: 24px;
        }

        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: 0.4s;
            border-radius: 50%;
        }

        .toggle-switch input:checked + .toggle-slider {
            background-color: #667eea;
        }

        .toggle-switch input:checked + .toggle-slider:before {
            transform: translateX(26px);
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container">
        <div class="main-content">
            <?php include 'sidebar.php'; ?>

            <div class="content">
                <h1>⚙️ System Settings</h1>

                <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
                <?php endif; ?>

                <!-- Tabs -->
                <div class="tabs">
                    <button class="tab-btn active" onclick="switchTab('general')">General</button>
                    <button class="tab-btn" onclick="switchTab('branding')">Branding</button>
                    <button class="tab-btn" onclick="switchTab('alerts')">Alerts & Notifications</button>
                    <button class="tab-btn" onclick="switchTab('monitoring')">Monitoring</button>
                </div>

                <!-- General Settings Tab -->
                <div id="general-tab" class="tab-content active">
                    <div class="card">
                        <h2>General Settings</h2>

                        <form method="POST">
                            <input type="hidden" name="action" value="update_settings">

                            <div class="form-group">
                                <label for="app_name">Application Name</label>
                                <input type="text" id="app_name" name="app_name"
                                       value="<?= htmlspecialchars($settings['app_name'] ?? 'Network Security Scanner') ?>">
                            </div>

                            <div class="form-group">
                                <label for="company_name">Company Name</label>
                                <input type="text" id="company_name" name="company_name"
                                       value="<?= htmlspecialchars($settings['company_name'] ?? '') ?>">
                            </div>

                            <div class="form-group">
                                <label for="support_email">Support Email</label>
                                <input type="email" id="support_email" name="support_email"
                                       value="<?= htmlspecialchars($settings['support_email'] ?? '') ?>">
                            </div>

                            <div class="form-group">
                                <label for="theme_color">Theme Color</label>
                                <input type="color" id="theme_color" name="theme_color"
                                       value="<?= htmlspecialchars($settings['theme_color'] ?? '#667eea') ?>">
                                <small>Primary color for the application theme</small>
                            </div>

                            <button type="submit" class="btn btn-primary">Save Settings</button>
                        </form>
                    </div>
                </div>

                <!-- Branding Tab -->
                <div id="branding-tab" class="tab-content">
                    <div class="card">
                        <h2>Company Logo</h2>
                        <p>Upload a logo to display on the dashboard header</p>

                        <?php if (isset($settings['logo_url']) && $settings['logo_url']): ?>
                        <div class="current-logo">
                            <img src="<?= htmlspecialchars($settings['logo_url']) ?>" alt="Current Logo" style="max-width: 300px; max-height: 150px; margin: 20px 0;">
                            <p><small>Current Logo</small></p>
                        </div>
                        <?php endif; ?>

                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="upload_logo">

                            <div class="form-group">
                                <label for="logo">Choose Logo (JPG, PNG, GIF, SVG)</label>
                                <input type="file" id="logo" name="logo" accept="image/*" required>
                                <small>Maximum file size: 2MB. Recommended dimensions: 200x60px</small>
                            </div>

                            <button type="submit" class="btn btn-primary">Upload Logo</button>
                        </form>
                    </div>
                </div>

                <!-- Alerts & Notifications Tab -->
                <div id="alerts-tab" class="tab-content">
                    <div class="card">
                        <h2>Alert Configuration</h2>
                        <p>Configure how and when the system sends alerts</p>

                        <form method="POST">
                            <input type="hidden" name="action" value="update_alert_settings">

                            <div class="setting-group">
                                <h3>Alert Channels</h3>

                                <div class="form-group" style="display: flex; align-items: center; gap: 15px;">
                                    <label class="toggle-switch">
                                        <input type="checkbox" name="alert_enable_email" <?= ($settings['alert_enable_email'] ?? '0') == '1' ? 'checked' : '' ?>>
                                        <span class="toggle-slider"></span>
                                    </label>
                                    <div style="flex: 1;">
                                        <strong>Email Notifications</strong>
                                        <p style="margin: 5px 0 0 0; font-size: 13px; color: #666;">Send alerts via email</p>
                                    </div>
                                </div>

                                <div class="form-group" style="margin-left: 65px;">
                                    <label for="alert_email">Alert Email Address</label>
                                    <input type="email" id="alert_email" name="alert_email"
                                           value="<?= htmlspecialchars($settings['alert_email'] ?? '') ?>"
                                           placeholder="alerts@company.com">
                                </div>

                                <hr style="margin: 20px 0;">

                                <div class="form-group" style="display: flex; align-items: center; gap: 15px;">
                                    <label class="toggle-switch">
                                        <input type="checkbox" name="alert_enable_sms" <?= ($settings['alert_enable_sms'] ?? '0') == '1' ? 'checked' : '' ?>>
                                        <span class="toggle-slider"></span>
                                    </label>
                                    <div style="flex: 1;">
                                        <strong>SMS Notifications</strong>
                                        <p style="margin: 5px 0 0 0; font-size: 13px; color: #666;">Send alerts via SMS (Twilio integration required)</p>
                                    </div>
                                </div>

                                <div class="form-group" style="margin-left: 65px;">
                                    <label for="alert_sms_number">SMS Phone Number</label>
                                    <input type="tel" id="alert_sms_number" name="alert_sms_number"
                                           value="<?= htmlspecialchars($settings['alert_sms_number'] ?? '') ?>"
                                           placeholder="+1234567890">
                                </div>

                                <hr style="margin: 20px 0;">

                                <div class="form-group" style="display: flex; align-items: center; gap: 15px;">
                                    <label class="toggle-switch">
                                        <input type="checkbox" name="alert_enable_webhook" <?= ($settings['alert_enable_webhook'] ?? '0') == '1' ? 'checked' : '' ?>>
                                        <span class="toggle-slider"></span>
                                    </label>
                                    <div style="flex: 1;">
                                        <strong>Webhook Notifications</strong>
                                        <p style="margin: 5px 0 0 0; font-size: 13px; color: #666;">Send alerts to external webhook (Slack, Teams, custom)</p>
                                    </div>
                                </div>

                                <div class="form-group" style="margin-left: 65px;">
                                    <label for="alert_webhook_url">Webhook URL</label>
                                    <input type="url" id="alert_webhook_url" name="alert_webhook_url"
                                           value="<?= htmlspecialchars($settings['alert_webhook_url'] ?? '') ?>"
                                           placeholder="https://hooks.slack.com/services/...">
                                </div>
                            </div>

                            <div class="setting-group">
                                <h3>Alert Triggers</h3>

                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" name="alert_critical" <?= ($settings['alert_critical'] ?? '1') == '1' ? 'checked' : '' ?>>
                                        Alert on Critical Vulnerabilities
                                    </label>
                                </div>

                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" name="alert_high" <?= ($settings['alert_high'] ?? '0') == '1' ? 'checked' : '' ?>>
                                        Alert on High Severity Vulnerabilities
                                    </label>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary">Save Alert Settings</button>
                        </form>
                    </div>
                </div>

                <!-- Monitoring Tab -->
                <div id="monitoring-tab" class="tab-content">
                    <div class="card">
                        <h2>Monitoring Configuration</h2>
                        <p>Configure SNMP, NetFlow/sFlow, and performance monitoring</p>

                        <form method="POST">
                            <input type="hidden" name="action" value="update_monitoring_settings">

                            <div class="setting-group">
                                <h3>SNMP Settings</h3>

                                <div class="form-group">
                                    <label for="snmp_version">SNMP Version</label>
                                    <select id="snmp_version" name="snmp_version">
                                        <option value="v1" <?= ($settings['snmp_version'] ?? 'v2c') == 'v1' ? 'selected' : '' ?>>SNMPv1</option>
                                        <option value="v2c" <?= ($settings['snmp_version'] ?? 'v2c') == 'v2c' ? 'selected' : '' ?>>SNMPv2c</option>
                                        <option value="v3" <?= ($settings['snmp_version'] ?? 'v2c') == 'v3' ? 'selected' : '' ?>>SNMPv3</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="snmp_community">SNMP Community String</label>
                                    <input type="text" id="snmp_community" name="snmp_community"
                                           value="<?= htmlspecialchars($settings['snmp_community'] ?? 'public') ?>">
                                </div>

                                <div class="form-group">
                                    <label for="snmp_port">SNMP Port</label>
                                    <input type="number" id="snmp_port" name="snmp_port"
                                           value="<?= htmlspecialchars($settings['snmp_port'] ?? '161') ?>">
                                </div>

                                <div class="form-group">
                                    <label for="snmp_timeout">SNMP Timeout (seconds)</label>
                                    <input type="number" id="snmp_timeout" name="snmp_timeout"
                                           value="<?= htmlspecialchars($settings['snmp_timeout'] ?? '5') ?>">
                                </div>
                            </div>

                            <div class="setting-group">
                                <h3>Flow Monitoring</h3>

                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" name="netflow_enabled" <?= ($settings['netflow_enabled'] ?? '0') == '1' ? 'checked' : '' ?>>
                                        Enable NetFlow Collector
                                    </label>
                                </div>

                                <div class="form-group">
                                    <label for="netflow_port">NetFlow Port</label>
                                    <input type="number" id="netflow_port" name="netflow_port"
                                           value="<?= htmlspecialchars($settings['netflow_port'] ?? '2055') ?>">
                                </div>

                                <hr style="margin: 15px 0;">

                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" name="sflow_enabled" <?= ($settings['sflow_enabled'] ?? '0') == '1' ? 'checked' : '' ?>>
                                        Enable sFlow Collector
                                    </label>
                                </div>

                                <div class="form-group">
                                    <label for="sflow_port">sFlow Port</label>
                                    <input type="number" id="sflow_port" name="sflow_port"
                                           value="<?= htmlspecialchars($settings['sflow_port'] ?? '6343') ?>">
                                </div>
                            </div>

                            <div class="setting-group">
                                <h3>Performance Monitoring</h3>

                                <div class="form-group">
                                    <label for="performance_polling_interval">Polling Interval (seconds)</label>
                                    <input type="number" id="performance_polling_interval" name="performance_polling_interval"
                                           value="<?= htmlspecialchars($settings['performance_polling_interval'] ?? '60') ?>">
                                    <small>How often to collect performance metrics (CPU, Memory, Bandwidth)</small>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary">Save Monitoring Settings</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function switchTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });

            // Remove active from all buttons
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });

            // Show selected tab
            document.getElementById(tabName + '-tab').classList.add('active');

            // Activate button
            event.target.classList.add('active');
        }
    </script>
</body>
</html>
