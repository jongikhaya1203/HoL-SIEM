<?php
/**
 * Log New Change Request Form
 */

require_once __DIR__ . '/../classes/Database.php';
$db = Database::getInstance();

$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $change_type = $_POST['change_type'] ?? '';
    $impact = $_POST['impact'] ?? '';
    $risk = $_POST['risk'] ?? '';
    $justification = trim($_POST['justification'] ?? '');
    $implementation_plan = trim($_POST['implementation_plan'] ?? '');
    $backout_plan = trim($_POST['backout_plan'] ?? '');
    $requested_by = trim($_POST['requested_by'] ?? '');
    $affected_systems = trim($_POST['affected_systems'] ?? '');
    $downtime_required = $_POST['downtime_required'] ?? 'no';
    $downtime_minutes = intval($_POST['downtime_minutes'] ?? 0);
    $scheduled_date = $_POST['scheduled_date'] ?? '';
    $scheduled_time = $_POST['scheduled_time'] ?? '';

    if ($title && $description && $change_type && $impact && $risk && $requested_by && $implementation_plan && $backout_plan) {
        try {
            // Create changes table if not exists
            $db->query("CREATE TABLE IF NOT EXISTS change_requests (
                id INT AUTO_INCREMENT PRIMARY KEY,
                change_id VARCHAR(50) UNIQUE NOT NULL,
                title VARCHAR(255) NOT NULL,
                description TEXT NOT NULL,
                change_type ENUM('Standard', 'Normal', 'Major', 'Emergency') NOT NULL,
                impact ENUM('Low', 'Medium', 'High', 'Critical') NOT NULL,
                risk ENUM('Low', 'Medium', 'High', 'Critical') NOT NULL,
                status ENUM('Draft', 'Pending Approval', 'Approved', 'Rejected', 'Scheduled', 'In Progress', 'Completed', 'Failed') DEFAULT 'Pending Approval',
                approval_status ENUM('Pending', 'Awaiting CAB', 'Approved', 'Rejected') DEFAULT 'Pending',
                justification TEXT,
                implementation_plan TEXT NOT NULL,
                backout_plan TEXT NOT NULL,
                requested_by VARCHAR(255) NOT NULL,
                affected_systems TEXT,
                downtime_required BOOLEAN DEFAULT FALSE,
                downtime_minutes INT DEFAULT 0,
                scheduled_date DATE,
                scheduled_time TIME,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_status (status),
                INDEX idx_scheduled (scheduled_date)
            )");

            // Generate change ID
            $year = date('Y');
            $result = $db->fetchOne("SELECT COUNT(*) as count FROM change_requests WHERE change_id LIKE 'CHG-{$year}-%'");
            $count = ($result['count'] ?? 0) + 1;
            $change_id = sprintf('CHG-%s-%03d', $year, $count);

            // Determine approval status based on impact and type
            $approval_status = 'Pending';
            if ($impact == 'Critical' || $change_type == 'Major') {
                $approval_status = 'Awaiting CAB';
            }

            // Combine scheduled date and time
            $scheduled_datetime = null;
            if ($scheduled_date && $scheduled_time) {
                $scheduled_datetime = $scheduled_date . ' ' . $scheduled_time;
            }

            // Insert change request
            $db->query(
                "INSERT INTO change_requests (change_id, title, description, change_type, impact, risk,
                 approval_status, justification, implementation_plan, backout_plan, requested_by,
                 affected_systems, downtime_required, downtime_minutes, scheduled_date, scheduled_time)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [$change_id, $title, $description, $change_type, $impact, $risk, $approval_status,
                 $justification, $implementation_plan, $backout_plan, $requested_by, $affected_systems,
                 $downtime_required == 'yes' ? 1 : 0, $downtime_minutes,
                 $scheduled_date ?: null, $scheduled_time ?: null]
            );

            $cab_message = ($approval_status == 'Awaiting CAB') ? ' This change requires CAB approval.' : '';
            $message = "Change Request {$change_id} created successfully!{$cab_message}";
            $messageType = 'success';

            // Clear form
            $_POST = [];
        } catch (Exception $e) {
            $message = 'Error creating change request: ' . $e->getMessage();
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
    <title>Log Change Request - ITSM</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 1000px;
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
            background: #fff3e0;
            border-left: 4px solid #ff9800;
            padding: 15px;
            margin-bottom: 25px;
            border-radius: 6px;
        }
        .info-box h3 {
            color: #e65100;
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
        .form-row-3 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
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
        input[type="time"],
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
        .radio-group {
            display: flex;
            gap: 20px;
            margin-top: 8px;
        }
        .radio-group label {
            display: flex;
            align-items: center;
            font-weight: normal;
        }
        .radio-group input[type="radio"] {
            width: auto;
            margin-right: 8px;
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
        .section-divider {
            margin: 30px 0;
            border-top: 2px dashed #e0e0e0;
            padding-top: 25px;
        }
        .section-title {
            color: #667eea;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
        }

        @media (max-width: 768px) {
            .form-row, .form-row-3 {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋 Log Change Request</h1>
            <p style="font-size: 18px; opacity: 0.95;">Request approval for IT infrastructure or application changes</p>
        </div>

        <div class="card">
            <div class="info-box">
                <h3>⚠️ Change Management Process</h3>
                <ul>
                    <li>All changes must go through proper approval before implementation</li>
                    <li>Critical and Major changes require CAB (Change Advisory Board) approval</li>
                    <li>Emergency changes may bypass normal approval but require post-implementation review</li>
                    <li>Always provide a backout plan in case the change needs to be reversed</li>
                </ul>
            </div>

            <h2>Change Request Details</h2>

            <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?>">
                <?= htmlspecialchars($message) ?>
            </div>
            <?php endif; ?>

            <form method="POST" id="changeForm">
                <!-- Basic Information -->
                <div class="form-group">
                    <label for="title">Change Title <span class="required">*</span></label>
                    <input type="text" id="title" name="title" required
                           placeholder="Brief description of the change"
                           value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
                    <div class="help-text">Example: "Upgrade production database to PostgreSQL 15"</div>
                </div>

                <div class="form-group">
                    <label for="description">Change Description <span class="required">*</span></label>
                    <textarea id="description" name="description" required
                              placeholder="Detailed description of what will be changed and why"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                    <div class="help-text">Describe what systems will be modified and the expected outcome</div>
                </div>

                <div class="form-row-3">
                    <div class="form-group">
                        <label for="change_type">Change Type <span class="required">*</span></label>
                        <select id="change_type" name="change_type" required>
                            <option value="">-- Select Type --</option>
                            <option value="Standard">Standard - Pre-approved</option>
                            <option value="Normal">Normal - Requires approval</option>
                            <option value="Major">Major - Requires CAB</option>
                            <option value="Emergency">Emergency - Expedited</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="impact">Impact Level <span class="required">*</span></label>
                        <select id="impact" name="impact" required>
                            <option value="">-- Select Impact --</option>
                            <option value="Low">Low - Minimal users affected</option>
                            <option value="Medium">Medium - Some users affected</option>
                            <option value="High">High - Many users affected</option>
                            <option value="Critical">Critical - All users affected</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="risk">Risk Level <span class="required">*</span></label>
                        <select id="risk" name="risk" required>
                            <option value="">-- Select Risk --</option>
                            <option value="Low">Low - Minimal risk</option>
                            <option value="Medium">Medium - Moderate risk</option>
                            <option value="High">High - Significant risk</option>
                            <option value="Critical">Critical - Very high risk</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="justification">Business Justification</label>
                    <textarea id="justification" name="justification"
                              placeholder="Why is this change necessary? What business value does it provide?"><?= htmlspecialchars($_POST['justification'] ?? '') ?></textarea>
                    <div class="help-text">Explain the business need and benefits of this change</div>
                </div>

                <!-- Implementation Details -->
                <div class="section-divider">
                    <div class="section-title">📝 Implementation Details</div>
                </div>

                <div class="form-group">
                    <label for="implementation_plan">Implementation Plan <span class="required">*</span></label>
                    <textarea id="implementation_plan" name="implementation_plan" required
                              placeholder="Step-by-step plan for implementing this change"><?= htmlspecialchars($_POST['implementation_plan'] ?? '') ?></textarea>
                    <div class="help-text">Provide detailed steps for implementation</div>
                </div>

                <div class="form-group">
                    <label for="backout_plan">Backout Plan <span class="required">*</span></label>
                    <textarea id="backout_plan" name="backout_plan" required
                              placeholder="Step-by-step plan to reverse this change if it fails"><?= htmlspecialchars($_POST['backout_plan'] ?? '') ?></textarea>
                    <div class="help-text">How will you roll back if something goes wrong?</div>
                </div>

                <div class="form-group">
                    <label for="affected_systems">Affected Systems/Services</label>
                    <input type="text" id="affected_systems" name="affected_systems"
                           placeholder="e.g., Production Database, Web Servers, Email System"
                           value="<?= htmlspecialchars($_POST['affected_systems'] ?? '') ?>">
                    <div class="help-text">List all systems that will be affected by this change</div>
                </div>

                <!-- Downtime Planning -->
                <div class="section-divider">
                    <div class="section-title">⏱️ Downtime & Scheduling</div>
                </div>

                <div class="form-group">
                    <label>Will this change require downtime? <span class="required">*</span></label>
                    <div class="radio-group">
                        <label>
                            <input type="radio" name="downtime_required" value="yes"
                                   <?= ($_POST['downtime_required'] ?? '') == 'yes' ? 'checked' : '' ?>>
                            Yes
                        </label>
                        <label>
                            <input type="radio" name="downtime_required" value="no"
                                   <?= ($_POST['downtime_required'] ?? 'no') == 'no' ? 'checked' : '' ?>>
                            No
                        </label>
                    </div>
                </div>

                <div class="form-group" id="downtime_duration_group" style="display: none;">
                    <label for="downtime_minutes">Expected Downtime Duration (minutes)</label>
                    <input type="number" id="downtime_minutes" name="downtime_minutes"
                           min="0" value="<?= $_POST['downtime_minutes'] ?? 0 ?>">
                    <div class="help-text">Estimated duration of system unavailability</div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="scheduled_date">Preferred Implementation Date</label>
                        <input type="date" id="scheduled_date" name="scheduled_date"
                               value="<?= $_POST['scheduled_date'] ?? '' ?>"
                               min="<?= date('Y-m-d') ?>">
                    </div>

                    <div class="form-group">
                        <label for="scheduled_time">Preferred Implementation Time</label>
                        <input type="time" id="scheduled_time" name="scheduled_time"
                               value="<?= $_POST['scheduled_time'] ?? '' ?>">
                    </div>
                </div>

                <!-- Requester Information -->
                <div class="section-divider">
                    <div class="section-title">👤 Requester Information</div>
                </div>

                <div class="form-group">
                    <label for="requested_by">Requested By <span class="required">*</span></label>
                    <input type="text" id="requested_by" name="requested_by" required
                           placeholder="Your name and department"
                           value="<?= htmlspecialchars($_POST['requested_by'] ?? '') ?>">
                </div>

                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">📋 Submit Change Request</button>
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
        // Show/hide downtime duration based on selection
        document.querySelectorAll('input[name="downtime_required"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const downtimeGroup = document.getElementById('downtime_duration_group');
                if (this.value === 'yes') {
                    downtimeGroup.style.display = 'block';
                } else {
                    downtimeGroup.style.display = 'none';
                    document.getElementById('downtime_minutes').value = 0;
                }
            });
        });

        // Initialize on page load
        if (document.querySelector('input[name="downtime_required"]:checked')?.value === 'yes') {
            document.getElementById('downtime_duration_group').style.display = 'block';
        }

        // Form validation
        document.getElementById('changeForm').addEventListener('submit', function(e) {
            const title = document.getElementById('title').value.trim();
            const implementationPlan = document.getElementById('implementation_plan').value.trim();
            const backoutPlan = document.getElementById('backout_plan').value.trim();

            if (title.length < 15) {
                alert('Title must be at least 15 characters long');
                e.preventDefault();
                return false;
            }

            if (implementationPlan.length < 30) {
                alert('Implementation plan must be at least 30 characters long');
                e.preventDefault();
                return false;
            }

            if (backoutPlan.length < 20) {
                alert('Backout plan must be at least 20 characters long');
                e.preventDefault();
                return false;
            }
        });

        // Auto-suggest risk based on impact
        document.getElementById('impact').addEventListener('change', function() {
            const risk = document.getElementById('risk');
            if (risk.value === '') {
                risk.value = this.value;
            }
        });
    </script>
</body>
</html>
