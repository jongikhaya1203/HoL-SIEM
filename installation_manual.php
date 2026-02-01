<?php
/**
 * HoL Installation Manual - PDF Ready Document
 * Covers: POC, Cloud Hybrid, and Full Cloud Installation
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HoL Installation Manual - Complete Guide</title>
    <style>
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none !important; }
            .page-break { page-break-before: always; }
            @page { margin: 1in; size: A4; }
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #1a1a2e;
            background: #fff;
            font-size: 11pt;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Cover Page */
        .cover-page {
            height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: white;
            margin: -20px;
            padding: 40px;
        }

        .cover-logo {
            font-size: 80px;
            margin-bottom: 30px;
        }

        .cover-title {
            font-size: 36pt;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .cover-subtitle {
            font-size: 18pt;
            color: #00b894;
            margin-bottom: 40px;
        }

        .cover-info {
            margin-top: 60px;
            font-size: 12pt;
            color: #94a3b8;
        }

        .cover-version {
            margin-top: 20px;
            padding: 10px 30px;
            background: rgba(0, 184, 148, 0.2);
            border-radius: 20px;
            color: #00b894;
        }

        /* Headers */
        h1 {
            font-size: 24pt;
            color: #1a1a2e;
            border-bottom: 3px solid #00b894;
            padding-bottom: 10px;
            margin: 30px 0 20px 0;
        }

        h2 {
            font-size: 16pt;
            color: #16213e;
            margin: 25px 0 15px 0;
            padding-left: 15px;
            border-left: 4px solid #00b894;
        }

        h3 {
            font-size: 13pt;
            color: #1f2b47;
            margin: 20px 0 10px 0;
        }

        h4 {
            font-size: 11pt;
            color: #2d3748;
            margin: 15px 0 8px 0;
        }

        /* Content */
        p {
            margin-bottom: 12px;
            text-align: justify;
        }

        ul, ol {
            margin: 10px 0 15px 25px;
        }

        li {
            margin-bottom: 6px;
        }

        /* Tables */
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 10pt;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 10px 12px;
            text-align: left;
        }

        th {
            background: #1a1a2e;
            color: white;
            font-weight: 600;
        }

        tr:nth-child(even) {
            background: #f8f9fa;
        }

        /* Code Blocks */
        .code-block {
            background: #1a1a2e;
            color: #e2e8f0;
            padding: 15px 20px;
            border-radius: 8px;
            font-family: 'Consolas', 'Monaco', monospace;
            font-size: 9pt;
            overflow-x: auto;
            margin: 15px 0;
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        .code-block .comment {
            color: #6b7280;
        }

        .code-block .command {
            color: #00b894;
        }

        .code-block .string {
            color: #fbbf24;
        }

        /* Info Boxes */
        .info-box {
            padding: 15px 20px;
            border-radius: 8px;
            margin: 15px 0;
            display: flex;
            gap: 12px;
        }

        .info-box.note {
            background: #e0f2fe;
            border-left: 4px solid #0284c7;
        }

        .info-box.warning {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
        }

        .info-box.danger {
            background: #fee2e2;
            border-left: 4px solid #ef4444;
        }

        .info-box.success {
            background: #d1fae5;
            border-left: 4px solid #10b981;
        }

        .info-box-icon {
            font-size: 18px;
        }

        .info-box-content {
            flex: 1;
        }

        .info-box-content strong {
            display: block;
            margin-bottom: 5px;
        }

        /* Steps */
        .step {
            display: flex;
            gap: 15px;
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .step-number {
            width: 32px;
            height: 32px;
            background: #00b894;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            flex-shrink: 0;
        }

        .step-content {
            flex: 1;
        }

        .step-content h4 {
            margin-top: 0;
            color: #1a1a2e;
        }

        /* TOC */
        .toc {
            background: #f8f9fa;
            padding: 25px 30px;
            border-radius: 10px;
            margin: 20px 0;
        }

        .toc h2 {
            border-left: none;
            padding-left: 0;
            margin-top: 0;
        }

        .toc ul {
            list-style: none;
            margin-left: 0;
        }

        .toc li {
            padding: 8px 0;
            border-bottom: 1px dashed #ddd;
        }

        .toc li:last-child {
            border-bottom: none;
        }

        .toc a {
            color: #1a1a2e;
            text-decoration: none;
        }

        .toc a:hover {
            color: #00b894;
        }

        .toc .section-num {
            color: #00b894;
            font-weight: 600;
            margin-right: 10px;
        }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 9pt;
            font-weight: 500;
        }

        .badge-poc { background: #dbeafe; color: #1d4ed8; }
        .badge-hybrid { background: #fef3c7; color: #b45309; }
        .badge-cloud { background: #d1fae5; color: #047857; }

        /* Diagrams */
        .diagram {
            background: #f8f9fa;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
        }

        .diagram svg {
            max-width: 100%;
        }

        /* Print Button */
        .print-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            padding: 15px 30px;
            background: linear-gradient(135deg, #00b894, #00a884);
            color: white;
            border: none;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 10px 30px rgba(0, 184, 148, 0.3);
            transition: all 0.3s;
        }

        .print-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(0, 184, 148, 0.4);
        }

        /* Footer */
        .doc-footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 2px solid #e5e7eb;
            text-align: center;
            color: #6b7280;
            font-size: 10pt;
        }

        /* Checklist */
        .checklist {
            list-style: none;
            margin-left: 0;
        }

        .checklist li {
            padding: 8px 0 8px 30px;
            position: relative;
        }

        .checklist li::before {
            content: "☐";
            position: absolute;
            left: 0;
            color: #00b894;
            font-size: 14pt;
        }
    </style>
</head>
<body>
    <!-- Print Button -->
    <button class="print-btn no-print" onclick="window.print()">
        📄 Save as PDF / Print
    </button>

    <!-- Cover Page -->
    <div class="cover-page">
        <div class="cover-logo">🏢</div>
        <div class="cover-title">HoL Intelligent Operating Centre</div>
        <div class="cover-subtitle">Installation Manual</div>
        <div style="font-size: 14pt; margin-top: 20px;">
            Complete Guide for POC, Cloud Hybrid, and Full Cloud Deployment
        </div>
        <div class="cover-info">
            <div>Document Version: 1.0</div>
            <div>Last Updated: <?= date('F Y') ?></div>
        </div>
        <div class="cover-version">For HoL Version 2.0+</div>
    </div>

    <div class="container">
        <!-- Table of Contents -->
        <div class="page-break"></div>
        <div class="toc">
            <h2>Table of Contents</h2>
            <ul>
                <li><span class="section-num">1.</span> <a href="#introduction">Introduction</a></li>
                <li><span class="section-num">2.</span> <a href="#requirements">System Requirements</a></li>
                <li><span class="section-num">3.</span> <a href="#poc">POC Installation (Proof of Concept)</a></li>
                <li><span class="section-num">4.</span> <a href="#hybrid">Cloud Hybrid Installation</a></li>
                <li><span class="section-num">5.</span> <a href="#cloud">Full Cloud Installation</a></li>
                <li><span class="section-num">6.</span> <a href="#post-install">Post-Installation Configuration</a></li>
                <li><span class="section-num">7.</span> <a href="#troubleshooting">Troubleshooting</a></li>
                <li><span class="section-num">8.</span> <a href="#appendix">Appendix</a></li>
            </ul>
        </div>

        <!-- Section 1: Introduction -->
        <div class="page-break"></div>
        <h1 id="introduction">1. Introduction</h1>

        <h2>1.1 About This Document</h2>
        <p>This installation manual provides step-by-step instructions for deploying the HoL Intelligent Operating Centre platform. It covers three deployment scenarios:</p>

        <table>
            <tr>
                <th>Deployment Type</th>
                <th>Best For</th>
                <th>Complexity</th>
            </tr>
            <tr>
                <td><span class="badge badge-poc">POC</span> Proof of Concept</td>
                <td>Evaluation, testing, demos</td>
                <td>Low</td>
            </tr>
            <tr>
                <td><span class="badge badge-hybrid">Hybrid</span> Cloud Hybrid</td>
                <td>SCADA/OT environments, data sovereignty</td>
                <td>Medium</td>
            </tr>
            <tr>
                <td><span class="badge badge-cloud">Cloud</span> Full Cloud</td>
                <td>Enterprise, scalability, managed services</td>
                <td>Medium-High</td>
            </tr>
        </table>

        <h2>1.2 Architecture Overview</h2>
        <div class="diagram">
            <svg viewBox="0 0 700 200" style="max-width: 600px;">
                <!-- POC Box -->
                <rect x="20" y="30" width="180" height="140" fill="#dbeafe" stroke="#1d4ed8" stroke-width="2" rx="10"/>
                <text x="110" y="60" text-anchor="middle" font-weight="bold" fill="#1d4ed8">POC</text>
                <text x="110" y="85" text-anchor="middle" font-size="10" fill="#1a1a2e">Single Server</text>
                <text x="110" y="105" text-anchor="middle" font-size="10" fill="#1a1a2e">All-in-One</text>
                <text x="110" y="125" text-anchor="middle" font-size="10" fill="#1a1a2e">XAMPP/Docker</text>
                <text x="110" y="150" text-anchor="middle" font-size="9" fill="#6b7280">Quick Setup</text>

                <!-- Hybrid Box -->
                <rect x="260" y="30" width="180" height="140" fill="#fef3c7" stroke="#b45309" stroke-width="2" rx="10"/>
                <text x="350" y="60" text-anchor="middle" font-weight="bold" fill="#b45309">HYBRID</text>
                <text x="350" y="85" text-anchor="middle" font-size="10" fill="#1a1a2e">On-Prem + Cloud</text>
                <text x="350" y="105" text-anchor="middle" font-size="10" fill="#1a1a2e">Sync Agent</text>
                <text x="350" y="125" text-anchor="middle" font-size="10" fill="#1a1a2e">VPN/Direct Connect</text>
                <text x="350" y="150" text-anchor="middle" font-size="9" fill="#6b7280">OT/SCADA Ready</text>

                <!-- Cloud Box -->
                <rect x="500" y="30" width="180" height="140" fill="#d1fae5" stroke="#047857" stroke-width="2" rx="10"/>
                <text x="590" y="60" text-anchor="middle" font-weight="bold" fill="#047857">FULL CLOUD</text>
                <text x="590" y="85" text-anchor="middle" font-size="10" fill="#1a1a2e">AWS/Azure/GCP</text>
                <text x="590" y="105" text-anchor="middle" font-size="10" fill="#1a1a2e">Managed Services</text>
                <text x="590" y="125" text-anchor="middle" font-size="10" fill="#1a1a2e">Auto-scaling</text>
                <text x="590" y="150" text-anchor="middle" font-size="9" fill="#6b7280">Enterprise Grade</text>
            </svg>
        </div>

        <!-- Section 2: Requirements -->
        <div class="page-break"></div>
        <h1 id="requirements">2. System Requirements</h1>

        <h2>2.1 Hardware Requirements</h2>
        <table>
            <tr>
                <th>Component</th>
                <th>POC (Minimum)</th>
                <th>Hybrid</th>
                <th>Full Cloud</th>
            </tr>
            <tr>
                <td><strong>CPU</strong></td>
                <td>2 cores</td>
                <td>4 cores (on-prem) + 2 cores (cloud)</td>
                <td>4+ cores</td>
            </tr>
            <tr>
                <td><strong>RAM</strong></td>
                <td>4 GB</td>
                <td>8 GB (on-prem) + 4 GB (cloud)</td>
                <td>8+ GB</td>
            </tr>
            <tr>
                <td><strong>Storage</strong></td>
                <td>20 GB SSD</td>
                <td>50 GB (on-prem) + 100 GB (cloud)</td>
                <td>100+ GB SSD</td>
            </tr>
            <tr>
                <td><strong>Network</strong></td>
                <td>100 Mbps</td>
                <td>1 Gbps + VPN</td>
                <td>1 Gbps</td>
            </tr>
        </table>

        <h2>2.2 Software Requirements</h2>
        <table>
            <tr>
                <th>Software</th>
                <th>Version</th>
                <th>Notes</th>
            </tr>
            <tr>
                <td><strong>Operating System</strong></td>
                <td>Windows 10/11, Ubuntu 20.04+, RHEL 8+</td>
                <td>64-bit required</td>
            </tr>
            <tr>
                <td><strong>Web Server</strong></td>
                <td>Apache 2.4+ or Nginx 1.18+</td>
                <td>mod_rewrite enabled</td>
            </tr>
            <tr>
                <td><strong>PHP</strong></td>
                <td>8.0 or higher</td>
                <td>Extensions: pdo_mysql, curl, gd, zip</td>
            </tr>
            <tr>
                <td><strong>Database</strong></td>
                <td>MySQL 8.0+ or MariaDB 10.6+</td>
                <td>InnoDB engine</td>
            </tr>
        </table>

        <h2>2.3 Network Requirements</h2>
        <table>
            <tr>
                <th>Port</th>
                <th>Protocol</th>
                <th>Purpose</th>
            </tr>
            <tr><td>80</td><td>TCP</td><td>HTTP (redirect to HTTPS)</td></tr>
            <tr><td>443</td><td>TCP</td><td>HTTPS (web interface)</td></tr>
            <tr><td>3306</td><td>TCP</td><td>MySQL (internal only)</td></tr>
            <tr><td>22</td><td>TCP</td><td>SSH (admin access)</td></tr>
            <tr><td>502</td><td>TCP</td><td>Modbus (SCADA - if applicable)</td></tr>
            <tr><td>102</td><td>TCP</td><td>S7 Protocol (SCADA - if applicable)</td></tr>
        </table>

        <!-- Section 3: POC Installation -->
        <div class="page-break"></div>
        <h1 id="poc">3. POC Installation (Proof of Concept)</h1>

        <div class="info-box note">
            <div class="info-box-icon">ℹ️</div>
            <div class="info-box-content">
                <strong>POC Purpose</strong>
                POC installations are designed for evaluation and testing. Do not use for production data or critical systems.
            </div>
        </div>

        <h2>3.1 Option A: XAMPP Installation (Windows/Mac)</h2>

        <div class="step">
            <div class="step-number">1</div>
            <div class="step-content">
                <h4>Download and Install XAMPP</h4>
                <p>Download XAMPP from <strong>https://www.apachefriends.org/</strong></p>
                <ul>
                    <li>Select PHP 8.0+ version</li>
                    <li>Install to default location (C:\xampp or /Applications/XAMPP)</li>
                    <li>Select components: Apache, MySQL, PHP, phpMyAdmin</li>
                </ul>
            </div>
        </div>

        <div class="step">
            <div class="step-number">2</div>
            <div class="step-content">
                <h4>Start Services</h4>
                <p>Open XAMPP Control Panel and start:</p>
                <ul>
                    <li>Apache (Web Server)</li>
                    <li>MySQL (Database Server)</li>
                </ul>
                <div class="info-box warning">
                    <div class="info-box-icon">⚠️</div>
                    <div class="info-box-content">
                        <strong>Port Conflict</strong>
                        If Apache fails to start, port 80 may be in use by Skype or IIS. Change the port in httpd.conf or stop the conflicting service.
                    </div>
                </div>
            </div>
        </div>

        <div class="step">
            <div class="step-number">3</div>
            <div class="step-content">
                <h4>Deploy HoL Application</h4>
                <p>Extract the HoL application files:</p>
                <div class="code-block"><span class="comment"># Windows</span>
Extract ioc-application.zip to: C:\xampp\htdocs\ioc\

<span class="comment"># Mac/Linux</span>
Extract ioc-application.zip to: /opt/lampp/htdocs/ioc/</div>
            </div>
        </div>

        <div class="step">
            <div class="step-number">4</div>
            <div class="step-content">
                <h4>Create Database</h4>
                <p>Open phpMyAdmin (http://localhost/phpmyadmin) and run:</p>
                <div class="code-block"><span class="comment">-- Create database</span>
CREATE DATABASE ioc_poc CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

<span class="comment">-- Create user</span>
CREATE USER <span class="string">'hol_user'</span>@<span class="string">'localhost'</span> IDENTIFIED BY <span class="string">'your_secure_password'</span>;
GRANT ALL PRIVILEGES ON ioc_poc.* TO <span class="string">'hol_user'</span>@<span class="string">'localhost'</span>;
FLUSH PRIVILEGES;</div>
            </div>
        </div>

        <div class="step">
            <div class="step-number">5</div>
            <div class="step-content">
                <h4>Configure Application</h4>
                <p>Edit the database configuration file:</p>
                <div class="code-block"><span class="comment">// config/database.php</span>
return [
    <span class="string">'host'</span>     => <span class="string">'localhost'</span>,
    <span class="string">'database'</span> => <span class="string">'ioc_poc'</span>,
    <span class="string">'username'</span> => <span class="string">'hol_user'</span>,
    <span class="string">'password'</span> => <span class="string">'your_secure_password'</span>,
    <span class="string">'charset'</span>  => <span class="string">'utf8mb4'</span>
];</div>
            </div>
        </div>

        <div class="step">
            <div class="step-number">6</div>
            <div class="step-content">
                <h4>Run Installation Wizard</h4>
                <p>Open browser and navigate to:</p>
                <div class="code-block"><span class="command">http://localhost/ioc/install.php</span></div>
                <p>Follow the wizard to complete setup.</p>
            </div>
        </div>

        <h2>3.2 Option B: Docker Installation</h2>

        <div class="step">
            <div class="step-number">1</div>
            <div class="step-content">
                <h4>Install Docker</h4>
                <p>Download and install Docker Desktop from <strong>https://www.docker.com/</strong></p>
            </div>
        </div>

        <div class="step">
            <div class="step-number">2</div>
            <div class="step-content">
                <h4>Create docker-compose.yml</h4>
                <div class="code-block">version: <span class="string">'3.8'</span>

services:
  ioc-web:
    image: ioc/intelligent-operating-centre:latest
    ports:
      - <span class="string">"8080:80"</span>
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
  db_data:</div>
            </div>
        </div>

        <div class="step">
            <div class="step-number">3</div>
            <div class="step-content">
                <h4>Start Containers</h4>
                <div class="code-block"><span class="command">docker-compose up -d</span>

<span class="comment"># Access the application at:</span>
<span class="command">http://localhost:8080</span></div>
            </div>
        </div>

        <!-- Section 4: Cloud Hybrid -->
        <div class="page-break"></div>
        <h1 id="hybrid">4. Cloud Hybrid Installation</h1>

        <div class="info-box note">
            <div class="info-box-icon">ℹ️</div>
            <div class="info-box-content">
                <strong>Hybrid Architecture</strong>
                Hybrid deployment keeps sensitive OT/SCADA data on-premises while leveraging cloud for analytics, dashboards, and remote access.
            </div>
        </div>

        <h2>4.1 Architecture Diagram</h2>
        <div class="diagram">
            <svg viewBox="0 0 700 280" style="max-width: 650px;">
                <!-- On-Premises Box -->
                <rect x="20" y="20" width="280" height="240" fill="none" stroke="#b45309" stroke-width="2" stroke-dasharray="5,5" rx="10"/>
                <text x="160" y="45" text-anchor="middle" font-weight="bold" fill="#b45309">ON-PREMISES</text>

                <!-- SCADA -->
                <rect x="40" y="60" width="100" height="50" fill="#fef3c7" stroke="#b45309" stroke-width="2" rx="5"/>
                <text x="90" y="90" text-anchor="middle" font-size="11" fill="#1a1a2e">SCADA/PLC</text>

                <!-- Local DB -->
                <rect x="160" y="60" width="100" height="50" fill="#fef3c7" stroke="#b45309" stroke-width="2" rx="5"/>
                <text x="210" y="90" text-anchor="middle" font-size="11" fill="#1a1a2e">Local DB</text>

                <!-- Sync Agent -->
                <rect x="40" y="130" width="220" height="50" fill="#fef3c7" stroke="#b45309" stroke-width="2" rx="5"/>
                <text x="150" y="160" text-anchor="middle" font-size="11" fill="#1a1a2e">HoL Sync Agent</text>

                <!-- Firewall -->
                <rect x="40" y="200" width="220" height="40" fill="#fee2e2" stroke="#ef4444" stroke-width="2" rx="5"/>
                <text x="150" y="225" text-anchor="middle" font-size="11" fill="#ef4444">Firewall / VPN</text>

                <!-- Cloud Box -->
                <rect x="400" y="20" width="280" height="240" fill="none" stroke="#047857" stroke-width="2" stroke-dasharray="5,5" rx="10"/>
                <text x="540" y="45" text-anchor="middle" font-weight="bold" fill="#047857">CLOUD (AWS/AZURE)</text>

                <!-- HoL Server -->
                <rect x="420" y="60" width="240" height="50" fill="#d1fae5" stroke="#047857" stroke-width="2" rx="5"/>
                <text x="540" y="90" text-anchor="middle" font-size="11" fill="#1a1a2e">HoL Main Server</text>

                <!-- Cloud DB -->
                <rect x="420" y="130" width="110" height="50" fill="#d1fae5" stroke="#047857" stroke-width="2" rx="5"/>
                <text x="475" y="160" text-anchor="middle" font-size="11" fill="#1a1a2e">Cloud DB</text>

                <!-- Storage -->
                <rect x="550" y="130" width="110" height="50" fill="#d1fae5" stroke="#047857" stroke-width="2" rx="5"/>
                <text x="605" y="160" text-anchor="middle" font-size="11" fill="#1a1a2e">S3/Blob</text>

                <!-- Dashboard -->
                <rect x="420" y="200" width="240" height="40" fill="#d1fae5" stroke="#047857" stroke-width="2" rx="5"/>
                <text x="540" y="225" text-anchor="middle" font-size="11" fill="#1a1a2e">Web Dashboard</text>

                <!-- Connection Arrow -->
                <line x1="300" y1="155" x2="400" y2="85" stroke="#00b894" stroke-width="3"/>
                <polygon points="395,80 405,85 395,95" fill="#00b894"/>
                <text x="350" y="105" text-anchor="middle" font-size="9" fill="#00b894">VPN Tunnel</text>
            </svg>
        </div>

        <h2>4.2 On-Premises Setup</h2>

        <div class="step">
            <div class="step-number">1</div>
            <div class="step-content">
                <h4>Install On-Premises Components</h4>
                <p>On your local server, install the required software:</p>
                <div class="code-block"><span class="comment"># Ubuntu/Debian</span>
sudo apt update
sudo apt install apache2 php8.1 php8.1-mysql php8.1-curl mariadb-server -y

<span class="comment"># Enable required modules</span>
sudo a2enmod rewrite ssl
sudo systemctl restart apache2</div>
            </div>
        </div>

        <div class="step">
            <div class="step-number">2</div>
            <div class="step-content">
                <h4>Deploy HoL Sync Agent</h4>
                <p>Install the sync agent that will communicate with the cloud:</p>
                <div class="code-block"><span class="comment"># Download sync agent</span>
wget https://releases.ioc.example.com/sync-agent-latest.tar.gz
tar -xzf sync-agent-latest.tar.gz -C /opt/ioc-agent/

<span class="comment"># Configure agent</span>
sudo nano /opt/ioc-agent/config/agent.php</div>
            </div>
        </div>

        <div class="step">
            <div class="step-number">3</div>
            <div class="step-content">
                <h4>Configure Sync Agent</h4>
                <div class="code-block"><span class="comment">// /opt/ioc-agent/config/agent.php</span>
return [
    <span class="string">'cloud_endpoint'</span> => <span class="string">'https://your-cloud-ioc.example.com/api/sync'</span>,
    <span class="string">'api_key'</span>        => <span class="string">'YOUR_SECURE_API_KEY'</span>,
    <span class="string">'sync_interval'</span>  => 60,  <span class="comment">// seconds</span>

    <span class="string">'data_sources'</span> => [
        <span class="string">'scada'</span> => [
            <span class="string">'enabled'</span>  => true,
            <span class="string">'protocol'</span> => <span class="string">'modbus'</span>,
            <span class="string">'host'</span>     => <span class="string">'192.168.1.100'</span>,
            <span class="string">'port'</span>     => 502
        ],
        <span class="string">'local_db'</span> => [
            <span class="string">'enabled'</span> => true,
            <span class="string">'tables'</span>  => [<span class="string">'scan_results'</span>, <span class="string">'alerts'</span>, <span class="string">'sensor_data'</span>]
        ]
    ],

    <span class="string">'offline_mode'</span> => [
        <span class="string">'enabled'</span>        => true,
        <span class="string">'queue_max_size'</span> => 10000
    ]
];</div>
            </div>
        </div>

        <div class="step">
            <div class="step-number">4</div>
            <div class="step-content">
                <h4>Start Sync Agent Service</h4>
                <div class="code-block"><span class="comment"># Create systemd service</span>
sudo nano /etc/systemd/system/ioc-agent.service

<span class="comment"># Add service configuration:</span>
[Unit]
Description=HoL Sync Agent
After=network.target

[Service]
Type=simple
User=www-data
ExecStart=/usr/bin/php /opt/ioc-agent/sync.php
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target

<span class="comment"># Enable and start</span>
sudo systemctl enable ioc-agent
sudo systemctl start ioc-agent</div>
            </div>
        </div>

        <h2>4.3 VPN Configuration</h2>

        <h3>AWS Site-to-Site VPN</h3>
        <div class="code-block"><span class="comment"># Create Virtual Private Gateway</span>
aws ec2 create-vpn-gateway --type ipsec.1

<span class="comment"># Create Customer Gateway (your on-premises router IP)</span>
aws ec2 create-customer-gateway --type ipsec.1 \
    --public-ip YOUR_ONPREM_PUBLIC_IP \
    --bgp-asn 65000

<span class="comment"># Create VPN Connection</span>
aws ec2 create-vpn-connection --type ipsec.1 \
    --customer-gateway-id cgw-xxxxxxxx \
    --vpn-gateway-id vgw-xxxxxxxx</div>

        <h3>Azure VPN Gateway</h3>
        <div class="code-block"><span class="comment"># Create VPN Gateway</span>
az network vnet-gateway create \
    --name HoL-VPN-Gateway \
    --resource-group HoL-RG \
    --vnet HoL-VNet \
    --gateway-type Vpn \
    --vpn-type RouteBased \
    --sku VpnGw1

<span class="comment"># Create Local Network Gateway</span>
az network local-gateway create \
    --name OnPrem-Gateway \
    --resource-group HoL-RG \
    --gateway-ip-address YOUR_ONPREM_IP \
    --local-address-prefixes 10.0.0.0/24</div>

        <h2>4.4 Cloud Setup (Continue to Section 5)</h2>
        <p>After configuring the on-premises components, proceed to Section 5 for cloud server setup.</p>

        <!-- Section 5: Full Cloud -->
        <div class="page-break"></div>
        <h1 id="cloud">5. Full Cloud Installation</h1>

        <h2>5.1 AWS Deployment</h2>

        <h3>Step 1: Launch EC2 Instance</h3>
        <div class="code-block"><span class="comment"># AWS CLI - Launch EC2 Instance</span>
aws ec2 run-instances \
    --image-id ami-0abcdef1234567890 \
    --instance-type t3.medium \
    --key-name your-key-pair \
    --security-group-ids sg-xxxxxxxx \
    --subnet-id subnet-xxxxxxxx \
    --block-device-mappings '[{"DeviceName":"/dev/sda1","Ebs":{"VolumeSize":50,"VolumeType":"gp3"}}]' \
    --tag-specifications 'ResourceType=instance,Tags=[{Key=Name,Value=HoL-Server}]'</div>

        <h3>Step 2: Configure Security Group</h3>
        <table>
            <tr>
                <th>Type</th>
                <th>Port</th>
                <th>Source</th>
                <th>Description</th>
            </tr>
            <tr><td>SSH</td><td>22</td><td>Your IP</td><td>Admin access</td></tr>
            <tr><td>HTTP</td><td>80</td><td>0.0.0.0/0</td><td>Web (redirect)</td></tr>
            <tr><td>HTTPS</td><td>443</td><td>0.0.0.0/0</td><td>Web interface</td></tr>
            <tr><td>MySQL</td><td>3306</td><td>VPC CIDR</td><td>Database (internal)</td></tr>
        </table>

        <h3>Step 3: Install Dependencies</h3>
        <div class="code-block"><span class="comment"># Connect to EC2</span>
ssh -i your-key.pem ec2-user@your-ec2-public-ip

<span class="comment"># Update system</span>
sudo yum update -y

<span class="comment"># Install Apache, PHP, and dependencies</span>
sudo amazon-linux-extras install php8.0 -y
sudo yum install httpd php-mysqlnd php-gd php-curl php-zip -y

<span class="comment"># Start Apache</span>
sudo systemctl enable httpd
sudo systemctl start httpd</div>

        <h3>Step 4: Configure RDS Database</h3>
        <div class="code-block"><span class="comment"># Create RDS MySQL instance</span>
aws rds create-db-instance \
    --db-instance-identifier ioc-database \
    --db-instance-class db.t3.small \
    --engine mysql \
    --engine-version 8.0 \
    --master-username admin \
    --master-user-password YOUR_SECURE_PASSWORD \
    --allocated-storage 20 \
    --storage-type gp2 \
    --vpc-security-group-ids sg-xxxxxxxx \
    --db-subnet-group-name your-subnet-group \
    --backup-retention-period 7 \
    --multi-az</div>

        <h3>Step 5: Deploy Application</h3>
        <div class="code-block"><span class="comment"># Download HoL application</span>
cd /var/www/html
sudo wget https://releases.ioc.example.com/ioc-latest.zip
sudo unzip ioc-latest.zip
sudo chown -R apache:apache /var/www/html
sudo chmod -R 755 /var/www/html

<span class="comment"># Configure database connection</span>
sudo nano /var/www/html/config/database.php</div>

        <h3>Step 6: Configure SSL with Let's Encrypt</h3>
        <div class="code-block"><span class="comment"># Install Certbot</span>
sudo yum install certbot python3-certbot-apache -y

<span class="comment"># Obtain SSL certificate</span>
sudo certbot --apache -d your-domain.com -d www.your-domain.com

<span class="comment"># Auto-renewal (add to crontab)</span>
echo "0 12 * * * /usr/bin/certbot renew --quiet" | sudo crontab -</div>

        <h2>5.2 Azure Deployment</h2>

        <h3>Step 1: Create Virtual Machine</h3>
        <div class="code-block"><span class="comment"># Create resource group</span>
az group create --name HoL-RG --location eastus

<span class="comment"># Create VM</span>
az vm create \
    --resource-group HoL-RG \
    --name HoL-Server \
    --image UbuntuLTS \
    --size Standard_B2s \
    --admin-username azureuser \
    --generate-ssh-keys \
    --public-ip-sku Standard

<span class="comment"># Open ports</span>
az vm open-port --port 80 --resource-group HoL-RG --name HoL-Server
az vm open-port --port 443 --resource-group HoL-RG --name HoL-Server --priority 1001</div>

        <h3>Step 2: Create Azure Database for MySQL</h3>
        <div class="code-block"><span class="comment"># Create MySQL server</span>
az mysql flexible-server create \
    --resource-group HoL-RG \
    --name ioc-mysql-server \
    --admin-user adminuser \
    --admin-password YOUR_SECURE_PASSWORD \
    --sku-name Standard_B1ms \
    --tier Burstable \
    --storage-size 20

<span class="comment"># Create database</span>
az mysql flexible-server db create \
    --resource-group HoL-RG \
    --server-name ioc-mysql-server \
    --database-name hol_production</div>

        <h3>Step 3: Install Application</h3>
        <div class="code-block"><span class="comment"># Connect to VM</span>
ssh azureuser@your-vm-public-ip

<span class="comment"># Install LAMP stack</span>
sudo apt update
sudo apt install apache2 php8.1 php8.1-mysql php8.1-curl php8.1-gd php8.1-zip -y

<span class="comment"># Deploy application</span>
cd /var/www/html
sudo wget https://releases.ioc.example.com/ioc-latest.zip
sudo unzip ioc-latest.zip
sudo chown -R www-data:www-data /var/www/html

<span class="comment"># Enable Apache modules</span>
sudo a2enmod rewrite ssl
sudo systemctl restart apache2</div>

        <h2>5.3 Google Cloud Platform Deployment</h2>
        <div class="code-block"><span class="comment"># Create Compute Engine instance</span>
gcloud compute instances create ioc-server \
    --zone=us-central1-a \
    --machine-type=e2-medium \
    --image-family=ubuntu-2204-lts \
    --image-project=ubuntu-os-cloud \
    --boot-disk-size=50GB \
    --boot-disk-type=pd-ssd \
    --tags=http-server,https-server

<span class="comment"># Create firewall rules</span>
gcloud compute firewall-rules create allow-http \
    --allow tcp:80 --target-tags=http-server
gcloud compute firewall-rules create allow-https \
    --allow tcp:443 --target-tags=https-server

<span class="comment"># Create Cloud SQL instance</span>
gcloud sql instances create ioc-mysql \
    --database-version=MYSQL_8_0 \
    --tier=db-g1-small \
    --region=us-central1</div>

        <!-- Section 6: Post-Installation -->
        <div class="page-break"></div>
        <h1 id="post-install">6. Post-Installation Configuration</h1>

        <h2>6.1 Initial Setup Wizard</h2>
        <p>After installation, access the web interface and complete the setup wizard:</p>
        <ol>
            <li>Navigate to <strong>https://your-server/install.php</strong></li>
            <li>Verify system requirements</li>
            <li>Configure database connection</li>
            <li>Create administrator account</li>
            <li>Set application name and branding</li>
            <li>Configure email settings (SMTP)</li>
            <li>Enable desired modules</li>
        </ol>

        <h2>6.2 Security Hardening</h2>

        <h3>Recommended Security Settings</h3>
        <ul class="checklist">
            <li>Change default admin password immediately</li>
            <li>Enable two-factor authentication (2FA)</li>
            <li>Configure SSL/TLS with strong ciphers</li>
            <li>Set up firewall rules (allow only necessary ports)</li>
            <li>Enable audit logging</li>
            <li>Configure session timeout (15-30 minutes)</li>
            <li>Disable directory listing in Apache/Nginx</li>
            <li>Remove installation files after setup</li>
        </ul>

        <h3>Apache Security Configuration</h3>
        <div class="code-block"><span class="comment"># /etc/apache2/conf-available/security.conf</span>
ServerTokens Prod
ServerSignature Off
TraceEnable Off

&lt;Directory /var/www/html&gt;
    Options -Indexes -FollowSymLinks
    AllowOverride All
    Require all granted
&lt;/Directory&gt;

<span class="comment"># Enable headers module</span>
&lt;IfModule mod_headers.c&gt;
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Strict-Transport-Security "max-age=31536000"
&lt;/IfModule&gt;</div>

        <h2>6.3 Backup Configuration</h2>
        <div class="code-block"><span class="comment"># Create backup script</span>
#!/bin/bash
<span class="comment"># /opt/scripts/backup.sh</span>

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/opt/backups"

<span class="comment"># Database backup</span>
mysqldump -u hol_user -p'password' hol_production > $BACKUP_DIR/db_$DATE.sql

<span class="comment"># Application files backup</span>
tar -czf $BACKUP_DIR/files_$DATE.tar.gz /var/www/html

<span class="comment"># Keep only last 7 days</span>
find $BACKUP_DIR -type f -mtime +7 -delete

<span class="comment"># Add to crontab (daily at 2 AM)</span>
0 2 * * * /opt/scripts/backup.sh</div>

        <h2>6.4 Monitoring Setup</h2>
        <p>Configure system monitoring to ensure optimal performance:</p>
        <ul>
            <li>Enable HoL built-in health monitoring</li>
            <li>Configure email alerts for critical events</li>
            <li>Set up log rotation</li>
            <li>Monitor disk space, CPU, and memory usage</li>
        </ul>

        <!-- Section 7: Troubleshooting -->
        <div class="page-break"></div>
        <h1 id="troubleshooting">7. Troubleshooting</h1>

        <h2>7.1 Common Issues</h2>

        <h3>Database Connection Failed</h3>
        <div class="info-box danger">
            <div class="info-box-icon">❌</div>
            <div class="info-box-content">
                <strong>Error: "Unable to connect to database"</strong>
            </div>
        </div>
        <p><strong>Solutions:</strong></p>
        <ol>
            <li>Verify MySQL/MariaDB service is running: <code>sudo systemctl status mysql</code></li>
            <li>Check database credentials in config/database.php</li>
            <li>Ensure database exists: <code>mysql -u root -p -e "SHOW DATABASES;"</code></li>
            <li>Check firewall allows port 3306 (for remote connections)</li>
            <li>Verify user permissions: <code>SHOW GRANTS FOR 'hol_user'@'localhost';</code></li>
        </ol>

        <h3>Apache/Nginx Not Starting</h3>
        <div class="info-box danger">
            <div class="info-box-icon">❌</div>
            <div class="info-box-content">
                <strong>Error: "Port 80 already in use"</strong>
            </div>
        </div>
        <p><strong>Solutions:</strong></p>
        <ol>
            <li>Find process using port 80: <code>sudo netstat -tulpn | grep :80</code></li>
            <li>Stop conflicting service or change Apache port</li>
            <li>Check configuration syntax: <code>sudo apachectl configtest</code></li>
        </ol>

        <h3>Permission Denied Errors</h3>
        <p><strong>Solutions:</strong></p>
        <div class="code-block"><span class="comment"># Fix file ownership</span>
sudo chown -R www-data:www-data /var/www/html

<span class="comment"># Fix permissions</span>
sudo find /var/www/html -type f -exec chmod 644 {} \;
sudo find /var/www/html -type d -exec chmod 755 {} \;

<span class="comment"># Make storage writable</span>
sudo chmod -R 775 /var/www/html/storage
sudo chmod -R 775 /var/www/html/logs</div>

        <h3>SSL Certificate Issues</h3>
        <p><strong>Solutions:</strong></p>
        <div class="code-block"><span class="comment"># Test SSL configuration</span>
sudo openssl s_client -connect your-domain.com:443

<span class="comment"># Renew Let's Encrypt certificate</span>
sudo certbot renew --dry-run

<span class="comment"># Check certificate expiry</span>
sudo certbot certificates</div>

        <h2>7.2 Log Files</h2>
        <table>
            <tr>
                <th>Log Type</th>
                <th>Location</th>
            </tr>
            <tr><td>Apache Error Log</td><td>/var/log/apache2/error.log</td></tr>
            <tr><td>Apache Access Log</td><td>/var/log/apache2/access.log</td></tr>
            <tr><td>PHP Errors</td><td>/var/log/php/error.log</td></tr>
            <tr><td>MySQL Log</td><td>/var/log/mysql/error.log</td></tr>
            <tr><td>HoL Application Log</td><td>/var/www/html/logs/app.log</td></tr>
            <tr><td>Sync Agent Log</td><td>/opt/ioc-agent/logs/sync.log</td></tr>
        </table>

        <!-- Section 8: Appendix -->
        <div class="page-break"></div>
        <h1 id="appendix">8. Appendix</h1>

        <h2>8.1 Quick Reference Commands</h2>
        <table>
            <tr>
                <th>Task</th>
                <th>Command</th>
            </tr>
            <tr><td>Start Apache</td><td><code>sudo systemctl start apache2</code></td></tr>
            <tr><td>Stop Apache</td><td><code>sudo systemctl stop apache2</code></td></tr>
            <tr><td>Restart Apache</td><td><code>sudo systemctl restart apache2</code></td></tr>
            <tr><td>Start MySQL</td><td><code>sudo systemctl start mysql</code></td></tr>
            <tr><td>Check PHP version</td><td><code>php -v</code></td></tr>
            <tr><td>Check disk space</td><td><code>df -h</code></td></tr>
            <tr><td>Check memory</td><td><code>free -m</code></td></tr>
            <tr><td>View live logs</td><td><code>tail -f /var/log/apache2/error.log</code></td></tr>
        </table>

        <h2>8.2 Default Ports</h2>
        <table>
            <tr><th>Service</th><th>Port</th><th>Protocol</th></tr>
            <tr><td>HTTP</td><td>80</td><td>TCP</td></tr>
            <tr><td>HTTPS</td><td>443</td><td>TCP</td></tr>
            <tr><td>MySQL</td><td>3306</td><td>TCP</td></tr>
            <tr><td>SSH</td><td>22</td><td>TCP</td></tr>
            <tr><td>Modbus</td><td>502</td><td>TCP</td></tr>
            <tr><td>OPC UA</td><td>4840</td><td>TCP</td></tr>
            <tr><td>DNP3</td><td>20000</td><td>TCP</td></tr>
        </table>

        <h2>8.3 Environment Variables</h2>
        <div class="code-block"><span class="comment"># /etc/environment or .env file</span>
HOL_ENV=production
HOL_DEBUG=false
HOL_DB_HOST=localhost
HOL_DB_NAME=hol_production
HOL_DB_USER=hol_user
HOL_DB_PASS=secure_password
HOL_CACHE_DRIVER=redis
HOL_SESSION_DRIVER=database
HOL_LOG_LEVEL=warning</div>

        <h2>8.4 Support Contacts</h2>
        <table>
            <tr><th>Type</th><th>Contact</th></tr>
            <tr><td>Technical Support</td><td>support@ioc.example.com</td></tr>
            <tr><td>Sales Inquiries</td><td>sales@ioc.example.com</td></tr>
            <tr><td>Documentation</td><td>https://docs.ioc.example.com</td></tr>
            <tr><td>Community Forum</td><td>https://community.ioc.example.com</td></tr>
        </table>

        <!-- Footer -->
        <div class="doc-footer">
            <p><strong>HoL Intelligent Operating Centre - Installation Manual</strong></p>
            <p>Version 1.0 | <?= date('F Y') ?></p>
            <p>© <?= date('Y') ?> HoL. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
