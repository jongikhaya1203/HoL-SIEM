<?php
/**
 * HoL SIEM Complete Product Brochure
 * Full module showcase with dashboard screenshots
 */

require_once __DIR__ . '/classes/Database.php';

try {
    $db = Database::getInstance();
    $settings_result = $db->fetchAll("SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ('app_name', 'company_name', 'logo_url')");
    $app_name = 'HoL Intelligent Operating Centre';
    $company_name = 'Your Organization';
    $logo_url = '';
    foreach ($settings_result as $row) {
        if ($row['setting_key'] === 'app_name') $app_name = $row['setting_value'];
        if ($row['setting_key'] === 'company_name') $company_name = $row['setting_value'];
        if ($row['setting_key'] === 'logo_url') $logo_url = $row['setting_value'];
    }
} catch (Exception $e) {
    $app_name = 'HoL Intelligent Operating Centre';
    $company_name = 'Your Organization';
    $logo_url = '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HoL SIEM - Complete Product Brochure</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --primary: #0f172a;
            --primary-light: #1e293b;
            --accent: #3b82f6;
            --accent-light: #60a5fa;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --purple: #8b5cf6;
            --pink: #ec4899;
            --cyan: #06b6d4;
            --text: #1e293b;
            --text-light: #64748b;
            --white: #ffffff;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-800: #1e293b;
        }

        @media print {
            body { font-size: 9pt; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            .no-print { display: none !important; }
            .page { page-break-after: always; page-break-inside: avoid; }
            .page:last-child { page-break-after: auto; }
            @page { margin: 0.3in; size: A4; }
        }

        body {
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--gray-100);
            color: var(--text);
            line-height: 1.5;
        }

        .container { max-width: 900px; margin: 0 auto; }

        .print-controls {
            position: fixed; top: 20px; right: 20px; z-index: 1000;
            display: flex; gap: 10px;
        }

        .btn {
            padding: 12px 24px; border: none; border-radius: 8px;
            cursor: pointer; font-size: 14px; font-weight: 600;
            text-decoration: none; display: inline-block;
        }
        .btn-primary { background: var(--accent); color: white; }
        .btn-secondary { background: var(--primary); color: white; }

        .page {
            background: white; min-height: 100vh;
            position: relative; overflow: hidden;
        }

        /* Cover Page */
        .cover-page {
            background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 40%, #3b82f6 100%);
            display: flex; flex-direction: column;
            justify-content: center; align-items: center;
            text-align: center; color: white; padding: 50px;
        }

        .cover-page::before {
            content: ''; position: absolute; inset: 0;
            background: radial-gradient(circle at 30% 70%, rgba(59,130,246,0.3) 0%, transparent 50%),
                        radial-gradient(circle at 70% 30%, rgba(139,92,246,0.2) 0%, transparent 50%);
        }

        .cover-content { position: relative; z-index: 1; }
        .cover-badge {
            background: rgba(255,255,255,0.15); padding: 10px 30px;
            border-radius: 30px; font-size: 13px; letter-spacing: 3px;
            text-transform: uppercase; margin-bottom: 30px;
        }
        .cover-logo { font-size: 90px; margin-bottom: 20px; }
        .cover-title { font-size: 48px; font-weight: 800; margin-bottom: 10px; }
        .cover-subtitle { font-size: 22px; font-weight: 300; opacity: 0.9; margin-bottom: 15px; }
        .cover-tagline { font-size: 16px; max-width: 650px; opacity: 0.8; line-height: 1.7; margin-bottom: 40px; }

        .cover-features {
            display: flex; gap: 40px; margin-top: 40px;
        }
        .cover-feature {
            text-align: center; padding: 20px;
            background: rgba(255,255,255,0.1); border-radius: 12px;
            min-width: 140px;
        }
        .cover-feature-icon { font-size: 32px; margin-bottom: 8px; }
        .cover-feature-text { font-size: 13px; opacity: 0.9; }

        /* Section Pages */
        .section-page { padding: 40px 50px; }

        .page-header {
            display: flex; align-items: center; gap: 15px;
            margin-bottom: 30px; padding-bottom: 15px;
            border-bottom: 3px solid var(--accent);
        }
        .page-header-icon { font-size: 40px; }
        .page-header h2 { font-size: 28px; color: var(--primary); }
        .page-header p { font-size: 14px; color: var(--text-light); margin-top: 3px; }

        /* Screenshot Mockups */
        .screenshot {
            background: var(--gray-800); border-radius: 12px;
            overflow: hidden; box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            margin: 20px 0;
        }

        .screenshot-header {
            background: #2d3748; padding: 10px 15px;
            display: flex; align-items: center; gap: 8px;
        }
        .screenshot-dot { width: 12px; height: 12px; border-radius: 50%; }
        .screenshot-dot.red { background: #ef4444; }
        .screenshot-dot.yellow { background: #f59e0b; }
        .screenshot-dot.green { background: #10b981; }
        .screenshot-title {
            margin-left: 10px; color: #94a3b8; font-size: 12px;
        }

        .screenshot-body { padding: 15px; }

        /* Dashboard Mockup */
        .dashboard-mock {
            display: grid; grid-template-columns: 200px 1fr; gap: 15px;
            min-height: 350px;
        }

        .sidebar-mock {
            background: #1a1a2e; border-radius: 8px; padding: 15px;
        }
        .sidebar-logo {
            color: #10b981; font-weight: bold; font-size: 14px;
            padding: 10px; border-bottom: 1px solid #2d3748; margin-bottom: 10px;
        }
        .sidebar-item {
            padding: 8px 10px; color: #94a3b8; font-size: 11px;
            border-radius: 5px; margin: 3px 0; display: flex;
            align-items: center; gap: 8px;
        }
        .sidebar-item.active { background: #3b82f6; color: white; }
        .sidebar-item:hover { background: #2d3748; }

        .main-content-mock { display: flex; flex-direction: column; gap: 15px; }

        .stats-row {
            display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px;
        }
        .stat-card {
            background: #2d3748; border-radius: 8px; padding: 15px;
            text-align: center;
        }
        .stat-value { font-size: 24px; font-weight: bold; color: white; }
        .stat-label { font-size: 10px; color: #94a3b8; margin-top: 3px; }
        .stat-card.green .stat-value { color: #10b981; }
        .stat-card.blue .stat-value { color: #3b82f6; }
        .stat-card.yellow .stat-value { color: #f59e0b; }
        .stat-card.red .stat-value { color: #ef4444; }

        .chart-row { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; flex: 1; }
        .chart-card {
            background: #2d3748; border-radius: 8px; padding: 15px;
        }
        .chart-title { color: #94a3b8; font-size: 11px; margin-bottom: 10px; }
        .chart-bars { display: flex; align-items: flex-end; gap: 8px; height: 80px; }
        .chart-bar {
            flex: 1; background: linear-gradient(to top, #3b82f6, #60a5fa);
            border-radius: 3px 3px 0 0;
        }

        .pie-chart {
            width: 80px; height: 80px; border-radius: 50%;
            background: conic-gradient(#10b981 0% 45%, #3b82f6 45% 75%, #f59e0b 75% 90%, #ef4444 90% 100%);
            margin: 0 auto;
        }

        /* Table Mockup */
        .table-mock {
            width: 100%; border-collapse: collapse; font-size: 11px;
        }
        .table-mock th {
            background: #3b82f6; color: white; padding: 10px;
            text-align: left; font-weight: 600;
        }
        .table-mock td {
            padding: 10px; border-bottom: 1px solid #374151; color: #e2e8f0;
        }
        .table-mock tr:nth-child(even) { background: #1e293b; }

        .status-badge {
            padding: 3px 10px; border-radius: 12px; font-size: 10px;
            font-weight: 600; display: inline-block;
        }
        .status-badge.success { background: #065f46; color: #10b981; }
        .status-badge.warning { background: #78350f; color: #f59e0b; }
        .status-badge.danger { background: #7f1d1d; color: #ef4444; }
        .status-badge.info { background: #1e3a5f; color: #60a5fa; }

        /* SCADA Mockup */
        .scada-mock { padding: 20px; }
        .scada-header {
            display: flex; justify-content: space-between;
            align-items: center; margin-bottom: 20px;
        }
        .scada-title { color: white; font-size: 16px; font-weight: 600; }
        .scada-status { color: #10b981; font-size: 12px; }

        .tank-row { display: flex; justify-content: space-around; margin-bottom: 20px; }
        .tank-item { text-align: center; }
        .tank-visual {
            width: 50px; height: 80px; background: #1a1a2e;
            border: 2px solid #374151; border-radius: 6px;
            position: relative; overflow: hidden; margin: 0 auto 8px;
        }
        .tank-fill {
            position: absolute; bottom: 0; left: 0; right: 0;
            transition: height 0.5s;
        }
        .tank-fill.green { background: linear-gradient(to top, #059669, #10b981); }
        .tank-fill.yellow { background: linear-gradient(to top, #d97706, #f59e0b); }
        .tank-fill.red { background: linear-gradient(to top, #dc2626, #ef4444); }
        .tank-label { color: #94a3b8; font-size: 9px; }
        .tank-value { color: white; font-size: 12px; font-weight: bold; }

        .pipeline-diagram {
            background: #1a1a2e; border-radius: 8px; padding: 15px;
        }
        .pipeline-row {
            display: flex; align-items: center; justify-content: center;
            gap: 5px; margin: 10px 0;
        }
        .pipeline-node {
            width: 60px; height: 30px; border-radius: 5px;
            display: flex; align-items: center; justify-content: center;
            font-size: 8px; color: white; font-weight: 600;
        }
        .pipeline-line {
            width: 30px; height: 3px; background: #3b82f6;
            position: relative;
        }
        .pipeline-line::after {
            content: '→'; position: absolute; right: -5px; top: -8px;
            color: #3b82f6; font-size: 12px;
        }

        /* Service Desk Mockup */
        .ticket-list { display: flex; flex-direction: column; gap: 8px; }
        .ticket-item {
            background: #2d3748; border-radius: 6px; padding: 12px;
            display: flex; align-items: center; gap: 12px;
            border-left: 3px solid;
        }
        .ticket-item.critical { border-left-color: #ef4444; }
        .ticket-item.high { border-left-color: #f59e0b; }
        .ticket-item.medium { border-left-color: #3b82f6; }
        .ticket-item.low { border-left-color: #10b981; }

        .ticket-id { color: #60a5fa; font-size: 11px; font-weight: 600; min-width: 70px; }
        .ticket-title { color: white; font-size: 12px; flex: 1; }
        .ticket-meta { color: #94a3b8; font-size: 10px; }

        /* Network Config Mockup */
        .network-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
        .device-card {
            background: #2d3748; border-radius: 8px; padding: 12px;
            text-align: center;
        }
        .device-icon { font-size: 28px; margin-bottom: 5px; }
        .device-name { color: white; font-size: 11px; font-weight: 600; }
        .device-ip { color: #94a3b8; font-size: 10px; margin-top: 3px; }
        .device-status { margin-top: 8px; }

        /* Features Grid */
        .features-grid {
            display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;
            margin: 25px 0;
        }
        .feature-card {
            background: var(--gray-50); border: 1px solid var(--gray-200);
            border-radius: 12px; padding: 25px; text-align: center;
        }
        .feature-card .icon { font-size: 36px; margin-bottom: 12px; }
        .feature-card h4 { font-size: 15px; color: var(--primary); margin-bottom: 8px; }
        .feature-card p { font-size: 12px; color: var(--text-light); }

        /* Module List */
        .module-list {
            display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;
        }
        .module-item {
            display: flex; align-items: center; gap: 15px;
            background: var(--gray-50); padding: 15px;
            border-radius: 10px; border-left: 4px solid var(--accent);
        }
        .module-item .icon { font-size: 28px; }
        .module-item h5 { font-size: 14px; color: var(--primary); }
        .module-item p { font-size: 11px; color: var(--text-light); margin-top: 2px; }

        /* Pump Mockup */
        .pump-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; }
        .pump-card {
            background: #2d3748; border-radius: 8px; padding: 12px; text-align: center;
        }
        .pump-icon { font-size: 24px; margin-bottom: 5px; }
        .pump-name { color: white; font-size: 10px; margin-bottom: 5px; }
        .pump-rate { color: #10b981; font-size: 14px; font-weight: bold; }
        .pump-unit { color: #94a3b8; font-size: 9px; }
        .pump-bar {
            height: 6px; background: #1a1a2e; border-radius: 3px;
            margin-top: 8px; overflow: hidden;
        }
        .pump-bar-fill {
            height: 100%; background: linear-gradient(90deg, #10b981, #34d399);
            border-radius: 3px;
        }

        /* DLP Mockup */
        .dlp-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 15px; }
        .dlp-stat {
            background: #2d3748; border-radius: 8px; padding: 15px; text-align: center;
        }
        .dlp-stat-icon { font-size: 24px; margin-bottom: 5px; }
        .dlp-stat-value { color: white; font-size: 20px; font-weight: bold; }
        .dlp-stat-label { color: #94a3b8; font-size: 10px; }

        /* Alarm List */
        .alarm-list { display: flex; flex-direction: column; gap: 6px; }
        .alarm-item {
            display: flex; align-items: center; gap: 10px;
            padding: 8px 12px; border-radius: 6px; font-size: 11px;
        }
        .alarm-item.critical { background: rgba(239,68,68,0.2); color: #fca5a5; }
        .alarm-item.warning { background: rgba(245,158,11,0.2); color: #fcd34d; }
        .alarm-item.info { background: rgba(59,130,246,0.2); color: #93c5fd; }
        .alarm-time { min-width: 60px; opacity: 0.7; }
        .alarm-message { flex: 1; }

        /* Footer */
        .page-footer {
            position: absolute; bottom: 20px; left: 50px; right: 50px;
            display: flex; justify-content: space-between;
            font-size: 10px; color: var(--text-light);
            border-top: 1px solid var(--gray-200); padding-top: 15px;
        }

        /* CTA Section */
        .cta-section {
            background: linear-gradient(135deg, var(--primary), #1e3a5f);
            color: white; padding: 50px; text-align: center;
            min-height: 100vh; display: flex; flex-direction: column;
            justify-content: center; align-items: center;
        }
        .cta-section h2 { font-size: 36px; margin-bottom: 15px; }
        .cta-section p { font-size: 18px; opacity: 0.9; margin-bottom: 40px; max-width: 600px; }
        .cta-buttons { display: flex; gap: 20px; }
        .cta-btn {
            padding: 15px 40px; border-radius: 8px; font-size: 16px;
            font-weight: 600; text-decoration: none;
        }
        .cta-btn.primary { background: var(--accent); color: white; }
        .cta-btn.secondary { background: transparent; border: 2px solid white; color: white; }
    </style>
</head>
<body>
    <div class="print-controls no-print">
        <button class="btn btn-primary" onclick="window.print()">Print / Save as PDF</button>
        <a href="index.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>

    <div class="container">
        <!-- Page 1: Cover -->
        <div class="page cover-page">
            <div class="cover-content">
                <div class="cover-badge">Enterprise Security Platform</div>
                <?php if ($logo_url): ?>
                <div class="cover-logo"><img src="<?= htmlspecialchars($logo_url) ?>" alt="HoL SIEM Logo" style="max-height: 100px; max-width: 180px;"></div>
                <?php else: ?>
                <div class="cover-logo">🛡️</div>
                <?php endif; ?>
                <h1 class="cover-title">HoL SIEM</h1>
                <p class="cover-subtitle">Intelligent Operating Centre</p>
                <p class="cover-tagline">
                    Complete IT/OT Security and Operations Platform.<br>
                    Unified monitoring for networks, SCADA systems, and critical infrastructure.
                </p>

                <div class="cover-features">
                    <div class="cover-feature">
                        <div class="cover-feature-icon">🔍</div>
                        <div class="cover-feature-text">Network<br>Security</div>
                    </div>
                    <div class="cover-feature">
                        <div class="cover-feature-icon">🏭</div>
                        <div class="cover-feature-text">SCADA<br>Monitoring</div>
                    </div>
                    <div class="cover-feature">
                        <div class="cover-feature-icon">🎫</div>
                        <div class="cover-feature-text">Service<br>Desk</div>
                    </div>
                    <div class="cover-feature">
                        <div class="cover-feature-icon">📊</div>
                        <div class="cover-feature-text">Custom<br>Dashboards</div>
                    </div>
                    <div class="cover-feature">
                        <div class="cover-feature-icon">🔐</div>
                        <div class="cover-feature-text">Data Loss<br>Prevention</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Page 2: Executive Dashboard -->
        <div class="page section-page">
            <div class="page-header">
                <div class="page-header-icon">📊</div>
                <div>
                    <h2>Executive Dashboard</h2>
                    <p>Real-time overview of your entire security and operations landscape</p>
                </div>
            </div>

            <div class="screenshot">
                <div class="screenshot-header">
                    <div class="screenshot-dot red"></div>
                    <div class="screenshot-dot yellow"></div>
                    <div class="screenshot-dot green"></div>
                    <span class="screenshot-title">HoL SIEM - Executive Dashboard</span>
                </div>
                <div class="screenshot-body">
                    <div class="dashboard-mock">
                        <div class="sidebar-mock">
                            <div class="sidebar-logo">🛡️ HoL SIEM</div>
                            <div class="sidebar-item active">📊 Dashboard</div>
                            <div class="sidebar-item">🔍 Network Scan</div>
                            <div class="sidebar-item">🏭 SCADA</div>
                            <div class="sidebar-item">🔐 DLP</div>
                            <div class="sidebar-item">🎫 Service Desk</div>
                            <div class="sidebar-item">📈 Reports</div>
                            <div class="sidebar-item">⚙️ Settings</div>
                        </div>
                        <div class="main-content-mock">
                            <div class="stats-row">
                                <div class="stat-card green">
                                    <div class="stat-value">847</div>
                                    <div class="stat-label">Assets Monitored</div>
                                </div>
                                <div class="stat-card blue">
                                    <div class="stat-value">12</div>
                                    <div class="stat-label">Active Scans</div>
                                </div>
                                <div class="stat-card yellow">
                                    <div class="stat-value">23</div>
                                    <div class="stat-label">Open Alerts</div>
                                </div>
                                <div class="stat-card red">
                                    <div class="stat-value">3</div>
                                    <div class="stat-label">Critical Issues</div>
                                </div>
                            </div>
                            <div class="chart-row">
                                <div class="chart-card">
                                    <div class="chart-title">Security Events (7 Days)</div>
                                    <div class="chart-bars">
                                        <div class="chart-bar" style="height: 60%;"></div>
                                        <div class="chart-bar" style="height: 80%;"></div>
                                        <div class="chart-bar" style="height: 45%;"></div>
                                        <div class="chart-bar" style="height: 90%;"></div>
                                        <div class="chart-bar" style="height: 70%;"></div>
                                        <div class="chart-bar" style="height: 55%;"></div>
                                        <div class="chart-bar" style="height: 65%;"></div>
                                    </div>
                                </div>
                                <div class="chart-card">
                                    <div class="chart-title">Vulnerability Distribution</div>
                                    <div class="pie-chart"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="features-grid">
                <div class="feature-card">
                    <div class="icon">📈</div>
                    <h4>Real-Time Metrics</h4>
                    <p>Live updates on security posture and operational status</p>
                </div>
                <div class="feature-card">
                    <div class="icon">🎨</div>
                    <h4>Customizable Widgets</h4>
                    <p>Drag-and-drop interface to build your perfect view</p>
                </div>
                <div class="feature-card">
                    <div class="icon">🔔</div>
                    <h4>Smart Alerts</h4>
                    <p>Priority-based notifications with escalation rules</p>
                </div>
            </div>

            <div class="page-footer">
                <span>HoL SIEM Product Brochure</span>
                <span>Page 2</span>
            </div>
        </div>

        <!-- Page 3: Network Configuration -->
        <div class="page section-page">
            <div class="page-header">
                <div class="page-header-icon">🌐</div>
                <div>
                    <h2>Network Configuration & Scanning</h2>
                    <p>Comprehensive network discovery, vulnerability assessment, and configuration management</p>
                </div>
            </div>

            <div class="screenshot">
                <div class="screenshot-header">
                    <div class="screenshot-dot red"></div>
                    <div class="screenshot-dot yellow"></div>
                    <div class="screenshot-dot green"></div>
                    <span class="screenshot-title">HoL SIEM - Network Scanner</span>
                </div>
                <div class="screenshot-body">
                    <div style="padding: 10px;">
                        <div class="stats-row" style="margin-bottom: 15px;">
                            <div class="stat-card green">
                                <div class="stat-value">156</div>
                                <div class="stat-label">Devices Online</div>
                            </div>
                            <div class="stat-card blue">
                                <div class="stat-value">8</div>
                                <div class="stat-label">Scans Running</div>
                            </div>
                            <div class="stat-card yellow">
                                <div class="stat-value">47</div>
                                <div class="stat-label">Vulnerabilities</div>
                            </div>
                            <div class="stat-card red">
                                <div class="stat-value">5</div>
                                <div class="stat-label">Critical CVEs</div>
                            </div>
                        </div>

                        <div class="network-grid">
                            <div class="device-card">
                                <div class="device-icon">🖥️</div>
                                <div class="device-name">Web Server 01</div>
                                <div class="device-ip">192.168.1.10</div>
                                <div class="device-status">
                                    <span class="status-badge success">Online</span>
                                </div>
                            </div>
                            <div class="device-card">
                                <div class="device-icon">🗄️</div>
                                <div class="device-name">Database Server</div>
                                <div class="device-ip">192.168.1.20</div>
                                <div class="device-status">
                                    <span class="status-badge success">Online</span>
                                </div>
                            </div>
                            <div class="device-card">
                                <div class="device-icon">🔥</div>
                                <div class="device-name">Firewall</div>
                                <div class="device-ip">192.168.1.1</div>
                                <div class="device-status">
                                    <span class="status-badge success">Online</span>
                                </div>
                            </div>
                            <div class="device-card">
                                <div class="device-icon">📡</div>
                                <div class="device-name">Core Switch</div>
                                <div class="device-ip">192.168.1.2</div>
                                <div class="device-status">
                                    <span class="status-badge warning">Warning</span>
                                </div>
                            </div>
                            <div class="device-card">
                                <div class="device-icon">📟</div>
                                <div class="device-name">PLC Gateway</div>
                                <div class="device-ip">192.168.10.1</div>
                                <div class="device-status">
                                    <span class="status-badge success">Online</span>
                                </div>
                            </div>
                            <div class="device-card">
                                <div class="device-icon">🖨️</div>
                                <div class="device-name">Print Server</div>
                                <div class="device-ip">192.168.1.50</div>
                                <div class="device-status">
                                    <span class="status-badge danger">Offline</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="module-list">
                <div class="module-item">
                    <div class="icon">🔍</div>
                    <div>
                        <h5>Network Discovery</h5>
                        <p>Automatic asset detection and mapping</p>
                    </div>
                </div>
                <div class="module-item">
                    <div class="icon">🛡️</div>
                    <div>
                        <h5>Vulnerability Scanning</h5>
                        <p>CVE detection with severity ratings</p>
                    </div>
                </div>
                <div class="module-item">
                    <div class="icon">📋</div>
                    <div>
                        <h5>Compliance Checking</h5>
                        <p>Policy-based configuration audits</p>
                    </div>
                </div>
                <div class="module-item">
                    <div class="icon">📊</div>
                    <div>
                        <h5>Port Scanning</h5>
                        <p>Service detection and enumeration</p>
                    </div>
                </div>
            </div>

            <div class="page-footer">
                <span>HoL SIEM Product Brochure</span>
                <span>Page 3</span>
            </div>
        </div>

        <!-- Page 4: SCADA Monitoring -->
        <div class="page section-page">
            <div class="page-header">
                <div class="page-header-icon">🏭</div>
                <div>
                    <h2>SCADA / ICS Monitoring</h2>
                    <p>Real-time industrial control system visualization and monitoring</p>
                </div>
            </div>

            <div class="screenshot">
                <div class="screenshot-header">
                    <div class="screenshot-dot red"></div>
                    <div class="screenshot-dot yellow"></div>
                    <div class="screenshot-dot green"></div>
                    <span class="screenshot-title">HoL SIEM - SCADA Dashboard</span>
                </div>
                <div class="screenshot-body scada-mock">
                    <div class="scada-header">
                        <div class="scada-title">🛢️ Oil & Gas Storage Facility</div>
                        <div class="scada-status">● System Normal</div>
                    </div>

                    <div class="tank-row">
                        <div class="tank-item">
                            <div class="tank-visual">
                                <div class="tank-fill green" style="height: 71%;"></div>
                            </div>
                            <div class="tank-label">Crude Oil T-101</div>
                            <div class="tank-value">71%</div>
                        </div>
                        <div class="tank-item">
                            <div class="tank-visual">
                                <div class="tank-fill green" style="height: 84%;"></div>
                            </div>
                            <div class="tank-label">Crude Oil T-102</div>
                            <div class="tank-value">84%</div>
                        </div>
                        <div class="tank-item">
                            <div class="tank-visual">
                                <div class="tank-fill yellow" style="height: 62%;"></div>
                            </div>
                            <div class="tank-label">Diesel T-103</div>
                            <div class="tank-value">62%</div>
                        </div>
                        <div class="tank-item">
                            <div class="tank-visual">
                                <div class="tank-fill green" style="height: 88%;"></div>
                            </div>
                            <div class="tank-label">Gasoline T-104</div>
                            <div class="tank-value">88%</div>
                        </div>
                        <div class="tank-item">
                            <div class="tank-visual">
                                <div class="tank-fill red" style="height: 28%;"></div>
                            </div>
                            <div class="tank-label">Kerosene T-105</div>
                            <div class="tank-value">28%</div>
                        </div>
                        <div class="tank-item">
                            <div class="tank-visual">
                                <div class="tank-fill green" style="height: 78%;"></div>
                            </div>
                            <div class="tank-label">Heavy Fuel T-106</div>
                            <div class="tank-value">78%</div>
                        </div>
                    </div>

                    <div class="pump-grid">
                        <div class="pump-card">
                            <div class="pump-icon">⚙️</div>
                            <div class="pump-name">Main Inlet Pump</div>
                            <div class="pump-rate">850</div>
                            <div class="pump-unit">BBL/hr</div>
                            <div class="pump-bar"><div class="pump-bar-fill" style="width: 71%;"></div></div>
                        </div>
                        <div class="pump-card">
                            <div class="pump-icon">⚙️</div>
                            <div class="pump-name">Transfer Pump</div>
                            <div class="pump-rate">620</div>
                            <div class="pump-unit">BBL/hr</div>
                            <div class="pump-bar"><div class="pump-bar-fill" style="width: 52%;"></div></div>
                        </div>
                        <div class="pump-card">
                            <div class="pump-icon">⚙️</div>
                            <div class="pump-name">Export Pump</div>
                            <div class="pump-rate">1,200</div>
                            <div class="pump-unit">BBL/hr</div>
                            <div class="pump-bar"><div class="pump-bar-fill" style="width: 89%;"></div></div>
                        </div>
                        <div class="pump-card">
                            <div class="pump-icon">🔴</div>
                            <div class="pump-name">Backup Pump</div>
                            <div class="pump-rate">0</div>
                            <div class="pump-unit">Standby</div>
                            <div class="pump-bar"><div class="pump-bar-fill" style="width: 0%;"></div></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="features-grid">
                <div class="feature-card">
                    <div class="icon">🛢️</div>
                    <h4>Tank Monitoring</h4>
                    <p>Real-time levels, temperature, and pressure</p>
                </div>
                <div class="feature-card">
                    <div class="icon">⚙️</div>
                    <h4>Pump Control</h4>
                    <p>Flow rates, efficiency, and runtime tracking</p>
                </div>
                <div class="feature-card">
                    <div class="icon">🚨</div>
                    <h4>Alarm Management</h4>
                    <p>Priority-based alerts with escalation</p>
                </div>
            </div>

            <div class="page-footer">
                <span>HoL SIEM Product Brochure</span>
                <span>Page 4</span>
            </div>
        </div>

        <!-- Page 5: SCADA Pipeline & Gas -->
        <div class="page section-page">
            <div class="page-header">
                <div class="page-header-icon">🔵</div>
                <div>
                    <h2>Pipeline & Gas Storage Monitoring</h2>
                    <p>Pipeline flow visualization, leak detection, and gas storage management</p>
                </div>
            </div>

            <div class="screenshot">
                <div class="screenshot-header">
                    <div class="screenshot-dot red"></div>
                    <div class="screenshot-dot yellow"></div>
                    <div class="screenshot-dot green"></div>
                    <span class="screenshot-title">HoL SIEM - Pipeline Monitoring</span>
                </div>
                <div class="screenshot-body">
                    <div class="pipeline-diagram">
                        <div style="color: #94a3b8; font-size: 11px; margin-bottom: 15px; text-align: center;">Pipeline Flow Diagram</div>
                        <div class="pipeline-row">
                            <div class="pipeline-node" style="background: #3b82f6;">Offshore</div>
                            <div class="pipeline-line"></div>
                            <div class="pipeline-node" style="background: #10b981;">T-101</div>
                            <div class="pipeline-line"></div>
                            <div class="pipeline-node" style="background: #10b981;">T-102</div>
                            <div class="pipeline-line"></div>
                            <div class="pipeline-node" style="background: #8b5cf6;">Distiller</div>
                            <div class="pipeline-line"></div>
                            <div class="pipeline-node" style="background: #f59e0b;">Terminal</div>
                        </div>
                        <div style="display: flex; justify-content: space-around; margin-top: 20px;">
                            <div style="text-align: center;">
                                <div style="color: #10b981; font-size: 18px; font-weight: bold;">1,250</div>
                                <div style="color: #94a3b8; font-size: 10px;">Inlet Flow (BBL/hr)</div>
                            </div>
                            <div style="text-align: center;">
                                <div style="color: #3b82f6; font-size: 18px; font-weight: bold;">85.4</div>
                                <div style="color: #94a3b8; font-size: 10px;">Pressure (PSI)</div>
                            </div>
                            <div style="text-align: center;">
                                <div style="color: #10b981; font-size: 18px; font-weight: bold;">Normal</div>
                                <div style="color: #94a3b8; font-size: 10px;">Leak Status</div>
                            </div>
                            <div style="text-align: center;">
                                <div style="color: #f59e0b; font-size: 18px; font-weight: bold;">680</div>
                                <div style="color: #94a3b8; font-size: 10px;">Export Flow (BBL/hr)</div>
                            </div>
                        </div>
                    </div>

                    <div style="margin-top: 15px;">
                        <div style="color: #94a3b8; font-size: 11px; margin-bottom: 10px;">Gas Storage Facilities</div>
                        <div class="tank-row">
                            <div class="tank-item">
                                <div class="tank-visual" style="border-radius: 50%;">
                                    <div class="tank-fill green" style="height: 77%; border-radius: 0 0 50% 50%;"></div>
                                </div>
                                <div class="tank-label">Natural Gas G-201</div>
                                <div class="tank-value">77%</div>
                            </div>
                            <div class="tank-item">
                                <div class="tank-visual" style="border-radius: 50%;">
                                    <div class="tank-fill green" style="height: 85%; border-radius: 0 0 50% 50%;"></div>
                                </div>
                                <div class="tank-label">Natural Gas G-202</div>
                                <div class="tank-value">85%</div>
                            </div>
                            <div class="tank-item">
                                <div class="tank-visual" style="border-radius: 50%;">
                                    <div class="tank-fill green" style="height: 82%; border-radius: 0 0 50% 50%;"></div>
                                </div>
                                <div class="tank-label">LPG Sphere G-203</div>
                                <div class="tank-value">82%</div>
                            </div>
                            <div class="tank-item">
                                <div class="tank-visual" style="border-radius: 50%;">
                                    <div class="tank-fill yellow" style="height: 61%; border-radius: 0 0 50% 50%;"></div>
                                </div>
                                <div class="tank-label">LPG Sphere G-204</div>
                                <div class="tank-value">61%</div>
                            </div>
                            <div class="tank-item">
                                <div class="tank-visual" style="border-radius: 50%;">
                                    <div class="tank-fill green" style="height: 85%; border-radius: 0 0 50% 50%;"></div>
                                </div>
                                <div class="tank-label">Propane G-205</div>
                                <div class="tank-value">85%</div>
                            </div>
                            <div class="tank-item">
                                <div class="tank-visual" style="border-radius: 50%;">
                                    <div class="tank-fill red" style="height: 32%; border-radius: 0 0 50% 50%;"></div>
                                </div>
                                <div class="tank-label">Butane G-206</div>
                                <div class="tank-value">32%</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="module-list">
                <div class="module-item">
                    <div class="icon">🔍</div>
                    <div>
                        <h5>Leak Detection</h5>
                        <p>Pressure drop and flow anomaly monitoring</p>
                    </div>
                </div>
                <div class="module-item">
                    <div class="icon">📊</div>
                    <div>
                        <h5>Flow Analytics</h5>
                        <p>Real-time and historical flow analysis</p>
                    </div>
                </div>
                <div class="module-item">
                    <div class="icon">🌡️</div>
                    <div>
                        <h5>Environmental Monitoring</h5>
                        <p>Temperature and pressure tracking</p>
                    </div>
                </div>
                <div class="module-item">
                    <div class="icon">⚡</div>
                    <div>
                        <h5>Compressor Status</h5>
                        <p>Gas compression monitoring</p>
                    </div>
                </div>
            </div>

            <div class="page-footer">
                <span>HoL SIEM Product Brochure</span>
                <span>Page 5</span>
            </div>
        </div>

        <!-- Page 6: Service Desk -->
        <div class="page section-page">
            <div class="page-header">
                <div class="page-header-icon">🎫</div>
                <div>
                    <h2>Service Desk & Incident Management</h2>
                    <p>Complete ITSM solution for incident, problem, and change management</p>
                </div>
            </div>

            <div class="screenshot">
                <div class="screenshot-header">
                    <div class="screenshot-dot red"></div>
                    <div class="screenshot-dot yellow"></div>
                    <div class="screenshot-dot green"></div>
                    <span class="screenshot-title">HoL SIEM - Service Desk</span>
                </div>
                <div class="screenshot-body">
                    <div style="padding: 10px;">
                        <div class="stats-row" style="margin-bottom: 15px;">
                            <div class="stat-card blue">
                                <div class="stat-value">42</div>
                                <div class="stat-label">Open Tickets</div>
                            </div>
                            <div class="stat-card green">
                                <div class="stat-value">156</div>
                                <div class="stat-label">Resolved Today</div>
                            </div>
                            <div class="stat-card yellow">
                                <div class="stat-value">8</div>
                                <div class="stat-label">Pending Changes</div>
                            </div>
                            <div class="stat-card red">
                                <div class="stat-value">3</div>
                                <div class="stat-label">SLA Breaches</div>
                            </div>
                        </div>

                        <div class="ticket-list">
                            <div class="ticket-item critical">
                                <div class="ticket-id">INC-2024-0847</div>
                                <div class="ticket-title">Critical: SCADA Server Unresponsive</div>
                                <div class="ticket-meta">
                                    <span class="status-badge danger">Critical</span>
                                </div>
                            </div>
                            <div class="ticket-item high">
                                <div class="ticket-id">INC-2024-0846</div>
                                <div class="ticket-title">Network Switch Port Failure - Building B</div>
                                <div class="ticket-meta">
                                    <span class="status-badge warning">High</span>
                                </div>
                            </div>
                            <div class="ticket-item medium">
                                <div class="ticket-id">CHG-2024-0125</div>
                                <div class="ticket-title">Firewall Rule Update - DMZ Access</div>
                                <div class="ticket-meta">
                                    <span class="status-badge info">Change</span>
                                </div>
                            </div>
                            <div class="ticket-item medium">
                                <div class="ticket-id">INC-2024-0845</div>
                                <div class="ticket-title">Email Delivery Delays</div>
                                <div class="ticket-meta">
                                    <span class="status-badge info">Medium</span>
                                </div>
                            </div>
                            <div class="ticket-item low">
                                <div class="ticket-id">REQ-2024-0412</div>
                                <div class="ticket-title">New User Account Request - John Smith</div>
                                <div class="ticket-meta">
                                    <span class="status-badge success">Request</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="features-grid">
                <div class="feature-card">
                    <div class="icon">🎫</div>
                    <h4>Incident Management</h4>
                    <p>Track and resolve issues with full audit trail</p>
                </div>
                <div class="feature-card">
                    <div class="icon">🔄</div>
                    <h4>Change Management</h4>
                    <p>Controlled change process with approvals</p>
                </div>
                <div class="feature-card">
                    <div class="icon">📚</div>
                    <h4>Knowledge Base</h4>
                    <p>Searchable solution articles and FAQs</p>
                </div>
            </div>

            <div class="page-footer">
                <span>HoL SIEM Product Brochure</span>
                <span>Page 6</span>
            </div>
        </div>

        <!-- Page 7: DLP & Security -->
        <div class="page section-page">
            <div class="page-header">
                <div class="page-header-icon">🔐</div>
                <div>
                    <h2>Data Loss Prevention</h2>
                    <p>Protect sensitive data with content inspection and policy enforcement</p>
                </div>
            </div>

            <div class="screenshot">
                <div class="screenshot-header">
                    <div class="screenshot-dot red"></div>
                    <div class="screenshot-dot yellow"></div>
                    <div class="screenshot-dot green"></div>
                    <span class="screenshot-title">HoL SIEM - Data Loss Prevention</span>
                </div>
                <div class="screenshot-body">
                    <div style="padding: 15px;">
                        <div class="dlp-stats">
                            <div class="dlp-stat">
                                <div class="dlp-stat-icon">📧</div>
                                <div class="dlp-stat-value" style="color: #10b981;">12,456</div>
                                <div class="dlp-stat-label">Emails Scanned</div>
                            </div>
                            <div class="dlp-stat">
                                <div class="dlp-stat-icon">📁</div>
                                <div class="dlp-stat-value" style="color: #3b82f6;">8,234</div>
                                <div class="dlp-stat-label">Files Analyzed</div>
                            </div>
                            <div class="dlp-stat">
                                <div class="dlp-stat-icon">🚫</div>
                                <div class="dlp-stat-value" style="color: #ef4444;">47</div>
                                <div class="dlp-stat-label">Violations Blocked</div>
                            </div>
                        </div>

                        <table class="table-mock">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>User</th>
                                    <th>Action</th>
                                    <th>Policy</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>14:32:15</td>
                                    <td>john.smith</td>
                                    <td>Email attachment</td>
                                    <td>PII Protection</td>
                                    <td><span class="status-badge danger">Blocked</span></td>
                                </tr>
                                <tr>
                                    <td>14:28:42</td>
                                    <td>mary.jones</td>
                                    <td>USB file copy</td>
                                    <td>Endpoint Control</td>
                                    <td><span class="status-badge warning">Warned</span></td>
                                </tr>
                                <tr>
                                    <td>14:15:08</td>
                                    <td>admin</td>
                                    <td>Cloud upload</td>
                                    <td>Cloud DLP</td>
                                    <td><span class="status-badge success">Allowed</span></td>
                                </tr>
                                <tr>
                                    <td>13:58:33</td>
                                    <td>bob.wilson</td>
                                    <td>Print document</td>
                                    <td>Print Control</td>
                                    <td><span class="status-badge danger">Blocked</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="module-list">
                <div class="module-item">
                    <div class="icon">📧</div>
                    <div>
                        <h5>Email Protection</h5>
                        <p>Scan attachments and prevent data leaks</p>
                    </div>
                </div>
                <div class="module-item">
                    <div class="icon">💻</div>
                    <div>
                        <h5>Endpoint Control</h5>
                        <p>USB, print, and clipboard monitoring</p>
                    </div>
                </div>
                <div class="module-item">
                    <div class="icon">☁️</div>
                    <div>
                        <h5>Cloud DLP</h5>
                        <p>Protect data in cloud applications</p>
                    </div>
                </div>
                <div class="module-item">
                    <div class="icon">📋</div>
                    <div>
                        <h5>Policy Engine</h5>
                        <p>Flexible rules with regex and keywords</p>
                    </div>
                </div>
            </div>

            <div class="page-footer">
                <span>HoL SIEM Product Brochure</span>
                <span>Page 7</span>
            </div>
        </div>

        <!-- Page 8: Alarms & Alerts -->
        <div class="page section-page">
            <div class="page-header">
                <div class="page-header-icon">🚨</div>
                <div>
                    <h2>Alarm Management & Alerting</h2>
                    <p>Centralized alarm console with priority-based escalation</p>
                </div>
            </div>

            <div class="screenshot">
                <div class="screenshot-header">
                    <div class="screenshot-dot red"></div>
                    <div class="screenshot-dot yellow"></div>
                    <div class="screenshot-dot green"></div>
                    <span class="screenshot-title">HoL SIEM - Alarm Console</span>
                </div>
                <div class="screenshot-body">
                    <div style="padding: 15px;">
                        <div class="stats-row" style="margin-bottom: 15px;">
                            <div class="stat-card red">
                                <div class="stat-value">4</div>
                                <div class="stat-label">Critical</div>
                            </div>
                            <div class="stat-card yellow">
                                <div class="stat-value">12</div>
                                <div class="stat-label">Warning</div>
                            </div>
                            <div class="stat-card blue">
                                <div class="stat-value">28</div>
                                <div class="stat-label">Info</div>
                            </div>
                            <div class="stat-card green">
                                <div class="stat-value">156</div>
                                <div class="stat-label">Cleared Today</div>
                            </div>
                        </div>

                        <div class="alarm-list">
                            <div class="alarm-item critical">
                                <span class="alarm-time">14:32:15</span>
                                <span class="alarm-message">🔴 CRITICAL: Tank T-105 level below LL threshold (10%)</span>
                            </div>
                            <div class="alarm-item critical">
                                <span class="alarm-time">14:28:42</span>
                                <span class="alarm-message">🔴 CRITICAL: PLC-Main-001 communication failure</span>
                            </div>
                            <div class="alarm-item warning">
                                <span class="alarm-time">14:15:08</span>
                                <span class="alarm-message">🟡 WARNING: Pump PMP-003 efficiency dropped to 78%</span>
                            </div>
                            <div class="alarm-item warning">
                                <span class="alarm-time">13:58:33</span>
                                <span class="alarm-message">🟡 WARNING: Pipeline P-004 pressure drop detected</span>
                            </div>
                            <div class="alarm-item info">
                                <span class="alarm-time">13:45:21</span>
                                <span class="alarm-message">🔵 INFO: Scheduled maintenance started on T-102</span>
                            </div>
                            <div class="alarm-item info">
                                <span class="alarm-time">13:30:00</span>
                                <span class="alarm-message">🔵 INFO: Backup completed successfully</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="features-grid">
                <div class="feature-card">
                    <div class="icon">📱</div>
                    <h4>Multi-Channel Alerts</h4>
                    <p>Email, SMS, push notifications, and webhooks</p>
                </div>
                <div class="feature-card">
                    <div class="icon">⏫</div>
                    <h4>Escalation Rules</h4>
                    <p>Automatic escalation based on severity and time</p>
                </div>
                <div class="feature-card">
                    <div class="icon">📊</div>
                    <h4>Alarm Analytics</h4>
                    <p>Trending, correlation, and root cause analysis</p>
                </div>
            </div>

            <div class="page-footer">
                <span>HoL SIEM Product Brochure</span>
                <span>Page 8</span>
            </div>
        </div>

        <!-- Page 9: Reports & Training -->
        <div class="page section-page">
            <div class="page-header">
                <div class="page-header-icon">📈</div>
                <div>
                    <h2>Reports & Training Center</h2>
                    <p>Comprehensive reporting and user training capabilities</p>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="screenshot">
                    <div class="screenshot-header">
                        <div class="screenshot-dot red"></div>
                        <div class="screenshot-dot yellow"></div>
                        <div class="screenshot-dot green"></div>
                        <span class="screenshot-title">Reports Module</span>
                    </div>
                    <div class="screenshot-body" style="padding: 15px;">
                        <div style="color: #94a3b8; font-size: 11px; margin-bottom: 10px;">Available Reports</div>
                        <div style="display: flex; flex-direction: column; gap: 8px;">
                            <div style="background: #2d3748; padding: 10px; border-radius: 6px; display: flex; align-items: center; gap: 10px;">
                                <span style="font-size: 20px;">📊</span>
                                <div>
                                    <div style="color: white; font-size: 12px;">Executive Summary</div>
                                    <div style="color: #94a3b8; font-size: 10px;">Weekly security overview</div>
                                </div>
                            </div>
                            <div style="background: #2d3748; padding: 10px; border-radius: 6px; display: flex; align-items: center; gap: 10px;">
                                <span style="font-size: 20px;">🛡️</span>
                                <div>
                                    <div style="color: white; font-size: 12px;">Vulnerability Report</div>
                                    <div style="color: #94a3b8; font-size: 10px;">CVE analysis and remediation</div>
                                </div>
                            </div>
                            <div style="background: #2d3748; padding: 10px; border-radius: 6px; display: flex; align-items: center; gap: 10px;">
                                <span style="font-size: 20px;">📋</span>
                                <div>
                                    <div style="color: white; font-size: 12px;">Compliance Report</div>
                                    <div style="color: #94a3b8; font-size: 10px;">Regulatory compliance status</div>
                                </div>
                            </div>
                            <div style="background: #2d3748; padding: 10px; border-radius: 6px; display: flex; align-items: center; gap: 10px;">
                                <span style="font-size: 20px;">🏭</span>
                                <div>
                                    <div style="color: white; font-size: 12px;">SCADA Operations</div>
                                    <div style="color: #94a3b8; font-size: 10px;">Industrial process analytics</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="screenshot">
                    <div class="screenshot-header">
                        <div class="screenshot-dot red"></div>
                        <div class="screenshot-dot yellow"></div>
                        <div class="screenshot-dot green"></div>
                        <span class="screenshot-title">Training Center</span>
                    </div>
                    <div class="screenshot-body" style="padding: 15px;">
                        <div style="color: #94a3b8; font-size: 11px; margin-bottom: 10px;">Training Modules</div>
                        <div style="display: flex; flex-direction: column; gap: 8px;">
                            <div style="background: #2d3748; padding: 10px; border-radius: 6px; display: flex; align-items: center; gap: 10px;">
                                <span style="font-size: 20px;">🎓</span>
                                <div style="flex: 1;">
                                    <div style="color: white; font-size: 12px;">SCADA Operations</div>
                                    <div style="color: #94a3b8; font-size: 10px;">6 modules</div>
                                </div>
                                <div style="background: #10b981; padding: 3px 8px; border-radius: 10px; font-size: 9px; color: white;">Complete</div>
                            </div>
                            <div style="background: #2d3748; padding: 10px; border-radius: 6px; display: flex; align-items: center; gap: 10px;">
                                <span style="font-size: 20px;">🔒</span>
                                <div style="flex: 1;">
                                    <div style="color: white; font-size: 12px;">ICS Security</div>
                                    <div style="color: #94a3b8; font-size: 10px;">4 modules</div>
                                </div>
                                <div style="background: #f59e0b; padding: 3px 8px; border-radius: 10px; font-size: 9px; color: white;">In Progress</div>
                            </div>
                            <div style="background: #2d3748; padding: 10px; border-radius: 6px; display: flex; align-items: center; gap: 10px;">
                                <span style="font-size: 20px;">☁️</span>
                                <div style="flex: 1;">
                                    <div style="color: white; font-size: 12px;">Cloud Deployment</div>
                                    <div style="color: #94a3b8; font-size: 10px;">3 modules</div>
                                </div>
                                <div style="background: #3b82f6; padding: 3px 8px; border-radius: 10px; font-size: 9px; color: white;">New</div>
                            </div>
                            <div style="background: #2d3748; padding: 10px; border-radius: 6px; display: flex; align-items: center; gap: 10px;">
                                <span style="font-size: 20px;">📖</span>
                                <div style="flex: 1;">
                                    <div style="color: white; font-size: 12px;">User Administration</div>
                                    <div style="color: #94a3b8; font-size: 10px;">5 modules</div>
                                </div>
                                <div style="background: #64748b; padding: 3px 8px; border-radius: 10px; font-size: 9px; color: white;">Not Started</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="module-list" style="margin-top: 25px;">
                <div class="module-item">
                    <div class="icon">📄</div>
                    <div>
                        <h5>PDF Export</h5>
                        <p>Professional reports in multiple formats</p>
                    </div>
                </div>
                <div class="module-item">
                    <div class="icon">📅</div>
                    <div>
                        <h5>Scheduled Reports</h5>
                        <p>Automatic delivery to stakeholders</p>
                    </div>
                </div>
                <div class="module-item">
                    <div class="icon">🎯</div>
                    <div>
                        <h5>Interactive Learning</h5>
                        <p>Progress tracking and certifications</p>
                    </div>
                </div>
                <div class="module-item">
                    <div class="icon">📚</div>
                    <div>
                        <h5>Documentation</h5>
                        <p>Architecture and installation guides</p>
                    </div>
                </div>
            </div>

            <div class="page-footer">
                <span>HoL SIEM Product Brochure</span>
                <span>Page 9</span>
            </div>
        </div>

        <!-- Page 10: CTA -->
        <div class="page cta-section">
            <div style="font-size: 80px; margin-bottom: 30px;">🚀</div>
            <h2>Ready to Transform Your Operations?</h2>
            <p>Contact us today for a personalized demo and see how HoL SIEM can unify your IT and OT security operations.</p>

            <div class="cta-buttons">
                <a href="#" class="cta-btn primary">Request a Demo</a>
                <a href="#" class="cta-btn secondary">Download Datasheet</a>
            </div>

            <div style="margin-top: 60px; display: flex; gap: 50px;">
                <div style="text-align: center;">
                    <div style="font-size: 28px; margin-bottom: 5px;">📧</div>
                    <div style="font-size: 12px; opacity: 0.7;">Email</div>
                    <div style="font-size: 14px; font-weight: 600;">sales@ioc-siem.com</div>
                </div>
                <div style="text-align: center;">
                    <div style="font-size: 28px; margin-bottom: 5px;">📞</div>
                    <div style="font-size: 12px; opacity: 0.7;">Phone</div>
                    <div style="font-size: 14px; font-weight: 600;">+1 (800) HOL-SIEM</div>
                </div>
                <div style="text-align: center;">
                    <div style="font-size: 28px; margin-bottom: 5px;">🌐</div>
                    <div style="font-size: 12px; opacity: 0.7;">Website</div>
                    <div style="font-size: 14px; font-weight: 600;">www.ioc-siem.com</div>
                </div>
            </div>

            <div style="margin-top: 80px; opacity: 0.6; font-size: 12px;">
                <p>© <?= date('Y') ?> HoL SIEM. All rights reserved.</p>
                <p style="margin-top: 10px;">HoL SIEM is a trademark of <?= htmlspecialchars($company_name) ?>.</p>
            </div>
        </div>
    </div>
</body>
</html>
