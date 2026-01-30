<?php
/**
 * Training Center
 * Comprehensive training portal for users, admins, and super users
 * Covers installation, operation, configuration, and report generation
 */

require_once __DIR__ . '/classes/Database.php';

// Get app settings
try {
    $db = Database::getInstance();
    $settings_result = $db->fetchAll("SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ('app_name')");
    $app_name = 'IOC Intelligent Operating Centre';
    foreach ($settings_result as $row) {
        if ($row['setting_key'] === 'app_name') $app_name = $row['setting_value'];
    }
} catch (Exception $e) {
    $app_name = 'IOC Intelligent Operating Centre';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Training Center | <?= htmlspecialchars($app_name) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --primary: #00b894;
            --primary-dark: #00a885;
            --secondary: #00cec9;
            --bg-dark: #1a1a2e;
            --bg-card: #16213e;
            --bg-lighter: #1f2b47;
            --text: #e2e8f0;
            --text-muted: #94a3b8;
            --border: #2d3748;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg-dark);
            color: var(--text);
            min-height: 100vh;
        }

        .layout {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 280px;
            background: var(--bg-card);
            border-right: 1px solid var(--border);
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            overflow-y: auto;
            z-index: 100;
        }

        .sidebar-header {
            padding: 25px 20px;
            border-bottom: 1px solid var(--border);
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
        }

        .sidebar-header h1 {
            font-size: 20px;
            color: white;
            margin-bottom: 5px;
        }

        .sidebar-header p {
            font-size: 12px;
            color: rgba(255,255,255,0.8);
        }

        .sidebar-nav {
            padding: 15px 0;
        }

        .nav-section {
            padding: 10px 20px;
            font-size: 11px;
            text-transform: uppercase;
            color: var(--text-muted);
            font-weight: 600;
            letter-spacing: 1px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 20px;
            color: var(--text);
            text-decoration: none;
            transition: all 0.3s;
            border-left: 3px solid transparent;
            cursor: pointer;
        }

        .nav-item:hover {
            background: var(--bg-lighter);
            border-left-color: var(--primary);
        }

        .nav-item.active {
            background: var(--bg-lighter);
            border-left-color: var(--primary);
            color: var(--primary);
        }

        .nav-item .icon { font-size: 18px; }
        .nav-item .label { font-size: 14px; }

        .nav-item .badge {
            margin-left: auto;
            background: var(--primary);
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 11px;
        }

        .nav-item .badge.new {
            background: linear-gradient(135deg, #f59e0b, #ef4444);
            animation: pulse-badge 2s infinite;
        }

        @keyframes pulse-badge {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }

        .sidebar-footer {
            padding: 20px;
            border-top: 1px solid var(--border);
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: var(--bg-card);
        }

        .back-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 20px;
            background: var(--bg-lighter);
            color: var(--text);
            text-decoration: none;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }

        .back-btn:hover {
            background: var(--primary);
            color: white;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 30px;
        }

        .content-header {
            margin-bottom: 30px;
        }

        .content-header h2 {
            font-size: 28px;
            margin-bottom: 10px;
            color: var(--text);
        }

        .content-header p {
            color: var(--text-muted);
            font-size: 15px;
        }

        .breadcrumb {
            display: flex;
            gap: 10px;
            font-size: 13px;
            color: var(--text-muted);
            margin-bottom: 10px;
        }

        .breadcrumb a {
            color: var(--primary);
            text-decoration: none;
        }

        /* Training Module */
        .module {
            display: none;
        }

        .module.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Cards */
        .card {
            background: var(--bg-card);
            border-radius: 12px;
            border: 1px solid var(--border);
            margin-bottom: 25px;
            overflow: hidden;
        }

        .card-header {
            padding: 20px 25px;
            border-bottom: 1px solid var(--border);
            background: var(--bg-lighter);
        }

        .card-header h3 {
            font-size: 18px;
            margin-bottom: 5px;
        }

        .card-header p {
            font-size: 13px;
            color: var(--text-muted);
        }

        .card-body {
            padding: 25px;
        }

        /* Role Cards */
        .role-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .role-card {
            background: var(--bg-card);
            border-radius: 12px;
            border: 2px solid var(--border);
            padding: 25px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .role-card:hover {
            border-color: var(--primary);
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 184, 148, 0.2);
        }

        .role-card.selected {
            border-color: var(--primary);
            background: rgba(0, 184, 148, 0.1);
        }

        .role-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }

        .role-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .role-desc {
            font-size: 13px;
            color: var(--text-muted);
            line-height: 1.6;
        }

        /* Lesson List */
        .lesson-list {
            list-style: none;
        }

        .lesson-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px 20px;
            border-bottom: 1px solid var(--border);
            cursor: pointer;
            transition: all 0.3s;
        }

        .lesson-item:last-child {
            border-bottom: none;
        }

        .lesson-item:hover {
            background: var(--bg-lighter);
        }

        .lesson-number {
            width: 36px;
            height: 36px;
            background: var(--primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
        }

        .lesson-number.completed {
            background: var(--success);
        }

        .lesson-info {
            flex: 1;
        }

        .lesson-title {
            font-weight: 500;
            margin-bottom: 3px;
        }

        .lesson-duration {
            font-size: 12px;
            color: var(--text-muted);
        }

        .lesson-status {
            font-size: 20px;
        }

        /* Steps */
        .steps {
            counter-reset: step;
        }

        .step {
            position: relative;
            padding: 20px 20px 20px 70px;
            border-left: 2px solid var(--border);
            margin-left: 20px;
        }

        .step:last-child {
            border-left-color: transparent;
        }

        .step::before {
            counter-increment: step;
            content: counter(step);
            position: absolute;
            left: -16px;
            top: 20px;
            width: 30px;
            height: 30px;
            background: var(--primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
        }

        .step h4 {
            margin-bottom: 10px;
            color: var(--text);
        }

        .step p {
            color: var(--text-muted);
            font-size: 14px;
            line-height: 1.7;
            margin-bottom: 10px;
        }

        /* Code Block */
        .code-block {
            background: #0d1117;
            border-radius: 8px;
            padding: 20px;
            margin: 15px 0;
            overflow-x: auto;
            font-family: 'Consolas', 'Monaco', monospace;
            font-size: 13px;
            line-height: 1.6;
            border: 1px solid #30363d;
        }

        .code-block code {
            color: #c9d1d9;
        }

        .code-block .comment { color: #8b949e; }
        .code-block .keyword { color: #ff7b72; }
        .code-block .string { color: #a5d6ff; }
        .code-block .function { color: #d2a8ff; }

        /* Info Boxes */
        .info-box {
            padding: 15px 20px;
            border-radius: 8px;
            margin: 15px 0;
            display: flex;
            gap: 15px;
            align-items: flex-start;
        }

        .info-box.tip {
            background: rgba(16, 185, 129, 0.1);
            border-left: 4px solid var(--success);
        }

        .info-box.warning {
            background: rgba(245, 158, 11, 0.1);
            border-left: 4px solid var(--warning);
        }

        .info-box.danger {
            background: rgba(239, 68, 68, 0.1);
            border-left: 4px solid var(--danger);
        }

        .info-box.info {
            background: rgba(59, 130, 246, 0.1);
            border-left: 4px solid var(--info);
        }

        .info-box-icon {
            font-size: 20px;
        }

        .info-box-content {
            flex: 1;
        }

        .info-box-content strong {
            display: block;
            margin-bottom: 5px;
        }

        .info-box-content p {
            font-size: 13px;
            color: var(--text-muted);
            line-height: 1.6;
        }

        /* Tables */
        .doc-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }

        .doc-table th,
        .doc-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }

        .doc-table th {
            background: var(--bg-lighter);
            font-weight: 600;
            font-size: 13px;
            color: var(--text-muted);
            text-transform: uppercase;
        }

        .doc-table td {
            font-size: 14px;
        }

        .doc-table tr:hover {
            background: var(--bg-lighter);
        }

        /* Video Placeholder */
        .video-placeholder {
            background: var(--bg-lighter);
            border-radius: 12px;
            padding: 60px 40px;
            text-align: center;
            margin: 20px 0;
            border: 2px dashed var(--border);
        }

        .video-placeholder-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }

        .video-placeholder h4 {
            margin-bottom: 10px;
        }

        .video-placeholder p {
            color: var(--text-muted);
            font-size: 14px;
        }

        /* Progress Bar */
        .progress-section {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 20px 25px;
            margin-bottom: 25px;
            border: 1px solid var(--border);
        }

        .progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .progress-title {
            font-weight: 600;
        }

        .progress-percent {
            color: var(--primary);
            font-weight: 600;
        }

        .progress-bar {
            height: 8px;
            background: var(--bg-lighter);
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            border-radius: 4px;
            transition: width 0.5s ease;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            text-decoration: none;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: var(--bg-lighter);
            color: var(--text);
            border: 1px solid var(--border);
        }

        .btn-secondary:hover {
            background: var(--border);
        }

        /* Quiz */
        .quiz-question {
            background: var(--bg-lighter);
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }

        .quiz-question h4 {
            margin-bottom: 15px;
        }

        .quiz-option {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            background: var(--bg-card);
            border: 2px solid var(--border);
            border-radius: 8px;
            margin: 8px 0;
            cursor: pointer;
            transition: all 0.3s;
        }

        .quiz-option:hover {
            border-color: var(--primary);
        }

        .quiz-option.selected {
            border-color: var(--primary);
            background: rgba(0, 184, 148, 0.1);
        }

        .quiz-option.correct {
            border-color: var(--success);
            background: rgba(16, 185, 129, 0.1);
        }

        .quiz-option.incorrect {
            border-color: var(--danger);
            background: rgba(239, 68, 68, 0.1);
        }

        /* Screenshot */
        .screenshot {
            border-radius: 10px;
            border: 1px solid var(--border);
            margin: 20px 0;
            overflow: hidden;
        }

        .screenshot-header {
            background: var(--bg-lighter);
            padding: 10px 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .screenshot-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }

        .screenshot-dot.red { background: #ff5f56; }
        .screenshot-dot.yellow { background: #ffbd2e; }
        .screenshot-dot.green { background: #27ca40; }

        .screenshot-body {
            padding: 20px;
            background: var(--bg-card);
            min-height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-muted);
            font-size: 14px;
        }

        /* Accordion */
        .accordion {
            border: 1px solid var(--border);
            border-radius: 10px;
            overflow: hidden;
            margin: 15px 0;
        }

        .accordion-item {
            border-bottom: 1px solid var(--border);
        }

        .accordion-item:last-child {
            border-bottom: none;
        }

        .accordion-header {
            padding: 15px 20px;
            background: var(--bg-lighter);
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 500;
        }

        .accordion-header:hover {
            background: var(--border);
        }

        .accordion-content {
            padding: 20px;
            display: none;
        }

        .accordion-content.active {
            display: block;
        }

        /* Search */
        .search-box {
            position: relative;
            margin-bottom: 20px;
        }

        .search-box input {
            width: 100%;
            padding: 14px 20px 14px 50px;
            background: var(--bg-lighter);
            border: 1px solid var(--border);
            border-radius: 10px;
            color: var(--text);
            font-size: 15px;
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--primary);
        }

        .search-box .search-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s;
            }

            .sidebar.open {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .mobile-menu-btn {
                display: flex;
            }
        }

        @media (max-width: 768px) {
            .role-grid {
                grid-template-columns: 1fr;
            }

            .step {
                padding-left: 50px;
            }
        }
    </style>
</head>
<body>
    <div class="layout">
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h1>📚 Training Center</h1>
                <p><?= htmlspecialchars($app_name) ?></p>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-section">Getting Started</div>
                <div class="nav-item active" onclick="showModule('welcome')">
                    <span class="icon">🏠</span>
                    <span class="label">Welcome</span>
                </div>
                <div class="nav-item" onclick="showModule('roles')">
                    <span class="icon">👥</span>
                    <span class="label">User Roles</span>
                </div>

                <div class="nav-section">Installation</div>
                <div class="nav-item" onclick="showModule('requirements')">
                    <span class="icon">📋</span>
                    <span class="label">System Requirements</span>
                </div>
                <div class="nav-item" onclick="showModule('installation')">
                    <span class="icon">💿</span>
                    <span class="label">Installation Guide</span>
                </div>
                <div class="nav-item" onclick="showModule('configuration')">
                    <span class="icon">⚙️</span>
                    <span class="label">Configuration</span>
                </div>

                <div class="nav-section">User Training</div>
                <div class="nav-item" onclick="showModule('user-basics')">
                    <span class="icon">📖</span>
                    <span class="label">Basic Operations</span>
                    <span class="badge">5</span>
                </div>
                <div class="nav-item" onclick="showModule('scanning')">
                    <span class="icon">🔍</span>
                    <span class="label">Network Scanning</span>
                </div>
                <div class="nav-item" onclick="showModule('reports')">
                    <span class="icon">📊</span>
                    <span class="label">Generating Reports</span>
                </div>

                <div class="nav-section">Admin Training</div>
                <div class="nav-item" onclick="showModule('admin-overview')">
                    <span class="icon">🛡️</span>
                    <span class="label">Admin Overview</span>
                </div>
                <div class="nav-item" onclick="showModule('user-management')">
                    <span class="icon">👤</span>
                    <span class="label">User Management</span>
                </div>
                <div class="nav-item" onclick="showModule('system-config')">
                    <span class="icon">🔧</span>
                    <span class="label">System Configuration</span>
                </div>

                <div class="nav-section">Super User Training</div>
                <div class="nav-item" onclick="showModule('superuser-overview')">
                    <span class="icon">👑</span>
                    <span class="label">Super User Overview</span>
                </div>
                <div class="nav-item" onclick="showModule('advanced-config')">
                    <span class="icon">🔐</span>
                    <span class="label">Advanced Configuration</span>
                </div>
                <div class="nav-item" onclick="showModule('integrations')">
                    <span class="icon">🔗</span>
                    <span class="label">Integrations</span>
                </div>

                <div class="nav-section">Cloud & Deployment</div>
                <div class="nav-item" onclick="showModule('cloud-setup')">
                    <span class="icon">☁️</span>
                    <span class="label">Cloud Setup</span>
                    <span class="badge new">NEW</span>
                </div>
                <div class="nav-item" onclick="showModule('cloud-hybrid')">
                    <span class="icon">🔄</span>
                    <span class="label">Cloud Hybrid</span>
                    <span class="badge new">NEW</span>
                </div>
                <div class="nav-item" onclick="showModule('poc-setup')">
                    <span class="icon">🧪</span>
                    <span class="label">POC Setup</span>
                    <span class="badge new">NEW</span>
                </div>

                <div class="nav-section">Resources</div>
                <div class="nav-item" onclick="showModule('faq')">
                    <span class="icon">❓</span>
                    <span class="label">FAQ</span>
                </div>
                <div class="nav-item" onclick="showModule('troubleshooting')">
                    <span class="icon">🔧</span>
                    <span class="label">Troubleshooting</span>
                </div>
            </nav>

            <div class="sidebar-footer">
                <a href="index.php" class="back-btn">
                    <span>←</span>
                    <span>Back to Dashboard</span>
                </a>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="main-content">
            <!-- Welcome Module -->
            <div id="welcome" class="module active">
                <div class="content-header">
                    <div class="breadcrumb">
                        <a href="#">Training Center</a>
                        <span>/</span>
                        <span>Welcome</span>
                    </div>
                    <h2>Welcome to the Training Center</h2>
                    <p>Learn how to install, configure, and operate the <?= htmlspecialchars($app_name) ?> system effectively.</p>
                </div>

                <div class="progress-section">
                    <div class="progress-header">
                        <span class="progress-title">Your Training Progress</span>
                        <span class="progress-percent">15%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 15%;"></div>
                    </div>
                </div>

                <div class="role-grid">
                    <div class="role-card" onclick="showModule('user-basics')">
                        <div class="role-icon">👤</div>
                        <div class="role-title">User Training</div>
                        <div class="role-desc">Learn basic operations, scanning networks, viewing vulnerabilities, and generating reports.</div>
                    </div>

                    <div class="role-card" onclick="showModule('admin-overview')">
                        <div class="role-icon">🛡️</div>
                        <div class="role-title">Admin Training</div>
                        <div class="role-desc">Manage users, configure system settings, set up alerts, and maintain the platform.</div>
                    </div>

                    <div class="role-card" onclick="showModule('superuser-overview')">
                        <div class="role-icon">👑</div>
                        <div class="role-title">Super User Training</div>
                        <div class="role-desc">Advanced configuration, API integrations, database management, and system optimization.</div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Quick Start Lessons</h3>
                        <p>Essential lessons to get you started</p>
                    </div>
                    <div class="card-body" style="padding: 0;">
                        <ul class="lesson-list">
                            <li class="lesson-item" onclick="showModule('user-basics')">
                                <div class="lesson-number completed">1</div>
                                <div class="lesson-info">
                                    <div class="lesson-title">System Overview</div>
                                    <div class="lesson-duration">10 minutes</div>
                                </div>
                                <div class="lesson-status">✅</div>
                            </li>
                            <li class="lesson-item" onclick="showModule('scanning')">
                                <div class="lesson-number">2</div>
                                <div class="lesson-info">
                                    <div class="lesson-title">Running Your First Scan</div>
                                    <div class="lesson-duration">15 minutes</div>
                                </div>
                                <div class="lesson-status">▶️</div>
                            </li>
                            <li class="lesson-item" onclick="showModule('reports')">
                                <div class="lesson-number">3</div>
                                <div class="lesson-info">
                                    <div class="lesson-title">Generating Reports</div>
                                    <div class="lesson-duration">20 minutes</div>
                                </div>
                                <div class="lesson-status">🔒</div>
                            </li>
                            <li class="lesson-item" onclick="showModule('configuration')">
                                <div class="lesson-number">4</div>
                                <div class="lesson-info">
                                    <div class="lesson-title">Basic Configuration</div>
                                    <div class="lesson-duration">15 minutes</div>
                                </div>
                                <div class="lesson-status">🔒</div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- User Roles Module -->
            <div id="roles" class="module">
                <div class="content-header">
                    <div class="breadcrumb">
                        <a href="#">Training Center</a>
                        <span>/</span>
                        <span>User Roles</span>
                    </div>
                    <h2>Understanding User Roles</h2>
                    <p>Learn about the different user roles and their permissions in the system.</p>
                </div>

                <div class="card">
                    <div class="card-body">
                        <table class="doc-table">
                            <thead>
                                <tr>
                                    <th>Role</th>
                                    <th>Permissions</th>
                                    <th>Typical Use</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>👤 User</strong></td>
                                    <td>View dashboards, run scans, generate reports, view vulnerabilities</td>
                                    <td>Security analysts, IT staff, auditors</td>
                                </tr>
                                <tr>
                                    <td><strong>🛡️ Admin</strong></td>
                                    <td>All User permissions + manage users, configure alerts, system settings</td>
                                    <td>IT managers, security team leads</td>
                                </tr>
                                <tr>
                                    <td><strong>👑 Super User</strong></td>
                                    <td>All permissions + database access, API management, integrations, advanced config</td>
                                    <td>System administrators, DevOps engineers</td>
                                </tr>
                            </tbody>
                        </table>

                        <div class="info-box tip">
                            <div class="info-box-icon">💡</div>
                            <div class="info-box-content">
                                <strong>Tip: Principle of Least Privilege</strong>
                                <p>Always assign users the minimum permissions they need to perform their job. This reduces security risks.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- System Requirements Module -->
            <div id="requirements" class="module">
                <div class="content-header">
                    <div class="breadcrumb">
                        <a href="#">Training Center</a>
                        <span>/</span>
                        <span>System Requirements</span>
                    </div>
                    <h2>System Requirements</h2>
                    <p>Hardware and software requirements for running the system.</p>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Minimum Requirements</h3>
                    </div>
                    <div class="card-body">
                        <table class="doc-table">
                            <thead>
                                <tr>
                                    <th>Component</th>
                                    <th>Minimum</th>
                                    <th>Recommended</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>CPU</strong></td>
                                    <td>2 cores</td>
                                    <td>4+ cores</td>
                                </tr>
                                <tr>
                                    <td><strong>RAM</strong></td>
                                    <td>4 GB</td>
                                    <td>8+ GB</td>
                                </tr>
                                <tr>
                                    <td><strong>Storage</strong></td>
                                    <td>20 GB</td>
                                    <td>100+ GB SSD</td>
                                </tr>
                                <tr>
                                    <td><strong>OS</strong></td>
                                    <td>Windows 10 / Ubuntu 20.04</td>
                                    <td>Windows Server 2019+ / Ubuntu 22.04</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Software Requirements</h3>
                    </div>
                    <div class="card-body">
                        <table class="doc-table">
                            <tbody>
                                <tr>
                                    <td><strong>Web Server</strong></td>
                                    <td>Apache 2.4+ or Nginx 1.18+</td>
                                </tr>
                                <tr>
                                    <td><strong>PHP</strong></td>
                                    <td>PHP 7.4+ (PHP 8.0+ recommended)</td>
                                </tr>
                                <tr>
                                    <td><strong>Database</strong></td>
                                    <td>MySQL 5.7+ or MariaDB 10.3+</td>
                                </tr>
                                <tr>
                                    <td><strong>PHP Extensions</strong></td>
                                    <td>PDO, MySQL, cURL, OpenSSL, JSON, mbstring</td>
                                </tr>
                            </tbody>
                        </table>

                        <div class="info-box info">
                            <div class="info-box-icon">ℹ️</div>
                            <div class="info-box-content">
                                <strong>XAMPP Users</strong>
                                <p>If using XAMPP, all requirements are pre-configured. Just ensure you're using XAMPP 8.0 or higher.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Installation Guide Module -->
            <div id="installation" class="module">
                <div class="content-header">
                    <div class="breadcrumb">
                        <a href="#">Training Center</a>
                        <span>/</span>
                        <span>Installation Guide</span>
                    </div>
                    <h2>Installation Guide</h2>
                    <p>Step-by-step instructions to install the system.</p>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Installation Steps</h3>
                    </div>
                    <div class="card-body">
                        <div class="steps">
                            <div class="step">
                                <h4>Download and Extract Files</h4>
                                <p>Download the application package and extract it to your web server's document root.</p>
                                <div class="code-block">
                                    <code>
<span class="comment"># For XAMPP on Windows:</span>
C:\xampp\htdocs\networkscanscada\

<span class="comment"># For Linux:</span>
/var/www/html/networkscanscada/
                                    </code>
                                </div>
                            </div>

                            <div class="step">
                                <h4>Create the Database</h4>
                                <p>Access phpMyAdmin or MySQL command line and create a new database.</p>
                                <div class="code-block">
                                    <code>
<span class="keyword">CREATE DATABASE</span> network_scan_scada;
<span class="keyword">CREATE USER</span> <span class="string">'scada_user'</span>@<span class="string">'localhost'</span> <span class="keyword">IDENTIFIED BY</span> <span class="string">'your_secure_password'</span>;
<span class="keyword">GRANT ALL PRIVILEGES ON</span> network_scan_scada.* <span class="keyword">TO</span> <span class="string">'scada_user'</span>@<span class="string">'localhost'</span>;
<span class="keyword">FLUSH PRIVILEGES</span>;
                                    </code>
                                </div>
                            </div>

                            <div class="step">
                                <h4>Configure Database Connection</h4>
                                <p>Edit the database configuration file with your database credentials.</p>
                                <div class="code-block">
                                    <code>
<span class="comment">// config/database.php</span>
<span class="keyword">private</span> <span class="function">$host</span> = <span class="string">'localhost'</span>;
<span class="keyword">private</span> <span class="function">$dbname</span> = <span class="string">'network_scan_scada'</span>;
<span class="keyword">private</span> <span class="function">$username</span> = <span class="string">'scada_user'</span>;
<span class="keyword">private</span> <span class="function">$password</span> = <span class="string">'your_secure_password'</span>;
                                    </code>
                                </div>
                            </div>

                            <div class="step">
                                <h4>Import Database Schema</h4>
                                <p>Import the SQL schema files to create all necessary tables.</p>
                                <div class="code-block">
                                    <code>
<span class="comment"># Via phpMyAdmin:</span>
1. Select your database
2. Click "Import"
3. Choose database/schema.sql
4. Click "Go"

<span class="comment"># Via command line:</span>
mysql -u scada_user -p network_scan_scada < database/schema.sql
                                    </code>
                                </div>
                            </div>

                            <div class="step">
                                <h4>Set File Permissions (Linux)</h4>
                                <p>Ensure proper file permissions for the web server.</p>
                                <div class="code-block">
                                    <code>
<span class="keyword">sudo</span> chown -R www-data:www-data /var/www/html/networkscanscada
<span class="keyword">sudo</span> chmod -R 755 /var/www/html/networkscanscada
<span class="keyword">sudo</span> chmod -R 777 /var/www/html/networkscanscada/uploads
                                    </code>
                                </div>
                            </div>

                            <div class="step">
                                <h4>Access the Application</h4>
                                <p>Open your browser and navigate to the application URL.</p>
                                <div class="code-block">
                                    <code>
http://localhost/networkscanscada/
                                    </code>
                                </div>
                            </div>
                        </div>

                        <div class="info-box warning">
                            <div class="info-box-icon">⚠️</div>
                            <div class="info-box-content">
                                <strong>Security Warning</strong>
                                <p>Change default passwords immediately after installation. Never use default credentials in production.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Configuration Module -->
            <div id="configuration" class="module">
                <div class="content-header">
                    <div class="breadcrumb">
                        <a href="#">Training Center</a>
                        <span>/</span>
                        <span>Configuration</span>
                    </div>
                    <h2>System Configuration</h2>
                    <p>Configure the system settings after installation.</p>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Configuration Files</h3>
                    </div>
                    <div class="card-body">
                        <table class="doc-table">
                            <thead>
                                <tr>
                                    <th>File</th>
                                    <th>Purpose</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code>config/database.php</code></td>
                                    <td>Database connection settings</td>
                                </tr>
                                <tr>
                                    <td><code>config/app.php</code></td>
                                    <td>Application settings (name, timezone, etc.)</td>
                                </tr>
                                <tr>
                                    <td><code>config/mail.php</code></td>
                                    <td>Email/SMTP configuration for alerts</td>
                                </tr>
                                <tr>
                                    <td><code>config/scan.php</code></td>
                                    <td>Network scanning parameters</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Branding Configuration</h3>
                    </div>
                    <div class="card-body">
                        <p>Customize the application branding through the admin panel:</p>
                        <ol style="margin: 15px 0; padding-left: 25px; line-height: 2;">
                            <li>Go to Dashboard → Branding</li>
                            <li>Upload your company logo</li>
                            <li>Set the application name</li>
                            <li>Choose theme colors</li>
                            <li>Save changes</li>
                        </ol>

                        <div class="info-box tip">
                            <div class="info-box-icon">💡</div>
                            <div class="info-box-content">
                                <strong>Logo Requirements</strong>
                                <p>Recommended logo size: 200x50 pixels, PNG or SVG format with transparent background.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- User Basics Module -->
            <div id="user-basics" class="module">
                <div class="content-header">
                    <div class="breadcrumb">
                        <a href="#">Training Center</a>
                        <span>/</span>
                        <span>Basic Operations</span>
                    </div>
                    <h2>Basic Operations for Users</h2>
                    <p>Learn the fundamental operations every user should know.</p>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Dashboard Overview</h3>
                    </div>
                    <div class="card-body">
                        <p>The dashboard provides a real-time overview of your network security status:</p>

                        <div class="screenshot">
                            <div class="screenshot-header">
                                <div class="screenshot-dot red"></div>
                                <div class="screenshot-dot yellow"></div>
                                <div class="screenshot-dot green"></div>
                            </div>
                            <div class="screenshot-body">
                                [Dashboard Screenshot - Shows main dashboard with statistics cards, charts, and module grid]
                            </div>
                        </div>

                        <h4 style="margin: 20px 0 15px;">Key Dashboard Elements:</h4>
                        <ul style="line-height: 2; padding-left: 25px;">
                            <li><strong>Total Scans</strong> - Number of network scans performed</li>
                            <li><strong>Active Vulnerabilities</strong> - Unresolved security issues</li>
                            <li><strong>Monitored Hosts</strong> - Devices being monitored</li>
                            <li><strong>Recent Alerts</strong> - Latest security notifications</li>
                        </ul>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Navigation</h3>
                    </div>
                    <div class="card-body">
                        <p>Use the navigation bar to access different modules:</p>
                        <table class="doc-table">
                            <tbody>
                                <tr>
                                    <td><strong>Dashboard</strong></td>
                                    <td>Main overview and statistics</td>
                                </tr>
                                <tr>
                                    <td><strong>New Scan</strong></td>
                                    <td>Start a new network scan</td>
                                </tr>
                                <tr>
                                    <td><strong>View Reports</strong></td>
                                    <td>Access generated reports</td>
                                </tr>
                                <tr>
                                    <td><strong>Report Builder</strong></td>
                                    <td>Create custom reports with AI analysis</td>
                                </tr>
                                <tr>
                                    <td><strong>Service Desk</strong></td>
                                    <td>IT service management and ticketing</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Network Scanning Module -->
            <div id="scanning" class="module">
                <div class="content-header">
                    <div class="breadcrumb">
                        <a href="#">Training Center</a>
                        <span>/</span>
                        <span>Network Scanning</span>
                    </div>
                    <h2>Network Scanning Guide</h2>
                    <p>Learn how to perform network scans and interpret results.</p>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Running a Network Scan</h3>
                    </div>
                    <div class="card-body">
                        <div class="steps">
                            <div class="step">
                                <h4>Access the Scan Page</h4>
                                <p>Click "New Scan" in the navigation bar or dashboard.</p>
                            </div>

                            <div class="step">
                                <h4>Enter Target Information</h4>
                                <p>Enter the IP address, IP range, or hostname you want to scan.</p>
                                <div class="code-block">
                                    <code>
<span class="comment"># Single IP:</span>
192.168.1.100

<span class="comment"># IP Range (CIDR):</span>
192.168.1.0/24

<span class="comment"># IP Range:</span>
192.168.1.1-192.168.1.254

<span class="comment"># Hostname:</span>
server.example.com
                                    </code>
                                </div>
                            </div>

                            <div class="step">
                                <h4>Select Scan Type</h4>
                                <p>Choose the appropriate scan type based on your needs:</p>
                                <table class="doc-table">
                                    <tbody>
                                        <tr>
                                            <td><strong>Quick Scan</strong></td>
                                            <td>Fast scan of common ports (1-5 minutes)</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Full Scan</strong></td>
                                            <td>Comprehensive scan of all ports (15-30 minutes)</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Vulnerability Scan</strong></td>
                                            <td>Checks for known vulnerabilities (30-60 minutes)</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="step">
                                <h4>Start the Scan</h4>
                                <p>Click "Start Scan" and wait for results. Progress will be displayed in real-time.</p>
                            </div>

                            <div class="step">
                                <h4>Review Results</h4>
                                <p>Once complete, review discovered hosts, open ports, and any vulnerabilities found.</p>
                            </div>
                        </div>

                        <div class="info-box warning">
                            <div class="info-box-icon">⚠️</div>
                            <div class="info-box-content">
                                <strong>Authorization Required</strong>
                                <p>Only scan networks you are authorized to test. Unauthorized scanning may be illegal.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Generating Reports Module -->
            <div id="reports" class="module">
                <div class="content-header">
                    <div class="breadcrumb">
                        <a href="#">Training Center</a>
                        <span>/</span>
                        <span>Generating Reports</span>
                    </div>
                    <h2>Generating Reports</h2>
                    <p>Learn how to create comprehensive security reports.</p>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Report Types</h3>
                    </div>
                    <div class="card-body">
                        <table class="doc-table">
                            <thead>
                                <tr>
                                    <th>Report Type</th>
                                    <th>Description</th>
                                    <th>Best For</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Executive Summary</strong></td>
                                    <td>High-level overview with key metrics and trends</td>
                                    <td>Management, stakeholders</td>
                                </tr>
                                <tr>
                                    <td><strong>Technical Report</strong></td>
                                    <td>Detailed technical findings with remediation steps</td>
                                    <td>IT teams, security analysts</td>
                                </tr>
                                <tr>
                                    <td><strong>Compliance Report</strong></td>
                                    <td>Compliance status against standards (PCI, HIPAA, etc.)</td>
                                    <td>Auditors, compliance officers</td>
                                </tr>
                                <tr>
                                    <td><strong>Vulnerability Report</strong></td>
                                    <td>List of all vulnerabilities with severity ratings</td>
                                    <td>Security teams</td>
                                </tr>
                                <tr>
                                    <td><strong>Custom Report</strong></td>
                                    <td>Build your own report with selected data</td>
                                    <td>Specific requirements</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Using the Report Builder</h3>
                    </div>
                    <div class="card-body">
                        <div class="steps">
                            <div class="step">
                                <h4>Access Report Builder</h4>
                                <p>Click "Report Builder" in the navigation bar. This opens the custom report creation interface.</p>
                            </div>

                            <div class="step">
                                <h4>Select Report Type</h4>
                                <p>Choose from Executive Summary, Technical Report, Compliance Report, or Custom Report.</p>
                            </div>

                            <div class="step">
                                <h4>Choose Date Range</h4>
                                <p>Select the time period for the report data (Last 7 days, 30 days, 90 days, or custom range).</p>
                            </div>

                            <div class="step">
                                <h4>Select Data Sections</h4>
                                <p>Choose which sections to include in your report:</p>
                                <ul style="padding-left: 25px; line-height: 1.8;">
                                    <li>Vulnerability Summary</li>
                                    <li>Host Inventory</li>
                                    <li>Scan History</li>
                                    <li>Risk Assessment</li>
                                    <li>Remediation Recommendations</li>
                                    <li>Trend Analysis</li>
                                </ul>
                            </div>

                            <div class="step">
                                <h4>Enable AI Analysis (Optional)</h4>
                                <p>Toggle "AI Analysis" to include intelligent insights and recommendations powered by AI.</p>
                                <div class="info-box tip">
                                    <div class="info-box-icon">🤖</div>
                                    <div class="info-box-content">
                                        <strong>AI Analysis Features</strong>
                                        <p>AI analysis provides prioritized remediation suggestions, pattern detection, and predictive risk assessment.</p>
                                    </div>
                                </div>
                            </div>

                            <div class="step">
                                <h4>Generate and Export</h4>
                                <p>Click "Generate Report" to create your report. Export options include:</p>
                                <ul style="padding-left: 25px; line-height: 1.8;">
                                    <li><strong>PDF</strong> - For printing and sharing</li>
                                    <li><strong>Excel</strong> - For data analysis</li>
                                    <li><strong>HTML</strong> - For web viewing</li>
                                    <li><strong>JSON</strong> - For API integration</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Scheduling Automated Reports</h3>
                    </div>
                    <div class="card-body">
                        <p>Set up reports to generate automatically on a schedule:</p>

                        <ol style="padding-left: 25px; line-height: 2;">
                            <li>Open Report Builder</li>
                            <li>Configure your report settings</li>
                            <li>Click "Schedule Report"</li>
                            <li>Select frequency (Daily, Weekly, Monthly, Quarterly)</li>
                            <li>Set delivery time and recipients</li>
                            <li>Choose delivery method (Email, Save to folder, Both)</li>
                            <li>Click "Save Schedule"</li>
                        </ol>

                        <div class="info-box info">
                            <div class="info-box-icon">ℹ️</div>
                            <div class="info-box-content">
                                <strong>Email Configuration Required</strong>
                                <p>To receive reports via email, ensure SMTP settings are configured in the admin panel.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Admin Overview Module -->
            <div id="admin-overview" class="module">
                <div class="content-header">
                    <div class="breadcrumb">
                        <a href="#">Training Center</a>
                        <span>/</span>
                        <span>Admin Overview</span>
                    </div>
                    <h2>Administrator Overview</h2>
                    <p>Learn the administrative functions and responsibilities.</p>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Admin Responsibilities</h3>
                    </div>
                    <div class="card-body">
                        <ul style="line-height: 2.2; padding-left: 25px;">
                            <li><strong>User Management</strong> - Create, modify, and deactivate user accounts</li>
                            <li><strong>Access Control</strong> - Assign roles and permissions to users</li>
                            <li><strong>System Configuration</strong> - Configure application settings</li>
                            <li><strong>Alert Management</strong> - Set up and manage security alerts</li>
                            <li><strong>Report Oversight</strong> - Review and approve scheduled reports</li>
                            <li><strong>Audit Logs</strong> - Monitor user activity and system events</li>
                            <li><strong>Backup Management</strong> - Ensure data backups are running</li>
                        </ul>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Admin Quick Actions</h3>
                    </div>
                    <div class="card-body">
                        <div class="role-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
                            <div class="role-card" onclick="showModule('user-management')" style="padding: 20px;">
                                <div class="role-icon" style="font-size: 36px;">👤</div>
                                <div class="role-title" style="font-size: 16px;">User Management</div>
                            </div>
                            <div class="role-card" onclick="showModule('system-config')" style="padding: 20px;">
                                <div class="role-icon" style="font-size: 36px;">⚙️</div>
                                <div class="role-title" style="font-size: 16px;">System Config</div>
                            </div>
                            <div class="role-card" style="padding: 20px;">
                                <div class="role-icon" style="font-size: 36px;">🔔</div>
                                <div class="role-title" style="font-size: 16px;">Alert Settings</div>
                            </div>
                            <div class="role-card" style="padding: 20px;">
                                <div class="role-icon" style="font-size: 36px;">📋</div>
                                <div class="role-title" style="font-size: 16px;">Audit Logs</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- User Management Module -->
            <div id="user-management" class="module">
                <div class="content-header">
                    <div class="breadcrumb">
                        <a href="#">Training Center</a>
                        <span>/</span>
                        <span>User Management</span>
                    </div>
                    <h2>User Management</h2>
                    <p>Learn how to manage user accounts and permissions.</p>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Creating a New User</h3>
                    </div>
                    <div class="card-body">
                        <div class="steps">
                            <div class="step">
                                <h4>Access User Management</h4>
                                <p>Navigate to Admin Panel → User Management</p>
                            </div>
                            <div class="step">
                                <h4>Click "Add New User"</h4>
                                <p>Opens the user creation form</p>
                            </div>
                            <div class="step">
                                <h4>Fill User Details</h4>
                                <p>Enter username, email, full name, and temporary password</p>
                            </div>
                            <div class="step">
                                <h4>Assign Role</h4>
                                <p>Select User, Admin, or Super User role based on responsibilities</p>
                            </div>
                            <div class="step">
                                <h4>Set Permissions</h4>
                                <p>Configure specific module access if needed</p>
                            </div>
                            <div class="step">
                                <h4>Save and Notify</h4>
                                <p>Save the user and optionally send welcome email with credentials</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- System Configuration Module -->
            <div id="system-config" class="module">
                <div class="content-header">
                    <div class="breadcrumb">
                        <a href="#">Training Center</a>
                        <span>/</span>
                        <span>System Configuration</span>
                    </div>
                    <h2>System Configuration</h2>
                    <p>Configure system-wide settings.</p>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Configuration Areas</h3>
                    </div>
                    <div class="card-body">
                        <div class="accordion">
                            <div class="accordion-item">
                                <div class="accordion-header" onclick="toggleAccordion(this)">
                                    <span>🎨 Branding Settings</span>
                                    <span>+</span>
                                </div>
                                <div class="accordion-content">
                                    <p>Configure logo, application name, and theme colors.</p>
                                    <ul style="padding-left: 25px; line-height: 1.8;">
                                        <li>Upload company logo</li>
                                        <li>Set application name</li>
                                        <li>Choose primary and secondary colors</li>
                                        <li>Configure favicon</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <div class="accordion-header" onclick="toggleAccordion(this)">
                                    <span>📧 Email Settings</span>
                                    <span>+</span>
                                </div>
                                <div class="accordion-content">
                                    <p>Configure SMTP settings for email notifications.</p>
                                    <ul style="padding-left: 25px; line-height: 1.8;">
                                        <li>SMTP server address and port</li>
                                        <li>Authentication credentials</li>
                                        <li>From address and name</li>
                                        <li>Test email functionality</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <div class="accordion-header" onclick="toggleAccordion(this)">
                                    <span>🔒 Security Settings</span>
                                    <span>+</span>
                                </div>
                                <div class="accordion-content">
                                    <p>Configure security policies and authentication.</p>
                                    <ul style="padding-left: 25px; line-height: 1.8;">
                                        <li>Password complexity requirements</li>
                                        <li>Session timeout duration</li>
                                        <li>Two-factor authentication</li>
                                        <li>IP whitelist/blacklist</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <div class="accordion-header" onclick="toggleAccordion(this)">
                                    <span>🔔 Notification Settings</span>
                                    <span>+</span>
                                </div>
                                <div class="accordion-content">
                                    <p>Configure alert thresholds and notification channels.</p>
                                    <ul style="padding-left: 25px; line-height: 1.8;">
                                        <li>Vulnerability severity thresholds</li>
                                        <li>Email notification rules</li>
                                        <li>Slack/Teams integration</li>
                                        <li>SMS alerts (if configured)</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Super User Overview Module -->
            <div id="superuser-overview" class="module">
                <div class="content-header">
                    <div class="breadcrumb">
                        <a href="#">Training Center</a>
                        <span>/</span>
                        <span>Super User Overview</span>
                    </div>
                    <h2>Super User Overview</h2>
                    <p>Advanced administration and system management.</p>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Super User Capabilities</h3>
                    </div>
                    <div class="card-body">
                        <div class="info-box danger">
                            <div class="info-box-icon">⚠️</div>
                            <div class="info-box-content">
                                <strong>High Privilege Access</strong>
                                <p>Super Users have full system access. Use these privileges responsibly and always follow change management procedures.</p>
                            </div>
                        </div>

                        <table class="doc-table">
                            <thead>
                                <tr>
                                    <th>Capability</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Database Access</strong></td>
                                    <td>Direct database queries and management</td>
                                </tr>
                                <tr>
                                    <td><strong>API Management</strong></td>
                                    <td>Create and manage API keys, configure endpoints</td>
                                </tr>
                                <tr>
                                    <td><strong>Integration Setup</strong></td>
                                    <td>Configure third-party integrations (SIEM, SOAR, etc.)</td>
                                </tr>
                                <tr>
                                    <td><strong>System Maintenance</strong></td>
                                    <td>Database optimization, cache clearing, log rotation</td>
                                </tr>
                                <tr>
                                    <td><strong>Backup/Restore</strong></td>
                                    <td>Full system backup and disaster recovery</td>
                                </tr>
                                <tr>
                                    <td><strong>Module Management</strong></td>
                                    <td>Enable/disable modules, update configurations</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Advanced Configuration Module -->
            <div id="advanced-config" class="module">
                <div class="content-header">
                    <div class="breadcrumb">
                        <a href="#">Training Center</a>
                        <span>/</span>
                        <span>Advanced Configuration</span>
                    </div>
                    <h2>Advanced Configuration</h2>
                    <p>Advanced system configuration for super users.</p>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Database Management</h3>
                    </div>
                    <div class="card-body">
                        <p>Access database tools for maintenance and optimization:</p>
                        <div class="code-block">
                            <code>
<span class="comment"># Optimize all tables</span>
<span class="keyword">OPTIMIZE TABLE</span> hosts, vulnerabilities, scan_results, users;

<span class="comment"># Check table integrity</span>
<span class="keyword">CHECK TABLE</span> hosts, vulnerabilities;

<span class="comment"># Analyze for query optimization</span>
<span class="keyword">ANALYZE TABLE</span> hosts, vulnerabilities, scan_results;
                            </code>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Integrations Module -->
            <div id="integrations" class="module">
                <div class="content-header">
                    <div class="breadcrumb">
                        <a href="#">Training Center</a>
                        <span>/</span>
                        <span>Integrations</span>
                    </div>
                    <h2>System Integrations</h2>
                    <p>Connect with external systems and services.</p>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Available Integrations</h3>
                    </div>
                    <div class="card-body">
                        <table class="doc-table">
                            <thead>
                                <tr>
                                    <th>Integration</th>
                                    <th>Purpose</th>
                                    <th>Configuration</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>SIEM</strong></td>
                                    <td>Forward security events to SIEM platform</td>
                                    <td>Syslog/API endpoint</td>
                                </tr>
                                <tr>
                                    <td><strong>Ticketing</strong></td>
                                    <td>Auto-create tickets for vulnerabilities</td>
                                    <td>ServiceNow, Jira, etc.</td>
                                </tr>
                                <tr>
                                    <td><strong>Slack/Teams</strong></td>
                                    <td>Real-time alerts and notifications</td>
                                    <td>Webhook URL</td>
                                </tr>
                                <tr>
                                    <td><strong>Active Directory</strong></td>
                                    <td>User authentication via LDAP</td>
                                    <td>LDAP server settings</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Cloud Setup Module -->
            <div id="cloud-setup" class="module">
                <div class="content-header">
                    <div class="breadcrumb">
                        <a href="#">Training Center</a>
                        <span>/</span>
                        <span>Cloud & Deployment</span>
                        <span>/</span>
                        <span>Cloud Setup</span>
                    </div>
                    <h2>☁️ Cloud Setup Guide</h2>
                    <p>Learn how to deploy IOC Intelligent Operating Centre in cloud environments.</p>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Supported Cloud Platforms</h3>
                    </div>
                    <div class="card-body">
                        <table class="doc-table">
                            <thead>
                                <tr>
                                    <th>Platform</th>
                                    <th>Service Type</th>
                                    <th>Recommended Tier</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Amazon Web Services (AWS)</strong></td>
                                    <td>EC2, RDS, S3</td>
                                    <td>t3.medium or higher</td>
                                    <td><span style="color: var(--success);">✓ Certified</span></td>
                                </tr>
                                <tr>
                                    <td><strong>Microsoft Azure</strong></td>
                                    <td>VMs, Azure SQL, Blob Storage</td>
                                    <td>Standard_B2s or higher</td>
                                    <td><span style="color: var(--success);">✓ Certified</span></td>
                                </tr>
                                <tr>
                                    <td><strong>Google Cloud Platform</strong></td>
                                    <td>Compute Engine, Cloud SQL</td>
                                    <td>e2-medium or higher</td>
                                    <td><span style="color: var(--success);">✓ Certified</span></td>
                                </tr>
                                <tr>
                                    <td><strong>DigitalOcean</strong></td>
                                    <td>Droplets, Managed MySQL</td>
                                    <td>Basic 2GB RAM</td>
                                    <td><span style="color: var(--info);">✓ Compatible</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>AWS Deployment Steps</h3>
                    </div>
                    <div class="card-body">
                        <div class="steps">
                            <div class="step">
                                <div class="step-number">1</div>
                                <div class="step-content">
                                    <h4>Launch EC2 Instance</h4>
                                    <p>Create a new EC2 instance with the following specifications:</p>
                                    <div class="code-block">
                                        <code>
<span class="comment"># Recommended AMI: Amazon Linux 2 or Ubuntu 22.04 LTS</span>
Instance Type: t3.medium (minimum)
Storage: 50GB SSD (gp3)
Security Group: Allow ports 80, 443, 22

<span class="comment"># Install dependencies</span>
sudo yum update -y
sudo amazon-linux-extras install php8.0 -y
sudo yum install httpd mariadb-server -y
                                        </code>
                                    </div>
                                </div>
                            </div>
                            <div class="step">
                                <div class="step-number">2</div>
                                <div class="step-content">
                                    <h4>Configure RDS Database</h4>
                                    <p>Set up a managed MySQL/MariaDB database:</p>
                                    <ul style="padding-left: 25px; line-height: 1.8;">
                                        <li>Engine: MySQL 8.0 or MariaDB 10.6</li>
                                        <li>Instance Class: db.t3.small (minimum)</li>
                                        <li>Storage: 20GB with auto-scaling</li>
                                        <li>Enable automated backups</li>
                                        <li>Configure VPC security group to allow EC2 access</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="step">
                                <div class="step-number">3</div>
                                <div class="step-content">
                                    <h4>Deploy Application</h4>
                                    <p>Upload and configure the IOC application:</p>
                                    <div class="code-block">
                                        <code>
<span class="comment"># Clone or upload application files</span>
cd /var/www/html
sudo unzip ioc-application.zip

<span class="comment"># Set permissions</span>
sudo chown -R apache:apache /var/www/html
sudo chmod -R 755 /var/www/html

<span class="comment"># Configure database connection</span>
sudo nano config/database.php
                                        </code>
                                    </div>
                                </div>
                            </div>
                            <div class="step">
                                <div class="step-number">4</div>
                                <div class="step-content">
                                    <h4>Configure SSL/HTTPS</h4>
                                    <p>Secure your installation with SSL:</p>
                                    <div class="code-block">
                                        <code>
<span class="comment"># Install Certbot for Let's Encrypt</span>
sudo yum install certbot python3-certbot-apache -y
sudo certbot --apache -d yourdomain.com

<span class="comment"># Auto-renewal setup</span>
echo "0 12 * * * /usr/bin/certbot renew --quiet" | sudo crontab -
                                        </code>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Azure Deployment Steps</h3>
                    </div>
                    <div class="card-body">
                        <div class="steps">
                            <div class="step">
                                <div class="step-number">1</div>
                                <div class="step-content">
                                    <h4>Create Azure VM</h4>
                                    <p>Launch a virtual machine in Azure Portal:</p>
                                    <ul style="padding-left: 25px; line-height: 1.8;">
                                        <li>Image: Ubuntu Server 22.04 LTS</li>
                                        <li>Size: Standard_B2s or higher</li>
                                        <li>Authentication: SSH public key</li>
                                        <li>Inbound ports: 80, 443, 22</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="step">
                                <div class="step-number">2</div>
                                <div class="step-content">
                                    <h4>Setup Azure SQL Database</h4>
                                    <p>Create managed database service:</p>
                                    <ul style="padding-left: 25px; line-height: 1.8;">
                                        <li>Service: Azure Database for MySQL</li>
                                        <li>Compute tier: Burstable (B1ms minimum)</li>
                                        <li>Storage: 20 GiB with auto-grow</li>
                                        <li>Backup retention: 7 days (adjustable)</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="step">
                                <div class="step-number">3</div>
                                <div class="step-content">
                                    <h4>Configure Networking</h4>
                                    <p>Set up Virtual Network and firewall rules:</p>
                                    <div class="code-block">
                                        <code>
<span class="comment"># Azure CLI commands</span>
az network vnet create --name IOC-VNet --resource-group IOC-RG
az network nsg rule create --name AllowHTTPS --nsg-name IOC-NSG \
    --priority 100 --access Allow --protocol Tcp --destination-port-ranges 443
                                        </code>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="info-box tip">
                    <div class="info-box-icon">💡</div>
                    <div class="info-box-content">
                        <strong>Cloud Best Practices</strong>
                        <p>Always use IAM roles instead of access keys, enable multi-AZ for production databases, and implement auto-scaling for variable workloads.</p>
                    </div>
                </div>
            </div>

            <!-- Cloud Hybrid Module -->
            <div id="cloud-hybrid" class="module">
                <div class="content-header">
                    <div class="breadcrumb">
                        <a href="#">Training Center</a>
                        <span>/</span>
                        <span>Cloud & Deployment</span>
                        <span>/</span>
                        <span>Cloud Hybrid</span>
                    </div>
                    <h2>🔄 Cloud Hybrid Architecture</h2>
                    <p>Deploy IOC in a hybrid environment combining on-premises and cloud infrastructure.</p>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Hybrid Architecture Overview</h3>
                    </div>
                    <div class="card-body">
                        <div class="screenshot">
                            <div class="screenshot-header">
                                <span class="screenshot-dot red"></span>
                                <span class="screenshot-dot yellow"></span>
                                <span class="screenshot-dot green"></span>
                                <span style="margin-left: 10px; color: var(--text-muted); font-size: 12px;">Hybrid Architecture Diagram</span>
                            </div>
                            <div class="screenshot-body" style="padding: 30px; text-align: left;">
                                <svg viewBox="0 0 800 400" style="width: 100%; max-width: 750px; margin: 0 auto; display: block;">
                                    <!-- On-Premises Box -->
                                    <rect x="20" y="50" width="250" height="300" fill="none" stroke="#00b894" stroke-width="2" stroke-dasharray="5,5" rx="10"/>
                                    <text x="145" y="80" fill="#00b894" font-size="14" text-anchor="middle" font-weight="bold">ON-PREMISES</text>

                                    <!-- SCADA Systems -->
                                    <rect x="40" y="100" width="90" height="60" fill="#1f2b47" stroke="#3b82f6" stroke-width="2" rx="5"/>
                                    <text x="85" y="135" fill="#e2e8f0" font-size="11" text-anchor="middle">SCADA</text>

                                    <!-- Local DB -->
                                    <rect x="150" y="100" width="90" height="60" fill="#1f2b47" stroke="#f59e0b" stroke-width="2" rx="5"/>
                                    <text x="195" y="135" fill="#e2e8f0" font-size="11" text-anchor="middle">Local DB</text>

                                    <!-- IOC Agent -->
                                    <rect x="40" y="200" width="200" height="60" fill="#1f2b47" stroke="#00b894" stroke-width="2" rx="5"/>
                                    <text x="140" y="235" fill="#e2e8f0" font-size="12" text-anchor="middle">IOC Sync Agent</text>

                                    <!-- Firewall -->
                                    <rect x="40" y="290" width="200" height="40" fill="#ef4444" stroke="#ef4444" stroke-width="2" rx="5" fill-opacity="0.3"/>
                                    <text x="140" y="315" fill="#ef4444" font-size="11" text-anchor="middle">🔥 Firewall</text>

                                    <!-- Cloud Box -->
                                    <rect x="530" y="50" width="250" height="300" fill="none" stroke="#3b82f6" stroke-width="2" stroke-dasharray="5,5" rx="10"/>
                                    <text x="655" y="80" fill="#3b82f6" font-size="14" text-anchor="middle" font-weight="bold">CLOUD (AWS/Azure)</text>

                                    <!-- Cloud IOC -->
                                    <rect x="550" y="100" width="210" height="60" fill="#1f2b47" stroke="#00b894" stroke-width="2" rx="5"/>
                                    <text x="655" y="135" fill="#e2e8f0" font-size="12" text-anchor="middle">IOC Main Server</text>

                                    <!-- Cloud DB -->
                                    <rect x="550" y="180" width="100" height="50" fill="#1f2b47" stroke="#f59e0b" stroke-width="2" rx="5"/>
                                    <text x="600" y="210" fill="#e2e8f0" font-size="11" text-anchor="middle">Cloud DB</text>

                                    <!-- Storage -->
                                    <rect x="660" y="180" width="100" height="50" fill="#1f2b47" stroke="#10b981" stroke-width="2" rx="5"/>
                                    <text x="710" y="210" fill="#e2e8f0" font-size="11" text-anchor="middle">S3/Blob</text>

                                    <!-- Dashboard -->
                                    <rect x="550" y="250" width="210" height="60" fill="#1f2b47" stroke="#00cec9" stroke-width="2" rx="5"/>
                                    <text x="655" y="285" fill="#e2e8f0" font-size="12" text-anchor="middle">Web Dashboard</text>

                                    <!-- Connection Lines -->
                                    <line x1="270" y1="230" x2="530" y2="130" stroke="#00b894" stroke-width="2" marker-end="url(#arrowhead)"/>
                                    <text x="400" y="170" fill="#00b894" font-size="10" text-anchor="middle">VPN / Direct Connect</text>

                                    <!-- Arrow marker -->
                                    <defs>
                                        <marker id="arrowhead" markerWidth="10" markerHeight="7" refX="9" refY="3.5" orient="auto">
                                            <polygon points="0 0, 10 3.5, 0 7" fill="#00b894"/>
                                        </marker>
                                    </defs>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Hybrid Deployment Components</h3>
                    </div>
                    <div class="card-body">
                        <table class="doc-table">
                            <thead>
                                <tr>
                                    <th>Component</th>
                                    <th>Location</th>
                                    <th>Purpose</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>IOC Sync Agent</strong></td>
                                    <td>On-Premises</td>
                                    <td>Collects data from SCADA, PLCs, and local systems; syncs to cloud</td>
                                </tr>
                                <tr>
                                    <td><strong>Local Database</strong></td>
                                    <td>On-Premises</td>
                                    <td>Stores operational data locally for low-latency access and offline operation</td>
                                </tr>
                                <tr>
                                    <td><strong>IOC Main Server</strong></td>
                                    <td>Cloud</td>
                                    <td>Central processing, analytics, and reporting engine</td>
                                </tr>
                                <tr>
                                    <td><strong>Cloud Database</strong></td>
                                    <td>Cloud</td>
                                    <td>Long-term storage, aggregated data, and historical analysis</td>
                                </tr>
                                <tr>
                                    <td><strong>Web Dashboard</strong></td>
                                    <td>Cloud</td>
                                    <td>User interface accessible from anywhere with authentication</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Setting Up VPN Connection</h3>
                    </div>
                    <div class="card-body">
                        <div class="steps">
                            <div class="step">
                                <div class="step-number">1</div>
                                <div class="step-content">
                                    <h4>AWS Site-to-Site VPN</h4>
                                    <div class="code-block">
                                        <code>
<span class="comment"># Create Virtual Private Gateway</span>
aws ec2 create-vpn-gateway --type ipsec.1

<span class="comment"># Create Customer Gateway (your on-premises router)</span>
aws ec2 create-customer-gateway --type ipsec.1 \
    --public-ip YOUR_ONPREM_IP --bgp-asn 65000

<span class="comment"># Create VPN Connection</span>
aws ec2 create-vpn-connection --type ipsec.1 \
    --customer-gateway-id cgw-xxxxx \
    --vpn-gateway-id vgw-xxxxx
                                        </code>
                                    </div>
                                </div>
                            </div>
                            <div class="step">
                                <div class="step-number">2</div>
                                <div class="step-content">
                                    <h4>Azure VPN Gateway</h4>
                                    <div class="code-block">
                                        <code>
<span class="comment"># Create VPN Gateway</span>
az network vnet-gateway create --name IOC-VPN-Gateway \
    --resource-group IOC-RG --vnet IOC-VNet \
    --gateway-type Vpn --vpn-type RouteBased --sku VpnGw1

<span class="comment"># Create Local Network Gateway (on-premises)</span>
az network local-gateway create --name OnPrem-Gateway \
    --resource-group IOC-RG \
    --gateway-ip-address YOUR_ONPREM_IP \
    --local-address-prefixes 10.0.0.0/24
                                        </code>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>IOC Sync Agent Configuration</h3>
                    </div>
                    <div class="card-body">
                        <p>Install and configure the sync agent on your on-premises server:</p>
                        <div class="code-block">
                            <code>
<span class="comment"># config/sync-agent.php</span>
&lt;?php
return [
    <span class="string">'cloud_endpoint'</span> => <span class="string">'https://your-cloud-ioc.example.com/api/sync'</span>,
    <span class="string">'api_key'</span> => <span class="string">'YOUR_SECURE_API_KEY'</span>,
    <span class="string">'sync_interval'</span> => 60, <span class="comment">// seconds</span>
    <span class="string">'data_sources'</span> => [
        <span class="string">'scada'</span> => [
            <span class="string">'enabled'</span> => true,
            <span class="string">'protocol'</span> => <span class="string">'modbus'</span>,
            <span class="string">'host'</span> => <span class="string">'192.168.1.100'</span>,
            <span class="string">'port'</span> => 502
        ],
        <span class="string">'local_db'</span> => [
            <span class="string">'enabled'</span> => true,
            <span class="string">'tables'</span> => [<span class="string">'scan_results'</span>, <span class="string">'alerts'</span>, <span class="string">'sensor_data'</span>]
        ]
    ],
    <span class="string">'offline_mode'</span> => [
        <span class="string">'enabled'</span> => true,
        <span class="string">'queue_max_size'</span> => 10000
    ]
];
                            </code>
                        </div>

                        <div class="info-box warning">
                            <div class="info-box-icon">⚠️</div>
                            <div class="info-box-content">
                                <strong>Security Note</strong>
                                <p>Always use encrypted connections (TLS 1.3) and rotate API keys regularly. Store sensitive credentials in environment variables, not config files.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Data Synchronization Modes</h3>
                    </div>
                    <div class="card-body">
                        <div class="accordion">
                            <div class="accordion-item">
                                <div class="accordion-header" onclick="toggleAccordion(this)">
                                    <span>Real-Time Sync</span>
                                    <span>+</span>
                                </div>
                                <div class="accordion-content">
                                    <p><strong>Use Case:</strong> Critical alerts and live monitoring data</p>
                                    <p>Data is pushed to cloud immediately as it's collected. Requires stable, low-latency connection.</p>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <div class="accordion-header" onclick="toggleAccordion(this)">
                                    <span>Batch Sync</span>
                                    <span>+</span>
                                </div>
                                <div class="accordion-content">
                                    <p><strong>Use Case:</strong> Historical data, logs, and reports</p>
                                    <p>Data is aggregated locally and synced at intervals (e.g., every 5 minutes). More bandwidth-efficient.</p>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <div class="accordion-header" onclick="toggleAccordion(this)">
                                    <span>Offline Queue</span>
                                    <span>+</span>
                                </div>
                                <div class="accordion-content">
                                    <p><strong>Use Case:</strong> Unreliable connections or temporary outages</p>
                                    <p>Data is queued locally when cloud is unreachable and synced when connection is restored.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- POC Setup Module -->
            <div id="poc-setup" class="module">
                <div class="content-header">
                    <div class="breadcrumb">
                        <a href="#">Training Center</a>
                        <span>/</span>
                        <span>Cloud & Deployment</span>
                        <span>/</span>
                        <span>POC Setup</span>
                    </div>
                    <h2>🧪 Proof of Concept (POC) Setup</h2>
                    <p>Quick setup guide for evaluating IOC in a test environment.</p>
                </div>

                <div class="info-box info">
                    <div class="info-box-icon">ℹ️</div>
                    <div class="info-box-content">
                        <strong>POC Overview</strong>
                        <p>A POC deployment allows you to evaluate IOC capabilities in a controlled environment before full production deployment. This typically involves minimal hardware and can be completed in a few hours.</p>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>POC Requirements (Minimal)</h3>
                    </div>
                    <div class="card-body">
                        <table class="doc-table">
                            <thead>
                                <tr>
                                    <th>Component</th>
                                    <th>Minimum Spec</th>
                                    <th>Recommended</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Server/VM</strong></td>
                                    <td>2 CPU, 4GB RAM, 20GB Storage</td>
                                    <td>4 CPU, 8GB RAM, 50GB SSD</td>
                                </tr>
                                <tr>
                                    <td><strong>Operating System</strong></td>
                                    <td>Windows 10/11 or Ubuntu 20.04+</td>
                                    <td>Ubuntu 22.04 LTS</td>
                                </tr>
                                <tr>
                                    <td><strong>Web Server</strong></td>
                                    <td>Apache 2.4+ or Nginx</td>
                                    <td>Apache 2.4 with mod_rewrite</td>
                                </tr>
                                <tr>
                                    <td><strong>PHP</strong></td>
                                    <td>PHP 7.4+</td>
                                    <td>PHP 8.1+</td>
                                </tr>
                                <tr>
                                    <td><strong>Database</strong></td>
                                    <td>MySQL 5.7 or MariaDB 10.3</td>
                                    <td>MySQL 8.0 or MariaDB 10.6</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Quick Start with XAMPP (Windows/Mac)</h3>
                    </div>
                    <div class="card-body">
                        <div class="steps">
                            <div class="step">
                                <div class="step-number">1</div>
                                <div class="step-content">
                                    <h4>Download and Install XAMPP</h4>
                                    <p>Download XAMPP from <a href="https://www.apachefriends.org/" target="_blank" style="color: var(--primary);">apachefriends.org</a></p>
                                    <ul style="padding-left: 25px; line-height: 1.8;">
                                        <li>Choose version with PHP 8.0 or higher</li>
                                        <li>Install to default location (C:\xampp or /Applications/XAMPP)</li>
                                        <li>Select Apache, MySQL, PHP, and phpMyAdmin components</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="step">
                                <div class="step-number">2</div>
                                <div class="step-content">
                                    <h4>Start Services</h4>
                                    <p>Open XAMPP Control Panel and start:</p>
                                    <ul style="padding-left: 25px; line-height: 1.8;">
                                        <li>Apache (Web Server)</li>
                                        <li>MySQL (Database)</li>
                                    </ul>
                                    <div class="info-box tip">
                                        <div class="info-box-icon">💡</div>
                                        <div class="info-box-content">
                                            <strong>Port Conflicts</strong>
                                            <p>If Apache fails to start, check if port 80 is used by another application (Skype, IIS). Change port in httpd.conf if needed.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="step">
                                <div class="step-number">3</div>
                                <div class="step-content">
                                    <h4>Deploy IOC Application</h4>
                                    <div class="code-block">
                                        <code>
<span class="comment"># Extract IOC files to htdocs</span>
Windows: C:\xampp\htdocs\ioc\
Mac/Linux: /opt/lampp/htdocs/ioc/

<span class="comment"># Set file permissions (Linux/Mac)</span>
chmod -R 755 /opt/lampp/htdocs/ioc/
chmod -R 777 /opt/lampp/htdocs/ioc/storage/
chmod -R 777 /opt/lampp/htdocs/ioc/logs/
                                        </code>
                                    </div>
                                </div>
                            </div>
                            <div class="step">
                                <div class="step-number">4</div>
                                <div class="step-content">
                                    <h4>Create Database</h4>
                                    <p>Open phpMyAdmin (http://localhost/phpmyadmin):</p>
                                    <div class="code-block">
                                        <code>
<span class="comment">-- Create database</span>
CREATE DATABASE ioc_poc CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

<span class="comment">-- Create user (optional but recommended)</span>
CREATE USER 'ioc_user'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON ioc_poc.* TO 'ioc_user'@'localhost';
FLUSH PRIVILEGES;
                                        </code>
                                    </div>
                                </div>
                            </div>
                            <div class="step">
                                <div class="step-number">5</div>
                                <div class="step-content">
                                    <h4>Configure Application</h4>
                                    <p>Edit config/database.php:</p>
                                    <div class="code-block">
                                        <code>
&lt;?php
return [
    <span class="string">'host'</span> => <span class="string">'localhost'</span>,
    <span class="string">'database'</span> => <span class="string">'ioc_poc'</span>,
    <span class="string">'username'</span> => <span class="string">'ioc_user'</span>,
    <span class="string">'password'</span> => <span class="string">'secure_password'</span>,
    <span class="string">'charset'</span> => <span class="string">'utf8mb4'</span>
];
                                        </code>
                                    </div>
                                </div>
                            </div>
                            <div class="step">
                                <div class="step-number">6</div>
                                <div class="step-content">
                                    <h4>Run Installation</h4>
                                    <p>Open browser and navigate to:</p>
                                    <div class="code-block">
                                        <code>
http://localhost/ioc/install.php

<span class="comment"># Follow the installation wizard to:</span>
- Verify system requirements
- Import database schema
- Create admin account
- Configure initial settings
                                        </code>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Quick Start with Docker</h3>
                    </div>
                    <div class="card-body">
                        <p>For a faster POC setup, use Docker Compose:</p>
                        <div class="code-block">
                            <code>
<span class="comment"># docker-compose.yml</span>
version: '3.8'

services:
  ioc-web:
    image: ioc/intelligent-operating-centre:latest
    ports:
      - "8080:80"
    environment:
      - DB_HOST=ioc-db
      - DB_NAME=ioc_poc
      - DB_USER=ioc
      - DB_PASS=secure_password
    depends_on:
      - ioc-db
    volumes:
      - ./storage:/var/www/html/storage

  ioc-db:
    image: mariadb:10.6
    environment:
      - MYSQL_ROOT_PASSWORD=root_password
      - MYSQL_DATABASE=ioc_poc
      - MYSQL_USER=ioc
      - MYSQL_PASSWORD=secure_password
    volumes:
      - db_data:/var/lib/mysql

volumes:
  db_data:
                            </code>
                        </div>
                        <div class="code-block" style="margin-top: 15px;">
                            <code>
<span class="comment"># Start the POC environment</span>
docker-compose up -d

<span class="comment"># Access the application</span>
http://localhost:8080

<span class="comment"># View logs</span>
docker-compose logs -f ioc-web

<span class="comment"># Stop when done</span>
docker-compose down
                            </code>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>POC Demo Data</h3>
                    </div>
                    <div class="card-body">
                        <p>Load sample data to demonstrate IOC capabilities:</p>
                        <div class="code-block">
                            <code>
<span class="comment"># Import demo dataset</span>
mysql -u ioc_user -p ioc_poc &lt; demo/sample_data.sql

<span class="comment"># Demo data includes:</span>
- 50 sample network scans
- 200+ vulnerability records
- 5 SCADA device simulations
- 30 days of sensor data
- Sample reports and alerts
                            </code>
                        </div>

                        <div class="info-box tip">
                            <div class="info-box-icon">💡</div>
                            <div class="info-box-content">
                                <strong>Demo Credentials</strong>
                                <p>Default login after installing demo data: <strong>admin@demo.local</strong> / <strong>demo123</strong>. Change these immediately after evaluation.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>POC Evaluation Checklist</h3>
                    </div>
                    <div class="card-body">
                        <div class="steps">
                            <div class="step" style="opacity: 0.9;">
                                <div class="step-number" style="background: var(--success);">✓</div>
                                <div class="step-content">
                                    <h4>Network Scanning</h4>
                                    <p>Run discovery and vulnerability scans on test network</p>
                                </div>
                            </div>
                            <div class="step" style="opacity: 0.9;">
                                <div class="step-number" style="background: var(--success);">✓</div>
                                <div class="step-content">
                                    <h4>SCADA Integration</h4>
                                    <p>Connect to simulated or test SCADA devices</p>
                                </div>
                            </div>
                            <div class="step" style="opacity: 0.9;">
                                <div class="step-number" style="background: var(--success);">✓</div>
                                <div class="step-content">
                                    <h4>Report Generation</h4>
                                    <p>Generate and export sample reports in various formats</p>
                                </div>
                            </div>
                            <div class="step" style="opacity: 0.9;">
                                <div class="step-number" style="background: var(--success);">✓</div>
                                <div class="step-content">
                                    <h4>Alert Configuration</h4>
                                    <p>Set up and test alert thresholds and notifications</p>
                                </div>
                            </div>
                            <div class="step" style="opacity: 0.9;">
                                <div class="step-number" style="background: var(--success);">✓</div>
                                <div class="step-content">
                                    <h4>User Management</h4>
                                    <p>Test role-based access control and permissions</p>
                                </div>
                            </div>
                            <div class="step" style="opacity: 0.9;">
                                <div class="step-number" style="background: var(--success);">✓</div>
                                <div class="step-content">
                                    <h4>Performance Testing</h4>
                                    <p>Evaluate system responsiveness under simulated load</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="info-box warning">
                    <div class="info-box-icon">⚠️</div>
                    <div class="info-box-content">
                        <strong>POC Limitations</strong>
                        <p>POC installations are for evaluation only. Do not use for production data. Demo data and default credentials should never be used in production environments.</p>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Next Steps After POC</h3>
                    </div>
                    <div class="card-body">
                        <ul style="padding-left: 25px; line-height: 2;">
                            <li>Document findings and feature requirements</li>
                            <li>Identify integration points with existing systems</li>
                            <li>Plan production architecture (on-premises, cloud, or hybrid)</li>
                            <li>Estimate storage and compute requirements based on POC data</li>
                            <li>Schedule production deployment and training</li>
                        </ul>
                        <div style="margin-top: 20px;">
                            <button class="btn btn-primary" onclick="showModule('cloud-setup')">☁️ View Cloud Setup Guide</button>
                            <button class="btn btn-secondary" onclick="showModule('cloud-hybrid')" style="margin-left: 10px;">🔄 View Hybrid Architecture</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FAQ Module -->
            <div id="faq" class="module">
                <div class="content-header">
                    <div class="breadcrumb">
                        <a href="#">Training Center</a>
                        <span>/</span>
                        <span>FAQ</span>
                    </div>
                    <h2>Frequently Asked Questions</h2>
                    <p>Common questions and answers.</p>
                </div>

                <div class="card">
                    <div class="card-body" style="padding: 0;">
                        <div class="accordion">
                            <div class="accordion-item">
                                <div class="accordion-header" onclick="toggleAccordion(this)">
                                    <span>How do I reset my password?</span>
                                    <span>+</span>
                                </div>
                                <div class="accordion-content">
                                    <p>Click "Forgot Password" on the login page and enter your email address. You'll receive a password reset link within a few minutes.</p>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <div class="accordion-header" onclick="toggleAccordion(this)">
                                    <span>Why is my scan taking so long?</span>
                                    <span>+</span>
                                </div>
                                <div class="accordion-content">
                                    <p>Scan duration depends on the scan type and network size. Full vulnerability scans can take 30-60 minutes for large networks. Check the progress bar for estimated completion time.</p>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <div class="accordion-header" onclick="toggleAccordion(this)">
                                    <span>How often should I run scans?</span>
                                    <span>+</span>
                                </div>
                                <div class="accordion-content">
                                    <p>We recommend weekly vulnerability scans and daily quick scans for critical systems. Set up scheduled scans to automate this process.</p>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <div class="accordion-header" onclick="toggleAccordion(this)">
                                    <span>Can I export reports in different formats?</span>
                                    <span>+</span>
                                </div>
                                <div class="accordion-content">
                                    <p>Yes! Reports can be exported as PDF, Excel, HTML, and JSON. Use the Report Builder for custom export options.</p>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <div class="accordion-header" onclick="toggleAccordion(this)">
                                    <span>How do I add a new module?</span>
                                    <span>+</span>
                                </div>
                                <div class="accordion-content">
                                    <p>Modules are managed by Super Users. Go to Admin → Module Management to enable or configure modules. Some modules may require additional licensing.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Troubleshooting Module -->
            <div id="troubleshooting" class="module">
                <div class="content-header">
                    <div class="breadcrumb">
                        <a href="#">Training Center</a>
                        <span>/</span>
                        <span>Troubleshooting</span>
                    </div>
                    <h2>Troubleshooting Guide</h2>
                    <p>Solutions to common issues.</p>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Common Issues</h3>
                    </div>
                    <div class="card-body">
                        <div class="accordion">
                            <div class="accordion-item">
                                <div class="accordion-header" onclick="toggleAccordion(this)">
                                    <span>Database Connection Error</span>
                                    <span>+</span>
                                </div>
                                <div class="accordion-content">
                                    <p><strong>Symptoms:</strong> "Unable to connect to database" error</p>
                                    <p><strong>Solutions:</strong></p>
                                    <ol style="padding-left: 25px; line-height: 1.8;">
                                        <li>Check MySQL/MariaDB service is running</li>
                                        <li>Verify credentials in config/database.php</li>
                                        <li>Ensure database exists and user has permissions</li>
                                        <li>Check firewall isn't blocking port 3306</li>
                                    </ol>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <div class="accordion-header" onclick="toggleAccordion(this)">
                                    <span>Scans Not Starting</span>
                                    <span>+</span>
                                </div>
                                <div class="accordion-content">
                                    <p><strong>Symptoms:</strong> Scan button doesn't respond or shows error</p>
                                    <p><strong>Solutions:</strong></p>
                                    <ol style="padding-left: 25px; line-height: 1.8;">
                                        <li>Check PHP max_execution_time setting</li>
                                        <li>Verify user has scan permissions</li>
                                        <li>Check target IP format is valid</li>
                                        <li>Ensure network connectivity to target</li>
                                    </ol>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <div class="accordion-header" onclick="toggleAccordion(this)">
                                    <span>Reports Not Generating</span>
                                    <span>+</span>
                                </div>
                                <div class="accordion-content">
                                    <p><strong>Symptoms:</strong> Report generation hangs or fails</p>
                                    <p><strong>Solutions:</strong></p>
                                    <ol style="padding-left: 25px; line-height: 1.8;">
                                        <li>Increase PHP memory_limit</li>
                                        <li>Check disk space for report storage</li>
                                        <li>Verify write permissions on reports folder</li>
                                        <li>Try generating a smaller date range</li>
                                    </ol>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <div class="accordion-header" onclick="toggleAccordion(this)">
                                    <span>Emails Not Sending</span>
                                    <span>+</span>
                                </div>
                                <div class="accordion-content">
                                    <p><strong>Symptoms:</strong> Alert emails or reports not received</p>
                                    <p><strong>Solutions:</strong></p>
                                    <ol style="padding-left: 25px; line-height: 1.8;">
                                        <li>Verify SMTP configuration settings</li>
                                        <li>Check spam/junk folders</li>
                                        <li>Test with SMTP test tool in admin panel</li>
                                        <li>Verify firewall allows outbound SMTP</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Getting Help</h3>
                    </div>
                    <div class="card-body">
                        <p>If you can't resolve an issue:</p>
                        <ol style="padding-left: 25px; line-height: 2;">
                            <li>Check the system logs in Admin → System Logs</li>
                            <li>Search the FAQ and documentation</li>
                            <li>Contact your system administrator</li>
                            <li>Submit a support ticket via Service Desk</li>
                        </ol>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
    // Module Navigation
    function showModule(moduleId) {
        // Hide all modules
        document.querySelectorAll('.module').forEach(m => m.classList.remove('active'));

        // Show selected module
        document.getElementById(moduleId).classList.add('active');

        // Update nav items
        document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
        event.target.closest('.nav-item')?.classList.add('active');

        // Scroll to top
        window.scrollTo(0, 0);
    }

    // Accordion Toggle
    function toggleAccordion(header) {
        const content = header.nextElementSibling;
        const icon = header.querySelector('span:last-child');

        content.classList.toggle('active');
        icon.textContent = content.classList.contains('active') ? '-' : '+';
    }

    // Quiz functionality
    function selectQuizOption(option) {
        const question = option.closest('.quiz-question');
        question.querySelectorAll('.quiz-option').forEach(o => o.classList.remove('selected'));
        option.classList.add('selected');
    }

    // Progress tracking (localStorage)
    function updateProgress() {
        const completed = JSON.parse(localStorage.getItem('training_completed') || '[]');
        const total = document.querySelectorAll('.lesson-item').length;
        const percent = Math.round((completed.length / total) * 100);

        document.querySelectorAll('.progress-fill').forEach(bar => {
            bar.style.width = percent + '%';
        });
        document.querySelectorAll('.progress-percent').forEach(text => {
            text.textContent = percent + '%';
        });
    }

    // Mark lesson complete
    function markComplete(lessonId) {
        let completed = JSON.parse(localStorage.getItem('training_completed') || '[]');
        if (!completed.includes(lessonId)) {
            completed.push(lessonId);
            localStorage.setItem('training_completed', JSON.stringify(completed));
        }
        updateProgress();
    }

    // Initialize
    document.addEventListener('DOMContentLoaded', function() {
        updateProgress();
    });
    </script>
</body>
</html>
