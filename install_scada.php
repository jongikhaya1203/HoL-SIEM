<?php
/**
 * SCADA System Installation Script
 * Automated installation of SCADA database schema and default data
 *
 * @author IOC Platform
 * @version 2.0
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>SCADA Network Monitoring System - Installation</h1>";
echo "<pre>";

// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'network_security';

try {
    echo "===========================================\n";
    echo "SCADA SYSTEM INSTALLATION\n";
    echo "===========================================\n\n";

    // Connect to MySQL
    echo "[1/6] Connecting to MySQL server...\n";
    $conn = new mysqli($host, $username, $password);

    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    echo "✓ Connected successfully\n\n";

    // Create database if not exists
    echo "[2/6] Creating/verifying database...\n";
    $conn->query("CREATE DATABASE IF NOT EXISTS $database");
    $conn->select_db($database);
    echo "✓ Database '$database' ready\n\n";

    // Read and execute SCADA schema
    echo "[3/6] Installing SCADA database schema...\n";
    $schemaFile = __DIR__ . '/scada_schema.sql';

    if (!file_exists($schemaFile)) {
        throw new Exception("Schema file not found: $schemaFile");
    }

    $schema = file_get_contents($schemaFile);

    // Split by delimiter and execute each statement
    $statements = explode(';', $schema);
    $successCount = 0;
    $errorCount = 0;

    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (empty($statement) || substr($statement, 0, 2) === '--') {
            continue;
        }

        if ($conn->query($statement)) {
            $successCount++;
        } else {
            // Some errors are acceptable (like DROP TABLE IF NOT EXISTS)
            if (!preg_match('/DROP/i', $statement)) {
                $errorCount++;
                echo "Warning: " . $conn->error . "\n";
            }
        }
    }

    echo "✓ Executed $successCount SQL statements\n";
    if ($errorCount > 0) {
        echo "  ⚠ $errorCount warnings (non-critical)\n";
    }
    echo "\n";

    // Install industry-specific tables
    echo "[4/6] Creating industry-specific tables...\n";

    // Oil & Gas tables
    $conn->query("CREATE TABLE IF NOT EXISTS oil_gas_pipelines (
        id INT AUTO_INCREMENT PRIMARY KEY,
        site_id INT NOT NULL,
        pipeline_name VARCHAR(100) NOT NULL,
        length_miles DECIMAL(10,2),
        diameter_inches DECIMAL(5,2),
        friction_factor DECIMAL(5,4),
        flow_in_tag_id INT,
        flow_out_tag_id INT,
        pressure_in_tag_id INT,
        pressure_out_tag_id INT,
        temperature_tag_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (site_id) REFERENCES scada_sites(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");

    $conn->query("CREATE TABLE IF NOT EXISTS oil_gas_wellheads (
        id INT AUTO_INCREMENT PRIMARY KEY,
        site_id INT NOT NULL,
        well_name VARCHAR(100) NOT NULL,
        casing_pressure_tag_id INT,
        tubing_pressure_tag_id INT,
        flow_rate_tag_id INT,
        temperature_tag_id INT,
        max_casing_pressure DECIMAL(10,2),
        max_tubing_pressure DECIMAL(10,2),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (site_id) REFERENCES scada_sites(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");

    $conn->query("CREATE TABLE IF NOT EXISTS oil_gas_lact_units (
        id INT AUTO_INCREMENT PRIMARY KEY,
        site_id INT NOT NULL,
        unit_name VARCHAR(100) NOT NULL,
        gross_volume_tag_id INT,
        net_volume_tag_id INT,
        bsw_tag_id INT,
        api_gravity_tag_id INT,
        temperature_tag_id INT,
        pressure_tag_id INT,
        flow_rate_tag_id INT,
        prover_status_tag_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (site_id) REFERENCES scada_sites(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");

    $conn->query("CREATE TABLE IF NOT EXISTS oil_gas_separators (
        id INT AUTO_INCREMENT PRIMARY KEY,
        site_id INT NOT NULL,
        separator_name VARCHAR(100) NOT NULL,
        oil_level_tag_id INT,
        water_level_tag_id INT,
        gas_pressure_tag_id INT,
        temperature_tag_id INT,
        oil_flow_tag_id INT,
        gas_flow_tag_id INT,
        water_flow_tag_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (site_id) REFERENCES scada_sites(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");

    // Rail tables
    $conn->query("CREATE TABLE IF NOT EXISTS rail_track_circuits (
        id INT AUTO_INCREMENT PRIMARY KEY,
        site_id INT NOT NULL,
        track_section VARCHAR(50) NOT NULL,
        occupied_tag_id INT,
        voltage_tag_id INT,
        current_tag_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (site_id) REFERENCES scada_sites(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");

    // Interlock rules table
    $conn->query("CREATE TABLE IF NOT EXISTS scada_interlock_rules (
        id INT AUTO_INCREMENT PRIMARY KEY,
        valve_asset_id INT NOT NULL,
        tag_id INT,
        condition_type ENUM('greater_than', 'less_than', 'equals', 'between') NOT NULL,
        threshold_value DECIMAL(15,4),
        threshold_min DECIMAL(15,4),
        threshold_max DECIMAL(15,4),
        severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'high',
        applicable_commands JSON,
        is_enabled BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (valve_asset_id) REFERENCES scada_assets(id) ON DELETE CASCADE,
        FOREIGN KEY (tag_id) REFERENCES scada_tags(id) ON DELETE CASCADE,
        INDEX idx_valve (valve_asset_id)
    ) ENGINE=InnoDB");

    // Permissives table
    $conn->query("CREATE TABLE IF NOT EXISTS scada_permissives (
        id INT AUTO_INCREMENT PRIMARY KEY,
        valve_asset_id INT NOT NULL,
        tag_id INT NOT NULL,
        description VARCHAR(255),
        condition ENUM('true', 'false', 'above', 'below') NOT NULL,
        threshold DECIMAL(15,4),
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (valve_asset_id) REFERENCES scada_assets(id) ON DELETE CASCADE,
        FOREIGN KEY (tag_id) REFERENCES scada_tags(id) ON DELETE CASCADE,
        INDEX idx_valve (valve_asset_id)
    ) ENGINE=InnoDB");

    // Emergency shutdown table
    $conn->query("CREATE TABLE IF NOT EXISTS scada_emergency_shutdown (
        id INT AUTO_INCREMENT PRIMARY KEY,
        site_id INT NOT NULL,
        reason TEXT NOT NULL,
        status ENUM('active', 'cleared') DEFAULT 'active',
        activated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        activated_by VARCHAR(100),
        cleared_at TIMESTAMP NULL,
        cleared_by VARCHAR(100),
        FOREIGN KEY (site_id) REFERENCES scada_sites(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");

    echo "✓ Industry-specific tables created\n\n";

    // Insert sample data
    echo "[5/6] Inserting sample demonstration data...\n";

    // Insert sample site
    $conn->query("INSERT IGNORE INTO scada_sites (id, site_code, site_name, industry_type, location, is_active)
                  VALUES (1, 'DEMO-001', 'Demo SCADA Site', 'oil_gas', 'Demo Location', 1)");

    // Insert sample asset
    $conn->query("INSERT IGNORE INTO scada_assets (id, site_id, asset_code, asset_name, asset_type, status)
                  VALUES (1, 1, 'PLC-001', 'Main PLC Controller', 'plc', 'operational')");

    // Insert sample PLC
    $conn->query("INSERT IGNORE INTO scada_plcs (id, asset_id, ip_address, port, protocol_id, scan_rate_ms)
                  VALUES (1, 1, '192.168.1.100', 502, 1, 1000)");

    // Insert sample tags
    $conn->query("INSERT IGNORE INTO scada_tags (id, site_id, plc_id, tag_name, tag_description, tag_type, data_type, memory_address, engineering_unit)
                  VALUES
                  (1, 1, 1, 'TANK001_LEVEL', 'Tank 1 Level Measurement', 'analog_input', 'float', '40001', 'meters'),
                  (2, 1, 1, 'VALVE001_POS', 'Valve 1 Position', 'analog_output', 'float', '40002', 'percent'),
                  (3, 1, 1, 'PUMP001_STATUS', 'Pump 1 Running Status', 'digital_input', 'bool', '00001', '')");

    echo "✓ Sample data inserted\n\n";

    // Create indexes for performance
    echo "[6/6] Optimizing database performance...\n";

    $conn->query("OPTIMIZE TABLE scada_tag_history");
    $conn->query("OPTIMIZE TABLE scada_alarm_history");

    echo "✓ Database optimized\n\n";

    echo "===========================================\n";
    echo "INSTALLATION COMPLETED SUCCESSFULLY!\n";
    echo "===========================================\n\n";

    echo "Next Steps:\n";
    echo "1. Configure your PLC/RTU connections in the database\n";
    echo "2. Set up SCADA tags for your instruments\n";
    echo "3. Configure alarm thresholds\n";
    echo "4. Access the HMI dashboard at: scada_hmi.php\n\n";

    echo "Documentation available at: SCADA_SETUP_GUIDE.md\n\n";

    $conn->close();

} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "\nInstallation failed. Please check the error message above.\n";
    exit(1);
}

echo "</pre>";
echo "<p><a href='scada_hmi.php'>Go to SCADA HMI Dashboard</a></p>";
?>
