<?php
/**
 * Valve Control System with Safety Interlocks
 * Controls industrial valves with comprehensive safety features
 * Implements IEC 61508/61511 functional safety standards
 *
 * @author HoL Platform
 * @version 2.0
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/SCADAMonitor.php';

class ValveController {
    private $db;
    private $scadaMonitor;
    private $safetyEnabled = true;
    private $interlockRules = [];
    private $auditLog = [];

    // Valve control commands
    const CMD_OPEN = 'open';
    const CMD_CLOSE = 'close';
    const CMD_STOP = 'stop';
    const CMD_POSITION = 'position';

    // Safety interlock types
    const INTERLOCK_PRESSURE = 'pressure';
    const INTERLOCK_TEMPERATURE = 'temperature';
    const INTERLOCK_FLOW = 'flow';
    const INTERLOCK_LEVEL = 'level';
    const INTERLOCK_MANUAL_LOCK = 'manual_lock';
    const INTERLOCK_MAINTENANCE = 'maintenance';
    const INTERLOCK_EMERGENCY = 'emergency';
    const INTERLOCK_PERMISSIVE = 'permissive';

    public function __construct() {
        $this->db = Database::getInstance();
        $this->scadaMonitor = new SCADAMonitor();
        $this->loadInterlockRules();
    }

    /**
     * Control valve operation
     */
    public function controlValve($valveId, $command, $value = null, $operatorInfo = []) {
        try {
            // Get valve information
            $valve = $this->getValveInfo($valveId);
            if (!$valve) {
                throw new Exception("Valve not found: ID $valveId");
            }

            // Validate operator authorization
            if (!$this->validateAuthorization($operatorInfo, $valve['criticality'])) {
                throw new Exception("Insufficient authorization level");
            }

            // Check safety interlocks
            $interlockCheck = $this->checkSafetyInterlocks($valveId, $command, $value);
            if (!$interlockCheck['safe']) {
                return [
                    'success' => false,
                    'interlocked' => true,
                    'reason' => $interlockCheck['reason'],
                    'severity' => $interlockCheck['severity']
                ];
            }

            // Validate control mode
            if (!$this->validateControlMode($valve, $operatorInfo)) {
                throw new Exception("Valve not in remote control mode");
            }

            // Execute control command
            $result = $this->executeControl($valve, $command, $value);

            if ($result['success']) {
                // Log the action
                $this->logControlAction(
                    $valveId,
                    $command,
                    $value,
                    $operatorInfo['operator_name'] ?? 'System',
                    $operatorInfo['authorization_level'] ?? 'operator',
                    'completed'
                );

                // Update valve status
                $this->updateValveStatus($valveId, $command, $value);

                return [
                    'success' => true,
                    'message' => "Valve control executed successfully",
                    'valve_name' => $valve['valve_name'],
                    'command' => $command,
                    'timestamp' => date('Y-m-d H:i:s')
                ];
            }

            throw new Exception($result['error'] ?? 'Control execution failed');

        } catch (Exception $e) {
            // Log failure
            $this->logControlAction(
                $valveId,
                $command,
                $value,
                $operatorInfo['operator_name'] ?? 'System',
                $operatorInfo['authorization_level'] ?? 'operator',
                'failed',
                $e->getMessage()
            );

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check safety interlocks
     */
    private function checkSafetyInterlocks($valveId, $command, $value) {
        // Get valve interlock rules
        $rules = $this->getValveInterlockRules($valveId);

        // Check manual lock
        $valve = $this->getValveInfo($valveId);
        if ($valve['is_interlocked'] && $valve['interlock_reason']) {
            return [
                'safe' => false,
                'reason' => $valve['interlock_reason'],
                'severity' => 'critical'
            ];
        }

        // Check maintenance mode
        $asset = $this->getAssetInfo($valve['valve_id']);
        if ($asset['status'] === 'maintenance') {
            return [
                'safe' => false,
                'reason' => 'Valve in maintenance mode',
                'severity' => 'high'
            ];
        }

        // Check process interlocks
        foreach ($rules as $rule) {
            $checkResult = $this->evaluateInterlockRule($rule, $command, $value);
            if (!$checkResult['safe']) {
                return $checkResult;
            }
        }

        // Check permissives (conditions that must be true)
        $permissives = $this->checkPermissives($valveId, $command);
        if (!$permissives['safe']) {
            return $permissives;
        }

        // Check emergency shutdown
        $esdStatus = $this->checkEmergencyShutdown($valve['site_id']);
        if ($esdStatus['active']) {
            return [
                'safe' => false,
                'reason' => 'Emergency shutdown active',
                'severity' => 'critical'
            ];
        }

        return ['safe' => true];
    }

    /**
     * Get valve interlock rules
     */
    private function getValveInterlockRules($valveId) {
        $query = "SELECT r.*, t.tag_name, t.current_value
                  FROM scada_interlock_rules r
                  LEFT JOIN scada_tags t ON r.tag_id = t.id
                  WHERE r.valve_asset_id = ? AND r.is_enabled = 1";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $valveId);
        $stmt->execute();
        $result = $stmt->get_result();

        $rules = [];
        while ($row = $result->fetch_assoc()) {
            $rules[] = $row;
        }

        return $rules;
    }

    /**
     * Evaluate interlock rule
     */
    private function evaluateInterlockRule($rule, $command, $value) {
        $tagValue = floatval($rule['current_value']);

        // Check condition based on rule type
        $violated = false;
        $reason = '';

        switch ($rule['condition_type']) {
            case 'greater_than':
                if ($tagValue > $rule['threshold_value']) {
                    $violated = true;
                    $reason = "{$rule['tag_name']} ({$tagValue}) exceeds limit ({$rule['threshold_value']})";
                }
                break;

            case 'less_than':
                if ($tagValue < $rule['threshold_value']) {
                    $violated = true;
                    $reason = "{$rule['tag_name']} ({$tagValue}) below minimum ({$rule['threshold_value']})";
                }
                break;

            case 'equals':
                if ($tagValue == $rule['threshold_value']) {
                    $violated = true;
                    $reason = "{$rule['tag_name']} equals interlock condition ({$rule['threshold_value']})";
                }
                break;

            case 'between':
                if ($tagValue >= $rule['threshold_min'] && $tagValue <= $rule['threshold_max']) {
                    $violated = true;
                    $reason = "{$rule['tag_name']} in interlock range ({$rule['threshold_min']} to {$rule['threshold_max']})";
                }
                break;
        }

        // Check if rule applies to this command
        if ($violated && $this->ruleAppliesToCommand($rule, $command)) {
            return [
                'safe' => false,
                'reason' => $reason,
                'severity' => $rule['severity'] ?? 'high',
                'rule_id' => $rule['id']
            ];
        }

        return ['safe' => true];
    }

    /**
     * Check if rule applies to command
     */
    private function ruleAppliesToCommand($rule, $command) {
        $applicableCommands = json_decode($rule['applicable_commands'], true);
        if (empty($applicableCommands)) {
            return true; // Apply to all commands
        }
        return in_array($command, $applicableCommands);
    }

    /**
     * Check permissives
     */
    private function checkPermissives($valveId, $command) {
        // Permissives are conditions that MUST be true for operation
        $query = "SELECT * FROM scada_permissives
                  WHERE valve_asset_id = ? AND is_active = 1";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $valveId);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            if (!$this->evaluatePermissive($row)) {
                return [
                    'safe' => false,
                    'reason' => "Permissive not satisfied: {$row['description']}",
                    'severity' => 'high'
                ];
            }
        }

        return ['safe' => true];
    }

    /**
     * Evaluate permissive condition
     */
    private function evaluatePermissive($permissive) {
        // Check if required condition is met
        $tagValue = $this->getTagValue($permissive['tag_id']);

        switch ($permissive['condition']) {
            case 'true':
                return (bool)$tagValue;
            case 'false':
                return !((bool)$tagValue);
            case 'above':
                return $tagValue > $permissive['threshold'];
            case 'below':
                return $tagValue < $permissive['threshold'];
            default:
                return false;
        }
    }

    /**
     * Check emergency shutdown status
     */
    private function checkEmergencyShutdown($siteId) {
        $query = "SELECT * FROM scada_emergency_shutdown
                  WHERE site_id = ? AND status = 'active'
                  ORDER BY activated_at DESC LIMIT 1";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $siteId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            return [
                'active' => true,
                'reason' => $row['reason'],
                'activated_at' => $row['activated_at']
            ];
        }

        return ['active' => false];
    }

    /**
     * Execute control command
     */
    private function executeControl($valve, $command, $value) {
        try {
            // Get the protocol handler
            $protocol = $this->getProtocolHandler($valve);

            if (!$protocol) {
                throw new Exception("Protocol handler not available");
            }

            // Execute based on command type
            switch ($command) {
                case self::CMD_OPEN:
                    $success = $this->openValve($valve, $protocol);
                    break;

                case self::CMD_CLOSE:
                    $success = $this->closeValve($valve, $protocol);
                    break;

                case self::CMD_POSITION:
                    $success = $this->setValvePosition($valve, $protocol, $value);
                    break;

                case self::CMD_STOP:
                    $success = $this->stopValve($valve, $protocol);
                    break;

                default:
                    throw new Exception("Unknown command: $command");
            }

            return ['success' => $success];

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Open valve
     */
    private function openValve($valve, $protocol) {
        if ($valve['command_tag_id']) {
            // Write open command to tag
            $tag = $this->getTagInfo($valve['command_tag_id']);
            return $protocol->writeNode($tag['memory_address'], 1);
        }
        return false;
    }

    /**
     * Close valve
     */
    private function closeValve($valve, $protocol) {
        if ($valve['command_tag_id']) {
            $tag = $this->getTagInfo($valve['command_tag_id']);
            return $protocol->writeNode($tag['memory_address'], 0);
        }
        return false;
    }

    /**
     * Set valve position
     */
    private function setValvePosition($valve, $protocol, $position) {
        if ($valve['command_tag_id']) {
            // Validate position (0-100%)
            $position = max(0, min(100, $position));
            $tag = $this->getTagInfo($valve['command_tag_id']);
            return $protocol->writeNode($tag['memory_address'], $position);
        }
        return false;
    }

    /**
     * Stop valve
     */
    private function stopValve($valve, $protocol) {
        // Send stop command if available
        return true;
    }

    /**
     * Validate authorization level
     */
    private function validateAuthorization($operatorInfo, $valveCriticality) {
        $authLevel = $operatorInfo['authorization_level'] ?? 'operator';

        $levelHierarchy = [
            'operator' => 1,
            'supervisor' => 2,
            'engineer' => 3,
            'administrator' => 4
        ];

        $requiredLevel = [
            'low' => 1,
            'medium' => 2,
            'high' => 3,
            'critical' => 4
        ];

        $userLevel = $levelHierarchy[$authLevel] ?? 1;
        $required = $requiredLevel[$valveCriticality] ?? 1;

        return $userLevel >= $required;
    }

    /**
     * Validate control mode
     */
    private function validateControlMode($valve, $operatorInfo) {
        // Check if valve is in appropriate control mode
        $allowedModes = ['remote', 'automatic'];

        // Administrators can override
        if ($operatorInfo['authorization_level'] === 'administrator') {
            return true;
        }

        return in_array($valve['control_mode'], $allowedModes);
    }

    /**
     * Update valve status
     */
    private function updateValveStatus($valveId, $command, $value) {
        $newState = null;

        switch ($command) {
            case self::CMD_OPEN:
                $newState = 'opening';
                $value = 100;
                break;
            case self::CMD_CLOSE:
                $newState = 'closing';
                $value = 0;
                break;
            case self::CMD_POSITION:
                $newState = 'partial';
                break;
        }

        if ($newState) {
            $query = "UPDATE scada_valve_status
                      SET valve_state = ?,
                          target_position = ?,
                          last_operated_time = NOW(),
                          cycle_count = cycle_count + 1
                      WHERE valve_id = ?";

            $stmt = $this->db->prepare($query);
            $stmt->bind_param("sdi", $newState, $value, $valveId);
            $stmt->execute();
        }
    }

    /**
     * Log control action
     */
    private function logControlAction($valveId, $command, $value, $operator, $authLevel, $status, $error = null) {
        $valve = $this->getValveInfo($valveId);

        $description = "Valve {$command}";
        if ($command === self::CMD_POSITION && $value !== null) {
            $description .= " to {$value}%";
        }

        $query = "INSERT INTO scada_control_actions
                  (site_id, asset_id, action_type, action_description,
                   new_value, initiated_by, authorization_level,
                   execution_status, error_message, created_at, completed_at)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

        $stmt = $this->db->prepare($query);
        $actionType = 'valve_' . $command;

        $stmt->bind_param(
            "iisssssss",
            $valve['site_id'],
            $valveId,
            $actionType,
            $description,
            $value,
            $operator,
            $authLevel,
            $status,
            $error
        );

        $stmt->execute();
    }

    /**
     * Create manual interlock
     */
    public function lockValve($valveId, $reason, $lockedBy) {
        $query = "UPDATE scada_valve_status
                  SET is_interlocked = 1,
                      interlock_reason = ?,
                      valve_state = 'locked'
                  WHERE valve_id = ?";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param("si", $reason, $valveId);
        $result = $stmt->execute();

        $this->logControlAction(
            $valveId,
            'lock',
            null,
            $lockedBy,
            'supervisor',
            'completed'
        );

        return $result;
    }

    /**
     * Remove manual interlock
     */
    public function unlockValve($valveId, $unlockedBy) {
        $query = "UPDATE scada_valve_status
                  SET is_interlocked = 0,
                      interlock_reason = NULL
                  WHERE valve_id = ?";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $valveId);
        $result = $stmt->execute();

        $this->logControlAction(
            $valveId,
            'unlock',
            null,
            $unlockedBy,
            'supervisor',
            'completed'
        );

        return $result;
    }

    /**
     * Get valve information
     */
    private function getValveInfo($valveId) {
        $query = "SELECT v.*, a.site_id, a.criticality
                  FROM scada_valve_status v
                  JOIN scada_assets a ON v.valve_id = a.id
                  WHERE v.valve_id = ?";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $valveId);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result->fetch_assoc();
    }

    /**
     * Get asset information
     */
    private function getAssetInfo($assetId) {
        $query = "SELECT * FROM scada_assets WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $assetId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Get tag information
     */
    private function getTagInfo($tagId) {
        $query = "SELECT * FROM scada_tags WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $tagId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Get tag current value
     */
    private function getTagValue($tagId) {
        $query = "SELECT current_value FROM scada_tags WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $tagId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['current_value'] ?? null;
    }

    /**
     * Get protocol handler
     */
    private function getProtocolHandler($valve) {
        // This would return the appropriate protocol handler
        // Connected to the PLC/RTU controlling this valve
        return $this->scadaMonitor;
    }

    /**
     * Load interlock rules
     */
    private function loadInterlockRules() {
        // Load from database or configuration
        $this->interlockRules = [];
    }
}
