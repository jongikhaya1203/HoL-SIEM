<?php
/**
 * Network Security Scanner - Agent
 *
 * Deploy this agent on hosts to automatically collect and report:
 * - System information
 * - Open ports and services
 * - Running processes
 * - Network configuration
 * - Security posture
 *
 * Usage: php agent.php --server=http://scanner-server/networkscan --key=YOUR_API_KEY
 */

class SecurityAgent {
    private $serverUrl;
    private $apiKey;
    private $hostname;
    private $agentId;
    private $configFile;

    public function __construct($serverUrl = null, $apiKey = null) {
        $this->configFile = __DIR__ . '/agent.config.json';
        $this->loadConfig();

        if ($serverUrl) $this->serverUrl = rtrim($serverUrl, '/');
        if ($apiKey) $this->apiKey = $apiKey;

        $this->hostname = gethostname();
        $this->agentId = $this->generateAgentId();

        $this->log("Agent initialized: {$this->hostname} ({$this->agentId})");
    }

    private function loadConfig() {
        if (file_exists($this->configFile)) {
            $config = json_decode(file_get_contents($this->configFile), true);
            $this->serverUrl = $config['server_url'] ?? null;
            $this->apiKey = $config['api_key'] ?? null;
        }
    }

    private function saveConfig() {
        $config = [
            'server_url' => $this->serverUrl,
            'api_key' => $this->apiKey,
            'agent_id' => $this->agentId,
            'hostname' => $this->hostname,
            'last_checkin' => date('Y-m-d H:i:s')
        ];
        file_put_contents($this->configFile, json_encode($config, JSON_PRETTY_PRINT));
    }

    private function generateAgentId() {
        // Generate unique agent ID based on hostname and MAC address
        $identifier = $this->hostname;

        // Try to get MAC address
        if (PHP_OS_FAMILY === 'Windows') {
            $mac = shell_exec('getmac');
            if ($mac) {
                preg_match('/([0-9A-F]{2}[:-]){5}[0-9A-F]{2}/i', $mac, $matches);
                if (!empty($matches[0])) {
                    $identifier .= '_' . str_replace([':', '-'], '', $matches[0]);
                }
            }
        } else {
            $mac = shell_exec('cat /sys/class/net/*/address 2>/dev/null | head -1');
            if ($mac) {
                $identifier .= '_' . str_replace([':', '-', "\n"], '', trim($mac));
            }
        }

        return hash('sha256', $identifier);
    }

    public function collectSystemInfo() {
        $this->log("Collecting system information...");

        $info = [
            'agent_id' => $this->agentId,
            'hostname' => $this->hostname,
            'os_family' => PHP_OS_FAMILY,
            'os' => php_uname('s'),
            'os_version' => php_uname('r'),
            'os_release' => php_uname('v'),
            'architecture' => php_uname('m'),
            'ip_addresses' => $this->getIPAddresses(),
            'timestamp' => time(),
            'collected_at' => date('Y-m-d H:i:s')
        ];

        return $info;
    }

    public function collectNetworkInfo() {
        $this->log("Collecting network information...");

        $network = [
            'interfaces' => $this->getNetworkInterfaces(),
            'open_ports' => $this->getOpenPorts(),
            'listening_services' => $this->getListeningServices(),
            'routing_table' => $this->getRoutingTable(),
            'dns_servers' => $this->getDNSServers()
        ];

        return $network;
    }

    public function collectProcessInfo() {
        $this->log("Collecting process information...");

        $processes = [];

        if (PHP_OS_FAMILY === 'Windows') {
            $output = shell_exec('tasklist /FO CSV /NH');
            if ($output) {
                $lines = explode("\n", trim($output));
                foreach ($lines as $line) {
                    $parts = str_getcsv($line);
                    if (count($parts) >= 2) {
                        $processes[] = [
                            'name' => $parts[0],
                            'pid' => $parts[1]
                        ];
                    }
                }
            }
        } else {
            $output = shell_exec('ps aux');
            if ($output) {
                $lines = explode("\n", trim($output));
                array_shift($lines); // Remove header
                foreach ($lines as $line) {
                    if (preg_match('/^(\S+)\s+(\d+).*?\s+(.+)$/', $line, $matches)) {
                        $processes[] = [
                            'user' => $matches[1],
                            'pid' => $matches[2],
                            'command' => $matches[3]
                        ];
                    }
                }
            }
        }

        return array_slice($processes, 0, 50); // Limit to top 50 processes
    }

