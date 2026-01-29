<?php
/**
 * Setup Admin User
 * Ensures admin user exists with correct password
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Setup Admin User</title>";
echo "<style>
body { font-family: Arial; padding: 20px; background: #f5f5f5; }
.success { color: green; font-weight: bold; }
.error { color: red; font-weight: bold; }
.info { color: blue; }
h1 { color: #667eea; }
.box { background: white; padding: 20px; margin: 10px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
code { background: #e0e0e0; padding: 2px 6px; border-radius: 3px; }
</style></head><body>";

echo "<h1>🔐 Admin User Setup</h1>";

require_once 'classes/Database.php';

try {
    $db = Database::getInstance();
    echo "<div class='box'><p class='success'>✓ Database connected</p></div>";
} catch (Exception $e) {
    echo "<div class='box'><p class='error'>✗ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</p></div>";
    exit;
}

// Check if admin_users table exists
echo "<div class='box'><h2>Checking Tables...</h2>";
try {
    $db->query("SELECT 1 FROM admin_users LIMIT 1");
    echo "<p class='success'>✓ admin_users table exists</p>";
} catch (Exception $e) {
    echo "<p class='error'>✗ admin_users table does not exist!</p>";
    echo "<p>Please import database/cms_tables.sql first</p>";
    echo "</div></body></html>";
    exit;
}
echo "</div>";

// Check for existing admin user
echo "<div class='box'><h2>Checking Admin User...</h2>";

$existingAdmin = $db->fetchOne("SELECT * FROM admin_users WHERE username = 'admin'");

if ($existingAdmin) {
    echo "<p class='info'>ℹ Admin user already exists</p>";
    echo "<p><strong>Username:</strong> " . htmlspecialchars($existingAdmin['username']) . "</p>";
    echo "<p><strong>Email:</strong> " . htmlspecialchars($existingAdmin['email'] ?? 'Not set') . "</p>";
    echo "<p><strong>Active:</strong> " . ($existingAdmin['active'] ? 'Yes' : 'No') . "</p>";
    echo "<p><strong>Last Login:</strong> " . ($existingAdmin['last_login'] ?? 'Never') . "</p>";
} else {
    echo "<p class='info'>ℹ No admin user found - creating one...</p>";
}

// Generate fresh password hash
$password = 'admin123';
$newHash = password_hash($password, PASSWORD_DEFAULT);

echo "<h3>Fresh Password Hash</h3>";
echo "<p><code>$newHash</code></p>";

// Verify it works
if (password_verify($password, $newHash)) {
    echo "<p class='success'>✓ Hash verified successfully!</p>";
}

// Insert or update admin user
try {
    $db->query("
        INSERT INTO admin_users (username, password_hash, email, full_name, role, active)
        VALUES ('admin', ?, 'admin@example.com', 'System Administrator', 'admin', 1)
        ON DUPLICATE KEY UPDATE
            password_hash = ?,
            active = 1
    ", [$newHash, $newHash]);

    echo "<p class='success'>✓ Admin user created/updated successfully!</p>";

    // Verify login will work
    $testUser = $db->fetchOne("SELECT * FROM admin_users WHERE username = 'admin' AND active = 1");

    if ($testUser && password_verify($password, $testUser['password_hash'])) {
        echo "<p class='success' style='font-size: 18px;'>✅ Login test PASSED! You can now login with:</p>";
        echo "<div style='background: #e3f2fd; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
        echo "<p><strong>URL:</strong> <a href='admin/login.php'>http://localhost/networkscan/admin/login.php</a></p>";
        echo "<p><strong>Username:</strong> <code>admin</code></p>";
        echo "<p><strong>Password:</strong> <code>admin123</code></p>";
        echo "</div>";
    } else {
        echo "<p class='error'>✗ Login test FAILED!</p>";
    }

} catch (Exception $e) {
    echo "<p class='error'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</div>";

echo "<div class='box'>";
echo "<h2>Next Steps</h2>";
echo "<p>✓ Admin user is ready</p>";
echo "<p>✓ Try logging in at <a href='admin/login.php'>admin/login.php</a></p>";
echo "<p style='margin-top: 20px;'>";
echo "<a href='admin/login.php' style='background: #667eea; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-size: 16px;'>Go to Login →</a>";
echo "</p>";
echo "</div>";

echo "</body></html>";
?>
