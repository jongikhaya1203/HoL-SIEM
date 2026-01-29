-- Storage Resource Management - Data Duplication & Classification
-- Schema for storage optimization

CREATE DATABASE IF NOT EXISTS storage_management;
USE storage_management;

-- Table for file scan results
CREATE TABLE IF NOT EXISTS file_scans (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for duplicate file groups
CREATE TABLE IF NOT EXISTS duplicate_groups (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for data classification rules
CREATE TABLE IF NOT EXISTS classification_rules (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for storage optimization recommendations
CREATE TABLE IF NOT EXISTS optimization_recommendations (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for storage tier definitions
CREATE TABLE IF NOT EXISTS storage_tiers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tier_name VARCHAR(100) UNIQUE NOT NULL,
    tier_level INT NOT NULL,
    description TEXT,
    cost_per_gb_monthly DECIMAL(10,4) DEFAULT 0.00,
    performance_level ENUM('High', 'Medium', 'Low') DEFAULT 'Medium',
    recommended_for TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default classification rules
INSERT INTO classification_rules (rule_name, rule_description, match_type, match_pattern, classification, sensitivity_level, retention_days, storage_tier_recommendation, priority) VALUES
-- By file extension
('Financial Documents', 'Excel files in Finance folders', 'path_pattern', '%/Finance/%', 'Financial Data', 'Confidential', 2555, 'Tier 1 - High Performance', 90),
('HR Records', 'Documents in HR directory', 'path_pattern', '%/HR/%', 'Human Resources', 'Restricted', 2555, 'Tier 1 - High Performance', 95),
('Legal Documents', 'Legal department files', 'path_pattern', '%/Legal/%', 'Legal', 'Restricted', 3650, 'Tier 1 - High Performance', 95),
('Customer Data', 'CRM and customer information', 'path_pattern', '%/Customers/%', 'Customer Information', 'Confidential', 1825, 'Tier 1 - High Performance', 85),

-- By file type
('Source Code', 'Programming source files', 'extension', '.java|.py|.js|.cpp|.cs|.php', 'Source Code', 'Internal', 1095, 'Tier 2 - Standard', 70),
('Database Backups', 'Database backup files', 'extension', '.bak|.sql|.dump', 'Database Backup', 'Confidential', 90, 'Tier 3 - Archive', 80),
('Office Documents', 'Microsoft Office files', 'extension', '.docx|.xlsx|.pptx|.doc|.xls|.ppt', 'Office Document', 'Internal', 730, 'Tier 2 - Standard', 50),
('PDF Documents', 'PDF files', 'extension', '.pdf', 'PDF Document', 'Internal', 730, 'Tier 2 - Standard', 50),
('Image Files', 'Images and graphics', 'extension', '.jpg|.jpeg|.png|.gif|.bmp|.svg', 'Image', 'Public', 365, 'Tier 3 - Archive', 30),
('Video Files', 'Video content', 'extension', '.mp4|.avi|.mov|.wmv|.mkv', 'Video', 'Internal', 180, 'Tier 3 - Archive', 40),
('Audio Files', 'Audio content', 'extension', '.mp3|.wav|.flac|.aac', 'Audio', 'Public', 180, 'Tier 3 - Archive', 30),
('Archive Files', 'Compressed archives', 'extension', '.zip|.rar|.7z|.tar|.gz', 'Archive', 'Internal', 365, 'Tier 3 - Archive', 40),
('Log Files', 'System and application logs', 'extension', '.log|.txt', 'Log File', 'Internal', 90, 'Tier 3 - Archive', 20),
('Temporary Files', 'Temporary and cache files', 'extension', '.tmp|.temp|.cache', 'Temporary', 'Public', 7, 'Tier 3 - Archive', 10),

-- By filename patterns
('Confidential Files', 'Files marked confidential', 'filename_pattern', '%confidential%', 'Confidential Data', 'Confidential', 1825, 'Tier 1 - High Performance', 90),
('Draft Documents', 'Draft versions of documents', 'filename_pattern', '%draft%', 'Draft', 'Internal', 180, 'Tier 2 - Standard', 30),
('Final Versions', 'Finalized documents', 'filename_pattern', '%final%', 'Final Document', 'Internal', 1095, 'Tier 1 - High Performance', 60),
('Backup Files', 'Backup copies', 'filename_pattern', '%backup%|%bak%', 'Backup', 'Internal', 90, 'Tier 3 - Archive', 50),

-- By size
('Large Media Files', 'Files larger than 100MB', 'size_range', '>104857600', 'Large Media', 'Internal', 365, 'Tier 3 - Archive', 40),
('Small Documents', 'Small files under 1MB', 'size_range', '<1048576', 'Small File', 'Public', 365, 'Tier 2 - Standard', 20);

-- Insert default storage tiers
INSERT INTO storage_tiers (tier_name, tier_level, description, cost_per_gb_monthly, performance_level, recommended_for) VALUES
('Tier 1 - High Performance', 1, 'High-performance SSD storage for mission-critical data', 0.50, 'High', 'Active databases, critical applications, frequently accessed files'),
('Tier 2 - Standard', 2, 'Standard SAS/SATA storage for regular business data', 0.15, 'Medium', 'Office documents, user files, moderate access frequency'),
('Tier 3 - Archive', 3, 'Low-cost nearline storage for infrequently accessed data', 0.05, 'Low', 'Backups, old documents, compliance archives, media files'),
('Tier 4 - Cold Storage', 4, 'Ultra-low-cost tape/cloud storage for long-term retention', 0.01, 'Low', 'Long-term archives, regulatory compliance, historical data');
