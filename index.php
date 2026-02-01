<?php
// Load settings from database
try {
    require_once __DIR__ . '/classes/Database.php';
    $db = Database::getInstance();
    $settings_result = $db->fetchAll("SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ('logo_url', 'app_name', 'theme_color')");
    $settings = [
        'app_name' => 'HoL Intelligent Operating Centre',
        'logo_url' => '',
        'theme_color' => '#667eea'
    ];
    foreach ($settings_result as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (Exception $e) {
    $settings = [
        'app_name' => 'HoL Intelligent Operating Centre',
        'logo_url' => '',
        'theme_color' => '#667eea'
    ];
}

$app_name = $settings['app_name'] ?? 'HoL Intelligent Operating Centre';
$logo_url = $settings['logo_url'] ?? '';
$theme_color = $settings['theme_color'] ?? '#667eea';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($app_name) ?> - Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            background: white;
            padding: 25px 30px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 30px;
        }

        .header-logo {
            flex-shrink: 0;
        }

        .header-content {
            flex-grow: 1;
            text-align: right;
        }

        h1 {
            color: #667eea;
            font-size: 28px;
            margin-bottom: 8px;
            line-height: 1.2;
        }

        .subtitle {
            color: #666;
            font-size: 13px;
            line-height: 1.4;
            margin: 3px 0;
        }

        .admin-link {
            margin-top: 10px;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }
            .header-content {
                text-align: center;
            }
        }

        .nav {
            background: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .nav-btn {
            display: inline-block;
            padding: 12px 24px;
            margin: 5px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
        }

        .nav-btn:hover {
            background: #764ba2;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .nav-btn.secondary {
            background: #4CAF50;
        }

        .nav-btn.danger {
            background: #f44336;
        }

        .dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .card h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 18px;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }

        .stat {
            font-size: 48px;
            font-weight: bold;
            color: #667eea;
            text-align: center;
            margin: 20px 0;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }

        .btn {
            padding: 12px 30px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s;
        }

        .btn:hover {
            background: #764ba2;
            transform: translateY(-2px);
        }

        .btn-success {
            background: #4CAF50;
        }

        .btn-danger {
            background: #f44336;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th {
            background: #667eea;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: 600;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #e0e0e0;
        }

        tr:hover {
            background: #f5f5f5;
        }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-critical {
            background: #f44336;
            color: white;
        }

        .badge-high {
            background: #ff9800;
            color: white;
        }

        .badge-medium {
            background: #ffc107;
            color: #333;
        }

        .badge-low {
            background: #4CAF50;
            color: white;
        }

        .status-running {
            color: #2196F3;
        }

        .status-completed {
            color: #4CAF50;
        }

        .status-failed {
            color: #f44336;
        }

        .alert {
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
            border-left: 4px solid;
        }

        .alert-info {
            background: #e3f2fd;
            border-color: #2196F3;
            color: #1565c0;
        }

        .alert-success {
            background: #e8f5e9;
            border-color: #4CAF50;
            color: #2e7d32;
        }

        .alert-warning {
            background: #fff3e0;
            border-color: #ff9800;
            color: #e65100;
        }

        .alert-danger {
            background: #ffebee;
            border-color: #f44336;
            color: #c62828;
        }

        .footer {
            text-align: center;
            color: white;
            margin-top: 50px;
            padding: 20px;
        }
        /* Module Cards Styling */
        .modules-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }

        .module-card {
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 20px;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .module-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
            border-color: #667eea;
        }

        .module-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .module-card:hover::before {
            transform: scaleX(1);
        }

        .module-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .module-icon {
            font-size: 36px;
            line-height: 1;
        }

        .module-badges {
            display: flex;
            gap: 5px;
        }

        .module-status {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 700;
            color: white;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .module-title {
            font-size: 18px;
            font-weight: 700;
            color: #333;
            margin: 10px 0 5px 0;
            min-height: 44px;
        }

        .module-code {
            font-size: 12px;
            color: #667eea;
            font-weight: 600;
            margin: 0 0 12px 0;
            font-family: 'Courier New', monospace;
        }

        .module-description {
            color: #666;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 15px;
            min-height: 66px;
        }

        .module-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 1px solid #e0e0e0;
        }

        .module-impl {
            font-size: 12px;
            font-weight: 600;
        }

        .module-link {
            color: #667eea;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: color 0.3s;
        }

        .module-link:hover {
            color: #764ba2;
        }

        @media (max-width: 768px) {
            .modules-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Report Builder Dropdown Styles */
        .nav-btn-dropdown {
            position: relative;
            display: inline-block;
        }

        .report-builder-main-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            font-weight: 600;
        }

        .report-builder-main-btn:hover {
            background: linear-gradient(135deg, #0f8a7f 0%, #2ed573 100%) !important;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(17, 153, 142, 0.4);
        }

        .dropdown-arrow {
            font-size: 10px;
            transition: transform 0.3s;
        }

        .nav-btn-dropdown:hover .dropdown-arrow {
            transform: rotate(180deg);
        }

        .new-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #ff4757;
            color: white;
            font-size: 9px;
            font-weight: 700;
            padding: 3px 6px;
            border-radius: 10px;
            animation: pulse-badge 2s infinite;
        }

        @keyframes pulse-badge {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        .nav-dropdown-menu {
            position: absolute;
            top: 100%;
            left: 0;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            min-width: 260px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: all 0.3s ease;
            z-index: 1000;
            overflow: hidden;
            margin-top: 8px;
        }

        .nav-btn-dropdown:hover .nav-dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .nav-dropdown-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px 18px;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            color: inherit;
            border-bottom: 1px solid #f0f0f0;
        }

        .nav-dropdown-item:last-child {
            border-bottom: none;
        }

        .nav-dropdown-item:hover {
            background: linear-gradient(135deg, #f8f9ff 0%, #e8ebff 100%);
        }

        .nav-dropdown-item .dropdown-icon {
            font-size: 24px;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
        }

        .nav-dropdown-item strong {
            display: block;
            color: #333;
            font-size: 14px;
            margin-bottom: 2px;
        }

        .nav-dropdown-item small {
            display: block;
            color: #888;
            font-size: 12px;
        }

        .nav-dropdown-item:first-child .dropdown-icon {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        }

        .nav-dropdown-item:last-child .dropdown-icon {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <?php if (!empty($logo_url)): ?>
            <div class="header-logo">
                <img src="<?= htmlspecialchars($logo_url) ?>" alt="Logo" style="max-height: 70px; max-width: 200px; display: block;">
            </div>
            <?php endif; ?>
            <div class="header-content">
                <h1>🛡️ <?= htmlspecialchars($app_name) ?></h1>
                <p class="subtitle">Intelligent Network Operations & Performance Management Platform</p>
                <p class="subtitle">AI-Powered Monitoring, Predictive Analytics & Automated Insights</p>
                <div class="admin-link">
                    <a href="admin/login.php" style="color: #667eea; text-decoration: none; font-size: 13px; font-weight: 500;">
                        ⚙️ Admin Portal
                    </a>
                </div>
            </div>
        </div>

        <!-- Navigation -->
        <div class="nav">
            <a href="index.php" class="nav-btn">Dashboard</a>
            <a href="scan.php" class="nav-btn secondary">New Scan</a>
            <a href="reports.php" class="nav-btn">View Reports</a>
            <div class="nav-btn-dropdown">
                <button class="nav-btn report-builder-main-btn" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); position: relative;">
                    <span>📊 Report Builder</span>
                    <span class="dropdown-arrow">▼</span>
                    <span class="new-badge">NEW</span>
                </button>
                <div class="nav-dropdown-menu">
                    <a href="report_builder.php" class="nav-dropdown-item">
                        <span class="dropdown-icon">🛠️</span>
                        <div>
                            <strong>Advanced Builder</strong>
                            <small>Full drag-and-drop interface</small>
                        </div>
                    </a>
                    <div class="nav-dropdown-item" onclick="openReportBuilder()">
                        <span class="dropdown-icon">⚡</span>
                        <div>
                            <strong>Quick Builder</strong>
                            <small>Generate reports instantly</small>
                        </div>
                    </div>
                </div>
            </div>
            <a href="compliance_reports.php" class="nav-btn" style="background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%); position: relative;">📋 Compliance Reports<span class="new-badge" style="position: absolute; top: -8px; right: -8px; background: #4CAF50; color: white; font-size: 9px; padding: 2px 6px; border-radius: 8px;">NEW</span></a>
            <a href="mail_dlp.php" class="nav-btn" style="background: #e91e63;">📧 Email DLP</a>
            <a href="api.php" class="nav-btn">API</a>
            <a href="cpanel/scada.php" class="nav-btn" style="background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);">🏭 SCADA</a>
            <a href="cpanel/scada.php?tab=tanks" class="nav-btn" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); position: relative;">🛢️ Tank & Pipeline<span class="new-badge" style="position: absolute; top: -8px; right: -8px; background: #ef4444; color: white; font-size: 9px; padding: 2px 6px; border-radius: 8px;">LIVE</span></a>
            <a href="service_desk.php" class="nav-btn" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); position: relative;">🎫 Service Desk<span class="new-badge" style="position: absolute; top: -8px; right: -8px; background: #ff4757; color: white; font-size: 9px; padding: 2px 6px; border-radius: 8px;">NEW</span></a>
            <a href="admin/logo_settings.php" class="nav-btn" style="background: #FF9800;">🎨 Branding</a>
            <a href="training_center.php" class="nav-btn" style="background: linear-gradient(135deg, #00b894 0%, #00cec9 100%); position: relative;">📚 Training Center<span class="new-badge" style="position: absolute; top: -8px; right: -8px; background: #ff4757; color: white; font-size: 9px; padding: 2px 6px; border-radius: 8px;">NEW</span></a>
            <a href="dark_web_monitor.php" class="nav-btn" style="background: linear-gradient(135deg, #1a1a2e 0%, #4a1942 100%); border: 2px solid #ff0055; position: relative;">🕵️ Dark Web Monitor<span class="new-badge" style="position: absolute; top: -8px; right: -8px; background: #ff0055; color: white; font-size: 9px; padding: 2px 6px; border-radius: 8px; animation: pulse 2s infinite;">ALERT</span></a>
        </div>

        <!-- Quick Stats Dashboard -->
        <div class="dashboard">
            <div class="card">
                <h3>📊 Total Scans</h3>
                <div class="stat" id="total-scans">0</div>
                <p style="text-align: center; color: #666;">All-time scans performed</p>
            </div>

            <div class="card">
                <h3>🎯 Active Vulnerabilities</h3>
                <div class="stat" style="color: #f44336;" id="total-vulns">0</div>
                <p style="text-align: center; color: #666;">Unresolved security issues</p>
            </div>

            <div class="card">
                <h3>⚠️ Critical Issues</h3>
                <div class="stat" style="color: #ff9800;" id="critical-vulns">0</div>
                <p style="text-align: center; color: #666;">Require immediate attention</p>
            </div>

            <div class="card">
                <h3>✅ Compliance Score</h3>
                <div class="stat" style="color: #4CAF50;" id="compliance-score">0%</div>
                <p style="text-align: center; color: #666;">Overall compliance status</p>
            </div>
        </div>

        <?php
        // Load modules from database
        try {
            $modules = $db->fetchAll("SELECT * FROM modules WHERE enabled = 1 ORDER BY category, display_order");
            $modulesByCategory = [];
            foreach ($modules as $module) {
                // Override for IT Service Desk - now fully implemented
                if ($module['module_code'] === 'SERVICE_DESK') {
                    $module['status'] = 'active';
                    $module['implementation_level'] = 'full';
                    $module['url'] = 'service_desk.php';
                    $module['description'] = 'Full ITSM solution with incidents, problems, changes, assets, knowledge base, and SLA management';
                }
                // Override for Remote Support - fully implemented
                if ($module['module_code'] === 'DRE') {
                    $module['status'] = 'active';
                    $module['implementation_level'] = 'full';
                    $module['url'] = 'modules/remote_support.php';
                }
                // Override for Virtualization Manager - fully implemented
                if ($module['module_code'] === 'VMAN') {
                    $module['status'] = 'active';
                    $module['implementation_level'] = 'full';
                    $module['url'] = 'modules/vman.php';
                    $module['description'] = 'Manages and monitors virtual machines, hypervisors (VMware/Hyper-V), and multi-cloud resources (AWS/Azure/GCP)';
                }
                // Override for Web Performance Monitor - fully implemented
                if ($module['module_code'] === 'WPM') {
                    $module['status'] = 'active';
                    $module['implementation_level'] = 'full';
                    $module['url'] = 'modules/wpm.php';
                    $module['description'] = 'Monitors website availability, performance, response times, and SSL certificate status';
                }
                // Override for Server Configuration Monitor - fully implemented
                if ($module['module_code'] === 'SCM') {
                    $module['status'] = 'active';
                    $module['implementation_level'] = 'full';
                    $module['url'] = 'modules/scm.php';
                    $module['description'] = 'Tracks server configuration changes, drift detection, file integrity, and compliance monitoring';
                }
                // Override for SQL Sentry - fully implemented
                if ($module['module_code'] === 'SQL_SENTRY') {
                    $module['status'] = 'active';
                    $module['implementation_level'] = 'full';
                    $module['url'] = 'modules/sql_sentry.php';
                    $module['description'] = 'Advanced SQL Server 2016-2022 monitoring with wait-time analysis, Always On AGs, TempDB, and performance insights';
                }
                // Override for HoL SIEM Observability
                if ($module['module_code'] === 'OBSERVABILITY') {
                    $module['module_name'] = 'HoL SIEM Observability';
                    $module['description'] = 'Unified monitoring for applications, infrastructure, logs, and traces';
                }
                $modulesByCategory[$module['category']][] = $module;
            }
        } catch (Exception $e) {
            $modules = [];
            $modulesByCategory = [];
        }
        ?>

        <!-- SolarWinds-Style Modules Dashboard -->
        <?php if (!empty($modulesByCategory)): ?>
        <div class="card">
            <h2 style="font-size: 24px; margin-bottom: 10px;">🎯 Enterprise Monitoring Modules</h2>
            <p style="color: #666; margin-bottom: 30px;">Comprehensive network, security, and application monitoring platform</p>

            <?php
            $categoryNames = [
                'network_infrastructure' => ['name' => 'Network Infrastructure', 'icon' => '🌐'],
                'systems_applications' => ['name' => 'Systems & Application Management', 'icon' => '💻'],
                'database' => ['name' => 'Database Management', 'icon' => '🗄️'],
                'security' => ['name' => 'IT Security', 'icon' => '🔒'],
                'service_management' => ['name' => 'IT Service Management', 'icon' => '🎫'],
                'observability' => ['name' => 'Observability', 'icon' => '👁️']
            ];

            foreach ($modulesByCategory as $category => $categoryModules):
                $catInfo = $categoryNames[$category] ?? ['name' => ucfirst(str_replace('_', ' ', $category)), 'icon' => '📦'];
            ?>

            <div style="margin-bottom: 40px;">
                <h3 style="font-size: 20px; color: #667eea; margin: 25px 0 20px 0; padding-bottom: 10px; border-bottom: 2px solid #667eea;">
                    <?= $catInfo['icon'] ?> <?= $catInfo['name'] ?>
                </h3>

                <div class="modules-grid">
                    <?php foreach ($categoryModules as $module):
                        $statusColors = [
                            'active' => '#4CAF50',
                            'beta' => '#2196F3',
                            'inactive' => '#9E9E9E',
                            'coming_soon' => '#FF9800'
                        ];
                        $statusColor = $statusColors[$module['status']] ?? '#9E9E9E';

                        $implColors = [
                            'full' => '#4CAF50',
                            'partial' => '#FF9800',
                            'placeholder' => '#9E9E9E',
                            'planned' => '#2196F3'
                        ];
                        $implColor = $implColors[$module['implementation_level']] ?? '#9E9E9E';
                    ?>
                    <div class="module-card" onclick="window.location='<?= htmlspecialchars($module['url']) ?>'">
                        <div class="module-header">
                            <span class="module-icon"><?= $module['icon'] ?></span>
                            <div class="module-badges">
                                <span class="module-status" style="background: <?= $statusColor ?>;">
                                    <?= strtoupper(str_replace('_', ' ', $module['status'])) ?>
                                </span>
                            </div>
                        </div>
                        <h4 class="module-title"><?= htmlspecialchars($module['module_name']) ?></h4>
                        <p class="module-code"><?= htmlspecialchars($module['module_code']) ?></p>
                        <p class="module-description"><?= htmlspecialchars($module['description']) ?></p>
                        <div class="module-footer">
                            <span class="module-impl" style="color: <?= $implColor ?>;">
                                ● <?= ucfirst($module['implementation_level']) ?>
                            </span>
                            <a href="<?= htmlspecialchars($module['url']) ?>" class="module-link">
                                View Module →
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php endforeach; ?>

            <div style="background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 30px; border-radius: 10px; margin-top: 30px;">
                <h3 style="color: white; border: none; margin-bottom: 15px;">🚀 Module Status Legend</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                    <div>
                        <strong style="color: #4CAF50;">●</strong> <strong>Active:</strong> Fully operational
                    </div>
                    <div>
                        <strong style="color: #2196F3;">●</strong> <strong>Beta:</strong> Testing phase
                    </div>
                    <div>
                        <strong style="color: #FF9800;">●</strong> <strong>Coming Soon:</strong> In development
                    </div>
                    <div>
                        <strong style="color: #9E9E9E;">●</strong> <strong>Inactive:</strong> Not available
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Original Features Overview -->
        <div class="card">
            <h3>🚀 Core Security Features</h3>
            <div class="dashboard" style="margin-top: 20px;">
                <div style="padding: 15px;">
                    <h4 style="color: #667eea; margin-bottom: 10px;">Network Discovery</h4>
                    <p>Automatic host discovery and network topology mapping using advanced scanning techniques.</p>
                </div>
                <div style="padding: 15px;">
                    <h4 style="color: #667eea; margin-bottom: 10px;">Vulnerability Assessment</h4>
                    <p>CVE database integration with CVSS scoring and risk prioritization.</p>
                </div>
                <div style="padding: 15px;">
                    <h4 style="color: #667eea; margin-bottom: 10px;">Compliance Checking</h4>
                    <p>Multi-framework support including NIST CSF, ISO 27001, CIS Controls, PCI DSS, and HIPAA.</p>
                </div>
                <div style="padding: 15px; background: linear-gradient(135deg, #fff5f5 0%, #ffe5e5 100%); border-left: 4px solid #e91e63; border-radius: 6px;">
                    <h4 style="color: #e91e63; margin-bottom: 10px;">📧 Email Data Loss Prevention (DLP)</h4>
                    <p>Advanced email scanning for sensitive information leaks including credit cards, SSN, API keys, and confidential data. <a href="mail_dlp.php" style="color: #e91e63; font-weight: 600;">Launch DLP System →</a></p>
                </div>
                <div style="padding: 15px;">
                    <h4 style="color: #667eea; margin-bottom: 10px;">Detailed Reporting</h4>
                    <p>Executive and technical reports with actionable remediation recommendations.</p>
                </div>
                <div style="padding: 15px;">
                    <h4 style="color: #667eea; margin-bottom: 10px;">Service Detection</h4>
                    <p>Banner grabbing and version fingerprinting for installed services.</p>
                </div>
                <div style="padding: 15px;">
                    <h4 style="color: #667eea; margin-bottom: 10px;">SSL/TLS Security</h4>
                    <p>Certificate validation and encryption strength assessment.</p>
                </div>
            </div>
        </div>

        <!-- Quick Start Guide -->
        <div class="card" style="margin-top: 20px;">
            <h3>📖 Quick Start Guide</h3>

            <div class="alert alert-info">
                <strong>Step 1:</strong> Set up the database by importing the schema:
                <code style="display: block; margin: 10px 0; padding: 10px; background: white; border-radius: 3px;">
                    mysql -u root -p &lt; database/schema.sql
                </code>
            </div>

            <div class="alert alert-success">
                <strong>Step 2:</strong> Start a new scan using the web interface or CLI:
                <code style="display: block; margin: 10px 0; padding: 10px; background: white; border-radius: 3px;">
                    php scan_cli.php --target 192.168.1.0/24 --type full
                </code>
            </div>

            <div class="alert alert-warning">
                <strong>Step 3:</strong> Review findings and generate reports via the dashboard or API.
            </div>

            <div class="alert alert-danger">
                <strong>Important:</strong> Only scan networks you have permission to test. Unauthorized scanning may be illegal.
            </div>
        </div>

        <!-- Enterprise Capabilities -->
        <div class="card" style="margin-top: 20px;">
            <h3>🏆 Enterprise-Grade Capabilities</h3>
            <p style="margin: 15px 0;">IOC delivers comprehensive network operations management with industry-leading capabilities:</p>

            <ul style="margin-left: 25px; line-height: 2;">
                <li><strong>AI/ML Analytics:</strong> Intelligent anomaly detection, predictive analytics, and automated insights</li>
                <li><strong>Real-Time Monitoring:</strong> Continuous network performance and device health monitoring</li>
                <li><strong>SNMP Support:</strong> Full SNMPv1/v2c/v3 protocol support with trap receiver</li>
                <li><strong>Network Topology:</strong> Interactive visualization of network infrastructure and connections</li>
                <li><strong>NetFlow/sFlow Analysis:</strong> Deep traffic analysis with top talkers and protocol distribution</li>
                <li><strong>Performance Baselines:</strong> Statistical analysis for proactive anomaly detection</li>
                <li><strong>Multi-Channel Alerting:</strong> Email, SMS, webhooks, and push notifications</li>
                <li><strong>Custom Dashboards:</strong> Personalized monitoring views with drag-and-drop widgets</li>
            </ul>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p><strong>HoL Intelligent Operating Centre v2.0</strong></p>
            <p>Built with PHP & MySQL | AI-Powered Network Operations Platform</p>
            <p>&copy; <?= date('Y') ?> | Enterprise Network Management & Monitoring</p>
        </div>
    </div>

    <!-- Report Builder Modal -->
    <div id="reportBuilderModal" class="rb-modal">
        <div class="rb-modal-overlay" onclick="closeReportBuilder()"></div>
        <div class="rb-modal-content">
            <div class="rb-modal-header">
                <div class="rb-header-left">
                    <h2>📊 Report Builder</h2>
                    <p>Create customized reports with real-time data</p>
                </div>
                <button class="rb-close-btn" onclick="closeReportBuilder()">&times;</button>
            </div>
            <div class="rb-modal-body">
                <!-- Report Builder Tabs -->
                <div class="rb-tabs">
                    <button class="rb-tab active" onclick="switchReportTab('templates')">📋 Templates</button>
                    <button class="rb-tab" onclick="switchReportTab('custom')">🛠️ Custom Builder</button>
                    <button class="rb-tab" onclick="switchReportTab('scheduled')">⏰ Scheduled</button>
                    <button class="rb-tab" onclick="switchReportTab('history')">📁 History</button>
                </div>

                <!-- Templates Tab -->
                <div id="rb-templates" class="rb-tab-content active">
                    <div class="rb-section">
                        <h3>Quick Report Templates</h3>
                        <div class="rb-templates-grid">
                            <div class="rb-template-card" onclick="selectTemplate('executive')">
                                <div class="rb-template-icon">📈</div>
                                <div class="rb-template-info">
                                    <h4>Executive Summary</h4>
                                    <p>High-level overview with key metrics and trends</p>
                                </div>
                                <span class="rb-template-badge popular">Popular</span>
                            </div>
                            <div class="rb-template-card" onclick="selectTemplate('technical')">
                                <div class="rb-template-icon">🔧</div>
                                <div class="rb-template-info">
                                    <h4>Technical Assessment</h4>
                                    <p>Detailed vulnerability findings with remediation</p>
                                </div>
                            </div>
                            <div class="rb-template-card" onclick="selectTemplate('compliance')">
                                <div class="rb-template-icon">✅</div>
                                <div class="rb-template-info">
                                    <h4>Compliance Report</h4>
                                    <p>Framework compliance status and gaps</p>
                                </div>
                                <span class="rb-template-badge new">New</span>
                            </div>
                            <div class="rb-template-card" onclick="selectTemplate('network')">
                                <div class="rb-template-icon">🌐</div>
                                <div class="rb-template-info">
                                    <h4>Network Inventory</h4>
                                    <p>Complete asset inventory with status</p>
                                </div>
                            </div>
                            <div class="rb-template-card" onclick="selectTemplate('vulnerability')">
                                <div class="rb-template-icon">🛡️</div>
                                <div class="rb-template-info">
                                    <h4>Vulnerability Report</h4>
                                    <p>All vulnerabilities sorted by severity</p>
                                </div>
                            </div>
                            <div class="rb-template-card" onclick="selectTemplate('trend')">
                                <div class="rb-template-icon">📊</div>
                                <div class="rb-template-info">
                                    <h4>Trend Analysis</h4>
                                    <p>Security posture over time</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Template Configuration -->
                    <div id="rb-template-config" class="rb-section" style="display: none;">
                        <h3>Configure Report: <span id="selected-template-name">Executive Summary</span></h3>
                        <div class="rb-config-grid">
                            <div class="rb-config-form">
                                <div class="rb-form-group">
                                    <label>Report Title</label>
                                    <input type="text" id="report-title" class="rb-input" value="Security Assessment Report">
                                </div>
                                <div class="rb-form-row">
                                    <div class="rb-form-group">
                                        <label>Date Range</label>
                                        <select id="report-date-range" class="rb-select" onchange="updateDateRange()">
                                            <option value="7">Last 7 Days</option>
                                            <option value="30" selected>Last 30 Days</option>
                                            <option value="90">Last 90 Days</option>
                                            <option value="365">Last Year</option>
                                            <option value="custom">Custom Range</option>
                                        </select>
                                    </div>
                                    <div class="rb-form-group">
                                        <label>Export Format</label>
                                        <select id="report-format" class="rb-select">
                                            <option value="pdf">PDF Document</option>
                                            <option value="html">HTML Report</option>
                                            <option value="excel">Excel Spreadsheet</option>
                                            <option value="csv">CSV Data</option>
                                        </select>
                                    </div>
                                </div>
                                <div id="custom-date-range" style="display: none;">
                                    <div class="rb-form-row">
                                        <div class="rb-form-group">
                                            <label>Start Date</label>
                                            <input type="date" id="report-start-date" class="rb-input">
                                        </div>
                                        <div class="rb-form-group">
                                            <label>End Date</label>
                                            <input type="date" id="report-end-date" class="rb-input">
                                        </div>
                                    </div>
                                </div>
                                <div class="rb-form-group">
                                    <label>Include Sections</label>
                                    <div class="rb-checkbox-grid">
                                        <label class="rb-checkbox">
                                            <input type="checkbox" checked> Executive Summary
                                        </label>
                                        <label class="rb-checkbox">
                                            <input type="checkbox" checked> Charts & Graphs
                                        </label>
                                        <label class="rb-checkbox">
                                            <input type="checkbox" checked> Detailed Findings
                                        </label>
                                        <label class="rb-checkbox">
                                            <input type="checkbox" checked> Recommendations
                                        </label>
                                        <label class="rb-checkbox">
                                            <input type="checkbox"> Raw Data Tables
                                        </label>
                                        <label class="rb-checkbox">
                                            <input type="checkbox"> Appendix
                                        </label>
                                    </div>
                                </div>
                                <div class="rb-form-actions">
                                    <button class="rb-btn secondary" onclick="hideTemplateConfig()">← Back</button>
                                    <button class="rb-btn primary" onclick="previewReport()">👁️ Preview</button>
                                    <button class="rb-btn success" onclick="generateReport()">📄 Generate Report</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Custom Builder Tab -->
                <div id="rb-custom" class="rb-tab-content">
                    <div class="rb-builder-layout">
                        <!-- Data Sources Panel -->
                        <div class="rb-builder-sidebar">
                            <h4>📦 Data Sources</h4>
                            <div class="rb-data-sources">
                                <div class="rb-data-item" draggable="true" data-source="scans">
                                    <span class="rb-data-icon">🔍</span>
                                    <span>Scan Results</span>
                                </div>
                                <div class="rb-data-item" draggable="true" data-source="vulnerabilities">
                                    <span class="rb-data-icon">🛡️</span>
                                    <span>Vulnerabilities</span>
                                </div>
                                <div class="rb-data-item" draggable="true" data-source="devices">
                                    <span class="rb-data-icon">🖥️</span>
                                    <span>Network Devices</span>
                                </div>
                                <div class="rb-data-item" draggable="true" data-source="compliance">
                                    <span class="rb-data-icon">✅</span>
                                    <span>Compliance</span>
                                </div>
                                <div class="rb-data-item" draggable="true" data-source="alerts">
                                    <span class="rb-data-icon">🔔</span>
                                    <span>Alerts</span>
                                </div>
                                <div class="rb-data-item" draggable="true" data-source="performance">
                                    <span class="rb-data-icon">📈</span>
                                    <span>Performance</span>
                                </div>
                            </div>

                            <h4>📊 Visualization Types</h4>
                            <div class="rb-viz-types">
                                <div class="rb-viz-item" onclick="addWidget('bar')">
                                    <span>📊</span> Bar Chart
                                </div>
                                <div class="rb-viz-item" onclick="addWidget('pie')">
                                    <span>🥧</span> Pie Chart
                                </div>
                                <div class="rb-viz-item" onclick="addWidget('line')">
                                    <span>📈</span> Line Chart
                                </div>
                                <div class="rb-viz-item" onclick="addWidget('table')">
                                    <span>📋</span> Data Table
                                </div>
                                <div class="rb-viz-item" onclick="addWidget('kpi')">
                                    <span>🎯</span> KPI Card
                                </div>
                                <div class="rb-viz-item" onclick="addWidget('gauge')">
                                    <span>⏱️</span> Gauge
                                </div>
                            </div>
                        </div>

                        <!-- Report Canvas -->
                        <div class="rb-builder-canvas">
                            <div class="rb-canvas-header">
                                <input type="text" class="rb-report-title-input" value="Custom Security Report" placeholder="Report Title">
                                <div class="rb-canvas-actions">
                                    <button class="rb-btn small" onclick="clearCanvas()">🗑️ Clear</button>
                                    <button class="rb-btn small primary" onclick="previewCustomReport()">👁️ Preview</button>
                                    <button class="rb-btn small success" onclick="saveCustomReport()">💾 Save</button>
                                </div>
                            </div>
                            <div class="rb-canvas-area" id="report-canvas">
                                <!-- Sample Widgets -->
                                <div class="rb-widget" data-type="kpi">
                                    <div class="rb-widget-header">
                                        <span>🎯 Key Metrics</span>
                                        <button class="rb-widget-remove" onclick="removeWidget(this)">×</button>
                                    </div>
                                    <div class="rb-widget-content">
                                        <div class="rb-kpi-grid">
                                            <div class="rb-kpi-item">
                                                <div class="rb-kpi-value">247</div>
                                                <div class="rb-kpi-label">Total Scans</div>
                                            </div>
                                            <div class="rb-kpi-item">
                                                <div class="rb-kpi-value" style="color: #f44336;">89</div>
                                                <div class="rb-kpi-label">Vulnerabilities</div>
                                            </div>
                                            <div class="rb-kpi-item">
                                                <div class="rb-kpi-value" style="color: #4CAF50;">94%</div>
                                                <div class="rb-kpi-label">Compliance</div>
                                            </div>
                                            <div class="rb-kpi-item">
                                                <div class="rb-kpi-value" style="color: #ff9800;">12</div>
                                                <div class="rb-kpi-label">Critical</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="rb-widget half" data-type="pie">
                                    <div class="rb-widget-header">
                                        <span>🥧 Vulnerability Severity</span>
                                        <button class="rb-widget-remove" onclick="removeWidget(this)">×</button>
                                    </div>
                                    <div class="rb-widget-content">
                                        <canvas id="custom-pie-chart"></canvas>
                                    </div>
                                </div>
                                <div class="rb-widget half" data-type="bar">
                                    <div class="rb-widget-header">
                                        <span>📊 Top Affected Hosts</span>
                                        <button class="rb-widget-remove" onclick="removeWidget(this)">×</button>
                                    </div>
                                    <div class="rb-widget-content">
                                        <canvas id="custom-bar-chart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Scheduled Reports Tab -->
                <div id="rb-scheduled" class="rb-tab-content">
                    <div class="rb-section">
                        <div class="rb-section-header">
                            <h3>⏰ Scheduled Reports</h3>
                            <button class="rb-btn primary" onclick="openScheduleModal()">➕ New Schedule</button>
                        </div>
                        <div class="rb-scheduled-list">
                            <div class="rb-scheduled-item">
                                <div class="rb-scheduled-icon active">📈</div>
                                <div class="rb-scheduled-info">
                                    <h4>Weekly Executive Summary</h4>
                                    <p>Every Monday at 8:00 AM • PDF • Email to management@company.com</p>
                                    <span class="rb-scheduled-next">Next: Mon, Feb 3, 2025</span>
                                </div>
                                <div class="rb-scheduled-actions">
                                    <button class="rb-btn small" onclick="runScheduledNow(1)">▶️ Run Now</button>
                                    <button class="rb-btn small secondary" onclick="editSchedule(1)">✏️</button>
                                    <button class="rb-btn small danger" onclick="deleteSchedule(1)">🗑️</button>
                                </div>
                            </div>
                            <div class="rb-scheduled-item">
                                <div class="rb-scheduled-icon active">🛡️</div>
                                <div class="rb-scheduled-info">
                                    <h4>Daily Vulnerability Report</h4>
                                    <p>Daily at 6:00 AM • HTML • Email to security@company.com</p>
                                    <span class="rb-scheduled-next">Next: Tomorrow at 6:00 AM</span>
                                </div>
                                <div class="rb-scheduled-actions">
                                    <button class="rb-btn small" onclick="runScheduledNow(2)">▶️ Run Now</button>
                                    <button class="rb-btn small secondary" onclick="editSchedule(2)">✏️</button>
                                    <button class="rb-btn small danger" onclick="deleteSchedule(2)">🗑️</button>
                                </div>
                            </div>
                            <div class="rb-scheduled-item">
                                <div class="rb-scheduled-icon paused">✅</div>
                                <div class="rb-scheduled-info">
                                    <h4>Monthly Compliance Report</h4>
                                    <p>1st of each month • PDF • Email to compliance@company.com</p>
                                    <span class="rb-scheduled-status paused">Paused</span>
                                </div>
                                <div class="rb-scheduled-actions">
                                    <button class="rb-btn small success" onclick="resumeSchedule(3)">▶️ Resume</button>
                                    <button class="rb-btn small secondary" onclick="editSchedule(3)">✏️</button>
                                    <button class="rb-btn small danger" onclick="deleteSchedule(3)">🗑️</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- History Tab -->
                <div id="rb-history" class="rb-tab-content">
                    <div class="rb-section">
                        <div class="rb-section-header">
                            <h3>📁 Report History</h3>
                            <div class="rb-history-filter">
                                <select class="rb-select" onchange="filterHistory(this.value)">
                                    <option value="all">All Types</option>
                                    <option value="executive">Executive Summary</option>
                                    <option value="technical">Technical Report</option>
                                    <option value="compliance">Compliance Report</option>
                                </select>
                            </div>
                        </div>
                        <table class="rb-history-table">
                            <thead>
                                <tr>
                                    <th>Report Name</th>
                                    <th>Type</th>
                                    <th>Generated</th>
                                    <th>Format</th>
                                    <th>Size</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Weekly Security Assessment</strong></td>
                                    <td><span class="rb-type-badge executive">Executive</span></td>
                                    <td>Jan 27, 2025 08:00 AM</td>
                                    <td>PDF</td>
                                    <td>2.4 MB</td>
                                    <td>
                                        <button class="rb-action-btn" onclick="downloadReport(1)">⬇️</button>
                                        <button class="rb-action-btn" onclick="viewReport(1)">👁️</button>
                                        <button class="rb-action-btn" onclick="shareReport(1)">📤</button>
                                        <button class="rb-action-btn danger" onclick="deleteReport(1)">🗑️</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Vulnerability Assessment - Q1 2025</strong></td>
                                    <td><span class="rb-type-badge technical">Technical</span></td>
                                    <td>Jan 26, 2025 02:30 PM</td>
                                    <td>HTML</td>
                                    <td>1.8 MB</td>
                                    <td>
                                        <button class="rb-action-btn" onclick="downloadReport(2)">⬇️</button>
                                        <button class="rb-action-btn" onclick="viewReport(2)">👁️</button>
                                        <button class="rb-action-btn" onclick="shareReport(2)">📤</button>
                                        <button class="rb-action-btn danger" onclick="deleteReport(2)">🗑️</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>PCI-DSS Compliance Status</strong></td>
                                    <td><span class="rb-type-badge compliance">Compliance</span></td>
                                    <td>Jan 25, 2025 10:15 AM</td>
                                    <td>PDF</td>
                                    <td>3.1 MB</td>
                                    <td>
                                        <button class="rb-action-btn" onclick="downloadReport(3)">⬇️</button>
                                        <button class="rb-action-btn" onclick="viewReport(3)">👁️</button>
                                        <button class="rb-action-btn" onclick="shareReport(3)">📤</button>
                                        <button class="rb-action-btn danger" onclick="deleteReport(3)">🗑️</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Network Device Inventory</strong></td>
                                    <td><span class="rb-type-badge network">Network</span></td>
                                    <td>Jan 24, 2025 04:45 PM</td>
                                    <td>Excel</td>
                                    <td>856 KB</td>
                                    <td>
                                        <button class="rb-action-btn" onclick="downloadReport(4)">⬇️</button>
                                        <button class="rb-action-btn" onclick="viewReport(4)">👁️</button>
                                        <button class="rb-action-btn" onclick="shareReport(4)">📤</button>
                                        <button class="rb-action-btn danger" onclick="deleteReport(4)">🗑️</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Daily Security Scan Results</strong></td>
                                    <td><span class="rb-type-badge technical">Technical</span></td>
                                    <td>Jan 24, 2025 06:00 AM</td>
                                    <td>CSV</td>
                                    <td>124 KB</td>
                                    <td>
                                        <button class="rb-action-btn" onclick="downloadReport(5)">⬇️</button>
                                        <button class="rb-action-btn" onclick="viewReport(5)">👁️</button>
                                        <button class="rb-action-btn" onclick="shareReport(5)">📤</button>
                                        <button class="rb-action-btn danger" onclick="deleteReport(5)">🗑️</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Preview Modal -->
    <div id="reportPreviewModal" class="rb-modal">
        <div class="rb-modal-overlay" onclick="closePreviewModal()"></div>
        <div class="rb-modal-content rb-preview-modal">
            <div class="rb-modal-header">
                <div class="rb-header-left">
                    <h2>👁️ Report Preview</h2>
                    <p id="preview-report-title">Executive Summary Report</p>
                </div>
                <button class="rb-close-btn" onclick="closePreviewModal()">&times;</button>
            </div>
            <div class="rb-preview-body">
                <div class="rb-preview-content" id="preview-content">
                    <!-- Preview content will be generated here -->
                </div>
            </div>
            <div class="rb-preview-footer">
                <button class="rb-btn secondary" onclick="closePreviewModal()">Close</button>
                <button class="rb-btn primary" onclick="printReport()">🖨️ Print</button>
                <button class="rb-btn success" onclick="downloadPreviewReport()">📥 Download</button>
            </div>
        </div>
    </div>

    <!-- Schedule Report Modal -->
    <div id="scheduleModal" class="rb-modal">
        <div class="rb-modal-overlay" onclick="closeScheduleModal()"></div>
        <div class="rb-modal-content rb-schedule-modal">
            <div class="rb-modal-header">
                <div class="rb-header-left">
                    <h2>⏰ Schedule Report</h2>
                    <p>Set up automated report generation</p>
                </div>
                <button class="rb-close-btn" onclick="closeScheduleModal()">&times;</button>
            </div>
            <div class="rb-modal-body">
                <div class="rb-form-group">
                    <label>Report Template</label>
                    <select id="schedule-template" class="rb-select">
                        <option value="executive">Executive Summary</option>
                        <option value="technical">Technical Assessment</option>
                        <option value="compliance">Compliance Report</option>
                        <option value="vulnerability">Vulnerability Report</option>
                        <option value="network">Network Inventory</option>
                    </select>
                </div>
                <div class="rb-form-group">
                    <label>Schedule Name</label>
                    <input type="text" class="rb-input" placeholder="e.g., Weekly Executive Report">
                </div>
                <div class="rb-form-row">
                    <div class="rb-form-group">
                        <label>Frequency</label>
                        <select id="schedule-frequency" class="rb-select" onchange="updateScheduleOptions()">
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                            <option value="monthly">Monthly</option>
                            <option value="quarterly">Quarterly</option>
                        </select>
                    </div>
                    <div class="rb-form-group">
                        <label>Time</label>
                        <input type="time" class="rb-input" value="08:00">
                    </div>
                </div>
                <div id="weekly-options" class="rb-form-group" style="display: none;">
                    <label>Day of Week</label>
                    <select class="rb-select">
                        <option value="1">Monday</option>
                        <option value="2">Tuesday</option>
                        <option value="3">Wednesday</option>
                        <option value="4">Thursday</option>
                        <option value="5">Friday</option>
                    </select>
                </div>
                <div class="rb-form-group">
                    <label>Output Format</label>
                    <select class="rb-select">
                        <option value="pdf">PDF Document</option>
                        <option value="html">HTML Report</option>
                        <option value="excel">Excel Spreadsheet</option>
                    </select>
                </div>
                <div class="rb-form-group">
                    <label>Email Recipients</label>
                    <input type="text" class="rb-input" placeholder="email@example.com, another@example.com">
                </div>
                <div class="rb-form-group">
                    <label class="rb-checkbox">
                        <input type="checkbox" checked> Send email notification on completion
                    </label>
                </div>
            </div>
            <div class="rb-modal-footer">
                <button class="rb-btn secondary" onclick="closeScheduleModal()">Cancel</button>
                <button class="rb-btn success" onclick="saveSchedule()">💾 Save Schedule</button>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="rb-toast-container"></div>

    <style>
        /* ============================================
           Report Builder Styles
           ============================================ */
        .rb-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 2000;
        }
        .rb-modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .rb-modal-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            backdrop-filter: blur(5px);
        }
        .rb-modal-content {
            position: relative;
            background: white;
            border-radius: 15px;
            width: 95%;
            max-width: 1200px;
            max-height: 90vh;
            overflow: hidden;
            box-shadow: 0 25px 80px rgba(0,0,0,0.4);
            animation: rbSlideIn 0.3s ease;
        }
        @keyframes rbSlideIn {
            from { opacity: 0; transform: translateY(-30px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        .rb-modal-header {
            padding: 25px 30px;
            background: linear-gradient(135deg, #9c27b0 0%, #7b1fa2 100%);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .rb-modal-header h2 {
            margin: 0;
            font-size: 24px;
            color: white;
            border: none;
        }
        .rb-modal-header p {
            margin: 5px 0 0 0;
            opacity: 0.9;
            font-size: 14px;
        }
        .rb-close-btn {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            font-size: 24px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .rb-close-btn:hover {
            background: rgba(255,255,255,0.3);
            transform: rotate(90deg);
        }
        .rb-modal-body {
            padding: 20px 30px;
            max-height: calc(90vh - 100px);
            overflow-y: auto;
        }

        /* Tabs */
        .rb-tabs {
            display: flex;
            gap: 10px;
            border-bottom: 2px solid #e0e0e0;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        .rb-tab {
            padding: 12px 24px;
            border: none;
            background: #f5f5f5;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.2s;
        }
        .rb-tab:hover {
            background: #e0e0e0;
        }
        .rb-tab.active {
            background: linear-gradient(135deg, #9c27b0 0%, #7b1fa2 100%);
            color: white;
        }
        .rb-tab-content {
            display: none;
        }
        .rb-tab-content.active {
            display: block;
        }

        /* Section */
        .rb-section {
            margin-bottom: 30px;
        }
        .rb-section h3 {
            font-size: 18px;
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #9c27b0;
        }
        .rb-section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .rb-section-header h3 {
            margin: 0;
            border: none;
        }

        /* Templates Grid */
        .rb-templates-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        .rb-template-card {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 20px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
        }
        .rb-template-card:hover {
            border-color: #9c27b0;
            box-shadow: 0 5px 20px rgba(156, 39, 176, 0.2);
            transform: translateY(-3px);
        }
        .rb-template-icon {
            font-size: 40px;
            flex-shrink: 0;
        }
        .rb-template-info h4 {
            margin: 0 0 5px 0;
            font-size: 16px;
            color: #333;
        }
        .rb-template-info p {
            margin: 0;
            font-size: 13px;
            color: #666;
        }
        .rb-template-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 4px 10px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .rb-template-badge.popular {
            background: #e3f2fd;
            color: #1976d2;
        }
        .rb-template-badge.new {
            background: #e8f5e9;
            color: #388e3c;
        }

        /* Form Elements */
        .rb-form-group {
            margin-bottom: 20px;
        }
        .rb-form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
        }
        .rb-form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .rb-input, .rb-select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s;
        }
        .rb-input:focus, .rb-select:focus {
            outline: none;
            border-color: #9c27b0;
            box-shadow: 0 0 0 3px rgba(156, 39, 176, 0.1);
        }
        .rb-checkbox {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            font-weight: normal !important;
        }
        .rb-checkbox input {
            width: 18px;
            height: 18px;
            accent-color: #9c27b0;
        }
        .rb-checkbox-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 12px;
        }

        /* Buttons */
        .rb-btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .rb-btn.small {
            padding: 8px 16px;
            font-size: 12px;
        }
        .rb-btn.primary {
            background: #9c27b0;
            color: white;
        }
        .rb-btn.primary:hover {
            background: #7b1fa2;
        }
        .rb-btn.secondary {
            background: #9e9e9e;
            color: white;
        }
        .rb-btn.secondary:hover {
            background: #757575;
        }
        .rb-btn.success {
            background: #4CAF50;
            color: white;
        }
        .rb-btn.success:hover {
            background: #43a047;
        }
        .rb-btn.danger {
            background: #f44336;
            color: white;
        }
        .rb-btn.danger:hover {
            background: #e53935;
        }
        .rb-form-actions {
            display: flex;
            gap: 10px;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }

        /* Custom Builder */
        .rb-builder-layout {
            display: flex;
            gap: 20px;
            min-height: 500px;
        }
        .rb-builder-sidebar {
            width: 220px;
            flex-shrink: 0;
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
        }
        .rb-builder-sidebar h4 {
            font-size: 13px;
            color: #666;
            margin: 0 0 12px 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .rb-data-sources, .rb-viz-types {
            margin-bottom: 25px;
        }
        .rb-data-item, .rb-viz-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            margin-bottom: 8px;
            cursor: grab;
            font-size: 13px;
            transition: all 0.2s;
        }
        .rb-data-item:hover, .rb-viz-item:hover {
            border-color: #9c27b0;
            background: #f3e5f5;
        }
        .rb-data-icon {
            font-size: 18px;
        }
        .rb-builder-canvas {
            flex: 1;
            background: #f5f5f5;
            border-radius: 10px;
            overflow: hidden;
        }
        .rb-canvas-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            background: white;
            border-bottom: 1px solid #e0e0e0;
        }
        .rb-report-title-input {
            border: none;
            font-size: 18px;
            font-weight: 600;
            color: #333;
            background: transparent;
            padding: 5px 0;
        }
        .rb-report-title-input:focus {
            outline: none;
            border-bottom: 2px solid #9c27b0;
        }
        .rb-canvas-actions {
            display: flex;
            gap: 8px;
        }
        .rb-canvas-area {
            padding: 20px;
            min-height: 400px;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-content: flex-start;
        }
        .rb-widget {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            width: 100%;
            overflow: hidden;
        }
        .rb-widget.half {
            width: calc(50% - 8px);
        }
        .rb-widget-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 15px;
            background: #f8f9fa;
            border-bottom: 1px solid #e0e0e0;
            font-weight: 600;
            font-size: 14px;
        }
        .rb-widget-remove {
            background: none;
            border: none;
            font-size: 18px;
            color: #999;
            cursor: pointer;
        }
        .rb-widget-remove:hover {
            color: #f44336;
        }
        .rb-widget-content {
            padding: 20px;
        }
        .rb-kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            text-align: center;
        }
        .rb-kpi-value {
            font-size: 32px;
            font-weight: bold;
            color: #9c27b0;
        }
        .rb-kpi-label {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }

        /* Scheduled Reports */
        .rb-scheduled-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .rb-scheduled-item {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 4px solid #9c27b0;
        }
        .rb-scheduled-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        .rb-scheduled-icon.active {
            background: #e8f5e9;
        }
        .rb-scheduled-icon.paused {
            background: #fff3e0;
        }
        .rb-scheduled-info {
            flex: 1;
        }
        .rb-scheduled-info h4 {
            margin: 0 0 5px 0;
            font-size: 16px;
        }
        .rb-scheduled-info p {
            margin: 0;
            font-size: 13px;
            color: #666;
        }
        .rb-scheduled-next {
            display: inline-block;
            margin-top: 8px;
            font-size: 12px;
            color: #4CAF50;
            font-weight: 600;
        }
        .rb-scheduled-status.paused {
            display: inline-block;
            margin-top: 8px;
            font-size: 12px;
            color: #ff9800;
            font-weight: 600;
        }
        .rb-scheduled-actions {
            display: flex;
            gap: 8px;
        }

        /* History Table */
        .rb-history-table {
            width: 100%;
            border-collapse: collapse;
        }
        .rb-history-table th {
            background: #9c27b0;
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }
        .rb-history-table td {
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
        }
        .rb-history-table tr:hover {
            background: #f8f9fa;
        }
        .rb-type-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }
        .rb-type-badge.executive {
            background: #e3f2fd;
            color: #1976d2;
        }
        .rb-type-badge.technical {
            background: #fff3e0;
            color: #e65100;
        }
        .rb-type-badge.compliance {
            background: #e8f5e9;
            color: #388e3c;
        }
        .rb-type-badge.network {
            background: #f3e5f5;
            color: #7b1fa2;
        }
        .rb-action-btn {
            background: none;
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            padding: 6px 10px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .rb-action-btn:hover {
            background: #f0f0f0;
        }
        .rb-action-btn.danger:hover {
            background: #ffebee;
        }
        .rb-history-filter {
            display: flex;
            gap: 10px;
        }

        /* Preview Modal */
        .rb-preview-modal {
            max-width: 900px;
        }
        .rb-preview-body {
            padding: 0;
            max-height: calc(90vh - 180px);
            overflow-y: auto;
        }
        .rb-preview-content {
            padding: 40px;
            background: #f5f5f5;
        }
        .rb-preview-footer {
            padding: 15px 30px;
            background: #f8f9fa;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            border-top: 1px solid #e0e0e0;
        }

        /* Schedule Modal */
        .rb-schedule-modal {
            max-width: 600px;
        }
        .rb-modal-footer {
            padding: 15px 30px;
            background: #f8f9fa;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            border-top: 1px solid #e0e0e0;
        }

        /* Toast */
        #rb-toast-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 3000;
        }
        .rb-toast {
            padding: 15px 25px;
            border-radius: 8px;
            color: white;
            margin-top: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.3);
            animation: rbToastSlide 0.3s ease;
        }
        .rb-toast.success { background: #4CAF50; }
        .rb-toast.error { background: #f44336; }
        .rb-toast.info { background: #2196F3; }
        @keyframes rbToastSlide {
            from { opacity: 0; transform: translateX(50px); }
            to { opacity: 1; transform: translateX(0); }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .rb-builder-layout {
                flex-direction: column;
            }
            .rb-builder-sidebar {
                width: 100%;
            }
            .rb-widget.half {
                width: 100%;
            }
            .rb-templates-grid {
                grid-template-columns: 1fr;
            }
            .rb-kpi-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        // Load dashboard statistics
        async function loadStats() {
            try {
                const response = await fetch('api.php?action=stats');
                const data = await response.json();

                if (data.success) {
                    document.getElementById('total-scans').textContent = data.stats.total_scans || 0;
                    document.getElementById('total-vulns').textContent = data.stats.total_vulnerabilities || 0;
                    document.getElementById('critical-vulns').textContent = data.stats.critical_vulnerabilities || 0;
                    document.getElementById('compliance-score').textContent =
                        (data.stats.compliance_score || 0) + '%';
                }
            } catch (error) {
                console.error('Failed to load statistics:', error);
            }
        }

        // Load stats on page load
        document.addEventListener('DOMContentLoaded', loadStats);

        // ============================================
        // Report Builder Functions
        // ============================================

        let selectedTemplate = null;
        let customChartPie = null;
        let customChartBar = null;

        // Open/Close Report Builder
        function openReportBuilder() {
            document.getElementById('reportBuilderModal').classList.add('active');
            document.body.style.overflow = 'hidden';
            initCustomCharts();
        }

        function closeReportBuilder() {
            document.getElementById('reportBuilderModal').classList.remove('active');
            document.body.style.overflow = '';
        }

        // Tab Switching
        function switchReportTab(tabName) {
            document.querySelectorAll('.rb-tab').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.rb-tab-content').forEach(content => content.classList.remove('active'));

            event.target.classList.add('active');
            document.getElementById('rb-' + tabName).classList.add('active');

            if (tabName === 'custom') {
                setTimeout(initCustomCharts, 100);
            }
        }

        // Template Selection
        function selectTemplate(template) {
            selectedTemplate = template;

            const templateNames = {
                executive: 'Executive Summary',
                technical: 'Technical Assessment',
                compliance: 'Compliance Report',
                network: 'Network Inventory',
                vulnerability: 'Vulnerability Report',
                trend: 'Trend Analysis'
            };

            document.getElementById('selected-template-name').textContent = templateNames[template];
            document.getElementById('rb-template-config').style.display = 'block';

            // Scroll to config
            document.getElementById('rb-template-config').scrollIntoView({ behavior: 'smooth' });
        }

        function hideTemplateConfig() {
            document.getElementById('rb-template-config').style.display = 'none';
            selectedTemplate = null;
        }

        // Date Range
        function updateDateRange() {
            const value = document.getElementById('report-date-range').value;
            document.getElementById('custom-date-range').style.display = value === 'custom' ? 'block' : 'none';
        }

        // Preview Report
        function previewReport() {
            const title = document.getElementById('report-title').value;
            document.getElementById('preview-report-title').textContent = title;

            // Generate preview content
            const content = generatePreviewContent();
            document.getElementById('preview-content').innerHTML = content;

            document.getElementById('reportPreviewModal').classList.add('active');
        }

        function closePreviewModal() {
            document.getElementById('reportPreviewModal').classList.remove('active');
        }

        function generatePreviewContent() {
            const title = document.getElementById('report-title').value;
            const dateRange = document.getElementById('report-date-range').selectedOptions[0].text;

            return `
                <div style="background: white; padding: 40px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <div style="text-align: center; margin-bottom: 40px; padding-bottom: 30px; border-bottom: 3px solid #9c27b0;">
                        <h1 style="color: #9c27b0; margin: 0 0 10px 0;">${title}</h1>
                        <p style="color: #666; margin: 0;">Report Period: ${dateRange}</p>
                        <p style="color: #888; font-size: 13px; margin-top: 10px;">Generated: ${new Date().toLocaleString()}</p>
                    </div>

                    <h2 style="color: #333; border-bottom: 2px solid #e0e0e0; padding-bottom: 10px;">Executive Summary</h2>
                    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin: 30px 0;">
                        <div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 10px;">
                            <div style="font-size: 36px; font-weight: bold; color: #9c27b0;">247</div>
                            <div style="font-size: 13px; color: #666;">Total Scans</div>
                        </div>
                        <div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 10px;">
                            <div style="font-size: 36px; font-weight: bold; color: #f44336;">89</div>
                            <div style="font-size: 13px; color: #666;">Vulnerabilities</div>
                        </div>
                        <div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 10px;">
                            <div style="font-size: 36px; font-weight: bold; color: #ff9800;">12</div>
                            <div style="font-size: 13px; color: #666;">Critical Issues</div>
                        </div>
                        <div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 10px;">
                            <div style="font-size: 36px; font-weight: bold; color: #4CAF50;">94%</div>
                            <div style="font-size: 13px; color: #666;">Compliance</div>
                        </div>
                    </div>

                    <h2 style="color: #333; border-bottom: 2px solid #e0e0e0; padding-bottom: 10px; margin-top: 40px;">Vulnerability Distribution</h2>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin: 30px 0;">
                        <div>
                            <h3 style="font-size: 16px; color: #666;">By Severity</h3>
                            <div style="margin-top: 15px;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                    <span>Critical</span>
                                    <span style="font-weight: bold; color: #f44336;">12</span>
                                </div>
                                <div style="height: 10px; background: #e0e0e0; border-radius: 5px; overflow: hidden;">
                                    <div style="width: 13%; height: 100%; background: #f44336;"></div>
                                </div>
                            </div>
                            <div style="margin-top: 15px;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                    <span>High</span>
                                    <span style="font-weight: bold; color: #ff9800;">28</span>
                                </div>
                                <div style="height: 10px; background: #e0e0e0; border-radius: 5px; overflow: hidden;">
                                    <div style="width: 31%; height: 100%; background: #ff9800;"></div>
                                </div>
                            </div>
                            <div style="margin-top: 15px;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                    <span>Medium</span>
                                    <span style="font-weight: bold; color: #ffc107;">35</span>
                                </div>
                                <div style="height: 10px; background: #e0e0e0; border-radius: 5px; overflow: hidden;">
                                    <div style="width: 39%; height: 100%; background: #ffc107;"></div>
                                </div>
                            </div>
                            <div style="margin-top: 15px;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                    <span>Low</span>
                                    <span style="font-weight: bold; color: #4CAF50;">14</span>
                                </div>
                                <div style="height: 10px; background: #e0e0e0; border-radius: 5px; overflow: hidden;">
                                    <div style="width: 16%; height: 100%; background: #4CAF50;"></div>
                                </div>
                            </div>
                        </div>
                        <div>
                            <h3 style="font-size: 16px; color: #666;">Top Affected Hosts</h3>
                            <table style="width: 100%; margin-top: 15px; font-size: 13px;">
                                <tr style="background: #f5f5f5;">
                                    <th style="padding: 10px; text-align: left;">Host</th>
                                    <th style="padding: 10px; text-align: right;">Vulnerabilities</th>
                                </tr>
                                <tr><td style="padding: 10px;">192.168.1.100</td><td style="padding: 10px; text-align: right; font-weight: bold;">18</td></tr>
                                <tr><td style="padding: 10px;">192.168.1.105</td><td style="padding: 10px; text-align: right; font-weight: bold;">15</td></tr>
                                <tr><td style="padding: 10px;">192.168.1.50</td><td style="padding: 10px; text-align: right; font-weight: bold;">12</td></tr>
                                <tr><td style="padding: 10px;">192.168.1.25</td><td style="padding: 10px; text-align: right; font-weight: bold;">9</td></tr>
                                <tr><td style="padding: 10px;">192.168.1.200</td><td style="padding: 10px; text-align: right; font-weight: bold;">7</td></tr>
                            </table>
                        </div>
                    </div>

                    <h2 style="color: #333; border-bottom: 2px solid #e0e0e0; padding-bottom: 10px; margin-top: 40px;">Recommendations</h2>
                    <ol style="margin: 20px 0; padding-left: 20px; line-height: 2;">
                        <li><strong>Address Critical Vulnerabilities:</strong> Prioritize patching 12 critical vulnerabilities within 24-48 hours</li>
                        <li><strong>Update Outdated Software:</strong> 8 systems running outdated software versions</li>
                        <li><strong>Strengthen Access Controls:</strong> Review and update firewall rules for 5 exposed services</li>
                        <li><strong>Enable Encryption:</strong> 3 services transmitting sensitive data without encryption</li>
                        <li><strong>Implement MFA:</strong> Deploy multi-factor authentication for administrative access</li>
                    </ol>

                    <div style="margin-top: 40px; padding: 20px; background: #f8f9fa; border-radius: 10px; text-align: center; color: #666; font-size: 12px;">
                        <p>This report was generated by HoL Intelligent Operating Centre</p>
                        <p>Confidential - For Internal Use Only</p>
                    </div>
                </div>
            `;
        }

        // Generate Report
        function generateReport() {
            showRbToast('Generating report...', 'info');

            setTimeout(() => {
                const format = document.getElementById('report-format').value;
                const title = document.getElementById('report-title').value;

                // Simulate file download
                showRbToast(`Report "${title}" generated successfully!`, 'success');

                // Create and trigger download
                if (format === 'csv') {
                    const csv = 'Host,Vulnerability,Severity,Status\n192.168.1.100,CVE-2024-1234,Critical,Open\n192.168.1.105,CVE-2024-5678,High,Open';
                    downloadFile(csv, `${title.replace(/\s+/g, '_')}.csv`, 'text/csv');
                } else {
                    showRbToast('Report ready for download', 'success');
                }

                hideTemplateConfig();
            }, 2000);
        }

        // Initialize Custom Charts
        function initCustomCharts() {
            // Pie Chart
            const pieCtx = document.getElementById('custom-pie-chart');
            if (pieCtx && !customChartPie) {
                customChartPie = new Chart(pieCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Critical', 'High', 'Medium', 'Low'],
                        datasets: [{
                            data: [12, 28, 35, 14],
                            backgroundColor: ['#f44336', '#ff9800', '#ffc107', '#4CAF50']
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'bottom' }
                        }
                    }
                });
            }

            // Bar Chart
            const barCtx = document.getElementById('custom-bar-chart');
            if (barCtx && !customChartBar) {
                customChartBar = new Chart(barCtx, {
                    type: 'bar',
                    data: {
                        labels: ['Server-01', 'Router-Core', 'FW-Main', 'Switch-01', 'AP-Lobby'],
                        datasets: [{
                            label: 'Vulnerabilities',
                            data: [18, 15, 12, 9, 7],
                            backgroundColor: '#9c27b0'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        indexAxis: 'y',
                        plugins: {
                            legend: { display: false }
                        }
                    }
                });
            }
        }

        // Widget Functions
        function addWidget(type) {
            const canvas = document.getElementById('report-canvas');
            const widgetId = 'widget-' + Date.now();

            const widgetTemplates = {
                bar: `<div class="rb-widget half" data-type="bar">
                    <div class="rb-widget-header">
                        <span>📊 Bar Chart</span>
                        <button class="rb-widget-remove" onclick="removeWidget(this)">×</button>
                    </div>
                    <div class="rb-widget-content" style="height: 200px;">
                        <canvas id="${widgetId}"></canvas>
                    </div>
                </div>`,
                pie: `<div class="rb-widget half" data-type="pie">
                    <div class="rb-widget-header">
                        <span>🥧 Pie Chart</span>
                        <button class="rb-widget-remove" onclick="removeWidget(this)">×</button>
                    </div>
                    <div class="rb-widget-content" style="height: 200px;">
                        <canvas id="${widgetId}"></canvas>
                    </div>
                </div>`,
                line: `<div class="rb-widget" data-type="line">
                    <div class="rb-widget-header">
                        <span>📈 Line Chart</span>
                        <button class="rb-widget-remove" onclick="removeWidget(this)">×</button>
                    </div>
                    <div class="rb-widget-content" style="height: 200px;">
                        <canvas id="${widgetId}"></canvas>
                    </div>
                </div>`,
                table: `<div class="rb-widget" data-type="table">
                    <div class="rb-widget-header">
                        <span>📋 Data Table</span>
                        <button class="rb-widget-remove" onclick="removeWidget(this)">×</button>
                    </div>
                    <div class="rb-widget-content">
                        <table style="width: 100%; font-size: 13px;">
                            <tr style="background: #f5f5f5;"><th style="padding: 10px;">Host</th><th style="padding: 10px;">Type</th><th style="padding: 10px;">Status</th></tr>
                            <tr><td style="padding: 10px;">192.168.1.1</td><td style="padding: 10px;">Router</td><td style="padding: 10px; color: #4CAF50;">Online</td></tr>
                            <tr><td style="padding: 10px;">192.168.1.10</td><td style="padding: 10px;">Switch</td><td style="padding: 10px; color: #4CAF50;">Online</td></tr>
                            <tr><td style="padding: 10px;">192.168.1.50</td><td style="padding: 10px;">Server</td><td style="padding: 10px; color: #f44336;">Offline</td></tr>
                        </table>
                    </div>
                </div>`,
                kpi: `<div class="rb-widget" data-type="kpi">
                    <div class="rb-widget-header">
                        <span>🎯 KPI Card</span>
                        <button class="rb-widget-remove" onclick="removeWidget(this)">×</button>
                    </div>
                    <div class="rb-widget-content">
                        <div class="rb-kpi-grid">
                            <div class="rb-kpi-item"><div class="rb-kpi-value">${Math.floor(Math.random() * 500)}</div><div class="rb-kpi-label">Metric 1</div></div>
                            <div class="rb-kpi-item"><div class="rb-kpi-value" style="color: #f44336;">${Math.floor(Math.random() * 100)}</div><div class="rb-kpi-label">Metric 2</div></div>
                            <div class="rb-kpi-item"><div class="rb-kpi-value" style="color: #4CAF50;">${Math.floor(Math.random() * 100)}%</div><div class="rb-kpi-label">Metric 3</div></div>
                            <div class="rb-kpi-item"><div class="rb-kpi-value" style="color: #ff9800;">${Math.floor(Math.random() * 50)}</div><div class="rb-kpi-label">Metric 4</div></div>
                        </div>
                    </div>
                </div>`,
                gauge: `<div class="rb-widget half" data-type="gauge">
                    <div class="rb-widget-header">
                        <span>⏱️ Gauge</span>
                        <button class="rb-widget-remove" onclick="removeWidget(this)">×</button>
                    </div>
                    <div class="rb-widget-content" style="text-align: center;">
                        <div style="font-size: 48px; font-weight: bold; color: #4CAF50;">${Math.floor(Math.random() * 40) + 60}%</div>
                        <div style="color: #666;">Security Score</div>
                    </div>
                </div>`
            };

            canvas.insertAdjacentHTML('beforeend', widgetTemplates[type]);

            // Initialize chart if needed
            setTimeout(() => {
                const chartCanvas = document.getElementById(widgetId);
                if (chartCanvas) {
                    if (type === 'bar') {
                        new Chart(chartCanvas, {
                            type: 'bar',
                            data: {
                                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May'],
                                datasets: [{ label: 'Data', data: [12, 19, 8, 15, 12], backgroundColor: '#9c27b0' }]
                            },
                            options: { responsive: true, maintainAspectRatio: false }
                        });
                    } else if (type === 'pie') {
                        new Chart(chartCanvas, {
                            type: 'pie',
                            data: {
                                labels: ['A', 'B', 'C', 'D'],
                                datasets: [{ data: [30, 25, 25, 20], backgroundColor: ['#9c27b0', '#e91e63', '#2196F3', '#4CAF50'] }]
                            },
                            options: { responsive: true, maintainAspectRatio: false }
                        });
                    } else if (type === 'line') {
                        new Chart(chartCanvas, {
                            type: 'line',
                            data: {
                                labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
                                datasets: [{ label: 'Trend', data: [65, 59, 80, 81], borderColor: '#9c27b0', tension: 0.4 }]
                            },
                            options: { responsive: true, maintainAspectRatio: false }
                        });
                    }
                }
            }, 100);

            showRbToast(`${type.charAt(0).toUpperCase() + type.slice(1)} widget added`, 'success');
        }

        function removeWidget(btn) {
            btn.closest('.rb-widget').remove();
            showRbToast('Widget removed', 'info');
        }

        function clearCanvas() {
            if (confirm('Are you sure you want to clear all widgets?')) {
                document.getElementById('report-canvas').innerHTML = '';
                showRbToast('Canvas cleared', 'info');
            }
        }

        function previewCustomReport() {
            showRbToast('Generating preview...', 'info');
            setTimeout(() => {
                previewReport();
            }, 500);
        }

        function saveCustomReport() {
            showRbToast('Custom report template saved!', 'success');
        }

        // Schedule Functions
        function openScheduleModal() {
            document.getElementById('scheduleModal').classList.add('active');
        }

        function closeScheduleModal() {
            document.getElementById('scheduleModal').classList.remove('active');
        }

        function updateScheduleOptions() {
            const frequency = document.getElementById('schedule-frequency').value;
            document.getElementById('weekly-options').style.display = frequency === 'weekly' ? 'block' : 'none';
        }

        function saveSchedule() {
            showRbToast('Schedule saved successfully!', 'success');
            closeScheduleModal();
        }

        function runScheduledNow(id) {
            showRbToast('Running scheduled report...', 'info');
            setTimeout(() => {
                showRbToast('Report generated and sent!', 'success');
            }, 2000);
        }

        function editSchedule(id) {
            openScheduleModal();
        }

        function deleteSchedule(id) {
            if (confirm('Are you sure you want to delete this schedule?')) {
                showRbToast('Schedule deleted', 'info');
            }
        }

        function resumeSchedule(id) {
            showRbToast('Schedule resumed', 'success');
        }

        // History Functions
        function filterHistory(type) {
            showRbToast(`Filtering by: ${type}`, 'info');
        }

        function downloadReport(id) {
            showRbToast('Downloading report...', 'info');
        }

        function viewReport(id) {
            previewReport();
        }

        function shareReport(id) {
            showRbToast('Share link copied to clipboard!', 'success');
        }

        function deleteReport(id) {
            if (confirm('Are you sure you want to delete this report?')) {
                showRbToast('Report deleted', 'info');
            }
        }

        function printReport() {
            window.print();
        }

        function downloadPreviewReport() {
            showRbToast('Downloading report...', 'info');
        }

        // Utility Functions
        function downloadFile(content, filename, contentType) {
            const blob = new Blob([content], { type: contentType });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            a.click();
            URL.revokeObjectURL(url);
        }

        function showRbToast(message, type = 'info') {
            const container = document.getElementById('rb-toast-container');
            const toast = document.createElement('div');
            toast.className = `rb-toast ${type}`;
            toast.textContent = message;
            container.appendChild(toast);

            setTimeout(() => {
                toast.style.opacity = '0';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
    </script>
</body>
</html>
