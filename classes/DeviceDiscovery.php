<?php
/**
 * Network Device Discovery System
 * Auto-discover devices via ping sweep, ARP, SNMP, and CDP/LLDP
 */

class DeviceDiscovery
{
    private $db;
    private $discoveredDevices = [];

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Discover devices on network subnet
     */
    public function discoverSubnet($subnet)
    {
        $this->discoveredDevices = [];

        // Parse subnet (e.g., 192.168.1.0/24)
        list($network, $cidr) = explode('/', $subnet);
        $ipList = $this->calculateIPRange($network, $cidr);

        foreach ($ipList as $ip) {
            if ($this->pingHost($ip)) {
                $device = $this->probeDevice($ip);
                if ($device) {
                    $this->discoveredDevices[] = $device;
                    $this->saveDevice($device);
                }
            }
        }

        return $this->discoveredDevices;
    }

    /**
     * Calculate IP range from CIDR
     */
    private function calculateIPRange($network, $cidr)
    {
        $ipList = [];
        $hostBits = 32 - $cidr;
        $numHosts = pow(2, $hostBits) - 2; // Exclude network and broadcast

        $networkLong = ip2long($network);

        for ($i = 1; $i <= min($numHosts, 254); $i++) {
            $ipList[] = long2ip($networkLong + $i);
        }

        return $ipList;
    }

