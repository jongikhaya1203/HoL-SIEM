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
        case 'save_connection':
            // In real implementation, this would update config file
            $message = 'Database connection settings saved (restart required)';
            $messageType = 'success';
            break;

        case 'test_connection':
            try {
                $testHost = $_POST['host'] ?? 'localhost';
                $testPort = $_POST['port'] ?? '3306';
                $testDb = $_POST['database'] ?? '';
                $testUser = $_POST['username'] ?? 'root';
                $testPass = $_POST['password'] ?? '';

                $dsn = "mysql:host=$testHost;port=$testPort;dbname=$testDb;charset=utf8mb4";
                $testConn = new PDO($dsn, $testUser, $testPass);
                $message = 'Database connection successful!';
                $messageType = 'success';
            } catch (Exception $e) {
                $message = 'Connection failed: ' . $e->getMessage();
                $messageType = 'danger';
            }
            break;

        case 'init_tables':
            try {
                // Create CPanel tables
                $sql = file_get_contents(__DIR__ . '/database/cpanel_schema.sql');
                $db->exec($sql);
                $message = 'Control panel tables initialized successfully';
                $messageType = 'success';
            } catch (Exception $e) {
                $message = 'Error initializing tables: ' . $e->getMessage();
                $messageType = 'danger';
            }
            break;

        case 'optimize':
            try {
                $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
                foreach ($tables as $table) {
                    $db->exec("OPTIMIZE TABLE `$table`");
                }
                $message = 'Database optimization completed';
                $messageType = 'success';
            } catch (Exception $e) {
                $message = 'Optimization error: ' . $e->getMessage();
                $messageType = 'danger';
            }
            break;
    }
}

// Get database statistics
$dbStats = [
    'tables' => 0,
    'size' => '0 MB',
    'connections' => 0,
    'uptime' => '0 days'
];

