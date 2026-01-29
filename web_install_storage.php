<?php
/**
 * Web-based Storage Optimization Installer
 * Access via: http://localhost/networkscan/web_install_storage.php
 */

$installed = false;
$error = null;
$message = '';

if (isset($_POST['install'])) {
    try {
        require_once __DIR__ . '/classes/Database.php';

        $db = Database::getInstance();
        $conn = $db->getConnection();

        $message .= "✓ Database connection successful\n\n";

        // Create tables
        $tables = [
            'file_scans' => "CREATE TABLE IF NOT EXISTS file_scans (
                id INT AUTO_INCREMENT PRIMARY KEY,
                scan_id VARCHAR(50) NOT NULL,
                file_path VARCHAR(1000) NOT NULL,
                file_name VARCHAR(500) NOT NULL,
                file_extension VARCHAR(50),
                file_size_bytes BIGINT NOT NULL,
                file_hash VARCHAR(64) NOT NULL,
                created_date DATETIME,
                modified_date DATETIME,
                accessed_date DATETIME,
                storage_location VARCHAR(200),
                storage_tier VARCHAR(50),
                owner VARCHAR(200),
                department VARCHAR(100),
                is_duplicate BOOLEAN DEFAULT FALSE,
                duplicate_group_id VARCHAR(50),
                duplicate_count INT DEFAULT 0,
                data_classification VARCHAR(50) DEFAULT 'Unclassified',
                sensitivity_level ENUM('Public', 'Internal', 'Confidential', 'Restricted') DEFAULT 'Public',
                retention_period INT DEFAULT 365,
                last_scanned TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_scan_id (scan_id),
                INDEX idx_file_hash (file_hash),
                INDEX idx_duplicate_group (duplicate_group_id),
                INDEX idx_classification (data_classification),
                INDEX idx_is_duplicate (is_duplicate),
                INDEX idx_storage_location (storage_location)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            'duplicate_groups' => "CREATE TABLE IF NOT EXISTS duplicate_groups (
                id INT AUTO_INCREMENT PRIMARY KEY,
                group_id VARCHAR(50) UNIQUE NOT NULL,
                file_hash VARCHAR(64) NOT NULL,
                file_size_bytes BIGINT NOT NULL,
                duplicate_count INT NOT NULL,
                total_wasted_space BIGINT NOT NULL,
                file_type VARCHAR(100),
                first_occurrence_path VARCHAR(1000),
                recommendation TEXT,
                priority ENUM('Low', 'Medium', 'High', 'Critical') DEFAULT 'Medium',
                status ENUM('Detected', 'Reviewing', 'Action Taken', 'Ignored') DEFAULT 'Detected',
                detected_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_group_id (group_id),
                INDEX idx_file_hash (file_hash),
                INDEX idx_priority (priority),
                INDEX idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            'classification_rules' => "CREATE TABLE IF NOT EXISTS classification_rules (
                id INT AUTO_INCREMENT PRIMARY KEY,
                rule_name VARCHAR(200) NOT NULL,
                rule_description TEXT,
                match_type ENUM('extension', 'filename_pattern', 'path_pattern', 'content_pattern', 'size_range') DEFAULT 'extension',
                match_pattern VARCHAR(500) NOT NULL,
                classification VARCHAR(50) NOT NULL,
                sensitivity_level ENUM('Public', 'Internal', 'Confidential', 'Restricted') NOT NULL,
                retention_days INT DEFAULT 365,
                storage_tier_recommendation VARCHAR(50),
                enabled BOOLEAN DEFAULT TRUE,
                priority INT DEFAULT 50,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_enabled (enabled),
                INDEX idx_classification (classification)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            'optimization_recommendations' => "CREATE TABLE IF NOT EXISTS optimization_recommendations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                recommendation_id VARCHAR(50) UNIQUE NOT NULL,
                recommendation_type ENUM('Deduplication', 'Tier Migration', 'Archive', 'Delete', 'Compress') NOT NULL,
                title VARCHAR(300) NOT NULL,
                description TEXT,
                potential_savings_bytes BIGINT DEFAULT 0,
                affected_files_count INT DEFAULT 0,
                priority ENUM('Low', 'Medium', 'High', 'Critical') DEFAULT 'Medium',
                status ENUM('Pending', 'In Progress', 'Completed', 'Dismissed') DEFAULT 'Pending',
                created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                completed_date TIMESTAMP NULL,
                notes TEXT,
                INDEX idx_type (recommendation_type),
                INDEX idx_priority (priority),
                INDEX idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            'storage_tiers' => "CREATE TABLE IF NOT EXISTS storage_tiers (
                id INT AUTO_INCREMENT PRIMARY KEY,
                tier_name VARCHAR(100) UNIQUE NOT NULL,
                tier_level INT NOT NULL,
                description TEXT,
                cost_per_gb_monthly DECIMAL(10,4) DEFAULT 0.00,
                performance_level ENUM('High', 'Medium', 'Low') DEFAULT 'Medium',
                recommended_for TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        ];

        foreach ($tables as $tableName => $sql) {
            $conn->exec($sql);
            $message .= "✓ Created table: $tableName\n";
        }

        $message .= "\n--- Installing Default Data ---\n\n";

        // Check if classification rules exist
        $stmt = $conn->query("SELECT COUNT(*) as count FROM classification_rules");
        $result = $stmt->fetch();

        if ($result['count'] == 0) {
            $message .= "Adding 16 classification rules...\n";

            $rules = [
                ['Financial Documents', 'Excel files in Finance folders', 'path_pattern', '%/Finance/%', 'Financial Data', 'Confidential', 2555, 'Tier 1 - High Performance', 90],
                ['HR Records', 'Documents in HR directory', 'path_pattern', '%/HR/%', 'Human Resources', 'Restricted', 2555, 'Tier 1 - High Performance', 95],
                ['Legal Documents', 'Legal department files', 'path_pattern', '%/Legal/%', 'Legal', 'Restricted', 3650, 'Tier 1 - High Performance', 95],
                ['Customer Data', 'CRM and customer information', 'path_pattern', '%/Customers/%', 'Customer Information', 'Confidential', 1825, 'Tier 1 - High Performance', 85],
                ['Source Code', 'Programming source files', 'extension', '.java|.py|.js|.cpp|.cs|.php', 'Source Code', 'Internal', 1095, 'Tier 2 - Standard', 70],
                ['Database Backups', 'Database backup files', 'extension', '.bak|.sql|.dump', 'Database Backup', 'Confidential', 90, 'Tier 3 - Archive', 80],
                ['Office Documents', 'Microsoft Office files', 'extension', '.docx|.xlsx|.pptx|.doc|.xls|.ppt', 'Office Document', 'Internal', 730, 'Tier 2 - Standard', 50],
                ['PDF Documents', 'PDF files', 'extension', '.pdf', 'PDF Document', 'Internal', 730, 'Tier 2 - Standard', 50],
                ['Image Files', 'Images and graphics', 'extension', '.jpg|.jpeg|.png|.gif|.bmp|.svg', 'Image', 'Public', 365, 'Tier 3 - Archive', 30],
                ['Video Files', 'Video content', 'extension', '.mp4|.avi|.mov|.wmv|.mkv', 'Video', 'Internal', 180, 'Tier 3 - Archive', 40],
                ['Audio Files', 'Audio content', 'extension', '.mp3|.wav|.flac|.aac', 'Audio', 'Public', 180, 'Tier 3 - Archive', 30],
                ['Archive Files', 'Compressed archives', 'extension', '.zip|.rar|.7z|.tar|.gz', 'Archive', 'Internal', 365, 'Tier 3 - Archive', 40],
                ['Log Files', 'System and application logs', 'extension', '.log|.txt', 'Log File', 'Internal', 90, 'Tier 3 - Archive', 20],
                ['Temporary Files', 'Temporary and cache files', 'extension', '.tmp|.temp|.cache', 'Temporary', 'Public', 7, 'Tier 3 - Archive', 10],
                ['Confidential Files', 'Files marked confidential', 'filename_pattern', '%confidential%', 'Confidential Data', 'Confidential', 1825, 'Tier 1 - High Performance', 90],
                ['Backup Files', 'Backup copies', 'filename_pattern', '%backup%|%bak%', 'Backup', 'Internal', 90, 'Tier 3 - Archive', 50]
            ];

            $stmt = $conn->prepare("
                INSERT INTO classification_rules
                (rule_name, rule_description, match_type, match_pattern, classification,
                 sensitivity_level, retention_days, storage_tier_recommendation, priority)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            foreach ($rules as $rule) {
                $stmt->execute($rule);
            }

            $message .= "✓ Added 16 classification rules\n";
        } else {
            $message .= "Classification rules already exist (skipped)\n";
        }

        // Check if storage tiers exist
        $stmt = $conn->query("SELECT COUNT(*) as count FROM storage_tiers");
        $result = $stmt->fetch();

        if ($result['count'] == 0) {
            $message .= "Adding 4 storage tiers...\n";

            $tiers = [
                ['Tier 1 - High Performance', 1, 'High-performance SSD storage for mission-critical data', 0.50, 'High', 'Active databases, critical applications, frequently accessed files'],
                ['Tier 2 - Standard', 2, 'Standard SAS/SATA storage for regular business data', 0.15, 'Medium', 'Office documents, user files, moderate access frequency'],
                ['Tier 3 - Archive', 3, 'Low-cost nearline storage for infrequently accessed data', 0.05, 'Low', 'Backups, old documents, compliance archives, media files'],
                ['Tier 4 - Cold Storage', 4, 'Ultra-low-cost tape/cloud storage for long-term retention', 0.01, 'Low', 'Long-term archives, regulatory compliance, historical data']
            ];

            $stmt = $conn->prepare("
                INSERT INTO storage_tiers
                (tier_name, tier_level, description, cost_per_gb_monthly, performance_level, recommended_for)
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            foreach ($tiers as $tier) {
                $stmt->execute($tier);
            }

            $message .= "✓ Added 4 storage tiers\n";
        } else {
            $message .= "Storage tiers already exist (skipped)\n";
        }

        $installed = true;
        $message .= "\n=== Installation Complete! ===\n\n";
        $message .= "Next steps:\n";
        $message .= "1. Generate sample data (see button below)\n";
        $message .= "2. Access dashboard: http://localhost/networkscan/modules/srm_optimization.php\n";

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Handle sample data generation
if (isset($_POST['generate_sample'])) {
    $output = shell_exec('C:\xampp\php\php.exe generate_storage_sample_data.php 2>&1');
    $message = "Sample Data Generation:\n\n" . $output;
    $installed = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Storage Optimization - Web Installer</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 32px;
        }
        .subtitle {
            color: #6c757d;
            margin-bottom: 30px;
        }
        .button {
            background: #667eea;
            color: white;
            border: none;
            padding: 15px 30px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.3s;
            margin-right: 10px;
        }
        .button:hover {
            background: #5568d3;
        }
        .button:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        .success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 20px;
            border-radius: 6px;
            margin: 20px 0;
            white-space: pre-wrap;
            font-family: 'Courier New', monospace;
            font-size: 14px;
        }
        .error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 20px;
            border-radius: 6px;
            margin: 20px 0;
        }
        .info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
            padding: 20px;
            border-radius: 6px;
            margin: 20px 0;
        }
        .link {
            display: inline-block;
            margin-top: 20px;
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        .link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📊 Storage Optimization Installer</h1>
        <div class="subtitle">Web-based installation for Storage Resource Management module</div>

        <?php if ($error): ?>
            <div class="error">
                <strong>❌ Installation Failed</strong><br>
                Error: <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($installed): ?>
            <div class="success"><?php echo htmlspecialchars($message); ?></div>

            <form method="post" style="margin-top: 20px;">
                <button type="submit" name="generate_sample" class="button">
                    Generate Sample Data
                </button>
            </form>

            <a href="modules/srm_optimization.php" class="link">
                → Access Storage Optimization Dashboard
            </a>
        <?php else: ?>
            <div class="info">
                <strong>ℹ️ What will be installed:</strong><br><br>
                • 5 database tables (file_scans, duplicate_groups, classification_rules, etc.)<br>
                • 16 classification rules (Financial, HR, Legal, Source Code, etc.)<br>
                • 4 storage tier definitions (High Performance, Standard, Archive, Cold)<br>
            </div>

            <form method="post">
                <button type="submit" name="install" class="button">
                    Install Storage Optimization Tables
                </button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