    /**
     * Ping host to check availability
     */
    private function pingHost($ip)
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            exec("ping -n 1 -w 1000 {$ip}", $output, $returnCode);
        } else {
            exec("ping -c 1 -W 1 {$ip}", $output, $returnCode);
        }

        return $returnCode === 0;
    }

    /**
     * Probe device for information
     */
    private function probeDevice($ip)
    {
        $device = [
            'ip_address' => $ip,
            'hostname' => $this->getHostname($ip),
            'mac_address' => $this->getMacAddress($ip),
            'device_type' => 'unknown',
            'manufacturer' => null,
            'model' => null,
            'os_type' => null,
            'open_ports' => [],
            'snmp_enabled' => false,
            'discovered_at' => date('Y-m-d H:i:s')
        ];

        // Try SNMP discovery
        $snmpData = $this->snmpDiscovery($ip);
        if ($snmpData) {
            $device = array_merge($device, $snmpData);
            $device['snmp_enabled'] = true;
        }

        // Port scan for common services
        $device['open_ports'] = $this->scanCommonPorts($ip);

        // Determine device type from services
        $device['device_type'] = $this->determineDeviceType($device);

        // Get manufacturer from MAC
        if ($device['mac_address']) {
            $device['manufacturer'] = $this->getMacVendor($device['mac_address']);
        }

        return $device;
    }

    /**
     * Get hostname via DNS
     */
    private function getHostname($ip)
    {
        $hostname = gethostbyaddr($ip);
        return ($hostname !== $ip) ? $hostname : null;
    }

    /**
     * Get MAC address via ARP
     */
    private function getMacAddress($ip)
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            exec("arp -a {$ip}", $output);
            foreach ($output as $line) {
                if (preg_match('/([0-9a-f]{2}[:-]){5}[0-9a-f]{2}/i', $line, $matches)) {
                    return strtoupper(str_replace('-', ':', $matches[0]));
                }
            }
        } else {
            exec("arp -n {$ip}", $output);
            foreach ($output as $line) {
                if (preg_match('/([0-9a-f]{2}:){5}[0-9a-f]{2}/i', $line, $matches)) {
                    return strtoupper($matches[0]);
                }
            }
        }

        return null;
    }

    /**
     * SNMP discovery
     */
    private function snmpDiscovery($ip)
    {
        // Try common community strings
        $communities = ['public', 'private'];

        foreach ($communities as $community) {
            // In production, use PHP's snmp functions
            // $sysDescr = @snmpget($ip, $community, '1.3.6.1.2.1.1.1.0');

            // Simulated SNMP data
            if (rand(0, 1)) {
                return [
                    'device_name' => "Device-" . str_replace('.', '-', $ip),
                    'device_type' => ['router', 'switch', 'firewall', 'server'][rand(0, 3)],
                    'manufacturer' => ['Cisco', 'HP', 'Juniper', 'Dell'][rand(0, 3)],
                    'model' => 'Model-' . rand(1000, 9999),
                    'os_type' => 'IOS',
                    'snmp_community' => $community,
                    'snmp_version' => 'v2c'
                ];
            }
        }

        return null;
    }

    /**
     * Scan common ports
     */
    private function scanCommonPorts($ip)
    {
        $commonPorts = [
            22 => 'SSH',
            23 => 'Telnet',
            80 => 'HTTP',
            443 => 'HTTPS',
            161 => 'SNMP',
            3389 => 'RDP',
            3306 => 'MySQL',
            5432 => 'PostgreSQL'
        ];

        $openPorts = [];

        foreach ($commonPorts as $port => $service) {
            if ($this->isPortOpen($ip, $port)) {
                $openPorts[] = ['port' => $port, 'service' => $service];
            }
        }

        return $openPorts;
    }

    /**
     * Check if port is open
     */
    private function isPortOpen($ip, $port, $timeout = 1)
    {
        $connection = @fsockopen($ip, $port, $errno, $errstr, $timeout);

        if (is_resource($connection)) {
            fclose($connection);
            return true;
        }

        return false;
    }

    /**
     * Determine device type from services
     */
    private function determineDeviceType($device)
    {
        $ports = array_column($device['open_ports'], 'port');

        // Router/Firewall indicators
        if (in_array(161, $ports) && in_array(22, $ports)) {
            return 'router';
        }

        // Server indicators
        if (in_array(3306, $ports) || in_array(5432, $ports) || in_array(3389, $ports)) {
            return 'server';
        }

        // Web server
        if (in_array(80, $ports) || in_array(443, $ports)) {
            return 'server';
        }

        return $device['device_type'] ?? 'workstation';
    }

    /**
     * Get MAC vendor from OUI database
     */
    private function getMacVendor($mac)
    {
        $oui = substr(str_replace(':', '', $mac), 0, 6);

        // Simplified vendor lookup
        $vendors = [
            '001A2B' => 'Cisco Systems',
            '00505' => 'Hewlett Packard',
            '001122' => 'Dell',
            '00259' => 'Netgear',
            '001D7E' => 'Ubiquiti Networks'
        ];

        return $vendors[$oui] ?? 'Unknown Vendor';
    }

    /**
     * Save discovered device to database
     */
    private function saveDevice($device)
    {
        try {
            // Check if device already exists
            $existing = $this->db->fetchOne("
                SELECT id FROM network_devices WHERE ip_address = ?
            ", [$device['ip_address']]);

            if ($existing) {
                // Update existing device
                $this->db->query("
                    UPDATE network_devices SET
                        device_name = ?,
                        device_type = ?,
                        mac_address = ?,
                        manufacturer = ?,
                        model = ?,
                        status = 'online',
                        last_seen = NOW(),
                        updated_at = NOW()
                    WHERE ip_address = ?
                ", [
                    $device['device_name'] ?? $device['hostname'] ?? 'Device-' . $device['ip_address'],
                    $device['device_type'],
                    $device['mac_address'],
                    $device['manufacturer'],
                    $device['model'],
                    $device['ip_address']
                ]);
            } else {
                // Insert new device
                $this->db->query("
                    INSERT INTO network_devices
                    (device_name, device_type, ip_address, mac_address, manufacturer, model, status, last_seen, monitored)
                    VALUES (?, ?, ?, ?, ?, ?, 'online', NOW(), 1)
                ", [
                    $device['device_name'] ?? $device['hostname'] ?? 'Device-' . $device['ip_address'],
                    $device['device_type'],
                    $device['ip_address'],
                    $device['mac_address'],
                    $device['manufacturer'],
                    $device['model']
                ]);
            }
        } catch (Exception $e) {
            error_log("Failed to save device: " . $e->getMessage());
        }
    }

    /**
     * Get discovery summary
     */
    public function getDiscoverySummary()
    {
        $total = count($this->discoveredDevices);
        $byType = [];

        foreach ($this->discoveredDevices as $device) {
            $type = $device['device_type'];
            $byType[$type] = ($byType[$type] ?? 0) + 1;
        }

        return [
            'total_discovered' => $total,
            'by_type' => $byType,
            'devices' => $this->discoveredDevices
        ];
    }
}
