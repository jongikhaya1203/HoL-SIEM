<?php
/**
 * Start scan in background and return immediately
 */
header('Content-Type: application/json');

require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/classes/NetworkScanner.php';

try {
    $target = $_POST['target'] ?? null;
    $type = $_POST['type'] ?? 'full';
    $name = $_POST['name'] ?? 'Scan ' . date('Y-m-d H:i:s');

    if (!$target) {
        throw new Exception('Target required');
    }

    // Create scan record
    $db = Database::getInstance();
    $scanner = new NetworkScanner(true);
    $scanId = $scanner->startScan($target, $type, ['scan_name' => $name]);

    // Start OPTIMIZED scan in background using Windows
    $phpPath = 'C:\\xampp\\php\\php.exe';
    $scriptPath = __DIR__ . '\\run_scan_optimized.php';

    // Use CMD /C START to properly detach the process
    // Remove output redirection so errors can be logged
    $command = "cmd /c start /B \"\" \"$phpPath\" \"$scriptPath\" $scanId \"$target\" \"$type\"";

    // Use proc_open for better process control
    $descriptorspec = [
        0 => ["pipe", "r"],  // stdin
        1 => ["pipe", "w"],  // stdout
        2 => ["pipe", "w"]   // stderr
    ];

    $process = proc_open($command, $descriptorspec, $pipes);

    if (is_resource($process)) {
        // Close pipes immediately to detach
        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        // Don't wait for process to finish
        proc_close($process);
    }

    echo json_encode([
        'success' => true,
        'scan_id' => $scanId,
        'message' => 'Scan started in background'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
