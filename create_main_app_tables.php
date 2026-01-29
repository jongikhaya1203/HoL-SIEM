<?php
/**
 * Create Main Application Tables
 * This creates the tables needed for the IOC main application
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'localhost';
$username = 'root';
$password = '';
$database = 'network_security';

echo "<!DOCTYPE html><html><head><title>Create Main App Tables</title>";
echo "<style>body{font-family:monospace;background:#1a1a2e;color:#0f0;padding:20px;}";
echo ".err{color:#f00;}.ok{color:#0f0;}.info{color:#0ff;}</style></head><body>";
echo "<h2>Create Main Application Tables</h2><pre>";

try {
    $conn = new mysqli($host, $username, $password, $database);

    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    echo "<span class='ok'>✓ Connected to database '$database'</span>\n\n";

    // Create reports table
    echo "Creating reports table...\n";
    $conn->query("CREATE TABLE IF NOT EXISTS reports (
        id INT AUTO_INCREMENT PRIMARY KEY,
        scan_id INT,
        report_type VARCHAR(50),
        report_format VARCHAR(20),
        file_path VARCHAR(255),
        file_size INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_scan (scan_id),
        INDEX idx_type (report_type)
    ) ENGINE=InnoDB");
    echo "<span class='ok'>✓ Reports table created</span>\n";

    // Create scans table
    echo "Creating scans table...\n";
    $conn->query("CREATE TABLE IF NOT EXISTS scans (
        id INT AUTO_INCREMENT PRIMARY KEY,
        scan_name VARCHAR(100),
        target VARCHAR(255),
        scan_type VARCHAR(50),
        status VARCHAR(50) DEFAULT 'pending',
        started_at TIMESTAMP NULL,
        completed_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_status (status),
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB");
    echo "<span class='ok'>✓ Scans table created</span>\n";

    // Create hosts table
    echo "Creating hosts table...\n";
    $conn->query("CREATE TABLE IF NOT EXISTS hosts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        scan_id INT,
        ip_address VARCHAR(45),
        hostname VARCHAR(255),
        status VARCHAR(50),
        os_detection TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (scan_id) REFERENCES scans(id) ON DELETE CASCADE,
        INDEX idx_scan (scan_id),
        INDEX idx_ip (ip_address)
    ) ENGINE=InnoDB");
    echo "<span class='ok'>✓ Hosts table created</span>\n";

    // Create vulnerabilities table
    echo "Creating vulnerabilities table...\n";
    $conn->query("CREATE TABLE IF NOT EXISTS vulnerabilities (
        id INT AUTO_INCREMENT PRIMARY KEY,
        cve_id VARCHAR(50),
        title VARCHAR(255),
        description TEXT,
        severity VARCHAR(20),
        cvss_score DECIMAL(3,1),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_cve (cve_id),
        INDEX idx_severity (severity)
    ) ENGINE=InnoDB");
    echo "<span class='ok'>✓ Vulnerabilities table created</span>\n";

    // Create site_settings table
    echo "Creating site_settings table...\n";
    $conn->query("CREATE TABLE IF NOT EXISTS site_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) UNIQUE NOT NULL,
        setting_value TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_key (setting_key)
    ) ENGINE=InnoDB");
    echo "<span class='ok'>✓ Site settings table created</span>\n";

    // Insert default settings
    echo "Inserting default settings...\n";
    $conn->query("INSERT IGNORE INTO site_settings (setting_key, setting_value) VALUES
        ('app_name', 'IOC Intelligent Operating Centre'),
        ('logo_url', ''),
        ('theme_color', '#667eea')");
    echo "<span class='ok'>✓ Default settings inserted</span>\n";

    // Create modules table
    echo "Creating modules table...\n";
    $conn->query("CREATE TABLE IF NOT EXISTS modules (
        id INT AUTO_INCREMENT PRIMARY KEY,
        module_code VARCHAR(50) UNIQUE NOT NULL,
        module_name VARCHAR(100) NOT NULL,
        description TEXT,
        category VARCHAR(50) NOT NULL,
        url VARCHAR(255) NOT NULL,
        icon VARCHAR(10) DEFAULT '📦',
        status ENUM('active', 'beta', 'inactive', 'coming_soon') DEFAULT 'active',
        implementation_level ENUM('full', 'partial', 'placeholder', 'planned') DEFAULT 'full',
        enabled BOOLEAN DEFAULT TRUE,
        display_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_category (category),
        INDEX idx_status (status),
        INDEX idx_enabled (enabled)
    ) ENGINE=InnoDB");
    echo "<span class='ok'>✓ Modules table created</span>\n";

    echo "\n";
    echo "<span class='ok'>========================================</span>\n";
    echo "<span class='ok'>SUCCESS! All Main Tables Created</span>\n";
    echo "<span class='ok'>========================================</span>\n\n";

    echo "Tables created:\n";
    echo "  ✓ reports\n";
    echo "  ✓ scans\n";
    echo "  ✓ hosts\n";
    echo "  ✓ vulnerabilities\n";
    echo "  ✓ site_settings\n";
    echo "  ✓ modules\n\n";

    echo "Next steps:\n";
    echo "1. Run: <a href='add_scada_module.php' style='color:#0ff;'>add_scada_module.php</a> to add SCADA to dashboard\n";
    echo "2. Visit: <a href='index.php' style='color:#0ff;'>index.php</a> to see the homepage\n";
    echo "3. Access SCADA: <a href='scada_hmi.php' style='color:#0ff;'>scada_hmi.php</a>\n";

    $conn->close();

} catch (Exception $e) {
    echo "\n<span class='err'>❌ ERROR: " . $e->getMessage() . "</span>\n";
    exit(1);
}

echo "</pre>";
echo "</body></html>";
?>
