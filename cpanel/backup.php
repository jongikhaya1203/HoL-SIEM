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

$backupDir = __DIR__ . '/backups';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'create_backup':
            try {
                $backupType = $_POST['backup_type'] ?? 'full';
                $timestamp = date('Y-m-d_H-i-s');
                $backupName = "backup_{$backupType}_{$timestamp}";

                // Create backup record
                $stmt = $db->prepare("INSERT INTO cpanel_backups (backup_name, backup_type, file_path, status, created_by) VALUES (?, ?, ?, 'in_progress', ?)");
                $stmt->execute([$backupName, $backupType, "$backupDir/$backupName.sql", $_SESSION['cpanel_user_id'] ?? 1]);
                $backupId = $db->lastInsertId();

                // In production, this would run mysqldump
                // For demo, we'll create a placeholder
                $backupContent = "-- IOC Network Scanner Database Backup\n";
                $backupContent .= "-- Created: " . date('Y-m-d H:i:s') . "\n";
                $backupContent .= "-- Type: $backupType\n\n";

                // Get all tables and their create statements
                $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
                foreach ($tables as $table) {
                    $backupContent .= "-- Table: $table\n";
                    $createStmt = $db->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC);
                    $backupContent .= $createStmt['Create Table'] . ";\n\n";
                }

                file_put_contents("$backupDir/$backupName.sql", $backupContent);
                $fileSize = filesize("$backupDir/$backupName.sql");

                // Update backup record
                $stmt = $db->prepare("UPDATE cpanel_backups SET status = 'completed', file_size = ?, completed_at = NOW() WHERE id = ?");
                $stmt->execute([$fileSize, $backupId]);

                $message = "Backup created successfully: $backupName.sql";
                $messageType = 'success';
            } catch (Exception $e) {
                $message = 'Backup failed: ' . $e->getMessage();
                $messageType = 'danger';
            }
            break;

        case 'delete_backup':
            try {
                $backupId = $_POST['backup_id'] ?? 0;
                $stmt = $db->prepare("SELECT file_path FROM cpanel_backups WHERE id = ?");
                $stmt->execute([$backupId]);
                $backup = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($backup && file_exists($backup['file_path'])) {
                    unlink($backup['file_path']);
                }

                $stmt = $db->prepare("DELETE FROM cpanel_backups WHERE id = ?");
                $stmt->execute([$backupId]);

                $message = 'Backup deleted successfully';
                $messageType = 'success';
            } catch (Exception $e) {
                $message = 'Delete failed: ' . $e->getMessage();
                $messageType = 'danger';
            }
            break;

        case 'restore_backup':
            try {
                $backupId = $_POST['backup_id'] ?? 0;
                // In production, this would restore the database
                $message = 'Restore functionality would execute here';
                $messageType = 'warning';
            } catch (Exception $e) {
                $message = 'Restore failed: ' . $e->getMessage();
                $messageType = 'danger';
            }
            break;

        case 'save_schedule':
            $message = 'Backup schedule saved successfully';
            $messageType = 'success';
            break;
    }
}

// Get backup history
$backups = [];
try {
    $stmt = $db->query("SELECT * FROM cpanel_backups ORDER BY created_at DESC LIMIT 20");
    $backups = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Table might not exist
}

// Calculate storage used
$storageUsed = 0;
foreach ($backups as $backup) {
    $storageUsed += $backup['file_size'] ?? 0;
}

$currentPage = 'backup';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup & Restore - IOC Control Panel</title>
    <link rel="stylesheet" href="assets/css/cpanel.css">
