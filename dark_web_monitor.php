<?php
/**
 * Dark Web Monitoring System
 * Track company email addresses found on dark web sites and underground forums
 */

require_once __DIR__ . '/classes/Database.php';

// Initialize database
try {
    $db = Database::getInstance();
} catch (Exception $e) {
    $db = null;
}

// Sample dark web exposure data
$darkWebExposures = [
    [
        'id' => 'DWE-001',
        'email' => 'john.smith@company.com',
        'employee_name' => 'John Smith',
        'department' => 'Finance',
        'exposure_type' => 'Data Breach',
        'source' => 'BreachForums',
        'source_type' => 'Underground Forum',
        'discovered_date' => '2025-01-25 08:30:00',
        'breach_date' => '2025-01-15',
        'data_exposed' => ['Email', 'Password Hash', 'Full Name', 'Phone Number'],
        'password_plain' => false,
        'severity' => 'critical',
        'status' => 'active',
        'breach_name' => 'MegaCorp Data Dump 2025',
        'records_in_breach' => '2.3 Million',
        'price_listed' => '$500 USD'
    ],
    [
        'id' => 'DWE-002',
        'email' => 'sarah.jones@company.com',
        'employee_name' => 'Sarah Jones',
        'department' => 'Human Resources',
        'exposure_type' => 'Credential Leak',
        'source' => 'RaidForums Archive',
        'source_type' => 'Leaked Database',
        'discovered_date' => '2025-01-24 14:15:00',
        'breach_date' => '2024-11-20',
        'data_exposed' => ['Email', 'Password (Plaintext)', 'Username'],
        'password_plain' => true,
        'severity' => 'critical',
        'status' => 'active',
        'breach_name' => 'LinkedIn Scrape 2024',
        'records_in_breach' => '500K',
        'price_listed' => 'Free'
    ],
    [
        'id' => 'DWE-003',
        'email' => 'michael.chen@company.com',
        'employee_name' => 'Michael Chen',
        'department' => 'Engineering',
        'exposure_type' => 'Paste Site',
        'source' => 'Pastebin Clone',
        'source_type' => 'Paste Site',
        'discovered_date' => '2025-01-23 22:45:00',
        'breach_date' => '2025-01-22',
        'data_exposed' => ['Email', 'API Keys', 'Internal URLs'],
        'password_plain' => false,
        'severity' => 'high',
        'status' => 'investigating',
        'breach_name' => 'Dev Credentials Dump',
        'records_in_breach' => '1,200',
        'price_listed' => 'N/A'
    ],
    [
        'id' => 'DWE-004',
        'email' => 'emily.davis@company.com',
        'employee_name' => 'Emily Davis',
        'department' => 'Marketing',
        'exposure_type' => 'Combo List',
        'source' => 'Telegram Channel',
        'source_type' => 'Messaging Platform',
        'discovered_date' => '2025-01-22 16:30:00',
        'breach_date' => '2025-01-10',
        'data_exposed' => ['Email', 'Password Hash'],
        'password_plain' => false,
        'severity' => 'high',
        'status' => 'resolved',
        'breach_name' => 'Collection #7 Combo',
        'records_in_breach' => '10 Million',
        'price_listed' => '$50 USD'
    ],
    [
        'id' => 'DWE-005',
        'email' => 'david.wilson@company.com',
        'employee_name' => 'David Wilson',
        'department' => 'Sales',
        'exposure_type' => 'Stealer Logs',
        'source' => 'Russian Market',
        'source_type' => 'Dark Web Marketplace',
        'discovered_date' => '2025-01-26 03:20:00',
        'breach_date' => '2025-01-25',
        'data_exposed' => ['Email', 'Password (Plaintext)', 'Browser Cookies', 'Saved Cards', 'Session Tokens'],
        'password_plain' => true,
        'severity' => 'critical',
        'status' => 'active',
        'breach_name' => 'RedLine Stealer Logs',
        'records_in_breach' => '50K',
        'price_listed' => '$10 per log'
    ],
    [
        'id' => 'DWE-006',
        'email' => 'admin@company.com',
        'employee_name' => 'System Administrator',
        'department' => 'IT',
        'exposure_type' => 'Admin Credentials',
        'source' => 'Exploit.in',
        'source_type' => 'Hacking Forum',
        'discovered_date' => '2025-01-26 09:15:00',
        'breach_date' => '2025-01-24',
        'data_exposed' => ['Email', 'Password (Plaintext)', 'Admin Panel URLs', 'SSH Keys'],
        'password_plain' => true,
        'severity' => 'critical',
        'status' => 'active',
        'breach_name' => 'Corporate Admin Dump',
        'records_in_breach' => '5,000',
        'price_listed' => '$2,000 USD'
    ],
    [
        'id' => 'DWE-007',
        'email' => 'cfo@company.com',
        'employee_name' => 'Robert Johnson',
        'department' => 'Executive',
        'exposure_type' => 'Executive Targeting',
        'source' => 'XSS.is Forum',
        'source_type' => 'Underground Forum',
        'discovered_date' => '2025-01-25 11:00:00',
        'breach_date' => '2025-01-20',
        'data_exposed' => ['Email', 'Phone', 'Home Address', 'Social Media'],
        'password_plain' => false,
        'severity' => 'critical',
        'status' => 'investigating',
        'breach_name' => 'Executive Dox Collection',
        'records_in_breach' => '800',
        'price_listed' => '$5,000 USD'
    ],
    [
        'id' => 'DWE-008',
        'email' => 'support@company.com',
        'employee_name' => 'Support Team',
        'department' => 'Customer Service',
        'exposure_type' => 'Phishing Database',
        'source' => 'Genesis Market',
        'source_type' => 'Dark Web Marketplace',
        'discovered_date' => '2025-01-21 19:45:00',
        'breach_date' => '2025-01-18',
        'data_exposed' => ['Email', 'Password Hash', 'Customer Data Access'],
        'password_plain' => false,
        'severity' => 'high',
        'status' => 'resolved',
        'breach_name' => 'Support Portal Breach',
        'records_in_breach' => '25K',
        'price_listed' => '$200 USD'
    ],
    [
        'id' => 'DWE-009',
        'email' => 'hr.manager@company.com',
        'employee_name' => 'Lisa Thompson',
        'department' => 'Human Resources',
        'exposure_type' => 'Ransomware Leak',
        'source' => 'LockBit Blog',
        'source_type' => 'Ransomware Leak Site',
        'discovered_date' => '2025-01-20 06:30:00',
        'breach_date' => '2025-01-12',
        'data_exposed' => ['Email', 'Employee Records', 'Salary Data', 'SSN'],
        'password_plain' => false,
        'severity' => 'critical',
        'status' => 'active',
        'breach_name' => 'HR Systems Ransomware',
        'records_in_breach' => '15K employees',
        'price_listed' => 'Published Free'
    ],
    [
        'id' => 'DWE-010',
        'email' => 'developer@company.com',
        'employee_name' => 'Alex Kumar',
        'department' => 'Engineering',
        'exposure_type' => 'GitHub Leak',
        'source' => 'TruffleHog Scan',
        'source_type' => 'Public Repository',
        'discovered_date' => '2025-01-19 13:20:00',
        'breach_date' => '2025-01-19',
        'data_exposed' => ['Email', 'AWS Keys', 'Database Credentials', 'API Secrets'],
        'password_plain' => true,
        'severity' => 'high',
        'status' => 'resolved',
        'breach_name' => 'Accidental Commit',
        'records_in_breach' => '1',
        'price_listed' => 'N/A'
    ]
];

// Dark web sources being monitored
$monitoredSources = [
    ['name' => 'BreachForums', 'type' => 'Underground Forum', 'status' => 'active', 'last_scan' => '2025-01-26 14:30:00', 'findings' => 3],
    ['name' => 'Russian Market', 'type' => 'Dark Web Marketplace', 'status' => 'active', 'last_scan' => '2025-01-26 14:28:00', 'findings' => 2],
    ['name' => 'Genesis Market', 'type' => 'Dark Web Marketplace', 'status' => 'active', 'last_scan' => '2025-01-26 14:25:00', 'findings' => 1],
    ['name' => 'Telegram Channels', 'type' => 'Messaging Platform', 'status' => 'active', 'last_scan' => '2025-01-26 14:20:00', 'findings' => 5],
    ['name' => 'Exploit.in', 'type' => 'Hacking Forum', 'status' => 'active', 'last_scan' => '2025-01-26 14:15:00', 'findings' => 1],
    ['name' => 'XSS.is', 'type' => 'Underground Forum', 'status' => 'active', 'last_scan' => '2025-01-26 14:10:00', 'findings' => 2],
    ['name' => 'Ransomware Blogs', 'type' => 'Leak Sites', 'status' => 'active', 'last_scan' => '2025-01-26 14:05:00', 'findings' => 1],
    ['name' => 'Paste Sites', 'type' => 'Paste Monitoring', 'status' => 'active', 'last_scan' => '2025-01-26 14:00:00', 'findings' => 4],
    ['name' => 'GitHub/GitLab', 'type' => 'Code Repositories', 'status' => 'active', 'last_scan' => '2025-01-26 13:55:00', 'findings' => 1],
    ['name' => 'Have I Been Pwned', 'type' => 'Breach Database', 'status' => 'active', 'last_scan' => '2025-01-26 13:50:00', 'findings' => 8]
];

