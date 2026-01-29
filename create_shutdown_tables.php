<?php
/**
 * Create Emergency Shutdown (ESD) System Tables
 * Based on IEC 61511, ISA-84, and API RP 14C standards
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'localhost';
$username = 'root';
$password = '';
$database = 'network_security';

echo "<!DOCTYPE html><html><head><title>Create ESD Shutdown Tables</title>";
echo "<style>body{font-family:monospace;background:#1a1a2e;color:#0f0;padding:20px;}";
echo ".err{color:#f00;}.ok{color:#0f0;}.info{color:#0ff;}</style></head><body>";
echo "<h2>Create Emergency Shutdown System Tables</h2><pre>";

try {
    $conn = new mysqli($host, $username, $password, $database);

    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    echo "<span class='ok'>✓ Connected to database</span>\n\n";

    // ============================================
    // 1. SHUTDOWN LEVELS
    // ============================================
    echo "Creating shutdown_levels table...\n";
    $conn->query("CREATE TABLE IF NOT EXISTS shutdown_levels (
        id INT AUTO_INCREMENT PRIMARY KEY,
        level_code VARCHAR(10) UNIQUE NOT NULL,
        level_name VARCHAR(100) NOT NULL,
        description TEXT,
        severity INT NOT NULL,
        requires_confirmation BOOLEAN DEFAULT TRUE,
        auto_trigger_enabled BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_code (level_code)
    ) ENGINE=InnoDB");
    echo "<span class='ok'>✓ shutdown_levels table created</span>\n";

    // ============================================
    // 2. SHUTDOWN SEQUENCES
    // ============================================
    echo "Creating shutdown_sequences table...\n";
    $conn->query("CREATE TABLE IF NOT EXISTS shutdown_sequences (
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
    ) ENGINE=InnoDB");
    echo "<span class='ok'>✓ shutdown_sequences table created</span>\n";

    // ============================================
    // 3. SHUTDOWN SEQUENCE STEPS
    // ============================================
    echo "Creating shutdown_sequence_steps table...\n";
    $conn->query("CREATE TABLE IF NOT EXISTS shutdown_sequence_steps (
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
    ) ENGINE=InnoDB");
    echo "<span class='ok'>✓ shutdown_sequence_steps table created</span>\n";

    // ============================================
    // 4. SHUTDOWN INTERLOCKS
    // ============================================
    echo "Creating shutdown_interlocks table...\n";
    $conn->query("CREATE TABLE IF NOT EXISTS shutdown_interlocks (
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
    ) ENGINE=InnoDB");
    echo "<span class='ok'>✓ shutdown_interlocks table created</span>\n";

    // ============================================
    // 5. SHUTDOWN INTERLOCK CONDITIONS
    // ============================================
    echo "Creating shutdown_interlock_conditions table...\n";
    $conn->query("CREATE TABLE IF NOT EXISTS shutdown_interlock_conditions (
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
    ) ENGINE=InnoDB");
    echo "<span class='ok'>✓ shutdown_interlock_conditions table created</span>\n";

    // ============================================
    // 6. SHUTDOWN PERMISSIVES
    // ============================================
    echo "Creating shutdown_permissives table...\n";
    $conn->query("CREATE TABLE IF NOT EXISTS shutdown_permissives (
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
    ) ENGINE=InnoDB");
    echo "<span class='ok'>✓ shutdown_permissives table created</span>\n";

    // ============================================
    // 7. SHUTDOWN EXECUTIONS
    // ============================================
    echo "Creating shutdown_executions table...\n";
    $conn->query("CREATE TABLE IF NOT EXISTS shutdown_executions (
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
    ) ENGINE=InnoDB");
    echo "<span class='ok'>✓ shutdown_executions table created</span>\n";

    // ============================================
    // 8. SHUTDOWN EXECUTION LOGS
    // ============================================
    echo "Creating shutdown_execution_logs table...\n";
    $conn->query("CREATE TABLE IF NOT EXISTS shutdown_execution_logs (
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
    ) ENGINE=InnoDB");
    echo "<span class='ok'>✓ shutdown_execution_logs table created</span>\n";

    // ============================================
    // 9. WELL SHUTDOWN PROCEDURES
    // ============================================
    echo "Creating well_shutdown_procedures table...\n";
    $conn->query("CREATE TABLE IF NOT EXISTS well_shutdown_procedures (
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
    ) ENGINE=InnoDB");
    echo "<span class='ok'>✓ well_shutdown_procedures table created</span>\n";

    // ============================================
    // 10. DCS INTERFACE COMMANDS
    // ============================================
    echo "Creating dcs_interface_commands table...\n";
    $conn->query("CREATE TABLE IF NOT EXISTS dcs_interface_commands (
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
    ) ENGINE=InnoDB");
    echo "<span class='ok'>✓ dcs_interface_commands table created</span>\n";

    // ============================================
    // INSERT DEFAULT SHUTDOWN LEVELS
    // ============================================
    echo "\nInserting default shutdown levels...\n";
    $conn->query("INSERT IGNORE INTO shutdown_levels (level_code, level_name, description, severity) VALUES
        ('L0', 'Normal Operation', 'All systems operational', 0),
        ('L1', 'Process Shutdown', 'Partial shutdown of specific process units', 1),
        ('L2', 'Unit Shutdown', 'Complete shutdown of production unit', 2),
        ('L3', 'Plant Shutdown', 'Full plant shutdown with safe isolation', 3),
        ('ESD1', 'Emergency Shutdown Level 1', 'Process emergency shutdown', 4),
        ('ESD2', 'Emergency Shutdown Level 2', 'Unit emergency shutdown', 5),
        ('ESD3', 'Emergency Shutdown Level 3', 'Total plant emergency shutdown', 6)
    ");
    echo "<span class='ok'>✓ Default shutdown levels inserted</span>\n";

    echo "\n";
    echo "<span class='ok'>========================================</span>\n";
    echo "<span class='ok'>SUCCESS! ESD Shutdown Tables Created</span>\n";
    echo "<span class='ok'>========================================</span>\n\n";

    echo "Tables created:\n";
    echo "  ✓ shutdown_levels - Shutdown severity levels\n";
    echo "  ✓ shutdown_sequences - Automated procedures\n";
    echo "  ✓ shutdown_sequence_steps - Individual actions\n";
    echo "  ✓ shutdown_interlocks - Safety interlocks\n";
    echo "  ✓ shutdown_interlock_conditions - Interlock logic\n";
    echo "  ✓ shutdown_permissives - Startup conditions\n";
    echo "  ✓ shutdown_executions - Execution tracking\n";
    echo "  ✓ shutdown_execution_logs - Audit trail\n";
    echo "  ✓ well_shutdown_procedures - Well-specific procedures\n";
    echo "  ✓ dcs_interface_commands - DCS integration\n\n";

    echo "Next steps:\n";
    echo "1. Run: <a href='create_shutdown_sequences.php' style='color:#0ff;'>create_shutdown_sequences.php</a> to create sample sequences\n";
    echo "2. Access: <a href='shutdown_manager.php' style='color:#0ff;'>shutdown_manager.php</a> for shutdown management\n";
    echo "3. View: <a href='scada_hmi.php' style='color:#0ff;'>scada_hmi.php</a> to see integrated shutdown controls\n";

    $conn->close();

} catch (Exception $e) {
    echo "\n<span class='err'>❌ ERROR: " . $e->getMessage() . "</span>\n";
    exit(1);
}

echo "</pre></body></html>";
?>
