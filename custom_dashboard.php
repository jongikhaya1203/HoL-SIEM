<?php
/**
 * Custom Dashboard Builder
 * Drag-and-drop widget customization
 */

require_once 'classes/Database.php';

$db = Database::getInstance();

// Get user's saved dashboard layout (would normally be user-specific)
$savedLayout = $db->fetchOne("SELECT setting_value FROM settings WHERE setting_key = 'custom_dashboard_layout'");
$layout = $savedLayout ? json_decode($savedLayout['setting_value'], true) : [];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Custom Dashboard Builder</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/gridstack@8.4.0/dist/gridstack.min.css">
    <script src="https://cdn.jsdelivr.net/npm/gridstack@8.4.0/dist/gridstack-all.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
        }

        .header {
            background: white;
            padding: 15px 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header h1 {
            color: #667eea;
            font-size: 24px;
        }

        .header-actions {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #764ba2;
        }

        .btn-secondary {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
        }

        .btn-secondary:hover {
            background: #f0f0f0;
        }

        .widget-sidebar {
            position: fixed;
            right: -350px;
            top: 0;
            width: 350px;
            height: 100vh;
            background: white;
            box-shadow: -2px 0 10px rgba(0,0,0,0.1);
            transition: right 0.3s;
            z-index: 999;
            overflow-y: auto;
        }

        .widget-sidebar.open {
            right: 0;
        }

        .sidebar-header {
            padding: 20px;
            background: #667eea;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .sidebar-content {
            padding: 20px;
        }

        .widget-category {
            margin-bottom: 20px;
        }

        .widget-category h3 {
            margin-bottom: 10px;
            color: #333;
            font-size: 14px;
            text-transform: uppercase;
        }

        .widget-item {
            background: #f8f9fa;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 5px;
            cursor: pointer;
            border: 2px solid transparent;
            transition: all 0.3s;
        }

        .widget-item:hover {
            border-color: #667eea;
            background: white;
        }

        .widget-item strong {
            display: block;
            margin-bottom: 5px;
            color: #333;
        }

        .widget-item small {
            color: #666;
        }

        .grid-stack {
            background: #f5f5f5;
            min-height: calc(100vh - 70px);
        }

        .grid-stack-item-content {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .widget-header {
            background: #667eea;
            color: white;
            padding: 12px 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .widget-header h3 {
            font-size: 14px;
            font-weight: 600;
        }

        .widget-remove {
            background: none;
            border: none;
            color: white;
            cursor: pointer;
            font-size: 18px;
            padding: 0 5px;
        }

        .widget-body {
            padding: 15px;
        }

        .stat-value {
            font-size: 36px;
            font-weight: bold;
            color: #667eea;
        }

        .stat-label {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
        }

        table {
            width: 100%;
            font-size: 13px;
        }

        th, td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
        }

        th {
            background: #f8f9fa;
            font-weight: 600;
        }

        .empty-state {
            text-align: center;
            padding: 100px 20px;
            color: #999;
        }

        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📊 Custom Dashboard Builder</h1>
        <div class="header-actions">
            <button class="btn btn-secondary" onclick="resetDashboard()">Reset Layout</button>
            <button class="btn btn-primary" onclick="toggleWidgetSidebar()">+ Add Widget</button>
            <button class="btn btn-primary" onclick="saveDashboard()">💾 Save</button>
            <a href="index.php" class="btn btn-secondary" style="text-decoration: none; display: inline-block;">← Back</a>
        </div>
    </div>

    <div class="grid-stack" id="dashboard-grid">
        <?php if (empty($layout)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">📊</div>
            <h2>Your Custom Dashboard is Empty</h2>
            <p>Click "Add Widget" to start building your personalized dashboard</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Widget Sidebar -->
    <div class="widget-sidebar" id="widgetSidebar">
        <div class="sidebar-header">
            <h2>Available Widgets</h2>
            <button class="widget-remove" onclick="toggleWidgetSidebar()">×</button>
        </div>
        <div class="sidebar-content">
            <div class="widget-category">
                <h3>Statistics</h3>
                <div class="widget-item" onclick="addWidget('total-scans')">
                    <strong>Total Scans</strong>
                    <small>Display total scan count</small>
                </div>
                <div class="widget-item" onclick="addWidget('total-hosts')">
                    <strong>Total Hosts</strong>
                    <small>Display discovered hosts</small>
                </div>
                <div class="widget-item" onclick="addWidget('vulnerabilities')">
                    <strong>Vulnerabilities</strong>
                    <small>Show vulnerability count</small>
                </div>
                <div class="widget-item" onclick="addWidget('devices-online')">
                    <strong>Devices Online</strong>
                    <small>Active device count</small>
                </div>
            </div>

            <div class="widget-category">
                <h3>Charts</h3>
                <div class="widget-item" onclick="addWidget('cpu-chart')">
                    <strong>CPU Usage Chart</strong>
                    <small>Real-time CPU monitoring</small>
                </div>
                <div class="widget-item" onclick="addWidget('bandwidth-chart')">
                    <strong>Bandwidth Chart</strong>
                    <small>Network traffic visualization</small>
                </div>
                <div class="widget-item" onclick="addWidget('vulnerability-trend')">
                    <strong>Vulnerability Trends</strong>
                    <small>Vulnerability timeline</small>
                </div>
            </div>

            <div class="widget-category">
                <h3>Tables</h3>
                <div class="widget-item" onclick="addWidget('recent-scans')">
                    <strong>Recent Scans</strong>
                    <small>Latest scan activity</small>
                </div>
                <div class="widget-item" onclick="addWidget('top-vulnerabilities')">
                    <strong>Top Vulnerabilities</strong>
                    <small>Most critical findings</small>
                </div>
                <div class="widget-item" onclick="addWidget('device-list')">
                    <strong>Network Devices</strong>
                    <small>Monitored devices</small>
                </div>
            </div>

            <div class="widget-category">
                <h3>Modules</h3>
                <div class="widget-item" onclick="addWidget('alert-summary')">
                    <strong>Alert Summary</strong>
                    <small>Recent security alerts</small>
                </div>
                <div class="widget-item" onclick="addWidget('netflow-summary')">
                    <strong>NetFlow Summary</strong>
                    <small>Traffic flow statistics</small>
                </div>
                <div class="widget-item" onclick="addWidget('snmp-status')">
                    <strong>SNMP Status</strong>
                    <small>SNMP device monitoring</small>
                </div>
            </div>
        </div>
    </div>

    <script>
        let grid;
        const widgets = {};

        // Initialize GridStack
        document.addEventListener('DOMContentLoaded', function() {
            grid = GridStack.init({
                cellHeight: 80,
                margin: 10,
                float: true,
                animate: true
            });

            // Load saved layout
            <?php if (!empty($layout)): ?>
            const savedLayout = <?= json_encode($layout) ?>;
            savedLayout.forEach(item => {
                addWidget(item.type, item);
            });
            <?php endif; ?>
        });

        function toggleWidgetSidebar() {
            document.getElementById('widgetSidebar').classList.toggle('open');
        }

        function addWidget(type, savedConfig = null) {
            const widgetId = 'widget-' + Date.now();
            const config = savedConfig || {
                x: 0,
                y: 0,
                w: 3,
                h: 2
            };

            const widgetContent = getWidgetContent(type);

            const widget = grid.addWidget({
                x: config.x,
                y: config.y,
                w: config.w,
                h: config.h,
                content: `
                    <div class="grid-stack-item-content" data-type="${type}" data-id="${widgetId}">
                        ${widgetContent}
                    </div>
                `
            });

            widgets[widgetId] = { type, element: widget };

            // Initialize charts if widget contains canvas
            setTimeout(() => {
                const canvas = widget.querySelector('canvas');
                if (canvas) {
                    initializeChart(canvas, type);
                }
            }, 100);

            toggleWidgetSidebar();
        }

        function getWidgetContent(type) {
            const templates = {
                'total-scans': `
                    <div class="widget-header">
                        <h3>Total Scans</h3>
                        <button class="widget-remove" onclick="removeWidget(this)">×</button>
                    </div>
                    <div class="widget-body">
                        <div class="stat-value">${Math.floor(Math.random() * 500 + 100)}</div>
                        <div class="stat-label">Total Scans Completed</div>
                    </div>
                `,
                'total-hosts': `
                    <div class="widget-header">
                        <h3>Total Hosts</h3>
                        <button class="widget-remove" onclick="removeWidget(this)">×</button>
                    </div>
                    <div class="widget-body">
                        <div class="stat-value">${Math.floor(Math.random() * 200 + 50)}</div>
                        <div class="stat-label">Discovered Hosts</div>
                    </div>
                `,
                'vulnerabilities': `
                    <div class="widget-header">
                        <h3>Vulnerabilities</h3>
                        <button class="widget-remove" onclick="removeWidget(this)">×</button>
                    </div>
                    <div class="widget-body">
                        <div class="stat-value" style="color: #f44336;">${Math.floor(Math.random() * 50 + 10)}</div>
                        <div class="stat-label">Open Vulnerabilities</div>
                    </div>
                `,
                'devices-online': `
                    <div class="widget-header">
                        <h3>Devices Online</h3>
                        <button class="widget-remove" onclick="removeWidget(this)">×</button>
                    </div>
                    <div class="widget-body">
                        <div class="stat-value" style="color: #4CAF50;">${Math.floor(Math.random() * 30 + 10)}</div>
                        <div class="stat-label">Active Devices</div>
                    </div>
                `,
                'cpu-chart': `
                    <div class="widget-header">
                        <h3>CPU Usage</h3>
                        <button class="widget-remove" onclick="removeWidget(this)">×</button>
                    </div>
                    <div class="widget-body">
                        <canvas id="cpu-chart-${Date.now()}"></canvas>
                    </div>
                `,
                'bandwidth-chart': `
                    <div class="widget-header">
                        <h3>Bandwidth</h3>
                        <button class="widget-remove" onclick="removeWidget(this)">×</button>
                    </div>
                    <div class="widget-body">
                        <canvas id="bandwidth-chart-${Date.now()}"></canvas>
                    </div>
                `,
                'recent-scans': `
                    <div class="widget-header">
                        <h3>Recent Scans</h3>
                        <button class="widget-remove" onclick="removeWidget(this)">×</button>
                    </div>
                    <div class="widget-body" style="padding: 0;">
                        <table>
                            <tr><th>Scan</th><th>Status</th></tr>
                            <tr><td>192.168.1.0/24</td><td>✓ Complete</td></tr>
                            <tr><td>10.0.0.0/24</td><td>⏳ Running</td></tr>
                            <tr><td>172.16.0.0/16</td><td>✓ Complete</td></tr>
                        </table>
                    </div>
                `
            };

            return templates[type] || '<div>Widget not found</div>';
        }

        function initializeChart(canvas, type) {
            const ctx = canvas.getContext('2d');

            if (type === 'cpu-chart') {
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: ['1m', '2m', '3m', '4m', '5m'],
                        datasets: [{
                            label: 'CPU %',
                            data: [45, 52, 48, 55, 50],
                            borderColor: '#667eea',
                            backgroundColor: 'rgba(102, 126, 234, 0.1)',
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } }
                    }
                });
            } else if (type === 'bandwidth-chart') {
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: ['1m', '2m', '3m', '4m', '5m'],
                        datasets: [{
                            label: 'In',
                            data: [320, 450, 380, 420, 400],
                            borderColor: '#4CAF50'
                        }, {
                            label: 'Out',
                            data: [180, 220, 200, 230, 210],
                            borderColor: '#2196F3'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false
                    }
                });
            }
        }

        function removeWidget(button) {
            const gridItem = button.closest('.grid-stack-item');
            grid.removeWidget(gridItem);
        }

        function saveDashboard() {
            const layout = [];
            grid.engine.nodes.forEach(node => {
                const element = node.el;
                const content = element.querySelector('[data-type]');
                if (content) {
                    layout.push({
                        type: content.dataset.type,
                        x: node.x,
                        y: node.y,
                        w: node.w,
                        h: node.h
                    });
                }
            });

            // Save to server
            fetch('api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'save_dashboard_layout',
                    layout: layout
                })
            })
            .then(response => response.json())
            .then(data => {
                alert('Dashboard saved successfully!');
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to save dashboard');
            });
        }

        function resetDashboard() {
            if (confirm('Are you sure you want to reset your dashboard? This will remove all widgets.')) {
                grid.removeAll();
            }
        }
    </script>
</body>
</html>
