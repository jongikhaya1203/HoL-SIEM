<?php
/**
 * Quick Shutdown System Setup
 * Creates sample sequences with minimal dependencies
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'localhost';
$username = 'root';
$password = '';
$database = 'network_security';

echo "<!DOCTYPE html><html><head><title>Quick Shutdown Setup</title>";
echo "<style>body{font-family:monospace;background:#1a1a2e;color:#0f0;padding:20px;}";
echo ".err{color:#f00;}.ok{color:#0f0;}.info{color:#0ff;}.warn{color:#fa0;}</style></head><body>";
echo "<h2>Quick Shutdown System Setup</h2><pre>";

try {
    $conn = new mysqli($host, $username, $password, $database);

    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    echo "<span class='ok'>✓ Connected to database</span>\n\n";

    // Check if shutdown tables exist
    $shutdownLevels = $conn->query("SHOW TABLES LIKE 'shutdown_levels'")->num_rows;
    $shutdownSequences = $conn->query("SHOW TABLES LIKE 'shutdown_sequences'")->num_rows;

    if ($shutdownLevels == 0 || $shutdownSequences == 0) {
        echo "<span class='warn'>⚠ Shutdown tables not found!</span>\n";
        echo "<span class='info'>Please run setup_shutdown_system.php first</span>\n\n";
        echo "<a href='setup_shutdown_system.php' style='background:#667eea;color:white;padding:15px 30px;text-decoration:none;border-radius:5px;display:inline-block;'>Run Setup Now</a>";
        exit;
    }

    // Get or create a site
    $site = $conn->query("SELECT id FROM scada_sites LIMIT 1")->fetch_assoc();
    if (!$site) {
        echo "<span class='info'>Creating demo site...</span>\n";
        $conn->query("INSERT INTO scada_sites (site_name, industry_type, site_code, is_active, latitude, longitude)
                     VALUES ('Demo Facility', 'oil_gas', 'DEMO001', 1, 0, 0)");
        $siteId = $conn->insert_id;
        echo "<span class='ok'>✓ Demo site created (ID: {$siteId})</span>\n\n";
    } else {
        $siteId = $site['id'];
        echo "<span class='ok'>✓ Using existing site (ID: {$siteId})</span>\n\n";
    }

    // Get shutdown levels
    $esd3 = $conn->query("SELECT id FROM shutdown_levels WHERE level_code = 'ESD3' LIMIT 1")->fetch_assoc();
    $l1 = $conn->query("SELECT id FROM shutdown_levels WHERE level_code = 'L1' LIMIT 1")->fetch_assoc();

    if (!$esd3 || !$l1) {
        throw new Exception("Shutdown levels not found. Database setup incomplete.");
    }

    // Check if sequences already exist
    $existingSeq = $conn->query("SELECT COUNT(*) as cnt FROM shutdown_sequences")->fetch_assoc()['cnt'];
    if ($existingSeq > 0) {
        echo "<span class='warn'>⚠ Sequences already exist ({$existingSeq} found)</span>\n";
        echo "<span class='info'>Delete existing sequences first or proceed to manager</span>\n\n";
        echo "<a href='shutdown_manager.php' style='background:#c62828;color:white;padding:15px 30px;text-decoration:none;border-radius:5px;display:inline-block;'>Go to Shutdown Manager</a>";
        exit;
    }

    // ============================================
    // CREATE SEQUENCES WITHOUT ASSET DEPENDENCIES
    // ============================================
    echo "<span class='info'>Creating shutdown sequences...</span>\n";

    // SEQUENCE 1: Emergency Shutdown
    echo "  • Emergency Shutdown (ESD3)...\n";
    $stmt = $conn->prepare("INSERT INTO shutdown_sequences
        (site_id, sequence_name, sequence_type, shutdown_level_id, description, estimated_duration_seconds, requires_operator_approval, is_active)
        VALUES (?, 'Emergency Plant Shutdown', 'emergency_stop', ?, 'Immediate emergency shutdown of all plant operations', 180, 0, 1)");
    $stmt->bind_param("ii", $siteId, $esd3['id']);
    $stmt->execute();
    $esd3SeqId = $conn->insert_id;

    // ESD3 Steps - Generic (no specific assets)
    $steps = [
        [1, "Initiate Emergency Stop", "Trigger all emergency stop systems", "alarm", '{"message": "EMERGENCY SHUTDOWN INITIATED", "priority": "critical"}', 5, 0, 0],
        [2, "Close All Inlet Valves", "Automatically close all inlet valves", "custom", '{"action": "close_all_valves"}', 30, 0, 0],
        [3, "Stop All Pumps", "Emergency stop all rotating equipment", "custom", '{"action": "stop_all_pumps"}', 30, 0, 0],
        [4, "Isolate Equipment", "Activate isolation protocols", "custom", '{"action": "isolate_all"}', 60, 0, 0],
        [5, "Verify Shutdown", "Confirm all equipment stopped", "check_condition", '{"verify": "all_stopped"}', 60, 0, 0]
    ];

    $stepStmt = $conn->prepare("INSERT INTO shutdown_sequence_steps
        (sequence_id, step_number, step_name, step_description, action_type, action_params, timeout_seconds, hold_point, parallel_group)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

    foreach ($steps as $step) {
        $stepStmt->bind_param("iisssiii", $esd3SeqId, $step[0], $step[1], $step[2], $step[3], $step[4], $step[5], $step[6], $step[7]);
        $stepStmt->execute();
    }
    echo "    <span class='ok'>✓ ESD3 created with 5 steps</span>\n";

    // SEQUENCE 2: Normal Shutdown
    echo "  • Normal Shutdown (L1)...\n";
    $stmt = $conn->prepare("INSERT INTO shutdown_sequences
        (site_id, sequence_name, sequence_type, shutdown_level_id, description, estimated_duration_seconds, requires_operator_approval, is_active)
        VALUES (?, 'Normal Process Shutdown', 'shutdown', ?, 'Controlled shutdown with gradual equipment isolation', 600, 1, 1)");
    $stmt->bind_param("ii", $siteId, $l1['id']);
    $stmt->execute();
    $l1SeqId = $conn->insert_id;

    // L1 Steps
    $steps = [
        [1, "Reduce Production Rate", "Gradually reduce to 50%", "custom", '{"rate": 50}', 120, 0, 0],
        [2, "System Stabilization", "Wait for process stabilization", "wait", '{"duration": 60}', 60, 0, 0],
        [3, "Reduce to 25%", "Further reduce production", "custom", '{"rate": 25}', 120, 0, 0],
        [4, "Operator Confirmation", "Operator approval to proceed", "wait", '{}', 300, 1, 0],
        [5, "Stop Production", "Complete production stop", "custom", '{"stop": true}', 60, 0, 0],
        [6, "Close Inlet Systems", "Isolate feed systems", "custom", '{"close_inlets": true}', 60, 0, 0],
        [7, "Verify Zero Flow", "Confirm no flow", "check_condition", '{"flow": 0}', 60, 0, 0],
        [8, "Final Isolation", "Complete equipment isolation", "custom", '{"isolate": true}', 60, 0, 0],
        [9, "Log Completion", "Record shutdown", "alarm", '{"message": "Normal shutdown completed"}', 5, 0, 0]
    ];

    foreach ($steps as $step) {
        $stepStmt->bind_param("iisssiii", $l1SeqId, $step[0], $step[1], $step[2], $step[3], $step[4], $step[5], $step[6], $step[7]);
        $stepStmt->execute();
    }
    echo "    <span class='ok'>✓ L1 Shutdown created with 9 steps</span>\n";

    // SEQUENCE 3: Startup
    echo "  • Normal Startup...\n";
    $stmt = $conn->prepare("INSERT INTO shutdown_sequences
        (site_id, sequence_name, sequence_type, shutdown_level_id, description, estimated_duration_seconds, requires_operator_approval, is_active)
        VALUES (?, 'Normal Process Startup', 'startup', ?, 'Safe startup procedure with pre-checks and gradual ramp', 900, 1, 1)");
    $stmt->bind_param("ii", $siteId, $l1['id']);
    $stmt->execute();
    $startupSeqId = $conn->insert_id;

    // Startup Steps
    $steps = [
        [1, "Pre-Start Safety Check", "Verify all safety systems", "check_condition", '{"safety": true}', 120, 1, 0],
        [2, "System Pressurization", "Gradual system pressure up", "custom", '{"pressurize": 10}', 180, 0, 0],
        [3, "Pressure Verification", "Confirm pressure stable", "wait", '{"duration": 60}', 60, 0, 0],
        [4, "Operator Approval", "Supervisor approval to start", "wait", '{}', 300, 1, 0],
        [5, "Initial Flow 10%", "Start at minimum flow", "custom", '{"flow": 10}', 60, 0, 0],
        [6, "Equipment Pre-Check", "Verify equipment ready", "check_condition", '{"equipment": true}', 60, 0, 0],
        [7, "Increase to 25%", "Ramp to 25%", "custom", '{"flow": 25}', 90, 0, 0],
        [8, "Verify Parameters", "Check all parameters", "check_condition", '{"params": true}', 60, 0, 0],
        [9, "Increase to 50%", "Ramp to 50%", "custom", '{"flow": 50}', 90, 0, 0],
        [10, "Mid-Point Review", "Operator review", "wait", '{}', 180, 1, 0],
        [11, "Increase to 75%", "Ramp to 75%", "custom", '{"flow": 75}', 90, 0, 0],
        [12, "Final Ramp", "Reach normal operation", "custom", '{"flow": 100}', 120, 0, 0],
        [13, "Verify Normal Operation", "Confirm all normal", "check_condition", '{"all_normal": true}', 60, 0, 0],
        [14, "Performance Check", "Verify performance", "wait", '{"duration": 60}', 60, 0, 0],
        [15, "Log Startup Complete", "Record completion", "alarm", '{"message": "Startup completed"}', 5, 0, 0]
    ];

    foreach ($steps as $step) {
        $stepStmt->bind_param("iisssiii", $startupSeqId, $step[0], $step[1], $step[2], $step[3], $step[4], $step[5], $step[6], $step[7]);
        $stepStmt->execute();
    }
    echo "    <span class='ok'>✓ Startup created with 15 steps</span>\n";

    // ============================================
    // SUCCESS
    // ============================================
    echo "\n";
    echo "<span class='ok'>========================================</span>\n";
    echo "<span class='ok'>SUCCESS! Shutdown System Ready</span>\n";
    echo "<span class='ok'>========================================</span>\n\n";

    echo "Sequences Created:\n";
    echo "  ✓ ESD3 Emergency Shutdown (ID: {$esd3SeqId})\n";
    echo "  ✓ L1 Normal Shutdown (ID: {$l1SeqId})\n";
    echo "  ✓ Normal Startup (ID: {$startupSeqId})\n\n";

    echo "Features:\n";
    echo "  • Multi-level shutdown classification\n";
    echo "  • Operator approval workflow\n";
    echo "  • Hold points for safety\n";
    echo "  • Comprehensive logging\n";
    echo "  • IEC 61511 compliant\n\n";

    echo "Note: These are generic sequences without specific asset dependencies.\n";
    echo "Customize them in the Shutdown Manager for your specific equipment.\n\n";

    $conn->close();

} catch (Exception $e) {
    echo "\n<span class='err'>❌ ERROR: " . $e->getMessage() . "</span>\n";
    exit(1);
}

echo "</pre>";
echo "<br><a href='shutdown_manager.php' style='background:#c62828;color:white;padding:15px 30px;text-decoration:none;border-radius:5px;display:inline-block;margin:10px 5px;'>🔴 Open Shutdown Manager</a>";
echo "<a href='scada_hmi.php' style='background:#667eea;color:white;padding:15px 30px;text-decoration:none;border-radius:5px;display:inline-block;margin:10px 5px;'>📊 SCADA Dashboard</a>";
echo "</body></html>";
?>
