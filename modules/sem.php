<?php
/**
 * Security Event Manager (SEM)
 * Real-time threat detection, SIEM capabilities, and incident response
 */

require_once __DIR__ . '/../classes/Database.php';
$db = Database::getInstance();

// Get recent scans as security events
$recent_scans = $db->fetchAll("SELECT * FROM scans ORDER BY started_at DESC LIMIT 10");

// Get vulnerability statistics
$critical_vulns = $db->fetchOne("SELECT COUNT(*) as count FROM vulnerabilities WHERE severity = 'critical'")['count'];
$high_vulns = $db->fetchOne("SELECT COUNT(*) as count FROM vulnerabilities WHERE severity = 'high'")['count'];
$medium_vulns = $db->fetchOne("SELECT COUNT(*) as count FROM vulnerabilities WHERE severity = 'medium'")['count'];
$low_vulns = $db->fetchOne("SELECT COUNT(*) as count FROM vulnerabilities WHERE severity = 'low'")['count'];

// Get total security events (scans)
$total_events = count($recent_scans);

// Calculate threat level
$threat_level = 'Low';
$threat_color = '#4CAF50';
if ($critical_vulns > 5) {
    $threat_level = 'Critical';
    $threat_color = '#f44336';
} elseif ($critical_vulns > 0 || $high_vulns > 10) {
    $threat_level = 'High';
    $threat_color = '#ff9800';
} elseif ($high_vulns > 0 || $medium_vulns > 20) {
    $threat_level = 'Medium';
    $threat_color = '#2196F3';
}

// Get top vulnerabilities (joined through scan_results)
$top_vulns = $db->fetchAll("SELECT v.*, h.ip_address, h.hostname,
    COALESCE(sr.detected_at, v.created_at) as discovered_at
    FROM vulnerabilities v
    LEFT JOIN scan_results sr ON v.id = sr.vulnerability_id
    LEFT JOIN hosts h ON sr.host_id = h.id
    ORDER BY
        FIELD(v.severity, 'critical', 'high', 'medium', 'low', 'info'),
        COALESCE(sr.detected_at, v.created_at) DESC
    LIMIT 20");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Event Manager | SEM</title>
    <link rel="stylesheet" href="../admin/style.css">
    <style>
        body { background: #f5f5f5; padding: 20px; }
        .container { max-width: 1400px; margin: 0 auto; }
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
        .threat-indicator {
            text-align: center;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .threat-level {
            font-size: 48px;
            font-weight: bold;
            margin: 10px 0;
        }
        .vuln-table {
            width: 100%;
            border-collapse: collapse;
        }
        .vuln-table th {
            background: #667eea;
            color: white;
            padding: 12px;
            text-align: left;
        }
        .vuln-table td {
            padding: 12px;
            border-bottom: 1px solid #e0e0e0;
        }
        .vuln-table tr:hover {
            background: #f5f5f5;
        }
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        .timeline-item {
            position: relative;
            padding: 15px;
            background: white;
            border-radius: 8px;
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -22px;
            top: 20px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #667eea;
            border: 3px solid white;
            box-shadow: 0 0 0 2px #667eea;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-bar">
            <div>
                <h1 style="margin: 0; color: #667eea;">🔒 Security Event Manager</h1>
                <p style="margin: 5px 0 0 0; color: #666;">Real-time threat detection, SIEM capabilities, and incident response</p>
            </div>
            <a href="../index.php" class="back-btn">← Back to Dashboard</a>
        </div>

        <div class="threat-indicator">
            <div style="font-size: 64px;">🛡️</div>
            <div class="threat-level" style="color: <?= $threat_color ?>;"><?= $threat_level ?></div>
            <div style="font-size: 18px; color: #666;">Current Threat Level</div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">🔴</div>
                <div class="stat-info">
                    <div class="stat-number" style="color: #f44336;"><?= $critical_vulns ?></div>
                    <div class="stat-label">Critical Vulnerabilities</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">🟠</div>
                <div class="stat-info">
                    <div class="stat-number" style="color: #ff9800;"><?= $high_vulns ?></div>
                    <div class="stat-label">High Severity</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">🟡</div>
                <div class="stat-info">
                    <div class="stat-number" style="color: #2196F3;"><?= $medium_vulns ?></div>
                    <div class="stat-label">Medium Severity</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">🟢</div>
                <div class="stat-info">
                    <div class="stat-number" style="color: #4CAF50;"><?= $low_vulns ?></div>
                    <div class="stat-label">Low Severity</div>
                </div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-top: 20px;">
            <div class="card">
                <h2>Recent Vulnerabilities</h2>
                <table class="vuln-table">
                    <thead>
                        <tr>
                            <th>Severity</th>
                            <th>Host</th>
                            <th>Vulnerability</th>
                            <th>CVSS Score</th>
                            <th>Discovered</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_vulns as $vuln):
                            $severityColors = [
                                'critical' => 'critical',
                                'high' => 'high',
                                'medium' => 'medium',
                                'low' => 'low'
                            ];
                        ?>
                        <tr>
                            <td>
                                <span class="badge badge-<?= $severityColors[$vuln['severity']] ?>">
                                    <?= strtoupper($vuln['severity']) ?>
                                </span>
                            </td>
                            <td>
                                <?= htmlspecialchars($vuln['hostname'] ?? $vuln['ip_address'] ?? 'Unknown') ?>
                            </td>
                            <td><?= htmlspecialchars($vuln['title']) ?></td>
                            <td style="font-weight: bold;">
                                <?= number_format($vuln['cvss_score'], 1) ?>
                            </td>
                            <td><?= date('Y-m-d H:i', strtotime($vuln['discovered_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="card">
                <h2>Security Events Timeline</h2>
                <div class="timeline">
                    <?php foreach ($recent_scans as $scan):
                        $statusIcons = [
                            'completed' => '✅',
                            'running' => '🔄',
                            'failed' => '❌',
                            'pending' => '⏳'
                        ];
                        $icon = $statusIcons[$scan['status']] ?? '📊';
                    ?>
                    <div class="timeline-item">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <div style="font-weight: bold; margin-bottom: 5px;">
                                    <?= $icon ?> <?= ucfirst($scan['scan_type']) ?> Scan
                                </div>
                                <div style="font-size: 12px; color: #666;">
                                    Target: <?= htmlspecialchars($scan['target']) ?>
                                </div>
                            </div>
                            <span class="badge badge-<?= $scan['status'] === 'completed' ? 'low' : 'in-progress' ?>">
                                <?= strtoupper($scan['status']) ?>
                            </span>
                        </div>
                        <div style="font-size: 11px; color: #999; margin-top: 5px;">
                            <?= date('Y-m-d H:i:s', strtotime($scan['started_at'])) ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="card">
            <h2>Features</h2>
            <ul style="line-height: 2;">
                <li>✅ Real-time vulnerability detection and tracking</li>
                <li>✅ CVSS-based severity scoring</li>
                <li>✅ Security event timeline</li>
                <li>✅ Threat level assessment</li>
                <li>✅ Comprehensive vulnerability reporting</li>
                <li>🔄 Log aggregation and analysis (coming soon)</li>
                <li>🔄 Correlation engine for threat detection (coming soon)</li>
                <li>🔄 Automated incident response (coming soon)</li>
                <li>🔄 Integration with threat intelligence feeds (coming soon)</li>
                <li>🔄 Compliance reporting (SIEM) (coming soon)</li>
                <li>🔄 User behavior analytics (UBA) (coming soon)</li>
            </ul>
        </div>
    </div>
</body>
</html>
