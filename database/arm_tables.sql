-- Access Rights Manager (ARM) Database Tables
-- Comprehensive user access management, permissions control, and compliance

USE network_security_scanner;

-- Drop existing ARM tables if they exist (for clean reinstall)
DROP TABLE IF EXISTS arm_access_requests;
DROP TABLE IF EXISTS arm_audit_log;
DROP TABLE IF EXISTS arm_access_reviews;
DROP TABLE IF EXISTS arm_violations;
DROP TABLE IF EXISTS arm_policies;
DROP TABLE IF EXISTS arm_role_permissions;
DROP TABLE IF EXISTS arm_user_groups;
DROP TABLE IF EXISTS arm_user_roles;
DROP TABLE IF EXISTS arm_permissions;
DROP TABLE IF EXISTS arm_resources;
DROP TABLE IF EXISTS arm_groups;
DROP TABLE IF EXISTS arm_roles;
DROP TABLE IF EXISTS arm_users;

-- ARM Users: Users managed by the system
CREATE TABLE arm_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    department VARCHAR(100) DEFAULT NULL,
    job_title VARCHAR(100) DEFAULT NULL,
    manager_id INT DEFAULT NULL,
    status ENUM('active', 'inactive', 'suspended', 'pending') DEFAULT 'active',
    user_type ENUM('employee', 'contractor', 'vendor', 'service_account', 'admin') DEFAULT 'employee',
    last_login DATETIME DEFAULT NULL,
    password_changed_at DATETIME DEFAULT NULL,
    mfa_enabled BOOLEAN DEFAULT FALSE,
    risk_score INT DEFAULT 0 COMMENT '0-100 risk score',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_status (status),
    INDEX idx_department (department)
) ENGINE=InnoDB;

-- ARM Roles: Role definitions
CREATE TABLE arm_roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(100) UNIQUE NOT NULL,
    role_code VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    role_type ENUM('system', 'application', 'data', 'infrastructure') DEFAULT 'application',
    risk_level ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    requires_approval BOOLEAN DEFAULT FALSE,
    max_duration_days INT DEFAULT NULL COMMENT 'Max days for temporary access',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_role_type (role_type),
    INDEX idx_risk_level (risk_level)
) ENGINE=InnoDB;

-- ARM Groups: User groups
CREATE TABLE arm_groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_name VARCHAR(100) UNIQUE NOT NULL,
    group_code VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    group_type ENUM('security', 'distribution', 'dynamic', 'nested') DEFAULT 'security',
    parent_group_id INT DEFAULT NULL,
    owner_user_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_group_id) REFERENCES arm_groups(id) ON DELETE SET NULL,
    FOREIGN KEY (owner_user_id) REFERENCES arm_users(id) ON DELETE SET NULL,
    INDEX idx_group_type (group_type)
) ENGINE=InnoDB;

-- ARM Resources: Protected resources
CREATE TABLE arm_resources (
    id INT AUTO_INCREMENT PRIMARY KEY,
    resource_name VARCHAR(255) NOT NULL,
    resource_type ENUM('file_share', 'application', 'database', 'server', 'cloud_service', 'api', 'mailbox', 'sharepoint', 'other') NOT NULL,
    resource_path VARCHAR(500) DEFAULT NULL,
    description TEXT,
    owner_user_id INT DEFAULT NULL,
    classification ENUM('public', 'internal', 'confidential', 'restricted', 'top_secret') DEFAULT 'internal',
    sensitivity_level INT DEFAULT 1 COMMENT '1-5 sensitivity',
    compliance_frameworks VARCHAR(255) DEFAULT NULL COMMENT 'Comma-separated: SOX,GDPR,HIPAA',
    last_audit_date DATE DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_user_id) REFERENCES arm_users(id) ON DELETE SET NULL,
    INDEX idx_resource_type (resource_type),
    INDEX idx_classification (classification)
) ENGINE=InnoDB;

-- ARM Permissions: Permission definitions
CREATE TABLE arm_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    permission_name VARCHAR(100) NOT NULL,
    permission_code VARCHAR(50) NOT NULL,
    description TEXT,
    permission_type ENUM('read', 'write', 'execute', 'delete', 'admin', 'full_control') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_permission (permission_code)
) ENGINE=InnoDB;

