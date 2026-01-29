<?php
/**
 * Service Detection Class
 * Identifies services and versions running on open ports
 */

class ServiceDetector {
    private $timeout = 1;  // Reduced from 3s for faster scanning

    /**
     * Detect service on a specific port
     */
    public function detectService($host, $port) {
        $result = [
            'service_name' => null,
            'version' => null,
            'banner' => null,
            'os_hint' => null,
            'ssl_enabled' => false
        ];

        // Check if it's an SSL/TLS port
        if (in_array($port, [443, 465, 993, 995, 8443])) {
            $result['ssl_enabled'] = true;
            $serviceInfo = $this->detectSSLService($host, $port);
        } else {
            $serviceInfo = $this->detectPlainService($host, $port);
        }

        return array_merge($result, $serviceInfo);
    }

    /**
     * Detect plain text service
     */
    private function detectPlainService($host, $port) {
        $result = [
            'service_name' => null,
            'version' => null,
            'banner' => null
        ];

        $connection = @fsockopen($host, $port, $errno, $errstr, $this->timeout);
        if (!$connection) {
            return $result;
        }

        stream_set_timeout($connection, $this->timeout);

        // Send HTTP request for web servers
        if (in_array($port, [80, 8080, 8888])) {
            fwrite($connection, "GET / HTTP/1.0\r\nHost: $host\r\n\r\n");
        }

        // Read banner (with time limit to avoid hanging)
        $banner = '';
        $startTime = microtime(true);
        while (!feof($connection) && (microtime(true) - $startTime) < 1) {
            $line = fgets($connection, 1024);
            if ($line === false) break;  // No more data
            $banner .= $line;
            if (strlen($banner) > 2048) break;  // Limit banner size (reduced from 4096)
        }
        fclose($connection);

        $result['banner'] = trim($banner);

        // Parse banner for service identification
        if (!empty($banner)) {
            $this->parseServiceBanner($banner, $result);
        }

        return $result;
    }

