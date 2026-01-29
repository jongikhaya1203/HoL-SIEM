<?php
/**
 * Update Application Name to IOC Intelligent Operating Centre
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Update App Name</title>";
echo "<style>
body { font-family: Arial; padding: 40px; background: #f5f5f5; }
.box { background: white; padding: 30px; border-radius: 8px; max-width: 600px; margin: 0 auto; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
h1 { color: #667eea; }
.success { color: green; font-weight: bold; font-size: 18px; }
.error { color: red; font-weight: bold; }
.btn { background: #667eea; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 20px; font-weight: 600; }
</style></head><body>";

echo "<div class='box'>";
echo "<h1>📝 Update Application Name</h1>";

require_once 'classes/Database.php';

try {
    $db = Database::getInstance();

    // Update the app name in settings
    $db->query("
        INSERT INTO settings (setting_key, setting_value, setting_type)
        VALUES ('app_name', 'IOC Intelligent Operating Centre', 'string')
        ON DUPLICATE KEY UPDATE setting_value = 'IOC Intelligent Operating Centre'
    ");

    echo "<p class='success'>✅ Application name updated successfully!</p>";
    echo "<p>The dashboard will now display: <strong>IOC Intelligent Operating Centre</strong></p>";

    echo "<p style='margin-top: 30px;'>";
    echo "<a href='index.php' class='btn'>🏠 Go to Dashboard →</a>";
    echo "</p>";

} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</div>";
echo "</body></html>";
?>
