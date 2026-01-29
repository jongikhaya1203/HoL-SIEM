<?php
/**
 * Generate Agent API Key
 * Creates a new API key for SCADA agent deployment
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'localhost';
$username = 'root';
$password = '';
$database = 'network_security';

echo "<!DOCTYPE html><html><head><title>Generate Agent API Key</title>";
echo "<style>body{font-family:monospace;background:#1a1a2e;color:#0f0;padding:20px;}";
echo ".err{color:#f00;}.ok{color:#0f0;}.info{color:#0ff;}.warn{color:#fa0;}";
echo ".key-box{background:#2a2a3e;border:2px solid #667eea;padding:20px;margin:20px 0;border-radius:5px;}";
echo ".key-value{color:#f9f;font-size:18px;font-weight:bold;letter-spacing:2px;word-break:break-all;}";
echo "input,select{background:#2a2a3e;color:#0f0;border:1px solid #667eea;padding:10px;margin:5px 0;width:100%;max-width:400px;}";
echo "button{background:#667eea;color:white;border:none;padding:15px 30px;cursor:pointer;border-radius:5px;font-size:16px;margin:10px 5px;}";
echo "button:hover{background:#5568d3;}</style></head><body>";
echo "<h2>Generate SCADA Agent API Key</h2>";

try {
    $conn = new mysqli($host, $username, $password, $database);

    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        echo "<pre>";
        echo "<span class='info'>Generating new API key...</span>\n\n";

        $keyName = $_POST['key_name'] ?? 'Unnamed Agent';
        $siteId = !empty($_POST['site_id']) ? intval($_POST['site_id']) : null;
        $allowedIps = $_POST['allowed_ips'] ?? '';
        $expiryDays = !empty($_POST['expiry_days']) ? intval($_POST['expiry_days']) : null;
        $createdBy = $_POST['created_by'] ?? 'System Administrator';
        $notes = $_POST['notes'] ?? '';

        // Generate secure API key
        $apiKey = bin2hex(random_bytes(32)); // 64 character hex string

        // Default permissions
        $permissions = json_encode([
            'submit_tag_data' => true,
            'submit_alarms' => true,
            'submit_events' => true,
            'read_tags' => true,
            'read_plc_config' => true
        ]);

        // Calculate expiry date
        $expiresAt = null;
        if ($expiryDays !== null) {
            $expiresAt = date('Y-m-d H:i:s', strtotime("+{$expiryDays} days"));
        }

        // Insert API key
        $stmt = $conn->prepare("INSERT INTO agent_api_keys (api_key, key_name, site_id, allowed_ips, permissions, status, expires_at, created_by, notes) VALUES (?, ?, ?, ?, ?, 'active', ?, ?, ?)");
        $stmt->bind_param("ssisssss", $apiKey, $keyName, $siteId, $allowedIps, $permissions, $expiresAt, $createdBy, $notes);

        if ($stmt->execute()) {
            $keyId = $conn->insert_id;

            echo "<span class='ok'>✓ API Key generated successfully!</span>\n\n";

            echo "═══════════════════════════════════════════════════════════════\n";
            echo "<div class='key-box'>";
            echo "<strong>API KEY (SAVE THIS - IT WON'T BE SHOWN AGAIN):</strong><br>";
            echo "<span class='key-value'>{$apiKey}</span>";
            echo "</div>";
            echo "═══════════════════════════════════════════════════════════════\n\n";

            echo "Key Details:\n";
            echo "  • Key ID: {$keyId}\n";
            echo "  • Key Name: {$keyName}\n";
            echo "  • Site ID: " . ($siteId ?? 'All Sites') . "\n";
            echo "  • Allowed IPs: " . ($allowedIps ?: 'Any') . "\n";
            echo "  • Status: Active\n";
            echo "  • Created By: {$createdBy}\n";
            echo "  • Expires: " . ($expiresAt ?? 'Never') . "\n";
            if ($notes) {
                echo "  • Notes: {$notes}\n";
            }

            echo "\n<span class='info'>Agent Configuration:</span>\n";
            echo "Copy this configuration to your agent's config file:\n\n";
            echo "<div class='key-box'>";
            echo "[scada_agent]\n";
            echo "api_url = http://localhost/networkscanscada/agent_api.php\n";
            echo "api_key = {$apiKey}\n";
            echo "site_id = " . ($siteId ?? 'auto') . "\n";
            echo "</div>\n";

            echo "\n<span class='ok'>Next Steps:</span>\n";
            echo "1. Save the API key in a secure location\n";
            echo "2. Download the agent software\n";
            echo "3. Configure the agent with this API key\n";
            echo "4. Deploy to your PLC/RTU\n\n";

            echo "<a href='agent_api_keys_list.php' style='background:#667eea;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;display:inline-block;margin:10px 5px;'>View All API Keys</a>";
            echo "<a href='generate_agent_api_key.php' style='background:#28a745;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;display:inline-block;margin:10px 5px;'>Generate Another Key</a>";

        } else {
            throw new Exception("Failed to create API key: " . $conn->error);
        }

        echo "</pre>";

    } else {
        // Show form
        echo "<form method='POST'>";
        echo "<pre>";

        echo "<span class='info'>Fill in the details to generate a new API key:</span>\n\n";

        echo "Key Name: *\n";
        echo "<input type='text' name='key_name' placeholder='e.g., Houston Refinery PLC-01' required>\n\n";

        // Get sites for dropdown
        echo "Site: (optional - leave empty for all sites)\n";
        echo "<select name='site_id'>";
        echo "<option value=''>All Sites</option>";
        $sites = $conn->query("SELECT id, site_name, industry_type FROM scada_sites ORDER BY site_name");
        while ($site = $sites->fetch_assoc()) {
            echo "<option value='{$site['id']}'>{$site['site_name']} ({$site['industry_type']})</option>";
        }
        echo "</select>\n\n";

        echo "Allowed IP Addresses: (optional - comma separated, leave empty for any)\n";
        echo "<input type='text' name='allowed_ips' placeholder='e.g., 192.168.1.100, 10.0.0.50'>\n\n";

        echo "Expiry (days): (optional - leave empty for no expiry)\n";
        echo "<input type='number' name='expiry_days' placeholder='e.g., 365'>\n\n";

        echo "Created By:\n";
        echo "<input type='text' name='created_by' value='System Administrator'>\n\n";

        echo "Notes: (optional)\n";
        echo "<input type='text' name='notes' placeholder='e.g., Production PLC in Building A'>\n\n";

        echo "<button type='submit'>🔑 Generate API Key</button>";

        echo "</pre>";
        echo "</form>";

        // Show existing keys
        echo "<br><hr><br>";
        echo "<h3>Existing API Keys</h3><pre>";

        $keys = $conn->query("SELECT ak.*, s.site_name
                              FROM agent_api_keys ak
                              LEFT JOIN scada_sites s ON ak.site_id = s.id
                              ORDER BY ak.created_at DESC
                              LIMIT 10");

        if ($keys->num_rows > 0) {
            echo "<span class='ok'>Recent API Keys:</span>\n\n";
            echo str_pad("ID", 5) . str_pad("Key Name", 35) . str_pad("Site", 25) . str_pad("Status", 12) . "Created\n";
            echo str_repeat("─", 110) . "\n";

            while ($key = $keys->fetch_assoc()) {
                $statusColor = $key['status'] === 'active' ? 'ok' : 'warn';
                echo str_pad($key['id'], 5) .
                     str_pad(substr($key['key_name'], 0, 33), 35) .
                     str_pad(substr($key['site_name'] ?? 'All Sites', 0, 23), 25) .
                     "<span class='{$statusColor}'>" . str_pad($key['status'], 12) . "</span>" .
                     date('Y-m-d H:i', strtotime($key['created_at'])) . "\n";
            }

            echo "\n<a href='agent_api_keys_list.php' style='color:#0ff;'>View all API keys →</a>\n";
        } else {
            echo "<span class='info'>No API keys generated yet.</span>\n";
        }

        echo "</pre>";
    }

    $conn->close();

} catch (Exception $e) {
    echo "<pre><span class='err'>❌ ERROR: " . $e->getMessage() . "</span></pre>";
    exit(1);
}

echo "</body></html>";
?>
