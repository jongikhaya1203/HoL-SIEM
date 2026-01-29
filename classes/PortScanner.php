<?php
/**
 * Port Scanner Class
 * Performs TCP/UDP port scanning on target hosts
 * Based on Gartner NDR best practices for network discovery
 */

class PortScanner {
    private $timeout = 0.5;  // Reduced from 2s for faster scanning
    private $commonPorts = [
        21, 22, 23, 25, 53, 80, 110, 111, 135, 139, 143, 443, 445, 993, 995,
        1723, 3306, 3389, 5900, 8080, 8443, 8888
    ];

    /**
     * Scan a single port on a host
     */
    public function scanPort($host, $port, $protocol = 'tcp') {
        $result = [
            'port' => $port,
            'protocol' => $protocol,
            'state' => 'closed',
            'service' => null,
            'banner' => null
        ];

        if ($protocol === 'tcp') {
            $connection = @fsockopen($host, $port, $errno, $errstr, $this->timeout);

            if ($connection) {
                $result['state'] = 'open';

                // Try to grab banner
                stream_set_timeout($connection, 1);
                $banner = @fread($connection, 1024);
                if ($banner) {
                    $result['banner'] = trim($banner);
                }

                fclose($connection);
            }
        } elseif ($protocol === 'udp') {
            // UDP scanning is less reliable
            $socket = @socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
            if ($socket) {
                socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => $this->timeout, 'usec' => 0]);
                @socket_connect($socket, $host, $port);
                @socket_send($socket, "\x00", 1, 0);

                $response = '';
                $from = '';
                $port_from = 0;

                if (@socket_recvfrom($socket, $response, 1024, 0, $from, $port_from)) {
                    $result['state'] = 'open';
                }

                socket_close($socket);
            }
        }

        return $result;
    }

    /**
     * Scan multiple ports on a host
     */
    public function scanPorts($host, $ports = null, $protocol = 'tcp') {
        if ($ports === null) {
            $ports = $this->commonPorts;
        }

        $results = [];
        foreach ($ports as $port) {
            $results[] = $this->scanPort($host, $port, $protocol);
        }

        return $results;
    }

    /**
     * Scan a range of ports
     */
    public function scanPortRange($host, $startPort, $endPort, $protocol = 'tcp') {
        $results = [];
        for ($port = $startPort; $port <= $endPort; $port++) {
            $results[] = $this->scanPort($host, $port, $protocol);
        }
        return $results;
    }

    /**
     * Quick scan - only common ports
     */
    public function quickScan($host) {
        return $this->scanPorts($host, $this->commonPorts);
    }

    /**
     * Full scan - all ports 1-65535 (very slow)
     */
    public function fullScan($host) {
        return $this->scanPortRange($host, 1, 65535);
    }

    /**
     * Check if host is alive (ping)
     */
    public function isHostAlive($host) {
        // Try common ports first
        $quickPorts = [80, 443, 22, 21, 445];

        foreach ($quickPorts as $port) {
            $result = $this->scanPort($host, $port);
            if ($result['state'] === 'open') {
                return true;
            }
        }

        // Try ICMP ping as fallback
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $ping = exec("ping -n 1 -w 1000 " . escapeshellarg($host), $output, $returnCode);
        } else {
            $ping = exec("ping -c 1 -W 1 " . escapeshellarg($host), $output, $returnCode);
        }

        return $returnCode === 0;
    }

    /**
     * Set timeout for connections
     */
    public function setTimeout($seconds) {
        $this->timeout = $seconds;
    }

    /**
     * Get service name for common ports
     */
    public function getServiceName($port) {
        $services = [
            20 => 'FTP-DATA',
            21 => 'FTP',
            22 => 'SSH',
            23 => 'TELNET',
            25 => 'SMTP',
            53 => 'DNS',
            80 => 'HTTP',
            110 => 'POP3',
            111 => 'RPCBIND',
            135 => 'MS-RPC',
            139 => 'NETBIOS-SSN',
            143 => 'IMAP',
            443 => 'HTTPS',
            445 => 'SMB',
            465 => 'SMTPS',
            587 => 'SMTP-SUBMISSION',
            993 => 'IMAPS',
            995 => 'POP3S',
            1433 => 'MS-SQL',
            1521 => 'ORACLE',
            1723 => 'PPTP',
            3306 => 'MYSQL',
            3389 => 'RDP',
            5432 => 'POSTGRESQL',
            5900 => 'VNC',
            6379 => 'REDIS',
            8080 => 'HTTP-PROXY',
            8443 => 'HTTPS-ALT',
            8888 => 'HTTP-ALT',
            27017 => 'MONGODB'
        ];

        return $services[$port] ?? 'UNKNOWN';
    }
}
