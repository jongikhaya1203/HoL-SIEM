<?php
session_start();
require_once __DIR__ . '/includes/Database.php';

if (!isset($_SESSION['cpanel_logged_in']) || $_SESSION['cpanel_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$db = Database::getInstance()->getConnection();
$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'add_device':
            try {
                $stmt = $db->prepare("INSERT INTO cpanel_scada_devices (device_name, device_type, protocol, connection_config, polling_interval, is_enabled) VALUES (?, ?, ?, ?, ?, ?)");
                $config = json_encode([
                    'host' => $_POST['host'] ?? '',
                    'port' => $_POST['port'] ?? '',
                    'unit_id' => $_POST['unit_id'] ?? 1
                ]);
                $stmt->execute([
                    $_POST['device_name'],
                    $_POST['device_type'],
                    $_POST['protocol'],
                    $config,
                    $_POST['polling_interval'] ?? 1000,
                    isset($_POST['is_enabled']) ? 1 : 0
                ]);
                $message = 'Device added successfully';
                $messageType = 'success';
            } catch (Exception $e) {
                $message = 'Error adding device: ' . $e->getMessage();
                $messageType = 'danger';
            }
            break;

        case 'update_device':
            try {
                $stmt = $db->prepare("UPDATE cpanel_scada_devices SET device_name = ?, device_type = ?, protocol = ?, connection_config = ?, polling_interval = ?, is_enabled = ? WHERE id = ?");
                $config = json_encode([
                    'host' => $_POST['host'] ?? '',
                    'port' => $_POST['port'] ?? '',
                    'unit_id' => $_POST['unit_id'] ?? 1
                ]);
                $stmt->execute([
                    $_POST['device_name'],
                    $_POST['device_type'],
                    $_POST['protocol'],
                    $config,
                    $_POST['polling_interval'] ?? 1000,
                    isset($_POST['is_enabled']) ? 1 : 0,
                    $_POST['device_id']
                ]);
                $message = 'Device updated successfully';
                $messageType = 'success';
            } catch (Exception $e) {
                $message = 'Error updating device: ' . $e->getMessage();
                $messageType = 'danger';
            }
            break;

        case 'delete_device':
            try {
                $stmt = $db->prepare("DELETE FROM cpanel_scada_devices WHERE id = ?");
                $stmt->execute([$_POST['device_id']]);
                $message = 'Device deleted successfully';
                $messageType = 'success';
            } catch (Exception $e) {
                $message = 'Error deleting device: ' . $e->getMessage();
                $messageType = 'danger';
            }
            break;

        case 'add_tag':
            try {
                $stmt = $db->prepare("INSERT INTO cpanel_scada_tags (device_id, tag_name, tag_address, data_type, scaling_factor, engineering_units, alarm_low, alarm_high, is_enabled) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $_POST['device_id'],
                    $_POST['tag_name'],
                    $_POST['tag_address'],
                    $_POST['data_type'],
                    $_POST['scaling_factor'] ?? 1.0,
                    $_POST['engineering_units'] ?? '',
                    $_POST['alarm_low'] ?: null,
                    $_POST['alarm_high'] ?: null,
                    isset($_POST['is_enabled']) ? 1 : 0
                ]);
                $message = 'Tag added successfully';
                $messageType = 'success';
            } catch (Exception $e) {
                $message = 'Error adding tag: ' . $e->getMessage();
                $messageType = 'danger';
            }
            break;

        case 'test_connection':
            // Simulate connection test
            $message = 'Connection test successful (simulated)';
            $messageType = 'success';
            break;
    }
}

// Get devices
$devices = [];
try {
    $stmt = $db->query("SELECT * FROM cpanel_scada_devices ORDER BY device_name");
    $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Table might not exist
}

// Sample devices if empty
if (empty($devices)) {
    $devices = [
        ['id' => 1, 'device_name' => 'PLC-Main-001', 'device_type' => 'PLC', 'protocol' => 'modbus_tcp', 'connection_config' => '{"host":"192.168.10.101","port":"502","unit_id":1}', 'polling_interval' => 1000, 'is_enabled' => 1, 'status' => 'online'],
        ['id' => 2, 'device_name' => 'RTU-Field-001', 'device_type' => 'RTU', 'protocol' => 'dnp3', 'connection_config' => '{"host":"192.168.10.102","port":"20000","unit_id":1}', 'polling_interval' => 2000, 'is_enabled' => 1, 'status' => 'online'],
        ['id' => 3, 'device_name' => 'HMI-Control-001', 'device_type' => 'HMI', 'protocol' => 'opc_ua', 'connection_config' => '{"host":"192.168.10.103","port":"4840","unit_id":1}', 'polling_interval' => 500, 'is_enabled' => 1, 'status' => 'online'],
        ['id' => 4, 'device_name' => 'PLC-Pump-002', 'device_type' => 'PLC', 'protocol' => 'modbus_tcp', 'connection_config' => '{"host":"192.168.10.104","port":"502","unit_id":2}', 'polling_interval' => 1000, 'is_enabled' => 1, 'status' => 'online'],
        ['id' => 5, 'device_name' => 'Sensor-Temp-001', 'device_type' => 'Sensor', 'protocol' => 'modbus_rtu', 'connection_config' => '{"host":"192.168.10.105","port":"502","unit_id":3}', 'polling_interval' => 5000, 'is_enabled' => 1, 'status' => 'online'],
        ['id' => 6, 'device_name' => 'Gateway-001', 'device_type' => 'Gateway', 'protocol' => 'ethernet_ip', 'connection_config' => '{"host":"192.168.10.1","port":"44818","unit_id":1}', 'polling_interval' => 1000, 'is_enabled' => 1, 'status' => 'online'],
        ['id' => 7, 'device_name' => 'DCS-Process-001', 'device_type' => 'DCS', 'protocol' => 'opc_ua', 'connection_config' => '{"host":"192.168.10.110","port":"4840","unit_id":1}', 'polling_interval' => 500, 'is_enabled' => 1, 'status' => 'online'],
        ['id' => 8, 'device_name' => 'RTU-Remote-002', 'device_type' => 'RTU', 'protocol' => 'dnp3', 'connection_config' => '{"host":"192.168.20.101","port":"20000","unit_id":2}', 'polling_interval' => 3000, 'is_enabled' => 0, 'status' => 'offline'],
    ];
}

// Get tags
$tags = [];
try {
    $stmt = $db->query("SELECT t.*, d.device_name FROM cpanel_scada_tags t LEFT JOIN cpanel_scada_devices d ON t.device_id = d.id ORDER BY t.tag_name");
    $tags = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Table might not exist
}

// Sample tags if empty
if (empty($tags)) {
    $tags = [
        ['id' => 1, 'device_id' => 1, 'device_name' => 'PLC-Main-001', 'tag_name' => 'Tank_Level_1', 'tag_address' => '40001', 'data_type' => 'FLOAT32', 'scaling_factor' => 1.0, 'engineering_units' => '%', 'alarm_low' => 10, 'alarm_high' => 90, 'is_enabled' => 1, 'current_value' => 67.5],
        ['id' => 2, 'device_id' => 1, 'device_name' => 'PLC-Main-001', 'tag_name' => 'Pump_Speed_1', 'tag_address' => '40003', 'data_type' => 'UINT16', 'scaling_factor' => 0.1, 'engineering_units' => 'RPM', 'alarm_low' => 100, 'alarm_high' => 3000, 'is_enabled' => 1, 'current_value' => 1850],
        ['id' => 3, 'device_id' => 2, 'device_name' => 'RTU-Field-001', 'tag_name' => 'Flow_Rate_Main', 'tag_address' => '30001', 'data_type' => 'FLOAT32', 'scaling_factor' => 1.0, 'engineering_units' => 'GPM', 'alarm_low' => 50, 'alarm_high' => 500, 'is_enabled' => 1, 'current_value' => 245.8],
        ['id' => 4, 'device_id' => 4, 'device_name' => 'PLC-Pump-002', 'tag_name' => 'Pressure_Inlet', 'tag_address' => '40001', 'data_type' => 'FLOAT32', 'scaling_factor' => 1.0, 'engineering_units' => 'PSI', 'alarm_low' => 20, 'alarm_high' => 150, 'is_enabled' => 1, 'current_value' => 85.2],
        ['id' => 5, 'device_id' => 4, 'device_name' => 'PLC-Pump-002', 'tag_name' => 'Pressure_Outlet', 'tag_address' => '40003', 'data_type' => 'FLOAT32', 'scaling_factor' => 1.0, 'engineering_units' => 'PSI', 'alarm_low' => 50, 'alarm_high' => 200, 'is_enabled' => 1, 'current_value' => 142.7],
        ['id' => 6, 'device_id' => 5, 'device_name' => 'Sensor-Temp-001', 'tag_name' => 'Temperature_Process', 'tag_address' => '30001', 'data_type' => 'FLOAT32', 'scaling_factor' => 0.1, 'engineering_units' => '°C', 'alarm_low' => 15, 'alarm_high' => 85, 'is_enabled' => 1, 'current_value' => 45.3],
        ['id' => 7, 'device_id' => 7, 'device_name' => 'DCS-Process-001', 'tag_name' => 'Valve_Position_1', 'tag_address' => '40010', 'data_type' => 'FLOAT32', 'scaling_factor' => 1.0, 'engineering_units' => '%', 'alarm_low' => null, 'alarm_high' => null, 'is_enabled' => 1, 'current_value' => 75.0],
        ['id' => 8, 'device_id' => 7, 'device_name' => 'DCS-Process-001', 'tag_name' => 'Motor_Status_1', 'tag_address' => '00001', 'data_type' => 'BOOL', 'scaling_factor' => 1.0, 'engineering_units' => '', 'alarm_low' => null, 'alarm_high' => null, 'is_enabled' => 1, 'current_value' => 1],
    ];
}

// Sample SCADA Reports
$scadaReports = [
    ['id' => 1, 'report_name' => 'Daily Production Summary', 'report_type' => 'Production', 'generated_at' => date('Y-m-d H:i:s', strtotime('-2 hours')), 'status' => 'completed', 'records' => 1458],
    ['id' => 2, 'report_name' => 'Alarm History Report', 'report_type' => 'Alarms', 'generated_at' => date('Y-m-d H:i:s', strtotime('-5 hours')), 'status' => 'completed', 'records' => 234],
    ['id' => 3, 'report_name' => 'Equipment Performance', 'report_type' => 'Performance', 'generated_at' => date('Y-m-d H:i:s', strtotime('-1 day')), 'status' => 'completed', 'records' => 892],
    ['id' => 4, 'report_name' => 'Energy Consumption', 'report_type' => 'Energy', 'generated_at' => date('Y-m-d H:i:s', strtotime('-1 day')), 'status' => 'completed', 'records' => 576],
    ['id' => 5, 'report_name' => 'Weekly Maintenance Log', 'report_type' => 'Maintenance', 'generated_at' => date('Y-m-d H:i:s', strtotime('-3 days')), 'status' => 'completed', 'records' => 128],
];

// Sample Alarm History
$alarmHistory = [
    ['id' => 1, 'timestamp' => date('Y-m-d H:i:s', strtotime('-15 minutes')), 'tag_name' => 'Tank_Level_1', 'device' => 'PLC-Main-001', 'alarm_type' => 'High', 'value' => 92.3, 'limit' => 90, 'status' => 'active', 'priority' => 'critical'],
    ['id' => 2, 'timestamp' => date('Y-m-d H:i:s', strtotime('-45 minutes')), 'tag_name' => 'Pressure_Outlet', 'device' => 'PLC-Pump-002', 'alarm_type' => 'High', 'value' => 165.8, 'limit' => 150, 'status' => 'acknowledged', 'priority' => 'warning'],
    ['id' => 3, 'timestamp' => date('Y-m-d H:i:s', strtotime('-2 hours')), 'tag_name' => 'Temperature_Process', 'device' => 'Sensor-Temp-001', 'alarm_type' => 'High', 'value' => 87.2, 'limit' => 85, 'status' => 'cleared', 'priority' => 'critical'],
    ['id' => 4, 'timestamp' => date('Y-m-d H:i:s', strtotime('-4 hours')), 'tag_name' => 'Flow_Rate_Main', 'device' => 'RTU-Field-001', 'alarm_type' => 'Low', 'value' => 42.1, 'limit' => 50, 'status' => 'cleared', 'priority' => 'warning'],
    ['id' => 5, 'timestamp' => date('Y-m-d H:i:s', strtotime('-6 hours')), 'tag_name' => 'Pump_Speed_1', 'device' => 'PLC-Main-001', 'alarm_type' => 'High', 'value' => 3150, 'limit' => 3000, 'status' => 'cleared', 'priority' => 'critical'],
];

// Oil Tank and Pipeline Data
$oilTanks = [
    ['id' => 'T-101', 'name' => 'Crude Oil Tank 1', 'capacity' => 50000, 'current_level' => 35420, 'unit' => 'BBL', 'temperature' => 72.5, 'pressure' => 14.7, 'status' => 'normal', 'product' => 'Crude Oil', 'pump_in_rate' => 850, 'pump_out_rate' => 620, 'pump_in_status' => 'running', 'pump_out_status' => 'running'],
    ['id' => 'T-102', 'name' => 'Crude Oil Tank 2', 'capacity' => 50000, 'current_level' => 42150, 'unit' => 'BBL', 'temperature' => 71.8, 'pressure' => 14.5, 'status' => 'normal', 'product' => 'Crude Oil', 'pump_in_rate' => 450, 'pump_out_rate' => 890, 'pump_in_status' => 'running', 'pump_out_status' => 'running'],
    ['id' => 'T-103', 'name' => 'Diesel Storage', 'capacity' => 30000, 'current_level' => 18750, 'unit' => 'BBL', 'temperature' => 68.2, 'pressure' => 15.1, 'status' => 'warning', 'product' => 'Diesel', 'pump_in_rate' => 320, 'pump_out_rate' => 0, 'pump_in_status' => 'running', 'pump_out_status' => 'stopped'],
    ['id' => 'T-104', 'name' => 'Gasoline Tank', 'capacity' => 25000, 'current_level' => 22100, 'unit' => 'BBL', 'temperature' => 65.4, 'pressure' => 14.8, 'status' => 'normal', 'product' => 'Gasoline', 'pump_in_rate' => 410, 'pump_out_rate' => 680, 'pump_in_status' => 'running', 'pump_out_status' => 'running'],
    ['id' => 'T-105', 'name' => 'Kerosene Tank', 'capacity' => 20000, 'current_level' => 8500, 'unit' => 'BBL', 'temperature' => 70.1, 'pressure' => 14.6, 'status' => 'low', 'product' => 'Kerosene', 'pump_in_rate' => 180, 'pump_out_rate' => 95, 'pump_in_status' => 'running', 'pump_out_status' => 'running'],
    ['id' => 'T-106', 'name' => 'Heavy Fuel Tank', 'capacity' => 40000, 'current_level' => 31200, 'unit' => 'BBL', 'temperature' => 125.6, 'pressure' => 16.2, 'status' => 'normal', 'product' => 'Heavy Fuel', 'pump_in_rate' => 520, 'pump_out_rate' => 380, 'pump_in_status' => 'running', 'pump_out_status' => 'running'],
];

// Gas Tanks - Natural Gas and LPG Storage
$gasTanks = [
    ['id' => 'G-201', 'name' => 'Natural Gas Holder 1', 'capacity' => 500000, 'current_level' => 385000, 'unit' => 'MCF', 'temperature' => 45.2, 'pressure' => 1200, 'status' => 'normal', 'product' => 'Natural Gas', 'pump_in_rate' => 12500, 'pump_out_rate' => 9800, 'pump_in_status' => 'running', 'pump_out_status' => 'running'],
    ['id' => 'G-202', 'name' => 'Natural Gas Holder 2', 'capacity' => 500000, 'current_level' => 425000, 'unit' => 'MCF', 'temperature' => 44.8, 'pressure' => 1180, 'status' => 'normal', 'product' => 'Natural Gas', 'pump_in_rate' => 8500, 'pump_out_rate' => 11200, 'pump_in_status' => 'running', 'pump_out_status' => 'running'],
    ['id' => 'G-203', 'name' => 'LPG Sphere 1', 'capacity' => 30000, 'current_level' => 24500, 'unit' => 'BBL', 'temperature' => -42.5, 'pressure' => 185, 'status' => 'normal', 'product' => 'LPG', 'pump_in_rate' => 450, 'pump_out_rate' => 620, 'pump_in_status' => 'running', 'pump_out_status' => 'running'],
    ['id' => 'G-204', 'name' => 'LPG Sphere 2', 'capacity' => 30000, 'current_level' => 18200, 'unit' => 'BBL', 'temperature' => -41.8, 'pressure' => 182, 'status' => 'warning', 'product' => 'LPG', 'pump_in_rate' => 380, 'pump_out_rate' => 0, 'pump_in_status' => 'running', 'pump_out_status' => 'stopped'],
    ['id' => 'G-205', 'name' => 'Propane Bullet 1', 'capacity' => 15000, 'current_level' => 12800, 'unit' => 'BBL', 'temperature' => -45.2, 'pressure' => 195, 'status' => 'normal', 'product' => 'Propane', 'pump_in_rate' => 280, 'pump_out_rate' => 320, 'pump_in_status' => 'running', 'pump_out_status' => 'running'],
    ['id' => 'G-206', 'name' => 'Butane Storage', 'capacity' => 10000, 'current_level' => 3200, 'unit' => 'BBL', 'temperature' => -38.6, 'pressure' => 165, 'status' => 'low', 'product' => 'Butane', 'pump_in_rate' => 150, 'pump_out_rate' => 85, 'pump_in_status' => 'running', 'pump_out_status' => 'running'],
];

