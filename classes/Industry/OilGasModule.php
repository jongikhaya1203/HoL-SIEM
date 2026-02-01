<?php
/**
 * Oil & Gas Industry SCADA Module
 * Pipeline monitoring, custody transfer, flow measurement
 * Leak detection, pressure management, LACT units
 *
 * @author HoL Platform
 * @version 2.0
 */

namespace SCADA\Industry;

require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../SCADAMonitor.php';
require_once __DIR__ . '/../TankMonitor.php';

use Database;
use SCADAMonitor;
use TankMonitor;

class OilGasModule {
    private $db;
    private $scadaMonitor;
    private $tankMonitor;

    // Pipeline monitoring parameters
    private $leakDetectionThreshold = 0.05; // 5% flow imbalance
    private $pressureVarianceThreshold = 10; // PSI

    public function __construct() {
        $this->db = Database::getInstance();
        $this->scadaMonitor = new SCADAMonitor();
        $this->tankMonitor = new TankMonitor();
    }

    /**
     * Monitor pipeline segment
     */
    public function monitorPipeline($pipelineId) {
        try {
            $pipeline = $this->getPipelineInfo($pipelineId);

            // Get flow measurements
            $flowIn = $this->getTagValue($pipeline['flow_in_tag_id']);
            $flowOut = $this->getTagValue($pipeline['flow_out_tag_id']);

            // Get pressure measurements
            $pressureIn = $this->getTagValue($pipeline['pressure_in_tag_id']);
            $pressureOut = $this->getTagValue($pipeline['pressure_out_tag_id']);

            // Get temperature
            $temperature = $this->getTagValue($pipeline['temperature_tag_id']);

            // Leak detection
            $leakStatus = $this->detectLeak($flowIn, $flowOut);

            // Pressure drop analysis
            $pressureDrop = $pressureIn - $pressureOut;
            $expectedDrop = $this->calculateExpectedPressureDrop($pipeline, $flowIn);

            // Anomaly detection
            $anomaly = abs($pressureDrop - $expectedDrop) > $this->pressureVarianceThreshold;

            return [
                'pipeline_id' => $pipelineId,
                'flow_in' => $flowIn,
                'flow_out' => $flowOut,
                'pressure_in' => $pressureIn,
                'pressure_out' => $pressureOut,
                'pressure_drop' => $pressureDrop,
                'temperature' => $temperature,
                'leak_detected' => $leakStatus['leak_detected'],
                'leak_severity' => $leakStatus['severity'],
                'anomaly_detected' => $anomaly,
                'status' => $leakStatus['leak_detected'] ? 'ALARM' : 'NORMAL'
            ];

        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Detect pipeline leak
     */
    private function detectLeak($flowIn, $flowOut) {
        if ($flowIn <= 0) {
            return ['leak_detected' => false, 'severity' => 'none'];
        }

        $imbalance = abs($flowIn - $flowOut) / $flowIn;

        if ($imbalance > $this->leakDetectionThreshold) {
            $severity = 'low';
            if ($imbalance > 0.10) {
                $severity = 'high';
            }
            if ($imbalance > 0.20) {
                $severity = 'critical';
            }

            return [
                'leak_detected' => true,
                'severity' => $severity,
                'imbalance_percent' => $imbalance * 100,
                'estimated_leak_rate' => $flowIn - $flowOut
            ];
        }

        return ['leak_detected' => false, 'severity' => 'none'];
    }

    /**
     * Calculate expected pressure drop
     */
    private function calculateExpectedPressureDrop($pipeline, $flowRate) {
        // Darcy-Weisbach equation simplified
        $length = $pipeline['length_miles'] ?? 1;
        $diameter = $pipeline['diameter_inches'] ?? 12;
        $friction = $pipeline['friction_factor'] ?? 0.02;

        // Simplified calculation
        $drop = $friction * ($length / $diameter) * pow($flowRate, 2) * 0.001;

        return $drop;
    }

    /**
     * Monitor LACT (Lease Automatic Custody Transfer) Unit
     */
    public function monitorLACT($lactId) {
        try {
            $lact = $this->getLACTInfo($lactId);

            return [
                'lact_id' => $lactId,
                'gross_volume' => $this->getTagValue($lact['gross_volume_tag_id']),
                'net_volume' => $this->getTagValue($lact['net_volume_tag_id']),
                'bsw_percent' => $this->getTagValue($lact['bsw_tag_id']), // Basic Sediment & Water
                'api_gravity' => $this->getTagValue($lact['api_gravity_tag_id']),
                'temperature' => $this->getTagValue($lact['temperature_tag_id']),
                'pressure' => $this->getTagValue($lact['pressure_tag_id']),
                'flow_rate' => $this->getTagValue($lact['flow_rate_tag_id']),
                'prover_status' => $this->getTagValue($lact['prover_status_tag_id']),
                'status' => 'operational'
            ];

        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Monitor wellhead
     */
    public function monitorWellhead($wellId) {
        try {
            $well = $this->getWellInfo($wellId);

            $casingPressure = $this->getTagValue($well['casing_pressure_tag_id']);
            $tubingPressure = $this->getTagValue($well['tubing_pressure_tag_id']);
            $flowRate = $this->getTagValue($well['flow_rate_tag_id']);
            $temperature = $this->getTagValue($well['temperature_tag_id']);

            // Check safety limits
            $safetyStatus = $this->checkWellheadSafety($well, $casingPressure, $tubingPressure);

            return [
                'well_id' => $wellId,
                'well_name' => $well['well_name'],
                'casing_pressure' => $casingPressure,
                'tubing_pressure' => $tubingPressure,
                'flow_rate' => $flowRate,
                'temperature' => $temperature,
                'safety_status' => $safetyStatus['status'],
                'production_status' => $flowRate > 0 ? 'producing' : 'shut_in'
            ];

        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Check wellhead safety
     */
    private function checkWellheadSafety($well, $casingPressure, $tubingPressure) {
        $maxCasingPressure = $well['max_casing_pressure'] ?? 3000;
        $maxTubingPressure = $well['max_tubing_pressure'] ?? 2000;

        if ($casingPressure > $maxCasingPressure || $tubingPressure > $maxTubingPressure) {
            return [
                'status' => 'ALARM',
                'reason' => 'Pressure exceeds safe limits'
            ];
        }

        return ['status' => 'NORMAL'];
    }

    /**
     * Monitor separator
     */
    public function monitorSeparator($separatorId) {
        try {
            $separator = $this->getSeparatorInfo($separatorId);

            return [
                'separator_id' => $separatorId,
                'oil_level' => $this->getTagValue($separator['oil_level_tag_id']),
                'water_level' => $this->getTagValue($separator['water_level_tag_id']),
                'gas_pressure' => $this->getTagValue($separator['gas_pressure_tag_id']),
                'temperature' => $this->getTagValue($separator['temperature_tag_id']),
                'oil_flow_out' => $this->getTagValue($separator['oil_flow_tag_id']),
                'gas_flow_out' => $this->getTagValue($separator['gas_flow_tag_id']),
                'water_flow_out' => $this->getTagValue($separator['water_flow_tag_id']),
                'status' => 'operational'
            ];

        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Calculate production metrics
     */
    public function calculateProduction($siteId, $periodHours = 24) {
        $query = "SELECT
                    SUM(CAST(value AS DECIMAL(15,2))) as total_production
                  FROM scada_tag_history h
                  JOIN scada_tags t ON h.tag_id = t.id
                  WHERE t.site_id = ?
                  AND t.tag_name LIKE '%PRODUCTION%'
                  AND h.timestamp >= DATE_SUB(NOW(), INTERVAL ? HOUR)";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ii", $siteId, $periodHours);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        return [
            'total_production_bbls' => $result['total_production'] ?? 0,
            'period_hours' => $periodHours,
            'average_rate_bpd' => ($result['total_production'] ?? 0) * (24 / $periodHours)
        ];
    }

    // Helper methods
    private function getPipelineInfo($pipelineId) {
        $query = "SELECT * FROM oil_gas_pipelines WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $pipelineId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    private function getLACTInfo($lactId) {
        $query = "SELECT * FROM oil_gas_lact_units WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $lactId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    private function getWellInfo($wellId) {
        $query = "SELECT * FROM oil_gas_wellheads WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $wellId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    private function getSeparatorInfo($separatorId) {
        $query = "SELECT * FROM oil_gas_separators WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $separatorId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    private function getTagValue($tagId) {
        if (!$tagId) return null;
        $query = "SELECT current_value FROM scada_tags WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $tagId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['current_value'] ?? null;
    }
}
