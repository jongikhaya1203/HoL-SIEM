<?php
/**
 * Rail Control System
 * Comprehensive railway signaling, interlocking, and train control
 */

class RailControlSystem {
    private $db;

    public function __construct($dbConnection) {
        $this->db = $dbConnection;
    }

    /**
     * Get all rail system data
     */
    public function getSystemStatus($siteId) {
        return [
            'track_circuits' => $this->getTrackCircuits($siteId),
            'signals' => $this->getSignals($siteId),
            'points' => $this->getPoints($siteId),
            'trains' => $this->getTrains($siteId),
            'platforms' => $this->getPlatforms($siteId),
            'level_crossings' => $this->getLevelCrossings($siteId),
            'emergency_systems' => $this->getEmergencySystems($siteId)
        ];
    }

    /**
     * Get track circuits
     */
    public function getTrackCircuits($siteId) {
        $stmt = $this->db->prepare("SELECT tc.*, ts.section_name
            FROM rail_track_circuits_enhanced tc
            LEFT JOIN rail_track_sections ts ON tc.section_id = ts.id
            WHERE tc.site_id = ?
            ORDER BY tc.circuit_code");
        $stmt->bind_param("i", $siteId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get signals
     */
    public function getSignals($siteId) {
        $stmt = $this->db->prepare("SELECT s.*, ts.section_name
            FROM rail_signals_enhanced s
            LEFT JOIN rail_track_sections ts ON s.section_id = ts.id
            WHERE s.site_id = ?
            ORDER BY s.signal_code");
        $stmt->bind_param("i", $siteId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Change signal aspect
     */
    public function changeSignalAspect($signalId, $newAspect, $operator = 'System') {
        try {
            // Get current signal state
            $stmt = $this->db->prepare("SELECT * FROM rail_signals_enhanced WHERE id = ?");
            $stmt->bind_param("i", $signalId);
            $stmt->execute();
            $signal = $stmt->get_result()->fetch_assoc();

            if (!$signal) {
                return ['success' => false, 'message' => 'Signal not found'];
            }

            // Check if change is allowed (interlocking logic)
            $interlockCheck = $this->checkSignalInterlocking($signalId, $newAspect);
            if (!$interlockCheck['allowed']) {
                return ['success' => false, 'message' => $interlockCheck['reason']];
            }

            // Update signal
            $stmt = $this->db->prepare("UPDATE rail_signals_enhanced
                SET commanded_aspect = ?, current_aspect = ?, last_aspect_change = NOW()
                WHERE id = ?");
            $stmt->bind_param("ssi", $newAspect, $newAspect, $signalId);
            $stmt->execute();

            // Log event
            $this->logEvent($signal['site_id'], 'signal_change', 'normal', 'signal', $signalId,
                "Signal {$signal['signal_code']} changed from {$signal['current_aspect']} to {$newAspect}", $operator);

            return ['success' => true, 'message' => "Signal aspect changed to {$newAspect}"];

        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Check signal interlocking
     */
    private function checkSignalInterlocking($signalId, $newAspect) {
        // Get signal details
        $stmt = $this->db->prepare("SELECT * FROM rail_signals_enhanced WHERE id = ?");
        $stmt->bind_param("i", $signalId);
        $stmt->execute();
        $signal = $stmt->get_result()->fetch_assoc();

        // Check track circuit ahead is clear
        if ($newAspect === 'green' || $newAspect === 'yellow') {
            $stmt = $this->db->prepare("SELECT * FROM rail_track_circuits_enhanced
                WHERE section_id = ? AND occupancy_status = 'occupied'");
            $stmt->bind_param("i", $signal['section_id']);
            $stmt->execute();
            $occupied = $stmt->get_result()->fetch_assoc();

            if ($occupied) {
                return ['allowed' => false, 'reason' => 'Track circuit occupied ahead'];
            }
        }

        return ['allowed' => true, 'reason' => ''];
    }

    /**
     * Get points
     */
    public function getPoints($siteId) {
        $stmt = $this->db->prepare("SELECT p.*, ts.section_name
            FROM rail_points_enhanced p
            LEFT JOIN rail_track_sections ts ON p.section_id = ts.id
            WHERE p.site_id = ?
            ORDER BY p.point_code");
        $stmt->bind_param("i", $siteId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Move point (switch)
     */
    public function movePoint($pointId, $newPosition, $operator = 'System') {
        try {
            // Get current point state
            $stmt = $this->db->prepare("SELECT * FROM rail_points_enhanced WHERE id = ?");
            $stmt->bind_param("i", $pointId);
            $stmt->execute();
            $point = $stmt->get_result()->fetch_assoc();

            if (!$point) {
                return ['success' => false, 'message' => 'Point not found'];
            }

            // Check if point is locked
            if ($point['is_locked']) {
                return ['success' => false, 'message' => 'Point is locked in route'];
            }

            // Check if track circuit is clear
            $stmt = $this->db->prepare("SELECT * FROM rail_track_circuits_enhanced
                WHERE section_id = ? AND occupancy_status = 'occupied'");
            $stmt->bind_param("i", $point['section_id']);
            $stmt->execute();
            $occupied = $stmt->get_result()->fetch_assoc();

            if ($occupied) {
                return ['success' => false, 'message' => 'Cannot move point - track occupied'];
            }

            // Update point position
            $detectionStatus = $newPosition === 'normal' ? 'normal_detected' : 'reverse_detected';
            $stmt = $this->db->prepare("UPDATE rail_points_enhanced
                SET commanded_position = ?, current_position = ?, detection_status = ?, last_movement = NOW()
                WHERE id = ?");
            $stmt->bind_param("sssi", $newPosition, $newPosition, $detectionStatus, $pointId);
            $stmt->execute();

            // Log event
            $this->logEvent($point['site_id'], 'point_movement', 'normal', 'point', $pointId,
                "Point {$point['point_code']} moved to {$newPosition} position", $operator);

            return ['success' => true, 'message' => "Point moved to {$newPosition} position"];

        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get trains
     */
    public function getTrains($siteId) {
        $stmt = $this->db->prepare("SELECT t.*, ts.section_name as current_section_name
            FROM rail_trains t
            LEFT JOIN rail_track_sections ts ON t.current_section_id = ts.id
            WHERE t.site_id = ?
            ORDER BY t.train_number");
        $stmt->bind_param("i", $siteId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Update train position
     */
    public function updateTrainPosition($trainId, $newSectionId, $speed) {
        try {
            $stmt = $this->db->prepare("UPDATE rail_trains
                SET current_section_id = ?, current_speed_kmh = ?, last_update = NOW()
                WHERE id = ?");
            $stmt->bind_param("iii", $newSectionId, $speed, $trainId);
            $stmt->execute();

            // Update track circuit occupancy
            $stmt = $this->db->prepare("UPDATE rail_track_circuits_enhanced
                SET is_occupied = TRUE, occupancy_status = 'occupied', last_occupied_time = NOW()
                WHERE section_id = ?");
            $stmt->bind_param("i", $newSectionId);
            $stmt->execute();

            return ['success' => true, 'message' => 'Train position updated'];

        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get platforms
     */
    public function getPlatforms($siteId) {
        $stmt = $this->db->prepare("SELECT p.*, ts.section_name, t.train_number
            FROM rail_platforms p
            LEFT JOIN rail_track_sections ts ON p.section_id = ts.id
            LEFT JOIN rail_trains t ON p.current_train_id = t.id
            WHERE p.site_id = ?
            ORDER BY p.platform_number");
        $stmt->bind_param("i", $siteId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Control platform doors
     */
    public function controlPlatformDoors($platformId, $action, $operator = 'System') {
        try {
            $validActions = ['open', 'close'];
            if (!in_array($action, $validActions)) {
                return ['success' => false, 'message' => 'Invalid action'];
            }

            $newStatus = $action === 'open' ? 'opening' : 'closing';

            $stmt = $this->db->prepare("UPDATE rail_platforms
                SET doors_status = ?
                WHERE id = ?");
            $stmt->bind_param("si", $newStatus, $platformId);
            $stmt->execute();

            // Simulate completion after 3 seconds
            sleep(1);
            $finalStatus = $action === 'open' ? 'open' : 'closed';
            $stmt = $this->db->prepare("UPDATE rail_platforms SET doors_status = ? WHERE id = ?");
            $stmt->bind_param("si", $finalStatus, $platformId);
            $stmt->execute();

            return ['success' => true, 'message' => "Platform doors {$action}ed"];

        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get level crossings
     */
    public function getLevelCrossings($siteId) {
        $stmt = $this->db->prepare("SELECT lc.*, ts.section_name
            FROM rail_level_crossings lc
            LEFT JOIN rail_track_sections ts ON lc.section_id = ts.id
            WHERE lc.site_id = ?");
        $stmt->bind_param("i", $siteId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Control level crossing
     */
    public function controlLevelCrossing($crossingId, $action, $operator = 'System') {
        try {
            $validActions = ['lower', 'raise'];
            if (!in_array($action, $validActions)) {
                return ['success' => false, 'message' => 'Invalid action'];
            }

            if ($action === 'lower') {
                // Activate warning lights and bells
                $stmt = $this->db->prepare("UPDATE rail_level_crossings
                    SET lights_status = 'flashing', bells_status = 'ringing', barrier_status = 'lowering'
                    WHERE id = ?");
                $stmt->bind_param("i", $crossingId);
                $stmt->execute();

                // Simulate barrier lowering
                sleep(1);
                $stmt = $this->db->prepare("UPDATE rail_level_crossings
                    SET barrier_status = 'lowered', barrier_lower_time = NOW()
                    WHERE id = ?");
                $stmt->bind_param("i", $crossingId);
                $stmt->execute();

            } else {
                // Raise barriers
                $stmt = $this->db->prepare("UPDATE rail_level_crossings
                    SET barrier_status = 'raising'
                    WHERE id = ?");
                $stmt->bind_param("i", $crossingId);
                $stmt->execute();

                // Simulate barrier raising
                sleep(1);
                $stmt = $this->db->prepare("UPDATE rail_level_crossings
                    SET barrier_status = 'raised', lights_status = 'off', bells_status = 'off', barrier_raise_time = NOW()
                    WHERE id = ?");
                $stmt->bind_param("i", $crossingId);
                $stmt->execute();
            }

            return ['success' => true, 'message' => "Barrier {$action}ed"];

        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get emergency systems
     */
    public function getEmergencySystems($siteId) {
        $stmt = $this->db->prepare("SELECT * FROM rail_emergency_systems WHERE site_id = ?");
        $stmt->bind_param("i", $siteId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Activate emergency stop
     */
    public function activateEmergencyStop($systemId, $operator = 'Operator') {
        try {
            $stmt = $this->db->prepare("UPDATE rail_emergency_systems
                SET is_activated = TRUE, activated_time = NOW(), activated_by = ?, status = 'activated', reset_required = TRUE
                WHERE id = ?");
            $stmt->bind_param("si", $operator, $systemId);
            $stmt->execute();

            // Set all signals to red
            $stmt = $this->db->prepare("SELECT site_id FROM rail_emergency_systems WHERE id = ?");
            $stmt->bind_param("i", $systemId);
            $stmt->execute();
            $system = $stmt->get_result()->fetch_assoc();

            $stmt = $this->db->prepare("UPDATE rail_signals_enhanced
                SET current_aspect = 'red', commanded_aspect = 'red'
                WHERE site_id = ?");
            $stmt->bind_param("i", $system['site_id']);
            $stmt->execute();

            return ['success' => true, 'message' => 'Emergency stop activated - All signals set to red'];

        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Reset emergency stop
     */
    public function resetEmergencyStop($systemId, $operator = 'Supervisor') {
        try {
            $stmt = $this->db->prepare("UPDATE rail_emergency_systems
                SET is_activated = FALSE, status = 'normal', reset_required = FALSE
                WHERE id = ?");
            $stmt->bind_param("i", $systemId);
            $stmt->execute();

            return ['success' => true, 'message' => 'Emergency stop reset - System returned to normal'];

        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get recent events
     */
    public function getRecentEvents($siteId, $limit = 50) {
        $stmt = $this->db->prepare("SELECT * FROM rail_event_log
            WHERE site_id = ?
            ORDER BY event_time DESC
            LIMIT ?");
        $stmt->bind_param("ii", $siteId, $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Log event
     */
    private function logEvent($siteId, $eventType, $category, $entityType, $entityId, $description, $operator = null) {
        $stmt = $this->db->prepare("INSERT INTO rail_event_log
            (site_id, event_type, event_category, entity_type, entity_id, description, operator)
            VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssis", $siteId, $eventType, $category, $entityType, $entityId, $description, $operator);
        $stmt->execute();
    }

    /**
     * Get system statistics
     */
    public function getStatistics($siteId) {
        $stats = [];

        // Track circuits
        $result = $conn->query("SELECT
            COUNT(*) as total,
            SUM(CASE WHEN occupancy_status = 'occupied' THEN 1 ELSE 0 END) as occupied,
            SUM(CASE WHEN occupancy_status = 'fault' THEN 1 ELSE 0 END) as faults
            FROM rail_track_circuits_enhanced WHERE site_id = {$siteId}");
        $stats['track_circuits'] = $result->fetch_assoc();

        // Signals
        $result = $conn->query("SELECT
            COUNT(*) as total,
            SUM(CASE WHEN current_aspect = 'red' THEN 1 ELSE 0 END) as red,
            SUM(CASE WHEN current_aspect = 'green' THEN 1 ELSE 0 END) as green
            FROM rail_signals_enhanced WHERE site_id = {$siteId}");
        $stats['signals'] = $result->fetch_assoc();

        // Trains
        $result = $conn->query("SELECT
            COUNT(*) as total,
            SUM(CASE WHEN status = 'at_platform' THEN 1 ELSE 0 END) as at_platform,
            SUM(CASE WHEN delay_minutes > 5 THEN 1 ELSE 0 END) as delayed
            FROM rail_trains WHERE site_id = {$siteId}");
        $stats['trains'] = $result->fetch_assoc();

        return $stats;
    }
}
?>
