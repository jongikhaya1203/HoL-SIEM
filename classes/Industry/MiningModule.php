<?php
/**
 * Mining Industry SCADA Module
 * Mine ventilation, gas detection, hoist control
 * Environmental monitoring, personnel tracking
 *
 * @author HoL Platform
 * @version 2.0
 */

namespace SCADA\Industry;

require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../SCADAMonitor.php';

use Database;
use SCADAMonitor;

class MiningModule {
    private $db;
    private $scadaMonitor;

    // Gas concentration limits (ppm)
    const CO_LIMIT = 50;
    const CH4_LIMIT = 5000; // 0.5%
    const O2_MIN = 19.5;
    const O2_MAX = 23.5;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->scadaMonitor = new SCADAMonitor();
    }

    /**
     * Monitor ventilation system
     */
    public function monitorVentilation($fanId) {
        try {
            $fan = $this->getFanInfo($fanId);

            $running = (bool)$this->getTagValue($fan['running_tag_id']);
            $speed = $this->getTagValue($fan['speed_tag_id']);
            $airflow = $this->getTagValue($fan['airflow_tag_id']);
            $motorCurrent = $this->getTagValue($fan['motor_current_tag_id']);

            // Check for faults
            $faultStatus = 'NORMAL';
            if ($running && $airflow < $fan['min_airflow']) {
                $faultStatus = 'LOW_AIRFLOW';
            }
            if ($motorCurrent > $fan['max_current']) {
                $faultStatus = 'OVERCURRENT';
            }

            return [
                'fan_id' => $fanId,
                'fan_name' => $fan['fan_name'],
                'running' => $running,
                'speed_rpm' => $speed,
                'airflow_cfm' => $airflow,
                'motor_current' => $motorCurrent,
                'status' => $faultStatus
            ];

        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Monitor gas detection
     */
    public function monitorGasDetection($sensorId) {
        try {
            $sensor = $this->getGasSensorInfo($sensorId);

            $ch4 = $this->getTagValue($sensor['ch4_tag_id']); // Methane
            $co = $this->getTagValue($sensor['co_tag_id']); // Carbon Monoxide
            $o2 = $this->getTagValue($sensor['o2_tag_id']); // Oxygen
            $co2 = $this->getTagValue($sensor['co2_tag_id']); // Carbon Dioxide

            // Check limits
            $alarmStatus = $this->checkGasLimits($ch4, $co, $o2);

            return [
                'sensor_id' => $sensorId,
                'location' => $sensor['location'],
                'ch4_ppm' => $ch4,
                'co_ppm' => $co,
                'o2_percent' => $o2,
                'co2_ppm' => $co2,
                'alarm_status' => $alarmStatus['status'],
                'alarm_reason' => $alarmStatus['reason'],
                'evacuation_required' => $alarmStatus['evacuate']
            ];

        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Check gas concentration limits
     */
    private function checkGasLimits($ch4, $co, $o2) {
        if ($ch4 > self::CH4_LIMIT) {
            return [
                'status' => 'CRITICAL',
                'reason' => 'Methane exceeds safe limit',
                'evacuate' => true
            ];
        }

        if ($co > self::CO_LIMIT) {
            return [
                'status' => 'CRITICAL',
                'reason' => 'Carbon monoxide exceeds safe limit',
                'evacuate' => true
            ];
        }

        if ($o2 < self::O2_MIN || $o2 > self::O2_MAX) {
            return [
                'status' => 'CRITICAL',
                'reason' => 'Oxygen level out of safe range',
                'evacuate' => true
            ];
        }

        return ['status' => 'NORMAL', 'reason' => '', 'evacuate' => false];
    }

    /**
     * Monitor hoist system
     */
    public function monitorHoist($hoistId) {
        try {
            $hoist = $this->getHoistInfo($hoistId);

            $position = $this->getTagValue($hoist['position_tag_id']);
            $speed = $this->getTagValue($hoist['speed_tag_id']);
            $load = $this->getTagValue($hoist['load_tag_id']);
            $brakeStatus = (bool)$this->getTagValue($hoist['brake_tag_id']);

            // Safety checks
            $safetyStatus = $this->checkHoistSafety($hoist, $speed, $load);

            return [
                'hoist_id' => $hoistId,
                'hoist_name' => $hoist['hoist_name'],
                'position_meters' => $position,
                'speed_mps' => $speed,
                'load_kg' => $load,
                'brake_applied' => $brakeStatus,
                'safety_status' => $safetyStatus['status'],
                'overload' => $load > $hoist['max_load']
            ];

        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Check hoist safety
     */
    private function checkHoistSafety($hoist, $speed, $load) {
        if ($load > $hoist['max_load']) {
            return ['status' => 'OVERLOAD'];
        }

        if ($speed > $hoist['max_speed']) {
            return ['status' => 'OVERSPEED'];
        }

        return ['status' => 'NORMAL'];
    }

    /**
     * Monitor personnel location
     */
    public function monitorPersonnel($tagId) {
        try {
            $tag = $this->getPersonnelTagInfo($tagId);

            return [
                'tag_id' => $tagId,
                'person_name' => $tag['person_name'],
                'last_known_location' => $tag['last_location'],
                'last_seen' => $tag['last_seen'],
                'emergency_status' => $tag['emergency_active'],
                'underground' => $tag['underground']
            ];

        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Monitor environmental conditions
     */
    public function monitorEnvironment($locationId) {
        try {
            $location = $this->getLocationInfo($locationId);

            return [
                'location_id' => $locationId,
                'location_name' => $location['location_name'],
                'temperature' => $this->getTagValue($location['temperature_tag_id']),
                'humidity' => $this->getTagValue($location['humidity_tag_id']),
                'pressure' => $this->getTagValue($location['pressure_tag_id']),
                'dust_concentration' => $this->getTagValue($location['dust_tag_id']),
                'status' => 'NORMAL'
            ];

        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    // Helper methods
    private function getFanInfo($id) {
        return [
            'fan_name' => 'FAN-' . $id,
            'running_tag_id' => 1,
            'speed_tag_id' => 2,
            'airflow_tag_id' => 3,
            'motor_current_tag_id' => 4,
            'min_airflow' => 1000,
            'max_current' => 100
        ];
    }

    private function getGasSensorInfo($id) {
        return [
            'location' => 'Level-' . $id,
            'ch4_tag_id' => 1,
            'co_tag_id' => 2,
            'o2_tag_id' => 3,
            'co2_tag_id' => 4
        ];
    }

    private function getHoistInfo($id) {
        return [
            'hoist_name' => 'HOIST-' . $id,
            'position_tag_id' => 1,
            'speed_tag_id' => 2,
            'load_tag_id' => 3,
            'brake_tag_id' => 4,
            'max_load' => 5000,
            'max_speed' => 10
        ];
    }

    private function getPersonnelTagInfo($id) {
        return [
            'person_name' => 'Worker-' . $id,
            'last_location' => 'Shaft-A',
            'last_seen' => date('Y-m-d H:i:s'),
            'emergency_active' => false,
            'underground' => true
        ];
    }

    private function getLocationInfo($id) {
        return [
            'location_name' => 'Zone-' . $id,
            'temperature_tag_id' => 1,
            'humidity_tag_id' => 2,
            'pressure_tag_id' => 3,
            'dust_tag_id' => 4
        ];
    }

    private function getTagValue($tagId) {
        return rand(0, 100);
    }
}
