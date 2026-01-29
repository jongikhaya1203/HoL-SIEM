<?php
/**
 * View Detailed Scan Report
 */
// require_once __DIR__ . '/auth_check.php'; // Auth disabled for easier access
require_once __DIR__ . '/classes/Database.php';

$db = Database::getInstance();
$scanId = $_GET['id'] ?? null;

if (!$scanId) {
    header('Location: index.php');
    exit;
}

// Get scan details
$scan = $db->fetchOne(
    "SELECT * FROM scans WHERE id = ?",
    [$scanId]
);

if (!$scan) {
    die('Scan not found');
}

// Get vulnerability details
$vulnerabilities = $db->fetchAll(
    "SELECT * FROM vulnerabilities WHERE scan_id = ? ORDER BY severity DESC, id ASC",
    [$scanId]
);

// Count vulnerabilities by severity
$criticalCount = 0;
$highCount = 0;
$mediumCount = 0;
$lowCount = 0;
$infoCount = 0;

foreach ($vulnerabilities as $vuln) {
    switch (strtolower($vuln['severity'])) {
        case 'critical':
            $criticalCount++;
            break;
        case 'high':
            $highCount++;
            break;
        case 'medium':
            $mediumCount++;
            break;
        case 'low':
            $lowCount++;
            break;
        case 'info':
            $infoCount++;
            break;
    }
}

