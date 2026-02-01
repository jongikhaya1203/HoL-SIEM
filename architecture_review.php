<?php
/**
 * IOC Intelligent Operating Centre - Architecture Review Document
 * Comprehensive system architecture documentation for stakeholders
 */

require_once __DIR__ . '/classes/Database.php';

// Get app settings
try {
    $db = Database::getInstance();
    $settings_result = $db->fetchAll("SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ('app_name', 'company_name')");
    $app_name = 'IOC Intelligent Operating Centre';
    $company_name = 'Organization';
    foreach ($settings_result as $row) {
        if ($row['setting_key'] === 'app_name') $app_name = $row['setting_value'];
        if ($row['setting_key'] === 'company_name') $company_name = $row['setting_value'];
    }
} catch (Exception $e) {
    $app_name = 'IOC Intelligent Operating Centre';
    $company_name = 'Organization';
}

$version = '2.0';
$date = date('F Y');
$doc_id = 'IOC-ARCH-' . date('Ymd');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Architecture Review Document | <?= htmlspecialchars($app_name) ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #1a365d;
            --primary-light: #2c5282;
            --secondary: #2d3748;
            --accent: #3182ce;
            --success: #38a169;
            --warning: #d69e2e;
            --danger: #e53e3e;
            --text: #1a202c;
            --text-light: #4a5568;
            --bg: #ffffff;
            --bg-alt: #f7fafc;
            --border: #e2e8f0;
        }

        @media print {
            body {
                font-size: 11pt;
                line-height: 1.4;
            }
            .no-print { display: none !important; }
            .page-break { page-break-before: always; }
            .avoid-break { page-break-inside: avoid; }
            @page {
                margin: 1in 0.75in;
                size: A4;
            }
            .cover-page {
                height: 100vh;
                page-break-after: always;
            }
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Print Button */
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
        }

        .btn-primary {
            background: var(--accent);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-light);
        }

        .btn-secondary {
            background: var(--secondary);
            color: white;
        }

        /* Cover Page */
        .cover-page {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            padding: 60px;
            margin: -20px -20px 40px -20px;
        }

        .cover-logo {
            font-size: 80px;
            margin-bottom: 30px;
        }

        .cover-title {
            font-size: 42px;
            font-weight: 700;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .cover-subtitle {
            font-size: 28px;
            font-weight: 300;
            margin-bottom: 40px;
            opacity: 0.9;
        }

        .cover-meta {
            font-size: 16px;
            opacity: 0.8;
            margin-top: 60px;
        }

        .cover-meta div {
            margin: 8px 0;
        }

        .cover-badge {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            padding: 8px 20px;
            border-radius: 20px;
            margin-top: 20px;
            font-size: 14px;
        }

        /* Document Info */
        .doc-info {
            background: var(--bg-alt);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 40px;
        }

        .doc-info-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        .doc-info-item label {
            font-size: 12px;
            color: var(--text-light);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .doc-info-item p {
            font-size: 14px;
            font-weight: 600;
            margin-top: 5px;
        }

        /* Table of Contents */
        .toc {
            background: var(--bg-alt);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 30px;
            margin-bottom: 40px;
        }

        .toc h2 {
            font-size: 24px;
            margin-bottom: 20px;
            color: var(--primary);
            border-bottom: 2px solid var(--accent);
            padding-bottom: 10px;
        }

        .toc-list {
            list-style: none;
        }

        .toc-list li {
            padding: 8px 0;
            border-bottom: 1px dotted var(--border);
            display: flex;
            justify-content: space-between;
        }

        .toc-list li:last-child {
            border-bottom: none;
        }

        .toc-list a {
            color: var(--text);
            text-decoration: none;
        }

        .toc-list a:hover {
            color: var(--accent);
        }

        .toc-section {
            font-weight: 600;
        }

        .toc-subsection {
            padding-left: 25px;
            font-size: 14px;
        }

        /* Sections */
        .section {
            margin-bottom: 50px;
        }

        .section-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            padding: 20px 30px;
            border-radius: 8px 8px 0 0;
            margin-bottom: 0;
        }

        .section-header h2 {
            font-size: 24px;
            margin: 0;
        }

        .section-header .section-num {
            font-size: 14px;
            opacity: 0.8;
            margin-bottom: 5px;
        }

        .section-content {
            background: var(--bg);
            border: 1px solid var(--border);
            border-top: none;
            border-radius: 0 0 8px 8px;
            padding: 30px;
        }

        /* Typography */
        h3 {
            font-size: 20px;
            color: var(--primary);
            margin: 25px 0 15px 0;
            padding-bottom: 8px;
            border-bottom: 1px solid var(--border);
        }

        h4 {
            font-size: 16px;
            color: var(--secondary);
            margin: 20px 0 10px 0;
        }

        p {
            margin-bottom: 15px;
            text-align: justify;
        }

        ul, ol {
            margin: 15px 0;
            padding-left: 25px;
        }

        li {
            margin-bottom: 8px;
        }

        /* Tables */
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 14px;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border: 1px solid var(--border);
        }

        th {
            background: var(--primary);
            color: white;
            font-weight: 600;
        }

        tr:nth-child(even) {
            background: var(--bg-alt);
        }

        /* Diagrams */
        .diagram {
            background: var(--bg-alt);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 30px;
            margin: 25px 0;
            text-align: center;
        }

        .diagram-title {
            font-size: 14px;
            color: var(--text-light);
            margin-top: 15px;
            font-style: italic;
        }

        /* Architecture Boxes */
        .arch-layer {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin: 10px 0;
            text-align: center;
        }

        .arch-layer.presentation { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
        .arch-layer.application { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .arch-layer.business { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }
        .arch-layer.data { background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); color: var(--text); }
        .arch-layer.infrastructure { background: linear-gradient(135deg, #434343 0%, #000000 100%); }

        .arch-layer h4 {
            color: inherit;
            margin: 0 0 10px 0;
            font-size: 18px;
        }

        .arch-layer p {
            margin: 0;
            font-size: 13px;
            opacity: 0.9;
        }

        /* Component Grid */
        .component-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin: 25px 0;
        }

        .component-card {
            background: var(--bg-alt);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 20px;
            text-align: center;
        }

        .component-card .icon {
            font-size: 36px;
            margin-bottom: 10px;
        }

        .component-card h5 {
            font-size: 14px;
            color: var(--primary);
            margin-bottom: 8px;
        }

        .component-card p {
            font-size: 12px;
            color: var(--text-light);
            margin: 0;
            text-align: center;
        }

        /* Flow Diagram */
        .flow-container {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-wrap: wrap;
            gap: 15px;
            margin: 25px 0;
        }

        .flow-box {
            background: var(--accent);
            color: white;
            padding: 15px 25px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
        }

        .flow-arrow {
            font-size: 24px;
            color: var(--text-light);
        }

        /* Info Boxes */
        .info-box {
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }

        .info-box.note {
            background: #ebf8ff;
            border-left: 4px solid #3182ce;
        }

        .info-box.warning {
            background: #fffaf0;
            border-left: 4px solid #d69e2e;
        }

        .info-box.success {
            background: #f0fff4;
            border-left: 4px solid #38a169;
        }

        .info-box strong {
            display: block;
            margin-bottom: 8px;
        }

        /* Network Diagram SVG */
        .network-diagram {
            background: #1a202c;
            border-radius: 12px;
            padding: 20px;
            margin: 25px 0;
        }

        /* Footer */
        .doc-footer {
            text-align: center;
            padding: 30px;
            border-top: 2px solid var(--border);
            margin-top: 50px;
            color: var(--text-light);
            font-size: 12px;
        }

        /* Revision History */
        .revision-table {
            font-size: 13px;
        }

        .revision-table th {
            background: var(--secondary);
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Print Controls -->
        <div class="print-controls no-print">
            <button class="btn btn-primary" onclick="window.print()">Print / Save as PDF</button>
            <a href="index.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>

        <!-- Cover Page -->
        <div class="cover-page">
            <div class="cover-logo">🏗️</div>
            <h1 class="cover-title"><?= htmlspecialchars($app_name) ?></h1>
            <p class="cover-subtitle">System Architecture Review Document</p>
            <div class="cover-badge">Enterprise Architecture Documentation</div>
            <div class="cover-meta">
                <div><strong>Document ID:</strong> <?= $doc_id ?></div>
                <div><strong>Version:</strong> <?= $version ?></div>
                <div><strong>Date:</strong> <?= $date ?></div>
                <div><strong>Classification:</strong> Internal Use Only</div>
            </div>
        </div>

        <!-- Document Information -->
        <div class="doc-info avoid-break">
            <h3 style="margin-top: 0;">Document Control</h3>
            <div class="doc-info-grid">
                <div class="doc-info-item">
                    <label>Document Owner</label>
                    <p>Enterprise Architecture Team</p>
                </div>
                <div class="doc-info-item">
                    <label>Review Cycle</label>
                    <p>Quarterly</p>
                </div>
                <div class="doc-info-item">
                    <label>Next Review</label>
                    <p><?= date('F Y', strtotime('+3 months')) ?></p>
                </div>
                <div class="doc-info-item">
                    <label>Prepared By</label>
                    <p>Solution Architecture</p>
                </div>
                <div class="doc-info-item">
                    <label>Approved By</label>
                    <p>Chief Technology Officer</p>
                </div>
                <div class="doc-info-item">
                    <label>Distribution</label>
                    <p>Technical Stakeholders</p>
                </div>
            </div>
        </div>

        <!-- Revision History -->
        <div class="avoid-break" style="margin-bottom: 40px;">
            <h3>Revision History</h3>
            <table class="revision-table">
                <thead>
                    <tr>
                        <th>Version</th>
                        <th>Date</th>
                        <th>Author</th>
                        <th>Changes</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>2.0</td>
                        <td><?= date('Y-m-d') ?></td>
                        <td>Architecture Team</td>
                        <td>Added SCADA integration, Gas Storage, Pump monitoring modules</td>
                    </tr>
                    <tr>
                        <td>1.5</td>
                        <td>2025-12-01</td>
                        <td>Architecture Team</td>
                        <td>Added Cloud Hybrid architecture, Service Desk integration</td>
                    </tr>
                    <tr>
                        <td>1.0</td>
                        <td>2025-06-15</td>
                        <td>Architecture Team</td>
                        <td>Initial architecture document</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Table of Contents -->
        <div class="toc page-break">
            <h2>Table of Contents</h2>
            <ol class="toc-list">
                <li class="toc-section"><a href="#section1">1. Executive Summary</a></li>
                <li class="toc-section"><a href="#section2">2. System Overview</a></li>
                <li class="toc-subsection"><a href="#section2-1">2.1 Purpose and Scope</a></li>
                <li class="toc-subsection"><a href="#section2-2">2.2 Key Capabilities</a></li>
                <li class="toc-section"><a href="#section3">3. Architecture Principles</a></li>
                <li class="toc-section"><a href="#section4">4. Logical Architecture</a></li>
                <li class="toc-subsection"><a href="#section4-1">4.1 Layered Architecture</a></li>
                <li class="toc-subsection"><a href="#section4-2">4.2 Component Architecture</a></li>
                <li class="toc-section"><a href="#section5">5. Physical Architecture</a></li>
                <li class="toc-subsection"><a href="#section5-1">5.1 Network Topology</a></li>
                <li class="toc-subsection"><a href="#section5-2">5.2 Infrastructure Components</a></li>
                <li class="toc-section"><a href="#section6">6. SCADA Integration Architecture</a></li>
                <li class="toc-subsection"><a href="#section6-1">6.1 ICS/OT Network Integration</a></li>
                <li class="toc-subsection"><a href="#section6-2">6.2 Protocol Support</a></li>
                <li class="toc-section"><a href="#section7">7. Data Architecture</a></li>
                <li class="toc-section"><a href="#section8">8. Security Architecture</a></li>
                <li class="toc-section"><a href="#section9">9. Integration Architecture</a></li>
                <li class="toc-section"><a href="#section10">10. Deployment Models</a></li>
                <li class="toc-section"><a href="#section11">11. Scalability & Performance</a></li>
                <li class="toc-section"><a href="#section12">12. Disaster Recovery</a></li>
                <li class="toc-section"><a href="#section13">13. Technology Stack</a></li>
                <li class="toc-section"><a href="#section14">14. Appendices</a></li>
            </ol>
        </div>

        <!-- Section 1: Executive Summary -->
        <div id="section1" class="section page-break">
            <div class="section-header">
                <div class="section-num">Section 1</div>
                <h2>Executive Summary</h2>
            </div>
            <div class="section-content">
                <p>The <strong><?= htmlspecialchars($app_name) ?></strong> is an enterprise-grade unified operations platform designed to provide comprehensive visibility and control across both Information Technology (IT) and Operational Technology (OT) environments. This architecture review document provides a detailed technical overview of the system's design, components, and integration patterns.</p>

                <h3>Business Context</h3>
                <p>Modern industrial organizations face the challenge of managing converged IT/OT environments while maintaining security, operational efficiency, and regulatory compliance. The IOC platform addresses these challenges by providing:</p>
                <ul>
                    <li><strong>Unified Visibility</strong> - Single pane of glass for IT infrastructure and SCADA/ICS systems</li>
                    <li><strong>Proactive Security</strong> - Continuous vulnerability scanning and threat detection</li>
                    <li><strong>Operational Intelligence</strong> - Real-time monitoring of industrial processes</li>
                    <li><strong>Compliance Management</strong> - Automated reporting for regulatory requirements</li>
                </ul>

                <h3>Architecture Highlights</h3>
                <div class="component-grid">
                    <div class="component-card">
                        <div class="icon">🏛️</div>
                        <h5>Layered Architecture</h5>
                        <p>5-tier architecture ensuring separation of concerns</p>
                    </div>
                    <div class="component-card">
                        <div class="icon">🔒</div>
                        <h5>Defense in Depth</h5>
                        <p>Multi-layer security with IT/OT segmentation</p>
                    </div>
                    <div class="component-card">
                        <div class="icon">📊</div>
                        <h5>Real-time Processing</h5>
                        <p>Sub-second data processing for critical alerts</p>
                    </div>
                    <div class="component-card">
                        <div class="icon">🔄</div>
                        <h5>Hybrid Deployment</h5>
                        <p>On-premises, cloud, and hybrid options</p>
                    </div>
                    <div class="component-card">
                        <div class="icon">🏭</div>
                        <h5>SCADA Integration</h5>
                        <p>Native support for industrial protocols</p>
                    </div>
                    <div class="component-card">
                        <div class="icon">📈</div>
                        <h5>Scalable Design</h5>
                        <p>Horizontal scaling for enterprise workloads</p>
                    </div>
                </div>

                <div class="info-box success">
                    <strong>Key Architecture Decision</strong>
                    The system employs a modular microservices-inspired architecture within a monolithic deployment model, allowing for future decomposition while maintaining operational simplicity for current deployments.
                </div>
            </div>
        </div>

        <!-- Section 2: System Overview -->
        <div id="section2" class="section page-break">
            <div class="section-header">
                <div class="section-num">Section 2</div>
                <h2>System Overview</h2>
            </div>
            <div class="section-content">
                <h3 id="section2-1">2.1 Purpose and Scope</h3>
                <p>The IOC platform serves as the central nervous system for industrial operations, consolidating data from multiple sources to provide actionable intelligence. The system scope encompasses:</p>

                <table>
                    <thead>
                        <tr>
                            <th>Domain</th>
                            <th>Capabilities</th>
                            <th>Key Modules</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>IT Security</strong></td>
                            <td>Vulnerability scanning, threat detection, compliance</td>
                            <td>Network Scanner, DLP, Vulnerability Manager</td>
                        </tr>
                        <tr>
                            <td><strong>OT Monitoring</strong></td>
                            <td>SCADA visualization, alarm management, process control</td>
                            <td>SCADA Module, Tank Monitor, Pump Control</td>
                        </tr>
                        <tr>
                            <td><strong>Asset Management</strong></td>
                            <td>Device inventory, configuration tracking, lifecycle</td>
                            <td>Asset Registry, CMDB, Discovery</td>
                        </tr>
                        <tr>
                            <td><strong>Service Management</strong></td>
                            <td>Incident handling, change management, knowledge base</td>
                            <td>Service Desk, Change Control, KB</td>
                        </tr>
                        <tr>
                            <td><strong>Reporting</strong></td>
                            <td>Dashboards, scheduled reports, compliance exports</td>
                            <td>Report Builder, Custom Dashboards</td>
                        </tr>
                    </tbody>
                </table>

                <h3 id="section2-2">2.2 Key Capabilities</h3>

                <h4>Network Security Scanning</h4>
                <ul>
                    <li>Automated network discovery and asset mapping</li>
                    <li>Vulnerability assessment with CVE correlation</li>
                    <li>Port scanning and service enumeration</li>
                    <li>Compliance checking against security baselines</li>
                </ul>

                <h4>SCADA/ICS Integration</h4>
                <ul>
                    <li>Real-time data acquisition from PLCs, RTUs, and DCS</li>
                    <li>Tank level monitoring for oil and gas storage</li>
                    <li>Pump station control and flow management</li>
                    <li>Pipeline leak detection and alerting</li>
                    <li>Alarm management with priority-based escalation</li>
                </ul>

                <h4>Data Loss Prevention</h4>
                <ul>
                    <li>Content inspection and classification</li>
                    <li>Policy-based data protection</li>
                    <li>Endpoint monitoring and control</li>
                    <li>Incident investigation and forensics</li>
                </ul>

                <div class="diagram">
                    <div class="flow-container">
                        <div class="flow-box">Data Sources</div>
                        <div class="flow-arrow">→</div>
                        <div class="flow-box">Collection Layer</div>
                        <div class="flow-arrow">→</div>
                        <div class="flow-box">Processing Engine</div>
                        <div class="flow-arrow">→</div>
                        <div class="flow-box">Analytics</div>
                        <div class="flow-arrow">→</div>
                        <div class="flow-box">Visualization</div>
                    </div>
                    <p class="diagram-title">Figure 2.1: High-Level Data Flow</p>
                </div>
            </div>
        </div>

        <!-- Section 3: Architecture Principles -->
        <div id="section3" class="section page-break">
            <div class="section-header">
                <div class="section-num">Section 3</div>
                <h2>Architecture Principles</h2>
            </div>
            <div class="section-content">
                <p>The following principles guide all architectural decisions for the IOC platform:</p>

                <table>
                    <thead>
                        <tr>
                            <th>Principle</th>
                            <th>Description</th>
                            <th>Rationale</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Security First</strong></td>
                            <td>Security is designed into every layer, not bolted on</td>
                            <td>Critical infrastructure requires defense in depth</td>
                        </tr>
                        <tr>
                            <td><strong>Separation of Concerns</strong></td>
                            <td>Clear boundaries between IT and OT networks</td>
                            <td>Prevents lateral movement of threats</td>
                        </tr>
                        <tr>
                            <td><strong>High Availability</strong></td>
                            <td>No single point of failure for critical components</td>
                            <td>24/7 operations require continuous availability</td>
                        </tr>
                        <tr>
                            <td><strong>Scalability</strong></td>
                            <td>Horizontal scaling for compute, vertical for data</td>
                            <td>Accommodates growth without re-architecture</td>
                        </tr>
                        <tr>
                            <td><strong>Interoperability</strong></td>
                            <td>Standard protocols and open APIs</td>
                            <td>Integration with existing enterprise systems</td>
                        </tr>
                        <tr>
                            <td><strong>Operational Simplicity</strong></td>
                            <td>Minimize operational complexity</td>
                            <td>Reduces human error and training requirements</td>
                        </tr>
                        <tr>
                            <td><strong>Data Integrity</strong></td>
                            <td>Ensure accuracy and consistency of all data</td>
                            <td>Decisions depend on reliable information</td>
                        </tr>
                        <tr>
                            <td><strong>Compliance by Design</strong></td>
                            <td>Built-in support for regulatory requirements</td>
                            <td>Reduces audit burden and risk exposure</td>
                        </tr>
                    </tbody>
                </table>

                <div class="info-box note">
                    <strong>Architecture Governance</strong>
                    All architectural changes must be reviewed by the Architecture Review Board (ARB) to ensure alignment with these principles. Exceptions require documented risk acceptance from the CTO.
                </div>
            </div>
        </div>

        <!-- Section 4: Logical Architecture -->
        <div id="section4" class="section page-break">
            <div class="section-header">
                <div class="section-num">Section 4</div>
                <h2>Logical Architecture</h2>
            </div>
            <div class="section-content">
                <h3 id="section4-1">4.1 Layered Architecture</h3>
                <p>The IOC platform implements a 5-tier layered architecture that provides clear separation of concerns and enables independent scaling of each layer.</p>

                <div style="margin: 30px 0;">
                    <div class="arch-layer presentation">
                        <h4>Presentation Layer</h4>
                        <p>Web UI, Mobile Apps, API Gateway, HMI Displays</p>
                    </div>
                    <div class="arch-layer application">
                        <h4>Application Layer</h4>
                        <p>Business Logic, Workflow Engine, Rule Processing, Alerting</p>
                    </div>
                    <div class="arch-layer business">
                        <h4>Service Layer</h4>
                        <p>Authentication, Authorization, Session Management, Audit Logging</p>
                    </div>
                    <div class="arch-layer data">
                        <h4>Data Layer</h4>
                        <p>Relational DB, Time-Series DB, Document Store, Cache</p>
                    </div>
                    <div class="arch-layer infrastructure">
                        <h4>Infrastructure Layer</h4>
                        <p>Servers, Network, Storage, Virtualization, Containers</p>
                    </div>
                </div>
                <p class="diagram-title">Figure 4.1: Logical Architecture Layers</p>

                <h3 id="section4-2">4.2 Component Architecture</h3>
                <p>The system is organized into functional modules, each with defined responsibilities and interfaces:</p>

                <div class="component-grid">
                    <div class="component-card">
                        <div class="icon">🔍</div>
                        <h5>Network Scanner</h5>
                        <p>Discovery, port scanning, vulnerability detection</p>
                    </div>
                    <div class="component-card">
                        <div class="icon">🏭</div>
                        <h5>SCADA Engine</h5>
                        <p>Industrial protocol handling, data normalization</p>
                    </div>
                    <div class="component-card">
                        <div class="icon">🛢️</div>
                        <h5>Tank Monitor</h5>
                        <p>Level tracking, flow calculation, leak detection</p>
                    </div>
                    <div class="component-card">
                        <div class="icon">⚙️</div>
                        <h5>Pump Controller</h5>
                        <p>Pump status, flow rates, efficiency metrics</p>
                    </div>
                    <div class="component-card">
                        <div class="icon">🚨</div>
                        <h5>Alarm Manager</h5>
                        <p>Alert processing, escalation, notification</p>
                    </div>
                    <div class="component-card">
                        <div class="icon">📊</div>
                        <h5>Report Engine</h5>
                        <p>Report generation, scheduling, distribution</p>
                    </div>
                    <div class="component-card">
                        <div class="icon">🔐</div>
                        <h5>DLP Engine</h5>
                        <p>Content inspection, policy enforcement</p>
                    </div>
                    <div class="component-card">
                        <div class="icon">🎫</div>
                        <h5>Service Desk</h5>
                        <p>Incident management, change control</p>
                    </div>
                    <div class="component-card">
                        <div class="icon">📚</div>
                        <h5>Training Center</h5>
                        <p>User training, certification tracking</p>
                    </div>
                </div>

                <h4>Component Interaction Pattern</h4>
                <p>Components communicate through a combination of:</p>
                <ul>
                    <li><strong>Synchronous REST APIs</strong> - For request/response operations</li>
                    <li><strong>Asynchronous Message Queue</strong> - For event-driven processing</li>
                    <li><strong>Shared Database</strong> - For transactional data consistency</li>
                    <li><strong>WebSocket</strong> - For real-time UI updates</li>
                </ul>
            </div>
        </div>

        <!-- Section 5: Physical Architecture -->
        <div id="section5" class="section page-break">
            <div class="section-header">
                <div class="section-num">Section 5</div>
                <h2>Physical Architecture</h2>
            </div>
            <div class="section-content">
                <h3 id="section5-1">5.1 Network Topology</h3>
                <p>The physical network architecture implements defense-in-depth with clear zone separation between enterprise IT, DMZ, and operational technology networks.</p>

                <div class="network-diagram">
                    <svg width="100%" height="450" viewBox="0 0 800 450">
                        <!-- Enterprise Zone -->
                        <rect x="50" y="20" width="700" height="80" rx="10" fill="#2d3748" stroke="#4a5568" stroke-width="2"/>
                        <text x="400" y="45" text-anchor="middle" fill="#a0aec0" font-size="12">ENTERPRISE ZONE (Level 5)</text>
                        <rect x="100" y="55" width="120" height="35" rx="5" fill="#4299e1"/>
                        <text x="160" y="77" text-anchor="middle" fill="white" font-size="11">Corporate Apps</text>
                        <rect x="250" y="55" width="120" height="35" rx="5" fill="#4299e1"/>
                        <text x="310" y="77" text-anchor="middle" fill="white" font-size="11">Email/Office</text>
                        <rect x="400" y="55" width="120" height="35" rx="5" fill="#4299e1"/>
                        <text x="460" y="77" text-anchor="middle" fill="white" font-size="11">ERP System</text>
                        <rect x="550" y="55" width="120" height="35" rx="5" fill="#4299e1"/>
                        <text x="610" y="77" text-anchor="middle" fill="white" font-size="11">Business Intel</text>

                        <!-- Firewall 1 -->
                        <rect x="350" y="110" width="100" height="30" rx="5" fill="#e53e3e"/>
                        <text x="400" y="130" text-anchor="middle" fill="white" font-size="11">Firewall</text>

                        <!-- DMZ Zone -->
                        <rect x="50" y="150" width="700" height="80" rx="10" fill="#2d3748" stroke="#ed8936" stroke-width="2"/>
                        <text x="400" y="175" text-anchor="middle" fill="#a0aec0" font-size="12">DMZ / IT-OT BOUNDARY (Level 4)</text>
                        <rect x="150" y="185" width="140" height="35" rx="5" fill="#ed8936"/>
                        <text x="220" y="207" text-anchor="middle" fill="white" font-size="11">IOC Web Server</text>
                        <rect x="330" y="185" width="140" height="35" rx="5" fill="#ed8936"/>
                        <text x="400" y="207" text-anchor="middle" fill="white" font-size="11">Historian Server</text>
                        <rect x="510" y="185" width="140" height="35" rx="5" fill="#ed8936"/>
                        <text x="580" y="207" text-anchor="middle" fill="white" font-size="11">API Gateway</text>

                        <!-- Firewall 2 -->
                        <rect x="350" y="240" width="100" height="30" rx="5" fill="#e53e3e"/>
                        <text x="400" y="260" text-anchor="middle" fill="white" font-size="11">ICS Firewall</text>

                        <!-- Control Zone -->
                        <rect x="50" y="280" width="700" height="80" rx="10" fill="#2d3748" stroke="#48bb78" stroke-width="2"/>
                        <text x="400" y="305" text-anchor="middle" fill="#a0aec0" font-size="12">CONTROL ZONE (Level 3)</text>
                        <rect x="100" y="315" width="120" height="35" rx="5" fill="#48bb78"/>
                        <text x="160" y="337" text-anchor="middle" fill="white" font-size="11">SCADA Server</text>
                        <rect x="250" y="315" width="120" height="35" rx="5" fill="#48bb78"/>
                        <text x="310" y="337" text-anchor="middle" fill="white" font-size="11">HMI Stations</text>
                        <rect x="400" y="315" width="120" height="35" rx="5" fill="#48bb78"/>
                        <text x="460" y="337" text-anchor="middle" fill="white" font-size="11">Engineering WS</text>
                        <rect x="550" y="315" width="120" height="35" rx="5" fill="#48bb78"/>
                        <text x="610" y="337" text-anchor="middle" fill="white" font-size="11">Alarm Server</text>

                        <!-- Field Zone -->
                        <rect x="50" y="370" width="700" height="70" rx="10" fill="#2d3748" stroke="#9f7aea" stroke-width="2"/>
                        <text x="400" y="395" text-anchor="middle" fill="#a0aec0" font-size="12">FIELD ZONE (Level 1-2)</text>
                        <rect x="100" y="405" width="80" height="25" rx="5" fill="#9f7aea"/>
                        <text x="140" y="422" text-anchor="middle" fill="white" font-size="10">PLC-001</text>
                        <rect x="200" y="405" width="80" height="25" rx="5" fill="#9f7aea"/>
                        <text x="240" y="422" text-anchor="middle" fill="white" font-size="10">PLC-002</text>
                        <rect x="300" y="405" width="80" height="25" rx="5" fill="#9f7aea"/>
                        <text x="340" y="422" text-anchor="middle" fill="white" font-size="10">RTU-001</text>
                        <rect x="400" y="405" width="80" height="25" rx="5" fill="#9f7aea"/>
                        <text x="440" y="422" text-anchor="middle" fill="white" font-size="10">RTU-002</text>
                        <rect x="500" y="405" width="80" height="25" rx="5" fill="#9f7aea"/>
                        <text x="540" y="422" text-anchor="middle" fill="white" font-size="10">DCS</text>
                        <rect x="600" y="405" width="80" height="25" rx="5" fill="#9f7aea"/>
                        <text x="640" y="422" text-anchor="middle" fill="white" font-size="10">Sensors</text>
                    </svg>
                    <p class="diagram-title" style="color: #a0aec0;">Figure 5.1: Network Zone Architecture (Purdue Model)</p>
                </div>

                <h3 id="section5-2">5.2 Infrastructure Components</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Component</th>
                            <th>Specification</th>
                            <th>Quantity</th>
                            <th>Purpose</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Application Server</td>
                            <td>8 vCPU, 32GB RAM, 500GB SSD</td>
                            <td>2 (HA pair)</td>
                            <td>IOC Web Application</td>
                        </tr>
                        <tr>
                            <td>Database Server</td>
                            <td>16 vCPU, 64GB RAM, 2TB SSD</td>
                            <td>2 (Primary/Replica)</td>
                            <td>MariaDB/MySQL</td>
                        </tr>
                        <tr>
                            <td>SCADA Gateway</td>
                            <td>4 vCPU, 16GB RAM, 256GB SSD</td>
                            <td>2 (HA pair)</td>
                            <td>Protocol translation</td>
                        </tr>
                        <tr>
                            <td>Historian Server</td>
                            <td>8 vCPU, 32GB RAM, 4TB HDD</td>
                            <td>1</td>
                            <td>Time-series data storage</td>
                        </tr>
                        <tr>
                            <td>Load Balancer</td>
                            <td>Hardware or Virtual</td>
                            <td>2 (HA pair)</td>
                            <td>Traffic distribution</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Section 6: SCADA Integration -->
        <div id="section6" class="section page-break">
            <div class="section-header">
                <div class="section-num">Section 6</div>
                <h2>SCADA Integration Architecture</h2>
            </div>
            <div class="section-content">
                <h3 id="section6-1">6.1 ICS/OT Network Integration</h3>
                <p>The IOC platform integrates with industrial control systems through a secure, unidirectional data flow architecture that maintains separation between IT and OT networks.</p>

                <div class="diagram">
                    <svg width="100%" height="300" viewBox="0 0 800 300">
                        <!-- Field Devices -->
                        <rect x="50" y="220" width="100" height="50" rx="5" fill="#9f7aea"/>
                        <text x="100" y="250" text-anchor="middle" fill="white" font-size="11">Tank Sensors</text>
                        <rect x="170" y="220" width="100" height="50" rx="5" fill="#9f7aea"/>
                        <text x="220" y="250" text-anchor="middle" fill="white" font-size="11">Pump PLCs</text>
                        <rect x="290" y="220" width="100" height="50" rx="5" fill="#9f7aea"/>
                        <text x="340" y="250" text-anchor="middle" fill="white" font-size="11">Flow Meters</text>
                        <rect x="410" y="220" width="100" height="50" rx="5" fill="#9f7aea"/>
                        <text x="460" y="250" text-anchor="middle" fill="white" font-size="11">RTUs</text>
                        <rect x="530" y="220" width="100" height="50" rx="5" fill="#9f7aea"/>
                        <text x="580" y="250" text-anchor="middle" fill="white" font-size="11">Gas Detectors</text>

                        <!-- Arrows up -->
                        <line x1="100" y1="220" x2="100" y2="180" stroke="#48bb78" stroke-width="2" marker-end="url(#arrowhead)"/>
                        <line x1="220" y1="220" x2="220" y2="180" stroke="#48bb78" stroke-width="2"/>
                        <line x1="340" y1="220" x2="340" y2="180" stroke="#48bb78" stroke-width="2"/>
                        <line x1="460" y1="220" x2="460" y2="180" stroke="#48bb78" stroke-width="2"/>
                        <line x1="580" y1="220" x2="580" y2="180" stroke="#48bb78" stroke-width="2"/>

                        <!-- SCADA Gateway -->
                        <rect x="200" y="130" width="400" height="50" rx="5" fill="#48bb78"/>
                        <text x="400" y="160" text-anchor="middle" fill="white" font-size="13">SCADA Data Gateway (Protocol Translation)</text>

                        <!-- Arrow up -->
                        <line x1="400" y1="130" x2="400" y2="90" stroke="#ed8936" stroke-width="3"/>

                        <!-- Data Diode -->
                        <rect x="350" y="60" width="100" height="30" rx="5" fill="#e53e3e"/>
                        <text x="400" y="80" text-anchor="middle" fill="white" font-size="11">Data Diode</text>

                        <!-- Arrow up -->
                        <line x1="400" y1="60" x2="400" y2="40" stroke="#4299e1" stroke-width="3"/>

                        <!-- IOC Platform -->
                        <rect x="200" y="10" width="400" height="30" rx="5" fill="#4299e1"/>
                        <text x="400" y="30" text-anchor="middle" fill="white" font-size="13">IOC Platform (IT Zone)</text>
                    </svg>
                    <p class="diagram-title">Figure 6.1: SCADA Data Flow Architecture</p>
                </div>

                <h3 id="section6-2">6.2 Protocol Support</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Protocol</th>
                            <th>Version</th>
                            <th>Use Case</th>
                            <th>Security</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Modbus TCP</td>
                            <td>Standard</td>
                            <td>PLC communication</td>
                            <td>Network segmentation required</td>
                        </tr>
                        <tr>
                            <td>Modbus RTU</td>
                            <td>Standard</td>
                            <td>Serial device polling</td>
                            <td>Physical access control</td>
                        </tr>
                        <tr>
                            <td>DNP3</td>
                            <td>IEEE 1815</td>
                            <td>RTU/SCADA communication</td>
                            <td>Secure Authentication v5</td>
                        </tr>
                        <tr>
                            <td>OPC UA</td>
                            <td>1.04+</td>
                            <td>Modern SCADA systems</td>
                            <td>Built-in TLS encryption</td>
                        </tr>
                        <tr>
                            <td>IEC 61850</td>
                            <td>Edition 2</td>
                            <td>Substation automation</td>
                            <td>MMS security profiles</td>
                        </tr>
                        <tr>
                            <td>MQTT</td>
                            <td>3.1.1 / 5.0</td>
                            <td>IoT sensors</td>
                            <td>TLS + authentication</td>
                        </tr>
                    </tbody>
                </table>

                <h4>Supported Industrial Equipment</h4>
                <div class="component-grid">
                    <div class="component-card">
                        <div class="icon">🛢️</div>
                        <h5>Tank Level Systems</h5>
                        <p>Radar, Servo, Ultrasonic gauges from Emerson, Endress+Hauser, Honeywell</p>
                    </div>
                    <div class="component-card">
                        <div class="icon">⚙️</div>
                        <h5>Pump Controllers</h5>
                        <p>VFDs and motor controllers from ABB, Siemens, Allen-Bradley</p>
                    </div>
                    <div class="component-card">
                        <div class="icon">📟</div>
                        <h5>PLCs</h5>
                        <p>Siemens S7, Allen-Bradley ControlLogix, Schneider Modicon</p>
                    </div>
                    <div class="component-card">
                        <div class="icon">📡</div>
                        <h5>RTUs</h5>
                        <p>ABB RTU500, Emerson ROC, Honeywell RTU2020</p>
                    </div>
                    <div class="component-card">
                        <div class="icon">🌡️</div>
                        <h5>Analyzers</h5>
                        <p>Gas chromatographs, moisture analyzers, flow computers</p>
                    </div>
                    <div class="component-card">
                        <div class="icon">🔥</div>
                        <h5>Safety Systems</h5>
                        <p>ESD, F&G detection, BMS integration</p>
                    </div>
                </div>

                <div class="info-box warning">
                    <strong>Security Consideration</strong>
                    All SCADA protocol traffic must traverse a DMZ with application-layer inspection. Direct connectivity between IT networks and field devices is prohibited.
                </div>
            </div>
        </div>

        <!-- Section 7: Data Architecture -->
        <div id="section7" class="section page-break">
            <div class="section-header">
                <div class="section-num">Section 7</div>
                <h2>Data Architecture</h2>
            </div>
            <div class="section-content">
                <h3>7.1 Data Stores</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Store Type</th>
                            <th>Technology</th>
                            <th>Data Types</th>
                            <th>Retention</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Relational Database</td>
                            <td>MariaDB 10.6+</td>
                            <td>Configuration, users, assets, incidents</td>
                            <td>Indefinite</td>
                        </tr>
                        <tr>
                            <td>Time-Series Database</td>
                            <td>InfluxDB / TimescaleDB</td>
                            <td>SCADA tags, metrics, performance data</td>
                            <td>2 years raw, 7 years aggregated</td>
                        </tr>
                        <tr>
                            <td>Document Store</td>
                            <td>Elasticsearch</td>
                            <td>Logs, events, full-text search</td>
                            <td>90 days hot, 1 year cold</td>
                        </tr>
                        <tr>
                            <td>Cache Layer</td>
                            <td>Redis</td>
                            <td>Sessions, real-time data, queues</td>
                            <td>Ephemeral</td>
                        </tr>
                        <tr>
                            <td>File Storage</td>
                            <td>S3-compatible / NFS</td>
                            <td>Reports, attachments, backups</td>
                            <td>Per policy</td>
                        </tr>
                    </tbody>
                </table>

                <h3>7.2 Data Model Overview</h3>
                <div class="component-grid">
                    <div class="component-card">
                        <div class="icon">🏢</div>
                        <h5>Assets</h5>
                        <p>Devices, tanks, pumps, sensors with hierarchical relationships</p>
                    </div>
                    <div class="component-card">
                        <div class="icon">📊</div>
                        <h5>Measurements</h5>
                        <p>Time-stamped values from SCADA tags and sensors</p>
                    </div>
                    <div class="component-card">
                        <div class="icon">🚨</div>
                        <h5>Alarms</h5>
                        <p>Active and historical alarms with acknowledgment tracking</p>
                    </div>
                    <div class="component-card">
                        <div class="icon">👤</div>
                        <h5>Users</h5>
                        <p>Authentication, roles, permissions, audit trail</p>
                    </div>
                    <div class="component-card">
                        <div class="icon">🔍</div>
                        <h5>Scans</h5>
                        <p>Network scans, vulnerabilities, compliance findings</p>
                    </div>
                    <div class="component-card">
                        <div class="icon">🎫</div>
                        <h5>Tickets</h5>
                        <p>Incidents, changes, problems, service requests</p>
                    </div>
                </div>

                <h3>7.3 Data Flow</h3>
                <div class="flow-container">
                    <div class="flow-box" style="background: #9f7aea;">Field Sensors</div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-box" style="background: #48bb78;">RTU/PLC</div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-box" style="background: #ed8936;">Gateway</div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-box" style="background: #4299e1;">Historian</div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-box" style="background: #667eea;">IOC DB</div>
                </div>

                <div class="info-box note">
                    <strong>Data Sovereignty</strong>
                    All operational data remains on-premises by default. Cloud deployments use customer-controlled encryption keys and data residency controls.
                </div>
            </div>
        </div>

        <!-- Section 8: Security Architecture -->
        <div id="section8" class="section page-break">
            <div class="section-header">
                <div class="section-num">Section 8</div>
                <h2>Security Architecture</h2>
            </div>
            <div class="section-content">
                <h3>8.1 Security Zones</h3>
                <p>The system implements the Purdue Model for Industrial Control System security with clearly defined zones:</p>

                <table>
                    <thead>
                        <tr>
                            <th>Level</th>
                            <th>Zone Name</th>
                            <th>Components</th>
                            <th>Security Controls</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>5</td>
                            <td>Enterprise</td>
                            <td>Corporate IT, business systems</td>
                            <td>Standard IT security policies</td>
                        </tr>
                        <tr>
                            <td>4</td>
                            <td>Site Business</td>
                            <td>IOC servers, historians</td>
                            <td>Enhanced monitoring, MFA required</td>
                        </tr>
                        <tr>
                            <td>3.5</td>
                            <td>DMZ</td>
                            <td>Data diodes, gateways</td>
                            <td>Unidirectional where possible</td>
                        </tr>
                        <tr>
                            <td>3</td>
                            <td>Operations</td>
                            <td>SCADA servers, HMI</td>
                            <td>Application whitelisting, jump servers</td>
                        </tr>
                        <tr>
                            <td>2</td>
                            <td>Control</td>
                            <td>PLCs, DCS controllers</td>
                            <td>Network isolation, change control</td>
                        </tr>
                        <tr>
                            <td>1</td>
                            <td>Field</td>
                            <td>RTUs, sensors, actuators</td>
                            <td>Physical security, tamper detection</td>
                        </tr>
                        <tr>
                            <td>0</td>
                            <td>Process</td>
                            <td>Physical process equipment</td>
                            <td>Safety instrumented systems</td>
                        </tr>
                    </tbody>
                </table>

                <h3>8.2 Authentication & Authorization</h3>
                <ul>
                    <li><strong>Multi-Factor Authentication</strong> - Required for all administrative access</li>
                    <li><strong>Role-Based Access Control (RBAC)</strong> - Granular permissions by function</li>
                    <li><strong>Active Directory Integration</strong> - SSO with enterprise identity</li>
                    <li><strong>Session Management</strong> - Configurable timeouts, concurrent session limits</li>
                    <li><strong>Audit Logging</strong> - All actions logged with user attribution</li>
                </ul>

                <h3>8.3 Encryption Standards</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Use Case</th>
                            <th>Algorithm</th>
                            <th>Key Size</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Data at Rest</td>
                            <td>AES-256-GCM</td>
                            <td>256-bit</td>
                        </tr>
                        <tr>
                            <td>Data in Transit</td>
                            <td>TLS 1.3</td>
                            <td>256-bit (ECDHE)</td>
                        </tr>
                        <tr>
                            <td>Password Storage</td>
                            <td>Argon2id</td>
                            <td>Per OWASP guidelines</td>
                        </tr>
                        <tr>
                            <td>API Authentication</td>
                            <td>JWT (RS256)</td>
                            <td>2048-bit RSA</td>
                        </tr>
                    </tbody>
                </table>

                <div class="info-box warning">
                    <strong>Compliance Frameworks</strong>
                    The security architecture is designed to support compliance with: IEC 62443, NIST CSF, NERC CIP, ISO 27001, and industry-specific regulations.
                </div>
            </div>
        </div>

        <!-- Section 9: Integration Architecture -->
        <div id="section9" class="section page-break">
            <div class="section-header">
                <div class="section-num">Section 9</div>
                <h2>Integration Architecture</h2>
            </div>
            <div class="section-content">
                <h3>9.1 Integration Patterns</h3>
                <div class="component-grid">
                    <div class="component-card">
                        <div class="icon">🔌</div>
                        <h5>REST API</h5>
                        <p>Primary integration method for external systems</p>
                    </div>
                    <div class="component-card">
                        <div class="icon">📨</div>
                        <h5>Webhooks</h5>
                        <p>Real-time event notifications to subscribers</p>
                    </div>
                    <div class="component-card">
                        <div class="icon">📤</div>
                        <h5>SNMP</h5>
                        <p>Network device discovery and monitoring</p>
                    </div>
                    <div class="component-card">
                        <div class="icon">📧</div>
                        <h5>Email/SMTP</h5>
                        <p>Alert notifications and report delivery</p>
                    </div>
                    <div class="component-card">
                        <div class="icon">📱</div>
                        <h5>SMS Gateway</h5>
                        <p>Critical alert notifications</p>
                    </div>
                    <div class="component-card">
                        <div class="icon">🔗</div>
                        <h5>Syslog</h5>
                        <p>Log forwarding to SIEM platforms</p>
                    </div>
                </div>

                <h3>9.2 External System Integrations</h3>
                <table>
                    <thead>
                        <tr>
                            <th>System Type</th>
                            <th>Integration Method</th>
                            <th>Data Direction</th>
                            <th>Use Case</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>SIEM (Splunk, QRadar)</td>
                            <td>Syslog / REST API</td>
                            <td>Outbound</td>
                            <td>Security event correlation</td>
                        </tr>
                        <tr>
                            <td>ITSM (ServiceNow)</td>
                            <td>REST API</td>
                            <td>Bidirectional</td>
                            <td>Ticket synchronization</td>
                        </tr>
                        <tr>
                            <td>CMDB</td>
                            <td>REST API</td>
                            <td>Bidirectional</td>
                            <td>Asset synchronization</td>
                        </tr>
                        <tr>
                            <td>Active Directory</td>
                            <td>LDAP/LDAPS</td>
                            <td>Inbound</td>
                            <td>User authentication</td>
                        </tr>
                        <tr>
                            <td>PI Historian</td>
                            <td>OPC UA / PI Web API</td>
                            <td>Inbound</td>
                            <td>Process data retrieval</td>
                        </tr>
                        <tr>
                            <td>ERP (SAP)</td>
                            <td>REST / RFC</td>
                            <td>Bidirectional</td>
                            <td>Asset and maintenance data</td>
                        </tr>
                    </tbody>
                </table>

                <h3>9.3 API Specifications</h3>
                <ul>
                    <li><strong>API Style:</strong> RESTful with OpenAPI 3.0 specification</li>
                    <li><strong>Authentication:</strong> OAuth 2.0 / API Keys</li>
                    <li><strong>Rate Limiting:</strong> 1000 requests/minute per client</li>
                    <li><strong>Versioning:</strong> URI-based (e.g., /api/v1/, /api/v2/)</li>
                    <li><strong>Format:</strong> JSON (primary), XML (legacy support)</li>
                </ul>
            </div>
        </div>

        <!-- Section 10: Deployment Models -->
        <div id="section10" class="section page-break">
            <div class="section-header">
                <div class="section-num">Section 10</div>
                <h2>Deployment Models</h2>
            </div>
            <div class="section-content">
                <h3>10.1 Deployment Options</h3>

                <div class="component-grid">
                    <div class="component-card" style="border: 2px solid #48bb78;">
                        <div class="icon">🏢</div>
                        <h5>On-Premises</h5>
                        <p>Full deployment within customer data center. Maximum control and data sovereignty.</p>
                    </div>
                    <div class="component-card" style="border: 2px solid #4299e1;">
                        <div class="icon">☁️</div>
                        <h5>Cloud (IaaS)</h5>
                        <p>Deployed on customer's cloud infrastructure (AWS, Azure, GCP).</p>
                    </div>
                    <div class="component-card" style="border: 2px solid #ed8936;">
                        <div class="icon">🔄</div>
                        <h5>Hybrid</h5>
                        <p>SCADA on-premises, analytics in cloud. Best of both worlds.</p>
                    </div>
                </div>

                <h3>10.2 Deployment Architecture Comparison</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Aspect</th>
                            <th>On-Premises</th>
                            <th>Cloud</th>
                            <th>Hybrid</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Data Location</td>
                            <td>Customer DC</td>
                            <td>Cloud Region</td>
                            <td>Split</td>
                        </tr>
                        <tr>
                            <td>SCADA Latency</td>
                            <td>Lowest</td>
                            <td>Higher</td>
                            <td>Lowest</td>
                        </tr>
                        <tr>
                            <td>Scalability</td>
                            <td>Hardware limited</td>
                            <td>Elastic</td>
                            <td>Elastic (analytics)</td>
                        </tr>
                        <tr>
                            <td>Maintenance</td>
                            <td>Customer</td>
                            <td>Shared</td>
                            <td>Shared</td>
                        </tr>
                        <tr>
                            <td>Connectivity Required</td>
                            <td>None (air-gapped possible)</td>
                            <td>Always-on</td>
                            <td>Intermittent OK</td>
                        </tr>
                        <tr>
                            <td>Best For</td>
                            <td>High-security, regulated</td>
                            <td>Distributed sites</td>
                            <td>Most scenarios</td>
                        </tr>
                    </tbody>
                </table>

                <h3>10.3 Containerization</h3>
                <p>The IOC platform supports containerized deployment using Docker and Kubernetes:</p>
                <ul>
                    <li><strong>Docker Compose</strong> - Single-node development and POC deployments</li>
                    <li><strong>Kubernetes</strong> - Production-grade orchestration with auto-scaling</li>
                    <li><strong>Helm Charts</strong> - Standardized deployment packages</li>
                    <li><strong>Container Registry</strong> - Private registry for image management</li>
                </ul>

                <div class="info-box success">
                    <strong>Recommended Deployment</strong>
                    For most industrial environments, we recommend the Hybrid model: critical SCADA functions remain on-premises for reliability and security, while analytics, reporting, and non-critical functions leverage cloud scalability.
                </div>
            </div>
        </div>

        <!-- Section 11: Scalability & Performance -->
        <div id="section11" class="section page-break">
            <div class="section-header">
                <div class="section-num">Section 11</div>
                <h2>Scalability & Performance</h2>
            </div>
            <div class="section-content">
                <h3>11.1 Performance Targets</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Metric</th>
                            <th>Target</th>
                            <th>Measurement Method</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Web UI Response Time</td>
                            <td>&lt; 2 seconds (95th percentile)</td>
                            <td>Real User Monitoring</td>
                        </tr>
                        <tr>
                            <td>API Response Time</td>
                            <td>&lt; 500ms (95th percentile)</td>
                            <td>API Gateway metrics</td>
                        </tr>
                        <tr>
                            <td>SCADA Tag Update Latency</td>
                            <td>&lt; 1 second</td>
                            <td>End-to-end timestamp comparison</td>
                        </tr>
                        <tr>
                            <td>Alarm Notification Latency</td>
                            <td>&lt; 5 seconds</td>
                            <td>Alarm to notification timestamp</td>
                        </tr>
                        <tr>
                            <td>Report Generation</td>
                            <td>&lt; 30 seconds (standard reports)</td>
                            <td>Job completion time</td>
                        </tr>
                        <tr>
                            <td>System Availability</td>
                            <td>99.9% (excluding planned maintenance)</td>
                            <td>Uptime monitoring</td>
                        </tr>
                    </tbody>
                </table>

                <h3>11.2 Scaling Dimensions</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Dimension</th>
                            <th>Small</th>
                            <th>Medium</th>
                            <th>Large</th>
                            <th>Enterprise</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Concurrent Users</td>
                            <td>50</td>
                            <td>200</td>
                            <td>1,000</td>
                            <td>5,000+</td>
                        </tr>
                        <tr>
                            <td>Monitored Assets</td>
                            <td>500</td>
                            <td>5,000</td>
                            <td>25,000</td>
                            <td>100,000+</td>
                        </tr>
                        <tr>
                            <td>SCADA Tags</td>
                            <td>10,000</td>
                            <td>50,000</td>
                            <td>250,000</td>
                            <td>1,000,000+</td>
                        </tr>
                        <tr>
                            <td>Events/Second</td>
                            <td>100</td>
                            <td>1,000</td>
                            <td>10,000</td>
                            <td>50,000+</td>
                        </tr>
                        <tr>
                            <td>Data Retention</td>
                            <td>1 year</td>
                            <td>3 years</td>
                            <td>5 years</td>
                            <td>7+ years</td>
                        </tr>
                    </tbody>
                </table>

                <h3>11.3 Scaling Strategies</h3>
                <ul>
                    <li><strong>Horizontal Scaling</strong> - Add application server instances behind load balancer</li>
                    <li><strong>Database Replication</strong> - Read replicas for reporting workloads</li>
                    <li><strong>Caching</strong> - Redis cluster for session and data caching</li>
                    <li><strong>CDN</strong> - Static asset delivery for distributed users</li>
                    <li><strong>Sharding</strong> - Data partitioning for multi-site deployments</li>
                </ul>
            </div>
        </div>

        <!-- Section 12: Disaster Recovery -->
        <div id="section12" class="section page-break">
            <div class="section-header">
                <div class="section-num">Section 12</div>
                <h2>Disaster Recovery</h2>
            </div>
            <div class="section-content">
                <h3>12.1 Recovery Objectives</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Component</th>
                            <th>RTO (Recovery Time)</th>
                            <th>RPO (Data Loss)</th>
                            <th>Recovery Strategy</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>SCADA Visualization</td>
                            <td>15 minutes</td>
                            <td>0 (real-time)</td>
                            <td>Active-Active HA</td>
                        </tr>
                        <tr>
                            <td>Alarm Management</td>
                            <td>5 minutes</td>
                            <td>0</td>
                            <td>Active-Active HA</td>
                        </tr>
                        <tr>
                            <td>Web Application</td>
                            <td>30 minutes</td>
                            <td>5 minutes</td>
                            <td>Active-Passive HA</td>
                        </tr>
                        <tr>
                            <td>Database</td>
                            <td>15 minutes</td>
                            <td>1 minute</td>
                            <td>Synchronous replication</td>
                        </tr>
                        <tr>
                            <td>Historical Data</td>
                            <td>4 hours</td>
                            <td>1 hour</td>
                            <td>Backup restore</td>
                        </tr>
                        <tr>
                            <td>Full System (DR site)</td>
                            <td>4 hours</td>
                            <td>15 minutes</td>
                            <td>Warm standby</td>
                        </tr>
                    </tbody>
                </table>

                <h3>12.2 Backup Strategy</h3>
                <ul>
                    <li><strong>Database:</strong> Continuous replication + daily full backup + hourly incrementals</li>
                    <li><strong>Configuration:</strong> Version controlled in Git, backed up daily</li>
                    <li><strong>Application:</strong> Container images in registry, infrastructure as code</li>
                    <li><strong>Documents:</strong> Daily backup to offsite storage</li>
                    <li><strong>Retention:</strong> Daily backups for 30 days, weekly for 1 year, monthly for 7 years</li>
                </ul>

                <h3>12.3 Failover Architecture</h3>
                <div class="diagram">
                    <div style="display: flex; justify-content: center; gap: 50px; align-items: center;">
                        <div style="text-align: center;">
                            <div style="background: #48bb78; color: white; padding: 20px 30px; border-radius: 10px; margin-bottom: 10px;">
                                <strong>Primary Site</strong><br>
                                <small>Active</small>
                            </div>
                            <div style="font-size: 12px; color: var(--text-light);">Production workloads</div>
                        </div>
                        <div style="font-size: 36px; color: var(--text-light);">⟷</div>
                        <div style="text-align: center;">
                            <div style="background: #ed8936; color: white; padding: 20px 30px; border-radius: 10px; margin-bottom: 10px;">
                                <strong>DR Site</strong><br>
                                <small>Standby</small>
                            </div>
                            <div style="font-size: 12px; color: var(--text-light);">Synchronized replica</div>
                        </div>
                    </div>
                    <p class="diagram-title">Figure 12.1: Primary/DR Site Configuration</p>
                </div>

                <div class="info-box warning">
                    <strong>DR Testing</strong>
                    Disaster recovery procedures must be tested quarterly. Full failover tests should be conducted annually with documented results and lessons learned.
                </div>
            </div>
        </div>

        <!-- Section 13: Technology Stack -->
        <div id="section13" class="section page-break">
            <div class="section-header">
                <div class="section-num">Section 13</div>
                <h2>Technology Stack</h2>
            </div>
            <div class="section-content">
                <h3>13.1 Core Technologies</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Layer</th>
                            <th>Technology</th>
                            <th>Version</th>
                            <th>Purpose</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Frontend</td>
                            <td>HTML5, CSS3, JavaScript</td>
                            <td>ES6+</td>
                            <td>User interface</td>
                        </tr>
                        <tr>
                            <td>Backend</td>
                            <td>PHP</td>
                            <td>8.1+</td>
                            <td>Application logic</td>
                        </tr>
                        <tr>
                            <td>Database</td>
                            <td>MariaDB</td>
                            <td>10.6+</td>
                            <td>Relational data storage</td>
                        </tr>
                        <tr>
                            <td>Web Server</td>
                            <td>Apache / Nginx</td>
                            <td>2.4+ / 1.20+</td>
                            <td>HTTP serving</td>
                        </tr>
                        <tr>
                            <td>Cache</td>
                            <td>Redis</td>
                            <td>7.0+</td>
                            <td>Session and data caching</td>
                        </tr>
                        <tr>
                            <td>Search</td>
                            <td>Elasticsearch</td>
                            <td>8.x</td>
                            <td>Full-text search, log analytics</td>
                        </tr>
                        <tr>
                            <td>Time-Series</td>
                            <td>InfluxDB / TimescaleDB</td>
                            <td>2.x / 2.x</td>
                            <td>SCADA tag storage</td>
                        </tr>
                        <tr>
                            <td>Message Queue</td>
                            <td>RabbitMQ / Redis Streams</td>
                            <td>3.x</td>
                            <td>Async processing</td>
                        </tr>
                        <tr>
                            <td>Containers</td>
                            <td>Docker</td>
                            <td>24.x</td>
                            <td>Application packaging</td>
                        </tr>
                        <tr>
                            <td>Orchestration</td>
                            <td>Kubernetes</td>
                            <td>1.28+</td>
                            <td>Container orchestration</td>
                        </tr>
                    </tbody>
                </table>

                <h3>13.2 Third-Party Libraries</h3>
                <div class="component-grid">
                    <div class="component-card">
                        <h5>Chart.js</h5>
                        <p>Data visualization and charting</p>
                    </div>
                    <div class="component-card">
                        <h5>DataTables</h5>
                        <p>Interactive data tables</p>
                    </div>
                    <div class="component-card">
                        <h5>PHPMailer</h5>
                        <p>Email sending</p>
                    </div>
                    <div class="component-card">
                        <h5>TCPDF</h5>
                        <p>PDF report generation</p>
                    </div>
                    <div class="component-card">
                        <h5>PhpSpreadsheet</h5>
                        <p>Excel export/import</p>
                    </div>
                    <div class="component-card">
                        <h5>Monolog</h5>
                        <p>Logging framework</p>
                    </div>
                </div>

                <h3>13.3 Development & Operations</h3>
                <ul>
                    <li><strong>Version Control:</strong> Git with GitFlow branching model</li>
                    <li><strong>CI/CD:</strong> Jenkins / GitLab CI / GitHub Actions</li>
                    <li><strong>Monitoring:</strong> Prometheus + Grafana</li>
                    <li><strong>Log Management:</strong> ELK Stack (Elasticsearch, Logstash, Kibana)</li>
                    <li><strong>Infrastructure as Code:</strong> Terraform, Ansible</li>
                </ul>
            </div>
        </div>

        <!-- Section 14: Appendices -->
        <div id="section14" class="section page-break">
            <div class="section-header">
                <div class="section-num">Section 14</div>
                <h2>Appendices</h2>
            </div>
            <div class="section-content">
                <h3>Appendix A: Glossary</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Term</th>
                            <th>Definition</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td>DCS</td><td>Distributed Control System</td></tr>
                        <tr><td>DMZ</td><td>Demilitarized Zone - network segment between trusted and untrusted networks</td></tr>
                        <tr><td>HMI</td><td>Human-Machine Interface</td></tr>
                        <tr><td>ICS</td><td>Industrial Control System</td></tr>
                        <tr><td>IOC</td><td>Intelligent Operating Centre</td></tr>
                        <tr><td>OT</td><td>Operational Technology</td></tr>
                        <tr><td>PLC</td><td>Programmable Logic Controller</td></tr>
                        <tr><td>RPO</td><td>Recovery Point Objective - acceptable data loss</td></tr>
                        <tr><td>RTO</td><td>Recovery Time Objective - acceptable downtime</td></tr>
                        <tr><td>RTU</td><td>Remote Terminal Unit</td></tr>
                        <tr><td>SCADA</td><td>Supervisory Control and Data Acquisition</td></tr>
                    </tbody>
                </table>

                <h3>Appendix B: Related Documents</h3>
                <ul>
                    <li>IOC Installation Manual (POC, Hybrid, Cloud)</li>
                    <li>IOC Security Hardening Guide</li>
                    <li>IOC API Reference Documentation</li>
                    <li>IOC User Administration Guide</li>
                    <li>IOC SCADA Integration Guide</li>
                    <li>IOC Disaster Recovery Procedures</li>
                </ul>

                <h3>Appendix C: Contact Information</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Role</th>
                            <th>Responsibility</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td>Solution Architect</td><td>Architecture decisions, design reviews</td></tr>
                        <tr><td>Security Architect</td><td>Security controls, compliance</td></tr>
                        <tr><td>Infrastructure Lead</td><td>Platform operations, capacity planning</td></tr>
                        <tr><td>Development Lead</td><td>Application development, releases</td></tr>
                        <tr><td>SCADA Engineer</td><td>Industrial integration, protocols</td></tr>
                    </tbody>
                </table>

                <div class="info-box note" style="margin-top: 30px;">
                    <strong>Document Feedback</strong>
                    This architecture document is maintained by the Enterprise Architecture team. For corrections, updates, or questions, please contact the Solution Architecture team or submit feedback through the internal documentation portal.
                </div>
            </div>
        </div>

        <!-- Document Footer -->
        <div class="doc-footer">
            <p><strong><?= htmlspecialchars($app_name) ?></strong> - Architecture Review Document</p>
            <p>Document ID: <?= $doc_id ?> | Version <?= $version ?> | <?= $date ?></p>
            <p>© <?= date('Y') ?> <?= htmlspecialchars($company_name) ?>. All rights reserved.</p>
            <p style="margin-top: 15px; font-style: italic;">This document contains confidential architectural information. Distribution is limited to authorized personnel.</p>
        </div>
    </div>
</body>
</html>
