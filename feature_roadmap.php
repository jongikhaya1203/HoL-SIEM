<?php
/**
 * Complete Feature Roadmap
 * Shows all implemented and planned features
 */

require_once 'classes/Database.php';
$db = Database::getInstance();

// Get implementation status
$implementedFeatures = [
    // Q1 2025 Features (All Implemented)
    'q1' => [
        ['name' => 'SNMP v1/v2c/v3 Support', 'status' => 'implemented', 'module' => 'modules/snmp_monitor.php', 'icon' => '✅'],
        ['name' => 'Real-Time Monitoring Engine', 'status' => 'implemented', 'module' => 'classes/RealtimeMonitor.php', 'icon' => '✅'],
        ['name' => 'Alert Management System', 'status' => 'implemented', 'module' => 'classes/AlertManager.php', 'icon' => '✅'],
        ['name' => 'Network Device Discovery', 'status' => 'implemented', 'module' => 'classes/DeviceDiscovery.php', 'icon' => '✅'],
    ],
    // Q2 2025 Features (All Implemented)
    'q2' => [
        ['name' => 'Performance Baselines', 'status' => 'implemented', 'module' => 'classes/PerformanceBaseline.php', 'icon' => '✅'],
        ['name' => 'Network Topology Mapper', 'status' => 'implemented', 'module' => 'modules/network_topology.php', 'icon' => '✅'],
        ['name' => 'NetFlow/sFlow Analysis', 'status' => 'implemented', 'module' => 'modules/netflow_analyzer.php', 'icon' => '✅'],
        ['name' => 'Custom Dashboard Widgets', 'status' => 'implemented', 'module' => 'custom_dashboard.php', 'icon' => '✅'],
        ['name' => 'API Rate Limiting', 'status' => 'implemented', 'module' => 'classes/RateLimiter.php', 'icon' => '✅'],
        ['name' => 'Performance Metrics', 'status' => 'implemented', 'module' => 'modules/performance_metrics.php', 'icon' => '✅'],
    ],
    // Q3 2025 Features (In Progress)
    'q3' => [
        ['name' => 'Configuration Management', 'status' => 'in_progress', 'module' => null, 'icon' => '🔄'],
        ['name' => 'Log Management Integration', 'status' => 'in_progress', 'module' => null, 'icon' => '🔄'],
        ['name' => 'Automated Remediation', 'status' => 'in_progress', 'module' => null, 'icon' => '🔄'],
        ['name' => 'VoIP Monitoring', 'status' => 'planned', 'module' => null, 'icon' => '📋'],
        ['name' => 'Wireless Monitoring', 'status' => 'planned', 'module' => null, 'icon' => '📋'],
    ],
    // Q4 2025 Features (Planned)
    'q4' => [
        ['name' => 'Mobile Apps (iOS/Android)', 'status' => 'planned', 'module' => null, 'icon' => '📋'],
        ['name' => 'Multi-Tenant Support', 'status' => 'planned', 'module' => null, 'icon' => '📋'],
        ['name' => 'SLA Monitoring', 'status' => 'planned', 'module' => null, 'icon' => '📋'],
        ['name' => 'Advanced Analytics', 'status' => 'planned', 'module' => null, 'icon' => '📋'],
        ['name' => 'White-Label Options', 'status' => 'planned', 'module' => null, 'icon' => '📋'],
    ]
];

// Calculate statistics
$totalFeatures = 0;
$implemented = 0;
$inProgress = 0;
$planned = 0;

foreach ($implementedFeatures as $quarter => $features) {
    foreach ($features as $feature) {
        $totalFeatures++;
        if ($feature['status'] === 'implemented') $implemented++;
        elseif ($feature['status'] === 'in_progress') $inProgress++;
        else $planned++;
    }
}

