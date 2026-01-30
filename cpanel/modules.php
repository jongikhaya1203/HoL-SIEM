<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['cpanel_logged_in']) || $_SESSION['cpanel_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$db = Database::getInstance()->getConnection();
$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'toggle_module':
                $moduleId = $_POST['module_id'] ?? 0;
                $enabled = $_POST['enabled'] ?? 0;
                try {
                    $stmt = $db->prepare("UPDATE cpanel_modules SET is_enabled = ? WHERE id = ?");
                    $stmt->execute([$enabled, $moduleId]);
                    $message = 'Module status updated successfully';
                    $messageType = 'success';
                } catch (Exception $e) {
                    $message = 'Error updating module: ' . $e->getMessage();
                    $messageType = 'danger';
                }
                break;

            case 'save_module_config':
                $moduleId = $_POST['module_id'] ?? 0;
                $config = json_encode($_POST['config'] ?? []);
                try {
                    $stmt = $db->prepare("UPDATE cpanel_modules SET config = ? WHERE id = ?");
                    $stmt->execute([$config, $moduleId]);
                    $message = 'Module configuration saved successfully';
                    $messageType = 'success';
                } catch (Exception $e) {
                    $message = 'Error saving configuration: ' . $e->getMessage();
                    $messageType = 'danger';
                }
                break;
        }
    }
}

// Define available modules
$availableModules = [
    ['name' => 'Network Scanner', 'code' => 'network_scanner', 'category' => 'Security', 'description' => 'Network vulnerability scanning and port detection'],
    ['name' => 'SCADA Monitor', 'code' => 'scada_monitor', 'category' => 'Industrial', 'description' => 'Industrial control system monitoring'],
    ['name' => 'Email DLP', 'code' => 'email_dlp', 'category' => 'Security', 'description' => 'Email data loss prevention and scanning'],
    ['name' => 'Compliance Checker', 'code' => 'compliance', 'category' => 'Compliance', 'description' => 'Multi-framework compliance checking'],
    ['name' => 'ITSM', 'code' => 'itsm', 'category' => 'Operations', 'description' => 'IT Service Management'],
    ['name' => 'Network Traffic Analysis', 'code' => 'nta', 'category' => 'Network', 'description' => 'NetFlow and traffic analysis'],
    ['name' => 'IP Address Manager', 'code' => 'ipam', 'category' => 'Network', 'description' => 'IP address management with DHCP/DNS'],
    ['name' => 'SNMP Monitor', 'code' => 'snmp', 'category' => 'Network', 'description' => 'SNMP device monitoring'],
    ['name' => 'Database Performance', 'code' => 'dpa', 'category' => 'Database', 'description' => 'Database performance analyzer'],
    ['name' => 'Server Monitor', 'code' => 'sam', 'category' => 'Infrastructure', 'description' => 'Server and application monitoring'],
    ['name' => 'Alert Manager', 'code' => 'alerts', 'category' => 'Operations', 'description' => 'Multi-channel alerting system'],
    ['name' => 'Report Generator', 'code' => 'reports', 'category' => 'Reporting', 'description' => 'Custom report generation'],
    ['name' => 'AI Analytics', 'code' => 'ai_analytics', 'category' => 'Advanced', 'description' => 'AI-powered security analytics'],
    ['name' => 'Network Topology', 'code' => 'topology', 'category' => 'Network', 'description' => 'Network topology mapping'],
    ['name' => 'Remote Support', 'code' => 'remote_support', 'category' => 'Operations', 'description' => 'Remote support and access'],
    ['name' => 'Observability', 'code' => 'observability', 'category' => 'Monitoring', 'description' => 'Full-stack observability'],
];

// Get modules from database
$modules = [];
try {
    $stmt = $db->query("SELECT * FROM cpanel_modules ORDER BY category, name");
    $modules = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Table may not exist, use available modules list
    $modules = array_map(function($m, $i) {
        return array_merge($m, ['id' => $i + 1, 'is_enabled' => 1, 'config' => '{}']);
    }, $availableModules, array_keys($availableModules));
}

// Group modules by category
$modulesByCategory = [];
foreach ($modules ?: $availableModules as $module) {
    $cat = $module['category'] ?? 'Other';
    if (!isset($modulesByCategory[$cat])) {
        $modulesByCategory[$cat] = [];
    }
    $modulesByCategory[$cat][] = $module;
}

$currentPage = 'modules';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modules - IOC Control Panel</title>
    <link rel="stylesheet" href="assets/css/cpanel.css">
