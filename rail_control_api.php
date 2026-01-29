<?php
/**
 * Rail Control API
 * Handles rail system control actions
 */

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/classes/RailControlSystem.php';

$host = 'localhost';
$username = 'root';
$password = '';
$database = 'network_security';

try {
    $conn = new mysqli($host, $username, $password, $database);

    if ($conn->connect_error) {
        throw new Exception("Database connection failed");
    }

    $railControl = new RailControlSystem($conn);

    $action = $_POST['action'] ?? $_GET['action'] ?? '';

    switch ($action) {
        case 'get_status':
            $siteId = $_GET['site_id'] ?? 1;
            $status = $railControl->getSystemStatus($siteId);
            echo json_encode(['success' => true, 'data' => $status]);
            break;

        case 'change_signal':
            $signalId = $_POST['signal_id'] ?? 0;
            $aspect = $_POST['aspect'] ?? 'red';
            $result = $railControl->changeSignalAspect($signalId, $aspect, 'Operator');
            echo json_encode($result);
            break;

        case 'move_point':
            $pointId = $_POST['point_id'] ?? 0;
            $position = $_POST['position'] ?? 'normal';
            $result = $railControl->movePoint($pointId, $position, 'Operator');
            echo json_encode($result);
            break;

        case 'control_doors':
            $platformId = $_POST['platform_id'] ?? 0;
            $doorAction = $_POST['door_action'] ?? 'close';
            $result = $railControl->controlPlatformDoors($platformId, $doorAction, 'Operator');
            echo json_encode($result);
            break;

        case 'control_crossing':
            $crossingId = $_POST['crossing_id'] ?? 0;
            $crossingAction = $_POST['crossing_action'] ?? 'raise';
            $result = $railControl->controlLevelCrossing($crossingId, $crossingAction, 'Operator');
            echo json_encode($result);
            break;

        case 'emergency_stop':
            $systemId = $_POST['system_id'] ?? 0;
            $result = $railControl->activateEmergencyStop($systemId, 'Operator');
            echo json_encode($result);
            break;

        case 'reset_emergency':
            $systemId = $_POST['system_id'] ?? 0;
            $result = $railControl->resetEmergencyStop($systemId, 'Supervisor');
            echo json_encode($result);
            break;

        case 'get_events':
            $siteId = $_GET['site_id'] ?? 1;
            $events = $railControl->getRecentEvents($siteId, 20);
            echo json_encode(['success' => true, 'events' => $events]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Unknown action']);
    }

    $conn->close();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
