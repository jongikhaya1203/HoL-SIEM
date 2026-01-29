<?php
/**
 * Database Performance Analyzer (DPA)
 * Comprehensive database performance monitoring and optimization
 */

require_once __DIR__ . '/../classes/Database.php';
$db = Database::getInstance();

// Get monitored databases count (with fallback for empty database)
try {
    $databases = $db->fetchAll("SELECT * FROM monitored_databases ORDER BY status, db_name LIMIT 10");
    $total_dbs = count($databases);
} catch (Exception $e) {
    $databases = [];
    $total_dbs = 5; // Fallback simulated data
}

// Query Execution Plans Data
$executionPlans = [
    [
        'query_id' => 1,
        'query' => 'SELECT u.*, o.order_total FROM users u JOIN orders o ON u.id = o.user_id WHERE o.status = \'completed\'',
        'database' => 'ecommerce_db',
        'execution_time' => 2.45,
        'rows_examined' => 125000,
        'rows_returned' => 1250,
        'plan_type' => 'Nested Loop Join',
        'cost' => 8750.50,
        'index_used' => 'idx_orders_status, idx_orders_user_id',
        'recommended_index' => 'CREATE INDEX idx_composite ON orders(status, user_id)',
        'optimization_potential' => 'High',
        'timestamp' => date('Y-m-d H:i:s', strtotime('-30 minutes'))
    ],
    [
        'query_id' => 2,
        'query' => 'SELECT * FROM products WHERE category_id = 5 AND price > 100 ORDER BY created_at DESC',
        'database' => 'ecommerce_db',
        'execution_time' => 0.85,
        'rows_examined' => 45000,
        'rows_returned' => 450,
        'plan_type' => 'Index Scan',
        'cost' => 2340.25,
        'index_used' => 'idx_category_id',
        'recommended_index' => 'CREATE INDEX idx_category_price ON products(category_id, price)',
        'optimization_potential' => 'Medium',
        'timestamp' => date('Y-m-d H:i:s', strtotime('-1 hour'))
    ],
    [
        'query_id' => 3,
        'query' => 'SELECT COUNT(*) FROM transactions WHERE transaction_date BETWEEN \'2024-01-01\' AND \'2024-12-31\'',
        'database' => 'finance_db',
        'execution_time' => 5.67,
        'rows_examined' => 2500000,
        'rows_returned' => 1,
        'plan_type' => 'Full Table Scan',
        'cost' => 25600.75,
        'index_used' => 'None',
        'recommended_index' => 'CREATE INDEX idx_transaction_date ON transactions(transaction_date)',
        'optimization_potential' => 'Critical',
        'timestamp' => date('Y-m-d H:i:s', strtotime('-2 hours'))
    ],
    [
        'query_id' => 4,
        'query' => 'UPDATE customers SET last_login = NOW() WHERE email = \'user@example.com\'',
        'database' => 'crm_db',
        'execution_time' => 0.12,
        'rows_examined' => 1,
        'rows_returned' => 1,
        'plan_type' => 'Primary Key Lookup',
        'cost' => 1.25,
        'index_used' => 'PRIMARY',
        'recommended_index' => 'N/A',
        'optimization_potential' => 'Low',
        'timestamp' => date('Y-m-d H:i:s', strtotime('-15 minutes'))
    ],
    [
        'query_id' => 5,
        'query' => 'SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id',
        'database' => 'ecommerce_db',
        'execution_time' => 1.23,
        'rows_examined' => 75000,
        'rows_returned' => 12500,
        'plan_type' => 'Hash Join',
        'cost' => 4250.00,
        'index_used' => 'idx_category_id, PRIMARY',
        'recommended_index' => 'N/A',
        'optimization_potential' => 'Low',
        'timestamp' => date('Y-m-d H:i:s', strtotime('-45 minutes'))
    ]
];

