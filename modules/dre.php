<?php
/**
 * Remote Support (DRE)
 * Remote IT support and system administration capabilities
 */

require_once __DIR__ . '/../classes/Database.php';
$db = Database::getInstance();

$total_hosts = $db->fetchOne("SELECT COUNT(*) as count FROM hosts")['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Remote Support | DRE</title>
    <link rel="stylesheet" href="../admin/style.css">
    <style>
        body { background: #f5f5f5; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .header-bar {
            background: white;
            padding: 20px 30px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .back-btn {
            background: #667eea;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
        }
        .coming-soon {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin: 40px 0;
        }
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .feature-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-bar">
            <div>
                <h1 style="margin: 0; color: #667eea;">🖥️ Remote Support</h1>
                <p style="margin: 5px 0 0 0; color: #666;">Remote IT support and system administration capabilities</p>
            </div>
            <a href="../index.php" class="back-btn">← Back to Dashboard</a>
        </div>

        <div class="coming-soon">
            <div style="font-size: 120px; margin-bottom: 20px;">🔧</div>
            <h2 style="color: #667eea; font-size: 36px; margin-bottom: 10px;">Coming Soon</h2>
            <p style="color: #666; font-size: 18px; max-width: 600px; margin: 0 auto;">
                Remote Support is currently under development. This module will provide secure remote access,
                screen sharing, and system administration tools for efficient IT support.
            </p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">💻</div>
                <div class="stat-info">
                    <div class="stat-number"><?= $total_hosts ?></div>
                    <div class="stat-label">Available Endpoints</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">🔗</div>
                <div class="stat-info">
                    <div class="stat-number">0</div>
                    <div class="stat-label">Active Sessions</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">📞</div>
                <div class="stat-info">
                    <div class="stat-number">0</div>
                    <div class="stat-label">Support Requests</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">⏱️</div>
                <div class="stat-info">
                    <div class="stat-number">0 min</div>
                    <div class="stat-label">Avg Resolution Time</div>
                </div>
            </div>
        </div>

        <div class="card">
            <h2>Planned Features</h2>
            <div class="feature-grid">
                <div class="feature-card">
                    <h3>🖥️ Remote Desktop</h3>
                    <p style="color: #666;">Secure remote desktop access with high-quality screen sharing and control.</p>
                </div>

                <div class="feature-card">
                    <h3>📁 File Transfer</h3>
                    <p style="color: #666;">Secure file transfer capabilities between technician and end user.</p>
                </div>

                <div class="feature-card">
                    <h3>💬 Chat Support</h3>
                    <p style="color: #666;">Built-in chat and messaging for real-time communication during support sessions.</p>
                </div>

                <div class="feature-card">
                    <h3>🔐 Secure Access</h3>
                    <p style="color: #666;">End-to-end encryption and multi-factor authentication for secure connections.</p>
                </div>

                <div class="feature-card">
                    <h3>📊 Session Recording</h3>
                    <p style="color: #666;">Record support sessions for training, quality assurance, and compliance.</p>
                </div>

                <div class="feature-card">
                    <h3>🎫 Ticket Integration</h3>
                    <p style="color: #666;">Integration with helpdesk ticketing systems for streamlined workflows.</p>
                </div>
            </div>
        </div>

        <div class="card">
            <h2>Capabilities (Planned)</h2>
            <ul style="line-height: 2;">
                <li>🔄 Unattended remote access</li>
                <li>🔄 Multi-platform support (Windows, Mac, Linux)</li>
                <li>🔄 Mobile device support (iOS, Android)</li>
                <li>🔄 Command line tools and scripting</li>
                <li>🔄 System diagnostics and troubleshooting tools</li>
                <li>🔄 Software deployment and updates</li>
                <li>🔄 Multi-monitor support</li>
                <li>🔄 Session transfer between technicians</li>
                <li>🔄 Wake-on-LAN capabilities</li>
                <li>🔄 Audit trails and reporting</li>
            </ul>
        </div>
    </div>
</body>
</html>