    public function collectSecurityInfo() {
        $this->log("Collecting security information...");

        $security = [
            'firewall_status' => $this->getFirewallStatus(),
            'antivirus_status' => $this->getAntivirusStatus(),
            'windows_defender' => $this->getWindowsDefenderStatus(),
            'last_update' => $this->getLastUpdateTime(),
            'user_accounts' => $this->getUserAccounts()
        ];

        return $security;
    }

    private function getIPAddresses() {
        $ips = [];

        if (PHP_OS_FAMILY === 'Windows') {
            $output = shell_exec('ipconfig');
            if ($output && preg_match_all('/IPv4 Address[.\s]+:\s+(\d+\.\d+\.\d+\.\d+)/', $output, $matches)) {
                $ips = $matches[1];
            }
        } else {
            $output = shell_exec('hostname -I 2>/dev/null');
            if ($output) {
                $ips = explode(' ', trim($output));
            }
        }

        return array_filter($ips);
    }

    private function getNetworkInterfaces() {
        $interfaces = [];

        if (PHP_OS_FAMILY === 'Windows') {
            $output = shell_exec('ipconfig /all');
            // Parse Windows network interfaces
        } else {
            $output = shell_exec('ip addr show 2>/dev/null || ifconfig');
            // Parse Linux network interfaces
        }

        return $interfaces;
    }

    private function getOpenPorts() {
        $ports = [];

        if (PHP_OS_FAMILY === 'Windows') {
            $output = shell_exec('netstat -ano | findstr LISTENING');
            if ($output) {
                $lines = explode("\n", trim($output));
                foreach ($lines as $line) {
                    if (preg_match('/TCP\s+[\d.]+:(\d+)/', $line, $matches)) {
                        $ports[] = (int)$matches[1];
                    }
                }
            }
        } else {
            $output = shell_exec('ss -tuln 2>/dev/null || netstat -tuln');
            if ($output) {
                $lines = explode("\n", trim($output));
                foreach ($lines as $line) {
                    if (preg_match('/:(\d+)\s/', $line, $matches)) {
                        $ports[] = (int)$matches[1];
                    }
                }
            }
        }

        return array_unique($ports);
    }

    private function getListeningServices() {
        $services = [];

        if (PHP_OS_FAMILY === 'Windows') {
            $output = shell_exec('netstat -anob 2>nul');
            // Parse Windows services
        } else {
            $output = shell_exec('ss -tulnp 2>/dev/null');
            // Parse Linux services
        }

        return $services;
    }

    private function getRoutingTable() {
        if (PHP_OS_FAMILY === 'Windows') {
            return shell_exec('route print');
        } else {
            return shell_exec('ip route 2>/dev/null || route -n');
        }
    }

    private function getDNSServers() {
        $dns = [];

        if (PHP_OS_FAMILY === 'Windows') {
            $output = shell_exec('ipconfig /all');
            if ($output && preg_match_all('/DNS Servers[.\s]+:\s+(\d+\.\d+\.\d+\.\d+)/', $output, $matches)) {
                $dns = $matches[1];
            }
        } else {
            $output = shell_exec('cat /etc/resolv.conf 2>/dev/null');
            if ($output && preg_match_all('/nameserver\s+(\d+\.\d+\.\d+\.\d+)/', $output, $matches)) {
                $dns = $matches[1];
            }
        }

        return $dns;
    }

    private function getFirewallStatus() {
        if (PHP_OS_FAMILY === 'Windows') {
            $output = shell_exec('netsh advfirewall show allprofiles state 2>nul');
            return strpos($output, 'ON') !== false ? 'enabled' : 'disabled';
        } else {
            $ufw = shell_exec('ufw status 2>/dev/null');
            $iptables = shell_exec('iptables -L 2>/dev/null | wc -l');

            if (strpos($ufw, 'active') !== false) return 'ufw_enabled';
            if ($iptables && (int)$iptables > 10) return 'iptables_configured';

            return 'unknown';
        }
    }