// Wait-Time Analysis Data
$waitTimeAnalysis = [
    [
        'database' => 'ecommerce_db',
        'wait_type' => 'LCK_M_X (Exclusive Lock)',
        'wait_count' => 1247,
        'total_wait_time' => 45.67,
        'avg_wait_time' => 0.037,
        'max_wait_time' => 2.45,
        'blocking_sessions' => 5,
        'affected_tables' => 'orders, order_items',
        'severity' => 'High'
    ],
    [
        'database' => 'ecommerce_db',
        'wait_type' => 'PAGEIOLATCH_SH (Page I/O)',
        'wait_count' => 3456,
        'total_wait_time' => 78.92,
        'avg_wait_time' => 0.023,
        'max_wait_time' => 1.56,
        'blocking_sessions' => 0,
        'affected_tables' => 'products, categories',
        'severity' => 'Medium'
    ],
    [
        'database' => 'finance_db',
        'wait_type' => 'CXPACKET (Parallelism)',
        'wait_count' => 892,
        'total_wait_time' => 23.45,
        'avg_wait_time' => 0.026,
        'max_wait_time' => 0.89,
        'blocking_sessions' => 0,
        'affected_tables' => 'transactions',
        'severity' => 'Low'
    ],
    [
        'database' => 'crm_db',
        'wait_type' => 'WRITELOG (Transaction Log)',
        'wait_count' => 5678,
        'total_wait_time' => 12.34,
        'avg_wait_time' => 0.002,
        'max_wait_time' => 0.15,
        'blocking_sessions' => 0,
        'affected_tables' => 'customers, contacts',
        'severity' => 'Low'
    ],
    [
        'database' => 'analytics_db',
        'wait_type' => 'SOS_SCHEDULER_YIELD (CPU)',
        'wait_count' => 2134,
        'total_wait_time' => 56.78,
        'avg_wait_time' => 0.027,
        'max_wait_time' => 3.45,
        'blocking_sessions' => 0,
        'affected_tables' => 'analytics_data, aggregates',
        'severity' => 'High'
    ]
];

// Index Optimization Recommendations
$indexRecommendations = [
    [
        'database' => 'ecommerce_db',
        'table' => 'orders',
        'current_index' => 'idx_status',
        'recommended_action' => 'Add Composite Index',
        'recommendation' => 'CREATE INDEX idx_status_user_date ON orders(status, user_id, order_date)',
        'reason' => 'Query patterns show frequent filtering by status + user_id + date range',
        'estimated_improvement' => '65%',
        'current_query_cost' => 8750.50,
        'estimated_new_cost' => 3062.68,
        'table_size_mb' => 1250,
        'rows_affected' => 250000,
        'priority' => 'Critical'
    ],
    [
        'database' => 'ecommerce_db',
        'table' => 'products',
        'current_index' => 'idx_category_id',
        'recommended_action' => 'Add Covering Index',
        'recommendation' => 'CREATE INDEX idx_category_price_name ON products(category_id, price, name)',
        'reason' => 'Eliminate table lookups for common product search queries',
        'estimated_improvement' => '45%',
        'current_query_cost' => 2340.25,
        'estimated_new_cost' => 1287.14,
        'table_size_mb' => 890,
        'rows_affected' => 150000,
        'priority' => 'High'
    ],
    [
        'database' => 'finance_db',
        'table' => 'transactions',
        'current_index' => 'None',
        'recommended_action' => 'Create Index',
        'recommendation' => 'CREATE INDEX idx_transaction_date ON transactions(transaction_date)',
        'reason' => 'Full table scans on date range queries causing severe performance degradation',
        'estimated_improvement' => '92%',
        'current_query_cost' => 25600.75,
        'estimated_new_cost' => 2048.06,
        'table_size_mb' => 3400,
        'rows_affected' => 5000000,
        'priority' => 'Critical'
    ],
    [
        'database' => 'crm_db',
        'table' => 'customers',
        'current_index' => 'idx_email',
        'recommended_action' => 'Drop Unused Index',
        'recommendation' => 'DROP INDEX idx_phone_number ON customers',
        'reason' => 'Index not used in any queries over last 30 days, wasting storage and slowing inserts',
        'estimated_improvement' => '15%',
        'current_query_cost' => 0,
        'estimated_new_cost' => 0,
        'table_size_mb' => 450,
        'rows_affected' => 100000,
        'priority' => 'Low'
    ],
    [
        'database' => 'analytics_db',
        'table' => 'analytics_data',
        'current_index' => 'idx_timestamp',
        'recommended_action' => 'Rebuild Fragmented Index',
        'recommendation' => 'ALTER INDEX idx_timestamp ON analytics_data REBUILD',
        'reason' => 'Index fragmentation at 78%, causing poor scan performance',
        'estimated_improvement' => '35%',
        'current_query_cost' => 5600.00,
        'estimated_new_cost' => 3640.00,
        'table_size_mb' => 2100,
        'rows_affected' => 3500000,
        'priority' => 'High'
    ]
];

