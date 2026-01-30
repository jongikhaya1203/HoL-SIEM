<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['cpanel_logged_in']) || $_SESSION['cpanel_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$db = Database::getInstance()->getConnection();

// Filters
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';
$dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-7 days'));
$dateTo = $_GET['date_to'] ?? date('Y-m-d');
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 50;

// Build query
$where = ["created_at BETWEEN ? AND ?"];
$params = [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'];

if ($filter !== 'all') {
    $where[] = "action LIKE ?";
    $params[] = "%$filter%";
}

if ($search) {
    $where[] = "(action LIKE ? OR ip_address LIKE ? OR user_agent LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereClause = implode(' AND ', $where);

// Get logs
$logs = [];
$totalLogs = 0;
try {
    // Count total
    $stmt = $db->prepare("SELECT COUNT(*) FROM cpanel_audit_log WHERE $whereClause");
    $stmt->execute($params);
    $totalLogs = $stmt->fetchColumn();

    // Get page
    $offset = ($page - 1) * $perPage;
    $stmt = $db->prepare("SELECT * FROM cpanel_audit_log WHERE $whereClause ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Table might not exist
}

$totalPages = ceil($totalLogs / $perPage);

// Get log statistics
$stats = [
    'total' => $totalLogs,
    'today' => 0,
    'errors' => 0,
    'logins' => 0
];

try {
    $stmt = $db->query("SELECT COUNT(*) FROM cpanel_audit_log WHERE DATE(created_at) = CURDATE()");
    $stats['today'] = $stmt->fetchColumn();

    $stmt = $db->query("SELECT COUNT(*) FROM cpanel_audit_log WHERE action LIKE '%error%' OR action LIKE '%fail%'");
    $stats['errors'] = $stmt->fetchColumn();

    $stmt = $db->query("SELECT COUNT(*) FROM cpanel_audit_log WHERE action LIKE '%login%'");
    $stats['logins'] = $stmt->fetchColumn();
} catch (Exception $e) {
    // Stats unavailable
}

$currentPage = 'logs';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Logs - IOC Control Panel</title>
    <link rel="stylesheet" href="assets/css/cpanel.css">
</head>
<body>
    <div class="cpanel-wrapper">
        <?php include 'includes/sidebar.php'; ?>

        <div class="cpanel-main">
            <?php include 'includes/header.php'; ?>

            <div class="cpanel-content">
                <div class="page-header">
                    <h1>System Logs</h1>
                    <p>View audit logs, system events, and activity history</p>
                </div>

                <!-- Stats -->
                <div class="stats-grid" style="grid-template-columns: repeat(4, 1fr);">
                    <div class="stat-card">
                        <div class="stat-icon modules">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                <line x1="16" y1="17" x2="8" y2="17"></line>
                            </svg>
                        </div>
                        <div class="stat-info">
                            <span class="stat-value"><?= number_format($stats['total']) ?></span>
                            <span class="stat-label">Total Entries</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon protocols">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                        </div>
                        <div class="stat-info">
                            <span class="stat-value"><?= number_format($stats['today']) ?></span>
                            <span class="stat-label">Today</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon api">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="8.5" cy="7" r="4"></circle>
                                <line x1="20" y1="8" x2="20" y2="14"></line>
                                <line x1="23" y1="11" x2="17" y2="11"></line>
                            </svg>
                        </div>
                        <div class="stat-info">
                            <span class="stat-value"><?= number_format($stats['logins']) ?></span>
                            <span class="stat-label">Login Events</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #ef4444, #dc2626);">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                            </svg>
                        </div>
                        <div class="stat-info">
                            <span class="stat-value"><?= number_format($stats['errors']) ?></span>
                            <span class="stat-label">Errors</span>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="section-card">
                    <div class="section-body">
                        <form method="GET" class="filter-form">
                            <div class="filter-row">
                                <div class="form-group" style="flex: 2;">
                                    <label>Search</label>
                                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search logs...">
                                </div>
                                <div class="form-group">
                                    <label>Filter</label>
                                    <select name="filter">
                                        <option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>All Events</option>
                                        <option value="login" <?= $filter === 'login' ? 'selected' : '' ?>>Login Events</option>
                                        <option value="security" <?= $filter === 'security' ? 'selected' : '' ?>>Security</option>
                                        <option value="config" <?= $filter === 'config' ? 'selected' : '' ?>>Configuration</option>
                                        <option value="api" <?= $filter === 'api' ? 'selected' : '' ?>>API Access</option>
                                        <option value="error" <?= $filter === 'error' ? 'selected' : '' ?>>Errors</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>From</label>
                                    <input type="date" name="date_from" value="<?= htmlspecialchars($dateFrom) ?>">
                                </div>
                                <div class="form-group">
                                    <label>To</label>
                                    <input type="date" name="date_to" value="<?= htmlspecialchars($dateTo) ?>">
                                </div>
                                <div class="form-group" style="align-self: flex-end;">
                                    <button type="submit" class="btn btn-primary">Apply</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Logs Table -->
                <div class="section-card">
                    <div class="section-header">
                        <h2>Audit Log</h2>
                        <div style="display: flex; gap: 10px;">
                            <button class="btn btn-secondary btn-sm" onclick="exportLogs()">Export CSV</button>
                            <span style="color: var(--text-secondary); font-size: 14px; align-self: center;">
                                Showing <?= count($logs) ?> of <?= number_format($totalLogs) ?>
                            </span>
                        </div>
                    </div>
                    <div class="section-body" style="padding: 0;">
                        <table class="config-table">
                            <thead>
                                <tr>
                                    <th style="width: 160px;">Timestamp</th>
                                    <th>Action</th>
                                    <th>User</th>
                                    <th>Target</th>
                                    <th>IP Address</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; color: var(--text-secondary);">No log entries found</td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><code><?= htmlspecialchars($log['created_at']) ?></code></td>
                                    <td>
                                        <span class="action-badge action-<?= getActionType($log['action']) ?>">
                                            <?= htmlspecialchars($log['action']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($log['user_id'] ?? 'System') ?></td>
                                    <td>
                                        <?php if ($log['target_type']): ?>
                                            <span class="target-badge"><?= htmlspecialchars($log['target_type']) ?></span>
                                            <?php if ($log['target_id']): ?>#<?= $log['target_id'] ?><?php endif; ?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td><code><?= htmlspecialchars($log['ip_address'] ?? 'N/A') ?></code></td>
                                    <td>
                                        <?php if ($log['new_value']): ?>
                                            <button class="btn btn-secondary btn-sm" onclick='showDetails(<?= json_encode($log) ?>)'>View</button>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?= $page - 1 ?>&filter=<?= $filter ?>&search=<?= urlencode($search) ?>&date_from=<?= $dateFrom ?>&date_to=<?= $dateTo ?>" class="btn btn-secondary btn-sm">&laquo; Prev</a>
                        <?php endif; ?>

                        <span class="page-info">Page <?= $page ?> of <?= $totalPages ?></span>

                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?= $page + 1 ?>&filter=<?= $filter ?>&search=<?= urlencode($search) ?>&date_from=<?= $dateFrom ?>&date_to=<?= $dateTo ?>" class="btn btn-secondary btn-sm">Next &raquo;</a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Details Modal -->
    <div id="detailsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Log Details</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div id="logDetails"></div>
            </div>
        </div>
    </div>

    <style>
        .filter-form .filter-row {
            display: flex;
            gap: 15px;
            align-items: flex-start;
        }
        .filter-form .form-group {
            flex: 1;
        }
        .action-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        .action-login { background: #dbeafe; color: #1e40af; }
        .action-security { background: #fef3c7; color: #92400e; }
        .action-config { background: #e0e7ff; color: #3730a3; }
        .action-error { background: #fee2e2; color: #991b1b; }
        .action-api { background: #d1fae5; color: #065f46; }
        .action-default { background: #e2e8f0; color: #475569; }
        .target-badge {
            background: #f1f5f9;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 11px;
            text-transform: uppercase;
        }
        code {
            background: #f1f5f9;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 12px;
        }
        .pagination {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            padding: 20px;
            border-top: 1px solid var(--border-color);
        }
        .page-info {
            color: var(--text-secondary);
            font-size: 14px;
        }
        #logDetails {
            font-family: monospace;
            font-size: 13px;
            background: #f8fafc;
            padding: 15px;
            border-radius: 8px;
            white-space: pre-wrap;
            max-height: 400px;
            overflow-y: auto;
        }
    </style>

    <script src="assets/js/cpanel.js"></script>
    <script>
        function showDetails(log) {
            const details = {
                'Timestamp': log.created_at,
                'Action': log.action,
                'User ID': log.user_id || 'System',
                'Target Type': log.target_type || 'N/A',
                'Target ID': log.target_id || 'N/A',
                'IP Address': log.ip_address || 'N/A',
                'User Agent': log.user_agent || 'N/A',
                'Old Value': log.old_value ? JSON.stringify(JSON.parse(log.old_value), null, 2) : 'N/A',
                'New Value': log.new_value ? JSON.stringify(JSON.parse(log.new_value), null, 2) : 'N/A'
            };

            let html = '';
            for (const [key, value] of Object.entries(details)) {
                html += `<strong>${key}:</strong>\n${value}\n\n`;
            }

            document.getElementById('logDetails').textContent = html;
            document.getElementById('detailsModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('detailsModal').classList.remove('active');
        }

        function exportLogs() {
            const params = new URLSearchParams(window.location.search);
            params.set('export', 'csv');
            alert('Export would download: logs_' + new Date().toISOString().split('T')[0] + '.csv');
        }
    </script>
</body>
</html>

<?php
function getActionType($action) {
    $action = strtolower($action);
    if (strpos($action, 'login') !== false) return 'login';
    if (strpos($action, 'security') !== false) return 'security';
    if (strpos($action, 'config') !== false) return 'config';
    if (strpos($action, 'error') !== false || strpos($action, 'fail') !== false) return 'error';
    if (strpos($action, 'api') !== false) return 'api';
    return 'default';
}
?>