$completionRate = round(($implemented / $totalFeatures) * 100, 1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feature Roadmap 2025 - IOC</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            color: white;
            margin-bottom: 40px;
        }

        .header h1 {
            font-size: 48px;
            margin-bottom: 15px;
        }

        .header p {
            font-size: 20px;
            opacity: 0.9;
        }

        .stats-bar {
            background: white;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .stat-item {
            text-align: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .stat-number {
            font-size: 36px;
            font-weight: bold;
            color: #667eea;
        }

        .stat-label {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
        }

        .progress-bar {
            width: 100%;
            height: 30px;
            background: #e0e0e0;
            border-radius: 15px;
            overflow: hidden;
            margin-top: 20px;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #4CAF50, #8BC34A);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            transition: width 1s;
        }

        .quarter-section {
            background: white;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .quarter-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 3px solid #667eea;
        }

        .quarter-title {
            font-size: 28px;
            color: #667eea;
            font-weight: 600;
        }

        .quarter-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-complete { background: #e8f5e9; color: #2e7d32; }
        .badge-progress { background: #fff3e0; color: #f57c00; }
        .badge-planned { background: #e3f2fd; color: #1976d2; }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 15px;
        }

        .feature-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #667eea;
            transition: all 0.3s;
        }

        .feature-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .feature-card.implemented {
            border-left-color: #4CAF50;
            background: linear-gradient(to right, #e8f5e9, #f8f9fa);
        }

        .feature-card.in-progress {
            border-left-color: #FF9800;
            background: linear-gradient(to right, #fff3e0, #f8f9fa);
        }

        .feature-card.planned {
            border-left-color: #2196F3;
            background: linear-gradient(to right, #e3f2fd, #f8f9fa);
        }

        .feature-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }

        .feature-icon {
            font-size: 24px;
        }

        .feature-name {
            font-size: 16px;
            font-weight: 600;
            color: #333;
        }

        .feature-link {
            display: inline-block;
            margin-top: 10px;
            padding: 6px 12px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }

        .feature-link:hover {
            background: #764ba2;
        }

        .legend {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .legend-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .legend-icon {
            font-size: 24px;
        }

        .back-button {
            text-align: center;
            margin-top: 30px;
        }

        .back-button a {
            display: inline-block;
            padding: 15px 40px;
            background: white;
            color: #667eea;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 18px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 Feature Roadmap 2025</h1>
            <p>HoL Intelligent Operating Centre - Enterprise Network Management</p>
        </div>

        <!-- Statistics -->
        <div class="stats-bar">
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-number"><?= $totalFeatures ?></div>
                    <div class="stat-label">Total Features</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= $implemented ?></div>
                    <div class="stat-label">Implemented</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= $inProgress ?></div>
                    <div class="stat-label">In Progress</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= $planned ?></div>
                    <div class="stat-label">Planned</div>
                </div>
            </div>
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?= $completionRate ?>%">
                    <?= $completionRate ?>% Complete
                </div>
            </div>
        </div>

        <!-- Legend -->
        <div class="legend">
            <h3 style="margin-bottom: 15px; color: #333;">Legend</h3>
            <div class="legend-grid">
                <div class="legend-item">
                    <span class="legend-icon">✅</span>
                    <span><strong>Implemented</strong> - Fully functional</span>
                </div>
                <div class="legend-item">
                    <span class="legend-icon">🔄</span>
                    <span><strong>In Progress</strong> - Under development</span>
                </div>
                <div class="legend-item">
                    <span class="legend-icon">📋</span>
                    <span><strong>Planned</strong> - Scheduled for development</span>
                </div>
            </div>
        </div>

        <!-- Q1 2025 -->
        <div class="quarter-section">
            <div class="quarter-header">
                <h2 class="quarter-title">Q1 2025 (Jan-Mar)</h2>
                <span class="quarter-badge badge-complete">All Complete</span>
            </div>
            <div class="features-grid">
                <?php foreach ($implementedFeatures['q1'] as $feature): ?>
                <div class="feature-card <?= $feature['status'] ?>">
                    <div class="feature-header">
                        <span class="feature-icon"><?= $feature['icon'] ?></span>
                        <span class="feature-name"><?= $feature['name'] ?></span>
                    </div>
                    <?php if ($feature['module']): ?>
                    <a href="<?= $feature['module'] ?>" class="feature-link">View Module →</a>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Q2 2025 -->
        <div class="quarter-section">
            <div class="quarter-header">
                <h2 class="quarter-title">Q2 2025 (Apr-Jun)</h2>
                <span class="quarter-badge badge-complete">All Complete</span>
            </div>
            <div class="features-grid">
                <?php foreach ($implementedFeatures['q2'] as $feature): ?>
                <div class="feature-card <?= $feature['status'] ?>">
                    <div class="feature-header">
                        <span class="feature-icon"><?= $feature['icon'] ?></span>
                        <span class="feature-name"><?= $feature['name'] ?></span>
                    </div>
                    <?php if ($feature['module']): ?>
                    <a href="<?= $feature['module'] ?>" class="feature-link">View Module →</a>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Q3 2025 -->
        <div class="quarter-section">
            <div class="quarter-header">
                <h2 class="quarter-title">Q3 2025 (Jul-Sep)</h2>
                <span class="quarter-badge badge-progress">In Progress</span>
            </div>
            <div class="features-grid">
                <?php foreach ($implementedFeatures['q3'] as $feature): ?>
                <div class="feature-card <?= $feature['status'] ?>">
                    <div class="feature-header">
                        <span class="feature-icon"><?= $feature['icon'] ?></span>
                        <span class="feature-name"><?= $feature['name'] ?></span>
                    </div>
                    <?php if ($feature['module']): ?>
                    <a href="<?= $feature['module'] ?>" class="feature-link">View Module →</a>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Q4 2025 -->
        <div class="quarter-section">
            <div class="quarter-header">
                <h2 class="quarter-title">Q4 2025 (Oct-Dec)</h2>
                <span class="quarter-badge badge-planned">Planned</span>
            </div>
            <div class="features-grid">
                <?php foreach ($implementedFeatures['q4'] as $feature): ?>
                <div class="feature-card <?= $feature['status'] ?>">
                    <div class="feature-header">
                        <span class="feature-icon"><?= $feature['icon'] ?></span>
                        <span class="feature-name"><?= $feature['name'] ?></span>
                    </div>
                    <?php if ($feature['module']): ?>
                    <a href="<?= $feature['module'] ?>" class="feature-link">View Module →</a>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="back-button">
            <a href="index.php">← Back to Dashboard</a>
        </div>
    </div>
</body>
</html>
