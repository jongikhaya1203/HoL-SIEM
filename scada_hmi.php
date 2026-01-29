<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SCADA HMI - Industrial Control System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #1a1a2e;
            color: #eee;
        }

        button {
            cursor: pointer;
            border: none;
            border-radius: 4px;
            font-family: inherit;
            font-size: 14px;
            transition: all 0.3s ease;
            outline: none;
        }

        button:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        button:active {
            transform: translateY(0);
        }

        .header {
            background: linear-gradient(135deg, #0f3460 0%, #16213e 100%);
            padding: 15px 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 24px;
            color: #00d9ff;
        }

        .header .status {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .status-indicator {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .status-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        .status-dot.online {
            background: #00ff00;
            box-shadow: 0 0 10px #00ff00;
        }

        .status-dot.offline {
            background: #ff0000;
            box-shadow: 0 0 10px #ff0000;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .container {
            padding: 20px;
            max-width: 1800px;
            margin: 0 auto;
        }

        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .tab {
            padding: 12px 24px;
            background: #16213e;
            border: none;
            border-radius: 8px 8px 0 0;
            color: #aaa;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 16px;
        }

        .tab:hover {
            background: #1f2b4a;
            color: #00d9ff;
        }

        .tab.active {
            background: #0f3460;
            color: #00d9ff;
            border-bottom: 3px solid #00d9ff;
        }

        .panel {
            display: none;
        }

        .panel.active {
            display: block;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .widget {
            background: #16213e;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.3);
            border: 1px solid #0f3460;
        }

        .widget h2 {
            font-size: 18px;
            margin-bottom: 15px;
            color: #00d9ff;
            border-bottom: 2px solid #0f3460;
            padding-bottom: 10px;
        }

        .metric {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #0f3460;
        }

        .metric:last-child {
            border-bottom: none;
        }

        .metric-label {
            color: #aaa;
            font-size: 14px;
        }

        .metric-value {
            font-size: 20px;
            font-weight: bold;
            color: #00ff00;
        }

        .metric-value.warning {
            color: #ffaa00;
        }

        .metric-value.critical {
            color: #ff0000;
            animation: blink 1s infinite;
        }

        @keyframes blink {
            0%, 50%, 100% { opacity: 1; }
            25%, 75% { opacity: 0.5; }
        }

        .alarm-panel {
            grid-column: 1 / -1;
        }

        .alarm-item {
            background: #ff000020;
            border-left: 4px solid #ff0000;
            padding: 12px;
            margin: 8px 0;
            border-radius: 4px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .alarm-item.high {
            border-left-color: #ffaa00;
            background: #ffaa0020;
        }

        .alarm-item.medium {
            border-left-color: #ffff00;
            background: #ffff0015;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #00d9ff;
            color: #000;
        }

        .btn-primary:hover {
            background: #00b8d4;
        }

        .btn-danger {
            background: #ff4444;
            color: #fff;
        }

        .btn-success {
            background: #00ff00;
            color: #000;
        }

        .valve-control {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }

        .tank-visual {
            width: 100%;
            height: 200px;
            background: linear-gradient(to bottom, #0f3460 0%, #1a1a2e 100%);
            border-radius: 8px;
            position: relative;
            overflow: hidden;
            border: 2px solid #00d9ff;
        }

        .tank-level {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, #00d9ff 0%, #0099cc 100%);
            transition: height 1s;
        }

        .tank-percentage {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 36px;
            font-weight: bold;
            color: #fff;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.8);
        }

        .progress-bar {
            width: 100%;
            height: 20px;
            background: #0f3460;
            border-radius: 10px;
            overflow: hidden;
            margin-top: 8px;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #00d9ff 0%, #0099cc 100%);
            transition: width 0.5s;
        }

        .control-panel {
            background: #1f2b4a;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
        }

        .gauge {
            width: 150px;
            height: 150px;
            margin: 10px auto;
            position: relative;
        }

        .gauge svg {
            transform: rotate(-90deg);
        }

        .gauge-value {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 24px;
            font-weight: bold;
            color: #00d9ff;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .data-table th {
            background: #0f3460;
            color: #00d9ff;
            padding: 10px;
            text-align: left;
        }

        .data-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #0f3460;
        }

        .data-table tr:hover {
            background: #1f2b4a;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }

        .status-normal {
            background: #00ff0030;
            color: #00ff00;
        }

        .status-warning {
            background: #ffaa0030;
            color: #ffaa00;
        }

        .status-critical {
            background: #ff000030;
            color: #ff0000;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🏭 SCADA HMI - Industrial Monitoring & Control</h1>
        <div class="status">
            <div class="status-indicator">
                <span class="status-dot online"></span>
                <span>System Online</span>
            </div>
            <div class="status-indicator">
                <span>PLCs: <span id="plc-count">5/5</span></span>
            </div>
            <div class="status-indicator">
                <span>RTUs: <span id="rtu-count">12/15</span></span>
            </div>
            <div class="status-indicator">
                <span id="current-time"><?php echo date('Y-m-d H:i:s'); ?></span>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="tabs">
            <button class="tab active" onclick="switchTab('overview')">📊 Overview</button>
            <button class="tab" onclick="switchTab('oilgas')">🛢️ Oil & Gas</button>
            <button class="tab" onclick="switchTab('rail')">🚂 Rail System</button>
            <button class="tab" onclick="switchTab('mining')">⛏️ Mining</button>
            <button class="tab" onclick="switchTab('manufacturing')">🏭 Manufacturing</button>
            <button class="tab" onclick="switchTab('alarms')">🚨 Alarms</button>
        </div>

        <!-- OVERVIEW PANEL -->
        <div id="overview-panel" class="panel active">
            <div class="dashboard-grid">
                <!-- Site Statistics -->
                <div class="widget">
                    <h2>📊 System Statistics</h2>
                    <div class="metric">
                        <span class="metric-label">Active Sites</span>
                        <span class="metric-value">4</span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">Total Assets</span>
                        <span class="metric-value">127</span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">SCADA Tags</span>
                        <span class="metric-value">1,458</span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">Active Alarms</span>
                        <span class="metric-value critical" id="alarm-count">3</span>
                    </div>
                </div>

                <!-- Tank Levels -->
                <div class="widget">
                    <h2>🛢️ Tank Levels</h2>
                    <div class="tank-visual">
                        <div class="tank-level" id="tank-level" style="height: 75%"></div>
                        <div class="tank-percentage" id="tank-percentage">75%</div>
                    </div>
                    <div class="metric" style="margin-top: 15px;">
                        <span class="metric-label">Tank T-101</span>
                        <span class="metric-value">7,500 / 10,000 bbl</span>
                    </div>
                </div>

                <!-- Valve Control -->
                <div class="widget">
                    <h2>🔧 Valve Control - MOV-101</h2>
                    <div class="metric">
                        <span class="metric-label">Position</span>
                        <span class="metric-value" id="valve-position">45%</span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">Status</span>
                        <span class="metric-value" id="valve-status">Partial Open</span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">Interlock</span>
                        <span class="metric-value">Normal</span>
                    </div>
                    <div class="valve-control">
                        <button class="btn btn-success" onclick="controlValve('open')">Open</button>
                        <button class="btn btn-danger" onclick="controlValve('close')">Close</button>
                        <button class="btn btn-primary" onclick="controlValve('stop')">Stop</button>
                    </div>
                </div>

                <!-- PLC Status -->
                <div class="widget">
                    <h2>🖥️ PLC Status</h2>
                    <div class="metric">
                        <span class="metric-label">PLC-01 (Main)</span>
                        <span class="metric-value">● Online</span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">PLC-02 (Backup)</span>
                        <span class="metric-value">● Online</span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">RTU-01 (Remote)</span>
                        <span class="metric-value">● Online</span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">Last Poll</span>
                        <span class="metric-value" id="last-poll">1.2s ago</span>
                    </div>
                </div>

                <!-- Production Metrics -->
                <div class="widget">
                    <h2>⚙️ Production Metrics</h2>
                    <div class="metric">
                        <span class="metric-label">Production Rate</span>
                        <span class="metric-value">850 bbl/day</span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">Efficiency</span>
                        <span class="metric-value">92.5%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 92.5%"></div>
                    </div>
                </div>

                <!-- Calibration Status -->
                <div class="widget">
                    <h2>📏 Calibration Status</h2>
                    <div class="metric">
                        <span class="metric-label">Due This Week</span>
                        <span class="metric-value warning">2</span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">Overdue</span>
                        <span class="metric-value critical">0</span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">Valid</span>
                        <span class="metric-value">125</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- OIL & GAS PANEL -->
        <div id="oilgas-panel" class="panel">
            <div class="dashboard-grid">
                <div class="widget">
                    <h2>🛢️ Pipeline Monitoring</h2>
                    <table class="data-table">
                        <tr>
                            <th>Pipeline</th>
                            <th>Flow In</th>
                            <th>Flow Out</th>
                            <th>Status</th>
                        </tr>
                        <tr>
                            <td>Pipeline-A</td>
                            <td>1,250 bbl/hr</td>
                            <td>1,245 bbl/hr</td>
                            <td><span class="status-badge status-normal">NORMAL</span></td>
                        </tr>
                        <tr>
                            <td>Pipeline-B</td>
                            <td>980 bbl/hr</td>
                            <td>975 bbl/hr</td>
                            <td><span class="status-badge status-normal">NORMAL</span></td>
                        </tr>
                        <tr>
                            <td>Pipeline-C</td>
                            <td>1,500 bbl/hr</td>
                            <td>1,425 bbl/hr</td>
                            <td><span class="status-badge status-warning">LEAK DETECTED</span></td>
                        </tr>
                    </table>
                </div>

                <div class="widget">
                    <h2>⛽ Wellhead Monitoring</h2>
                    <div class="metric">
                        <span class="metric-label">Well-01 Casing Pressure</span>
                        <span class="metric-value">1,850 PSI</span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">Well-01 Tubing Pressure</span>
                        <span class="metric-value">1,250 PSI</span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">Flow Rate</span>
                        <span class="metric-value">125 bbl/day</span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">Status</span>
                        <span class="metric-value">Producing</span>
                    </div>
                </div>

                <div class="widget">
                    <h2>🔬 LACT Unit</h2>
                    <div class="metric">
                        <span class="metric-label">Gross Volume</span>
                        <span class="metric-value">10,250 bbl</span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">Net Volume</span>
                        <span class="metric-value">10,125 bbl</span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">BS&W</span>
                        <span class="metric-value">1.2%</span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">API Gravity</span>
                        <span class="metric-value">38.5°</span>
                    </div>
                </div>

                <div class="widget">
                    <h2>💧 Separator Status</h2>
                    <div class="metric">
                        <span class="metric-label">Oil Level</span>
                        <span class="metric-value">65%</span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">Water Level</span>
                        <span class="metric-value">15%</span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">Gas Pressure</span>
                        <span class="metric-value">125 PSI</span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">Temperature</span>
                        <span class="metric-value">85°F</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- RAIL SYSTEM PANEL -->
        <div id="rail-panel" class="panel">
            <div class="dashboard-grid">
                <!-- System Overview -->
                <div class="widget">
                    <h2>🚄 Rail System Overview</h2>
                    <div id="rail-overview"></div>
                </div>

                <!-- Track Circuits -->
                <div class="widget">
                    <h2>🚦 Track Circuits</h2>
                    <div id="rail-track-circuits"></div>
                </div>

                <!-- Signal Control -->
                <div class="widget">
                    <h2>🚥 Signal Control</h2>
                    <div id="rail-signals"></div>
                </div>

                <!-- Point Control -->
                <div class="widget">
                    <h2>🔀 Point (Switch) Control</h2>
                    <div id="rail-points"></div>
                </div>

                <!-- Train Tracking -->
                <div class="widget">
                    <h2>🚂 Train Tracking</h2>
                    <div id="rail-trains"></div>
                </div>

                <!-- Platform Management -->
                <div class="widget">
                    <h2>🚉 Platform Management</h2>
                    <div id="rail-platforms"></div>
                </div>

                <!-- Level Crossings -->
                <div class="widget">
                    <h2>🚧 Level Crossings</h2>
                    <div id="rail-crossings"></div>
                </div>

                <!-- Emergency Systems -->
                <div class="widget">
                    <h2>🚨 Emergency Systems</h2>
                    <div id="rail-emergency"></div>
                </div>

                <!-- Event Log -->
                <div class="widget" style="grid-column: 1/-1;">
                    <h2>📋 Recent Events</h2>
                    <div id="rail-events" style="max-height:200px;overflow-y:auto;"></div>
                </div>
            </div>
        </div>

        <!-- MINING PANEL -->
        <div id="mining-panel" class="panel">
            <div class="dashboard-grid">
                <div class="widget">
                    <h2>💨 Ventilation System</h2>
                    <div class="metric">
                        <span class="metric-label">Fan-01 Speed</span>
                        <span class="metric-value">1,450 RPM</span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">Airflow</span>
                        <span class="metric-value">50,000 CFM</span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">Motor Current</span>
                        <span class="metric-value">85 A</span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">Status</span>
                        <span class="metric-value">Running</span>
                    </div>
                </div>

                <div class="widget">
                    <h2>☣️ Gas Detection</h2>
                    <div class="metric">
                        <span class="metric-label">Methane (CH₄)</span>
                        <span class="metric-value">250 ppm</span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">CO</span>
                        <span class="metric-value">15 ppm</span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">O₂</span>
                        <span class="metric-value">20.8%</span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">Status</span>
                        <span class="metric-value">Safe</span>
                    </div>
                </div>

                <div class="widget">
                    <h2>⬆️ Hoist System</h2>
                    <div class="metric">
                        <span class="metric-label">Position</span>
                        <span class="metric-value">-450m</span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">Speed</span>
                        <span class="metric-value">5.2 m/s</span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">Load</span>
                        <span class="metric-value">3,500 kg</span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">Brake Status</span>
                        <span class="metric-value">Released</span>
                    </div>
                </div>

                <div class="widget">
                    <h2>👷 Personnel Tracking</h2>
                    <div class="metric">
                        <span class="metric-label">Underground</span>
                        <span class="metric-value">42 Workers</span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">Zone A</span>
                        <span class="metric-value">15 Workers</span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">Zone B</span>
                        <span class="metric-value">18 Workers</span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">Emergency Active</span>
                        <span class="metric-value">None</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- MANUFACTURING PANEL -->
        <div id="manufacturing-panel" class="panel">
            <div class="dashboard-grid">
                <div class="widget">
                    <h2>🏭 Production Line Status</h2>
                    <div class="metric">
                        <span class="metric-label">Line 1 - Running</span>
                        <span class="metric-value">● Active</span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">Production Count</span>
                        <span class="metric-value">1,258 units</span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">Reject Count</span>
                        <span class="metric-value warning">32 units</span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">Efficiency</span>
                        <span class="metric-value">97.5%</span>
                    </div>
                </div>

                <div class="widget">
                    <h2>🤖 Robot Status</h2>
                    <div class="metric">
                        <span class="metric-label">Robot-01</span>
                        <span class="metric-value">Running</span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">Cycle Count</span>
                        <span class="metric-value">1,245</span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">Program</span>
                        <span class="metric-value">WELD-A</span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">Errors</span>
                        <span class="metric-value">0</span>
                    </div>
                </div>

                <div class="widget">
                    <h2>📊 OEE Calculation</h2>
                    <div class="metric">
                        <span class="metric-label">Availability</span>
                        <span class="metric-value">92.5%</span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">Performance</span>
                        <span class="metric-value">95.0%</span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">Quality</span>
                        <span class="metric-value">97.5%</span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">Overall OEE</span>
                        <span class="metric-value">85.6%</span>
                    </div>
                </div>

                <div class="widget">
                    <h2>✅ Quality Control</h2>
                    <div class="metric">
                        <span class="metric-label">Inspected</span>
                        <span class="metric-value">1,258</span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">Passed</span>
                        <span class="metric-value">1,226</span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">Failed</span>
                        <span class="metric-value">32</span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">Pass Rate</span>
                        <span class="metric-value">97.5%</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ALARMS PANEL -->
        <div id="alarms-panel" class="panel">
            <div class="widget alarm-panel">
                <h2>🚨 Active Alarms</h2>
                <div class="alarm-item">
                    <div>
                        <strong>CRITICAL</strong> - Tank T-103 High Level (95%)
                        <br><small>Triggered: <span class="alarm-time">2 minutes ago</span> | Site: Refinery-A</small>
                    </div>
                    <button class="btn btn-primary" onclick="acknowledgeAlarm(1)">Acknowledge</button>
                </div>
                <div class="alarm-item high">
                    <div>
                        <strong>HIGH</strong> - Pipeline P-201 Pressure Low (45 PSI)
                        <br><small>Triggered: <span class="alarm-time">5 minutes ago</span> | Site: Pipeline-B</small>
                    </div>
                    <button class="btn btn-primary" onclick="acknowledgeAlarm(2)">Acknowledge</button>
                </div>
                <div class="alarm-item medium">
                    <div>
                        <strong>MEDIUM</strong> - Valve MOV-105 Position Deviation
                        <br><small>Triggered: <span class="alarm-time">12 minutes ago</span> | Site: Refinery-A</small>
                    </div>
                    <button class="btn btn-primary" onclick="acknowledgeAlarm(3)">Acknowledge</button>
                </div>
            </div>

            <div class="dashboard-grid" style="margin-top: 20px;">
                <div class="widget">
                    <h2>📈 Alarm Statistics</h2>
                    <div class="metric">
                        <span class="metric-label">Total Active</span>
                        <span class="metric-value">3</span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">Critical</span>
                        <span class="metric-value critical">1</span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">High</span>
                        <span class="metric-value warning">1</span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">Medium/Low</span>
                        <span class="metric-value">1</span>
                    </div>
                </div>

                <div class="widget">
                    <h2>🕐 Recent Cleared</h2>
                    <div class="metric">
                        <span class="metric-label">Last Hour</span>
                        <span class="metric-value">5</span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">Last 24 Hours</span>
                        <span class="metric-value">42</span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">Avg Response Time</span>
                        <span class="metric-value">3.2 min</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Tab switching functionality
        function switchTab(tabName) {
            // Hide all panels
            document.querySelectorAll('.panel').forEach(panel => {
                panel.classList.remove('active');
            });

            // Remove active class from all tabs
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });

            // Show selected panel
            document.getElementById(tabName + '-panel').classList.add('active');

            // Add active class to clicked tab
            event.target.classList.add('active');

            // Load rail system data when rail tab is opened
            if (tabName === 'rail') {
                if (typeof loadRailSystem === 'function') {
                    loadRailSystem();
                }
            }

            console.log('Switched to ' + tabName + ' panel');
        }

        // Valve control function
        function controlValve(action) {
            const valvePosition = document.getElementById('valve-position');
            const valveStatus = document.getElementById('valve-status');

            if (action === 'open') {
                valvePosition.textContent = '100%';
                valveStatus.textContent = 'Fully Open';
                alert('✓ Valve MOV-101 opening...');
            } else if (action === 'close') {
                valvePosition.textContent = '0%';
                valveStatus.textContent = 'Fully Closed';
                alert('✓ Valve MOV-101 closing...');
            } else if (action === 'stop') {
                alert('✓ Valve MOV-101 stopped at current position');
            }
        }

        // Acknowledge alarm function
        function acknowledgeAlarm(alarmId) {
            if (confirm('Acknowledge alarm #' + alarmId + '?')) {
                event.target.closest('.alarm-item').style.opacity = '0.5';
                event.target.textContent = 'Acknowledged';
                event.target.disabled = true;

                // Update alarm count
                const alarmCount = document.getElementById('alarm-count');
                const currentCount = parseInt(alarmCount.textContent);
                alarmCount.textContent = Math.max(0, currentCount - 1);

                alert('✓ Alarm #' + alarmId + ' acknowledged');
            }
        }

        // Update current time
        function updateTime() {
            const now = new Date();
            const timeStr = now.toISOString().slice(0, 19).replace('T', ' ');
            document.getElementById('current-time').textContent = timeStr;
        }

        // Simulate real-time tank level updates
        function updateTankLevel() {
            const randomLevel = 60 + Math.random() * 30; // 60-90%
            const tankLevel = document.getElementById('tank-level');
            const tankPercentage = document.getElementById('tank-percentage');

            tankLevel.style.height = randomLevel + '%';
            tankPercentage.textContent = Math.round(randomLevel) + '%';
        }

        // Update alarm times
        function updateAlarmTimes() {
            const times = document.querySelectorAll('.alarm-time');
            times.forEach((time, index) => {
                const minutes = [2, 5, 12][index] || 0;
                const minuteOffset = Math.floor(Math.random() * 2);
                time.textContent = (minutes + minuteOffset) + ' minutes ago';
            });
        }

        // Initialize
        setInterval(updateTime, 1000);
        setInterval(updateTankLevel, 5000);
        setInterval(updateAlarmTimes, 10000);

        // Initial update
        updateTime();
        updateAlarmTimes();

        // ========================================
        // RAIL SYSTEM FUNCTIONS
        // ========================================

        let railSystemData = null;

        // Load rail system data
        function loadRailSystem() {
            fetch('rail_control_api.php?action=get_status&site_id=2')
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        railSystemData = result.data;
                        updateRailDisplay();
                    }
                })
                .catch(error => console.error('Rail system error:', error));
        }

        // Update all rail displays
        function updateRailDisplay() {
            if (!railSystemData) return;

            updateRailOverview();
            updateTrackCircuits();
            updateSignals();
            updatePoints();
            updateTrains();
            updatePlatforms();
            updateLevelCrossings();
            updateEmergencySystems();
            loadRailEvents();
        }

        // Rail system overview
        function updateRailOverview() {
            const circuits = railSystemData.track_circuits || [];
            const signals = railSystemData.signals || [];
            const trains = railSystemData.trains || [];
            const emergency = railSystemData.emergency_systems || [];

            const occupied = circuits.filter(c => c.occupancy_status === 'occupied').length;
            const clear = circuits.filter(c => c.occupancy_status === 'clear').length;
            const redSignals = signals.filter(s => s.current_aspect === 'red').length;
            const greenSignals = signals.filter(s => s.current_aspect === 'green').length;
            const emergencyActive = emergency.filter(e => e.is_activated == 1).length;

            document.getElementById('rail-overview').innerHTML = `
                <div class="metric"><span class="metric-label">Track Circuits Clear</span><span class="metric-value">${clear}/${circuits.length}</span></div>
                <div class="metric"><span class="metric-label">Track Circuits Occupied</span><span class="metric-value">${occupied}</span></div>
                <div class="metric"><span class="metric-label">Signals Green</span><span class="metric-value" style="color:#0f0;">${greenSignals}</span></div>
                <div class="metric"><span class="metric-label">Signals Red</span><span class="metric-value" style="color:#f00;">${redSignals}</span></div>
                <div class="metric"><span class="metric-label">Active Trains</span><span class="metric-value">${trains.length}</span></div>
                <div class="metric"><span class="metric-label">Emergency Systems</span><span class="metric-value" style="color:${emergencyActive > 0 ? '#f00' : '#0f0'};">${emergencyActive > 0 ? 'ACTIVE' : 'Normal'}</span></div>
            `;
        }

        // Track circuits display
        function updateTrackCircuits() {
            const circuits = railSystemData.track_circuits || [];
            let html = '<table class="data-table" style="font-size:0.85em;"><tr><th>Code</th><th>Section</th><th>Status</th><th>Voltage</th></tr>';

            circuits.slice(0, 6).forEach(circuit => {
                const statusColor = circuit.occupancy_status === 'occupied' ? '#f00' :
                                  circuit.occupancy_status === 'clear' ? '#0f0' : '#ff0';
                html += `<tr>
                    <td>${circuit.circuit_code}</td>
                    <td>${circuit.section_name || 'N/A'}</td>
                    <td style="color:${statusColor};">● ${circuit.occupancy_status.toUpperCase()}</td>
                    <td>${circuit.voltage_v || '0'}V</td>
                </tr>`;
            });

            html += '</table>';
            document.getElementById('rail-track-circuits').innerHTML = html;
        }

        // Signal control
        function updateSignals() {
            const signals = railSystemData.signals || [];
            let html = '';

            signals.forEach(signal => {
                const aspectColor = signal.current_aspect === 'green' ? '#0f0' :
                                  signal.current_aspect === 'yellow' ? '#ff0' : '#f00';
                html += `
                    <div class="metric">
                        <span class="metric-label">${signal.signal_code} (${signal.signal_type})</span>
                        <span class="metric-value" style="color:${aspectColor};">● ${signal.current_aspect.toUpperCase()}</span>
                    </div>
                    <div style="margin:5px 0;">
                        <button onclick="changeSignal(${signal.id}, 'red')" style="background:#f00;padding:3px 8px;margin:2px;">Red</button>
                        <button onclick="changeSignal(${signal.id}, 'yellow')" style="background:#ff0;color:#000;padding:3px 8px;margin:2px;">Yellow</button>
                        <button onclick="changeSignal(${signal.id}, 'green')" style="background:#0f0;color:#000;padding:3px 8px;margin:2px;">Green</button>
                    </div>
                `;
            });

            document.getElementById('rail-signals').innerHTML = html;
        }

        // Change signal aspect
        function changeSignal(signalId, aspect) {
            if (!confirm(\`Change signal to \${aspect.toUpperCase()}?\`)) return;

            const formData = new FormData();
            formData.append('action', 'change_signal');
            formData.append('signal_id', signalId);
            formData.append('aspect', aspect);

            fetch('rail_control_api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                alert(result.message);
                if (result.success) loadRailSystem();
            })
            .catch(error => alert('Error: ' + error));
        }

        // Point control
        function updatePoints() {
            const points = railSystemData.points || [];
            let html = '';

            points.forEach(point => {
                const posColor = point.current_position === 'normal' ? '#0f0' : '#ff0';
                html += `
                    <div class="metric">
                        <span class="metric-label">${point.point_code}</span>
                        <span class="metric-value" style="color:${posColor};">${point.current_position.toUpperCase()}</span>
                    </div>
                    <div style="margin:5px 0;">
                        <button onclick="movePoint(${point.id}, 'normal')" style="background:#0f0;color:#000;padding:3px 8px;margin:2px;">Normal</button>
                        <button onclick="movePoint(${point.id}, 'reverse')" style="background:#ff0;color:#000;padding:3px 8px;margin:2px;">Reverse</button>
                    </div>
                `;
            });

            document.getElementById('rail-points').innerHTML = html;
        }

        // Move point
        function movePoint(pointId, position) {
            if (!confirm(\`Move point to \${position.toUpperCase()}?\`)) return;

            const formData = new FormData();
            formData.append('action', 'move_point');
            formData.append('point_id', pointId);
            formData.append('position', position);

            fetch('rail_control_api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                alert(result.message);
                if (result.success) loadRailSystem();
            })
            .catch(error => alert('Error: ' + error));
        }

        // Train tracking
        function updateTrains() {
            const trains = railSystemData.trains || [];
            let html = '<table class="data-table" style="font-size:0.85em;"><tr><th>Train</th><th>Service</th><th>Location</th><th>Speed</th></tr>';

            trains.forEach(train => {
                html += `<tr>
                    <td>${train.train_number}</td>
                    <td>${train.service_name || 'N/A'}</td>
                    <td>${train.current_section_name || 'Unknown'}</td>
                    <td>${train.current_speed_kmh || 0} km/h</td>
                </tr>`;
            });

            html += '</table>';
            document.getElementById('rail-trains').innerHTML = html;
        }

        // Platform management
        function updatePlatforms() {
            const platforms = railSystemData.platforms || [];
            let html = '';

            platforms.forEach(platform => {
                const trainInfo = platform.train_number || 'No train';
                const doorsColor = platform.doors_status === 'open' ? '#f00' : '#0f0';
                html += `
                    <div class="metric">
                        <span class="metric-label">Platform ${platform.platform_number}</span>
                        <span class="metric-value">${trainInfo}</span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">Doors</span>
                        <span class="metric-value" style="color:${doorsColor};">${platform.doors_status.toUpperCase()}</span>
                    </div>
                    <div style="margin:5px 0;">
                        <button onclick="controlDoors(${platform.id}, 'open')" style="background:#f00;padding:3px 8px;margin:2px;">Open Doors</button>
                        <button onclick="controlDoors(${platform.id}, 'close')" style="background:#0f0;color:#000;padding:3px 8px;margin:2px;">Close Doors</button>
                    </div>
                `;
            });

            document.getElementById('rail-platforms').innerHTML = html;
        }

        // Control platform doors
        function controlDoors(platformId, action) {
            if (!confirm(\`\${action.toUpperCase()} platform doors?\`)) return;

            const formData = new FormData();
            formData.append('action', 'control_doors');
            formData.append('platform_id', platformId);
            formData.append('door_action', action);

            fetch('rail_control_api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                alert(result.message);
                if (result.success) setTimeout(loadRailSystem, 1500);
            })
            .catch(error => alert('Error: ' + error));
        }

        // Level crossing control
        function updateLevelCrossings() {
            const crossings = railSystemData.level_crossings || [];
            let html = '';

            crossings.forEach(crossing => {
                const barrierColor = crossing.barrier_status === 'lowered' ? '#f00' : '#0f0';
                html += `
                    <div class="metric">
                        <span class="metric-label">${crossing.crossing_name}</span>
                        <span class="metric-value" style="color:${barrierColor};">Barriers ${crossing.barrier_status.toUpperCase()}</span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">Lights</span>
                        <span class="metric-value">${crossing.lights_status.toUpperCase()}</span>
                    </div>
                    <div style="margin:5px 0;">
                        <button onclick="controlCrossing(${crossing.id}, 'lower')" style="background:#f00;padding:3px 8px;margin:2px;">Lower</button>
                        <button onclick="controlCrossing(${crossing.id}, 'raise')" style="background:#0f0;color:#000;padding:3px 8px;margin:2px;">Raise</button>
                    </div>
                `;
            });

            document.getElementById('rail-crossings').innerHTML = html;
        }

        // Control level crossing
        function controlCrossing(crossingId, action) {
            if (!confirm(\`\${action.toUpperCase()} level crossing barriers?\`)) return;

            const formData = new FormData();
            formData.append('action', 'control_crossing');
            formData.append('crossing_id', crossingId);
            formData.append('crossing_action', action);

            fetch('rail_control_api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                alert(result.message);
                if (result.success) setTimeout(loadRailSystem, 1500);
            })
            .catch(error => alert('Error: ' + error));
        }

        // Emergency systems
        function updateEmergencySystems() {
            const systems = railSystemData.emergency_systems || [];
            let html = '';

            systems.forEach(system => {
                const isActive = system.is_activated == 1;
                const statusColor = isActive ? '#f00' : '#0f0';
                html += `
                    <div class="metric">
                        <span class="metric-label">Status</span>
                        <span class="metric-value" style="color:${statusColor};font-weight:bold;">${isActive ? 'EMERGENCY ACTIVE' : 'Normal'}</span>
                    </div>
                    <div style="margin:10px 0;">
                        ${!isActive ? \`
                            <button onclick="activateEmergency(\${system.id})" style="background:#f00;color:#fff;padding:10px 20px;font-weight:bold;font-size:1.1em;">EMERGENCY STOP</button>
                        \` : \`
                            <button onclick="resetEmergency(\${system.id})" style="background:#0f0;color:#000;padding:10px 20px;font-weight:bold;">RESET EMERGENCY</button>
                        \`}
                    </div>
                `;
            });

            document.getElementById('rail-emergency').innerHTML = html;
        }

        // Activate emergency stop
        function activateEmergency(systemId) {
            if (!confirm('ACTIVATE EMERGENCY STOP? This will set ALL signals to RED!')) return;

            const formData = new FormData();
            formData.append('action', 'emergency_stop');
            formData.append('system_id', systemId);

            fetch('rail_control_api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                alert(result.message);
                if (result.success) loadRailSystem();
            })
            .catch(error => alert('Error: ' + error));
        }

        // Reset emergency stop
        function resetEmergency(systemId) {
            if (!confirm('Reset emergency stop system?')) return;

            const formData = new FormData();
            formData.append('action', 'reset_emergency');
            formData.append('system_id', systemId);

            fetch('rail_control_api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                alert(result.message);
                if (result.success) loadRailSystem();
            })
            .catch(error => alert('Error: ' + error));
        }

        // Load rail events
        function loadRailEvents() {
            fetch('rail_control_api.php?action=get_events&site_id=2')
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        const events = result.events || [];
                        let html = '<table class="data-table" style="font-size:0.8em;"><tr><th>Time</th><th>Event</th><th>Operator</th></tr>';

                        events.slice(0, 10).forEach(event => {
                            const time = new Date(event.event_time).toLocaleTimeString();
                            html += \`<tr>
                                <td>\${time}</td>
                                <td>\${event.description}</td>
                                <td>\${event.operator || 'System'}</td>
                            </tr>\`;
                        });

                        html += '</table>';
                        document.getElementById('rail-events').innerHTML = html;
                    }
                })
                .catch(error => console.error('Events error:', error));
        }

        // Auto-refresh rail system every 5 seconds
        setInterval(() => {
            if (document.getElementById('rail-panel').classList.contains('active')) {
                loadRailSystem();
            }
        }, 5000);

        console.log('SCADA HMI Initialized - All modules active');
    </script>
</body>
</html>
