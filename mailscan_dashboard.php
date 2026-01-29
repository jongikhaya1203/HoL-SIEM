<?php
require_once 'mailscan_config.php';
require_once 'EmailScanner.php';

$db = getDBConnection();
$scanner = new EmailScanner();

// Get statistics
$stats = $scanner->getStatistics();

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$severity_filter = $_GET['severity'] ?? 'all';
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * ITEMS_PER_PAGE;

// Build query
$where_conditions = [];
$params = [];

if ($status_filter !== 'all') {
    $where_conditions[] = "el.scan_status = ?";
    $params[] = $status_filter;
}

if ($severity_filter !== 'all') {
    $where_conditions[] = "EXISTS (
        SELECT 1 FROM scan_results sr
        JOIN detection_rules dr ON sr.rule_id = dr.id
        WHERE sr.email_id = el.email_id AND dr.severity = ?
    )";
    $params[] = $severity_filter;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get flagged emails
$stmt = $db->prepare("
    SELECT el.*, COUNT(sr.id) as match_count
    FROM email_logs el
    LEFT JOIN scan_results sr ON el.email_id = sr.email_id
    $where_clause
    GROUP BY el.id
    ORDER BY el.received_date DESC
    LIMIT ? OFFSET ?
");

$params[] = ITEMS_PER_PAGE;
$params[] = $offset;
$stmt->execute($params);
$emails = $stmt->fetchAll();

// Get total count for pagination
$count_params = array_slice($params, 0, -2);
$stmt = $db->prepare("SELECT COUNT(DISTINCT el.id) as total FROM email_logs el $where_clause");
$stmt->execute($count_params);
$total_emails = $stmt->fetch()['total'];
$total_pages = ceil($total_emails / ITEMS_PER_PAGE);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email DLP - Monitoring Dashboard</title>
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
            color: #667eea;
            border-bottom-color: #667eea;
            background: #f8f9fa;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border-left: 4px solid #667eea;
        }
        .stat-value {
            font-size: 36px;
            font-weight: bold;
            color: #667eea;
            margin: 10px 0;
        }
        .stat-label {
            color: #5a6c7d;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .filters {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .filters form {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .filter-group label {
            font-size: 12px;
            color: #5a6c7d;
            font-weight: 600;
        }
        select, button {
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        button {
            background: #667eea;
            color: white;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 18px;
        }
        button:hover {
            background: #5568d3;
            transform: translateY(-1px);
        }
        .email-list {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        .email-item {
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
            transition: background 0.2s;
        }
        .email-item:hover {
            background: #f8f9fa;
        }
        .email-item:last-child {
            border-bottom: none;
        }
        .email-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 10px;
        }
        .email-subject {
            font-size: 16px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        .email-meta {
            font-size: 13px;
            color: #6c757d;
        }
        .email-stats {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        .risk-score {
            background: #fee;
            color: #c33;
            padding: 5px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 13px;
        }
        .match-count {
            background: #fff3cd;
            color: #856404;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 13px;
        }
        .badge {
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        .view-details {
            color: #667eea;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
        }
        .view-details:hover {
            text-decoration: underline;
        }
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
            padding: 20px;
        }
        .pagination a {
            padding: 8px 15px;
            background: white;
            color: #667eea;
            text-decoration: none;
            border-radius: 6px;
            border: 1px solid #ddd;
        }
        .pagination a:hover, .pagination a.active {
            background: #667eea;
            color: white;
        }
        .severity-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: 600;
            color: white;
        }
        .empty-state {
            padding: 60px 20px;
            text-align: center;
            color: #6c757d;
        }
        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <h1>📧 Email Data Loss Prevention System</h1>
            <div class="subtitle">Monitor and detect sensitive information in email communications</div>
        </div>
    </div>

    <div class="nav">
        <div class="container nav-container">
            <a href="mailscan_dashboard.php" class="nav-link active">Dashboard</a>
            <a href="mailscan_rules.php" class="nav-link">Detection Rules</a>
            <a href="mailscan_scan.php" class="nav-link">Scan Email</a>
            <a href="mailscan_leak_tracker.php" class="nav-link">Leak Tracker</a>
        </div>
    </div>

    <div class="container">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total Emails</div>
                <div class="stat-value"><?= number_format($stats['total_emails']) ?></div>
            </div>
            <div class="stat-card" style="border-left-color: #dc3545;">
                <div class="stat-label">Flagged Emails</div>
                <div class="stat-value" style="color: #dc3545;"><?= number_format($stats['flagged_emails']) ?></div>
            </div>
            <div class="stat-card" style="border-left-color: #ffc107;">
                <div class="stat-label">Total Matches</div>
                <div class="stat-value" style="color: #ffc107;"><?= number_format($stats['total_matches']) ?></div>
            </div>
            <div class="stat-card" style="border-left-color: #28a745;">
                <div class="stat-label">Active Rules</div>
                <div class="stat-value" style="color: #28a745;"><?= number_format($stats['active_rules']) ?></div>
            </div>
        </div>

        <div class="filters">
            <form method="GET">
                <div class="filter-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>All</option>
                        <option value="flagged" <?= $status_filter === 'flagged' ? 'selected' : '' ?>>Flagged</option>
                        <option value="scanned" <?= $status_filter === 'scanned' ? 'selected' : '' ?>>Scanned</option>
                        <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Severity</label>
                    <select name="severity">
                        <option value="all" <?= $severity_filter === 'all' ? 'selected' : '' ?>>All</option>
                        <option value="critical" <?= $severity_filter === 'critical' ? 'selected' : '' ?>>Critical</option>
                        <option value="high" <?= $severity_filter === 'high' ? 'selected' : '' ?>>High</option>
                        <option value="medium" <?= $severity_filter === 'medium' ? 'selected' : '' ?>>Medium</option>
                        <option value="low" <?= $severity_filter === 'low' ? 'selected' : '' ?>>Low</option>
                    </select>
                </div>
                <button type="submit">Apply Filters</button>
            </form>
        </div>

        <div class="email-list">
            <?php if (empty($emails)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📭</div>
                    <h3>No emails found</h3>
                    <p>Try adjusting your filters or scan some emails to get started.</p>
                </div>
            <?php else: ?>
                <?php foreach ($emails as $email): ?>
                    <div class="email-item">
                        <div class="email-header">
                            <div style="flex: 1;">
                                <div class="email-subject"><?= h($email['subject']) ?></div>
                                <div class="email-meta">
                                    From: <strong><?= h($email['sender_email']) ?></strong>
                                    <?php if ($email['sender_name']): ?>
                                        (<?= h($email['sender_name']) ?>)
                                    <?php endif; ?>
                                    • <?= date('M j, Y g:i A', strtotime($email['received_date'])) ?>
                                </div>
                            </div>
                            <div class="email-stats">
                                <?= getStatusBadge($email['scan_status']) ?>
                                <?php if ($email['risk_score'] > 0): ?>
                                    <div class="risk-score">Risk: <?= $email['risk_score'] ?></div>
                                <?php endif; ?>
                                <?php if ($email['match_count'] > 0): ?>
                                    <div class="match-count"><?= $email['match_count'] ?> match<?= $email['match_count'] > 1 ? 'es' : '' ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div style="margin-top: 10px;">
                            <a href="mailscan_details.php?email_id=<?= urlencode($email['email_id']) ?>" class="view-details">
                                View Details →
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>&status=<?= $status_filter ?>&severity=<?= $severity_filter ?>">← Previous</a>
                <?php endif; ?>

                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                    <a href="?page=<?= $i ?>&status=<?= $status_filter ?>&severity=<?= $severity_filter ?>"
                       class="<?= $i === $page ? 'active' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?= $page + 1 ?>&status=<?= $status_filter ?>&severity=<?= $severity_filter ?>">Next →</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
