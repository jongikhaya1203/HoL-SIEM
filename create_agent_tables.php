<?php
/**
 * Create database tables for agent management
 * Run once: php create_agent_tables.php
 */
require_once __DIR__ . '/classes/Database.php';

$db = Database::getInstance();

echo "Creating agent management tables...\n\n";

// Table for agent registration and tracking
$sql1 = "CREATE TABLE IF NOT EXISTS agents (
    id INT PRIMARY KEY AUTO_INCREMENT,
    agent_id VARCHAR(64) UNIQUE NOT NULL,
    hostname VARCHAR(255) NOT NULL,
    first_seen DATETIME NOT NULL,
    last_checkin DATETIME NOT NULL,
    status ENUM('active', 'inactive', 'error') DEFAULT 'active',
    os_family VARCHAR(50),
    os VARCHAR(255),
    architecture VARCHAR(50),
    agent_version VARCHAR(20),
    INDEX idx_agent_id (agent_id),
    INDEX idx_last_checkin (last_checkin),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

try {
    $db->query($sql1, []);
    echo "✓ Created 'agents' table\n";
} catch (Exception $e) {
    echo "✗ Error creating 'agents' table: " . $e->getMessage() . "\n";
}

// Table for agent IP addresses (one agent can have multiple IPs)
$sql2 = "CREATE TABLE IF NOT EXISTS agent_ips (
    id INT PRIMARY KEY AUTO_INCREMENT,
    agent_id VARCHAR(64) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    discovered_at DATETIME NOT NULL,
    last_seen DATETIME NOT NULL,
    FOREIGN KEY (agent_id) REFERENCES agents(agent_id) ON DELETE CASCADE,
    INDEX idx_agent_id (agent_id),
    INDEX idx_ip (ip_address)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

try {
    $db->query($sql2, []);
    echo "✓ Created 'agent_ips' table\n";
} catch (Exception $e) {
    echo "✗ Error creating 'agent_ips' table: " . $e->getMessage() . "\n";
}

// Table for agent network information
$sql3 = "CREATE TABLE IF NOT EXISTS agent_network (
    id INT PRIMARY KEY AUTO_INCREMENT,
    agent_id VARCHAR(64) NOT NULL,
    collected_at DATETIME NOT NULL,
    open_ports TEXT,
    listening_services TEXT,
    dns_servers TEXT,
    routing_table TEXT,
    FOREIGN KEY (agent_id) REFERENCES agents(agent_id) ON DELETE CASCADE,
    INDEX idx_agent_id (agent_id),
    INDEX idx_collected_at (collected_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

try {
    $db->query($sql3, []);
    echo "✓ Created 'agent_network' table\n";
} catch (Exception $e) {
    echo "✗ Error creating 'agent_network' table: " . $e->getMessage() . "\n";
}

// Table for agent processes (top processes snapshot)
$sql4 = "CREATE TABLE IF NOT EXISTS agent_processes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    agent_id VARCHAR(64) NOT NULL,
    collected_at DATETIME NOT NULL,
    processes TEXT,
    FOREIGN KEY (agent_id) REFERENCES agents(agent_id) ON DELETE CASCADE,
    INDEX idx_agent_id (agent_id),
    INDEX idx_collected_at (collected_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

try {
    $db->query($sql4, []);
    echo "✓ Created 'agent_processes' table\n";
} catch (Exception $e) {
    echo "✗ Error creating 'agent_processes' table: " . $e->getMessage() . "\n";
}

// Table for agent security information
$sql5 = "CREATE TABLE IF NOT EXISTS agent_security (
    id INT PRIMARY KEY AUTO_INCREMENT,
    agent_id VARCHAR(64) NOT NULL,
    collected_at DATETIME NOT NULL,
    firewall_status VARCHAR(50),
    antivirus_status VARCHAR(255),
    windows_defender VARCHAR(50),
    last_update VARCHAR(100),
    user_accounts TEXT,
    FOREIGN KEY (agent_id) REFERENCES agents(agent_id) ON DELETE CASCADE,
    INDEX idx_agent_id (agent_id),
    INDEX idx_collected_at (collected_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

try {
    $db->query($sql5, []);
    echo "✓ Created 'agent_security' table\n";
} catch (Exception $e) {
    echo "✗ Error creating 'agent_security' table: " . $e->getMessage() . "\n";
}

// Table for API keys
$sql6 = "CREATE TABLE IF NOT EXISTS agent_api_keys (
    id INT PRIMARY KEY AUTO_INCREMENT,
    api_key VARCHAR(64) UNIQUE NOT NULL,
    description VARCHAR(255),
    created_at DATETIME NOT NULL,
    last_used DATETIME,
    status ENUM('active', 'disabled') DEFAULT 'active',
    INDEX idx_api_key (api_key),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

try {
    $db->query($sql6, []);
    echo "✓ Created 'agent_api_keys' table\n";
} catch (Exception $e) {
    echo "✗ Error creating 'agent_api_keys' table: " . $e->getMessage() . "\n";
}

// Table for agent check-in log
$sql7 = "CREATE TABLE IF NOT EXISTS agent_checkins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    agent_id VARCHAR(64) NOT NULL,
    checkin_time DATETIME NOT NULL,
    success BOOLEAN DEFAULT TRUE,
    error_message TEXT,
    FOREIGN KEY (agent_id) REFERENCES agents(agent_id) ON DELETE CASCADE,
    INDEX idx_agent_id (agent_id),
    INDEX idx_checkin_time (checkin_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

try {
    $db->query($sql7, []);
    echo "✓ Created 'agent_checkins' table\n";
} catch (Exception $e) {
    echo "✗ Error creating 'agent_checkins' table: " . $e->getMessage() . "\n";
}

// Create a default API key
$defaultKey = bin2hex(random_bytes(32));
try {
    $db->query(
        "INSERT INTO agent_api_keys (api_key, description, created_at, status)
         VALUES (?, ?, NOW(), 'active')",
        [$defaultKey, 'Default API Key']
    );
    echo "\n✓ Created default API key: $defaultKey\n";
    echo "  Save this key for agent configuration!\n";
} catch (Exception $e) {
    echo "\n✗ Error creating default API key: " . $e->getMessage() . "\n";
}

echo "\nAgent management tables created successfully!\n";
echo "Use the API key above when configuring agents.\n";
