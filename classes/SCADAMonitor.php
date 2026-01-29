<?php
/**
 * SCADA Monitoring Engine
 * Core monitoring system for industrial control systems
 * Handles real-time data acquisition, protocol management, and alarm processing
 *
 * @author IOC Platform
 * @version 2.0
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/ModbusProtocol.php';
require_once __DIR__ . '/OPCUAProtocol.php';
require_once __DIR__ . '/DNP3Protocol.php';

class SCADAMonitor {
    private $db;
    private $protocols = [];
    private $activeSessions = [];
    private $alarmThresholds = [];
    private $scanInterval = 1000; // milliseconds

    public function __construct() {
        $this->db = Database::getInstance();
        $this->initializeProtocols();
        $this->loadAlarmThresholds();
    }

    /**
     * Initialize protocol handlers
     */
    private function initializeProtocols() {
        $this->protocols['modbus_tcp'] = new ModbusProtocol('tcp');
        $this->protocols['modbus_rtu'] = new ModbusProtocol('rtu');
        $this->protocols['opc_ua'] = new OPCUAProtocol();
        $this->protocols['dnp3'] = new DNP3Protocol();
    }

    /**
     * Load alarm thresholds from database
     */
    private function loadAlarmThresholds() {
        $query = "SELECT id, tag_name, alarm_low_low, alarm_low, alarm_high, alarm_high_high
                  FROM scada_tags
                  WHERE is_alarmed = 1";
        $result = $this->db->query($query);

        while ($row = $result->fetch_assoc()) {
            $this->alarmThresholds[$row['id']] = [
                'tag_name' => $row['tag_name'],
                'low_low' => $row['alarm_low_low'],
                'low' => $row['alarm_low'],
                'high' => $row['alarm_high'],
                'high_high' => $row['alarm_high_high']
            ];
        }
    }

    /**
     * Start monitoring a specific site
     */
    public function startMonitoring($siteId) {
        try {
            // Get site information
            $site = $this->getSiteInfo($siteId);
            if (!$site) {
                throw new Exception("Site not found");
            }

            // Get all PLCs for this site
            $plcs = $this->getActivePLCs($siteId);

            // Get all RTUs for this site
            $rtus = $this->getActiveRTUs($siteId);

            // Start polling each device
            foreach ($plcs as $plc) {
                $this->startPLCPolling($plc);
            }

            foreach ($rtus as $rtu) {
                $this->startRTUPolling($rtu);
            }

            $this->activeSessions[$siteId] = [
                'site' => $site,
                'start_time' => microtime(true),
                'plc_count' => count($plcs),
                'rtu_count' => count($rtus),
                'status' => 'active'
            ];

            return [
                'success' => true,
                'message' => "Monitoring started for site: {$site['site_name']}",
                'devices' => count($plcs) + count($rtus)
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Start polling a PLC
     */
    private function startPLCPolling($plc) {
        try {
            // Get protocol handler
            $protocolName = $this->getProtocolName($plc['protocol_id']);
            if (!isset($this->protocols[$protocolName])) {
                throw new Exception("Unsupported protocol: $protocolName");
            }

            $protocol = $this->protocols[$protocolName];

            // Connect to PLC
            $connected = $protocol->connect(
                $plc['ip_address'],
                $plc['port'],
                $plc['connection_timeout_ms']
            );

            if ($connected) {
                // Update PLC status
                $this->updatePLCStatus($plc['id'], true);

                // Get all tags for this PLC
                $tags = $this->getPLCTags($plc['id']);

                // Start polling tags
                $this->pollTags($protocol, $tags, $plc);

            } else {
                $this->updatePLCStatus($plc['id'], false, "Connection failed");
            }

        } catch (Exception $e) {
            $this->updatePLCStatus($plc['id'], false, $e->getMessage());
            error_log("PLC Polling Error [{$plc['id']}]: " . $e->getMessage());
        }
    }

    /**
     * Start polling an RTU
     */
    private function startRTUPolling($rtu) {
        try {
            // Get protocol handler
            $protocolName = $this->getProtocolName($rtu['protocol_id']);
            if (!isset($this->protocols[$protocolName])) {
                throw new Exception("Unsupported protocol: $protocolName");
            }

            $protocol = $this->protocols[$protocolName];

            // Connect to RTU (supports GSM, serial, TCP)
            $connected = false;
            if ($rtu['communication_type'] === 'gsm') {
                $connected = $protocol->connectGSM($rtu['gsm_number']);
            } else if ($rtu['communication_type'] === 'serial') {
                $connected = $protocol->connectSerial($rtu['rtu_address']);
            } else {
                $connected = $protocol->connect($rtu['rtu_address'], null, 5000);
            }

            if ($connected) {
                $this->updateRTUStatus($rtu['id'], true);

                // Get all tags for this RTU
                $tags = $this->getRTUTags($rtu['id']);

                // Start polling tags
                $this->pollTags($protocol, $tags, $rtu);

                // Update signal strength for GSM connections
                if ($rtu['communication_type'] === 'gsm') {
                    $this->updateGSMSignal($rtu['id'], $protocol->getSignalStrength());
                }

            } else {
                $this->updateRTUStatus($rtu['id'], false, "Connection failed");
            }

        } catch (Exception $e) {
            $this->updateRTUStatus($rtu['id'], false, $e->getMessage());
            error_log("RTU Polling Error [{$rtu['id']}]: " . $e->getMessage());
        }
    }

    /**
     * Poll tags from device
     */
    private function pollTags($protocol, $tags, $device) {
        foreach ($tags as $tag) {
            try {
                // Read value from device based on tag configuration
                $value = null;
                $quality = 'uncertain';

                if ($tag['memory_address']) {
                    $value = $protocol->readAddress($tag['memory_address'], $tag['data_type']);
                } else if ($tag['register_number']) {
                    $value = $protocol->readRegister(
                        $tag['register_number'],
                        $tag['data_type'],
                        $tag['bit_position']
                    );
                }

                if ($value !== null) {
                    // Apply scaling and offset
                    if ($tag['tag_type'] === 'analog_input' || $tag['tag_type'] === 'analog_output') {
                        $value = ($value * $tag['scaling_factor']) + $tag['offset'];
                    }

                    $quality = 'good';

                    // Check alarms
                    $this->checkAlarms($tag, $value);
                }

                // Update tag value
                $this->updateTagValue($tag['id'], $value, $quality);

                // Store in history if archiving is enabled
                if ($tag['is_archived']) {
                    $this->storeTagHistory($tag['id'], $value, $quality);
                }

            } catch (Exception $e) {
                $this->updateTagValue($tag['id'], null, 'bad');
                error_log("Tag Read Error [{$tag['tag_name']}]: " . $e->getMessage());
            }

            // Respect scan rate
            usleep($tag['scan_rate_ms'] * 1000);
        }
    }

    /**
     * Check alarm conditions
     */
    private function checkAlarms($tag, $value) {
        if (!isset($this->alarmThresholds[$tag['id']])) {
            return;
        }

        $thresholds = $this->alarmThresholds[$tag['id']];
        $alarmType = null;
        $severity = null;

        // Check thresholds
        if ($value !== null && is_numeric($value)) {
            if ($thresholds['high_high'] !== null && $value >= $thresholds['high_high']) {
                $alarmType = 'high_high';
                $severity = 'critical';
            } else if ($thresholds['high'] !== null && $value >= $thresholds['high']) {
                $alarmType = 'high';
                $severity = 'high';
            } else if ($thresholds['low_low'] !== null && $value <= $thresholds['low_low']) {
                $alarmType = 'low_low';
                $severity = 'critical';
            } else if ($thresholds['low'] !== null && $value <= $thresholds['low']) {
                $alarmType = 'low';
                $severity = 'high';
            }
        }

        // Generate alarm if threshold exceeded
        if ($alarmType) {
            $this->generateAlarm($tag, $alarmType, $severity, $value);
        }
    }

    /**
     * Generate alarm
     */
    private function generateAlarm($tag, $alarmType, $severity, $value) {
        // Check if alarm already exists and is active
        $existing = $this->db->query(
            "SELECT id FROM scada_alarm_history
             WHERE tag_id = {$tag['id']}
             AND alarm_type = '$alarmType'
             AND alarm_state = 'active'
             LIMIT 1"
        );

        if ($existing->num_rows > 0) {
            return; // Alarm already active
        }

        // Create new alarm
        $message = "{$tag['tag_name']} {$alarmType} alarm: Value = {$value} {$tag['engineering_unit']}";

        $stmt = $this->db->prepare(
            "INSERT INTO scada_alarm_history
             (site_id, tag_id, asset_id, alarm_type, severity, alarm_message, alarm_value, trigger_time, alarm_state)
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 'active')"
        );

        $assetId = $this->getTagAssetId($tag['id']);

        $stmt->bind_param(
            "iiissss",
            $tag['site_id'],
            $tag['id'],
            $assetId,
            $alarmType,
            $severity,
            $message,
            $value
        );

        $stmt->execute();

        // Send notifications if configured
        $this->sendAlarmNotification($tag, $alarmType, $severity, $message);
    }

    /**
     * Update tag value in database
     */
    private function updateTagValue($tagId, $value, $quality) {
        $stmt = $this->db->prepare(
            "UPDATE scada_tags
             SET current_value = ?,
                 current_quality = ?,
                 last_update_time = NOW()
             WHERE id = ?"
        );

        $stmt->bind_param("ssi", $value, $quality, $tagId);
        $stmt->execute();
    }

    /**
     * Store tag value in historical table
     */
    private function storeTagHistory($tagId, $value, $quality) {
        $stmt = $this->db->prepare(
            "INSERT INTO scada_tag_history (tag_id, timestamp, value, quality)
             VALUES (?, NOW(3), ?, ?)"
        );

        $stmt->bind_param("iss", $tagId, $value, $quality);
        $stmt->execute();
    }

    /**
     * Get site information
     */
    private function getSiteInfo($siteId) {
        $result = $this->db->query(
            "SELECT * FROM scada_sites WHERE id = $siteId AND is_active = 1"
        );
        return $result->fetch_assoc();
    }

    /**
     * Get active PLCs for a site
     */
    private function getActivePLCs($siteId) {
        $query = "SELECT p.*, a.asset_name
                  FROM scada_plcs p
                  JOIN scada_assets a ON p.asset_id = a.id
                  WHERE a.site_id = $siteId AND a.status = 'operational'";

        $result = $this->db->query($query);
        $plcs = [];
        while ($row = $result->fetch_assoc()) {
            $plcs[] = $row;
        }
        return $plcs;
    }

    /**
     * Get active RTUs for a site
     */
    private function getActiveRTUs($siteId) {
        $query = "SELECT r.*, a.asset_name
                  FROM scada_rtus r
                  JOIN scada_assets a ON r.asset_id = a.id
                  WHERE a.site_id = $siteId AND a.status = 'operational'";

        $result = $this->db->query($query);
        $rtus = [];
        while ($row = $result->fetch_assoc()) {
            $rtus[] = $row;
        }
        return $rtus;
    }

    /**
     * Get tags for a PLC
     */
    private function getPLCTags($plcId) {
        $query = "SELECT * FROM scada_tags WHERE plc_id = $plcId ORDER BY scan_rate_ms";
        $result = $this->db->query($query);
        $tags = [];
        while ($row = $result->fetch_assoc()) {
            $tags[] = $row;
        }
        return $tags;
    }

    /**
     * Get tags for an RTU
     */
    private function getRTUTags($rtuId) {
        $query = "SELECT * FROM scada_tags WHERE rtu_id = $rtuId ORDER BY scan_rate_ms";
        $result = $this->db->query($query);
        $tags = [];
        while ($row = $result->fetch_assoc()) {
            $tags[] = $row;
        }
        return $tags;
    }

    /**
     * Update PLC status
     */
    private function updatePLCStatus($plcId, $isOnline, $error = null) {
        $stmt = $this->db->prepare(
            "UPDATE scada_plcs
             SET is_online = ?, last_poll_time = NOW(), last_error = ?
             WHERE id = ?"
        );

        $stmt->bind_param("isi", $isOnline, $error, $plcId);
        $stmt->execute();
    }

    /**
     * Update RTU status
     */
    private function updateRTUStatus($rtuId, $isOnline, $error = null) {
        $stmt = $this->db->prepare(
            "UPDATE scada_rtus
             SET is_online = ?, last_poll_time = NOW(), last_error = ?
             WHERE id = ?"
        );

        $stmt->bind_param("isi", $isOnline, $error, $rtuId);
        $stmt->execute();
    }

    /**
     * Update GSM signal strength
     */
    private function updateGSMSignal($rtuId, $signalStrength) {
        $this->db->query(
            "UPDATE scada_rtus
             SET signal_strength = $signalStrength
             WHERE id = $rtuId"
        );
    }

    /**
     * Get protocol name by ID
     */
    private function getProtocolName($protocolId) {
        $result = $this->db->query(
            "SELECT protocol_name FROM scada_protocols WHERE id = $protocolId"
        );
        $row = $result->fetch_assoc();
        return $row['protocol_name'];
    }

    /**
     * Get asset ID for a tag
     */
    private function getTagAssetId($tagId) {
        $result = $this->db->query(
            "SELECT COALESCE(
                (SELECT asset_id FROM scada_plcs WHERE id = (SELECT plc_id FROM scada_tags WHERE id = $tagId)),
                (SELECT asset_id FROM scada_rtus WHERE id = (SELECT rtu_id FROM scada_tags WHERE id = $tagId))
             ) as asset_id"
        );
        $row = $result->fetch_assoc();
        return $row['asset_id'];
    }

    /**
     * Send alarm notification
     */
    private function sendAlarmNotification($tag, $alarmType, $severity, $message) {
        // This would integrate with email/SMS/push notification system
        // For now, just log it
        error_log("SCADA ALARM [$severity]: $message");

        // Could integrate with existing AlertManager class
        // $alertManager = new AlertManager();
        // $alertManager->sendAlert($severity, $message);
    }

    /**
     * Get monitoring statistics
     */
    public function getStatistics($siteId = null) {
        $siteFilter = $siteId ? "WHERE site_id = $siteId" : "";

        $stats = [
            'total_sites' => $this->db->query("SELECT COUNT(*) as cnt FROM scada_sites WHERE is_active = 1")->fetch_assoc()['cnt'],
            'total_assets' => $this->db->query("SELECT COUNT(*) as cnt FROM scada_assets WHERE status = 'operational'")->fetch_assoc()['cnt'],
            'total_tags' => $this->db->query("SELECT COUNT(*) as cnt FROM scada_tags $siteFilter")->fetch_assoc()['cnt'],
            'active_alarms' => $this->db->query("SELECT COUNT(*) as cnt FROM scada_alarm_history WHERE alarm_state = 'active' $siteFilter")->fetch_assoc()['cnt'],
            'critical_alarms' => $this->db->query("SELECT COUNT(*) as cnt FROM scada_alarm_history WHERE alarm_state = 'active' AND severity = 'critical' $siteFilter")->fetch_assoc()['cnt'],
            'online_plcs' => $this->db->query("SELECT COUNT(*) as cnt FROM scada_plcs WHERE is_online = 1")->fetch_assoc()['cnt'],
            'online_rtus' => $this->db->query("SELECT COUNT(*) as cnt FROM scada_rtus WHERE is_online = 1")->fetch_assoc()['cnt'],
        ];

        return $stats;
    }

    /**
     * Stop monitoring a site
     */
    public function stopMonitoring($siteId) {
        if (isset($this->activeSessions[$siteId])) {
            unset($this->activeSessions[$siteId]);

            // Disconnect all devices
            $this->db->query("UPDATE scada_plcs p
                             JOIN scada_assets a ON p.asset_id = a.id
                             SET p.is_online = 0
                             WHERE a.site_id = $siteId");

            $this->db->query("UPDATE scada_rtus r
                             JOIN scada_assets a ON r.asset_id = a.id
                             SET r.is_online = 0
                             WHERE a.site_id = $siteId");

            return ['success' => true, 'message' => 'Monitoring stopped'];
        }

        return ['success' => false, 'error' => 'Site not being monitored'];
    }
}
