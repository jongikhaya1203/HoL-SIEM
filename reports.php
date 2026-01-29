<?php
/**
 * Reports Dashboard
 * View and manage vulnerability scan reports
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
        'app_name' => 'HoL SIEM',
        'logo_url' => '',
        'theme_color' => '#667eea'
    ];
}

$app_name = $settings['app_name'] ?? 'HoL SIEM';
$logo_url = $settings['logo_url'] ?? '';

// Get all reports - simplified query without severity counts
$reports = $db->fetchAll("
    SELECT r.*, s.target_range, s.scan_type, s.started_at, s.completed_at,
           COUNT(DISTINCT sr.id) as findings_count,
           0 as critical_count,
           0 as high_count,
           0 as medium_count,
           0 as low_count
    FROM reports r
    LEFT JOIN scans s ON r.scan_id = s.id
    LEFT JOIN scan_results sr ON s.id = sr.scan_id
    GROUP BY r.id
    ORDER BY r.id DESC
    LIMIT 50
");

// Get statistics
$total_reports = count($reports);
$total_scans = $db->fetchOne("SELECT COUNT(*) as count FROM scans")['count'];
$total_vulns = $db->fetchOne("SELECT COUNT(*) as count FROM vulnerabilities")['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vulnerability Reports | <?= htmlspecialchars($app_name) ?></title>
    <link rel="stylesheet" href="admin/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            text-align: center;
        }

        .nav {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .nav-btn {
            background: white;
            color: #667eea;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .nav-btn:hover {
            background: #667eea;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        .nav-btn.secondary {
            background: transparent;
            border: 2px solid white;
            color: white;
        }

        .nav-btn.secondary:hover {
            background: white;
            color: #667eea;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            margin-bottom: 25px;
        }

        .report-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .report-card {
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 20px;
            transition: all 0.3s;
            cursor: pointer;
        }

        .report-card:hover {
            border-color: #667eea;
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
            transform: translateY(-3px);
        }

        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .report-title {
            font-weight: bold;
            font-size: 16px;
            color: #333;
        }

        .report-date {
            font-size: 12px;
            color: #999;
        }

        .severity-badges {
            display: flex;
            gap: 8px;
            margin: 15px 0;
            flex-wrap: wrap;
        }

        .severity-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .severity-badge.critical {
            background: #ffebee;
            color: #c62828;
        }

        .severity-badge.high {
            background: #fff3e0;
            color: #e65100;
        }

        .severity-badge.medium {
            background: #fff9c4;
            color: #f57f17;
        }

        .severity-badge.low {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .report-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #764ba2;
        }

        .btn-secondary {
            background: #f5f5f5;
            color: #333;
        }

        .btn-secondary:hover {
            background: #e0e0e0;
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

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }

        .filter-bar {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-bar select,
        .filter-bar input {
            padding: 10px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
        }

        .filter-bar select:focus,
        .filter-bar input:focus {
            outline: none;
            border-color: #667eea;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div style="text-align: center; margin-bottom: 20px;">
                <?php if (!empty($logo_url)): ?>
                <img src="<?= htmlspecialchars($logo_url) ?>" alt="Logo" style="max-height: 80px; max-width: 300px;">
                <?php else: ?>
                <div style="display: inline-flex; align-items: center; gap: 15px;">
                    <div style="width: 60px; height: 60px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                        <span style="font-size: 28px; color: white; font-weight: bold;">H</span>
                    </div>
                    <div style="text-align: left;">
                        <div style="font-size: 24px; font-weight: 700; color: #667eea; letter-spacing: -0.5px;">HoL SIEM</div>
                        <div style="font-size: 11px; color: #888; text-transform: uppercase; letter-spacing: 1px;">Security Platform</div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <h1>📊 Vulnerability Reports</h1>
            <p class="subtitle">Comprehensive security assessment reports and findings</p>
            <div style="text-align: right; margin-top: 15px;">
                <a href="admin/login.php" style="color: #667eea; text-decoration: none; font-size: 14px;">
                    ⚙️ Admin Portal
                </a>
            </div>
        </div>

        <!-- Navigation -->
        <div class="nav">
            <a href="index.php" class="nav-btn secondary">Dashboard</a>
            <a href="scan.php" class="nav-btn secondary">New Scan</a>
            <a href="reports.php" class="nav-btn">View Reports</a>
            <a href="compliance.php" class="nav-btn secondary">Compliance</a>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📊</div>
                <div class="stat-info">
                    <div class="stat-number"><?= $total_reports ?></div>
                    <div class="stat-label">Total Reports</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">🔍</div>
                <div class="stat-info">
                    <div class="stat-number"><?= $total_scans ?></div>
                    <div class="stat-label">Scans Performed</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">🔓</div>
                <div class="stat-info">
                    <div class="stat-number"><?= $total_vulns ?></div>
                    <div class="stat-label">Vulnerabilities Found</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">📈</div>
                <div class="stat-info">
                    <div class="stat-number"><?= $total_scans > 0 ? round($total_vulns / $total_scans, 1) : 0 ?></div>
                    <div class="stat-label">Avg Findings per Scan</div>
                </div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="card">
            <div class="filter-bar">
                <label style="font-weight: 600;">Filter Reports:</label>
                <select id="filterType">
                    <option value="">All Types</option>
                    <option value="quick">Quick Scan</option>
                    <option value="full">Full Scan</option>
                    <option value="compliance">Compliance</option>
                </select>
                <select id="filterSeverity">
                    <option value="">All Severities</option>
                    <option value="critical">Critical Issues</option>
                    <option value="high">High Severity</option>
                    <option value="medium">Medium Severity</option>
                    <option value="low">Low Severity</option>
                </select>
                <input type="text" id="searchReports" placeholder="Search target or report..." style="flex: 1; min-width: 250px;">
            </div>
        </div>

        <!-- Reports -->
        <div class="card">
            <h2>📋 Scan Reports</h2>

            <?php if (empty($reports)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📄</div>
                    <h3>No Reports Available</h3>
                    <p>Start a new scan to generate vulnerability reports</p>
                    <a href="scan.php" class="btn btn-primary" style="margin-top: 20px;">
                        🔍 Start New Scan
                    </a>
                </div>
            <?php else: ?>
                <div class="report-grid">
                    <?php foreach ($reports as $report): ?>
                    <div class="report-card" onclick="window.location='view_report.php?id=<?= $report['id'] ?>'">
                        <div class="report-header">
                            <div>
                                <div class="report-title">
                                    <?= htmlspecialchars($report['title'] ?? 'Scan Report #' . $report['id']) ?>
                                </div>
                                <div style="font-size: 13px; color: #666; margin-top: 5px;">
                                    Target: <strong><?= htmlspecialchars($report['target_range'] ?? 'Unknown') ?></strong>
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <div style="background: #667eea; color: white; padding: 4px 12px; border-radius: 12px; font-size: 11px; font-weight: bold;">
                                    <?= strtoupper($report['scan_type'] ?? 'SCAN') ?>
                                </div>
                            </div>
                        </div>

                        <div class="report-date">
                            📅 <?= $report['started_at'] ? date('M d, Y', strtotime($report['started_at'])) : 'N/A' ?> •
                            ⏱️ <?= $report['started_at'] ? date('H:i', strtotime($report['started_at'])) : 'N/A' ?>
                        </div>

                        <div class="severity-badges">
                            <?php if ($report['critical_count'] > 0): ?>
                            <span class="severity-badge critical">
                                🔴 <?= $report['critical_count'] ?> Critical
                            </span>
                            <?php endif; ?>

                            <?php if ($report['high_count'] > 0): ?>
                            <span class="severity-badge high">
                                🟠 <?= $report['high_count'] ?> High
                            </span>
                            <?php endif; ?>

                            <?php if ($report['medium_count'] > 0): ?>
                            <span class="severity-badge medium">
                                🟡 <?= $report['medium_count'] ?> Medium
                            </span>
                            <?php endif; ?>

                            <?php if ($report['low_count'] > 0): ?>
                            <span class="severity-badge low">
                                🟢 <?= $report['low_count'] ?> Low
                            </span>
                            <?php endif; ?>
                        </div>

                        <div style="font-size: 13px; color: #666; padding-top: 10px; border-top: 1px solid #e0e0e0;">
                            <strong><?= $report['findings_count'] ?></strong> total findings
                        </div>

                        <div class="report-actions" onclick="event.stopPropagation();">
                            <a href="view_report.php?id=<?= $report['id'] ?>" class="btn btn-primary">
                                👁️ View Report
                            </a>
                            <a href="download_report.php?id=<?= $report['id'] ?>&format=pdf" class="btn btn-secondary">
                                📥 PDF
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Table View -->
                <div style="margin-top: 40px;">
                    <h3>📊 Detailed View</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Report ID</th>
                                <th>Title</th>
                                <th>Target</th>
                                <th>Scan Type</th>
                                <th>Critical</th>
                                <th>High</th>
                                <th>Medium</th>
                                <th>Low</th>
                                <th>Total</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reports as $report): ?>
                            <tr>
                                <td><strong>#<?= $report['id'] ?></strong></td>
                                <td><?= htmlspecialchars($report['title'] ?? 'Report #' . $report['id']) ?></td>
                                <td><?= htmlspecialchars($report['target_range'] ?? 'N/A') ?></td>
                                <td>
                                    <span class="badge badge-in-progress">
                                        <?= strtoupper($report['scan_type'] ?? 'SCAN') ?>
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    <?php if ($report['critical_count'] > 0): ?>
                                    <span class="badge badge-critical"><?= $report['critical_count'] ?></span>
                                    <?php else: ?>
                                    <span style="color: #ccc;">0</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center;">
                                    <?php if ($report['high_count'] > 0): ?>
                                    <span class="badge badge-high"><?= $report['high_count'] ?></span>
                                    <?php else: ?>
                                    <span style="color: #ccc;">0</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center;">
                                    <?php if ($report['medium_count'] > 0): ?>
                                    <span class="badge badge-medium"><?= $report['medium_count'] ?></span>
                                    <?php else: ?>
                                    <span style="color: #ccc;">0</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center;">
                                    <?php if ($report['low_count'] > 0): ?>
                                    <span class="badge badge-low"><?= $report['low_count'] ?></span>
                                    <?php else: ?>
                                    <span style="color: #ccc;">0</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center;"><strong><?= $report['findings_count'] ?></strong></td>
                                <td><?= $report['started_at'] ? date('M d, Y', strtotime($report['started_at'])) : 'N/A' ?></td>
                                <td>
                                    <a href="view_report.php?id=<?= $report['id'] ?>" class="btn btn-primary" style="padding: 6px 12px; font-size: 12px;">
                                        View
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($app_name) ?>. All rights reserved.</p>
            <p style="margin-top: 10px; font-size: 14px;">
                Powered by HoL SIEM Security Platform
            </p>
        </div>
    </div>

    <script>
    // Simple client-side filtering
    const searchInput = document.getElementById('searchReports');
    const typeFilter = document.getElementById('filterType');
    const severityFilter = document.getElementById('filterSeverity');

    function filterReports() {
        const searchTerm = searchInput.value.toLowerCase();
        const selectedType = typeFilter.value.toLowerCase();
        const selectedSeverity = severityFilter.value.toLowerCase();

        const cards = document.querySelectorAll('.report-card');

        cards.forEach(card => {
            const text = card.textContent.toLowerCase();
            const matchesSearch = text.includes(searchTerm);
            const matchesType = !selectedType || text.includes(selectedType);
            const matchesSeverity = !selectedSeverity || text.includes(selectedSeverity);

            if (matchesSearch && matchesType && matchesSeverity) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });
    }

    if (searchInput) searchInput.addEventListener('input', filterReports);
    if (typeFilter) typeFilter.addEventListener('change', filterReports);
    if (severityFilter) severityFilter.addEventListener('change', filterReports);
    </script>
</body>
</html>
