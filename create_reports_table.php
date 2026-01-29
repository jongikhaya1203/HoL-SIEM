<?php
/**
 * Create Reports Table
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'localhost';
$username = 'root';
$password = '';
$database = 'network_security';

try {
    $conn = new mysqli($host, $username, $password, $database);

    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    echo "✓ Connected to database\n\n";

    // Create reports table
    $sql = "CREATE TABLE IF NOT EXISTS reports (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tenant_id INT NOT NULL DEFAULT 1,
        report_type VARCHAR(50) NOT NULL,
        report_name VARCHAR(255) NOT NULL,
        description TEXT,
        generated_by INT,
        file_path VARCHAR(500),
        file_size INT,
        format VARCHAR(20) DEFAULT 'pdf',
        status VARCHAR(20) DEFAULT 'completed',
        parameters TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_tenant (tenant_id),
        INDEX idx_type (report_type),
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    if ($conn->query($sql) === TRUE) {
        echo "✓ Reports table created successfully\n";
    } else {
        throw new Exception("Error creating reports table: " . $conn->error);
    }

    // Insert sample report data
    $sampleReports = [
        [
            'tenant_id' => 1,
            'report_type' => 'vulnerability_summary',
            'report_name' => 'Monthly Vulnerability Summary - January 2025',
            'description' => 'Comprehensive vulnerability assessment report for all scanned assets',
            'format' => 'pdf',
            'status' => 'completed'
        ],
        [
            'tenant_id' => 1,
            'report_type' => 'compliance',
            'report_name' => 'IEC 62443 Compliance Report',
            'description' => 'Industrial cybersecurity compliance status report',
            'format' => 'pdf',
            'status' => 'completed'
        ],
        [
            'tenant_id' => 1,
            'report_type' => 'asset_inventory',
            'report_name' => 'SCADA Asset Inventory',
            'description' => 'Complete inventory of all SCADA devices and systems',
            'format' => 'xlsx',
            'status' => 'completed'
        ],
        [
            'tenant_id' => 1,
            'report_type' => 'incident',
            'report_name' => 'Security Incident Report - Week 2',
            'description' => 'Summary of security incidents and response actions',
            'format' => 'pdf',
            'status' => 'completed'
        ],
        [
            'tenant_id' => 1,
            'report_type' => 'network_scan',
            'report_name' => 'Network Scan Results - OT Network',
            'description' => 'Detailed network scan results for operational technology network',
            'format' => 'pdf',
            'status' => 'processing'
        ]
    ];

    $stmt = $conn->prepare("INSERT INTO reports (tenant_id, report_type, report_name, description, format, status) VALUES (?, ?, ?, ?, ?, ?)");

    foreach ($sampleReports as $report) {
        $stmt->bind_param("isssss",
            $report['tenant_id'],
            $report['report_type'],
            $report['report_name'],
            $report['description'],
            $report['format'],
            $report['status']
        );
        $stmt->execute();
    }

    echo "✓ Sample reports inserted (" . count($sampleReports) . " reports)\n";

    // Create report_schedules table
    $sql = "CREATE TABLE IF NOT EXISTS report_schedules (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tenant_id INT NOT NULL DEFAULT 1,
        report_type VARCHAR(50) NOT NULL,
        report_name VARCHAR(255) NOT NULL,
        frequency VARCHAR(20) NOT NULL,
        schedule_time TIME,
        schedule_day VARCHAR(20),
        recipients TEXT,
        format VARCHAR(20) DEFAULT 'pdf',
        is_active BOOLEAN DEFAULT TRUE,
        last_run TIMESTAMP NULL,
        next_run TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_tenant (tenant_id),
        INDEX idx_active (is_active),
        INDEX idx_next_run (next_run)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    if ($conn->query($sql) === TRUE) {
        echo "✓ Report schedules table created successfully\n";
    } else {
        throw new Exception("Error creating report_schedules table: " . $conn->error);
    }

    echo "\n========================================\n";
    echo "SUCCESS! Reports tables created\n";
    echo "========================================\n";

    $conn->close();

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?>
