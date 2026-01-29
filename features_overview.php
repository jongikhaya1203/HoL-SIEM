<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Features Overview - Network Scanner</title>
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
            max-width: 1200px;
            margin: 0 auto;
        }

        h1 {
            color: white;
            text-align: center;
            margin-bottom: 20px;
            font-size: 42px;
        }

        .subtitle {
            color: rgba(255,255,255,0.9);
            text-align: center;
            margin-bottom: 40px;
            font-size: 18px;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .feature-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            transition: all 0.3s;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.3);
        }

        .feature-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }

        .feature-title {
            color: #667eea;
            font-size: 22px;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .feature-description {
            color: #666;
            line-height: 1.6;
            margin-bottom: 15px;
        }

        .feature-status {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .status-implemented {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .status-partial {
            background: #fff3e0;
            color: #f57c00;
        }

        .feature-capabilities {
            list-style: none;
            margin-bottom: 20px;
        }

        .feature-capabilities li {
            padding: 5px 0;
            color: #555;
            font-size: 14px;
        }

        .feature-capabilities li:before {
            content: "✓ ";
            color: #4CAF50;
            font-weight: bold;
            margin-right: 8px;
        }

        .feature-link {
            display: inline-block;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
            transition: background 0.3s;
        }

        .feature-link:hover {
            background: #764ba2;
        }

        .back-home {
            text-align: center;
            margin-top: 30px;
        }

        .back-home a {
            display: inline-block;
            padding: 15px 30px;
            background: white;
            color: #667eea;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 18px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .back-home a:hover {
            transform: translateY(-2px);
            box-shadow: 0 7px 20px rgba(0,0,0,0.3);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 New Features Overview</h1>
        <p class="subtitle">Enterprise-grade network monitoring and management capabilities</p>

        <div class="features-grid">
            <!-- Network Topology Maps -->
            <div class="feature-card">
                <div class="feature-icon">🗺️</div>
                <h2 class="feature-title">Network Topology Maps</h2>
                <span class="feature-status status-implemented">✓ Fully Implemented</span>
                <p class="feature-description">Auto-generated, interactive network visualization</p>
                <ul class="feature-capabilities">
                    <li>Interactive drag-and-drop topology</li>
                    <li>Multiple layout options (hierarchical, force, circular)</li>
                    <li>Real-time device status indicators</li>
                    <li>Device details on click</li>
                    <li>Export topology as image</li>
                    <li>Auto-discovery connections</li>
                </ul>
                <a href="modules/network_topology.php" class="feature-link">Explore Topology →</a>
            </div>

            <!-- Performance Metrics -->
            <div class="feature-card">
                <div class="feature-icon">⚡</div>
                <h2 class="feature-title">Performance Metrics</h2>
                <span class="feature-status status-implemented">✓ Fully Implemented</span>
                <p class="feature-description">Comprehensive performance monitoring dashboard</p>
                <ul class="feature-capabilities">
                    <li>CPU, Memory, Disk usage tracking</li>
                    <li>Bandwidth utilization monitoring</li>
                    <li>Network latency measurements</li>
                    <li>Performance baselines</li>
                    <li>Historical trend charts</li>
                    <li>Health score calculations</li>
                </ul>
                <a href="modules/performance_metrics.php" class="feature-link">View Metrics →</a>
            </div>

            <!-- NetFlow/sFlow Analysis -->
            <div class="feature-card">
                <div class="feature-icon">📊</div>
                <h2 class="feature-title">NetFlow/sFlow Analyzer</h2>
                <span class="feature-status status-implemented">✓ Fully Implemented</span>
                <p class="feature-description">Full traffic flow analysis and monitoring</p>
                <ul class="feature-capabilities">
                    <li>Traffic flow collection & analysis</li>
                    <li>Top talkers identification</li>
                    <li>Protocol distribution charts</li>
                    <li>Port usage statistics</li>
                    <li>Traffic patterns over time</li>
                    <li>Export flow data to CSV</li>
                </ul>
                <a href="modules/netflow_analyzer.php" class="feature-link">Analyze Traffic →</a>
            </div>

            <!-- SNMP Monitoring -->
            <div class="feature-card">
                <div class="feature-icon">🔍</div>
                <h2 class="feature-title">SNMP Monitoring</h2>
                <span class="feature-status status-implemented">✓ Fully Implemented</span>
                <p class="feature-description">SNMPv1, v2c, v3 with trap receiver</p>
                <ul class="feature-capabilities">
                    <li>Support for SNMPv1, v2c, and v3</li>
                    <li>SNMP trap receiver (UDP 162)</li>
                    <li>OID explorer & browser</li>
                    <li>Device metrics collection</li>
                    <li>SNMPv3 authentication & encryption</li>
                    <li>Real-time device monitoring</li>
                </ul>
                <a href="modules/snmp_monitor.php" class="feature-link">SNMP Monitor →</a>
            </div>

            <!-- Alert System -->
            <div class="feature-card">
                <div class="feature-icon">🔔</div>
                <h2 class="feature-title">Enhanced Alerting</h2>
                <span class="feature-status status-implemented">✓ Fully Implemented</span>
                <p class="feature-description">Multi-channel notification system</p>
                <ul class="feature-capabilities">
                    <li>Email notifications</li>
                    <li>SMS alerts (Twilio integration ready)</li>
                    <li>Webhook support (Slack, Teams, custom)</li>
                    <li>Push notifications (FCM ready)</li>
                    <li>Configurable alert thresholds</li>
                    <li>Alert history & logging</li>
                </ul>
                <a href="admin/settings.php#alerts-tab" class="feature-link">Configure Alerts →</a>
            </div>

            <!-- Custom Dashboards -->
            <div class="feature-card">
                <div class="feature-icon">📐</div>
                <h2 class="feature-title">Custom Dashboards</h2>
                <span class="feature-status status-implemented">✓ Fully Implemented</span>
                <p class="feature-description">Drag-and-drop dashboard builder</p>
                <ul class="feature-capabilities">
                    <li>Drag-and-drop widget placement</li>
                    <li>Resizable widgets</li>
                    <li>12+ widget types available</li>
                    <li>Save custom layouts</li>
                    <li>Real-time data updates</li>
                    <li>Export/import configurations</li>
                </ul>
                <a href="custom_dashboard.php" class="feature-link">Build Dashboard →</a>
            </div>

            <!-- Settings Portal -->
            <div class="feature-card">
                <div class="feature-icon">⚙️</div>
                <h2 class="feature-title">Admin Settings Portal</h2>
                <span class="feature-status status-implemented">✓ Fully Implemented</span>
                <p class="feature-description">Centralized configuration management</p>
                <ul class="feature-capabilities">
                    <li>Logo upload & branding</li>
                    <li>SNMP configuration</li>
                    <li>NetFlow/sFlow settings</li>
                    <li>Alert channel configuration</li>
                    <li>Theme customization</li>
                    <li>Performance polling intervals</li>
                </ul>
                <a href="admin/settings.php" class="feature-link">Open Settings →</a>
            </div>

            <!-- NPM Module -->
            <div class="feature-card">
                <div class="feature-icon">🌐</div>
                <h2 class="feature-title">Network Performance Monitor</h2>
                <span class="feature-status status-implemented">✓ Fully Enhanced</span>
                <p class="feature-description">Comprehensive NPM capabilities</p>
                <ul class="feature-capabilities">
                    <li>Bandwidth utilization charts</li>
                    <li>SNMP device monitoring</li>
                    <li>Performance metrics tracking</li>
                    <li>Alert threshold configuration</li>
                    <li>Network topology visualization</li>
                    <li>Auto-refresh functionality</li>
                </ul>
                <a href="modules/npm.php" class="feature-link">Open NPM →</a>
            </div>
        </div>

        <div class="back-home">
            <a href="index.php">← Back to Main Dashboard</a>
        </div>
    </div>
</body>
</html>
