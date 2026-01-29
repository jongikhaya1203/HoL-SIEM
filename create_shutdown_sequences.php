<?php
/**
 * Create Sample Shutdown Sequences
 * Creates automated shutdown and startup procedures for demonstration
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'localhost';
$username = 'root';
$password = '';
$database = 'network_security';

echo "<!DOCTYPE html><html><head><title>Create Shutdown Sequences</title>";
echo "<style>body{font-family:monospace;background:#1a1a2e;color:#0f0;padding:20px;}";
echo ".err{color:#f00;}.ok{color:#0f0;}.info{color:#0ff;}</style></head><body>";
echo "<h2>Create Sample Shutdown Sequences</h2><pre>";

try {
    $conn = new mysqli($host, $username, $password, $database);

    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    echo "<span class='ok'>✓ Connected to database</span>\n\n";

    // Get site and asset IDs
    $oilGasSite = $conn->query("SELECT id FROM scada_sites WHERE industry_type = 'oil_gas' LIMIT 1")->fetch_assoc();
    $tank101 = $conn->query("SELECT id FROM scada_assets WHERE asset_tag = 'Tank-101' LIMIT 1")->fetch_assoc();
    $valve201 = $conn->query("SELECT id FROM scada_assets WHERE asset_tag = 'Valve-201' LIMIT 1")->fetch_assoc();
    $pump301 = $conn->query("SELECT id FROM scada_assets WHERE asset_tag = 'Pump-301' LIMIT 1")->fetch_assoc();

    if (!$oilGasSite || !$tank101 || !$valve201 || !$pump301) {
        throw new Exception("Required sample data not found. Run create_sample_data.php first.");
    }

    $siteId = $oilGasSite['id'];
    $tank101Id = $tank101['id'];
    $valve201Id = $valve201['id'];
    $pump301Id = $pump301['id'];

    // Get shutdown level IDs
    $esd3Level = $conn->query("SELECT id FROM shutdown_levels WHERE level_code = 'ESD3' LIMIT 1")->fetch_assoc();
    $l1Level = $conn->query("SELECT id FROM shutdown_levels WHERE level_code = 'L1' LIMIT 1")->fetch_assoc();

    // ============================================
    // SEQUENCE 1: EMERGENCY SHUTDOWN (ESD3)
    // ============================================
    echo "<span class='info'>Creating Emergency Shutdown Sequence (ESD3)...</span>\n";

    $stmt = $conn->prepare("INSERT INTO shutdown_sequences
        (site_id, sequence_name, sequence_type, shutdown_level_id, description, estimated_duration_seconds, requires_operator_approval)
        VALUES (?, ?, ?, ?, ?, ?, ?)");

    $seqName = "Emergency Shutdown - Total Plant";
    $seqType = "emergency_stop";
    $desc = "Complete emergency shutdown of all plant operations. Immediately closes all inlet valves, stops all pumps, and isolates all equipment.";
    $duration = 180;
    $requiresApproval = 0; // Emergency doesn't need approval

    $stmt->bind_param("issiii", $siteId, $seqName, $seqType, $esd3Level['id'], $desc, $duration, $requiresApproval);
    $stmt->execute();
    $esd3SeqId = $conn->insert_id;
    echo "  ✓ ESD3 Sequence created (ID: {$esd3SeqId})\n";

    // ESD3 Steps
    $steps = [
        [1, "Close Main Inlet Valve", "Immediately close main pipeline inlet valve", "close_valve", $valve201Id, null, '{}', 10, 0, 0, 1],
        [2, "Stop Transfer Pump", "Emergency stop of transfer pump", "stop_pump", $pump301Id, null, '{}', 10, 0, 0, 1],
        [3, "Isolate Tank-101", "Close all tank isolation valves", "custom", $tank101Id, null, '{"action": "isolate"}', 15, 0, 0, 1],
        [4, "Activate ESD Alarms", "Trigger emergency shutdown alarms", "alarm", null, null, '{"message": "Emergency Shutdown ESD3 Activated", "priority": "critical"}', 5, 0, 0, 1],
        [5, "Verify Shutdown Complete", "Confirm all equipment shutdown", "check_condition", null, null, '{"verify_all_stopped": true}', 30, 0, 0, 0]
    ];

    $stepStmt = $conn->prepare("INSERT INTO shutdown_sequence_steps
        (sequence_id, step_number, step_name, step_description, action_type, target_asset_id, target_tag_id, action_params, timeout_seconds, requires_confirmation, hold_point, parallel_group)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    foreach ($steps as $step) {
        $stepStmt->bind_param("iisssiisilii", $esd3SeqId, ...$step);
        $stepStmt->execute();
    }

    echo "  ✓ {count($steps)} steps created for ESD3\n\n";

    // ============================================
    // SEQUENCE 2: NORMAL SHUTDOWN (L1)
    // ============================================
    echo "<span class='info'>Creating Normal Shutdown Sequence (L1)...</span>\n";

    $seqName = "Normal Process Shutdown";
    $seqType = "shutdown";
    $desc = "Orderly shutdown of process unit. Gradually reduces flow, stops equipment in sequence, and ensures safe isolation.";
    $duration = 600; // 10 minutes
    $requiresApproval = 1;

    $stmt->bind_param("issiii", $siteId, $seqName, $seqType, $l1Level['id'], $desc, $duration, $requiresApproval);
    $stmt->execute();
    $l1SeqId = $conn->insert_id;
    echo "  ✓ L1 Shutdown Sequence created (ID: {$l1SeqId})\n";

    // L1 Shutdown Steps
    $steps = [
        [1, "Reduce Pump Speed", "Gradually reduce pump speed to 50%", "custom", $pump301Id, null, '{"action": "set_speed", "target": 50, "rate": 10}', 60, 0, 0, 0],
        [2, "Wait for Stabilization", "Allow system to stabilize at reduced flow", "wait", null, null, '{"duration": 30}', 30, 0, 0, 0],
        [3, "Close Inlet Valve 50%", "Partially close inlet valve", "close_valve", $valve201Id, null, '{"position": 50}', 30, 0, 0, 0],
        [4, "Reduce Pump Speed to 25%", "Further reduce pump speed", "custom", $pump301Id, null, '{"action": "set_speed", "target": 25, "rate": 5}', 60, 0, 0, 0],
        [5, "Operator Confirmation", "Operator confirms system is ready for shutdown", "wait", null, null, '{}', 300, 1, 1, 0],
        [6, "Stop Transfer Pump", "Stop pump completely", "stop_pump", $pump301Id, null, '{}', 30, 0, 0, 0],
        [7, "Close Inlet Valve", "Fully close inlet valve", "close_valve", $valve201Id, null, '{}', 30, 0, 0, 0],
        [8, "Verify Zero Flow", "Confirm no flow through system", "check_condition", null, null, '{"tag": "Pump-301.Flow", "condition": "<", "value": 1.0}', 60, 0, 0, 0],
        [9, "Log Shutdown Complete", "Record shutdown completion", "alarm", null, null, '{"message": "Normal shutdown completed", "priority": "info"}', 5, 0, 0, 0]
    ];

    foreach ($steps as $step) {
        $stepStmt->bind_param("iisssiisilii", $l1SeqId, ...$step);
        $stepStmt->execute();
    }

    echo "  ✓ " . count($steps) . " steps created for L1 Shutdown\n\n";

    // ============================================
    // SEQUENCE 3: STARTUP SEQUENCE
    // ============================================
    echo "<span class='info'>Creating Startup Sequence...</span>\n";

    $seqName = "Normal Process Startup";
    $seqType = "startup";
    $desc = "Safe startup procedure for process unit. Includes pre-start checks, gradual pressurization, and equipment startup in correct sequence.";
    $duration = 900; // 15 minutes
    $requiresApproval = 1;

    $stmt->bind_param("issiii", $siteId, $seqName, $seqType, $l1Level['id'], $desc, $duration, $requiresApproval);
    $stmt->execute();
    $startupSeqId = $conn->insert_id;
    echo "  ✓ Startup Sequence created (ID: {$startupSeqId})\n";

    // Startup Steps
    $steps = [
        [1, "Pre-Start Safety Check", "Verify all safety systems operational", "check_condition", null, null, '{"checks": ["interlocks", "alarms", "emergency_systems"]}', 60, 1, 1, 0],
        [2, "Open Inlet Valve 10%", "Begin opening inlet valve slowly", "open_valve", $valve201Id, null, '{"position": 10}', 30, 0, 0, 0],
        [3, "Pressurization Check", "Monitor pressure rise", "wait", null, null, '{"duration": 60}', 60, 0, 0, 0],
        [4, "Operator Confirmation", "Operator confirms pressurization is normal", "wait", null, null, '{}', 300, 1, 1, 0],
        [5, "Open Inlet Valve 25%", "Continue opening inlet valve", "open_valve", $valve201Id, null, '{"position": 25}', 30, 0, 0, 0],
        [6, "Pre-Lube Pump", "Run pump in pre-lubrication mode", "custom", $pump301Id, null, '{"action": "pre_lube", "duration": 30}', 30, 0, 0, 0],
        [7, "Start Pump Low Speed", "Start pump at minimum speed", "start_pump", $pump301Id, null, '{"speed": 25}', 30, 0, 0, 0],
        [8, "Verify Pump Operation", "Check pump current, vibration, temperature", "check_condition", null, null, '{"tag": "Pump-301.Speed", "condition": ">", "value": 500}', 60, 0, 0, 0],
        [9, "Open Inlet Valve 50%", "Increase inlet valve position", "open_valve", $valve201Id, null, '{"position": 50}', 30, 0, 0, 0],
        [10, "Increase Pump Speed", "Gradually increase pump to 50% speed", "custom", $pump301Id, null, '{"action": "set_speed", "target": 50, "rate": 5}', 60, 0, 0, 0],
        [11, "Operator Review", "Operator reviews system performance", "wait", null, null, '{}', 300, 1, 1, 0],
        [12, "Open Inlet Valve 75%", "Continue valve opening", "open_valve", $valve201Id, null, '{"position": 75}', 30, 0, 0, 0],
        [13, "Increase Pump to Normal", "Set pump to normal operating speed", "custom", $pump301Id, null, '{"action": "set_speed", "target": 100, "rate": 10}', 90, 0, 0, 0],
        [14, "Verify Normal Operation", "Confirm all parameters within normal range", "check_condition", null, null, '{"verify_all_normal": true}', 60, 0, 0, 0],
        [15, "Log Startup Complete", "Record successful startup", "alarm", null, null, '{"message": "Startup completed - Normal operation", "priority": "info"}', 5, 0, 0, 0]
    ];

    foreach ($steps as $step) {
        $stepStmt->bind_param("iisssiisilii", $startupSeqId, ...$step);
        $stepStmt->execute();
    }

    echo "  ✓ " . count($steps) . " steps created for Startup\n\n";

    // ============================================
    // INTERLOCKS
    // ============================================
    echo "<span class='info'>Creating Safety Interlocks...</span>\n";

    // High Tank Level Interlock
    $stmt = $conn->prepare("INSERT INTO shutdown_interlocks
        (interlock_name, description, site_id, interlock_type, condition_logic, trigger_action, shutdown_sequence_id, priority)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

    $intName = "High Tank Level Protection";
    $intDesc = "Triggers shutdown if Tank-101 level exceeds 14.5m to prevent overflow";
    $intType = "safety";
    $logic = "Tank-101.Level > 14.5";
    $action = "partial_shutdown";
    $priority = 1;

    $stmt->bind_param("ssissiii", $intName, $intDesc, $siteId, $intType, $logic, $action, $l1SeqId, $priority);
    $stmt->execute();
    $interlock1Id = $conn->insert_id;

    // Add interlock conditions
    $tankLevelTag = $conn->query("SELECT id FROM scada_tags WHERE tag_name = 'Tank-101.Level' LIMIT 1")->fetch_assoc();

    $condStmt = $conn->prepare("INSERT INTO shutdown_interlock_conditions
        (interlock_id, tag_id, condition_operator, setpoint_value, logic_operator)
        VALUES (?, ?, ?, ?, ?)");

    $operator = ">";
    $setpoint = 14.5;
    $logicOp = "AND";

    $condStmt->bind_param("iisds", $interlock1Id, $tankLevelTag['id'], $operator, $setpoint, $logicOp);
    $condStmt->execute();

    echo "  ✓ High Tank Level Interlock created\n";

    // Low Suction Pressure Interlock
    $tankPressureTag = $conn->query("SELECT id FROM scada_tags WHERE tag_name = 'Tank-101.Pressure' LIMIT 1")->fetch_assoc();

    $intName = "Low Suction Pressure Protection";
    $intDesc = "Prevents pump startup if suction pressure is below 0.5 bar";
    $intType = "equipment";
    $logic = "Tank-101.Pressure < 0.5";
    $action = "prevent_startup";

    $stmt->bind_param("ssissiii", $intName, $intDesc, $siteId, $intType, $logic, $action, $startupSeqId, $priority);
    $stmt->execute();
    $interlock2Id = $conn->insert_id;

    $operator = "<";
    $setpoint = 0.5;

    $condStmt->bind_param("iisds", $interlock2Id, $tankPressureTag['id'], $operator, $setpoint, $logicOp);
    $condStmt->execute();

    echo "  ✓ Low Suction Pressure Interlock created\n\n";

    // ============================================
    // PERMISSIVES
    // ============================================
    echo "<span class='info'>Creating Startup Permissives...</span>\n";

    // Find startup step that starts pump
    $pumpStartStep = $conn->query("SELECT id FROM shutdown_sequence_steps
        WHERE sequence_id = {$startupSeqId} AND step_name = 'Start Pump Low Speed' LIMIT 1")->fetch_assoc();

    $permStmt = $conn->prepare("INSERT INTO shutdown_permissives
        (permissive_name, description, sequence_id, step_id, tag_id, required_value, tolerance)
        VALUES (?, ?, ?, ?, ?, ?, ?)");

    // Permissive: Inlet valve must be open before starting pump
    $valve201PosTag = $conn->query("SELECT id FROM scada_tags WHERE tag_name = 'Valve-201.Position' LIMIT 1")->fetch_assoc();

    $permName = "Inlet Valve Open";
    $permDesc = "Inlet valve must be at least 25% open before starting pump";
    $requiredValue = 25.0;
    $tolerance = 5.0;

    $permStmt->bind_param("ssiiidd", $permName, $permDesc, $startupSeqId, $pumpStartStep['id'], $valve201PosTag['id'], $requiredValue, $tolerance);
    $permStmt->execute();

    echo "  ✓ Inlet Valve Open permissive created\n";

    // Permissive: Tank pressure must be adequate
    $permName = "Adequate Suction Pressure";
    $permDesc = "Tank pressure must be at least 1.0 bar before starting pump";
    $requiredValue = 1.0;
    $tolerance = 0.2;

    $permStmt->bind_param("ssiiidd", $permName, $permDesc, $startupSeqId, $pumpStartStep['id'], $tankPressureTag['id'], $requiredValue, $tolerance);
    $permStmt->execute();

    echo "  ✓ Adequate Suction Pressure permissive created\n\n";

    // ============================================
    // SUCCESS
    // ============================================
    echo "<span class='ok'>========================================</span>\n";
    echo "<span class='ok'>SUCCESS! Shutdown Sequences Created</span>\n";
    echo "<span class='ok'>========================================</span>\n\n";

    echo "Summary:\n";
    echo "  ✓ Emergency Shutdown (ESD3) - 5 steps\n";
    echo "  ✓ Normal Shutdown (L1) - 9 steps\n";
    echo "  ✓ Normal Startup - 15 steps\n";
    echo "  ✓ 2 Safety Interlocks\n";
    echo "  ✓ 2 Startup Permissives\n\n";

    echo "Sequence Details:\n";
    echo "  • ESD3 (ID: {$esd3SeqId}) - Emergency total plant shutdown\n";
    echo "  • L1 Shutdown (ID: {$l1SeqId}) - Orderly process shutdown\n";
    echo "  • Startup (ID: {$startupSeqId}) - Safe startup procedure\n\n";

    echo "Next Steps:\n";
    echo "1. View sequences: <a href='shutdown_manager.php' style='color:#0ff;'>shutdown_manager.php</a>\n";
    echo "2. Test execution: <a href='shutdown_manager.php?sequence={$l1SeqId}' style='color:#0ff;'>Initiate L1 Shutdown</a>\n";
    echo "3. Monitor: <a href='scada_hmi.php' style='color:#0ff;'>SCADA HMI Dashboard</a>\n";

    $conn->close();

} catch (Exception $e) {
    echo "\n<span class='err'>❌ ERROR: " . $e->getMessage() . "</span>\n";
    exit(1);
}

echo "</pre></body></html>";
?>
