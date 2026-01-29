<?php
require_once 'mailscan_config.php';
require_once 'EmailScanner.php';

$db = getDBConnection();
$scanner = new EmailScanner();

$result = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Generate unique email ID
        $email_id = 'EMAIL-' . date('Ymd-His') . '-' . substr(md5(uniqid()), 0, 8);

        // Insert email into database
        $stmt = $db->prepare("
            INSERT INTO email_logs
            (email_id, sender_email, sender_name, recipient_email, subject, body_text, body_html, attachments)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $email_id,
            $_POST['sender_email'],
            $_POST['sender_name'] ?? '',
            $_POST['recipient_email'],
            $_POST['subject'],
            $_POST['body_text'],
            $_POST['body_html'] ?? '',
            $_POST['attachments'] ?? ''
        ]);

        // Scan the email
        $result = $scanner->scanEmail($email_id);

    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scan Email - Email DLP</title>
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
            color: #667eea;
            border-bottom-color: #667eea;
            background: #f8f9fa;
        }
        .main-content {
            margin-top: 30px;
        }
        .card {
            background: white;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .card-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #2c3e50;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
            font-size: 14px;
        }
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            font-family: inherit;
        }
        .form-group textarea {
            min-height: 200px;
            resize: vertical;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .submit-btn {
            background: #667eea;
            color: white;
            padding: 14px 30px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            width: 100%;
        }
        .submit-btn:hover {
            background: #5568d3;
        }
        .result-card {
            background: white;
            border-radius: 10px;
            padding: 30px;
            margin-top: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .result-success {
            border-left: 4px solid #28a745;
        }
        .result-warning {
            border-left: 4px solid #ffc107;
        }
        .result-danger {
            border-left: 4px solid #dc3545;
        }
        .result-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }
        .result-icon {
            font-size: 48px;
        }
        .result-title {
            font-size: 24px;
            font-weight: 600;
        }
        .result-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .stat-item {
            text-align: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 6px;
        }
        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
        }
        .stat-label {
            font-size: 12px;
            color: #6c757d;
            margin-top: 5px;
            text-transform: uppercase;
        }
        .match-list {
            margin-top: 20px;
        }
        .match-item {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 10px;
        }
        .match-rule {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        .match-content {
            font-family: 'Courier New', monospace;
            background: white;
            padding: 8px;
            border-radius: 4px;
            margin-top: 8px;
            font-size: 13px;
        }
        .severity-badge {
            padding: 4px 8px;
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
        .view-details-btn {
            margin-top: 20px;
            background: #667eea;
            color: white;
            padding: 12px 24px;
            border-radius: 6px;
            text-decoration: none;
            display: inline-block;
            font-weight: 600;
        }
        .view-details-btn:hover {
            background: #5568d3;
        }
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 6px;
            border: 1px solid #f5c6cb;
        }
        .sample-data-btn {
            background: #6c757d;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            margin-bottom: 15px;
        }
        .sample-data-btn:hover {
            background: #5a6268;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <h1>📨 Scan Email</h1>
            <div class="subtitle">Submit an email for sensitive information detection</div>
        </div>
    </div>

    <div class="nav">
        <div class="container nav-container">
            <a href="mailscan_dashboard.php" class="nav-link">Dashboard</a>
            <a href="mailscan_rules.php" class="nav-link">Detection Rules</a>
            <a href="mailscan_scan.php" class="nav-link active">Scan Email</a>
            <a href="mailscan_leak_tracker.php" class="nav-link">Leak Tracker</a>
        </div>
    </div>

    <div class="container main-content">
        <div class="card">
            <div class="card-title">Email Information</div>

            <?php if ($error): ?>
                <div class="error-message"><?= h($error) ?></div>
            <?php endif; ?>

            <button class="sample-data-btn" onclick="fillSampleData()">Load Sample Email</button>

            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label>Sender Email *</label>
                        <input type="email" name="sender_email" id="sender_email" required
                               placeholder="sender@company.com">
                    </div>
                    <div class="form-group">
                        <label>Sender Name</label>
                        <input type="text" name="sender_name" id="sender_name"
                               placeholder="John Doe">
                    </div>
                </div>

                <div class="form-group">
                    <label>Recipient Email *</label>
                    <input type="text" name="recipient_email" id="recipient_email" required
                           placeholder="recipient@external.com">
                </div>

                <div class="form-group">
                    <label>Subject *</label>
                    <input type="text" name="subject" id="subject" required
                           placeholder="Email subject line">
                </div>

                <div class="form-group">
                    <label>Email Body *</label>
                    <textarea name="body_text" id="body_text" required
                              placeholder="Enter the email body content here..."></textarea>
                </div>

                <button type="submit" class="submit-btn">🔍 Scan Email</button>
            </form>
        </div>

        <?php if ($result): ?>
            <div class="result-card <?= $result['status'] === 'flagged' ? 'result-danger' : 'result-success' ?>">
                <div class="result-header">
                    <div class="result-icon">
                        <?= $result['status'] === 'flagged' ? '⚠️' : '✅' ?>
                    </div>
                    <div>
                        <div class="result-title">
                            <?= $result['status'] === 'flagged' ? 'Sensitive Information Detected!' : 'Scan Complete - No Issues Found' ?>
                        </div>
                        <div style="color: #6c757d; margin-top: 5px;">
                            Email ID: <?= h($result['email_id']) ?>
                        </div>
                    </div>
                </div>

                <div class="result-stats">
                    <div class="stat-item">
                        <div class="stat-value"><?= $result['total_matches'] ?></div>
                        <div class="stat-label">Matches Found</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value" style="color: #dc3545;"><?= $result['risk_score'] ?></div>
                        <div class="stat-label">Risk Score</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?= strtoupper($result['status']) ?></div>
                        <div class="stat-label">Status</div>
                    </div>
                </div>

                <?php if (!empty($result['matches'])): ?>
                    <div class="match-list">
                        <h3 style="margin-bottom: 15px;">Detected Patterns:</h3>
                        <?php foreach (array_slice($result['matches'], 0, 5) as $match): ?>
                            <div class="match-item">
                                <div class="match-rule">
                                    <?= h($match['rule']['rule_name']) ?>
                                    <span class="severity-badge severity-<?= $match['rule']['severity'] ?>">
                                        <?= strtoupper($match['rule']['severity']) ?>
                                    </span>
                                </div>
                                <div style="font-size: 13px; color: #6c757d; margin: 5px 0;">
                                    <?= h($match['rule']['rule_description']) ?>
                                </div>
                                <div style="font-size: 12px; color: #495057; margin: 5px 0;">
                                    <strong>Location:</strong> <?= h($match['match']['location']) ?>
                                </div>
                                <div class="match-content">
                                    <?= h($match['match']['matched_content']) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <?php if (count($result['matches']) > 5): ?>
                            <p style="margin-top: 15px; color: #6c757d;">
                                + <?= count($result['matches']) - 5 ?> more match(es)
                            </p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <a href="mailscan_details.php?email_id=<?= urlencode($result['email_id']) ?>"
                   class="view-details-btn">
                    View Full Details →
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function fillSampleData() {
            document.getElementById('sender_email').value = 'john.smith@techcorp.com';
            document.getElementById('sender_name').value = 'John Smith';
            document.getElementById('recipient_email').value = 'competitor@external.com';
            document.getElementById('subject').value = 'Confidential: Q4 Financial Results';
            document.getElementById('body_text').value = `Hi,

Here are the confidential Q4 financial results before the official announcement.

Our revenue was $5.2M with EBITDA of $1.8M. This is strictly confidential information - do not distribute.

Also, I'm attaching the customer database. The main access credentials are:
API Key: sk_test_EXAMPLE_KEY_FOR_TESTING_ONLY_12345
Database Password: MySecureP@ssw0rd123

Some key customer credit cards on file:
- Premium Customer: 4532-1234-5678-9010
- Enterprise Client: 5425-2334-5566-7788

Patient records show increase in claims. Patient ID: MRN-998877 has concerning history.

Please call me at 555-123-4567 to discuss.

Best regards,
John Smith
SSN: 123-45-6789`;
        }
    </script>
</body>
</html>
