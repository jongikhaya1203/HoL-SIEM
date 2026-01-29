<?php
/**
 * Log New Problem Form
 */

require_once __DIR__ . '/../classes/Database.php';
$db = Database::getInstance();

$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $root_cause = trim($_POST['root_cause'] ?? '');
    $priority = $_POST['priority'] ?? '';
    $related_incidents = intval($_POST['related_incidents'] ?? 0);
    $affected_services = trim($_POST['affected_services'] ?? '');
    $workaround = trim($_POST['workaround'] ?? '');
    $identified_by = trim($_POST['identified_by'] ?? '');

    if ($title && $description && $priority && $identified_by) {
        try {
            // Create problems table if not exists
            $db->query("CREATE TABLE IF NOT EXISTS problems (
                id INT AUTO_INCREMENT PRIMARY KEY,
                problem_id VARCHAR(50) UNIQUE NOT NULL,
                title VARCHAR(255) NOT NULL,
                description TEXT NOT NULL,
                root_cause TEXT,
                priority ENUM('Critical', 'High', 'Medium', 'Low') NOT NULL,
                status ENUM('New', 'Root Cause Analysis', 'Known Error', 'Resolved', 'Closed') DEFAULT 'New',
                related_incidents INT DEFAULT 0,
                affected_services TEXT,
                workaround TEXT,
                identified_by VARCHAR(255) NOT NULL,
                assigned_to VARCHAR(255),
                identified_date DATE,
                target_resolution DATE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_priority (priority),
                INDEX idx_status (status)
            )");

            // Generate problem ID
            $year = date('Y');
            $result = $db->fetchOne("SELECT COUNT(*) as count FROM problems WHERE problem_id LIKE 'PRB-{$year}-%'");
            $count = ($result['count'] ?? 0) + 1;
            $problem_id = sprintf('PRB-%s-%03d', $year, $count);

            // Calculate target resolution (30 days from now)
            $identified_date = date('Y-m-d');
            $target_resolution = date('Y-m-d', strtotime('+30 days'));

            // Insert problem
            $db->query(
                "INSERT INTO problems (problem_id, title, description, root_cause, priority, related_incidents,
                 affected_services, workaround, identified_by, identified_date, target_resolution)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [$problem_id, $title, $description, $root_cause, $priority, $related_incidents,
                 $affected_services, $workaround, $identified_by, $identified_date, $target_resolution]
            );

            $message = "Problem {$problem_id} created successfully! Target resolution: {$target_resolution}";
            $messageType = 'success';

            // Clear form
            $_POST = [];
        } catch (Exception $e) {
            $message = 'Error creating problem: ' . $e->getMessage();
            $messageType = 'error';
        }
    } else {
        $message = 'Please fill all required fields';
        $messageType = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log New Problem - ITSM</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            color: white;
            margin-bottom: 30px;
        }
        .header h1 {
            font-size: 42px;
            margin-bottom: 10px;
            text-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        .card {
            background: white;
            border-radius: 15px;
            padding: 35px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            margin-bottom: 20px;
        }
        .card h2 {
            color: #667eea;
            font-size: 24px;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e0e0e0;
        }
        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin-bottom: 25px;
            border-radius: 6px;
        }
        .info-box h3 {
            color: #1565c0;
            margin-bottom: 10px;
            font-size: 16px;
        }
        .info-box ul {
            margin-left: 20px;
            color: #333;
        }
        .info-box li {
            margin-bottom: 5px;
        }
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid #4CAF50;
        }
        .alert-error {
            background: #ffebee;
            color: #c62828;
            border-left: 4px solid #f44336;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        label {
            display: block;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }
        label .required {
            color: #f44336;
        }
        input[type="text"],
        input[type="number"],
        input[type="date"],
        select,
        textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            transition: border-color 0.3s;
        }
        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        textarea {
            resize: vertical;
            min-height: 100px;
        }
        .btn {
            padding: 14px 28px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary {
            background: #667eea;
            color: white;
        }
        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        .btn-secondary {
            background: #e0e0e0;
            color: #333;
        }
        .btn-secondary:hover {
            background: #d0d0d0;
        }
        .btn-group {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        .help-text {
            font-size: 13px;
            color: #666;
            margin-top: 5px;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔧 Log New Problem</h1>
            <p style="font-size: 18px; opacity: 0.95;">Root cause analysis for recurring incidents</p>
        </div>

        <div class="card">
            <div class="info-box">
                <h3>📘 What is a Problem?</h3>
                <ul>
                    <li>A problem is the underlying cause of one or more incidents</li>
                    <li>Problems require root cause analysis to prevent future incidents</li>
                    <li>Use this form when you've identified a pattern or recurring issue</li>
                    <li>Problems are tracked separately from incidents for long-term resolution</li>
                </ul>
            </div>

            <h2>Problem Details</h2>

            <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?>">
                <?= htmlspecialchars($message) ?>
            </div>
            <?php endif; ?>

            <form method="POST" id="problemForm">
                <div class="form-group">
                    <label for="title">Problem Title <span class="required">*</span></label>
                    <input type="text" id="title" name="title" required
                           placeholder="Concise description of the underlying problem"
                           value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
                    <div class="help-text">Example: "Database connection pool exhaustion causing application timeouts"</div>
                </div>

                <div class="form-group">
                    <label for="description">Problem Description <span class="required">*</span></label>
                    <textarea id="description" name="description" required
                              placeholder="Detailed description of the problem, including symptoms and patterns observed"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                    <div class="help-text">Describe the problem in detail, including when it occurs and what symptoms appear</div>
                </div>

                <div class="form-group">
                    <label for="root_cause">Known or Suspected Root Cause</label>
                    <textarea id="root_cause" name="root_cause"
                              placeholder="What is causing this problem? (Leave blank if still under investigation)"><?= htmlspecialchars($_POST['root_cause'] ?? '') ?></textarea>
                    <div class="help-text">If known, describe the root cause. Otherwise, this will be investigated during analysis.</div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="priority">Priority <span class="required">*</span></label>
                        <select id="priority" name="priority" required>
                            <option value="">-- Select Priority --</option>
                            <option value="Critical">Critical - Severe business impact</option>
                            <option value="High">High - Significant impact</option>
                            <option value="Medium">Medium - Moderate impact</option>
                            <option value="Low">Low - Minor impact</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="related_incidents">Number of Related Incidents</label>
                        <input type="number" id="related_incidents" name="related_incidents"
                               min="0" value="<?= $_POST['related_incidents'] ?? 0 ?>">
                        <div class="help-text">How many incidents are linked to this problem?</div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="affected_services">Affected Services/Systems</label>
                    <input type="text" id="affected_services" name="affected_services"
                           placeholder="e.g., Email Server, CRM Application, Network Infrastructure"
                           value="<?= htmlspecialchars($_POST['affected_services'] ?? '') ?>">
                    <div class="help-text">List all systems or services affected by this problem</div>
                </div>

                <div class="form-group">
                    <label for="workaround">Temporary Workaround</label>
                    <textarea id="workaround" name="workaround"
                              placeholder="Describe any temporary solutions or workarounds that can mitigate the problem"><?= htmlspecialchars($_POST['workaround'] ?? '') ?></textarea>
                    <div class="help-text">This helps support teams handle incidents while the problem is being fixed</div>
                </div>

                <div class="form-group">
                    <label for="identified_by">Identified By <span class="required">*</span></label>
                    <input type="text" id="identified_by" name="identified_by" required
                           placeholder="Your name or team name"
                           value="<?= htmlspecialchars($_POST['identified_by'] ?? '') ?>">
                </div>

                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">🔧 Submit Problem</button>
                    <a href="itsm_enhanced.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>

        <div style="text-align: center; margin-top: 20px;">
            <a href="itsm_enhanced.php" style="color: white; text-decoration: none; font-weight: 600;">
                ← Back to ITSM Dashboard
            </a>
        </div>
    </div>

    <script>
        // Form validation
        document.getElementById('problemForm').addEventListener('submit', function(e) {
            const title = document.getElementById('title').value.trim();
            const description = document.getElementById('description').value.trim();

            if (title.length < 15) {
                alert('Title must be at least 15 characters long for proper problem description');
                e.preventDefault();
                return false;
            }

            if (description.length < 30) {
                alert('Description must be at least 30 characters long');
                e.preventDefault();
                return false;
            }
        });
    </script>
</body>
</html>
