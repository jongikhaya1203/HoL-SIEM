<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($scan['scan_name'] ?? 'Security Assessment Report') ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f5f5f5;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 40px;
        }

        .header {
            border-bottom: 4px solid #d32f2f;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        h1 {
            color: #d32f2f;
            font-size: 32px;
            margin-bottom: 10px;
        }

        h2 {
            color: #1976d2;
            font-size: 24px;
            margin: 30px 0 15px 0;
            border-bottom: 2px solid #1976d2;
            padding-bottom: 8px;
        }

        h3 {
            color: #424242;
            font-size: 18px;
            margin: 20px 0 10px 0;
        }

        .meta-info {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }

        .meta-info p {
            margin: 5px 0;
        }

        .severity-critical {
            color: #d32f2f;
            font-weight: bold;
        }

        .severity-high {
            color: #ff6f00;
            font-weight: bold;
        }

        .severity-medium {
            color: #f9a825;
            font-weight: bold;
        }

        .severity-low {
            color: #388e3c;
        }

        .severity-info {
            color: #1976d2;
        }

        .risk-score {
            font-size: 48px;
            font-weight: bold;
            text-align: center;
            padding: 20px;
            margin: 20px 0;
            border-radius: 10px;
        }

        .risk-critical {
            background: #ffebee;
            color: #d32f2f;
        }

        .risk-high {
            background: #fff3e0;
            color: #ff6f00;
        }

        .risk-medium {
            background: #fffde7;
            color: #f9a825;
        }

        .risk-low {
            background: #e8f5e9;
            color: #388e3c;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        th {
            background: #1976d2;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: 600;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #e0e0e0;
        }

        tr:hover {
            background: #f5f5f5;
        }

        .vulnerability-card {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            padding: 20px;
            margin: 15px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .vulnerability-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
            margin-right: 8px;
        }

        .badge-critical {
            background: #d32f2f;
            color: white;
        }

        .badge-high {
            background: #ff6f00;
            color: white;
        }

        .badge-medium {
            background: #f9a825;
            color: white;
        }

        .badge-low {
            background: #388e3c;
            color: white;
        }

        .recommendation {
            background: #e3f2fd;
            border-left: 4px solid #1976d2;
            padding: 15px;
            margin: 15px 0;
            border-radius: 3px;
        }

        .chart-container {
            display: flex;
            justify-content: space-around;
            margin: 30px 0;
            flex-wrap: wrap;
        }

        .chart-box {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            margin: 10px;
            flex: 1;
            min-width: 250px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .stat-number {
            font-size: 36px;
            font-weight: bold;
            color: #1976d2;
        }

        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 2px solid #e0e0e0;
            text-align: center;
            color: #757575;
            font-size: 14px;
        }

        @media print {
            body {
                background: white;
            }
            .container {
                padding: 20px;
            }
            .vulnerability-card {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>Network Security Assessment Report</h1>
            <div class="meta-info">
                <p><strong>Scan Name:</strong> <?= htmlspecialchars($scan['scan_name']) ?></p>
                <p><strong>Target Range:</strong> <?= htmlspecialchars($scan['target_range']) ?></p>
                <p><strong>Scan Date:</strong> <?= htmlspecialchars($scan['started_at']) ?></p>
                <p><strong>Completion:</strong> <?= htmlspecialchars($scan['completed_at']) ?></p>
                <p><strong>Scan Type:</strong> <?= strtoupper(htmlspecialchars($scan['scan_type'])) ?></p>
            </div>
        </div>

        <!-- Executive Summary -->
        <?php if (isset($content['executive'])): ?>
        <h2>Executive Summary</h2>
        <div class="chart-container">
            <div class="chart-box">
                <h3>Total Hosts Scanned</h3>
                <div class="stat-number"><?= $scan['total_hosts'] ?></div>
            </div>
            <div class="chart-box">
                <h3>Vulnerabilities Found</h3>
                <div class="stat-number severity-critical"><?= $scan['total_vulnerabilities'] ?></div>
            </div>
            <div class="chart-box">
                <h3>Overall Risk Score</h3>
                <div class="stat-number"><?= number_format($content['executive']['risk_overview']['risk_score'], 1) ?>/10</div>
            </div>
        </div>

        <h3>Severity Distribution</h3>
        <table>
            <thead>
                <tr>
                    <th>Severity</th>
                    <th>Count</th>
                    <th>Percentage</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><span class="severity-critical">Critical</span></td>
                    <td><?= $scan['critical_count'] ?></td>
                    <td><?= $scan['total_vulnerabilities'] > 0 ? round(($scan['critical_count']/$scan['total_vulnerabilities'])*100, 1) : 0 ?>%</td>
                </tr>
                <tr>
                    <td><span class="severity-high">High</span></td>
                    <td><?= $scan['high_count'] ?></td>
                    <td><?= $scan['total_vulnerabilities'] > 0 ? round(($scan['high_count']/$scan['total_vulnerabilities'])*100, 1) : 0 ?>%</td>
                </tr>
                <tr>
                    <td><span class="severity-medium">Medium</span></td>
                    <td><?= $scan['medium_count'] ?></td>
                    <td><?= $scan['total_vulnerabilities'] > 0 ? round(($scan['medium_count']/$scan['total_vulnerabilities'])*100, 1) : 0 ?>%</td>
                </tr>
                <tr>
                    <td><span class="severity-low">Low</span></td>
                    <td><?= $scan['low_count'] ?></td>
                    <td><?= $scan['total_vulnerabilities'] > 0 ? round(($scan['low_count']/$scan['total_vulnerabilities'])*100, 1) : 0 ?>%</td>
                </tr>
            </tbody>
        </table>

        <h3>Executive Summary</h3>
        <p style="white-space: pre-line; margin: 15px 0;"><?= htmlspecialchars($content['executive']['executive_summary']) ?></p>

        <h3>Top Vulnerabilities</h3>
        <?php foreach ($content['executive']['top_vulnerabilities'] as $vuln): ?>
        <div class="vulnerability-card">
            <div class="vulnerability-title">
                <span class="badge badge-<?= $vuln['severity'] ?>"><?= strtoupper($vuln['severity']) ?></span>
                <?= htmlspecialchars($vuln['title']) ?>
                <?php if ($vuln['cve_id']): ?>
                    <span style="color: #757575;">(<?= htmlspecialchars($vuln['cve_id']) ?>)</span>
                <?php endif; ?>
            </div>
            <p><strong>CVSS Score:</strong> <?= number_format($vuln['cvss_score'], 1) ?>/10.0</p>
            <p><strong>Affected Hosts:</strong> <?= $vuln['affected_hosts'] ?></p>
            <p><?= htmlspecialchars($vuln['description']) ?></p>
        </div>
        <?php endforeach; ?>

        <h3>Immediate Actions Required</h3>
        <?php foreach ($content['executive']['recommendations'] as $rec): ?>
        <div class="recommendation">
            <h4><?= htmlspecialchars($rec['title']) ?></h4>
            <p><strong>Priority:</strong> <?= htmlspecialchars($rec['priority']) ?></p>
            <p><?= htmlspecialchars($rec['description']) ?></p>
            <p><strong>Effort:</strong> <?= htmlspecialchars($rec['effort']) ?> | <strong>Impact:</strong> <?= htmlspecialchars($rec['impact']) ?></p>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>

        <!-- Technical Details -->
        <?php if (isset($content['technical'])): ?>
        <h2>Technical Details</h2>

        <h3>Network Topology</h3>
        <p><strong>Total Assets:</strong> <?= $content['technical']['network_topology']['total_hosts'] ?></p>

        <h3>Services Detected</h3>
        <table>
            <thead>
                <tr>
                    <th>Service</th>
                    <th>Instances</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($content['technical']['network_topology']['services_detected'] as $service => $count): ?>
                <tr>
                    <td><?= htmlspecialchars($service) ?></td>
                    <td><?= $count ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h3>Host Details</h3>
        <?php foreach ($content['technical']['hosts'] as $host): ?>
        <div class="vulnerability-card">
            <h4>Host: <?= htmlspecialchars($host['ip_address']) ?></h4>
            <?php if ($host['hostname']): ?>
                <p><strong>Hostname:</strong> <?= htmlspecialchars($host['hostname']) ?></p>
            <?php endif; ?>
            <p><strong>Risk Score:</strong> <?= number_format($host['risk_score'], 2) ?>/10</p>
            <p><strong>Open Ports:</strong> <?= $host['open_ports'] ?></p>
            <p><strong>Vulnerabilities:</strong> <?= count($host['vulnerabilities']) ?></p>

            <?php if (!empty($host['ports'])): ?>
            <h5>Open Ports:</h5>
            <ul>
                <?php foreach ($host['ports'] as $port): ?>
                <li>
                    Port <?= $port['port_number'] ?>/<?= $port['protocol'] ?>:
                    <?= htmlspecialchars($port['service_name'] ?? 'unknown') ?>
                    <?php if ($port['service_version']): ?>
                        v<?= htmlspecialchars($port['service_version']) ?>
                    <?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>

        <!-- Compliance Report -->
        <?php if (isset($content['compliance'])): ?>
        <h2>Compliance Assessment</h2>

        <?php foreach ($content['compliance']['frameworks'] as $framework): ?>
        <div class="chart-box">
            <h3><?= htmlspecialchars($framework['name']) ?> <?= htmlspecialchars($framework['version'] ?? '') ?></h3>
            <p><?= htmlspecialchars($framework['description']) ?></p>
            <?php if ($framework['total_checks'] > 0): ?>
            <p><strong>Compliance Score:</strong>
                <?= round(($framework['passed'] / $framework['total_checks']) * 100, 1) ?>%
                (<?= $framework['passed'] ?>/<?= $framework['total_checks'] ?> controls passed)
            </p>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>

        <!-- Footer -->
        <div class="footer">
            <p><strong>IOC Intelligent Operating Centre</strong></p>
            <p>Report generated on <?= date('Y-m-d H:i:s') ?></p>
            <p>This report contains confidential information and should be handled securely.</p>
            <p>AI-Powered Network Operations & Performance Management Platform</p>
        </div>
    </div>
</body>
</html>
