<?php
/**
 * Agent API Endpoint
 * Receives check-ins from deployed agents
 */
header('Content-Type: application/json');
require_once __DIR__ . '/classes/Database.php';

// Initialize response
$response = [
    'success' => false,
    'message' => '',
    'data' => []
];

// Get request body
$input = file_get_contents('php://input');
$request = json_decode($input, true);

// Log incoming request for debugging
$logFile = __DIR__ . '/agent_api.log';
file_put_contents($logFile, date('Y-m-d H:i:s') . " - Request received\n", FILE_APPEND);

try {
    // Validate request format
    if (!$request) {
        throw new Exception('Invalid JSON in request body');
    }

    // Check required fields
    if (!isset($request['api_key'])) {
        throw new Exception('API key required');
    }

    if (!isset($request['action'])) {
        throw new Exception('Action required');
    }

    $db = Database::getInstance();

    // Validate API key and get tenant_id
    $apiKey = $request['api_key'];
    $keyCheck = $db->fetchOne(
        "SELECT id, tenant_id FROM agent_api_keys WHERE api_key = ? AND status = 'active'",
        [$apiKey]
    );

    if (!$keyCheck) {
        throw new Exception('Invalid or inactive API key');
    }

    $tenantId = $keyCheck['tenant_id'];

    // Update last_used timestamp for API key
    $db->query(
        "UPDATE agent_api_keys SET last_used = NOW() WHERE api_key = ?",
        [$apiKey]
    );

    // Process action
    $action = $request['action'];

    switch ($action) {
        case 'checkin':
            handleCheckin($db, $request, $response, $tenantId);
            break;

        case 'get_config':
            handleGetConfig($db, $request, $response, $tenantId);
            break;

        case 'report_error':
            handleReportError($db, $request, $response, $tenantId);
            break;

        default:
            throw new Exception('Unknown action: ' . $action);
    }

} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
}

echo json_encode($response);

/**
 * Handle agent check-in
 */
