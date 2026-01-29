<?php
/**
 * Update Service Management Modules
 * Fixes Remote Support and IT Service Desk module entries in the database
 */

require_once 'config/database.php';

try {
    $db = Database::getInstance();

    // Update Remote Support module
    $db->execute(
        "UPDATE modules SET
            status = 'active',
            implementation_level = 'full',
            url = 'modules/remote_support.php',
            description = 'Comprehensive remote IT support with desktop sharing, file transfer, and system management'
        WHERE module_code = 'DRE'"
    );

    // Update IT Service Desk module
    $db->execute(
        "UPDATE modules SET
            status = 'active',
            implementation_level = 'full',
            url = 'service_desk.php',
            description = 'Full ITSM solution with incidents, problems, changes, assets, knowledge base, and SLA management'
        WHERE module_code = 'SERVICE_DESK'"
    );

    echo "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 30px; background: #f0fff0; border: 2px solid #4CAF50; border-radius: 10px;'>";
    echo "<h2 style='color: #4CAF50; margin-top: 0;'>✅ Modules Updated Successfully!</h2>";
    echo "<p>The following modules have been updated:</p>";
    echo "<ul>";
    echo "<li><strong>Remote Support (DRE)</strong> - Status: Active, URL: modules/remote_support.php</li>";
    echo "<li><strong>IT Service Desk (SERVICE_DESK)</strong> - Status: Active, URL: service_desk.php</li>";
    echo "</ul>";
    echo "<p style='margin-top: 20px;'><a href='index.php' style='background: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Return to Dashboard</a></p>";
    echo "</div>";

} catch (Exception $e) {
    echo "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 30px; background: #fff0f0; border: 2px solid #f44336; border-radius: 10px;'>";
    echo "<h2 style='color: #f44336; margin-top: 0;'>❌ Update Failed</h2>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p style='margin-top: 20px;'><a href='index.php' style='background: #f44336; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Return to Dashboard</a></p>";
    echo "</div>";
}
?>
