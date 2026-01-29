<?php
require_once __DIR__ . '/classes/Database.php';
$db = Database::getInstance();

try {
    $db->query("UPDATE modules SET status = 'active', implementation_level = 'full', url = 'modules/remote_support.php', description = 'Comprehensive remote IT support with desktop sharing, file transfer, and system management' WHERE module_code = 'DRE'");
    echo "Module updated successfully!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