// Recent breach timeline
$breachTimeline = [
    ['date' => '2025-01-26', 'event' => 'New stealer logs detected on Russian Market', 'severity' => 'critical', 'affected' => 1],
    ['date' => '2025-01-26', 'event' => 'Admin credentials found on Exploit.in forum', 'severity' => 'critical', 'affected' => 1],
    ['date' => '2025-01-25', 'event' => 'Executive targeting data discovered', 'severity' => 'critical', 'affected' => 1],
    ['date' => '2025-01-25', 'event' => 'Corporate breach dump posted on BreachForums', 'severity' => 'critical', 'affected' => 1],
    ['date' => '2025-01-24', 'event' => 'LinkedIn scrape data contains employee emails', 'severity' => 'high', 'affected' => 1],
    ['date' => '2025-01-23', 'event' => 'Developer credentials found on paste site', 'severity' => 'high', 'affected' => 1],
    ['date' => '2025-01-22', 'event' => 'Combo list shared on Telegram', 'severity' => 'medium', 'affected' => 1],
    ['date' => '2025-01-21', 'event' => 'Support portal breach data on Genesis', 'severity' => 'high', 'affected' => 1],
    ['date' => '2025-01-20', 'event' => 'HR data leaked by ransomware gang', 'severity' => 'critical', 'affected' => 1],
    ['date' => '2025-01-19', 'event' => 'AWS credentials exposed in public repo', 'severity' => 'high', 'affected' => 1]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dark Web Monitoring - Email Exposure Tracker</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --primary: #ff0055;
            --primary-dark: #cc0044;
            --secondary: #4a1942;
            --bg-dark: #0a0a0f;
            --bg-card: #12121a;
            --bg-lighter: #1a1a25;
            --text: #e2e8f0;
            --text-muted: #94a3b8;
            --border: #2a2a3a;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
            --purple: #8b5cf6;
        }

        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: var(--bg-dark);
            color: var(--text);
            min-height: 100vh;
        }

        /* Header */
        .header {
            background: linear-gradient(135deg, #0a0a0f 0%, #1a0a15 50%, #2a1025 100%);
            border-bottom: 1px solid var(--primary);
            padding: 20px 30px;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-content {
            max-width: 1600px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header h1 span {
            color: var(--primary);
        }

        .header-actions {
            display: flex;
            gap: 10px;
        }

        .back-link {
            color: var(--text-muted);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 8px;
            background: var(--bg-lighter);
            transition: all 0.3s;
        }

        .back-link:hover {
            background: var(--bg-card);
            color: var(--text);
        }

        /* Main Content */
        .main-content {
            max-width: 1600px;
            margin: 0 auto;
            padding: 30px;
        }

        /* Alert Banner */
        .alert-banner {
            background: linear-gradient(90deg, rgba(255,0,85,0.2), rgba(239,68,68,0.1));
            border: 1px solid var(--primary);
            border-radius: 12px;
            padding: 20px 25px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            animation: alertPulse 3s infinite;
        }

        @keyframes alertPulse {
            0%, 100% { box-shadow: 0 0 20px rgba(255,0,85,0.3); }
            50% { box-shadow: 0 0 40px rgba(255,0,85,0.5); }
        }

        .alert-banner-content h2 {
            color: var(--primary);
            font-size: 20px;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-banner-content p {
            color: var(--text-muted);
            font-size: 14px;
        }

        .alert-banner-stats {
            display: flex;
            gap: 30px;
        }

        .alert-stat {
            text-align: center;
        }

        .alert-stat-value {
            font-size: 32px;
            font-weight: bold;
            color: var(--primary);
        }

        .alert-stat-label {
            font-size: 11px;
            color: var(--text-muted);
            text-transform: uppercase;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 20px;
            border: 1px solid var(--border);
            text-align: center;
        }

        .stat-card.critical {
            border-color: var(--danger);
            background: linear-gradient(135deg, var(--bg-card), rgba(239,68,68,0.1));
        }

        .stat-value {
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 12px;
            color: var(--text-muted);
            text-transform: uppercase;
        }

        /* Cards */
        .card {
            background: var(--bg-card);
            border-radius: 12px;
            border: 1px solid var(--border);
            margin-bottom: 20px;
            overflow: hidden;
        }

        .card-header {
            padding: 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header h3 {
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-body {
            padding: 20px;
        }

        /* Table */
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th,
        .data-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }

        .data-table th {
            background: var(--bg-lighter);
            font-size: 11px;
            text-transform: uppercase;
            color: var(--text-muted);
            font-weight: 600;
        }

        .data-table tr:hover {
            background: var(--bg-lighter);
        }

        /* Badges */
        .badge {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-critical { background: var(--danger); color: white; }
        .badge-high { background: var(--warning); color: #1a1a2e; }
        .badge-medium { background: var(--info); color: white; }
        .badge-low { background: var(--success); color: white; }
        .badge-active { background: var(--danger); color: white; animation: blink 1s infinite; }
        .badge-investigating { background: var(--warning); color: #1a1a2e; }
        .badge-resolved { background: var(--success); color: white; }

        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }

        /* Buttons */
        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            border: none;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-dark); }
        .btn-secondary { background: var(--bg-lighter); color: var(--text); border: 1px solid var(--border); }
        .btn-secondary:hover { background: var(--border); }
        .btn-danger { background: var(--danger); color: white; }
        .btn-danger:hover { background: #dc2626; }
        .btn-warning { background: var(--warning); color: #1a1a2e; }
        .btn-sm { padding: 6px 12px; font-size: 12px; }

        /* Grid Layouts */
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .grid-3 {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }

        /* Source Cards */
        .source-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 15px;
        }

        .source-card {
            background: var(--bg-lighter);
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            border: 1px solid var(--border);
        }

        .source-card.has-findings {
            border-color: var(--danger);
            background: linear-gradient(135deg, var(--bg-lighter), rgba(239,68,68,0.1));
        }

        .source-icon {
            font-size: 24px;
            margin-bottom: 10px;
        }

        .source-name {
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .source-type {
            font-size: 11px;
            color: var(--text-muted);
            margin-bottom: 10px;
        }

        .source-findings {
            font-size: 20px;
            font-weight: bold;
            color: var(--danger);
        }

        /* Timeline */
        .timeline {
            position: relative;
            padding-left: 30px;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 8px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: var(--border);
        }

        .timeline-item {
            position: relative;
            padding-bottom: 20px;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -26px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--danger);
            border: 2px solid var(--bg-dark);
        }

        .timeline-item.high::before { background: var(--warning); }
        .timeline-item.medium::before { background: var(--info); }

        .timeline-date {
            font-size: 11px;
            color: var(--text-muted);
            margin-bottom: 5px;
        }

        .timeline-event {
            font-size: 13px;
        }

        /* Exposure Detail */
        .exposure-row {
            background: var(--bg-lighter);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            border-left: 4px solid var(--danger);
        }

        .exposure-row.high { border-left-color: var(--warning); }
        .exposure-row.resolved { border-left-color: var(--success); opacity: 0.7; }

        .exposure-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }

        .exposure-email {
            font-size: 16px;
            font-weight: 600;
            color: var(--primary);
        }

        .exposure-meta {
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 5px;
        }

        .exposure-details {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
        }

        .exposure-detail {
            background: var(--bg-dark);
            padding: 12px;
            border-radius: 6px;
        }

        .exposure-detail-label {
            font-size: 10px;
            color: var(--text-muted);
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .exposure-detail-value {
            font-size: 13px;
            font-weight: 500;
        }

        .data-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-top: 10px;
        }

        .data-tag {
            padding: 4px 8px;
            background: rgba(255,0,85,0.2);
            border-radius: 4px;
            font-size: 11px;
            color: var(--primary);
        }

        .data-tag.password {
            background: rgba(239,68,68,0.3);
            color: #ff6b6b;
            animation: blink 1s infinite;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 1000;
        }

        .modal.active { display: block; }

        .modal-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.8);
        }

        .modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: var(--bg-card);
            border-radius: 12px;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
            border: 1px solid var(--primary);
        }

        .modal-header {
            padding: 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .modal-close {
            background: none;
            border: none;
            color: var(--text-muted);
            font-size: 24px;
            cursor: pointer;
        }

        .modal-close:hover { color: var(--text); }

        .modal-body { padding: 20px; }
        .modal-footer {
            padding: 20px;
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        /* Toast */
        .toast {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: var(--success);
            color: white;
            padding: 15px 25px;
            border-radius: 8px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.3);
            transform: translateY(100px);
            opacity: 0;
            transition: all 0.3s;
            z-index: 1001;
        }

        .toast.show { transform: translateY(0); opacity: 1; }
        .toast.error { background: var(--danger); }
        .toast.warning { background: var(--warning); color: #1a1a2e; }
        .toast.info { background: var(--info); }

        /* Chart Container */
        .chart-container {
            position: relative;
            height: 250px;
        }

        /* Form */
        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 13px;
            color: var(--text-muted);
        }

        .form-input {
            width: 100%;
            padding: 12px;
            background: var(--bg-lighter);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text);
            font-size: 14px;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary);
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <h1>
                <span>🕵️</span> Dark Web Monitoring
                <span style="font-size: 12px; background: var(--danger); padding: 4px 10px; border-radius: 4px; margin-left: 10px; animation: blink 1s infinite;">LIVE MONITORING</span>
            </h1>
            <div class="header-actions">
                <button class="btn btn-secondary" onclick="refreshScan()">🔄 Refresh Scan</button>
                <button class="btn btn-primary" onclick="openAddDomainModal()">+ Add Domain to Monitor</button>
                <a href="index.php" class="back-link">← Back to Dashboard</a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Alert Banner -->
        <div class="alert-banner">
            <div class="alert-banner-content">
                <h2>⚠️ CRITICAL ALERT: Company Credentials Found on Dark Web</h2>
                <p>10 company email addresses have been discovered on dark web sites and underground forums. Immediate action required.</p>
            </div>
            <div class="alert-banner-stats">
                <div class="alert-stat">
                    <div class="alert-stat-value">6</div>
                    <div class="alert-stat-label">Critical Exposures</div>
                </div>
                <div class="alert-stat">
                    <div class="alert-stat-value">4</div>
                    <div class="alert-stat-label">Plaintext Passwords</div>
                </div>
                <div class="alert-stat">
                    <div class="alert-stat-value">3</div>
                    <div class="alert-stat-label">Last 24 Hours</div>
                </div>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card critical">
                <div class="stat-value" style="color: var(--danger);">10</div>
                <div class="stat-label">Total Exposures</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="color: var(--danger);">6</div>
                <div class="stat-label">Active Threats</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="color: var(--warning);">2</div>
                <div class="stat-label">Investigating</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="color: var(--success);">2</div>
                <div class="stat-label">Resolved</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="color: var(--purple);">10</div>
                <div class="stat-label">Sources Monitored</div>
            </div>
        </div>

        <!-- Monitored Sources -->
        <div class="card">
            <div class="card-header">
                <h3>🌐 Dark Web Sources Being Monitored</h3>
                <span style="font-size: 12px; color: var(--text-muted);">Last full scan: <?= date('Y-m-d H:i:s') ?></span>
            </div>
            <div class="card-body">
                <div class="source-grid">
                    <?php foreach ($monitoredSources as $source): ?>
                    <div class="source-card <?= $source['findings'] > 0 ? 'has-findings' : '' ?>">
                        <div class="source-icon">
                            <?php
                            $icons = [
                                'Underground Forum' => '💀',
                                'Dark Web Marketplace' => '🛒',
                                'Messaging Platform' => '💬',
                                'Hacking Forum' => '👾',
                                'Leak Sites' => '📢',
                                'Paste Monitoring' => '📋',
                                'Code Repositories' => '💻',
                                'Breach Database' => '🗄️'
                            ];
                            echo $icons[$source['type']] ?? '🔍';
                            ?>
                        </div>
                        <div class="source-name"><?= htmlspecialchars($source['name']) ?></div>
                        <div class="source-type"><?= htmlspecialchars($source['type']) ?></div>
                        <?php if ($source['findings'] > 0): ?>
                        <div class="source-findings"><?= $source['findings'] ?> found</div>
                        <?php else: ?>
                        <div style="color: var(--success); font-size: 12px;">✓ Clear</div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Main Grid -->
        <div class="grid-3">
            <!-- Exposed Emails List -->
            <div>
                <div class="card">
                    <div class="card-header">
                        <h3>📧 Exposed Email Addresses</h3>
                        <div style="display: flex; gap: 10px;">
                            <select class="form-input" style="width: 150px; padding: 8px;" onchange="filterExposures(this.value)">
                                <option value="all">All Status</option>
                                <option value="active">Active</option>
                                <option value="investigating">Investigating</option>
                                <option value="resolved">Resolved</option>
                            </select>
                            <button class="btn btn-sm btn-secondary" onclick="exportExposures()">📥 Export</button>
                        </div>
                    </div>
                    <div class="card-body" style="padding: 15px;">
                        <?php foreach ($darkWebExposures as $exposure): ?>
                        <div class="exposure-row <?= $exposure['severity'] === 'high' ? 'high' : '' ?> <?= $exposure['status'] === 'resolved' ? 'resolved' : '' ?>">
                            <div class="exposure-header">
                                <div>
                                    <div class="exposure-email"><?= htmlspecialchars($exposure['email']) ?></div>
                                    <div class="exposure-meta">
                                        <?= htmlspecialchars($exposure['employee_name']) ?> • <?= htmlspecialchars($exposure['department']) ?> •
                                        Discovered: <?= date('M j, Y', strtotime($exposure['discovered_date'])) ?>
                                    </div>
                                </div>
                                <div style="display: flex; gap: 8px; align-items: center;">
                                    <span class="badge badge-<?= $exposure['severity'] ?>"><?= strtoupper($exposure['severity']) ?></span>
                                    <span class="badge badge-<?= $exposure['status'] ?>"><?= strtoupper($exposure['status']) ?></span>
                                    <button class="btn btn-sm btn-secondary" onclick="viewExposureDetails('<?= $exposure['id'] ?>')">Details</button>
                                </div>
                            </div>
                            <div class="exposure-details">
                                <div class="exposure-detail">
                                    <div class="exposure-detail-label">Source</div>
                                    <div class="exposure-detail-value"><?= htmlspecialchars($exposure['source']) ?></div>
                                </div>
                                <div class="exposure-detail">
                                    <div class="exposure-detail-label">Breach Name</div>
                                    <div class="exposure-detail-value"><?= htmlspecialchars($exposure['breach_name']) ?></div>
                                </div>
                                <div class="exposure-detail">
                                    <div class="exposure-detail-label">Records in Breach</div>
                                    <div class="exposure-detail-value"><?= htmlspecialchars($exposure['records_in_breach']) ?></div>
                                </div>
                                <div class="exposure-detail">
                                    <div class="exposure-detail-label">Listed Price</div>
                                    <div class="exposure-detail-value" style="color: var(--danger);"><?= htmlspecialchars($exposure['price_listed']) ?></div>
                                </div>
                            </div>
                            <div class="data-tags">
                                <span style="font-size: 11px; color: var(--text-muted); margin-right: 5px;">Data Exposed:</span>
                                <?php foreach ($exposure['data_exposed'] as $data): ?>
                                <span class="data-tag <?= strpos(strtolower($data), 'password') !== false && $exposure['password_plain'] ? 'password' : '' ?>">
                                    <?= htmlspecialchars($data) ?>
                                </span>
                                <?php endforeach; ?>
                            </div>
                            <?php if ($exposure['status'] === 'active'): ?>
                            <div style="margin-top: 15px; display: flex; gap: 10px;">
                                <button class="btn btn-sm btn-danger" onclick="forcePasswordReset('<?= $exposure['email'] ?>')">Force Password Reset</button>
                                <button class="btn btn-sm btn-warning" onclick="notifyUser('<?= $exposure['email'] ?>')">Notify User</button>
                                <button class="btn btn-sm btn-secondary" onclick="markInvestigating('<?= $exposure['id'] ?>')">Mark Investigating</button>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div>
                <!-- Breach Timeline -->
                <div class="card">
                    <div class="card-header">
                        <h3>📅 Recent Discovery Timeline</h3>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            <?php foreach ($breachTimeline as $event): ?>
                            <div class="timeline-item <?= $event['severity'] ?>">
                                <div class="timeline-date"><?= $event['date'] ?></div>
                                <div class="timeline-event"><?= htmlspecialchars($event['event']) ?></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Exposure by Type Chart -->
                <div class="card">
                    <div class="card-header">
                        <h3>📊 Exposure by Type</h3>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="exposureTypeChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card">
                    <div class="card-header">
                        <h3>⚡ Quick Actions</h3>
                    </div>
                    <div class="card-body">
                        <div style="display: grid; gap: 10px;">
                            <button class="btn btn-danger" onclick="forceAllPasswordReset()" style="width: 100%; justify-content: center;">
                                🔑 Force Reset All Exposed Passwords
                            </button>
                            <button class="btn btn-warning" onclick="notifyAllUsers()" style="width: 100%; justify-content: center;">
                                📧 Notify All Affected Users
                            </button>
                            <button class="btn btn-secondary" onclick="generateExecutiveReport()" style="width: 100%; justify-content: center;">
                                📋 Generate Executive Report
                            </button>
                            <button class="btn btn-secondary" onclick="scheduleSecurityTraining()" style="width: 100%; justify-content: center;">
                                🎓 Schedule Security Training
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Table -->
        <div class="card">
            <div class="card-header">
                <h3>📋 Complete Exposure Report</h3>
                <button class="btn btn-sm btn-secondary" onclick="exportFullReport()">📥 Export Full Report</button>
            </div>
            <div class="card-body" style="padding: 0; overflow-x: auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Email</th>
                            <th>Employee</th>
                            <th>Department</th>
                            <th>Source</th>
                            <th>Exposure Type</th>
                            <th>Breach Date</th>
                            <th>Password Exposed</th>
                            <th>Severity</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($darkWebExposures as $exposure): ?>
                        <tr>
                            <td><strong><?= $exposure['id'] ?></strong></td>
                            <td style="color: var(--primary);"><?= htmlspecialchars($exposure['email']) ?></td>
                            <td><?= htmlspecialchars($exposure['employee_name']) ?></td>
                            <td><?= htmlspecialchars($exposure['department']) ?></td>
                            <td><?= htmlspecialchars($exposure['source']) ?></td>
                            <td><?= htmlspecialchars($exposure['exposure_type']) ?></td>
                            <td><?= $exposure['breach_date'] ?></td>
                            <td>
                                <?php if ($exposure['password_plain']): ?>
                                <span class="badge badge-critical" style="animation: blink 1s infinite;">PLAINTEXT!</span>
                                <?php else: ?>
                                <span class="badge badge-medium">Hash Only</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge badge-<?= $exposure['severity'] ?>"><?= strtoupper($exposure['severity']) ?></span></td>
                            <td><span class="badge badge-<?= $exposure['status'] ?>"><?= strtoupper($exposure['status']) ?></span></td>
                            <td>
                                <button class="btn btn-sm btn-secondary" onclick="viewExposureDetails('<?= $exposure['id'] ?>')">View</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Exposure Details Modal -->
    <div id="exposureModal" class="modal">
        <div class="modal-overlay" onclick="closeModal('exposureModal')"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h3>🔍 Exposure Details - <span id="modalExposureId"></span></h3>
                <button class="modal-close" onclick="closeModal('exposureModal')">&times;</button>
            </div>
            <div class="modal-body" id="exposureModalBody">
                <!-- Dynamic content -->
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('exposureModal')">Close</button>
                <button class="btn btn-warning" onclick="notifyUserFromModal()">Notify User</button>
                <button class="btn btn-danger" onclick="forceResetFromModal()">Force Password Reset</button>
            </div>
        </div>
    </div>

    <!-- Add Domain Modal -->
    <div id="addDomainModal" class="modal">
        <div class="modal-overlay" onclick="closeModal('addDomainModal')"></div>
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h3>➕ Add Domain to Monitor</h3>
                <button class="modal-close" onclick="closeModal('addDomainModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Domain Name</label>
                    <input type="text" id="newDomain" class="form-input" placeholder="e.g., company.com">
                </div>
                <div class="form-group">
                    <label>Monitoring Level</label>
                    <select id="monitorLevel" class="form-input">
                        <option value="standard">Standard - Daily scans</option>
                        <option value="enhanced">Enhanced - Hourly scans</option>
                        <option value="realtime">Real-time - Continuous monitoring</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Alert Recipients</label>
                    <input type="text" id="alertRecipients" class="form-input" placeholder="security@company.com, ciso@company.com">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('addDomainModal')">Cancel</button>
                <button class="btn btn-primary" onclick="addDomain()">Add Domain</button>
            </div>
        </div>
    </div>

    <!-- Force Reset All Passwords Modal -->
    <div id="forceResetAllModal" class="modal">
        <div class="modal-overlay" onclick="closeModal('forceResetAllModal')"></div>
        <div class="modal-content" style="max-width: 700px;">
            <div class="modal-header" style="background: linear-gradient(135deg, rgba(239,68,68,0.2), rgba(255,0,85,0.1));">
                <h3>🔑 Force Password Reset - All Exposed Users</h3>
                <button class="modal-close" onclick="closeModal('forceResetAllModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div style="background: rgba(239,68,68,0.1); border: 1px solid var(--danger); padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <strong style="color: var(--danger);">⚠️ Warning: This is a significant security action</strong>
                    <p style="margin-top: 8px; font-size: 13px; color: var(--text-muted);">
                        This will force password reset for all users with active exposures. Users will be logged out of all sessions immediately.
                    </p>
                </div>

                <h4 style="margin-bottom: 15px;">Affected Users (<span id="resetUserCount">0</span>)</h4>
                <div id="resetUserList" style="max-height: 200px; overflow-y: auto; background: var(--bg-lighter); border-radius: 8px; padding: 15px;">
                    <!-- User list populated by JavaScript -->
                </div>

                <div class="form-group" style="margin-top: 20px;">
                    <label>Reset Options</label>
                    <div style="display: grid; gap: 10px;">
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                            <input type="checkbox" id="resetInvalidateSessions" checked style="width: 18px; height: 18px;">
                            <span>Invalidate all active sessions immediately</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                            <input type="checkbox" id="resetRequireMFA" checked style="width: 18px; height: 18px;">
                            <span>Require MFA re-enrollment</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                            <input type="checkbox" id="resetNotifyManager" checked style="width: 18px; height: 18px;">
                            <span>Notify managers of affected users</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                            <input type="checkbox" id="resetAuditLog" checked style="width: 18px; height: 18px;">
                            <span>Create audit log entry</span>
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label>Password Reset Reason (for audit)</label>
                    <textarea id="resetReason" class="form-input" rows="3" placeholder="Enter reason for mass password reset...">Dark web exposure detected - credentials found on underground forums. Immediate reset required per security policy.</textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('forceResetAllModal')">Cancel</button>
                <button class="btn btn-danger" onclick="confirmForceResetAll()">🔑 Force Reset All Passwords</button>
            </div>
        </div>
    </div>

    <!-- Notify All Users Modal -->
    <div id="notifyAllModal" class="modal">
        <div class="modal-overlay" onclick="closeModal('notifyAllModal')"></div>
        <div class="modal-content" style="max-width: 700px;">
            <div class="modal-header" style="background: linear-gradient(135deg, rgba(245,158,11,0.2), rgba(255,0,85,0.1));">
                <h3>📧 Notify All Affected Users</h3>
                <button class="modal-close" onclick="closeModal('notifyAllModal')">&times;</button>
            </div>
            <div class="modal-body">
                <h4 style="margin-bottom: 15px;">Recipients (<span id="notifyUserCount">0</span> users)</h4>
                <div id="notifyUserList" style="max-height: 150px; overflow-y: auto; background: var(--bg-lighter); border-radius: 8px; padding: 15px; margin-bottom: 20px;">
                    <!-- User list populated by JavaScript -->
                </div>

                <div class="form-group">
                    <label>Notification Template</label>
                    <select id="notifyTemplate" class="form-input" onchange="loadNotificationTemplate()">
                        <option value="security_alert">Security Alert - Credential Exposure</option>
                        <option value="password_change">Password Change Required</option>
                        <option value="security_awareness">Security Awareness Notice</option>
                        <option value="custom">Custom Message</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Email Subject</label>
                    <input type="text" id="notifySubject" class="form-input" value="[URGENT] Security Alert: Your Credentials May Be Compromised">
                </div>

                <div class="form-group">
                    <label>Email Message</label>
                    <textarea id="notifyMessage" class="form-input" rows="8">Dear Employee,

Our security monitoring systems have detected that your company email credentials may have been exposed in a data breach on the dark web.

IMMEDIATE ACTIONS REQUIRED:
1. Change your password immediately
2. Enable Multi-Factor Authentication (MFA) if not already active
3. Review your recent account activity for any suspicious actions
4. Do not use this password on any other services

If you have any questions, please contact the IT Security team immediately.

Best regards,
IT Security Team</textarea>
                </div>

                <div class="form-group">
                    <label>Additional Options</label>
                    <div style="display: grid; gap: 10px;">
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                            <input type="checkbox" id="notifyCCManager" checked style="width: 18px; height: 18px;">
                            <span>CC direct managers</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                            <input type="checkbox" id="notifyCCSecurity" checked style="width: 18px; height: 18px;">
                            <span>CC Security team</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                            <input type="checkbox" id="notifyHighPriority" checked style="width: 18px; height: 18px;">
                            <span>Mark as high priority</span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('notifyAllModal')">Cancel</button>
                <button class="btn btn-warning" onclick="confirmNotifyAll()">📧 Send Notifications</button>
            </div>
        </div>
    </div>

    <!-- Security Training Modal -->
    <div id="securityTrainingModal" class="modal">
        <div class="modal-overlay" onclick="closeModal('securityTrainingModal')"></div>
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header" style="background: linear-gradient(135deg, rgba(139,92,246,0.2), rgba(59,130,246,0.1));">
                <h3>🎓 Schedule Security Awareness Training</h3>
                <button class="modal-close" onclick="closeModal('securityTrainingModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div style="background: rgba(139,92,246,0.1); padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <strong style="color: var(--purple);">📚 Training Recommendation</strong>
                    <p style="margin-top: 8px; font-size: 13px; color: var(--text-muted);">
                        Based on the current exposures, we recommend scheduling mandatory security awareness training for all affected employees.
                    </p>
                </div>

                <div class="form-group">
                    <label>Training Type</label>
                    <select id="trainingType" class="form-input">
                        <option value="password_security">Password Security Best Practices</option>
                        <option value="phishing_awareness">Phishing Awareness</option>
                        <option value="dark_web_risks">Dark Web Risks & Prevention</option>
                        <option value="comprehensive">Comprehensive Security Training</option>
                        <option value="incident_response">Incident Response Basics</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Training Format</label>
                    <select id="trainingFormat" class="form-input">
                        <option value="online">Online Self-Paced Course</option>
                        <option value="webinar">Live Webinar</option>
                        <option value="in_person">In-Person Workshop</option>
                        <option value="video">Video Series</option>
                    </select>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>Scheduled Date</label>
                        <input type="date" id="trainingDate" class="form-input" value="<?= date('Y-m-d', strtotime('+7 days')) ?>">
                    </div>
                    <div class="form-group">
                        <label>Deadline to Complete</label>
                        <input type="date" id="trainingDeadline" class="form-input" value="<?= date('Y-m-d', strtotime('+14 days')) ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Target Audience</label>
                    <div style="display: grid; gap: 10px;">
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                            <input type="radio" name="trainingAudience" value="affected" checked style="width: 18px; height: 18px;">
                            <span>Affected users only (<span id="affectedCount">0</span> users)</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                            <input type="radio" name="trainingAudience" value="department" style="width: 18px; height: 18px;">
                            <span>Entire departments of affected users</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                            <input type="radio" name="trainingAudience" value="company" style="width: 18px; height: 18px;">
                            <span>Company-wide training</span>
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label>Additional Notes</label>
                    <textarea id="trainingNotes" class="form-input" rows="3" placeholder="Optional notes for training coordinator..."></textarea>
                </div>

                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                        <input type="checkbox" id="trainingMandatory" checked style="width: 18px; height: 18px;">
                        <span>Make training mandatory (track completion)</span>
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('securityTrainingModal')">Cancel</button>
                <button class="btn btn-primary" style="background: var(--purple);" onclick="confirmScheduleTraining()">🎓 Schedule Training</button>
            </div>
        </div>
    </div>

    <!-- Executive Report Modal -->
    <div id="executiveReportModal" class="modal">
        <div class="modal-overlay" onclick="closeModal('executiveReportModal')"></div>
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h3>📋 Generate Executive Report</h3>
                <button class="modal-close" onclick="closeModal('executiveReportModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Report Type</label>
                    <select id="reportType" class="form-input">
                        <option value="summary">Executive Summary (1-2 pages)</option>
                        <option value="detailed">Detailed Technical Report</option>
                        <option value="compliance">Compliance Report (GDPR/HIPAA)</option>
                        <option value="incident">Incident Response Report</option>
                        <option value="board">Board Presentation Format</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Report Format</label>
                    <select id="reportFormat" class="form-input">
                        <option value="pdf">PDF Document</option>
                        <option value="txt">Plain Text</option>
                        <option value="csv">CSV Data Export</option>
                        <option value="html">HTML Report</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Include Sections</label>
                    <div style="display: grid; gap: 10px;">
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                            <input type="checkbox" id="includeSummary" checked style="width: 18px; height: 18px;">
                            <span>Executive Summary</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                            <input type="checkbox" id="includeExposures" checked style="width: 18px; height: 18px;">
                            <span>Detailed Exposure List</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                            <input type="checkbox" id="includeTimeline" checked style="width: 18px; height: 18px;">
                            <span>Breach Timeline</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                            <input type="checkbox" id="includeRiskAssessment" checked style="width: 18px; height: 18px;">
                            <span>Risk Assessment</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                            <input type="checkbox" id="includeRecommendations" checked style="width: 18px; height: 18px;">
                            <span>Recommendations</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                            <input type="checkbox" id="includeActionsTaken" style="width: 18px; height: 18px;">
                            <span>Actions Taken Log</span>
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label>Classification Level</label>
                    <select id="reportClassification" class="form-input">
                        <option value="confidential">CONFIDENTIAL</option>
                        <option value="internal">INTERNAL USE ONLY</option>
                        <option value="restricted">RESTRICTED</option>
                        <option value="public">PUBLIC (Redacted)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Report Recipients (optional)</label>
                    <input type="text" id="reportRecipients" class="form-input" placeholder="ciso@company.com, ceo@company.com">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('executiveReportModal')">Cancel</button>
                <button class="btn btn-primary" onclick="generateReport()">📋 Generate Report</button>
            </div>
        </div>
    </div>

    <!-- Individual Force Reset Modal -->
    <div id="forceResetModal" class="modal">
        <div class="modal-overlay" onclick="closeModal('forceResetModal')"></div>
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header" style="background: linear-gradient(135deg, rgba(239,68,68,0.2), rgba(255,0,85,0.1));">
                <h3>🔑 Force Password Reset</h3>
                <button class="modal-close" onclick="closeModal('forceResetModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div id="forceResetUserInfo" style="background: var(--bg-lighter); padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <!-- Populated by JavaScript -->
                </div>

                <div class="form-group">
                    <label>Reset Options</label>
                    <div style="display: grid; gap: 10px;">
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                            <input type="checkbox" id="singleResetSessions" checked style="width: 18px; height: 18px;">
                            <span>Invalidate all active sessions</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                            <input type="checkbox" id="singleResetMFA" style="width: 18px; height: 18px;">
                            <span>Require MFA re-enrollment</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                            <input type="checkbox" id="singleResetNotify" checked style="width: 18px; height: 18px;">
                            <span>Send password reset email to user</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                            <input type="checkbox" id="singleResetManager" style="width: 18px; height: 18px;">
                            <span>Notify user's manager</span>
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label>Reset Reason</label>
                    <textarea id="singleResetReason" class="form-input" rows="2" placeholder="Reason for password reset...">Credentials exposed in dark web breach</textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('forceResetModal')">Cancel</button>
                <button class="btn btn-danger" onclick="confirmSingleReset()">🔑 Reset Password</button>
            </div>
        </div>
    </div>

    <!-- Individual Notify User Modal -->
    <div id="notifyUserModal" class="modal">
        <div class="modal-overlay" onclick="closeModal('notifyUserModal')"></div>
        <div class="modal-content" style="max-width: 550px;">
            <div class="modal-header" style="background: linear-gradient(135deg, rgba(245,158,11,0.2), rgba(255,0,85,0.1));">
                <h3>📧 Send Security Notification</h3>
                <button class="modal-close" onclick="closeModal('notifyUserModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div id="notifyUserInfo" style="background: var(--bg-lighter); padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <!-- Populated by JavaScript -->
                </div>

                <div class="form-group">
                    <label>Subject</label>
                    <input type="text" id="singleNotifySubject" class="form-input" value="[SECURITY] Your Account May Be Compromised">
                </div>

                <div class="form-group">
                    <label>Message</label>
                    <textarea id="singleNotifyMessage" class="form-input" rows="6">Your email credentials have been detected in a data breach. Please change your password immediately and enable MFA if not already active.

For assistance, contact IT Security at security@company.com</textarea>
                </div>

                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                        <input type="checkbox" id="singleNotifyCCManager" style="width: 18px; height: 18px;">
                        <span>CC user's manager</span>
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('notifyUserModal')">Cancel</button>
                <button class="btn btn-warning" onclick="confirmSingleNotify()">📧 Send Notification</button>
            </div>
        </div>
    </div>

    <!-- Mark Investigating Modal -->
    <div id="investigatingModal" class="modal">
        <div class="modal-overlay" onclick="closeModal('investigatingModal')"></div>
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header" style="background: linear-gradient(135deg, rgba(59,130,246,0.2), rgba(139,92,246,0.1));">
                <h3>🔍 Mark as Investigating</h3>
                <button class="modal-close" onclick="closeModal('investigatingModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div id="investigatingInfo" style="background: var(--bg-lighter); padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <!-- Populated by JavaScript -->
                </div>

                <div class="form-group">
                    <label>Assigned Investigator</label>
                    <select id="investigator" class="form-input">
                        <option value="auto">Auto-assign</option>
                        <option value="security_team">Security Team</option>
                        <option value="incident_response">Incident Response Team</option>
                        <option value="soc">SOC Analyst</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Priority Level</label>
                    <select id="investigationPriority" class="form-input">
                        <option value="critical">Critical - Immediate</option>
                        <option value="high">High - Within 4 hours</option>
                        <option value="medium">Medium - Within 24 hours</option>
                        <option value="low">Low - Within 72 hours</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Investigation Notes</label>
                    <textarea id="investigationNotes" class="form-input" rows="3" placeholder="Initial investigation notes..."></textarea>
                </div>

                <div class="form-group">
                    <label>Initial Actions</label>
                    <div style="display: grid; gap: 10px;">
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                            <input type="checkbox" id="invReviewLogs" checked style="width: 18px; height: 18px;">
                            <span>Review user access logs (last 30 days)</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                            <input type="checkbox" id="invCheckMFA" checked style="width: 18px; height: 18px;">
                            <span>Verify MFA status</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                            <input type="checkbox" id="invMonitorAccount" style="width: 18px; height: 18px;">
                            <span>Enable enhanced monitoring</span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('investigatingModal')">Cancel</button>
                <button class="btn btn-primary" onclick="confirmInvestigation()">🔍 Start Investigation</button>
            </div>
        </div>
    </div>

    <!-- Toast -->
    <div id="toast" class="toast"></div>

    <script>
    // Exposure data for JavaScript
    const exposuresData = <?= json_encode($darkWebExposures) ?>;

    let currentExposureId = null;

    function showToast(message, type = 'success') {
        const toast = document.getElementById('toast');
        toast.textContent = message;
        toast.className = 'toast ' + type + ' show';
        setTimeout(() => toast.classList.remove('show'), 4000);
    }

    function openModal(modalId) {
        document.getElementById(modalId).classList.add('active');
    }

    function closeModal(modalId) {
        document.getElementById(modalId).classList.remove('active');
    }

    function viewExposureDetails(id) {
        const exposure = exposuresData.find(e => e.id === id);
        if (!exposure) return;

        currentExposureId = id;
        document.getElementById('modalExposureId').textContent = id;

        const modalBody = document.getElementById('exposureModalBody');
        modalBody.innerHTML = `
            <div style="background: ${exposure.severity === 'critical' ? 'rgba(239,68,68,0.1)' : 'rgba(245,158,11,0.1)'}; padding: 15px; border-radius: 8px; border-left: 4px solid ${exposure.severity === 'critical' ? 'var(--danger)' : 'var(--warning)'}; margin-bottom: 20px;">
                <strong style="color: ${exposure.severity === 'critical' ? 'var(--danger)' : 'var(--warning)'};">
                    ${exposure.severity === 'critical' ? '🚨 CRITICAL EXPOSURE' : '⚠️ HIGH SEVERITY EXPOSURE'}
                </strong>
                <p style="margin-top: 5px; font-size: 13px;">${exposure.password_plain ? 'Password exposed in PLAINTEXT - Immediate action required!' : 'Password hash exposed - Monitor for credential stuffing attacks.'}</p>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <h4 style="margin-bottom: 15px; color: var(--primary);">Employee Information</h4>
                    <div style="background: var(--bg-lighter); padding: 15px; border-radius: 8px;">
                        <div style="margin-bottom: 10px;"><strong>Email:</strong> ${exposure.email}</div>
                        <div style="margin-bottom: 10px;"><strong>Name:</strong> ${exposure.employee_name}</div>
                        <div style="margin-bottom: 10px;"><strong>Department:</strong> ${exposure.department}</div>
                        <div><strong>Status:</strong> <span class="badge badge-${exposure.status}">${exposure.status.toUpperCase()}</span></div>
                    </div>
                </div>
                <div>
                    <h4 style="margin-bottom: 15px; color: var(--primary);">Breach Information</h4>
                    <div style="background: var(--bg-lighter); padding: 15px; border-radius: 8px;">
                        <div style="margin-bottom: 10px;"><strong>Source:</strong> ${exposure.source}</div>
                        <div style="margin-bottom: 10px;"><strong>Source Type:</strong> ${exposure.source_type}</div>
                        <div style="margin-bottom: 10px;"><strong>Breach Name:</strong> ${exposure.breach_name}</div>
                        <div><strong>Breach Date:</strong> ${exposure.breach_date}</div>
                    </div>
                </div>
            </div>

            <div style="margin-bottom: 20px;">
                <h4 style="margin-bottom: 15px; color: var(--primary);">Exposure Details</h4>
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px;">
                    <div style="background: var(--bg-lighter); padding: 15px; border-radius: 8px; text-align: center;">
                        <div style="font-size: 24px; font-weight: bold; color: var(--danger);">${exposure.records_in_breach}</div>
                        <div style="font-size: 11px; color: var(--text-muted);">Records in Breach</div>
                    </div>
                    <div style="background: var(--bg-lighter); padding: 15px; border-radius: 8px; text-align: center;">
                        <div style="font-size: 24px; font-weight: bold; color: var(--warning);">${exposure.price_listed}</div>
                        <div style="font-size: 11px; color: var(--text-muted);">Listed Price</div>
                    </div>
                    <div style="background: var(--bg-lighter); padding: 15px; border-radius: 8px; text-align: center;">
                        <div style="font-size: 24px; font-weight: bold;">${exposure.discovered_date.split(' ')[0]}</div>
                        <div style="font-size: 11px; color: var(--text-muted);">Discovered</div>
                    </div>
                </div>
            </div>

            <div>
                <h4 style="margin-bottom: 15px; color: var(--danger);">⚠️ Data Exposed</h4>
                <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                    ${exposure.data_exposed.map(d => `
                        <span style="padding: 8px 15px; background: ${d.toLowerCase().includes('password') && exposure.password_plain ? 'rgba(239,68,68,0.3)' : 'rgba(255,0,85,0.2)'}; border-radius: 6px; font-size: 13px; color: ${d.toLowerCase().includes('password') && exposure.password_plain ? '#ff6b6b' : 'var(--primary)'}; ${d.toLowerCase().includes('password') && exposure.password_plain ? 'animation: blink 1s infinite;' : ''}">
                            ${d.toLowerCase().includes('password') && exposure.password_plain ? '🔓 ' : ''}${d}
                        </span>
                    `).join('')}
                </div>
            </div>

            ${exposure.password_plain ? `
            <div style="margin-top: 20px; background: rgba(239,68,68,0.1); padding: 15px; border-radius: 8px; border: 1px solid var(--danger);">
                <strong style="color: var(--danger);">🚨 IMMEDIATE ACTIONS REQUIRED:</strong>
                <ul style="margin-top: 10px; margin-left: 20px; font-size: 13px;">
                    <li>Force password reset for this user immediately</li>
                    <li>Check for unauthorized access in the last 30 days</li>
                    <li>Review any MFA bypass attempts</li>
                    <li>Notify the user and their manager</li>
                    <li>Document incident for compliance</li>
                </ul>
            </div>
            ` : ''}
        `;

        openModal('exposureModal');
    }

    // Track current user for individual modals
    let currentResetEmail = null;
    let currentNotifyEmail = null;
    let currentInvestigatingId = null;

    // Individual Force Password Reset
    function forcePasswordReset(email) {
        currentResetEmail = email;
        const exposure = exposuresData.find(e => e.email === email);

        document.getElementById('forceResetUserInfo').innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <div style="font-size: 16px; font-weight: 600; color: var(--primary);">${email}</div>
                    <div style="font-size: 13px; color: var(--text-muted); margin-top: 5px;">
                        ${exposure ? exposure.employee_name + ' • ' + exposure.department : 'Unknown User'}
                    </div>
                </div>
                <span class="badge badge-${exposure ? exposure.severity : 'high'}">${exposure ? exposure.severity.toUpperCase() : 'HIGH'}</span>
            </div>
            ${exposure ? `<div style="margin-top: 10px; font-size: 12px;"><strong>Exposure Source:</strong> ${exposure.source}</div>` : ''}
        `;

        openModal('forceResetModal');
    }

    function confirmSingleReset() {
        if (!currentResetEmail) return;

        const options = {
            invalidateSessions: document.getElementById('singleResetSessions').checked,
            requireMFA: document.getElementById('singleResetMFA').checked,
            notifyUser: document.getElementById('singleResetNotify').checked,
            notifyManager: document.getElementById('singleResetManager').checked,
            reason: document.getElementById('singleResetReason').value
        };

        closeModal('forceResetModal');
        showToast(`Password reset initiated for ${currentResetEmail}`, 'warning');

        // Update UI to show action was taken
        setTimeout(() => {
            showToast(`${currentResetEmail} has been logged out of all sessions`, 'info');
        }, 1500);

        currentResetEmail = null;
    }

    function forceResetFromModal() {
        const exposure = exposuresData.find(e => e.id === currentExposureId);
        if (exposure) {
            closeModal('exposureModal');
            forcePasswordReset(exposure.email);
        }
    }

    // Individual Notify User
    function notifyUser(email) {
        currentNotifyEmail = email;
        const exposure = exposuresData.find(e => e.email === email);

        document.getElementById('notifyUserInfo').innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <div style="font-size: 16px; font-weight: 600; color: var(--primary);">${email}</div>
                    <div style="font-size: 13px; color: var(--text-muted); margin-top: 5px;">
                        ${exposure ? exposure.employee_name + ' • ' + exposure.department : 'Unknown User'}
                    </div>
                </div>
                <span class="badge badge-${exposure ? exposure.severity : 'high'}">${exposure ? exposure.severity.toUpperCase() : 'HIGH'}</span>
            </div>
            ${exposure ? `
            <div style="margin-top: 10px; font-size: 12px;">
                <strong>Data Exposed:</strong> ${exposure.data_exposed.join(', ')}
            </div>
            ` : ''}
        `;

        openModal('notifyUserModal');
    }

    function confirmSingleNotify() {
        if (!currentNotifyEmail) return;

        const subject = document.getElementById('singleNotifySubject').value;
        const message = document.getElementById('singleNotifyMessage').value;
        const ccManager = document.getElementById('singleNotifyCCManager').checked;

        closeModal('notifyUserModal');
        showToast(`Security notification sent to ${currentNotifyEmail}`);

        if (ccManager) {
            setTimeout(() => {
                showToast(`Manager also notified`, 'info');
            }, 1000);
        }

        currentNotifyEmail = null;
    }

    function notifyUserFromModal() {
        const exposure = exposuresData.find(e => e.id === currentExposureId);
        if (exposure) {
            closeModal('exposureModal');
            notifyUser(exposure.email);
        }
    }

    // Mark as Investigating
    function markInvestigating(id) {
        currentInvestigatingId = id;
        const exposure = exposuresData.find(e => e.id === id);

        document.getElementById('investigatingInfo').innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <div style="font-size: 14px; font-weight: 600;">Exposure ID: ${id}</div>
                    <div style="font-size: 16px; color: var(--primary); margin-top: 5px;">${exposure ? exposure.email : 'Unknown'}</div>
                    <div style="font-size: 12px; color: var(--text-muted); margin-top: 3px;">
                        ${exposure ? exposure.employee_name + ' • ' + exposure.source : ''}
                    </div>
                </div>
                <span class="badge badge-${exposure ? exposure.severity : 'high'}">${exposure ? exposure.severity.toUpperCase() : 'HIGH'}</span>
            </div>
        `;

        // Set priority based on severity
        if (exposure) {
            document.getElementById('investigationPriority').value = exposure.severity === 'critical' ? 'critical' : 'high';
        }

        openModal('investigatingModal');
    }

    function confirmInvestigation() {
        if (!currentInvestigatingId) return;

        const investigator = document.getElementById('investigator').value;
        const priority = document.getElementById('investigationPriority').value;
        const notes = document.getElementById('investigationNotes').value;

        closeModal('investigatingModal');
        showToast(`Investigation started for ${currentInvestigatingId}`, 'info');

        // Visual update
        setTimeout(() => {
            const exposure = exposuresData.find(e => e.id === currentInvestigatingId);
            if (exposure) {
                exposure.status = 'investigating';
                showToast(`Assigned to ${investigator === 'auto' ? 'Security Team' : investigator.replace('_', ' ')}`, 'info');
            }
        }, 1000);

        currentInvestigatingId = null;
    }

    // Force Reset All Passwords
    function forceAllPasswordReset() {
        const activeExposures = exposuresData.filter(e => e.status === 'active');

        document.getElementById('resetUserCount').textContent = activeExposures.length;

        let userListHTML = '';
        activeExposures.forEach(e => {
            userListHTML += `
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid var(--border);">
                    <div>
                        <div style="font-weight: 500;">${e.email}</div>
                        <div style="font-size: 11px; color: var(--text-muted);">${e.employee_name} • ${e.department}</div>
                    </div>
                    <div style="display: flex; gap: 5px;">
                        <span class="badge badge-${e.severity}">${e.severity.toUpperCase()}</span>
                        ${e.password_plain ? '<span class="badge badge-critical">PLAINTEXT</span>' : ''}
                    </div>
                </div>
            `;
        });
        document.getElementById('resetUserList').innerHTML = userListHTML || '<p style="color: var(--text-muted);">No active exposures</p>';

        openModal('forceResetAllModal');
    }

    function confirmForceResetAll() {
        const activeCount = exposuresData.filter(e => e.status === 'active').length;
        const options = {
            invalidateSessions: document.getElementById('resetInvalidateSessions').checked,
            requireMFA: document.getElementById('resetRequireMFA').checked,
            notifyManagers: document.getElementById('resetNotifyManager').checked,
            auditLog: document.getElementById('resetAuditLog').checked,
            reason: document.getElementById('resetReason').value
        };

        closeModal('forceResetAllModal');
        showToast(`Processing password reset for ${activeCount} users...`, 'info');

        setTimeout(() => {
            showToast(`Password reset completed for ${activeCount} users`, 'warning');
        }, 2000);

        if (options.notifyManagers) {
            setTimeout(() => {
                showToast(`All managers have been notified`, 'info');
            }, 3000);
        }
    }

    // Notify All Users
    function notifyAllUsers() {
        const activeExposures = exposuresData.filter(e => e.status === 'active');

        document.getElementById('notifyUserCount').textContent = activeExposures.length;

        let userListHTML = '';
        activeExposures.forEach(e => {
            userListHTML += `
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 6px 0; border-bottom: 1px solid var(--border);">
                    <span>${e.email}</span>
                    <span class="badge badge-${e.severity}" style="font-size: 10px;">${e.severity.toUpperCase()}</span>
                </div>
            `;
        });
        document.getElementById('notifyUserList').innerHTML = userListHTML || '<p style="color: var(--text-muted);">No active exposures</p>';

        openModal('notifyAllModal');
    }

    function loadNotificationTemplate() {
        const template = document.getElementById('notifyTemplate').value;
        const subjectField = document.getElementById('notifySubject');
        const messageField = document.getElementById('notifyMessage');

        const templates = {
            security_alert: {
                subject: '[URGENT] Security Alert: Your Credentials May Be Compromised',
                message: `Dear Employee,

Our security monitoring systems have detected that your company email credentials may have been exposed in a data breach on the dark web.

IMMEDIATE ACTIONS REQUIRED:
1. Change your password immediately
2. Enable Multi-Factor Authentication (MFA) if not already active
3. Review your recent account activity for any suspicious actions
4. Do not use this password on any other services

If you have any questions, please contact the IT Security team immediately.

Best regards,
IT Security Team`
            },
            password_change: {
                subject: '[ACTION REQUIRED] Mandatory Password Change',
                message: `Dear Employee,

Due to a security incident, you are required to change your password immediately.

Please follow these steps:
1. Go to the password reset portal
2. Create a new strong password (12+ characters, mixed case, numbers, symbols)
3. Do not reuse any previous passwords

Your account will be locked in 24 hours if the password is not changed.

IT Security Team`
            },
            security_awareness: {
                subject: 'Security Awareness: Protecting Your Credentials',
                message: `Dear Employee,

We are writing to inform you about credential security best practices.

TIPS FOR STAYING SECURE:
• Use unique passwords for each service
• Enable MFA wherever possible
• Never share your passwords
• Be cautious of phishing emails
• Report suspicious activity immediately

Stay vigilant!
IT Security Team`
            },
            custom: {
                subject: 'Security Notice',
                message: 'Enter your custom message here...'
            }
        };

        if (templates[template]) {
            subjectField.value = templates[template].subject;
            messageField.value = templates[template].message;
        }
    }

    function confirmNotifyAll() {
        const activeCount = exposuresData.filter(e => e.status === 'active').length;
        const subject = document.getElementById('notifySubject').value;
        const ccManager = document.getElementById('notifyCCManager').checked;
        const ccSecurity = document.getElementById('notifyCCSecurity').checked;
        const highPriority = document.getElementById('notifyHighPriority').checked;

        closeModal('notifyAllModal');
        showToast(`Sending notifications to ${activeCount} users...`, 'info');

        setTimeout(() => {
            showToast(`${activeCount} security notifications sent successfully`);
        }, 2000);

        if (ccManager) {
            setTimeout(() => {
                showToast(`Managers have been CC'd on notifications`, 'info');
            }, 2500);
        }
    }

    // Generate Executive Report
    function generateExecutiveReport() {
        openModal('executiveReportModal');
    }

    function generateReport() {
        const reportType = document.getElementById('reportType').value;
        const reportFormat = document.getElementById('reportFormat').value;
        const classification = document.getElementById('reportClassification').value;

        closeModal('executiveReportModal');
        showToast('Generating report...', 'info');

        setTimeout(() => {
            let report = '';

            if (document.getElementById('includeSummary').checked) {
                report += `DARK WEB EXPOSURE - EXECUTIVE REPORT\n`;
                report += `=====================================\n\n`;
                report += `Generated: ${new Date().toISOString()}\n`;
                report += `Classification: ${classification.toUpperCase()}\n`;
                report += `Report Type: ${reportType.replace('_', ' ').toUpperCase()}\n\n`;
                report += `EXECUTIVE SUMMARY\n`;
                report += `-----------------\n`;
                report += `Total Exposures Detected: ${exposuresData.length}\n`;
                report += `Critical Severity: ${exposuresData.filter(e => e.severity === 'critical').length}\n`;
                report += `High Severity: ${exposuresData.filter(e => e.severity === 'high').length}\n`;
                report += `Plaintext Passwords Exposed: ${exposuresData.filter(e => e.password_plain).length}\n`;
                report += `Active Threats: ${exposuresData.filter(e => e.status === 'active').length}\n`;
                report += `Under Investigation: ${exposuresData.filter(e => e.status === 'investigating').length}\n`;
                report += `Resolved: ${exposuresData.filter(e => e.status === 'resolved').length}\n\n`;
            }

            if (document.getElementById('includeRiskAssessment').checked) {
                report += `RISK ASSESSMENT\n`;
                report += `---------------\n`;
                report += `Overall Risk Level: CRITICAL\n`;
                report += `Immediate Actions Required: YES\n`;
                report += `Business Impact: HIGH\n`;
                report += `Compliance Impact: Potential GDPR/HIPAA violations\n\n`;
            }

            if (document.getElementById('includeExposures').checked) {
                report += `DETAILED FINDINGS\n`;
                report += `-----------------\n`;
                exposuresData.forEach(e => {
                    report += `\n[${e.id}] ${e.email}\n`;
                    report += `  Employee: ${e.employee_name} (${e.department})\n`;
                    report += `  Severity: ${e.severity.toUpperCase()}\n`;
                    report += `  Source: ${e.source} (${e.source_type})\n`;
                    report += `  Breach Name: ${e.breach_name}\n`;
                    report += `  Data Exposed: ${e.data_exposed.join(', ')}\n`;
                    report += `  Password Plaintext: ${e.password_plain ? 'YES - CRITICAL' : 'No (hash only)'}\n`;
                    report += `  Status: ${e.status.toUpperCase()}\n`;
                    report += `  Discovered: ${e.discovered_date}\n`;
                });
                report += '\n';
            }

            if (document.getElementById('includeRecommendations').checked) {
                report += `RECOMMENDATIONS\n`;
                report += `---------------\n`;
                report += `1. Force password reset for all affected users immediately\n`;
                report += `2. Enable MFA for all exposed accounts\n`;
                report += `3. Review access logs for unauthorized activity\n`;
                report += `4. Schedule security awareness training\n`;
                report += `5. Implement dark web monitoring for early detection\n`;
                report += `6. Review and update password policies\n`;
                report += `7. Consider credential monitoring service expansion\n\n`;
            }

            report += `\n--- END OF REPORT ---\n`;
            report += `Generated by Dark Web Monitoring System\n`;
            report += `${new Date().toISOString()}\n`;

            const blob = new Blob([report], { type: 'text/plain' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `Dark_Web_${reportType}_Report_${classification}_${new Date().toISOString().split('T')[0]}.txt`;
            a.click();
            window.URL.revokeObjectURL(url);
            showToast('Executive report downloaded successfully');
        }, 1500);
    }

    // Schedule Security Training
    function scheduleSecurityTraining() {
        const activeCount = exposuresData.filter(e => e.status === 'active').length;
        document.getElementById('affectedCount').textContent = activeCount;
        openModal('securityTrainingModal');
    }

    function confirmScheduleTraining() {
        const trainingType = document.getElementById('trainingType').value;
        const trainingFormat = document.getElementById('trainingFormat').value;
        const trainingDate = document.getElementById('trainingDate').value;
        const deadline = document.getElementById('trainingDeadline').value;
        const audience = document.querySelector('input[name="trainingAudience"]:checked').value;
        const mandatory = document.getElementById('trainingMandatory').checked;

        closeModal('securityTrainingModal');

        let audienceLabel = '';
        switch(audience) {
            case 'affected': audienceLabel = 'affected users'; break;
            case 'department': audienceLabel = 'affected departments'; break;
            case 'company': audienceLabel = 'all employees'; break;
        }

        showToast(`Scheduling ${trainingType.replace('_', ' ')} training...`, 'info');

        setTimeout(() => {
            showToast(`Training scheduled for ${audienceLabel} on ${trainingDate}`);
        }, 1500);

        if (mandatory) {
            setTimeout(() => {
                showToast(`Training marked as mandatory - completion will be tracked`, 'info');
            }, 2500);
        }
    }

    function exportExposures() {
        showToast('Exporting exposures...', 'info');
        setTimeout(() => {
            let csv = 'ID,Email,Employee,Department,Source,Exposure Type,Breach Date,Password Plaintext,Severity,Status\n';
            exposuresData.forEach(e => {
                csv += `"${e.id}","${e.email}","${e.employee_name}","${e.department}","${e.source}","${e.exposure_type}","${e.breach_date}",${e.password_plain},"${e.severity}","${e.status}"\n`;
            });
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `dark_web_exposures_${new Date().toISOString().split('T')[0]}.csv`;
            a.click();
            window.URL.revokeObjectURL(url);
            showToast('Exposures exported');
        }, 1000);
    }

    function exportFullReport() {
        exportExposures();
    }

    function filterExposures(status) {
        const rows = document.querySelectorAll('.exposure-row');
        rows.forEach(row => {
            if (status === 'all') {
                row.style.display = 'block';
            } else {
                row.style.display = row.classList.contains(status) ||
                    (status === 'active' && !row.classList.contains('resolved') && !row.classList.contains('investigating'))
                    ? 'block' : 'none';
            }
        });
    }

    function refreshScan() {
        showToast('Initiating dark web scan...', 'info');
        setTimeout(() => showToast('Scan complete - No new exposures found'), 3000);
    }

    function openAddDomainModal() {
        openModal('addDomainModal');
    }

    function addDomain() {
        const domain = document.getElementById('newDomain').value;
        if (!domain) {
            showToast('Please enter a domain', 'error');
            return;
        }
        closeModal('addDomainModal');
        showToast(`Domain ${domain} added to monitoring`);
    }

    // Initialize Chart
    document.addEventListener('DOMContentLoaded', function() {
        new Chart(document.getElementById('exposureTypeChart'), {
            type: 'doughnut',
            data: {
                labels: ['Data Breach', 'Credential Leak', 'Stealer Logs', 'Paste Sites', 'Combo Lists', 'Ransomware Leak'],
                datasets: [{
                    data: [2, 2, 1, 1, 1, 1],
                    backgroundColor: [
                        '#ef4444',
                        '#f59e0b',
                        '#8b5cf6',
                        '#3b82f6',
                        '#10b981',
                        '#ff0055'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { color: '#e2e8f0', font: { size: 11 } }
                    }
                }
            }
        });
    });
    </script>
</body>
</html>