    /**
     * Detect SSL/TLS service
     */
    private function detectSSLService($host, $port) {
        $result = [
            'service_name' => null,
            'version' => null,
            'banner' => null,
            'ssl_info' => []
        ];

        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'capture_peer_cert' => true
            ]
        ]);

        $connection = @stream_socket_client(
            "ssl://{$host}:{$port}",
            $errno,
            $errstr,
            $this->timeout,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (!$connection) {
            return $result;
        }

        // Get SSL certificate info
        $params = stream_context_get_params($connection);
        if (isset($params['options']['ssl']['peer_certificate'])) {
            $cert = openssl_x509_parse($params['options']['ssl']['peer_certificate']);
            $result['ssl_info'] = [
                'issuer' => $cert['issuer']['CN'] ?? null,
                'subject' => $cert['name'] ?? null,
                'valid_from' => date('Y-m-d', $cert['validFrom_time_t']),
                'valid_to' => date('Y-m-d', $cert['validTo_time_t']),
                'expired' => time() > $cert['validTo_time_t']
            ];
        }

        // Try to get HTTP headers for web servers
        if (in_array($port, [443, 8443])) {
            fwrite($connection, "GET / HTTP/1.0\r\nHost: $host\r\n\r\n");
            $banner = '';
            $startTime = microtime(true);
            while (!feof($connection) && strlen($banner) < 2048 && (microtime(true) - $startTime) < 1) {
                $line = fgets($connection, 1024);
                if ($line === false) break;
                $banner .= $line;
            }
            $result['banner'] = trim($banner);
            $this->parseServiceBanner($banner, $result);
        }

        fclose($connection);
        return $result;
    }

    /**
     * Parse service banner to identify service and version
     */
    private function parseServiceBanner($banner, &$result) {
        // HTTP/HTTPS detection
        if (preg_match('/^HTTP\/[\d.]+/', $banner)) {
            $result['service_name'] = 'HTTP';

            // Apache
            if (preg_match('/Apache\/([\d.]+)/i', $banner, $matches)) {
                $result['service_name'] = 'Apache';
                $result['version'] = $matches[1];
            }
            // Nginx
            elseif (preg_match('/nginx\/([\d.]+)/i', $banner, $matches)) {
                $result['service_name'] = 'Nginx';
                $result['version'] = $matches[1];
            }
            // IIS
            elseif (preg_match('/Microsoft-IIS\/([\d.]+)/i', $banner, $matches)) {
                $result['service_name'] = 'IIS';
                $result['version'] = $matches[1];
            }
            // PHP
            if (preg_match('/PHP\/([\d.]+)/i', $banner, $matches)) {
                $result['php_version'] = $matches[1];
            }
        }
        // SSH detection
        elseif (preg_match('/^SSH-([\d.]+)-(.+)/i', $banner, $matches)) {
            $result['service_name'] = 'SSH';
            $result['version'] = $matches[1];
            $result['ssh_software'] = trim($matches[2]);
        }
        // FTP detection
        elseif (preg_match('/^220.*FTP/i', $banner)) {
            $result['service_name'] = 'FTP';
            if (preg_match('/ProFTPD ([\d.]+)/i', $banner, $matches)) {
                $result['version'] = $matches[1];
            } elseif (preg_match('/vsftpd ([\d.]+)/i', $banner, $matches)) {
                $result['version'] = $matches[1];
            }
        }
        // SMTP detection
        elseif (preg_match('/^220.*SMTP/i', $banner)) {
            $result['service_name'] = 'SMTP';
            if (preg_match('/Postfix/i', $banner)) {
                $result['smtp_software'] = 'Postfix';
            } elseif (preg_match('/Exim ([\d.]+)/i', $banner, $matches)) {
                $result['smtp_software'] = 'Exim';
                $result['version'] = $matches[1];
            }
        }
        // MySQL detection
        elseif (preg_match('/mysql/i', $banner)) {
            $result['service_name'] = 'MySQL';
            if (preg_match('/([\d.]+)/', $banner, $matches)) {
                $result['version'] = $matches[1];
            }
        }
        // PostgreSQL detection
        elseif (preg_match('/postgresql/i', $banner)) {
            $result['service_name'] = 'PostgreSQL';
        }
        // MongoDB detection
        elseif (preg_match('/mongodb/i', $banner)) {
            $result['service_name'] = 'MongoDB';
        }
        // RDP detection
        elseif (preg_match('/rdp|remote desktop/i', $banner)) {
            $result['service_name'] = 'RDP';
        }
    }

    /**
     * Check for known vulnerable service versions
     */
    public function checkVulnerableVersion($serviceName, $version) {
        $vulnerableVersions = [
            'Apache' => [
                ['version' => '2.4.49', 'cve' => 'CVE-2021-41773', 'severity' => 'critical'],
                ['version' => '2.4.50', 'cve' => 'CVE-2021-42013', 'severity' => 'critical']
            ],
            'OpenSSH' => [
                ['version' => '7.4', 'cve' => 'CVE-2018-15473', 'severity' => 'medium'],
                ['version' => '8.5', 'cve' => 'CVE-2021-28041', 'severity' => 'high']
            ],
            'MySQL' => [
                ['version' => '5.7.0', 'cve' => 'CVE-2023-21980', 'severity' => 'high']
            ]
        ];

        $vulnerabilities = [];
        if (isset($vulnerableVersions[$serviceName])) {
            foreach ($vulnerableVersions[$serviceName] as $vuln) {
                if (version_compare($version, $vuln['version'], '<=')) {
                    $vulnerabilities[] = $vuln;
                }
            }
        }

        return $vulnerabilities;
    }

    /**
     * Perform OS fingerprinting based on service banners
     */
    public function detectOS($services) {
        $osHints = [];

        foreach ($services as $service) {
            if (isset($service['banner'])) {
                $banner = $service['banner'];

                if (preg_match('/Ubuntu/i', $banner)) {
                    $osHints[] = 'Ubuntu Linux';
                } elseif (preg_match('/Debian/i', $banner)) {
                    $osHints[] = 'Debian Linux';
                } elseif (preg_match('/CentOS/i', $banner)) {
                    $osHints[] = 'CentOS Linux';
                } elseif (preg_match('/Red Hat|RHEL/i', $banner)) {
                    $osHints[] = 'Red Hat Linux';
                } elseif (preg_match('/Windows/i', $banner)) {
                    $osHints[] = 'Windows';
                } elseif (preg_match('/Microsoft/i', $banner)) {
                    $osHints[] = 'Windows';
                }
            }
        }

        return array_unique($osHints);
    }
}
