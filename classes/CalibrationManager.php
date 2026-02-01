<?php
/**
 * Instrumentation Calibration Management System
 * Software-based calibration tracking and management
 * Compliant with ISO/IEC 17025 and ISA standards
 *
 * @author HoL Platform
 * @version 2.0
 */

require_once __DIR__ . '/Database.php';

class CalibrationManager {
    private $db;

    // Calibration types
    const TYPE_ZERO = 'zero';
    const TYPE_SPAN = 'span';
    const TYPE_FULL = 'full';
    const TYPE_VERIFICATION = 'verification';
    const TYPE_ADJUSTMENT = 'adjustment';

    // Pass/Fail criteria
    const PASS = 'pass';
    const FAIL = 'fail';
    const CONDITIONAL = 'conditional';

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Perform instrument calibration
     */
    public function performCalibration($instrumentId, $calibrationData) {
        try {
            // Get instrument information
            $instrument = $this->getInstrumentInfo($instrumentId);
            if (!$instrument) {
                throw new Exception("Instrument not found");
            }

            // Validate calibration data
            $this->validateCalibrationData($calibrationData);

            // Generate test points based on instrument range
            $testPoints = $this->generateTestPoints(
                $instrument['range_min'],
                $instrument['range_max'],
                $calibrationData['num_points'] ?? 5
            );

            // Perform calibration
            $results = $this->executeCalibration(
                $instrument,
                $testPoints,
                $calibrationData
            );

            // Calculate accuracy achieved
            $accuracyAchieved = $this->calculateAccuracy($results);

            // Determine pass/fail
            $passFail = $this->determinePassFail(
                $accuracyAchieved,
                $instrument['accuracy_percent']
            );

            // Calculate next calibration date
            $nextCalDate = $this->calculateNextCalibrationDate(
                $instrument['calibration_interval_days']
            );

            // Store calibration record
            $recordId = $this->storeCalibrationRecord(
                $instrumentId,
                $calibrationData,
                $testPoints,
                $results,
                $accuracyAchieved,
                $passFail,
                $nextCalDate
            );

            // Update instrument status
            $this->updateInstrumentStatus(
                $instrumentId,
                $passFail,
                $nextCalDate
            );

            // Generate certificate
            $certificateNumber = $this->generateCertificate($recordId);

            return [
                'success' => true,
                'calibration_id' => $recordId,
                'pass_fail' => $passFail,
                'accuracy_achieved' => $accuracyAchieved,
                'next_calibration_date' => $nextCalDate,
                'certificate_number' => $certificateNumber,
                'test_results' => $results
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Generate calibration test points
     */
    private function generateTestPoints($rangeMin, $rangeMax, $numPoints = 5) {
        $points = [];
        $span = $rangeMax - $rangeMin;

        // Always include 0%, 25%, 50%, 75%, 100% of span
        $percentages = [0, 25, 50, 75, 100];

        if ($numPoints > 5) {
            // Add intermediate points
            $step = 100 / ($numPoints - 1);
            $percentages = [];
            for ($i = 0; $i < $numPoints; $i++) {
                $percentages[] = $i * $step;
            }
        }

        foreach ($percentages as $pct) {
            $points[] = $rangeMin + ($span * ($pct / 100));
        }

        return $points;
    }

    /**
     * Execute calibration procedure
     */
    private function executeCalibration($instrument, $testPoints, $calibrationData) {
        $results = [];

        // Get as-found readings
        $asFoundReadings = $calibrationData['as_found_readings'] ?? [];

        // Get as-left readings (after adjustment)
        $asLeftReadings = $calibrationData['as_left_readings'] ?? [];

        foreach ($testPoints as $index => $appliedValue) {
            $results[] = [
                'test_point' => $appliedValue,
                'applied_value' => $appliedValue,
                'as_found_reading' => $asFoundReadings[$index] ?? null,
                'as_found_error' => $this->calculateError(
                    $appliedValue,
                    $asFoundReadings[$index] ?? null
                ),
                'as_found_error_percent' => $this->calculateErrorPercent(
                    $appliedValue,
                    $asFoundReadings[$index] ?? null,
                    $instrument['range_min'],
                    $instrument['range_max']
                ),
                'as_left_reading' => $asLeftReadings[$index] ?? null,
                'as_left_error' => $this->calculateError(
                    $appliedValue,
                    $asLeftReadings[$index] ?? null
                ),
                'as_left_error_percent' => $this->calculateErrorPercent(
                    $appliedValue,
                    $asLeftReadings[$index] ?? null,
                    $instrument['range_min'],
                    $instrument['range_max']
                )
            ];
        }

        return $results;
    }

    /**
     * Calculate measurement error
     */
    private function calculateError($applied, $measured) {
        if ($measured === null) {
            return null;
        }
        return $measured - $applied;
    }

    /**
     * Calculate error as percentage of span
     */
    private function calculateErrorPercent($applied, $measured, $rangeMin, $rangeMax) {
        if ($measured === null) {
            return null;
        }

        $span = $rangeMax - $rangeMin;
        $error = $measured - $applied;

        return ($error / $span) * 100;
    }

    /**
     * Calculate overall accuracy achieved
     */
    private function calculateAccuracy($results) {
        $maxError = 0;

        foreach ($results as $result) {
            if ($result['as_left_error_percent'] !== null) {
                $maxError = max($maxError, abs($result['as_left_error_percent']));
            }
        }

        return round($maxError, 3);
    }

    /**
     * Determine pass/fail status
     */
    private function determinePassFail($accuracyAchieved, $requiredAccuracy) {
        if ($accuracyAchieved <= $requiredAccuracy) {
            return self::PASS;
        } else if ($accuracyAchieved <= $requiredAccuracy * 1.5) {
            return self::CONDITIONAL;
        } else {
            return self::FAIL;
        }
    }

    /**
     * Calculate next calibration date
     */
    private function calculateNextCalibrationDate($intervalDays) {
        return date('Y-m-d', strtotime("+$intervalDays days"));
    }

    /**
     * Store calibration record
     */
    private function storeCalibrationRecord(
        $instrumentId,
        $calibrationData,
        $testPoints,
        $results,
        $accuracyAchieved,
        $passFail,
        $nextCalDate
    ) {
        $query = "INSERT INTO scada_calibration_records
                  (instrument_id, calibration_type, calibration_date,
                   calibrated_by, reference_standard, test_points,
                   as_found_values, as_left_values, accuracy_achieved,
                   pass_fail, next_calibration_date, comments, calibration_data)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($query);

        $calibrationType = $calibrationData['type'] ?? self::TYPE_FULL;
        $calibrationDate = $calibrationData['date'] ?? date('Y-m-d');
        $calibratedBy = $calibrationData['calibrated_by'] ?? 'System';
        $referenceStandard = $calibrationData['reference_standard'] ?? '';
        $comments = $calibrationData['comments'] ?? '';

        $testPointsJson = json_encode($testPoints);
        $asFoundJson = json_encode(array_column($results, 'as_found_reading'));
        $asLeftJson = json_encode(array_column($results, 'as_left_reading'));
        $calibrationDataJson = json_encode($calibrationData);

        $stmt->bind_param(
            "isssssssdssss",
            $instrumentId,
            $calibrationType,
            $calibrationDate,
            $calibratedBy,
            $referenceStandard,
            $testPointsJson,
            $asFoundJson,
            $asLeftJson,
            $accuracyAchieved,
            $passFail,
            $nextCalDate,
            $comments,
            $calibrationDataJson
        );

        $stmt->execute();
        return $this->db->insert_id;
    }

    /**
     * Update instrument calibration status
     */
    private function updateInstrumentStatus($instrumentId, $passFail, $nextCalDate) {
        $calibrationStatus = ($passFail === self::PASS) ? 'valid' : 'failed';

        if ($passFail === self::CONDITIONAL) {
            $calibrationStatus = 'valid'; // But with note
        }

        $query = "UPDATE scada_instruments
                  SET last_calibration_date = CURDATE(),
                      next_calibration_date = ?,
                      calibration_status = ?
                  WHERE id = ?";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ssi", $nextCalDate, $calibrationStatus, $instrumentId);
        $stmt->execute();
    }

    /**
     * Generate calibration certificate number
     */
    private function generateCertificate($recordId) {
        $year = date('Y');
        $month = date('m');
        $certificateNumber = "CAL-$year$month-" . str_pad($recordId, 6, '0', STR_PAD_LEFT);

        // Update record with certificate number
        $query = "UPDATE scada_calibration_records
                  SET certificate_number = ?
                  WHERE id = ?";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param("si", $certificateNumber, $recordId);
        $stmt->execute();

        return $certificateNumber;
    }

    /**
     * Get instruments due for calibration
     */
    public function getInstrumentsDue($daysAhead = 30) {
        $query = "SELECT i.*, a.asset_name, s.site_name
                  FROM scada_instruments i
                  JOIN scada_assets a ON i.asset_id = a.id
                  JOIN scada_sites s ON a.site_id = s.id
                  WHERE i.next_calibration_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
                  AND i.calibration_status != 'failed'
                  ORDER BY i.next_calibration_date ASC";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $daysAhead);
        $stmt->execute();
        $result = $stmt->get_result();

        $instruments = [];
        while ($row = $result->fetch_assoc()) {
            $instruments[] = $row;
        }

        return $instruments;
    }

    /**
     * Get calibration history for instrument
     */
    public function getCalibrationHistory($instrumentId, $limit = 10) {
        $query = "SELECT * FROM scada_calibration_records
                  WHERE instrument_id = ?
                  ORDER BY calibration_date DESC
                  LIMIT ?";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ii", $instrumentId, $limit);
        $stmt->execute();
        $result = $stmt->get_result();

        $history = [];
        while ($row = $result->fetch_assoc()) {
            $history[] = $row;
        }

        return $history;
    }

    /**
     * Perform zero calibration
     */
    public function zeroCalibration($instrumentId, $zeroReading, $calibratedBy) {
        $calibrationData = [
            'type' => self::TYPE_ZERO,
            'calibrated_by' => $calibratedBy,
            'as_found_readings' => [$zeroReading],
            'as_left_readings' => [0],
            'num_points' => 1,
            'comments' => 'Zero calibration performed'
        ];

        return $this->performCalibration($instrumentId, $calibrationData);
    }

    /**
     * Perform span calibration
     */
    public function spanCalibration($instrumentId, $spanReading, $calibratedBy) {
        $instrument = $this->getInstrumentInfo($instrumentId);

        $calibrationData = [
            'type' => self::TYPE_SPAN,
            'calibrated_by' => $calibratedBy,
            'as_found_readings' => [$spanReading],
            'as_left_readings' => [$instrument['range_max']],
            'num_points' => 1,
            'comments' => 'Span calibration performed'
        ];

        return $this->performCalibration($instrumentId, $calibrationData);
    }

    /**
     * Validate calibration data
     */
    private function validateCalibrationData($data) {
        if (!isset($data['calibrated_by'])) {
            throw new Exception("Calibrated by field is required");
        }

        if (!isset($data['as_found_readings']) || empty($data['as_found_readings'])) {
            throw new Exception("As-found readings are required");
        }

        return true;
    }

    /**
     * Get instrument information
     */
    private function getInstrumentInfo($instrumentId) {
        $query = "SELECT i.*, a.asset_name, s.site_name
                  FROM scada_instruments i
                  JOIN scada_assets a ON i.asset_id = a.id
                  JOIN scada_sites s ON a.site_id = s.id
                  WHERE i.id = ?";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $instrumentId);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result->fetch_assoc();
    }

    /**
     * Generate calibration report
     */
    public function generateReport($calibrationId) {
        $query = "SELECT cr.*, i.*, a.asset_name, s.site_name
                  FROM scada_calibration_records cr
                  JOIN scada_instruments i ON cr.instrument_id = i.id
                  JOIN scada_assets a ON i.asset_id = a.id
                  JOIN scada_sites s ON a.site_id = s.id
                  WHERE cr.id = ?";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $calibrationId);
        $stmt->execute();
        $result = $stmt->get_result();

        $record = $result->fetch_assoc();

        if (!$record) {
            return null;
        }

        // Build comprehensive report
        return [
            'certificate_number' => $record['certificate_number'],
            'instrument' => [
                'name' => $record['asset_name'],
                'type' => $record['instrument_type'],
                'manufacturer' => $record['manufacturer'] ?? 'N/A',
                'model' => $record['model_number'] ?? 'N/A',
                'serial' => $record['serial_number'] ?? 'N/A',
                'range' => $record['range_min'] . ' to ' . $record['range_max'] . ' ' . $record['measurement_unit'],
                'accuracy' => $record['accuracy_percent'] . '%'
            ],
            'calibration' => [
                'date' => $record['calibration_date'],
                'type' => $record['calibration_type'],
                'calibrated_by' => $record['calibrated_by'],
                'reference_standard' => $record['reference_standard'],
                'pass_fail' => $record['pass_fail'],
                'accuracy_achieved' => $record['accuracy_achieved'] . '%',
                'next_date' => $record['next_calibration_date']
            ],
            'test_results' => [
                'test_points' => json_decode($record['test_points'], true),
                'as_found' => json_decode($record['as_found_values'], true),
                'as_left' => json_decode($record['as_left_values'], true)
            ],
            'comments' => $record['comments'],
            'site' => $record['site_name']
        ];
    }
}