$totalVulns = count($vulnerabilities);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scan Report - <?php echo htmlspecialchars($scan['target']); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .header h1 {
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .header .meta {
            color: #7f8c8d;
            font-size: 14px;
        }

        .header .meta span {
            margin-right: 20px;
        }

        .back-btn {
            display: inline-block;
            padding: 10px 20px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-bottom: 20px;
            transition: background 0.3s;
        }

        .back-btn:hover {
            background: #2980b9;
        }

        .summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .summary-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }

        .summary-card h3 {
            font-size: 14px;
            color: #7f8c8d;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .summary-card .number {
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .summary-card.critical .number { color: #e74c3c; }
        .summary-card.high .number { color: #e67e22; }
        .summary-card.medium .number { color: #f39c12; }
        .summary-card.low .number { color: #3498db; }
        .summary-card.info .number { color: #95a5a6; }
        .summary-card.total .number { color: #2c3e50; }

        .vulnerabilities {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .vulnerabilities h2 {
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #ecf0f1;
        }

        .vuln-item {
            padding: 20px;
            margin-bottom: 15px;
            border-left: 4px solid #ddd;
            background: #f8f9fa;
            border-radius: 5px;
        }

        .vuln-item.critical { border-left-color: #e74c3c; }
        .vuln-item.high { border-left-color: #e67e22; }
        .vuln-item.medium { border-left-color: #f39c12; }
        .vuln-item.low { border-left-color: #3498db; }
        .vuln-item.info { border-left-color: #95a5a6; }

        .vuln-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .vuln-title {
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
        }

        .severity-badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            color: white;
        }

        .severity-badge.critical { background: #e74c3c; }
        .severity-badge.high { background: #e67e22; }
        .severity-badge.medium { background: #f39c12; }
        .severity-badge.low { background: #3498db; }
        .severity-badge.info { background: #95a5a6; }

        .vuln-description {
            color: #555;
            line-height: 1.6;
            margin-bottom: 15px;
        }

        .vuln-details {
            background: white;
            padding: 15px;
            border-radius: 5px;
            margin-top: 10px;
        }

        .vuln-details h4 {
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .vuln-details p {
            color: #666;
            line-height: 1.6;
            margin-bottom: 10px;
        }

        .vuln-meta {
            display: flex;
            gap: 20px;
            margin-top: 10px;
            font-size: 14px;
            color: #7f8c8d;
        }

        .vuln-meta span {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .no-vulns {
            text-align: center;
            padding: 40px;
            color: #27ae60;
        }

        .no-vulns h3 {
            font-size: 24px;
            margin-bottom: 10px;
        }

        .scan-info {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .scan-info h3 {
            color: #2c3e50;
            margin-bottom: 15px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }

        .info-item {
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
        }

        .info-item label {
            display: block;
            font-size: 12px;
            color: #7f8c8d;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .info-item value {
            display: block;
            font-size: 16px;
            color: #2c3e50;
            font-weight: 500;
        }

        .status-badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-badge.completed { background: #27ae60; color: white; }
        .status-badge.running { background: #3498db; color: white; }
        .status-badge.failed { background: #e74c3c; color: white; }
        .status-badge.pending { background: #95a5a6; color: white; }

        @media print {
            body { background: white; }
            .back-btn { display: none; }
            .container { max-width: 100%; }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="back-btn">← Back to Dashboard</a>

        <div class="header">
            <h1>Scan Report: <?php echo htmlspecialchars($scan['target'] ?? $scan['scan_name'] ?? 'Unknown Target'); ?></h1>
            <div class="meta">
                <span><strong>Scan Name:</strong> <?php echo htmlspecialchars($scan['scan_name'] ?? 'N/A'); ?></span>
                <span><strong>Type:</strong> <?php echo htmlspecialchars($scan['scan_type'] ?? 'N/A'); ?></span>
                <span><strong>Started:</strong> <?php echo $scan['started_at'] ? date('Y-m-d H:i:s', strtotime($scan['started_at'])) : 'N/A'; ?></span>
                <?php if (!empty($scan['completed_at'])): ?>
                <span><strong>Completed:</strong> <?php echo date('Y-m-d H:i:s', strtotime($scan['completed_at'])); ?></span>
                <?php endif; ?>
            </div>
        </div>

        <div class="scan-info">
            <h3>Scan Information</h3>
            <div class="info-grid">
                <div class="info-item">
                    <label>Status</label>
                    <value>
                        <span class="status-badge <?php echo strtolower($scan['status']); ?>">
                            <?php echo htmlspecialchars($scan['status']); ?>
                        </span>
                    </value>
                </div>
                <div class="info-item">
                    <label>Target</label>
                    <value><?php echo htmlspecialchars($scan['target'] ?? $scan['scan_name'] ?? 'N/A'); ?></value>
                </div>
                <div class="info-item">
                    <label>Scan Type</label>
                    <value><?php echo htmlspecialchars(ucfirst($scan['scan_type'] ?? 'N/A')); ?></value>
                </div>
                <div class="info-item">
                    <label>Duration</label>
                    <value>
                        <?php
                        if ($scan['completed_at']) {
                            $start = strtotime($scan['started_at']);
                            $end = strtotime($scan['completed_at']);
                            $duration = $end - $start;
                            echo gmdate("H:i:s", $duration);
                        } else {
                            echo "In Progress";
                        }
                        ?>
                    </value>
                </div>
            </div>
        </div>

        <div class="summary">
            <div class="summary-card total">
                <h3>Total Vulnerabilities</h3>
                <div class="number"><?php echo $totalVulns; ?></div>
            </div>
            <div class="summary-card critical">
                <h3>Critical</h3>
                <div class="number"><?php echo $criticalCount; ?></div>
            </div>
            <div class="summary-card high">
                <h3>High</h3>
                <div class="number"><?php echo $highCount; ?></div>
            </div>
            <div class="summary-card medium">
                <h3>Medium</h3>
                <div class="number"><?php echo $mediumCount; ?></div>
            </div>
            <div class="summary-card low">
                <h3>Low</h3>
                <div class="number"><?php echo $lowCount; ?></div>
            </div>
            <div class="summary-card info">
                <h3>Info</h3>
                <div class="number"><?php echo $infoCount; ?></div>
            </div>
        </div>

        <div class="vulnerabilities">
            <h2>Vulnerability Details</h2>

            <?php if (empty($vulnerabilities)): ?>
                <div class="no-vulns">
                    <h3>✓ No vulnerabilities found</h3>
                    <p>This target appears to be secure based on the current scan.</p>
                </div>
            <?php else: ?>
                <?php foreach ($vulnerabilities as $vuln): ?>
                    <div class="vuln-item <?php echo strtolower($vuln['severity']); ?>">
                        <div class="vuln-header">
                            <div class="vuln-title"><?php echo htmlspecialchars($vuln['title']); ?></div>
                            <span class="severity-badge <?php echo strtolower($vuln['severity']); ?>">
                                <?php echo htmlspecialchars($vuln['severity']); ?>
                            </span>
                        </div>

                        <div class="vuln-description">
                            <?php echo nl2br(htmlspecialchars($vuln['description'])); ?>
                        </div>

                        <?php if ($vuln['recommendation']): ?>
                            <div class="vuln-details">
                                <h4>💡 Recommendation</h4>
                                <p><?php echo nl2br(htmlspecialchars($vuln['recommendation'])); ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if ($vuln['cve_id']): ?>
                            <div class="vuln-details">
                                <h4>🔍 CVE Reference</h4>
                                <p>
                                    <a href="https://nvd.nist.gov/vuln/detail/<?php echo htmlspecialchars($vuln['cve_id']); ?>"
                                       target="_blank"
                                       style="color: #3498db; text-decoration: none;">
                                        <?php echo htmlspecialchars($vuln['cve_id']); ?>
                                    </a>
                                </p>
                            </div>
                        <?php endif; ?>

                        <div class="vuln-meta">
                            <?php if ($vuln['port']): ?>
                                <span>🔌 Port: <?php echo htmlspecialchars($vuln['port']); ?></span>
                            <?php endif; ?>
                            <?php if ($vuln['service']): ?>
                                <span>⚙️ Service: <?php echo htmlspecialchars($vuln['service']); ?></span>
                            <?php endif; ?>
                            <span>📅 Detected: <?php echo date('Y-m-d H:i:s', strtotime($vuln['detected_at'])); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
