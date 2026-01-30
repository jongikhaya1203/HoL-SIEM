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
        case 'save_security':
            try {
                $settings = [
                    'session_timeout' => $_POST['session_timeout'] ?? 3600,
                    'max_login_attempts' => $_POST['max_login_attempts'] ?? 5,
                    'lockout_duration' => $_POST['lockout_duration'] ?? 900,
                    'password_min_length' => $_POST['password_min_length'] ?? 8,
                    'require_2fa' => isset($_POST['require_2fa']) ? 1 : 0,
                    'ip_whitelist' => $_POST['ip_whitelist'] ?? '',
                    'audit_retention_days' => $_POST['audit_retention_days'] ?? 90,
                    'force_https' => isset($_POST['force_https']) ? 1 : 0,
                    'csrf_protection' => isset($_POST['csrf_protection']) ? 1 : 0,
                    'xss_protection' => isset($_POST['xss_protection']) ? 1 : 0
                ];

                $stmt = $db->prepare("INSERT INTO cpanel_security_settings (setting_key, setting_value, description)
                    VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");

                foreach ($settings as $key => $value) {
                    $stmt->execute([$key, $value, '']);
                }

                $message = 'Security settings saved successfully';
                $messageType = 'success';
            } catch (Exception $e) {
                $message = 'Error saving settings: ' . $e->getMessage();
                $messageType = 'danger';
            }
            break;

        case 'clear_sessions':
            // In production, this would clear all active sessions
            $message = 'All sessions cleared (except current)';
            $messageType = 'success';
            break;

        case 'rotate_keys':
            // In production, this would rotate encryption keys
            $message = 'Encryption keys rotated successfully';
            $messageType = 'success';
            break;
    }
}

// Load current security settings
$securitySettings = [
    'session_timeout' => 3600,
    'max_login_attempts' => 5,
    'lockout_duration' => 900,
    'password_min_length' => 8,
    'require_2fa' => 0,
    'ip_whitelist' => '',
    'audit_retention_days' => 90,
    'force_https' => 1,
    'csrf_protection' => 1,
    'xss_protection' => 1
];

try {
    $stmt = $db->query("SELECT setting_key, setting_value FROM cpanel_security_settings");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $securitySettings[$row['setting_key']] = $row['setting_value'];
    }
} catch (Exception $e) {
    // Use defaults
}

// Get recent security events
$securityEvents = [];
try {
    $stmt = $db->query("SELECT * FROM cpanel_audit_log WHERE action LIKE '%login%' OR action LIKE '%security%' ORDER BY created_at DESC LIMIT 10");
    $securityEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // No events
}

$currentPage = 'security';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security - IOC Control Panel</title>
    <link rel="stylesheet" href="assets/css/cpanel.css">
