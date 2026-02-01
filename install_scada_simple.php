<?php
/**
 * SCADA System - Simple Installation Script
 * Handles installation with better error handling
 *
 * @author HoL Platform
 * @version 2.0
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>SCADA Installation</title>";
echo "<style>body{font-family:monospace;background:#1a1a2e;color:#eee;padding:20px;}";
echo ".success{color:#00ff00;}.error{color:#ff0000;}.warning{color:#ffaa00;}</style></head><body>";
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
    echo "[1/5] Connecting to MySQL server...\n";
    $conn = new mysqli($host, $username, $password);

    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    echo "<span class='success'>✓ Connected successfully</span>\n\n";

    // Create database
    echo "[2/5] Creating database...\n";
    $conn->query("CREATE DATABASE IF NOT EXISTS $database");
    $conn->select_db($database);
    echo "<span class='success'>✓ Database '$database' ready</span>\n\n";

    // Drop existing SCADA tables in correct order
    echo "[3/5] Cleaning up existing SCADA tables...\n";
    $dropTables = [
        'scada_emergency_shutdown',
        'scada_permissives',
        'scada_interlock_rules',
        'scada_alarm_history',
        'scada_tag_history',
        'scada_control_actions',
        'scada_calibration_records',
        'scada_valve_status',
        'scada_tank_levels',
        'scada_tags',
        'scada_rtus',
        'scada_plcs',
        'scada_instruments',
        'scada_assets',
        'scada_sites',
        'scada_protocols',
        'scada_industry_configs',
        'oil_gas_separators',
        'oil_gas_lact_units',
        'oil_gas_wellheads',
        'oil_gas_pipelines',
        'rail_track_circuits'
    ];

    foreach ($dropTables as $table) {
        $conn->query("DROP TABLE IF EXISTS $table");
    }
    echo "<span class='success'>✓ Cleanup completed</span>\n\n";

    // Read and execute schema
    echo "[4/5] Installing SCADA database schema...\n";
    $schemaFile = __DIR__ . '/scada_schema.sql';

    if (!file_exists($schemaFile)) {
        throw new Exception("Schema file not found: $schemaFile");
    }

    $schema = file_get_contents($schemaFile);

    // Remove comments and split by semicolons
    $lines = explode("\n", $schema);
    $statement = '';
    $successCount = 0;

    foreach ($lines as $line) {
        $line = trim($line);

        // Skip comments and empty lines
        if (empty($line) || substr($line, 0, 2) === '--' || substr($line, 0, 2) === '/*') {
            continue;
        }

        $statement .= $line . ' ';

        // Execute when we hit a semicolon
        if (substr($line, -1) === ';') {
            $statement = trim($statement);

            if (!empty($statement)) {
                if ($conn->query($statement)) {
                    $successCount++;
                } else {
                    // Only show error if it's not a DROP or DELIMITER statement
                    if (!preg_match('/(DROP|DELIMITER|CREATE EVENT|CREATE PROCEDURE)/i', $statement)) {
                        echo "<span class='warning'>Warning: " . $conn->error . "</span>\n";
                    }
                }
            }

            $statement = '';
        }
    }

    echo "<span class='success'>✓ Executed $successCount SQL statements</span>\n\n";

    // Create additional tables
    echo "[5/5] Creating additional tables...\n";

    // Oil & Gas tables
    $conn->query("CREATE TABLE IF NOT EXISTS oil_gas_pipelines (
        id INT AUTO_INCREMENT PRIMARY KEY,
        site_id INT NOT NULL,
        pipeline_name VARCHAR(100) NOT NULL,
        length_miles DECIMAL(10,2),
        diameter_inches DECIMAL(5,2),
        friction_factor DECIMAL(5,4) DEFAULT 0.02,
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
        max_casing_pressure DECIMAL(10,2) DEFAULT 3000,
        max_tubing_pressure DECIMAL(10,2) DEFAULT 2000,
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

    // Interlock and safety tables
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

    echo "<span class='success'>✓ Additional tables created</span>\n\n";

    // Insert sample data
    echo "Inserting sample data...\n";

    // Sample site
    $conn->query("INSERT IGNORE INTO scada_sites (id, site_code, site_name, industry_type, location, is_active)
                  VALUES (1, 'DEMO-001', 'Demo SCADA Site', 'oil_gas', 'Demo Location', 1)");

    // Sample asset
    $conn->query("INSERT IGNORE INTO scada_assets (id, site_id, asset_code, asset_name, asset_type, status)
                  VALUES (1, 1, 'PLC-001', 'Main PLC Controller', 'plc', 'operational')");

    // Sample PLC
    $conn->query("INSERT IGNORE INTO scada_plcs (id, asset_id, ip_address, port, protocol_id, scan_rate_ms)
                  VALUES (1, 1, '192.168.1.100', 502, 1, 1000)");

    // Sample tags
    $conn->query("INSERT IGNORE INTO scada_tags (id, site_id, plc_id, tag_name, tag_description, tag_type, data_type, memory_address, engineering_unit)
                  VALUES
                  (1, 1, 1, 'TANK001_LEVEL', 'Tank 1 Level Measurement', 'analog_input', 'float', '40001', 'meters'),
                  (2, 1, 1, 'VALVE001_POS', 'Valve 1 Position', 'analog_output', 'float', '40002', 'percent'),
                  (3, 1, 1, 'PUMP001_STATUS', 'Pump 1 Running Status', 'digital_input', 'bool', '00001', '')");

    echo "<span class='success'>✓ Sample data inserted</span>\n\n";

    echo "===========================================\n";
    echo "<span class='success'>INSTALLATION COMPLETED SUCCESSFULLY!</span>\n";
    echo "===========================================\n\n";

    echo "Next Steps:\n";
    echo "1. Access HMI Dashboard: <a href='scada_hmi.php' style='color:#00d9ff;'>scada_hmi.php</a>\n";
    echo "2. Configure your PLC/RTU connections\n";
    echo "3. Set up SCADA tags for your instruments\n";
    echo "4. Configure alarm thresholds\n\n";

    echo "Documentation: <a href='SCADA_SETUP_GUIDE.md' style='color:#00d9ff;'>SCADA_SETUP_GUIDE.md</a>\n\n";

    $conn->close();

} catch (Exception $e) {
    echo "\n<span class='error'>❌ ERROR: " . $e->getMessage() . "</span>\n";
    echo "\nInstallation failed. Please check the error message above.\n";
    echo "\nCommon fixes:\n";
    echo "1. Ensure MySQL is running\n";
    echo "2. Check database credentials in the script\n";
    echo "3. Ensure you have CREATE permissions\n";
    exit(1);
}

echo "</pre>";
echo "</body></html>";
?>
