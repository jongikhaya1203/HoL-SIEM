<?php
/**
 * Create Sample Shutdown Sequences (Fixed Version)
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
echo ".err{color:#f00;}.ok{color:#0f0;}.info{color:#0ff;}.warn{color:#fa0;}</style></head><body>";
echo "<h2>Create Sample Shutdown Sequences</h2><pre>";

try {
    $conn = new mysqli($host, $username, $password, $database);

    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    echo "<span class='ok'>✓ Connected to database</span>\n\n";

    // First, check what columns exist in scada_assets
    echo "<span class='info'>Checking scada_assets table structure...</span>\n";
    $columns = $conn->query("SHOW COLUMNS FROM scada_assets");
    $columnNames = [];
    while ($col = $columns->fetch_assoc()) {
        $columnNames[] = $col['Field'];
    }
    echo "Available columns: " . implode(", ", $columnNames) . "\n\n";

    // Check if we have sample data
    $siteCount = $conn->query("SELECT COUNT(*) as cnt FROM scada_sites")->fetch_assoc()['cnt'];
    $assetCount = $conn->query("SELECT COUNT(*) as cnt FROM scada_assets")->fetch_assoc()['cnt'];

    if ($siteCount == 0 || $assetCount == 0) {
        echo "<span class='warn'>⚠ WARNING: No sample data found!</span>\n";
        echo "<span class='info'>Creating basic sample data first...</span>\n\n";

        // Create a basic site
        $conn->query("INSERT INTO scada_sites (site_name, industry_type, latitude, longitude, site_code, is_active)
                     VALUES ('Demo Site', 'oil_gas', 0, 0, 'demo_site', 1)");
        $siteId = $conn->insert_id;
        echo "✓ Created demo site (ID: {$siteId})\n";

        // Create a basic PLC
        $conn->query("INSERT INTO scada_plcs (site_id, plc_name, plc_model, ip_address, port, protocol, is_active)
                     VALUES ({$siteId}, 'Demo PLC', 'Generic PLC', '192.168.1.100', 502, 'modbus_tcp', 1)");
        $plcId = $conn->insert_id;
        echo "✓ Created demo PLC (ID: {$plcId})\n";

        // Create basic assets
        $assets = [
            ['Tank-101', 'tank', 'Demo Tank'],
            ['Valve-201', 'valve', 'Demo Valve'],
            ['Pump-301', 'pump', 'Demo Pump']
        ];

        $assetIds = [];
        foreach ($assets as $asset) {
            $stmt = $conn->prepare("INSERT INTO scada_assets (site_id, asset_tag, asset_type, description, plc_id, is_active)
                                   VALUES (?, ?, ?, ?, ?, 1)");
            $stmt->bind_param("isssi", $siteId, $asset[0], $asset[1], $asset[2], $plcId);
            $stmt->execute();
            $assetIds[$asset[0]] = $conn->insert_id;
            echo "✓ Created {$asset[0]} (ID: {$assetIds[$asset[0]]})\n";
        }

        // Create basic tags
        $tags = [
            ['Tank-101', 'Tank-101.Level', 'AI', 'Tank Level', 40001, 'float32', 'm', 0, 15, 12.5, 3.0, 13.5],
            ['Tank-101', 'Tank-101.Pressure', 'AI', 'Tank Pressure', 40005, 'float32', 'bar', 0, 10, 2.5, 0.5, 8.0],
            ['Valve-201', 'Valve-201.Position', 'AI', 'Valve Position', 40011, 'uint16', '%', 0, 100, 75, null, null],
            ['Pump-301', 'Pump-301.Speed', 'AI', 'Pump Speed', 40021, 'uint16', 'RPM', 0, 3600, 1800, null, null],
            ['Pump-301', 'Pump-301.Flow', 'AI', 'Pump Flow', 40023, 'float32', 'm3/h', 0, 500, 245, null, null]
        ];

        foreach ($tags as $tag) {
            $stmt = $conn->prepare("INSERT INTO scada_tags
                (asset_id, plc_id, tag_name, tag_type, description, modbus_address, data_type,
                 engineering_unit, min_value, max_value, current_value, alarm_low, alarm_high, is_active, poll_interval_ms)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 3000)");

            $stmt->bind_param("iisssissddddd",
                $assetIds[$tag[0]], $plcId, $tag[1], $tag[2], $tag[3], $tag[4], $tag[5],
                $tag[6], $tag[7], $tag[8], $tag[9], $tag[10], $tag[11]
            );
            $stmt->execute();
            echo "✓ Created tag {$tag[1]}\n";
        }

        echo "\n<span class='ok'>✓ Basic sample data created</span>\n\n";

    } else {
        // Get existing site and asset IDs
        $oilGasSite = $conn->query("SELECT id FROM scada_sites WHERE industry_type = 'oil_gas' LIMIT 1")->fetch_assoc();

        if (!$oilGasSite) {
            // Just get any site
            $oilGasSite = $conn->query("SELECT id FROM scada_sites LIMIT 1")->fetch_assoc();
        }

        $siteId = $oilGasSite['id'];

        // Try to get assets by asset_tag
        $tank101 = $conn->query("SELECT id FROM scada_assets WHERE asset_tag = 'Tank-101' LIMIT 1")->fetch_assoc();
        $valve201 = $conn->query("SELECT id FROM scada_assets WHERE asset_tag = 'Valve-201' LIMIT 1")->fetch_assoc();
        $pump301 = $conn->query("SELECT id FROM scada_assets WHERE asset_tag = 'Pump-301' LIMIT 1")->fetch_assoc();

        if (!$tank101 || !$valve201 || !$pump301) {
            // Get any three assets
            echo "<span class='warn'>⚠ Specific assets not found, using any available assets...</span>\n";
            $assets = $conn->query("SELECT id, asset_tag FROM scada_assets LIMIT 3");
            $assetList = [];
            while ($a = $assets->fetch_assoc()) {
                $assetList[] = $a;
            }

            $assetIds = [
                'Tank-101' => $assetList[0]['id'] ?? 1,
                'Valve-201' => $assetList[1]['id'] ?? 2,
                'Pump-301' => $assetList[2]['id'] ?? 3
            ];
        } else {
            $assetIds = [
                'Tank-101' => $tank101['id'],
                'Valve-201' => $valve201['id'],
                'Pump-301' => $pump301['id']
            ];
        }
    }

    $tank101Id = $assetIds['Tank-101'];
    $valve201Id = $assetIds['Valve-201'];
    $pump301Id = $assetIds['Pump-301'];

    echo "<span class='info'>Using assets:</span>\n";
    echo "  Tank-101 ID: {$tank101Id}\n";
    echo "  Valve-201 ID: {$valve201Id}\n";
    echo "  Pump-301 ID: {$pump301Id}\n\n";

    // Get shutdown level IDs
    $esd3Level = $conn->query("SELECT id FROM shutdown_levels WHERE level_code = 'ESD3' LIMIT 1")->fetch_assoc();
    $l1Level = $conn->query("SELECT id FROM shutdown_levels WHERE level_code = 'L1' LIMIT 1")->fetch_assoc();

    if (!$esd3Level || !$l1Level) {
        throw new Exception("Shutdown levels not found. Run setup_shutdown_system.php first!");
    }

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
    $requiresApproval = 0;

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

    echo "  ✓ " . count($steps) . " steps created for ESD3\n\n";

    // ============================================
    // SEQUENCE 2: NORMAL SHUTDOWN (L1)
    // ============================================
    echo "<span class='info'>Creating Normal Shutdown Sequence (L1)...</span>\n";

    $seqName = "Normal Process Shutdown";
    $seqType = "shutdown";
    $desc = "Orderly shutdown of process unit. Gradually reduces flow, stops equipment in sequence, and ensures safe isolation.";
    $duration = 600;
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
    $duration = 900;
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
    // SUCCESS
    // ============================================
    echo "<span class='ok'>========================================</span>\n";
    echo "<span class='ok'>SUCCESS! Shutdown Sequences Created</span>\n";
    echo "<span class='ok'>========================================</span>\n\n";

    echo "Summary:\n";
    echo "  ✓ Emergency Shutdown (ESD3) - 5 steps\n";
    echo "  ✓ Normal Shutdown (L1) - 9 steps\n";
    echo "  ✓ Normal Startup - 15 steps\n\n";

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
    echo "<span class='info'>Stack trace:</span>\n";
    echo "<span class='err'>" . $e->getTraceAsString() . "</span>\n";
    exit(1);
}

echo "</pre>";
echo "<br><a href='shutdown_manager.php' style='background:#c62828;color:white;padding:15px 30px;text-decoration:none;border-radius:5px;display:inline-block;'>Open Shutdown Manager</a>";
echo "</body></html>";
?>