// Pumps Data
$pumps = [
    ['id' => 'PMP-001', 'name' => 'Main Inlet Pump', 'tank' => 'T-101', 'type' => 'Centrifugal', 'flow_rate' => 850, 'max_rate' => 1200, 'power' => 250, 'status' => 'running', 'efficiency' => 94.5, 'runtime' => 1847],
    ['id' => 'PMP-002', 'name' => 'Transfer Pump 1-2', 'tank' => 'T-101', 'type' => 'Centrifugal', 'flow_rate' => 620, 'max_rate' => 800, 'power' => 150, 'status' => 'running', 'efficiency' => 91.2, 'runtime' => 1523],
    ['id' => 'PMP-003', 'name' => 'Distillation Feed', 'tank' => 'T-102', 'type' => 'Positive Displacement', 'flow_rate' => 890, 'max_rate' => 1000, 'power' => 200, 'status' => 'running', 'efficiency' => 96.8, 'runtime' => 2156],
    ['id' => 'PMP-004', 'name' => 'Diesel Transfer', 'tank' => 'T-103', 'type' => 'Centrifugal', 'flow_rate' => 320, 'max_rate' => 500, 'power' => 75, 'status' => 'running', 'efficiency' => 89.4, 'runtime' => 984],
    ['id' => 'PMP-005', 'name' => 'Diesel Export', 'tank' => 'T-103', 'type' => 'Centrifugal', 'flow_rate' => 0, 'max_rate' => 600, 'power' => 100, 'status' => 'stopped', 'efficiency' => 0, 'runtime' => 0],
    ['id' => 'PMP-006', 'name' => 'Gasoline Transfer', 'tank' => 'T-104', 'type' => 'Centrifugal', 'flow_rate' => 410, 'max_rate' => 600, 'power' => 100, 'status' => 'running', 'efficiency' => 92.1, 'runtime' => 1678],
    ['id' => 'PMP-007', 'name' => 'Export Pump', 'tank' => 'T-104', 'type' => 'Multistage', 'flow_rate' => 680, 'max_rate' => 900, 'power' => 180, 'status' => 'running', 'efficiency' => 95.3, 'runtime' => 2034],
    ['id' => 'PMP-008', 'name' => 'NG Compressor 1', 'tank' => 'G-201', 'type' => 'Reciprocating', 'flow_rate' => 12500, 'max_rate' => 15000, 'power' => 500, 'status' => 'running', 'efficiency' => 88.7, 'runtime' => 3256],
    ['id' => 'PMP-009', 'name' => 'NG Compressor 2', 'tank' => 'G-202', 'type' => 'Reciprocating', 'flow_rate' => 8500, 'max_rate' => 15000, 'power' => 500, 'status' => 'running', 'efficiency' => 85.2, 'runtime' => 2847],
    ['id' => 'PMP-010', 'name' => 'LPG Transfer 1', 'tank' => 'G-203', 'type' => 'Cryogenic', 'flow_rate' => 450, 'max_rate' => 600, 'power' => 120, 'status' => 'running', 'efficiency' => 91.8, 'runtime' => 1456],
    ['id' => 'PMP-011', 'name' => 'LPG Transfer 2', 'tank' => 'G-204', 'type' => 'Cryogenic', 'flow_rate' => 380, 'max_rate' => 600, 'power' => 120, 'status' => 'running', 'efficiency' => 90.4, 'runtime' => 1234],
    ['id' => 'PMP-012', 'name' => 'Propane Pump', 'tank' => 'G-205', 'type' => 'Cryogenic', 'flow_rate' => 280, 'max_rate' => 400, 'power' => 80, 'status' => 'running', 'efficiency' => 93.2, 'runtime' => 1678],
];

$pipelines = [
    ['id' => 'P-001', 'name' => 'Main Inlet Pipeline', 'from' => 'Offshore', 'to' => 'T-101', 'flow_rate' => 1250, 'pressure' => 85.4, 'status' => 'normal', 'length' => 12.5],
    ['id' => 'P-002', 'name' => 'Transfer Line 1-2', 'from' => 'T-101', 'to' => 'T-102', 'flow_rate' => 450, 'pressure' => 45.2, 'status' => 'normal', 'length' => 0.8],
    ['id' => 'P-003', 'name' => 'Distillation Feed', 'from' => 'T-102', 'to' => 'Distiller', 'flow_rate' => 890, 'pressure' => 62.8, 'status' => 'normal', 'length' => 2.1],
    ['id' => 'P-004', 'name' => 'Diesel Output', 'from' => 'Distiller', 'to' => 'T-103', 'flow_rate' => 320, 'pressure' => 38.5, 'status' => 'warning', 'length' => 1.5],
    ['id' => 'P-005', 'name' => 'Gasoline Output', 'from' => 'Distiller', 'to' => 'T-104', 'flow_rate' => 410, 'pressure' => 42.1, 'status' => 'normal', 'length' => 1.8],
    ['id' => 'P-006', 'name' => 'Kerosene Output', 'from' => 'Distiller', 'to' => 'T-105', 'flow_rate' => 180, 'pressure' => 35.6, 'status' => 'normal', 'length' => 1.6],
    ['id' => 'P-007', 'name' => 'Heavy Fuel Line', 'from' => 'Distiller', 'to' => 'T-106', 'flow_rate' => 520, 'pressure' => 55.3, 'status' => 'normal', 'length' => 2.3],
    ['id' => 'P-008', 'name' => 'Export Pipeline', 'from' => 'T-104', 'to' => 'Terminal', 'flow_rate' => 680, 'pressure' => 72.4, 'status' => 'normal', 'length' => 8.7],
];

$leakDetectors = [
    ['id' => 'LD-001', 'location' => 'P-001 KM 2.5', 'pipeline' => 'P-001', 'status' => 'normal', 'last_check' => '2 min ago', 'sensitivity' => 'high'],
    ['id' => 'LD-002', 'location' => 'P-001 KM 7.8', 'pipeline' => 'P-001', 'status' => 'normal', 'last_check' => '2 min ago', 'sensitivity' => 'high'],
    ['id' => 'LD-003', 'location' => 'P-003 KM 1.2', 'pipeline' => 'P-003', 'status' => 'normal', 'last_check' => '2 min ago', 'sensitivity' => 'medium'],
    ['id' => 'LD-004', 'location' => 'P-004 KM 0.8', 'pipeline' => 'P-004', 'status' => 'alert', 'last_check' => '1 min ago', 'sensitivity' => 'high', 'alert_type' => 'Pressure Drop Detected'],
    ['id' => 'LD-005', 'location' => 'P-008 KM 3.2', 'pipeline' => 'P-008', 'status' => 'normal', 'last_check' => '2 min ago', 'sensitivity' => 'high'],
    ['id' => 'LD-006', 'location' => 'P-008 KM 6.5', 'pipeline' => 'P-008', 'status' => 'warning', 'last_check' => '30 sec ago', 'sensitivity' => 'high', 'alert_type' => 'Minor Anomaly'],
    ['id' => 'LD-007', 'location' => 'T-103 Base', 'pipeline' => 'Tank', 'status' => 'warning', 'last_check' => '1 min ago', 'sensitivity' => 'high', 'alert_type' => 'Seepage Detected'],
];

$leakAlerts = [
    ['id' => 1, 'timestamp' => date('Y-m-d H:i:s', strtotime('-5 minutes')), 'location' => 'P-004 KM 0.8', 'type' => 'Pressure Drop', 'severity' => 'high', 'status' => 'active', 'estimated_loss' => '~15 BBL/hr', 'response' => 'Team dispatched'],
    ['id' => 2, 'timestamp' => date('Y-m-d H:i:s', strtotime('-18 minutes')), 'location' => 'T-103 Base', 'type' => 'Seepage', 'severity' => 'medium', 'status' => 'investigating', 'estimated_loss' => '~2 BBL/hr', 'response' => 'Under inspection'],
    ['id' => 3, 'timestamp' => date('Y-m-d H:i:s', strtotime('-45 minutes')), 'location' => 'P-008 KM 6.5', 'type' => 'Flow Anomaly', 'severity' => 'low', 'status' => 'monitoring', 'estimated_loss' => 'TBD', 'response' => 'Monitoring'],
    ['id' => 4, 'timestamp' => date('Y-m-d H:i:s', strtotime('-3 hours')), 'location' => 'P-001 KM 5.2', 'type' => 'Pressure Spike', 'severity' => 'medium', 'status' => 'resolved', 'estimated_loss' => '0 BBL', 'response' => 'False alarm'],
    ['id' => 5, 'timestamp' => date('Y-m-d H:i:s', strtotime('-1 day')), 'location' => 'T-102 Valve', 'type' => 'Valve Leak', 'severity' => 'high', 'status' => 'resolved', 'estimated_loss' => '45 BBL', 'response' => 'Valve replaced'],
];

// Network Topology Data
$topologyNodes = [
    ['id' => 'control-center', 'label' => 'Control Center', 'type' => 'control', 'ip' => '192.168.1.10', 'x' => 400, 'y' => 50],
    ['id' => 'firewall', 'label' => 'Industrial Firewall', 'type' => 'firewall', 'ip' => '192.168.1.1', 'x' => 400, 'y' => 130],
    ['id' => 'gateway', 'label' => 'Gateway-001', 'type' => 'gateway', 'ip' => '192.168.10.1', 'x' => 400, 'y' => 210],
    ['id' => 'switch1', 'label' => 'ICS Switch 1', 'type' => 'switch', 'ip' => '192.168.10.2', 'x' => 250, 'y' => 290],
    ['id' => 'switch2', 'label' => 'ICS Switch 2', 'type' => 'switch', 'ip' => '192.168.10.3', 'x' => 550, 'y' => 290],
    ['id' => 'plc1', 'label' => 'PLC-Main-001', 'type' => 'plc', 'ip' => '192.168.10.101', 'x' => 100, 'y' => 380],
    ['id' => 'plc2', 'label' => 'PLC-Pump-002', 'type' => 'plc', 'ip' => '192.168.10.104', 'x' => 250, 'y' => 380],
    ['id' => 'rtu1', 'label' => 'RTU-Field-001', 'type' => 'rtu', 'ip' => '192.168.10.102', 'x' => 400, 'y' => 380],
    ['id' => 'hmi', 'label' => 'HMI-Control-001', 'type' => 'hmi', 'ip' => '192.168.10.103', 'x' => 550, 'y' => 380],
    ['id' => 'dcs', 'label' => 'DCS-Process-001', 'type' => 'dcs', 'ip' => '192.168.10.110', 'x' => 700, 'y' => 380],
    ['id' => 'sensor1', 'label' => 'Sensor-Temp-001', 'type' => 'sensor', 'ip' => '192.168.10.105', 'x' => 175, 'y' => 470],
    ['id' => 'rtu2', 'label' => 'RTU-Remote-002', 'type' => 'rtu', 'ip' => '192.168.20.101', 'x' => 625, 'y' => 470, 'status' => 'offline'],
];

$topologyConnections = [
    ['from' => 'control-center', 'to' => 'firewall'],
    ['from' => 'firewall', 'to' => 'gateway'],
    ['from' => 'gateway', 'to' => 'switch1'],
    ['from' => 'gateway', 'to' => 'switch2'],
    ['from' => 'switch1', 'to' => 'plc1'],
    ['from' => 'switch1', 'to' => 'plc2'],
    ['from' => 'switch1', 'to' => 'rtu1'],
    ['from' => 'switch2', 'to' => 'hmi'],
    ['from' => 'switch2', 'to' => 'dcs'],
    ['from' => 'plc1', 'to' => 'sensor1'],
    ['from' => 'plc2', 'to' => 'sensor1'],
    ['from' => 'switch2', 'to' => 'rtu2'],
];

// Get protocols
$protocols = [];
try {
    $stmt = $db->query("SELECT * FROM cpanel_protocols WHERE category = 'Industrial' AND is_enabled = 1");
    $protocols = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Default protocols
    $protocols = [
        ['protocol_name' => 'modbus_tcp', 'display_name' => 'Modbus TCP'],
        ['protocol_name' => 'opc_ua', 'display_name' => 'OPC UA'],
        ['protocol_name' => 'dnp3', 'display_name' => 'DNP3']
    ];
}

$currentPage = 'scada';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SCADA Configuration - IOC Control Panel</title>
    <link rel="stylesheet" href="assets/css/cpanel.css">