</head>
<body>
    <div class="cpanel-wrapper">
        <?php include 'includes/sidebar.php'; ?>

        <div class="cpanel-main">
            <?php include 'includes/header.php'; ?>

            <div class="cpanel-content">
                <div class="page-header">
                    <h1>Module Configuration</h1>
                    <p>Enable, disable, and configure system modules</p>
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

                <!-- Module Stats -->
                <div class="stats-grid" style="grid-template-columns: repeat(3, 1fr); margin-bottom: 30px;">
                    <div class="stat-card">
                        <div class="stat-icon modules">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="3" width="7" height="7"></rect>
                                <rect x="14" y="3" width="7" height="7"></rect>
                                <rect x="14" y="14" width="7" height="7"></rect>
                                <rect x="3" y="14" width="7" height="7"></rect>
                            </svg>
                        </div>
                        <div class="stat-info">
                            <span class="stat-value"><?= count($modules ?: $availableModules) ?></span>
                            <span class="stat-label">Total Modules</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon protocols">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                        </div>
                        <div class="stat-info">
                            <span class="stat-value"><?= count(array_filter($modules ?: $availableModules, fn($m) => ($m['is_enabled'] ?? 1) == 1)) ?></span>
                            <span class="stat-label">Active Modules</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon api">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="2" y1="12" x2="22" y2="12"></line>
                                <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
                            </svg>
                        </div>
                        <div class="stat-info">
                            <span class="stat-value"><?= count($modulesByCategory) ?></span>
                            <span class="stat-label">Categories</span>
                        </div>
                    </div>
                </div>

                <!-- Modules by Category -->
                <?php foreach ($modulesByCategory as $category => $catModules): ?>
                <div class="section-card">
                    <div class="section-header">
                        <h2><?= htmlspecialchars($category) ?> Modules</h2>
                        <span class="badge" style="background: var(--primary); color: white; padding: 5px 12px; border-radius: 20px; font-size: 12px;">
                            <?= count($catModules) ?> modules
                        </span>
                    </div>
                    <div class="section-body">
                        <div class="grid-3">
                            <?php foreach ($catModules as $module): ?>
                            <div class="module-card <?= ($module['is_enabled'] ?? 1) ? 'active' : '' ?>">
                                <div class="module-header">
                                    <div class="module-icon">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="3" y="3" width="7" height="7"></rect>
                                            <rect x="14" y="3" width="7" height="7"></rect>
                                            <rect x="14" y="14" width="7" height="7"></rect>
                                            <rect x="3" y="14" width="7" height="7"></rect>
                                        </svg>
                                    </div>
                                    <div class="module-toggle">
                                        <label class="toggle-switch">
                                            <input type="checkbox"
                                                   onchange="toggleModule(<?= $module['id'] ?? 0 ?>, this.checked)"
                                                   <?= ($module['is_enabled'] ?? 1) ? 'checked' : '' ?>>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>
                                </div>
                                <h3 class="module-name"><?= htmlspecialchars($module['name']) ?></h3>
                                <p class="module-description"><?= htmlspecialchars($module['description'] ?? '') ?></p>
                                <div class="module-actions">
                                    <button class="btn btn-secondary btn-sm" onclick="configureModule(<?= $module['id'] ?? 0 ?>, '<?= htmlspecialchars($module['name']) ?>')">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;">
                                            <circle cx="12" cy="12" r="3"></circle>
                                            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                                        </svg>
                                        Configure
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Configuration Modal -->
    <div id="configModal" class="modal" style="display:none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Module Configuration</h2>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body" id="modalBody">
                <!-- Dynamic content -->
            </div>
        </div>
    </div>

    <style>
        .module-card {
            background: white;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            padding: 20px;
            transition: all 0.3s;
        }
        .module-card:hover {
            border-color: var(--primary);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.1);
        }
        .module-card.active {
            border-color: var(--success);
            background: linear-gradient(to bottom, #f0fdf4, white);
        }
        .module-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px;
        }
        .module-icon {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .module-icon svg {
            width: 22px;
            height: 22px;
            color: white;
        }
        .module-name {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .module-description {
            font-size: 13px;
            color: var(--text-secondary);
            margin-bottom: 15px;
            line-height: 1.5;
        }
        .module-actions {
            display: flex;
            gap: 10px;
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
            max-width: 600px;
            max-height: 80vh;
            overflow: auto;
        }
        .modal-header {
            padding: 20px 25px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .modal-header h2 {
            font-size: 18px;
            font-weight: 600;
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
        function toggleModule(moduleId, enabled) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="toggle_module">
                <input type="hidden" name="module_id" value="${moduleId}">
                <input type="hidden" name="enabled" value="${enabled ? 1 : 0}">
            `;
            document.body.appendChild(form);
            form.submit();
        }

        function configureModule(moduleId, moduleName) {
            document.getElementById('modalTitle').textContent = moduleName + ' Configuration';
            document.getElementById('modalBody').innerHTML = `
                <form method="POST">
                    <input type="hidden" name="action" value="save_module_config">
                    <input type="hidden" name="module_id" value="${moduleId}">
                    <div class="form-group">
                        <label>Enable Logging</label>
                        <select name="config[logging]" class="form-control">
                            <option value="1">Enabled</option>
                            <option value="0">Disabled</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Log Level</label>
                        <select name="config[log_level]" class="form-control">
                            <option value="debug">Debug</option>
                            <option value="info">Info</option>
                            <option value="warning">Warning</option>
                            <option value="error">Error</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Timeout (seconds)</label>
                        <input type="number" name="config[timeout]" value="30" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Max Retries</label>
                        <input type="number" name="config[max_retries]" value="3" class="form-control">
                    </div>
                    <button type="submit" class="btn btn-primary">Save Configuration</button>
                </form>
            `;
            document.getElementById('configModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('configModal').style.display = 'none';
        }

        document.getElementById('configModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });
    </script>
</body>
</html>
