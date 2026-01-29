<?php
/**
 * IT Service Desk
 * IT service management, ticketing, and helpdesk solutions
 */

require_once __DIR__ . '/../classes/Database.php';
$db = Database::getInstance();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT Service Desk</title>
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
                <h1 style="margin: 0; color: #667eea;">🎫 IT Service Desk</h1>
                <p style="margin: 5px 0 0 0; color: #666;">IT service management, ticketing, and helpdesk solutions</p>
            </div>
            <a href="../index.php" class="back-btn">← Back to Dashboard</a>
        </div>

        <div class="coming-soon">
            <div style="font-size: 120px; margin-bottom: 20px;">🎫</div>
            <h2 style="color: #667eea; font-size: 36px; margin-bottom: 10px;">Coming Soon</h2>
            <p style="color: #666; font-size: 18px; max-width: 600px; margin: 0 auto;">
                IT Service Desk is currently under development. This module will provide comprehensive ITSM
                capabilities including incident management, problem tracking, and knowledge base functionality.
            </p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">🎫</div>
                <div class="stat-info">
                    <div class="stat-number">0</div>
                    <div class="stat-label">Open Tickets</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-info">
                    <div class="stat-number">0</div>
                    <div class="stat-label">Resolved Today</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">⏱️</div>
                <div class="stat-info">
                    <div class="stat-number">0 hrs</div>
                    <div class="stat-label">Avg Response Time</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">😊</div>
                <div class="stat-info">
                    <div class="stat-number">0%</div>
                    <div class="stat-label">Customer Satisfaction</div>
                </div>
            </div>
        </div>

        <div class="card">
            <h2>Planned Features</h2>
            <div class="feature-grid">
                <div class="feature-card">
                    <h3>🎫 Incident Management</h3>
                    <p style="color: #666;">Track and manage IT incidents from submission to resolution with SLA monitoring.</p>
                </div>

                <div class="feature-card">
                    <h3>🔧 Problem Management</h3>
                    <p style="color: #666;">Identify root causes and prevent recurring incidents through problem tracking.</p>
                </div>

                <div class="feature-card">
                    <h3>📋 Change Management</h3>
                    <p style="color: #666;">Manage IT changes with approval workflows and impact assessment.</p>
                </div>

                <div class="feature-card">
                    <h3>📚 Knowledge Base</h3>
                    <p style="color: #666;">Self-service knowledge base with articles, FAQs, and troubleshooting guides.</p>
                </div>

                <div class="feature-card">
                    <h3>📦 Asset Management</h3>
                    <p style="color: #666;">Track hardware and software assets with lifecycle management.</p>
                </div>

                <div class="feature-card">
                    <h3>📊 Reporting & Analytics</h3>
                    <p style="color: #666;">Comprehensive dashboards and reports for performance monitoring.</p>
                </div>
            </div>
        </div>

        <div class="card">
            <h2>ITIL/ITSM Capabilities (Planned)</h2>
            <ul style="line-height: 2;">
                <li>🔄 Multi-channel support (Email, Web Portal, Phone, Chat)</li>
                <li>🔄 SLA management and tracking</li>
                <li>🔄 Automated ticket routing and assignment</li>
                <li>🔄 Service catalog and request fulfillment</li>
                <li>🔄 Workflow automation and escalation</li>
                <li>🔄 Custom forms and ticket fields</li>
                <li>🔄 Integration with monitoring tools</li>
                <li>🔄 Customer satisfaction surveys</li>
                <li>🔄 Agent productivity tracking</li>
                <li>🔄 Mobile app for technicians</li>
            </ul>
        </div>
    </div>
</body>
</html>
