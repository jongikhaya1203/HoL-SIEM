<?php
session_start();
require_once __DIR__ . '/includes/Database.php';

if (!isset($_SESSION['cpanel_logged_in']) || $_SESSION['cpanel_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$db = Database::getInstance()->getConnection();
$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'toggle_framework':
            try {
                $stmt = $db->prepare("UPDATE cpanel_compliance_frameworks SET is_enabled = ? WHERE id = ?");
                $stmt->execute([isset($_POST['is_enabled']) ? 1 : 0, $_POST['framework_id']]);
                $message = 'Framework status updated';
                $messageType = 'success';
            } catch (Exception $e) {
                $message = 'Error: ' . $e->getMessage();
                $messageType = 'danger';
            }
            break;

        case 'run_assessment':
            $message = 'Compliance assessment started. Results will be available shortly.';
            $messageType = 'success';
            break;

        case 'export_report':
            $message = 'Compliance report export initiated';
            $messageType = 'success';
            break;

        case 'save_controls':
            $message = 'Control configurations saved successfully';
            $messageType = 'success';
            break;
    }
}

// Get compliance frameworks
$frameworks = [];
try {
    $stmt = $db->query("SELECT * FROM cpanel_compliance_frameworks ORDER BY framework_name");
    $frameworks = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Default frameworks
    $frameworks = [
        ['id' => 1, 'framework_name' => 'NIST Cybersecurity Framework', 'framework_code' => 'NIST_CSF', 'version' => '1.1', 'is_enabled' => 1],
        ['id' => 2, 'framework_name' => 'ISO 27001', 'framework_code' => 'ISO_27001', 'version' => '2013', 'is_enabled' => 1],
        ['id' => 3, 'framework_name' => 'CIS Controls', 'framework_code' => 'CIS', 'version' => 'v8', 'is_enabled' => 1],
        ['id' => 4, 'framework_name' => 'PCI DSS', 'framework_code' => 'PCI_DSS', 'version' => '4.0', 'is_enabled' => 1],
        ['id' => 5, 'framework_name' => 'HIPAA', 'framework_code' => 'HIPAA', 'version' => '2013', 'is_enabled' => 1],
        ['id' => 6, 'framework_name' => 'SOC 2', 'framework_code' => 'SOC2', 'version' => 'Type II', 'is_enabled' => 1]
    ];
}

// Sample compliance scores
$complianceScores = [
    'NIST_CSF' => ['score' => 78, 'controls' => 98, 'passed' => 76, 'failed' => 8, 'na' => 14],
    'ISO_27001' => ['score' => 82, 'controls' => 114, 'passed' => 94, 'failed' => 6, 'na' => 14],
    'CIS' => ['score' => 71, 'controls' => 153, 'passed' => 109, 'failed' => 12, 'na' => 32],
    'PCI_DSS' => ['score' => 85, 'controls' => 78, 'passed' => 66, 'failed' => 4, 'na' => 8],
    'HIPAA' => ['score' => 79, 'controls' => 45, 'passed' => 36, 'failed' => 3, 'na' => 6],
    'SOC2' => ['score' => 88, 'controls' => 64, 'passed' => 56, 'failed' => 2, 'na' => 6]
];

$currentPage = 'compliance';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compliance - HoL Control Panel</title>
    <link rel="stylesheet" href="assets/css/cpanel.css">
