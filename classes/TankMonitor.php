<?php
/**
 * Tank Level Monitoring System
 * Real-time tank level monitoring with alarming
 * Supports multiple tank types and measurement methods
 *
 * @author HoL Platform
 * @version 2.0
 */

require_once __DIR__ . '/Database.php';

class TankMonitor {
    private $db;
    private $alarmThresholds = [];

    // Tank status codes
    const STATUS_NORMAL = 'normal';
    const STATUS_LOW = 'low';
    const STATUS_HIGH = 'high';
    const STATUS_CRITICAL_LOW = 'critical_low';
    const STATUS_CRITICAL_HIGH = 'critical_high';
    const STATUS_OVERFLOW = 'overflow';
    const STATUS_EMPTY = 'empty';

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Update tank level from sensor reading
     */
    public function updateTankLevel($tankId, $levelValue, $temperatureValue = null, $pressureValue = null) {
        try {
            // Get tank configuration
            $tank = $this->getTankInfo($tankId);
            if (!$tank) {
                throw new Exception("Tank not found: ID $tankId");
            }

            // Calculate volume from level
            $volume = $this->calculateVolume($tank, $levelValue);

            // Calculate percentage
            $percentage = ($levelValue / $tank['capacity_total']) * 100;

            // Determine tank status
            $status = $this->determineTankStatus($tank, $percentage);

            // Update tank status
            $this->updateTankStatus($tankId, $levelValue, $volume, $percentage, $status);

            // Check alarms
            $this->checkTankAlarms($tank, $levelValue, $percentage, $status);

            // Update temperature and pressure if provided
            if ($temperatureValue !== null && $tank['temperature_tag_id']) {
                $this->updateTagValue($tank['temperature_tag_id'], $temperatureValue);
            }

            if ($pressureValue !== null && $tank['pressure_tag_id']) {
                $this->updateTagValue($tank['pressure_tag_id'], $pressureValue);
            }

            return [
                'success' => true,
                'tank_name' => $tank['tank_name'],
                'level' => $levelValue,
                'volume' => $volume,
                'percentage' => $percentage,
                'status' => $status
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Calculate volume based on tank geometry
     */
    private function calculateVolume($tank, $level) {
        // Extract tank dimensions from metadata
        $metadata = json_decode($tank['tank_metadata'] ?? '{}', true);

        $shape = $metadata['shape'] ?? 'cylindrical';
        $diameter = $metadata['diameter'] ?? 0;
        $height = $metadata['height'] ?? 0;
        $length = $metadata['length'] ?? 0;

        switch ($shape) {
            case 'cylindrical_vertical':
                // V = π * r² * h
                $radius = $diameter / 2;
                $volume = pi() * pow($radius, 2) * $level;
                break;

            case 'cylindrical_horizontal':
                // More complex calculation for horizontal cylinder
                $radius = $diameter / 2;
                $volume = $this->horizontalCylinderVolume($radius, $length, $level);
                break;

            case 'rectangular':
                // V = length * width * height
                $width = $metadata['width'] ?? 0;
                $volume = $length * $width * $level;
                break;

            case 'spherical':
                // V = (4/3) * π * r³ * fill_ratio
                $radius = $diameter / 2;
                $fillRatio = $level / $diameter;
                $volume = (4/3) * pi() * pow($radius, 3) * $fillRatio;
                break;

            case 'conical':
                // V = (1/3) * π * r² * h
                $radiusAtLevel = ($diameter / 2) * ($level / $height);
                $volume = (1/3) * pi() * pow($radiusAtLevel, 2) * $level;
                break;

            default:
                // Linear approximation
                $volume = $tank['capacity_total'] * ($level / $height);
        }

        // Convert to unit specified in tank configuration
        return round($volume, 2);
    }

    /**
     * Calculate volume for horizontal cylindrical tank
     */
    private function horizontalCylinderVolume($radius, $length, $height) {
        // Formula for partially filled horizontal cylinder
        $r = $radius;
        $h = $height;

        if ($h >= 2 * $r) {
            return pi() * pow($r, 2) * $length; // Full
        }

        if ($h <= 0) {
            return 0; // Empty
        }

        // Partial fill
        $area = pow($r, 2) * acos(($r - $h) / $r) - ($r - $h) * sqrt(2 * $r * $h - pow($h, 2));
        return $area * $length;
    }

    /**
     * Determine tank status based on level
     */
    private function determineTankStatus($tank, $percentage) {
        if ($percentage >= 100) {
            return self::STATUS_OVERFLOW;
        }

        if ($percentage <= 0) {
            return self::STATUS_EMPTY;
        }

        $criticalHigh = ($tank['critical_high_alarm'] / $tank['capacity_total']) * 100;
        $highAlarm = ($tank['high_level_alarm'] / $tank['capacity_total']) * 100;
        $lowAlarm = ($tank['low_level_alarm'] / $tank['capacity_total']) * 100;
        $criticalLow = ($tank['critical_low_alarm'] / $tank['capacity_total']) * 100;

        if ($percentage >= $criticalHigh) {
            return self::STATUS_CRITICAL_HIGH;
        }

        if ($percentage >= $highAlarm) {
            return self::STATUS_HIGH;
        }

        if ($percentage <= $criticalLow) {
            return self::STATUS_CRITICAL_LOW;
        }

        if ($percentage <= $lowAlarm) {
            return self::STATUS_LOW;
        }

        return self::STATUS_NORMAL;
    }

    /**
     * Update tank status in database
     */
    private function updateTankStatus($tankId, $level, $volume, $percentage, $status) {
        $query = "UPDATE scada_tank_levels
                  SET current_level = ?,
                      current_volume = ?,
                      current_percentage = ?,
                      tank_status = ?,
                      last_update_time = NOW()
                  WHERE tank_id = ?";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param("dddsi", $level, $volume, $percentage, $status, $tankId);
        $stmt->execute();
    }

    /**
     * Check and generate tank alarms
     */
    private function checkTankAlarms($tank, $level, $percentage, $status) {
        $alarmTypes = [
            self::STATUS_CRITICAL_LOW => ['type' => 'low_low', 'severity' => 'critical'],
            self::STATUS_LOW => ['type' => 'low', 'severity' => 'high'],
            self::STATUS_HIGH => ['type' => 'high', 'severity' => 'high'],
            self::STATUS_CRITICAL_HIGH => ['type' => 'high_high', 'severity' => 'critical'],
            self::STATUS_OVERFLOW => ['type' => 'high_high', 'severity' => 'critical'],
            self::STATUS_EMPTY => ['type' => 'low_low', 'severity' => 'critical']
        ];

        if (isset($alarmTypes[$status])) {
            $alarmInfo = $alarmTypes[$status];

            // Check if alarm already active
            $existingQuery = "SELECT id FROM scada_alarm_history
                             WHERE tag_id = ? AND alarm_state = 'active'
                             AND alarm_type = ? LIMIT 1";

            $stmt = $this->db->prepare($existingQuery);
            $stmt->bind_param("is", $tank['level_tag_id'], $alarmInfo['type']);
            $stmt->execute();
            $existing = $stmt->get_result()->fetch_assoc();

            if (!$existing) {
                // Generate new alarm
                $message = "Tank {$tank['tank_name']} level alarm: {$status} - {$percentage}% ({$level} {$tank['capacity_unit']})";

                $insertQuery = "INSERT INTO scada_alarm_history
                               (site_id, tag_id, asset_id, alarm_type, severity,
                                alarm_message, alarm_value, trigger_time, alarm_state)
                               VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 'active')";

                $stmt = $this->db->prepare($insertQuery);
                $stmt->bind_param(
                    "iiissd",
                    $tank['site_id'],
                    $tank['level_tag_id'],
                    $tank['tank_id'],
                    $alarmInfo['type'],
                    $alarmInfo['severity'],
                    $message,
                    $level
                );
                $stmt->execute();

                // Send notification
                $this->sendAlarmNotification($tank, $message, $alarmInfo['severity']);
            }
        } else {
            // Clear alarms if status is normal
            $this->clearTankAlarms($tank['level_tag_id']);
        }
    }

    /**
     * Clear tank alarms
     */
    private function clearTankAlarms($tagId) {
        $query = "UPDATE scada_alarm_history
                  SET alarm_state = 'cleared',
                      clear_time = NOW(),
                      duration_seconds = TIMESTAMPDIFF(SECOND, trigger_time, NOW())
                  WHERE tag_id = ? AND alarm_state = 'active'";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $tagId);
        $stmt->execute();
    }

    /**
     * Get tank fill/drain rate
     */
    public function getTankRate($tankId, $timeWindowMinutes = 5) {
        $query = "SELECT timestamp, value
                  FROM scada_tag_history
                  WHERE tag_id = (SELECT level_tag_id FROM scada_tank_levels WHERE tank_id = ?)
                  AND timestamp >= DATE_SUB(NOW(), INTERVAL ? MINUTE)
                  ORDER BY timestamp ASC";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ii", $tankId, $timeWindowMinutes);
        $stmt->execute();
        $result = $stmt->get_result();

        $readings = [];
        while ($row = $result->fetch_assoc()) {
            $readings[] = [
                'timestamp' => strtotime($row['timestamp']),
                'value' => floatval($row['value'])
            ];
        }

        if (count($readings) < 2) {
            return null;
        }

        // Calculate rate using linear regression
        $firstReading = $readings[0];
        $lastReading = end($readings);

        $timeDiff = ($lastReading['timestamp'] - $firstReading['timestamp']) / 60; // minutes
        $valueDiff = $lastReading['value'] - $firstReading['value'];

        $rate = $valueDiff / $timeDiff; // units per minute

        return [
            'rate_per_minute' => $rate,
            'rate_per_hour' => $rate * 60,
            'direction' => $rate > 0 ? 'filling' : 'draining',
            'time_to_full' => $rate > 0 ? $this->calculateTimeToFull($tankId, $rate) : null,
            'time_to_empty' => $rate < 0 ? $this->calculateTimeToEmpty($tankId, $rate) : null
        ];
    }

    /**
     * Calculate time to fill
     */
    private function calculateTimeToFull($tankId, $rate) {
        $tank = $this->getTankInfo($tankId);
        $remaining = $tank['capacity_total'] - $tank['current_level'];

        if ($rate <= 0) {
            return null;
        }

        return $remaining / $rate; // minutes
    }

    /**
     * Calculate time to empty
     */
    private function calculateTimeToEmpty($tankId, $rate) {
        $tank = $this->getTankInfo($tankId);

        if ($rate >= 0) {
            return null;
        }

        return $tank['current_level'] / abs($rate); // minutes
    }

    /**
     * Get tank information
     */
    private function getTankInfo($tankId) {
        $query = "SELECT t.*, a.asset_name, a.site_id, a.asset_metadata as tank_metadata
                  FROM scada_tank_levels t
                  JOIN scada_assets a ON t.tank_id = a.id
                  WHERE t.tank_id = ?";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $tankId);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result->fetch_assoc();
    }

    /**
     * Get all tanks for a site
     */
    public function getSiteTanks($siteId) {
        $query = "SELECT t.*, a.asset_name
                  FROM scada_tank_levels t
                  JOIN scada_assets a ON t.tank_id = a.id
                  WHERE a.site_id = ?
                  ORDER BY t.current_percentage ASC";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $siteId);
        $stmt->execute();
        $result = $stmt->get_result();

        $tanks = [];
        while ($row = $result->fetch_assoc()) {
            $tanks[] = $row;
        }

        return $tanks;
    }

    /**
     * Update tag value
     */
    private function updateTagValue($tagId, $value) {
        $query = "UPDATE scada_tags
                  SET current_value = ?,
                      last_update_time = NOW()
                  WHERE id = ?";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param("di", $value, $tagId);
        $stmt->execute();
    }

    /**
     * Send alarm notification
     */
    private function sendAlarmNotification($tank, $message, $severity) {
        // Integrate with notification system
        error_log("TANK ALARM [$severity]: $message");
    }

    /**
     * Get tank statistics
     */
    public function getTankStatistics($tankId, $periodHours = 24) {
        $query = "SELECT
                    MIN(CAST(value AS DECIMAL(10,2))) as min_level,
                    MAX(CAST(value AS DECIMAL(10,2))) as max_level,
                    AVG(CAST(value AS DECIMAL(10,2))) as avg_level,
                    STDDEV(CAST(value AS DECIMAL(10,2))) as std_dev
                  FROM scada_tag_history
                  WHERE tag_id = (SELECT level_tag_id FROM scada_tank_levels WHERE tank_id = ?)
                  AND timestamp >= DATE_SUB(NOW(), INTERVAL ? HOUR)";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ii", $tankId, $periodHours);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result->fetch_assoc();
    }
}
