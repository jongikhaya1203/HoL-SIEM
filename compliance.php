<?php
/**
 * Compliance Dashboard
 * Monitor compliance with various security and regulatory frameworks
 */

require_once __DIR__ . '/classes/Database.php';
$db = Database::getInstance();

// Load settings for logo and app name
try {
    $settings_result = $db->fetchAll("SELECT setting_key, setting_value FROM settings");
    $settings = [];
    foreach ($settings_result as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (Exception $e) {
    $settings = [
        'app_name' => 'Network Security Scanner',
        'logo_url' => '',
        'theme_color' => '#667eea'
    ];
}

$app_name = $settings['app_name'] ?? 'Network Security Scanner';
$logo_url = $settings['logo_url'] ?? '';

// Compliance Frameworks
$frameworks = [
    [
        'name' => 'PCI-DSS 4.0',
        'description' => 'Payment Card Industry Data Security Standard',
        'total_controls' => 324,
        'compliant' => 289,
        'non_compliant' => 24,
        'not_tested' => 11,
        'score' => 89,
        'status' => 'Compliant',
        'last_audit' => date('Y-m-d', strtotime('-15 days')),
        'next_audit' => date('Y-m-d', strtotime('+165 days'))
    ],
    [
        'name' => 'HIPAA',
        'description' => 'Health Insurance Portability and Accountability Act',
        'total_controls' => 182,
        'compliant' => 165,
        'non_compliant' => 12,
        'not_tested' => 5,
        'score' => 91,
        'status' => 'Compliant',
        'last_audit' => date('Y-m-d', strtotime('-22 days')),
        'next_audit' => date('Y-m-d', strtotime('+158 days'))
    ],
    [
        'name' => 'GDPR',
        'description' => 'General Data Protection Regulation',
        'total_controls' => 156,
        'compliant' => 138,
        'non_compliant' => 15,
        'not_tested' => 3,
        'score' => 88,
        'status' => 'Compliant',
        'last_audit' => date('Y-m-d', strtotime('-30 days')),
        'next_audit' => date('Y-m-d', strtotime('+150 days'))
    ],
    [
        'name' => 'SOC 2 Type II',
        'description' => 'Service Organization Control 2',
        'total_controls' => 245,
        'compliant' => 198,
        'non_compliant' => 38,
        'not_tested' => 9,
        'score' => 81,
        'status' => 'Partial',
        'last_audit' => date('Y-m-d', strtotime('-45 days')),
        'next_audit' => date('Y-m-d', strtotime('+135 days'))
    ],
    [
        'name' => 'ISO 27001:2022',
        'description' => 'Information Security Management',
        'total_controls' => 196,
        'compliant' => 172,
        'non_compliant' => 19,
        'not_tested' => 5,
        'score' => 88,
        'status' => 'Compliant',
        'last_audit' => date('Y-m-d', strtotime('-18 days')),
        'next_audit' => date('Y-m-d', strtotime('+162 days'))
    ],
    [
        'name' => 'NIST CSF',
        'description' => 'NIST Cybersecurity Framework',
        'total_controls' => 298,
        'compliant' => 245,
        'non_compliant' => 42,
        'not_tested' => 11,
        'score' => 82,
        'status' => 'Partial',
        'last_audit' => date('Y-m-d', strtotime('-12 days')),
        'next_audit' => date('Y-m-d', strtotime('+168 days'))
    ]
];

// Calculate totals
$total_frameworks = count($frameworks);
$compliant_frameworks = count(array_filter($frameworks, fn($f) => $f['status'] === 'Compliant'));
$total_controls = array_sum(array_column($frameworks, 'total_controls'));
$total_compliant = array_sum(array_column($frameworks, 'compliant'));
$avg_score = round(array_sum(array_column($frameworks, 'score')) / $total_frameworks);

// Recent compliance issues
$recentIssues = [
    [
        'framework' => 'PCI-DSS 4.0',
        'control' => 'Requirement 8.3.2',
        'description' => 'Strong cryptography not enforced for password storage',
        'severity' => 'High',
        'discovered' => date('Y-m-d', strtotime('-5 days')),
        'status' => 'Open',
        'assigned_to' => 'Security Team'
    ],
    [
        'framework' => 'SOC 2 Type II',
        'control' => 'CC6.1',
        'description' => 'Insufficient logging of security events',
        'severity' => 'Medium',
        'discovered' => date('Y-m-d', strtotime('-8 days')),
        'status' => 'In Progress',
        'assigned_to' => 'IT Operations'
    ],
    [
        'framework' => 'GDPR',
        'control' => 'Article 32',
        'description' => 'Data encryption at rest not implemented for all databases',
        'severity' => 'High',
        'discovered' => date('Y-m-d', strtotime('-12 days')),
        'status' => 'Open',
        'assigned_to' => 'Database Team'
    ],
    [
        'framework' => 'HIPAA',
        'control' => '164.312(a)(2)(iv)',
        'description' => 'Missing encryption for data in transit',
        'severity' => 'Critical',
        'discovered' => date('Y-m-d', strtotime('-3 days')),
        'status' => 'Open',
        'assigned_to' => 'Security Team'
    ],
    [
        'framework' => 'NIST CSF',
        'control' => 'PR.AC-4',
        'description' => 'Access permissions review overdue',
        'severity' => 'Medium',
        'discovered' => date('Y-m-d', strtotime('-15 days')),
        'status' => 'Resolved',
        'assigned_to' => 'Compliance Team'
    ]
];

// Compliance trend (last 6 months)
$complianceTrend = [
    ['month' => date('M Y', strtotime('-5 months')), 'score' => 78],
    ['month' => date('M Y', strtotime('-4 months')), 'score' => 81],
    ['month' => date('M Y', strtotime('-3 months')), 'score' => 84],
    ['month' => date('M Y', strtotime('-2 months')), 'score' => 86],
    ['month' => date('M Y', strtotime('-1 month')), 'score' => 85],
    ['month' => date('M Y'), 'score' => $avg_score]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compliance Dashboard | <?= htmlspecialchars($app_name) ?></title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        .container { max-width: 1400px; margin: 0 auto; }

        .header {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            margin-bottom: 30px;
        }
        .header h1 { color: #667eea; font-size: 36px; margin-bottom: 10px; }
        .header p { color: #666; font-size: 16px; }
        .header-nav {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        .nav-btn {
            background: white;
            color: #667eea;
            padding: 10px 20px;
            border: 2px solid #667eea;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }
        .nav-btn:hover {
            background: #667eea;
            color: white;
        }
        .nav-btn.active {
            background: #667eea;
            color: white;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .stat-icon { font-size: 48px; margin-bottom: 15px; }
        .stat-number { font-size: 36px; font-weight: bold; color: #333; }
        .stat-label { font-size: 14px; color: #666; margin-top: 5px; }

        .card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            margin-bottom: 25px;
        }
        .card h2 {
            color: #667eea;
            font-size: 24px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 3px solid #667eea;
        }

        .framework-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .framework-card {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            border-left: 5px solid #667eea;
            transition: all 0.3s;
        }
        .framework-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        .framework-card h3 {
            color: #333;
            font-size: 20px;
            margin-bottom: 5px;
        }
        .framework-card .description {
            color: #666;
            font-size: 13px;
            margin-bottom: 15px;
        }

        .progress-bar {
            height: 12px;
            background: #e0e0e0;
            border-radius: 6px;
            overflow: hidden;
            margin: 15px 0;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #4CAF50, #8BC34A);
            transition: width 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            color: white;
            font-weight: bold;
        }

        .metrics {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin: 15px 0;
        }
        .metric {
            text-align: center;
            padding: 10px;
            background: white;
            border-radius: 6px;
        }
        .metric-value {
            font-size: 20px;
            font-weight: bold;
            color: #333;
        }
        .metric-label {
            font-size: 11px;
            color: #666;
            margin-top: 5px;
        }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-compliant { background: #e8f5e9; color: #2e7d32; }
        .badge-partial { background: #fff3e0; color: #e65100; }
        .badge-non-compliant { background: #ffebee; color: #c62828; }
        .badge-critical { background: #ffebee; color: #c62828; }
        .badge-high { background: #fff3e0; color: #e65100; }
        .badge-medium { background: #fff9c4; color: #f57f17; }
        .badge-low { background: #e8f5e9; color: #2e7d32; }
        .badge-open { background: #ffebee; color: #c62828; }
        .badge-in-progress { background: #fff3e0; color: #e65100; }
        .badge-resolved { background: #e8f5e9; color: #2e7d32; }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th {
            background: #667eea;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #e0e0e0;
            font-size: 13px;
        }
        tr:hover { background: #f5f5f5; }

        .chart-container {
            position: relative;
            height: 350px;
            margin: 25px 0;
        }

        .footer {
            background: rgba(255,255,255,0.1);
            color: white;
            text-align: center;
            padding: 20px;
            border-radius: 10px;
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <?php if (!empty($logo_url)): ?>
            <div style="text-align: center; margin-bottom: 20px;">
                <img src="<?= htmlspecialchars($logo_url) ?>" alt="Logo" style="max-height: 80px; max-width: 300px;">
            </div>
            <?php endif; ?>
            <h1>📋 Compliance Dashboard</h1>
            <p>Monitor and manage compliance with security and regulatory frameworks</p>

            <div class="header-nav">
                <a href="index.php" class="nav-btn">🏠 Dashboard</a>
                <a href="scan.php" class="nav-btn">🔍 New Scan</a>
                <a href="reports.php" class="nav-btn">📊 Reports</a>
                <a href="compliance.php" class="nav-btn active">📋 Compliance</a>
            </div>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📊</div>
                <div class="stat-number"><?= $total_frameworks ?></div>
                <div class="stat-label">Compliance Frameworks</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-number"><?= $compliant_frameworks ?></div>
                <div class="stat-label">Compliant Frameworks</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">🎯</div>
                <div class="stat-number"><?= $avg_score ?>%</div>
                <div class="stat-label">Average Score</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">📈</div>
                <div class="stat-number"><?= round(($total_compliant / $total_controls) * 100) ?>%</div>
                <div class="stat-label">Overall Compliance</div>
            </div>
        </div>

        <!-- Compliance Frameworks -->
        <div class="card">
            <h2>Compliance Frameworks</h2>

            <div class="framework-grid">
                <?php foreach ($frameworks as $framework): ?>
                <div class="framework-card">
                    <h3><?= htmlspecialchars($framework['name']) ?></h3>
                    <div class="description"><?= htmlspecialchars($framework['description']) ?></div>

                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <span style="font-size: 24px; font-weight: bold; color: <?= $framework['score'] >= 85 ? '#4CAF50' : ($framework['score'] >= 70 ? '#FF9800' : '#f44336') ?>;">
                            <?= $framework['score'] ?>%
                        </span>
                        <span class="badge badge-<?= strtolower(str_replace(' ', '-', $framework['status'])) ?>">
                            <?= $framework['status'] ?>
                        </span>
                    </div>

                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?= $framework['score'] ?>%; background: <?= $framework['score'] >= 85 ? 'linear-gradient(90deg, #4CAF50, #8BC34A)' : ($framework['score'] >= 70 ? 'linear-gradient(90deg, #FF9800, #FFB300)' : 'linear-gradient(90deg, #f44336, #e91e63)') ?>;">
                            <?= $framework['score'] ?>%
                        </div>
                    </div>

                    <div class="metrics">
                        <div class="metric">
                            <div class="metric-value" style="color: #4CAF50;"><?= $framework['compliant'] ?></div>
                            <div class="metric-label">Compliant</div>
                        </div>
                        <div class="metric">
                            <div class="metric-value" style="color: #f44336;"><?= $framework['non_compliant'] ?></div>
                            <div class="metric-label">Non-Compliant</div>
                        </div>
                        <div class="metric">
                            <div class="metric-value" style="color: #FF9800;"><?= $framework['not_tested'] ?></div>
                            <div class="metric-label">Not Tested</div>
                        </div>
                        <div class="metric">
                            <div class="metric-value"><?= $framework['total_controls'] ?></div>
                            <div class="metric-label">Total</div>
                        </div>
                    </div>

                    <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd; font-size: 12px; color: #666;">
                        <div><strong>Last Audit:</strong> <?= $framework['last_audit'] ?></div>
                        <div style="margin-top: 5px;"><strong>Next Audit:</strong> <?= $framework['next_audit'] ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Compliance Trend -->
        <div class="card">
            <h2>Compliance Trend (6 Months)</h2>
            <div class="chart-container">
                <canvas id="trendChart"></canvas>
            </div>
        </div>

        <!-- Framework Distribution -->
        <div class="card">
            <h2>Framework Scores Comparison</h2>
            <div class="chart-container">
                <canvas id="frameworkChart"></canvas>
            </div>
        </div>

        <!-- Recent Compliance Issues -->
        <div class="card">
            <h2>Recent Compliance Issues</h2>
            <table>
                <thead>
                    <tr>
                        <th>Framework</th>
                        <th>Control</th>
                        <th>Description</th>
                        <th>Severity</th>
                        <th>Discovered</th>
                        <th>Status</th>
                        <th>Assigned To</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentIssues as $issue): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($issue['framework']) ?></strong></td>
                        <td><code><?= htmlspecialchars($issue['control']) ?></code></td>
                        <td><?= htmlspecialchars($issue['description']) ?></td>
                        <td>
                            <span class="badge badge-<?= strtolower($issue['severity']) ?>">
                                <?= $issue['severity'] ?>
                            </span>
                        </td>
                        <td><?= $issue['discovered'] ?></td>
                        <td>
                            <span class="badge badge-<?= strtolower(str_replace(' ', '-', $issue['status'])) ?>">
                                <?= $issue['status'] ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($issue['assigned_to']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($app_name) ?>. All rights reserved.</p>
            <p style="margin-top: 10px; font-size: 14px;">
                Compliance Monitoring & Security Framework Management
            </p>
        </div>
    </div>

    <script>
    // Compliance Trend Chart
    new Chart(document.getElementById('trendChart'), {
        type: 'line',
        data: {
            labels: [<?php echo implode(',', array_map(fn($t) => "'" . $t['month'] . "'", $complianceTrend)); ?>],
            datasets: [{
                label: 'Average Compliance Score (%)',
                data: [<?php echo implode(',', array_column($complianceTrend, 'score')); ?>],
                borderColor: '#667eea',
                backgroundColor: 'rgba(102, 126, 234, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#667eea',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 5,
                pointHoverRadius: 7
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                },
                title: {
                    display: true,
                    text: 'Compliance Score Trend',
                    font: { size: 16 }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    title: {
                        display: true,
                        text: 'Compliance Score (%)'
                    }
                }
            }
        }
    });

    // Framework Comparison Chart
    new Chart(document.getElementById('frameworkChart'), {
        type: 'bar',
        data: {
            labels: [<?php echo implode(',', array_map(fn($f) => "'" . $f['name'] . "'", $frameworks)); ?>],
            datasets: [{
                label: 'Compliance Score (%)',
                data: [<?php echo implode(',', array_column($frameworks, 'score')); ?>],
                backgroundColor: [
                    '#4CAF50',
                    '#8BC34A',
                    '#FFC107',
                    '#FF9800',
                    '#2196F3',
                    '#9C27B0'
                ],
                borderRadius: 8,
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                title: {
                    display: true,
                    text: 'Framework Compliance Scores',
                    font: { size: 16 }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    title: {
                        display: true,
                        text: 'Score (%)'
                    }
                }
            }
        }
    });
    </script>
</body>
</html>
