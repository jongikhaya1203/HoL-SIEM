<?php
/**
 * HoL SIEM Marketing Brochure
 * Professional sales and marketing collateral
 */

require_once __DIR__ . '/classes/Database.php';

try {
    $db = Database::getInstance();
    $settings_result = $db->fetchAll("SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ('app_name', 'company_name', 'logo_url')");
    $app_name = 'HoL Intelligent Operating Centre';
    $company_name = 'Your Organization';
    $logo_url = '';
    foreach ($settings_result as $row) {
        if ($row['setting_key'] === 'app_name') $app_name = $row['setting_value'];
        if ($row['setting_key'] === 'company_name') $company_name = $row['setting_value'];
        if ($row['setting_key'] === 'logo_url') $logo_url = $row['setting_value'];
    }
} catch (Exception $e) {
    $app_name = 'HoL Intelligent Operating Centre';
    $company_name = 'Your Organization';
    $logo_url = '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HoL SIEM - Product Brochure</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #0f172a;
            --primary-light: #1e293b;
            --accent: #3b82f6;
            --accent-light: #60a5fa;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --purple: #8b5cf6;
            --pink: #ec4899;
            --cyan: #06b6d4;
            --text: #1e293b;
            --text-light: #64748b;
            --white: #ffffff;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
        }

        @media print {
            body {
                font-size: 10pt;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .no-print { display: none !important; }
            .page {
                page-break-after: always;
                page-break-inside: avoid;
            }
            .page:last-child { page-break-after: auto; }
            @page {
                margin: 0;
                size: A4;
            }
        }

        body {
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--gray-100);
            color: var(--text);
            line-height: 1.6;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
        }

        /* Print Controls */
        .print-controls {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary { background: var(--accent); color: white; }
        .btn-primary:hover { background: #2563eb; }
        .btn-secondary { background: var(--primary); color: white; }

        /* Page Styles */
        .page {
            background: white;
            min-height: 100vh;
            position: relative;
            overflow: hidden;
        }

        /* Cover Page */
        .cover-page {
            background: linear-gradient(135deg, var(--primary) 0%, #1e3a5f 50%, var(--accent) 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            color: white;
            padding: 60px;
        }

        .cover-page::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="40" stroke="rgba(255,255,255,0.05)" stroke-width="0.5" fill="none"/><circle cx="50" cy="50" r="30" stroke="rgba(255,255,255,0.05)" stroke-width="0.5" fill="none"/><circle cx="50" cy="50" r="20" stroke="rgba(255,255,255,0.05)" stroke-width="0.5" fill="none"/></svg>');
            opacity: 0.3;
        }

        .cover-content {
            position: relative;
            z-index: 1;
        }

        .cover-badge {
            background: rgba(255,255,255,0.15);
            padding: 8px 24px;
            border-radius: 30px;
            font-size: 14px;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 30px;
            backdrop-filter: blur(10px);
        }

        .cover-logo {
            font-size: 100px;
            margin-bottom: 20px;
            filter: drop-shadow(0 10px 30px rgba(0,0,0,0.3));
        }

        .cover-title {
            font-size: 52px;
            font-weight: 800;
            margin-bottom: 15px;
            text-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }

        .cover-subtitle {
            font-size: 24px;
            font-weight: 300;
            opacity: 0.9;
            margin-bottom: 40px;
        }

        .cover-tagline {
            font-size: 18px;
            max-width: 600px;
            opacity: 0.8;
            line-height: 1.8;
        }

        .cover-stats {
            display: flex;
            gap: 50px;
            margin-top: 60px;
        }

        .cover-stat {
            text-align: center;
        }

        .cover-stat-value {
            font-size: 42px;
            font-weight: 700;
        }

        .cover-stat-label {
            font-size: 14px;
            opacity: 0.8;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Section Pages */
        .section-page {
            padding: 60px;
        }

        .section-header {
            text-align: center;
            margin-bottom: 50px;
        }

        .section-icon {
            font-size: 50px;
            margin-bottom: 15px;
        }

        .section-title {
            font-size: 36px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 10px;
        }

        .section-subtitle {
            font-size: 18px;
            color: var(--text-light);
        }

        /* Feature Cards */
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 25px;
            margin: 40px 0;
        }

        .feature-card {
            background: linear-gradient(135deg, var(--gray-50), white);
            border: 1px solid var(--gray-200);
            border-radius: 16px;
            padding: 30px;
            text-align: center;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--accent), var(--purple));
        }

        .feature-card .icon {
            font-size: 40px;
            margin-bottom: 15px;
        }

        .feature-card h3 {
            font-size: 18px;
            color: var(--primary);
            margin-bottom: 10px;
        }

        .feature-card p {
            font-size: 13px;
            color: var(--text-light);
            line-height: 1.6;
        }

        /* Module Cards */
        .module-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 25px;
        }

        .module-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }

        .module-card-header {
            padding: 25px;
            color: white;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .module-card-header.security { background: linear-gradient(135deg, #3b82f6, #1d4ed8); }
        .module-card-header.scada { background: linear-gradient(135deg, #10b981, #059669); }
        .module-card-header.dlp { background: linear-gradient(135deg, #8b5cf6, #6d28d9); }
        .module-card-header.service { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .module-card-header.dashboard { background: linear-gradient(135deg, #ec4899, #be185d); }
        .module-card-header.training { background: linear-gradient(135deg, #06b6d4, #0891b2); }
        .module-card-header.reports { background: linear-gradient(135deg, #64748b, #475569); }
        .module-card-header.assets { background: linear-gradient(135deg, #84cc16, #65a30d); }

        .module-card-header .icon {
            font-size: 36px;
        }

        .module-card-header h3 {
            font-size: 20px;
            font-weight: 600;
        }

        .module-card-body {
            padding: 25px;
        }

        .module-card-body ul {
            list-style: none;
            padding: 0;
        }

        .module-card-body li {
            padding: 8px 0;
            font-size: 14px;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .module-card-body li::before {
            content: '✓';
            color: var(--success);
            font-weight: bold;
        }

        /* Dashboard Preview */
        .dashboard-preview {
            background: var(--primary);
            border-radius: 16px;
            padding: 20px;
            margin: 30px 0;
        }

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 20px;
        }

        .dashboard-header h4 {
            color: white;
            font-size: 16px;
        }

        .dashboard-dots {
            display: flex;
            gap: 6px;
        }

        .dashboard-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }

        .dashboard-dot.red { background: #ef4444; }
        .dashboard-dot.yellow { background: #f59e0b; }
        .dashboard-dot.green { background: #10b981; }

        .dashboard-widgets {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
        }

        .widget {
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            color: white;
        }

        .widget-value {
            font-size: 28px;
            font-weight: 700;
        }

        .widget-label {
            font-size: 11px;
            opacity: 0.7;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 5px;
        }

        .widget.green .widget-value { color: #10b981; }
        .widget.blue .widget-value { color: #3b82f6; }
        .widget.yellow .widget-value { color: #f59e0b; }
        .widget.red .widget-value { color: #ef4444; }

        /* Benefits Section */
        .benefits-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 30px;
        }

        .benefit-item {
            display: flex;
            gap: 20px;
            align-items: flex-start;
        }

        .benefit-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--accent), var(--purple));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            flex-shrink: 0;
        }

        .benefit-content h4 {
            font-size: 18px;
            color: var(--primary);
            margin-bottom: 8px;
        }

        .benefit-content p {
            font-size: 14px;
            color: var(--text-light);
        }

        /* Stats Bar */
        .stats-bar {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            border-radius: 16px;
            overflow: hidden;
            margin: 40px 0;
        }

        .stat-item {
            padding: 30px;
            text-align: center;
            color: white;
            border-right: 1px solid rgba(255,255,255,0.1);
        }

        .stat-item:last-child {
            border-right: none;
        }

        .stat-value {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 13px;
            opacity: 0.8;
        }

        /* Use Cases */
        .usecase-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        .usecase-card {
            background: white;
            border: 2px solid var(--gray-200);
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            transition: all 0.3s;
        }

        .usecase-card:hover {
            border-color: var(--accent);
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .usecase-icon {
            font-size: 40px;
            margin-bottom: 15px;
        }

        .usecase-card h4 {
            font-size: 16px;
            color: var(--primary);
            margin-bottom: 10px;
        }

        .usecase-card p {
            font-size: 13px;
            color: var(--text-light);
        }

        /* SCADA Visualization */
        .scada-preview {
            background: linear-gradient(135deg, #064e3b, #065f46);
            border-radius: 16px;
            padding: 30px;
            margin: 30px 0;
        }

        .scada-header {
            color: white;
            margin-bottom: 20px;
        }

        .scada-header h4 {
            font-size: 18px;
            margin-bottom: 5px;
        }

        .scada-header p {
            font-size: 13px;
            opacity: 0.8;
        }

        .tank-row {
            display: flex;
            gap: 20px;
            justify-content: center;
        }

        .tank {
            text-align: center;
        }

        .tank-visual {
            width: 60px;
            height: 100px;
            background: rgba(0,0,0,0.3);
            border-radius: 8px;
            position: relative;
            overflow: hidden;
            margin: 0 auto 10px;
            border: 2px solid rgba(255,255,255,0.3);
        }

        .tank-fill {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, #10b981, #34d399);
            transition: height 0.5s;
        }

        .tank-label {
            color: white;
            font-size: 11px;
            opacity: 0.9;
        }

        .tank-value {
            color: #10b981;
            font-size: 14px;
            font-weight: bold;
        }

        /* Pricing */
        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 25px;
        }

        .pricing-card {
            background: white;
            border: 2px solid var(--gray-200);
            border-radius: 16px;
            overflow: hidden;
            text-align: center;
        }

        .pricing-card.featured {
            border-color: var(--accent);
            transform: scale(1.05);
            box-shadow: 0 10px 40px rgba(59, 130, 246, 0.2);
        }

        .pricing-header {
            padding: 30px;
            background: var(--gray-50);
        }

        .pricing-card.featured .pricing-header {
            background: linear-gradient(135deg, var(--accent), var(--purple));
            color: white;
        }

        .pricing-name {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .pricing-price {
            font-size: 36px;
            font-weight: 700;
        }

        .pricing-period {
            font-size: 14px;
            opacity: 0.7;
        }

        .pricing-features {
            padding: 30px;
            text-align: left;
        }

        .pricing-features li {
            padding: 10px 0;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .pricing-features li::before {
            content: '✓';
            color: var(--success);
            font-weight: bold;
        }

        /* Contact Section */
        .contact-section {
            background: linear-gradient(135deg, var(--primary), #1e3a5f);
            color: white;
            padding: 60px;
            text-align: center;
        }

        .contact-section h2 {
            font-size: 32px;
            margin-bottom: 15px;
        }

        .contact-section p {
            font-size: 18px;
            opacity: 0.9;
            margin-bottom: 40px;
        }

        .contact-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
            max-width: 700px;
            margin: 0 auto;
        }

        .contact-item {
            text-align: center;
        }

        .contact-icon {
            font-size: 32px;
            margin-bottom: 10px;
        }

        .contact-label {
            font-size: 12px;
            opacity: 0.7;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .contact-value {
            font-size: 16px;
            font-weight: 600;
            margin-top: 5px;
        }

        /* Footer */
        .brochure-footer {
            background: var(--primary);
            color: white;
            padding: 30px 60px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .footer-logo {
            font-size: 20px;
            font-weight: 700;
        }

        .footer-text {
            font-size: 12px;
            opacity: 0.7;
        }

        /* Certifications */
        .cert-row {
            display: flex;
            justify-content: center;
            gap: 40px;
            margin-top: 40px;
        }

        .cert-badge {
            background: var(--gray-100);
            padding: 15px 25px;
            border-radius: 10px;
            text-align: center;
        }

        .cert-badge .icon {
            font-size: 24px;
            margin-bottom: 5px;
        }

        .cert-badge .label {
            font-size: 11px;
            color: var(--text-light);
            text-transform: uppercase;
        }

        /* Testimonial */
        .testimonial {
            background: var(--gray-50);
            border-left: 4px solid var(--accent);
            padding: 30px;
            border-radius: 0 12px 12px 0;
            margin: 30px 0;
        }

        .testimonial-text {
            font-size: 18px;
            font-style: italic;
            color: var(--text);
            margin-bottom: 15px;
        }

        .testimonial-author {
            font-size: 14px;
            color: var(--text-light);
        }

        .testimonial-author strong {
            color: var(--primary);
        }
    </style>
</head>
<body>
    <!-- Print Controls -->
    <div class="print-controls no-print">
        <button class="btn btn-primary" onclick="window.print()">Print / Save as PDF</button>
        <a href="index.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>

    <div class="container">
        <!-- Page 1: Cover -->
        <div class="page cover-page">
            <div class="cover-content">
                <div class="cover-badge">Enterprise Security Platform</div>
                <?php if ($logo_url): ?>
                <div class="cover-logo"><img src="<?= htmlspecialchars($logo_url) ?>" alt="HoL SIEM Logo" style="max-height: 120px; max-width: 200px;"></div>
                <?php else: ?>
                <div class="cover-logo">🛡️</div>
                <?php endif; ?>
                <h1 class="cover-title">HoL SIEM</h1>
                <p class="cover-subtitle">Intelligent Operating Centre</p>
                <p class="cover-tagline">
                    Unified IT/OT Security Platform for Modern Industrial Operations.<br>
                    Monitor, Detect, Respond, and Protect your critical infrastructure.
                </p>
                <div class="cover-stats">
                    <div class="cover-stat">
                        <div class="cover-stat-value">15+</div>
                        <div class="cover-stat-label">Modules</div>
                    </div>
                    <div class="cover-stat">
                        <div class="cover-stat-value">99.9%</div>
                        <div class="cover-stat-label">Uptime</div>
                    </div>
                    <div class="cover-stat">
                        <div class="cover-stat-value">1M+</div>
                        <div class="cover-stat-label">Events/Day</div>
                    </div>
                    <div class="cover-stat">
                        <div class="cover-stat-value">24/7</div>
                        <div class="cover-stat-label">Monitoring</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Page 2: Overview -->
        <div class="page section-page">
            <div class="section-header">
                <div class="section-icon">🎯</div>
                <h2 class="section-title">Why HoL SIEM?</h2>
                <p class="section-subtitle">The Complete Security Operations Platform</p>
            </div>

            <div class="feature-grid">
                <div class="feature-card">
                    <div class="icon">🔍</div>
                    <h3>Unified Visibility</h3>
                    <p>Single pane of glass for IT infrastructure, OT systems, and SCADA networks</p>
                </div>
                <div class="feature-card">
                    <div class="icon">⚡</div>
                    <h3>Real-Time Detection</h3>
                    <p>Sub-second threat detection with AI-powered analytics and correlation</p>
                </div>
                <div class="feature-card">
                    <div class="icon">🛡️</div>
                    <h3>Defense in Depth</h3>
                    <p>Multi-layer security architecture following industry best practices</p>
                </div>
                <div class="feature-card">
                    <div class="icon">🏭</div>
                    <h3>IT/OT Convergence</h3>
                    <p>Bridge the gap between enterprise IT and industrial control systems</p>
                </div>
                <div class="feature-card">
                    <div class="icon">📊</div>
                    <h3>Compliance Ready</h3>
                    <p>Built-in support for NIST, IEC 62443, NERC CIP, and ISO 27001</p>
                </div>
                <div class="feature-card">
                    <div class="icon">🚀</div>
                    <h3>Rapid Deployment</h3>
                    <p>Get operational in days, not months, with flexible deployment options</p>
                </div>
            </div>

            <div class="stats-bar">
                <div class="stat-item">
                    <div class="stat-value">70%</div>
                    <div class="stat-label">Faster Threat Detection</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">50%</div>
                    <div class="stat-label">Reduced Alert Fatigue</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">90%</div>
                    <div class="stat-label">Automation Rate</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">3x</div>
                    <div class="stat-label">ROI in Year 1</div>
                </div>
            </div>

            <div class="testimonial">
                <p class="testimonial-text">"HoL SIEM transformed our security operations. We now have complete visibility across our IT and OT environments, reducing our incident response time by 65%."</p>
                <p class="testimonial-author">— <strong>Chief Security Officer</strong>, Major Energy Company</p>
            </div>
        </div>

        <!-- Page 3: Core Modules -->
        <div class="page section-page">
            <div class="section-header">
                <div class="section-icon">📦</div>
                <h2 class="section-title">Core Modules</h2>
                <p class="section-subtitle">Comprehensive security and operations capabilities</p>
            </div>

            <div class="module-grid">
                <div class="module-card">
                    <div class="module-card-header security">
                        <span class="icon">🔍</span>
                        <h3>Network Security Scanner</h3>
                    </div>
                    <div class="module-card-body">
                        <ul>
                            <li>Automated network discovery</li>
                            <li>Vulnerability assessment</li>
                            <li>Port scanning & service detection</li>
                            <li>CVE correlation & prioritization</li>
                            <li>Compliance baseline checking</li>
                            <li>Scheduled & on-demand scans</li>
                        </ul>
                    </div>
                </div>

                <div class="module-card">
                    <div class="module-card-header scada">
                        <span class="icon">🏭</span>
                        <h3>SCADA/ICS Monitoring</h3>
                    </div>
                    <div class="module-card-body">
                        <ul>
                            <li>Real-time process visualization</li>
                            <li>Tank level monitoring</li>
                            <li>Pump station control</li>
                            <li>Pipeline leak detection</li>
                            <li>Alarm management</li>
                            <li>Protocol support: Modbus, DNP3, OPC</li>
                        </ul>
                    </div>
                </div>

                <div class="module-card">
                    <div class="module-card-header dlp">
                        <span class="icon">🔐</span>
                        <h3>Data Loss Prevention</h3>
                    </div>
                    <div class="module-card-body">
                        <ul>
                            <li>Content inspection & classification</li>
                            <li>Policy-based protection</li>
                            <li>Endpoint monitoring</li>
                            <li>Email & web filtering</li>
                            <li>Incident investigation</li>
                            <li>Regulatory compliance</li>
                        </ul>
                    </div>
                </div>

                <div class="module-card">
                    <div class="module-card-header service">
                        <span class="icon">🎫</span>
                        <h3>Service Desk</h3>
                    </div>
                    <div class="module-card-body">
                        <ul>
                            <li>Incident management</li>
                            <li>Change control workflow</li>
                            <li>Problem management</li>
                            <li>Asset tracking</li>
                            <li>Knowledge base</li>
                            <li>SLA monitoring</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="scada-preview">
                <div class="scada-header">
                    <h4>🛢️ Real-Time Tank Monitoring</h4>
                    <p>Live visualization of storage tanks with pump rates and level alerts</p>
                </div>
                <div class="tank-row">
                    <div class="tank">
                        <div class="tank-visual">
                            <div class="tank-fill" style="height: 71%;"></div>
                        </div>
                        <div class="tank-label">Crude Oil T-101</div>
                        <div class="tank-value">71%</div>
                    </div>
                    <div class="tank">
                        <div class="tank-visual">
                            <div class="tank-fill" style="height: 84%;"></div>
                        </div>
                        <div class="tank-label">Crude Oil T-102</div>
                        <div class="tank-value">84%</div>
                    </div>
                    <div class="tank">
                        <div class="tank-visual">
                            <div class="tank-fill" style="height: 62%; background: linear-gradient(to top, #f59e0b, #fbbf24);"></div>
                        </div>
                        <div class="tank-label">Diesel T-103</div>
                        <div class="tank-value" style="color: #f59e0b;">62%</div>
                    </div>
                    <div class="tank">
                        <div class="tank-visual">
                            <div class="tank-fill" style="height: 88%;"></div>
                        </div>
                        <div class="tank-label">Gasoline T-104</div>
                        <div class="tank-value">88%</div>
                    </div>
                    <div class="tank">
                        <div class="tank-visual">
                            <div class="tank-fill" style="height: 42%; background: linear-gradient(to top, #ef4444, #f87171);"></div>
                        </div>
                        <div class="tank-label">Kerosene T-105</div>
                        <div class="tank-value" style="color: #ef4444;">42%</div>
                    </div>
                    <div class="tank">
                        <div class="tank-visual">
                            <div class="tank-fill" style="height: 78%;"></div>
                        </div>
                        <div class="tank-label">Heavy Fuel T-106</div>
                        <div class="tank-value">78%</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Page 4: More Modules -->
        <div class="page section-page">
            <div class="section-header">
                <div class="section-icon">⚙️</div>
                <h2 class="section-title">Additional Modules</h2>
                <p class="section-subtitle">Extended capabilities for enterprise operations</p>
            </div>

            <div class="module-grid">
                <div class="module-card">
                    <div class="module-card-header dashboard">
                        <span class="icon">📊</span>
                        <h3>Custom Dashboards</h3>
                    </div>
                    <div class="module-card-body">
                        <ul>
                            <li>Drag-and-drop widget builder</li>
                            <li>Real-time data visualization</li>
                            <li>Role-based views</li>
                            <li>KPI tracking</li>
                            <li>Executive summaries</li>
                            <li>Shareable dashboards</li>
                        </ul>
                    </div>
                </div>

                <div class="module-card">
                    <div class="module-card-header training">
                        <span class="icon">🎓</span>
                        <h3>Training Center</h3>
                    </div>
                    <div class="module-card-body">
                        <ul>
                            <li>Interactive learning modules</li>
                            <li>SCADA operations training</li>
                            <li>Security awareness</li>
                            <li>Certification tracking</li>
                            <li>Progress monitoring</li>
                            <li>Cloud deployment guides</li>
                        </ul>
                    </div>
                </div>

                <div class="module-card">
                    <div class="module-card-header reports">
                        <span class="icon">📈</span>
                        <h3>Reports & Analytics</h3>
                    </div>
                    <div class="module-card-body">
                        <ul>
                            <li>Automated report generation</li>
                            <li>Compliance reports</li>
                            <li>Executive summaries</li>
                            <li>Trend analysis</li>
                            <li>Scheduled delivery</li>
                            <li>PDF, Excel, CSV export</li>
                        </ul>
                    </div>
                </div>

                <div class="module-card">
                    <div class="module-card-header assets">
                        <span class="icon">🖥️</span>
                        <h3>Asset Management</h3>
                    </div>
                    <div class="module-card-body">
                        <ul>
                            <li>Automatic discovery</li>
                            <li>Hardware inventory</li>
                            <li>Software tracking</li>
                            <li>Lifecycle management</li>
                            <li>CMDB integration</li>
                            <li>Dependency mapping</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Dashboard Preview -->
            <div class="dashboard-preview">
                <div class="dashboard-header">
                    <h4>📊 Executive Dashboard</h4>
                    <div class="dashboard-dots">
                        <div class="dashboard-dot red"></div>
                        <div class="dashboard-dot yellow"></div>
                        <div class="dashboard-dot green"></div>
                    </div>
                </div>
                <div class="dashboard-widgets">
                    <div class="widget green">
                        <div class="widget-value">847</div>
                        <div class="widget-label">Assets Monitored</div>
                    </div>
                    <div class="widget blue">
                        <div class="widget-value">12</div>
                        <div class="widget-label">Active Scans</div>
                    </div>
                    <div class="widget yellow">
                        <div class="widget-value">23</div>
                        <div class="widget-label">Open Alerts</div>
                    </div>
                    <div class="widget red">
                        <div class="widget-value">3</div>
                        <div class="widget-label">Critical Issues</div>
                    </div>
                </div>
            </div>

            <div class="cert-row">
                <div class="cert-badge">
                    <div class="icon">🔒</div>
                    <div class="label">ISO 27001</div>
                </div>
                <div class="cert-badge">
                    <div class="icon">🏭</div>
                    <div class="label">IEC 62443</div>
                </div>
                <div class="cert-badge">
                    <div class="icon">⚡</div>
                    <div class="label">NERC CIP</div>
                </div>
                <div class="cert-badge">
                    <div class="icon">🛡️</div>
                    <div class="label">NIST CSF</div>
                </div>
                <div class="cert-badge">
                    <div class="icon">📋</div>
                    <div class="label">SOC 2</div>
                </div>
            </div>
        </div>

        <!-- Page 5: Use Cases -->
        <div class="page section-page">
            <div class="section-header">
                <div class="section-icon">🏢</div>
                <h2 class="section-title">Industry Solutions</h2>
                <p class="section-subtitle">Trusted across critical infrastructure sectors</p>
            </div>

            <div class="usecase-grid">
                <div class="usecase-card">
                    <div class="usecase-icon">🛢️</div>
                    <h4>Oil & Gas</h4>
                    <p>Pipeline monitoring, refinery operations, tank farm management, and safety systems</p>
                </div>
                <div class="usecase-card">
                    <div class="usecase-icon">⚡</div>
                    <h4>Power & Utilities</h4>
                    <p>Grid monitoring, substation protection, SCADA security, and compliance</p>
                </div>
                <div class="usecase-card">
                    <div class="usecase-icon">🏭</div>
                    <h4>Manufacturing</h4>
                    <p>Production line monitoring, quality control, and industrial IoT security</p>
                </div>
                <div class="usecase-card">
                    <div class="usecase-icon">💧</div>
                    <h4>Water & Wastewater</h4>
                    <p>Treatment plant monitoring, distribution networks, and quality assurance</p>
                </div>
                <div class="usecase-card">
                    <div class="usecase-icon">🚂</div>
                    <h4>Transportation</h4>
                    <p>Rail networks, port operations, and logistics infrastructure</p>
                </div>
                <div class="usecase-card">
                    <div class="usecase-icon">🏥</div>
                    <h4>Healthcare</h4>
                    <p>Medical device monitoring, facility management, and data protection</p>
                </div>
            </div>

            <div class="benefits-grid" style="margin-top: 50px;">
                <div class="benefit-item">
                    <div class="benefit-icon">💰</div>
                    <div class="benefit-content">
                        <h4>Reduce Operational Costs</h4>
                        <p>Consolidate multiple tools into one platform. Automate routine tasks and reduce manual effort by up to 70%.</p>
                    </div>
                </div>
                <div class="benefit-item">
                    <div class="benefit-icon">🎯</div>
                    <div class="benefit-content">
                        <h4>Improve Detection Accuracy</h4>
                        <p>AI-powered correlation reduces false positives by 50% while catching threats others miss.</p>
                    </div>
                </div>
                <div class="benefit-item">
                    <div class="benefit-icon">⏱️</div>
                    <div class="benefit-content">
                        <h4>Accelerate Response Time</h4>
                        <p>Automated playbooks and workflows reduce mean time to respond (MTTR) from hours to minutes.</p>
                    </div>
                </div>
                <div class="benefit-item">
                    <div class="benefit-icon">📊</div>
                    <div class="benefit-content">
                        <h4>Demonstrate Compliance</h4>
                        <p>Pre-built reports for major frameworks. Audit-ready documentation at the click of a button.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Page 6: Deployment & Pricing -->
        <div class="page section-page">
            <div class="section-header">
                <div class="section-icon">☁️</div>
                <h2 class="section-title">Flexible Deployment</h2>
                <p class="section-subtitle">Choose the model that fits your organization</p>
            </div>

            <div class="pricing-grid">
                <div class="pricing-card">
                    <div class="pricing-header">
                        <div class="pricing-name">On-Premises</div>
                        <div class="pricing-price">Self-Hosted</div>
                        <div class="pricing-period">Full control</div>
                    </div>
                    <div class="pricing-features">
                        <ul>
                            <li>Deploy in your data center</li>
                            <li>Complete data sovereignty</li>
                            <li>Air-gapped option available</li>
                            <li>Perpetual licensing</li>
                            <li>Annual maintenance</li>
                            <li>On-site support available</li>
                        </ul>
                    </div>
                </div>

                <div class="pricing-card featured">
                    <div class="pricing-header">
                        <div class="pricing-name">Hybrid Cloud</div>
                        <div class="pricing-price">Best Value</div>
                        <div class="pricing-period">Recommended</div>
                    </div>
                    <div class="pricing-features">
                        <ul>
                            <li>SCADA on-premises</li>
                            <li>Analytics in cloud</li>
                            <li>Secure VPN connectivity</li>
                            <li>Elastic scaling</li>
                            <li>Subscription pricing</li>
                            <li>24/7 managed support</li>
                        </ul>
                    </div>
                </div>

                <div class="pricing-card">
                    <div class="pricing-header">
                        <div class="pricing-name">Full Cloud</div>
                        <div class="pricing-price">SaaS</div>
                        <div class="pricing-period">Zero infrastructure</div>
                    </div>
                    <div class="pricing-features">
                        <ul>
                            <li>AWS, Azure, or GCP</li>
                            <li>Fully managed service</li>
                            <li>Auto-scaling included</li>
                            <li>Pay-as-you-go</li>
                            <li>Automatic updates</li>
                            <li>Global availability</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="stats-bar" style="margin-top: 50px;">
                <div class="stat-item">
                    <div class="stat-value">500+</div>
                    <div class="stat-label">Enterprise Customers</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">40+</div>
                    <div class="stat-label">Countries</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">10M+</div>
                    <div class="stat-label">Assets Protected</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">99.9%</div>
                    <div class="stat-label">Customer Satisfaction</div>
                </div>
            </div>

            <div class="testimonial">
                <p class="testimonial-text">"The hybrid deployment model was perfect for us. We kept our SCADA systems on-premises for reliability while leveraging cloud analytics for advanced threat detection."</p>
                <p class="testimonial-author">— <strong>IT Director</strong>, Regional Utility Provider</p>
            </div>
        </div>

        <!-- Page 7: Contact -->
        <div class="page">
            <div class="contact-section" style="min-height: 100vh; display: flex; flex-direction: column; justify-content: center;">
                <div class="section-icon" style="font-size: 80px; margin-bottom: 20px;">🚀</div>
                <h2>Ready to Get Started?</h2>
                <p>Contact us today for a personalized demo and see how HoL SIEM can transform your security operations.</p>

                <div class="contact-grid">
                    <div class="contact-item">
                        <div class="contact-icon">📧</div>
                        <div class="contact-label">Email</div>
                        <div class="contact-value">sales@ioc-siem.com</div>
                    </div>
                    <div class="contact-item">
                        <div class="contact-icon">📞</div>
                        <div class="contact-label">Phone</div>
                        <div class="contact-value">+1 (800) HOL-SIEM</div>
                    </div>
                    <div class="contact-item">
                        <div class="contact-icon">🌐</div>
                        <div class="contact-label">Website</div>
                        <div class="contact-value">www.ioc-siem.com</div>
                    </div>
                </div>

                <div style="margin-top: 60px;">
                    <a href="#" class="btn btn-primary" style="font-size: 18px; padding: 18px 50px;">Request a Demo</a>
                </div>

                <div style="margin-top: 80px; opacity: 0.7;">
                    <p style="font-size: 14px;">© <?= date('Y') ?> HoL SIEM. All rights reserved.</p>
                    <p style="font-size: 12px; margin-top: 10px;">HoL SIEM is a trademark of <?= htmlspecialchars($company_name) ?>.</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
