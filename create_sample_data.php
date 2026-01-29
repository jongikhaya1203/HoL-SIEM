<?php
/**
 * Create Sample Data for All SCADA Modules
 * This populates demonstration data for Oil & Gas, Rail, Mining, and Manufacturing
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'localhost';
$username = 'root';
$password = '';
$database = 'network_security';

echo "<!DOCTYPE html><html><head><title>Create SCADA Sample Data</title>";
echo "<style>body{font-family:monospace;background:#1a1a2e;color:#0f0;padding:20px;}";
echo ".err{color:#f00;}.ok{color:#0f0;}.info{color:#0ff;}.warn{color:#fa0;}</style></head><body>";
echo "<h2>Creating Sample Data for All SCADA Modules</h2><pre>";

try {
    $conn = new mysqli($host, $username, $password, $database);

    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    echo "<span class='ok'>✓ Connected to database</span>\n\n";

    // Clear existing data
    echo "<span class='info'>Clearing existing sample data...</span>\n";
    $conn->query("DELETE FROM scada_alarm_history");
    $conn->query("DELETE FROM scada_tag_history");
    $conn->query("DELETE FROM scada_control_actions");
    $conn->query("DELETE FROM scada_calibration_records");
    $conn->query("DELETE FROM manufacturing_production_lines");
    $conn->query("DELETE FROM manufacturing_robots");
    $conn->query("DELETE FROM mining_ventilation_fans");
    $conn->query("DELETE FROM mining_gas_sensors");
    $conn->query("DELETE FROM mining_hoists");
    $conn->query("DELETE FROM rail_track_circuits");
    $conn->query("DELETE FROM rail_signals");
    $conn->query("DELETE FROM rail_points");
    $conn->query("DELETE FROM oil_gas_pipelines");
    $conn->query("DELETE FROM oil_gas_wellheads");
    $conn->query("DELETE FROM oil_gas_separators");
    $conn->query("DELETE FROM scada_tank_levels");
    $conn->query("DELETE FROM scada_valve_status");
    $conn->query("DELETE FROM scada_instruments");
    $conn->query("DELETE FROM scada_tags");
    $conn->query("DELETE FROM scada_rtus");
    $conn->query("DELETE FROM scada_plcs");
    $conn->query("DELETE FROM scada_assets");
    $conn->query("DELETE FROM scada_sites");
    echo "<span class='ok'>✓ Cleared existing data</span>\n\n";

    // ============================================
    // 1. CREATE SITES
    // ============================================
    echo "<span class='info'>Creating sites...</span>\n";

    $sites = [
        ['Oil & Gas Refinery - Houston', 'oil_gas', 29.7604, -95.3698, 'houston_refinery', 1],
        ['Metro Rail Operations - Sydney', 'rail', -33.8688, 151.2093, 'sydney_metro', 1],
        ['Deep Mine Complex - Johannesburg', 'mining', -26.2041, 28.0473, 'jburg_mine', 1],
        ['Smart Factory - Shanghai', 'manufacturing', 31.2304, 121.4737, 'shanghai_factory', 1]
    ];

    $siteIds = [];
    foreach ($sites as $site) {
        $stmt = $conn->prepare("INSERT INTO scada_sites (site_name, industry_type, latitude, longitude, site_code, is_active) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssddsi", $site[0], $site[1], $site[2], $site[3], $site[4], $site[5]);
        $stmt->execute();
        $siteIds[$site[1]] = $conn->insert_id;
        echo "  ✓ {$site[0]}\n";
    }

    // ============================================
    // 2. CREATE PLCs AND RTUs
    // ============================================
    echo "\n<span class='info'>Creating PLCs and RTUs...</span>\n";

    // Oil & Gas PLCs
    $plcs = [
        [$siteIds['oil_gas'], 'Refinery Main PLC', 'Siemens S7-1500', '192.168.1.100', 502, 'modbus_tcp', 1, null],
        [$siteIds['rail'], 'Signaling PLC', 'Allen-Bradley ControlLogix', '192.168.2.100', 44818, 'ethernet_ip', 1, null],
        [$siteIds['mining'], 'Ventilation Control PLC', 'Schneider M580', '192.168.3.100', 502, 'modbus_tcp', 1, null],
        [$siteIds['manufacturing'], 'Production Line PLC', 'Siemens S7-1200', '192.168.4.100', 4840, 'opc_ua', 1, null]
    ];

    $plcIds = [];
    foreach ($plcs as $idx => $plc) {
        $stmt = $conn->prepare("INSERT INTO scada_plcs (site_id, plc_name, plc_model, ip_address, port, protocol, is_active, poll_interval_ms) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssissi", $plc[0], $plc[1], $plc[2], $plc[3], $plc[4], $plc[5], $plc[6], $plc[7]);
        $stmt->execute();
        $plcIds[$idx] = $conn->insert_id;
        echo "  ✓ {$plc[1]}\n";
    }

    // RTUs
    $rtus = [
        [$siteIds['oil_gas'], 'Pipeline RTU-01', 'ABB RTU560', '192.168.1.201', 20000, 'dnp3', 0, '+14155551234', 3000],
        [$siteIds['mining'], 'Underground RTU-05', 'GE MDS Mercury', '192.168.3.205', 502, 'modbus_rtu', 0, '+27825551111', 5000]
    ];

    $rtuIds = [];
    foreach ($rtus as $idx => $rtu) {
        $stmt = $conn->prepare("INSERT INTO scada_rtus (site_id, rtu_name, rtu_model, ip_address, port, protocol, is_gsm, gsm_number, poll_interval_ms) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssisssi", $rtu[0], $rtu[1], $rtu[2], $rtu[3], $rtu[4], $rtu[5], $rtu[6], $rtu[7], $rtu[8]);
        $stmt->execute();
        $rtuIds[$idx] = $conn->insert_id;
        echo "  ✓ {$rtu[1]}\n";
    }

    // ============================================
    // 3. CREATE ASSETS (TANKS, VALVES, ETC)
    // ============================================
    echo "\n<span class='info'>Creating assets...</span>\n";

    $assets = [
        // Oil & Gas
        [$siteIds['oil_gas'], 'Tank-101', 'tank', 'Crude Oil Storage Tank', $plcIds[0], null, 1, null],
        [$siteIds['oil_gas'], 'Valve-201', 'valve', 'Main Pipeline Inlet Valve', $plcIds[0], null, 1, null],
        [$siteIds['oil_gas'], 'Pump-301', 'pump', 'Transfer Pump', $plcIds[0], null, 1, null],
        // Rail
        [$siteIds['rail'], 'Signal-A12', 'signal', 'Platform Entry Signal', $plcIds[1], null, 1, null],
        [$siteIds['rail'], 'Point-SW05', 'point', 'Junction Points', $plcIds[1], null, 1, null],
        // Mining
        [$siteIds['mining'], 'Fan-VF01', 'fan', 'Primary Ventilation Fan', $plcIds[2], null, 1, null],
        [$siteIds['mining'], 'Hoist-H1', 'hoist', 'Main Shaft Hoist', $plcIds[2], null, 1, null],
        // Manufacturing
        [$siteIds['manufacturing'], 'Robot-R01', 'robot', 'Assembly Robot Arm', $plcIds[3], null, 1, null],
        [$siteIds['manufacturing'], 'Conv-C01', 'conveyor', 'Main Conveyor Belt', $plcIds[3], null, 1, null]
    ];

    $assetIds = [];
    foreach ($assets as $idx => $asset) {
        $stmt = $conn->prepare("INSERT INTO scada_assets (site_id, asset_tag, asset_type, description, plc_id, rtu_id, is_active, parent_asset_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssiii", $asset[0], $asset[1], $asset[2], $asset[3], $asset[4], $asset[5], $asset[6], $asset[7]);
        $stmt->execute();
        $assetIds[$asset[1]] = $conn->insert_id;
        echo "  ✓ {$asset[1]} - {$asset[3]}\n";
    }

    // ============================================
    // 4. CREATE SCADA TAGS
    // ============================================
    echo "\n<span class='info'>Creating SCADA tags...</span>\n";

    $tags = [
        // Oil & Gas - Tank
        [$assetIds['Tank-101'], $plcIds[0], null, 'Tank-101.Level', 'AI', 'Tank 101 Level', 40001, 'float32', 'm', 0.00, 15.00, 12.50, 3.00, 13.50, 1, 3000, 1],
        [$assetIds['Tank-101'], $plcIds[0], null, 'Tank-101.Temp', 'AI', 'Tank 101 Temperature', 40003, 'float32', '°C', -20.00, 80.00, 45.30, null, null, 1, 3000, 1],
        [$assetIds['Tank-101'], $plcIds[0], null, 'Tank-101.Pressure', 'AI', 'Tank 101 Pressure', 40005, 'float32', 'bar', 0.00, 10.00, 2.50, null, null, 1, 3000, 1],

        // Oil & Gas - Valve
        [$assetIds['Valve-201'], $plcIds[0], null, 'Valve-201.Position', 'AI', 'Valve 201 Position', 40011, 'uint16', '%', 0.00, 100.00, 75.00, null, null, 1, 1000, 1],
        [$assetIds['Valve-201'], $plcIds[0], null, 'Valve-201.Status', 'DI', 'Valve 201 Status', 10001, 'bool', null, null, null, 1.00, null, null, 1, 1000, 1],
        [$assetIds['Valve-201'], $plcIds[0], null, 'Valve-201.Command', 'DO', 'Valve 201 Command', 00001, 'bool', null, null, null, 0.00, null, null, 1, 1000, 0],

        // Oil & Gas - Pump
        [$assetIds['Pump-301'], $plcIds[0], null, 'Pump-301.Speed', 'AI', 'Pump 301 Speed', 40021, 'uint16', 'RPM', 0.00, 3600.00, 1800.00, null, null, 1, 2000, 1],
        [$assetIds['Pump-301'], $plcIds[0], null, 'Pump-301.Flow', 'AI', 'Pump 301 Flow Rate', 40023, 'float32', 'm3/h', 0.00, 500.00, 245.50, null, null, 1, 2000, 1],

        // Rail - Signal
        [$assetIds['Signal-A12'], $plcIds[1], null, 'Signal-A12.Aspect', 'DI', 'Signal A12 Aspect', 10101, 'uint16', null, 0.00, 3.00, 1.00, null, null, 1, 1000, 1],
        [$assetIds['Signal-A12'], $plcIds[1], null, 'Signal-A12.LampStatus', 'DI', 'Signal A12 Lamp OK', 10102, 'bool', null, null, null, 1.00, null, null, 1, 5000, 1],

        // Rail - Point
        [$assetIds['Point-SW05'], $plcIds[1], null, 'Point-SW05.Position', 'DI', 'Point SW05 Position', 10201, 'bool', null, null, null, 0.00, null, null, 1, 1000, 1],
        [$assetIds['Point-SW05'], $plcIds[1], null, 'Point-SW05.Locked', 'DI', 'Point SW05 Locked', 10202, 'bool', null, null, null, 1.00, null, null, 1, 1000, 1],

        // Mining - Fan
        [$assetIds['Fan-VF01'], $plcIds[2], null, 'Fan-VF01.Speed', 'AI', 'Fan VF01 Speed', 40301, 'uint16', 'RPM', 0.00, 1500.00, 980.00, null, null, 1, 2000, 1],
        [$assetIds['Fan-VF01'], $plcIds[2], null, 'Fan-VF01.Airflow', 'AI', 'Fan VF01 Airflow', 40303, 'float32', 'm3/s', 0.00, 200.00, 145.30, 100.00, null, 1, 2000, 1],
        [$assetIds['Fan-VF01'], $plcIds[2], null, 'Fan-VF01.Running', 'DI', 'Fan VF01 Running', 10301, 'bool', null, null, null, 1.00, null, null, 1, 1000, 1],

        // Mining - Hoist
        [$assetIds['Hoist-H1'], $plcIds[2], null, 'Hoist-H1.Position', 'AI', 'Hoist H1 Position', 40401, 'float32', 'm', -1200.00, 0.00, -450.00, null, null, 1, 500, 1],
        [$assetIds['Hoist-H1'], $plcIds[2], null, 'Hoist-H1.Speed', 'AI', 'Hoist H1 Speed', 40403, 'float32', 'm/s', 0.00, 16.00, 8.50, null, 14.00, 1, 500, 1],
        [$assetIds['Hoist-H1'], $plcIds[2], null, 'Hoist-H1.Load', 'AI', 'Hoist H1 Load', 40405, 'uint16', 'kg', 0.00, 10000.00, 3500.00, null, 9500.00, 1, 1000, 1],

        // Manufacturing - Robot
        [$assetIds['Robot-R01'], $plcIds[3], null, 'Robot-R01.Status', 'DI', 'Robot R01 Status', 10501, 'uint16', null, 0.00, 5.00, 2.00, null, null, 1, 1000, 1],
        [$assetIds['Robot-R01'], $plcIds[3], null, 'Robot-R01.Cycle', 'AI', 'Robot R01 Cycle Count', 40501, 'uint32', 'cycles', null, null, 12547.00, null, null, 1, 5000, 1],
        [$assetIds['Robot-R01'], $plcIds[3], null, 'Robot-R01.Position.X', 'AI', 'Robot R01 X Position', 40503, 'float32', 'mm', -1000.00, 1000.00, 245.50, null, null, 1, 500, 1],

        // Manufacturing - Conveyor
        [$assetIds['Conv-C01'], $plcIds[3], null, 'Conv-C01.Speed', 'AI', 'Conveyor C01 Speed', 40601, 'float32', 'm/min', 0.00, 60.00, 35.50, null, null, 1, 2000, 1],
        [$assetIds['Conv-C01'], $plcIds[3], null, 'Conv-C01.Running', 'DI', 'Conveyor C01 Running', 10601, 'bool', null, null, null, 1.00, null, null, 1, 1000, 1],
        [$assetIds['Conv-C01'], $plcIds[3], null, 'Conv-C01.Parts', 'AI', 'Conveyor C01 Part Count', 40603, 'uint32', 'parts', null, null, 8765.00, null, null, 1, 10000, 1]
    ];

    $tagIds = [];
    foreach ($tags as $idx => $tag) {
        $stmt = $conn->prepare("INSERT INTO scada_tags (asset_id, plc_id, rtu_id, tag_name, tag_type, description, modbus_address, data_type, engineering_unit, min_value, max_value, current_value, alarm_low, alarm_high, is_active, poll_interval_ms, enable_history) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiisssissdddddiii",
            $tag[0], $tag[1], $tag[2], $tag[3], $tag[4], $tag[5], $tag[6], $tag[7], $tag[8],
            $tag[9], $tag[10], $tag[11], $tag[12], $tag[13], $tag[14], $tag[15], $tag[16]
        );
        $stmt->execute();
        $tagIds[$tag[3]] = $conn->insert_id;
        echo "  ✓ {$tag[3]}\n";
    }

    // ============================================
    // 5. CREATE VALVE STATUS
    // ============================================
    echo "\n<span class='info'>Creating valve status...</span>\n";

    $valves = [
        [$assetIds['Valve-201'], 75.00, 'modulating', 'normal', 0, null, null]
    ];

    foreach ($valves as $valve) {
        $stmt = $conn->prepare("INSERT INTO scada_valve_status (valve_asset_id, position_percent, valve_type, status, is_locked, locked_by, lock_reason) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("idssis", $valve[0], $valve[1], $valve[2], $valve[3], $valve[4], $valve[5], $valve[6]);
        $stmt->execute();
        echo "  ✓ Valve status created\n";
    }

    // ============================================
    // 6. CREATE TANK LEVELS
    // ============================================
    echo "\n<span class='info'>Creating tank levels...</span>\n";

    $tanks = [
        [$assetIds['Tank-101'], $tagIds['Tank-101.Level'], 12.50, 15.00, 83.33, 5000.00, 6000.00, 4166.50, 45.30, 'horizontal_cylinder', 3.00, 20.00, null, null]
    ];

    foreach ($tanks as $tank) {
        $stmt = $conn->prepare("INSERT INTO scada_tank_levels (tank_asset_id, level_tag_id, level_value, max_level, level_percent, volume_current, volume_capacity, volume_available, temperature, tank_geometry, radius, length, height, width) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iidddddddsddddd",
            $tank[0], $tank[1], $tank[2], $tank[3], $tank[4], $tank[5], $tank[6], $tank[7],
            $tank[8], $tank[9], $tank[10], $tank[11], $tank[12], $tank[13]
        );
        $stmt->execute();
        echo "  ✓ Tank level created\n";
    }

    // ============================================
    // 7. CREATE INSTRUMENTS FOR CALIBRATION
    // ============================================
    echo "\n<span class='info'>Creating instruments...</span>\n";

    $instruments = [
        [$assetIds['Tank-101'], $tagIds['Tank-101.Level'], 'Level Transmitter', 'Rosemount 3051L', 'LT-101', 'pressure', '0-15m', 0.001, 0.00, 0.00, 365, '2025-12-15', 1],
        [$assetIds['Pump-301'], $tagIds['Pump-301.Flow'], 'Flow Meter', 'Endress Hauser Promag', 'FT-301', 'flow', '0-500 m3/h', 0.5, 0.00, 0.00, 730, '2026-06-20', 1],
        [$assetIds['Tank-101'], $tagIds['Tank-101.Pressure'], 'Pressure Transmitter', 'Yokogawa EJA530E', 'PT-101', 'pressure', '0-10 bar', 0.075, 0.00, 0.00, 365, '2025-11-30', 1]
    ];

    $instrumentIds = [];
    foreach ($instruments as $idx => $instrument) {
        $stmt = $conn->prepare("INSERT INTO scada_instruments (asset_id, tag_id, instrument_type, model, serial_number, measurement_type, range_spec, accuracy, zero_offset, span_offset, calibration_interval_days, next_calibration_date, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iisssssdddssi",
            $instrument[0], $instrument[1], $instrument[2], $instrument[3], $instrument[4],
            $instrument[5], $instrument[6], $instrument[7], $instrument[8], $instrument[9],
            $instrument[10], $instrument[11], $instrument[12]
        );
        $stmt->execute();
        $instrumentIds[$idx] = $conn->insert_id;
        echo "  ✓ {$instrument[2]} - {$instrument[4]}\n";
    }

    // ============================================
    // 8. CREATE CALIBRATION RECORDS
    // ============================================
    echo "\n<span class='info'>Creating calibration records...</span>\n";

    $calibrations = [
        [$instrumentIds[0], 'John Smith', 'Full calibration per ISO/IEC 17025', 'pass', 0.0005, 0.00, 0.00, '{"test_points": [0, 3.75, 7.5, 11.25, 15], "errors": [0.0002, 0.0003, 0.0005, 0.0004, 0.0003]}', null],
        [$instrumentIds[1], 'Jane Doe', 'Annual flow calibration', 'pass', 0.45, 0.00, 0.00, '{"test_points": [0, 125, 250, 375, 500], "errors": [0.1, 0.3, 0.45, 0.35, 0.25]}', null]
    ];

    foreach ($calibrations as $calibration) {
        $stmt = $conn->prepare("INSERT INTO scada_calibration_records (instrument_id, calibrated_by, calibration_notes, calibration_result, accuracy_found, zero_adjustment, span_adjustment, test_data, next_calibration_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssdddss",
            $calibration[0], $calibration[1], $calibration[2], $calibration[3],
            $calibration[4], $calibration[5], $calibration[6], $calibration[7], $calibration[8]
        );
        $stmt->execute();
        echo "  ✓ Calibration record for instrument {$calibration[0]}\n";
    }

    // ============================================
    // 9. CREATE OIL & GAS SPECIFIC DATA
    // ============================================
    echo "\n<span class='info'>Creating Oil & Gas industry data...</span>\n";

    // Pipelines
    $pipelines = [
        [$siteIds['oil_gas'], 'Pipeline-North', 'crude', 5000.00, 16, 245.50, 250.00, 'operational', 29.7604, -95.3698, 30.2672, -97.7431]
    ];

    foreach ($pipelines as $pipeline) {
        $stmt = $conn->prepare("INSERT INTO oil_gas_pipelines (site_id, pipeline_name, product_type, length_km, diameter_inches, flow_rate, max_flow_rate, status, start_lat, start_lon, end_lat, end_lon) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issidddsddddd",
            $pipeline[0], $pipeline[1], $pipeline[2], $pipeline[3], $pipeline[4],
            $pipeline[5], $pipeline[6], $pipeline[7], $pipeline[8], $pipeline[9],
            $pipeline[10], $pipeline[11]
        );
        $stmt->execute();
        echo "  ✓ Pipeline: {$pipeline[1]}\n";
    }

    // Wellheads
    $wellheads = [
        [$siteIds['oil_gas'], 'Well-A-07', 'oil', 125.50, 1850.00, 1200.00, 45.30, 'producing']
    ];

    foreach ($wellheads as $wellhead) {
        $stmt = $conn->prepare("INSERT INTO oil_gas_wellheads (site_id, well_name, well_type, production_rate, reservoir_pressure, tubing_pressure, temperature, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issdddds",
            $wellhead[0], $wellhead[1], $wellhead[2], $wellhead[3],
            $wellhead[4], $wellhead[5], $wellhead[6], $wellhead[7]
        );
        $stmt->execute();
        echo "  ✓ Wellhead: {$wellhead[1]}\n";
    }

    // Separators
    $separators = [
        [$siteIds['oil_gas'], 'Sep-100', 'three_phase', 180.50, 125.00, 55.50, 12.50, 45.30, 85.50, 'operating']
    ];

    foreach ($separators as $separator) {
        $stmt = $conn->prepare("INSERT INTO oil_gas_separators (site_id, separator_name, separator_type, oil_output, gas_output, water_output, pressure, temperature, efficiency, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issdddddds",
            $separator[0], $separator[1], $separator[2], $separator[3],
            $separator[4], $separator[5], $separator[6], $separator[7],
            $separator[8], $separator[9]
        );
        $stmt->execute();
        echo "  ✓ Separator: {$separator[1]}\n";
    }

    // ============================================
    // 10. CREATE RAIL SPECIFIC DATA
    // ============================================
    echo "\n<span class='info'>Creating Rail industry data...</span>\n";

    // Track Circuits
    $trackCircuits = [
        [$siteIds['rail'], 'TC-A12', 1, 450.00, 'clear', 1.25, 'ok'],
        [$siteIds['rail'], 'TC-B05', 1, 320.00, 'occupied', 0.85, 'ok']
    ];

    foreach ($trackCircuits as $circuit) {
        $stmt = $conn->prepare("INSERT INTO rail_track_circuits (site_id, circuit_name, is_occupied, length_meters, occupancy_status, voltage, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isidsds",
            $circuit[0], $circuit[1], $circuit[2], $circuit[3],
            $circuit[4], $circuit[5], $circuit[6]
        );
        $stmt->execute();
        echo "  ✓ Track Circuit: {$circuit[1]}\n";
    }

    // Signals
    $signals = [
        [$siteIds['rail'], 'Signal-A12', 'main', 'proceed', 1, 'ok'],
        [$siteIds['rail'], 'Signal-B05', 'distant', 'caution', 1, 'ok']
    ];

    foreach ($signals as $signal) {
        $stmt = $conn->prepare("INSERT INTO rail_signals (site_id, signal_name, signal_type, aspect, lamp_ok, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssis",
            $signal[0], $signal[1], $signal[2], $signal[3],
            $signal[4], $signal[5]
        );
        $stmt->execute();
        echo "  ✓ Signal: {$signal[1]}\n";
    }

    // Points
    $points = [
        [$siteIds['rail'], 'Point-SW05', 'normal', 1, 'ok']
    ];

    foreach ($points as $point) {
        $stmt = $conn->prepare("INSERT INTO rail_points (site_id, point_name, position, is_locked, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issis", $point[0], $point[1], $point[2], $point[3], $point[4]);
        $stmt->execute();
        echo "  ✓ Point: {$point[1]}\n";
    }

    // ============================================
    // 11. CREATE MINING SPECIFIC DATA
    // ============================================
    echo "\n<span class='info'>Creating Mining industry data...</span>\n";

    // Ventilation Fans
    $fans = [
        [$siteIds['mining'], 'Fan-VF01', 'main', 980.00, 145.30, 35.50, 1, 'running'],
        [$siteIds['mining'], 'Fan-VF02', 'auxiliary', 750.00, 98.20, 28.30, 1, 'running']
    ];

    foreach ($fans as $fan) {
        $stmt = $conn->prepare("INSERT INTO mining_ventilation_fans (site_id, fan_name, fan_type, speed_rpm, airflow_m3s, temperature, is_running, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issdddis",
            $fan[0], $fan[1], $fan[2], $fan[3],
            $fan[4], $fan[5], $fan[6], $fan[7]
        );
        $stmt->execute();
        echo "  ✓ Ventilation Fan: {$fan[1]}\n";
    }

    // Gas Sensors
    $gasSensors = [
        [$siteIds['mining'], 'GS-Level5-01', 'methane', 0.35, 1.00, 'normal'],
        [$siteIds['mining'], 'GS-Level7-02', 'co', 5.50, 50.00, 'normal'],
        [$siteIds['mining'], 'GS-Level5-03', 'oxygen', 20.80, 19.50, 'normal']
    ];

    foreach ($gasSensors as $sensor) {
        $stmt = $conn->prepare("INSERT INTO mining_gas_sensors (site_id, sensor_name, gas_type, reading, alarm_threshold, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issdds",
            $sensor[0], $sensor[1], $sensor[2], $sensor[3],
            $sensor[4], $sensor[5]
        );
        $stmt->execute();
        echo "  ✓ Gas Sensor: {$sensor[1]} ({$sensor[2]})\n";
    }

    // Hoists
    $hoists = [
        [$siteIds['mining'], 'Hoist-H1', 'main_shaft', -450.00, 8.50, 3500, 10000, 1, 'running']
    ];

    foreach ($hoists as $hoist) {
        $stmt = $conn->prepare("INSERT INTO mining_hoists (site_id, hoist_name, hoist_type, depth_meters, speed_ms, load_kg, max_load_kg, is_running, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issddiiis",
            $hoist[0], $hoist[1], $hoist[2], $hoist[3],
            $hoist[4], $hoist[5], $hoist[6], $hoist[7], $hoist[8]
        );
        $stmt->execute();
        echo "  ✓ Hoist: {$hoist[1]}\n";
    }

    // ============================================
    // 12. CREATE MANUFACTURING SPECIFIC DATA
    // ============================================
    echo "\n<span class='info'>Creating Manufacturing industry data...</span>\n";

    // Production Lines
    $prodLines = [
        [$siteIds['manufacturing'], 'Line-A', 35.50, 100, 95, 88, 79.46, 1, 'running']
    ];

    foreach ($prodLines as $line) {
        $stmt = $conn->prepare("INSERT INTO manufacturing_production_lines (site_id, line_name, speed_units_min, availability_percent, performance_percent, quality_percent, oee_percent, is_running, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isdiiidis",
            $line[0], $line[1], $line[2], $line[3],
            $line[4], $line[5], $line[6], $line[7], $line[8]
        );
        $stmt->execute();
        echo "  ✓ Production Line: {$line[1]} (OEE: {$line[6]}%)\n";
    }

    // Robots
    $robots = [
        [$siteIds['manufacturing'], 'Robot-R01', 'assembly', 'running', 12547, 245.50, -150.30, 425.75, 'normal']
    ];

    foreach ($robots as $robot) {
        $stmt = $conn->prepare("INSERT INTO manufacturing_robots (site_id, robot_name, robot_type, status, cycle_count, position_x, position_y, position_z, health_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssissdds",
            $robot[0], $robot[1], $robot[2], $robot[3],
            $robot[4], $robot[5], $robot[6], $robot[7], $robot[8]
        );
        $stmt->execute();
        echo "  ✓ Robot: {$robot[1]}\n";
    }

    // ============================================
    // 13. CREATE TAG HISTORY
    // ============================================
    echo "\n<span class='info'>Creating tag history (last 24 hours)...</span>\n";

    $historyCount = 0;
    $now = time();

    // Create hourly data points for key tags
    $historyTags = [
        $tagIds['Tank-101.Level'] => ['min' => 12.0, 'max' => 13.0],
        $tagIds['Tank-101.Temp'] => ['min' => 44.0, 'max' => 47.0],
        $tagIds['Pump-301.Flow'] => ['min' => 230.0, 'max' => 260.0],
        $tagIds['Fan-VF01.Airflow'] => ['min' => 140.0, 'max' => 150.0],
        $tagIds['Hoist-H1.Speed'] => ['min' => 7.0, 'max' => 10.0]
    ];

    foreach ($historyTags as $tagId => $range) {
        for ($i = 24; $i >= 0; $i--) {
            $timestamp = date('Y-m-d H:i:s', $now - ($i * 3600));
            $value = $range['min'] + (($range['max'] - $range['min']) * (rand(0, 100) / 100));
            $quality = 'good';

            $stmt = $conn->prepare("INSERT INTO scada_tag_history (tag_id, timestamp, value, quality) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isds", $tagId, $timestamp, $value, $quality);
            $stmt->execute();
            $historyCount++;
        }
    }
    echo "  ✓ Created {$historyCount} historical data points\n";

    // ============================================
    // 14. CREATE ALARMS
    // ============================================
    echo "\n<span class='info'>Creating alarm history...</span>\n";

    $alarms = [
        [$tagIds['Tank-101.Level'], 'high', 'Tank 101 Level High', 13.75, 13.50, 0, null, null],
        [$tagIds['Fan-VF01.Airflow'], 'low', 'Fan VF01 Airflow Low Alarm', 95.50, 100.00, 1, 'operator', date('Y-m-d H:i:s', $now - 1800)],
        [$tagIds['Hoist-H1.Speed'], 'high', 'Hoist H1 Overspeed Warning', 14.25, 14.00, 0, null, null]
    ];

    foreach ($alarms as $alarm) {
        $alarmTime = date('Y-m-d H:i:s', $now - rand(300, 7200));
        $stmt = $conn->prepare("INSERT INTO scada_alarm_history (tag_id, alarm_type, alarm_message, value, setpoint, is_acknowledged, acknowledged_by, acknowledged_at, alarm_time) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issddisss",
            $alarm[0], $alarm[1], $alarm[2], $alarm[3],
            $alarm[4], $alarm[5], $alarm[6], $alarm[7], $alarmTime
        );
        $stmt->execute();
        echo "  ✓ {$alarm[2]}\n";
    }

    // ============================================
    // 15. CREATE CONTROL ACTIONS LOG
    // ============================================
    echo "\n<span class='info'>Creating control actions log...</span>\n";

    $actions = [
        [$assetIds['Valve-201'], $tagIds['Valve-201.Command'], 'modulate', '{"target_position": 75, "previous_position": 50}', 'Operator', '192.168.1.50', 'success', 'Adjusted valve position for flow optimization'],
        [$assetIds['Pump-301'], null, 'start', '{"speed": 1800}', 'Automation', 'PLC-100', 'success', 'Auto-start based on tank level']
    ];

    foreach ($actions as $action) {
        $actionTime = date('Y-m-d H:i:s', $now - rand(300, 14400));
        $stmt = $conn->prepare("INSERT INTO scada_control_actions (asset_id, tag_id, action_type, action_params, initiated_by, source_ip, result, notes, action_time) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iisssisss",
            $action[0], $action[1], $action[2], $action[3],
            $action[4], $action[5], $action[6], $action[7], $actionTime
        );
        $stmt->execute();
        echo "  ✓ {$action[2]} action on asset {$action[0]}\n";
    }

    // ============================================
    // SUCCESS
    // ============================================
    echo "\n";
    echo "<span class='ok'>========================================</span>\n";
    echo "<span class='ok'>SUCCESS! Sample Data Created</span>\n";
    echo "<span class='ok'>========================================</span>\n\n";

    echo "Summary:\n";
    echo "  ✓ 4 Sites (Oil & Gas, Rail, Mining, Manufacturing)\n";
    echo "  ✓ 4 PLCs + 2 RTUs\n";
    echo "  ✓ 9 Assets (Tanks, Valves, Pumps, Signals, Fans, etc.)\n";
    echo "  ✓ 24 SCADA Tags with live values\n";
    echo "  ✓ Tank levels and valve positions\n";
    echo "  ✓ 3 Calibrated instruments\n";
    echo "  ✓ Industry-specific equipment:\n";
    echo "    - Oil & Gas: Pipelines, Wellheads, Separators\n";
    echo "    - Rail: Track Circuits, Signals, Points\n";
    echo "    - Mining: Ventilation Fans, Gas Sensors, Hoists\n";
    echo "    - Manufacturing: Production Lines, Robots\n";
    echo "  ✓ {$historyCount} Historical data points (24h)\n";
    echo "  ✓ " . count($alarms) . " Active/Historical alarms\n";
    echo "  ✓ " . count($actions) . " Control action logs\n\n";

    echo "Next Steps:\n";
    echo "1. View SCADA HMI: <a href='scada_hmi.php' style='color:#0ff;'>http://localhost/networkscanscada/scada_hmi.php</a>\n";
    echo "2. All tabs should now show live data\n";
    echo "3. Alarms tab will show active alarms\n";
    echo "4. Navigate between industries to see different equipment\n";

    $conn->close();

} catch (Exception $e) {
    echo "\n<span class='err'>❌ ERROR: " . $e->getMessage() . "</span>\n";
    echo "<span class='err'>Stack trace: " . $e->getTraceAsString() . "</span>\n";
    exit(1);
}

echo "</pre>";
echo "<br><a href='scada_hmi.php' style='background:#667eea;color:white;padding:15px 30px;text-decoration:none;border-radius:5px;display:inline-block;'>View SCADA HMI Dashboard</a>";
echo "</body></html>";
?>
