<?php
/**
 * Complete Emergency Shutdown System Setup
 * One-click installation of the entire ESD system
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'localhost';
$username = 'root';
$password = '';
$database = 'network_security';

echo "<!DOCTYPE html><html><head><title>ESD System Setup</title>";
echo "<style>body{font-family:monospace;background:#1a1a2e;color:#0f0;padding:20px;}";
echo ".err{color:#f00;}.ok{color:#0f0;}.info{color:#0ff;}.warn{color:#fa0;}";
echo ".step{background:#2a2a3e;padding:15px;margin:10px 0;border-radius:5px;border-left:4px solid #667eea;}";
echo "</style></head><body>";
echo "<h1 style='color:#c62828;'>🔴 Emergency Shutdown System - Complete Setup</h1><pre>";

try {
    $conn = new mysqli($host, $username, $password, $database);

    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    echo "<span class='ok'>✓ Connected to database '{$database}'</span>\n\n";

    $errors = 0;
    $warnings = 0;

    // ============================================
    // STEP 1: Check Prerequisites
    // ============================================
    echo "</pre><div class='step'><pre>";
    echo "<span class='info'>STEP 1: Checking Prerequisites</span>\n";
    echo str_repeat("─", 50) . "\n";

    // Check SCADA tables exist
    $requiredTables = ['scada_sites', 'scada_assets', 'scada_tags', 'scada_plcs'];
    $missingTables = [];

    foreach ($requiredTables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '{$table}'");
        if ($result->num_rows == 0) {
            $missingTables[] = $table;
        }
    }

    if (!empty($missingTables)) {
        echo "<span class='warn'>⚠ WARNING: Required SCADA tables missing:</span>\n";
        foreach ($missingTables as $table) {
            echo "  ✗ {$table}\n";
        }
        echo "\n<span class='info'>Run install_scada_simple.php first!</span>\n";
        $warnings++;
    } else {
        echo "<span class='ok'>✓ All required SCADA tables exist</span>\n";
    }

    // Check sample data
    $siteCount = $conn->query("SELECT COUNT(*) as cnt FROM scada_sites")->fetch_assoc()['cnt'];
    if ($siteCount == 0) {
        echo "<span class='warn'>⚠ WARNING: No sample data found</span>\n";
        echo "<span class='info'>  Run create_sample_data.php for demo data</span>\n";
        $warnings++;
    } else {
        echo "<span class='ok'>✓ Found {$siteCount} site(s)</span>\n";
    }

    echo "</pre></div><pre>";

    // ============================================
    // STEP 2: Create Shutdown Tables
    // ============================================
    echo "</pre><div class='step'><pre>";
    echo "<span class='info'>STEP 2: Creating Shutdown System Tables</span>\n";
    echo str_repeat("─", 50) . "\n";

    $shutdownTables = [
        'shutdown_levels' => "CREATE TABLE IF NOT EXISTS shutdown_levels (
            id INT AUTO_INCREMENT PRIMARY KEY,
            level_code VARCHAR(10) UNIQUE NOT NULL,
            level_name VARCHAR(100) NOT NULL,
            description TEXT,
            severity INT NOT NULL,
            requires_confirmation BOOLEAN DEFAULT TRUE,
            auto_trigger_enabled BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_code (level_code)
        ) ENGINE=InnoDB",

        'shutdown_sequences' => "CREATE TABLE IF NOT EXISTS shutdown_sequences (
            id INT AUTO_INCREMENT PRIMARY KEY,
            site_id INT,
            sequence_name VARCHAR(100) NOT NULL,
            sequence_type ENUM('shutdown', 'startup', 'restart', 'emergency_stop') NOT NULL,
            shutdown_level_id INT,
            description TEXT,
            estimated_duration_seconds INT,
            requires_operator_approval BOOLEAN DEFAULT TRUE,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_site (site_id),
            INDEX idx_type (sequence_type),
            FOREIGN KEY (site_id) REFERENCES scada_sites(id) ON DELETE CASCADE,
            FOREIGN KEY (shutdown_level_id) REFERENCES shutdown_levels(id) ON DELETE SET NULL
        ) ENGINE=InnoDB",

        'shutdown_sequence_steps' => "CREATE TABLE IF NOT EXISTS shutdown_sequence_steps (
            id INT AUTO_INCREMENT PRIMARY KEY,
            sequence_id INT NOT NULL,
            step_number INT NOT NULL,
            step_name VARCHAR(100) NOT NULL,
            step_description TEXT,
            action_type ENUM('close_valve', 'open_valve', 'stop_pump', 'start_pump',
                             'shutdown_well', 'depressurize', 'isolate', 'vent',
                             'wait', 'check_condition', 'alarm', 'custom') NOT NULL,
            target_asset_id INT,
            target_tag_id INT,
            action_params JSON,
            timeout_seconds INT DEFAULT 30,
            requires_confirmation BOOLEAN DEFAULT FALSE,
            hold_point BOOLEAN DEFAULT FALSE,
            parallel_group INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_sequence (sequence_id),
            INDEX idx_step (step_number),
            INDEX idx_asset (target_asset_id),
            FOREIGN KEY (sequence_id) REFERENCES shutdown_sequences(id) ON DELETE CASCADE,
            FOREIGN KEY (target_asset_id) REFERENCES scada_assets(id) ON DELETE CASCADE,
            FOREIGN KEY (target_tag_id) REFERENCES scada_tags(id) ON DELETE SET NULL
        ) ENGINE=InnoDB",

        'shutdown_interlocks' => "CREATE TABLE IF NOT EXISTS shutdown_interlocks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            interlock_name VARCHAR(100) NOT NULL,
            description TEXT,
            site_id INT,
            interlock_type ENUM('safety', 'process', 'equipment', 'environmental') NOT NULL,
            condition_logic TEXT NOT NULL,
            trigger_action ENUM('alarm', 'partial_shutdown', 'full_shutdown', 'prevent_startup') NOT NULL,
            shutdown_sequence_id INT,
            priority INT DEFAULT 1,
            is_active BOOLEAN DEFAULT TRUE,
            bypass_allowed BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_site (site_id),
            INDEX idx_active (is_active),
            FOREIGN KEY (site_id) REFERENCES scada_sites(id) ON DELETE CASCADE,
            FOREIGN KEY (shutdown_sequence_id) REFERENCES shutdown_sequences(id) ON DELETE SET NULL
        ) ENGINE=InnoDB",

        'shutdown_interlock_conditions' => "CREATE TABLE IF NOT EXISTS shutdown_interlock_conditions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            interlock_id INT NOT NULL,
            tag_id INT NOT NULL,
            condition_operator ENUM('>', '<', '>=', '<=', '=', '!=', 'IN_RANGE', 'OUT_OF_RANGE') NOT NULL,
            setpoint_value DECIMAL(15,4),
            range_min DECIMAL(15,4),
            range_max DECIMAL(15,4),
            logic_operator ENUM('AND', 'OR') DEFAULT 'AND',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_interlock (interlock_id),
            INDEX idx_tag (tag_id),
            FOREIGN KEY (interlock_id) REFERENCES shutdown_interlocks(id) ON DELETE CASCADE,
            FOREIGN KEY (tag_id) REFERENCES scada_tags(id) ON DELETE CASCADE
        ) ENGINE=InnoDB",

        'shutdown_permissives' => "CREATE TABLE IF NOT EXISTS shutdown_permissives (
            id INT AUTO_INCREMENT PRIMARY KEY,
            permissive_name VARCHAR(100) NOT NULL,
            description TEXT,
            sequence_id INT,
            step_id INT,
            tag_id INT NOT NULL,
            required_value DECIMAL(15,4),
            required_state ENUM('true', 'false', 'any'),
            tolerance DECIMAL(15,4) DEFAULT 0,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_sequence (sequence_id),
            INDEX idx_step (step_id),
            INDEX idx_tag (tag_id),
            FOREIGN KEY (sequence_id) REFERENCES shutdown_sequences(id) ON DELETE CASCADE,
            FOREIGN KEY (step_id) REFERENCES shutdown_sequence_steps(id) ON DELETE CASCADE,
            FOREIGN KEY (tag_id) REFERENCES scada_tags(id) ON DELETE CASCADE
        ) ENGINE=InnoDB",

        'shutdown_executions' => "CREATE TABLE IF NOT EXISTS shutdown_executions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            sequence_id INT NOT NULL,
            execution_status ENUM('pending', 'running', 'paused', 'completed', 'failed', 'aborted') NOT NULL,
            initiated_by VARCHAR(100),
            initiated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            completed_at TIMESTAMP NULL,
            current_step_id INT,
            reason TEXT,
            is_emergency BOOLEAN DEFAULT FALSE,
            bypass_interlocks BOOLEAN DEFAULT FALSE,
            approval_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            approved_by VARCHAR(100),
            approved_at TIMESTAMP NULL,
            INDEX idx_sequence (sequence_id),
            INDEX idx_status (execution_status),
            INDEX idx_initiated (initiated_at),
            FOREIGN KEY (sequence_id) REFERENCES shutdown_sequences(id) ON DELETE CASCADE,
            FOREIGN KEY (current_step_id) REFERENCES shutdown_sequence_steps(id) ON DELETE SET NULL
        ) ENGINE=InnoDB",

        'shutdown_execution_logs' => "CREATE TABLE IF NOT EXISTS shutdown_execution_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            execution_id INT NOT NULL,
            step_id INT,
            log_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            log_level ENUM('INFO', 'WARNING', 'ERROR', 'SUCCESS') NOT NULL,
            message TEXT NOT NULL,
            tag_value DECIMAL(15,4),
            expected_value DECIMAL(15,4),
            additional_data JSON,
            INDEX idx_execution (execution_id),
            INDEX idx_time (log_time),
            FOREIGN KEY (execution_id) REFERENCES shutdown_executions(id) ON DELETE CASCADE,
            FOREIGN KEY (step_id) REFERENCES shutdown_sequence_steps(id) ON DELETE SET NULL
        ) ENGINE=InnoDB",

        'well_shutdown_procedures' => "CREATE TABLE IF NOT EXISTS well_shutdown_procedures (
            id INT AUTO_INCREMENT PRIMARY KEY,
            well_id INT NOT NULL,
            procedure_type ENUM('normal_shutdown', 'emergency_shutdown', 'maintenance_shutdown') NOT NULL,
            choke_close_rate DECIMAL(5,2) DEFAULT 10.0,
            depressure_rate DECIMAL(10,4),
            final_pressure_target DECIMAL(10,4),
            isolation_valves JSON,
            vent_duration_seconds INT,
            monitoring_tags JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_well (well_id)
        ) ENGINE=InnoDB",

        'dcs_interface_commands' => "CREATE TABLE IF NOT EXISTS dcs_interface_commands (
            id INT AUTO_INCREMENT PRIMARY KEY,
            execution_id INT,
            command_type ENUM('setpoint', 'valve_command', 'pump_command', 'mode_change', 'alarm_ack') NOT NULL,
            target_dcs VARCHAR(100),
            target_point VARCHAR(100),
            command_value VARCHAR(255),
            command_status ENUM('pending', 'sent', 'confirmed', 'failed') DEFAULT 'pending',
            sent_at TIMESTAMP NULL,
            confirmed_at TIMESTAMP NULL,
            error_message TEXT,
            retry_count INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_execution (execution_id),
            INDEX idx_status (command_status),
            FOREIGN KEY (execution_id) REFERENCES shutdown_executions(id) ON DELETE CASCADE
        ) ENGINE=InnoDB"
    ];

    foreach ($shutdownTables as $tableName => $createSql) {
        try {
            $conn->query($createSql);
            echo "<span class='ok'>✓ {$tableName}</span>\n";
        } catch (Exception $e) {
            echo "<span class='err'>✗ {$tableName}: " . $e->getMessage() . "</span>\n";
            $errors++;
        }
    }

    // Insert default shutdown levels
    echo "\n<span class='info'>Inserting default shutdown levels...</span>\n";
    $conn->query("INSERT IGNORE INTO shutdown_levels (level_code, level_name, description, severity) VALUES
        ('L0', 'Normal Operation', 'All systems operational', 0),
        ('L1', 'Process Shutdown', 'Partial shutdown of specific process units', 1),
        ('L2', 'Unit Shutdown', 'Complete shutdown of production unit', 2),
        ('L3', 'Plant Shutdown', 'Full plant shutdown with safe isolation', 3),
        ('ESD1', 'Emergency Shutdown Level 1', 'Process emergency shutdown', 4),
        ('ESD2', 'Emergency Shutdown Level 2', 'Unit emergency shutdown', 5),
        ('ESD3', 'Emergency Shutdown Level 3', 'Total plant emergency shutdown', 6)
    ");
    echo "<span class='ok'>✓ Shutdown levels inserted</span>\n";

    echo "</pre></div><pre>";

    // ============================================
    // STEP 3: Add to Modules
    // ============================================
    echo "</pre><div class='step'><pre>";
    echo "<span class='info'>STEP 3: Adding to Dashboard Modules</span>\n";
    echo str_repeat("─", 50) . "\n";

    // Create modules table if doesn't exist
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

    // Insert/Update ESD module
    $stmt = $conn->prepare("INSERT INTO modules
        (module_code, module_name, description, category, url, icon, status, implementation_level, enabled, display_order)
        VALUES ('ESD', 'Emergency Shutdown System', 'Automated plant shutdown and startup management', 'safety_systems', 'shutdown_manager.php', '🔴', 'active', 'full', 1, 2)
        ON DUPLICATE KEY UPDATE
        module_name = VALUES(module_name),
        description = VALUES(description),
        url = VALUES(url),
        icon = VALUES(icon),
        status = VALUES(status),
        implementation_level = VALUES(implementation_level)");

    $stmt->execute();
    echo "<span class='ok'>✓ Emergency Shutdown module added to dashboard</span>\n";

    echo "</pre></div><pre>";

    // ============================================
    // STEP 4: Summary
    // ============================================
    echo "\n";
    echo "<span class='ok'>========================================</span>\n";
    echo "<span class='ok'>SETUP COMPLETE!</span>\n";
    echo "<span class='ok'>========================================</span>\n\n";

    if ($errors > 0) {
        echo "<span class='err'>⚠ {$errors} error(s) encountered</span>\n";
    }
    if ($warnings > 0) {
        echo "<span class='warn'>⚠ {$warnings} warning(s)</span>\n";
    }
    if ($errors == 0 && $warnings == 0) {
        echo "<span class='ok'>✓ No errors or warnings!</span>\n";
    }

    echo "\nTables Created:\n";
    echo "  ✓ shutdown_levels\n";
    echo "  ✓ shutdown_sequences\n";
    echo "  ✓ shutdown_sequence_steps\n";
    echo "  ✓ shutdown_interlocks\n";
    echo "  ✓ shutdown_interlock_conditions\n";
    echo "  ✓ shutdown_permissives\n";
    echo "  ✓ shutdown_executions\n";
    echo "  ✓ shutdown_execution_logs\n";
    echo "  ✓ well_shutdown_procedures\n";
    echo "  ✓ dcs_interface_commands\n\n";

    echo "Next Steps:\n";
    echo "1. Create sample sequences:\n";
    echo "   <a href='create_shutdown_sequences.php' style='color:#0ff;'>→ create_shutdown_sequences.php</a>\n\n";

    echo "2. Access Shutdown Manager:\n";
    echo "   <a href='shutdown_manager.php' style='color:#0ff;'>→ shutdown_manager.php</a>\n\n";

    echo "3. View Dashboard:\n";
    echo "   <a href='index.php' style='color:#0ff;'>→ index.php</a>\n\n";

    echo "4. Read Documentation:\n";
    echo "   📖 SHUTDOWN_SYSTEM_README.md\n\n";

    $conn->close();

} catch (Exception $e) {
    echo "\n<span class='err'>❌ FATAL ERROR: " . $e->getMessage() . "</span>\n";
    exit(1);
}

echo "</pre>";
echo "<div style='margin-top:30px;'>";
echo "<a href='create_shutdown_sequences.php' style='background:#4caf50;color:white;padding:15px 30px;text-decoration:none;border-radius:5px;display:inline-block;margin:5px;'>Create Sample Sequences</a>";
echo "<a href='shutdown_manager.php' style='background:#c62828;color:white;padding:15px 30px;text-decoration:none;border-radius:5px;display:inline-block;margin:5px;'>Open Shutdown Manager</a>";
echo "<a href='index.php' style='background:#667eea;color:white;padding:15px 30px;text-decoration:none;border-radius:5px;display:inline-block;margin:5px;'>Go to Dashboard</a>";
echo "</div>";
echo "</body></html>";
?>
