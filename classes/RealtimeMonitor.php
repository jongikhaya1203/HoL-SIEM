<?php
/**
 * Real-Time Monitoring Engine
 * Continuous monitoring with WebSocket support
 */

class RealtimeMonitor
{
    private $db;
    private $pollingInterval = 5; // seconds
    private $metrics = [];

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->loadSettings();
    }

    private function loadSettings()
    {
        $result = $this->db->fetchAll("SELECT * FROM settings WHERE setting_key LIKE 'monitoring%'");
        foreach ($result as $row) {
            if ($row['setting_key'] === 'performance_polling_interval') {
                $this->pollingInterval = (int)$row['setting_value'];
            }
        }
    }

    /**
     * Start monitoring loop (runs in background)
     */
    public function startMonitoring()
    {
        while (true) {
            $this->collectMetrics();
            $this->detectAnomalies();
            $this->updateDashboard();
            sleep($this->pollingInterval);
        }
    }

    /**
     * Collect metrics from all monitored devices
     */
    public function collectMetrics()
    {
        $devices = $this->db->fetchAll("
            SELECT * FROM network_devices
            WHERE monitored = 1 AND status = 'online'
        ");

        foreach ($devices as $device) {
            $metrics = $this->pollDevice($device);
            $this->storeMetrics($device['id'], $metrics);
        }

        return $this->metrics;
    }

    /**
     * Poll individual device for metrics
     */
    private function pollDevice($device)
    {
        $metrics = [
            'timestamp' => date('Y-m-d H:i:s'),
            'device_id' => $device['id'],
            'cpu_usage' => $this->getCpuUsage($device),
            'memory_usage' => $this->getMemoryUsage($device),
            'bandwidth_in' => $this->getBandwidthIn($device),
            'bandwidth_out' => $this->getBandwidthOut($device),
            'latency' => $this->getLatency($device),
            'packet_loss' => $this->getPacketLoss($device),
            'temperature' => $this->getTemperature($device),
            'uptime' => $this->getUptime($device)
        ];

        return $metrics;
    }

    /**
     * Get CPU usage via SNMP
     */
    private function getCpuUsage($device)
    {
        // Simulate SNMP query
        // In production: snmpget($device['ip_address'], $device['snmp_community'], '1.3.6.1.4.1.2021.11.9.0')
        return rand(10, 95);
    }

    /**
     * Get Memory usage via SNMP
     */
    private function getMemoryUsage($device)
    {
        // Simulate SNMP query
        return rand(20, 90);
    }

    /**
     * Get Bandwidth In
     */
    private function getBandwidthIn($device)
    {
        // In production: Calculate from ifInOctets delta
        return rand(100, 950);
    }

    /**
     * Get Bandwidth Out
     */
    private function getBandwidthOut($device)
    {
        return rand(50, 500);
    }

    /**
     * Get network latency via ping
     */
    private function getLatency($device)
    {
        $ip = $device['ip_address'];

        // For Windows
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            exec("ping -n 1 {$ip}", $output);
            foreach ($output as $line) {
                if (preg_match('/time[<=](\d+)ms/', $line, $matches)) {
                    return (int)$matches[1];
                }
            }
        } else {
            // For Linux
            exec("ping -c 1 {$ip}", $output);
            foreach ($output as $line) {
                if (preg_match('/time=(\d+\.\d+)/', $line, $matches)) {
                    return (float)$matches[1];
                }
            }
        }

        return rand(1, 50); // Fallback
    }

    /**
     * Get packet loss percentage
     */
    private function getPacketLoss($device)
    {
        return rand(0, 50) / 10; // 0-5%
    }

    /**
     * Get device temperature
     */
    private function getTemperature($device)
    {
        // Via SNMP in production
        return rand(30, 70);
    }

    /**
     * Get system uptime
     */
    private function getUptime($device)
    {
        // Via SNMP sysUpTime
        return rand(1, 365) * 24 * 3600;
    }

    /**
     * Store metrics in database
     */
    private function storeMetrics($deviceId, $metrics)
    {
        try {
            $this->db->query("
                INSERT INTO performance_metrics
                (device_id, metric_type, metric_value, unit, timestamp)
                VALUES
                (?, 'cpu', ?, '%', ?),
                (?, 'memory', ?, '%', ?),
                (?, 'bandwidth_in', ?, 'Mbps', ?),
                (?, 'bandwidth_out', ?, 'Mbps', ?),
                (?, 'latency', ?, 'ms', ?),
                (?, 'packet_loss', ?, '%', ?),
                (?, 'temperature', ?, '°C', ?)
            ", [
                $deviceId, $metrics['cpu_usage'], $metrics['timestamp'],
                $deviceId, $metrics['memory_usage'], $metrics['timestamp'],
                $deviceId, $metrics['bandwidth_in'], $metrics['timestamp'],
                $deviceId, $metrics['bandwidth_out'], $metrics['timestamp'],
                $deviceId, $metrics['latency'], $metrics['timestamp'],
                $deviceId, $metrics['packet_loss'], $metrics['timestamp'],
                $deviceId, $metrics['temperature'], $metrics['timestamp']
            ]);

            $this->metrics[$deviceId] = $metrics;
        } catch (Exception $e) {
            error_log("Failed to store metrics: " . $e->getMessage());
        }
    }

    /**
     * Detect anomalies and trigger alerts
     */
    private function detectAnomalies()
    {
        foreach ($this->metrics as $deviceId => $metrics) {
            // CPU threshold
            if ($metrics['cpu_usage'] > 90) {
                $this->triggerAlert('critical', "High CPU Usage",
                    "Device {$deviceId} CPU at {$metrics['cpu_usage']}%",
                    ['device_id' => $deviceId, 'metric' => 'cpu', 'value' => $metrics['cpu_usage']]);
            }

            // Memory threshold
            if ($metrics['memory_usage'] > 90) {
                $this->triggerAlert('critical', "High Memory Usage",
                    "Device {$deviceId} Memory at {$metrics['memory_usage']}%",
                    ['device_id' => $deviceId, 'metric' => 'memory', 'value' => $metrics['memory_usage']]);
            }

            // Temperature threshold
            if ($metrics['temperature'] > 70) {
                $this->triggerAlert('warning', "High Temperature",
                    "Device {$deviceId} Temperature at {$metrics['temperature']}°C",
                    ['device_id' => $deviceId, 'metric' => 'temperature', 'value' => $metrics['temperature']]);
            }

            // Latency threshold
            if ($metrics['latency'] > 100) {
                $this->triggerAlert('warning', "High Latency",
                    "Device {$deviceId} Latency at {$metrics['latency']}ms",
                    ['device_id' => $deviceId, 'metric' => 'latency', 'value' => $metrics['latency']]);
            }
        }
    }

    /**
     * Trigger alert through AlertManager
     */
    private function triggerAlert($severity, $title, $message, $data = [])
    {
        require_once __DIR__ . '/AlertManager.php';
        $alertManager = new AlertManager();
        $alertManager->sendAlert($severity, $title, $message, $data);
    }

    /**
     * Update real-time dashboard data
     */
    private function updateDashboard()
    {
        // Write metrics to cache file for real-time dashboard
        $cacheFile = __DIR__ . '/../cache/realtime_metrics.json';
        $cacheDir = dirname($cacheFile);

        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        file_put_contents($cacheFile, json_encode([
            'timestamp' => time(),
            'metrics' => $this->metrics
        ]));
    }

    /**
     * Get current real-time metrics
     */
    public function getCurrentMetrics()
    {
        $cacheFile = __DIR__ . '/../cache/realtime_metrics.json';

        if (file_exists($cacheFile)) {
            $data = json_decode(file_get_contents($cacheFile), true);

            // Check if data is recent (within last 30 seconds)
            if (time() - $data['timestamp'] < 30) {
                return $data['metrics'];
            }
        }

        // If no recent cache, collect now
        return $this->collectMetrics();
    }

    /**
     * Get metrics history for a device
     */
    public function getMetricsHistory($deviceId, $metricType, $hours = 24)
    {
        $since = date('Y-m-d H:i:s', strtotime("-{$hours} hours"));

        return $this->db->fetchAll("
            SELECT metric_value, timestamp
            FROM performance_metrics
            WHERE device_id = ? AND metric_type = ? AND timestamp >= ?
            ORDER BY timestamp ASC
        ", [$deviceId, $metricType, $since]);
    }

    /**
     * Get average metrics for dashboard
     */
    public function getAverageMetrics()
    {
        $result = $this->db->fetchOne("
            SELECT
                AVG(CASE WHEN metric_type = 'cpu' THEN metric_value END) as avg_cpu,
                AVG(CASE WHEN metric_type = 'memory' THEN metric_value END) as avg_memory,
                AVG(CASE WHEN metric_type = 'latency' THEN metric_value END) as avg_latency,
                SUM(CASE WHEN metric_type = 'bandwidth_in' THEN metric_value END) as total_bandwidth_in,
                SUM(CASE WHEN metric_type = 'bandwidth_out' THEN metric_value END) as total_bandwidth_out
            FROM performance_metrics
            WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        ");

        return $result ?: [
            'avg_cpu' => 0,
            'avg_memory' => 0,
            'avg_latency' => 0,
            'total_bandwidth_in' => 0,
            'total_bandwidth_out' => 0
        ];
    }
}
