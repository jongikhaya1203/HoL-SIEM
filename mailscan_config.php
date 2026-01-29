<?php
// Mail Scanning DLP System - Configuration

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'mailscan_dlp');

// System configuration
define('ITEMS_PER_PAGE', 20);
define('MAX_CONTEXT_LENGTH', 200);

// Database connection
function getDBConnection() {
    try {
        $conn = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        return $conn;
    } catch(PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}

// Utility function to sanitize output
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Get severity color
function getSeverityColor($severity) {
    switch($severity) {
        case 'critical': return '#dc3545';
        case 'high': return '#fd7e14';
        case 'medium': return '#ffc107';
        case 'low': return '#17a2b8';
        default: return '#6c757d';
    }
}

// Get status badge
function getStatusBadge($status) {
    $badges = [
        'pending' => '<span class="badge" style="background: #6c757d">Pending</span>',
        'scanned' => '<span class="badge" style="background: #28a745">Scanned</span>',
        'flagged' => '<span class="badge" style="background: #dc3545">Flagged</span>',
        'blocked' => '<span class="badge" style="background: #000">Blocked</span>'
    ];
    return $badges[$status] ?? $status;
}
?>
