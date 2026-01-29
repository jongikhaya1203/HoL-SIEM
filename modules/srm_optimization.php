<?php
/**
 * Storage Resource Management - Deduplication & Classification
 */

require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/StorageScanner.php';

$db = Database::getInstance();
$scanner = new StorageScanner();

// Get statistics
$stats = $scanner->getScanStatistics();

// Get duplicate groups
$duplicateGroups = $scanner->getDuplicateGroups(20);

// Get recommendations
$recommendations = $scanner->getRecommendations('Pending');

// Calculate savings potential
$totalSavingsGB = round($stats['potential_savings_bytes'] / 1073741824, 2);
$wastedSpaceGB = round($stats['wasted_space_bytes'] / 1073741824, 2);
$totalStorageGB = round($stats['total_storage_bytes'] / 1073741824, 2);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Storage Optimization - Deduplication & Classification</title>
    <link rel="stylesheet" href="../admin/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        .container {
            max-width: 1600px;
            margin: 0 auto;
        }
        .header-bar {
            background: white;
            padding: 25px 35px;
            border-radius: 15px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        .header-bar h1 {
            margin: 0;
            color: #667eea;
            font-size: 32px;
        }
        .header-bar p {
            margin: 8px 0 0 0;
            color: #666;
            font-size: 16px;
        }
        .nav-links {
            display: flex;
            gap: 10px;
        }
        .back-btn {
            background: #667eea;
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }
        .back-btn:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            border-left: 5px solid;
        }
        .stat-card.savings { border-color: #4CAF50; }
        .stat-card.wasted { border-color: #f44336; }
        .stat-card.files { border-color: #2196F3; }
        .stat-card.recommendations { border-color: #FF9800; }
        .stat-number {
            font-size: 42px;
            font-weight: bold;
            margin-bottom: 8px;
            line-height: 1;
        }
        .stat-label {
            color: #666;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 25px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
        }
        .card h2 {
            color: #667eea;
            font-size: 24px;
            margin: 0 0 25px 0;
            padding-bottom: 15px;
            border-bottom: 3px solid #667eea;
        }
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            border-bottom: 2px solid #e0e0e0;
            flex-wrap: wrap;
        }
        .tab {
            padding: 12px 24px;
            background: #f5f5f5;
            border: none;
            border-radius: 8px 8px 0 0;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            color: #666;
            transition: all 0.3s;
        }
        .tab:hover {
            background: #e8e8e8;
            color: #333;
        }
        .tab.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
            animation: fadeIn 0.3s;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .dup-group {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 15px;
            border-left: 5px solid;
        }
        .dup-group.critical { border-color: #f44336; }
        .dup-group.high { border-color: #FF9800; }
        .dup-group.medium { border-color: #ffc107; }
        .dup-group.low { border-color: #4CAF50; }
        .metric-row {
            display: flex;
            justify-content: space-between;
            margin: 10px 0;
            font-size: 14px;
        }
        .metric-label {
            color: #666;
            font-weight: 500;
        }
        .metric-value {
            font-weight: bold;
            color: #333;
        }
        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            color: white;
        }
        .badge-critical { background: #f44336; }
        .badge-high { background: #FF9800; }
        .badge-medium { background: #ffc107; color: #333; }
        .badge-low { background: #4CAF50; }
        .badge-public { background: #2196F3; }
        .badge-internal { background: #FF9800; }
        .badge-confidential { background: #f44336; }
        .badge-restricted { background: #9C27B0; }
        .recommendation-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 15px;
            border-left: 5px solid;
        }
        .recommendation-card.critical { border-color: #f44336; }
        .recommendation-card.high { border-color: #FF9800; }
        .recommendation-card.medium { border-color: #ffc107; }
        .recommendation-card.low { border-color: #4CAF50; }
        .btn {
            background: #667eea;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s;
        }
        .btn:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }
        .btn-success { background: #4CAF50; }
        .btn-danger { background: #f44336; }
        .progress-bar {
            width: 100%;
            height: 10px;
            background: #e0e0e0;
            border-radius: 5px;
            overflow: hidden;
            margin: 10px 0;
        }
        .progress-fill {
            height: 100%;
            border-radius: 5px;
            transition: width 0.3s;
        }
        .progress-fill.green { background: #4CAF50; }
        .progress-fill.orange { background: #FF9800; }
        .progress-fill.red { background: #f44336; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        th {
            padding: 14px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
        }
        td {
            padding: 12px 14px;
            border-bottom: 1px solid #e0e0e0;
            font-size: 14px;
        }
        tbody tr:hover {
            background: #f5f5f5;
        }
        .chart-container {
            position: relative;
            height: 350px;
            margin: 25px 0;
        }
        .savings-highlight {
            background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
            border: 2px solid #4CAF50;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 25px;
            text-align: center;
        }
        .savings-amount {
            font-size: 56px;
            font-weight: bold;
            color: #4CAF50;
            margin: 10px 0;
        }
        .alert-banner {
            background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%);
            border-left: 5px solid #FF9800;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-bar">
            <div>
                <h1>🎯 Storage Optimization</h1>
                <p>Deduplication Analysis & Data Classification for Storage Efficiency</p>
            </div>
            <div class="nav-links">
                <a href="srm.php" class="back-btn">← Storage Monitoring</a>
                <a href="../index.php" class="back-btn">Dashboard</a>
            </div>
        </div>

        <?php if ($wastedSpaceGB > 1): ?>
        <div class="alert-banner">
            <strong>⚠️ Storage Optimization Opportunity Detected!</strong><br>
            Found <?= $stats['duplicate_groups'] ?> groups of duplicate files wasting <strong><?= $wastedSpaceGB ?> GB</strong> of storage space.
            Review recommendations below to optimize your storage infrastructure.
        </div>
        <?php endif; ?>

        <!-- Summary Statistics -->
        <div class="stats-grid">
            <div class="stat-card savings">
                <div class="stat-number" style="color: #4CAF50;"><?= $totalSavingsGB ?> GB</div>
                <div class="stat-label">Potential Savings</div>
                <div style="font-size: 12px; color: #666; margin-top: 5px;">
                    From deduplication & optimization
                </div>
            </div>
            <div class="stat-card wasted">
                <div class="stat-number" style="color: #f44336;"><?= $wastedSpaceGB ?> GB</div>
                <div class="stat-label">Wasted Space</div>
                <div style="font-size: 12px; color: #666; margin-top: 5px;">
                    By duplicate files
                </div>
            </div>
            <div class="stat-card files">
                <div class="stat-number" style="color: #2196F3;"><?= number_format($stats['total_files']) ?></div>
                <div class="stat-label">Files Scanned</div>
                <div style="font-size: 12px; color: #666; margin-top: 5px;">
                    Total: <?= $totalStorageGB ?> GB
                </div>
            </div>
            <div class="stat-card recommendations">
                <div class="stat-number" style="color: #FF9800;"><?= $stats['pending_recommendations'] ?></div>
                <div class="stat-label">Recommendations</div>
                <div style="font-size: 12px; color: #666; margin-top: 5px;">
                    Pending review
                </div>
            </div>
        </div>

        <!-- Main Content with Tabs -->
        <div class="card">
            <div class="tabs">
                <button class="tab active" onclick="switchTab('overview')">📊 Overview</button>
                <button class="tab" onclick="switchTab('duplicates')">🔄 Deduplication</button>
                <button class="tab" onclick="switchTab('classification')">🏷️ Classification</button>
                <button class="tab" onclick="switchTab('recommendations')">💡 Recommendations</button>
                <button class="tab" onclick="switchTab('tiers')">📦 Storage Tiers</button>
            </div>

            <!-- Overview Tab -->
            <div id="overview" class="tab-content active">
                <h2>Storage Optimization Overview</h2>

                <?php if ($totalSavingsGB > 5): ?>
                <div class="savings-highlight">
                    <h3 style="margin: 0 0 10px 0; color: #2e7d32;">Total Potential Savings</h3>
                    <div class="savings-amount"><?= $totalSavingsGB ?> GB</div>
                    <p style="color: #666; margin: 10px 0 0 0;">
                        Equivalent to <?= round($totalSavingsGB * 0.15 * 12, 2) ?> USD/year in storage costs
                    </p>
                </div>
                <?php endif; ?>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin: 30px 0;">
                    <div>
                        <h3>Storage Distribution by Classification</h3>
                        <div class="chart-container">
                            <canvas id="classificationChart"></canvas>
                        </div>
                    </div>
                    <div>
                        <h3>Data Sensitivity Levels</h3>
                        <div class="chart-container">
                            <canvas id="sensitivityChart"></canvas>
                        </div>
                    </div>
                </div>

                <h3 style="margin-top: 30px;">Classification Breakdown</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Data Classification</th>
                            <th>File Count</th>
                            <th>Total Size</th>
                            <th>Percentage</th>
                            <th>Avg File Size</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats['by_classification'] as $class):
                            $sizeGB = round($class['total_size'] / 1073741824, 2);
                            $percentage = ($totalStorageGB > 0) ? round(($class['total_size'] / $stats['total_storage_bytes']) * 100, 1) : 0;
                            $avgMB = round($class['total_size'] / $class['count'] / 1048576, 2);
                        ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($class['data_classification']) ?></strong></td>
                            <td><?= number_format($class['count']) ?></td>
                            <td><?= $sizeGB ?> GB</td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div class="progress-bar" style="flex: 1; max-width: 150px;">
                                        <div class="progress-fill green" style="width: <?= $percentage ?>%;"></div>
                                    </div>
                                    <span><?= $percentage ?>%</span>
                                </div>
                            </td>
                            <td><?= $avgMB ?> MB</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <h3 style="margin-top: 30px;">Sensitivity Level Distribution</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Sensitivity Level</th>
                            <th>File Count</th>
                            <th>Total Size</th>
                            <th>Recommended Tier</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats['by_sensitivity'] as $sens):
                            $sizeGB = round($sens['total_size'] / 1073741824, 2);
                            $tier = match($sens['sensitivity_level']) {
                                'Restricted' => 'Tier 1 - High Performance',
                                'Confidential' => 'Tier 1 - High Performance',
                                'Internal' => 'Tier 2 - Standard',
                                'Public' => 'Tier 3 - Archive',
                                default => 'Tier 2 - Standard'
                            };
                        ?>
                        <tr>
                            <td>
                                <span class="badge badge-<?= strtolower($sens['sensitivity_level']) ?>">
                                    <?= $sens['sensitivity_level'] ?>
                                </span>
                            </td>
                            <td><?= number_format($sens['count']) ?></td>
                            <td><?= $sizeGB ?> GB</td>
                            <td><?= $tier ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Deduplication Tab -->
            <div id="duplicates" class="tab-content">
                <h2>Duplicate File Detection & Analysis</h2>

                <div style="background: #e3f2fd; padding: 20px; border-radius: 10px; margin-bottom: 25px; border-left: 5px solid #2196F3;">
                    <h4 style="margin: 0 0 10px 0; color: #1565c0;">Deduplication Summary</h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
                        <div>
                            <strong>Duplicate Groups:</strong> <?= $stats['duplicate_groups'] ?>
                        </div>
                        <div>
                            <strong>Wasted Space:</strong> <?= $wastedSpaceGB ?> GB
                        </div>
                        <div>
                            <strong>Potential Savings:</strong> <?= round(($wastedSpaceGB / $totalStorageGB) * 100, 1) ?>%
                        </div>
                    </div>
                </div>

                <h3>Top Duplicate Groups (By Wasted Space)</h3>
                <?php if (empty($duplicateGroups)): ?>
                    <p style="text-align: center; padding: 40px; color: #666;">
                        ✓ No duplicate files found. Run a scan to detect duplicates.
                    </p>
                <?php else: ?>
                    <?php foreach ($duplicateGroups as $group):
                        $wastedGB = round($group['total_wasted_space'] / 1073741824, 2);
                        $sizeGB = round($group['file_size_bytes'] / 1073741824, 2);
                        $priority = strtolower($group['priority']);
                    ?>
                    <div class="dup-group <?= $priority ?>">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px;">
                            <div style="flex: 1;">
                                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                                    <h4 style="margin: 0;"><?= htmlspecialchars($group['group_id']) ?></h4>
                                    <span class="badge badge-<?= $priority ?>">
                                        <?= strtoupper($group['priority']) ?>
                                    </span>
                                    <span class="badge" style="background: #666;">
                                        <?= htmlspecialchars($group['file_type']) ?>
                                    </span>
                                </div>
                                <div style="font-size: 13px; color: #666;">
                                    Hash: <code><?= substr($group['file_hash'], 0, 16) ?>...</code>
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <button class="btn btn-danger" style="font-size: 13px;">Review Duplicates</button>
                            </div>
                        </div>

                        <div class="metric-row">
                            <span class="metric-label">File Size:</span>
                            <span class="metric-value"><?= $sizeGB ?> GB</span>
                        </div>

                        <div class="metric-row">
                            <span class="metric-label">Duplicate Copies:</span>
                            <span class="metric-value" style="color: #f44336;"><?= $group['duplicate_count'] ?></span>
                        </div>

                        <div class="metric-row">
                            <span class="metric-label">Wasted Space:</span>
                            <span class="metric-value" style="color: #f44336; font-size: 18px;"><?= $wastedGB ?> GB</span>
                        </div>

                        <div class="metric-row">
                            <span class="metric-label">First Occurrence:</span>
                            <span class="metric-value" style="font-size: 12px;">
                                <?= htmlspecialchars(substr($group['first_occurrence_path'], 0, 80)) ?>...
                            </span>
                        </div>

                        <div style="margin-top: 15px; padding: 15px; background: white; border-radius: 6px;">
                            <strong style="font-size: 13px;">Recommendation:</strong>
                            <p style="margin: 8px 0 0 0; font-size: 13px; color: #666;">
                                <?= htmlspecialchars($group['recommendation']) ?>
                            </p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <div style="margin-top: 30px; padding: 20px; background: #fff3e0; border-left: 5px solid #FF9800; border-radius: 8px;">
                    <h4 style="margin: 0 0 10px 0; color: #e65100;">Deduplication Best Practices</h4>
                    <ul style="margin: 0; color: #666; line-height: 1.8;">
                        <li>Keep one master copy of each file in a central location</li>
                        <li>Replace duplicate copies with symbolic links or shortcuts</li>
                        <li>Use file synchronization tools instead of creating manual copies</li>
                        <li>Implement storage deduplication at the array level for automatic optimization</li>
                        <li>Regular scans to identify and remove new duplicates</li>
                        <li>Document the master copy location for team reference</li>
                    </ul>
                </div>
            </div>

            <!-- Classification Tab -->
            <div id="classification" class="tab-content">
                <h2>Data Classification Analysis</h2>

                <div style="background: #f3e5f5; padding: 20px; border-radius: 10px; margin-bottom: 25px; border-left: 5px solid #9C27B0;">
                    <h4 style="margin: 0 0 15px 0; color: #6a1b9a;">Classification Rules Active</h4>
                    <p style="margin: 0; color: #666;">
                        Files are automatically classified based on location, type, naming patterns, and size.
                        Classification determines retention periods, storage tier recommendations, and access controls.
                    </p>
                </div>

                <div class="chart-container">
                    <canvas id="classificationBarChart"></canvas>
                </div>

                <h3 style="margin-top: 30px;">Classification Details</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Classification</th>
                            <th>Sensitivity</th>
                            <th>Files</th>
                            <th>Total Size</th>
                            <th>Recommended Tier</th>
                            <th>Avg Retention</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $classificationMap = [
                            'Financial Data' => ['Confidential', 'Tier 1', '7 years'],
                            'Human Resources' => ['Restricted', 'Tier 1', '7 years'],
                            'Customer Information' => ['Confidential', 'Tier 1', '5 years'],
                            'Database Backup' => ['Confidential', 'Tier 3', '90 days'],
                            'Source Code' => ['Internal', 'Tier 2', '3 years'],
                            'Office Document' => ['Internal', 'Tier 2', '2 years'],
                            'Log File' => ['Internal', 'Tier 3', '90 days'],
                            'Temporary' => ['Public', 'Tier 3', '7 days'],
                            'Image' => ['Public', 'Tier 3', '1 year'],
                            'Video' => ['Internal', 'Tier 3', '180 days'],
                        ];

                        foreach ($stats['by_classification'] as $class):
                            $sizeGB = round($class['total_size'] / 1073741824, 2);
                            $className = $class['data_classification'];
                            $details = $classificationMap[$className] ?? ['Internal', 'Tier 2', '1 year'];
                        ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($className) ?></strong></td>
                            <td>
                                <span class="badge badge-<?= strtolower($details[0]) ?>">
                                    <?= $details[0] ?>
                                </span>
                            </td>
                            <td><?= number_format($class['count']) ?></td>
                            <td><?= $sizeGB ?> GB</td>
                            <td><?= $details[1] ?></td>
                            <td><?= $details[2] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <h3 style="margin-top: 30px;">Classification Rules</h3>
                <div style="background: #f8f9fa; padding: 20px; border-radius: 10px;">
                    <table>
                        <thead>
                            <tr>
                                <th>Rule Name</th>
                                <th>Match Type</th>
                                <th>Pattern</th>
                                <th>Classification</th>
                                <th>Sensitivity</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Financial Documents</td>
                                <td>Path Pattern</td>
                                <td><code>%/Finance/%</code></td>
                                <td>Financial Data</td>
                                <td><span class="badge badge-confidential">Confidential</span></td>
                            </tr>
                            <tr>
                                <td>HR Records</td>
                                <td>Path Pattern</td>
                                <td><code>%/HR/%</code></td>
                                <td>Human Resources</td>
                                <td><span class="badge badge-restricted">Restricted</span></td>
                            </tr>
                            <tr>
                                <td>Database Backups</td>
                                <td>Extension</td>
                                <td><code>.bak|.sql|.dump</code></td>
                                <td>Database Backup</td>
                                <td><span class="badge badge-confidential">Confidential</span></td>
                            </tr>
                            <tr>
                                <td>Temporary Files</td>
                                <td>Extension</td>
                                <td><code>.tmp|.temp|.cache</code></td>
                                <td>Temporary</td>
                                <td><span class="badge badge-public">Public</span></td>
                            </tr>
                            <tr>
                                <td>Confidential Marker</td>
                                <td>Filename Pattern</td>
                                <td><code>%confidential%</code></td>
                                <td>Confidential Data</td>
                                <td><span class="badge badge-confidential">Confidential</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recommendations Tab -->
            <div id="recommendations" class="tab-content">
                <h2>Storage Optimization Recommendations</h2>

                <?php if (empty($recommendations)): ?>
                    <p style="text-align: center; padding: 40px; color: #666;">
                        ✓ No pending recommendations. Your storage is optimized!
                    </p>
                <?php else: ?>
                    <div style="margin-bottom: 20px;">
                        <button class="btn btn-success">Apply All High Priority</button>
                        <button class="btn" style="background: white; color: #667eea; border: 2px solid #667eea;">
                            Export Report
                        </button>
                    </div>

                    <?php foreach ($recommendations as $rec):
                        $savingsGB = round($rec['potential_savings_bytes'] / 1073741824, 2);
                        $priority = strtolower($rec['priority']);
                    ?>
                    <div class="recommendation-card <?= $priority ?>">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px;">
                            <div style="flex: 1;">
                                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                                    <h4 style="margin: 0;"><?= htmlspecialchars($rec['title']) ?></h4>
                                    <span class="badge badge-<?= $priority ?>">
                                        <?= strtoupper($rec['priority']) ?>
                                    </span>
                                    <span class="badge" style="background: #2196F3;">
                                        <?= htmlspecialchars($rec['recommendation_type']) ?>
                                    </span>
                                </div>
                                <div style="font-size: 13px; color: #666;">
                                    ID: <?= htmlspecialchars($rec['recommendation_id']) ?>
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <button class="btn btn-success" style="font-size: 13px;">Apply</button>
                                <button class="btn" style="background: #666; font-size: 13px;">Dismiss</button>
                            </div>
                        </div>

                        <p style="margin: 15px 0; color: #333; line-height: 1.6;">
                            <?= htmlspecialchars($rec['description']) ?>
                        </p>

                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-top: 15px; padding: 15px; background: white; border-radius: 6px;">
                            <div>
                                <div class="metric-label">Potential Savings:</div>
                                <div class="metric-value" style="color: #4CAF50; font-size: 20px;">
                                    <?= $savingsGB ?> GB
                                </div>
                            </div>
                            <div>
                                <div class="metric-label">Affected Files:</div>
                                <div class="metric-value" style="font-size: 20px;">
                                    <?= number_format($rec['affected_files_count']) ?>
                                </div>
                            </div>
                            <div>
                                <div class="metric-label">Annual Cost Savings:</div>
                                <div class="metric-value" style="color: #4CAF50; font-size: 20px;">
                                    $<?= round($savingsGB * 0.15 * 12, 2) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Storage Tiers Tab -->
            <div id="tiers" class="tab-content">
                <h2>Storage Tier Management</h2>

                <div style="background: #e8f5e9; padding: 20px; border-radius: 10px; margin-bottom: 25px; border-left: 5px solid #4CAF50;">
                    <h4 style="margin: 0 0 10px 0; color: #2e7d32;">Tiered Storage Strategy</h4>
                    <p style="margin: 0; color: #666;">
                        Optimize costs by matching data to appropriate storage tiers based on access frequency and business value.
                    </p>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 30px;">
                    <?php
                    $tiers = [
                        ['name' => 'Tier 1 - High Performance', 'cost' => 0.50, 'perf' => 'High', 'use' => 'Mission-critical databases, active applications'],
                        ['name' => 'Tier 2 - Standard', 'cost' => 0.15, 'perf' => 'Medium', 'use' => 'Office documents, user files'],
                        ['name' => 'Tier 3 - Archive', 'cost' => 0.05, 'perf' => 'Low', 'use' => 'Backups, old documents, compliance archives'],
                        ['name' => 'Tier 4 - Cold Storage', 'cost' => 0.01, 'perf' => 'Very Low', 'use' => 'Long-term retention, historical data']
                    ];

                    foreach ($tiers as $tier):
                    ?>
                    <div style="background: white; border: 2px solid #667eea; border-radius: 10px; padding: 20px;">
                        <h4 style="color: #667eea; margin: 0 0 15px 0;"><?= $tier['name'] ?></h4>
                        <div class="metric-row">
                            <span class="metric-label">Cost/GB/Month:</span>
                            <span class="metric-value" style="color: #4CAF50;">$<?= number_format($tier['cost'], 2) ?></span>
                        </div>
                        <div class="metric-row">
                            <span class="metric-label">Performance:</span>
                            <span class="metric-value"><?= $tier['perf'] ?></span>
                        </div>
                        <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #e0e0e0;">
                            <strong style="font-size: 13px; color: #666;">Best For:</strong>
                            <p style="margin: 8px 0 0 0; font-size: 13px; color: #333;">
                                <?= $tier['use'] ?>
                            </p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <h3>Tier Migration Opportunities</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Current Tier</th>
                            <th>Data Type</th>
                            <th>File Count</th>
                            <th>Size (GB)</th>
                            <th>Recommended Tier</th>
                            <th>Annual Savings</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Tier 1 - High Performance</td>
                            <td>Log Files</td>
                            <td>1,245</td>
                            <td>315 GB</td>
                            <td>Tier 3 - Archive</td>
                            <td style="color: #4CAF50; font-weight: bold;">$1,701</td>
                        </tr>
                        <tr>
                            <td>Tier 1 - High Performance</td>
                            <td>Backup Files</td>
                            <td>87</td>
                            <td>523 GB</td>
                            <td>Tier 3 - Archive</td>
                            <td style="color: #4CAF50; font-weight: bold;">$2,824</td>
                        </tr>
                        <tr>
                            <td>Tier 2 - Standard</td>
                            <td>Old Documents</td>
                            <td>3,421</td>
                            <td>89 GB</td>
                            <td>Tier 3 - Archive</td>
                            <td style="color: #4CAF50; font-weight: bold;">$107</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function switchTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            document.getElementById(tabName).classList.add('active');
            event.target.classList.add('active');
        }

        // Classification Pie Chart
        const classCtx = document.getElementById('classificationChart').getContext('2d');
        new Chart(classCtx, {
            type: 'pie',
            data: {
                labels: <?= json_encode(array_column($stats['by_classification'], 'data_classification')) ?>,
                datasets: [{
                    data: <?= json_encode(array_column($stats['by_classification'], 'total_size')) ?>,
                    backgroundColor: [
                        '#667eea', '#4CAF50', '#FF9800', '#2196F3', '#f44336',
                        '#9C27B0', '#00BCD4', '#FF5722', '#795548', '#607D8B'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'right' }
                }
            }
        });

        // Sensitivity Doughnut Chart
        const sensCtx = document.getElementById('sensitivityChart').getContext('2d');
        new Chart(sensCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_column($stats['by_sensitivity'], 'sensitivity_level')) ?>,
                datasets: [{
                    data: <?= json_encode(array_column($stats['by_sensitivity'], 'total_size')) ?>,
                    backgroundColor: ['#2196F3', '#FF9800', '#f44336', '#9C27B0']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'right' }
                }
            }
        });

        // Classification Bar Chart
        const classBarCtx = document.getElementById('classificationBarChart').getContext('2d');
        new Chart(classBarCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($stats['by_classification'], 'data_classification')) ?>,
                datasets: [{
                    label: 'Storage Used (bytes)',
                    data: <?= json_encode(array_column($stats['by_classification'], 'total_size')) ?>,
                    backgroundColor: '#667eea'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    title: { display: true, text: 'Storage Usage by Classification' }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: 'Storage (bytes)' }
                    }
                }
            }
        });
    </script>
</body>
</html>
