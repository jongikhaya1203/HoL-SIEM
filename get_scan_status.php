<?php
/**
 * Get current scan status
 */
header('Content-Type: application/json');

require_once __DIR__ . '/classes/Database.php';

try {
    $scanId = $_GET['scan_id'] ?? null;

    if (!$scanId) {
        throw new Exception('Scan ID required');
    }

    $db = Database::getInstance();
    $scan = $db->fetchOne("SELECT * FROM scans WHERE id = ?", [$scanId]);

    if (!$scan) {
        throw new Exception('Scan not found');
    }

    // Use real progress if available, otherwise estimate
    $progress = $scan['progress'] ?? 0;
    if ($scan['status'] === 'running' && $progress == 0) {
        // Fallback: Estimate progress based on time elapsed
        $started = strtotime($scan['started_at']);
        $elapsed = time() - $started;
        // Assume average scan takes 120 seconds (2 minutes with optimization)
        $progress = min(95, ($elapsed / 120) * 100);
    } elseif ($scan['status'] === 'completed') {
        $progress = 100;
    }

    echo json_encode([
        'success' => true,
        'scan' => [
            'id' => $scan['id'],
            'status' => $scan['status'],
            'progress' => round($progress),
            'total_hosts' => $scan['total_hosts'],
            'total_vulnerabilities' => $scan['total_vulnerabilities'],
            'critical_count' => $scan['critical_count'],
            'high_count' => $scan['high_count'],
            'medium_count' => $scan['medium_count'],
            'low_count' => $scan['low_count'],
            'info_count' => $scan['info_count'],
            'started_at' => $scan['started_at'],
            'completed_at' => $scan['completed_at']
        ]
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
