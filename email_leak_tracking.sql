-- Email Leak Tracking System
-- Tracks how emails are forwarded and leaked through multiple recipients

USE mailscan_dlp;

-- Table for tracking email forwarding chains
CREATE TABLE IF NOT EXISTS email_forwarding_chains (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chain_id VARCHAR(50) NOT NULL,
    original_email_id VARCHAR(255) NOT NULL,
    hop_number INT NOT NULL DEFAULT 1,
    from_address VARCHAR(255) NOT NULL,
    to_address VARCHAR(255) NOT NULL,
    forwarded_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    forward_type ENUM('direct_forward', 'cc', 'bcc', 'external_leak', 'personal_email') DEFAULT 'direct_forward',
    is_external BOOLEAN DEFAULT FALSE,
    is_unauthorized BOOLEAN DEFAULT FALSE,
    subject VARCHAR(500),
    additional_recipients TEXT,
    leak_risk_score INT DEFAULT 0,
    INDEX idx_chain_id (chain_id),
    INDEX idx_original_email (original_email_id),
    INDEX idx_from_address (from_address),
    INDEX idx_to_address (to_address),
    INDEX idx_is_external (is_external),
    INDEX idx_is_unauthorized (is_unauthorized)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for tracking email recipients and their attributes
CREATE TABLE IF NOT EXISTS email_recipients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email_address VARCHAR(255) UNIQUE NOT NULL,
    recipient_name VARCHAR(255),
    recipient_type ENUM('internal_employee', 'external_partner', 'personal_email', 'public_email', 'unknown') DEFAULT 'unknown',
    department VARCHAR(100),
    trust_level ENUM('trusted', 'suspicious', 'unauthorized', 'blocked') DEFAULT 'trusted',
    domain VARCHAR(255),
    is_external BOOLEAN DEFAULT TRUE,
    total_emails_received INT DEFAULT 0,
    total_emails_forwarded INT DEFAULT 0,
    leak_incidents INT DEFAULT 0,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    notes TEXT,
    INDEX idx_email (email_address),
    INDEX idx_domain (domain),
    INDEX idx_trust_level (trust_level),
    INDEX idx_is_external (is_external)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for identified leak incidents
CREATE TABLE IF NOT EXISTS leak_incidents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    incident_id VARCHAR(50) UNIQUE NOT NULL,
    chain_id VARCHAR(50) NOT NULL,
    original_email_id VARCHAR(255) NOT NULL,
    leak_source VARCHAR(255) NOT NULL,
    leak_destination VARCHAR(255) NOT NULL,
    total_hops INT DEFAULT 1,
    severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    incident_type ENUM('internal_forward', 'external_leak', 'public_exposure', 'competitor_leak', 'personal_email') DEFAULT 'external_leak',
    detected_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    investigation_status ENUM('new', 'investigating', 'confirmed', 'false_positive', 'resolved') DEFAULT 'new',
    assigned_to VARCHAR(255),
    resolution_notes TEXT,
    resolved_date TIMESTAMP NULL,
    INDEX idx_incident_id (incident_id),
    INDEX idx_chain_id (chain_id),
    INDEX idx_severity (severity),
    INDEX idx_status (investigation_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for domain trust classifications
CREATE TABLE IF NOT EXISTS domain_classifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    domain VARCHAR(255) UNIQUE NOT NULL,
    classification ENUM('internal', 'partner', 'competitor', 'public', 'personal', 'suspicious') DEFAULT 'public',
    trust_level ENUM('trusted', 'neutral', 'suspicious', 'blocked') DEFAULT 'neutral',
    is_whitelist BOOLEAN DEFAULT FALSE,
    is_blacklist BOOLEAN DEFAULT FALSE,
    risk_score INT DEFAULT 50,
    notes TEXT,
    added_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_domain (domain),
    INDEX idx_classification (classification),
    INDEX idx_trust_level (trust_level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default domain classifications
INSERT INTO domain_classifications (domain, classification, trust_level, is_whitelist, risk_score, notes) VALUES
-- Internal/Trusted domains
('techcorp.com', 'internal', 'trusted', TRUE, 0, 'Company internal domain'),
('company.local', 'internal', 'trusted', TRUE, 0, 'Internal network domain'),

-- Partner domains
('partner.com', 'partner', 'trusted', TRUE, 20, 'Trusted business partner'),
('vendor.com', 'partner', 'neutral', FALSE, 30, 'Regular vendor'),

-- Competitor domains (high risk)
('competitor.com', 'competitor', 'blocked', FALSE, 100, 'Direct competitor - unauthorized'),
('rival-corp.com', 'competitor', 'blocked', FALSE, 100, 'Competitor organization'),

-- Personal email domains (medium risk)
('gmail.com', 'personal', 'suspicious', FALSE, 70, 'Personal email - potential data leak'),
('yahoo.com', 'personal', 'suspicious', FALSE, 70, 'Personal email service'),
('hotmail.com', 'personal', 'suspicious', FALSE, 70, 'Personal email service'),
('outlook.com', 'personal', 'suspicious', FALSE, 70, 'Personal email service'),
('protonmail.com', 'personal', 'suspicious', FALSE, 75, 'Encrypted personal email'),

-- Public/suspicious domains
('mailinator.com', 'suspicious', 'blocked', TRUE, 95, 'Disposable email service'),
('tempmail.com', 'suspicious', 'blocked', TRUE, 95, 'Temporary email service'),
('guerrillamail.com', 'suspicious', 'blocked', TRUE, 95, 'Anonymous email service'),

-- News/media domains
('newsoutlet.com', 'public', 'suspicious', FALSE, 85, 'News media organization'),
('journalist.com', 'public', 'suspicious', FALSE, 85, 'Media contact');

-- Sample internal recipients
INSERT INTO email_recipients (email_address, recipient_name, recipient_type, department, trust_level, domain, is_external, total_emails_received) VALUES
('john.smith@techcorp.com', 'John Smith', 'internal_employee', 'Finance', 'trusted', 'techcorp.com', FALSE, 150),
('sarah.johnson@techcorp.com', 'Sarah Johnson', 'internal_employee', 'Finance', 'trusted', 'techcorp.com', FALSE, 200),
('mike.chen@techcorp.com', 'Mike Chen', 'internal_employee', 'IT', 'trusted', 'techcorp.com', FALSE, 180),
('lisa.williams@techcorp.com', 'Lisa Williams', 'internal_employee', 'HR', 'trusted', 'techcorp.com', FALSE, 95),
('robert.taylor@techcorp.com', 'Robert Taylor', 'internal_employee', 'Sales', 'suspicious', 'techcorp.com', FALSE, 75),
('emily.davis@techcorp.com', 'Emily Davis', 'internal_employee', 'Marketing', 'trusted', 'techcorp.com', FALSE, 120);

-- Sample external recipients
INSERT INTO email_recipients (email_address, recipient_name, recipient_type, department, trust_level, domain, is_external, leak_incidents) VALUES
('john.personal@gmail.com', 'John Smith', 'personal_email', NULL, 'suspicious', 'gmail.com', TRUE, 3),
('competitor@rival-corp.com', 'Unknown', 'external_partner', NULL, 'unauthorized', 'rival-corp.com', TRUE, 5),
('reporter@newsoutlet.com', 'Media Reporter', 'public_email', NULL, 'unauthorized', 'newsoutlet.com', TRUE, 2),
('partner@partner.com', 'Business Partner', 'external_partner', NULL, 'trusted', 'partner.com', TRUE, 0),
('sarah.home@yahoo.com', 'Sarah Johnson', 'personal_email', NULL, 'suspicious', 'yahoo.com', TRUE, 1),
('external.friend@gmail.com', 'External Contact', 'personal_email', NULL, 'unauthorized', 'gmail.com', TRUE, 4);
