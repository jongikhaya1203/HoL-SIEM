<?php
/**
 * Rail System SCADA Module
 * Railway signaling, track circuits, points/switches control
 * Train detection, interlocking systems, platform monitoring
 *
 * @author HoL Platform
 * @version 2.0
 */

namespace SCADA\Industry;

require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../SCADAMonitor.php';

use Database;
use SCADAMonitor;

class RailModule {
    private $db;
    private $scadaMonitor;

    // Signal aspects
    const SIGNAL_RED = 'red';
    const SIGNAL_YELLOW = 'yellow';
    const SIGNAL_GREEN = 'green';
    const SIGNAL_DOUBLE_YELLOW = 'double_yellow';

    public function __construct() {
        $this->db = Database::getInstance();
        $this->scadaMonitor = new SCADAMonitor();
    }

    /**
     * Monitor track circuit
     */
    public function monitorTrackCircuit($trackCircuitId) {
        try {
            $circuit = $this->getTrackCircuitInfo($trackCircuitId);

            $occupied = (bool)$this->getTagValue($circuit['occupied_tag_id']);
            $voltage = $this->getTagValue($circuit['voltage_tag_id']);
            $current = $this->getTagValue($circuit['current_tag_id']);

            // Check for faults
            $faultStatus = $this->checkTrackCircuitFault($voltage, $current, $occupied);

            return [
                'circuit_id' => $trackCircuitId,
                'track_section' => $circuit['track_section'],
                'occupied' => $occupied,
                'voltage' => $voltage,
                'current' => $current,
                'fault_detected' => $faultStatus['fault'],
                'fault_type' => $faultStatus['type'],
                'status' => $faultStatus['fault'] ? 'FAULT' : 'NORMAL'
            ];

        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Check track circuit fault
     */
    private function checkTrackCircuitFault($voltage, $current, $occupied) {
        // Normal operating voltage: 8-12V DC
        // Occupied: low voltage due to train shunt

        if ($voltage < 2 && !$occupied) {
            return ['fault' => true, 'type' => 'short_circuit'];
        }

        if ($voltage > 14) {
            return ['fault' => true, 'type' => 'open_circuit'];
        }

        if ($current < 10 && !$occupied) {
            return ['fault' => true, 'type' => 'broken_rail'];
        }

        return ['fault' => false, 'type' => 'none'];
    }

    /**
     * Monitor signal
     */
    public function monitorSignal($signalId) {
        try {
            $signal = $this->getSignalInfo($signalId);

            $currentAspect = $this->getTagValue($signal['aspect_tag_id']);
            $commandedAspect = $this->getTagValue($signal['command_tag_id']);

            // Check lamp status
            $redLamp = (bool)$this->getTagValue($signal['red_lamp_tag_id']);
            $yellowLamp = (bool)$this->getTagValue($signal['yellow_lamp_tag_id']);
            $greenLamp = (bool)$this->getTagValue($signal['green_lamp_tag_id']);

            // Verify signal display
            $displayCorrect = $this->verifySignalDisplay($currentAspect, $redLamp, $yellowLamp, $greenLamp);

            return [
                'signal_id' => $signalId,
                'signal_name' => $signal['signal_name'],
                'current_aspect' => $this->getAspectName($currentAspect),
                'commanded_aspect' => $this->getAspectName($commandedAspect),
                'red_lamp' => $redLamp,
                'yellow_lamp' => $yellowLamp,
                'green_lamp' => $greenLamp,
                'display_correct' => $displayCorrect,
                'status' => $displayCorrect ? 'NORMAL' : 'FAULT'
            ];

        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Verify signal display
     */
    private function verifySignalDisplay($aspect, $red, $yellow, $green) {
        switch ($aspect) {
            case 0: // Red
                return $red && !$yellow && !$green;
            case 1: // Yellow
                return !$red && $yellow && !$green;
            case 2: // Green
                return !$red && !$yellow && $green;
            case 3: // Double Yellow
                return !$red && $yellow && !$green; // Simplified
            default:
                return false;
        }
    }

    /**
     * Monitor point (switch)
     */
    public function monitorPoint($pointId) {
        try {
            $point = $this->getPointInfo($pointId);

            $position = $this->getTagValue($point['position_tag_id']);
            $locked = (bool)$this->getTagValue($point['locked_tag_id']);
            $detection = $this->getTagValue($point['detection_tag_id']);

            // Position: 0 = Normal, 1 = Reverse
            $positionName = ($position == 0) ? 'normal' : 'reverse';

            // Check detection matches position
            $detectionCorrect = ($position == $detection);

            return [
                'point_id' => $pointId,
                'point_name' => $point['point_name'],
                'position' => $positionName,
                'locked' => $locked,
                'detection_correct' => $detectionCorrect,
                'status' => ($locked && $detectionCorrect) ? 'NORMAL' : 'FAULT'
            ];

        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Monitor interlocking system
     */
    public function monitorInterlocking($interlockingId) {
        try {
            $interlocking = $this->getInterlockingInfo($interlockingId);

            // Get all routes in this interlocking
            $routes = $this->getRoutes($interlockingId);

            $activeRoute = null;
            $conflictDetected = false;

            foreach ($routes as $route) {
                if ($this->getTagValue($route['active_tag_id'])) {
                    $activeRoute = $route;
                    break;
                }
            }

            return [
                'interlocking_id' => $interlockingId,
                'interlocking_name' => $interlocking['interlocking_name'],
                'active_route' => $activeRoute['route_name'] ?? 'None',
                'conflict_detected' => $conflictDetected,
                'total_routes' => count($routes),
                'status' => 'NORMAL'
            ];

        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Monitor platform
     */
    public function monitorPlatform($platformId) {
        try {
            $platform = $this->getPlatformInfo($platformId);

            return [
                'platform_id' => $platformId,
                'platform_name' => $platform['platform_name'],
                'train_present' => (bool)$this->getTagValue($platform['train_present_tag_id']),
                'door_status' => $this->getTagValue($platform['door_status_tag_id']),
                'passenger_count' => $this->getTagValue($platform['passenger_count_tag_id']),
                'emergency_alarm' => (bool)$this->getTagValue($platform['emergency_alarm_tag_id']),
                'status' => 'NORMAL'
            ];

        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Monitor axle counter
     */
    public function monitorAxleCounter($counterId) {
        try {
            $counter = $this->getAxleCounterInfo($counterId);

            $inCount = $this->getTagValue($counter['in_count_tag_id']);
            $outCount = $this->getTagValue($counter['out_count_tag_id']);

            $occupied = ($inCount != $outCount);

            return [
                'counter_id' => $counterId,
                'section_name' => $counter['section_name'],
                'in_count' => $inCount,
                'out_count' => $outCount,
                'occupied' => $occupied,
                'status' => 'NORMAL'
            ];

        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    // Helper methods
    private function getAspectName($aspect) {
        $aspects = [
            0 => 'Red',
            1 => 'Yellow',
            2 => 'Green',
            3 => 'Double Yellow'
        ];
        return $aspects[$aspect] ?? 'Unknown';
    }

    private function getTrackCircuitInfo($id) {
        return ['track_section' => 'TC-' . $id, 'occupied_tag_id' => 1, 'voltage_tag_id' => 2, 'current_tag_id' => 3];
    }

    private function getSignalInfo($id) {
        return ['signal_name' => 'SIG-' . $id, 'aspect_tag_id' => 1, 'command_tag_id' => 2, 'red_lamp_tag_id' => 3, 'yellow_lamp_tag_id' => 4, 'green_lamp_tag_id' => 5];
    }

    private function getPointInfo($id) {
        return ['point_name' => 'PT-' . $id, 'position_tag_id' => 1, 'locked_tag_id' => 2, 'detection_tag_id' => 3];
    }

    private function getInterlockingInfo($id) {
        return ['interlocking_name' => 'IXL-' . $id];
    }

    private function getPlatformInfo($id) {
        return ['platform_name' => 'PLT-' . $id, 'train_present_tag_id' => 1, 'door_status_tag_id' => 2, 'passenger_count_tag_id' => 3, 'emergency_alarm_tag_id' => 4];
    }

    private function getAxleCounterInfo($id) {
        return ['section_name' => 'AC-' . $id, 'in_count_tag_id' => 1, 'out_count_tag_id' => 2];
    }

    private function getRoutes($interlockingId) {
        return [];
    }

    private function getTagValue($tagId) {
        if (!$tagId) return null;
        return rand(0, 100); // Simulated
    }
}
