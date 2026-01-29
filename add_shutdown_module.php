<?php
/**
 * Add Emergency Shutdown Module to Dashboard
 * Adds the ESD system to the main IOC dashboard
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'localhost';
$username = 'root';
$password = '';
$database = 'network_security';

echo "<!DOCTYPE html><html><head><title>Add Shutdown Module</title>";
echo "<style>body{font-family:monospace;background:#1a1a2e;color:#0f0;padding:20px;}";
echo ".err{color:#f00;}.ok{color:#0f0;}.info{color:#0ff;}</style></head><body>";
echo "<h2>Add Emergency Shutdown Module to Dashboard</h2><pre>";

try {
    $conn = new mysqli($host, $username, $password, $database);

    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    echo "<span class='ok'>✓ Connected to database</span>\n\n";

    // Check if modules table exists
    $result = $conn->query("SHOW TABLES LIKE 'modules'");

    if ($result->num_rows == 0) {
        echo "<span class='err'>✗ Modules table doesn't exist. Creating it...</span>\n";

        // Create modules table
        $conn->query("CREATE TABLE IF NOT EXISTS modules (
            id INT AUTO_INCREMENT PRIMARY KEY,
            module_code VARCHAR(50) UNIQUE NOT NULL,
            module_name VARCHAR(100) NOT NULL,
            description TEXT,
            category VARCHAR(50) NOT NULL,
            url VARCHAR(255) NOT NULL,
            icon VARCHAR(10) DEFAULT '📦',
            status ENUM('active', 'beta', 'inactive', 'coming_soon') DEFAULT 'active',
            implementation_level ENUM('full', 'partial', 'placeholder', 'planned') DEFAULT 'full',
            enabled BOOLEAN DEFAULT TRUE,
            display_order INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_category (category),
            INDEX idx_status (status),
            INDEX idx_enabled (enabled)
        ) ENGINE=InnoDB");

        echo "<span class='ok'>✓ Modules table created</span>\n";
    }

    // Check if shutdown module already exists
    $check = $conn->query("SELECT id FROM modules WHERE module_code = 'ESD'");

    if ($check->num_rows > 0) {
        echo "<span class='info'>ℹ Shutdown module already exists. Updating...</span>\n";

        $stmt = $conn->prepare("UPDATE modules SET
            module_name = ?,
            description = ?,
            category = ?,
            url = ?,
            icon = ?,
            status = ?,
            implementation_level = ?,
            enabled = ?,
            display_order = ?
            WHERE module_code = 'ESD'");

        $module_name = "Emergency Shutdown System";
        $description = "Automated plant shutdown and startup management. Emergency shutdown sequences (ESD), normal shutdowns, startup procedures, safety interlocks, and permissives. IEC 61511 & ISA-84 compliant.";
        $category = "safety_systems";
        $url = "shutdown_manager.php";
        $icon = "🔴";
        $status = "active";
        $implementation_level = "full";
        $enabled = 1;
        $display_order = 2;

        $stmt->bind_param("sssssssii",
            $module_name,
            $description,
            $category,
            $url,
            $icon,
            $status,
            $implementation_level,
            $enabled,
            $display_order
        );

        $stmt->execute();
        echo "<span class='ok'>✓ Shutdown module updated successfully!</span>\n";

    } else {
        echo "<span class='info'>ℹ Adding new Shutdown module...</span>\n";

        $stmt = $conn->prepare("INSERT INTO modules
            (module_code, module_name, description, category, url, icon, status, implementation_level, enabled, display_order)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $module_code = "ESD";
        $module_name = "Emergency Shutdown System";
        $description = "Automated plant shutdown and startup management. Emergency shutdown sequences (ESD), normal shutdowns, startup procedures, safety interlocks, and permissives. IEC 61511 & ISA-84 compliant.";
        $category = "safety_systems";
        $url = "shutdown_manager.php";
        $icon = "🔴";
        $status = "active";
        $implementation_level = "full";
        $enabled = 1;
        $display_order = 2;

        $stmt->bind_param("ssssssssii",
            $module_code,
            $module_name,
            $description,
            $category,
            $url,
            $icon,
            $status,
            $implementation_level,
            $enabled,
            $display_order
        );

        if ($stmt->execute()) {
            echo "<span class='ok'>✓ Shutdown module added successfully!</span>\n";
        } else {
            throw new Exception("Failed to insert module: " . $conn->error);
        }
    }

    echo "\n";
    echo "<span class='ok'>========================================</span>\n";
    echo "<span class='ok'>SUCCESS! ESD Module Added to Dashboard</span>\n";
    echo "<span class='ok'>========================================</span>\n\n";

    echo "Module Details:\n";
    echo "  • Code: ESD\n";
    echo "  • Name: Emergency Shutdown System\n";
    echo "  • Category: Safety Systems\n";
    echo "  • Status: Active\n";
    echo "  • Implementation: Full\n";
    echo "  • URL: shutdown_manager.php\n\n";

    echo "Features:\n";
    echo "  ✓ Multi-level shutdown classification (L0-L3, ESD1-ESD3)\n";
    echo "  ✓ Automated sequence execution\n";
    echo "  ✓ Safety interlocks\n";
    echo "  ✓ Startup permissives\n";
    echo "  ✓ Comprehensive logging\n";
    echo "  ✓ IEC 61511 & ISA-84 compliant\n\n";

    echo "Next Steps:\n";
    echo "1. Visit homepage: <a href='index.php' style='color:#0ff;'>http://localhost/networkscanscada/</a>\n";
    echo "2. Look for the 🔴 Emergency Shutdown System module\n";
    echo "3. Click to access the shutdown manager\n";
    echo "4. Or access directly: <a href='shutdown_manager.php' style='color:#0ff;'>shutdown_manager.php</a>\n\n";

    echo "Documentation:\n";
    echo "  📖 <a href='SHUTDOWN_SYSTEM_README.md' style='color:#0ff;'>SHUTDOWN_SYSTEM_README.md</a>\n";

    $conn->close();

} catch (Exception $e) {
    echo "\n<span class='err'>❌ ERROR: " . $e->getMessage() . "</span>\n";
    echo "\nFailed to add shutdown module.\n";
    exit(1);
}

echo "</pre>";
echo "<br><a href='index.php' style='background:#667eea;color:white;padding:15px 30px;text-decoration:none;border-radius:5px;display:inline-block;'>Go to Dashboard</a>";
echo "&nbsp;<a href='shutdown_manager.php' style='background:#c62828;color:white;padding:15px 30px;text-decoration:none;border-radius:5px;display:inline-block;'>Open Shutdown Manager</a>";
echo "</body></html>";
?>
