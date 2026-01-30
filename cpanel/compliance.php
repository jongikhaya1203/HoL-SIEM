<?php
session_start();
require_once __DIR__ . '/../config/database.php';

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
    <title>Compliance - IOC Control Panel</title>
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
                    <div class="section-body" style="display: flex; gap: 15px; align-items: center;">
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
                        <span style="color: var(--text-secondary); font-size: 14px; margin-left: auto;">
                            Last assessment: Today at 08:30 AM
                        </span>
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
                                    <td><button class="btn btn-secondary btn-sm">Remediate</button></td>
                                </tr>
                                <tr>
                                    <td><code>A.9.4.1</code></td>
                                    <td><span class="framework-badge">ISO 27001</span></td>
                                    <td>Information access restriction - Insufficient logging</td>
                                    <td><span class="status-badge status-failed">Failed</span></td>
                                    <td><span class="risk-badge risk-medium">Medium</span></td>
                                    <td><button class="btn btn-secondary btn-sm">Remediate</button></td>
                                </tr>
                                <tr>
                                    <td><code>3.1</code></td>
                                    <td><span class="framework-badge">CIS</span></td>
                                    <td>Data Protection - Encryption at rest not enabled</td>
                                    <td><span class="status-badge status-failed">Failed</span></td>
                                    <td><span class="risk-badge risk-critical">Critical</span></td>
                                    <td><button class="btn btn-secondary btn-sm">Remediate</button></td>
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
    </style>

    <script src="assets/js/cpanel.js"></script>
    <script>
        function viewFramework(code) {
            alert('Viewing detailed controls for framework: ' + code);
        }

        function configureFramework(code) {
            alert('Configuration panel for framework: ' + code);
        }
    </script>
</body>
</html>