-- ARM User-Role Assignments
CREATE TABLE arm_user_roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    role_id INT NOT NULL,
    assigned_by INT DEFAULT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME DEFAULT NULL,
    status ENUM('active', 'expired', 'revoked') DEFAULT 'active',
    justification TEXT,
    FOREIGN KEY (user_id) REFERENCES arm_users(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES arm_roles(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES arm_users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_user_role (user_id, role_id),
    INDEX idx_user (user_id),
    INDEX idx_role (role_id),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- ARM User-Group Memberships
CREATE TABLE arm_user_groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    group_id INT NOT NULL,
    added_by INT DEFAULT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES arm_users(id) ON DELETE CASCADE,
    FOREIGN KEY (group_id) REFERENCES arm_groups(id) ON DELETE CASCADE,
    FOREIGN KEY (added_by) REFERENCES arm_users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_user_group (user_id, group_id),
    INDEX idx_user (user_id),
    INDEX idx_group (group_id)
) ENGINE=InnoDB;

-- ARM Role-Permission Assignments
CREATE TABLE arm_role_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_id INT NOT NULL,
    permission_id INT NOT NULL,
    resource_id INT DEFAULT NULL,
    FOREIGN KEY (role_id) REFERENCES arm_roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES arm_permissions(id) ON DELETE CASCADE,
    FOREIGN KEY (resource_id) REFERENCES arm_resources(id) ON DELETE CASCADE,
    UNIQUE KEY unique_role_perm (role_id, permission_id, resource_id),
    INDEX idx_role (role_id),
    INDEX idx_permission (permission_id)
) ENGINE=InnoDB;

-- ARM Access Requests: Self-service access request workflow
CREATE TABLE arm_access_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_number VARCHAR(20) UNIQUE NOT NULL,
    requester_id INT NOT NULL,
    request_type ENUM('role', 'group', 'resource', 'permission') NOT NULL,
    target_id INT NOT NULL COMMENT 'ID of role/group/resource requested',
    justification TEXT NOT NULL,
    duration_days INT DEFAULT NULL COMMENT 'Requested duration, NULL=permanent',
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    status ENUM('pending', 'approved', 'rejected', 'cancelled', 'expired') DEFAULT 'pending',
    approver_id INT DEFAULT NULL,
    approved_at DATETIME DEFAULT NULL,
    rejection_reason TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (requester_id) REFERENCES arm_users(id) ON DELETE CASCADE,
    FOREIGN KEY (approver_id) REFERENCES arm_users(id) ON DELETE SET NULL,
    INDEX idx_requester (requester_id),
    INDEX idx_status (status),
    INDEX idx_created (created_at)
) ENGINE=InnoDB;

-- ARM Audit Log: Track all access-related changes
CREATE TABLE arm_audit_log (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    action_type ENUM('login', 'logout', 'access_granted', 'access_revoked', 'permission_change', 'role_assigned', 'group_added', 'password_change', 'mfa_change', 'resource_access', 'config_change', 'violation') NOT NULL,
    target_type ENUM('user', 'role', 'group', 'resource', 'permission', 'system') DEFAULT NULL,
    target_id INT DEFAULT NULL,
    old_value TEXT DEFAULT NULL,
    new_value TEXT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent TEXT DEFAULT NULL,
    result ENUM('success', 'failure', 'blocked') DEFAULT 'success',
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES arm_users(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_action (action_type),
    INDEX idx_created (created_at),
    INDEX idx_target (target_type, target_id)
) ENGINE=InnoDB;

-- ARM Access Reviews: Periodic access certification
CREATE TABLE arm_access_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    review_name VARCHAR(255) NOT NULL,
    review_type ENUM('quarterly', 'annual', 'triggered', 'compliance') NOT NULL,
    scope ENUM('all_users', 'department', 'role', 'resource', 'high_risk') NOT NULL,
    scope_filter VARCHAR(255) DEFAULT NULL,
    reviewer_id INT DEFAULT NULL,
    status ENUM('scheduled', 'in_progress', 'completed', 'overdue') DEFAULT 'scheduled',
    start_date DATE NOT NULL,
    due_date DATE NOT NULL,
    completed_date DATE DEFAULT NULL,
    total_items INT DEFAULT 0,
    reviewed_items INT DEFAULT 0,
    approved_items INT DEFAULT 0,
    revoked_items INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reviewer_id) REFERENCES arm_users(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_due_date (due_date)
) ENGINE=InnoDB;

