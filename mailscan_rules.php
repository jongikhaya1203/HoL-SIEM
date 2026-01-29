<?php
require_once 'mailscan_config.php';

$db = getDBConnection();
$message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $stmt = $db->prepare("
                    INSERT INTO detection_rules
                    (rule_name, rule_description, rule_type, pattern, severity, action, category, created_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $_POST['rule_name'],
                    $_POST['rule_description'],
                    $_POST['rule_type'],
                    $_POST['pattern'],
                    $_POST['severity'],
                    $_POST['action_type'],
                    $_POST['category'],
                    'admin'
                ]);
                $message = "Rule added successfully!";
                break;

            case 'toggle':
                $stmt = $db->prepare("UPDATE detection_rules SET enabled = NOT enabled WHERE id = ?");
                $stmt->execute([$_POST['rule_id']]);
                $message = "Rule status updated!";
                break;

            case 'delete':
                $stmt = $db->prepare("DELETE FROM detection_rules WHERE id = ?");
                $stmt->execute([$_POST['rule_id']]);
                $message = "Rule deleted successfully!";
                break;
        }
    }
}

// Get all rules
$stmt = $db->query("
    SELECT dr.*,
           COUNT(DISTINCT sr.email_id) as emails_matched
    FROM detection_rules dr
    LEFT JOIN scan_results sr ON dr.id = sr.rule_id
    GROUP BY dr.id
    ORDER BY dr.severity DESC, dr.created_at DESC
");
$rules = $stmt->fetchAll();

// Get categories
$stmt = $db->query("SELECT DISTINCT category FROM detection_rules WHERE category IS NOT NULL ORDER BY category");
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detection Rules - Email DLP</title>
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
        .message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }
        .add-rule-btn {
            background: #667eea;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 20px;
        }
        .add-rule-btn:hover {
            background: #5568d3;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal.active {
            display: flex;
        }
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 10px;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        .modal-title {
            font-size: 24px;
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
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            font-family: inherit;
        }
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        .form-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 25px;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
        }
        .btn-primary {
            background: #667eea;
            color: white;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .rules-table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
            color: #5a6c7d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        td {
            padding: 15px;
            border-top: 1px solid #e9ecef;
            font-size: 14px;
        }
        tr:hover {
            background: #f8f9fa;
        }
        .rule-name {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 3px;
        }
        .rule-desc {
            font-size: 12px;
            color: #6c757d;
        }
        .severity-badge {
            padding: 6px 12px;
            border-radius: 6px;
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
            font-size: 12px;
            font-weight: 600;
        }
        .status-enabled {
            background: #d4edda;
            color: #155724;
        }
        .status-disabled {
            background: #f8d7da;
            color: #721c24;
        }
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        .action-btn {
            padding: 6px 12px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }
        .action-btn:hover {
            background: #f8f9fa;
        }
        .pattern-code {
            font-family: 'Courier New', monospace;
            background: #f8f9fa;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <h1>🔍 Detection Rules</h1>
            <div class="subtitle">Manage patterns and keywords for sensitive information detection</div>
        </div>
    </div>

    <div class="nav">
        <div class="container nav-container">
            <a href="mailscan_dashboard.php" class="nav-link">Dashboard</a>
            <a href="mailscan_rules.php" class="nav-link active">Detection Rules</a>
            <a href="mailscan_scan.php" class="nav-link">Scan Email</a>
            <a href="mailscan_leak_tracker.php" class="nav-link">Leak Tracker</a>
        </div>
    </div>

    <div class="container">
        <?php if ($message): ?>
            <div class="message"><?= h($message) ?></div>
        <?php endif; ?>

        <button class="add-rule-btn" onclick="openModal()">+ Add New Rule</button>

        <div class="rules-table">
            <table>
                <thead>
                    <tr>
                        <th>Rule Name</th>
                        <th>Type</th>
                        <th>Severity</th>
                        <th>Category</th>
                        <th>Pattern</th>
                        <th>Status</th>
                        <th>Matches</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rules as $rule): ?>
                        <tr>
                            <td>
                                <div class="rule-name"><?= h($rule['rule_name']) ?></div>
                                <div class="rule-desc"><?= h($rule['rule_description']) ?></div>
                            </td>
                            <td><?= h($rule['rule_type']) ?></td>
                            <td>
                                <span class="severity-badge severity-<?= $rule['severity'] ?>">
                                    <?= strtoupper($rule['severity']) ?>
                                </span>
                            </td>
                            <td><?= h($rule['category']) ?></td>
                            <td>
                                <div class="pattern-code" title="<?= h($rule['pattern']) ?>">
                                    <?= h($rule['pattern']) ?>
                                </div>
                            </td>
                            <td>
                                <span class="status-badge status-<?= $rule['enabled'] ? 'enabled' : 'disabled' ?>">
                                    <?= $rule['enabled'] ? 'Enabled' : 'Disabled' ?>
                                </span>
                            </td>
                            <td><?= number_format($rule['emails_matched']) ?></td>
                            <td>
                                <div class="action-buttons">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="rule_id" value="<?= $rule['id'] ?>">
                                        <button type="submit" class="action-btn">
                                            <?= $rule['enabled'] ? 'Disable' : 'Enable' ?>
                                        </button>
                                    </form>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this rule?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="rule_id" value="<?= $rule['id'] ?>">
                                        <button type="submit" class="action-btn">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Rule Modal -->
    <div id="addRuleModal" class="modal">
        <div class="modal-content">
            <h2 class="modal-title">Add New Detection Rule</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add">

                <div class="form-group">
                    <label>Rule Name *</label>
                    <input type="text" name="rule_name" required placeholder="e.g., Credit Card Detection">
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="rule_description" placeholder="What does this rule detect?"></textarea>
                </div>

                <div class="form-group">
                    <label>Rule Type *</label>
                    <select name="rule_type" required>
                        <option value="regex">Regex Pattern</option>
                        <option value="keyword">Keyword Match</option>
                        <option value="pattern">Custom Pattern</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Pattern *</label>
                    <textarea name="pattern" required placeholder="For regex: \b\d{3}-\d{2}-\d{4}\b&#10;For keyword: confidential|secret|internal (use | to separate)"></textarea>
                </div>

                <div class="form-group">
                    <label>Severity *</label>
                    <select name="severity" required>
                        <option value="critical">Critical</option>
                        <option value="high">High</option>
                        <option value="medium" selected>Medium</option>
                        <option value="low">Low</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Action *</label>
                    <select name="action_type" required>
                        <option value="flag" selected>Flag for Review</option>
                        <option value="block">Block Email</option>
                        <option value="quarantine">Quarantine</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Category</label>
                    <input type="text" name="category" placeholder="e.g., PII, Financial, Security" list="categories">
                    <datalist id="categories">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= h($cat) ?>">
                        <?php endforeach; ?>
                    </datalist>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Rule</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal() {
            document.getElementById('addRuleModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('addRuleModal').classList.remove('active');
        }

        // Close modal when clicking outside
        document.getElementById('addRuleModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>