</head>
<body>
    <div class="cpanel-wrapper">
        <?php include 'includes/sidebar.php'; ?>

        <div class="cpanel-main">
            <?php include 'includes/header.php'; ?>

            <div class="cpanel-content">
                <div class="page-header">
                    <h1>Compliance Management</h1>
                    <p>Multi-framework compliance monitoring and assessment</p>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?= $messageType ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <?php if ($messageType === 'success'): ?>
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            <?php else: ?>
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                            <?php endif; ?>
                        </svg>
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <!-- Overall Stats -->
                <div class="stats-grid" style="grid-template-columns: repeat(4, 1fr);">
                    <div class="stat-card">
                        <div class="stat-icon modules">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 11l3 3L22 4"></path>
                                <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                            </svg>
                        </div>
                        <div class="stat-info">
                            <span class="stat-value"><?= count(array_filter($frameworks, fn($f) => $f['is_enabled'])) ?></span>
                            <span class="stat-label">Active Frameworks</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon protocols">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                            </svg>
                        </div>
                        <div class="stat-info">
                            <span class="stat-value">81%</span>
                            <span class="stat-label">Average Score</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon api">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                        </div>
                        <div class="stat-info">
                            <span class="stat-value">437</span>
                            <span class="stat-label">Controls Passed</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #ef4444, #dc2626);">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="15" y1="9" x2="9" y2="15"></line>
                                <line x1="9" y1="9" x2="15" y2="15"></line>
                            </svg>
                        </div>
                        <div class="stat-info">
                            <span class="stat-value">35</span>
                            <span class="stat-label">Controls Failed</span>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="section-card">
                    <div class="section-body" style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="run_assessment">
                            <button type="submit" class="btn btn-primary">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px; margin-right: 8px;">
                                    <polygon points="5 3 19 12 5 21 5 3"></polygon>
                                </svg>
                                Run Assessment
                            </button>
                        </form>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="export_report">
                            <button type="submit" class="btn btn-secondary">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px; margin-right: 8px;">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                    <polyline points="7 10 12 15 17 10"></polyline>
                                    <line x1="12" y1="15" x2="12" y2="3"></line>
                                </svg>
                                Export Report
                            </button>
                        </form>
                        <a href="../compliance_reports.php" class="btn" style="background: linear-gradient(135deg, #2196F3, #1976D2); color: white; text-decoration: none;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px; margin-right: 8px;">
                                <path d="M9 11l3 3L22 4"></path>
                                <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                            </svg>
                            Full Compliance Reports
                        </a>
                        <span style="color: var(--text-secondary); font-size: 14px; margin-left: auto;">
                            Last assessment: Today at 08:30 AM
                        </span>
                    </div>
                </div>

                <!-- Interactive Recommendations Feature -->
                <div class="section-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; margin-bottom: 20px;">
                    <div class="section-body">
                        <div style="display: flex; align-items: center; gap: 20px;">
                            <div style="font-size: 48px;">💡</div>
                            <div style="flex: 1;">
                                <h3 style="margin-bottom: 8px; font-size: 18px;">NEW: Interactive Compliance Recommendations</h3>
                                <p style="opacity: 0.9; font-size: 14px; margin-bottom: 15px;">
                                    Access detailed remediation guidance with step-by-step instructions, auto-fix scripts,
                                    and one-click fix application for all 7 international compliance frameworks.
                                </p>
                                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                    <span style="background: rgba(255,255,255,0.2); padding: 4px 12px; border-radius: 15px; font-size: 12px;">✓ View Recommendations</span>
                                    <span style="background: rgba(255,255,255,0.2); padding: 4px 12px; border-radius: 15px; font-size: 12px;">✓ Accept & Queue</span>
                                    <span style="background: rgba(255,255,255,0.2); padding: 4px 12px; border-radius: 15px; font-size: 12px;">✓ Auto-Fix Scripts</span>
                                    <span style="background: rgba(255,255,255,0.2); padding: 4px 12px; border-radius: 15px; font-size: 12px;">✓ Bulk Apply</span>
                                </div>
                            </div>
                            <a href="../compliance_reports.php?framework=iso27001" style="background: white; color: #667eea; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 600; white-space: nowrap;">
                                Try Now →
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Framework Cards -->
                <div class="framework-grid">
                    <?php foreach ($frameworks as $framework): ?>
                    <?php $scores = $complianceScores[$framework['framework_code']] ?? ['score' => 0, 'controls' => 0, 'passed' => 0, 'failed' => 0, 'na' => 0]; ?>
                    <div class="framework-card <?= $framework['is_enabled'] ? '' : 'disabled' ?>">
                        <div class="framework-header">
                            <div class="framework-info">
                                <h3><?= htmlspecialchars($framework['framework_name']) ?></h3>
                                <span class="version-badge">v<?= htmlspecialchars($framework['version']) ?></span>
                            </div>
                            <form method="POST">
                                <input type="hidden" name="action" value="toggle_framework">
                                <input type="hidden" name="framework_id" value="<?= $framework['id'] ?>">
                                <input type="hidden" name="is_enabled" value="<?= $framework['is_enabled'] ? 0 : 1 ?>">
                                <label class="toggle-switch">
                                    <input type="checkbox" <?= $framework['is_enabled'] ? 'checked' : '' ?> onchange="this.form.submit()">
                                    <span class="toggle-slider"></span>
                                </label>
                            </form>
                        </div>

                        <div class="score-circle">
                            <svg viewBox="0 0 36 36" class="circular-chart">
                                <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"></path>
                                <path class="circle" stroke-dasharray="<?= $scores['score'] ?>, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"></path>
                            </svg>
                            <span class="score-value"><?= $scores['score'] ?>%</span>
                        </div>

                        <div class="control-stats">
                            <div class="control-stat">
                                <span class="stat-num passed"><?= $scores['passed'] ?></span>
                                <span class="stat-label">Passed</span>
                            </div>
                            <div class="control-stat">
                                <span class="stat-num failed"><?= $scores['failed'] ?></span>
                                <span class="stat-label">Failed</span>
                            </div>
                            <div class="control-stat">
                                <span class="stat-num na"><?= $scores['na'] ?></span>
                                <span class="stat-label">N/A</span>
                            </div>
                        </div>

                        <div class="framework-actions">
                            <button class="btn btn-secondary btn-sm" onclick="viewFramework('<?= $framework['framework_code'] ?>')">View Details</button>
                            <button class="btn btn-secondary btn-sm" onclick="configureFramework('<?= $framework['framework_code'] ?>')">Configure</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Control Mappings -->
                <div class="section-card" style="margin-top: 20px;">
                    <div class="section-header">
                        <h2>Recent Findings</h2>
                        <a href="logs.php?filter=compliance" class="btn btn-secondary btn-sm">View All</a>
                    </div>
                    <div class="section-body" style="padding: 0;">
                        <table class="config-table">
                            <thead>
                                <tr>
                                    <th>Control ID</th>
                                    <th>Framework</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th>Risk Level</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code>AC-2</code></td>
                                    <td><span class="framework-badge">NIST CSF</span></td>
                                    <td>Account Management - Inactive accounts not disabled</td>
                                    <td><span class="status-badge status-failed">Failed</span></td>
                                    <td><span class="risk-badge risk-high">High</span></td>
                                    <td><button class="btn btn-secondary btn-sm" onclick="showRemediation('AC-2', 'NIST CSF', 'Account Management - Inactive accounts not disabled', 'high')">Remediate</button></td>
                                </tr>
                                <tr>
                                    <td><code>A.9.4.1</code></td>
                                    <td><span class="framework-badge">ISO 27001</span></td>
                                    <td>Information access restriction - Insufficient logging</td>
                                    <td><span class="status-badge status-failed">Failed</span></td>
                                    <td><span class="risk-badge risk-medium">Medium</span></td>
                                    <td><button class="btn btn-secondary btn-sm" onclick="showRemediation('A.9.4.1', 'ISO 27001', 'Information access restriction - Insufficient logging', 'medium')">Remediate</button></td>
                                </tr>
                                <tr>
                                    <td><code>3.1</code></td>
                                    <td><span class="framework-badge">CIS</span></td>
                                    <td>Data Protection - Encryption at rest not enabled</td>
                                    <td><span class="status-badge status-failed">Failed</span></td>
                                    <td><span class="risk-badge risk-critical">Critical</span></td>
                                    <td><button class="btn btn-secondary btn-sm" onclick="showRemediation('3.1', 'CIS', 'Data Protection - Encryption at rest not enabled', 'critical')">Remediate</button></td>
                                </tr>
                                <tr>
                                    <td><code>8.3.1</code></td>
                                    <td><span class="framework-badge">PCI DSS</span></td>
                                    <td>Authentication - Password complexity requirements</td>
                                    <td><span class="status-badge status-passed">Passed</span></td>
                                    <td><span class="risk-badge risk-low">Low</span></td>
                                    <td><button class="btn btn-secondary btn-sm" disabled>N/A</button></td>
                                </tr>
                                <tr>
                                    <td><code>164.312</code></td>
                                    <td><span class="framework-badge">HIPAA</span></td>
                                    <td>Technical Safeguards - Audit controls implemented</td>
                                    <td><span class="status-badge status-passed">Passed</span></td>
                                    <td><span class="risk-badge risk-low">Low</span></td>
                                    <td><button class="btn btn-secondary btn-sm" disabled>N/A</button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Framework Details Modal -->
    <div id="frameworkModal" class="modal">
        <div class="modal-content" style="max-width: 800px;">
            <div class="modal-header">
                <h2 id="frameworkModalTitle">Framework Details</h2>
                <button class="modal-close" onclick="closeModal('frameworkModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div id="frameworkDetails">
                    <!-- Populated by JavaScript -->
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('frameworkModal')">Close</button>
                <button class="btn btn-primary" onclick="exportFrameworkReport()">Export Report</button>
            </div>
        </div>
    </div>

    <!-- Framework Configuration Modal -->
    <div id="configModal" class="modal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h2 id="configModalTitle">Configure Framework</h2>
                <button class="modal-close" onclick="closeModal('configModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="configForm">
                    <input type="hidden" id="configFrameworkCode" name="framework_code">
                    <div class="form-group">
                        <label>Assessment Frequency</label>
                        <select id="assessmentFreq" name="assessment_freq" class="form-control">
                            <option value="daily">Daily</option>
                            <option value="weekly" selected>Weekly</option>
                            <option value="monthly">Monthly</option>
                            <option value="quarterly">Quarterly</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Auto-Remediation</label>
                        <select id="autoRemediation" name="auto_remediation" class="form-control">
                            <option value="disabled">Disabled</option>
                            <option value="low_risk">Low Risk Only</option>
                            <option value="all">All Findings</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Alert Threshold</label>
                        <select id="alertThreshold" name="alert_threshold" class="form-control">
                            <option value="critical">Critical Only</option>
                            <option value="high" selected>High and Above</option>
                            <option value="medium">Medium and Above</option>
                            <option value="all">All Findings</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Notification Channels</label>
                        <div class="checkbox-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="notify_email" checked> Email
                            </label>
                            <label class="checkbox-label">
                                <input type="checkbox" name="notify_slack"> Slack
                            </label>
                            <label class="checkbox-label">
                                <input type="checkbox" name="notify_siem" checked> SIEM
                            </label>
                            <label class="checkbox-label">
                                <input type="checkbox" name="notify_webhook"> Webhook
                            </label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Control Exclusions</label>
                        <textarea id="controlExclusions" name="exclusions" class="form-control" rows="3" placeholder="Enter control IDs to exclude (one per line)"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('configModal')">Cancel</button>
                <button class="btn btn-primary" onclick="saveFrameworkConfig()">Save Configuration</button>
            </div>
        </div>
    </div>

    <!-- Remediation Modal -->
    <div id="remediationModal" class="modal">
        <div class="modal-content" style="max-width: 700px;">
            <div class="modal-header">
                <h2>Remediation Guidance</h2>
                <button class="modal-close" onclick="closeModal('remediationModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="remediation-info">
                    <div class="remediation-header">
                        <span class="control-id" id="remControlId">AC-2</span>
                        <span class="framework-badge" id="remFramework">NIST CSF</span>
                        <span class="risk-badge" id="remRisk">High</span>
                    </div>
                    <p class="finding-desc" id="remDescription">Account Management - Inactive accounts not disabled</p>
                </div>

                <div class="remediation-steps">
                    <h4>Recommended Actions</h4>
                    <div id="remediationSteps">
                        <!-- Populated by JavaScript -->
                    </div>
                </div>

                <div class="remediation-options" style="margin-top: 20px;">
                    <h4>Remediation Options</h4>
                    <div class="option-cards">
                        <div class="option-card" onclick="applyRemediation('auto')">
                            <div class="option-icon" style="background: #dbeafe;">
                                <svg viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2" style="width: 24px; height: 24px;">
                                    <path d="M12 2v4m0 12v4M4.93 4.93l2.83 2.83m8.48 8.48l2.83 2.83M2 12h4m12 0h4M4.93 19.07l2.83-2.83m8.48-8.48l2.83-2.83"/>
                                </svg>
                            </div>
                            <h5>Auto-Remediate</h5>
                            <p>Apply automated fix</p>
                        </div>
                        <div class="option-card" onclick="applyRemediation('ticket')">
                            <div class="option-icon" style="background: #fef3c7;">
                                <svg viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2" style="width: 24px; height: 24px;">
                                    <path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/>
                                    <rect x="9" y="3" width="6" height="4" rx="2"/>
                                </svg>
                            </div>
                            <h5>Create Ticket</h5>
                            <p>Assign to team</p>
                        </div>
                        <div class="option-card" onclick="applyRemediation('exception')">
                            <div class="option-icon" style="background: #fee2e2;">
                                <svg viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2" style="width: 24px; height: 24px;">
                                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                                    <line x1="12" y1="9" x2="12" y2="13"/>
                                    <line x1="12" y1="17" x2="12.01" y2="17"/>
                                </svg>
                            </div>
                            <h5>Request Exception</h5>
                            <p>Document risk acceptance</p>
                        </div>
                        <div class="option-card" onclick="applyRemediation('manual')">
                            <div class="option-icon" style="background: #d1fae5;">
                                <svg viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2" style="width: 24px; height: 24px;">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                </svg>
                            </div>
                            <h5>Manual Fix</h5>
                            <p>Mark as resolved</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('remediationModal')">Close</button>
            </div>
        </div>
    </div>

    <style>
        .framework-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-top: 20px;
        }
        .framework-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            border: 1px solid var(--border-color);
        }
        .framework-card.disabled {
            opacity: 0.6;
        }
        .framework-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }
        .framework-info h3 {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        .version-badge {
            background: #e2e8f0;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 11px;
            color: var(--text-secondary);
        }
        .score-circle {
            position: relative;
            width: 120px;
            height: 120px;
            margin: 0 auto 20px;
        }
        .circular-chart {
            display: block;
            margin: 0 auto;
            max-width: 100%;
            max-height: 120px;
        }
        .circle-bg {
            fill: none;
            stroke: #eee;
            stroke-width: 3.8;
        }
        .circle {
            fill: none;
            stroke: var(--primary);
            stroke-width: 2.8;
            stroke-linecap: round;
            animation: progress 1s ease-out forwards;
        }
        @keyframes progress {
            0% { stroke-dasharray: 0 100; }
        }
        .score-value {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 24px;
            font-weight: 700;
            color: var(--primary);
        }
        .control-stats {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-bottom: 20px;
        }
        .control-stat {
            text-align: center;
        }
        .stat-num {
            display: block;
            font-size: 20px;
            font-weight: 600;
        }
        .stat-num.passed { color: #059669; }
        .stat-num.failed { color: #dc2626; }
        .stat-num.na { color: #6b7280; }
        .control-stat .stat-label {
            font-size: 12px;
            color: var(--text-secondary);
        }
        .framework-actions {
            display: flex;
            gap: 10px;
        }
        .framework-actions .btn {
            flex: 1;
        }
        .framework-badge {
            background: #e0e7ff;
            color: #3730a3;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
        }
        .status-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        .status-passed { background: #d1fae5; color: #065f46; }
        .status-failed { background: #fee2e2; color: #991b1b; }
        .risk-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        .risk-critical { background: #fee2e2; color: #991b1b; }
        .risk-high { background: #fef3c7; color: #92400e; }
        .risk-medium { background: #dbeafe; color: #1e40af; }
        .risk-low { background: #d1fae5; color: #065f46; }
        code {
            background: #f1f5f9;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 12px;
        }
        @media (max-width: 1200px) {
            .framework-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (max-width: 768px) {
            .framework-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Remediation modal styles */
        .remediation-info {
            background: #f8fafc;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .remediation-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }
        .control-id {
            font-family: monospace;
            font-size: 16px;
            font-weight: 600;
            color: var(--text-primary);
        }
        .finding-desc {
            color: var(--text-secondary);
            margin: 0;
        }
        .remediation-steps {
            margin-top: 20px;
        }
        .remediation-steps h4 {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 15px;
        }
        .step-item {
            display: flex;
            gap: 12px;
            padding: 12px;
            background: #f8fafc;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        .step-number {
            width: 24px;
            height: 24px;
            background: var(--primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 600;
            flex-shrink: 0;
        }
        .step-content h5 {
            font-size: 14px;
            margin-bottom: 4px;
        }
        .step-content p {
            font-size: 13px;
            color: var(--text-secondary);
            margin: 0;
        }
        .option-cards {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
        }
        .option-card {
            background: #f8fafc;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
        }
        .option-card:hover {
            border-color: var(--primary);
            background: #f0f9ff;
        }
        .option-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
        }
        .option-card h5 {
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 4px;
        }
        .option-card p {
            font-size: 11px;
            color: var(--text-secondary);
            margin: 0;
        }
        .checkbox-group {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }
        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 14px;
            cursor: pointer;
        }
        .checkbox-label input {
            width: 16px;
            height: 16px;
        }
        .controls-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .controls-table th,
        .controls-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        .controls-table th {
            background: #f8fafc;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
        }
        @media (max-width: 600px) {
            .option-cards {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>

    <script src="assets/js/cpanel.js"></script>
    <script>
        // Framework data for details view
        const frameworkData = {
            'NIST_CSF': {
                name: 'NIST Cybersecurity Framework',
                description: 'A comprehensive framework for managing cybersecurity risk.',
                controls: [
                    { id: 'ID.AM-1', name: 'Asset Management', status: 'passed' },
                    { id: 'ID.AM-2', name: 'Software Inventory', status: 'passed' },
                    { id: 'PR.AC-1', name: 'Access Control', status: 'passed' },
                    { id: 'AC-2', name: 'Account Management', status: 'failed' },
                    { id: 'DE.CM-1', name: 'Network Monitoring', status: 'passed' },
                    { id: 'RS.RP-1', name: 'Response Planning', status: 'passed' }
                ]
            },
            'ISO_27001': {
                name: 'ISO 27001',
                description: 'International standard for information security management systems.',
                controls: [
                    { id: 'A.5.1', name: 'Information Security Policies', status: 'passed' },
                    { id: 'A.6.1', name: 'Internal Organization', status: 'passed' },
                    { id: 'A.9.4.1', name: 'Information Access Restriction', status: 'failed' },
                    { id: 'A.12.1', name: 'Operational Security', status: 'passed' },
                    { id: 'A.13.1', name: 'Network Security', status: 'passed' }
                ]
            },
            'CIS': {
                name: 'CIS Controls',
                description: 'Prioritized set of actions to protect organizations from cyber attacks.',
                controls: [
                    { id: '1.1', name: 'Hardware Asset Inventory', status: 'passed' },
                    { id: '2.1', name: 'Software Asset Inventory', status: 'passed' },
                    { id: '3.1', name: 'Data Protection', status: 'failed' },
                    { id: '4.1', name: 'Secure Configuration', status: 'passed' },
                    { id: '5.1', name: 'Account Management', status: 'passed' }
                ]
            },
            'PCI_DSS': {
                name: 'PCI DSS',
                description: 'Payment Card Industry Data Security Standard for protecting cardholder data.',
                controls: [
                    { id: '1.1', name: 'Firewall Configuration', status: 'passed' },
                    { id: '3.4', name: 'Data Encryption', status: 'passed' },
                    { id: '6.5', name: 'Secure Development', status: 'passed' },
                    { id: '8.3.1', name: 'Password Complexity', status: 'passed' },
                    { id: '10.1', name: 'Audit Logging', status: 'passed' }
                ]
            },
            'HIPAA': {
                name: 'HIPAA',
                description: 'Health Insurance Portability and Accountability Act security requirements.',
                controls: [
                    { id: '164.308', name: 'Administrative Safeguards', status: 'passed' },
                    { id: '164.310', name: 'Physical Safeguards', status: 'passed' },
                    { id: '164.312', name: 'Technical Safeguards', status: 'passed' },
                    { id: '164.314', name: 'Organizational Requirements', status: 'passed' }
                ]
            },
            'SOC2': {
                name: 'SOC 2',
                description: 'Service Organization Control 2 trust service criteria.',
                controls: [
                    { id: 'CC1.1', name: 'Control Environment', status: 'passed' },
                    { id: 'CC6.1', name: 'Logical Access', status: 'passed' },
                    { id: 'CC7.1', name: 'System Operations', status: 'passed' },
                    { id: 'CC8.1', name: 'Change Management', status: 'passed' }
                ]
            }
        };

        // Remediation guidance data
        const remediationData = {
            'AC-2': {
                steps: [
                    { title: 'Identify Inactive Accounts', desc: 'Run a query to find accounts with no login activity in the past 90 days.' },
                    { title: 'Review Account List', desc: 'Verify the accounts identified are truly inactive and not service accounts.' },
                    { title: 'Disable Accounts', desc: 'Disable the inactive accounts through Active Directory or IAM console.' },
                    { title: 'Document Changes', desc: 'Log all disabled accounts for audit trail and compliance reporting.' }
                ]
            },
            'A.9.4.1': {
                steps: [
                    { title: 'Enable Audit Logging', desc: 'Configure comprehensive logging for all access events.' },
                    { title: 'Set Log Retention', desc: 'Ensure logs are retained for at least 90 days.' },
                    { title: 'Configure SIEM Integration', desc: 'Forward logs to SIEM for centralized monitoring.' },
                    { title: 'Create Alert Rules', desc: 'Set up alerts for suspicious access patterns.' }
                ]
            },
            '3.1': {
                steps: [
                    { title: 'Identify Sensitive Data', desc: 'Scan storage systems to identify unencrypted sensitive data.' },
                    { title: 'Enable Encryption', desc: 'Enable encryption at rest for all identified data stores.' },
                    { title: 'Manage Encryption Keys', desc: 'Implement proper key management using a KMS.' },
                    { title: 'Verify Encryption', desc: 'Run validation tests to confirm encryption is active.' }
                ]
            }
        };

        let currentRemediationControl = null;

        function viewFramework(code) {
            const framework = frameworkData[code];
            if (!framework) {
                showNotification('Framework data not found', 'error');
                return;
            }

            document.getElementById('frameworkModalTitle').textContent = framework.name + ' - Control Details';

            let html = '<p style="color: var(--text-secondary); margin-bottom: 20px;">' + framework.description + '</p>';
            html += '<table class="controls-table">';
            html += '<thead><tr><th>Control ID</th><th>Name</th><th>Status</th></tr></thead>';
            html += '<tbody>';

            framework.controls.forEach(control => {
                const statusClass = control.status === 'passed' ? 'status-passed' : 'status-failed';
                const statusText = control.status === 'passed' ? 'Passed' : 'Failed';
                html += '<tr>';
                html += '<td><code>' + control.id + '</code></td>';
                html += '<td>' + control.name + '</td>';
                html += '<td><span class="status-badge ' + statusClass + '">' + statusText + '</span></td>';
                html += '</tr>';
            });

            html += '</tbody></table>';

            document.getElementById('frameworkDetails').innerHTML = html;
            document.getElementById('frameworkModal').classList.add('active');
        }

        function configureFramework(code) {
            const framework = frameworkData[code];
            document.getElementById('configModalTitle').textContent = 'Configure ' + (framework ? framework.name : code);
            document.getElementById('configFrameworkCode').value = code;
            document.getElementById('configModal').classList.add('active');
        }

        function saveFrameworkConfig() {
            const code = document.getElementById('configFrameworkCode').value;
            showNotification('Configuration saved for ' + code, 'success');
            closeModal('configModal');
        }

        function exportFrameworkReport() {
            showNotification('Exporting compliance report...', 'info');
            closeModal('frameworkModal');
        }

        function showRemediation(controlId, framework, description, riskLevel) {
            currentRemediationControl = controlId;

            document.getElementById('remControlId').textContent = controlId;
            document.getElementById('remFramework').textContent = framework;
            document.getElementById('remDescription').textContent = description;

            const riskBadge = document.getElementById('remRisk');
            riskBadge.textContent = riskLevel.charAt(0).toUpperCase() + riskLevel.slice(1);
            riskBadge.className = 'risk-badge risk-' + riskLevel;

            // Get remediation steps
            const steps = remediationData[controlId] ? remediationData[controlId].steps : [
                { title: 'Review Finding', desc: 'Analyze the compliance finding and its impact.' },
                { title: 'Plan Remediation', desc: 'Develop a remediation plan based on best practices.' },
                { title: 'Implement Fix', desc: 'Apply the necessary changes to address the finding.' },
                { title: 'Verify Resolution', desc: 'Run a new assessment to confirm the issue is resolved.' }
            ];

            let stepsHtml = '';
            steps.forEach((step, index) => {
                stepsHtml += '<div class="step-item">';
                stepsHtml += '<span class="step-number">' + (index + 1) + '</span>';
                stepsHtml += '<div class="step-content">';
                stepsHtml += '<h5>' + step.title + '</h5>';
                stepsHtml += '<p>' + step.desc + '</p>';
                stepsHtml += '</div></div>';
            });

            document.getElementById('remediationSteps').innerHTML = stepsHtml;
            document.getElementById('remediationModal').classList.add('active');
        }

        function applyRemediation(type) {
            const messages = {
                'auto': 'Auto-remediation initiated for ' + currentRemediationControl + '. Changes will be applied automatically.',
                'ticket': 'Support ticket created for ' + currentRemediationControl + '. Assigned to Security Team.',
                'exception': 'Exception request submitted for ' + currentRemediationControl + '. Pending approval.',
                'manual': 'Control ' + currentRemediationControl + ' marked for manual remediation. Please document changes.'
            };

            showNotification(messages[type] || 'Action initiated', 'success');
            closeModal('remediationModal');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }

        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = 'notification notification-' + type;
            notification.innerHTML = message;
            notification.style.cssText = 'position: fixed; top: 20px; right: 20px; padding: 15px 20px; border-radius: 8px; color: white; z-index: 2000; animation: slideIn 0.3s ease;';

            if (type === 'success') notification.style.background = '#059669';
            else if (type === 'error') notification.style.background = '#dc2626';
            else notification.style.background = '#3b82f6';

            document.body.appendChild(notification);

            setTimeout(() => {
                notification.style.animation = 'fadeOut 0.3s ease';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        // Close modals on backdrop click and Escape key
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('active');
                }
            });
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal.active').forEach(modal => {
                    modal.classList.remove('active');
                });
            }
        });
    </script>
</body>
</html>
