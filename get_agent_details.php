<?php
/**
 * Get detailed information about a specific agent
 */
header('Content-Type: application/json');
require_once __DIR__ . '/classes/Database.php';

$response = [
    'success' => false,
    'agent' => null,
    'error' => null
];

try {
    if (!isset($_GET['agent_id'])) {
        throw new Exception('Agent ID required');
    }

    $agentId = $_GET['agent_id'];
    $db = Database::getInstance();

    // Get agent basic info
    $agent = $db->fetchOne(
        "SELECT * FROM agents WHERE agent_id = ?",
        [$agentId]
    );

    if (!$agent) {
        throw new Exception('Agent not found');
    }

    // Get IP addresses
    $ips = $db->fetchAll(
        "SELECT ip_address, discovered_at, last_seen FROM agent_ips WHERE agent_id = ? ORDER BY last_seen DESC",
        [$agentId]
    );

    // Get latest network info
    $network = $db->fetchOne(
        "SELECT * FROM agent_network WHERE agent_id = ? ORDER BY collected_at DESC LIMIT 1",
        [$agentId]
    );

    if ($network) {
        $network['open_ports'] = json_decode($network['open_ports'], true);
        $network['listening_services'] = json_decode($network['listening_services'], true);
        $network['dns_servers'] = json_decode($network['dns_servers'], true);
    }

    // Get latest security info
    $security = $db->fetchOne(
        "SELECT * FROM agent_security WHERE agent_id = ? ORDER BY collected_at DESC LIMIT 1",
        [$agentId]
    );

    if ($security) {
        $security['user_accounts'] = json_decode($security['user_accounts'], true);
    }

    // Get recent check-ins
    $checkins = $db->fetchAll(
        "SELECT checkin_time, success, error_message FROM agent_checkins
         WHERE agent_id = ?
         ORDER BY checkin_time DESC
         LIMIT 10",
        [$agentId]
    );

    // Build response
    $response['success'] = true;
    $response['agent'] = [
        'agent_id' => $agent['agent_id'],
        'hostname' => $agent['hostname'],
        'first_seen' => $agent['first_seen'],
        'last_checkin' => $agent['last_checkin'],
        'status' => $agent['status'],
        'system' => [
            'os_family' => $agent['os_family'],
            'os' => $agent['os'],
            'os_version' => $agent['os_version'] ?? 'N/A',
            'architecture' => $agent['architecture'],
            'agent_version' => $agent['agent_version']
        ],
        'ip_addresses' => $ips,
        'network' => $network,
        'security' => $security,
        'recent_checkins' => $checkins
    ];

} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response);
