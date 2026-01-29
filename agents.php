<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent Management - Network Security Scanner</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        h1 {
            color: white;
            text-align: center;
            margin-bottom: 10px;
            font-size: 2.5em;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .subtitle {
            color: rgba(255,255,255,0.9);
            text-align: center;
            margin-bottom: 30px;
            font-size: 1.1em;
        }

        .nav {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .nav a {
            color: #667eea;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 5px;
            transition: all 0.3s;
            font-weight: 600;
        }

        .nav a:hover {
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
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .stat-value {
            font-size: 2.5em;
            font-weight: bold;
            color: #667eea;
        }

        .stat-label {
            color: #666;
            margin-top: 5px;
        }

        .agents-table {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
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
            border-bottom: 1px solid #eee;
        }

        tr:hover {
            background: #f5f5f5;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
        }

        .status-active {
            background: #4caf50;
            color: white;
        }

        .status-inactive {
            background: #ff9800;
            color: white;
        }

        .status-error {
            background: #f44336;
            color: white;
        }

        .btn {
            display: inline-block;
            padding: 8px 16px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn:hover {
            background: #5568d3;
        }

        .btn-small {
            padding: 5px 10px;
            font-size: 0.9em;
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .agent-details {
            margin-top: 20px;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 5px;
            display: none;
        }

        .agent-details.show {
            display: block;
        }

        .detail-section {
            margin: 10px 0;
        }

        .detail-label {
            font-weight: 600;
            color: #667eea;
        }

        .api-key-section {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .api-key {
            font-family: monospace;
            background: white;
            padding: 10px;
            border-radius: 3px;
            word-break: break-all;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Agent Management</h1>
        <p class="subtitle">Monitor and manage deployed security agents</p>

        <div class="nav">
            <a href="index.php">Dashboard</a>
            <a href="agents.php">Agents</a>
            <a href="tenants.php">Tenants</a>
            <a href="logout.php" style="background: #f44336; color: white; padding: 10px 20px; border-radius: 5px;">Logout</a>
        </div>

        <?php
        require_once __DIR__ . '/classes/Database.php';
        $db = Database::getInstance();

        // Handle tenant selection
        $selectedTenant = $_GET['tenant_id'] ?? 'all';

        // Get all tenants for dropdown
        $tenants = $db->fetchAll(
            "SELECT id, tenant_name, tenant_code FROM tenants ORDER BY tenant_name",
            []
        );
        ?>

        <!-- Tenant Selector -->
        <div class="api-key-section" style="background: #e3f2fd; border-color: #2196f3;">
            <h3>Tenant Filter</h3>
            <form method="GET" style="display: flex; gap: 10px; align-items: center; margin-top: 10px;">
                <label for="tenant_id" style="font-weight: 600;">View agents for:</label>
                <select name="tenant_id" id="tenant_id" onchange="this.form.submit()" style="padding: 8px 12px; border: 1px solid #2196f3; border-radius: 5px; font-size: 1em;">
                    <option value="all" <?= $selectedTenant === 'all' ? 'selected' : '' ?>>All Tenants</option>
                    <?php foreach ($tenants as $tenant): ?>
                        <option value="<?= $tenant['id'] ?>" <?= $selectedTenant == $tenant['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($tenant['tenant_name']) ?> (<?= htmlspecialchars($tenant['tenant_code']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <?php
        // Get API key for display - filter by tenant if selected
        if ($selectedTenant === 'all') {
            $apiKey = $db->fetchOne("SELECT api_key, tenant_id FROM agent_api_keys WHERE status = 'active' LIMIT 1", []);
        } else {
            $apiKey = $db->fetchOne(
                "SELECT api_key, tenant_id FROM agent_api_keys WHERE tenant_id = ? AND status = 'active' LIMIT 1",
                [$selectedTenant]
            );
        }

        $serverUrl = "http://" . $_SERVER['HTTP_HOST'] . "/networkscan/agent_api.php";

        if ($apiKey): ?>

        <!-- Agent Installer Download Section -->
        <div style="background: white; border-radius: 10px; padding: 30px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-bottom: 20px;">
            <h2 style="color: #2c3e50; margin-bottom: 20px; border-bottom: 2px solid #ecf0f1; padding-bottom: 10px;">📥 Download Agent Installers</h2>

            <div class="api-key-section">
                <h3>Your API Key</h3>
                <div class="api-key"><?php echo htmlspecialchars($apiKey['api_key']); ?></div>
                <p style="margin-top: 10px; color: #7f8c8d; font-size: 0.9em;">
                    This API key is pre-configured in the installers below. Each installer is ready to deploy.
                </p>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-top: 25px;">
                <!-- Windows Installer -->
                <div style="background: #f8f9fa; padding: 25px; border-radius: 8px; border: 2px solid #ecf0f1;">
                    <h3 style="color: #2c3e50; margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                        🪟 Windows Agent
                    </h3>
                    <p style="color: #7f8c8d; margin-bottom: 20px; line-height: 1.6;">
                        Automated batch installer for Windows systems. Creates a scheduled task that runs hourly
                        to collect system information, network data, and security status.
                    </p>
                    <a href="create_agent_installer.php?api_key=<?php echo urlencode($apiKey['api_key']); ?>&platform=windows&server_url=<?php echo urlencode($serverUrl); ?>"
                       class="btn" style="display: block; text-align: center; background: #27ae60; text-decoration: none; padding: 12px; border-radius: 5px; color: white; font-weight: 600;">
                        ⬇ Download Windows Installer (.bat)
                    </a>
                    <p style="margin-top: 15px; font-size: 0.85em; color: #95a5a6;">
                        <strong>Requirements:</strong> Windows 7+ with PowerShell 3.0+<br>
                        <strong>Run as:</strong> Administrator
                    </p>
                </div>

                <!-- Linux Installer -->
                <div style="background: #f8f9fa; padding: 25px; border-radius: 8px; border: 2px solid #ecf0f1;">
                    <h3 style="color: #2c3e50; margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                        🐧 Linux Agent
                    </h3>
                    <p style="color: #7f8c8d; margin-bottom: 20px; line-height: 1.6;">
                        Shell script installer for Linux systems. Sets up a cron job to periodically collect
                        and report system metrics, network configuration, and security information.
                    </p>
                    <a href="#" onclick="alert('Linux installer coming soon!'); return false;"
                       class="btn" style="display: block; text-align: center; background: #95a5a6; text-decoration: none; padding: 12px; border-radius: 5px; color: white; font-weight: 600; cursor: not-allowed;">
                        ⏳ Coming Soon (.sh)
                    </a>
                    <p style="margin-top: 15px; font-size: 0.85em; color: #95a5a6;">
                        <strong>Requirements:</strong> Linux (Ubuntu, CentOS, Debian)<br>
                        <strong>Run as:</strong> Root or sudo
                    </p>
                </div>
            </div>

            <div style="background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 5px; margin-top: 25px;">
                <h3 style="margin-bottom: 15px;">📋 Windows Installation Instructions</h3>
                <ol style="margin-left: 20px; line-height: 2; color: #555;">
                    <li>Download the Windows installer using the button above</li>
                    <li>Right-click on <code style="background: white; padding: 3px 8px; border-radius: 3px; color: #e74c3c;">NetworkAgent-Setup.bat</code> and select <strong>"Run as Administrator"</strong></li>
                    <li>The installer will automatically:
                        <ul style="margin-top: 10px; margin-left: 20px;">
                            <li>Check system requirements (PowerShell 3.0+)</li>
                            <li>Create <code style="background: white; padding: 3px 8px; border-radius: 3px;">C:\ProgramData\NetworkAgent</code> directory</li>
                            <li>Install the agent script with your pre-configured API key</li>
                            <li>Create a Windows Scheduled Task (runs every hour)</li>
                            <li>Perform the first check-in automatically</li>
                        </ul>
                    </li>
                    <li>Monitor the agent status on this page - it should appear within a few minutes</li>
                </ol>

                <h3 style="margin-top: 25px; margin-bottom: 15px;">⚙️ Managing the Agent</h3>
                <ul style="margin-left: 20px; line-height: 2; color: #555;">
                    <li><strong>Check Status:</strong> <code style="background: white; padding: 3px 8px; border-radius: 3px;">schtasks /query /TN NetworkAgent</code></li>
                    <li><strong>View Logs:</strong> <code style="background: white; padding: 3px 8px; border-radius: 3px;">type C:\ProgramData\NetworkAgent\agent.log</code></li>
                    <li><strong>Start Manually:</strong> <code style="background: white; padding: 3px 8px; border-radius: 3px;">schtasks /run /TN NetworkAgent</code></li>
                    <li><strong>Uninstall:</strong> <code style="background: white; padding: 3px 8px; border-radius: 3px;">powershell -ExecutionPolicy Bypass -File "C:\ProgramData\NetworkAgent\agent.ps1" -Uninstall</code></li>
                </ul>
            </div>
        </div>
        <?php endif; ?>

        <div class="stats-grid">
            <?php
            // Build queries based on tenant selection
            if ($selectedTenant === 'all') {
                $totalAgents = $db->fetchOne("SELECT COUNT(*) as count FROM agents", []);
                $activeAgents = $db->fetchOne("SELECT COUNT(*) as count FROM agents WHERE status = 'active' AND last_checkin > DATE_SUB(NOW(), INTERVAL 2 HOUR)", []);
                $recentCheckins = $db->fetchOne("SELECT COUNT(*) as count FROM agent_checkins WHERE checkin_time > DATE_SUB(NOW(), INTERVAL 24 HOUR)", []);
                $errorCount = $db->fetchOne("SELECT COUNT(*) as count FROM agents WHERE status = 'error'", []);
            } else {
                $totalAgents = $db->fetchOne("SELECT COUNT(*) as count FROM agents WHERE tenant_id = ?", [$selectedTenant]);
                $activeAgents = $db->fetchOne("SELECT COUNT(*) as count FROM agents WHERE tenant_id = ? AND status = 'active' AND last_checkin > DATE_SUB(NOW(), INTERVAL 2 HOUR)", [$selectedTenant]);
                $recentCheckins = $db->fetchOne("SELECT COUNT(*) as count FROM agent_checkins ac JOIN agents a ON ac.agent_id = a.agent_id WHERE a.tenant_id = ? AND ac.checkin_time > DATE_SUB(NOW(), INTERVAL 24 HOUR)", [$selectedTenant]);
                $errorCount = $db->fetchOne("SELECT COUNT(*) as count FROM agents WHERE tenant_id = ? AND status = 'error'", [$selectedTenant]);
            }
            ?>
            <div class="stat-card">
                <div class="stat-value"><?php echo $totalAgents['count']; ?></div>
                <div class="stat-label">Total Agents</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $activeAgents['count']; ?></div>
                <div class="stat-label">Active (Last 2 Hours)</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $recentCheckins['count']; ?></div>
                <div class="stat-label">Check-ins (24h)</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $errorCount['count']; ?></div>
                <div class="stat-label">Errors</div>
            </div>
        </div>

        <div class="agents-table">
            <h2 style="margin-bottom: 20px;">Registered Agents</h2>
            <?php
            // Filter agents by tenant selection
            if ($selectedTenant === 'all') {
                $agents = $db->fetchAll(
                    "SELECT a.*,
                     t.tenant_name,
                     GROUP_CONCAT(ai.ip_address SEPARATOR ', ') as ip_addresses,
                     (SELECT COUNT(*) FROM agent_checkins WHERE agent_id = a.agent_id) as total_checkins,
                     TIMESTAMPDIFF(MINUTE, last_checkin, NOW()) as minutes_since_checkin
                     FROM agents a
                     LEFT JOIN agent_ips ai ON a.agent_id = ai.agent_id
                     LEFT JOIN tenants t ON a.tenant_id = t.id
                     GROUP BY a.id
                     ORDER BY a.last_checkin DESC",
                    []
                );
            } else {
                $agents = $db->fetchAll(
                    "SELECT a.*,
                     t.tenant_name,
                     GROUP_CONCAT(ai.ip_address SEPARATOR ', ') as ip_addresses,
                     (SELECT COUNT(*) FROM agent_checkins WHERE agent_id = a.agent_id) as total_checkins,
                     TIMESTAMPDIFF(MINUTE, last_checkin, NOW()) as minutes_since_checkin
                     FROM agents a
                     LEFT JOIN agent_ips ai ON a.agent_id = ai.agent_id
                     LEFT JOIN tenants t ON a.tenant_id = t.id
                     WHERE a.tenant_id = ?
                     GROUP BY a.id
                     ORDER BY a.last_checkin DESC",
                    [$selectedTenant]
                );
            }

            if (empty($agents)):
            ?>
                <div class="loading">
                    <p>No agents registered yet.</p>
                    <p style="margin-top: 10px;">Deploy the agent on target hosts to start collecting data.</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <?php if ($selectedTenant === 'all'): ?>
                            <th>Tenant</th>
                            <?php endif; ?>
                            <th>Hostname</th>
                            <th>IP Addresses</th>
                            <th>OS</th>
                            <th>Status</th>
                            <th>Last Check-in</th>
                            <th>Total Check-ins</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($agents as $agent):
                            $statusClass = 'status-' . $agent['status'];
                            if ($agent['status'] === 'active' && $agent['minutes_since_checkin'] > 120) {
                                $statusClass = 'status-inactive';
                                $statusText = 'inactive';
                            } else {
                                $statusText = $agent['status'];
                            }
                        ?>
                        <tr>
                            <?php if ($selectedTenant === 'all'): ?>
                            <td><strong><?php echo htmlspecialchars($agent['tenant_name'] ?: 'N/A'); ?></strong></td>
                            <?php endif; ?>
                            <td><strong><?php echo htmlspecialchars($agent['hostname']); ?></strong></td>
                            <td><?php echo htmlspecialchars($agent['ip_addresses'] ?: 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($agent['os_family'] ?: 'Unknown'); ?> <?php echo htmlspecialchars($agent['architecture'] ?: ''); ?></td>
                            <td><span class="status-badge <?php echo $statusClass; ?>"><?php echo strtoupper($statusText); ?></span></td>
                            <td><?php echo date('Y-m-d H:i:s', strtotime($agent['last_checkin'])); ?></td>
                            <td><?php echo $agent['total_checkins']; ?></td>
                            <td>
                                <button class="btn btn-small" onclick="viewAgent('<?php echo $agent['agent_id']; ?>')">View Details</button>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="<?php echo $selectedTenant === 'all' ? '8' : '7'; ?>">
                                <div id="details-<?php echo $agent['agent_id']; ?>" class="agent-details">
                                    <div class="loading">Loading agent details...</div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function viewAgent(agentId) {
            const detailsDiv = document.getElementById('details-' + agentId);

            if (detailsDiv.classList.contains('show')) {
                detailsDiv.classList.remove('show');
                return;
            }

            // Hide all other details
            document.querySelectorAll('.agent-details').forEach(div => {
                div.classList.remove('show');
            });

            // Show this one
            detailsDiv.classList.add('show');

            // Fetch agent details via AJAX
            fetch('get_agent_details.php?agent_id=' + encodeURIComponent(agentId))
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayAgentDetails(agentId, data.agent);
                    } else {
                        detailsDiv.innerHTML = '<p>Error loading agent details: ' + data.error + '</p>';
                    }
                })
                .catch(error => {
                    detailsDiv.innerHTML = '<p>Error loading agent details</p>';
                });
        }

        function displayAgentDetails(agentId, agent) {
            const detailsDiv = document.getElementById('details-' + agentId);

            let html = '<h3>Agent Details</h3>';

            // System info
            html += '<div class="detail-section">';
            html += '<div class="detail-label">System Information:</div>';
            html += '<p>OS: ' + (agent.system.os || 'N/A') + ' ' + (agent.system.os_version || '') + '</p>';
            html += '<p>Agent ID: <code>' + agent.agent_id + '</code></p>';
            html += '<p>First Seen: ' + agent.first_seen + '</p>';
            html += '</div>';

            // Network info
            if (agent.network) {
                html += '<div class="detail-section">';
                html += '<div class="detail-label">Latest Network Scan:</div>';
                html += '<p>Open Ports: ' + (agent.network.open_ports ? agent.network.open_ports.join(', ') : 'None') + '</p>';
                html += '<p>DNS Servers: ' + (agent.network.dns_servers ? agent.network.dns_servers.join(', ') : 'N/A') + '</p>';
                html += '<p>Collected: ' + agent.network.collected_at + '</p>';
                html += '</div>';
            }

            // Security info
            if (agent.security) {
                html += '<div class="detail-section">';
                html += '<div class="detail-label">Security Status:</div>';
                html += '<p>Firewall: ' + (agent.security.firewall_status || 'Unknown') + '</p>';
                html += '<p>Antivirus: ' + (agent.security.antivirus_status || 'Unknown') + '</p>';
                html += '<p>Windows Defender: ' + (agent.security.windows_defender || 'N/A') + '</p>';
                html += '<p>Collected: ' + agent.security.collected_at + '</p>';
                html += '</div>';
            }

            // Recent check-ins
            if (agent.recent_checkins && agent.recent_checkins.length > 0) {
                html += '<div class="detail-section">';
                html += '<div class="detail-label">Recent Check-ins:</div>';
                html += '<ul>';
                agent.recent_checkins.forEach(checkin => {
                    html += '<li>' + checkin.checkin_time + ' - ' + (checkin.success ? 'Success' : 'Failed: ' + checkin.error_message) + '</li>';
                });
                html += '</ul>';
                html += '</div>';
            }

            detailsDiv.innerHTML = html;
        }

        // Auto-refresh every 30 seconds
        setInterval(() => {
            location.reload();
        }, 30000);
    </script>
</body>
</html>