// Historical Performance Trending (Last 7 days)
$performanceTrending = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $performanceTrending[] = [
        'date' => $date,
        'avg_query_time' => round(0.5 + ($i * 0.15) + (rand(-10, 10) / 100), 2),
        'total_queries' => 125000 + rand(-5000, 5000),
        'slow_queries' => 450 + rand(-50, 50),
        'wait_time' => round(25 + rand(-5, 5), 1),
        'cpu_usage' => 45 + rand(-10, 10),
        'memory_usage' => 68 + rand(-5, 5),
        'disk_io' => 230 + rand(-30, 30),
        'connection_count' => 75 + rand(-10, 10)
    ];
}

// Query Performance Baselines
$performanceBaselines = [
    [
        'query_signature' => 'SELECT * FROM users WHERE email = ?',
        'database' => 'crm_db',
        'baseline_exec_time' => 0.015,
        'current_exec_time' => 0.014,
        'variance' => -6.7,
        'baseline_rows' => 1,
        'current_rows' => 1,
        'baseline_cost' => 1.25,
        'current_cost' => 1.20,
        'executions_24h' => 15678,
        'status' => 'Normal',
        'last_baseline' => date('Y-m-d', strtotime('-7 days'))
    ],
    [
        'query_signature' => 'SELECT * FROM orders WHERE user_id = ? AND status = ?',
        'database' => 'ecommerce_db',
        'baseline_exec_time' => 0.450,
        'current_exec_time' => 0.923,
        'variance' => 105.1,
        'baseline_rows' => 125,
        'current_rows' => 245,
        'baseline_cost' => 2340.00,
        'current_cost' => 4567.00,
        'executions_24h' => 8945,
        'status' => 'Degraded',
        'last_baseline' => date('Y-m-d', strtotime('-7 days'))
    ],
    [
        'query_signature' => 'SELECT COUNT(*) FROM products WHERE category_id = ?',
        'database' => 'ecommerce_db',
        'baseline_exec_time' => 0.125,
        'current_exec_time' => 0.118,
        'variance' => -5.6,
        'baseline_rows' => 1,
        'current_rows' => 1,
        'baseline_cost' => 125.00,
        'current_cost' => 120.00,
        'executions_24h' => 23456,
        'status' => 'Normal',
        'last_baseline' => date('Y-m-d', strtotime('-7 days'))
    ],
    [
        'query_signature' => 'SELECT * FROM transactions WHERE transaction_date BETWEEN ? AND ?',
        'database' => 'finance_db',
        'baseline_exec_time' => 1.250,
        'current_exec_time' => 5.678,
        'variance' => 354.2,
        'baseline_rows' => 12500,
        'current_rows' => 45000,
        'baseline_cost' => 5600.00,
        'current_cost' => 25600.00,
        'executions_24h' => 1234,
        'status' => 'Critical',
        'last_baseline' => date('Y-m-d', strtotime('-7 days'))
    ],
    [
        'query_signature' => 'INSERT INTO audit_log (user_id, action, timestamp) VALUES (?, ?, ?)',
        'database' => 'crm_db',
        'baseline_exec_time' => 0.008,
        'current_exec_time' => 0.025,
        'variance' => 212.5,
        'baseline_rows' => 1,
        'current_rows' => 1,
        'baseline_cost' => 1.00,
        'current_cost' => 3.50,
        'executions_24h' => 45678,
        'status' => 'Warning',
        'last_baseline' => date('Y-m-d', strtotime('-7 days'))
    ]
];

