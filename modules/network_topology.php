<?php
/**
 * Network Topology Mapper
 * Interactive, auto-generated network maps
 */

require_once __DIR__ . '/../classes/Database.php';

$db = Database::getInstance();

// Get all network devices
$devices = $db->fetchAll("
    SELECT * FROM network_devices
    WHERE monitored = 1
    ORDER BY device_type, device_name
");

// Get performance data for devices
foreach ($devices as &$device) {
    // Simulate real-time metrics
    $device['cpu_usage'] = rand(10, 95);
    $device['memory_usage'] = rand(20, 90);
    $device['bandwidth_in'] = rand(100, 950);
    $device['bandwidth_out'] = rand(50, 500);
    $device['latency'] = rand(1, 50);
}

// Get topology connections (can be from discovery or manual configuration)
try {
    $connections = $db->fetchAll("
        SELECT * FROM network_topology_links
        WHERE active = 1
    ") ?: [];
} catch (Exception $e) {
    // Table doesn't exist yet
    $connections = [];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Network Topology Mapper</title>
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/vis-network@9.1.6/dist/vis-network.min.js"></script>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 0;
            background: #f5f5f5;
        }

        .header {
            background: white;
            padding: 20px 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            margin: 0;
            color: #667eea;
            font-size: 24px;
        }

        .controls {
            background: white;
            padding: 15px 30px;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        .controls button {
            padding: 8px 16px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s;
        }

        .controls button:hover {
            background: #764ba2;
        }

        .controls label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }

        #topology-container {
            width: 100%;
            height: calc(100vh - 160px);
            border: 1px solid #ddd;
            background: #fafafa;
        }

        .legend {
            position: absolute;
            top: 180px;
            right: 20px;
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 1000;
        }

        .legend h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #333;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 8px 0;
            font-size: 13px;
        }

        .legend-color {
            width: 20px;
            height: 20px;
            border-radius: 50%;
        }

        .device-info-panel {
            position: absolute;
            bottom: 20px;
            left: 20px;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            min-width: 300px;
            display: none;
        }

        .device-info-panel.active {
            display: block;
        }

        .device-info-panel h3 {
            margin: 0 0 15px 0;
            color: #667eea;
        }

        .metric {
            display: flex;
            justify-content: space-between;
            margin: 10px 0;
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .metric:last-child {
            border-bottom: none;
        }

        .metric-label {
            color: #666;
            font-size: 13px;
        }

        .metric-value {
            font-weight: 600;
            color: #333;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-online {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .status-offline {
            background: #ffebee;
            color: #c62828;
        }

        .status-warning {
            background: #fff3e0;
            color: #f57c00;
        }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>🗺️ Network Topology Mapper</h1>
            <p style="margin: 5px 0 0 0; color: #666; font-size: 14px;">Interactive network visualization and monitoring</p>
        </div>
        <a href="../index.php" style="text-decoration: none; color: #667eea; font-weight: 600;">← Dashboard</a>
    </div>

    <div class="controls">
        <button onclick="resetView()">🔄 Reset View</button>
        <button onclick="autoArrange()">📐 Auto Arrange</button>
        <button onclick="exportTopology()">💾 Export Image</button>
        <label>
            <input type="checkbox" id="showLabels" checked onchange="toggleLabels()">
            Show Labels
        </label>
        <label>
            <input type="checkbox" id="physicsEnabled" checked onchange="togglePhysics()">
            Physics Simulation
        </label>
        <label>
            Layout:
            <select id="layoutType" onchange="changeLayout()">
                <option value="hierarchical">Hierarchical</option>
                <option value="force">Force Directed</option>
                <option value="circular">Circular</option>
            </select>
        </label>
    </div>

    <div id="topology-container"></div>

    <div class="legend">
        <h3>Legend</h3>
        <div class="legend-item">
            <div class="legend-color" style="background: #4CAF50;"></div>
            <span>Online</span>
        </div>
        <div class="legend-item">
            <div class="legend-color" style="background: #f44336;"></div>
            <span>Offline</span>
        </div>
        <div class="legend-item">
            <div class="legend-color" style="background: #ff9800;"></div>
            <span>Warning</span>
        </div>
        <hr style="margin: 10px 0;">
        <div class="legend-item">
            <div style="width: 20px; height: 3px; background: #2196F3;"></div>
            <span>Connection</span>
        </div>
    </div>

    <div class="device-info-panel" id="deviceInfoPanel">
        <h3 id="deviceName">Device Name</h3>
        <div id="deviceDetails"></div>
    </div>

    <script>
        const devices = <?= json_encode($devices) ?>;
        let network;
        let nodes;
        let edges;

        // Create nodes from devices
        const nodesArray = devices.map((device, index) => {
            let color;
            switch(device.status) {
                case 'online':
                    color = '#4CAF50';
                    break;
                case 'offline':
                    color = '#f44336';
                    break;
                case 'warning':
                    color = '#ff9800';
                    break;
                default:
                    color = '#9e9e9e';
            }

            let shape;
            let size;
            switch(device.device_type) {
                case 'router':
                    shape = 'diamond';
                    size = 30;
                    break;
                case 'switch':
                    shape = 'box';
                    size = 25;
                    break;
                case 'firewall':
                    shape = 'star';
                    size = 28;
                    break;
                case 'access_point':
                    shape = 'triangle';
                    size = 22;
                    break;
                case 'server':
                    shape = 'box';
                    size = 28;
                    break;
                default:
                    shape = 'dot';
                    size = 20;
            }

            return {
                id: index,
                label: device.device_name,
                title: `${device.device_name}\nIP: ${device.ip_address}\nType: ${device.device_type}\nStatus: ${device.status}`,
                color: color,
                shape: shape,
                size: size,
                font: { size: 14 },
                deviceData: device
            };
        });

        // Create edges (connections) - simulate based on device types
        const edgesArray = [];
        let edgeId = 0;

        // Simple topology: connect routers to switches, switches to devices
        const routers = devices.filter(d => d.device_type === 'router');
        const switches = devices.filter(d => d.device_type === 'switch');
        const firewalls = devices.filter(d => d.device_type === 'firewall');
        const servers = devices.filter(d => d.device_type === 'server');
        const accessPoints = devices.filter(d => d.device_type === 'access_point');

        // Connect firewalls to routers
        firewalls.forEach(firewall => {
            if (routers.length > 0) {
                const routerIdx = devices.findIndex(d => d.id === routers[0].id);
                const firewallIdx = devices.findIndex(d => d.id === firewall.id);
                edgesArray.push({
                    id: edgeId++,
                    from: firewallIdx,
                    to: routerIdx,
                    color: { color: '#2196F3' },
                    width: 2
                });
            }
        });

        // Connect routers to switches
        routers.forEach(router => {
            switches.forEach(sw => {
                const routerIdx = devices.findIndex(d => d.id === router.id);
                const switchIdx = devices.findIndex(d => d.id === sw.id);
                edgesArray.push({
                    id: edgeId++,
                    from: routerIdx,
                    to: switchIdx,
                    color: { color: '#2196F3' },
                    width: 2
                });
            });
        });

        // Connect switches to servers and access points
        switches.forEach((sw, swIdx) => {
            const switchIdx = devices.findIndex(d => d.id === sw.id);

            // Connect to servers
            servers.forEach((server, idx) => {
                if (idx % switches.length === swIdx) {
                    const serverIdx = devices.findIndex(d => d.id === server.id);
                    edgesArray.push({
                        id: edgeId++,
                        from: switchIdx,
                        to: serverIdx,
                        color: { color: '#2196F3' },
                        width: 1.5
                    });
                }
            });

            // Connect to access points
            accessPoints.forEach((ap, idx) => {
                if (idx % switches.length === swIdx) {
                    const apIdx = devices.findIndex(d => d.id === ap.id);
                    edgesArray.push({
                        id: edgeId++,
                        from: switchIdx,
                        to: apIdx,
                        color: { color: '#2196F3' },
                        width: 1.5
                    });
                }
            });
        });

        // Create network
        nodes = new vis.DataSet(nodesArray);
        edges = new vis.DataSet(edgesArray);

        const container = document.getElementById('topology-container');
        const data = { nodes: nodes, edges: edges };
        const options = {
            layout: {
                hierarchical: {
                    enabled: true,
                    direction: 'UD',
                    sortMethod: 'directed',
                    nodeSpacing: 150,
                    levelSeparation: 200
                }
            },
            physics: {
                enabled: true,
                hierarchicalRepulsion: {
                    centralGravity: 0.0,
                    springLength: 200,
                    springConstant: 0.01,
                    nodeDistance: 150,
                    damping: 0.09
                }
            },
            interaction: {
                hover: true,
                tooltipDelay: 100,
                navigationButtons: true,
                keyboard: true
            },
            nodes: {
                borderWidth: 2,
                borderWidthSelected: 4,
                shadow: true
            },
            edges: {
                smooth: {
                    type: 'continuous'
                },
                shadow: true
            }
        };

        network = new vis.Network(container, data, options);

        // Event listeners
        network.on('click', function(params) {
            if (params.nodes.length > 0) {
                const nodeId = params.nodes[0];
                const device = devices[nodeId];
                showDeviceInfo(device);
            } else {
                hideDeviceInfo();
            }
        });

        network.on('doubleClick', function(params) {
            if (params.nodes.length > 0) {
                const nodeId = params.nodes[0];
                const device = devices[nodeId];
                // Navigate to device details page (you can create this)
                window.location.href = `device_details.php?id=${device.id}`;
            }
        });

        function showDeviceInfo(device) {
            const panel = document.getElementById('deviceInfoPanel');
            const nameEl = document.getElementById('deviceName');
            const detailsEl = document.getElementById('deviceDetails');

            nameEl.textContent = device.device_name;

            const statusClass = device.status === 'online' ? 'status-online' : (device.status === 'offline' ? 'status-offline' : 'status-warning');

            detailsEl.innerHTML = `
                <div class="metric">
                    <span class="metric-label">Status</span>
                    <span class="status-badge ${statusClass}">${device.status.toUpperCase()}</span>
                </div>
                <div class="metric">
                    <span class="metric-label">IP Address</span>
                    <span class="metric-value">${device.ip_address}</span>
                </div>
                <div class="metric">
                    <span class="metric-label">Type</span>
                    <span class="metric-value">${device.device_type}</span>
                </div>
                <div class="metric">
                    <span class="metric-label">Manufacturer</span>
                    <span class="metric-value">${device.manufacturer || 'Unknown'}</span>
                </div>
                <div class="metric">
                    <span class="metric-label">CPU Usage</span>
                    <span class="metric-value">${device.cpu_usage}%</span>
                </div>
                <div class="metric">
                    <span class="metric-label">Memory Usage</span>
                    <span class="metric-value">${device.memory_usage}%</span>
                </div>
                <div class="metric">
                    <span class="metric-label">Latency</span>
                    <span class="metric-value">${device.latency}ms</span>
                </div>
            `;

            panel.classList.add('active');
        }

        function hideDeviceInfo() {
            document.getElementById('deviceInfoPanel').classList.remove('active');
        }

        function resetView() {
            network.fit();
        }

        function autoArrange() {
            network.stabilize();
        }

        function toggleLabels() {
            const showLabels = document.getElementById('showLabels').checked;
            nodes.forEach(node => {
                nodes.update({
                    id: node.id,
                    label: showLabels ? node.label : ''
                });
            });
        }

        function togglePhysics() {
            const enabled = document.getElementById('physicsEnabled').checked;
            network.setOptions({ physics: { enabled: enabled } });
        }

        function changeLayout() {
            const layoutType = document.getElementById('layoutType').value;

            let newOptions = {};
            switch(layoutType) {
                case 'hierarchical':
                    newOptions = {
                        layout: {
                            hierarchical: {
                                enabled: true,
                                direction: 'UD',
                                sortMethod: 'directed'
                            }
                        }
                    };
                    break;
                case 'force':
                    newOptions = {
                        layout: {
                            hierarchical: { enabled: false }
                        }
                    };
                    break;
                case 'circular':
                    newOptions = {
                        layout: {
                            hierarchical: { enabled: false }
                        }
                    };
                    // Arrange in circle
                    const radius = 300;
                    const angleStep = (2 * Math.PI) / nodesArray.length;
                    nodesArray.forEach((node, index) => {
                        const angle = index * angleStep;
                        nodes.update({
                            id: node.id,
                            x: radius * Math.cos(angle),
                            y: radius * Math.sin(angle),
                            fixed: { x: true, y: true }
                        });
                    });
                    break;
            }

            network.setOptions(newOptions);
        }

        function exportTopology() {
            const canvas = document.querySelector('#topology-container canvas');
            const link = document.createElement('a');
            link.download = 'network-topology.png';
            link.href = canvas.toDataURL();
            link.click();
        }

        // Auto-refresh every 30 seconds
        setInterval(() => {
            location.reload();
        }, 30000);
    </script>
</body>
</html>