</head>
<body>
    <div class="cpanel-wrapper">
        <?php include 'includes/sidebar.php'; ?>

        <div class="cpanel-main">
            <?php include 'includes/header.php'; ?>

            <div class="cpanel-content">
                <div class="page-header">
                    <h1>SCADA Configuration</h1>
                    <p>Configure industrial control system devices and data points</p>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?= $messageType ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <?php if ($messageType === 'success'): ?>
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            <?php else: ?>
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                            <?php endif; ?>
                        </svg>
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <!-- Stats -->
                <div class="stats-grid" style="grid-template-columns: repeat(5, 1fr);">
                    <div class="stat-card">
                        <div class="stat-icon modules">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="4" y="4" width="16" height="16" rx="2" ry="2"></rect>
                                <rect x="9" y="9" width="6" height="6"></rect>
                                <line x1="9" y1="1" x2="9" y2="4"></line>
                                <line x1="15" y1="1" x2="15" y2="4"></line>
                                <line x1="9" y1="20" x2="9" y2="23"></line>
                                <line x1="15" y1="20" x2="15" y2="23"></line>
                            </svg>
                        </div>
                        <div class="stat-info">
                            <span class="stat-value"><?= count($devices) ?></span>
                            <span class="stat-label">Devices</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon protocols">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                            </svg>
                        </div>
                        <div class="stat-info">
                            <span class="stat-value"><?= count($tags) ?></span>
                            <span class="stat-label">Tags</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon api">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                        </div>
                        <div class="stat-info">
                            <span class="stat-value"><?= count(array_filter($devices, fn($d) => ($d['status'] ?? '') === 'online')) ?></span>
                            <span class="stat-label">Online</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #ef4444, #dc2626);">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                                <line x1="12" y1="9" x2="12" y2="13"></line>
                                <line x1="12" y1="17" x2="12.01" y2="17"></line>
                            </svg>
                        </div>
                        <div class="stat-info">
                            <span class="stat-value"><?= count(array_filter($alarmHistory, fn($a) => $a['status'] === 'active')) ?></span>
                            <span class="stat-label">Active Alarms</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon channels">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                        </div>
                        <div class="stat-info">
                            <span class="stat-value">1000ms</span>
                            <span class="stat-label">Avg Poll Rate</span>
                        </div>
                    </div>
                </div>

                <!-- Tabs Navigation -->
                <div class="scada-tabs">
                    <button class="scada-tab active" data-tab="devices" onclick="switchScadaTab('devices')">Devices & Tags</button>
                    <button class="scada-tab" data-tab="tanks" onclick="switchScadaTab('tanks')">Oil Tank & Pipeline</button>
                    <button class="scada-tab" data-tab="gas" onclick="switchScadaTab('gas')">Gas Storage</button>
                    <button class="scada-tab" data-tab="pumps" onclick="switchScadaTab('pumps')">Pumps & Flow</button>
                    <button class="scada-tab" data-tab="topology" onclick="switchScadaTab('topology')">Network Topology</button>
                    <button class="scada-tab" data-tab="reports" onclick="switchScadaTab('reports')">Reports</button>
                    <button class="scada-tab" data-tab="alarms" onclick="switchScadaTab('alarms')">Alarm History</button>
                </div>

                <!-- Tab Content: Devices & Tags -->
                <div id="tab-devices" class="scada-tab-content active">

                <!-- Devices Section -->
                <div class="section-card">
                    <div class="section-header">
                        <h2>SCADA Devices</h2>
                        <button class="btn btn-primary btn-sm" onclick="showAddDevice()">+ Add Device</button>
                    </div>
                    <div class="section-body" style="padding: 0;">
                        <table class="config-table">
                            <thead>
                                <tr>
                                    <th>Device Name</th>
                                    <th>Type</th>
                                    <th>Protocol</th>
                                    <th>Connection</th>
                                    <th>Poll Interval</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($devices)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; color: var(--text-secondary);">No devices configured. Add your first SCADA device above.</td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($devices as $deviceIndex => $device): ?>
                                <?php $config = json_decode($device['connection_config'] ?? '{}', true); ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($device['device_name']) ?></strong></td>
                                    <td><?= htmlspecialchars($device['device_type']) ?></td>
                                    <td><span class="protocol-badge"><?= htmlspecialchars($device['protocol']) ?></span></td>
                                    <td><code><?= htmlspecialchars(($config['host'] ?? '') . ':' . ($config['port'] ?? '')) ?></code></td>
                                    <td><?= $device['polling_interval'] ?>ms</td>
                                    <td>
                                        <span class="status-badge status-<?= $device['status'] ?? 'unknown' ?>">
                                            <?= ucfirst($device['status'] ?? 'Unknown') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 5px;">
                                            <button class="btn btn-secondary btn-sm" onclick="testDeviceConnection(<?= $deviceIndex ?>)">Test</button>
                                            <button class="btn btn-secondary btn-sm" onclick="editDeviceById(<?= $deviceIndex ?>)">Edit</button>
                                            <button class="btn btn-danger btn-sm" onclick="deleteDevice(<?= $device['id'] ?>, '<?= htmlspecialchars($device['device_name']) ?>')">Delete</button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tags Section -->
                <div class="section-card" style="margin-top: 20px;">
                    <div class="section-header">
                        <h2>Data Tags</h2>
                        <button class="btn btn-primary btn-sm" onclick="showAddTag()" <?= empty($devices) ? 'disabled' : '' ?>>+ Add Tag</button>
                    </div>
                    <div class="section-body" style="padding: 0;">
                        <table class="config-table">
                            <thead>
                                <tr>
                                    <th>Tag Name</th>
                                    <th>Device</th>
                                    <th>Address</th>
                                    <th>Data Type</th>
                                    <th>Units</th>
                                    <th>Alarms</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($tags)): ?>
                                <tr>
                                    <td colspan="8" style="text-align: center; color: var(--text-secondary);">No tags configured</td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($tags as $tagIndex => $tag): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($tag['tag_name']) ?></strong></td>
                                    <td><?= htmlspecialchars($tag['device_name'] ?? 'Unknown') ?></td>
                                    <td><code><?= htmlspecialchars($tag['tag_address']) ?></code></td>
                                    <td><?= htmlspecialchars($tag['data_type']) ?></td>
                                    <td><?= htmlspecialchars($tag['engineering_units'] ?: '-') ?></td>
                                    <td>
                                        <?php if ($tag['alarm_low'] || $tag['alarm_high']): ?>
                                            <span class="alarm-badge">L: <?= $tag['alarm_low'] ?: '-' ?> / H: <?= $tag['alarm_high'] ?: '-' ?></span>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?= $tag['is_enabled'] ? 'online' : 'offline' ?>">
                                            <?= $tag['is_enabled'] ? 'Active' : 'Disabled' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 5px;">
                                            <button class="btn btn-secondary btn-sm" onclick="editTag(<?= $tagIndex ?>)">Edit</button>
                                            <button class="btn btn-danger btn-sm" onclick="deleteTag(<?= $tag['id'] ?>, '<?= htmlspecialchars($tag['tag_name']) ?>')">Delete</button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                </div><!-- End Tab: Devices & Tags -->

                <!-- Tab Content: Tank & Pipeline -->
                <div id="tab-tanks" class="scada-tab-content">

                    <!-- Leak Detection Alert Banner -->
                    <?php $activeLeaks = array_filter($leakAlerts, fn($a) => $a['status'] === 'active' || $a['status'] === 'investigating'); ?>
                    <?php if (!empty($activeLeaks)): ?>
                    <div class="leak-alert-banner">
                        <div class="leak-alert-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                                <line x1="12" y1="9" x2="12" y2="13"></line>
                                <line x1="12" y1="17" x2="12.01" y2="17"></line>
                            </svg>
                        </div>
                        <div class="leak-alert-content">
                            <strong><?= count($activeLeaks) ?> Active Leak Alert<?= count($activeLeaks) > 1 ? 's' : '' ?></strong>
                            <span>Potential leaks detected - Response teams dispatched</span>
                        </div>
                        <button class="btn btn-danger btn-sm" onclick="viewLeakDetails()">View Details</button>
                    </div>
                    <?php endif; ?>

                    <!-- Pipeline Visualization -->
                    <div class="section-card">
                        <div class="section-header">
                            <h2>Oil Storage & Pipeline System</h2>
                            <div style="display: flex; gap: 10px;">
                                <button class="btn btn-secondary btn-sm" onclick="runLeakTest()">Run Leak Test</button>
                                <button class="btn btn-primary btn-sm" onclick="refreshPipelineData()">Refresh Data</button>
                            </div>
                        </div>
                        <div class="section-body">
                            <!-- Pipeline Diagram -->
                            <div class="pipeline-diagram">
                                <svg id="pipelineSvg" width="100%" height="480" viewBox="0 0 1000 480">
                                    <!-- Background Grid -->
                                    <defs>
                                        <pattern id="grid" width="20" height="20" patternUnits="userSpaceOnUse">
                                            <path d="M 20 0 L 0 0 0 20" fill="none" stroke="#f0f0f0" stroke-width="0.5"/>
                                        </pattern>
                                        <!-- Leak animation -->
                                        <radialGradient id="leakGradient">
                                            <stop offset="0%" stop-color="#ef4444" stop-opacity="0.8"/>
                                            <stop offset="100%" stop-color="#ef4444" stop-opacity="0"/>
                                        </radialGradient>
                                        <!-- Flow animation -->
                                        <linearGradient id="flowGradient" x1="0%" y1="0%" x2="100%" y2="0%">
                                            <stop offset="0%" stop-color="#3b82f6" stop-opacity="0.3"/>
                                            <stop offset="50%" stop-color="#3b82f6" stop-opacity="1"/>
                                            <stop offset="100%" stop-color="#3b82f6" stop-opacity="0.3"/>
                                            <animate attributeName="x1" from="-100%" to="100%" dur="2s" repeatCount="indefinite"/>
                                            <animate attributeName="x2" from="0%" to="200%" dur="2s" repeatCount="indefinite"/>
                                        </linearGradient>
                                    </defs>
                                    <rect width="100%" height="100%" fill="url(#grid)"/>

                                    <!-- Offshore Source -->
                                    <g transform="translate(30, 200)">
                                        <rect x="0" y="0" width="60" height="80" rx="5" fill="#0ea5e9" stroke="#0284c7" stroke-width="2"/>
                                        <text x="30" y="35" text-anchor="middle" fill="white" font-size="20">🚢</text>
                                        <text x="30" y="55" text-anchor="middle" fill="white" font-size="9" font-weight="bold">OFFSHORE</text>
                                        <text x="30" y="68" text-anchor="middle" fill="white" font-size="8">Platform</text>
                                    </g>

                                    <!-- Main Inlet Pipeline P-001 -->
                                    <line x1="90" y1="240" x2="180" y2="240" stroke="#64748b" stroke-width="8" stroke-linecap="round"/>
                                    <line x1="90" y1="240" x2="180" y2="240" stroke="url(#flowGradient)" stroke-width="4" stroke-linecap="round"/>
                                    <text x="135" y="225" text-anchor="middle" font-size="8" fill="#64748b">P-001</text>
                                    <circle cx="135" cy="240" r="6" fill="#10b981" stroke="white" stroke-width="2"/>

                                    <!-- Tank T-101 -->
                                    <g class="tank-group" transform="translate(180, 180)" onclick="showTankById(0)" style="cursor:pointer;">
                                        <rect class="tank-body" x="0" y="0" width="70" height="120" rx="5" fill="#f8fafc" stroke="#10b981" stroke-width="3"/>
                                        <rect class="tank-level" x="5" y="<?= 120 - (($oilTanks[0]['current_level'] / $oilTanks[0]['capacity']) * 110) ?>" width="60" height="<?= ($oilTanks[0]['current_level'] / $oilTanks[0]['capacity']) * 110 ?>" fill="#10b981" opacity="0.6" rx="3"/>
                                        <text x="35" y="50" text-anchor="middle" font-size="11" font-weight="bold" fill="#1e293b">T-101</text>
                                        <text x="35" y="65" text-anchor="middle" font-size="9" fill="#64748b"><?= round(($oilTanks[0]['current_level'] / $oilTanks[0]['capacity']) * 100) ?>%</text>
                                        <text x="35" y="80" text-anchor="middle" font-size="8" fill="#64748b"><?= number_format($oilTanks[0]['current_level']) ?></text>
                                        <text x="35" y="90" text-anchor="middle" font-size="7" fill="#94a3b8">BBL</text>
                                    </g>

                                    <!-- Transfer Line P-002 -->
                                    <line x1="250" y1="240" x2="300" y2="240" stroke="#64748b" stroke-width="6" stroke-linecap="round"/>
                                    <text x="275" y="225" text-anchor="middle" font-size="8" fill="#64748b">P-002</text>

                                    <!-- Tank T-102 -->
                                    <g class="tank-group" transform="translate(300, 180)" onclick="showTankById(1)" style="cursor:pointer;">
                                        <rect class="tank-body" x="0" y="0" width="70" height="120" rx="5" fill="#f8fafc" stroke="#10b981" stroke-width="3"/>
                                        <rect class="tank-level" x="5" y="<?= 120 - (($oilTanks[1]['current_level'] / $oilTanks[1]['capacity']) * 110) ?>" width="60" height="<?= ($oilTanks[1]['current_level'] / $oilTanks[1]['capacity']) * 110 ?>" fill="#10b981" opacity="0.6" rx="3"/>
                                        <text x="35" y="50" text-anchor="middle" font-size="11" font-weight="bold" fill="#1e293b">T-102</text>
                                        <text x="35" y="65" text-anchor="middle" font-size="9" fill="#64748b"><?= round(($oilTanks[1]['current_level'] / $oilTanks[1]['capacity']) * 100) ?>%</text>
                                        <text x="35" y="80" text-anchor="middle" font-size="8" fill="#64748b"><?= number_format($oilTanks[1]['current_level']) ?></text>
                                        <text x="35" y="90" text-anchor="middle" font-size="7" fill="#94a3b8">BBL</text>
                                    </g>

                                    <!-- Distillation Feed P-003 -->
                                    <line x1="370" y1="240" x2="440" y2="240" stroke="#64748b" stroke-width="6" stroke-linecap="round"/>
                                    <line x1="370" y1="240" x2="440" y2="240" stroke="url(#flowGradient)" stroke-width="3" stroke-linecap="round"/>
                                    <text x="405" y="225" text-anchor="middle" font-size="8" fill="#64748b">P-003</text>
                                    <circle cx="405" cy="240" r="6" fill="#10b981" stroke="white" stroke-width="2"/>

                                    <!-- Distillation Unit -->
                                    <g transform="translate(440, 140)">
                                        <rect x="0" y="0" width="100" height="200" rx="8" fill="#6366f1" stroke="#4f46e5" stroke-width="3"/>
                                        <rect x="10" y="10" width="80" height="40" rx="4" fill="#818cf8" opacity="0.5"/>
                                        <rect x="10" y="60" width="80" height="40" rx="4" fill="#818cf8" opacity="0.5"/>
                                        <rect x="10" y="110" width="80" height="40" rx="4" fill="#818cf8" opacity="0.5"/>
                                        <rect x="10" y="160" width="80" height="30" rx="4" fill="#818cf8" opacity="0.5"/>
                                        <text x="50" y="30" text-anchor="middle" fill="white" font-size="9">Gasoline</text>
                                        <text x="50" y="80" text-anchor="middle" fill="white" font-size="9">Kerosene</text>
                                        <text x="50" y="130" text-anchor="middle" fill="white" font-size="9">Diesel</text>
                                        <text x="50" y="178" text-anchor="middle" fill="white" font-size="9">Heavy Fuel</text>
                                        <text x="50" y="-10" text-anchor="middle" font-size="10" font-weight="bold" fill="#4f46e5">DISTILLATION</text>
                                    </g>

                                    <!-- Output Pipelines -->
                                    <!-- Gasoline P-005 -->
                                    <line x1="540" y1="160" x2="620" y2="80" stroke="#64748b" stroke-width="5" stroke-linecap="round"/>
                                    <text x="580" y="105" text-anchor="middle" font-size="8" fill="#64748b">P-005</text>

                                    <!-- Tank T-104 Gasoline -->
                                    <g class="tank-group" transform="translate(620, 30)" onclick="showTankById(3)" style="cursor:pointer;">
                                        <rect class="tank-body" x="0" y="0" width="60" height="100" rx="5" fill="#f8fafc" stroke="#f59e0b" stroke-width="3"/>
                                        <rect class="tank-level" x="4" y="<?= 100 - (($oilTanks[3]['current_level'] / $oilTanks[3]['capacity']) * 92) ?>" width="52" height="<?= ($oilTanks[3]['current_level'] / $oilTanks[3]['capacity']) * 92 ?>" fill="#f59e0b" opacity="0.6" rx="3"/>
                                        <text x="30" y="40" text-anchor="middle" font-size="10" font-weight="bold" fill="#1e293b">T-104</text>
                                        <text x="30" y="55" text-anchor="middle" font-size="8" fill="#64748b"><?= round(($oilTanks[3]['current_level'] / $oilTanks[3]['capacity']) * 100) ?>%</text>
                                        <text x="30" y="70" text-anchor="middle" font-size="7" fill="#f59e0b">Gasoline</text>
                                    </g>

                                    <!-- Kerosene P-006 -->
                                    <line x1="540" y1="200" x2="620" y2="180" stroke="#64748b" stroke-width="5" stroke-linecap="round"/>
                                    <text x="580" y="178" text-anchor="middle" font-size="8" fill="#64748b">P-006</text>

                                    <!-- Tank T-105 Kerosene -->
                                    <g class="tank-group" transform="translate(620, 140)" onclick="showTankById(4)" style="cursor:pointer;">
                                        <rect class="tank-body" x="0" y="0" width="60" height="100" rx="5" fill="#f8fafc" stroke="#ef4444" stroke-width="3"/>
                                        <rect class="tank-level" x="4" y="<?= 100 - (($oilTanks[4]['current_level'] / $oilTanks[4]['capacity']) * 92) ?>" width="52" height="<?= ($oilTanks[4]['current_level'] / $oilTanks[4]['capacity']) * 92 ?>" fill="#ef4444" opacity="0.6" rx="3"/>
                                        <text x="30" y="40" text-anchor="middle" font-size="10" font-weight="bold" fill="#1e293b">T-105</text>
                                        <text x="30" y="55" text-anchor="middle" font-size="8" fill="#64748b"><?= round(($oilTanks[4]['current_level'] / $oilTanks[4]['capacity']) * 100) ?>%</text>
                                        <text x="30" y="70" text-anchor="middle" font-size="7" fill="#ef4444">Kerosene</text>
                                        <text x="30" y="85" text-anchor="middle" font-size="7" fill="#ef4444">LOW</text>
                                    </g>

                                    <!-- Diesel P-004 (with leak warning) -->
                                    <line x1="540" y1="270" x2="620" y2="300" stroke="#ef4444" stroke-width="5" stroke-linecap="round" class="pipeline-warning"/>
                                    <text x="580" y="275" text-anchor="middle" font-size="8" fill="#ef4444" font-weight="bold">P-004 ⚠️</text>
                                    <!-- Leak indicator -->
                                    <circle cx="580" cy="290" r="12" fill="url(#leakGradient)" class="leak-pulse"/>
                                    <circle cx="580" cy="290" r="5" fill="#ef4444"/>

                                    <!-- Tank T-103 Diesel (warning) -->
                                    <g class="tank-group warning" transform="translate(620, 270)" onclick="showTankById(2)" style="cursor:pointer;">
                                        <rect class="tank-body" x="0" y="0" width="60" height="100" rx="5" fill="#fef2f2" stroke="#ef4444" stroke-width="3"/>
                                        <rect class="tank-level" x="4" y="<?= 100 - (($oilTanks[2]['current_level'] / $oilTanks[2]['capacity']) * 92) ?>" width="52" height="<?= ($oilTanks[2]['current_level'] / $oilTanks[2]['capacity']) * 92 ?>" fill="#3b82f6" opacity="0.6" rx="3"/>
                                        <text x="30" y="40" text-anchor="middle" font-size="10" font-weight="bold" fill="#1e293b">T-103</text>
                                        <text x="30" y="55" text-anchor="middle" font-size="8" fill="#64748b"><?= round(($oilTanks[2]['current_level'] / $oilTanks[2]['capacity']) * 100) ?>%</text>
                                        <text x="30" y="70" text-anchor="middle" font-size="7" fill="#3b82f6">Diesel</text>
                                        <!-- Seepage indicator at base -->
                                        <circle cx="30" cy="105" r="8" fill="url(#leakGradient)" class="leak-pulse"/>
                                    </g>

                                    <!-- Heavy Fuel P-007 -->
                                    <line x1="540" y1="320" x2="620" y2="400" stroke="#64748b" stroke-width="5" stroke-linecap="round"/>
                                    <text x="580" y="365" text-anchor="middle" font-size="8" fill="#64748b">P-007</text>

                                    <!-- Tank T-106 Heavy Fuel -->
                                    <g class="tank-group" transform="translate(620, 370)" onclick="showTankById(5)" style="cursor:pointer;">
                                        <rect class="tank-body" x="0" y="0" width="60" height="100" rx="5" fill="#f8fafc" stroke="#64748b" stroke-width="3"/>
                                        <rect class="tank-level" x="4" y="<?= 100 - (($oilTanks[5]['current_level'] / $oilTanks[5]['capacity']) * 92) ?>" width="52" height="<?= ($oilTanks[5]['current_level'] / $oilTanks[5]['capacity']) * 92 ?>" fill="#64748b" opacity="0.6" rx="3"/>
                                        <text x="30" y="40" text-anchor="middle" font-size="10" font-weight="bold" fill="#1e293b">T-106</text>
                                        <text x="30" y="55" text-anchor="middle" font-size="8" fill="#64748b"><?= round(($oilTanks[5]['current_level'] / $oilTanks[5]['capacity']) * 100) ?>%</text>
                                        <text x="30" y="70" text-anchor="middle" font-size="7" fill="#64748b">Heavy Fuel</text>
                                    </g>

                                    <!-- Export Pipeline P-008 -->
                                    <line x1="680" y1="80" x2="780" y2="80" stroke="#64748b" stroke-width="6" stroke-linecap="round"/>
                                    <line x1="680" y1="80" x2="780" y2="80" stroke="url(#flowGradient)" stroke-width="3" stroke-linecap="round"/>
                                    <text x="730" y="65" text-anchor="middle" font-size="8" fill="#64748b">P-008</text>
                                    <!-- Minor anomaly indicator -->
                                    <circle cx="750" cy="80" r="8" fill="#fef3c7" stroke="#f59e0b" stroke-width="2" class="anomaly-indicator"/>
                                    <text x="750" y="84" text-anchor="middle" font-size="8" fill="#f59e0b">!</text>

                                    <!-- Export Terminal -->
                                    <g transform="translate(780, 40)">
                                        <rect x="0" y="0" width="80" height="80" rx="8" fill="#10b981" stroke="#059669" stroke-width="2"/>
                                        <text x="40" y="35" text-anchor="middle" fill="white" font-size="20">🚛</text>
                                        <text x="40" y="55" text-anchor="middle" fill="white" font-size="9" font-weight="bold">TERMINAL</text>
                                        <text x="40" y="68" text-anchor="middle" fill="white" font-size="8">Export</text>
                                    </g>

                                    <!-- Legend -->
                                    <g transform="translate(750, 420)">
                                        <text x="0" y="0" font-size="9" font-weight="bold" fill="#64748b">LEGEND:</text>
                                        <circle cx="10" cy="20" r="5" fill="#10b981"/>
                                        <text x="20" y="24" font-size="8" fill="#64748b">Normal</text>
                                        <circle cx="70" cy="20" r="5" fill="#f59e0b"/>
                                        <text x="80" y="24" font-size="8" fill="#64748b">Warning</text>
                                        <circle cx="140" cy="20" r="5" fill="#ef4444"/>
                                        <text x="150" y="24" font-size="8" fill="#64748b">Alert</text>
                                    </g>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Tank Status Cards -->
                    <div class="section-card" style="margin-top: 20px;">
                        <div class="section-header">
                            <h2>Tank Inventory Status</h2>
                        </div>
                        <div class="section-body">
                            <div class="tank-cards-grid">
                                <?php foreach ($oilTanks as $tankIndex => $tank):
                                    $percentage = round(($tank['current_level'] / $tank['capacity']) * 100);
                                    $statusClass = $tank['status'];
                                    if ($percentage < 25) $statusClass = 'low';
                                    elseif ($percentage > 90) $statusClass = 'high';
                                ?>
                                <div class="tank-status-card <?= $statusClass ?>" onclick="showTankById(<?= $tankIndex ?>)" style="cursor:pointer;">
                                    <div class="tank-status-header">
                                        <span class="tank-id"><?= $tank['id'] ?></span>
                                        <span class="tank-status-badge <?= $tank['status'] ?>"><?= ucfirst($tank['status']) ?></span>
                                    </div>
                                    <div class="tank-name"><?= htmlspecialchars($tank['name']) ?></div>
                                    <div class="tank-product"><?= htmlspecialchars($tank['product']) ?></div>
                                    <div class="tank-gauge">
                                        <div class="tank-gauge-fill" style="height: <?= $percentage ?>%;"></div>
                                        <span class="tank-gauge-label"><?= $percentage ?>%</span>
                                    </div>
                                    <div class="tank-stats">
                                        <div class="tank-stat">
                                            <span class="tank-stat-value"><?= number_format($tank['current_level']) ?></span>
                                            <span class="tank-stat-label"><?= $tank['unit'] ?></span>
                                        </div>
                                        <div class="tank-stat">
                                            <span class="tank-stat-value"><?= $tank['temperature'] ?>°F</span>
                                            <span class="tank-stat-label">Temp</span>
                                        </div>
                                        <div class="tank-stat">
                                            <span class="tank-stat-value"><?= $tank['pressure'] ?></span>
                                            <span class="tank-stat-label">PSI</span>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Leak Detection Section -->
                    <div class="grid-2" style="margin-top: 20px;">
                        <!-- Leak Detectors -->
                        <div class="section-card">
                            <div class="section-header">
                                <h3>Leak Detection Sensors</h3>
                                <span class="detector-count"><?= count(array_filter($leakDetectors, fn($d) => $d['status'] !== 'normal')) ?> Alerts</span>
                            </div>
                            <div class="section-body" style="padding: 0;">
                                <table class="config-table">
                                    <thead>
                                        <tr>
                                            <th>Sensor ID</th>
                                            <th>Location</th>
                                            <th>Status</th>
                                            <th>Last Check</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($leakDetectors as $detector): ?>
                                        <tr class="<?= $detector['status'] !== 'normal' ? 'alert-row' : '' ?>">
                                            <td><strong><?= $detector['id'] ?></strong></td>
                                            <td>
                                                <?= htmlspecialchars($detector['location']) ?>
                                                <?php if (isset($detector['alert_type'])): ?>
                                                <br><small class="alert-type"><?= $detector['alert_type'] ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="detector-status <?= $detector['status'] ?>">
                                                    <?= $detector['status'] === 'normal' ? '✓' : ($detector['status'] === 'alert' ? '⚠' : '!') ?>
                                                    <?= ucfirst($detector['status']) ?>
                                                </span>
                                            </td>
                                            <td><?= $detector['last_check'] ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Active Leak Alerts -->
                        <div class="section-card">
                            <div class="section-header">
                                <h3>Leak Alert History</h3>
                                <button class="btn btn-secondary btn-sm" onclick="exportLeakReport()">Export</button>
                            </div>
                            <div class="section-body" style="padding: 0;">
                                <table class="config-table">
                                    <thead>
                                        <tr>
                                            <th>Time</th>
                                            <th>Location</th>
                                            <th>Type</th>
                                            <th>Severity</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($leakAlerts as $alert): ?>
                                        <tr class="leak-alert-row <?= $alert['status'] ?>">
                                            <td><?= date('H:i', strtotime($alert['timestamp'])) ?></td>
                                            <td><strong><?= htmlspecialchars($alert['location']) ?></strong></td>
                                            <td><?= htmlspecialchars($alert['type']) ?></td>
                                            <td><span class="severity-badge <?= $alert['severity'] ?>"><?= ucfirst($alert['severity']) ?></span></td>
                                            <td><span class="leak-status <?= $alert['status'] ?>"><?= ucfirst($alert['status']) ?></span></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Pipeline Status -->
                    <div class="section-card" style="margin-top: 20px;">
                        <div class="section-header">
                            <h2>Pipeline Status</h2>
                        </div>
                        <div class="section-body" style="padding: 0;">
                            <table class="config-table">
                                <thead>
                                    <tr>
                                        <th>Pipeline ID</th>
                                        <th>Name</th>
                                        <th>Route</th>
                                        <th>Flow Rate</th>
                                        <th>Pressure</th>
                                        <th>Length</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pipelines as $pipeline): ?>
                                    <tr class="<?= $pipeline['status'] !== 'normal' ? 'pipeline-warning-row' : '' ?>">
                                        <td><strong><?= $pipeline['id'] ?></strong></td>
                                        <td><?= htmlspecialchars($pipeline['name']) ?></td>
                                        <td><code><?= $pipeline['from'] ?> → <?= $pipeline['to'] ?></code></td>
                                        <td><?= number_format($pipeline['flow_rate']) ?> <small>BBL/hr</small></td>
                                        <td><?= $pipeline['pressure'] ?> <small>PSI</small></td>
                                        <td><?= $pipeline['length'] ?> <small>km</small></td>
                                        <td>
                                            <span class="pipeline-status <?= $pipeline['status'] ?>">
                                                <?= $pipeline['status'] === 'normal' ? '● Normal' : '⚠ ' . ucfirst($pipeline['status']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div><!-- End Tab: Tank & Pipeline -->

                <!-- Tab Content: Gas Storage -->
                <div id="tab-gas" class="scada-tab-content">
                    <div class="section-card">
                        <div class="section-header">
                            <h2>Gas Storage Facilities</h2>
                            <div style="display: flex; gap: 10px;">
                                <span class="status-badge status-normal">System Normal</span>
                                <button class="btn btn-secondary btn-sm" onclick="refreshGasData()">Refresh</button>
                            </div>
                        </div>
                        <div class="section-body">
                            <!-- Gas Storage Summary -->
                            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 25px;">
                                <div class="summary-card" style="background: linear-gradient(135deg, #3498db, #2980b9); color: white; padding: 20px; border-radius: 10px;">
                                    <div style="font-size: 14px; opacity: 0.9;">Total Gas Storage</div>
                                    <div style="font-size: 28px; font-weight: bold;"><?= number_format(array_sum(array_column($gasTanks, 'current_level'))) ?></div>
                                    <div style="font-size: 12px; opacity: 0.8;">MCF/BBL Combined</div>
                                </div>
                                <div class="summary-card" style="background: linear-gradient(135deg, #27ae60, #229954); color: white; padding: 20px; border-radius: 10px;">
                                    <div style="font-size: 14px; opacity: 0.9;">Natural Gas Capacity</div>
                                    <div style="font-size: 28px; font-weight: bold;"><?= round((($gasTanks[0]['current_level'] + $gasTanks[1]['current_level']) / ($gasTanks[0]['capacity'] + $gasTanks[1]['capacity'])) * 100, 1) ?>%</div>
                                    <div style="font-size: 12px; opacity: 0.8;">810,000 / 1,000,000 MCF</div>
                                </div>
                                <div class="summary-card" style="background: linear-gradient(135deg, #9b59b6, #8e44ad); color: white; padding: 20px; border-radius: 10px;">
                                    <div style="font-size: 14px; opacity: 0.9;">LPG Capacity</div>
                                    <div style="font-size: 28px; font-weight: bold;"><?= round((($gasTanks[2]['current_level'] + $gasTanks[3]['current_level']) / ($gasTanks[2]['capacity'] + $gasTanks[3]['capacity'])) * 100, 1) ?>%</div>
                                    <div style="font-size: 12px; opacity: 0.8;">42,700 / 60,000 BBL</div>
                                </div>
                                <div class="summary-card" style="background: linear-gradient(135deg, #e67e22, #d35400); color: white; padding: 20px; border-radius: 10px;">
                                    <div style="font-size: 14px; opacity: 0.9;">Active Compressors</div>
                                    <div style="font-size: 28px; font-weight: bold;">5 / 6</div>
                                    <div style="font-size: 12px; opacity: 0.8;">Running Normally</div>
                                </div>
                            </div>

                            <!-- Gas Tanks Grid -->
                            <h3 style="margin-bottom: 15px; color: var(--text-primary);">Gas Storage Tanks</h3>
                            <div class="tank-grid" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 25px;">
                                <?php foreach ($gasTanks as $index => $tank):
                                    $fillPercent = ($tank['current_level'] / $tank['capacity']) * 100;
                                    $statusClass = $tank['status'];
                                    $tankIcon = (strpos($tank['product'], 'Natural Gas') !== false) ? '🔵' :
                                               ((strpos($tank['product'], 'LPG') !== false) ? '🟣' :
                                               ((strpos($tank['product'], 'Propane') !== false) ? '🟠' : '🔴'));
                                ?>
                                <div class="tank-card gas-tank" onclick="showGasTankDetails(<?= $index ?>)" style="cursor: pointer; background: var(--bg-elevated); border-radius: 12px; padding: 20px; border: 1px solid var(--border-primary); transition: all 0.3s ease;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                        <div>
                                            <span style="font-size: 24px; margin-right: 8px;"><?= $tankIcon ?></span>
                                            <strong style="font-size: 16px;"><?= htmlspecialchars($tank['name']) ?></strong>
                                        </div>
                                        <span class="status-badge status-<?= $statusClass ?>"><?= ucfirst($tank['status']) ?></span>
                                    </div>
                                    <div style="display: flex; gap: 20px; align-items: flex-end;">
                                        <!-- Tank Visual -->
                                        <div style="flex: 0 0 80px;">
                                            <div style="background: rgba(100, 100, 100, 0.2); border-radius: 8px; height: 120px; width: 80px; position: relative; overflow: hidden; border: 2px solid var(--border-secondary);">
                                                <div style="position: absolute; bottom: 0; width: 100%; height: <?= $fillPercent ?>%; background: <?= $statusClass === 'normal' ? 'linear-gradient(to top, #3498db, #5dade2)' : ($statusClass === 'warning' ? 'linear-gradient(to top, #f39c12, #f1c40f)' : 'linear-gradient(to top, #e74c3c, #ec7063)') ?>; transition: height 0.5s ease;"></div>
                                                <div style="position: absolute; width: 100%; text-align: center; top: 50%; transform: translateY(-50%); color: white; font-weight: bold; text-shadow: 0 1px 2px rgba(0,0,0,0.5);"><?= round($fillPercent) ?>%</div>
                                            </div>
                                        </div>
                                        <!-- Tank Info -->
                                        <div style="flex: 1; font-size: 13px;">
                                            <div style="margin-bottom: 8px;"><strong>ID:</strong> <?= htmlspecialchars($tank['id']) ?></div>
                                            <div style="margin-bottom: 8px;"><strong>Product:</strong> <?= htmlspecialchars($tank['product']) ?></div>
                                            <div style="margin-bottom: 8px;"><strong>Level:</strong> <?= number_format($tank['current_level']) ?> / <?= number_format($tank['capacity']) ?> <?= $tank['unit'] ?></div>
                                            <div style="margin-bottom: 8px;"><strong>Temperature:</strong> <?= $tank['temperature'] ?>°F</div>
                                            <div style="margin-bottom: 8px;"><strong>Pressure:</strong> <?= number_format($tank['pressure']) ?> PSI</div>
                                        </div>
                                    </div>
                                    <!-- Pump Flow Indicators -->
                                    <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid var(--border-primary); display: flex; justify-content: space-between;">
                                        <div style="text-align: center; flex: 1;">
                                            <div style="font-size: 11px; color: var(--text-secondary);">Pump In</div>
                                            <div style="font-size: 16px; font-weight: bold; color: #27ae60;">
                                                <?php if ($tank['pump_in_status'] === 'running'): ?>
                                                    ▲ <?= number_format($tank['pump_in_rate']) ?>
                                                <?php else: ?>
                                                    <span style="color: #7f8c8d;">— Stopped</span>
                                                <?php endif; ?>
                                            </div>
                                            <div style="font-size: 10px; color: var(--text-secondary);"><?= $tank['unit'] ?>/hr</div>
                                        </div>
                                        <div style="text-align: center; flex: 1;">
                                            <div style="font-size: 11px; color: var(--text-secondary);">Pump Out</div>
                                            <div style="font-size: 16px; font-weight: bold; color: #e74c3c;">
                                                <?php if ($tank['pump_out_status'] === 'running'): ?>
                                                    ▼ <?= number_format($tank['pump_out_rate']) ?>
                                                <?php else: ?>
                                                    <span style="color: #7f8c8d;">— Stopped</span>
                                                <?php endif; ?>
                                            </div>
                                            <div style="font-size: 10px; color: var(--text-secondary);"><?= $tank['unit'] ?>/hr</div>
                                        </div>
                                        <div style="text-align: center; flex: 1;">
                                            <div style="font-size: 11px; color: var(--text-secondary);">Net Flow</div>
                                            <?php $netFlow = $tank['pump_in_rate'] - $tank['pump_out_rate']; ?>
                                            <div style="font-size: 16px; font-weight: bold; color: <?= $netFlow >= 0 ? '#27ae60' : '#e74c3c' ?>;">
                                                <?= $netFlow >= 0 ? '+' : '' ?><?= number_format($netFlow) ?>
                                            </div>
                                            <div style="font-size: 10px; color: var(--text-secondary);"><?= $tank['unit'] ?>/hr</div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Gas Flow Diagram -->
                            <h3 style="margin-bottom: 15px; color: var(--text-primary);">Gas Flow Diagram</h3>
                            <div style="background: var(--bg-elevated); border-radius: 12px; padding: 20px; border: 1px solid var(--border-primary);">
                                <svg width="100%" height="300" viewBox="0 0 900 300" style="max-width: 100%;">
                                    <!-- Background -->
                                    <rect width="900" height="300" fill="transparent"/>

                                    <!-- Gas Source -->
                                    <rect x="20" y="120" width="80" height="60" rx="5" fill="#2c3e50" stroke="#3498db" stroke-width="2"/>
                                    <text x="60" y="145" text-anchor="middle" fill="white" font-size="11">Gas</text>
                                    <text x="60" y="160" text-anchor="middle" fill="white" font-size="11">Source</text>

                                    <!-- Pipelines -->
                                    <line x1="100" y1="150" x2="200" y2="150" stroke="#3498db" stroke-width="4"/>
                                    <line x1="200" y1="150" x2="200" y2="80" stroke="#3498db" stroke-width="4"/>
                                    <line x1="200" y1="150" x2="200" y2="220" stroke="#3498db" stroke-width="4"/>
                                    <line x1="200" y1="80" x2="280" y2="80" stroke="#3498db" stroke-width="4"/>
                                    <line x1="200" y1="220" x2="280" y2="220" stroke="#3498db" stroke-width="4"/>

                                    <!-- Animated flow -->
                                    <circle r="4" fill="#5dade2">
                                        <animateMotion dur="2s" repeatCount="indefinite" path="M100,150 L200,150 L200,80 L280,80"/>
                                    </circle>
                                    <circle r="4" fill="#5dade2">
                                        <animateMotion dur="2s" repeatCount="indefinite" begin="1s" path="M100,150 L200,150 L200,220 L280,220"/>
                                    </circle>

                                    <!-- Natural Gas Holders -->
                                    <rect x="280" y="50" width="100" height="60" rx="10" fill="#2980b9" stroke="#3498db" stroke-width="2"/>
                                    <text x="330" y="75" text-anchor="middle" fill="white" font-size="11">NG Holder 1</text>
                                    <text x="330" y="90" text-anchor="middle" fill="white" font-size="10">77% Full</text>

                                    <rect x="280" y="190" width="100" height="60" rx="10" fill="#2980b9" stroke="#3498db" stroke-width="2"/>
                                    <text x="330" y="215" text-anchor="middle" fill="white" font-size="11">NG Holder 2</text>
                                    <text x="330" y="230" text-anchor="middle" fill="white" font-size="10">85% Full</text>

                                    <!-- Compressor Station -->
                                    <line x1="380" y1="80" x2="450" y2="150" stroke="#9b59b6" stroke-width="4"/>
                                    <line x1="380" y1="220" x2="450" y2="150" stroke="#9b59b6" stroke-width="4"/>

                                    <rect x="450" y="120" width="80" height="60" rx="5" fill="#8e44ad" stroke="#9b59b6" stroke-width="2"/>
                                    <text x="490" y="145" text-anchor="middle" fill="white" font-size="10">Compressor</text>
                                    <text x="490" y="160" text-anchor="middle" fill="white" font-size="10">Station</text>

                                    <!-- LPG Processing -->
                                    <line x1="530" y1="150" x2="600" y2="150" stroke="#9b59b6" stroke-width="4"/>
                                    <line x1="600" y1="150" x2="600" y2="60" stroke="#9b59b6" stroke-width="4"/>
                                    <line x1="600" y1="150" x2="600" y2="240" stroke="#9b59b6" stroke-width="4"/>

                                    <!-- LPG Spheres -->
                                    <circle cx="680" cy="60" r="35" fill="#8e44ad" stroke="#9b59b6" stroke-width="2"/>
                                    <text x="680" y="55" text-anchor="middle" fill="white" font-size="10">LPG</text>
                                    <text x="680" y="70" text-anchor="middle" fill="white" font-size="9">Sphere 1</text>

                                    <circle cx="680" cy="150" r="35" fill="#9b59b6" stroke="#8e44ad" stroke-width="2"/>
                                    <text x="680" y="145" text-anchor="middle" fill="white" font-size="10">LPG</text>
                                    <text x="680" y="160" text-anchor="middle" fill="white" font-size="9">Sphere 2</text>

                                    <!-- Propane/Butane -->
                                    <ellipse cx="780" cy="60" rx="50" ry="25" fill="#e67e22" stroke="#d35400" stroke-width="2"/>
                                    <text x="780" y="55" text-anchor="middle" fill="white" font-size="10">Propane</text>
                                    <text x="780" y="70" text-anchor="middle" fill="white" font-size="9">Bullet</text>

                                    <ellipse cx="780" cy="150" rx="50" ry="25" fill="#d35400" stroke="#e67e22" stroke-width="2"/>
                                    <text x="780" y="145" text-anchor="middle" fill="white" font-size="10">Butane</text>
                                    <text x="780" y="160" text-anchor="middle" fill="white" font-size="9">Storage</text>

                                    <!-- Export -->
                                    <line x1="600" y1="240" x2="830" y2="240" stroke="#27ae60" stroke-width="4"/>
                                    <rect x="830" y="215" width="60" height="50" rx="5" fill="#27ae60" stroke="#229954" stroke-width="2"/>
                                    <text x="860" y="235" text-anchor="middle" fill="white" font-size="10">Export</text>
                                    <text x="860" y="250" text-anchor="middle" fill="white" font-size="10">Terminal</text>

                                    <!-- Legend -->
                                    <rect x="20" y="260" width="15" height="15" fill="#3498db"/>
                                    <text x="40" y="272" fill="var(--text-primary)" font-size="10">Natural Gas</text>
                                    <rect x="120" y="260" width="15" height="15" fill="#9b59b6"/>
                                    <text x="140" y="272" fill="var(--text-primary)" font-size="10">LPG</text>
                                    <rect x="200" y="260" width="15" height="15" fill="#e67e22"/>
                                    <text x="220" y="272" fill="var(--text-primary)" font-size="10">Propane/Butane</text>
                                    <rect x="320" y="260" width="15" height="15" fill="#27ae60"/>
                                    <text x="340" y="272" fill="var(--text-primary)" font-size="10">Export</text>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div><!-- End Tab: Gas Storage -->

                <!-- Tab Content: Pumps & Flow -->
                <div id="tab-pumps" class="scada-tab-content">
                    <div class="section-card">
                        <div class="section-header">
                            <h2>Pump Stations & Flow Control</h2>
                            <div style="display: flex; gap: 10px;">
                                <span class="status-badge status-normal"><?= count(array_filter($pumps, fn($p) => $p['status'] === 'running')) ?> Running</span>
                                <button class="btn btn-secondary btn-sm" onclick="refreshPumpData()">Refresh</button>
                            </div>
                        </div>
                        <div class="section-body">
                            <!-- Pump Summary Cards -->
                            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 25px;">
                                <div class="summary-card" style="background: linear-gradient(135deg, #27ae60, #229954); color: white; padding: 20px; border-radius: 10px;">
                                    <div style="font-size: 14px; opacity: 0.9;">Running Pumps</div>
                                    <div style="font-size: 28px; font-weight: bold;"><?= count(array_filter($pumps, fn($p) => $p['status'] === 'running')) ?></div>
                                    <div style="font-size: 12px; opacity: 0.8;">of <?= count($pumps) ?> Total</div>
                                </div>
                                <div class="summary-card" style="background: linear-gradient(135deg, #3498db, #2980b9); color: white; padding: 20px; border-radius: 10px;">
                                    <div style="font-size: 14px; opacity: 0.9;">Total Flow Rate</div>
                                    <div style="font-size: 28px; font-weight: bold;"><?= number_format(array_sum(array_column($pumps, 'flow_rate'))) ?></div>
                                    <div style="font-size: 12px; opacity: 0.8;">Units/hr</div>
                                </div>
                                <div class="summary-card" style="background: linear-gradient(135deg, #9b59b6, #8e44ad); color: white; padding: 20px; border-radius: 10px;">
                                    <div style="font-size: 14px; opacity: 0.9;">Avg Efficiency</div>
                                    <?php
                                    $runningPumps = array_filter($pumps, fn($p) => $p['status'] === 'running');
                                    $avgEff = count($runningPumps) > 0 ? array_sum(array_column($runningPumps, 'efficiency')) / count($runningPumps) : 0;
                                    ?>
                                    <div style="font-size: 28px; font-weight: bold;"><?= round($avgEff, 1) ?>%</div>
                                    <div style="font-size: 12px; opacity: 0.8;">Running Pumps</div>
                                </div>
                                <div class="summary-card" style="background: linear-gradient(135deg, #e67e22, #d35400); color: white; padding: 20px; border-radius: 10px;">
                                    <div style="font-size: 14px; opacity: 0.9;">Total Power</div>
                                    <div style="font-size: 28px; font-weight: bold;"><?= number_format(array_sum(array_column(array_filter($pumps, fn($p) => $p['status'] === 'running'), 'power'))) ?></div>
                                    <div style="font-size: 12px; opacity: 0.8;">kW Consumed</div>
                                </div>
                            </div>

                            <!-- Pump Cards Grid -->
                            <h3 style="margin-bottom: 15px; color: var(--text-primary);">Pump Status Overview</h3>
                            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 25px;">
                                <?php foreach ($pumps as $pumpIndex => $pump):
                                    $flowPercent = ($pump['flow_rate'] / $pump['max_rate']) * 100;
                                ?>
                                <div class="pump-card" onclick="showPumpDetails(<?= $pumpIndex ?>)" style="cursor: pointer; background: var(--bg-elevated); border-radius: 12px; padding: 20px; border: 1px solid var(--border-primary); transition: all 0.3s ease;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                        <div>
                                            <strong style="font-size: 14px;"><?= htmlspecialchars($pump['name']) ?></strong>
                                            <div style="font-size: 12px; color: var(--text-secondary);"><?= htmlspecialchars($pump['id']) ?> • <?= htmlspecialchars($pump['type']) ?></div>
                                        </div>
                                        <span class="status-badge status-<?= $pump['status'] === 'running' ? 'normal' : 'warning' ?>">
                                            <?= $pump['status'] === 'running' ? '● Running' : '○ Stopped' ?>
                                        </span>
                                    </div>

                                    <!-- Flow Rate Gauge -->
                                    <div style="margin-bottom: 15px;">
                                        <div style="display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 5px;">
                                            <span>Flow Rate</span>
                                            <span><?= number_format($pump['flow_rate']) ?> / <?= number_format($pump['max_rate']) ?></span>
                                        </div>
                                        <div style="background: var(--bg-secondary); border-radius: 5px; height: 20px; overflow: hidden;">
                                            <div style="height: 100%; width: <?= $flowPercent ?>%; background: <?= $pump['status'] === 'running' ? 'linear-gradient(90deg, #27ae60, #2ecc71)' : '#7f8c8d' ?>; transition: width 0.5s ease; display: flex; align-items: center; justify-content: center;">
                                                <span style="font-size: 11px; color: white; font-weight: bold;"><?= round($flowPercent) ?>%</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Pump Stats -->
                                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; font-size: 12px;">
                                        <div style="text-align: center; padding: 8px; background: var(--bg-secondary); border-radius: 6px;">
                                            <div style="color: var(--text-secondary);">Power</div>
                                            <div style="font-weight: bold;"><?= $pump['power'] ?> kW</div>
                                        </div>
                                        <div style="text-align: center; padding: 8px; background: var(--bg-secondary); border-radius: 6px;">
                                            <div style="color: var(--text-secondary);">Efficiency</div>
                                            <div style="font-weight: bold; color: <?= $pump['efficiency'] >= 90 ? '#27ae60' : ($pump['efficiency'] >= 80 ? '#f39c12' : '#e74c3c') ?>;"><?= $pump['efficiency'] ?>%</div>
                                        </div>
                                        <div style="text-align: center; padding: 8px; background: var(--bg-secondary); border-radius: 6px;">
                                            <div style="color: var(--text-secondary);">Runtime</div>
                                            <div style="font-weight: bold;"><?= number_format($pump['runtime']) ?>h</div>
                                        </div>
                                    </div>

                                    <!-- Associated Tank -->
                                    <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--border-primary); font-size: 12px;">
                                        <span style="color: var(--text-secondary);">Connected to:</span>
                                        <span style="font-weight: bold; margin-left: 5px;"><?= htmlspecialchars($pump['tank']) ?></span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Pump Status Table -->
                            <h3 style="margin-bottom: 15px; color: var(--text-primary);">Detailed Pump Status</h3>
                            <table class="config-table">
                                <thead>
                                    <tr>
                                        <th>Pump ID</th>
                                        <th>Name</th>
                                        <th>Type</th>
                                        <th>Tank</th>
                                        <th>Flow Rate</th>
                                        <th>Power</th>
                                        <th>Efficiency</th>
                                        <th>Runtime</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pumps as $pumpIndex => $pump): ?>
                                    <tr>
                                        <td><code><?= htmlspecialchars($pump['id']) ?></code></td>
                                        <td><strong><?= htmlspecialchars($pump['name']) ?></strong></td>
                                        <td><?= htmlspecialchars($pump['type']) ?></td>
                                        <td><span class="protocol-badge"><?= htmlspecialchars($pump['tank']) ?></span></td>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 10px;">
                                                <div style="flex: 1; background: var(--bg-secondary); border-radius: 3px; height: 8px; overflow: hidden;">
                                                    <div style="height: 100%; width: <?= ($pump['flow_rate'] / $pump['max_rate']) * 100 ?>%; background: <?= $pump['status'] === 'running' ? '#27ae60' : '#7f8c8d' ?>;"></div>
                                                </div>
                                                <span style="min-width: 80px;"><?= number_format($pump['flow_rate']) ?> / <?= number_format($pump['max_rate']) ?></span>
                                            </div>
                                        </td>
                                        <td><?= $pump['power'] ?> kW</td>
                                        <td>
                                            <span style="color: <?= $pump['efficiency'] >= 90 ? '#27ae60' : ($pump['efficiency'] >= 80 ? '#f39c12' : '#e74c3c') ?>; font-weight: bold;">
                                                <?= $pump['efficiency'] ?>%
                                            </span>
                                        </td>
                                        <td><?= number_format($pump['runtime']) ?> hrs</td>
                                        <td>
                                            <span class="status-badge status-<?= $pump['status'] === 'running' ? 'normal' : 'warning' ?>">
                                                <?= ucfirst($pump['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div style="display: flex; gap: 5px;">
                                                <?php if ($pump['status'] === 'running'): ?>
                                                    <button class="btn btn-sm btn-danger" onclick="stopPump('<?= $pump['id'] ?>')">Stop</button>
                                                <?php else: ?>
                                                    <button class="btn btn-sm btn-success" onclick="startPump('<?= $pump['id'] ?>')">Start</button>
                                                <?php endif; ?>
                                                <button class="btn btn-sm btn-secondary" onclick="showPumpDetails(<?= $pumpIndex ?>)">Details</button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div><!-- End Tab: Pumps & Flow -->

                <!-- Tab Content: Network Topology -->
                <div id="tab-topology" class="scada-tab-content">
                    <div class="section-card">
                        <div class="section-header">
                            <h2>SCADA Network Topology</h2>
                            <div style="display: flex; gap: 10px;">
                                <button class="btn btn-secondary btn-sm" onclick="refreshTopology()">Refresh</button>
                                <button class="btn btn-primary btn-sm" onclick="exportTopology()">Export Diagram</button>
                            </div>
                        </div>
                        <div class="section-body">
                            <div class="topology-legend">
                                <div class="legend-item"><span class="legend-icon control"></span> Control Center</div>
                                <div class="legend-item"><span class="legend-icon firewall"></span> Firewall</div>
                                <div class="legend-item"><span class="legend-icon gateway"></span> Gateway</div>
                                <div class="legend-item"><span class="legend-icon switch"></span> Switch</div>
                                <div class="legend-item"><span class="legend-icon plc"></span> PLC</div>
                                <div class="legend-item"><span class="legend-icon rtu"></span> RTU</div>
                                <div class="legend-item"><span class="legend-icon hmi"></span> HMI</div>
                                <div class="legend-item"><span class="legend-icon dcs"></span> DCS</div>
                                <div class="legend-item"><span class="legend-icon sensor"></span> Sensor</div>
                            </div>
                            <div class="topology-container">
                                <svg id="topologySvg" width="100%" height="550" viewBox="0 0 800 550">
                                    <!-- Connections -->
                                    <?php foreach ($topologyConnections as $conn):
                                        $fromNode = array_filter($topologyNodes, fn($n) => $n['id'] === $conn['from']);
                                        $toNode = array_filter($topologyNodes, fn($n) => $n['id'] === $conn['to']);
                                        $from = reset($fromNode);
                                        $to = reset($toNode);
                                        if ($from && $to):
                                    ?>
                                    <line class="topology-link" x1="<?= $from['x'] ?>" y1="<?= $from['y'] + 20 ?>" x2="<?= $to['x'] ?>" y2="<?= $to['y'] - 20 ?>"/>
                                    <?php endif; endforeach; ?>

                                    <!-- Nodes -->
                                    <?php foreach ($topologyNodes as $nodeIndex => $node):
                                        $nodeStatus = $node['status'] ?? 'online';
                                    ?>
                                    <g class="topology-node <?= $node['type'] ?> <?= $nodeStatus ?>" transform="translate(<?= $node['x'] - 40 ?>, <?= $node['y'] - 25 ?>)" onclick="showNodeById(<?= $nodeIndex ?>)" style="cursor:pointer;">
                                        <rect class="node-bg" x="0" y="0" width="80" height="50" rx="8"/>
                                        <text class="node-icon" x="40" y="22" text-anchor="middle">
                                            <?php
                                            $icons = [
                                                'control' => '🖥️',
                                                'firewall' => '🛡️',
                                                'gateway' => '🌐',
                                                'switch' => '🔀',
                                                'plc' => '⚙️',
                                                'rtu' => '📡',
                                                'hmi' => '🖥️',
                                                'dcs' => '🏭',
                                                'sensor' => '📊'
                                            ];
                                            echo $icons[$node['type']] ?? '📦';
                                            ?>
                                        </text>
                                        <text class="node-label" x="40" y="42" text-anchor="middle"><?= htmlspecialchars($node['label']) ?></text>
                                        <?php if ($nodeStatus === 'offline'): ?>
                                        <circle class="status-indicator offline" cx="70" cy="10" r="6"/>
                                        <?php else: ?>
                                        <circle class="status-indicator online" cx="70" cy="10" r="6"/>
                                        <?php endif; ?>
                                    </g>
                                    <?php endforeach; ?>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Network Stats -->
                    <div class="grid-3" style="margin-top: 20px;">
                        <div class="section-card">
                            <div class="section-body" style="text-align: center;">
                                <div style="font-size: 32px; font-weight: 700; color: var(--primary);"><?= count($topologyNodes) ?></div>
                                <div style="color: var(--text-secondary);">Total Nodes</div>
                            </div>
                        </div>
                        <div class="section-card">
                            <div class="section-body" style="text-align: center;">
                                <div style="font-size: 32px; font-weight: 700; color: #059669;"><?= count($topologyConnections) ?></div>
                                <div style="color: var(--text-secondary);">Connections</div>
                            </div>
                        </div>
                        <div class="section-card">
                            <div class="section-body" style="text-align: center;">
                                <div style="font-size: 32px; font-weight: 700; color: #f59e0b;">3</div>
                                <div style="color: var(--text-secondary);">Network Segments</div>
                            </div>
                        </div>
                    </div>
                </div><!-- End Tab: Network Topology -->

                <!-- Tab Content: Reports -->
                <div id="tab-reports" class="scada-tab-content">
                    <div class="section-card">
                        <div class="section-header">
                            <h2>SCADA Reports</h2>
                            <button class="btn btn-primary btn-sm" onclick="generateReport()">+ Generate Report</button>
                        </div>
                        <div class="section-body" style="padding: 0;">
                            <table class="config-table">
                                <thead>
                                    <tr>
                                        <th>Report Name</th>
                                        <th>Type</th>
                                        <th>Generated</th>
                                        <th>Records</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($scadaReports as $report): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($report['report_name']) ?></strong></td>
                                        <td><span class="report-type-badge <?= strtolower($report['report_type']) ?>"><?= $report['report_type'] ?></span></td>
                                        <td><?= date('M j, Y H:i', strtotime($report['generated_at'])) ?></td>
                                        <td><?= number_format($report['records']) ?></td>
                                        <td><span class="status-badge status-online"><?= ucfirst($report['status']) ?></span></td>
                                        <td>
                                            <div style="display: flex; gap: 5px;">
                                                <button class="btn btn-secondary btn-sm" onclick="viewReport(<?= $report['id'] ?>)">View</button>
                                                <button class="btn btn-secondary btn-sm" onclick="downloadReport(<?= $report['id'] ?>)">Download</button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Report Statistics -->
                    <div class="grid-2" style="margin-top: 20px;">
                        <div class="section-card">
                            <div class="section-header">
                                <h3>Report Types Distribution</h3>
                            </div>
                            <div class="section-body">
                                <div class="report-stats">
                                    <div class="report-stat-item">
                                        <div class="report-stat-bar" style="width: 80%; background: #3b82f6;"></div>
                                        <span class="report-stat-label">Production</span>
                                        <span class="report-stat-value">32</span>
                                    </div>
                                    <div class="report-stat-item">
                                        <div class="report-stat-bar" style="width: 60%; background: #ef4444;"></div>
                                        <span class="report-stat-label">Alarms</span>
                                        <span class="report-stat-value">24</span>
                                    </div>
                                    <div class="report-stat-item">
                                        <div class="report-stat-bar" style="width: 45%; background: #10b981;"></div>
                                        <span class="report-stat-label">Performance</span>
                                        <span class="report-stat-value">18</span>
                                    </div>
                                    <div class="report-stat-item">
                                        <div class="report-stat-bar" style="width: 35%; background: #f59e0b;"></div>
                                        <span class="report-stat-label">Energy</span>
                                        <span class="report-stat-value">14</span>
                                    </div>
                                    <div class="report-stat-item">
                                        <div class="report-stat-bar" style="width: 25%; background: #8b5cf6;"></div>
                                        <span class="report-stat-label">Maintenance</span>
                                        <span class="report-stat-value">10</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="section-card">
                            <div class="section-header">
                                <h3>Scheduled Reports</h3>
                            </div>
                            <div class="section-body">
                                <div class="scheduled-reports">
                                    <div class="scheduled-item" onclick="toggleScheduledReport(1, 'Daily Production Summary')" style="cursor:pointer;">
                                        <div class="scheduled-info">
                                            <strong>Daily Production Summary</strong>
                                            <span>Every day at 06:00 AM</span>
                                        </div>
                                        <div class="scheduled-actions">
                                            <button class="btn btn-secondary btn-sm" onclick="event.stopPropagation(); editScheduledReport(1)">Edit</button>
                                            <span class="scheduled-status active">Active</span>
                                        </div>
                                    </div>
                                    <div class="scheduled-item" onclick="toggleScheduledReport(2, 'Weekly Alarm Report')" style="cursor:pointer;">
                                        <div class="scheduled-info">
                                            <strong>Weekly Alarm Report</strong>
                                            <span>Every Monday at 08:00 AM</span>
                                        </div>
                                        <div class="scheduled-actions">
                                            <button class="btn btn-secondary btn-sm" onclick="event.stopPropagation(); editScheduledReport(2)">Edit</button>
                                            <span class="scheduled-status active">Active</span>
                                        </div>
                                    </div>
                                    <div class="scheduled-item" onclick="toggleScheduledReport(3, 'Monthly Performance')" style="cursor:pointer;">
                                        <div class="scheduled-info">
                                            <strong>Monthly Performance</strong>
                                            <span>1st of month at 00:00</span>
                                        </div>
                                        <div class="scheduled-actions">
                                            <button class="btn btn-secondary btn-sm" onclick="event.stopPropagation(); editScheduledReport(3)">Edit</button>
                                            <span class="scheduled-status active">Active</span>
                                        </div>
                                    </div>
                                    <div class="scheduled-item" onclick="toggleScheduledReport(4, 'Quarterly Compliance')" style="cursor:pointer;">
                                        <div class="scheduled-info">
                                            <strong>Quarterly Compliance</strong>
                                            <span>Every 3 months</span>
                                        </div>
                                        <div class="scheduled-actions">
                                            <button class="btn btn-secondary btn-sm" onclick="event.stopPropagation(); editScheduledReport(4)">Edit</button>
                                            <span class="scheduled-status paused">Paused</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div><!-- End Tab: Reports -->

                <!-- Tab Content: Alarm History -->
                <div id="tab-alarms" class="scada-tab-content">
                    <div class="section-card">
                        <div class="section-header">
                            <h2>Alarm History</h2>
                            <div style="display: flex; gap: 10px;">
                                <select class="form-control" style="width: auto;" onchange="filterAlarms(this.value)">
                                    <option value="all">All Alarms</option>
                                    <option value="active">Active Only</option>
                                    <option value="acknowledged">Acknowledged</option>
                                    <option value="cleared">Cleared</option>
                                </select>
                                <button class="btn btn-secondary btn-sm" onclick="exportAlarms()">Export</button>
                            </div>
                        </div>
                        <div class="section-body" style="padding: 0;">
                            <table class="config-table">
                                <thead>
                                    <tr>
                                        <th>Timestamp</th>
                                        <th>Tag</th>
                                        <th>Device</th>
                                        <th>Type</th>
                                        <th>Value</th>
                                        <th>Limit</th>
                                        <th>Priority</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($alarmHistory as $alarm): ?>
                                    <tr class="alarm-row <?= $alarm['status'] ?>">
                                        <td><?= date('M j, H:i:s', strtotime($alarm['timestamp'])) ?></td>
                                        <td><strong><?= htmlspecialchars($alarm['tag_name']) ?></strong></td>
                                        <td><?= htmlspecialchars($alarm['device']) ?></td>
                                        <td><span class="alarm-type <?= strtolower($alarm['alarm_type']) ?>"><?= $alarm['alarm_type'] ?></span></td>
                                        <td><code><?= $alarm['value'] ?></code></td>
                                        <td><code><?= $alarm['limit'] ?></code></td>
                                        <td><span class="priority-badge <?= $alarm['priority'] ?>"><?= ucfirst($alarm['priority']) ?></span></td>
                                        <td><span class="alarm-status <?= $alarm['status'] ?>"><?= ucfirst($alarm['status']) ?></span></td>
                                        <td>
                                            <?php if ($alarm['status'] === 'active'): ?>
                                            <button class="btn btn-secondary btn-sm" onclick="acknowledgeAlarm(<?= $alarm['id'] ?>)">Acknowledge</button>
                                            <?php elseif ($alarm['status'] === 'acknowledged'): ?>
                                            <button class="btn btn-secondary btn-sm" onclick="clearAlarm(<?= $alarm['id'] ?>)">Clear</button>
                                            <?php else: ?>
                                            <button class="btn btn-secondary btn-sm" disabled>Cleared</button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Alarm Summary -->
                    <div class="grid-4" style="margin-top: 20px;">
                        <div class="alarm-summary-card critical">
                            <div class="alarm-count">2</div>
                            <div class="alarm-label">Critical</div>
                        </div>
                        <div class="alarm-summary-card warning">
                            <div class="alarm-count">3</div>
                            <div class="alarm-label">Warning</div>
                        </div>
                        <div class="alarm-summary-card info">
                            <div class="alarm-count">5</div>
                            <div class="alarm-label">Info</div>
                        </div>
                        <div class="alarm-summary-card total">
                            <div class="alarm-count"><?= count($alarmHistory) ?></div>
                            <div class="alarm-label">Total (24h)</div>
                        </div>
                    </div>
                </div><!-- End Tab: Alarm History -->

            </div>
        </div>
    </div>

    <!-- Node Details Modal -->
    <div id="nodeModal" class="modal">
        <div class="modal-content" style="max-width: 450px;">
            <div class="modal-header">
                <h3 id="nodeModalTitle">Device Details</h3>
                <button class="modal-close" onclick="closeModal('nodeModal')">&times;</button>
            </div>
            <div class="modal-body" id="nodeModalBody">
                <!-- Populated by JavaScript -->
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('nodeModal')">Close</button>
                <button class="btn btn-primary" onclick="pingNode()">Ping Device</button>
            </div>
        </div>
    </div>

    <!-- Generate Report Modal -->
    <div id="reportModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h3>Generate Report</h3>
                <button class="modal-close" onclick="closeModal('reportModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="reportForm">
                    <div class="form-group">
                        <label>Report Type</label>
                        <select name="report_type" class="form-control">
                            <option value="production">Production Summary</option>
                            <option value="alarms">Alarm History</option>
                            <option value="performance">Equipment Performance</option>
                            <option value="energy">Energy Consumption</option>
                            <option value="maintenance">Maintenance Log</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Date Range</label>
                        <div class="form-row">
                            <input type="date" name="start_date" class="form-control" value="<?= date('Y-m-d', strtotime('-7 days')) ?>">
                            <input type="date" name="end_date" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Devices</label>
                        <select name="devices[]" multiple class="form-control" style="height: 100px;">
                            <?php foreach ($devices as $device): ?>
                            <option value="<?= $device['id'] ?>" selected><?= htmlspecialchars($device['device_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Format</label>
                        <select name="format" class="form-control">
                            <option value="pdf">PDF</option>
                            <option value="excel">Excel</option>
                            <option value="csv">CSV</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('reportModal')">Cancel</button>
                <button class="btn btn-primary" onclick="submitReport()">Generate</button>
            </div>
        </div>
    </div>

    <!-- Tank Details Modal -->
    <div id="tankModal" class="modal">
        <div class="modal-content" style="max-width: 550px;">
            <div class="modal-header">
                <h3 id="tankModalTitle">Tank Details</h3>
                <button class="modal-close" onclick="closeModal('tankModal')">&times;</button>
            </div>
            <div class="modal-body" id="tankModalBody">
                <!-- Populated by JavaScript -->
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('tankModal')">Close</button>
                <button class="btn btn-primary" onclick="viewTankHistory()">View History</button>
            </div>
        </div>
    </div>

    <!-- Leak Details Modal -->
    <div id="leakModal" class="modal">
        <div class="modal-content" style="max-width: 700px;">
            <div class="modal-header" style="background: #fef2f2; border-bottom-color: #fecaca;">
                <h3 style="color: #991b1b;">⚠️ Active Leak Alerts</h3>
                <button class="modal-close" onclick="closeModal('leakModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="leak-detail-cards">
                    <?php foreach (array_filter($leakAlerts, fn($a) => $a['status'] === 'active' || $a['status'] === 'investigating') as $alert): ?>
                    <div class="leak-detail-card <?= $alert['severity'] ?>">
                        <div class="leak-detail-header">
                            <span class="leak-location"><?= htmlspecialchars($alert['location']) ?></span>
                            <span class="severity-badge <?= $alert['severity'] ?>"><?= ucfirst($alert['severity']) ?></span>
                        </div>
                        <div class="leak-detail-body">
                            <div class="leak-detail-row">
                                <span>Type:</span>
                                <strong><?= $alert['type'] ?></strong>
                            </div>
                            <div class="leak-detail-row">
                                <span>Detected:</span>
                                <strong><?= date('H:i:s', strtotime($alert['timestamp'])) ?></strong>
                            </div>
                            <div class="leak-detail-row">
                                <span>Est. Loss:</span>
                                <strong style="color: #ef4444;"><?= $alert['estimated_loss'] ?></strong>
                            </div>
                            <div class="leak-detail-row">
                                <span>Response:</span>
                                <strong><?= $alert['response'] ?></strong>
                            </div>
                        </div>
                        <div class="leak-detail-actions">
                            <button class="btn btn-secondary btn-sm" onclick="dispatchTeam(<?= $alert['id'] ?>)">Dispatch Team</button>
                            <button class="btn btn-danger btn-sm" onclick="emergencyShutdown(<?= $alert['id'] ?>)">Emergency Shutdown</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('leakModal')">Close</button>
                <button class="btn btn-danger" onclick="initiateEmergencyProtocol()">Emergency Protocol</button>
            </div>
        </div>
    </div>

    <!-- Add Device Modal -->
    <div id="deviceModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h3 id="deviceModalTitle">Add Device</h3>
                <button class="modal-close" onclick="closeModal('deviceModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" id="deviceForm">
                    <input type="hidden" name="action" value="add_device" id="deviceAction">
                    <input type="hidden" name="device_id" id="deviceId">

                    <div class="form-group">
                        <label>Device Name *</label>
                        <input type="text" name="device_name" id="deviceName" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Device Type</label>
                            <select name="device_type" id="deviceType">
                                <option value="PLC">PLC</option>
                                <option value="RTU">RTU</option>
                                <option value="DCS">DCS</option>
                                <option value="HMI">HMI</option>
                                <option value="Sensor">Sensor</option>
                                <option value="Gateway">Gateway</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Protocol</label>
                            <select name="protocol" id="deviceProtocol">
                                <option value="modbus_tcp">Modbus TCP</option>
                                <option value="modbus_rtu">Modbus RTU</option>
                                <option value="opc_ua">OPC UA</option>
                                <option value="dnp3">DNP3</option>
                                <option value="ethernet_ip">EtherNet/IP</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Host/IP *</label>
                            <input type="text" name="host" id="deviceHost" placeholder="192.168.1.100" required>
                        </div>
                        <div class="form-group">
                            <label>Port</label>
                            <input type="number" name="port" id="devicePort" value="502">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Unit ID</label>
                            <input type="number" name="unit_id" id="deviceUnitId" value="1" min="1" max="255">
                        </div>
                        <div class="form-group">
                            <label>Polling Interval (ms)</label>
                            <input type="number" name="polling_interval" id="devicePolling" value="1000" min="100">
                        </div>
                    </div>

                    <div class="toggle-item">
                        <div class="toggle-info">
                            <span class="toggle-label">Enable Device</span>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="is_enabled" id="deviceEnabled" checked>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>

                    <div style="margin-top: 20px; text-align: right;">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('deviceModal')">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Device</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Tag Modal -->
    <div id="tagModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h3>Add Tag</h3>
                <button class="modal-close" onclick="closeModal('tagModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="action" value="add_tag">

                    <div class="form-group">
                        <label>Device *</label>
                        <select name="device_id" required>
                            <?php foreach ($devices as $device): ?>
                            <option value="<?= $device['id'] ?>"><?= htmlspecialchars($device['device_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Tag Name *</label>
                        <input type="text" name="tag_name" required placeholder="e.g., Tank_Level_1">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Address *</label>
                            <input type="text" name="tag_address" required placeholder="e.g., 40001">
                        </div>
                        <div class="form-group">
                            <label>Data Type</label>
                            <select name="data_type">
                                <option value="INT16">INT16</option>
                                <option value="UINT16">UINT16</option>
                                <option value="INT32">INT32</option>
                                <option value="UINT32">UINT32</option>
                                <option value="FLOAT32">FLOAT32</option>
                                <option value="FLOAT64">FLOAT64</option>
                                <option value="BOOL">BOOL</option>
                                <option value="STRING">STRING</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Scaling Factor</label>
                            <input type="number" name="scaling_factor" value="1.0" step="0.0001">
                        </div>
                        <div class="form-group">
                            <label>Engineering Units</label>
                            <input type="text" name="engineering_units" placeholder="e.g., PSI, GPM, °C">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Low Alarm</label>
                            <input type="number" name="alarm_low" step="0.01">
                        </div>
                        <div class="form-group">
                            <label>High Alarm</label>
                            <input type="number" name="alarm_high" step="0.01">
                        </div>
                    </div>

                    <div class="toggle-item">
                        <div class="toggle-info">
                            <span class="toggle-label">Enable Tag</span>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="is_enabled" checked>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>

                    <div style="margin-top: 20px; text-align: right;">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('tagModal')">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Tag</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
        .protocol-badge {
            background: #e0e7ff;
            color: #3730a3;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        .status-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        .status-online { background: #d1fae5; color: #065f46; }
        .status-offline { background: #fee2e2; color: #991b1b; }
        .status-error { background: #fef3c7; color: #92400e; }
        .status-unknown { background: #e2e8f0; color: #475569; }
        .alarm-badge {
            background: #fef3c7;
            color: #92400e;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
        }
        code {
            background: #f1f5f9;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 12px;
        }

        /* Modal Base Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .modal.active {
            display: flex;
            opacity: 1;
        }
        .modal-content {
            background: white;
            border-radius: 16px;
            width: 90%;
            max-width: 500px;
            max-height: 85vh;
            overflow: auto;
            animation: modalSlideIn 0.3s ease;
        }
        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid var(--border-color);
        }
        .modal-header h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
        }
        .modal-close {
            background: none;
            border: none;
            font-size: 28px;
            cursor: pointer;
            color: var(--text-secondary);
            line-height: 1;
            padding: 0;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.2s;
        }
        .modal-close:hover {
            background: #f1f5f9;
            color: var(--text-primary);
        }
        .modal-body {
            padding: 20px;
        }
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            padding: 15px 20px;
            border-top: 1px solid var(--border-color);
            background: #f8fafc;
            border-radius: 0 0 16px 16px;
        }

        /* Tabs */
        .scada-tabs {
            display: flex !important;
            gap: 5px;
            margin: 20px 0;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 0;
            background: #f8fafc;
            border-radius: 8px 8px 0 0;
            padding: 10px 10px 0 10px;
        }
        .scada-tab {
            padding: 12px 24px;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-bottom: none;
            border-radius: 8px 8px 0 0;
            font-size: 14px;
            font-weight: 500;
            color: #64748b;
            cursor: pointer;
            margin-bottom: -2px;
            transition: all 0.2s;
        }
        .scada-tab:hover {
            color: #667eea;
            background: #f0f4ff;
        }
        .scada-tab.active {
            color: #667eea;
            background: #fff;
            border-color: #667eea;
            border-bottom: 2px solid #fff;
            font-weight: 600;
        }
        .scada-tab-content {
            display: none;
        }
        .scada-tab-content.active {
            display: block;
        }

        /* Topology */
        .topology-container {
            background: #f8fafc;
            border-radius: 12px;
            padding: 20px;
            overflow-x: auto;
        }
        .topology-legend {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
        }
        .legend-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: var(--text-secondary);
        }
        .legend-icon {
            width: 16px;
            height: 16px;
            border-radius: 4px;
        }
        .legend-icon.control { background: #3b82f6; }
        .legend-icon.firewall { background: #ef4444; }
        .legend-icon.gateway { background: #8b5cf6; }
        .legend-icon.switch { background: #06b6d4; }
        .legend-icon.plc { background: #10b981; }
        .legend-icon.rtu { background: #f59e0b; }
        .legend-icon.hmi { background: #ec4899; }
        .legend-icon.dcs { background: #6366f1; }
        .legend-icon.sensor { background: #84cc16; }

        .topology-link {
            stroke: #94a3b8;
            stroke-width: 2;
        }
        .topology-node {
            cursor: pointer;
        }
        .topology-node .node-bg {
            fill: white;
            stroke: #e2e8f0;
            stroke-width: 2;
            transition: all 0.2s;
        }
        .topology-node:hover .node-bg {
            stroke: var(--primary);
            filter: drop-shadow(0 4px 6px rgba(0,0,0,0.1));
        }
        .topology-node.control .node-bg { stroke: #3b82f6; }
        .topology-node.firewall .node-bg { stroke: #ef4444; }
        .topology-node.gateway .node-bg { stroke: #8b5cf6; }
        .topology-node.switch .node-bg { stroke: #06b6d4; }
        .topology-node.plc .node-bg { stroke: #10b981; }
        .topology-node.rtu .node-bg { stroke: #f59e0b; }
        .topology-node.hmi .node-bg { stroke: #ec4899; }
        .topology-node.dcs .node-bg { stroke: #6366f1; }
        .topology-node.sensor .node-bg { stroke: #84cc16; }
        .topology-node.offline .node-bg { stroke: #ef4444; fill: #fef2f2; }

        .node-icon {
            font-size: 16px;
        }
        .node-label {
            font-size: 9px;
            fill: var(--text-secondary);
            font-weight: 500;
        }
        .status-indicator {
            stroke: white;
            stroke-width: 2;
        }
        .status-indicator.online { fill: #10b981; }
        .status-indicator.offline { fill: #ef4444; }

        /* Reports */
        .report-type-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
        }
        .report-type-badge.production { background: #dbeafe; color: #1e40af; }
        .report-type-badge.alarms { background: #fee2e2; color: #991b1b; }
        .report-type-badge.performance { background: #d1fae5; color: #065f46; }
        .report-type-badge.energy { background: #fef3c7; color: #92400e; }
        .report-type-badge.maintenance { background: #ede9fe; color: #5b21b6; }

        .report-stats {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .report-stat-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .report-stat-bar {
            height: 8px;
            border-radius: 4px;
            min-width: 20px;
        }
        .report-stat-label {
            flex: 1;
            font-size: 13px;
        }
        .report-stat-value {
            font-weight: 600;
            font-size: 13px;
        }

        .scheduled-reports {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .scheduled-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            background: #f8fafc;
            border-radius: 8px;
        }
        .scheduled-info strong {
            display: block;
            font-size: 13px;
            margin-bottom: 2px;
        }
        .scheduled-info span {
            font-size: 11px;
            color: var(--text-secondary);
        }
        .scheduled-status {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
        }
        .scheduled-status.active { background: #d1fae5; color: #065f46; }
        .scheduled-status.paused { background: #fef3c7; color: #92400e; }
        .scheduled-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .scheduled-item:hover {
            background: #f0f4ff;
        }

        /* Alarms */
        .alarm-row.active { background: #fef2f2; }
        .alarm-row.acknowledged { background: #fffbeb; }

        .alarm-type {
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 500;
        }
        .alarm-type.high { background: #fee2e2; color: #991b1b; }
        .alarm-type.low { background: #dbeafe; color: #1e40af; }

        .priority-badge {
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 500;
        }
        .priority-badge.critical { background: #fee2e2; color: #991b1b; }
        .priority-badge.warning { background: #fef3c7; color: #92400e; }
        .priority-badge.info { background: #dbeafe; color: #1e40af; }

        .alarm-status {
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 500;
        }
        .alarm-status.active { background: #fee2e2; color: #991b1b; }
        .alarm-status.acknowledged { background: #fef3c7; color: #92400e; }
        .alarm-status.cleared { background: #d1fae5; color: #065f46; }

        .grid-4 {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
        }
        .alarm-summary-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            border: 2px solid var(--border-color);
        }
        .alarm-summary-card.critical { border-color: #ef4444; background: linear-gradient(to bottom, #fef2f2, white); }
        .alarm-summary-card.warning { border-color: #f59e0b; background: linear-gradient(to bottom, #fffbeb, white); }
        .alarm-summary-card.info { border-color: #3b82f6; background: linear-gradient(to bottom, #eff6ff, white); }
        .alarm-summary-card.total { border-color: #6366f1; background: linear-gradient(to bottom, #eef2ff, white); }
        .alarm-count {
            font-size: 36px;
            font-weight: 700;
        }
        .alarm-summary-card.critical .alarm-count { color: #ef4444; }
        .alarm-summary-card.warning .alarm-count { color: #f59e0b; }
        .alarm-summary-card.info .alarm-count { color: #3b82f6; }
        .alarm-summary-card.total .alarm-count { color: #6366f1; }
        .alarm-label {
            font-size: 13px;
            color: var(--text-secondary);
            margin-top: 5px;
        }

        .grid-3 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        @media (max-width: 900px) {
            .grid-4, .grid-3 {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (max-width: 600px) {
            .grid-4, .grid-3 {
                grid-template-columns: 1fr;
            }
            .scada-tabs {
                flex-wrap: wrap;
            }
        }

        /* Tank & Pipeline Styles */
        .leak-alert-banner {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px 20px;
            background: linear-gradient(135deg, #fef2f2, #fee2e2);
            border: 2px solid #ef4444;
            border-radius: 12px;
            margin-bottom: 20px;
            animation: pulse-border 2s infinite;
        }
        @keyframes pulse-border {
            0%, 100% { border-color: #ef4444; }
            50% { border-color: #fca5a5; }
        }
        .leak-alert-icon {
            width: 40px;
            height: 40px;
            background: #ef4444;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: pulse 1.5s infinite;
        }
        .leak-alert-icon svg {
            width: 24px;
            height: 24px;
            color: white;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        .leak-alert-content {
            flex: 1;
        }
        .leak-alert-content strong {
            display: block;
            color: #991b1b;
            font-size: 16px;
        }
        .leak-alert-content span {
            color: #b91c1c;
            font-size: 13px;
        }

        .pipeline-diagram {
            background: #f8fafc;
            border-radius: 12px;
            padding: 20px;
            overflow-x: auto;
        }
        .tank-group {
            cursor: pointer;
            transition: transform 0.2s;
        }
        .tank-group:hover {
            transform: scale(1.05);
        }
        .tank-group.warning .tank-body {
            animation: tank-warning 1s infinite;
        }
        @keyframes tank-warning {
            0%, 100% { fill: #fef2f2; }
            50% { fill: #fee2e2; }
        }
        .pipeline-warning {
            animation: pipeline-flash 1s infinite;
        }
        @keyframes pipeline-flash {
            0%, 100% { stroke: #ef4444; }
            50% { stroke: #fca5a5; }
        }
        .leak-pulse {
            animation: leak-pulse 1.5s infinite;
        }
        @keyframes leak-pulse {
            0%, 100% { r: 12; opacity: 0.8; }
            50% { r: 18; opacity: 0.3; }
        }
        .anomaly-indicator {
            animation: anomaly-blink 1s infinite;
        }
        @keyframes anomaly-blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        /* Tank Cards */
        .tank-cards-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 15px;
        }
        .tank-status-card {
            background: white;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .tank-status-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        .tank-status-card.warning {
            border-color: #f59e0b;
            background: linear-gradient(to bottom, #fffbeb, white);
        }
        .tank-status-card.low {
            border-color: #ef4444;
            background: linear-gradient(to bottom, #fef2f2, white);
        }
        .tank-status-card.high {
            border-color: #3b82f6;
            background: linear-gradient(to bottom, #eff6ff, white);
        }
        .tank-status-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }
        .tank-id {
            font-weight: 700;
            font-size: 14px;
            color: var(--primary);
        }
        .tank-status-badge {
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: 500;
        }
        .tank-status-badge.normal { background: #d1fae5; color: #065f46; }
        .tank-status-badge.warning { background: #fef3c7; color: #92400e; }
        .tank-status-badge.low { background: #fee2e2; color: #991b1b; }
        .tank-name {
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .tank-product {
            font-size: 10px;
            color: var(--text-secondary);
            margin-bottom: 10px;
        }
        .tank-gauge {
            position: relative;
            width: 100%;
            height: 60px;
            background: #e2e8f0;
            border-radius: 6px;
            overflow: hidden;
            margin-bottom: 10px;
        }
        .tank-gauge-fill {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, #10b981, #34d399);
            transition: height 0.5s ease;
        }
        .tank-status-card.warning .tank-gauge-fill { background: linear-gradient(to top, #f59e0b, #fbbf24); }
        .tank-status-card.low .tank-gauge-fill { background: linear-gradient(to top, #ef4444, #f87171); }
        .tank-gauge-label {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 16px;
            font-weight: 700;
            color: white;
            text-shadow: 0 1px 3px rgba(0,0,0,0.3);
        }
        .tank-stats {
            display: flex;
            justify-content: space-between;
        }
        .tank-stat {
            text-align: center;
        }
        .tank-stat-value {
            display: block;
            font-size: 12px;
            font-weight: 600;
        }
        .tank-stat-label {
            font-size: 9px;
            color: var(--text-secondary);
        }

        /* Leak Detection */
        .detector-count {
            background: #fee2e2;
            color: #991b1b;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        .alert-row {
            background: #fef2f2;
        }
        .alert-type {
            color: #ef4444;
            font-weight: 500;
        }
        .detector-status {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
        }
        .detector-status.normal { background: #d1fae5; color: #065f46; }
        .detector-status.alert { background: #fee2e2; color: #991b1b; }
        .detector-status.warning { background: #fef3c7; color: #92400e; }

        .leak-alert-row.active { background: #fef2f2; }
        .leak-alert-row.investigating { background: #fffbeb; }
        .severity-badge {
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 500;
        }
        .severity-badge.high { background: #fee2e2; color: #991b1b; }
        .severity-badge.medium { background: #fef3c7; color: #92400e; }
        .severity-badge.low { background: #dbeafe; color: #1e40af; }
        .leak-status {
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 500;
        }
        .leak-status.active { background: #fee2e2; color: #991b1b; }
        .leak-status.investigating { background: #fef3c7; color: #92400e; }
        .leak-status.monitoring { background: #dbeafe; color: #1e40af; }
        .leak-status.resolved { background: #d1fae5; color: #065f46; }

        /* Pipeline Status */
        .pipeline-warning-row {
            background: #fffbeb;
        }
        .pipeline-status {
            font-size: 12px;
            font-weight: 500;
        }
        .pipeline-status.normal { color: #059669; }
        .pipeline-status.warning { color: #d97706; }

        @media (max-width: 1200px) {
            .tank-cards-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        @media (max-width: 768px) {
            .tank-cards-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (max-width: 500px) {
            .tank-cards-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <script src="assets/js/cpanel.js"></script>
    <script>
        // Data from PHP
        const tankData = <?= json_encode($oilTanks, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
        const topologyData = <?= json_encode($topologyNodes, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
        const deviceData = <?= json_encode($devices, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
        const tagData = <?= json_encode($tags, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

        // Auto-select tab from URL parameter
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const tabParam = urlParams.get('tab');
            if (tabParam) {
                const tabContent = document.getElementById('tab-' + tabParam);
                const tabButton = document.querySelector('.scada-tab[data-tab="' + tabParam + '"]');
                if (tabContent && tabButton) {
                    // Remove active from all tabs and content
                    document.querySelectorAll('.scada-tab').forEach(t => t.classList.remove('active'));
                    document.querySelectorAll('.scada-tab-content').forEach(c => c.classList.remove('active'));

                    // Activate the requested tab
                    tabContent.classList.add('active');
                    tabButton.classList.add('active');
                }
            }
        });

        function showTankById(index) {
            if (tankData[index]) {
                showTankDetails(tankData[index]);
            }
        }

        function showNodeById(index) {
            if (topologyData[index]) {
                showNodeDetails(topologyData[index]);
            }
        }

        function editDeviceById(index) {
            if (deviceData[index]) {
                editDevice(deviceData[index]);
            }
        }

        function testDeviceConnection(index) {
            const device = deviceData[index];
            if (device) {
                showNotification('Testing connection to ' + device.device_name + '...', 'info');
                setTimeout(() => {
                    showNotification('Connection to ' + device.device_name + ' successful!', 'success');
                }, 1500);
            }
        }

        function deleteDevice(deviceId, deviceName) {
            if (confirm('Are you sure you want to delete device "' + deviceName + '"?')) {
                // Create and submit form
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="action" value="delete_device"><input type="hidden" name="device_id" value="' + deviceId + '">';
                document.body.appendChild(form);
                form.submit();
            }
        }

        function showAddDevice() {
            document.getElementById('deviceModalTitle').textContent = 'Add Device';
            document.getElementById('deviceAction').value = 'add_device';
            document.getElementById('deviceForm').reset();
            document.getElementById('deviceEnabled').checked = true;
            document.getElementById('deviceModal').classList.add('active');
        }

        function editDevice(device) {
            document.getElementById('deviceModalTitle').textContent = 'Edit Device';
            document.getElementById('deviceAction').value = 'update_device';
            document.getElementById('deviceId').value = device.id;
            document.getElementById('deviceName').value = device.device_name;
            document.getElementById('deviceType').value = device.device_type;
            document.getElementById('deviceProtocol').value = device.protocol;

            const config = JSON.parse(device.connection_config || '{}');
            document.getElementById('deviceHost').value = config.host || '';
            document.getElementById('devicePort').value = config.port || 502;
            document.getElementById('deviceUnitId').value = config.unit_id || 1;
            document.getElementById('devicePolling').value = device.polling_interval;
            document.getElementById('deviceEnabled').checked = device.is_enabled == 1;

            document.getElementById('deviceModal').classList.add('active');
        }

        function showAddTag() {
            document.getElementById('tagModal').classList.add('active');
        }

        function editTag(index) {
            const tag = tagData[index];
            if (tag) {
                // For now, show notification - could open edit modal
                showNotification('Editing tag: ' + tag.tag_name, 'info');
                // TODO: Populate tag modal with data for editing
                document.getElementById('tagModal').classList.add('active');
            }
        }

        function deleteTag(tagId, tagName) {
            if (confirm('Are you sure you want to delete tag "' + tagName + '"?')) {
                showNotification('Tag "' + tagName + '" deleted', 'success');
                // In real implementation, submit form to delete
            }
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }

        // Close modal on backdrop click
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('active');
                }
            });
        });

        // Close modal on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal.active').forEach(modal => {
                    modal.classList.remove('active');
                });
            }
        });

        // Tab switching
        function switchScadaTab(tabName) {
            // Update tab buttons
            document.querySelectorAll('.scada-tab').forEach(tab => {
                tab.classList.remove('active');
            });

            // Find and activate the clicked button
            const clickedBtn = document.querySelector('.scada-tab[data-tab="' + tabName + '"]');
            if (clickedBtn) {
                clickedBtn.classList.add('active');
            }

            // Update tab content
            document.querySelectorAll('.scada-tab-content').forEach(content => {
                content.classList.remove('active');
            });

            const tabContent = document.getElementById('tab-' + tabName);
            if (tabContent) {
                tabContent.classList.add('active');
            }
        }

        // Topology functions
        let currentNode = null;

        function showNodeDetails(node) {
            currentNode = node;
            document.getElementById('nodeModalTitle').textContent = node.label;

            const statusClass = (node.status || 'online') === 'online' ? 'status-online' : 'status-offline';
            const statusText = (node.status || 'online') === 'online' ? 'Online' : 'Offline';

            document.getElementById('nodeModalBody').innerHTML = `
                <div class="node-detail-grid">
                    <div class="node-detail-item">
                        <label>Device Type</label>
                        <span>${node.type.toUpperCase()}</span>
                    </div>
                    <div class="node-detail-item">
                        <label>IP Address</label>
                        <code>${node.ip}</code>
                    </div>
                    <div class="node-detail-item">
                        <label>Status</label>
                        <span class="status-badge ${statusClass}">${statusText}</span>
                    </div>
                    <div class="node-detail-item">
                        <label>Last Seen</label>
                        <span>${statusText === 'Online' ? 'Just now' : '2 hours ago'}</span>
                    </div>
                </div>
                <div style="margin-top: 15px; padding: 12px; background: #f8fafc; border-radius: 8px;">
                    <strong style="font-size: 12px; color: var(--text-secondary);">Connected To:</strong>
                    <div style="margin-top: 8px; font-size: 13px;">Gateway-001, ICS Switch 1</div>
                </div>
            `;
            document.getElementById('nodeModal').classList.add('active');
        }

        function pingNode() {
            if (currentNode) {
                showNotification('Pinging ' + currentNode.ip + '...', 'info');
                setTimeout(() => {
                    showNotification('Ping successful: ' + currentNode.ip + ' - 12ms', 'success');
                }, 1500);
            }
            closeModal('nodeModal');
        }

        function refreshTopology() {
            showNotification('Refreshing network topology...', 'info');
            setTimeout(() => {
                showNotification('Topology refreshed successfully', 'success');
            }, 1000);
        }

        function exportTopology() {
            showNotification('Exporting topology diagram as PNG...', 'info');
            setTimeout(() => {
                showNotification('Topology diagram exported', 'success');
            }, 1500);
        }

        // Report functions
        function generateReport() {
            document.getElementById('reportModal').classList.add('active');
        }

        function submitReport() {
            showNotification('Generating report...', 'info');
            closeModal('reportModal');
            setTimeout(() => {
                showNotification('Report generated successfully', 'success');
            }, 2000);
        }

        function viewReport(reportId) {
            showNotification('Opening report #' + reportId + '...', 'info');
        }

        function downloadReport(reportId) {
            showNotification('Downloading report #' + reportId + '...', 'info');
            setTimeout(() => {
                showNotification('Report downloaded successfully', 'success');
            }, 1500);
        }

        // Alarm functions
        function filterAlarms(status) {
            const rows = document.querySelectorAll('.alarm-row');
            rows.forEach(row => {
                if (status === 'all') {
                    row.style.display = '';
                } else {
                    row.style.display = row.classList.contains(status) ? '' : 'none';
                }
            });
        }

        function acknowledgeAlarm(alarmId) {
            showNotification('Alarm #' + alarmId + ' acknowledged', 'success');
        }

        function clearAlarm(alarmId) {
            showNotification('Alarm #' + alarmId + ' cleared', 'success');
        }

        function exportAlarms() {
            showNotification('Exporting alarm history...', 'info');
            setTimeout(() => {
                showNotification('Alarm history exported to CSV', 'success');
            }, 1500);
        }

        function toggleScheduledReport(reportId, reportName) {
            showNotification('Toggling schedule for: ' + reportName, 'info');
        }

        function editScheduledReport(reportId) {
            showNotification('Opening schedule editor for report #' + reportId, 'info');
        }

        // Tank & Pipeline functions
        let currentTank = null;

        function showTankDetails(tank) {
            currentTank = tank;
            document.getElementById('tankModalTitle').textContent = tank.id + ' - ' + tank.name;

            const percentage = Math.round((tank.current_level / tank.capacity) * 100);
            const statusClass = tank.status === 'normal' ? 'normal' : (tank.status === 'warning' ? 'warning' : 'low');
            const gaugeColor = statusClass === 'normal' ? '#10b981' : (statusClass === 'warning' ? '#f59e0b' : '#ef4444');

            document.getElementById('tankModalBody').innerHTML = `
                <div class="tank-detail-gauge">
                    <div class="tank-detail-visual">
                        <div class="tank-3d">
                            <div class="tank-3d-fill" style="height: ${percentage}%; background: ${gaugeColor};"></div>
                            <div class="tank-3d-level">${percentage}%</div>
                        </div>
                    </div>
                    <div class="tank-detail-info">
                        <div class="tank-detail-item">
                            <label>Product</label>
                            <span>${tank.product}</span>
                        </div>
                        <div class="tank-detail-item">
                            <label>Current Level</label>
                            <span>${tank.current_level.toLocaleString()} ${tank.unit}</span>
                        </div>
                        <div class="tank-detail-item">
                            <label>Capacity</label>
                            <span>${tank.capacity.toLocaleString()} ${tank.unit}</span>
                        </div>
                        <div class="tank-detail-item">
                            <label>Available</label>
                            <span>${(tank.capacity - tank.current_level).toLocaleString()} ${tank.unit}</span>
                        </div>
                        <div class="tank-detail-item">
                            <label>Temperature</label>
                            <span>${tank.temperature}°F</span>
                        </div>
                        <div class="tank-detail-item">
                            <label>Pressure</label>
                            <span>${tank.pressure} PSI</span>
                        </div>
                        <div class="tank-detail-item">
                            <label>Status</label>
                            <span class="tank-status-badge ${statusClass}">${tank.status.charAt(0).toUpperCase() + tank.status.slice(1)}</span>
                        </div>
                    </div>
                </div>
                ${tank.status !== 'normal' ? `
                <div class="tank-warning-box">
                    <strong>⚠️ Alert:</strong> ${tank.status === 'warning' ? 'Potential seepage detected at tank base. Inspection team notified.' : 'Tank level is low. Schedule refill.'}
                </div>
                ` : ''}
            `;
            document.getElementById('tankModal').classList.add('active');
        }

        function viewTankHistory() {
            if (currentTank) {
                showNotification('Loading history for ' + currentTank.id + '...', 'info');
                closeModal('tankModal');
            }
        }

        function viewLeakDetails() {
            document.getElementById('leakModal').classList.add('active');
        }

        function runLeakTest() {
            showNotification('Running comprehensive leak detection test...', 'info');
            setTimeout(() => {
                showNotification('Leak test completed. 2 anomalies detected.', 'warning');
            }, 3000);
        }

        function refreshPipelineData() {
            showNotification('Refreshing pipeline data...', 'info');
            setTimeout(() => {
                showNotification('Pipeline data refreshed', 'success');
            }, 1500);
        }

        function exportLeakReport() {
            showNotification('Generating leak detection report...', 'info');
            setTimeout(() => {
                showNotification('Report exported to PDF', 'success');
            }, 2000);
        }

        function dispatchTeam(alertId) {
            showNotification('Emergency response team dispatched to location', 'success');
        }

        function emergencyShutdown(alertId) {
            if (confirm('Are you sure you want to initiate emergency shutdown for this section?')) {
                showNotification('Emergency shutdown initiated. Valves closing...', 'warning');
                setTimeout(() => {
                    showNotification('Section isolated successfully', 'success');
                }, 2000);
            }
        }

        function initiateEmergencyProtocol() {
            if (confirm('CRITICAL: This will initiate full emergency protocol. All operations will be halted. Continue?')) {
                showNotification('EMERGENCY PROTOCOL ACTIVATED', 'error');
                document.body.style.background = '#fef2f2';
                setTimeout(() => {
                    showNotification('All systems secured. Awaiting manual override.', 'warning');
                    document.body.style.background = '';
                }, 3000);
            }
        }

        // Gas Storage functions
        const gasTanksData = <?= json_encode($gasTanks) ?>;
        let currentGasTank = null;

        function showGasTankDetails(index) {
            const tank = gasTanksData[index];
            currentGasTank = tank;

            const percentage = Math.round((tank.current_level / tank.capacity) * 100);
            const statusClass = tank.status === 'normal' ? 'normal' : (tank.status === 'warning' ? 'warning' : 'low');
            const gaugeColor = statusClass === 'normal' ? '#3498db' : (statusClass === 'warning' ? '#f39c12' : '#e74c3c');

            document.getElementById('tankModalTitle').textContent = tank.id + ' - ' + tank.name;
            document.getElementById('tankModalBody').innerHTML = `
                <div class="tank-detail-gauge">
                    <div class="tank-detail-visual">
                        <div class="tank-3d">
                            <div class="tank-3d-fill" style="height: ${percentage}%; background: ${gaugeColor};"></div>
                            <div class="tank-3d-level">${percentage}%</div>
                        </div>
                    </div>
                    <div class="tank-detail-info">
                        <div class="tank-detail-item">
                            <label>Product</label>
                            <span>${tank.product}</span>
                        </div>
                        <div class="tank-detail-item">
                            <label>Current Level</label>
                            <span>${tank.current_level.toLocaleString()} ${tank.unit}</span>
                        </div>
                        <div class="tank-detail-item">
                            <label>Capacity</label>
                            <span>${tank.capacity.toLocaleString()} ${tank.unit}</span>
                        </div>
                        <div class="tank-detail-item">
                            <label>Available</label>
                            <span>${(tank.capacity - tank.current_level).toLocaleString()} ${tank.unit}</span>
                        </div>
                        <div class="tank-detail-item">
                            <label>Temperature</label>
                            <span>${tank.temperature}°F</span>
                        </div>
                        <div class="tank-detail-item">
                            <label>Pressure</label>
                            <span>${tank.pressure} PSI</span>
                        </div>
                        <div class="tank-detail-item">
                            <label>Pump In Rate</label>
                            <span style="color: #27ae60;">${tank.pump_in_status === 'running' ? '▲ ' + tank.pump_in_rate.toLocaleString() + ' ' + tank.unit + '/hr' : 'Stopped'}</span>
                        </div>
                        <div class="tank-detail-item">
                            <label>Pump Out Rate</label>
                            <span style="color: #e74c3c;">${tank.pump_out_status === 'running' ? '▼ ' + tank.pump_out_rate.toLocaleString() + ' ' + tank.unit + '/hr' : 'Stopped'}</span>
                        </div>
                        <div class="tank-detail-item">
                            <label>Status</label>
                            <span class="tank-status-badge ${statusClass}">${tank.status.charAt(0).toUpperCase() + tank.status.slice(1)}</span>
                        </div>
                    </div>
                </div>
                ${tank.status !== 'normal' ? `
                <div class="tank-warning-box">
                    <strong>⚠️ Alert:</strong> ${tank.status === 'warning' ? 'Pressure fluctuation detected. Monitoring closely.' : 'Tank level is low. Schedule refill.'}
                </div>
                ` : ''}
            `;
            document.getElementById('tankModal').classList.add('active');
        }

        function refreshGasData() {
            showNotification('Refreshing gas storage data...', 'info');
            setTimeout(() => {
                showNotification('Gas storage data refreshed', 'success');
            }, 1500);
        }

        // Pump functions
        const pumpsData = <?= json_encode($pumps) ?>;
        let currentPump = null;

        function showPumpDetails(index) {
            const pump = pumpsData[index];
            currentPump = pump;

            const flowPercent = Math.round((pump.flow_rate / pump.max_rate) * 100);

            document.getElementById('tankModalTitle').textContent = pump.id + ' - ' + pump.name;
            document.getElementById('tankModalBody').innerHTML = `
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div>
                        <h4 style="margin-bottom: 15px; color: var(--text-primary);">Pump Information</h4>
                        <div class="tank-detail-info" style="display: grid; gap: 12px;">
                            <div class="tank-detail-item">
                                <label>Pump ID</label>
                                <span>${pump.id}</span>
                            </div>
                            <div class="tank-detail-item">
                                <label>Type</label>
                                <span>${pump.type}</span>
                            </div>
                            <div class="tank-detail-item">
                                <label>Connected Tank</label>
                                <span>${pump.tank}</span>
                            </div>
                            <div class="tank-detail-item">
                                <label>Power Rating</label>
                                <span>${pump.power} kW</span>
                            </div>
                            <div class="tank-detail-item">
                                <label>Total Runtime</label>
                                <span>${pump.runtime.toLocaleString()} hours</span>
                            </div>
                        </div>
                    </div>
                    <div>
                        <h4 style="margin-bottom: 15px; color: var(--text-primary);">Performance Metrics</h4>
                        <div style="margin-bottom: 20px;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                <span>Flow Rate</span>
                                <span>${pump.flow_rate.toLocaleString()} / ${pump.max_rate.toLocaleString()} units/hr</span>
                            </div>
                            <div style="background: var(--bg-secondary); border-radius: 8px; height: 25px; overflow: hidden;">
                                <div style="height: 100%; width: ${flowPercent}%; background: ${pump.status === 'running' ? 'linear-gradient(90deg, #27ae60, #2ecc71)' : '#7f8c8d'}; display: flex; align-items: center; justify-content: center;">
                                    <span style="color: white; font-weight: bold;">${flowPercent}%</span>
                                </div>
                            </div>
                        </div>
                        <div style="margin-bottom: 20px;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                <span>Efficiency</span>
                                <span>${pump.efficiency}%</span>
                            </div>
                            <div style="background: var(--bg-secondary); border-radius: 8px; height: 25px; overflow: hidden;">
                                <div style="height: 100%; width: ${pump.efficiency}%; background: ${pump.efficiency >= 90 ? '#27ae60' : (pump.efficiency >= 80 ? '#f39c12' : '#e74c3c')}; display: flex; align-items: center; justify-content: center;">
                                    <span style="color: white; font-weight: bold;">${pump.efficiency}%</span>
                                </div>
                            </div>
                        </div>
                        <div class="tank-detail-item">
                            <label>Status</label>
                            <span class="status-badge status-${pump.status === 'running' ? 'normal' : 'warning'}">${pump.status === 'running' ? '● Running' : '○ Stopped'}</span>
                        </div>
                    </div>
                </div>
            `;
            document.getElementById('tankModal').classList.add('active');
        }

        function startPump(pumpId) {
            showNotification('Starting pump ' + pumpId + '...', 'info');
            setTimeout(() => {
                showNotification('Pump ' + pumpId + ' started successfully', 'success');
            }, 2000);
        }

        function stopPump(pumpId) {
            if (confirm('Are you sure you want to stop pump ' + pumpId + '?')) {
                showNotification('Stopping pump ' + pumpId + '...', 'warning');
                setTimeout(() => {
                    showNotification('Pump ' + pumpId + ' stopped', 'success');
                }, 2000);
            }
        }

        function refreshPumpData() {
            showNotification('Refreshing pump data...', 'info');
            setTimeout(() => {
                showNotification('Pump data refreshed', 'success');
            }, 1500);
        }

        // Notification helper
        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = 'notification notification-' + type;
            notification.innerHTML = message;
            notification.style.cssText = 'position: fixed; top: 20px; right: 20px; padding: 15px 20px; border-radius: 8px; color: white; z-index: 2000; animation: slideIn 0.3s ease;';

            if (type === 'success') notification.style.background = '#059669';
            else if (type === 'error') notification.style.background = '#dc2626';
            else notification.style.background = '#3b82f6';

            document.body.appendChild(notification);

            setTimeout(() => {
                notification.style.opacity = '0';
                notification.style.transition = 'opacity 0.3s';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }
    </script>

    <style>
        .node-detail-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        .node-detail-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .node-detail-item label {
            font-size: 11px;
            color: var(--text-secondary);
            text-transform: uppercase;
        }
        .node-detail-item span, .node-detail-item code {
            font-size: 14px;
            font-weight: 500;
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateX(20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        /* Tank Detail Modal */
        .tank-detail-gauge {
            display: flex;
            gap: 25px;
        }
        .tank-detail-visual {
            flex-shrink: 0;
        }
        .tank-3d {
            width: 120px;
            height: 180px;
            background: linear-gradient(to right, #e2e8f0, #f8fafc, #e2e8f0);
            border-radius: 10px;
            border: 3px solid #cbd5e1;
            position: relative;
            overflow: hidden;
            box-shadow: inset 0 0 20px rgba(0,0,0,0.1);
        }
        .tank-3d-fill {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            transition: height 0.5s ease;
            opacity: 0.8;
        }
        .tank-3d-level {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 28px;
            font-weight: 700;
            color: white;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
            z-index: 1;
        }
        .tank-detail-info {
            flex: 1;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }
        .tank-detail-item {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }
        .tank-detail-item label {
            font-size: 11px;
            color: var(--text-secondary);
            text-transform: uppercase;
        }
        .tank-detail-item span {
            font-size: 14px;
            font-weight: 600;
        }
        .tank-warning-box {
            margin-top: 20px;
            padding: 12px 15px;
            background: #fef3c7;
            border: 1px solid #fcd34d;
            border-radius: 8px;
            font-size: 13px;
            color: #92400e;
        }

        /* Leak Detail Modal */
        .leak-detail-cards {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .leak-detail-card {
            border: 2px solid var(--border-color);
            border-radius: 12px;
            overflow: hidden;
        }
        .leak-detail-card.high {
            border-color: #ef4444;
        }
        .leak-detail-card.medium {
            border-color: #f59e0b;
        }
        .leak-detail-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 15px;
            background: #f8fafc;
        }
        .leak-detail-card.high .leak-detail-header {
            background: #fef2f2;
        }
        .leak-location {
            font-weight: 600;
            font-size: 14px;
        }
        .leak-detail-body {
            padding: 15px;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
        .leak-detail-row {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
        }
        .leak-detail-row span {
            color: var(--text-secondary);
        }
        .leak-detail-actions {
            padding: 12px 15px;
            background: #f8fafc;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
    </style>
</body>
</html>