-- ARM Violations: Policy violations and anomalies
CREATE TABLE arm_violations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    violation_type ENUM('excessive_permissions', 'stale_account', 'orphan_access', 'sod_conflict', 'unauthorized_access', 'policy_breach', 'anomaly') NOT NULL,
    severity ENUM('low', 'medium', 'high', 'critical') NOT NULL,
    description TEXT NOT NULL,
    resource_id INT DEFAULT NULL,
    detected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('open', 'investigating', 'resolved', 'accepted') DEFAULT 'open',
    resolved_by INT DEFAULT NULL,
    resolved_at DATETIME DEFAULT NULL,
    resolution_notes TEXT DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES arm_users(id) ON DELETE SET NULL,
    FOREIGN KEY (resource_id) REFERENCES arm_resources(id) ON DELETE SET NULL,
    FOREIGN KEY (resolved_by) REFERENCES arm_users(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_type (violation_type),
    INDEX idx_severity (severity),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- ARM Policies: Access control policies
CREATE TABLE arm_policies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    policy_name VARCHAR(255) NOT NULL,
    policy_type ENUM('password', 'session', 'access', 'sod', 'data_classification') NOT NULL,
    description TEXT,
    policy_rules JSON NOT NULL,
    enforcement_mode ENUM('audit', 'enforce', 'disabled') DEFAULT 'audit',
    applies_to VARCHAR(255) DEFAULT 'all' COMMENT 'all, role:X, group:X, department:X',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_type (policy_type),
    INDEX idx_enforcement (enforcement_mode)
) ENGINE=InnoDB;

-- Insert default permissions
INSERT INTO arm_permissions (permission_name, permission_code, description, permission_type) VALUES
('Read Access', 'READ', 'View and read content', 'read'),
('Write Access', 'WRITE', 'Create and modify content', 'write'),
('Execute Access', 'EXECUTE', 'Run applications or scripts', 'execute'),
('Delete Access', 'DELETE', 'Remove content permanently', 'delete'),
('Admin Access', 'ADMIN', 'Administrative privileges', 'admin'),
('Full Control', 'FULL_CONTROL', 'Complete control over resource', 'full_control')
ON DUPLICATE KEY UPDATE permission_name = permission_name;

-- Insert default roles
INSERT INTO arm_roles (role_name, role_code, description, role_type, risk_level, requires_approval) VALUES
('System Administrator', 'SYS_ADMIN', 'Full system administration access', 'system', 'critical', TRUE),
('Network Administrator', 'NET_ADMIN', 'Network infrastructure management', 'infrastructure', 'high', TRUE),
('Database Administrator', 'DB_ADMIN', 'Database management and access', 'data', 'high', TRUE),
('Security Analyst', 'SEC_ANALYST', 'Security monitoring and incident response', 'system', 'high', TRUE),
('Help Desk', 'HELP_DESK', 'Basic IT support and user assistance', 'application', 'low', FALSE),
('Standard User', 'STD_USER', 'Standard employee access', 'application', 'low', FALSE),
('Manager', 'MANAGER', 'Team management and approval rights', 'application', 'medium', FALSE),
('Executive', 'EXECUTIVE', 'Executive level access', 'application', 'high', TRUE),
('Auditor', 'AUDITOR', 'Read-only audit access', 'system', 'medium', TRUE),
('Contractor', 'CONTRACTOR', 'Limited contractor access', 'application', 'medium', TRUE)
ON DUPLICATE KEY UPDATE role_name = role_name;

-- Insert sample users
INSERT INTO arm_users (username, email, full_name, department, job_title, status, user_type, mfa_enabled, risk_score) VALUES
('admin', 'admin@company.com', 'System Administrator', 'IT', 'IT Director', 'active', 'admin', TRUE, 15),
('jsmith', 'john.smith@company.com', 'John Smith', 'Finance', 'Financial Analyst', 'active', 'employee', TRUE, 25),
('sjones', 'sarah.jones@company.com', 'Sarah Jones', 'HR', 'HR Manager', 'active', 'employee', TRUE, 20),
('mchen', 'michael.chen@company.com', 'Michael Chen', 'Engineering', 'Senior Developer', 'active', 'employee', FALSE, 45),
('edavis', 'emily.davis@company.com', 'Emily Davis', 'Marketing', 'Marketing Specialist', 'active', 'employee', FALSE, 30),
('rwilson', 'robert.wilson@company.com', 'Robert Wilson', 'Sales', 'Sales Manager', 'active', 'employee', TRUE, 35),
('lthompson', 'lisa.thompson@company.com', 'Lisa Thompson', 'IT', 'Security Analyst', 'active', 'employee', TRUE, 10),
('contractor1', 'contractor@external.com', 'External Contractor', 'IT', 'Contract Developer', 'active', 'contractor', FALSE, 60),
('svc_backup', 'backup@system.local', 'Backup Service Account', 'IT', 'Service Account', 'active', 'service_account', FALSE, 40),
('inactive_user', 'inactive@company.com', 'Inactive User', 'HR', 'Former Employee', 'inactive', 'employee', FALSE, 80)
ON DUPLICATE KEY UPDATE username = username;

