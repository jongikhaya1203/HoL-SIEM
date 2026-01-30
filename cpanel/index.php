<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Check authentication
if (!isset($_SESSION['cpanel_logged_in']) || $_SESSION['cpanel_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$db = Database::getInstance()->getConnection();

// Get system stats
$stats = [
    'modules' => 0,
    'protocols' => 0,
    'api_endpoints' => 0,
    'channels' => 0
];

try {
    // Count active modules
    $stmt = $db->query("SELECT COUNT(*) FROM cpanel_modules WHERE is_enabled = 1");
    $stats['modules'] = $stmt->fetchColumn() ?: 0;

    // Count configured protocols
    $stmt = $db->query("SELECT COUNT(*) FROM cpanel_protocols WHERE is_enabled = 1");
    $stats['protocols'] = $stmt->fetchColumn() ?: 0;

    // Count API endpoints
    $stmt = $db->query("SELECT COUNT(*) FROM cpanel_api_endpoints WHERE is_enabled = 1");
    $stats['api_endpoints'] = $stmt->fetchColumn() ?: 0;

    // Count notification channels
    $stmt = $db->query("SELECT COUNT(*) FROM cpanel_channels WHERE is_enabled = 1");
    $stats['channels'] = $stmt->fetchColumn() ?: 0;
} catch (Exception $e) {
    // Tables may not exist yet
}

$currentPage = 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IOC Control Panel</title>
    <link rel="stylesheet" href="assets/css/cpanel.css">
</head>
<body>
    <div class="cpanel-wrapper">
        <?php include 'includes/sidebar.php'; ?>

        <div class="cpanel-main">
            <?php include 'includes/header.php'; ?>

            <div class="cpanel-content">
                <div class="page-header">
                    <h1>Dashboard</h1>
                    <p>Welcome to the IOC Intelligent Operating Centre Control Panel</p>
                </div>

                <!-- Stats Cards -->
                <div class="stats-grid">
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
                            <span class="stat-value"><?= $stats['modules'] ?></span>
                            <span class="stat-label">Active Modules</span>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon protocols">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 12h-4l-3 9L9 3l-3 9H2"></path>
                            </svg>
                        </div>
                        <div class="stat-info">
                            <span class="stat-value"><?= $stats['protocols'] ?></span>
                            <span class="stat-label">Protocols</span>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon api">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M18 20V10"></path>
                                <path d="M12 20V4"></path>
                                <path d="M6 20v-6"></path>
                            </svg>
                        </div>
                        <div class="stat-info">
                            <span class="stat-value"><?= $stats['api_endpoints'] ?></span>
                            <span class="stat-label">API Endpoints</span>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon channels">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 2L11 13"></path>
                                <path d="M22 2L15 22L11 13L2 9L22 2Z"></path>
                            </svg>
                        </div>
                        <div class="stat-info">
                            <span class="stat-value"><?= $stats['channels'] ?></span>
                            <span class="stat-label">Channels</span>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="section-card">
                    <div class="section-header">
                        <h2>Quick Actions</h2>
                    </div>
                    <div class="quick-actions">
                        <a href="modules.php" class="action-btn">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="3" width="7" height="7"></rect>
                                <rect x="14" y="3" width="7" height="7"></rect>
                                <rect x="14" y="14" width="7" height="7"></rect>
                                <rect x="3" y="14" width="7" height="7"></rect>
                            </svg>
                            Configure Modules
                        </a>
                        <a href="protocols.php" class="action-btn">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 12h-4l-3 9L9 3l-3 9H2"></path>
                            </svg>
                            Network Protocols
                        </a>
                        <a href="api.php" class="action-btn">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="16 18 22 12 16 6"></polyline>
                                <polyline points="8 6 2 12 8 18"></polyline>
                            </svg>
                            API Settings
                        </a>
                        <a href="channels.php" class="action-btn">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 2L11 13"></path>
                                <path d="M22 2L15 22L11 13L2 9L22 2Z"></path>
                            </svg>
                            Notification Channels
                        </a>
                        <a href="database.php" class="action-btn">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <ellipse cx="12" cy="5" rx="9" ry="3"></ellipse>
                                <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"></path>
                                <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path>
                            </svg>
                            Database Config
                        </a>
                        <a href="security.php" class="action-btn">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                            </svg>
                            Security Settings
                        </a>
                    </div>
                </div>

                <!-- System Status -->
                <div class="grid-2">
                    <div class="section-card">
                        <div class="section-header">
                            <h2>System Status</h2>
                        </div>
                        <div class="status-list">
                            <div class="status-item">
                                <span class="status-name">Database Connection</span>
                                <span class="status-badge success">Connected</span>
                            </div>
                            <div class="status-item">
                                <span class="status-name">Web Server</span>
                                <span class="status-badge success">Running</span>
                            </div>
                            <div class="status-item">
                                <span class="status-name">Scanner Service</span>
                                <span class="status-badge success">Active</span>
                            </div>
                            <div class="status-item">
                                <span class="status-name">SCADA Monitor</span>
                                <span class="status-badge warning">Standby</span>
                            </div>
                            <div class="status-item">
                                <span class="status-name">Email DLP</span>
                                <span class="status-badge success">Active</span>
                            </div>
                        </div>
                    </div>

                    <div class="section-card">
                        <div class="section-header">
                            <h2>Recent Activity</h2>
                        </div>
                        <div class="activity-list">
                            <div class="activity-item">
                                <div class="activity-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                                    </svg>
                                </div>
                                <div class="activity-content">
                                    <span class="activity-text">Security scan completed</span>
                                    <span class="activity-time">2 minutes ago</span>
                                </div>
                            </div>
                            <div class="activity-item">
                                <div class="activity-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="3" y="3" width="7" height="7"></rect>
                                        <rect x="14" y="3" width="7" height="7"></rect>
                                        <rect x="14" y="14" width="7" height="7"></rect>
                                        <rect x="3" y="14" width="7" height="7"></rect>
                                    </svg>
                                </div>
                                <div class="activity-content">
                                    <span class="activity-text">Module configuration updated</span>
                                    <span class="activity-time">15 minutes ago</span>
                                </div>
                            </div>
                            <div class="activity-item">
                                <div class="activity-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M22 12h-4l-3 9L9 3l-3 9H2"></path>
                                    </svg>
                                </div>
                                <div class="activity-content">
                                    <span class="activity-text">Modbus protocol connected</span>
                                    <span class="activity-time">1 hour ago</span>
                                </div>
                            </div>
                            <div class="activity-item">
                                <div class="activity-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <polyline points="12 6 12 12 16 14"></polyline>
                                    </svg>
                                </div>
                                <div class="activity-content">
                                    <span class="activity-text">Scheduled scan triggered</span>
                                    <span class="activity-time">3 hours ago</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/cpanel.js"></script>
</body>
</html>
