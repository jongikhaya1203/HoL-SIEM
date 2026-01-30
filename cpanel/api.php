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
        case 'save_api_settings':
            try {
                $settings = [
                    'api_enabled' => $_POST['api_enabled'] ?? 0,
                    'rate_limit' => $_POST['rate_limit'] ?? 100,
                    'rate_window' => $_POST['rate_window'] ?? 60,
                    'require_auth' => $_POST['require_auth'] ?? 1,
                    'auth_type' => $_POST['auth_type'] ?? 'api_key',
                    'cors_enabled' => $_POST['cors_enabled'] ?? 1,
                    'cors_origins' => $_POST['cors_origins'] ?? '*',
                    'log_requests' => $_POST['log_requests'] ?? 1,
                    'max_request_size' => $_POST['max_request_size'] ?? 10,
                    'timeout' => $_POST['timeout'] ?? 30,
                ];

                foreach ($settings as $key => $value) {
                    $stmt = $db->prepare("
                        INSERT INTO cpanel_settings (setting_key, setting_value, category)
                        VALUES (?, ?, 'api')
                        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
                    ");
                    $stmt->execute([$key, $value]);
                }

                $message = 'API settings saved successfully';
                $messageType = 'success';
            } catch (Exception $e) {
                $message = 'Error saving settings: ' . $e->getMessage();
                $messageType = 'danger';
            }
            break;

        case 'generate_api_key':
            $keyName = $_POST['key_name'] ?? 'New API Key';
            $permissions = $_POST['permissions'] ?? [];
            $apiKey = 'ioc_' . bin2hex(random_bytes(24));

            try {
                $stmt = $db->prepare("
                    INSERT INTO cpanel_api_keys (key_name, api_key, permissions, created_at)
                    VALUES (?, ?, ?, NOW())
                ");
                $stmt->execute([$keyName, password_hash($apiKey, PASSWORD_DEFAULT), json_encode($permissions)]);

                $message = "API Key generated: $apiKey (save this, it won't be shown again)";
                $messageType = 'success';
            } catch (Exception $e) {
                $message = 'Error generating API key: ' . $e->getMessage();
                $messageType = 'danger';
            }
            break;

        case 'toggle_endpoint':
            $endpointId = $_POST['endpoint_id'] ?? 0;
            $enabled = $_POST['enabled'] ?? 0;
            try {
                $stmt = $db->prepare("UPDATE cpanel_api_endpoints SET is_enabled = ? WHERE id = ?");
                $stmt->execute([$enabled, $endpointId]);
                $message = 'Endpoint status updated';
                $messageType = 'success';
            } catch (Exception $e) {
                $message = 'Error updating endpoint';
                $messageType = 'danger';
            }
            break;
    }
}

// Get current settings
$apiSettings = [
    'api_enabled' => 1,
    'rate_limit' => 100,
    'rate_window' => 60,
    'require_auth' => 1,
    'auth_type' => 'api_key',
    'cors_enabled' => 1,
    'cors_origins' => '*',
    'log_requests' => 1,
    'max_request_size' => 10,
    'timeout' => 30,
];

try {
    $stmt = $db->query("SELECT setting_key, setting_value FROM cpanel_settings WHERE category = 'api'");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $apiSettings[$row['setting_key']] = $row['setting_value'];
    }
} catch (Exception $e) {
    // Use defaults
}

// Define API endpoints
$endpoints = [
    ['id' => 1, 'method' => 'GET', 'path' => '/api/stats', 'description' => 'Get dashboard statistics', 'category' => 'Dashboard', 'is_enabled' => 1],
    ['id' => 2, 'method' => 'GET', 'path' => '/api/scans', 'description' => 'List all scans', 'category' => 'Scanning', 'is_enabled' => 1],
    ['id' => 3, 'method' => 'GET', 'path' => '/api/scan/{id}', 'description' => 'Get scan details', 'category' => 'Scanning', 'is_enabled' => 1],
    ['id' => 4, 'method' => 'POST', 'path' => '/api/scan/start', 'description' => 'Start a new scan', 'category' => 'Scanning', 'is_enabled' => 1],
    ['id' => 5, 'method' => 'GET', 'path' => '/api/vulnerabilities', 'description' => 'List vulnerabilities', 'category' => 'Security', 'is_enabled' => 1],
    ['id' => 6, 'method' => 'GET', 'path' => '/api/hosts', 'description' => 'List discovered hosts', 'category' => 'Network', 'is_enabled' => 1],
    ['id' => 7, 'method' => 'GET', 'path' => '/api/compliance', 'description' => 'Get compliance status', 'category' => 'Compliance', 'is_enabled' => 1],
    ['id' => 8, 'method' => 'POST', 'path' => '/api/report/generate', 'description' => 'Generate report', 'category' => 'Reporting', 'is_enabled' => 1],
    ['id' => 9, 'method' => 'GET', 'path' => '/api/alerts', 'description' => 'List active alerts', 'category' => 'Alerting', 'is_enabled' => 1],
    ['id' => 10, 'method' => 'POST', 'path' => '/api/alert/acknowledge', 'description' => 'Acknowledge alert', 'category' => 'Alerting', 'is_enabled' => 1],
    ['id' => 11, 'method' => 'GET', 'path' => '/api/scada/devices', 'description' => 'List SCADA devices', 'category' => 'SCADA', 'is_enabled' => 1],
    ['id' => 12, 'method' => 'GET', 'path' => '/api/scada/tags', 'description' => 'Read SCADA tags', 'category' => 'SCADA', 'is_enabled' => 1],
    ['id' => 13, 'method' => 'POST', 'path' => '/api/scada/write', 'description' => 'Write to SCADA tag', 'category' => 'SCADA', 'is_enabled' => 0],
    ['id' => 14, 'method' => 'GET', 'path' => '/api/dlp/emails', 'description' => 'List scanned emails', 'category' => 'DLP', 'is_enabled' => 1],
    ['id' => 15, 'method' => 'POST', 'path' => '/api/dlp/scan', 'description' => 'Scan email content', 'category' => 'DLP', 'is_enabled' => 1],
    ['id' => 16, 'method' => 'GET', 'path' => '/api/users', 'description' => 'List users', 'category' => 'Admin', 'is_enabled' => 1],
    ['id' => 17, 'method' => 'POST', 'path' => '/api/webhook/register', 'description' => 'Register webhook', 'category' => 'Webhooks', 'is_enabled' => 1],
    ['id' => 18, 'method' => 'GET', 'path' => '/api/export', 'description' => 'Export data', 'category' => 'Reporting', 'is_enabled' => 1],
];

