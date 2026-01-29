<?php
/**
 * Get detailed scan info including error messages
 */
require_once __DIR__ . '/classes/Database.php';

$scanId = $_GET['scan_id'] ?? 20;

$db = Database::getInstance();
$scan = $db->fetchOne("SELECT * FROM scans WHERE id = ?", [$scanId]);

header('Content-Type: application/json');
echo json_encode($scan, JSON_PRETTY_PRINT);
