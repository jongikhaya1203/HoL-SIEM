<?php
/**
 * Test agent check-in flow
 * Simulates an agent checking in with the server
 */
require_once __DIR__ . '/classes/Database.php';

echo "=== Testing Agent Check-in Flow ===\n\n";

// Get API key
$db = Database::getInstance();
$apiKey = $db->fetchOne("SELECT api_key FROM agent_api_keys WHERE status = 'active' LIMIT 1", []);

if (!$apiKey) {
    die("ERROR: No active API key found. Run create_agent_tables.php first.\n");
}

$apiKeyValue = $apiKey['api_key'];
echo "Using API key: " . substr($apiKeyValue, 0, 16) . "...\n\n";

// Simulate agent data
$agentData = [
    'agent_version' => '1.0.0',
    'system' => [
        'agent_id' => hash('sha256', 'test_agent_' . gethostname()),
        'hostname' => gethostname(),
        'os_family' => PHP_OS_FAMILY,
        'os' => php_uname('s'),
        'os_version' => php_uname('r'),
        'os_release' => php_uname('v'),
        'architecture' => php_uname('m'),
        'ip_addresses' => ['127.0.0.1', '10.0.0.100'],
        'timestamp' => time(),
        'collected_at' => date('Y-m-d H:i:s')
    ],
    'network' => [
        'interfaces' => [],
        'open_ports' => [80, 443, 3306, 3000],
        'listening_services' => [
            ['port' => 80, 'service' => 'Apache'],
            ['port' => 3306, 'service' => 'MySQL']
        ],
        'dns_servers' => ['8.8.8.8', '8.8.4.4']
    ],
    'processes' => [
        ['name' => 'apache2', 'pid' => '1234'],
        ['name' => 'mysqld', 'pid' => '5678'],
        ['name' => 'php', 'pid' => '9012']
    ],
    'security' => [
        'firewall_status' => 'enabled',
        'antivirus_status' => 'Windows Defender',
        'windows_defender' => 'enabled',
        'last_update' => 'unknown',
        'user_accounts' => ['Administrator', 'IUSR', 'Guest']
    ]
];

echo "Agent ID: " . $agentData['system']['agent_id'] . "\n";
echo "Hostname: " . $agentData['system']['hostname'] . "\n";
echo "OS: " . $agentData['system']['os_family'] . "\n\n";

// Prepare request
$payload = json_encode([
    'api_key' => $apiKeyValue,
    'action' => 'checkin',
    'data' => $agentData
]);

// Send check-in request
echo "Sending check-in request to agent_api.php...\n";

$url = 'http://localhost/networkscan/agent_api.php';
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'User-Agent: NetworkSecurityAgent/1.0'
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    die("ERROR: " . $error . "\n");
}

echo "HTTP Status: $httpCode\n";
echo "Response:\n";
echo $response . "\n\n";

$result = json_decode($response, true);

if ($result && isset($result['success']) && $result['success']) {
    echo "✓ Check-in successful!\n";
    echo "  Message: " . ($result['message'] ?? 'OK') . "\n";

    if (isset($result['data'])) {
        echo "  Agent ID: " . ($result['data']['agent_id'] ?? 'N/A') . "\n";
        echo "  Next check-in: " . ($result['data']['next_checkin'] ?? 'N/A') . " seconds\n";
    }

    echo "\n=== Verifying Database ===\n";

    // Check agent registered
    $agent = $db->fetchOne(
        "SELECT * FROM agents WHERE agent_id = ?",
        [$agentData['system']['agent_id']]
    );

    if ($agent) {
        echo "✓ Agent registered in database\n";
        echo "  Hostname: " . $agent['hostname'] . "\n";
        echo "  Status: " . $agent['status'] . "\n";
        echo "  Last check-in: " . $agent['last_checkin'] . "\n";
    } else {
        echo "✗ Agent NOT found in database\n";
    }

    // Check IP addresses
    $ips = $db->fetchAll(
        "SELECT * FROM agent_ips WHERE agent_id = ?",
        [$agentData['system']['agent_id']]
    );
    echo "✓ IP addresses registered: " . count($ips) . "\n";

    // Check network data
    $network = $db->fetchOne(
        "SELECT * FROM agent_network WHERE agent_id = ? ORDER BY collected_at DESC LIMIT 1",
        [$agentData['system']['agent_id']]
    );
    if ($network) {
        echo "✓ Network data collected\n";
    }

    // Check security data
    $security = $db->fetchOne(
        "SELECT * FROM agent_security WHERE agent_id = ? ORDER BY collected_at DESC LIMIT 1",
        [$agentData['system']['agent_id']]
    );
    if ($security) {
        echo "✓ Security data collected\n";
    }

    // Check check-in log
    $checkins = $db->fetchAll(
        "SELECT * FROM agent_checkins WHERE agent_id = ?",
        [$agentData['system']['agent_id']]
    );
    echo "✓ Check-ins logged: " . count($checkins) . "\n";

    echo "\n=== Test Complete ===\n";
    echo "View the agent at: http://localhost/networkscan/agents.php\n";

} else {
    echo "✗ Check-in failed!\n";
    echo "  Error: " . ($result['message'] ?? 'Unknown error') . "\n";
}
