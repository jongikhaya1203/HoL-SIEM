<?php
/**
 * Create Complete Rail System
 * Full railway signaling, interlocking, and train control system
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'localhost';
$username = 'root';
$password = '';
$database = 'network_security';

echo "<!DOCTYPE html><html><head><title>Create Rail System</title>";
echo "<style>body{font-family:monospace;background:#1a1a2e;color:#0f0;padding:20px;}";
echo ".err{color:#f00;}.ok{color:#0f0;}.info{color:#0ff;}</style></head><body>";
echo "<h2>Create Complete Rail System</h2><pre>";

try {
    $conn = new mysqli($host, $username, $password, $database);

    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    echo "<span class='ok'>✓ Connected to database</span>\n\n";

    // Get or create rail site
    $railSite = $conn->query("SELECT id FROM scada_sites WHERE industry_type = 'rail' LIMIT 1")->fetch_assoc();

    if (!$railSite) {
        echo "<span class='info'>Creating rail site...</span>\n";
        $conn->query("INSERT INTO scada_sites (site_name, industry_type, latitude, longitude, site_code, is_active)
                     VALUES ('Metro Rail Control Center', 'rail', -33.8688, 151.2093, 'MRC_SYD', 1)");
        $siteId = $conn->insert_id;
        echo "<span class='ok'>✓ Rail site created (ID: {$siteId})</span>\n";
    } else {
        $siteId = $railSite['id'];
        echo "<span class='ok'>✓ Using existing rail site (ID: {$siteId})</span>\n";
    }

    // ============================================
    // 1. CREATE ENHANCED RAIL TABLES
    // ============================================
    echo "\n<span class='info'>Creating enhanced rail tables...</span>\n";

    // Track Sections
    $conn->query("CREATE TABLE IF NOT EXISTS rail_track_sections (
        id INT AUTO_INCREMENT PRIMARY KEY,
        site_id INT NOT NULL,
        section_name VARCHAR(50) NOT NULL,
        section_code VARCHAR(20) NOT NULL,
        track_number INT,
        length_meters DECIMAL(10,2),
        max_speed_kmh INT,
        section_type ENUM('main_line', 'siding', 'platform', 'depot', 'junction') DEFAULT 'main_line',
        status ENUM('operational', 'maintenance', 'blocked') DEFAULT 'operational',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_site (site_id),
        INDEX idx_code (section_code),
        FOREIGN KEY (site_id) REFERENCES scada_sites(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");
    echo "  ✓ rail_track_sections\n";

    // Enhanced Track Circuits
    $conn->query("CREATE TABLE IF NOT EXISTS rail_track_circuits_enhanced (
        id INT AUTO_INCREMENT PRIMARY KEY,
        site_id INT NOT NULL,
        section_id INT,
        circuit_name VARCHAR(50) NOT NULL,
        circuit_code VARCHAR(20) NOT NULL,
        is_occupied BOOLEAN DEFAULT FALSE,
        length_meters DECIMAL(10,2),
        voltage DECIMAL(5,2),
        current_ma DECIMAL(7,2),
        occupancy_status ENUM('clear', 'occupied', 'fault') DEFAULT 'clear',
        fault_type VARCHAR(100),
        last_clear_time TIMESTAMP NULL,
        last_occupied_time TIMESTAMP NULL,
        status ENUM('ok', 'warning', 'fault') DEFAULT 'ok',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_site (site_id),
        INDEX idx_section (section_id),
        INDEX idx_status (occupancy_status),
        FOREIGN KEY (site_id) REFERENCES scada_sites(id) ON DELETE CASCADE,
        FOREIGN KEY (section_id) REFERENCES rail_track_sections(id) ON DELETE SET NULL
    ) ENGINE=InnoDB");
    echo "  ✓ rail_track_circuits_enhanced\n";

    // Enhanced Signals
    $conn->query("CREATE TABLE IF NOT EXISTS rail_signals_enhanced (
        id INT AUTO_INCREMENT PRIMARY KEY,
        site_id INT NOT NULL,
        section_id INT,
        signal_name VARCHAR(50) NOT NULL,
        signal_code VARCHAR(20) NOT NULL,
        signal_type ENUM('main', 'distant', 'shunt', 'repeater', 'banner') DEFAULT 'main',
        direction ENUM('up', 'down', 'bidirectional') DEFAULT 'up',
        current_aspect ENUM('red', 'yellow', 'double_yellow', 'green') DEFAULT 'red',
        commanded_aspect ENUM('red', 'yellow', 'double_yellow', 'green') DEFAULT 'red',
        lamp_ok BOOLEAN DEFAULT TRUE,
        lamp_status JSON,
        auto_mode BOOLEAN DEFAULT TRUE,
        override_active BOOLEAN DEFAULT FALSE,
        override_by VARCHAR(100),
        override_reason TEXT,
        status ENUM('ok', 'warning', 'fault') DEFAULT 'ok',
        last_aspect_change TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_site (site_id),
        INDEX idx_section (section_id),
        INDEX idx_aspect (current_aspect),
        FOREIGN KEY (site_id) REFERENCES scada_sites(id) ON DELETE CASCADE,
        FOREIGN KEY (section_id) REFERENCES rail_track_sections(id) ON DELETE SET NULL
    ) ENGINE=InnoDB");
    echo "  ✓ rail_signals_enhanced\n";

    // Enhanced Points (Switches)
    $conn->query("CREATE TABLE IF NOT EXISTS rail_points_enhanced (
        id INT AUTO_INCREMENT PRIMARY KEY,
        site_id INT NOT NULL,
        section_id INT,
        point_name VARCHAR(50) NOT NULL,
        point_code VARCHAR(20) NOT NULL,
        point_type ENUM('simple', 'double_slip', 'single_slip', 'crossover') DEFAULT 'simple',
        current_position ENUM('normal', 'reverse') DEFAULT 'normal',
        commanded_position ENUM('normal', 'reverse') DEFAULT 'normal',
        is_locked BOOLEAN DEFAULT FALSE,
        is_detected BOOLEAN DEFAULT TRUE,
        detection_status ENUM('normal_detected', 'reverse_detected', 'no_detection', 'both_detected') DEFAULT 'normal_detected',
        motor_current_a DECIMAL(5,2),
        switch_time_sec DECIMAL(4,2),
        auto_mode BOOLEAN DEFAULT TRUE,
        trailing_allowed BOOLEAN DEFAULT FALSE,
        status ENUM('ok', 'warning', 'fault') DEFAULT 'ok',
        last_movement TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_site (site_id),
        INDEX idx_section (section_id),
        INDEX idx_position (current_position),
        FOREIGN KEY (site_id) REFERENCES scada_sites(id) ON DELETE CASCADE,
        FOREIGN KEY (section_id) REFERENCES rail_track_sections(id) ON DELETE SET NULL
    ) ENGINE=InnoDB");
    echo "  ✓ rail_points_enhanced\n";

    // Interlocking Routes
    $conn->query("CREATE TABLE IF NOT EXISTS rail_interlocking_routes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        site_id INT NOT NULL,
        route_name VARCHAR(100) NOT NULL,
        route_code VARCHAR(20) NOT NULL,
        route_type ENUM('main', 'shunt', 'emergency') DEFAULT 'main',
        origin_signal_id INT,
        destination_signal_id INT,
        route_sections JSON,
        required_points JSON,
        conflicting_routes JSON,
        is_set BOOLEAN DEFAULT FALSE,
        is_locked BOOLEAN DEFAULT FALSE,
        set_time TIMESTAMP NULL,
        set_by VARCHAR(100),
        auto_cancellation BOOLEAN DEFAULT TRUE,
        cancellation_timer_sec INT DEFAULT 180,
        status ENUM('available', 'set', 'locked', 'cancelled', 'fault') DEFAULT 'available',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_site (site_id),
        INDEX idx_status (status),
        FOREIGN KEY (site_id) REFERENCES scada_sites(id) ON DELETE CASCADE,
        FOREIGN KEY (origin_signal_id) REFERENCES rail_signals_enhanced(id) ON DELETE CASCADE,
        FOREIGN KEY (destination_signal_id) REFERENCES rail_signals_enhanced(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");
    echo "  ✓ rail_interlocking_routes\n";

    // Trains
    $conn->query("CREATE TABLE IF NOT EXISTS rail_trains (
        id INT AUTO_INCREMENT PRIMARY KEY,
        site_id INT NOT NULL,
        train_number VARCHAR(20) NOT NULL,
        train_service VARCHAR(50),
        train_type ENUM('passenger', 'freight', 'maintenance', 'test') DEFAULT 'passenger',
        current_section_id INT,
        current_speed_kmh INT DEFAULT 0,
        direction ENUM('up', 'down') DEFAULT 'up',
        scheduled_arrival TIMESTAMP NULL,
        scheduled_departure TIMESTAMP NULL,
        actual_arrival TIMESTAMP NULL,
        actual_departure TIMESTAMP NULL,
        delay_minutes INT DEFAULT 0,
        train_length_m DECIMAL(6,2),
        number_of_cars INT,
        status ENUM('approaching', 'at_platform', 'departing', 'in_transit', 'terminated') DEFAULT 'in_transit',
        last_update TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_site (site_id),
        INDEX idx_section (current_section_id),
        INDEX idx_status (status),
        FOREIGN KEY (site_id) REFERENCES scada_sites(id) ON DELETE CASCADE,
        FOREIGN KEY (current_section_id) REFERENCES rail_track_sections(id) ON DELETE SET NULL
    ) ENGINE=InnoDB");
    echo "  ✓ rail_trains\n";

    // Platforms
    $conn->query("CREATE TABLE IF NOT EXISTS rail_platforms (
        id INT AUTO_INCREMENT PRIMARY KEY,
        site_id INT NOT NULL,
        section_id INT,
        platform_name VARCHAR(50) NOT NULL,
        platform_number VARCHAR(10),
        track_number INT,
        length_meters DECIMAL(6,2),
        platform_type ENUM('side', 'island', 'bay') DEFAULT 'side',
        current_train_id INT,
        occupancy_status ENUM('vacant', 'occupied', 'boarding', 'alighting') DEFAULT 'vacant',
        doors_status ENUM('closed', 'opening', 'open', 'closing') DEFAULT 'closed',
        platform_screen_doors BOOLEAN DEFAULT FALSE,
        psd_status ENUM('closed', 'opening', 'open', 'closing', 'fault') DEFAULT 'closed',
        status ENUM('operational', 'maintenance', 'closed') DEFAULT 'operational',
        last_train_departed TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_site (site_id),
        INDEX idx_section (section_id),
        INDEX idx_status (occupancy_status),
        FOREIGN KEY (site_id) REFERENCES scada_sites(id) ON DELETE CASCADE,
        FOREIGN KEY (section_id) REFERENCES rail_track_sections(id) ON DELETE SET NULL,
        FOREIGN KEY (current_train_id) REFERENCES rail_trains(id) ON DELETE SET NULL
    ) ENGINE=InnoDB");
    echo "  ✓ rail_platforms\n";

    // Level Crossings
    $conn->query("CREATE TABLE IF NOT EXISTS rail_level_crossings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        site_id INT NOT NULL,
        section_id INT,
        crossing_name VARCHAR(50) NOT NULL,
        crossing_code VARCHAR(20),
        crossing_type ENUM('automatic', 'manual', 'pedestrian') DEFAULT 'automatic',
        barrier_status ENUM('raised', 'lowering', 'lowered', 'raising', 'fault') DEFAULT 'raised',
        lights_status ENUM('off', 'flashing', 'fault') DEFAULT 'off',
        bells_status ENUM('off', 'ringing', 'fault') DEFAULT 'off',
        road_traffic_detected BOOLEAN DEFAULT FALSE,
        train_approaching BOOLEAN DEFAULT FALSE,
        approach_distance_m INT,
        barrier_lower_time TIMESTAMP NULL,
        barrier_raise_time TIMESTAMP NULL,
        status ENUM('ok', 'warning', 'fault', 'emergency') DEFAULT 'ok',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_site (site_id),
        INDEX idx_status (barrier_status),
        FOREIGN KEY (site_id) REFERENCES scada_sites(id) ON DELETE CASCADE,
        FOREIGN KEY (section_id) REFERENCES rail_track_sections(id) ON DELETE SET NULL
    ) ENGINE=InnoDB");
    echo "  ✓ rail_level_crossings\n";

    // Emergency Systems
    $conn->query("CREATE TABLE IF NOT EXISTS rail_emergency_systems (
        id INT AUTO_INCREMENT PRIMARY KEY,
        site_id INT NOT NULL,
        system_name VARCHAR(100) NOT NULL,
        system_type ENUM('emergency_stop', 'fire_alarm', 'evacuation', 'communication') DEFAULT 'emergency_stop',
        location VARCHAR(100),
        is_activated BOOLEAN DEFAULT FALSE,
        activated_time TIMESTAMP NULL,
        activated_by VARCHAR(100),
        reset_required BOOLEAN DEFAULT FALSE,
        status ENUM('normal', 'activated', 'fault') DEFAULT 'normal',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_site (site_id),
        INDEX idx_status (status),
        FOREIGN KEY (site_id) REFERENCES scada_sites(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");
    echo "  ✓ rail_emergency_systems\n";

    // Event Log
    $conn->query("CREATE TABLE IF NOT EXISTS rail_event_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        site_id INT NOT NULL,
        event_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        event_type ENUM('signal_change', 'point_movement', 'route_set', 'train_movement', 'alarm', 'operator_action', 'system_fault') NOT NULL,
        event_category ENUM('normal', 'warning', 'alarm', 'fault') DEFAULT 'normal',
        entity_type VARCHAR(50),
        entity_id INT,
        description TEXT NOT NULL,
        operator VARCHAR(100),
        auto_logged BOOLEAN DEFAULT TRUE,
        INDEX idx_site (site_id),
        INDEX idx_time (event_time),
        INDEX idx_type (event_type),
        FOREIGN KEY (site_id) REFERENCES scada_sites(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");
    echo "  ✓ rail_event_log\n";

    echo "<span class='ok'>✓ All rail tables created</span>\n\n";

    // ============================================
    // 2. CREATE SAMPLE RAIL DATA
    // ============================================
    echo "<span class='info'>Creating sample rail system data...</span>\n";

    // Delete existing data
    $conn->query("DELETE FROM rail_event_log WHERE site_id = {$siteId}");
    $conn->query("DELETE FROM rail_emergency_systems WHERE site_id = {$siteId}");
    $conn->query("DELETE FROM rail_level_crossings WHERE site_id = {$siteId}");
    $conn->query("DELETE FROM rail_platforms WHERE site_id = {$siteId}");
    $conn->query("DELETE FROM rail_trains WHERE site_id = {$siteId}");
    $conn->query("DELETE FROM rail_interlocking_routes WHERE site_id = {$siteId}");
    $conn->query("DELETE FROM rail_points_enhanced WHERE site_id = {$siteId}");
    $conn->query("DELETE FROM rail_signals_enhanced WHERE site_id = {$siteId}");
    $conn->query("DELETE FROM rail_track_circuits_enhanced WHERE site_id = {$siteId}");
    $conn->query("DELETE FROM rail_track_sections WHERE site_id = {$siteId}");

    // Track Sections
    $sections = [
        ['Platform 1 Approach', 'P1-APP', 1, 350.0, 60, 'platform'],
        ['Platform 1', 'P1', 1, 180.0, 0, 'platform'],
        ['Platform 1 Departure', 'P1-DEP', 1, 400.0, 80, 'main_line'],
        ['Platform 2 Approach', 'P2-APP', 2, 350.0, 60, 'platform'],
        ['Platform 2', 'P2', 2, 180.0, 0, 'platform'],
        ['Platform 2 Departure', 'P2-DEP', 2, 400.0, 80, 'main_line'],
        ['Junction East', 'JCT-E', 1, 150.0, 40, 'junction'],
        ['Main Line Section A', 'ML-A', 1, 1200.0, 100, 'main_line'],
        ['Siding 1', 'SID-1', 3, 250.0, 40, 'siding']
    ];

    $sectionIds = [];
    foreach ($sections as $section) {
        $stmt = $conn->prepare("INSERT INTO rail_track_sections (site_id, section_name, section_code, track_number, length_meters, max_speed_kmh, section_type)
                               VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issidis", $siteId, $section[0], $section[1], $section[2], $section[3], $section[4], $section[5]);
        $stmt->execute();
        $sectionIds[$section[1]] = $conn->insert_id;
    }
    echo "  ✓ " . count($sections) . " track sections\n";

    // Track Circuits
    $circuits = [
        ['TC-P1-APP', 'P1-APP', FALSE, 'clear', 1.25, 250.0],
        ['TC-P1', 'P1', TRUE, 'occupied', 0.85, 120.0],
        ['TC-P1-DEP', 'P1-DEP', FALSE, 'clear', 1.30, 280.0],
        ['TC-P2-APP', 'P2-APP', TRUE, 'occupied', 0.80, 110.0],
        ['TC-P2', 'P2', FALSE, 'clear', 1.25, 250.0],
        ['TC-P2-DEP', 'P2-DEP', FALSE, 'clear', 1.28, 275.0],
        ['TC-JCT-E', 'JCT-E', FALSE, 'clear', 1.22, 240.0],
        ['TC-ML-A', 'ML-A', FALSE, 'clear', 1.35, 295.0]
    ];

    foreach ($circuits as $circuit) {
        $stmt = $conn->prepare("INSERT INTO rail_track_circuits_enhanced
            (site_id, section_id, circuit_name, circuit_code, is_occupied, occupancy_status, voltage, current_ma)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iissisdd", $siteId, $sectionIds[$circuit[1]], $circuit[0], $circuit[0], $circuit[2], $circuit[3], $circuit[4], $circuit[5]);
        $stmt->execute();
    }
    echo "  ✓ " . count($circuits) . " track circuits\n";

    // Signals
    $signals = [
        ['S-P1-APP', 'P1-APP', 'main', 'up', 'green', TRUE],
        ['S-P1-DEP', 'P1-DEP', 'main', 'up', 'red', TRUE],
        ['S-P2-APP', 'P2-APP', 'main', 'down', 'yellow', TRUE],
        ['S-P2-DEP', 'P2-DEP', 'main', 'down', 'red', TRUE],
        ['S-JCT-E1', 'JCT-E', 'main', 'up', 'green', TRUE],
        ['S-JCT-E2', 'JCT-E', 'main', 'up', 'red', TRUE]
    ];

    $signalIds = [];
    foreach ($signals as $signal) {
        $lampStatus = json_encode(['red' => true, 'yellow' => true, 'green' => true]);
        $stmt = $conn->prepare("INSERT INTO rail_signals_enhanced
            (site_id, section_id, signal_name, signal_code, signal_type, direction, current_aspect, commanded_aspect, lamp_ok, lamp_status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iissssssis", $siteId, $sectionIds[$signal[1]], $signal[0], $signal[0], $signal[2], $signal[3], $signal[4], $signal[4], $signal[5], $lampStatus);
        $stmt->execute();
        $signalIds[$signal[0]] = $conn->insert_id;
    }
    echo "  ✓ " . count($signals) . " signals\n";

    // Points
    $points = [
        ['PNT-JCT-E1', 'JCT-E', 'simple', 'normal', TRUE, 'normal_detected', 2.5],
        ['PNT-JCT-E2', 'JCT-E', 'simple', 'reverse', TRUE, 'reverse_detected', 2.3],
        ['PNT-SID-1', 'ML-A', 'simple', 'normal', TRUE, 'normal_detected', 2.4]
    ];

    foreach ($points as $point) {
        $stmt = $conn->prepare("INSERT INTO rail_points_enhanced
            (site_id, section_id, point_name, point_code, point_type, current_position, is_locked, detection_status, switch_time_sec)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iissssisd", $siteId, $sectionIds[$point[1]], $point[0], $point[0], $point[2], $point[3], $point[4], $point[5], $point[6]);
        $stmt->execute();
    }
    echo "  ✓ " . count($points) . " points\n";

    // Trains
    $trains = [
        ['T-501', 'City Circle', 'passenger', $sectionIds['P1'], 0, 'up', 'at_platform', 8, 160.0, 6],
        ['T-502', 'Express North', 'passenger', $sectionIds['P2-APP'], 45, 'down', 'approaching', 0, 160.0, 6],
        ['T-503', 'Local', 'passenger', $sectionIds['ML-A'], 80, 'up', 'in_transit', 0, 160.0, 6]
    ];

    $trainIds = [];
    foreach ($trains as $train) {
        $stmt = $conn->prepare("INSERT INTO rail_trains
            (site_id, train_number, train_service, train_type, current_section_id, current_speed_kmh, direction, status, delay_minutes, train_length_m, number_of_cars)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssiissddi", $siteId, $train[0], $train[1], $train[2], $train[3], $train[4], $train[5], $train[6], $train[7], $train[8], $train[9]);
        $stmt->execute();
        $trainIds[$train[0]] = $conn->insert_id;
    }
    echo "  ✓ " . count($trains) . " trains\n";

    // Platforms
    $platforms = [
        ['Platform 1', '1', 1, 180.0, 'side', $trainIds['T-501'], 'occupied', 'open'],
        ['Platform 2', '2', 2, 180.0, 'side', null, 'vacant', 'closed']
    ];

    foreach ($platforms as $platform) {
        $stmt = $conn->prepare("INSERT INTO rail_platforms
            (site_id, section_id, platform_name, platform_number, track_number, length_meters, platform_type, current_train_id, occupancy_status, doors_status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iisidsiiss", $siteId, $sectionIds['P'.$platform[1]], $platform[0], $platform[1], $platform[2], $platform[3], $platform[4], $platform[5], $platform[6], $platform[7]);
        $stmt->execute();
    }
    echo "  ✓ " . count($platforms) . " platforms\n";

    // Level Crossing
    $stmt = $conn->prepare("INSERT INTO rail_level_crossings
        (site_id, section_id, crossing_name, crossing_code, crossing_type, barrier_status, lights_status, bells_status, train_approaching, approach_distance_m)
        VALUES (?, ?, 'Main Street Crossing', 'LC-MS-01', 'automatic', 'raised', 'off', 'off', FALSE, 500)");
    $stmt->bind_param("ii", $siteId, $sectionIds['ML-A']);
    $stmt->execute();
    echo "  ✓ 1 level crossing\n";

    // Emergency System
    $stmt = $conn->prepare("INSERT INTO rail_emergency_systems
        (site_id, system_name, system_type, location, is_activated, status)
        VALUES (?, 'Platform Emergency Stop', 'emergency_stop', 'Platform 1', FALSE, 'normal')");
    $stmt->bind_param("i", $siteId);
    $stmt->execute();
    echo "  ✓ 1 emergency system\n";

    // ============================================
    // SUCCESS
    // ============================================
    echo "\n<span class='ok'>========================================</span>\n";
    echo "<span class='ok'>SUCCESS! Rail System Created</span>\n";
    echo "<span class='ok'>========================================</span>\n\n";

    echo "Rail System Components:\n";
    echo "  ✓ 9 Track Sections\n";
    echo "  ✓ 8 Track Circuits\n";
    echo "  ✓ 6 Signals\n";
    echo "  ✓ 3 Points (Switches)\n";
    echo "  ✓ 3 Active Trains\n";
    echo "  ✓ 2 Platforms\n";
    echo "  ✓ 1 Level Crossing\n";
    echo "  ✓ 1 Emergency System\n\n";

    echo "Features:\n";
    echo "  • Real-time track circuit monitoring\n";
    echo "  • Signal aspect control (Red/Yellow/Green)\n";
    echo "  • Point position control and detection\n";
    echo "  • Train tracking and scheduling\n";
    echo "  • Platform management\n";
    echo "  • Level crossing control\n";
    echo "  • Interlocking logic\n";
    echo "  • Emergency systems\n";
    echo "  • Comprehensive event logging\n\n";

    echo "Next Steps:\n";
    echo "1. View SCADA HMI: <a href='scada_hmi.php' style='color:#0ff;'>scada_hmi.php</a>\n";
    echo "2. Access Rail System tab\n";
    echo "3. Control signals, points, and routes\n";
    echo "4. Monitor train movements\n";

    $conn->close();

} catch (Exception $e) {
    echo "\n<span class='err'>❌ ERROR: " . $e->getMessage() . "</span>\n";
    echo "<span class='err'>" . $e->getTraceAsString() . "</span>\n";
    exit(1);
}

echo "</pre>";
echo "<br><a href='scada_hmi.php' style='background:#667eea;color:white;padding:15px 30px;text-decoration:none;border-radius:5px;display:inline-block;'>Open SCADA HMI</a>";
echo "</body></html>";
?>
