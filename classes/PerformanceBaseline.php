<?php
/**
 * Performance Baseline System
 * Establish and monitor performance baselines for anomaly detection
 */

class PerformanceBaseline
{
    private $db;
    private $baselineWindow = 7; // days

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Calculate baseline for a device and metric
     */
    public function calculateBaseline($deviceId, $metricType)
    {
        $data = $this->getHistoricalData($deviceId, $metricType, $this->baselineWindow);

        if (empty($data)) {
            return null;
        }

        $values = array_column($data, 'metric_value');

        $baseline = [
            'device_id' => $deviceId,
            'metric_type' => $metricType,
            'mean' => $this->calculateMean($values),
            'median' => $this->calculateMedian($values),
            'std_dev' => $this->calculateStdDev($values),
            'min' => min($values),
            'max' => max($values),
            'percentile_95' => $this->calculatePercentile($values, 95),
            'percentile_99' => $this->calculatePercentile($values, 99),
            'sample_size' => count($values),
            'calculated_at' => date('Y-m-d H:i:s'),
            'valid_until' => date('Y-m-d H:i:s', strtotime('+7 days'))
        ];

        $this->saveBaseline($baseline);

        return $baseline;
    }

    /**
     * Get historical data for baseline calculation
     */
    private function getHistoricalData($deviceId, $metricType, $days)
    {
        return $this->db->fetchAll("
            SELECT metric_value, timestamp
            FROM performance_metrics
            WHERE device_id = ?
              AND metric_type = ?
              AND timestamp >= DATE_SUB(NOW(), INTERVAL ? DAY)
            ORDER BY timestamp ASC
        ", [$deviceId, $metricType, $days]);
    }

    /**
     * Save baseline to database
     */
    private function saveBaseline($baseline)
    {
        try {
            $this->db->query("
                INSERT INTO performance_baselines
                (device_id, metric_type, mean, median, std_dev, min_value, max_value,
                 percentile_95, percentile_99, sample_size, calculated_at, valid_until)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    mean = VALUES(mean),
                    median = VALUES(median),
                    std_dev = VALUES(std_dev),
                    min_value = VALUES(min_value),
                    max_value = VALUES(max_value),
                    percentile_95 = VALUES(percentile_95),
                    percentile_99 = VALUES(percentile_99),
                    sample_size = VALUES(sample_size),
                    calculated_at = VALUES(calculated_at),
                    valid_until = VALUES(valid_until)
            ", [
                $baseline['device_id'],
                $baseline['metric_type'],
                $baseline['mean'],
                $baseline['median'],
                $baseline['std_dev'],
                $baseline['min'],
                $baseline['max'],
                $baseline['percentile_95'],
                $baseline['percentile_99'],
                $baseline['sample_size'],
                $baseline['calculated_at'],
                $baseline['valid_until']
            ]);
        } catch (Exception $e) {
            error_log("Failed to save baseline: " . $e->getMessage());
        }
    }

    /**
     * Check if current value is anomalous
     */
    public function isAnomaly($deviceId, $metricType, $currentValue)
    {
        $baseline = $this->getBaseline($deviceId, $metricType);

        if (!$baseline) {
            return ['is_anomaly' => false, 'reason' => 'No baseline available'];
        }

        // Use 3-sigma rule for anomaly detection
        $threshold = 3 * $baseline['std_dev'];
        $deviation = abs($currentValue - $baseline['mean']);

        if ($deviation > $threshold) {
            return [
                'is_anomaly' => true,
                'reason' => 'Value exceeds 3-sigma threshold',
                'deviation' => $deviation,
                'threshold' => $threshold,
                'baseline_mean' => $baseline['mean'],
                'severity' => $deviation > (4 * $baseline['std_dev']) ? 'critical' : 'warning'
            ];
        }

        // Check if value exceeds 99th percentile
        if ($currentValue > $baseline['percentile_99']) {
            return [
                'is_anomaly' => true,
                'reason' => 'Value exceeds 99th percentile',
                'percentile_99' => $baseline['percentile_99'],
                'severity' => 'warning'
            ];
        }

        return ['is_anomaly' => false];
    }

    /**
     * Get baseline for device and metric
     */
    public function getBaseline($deviceId, $metricType)
    {
        try {
            return $this->db->fetchOne("
                SELECT * FROM performance_baselines
                WHERE device_id = ? AND metric_type = ?
                  AND valid_until >= NOW()
                ORDER BY calculated_at DESC
                LIMIT 1
            ", [$deviceId, $metricType]);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Calculate mean
     */
    private function calculateMean($values)
    {
        return array_sum($values) / count($values);
    }

    /**
     * Calculate median
     */
    private function calculateMedian($values)
    {
        sort($values);
        $count = count($values);
        $middle = floor($count / 2);

        if ($count % 2 == 0) {
            return ($values[$middle - 1] + $values[$middle]) / 2;
        }

        return $values[$middle];
    }

    /**
     * Calculate standard deviation
     */
    private function calculateStdDev($values)
    {
        $mean = $this->calculateMean($values);
        $variance = array_sum(array_map(function($val) use ($mean) {
            return pow($val - $mean, 2);
        }, $values)) / count($values);

        return sqrt($variance);
    }

    /**
     * Calculate percentile
     */
    private function calculatePercentile($values, $percentile)
    {
        sort($values);
        $index = ($percentile / 100) * (count($values) - 1);
        $lower = floor($index);
        $upper = ceil($index);

        if ($lower == $upper) {
            return $values[$lower];
        }

        return $values[$lower] + ($index - $lower) * ($values[$upper] - $values[$lower]);
    }

    /**
     * Recalculate all baselines
     */
    public function recalculateAllBaselines()
    {
        $devices = $this->db->fetchAll("SELECT id FROM network_devices WHERE monitored = 1");
        $metrics = ['cpu', 'memory', 'bandwidth_in', 'bandwidth_out', 'latency'];

        $updated = 0;

        foreach ($devices as $device) {
            foreach ($metrics as $metric) {
                $baseline = $this->calculateBaseline($device['id'], $metric);
                if ($baseline) {
                    $updated++;
                }
            }
        }

        return $updated;
    }
}
