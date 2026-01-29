<?php
/**
 * SCADA Agent API Endpoint
 * Handles data submissions from deployed SCADA agents
 *
 * Endpoints:
 * - POST /scada_agent_api.php?action=submit_data - Submit tag data
 * - POST /scada_agent_api.php?action=heartbeat - Agent heartbeat
 * - GET  /scada_agent_api.php?action=get_config - Get agent configuration
 * - GET  /scada_agent_api.php?action=get_tags - Get tags to monitor
 */

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't show errors in API responses

$host = 'localhost';
$username = 'root';
$password = '';
$database = 'network_security';

// Response helper function
function apiResponse($success, $message, $data = null, $code = 200) {
    http_response_code($code);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}

// Error handler
function apiError($message, $code = 400) {
    apiResponse(false, $message, null, $code);
}

try {
    $conn = new mysqli($host, $username, $password, $database);

    if ($conn->connect_error) {
        apiError("Database connection failed", 500);
    }

    // Get API key from header or query parameter
    $apiKey = null;
    if (isset($_SERVER['HTTP_X_API_KEY'])) {
        $apiKey = $_SERVER['HTTP_X_API_KEY'];
    } elseif (isset($_GET['api_key'])) {
        $apiKey = $_GET['api_key'];
    } elseif (isset($_POST['api_key'])) {
        $apiKey = $_POST['api_key'];
    }

    if (!$apiKey) {
        apiError("API key is required", 401);
    }

    // Validate API key
    $stmt = $conn->prepare("SELECT ak.*, s.site_name
                           FROM agent_api_keys ak
                           LEFT JOIN scada_sites s ON ak.site_id = s.id
                           WHERE ak.api_key = ? AND ak.status = 'active'");
    $stmt->bind_param("s", $apiKey);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        apiError("Invalid or inactive API key", 401);
    }

    $apiKeyData = $result->fetch_assoc();
    $apiKeyId = $apiKeyData['id'];
    $siteId = $apiKeyData['site_id'];

    // Check expiry
    if ($apiKeyData['expires_at'] && strtotime($apiKeyData['expires_at']) < time()) {
        apiError("API key has expired", 401);
    }

    // Check IP restriction
    if ($apiKeyData['allowed_ips']) {
        $allowedIps = array_map('trim', explode(',', $apiKeyData['allowed_ips']));
        $clientIp = $_SERVER['REMOTE_ADDR'];
        if (!in_array($clientIp, $allowedIps)) {
            apiError("IP address not authorized: {$clientIp}", 403);
        }
    }

    // Update last used timestamp
    $conn->query("UPDATE agent_api_keys SET last_used_at = NOW() WHERE id = {$apiKeyId}");

    // Get action
    $action = $_GET['action'] ?? $_POST['action'] ?? 'unknown';

    // Route to appropriate handler
    switch ($action) {

        // ============================================
        // SUBMIT TAG DATA
        // ============================================
        case 'submit_data':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                apiError("POST method required", 405);
            }

            $input = file_get_contents('php://input');
            $data = json_decode($input, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                apiError("Invalid JSON payload", 400);
            }

            if (!isset($data['readings']) || !is_array($data['readings'])) {
                apiError("Missing 'readings' array in payload", 400);
            }

            $plcId = $data['plc_id'] ?? null;
            $rtuId = $data['rtu_id'] ?? null;
            $submissionType = $data['type'] ?? 'tag_data';

            // Log the submission
            $stmt = $conn->prepare("INSERT INTO agent_data_submissions
                                   (api_key_id, submission_type, plc_id, rtu_id, data_payload, records_count, processing_status)
                                   VALUES (?, ?, ?, ?, ?, ?, 'pending')");
            $payload = json_encode($data);
            $recordsCount = count($data['readings']);
            $stmt->bind_param("isiisi", $apiKeyId, $submissionType, $plcId, $rtuId, $payload, $recordsCount);
            $stmt->execute();
            $submissionId = $conn->insert_id;

            // Process each reading
            $processedCount = 0;
            $errorCount = 0;
            $errors = [];

            foreach ($data['readings'] as $reading) {
                try {
                    // Validate required fields
                    if (!isset($reading['tag_name']) || !isset($reading['value'])) {
                        $errors[] = "Missing tag_name or value";
                        $errorCount++;
                        continue;
                    }

                    $tagName = $reading['tag_name'];
                    $value = $reading['value'];
                    $quality = $reading['quality'] ?? 'good';
                    $timestamp = $reading['timestamp'] ?? date('Y-m-d H:i:s');

                    // Find tag by name
                    $stmt = $conn->prepare("SELECT id, current_value, alarm_low, alarm_high
                                           FROM scada_tags
                                           WHERE tag_name = ? AND is_active = 1");
                    $stmt->bind_param("s", $tagName);
                    $stmt->execute();
                    $tagResult = $stmt->get_result();

                    if ($tagResult->num_rows === 0) {
                        $errors[] = "Tag not found: {$tagName}";
                        $errorCount++;
                        continue;
                    }

                    $tag = $tagResult->fetch_assoc();
                    $tagId = $tag['id'];

                    // Update current value in tags table
                    $stmt = $conn->prepare("UPDATE scada_tags SET current_value = ?, updated_at = ? WHERE id = ?");
                    $stmt->bind_param("dsi", $value, $timestamp, $tagId);
                    $stmt->execute();

                    // Insert into tag history
                    $stmt = $conn->prepare("INSERT INTO scada_tag_history (tag_id, timestamp, value, quality) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("isds", $tagId, $timestamp, $value, $quality);
                    $stmt->execute();

                    // Check for alarms
                    if ($quality === 'good') {
                        if ($tag['alarm_high'] !== null && $value > $tag['alarm_high']) {
                            // High alarm
                            $alarmMsg = "High alarm: {$tagName} = {$value}";
                            $stmt = $conn->prepare("INSERT INTO scada_alarm_history
                                                   (tag_id, alarm_type, alarm_message, value, setpoint, is_acknowledged, alarm_time)
                                                   VALUES (?, 'high', ?, ?, ?, 0, ?)");
                            $stmt->bind_param("isdds", $tagId, $alarmMsg, $value, $tag['alarm_high'], $timestamp);
                            $stmt->execute();
                        } elseif ($tag['alarm_low'] !== null && $value < $tag['alarm_low']) {
                            // Low alarm
                            $alarmMsg = "Low alarm: {$tagName} = {$value}";
                            $stmt = $conn->prepare("INSERT INTO scada_alarm_history
                                                   (tag_id, alarm_type, alarm_message, value, setpoint, is_acknowledged, alarm_time)
                                                   VALUES (?, 'low', ?, ?, ?, 0, ?)");
                            $stmt->bind_param("isdds", $tagId, $alarmMsg, $value, $tag['alarm_low'], $timestamp);
                            $stmt->execute();
                        }
                    }

                    $processedCount++;

                } catch (Exception $e) {
                    $errors[] = "Error processing tag {$reading['tag_name']}: " . $e->getMessage();
                    $errorCount++;
                }
            }

            // Update submission status
            $status = ($errorCount === 0) ? 'processed' : (($processedCount === 0) ? 'failed' : 'processed');
            $errorMessage = $errorCount > 0 ? implode('; ', $errors) : null;
            $stmt = $conn->prepare("UPDATE agent_data_submissions
                                   SET processing_status = ?, error_message = ?, processed_at = NOW()
                                   WHERE id = ?");
            $stmt->bind_param("ssi", $status, $errorMessage, $submissionId);
            $stmt->execute();

            apiResponse(true, "Data processed successfully", [
                'submission_id' => $submissionId,
                'total_readings' => $recordsCount,
                'processed' => $processedCount,
                'errors' => $errorCount,
                'error_details' => $errors
            ]);
            break;

        // ============================================
        // HEARTBEAT
        // ============================================
        case 'heartbeat':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                apiError("POST method required", 405);
            }

            $input = file_get_contents('php://input');
            $data = json_decode($input, true);

            $agentVersion = $data['agent_version'] ?? 'unknown';
            $hostname = $data['hostname'] ?? 'unknown';
            $ipAddress = $_SERVER['REMOTE_ADDR'];
            $cpuUsage = $data['cpu_usage'] ?? null;
            $memoryUsage = $data['memory_usage'] ?? null;
            $diskUsage = $data['disk_usage'] ?? null;
            $uptimeSeconds = $data['uptime_seconds'] ?? null;
            $systemInfo = json_encode($data['system_info'] ?? []);

            $stmt = $conn->prepare("INSERT INTO agent_heartbeats
                                   (api_key_id, agent_version, hostname, ip_address, system_info, cpu_usage, memory_usage, disk_usage, uptime_seconds)
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssddd", $apiKeyId, $agentVersion, $hostname, $ipAddress, $systemInfo, $cpuUsage, $memoryUsage, $diskUsage, $uptimeSeconds);
            $stmt->execute();

            apiResponse(true, "Heartbeat received", [
                'api_key_name' => $apiKeyData['key_name'],
                'site_name' => $apiKeyData['site_name'],
                'server_time' => date('Y-m-d H:i:s')
            ]);
            break;

        // ============================================
        // GET CONFIGURATION
        // ============================================
        case 'get_config':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                apiError("GET method required", 405);
            }

            $config = [
                'api_key_name' => $apiKeyData['key_name'],
                'site_id' => $siteId,
                'site_name' => $apiKeyData['site_name'],
                'permissions' => json_decode($apiKeyData['permissions'], true),
                'poll_interval_default' => 5000,
                'batch_size' => 100,
                'heartbeat_interval' => 60
            ];

            apiResponse(true, "Configuration retrieved", $config);
            break;

        // ============================================
        // GET TAGS TO MONITOR
        // ============================================
        case 'get_tags':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                apiError("GET method required", 405);
            }

            $plcId = $_GET['plc_id'] ?? null;
            $rtuId = $_GET['rtu_id'] ?? null;

            $query = "SELECT t.*, a.asset_tag, a.asset_type
                     FROM scada_tags t
                     LEFT JOIN scada_assets a ON t.asset_id = a.id
                     WHERE t.is_active = 1";

            if ($siteId) {
                $query .= " AND a.site_id = {$siteId}";
            }
            if ($plcId) {
                $query .= " AND t.plc_id = {$plcId}";
            }
            if ($rtuId) {
                $query .= " AND t.rtu_id = {$rtuId}";
            }

            $query .= " ORDER BY t.poll_interval_ms, t.tag_name";

            $result = $conn->query($query);
            $tags = [];

            while ($row = $result->fetch_assoc()) {
                $tags[] = [
                    'tag_id' => $row['id'],
                    'tag_name' => $row['tag_name'],
                    'tag_type' => $row['tag_type'],
                    'description' => $row['description'],
                    'asset_tag' => $row['asset_tag'],
                    'asset_type' => $row['asset_type'],
                    'modbus_address' => $row['modbus_address'],
                    'data_type' => $row['data_type'],
                    'engineering_unit' => $row['engineering_unit'],
                    'poll_interval_ms' => $row['poll_interval_ms'],
                    'min_value' => $row['min_value'],
                    'max_value' => $row['max_value']
                ];
            }

            apiResponse(true, "Tags retrieved", [
                'count' => count($tags),
                'tags' => $tags
            ]);
            break;

        // ============================================
        // UNKNOWN ACTION
        // ============================================
        default:
            apiError("Unknown action: {$action}", 400);
    }

    $conn->close();

} catch (Exception $e) {
    apiError("Server error: " . $e->getMessage(), 500);
}
?>