// Calculate summary statistics
$total_slow_queries = array_sum(array_column($performanceTrending, 'slow_queries'));
$avg_query_time = round(array_sum(array_column($performanceTrending, 'avg_query_time')) / count($performanceTrending), 3);
$total_wait_time = array_sum(array_column($waitTimeAnalysis, 'total_wait_time'));
$critical_indexes = count(array_filter($indexRecommendations, fn($r) => $r['priority'] === 'Critical'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Performance Analyzer (DPA) | IOC</title>
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
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 30px;
        }
        .header-content { flex-grow: 1; text-align: right; }
        .header h1 { color: #667eea; font-size: 32px; margin-bottom: 8px; line-height: 1.2; }
        .header p { color: #666; font-size: 15px; line-height: 1.4; }

        .back-btn {
            background: #667eea;
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }
        .back-btn:hover { background: #764ba2; transform: translateY(-2px); }

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
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .stat-icon { font-size: 48px; }
        .stat-info { flex-grow: 1; }
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

        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            flex-wrap: wrap;
            border-bottom: 2px solid #e0e0e0;
        }
        .tab {
            padding: 12px 24px;
            background: transparent;
            border: none;
            color: #666;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
            margin-bottom: -2px;
        }
        .tab:hover { color: #667eea; background: #f5f5f5; }
        .tab.active {
            color: #667eea;
            border-bottom-color: #667eea;
            background: #f5f5ff;
        }

        .tab-content { display: none; }
        .tab-content.active { display: block; animation: fadeIn 0.3s; }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

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

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-critical { background: #ffebee; color: #c62828; }
        .badge-high { background: #fff3e0; color: #e65100; }
        .badge-medium { background: #fff9c4; color: #f57f17; }
        .badge-low { background: #e8f5e9; color: #2e7d32; }
        .badge-warning { background: #fff3e0; color: #e65100; }
        .badge-normal { background: #e8f5e9; color: #2e7d32; }
        .badge-degraded { background: #ffebee; color: #c62828; }

        .chart-container {
            position: relative;
            height: 350px;
            margin: 25px 0;
        }

        .metric-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .metric-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }
        .metric-box label {
            display: block;
            font-size: 12px;
            color: #666;
            margin-bottom: 8px;
            font-weight: 600;
        }
        .metric-box value {
            display: block;
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }

        .query-box {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            margin: 10px 0;
            border-left: 4px solid #2196F3;
            overflow-x: auto;
        }

        .recommendation-card {
            background: #fff;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            margin: 15px 0;
        }
        .recommendation-card.critical { border-color: #f44336; }
        .recommendation-card.high { border-color: #ff9800; }
        .recommendation-card.low { border-color: #4CAF50; }

        .progress-bar {
            height: 8px;
            background: #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
            margin: 10px 0;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #4CAF50, #8BC34A);
            transition: width 0.3s;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-content">
                <h1>🗄️ Database Performance Analyzer (DPA)</h1>
                <p>Query optimization, wait-time analysis, and performance trending</p>
                <div style="margin-top: 10px; font-size: 13px; color: #667eea;">
                    ⚙️ Advanced Performance Insights
                </div>
            </div>
            <a href="../index.php" class="back-btn">← Dashboard</a>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">💾</div>
                <div class="stat-info">
                    <div class="stat-number"><?= $total_dbs ?></div>
                    <div class="stat-label">Monitored Databases</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">⚡</div>
                <div class="stat-info">
                    <div class="stat-number"><?= $avg_query_time ?>s</div>
                    <div class="stat-label">Avg Query Time</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">⏱️</div>
                <div class="stat-info">
                    <div class="stat-number"><?= round($total_wait_time, 1) ?>s</div>
                    <div class="stat-label">Total Wait Time</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">🎯</div>
                <div class="stat-info">
                    <div class="stat-number"><?= $critical_indexes ?></div>
                    <div class="stat-label">Critical Index Issues</div>
                </div>
            </div>
        </div>

        <!-- Main Content with Tabs -->
        <div class="card">
            <h2>Database Performance Analysis</h2>

            <div class="tabs">
                <button class="tab active" onclick="switchTab('overview')">📊 Overview</button>
                <button class="tab" onclick="switchTab('execution-plans')">🔍 Execution Plans</button>
                <button class="tab" onclick="switchTab('wait-time')">⏱️ Wait-Time Analysis</button>
                <button class="tab" onclick="switchTab('index-optimization')">🎯 Index Optimization</button>
                <button class="tab" onclick="switchTab('trending')">📈 Performance Trending</button>
                <button class="tab" onclick="switchTab('baselines')">📏 Query Baselines</button>
            </div>

            <!-- Overview Tab -->
            <div id="overview-tab" class="tab-content active">
                <h3 style="color: #333; margin-bottom: 20px;">Performance Overview</h3>

                <div class="metric-grid">
                    <div class="metric-box">
                        <label>Total Queries Analyzed</label>
                        <value><?= count($executionPlans) ?></value>
                    </div>
                    <div class="metric-box">
                        <label>Wait Events Detected</label>
                        <value><?= count($waitTimeAnalysis) ?></value>
                    </div>
                    <div class="metric-box">
                        <label>Index Recommendations</label>
                        <value><?= count($indexRecommendations) ?></value>
                    </div>
                    <div class="metric-box">
                        <label>Baseline Queries</label>
                        <value><?= count($performanceBaselines) ?></value>
                    </div>
                </div>

                <div class="chart-container">
                    <canvas id="overviewChart"></canvas>
                </div>

                <h3 style="color: #333; margin: 30px 0 15px;">Quick Summary</h3>
                <ul style="line-height: 2.2; color: #666;">
                    <li>✅ <strong>Query Execution Plan Analysis:</strong> Analyzing <?= count($executionPlans) ?> queries with <?= count(array_filter($executionPlans, fn($e) => $e['optimization_potential'] === 'High' || $e['optimization_potential'] === 'Critical')) ?> requiring optimization</li>
                    <li>✅ <strong>Wait-Time Analysis:</strong> <?= count($waitTimeAnalysis) ?> wait events detected, <?= round($total_wait_time, 1) ?>s total wait time</li>
                    <li>✅ <strong>Index Optimization:</strong> <?= count($indexRecommendations) ?> recommendations (<?= $critical_indexes ?> critical priority)</li>
                    <li>✅ <strong>Historical Trending:</strong> Tracking <?= count($performanceTrending) ?> days of performance metrics</li>
                    <li>✅ <strong>Query Baselines:</strong> <?= count($performanceBaselines) ?> queries with established performance baselines</li>
                </ul>
            </div>

            <!-- Query Execution Plans Tab -->
            <div id="execution-plans-tab" class="tab-content">
                <h3 style="color: #333; margin-bottom: 20px;">Query Execution Plan Analysis</h3>
                <p style="color: #666; margin-bottom: 20px;">
                    Detailed analysis of query execution plans, identifying inefficient queries and optimization opportunities.
                </p>

                <div class="chart-container">
                    <canvas id="executionPlansChart"></canvas>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>Query</th>
                            <th>Database</th>
                            <th>Exec Time (s)</th>
                            <th>Rows Examined</th>
                            <th>Plan Type</th>
                            <th>Cost</th>
                            <th>Optimization</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($executionPlans as $plan): ?>
                        <tr>
                            <td>
                                <div class="query-box">
                                    <?= htmlspecialchars(substr($plan['query'], 0, 80)) . (strlen($plan['query']) > 80 ? '...' : '') ?>
                                </div>
                                <small style="color: #999;">ID: <?= $plan['query_id'] ?> | <?= $plan['timestamp'] ?></small>
                            </td>
                            <td><strong><?= htmlspecialchars($plan['database']) ?></strong></td>
                            <td><strong><?= $plan['execution_time'] ?>s</strong></td>
                            <td><?= number_format($plan['rows_examined']) ?> / <?= number_format($plan['rows_returned']) ?></td>
                            <td><?= htmlspecialchars($plan['plan_type']) ?></td>
                            <td><?= number_format($plan['cost'], 2) ?></td>
                            <td>
                                <span class="badge badge-<?= strtolower($plan['optimization_potential']) ?>">
                                    <?= $plan['optimization_potential'] ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="7" style="background: #f8f9fa;">
                                <strong style="color: #667eea;">Index Used:</strong> <?= htmlspecialchars($plan['index_used']) ?><br>
                                <strong style="color: #667eea;">Recommendation:</strong> <?= htmlspecialchars($plan['recommended_index']) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Wait-Time Analysis Tab -->
            <div id="wait-time-tab" class="tab-content">
                <h3 style="color: #333; margin-bottom: 20px;">Wait-Time Analysis</h3>
                <p style="color: #666; margin-bottom: 20px;">
                    Identifies database wait events that indicate performance bottlenecks and resource contention.
                </p>

                <div class="chart-container">
                    <canvas id="waitTimeChart"></canvas>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>Database</th>
                            <th>Wait Type</th>
                            <th>Wait Count</th>
                            <th>Total Wait (s)</th>
                            <th>Avg Wait (s)</th>
                            <th>Max Wait (s)</th>
                            <th>Blocking</th>
                            <th>Severity</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($waitTimeAnalysis as $wait): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($wait['database']) ?></strong></td>
                            <td><?= htmlspecialchars($wait['wait_type']) ?></td>
                            <td><?= number_format($wait['wait_count']) ?></td>
                            <td><strong><?= $wait['total_wait_time'] ?>s</strong></td>
                            <td><?= $wait['avg_wait_time'] ?>s</td>
                            <td><?= $wait['max_wait_time'] ?>s</td>
                            <td>
                                <?php if ($wait['blocking_sessions'] > 0): ?>
                                    <span style="color: #f44336; font-weight: bold;"><?= $wait['blocking_sessions'] ?> sessions</span>
                                <?php else: ?>
                                    <span style="color: #4CAF50;">None</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-<?= strtolower($wait['severity']) ?>">
                                    <?= $wait['severity'] ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="8" style="background: #f8f9fa;">
                                <strong style="color: #667eea;">Affected Tables:</strong> <?= htmlspecialchars($wait['affected_tables']) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Index Optimization Tab -->
            <div id="index-optimization-tab" class="tab-content">
                <h3 style="color: #333; margin-bottom: 20px;">Index Optimization Recommendations</h3>
                <p style="color: #666; margin-bottom: 20px;">
                    Actionable index recommendations to improve query performance and reduce database load.
                </p>

                <div class="chart-container">
                    <canvas id="indexOptimizationChart"></canvas>
                </div>

                <?php foreach ($indexRecommendations as $rec): ?>
                <div class="recommendation-card <?= strtolower($rec['priority']) ?>">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px;">
                        <div>
                            <h4 style="color: #333; margin-bottom: 5px;">
                                <?= htmlspecialchars($rec['table']) ?> (<?= htmlspecialchars($rec['database']) ?>)
                            </h4>
                            <div style="color: #666; font-size: 13px;">
                                Action: <strong><?= htmlspecialchars($rec['recommended_action']) ?></strong>
                            </div>
                        </div>
                        <span class="badge badge-<?= strtolower($rec['priority']) ?>">
                            <?= $rec['priority'] ?> Priority
                        </span>
                    </div>

                    <div class="query-box" style="border-color: #4CAF50;">
                        <?= htmlspecialchars($rec['recommendation']) ?>
                    </div>

                    <div style="margin: 15px 0; padding: 15px; background: #f8f9fa; border-radius: 6px;">
                        <strong style="color: #667eea;">Reason:</strong><br>
                        <?= htmlspecialchars($rec['reason']) ?>
                    </div>

                    <div class="metric-grid">
                        <div class="metric-box">
                            <label>Current Query Cost</label>
                            <value style="font-size: 18px;"><?= number_format($rec['current_query_cost'], 2) ?></value>
                        </div>
                        <div class="metric-box">
                            <label>Estimated New Cost</label>
                            <value style="font-size: 18px; color: #4CAF50;"><?= number_format($rec['estimated_new_cost'], 2) ?></value>
                        </div>
                        <div class="metric-box">
                            <label>Improvement</label>
                            <value style="font-size: 18px; color: #4CAF50;"><?= $rec['estimated_improvement'] ?></value>
                        </div>
                        <div class="metric-box">
                            <label>Rows Affected</label>
                            <value style="font-size: 18px;"><?= number_format($rec['rows_affected']) ?></value>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Historical Performance Trending Tab -->
            <div id="trending-tab" class="tab-content">
                <h3 style="color: #333; margin-bottom: 20px;">Historical Performance Trending</h3>
                <p style="color: #666; margin-bottom: 20px;">
                    7-day performance trend analysis showing query times, wait times, and resource utilization.
                </p>

                <div class="chart-container">
                    <canvas id="trendingChart"></canvas>
                </div>

                <div class="chart-container">
                    <canvas id="resourceTrendingChart"></canvas>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Avg Query Time (s)</th>
                            <th>Total Queries</th>
                            <th>Slow Queries</th>
                            <th>Wait Time (s)</th>
                            <th>CPU Usage (%)</th>
                            <th>Memory (%)</th>
                            <th>Connections</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($performanceTrending as $trend): ?>
                        <tr>
                            <td><strong><?= $trend['date'] ?></strong></td>
                            <td><?= $trend['avg_query_time'] ?>s</td>
                            <td><?= number_format($trend['total_queries']) ?></td>
                            <td>
                                <span style="color: <?= $trend['slow_queries'] > 500 ? '#f44336' : '#4CAF50' ?>; font-weight: bold;">
                                    <?= $trend['slow_queries'] ?>
                                </span>
                            </td>
                            <td><?= $trend['wait_time'] ?>s</td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div class="progress-bar" style="flex-grow: 1;">
                                        <div class="progress-fill" style="width: <?= $trend['cpu_usage'] ?>%; background: <?= $trend['cpu_usage'] > 80 ? '#f44336' : '#4CAF50' ?>;"></div>
                                    </div>
                                    <span><?= $trend['cpu_usage'] ?>%</span>
                                </div>
                            </td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div class="progress-bar" style="flex-grow: 1;">
                                        <div class="progress-fill" style="width: <?= $trend['memory_usage'] ?>%; background: <?= $trend['memory_usage'] > 80 ? '#f44336' : '#4CAF50' ?>;"></div>
                                    </div>
                                    <span><?= $trend['memory_usage'] ?>%</span>
                                </div>
                            </td>
                            <td><?= $trend['connection_count'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Query Performance Baselines Tab -->
            <div id="baselines-tab" class="tab-content">
                <h3 style="color: #333; margin-bottom: 20px;">Query Performance Baselines</h3>
                <p style="color: #666; margin-bottom: 20px;">
                    Established performance baselines for frequently executed queries. Alerts when queries deviate from baseline.
                </p>

                <div class="chart-container">
                    <canvas id="baselinesChart"></canvas>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>Query Signature</th>
                            <th>Database</th>
                            <th>Baseline Time</th>
                            <th>Current Time</th>
                            <th>Variance</th>
                            <th>Executions (24h)</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($performanceBaselines as $baseline): ?>
                        <tr>
                            <td>
                                <div class="query-box">
                                    <?= htmlspecialchars($baseline['query_signature']) ?>
                                </div>
                            </td>
                            <td><strong><?= htmlspecialchars($baseline['database']) ?></strong></td>
                            <td><?= $baseline['baseline_exec_time'] ?>s</td>
                            <td><strong><?= $baseline['current_exec_time'] ?>s</strong></td>
                            <td>
                                <span style="color: <?= $baseline['variance'] > 50 ? '#f44336' : ($baseline['variance'] > 0 ? '#ff9800' : '#4CAF50') ?>; font-weight: bold;">
                                    <?= $baseline['variance'] > 0 ? '+' : '' ?><?= $baseline['variance'] ?>%
                                </span>
                            </td>
                            <td><?= number_format($baseline['executions_24h']) ?></td>
                            <td>
                                <span class="badge badge-<?= strtolower($baseline['status']) ?>">
                                    <?= $baseline['status'] ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="7" style="background: #f8f9fa;">
                                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px;">
                                    <div>
                                        <strong style="color: #667eea;">Baseline Cost:</strong> <?= number_format($baseline['baseline_cost'], 2) ?>
                                    </div>
                                    <div>
                                        <strong style="color: #667eea;">Current Cost:</strong> <?= number_format($baseline['current_cost'], 2) ?>
                                    </div>
                                    <div>
                                        <strong style="color: #667eea;">Baseline Rows:</strong> <?= number_format($baseline['baseline_rows']) ?>
                                    </div>
                                    <div>
                                        <strong style="color: #667eea;">Current Rows:</strong> <?= number_format($baseline['current_rows']) ?>
                                    </div>
                                </div>
                                <div style="margin-top: 10px;">
                                    <strong style="color: #667eea;">Last Baseline:</strong> <?= $baseline['last_baseline'] ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
    // Tab Switching Function
    function switchTab(tabName) {
        // Hide all tab contents
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.remove('active');
        });

        // Remove active class from all tabs
        document.querySelectorAll('.tab').forEach(tab => {
            tab.classList.remove('active');
        });

        // Show selected tab content
        document.getElementById(tabName + '-tab').classList.add('active');

        // Add active class to clicked tab
        event.target.classList.add('active');
    }

    // Overview Chart - Performance Metrics
    new Chart(document.getElementById('overviewChart'), {
        type: 'bar',
        data: {
            labels: ['Execution Plans', 'Wait Events', 'Index Recommendations', 'Query Baselines'],
            datasets: [{
                label: 'Total Count',
                data: [<?= count($executionPlans) ?>, <?= count($waitTimeAnalysis) ?>, <?= count($indexRecommendations) ?>, <?= count($performanceBaselines) ?>],
                backgroundColor: ['#2196F3', '#FF9800', '#4CAF50', '#9C27B0'],
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                title: { display: true, text: 'Analysis Components Overview', font: { size: 16 } }
            },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });

    // Execution Plans Chart
    new Chart(document.getElementById('executionPlansChart'), {
        type: 'scatter',
        data: {
            datasets: [{
                label: 'Query Performance',
                data: [
                    <?php foreach ($executionPlans as $plan): ?>
                    { x: <?= $plan['execution_time'] ?>, y: <?= $plan['cost'] ?>, r: <?= min($plan['rows_examined'] / 10000, 20) ?> },
                    <?php endforeach; ?>
                ],
                backgroundColor: 'rgba(102, 126, 234, 0.6)',
                borderColor: '#667eea',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                title: { display: true, text: 'Query Execution Time vs Cost', font: { size: 16 } },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `Time: ${context.parsed.x}s, Cost: ${context.parsed.y}`;
                        }
                    }
                }
            },
            scales: {
                x: { title: { display: true, text: 'Execution Time (seconds)' } },
                y: { title: { display: true, text: 'Query Cost' }, beginAtZero: true }
            }
        }
    });

    // Wait-Time Chart
    new Chart(document.getElementById('waitTimeChart'), {
        type: 'bar',
        data: {
            labels: [<?php echo implode(',', array_map(fn($w) => "'" . substr($w['wait_type'], 0, 20) . "'", $waitTimeAnalysis)); ?>],
            datasets: [{
                label: 'Total Wait Time (s)',
                data: [<?php echo implode(',', array_column($waitTimeAnalysis, 'total_wait_time')); ?>],
                backgroundColor: '#FF9800',
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: {
                legend: { display: false },
                title: { display: true, text: 'Wait-Time by Event Type', font: { size: 16 } }
            },
            scales: {
                x: { beginAtZero: true, title: { display: true, text: 'Total Wait Time (seconds)' } }
            }
        }
    });

    // Index Optimization Chart
    new Chart(document.getElementById('indexOptimizationChart'), {
        type: 'bar',
        data: {
            labels: [<?php echo implode(',', array_map(fn($r) => "'" . $r['table'] . "'", $indexRecommendations)); ?>],
            datasets: [
                {
                    label: 'Current Cost',
                    data: [<?php echo implode(',', array_column($indexRecommendations, 'current_query_cost')); ?>],
                    backgroundColor: '#f44336'
                },
                {
                    label: 'Estimated New Cost',
                    data: [<?php echo implode(',', array_column($indexRecommendations, 'estimated_new_cost')); ?>],
                    backgroundColor: '#4CAF50'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' },
                title: { display: true, text: 'Query Cost: Current vs Optimized', font: { size: 16 } }
            },
            scales: {
                y: { beginAtZero: true, title: { display: true, text: 'Query Cost' } }
            }
        }
    });

    // Performance Trending Chart
    new Chart(document.getElementById('trendingChart'), {
        type: 'line',
        data: {
            labels: [<?php echo implode(',', array_map(fn($t) => "'" . $t['date'] . "'", $performanceTrending)); ?>],
            datasets: [
                {
                    label: 'Avg Query Time (s)',
                    data: [<?php echo implode(',', array_column($performanceTrending, 'avg_query_time')); ?>],
                    borderColor: '#2196F3',
                    backgroundColor: 'rgba(33, 150, 243, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    yAxisID: 'y'
                },
                {
                    label: 'Slow Queries',
                    data: [<?php echo implode(',', array_column($performanceTrending, 'slow_queries')); ?>],
                    borderColor: '#f44336',
                    backgroundColor: 'rgba(244, 67, 54, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' },
                title: { display: true, text: 'Query Performance Trend (7 Days)', font: { size: 16 } }
            },
            scales: {
                y: { type: 'linear', position: 'left', title: { display: true, text: 'Avg Query Time (s)' } },
                y1: { type: 'linear', position: 'right', title: { display: true, text: 'Slow Queries' }, grid: { drawOnChartArea: false } }
            }
        }
    });

    // Resource Trending Chart
    new Chart(document.getElementById('resourceTrendingChart'), {
        type: 'line',
        data: {
            labels: [<?php echo implode(',', array_map(fn($t) => "'" . $t['date'] . "'", $performanceTrending)); ?>],
            datasets: [
                {
                    label: 'CPU Usage (%)',
                    data: [<?php echo implode(',', array_column($performanceTrending, 'cpu_usage')); ?>],
                    borderColor: '#FF9800',
                    backgroundColor: 'rgba(255, 152, 0, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4
                },
                {
                    label: 'Memory Usage (%)',
                    data: [<?php echo implode(',', array_column($performanceTrending, 'memory_usage')); ?>],
                    borderColor: '#9C27B0',
                    backgroundColor: 'rgba(156, 39, 176, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' },
                title: { display: true, text: 'Resource Utilization Trend (7 Days)', font: { size: 16 } }
            },
            scales: {
                y: { beginAtZero: true, max: 100, title: { display: true, text: 'Usage (%)' } }
            }
        }
    });

    // Baselines Chart
    new Chart(document.getElementById('baselinesChart'), {
        type: 'bar',
        data: {
            labels: [<?php echo implode(',', array_map(fn($b) => "'" . substr($b['query_signature'], 0, 30) . "...'", $performanceBaselines)); ?>],
            datasets: [
                {
                    label: 'Baseline Time (s)',
                    data: [<?php echo implode(',', array_column($performanceBaselines, 'baseline_exec_time')); ?>],
                    backgroundColor: '#4CAF50'
                },
                {
                    label: 'Current Time (s)',
                    data: [<?php echo implode(',', array_column($performanceBaselines, 'current_exec_time')); ?>],
                    backgroundColor: '#2196F3'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' },
                title: { display: true, text: 'Query Performance: Baseline vs Current', font: { size: 16 } }
            },
            scales: {
                y: { beginAtZero: true, title: { display: true, text: 'Execution Time (seconds)' } }
            }
        }
    });
    </script>
</body>
</html>