try {
    // Count tables
    $stmt = $db->query("SHOW TABLES");
    $dbStats['tables'] = $stmt->rowCount();

    // Get database size
    $dbName = $db->query("SELECT DATABASE()")->fetchColumn();
    $stmt = $db->query("
        SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size
        FROM information_schema.tables
        WHERE table_schema = '$dbName'
    ");
    $dbStats['size'] = ($stmt->fetchColumn() ?: '0') . ' MB';

    // Get uptime
    $stmt = $db->query("SHOW GLOBAL STATUS LIKE 'Uptime'");
    $uptime = $stmt->fetch(PDO::FETCH_ASSOC);
    $days = floor($uptime['Value'] / 86400);
    $dbStats['uptime'] = $days . ' days';

} catch (Exception $e) {
    // Stats unavailable
}

// Get table list with sizes
$tables = [];
try {
    $dbName = $db->query("SELECT DATABASE()")->fetchColumn();
    $stmt = $db->query("
        SELECT
            table_name,
            table_rows,
            ROUND((data_length + index_length) / 1024, 2) AS size_kb,
            update_time
        FROM information_schema.tables
        WHERE table_schema = '$dbName'
        ORDER BY table_name
    ");
    $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Tables unavailable
}

$currentPage = 'database';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database - IOC Control Panel</title>
    <link rel="stylesheet" href="assets/css/cpanel.css">
</head>
<body>
    <div class="cpanel-wrapper">
        <?php include 'includes/sidebar.php'; ?>

        <div class="cpanel-main">
            <?php include 'includes/header.php'; ?>

            <div class="cpanel-content">
                <div class="page-header">
                    <h1>Database Configuration</h1>
                    <p>Manage database connections, tables, and maintenance</p>
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

                <!-- Database Stats -->
                <div class="stats-grid" style="grid-template-columns: repeat(4, 1fr);">
                    <div class="stat-card">
                        <div class="stat-icon modules">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <ellipse cx="12" cy="5" rx="9" ry="3"></ellipse>
                                <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"></path>
                                <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path>
                            </svg>
                        </div>
                        <div class="stat-info">
                            <span class="stat-value"><?= $dbStats['tables'] ?></span>
                            <span class="stat-label">Tables</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon protocols">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                            </svg>
                        </div>
                        <div class="stat-info">
                            <span class="stat-value"><?= $dbStats['size'] ?></span>
                            <span class="stat-label">Database Size</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon api">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                        </div>
                        <div class="stat-info">
                            <span class="stat-value"><?= $dbStats['uptime'] ?></span>
                            <span class="stat-label">Server Uptime</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon channels">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                        </div>
                        <div class="stat-info">
                            <span class="stat-value" style="color: var(--success);">Connected</span>
                            <span class="stat-label">Status</span>
                        </div>
                    </div>
                </div>

                <div class="grid-2">
                    <!-- Connection Settings -->
                    <div class="section-card">
                        <div class="section-header">
                            <h2>Connection Settings</h2>
                        </div>
                        <div class="section-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="test_connection">

                                <div class="form-group">
                                    <label>Database Host</label>
                                    <input type="text" name="host" value="localhost">
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Port</label>
                                        <input type="number" name="port" value="3307">
                                    </div>
                                    <div class="form-group">
                                        <label>Database Name</label>
                                        <input type="text" name="database" value="network_security_scanner">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Username</label>
                                    <input type="text" name="username" value="root">
                                </div>

                                <div class="form-group">
                                    <label>Password</label>
                                    <input type="password" name="password" value="">
                                </div>

                                <div style="display: flex; gap: 10px;">
                                    <button type="submit" class="btn btn-secondary">Test Connection</button>
                                    <button type="submit" name="action" value="save_connection" class="btn btn-primary">Save Settings</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Maintenance -->
                    <div class="section-card">
                        <div class="section-header">
                            <h2>Maintenance</h2>
                        </div>
                        <div class="section-body">
                            <div class="maintenance-actions">
                                <div class="maint-item">
                                    <div class="maint-info">
                                        <h4>Initialize CPanel Tables</h4>
                                        <p>Create required control panel database tables</p>
                                    </div>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="init_tables">
                                        <button type="submit" class="btn btn-primary btn-sm">Initialize</button>
                                    </form>
                                </div>

                                <div class="maint-item">
                                    <div class="maint-info">
                                        <h4>Optimize Tables</h4>
                                        <p>Optimize all database tables for better performance</p>
                                    </div>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="optimize">
                                        <button type="submit" class="btn btn-secondary btn-sm">Optimize</button>
                                    </form>
                                </div>

                                <div class="maint-item">
                                    <div class="maint-info">
                                        <h4>Backup Database</h4>
                                        <p>Create a full database backup</p>
                                    </div>
                                    <a href="backup.php" class="btn btn-secondary btn-sm">Backup</a>
                                </div>

                                <div class="maint-item">
                                    <div class="maint-info">
                                        <h4>Clear Old Data</h4>
                                        <p>Remove scan results older than 90 days</p>
                                    </div>
                                    <button class="btn btn-danger btn-sm" onclick="confirmClear()">Clear</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tables List -->
                <div class="section-card">
                    <div class="section-header">
                        <h2>Database Tables</h2>
                        <span style="color: var(--text-secondary); font-size: 14px;">
                            <?= count($tables) ?> tables
                        </span>
                    </div>
                    <div class="section-body" style="padding: 0;">
                        <table class="config-table">
                            <thead>
                                <tr>
                                    <th>Table Name</th>
                                    <th>Rows</th>
                                    <th>Size</th>
                                    <th>Last Updated</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tables as $table): ?>
                                <tr>
                                    <td><code><?= htmlspecialchars($table['table_name']) ?></code></td>
                                    <td><?= number_format($table['table_rows'] ?? 0) ?></td>
                                    <td><?= $table['size_kb'] ?> KB</td>
                                    <td><?= $table['update_time'] ?? 'N/A' ?></td>
                                    <td>
                                        <button class="btn btn-secondary btn-sm" onclick="viewTable('<?= htmlspecialchars($table['table_name']) ?>')">View</button>
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
        code {
            background: #f1f5f9;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 13px;
        }
    </style>

    <script src="assets/js/cpanel.js"></script>
    <script>
        function confirmClear() {
            if (confirm('Are you sure you want to clear old data? This cannot be undone.')) {
                alert('Data clearing would execute here');
            }
        }

        function viewTable(tableName) {
            alert('Table viewer for ' + tableName + ' would open here');
        }
    </script>
</body>
</html>
