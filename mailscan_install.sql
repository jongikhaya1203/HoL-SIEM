-- Email DLP System - Complete Installation
-- Import this file via phpMyAdmin

-- Create database
CREATE DATABASE IF NOT EXISTS mailscan_dlp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE mailscan_dlp;

-- Drop existing tables if any
DROP TABLE IF EXISTS scan_results;
DROP TABLE IF EXISTS email_logs;
DROP TABLE IF EXISTS detection_rules;
DROP TABLE IF EXISTS audit_log;

-- Table for detection rules
CREATE TABLE detection_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rule_name VARCHAR(255) NOT NULL,
    rule_description TEXT,
    rule_type ENUM('regex', 'keyword', 'pattern') NOT NULL DEFAULT 'keyword',
    pattern TEXT NOT NULL,
    severity ENUM('low', 'medium', 'high', 'critical') NOT NULL DEFAULT 'medium',
    action ENUM('flag', 'block', 'quarantine') NOT NULL DEFAULT 'flag',
    enabled BOOLEAN DEFAULT TRUE,
    category VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by VARCHAR(100),
    INDEX idx_enabled (enabled),
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for email logs
CREATE TABLE email_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email_id VARCHAR(255) UNIQUE,
    sender_email VARCHAR(255) NOT NULL,
    sender_name VARCHAR(255),
    recipient_email TEXT,
    subject TEXT,
    body_text TEXT,
    body_html TEXT,
    attachments TEXT,
    received_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    scan_status ENUM('pending', 'scanned', 'flagged', 'blocked') DEFAULT 'pending',
    risk_score INT DEFAULT 0,
    INDEX idx_scan_status (scan_status),
    INDEX idx_sender (sender_email),
    INDEX idx_received (received_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for scan results/matches
CREATE TABLE scan_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email_id VARCHAR(255) NOT NULL,
    rule_id INT NOT NULL,
    matched_content TEXT,
    match_location VARCHAR(50),
    context_snippet TEXT,
    detected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed BOOLEAN DEFAULT FALSE,
    reviewed_by VARCHAR(100),
    reviewed_at TIMESTAMP NULL,
    false_positive BOOLEAN DEFAULT FALSE,
    notes TEXT,
    FOREIGN KEY (rule_id) REFERENCES detection_rules(id) ON DELETE CASCADE,
    INDEX idx_email (email_id),
    INDEX idx_reviewed (reviewed),
    INDEX idx_detected (detected_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for audit log
CREATE TABLE audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    action_type VARCHAR(50) NOT NULL,
    entity_type VARCHAR(50),
    entity_id VARCHAR(255),
    user_email VARCHAR(255),
    action_details TEXT,
    ip_address VARCHAR(45),
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_timestamp (timestamp),
    INDEX idx_action (action_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample detection rules
INSERT INTO detection_rules (rule_name, rule_description, rule_type, pattern, severity, category, created_by) VALUES
('Credit Card Numbers', 'Detects credit card numbers (Visa, MasterCard, Amex, Discover)', 'regex', '\\b(?:4[0-9]{12}(?:[0-9]{3})?|5[1-5][0-9]{14}|3[47][0-9]{13}|6(?:011|5[0-9]{2})[0-9]{12})\\b', 'critical', 'Financial', 'system'),
('Social Security Numbers', 'Detects US Social Security Numbers in format XXX-XX-XXXX', 'regex', '\\b\\d{3}-\\d{2}-\\d{4}\\b', 'critical', 'PII', 'system'),
('Email Addresses', 'Detects email addresses that might be leaked', 'regex', '\\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\\.[A-Z|a-z]{2,}\\b', 'low', 'Contact Info', 'system'),
('Phone Numbers', 'Detects US phone numbers', 'regex', '\\b(?:\\+?1[-.]?)?\\(?([0-9]{3})\\)?[-.]?([0-9]{3})[-.]?([0-9]{4})\\b', 'low', 'Contact Info', 'system'),
('Confidential Keywords', 'Detects confidential/secret keywords', 'keyword', 'confidential|secret|classified|internal only|do not distribute|proprietary|trade secret', 'high', 'Classification', 'system'),
('API Keys', 'Detects potential API keys and tokens', 'regex', '(?i)(api[_-]?key|apikey|access[_-]?token|auth[_-]?token|secret[_-]?key)["\']?\\s*[:=]\\s*["\']?([a-zA-Z0-9_\\-]{20,})', 'critical', 'Security', 'system'),
('AWS Keys', 'Detects AWS access keys', 'regex', '(?:AKIA|A3T|AGPA|AIDA|AROA|AIPA|ANPA|ANVA|ASIA)[A-Z0-9]{16}', 'critical', 'Security', 'system'),
('IP Addresses', 'Detects IPv4 addresses', 'regex', '\\b(?:[0-9]{1,3}\\.){3}[0-9]{1,3}\\b', 'low', 'Network Info', 'system'),
('Passwords in Plain Text', 'Detects password keywords', 'keyword', 'password is|password:|pwd:|pass:|credentials are', 'critical', 'Security', 'system'),
('Bank Account Numbers', 'Detects potential bank account numbers', 'regex', '\\b[0-9]{8,17}\\b', 'high', 'Financial', 'system'),
('Medical Record Numbers', 'Detects medical/patient ID patterns', 'keyword', 'patient id|medical record|mrn:|patient number', 'high', 'Healthcare', 'system'),
('Insider Trading Keywords', 'Detects potential insider trading discussions', 'keyword', 'insider information|before announcement|stock tip|confidential earnings|unreleased', 'critical', 'Compliance', 'system');
