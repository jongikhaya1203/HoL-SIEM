<?php
require_once 'mailscan_config.php';

$db = getDBConnection();
$email_id = $_GET['email_id'] ?? '';

if (empty($email_id)) {
    header('Location: mailscan_dashboard.php');
    exit;
}

// Get email details
$stmt = $db->prepare("SELECT * FROM email_logs WHERE email_id = ?");
$stmt->execute([$email_id]);
$email = $stmt->fetch();

if (!$email) {
    die("Email not found");
}

// Get scan results with rule details
$stmt = $db->prepare("
    SELECT sr.*, dr.rule_name, dr.rule_description, dr.severity, dr.category
    FROM scan_results sr
    JOIN detection_rules dr ON sr.rule_id = dr.id
    WHERE sr.email_id = ?
    ORDER BY dr.severity DESC, sr.detected_at DESC
");
$stmt->execute([$email_id]);
$matches = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Details - DLP System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f7fa;
            color: #2c3e50;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        .back-link {
            color: white;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 15px;
            opacity: 0.9;
        }
        .back-link:hover {
            opacity: 1;
            text-decoration: underline;
        }
        h1 {
            font-size: 28px;
        }
        .main-content {
            margin-top: 30px;
        }
        .card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .card-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .email-info {
            display: grid;
            grid-template-columns: 150px 1fr;
            gap: 15px;
            font-size: 14px;
        }
        .info-label {
            font-weight: 600;
            color: #5a6c7d;
        }
        .info-value {
            color: #2c3e50;
        }
        .email-body {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 6px;
            border-left: 4px solid #667eea;
            white-space: pre-wrap;
            word-wrap: break-word;
            max-height: 400px;
            overflow-y: auto;
            font-size: 14px;
            line-height: 1.6;
        }
        .match-item {
            background: #fff;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            border-left: 4px solid;
        }
        .match-item.critical { border-left-color: #dc3545; }
        .match-item.high { border-left-color: #fd7e14; }
        .match-item.medium { border-left-color: #ffc107; }
        .match-item.low { border-left-color: #17a2b8; }
        .match-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }
        .match-rule {
            font-weight: 600;
            font-size: 16px;
            color: #2c3e50;
        }
        .match-description {
            font-size: 13px;
            color: #6c757d;
            margin: 5px 0;
        }
        .severity-badge {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            color: white;
            display: inline-block;
        }
        .severity-critical { background: #dc3545; }
        .severity-high { background: #fd7e14; }
        .severity-medium { background: #ffc107; color: #333; }
        .severity-low { background: #17a2b8; }
        .match-content {
            background: #fff3cd;
            padding: 12px;
            border-radius: 6px;
            margin: 10px 0;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            border: 1px solid #ffc107;
        }
        .match-context {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 6px;
            font-size: 13px;
            color: #495057;
            border-left: 3px solid #667eea;
            margin-top: 10px;
        }
        .match-meta {
            display: flex;
            gap: 20px;
            font-size: 12px;
            color: #6c757d;
            margin-top: 10px;
        }
        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            background: #e9ecef;
            color: #495057;
        }
        .risk-summary {
            background: linear-gradient(135deg, #fff5f5 0%, #ffe5e5 100%);
            border: 2px solid #dc3545;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .risk-score-big {
            font-size: 48px;
            font-weight: bold;
            color: #dc3545;
            text-align: center;
        }
        .risk-label {
            text-align: center;
            color: #6c757d;
            font-size: 14px;
            margin-top: 10px;
        }
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <a href="mailscan_dashboard.php" class="back-link">← Back to Dashboard</a>
            <h1>Email Details & Scan Results</h1>
        </div>
    </div>

    <div class="container main-content">
        <?php if ($email['risk_score'] > 0): ?>
            <div class="risk-summary">
                <div class="risk-score-big"><?= $email['risk_score'] ?></div>
                <div class="risk-label">RISK SCORE - <?= count($matches) ?> sensitive pattern<?= count($matches) > 1 ? 's' : '' ?> detected</div>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-title">📧 Email Information</div>
            <div class="email-info">
                <div class="info-label">Subject:</div>
                <div class="info-value"><strong><?= h($email['subject']) ?></strong></div>

                <div class="info-label">From:</div>
                <div class="info-value">
                    <?= h($email['sender_email']) ?>
                    <?php if ($email['sender_name']): ?>
                        (<?= h($email['sender_name']) ?>)
                    <?php endif; ?>
                </div>

                <div class="info-label">To:</div>
                <div class="info-value"><?= h($email['recipient_email']) ?></div>

                <div class="info-label">Received:</div>
                <div class="info-value"><?= date('F j, Y g:i A', strtotime($email['received_date'])) ?></div>

                <div class="info-label">Status:</div>
                <div class="info-value"><?= getStatusBadge($email['scan_status']) ?></div>

                <div class="info-label">Email ID:</div>
                <div class="info-value"><code><?= h($email['email_id']) ?></code></div>
            </div>
        </div>

        <div class="card">
            <div class="card-title">📝 Email Body</div>
            <div class="email-body"><?= h($email['body_text']) ?></div>
        </div>

        <div class="card">
            <div class="card-title">🚨 Detection Results (<?= count($matches) ?>)</div>

            <?php if (empty($matches)): ?>
                <div class="empty-state">
                    <p>✓ No sensitive information detected in this email.</p>
                </div>
            <?php else: ?>
                <?php foreach ($matches as $match): ?>
                    <div class="match-item <?= $match['severity'] ?>">
                        <div class="match-header">
                            <div>
                                <div class="match-rule"><?= h($match['rule_name']) ?></div>
                                <div class="match-description"><?= h($match['rule_description']) ?></div>
                            </div>
                            <div>
                                <span class="severity-badge severity-<?= $match['severity'] ?>">
                                    <?= strtoupper($match['severity']) ?>
                                </span>
                            </div>
                        </div>

                        <div>
                            <strong>Matched Content:</strong>
                            <div class="match-content"><?= h($match['matched_content']) ?></div>
                        </div>

                        <?php if ($match['context_snippet']): ?>
                            <div>
                                <strong>Context:</strong>
                                <div class="match-context"><?= h($match['context_snippet']) ?></div>
                            </div>
                        <?php endif; ?>

                        <div class="match-meta">
                            <span><strong>Location:</strong> <?= h($match['match_location']) ?></span>
                            <span><strong>Category:</strong> <span class="badge"><?= h($match['category']) ?></span></span>
                            <span><strong>Detected:</strong> <?= date('M j, Y g:i A', strtotime($match['detected_at'])) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