function handleCheckin($db, $request, &$response, $tenantId) {
    global $logFile;

    if (!isset($request['data'])) {
        throw new Exception('Check-in data required');
    }

    $data = $request['data'];

    // Extract system info
    $system = $data['system'] ?? [];
    $agentId = $system['agent_id'] ?? null;
    $hostname = $system['hostname'] ?? 'unknown';
    $osFamily = $system['os_family'] ?? null;
    $os = $system['os'] ?? null;
    $architecture = $system['architecture'] ?? null;
    $ipAddresses = $system['ip_addresses'] ?? [];
    $agentVersion = $data['agent_version'] ?? 'unknown';

    if (!$agentId) {
        throw new Exception('Agent ID required in system data');
    }

    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Check-in from agent: $agentId ($hostname) [Tenant: $tenantId]\n", FILE_APPEND);

    // Register or update agent
    $existingAgent = $db->fetchOne(
        "SELECT id, tenant_id FROM agents WHERE agent_id = ?",
        [$agentId]
    );

    if ($existingAgent) {
        // Verify tenant ownership
        if ($existingAgent['tenant_id'] != $tenantId) {
            throw new Exception('Agent belongs to different tenant');
        }

        // Update existing agent
        $db->query(
            "UPDATE agents SET
                hostname = ?,
                last_checkin = NOW(),
                status = 'active',
                os_family = ?,
                os = ?,
                architecture = ?,
                agent_version = ?
             WHERE agent_id = ? AND tenant_id = ?",
            [$hostname, $osFamily, $os, $architecture, $agentVersion, $agentId, $tenantId]
        );
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - Updated existing agent\n", FILE_APPEND);
    } else {
        // Register new agent with tenant_id
        $db->query(
            "INSERT INTO agents (agent_id, tenant_id, hostname, first_seen, last_checkin, status, os_family, os, architecture, agent_version)
             VALUES (?, ?, ?, NOW(), NOW(), 'active', ?, ?, ?, ?)",
            [$agentId, $tenantId, $hostname, $osFamily, $os, $architecture, $agentVersion]
        );
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - Registered new agent for tenant $tenantId\n", FILE_APPEND);
    }

    // Update IP addresses
    if (!empty($ipAddresses)) {
        foreach ($ipAddresses as $ip) {
            $existingIp = $db->fetchOne(
                "SELECT id FROM agent_ips WHERE agent_id = ? AND ip_address = ?",
                [$agentId, $ip]
            );

            if ($existingIp) {
                $db->query(
                    "UPDATE agent_ips SET last_seen = NOW() WHERE agent_id = ? AND ip_address = ?",
                    [$agentId, $ip]
                );
            } else {
                $db->query(
                    "INSERT INTO agent_ips (agent_id, ip_address, discovered_at, last_seen)
                     VALUES (?, ?, NOW(), NOW())",
                    [$agentId, $ip]
                );
            }
        }
    }

    // Store network information
    $network = $data['network'] ?? [];
    if (!empty($network)) {
        $db->query(
            "INSERT INTO agent_network (agent_id, collected_at, open_ports, listening_services, dns_servers, routing_table)
             VALUES (?, NOW(), ?, ?, ?, ?)",
            [
                $agentId,
                json_encode($network['open_ports'] ?? []),
                json_encode($network['listening_services'] ?? []),
                json_encode($network['dns_servers'] ?? []),
                $network['routing_table'] ?? ''
            ]
        );
    }

    // Store process information
    $processes = $data['processes'] ?? [];
    if (!empty($processes)) {
        $db->query(
            "INSERT INTO agent_processes (agent_id, collected_at, processes)
             VALUES (?, NOW(), ?)",
            [$agentId, json_encode($processes)]
        );
    }

    // Store security information
    $security = $data['security'] ?? [];
    if (!empty($security)) {
        $db->query(
            "INSERT INTO agent_security (agent_id, collected_at, firewall_status, antivirus_status, windows_defender, last_update, user_accounts)
             VALUES (?, NOW(), ?, ?, ?, ?, ?)",
            [
                $agentId,
                $security['firewall_status'] ?? null,
                $security['antivirus_status'] ?? null,
                $security['windows_defender'] ?? null,
                $security['last_update'] ?? null,
                json_encode($security['user_accounts'] ?? [])
            ]
        );
    }

    // Log check-in
    $db->query(
        "INSERT INTO agent_checkins (agent_id, checkin_time, success)
         VALUES (?, NOW(), TRUE)",
        [$agentId]
    );

    // Clean up old data (keep last 30 days)
    $db->query(
        "DELETE FROM agent_network WHERE collected_at < DATE_SUB(NOW(), INTERVAL 30 DAY)",
        []
    );
    $db->query(
        "DELETE FROM agent_processes WHERE collected_at < DATE_SUB(NOW(), INTERVAL 30 DAY)",
        []
    );
    $db->query(
        "DELETE FROM agent_security WHERE collected_at < DATE_SUB(NOW(), INTERVAL 30 DAY)",
        []
    );
    $db->query(
        "DELETE FROM agent_checkins WHERE checkin_time < DATE_SUB(NOW(), INTERVAL 30 DAY)",
        []
    );

    $response['success'] = true;
    $response['message'] = 'Check-in successful';
    $response['data'] = [
        'agent_id' => $agentId,
        'hostname' => $hostname,
        'next_checkin' => 3600, // Suggest next check-in in 1 hour
        'config' => [
            'scan_enabled' => true,
            'report_interval' => 3600
        ]
    ];
}

/**
 * Handle get configuration request
 */
function handleGetConfig($db, $request, &$response, $tenantId) {
    // Return configuration for agent
    // TODO: Add tenant-specific configuration in future
    $response['success'] = true;
    $response['message'] = 'Configuration retrieved';
    $response['data'] = [
        'report_interval' => 3600,
        'collect_processes' => true,
        'collect_network' => true,
        'collect_security' => true,
        'max_processes' => 50,
        'tenant_id' => $tenantId
    ];
}

/**
 * Handle error report from agent
 */
function handleReportError($db, $request, &$response, $tenantId) {
    global $logFile;

    $agentId = $request['agent_id'] ?? 'unknown';
    $errorMessage = $request['error'] ?? 'No error message provided';

    // Verify agent belongs to tenant
    if ($agentId !== 'unknown') {
        $agent = $db->fetchOne(
            "SELECT tenant_id FROM agents WHERE agent_id = ?",
            [$agentId]
        );

        if ($agent && $agent['tenant_id'] != $tenantId) {
            throw new Exception('Agent belongs to different tenant');
        }
    }

    file_put_contents($logFile, date('Y-m-d H:i:s') . " - ERROR from agent $agentId [Tenant: $tenantId]: $errorMessage\n", FILE_APPEND);

    // Log error in database
    $db->query(
        "INSERT INTO agent_checkins (agent_id, checkin_time, success, error_message)
         VALUES (?, NOW(), FALSE, ?)",
        [$agentId, $errorMessage]
    );

    // Update agent status with tenant verification
    $db->query(
        "UPDATE agents SET status = 'error', last_checkin = NOW() WHERE agent_id = ? AND tenant_id = ?",
        [$agentId, $tenantId]
    );

    $response['success'] = true;
    $response['message'] = 'Error report received';
}