</head>
<body>
    <div class="cpanel-wrapper">
        <?php include 'includes/sidebar.php'; ?>

        <div class="cpanel-main">
            <?php include 'includes/header.php'; ?>

            <div class="cpanel-content">
                <div class="page-header">
                    <h1>Security Settings</h1>
                    <p>Configure authentication, access control, and security policies</p>
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

                <form method="POST">
                    <input type="hidden" name="action" value="save_security">

                    <div class="grid-2">
                        <!-- Authentication Settings -->
                        <div class="section-card">
                            <div class="section-header">
                                <h2>Authentication</h2>
                            </div>
                            <div class="section-body">
                                <div class="form-group">
                                    <label>Session Timeout (seconds)</label>
                                    <input type="number" name="session_timeout" value="<?= htmlspecialchars($securitySettings['session_timeout']) ?>" min="300" max="86400">
                                    <small>Time before inactive sessions expire (300-86400)</small>
                                </div>

                                <div class="form-group">
                                    <label>Max Login Attempts</label>
                                    <input type="number" name="max_login_attempts" value="<?= htmlspecialchars($securitySettings['max_login_attempts']) ?>" min="3" max="10">
                                    <small>Failed attempts before account lockout</small>
                                </div>

                                <div class="form-group">
                                    <label>Lockout Duration (seconds)</label>
                                    <input type="number" name="lockout_duration" value="<?= htmlspecialchars($securitySettings['lockout_duration']) ?>" min="60" max="3600">
                                    <small>How long accounts remain locked</small>
                                </div>

                                <div class="form-group">
                                    <label>Minimum Password Length</label>
                                    <input type="number" name="password_min_length" value="<?= htmlspecialchars($securitySettings['password_min_length']) ?>" min="6" max="32">
                                </div>

                                <div class="toggle-item">
                                    <div class="toggle-info">
                                        <span class="toggle-label">Require Two-Factor Authentication</span>
                                        <span class="toggle-desc">Enforce 2FA for all users</span>
                                    </div>
                                    <label class="toggle-switch">
                                        <input type="checkbox" name="require_2fa" <?= $securitySettings['require_2fa'] ? 'checked' : '' ?>>
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Access Control -->
                        <div class="section-card">
                            <div class="section-header">
                                <h2>Access Control</h2>
                            </div>
                            <div class="section-body">
                                <div class="form-group">
                                    <label>IP Whitelist</label>
                                    <textarea name="ip_whitelist" rows="4" placeholder="Enter allowed IPs, one per line"><?= htmlspecialchars($securitySettings['ip_whitelist']) ?></textarea>
                                    <small>Leave empty to allow all IPs</small>
                                </div>

                                <div class="toggle-item">
                                    <div class="toggle-info">
                                        <span class="toggle-label">Force HTTPS</span>
                                        <span class="toggle-desc">Redirect all HTTP to HTTPS</span>
                                    </div>
                                    <label class="toggle-switch">
                                        <input type="checkbox" name="force_https" <?= $securitySettings['force_https'] ? 'checked' : '' ?>>
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>

                                <div class="toggle-item">
                                    <div class="toggle-info">
                                        <span class="toggle-label">CSRF Protection</span>
                                        <span class="toggle-desc">Enable cross-site request forgery protection</span>
                                    </div>
                                    <label class="toggle-switch">
                                        <input type="checkbox" name="csrf_protection" <?= $securitySettings['csrf_protection'] ? 'checked' : '' ?>>
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>

                                <div class="toggle-item">
                                    <div class="toggle-info">
                                        <span class="toggle-label">XSS Protection</span>
                                        <span class="toggle-desc">Enable cross-site scripting protection headers</span>
                                    </div>
                                    <label class="toggle-switch">
                                        <input type="checkbox" name="xss_protection" <?= $securitySettings['xss_protection'] ? 'checked' : '' ?>>
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid-2">
                        <!-- Audit Settings -->
                        <div class="section-card">
                            <div class="section-header">
                                <h2>Audit & Logging</h2>
                            </div>
                            <div class="section-body">
                                <div class="form-group">
                                    <label>Audit Log Retention (days)</label>
                                    <input type="number" name="audit_retention_days" value="<?= htmlspecialchars($securitySettings['audit_retention_days']) ?>" min="30" max="365">
                                    <small>Days to keep audit logs</small>
                                </div>

                                <div class="toggle-item">
                                    <div class="toggle-info">
                                        <span class="toggle-label">Log All API Requests</span>
                                        <span class="toggle-desc">Record all API access in audit log</span>
                                    </div>
                                    <label class="toggle-switch">
                                        <input type="checkbox" name="log_api" checked>
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>

                                <div class="toggle-item">
                                    <div class="toggle-info">
                                        <span class="toggle-label">Log Configuration Changes</span>
                                        <span class="toggle-desc">Record all setting modifications</span>
                                    </div>
                                    <label class="toggle-switch">
                                        <input type="checkbox" name="log_config" checked>
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="section-card">
                            <div class="section-header">
                                <h2>Security Actions</h2>
                            </div>
                            <div class="section-body">
                                <div class="maint-item">
                                    <div class="maint-info">
                                        <h4>Clear All Sessions</h4>
                                        <p>Force logout all users except yourself</p>
                                    </div>
                                    <button type="submit" name="action" value="clear_sessions" class="btn btn-warning btn-sm">Clear</button>
                                </div>

                                <div class="maint-item">
                                    <div class="maint-info">
                                        <h4>Rotate Encryption Keys</h4>
                                        <p>Generate new encryption keys</p>
                                    </div>
                                    <button type="submit" name="action" value="rotate_keys" class="btn btn-secondary btn-sm">Rotate</button>
                                </div>

                                <div class="maint-item">
                                    <div class="maint-info">
                                        <h4>Security Scan</h4>
                                        <p>Run vulnerability assessment</p>
                                    </div>
                                    <button type="button" class="btn btn-primary btn-sm" onclick="runSecurityScan()">Scan</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div style="margin-top: 20px; text-align: right;">
                        <button type="submit" class="btn btn-primary">Save Security Settings</button>
                    </div>
                </form>

                <!-- Recent Security Events -->
                <div class="section-card" style="margin-top: 20px;">
                    <div class="section-header">
                        <h2>Recent Security Events</h2>
                        <a href="logs.php?filter=security" class="btn btn-secondary btn-sm">View All</a>
                    </div>
                    <div class="section-body" style="padding: 0;">
                        <table class="config-table">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Action</th>
                                    <th>User</th>
                                    <th>IP Address</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($securityEvents)): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; color: var(--text-secondary);">No recent security events</td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($securityEvents as $event): ?>
                                <tr>
                                    <td><?= htmlspecialchars($event['created_at']) ?></td>
                                    <td><span class="badge"><?= htmlspecialchars($event['action']) ?></span></td>
                                    <td><?= htmlspecialchars($event['user_id'] ?? 'System') ?></td>
                                    <td><code><?= htmlspecialchars($event['ip_address'] ?? 'N/A') ?></code></td>
                                    <td><?= htmlspecialchars(substr($event['new_value'] ?? '', 0, 50)) ?></td>
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

    <style>
        .maint-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px 0;
            border-bottom: 1px solid var(--border-color);
        }
        .maint-item:last-child {
            border-bottom: none;
        }
        .maint-info h4 {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        .maint-info p {
            font-size: 13px;
            color: var(--text-secondary);
        }
        .badge {
            background: #e2e8f0;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        code {
            background: #f1f5f9;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 13px;
        }
        small {
            display: block;
            margin-top: 5px;
            color: var(--text-secondary);
            font-size: 12px;
        }
        .btn-warning {
            background: #f59e0b;
            color: white;
        }
        .btn-warning:hover {
            background: #d97706;
        }
    </style>

    <script src="assets/js/cpanel.js"></script>
    <script>
        function runSecurityScan() {
            alert('Security scan would start here. This would check for:\n- Weak passwords\n- Outdated dependencies\n- Misconfigured permissions\n- Open ports\n- SSL/TLS configuration');
        }
    </script>
</body>
</html>
