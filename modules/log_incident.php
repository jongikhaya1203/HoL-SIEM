<?php
/**
 * Log New Incident Form
 */

require_once __DIR__ . '/../classes/Database.php';
$db = Database::getInstance();

$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = $_POST['category'] ?? '';
    $priority = $_POST['priority'] ?? '';
    $channel = $_POST['channel'] ?? '';
    $reported_by = trim($_POST['reported_by'] ?? '');
    $affected_users = intval($_POST['affected_users'] ?? 1);
    $department = $_POST['department'] ?? '';
    $business_impact = $_POST['business_impact'] ?? '';

    if ($title && $description && $category && $priority && $channel && $reported_by) {
        try {
            // Create incidents table if not exists
            $db->query("CREATE TABLE IF NOT EXISTS incidents (
                id INT AUTO_INCREMENT PRIMARY KEY,
                incident_id VARCHAR(50) UNIQUE NOT NULL,
                title VARCHAR(255) NOT NULL,
                description TEXT NOT NULL,
                category VARCHAR(100),
                priority ENUM('Critical', 'High', 'Medium', 'Low') NOT NULL,
                status ENUM('New', 'Assigned', 'In Progress', 'On Hold', 'Resolved', 'Closed') DEFAULT 'New',
                channel ENUM('Email', 'Web Portal', 'Phone', 'Chat') NOT NULL,
                reported_by VARCHAR(255) NOT NULL,
                assigned_to VARCHAR(255),
                affected_users INT DEFAULT 1,
                department VARCHAR(100),
                business_impact ENUM('Critical', 'High', 'Medium', 'Low'),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_priority (priority),
                INDEX idx_status (status),
                INDEX idx_created (created_at)
            )");

            // Generate incident ID
            $year = date('Y');
            $result = $db->fetchOne("SELECT COUNT(*) as count FROM incidents WHERE incident_id LIKE 'INC-{$year}-%'");
            $count = ($result['count'] ?? 0) + 1;
            $incident_id = sprintf('INC-%s-%03d', $year, $count);

            // Calculate SLA based on priority
            $sla_times = [
                'Critical' => 240,
                'High' => 480,
                'Medium' => 960,
                'Low' => 1440
            ];
            $sla_minutes = $sla_times[$priority];

            // Insert incident
            $db->query(
                "INSERT INTO incidents (incident_id, title, description, category, priority, channel, reported_by, affected_users, department, business_impact)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [$incident_id, $title, $description, $category, $priority, $channel, $reported_by, $affected_users, $department, $business_impact]
            );

            $message = "Incident {$incident_id} created successfully! SLA: {$sla_minutes} minutes for resolution.";
            $messageType = 'success';

            // Clear form
            $_POST = [];
        } catch (Exception $e) {
            $message = 'Error creating incident: ' . $e->getMessage();
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
    <title>Log New Incident - ITSM</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 800px;
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
        }
        .card h2 {
            color: #667eea;
            font-size: 24px;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e0e0e0;
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
        input[type="email"],
        input[type="number"],
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
            min-height: 120px;
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
        .priority-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
        }
        .badge-critical { background: #ffebee; color: #c62828; }
        .badge-high { background: #fff3e0; color: #e65100; }
        .badge-medium { background: #e3f2fd; color: #1565c0; }
        .badge-low { background: #e8f5e9; color: #2e7d32; }

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
            <h1>🎫 Log New Incident</h1>
            <p style="font-size: 18px; opacity: 0.95;">Report an IT incident or service disruption</p>
        </div>

        <div class="card">
            <h2>Incident Details</h2>

            <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?>">
                <?= htmlspecialchars($message) ?>
            </div>
            <?php endif; ?>

            <form method="POST" id="incidentForm">
                <div class="form-group">
                    <label for="title">Incident Title <span class="required">*</span></label>
                    <input type="text" id="title" name="title" required
                           placeholder="Brief description of the issue"
                           value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
                    <div class="help-text">Provide a clear, concise summary of the incident</div>
                </div>

                <div class="form-group">
                    <label for="description">Detailed Description <span class="required">*</span></label>
                    <textarea id="description" name="description" required
                              placeholder="Detailed description of the issue, including error messages, symptoms, and impact"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                    <div class="help-text">Include error messages, steps to reproduce, and what you've already tried</div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="category">Category <span class="required">*</span></label>
                        <select id="category" name="category" required>
                            <option value="">-- Select Category --</option>
                            <option value="Email & Messaging">Email & Messaging</option>
                            <option value="Network">Network</option>
                            <option value="Application">Application</option>
                            <option value="Hardware">Hardware</option>
                            <option value="Database">Database</option>
                            <option value="Security">Security</option>
                            <option value="Access Management">Access Management</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="priority">Priority <span class="required">*</span></label>
                        <select id="priority" name="priority" required>
                            <option value="">-- Select Priority --</option>
                            <option value="Critical">Critical - System Down (4 hours SLA)</option>
                            <option value="High">High - Major Impact (8 hours SLA)</option>
                            <option value="Medium">Medium - Moderate Impact (16 hours SLA)</option>
                            <option value="Low">Low - Minor Issue (24 hours SLA)</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="channel">How are you reporting this? <span class="required">*</span></label>
                        <select id="channel" name="channel" required>
                            <option value="">-- Select Channel --</option>
                            <option value="Web Portal" selected>Web Portal</option>
                            <option value="Email">Email</option>
                            <option value="Phone">Phone</option>
                            <option value="Chat">Chat</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="reported_by">Your Name/Email <span class="required">*</span></label>
                        <input type="text" id="reported_by" name="reported_by" required
                               placeholder="John Doe / john.doe@company.com"
                               value="<?= htmlspecialchars($_POST['reported_by'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="affected_users">Number of Affected Users</label>
                        <input type="number" id="affected_users" name="affected_users"
                               min="1" value="<?= $_POST['affected_users'] ?? 1 ?>">
                        <div class="help-text">How many users are impacted?</div>
                    </div>

                    <div class="form-group">
                        <label for="department">Department</label>
                        <select id="department" name="department">
                            <option value="">-- Select Department --</option>
                            <option value="IT">IT</option>
                            <option value="HR">HR</option>
                            <option value="Finance">Finance</option>
                            <option value="Sales">Sales</option>
                            <option value="Marketing">Marketing</option>
                            <option value="Operations">Operations</option>
                            <option value="Engineering">Engineering</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="business_impact">Business Impact</label>
                    <select id="business_impact" name="business_impact">
                        <option value="">-- Select Impact Level --</option>
                        <option value="Critical">Critical - Business operations stopped</option>
                        <option value="High">High - Major business functions affected</option>
                        <option value="Medium">Medium - Some business functions impacted</option>
                        <option value="Low">Low - Minimal business impact</option>
                    </select>
                    <div class="help-text">How is this affecting business operations?</div>
                </div>

                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">🎫 Submit Incident</button>
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
        document.getElementById('incidentForm').addEventListener('submit', function(e) {
            const title = document.getElementById('title').value.trim();
            const description = document.getElementById('description').value.trim();

            if (title.length < 10) {
                alert('Title must be at least 10 characters long');
                e.preventDefault();
                return false;
            }

            if (description.length < 20) {
                alert('Description must be at least 20 characters long');
                e.preventDefault();
                return false;
            }
        });

        // Auto-select priority based on keywords
        document.getElementById('description').addEventListener('blur', function() {
            const desc = this.value.toLowerCase();
            const priority = document.getElementById('priority');

            if (priority.value === '') {
                if (desc.includes('down') || desc.includes('not working') || desc.includes('crash')) {
                    priority.value = 'Critical';
                } else if (desc.includes('slow') || desc.includes('error') || desc.includes('problem')) {
                    priority.value = 'High';
                }
            }
        });
    </script>
</body>
</html>