    private function getAntivirusStatus() {
        if (PHP_OS_FAMILY === 'Windows') {
            $output = shell_exec('wmic /namespace:\\\\root\\SecurityCenter2 path AntiVirusProduct get displayName 2>nul');
            return $output ? trim(str_replace('displayName', '', $output)) : 'none';
        }

        return 'not_applicable';
    }

    private function getWindowsDefenderStatus() {
        if (PHP_OS_FAMILY === 'Windows') {
            $output = shell_exec('powershell -Command "Get-MpComputerStatus | Select-Object -Property AntivirusEnabled" 2>nul');
            return strpos($output, 'True') !== false ? 'enabled' : 'disabled';
        }

        return 'not_applicable';
    }

    private function getLastUpdateTime() {
        if (PHP_OS_FAMILY === 'Windows') {
            $output = shell_exec('wmic qfe list brief 2>nul | find /V "" | find /V "Caption" | find /V "Description"');
            // Parse last Windows Update date
        } else {
            $output = shell_exec('stat /var/log/apt/history.log 2>/dev/null | grep Modify');
            // Parse last apt update
        }

        return 'unknown';
    }

    private function getUserAccounts() {
        $users = [];

        if (PHP_OS_FAMILY === 'Windows') {
            $output = shell_exec('net user');
            // Parse Windows users
        } else {
            $output = shell_exec('cat /etc/passwd 2>/dev/null');
            if ($output) {
                $lines = explode("\n", trim($output));
                foreach ($lines as $line) {
                    $parts = explode(':', $line);
                    if (!empty($parts[0])) {
                        $users[] = $parts[0];
                    }
                }
            }
        }

        return array_slice($users, 0, 20); // Limit to 20 users
    }

    public function collectAll() {
        $this->log("Starting full collection...");

        $data = [
            'agent_version' => '1.0.0',
            'system' => $this->collectSystemInfo(),
            'network' => $this->collectNetworkInfo(),
            'processes' => $this->collectProcessInfo(),
            'security' => $this->collectSecurityInfo()
        ];

        $this->log("Collection complete");
        return $data;
    }

    public function sendToServer($data) {
        if (!$this->serverUrl || !$this->apiKey) {
            $this->log("ERROR: Server URL or API key not configured", 'error');
            return false;
        }

        $url = $this->serverUrl . '/agent_api.php';
        $this->log("Sending data to: $url");

        $payload = json_encode([
            'api_key' => $this->apiKey,
            'action' => 'checkin',
            'data' => $data
        ]);

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
            $this->log("ERROR: " . $error, 'error');
            return false;
        }

        if ($httpCode === 200) {
            $this->log("Data sent successfully (HTTP $httpCode)");
            $result = json_decode($response, true);

            if ($result && isset($result['success']) && $result['success']) {
                $this->log("Server acknowledged: " . ($result['message'] ?? 'OK'));
                $this->saveConfig();
                return true;
            } else {
                $this->log("Server error: " . ($result['error'] ?? 'Unknown'), 'error');
                return false;
            }
        } else {
            $this->log("ERROR: HTTP $httpCode - $response", 'error');
            return false;
        }
    }

    public function run() {
        $this->log("=== Agent Check-in Started ===");

        $data = $this->collectAll();
        $success = $this->sendToServer($data);

        $this->log("=== Agent Check-in " . ($success ? "Complete" : "Failed") . " ===");

        return $success;
    }

    private function log($message, $level = 'info') {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] [$level] $message\n";

        echo $logMessage;

        // Also log to file
        $logFile = __DIR__ . '/agent.log';
        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }
}

// CLI execution
if (php_sapi_name() === 'cli') {
    $options = getopt('', ['server:', 'key:', 'daemon', 'interval:']);

    $server = $options['server'] ?? null;
    $key = $options['key'] ?? null;
    $daemon = isset($options['daemon']);
    $interval = (int)($options['interval'] ?? 3600); // Default 1 hour

    $agent = new SecurityAgent($server, $key);

    if ($daemon) {
        echo "Running in daemon mode (interval: {$interval}s)\n";
        echo "Press Ctrl+C to stop\n\n";

        while (true) {
            $agent->run();
            sleep($interval);
        }
    } else {
        $agent->run();
    }
}
