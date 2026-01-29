<?php
/**
 * Parallel Scanner - High-Performance Network Scanning
 * Uses multi-curl and stream_select for parallel operations
 */

class ParallelScanner {
    private $timeout = 0.5;
    private $maxParallel = 50;  // Scan 50 hosts/ports simultaneously

    /**
     * Check multiple hosts alive in parallel using ICMP ping
     */
    public function checkHostsAlive($hosts) {
        $alive = [];

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows: Use parallel ping
            $batches = array_chunk($hosts, 50);
            foreach ($batches as $batch) {
                $processes = [];
                foreach ($batch as $host) {
                    $descriptorspec = [
                        0 => ["pipe", "r"],
                        1 => ["pipe", "w"],
                        2 => ["pipe", "w"]
                    ];
                    $process = proc_open(
                        "ping -n 1 -w 500 " . escapeshellarg($host),
                        $descriptorspec,
                        $pipes
                    );
                    if (is_resource($process)) {
                        $processes[$host] = ['process' => $process, 'pipes' => $pipes];
                    }
                }

                // Wait for all pings to complete
                foreach ($processes as $host => $data) {
                    $status = proc_get_status($data['process']);
                    fclose($data['pipes'][0]);
                    fclose($data['pipes'][1]);
                    fclose($data['pipes'][2]);
                    $exitCode = proc_close($data['process']);
                    if ($exitCode === 0) {
                        $alive[] = $host;
                    }
                }
            }
        } else {
            // Linux: Use fping for parallel ping
            $hostList = implode(' ', array_map('escapeshellarg', $hosts));
            exec("fping -c 1 -t 500 $hostList 2>&1", $output);
            foreach ($output as $line) {
                if (preg_match('/^(\S+)\s+:\s+.*\s+(\d+)\/\d+/', $line, $matches)) {
                    if ($matches[2] > 0) {  // At least one response
                        $alive[] = $matches[1];
                    }
                }
            }
        }

        return $alive;
    }

    /**
     * Scan multiple ports in parallel using stream_select
     */
    public function scanPortsParallel($host, $ports) {
        $results = [];
        $sockets = [];
        $startTimes = [];

        // Open all connections simultaneously
        foreach ($ports as $port) {
            $socket = @stream_socket_client(
                "tcp://{$host}:{$port}",
                $errno,
                $errstr,
                $this->timeout,
                STREAM_CLIENT_CONNECT | STREAM_CLIENT_ASYNC_CONNECT
            );

            if ($socket) {
                stream_set_blocking($socket, false);
                $sockets[$port] = $socket;
                $startTimes[$port] = microtime(true);
                $results[$port] = ['port' => $port, 'state' => 'filtered', 'protocol' => 'tcp'];
            } else {
                $results[$port] = ['port' => $port, 'state' => 'closed', 'protocol' => 'tcp'];
            }
        }

        // Check which connections succeeded
        $timeout = $this->timeout;
        while (!empty($sockets) && $timeout > 0) {
            $read = $write = $except = $sockets;
            $startSelect = microtime(true);

            $num = @stream_select($read, $write, $except, 0, 100000);  // 100ms timeout

            $timeout -= (microtime(true) - $startSelect);

            // Check writable sockets (connection established)
            foreach ($write as $socket) {
                $port = array_search($socket, $sockets);
                if ($port !== false) {
                    $results[$port]['state'] = 'open';

                    // Try to grab banner
                    $banner = @fread($socket, 512);
                    if ($banner) {
                        $results[$port]['banner'] = trim($banner);
                    }

                    fclose($socket);
                    unset($sockets[$port]);
                }
            }

            // Check for timeouts
            foreach ($sockets as $port => $socket) {
                if (microtime(true) - $startTimes[$port] > $this->timeout) {
                    fclose($socket);
                    unset($sockets[$port]);
                }
            }
        }

        // Close remaining sockets
        foreach ($sockets as $socket) {
            fclose($socket);
        }

        return array_values($results);
    }

    /**
     * Quick parallel scan of common ports
     */
    public function quickScanParallel($host) {
        $commonPorts = [21, 22, 23, 25, 53, 80, 110, 135, 139, 143, 443, 445,
                        993, 995, 1723, 3306, 3389, 5900, 8080, 8443, 8888];
        return $this->scanPortsParallel($host, $commonPorts);
    }

    /**
     * Scan multiple hosts in parallel (batched)
     */
    public function scanMultipleHosts($hosts, $ports) {
        $allResults = [];

        // Process in batches to avoid overwhelming the network
        $batches = array_chunk($hosts, 10);  // 10 hosts at a time

        foreach ($batches as $batch) {
            foreach ($batch as $host) {
                $allResults[$host] = $this->scanPortsParallel($host, $ports);
            }
        }

        return $allResults;
    }

    /**
     * Set timeout
     */
    public function setTimeout($seconds) {
        $this->timeout = $seconds;
    }

    /**
     * Set max parallel connections
     */
    public function setMaxParallel($max) {
        $this->maxParallel = $max;
    }
}
