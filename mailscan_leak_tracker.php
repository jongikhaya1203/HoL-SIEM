<?php
require_once 'mailscan_config.php';
require_once 'EmailLeakTracker.php';

$db = getDBConnection();
$tracker = new EmailLeakTracker();

// Get statistics
$stats = $tracker->getTrackingStats();

// Get recent leak incidents
$incidents = $tracker->getLeakIncidents(20);

// Get top leakers
$topLeakers = $tracker->getTopLeakers(10);

// Get chain details if requested
$chainDetails = null;
$chainId = $_GET['chain_id'] ?? null;
if ($chainId) {
    $chainDetails = $tracker->getEmailChain($chainId);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Leak Tracker - DLP System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f7fa;
            color: #2c3e50;
        }
        .header {
            background: linear-gradient(135deg, #e91e63 0%, #c2185b 100%);
            color: white;
            padding: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }
        h1 {
            font-size: 28px;
            margin-bottom: 5px;
        }
        .subtitle {
            opacity: 0.9;
            font-size: 14px;
        }
        .nav {
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        .nav-container {
            display: flex;
            gap: 0;
        }
        .nav-link {
            padding: 15px 25px;
            text-decoration: none;
            color: #5a6c7d;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }
        .nav-link:hover, .nav-link.active {
            color: #e91e63;
            border-bottom-color: #e91e63;
            background: #f8f9fa;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border-left: 4px solid #e91e63;
        }
        .stat-value {
            font-size: 36px;
            font-weight: bold;
            color: #e91e63;
            margin: 10px 0;
        }
        .stat-label {
            color: #5a6c7d;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .card-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
            color: #5a6c7d;
            text-transform: uppercase;
            border-bottom: 2px solid #e9ecef;
        }
        td {
            padding: 15px 12px;
            border-bottom: 1px solid #e9ecef;
            font-size: 14px;
        }
        tr:hover {
            background: #f8f9fa;
        }
        .severity-badge {
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            color: white;
            display: inline-block;
        }
        .severity-critical { background: #dc3545; }
        .severity-high { background: #fd7e14; }
        .severity-medium { background: #ffc107; color: #333; }
        .severity-low { background: #17a2b8; }
        .status-badge {
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
        }
        .status-new { background: #fff3cd; color: #856404; }
        .status-investigating { background: #cfe2ff; color: #084298; }
        .status-confirmed { background: #f8d7da; color: #842029; }
        .status-resolved { background: #d1e7dd; color: #0f5132; }
        .view-chain {
            color: #e91e63;
            text-decoration: none;
            font-weight: 600;
            font-size: 13px;
        }
        .view-chain:hover {
            text-decoration: underline;
        }
        /* Chain visualization styles */
        .chain-viz {
            background: white;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .chain-path {
            display: flex;
            flex-direction: column;
            gap: 20px;
            margin-top: 20px;
        }
        .hop-item {
            position: relative;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid;
        }
        .hop-item.internal { border-left-color: #28a745; }
        .hop-item.external { border-left-color: #ffc107; }
        .hop-item.unauthorized { border-left-color: #dc3545; }
        .hop-item:not(:last-child)::after {
            content: '⬇';
            position: absolute;
            bottom: -25px;
            left: 20px;
            font-size: 20px;
            color: #dc3545;
        }
        .hop-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 10px;
        }
        .hop-number {
            background: #e91e63;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
        }
        .hop-details {
            flex: 1;
            margin-left: 15px;
        }
        .hop-address {
            font-weight: 600;
            font-size: 15px;
            color: #2c3e50;
        }
        .hop-meta {
            font-size: 12px;
            color: #6c757d;
            margin-top: 5px;
        }
        .risk-indicator {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }
        .risk-low { background: #d1e7dd; color: #0f5132; }
        .risk-medium { background: #fff3cd; color: #856404; }
        .risk-high { background: #f8d7da; color: #842029; }
        .leak-alert {
            background: #fff5f5;
            border: 2px solid #dc3545;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .leak-alert-title {
            color: #dc3545;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <h1>🔍 Email Leak Tracker</h1>
            <div class="subtitle">Track email forwarding chains and identify unauthorized data leaks</div>
        </div>
    </div>

    <div class="nav">
        <div class="container nav-container">
            <a href="mailscan_dashboard.php" class="nav-link">Dashboard</a>
            <a href="mailscan_rules.php" class="nav-link">Detection Rules</a>
            <a href="mailscan_scan.php" class="nav-link">Scan Email</a>
            <a href="mailscan_leak_tracker.php" class="nav-link active">Leak Tracker</a>
        </div>
    </div>

    <div class="container">
        <?php if ($chainDetails): ?>
            <!-- Chain Visualization -->
            <div class="chain-viz">
                <a href="mailscan_leak_tracker.php" style="color: #e91e63; text-decoration: none; margin-bottom: 20px; display: inline-block;">← Back to Incidents</a>

                <h2 style="font-size: 24px; color: #2c3e50; margin-bottom: 10px;">Email Forwarding Chain</h2>
                <p style="color: #6c757d; margin-bottom: 20px;">Chain ID: <strong><?= h($chainId) ?></strong></p>

                <?php
                $lastHop = end($chainDetails);
                if ($lastHop['is_unauthorized']):
                ?>
                <div class="leak-alert">
                    <div class="leak-alert-title">⚠️ UNAUTHORIZED LEAK DETECTED</div>
                    <p>This email was forwarded to an unauthorized recipient: <strong><?= h($lastHop['to_address']) ?></strong></p>
                    <p style="margin-top: 10px;">Risk Score: <strong><?= $lastHop['leak_risk_score'] ?></strong> | Classification: <strong><?= h($lastHop['to_domain_class'] ?? 'Unknown') ?></strong></p>
                </div>
                <?php endif; ?>

                <div class="chain-path">
                    <?php foreach ($chainDetails as $hop): ?>
                        <div class="hop-item <?= $hop['is_unauthorized'] ? 'unauthorized' : ($hop['is_external'] ? 'external' : 'internal') ?>">
                            <div class="hop-header">
                                <div style="display: flex; align-items: start; flex: 1;">
                                    <div class="hop-number"><?= $hop['hop_number'] ?></div>
                                    <div class="hop-details">
                                        <div style="margin-bottom: 8px;">
                                            <strong>From:</strong> <span class="hop-address"><?= h($hop['from_address']) ?></span>
                                            <?php if ($hop['from_name']): ?>
                                                <span style="color: #6c757d;">(<?= h($hop['from_name']) ?>)</span>
                                            <?php endif; ?>
                                        </div>
                                        <div style="margin-bottom: 8px;">
                                            <strong>To:</strong> <span class="hop-address"><?= h($hop['to_address']) ?></span>
                                            <?php if ($hop['to_name']): ?>
                                                <span style="color: #6c757d;">(<?= h($hop['to_name']) ?>)</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="hop-meta">
                                            <strong>Type:</strong> <?= h(str_replace('_', ' ', ucfirst($hop['forward_type']))) ?>
                                            | <strong>Date:</strong> <?= date('M j, Y g:i A', strtotime($hop['forwarded_date'])) ?>
                                            | <strong>Classification:</strong> <?= h($hop['to_domain_class'] ?? 'Unknown') ?>
                                        </div>
                                        <?php if ($hop['subject']): ?>
                                            <div class="hop-meta" style="margin-top: 5px;">
                                                <strong>Subject:</strong> <?= h($hop['subject']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div>
                                    <?php
                                    $riskClass = $hop['leak_risk_score'] >= 70 ? 'high' : ($hop['leak_risk_score'] >= 40 ? 'medium' : 'low');
                                    ?>
                                    <div class="risk-indicator risk-<?= $riskClass ?>">
                                        Risk: <?= $hop['leak_risk_score'] ?>
                                    </div>
                                    <?php if ($hop['is_external']): ?>
                                        <div style="margin-top: 5px; font-size: 11px; color: #fd7e14; font-weight: 600;">
                                            🌐 EXTERNAL
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($hop['is_unauthorized']): ?>
                                        <div style="margin-top: 5px; font-size: 11px; color: #dc3545; font-weight: 600;">
                                            🚫 UNAUTHORIZED
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

        <?php else: ?>
            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Total Chains Tracked</div>
                    <div class="stat-value"><?= number_format($stats['total_chains']) ?></div>
                </div>
                <div class="stat-card" style="border-left-color: #dc3545;">
                    <div class="stat-label">Leak Incidents</div>
                    <div class="stat-value" style="color: #dc3545;"><?= number_format($stats['total_incidents']) ?></div>
                </div>
                <div class="stat-card" style="border-left-color: #fd7e14;">
                    <div class="stat-label">Critical Incidents</div>
                    <div class="stat-value" style="color: #fd7e14;"><?= number_format($stats['critical_incidents']) ?></div>
                </div>
                <div class="stat-card" style="border-left-color: #ffc107;">
                    <div class="stat-label">External Forwards</div>
                    <div class="stat-value" style="color: #ffc107;"><?= number_format($stats['external_forwards']) ?></div>
                </div>
                <div class="stat-card" style="border-left-color: #000;">
                    <div class="stat-label">Unauthorized</div>
                    <div class="stat-value" style="color: #000;"><?= number_format($stats['unauthorized_forwards']) ?></div>
                </div>
            </div>

            <!-- Recent Leak Incidents -->
            <div class="card">
                <div class="card-title">🚨 Recent Leak Incidents</div>
                <?php if (empty($incidents)): ?>
                    <p style="text-align: center; color: #6c757d; padding: 40px;">No leak incidents found. Generate sample data to see tracking in action.</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Incident ID</th>
                                <th>Source</th>
                                <th>Destination</th>
                                <th>Hops</th>
                                <th>Type</th>
                                <th>Severity</th>
                                <th>Status</th>
                                <th>Detected</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($incidents as $incident): ?>
                                <tr>
                                    <td><code><?= h($incident['incident_id']) ?></code></td>
                                    <td><?= h($incident['leak_source']) ?></td>
                                    <td><strong><?= h($incident['leak_destination']) ?></strong></td>
                                    <td><?= $incident['total_hops'] ?></td>
                                    <td><?= h(str_replace('_', ' ', ucfirst($incident['incident_type']))) ?></td>
                                    <td>
                                        <span class="severity-badge severity-<?= $incident['severity'] ?>">
                                            <?= strtoupper($incident['severity']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?= $incident['investigation_status'] ?>">
                                            <?= str_replace('_', ' ', ucfirst($incident['investigation_status'])) ?>
                                        </span>
                                    </td>
                                    <td><?= date('M j, Y', strtotime($incident['detected_date'])) ?></td>
                                    <td>
                                        <a href="?chain_id=<?= urlencode($incident['chain_id']) ?>" class="view-chain">
                                            View Chain →
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <!-- Top Leakers -->
            <?php if (!empty($topLeakers)): ?>
                <div class="card">
                    <div class="card-title">👥 Top Email Leakers</div>
                    <table>
                        <thead>
                            <tr>
                                <th>Email Address</th>
                                <th>Name</th>
                                <th>Domain</th>
                                <th>Emails Forwarded</th>
                                <th>Leak Incidents</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($topLeakers as $leaker): ?>
                                <tr>
                                    <td><strong><?= h($leaker['email_address']) ?></strong></td>
                                    <td><?= h($leaker['recipient_name'] ?? 'Unknown') ?></td>
                                    <td><code><?= h($leaker['domain']) ?></code></td>
                                    <td><?= number_format($leaker['total_emails_forwarded']) ?></td>
                                    <td style="color: #dc3545; font-weight: 600;"><?= number_format($leaker['leak_incidents']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>