$currentPage = 'api';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Configuration - IOC Control Panel</title>
    <link rel="stylesheet" href="assets/css/cpanel.css">
</head>
<body>
    <div class="cpanel-wrapper">
        <?php include 'includes/sidebar.php'; ?>

        <div class="cpanel-main">
            <?php include 'includes/header.php'; ?>

            <div class="cpanel-content">
                <div class="page-header">
                    <h1>API Configuration</h1>
                    <p>Configure API settings, authentication, and endpoint access</p>
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

                <div class="grid-2">
                    <!-- API General Settings -->
                    <div class="section-card">
                        <div class="section-header">
                            <h2>General Settings</h2>
                        </div>
                        <div class="section-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="save_api_settings">

                                <div class="form-group">
                                    <label>API Status</label>
                                    <select name="api_enabled">
                                        <option value="1" <?= $apiSettings['api_enabled'] ? 'selected' : '' ?>>Enabled</option>
                                        <option value="0" <?= !$apiSettings['api_enabled'] ? 'selected' : '' ?>>Disabled</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Require Authentication</label>
                                    <select name="require_auth">
                                        <option value="1" <?= $apiSettings['require_auth'] ? 'selected' : '' ?>>Yes</option>
                                        <option value="0" <?= !$apiSettings['require_auth'] ? 'selected' : '' ?>>No</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Authentication Type</label>
                                    <select name="auth_type">
                                        <option value="api_key" <?= $apiSettings['auth_type'] === 'api_key' ? 'selected' : '' ?>>API Key</option>
                                        <option value="bearer" <?= $apiSettings['auth_type'] === 'bearer' ? 'selected' : '' ?>>Bearer Token</option>
                                        <option value="basic" <?= $apiSettings['auth_type'] === 'basic' ? 'selected' : '' ?>>Basic Auth</option>
                                        <option value="oauth2" <?= $apiSettings['auth_type'] === 'oauth2' ? 'selected' : '' ?>>OAuth 2.0</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Request Timeout (seconds)</label>
                                    <input type="number" name="timeout" value="<?= htmlspecialchars($apiSettings['timeout']) ?>">
                                </div>

                                <div class="form-group">
                                    <label>Max Request Size (MB)</label>
                                    <input type="number" name="max_request_size" value="<?= htmlspecialchars($apiSettings['max_request_size']) ?>">
                                </div>

                                <div class="form-group">
                                    <label>Log API Requests</label>
                                    <select name="log_requests">
                                        <option value="1" <?= $apiSettings['log_requests'] ? 'selected' : '' ?>>Yes</option>
                                        <option value="0" <?= !$apiSettings['log_requests'] ? 'selected' : '' ?>>No</option>
                                    </select>
                                </div>

                                <button type="submit" class="btn btn-primary">Save Settings</button>
                            </form>
                        </div>
                    </div>

                    <!-- Rate Limiting & CORS -->
                    <div class="section-card">
                        <div class="section-header">
                            <h2>Rate Limiting & CORS</h2>
                        </div>
                        <div class="section-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="save_api_settings">

                                <div class="form-group">
                                    <label>Rate Limit (requests)</label>
                                    <input type="number" name="rate_limit" value="<?= htmlspecialchars($apiSettings['rate_limit']) ?>">
                                    <span class="help-text">Maximum requests per time window</span>
                                </div>

                                <div class="form-group">
                                    <label>Rate Window (seconds)</label>
                                    <input type="number" name="rate_window" value="<?= htmlspecialchars($apiSettings['rate_window']) ?>">
                                    <span class="help-text">Time window for rate limiting</span>
                                </div>

                                <div class="form-group">
                                    <label>Enable CORS</label>
                                    <select name="cors_enabled">
                                        <option value="1" <?= $apiSettings['cors_enabled'] ? 'selected' : '' ?>>Yes</option>
                                        <option value="0" <?= !$apiSettings['cors_enabled'] ? 'selected' : '' ?>>No</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Allowed Origins</label>
                                    <textarea name="cors_origins" rows="3"><?= htmlspecialchars($apiSettings['cors_origins']) ?></textarea>
                                    <span class="help-text">Use * for all origins, or comma-separated list</span>
                                </div>

                                <button type="submit" class="btn btn-primary">Save Settings</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- API Key Generation -->
                <div class="section-card">
                    <div class="section-header">
                        <h2>API Key Management</h2>
                        <button class="btn btn-primary btn-sm" onclick="showApiKeyModal()">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;">
                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                            </svg>
                            Generate New Key
                        </button>
                    </div>
                    <div class="section-body">
                        <table class="config-table">
                            <thead>
                                <tr>
                                    <th>Key Name</th>
                                    <th>Created</th>
                                    <th>Last Used</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Default API Key</td>
                                    <td>Jan 15, 2026</td>
                                    <td>2 hours ago</td>
                                    <td><span class="status-badge success">Active</span></td>
                                    <td>
                                        <button class="btn btn-secondary btn-sm">Revoke</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Integration Key</td>
                                    <td>Jan 20, 2026</td>
                                    <td>Never</td>
                                    <td><span class="status-badge success">Active</span></td>
                                    <td>
                                        <button class="btn btn-secondary btn-sm">Revoke</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- API Endpoints -->
                <div class="section-card">
                    <div class="section-header">
                        <h2>API Endpoints</h2>
                    </div>
                    <div class="section-body">
                        <table class="config-table">
                            <thead>
                                <tr>
                                    <th>Method</th>
                                    <th>Endpoint</th>
                                    <th>Description</th>
                                    <th>Category</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($endpoints as $endpoint): ?>
                                <tr>
                                    <td>
                                        <span class="method-badge method-<?= strtolower($endpoint['method']) ?>">
                                            <?= $endpoint['method'] ?>
                                        </span>
                                    </td>
                                    <td><code><?= htmlspecialchars($endpoint['path']) ?></code></td>
                                    <td><?= htmlspecialchars($endpoint['description']) ?></td>
                                    <td><?= htmlspecialchars($endpoint['category']) ?></td>
                                    <td>
                                        <label class="toggle-switch">
                                            <input type="checkbox"
                                                   onchange="toggleEndpoint(<?= $endpoint['id'] ?>, this.checked)"
                                                   <?= $endpoint['is_enabled'] ? 'checked' : '' ?>>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- API Key Modal -->
    <div id="apiKeyModal" class="modal" style="display:none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Generate API Key</h2>
                <button class="modal-close" onclick="closeApiKeyModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="action" value="generate_api_key">

                    <div class="form-group">
                        <label>Key Name</label>
                        <input type="text" name="key_name" required placeholder="e.g., Integration Key">
                    </div>

                    <div class="form-group">
                        <label>Permissions</label>
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;">
                            <label style="display: flex; align-items: center; gap: 8px; font-weight: normal;">
                                <input type="checkbox" name="permissions[]" value="read"> Read Access
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px; font-weight: normal;">
                                <input type="checkbox" name="permissions[]" value="write"> Write Access
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px; font-weight: normal;">
                                <input type="checkbox" name="permissions[]" value="scan"> Scan Operations
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px; font-weight: normal;">
                                <input type="checkbox" name="permissions[]" value="admin"> Admin Access
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">Generate Key</button>
                </form>
            </div>
        </div>
    </div>

    <style>
        .method-badge {
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 700;
            font-family: monospace;
        }
        .method-get { background: #d1fae5; color: #059669; }
        .method-post { background: #dbeafe; color: #2563eb; }
        .method-put { background: #fef3c7; color: #d97706; }
        .method-delete { background: #fee2e2; color: #dc2626; }
        code {
            background: #f1f5f9;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 13px;
        }
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        .modal-content {
            background: white;
            border-radius: 16px;
            width: 90%;
            max-width: 500px;
        }
        .modal-header {
            padding: 20px 25px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: var(--text-secondary);
        }
        .modal-body {
            padding: 25px;
        }
    </style>

    <script src="assets/js/cpanel.js"></script>
    <script>
        function showApiKeyModal() {
            document.getElementById('apiKeyModal').style.display = 'flex';
        }

        function closeApiKeyModal() {
            document.getElementById('apiKeyModal').style.display = 'none';
        }

        function toggleEndpoint(endpointId, enabled) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="toggle_endpoint">
                <input type="hidden" name="endpoint_id" value="${endpointId}">
                <input type="hidden" name="enabled" value="${enabled ? 1 : 0}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    </script>
</body>
</html>
