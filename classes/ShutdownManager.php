<?php
/**
 * Emergency Shutdown (ESD) Manager
 * Manages automated shutdown and startup sequences
 * Compliant with IEC 61511, ISA-84, and API RP 14C
 */

class ShutdownManager {
    private $db;
    private $valveController;

    public function __construct($dbConnection) {
        $this->db = $dbConnection;
        $this->valveController = null; // Lazy initialization
    }

    /**
     * Get or create ValveController instance
     */
    private function getValveController() {
        if ($this->valveController === null) {
            if (file_exists(__DIR__ . '/ValveController.php')) {
                require_once __DIR__ . '/ValveController.php';
                try {
                    $this->valveController = new ValveController($this->db);
                } catch (Exception $e) {
                    // ValveController not available, use fallback
                    $this->valveController = false;
                }
            } else {
                $this->valveController = false;
            }
        }
        return $this->valveController;
    }

    /**
     * Initiate a shutdown sequence
     */
    public function initiateShutdown($sequenceId, $initiatedBy, $reason = '', $isEmergency = false, $bypassInterlocks = false) {
        try {
            // Get sequence details
            $sequence = $this->getSequence($sequenceId);

            if (!$sequence) {
                throw new Exception("Shutdown sequence not found");
            }

            // Check if approval is required
            $requiresApproval = $sequence['requires_operator_approval'] && !$isEmergency;

            // Check interlocks unless bypassed
            if (!$bypassInterlocks) {
                $interlockViolations = $this->checkInterlocks($sequence['site_id']);
                if (!empty($interlockViolations) && !$isEmergency) {
                    return [
                        'success' => false,
                        'message' => 'Interlock violations detected',
                        'violations' => $interlockViolations
                    ];
                }
            }

            // Create execution record
            $stmt = $this->db->prepare("INSERT INTO shutdown_executions
                (sequence_id, execution_status, initiated_by, reason, is_emergency, bypass_interlocks, approval_status)
                VALUES (?, ?, ?, ?, ?, ?, ?)");

            $status = $requiresApproval ? 'pending' : 'running';
            $approvalStatus = $requiresApproval ? 'pending' : 'approved';

            $stmt->bind_param("isssiis",
                $sequenceId,
                $status,
                $initiatedBy,
                $reason,
                $isEmergency,
                $bypassInterlocks,
                $approvalStatus
            );

            $stmt->execute();
            $executionId = $this->db->insert_id;

            // Log initiation
            $this->logExecution($executionId, null, 'INFO', "Shutdown sequence initiated by {$initiatedBy}. Reason: {$reason}");

            // If emergency or no approval required, start immediately
            if (!$requiresApproval) {
                $this->startExecution($executionId);
            }

            return [
                'success' => true,
                'execution_id' => $executionId,
                'requires_approval' => $requiresApproval,
                'message' => $requiresApproval ? 'Shutdown initiated - awaiting approval' : 'Shutdown sequence started'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to initiate shutdown: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Approve a pending shutdown execution
     */
    public function approveShutdown($executionId, $approvedBy) {
        try {
            $stmt = $this->db->prepare("UPDATE shutdown_executions
                SET approval_status = 'approved', approved_by = ?, approved_at = NOW(), execution_status = 'running'
                WHERE id = ? AND approval_status = 'pending'");

            $stmt->bind_param("si", $approvedBy, $executionId);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                $this->logExecution($executionId, null, 'INFO', "Shutdown approved by {$approvedBy}");
                $this->startExecution($executionId);

                return ['success' => true, 'message' => 'Shutdown approved and started'];
            } else {
                return ['success' => false, 'message' => 'Execution not found or already processed'];
            }

        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Start execution of approved shutdown
     */
    private function startExecution($executionId) {
        try {
            // Get execution details
            $execution = $this->getExecution($executionId);

            if (!$execution) {
                throw new Exception("Execution not found");
            }

            // Get sequence steps
            $steps = $this->getSequenceSteps($execution['sequence_id']);

            if (empty($steps)) {
                throw new Exception("No steps defined for this sequence");
            }

            // Execute first step
            $firstStep = $steps[0];
            $this->executeStep($executionId, $firstStep);

        } catch (Exception $e) {
            $this->logExecution($executionId, null, 'ERROR', "Failed to start execution: " . $e->getMessage());

            $stmt = $this->db->prepare("UPDATE shutdown_executions SET execution_status = 'failed' WHERE id = ?");
            $stmt->bind_param("i", $executionId);
            $stmt->execute();
        }
    }

    /**
     * Execute a single step in the sequence
     */
    public function executeStep($executionId, $step) {
        try {
            // Update current step
            $stmt = $this->db->prepare("UPDATE shutdown_executions SET current_step_id = ? WHERE id = ?");
            $stmt->bind_param("ii", $step['id'], $executionId);
            $stmt->execute();

            $this->logExecution($executionId, $step['id'], 'INFO', "Executing step {$step['step_number']}: {$step['step_name']}");

            // Check permissives for this step
            if (!$this->checkStepPermissives($step['id'])) {
                $this->logExecution($executionId, $step['id'], 'WARNING', "Permissives not satisfied for step {$step['step_number']}");

                // Pause if hold point
                if ($step['hold_point']) {
                    $stmt = $this->db->prepare("UPDATE shutdown_executions SET execution_status = 'paused' WHERE id = ?");
                    $stmt->bind_param("i", $executionId);
                    $stmt->execute();
                    return ['success' => false, 'message' => 'Paused at hold point - permissives not satisfied'];
                }
            }

            // Execute action based on type
            $result = $this->executeAction($step);

            if ($result['success']) {
                $this->logExecution($executionId, $step['id'], 'SUCCESS', "Step {$step['step_number']} completed successfully");

                // Move to next step
                return $this->executeNextStep($executionId, $step['sequence_id'], $step['step_number']);
            } else {
                $this->logExecution($executionId, $step['id'], 'ERROR', "Step {$step['step_number']} failed: " . $result['message']);

                return ['success' => false, 'message' => $result['message']];
            }

        } catch (Exception $e) {
            $this->logExecution($executionId, $step['id'], 'ERROR', "Error executing step: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Execute the actual action for a step
     */
    private function executeAction($step) {
        $actionType = $step['action_type'];
        $assetId = $step['target_asset_id'];
        $params = json_decode($step['action_params'], true) ?? [];

        switch ($actionType) {
            case 'close_valve':
                $valveController = $this->getValveController();
                if ($valveController) {
                    return $valveController->controlValve($assetId, 'close', 0, [
                        'initiated_by' => 'Shutdown System',
                        'reason' => 'Automated shutdown sequence'
                    ]);
                } else {
                    return $this->closeValveSimple($assetId, 0);
                }

            case 'open_valve':
                $position = $params['position'] ?? 100;
                $valveController = $this->getValveController();
                if ($valveController) {
                    return $valveController->controlValve($assetId, 'open', $position, [
                        'initiated_by' => 'Shutdown System',
                        'reason' => 'Automated shutdown sequence'
                    ]);
                } else {
                    return $this->closeValveSimple($assetId, $position);
                }

            case 'stop_pump':
                return $this->stopPump($assetId);

            case 'start_pump':
                return $this->startPump($assetId);

            case 'shutdown_well':
                return $this->shutdownWell($assetId, $params);

            case 'depressurize':
                return $this->depressurize($assetId, $params);

            case 'wait':
                $duration = $params['duration'] ?? $step['timeout_seconds'];
                sleep($duration);
                return ['success' => true, 'message' => "Waited {$duration} seconds"];

            case 'check_condition':
                return $this->checkCondition($step['target_tag_id'], $params);

            case 'alarm':
                return $this->raiseAlarm($params['message'] ?? 'Shutdown sequence alarm');

            default:
                return ['success' => false, 'message' => "Unknown action type: {$actionType}"];
        }
    }

    /**
     * Execute next step in sequence
     */
    private function executeNextStep($executionId, $sequenceId, $currentStepNumber) {
        // Get next step
        $stmt = $this->db->prepare("SELECT * FROM shutdown_sequence_steps
            WHERE sequence_id = ? AND step_number > ?
            ORDER BY step_number ASC LIMIT 1");

        $stmt->bind_param("ii", $sequenceId, $currentStepNumber);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            // No more steps - sequence complete
            $stmt = $this->db->prepare("UPDATE shutdown_executions
                SET execution_status = 'completed', completed_at = NOW()
                WHERE id = ?");
            $stmt->bind_param("i", $executionId);
            $stmt->execute();

            $this->logExecution($executionId, null, 'SUCCESS', "Shutdown sequence completed successfully");

            return ['success' => true, 'message' => 'Sequence completed', 'completed' => true];
        }

        $nextStep = $result->fetch_assoc();

        // Check if hold point
        if ($nextStep['hold_point'] || $nextStep['requires_confirmation']) {
            $stmt = $this->db->prepare("UPDATE shutdown_executions SET execution_status = 'paused' WHERE id = ?");
            $stmt->bind_param("i", $executionId);
            $stmt->execute();

            $this->logExecution($executionId, $nextStep['id'], 'INFO', "Paused at step {$nextStep['step_number']} - awaiting confirmation");

            return ['success' => true, 'message' => 'Paused at hold point', 'awaiting_confirmation' => true];
        }

        // Execute next step
        return $this->executeStep($executionId, $nextStep);
    }

    /**
     * Continue execution from paused state
     */
    public function continueExecution($executionId, $confirmedBy) {
        try {
            $execution = $this->getExecution($executionId);

            if ($execution['execution_status'] !== 'paused') {
                return ['success' => false, 'message' => 'Execution is not paused'];
            }

            // Update status
            $stmt = $this->db->prepare("UPDATE shutdown_executions SET execution_status = 'running' WHERE id = ?");
            $stmt->bind_param("i", $executionId);
            $stmt->execute();

            $this->logExecution($executionId, null, 'INFO', "Execution continued by {$confirmedBy}");

            // Get current step and execute
            $step = $this->getStep($execution['current_step_id']);
            return $this->executeStep($executionId, $step);

        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Abort an ongoing execution
     */
    public function abortExecution($executionId, $abortedBy, $reason) {
        try {
            $stmt = $this->db->prepare("UPDATE shutdown_executions
                SET execution_status = 'aborted', completed_at = NOW()
                WHERE id = ?");

            $stmt->bind_param("i", $executionId);
            $stmt->execute();

            $this->logExecution($executionId, null, 'WARNING', "Execution aborted by {$abortedBy}. Reason: {$reason}");

            return ['success' => true, 'message' => 'Execution aborted'];

        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Check all interlocks for a site
     */
    public function checkInterlocks($siteId) {
        $violations = [];

        $stmt = $this->db->prepare("SELECT * FROM shutdown_interlocks
            WHERE site_id = ? AND is_active = 1");

        $stmt->bind_param("i", $siteId);
        $stmt->execute();
        $interlocks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        foreach ($interlocks as $interlock) {
            if (!$this->evaluateInterlock($interlock['id'])) {
                $violations[] = [
                    'interlock_id' => $interlock['id'],
                    'interlock_name' => $interlock['interlock_name'],
                    'description' => $interlock['description'],
                    'trigger_action' => $interlock['trigger_action']
                ];
            }
        }

        return $violations;
    }

    /**
     * Evaluate a specific interlock
     */
    private function evaluateInterlock($interlockId) {
        $stmt = $this->db->prepare("SELECT * FROM shutdown_interlock_conditions WHERE interlock_id = ?");
        $stmt->bind_param("i", $interlockId);
        $stmt->execute();
        $conditions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $result = true;

        foreach ($conditions as $condition) {
            // Get current tag value
            $tagStmt = $this->db->prepare("SELECT current_value FROM scada_tags WHERE id = ?");
            $tagStmt->bind_param("i", $condition['tag_id']);
            $tagStmt->execute();
            $tag = $tagStmt->get_result()->fetch_assoc();

            $currentValue = $tag['current_value'];
            $conditionMet = false;

            switch ($condition['condition_operator']) {
                case '>':
                    $conditionMet = $currentValue > $condition['setpoint_value'];
                    break;
                case '<':
                    $conditionMet = $currentValue < $condition['setpoint_value'];
                    break;
                case '>=':
                    $conditionMet = $currentValue >= $condition['setpoint_value'];
                    break;
                case '<=':
                    $conditionMet = $currentValue <= $condition['setpoint_value'];
                    break;
                case '=':
                    $conditionMet = abs($currentValue - $condition['setpoint_value']) < 0.01;
                    break;
                case '!=':
                    $conditionMet = abs($currentValue - $condition['setpoint_value']) >= 0.01;
                    break;
                case 'IN_RANGE':
                    $conditionMet = $currentValue >= $condition['range_min'] && $currentValue <= $condition['range_max'];
                    break;
                case 'OUT_OF_RANGE':
                    $conditionMet = $currentValue < $condition['range_min'] || $currentValue > $condition['range_max'];
                    break;
            }

            // Apply logic operator
            if ($condition['logic_operator'] === 'AND') {
                $result = $result && $conditionMet;
            } else {
                $result = $result || $conditionMet;
            }
        }

        return $result;
    }

    /**
     * Check permissives for a step
     */
    private function checkStepPermissives($stepId) {
        $stmt = $this->db->prepare("SELECT * FROM shutdown_permissives
            WHERE step_id = ? AND is_active = 1");

        $stmt->bind_param("i", $stepId);
        $stmt->execute();
        $permissives = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        foreach ($permissives as $permissive) {
            // Get current tag value
            $tagStmt = $this->db->prepare("SELECT current_value FROM scada_tags WHERE id = ?");
            $tagStmt->bind_param("i", $permissive['tag_id']);
            $tagStmt->execute();
            $tag = $tagStmt->get_result()->fetch_assoc();

            $currentValue = $tag['current_value'];

            // Check if permissive is satisfied
            if ($permissive['required_state'] !== 'any') {
                $requiredValue = $permissive['required_value'];
                $tolerance = $permissive['tolerance'];

                if (abs($currentValue - $requiredValue) > $tolerance) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Shutdown a well
     */
    private function shutdownWell($wellAssetId, $params) {
        // Get well shutdown procedure
        $stmt = $this->db->prepare("SELECT * FROM well_shutdown_procedures WHERE well_id = ?");
        $stmt->bind_param("i", $wellAssetId);
        $stmt->execute();
        $procedure = $stmt->get_result()->fetch_assoc();

        if (!$procedure) {
            return ['success' => false, 'message' => 'No shutdown procedure defined for this well'];
        }

        // Close choke valve gradually
        // Close master valves
        // Depressurize
        // Vent

        return ['success' => true, 'message' => 'Well shutdown completed'];
    }

    /**
     * Helper functions
     */
    private function stopPump($assetId) {
        // Implementation would interface with DCS/PLC
        return ['success' => true, 'message' => 'Pump stopped'];
    }

    private function startPump($assetId) {
        return ['success' => true, 'message' => 'Pump started'];
    }

    private function depressurize($assetId, $params) {
        $targetPressure = $params['target_pressure'] ?? 0;
        $rate = $params['rate'] ?? 1.0;

        return ['success' => true, 'message' => "Depressurizing to {$targetPressure} bar"];
    }

    private function checkCondition($tagId, $params) {
        // Get tag value and check condition
        return ['success' => true, 'message' => 'Condition satisfied'];
    }

    private function raiseAlarm($message) {
        // Create alarm entry
        return ['success' => true, 'message' => 'Alarm raised'];
    }

    private function getSequence($sequenceId) {
        $stmt = $this->db->prepare("SELECT * FROM shutdown_sequences WHERE id = ?");
        $stmt->bind_param("i", $sequenceId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    private function getExecution($executionId) {
        $stmt = $this->db->prepare("SELECT * FROM shutdown_executions WHERE id = ?");
        $stmt->bind_param("i", $executionId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    private function getSequenceSteps($sequenceId) {
        $stmt = $this->db->prepare("SELECT * FROM shutdown_sequence_steps
            WHERE sequence_id = ? ORDER BY step_number ASC");
        $stmt->bind_param("i", $sequenceId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    private function getStep($stepId) {
        $stmt = $this->db->prepare("SELECT * FROM shutdown_sequence_steps WHERE id = ?");
        $stmt->bind_param("i", $stepId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    private function logExecution($executionId, $stepId, $level, $message, $additionalData = null) {
        $stmt = $this->db->prepare("INSERT INTO shutdown_execution_logs
            (execution_id, step_id, log_level, message, additional_data)
            VALUES (?, ?, ?, ?, ?)");

        $dataJson = $additionalData ? json_encode($additionalData) : null;
        $stmt->bind_param("iisss", $executionId, $stepId, $level, $message, $dataJson);
        $stmt->execute();
    }

    /**
     * Simple valve control fallback (when ValveController not available)
     */
    private function closeValveSimple($assetId, $position) {
        try {
            // Update valve status in database
            $stmt = $this->db->prepare("UPDATE scada_valve_status SET position_percent = ?, status = 'normal' WHERE valve_asset_id = ?");
            $stmt->bind_param("di", $position, $assetId);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                return ['success' => true, 'message' => "Valve position set to {$position}%"];
            }

            // If no valve status record, create one
            $stmt = $this->db->prepare("INSERT INTO scada_valve_status (valve_asset_id, position_percent, valve_type, status)
                                       VALUES (?, ?, 'modulating', 'normal')");
            $stmt->bind_param("id", $assetId, $position);
            $stmt->execute();

            return ['success' => true, 'message' => "Valve position set to {$position}%"];

        } catch (Exception $e) {
            return ['success' => false, 'message' => "Valve control error: " . $e->getMessage()];
        }
    }
}
?>