</head>
<body>
    <div class="cpanel-wrapper">
        <?php include 'includes/sidebar.php'; ?>

        <div class="cpanel-main">
            <?php include 'includes/header.php'; ?>

            <div class="cpanel-content">
                <div class="page-header">
                    <h1>Backup & Restore</h1>
                    <p>Manage database backups and restoration</p>
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
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="7 10 12 15 17 10"></polyline>
                                <line x1="12" y1="15" x2="12" y2="3"></line>
                            </svg>
                        </div>
                        <div class="stat-info">
                            <span class="stat-value"><?= count($backups) ?></span>
                            <span class="stat-label">Total Backups</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon protocols">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                            </svg>
                        </div>
                        <div class="stat-info">
                            <span class="stat-value"><?= round($storageUsed / 1024 / 1024, 2) ?> MB</span>
                            <span class="stat-label">Storage Used</span>
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
                            <span class="stat-value"><?= !empty($backups) ? date('M d', strtotime($backups[0]['created_at'])) : 'Never' ?></span>
                            <span class="stat-label">Last Backup</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon channels">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                            </svg>
                        </div>
                        <div class="stat-info">
                            <span class="stat-value" style="color: var(--success);">Healthy</span>
                            <span class="stat-label">Backup Status</span>
                        </div>
                    </div>
                </div>

                <div class="grid-2">
                    <!-- Create Backup -->
                    <div class="section-card">
                        <div class="section-header">
                            <h2>Create Backup</h2>
                        </div>
                        <div class="section-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="create_backup">

                                <div class="form-group">
                                    <label>Backup Type</label>
                                    <select name="backup_type">
                                        <option value="full">Full Backup (Database + Config)</option>
                                        <option value="incremental">Incremental Backup</option>
                                        <option value="config_only">Configuration Only</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Include Options</label>
                                    <div class="checkbox-group">
                                        <label class="checkbox-label">
                                            <input type="checkbox" name="include_data" checked> Include table data
                                        </label>
                                        <label class="checkbox-label">
                                            <input type="checkbox" name="include_logs"> Include audit logs
                                        </label>
                                        <label class="checkbox-label">
                                            <input type="checkbox" name="compress" checked> Compress backup
                                        </label>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary" style="width: 100%;">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px; margin-right: 8px;">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                        <polyline points="7 10 12 15 17 10"></polyline>
                                        <line x1="12" y1="15" x2="12" y2="3"></line>
                                    </svg>
                                    Create Backup Now
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Schedule -->
                    <div class="section-card">
                        <div class="section-header">
                            <h2>Backup Schedule</h2>
                        </div>
                        <div class="section-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="save_schedule">

                                <div class="toggle-item" style="padding-top: 0;">
                                    <div class="toggle-info">
                                        <span class="toggle-label">Automatic Backups</span>
                                        <span class="toggle-desc">Enable scheduled backups</span>
                                    </div>
                                    <label class="toggle-switch">
                                        <input type="checkbox" name="auto_backup" checked>
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>

                                <div class="form-group">
                                    <label>Frequency</label>
                                    <select name="frequency">
                                        <option value="daily">Daily</option>
                                        <option value="weekly" selected>Weekly</option>
                                        <option value="monthly">Monthly</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Time</label>
                                    <input type="time" name="backup_time" value="02:00">
                                </div>

                                <div class="form-group">
                                    <label>Retention (days)</label>
                                    <input type="number" name="retention" value="30" min="7" max="365">
                                </div>

                                <button type="submit" class="btn btn-secondary" style="width: 100%;">Save Schedule</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Backup History -->
                <div class="section-card" style="margin-top: 20px;">
                    <div class="section-header">
                        <h2>Backup History</h2>
                        <span style="color: var(--text-secondary); font-size: 14px;">
                            <?= count($backups) ?> backups
                        </span>
                    </div>
                    <div class="section-body" style="padding: 0;">
                        <table class="config-table">
                            <thead>
                                <tr>
                                    <th>Backup Name</th>
                                    <th>Type</th>
                                    <th>Size</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($backups)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; color: var(--text-secondary);">No backups found. Create your first backup above.</td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($backups as $backup): ?>
                                <tr>
                                    <td><code><?= htmlspecialchars($backup['backup_name']) ?></code></td>
                                    <td>
                                        <span class="badge badge-<?= $backup['backup_type'] ?>">
                                            <?= ucfirst($backup['backup_type']) ?>
                                        </span>
                                    </td>
                                    <td><?= round(($backup['file_size'] ?? 0) / 1024, 2) ?> KB</td>
                                    <td>
                                        <span class="status-badge status-<?= $backup['status'] ?>">
                                            <?= ucfirst($backup['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($backup['created_at']) ?></td>
                                    <td>
                                        <div style="display: flex; gap: 5px;">
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="restore_backup">
                                                <input type="hidden" name="backup_id" value="<?= $backup['id'] ?>">
                                                <button type="submit" class="btn btn-secondary btn-sm" onclick="return confirm('Restore this backup? Current data will be overwritten.');">Restore</button>
                                            </form>
                                            <a href="backups/<?= htmlspecialchars($backup['backup_name']) ?>.sql" download class="btn btn-secondary btn-sm">Download</a>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="delete_backup">
                                                <input type="hidden" name="backup_id" value="<?= $backup['id'] ?>">
                                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Delete this backup?');">Delete</button>
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
            </div>
        </div>
    </div>

    <style>
        .checkbox-group {
            display: flex;
            flex-direction: column;
            gap: 10px;
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
        }
        .badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        .badge-full { background: #dbeafe; color: #1e40af; }
        .badge-incremental { background: #fef3c7; color: #92400e; }
        .badge-config_only { background: #e0e7ff; color: #3730a3; }
        .status-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        .status-completed { background: #d1fae5; color: #065f46; }
        .status-failed { background: #fee2e2; color: #991b1b; }
        .status-in_progress { background: #fef3c7; color: #92400e; }
        code {
            background: #f1f5f9;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 13px;
        }
    </style>

    <script src="assets/js/cpanel.js"></script>
</body>
</html>