-- Insert sample groups
INSERT INTO arm_groups (group_name, group_code, description, group_type) VALUES
('Domain Admins', 'DOMAIN_ADMINS', 'Full domain administrative access', 'security'),
('IT Department', 'IT_DEPT', 'All IT department members', 'security'),
('Finance Team', 'FINANCE', 'Finance department access', 'security'),
('HR Team', 'HR', 'Human Resources team', 'security'),
('Engineering', 'ENGINEERING', 'Engineering department', 'security'),
('All Employees', 'ALL_EMPLOYEES', 'All company employees', 'distribution'),
('VPN Users', 'VPN_USERS', 'Users with VPN access', 'security'),
('Remote Workers', 'REMOTE_WORKERS', 'Approved remote work employees', 'security'),
('Sensitive Data Access', 'SENSITIVE_DATA', 'Access to confidential data', 'security'),
('Privileged Users', 'PRIVILEGED', 'Users with elevated privileges', 'security')
ON DUPLICATE KEY UPDATE group_name = group_name;

-- Insert sample resources
INSERT INTO arm_resources (resource_name, resource_type, resource_path, classification, sensitivity_level, compliance_frameworks) VALUES
('Finance Share', 'file_share', '\\\\fileserver\\finance$', 'confidential', 4, 'SOX,GDPR'),
('HR Documents', 'file_share', '\\\\fileserver\\hr$', 'restricted', 5, 'GDPR,HIPAA'),
('Public Website', 'application', 'https://www.company.com', 'public', 1, NULL),
('CRM System', 'application', 'https://crm.company.com', 'internal', 3, 'GDPR'),
('Production Database', 'database', 'prod-db.company.local', 'restricted', 5, 'SOX,PCI-DSS'),
('Development Server', 'server', 'dev-server.company.local', 'internal', 2, NULL),
('AWS Console', 'cloud_service', 'https://console.aws.amazon.com', 'confidential', 4, 'SOC2'),
('API Gateway', 'api', 'https://api.company.com', 'internal', 3, NULL),
('Executive Mailboxes', 'mailbox', 'Exchange Online', 'restricted', 5, 'GDPR'),
('SharePoint Intranet', 'sharepoint', 'https://company.sharepoint.com', 'internal', 2, NULL)
ON DUPLICATE KEY UPDATE resource_name = resource_name;

-- Insert sample violations
INSERT INTO arm_violations (user_id, violation_type, severity, description, status) VALUES
(4, 'excessive_permissions', 'medium', 'User has write access to 15+ file shares not required for role', 'open'),
(8, 'stale_account', 'high', 'Contractor account not accessed in 90+ days', 'investigating'),
(10, 'orphan_access', 'high', 'Inactive user still has access to sensitive resources', 'open'),
(9, 'sod_conflict', 'critical', 'Service account has both create and approve permissions', 'open')
ON DUPLICATE KEY UPDATE description = description;

-- Insert sample policies
INSERT INTO arm_policies (policy_name, policy_type, description, policy_rules, enforcement_mode) VALUES
('Password Policy', 'password', 'Corporate password requirements', '{"min_length": 12, "require_uppercase": true, "require_lowercase": true, "require_numbers": true, "require_special": true, "max_age_days": 90, "history_count": 12}', 'enforce'),
('Session Policy', 'session', 'Session timeout and security', '{"idle_timeout_minutes": 30, "max_session_hours": 12, "require_mfa_on_login": true, "block_concurrent_sessions": false}', 'enforce'),
('Privileged Access Policy', 'access', 'Controls for privileged accounts', '{"require_justification": true, "require_approval": true, "max_duration_hours": 8, "require_mfa": true}', 'enforce'),
('SOD Policy', 'sod', 'Segregation of Duties rules', '{"conflicts": [{"role1": "AP_CREATOR", "role2": "AP_APPROVER"}, {"role1": "USER_CREATOR", "role2": "USER_ADMIN"}]}', 'audit')
ON DUPLICATE KEY UPDATE policy_name = policy_name;

-- Insert sample access requests
INSERT INTO arm_access_requests (request_number, requester_id, request_type, target_id, justification, duration_days, status, priority) VALUES
('AR-2025-001', 4, 'role', 2, 'Need network admin access for infrastructure project', 30, 'pending', 'high'),
('AR-2025-002', 5, 'resource', 1, 'Require finance share access for Q1 reporting', NULL, 'approved', 'medium'),
('AR-2025-003', 6, 'group', 9, 'Need sensitive data access for client contracts', 90, 'pending', 'high'),
('AR-2025-004', 8, 'role', 3, 'Database access for migration project', 14, 'rejected', 'urgent')
ON DUPLICATE KEY UPDATE request_number = request_number;
