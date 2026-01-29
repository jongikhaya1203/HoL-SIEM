<?php
/**
 * Create Agent API Tables
 * This creates tables for agent authentication and data submission
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'localhost';
$username = 'root';
$password = '';
$database = 'network_security';

echo "<!DOCTYPE html><html><head><title>Create Agent API Tables</title>";
echo "<style>body{font-family:monospace;background:#1a1a2e;color:#0f0;padding:20px;}";
echo ".err{color:#f00;}.ok{color:#0f0;}.info{color:#0ff;}</style></head><body>";
echo "<h2>Create Agent API Tables</h2><pre>";

try {
    $conn = new mysqli($host, $username, $password, $database);

    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    echo "<span class='ok'>✓ Connected to database</span>\n\n";

    // Create API keys table
    echo "Creating agent_api_keys table...\n";
    $conn->query("CREATE TABLE IF NOT EXISTS agent_api_keys (
        id INT AUTO_INCREMENT PRIMARY KEY,
        api_key VARCHAR(64) UNIQUE NOT NULL,
        key_name VARCHAR(100) NOT NULL,
        site_id INT,
        allowed_ips TEXT,
        permissions JSON,
        status ENUM('active', 'suspended', 'revoked') DEFAULT 'active',
        last_used_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        expires_at TIMESTAMP NULL,
        created_by VARCHAR(100),
        notes TEXT,
        INDEX idx_api_key (api_key),
        INDEX idx_status (status),
        INDEX idx_site (site_id),
        FOREIGN KEY (site_id) REFERENCES scada_sites(id) ON DELETE SET NULL
    ) ENGINE=InnoDB");
    echo "<span class='ok'>✓ agent_api_keys table created</span>\n";

    // Create agent heartbeat table
    echo "Creating agent_heartbeats table...\n";
    $conn->query("CREATE TABLE IF NOT EXISTS agent_heartbeats (
        id INT AUTO_INCREMENT PRIMARY KEY,
        api_key_id INT NOT NULL,
        agent_version VARCHAR(20),
        hostname VARCHAR(255),
        ip_address VARCHAR(45),
        system_info JSON,
        cpu_usage DECIMAL(5,2),
        memory_usage DECIMAL(5,2),
        disk_usage DECIMAL(5,2),
        uptime_seconds INT,
        heartbeat_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_api_key (api_key_id),
        INDEX idx_time (heartbeat_time),
        FOREIGN KEY (api_key_id) REFERENCES agent_api_keys(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");
    echo "<span class='ok'>✓ agent_heartbeats table created</span>\n";

    // Create agent data submissions table
    echo "Creating agent_data_submissions table...\n";
    $conn->query("CREATE TABLE IF NOT EXISTS agent_data_submissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        api_key_id INT NOT NULL,
        submission_type ENUM('tag_data', 'alarm', 'event', 'status') NOT NULL,
        plc_id INT,
        rtu_id INT,
        data_payload JSON NOT NULL,
        records_count INT DEFAULT 0,
        processing_status ENUM('pending', 'processed', 'failed') DEFAULT 'pending',
        error_message TEXT,
        submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        processed_at TIMESTAMP NULL,
        INDEX idx_api_key (api_key_id),
        INDEX idx_status (processing_status),
        INDEX idx_submitted (submitted_at),
        INDEX idx_plc (plc_id),
        INDEX idx_rtu (rtu_id),
        FOREIGN KEY (api_key_id) REFERENCES agent_api_keys(id) ON DELETE CASCADE,
        FOREIGN KEY (plc_id) REFERENCES scada_plcs(id) ON DELETE SET NULL,
        FOREIGN KEY (rtu_id) REFERENCES scada_rtus(id) ON DELETE SET NULL
    ) ENGINE=InnoDB");
    echo "<span class='ok'>✓ agent_data_submissions table created</span>\n";

    echo "\n";
    echo "<span class='ok'>========================================</span>\n";
    echo "<span class='ok'>SUCCESS! Agent API Tables Created</span>\n";
    echo "<span class='ok'>========================================</span>\n\n";

    echo "Tables created:\n";
    echo "  ✓ agent_api_keys - Store API keys for agent authentication\n";
    echo "  ✓ agent_heartbeats - Track agent health and status\n";
    echo "  ✓ agent_data_submissions - Log all data submissions from agents\n\n";

    echo "Next steps:\n";
    echo "1. Run: <a href='generate_agent_api_key.php' style='color:#0ff;'>generate_agent_api_key.php</a> to create API keys\n";
    echo "2. Deploy agent software to PLCs/RTUs\n";
    echo "3. Configure agents with API keys\n";

    $conn->close();

} catch (Exception $e) {
    echo "\n<span class='err'>❌ ERROR: " . $e->getMessage() . "</span>\n";
    exit(1);
}

echo "</pre></body></html>";
?>
